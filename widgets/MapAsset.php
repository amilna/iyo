<?php
namespace amilna\iyo\widgets;

use Yii;
use yii\web\AssetBundle;

class MapAsset extends AssetBundle
{
    public $sourcePath = '@amilna/iyo/widgets/assets';
	
	public $publishOptions = [
        'forceCopy' => YII_DEBUG,
    ];
    
    public $css = [        
        'css/map.css',               
        'css/ol.css',               
    ];
         
	
    public $depends = [
        'yii\web\YiiAsset',
        'yii\web\JqueryAsset',        
        'yii\bootstrap\BootstrapAsset',
    ];

    public function init()
    {
        parent::init();

        $this->js[] = !YII_DEBUG ? 'js/ol.js' : 'js/ol-debug.js';       
        //$this->js[] = 'js/ol.js';   
        $this->js[] = 'js/turf.min.js';        
        $this->js[] = !YII_DEBUG ? 'js/ol.utfgrid.min.js' : 'js/sa-src/ol.utfgrid.js';       
        $this->js[] = !YII_DEBUG ? 'js/sA.min.js' : 'js/sa-src/sA.js';        
    }    
}
