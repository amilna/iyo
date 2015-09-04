/* usage
 * 1. define activeModels should included
 * 2. define initApps function block
 * 3. create sa instance
 * 4. define s.init function
 * 5. example:

activeModels = ["product"];		
var initApps = function(db){
	//put your custom init code here
};
var sa = new sA({baseUrl:"js/",plugins:["Db"]});
//sa.baseUrl = "js/";
sa.init(function(){
	initDb();						
});
 
 
 * 6. example usage of sA.Db	

var sql = "username like 'Satrio%' AND id > 10";
//var sql = ["id > ? OR id < ?",[4,3]];
//var sql = "";

var u1 = db.getModel("user");

u1.find(sql,function(db,m){				
	console.log(m.lastId,m.selection);				
});

var val = {id:11,username:"ahmad Salman"};
var val2 = {username:"iyo"};
var val3 = {username:"Satrio Arditama"};

u1.setRec(val,function(db,m){				
	console.log(m);
});			
			
var u2 = db.getModel("User 2");								
			
//u2.delRec(["id > ? OR id < ?",[4,3]],function(db,m){
//u2.delRec("id > 2 AND id < 5 OR id > 10",function(db,m){	
//u2.delRec("",function(db,m){									
//console.log(u2.lastId);	
u2.delRec(u2.lastId,function(db,m){		
	m.setRec(val2,function(db,m2){
		console.log(m2);			
		console.log(Db);
	});
}); 

*/


sA.Db = function(str_name,arrObj_options) {
	
	this.name = "Amilna DB";
	if (this.isObj(str_name)) {
		this.name = str_name;
	}
	
	this.dbName = this.name.replace(/[^a-zA-Z0-9]/g,"_");
	
	var options = {};
	if (this.isObj(arrObj_options)) {
		options = arrObj_options;
	}
	
	this.version = "";
	if (this.isObj(options.version)) {
		this.version = options.version;
	}
	
	this.initSize = 64*1024;
	if (this.isObj(options.initSize)) {
		this.initSize = options.initSize;
	}
	
	this.db = openDatabase(this.dbName,this.version,this.name,this.initSize);				
	
	this.models = [];	
	if (this.isObj(options.models)) {				
		if (this.isObj(options.callBack))
		{						
			this.mkModels(options.models,options.callBack);
		}
		else
		{
			this.mkModels(options.models);
		}
	}	
	
};
sA.Db.prototype = Object.create(sA.prototype);
sA.Db.prototype.constructor = sA.Db;

sA.Db.Model = function(str_name,arrObj_options) {	
	this.name = "Amilna DB Model";
	if (this.isObj(str_name)) {
		this.name = str_name;
	}
	
	var n = str_name.substr(0,1);
	if (!isNaN(parseFloat(n)) && isFinite(n))
	{
		str_name = "N"+str_name;
	}
	
	this.tableName = str_name.toLowerCase().replace(/[^a-zA-Z0-9]/g,"_");
	this.data = [];
	this.selection = [];
	this.lastId = 0;
	this.keys = [];
	this.hasTable = false;
	this.db = undefined;
	
	var options = {};
	if (this.isObj(arrObj_options)) {
		options = arrObj_options;
	}
	
	this.attr = [{"name":"id","type":"integer","options":"NOT NULL PRIMARY KEY AUTOINCREMENT"}];
	if (this.isObj(options.attr)) {
		this.attr = options.attr;
	}
};
sA.Db.Model.prototype = Object.create(sA.prototype);
sA.Db.Model.prototype.constructor = sA.Db.Model;
	
sA.Db.prototype.erSql = function(obj_transaction, obj_result) {
	console.log("DB error : " + obj_result.message);		
	return false;
};

sA.Db.prototype.okSql = function(obj_transaction, obj_result) {	
	console.log("DB ok : " + obj_result);
	return true;
};

sA.Db.prototype.runSql = function (str_sql,func_ifOk,func_ifError,obj_values) {	
	var okSql = this.okSql;
	if (this.isObj(func_ifOk)) {
		okSql = func_ifOk;
	}
	
	var erSql = this.erSql;
	if (this.isObj(func_ifError)) {
		erSql = func_ifError;
	}
	
	var valSql = undefined;
	if (this.isObj(obj_values)) {
		valSql = obj_values;
	}		
	
	this.db.transaction(function(tx) {
		tx.executeSql(str_sql,valSql,okSql,erSql);		
	});							
};

