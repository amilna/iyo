#!/usr/bin/env python
import web
import xml.etree.ElementTree as ET
import sys, getopt, os, json, mapnik2, tempfile, urllib2, cPickle
import math
import sqlite3 as lite

from subprocess import Popen, PIPE
from xml.dom import minidom

try:
  from mapnik2 import ProjTransform, Projection, Box2d, Image
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

class clear_tile:
	def GET(self, tilename):
		global cached_tiles
		global cached_xml
		
		qs = web.input(r='',x='')				
		
		if qs.x != '':
			if tilename in cached_xml:
				del cached_xml[tilename]			
				
		if qs.r != '':			
			cached_tiles[tilename] = {}			
			from subprocess import call
			call(["rm","-R",tiledir+"/"+tilename])						
								
		xmlstr = (tilename[:3] != 'iyo')
		if os.path.exists(xmldir):
			if tilename+'.xml' in os.listdir(xmldir):
				xmlstr = open(xmldir+'/'+tilename+'.xml',"rb").read()
													
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
		
		web.header("Access-Control-Allow-Origin", "*")
		web.header("Content-Type", "text/plain")
		if xmlstr:
			cached_xml[tilename] = xmlstr
			return '{"tilename":"'+tilename+'","status":true}'			
		else :
			return '{"tilename":"'+tilename+'","status":false}'	
				
			
class get_tile:
	def GET(self, tilename, z, x, y, sffx):				
		
		global cached_tiles
		global cached_xml
		
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
		
		if isCache:
			tile = False
			if tilename in cached_tiles:
				if z in cached_tiles[tilename]:
					if x in cached_tiles[tilename][z]:
						if y in cached_tiles[tilename][z][x]:
							if sffx in cached_tiles[tilename][z][x][y]:
								tile = cached_tiles[tilename][z][x][y][sffx]																		
																	
			if (tile):
				web.header("Access-Control-Allow-Origin", "*")
				web.header("Content-Type", cType[sffx])
				return tile
			else:
				isCache = False
								
		if tilename not in cached_tiles:
			cached_tiles[tilename] = {}
		if z not in cached_tiles[tilename]:
			cached_tiles[tilename][z] = {}	
		if x not in cached_tiles[tilename][z]:	
			cached_tiles[tilename][z][x] = {}	
		if y not in cached_tiles[tilename][z][x]:
			cached_tiles[tilename][z][x][y] = {}												
		
		if not isCache:
			afile = tiledir+'/'+tilename+'/'+str(z)+'/'+str(x)+'/'+str(y)+'.'+sffx			
			if not os.path.exists(os.path.dirname(afile)) and zoomcache >= z :
				try:
					os.makedirs(os.path.dirname(afile))
				except:
					pass
													
			if os.path.exists(afile) and zoomcache >= z :
				web.header("Access-Control-Allow-Origin", "*")
				web.header("Content-Type", cType[sffx])				
				return open(afile,"rb").read()
			else:					
				xmlstr = False
				if tilename in cached_xml and qs.x == '':					
					xmlstr = cached_xml[tilename]
					
				if not xmlstr:
					if os.path.exists(xmldir):
						if tilename+'.xml' in os.listdir(xmldir):
							xmlstr = open(xmldir+'/'+tilename+'.xml',"rb").read()
																
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
				
				if not xmlstr:
					return ''
				else:					
					cached_xml[tilename] = xmlstr
					
					tilep = Tilep()
					box = tilep.getBbox(z=z,x=x,y=y)		
					geo_extent = Box2d(box[0],box[1],box[2],box[3])		
					
					geo_proj = Projection('+init=epsg:4326')
					merc_proj = Projection('+init=epsg:3857')	

					transform = ProjTransform(geo_proj,merc_proj)
					merc_extent = transform.forward(geo_extent)	
					
					mp = mapnik2.Map(256,256)
					mapnik2.load_map_from_string(mp,xmlstr)																
					mp.zoom_to_box(merc_extent)																							
										
					if sffx == 'png':
						im = Image(mp.width,mp.height)		
						mapnik2.render(mp,im)
						output = im.tostring('png')						
						
						cached_tiles[tilename][z][x][y][sffx] = output																								
												
						if zoomcache >= z :
							mapnik2.render_to_file(mp, str(afile))																									
						
						web.header("Access-Control-Allow-Origin", "*")
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
						key = "__id__"  #Field used for the key in mapnik2 (should probably be unique)		
							
						enfix = ""						
						#if ly > 0:
						#	enfix = "_"+str(ly)																			

						#return str(len(fields))

						if len(fields) > 0:		
							d = mapnik2.render_grid(mp, layer_index, key, resolution, fields) #returns a dictionary		
							output = "grid("+json.dumps(d)+")"														
						else:
							output = ''																		
						
						if output != '':						
							cached_tiles[tilename][z][x][y][sffx] = output
							if zoomcache >= z :
								f = open(afile,'wb')
								f.write(output)	
								f.close()
						
						web.header("Access-Control-Allow-Origin", "*")
						web.header("Content-Type", cType[sffx])																						
						return output
					else:
						return ''															
					
		else:
			return ''
			

