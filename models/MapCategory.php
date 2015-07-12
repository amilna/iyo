<?php

namespace amilna\iyo\models;

use Yii;

/**
 * This is the model class for table "{{%iyo_category}}".
 *
 * @property integer $id
 * @property string $title
 * @property integer $parent_id
 * @property string $description
 * @property string $image
 * @property boolean $status
 * @property integer $isdel
 *
 * @property IyoCatMap[] $iyoCatMaps
 * @property MapCategory $parent
 * @property MapCategory[] $mapCategories
 */
class MapCategory extends \yii\db\ActiveRecord
{
    public $dynTableName = '{{%iyo_category}}';
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {        
        $mod = new MapCategory();        
        return $mod->dynTableName;
    }
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title', 'description'], 'required'],
            [['parent_id', 'isdel'], 'integer'],
            [['description'], 'string'],
            [['status'], 'boolean'],
            [['title'], 'string', 'max' => 65],
            [['image'], 'string', 'max' => 255],
            [['title'], 'unique']
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
            'parent_id' => Yii::t('app', 'Parent ID'),
            'description' => Yii::t('app', 'Description'),
            'image' => Yii::t('app', 'Image'),
            'status' => Yii::t('app', 'Status'),
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
    public function getCatMap()
    {
        return $this->hasMany(CatMap::className(), ['category_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        return $this->hasOne(MapCategory::className(), ['id' => 'parent_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMapCategories()
    {
        return $this->hasMany(MapCategory::className(), ['parent_id' => 'id']);
    }
}
