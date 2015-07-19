<?php

namespace amilna\iyo\controllers;

use Yii;
use amilna\iyo\models\Record;
use amilna\iyo\models\Data;
use amilna\iyo\models\RecordSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * RecordController implements the CRUD actions for Record model.
 */
class RecordController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                    'rest' => ['post']
                ],
            ],
        ];
    }

    /**
     * Lists all Data models.
     * @params string $format, array $arraymap, string $term
     * @return mixed
     */
    public function actionIndex($format= false,$arraymap= false,$term = false,$data = false)
    {		                		
		$searchModel = new RecordSearch($data);								
		
		$req = Yii::$app->request->queryParams;
		if ($term) { $req[basename(str_replace("\\","/",get_class($searchModel)))]["term"] = $term;}        
		$dataProvider = $searchModel->search($req);		

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
						$obj = $d[$arraymap];
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

    /**
     * Displays a single Data model.
     * @param integer $id
     * @additionalParam string $format
     * @return mixed
     */
    public function actionView($data = false,$id,$format= false)
    {
        if (!$data || !is_numeric($data))
        {
			return $this->redirect(['//iyo/data/index']);
		}
		
        $model = $this->findModel($data,$id);
        
        $module = Yii::$app->getModule('iyo');
		$geom_col = $module->geom_col;
        $model->$geom_col = $model->geojson;        
        
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

    /**
     * Creates a new Data model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate($data = false)
    {
        if (!$data)
        {
			return $this->redirect(['//iyo/data/index']);
		}
			
        $model = new Record($data);
			
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->gid,'data'=>$model::$dataId]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing Data model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($data = false,$id)
    {
        if (!$data)
        {
			return $this->redirect(['//iyo/data/index']);
		}
        
        $model = $this->findModel($data,$id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->gid,'data'=>$model::$dataId]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing Data model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($data = false,$id,$format=false)
    {        
		if (!$data)
        {
			return $this->redirect(['//iyo/data/index']);
		}
		$model = $this->findModel($data,$id);        
        $model->isdel = 1;
        $model->save();
        //$model->delete(); //this will true delete
        
        if ($format='json')
        {
			return json_encode(['status'=>true]);	
		}
        
        return $this->redirect(['index','data'=>$model::$dataId]);
    }

    /**
     * Finds the Data model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Data the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($data,$id)
    {
        if (!$data)
        {
			return $this->redirect(['//iyo/data/index']);
		}
        $model = new Record($data);
        if (($model = $model->findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
    
    /**
	 * @inheritdoc
	 */
	public function beforeAction($action)
	{            
		if ($action == 'rest') {
			Yii::$app->controller->enableCsrfValidation = false;
		}

		return parent::beforeAction($action);
	}
    
    public function actionRest($data = false,$id = null)
    {
        $this->enableCsrfValidation = false;
        if (!$data)
        {
			return json_encode(['status'=>false]);
		}
		
		$post = Yii::$app->request->post();
		
		$dataRec = [];
		if ($id == 0 && isset($post['records']))
		{
			$dataRec = $post['records'];
			//print_r($post);
			//die();
		}
		else
		{
			$dataRec[$id] = $post;	
		}
		
		$errors = [];
				
		foreach ($dataRec as $id=>$rec)
		{						
			
			$model = new Record($data);
			if (is_numeric($id) && $model)
			{
				$model = $model->findOne($id);		
				if (!$model) 
				{
					$model = new Record($data);	
				}
			}
			
			if ($model)
			{
				$datamodel = Data::findOne($data);	
			}
			
			$res= false;					
			if (count($post) > 1 && $model->load(['Record'=>$rec]))
			{
				
				$module = Yii::$app->getModule('iyo');
				$geom1 = false;
				$geom2 = false;
				foreach ($rec as $key=>$val)
				{
					if ($key == "geometry")
					{					
						$key = $module->geom_col;	
						
						$datatype = $datamodel->type;
						
						/* postgis 2
						$val = $model->db->createCommand(
							"SELECT (ST_GeomFromGeoJSON(CAST(:val AS text))) as g"
						)->bindValues([":val"=>$val])->queryScalar();
						*/
						
						$geometry = json_decode($val);					
						$tipe = strtoupper($geometry->type);
						$coordinates = $geometry->coordinates;
						$srid = (empty($model->data->srid)?4326:$model->data->srid); //4326;																				
											
						$string = json_encode($coordinates);										
						//echo $string."\n";
						
						$string = preg_replace(['/([0-9\-\.]+),([0-9\-\.]+)/','/\[/','/\]/'], ['$1 $2','(',')'], $string);
						$string = str_replace(['((','))','),('], ['(',')',','], $string);
						
						//if (substr($tipe,-3) == 'GON')
						if ($tipe == 'MULTIPOLYGON')
						{
							$string = '('.$string.')';	
						}										
						
						//die($string);						
						
						$string = $tipe.' '.$string;
						
						$geom2 = $model->db->createCommand(
								"SELECT (ST_Multi(ST_Transform(ST_GeomFromText(:val,4326),:srid))) as g"
							)->bindValues([":val"=>$string,":srid"=>$srid])->queryScalar();
							
						$geom1 = $model->db->createCommand(
								"SELECT (ST_Transform(ST_GeomFromText(:val,4326),:srid)) as g"
							)->bindValues([":val"=>$string,":srid"=>$srid])->queryScalar();	
						
						//echo $string." ".$srid;
						//die($geom1);						
						
						if ($datatype > 2)
						{
							$val = $geom2;
						}
						else
						{																											
							$val = $geom1;
						}
						
						//$model->$key = $val;
					}
					
					if( $key != "_csrf" && !(empty($val) || $val == 'undefined'))
					{
						//$model->$key = 	is_numeric($val)?(is_int($val)?intval($val):floatval($val)):$val;	
					//	$model->$key = $val;
					}					
					
					
				}
				
				
				$geom_col = $module->geom_col;
				$model->$geom_col = $geom1;		
				
				if ($model->validate()) {					
					try {
						$res = $model->save();
					}
					catch (yii\db\Exception $e)
					{
						$model->$geom_col = $geom2;			
						$res = $model->save();
					}
				} else {					
					$err = $model->errors;
				}
								
			}
			elseif (count($post) == 1 && $model)
			{
				$res = $model->delete();
			}
			
			$err = $model->getErrors();			
			if (!empty($rrr))
			{
				$errors = array_merge($errors,$err);	
			}
			else
			{
				shell_exec("rm -R ".\Yii::getAlias($module->tileDir)."/*");	
			}
			
			/*
			if ($res)
			{
				$url = "http://127.0.0.1:1402/wilayah_kerja?r=123";
				$c = curl_init($url);
				curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
				$page = curl_exec($c);
				curl_close($c);	
			}
			*/
		} 
				
		return json_encode(['status'=>$res,'error'=>$errors,'gid'=>$model->gid]);
				
    }
}
