<?php

namespace amilna\iyo\controllers;

use Yii;
use amilna\iyo\models\StaticPage;
use amilna\blog\models\StaticPageSearch;
use amilna\iyo\models\Map;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\db\Query;

class PageController extends \amilna\blog\controllers\PageController
{         
    public function actionIndex($format= false,$arraymap= false,$term = false)
    {
        $searchModel = new StaticPageSearch();                
        $req = Yii::$app->request->queryParams;
        if ($term) { $req[basename(str_replace("\\","/",get_class($searchModel)))]["term"] = $term;}        
        $dataProvider = $searchModel->search($req);	
        
        $query = $dataProvider->query;
		$query->andWhere(['status'=>[3,4,5,6]]);

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
 
 
    public function actionView($id = false,$format= false)
    {
        
        $model = false;
        if ($id == false)
        {
			$model = StaticPage::find()->orderBy('status DESC')->one();
		}
        
        if (!$model)
        {
			$model = $this->findModel($id);
		}
        
        if ($format == 'json')
        {
			return \yii\helpers\Json::encode($model);	
		}
		else
		{
			return $this->render('view', [
				'model' => $model,
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
    
    public function actionQuery()
    {
		$content = isset($_POST['content'])?$_POST['content']:false;
		$res = [];		
		if ($content)
		{			
			preg_match_all('/\{DATA id\:(\d+) query\:(.*) var\:([a-zA-Z0-9]+)\}/',$content,$matches);		
			if (count($matches[0]) > 0)
			{		
				for ($m=0;$m<count($matches[0]);$m++)
				{
					$did = $matches[1][$m];
					
										
					$querystr = $matches[2][$m];			
					$var  = $matches[3][$m];
					
					$json = json_decode($querystr,true);			
					if (isset($json['select']) && isset($json['from']))
					{																					
						$fromt = is_array($json['from'])?$json['from']:explode(',',$json['from']);
						$froms = [];
						foreach ($fromt as $n=>$f)
						{
							$froms[] = is_numeric($f)?'{{%iyo_data_'.$f.'}} as t'.($n==0?'':$n):$f.' as t'.($n==0?'':$n);							
						}
						
						$query = new Query;
						$query->select($json['select'])						
							->from($froms);
						
						if (isset($json['leftJoins']))
						{
							foreach ($json['leftJoins'] as $lj)
							{
								$query->leftJoin(is_numeric($lj['table'])?'{{%iyo_data_'.$lj['table'].'}} as j'.$lj['table']:$lj['table'],$lj['on'],$lj['params']);
							}
						}	
						
						if (isset($json['where']))
						{
							$query->where($json['where']['condition'],$json['where']['params']);
						}
						
						if (isset($json['groupBy']))
						{
							$query->groupBy($json['groupBy']);
						}
						
						if (isset($json['orderBy']))
						{
							$query->orderBy($json['orderBy']);
						}
						
						if (isset($json['limit']))
						{
							$query->limit($json['limit']);
						}
						
						$rows = $query->all();			
						$res[$did] = $rows;								
					}
				
				}				
			}
		}
		
		die(json_encode($res));	
		
	}

 
}
