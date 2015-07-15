<?php
namespace amilna\iyo\widgets;

use Yii;
use yii\helpers\Html;
use yii\base\Widget;
use yii\helpers\Json;
use amilna\iyo\components\Process;
use amilna\iyo\models\Layer;
use amilna\iyo\models\Data;

class Map extends Widget
{    	
	public $viewPath = '@amilna/iyo/widgets/views/map';
	public $id = 'iyo';
	public $options = [];
	
	private $defoptions = [
		'name'=>'Amilna Map',
		'init'=>[
			'zoom'=>5,
			'center'=>[117, -2],
		],
		'minZoom'=>5,
		'maxZoom'=>19,
	];
	
	private $bundle;

    public function init()
    {        
        parent::init();
        $view = $this->getView();				
		$module = Yii::$app->getModule("iyo");
		$user_id = Yii::$app->user->id;				
				
		$comDir = \Yii::getAlias('@amilna/iyo/components');
		
		$ipaddr = $module->xmlipaddress;
		$sock = @fsockopen($ipaddr,$module->xmlports[0],$num,$error,1);
		if (!$sock && $module->xmlServer == 'nodejs') {
			$execFile = $comDir.'/exec';
			$allowedips = implode(',',$module->allowedips);
			$portsStr = '['.implode(',',$module->xmlports).']';
			$db = Yii::$app->db;
			$dbstr = $db->dsn.','.$db->tablePrefix.','.$db->username.','.$db->password;
			$geomuserstr = $module->geom_col.','.Yii::$app->user->id;
			$cmd = 'node "'.$comDir.'/xmlin.js" "'.$allowedips.'" "'.$ipaddr.'" "'.$portsStr.'" "'.$execFile.'" "'.$dbstr.'" "'.$geomuserstr.'" '.(!empty($module->sslKey)?'"'.$module->sslKey.'"':'').' '.(!empty($module->sslCert)?'"'.$module->sslCert.'"':''); 			
			$process = new Process($cmd);
		}
		
		$ipaddr = $module->ipaddress;
		$sock = @fsockopen($ipaddr,$module->ports[0],$num,$error,1);
		if (!$sock && $module->tileServer == 'nodejs') {						
			$pyFile = \Yii::getAlias($module->pyFile);
			$xmlDir = \Yii::getAlias($module->xmlDir);
			$tileDir = \Yii::getAlias($module->baseDir);
			$portsStr = '['.implode(',',$module->ports).']';
			
			if ($module->xmlServer == 'nodejs')
			{
				$xmlUrl = "http".(!empty($module->sslKey)?"s":"")."://".(!empty($module->xmlproxyhosts)?$module->xmlproxyhosts[0]:($module->xmlipaddress.":".$module->xmlports[0]));				
			}
			else
			{
				$xmlUrl = "http".(!empty($module->sslKey)?"s":"")."://".$_SERVER['SERVER_NAME'].\yii\helpers\Url::toRoute('//iyo/layer/xml');
			}			
			
			$cmd = 'node "'.$comDir.'/tilepin.js" "'.$xmlDir.'" "'.$pyFile.'" "'.$tileDir.'" "'.$ipaddr.'" "'.$portsStr.'" "'.$xmlUrl.'" "'.$module->maxZoomCache.'" '.(!empty($module->sslKey)?'"'.$module->sslKey.'"':'').' '.(!empty($module->sslCert)?'"'.$module->sslCert.'"':'');						
			$process = new Process($cmd);
		}				
		
		$bundle = MapAsset::register($view);
		$this->bundle = $bundle;				
		
		$options = $this->defoptions;
		if (intval($this->id) > 0)
		{
			$map = Yii::$app->db->createCommand("SELECT title,config FROM {{%iyo_map}} WHERE id = :id")->bindValues([':id'=>$this->id])->query();			
			foreach ($map as $m)
			{
				$options = json_decode($m['config'],true);
				$options['name'] = $m['title'];
			}
		}				
				
		$this->options = array_merge($options,(!is_array($this->options)?json_decode($this->options,true):$this->options));								
		
		if (isset($this->options['name']) && !isset($this->options['id']))
		{
			$this->id = preg_replace('/[^a-zA-Z0-9]/','_',strtolower($this->options['name']));			
		}
		elseif (isset($this->options['id']))
		{
			$this->id = $this->options['id'];
		}
		
			
		
		if (isset($this->options['layers']))
		{			
			foreach ($this->options['layers'] as $i=>$l)
			{
				if (isset($l['layerId']))
				{					
					$layer = Layer::findOne($l['layerId']);
					if ($layer)
					{																	
						$this->options['layers'][$i]['name'] = isset($this->options['layers'][$i]['name'])?$this->options['layers'][$i]['name']:$layer->title;	
						$this->options['layers'][$i]['dataId'] = $layer->data_id;	
						$this->options['layers'][$i]['geomtype'] = Data::itemAlias("geomtype",$layer->data->type);	
						$this->options['layers'][$i]['type'] = in_array($layer->data->type,[0,3])?'geojson':'tile';	
						
						if ($module->tileServer == 'nodejs')
						{
							$this->options['layers'][$i]['urls'] = ["http".(!empty($module->sslKey)?"s":"")."://".($module->proxyhost?$module->proxyhost:$module->ipaddress.":".$module->ports[0])."/iyo".$layer->id."_".preg_replace('/[^a-zA-Z0-9]/','_',strtolower($layer->title))."/{z}/{x}/{y}.".(in_array($layer->data->type,[0,3])?'json':'png')];	
						}
						else
						{
							$baseUrl = \Yii::getAlias($module->baseUrl);
							$this->options['layers'][$i]['urls'] = [$baseUrl."/iyo".$layer->id."_".preg_replace('/[^a-zA-Z0-9]/','_',strtolower($layer->title))."/{z}/{x}/{y}.".(in_array($layer->data->type,[0,3])?'json':'png')];
						}	
						
						$lconfigs = json_decode($layer->config,true);
						if (isset($lconfigs[0]['type']))
						{							
							if ($module->tileServer == 'nodejs')
							{
								$this->options['layers'][$i]['urls'] = ["http".(!empty($module->sslKey)?"s":"")."://".($module->proxyhost?$module->proxyhost:$module->ipaddress.":".$module->ports[0])."/iyo".$layer->id."_".preg_replace('/[^a-zA-Z0-9]/','_',strtolower($layer->title))."/{z}/{x}/{y}.".(in_array($lconfigs[0]['type'],['geojson'])?'json':'png')];
							}
							else
							{
								$baseUrl = \Yii::getAlias($module->baseUrl);
								$this->options['layers'][$i]['urls'] = [$baseUrl."/iyo".$layer->id."_".preg_replace('/[^a-zA-Z0-9]/','_',strtolower($layer->title))."/{z}/{x}/{y}.".(in_array($lconfigs[0]['type'],['geojson'])?'json':'png')];								
							}
						}
						
						foreach ($lconfigs[0] as $p=>$v)
						{
							$this->options['layers'][$i][$p] = $v;
						}												
						unset($l['layerId']);								
					}
					else
					{
						unset($this->options['layers'][$i]);									
					}					
				}				
				
			}							
		}											
		
		$path = \Yii::getAlias($module->basePath);
		$this->options['reqUrl'] = $path;	
				
		$options = json_encode($this->options);
		
		$script = '	
			var '.$this->id.';
			var csrfToken = $(\'meta[name="csrf-token"]\').attr("content");
			var SA = new sA({baseUrl:"'.$bundle->baseUrl.'/js/",plugins:["Map"]});								
			SA.init(function(){						
				'.$this->id.' = new sA.Map('.$options.');			
			});					
						
		' . PHP_EOL;
		$view->registerJs($script, yii\web\View::POS_END);
						
		echo $this->render($this->viewPath,["id"=>$this->id,"options"=>$this->options]);		
    }
        
}
