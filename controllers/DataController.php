<?php

namespace amilna\iyo\controllers;

use Yii;
use amilna\iyo\models\Data;
use amilna\iyo\models\DataSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * DataController implements the CRUD actions for Data model.
 */
class DataController extends Controller
{
    public $controller;
	public $view = 'error';
	
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Lists all Data models.
     * @params string $format, array $arraymap, string $term
     * @return mixed
     */
    public function actionIndex($format= false,$arraymap= false,$term = false)
    {		        
        $searchModel = new DataSearch();                
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
    public function actionView($id,$format= false)
    {
        $model = $this->findModel($id);                
        
        if (in_array($model->status,[1,3]) && $model->type < 6)
        {
			return $this->redirect(['//iyo/record/index','data'=>$id]);
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

    /**
     * Creates a new Data model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Data();
		$model->time = date("Y-m-d H:i:s");	
        $model->author_id = Yii::$app->user->id;
        $model->isdel = 0;
        $model->type = 0;
        $model->status = 0;        
		$model->metadata = '{"columns":[{"name":"title","type":"varchar(65)","options":""},{"name":"remarks","type":"text","options":""}]}';
		
		$post = Yii::$app->request->post();				
		if ($post)
		{										
			if (is_array($post['Data']['tags']))
			{
				$post['Data']['tags'] = implode(",",$post['Data']['tags']);
			}
			
			if ($model->load($post))        
			{
				$metadata = json_decode($model->metadata,true);								
				if (!empty($post['filesrc']))
				{
					$metadata["srcfile"] = $post['filesrc'];
				}
				
				if ($model->type == 6 )
				{
					
					if (!isset($metadata["tilename"]))
					{
						$metadata["tilename"] = $model->title;	
					}
					else
					{
						if (empty($metadata["tilename"]))
						{
							$metadata["tilename"] = $model->title;	
						}
					}										
					$metadata["tilename"] = preg_replace('/[^a-zA-Z0-9]/','_',strtolower($metadata["tilename"]));
				}
				$model->metadata = json_encode($metadata);
				
				if ($model->save()) 
				{
					return $this->redirect(['view', 'id' => $model->id]);
				}            
			}
		}
			
		return $this->render('create', [
			'model' => $model,
		]);
	
    }

    /**
     * Updates an existing Data model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
		
		$post = Yii::$app->request->post();
		if ($post)
		{							
			if (is_array($post['Data']['tags']))
			{
				$post['Data']['tags'] = implode(",",$post['Data']['tags']);
			}
			
			if ($model->load($post))        
			{
				$metadata = json_decode($model->metadata,true);								
				if (!empty($post['filesrc']))
				{
					$metadata["srcfile"] = $post['filesrc'];
				}
				
				if ($model->type == 6 )
				{
					
					if (!isset($metadata["tilename"]))
					{
						$metadata["tilename"] = $model->title;	
					}
					else
					{
						if (empty($metadata["tilename"]))
						{
							$metadata["tilename"] = $model->title;	
						}
					}										
					$metadata["tilename"] = preg_replace('/[^a-zA-Z0-9]/','_',strtolower($metadata["tilename"]));
				}
				
				$model->metadata = json_encode($metadata);
				
				if ($model->save()) 
				{
					return $this->redirect(['view', 'id' => $model->id]);
				}            
			}
		}
        
        return $this->render('update', [
			'model' => $model,
		]);
    }

    /**
     * Deletes an existing Data model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {        
		$model = $this->findModel($id);        
        $model->isdel = 1;
        $model->save();
        //$model->delete(); //this will true delete
        
        return $this->redirect(['index']);
    }

    /**
     * Finds the Data model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Data the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Data::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
    
    /**
     * Check if a file is able to be imported as postgis geometry.
     * If check is successful, it will send true.
     * @param string $filename
     * @return json boolean
     */
    public function actionImportable($filename = false)
    {        						
        return Data::checkFileExt($filename);
    }
      
    public function actionTilep($tile,$z,$x,$y,$type = false,$clear = false,$lgrid = false)
    {
		$module = Yii::$app->getModule('iyo');
		$tilep = new \amilna\iyo\components\Tilep();
		$tilep->xmlDir = \Yii::getAlias($module->xmlDir);
		$tilep->pyFile = \Yii::getAlias($module->pyFile);
		$tilep->tileDir = \Yii::getAlias($module->tileDir);
		$tilep->tileURL = \Yii::getAlias($module->tileURL);
		
		$urls = $module->urls;
		//$urls = [];

		if (isset($urls[$tile]))
		{
			$urls = [$tile=>$urls[$tile]];
		}
			
		$tilep->createTile($tile,$z,$x,$y,$type,$clear,$lgrid,$urls);			
	}

	
	public function actionError()
    {		
		http_response_code(200);
		$req = Yii::$app->request;
		preg_match('/\/tile\/(.*)\./', $req->url, $matches);			
		if (count($matches) < 2)
		{			
			
			$this->controller = Yii::$app->requestedAction->controller;
			$this->view = '@app/views/site/error';			
			return \yii\web\ErrorAction::run();						
		}
		
		$tilep = new \amilna\iyo\components\Tilep();
		$tilep->errorHandler();		
	}
	
	public function actionGetshp($id,$title="") 
    {        															
		$model = $this->findModel($id);        
		
		if ($model)
		{
			$userid = Yii::$app->user->id;
			$date = date('Ymd');
			$enfix = $userid.'_'.$date;
				
			$path = Yii::getAlias("@common/../backend/runtime");						
			$fileshp = $path."/".(empty($title)?'data_'.$id:$title).'_'.$enfix;						
			
			preg_match('/dbname\=(.*)?/', Yii::$app->db->dsn, $matches);																		
			$dbname = $matches[1];
			$tablePrefix = Yii::$app->db->tablePrefix;
			
			$query = 'select * from '.$tablePrefix.'iyo_data_'.$id;
			
			$post = Yii::$app->request->post();
			if ($post)
			{							
				if (isset($post['query']))
				{
				//	$query .= ' where '.$post['query'];
				}
			}																		
						
			$fileshp = \amilna\yap\Helpers::shellvar($fileshp);
			$dbname = \amilna\yap\Helpers::shellvar($dbname);
			$query = \amilna\yap\Helpers::shellvar($query);			
												
			$pgsql2shp = shell_exec("pgsql2shp -f ".$fileshp." ".$dbname.' "'.$query.'"');											
			
			$path = false;
			$result = false;
			
			$files_to_zip = glob($fileshp."*");
			
			if (file_exists($fileshp.".shp"))
			{																															
				$module = Yii::$app->getModule('iyo');
				$ddir = Yii::getAlias($module->uploadDir).'/files';
				$durl = Yii::getAlias($module->uploadURL).'/files';
				
				if (!file_exists($ddir))
				{
					mkdir($ddir, 0775);
				}				
					
				$result = \amilna\iyo\components\FormatData::create_zip($files_to_zip,$ddir.'/'.basename($fileshp.".zip"),true);													
			}
			
			foreach ($files_to_zip as $f)
			{
				unlink($f);
			}																					
							
			
			if ($result)
			{																								
				$path = $durl.'/'.basename($fileshp.".zip");	
				
				return $path;			
			}
			
			throw new NotFoundHttpException('The requested data can not be downloaded.');			
					
		}
				
	}
	
        
}
