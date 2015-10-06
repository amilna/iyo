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
		'editable'=>false,
	];
	
	private $bundle;

    public function init()
    {        
        parent::init();
        $view = $this->getView();				
		$module = Yii::$app->getModule("iyo");
		$user_id = Yii::$app->user->id;				
				
		$comDir = \Yii::getAlias('@amilna/iyo/components');
		$tileURL = \Yii::getAlias($module->tileURL);
		
		$sock = false;		
		$ipaddr = $module->ipaddress;
		foreach ($module->ports as $port)
		{
			$sock = (!$sock?false:@fsockopen($ipaddr,$port,$num,$error,1));
		}
		
		//$sock = true;
		
		if (!$sock && in_array($module->tileServer,['webpy','nodejs'])) {						
			$pyFile = \Yii::getAlias($module->pyFile);
			$webpyFile = \Yii::getAlias($module->webpyFile);
			$xmlDir = \Yii::getAlias($module->xmlDir);
			$tileDir = \Yii::getAlias($module->tileDir);
			$portsStr = implode(',',$module->ports);
			
			$db = Yii::$app->db;
			$dbdsn = $db->dsn;
			$dbpfx = $db->tablePrefix;
			$dbusr = $db->username;
			$dbpwd = $db->password;
			$geomCol = $module->geom_col;
			$webDir = \Yii::getAlias('@webroot');			
			$execFile = $comDir.'/exec';				
			
			if ($module->tileServer == 'webpy') {																
				foreach ($module->ports as $port)
				{										
					$cmd = 'python "'.$webpyFile.'" -x "'.$xmlDir.'" -d "'.$webDir.'" -t "'.$tileDir.'" -a "'.$ipaddr.'" -p "'.$port.'" -T "'.$tileURL.'" -E "'.$execFile.'" -D "'.$dbdsn.'" -P '.$dbpfx.' -U '.$dbusr.' -W '.$dbpwd.'  -G '.$geomCol.' -c "'.$module->maxZoomCache.'" '.(!empty($module->sslKey)?'-K "'.$module->sslKey.'"':'').' '.(!empty($module->sslCert)?'-C "'.$module->sslCert.'"':'');
					$process = new Process($cmd);
				}				
			}
			else
			{
				if ($module->xmlServer == 'nodejs')
				{					
					$sock = false;
					$ipaddr = $module->xmlipaddress;					
					foreach ($module->xmlports as $xport)
					{
						$sock = (!$sock?false:@fsockopen($ipaddr,$xport,$num,$error,1));
					}
					
					if (!$sock) {						
						$allowedips = implode(',',$module->allowedips);
						$portsStr = '['.implode(',',$module->xmlports).']';						
						$dbstr = $db->dsn.','.$db->tablePrefix.','.$db->username.','.$db->password;
						$geomuserstr = $module->geom_col.','.Yii::$app->user->id;
						$cmd = 'node "'.$comDir.'/xmlin.js" "'.$allowedips.'" "'.$ipaddr.'" "'.$portsStr.'" "'.$execFile.'" "'.$dbstr.'" "'.$geomuserstr.'" '.(!empty($module->sslKey)?'"'.$module->sslKey.'"':'').' '.(!empty($module->sslCert)?'"'.$module->sslCert.'"':''); 			
						$process = new Process($cmd);						
					}
					
					$xmlUrl = "http".(!empty($module->sslKey)?"s":"")."://".(!empty($module->xmlproxyhosts)?$module->xmlproxyhosts[0]:($module->xmlipaddress.":".$module->xmlports[0]));				
				}
				else
				{
					$xmlUrl = "http".(!empty($module->sslKey)?"s":"")."://".$_SERVER['SERVER_NAME'].\yii\helpers\Url::toRoute('//iyo/layer/xml');
					$xmlUrl = str_replace('/iyo/layer/xml/','/iyo/layer/xml',$xmlUrl);
				}			
				
				$cmd = 'node "'.$comDir.'/tilepin.js" "'.$xmlDir.'" "'.$pyFile.'" "'.$tileDir.'" "'.$ipaddr.'" "['.$portsStr.']" "'.$xmlUrl.'" "'.$module->maxZoomCache.'" '.(!empty($module->sslKey)?'"'.$module->sslKey.'"':'').' '.(!empty($module->sslCert)?'"'.$module->sslCert.'"':'');						
				$process = new Process($cmd);								
			}
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
						$gtype = Data::itemAlias("geomtype",$layer->data->type);	
						
						if ($layer->data->type == 6)
						{
							$ltype = 'rastertile';
							$epsgs = [];
							$tilename = "";
							$imgDb = $comDir.'/indeks.db';
							if (file_exists($imgDb))
							{
								$sqlDb = new \yii\db\Connection([
									'dsn' => 'sqlite:'.$imgDb,
								]);				
								
								$metadata = json_decode($layer->data->metadata,true);								
								if (isset($metadata["tilename"]))
								{
									$sql = 'SELECT epsg from indeks WHERE name = :name';				
									$epsgs = $sqlDb->createCommand($sql)->bindValues([':name'=>$metadata["tilename"]])->queryAll();		
									$tilename = $metadata["tilename"];
								}																
							}	
							
							$lconfigs = json_decode($layer->config,true);
							if (isset($lconfigs[0]['tilename']))
							{							
								$tilename = $lconfigs[0]['tilename'];							
							}
							if (isset($lconfigs[0]['epsgs']))
							{							
								$epsgs = $lconfigs[0]['epsgs'];							
							}
							
							$this->options['layers'][$i]['epsgs'] = $epsgs;
							$this->options['layers'][$i]['tilename'] = $tilename;
							$this->options['layers'][$i]['type'] = $ltype;
																	
							if (in_array($module->tileServer,['webpy','nodejs']))
							{								
								$np = 0;								
								$this->options['layers'][$i]['urls'] = [];
								for ($np=0;$np<count($module->ports);$np++)
								{
									$this->options['layers'][$i]['urls'][] = "http".(!empty($module->sslKey)?"s":"")."://".(!empty($module->proxyhosts)?$module->proxyhosts[$np]:$module->ipaddress.":".$module->ports[$np]).$tileURL."/".$tilename."/{epsgs}/{z}/{x}/{y}.png";
								}
							}
							else
							{								
								$this->options['layers'][$i]['urls'] = [$tileURL."/".$tilename."/{epsgs}/{z}/{x}/{y}.png"];								
							}
						}
						else
						{
												
							$this->options['layers'][$i]['dataId'] = $layer->data_id;	
							
							$ltype = in_array($layer->data->type,[0,3])?'geojson':'tile';
							
							$lconfigs = json_decode($layer->config,true);
							if (isset($lconfigs[0]['type']))
							{							
								$ltype = $lconfigs[0]['type'];							
							}
							
							if (isset($lconfigs[0]['geomtype']))
							{							
								$gtype = $lconfigs[0]['geomtype'];							
							}
							
							$this->options['layers'][$i]['type'] = $ltype;
							$this->options['layers'][$i]['geomtype'] = $gtype;
							
							if (isset($lconfigs[0]['dataquery']))
							{							
								$this->options['layers'][$i]['dataquery'] = $lconfigs[0]['dataquery'];							
							}
							
							if (in_array($module->tileServer,['webpy','nodejs']))
							{								
								$np = 0;								
								$this->options['layers'][$i]['urls'] = [];
								for ($np=0;$np<count($module->ports);$np++)
								{
									$this->options['layers'][$i]['urls'][] = "http".(!empty($module->sslKey)?"s":"")."://".(!empty($module->proxyhosts)?$module->proxyhosts[$np]:$module->ipaddress.":".$module->ports[$np]).$tileURL."/iyo".$layer->id."_".preg_replace('/[^a-zA-Z0-9]/','_',strtolower($layer->title))."/{z}/{x}/{y}.".(in_array($ltype,['geojson'])?'json':'png');
								}
							}
							else
							{								
								$this->options['layers'][$i]['urls'] = [$tileURL."/iyo".$layer->id."_".preg_replace('/[^a-zA-Z0-9]/','_',strtolower($layer->title))."/{z}/{x}/{y}.".(in_array($ltype,['geojson'])?'json':'png')];								
							}												
							
							$n = 0;
							foreach ($lconfigs as $lc)
							{							
								foreach ($lc as $p=>$v)
								{
									if ((substr($p,0,6) == 'fields' || $p == 'data') && (in_array($ltype,['geojson'])?($n == 0):true))								
									{
										$this->options['layers'][$i][$p] = $v;
									}
									elseif ($p == 'rules' && (in_array($ltype,['geojson'])?($n == 0):true))								
									{									
										$this->options['layers'][$i][$p] = (isset($this->options['layers'][$i][$p])?$this->options['layers'][$i][$p]:[]);
										$this->options['layers'][$i][$p] = array_merge_recursive($this->options['layers'][$i][$p],$v);
									}								
								}
								$n += 1;
							}
							
							if ($l['layerId'] == 25)
							{
								//print_r($this->options['layers'][$i]);
								//die($ltype);							
							}
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
			var SA = new sA({baseUrl:"'.$bundle->baseUrl.'/js/'.(YII_DEBUG ?'sa-src/':'').'",plugins:["'.(YII_DEBUG ?'Map':'Map.min').'"]});		
			SA.init(function(){						
				'.$this->id.' = new sA.Map('.$options.');			
			});					
						
		' . PHP_EOL;
		$view->registerJs($script, yii\web\View::POS_END);
						
		echo $this->render($this->viewPath,["id"=>$this->id,"options"=>$this->options]);		
    }
        
}
