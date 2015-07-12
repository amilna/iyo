<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model amilna\iyo\models\DataSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="data-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'title') ?>

    <?= $form->field($model, 'description') ?>

    <?= $form->field($model, 'remarks') ?>

    <?= $form->field($model, 'metadata') ?>

    <?php // echo $form->field($model, 'tags') ?>

    <?php // echo $form->field($model, 'author_id') ?>

    <?php // echo $form->field($model, 'type') ?>

    <?php // echo $form->field($model, 'status') ?>

    <?php // echo $form->field($model, 'time') ?>

    <?php // echo $form->field($model, 'isdel') ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
