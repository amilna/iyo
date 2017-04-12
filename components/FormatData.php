<?php
namespace amilna\iyo\components;

use Yii;
use yii\base\Component;
use yii\console\Application;
use yii\helpers\ArrayHelper;
use amilna\iyo\models\Data;

class FormatData extends Component
{		
	private $dbname = null; 
	private $prefix = null;
	private $db = null;
	private $dbusr = null;
	private $dbpwd = null;
	private $filename = false;
	private $dataid = null;			
	private $metadata = null;
	private $ext = null;	
	private $userid = null;
	private $uploadDir = null;
	private $tileDir = null;
	private $geom_col = null;
	private $postgis = 1.5;
	private $iswin = false;
	private $gdalinfo = 'gdalinfo';
	private $shp2pgsql = 'shp2pgsql';
	private $runtime = '';
	
	private $ogrexts = [".kml",".geojson",".gpx"];
	private $imgexts = [".tif"];
	private $importId = null;
	
	public function __construct($dsn,$tablePrefix,$username,$password,$param)
	{						
		$params = explode("~",$param);
		$uploadDir = $params[0];
		$tileDir = $params[1];
		$geom_col = $params[2];
		$dataid = $params[3];		
		$userid = $params[4];						
		
		if (isset($params[5]))
		{
			$this->postgis = floatval($params[5]);
		}
		
		if (isset($params[6]))
		{
			$this->gdalinfo = $params[6];
		}
		
		if (isset($params[7]))
		{
			$this->shp2pgsql = $params[7];
		}
		
		if (isset($params[8]))
		{
			$this->runtime = $params[8];
		}
			
		if (strtoupper(substr(\PHP_OS, 0, 3)) === 'WIN') {
			$iswin = true;
		} else {
			$iswin = false;	
		}
		$this->iswin = $iswin;	
		
		preg_match('/dbname\=(.*)?/', $dsn, $matches);			
		$this->dbname = $matches[1];
		$this->prefix = $tablePrefix;		
		$this->dataid = $dataid;
		$this->uploadDir = $uploadDir;
		$this->tileDir = $tileDir;
		$this->geom_col = $geom_col;		
		$this->dbusr = $username;
		$this->dbpwd = $password;
		
		$this->db = new \yii\db\Connection([
				'dsn' => $dsn,
				'username' => $username,
				'password' => $password,
				'tablePrefix'=>	$tablePrefix		
			]);				
					
		$sql = "SELECT metadata FROM ".Data::tableName()." 				
				WHERE id = ".$this->dataid;

		$this->metadata = $this->db->createCommand($sql)->queryScalar();						
					
		$path = $uploadDir;
						
		$metadata = json_decode($this->metadata,true);		
		$filename = isset($metadata["srcfile"])?$metadata["srcfile"]:false;														
		$filename = str_replace('%20',' ',$filename);
		
		if ($filename)
		{
			$this->ext = $this->checkFileExt($path.$filename);									
			$this->filename = $path.$filename;									
		}
		
	}	
	
	public function checkFileExt($filename = false)	
	{		
		$ext = false;		
		
		if ($filename)
		{			
			$file = $filename;
						
			if (file_exists($file))
			{				
				$ext = substr($file,strrpos($file,"."));
				
				if (in_array($ext,['.dbf','.prj','.qix','.qpj','.sbn','.sbx','.shp','.shx','.xml']))
				{
					$files = glob(str_replace($ext,"*",$file));										
					
					$shps = ['.dbf','.shp','.shx'];
					$exts = [];					
					foreach ($files as $f)
					{
						$e = substr($f,strrpos($f,"."));
						$exts[] = $e;	
					}
					$all = array_intersect($shps,$exts);									
					if ($all != $shps)
					{
						$ext = false;							
					}
					else
					{
						$ext = '.shp';
					}					
				}
				else
				{
					preg_match('/\.(\d+)\.zip/',$this->filename,$matches);					
					if (count($matches) > 1)
					{
						$ext = '.zip';
					}
					else
					{	
						preg_match('/\.zip\.(.*)/',$file,$matches);
						if (count($matches) > 1)
						{
							$ext = '.zip';
						}
						else
						{							
							if (!in_array($ext,array_merge([".zip"],$this->ogrexts,$this->imgexts)))
							{
								$ext = false;
							}
						}						
					}										
										
				}
			}
		}	
				
		return $ext;		
	}
	
