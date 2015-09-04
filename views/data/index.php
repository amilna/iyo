<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use amilna\yap\GridView;

/* @var $this yii\web\View */
/* @var $searchModel amilna\iyo\models\DataSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Data');
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="data-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>


    <p>
        <?= Html::a(Yii::t('app', 'Create {modelClass}', [
    'modelClass' => Yii::t('app', 'Data'),
]), ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        
        'containerOptions' => ['style'=>'overflow: auto'], // only set when $responsive = false		
		'caption'=>Yii::t('app', 'Data'),
		'headerRowOptions'=>['class'=>'kartik-sheet-style','style'=>'background-color: #fdfdfd'],
		'filterRowOptions'=>['class'=>'kartik-sheet-style skip-export','style'=>'background-color: #fdfdfd'],
		'pjax' => false,
		'bordered' => true,
		'striped' => true,
		'condensed' => true,
		'responsive' => true,
		'hover' => true,
		'showPageSummary' => true,
		'pageSummaryRowOptions'=>['class'=>'kv-page-summary','style'=>'background-color: #fdfdfd'],
		'tableOptions'=>["style"=>"margin-bottom:100px;"],
		'panel' => [
			'type' => GridView::TYPE_DEFAULT,
			'heading' => false,
		],
		'toolbar' => [
			['content'=>				
				Html::a('<i class="glyphicon glyphicon-repeat"></i>', ['index'], ['data-pjax'=>false, 'class' => 'btn btn-default', 'title'=>Yii::t('app', 'Reset Grid')])
			],
			'{export}',
			'{toggleData}'
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
		
		/* uncomment to use megeer some columns
        'mergeColumns' => ['Column 1','Column 2','Column 3'],
        'type'=>'firstrow', // or use 'simple'
        */
        
        'filterModel' => $searchModel,
        'columns' => [
            //['class' => 'kartik\grid\SerialColumn'],

            'id',
            'title',
            'description',
            'remarks:ntext',
            'metadata:ntext',
            'tags',
            // 'author_id',
             [				
				'attribute'=>'type',				
				'value'=>function($data){																			
					return $data->itemAlias('geomtype',$data->type);										
				},
				'filterType'=>GridView::FILTER_SELECT2,				
				'filterWidgetOptions'=>[					
					'options' => ['placeholder' => Yii::t('app','Geometry type')],
					'data'=>$searchModel->itemAlias('geomtype'),
					'pluginOptions' => [
						'allowClear' => true,											
					],
					
				],
			],           
             [				
				'attribute'=>'status',
				'format'=>'raw',
				'value'=>function($data){															
					if (in_array($data->status,[0,5]))
					{
						$cl =  'btn-danger';	
					}
					elseif (in_array($data->status,[2,4]))
					{
						$cl =  'btn-warning';	
					}
					elseif (in_array($data->status,[1]))
					{
						$cl =  'btn-info';	
					}
					elseif (in_array($data->status,[3]))
					{
						$cl =  'btn-success';	
					}
					return Html::a($data->itemAlias('status',$data->status),'',["class"=>"btn btn-xs ".$cl." btn-block"]);										
				},
				'filterType'=>GridView::FILTER_SELECT2,				
				'filterWidgetOptions'=>[					
					'options' => ['placeholder' => Yii::t('app','Status')],
					'data'=>$searchModel->itemAlias('status'),
					'pluginOptions' => [
						'allowClear' => true,											
					],
					
				],
			],	
            // 'time',
            // 'isdel',

            [
				'class' => 'kartik\grid\ActionColumn',
				'buttons'=>[
					'view'=>function ($url, $model, $key) {
						return Html::a('<span class="glyphicon glyphicon-eye-open"></span>',["//iyo/record/index",'data'=>$model->id],["title"=>Yii::t("yii","View")]);
					},									
				]
            ],
        ],
    ]); ?>

</div>
