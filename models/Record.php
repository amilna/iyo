<?php

namespace amilna\iyo\models;

use Yii;
use yii\db\Schema;

/**
 * This is the model class for geometry tables. 
 */
class Record extends \yii\db\ActiveRecord
{
    public static $dataId = false;
    
    public function __construct($id = false, array $config = [])
    {        	
		if (is_numeric($id))
		{							
			$data = Data::find()->where('id=:id AND type < 6 ',[':id'=>$id])->one();			
			if ($data)
			{
				static::$dataId = $id;					
			}						
		}										
							
		parent::__construct($config);		
    }
    
    public static function tableName()
    {
        					        
        if (static::$dataId) {
			return '{{%'.str_replace(["{{%","}}"],"",Data::tableName())."_".static::$dataId.'}}';
		}	
		else		
		{			
			$url = \yii\helpers\Url::toRoute(['//iyo/data/index']);
			header( "Location: $url" );						
			die();			
		}	        
    }        
    
    public static function find($id = false)
	{		
		if ($id)
		{
			static::$dataId = $id;
		}
		return Yii::createObject(\yii\db\ActiveQuery::className(), [get_called_class(), ['from' => [static::tableName()]]]);
	}        

    /**
     * @inheritdoc
     */
    public function rules()
    {               		
		$module = Yii::$app->getModule('iyo');
		$geom_col = $module->geom_col;
		
		$table = self::getTableSchema();
		$types = [];
        $lengths = [];
        $dates = [];
        foreach ($table->columns as $column) {
            
            if ($column->name == $geom_col)
            {
				$types['safe'][] = $column->name;				
			}
			else
			{            
				if ($column->autoIncrement) {
					continue;
				}
				if (!$column->allowNull && $column->defaultValue === null) {
					$types['required'][] = $column->name;
				}
				switch ($column->type) {
					case Schema::TYPE_SMALLINT:
					case Schema::TYPE_INTEGER:
					case Schema::TYPE_BIGINT:
						$types['integer'][] = $column->name;
						break;
					case Schema::TYPE_BOOLEAN:
						$types['boolean'][] = $column->name;
						break;
					case Schema::TYPE_FLOAT:
					case Schema::TYPE_DOUBLE:
					case Schema::TYPE_DECIMAL:
					case Schema::TYPE_MONEY:
						$types['number'][] = $column->name;
						break;
					case Schema::TYPE_DATE:
					case Schema::TYPE_TIME:
					case Schema::TYPE_DATETIME:
					case Schema::TYPE_TIMESTAMP:
						$types['safe'][] = $column->name;
						$dates[$column->type][] = $column->name;
						break;
					default: // strings
						if ($column->size > 0) {
							$lengths[$column->size][] = $column->name;
						} else {
							$types['string'][] = $column->name;
						}
				}
			}
        }                
        
        $rules = [];
        foreach ($types as $type => $columns) {            
            $rules[] = [$columns,$type];
        }
        foreach ($lengths as $length => $columns) {            
            $rules[] = [$columns,'string', 'max' => $length];
        }
        foreach ($dates as $ctype => $columns) {            
			if ($ctype == Schema::TYPE_DATE)
			{
				$rules[] = [$columns,'date', 'format' => 'yyyy-M-d'];
			}
			else
			{
				//$rules[] = [$columns,'date', 'format' => 'php:Y-M-d H:i:s'];
			}
        }
		
        // Unique indexes rules
        try {
            $db = $this->db;
            $uniqueIndexes = $db->getSchema()->findUniqueIndexes($table);
            foreach ($uniqueIndexes as $uniqueColumns) {
                // Avoid validating auto incremental columns
                if (!$this->isColumnAutoIncremental($table, $uniqueColumns)) {
                    $attributesCount = count($uniqueColumns);

                    if ($attributesCount == 1) {                        
                        $rules[] = [$uniqueColumns[0],'unique'];                        
                    } elseif ($attributesCount > 1) {
                        $labels = array_intersect_key($this->generateLabels($table), array_flip($uniqueColumns));
                        $lastLabel = array_pop($labels);
                        $columnsList = implode("', '", $uniqueColumns);
                        $rules[] = [$columnsList, 'unique', 'targetAttribute' => [$columnsList], 'message' => 'The combination of ' . implode(', ', $labels) . " and " . $lastLabel . ' has already been taken.'];                        
                    }
                }
            }
        } catch (NotSupportedException $e) {
            // doesn't support unique indexes information...do nothing
        }                        
        
        return $rules;        
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        $metadata = json_decode($this->data->metadata,true);        
        return isset($metadata["attributeLabels"])?$metadata["attributeLabels"]:[];        
    }	
    
	public function itemAlias($list,$item = false,$bykey = false)
	{
		$metadata = json_decode($this->data->metadata,true);        
        $lists = isset($metadata["itemAlias"])?$metadata["itemAlias"]:[];				
		
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
    
    public function getTitle()
    {
        $table = self::getTableSchema();
		$title = $this->gid;        
        foreach ($table->columns as $column) {
            
            if ($title == $this->gid && $column->phpType == 'string')
            {
				$atr = $column->name;
				if (!empty($this->$atr))
				{
					$title = $this->$atr;
				}
			}
		}	
                
        return $title;
    }
    
    public function getGeojson()
    {
		
		$module = Yii::$app->getModule('iyo');
		$geom_col = $module->geom_col;
		
		return static::getGeomGeojson($this->$geom_col);
		
	}
	
	public static function getGeomGeojson($geom)
    {		
		if (empty($geom))
		{
			return null;	
		}
		
		return Yii::$app->db->createCommand(
			"SELECT ST_AsGeoJSON(ST_Transform(cast('".$geom."' as geometry),4326),4,0) as geojson"
		)->queryScalar();
		
	}
	
	public static function asGeojson($data,$isfeature = false)
    {
		$module = Yii::$app->getModule('iyo');
		$geom_col = $module->geom_col;		
		
		if (is_array($data) && !$isfeature)
		{
			$features = [];
			foreach ($data as $d)
			{								
				if (is_array($d) || isset($d->attributes))
				{
					$features[] = static::asGeojson($d,true);				
				}
			}	
			
			return [
					"type"=> "FeatureCollection",
					"crs"=>[
						"type"=> "name",
						"properties"=> [
							"name"=> "EPSG:4326"
						]
					],
					"features"=>$features
				];
		}
		else
		{						
			
			if (isset($data->attributes))
			{
				$properties = $data->attributes;
				$geom = $data->geojson;
				unset($properties[$geom_col]);
				
			}
			else
			{
				$properties = $data;				
				$geom = static::getGeomGeojson($data[$geom_col]);
				unset($properties[$geom_col]);
			}
			
			return [
						"type"=> "Feature",
						"properties"=> $properties,
						"geometry"=> json_decode($geom)
					];		
		}			
		
	}
        
	public function getData()
    {        		
        return Data::findOne(static::$dataId);
    }
        

}
