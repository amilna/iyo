<?php

namespace amilna\iyo\models;

use Yii;

/**
 * This is the model class for table "{{%iyo_map}}".
 *
 * @property integer $id
 * @property string $title
 * @property string $description
 * @property string $remarks
 * @property string $config
 * @property string $tags
 * @property integer $author_id
 * @property integer $status
 * @property string $time
 * @property integer $isdel
 *
 * @property IyoCatMap[] $iyoCatMaps
 * @property User $author
 * @property IyoMapDat[] $iyoMapDats
 */
class Map extends \yii\db\ActiveRecord
{
    public $dynTableName = '{{%iyo_map}}';
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {        
        $mod = new Map();        
        return $mod->dynTableName;
    }
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title', 'description', 'status', 'config'], 'required'],
            [['remarks', 'config'], 'string'],
            [['author_id', 'status', 'isdel'], 'integer'],
            [['time','tags'], 'safe'],
            [['title'], 'string', 'max' => 65],
            [['description'], 'string', 'max' => 155],
            //[['tags'], 'string', 'max' => 255]
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
            'config' => Yii::t('app', 'Config'),
            'tags' => Yii::t('app', 'Tags'),
            'author_id' => Yii::t('app', 'Author ID'),
            'status' => Yii::t('app', 'Status'),
            'time' => Yii::t('app', 'Time'),
            'isdel' => Yii::t('app', 'Isdel'),
        ];
    }	
    
	public function itemAlias($list,$item = false,$bykey = false)
	{
		$lists = [
			/* example list of item alias for a field with name field */	
			'status'=>[							
							-1=>Yii::t('app','Private'),
							0=>Yii::t('app','Draft'),							
							1=>Yii::t('app','Available'),
							2=>Yii::t('app','Featured'),
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
    public function getCatMap()
    {
        return $this->hasMany(CatMap::className(), ['map_id' => 'id']);
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
    public function getMapLay()
    {
        return $this->hasMany(MapLay::className(), ['map_id' => 'id']);
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
	
	
	public function beforeSave($insert)
	{
		if (parent::beforeSave($insert)) {
			$this->config = str_replace('&amp;','&',$this->config);			
			return true;
		} else {
			return false;
		}
	}   
}
