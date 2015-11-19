ol.utfGrid = function(opt_options) {  
	var options = this.isObj(opt_options) ? opt_options : {};
	this.name = this.isObj(options.name) ? options.name : null;
	this.pk = this.isObj(options.pk) ? options.pk : null;
	this.render = this.isObj(options.render) ? options.render : false;
	this.base = this.isObj(options.base) ? options.base : 64;
	this.renderRule = this.isObj(options.renderRule) ? options.renderRule : [];
	this.data = {};	
	this.url = this.isObj(options.url) ? options.url : null;
	this.urls = this.isObj(options.urls) ? options.urls : [];
	this.map = this.isObj(options.map) ? options.map : null;
	this.request = [];	
	this.startEvent = null;
	this.finishEvent = null;
	
	if (this.map != null)
	{
		this.map.utfGrids = (this.isObj(this.map.utfGrids)?this.map.utfGrids:[]);
		this.map.utfGrids.push(this);
		if (this.isObj(this.map.utfGridMoveEndKey)) {		
			this.map.unByKey(this.map.utfGridMoveEndKey);
		}	
		this.map.utfGridMoveEndKey = this.map.on('moveend', this.fetch);
		
		if (this.isObj(this.map.utfGridPointerMoveKey)) {		
			this.map.unByKey(this.map.utfGridPointerMoveKey);
		}	
		
		this.map.utfGridPointerMoveKey = this.map.on('pointermove', this.onMove);		
	}	
}; 


ol.utfGrid.prototype.addTo = function(map)
{ 
	if (this.isObj(map))
	{
		this.map = map;
		map.utfGrids = (this.isObj(map.utfGrids)?map.utfGrids:[]);
		map.utfGrids.push(this);
		if (this.isObj(map.utfGridMoveEndKey))
		{		
			map.unByKey(map.utfGridMoveEndKey);
		}
		map.utfGridMoveEndKey = map.on('moveend', this.fetch);
		
		if (this.isObj(map.utfGridPointerMoveKey)) {		
			map.unByKey(map.utfGridPointerMoveKey);
		}	
		
		map.utfGridPointerMoveKey = map.on('pointermove', this.onMove);		
		return true;
	}	
	else
	{
		return false;
	}
};


ol.utfGrid.prototype.isObj = function(obj)
{
	return (typeof obj != "undefined"?true:false);
};	


ol.utfGrid.prototype.getUrl = function(url,success,err,params)
{
	params = (typeof params != "undefined"?params:[]);
	
	var xmlhttp;	
	if (window.XMLHttpRequest)
	{// code for IE7+, Firefox, Chrome, Opera, Safari
		xmlhttp=new XMLHttpRequest();
	}
	else
	{// code for IE6, IE5
		xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	}
	
	this.request.push(xmlhttp);	
	
	xmlhttp.onreadystatechange=function()
	{
		if (xmlhttp.readyState==4 && xmlhttp.status==200)
		{
			if (typeof success != "undefined")
			{				
				success(xmlhttp.responseText,params,xmlhttp);
			}				
		}
		else
		{
			if (typeof err != "undefined")
			{
				err(xmlhttp.responseText,params,xmlhttp);
			}	
		}		
	};
	
	xmlhttp.open("GET",url,true);
	xmlhttp.send();	
	
};

/*
ol.utfGrid.prototype.grid = function(grid)
{	
	return grid;
};
*/ 

ol.utfGrid.prototype.fixLonlat = function(lonlat)
{ 	
	lonlat[0] = (lonlat[0]%180 != lonlat[0]%360?(lonlat[0]>180?(-1)*(180-lonlat[0]%180):180+lonlat[0]%180):lonlat[0]%180);
	lonlat[1] = (lonlat[1]%90 != lonlat[1]%180?90+(lonlat[1]>90?(-1)*(90-lonlat[1]%90):90+lonlat[1]%90):lonlat[1]%90);
	
	return lonlat;
};	