	public function mkData($table,$geom_col,$type)
	{
		$sql = "select 1 from information_schema.columns where table_name = '".$table."' limit 1";
		$tes = $this->db->createCommand($sql)->queryScalar();				
		
		if ($tes != 1)
		{		
			$srid = "4326";																
			$sql = "UPDATE ".Data::tableName()." 
					SET srid = ".$srid." 
					WHERE id = ".$this->dataid;
			$updatesrid = !is_array($this->db->createCommand($sql)->execute());
			
			$sql = 'SET STANDARD_CONFORMING_STRINGS TO ON;
					BEGIN;
					CREATE TABLE IF NOT EXISTS "public"."'.$table.'" (
						gid serial PRIMARY KEY					
					);'.
					"SELECT AddGeometryColumn(cast('public' as varchar),cast('".$table."' as varchar),cast('".$geom_col."' as varchar),cast(".$srid." as int),cast('".strtoupper(Data::itemAlias("geomtype",$type))."' as varchar),cast(2 as int));".
					'CREATE INDEX "'.$table.'_'.$geom_col.'_gist" ON "public"."'.$table.'" using gist ("'.$geom_col.'");'.
					'END;';									
			
			return ($this->db->pdo->exec($sql) === false?false:true);									
		}
		else
		{
			return true;	
		}	
		
	}
	
	public function mkColumn($table,$column,$geom_col)
	{		
		$sql = "SELECT 1 
				FROM information_schema.columns 
				WHERE table_name='".$table."' LIMIT 1";
		$ntable = $this->db->createCommand($sql)->queryScalar();				
		
		if ($ntable == 1)
		{
			$sql = "SELECT count(column_name) 
					FROM information_schema.columns 
					WHERE table_name='".$table."' and column_name='".$column["name"]."' LIMIT 1";
			$ncolumn = $this->db->createCommand($sql)->queryScalar();							
					
			
			if ($ncolumn != 1)
			{						
				$column["type"] = str_replace("double precision","float",$column["type"]);
				$sql = "ALTER TABLE ".$table." ADD COLUMN ".'"'.$column["name"].'"'." ".$column["type"]." ".$column["options"];
				
				return !is_array($this->db->createCommand($sql)->execute());							
			}
			else
			{
				return true;	
			}	
		}
		else
		{					
			return false;	
		}				
	}
	
	public function mkRelColumns($relColumns,$table = false)
	{
		if (!$table)
		{
			$table = $this->prefix.str_replace(["{{%","}}"],"",Data::tableName())."_".$this->dataid;
		}
		$pret = $this->prefix.str_replace(["{{%","}}"],"",Data::tableName())."_";
		$tabs = [];
		$tables = '(';
		$cols = '(';
		foreach ($relColumns as $d=>$cs)
		{
			foreach ($cs as $c)
			{
				$tab = "'".$pret.$d."'";
				$col = "'".$c."'";
				if (!in_array($tab,$tabs))
				{
					$tables	.= ($tables == '('?'':',').$tab;
					$tabs[] = $tab;
				}	
				$cols	.= ($cols == '('?'':',').$col;
			}	
		}
		$tables .= ')';	
		$cols .= ')';
				
		$sql = "SELECT concat('_',replace(table_name,'".$pret."',''),'_',column_name) as column_name,data_type,character_maximum_length 
				FROM information_schema.columns 
				WHERE table_name IN ".$tables." and column_name IN ".$cols;
						
		$relCols = $this->db->createCommand($sql)->queryAll();			
		
		$res = true;
		foreach ($relCols as $c)
		{																								
			$col = [
				"name"=>$c["column_name"],
				"type"=>$c["data_type"].($c["character_maximum_length"]?"(".$c["character_maximum_length"].")":""),
				"options"=>""
			];
			$res0 = $this->mkColumn($table,$col,false);
			$res = ($res0?$res:false);	
		}
		
		//return $res?$relCols:false;
		return $relCols;
	}
	
