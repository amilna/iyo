<?php

namespace amilna\iyo\models;

use Yii;

/**
 * This is the model class for table "{{%iyo_lay_dat}}".
 *
 * @property integer $layer_id
 * @property integer $data_id
 * @property integer $isdel
 *
 * @property IyoData $data
 * @property IyoLayer $layer
 */
class LayDat extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%iyo_lay_dat}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['layer_id', 'data_id'], 'required'],
            [['layer_id', 'data_id', 'isdel'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'layer_id' => Yii::t('app', 'Layer ID'),
            'data_id' => Yii::t('app', 'Data ID'),
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
    public function getData()
    {
        return $this->hasOne(IyoData::className(), ['id' => 'data_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLayer()
    {
        return $this->hasOne(IyoLayer::className(), ['id' => 'layer_id']);
    }
}
