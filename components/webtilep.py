#!/usr/bin/env python
import web
import xml.etree.ElementTree as ET
import sys, getopt, os, json, mapnik, tempfile, urllib2, cPickle, shutil, zlib
import math
import sqlite3 as lite

from subprocess import Popen, PIPE
from xml.dom import minidom
from shutil import copyfile

try:
  from mapnik import ProjTransform, Projection, Box2d, Image
except ImportError, E:
  sys.exit("Requires Mapnik SVN r822 or greater:\n%s" % E)

cached_tiles = {}
cached_xml = {}
xmldir = '@amilna/yii2-iyo/xml'
ip = "127.0.0.1"	
ports = [1401]
webdir = "@web"	
zoomcache = -1
tiledir = "@web/tile"
tileURL = "/tile"
dbdsn = "pgsql:host=localhost;dbname=iyo"
dbpfx = "tbl_"
dbusr = "postgres"
dbpwd = "postgres"
geomCol = "the_geom"
sslCert = False
sslKey = False
execFile = "@amilna/yii2-iyo/components/exec"
web.config.debug = False

def main(argv):		
	global ip
	global ports
	global webdir
	global xmldir
	global zoomcache
	global tiledir
	global dbdsn
	global dbpfx
	global dbusr
	global dbpwd
	global geomCol
	global sslCert
	global sslKey
	global execFile
	global tileURL
		   
	try:
		opts, args = getopt.getopt(argv,"ha:p:d:x:c:t:D:P:U:W:G:C:K:E:T:",["ipAddress","port","webDir","xmlDir","zoomCache","tileURL","dsn","tablePrefix","username","password","geomCol","sslCert","sslkey","execFile","tileURL"])
	except getopt.GetoptError:
		print 'webtilep.py -a <ipAddress> -p <port> -d <webDir> -x <xmlDir> -c <zoomCache> -t <tiledir> -D <dsn> -P <tablePrefix> -U <username> -W <password> -G <geomCol> -C <sslCert> -K <sslKey> -E <execFile> -T <tileURL>'
		sys.exit(2)	  	
	  
	for opt, arg in opts:
		if opt == '-h':
			print 'webtilep.py -a <ipAddress> -p <port> -d <webDir> -x <xmlDir> -c <zoomCache> -t <tiledir> -D <dsn> -P <tablePrefix> -U <username> -W <password> -G <geomCol> -C <sslCert> -K <sslKey> -E <execFile> -T <tileURL>'
			sys.exit()
		elif opt in ("-a", "--ipAddress"):
			ip = arg			
		elif opt in ("-p", "--port"):
			ports = []
			for p in arg.split(','):
				ports.append(int(p))
		elif opt in ("-d", "--webDir"):			
			webdir = arg
		elif opt in ("-x", "--xmlDir"):			
			xmldir = arg	
		elif opt in ("-c", "--zoomCache"):
			zoomcache = int(arg)
		elif opt in ("-t", "--tiledir"):
			tiledir = arg			
		elif opt in ("-D", "--dsn"):
			dbdsn = arg	
		elif opt in ("-P", "--tablePrefix"):
			dbpfx = arg	
		elif opt in ("-U", "--username"):
			dbusr = arg		
		elif opt in ("-W", "--password"):
			dbpwd = arg		
		elif opt in ("-G", "--geomCol"):
			geomCol = arg		
		elif opt in ("-C", "--sslCert"):
			sslCert = arg		
		elif opt in ("-K", "--sslKey"):
			sslKey = arg			
		elif opt in ("-E", "--execFile"):
			execFile = arg		
		elif opt in ("-T", "--tileURL"):
			tileURL = arg	
				
					
	urls = (		
		tileURL+'/([a-zA-Z0-9_]+)', 'clear_tile',
		tileURL+'/([a-zA-Z0-9_]+)/(\d+)/(\d+)/(\d+).(png|json)', 'get_tile',
		tileURL+'/([a-zA-Z0-9_]+)/(\d+)/(\d+)/(\d+)/(\d+).png', 'get_image'
	)			
	
	if sslCert and sslKey:
		from web.wsgiserver import CherryPyWSGIServer
		CherryPyWSGIServer.ssl_certificate = sslCert
		CherryPyWSGIServer.ssl_private_key = sslKey
		
	app = TMS(urls, globals())
	app.run(ip=ip,port=int(ports[0]))				

class TMS(web.application):
	def run(self, ip='0.0.0.0', port=8080, *middleware):
		func = self.wsgifunc(*middleware)
		return web.httpserver.runsimple(func, (ip, port))

