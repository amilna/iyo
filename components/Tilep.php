<?php
namespace amilna\iyo\components;

use Yii;
use yii\base\Component;
use yii\console\Application;
use yii\helpers\ArrayHelper;

class Tilep extends Component
{
	public $xmlDir = './'; /* mapnik xml directory */
	public $pyFile = './'; /* tilep.py file */
	public $tileURL = ''; /* baseurl for output tile */
	public $tileDir = ''; /* basedir for output tile */
	
	public $controller;
	public $view = 'error';
	
	public function getLonLat($xtile, $ytile, $zoom)
	{
		$n = pow(2, intval($zoom));
		$lon_deg = $xtile / $n * 360.0 - 180.0;
		$lat_deg = rad2deg(atan(sinh(pi() * (1 - 2 * $ytile / $n))));	
		return array($lon_deg,$lat_deg);
	}
	
	public function getTile($lon, $lat, $zoom)
	{
		$xtile = floor((($lon + 180) / 360) * pow(2, $zoom));
		$ytile = floor((1 - log(tan(deg2rad($lat)) + 1 / cos(deg2rad($lat))) / pi()) /2 * pow(2, $zoom));
		return array($zoom,$xtile,$ytile);
	}
	
	public function dumpTiles($xml,$bbox,$minzoom,$maxzoom,$lgrid,$urls = [])
	{
		$bbox = split(",",$bbox);
		$tiles = [];
		$min = [floatval($bbox[0]),floatval($bbox[1])];
		$max = [floatval($bbox[2]),floatval($bbox[3])];				
		
		for ($zoom = $minzoom;$zoom <= $maxzoom;$zoom++)
		{
			$t0 = $this->getTile($min[0], $min[1], $zoom);
			$t1 = $this->getTile($max[0], $max[1], $zoom);						
						
			for($x = $t0[1];$x<=$t1[1];$x++)
			{													
				for($y = $t0[2];$y>=$t1[2];$y--)
				{																					
					$tile = [$zoom,$x,$y];	
					
					$ok = true;
										
					if  ($zoom == 17) {
						if  ($x < 104378) {
							$ok = false;
						}
						else
						{	
							if ($x == 104378 && $y > 67769) {
								$ok = false;
							}
						}
					}	
					
					if  ($ok)
					{				
						if (!in_array($tile,$tiles))
						{
							array_push($tiles,$tile);
							$this->putTile($xml,$zoom,$x,$y);
							$this->putTile($xml,$zoom,$x,$y,"utf",$lgrid);
							
							foreach($urls as $n=>$u)
							{
								$this->putTms($n,$u,$zoom,$x,$y);								
							}
							
							echo $zoom."  ".$x."  ".$y." berhasil\n";
						}				
					}	
				}	
			}					
		}					
	}	
	
	public function putTms($name,$url,$zoom,$xtile,$ytile)
	{						
		$dir = $this->tileDir."/".$name."/".$zoom."/".$xtile;
		$bfile = $dir."/".$ytile;					
		
		if (!file_exists($dir))
		{
			mkdir($dir,0775,true);
		}
		
		$subject = $url;
		$pattern = "/\\[[a-z0-9,]+\\]/";		
		preg_match($pattern, $subject, $matches, PREG_OFFSET_CAPTURE);		
		
		if (count($matches) > 0)
		{
			eval("\$sf = ".str_replace(array("[",",","]"),array("['","','","']"),$matches[0][0]).";");			
			
			$r = rand(0,count($sf)-1);
			$url = str_replace($matches[0][0],$sf[$r],$url);
		}
		
		$input = str_replace(array("{z}","{x}","{y}"),array($zoom,$xtile,$ytile),$url);
		$output = $bfile.'.png';
		
		$content = file_get_contents($input);
		if ($content)
		{		
			file_put_contents($output, $content);
		}
		else
		{
			$dir = "./log/";						
			if (!file_exists($dir))
			{
				mkdir($dir,0775,true);
			}			
			$fh = fopen($dir.$name."_".$zoom."_".$xtile."_".$ytile, 'w');						
			fwrite($fh, $name."_".$zoom."_".$xtile."_".$ytile);		
			fclose($fh);				
		}									
	}	
	
