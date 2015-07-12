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
/* @var $model amilna\iyo\models\Record */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="record-form">

    <?php $form = ActiveForm::begin(); ?>
	
	<?php
		
		$module = Yii::$app->getModule('iyo');		
		
		$columns = [];
		foreach ($model->rules() as $r)
		{			
			$columns = array_merge($columns,$r[0]);
		}
		$columns = array_unique($columns);				
		
		if(($key = array_search("term", $columns)) !== false) {
			unset($columns[$key]);
		}
		
		if(($key = array_search("gid", $columns)) !== false) {
			unset($columns[$key]);
		}						
		
		if(($key = array_search($module->geom_col, $columns)) !== false) {
			unset($columns[$key]);
		}				
		
		foreach ($columns as $c)
		{
	?>		
			<div class='row'>
				<div class='col-sm-10 col-sm-offset-1 col-md-8 col-md-offset-2'>
					<?= $form->field($model,$c)->textInput() ?>
				</div>
			</div>
	
	<?php		
		}
    ?>
		

	<div class='row'>
		<div class='col-sm-10 col-sm-offset-1 col-md-8 col-md-offset-2'>
			<div class="form-group">
				<?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success pull-right' : 'btn btn-primary pull-right']) ?>
			</div>
		</div>
    </div>

    <?php ActiveForm::end(); ?>

</div>