ol.utfGrid.prototype.fetch = function(evt,forceRefresh)
{ 	
	var map = evt.map;					
	
	var zoom0 = map.getView().getZoom(); 
	var center0 = map.getView().getCenter();
	
	setTimeout(function () {
		var zoom = map.getView().getZoom(); 
		var center = map.getView().getCenter();
		
		if (zoom == zoom0 && center == center0)
		{
			var extent = map.getView().calculateExtent(map.getSize());	
			
			//var ddbox = ol.proj.transform(extent,
			//  'EPSG:3857', 'EPSG:4326');
			  
			var ddbox0 = ol.proj.transform([extent[0],extent[1]],
			  'EPSG:3857', 'EPSG:4326');  
			  
			var ddbox1 = ol.proj.transform([extent[2],extent[3]],
			  'EPSG:3857', 'EPSG:4326');  
			  
			var ddctr = ol.proj.transform(center,
			  'EPSG:3857', 'EPSG:4326');  
			
			ddbox = [ddbox0[0],ddbox0[1],ddbox1[0],ddbox1[1]];  			
			
			var utfGrids = map.utfGrids;
			var tile = [];
			var tiles = [];
			
			var start = true;
				
			for (var g=0;g<utfGrids.length;g++)
			{				
				var ug = utfGrids[g];		
				
				var fetch = false;
				map.getLayers().forEach(function (lyr) {						
					if (lyr.get('name') == ug.name) {
						fetch = lyr.getVisible();												
					} 						
				});		
				
				
				
				if (fetch)
				{					
					if (ug.request.length > 0 && start)
					{
						for(key in ug.request)
						{
							if (ug.isObj(ug.request[key]))
							{
								ug.request[key].abort();
								delete ug.request[key];
							}
						}
					}			
					start = false;
					
					
					min = ug.fixLonlat([ddbox[0],ddbox[1]]);
					max = ug.fixLonlat([ddbox[2],ddbox[3]]);
					ctr = ug.fixLonlat(ddctr);				
					
					//console.log(min,ctr,max);
					
					if (ctr[0] < min[0] || ctr[0] > max[0])
					{
						min = [-180,min[1]];
						max = [180,max[1]];
					}
					
					var g0 = ug.lonlat2tile([min[0],min[1]],zoom);
					var g1 = ug.lonlat2tile([(max[0]<min[0]?180:max[0]),(max[1]<min[1]?-85.0551:max[1])],zoom);
					var g2 = ug.lonlat2tile([(max[0]<min[0]?-180:max[0]),(max[1]<min[1]?85.0551:max[1])],zoom);
					var g3 = ug.lonlat2tile([max[0],max[1]],zoom);				
					
					if (!ug.isObj(ug.data[zoom]))
					{
						ug.data[zoom] = [];
					}				
					
					//var grid = ug.grid;
					var tiles = [];
					
					for(var x = g0[0];x<=g1[0];x++)
					{									
						if (!ug.isObj(ug.data[zoom][x]))
						{
							ug.data[zoom][x] = [];
						}			
						for(var y = g0[1];y>=Math.max(0,g1[1]);y--)
						{											
							if (!ug.isObj(ug.data[zoom][x][y]) || ug.isObj(forceRefresh))
							{															
								ug.data[zoom][x][y] = null;
							}
							
							if (ug.data[zoom][x][y] == null)
							{	
								var tile = [zoom,x,y];
								if (tiles.indexOf(tile) < 0)
								{
									tiles.push(tile);						
								}
							}
						}	
					}		
					for(var x = g2[0];x<=g3[0];x++)
					{						
						if (!ug.isObj(ug.data[zoom][x]))
						{
							ug.data[zoom][x] = [];
						}			
						for(var y = Math.max(0,g2[1]);y>=Math.max(0,g3[1]);y--)
						{											
							if (!ug.isObj(ug.data[zoom][x][y]) || ug.isObj(forceRefresh))
							{															
								ug.data[zoom][x][y] = null;
							}
							
							if (ug.data[zoom][x][y] == null)
							{	
								var tile = [zoom,x,y];
								if (tiles.indexOf(tile) < 0)
								{
									tiles.push(tile);											
								}
							}
						}	
					}
										
					//console.log(tiles,min,max,ctr,g0,g1,g2,g3);		
					if (ug.startEvent != null && tiles.length > 0)
					{
						ug.startEvent(ug);						
					}
										
					
					for(var t = 0;t<tiles.length;t++)
					{
						var tile = tiles[t];												
						
						//console.log(ug.urls);
						if (ug.urls.length > 0)
						{
							var nurl = Math.floor((Math.random() * ug.urls.length) + 1);
							//console.log(nurl);
							ug.url = ug.urls[nurl-1];	
							//ug.url = ug.urls[0];	
						}
						
						//console.log(ug.url); 
						
						if (ug.isObj(forceRefresh))
						{
							var d = new Date();
							var n = d.getTime();														
							ug.url = ug.url.replace(/(\?|&)r\=(\d+)/g,"");
							ug.url = ug.url.replace(/\.json\??(.*)?/g,".json?r="+n+"&$1");							
						}												
									
						ug.getUrl(ug.url.replace("{z}",tile[0]).replace("{x}",tile[1]).replace("{y}",tile[2]),
							function(jsonp,params,request){					
								var dug = params[0];
								var tile = params[1];
								if (jsonp.substr(0,5) == 'grid(')
								{
									dug.data[tile[0]][tile[1]][tile[2]] = JSON.parse(jsonp.substr(5,jsonp.length-6));
								}
								
								if (dug.isObj(dug.data[tile[0]][tile[1]][tile[2]]))
								{
									if (dug.data[tile[0]][tile[1]][tile[2]] != null)
									{
										if (dug.data[tile[0]][tile[1]][tile[2]]["keys"].length > 1 && dug.render)
										{						
											dug.renderPoint(tile);						
										}
									}
								}
								
								if (dug.finishEvent != null && (tile == tiles[tiles.length-1] || tiles.length == 0 ) )
								{									
									dug.finishEvent(ug);																		
								}
							},
							function(jsonp,params,request){												
								var dug = params[0];
								var tile = params[1];								
								setTimeout(function () {								
									if (dug.finishEvent != null && (tile == tiles[tiles.length-1] || tiles.length == 0) && request.status == 0)
									{
										dug.finishEvent(ug);
									}
								},5000);
							},
							[ug,tile]
						);					
					}
				}			
			}
		}		
	}, 2000);
	
	
												
	
	
};