sA.Db.prototype.mkModels = function(arrObj_models,func_callBack) {					
	
	var saDb = {mkModels:{}};
	var i = 0;
	
	var callback = (this.isObj(func_callBack)?func_callBack:undefined);
	
	saDb.mkModels.build = function(db) {
		if (arrObj_models.length > 0)
		{
			var m = arrObj_models[i];
			i += 1;
			var mod = new sA.Db.Model(m.name,{attr:m.attr});		
			mod.db = db;
			db.models.push(mod);				
			//console.log("build");
			if (mod.inArray(m,arrObj_models) < arrObj_models.length-1)
			{
				db.getModel(mod.name,saDb.mkModels.build);	
			}
			else
			{
				db.getModel(mod.name,callback);			
			}
			return this.models;	
		}
		else
		{
			return false;
		}
	};
		
	/*
	for (var i; i < arrObj_models.length i++)
	{
		var m = arrObj_models[i];		
		m = new sA.Db.Model(m.name,{attr:m.attr});
		m.db = this;
		this.models.push(m);
		 
		return this.models;
	}
	*/ 
	return saDb.mkModels.build(this);	
};	

sA.Db.prototype.getModel = function(str_name,func_callBack) {		
	var model = false;
	for (var i in this.models)
	{
		var m = this.models[i];		
		if (m.name == str_name)
		{				
			model = m;
		}	
	}		
	
	var callback = (this.isObj(func_callBack)?func_callBack:undefined);
		
	if (model)
	{				
		if (!model.hasTable) {
						
			model.load(callback);						
		}				
		
		return model;	
	}
	else
	{
		return false;	
	}	
};

sA.Db.prototype.mkData = function(model,func_callBack) {
	
	var sql = "CREATE TABLE "+model.tableName +" (";			
		
	for (i in model.attr)
	{
		var a = model.attr[i];
		var opsi = "";
		if (this.isObj(a.options)) {
			opsi = a.options;
		}		
		sql = sql+a.name+" "+a.type.toUpperCase()+" "+opsi+", ";				
	}
		
	sql = sql.substr(0,sql.length-2)+")";
	
	var saDb = {mkData:{}};	
		
	saDb.mkData.ok = function(transaction,result) {						
				model.hasTable = true;
				if (model.isObj(func_callBack))
				{															
					func_callBack(model.db,model);
				}	
				//console.log(transaction,result);
			};
	
	saDb.mkData.error = this.erSql;	
					
	this.runSql(sql,saDb.mkData.ok,saDb.mkData.error);			
};
	
sA.Db.prototype.rmData = function(model,func_callBack) {				
	var sql = "DROP TABLE "+model.tableName;
	
	var saDb = {rmData:{}};	
	saDb.rmData.ok = function(transaction,result) {																	
				model.hasTable = false;
				if (model.isObj(func_callBack))
				{															
					func_callBack(model.db,model);
				}
				//console.log(transaction,result);
			};
			
	saDb.rmData.error = this.erSql;
					
	this.runSql(sql,saDb.rmData.ok,saDb.rmData.error);
};

sA.Db.Model.prototype.load = function(func_callBack) {
	var db = this.db;
	var obj_model = this;
	var sql = "SELECT name FROM sqlite_master WHERE type='table' AND name='"+obj_model.tableName+"'";	
	
	var callback = (obj_model.isObj(func_callBack)?func_callBack:undefined);
	
	var saDbModel = {load:{}};	
	saDbModel.load.ok = function(transaction,obj_result) {
				if (obj_result.rows.length > 0)
				{												
					obj_model.find("",function(db,m){						
						
						db.runSql("SELECT seq FROM sqlite_sequence WHERE name = '"+m.tableName+"'",
							function(trx,res){																
								
								if (res.rows.length !== false)
								{												
									for (var i = 0; i < res.rows.length; i++) 
									{
										var row = res.rows.item (i);					
										m.lastId = row.seq;										
									}																									
									
								}	
								
								for (var i in m.selection)
								{							
									//m.lastId = (m.selection[i].id > m.lastId ? m.selection[i].id : m.lastId );
									m.keys.push(m.selection[i].id);
									m.data.push(m.selection[i]);
								}												
								m.hasTable = true;
								m.selection = [];
								
								if (m.isObj(func_callBack))
								{															
									//console.log("teso",func_callBack);
									func_callBack(m.db,m);							
								}	
							}
						);		
												
					});
				}
				else
				{									
					db.mkData(obj_model,callback);
				}
			};
				
	saDbModel.load.error = function(transaction,obj_result) {
				db.mkData(obj_model,callback);
			};
					
	this.db.runSql(sql,saDbModel.load.ok,saDbModel.load.error);
};


