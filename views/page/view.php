<?php

use yii\helpers\Html;
use yii\helpers\HtmlPurifier;
use yii\widgets\ActiveForm;
use yii\widgets\DetailView;
use yii\db\Query;
use amilna\blog\models\Category;

/* @var $this yii\web\View */
/* @var $model amilna\iyo\models\Page */

$this->title = $model->title;
$this->params['breadcrumbs'][] = $this->title;

$this->params['no-content-header'] = true;

	$content = $model->content;	
	preg_match_all('/\{MAP id\:(\d+)\}/',$content,$matches);
	if (count($matches[0]) > 0)
	{
		for ($m=0;$m<count($matches[0]);$m++)
		{
			$id  = $matches[1][$m];
			/*
			$options = Yii::$app->db->createCommand("SELECT config FROM {{%iyo_map}} WHERE id = :id")->bindValues([':id'=>$id])->queryScalar();
			$mapOptions = json_decode($options,true);
			$map = amilna\iyo\widgets\Map::widget(['options'=>$mapOptions]);
			*/ 
			$map = amilna\iyo\widgets\Map::widget(['id'=>$id]);
			$content = preg_replace('/\{MAP id\:'.$id.'\}/',$map,$content);
		}
		$content = preg_replace('/\{DATA id\:(\d+) query\:(.*) var\:([a-zA-Z0-9]+)\}/','',$content);
	}		
												
	echo $content;					
?>

<script>
<?php 
$this->beginBlock('STATIC_SCRIPTS');
	
	$content = $model->content;	
	$content = str_replace(['&lt;','&gt;'],['<','>'],$content);
	preg_match_all('/\{DATA id\:(\d+) query\:(.*) var\:([a-zA-Z0-9]+)\}/',$content,$matches);				
	
	if (count($matches[0]) > 0)
	{		
		for ($m=0;$m<count($matches[0]);$m++)
		{			
			$id = $matches[1][$m];
			$querystr = $matches[2][$m];			
			$var  = $matches[3][$m];						
			
			$json = json_decode($querystr,true);											
				
			if (isset($json['select']) && isset($json['from']))
			{																			
				$fromt = is_array($json['from'])?$json['from']:explode(',',$json['from']);
				$froms = [];
				foreach ($fromt as $n=>$f)
				{
					$allow = true;
					if (!is_numeric($f))
					{
						preg_match_all('/from ([a-zA-Z0-9_\{\}\%]+)/i',$f,$errs);								
						if (count($errs[0]) > 0)
						{		
							for ($e=0;$e<count($errs[0]);$e++)
							{
								$tb = $errs[1][$e];										
								if (preg_replace('/^{{%iyo_data_(\d+)}}$/i','',$tb) == $tb)
								{																						
									$allow = false;
								}																		
							}	
						}	
						else
						{
							if (preg_replace('/^{{%iyo_data_(\d+)}}$/i','',$f) == $f)
							{										
								$allow = false;
							}
						}																
					}																					
					
					if ($allow)
					{
						$froms[] = is_numeric($f)?'{{%iyo_data_'.$f.'}} as t'.($n==0?'':$n):$f.' as t'.($n==0?'':$n);							
					}					
				}
								
				$query = new Query;
				$query->select($json['select'])						
					->from($froms);
				
				if (isset($json['leftJoins']))
				{
					foreach ($json['leftJoins'] as $lj)
					{
						$allow = true;
						if (!is_numeric($lj['table']))
						{
							preg_match_all('/from ([a-zA-Z0-9_\{\}\%]+)/i',$lj['table'],$errs);									
							if (count($errs[0]) > 0)
							{		
								for ($e=0;$e<count($errs[0]);$e++)
								{
									$tb = $errs[1][$e];
									if (preg_replace('/^{{%iyo_data_(\d+)}}$/i','',$tb) == $tb)
									{
										$allow = false;
									}								
								}	
							}	
							else
							{
								if (preg_replace('/^{{%iyo_data_(\d+)}}$/i','',$lj['table']) == $lj['table'])
								{
									$allow = false;
								}
							}								
						}
						
						if ($allow)
						{								
							$query->leftJoin(is_numeric($lj['table'])?'{{%iyo_data_'.$lj['table'].'}} as j'.$lj['table']:$lj['table'],$lj['on'],$lj['params']);
						}							
					}
				}	
				
				if (isset($json['where']))
				{
					$query->where($json['where']['condition'],$json['where']['params']);
				}
				
				if (isset($json['groupBy']))
				{
					$query->groupBy($json['groupBy']);
				}
				
				if (isset($json['orderBy']))
				{
					$query->orderBy($json['orderBy']);
				}
				
				if (isset($json['limit']))
				{
					$query->limit($json['limit']);
				}
				
				if (isset($json['offset']))
				{
					$query->offset($json['offset']);
				}
				
				try {
					$rows = $query->all();									
				} catch (\yii\db\Exception $e) {
					$rows = [];	
				}	
				
				$dataJSON = json_encode($rows);			
				
				echo $var.' = '.$dataJSON.';';				
			}
			 			
		}		
	}		
	echo $model->scripts;
	
$this->endBlock(); 
?>
</script>
<?php
yii\web\YiiAsset::register($this);
$this->registerJs($this->blocks['STATIC_SCRIPTS'], yii\web\View::POS_END);

/*
 
--select k.fungsi as fungsi,count(case when ST_WITHIN(s.geom,i.geom) AND ST_WITHIN(s.geom,k.the_geom) then 1 else 0 end) as sippkh,count(case when ST_WITHIN(s.geom,i.geom) AND ST_WITHIN(s.geom,k.the_geom) then 1 else 0 end) as bippkh,count(case when ST_WITHIN(s.geom,k.the_geom) then 1 else 0 end) as total FROM am_iyo_data_3 k,am_iyo_data_1 s,am_iyo_data_5 i WHERE fungsi = 'HL' GROUP BY k.fungsi LIMIT 5;

--select s.gid,s,nama_asset,i.sk,k.fungsi FROM am_iyo_data_1 s left join am_iyo_data_5 i on ST_WITHIN(s.geom,i.geom) left join am_iyo_data_3 as k on ST_WITHIN(s.geom,k.the_geom) LIMIT 5000;

select k.fungsi,count(case when s.gid is not null then 1 else 0 end) as total from am_iyo_data_3 k left join am_iyo_data_1 s on ST_WITHIN(s.geom,k.the_geom) group by k.fungsi limit 3;

*/
