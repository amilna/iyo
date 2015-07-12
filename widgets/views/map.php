<?php
	use yii\helpers\Html;
?>

<div id="<?= $id ?>" class="iyo">
	<div id="<?= $id ?>-iyo-map" class="iyo-map"></div>
	<div class="iyo-panel0 noprint">
		<div class="iyo-nav noprint">
			<div class="iyo-nav-layers" title="<?= Yii::t("app","Layers") ?>"><i class="glyphicon glyphicon-th-list"></i></div>
			<div class="iyo-nav-search" title="<?= Yii::t("app","Search") ?>"><i class="glyphicon glyphicon-search"></i></div>
			<div class="iyo-nav-tools" title="<?= Yii::t("app","Tools") ?>"><i class="glyphicon glyphicon-th-large"></i></div>			
			<span class="iyo-nav-panel1"><i class="glyphicon glyphicon-minus"></i></span>
		</div>
	</div>	
	<div class="iyo-panel1">				
		<div class="iyo-layers iyo-tab">			
		</div>
		<div class="iyo-search iyo-tab">
			<div class="iyo-search-query">
				<input type="text" class="form-control" placeholder="<?= Yii::t("app","Search") ?>">
			</div>
			<div class="iyo-search-result"></div>
		</div>
		<div class="iyo-tools iyo-tab">
			<div class="iyo-tools-group">
				<div class="iyo-tools-button">
					<div class="iyo-home iyo-tool" title="<?= Yii::t("app","Initial Preview") ?>"><i class="glyphicon glyphicon-home"></i></div>
					<div class="iyo-base iyo-tool" title="<?= Yii::t("app","Base Maps") ?>"><i class="glyphicon glyphicon-picture"></i></div>
					<div class="iyo-print iyo-tool" title="<?= Yii::t("app","Print") ?>"><i class="glyphicon glyphicon-print"></i></div>
					<div class="iyo-edit-attributes iyo-tool" title="<?= Yii::t("app","Edit Attributes") ?>"><i class="glyphicon glyphicon-comment"></i></div>
					<div class="iyo-add-coordinates iyo-tool" title="<?= Yii::t("app","Add Points by Coordinates") ?>"><i class="glyphicon glyphicon-map-marker"></i></div>
				</div>
				<div class="iyo-tools-options">				
					<div class="iyo-tool-options-message iyo-tool-options">
						<?= Yii::t("app","Please hover or click a tool button on the left!") ?>
					</div>
					<div class="iyo-home-message iyo-tool-options" >
						<?= Yii::t("app","Click to set map preview at initial stage!") ?>
					</div>
					<div class="iyo-basemaps iyo-tool-options"></div>
					<div class="iyo-print-message iyo-tool-options" >
						<?= Yii::t("app","Click to print current map preview!") ?>
					</div>
					<div class="iyo-attributes iyo-tool-options">
						<div class="iyo-attributes-message" >
							<?= Yii::t("app","Please edit a layer and select feature to be edited!") ?>
						</div>
						<div class="iyo-attributes-fields" >
						</div>
					</div>
					<div class="iyo-add-coordinates-form iyo-tool-options">
						<div class="form-group">
							<label class="control-label" for="<?= $id ?>-iyo-coordinates-form"><?= Yii::t("app","Coordinates") ?></label>
							<textarea rows=4 id="<?= $id ?>-iyo-coordinates-form" class="form-control" placeholder="<?= Yii::t("app","format x,y (ex: 106.567,-5.444) add at new line for next coordinate") ?>" ></textarea>
						</div>
						<a id="<?= $id ?>-iyo-coordinates-clear" class="btn btn-warning pull-left"><?=Yii::t("app","Clear")?></a>
						<a id="<?= $id ?>-iyo-coordinates-add" class="btn btn-primary pull-right"><?=Yii::t("app","Add")?></a>
					</div>
				</div>	
				<div class="row"></div>		
			</div>			
		</div>
		<div class="iyo-editor iyo-tab"></div>						
	</div>
	
	<div class="iyo-panel2">				
			<div id="<?= $id ?>-iyo-inset" class="iyo-inset iyo-tab print"></div>
			<div class="iyo-info iyo-tab"></div>						
	</div>
	<div class="iyo-status noprint">
		<div class="iyo-coordinate"></div>			
		<div class="iyo-nav-inset"><i class="glyphicon glyphicon-globe"></i></div>
	</div>
	
	<div class="iyo-panel3">
		<div class="iyo-scale"></div>						
	</div>		
	
	<div class="iyo-popup ol-popup">
		<a href="#" class="iyo-popup-closer ol-popup-closer"></a>
		<div class="iyo-popup-content"></div>
	</div>	
	
	<div class="iyo-wait"></div>
	
