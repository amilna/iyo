<?php
	$this->params['no-content-header'] = true;
	$module = Yii::$app->getModule("iyo");
	$this->title = $model->title;
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
		$options = $model->config;
		$mapOptions = json_decode($options,true);
		echo amilna\iyo\widgets\Map::widget(['options'=>$mapOptions]);				
    ?>
</div>

<script type="text/javascript">
<?php $this->beginBlock('IYO') ?>			
	
	function setview(opposite){		
		var headerHeight = $('header').height();
		var footerHeight = $('footer').height()+31; // footer padding 15px + 15px & footer border 1px		
		if ($(".iyo").length)
		{			
			var newHeight = $(window).height()-(headerHeight+footerHeight);						
			$(".content-wrapper .container-fluid,section.content").css("padding",0);		
			$(".iyo").css("height",newHeight);			
		}
				
	}
	
	function updateview() {
		if (typeof amilna_map != "undefined")
		{
			if (typeof amilna_map.map != "undefined")
			{
				var map = amilna_map.map;		
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
