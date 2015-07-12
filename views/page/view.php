<?php

use yii\helpers\Html;
use yii\helpers\HtmlPurifier;
use yii\widgets\ActiveForm;
use yii\widgets\DetailView;
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
		$content = preg_replace('/\{DATA sql\:(.*) values\:(.*) var\:([a-z])\}/','',$content);
	}		
												
	echo $content;					
?>

<script>
<?php 
$this->beginBlock('STATIC_SCRIPTS');
	
	$content = $model->content;	
	preg_match_all('/\{DATA sql\:\"SELECT (.*);\" values\:\{(.*)\} var\:([a-z]+)\}/',$content,$matches);		
	if (count($matches[0]) > 0)
	{
		for ($m=0;$m<count($matches[0]);$m++)
		{
			$sql  = $matches[1][$m];
			$values  = '{'.$matches[2][$m].'}';
			$var  = $matches[3][$m];
			$data = Yii::$app->db->createCommand($sql)->bindValues(json_decode($values,true))->queryScalar();
			$dataJSON = json_encode($data);
			
			
			echo $var.' = '.$dataJSON.';';
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
