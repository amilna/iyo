<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model amilna\iyo\models\Record */

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

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Data'), 'url' => ['//iyo/data/index']];
$this->params['breadcrumbs'][] = ['label' => $model->data->title, 'url' => ['index', 'data'=>$model::$dataId]];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="record-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(Yii::t('app', 'Update'), ['update', 'id' => $model->gid,'data'=>$model::$dataId], ['class' => 'btn btn-primary']) ?>
        <?= Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->gid,'data'=>$model::$dataId], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                'method' => 'post',
            ],
        ]) ?>
    </p>	
    
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => $columns,
    ]) ?>

</div>
