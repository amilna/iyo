<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model amilna\iyo\models\Map */

$this->title = Yii::t('app', 'Create {modelClass}', [
    'modelClass' => Yii::t('app', 'Map'),
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Maps'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="map-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
