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
			<?= $form->field($model, 'config')->textArea(['rows' => 8]) ?>
			
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
			
			<?= $model->remarks ?>
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

<script type="text/javascript">
<?php $this->beginBlock('TAB') ?>			
$(document).delegate('#map-config', 'keydown', function(e) {
  var keyCode = e.keyCode || e.which;

  if (keyCode == 9) {
    e.preventDefault();
    var start = $(this).get(0).selectionStart;
    var end = $(this).get(0).selectionEnd;

    // set textarea value to: text before caret + tab + text after caret
    $(this).val($(this).val().substring(0, start)
                + "\t"
                + $(this).val().substring(end));

    // put caret at right position again
    $(this).get(0).selectionStart =
    $(this).get(0).selectionEnd = start + 1;
  }
});
<?php $this->endBlock(); ?>

</script>
<?php
yii\web\YiiAsset::register($this);
$this->registerJs($this->blocks['TAB'], yii\web\View::POS_END);

