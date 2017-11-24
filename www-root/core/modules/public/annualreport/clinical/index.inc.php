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
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Serves as a dashboard type file for the Annual Report - Research module.
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
	if(!isset($_SESSION["clinical_expand_grid"])) {
		$_SESSION["clinical_expand_grid"] = "clinical_grid";
	}
	?>
	<h1>Section III - Clinical</h1>
	<table id="flex1" style="display:none"></table>
	<table id="flex2" style="display:none"></table>
	<table id="flex3" style="display:none"></table>
	<table id="flex4" style="display:none"></table>
	<table id="flex5" style="display:none"></table>
	<table id="flex6" style="display:none"></table>
	<table id="flex7" style="display:none"></table>
	<table id="flex8" style="display:none"></table>
	
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
		
		<?php $fields = "ar_clinical_activity,clinical_activity_id,average_hours,site,description,year_reported"; ?>
		clinical_grid = jQuery("#flex1").flexigrid
		(
			{
			url: '<?php echo ENTRADA_URL; ?>/api/ar_loadgrid.api.php?id=<?php echo $ENTRADA_USER->getActiveId(); ?>&t=<?php echo $fields; ?>',
			dataType: 'json',
			method: 'POST',
			colModel : [
				{display: 'Hours', name : 'hours', width : 59, sortable : true, align: 'left'},
				{display: 'Site', name : 'site', width : 100, sortable : true, align: 'left'},
				{display: 'Description', name : 'description', width : 384, sortable : true, align: 'left'},
				{display: 'Year', name : 'year_reported', width : 50, sortable : true, align: 'left'},
				{display: 'Edit', name : 'ctled', width : 25,  sortable : false, align: 'center', process:edRow}
				],
			searchitems : [
				{display: 'Hours', name : 'hours'},
				{display: 'Site', name : 'site'},
				{display: 'Description', name : 'description'},
				{display: 'Year', name : 'year_reported', isdefault: true}
				],
			sortname: "year_reported",
			sortorder: "desc",
			resizable: false, 
			usepager: true,
			showToggleBtn: false,
			collapseTable: <?php echo ($_SESSION["clinical_expand_grid"] == "clinical_grid" ? "false" : "true"); ?>,
			title: 'A. Clinical Activity',
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
	             window.location='<?php echo ENTRADA_URL; ?>/annualreport/clinical?section=add_clinical';
	        }            
	    }
	     
	    function edRow(celDiv,id) {
	    	celDiv.innerHTML = "<a href='<?php echo ENTRADA_URL; ?>/annualreport/clinical?section=edit_clinical&amp;rid="+id+"' style=\"cursor: pointer; cursor: hand\" text-decoration: none><img src=\"<?php echo ENTRADA_RELATIVE; ?>/images/action-edit.gif\" style=\"border: none\"/></a>";
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
							       	
							       	window.setTimeout('clinical_grid.flexReload()', 1000);
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
		<?php $fields = "ar_ward_supervision,ward_supervision_id,average_clerks,average_patients,service,year_reported"; ?>
		ward_grid = jQuery("#flex2").flexigrid
		(
			{
			url: '<?php echo ENTRADA_URL; ?>/api/ar_loadgrid.api.php?id=<?php echo $ENTRADA_USER->getActiveId(); ?>&t=<?php echo $fields; ?>',
			dataType: 'json',
			method: 'POST',
			colModel : [
				{display: 'Clerks', name : 'average_clerks', width : 59, sortable : true, align: 'left'},
				{display: 'Patients', name : 'average_patients', width : 100, sortable : true, align: 'left'},
				{display: 'Service', name : 'service', width : 384, sortable : true, align: 'left'},
				{display: 'Year', name : 'year_reported', width : 50, sortable : true, align: 'left'},
				{display: 'Edit', name : 'ctled', width : 25,  sortable : false, align: 'center', process:editWard}
				],
			searchitems : [
				{display: 'Clerks', name : 'average_clerks'},
				{display: 'Patients', name : 'average_patients'},
				{display: 'Service', name : 'service'},
				{display: 'Year', name : 'year_reported', isdefault: true}
				],
			sortname: "year_reported",
			sortorder: "desc",
			resizable: false, 
			usepager: true,
			showToggleBtn: false,
			collapseTable: <?php echo ($_SESSION["clinical_expand_grid"] == "ward_grid" ? "false" : "true"); ?>,
			title: 'B. Ward Supervision', 
			useRp: true,
			rp: 15,
			showTableToggleBtn: true,
			width: 675,
			height: 200,
			nomsg: 'No Results', 
			buttons : [
	            {name: 'Add', bclass: 'add', onpress : addWard},
	            {separator: true}, 
	            {name: 'Delete Selected', bclass: 'delete', onpress : deleteWard}
	            ]
			}
		);
			
		function addWard(com,grid) {
	        if (com=='Add') {
	             window.location='<?php echo ENTRADA_URL; ?>/annualreport/clinical?section=add_ward';
	        }            
	    }
	     
	    function editWard(celDiv,id) {
	    	celDiv.innerHTML = "<a href='<?php echo ENTRADA_URL; ?>/annualreport/clinical?section=edit_ward&amp;rid="+id+"' style=\"cursor: pointer; cursor: hand\" text-decoration: none><img src=\"<?php echo ENTRADA_RELATIVE; ?>/images/action-edit.gif\" style=\"border: none\"/></a>";
	    }
	    
	    function deleteWard(com,grid) {
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
							       	
							       	window.setTimeout('ward_grid.flexReload()', 1000);
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
		<?php $fields = "ar_clinics,clinics_id,average_clerks,patients,clinic,year_reported"; ?>
		clinic_grid = jQuery("#flex3").flexigrid
		(
			{
			url: '<?php echo ENTRADA_URL; ?>/api/ar_loadgrid.api.php?id=<?php echo $ENTRADA_USER->getActiveId(); ?>&t=<?php echo $fields; ?>',
			dataType: 'json',
			method: 'POST',
			colModel : [
				{display: 'Clerks', name : 'average_clerks', width : 59, sortable : true, align: 'left'},
				{display: 'Patients', name : 'patients', width : 100, sortable : true, align: 'left'},
				{display: 'Clinic', name : 'clinic', width : 384, sortable : true, align: 'left'},
				{display: 'Year', name : 'year_reported', width : 50, sortable : true, align: 'left'},
				{display: 'Edit', name : 'ctled', width : 25,  sortable : false, align: 'center', process:editClinic}
				],
			searchitems : [
				{display: 'Clerks', name : 'average_clerks'},
				{display: 'Patients', name : 'patients'},
				{display: 'Clinic', name : 'clinic'},
				{display: 'Year', name : 'year_reported', isdefault: true}
				],
			sortname: "year_reported",
			sortorder: "desc",
			resizable: false, 
			usepager: true,
			showToggleBtn: false,
			collapseTable: <?php echo ($_SESSION["clinical_expand_grid"] == "clinic_grid" ? "false" : "true"); ?>,
			title: 'C. Clinics',
			useRp: true,
			rp: 15,
			showTableToggleBtn: true,
			width: 675,
			height: 200,
			nomsg: 'No Results', 
			buttons : [
	            {name: 'Add', bclass: 'add', onpress : addClinic},
	            {separator: true}, 
	            {name: 'Delete Selected', bclass: 'delete', onpress : deleteClinic}
	            ]
			}
		);
			
		function addClinic(com,grid) {
	        if (com=='Add') {
	             window.location='<?php echo ENTRADA_URL; ?>/annualreport/clinical?section=add_clinic';
	        }            
	    }
	     
	    function editClinic(celDiv,id) {
	    	celDiv.innerHTML = "<a href='<?php echo ENTRADA_URL; ?>/annualreport/clinical?section=edit_clinic&amp;rid="+id+"' style=\"cursor: pointer; cursor: hand\" text-decoration: none><img src=\"<?php echo ENTRADA_RELATIVE; ?>/images/action-edit.gif\" style=\"border: none\"/></a>";
	    }
	    
	    function deleteClinic(com,grid) {
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
							       	
							       	window.setTimeout('clinic_grid.flexReload()', 1000);
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
		
		<?php $fields = "ar_consults,consults_id,months,average_consults,activity,year_reported"; ?>
		consults_grid = jQuery("#flex4").flexigrid
		(
			{
			url: '<?php echo ENTRADA_URL; ?>/api/ar_loadgrid.api.php?id=<?php echo $ENTRADA_USER->getActiveId(); ?>&t=<?php echo $fields; ?>',
			dataType: 'json',
			method: 'POST',
			colModel : [
				{display: 'Months', name : 'months', width : 59, sortable : true, align: 'left'},
				{display: 'Consults', name : 'average_consults', width : 100, sortable : true, align: 'left'},
				{display: 'Activity', name : 'activity', width : 384, sortable : true, align: 'left'},
				{display: 'Year', name : 'year_reported', width : 50, sortable : true, align: 'left'},
				{display: 'Edit', name : 'ctled', width : 25,  sortable : false, align: 'center', process:editConsult}
				],
			searchitems : [
				{display: 'Months', name : 'months'},
				{display: 'Consults', name : 'average_consults'},
				{display: 'Activity', name : 'activity'},
				{display: 'Year', name : 'year_reported', isdefault: true}
				],
			sortname: "year_reported",
			sortorder: "desc",
			resizable: false, 
			usepager: true,
			showToggleBtn: false,
			collapseTable: <?php echo ($_SESSION["clinical_expand_grid"] == "consults_grid" ? "false" : "true"); ?>,
			title: 'D. In-Hospital Consultations',
			useRp: true,
			rp: 15,
			showTableToggleBtn: true,
			width: 675,
			height: 200,
			nomsg: 'No Results', 
			buttons : [
	            {name: 'Add', bclass: 'add', onpress : addConsult},
	            {separator: true}, 
	            {name: 'Delete Selected', bclass: 'delete', onpress : deleteConsult}
	            ]
			}
		);
			
		function addConsult(com,grid) {
	        if (com=='Add') {
	             window.location='<?php echo ENTRADA_URL; ?>/annualreport/clinical?section=add_consult';
	        }            
	    }
	     
	    function editConsult(celDiv,id) {
	    	celDiv.innerHTML = "<a href='<?php echo ENTRADA_URL; ?>/annualreport/clinical?section=edit_consult&amp;rid="+id+"' style=\"cursor: pointer; cursor: hand\" text-decoration: none><img src=\"<?php echo ENTRADA_RELATIVE; ?>/images/action-edit.gif\" style=\"border: none\"/></a>";
	    }
	    
	    function deleteConsult(com,grid) {
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
							       	
							       	window.setTimeout('consults_grid.flexReload()', 1000);
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
		
		<?php $fields = "ar_on_call,on_call_id,frequency,site,special_features,year_reported"; ?>
		on_call_grid = jQuery("#flex5").flexigrid
		(
			{
			url: '<?php echo ENTRADA_URL; ?>/api/ar_loadgrid.api.php?id=<?php echo $ENTRADA_USER->getActiveId(); ?>&t=<?php echo $fields; ?>',
			dataType: 'json',
			method: 'POST',
			colModel : [
				{display: 'Frequency', name : 'frequency', width : 59, sortable : true, align: 'left'},
				{display: 'Site', name : 'site', width : 100, sortable : true, align: 'left'},
				{display: 'Special Features', name : 'special_features', width : 384, sortable : true, align: 'left'},
				{display: 'Year', name : 'year_reported', width : 50, sortable : true, align: 'left'},
				{display: 'Edit', name : 'ctled', width : 25,  sortable : false, align: 'center', process:editOnCall}
				],
			searchitems : [
				{display: 'Frequency', name : 'frequency'},
				{display: 'Site', name : 'site'},
				{display: 'Special Features', name : 'special_features'},
				{display: 'Year', name : 'year_reported', isdefault: true}
				],
			sortname: "year_reported",
			sortorder: "desc",
			resizable: false, 
			usepager: true,
			showToggleBtn: false,
			collapseTable: <?php echo ($_SESSION["clinical_expand_grid"] == "on_call_grid" ? "false" : "true"); ?>,
			title: 'E. On-Call Responsibility',
			useRp: true,
			rp: 15,
			showTableToggleBtn: true,
			width: 675,
			height: 200,
			nomsg: 'No Results', 
			buttons : [
	            {name: 'Add', bclass: 'add', onpress : addOnCall},
	            {separator: true}, 
	            {name: 'Delete Selected', bclass: 'delete', onpress : deleteOnCall}
	            ]
			}
		);
			
		function addOnCall(com,grid) {
	        if (com=='Add') {
	             window.location='<?php echo ENTRADA_URL; ?>/annualreport/clinical?section=add_on_call';
	        }            
	    }
	     
	    function editOnCall(celDiv,id) {
	    	celDiv.innerHTML = "<a href='<?php echo ENTRADA_URL; ?>/annualreport/clinical?section=edit_on_call&amp;rid="+id+"' style=\"cursor: pointer; cursor: hand\" text-decoration: none><img src=\"<?php echo ENTRADA_RELATIVE; ?>/images/action-edit.gif\" style=\"border: none\"/></a>";
	    }
	    
	    function deleteOnCall(com,grid) {
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
							       	
							       	window.setTimeout('on_call_grid.flexReload()', 1000);
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
		
		<?php $fields = "ar_procedures,procedures_id,average_hours,site,special_features,year_reported"; ?>
		procedures_grid = jQuery("#flex6").flexigrid
		(
			{
			url: '<?php echo ENTRADA_URL; ?>/api/ar_loadgrid.api.php?id=<?php echo $ENTRADA_USER->getActiveId(); ?>&t=<?php echo $fields; ?>',
			dataType: 'json',
			method: 'POST',
			colModel : [
				{display: 'Hours', name : 'average_hours', width : 59, sortable : true, align: 'left'},
				{display: 'Site', name : 'site', width : 100, sortable : true, align: 'left'},
				{display: 'Special Features', name : 'special_features', width : 384, sortable : true, align: 'left'},
				{display: 'Year', name : 'year_reported', width : 50, sortable : true, align: 'left'},
				{display: 'Edit', name : 'ctled', width : 25,  sortable : false, align: 'center', process:editProcedure}
				],
			searchitems : [
				{display: 'Hours', name : 'average_hours'},
				{display: 'Site', name : 'site'},
				{display: 'Special Features', name : 'special_features'},
				{display: 'Year', name : 'year_reported', isdefault: true}
				],
			sortname: "year_reported",
			sortorder: "desc",
			resizable: false, 
			usepager: true,
			showToggleBtn: false,
			collapseTable: <?php echo ($_SESSION["clinical_expand_grid"] == "procedures_grid" ? "false" : "true"); ?>,
			title: 'F. Procedures',
			useRp: true,
			rp: 15,
			showTableToggleBtn: true,
			width: 675,
			height: 200,
			nomsg: 'No Results', 
			buttons : [
	            {name: 'Add', bclass: 'add', onpress : addProcedure},
	            {separator: true}, 
	            {name: 'Delete Selected', bclass: 'delete', onpress : deleteProcedure}
	            ]
			}
		);
			
		function addProcedure(com,grid) {
	        if (com=='Add') {
	             window.location='<?php echo ENTRADA_URL; ?>/annualreport/clinical?section=add_procedure';
	        }            
	    }
	     
	    function editProcedure(celDiv,id) {
	    	celDiv.innerHTML = "<a href='<?php echo ENTRADA_URL; ?>/annualreport/clinical?section=edit_procedure&amp;rid="+id+"' style=\"cursor: pointer; cursor: hand\" text-decoration: none><img src=\"<?php echo ENTRADA_RELATIVE; ?>/images/action-edit.gif\" style=\"border: none\"/></a>";
	    }
	    
	    function deleteProcedure(com,grid) {
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
							       	
							       	window.setTimeout('procedures_grid.flexReload()', 1000);
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
		
		<?php $fields = "ar_other_activity,other_activity_id,average_hours,site,special_features,year_reported"; ?>
		other_grid = jQuery("#flex7").flexigrid
		(
			{
			url: '<?php echo ENTRADA_URL; ?>/api/ar_loadgrid.api.php?id=<?php echo $ENTRADA_USER->getActiveId(); ?>&t=<?php echo $fields; ?>',
			dataType: 'json',
			method: 'POST',
			colModel : [
				{display: 'Hours', name : 'average_hours', width : 59, sortable : true, align: 'left'},
				{display: 'Site', name : 'site', width : 100, sortable : true, align: 'left'},
				{display: 'Special Features', name : 'special_features', width : 384, sortable : true, align: 'left'},
				{display: 'Year', name : 'year_reported', width : 50, sortable : true, align: 'left'},
				{display: 'Edit', name : 'ctled', width : 25,  sortable : false, align: 'center', process:editOther}
				],
			searchitems : [
				{display: 'Hours', name : 'average_hours'},
				{display: 'Site', name : 'site'},
				{display: 'Special Features', name : 'special_features'},
				{display: 'Year', name : 'year_reported', isdefault: true}
				],
			sortname: "year_reported",
			sortorder: "desc",
			resizable: false, 
			usepager: true,
			showToggleBtn: false,
			collapseTable: <?php echo ($_SESSION["clinical_expand_grid"] == "other_grid" ? "false" : "true"); ?>,
			title: 'G. Other Professional Activity', 
			useRp: true,
			rp: 15,
			showTableToggleBtn: true,
			width: 675,
			height: 200,
			nomsg: 'No Results', 
			buttons : [
	            {name: 'Add', bclass: 'add', onpress : addOther},
	            {separator: true}, 
	            {name: 'Delete Selected', bclass: 'delete', onpress : deleteOther}
	            ]
			}
		);
			
		function addOther(com,grid) {
	        if (com=='Add') {
	             window.location='<?php echo ENTRADA_URL; ?>/annualreport/clinical?section=add_other';
	        }            
	    }
	     
	    function editOther(celDiv,id) {
	    	celDiv.innerHTML = "<a href='<?php echo ENTRADA_URL; ?>/annualreport/clinical?section=edit_other&amp;rid="+id+"' style=\"cursor: pointer; cursor: hand\" text-decoration: none><img src=\"<?php echo ENTRADA_RELATIVE; ?>/images/action-edit.gif\" style=\"border: none\"/></a>";
	    }
	    
	    function deleteOther(com,grid) {
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
							       	
							       	window.setTimeout('other_grid.flexReload()', 1000);
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
		
		<?php $fields = "ar_clinical_innovation,clinical_innovation_id,description,year_reported"; ?>
		innovation_grid = jQuery("#flex8").flexigrid
		(
			{
			url: '<?php echo ENTRADA_URL; ?>/api/ar_loadgrid.api.php?id=<?php echo $ENTRADA_USER->getActiveId(); ?>&t=<?php echo $fields; ?>',
			dataType: 'json',
			method: 'POST',
			colModel : [
				{display: 'Description', name : 'description', width : 564, sortable : true, align: 'left'},
				{display: 'Year', name : 'year_reported', width : 50, sortable : true, align: 'left'},
				{display: 'Edit', name : 'ctled', width : 25,  sortable : false, align: 'center', process:editInnovation}
				],
			searchitems : [
				{display: 'Description', name : 'description'},
				{display: 'Year', name : 'year_reported', isdefault: true}
				],
			sortname: "year_reported",
			sortorder: "desc",
			resizable: false, 
			usepager: true,
			showToggleBtn: false,
			collapseTable: <?php echo ($_SESSION["clinical_expand_grid"] == "innovation_grid" ? "false" : "true"); ?>,
			title: 'H. Innovation In Clinical Activity',
			useRp: true,
			rp: 15,
			showTableToggleBtn: true,
			width: 675,
			height: 200,
			nomsg: 'No Results', 
			buttons : [
	            {name: 'Add', bclass: 'add', onpress : addInnovation},
	            {separator: true}, 
	            {name: 'Delete Selected', bclass: 'delete', onpress : deleteInnovation}
	            ]
			}
		);
			
		function addInnovation(com,grid) {
	        if (com=='Add') {
	             window.location='<?php echo ENTRADA_URL; ?>/annualreport/clinical?section=add_innovation';
	        }            
	    }
	     
	    function editInnovation(celDiv,id) {
	    	celDiv.innerHTML = "<a href='<?php echo ENTRADA_URL; ?>/annualreport/clinical?section=edit_innovation&amp;rid="+id+"' style=\"cursor: pointer; cursor: hand\" text-decoration: none><img src=\"<?php echo ENTRADA_RELATIVE; ?>/images/action-edit.gif\" style=\"border: none\"/></a>";
	    }
	    
	    function deleteInnovation(com,grid) {
		    if (com=='Delete Selected') {
		    	jQuery(function() {
		    		var error = "false";
					if(jQuery('.trSelected',grid).length>0) {
			    		// a workaround for a flaw in the demo system (http://dev.jqueryui.com/ticket/4375), ignore!
						jQuery("#dialog-confirm").dialog("destroy");
						jQuery('.trSelected', grid).each(function() {  
	               			var reportYear = jQuery(this).find('div')[1].textContent;
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
							       	
							       	window.setTimeout('innovation_grid.flexReload()', 1000);
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