class Tilep:
	def getLonLat(self, x, y, z):
		n = 2.0 ** z
		lon = x / n * 360.0 - 180.0
		lat_rad = math.atan(math.sinh(math.pi * (1 - 2 * y / n)))
		lat = math.degrees(lat_rad)
		return (lon, lat)
	def getTile(self, lon, lat, z):
		lat_rad = math.radians(lat)
		n = 2.0 ** z
		x = int((lon + 180.0) / 360.0 * n)
		y = int((1.0 - math.log(math.tan(lat_rad) + (1 / math.cos(lat_rad))) / math.pi) / 2.0 * n)
		return (x, y)
	def getBbox(self, x, y, z):		
		s = self.getLonLat(x, y, z);
		e = self.getLonLat(x+1, y+1, z);										
		if (s[0] > 360):
			s[0] = (s[0]%360)+(s[0]-math.floor(s[0]))
			e[0] = (e[0]%360)+(e[0]-math.floor(e[0]))		
		if (s[0] >= 180):
			s[0] = s[0]-360			
		if (e[0] >= 180):
			e[0] = e[0]-360		
		return (s[0],e[1],e[0],s[1])
	def getDb(self, dbname, isxml=False):
		adir = os.path.dirname(os.path.realpath(__file__));	
		if not isxml:
			sub = '/dbs'
			csql = 'CREATE TABLE IF NOT EXISTS tiles (z INT NOT NULL, x INT NOT NULL, y INT NOT NULL, minx REAL NOT NULL, miny REAL NOT NULL, maxx REAL NOT NULL, maxy REAL NOT NULL, src BLOB NOT NULL , sffx TEXT NOT NULL )'
		else:
			sub = ''
			csql = 'CREATE TABLE IF NOT EXISTS xmls (tilename TEXT NOT NULL, src BLOB NOT NULL )'								
			
		if not os.path.exists(adir+sub):
			try:				
				os.makedirs(adir+sub)								
			except:
				pass
		
		src = adir+'/tile.db';
		dst = adir+sub+'/'+dbname+'.db';
		if not os.path.exists(dst):
			copyfile(src, dst)				
			if os.path.exists(dst):				
				con = lite.connect(dst)
				cur = con.cursor()    				
				cur.execute(csql)								
			else:
				dst = False			
		
		return dst
	def getXml(self, tilename, isforce = False, isimage = False):
		
		xmlstr = False		
		isfromdir = (tilename[:3] != 'iyo')
		if isfromdir:
			if os.path.exists(xmldir):
				if tilename+'.xml' in os.listdir(xmldir):
					xmlstr = open(xmldir+'/'+tilename+'.xml',"rb").read()							
		
		else:			
			#tilep = Tilep()		
			dbfile = self.getDb('xml',True)	
			if (dbfile):
				con = lite.connect(dbfile)
				cur = con.cursor()    				
			
				if not isforce:		
					sql = "SELECT * from xmls WHERE tilename = ?;"
					cur.execute(sql,[tilename])		
					rows = cur.fetchall()
					n = 0
					for row in rows:						
						xmlstr = zlib.decompress(row[1])
																		
				if not xmlstr:
					ts = tilename.split('_')
					layId = '-1'
					layName = ''
					for n in range(0,len(ts)):
						if n == 0:
							layId = ts[n][3:]
						else:
							pre = '_'
							if (layName == ''):
								pre = ''
							layName = layName+pre+ts[n]															
																	
					p = Popen([execFile,"-action=getXml","-dsn="+dbdsn,"-tablePrefix="+dbpfx,"-username="+dbusr,"-password="+dbpwd,"-param="+layId+":"+layName+":"+geomCol+":1:"+webdir], stdin=PIPE, stdout=PIPE, stderr=PIPE)
					xoutput, err = p.communicate(b"input data that is passed to subprocess' stdin")											
					if xoutput[:5] == '<Map ':
						xmlstr = xoutput
						
					if xmlstr:									
						sql = "DELETE from xmls WHERE tilename = ?;"
						cur.execute(sql,[tilename])
						sql = "INSERT INTO xmls VALUES (?,?);"			
						cur.execute(sql,[tilename,lite.Binary(zlib.compress(xmlstr))])
						con.commit()
						cur.close()
				
		return xmlstr	

class clear_tile:
	def GET(self, tilename):				
		qs = web.input(r='',x='')								
		
		tilep = Tilep()
				
		if qs.r != '':			
			from subprocess import call
			call(["rm","-R",tiledir+"/"+tilename])
			dbfilet = tilep.getDb(tilename)
			if dbfilet :
				call(["rm",dbfilet])
						
		isforce = True
		if qs.x == '':
			isforce = False
			
		xmlstr = tilep.getXml(tilename,isforce)		
		
		web.header("Access-Control-Allow-Origin", "*")
		web.header("Content-Type", "text/plain")
		if xmlstr:			
			return '{"tilename":"'+tilename+'","status":true}'			
		else :
			return '{"tilename":"'+tilename+'","status":false}'	
				
			
