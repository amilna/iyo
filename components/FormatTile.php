<?php
namespace amilna\iyo\components;

use Yii;
use yii\base\Component;
use yii\console\Application;
use yii\helpers\ArrayHelper;
use amilna\iyo\models\Data;
use amilna\iyo\models\Layer;
use amilna\iyo\models\LayDat;

class FormatTile extends Component
{		
	private $dbname = null; 
	private $prefix = null;
	private $db = null;		
	private $tileURL = null;		
	private $id = false; 
	private $isdata = false;
	private $z = false;
	private $x = false;
	private $y = false;
	private $xml = false;
	
	private $tileDir = null;
	private $tileServer = null; /* webpy or nodejs or apache */
	private $proxyhosts = [];
	private $ports = [];
	private $ipaddress = null;
	private $sslKey = null;
	
	public function __construct($dsn,$tablePrefix,$username,$password,$param)
	{																
		$params = explode(":",$param);
		$this->tileURL = \Yii::getAlias($params[0]);
		$this->id = $params[1];		
		$this->isdata = $params[2];
		$this->z = $params[3];						
		$this->x = $params[4];				
		$this->y = $params[5];				
		$this->xml = $params[6];
		
		$this->tileDir = \Yii::getAlias($params[7]);
		$this->tileServer = $params[8];
		$this->proxyhosts = $params[9] == ''?[]:explode(',',$params[9]);
		$this->ports = explode(',',$params[10]);
		$this->ipaddress = $params[11];
		$this->sslKey = $params[12];		
		
		preg_match('/dbname\=(.*)?/', $dsn, $matches);			
		$this->dbname = $matches[1];
		$this->prefix = $tablePrefix;						
		
		$this->db = new \yii\db\Connection([
				'dsn' => $dsn,
				'username' => $username,
				'password' => $password,
				'tablePrefix'=>	$tablePrefix		
			]);																						
		
	}		

	public function clearTile()
    {	
		$id = $this->id;		
		$isdata = $this->isdata;
		$z = $this->z;						
		$x = $this->x;				
		$y = $this->y;				
		$xml = $this->xml;		
		
		$time = time();		
		$tileURL = $this->tileURL;
		
		if (!$isdata)
		{
			$lids = [$id];	
		}
		else
		{						
			$sql = "SELECT layer_id FROM ".LayDat::tableName()." 				
				WHERE data_id = :id";				
			$lids = $this->db->createCommand($sql)->bindValues([':id'=>$id])->queryAll();
		}
		
		foreach ($lids as $lid)
		{	
			$sql = "SELECT l.id as id,l.title as title,d.type as datatype,d.metadata as metadata FROM ".Layer::tableName()." as l 
				LEFT JOIN ".Data::tableName()." as d ON l.data_id=d.id 
				WHERE l.id = :id";				
			$layer = $this->db->createCommand($sql)->bindValues([':id'=>$lid])->queryOne();			
			if ($layer)
			{								
				if (in_array($this->tileServer,['webpy','nodejs']))
				{							
					for ($np=0;$np<count($this->ports);$np++)
					{
						if ($layer['datatype'] == 6)
						{
							$metadata = json_decode($layer['metadata'],true);
							$url = "http".(!empty($this->sslKey)?"s":"")."://".(!empty($this->proxyhosts)?$this->proxyhosts[$np]:$this->ipaddress.":".$this->ports[$np]).$tileURL."/".$metadata['tilename'];												
						}
						else
						{
							$url = "http".(!empty($this->sslKey)?"s":"")."://".(!empty($this->proxyhosts)?$this->proxyhosts[$np]:$this->ipaddress.":".$this->ports[$np]).$tileURL."/iyo".$layer['id']."_".preg_replace('/[^a-zA-Z0-9]/','_',strtolower($layer['title']));												
						}
						
						if ($z && $x && $y)
						{
							$urls = [];
							foreach (['json','png'] as $type)
							{
								$url .= "/".$z."/".$x."/".$y.".".(in_array($layer['datatype'],[0,3])?'json':$type);
								$url .= "?r=".$time.($xml?"&x=".$time:"");
								if (!in_array($url,$urls))
								{
									$urls[] = $url;	
								}
							}
						}
						else
						{
							$url .= "?r=".$time.($xml?"&x=".$time:"");	
							$urls = [$url];
						}
						
						foreach ($urls as $url)
						{
							$c = curl_init($url);
							curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
							$page = curl_exec($c);
							curl_close($c);	
						}
					}				
				}
				else
				{												
					if ($layer['datatype'] == 6)
					{
						$metadata = json_decode($layer['metadata'],true);
						$dir = $this->tileDir."/".$metadata['tilename'];				
					}
					else
					{	
						$dir = $this->tileDir."/iyo".$layer['id']."_".preg_replace('/[^a-zA-Z0-9]/','_',strtolower($layer['title']));				
					}	
					if ($z && $x && $y)
					{
						$dir .= "/".$z."/".$x."/".$y;
					}				
					shell_exec("rm -R ".$dir);	
				}
			}
		}		
	}	
		
}