ol.utfGrid.prototype.lon2xtile = function(lon,zoom)
{ 
	return (lon+180)/360*Math.pow(2,zoom);
};

ol.utfGrid.prototype.lat2ytile = function(lat,zoom)
{ 
	return (1-Math.log(Math.tan(lat*Math.PI/180) + 1/Math.cos(lat*Math.PI/180))/Math.PI)/2 *Math.pow(2,zoom);
};

ol.utfGrid.prototype.tile2long = function(x,z) {
  return (x/Math.pow(2,z)*360-180);
};

ol.utfGrid.prototype.tile2lat = function(y,z) {
  var n=Math.PI-2*Math.PI*y/Math.pow(2,z);
  return (180/Math.PI*Math.atan(0.5*(Math.exp(n)-Math.exp(-n))));
};

ol.utfGrid.prototype.pixBase = function(n,base)
{ 
	base = this.base;
	return Math.floor((n-Math.floor(n))/1*base);
};

ol.utfGrid.prototype.lonlat2tile = function(lonlat,zoom)
{		
	var x = this.lon2xtile(lonlat[0],zoom);
	var xp = this.pixBase(x);	
	var y = this.lat2ytile(lonlat[1],zoom);
	var yp = this.pixBase(y);	
	return [Math.floor(x),Math.floor(y),xp,yp]; 
};


ol.utfGrid.prototype.getData = function(lonlat,ug) {	
	ug = (typeof ug != "undefined"?ug:this);
	var zoom = this.map.getView().getZoom();
	
	var g = ug.lonlat2tile(lonlat,zoom);
	try {				
		var json = ug.data[zoom][g[0]][g[1]];
	}
	catch (Exception)
	{
		var json = null;
	}
	
	var d = null;
			
	if (json != null)
	{			
		try {				
			code = json.grid[g[3]].substr(g[2],1).charCodeAt(0);
			if (code >= 93) { code --;}
			if (code >= 35) { code --;}
			code -= 32;
			
			var d = json.data[json.keys[code]];		
		}
		catch (Exception)
		{
			var d = null;
		}
		
	}

	return d;	
};

