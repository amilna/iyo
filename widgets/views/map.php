<?php
	use yii\helpers\Html;
	use yii\jui\AutoComplete;
	
	echo AutoComplete::widget([		
		'name'=>'',						
		'options'=>[
			'class'=>'hidden'
		]
	]);
?>

<div id="<?= $id ?>" class="iyo">
	<div id="<?= $id ?>-iyo-map" class="iyo-map"></div>
	<div class="iyo-panel0 noprint">
		<div class="iyo-nav noprint">
			<span class="iyo-nav-left"></span>
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
			<p class="iyo-search-notfound" style="display:none;"><br><?= Yii::t("app","Not record found") ?></p>
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
						<?= Yii::t("app","Click to set map preview at initial stage! (this action also available by pressing Shfit + Mouse Left Click)") ?>
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
						
						<div id="<?= $id ?>-iyo-add-coordinates-form-tab" role="tabpanel">							
							<ul class="nav nav-tabs nav-justified" role="tablist">
								<li role="presentation" class="active"><a href="#<?= $id ?>-iyo-add-coordinates-form-tab-dd" aria-controls="<?= $id ?>-iyo-add-coordinates-form-tab-dd" role="tab" data-toggle="tab"><?= Yii::t("app","DD") ?></a></li>
								<li role="presentation"><a href="#<?= $id ?>-iyo-add-coordinates-form-tab-dms" aria-controls="<?= $id ?>-iyo-add-coordinates-form-tab-dms" role="tab" data-toggle="tab"><?= Yii::t("app","DMS") ?></a></li>							
							</ul>

							<!-- Tab panes -->
							<div class="tab-content panel">
								<div role="tabpanel" class="tab-pane panel-body active" id="<?= $id ?>-iyo-add-coordinates-form-tab-dd">
									<div class="form-group">
										<label class="control-label" for="<?= $id ?>-iyo-coordinates-form"><?= Yii::t("app","Coordinates in Decimal Degree") ?></label>
										<textarea rows=4 id="<?= $id ?>-iyo-coordinates-form" class="form-control" placeholder="<?= Yii::t("app","format x,y (ex: 106.567,-5.444) add at new line for next coordinate") ?>" ></textarea>
									</div>
								</div>	
								<div role="tabpanel" class="tab-pane panel-body" id="<?= $id ?>-iyo-add-coordinates-form-tab-dms">
										<h5><?= Yii::t("app","Coordinates in Degree Minute Second") ?></h5>
										<div class="row">							
											<div class="col-xs-12">
												<h5 >		
													<a id="<?= $id ?>-dms-add" class="btn btn-sm btn-default pull-right" style="margin:0 0 0 4px"><?= Yii::t("app","Add Coordinate") ?></a>																
													<small style="text-align:right!important;"><?= 
														Yii::t("app","click button on the right to add a coordinate")
													?>
													</small>									
												</h5>
											</div>							
										</div>						
								</div>
							</div>
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
				<div class="iyo-layer-tools-cog iyo-notool" title="<?= Yii::t("app","Layer Properties") ?>"><i class="glyphicon glyphicon-cog"></i></div>
				<div class="iyo-layer-tools-remove iyo-notool" title="<?= Yii::t("app","Remove Layer") ?>"><i class="glyphicon glyphicon-trash"></i></div>
				<div class="iyo-layer-tools-editor iyo-tool" title="<?= Yii::t("app","Edit layer, add or edit feature in this layer") ?>"><i class="glyphicon glyphicon-pencil"></i></div>
				<div class="iyo-layer-tools-drag iyo-notool" title="<?= Yii::t("app","Layer Position, drag to change position") ?>"><i class="glyphicon glyphicon-resize-vertical"></i></div>			
			</div>			
			<div class="iyo-layer-tools-def noprint">				
				<div class="iyo-layer-tools-opacity" title="<?= Yii::t("app","Layer Opacity") ?>"></div>
			</div>
		</div>
		<div class="iyo-layer-legend">
			
				<div class="iyo-layer-tools-visibility noprint pull-left" title="<?= Yii::t("app","Layer Visibility") ?>"><i class="glyphicon glyphicon-eye-open"></i></div>
				<div class="iyo-layer-tools-seemore noprint pull-right" title="<?= Yii::t("app","More") ?>"><i class="glyphicon glyphicon-chevron-up"></i></div>
				<div class="iyo-layer-name">{name}</div>				
			<div class="iyo-layer-classes">
				
			</div>
		</div>		
	</div>
