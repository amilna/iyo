#!/usr/bin/env node

//console.log(process.argv);

var usage = 'usage: node xmlin.js <allowedips> <ipaddress> <ports> <execFile> <dbstr> <geomuserstr> <sslkey file (optional)> <sslcert file (optional)>';
usage += '\ndemo: node xmlin.js [127.0.0.1,::1] 127.0.0.1 [1403] /amilna/yii2-iyo/components/exec pgsql:host=localhost;dbname=dbname,prefix_,username,password the_geom,1';

if (process.argv.length < 7)
{
	console.log(usage);
	process.exit(1);
}

var http = require('http');
var https = require('https');
var fs = require('fs');
var cache = [];

var allowedips = process.argv[2].split(","); //[127.0.0.1,::1]';
var ipaddr = process.argv[3]; //'127.0.0.1';
var ports = eval(process.argv[4]); //[1401,1402];
var execFile = process.argv[5]; //'/amilna/yii2-iyo/components/exec';
var dbstr = process.argv[6]; // 'pgsql:host=localhost;dbname=dbname,prefix_,username,password';
var geomuserstr = process.argv[7]; //'the_geom,1';

var dblst = dbstr.split(',');
var actionstr = "-action='getXml' -dsn='"+dblst[0]+"' -tablePrefix='"+dblst[1]+"' -username='"+dblst[2]+"' -password='"+dblst[3]+"'";
var geomuserlst = geomuserstr.split(',');
var geomuser = geomuserlst[0]+":"+geomuserlst[1];

if (typeof process.argv[8] != "undefined" && typeof process.argv[9] != "undefined")
{
	var usessl = true;
	var sslkey = process.argv[8]; //'/home/iyo/ssl.key';
	var sslcert = process.argv[9]; //'/home/iyo/ssl.cert';
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

function getXml(lid,lname,callback)
{		
	var sys = require('sys')
	var exec = require('child_process').exec;
	var child;
	
	child = exec(execFile+" "+actionstr+" -param='"+lid+":"+lname+":"+geomuser+"'", function (error, stdout, stderr) {								
						
		if (error !== null) {			
			return callback(false,error);
		}
		else
		{											
			return callback(stdout);			
		}					
	});
	
}

var mkServ = function (req, res) {
	
	var ip = req.headers['x-forwarded-for'] || 
     req.connection.remoteAddress || 
     req.socket.remoteAddress ||
     req.connection.socket.remoteAddress;	
     	
	m = req.url.match(/^\/\?id=(\d+)&name=([a-zA-Z0-9_]+)/);	
	
	var isallowed = false;
	for (var s=0;s<allowedips.length;s++)
	{
		if (allowedips[s] == ip)
		{
			isallowed = (!isallowed?true:isallowed);
		}
	}
	
	if (!isallowed)
	{
		m = null;
	}	
		
	if (m != null)
	{
		var lid = m[1];
		var lname = m[2];
		
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
		
		getXml(lid,lname,function(data,error){
			
			if (data)
			{				
				if (data != "")
				{					
					res.statusCode = 200;
					res.setHeader("Content-Type", "application/xml");
					res.end(data);
				}
				else
				{					
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