</div>

<!--<div id="<?= $id ?>-iyo-maps"></div>-->

<div id="iyo-template-uilayer" class="noprint hidden">
	<div id="{id}" class="iyo-layer">
		<div class="iyo-layer-tools noprint">			
			
			<div class="iyo-layer-tools-more noprint">								
				<div class="iyo-layer-tools-cog iyo-tool" title="<?= Yii::t("app","Layer Properties") ?>"><i class="glyphicon glyphicon-cog"></i></div>
				<div class="iyo-layer-tools-remove iyo-tool" title="<?= Yii::t("app","Remove Layer") ?>"><i class="glyphicon glyphicon-trash"></i></div>
				<div class="iyo-layer-tools-editor iyo-tool" title="<?= Yii::t("app","Edit layer, add or edit feature in this layer") ?>"><i class="glyphicon glyphicon-pencil"></i></div>
				<div class="iyo-layer-tools-drag iyo-tool" title="<?= Yii::t("app","Layer Position, drag to change position") ?>"><i class="glyphicon glyphicon-resize-vertical"></i></div>			
			</div>			
			<div class="iyo-layer-tools-def noprint">
				<!--<div class="iyo-layer-tools-visibility iyo-tool" title="<?= Yii::t("app","Layer Visibility") ?>"><i class="glyphicon glyphicon-eye-open"></i></div>-->
				<div class="iyo-layer-tools-opacity" title="<?= Yii::t("app","Layer Opacity") ?>"></div>
			</div>
		</div>
		<div class="iyo-layer-legend">
			
				<div class="iyo-layer-tools-visibility noprint pull-left" title="<?= Yii::t("app","Layer Visibility") ?>"><i class="glyphicon glyphicon-eye-open"></i></div>
				<div class="iyo-layer-tools-seemore noprint pull-right" title="<?= Yii::t("app","More") ?>"><i class="glyphicon glyphicon-menu-hamburger"></i></div>
				<div class="iyo-layer-name">{name}</div>				
			<div class="iyo-layer-classes">
				
			</div>
		</div>		
	</div>
</div>


<div id="iyo-template-uilayerclass" class="noprint hidden">
	<div id="{cid}" class="iyo-layer-class">
		<div class="iyo-layer-class-symbol">
			<div class="iyo-layer-class-symbol-fill"></div>			
			<div class="iyo-layer-class-symbol-stroke"></div>			
		</div>
		<div class="iyo-layer-class-remarks">{class}</div>
	</div>
</div>

<div id="iyo-template-uibasemap" class="noprint hidden">
	<div id="{id}" class="iyo-basemap">		
		<div class="iyo-basemap-preview" title="{name}">
			{img src="{url}" }					
		</div>		
	</div>
</div>

<div id="iyo-template-uiattribute" class="noprint hidden">
	<div class="form-group">
		<label class="control-label" for="{lid}">{label}</label>
		<input type="text" id="{did}" class="form-control" name="{name}" placeholder="..." value="{value}">
	</div>
</div>

<div id="iyo-template-uiattributebutton" class="noprint hidden">
	<a id="delete-{lid}" class="btn btn-danger pull-left"><?=Yii::t("app","Delete")?></a>	
	<a id="save-{did}" class="btn btn-primary pull-right"><?=Yii::t("app","Save")?></a>
	<a id="clean-{cid}" class="btn btn-warning pull-right"><?=Yii::t("app","Clean")?></a>			
</div>

<div id="iyo-template-changeconfirmbutton" class="noprint hidden">
	<p><?=Yii::t("app","There are records have been modified, but not yet saved. Please confirm to save or cancel those changes.")?></p>
	<div class="row">
		<div class="col-xs-12">
			<a id="layer-changes-cancel-{cid}" class="btn btn-danger pull-left"><?=Yii::t("app","Cancel")?></a>	
			<a id="layer-changes-save-{sid}" class="btn btn-primary pull-right"><?=Yii::t("app","Save")?></a>
		</div>
	</div>
</div>

<div id="iyo-modal-layer-confirm" class="modal fade" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" >	
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">×</button>
				<h4 class="blue bigger"><?=Yii::t("app","Save Modified Record?")?></h4>
			</div>
			<div class="modal-body">
																		
			</div>
		</div>
	</div>
</div>				
