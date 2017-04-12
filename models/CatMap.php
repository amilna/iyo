<?php

namespace amilna\iyo\models;

use Yii;

/**
 * This is the model class for table "{{%iyo_cat_map}}".
 *
 * @property integer $category_id
 * @property integer $map_id
 * @property integer $isdel
 *
 * @property IyoCategory $category
 * @property IyoMap $map
 */
class CatMap extends \yii\db\ActiveRecord
{
    public $dynTableName = '{{%iyo_cat_map}}';
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {        
        $mod = new CatMap();        
        return $mod->dynTableName;
    }    

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['category_id', 'map_id'], 'required'],
            [['category_id', 'map_id', 'isdel'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'category_id' => Yii::t('app', 'Category ID'),
            'map_id' => Yii::t('app', 'Map ID'),
            'isdel' => Yii::t('app', 'Isdel'),
        ];
    }	
    
	public function itemAlias($list,$item = false,$bykey = false)
	{
		$lists = [
			/* example list of item alias for a field with name field
			'afield'=>[							
							0=>Yii::t('app','an alias of 0'),							
							1=>Yii::t('app','an alias of 1'),														
						],			
			*/			
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
    public function getCategory()
    {
        return $this->hasOne(MapCategory::className(), ['id' => 'category_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMap()
    {
        return $this->hasOne(Map::className(), ['id' => 'map_id']);
    }
}
