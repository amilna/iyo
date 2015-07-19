<?php

namespace amilna\iyo\models;

use Yii;
use amilna\iyo\components\FormatData;
use amilna\iyo\components\Process;

/**
 * This is the model class for table "{{%iyo_data}}".
 *
 * @property integer $id
 * @property string $title
 * @property string $description
 * @property string $remarks
 * @property string $metadata
 * @property string $tags
 * @property integer $author_id
 * @property integer $type
 * @property integer $status
 * @property string $time
 * @property integer $isdel
 *
 * @property User $author
 * @property IyoMapDat[] $iyoMapDats
 */
class Data extends \yii\db\ActiveRecord
{
    public $dynTableName = '{{%iyo_data}}';
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {        
        $mod = new Data();        
        return $mod->dynTableName;
    }    

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title', 'description', 'metadata'], 'required'],
            [['remarks', 'metadata'], 'string'],
            [['author_id', 'srid', 'type', 'status', 'isdel', 'pid'], 'integer'],
            [['time','tags'], 'safe'],
            [['title'], 'string', 'max' => 65],
            [['description'], 'string', 'max' => 155]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'title' => Yii::t('app', 'Title'),
            'description' => Yii::t('app', 'Description'),
            'remarks' => Yii::t('app', 'Remarks'),
            'metadata' => Yii::t('app', 'Metadata'),
            'tags' => Yii::t('app', 'Tags'),
            'author_id' => Yii::t('app', 'Author ID'),
            'type' => Yii::t('app', 'Type'),
            'status' => Yii::t('app', 'Status'),
            'time' => Yii::t('app', 'Time'),
            'isdel' => Yii::t('app', 'Isdel'),
            'srid' => Yii::t('app', 'SRID'),
        ];
    }	
    
	public function itemAlias($list,$item = false,$bykey = false)
	{
		$lists = [
			/* example list of item alias for a field with name field */
						
			'geomtype'=>[							
						0=>'Point',							
						1=>'LineString',														
						2=>'Polygon',														
						3=>'MultiPoint',							
						4=>'MultiLineString',														
						5=>'MultiPolygon',														
					],		
			'status'=>[							
						0=>Yii::t('app','not ready'),
						1=>Yii::t('app','ready'),														
						2=>Yii::t('app','importing process'),
						3=>Yii::t('app','ready but no topology'),
						4=>Yii::t('app','building topology'),
						5=>Yii::t('app','file not exists'),
					],		
					
		];				
		
		if (isset($lists[$list]))
		{					
			if ($bykey)
			{				
				$nlist = [];
				foreach ($lists[$list] as $k=>$i)
				{
					$nlist[$i] = $k;
				}
				$list = $nlist;				
			}
			else
			{
				$list = $lists[$list];
			}
							
			if ($item !== false)
			{			
				return	(isset($list[$item])?$list[$item]:false);
			}
			else
			{
				return $list;	
			}			
		}
		else
		{
			return false;	
		}
	}    
    

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAuthor()
    {
        $userClass = Yii::$app->getModule('iyo')->userClass;
        return $this->hasOne($userClass::className(), ['id' => 'author_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLayDat()
    {
        return $this->hasMany(LayDat::className(), ['data_id' => 'id']);
    }        
    
    public function getRecord($sql = "")
    {        
        $record = new Record($this->id);
        return $record->find()->where($sql)->all();                
    }
    
    public function afterSave($insert, $changedAttributes)
    {				
		$res = true;
		//if ($insert)		
		if (true);
		{			
			$module = Yii::$app->getModule('iyo');
			$uploadDir = \Yii::getAlias($module->uploadDir);
			$tileDir = \Yii::getAlias($module->tileDir);
			$geom_col = $module->geom_col;				
			
			$pid = !empty($this->pid)?intval($this->pid):false;			
			if ($pid)
			{				
				$oldprocess = new Process();
				$oldprocess->setPid($pid);
				$oldprocess->stop();
			}
			
			/*
			$prefix = $this->db->tablePrefix;
			$table = $prefix.str_replace(["{{%","}}"],"",Data::tableName())."_".$this->id;			
			$res = FormatData::mkData($table,$geom_col,$this->type);						
			$cols = isset($this->metadata["columns"])?$this->metadata["columns"]:[];			
			foreach ($cols as $col)
			{									
				$res0 = FormatData::mkColumn($table,$col,$geom_col);
				$res = ($res == false?false:$res0);
			}
			*/ 			
			
			$dsn = $this->db->dsn;
			$tablePrefix = $this->db->tablePrefix;
			$username = $this->db->username;
			$password = $this->db->password;				
			
			$param = $uploadDir.":".$tileDir.":".$geom_col.":".$this->id.":".Yii::$app->user->id;
			$path = \Yii::getAlias("@amilna/iyo/components");			
			$cmd = $path."/exec -action='import' -dsn='".$dsn."' -tablePrefix='".$tablePrefix."' -username='".$username."' -password='".$password."' -param='".$param."'";
			//die($cmd);			
			$process = new Process($cmd);
			
			$sql = "UPDATE ".Data::tableName()." 
				SET pid = ".$process->getPid()."
				WHERE id = ".$this->id;
				
			$res = $this->db->createCommand($sql)->execute();			
		}
		
		if (!$res)
		{
			return false;
		}
		else
		{
			parent::afterSave($insert, $changedAttributes);
		}
	}
	

	
	
	/**
     * Do import file into postgis geometry.
     * If import is successful, it will redirect to data view.
     * @param string $filename
     * @return boolean
     */
    private function importFile($filename = false)
    {        		
		
		$ext = Data::checkFileExt($filename);
		if ($ext)
		{						
			$module = Yii::$app->getModule('iyo');
			$path = \Yii::getAlias($module->uploadDir);
			$path = $path."/geos/";
			$db = Yii::$app->db;
			$dsn = $db->dsn;
			preg_match('/dbname\=(.*)?/', $db->dsn, $matches);			
			$dbname = $matches[1];
			$prefix = $db->tablePrefix;
			$dataid = $this->id;
			
			
			
			
			$file = $path.$filename;
			
			
						
			if (file_exists($file) && $ext == ".shp")
			{											
				$proj4 = shell_exec("gdalsrsinfo -o proj4 '".$file."'");
				$ogrinfo = shell_exec("ogrinfo '".$file."' '".basename($file,".shp")."' -so");					
					
				$srid = "";
				if (strpos($proj4,"ERROR") === false)
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
					$ogrinfo = shell_exec("ogrinfo '".$file."' '".basename($file,".shp")."' -so");								
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
				
				preg_match('/Geometry\:(.*)/', $ogrinfo, $matches);
				if (isset($matches[1]))
				{
					$geom = strtolower(trim($matches[1]));				
					$geoms = $this->itemAlias("geomtype");
					$geomtype = false;
					
					foreach ($geoms as $g=>$gt)
					{
						$gt= strtolower(str_replace(" ","",$gt));						
						if (strpos($gt,$geom) !== false && !$geomtype)
						{
							$geomtype = $g;								
						}						
					}
					
					if ($geomtype)
					{
						$sql = "UPDATE ".Data::tableName()." 
						SET type = ".$geomtype." 
						WHERE id = ".$this->id;
						$updatetype = $this->db->createCommand($sql)->execute();					
					}
				}
				
				$table = $prefix.str_replace(["{{%","}}"],"",Data::tableName())."_".$this->id;
				$filesql = $path.Yii::$app->user->id."_".$table."_".time();
				$shp2pgsql = shell_exec("shp2pgsql -s ".$srid." -W latin1 '".$file."' public.".$table." > ".$filesql);								
				
				$sql = "DROP TABLE IF EXISTS ".$table."";
				$drop = $this->db->createCommand($sql)->execute();
				
				$psql = shell_exec("psql -d ".$dbname." < ".$filesql);
				unlink($filesql);
				
				$sql = "SELECT column_name,data_type,character_maximum_length 
					FROM information_schema.columns 
					WHERE table_name='".$table."'";

				$ext = $this->db->createCommand($sql)->queryAll();
			}
			elseif (file_exists($file) && $ext == ".zip")
			{
				$basefile = preg_replace('/\.(\d+)\.zip/',"",$file);
				
				$session = Yii::$app->session;
				$session["iyo-data-importid"] = Yii::$app->user->id."_".date('U');
				$seqrun = shell_exec("touch ".$path."start_".$session["iyo-data-importid"].";
					cat '".$basefile.".*' > '".$basefile.".zip';
					unzip '".$basefile.".zip' '".$basefile."';				
				");
			}
		}
		
        return $ext;
    }
    
    public function getProcess()
    {
		$process = new Process();
		$process->setPid = $this->pid;
		return $process->status();
	}
	
	public function getTags()
	{
		$models = $this->find()->all();
		$tags = [];
		foreach ($models as $m)
		{
			$ts = explode(",",$m->tags);
			foreach ($ts as $t)
			{	
				if (!in_array($t,$tags))
				{
					$tags[$t] = $t;
				}
			}	
		}
		return $tags;
	}
}
