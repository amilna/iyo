<?php

namespace amilna\iyo\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use amilna\iyo\models\Record;
use yii\db\Schema;

/**
 * RecordSearch represents the model behind the search form about `amilna\iyo\models\Data`.
 */
class RecordSearch extends Record
{
	
	public $term;	

    /**
     * @inheritdoc
     */
    public function rules()
    {
		$module = Yii::$app->getModule('iyo');
		$geom_col = $module->geom_col;
		
		$table = self::getTableSchema();
		$rules = [['term'],'safe'];        
        foreach ($table->columns as $column) {
            
            if (!in_array($column->name,[$geom_col]))
            {
				$rules[0][] = $column->name;				
			}
        }
               
        return [$rules];
    }

	/* uncomment to undisplay deleted records (assumed the table has column isdel)
	public static function find()
	{
		return parent::find()->where([Data::tableName().'.isdel' => 0]);
	}
	*/

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        //print_r($this);
        //print_r($this->rules());
        //die();
        return Model::scenarios();
    }

	private function queryString($fields)
	{		
		$params = [];
		foreach ($fields as $afield)
		{
			$field = $afield[0];
			$tab = isset($afield[1])?$afield[1]:false;			
			if (!empty($this->$field))
			{				
				if (substr($this->$field,0,2) == "< " || substr($this->$field,0,2) == "> " || substr($this->$field,0,2) == "<=" || substr($this->$field,0,2) == ">=" || substr($this->$field,0,2) == "<>") 
				{					
					array_push($params,[str_replace(" ","",substr($this->$field,0,2)), "lower(".($tab?$tab.".":"").$field.")", strtolower(trim(substr($this->$field,2)))]);
				}
				else
				{					
					array_push($params,["like", "lower(".($tab?$tab.".":"").$field.")", strtolower($this->$field)]);
				}				
			}
		}	
		return $params;
	}	
	
	private function queryNumber($fields)
	{		
		$params = [];
		foreach ($fields as $afield)
		{
			$field = $afield[0];
			$tab = isset($afield[1])?$afield[1]:false;			
			if (!empty($this->$field))
			{				
				$number = explode(" ",trim($this->$field));							
				if (count($number) == 2)
				{									
					if (in_array($number[0],['>','>=','<','<=','<>']) && is_numeric($number[1]))
					{
						array_push($params,[$number[0], ($tab?$tab.".":"").$field, $number[1]]);	
					}
				}
				elseif (count($number) == 3)
				{															
					if (is_numeric($number[0]) && is_numeric($number[2]))
					{
						array_push($params,['>=', ($tab?$tab.".":"").$field, $number[0]]);		
						array_push($params,['<=', ($tab?$tab.".":"").$field, $number[2]]);		
					}
				}
				elseif (count($number) == 1)
				{					
					if (is_numeric($number[0]))
					{
						array_push($params,['=', ($tab?$tab.".":"").$field, str_replace(["<",">","="],"",$number[0])]);		
					}	
				}
			}
		}	
		return $params;
	}
	
	private function queryTime($fields)
	{		
		$params = [];
		foreach ($fields as $afield)
		{
			$field = $afield[0];
			$tab = isset($afield[1])?$afield[1]:false;			
			if (!empty($this->$field))
			{				
				$time = explode(" - ",$this->$field);			
				if (count($time) > 1)
				{								
					array_push($params,[">=", "concat('',".($tab?$tab.".":"").$field.")", $time[0]]);	
					array_push($params,["<=", "concat('',".($tab?$tab.".":"").$field.")", $time[1]." 24:00:00"]);
				}
				else
				{
					if (substr($time[0],0,2) == "< " || substr($time[0],0,2) == "> " || substr($time[0],0,2) == "<=" || substr($time[0],0,2) == ">=" || substr($time[0],0,2) == "<>") 
					{					
						array_push($params,[str_replace(" ","",substr($time[0],0,2)), "concat('',".($tab?$tab.".":"").$field.")", trim(substr($time[0],2))]);
					}
					else
					{					
						array_push($params,["like", "concat('',".($tab?$tab.".":"").$field.")", $time[0]]);
					}
				}	
			}
		}	
		return $params;
	}

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = $this->find();                

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);
        
        $module = Yii::$app->getModule('iyo');
        
        $userClass = $module->userClass;                        
		$geom_col = $module->geom_col;
		
		$table = self::getTableSchema();		
		$types = [];        
        foreach ($table->columns as $column) {
            
            if ($column->name != $geom_col)
            {
				$dataProvider->sort->attributes[$column->name] = [			
					'asc' => [$column->name => SORT_ASC],
					'desc' => [$column->name => SORT_DESC],
				];
			}
			
			$cname = $column->name;
			switch ($column->type) {
				case Schema::TYPE_SMALLINT:
				case Schema::TYPE_INTEGER:
				case Schema::TYPE_BIGINT:
					$types['number'][] = [$column->name];
					break;
				case Schema::TYPE_BOOLEAN:
					$types['boolean'][] = [$column->name => $this->$cname];
					break;
				case Schema::TYPE_FLOAT:
				case Schema::TYPE_DECIMAL:
				case Schema::TYPE_MONEY:
					$types['number'][] = [$column->name];
					break;
				case Schema::TYPE_DATE:
				case Schema::TYPE_TIME:
				case Schema::TYPE_DATETIME:
				case Schema::TYPE_TIMESTAMP:
					$types['time'][] = [$column->name];
					break;
				default: // strings
					$types['string'][] = [$column->name];					
			}
						
        }                
        		
        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }
        
        if (isset($types['boolean']))
        {
			$query->andFilterWhere($types['boolean']);				
		}
		
		if (isset($types['number']))
        {
			$params = self::queryNumber($types['number']);
			foreach ($params as $p)
			{
				$query->andFilterWhere($p);
			}
		}
		
		if (isset($types['string']))
        {
			$params = self::queryString($types['string']);
			foreach ($params as $p)
			{
				$query->andFilterWhere($p);
			}
			
			$str = "";
			foreach ($types['string'] as $s)
			{
				if ($s[0] != $geom_col)
				{
					$str .= ($str == ""?"":",").'"'.$s[0].'"';
				}
			}
							
			$query->andFilterWhere(["like","lower(concat($str))",strtolower($this->term)]);
		}
		
		if (isset($types['time']))
        {
			$params = self::queryTime($types['time']);
			foreach ($params as $p)
			{
				$query->andFilterWhere($p);
			}
		}										

        return $dataProvider;
    }
}
