<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model amilna\iyo\models\Data */

$this->title = Yii::t('app', 'Update {modelClass}', [
    'modelClass' => Yii::t('app', 'Data'),
]). ' ' . $model->title;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Data'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<div class="data-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
