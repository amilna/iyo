<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;
use yii\jui\AutoComplete;
use amilna\yap\Money;
use kartik\widgets\Select2;
use kartik\widgets\SwitchInput;
use kartik\datetime\DateTimePicker;

/* @var $this yii\web\View */
/* @var $model amilna\iyo\models\Data */
/* @var $form yii\widgets\ActiveForm */

use iutbay\yii2kcfinder\KCFinderInputWidget;

$module = Yii::$app->getModule('iyo');
// kcfinder options
// http://kcfinder.sunhater.com/install#dynamic
$kcfOptions = array_merge([], [
    'uploadURL' => Yii::getAlias($module->uploadURL),
    'uploadDir' => Yii::getAlias($module->uploadDir),
    'access' => [
        'files' => [
            'upload' => true,
            'delete' => true,
            'copy' => false,
            'move' => true,
            'rename' => true,
        ],
        'dirs' => [
            'create' => false,
            'delete' => false,
            'rename' => false,
        ],
    ],  
    'types'=>[
		'files'    =>  "",        
        'images'   =>  "*img",
        //'geos'   =>  "dbf prj qix qpj sbn sbx shp shx xml geojson kml gpx",
        //'geos'   =>  "zip geojson kml gpx z*",
        'geos'   =>  "*",
    ],    
    'thumbWidth' => 260,
    'thumbHeight' => 0,              
]);

?>

<div class="data-form">

    <?php $form = ActiveForm::begin(); ?>

	<div class='row'>
		<div class='col-sm-6'>
			<?= $form->field($model, 'title')->textInput(['maxlength' => 65]) ?>
			<?= $form->field($model, 'description')->textArea(['maxlength' => 155]) ?>
			<?= $form->field($model, 'metadata')->textarea(['rows' => 6]) ?>						
			<?= $form->field($model, 'remarks')->textarea(['rows' => 6]) ?>			
		</div>
		<div class='col-sm-6'>
			<a class="pull-right btn <?php
				if (in_array($model->status,[0,5]))
				{
					echo 'btn-danger';	
				}
				elseif (in_array($model->status,[2,4]))
				{
					echo 'btn-warning';	
				}
				elseif (in_array($model->status,[1]))
				{
					echo 'btn-info';	
				}
				elseif (in_array($model->status,[3]))
				{
					echo 'btn-success';	
				}
			 ?>">
				<?= $model->itemAlias('status',$model->status)?>
			</a>
			<div class="row"></div>
			
			
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
			
			<?= $form->field($model, 'type')->widget(Select2::classname(), [
					'options' => [
						'placeholder' => Yii::t('app','Geometry type ...'),
					],
					'data'=>$model->itemAlias('geomtype'),					
				]) ?>
				
			
			
			<div id="filesrc" class="col-md-12">
				<div class="well">
			<?php 	
				$field = $form->field($model, 'metadata[filesrc]');
				$field->template = "{input}";
				echo Html::textInput("filesrc","",['id'=>'data-filesrc','readonly'=>true,'class'=>'form-control','placeholder'=>Yii::t('app','Name of uploaded file')]);
				echo KCFinderInputWidget::widget([
					'name'=>'filesrc_url',
					'multiple' => false,
					'kcfOptions'=>$kcfOptions,	
					'kcfBrowseOptions'=>[
						'type'=>'geos',
						'lng'=>substr(Yii::$app->language,0,2),				
					],					
				]);	
			?>
				</div>					
			</div>
			<?php 
				echo Html::label(Yii::t('app','Append to existing records?'),'isappend');
				echo SwitchInput::widget([
					'name'=>'isappend',
					'type' => SwitchInput::CHECKBOX,
					'options'=>['id'=>'isappend'],
					'pluginEvents' => [
						"switchChange.bootstrapSwitch" => "function(event, value) { 
							var metadata = JSON.parse($('#data-metadata').val());
							metadata.isappend = value;
							$('#data-metadata').val(JSON.stringify(metadata));						
						}",
					]	
				]);
			?>
			
			<?php 
				echo Html::label(Yii::t('app','Retrieve relational columns data?'),'relational_columns_update');
				echo SwitchInput::widget([
					'name'=>'relational_columns_update',
					'type' => SwitchInput::CHECKBOX,
					'options'=>['id'=>'relational_columns_update'],
					'pluginEvents' => [
						"switchChange.bootstrapSwitch" => "function(event, value) { 
							var metadata = JSON.parse($('#data-metadata').val());
							if (value)
							{
								metadata.relational_columns_update = value;
							}
							else
							{
								delete metadata['relational_columns_update'];
							}	
							$('#data-metadata').val(JSON.stringify(metadata));						
						}",
					]	
				]);
			?>
			

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
	
<?php $this->beginBlock('FILESRC') ?>			

function baseName(str,wext)
{
	var base = new String(str).substring(str.lastIndexOf('/') + 1); 
	if(base.lastIndexOf(".") != -1 && typeof wext == "undefined") {
		base = base.substring(0, base.lastIndexOf("."));		
	}    
	return base;
}

$('#filesrc .kcf-thumbs').bind("DOMSubtreeModified",function(){	
	var sel = $('#filesrc .kcf-thumbs input[name=filesrc_url]');
	var url = "";
	if (sel.length > 0 && $('#filesrc .kcf-thumbs').html().replace(/ /g,"") != "")
	{
		url = sel.val();
		$('#filesrc .kcf-thumbs img').each(function(i,img){
			var src = $(img).attr("src");
			var ext = baseName(src);
			if (ext != 'htm')
			{
				$(img).attr("src",src.replace(ext+".png","htm.png"));
			}			
		});
	}	
	$('#data-filesrc').val(url.replace("<?= Yii::getAlias($module->uploadURL)."/geos/"?>","").replace(/%3D/g,"="));	
});
	
<?php $this->endBlock(); ?>

</script>
<?php
yii\web\YiiAsset::register($this);
$this->registerJs($this->blocks['FILESRC'], yii\web\View::POS_END);

