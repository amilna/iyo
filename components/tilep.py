#!/usr/bin/env python

#import time
#t0 = time.time()

import sys, getopt, os, json, mapnik, tempfile, urllib2
from xml.dom import minidom

try:
  from mapnik import ProjTransform, Projection, Box2d, Image
except ImportError, E:
  sys.exit("Requires Mapnik SVN r822 or greater:\n%s" % E)

def main(argv):
	b = "-180,-90,180,90"	
	i = ""
	o = ""
	l = "-1"
	   
	try:
	  opts, args = getopt.getopt(argv,"hb:i:o:l:",["bbox","inputXml","output","layerGrid"])
	except getopt.GetoptError:
	  print 'tilep.py -b <bbox> -i <inputXml> -o <output> -l <layerGrid>'
	  sys.exit(2)	  	
	  
	for opt, arg in opts:
	  if opt == '-h':
		 print 'tilep.py -b <bbox> -i <inputXml> -o <output> -l <layerGrid>'
		 sys.exit()
	  elif opt in ("-b", "--bbox"):
		 b = arg	  
	  elif opt in ("-i", "--inputXml"):
		 i = arg      
	  elif opt in ("-o", "--output"):
		 o = arg   			
	  elif opt in ("-l", "--layerGrid"):
		 l = arg   				 				
	
	box = []
	for s in b.split(",") :
		box.append(float(s))
		
	geo_extent = Box2d(box[0],box[1],box[2],box[3])		
	
	geo_proj = Projection('+init=epsg:4326')
	merc_proj = Projection('+init=epsg:3857')	

	transform = ProjTransform(geo_proj,merc_proj)
	merc_extent = transform.forward(geo_extent)	
	
	mp = mapnik.Map(256,256)	
	
	#sys.exit(i)		
	
	if i.find('http') >= 0:				
		xmlurl=urllib2.urlopen(i)
		xmlstr=xmlurl.read()		
		#xmlstr = '<Map srs="+proj=merc +a=6378137 +b=6378137 +lat_ts=0.0 +lon_0=0.0 +x_0=0.0 +y_0=0 +k=1.0 +units=m +nadgrids=@null +wktext +no_defs"><Style name="Style1"><Rule><PolygonSymbolizer fill="#ffbf00" fill-opacity="0.5"/><LineSymbolizer stroke="#ff7b00" stroke-width="1" stroke-opacity="1" stroke-linejoin="bevel"/></Rule></Style><Layer name="Layer1" srs="+proj=longlat +ellps=WGS84 +datum=WGS84 +no_defs "><StyleName>Style1</StyleName><Datasource><Parameter name="type">postgis</Parameter><Parameter name="host">localhost</Parameter><Parameter name="dbname">webgisPEP</Parameter><Parameter name="username">iyo</Parameter><Parameter name="password">SAlamaH</Parameter><Parameter name="table">(SELECT * FROM sxz51_iyo_data_6) as layer1</Parameter><Parameter name="geometry_field">the_geom</Parameter><Parameter name="srid">EPSG:4326</Parameter><Parameter name="fields">gid,nama_kebun,sk</Parameter></Datasource></Layer></Map>'
		mapnik.load_map_from_string(mp,xmlstr)		
		xmldoc = minidom.parseString(xmlstr)
	else:
		mapnik.load_map(mp, i)	
		xmldoc = minidom.parse(i)		
		
		
	mp.zoom_to_box(merc_extent)
	
	printed = 0	
	if o == 'png':			
		im = Image(mp.width,mp.height)		
		mapnik.render(mp,im)
		fd, path = tempfile.mkstemp()		
					    
		os.write(fd,im.tostring('png'))
		os.fsync(fd)		
		print path								
	elif o == 'json':	
		printed = 1				
	else:			
		image = o+".png"
		if not os.path.exists(os.path.dirname(image)):
			try:
				os.makedirs(os.path.dirname(image))
			except:
				pass					
		mapnik.render_to_file(mp, image)
		if l == '-1':
			print image
	
	#print ((time.time())-(t0))*1000
	#sys.exit()
	
	lgrids = []	
	if l != '-1':
		for s in l.split(",") :
			lgrids.append(s)
	else:
		sys.exit()
				
	itemlist = xmldoc.getElementsByTagName('Layer') 
	
	fields = []
	resolution = 4 #Pixel resolution of output.   
	printed = 0
	for lysl in lgrids :
		lys = lysl.split(":")
		ly = int(lys[0])
		lz = ''
		if (len(lys) == 2):	
			lz = lys[1]
		dat = itemlist[ly].getElementsByTagName('Datasource')[0] 
		par = dat.getElementsByTagName('Parameter')	
		for s in par :
			dv = s.attributes['name'].value
			dck = False
			
			if (dv[6:] == '' or lz == ''):
				dck = True
			elif (dv[6:].isdigit() and lz.isdigit()):
				dck = (int(lz) >= int(dv[6:]))
						
			
			if dv[:6] == 'fields' and dck and fields == [] :			
				text = s.childNodes[0].nodeValue.encode("utf-8")			
				#print "fields "+text
				fields = text.split(",")
			#elif dv[:6] == 'fields':			
			#	ly = ly + 1
				
			if dv == 'resolution':			
				res = s.childNodes[0].nodeValue.encode("utf-8")			
				#print "resolution "+res
				resolution = int(res)	
						
		layer_index = ly #First layer on the map - index in m.layers
		key = "__id__"  #Field used for the key in mapnik (should probably be unique)		
		
		enfix = ""
		if ly > 0:
			enfix = "_"+str(ly)
			
		d = mapnik.render_grid(mp, layer_index, key, resolution, fields) #returns a dictionary		
		d = "grid("+json.dumps(d)+")"
		
		if o == 'json' and printed == 0:				
			print d
			printed = 1
		elif o == 'png':
			printed = 1				
		else:				
			print d
			f = open(o+enfix+".json",'wb')
			f.write(d)	
			f.close()

if __name__ == "__main__":
   main(sys.argv[1:])
