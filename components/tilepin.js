#!/usr/bin/env node

//console.log(process.argv);

var usage = 'usage: node tilepin.js <xml Dir> <python File> <output tile Dir> <ipaddress> <ports> <maxzoomcache> <sslkey file (optional)> <sslcert file (optional)>';
usage += '\ndemo: node tilepin.js /yii2/advanced/vendor/amilna/yii2-iyo/xml /yii2/advanced/vendor/amilna/yii2-iyo/components ';
usage += '/yii2/advanced/backend/web/tile 127.0.0.1 [1401,1402,1403,1404] -1 /ssl.key /ssl.cert';

if (process.argv.length < 7)
{
	console.log(usage);
	process.exit(1);
}

var http = require('http');
var https = require('https');
var fs = require('fs');
var cache = [];

var xmlDir = process.argv[2]; //"/home/iyo/www/yii2/advanced/vendor/amilna/yii2-iyo/xml";
var pyFile = process.argv[3]; //"/home/iyo/www/yii2/advanced/vendor/amilna/yii2-iyo/components";
var tileDir = process.argv[4]; //"/home/iyo/www/yii2/advanced/backend/web/tile";

var ipaddr = process.argv[5]; //'127.0.0.1';
var ports = eval(process.argv[6]); //[1401,1402];

var xmlUrl = process.argv[7]; //'http://127.0.0.1/iyo/layer/xml';

var maxZoomCache = -1;
if (typeof process.argv[8] != "undefined")
{
	maxZoomCache = parseInt(process.argv[8]);
}	

if (typeof process.argv[9] != "undefined" && typeof process.argv[10] != "undefined")
{
	var usessl = true;
	var sslkey = process.argv[9]; //'/home/iyo/ssl.key';
	var sslcert = process.argv[10]; //'/home/iyo/ssl.cert';
}
else
{
	var usessl = false;
}



if (usessl)
{
	var options = {
	  key: fs.readFileSync(sslkey),
	  cert: fs.readFileSync(sslcert)
	};
}

function tile2long(x,z) {
	return (x/Math.pow(2,z)*360-180);
}

function tile2lat(y,z) {
	var n=Math.PI-2*Math.PI*y/Math.pow(2,z);
	return (180/Math.PI*Math.atan(0.5*(Math.exp(n)-Math.exp(-n))));
}

function getLonLat(x,y,z)
{	
	var lon = tile2long(x,z);
	var lat = tile2lat(y,z);
	return [lon,lat];
}

function putCache(data,xml,ext,z,x,y)
{	
	if (typeof cache[xml] == "undefined")
	{
		cache[xml] = [];
	}
	
	if (typeof cache[xml][ext] == "undefined")
	{
		cache[xml][ext] = [];
	}
		
	if (typeof cache[xml][ext][z] == "undefined")
	{
		cache[xml][ext][z] = [];
	}	
	
	if (typeof cache[xml][ext][z][x] == "undefined")
	{
		cache[xml][ext][z][x] = [];												
	}										
										
	cache[xml][ext][z][x][y] = data;								
	return cache;
}

function isCache(xml,ext,tile)
{
	var z = tile[0];
	var x = tile[1];
	var y = tile[2];
	var res = false;
	if (typeof cache[xml] != "undefined")
	{
		if (typeof cache[xml][ext] != "undefined")
		{
			if (typeof cache[xml][ext][z] != "undefined")
			{
				if (typeof cache[xml][ext][z][x] != "undefined")
				{
					if (typeof cache[xml][ext][z][x][y] != "undefined")
					{
						res =  cache[xml][ext][z][x][y];	
					}	
				}	
			}
		}
	}
	return res;
}

function getTile(tile,xml,o,ext,bbox,lgrid,callback)
{
	if (o != ext)
	{
	
		fs.readFile(o+"."+ext, function (err, data) {				  			
			if (err) {
				mkTile(tile,xml,o,ext,bbox,lgrid,callback);
			}
			else					
			{																			
				return callback(data);				
			}			
		});			
	
	
	}
	else
	{	
		mkTile(tile,xml,o,ext,bbox,lgrid,callback);
	}	
}	

function mkTile(tile,xml,o,ext,bbox,lgrid,callback)
{
	var z = tile[0];
	var x = tile[1];
	var y = tile[2];
	
	if (isCache(xml,ext,tile))
	{
		return callback(cache[xml][ext][z][x][y]);	
	}
	
	var sys = require('sys')
	var exec = require('child_process').exec;
	var child;
	
	m = xml.match(/iyo(\d+)_([a-zA-Z0-9_]+)/);		
	if (m != null)
	{		
		xmlp = 	xmlUrl+"/?id="+m[1]+"&name="+m[2];
	}
	else
	{
		xmlp = 	xmlDir+"/"+xml+".xml";
		
	}		
	
	child = exec("python '"+pyFile+"' -i '"+xmlp+"' -o "+o+" -b "+bbox+lgrid, function (error, stdout, stderr) {								
						
		if (error !== null) {			
			return callback(false,error);
		}
		else
		{								
			if (ext == "png")
			{					  																		
								
				fs.readFile(stdout.trim(), function (err, data) {				  
					//if (err) throw err;
					if (err) {
						return callback(false,err);
					}
					else					
					{															
						putCache(data,xml,ext,z,x,y);
						callback(data);
						
						if (o == ext)
						{
							setTimeout(function () {
								fs.unlink(stdout.trim(), function (err) {
									//if (err) throw err;						
									if (err) {
										console.log(err);	
									}
								});
							}, 1000);
						}
						
						return true;
					}
					
				});										
			}
			else if	(ext == "json")
			{
				putCache(stdout,xml,ext,z,x,y);
				return callback(stdout);										
			}
			else {
				return callback(false,"type unrecognized");
			}
		}					
	});
	
}

