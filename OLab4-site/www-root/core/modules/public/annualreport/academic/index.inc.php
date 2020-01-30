<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Entrada is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Entrada is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Entrada.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Andrew Dos-Santos <andrew.dos-santos@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/
if((!defined("PARENT_INCLUDED")) || (!defined("IN_ANNUAL_REPORT"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
}

if ($ENTRADA_ACL->amIAllowed('annualreport', 'read')) {			
	if(!isset($_SESSION["academic_expand_grid"])) {
		$_SESSION["academic_expand_grid"] = "internal_grid";
	}
	?>
	<h1>Section <?php echo ($ENTRADA_USER->getClinical() ? "IV" : "III"); ?> - Service</h1>
	
	<table id="flex1" style="display:none"></table>
	<table id="flex2" style="display:none"></table>
	
	<script type="text/javascript" defer="defer">
	jQuery(document).ready(function() {
		jQuerydialog = jQuery('<div></div>')
			.html('<span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>You must select at least one record in order to delete.')
			.dialog({
				autoOpen: false,
				title: 'Please Select a Record',
				buttons: {
					Ok: function() {
						jQuery(this).dialog('close');
					}
				}
			});
			
		jQueryError = jQuery('<div></div>')
		.html('<span class="ui-icon ui-icon-locked" style="float:left; margin:0 7px 50px 0;"></span>Error: You cannot delete records from previous years. Contact support if you need one deleted.')
		.dialog({
			autoOpen: false,
			title: 'Error',
			buttons: {
				Cancel: function() {
					jQuery(this).dialog('close');
				},
				'Contact Support': function() {
					sendFeedback('<?php echo ENTRADA_URL;?>/agent-feedback.php?enc=<?php echo feedback_enc()?>');
					jQuery(this).dialog('close');
				}
			}
		});
			
		<?php $fields = "ar_internal_contributions,internal_contributions_id,time_commitment,description,role,year_reported"; ?>
		internal_grid = jQuery("#flex1").flexigrid
		(
			{
			url: '<?php echo ENTRADA_URL; ?>/api/ar_loadgrid.api.php?id=<?php echo $ENTRADA_USER->getActiveId(); ?>&t=<?php echo $fields; ?>',
			dataType: 'json',
			method: 'POST',
			colModel : [
				{display: 'Hours', name : 'time_commitment', width : 59, sortable : true, align: 'left'},
				{display: 'Description', name : 'description', width : 384, sortable : true, align: 'left'},
				{display: 'Role', name : 'role', width : 100, sortable : true, align: 'left'},
				{display: 'Year', name : 'year_reported', width : 50, sortable : true, align: 'left'},
				{display: 'Edit', name : 'ctled', width : 25,  sortable : false, align: 'center', process:edRow}
				],
			searchitems : [
				{display: 'Hours', name : 'time_commitment'},
				{display: 'Description', name : 'description'},
				{display: 'Role', name : 'role'},
				{display: 'Year', name : 'year_reported', isdefault: true}
				],
			sortname: "year_reported",
			sortorder: "desc",
			resizable: false, 
			usepager: true,
			showToggleBtn: false,
			collapseTable: <?php echo ($_SESSION["academic_expand_grid"] == "internal_grid" ? "false" : "true"); ?>,
			title: 'A. Service Internal to Queen\'s University',
			useRp: true,
			rp: 15,
			showTableToggleBtn: true,
			width: 675,
			height: 200,
			nomsg: 'No Results', 
			buttons : [
	            {name: 'Add', bclass: 'add', onpress : addRecord},
	            {separator: true}, 
	            {name: 'Delete Selected', bclass: 'delete', onpress : deleteRecord}
	            ]
			}
		);
			
		function addRecord(com,grid) {
	        if (com=='Add') {
	             window.location='<?php echo ENTRADA_URL; ?>/annualreport/academic?section=add_contribution';
	        }            
	    }
	     
	    function edRow(celDiv,id) {
	    	celDiv.innerHTML = "<a href='<?php echo ENTRADA_URL; ?>/annualreport/academic?section=edit_contribution&amp;rid="+id+"' style=\"cursor: pointer; cursor: hand\" text-decoration: none><img src=\"<?php echo ENTRADA_RELATIVE; ?>/images/action-edit.gif\" style=\"border: none\"/></a>";
	    }
		
	    function deleteRecord(com,grid) {
		    if (com=='Delete Selected') {
		    	jQuery(function() {
		    		var error = "false";
					if(jQuery('.trSelected',grid).length>0) {
			    		// a workaround for a flaw in the demo system (http://dev.jqueryui.com/ticket/4375), ignore!
						jQuery("#dialog-confirm").dialog("destroy");
						jQuery('.trSelected', grid).each(function() {  
	               			var reportYear = jQuery(this).find('div')[3].textContent;
							if(reportYear < <?php echo $AR_CUR_YEAR;?>) {
		               			// Do not allow the deletion of years that are prior to the current reporting year.
		               			error = "true";
							}
						});
						
						if(error == "false") {
							// allow deletion
							jQuery("#dialog-confirm").dialog({
							resizable: false,
							height:180,
							modal: true,
							buttons: {
								'Delete': function() {
									var ids = "";
				               		jQuery('.trSelected', grid).each(function() {  
				               			var id = jQuery(this).attr('id');
										id = id.substring(id.lastIndexOf('row')+3);
										if(ids == "") {
											ids = id;
										} else {
											ids = id+"|"+ids;
										}
									});
									jQuery.ajax
						            ({
						               type: "POST",
						               dataType: "json",
						               url: '<?php echo ENTRADA_URL; ?>/api/ar_delete.api.php?id=<?php echo $ENTRADA_USER->getActiveId(); ?>&t=<?php echo $fields; ?>&rid='+ids
						             });
							       	
							       	window.setTimeout('internal_grid.flexReload()', 1000);
									jQuery(this).dialog('close');
								},
								Cancel: function() {
									jQuery(this).dialog('close');
								}
							}
							});
						} else {
							jQueryError.dialog('open');
						}
			    	} else {
				    	jQuerydialog.dialog('open');
			    	}
				});
		    }
		}
		 
		<?php $fields = "ar_external_contributions,external_contributions_id,days_of_year,description,organisation,year_reported"; ?>
		external_grid = jQuery("#flex2").flexigrid
		(
			{
			url: '<?php echo ENTRADA_URL; ?>/api/ar_loadgrid.api.php?id=<?php echo $ENTRADA_USER->getActiveId(); ?>&t=<?php echo $fields; ?>',
			dataType: 'json',
			method: 'POST',
			colModel : [
				{display: 'Days', name : 'days_of_year', width : 25, sortable : true, align: 'left'},
				{display: 'Description', name : 'description', width : 384, sortable : true, align: 'left'},
				{display: 'Organisation', name : 'organisation', width : 134, sortable : true, align: 'left'},
				{display: 'Year', name : 'year_reported', width : 50, sortable : true, align: 'left'},
				{display: 'Edit', name : 'ctled', width : 25,  sortable : false, align: 'center', process:editExternal}
				],
			searchitems : [
				{display: 'Days', name : 'days_of_year'},
				{display: 'Description', name : 'description'},
				{display: 'Organisation', name : 'organisation'},
				{display: 'Year', name : 'year_reported', isdefault: true}
				],
			sortname: "year_reported",
			sortorder: "desc",
			resizable: false, 
			usepager: true,
			showToggleBtn: false,
			collapseTable: <?php echo ($_SESSION["academic_expand_grid"] == "external_grid" ? "false" : "true"); ?>,
			title: 'B. Service External to Queen\'s University',
			useRp: true,
			rp: 15,
			showTableToggleBtn: true,
			width: 675,
			height: 200,
			nomsg: 'No Results', 
			buttons : [
	            {name: 'Add', bclass: 'add', onpress : addExternal},
	            {separator: true}, 
	            {name: 'Delete Selected', bclass: 'delete', onpress : deleteExternal}
	            ]
			}
		);
			
		function addExternal(com,grid) {
	        if (com=='Add') {
	             window.location='<?php echo ENTRADA_URL; ?>/annualreport/academic?section=add_external';
	        }            
	    }
	     
	    function editExternal(celDiv,id) {
	    	celDiv.innerHTML = "<a href='<?php echo ENTRADA_URL; ?>/annualreport/academic?section=edit_external&amp;rid="+id+"' style=\"cursor: pointer; cursor: hand\" text-decoration: none><img src=\"<?php echo ENTRADA_RELATIVE; ?>/images/action-edit.gif\" style=\"border: none\"/></a>";
	    }
	    
	    function deleteExternal(com,grid) {
		    if (com=='Delete Selected') {
		    	jQuery(function() {
		    		var error = "false";
					if(jQuery('.trSelected',grid).length>0) {
			    		// a workaround for a flaw in the demo system (http://dev.jqueryui.com/ticket/4375), ignore!
						jQuery("#dialog-confirm").dialog("destroy");
						jQuery('.trSelected', grid).each(function() {  
	               			var reportYear = jQuery(this).find('div')[3].textContent;
							if(reportYear < <?php echo $AR_CUR_YEAR;?>) {
		               			// Do not allow the deletion of years that are prior to the current reporting year.
		               			error = "true";
							}
						});
						
						if(error == "false") {
							// allow deletion
							jQuery("#dialog-confirm").dialog({
							resizable: false,
							height:180,
							modal: true,
							buttons: {
								'Delete': function() {
									var ids = "";
				               		jQuery('.trSelected', grid).each(function() {  
				               			var id = jQuery(this).attr('id');
										id = id.substring(id.lastIndexOf('row')+3);
										if(ids == "") {
											ids = id;
										} else {
											ids = id+"|"+ids;
										}
									});
									jQuery.ajax
						            ({
						               type: "POST",
						               dataType: "json",
						               url: '<?php echo ENTRADA_URL; ?>/api/ar_delete.api.php?id=<?php echo $ENTRADA_USER->getActiveId(); ?>&t=<?php echo $fields; ?>&rid='+ids
						             });
							       	
							       	window.setTimeout('external_grid.flexReload()', 1000);
									jQuery(this).dialog('close');
								},
								Cancel: function() {
									jQuery(this).dialog('close');
								}
							}
							});
						} else {
							jQueryError.dialog('open');
						}
			    	} else {
				    	jQuerydialog.dialog('open');
			    	}
				});
		    }          
		}
	});
	</script>
	
	<div id="dialog-confirm" title="Delete?" style="display: none">
		<p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>These items will be permanently deleted and cannot be recovered. Are you sure?</p>
	</div>

	<?php
}	