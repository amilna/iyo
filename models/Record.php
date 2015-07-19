<?php

namespace amilna\iyo\models;

use Yii;
use yii\db\Schema;

/**
 * This is the model class for geometry tables. 
 */
class Record extends \yii\db\ActiveRecord
{
    public static $dynTableName;
    public static $dataId;
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {                
        return self::$dynTableName;
    }        
    
    public function __construct($dataId = false)
	{
		if ($dataId)
		{
			self::$dataId = $dataId;		
			$prefix = Yii::$app->db->tablePrefix;
			
			$go = false;
			$data = Data::findOne($dataId);
			if ($data)
			{
				if (in_array($data->status,[1,3]))
				{
					$go = true;	
				}
			}						
			
			if (!$go)
			{
				//if (!$searchModel)
				//{
					return Yii::$app->controller->redirect(['//iyo/data/index']);
				//}
			}
					
			$table = $prefix.str_replace(["{{%","}}"],"",Data::tableName())."_".$dataId;
			Yii::$app->session->set('RecordTable',$table);
			Yii::$app->session->set('RecordDataId',$dataId);
		}
		else
		{
			if (Yii::$app->session->has('RecordTable'))
			{
				$table = Yii::$app->session->get('RecordTable');				
			}
		}				
		self::$dynTableName = $table;		
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
				$rules[] = [$columns,'time', 'format' => 'yyyy-M-d H:m:s'];
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
		return $this->db->createCommand(
			"SELECT ST_AsGeoJSON(".$geom_col.",4,0) as geojson FROM ".(self::$dynTableName)." WHERE gid = :gid"
		)->bindValues([":gid"=>$this->gid])->queryScalar();
		
	}
    
    
	public function getData()
    {
        if (Yii::$app->session->has('RecordDataId'))
		{
			$dataId = Yii::$app->session->get('RecordDataId');				
		}
		else
		{
			$tbl = $this->tableName();        
			preg_match('/_(\d+)/', $tbl, $matches);        
			$dataId = $matches[1];
		}
        return Data::findOne($dataId);
    }
        

}
