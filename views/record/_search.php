<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model amilna\iyo\models\RecordSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="data-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index','data'=>$model::$dataId],
        'method' => 'get',
    ]); ?>
	
	<?php
    
		$columns = $model->rules()[0][0];
		
		if(($key = array_search("term", $columns)) !== false) {
			unset($columns[$key]);
		}
		
		if(($key = array_search("gid", $columns)) !== false) {
			unset($columns[$key]);
		}						
		
		foreach ($columns as $c)
		{
			echo $form->field($model, $c);	
		}
    ?>
	    
    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