sA.Db.Model.prototype.getRec = function(id) {
	var data = false;		
	for(i in this.data)
	{						
		var d = this.data[i];		
		if (d.id == id)
		{
			data = d;			
			return data;			
			break;
		}				
	}	
};

sA.Db.Model.prototype.setRec = function(arrObj_val,func) {
	
	var col = "";
	var val = "";	
	var rec = {};		
	var pk = {};
	var vals = [];
	
	var obj_cond = false;
	if( Object.prototype.toString.call( arrObj_val ) === '[object Array]' ) {
		var atribut = arrObj_val[0];
		obj_cond = (this.isObj(arrObj_val[1])?arrObj_val[1]:"");
	}
	else
	{
		var atribut = arrObj_val;
	}
	
	var wpk = false;
	var model = this;
	var id = false;
	var wvals = false;
	var str_sql = "1 = 2";		
	
	if (obj_cond)
	{
		var c2v = this.cond2Val(obj_cond);
		if (c2v) {
			str_sql = c2v.str_sql;
			wvals = c2v.vals;
			wpk = c2v.pk;
		}		
	}
	
	for (i in this.attr)
	{			
		var a = this.attr[i];			
		rec[a.name] = (this.isObj(atribut[a.name])?atribut[a.name]:null);
		
		if (a.options.indexOf("PRIMARY KEY") >= 0 )
		{																	
			pk.key = a.name;			
			rec[a.name] = (this.lastId+1);
		}
					
		if (this.isObj(atribut[a.name]))
		{										
			col = col+a.name+", ";
			val = val+"?, ";
			vals.push(atribut[a.name]);			
				
			if (a.options.indexOf("PRIMARY KEY") >= 0 )
			{																	
				pk.sql = a.name+" = "+atribut[a.name];
				pk.key = a.name;
				pk.val = parseInt(atribut[a.name]);
				
				rec[a.name] = pk.val;
			}			
		}	
	}
	
	col = (col == ""?"":col.substr(0,col.length-2));
	val = (val == ""?"":val.substr(0,val.length-2));	
	
	var sqln = "INSERT INTO "+this.tableName+" ("+col+") VALUES ("+val+")";
	//console.log(sqln,vals);		
	
	var dcols = col.split(", ");
	var dvals = val.split(", ");
	
	var sqlup = "";
	for (var i=0;i<dcols.length;i++)
	{			
		sqlup = sqlup+dcols[i]+" = "+dvals[i]+", ";
	}	
	sqlup = (sqlup == ""?"":sqlup.substr(0,sqlup.length-2));
		
	var sqlu = "UPDATE "+this.tableName+" SET "+sqlup+" WHERE "+(obj_cond || !this.isObj(pk.val)?str_sql:pk.key+" = "+pk.val);				
		
	
	var saDbModel = {setRec:{}};
	saDbModel.setRec.ok = 
		function(transaction,result)
		{																										
			/*
			if (obj_cond || !model.isObj(pk.val))
			{								
				model.find("",function(db,m){
					m.data = m.selection.concat([]);
					m.selection = [];
					
					console.log(obj_cond,pk.val);
					
					if (m.isObj(func))
					{
						func(db,m);
					}	
				});			
				
			}
			else
			{	
			*/
				model.db.runSql("SELECT seq FROM sqlite_sequence WHERE name = '"+model.tableName+"'",
					function(trx,res){
						
						var n = -1;
						if (model.isObj(pk.val))
						{
							n = model.inArray(pk.val,model.keys);
						}
						
						if (res.rows.length !== false)
						{												
							for (var i = 0; i < res.rows.length; i++) 
							{
								var row = res.rows.item (i);					
								model.lastId = row.seq;
							}							
							
							if (n < 0)
							{														
								rec[pk.key] = (model.isObj(pk.val)?pk.val:model.lastId);
							}
							
						}	
																
						if (n < 0)
						{
							//console.log("tes",n,model.data);
							model.data.push(rec);
							if (model.isObj(pk.key))
							{
								model.keys.push(model.isObj(pk.val)?pk.val:model.lastId);
								model.lastId = model.lastId;
							}	
						}
						else
						{												
							model.data[n] = rec;
						}	
						
						//console.log("tes2",model);
						
						if (model.isObj(func))
						{
							func(model.db,model);
						}		
					}
				);
			//}													
		};
			
	saDbModel.setRec.error = this.db.erSql;			
	
	var sql = sqln;
	
	if (obj_cond)
	{
		var sql = sqlu;
	}
	else
	{	
		if (this.isObj(pk.sql))	{
			/*
			this.find(pk.sql,function(db,model){
				if (model.selection.length > 0)
				{
					var sql = sqlu;
				}
				else
				{
					var sql = sqln;
				}
				db.runSql(sql,saDbModel.setRec.ok,saDbModel.setRec.error,vals);
			});
			*/ 
			if (model.inArray(pk.val,model.keys) >= 0)
			{
				var sql = sqlu;
			}		
		}
	}
	//console.log(sql,vals);
	this.db.runSql(sql,saDbModel.setRec.ok,saDbModel.setRec.error,vals);	
									
};

