<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model amilna\iyo\models\Layer */

$this->title = Yii::t('app', 'Create {modelClass}', [
    'modelClass' => Yii::t('app', 'Layer'),
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Layers'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="layer-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
