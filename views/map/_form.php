<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;
use yii\jui\AutoComplete;
use yii\web\JsExpression;
use amilna\yap\Money;
use kartik\widgets\Select2;
use kartik\widgets\SwitchInput;
use kartik\datetime\DateTimePicker;

/* @var $this yii\web\View */
/* @var $model amilna\iyo\models\Map */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="layer-form">

    <?php $form = ActiveForm::begin(); ?>

	<div class='row'>
		
		<div class="col-md-9">
			<?= $form->field($model, 'title')->textInput(['maxlength' => 65]) ?>
			<?= $form->field($model, 'description')->textArea(['maxlength' => 155]) ?>						
			
			<?php 
			$isettings = [
					'lang' => substr(Yii::$app->language,0,2),
					'minHeight' => 400,
					'toolbarFixedTopOffset'=>50,	
					'buttonSource'=> true,							
					'plugins' => [				
								
						'fullscreen'
					],
					'buttons'=> ['html'],					
					'formatting'=>[],		
					'paragraphize'=>false,	
					'pastePlainText'=>true,
					'deniedTags'=> ['html', 'head', 'link', 'body', 'meta', 'script', 'style', 'applet','p']
				];							
			
			use vova07\imperavi\Widget;
			echo $form->field($model, 'config')->widget(Widget::className(), [
				'settings' => $isettings,
				'options'=>["style"=>"width:100%"]
			]);
			?>
			<div class="well">
				<h4><?= Yii::t("app","Config example")?></h4>
				<code>
					{
						"init":{"zoom":5,"center":[117,-2]},
						"minZoom":5,
						"maxZoom":19,
						"baseMaps":[
							{"name" : "Openstreet Map" , "source" : "www.openstreetmap.org" , "url": "http:\/\/[a,b,c].tile.openstreetmap.org\/{z}\/{x}\/{y}.png"}
						],
						"baseIndex":0,
						"layers":[							
							{
								"name":"Tes Layer",
								"layerId":1,
								"visible":false
							},
							{
								"layerId":2,
								"visible":true,
								"sublayers":["Tes Layer"]
							}
						]
					}
				</code>
			</div>
			<?= $form->field($model, 'remarks')->textarea(['rows' => 6]) ?>
		</div>
		<div class="col-md-3">						
			<?= $form->field($model, 'status')->widget(Select2::classname(), [			
						'data' => $model->itemAlias('status'),				
						'options' => ['placeholder' => Yii::t('app','Select layer status...')],
						'pluginOptions' => [
							'allowClear' => false
						],
						'pluginEvents' => [						
						],
					]);
				?>	
			<?= $form->field($model, 'tags')->widget(Select2::classname(), [
					'options' => [
						'placeholder' => Yii::t('app','Put additional tags ...'),
					],
					'data'=>$model->getTags(),
					'pluginOptions' => [
						'tags' => true,
						'tokenSeparators'=>[',',' '],
					],
				]) ?>
		</div>
	</div>

	
	<div class='row'>
		<div class='col-xs-12'>
			<div class="form-group">
				<?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success pull-right' : 'btn btn-primary pull-right']) ?>
			</div>
		</div>
    </div>

    <?php ActiveForm::end(); ?>

</div>
