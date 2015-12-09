<?php

namespace amilna\iyo\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

class DefaultController extends Controller
{
    public function actionIndex()
    {
        return $this->render('index');
    }
    
    public function actionGetupload($pattern)
    {
		$module = Yii::$app->getModule('iyo');
		$uploadDir = $module->uploadDir;
		$uploadURL = $module->uploadURL;
		$path = \Yii::getAlias($uploadDir);	
		$url = \Yii::getAlias($uploadURL);	
		
		$files = glob($path.$pattern);
		
		if (count($files) >= 1)
		{
			$file = $files[0];
			$url = $url.str_replace($path,"",$file);
			$this->redirect($url);				
		}							
		else
		{
			throw new NotFoundHttpException('The requested page does not exist.');	
		}				
	}
}
