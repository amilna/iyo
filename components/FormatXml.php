<?php
namespace amilna\iyo\components;

use Yii;
use yii\base\Component;
use yii\console\Application;
use yii\helpers\ArrayHelper;
use amilna\iyo\models\Data;
use amilna\iyo\models\Layer;

class FormatXml extends Component
{		
	private $dbname = null; 
	private $prefix = null;
	private $db = null;	
	private $userid = null;			
	private $layer = null;	
	private $geom_col = null;
	
	public function __construct($dsn,$tablePrefix,$username,$password,$param)
	{										
		$params = explode(":",$param);
		$layerid = $params[0];
		$layername = $params[1];		
		$geom_col = $params[2];
		$userid = $params[3];						
		
		preg_match('/dbname\=(.*)?/', $dsn, $matches);			
		$this->dbname = $matches[1];
		$this->prefix = $tablePrefix;				
		$this->geom_col = $geom_col;		
		$this->userid = $userid;		
		
		$this->db = new \yii\db\Connection([
				'dsn' => $dsn,
				'username' => $username,
				'password' => $password,
				'tablePrefix'=>	$tablePrefix		
			]);								
					
		$sql = "SELECT l.data_id as did,d.srid as srid,l.config as config,p.proj4text as proj FROM ".Layer::tableName()." as l LEFT JOIN ".Data::tableName()." as d ON d.id=l.data_id LEFT JOIN spatial_ref_sys as p ON p.auth_srid=d.srid WHERE l.id = :id AND replace(lower(l.title),' ','_') = :name";						
		$config = $this->db->createCommand($sql)->bindValues([':id'=>$layerid,':name'=>$layername])->query();
		
		if (empty($config))
		{
			return false;
		}
		else
		{
			foreach ($config as $c)
			{				
				$this->layer = $c;
			}			
		}				
		
	}		
	
