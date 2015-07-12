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
/* @var $model amilna\iyo\models\Layer */
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
				<h5>Polygon / Line layer</h5>
				<code>
					[
						{
							"fields":[
								{
									"name":"sk",
									"alias":"No.SK",
									"type":[
										"link",
										{
											"reformat":[
												"\/^SK\\.([0-9]+)\\\/(.*)\\\/([0-9]{4})\/",
												"http:\/\/localhost\/iyo\/default\/getupload\/?pattern=\/files\/Kumpulan_SK_PEP\/$3\/SK_$1_*-$3_*"
											]
										}
									]
								},
								{"name":"luas_ha","alias":"Luas (Ha)"}
							],
							"data":[
								{"name":"sk","alias":"No.SK"},
								{"name":"tgl_sk","alias":"Tanggal SK"},
								{"name":"keterangan","alias":"Keterangan"},
								{"name":"luas_ha","alias":"Luas (Ha)"}],
							"rules":[
								{				
									"polygonSymbolizer":{								
										"fill":"rgb(255,255,255)",
										"fillOpacity":"0.3"  
									},							
									"lineSymbolizer":{								
										"stroke":"#ff8fe6",
										"strokeWidth":"2",  
										"strokeOpacity":"1"
									},
									"style": {											
										"label": {
											"attribute":"sk",											
											"font": "12px DejaVu Sans Book,sans-serif",
											"color": "rgb(0,0,0)",
											"strokeColor": "#ffffff",
											"strokeWidth": 2
										}
									}			
								}
							]
						}
					]
				</code>
				<h5>Point layer</h5>
				<code>
					[
						{
							"fields":[
								{"name":"nama_asset","alias":"Nama Asset"},								
							"data":[
								{"name":"nama_asset","alias":"Nama Asset"},
								{"name":"nama_field","alias":"Field"},
								{"name":"status","alias":"Status","default":"0"}],
							"rules":[
								{	
									"style": {	
										"scale":0.4,
										"opacity":0.8,
										"anchor":[0.5,1],
										"src":"\/images\/well-icon.png",
										"label": {
											"attribute":"nama_asset",
											"textAlign":"start",
											"offsetX":10
										}
									}
								}
							]
						}
					]
				</code>
			</div>
			
			<?= $form->field($model, 'remarks')->textarea(['rows' => 6]) ?>
		</div>
		<div class="col-md-3">
			<?= $form->field($model, 'type')->widget(Select2::classname(), [			
						'data' => $model->itemAlias('type'),				
						'options' => ['placeholder' => Yii::t('app','Select layer type...')],
						'pluginOptions' => [
							'allowClear' => false
						],
						'pluginEvents' => [						
						],
					]);
				?>
				
			<div class="form-group">
			<?php
			
			$url = Yii::$app->urlManager->createUrl("//iyo/layer/datas");						
			$initScript = <<< SCRIPT
			function (element, callback) {							
				var id=\$(element).val();						
				if (id !== "") {
					\$.ajax("{$url}?id=" + id, {
						dataType: "json"
					}).done(function(data) { callback(data.results);});
				}
			}
SCRIPT;
			
			echo $form->field($model, 'data_id')->widget(Select2::classname(), [
				'options' => ['placeholder' => 'Search data by title ...'],
				'pluginOptions' => [
					'allowClear' => true,
					'minimumInputLength' => 3,								
					'ajax' => [
						'url' => $url,
						'dataType' => 'json',
						'data' => new JsExpression('function(params) { return {search:params.term}; }'),
						'results' => new JsExpression('function(data,page) {return {results:data.results}; }'),
					],
					'initSelection' => new JsExpression($initScript)
				]							
			]);
		
			?>
									
			</div>
			
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
