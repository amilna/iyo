<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\DetailView;
use amilna\yap\GridView;

/* @var $this yii\web\View */
/* @var $searchModel amilna\iyo\models\RecordSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$data = $searchModel->data;
$this->title = $data->title;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Data'), 'url' => ['//iyo/data/index']];
$this->params['breadcrumbs'][] = $this->title;


$text = '';
$text .= ($text == ''?'':'. ').$data->description;
$text .= ($text == ''?'':'. ').$data->remarks;

$labels = $data->attributeLabels();
?>

<?php 
	// echo $this->render('_search', ['model' => $searchModel]);        
	$colnames = '';
	$columns = $searchModel->rules()[0][0];
	
	if(($key = array_search("term", $columns)) !== false) {
		unset($columns[$key]);
	}
	
	if(($key = array_search("gid", $columns)) !== false) {
		unset($columns[$key]);
	}
	
	$cols = [];
	$mcols = json_decode($data->metadata);
	$n = 0;
	
	if (isset($mcols->columns))
	{			
			
		foreach ($mcols->columns as $mcol)
		{
			if ($n < 5 && in_array($mcol->name,$columns))
			{								
				$cols[] = $mcol->name;
				$n += 1;	
			}
			$colnames .= ($colnames == ''?'<a>':', <a>').$mcol->name.'</a> <small>'.$mcol->type.' '.$mcol->options.'</small>';
		}
		
	}
?>


<div class="data-view">

    <h1><?= Html::encode($this->title) ?></h1>
				
    <p>                
        <?= $text ?>
    </p>
    <div class="well"><?= $colnames ?></div>      
    <div class="alert"><?= '<b>'.$labels['type'].'</b> '.$data->itemAlias('geomtype',$data->type) .
        ' <b>'.$labels['srid'].'</b> '.$data->srid .        
        ' <b>'.$labels['tags'].'</b> '.$data->tags         
        ?>
        
        <?= Html::a(Yii::t('app', 'Update'), ['//iyo/data/update', 'id' => $data->id], ['class' => 'btn btn-primary pull-right']) ?>
        <?= Html::a(Yii::t('app', 'Delete'), ['//iyo/data/delete', 'id' => $data->id], [
            'class' => 'btn btn-danger pull-right',
            'data' => [
                'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                'method' => 'post',
            ],
        ]) ?>
        
      </div>		

</div>

<div class="record-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]);        
    
		$columns = $searchModel->rules()[0][0];
		
		if(($key = array_search("term", $columns)) !== false) {
			unset($columns[$key]);
		}
		
		if(($key = array_search("gid", $columns)) !== false) {
			unset($columns[$key]);
		}
		
		$cols = [];
		$mcols = json_decode($data->metadata);
		$n = 0;
					
		if (isset($mcols->columns))
		{			
			foreach ($mcols->columns as $mcol)
			{
				if ($n < 5 && in_array($mcol->name,$columns))
				{								
					$cols[] = $mcol->name;
					$n += 1;	
				}
			}
		}
    ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        
        'containerOptions' => ['style'=>'overflow: auto'], // only set when $responsive = false		
		'caption'=>Yii::t('app', 'Record'),
		'headerRowOptions'=>['class'=>'kartik-sheet-style','style'=>'background-color: #fdfdfd'],
		'filterRowOptions'=>['class'=>'kartik-sheet-style skip-export','style'=>'background-color: #fdfdfd'],
		'pjax' => false,
		'bordered' => true,
		'striped' => true,
		'condensed' => true,
		'responsive' => true,
		'responsiveWrap' => false,
		'hover' => true,
		'showPageSummary' => true,
		'pageSummaryRowOptions'=>['class'=>'kv-page-summary','style'=>'background-color: #fdfdfd'],
		'tableOptions'=>["style"=>"margin-bottom:100px;"],
		'panel' => [
			'type' => GridView::TYPE_DEFAULT,
			'heading' => '<i class="glyphicon glyphicon-th-list"></i>  '.Yii::t('app', 'Record'),			
			'before'=>Html::a(
					'<i class="glyphicon glyphicon-plus"></i> '.Yii::t('app', 'Create'),
					['create','data'=>$searchModel::$dataId], 
					[	'class' => 'btn btn-success', 
						'title'=>Yii::t('app', 'Create {modelClass}', [
							'modelClass' => Yii::t('app','Record'),
						])
					]
				).' <em style="margin:10px;"><small>'.Yii::t('app', 'Type in column input below to filter, or click column title to sort').'</small></em>',
		],				
		'toolbar' => [			
			['content'=>								
				Html::a('<i class="glyphicon glyphicon-repeat"></i>', ['index'], ['data-pjax'=>true, 'class' => 'btn btn-default', 'title'=>Yii::t('app', 'Reset Grid')])
			],
			'{export}',
			//'{toggleData}'
		],
		'beforeHeader'=>[
			[
				/* uncomment to use additional header
				'columns'=>[
					['content'=>'Group 1', 'options'=>['colspan'=>6, 'class'=>'text-center','style'=>'background-color: #fdfdfd']], 
					['content'=>'Group 2', 'options'=>['colspan'=>6, 'class'=>'text-center','style'=>'background-color: #fdfdfd']], 					
				],
				*/
				'options'=>['class'=>'skip-export'] // remove this row from export
			]
		],
		'floatHeader' => true,		
		'floatHeaderOptions'=>['position'=>'absolute','top'=>50],
		/*uncomment to use megeer some columns
        'mergeColumns' => ['Column 1','Column 2','Column 3'],
        'type'=>'firstrow', // or use 'simple'
        */
        
        'filterModel' => $searchModel,
        'columns' => array_merge(
            [['class' => 'kartik\grid\SerialColumn']],
			//$columns,
			$cols,
            /*'id',
            'title',
            'description',
            'remarks:ntext',
            'metadata:ntext',*/
            // 'tags',
            // 'author_id',
            // 'type',
            // 'status',
            // 'time',
            // 'isdel',

            [[
				'class' => 'kartik\grid\ActionColumn',
				'buttons'=>[
					'view'=>function ($url, $model, $key) {
						return Html::a('<span class="glyphicon glyphicon-eye-open"></span>',["//iyo/record/view","id"=>$model->gid,'data'=>$model::$dataId],["title"=>Yii::t("yii","View")]);
					},						
					'update'=>function ($url, $model, $key) {
						return Html::a('<span class="glyphicon glyphicon-pencil"></span>',["//iyo/record/update","id"=>$model->gid,'data'=>$model::$dataId],["title"=>Yii::t("yii","Update")]);
					},	
					'delete'=>function ($url, $model, $key) {
						return Html::a('<span class="glyphicon glyphicon-trash"></span>',["//iyo/record/delete","id"=>$model->gid,'data'=>$model::$dataId],["title"=>Yii::t("yii","Delete"),"data"=>["confirm"=>Yii::t("app","Are you sure you want to delete this item?"),"method"=>"post"]]);
					},	
				]
            ]]
        ),
    ]); ?>

</div>