var mkServ = function (req, res) {	
	
	m = req.url.match(/^\/([a-zA-Z0-9_]+)\/(\d+)\/(\d+)\/(\d+)\.(png|json)\?r\=(\d+)/);	
	
	if (m == null)
	{
		m = req.url.match(/^\/([a-zA-Z0-9_]+)\/(\d+)\/(\d+)\/(\d+)\.(png|json)/);	
				
		if (m == null)		
		{
			m = req.url.match(/^\/([a-zA-Z0-9_]+)\?r\=(\d+)/);	
			if (m != null)
			{
				var xml = m[1];				
				delete cache[xml];
				m = null;
			}
		}	
	}
	else
	{
		var xml = m[1];
		var z = parseInt(m[2]);
		var x = parseInt(m[3]);
		var y = parseInt(m[4]);
		var ext = m[5];
		
		if (isCache(xml,'json',[z,x,y]))
		{
			delete cache[xml]['json'][z][x][y];
		}	
		if (isCache(xml,'png',[z,x,y]))
		{	
			delete cache[xml]['png'][z][x][y];
		}		
	}
		
	if (m != null)
	{
		var xml = m[1];
		var z = parseInt(m[2]);
		var x = parseInt(m[3]);
		var y = parseInt(m[4]);
		var ext = m[5];
		
		var o = tileDir+"/"+xml+"/"+z+"/"+x+"/"+y;
		
		var tw = 256;
		var th = 256;
		var tsz = 256;

		var xtile_s = (x * tsz - tw/2) / tsz;
		var ytile_s = (y * tsz - th/2) / tsz;
		var xtile_e = (x * tsz + tw/2) / tsz;
		var ytile_e = (y * tsz + th/2) / tsz;
		
		var s0 = getLonLat(xtile_s, ytile_s, z);
		var e0 = getLonLat(xtile_e, ytile_e, z);

		var s = getLonLat(x, y, z);
		var e = getLonLat(x+1, y+1, z);				
		
		//console.log(s,e);
		//console.log("tes/"+z+"/"+x+"/"+y,[z,x+1, y+1]);		
		//console.log("mod",s[0]%360,s[0],Math.floor(s[0]));
		
		if (s[0] > 360)
		{
			s[0] = (s[0]%360)+(s[0]-Math.floor(s[0]));
			e[0] = (e[0]%360)+(e[0]-Math.floor(e[0]));		
		}

		s[0] = s[0] >= 180? s[0]-360:s[0];
		e[0] = e[0] > 180? e[0]-360:e[0];
		
		//console.log(s,e);
		 
		var bbox = s[0]+","+e[1]+","+e[0]+","+s[1];
		var lgrid = (ext == "json"?" -l 0":"");										
		
		var ohost = req.headers.host;
		var port = ohost.substr(ohost.indexOf(":")+1);
		var pi = 0;
		for (var p=0;p<ports.length;p++)
		{
			if (ports[p]+"" == port+"")	
			{
				pi = p;	
			}
		}
		pi = (pi+1 >= ports.length-1?false:pi+1);
		var nhost = pi?ohost.replace(":"+port,":"+ports[pi]):false;
		
		if (z > maxZoomCache)
		{		
			o = ext;		
		}
		//console.log(z,maxZoomCache,o);
		
		getTile([z,x,y],xml,o,ext,bbox,lgrid,function(data,error){
			
			if (data)
			{
				res.setHeader("Access-Control-Allow-Origin", "*");
				
				if (ext == "json")
				{
					ct = {
						'Content-Type': 'text/plain'
					};				
					//res.writeHead(200, ct); 
					res.statusCode = 200;
					res.setHeader("Access-Control-Allow-Origin", "*");
					res.setHeader("Content-Type", "text/plain");
					res.end(data);
				}
				else if (ext == "png")
				{
					ct = {
						'Content-Type': 'image/png',					
					};	
					//res.writeHead(200, ct);				  
					res.statusCode = 200;
					res.setHeader("Content-Type", "image/png");
					res.end(data,'binary');
				}
				else
				{
					var ct = {'Content-Type': 'text/plain'};				
					//res.writeHead(404, ct);  
					res.statusCode = 200;
					res.setHeader("Content-Type", "text/plain");
					res.end();	
				}
							
			}
			else
			{
				if (nhost)
				{
					res.writeHead(301,
					  {Location: (usessl?'https://':'http://')+nhost+req.url}
					);
					res.end();	
				}
				else
				{
					var ct = {'Content-Type': 'text/plain'};				
					//res.writeHead(404, ct);  
					res.statusCode = 404;
					res.setHeader("Content-Type", "text/plain");
					res.end();
				}
				console.log(error);
			}
		
		});								
	}
	else
	{		
		var ct = {'Content-Type': 'text/plain'};				
		res.statusCode = 200;
		res.setHeader("Content-Type", "text/plain");
		res.end();
	}			
  
}

for (var i=0;i<ports.length;i++)
{
	var p = ports[i];
	
	if (usessl)
	{
		var serv = https.createServer(options, mkServ).listen(p, ipaddr);
	}
	else
	{	
		var serv = http.createServer(mkServ).listen(p, ipaddr);
	}
	console.log('Server running at '+(usessl?'https':'http')+'://'+ipaddr+':'+p+'/');
}
