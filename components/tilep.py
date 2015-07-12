#!/usr/bin/env python

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
	
	lgrids = []	
	if l != '-1':
		for s in l.split(",") :
			lgrids.append(int(s))
	else:
		sys.exit()
				
	itemlist = xmldoc.getElementsByTagName('Layer') 
	
	fields = []
	resolution = 4 #Pixel resolution of output.   
	printed = 0
	for ly in lgrids :	
		dat = itemlist[ly].getElementsByTagName('Datasource')[0] 
		par = dat.getElementsByTagName('Parameter')	
		for s in par :
			if s.attributes['name'].value == 'fields':			
				text = s.childNodes[0].nodeValue.encode("utf-8")			
				#print "fields "+text
				fields = text.split(",")				
			if s.attributes['name'].value == 'resolution':			
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
