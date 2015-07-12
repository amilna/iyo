<?php

namespace amilna\iyo\controllers;

use Yii;
use amilna\iyo\models\Data;
use amilna\iyo\models\Layer;
use amilna\iyo\models\LayerSearch;
use amilna\iyo\components\FormatXml;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * LayerController implements the CRUD actions for Layer model.
 */
class LayerController extends Controller
{
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
     * Lists all Layer models.
     * @params string $format, array $arraymap, string $term
     * @return mixed
     */
    public function actionIndex($format= false,$arraymap= false,$term = false)
    {
        $searchModel = new LayerSearch();                
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
     * Displays a single Layer model.
     * @param integer $id
     * @additionalParam string $format
     * @return mixed
     */
    public function actionView($id,$format= false)
    {
        $model = $this->findModel($id);
        
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
     * Creates a new Layer model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Layer();        
        $model->time = date("Y-m-d H:i:s");	
        $model->author_id = Yii::$app->user->id;
        $model->isdel = 0;
		
		$post = Yii::$app->request->post();
		if ($post)
		{
			if (is_array($post['Layer']['tags']))
			{
				$post['Layer']['tags'] = implode(",",$post['Layer']['tags']);
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

    /**
     * Updates an existing Layer model.
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
			if (is_array($post['Layer']['tags']))
			{
				$post['Layer']['tags'] = implode(",",$post['Layer']['tags']);
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

    /**
     * Deletes an existing Layer model.
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
     * Finds the Layer model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Layer the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Layer::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
    
    public function actionXml($id,$name)
    {
        $module = Yii::$app->getModule('iyo');
        if (in_array($_SERVER['REMOTE_ADDR'],$module->allowedips))
		{			
			$db = Yii::$app->db;
			$format = new FormatXml($db->dsn,$db->tablePrefix,$db->username,$db->password,$id.':'.$name.':'.$module->geom_col.':'.Yii::$app->user->id);
			$xml = $format->getXml();
			if ($xml)
			{
				Header('Content-type: application/xml');				
				die($xml);		
			}
			else
			{
				throw new NotFoundHttpException('The requested page does not exist.');
			}
		}
		else
		{
			throw new NotFoundHttpException('The requested page does not exist.');
		}		        
    }
    
    public function actionDatas($search = null, $id = null) {
		$userClass = Yii::$app->getModule('iyo')->userClass;
		$out = ['more' => false];
		if (!is_null($search)) {
			
			$query = new Data();
			$query = $query->find();
			$query->select(["{{%iyo_data}}.id","concat({{%iyo_data}}.title,' (',{{%iyo_data}}.description,')') AS text"])				
				->leftJoin($userClass::tableName(),$userClass::tableName().".id = {{%iyo_data}}.id")
				->leftJoin("{{%profile}}","{{%profile}}.user_id = ".$userClass::tableName().".id")
				->andwhere("lower(concat(".$userClass::tableName().".username,{{%profile}}.name,{{%iyo_data}}.title,{{%iyo_data}}.description,{{%iyo_data}}.remarks,{{%iyo_data}}.metadata)) LIKE '%" . strtolower($search) ."%'")
				//->andwhere(SiteNode::tableName().".owner_id is null")
				->limit(20);			
			$out['results'] = $query->asArray()->all();			
		}		
		elseif ($id > 0) {			
			$data = Data::findOne($id);
			if ($data)
			{
				$out['results'] = ['id' => $id, 'text' => $data->title." (".$data->description.")"];
			}
			else
			{
				$out['results'] = [];
			}
		}		
		else {
			$out['results'] = ['id' => 0, 'text' => 'No matching records found'];
		}
		echo json_encode($out);
	}
}
