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

$blogmodule = Yii::$app->getModule('blog');
if ($blogmodule->enableUpload)
{
	// kcfinder options
	// http://kcfinder.sunhater.com/install#dynamic
	$kcfOptions = array_merge([], [
		'uploadURL' => Yii::getAlias($blogmodule->uploadURL),
		'uploadDir' => Yii::getAlias($blogmodule->uploadDir),
		'access' => [
			'files' => [
				'upload' => true,
				'delete' => false,
				'copy' => false,
				'move' => false,
				'rename' => false,
			],
			'dirs' => [
				'create' => true,
				'delete' => false,
				'rename' => false,
			],
		], 
		'types'=>[
			'files'    =>  "",        
			'images'   =>  "*img",
		],     
		'thumbWidth' => 260,
		'thumbHeight' => 260,          
	]);

	// Set kcfinder session options
	Yii::$app->session->set('KCFINDER', $kcfOptions);
}

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
		
		$metadata = json_decode($model->data->metadata,true);				
		$cols = $metadata['columns'];
				
		
		$nocol = [];				
		foreach ($cols as $cl)
		{
			$c = $cl['name'];
			if (in_array($c,$columns))
			{											
			
	?>		
			<div class='row'>
				<div class='col-sm-10 col-sm-offset-1 col-md-8 col-md-offset-2'>
					<?php 
							
						if ($cl)
						{
							if ($cl['type'] == 'text')
							{
								$isettings = [
										'lang' => substr(Yii::$app->language,0,2),
										'minHeight' => 200,
										'toolbarFixed' => false,
										'toolbarFixedTopOffset'=>50,
										'buttonSource'=> true,									
										'plugins' => [				
											'imagemanager',
											'filemanager',
											'video',
											'table',
											'clips',				
											'fullscreen'
										],
										'buttons'=> ['html','formatting', 'bold', 'italic','underline','deleted', 'unorderedlist', 'orderedlist',
										  'outdent', 'indent', 'image', 'file', 'link', 'alignment', 'horizontalrule'
										],
										'replaceDivs'=> false,
										'deniedTags'=> ['script']
									];
									
								if ($blogmodule->enableUpload)
								{
									$isettings = array_merge($isettings,[
													'imageUpload' => Url::to(['//blog/default/image-upload']),								
													'fileUpload' => Url::to(['//blog/default/file-upload']),
													'imageManagerJson' => Url::to(['//blog/default/images-get']),			
													'fileManagerJson' => Url::to(['//blog/default/files-get']),
												]);
								}
																	
								echo $form->field($model, $c)->widget(\vova07\imperavi\Widget::className(), [
									'settings' => $isettings,
									'options'=>["style"=>"width:100%"]
								]);
							}
							elseif (substr($cl['type'],0,3) == 'int')
							{
								echo $form->field($model,$c)->textInput(); 
							}	
							else
							{
								echo $form->field($model,$c)->textInput(); 
							}
							
						}
						else
						{	
							echo $form->field($model,$c)->textInput(); 
						}	
					
					?>
				</div>
			</div>
	
	<?php		
			}
			else
			{
				array_push($nocol,$c);	
			}
		}
		
		foreach ($nocol as $c)
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
