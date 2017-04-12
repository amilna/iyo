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
						6=>'Raster',														
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
		if ($res);
		{			
			$module = Yii::$app->getModule('iyo');
			$uploadDir = \Yii::getAlias($module->uploadDir).'/'.$module->geosDir.'/';			
			$tileDir = \Yii::getAlias($module->tileDir);
			$geom_col = $module->geom_col;				
			
			$pid = !empty($this->pid)?intval($this->pid):false;			
			if ($pid)
			{				
				$oldprocess = new Process();
				$oldprocess->setPid($pid);
				$oldprocess->stop();
			}
			
			$dsn = $this->db->dsn;
			$tablePrefix = $this->db->tablePrefix;
			$username = $this->db->username;
			$password = $this->db->password;				
			
			$param = $uploadDir."~".$tileDir."~".$geom_col."~".$this->id."~".Yii::$app->user->id."~".$module->postgis."~".$module->gdalinfo."~".$module->shp2pgsql."~".Yii::getAlias('@runtime');
			$path = \Yii::getAlias("@amilna/iyo/components");			
			$execFile = \Yii::getAlias($module->execFile);			
			$cmd = $module->php.' "'.$execFile.'" -action="import" -dsn="'.$dsn.'" -tablePrefix="'.$tablePrefix.'" -username="'.$username.'" -password="'.$password.'" -param="'.$param.'"';
			 
			//die($cmd);			
			$process = new Process($cmd);
						
			$pid = $process->getPid();
			
			if (!empty($pid))
			{
				$sql = "UPDATE ".Data::tableName()." 
					SET pid = ".$pid."
					WHERE id = ".$this->id;
					
				$res = $this->db->createCommand($sql)->execute();			
			}
			
			/* generate layer if raster */
			if ($this->type == 6)
			{
				$lay = Layer::find()->where(['data_id'=>$this->id])->one();
				if (!$lay)
				{
					$lay = new Layer();	
					$lay->data_id = $this->id;
					$lay->title = $this->title;
					$lay->description = $this->description;
					$lay->remarks = $this->remarks;
					$lay->config = '{}';
					$lay->tags = $this->tags;
					$lay->author_id = Yii::$app->user->id;
					$lay->type = 0;
					$lay->status = 1;
					$lay->isdel = 0;
					$lay->save();
				}
				
				$path = $uploadDir;
				$path = $path."/geos/";	
								
				$metadata = json_decode($this->metadata,true);		
				$filename = isset($metadata["srcfile"])?$metadata["srcfile"]:false;														
				if ($filename)
				{
					$filename = str_replace('%20',' ',$filename);
					$file = $path.$filename;
															
					$file = \amilna\yap\Helpers::shellvar($file);		
					
					$gdalinfo = shell_exec($module->gdalinfo." '".$file."'");						
					preg_match('/Lower Left([ ]+)\(([ ]+)?(-?[0-9\.]+),([ ]+)?(-?[0-9\.]+)\)/', $gdalinfo, $min);		
					preg_match('/Upper Right([ ]+)\(([ ]+)?(-?[0-9\.]+),([ ]+)?(-?[0-9\.]+)\)/', $gdalinfo, $max);
					preg_match('/\n    AUTHORITY\[\"EPSG\",\"(\d+)\"\]/', $gdalinfo, $epsg);																
					
					if (isset($min[3]) && isset($min[5]) && isset($max[3]) && isset($max[5]))
					{						
						if (!isset($epsg[1]))
						{
							$epsg[1] = '4326';
						}
						$bbox = $min[3].','.$min[5].','.$max[3].','.$max[5];						
						
						$bbox = $this->db->createCommand('SELECT ST_Extent(ST_Transform(ST_MakeEnvelope('.$bbox.','.$epsg[1].'),4326)) as bbox')->queryScalar();
						$bbox = preg_replace('/BOX\((-?[0-9\.]+) (-?[0-9\.]+),(-?[0-9\.]+) (-?[0-9\.]+)\)/','$1,$2,$3,$4',$bbox);						
						
						$tilep = new \amilna\iyo\components\Tilep();
						$clear = $tilep->clearTile($this->id,true,true,$bbox);
					}	
				}	
			}
			else
			{
				$tilep = new \amilna\iyo\components\Tilep();
				$clear = $tilep->clearTile($this->id,true,true);
			}
			
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