class get_tile:
	def GET(self, tilename, z, x, y, sffx):								
		
		z = int(z)
		x = int(x)
		y = int(y)
		
		if (z < 0 or z > 19):
			return ''
		
		cType = {
            "png":"images/png",
            "json":"text/plain"
		}
				
		qs = web.input(r='',x='')
		isCache = False
		if qs.r == '':
			isCache = True								
		
		output = False			
		tilep = Tilep()	
		
		web.header("Access-Control-Allow-Origin", "*")
		
		dbfilet = tilep.getDb(tilename)
		if dbfilet:
			cont = lite.connect(dbfilet)
			curt = cont.cursor()
		
		if isCache and dbfilet:
			sql = "SELECT * from tiles WHERE z = ? AND x = ? AND y = ? AND sffx = ?;"				
			curt.execute(sql,[z,x,y,sffx])		
			rows = curt.fetchall()								
			for row in rows:						
				output = zlib.decompress(row[7])					
			
			if output:										
				web.header("Content-Type", cType[sffx])				
				return output
			
		if not output:
			isforce = True
			if qs.x == '':
				isforce = False
					
			xmlstr = tilep.getXml(tilename,isforce)
				
			if not xmlstr:				
				return ''
			else:																															
				box = tilep.getBbox(z=z,x=x,y=y)		
				geo_extent = Box2d(box[0],box[1],box[2],box[3])		
				
				geo_proj = Projection('+init=epsg:4326')
				merc_proj = Projection('+init=epsg:3857')	

				transform = ProjTransform(geo_proj,merc_proj)
				merc_extent = transform.forward(geo_extent)	
				
				mp = mapnik.Map(256,256)
				mapnik.load_map_from_string(mp,xmlstr)																
				mp.zoom_to_box(merc_extent)																							
									
				if sffx == 'png':
					im = Image(mp.width,mp.height)		
					mapnik.render(mp,im)
					output = im.tostring('png')																	
											
					if dbfilet:
						minx = box[0]																						
						miny = box[1]
						maxx = box[2]
						maxy = box[3]
						sql = "INSERT INTO tiles VALUES (?,?,?,?,?,?,?,?,?);"			
						curt.execute(sql,[z,x,y,minx,miny,maxx,maxy,lite.Binary(zlib.compress(output)),sffx])
						cont.commit()
						curt.close()
											
					#if zoomcache >= z :
					#	mapnik.render_to_file(mp, str(afile))																									
										
					web.header("Content-Type", cType[sffx])																						
					return output
				elif sffx == 'json':
					xmldoc = minidom.parseString(xmlstr)			
					itemlist = xmldoc.getElementsByTagName('Layer') 
					
					nlay = len(itemlist)
					fields = []
					resolution = 4 #Pixel resolution of output.   
					li = 0																								
																
					for ly in range(0, nlay):							
						resolution = 4
						dat = itemlist[ly].getElementsByTagName('Datasource')[0] 
						par = dat.getElementsByTagName('Parameter')	
						for s in par :
							dv = s.attributes['name'].value								
							dck = False								
							if (dv[6:] == ''):
								dck = True
							elif (dv[6:].isdigit()):
								dck = (z >= int(dv[6:]))
							
							if dv[:6] == 'fields' and dck and fields == [] :
								text = s.childNodes[0].nodeValue.encode("utf-8")
								fields = text.split(",")
								li = ly
								
							if dv == 'resolution':
								res = s.childNodes[0].nodeValue.encode("utf-8")									
								resolution = int(res)	
										
					layer_index = li #First layer on the map - index in m.layers
					key = "__id__"  #Field used for the key in mapnik (should probably be unique)		
						
					enfix = ""						
					#if ly > 0:
					#	enfix = "_"+str(ly)																			

					#return str(len(fields))

					if len(fields) > 0:		
						d = mapnik.render_grid(mp, layer_index, key, resolution, fields) #returns a dictionary		
						output = "grid("+json.dumps(d)+")"														
					else:
						output = ''																		
					
					if output != '':											
						
						if dbfilet:
							minx = box[0]																						
							miny = box[1]
							maxx = box[2]
							maxy = box[3]
							sql = "INSERT INTO tiles VALUES (?,?,?,?,?,?,?,?,?);"			
							curt.execute(sql,[z,x,y,minx,miny,maxx,maxy,lite.Binary(zlib.compress(output)),sffx])
							cont.commit()								
							curt.close()
						
						#if zoomcache >= z :
						#	f = open(afile,'wb')
						#	f.write(output)	
						#	f.close()
										
					web.header("Content-Type", cType[sffx])																						
					return output
				else:					
					return ''																	
			