class get_image:
	def GET(self, tilename, epsg, z, x, y):				
		
		global cached_tiles
		global cached_xml
		
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
		
		if isCache:
			tile = False
			if tilename in cached_tiles:
				if epsg in cached_tiles[tilename]:
					if z in cached_tiles[tilename][epsg]:
						if x in cached_tiles[tilename][epsg][z]:
							if y in cached_tiles[tilename][epsg][z][x]:
								if sffx in cached_tiles[tilename][epsg][z][x][y]:
									tile = cached_tiles[tilename][epsg][z][x][y][sffx]																		
																	
			if (tile):
				web.header("Access-Control-Allow-Origin", "*")
				web.header("Content-Type", cType[sffx])
				return tile
			else:
				isCache = False
								
		if tilename not in cached_tiles:
			cached_tiles[tilename] = {}
		if epsg not in cached_tiles[tilename]:
			cached_tiles[tilename][epsg] = {}	
		if z not in cached_tiles[tilename][epsg]:
			cached_tiles[tilename][epsg][z] = {}	
		if x not in cached_tiles[tilename][epsg][z]:	
			cached_tiles[tilename][epsg][z][x] = {}	
		if y not in cached_tiles[tilename][epsg][z][x]:
			cached_tiles[tilename][epsg][z][x][y] = {}												
		
		if not isCache:
			afile = tiledir+'/'+tilename+'/'+str(epsg)+'/'+str(z)+'/'+str(x)+'/'+str(y)+'.'+sffx			
			if not os.path.exists(os.path.dirname(afile)) and zoomcache >= z and qs.r == '':
				try:
					os.makedirs(os.path.dirname(afile))
				except:
					pass
													
			if os.path.exists(afile) and zoomcache >= z :
				web.header("Access-Control-Allow-Origin", "*")
				web.header("Content-Type", cType[sffx])				
				return open(afile,"rb").read()
			else:					
				xmlstr = False
				if tilename in cached_xml and qs.x == '':					
					xmlstr = cached_xml[tilename]
					
				if not xmlstr:
					
					#if (epsg != 4326):
					if (True):
						mp = mapnik2.Map(256,256,'+init=epsg:'+str(epsg))
					#else:	
					#	mp = mapnik2.Map(256,256)
						
					s = mapnik2.Style()
					r = mapnik2.Rule()
					r.symbols.append(mapnik2.RasterSymbolizer())
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
					con = lite.connect(adir+'/indeks.db')    
					cur = con.cursor()    
					#sql = "SELECT * FROM indeks WHERE name = '"+btilename+"' AND epsg = "+str(epsg)+" AND ((minx >= "+str(merc_extent[0])+" AND miny >= "+str(merc_extent[1])+" AND maxx <= "+str(merc_extent[2])+" AND maxy <= "+str(merc_extent[3])+") OR (minx <= "+str(merc_extent[0])+" AND miny <= "+str(merc_extent[1])+" AND maxx >= "+str(merc_extent[2])+" AND maxy >= "+str(merc_extent[3])+"))"
					sql = "SELECT * FROM indeks WHERE name = '"+tilename+"' AND epsg = "+str(epsg)+""
					#if epsg == 4326:
					#	sql = "SELECT * FROM indeks WHERE name = '"+btilename+"'"
					cur.execute(sql)
					rows = cur.fetchall()
					n = 0
					for row in rows:						
						afilename = row[1]					
						ds = mapnik2.Gdal(file=afilename)
						#if (epsg != 4326):
						if (True):
							layer = mapnik2.Layer('raster'+str(n),'+init=epsg:'+str(epsg))					
						#else:
						#	layer = mapnik2.Layer('raster'+str(n))						
						layer.datasource = ds
						layer.styles.append('RStyle')
						mp.layers.append(layer)
						n = n+1
					
					xmlstr = mapnik2.save_map_to_string(mp)
					#print xmlstr					
					#cached_xml[tilename] = xmlstr					
					#mapnik2.load_map_from_string(mp,xmlstr)																
					
					mp.zoom_to_box(merc_extent)																																										
															
					im = Image(mp.width,mp.height)		
					mapnik2.render(mp,im)
					output = im.tostring('png')																
					
					cached_tiles[tilename][epsg][z][x][y][sffx] = output																								
					if zoomcache >= z :
						mapnik2.render_to_file(mp, str(afile))																															
					
					web.header("Access-Control-Allow-Origin", "*")
					web.header("Content-Type", cType[sffx])																						
					return output					
					
		else:
			return ''			
		

if __name__ == "__main__":
    main(sys.argv[1:])
    
app = web.application(urls, globals(), autoreload=False)
application = app.wsgifunc()    