ol.utfGrid.prototype.renderPoint = function(tile) {
	var ug = this;
	var json = ug.data[tile[0]][tile[1]][tile[2]];
	json = ug.isObj(json)?json:null;
	var keys = json["keys"];
	var data = json["data"];	
	var zoom = tile[0];
	var g = [tile[1],tile[2]];
	
	var nocode = " ";
	nocode = nocode.charCodeAt(0);
	if (nocode >= 93) { nocode --;}
	if (nocode >= 35) { nocode --;}
	nocode -= 32;
			
	for (var yp=0 ;yp < this.base;yp ++)
	{
		var yval = json.grid[yp];
		for (var xp=0 ;xp < this.base;xp ++)
		{
			var xval = yval.substr(xp,1);
			var code = xval.charCodeAt(0);						
			if (code >= 93) { code --;}
			if (code >= 35) { code --;}
			code -= 32;
			
			if (code != nocode)
			{
				var lon0 = this.tile2long(g[0],zoom);
				var lat0 = this.tile2lat(g[1],zoom);							
											
				var lon1 = this.tile2long(g[0]+1,zoom);
				var lat1 = this.tile2lat(g[1]+1,zoom);
				
				var lon = lon0+((xp+1)/this.base*(lon1-lon0));
				var lat = lat0+((yp+1)/this.base*(lat1-lat0));
			//	console.log(tile,lon,lat,[code,nocode],[xval," "],[xp,yp],[lon0,lon1]);														
				
				//console.log(data);
				var Data = data[keys[code]];
				if (ug.isObj(Data))
				{
																			
					//iconFeature.setStyle(iconStyle);
										
					var vectorLayer = null;
					ug.map.getLayers().forEach(function (lyr) {						
						if (lyr.get('name') == ug.name) {
							vectorLayer = lyr;			
						} 						
					});					
					Data.zoom = zoom;
					Data.resolution = ug.map.getView().getResolution();
					Data.geometry = new ol.geom.Point(ol.proj.transform([lon,lat], 'EPSG:4326', 'EPSG:3857'));
					var iconFeature = new ol.Feature(Data);							
					iconFeature.setId(Data.gid);						
					
					if (vectorLayer != null)					
					{
						var oldf = vectorLayer.getSource().getFeatureById(Data.gid);											
						if (oldf != null)
						{
							//console.log(oldf.get('zoom'),zoom);
							var maxz = Math.min(oldf.get('maxzoom'),zoom);
							oldf.set('maxzoom',maxz);
							if (oldf.get('zoom') < zoom)
							{								
								oldf.set('zoom',Data.zoom);
								oldf.set('resolution',Data.resolution);
								oldf.setGeometry(Data.geometry);
								//iconFeature.set('maxzoom',maxz);
								//vectorLayer.getSource().removeFeature(oldf);
								//vectorLayer.getSource().addFeature(iconFeature);																
								//ug.map.render();
							}
						}
						else
						{							
							iconFeature.set('maxzoom',zoom);
							vectorLayer.getSource().addFeature(iconFeature);														
						}						
						
																		
						
					}
					
				}
			}
		}
	}
	ug.map.render();
	
	


};

ol.utfGrid.prototype.onMove = function(evt) {
	var lonlat = ol.proj.transform(evt.coordinate,
	  'EPSG:3857', 'EPSG:4326');				
	
	var data = [];
	var utfGrids = evt.map.utfGrids;
	
	for (var g=0;g<utfGrids.length;g++)
	{
		var ug = utfGrids[g];
		lonlat = ug.fixLonlat(lonlat);
		d = ug.getData(lonlat);
		data.push(d);					
	}			
	//console.log(lonlat,data[0]);	
};	
/**/ 