class get_image:
	def GET(self, tilename, epsg, z, x, y):						
		
		sffx = 'png';		
		epsg = int(epsg)
		z = int(z)
		x = int(x)
		y = int(y)
		
		if (z < 0 or z > 19):
			return ''
		
		cType = {
            "png":"images/png"
		}
				
		qs = web.input(r='',x='')
		isCache = False
		if qs.r == '':
			isCache = True								
		
		output = False			
		tilep = Tilep()	
		
		web.header("Access-Control-Allow-Origin", "*")
		
		atilename = tilename+'_'+str(epsg)
		
		dbfilet = tilep.getDb(atilename)
		if dbfilet:
			cont = lite.connect(dbfilet)
			curt = cont.cursor()
				
		if isCache and dbfilet:		
			sql = "SELECT * from tiles WHERE z = ? AND x = ? AND y = ? AND sffx = ?;"				
			curt.execute(sql,[z,x,y,sffx])		
			rows = curt.fetchall()								
			for row in rows:						
				output = zlib.decompress(row[7])					
			
			if output:										
				web.header("Content-Type", cType[sffx])				
				return output
			
		if not output:																
			#if (epsg != 4326):			
			mp = mapnik.Map(256,256,'+init=epsg:'+str(epsg))
			#else:	
			#	mp = mapnik.Map(256,256)
				
			s = mapnik.Style()
			r = mapnik.Rule()
			r.symbols.append(mapnik.RasterSymbolizer())
			s.rules.append(r)
			mp.append_style('RStyle',s)
			
			tilep = Tilep()
			box = tilep.getBbox(z=z,x=x,y=y)		
			geo_extent = Box2d(box[0],box[1],box[2],box[3])		
			
			geo_proj = Projection('+init=epsg:4326')
			merc_proj = Projection('+init=epsg:'+str(epsg))	

			transform = ProjTransform(geo_proj,merc_proj)
			merc_extent = transform.forward(geo_extent)																										
			
			adir = os.path.dirname(os.path.realpath(__file__))
			coni = lite.connect(adir+'/indeks.db')
			curi = coni.cursor()    
			#sql = "SELECT * FROM indeks WHERE name = '"+btilename+"' AND epsg = "+str(epsg)+" AND ((minx >= "+str(merc_extent[0])+" AND miny >= "+str(merc_extent[1])+" AND maxx <= "+str(merc_extent[2])+" AND maxy <= "+str(merc_extent[3])+") OR (minx <= "+str(merc_extent[0])+" AND miny <= "+str(merc_extent[1])+" AND maxx >= "+str(merc_extent[2])+" AND maxy >= "+str(merc_extent[3])+"))"
			sql = "SELECT * FROM indeks WHERE name = ? AND epsg = ?;"
			#if epsg == 4326:
			#	sql = "SELECT * FROM indeks WHERE name = '"+btilename+"'"
			curi.execute(sql,[tilename,epsg])
			rows = curi.fetchall()
			n = 0
			for row in rows:						
				afilename = str(row[1])
				ds = mapnik.Gdal(file=afilename)
				#if (epsg != 4326):
				if (True):
					layer = mapnik.Layer('raster'+str(n),'+init=epsg:'+str(epsg))					
				#else:
				#	layer = mapnik.Layer('raster'+str(n))						
				layer.datasource = ds
				layer.styles.append('RStyle')
				mp.layers.append(layer)
				n = n+1
			
			#xmlstr = mapnik.save_map_to_string(mp)
			#print xmlstr								
			#mapnik.load_map_from_string(mp,xmlstr)																
			
			mp.zoom_to_box(merc_extent)																																										
													
			im = Image(mp.width,mp.height)		
			mapnik.render(mp,im)
			output = im.tostring('png')																
			
			
			if dbfilet:
				minx = box[0]																						
				miny = box[1]
				maxx = box[2]
				maxy = box[3]
				sql = "INSERT INTO tiles VALUES (?,?,?,?,?,?,?,?,?);"			
				curt.execute(sql,[z,x,y,minx,miny,maxx,maxy,lite.Binary(zlib.compress(output)),sffx])
				cont.commit()
				curt.close()
									
			#if zoomcache >= z :
			#	mapnik.render_to_file(mp, str(afile))																															
						
			web.header("Content-Type", cType[sffx])																						
			return output					
			
		else:
			return ''			
		

if __name__ == "__main__":
    main(sys.argv[1:])
    
app = web.application(urls, globals(), autoreload=False)
application = app.wsgifunc()    
