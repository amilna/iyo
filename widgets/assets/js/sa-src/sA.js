String.prototype.capitalize = function() {
    return this.replace(/(?:^|\s)\S/g, function(a) { return a.toUpperCase(); });
};

sA = function(obj_options) {
	this.version = "0.0.0";
	var options = (this.isObj(obj_options)?obj_options:{});
	this.baseUrl = (this.isObj(options.baseUrl)?options.baseUrl:"");
	this.plugins = (this.isObj(options.plugins)?options.plugins:[]);
	this.log = {};
	if (this.isObj(options.callBack))
	{
		this.init(callBack);
	}
};

sA.prototype.init = function(func_callBack) {
	/*
	var d = new Date();
	var n = d.getTime();
	this.getScript(this.url+"sA.Db.js?_"+n,function(){	
	*/		
	
	var sa = this;
		
	var saInit = {};
	var i = 0;
	
	saInit.load = function() {
		var p = sa.plugins[i];
		sa.log["plugin_"+p] = false;
		i += 1;		
		if (sa.inArray(p,sa.plugins) < sa.plugins.length-1)
		{			
			if (sa.isObj(eval("sA."+p.replace('.min',''))))
			{
				saInit.load();
			}
			else
			{					
				sa.getScript(sa.baseUrl+"sA."+p+".js",function(){
					saInit.load();
				});
			}
		}
		else
		{			
			if (sa.isObj(eval("sA."+p.replace('.min',''))))
			{
				if (sa.isObj(func_callBack))
				{
					func_callBack();
				}
			}
			else
			{					
				sa.getScript(sa.baseUrl+"sA."+p+".js",function(){					
					if (sa.isObj(func_callBack))
					{
						func_callBack();
					}
				});
			}
		}		
	};
	
	if (sa.plugins.length > 0)
	{
		saInit.load();
	}
	else
	{
		if (sa.isObj(func_callBack))
		{
			func_callBack();
		}
	}
				
};

sA.prototype.isObj = function(obj) {
	if (typeof obj != "undefined") {
		return true;
	}
	else
	{
		return false;	
	}	
};

sA.prototype.toMoney = function(num,str_moneySym) {
	var decimalSeparator = Number("1.2").toLocaleString().substr(1,1);
	var thousandSeparator = decimalSeparator == '.'?',':'.';
	var formated = parseFloat(num).toFixed(2).replace(/(\d)(?=(\d{3})+\.)/g, '$1'+thousandSeparator).replace(/(\.|,)(\d{2})$/g, decimalSeparator+'$2');	
	return (this.isObj(str_moneySym)?str_moneySym+" ":"") + formated;		
};
	
sA.prototype.inArray = function(obj,array) {
	var pos = -1;
	var n = 0;
	for(i in array)
	{						
		var d = array[i];			
		if (d == obj)
		{
			pos = n;
		}		
		n++;		
	}
	return pos;
};

sA.prototype.xhr = function(str_url,func_ifSuccess,func_ifError,arrObj_params,type,data)
{
	var sa_ = this;
	arrObj_params = (this.isObj(arrObj_params)?arrObj_params:[]);
	
	var saXhr;
	if (window.XMLHttpRequest)
	{// code for IE7+, Firefox, Chrome, Opera, Safari
		saXhr=new XMLHttpRequest();
	}
	else
	{// code for IE6, IE5
		saXhr=new ActiveXObject("Microsoft.XMLHTTP");
	}
	
	saXhr.onreadystatechange=function()
	{		
		if (saXhr.readyState==4 && sa_.inArray(saXhr.status,[200,201]) >= 0 )
		//if (saXhr.readyState==4)
		{
			if (sa_.isObj(func_ifSuccess))
			{				
				func_ifSuccess(saXhr.responseText,arrObj_params,saXhr);
			}				
		}
		else
		{
			if (sa_.isObj(func_ifError))
			{
				func_ifError(saXhr.responseText,arrObj_params,saXhr);
			}	
		}
	};
	
	
	if (!sa_.isObj(type))
	{
		type = "GET";	
	}		
	
	saXhr.open(type,str_url,true);
		
	if (typeof data == "object" || typeof data == "array" )
	{		
		saXhr.setRequestHeader("Content-type","application/x-www-form-urlencoded");
		params = "";
		for (key in data)
		{
			if (data[key]+"" != "null")
			{
				params += (params==""?"":"&")+key+"="+data[key];	
			}
		}		
	}
	else if (typeof data == "objects")
	{
		params = JSON.stringify(data);	
	}
	else
	{
		params = data;	
	}
	
	
	
	saXhr.send(params);
};

sA.prototype.getScript = function (str_url, func_callBack)
{        
    var sa_ = this;
    this.xhr(str_url,
		function(text){		
			var head = document.getElementsByTagName('head')[0];
			var script = document.createElement('script');
			script.type = 'text/javascript';
			script.innerHTML = text;						
			head.appendChild(script);
			func_callBack();
		},
		function(text){
			/*
			var p = str_url.replace(sa_.baseUrl+"sA.","").replace(".js","");
			if (!sa_.log["plugin_"+p])
			{
				func_callBack();
			}
			sa_.log["plugin_"+p] = true;			
			*/ 
		}
	);
        
    /*
    // Adding the script tag to the head as suggested before
    var head = document.getElementsByTagName('head')[0];
    var script = document.createElement('script');
    script.type = 'text/javascript';
    script.src = str_url;

    // Then bind the event to the callback function.
    // There are several events for cross browser compatibility.
    script.onreadystatechange = func_callBack;
    script.onload = func_callBack;

    // Fire the loading
    head.appendChild(script);
    */
};