	public function import()
	{													
		$geom_col = $this->geom_col;
		$metadata = json_decode($this->metadata,true);
		$pret = $this->prefix.str_replace(["{{%","}}"],"",Data::tableName())."_";
		$table = $pret.$this->dataid;				
		
		$sql = "UPDATE ".Data::tableName()." 
				SET status = 2 
				WHERE id = ".$this->dataid;

		$res = !is_array($this->db->createCommand($sql)->execute());
				
		if ($this->filename && $this->ext)
		{			
			
			$this->importId = $this->userid."_".time();
			
			if (in_array($this->ext,$this->ogrexts))
			{
				$act = "ogrImport";	
			}
			elseif (in_array($this->ext,$this->imgexts))
			{
				$act = "imgImport";	
			}
			else
			{			
				$act = substr($this->ext,1)."Import";						
			}	
					
			$res = $this->$act();									
						
			$sql = "SELECT column_name,data_type,character_maximum_length 
				FROM information_schema.columns 
				WHERE table_name='".$table."'";
						
			$cols = $this->db->createCommand($sql)->queryAll();			
									
			if (is_array($cols))
			{				
																				
				foreach ($cols as $c)
				{										
					if (!in_array($c["column_name"],[$geom_col,"gid"]))
					{
						$exists = false;
						foreach ($metadata["columns"] as $c0)
						{
							if ($c0["name"] == $c["column_name"])
							{
								$exists = true;	
							}
						}
						
						if (!$exists && substr($c["column_name"],0,1) != '_')
						{																
							$col = [
								"name"=>$c["column_name"],
								"type"=>$c["data_type"].($c["character_maximum_length"]?"(".$c["character_maximum_length"].")":""),
								"options"=>""
							];
							$metadata["columns"][] = $col;
						}
					}
				}
				$metadata["srcfile"] = "";					
				
				unset($metadata['isappend']);																				
				
				$sql = "UPDATE ".Data::tableName()." 
					SET (metadata) = ('".str_replace("'","''",json_encode($metadata))."') 
					WHERE id = ".$this->dataid;

				$res = !is_array($this->db->createCommand($sql)->execute());
								
			}					
		}
		else
		{			
			$res = true; 
		}											
		
		if (isset($metadata["columns"]) && $res)
		{							
			$sql = "SELECT type FROM ".Data::tableName()." 					
					WHERE id = ".$this->dataid;
			$type = $this->db->createCommand($sql)->queryScalar();
						
			$res = $this->mkData($table,$geom_col,$type);									
			
			$cols = isset($metadata["columns"])?$metadata["columns"]:[];			
			foreach ($cols as $col)
			{									
				$res0 = $this->mkColumn($table,$col,$geom_col);				
				$res = ($res0?$res:false);
			}						
		}	
		
		if (isset($metadata["relational_columns"]) && $res && isset($metadata["relational_columns_update"]))
		{										
			$cols = $this->mkRelColumns($metadata["relational_columns"]);
			
			$joins = [];
			
			$where = [];
			foreach ($metadata["relational_columns"] as $d=>$cs)
			{										
				$froms = "FROM ".$table." as d0";
				$sets = "SET (";
				$vals = "= (";
				
				foreach ($cs as $csc)
				{					
					$vals .= ($vals == "= ("?"":",")."d".$d.".".$csc;
					$sets .= ($sets == "SET ("?"":",").'_'.$d.'_'.$csc;
				}
				
				$sets .= ")";
				$vals .= ")";															
				
				$froms .= " LEFT JOIN ".$pret.$d." as d".$d." ON ST_INTERSECTS(d0.".$geom_col.",d".$d.".".$geom_col.")";				
				
				$sql = "UPDATE ".$table." as t ".$sets." ".$vals." ".$froms." WHERE t.gid=d0.gid AND ST_isvalid(d0.".$geom_col.") AND ST_isvalid(d".$d.".".$geom_col.")";
				$res = !is_array($this->db->createCommand($sql)->execute());												
			}
			unset($metadata['relational_columns_update']);																																			
				
			$sql = "UPDATE ".Data::tableName()." 
				SET (metadata) = ('".str_replace("'","''",json_encode($metadata))."') 
				WHERE id = ".$this->dataid;

			$res = !is_array($this->db->createCommand($sql)->execute());
		}
							
		$sql = "SELECT type FROM ".Data::tableName()." 					
					WHERE id = ".$this->dataid;
		$type = $this->db->createCommand($sql)->queryScalar();													
		$status = (in_array($type,[0,1,3,4,6])?1:3);
				
		
		$sql = "UPDATE ".Data::tableName()." 
				SET status = ".($res?$status:0)." 
				WHERE id = ".$this->dataid;						
				
		return !is_array($this->db->createCommand($sql)->execute());		
				
	}
	
	private function zipImport()
	{
		
		if (file_exists($this->filename) && $this->ext == ".zip")
		{			
			$ziptype = 0;
			preg_match('/\.(\d+)\.zip/',$this->filename,$matches);					
			if (count($matches) > 1)
			{
				$ziptype = 1;
			}
			else
			{
				preg_match('/\.(\d+)\.z(\d+)/',$this->filename,$matches);
				if (count($matches) > 1)
				{
					$ziptype = 2;
				}	
				else
				{
					preg_match('/\.zip\.(.*)/',$this->filename,$matches);
					if (count($matches) > 1)
					{
						$ziptype = 3;
					}	
				}
			}
			
			$bp = explode('.', $this->filename);
			$bname = $bp[0];															
			$basefile = $bname;
			$basefile = \amilna\yap\Helpers::shellvar($basefile);
				
			if ($ziptype == 0)
			{	
				$unzip = \amilna\yap\Helpers::zipExtract($basefile.'.zip',$basefile);							
			}			
			else
			{
				$unzip = \amilna\yap\Helpers::zipExtract($basefile.'.',$basefile);
			}
			
			$run = false;
			$files = glob($basefile."/*");						
			
			foreach ($files as $f)
			{				
				if (substr($f,-4) == ".shp")	
				{
					$run = $f;
					$act = 'shpImport';
				}
				elseif (in_array(substr($f,-4),$this->ogrexts))
				{
					$run = $f;
					$act = 'ogrImport';
				}
				elseif (in_array(substr($f,-4),$this->imgexts))
				{
					$run = $f;
					$act = 'imgImport';
				}
			}
			
			if ($run)
			{		
				if ($act == 'imgImport')
				{
					$bdir = dirname($basefile);
					$nfilename = $bdir.'/'.basename($run);
					
					$run = \amilna\yap\Helpers::shellvar($run);			
					$nfilename = \amilna\yap\Helpers::shellvar($nfilename);			
					
					rename($run, $nfilename);
					$this->filename = $nfilename;
				}
				else
				{
					$this->filename = $run;
				}
				
				if ($act == 'shpImport')
				{
					$this->ext = ".shp";
				}
				else
				{
					$this->ext = substr($f,-4);
				}	
				$this->$act();
				
				$basefile = \amilna\yap\Helpers::shellvar($basefile); 
			}
			else
			{
				$sql = "UPDATE ".Data::tableName()." 
				SET status = 5 
				WHERE id = ".$this->dataid;

				return !is_array($this->db->createCommand($sql)->execute());
			}			
		}
		else
		{
			$sql = "UPDATE ".Data::tableName()." 
				SET status = 5 
				WHERE id = ".$this->dataid;

			return !is_array($this->db->createCommand($sql)->execute());
		}
	}
	
	private function shpImport()
	{									
		$geom_col = $this->geom_col;
		$metadata = json_decode($this->metadata,true);
		
		if (file_exists($this->filename) && $this->ext == ".shp")
		{											
			$file = substr($this->filename,0,(strrpos($this->filename,"."))).$this->ext;
			
			$file = \amilna\yap\Helpers::shellvar($file);			
			
			$proj4 = shell_exec(str_replace('gdalinfo','gdalsrsinfo',$this->gdalinfo).' -o proj4 "'.$file.'" 2>&1');
			$ogrinfo = shell_exec(str_replace('gdalinfo','ogrinfo',$this->gdalinfo).' "'.$file.'" "'.basename($file,".shp").'" -so 2>&1');					
				
			$srid = "";
			if (strpos($proj4,"ERROR") === false && !empty($proj4))
			{								
				
				$srparams = explode(" ",trim(str_replace("'","",$proj4)));
				$sql = "SELECT auth_srid FROM spatial_ref_sys WHERE proj4text='".trim(str_replace("'","",$proj4))."'";
				
				$nsql = "";
				foreach ($srparams as $sr)
				{					
					$nsql .= ($nsql == ""?"":" AND ")."proj4text LIKE '%".$sr."%'";
				}
				$sql .= ($nsql==""?"":" OR (".$nsql.")");												
				
				$srid = $this->db->createCommand($sql)->queryScalar();																
									
				/*	
				preg_match('/Layer SRS WKT\:\~(\S*)\~/', preg_replace("/\,(\s*)/",",",str_replace("~ ","",str_replace(["\n","\t"],["~",""],$ogrinfo))), $matches);			
				$srtext = $matches[1];
				$srparams = explode("],",substr($srtext,6,-1));
				$sql = "SELECT auth_srid FROM spatial_ref_sys WHERE srtext='".$srtext."'";
				foreach ($srparams as $sr)
				{
					$sr .= (substr($sr,-1) == "]"?"":"]");
					$sql .= " OR srtext LIKE '%".preg_replace('/(\S*)DATUM/',"",$sr)."%'";
				}								
				$srid = $this->db->createCommand($sql)->queryScalar();
				*/ 
			}
			else
			{																				
				preg_match('/Extent\:(.*)/', $ogrinfo, $matches);								
				$extent = explode(",",preg_replace('/[^0-9\.\,\-]/',"",str_replace(") - (",",",$matches[1])));
				if ($extent[0] < -180 && $extent[1] < -90  && $extent[2] > 180 && $extent[3] > 90)
				{
					$srid = "3857";
				}					
			}
			$srid = ($srid==""?"4326":$srid);																
			$sql = "UPDATE ".Data::tableName()." 
					SET srid = ".$srid." 
					WHERE id = ".$this->dataid;
			$updatesrid = !is_array($this->db->createCommand($sql)->execute());			
			
			$table = $this->prefix.str_replace(["{{%","}}"],"",Data::tableName())."_".$this->dataid;
					
			$path = $this->runtime;			
			$filesql = $path."/".$table."_".$this->importId;								
			
			$append = false;
			if (isset($metadata['isappend']))
			{
				if ($metadata['isappend'])
				{
					$append = true;	
				}					
			}
			
			$file = \amilna\yap\Helpers::shellvar($file);			
			$filesql = \amilna\yap\Helpers::shellvar($filesql);			
			$table = \amilna\yap\Helpers::shellvar($table);			
			
			if ($this->postgis >= 2)
			{
				/* if use postgis 2 */									
				$cmds = $this->shp2pgsql." -t 2D ".($append?"-a":"-d")." -s ".$srid." -g ".$geom_col." -W latin1 ".'"'.$file.'"'." public.".$table." > ".'"'.$filesql.'"';
				$shp2pgsql = shell_exec($cmds);
				/* end */
			}
			else
			{
				/* if use postgis 1.5 */
				$cmds = $this->shp2pgsql." ".($append?"-a":"-d")." -s ".$srid." -g ".$geom_col." -W latin1 ".'"'.$file.'"'." public.".$table." > ".'"'.$filesql.'"';
				$shp2pgsql = shell_exec($cmds);
				$str=file_get_contents($filesql);
				$str=preg_replace('/ AddGeometryColumn\((.*),(\d+)\);/', ' AddGeometryColumn($1,2);',$str);
				$str=preg_replace('/\'([A-Z0-9]+)\'\);/', 'ST_Force_2D(CAST(\'$1\' as text)));',$str);
				file_put_contents($filesql, $str);
				/* end */
			}
			
			
			if (!$append)
			{
			
				$geom = 'Point';
				preg_match('/Postgis type\: ([A-Za-z]+)/', $shp2pgsql, $matches);
				if (isset($matches[1]))
				{
					$geom = str_replace([" ","3d"],"",strtolower(trim($matches[1])));								
				}
				else
				{
					preg_match('/Geometry\:(.*)/', $ogrinfo, $matches);
					if (isset($matches[1]))
					{							
						$geom = str_replace([" ","3d"],"",strtolower(trim($matches[1])));									
					}				
				}
				
				$sql = "UPDATE ".Data::tableName()." 
					SET remarks = '".$geom."' 
					WHERE id = ".$this->dataid;
					$gtype = !is_array($this->db->createCommand($sql)->execute());
				
				$data = new Data();
				$geoms = $data->itemAlias("geomtype");
				$geomtype = 0;
				
				foreach ($geoms as $g=>$gt)
				{
					$gt= strtolower(str_replace(" ","",$gt));						
					//if (strpos($gt,$geom) !== false && !$geomtype)
					if ($gt==$geom )
					{
						$geomtype = $g;								
					}						
				}			 
				
				$sql = "UPDATE ".Data::tableName()." 
				SET type = ".$geomtype." 
				WHERE id = ".$this->dataid;
				$updatetype = !is_array($this->db->createCommand($sql)->execute());
							
				//$sql = "DROP TABLE IF EXISTS ".$table."";
				//$drop = $this->db->createCommand($sql)->execute();
			}
			
			preg_match('/port\=(\d+)?/', $this->db->dsn, $patches);
			$portdb = (isset($patches[1])?$patches[1]:'5432');
			preg_match('/host\=(\d+)?/', $this->db->dsn, $patches);		
			$hostdb = (isset($patches[1])?$patches[1]:'localhost');
			
			$cmds = str_replace('shp2pgsql','psql',$this->shp2pgsql).' "user='.$this->dbusr.' password='.$this->dbpwd.' host='.$hostdb.' port='.$portdb.' dbname='.$this->dbname.'" < "'.$filesql.'"';
			/*
			if ($this->iswin)
			{
				$cmds = str_replace('shp2pgsql','psql',$this->shp2pgsql).' "user='.$this->dbusr.' password='.$this->dbpwd.' host='.$hostdb.' port='.$portdb.' dbname='.$this->dbname.'" < "'.$filesql.'"';
			}
			else
			{
				$cmds = "PGPASSWORD=".$this->dbpwd." psql ".$portdb."-q -w -U ".$this->dbusr." -d ".$this->dbname." < ".'"'.$filesql.'"';
			}
			*/ 
			
			$psql = shell_exec($cmds);
			
			unlink($filesql);						
			
			$sql = "SELECT column_name,data_type,character_maximum_length 
				FROM information_schema.columns 
				WHERE table_name='".$table."'";
						
			return $this->db->createCommand($sql)->queryAll();
		}
		else
		{
			$sql = "UPDATE ".Data::tableName()." 
				SET status = 5 
				WHERE id = ".$this->dataid;

			return !is_array($this->db->createCommand($sql)->execute());			
		}
	}		
	
	private function imgImport()
	{									
		$compDir = __DIR__ ;
		$uploadDir = $this->uploadDir;				
		
		$bfile = substr($this->filename,0,(strrpos($this->filename,".")));
		$tileDb = $compDir.'/tile.db';
		$imgDb = $compDir.'/indeks.db';				
		
		if (!file_exists($imgDb))
		{			
			$cpDb = shell_exec("cp '".$tileDb."' '".$imgDb."'");
		}		
				
		$sqlDb = new \yii\db\Connection([
			'dsn' => 'sqlite:'.$imgDb,
		]);			
			
		$sql = 'CREATE TABLE IF NOT EXISTS indeks (
				name TEXT NOT NULL, 
				filename TEXT NOT NULL, 
				epsg INT NOT NULL,
				minx REAL NOT NULL,
				miny REAL NOT NULL,
				maxx REAL NOT NULL,
				maxy REAL NOT NULL
				)';
				
		$createDb = $sqlDb->createCommand($sql)->execute();
		
		$bfile = substr($this->filename,0,(strrpos($this->filename,"."))).$this->ext;		
		
		$files = [];
		foreach ($this->imgexts as $e)
		{
			$files = array_merge($files,glob(dirname($bfile).'/*'.$e));
		}
		
		$metadata = json_decode($this->metadata,true);		
		$name = $metadata["tilename"];			
		$sql = 'DELETE from indeks WHERE name = :name';				
		$del = $sqlDb->createCommand($sql)->bindValues([':name'=>$name])->execute();		
		
		$insertall = true;
		$failinsert = [];
		foreach ($files as $file)
		{				
			$file = \amilna\yap\Helpers::shellvar($file);			
			
			$gdalinfo = shell_exec("gdalinfo '".$file."'");
						
			preg_match('/Lower Left([ ]+)\(([ ]+)?(-?[0-9\.]+),([ ]+)?(-?[0-9\.]+)\)/', $gdalinfo, $min);		
			preg_match('/Upper Right([ ]+)\(([ ]+)?(-?[0-9\.]+),([ ]+)?(-?[0-9\.]+)\)/', $gdalinfo, $max);
			preg_match('/\n    AUTHORITY\[\"EPSG\",\"(\d+)\"\]/', $gdalinfo, $epsg);																
			
			if (isset($epsg[1]) && isset($min[3]) && isset($min[5]) && isset($max[3]) && isset($max[5]))
			{
					
				$sql = 'INSERT INTO indeks   
					VALUES (:name, :filename, :epsg, :minx, :miny, :maxx, :maxy);';						
							
				$insert = !is_array($sqlDb->createCommand($sql)->bindValues([':name'=>$name,':filename'=>$file,
						':epsg'=>$epsg[1],':minx'=>$min[3],':miny'=>$min[5],':maxx'=>$max[3],':maxy'=>$max[5]])->execute());													
					
			}
			else
			{
				$insert = false;
				array_push($failinsert,['file'=>$file,'gdalinfo'=>$gdalinfo]);	
			}		
			
			$insertall = !$insertall?false:$insert;
		}		
		
		if ($insertall)
		{					
			$metadata["srcfile"] = "";
			$metadata = json_encode($metadata);																								
			
			$sql = "UPDATE ".Data::tableName()." 
				SET (metadata,remarks) = ('".str_replace("'","''",$metadata)."','') 
				WHERE id = ".$this->dataid;						
			
			$res = !is_array($this->db->createCommand($sql)->execute());
		}		
		else
		{
			$failinsert = json_encode($failinsert);																								
			
			$sql = "UPDATE ".Data::tableName()." 
				SET (remarks) = (concat(remarks,',','".str_replace("'","''",$failinsert)."')) 
				WHERE id = ".$this->dataid;						
			
			$res = !is_array($this->db->createCommand($sql)->execute());						
		}
		
		return $res;		
	}						
	
	private function ogrImport()
	{									
		$geom_col = $this->geom_col;
		$exts = $this->ogrexts;
		if (file_exists($this->filename) && in_array($this->ext,$exts))
		{																
			$bfile = substr($this->filename,0,(strrpos($this->filename,".")));
			$file = $bfile.$this->ext;
			
			$bfile = \amilna\yap\Helpers::shellvar($bfile);			
			$file = \amilna\yap\Helpers::shellvar($file);			
			
			$ogr2shp = shell_exec("ogr2ogr -f 'ESRI Shapefile' -overwrite '".$bfile.".shp' '".$file."'");
						
			$this->filename = $bfile.".shp";
			$this->ext = ".shp";
			$this->shpImport();
			$unlink = shell_exec("				
				rm -R '".$bfile.".shp';				
			");
									
		}
		else
		{
			$sql = "UPDATE ".Data::tableName()." 
				SET status = 5 
				WHERE id = ".$this->dataid;

			return !is_array($this->db->createCommand($sql)->execute());
		}
	}	
	
	/* creates a compressed zip file */
	public static function create_zip($files = array(),$destination = '',$overwrite = false) {	  	  
	  
		if (file_exists($destination) && !$overwrite) { return false; }

		if (!file_exists($destination))
		{
			$overwrite = false;  
		}
		
		$valid_files = array();
		//if files were passed in...
		if(is_array($files)) {
			//cycle through each file
			foreach($files as $file) {
				//make sure the file exists     
				if(file_exists($file)) {
					$valid_files[] = $file;
				}
			}
		}	  	  

		//if we have good files...
		if(count($valid_files)) {		

			//create the archive
			$zip = new \ZipArchive();
			
			if($zip->open($destination,$overwrite ? \ZIPARCHIVE::OVERWRITE : \ZIPARCHIVE::CREATE) !== true) {
				return false;
			}			

			//add the files
			foreach($valid_files as $file) {			  		  
				$zip->addFile($file,basename($file));
			}
			//debug
			//echo 'The zip archive contains ',$zip->numFiles,' files with a status of ',$zip->status;

			//close the zip -- done!
			$zip->close();

			//check to make sure the file exists
			return file_exists($destination);
		}
		else
		{
			return false;
		}
	}	
	
}
