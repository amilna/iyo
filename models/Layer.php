<?php

namespace amilna\iyo\models;

use Yii;

/**
 * This is the model class for table "{{%iyo_layer}}".
 *
 * @property integer $id
 * @property integer $data_id
 * @property string $title
 * @property string $description
 * @property string $remarks
 * @property string $config
 * @property string $tags
 * @property integer $author_id
 * @property integer $type
 * @property integer $status
 * @property string $time
 * @property integer $isdel
 *
 * @property IyoLayDat[] $iyoLayDats
 * @property User $author
 * @property IyoMapLay[] $iyoMapLays
 */
class Layer extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%iyo_layer}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['data_id', 'author_id', 'type', 'status', 'isdel'], 'integer'],
            [['title', 'type', 'status', 'description', 'config'], 'required'],
            [['remarks', 'config'], 'string'],
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
            'data_id' => Yii::t('app', 'Data ID'),
            'title' => Yii::t('app', 'Title'),
            'description' => Yii::t('app', 'Description'),
            'remarks' => Yii::t('app', 'Remarks'),
            'config' => Yii::t('app', 'Config'),
            'tags' => Yii::t('app', 'Tags'),
            'author_id' => Yii::t('app', 'Author ID'),
            'type' => Yii::t('app', 'Type'),
            'status' => Yii::t('app', 'Status'),
            'time' => Yii::t('app', 'Time'),
            'isdel' => Yii::t('app', 'Isdel'),
        ];
    }	
    
	public function itemAlias($list,$item = false,$bykey = false)
	{
		$lists = [
			/* example list of item alias for a field with name field */	
			'type'=>[							
						0=>Yii::t('app','Data'),							
						//1=>Yii::t('app','TMS'),														
					],			
			'status'=>[							
						0=>Yii::t('app','Draft'),							
						1=>Yii::t('app','Available'),
						2=>Yii::t('app','Private'),
						3=>Yii::t('app','Not Ready'),
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
    public function getLayDat()
    {
        return $this->hasMany(LayDat::className(), ['layer_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAuthor()
    {
        return $this->hasOne(User::className(), ['id' => 'author_id']);
    }
    
    /**
     * @return \yii\db\ActiveQuery
     */
    public function getData()
    {
        return $this->hasOne(Data::className(), ['id' => 'data_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMapLay()
    {
        return $this->hasMany(MapLay::className(), ['layer_id' => 'id']);
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
			$this->config = str_replace(['&amp;','&lt;','&gt;'],['&','<','>'],$this->config);						
			return true;
		} else {
			return false;
		}
	}   
	
	public function afterSave($insert, $changedAttributes)
    {		
		$dids = $this->data_id != null?[$this->data_id]:[];
		$configs = json_decode($this->config,true);
		if ($configs)
		{
			foreach ($configs as $config)
			{
				if (isset($config['dataquery']))
				{
					preg_match('/(\d+)/',$config['dataquery'],$qid);				
					preg_match('/intersect\((\d+)\,(\d+)\)/',$config['dataquery'],$intersect);
					preg_match('/centerOf\((\d+)\)/',$config['dataquery'],$centerof);					
					preg_match('/centerOn\((\d+)\)/',$config['dataquery'],$centeron);
					preg_match('/dissolveBy\((\d+)\,([a-z0-9_]+)\)/',$config['dataquery'],$dissolve);
					
					if (count($qid) > 0)
					{
						if (!in_array(intval($qid[1]),$dids))
						{
							$dids[] = intval($qid[1]);	
						}
					}
					if (count($intersect) > 0)
					{
						if (!in_array(intval($intersect[1]),$dids))
						{
							$dids[] = intval($intersect[1]);	
						}
						if (!in_array(intval($intersect[2]),$dids))
						{
							$dids[] = intval($intersect[2]);	
						}
					}
					if (count($centerof) > 0)
					{
						if (!in_array(intval($centerof[1]),$dids))
						{
							$dids[] = intval($centerof[1]);	
						}
					}
					if (count($centeron) > 0)
					{
						if (!in_array(intval($centeron[1]),$dids))
						{
							$dids[] = intval($centeron[1]);	
						}
					}
					if (count($dissolve) > 0)
					{
						if (!in_array(intval($dissolve[1]),$dids))
						{
							$dids[] = intval($dissolve[1]);	
						}
					}
				}
			}
		}
		
		$sql = "DELETE FROM ".$this->db->tablePrefix."iyo_lay_dat 				
				WHERE layer_id = ".$this->id.";";						
		
		foreach ($dids as $did)
		{
			$sql .= "INSERT INTO ".$this->db->tablePrefix."iyo_lay_dat
				(layer_id,data_id) VALUES (".$this->id.",".$did.");";						
		}		
				
		$res = $this->db->pdo->exec($sql);
		
		$tilep = new \amilna\iyo\components\Tilep();
        $clear = $tilep->clearTile($this->id,false,true);
        
			
		parent::afterSave($insert, $changedAttributes);
	}   
}