</div>


<div id="iyo-template-uilayerclass" class="noprint hidden">
	<div id="{cid}" class="iyo-layer-class">
		<div class="iyo-layer-class-symbol">			
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

<div id="iyo-template-uiattributemessage" class="noprint hidden">
	{
		"invalidInput":"<?= Yii::t("app","Invalid input, please check your input values!")?>",
		"deleteFailed":"<?= Yii::t("app","Delete has failed, try again later!")?>"		
	}
</div>

<div id="iyo-template-dms-form" class="noprint hidden">
	<div id="dms_:N" class="dms">	
		<div class="row">			
			<div class="col-xs-4" style="padding-right:0px;">																						
				<?= Html::textInput("Dms[coord][:N][degreex]",false,["id"=>"Dms_coord_:N_degreex","class"=>"form-control","placeholder"=>Yii::t("app","Degree"),"title"=>Yii::t("app","X Degree"),"style"=>"width:100%"]) ?>
			</div>
			<div class="col-xs-3" style="padding-left:0px;padding-right:0px;">																						
				<?= Html::textInput("Dms[coord][:N][minutex]",false,["id"=>"Dms_coord_:N_minutex","class"=>"form-control","placeholder"=>Yii::t("app","Minute"),"title"=>Yii::t("app","X Minute"),"style"=>"width:100%"]) ?>
			</div>
			<div class="col-xs-5" style="padding-left:0px;">																										
				<div class="input-group">				  
				  <input name="Dms[coord][:N][secondx]" id="Dms_coord_:N_secondx" type="text" class="form-control" placeholder="<?= Yii::t("app","Second")?>" title="<?= Yii::t("app","X Second")?>">
				  <div id="dms-del:N" title="<?= Yii::t("app","First row for x (absis),\r\nsecond row for y (ordinat).\r\nPrepend a minus (-) on degree value\r\nfor negative value!")?>" class="input-group-addon" style="padding: 6px 2px;"><i class="glyphicon glyphicon-question-sign"></i></div>
				</div>
			</div>			
		</div>						
		<div class="row">			
			<div class="col-xs-4" style="padding-right:0px;">																						
				<?= Html::textInput("Dms[coord][:N][degreey]",false,["id"=>"Dms_coord_:N_degreey","class"=>"form-control","placeholder"=>Yii::t("app","Degree"),"title"=>Yii::t("app","Y Degree"),"style"=>"width:100%"]) ?>
			</div>	
			<div class="col-xs-3" style="padding-left:0px;padding-right:0px;">																						
				<?= Html::textInput("Dms[coord][:N][minutey]",false,["id"=>"Dms_coord_:N_minutey","class"=>"form-control","placeholder"=>Yii::t("app","Minute"),"title"=>Yii::t("app","Y Minute"),"style"=>"width:100%"]) ?>
			</div>
			<div class="col-xs-5" style="padding-left:0px;">																										
				<div class="input-group">				  
				  <input name="Dms[coord][:N][secondy]" id="Dms_coord_:N_secondy" type="text" class="form-control" placeholder="<?= Yii::t("app","Second")?>" title="<?= Yii::t("app","Y Second")?>">
				  <div id="dms-del:N" title="<?= Yii::t("app","Remove Coordinate")?>" class="input-group-addon" style="cursor:pointer;padding: 6px 2px;"><i class="glyphicon glyphicon-trash"></i></div>
				</div>
			</div>			
		</div>
		<hr>
	</div>	
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
