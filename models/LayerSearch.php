<?php

namespace amilna\iyo\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use amilna\iyo\models\Layer;

/**
 * LayerSearch represents the model behind the search form about `amilna\iyo\models\Layer`.
 */
class LayerSearch extends Layer
{

	
	/*public $iyolaydatId;*/
	/*public $authorId;*/
	/*public $iyomaplayId;*/

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'data_id', 'author_id', 'type', 'status', 'isdel'], 'integer'],
            [['title', 'description', 'remarks', 'config', 'tags', 'time'/*, 'iyolaydatId', 'authorId', 'iyomaplayId'*/], 'safe'],
        ];
    }

	/* uncomment to undisplay deleted records (assumed the table has column isdel) */
	public static function find()
	{
		return parent::find()->where([Layer::tableName().'.isdel' => 0]);
	}
	

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
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
        
                
        $query->joinWith([/*'iyolaydat', 'author', 'iyomaplay'*/]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);
        
        /* uncomment to sort by relations table on respective column
		$dataProvider->sort->attributes['iyolaydatId'] = [			
			'asc' => ['{{%iyolaydat}}.id' => SORT_ASC],
			'desc' => ['{{%iyolaydat}}.id' => SORT_DESC],
		];
		$dataProvider->sort->attributes['authorId'] = [			
			'asc' => ['{{%author}}.id' => SORT_ASC],
			'desc' => ['{{%author}}.id' => SORT_DESC],
		];
		$dataProvider->sort->attributes['iyomaplayId'] = [			
			'asc' => ['{{%iyomaplay}}.id' => SORT_ASC],
			'desc' => ['{{%iyomaplay}}.id' => SORT_DESC],
		];*/

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }				
		
        $params = self::queryNumber([['id',$this->tableName()],['data_id'],['author_id'],['type'],['status'],['isdel']/*['id','{{%iyolaydat}}'],['id','{{%author}}'],['id','{{%iyomaplay}}']*/]);
		foreach ($params as $p)
		{
			$query->andFilterWhere($p);
		}
        $params = self::queryString([['title'],['description'],['remarks'],['config'],['tags']/*['id','{{%iyolaydat}}'],['id','{{%author}}'],['id','{{%iyomaplay}}']*/]);
		foreach ($params as $p)
		{
			$query->andFilterWhere($p);
		}
        $params = self::queryTime([['time']/*['id','{{%iyolaydat}}'],['id','{{%author}}'],['id','{{%iyomaplay}}']*/]);
		foreach ($params as $p)
		{
			$query->andFilterWhere($p);
		}		
		/* example to use search all in field1,field2,field3 or field4
		if ($this->term)
		{
			$query->andFilterWhere(["OR","lower(field1) like '%".strtolower($this->term)."%'",
				["OR","lower(field2) like '%".strtolower($this->term)."%'",
					["OR","lower(field3) like '%".strtolower($this->term)."%'",
						"lower(field4) like '%".strtolower($this->term)."%'"						
					]
				]
			]);	
		}	
		*/

        return $dataProvider;
    }
}
