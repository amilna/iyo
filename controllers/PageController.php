<?php

namespace amilna\iyo\controllers;

use Yii;
use amilna\iyo\models\StaticPage;
use amilna\blog\models\StaticPageSearch;
use amilna\iyo\models\Map;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

class PageController extends \amilna\blog\controllers\PageController
{         
    public function actionIndex($format= false,$arraymap= false,$term = false)
    {
        $searchModel = new StaticPageSearch();                
        $req = Yii::$app->request->queryParams;
        if ($term) { $req[basename(str_replace("\\","/",get_class($searchModel)))]["term"] = $term;}        
        $dataProvider = $searchModel->search($req);	
        
        $query = $dataProvider->query;
		$query->andWhere(['status'=>[3,4,5]]);

        if ($format == 'json')
        {
			$model = [];
			foreach ($dataProvider->getModels() as $d)
			{
				$obj = $d->attributes;
				if ($arraymap)
				{
					$map = explode(",",$arraymap);
					if (count($map) == 1)
					{
						$obj = (isset($d[$arraymap])?$d[$arraymap]:null);
					}
					else
					{
						$obj = [];					
						foreach ($map as $a)
						{
							$k = explode(":",$a);						
							$v = (count($k) > 1?$k[1]:$k[0]);
							$obj[$k[0]] = ($v == "Obj"?json_encode($d->attributes):(isset($d->$v)?$d->$v:null));
						}
					}
				}
				
				if ($term)
				{
					if (!in_array($obj,$model))
					{
						array_push($model,$obj);
					}
				}
				else
				{	
					array_push($model,$obj);
				}
			}			
			return \yii\helpers\Json::encode($model);	
		}
		else
		{
			return $this->render('index', [
				'searchModel' => $searchModel,
				'dataProvider' => $dataProvider,
			]);
		}	
    }
    
    public function actionCreate()
    {
        $model = new StaticPage();
        $model->time = date("Y-m-d H:i:s");	        
        $model->isdel = 0;

        $post = Yii::$app->request->post();
		if (isset($post['StaticPage']['tags']))
		{
			if (is_array($post['StaticPage']['tags']))
			{
				$post['StaticPage']['tags'] = implode(",",$post['StaticPage']['tags']);
			}	
		}
		
        if ($model->load($post) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }
    
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
		$model->tags = !empty($model->tags)?explode(",",$model->tags):[];
		
		$post = Yii::$app->request->post();
		if (isset($post['StaticPage']['tags']))
		{
			if (is_array($post['StaticPage']['tags']))
			{
				$post['StaticPage']['tags'] = implode(",",$post['StaticPage']['tags']);
			}	
		}
		
        if ($model->load($post) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }
    
    protected function findModel($id)
    {
        if (($model = StaticPage::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

 
}