	public function putTile($xml,$zoom,$xtile,$ytile,$type = false,$lgrid = false,$urls=[])
	{								
		$req = "/".$xml."/".$zoom."/".$xtile."/".$ytile.".".$type."?".$lgrid;
		preg_match('/^\/([a-zA-Z0-9_]+)\/(\d+)\/(\d+)\/(\d+)\.(png|json)?/', $req, $matches);
		
		if (empty($matches))
		{
			die();	
		}		
		
		$dir = $this->tileDir."/".$xml."/".$zoom."/".$xtile;
		$bfile = $dir."/".$ytile;						
				
		if ($type == 'utf' || $type == 'json')
		{		
			$file = $bfile.".json";	
		}
		else
		{						
			$file = $bfile.".png";
		}								
				
		$width = 256;
		$height = 256;
		$tile_size = 256;
		
		/*
		$xtile_s = ($xtile * $tile_size - $width/2) / $tile_size;
		$ytile_s = ($ytile * $tile_size - $height/2) / $tile_size;
		$xtile_e = ($xtile * $tile_size + $width/2) / $tile_size;
		$ytile_e = ($ytile * $tile_size + $height/2) / $tile_size;
		$s0 = $this->getLonLat($xtile_s, $ytile_s, $zoom);
		$e0 = $this->getLonLat($xtile_e, $ytile_e, $zoom);
		*/
		
		$s = $this->getLonLat($xtile, $ytile, $zoom);
		$e = $this->getLonLat($xtile+1, $ytile+1, $zoom);
				
		
		$s[0] = ($s[0]%360)+($s[0]-floor($s[0]));
		$e[0] = ($e[0]%360)+($e[0]-floor($e[0]));

		$s[0] = $s[0] >= 180? $s[0]-360:$s[0];
		$e[0] = $e[0] > 180? $e[0]-360:$e[0];
		 
		$bbox = $s[0].",".$e[1].",".$e[0].",".$s[1];				
		
		$lgrid = ($lgrid !== false?" -l ".$lgrid:"");								
		
		$ok = false;
		if (substr($this->xmlDir,0,4) == 'http')
		{
			preg_match('/iyo(\d+)_([a-zA-Z0-9_]+)/',$xml,$matches);
			$ok = shell_exec("python ".$this->pyFile." -i ".$this->xmlDir."/?id=".$matches[1]."&name=".$matches[2]." -o ".$bfile." -b ".$bbox.$lgrid);
		}
		else
		{
			if (file_exists($this->xmlDir."/".$xml.".xml"))
			{
				$ok = shell_exec("python ".$this->pyFile." -i ".$this->xmlDir."/".$xml.".xml -o ".$bfile." -b ".$bbox.$lgrid);				
			}	
		}
		
		
		if (!$ok)
		{
			$dir = "./log/";						
			if (!file_exists($dir))
			{
				mkdir($dir,0775,true);
			}			
			$fh = fopen($dir.$xml."_".$zoom."_".$xtile."_".$ytile, 'w');						
			fwrite($fh, $xml."_".$zoom."_".$xtile."_".$ytile);		
			fclose($fh);												
		}
		
				
		foreach($urls as $n=>$u)
		{			
			$this->putTms($n,$u,$zoom,$xtile,$ytile);
		}
			
	}
	
	public function createTile($xml,$zoom,$xtile,$ytile,$type = false,$clear = false,$lgrid = false,$urls=[])
	{						
		$dir = $this->tileDir."/".$xml."/".$zoom."/".$xtile;
		$bfile = $dir."/".$ytile;								
		header("Access-Control-Allow-Origin: *");	
				
		if (in_array($type,['utf','json']))
		{			
			$lgrid = $lgrid?$lgrid:'0';
			$lgrids = ($lgrid?split(",",$lgrid):[""]);
			header("Content-Type: text/json");
			$file = $bfile.($lgrids[0] == ""?"":"_").$lgrids[count($lgrids)-1].".json";									
		}
		else
		{				
			header("Content-Type: image/png");
			$file = $bfile.".png";
		}				
							
		if (file_exists($file) && !$clear)
		{			
			readfile($file);			
		}
		else
		{																					
			$this->putTile($xml,$zoom,$xtile,$ytile,$type,$lgrid,$urls);
			readfile($file);		
			
		}
		exit(0);

	}
	
	public function errorHandler()
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
		
		$tiles = explode("/",$matches[1]);
		$tile = $tiles[0];
		$z = $tiles[1];
		$x = $tiles[2];
		$y = $tiles[3];				
		
		$type = substr($req->url,strrpos($req->url,".")+1);		
		
		$module = Yii::$app->getModule('iyo');
		$tilep = new \amilna\iyo\components\Tilep();
		$tilep->xmlDir = \Yii::getAlias($module->xmlDir);
		$tilep->pyFile = \Yii::getAlias($module->pyFile);
		$tilep->tileDir = \Yii::getAlias($module->tileDir);
		$tilep->tileURL = \Yii::getAlias($module->tileURL);
		
		preg_match('/iyo(\d+)_([a-zA-Z0-9_]+)/',$tile,$matches);
		if (count($matches) > 0)
		{
			if ($module->xmlServer == 'nodejs')
			{
				$tilep->xmlDir = "http".(!empty($module->sslKey)?"s":"")."://".(!empty($module->xmlproxyhosts)?$module->xmlproxyhosts[0]:($module->xmlipaddress.":".$module->xmlports[0]));				
			}
			else
			{
				$tilep->xmlDir = "http".(!empty($module->sslKey)?"s":"")."://".$_SERVER['SERVER_NAME'].\yii\helpers\Url::toRoute('//iyo/layer/xml');
			}	
		}
				
		$urls = [];

		if (isset($urls[$tile]))
		{
			$urls = [$tile=>$urls[$tile]];
		}
			
		$tilep->createTile($tile,$z,$x,$y,$type,false,false,$urls);					
	}
}