	public function getXml()
    {								
		if (empty($this->layer))
		{
			return false;
		}
		else
		{
			$c = $this->layer;	
			$configs = json_decode($c['config'],true);
			$proj = $c['proj'];
			$did = $c['did'];
			$lsrid = $c['srid'];
		}		
		
		$map = new \SimpleXMLElement('<Map/>');
		$map->addAttribute('srs', "+proj=merc +a=6378137 +b=6378137 +lat_ts=0.0 +lon_0=0.0 +x_0=0.0 +y_0=0 +k=1.0 +units=m +nadgrids=@null +wktext  +no_defs");						
		
		$n = 0;
		foreach ($configs as $config)
		{			
			$n += 1;								
			
			$style = $map->addChild('Style');
			$style->addAttribute('name','Style'.$n);
			
			$islabel = false;
			
			foreach ($config['rules'] as $r)
			{
				$rule = $style->addChild('Rule');
				if (isset($r['name']))
				{
					$rule->addAttribute('name',$r['name']);
				}
				if (isset($r['filter']))
				{
					$filter = $rule->addChild('Filter',$r['filter']);
				}						
				if (isset($r['polygonSymbolizer']))
				{
					$polygonSymbolizer = $rule->addChild('PolygonSymbolizer');
					foreach ($r['polygonSymbolizer'] as $key=>$val)
					{
						$polygonSymbolizer->addAttribute(strtolower(preg_replace('/([A-Z])/','-$1',$key)),$val);								
					}	
				}
				if (isset($r['lineSymbolizer']))
				{
					$lineSymbolizer = $rule->addChild('LineSymbolizer');
					foreach ($r['lineSymbolizer'] as $key=>$val)
					{
						$lineSymbolizer->addAttribute(strtolower(preg_replace('/([A-Z])/','-$1',$key)),$val);								
					}	
					$lineSymbolizer->addAttribute('stroke-linejoin','bevel');	
				}
				if (isset($r['style']))
				{
					if (isset($r['style']['src']) || isset($r['style']['radius']))
					{
						$pointSymbolizer = $rule->addChild('PointSymbolizer');					
					}
						
					if (isset($r['style']['label']) && !(isset($r['style']['src']) || isset($r['style']['radius'])))
					{
						if (isset($r['style']['label']['attribute']) || isset($r['style']['label']['text']))
						{
							$islabel = $r['style']['label'];														
						}	
					}	
				}
			}
			
			if ($islabel)
			{				
				$lstyle = $map->addChild('Style');
				$lstyle->addAttribute('name','Label'.$n);
				$lrule = $lstyle->addChild('Rule');
				
				if (isset($islabel['attribute']))
				{					
					$textSymbolizer = $lrule->addChild('TextSymbolizer','['.$islabel['attribute'].']');
				}
				else
				{
					$textSymbolizer = $lrule->addChild('TextSymbolizer',$islabel['text']);
				}
				
				$size = 10;
				$face = 'arial';
				if (isset($islabel['font']))
				{
					preg_match('/(\d+)px ([a-zA-Z0-9\-_ ]+)/',$islabel['font'],$matches);
					if (count($matches) > 0)
					{
						$size = $matches[1];
						$face = $matches[2];
					}					
				}
				$textSymbolizer->addAttribute('size',$size);
				$textSymbolizer->addAttribute('face-name',$face);
				$textSymbolizer->addAttribute('fill',isset($islabel['color'])?$islabel['color']:'#000000');
				$textSymbolizer->addAttribute('halo-fill',isset($islabel['strokeColor'])?$islabel['strokeColor']:'#ffffff');
				$textSymbolizer->addAttribute('halo-radius',isset($islabel['strokeWidth'])?$islabel['strokeWidth'].'':'1');
				$textSymbolizer->addAttribute('avoid-edges',"true");
				$textSymbolizer->addAttribute('allow-overlap',"false");				
			}
			
			$slayer = $map->addChild('Layer');				
			$ll = [$slayer];
			if ($islabel)
			{
				$llayer = $map->addChild('Layer');
				$ll[] = $llayer;
			}
						
			foreach ($ll as $layer)
			{
				$layer->addAttribute('name','Layer'.($layer == $slayer?'':'Label').$n);				
				$layer->addAttribute('srs',$proj);
				
				$layer->addChild('StyleName',($layer == $slayer?'Style':'Label').$n);
				$data = $layer->addChild('Datasource');
				$type = $data->addChild('Parameter','postgis');
				$type->addAttribute('name','type');
				
				$dsn = $this->db->dsn;
				preg_match('/host\=(.*);/', $dsn, $matches);				
				$host = $data->addChild('Parameter',$matches[1]);
				$host->addAttribute('name','host');
				
				preg_match('/dbname\=(.*)?/', $dsn, $matches);				
				$dbname = $data->addChild('Parameter',$matches[1]);
				$dbname->addAttribute('name','dbname');
				
				$username = $data->addChild('Parameter',$this->db->username);
				$username->addAttribute('name','username');
				
				$password = $data->addChild('Parameter',$this->db->password);
				$password->addAttribute('name','password');
				
				$geom_col = $this->geom_col;
				if (isset($config['dataquery']))
				{
					preg_match('/(\d+)/',$config['dataquery'],$qid);
					//$sql = "(SELECT * FROM ".$model->db->tablePrefix."iyo_data_".count($did) > 0?$did[1]:$model->data_id.") as layer".$n;					
					
					preg_match('/intersect\((\d+)\,(\d+)\)/',$config['dataquery'],$intersect);
					preg_match('/centerOf\((\d+)\)/',$config['dataquery'],$centerof);					
					preg_match('/centerOn\((\d+)\)/',$config['dataquery'],$centeron);					
					if (count($intersect) > 0)
					{
						$sql = "(SELECT * FROM ".$this->db->tablePrefix."iyo_data_".$intersect[1]." itc1,".$this->db->tablePrefix."iyo_data_".$intersect[2]." itc2 WHERE ST_INTERSECT(itc1.".$geom_col.",itc2.".$geom_col.")) as layer".$n;
					}					
					elseif (count($centerof) > 0)
					{
						
						$cols = "gid,";						
						$sql = "SELECT metadata FROM ".Data::tableName()." WHERE id = :id";						
						$dataquery = $this->db->createCommand($sql)->bindValues([':id'=>$centerof[1]])->queryScalar();
						
						if (!empty($dataquery))
						{
							$metadata = json_decode($dataquery,true);
							if (isset($metadata['columns']))
							{
								foreach ($metadata['columns'] as $mc)
								{
									$cols .= $mc['name'].",";
								}							
							}						
						}
						$sql = "(SELECT ".$cols."(ST_CENTROID(cto1.".$geom_col.")) as ".$geom_col." FROM ".$this->db->tablePrefix."iyo_data_".$centerof[1]." cto1) as layer".$n;
					}
					elseif (count($centeron) > 0)
					{
						
						$cols = "gid,";						
						$sql = "SELECT metadata FROM ".Data::tableName()." WHERE id = :id";						
						$dataquery = $this->db->createCommand($sql)->bindValues([':id'=>$centeron[1]])->queryScalar();
						
						if (!empty($dataquery))
						{
							$metadata = json_decode($dataquery,true);
							if (isset($metadata['columns']))
							{
								foreach ($metadata['columns'] as $mc)
								{
									$cols .= $mc['name'].",";
								}							
							}						
						}
						$sql = "(SELECT ".$cols."(ST_POINTONSURFACE(cto1.".$geom_col.")) as ".$geom_col." FROM ".$this->db->tablePrefix."iyo_data_".$centeron[1]." cto1) as layer".$n;
					}
					else
					{
						$sql = "(SELECT * FROM ".$this->db->tablePrefix."iyo_data_".count($qid) > 0?$qid[1]:$did.") as layer".$n;
					}
					
				}
				else
				{
					$sql = "(SELECT * FROM ".$this->db->tablePrefix."iyo_data_".$did.") as layer".$n;
				}
				$table = $data->addChild('Parameter',$sql);
				$table->addAttribute('name','table');
				
				$geom = $data->addChild('Parameter',$geom_col);
				$geom->addAttribute('name','geometry_field');
				
				$srid = $data->addChild('Parameter','EPSG:'.$lsrid);
				$srid->addAttribute('name','srid');
				
				if ($layer == $slayer)
				{
					$fields = 'gid';
					foreach ($config['fields'] as $f)
					{
						$fields .= ($fields == ''?'':',').$f['name'];
					}	
					
					$field = $data->addChild('Parameter',$fields);
					$field->addAttribute('name','fields');
					
					if (isset($config['resolution']))
					{
						$resolution = $data->addChild('Parameter',$config['resolution']);
						$resolution->addAttribute('name','resolution');								
					}	
					
				}
			}				
		}
				
		$xml = $map->asXML();			
		$xml = preg_replace('/\<\?xml (.*)\?\>\n/','',$xml);			
		
		return $xml;
    }
    
    public function printXml()
    {
		echo $this->getXml();	
	}
		
}
