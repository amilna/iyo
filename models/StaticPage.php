<?php

namespace amilna\iyo\models;

use Yii;

/**
 * This is the model class for table "{{%blog_static}}".
 *
 * @property integer $id
 * @property string $title
 * @property string $description
 * @property string $content
 * @property string $tags
 * @property integer $status
 * @property string $time
 * @property integer $isdel
 */
class StaticPage extends \amilna\iyo\StaticPage
{
    
	public function itemAlias($list,$item = false,$bykey = false)
	{
		$lists = [
			/* example list of item alias for a field with name field */			
			'status'=>[													
						3=>Yii::t('app','Draft'),							
						4=>Yii::t('app','Published'),
						5=>Yii::t('app','Archived'),
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
        
}
