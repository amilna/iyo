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

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        
        'containerOptions' => ['style'=>'overflow: auto'], // only set when $responsive = false		
		//'caption'=>Yii::t('app', 'Data'),
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
			'heading' => '<i class="glyphicon glyphicon-th-list"></i>  '.Yii::t('app', 'Data'),			
			'before'=>Html::a(
					'<i class="glyphicon glyphicon-plus"></i> '.Yii::t('app', 'Create'),
					['create'], 
					[	'class' => 'btn btn-success', 
						'title'=>Yii::t('app', 'Create {modelClass}', [
							'modelClass' => Yii::t('app','Data'),
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
        'columns' => [
            //['class' => 'kartik\grid\SerialColumn'],

            'id',
            'title',
            'description',
            'remarks:ntext',
            [				
				'attribute'=>'metadata',				
				'format'=>'raw',
				'value'=>function($data){																			
					$colnames = '';
					$mcols = json_decode($data->metadata);												
					foreach ($mcols->columns as $mcol)
					{						
						$colnames .= ($colnames == ''?'<a>':', <a>').$mcol->name.'</a> <small>'.$mcol->type.' '.$mcol->options.'</small>';
					}
					return $colnames;										
				},				
			],
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
				'template' => '{download} {view} {update} {delete}',
				'buttons'=>[
					'view'=>function ($url, $model, $key) {
						return Html::a('<span class="glyphicon glyphicon-eye-open"></span>',["//iyo/".($model->type < 6?"record/index":"data/view"),($model->type < 6?'data':'id')=>$model->id],["title"=>Yii::t("yii","View")]);
					},									
					'download'=>function ($url, $model, $key) {
						return Html::a('<span class="glyphicon glyphicon-download"></span>',["//iyo/data/getshp",'id'=>$model->id],["title"=>Yii::t("app","Download")]);
					},
				]
            ],
        ],
    ]); ?>

</div>
