<?php
	use yii\helpers\Html;
	
	$this->params['no-content-header'] = true;
	$module = Yii::$app->getModule("iyo");
?>

<style>
@media print {	
	.content-wrapper,.content {		
		background-color:white!important;
		border:0px solid white!important;		
	}
}	
</style>

<div class="iyo-default-index">
    <?php
		//$options = Yii::$app->db->createCommand("SELECT config FROM {{%iyo_map}} WHERE id = 1")->queryScalar();
		//$mapOptions = json_decode($options,true);
		//echo amilna\iyo\widgets\Map::widget(['options'=>$mapOptions]);
		$tileURL = \Yii::getAlias($module->tileURL);
		echo amilna\iyo\widgets\Map::widget(['options'=>[
			'name'=>'Peta Dasar',
			'id'=>'peta_dasar',
			'init'=>[
				'zoom'=>5,
				'center'=>[117, -2],//[103.74, -3.30],
			],
			'geom_col'=>$module->geom_col,
			'minZoom'=>5,
			'maxZoom'=>19,
			'baseMaps'=>[				
				[
					'name'=>'ArcGIS Online (Satellite)',
					'source'=>'www.arcgisonline.com',
					'url'=>"http://[services,server].arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}",
				],
				[
					'name'=>'Openstreet Map',
					'source'=>'www.openstreetmap.org',
					'url'=>"http://[a,b,c].tile.openstreetmap.org/{z}/{x}/{y}.png",
				],
			],
			'baseIndex'=>0,
			'layers'=>[				
				[
					'name'=>'Ini cuma layer sample yang dibuat dengan konfigurasi xml melalui folder xml',
					'type'=>'tile',					
					'geomtype'=>'Polygon',
					'urls'=>["http".(!empty($module->sslKey)?"s":"")."://".(!empty($module->proxyhosts)?$module->proxyhosts[0]:($module->ipaddress.":".$module->ports[0])).$tileURL."/world_style/{z}/{x}/{y}.png"],
					//'urls'=>["http://127.0.0.1:1400/yii2/webgisPEP/backend/web/tile/citrates/{z}/{x}/{y}.png"],					
					
					'fields'=>[
						[
							'name'=>'NAME',
							'alias'=>'Country Name',
						],
						[
							'name'=>'ABBREV',
							'alias'=>'Abbreviation',
						],
						[
							'name'=>'POP_EST',
							'alias'=>'Population',
						]
					],
					 					
					'opacity'=>0.7,
					'visible'=>true
				],								
			]
		
		]]);                      
    ?>
    <div class="row well">
		<div class="col-sm-3">
			<h2>Data</h2>

			<p>Manage and upload GIS data (shp,kml,gpx,geojson) to be used as layer.</p>

			<p><?= Html::a(Yii::t('app','Create Data'),['//iyo/data/create'],["class"=>"btn btn-warning"])?>
			<?= Html::a(Yii::t('app','Manage Data'),['//iyo/data/index'],["class"=>"btn btn-primary"])?></p>
		</div>
		<div class="col-sm-3">
			<h2>Layer</h2>

			<p>Manage and set layer title, data related to, rules (style), etc  to be used as map layer.</p>

			<p><?= Html::a(Yii::t('app','Create Layer'),['//iyo/layer/create'],["class"=>"btn btn-warning"])?>
			<?= Html::a(Yii::t('app','Manage Layer'),['//iyo/layer/index'],["class"=>"btn btn-primary"])?></p>
		</div>
		<div class="col-sm-3">			
			<h2>Map</h2>

			<p>Manage maps.</p>

			<p><?= Html::a(Yii::t('app','Create Map'),['//iyo/map/create'],["class"=>"btn btn-warning"])?>
			<?= Html::a(Yii::t('app','Manage Map'),['//iyo/map/index'],["class"=>"btn btn-primary"])?></p>		
		</div>
		<div class="col-sm-3">			
			<h2>Page</h2>

			<p>Manage customizable map pages with your own custom javascript. This page should be restricted, and available to web admin only.</p>

			<p><?= Html::a(Yii::t('app','Create Page'),['//iyo/page/create'],["class"=>"btn btn-warning"])?>
			<?= Html::a(Yii::t('app','Manage Page'),['//iyo/page/index'],["class"=>"btn btn-primary"])?></p>		
		</div>
    </div>
</div>

<script type="text/javascript">
<?php $this->beginBlock('IYO') ?>		

	function setview(opposite){		
		var headerHeight = $('header').height();
		var footerHeight = $('footer').height()+31+200; // footer padding 15px + 15px & footer border 1px		
		if ($(".iyo").length)
		{			
			var newHeight = $(window).height()-(headerHeight+footerHeight);						
			$(".container-fluid,section.content").css("padding",0);			
			$(".iyo").css("height",newHeight);			
		}
				
	}
	
	function updateview() {
		if (typeof peta_dasar != "undefined")
		{
			if (typeof peta_dasar.map != "undefined")
			{
				var map = peta_dasar.map;		
				map.updateSize();
			}		
		}		
	}

	$(window).on('resize', function() {    
		setview();		
		updateview();		
	});	
	
	$(".sidebar-toggle").on('click', function() {    		
		setview(true);
		setTimeout(function(){
			updateview();		
		},500);			
		
	});	
	
	setview();			
	updateview();		
	
<?php $this->endBlock(); ?>

</script>
<?php
yii\web\YiiAsset::register($this);
$this->registerJs($this->blocks['IYO'], yii\web\View::POS_END);
