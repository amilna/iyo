<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model amilna\iyo\models\Record */

$this->title = Yii::t('app', 'Update {modelClass}', [
    'modelClass' => Yii::t('app', 'Record'),
]). ' ' . $model->title;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Data'), 'url' => ['//iyo/data/index']];
$this->params['breadcrumbs'][] = ['label' => $model->data->title, 'url' => ['index', 'data'=>$model::$dataId]];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['view', 'id' => $model->gid,'data'=>$model::$dataId]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<div class="record-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