sA.Db.Model.prototype.cond2Val = function(obj_cond) {
	var pk = false;
	var id = false;
	var vals = false;
	var str_sql = false;
	
	if (this.isObj(obj_cond))
	{
		if (!isNaN(parseFloat(obj_cond)) && isFinite(obj_cond))
		{
			id = obj_cond;

			for (var i in this.attr)
			{			
				var a = this.attr[i];			
				if (a.options.indexOf("PRIMARY KEY") >= 0)
				{				
					pk = {};
					pk.key = a.name;
					pk.val = id;
					pk.sql = pk.key+" = ?";
					vals = [id];
				}
			}
			
			if (pk)
			{			
				str_sql = pk.sql;				
			}	
		}
		else
		{
			if (obj_cond.length >= 1)
			{
				if ( typeof obj_cond === 'string' ) {
					obj_cond = [ obj_cond ];
				}
				
				str_sql = obj_cond[0];
				
				if (str_sql.indexOf("?") >= 0)
				{			
					vals = obj_cond[1];
								
					if (str_sql.split(/\?/g).length-1 == vals.length)
					{
						//console.log(str_sql.split(/\?/g),vals);
						pk = true;	
					}
				}
				else
				{
					vals = [];
					pk = true;	
				}
			}
			else
			{
				str_sql = "1 = 1";
				vals = [];
				pk = true;
			}
		}
	}
	else
	{
		str_sql = "1 = 1";
		vals = [];
		pk = true;
	}
	return {str_sql:str_sql,vals:vals,pk:pk};
};	
	
sA.Db.Model.prototype.delRec = function(obj_cond,func) {									
	var pk = false;
	var model = this;
	var id = false;
	var vals = false;
	var str_sql = false;
	
	var c2v = this.cond2Val(obj_cond);
	if (c2v) {
		str_sql = c2v.str_sql;	
		vals = c2v.vals;	
		pk = c2v.pk;
	}		
							
	if (pk)
	{			
		var sql = "DELETE FROM "+this.tableName+" WHERE "+str_sql;						
		
		var saDbModel = {delRec:{}};
		saDbModel.delRec.ok = 
			function(transaction,result)
			{													
				if (pk === true)
				{										
					model.find("",function(db,m){
						m.data = m.selection.concat([]);
						m.selection = [];
						if (m.isObj(func))
						{
							func(db,m);
						}	
					});			
				}
				else
				{
					var n = model.inArray(pk.val,model.keys);
					
					if (n >= 0)
					{
						model.data.splice(n,1);				
						model.keys.splice(n,1);
					}
					
					if (model.isObj(func))
					{			
						func(model.db,model);
					}
				}				 									
				
			};
		
		saDbModel.delRec.error = this.db.erSql;			
		
		//console.log(sql,vals);
		this.db.runSql(sql,saDbModel.delRec.ok,saDbModel.delRec.error,vals);
	}
};

sA.Db.Model.prototype.find = function(obj_cond,func) {
	var pk = false;
	var model = this;
	var id = false;
	var vals = false;
	var str_sql = false;
	
	var c2v = this.cond2Val(obj_cond);
	if (c2v) {
		str_sql = c2v.str_sql;	
		vals = c2v.vals;	
		pk = c2v.pk;
	}
	
	if (pk)
	{		
		var sql = "SELECT * FROM "+this.tableName+" WHERE "+str_sql;
		
		var saDbModel = {find:{}};
		
		saDbModel.find.ok = 
			function(transaction,result)
			{								
				model.selection = [];
				
				if (result.rows.length !== false)
				{												
					for (var i = 0; i < result.rows.length; i++) 
					{
						var row = result.rows.item (i);					
						model.selection.push(row);					
					}								
										
					if (model.isObj(func))
					{						
						//console.log("tes1",model);
						func(model.db,model);										
					}
				}									
				
			};
									
		saDbModel.find.error = 
			function(transaction,err)
			{																		
				if (model.isObj(func))
				{						
					func(model.db,model);	
				}					
			};
		
		
		this.db.runSql(sql,saDbModel.find.ok,saDbModel.find.error,vals);		
	}	
};
