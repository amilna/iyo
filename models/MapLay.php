<?php

namespace amilna\iyo\models;

use Yii;

/**
 * This is the model class for table "{{%iyo_map_dat}}".
 *
 * @property integer $map_id
 * @property integer $data_id
 * @property integer $isdel
 *
 * @property IyoData $data
 * @property IyoMap $map
 */
class MapLay extends \yii\db\ActiveRecord
{
    public $dynTableName = '{{%iyo_map_lay}}';
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {        
        $mod = new MapLay();        
        return $mod->dynTableName;
    }
        
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['map_id', 'layer_id'], 'required'],
            [['map_id', 'layer_id', 'isdel'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'map_id' => Yii::t('app', 'Map ID'),
            'layer_id' => Yii::t('app', 'Layer ID'),
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
    public function getLayer()
    {
        return $this->hasOne(Data::className(), ['id' => 'layer_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMap()
    {
        return $this->hasOne(Map::className(), ['id' => 'map_id']);
    }
}
