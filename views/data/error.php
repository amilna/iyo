<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model amilna\iyo\models\Data */

$this->title = "Error ".$exception->statusCode;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Data'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="alert alert-danger">

    <?= $exception->getName() ?>    
    
</div>
