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
	if(!isset($_SESSION["research_expand_grid"])) {
		$_SESSION["research_expand_grid"] = "research_grid";
	}
	?>
	<h1>Section II - Research</h1>
	<table id="flex1" style="display:none"></table>
	<table id="flex2" style="display:none"></table>
	<table id="flex3" style="display:none"></table>
	<table id="flex4" style="display:none"></table>
	<table id="flex5" style="display:none"></table>
	<table id="flex6" style="display:none"></table>
	<table id="flex7" style="display:none"></table>
	<table id="flex8" style="display:none"></table>
	<table id="flex9" style="display:none"></table>
	
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
		
		<?php $fields = "ar_research,research_id,principal_investigator,grant_title,amount_received,year_reported"; ?>
		research_grid = jQuery("#flex1").flexigrid
		(
			{
			url: '<?php echo ENTRADA_URL; ?>/api/ar_loadgrid.api.php?id=<?php echo $ENTRADA_USER->getActiveId(); ?>&t=<?php echo $fields; ?>',
			dataType: 'json',
			method: 'POST',
			colModel : [
				{display: 'Principal Investigator', name : 'principal_investigator', width : 100, sortable : true, align: 'left'},
				{display: 'Grant Title', name : 'grant_title', width : 384, sortable : true, align: 'left'},
				{display: 'Amount', name : 'amount_received', width : 59, sortable : true, align: 'left'},
				{display: 'Year', name : 'year_reported', width : 50, sortable : true, align: 'left'},
				{display: 'Edit', name : 'ctled', width : 25,  sortable : false, align: 'center', process:edRow}
				],
			searchitems : [
				{display: 'Principal Investigator', name : 'principal_investigator'},
				{display: 'Grant Title', name : 'grant_title'},
				{display: 'Amount', name : 'amount_received'},
				{display: 'Year', name : 'year_reported', isdefault: true}
				],
			sortname: "year_reported",
			sortorder: "desc",
			resizable: false, 
			usepager: true,
			showToggleBtn: false,
			collapseTable: <?php echo ($_SESSION["research_expand_grid"] == "research_grid" ? "false" : "true"); ?>,
			title: 'A. Projects, Grants and Contracts',
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
	             window.location='<?php echo ENTRADA_URL; ?>/annualreport/research?section=add';
	        }            
	    }
	     
	    function edRow(celDiv,id) {
	    	celDiv.innerHTML = "<a href='<?php echo ENTRADA_URL; ?>/annualreport/research?section=edit&amp;rid="+id+"' style=\"cursor: pointer; cursor: hand\" text-decoration: none><img src=\"<?php echo ENTRADA_RELATIVE; ?>/images/action-edit.gif\" style=\"border: none\"/></a>";
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
							       	
							       	window.setTimeout('research_grid.flexReload()', 1000);
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
		
		<?php $fields = "ar_peer_reviewed_papers,peer_reviewed_papers_id,source,title,author_list,year_reported"; ?>
		peer_reviewed_grid = jQuery("#flex2").flexigrid
		(
			{
			url: '<?php echo ENTRADA_URL; ?>/api/ar_loadgrid.api.php?id=<?php echo $ENTRADA_USER->getActiveId(); ?>&t=<?php echo $fields; ?>',
			dataType: 'json',
			method: 'POST',
			colModel : [
				{display: 'Source', name : 'source', width : 100, sortable : true, align: 'left'},
				{display: 'Title', name : 'title', width : 384, sortable : true, align: 'left'},
				{display: 'Author List', name : 'author_list', width : 59, sortable : true, align: 'left'},
				{display: 'Year', name : 'year_reported', width : 50, sortable : true, align: 'left'},
				{display: 'Edit', name : 'ctled', width : 25,  sortable : false, align: 'center', process:editPeerReviewed}
				],
			searchitems : [
				{display: 'Source', name : 'source'},
				{display: 'Title', name : 'title'},
				{display: 'Author List', name : 'author_list'},
				{display: 'Year', name : 'year_reported', isdefault: true}
				],
			sortname: "year_reported",
			sortorder: "desc",
			resizable: false, 
			usepager: true,
			showToggleBtn: false,
			collapseTable: <?php echo ($_SESSION["research_expand_grid"] == "peer_reviewed_grid" ? "false" : "true"); ?>,
			title: 'B. Peer-Reviewed Publications', 
			useRp: true,
			rp: 15,
			showTableToggleBtn: true,
			width: 675,
			height: 200,
			nomsg: 'No Results', 
			buttons : [
	            {name: 'Add', bclass: 'add', onpress : addPeerReviewed},
	            {separator: true}, 
	            {name: 'Delete Selected', bclass: 'delete', onpress : deletePeerReviewed}
	            ]
			}
		);
			
		function addPeerReviewed(com,grid) {
	        if (com=='Add') {
	             window.location='<?php echo ENTRADA_URL; ?>/annualreport/research?section=add_peer_reviewed';
	        }            
	    }
	     
	    function editPeerReviewed(celDiv,id) {
	    	celDiv.innerHTML = "<a href='<?php echo ENTRADA_URL; ?>/annualreport/research?section=edit_peer_reviewed&amp;rid="+id+"' style=\"cursor: pointer; cursor: hand\" text-decoration: none><img src=\"<?php echo ENTRADA_RELATIVE; ?>/images/action-edit.gif\" style=\"border: none\"/></a>";
	    }
	    
	    function deletePeerReviewed(com,grid) {
		    if (com=='Delete Selected') {
		    	jQuery(function() {
					if(jQuery('.trSelected',grid).length>0) {
			    		// a workaround for a flaw in the demo system (http://dev.jqueryui.com/ticket/4375), ignore!
						jQuery("#dialog-confirm").dialog("destroy");
					
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
							       	
							       	window.setTimeout('peer_reviewed_grid.flexReload()', 1000);
									jQuery(this).dialog('close');
								},
								Cancel: function() {
									jQuery(this).dialog('close');
								}
							}
						});
			    	} else {
				    	jQuerydialog.dialog('open');
			    	}
				});
		    }          
		}
		<?php $fields = "ar_non_peer_reviewed_papers,non_peer_reviewed_papers_id,source,title,author_list,year_reported"; ?>
		non_peer_reviewed_grid = jQuery("#flex3").flexigrid
		(
			{
			url: '<?php echo ENTRADA_URL; ?>/api/ar_loadgrid.api.php?id=<?php echo $ENTRADA_USER->getActiveId(); ?>&t=<?php echo $fields; ?>',
			dataType: 'json',
			method: 'POST',
			colModel : [
				{display: 'Source', name : 'source', width : 100, sortable : true, align: 'left'},
				{display: 'Title', name : 'title', width : 384, sortable : true, align: 'left'},
				{display: 'Author List', name : 'author_list', width : 59, sortable : true, align: 'left'},
				{display: 'Year', name : 'year_reported', width : 50, sortable : true, align: 'left'},
				{display: 'Edit', name : 'ctled', width : 25,  sortable : false, align: 'center', process:editNonPeerReviewed}
				],
			searchitems : [
				{display: 'Source', name : 'source'},
				{display: 'Title', name : 'title'},
				{display: 'Author List', name : 'author_list'},
				{display: 'Year', name : 'year_reported', isdefault: true}
				],
			sortname: "year_reported",
			sortorder: "desc",
			resizable: false, 
			usepager: true,
			showToggleBtn: false,
			collapseTable: <?php echo ($_SESSION["research_expand_grid"] == "non_peer_reviewed_grid" ? "false" : "true"); ?>,
			title: 'C. Non-Peer Reviewed Publications',
			useRp: true,
			rp: 15,
			showTableToggleBtn: true,
			width: 675,
			height: 200,
			nomsg: 'No Results', 
			buttons : [
	            {name: 'Add', bclass: 'add', onpress : addNonPeerReviewed},
	            {separator: true}, 
	            {name: 'Delete Selected', bclass: 'delete', onpress : deleteNonPeerReviewed}
	            ]
			}
		);
			
		function addNonPeerReviewed(com,grid) {
	        if (com=='Add') {
	             window.location='<?php echo ENTRADA_URL; ?>/annualreport/research?section=add_non_peer_reviewed';
	        }            
	    }
	     
	    function editNonPeerReviewed(celDiv,id) {
	    	celDiv.innerHTML = "<a href='<?php echo ENTRADA_URL; ?>/annualreport/research?section=edit_non_peer_reviewed&amp;rid="+id+"' style=\"cursor: pointer; cursor: hand\" text-decoration: none><img src=\"<?php echo ENTRADA_RELATIVE; ?>/images/action-edit.gif\" style=\"border: none\"/></a>";
	    }
	    
	    function deleteNonPeerReviewed(com,grid) {
		    if (com=='Delete Selected') {
		    	jQuery(function() {
					if(jQuery('.trSelected',grid).length>0) {
			    		// a workaround for a flaw in the demo system (http://dev.jqueryui.com/ticket/4375), ignore!
						jQuery("#dialog-confirm").dialog("destroy");
					
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
							       	
							       	window.setTimeout('non_peer_reviewed_grid.flexReload()', 1000);
									jQuery(this).dialog('close');
								},
								Cancel: function() {
									jQuery(this).dialog('close');
								}
							}
						});
			    	} else {
				    	jQuerydialog.dialog('open');
			    	}
				});
		    }          
		}
		<?php $fields = "ar_book_chapter_mono,book_chapter_mono_id,source,title,author_list,year_reported"; ?>
		book_chapter_mono_grid = jQuery("#flex4").flexigrid
		(
			{
			url: '<?php echo ENTRADA_URL; ?>/api/ar_loadgrid.api.php?id=<?php echo $ENTRADA_USER->getActiveId(); ?>&t=<?php echo $fields; ?>',
			dataType: 'json',
			method: 'POST',
			colModel : [
				{display: 'Source', name : 'source', width : 100, sortable : true, align: 'left'},
				{display: 'Title', name : 'title', width : 384, sortable : true, align: 'left'},
				{display: 'Author List', name : 'author_list', width : 59, sortable : true, align: 'left'},
				{display: 'Year', name : 'year_reported', width : 50, sortable : true, align: 'left'},
				{display: 'Edit', name : 'ctled', width : 25,  sortable : false, align: 'center', process:editBookChapterMono}
				],
			searchitems : [
				{display: 'Source', name : 'source'},
				{display: 'Title', name : 'title'},
				{display: 'Author List', name : 'author_list'},
				{display: 'Year', name : 'year_reported', isdefault: true}
				],
			sortname: "year_reported",
			sortorder: "desc",
			resizable: false, 
			usepager: true,
			showToggleBtn: false,
			collapseTable: <?php echo ($_SESSION["research_expand_grid"] == "book_chapter_mono_grid" ? "false" : "true"); ?>,
			title: 'D. Books / Chapters / Monographs / Editorials',
			useRp: true,
			rp: 15,
			showTableToggleBtn: true,
			width: 675,
			height: 200,
			nomsg: 'No Results', 
			buttons : [
	            {name: 'Add', bclass: 'add', onpress : addBookChapterMono},
	            {separator: true}, 
	            {name: 'Delete Selected', bclass: 'delete', onpress : deleteBookChapterMono}
	            ]
			}
		);
			
		function addBookChapterMono(com,grid) {
	        if (com=='Add') {
	             window.location='<?php echo ENTRADA_URL; ?>/annualreport/research?section=add_book_chapter_mono';
	        }            
	    }
	     
	    function editBookChapterMono(celDiv,id) {
	    	celDiv.innerHTML = "<a href='<?php echo ENTRADA_URL; ?>/annualreport/research?section=edit_book_chapter_mono&amp;rid="+id+"' style=\"cursor: pointer; cursor: hand\" text-decoration: none><img src=\"<?php echo ENTRADA_RELATIVE; ?>/images/action-edit.gif\" style=\"border: none\"/></a>";
	    }
	    
	    function deleteBookChapterMono(com,grid) {
		    if (com=='Delete Selected') {
		    	jQuery(function() {
					if(jQuery('.trSelected',grid).length>0) {
			    		// a workaround for a flaw in the demo system (http://dev.jqueryui.com/ticket/4375), ignore!
						jQuery("#dialog-confirm").dialog("destroy");
					
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
							       	
							       	window.setTimeout('book_chapter_mono_grid.flexReload()', 1000);
									jQuery(this).dialog('close');
								},
								Cancel: function() {
									jQuery(this).dialog('close');
								}
							}
						});
			    	} else {
				    	jQuerydialog.dialog('open');
			    	}
				});
		    }          
		}
		<?php $fields = "ar_poster_reports,poster_reports_id,source,title,author_list,year_reported"; ?>
		poster_reports_grid = jQuery("#flex5").flexigrid
		(
			{
			url: '<?php echo ENTRADA_URL; ?>/api/ar_loadgrid.api.php?id=<?php echo $ENTRADA_USER->getActiveId(); ?>&t=<?php echo $fields; ?>',
			dataType: 'json',
			method: 'POST',
			colModel : [
				{display: 'Source', name : 'source', width : 100, sortable : true, align: 'left'},
				{display: 'Title', name : 'title', width : 384, sortable : true, align: 'left'},
				{display: 'Author List', name : 'author_list', width : 59, sortable : true, align: 'left'},
				{display: 'Year', name : 'year_reported', width : 50, sortable : true, align: 'left'},
				{display: 'Edit', name : 'ctled', width : 25,  sortable : false, align: 'center', process:editPosterReports}
				],
			searchitems : [
				{display: 'Source', name : 'source'},
				{display: 'Title', name : 'title'},
				{display: 'Author List', name : 'author_list'},
				{display: 'Year', name : 'year_reported', isdefault: true}
				],
			sortname: "year_reported",
			sortorder: "desc",
			resizable: false, 
			usepager: true,
			showToggleBtn: false,
			collapseTable: <?php echo ($_SESSION["research_expand_grid"] == "poster_reports_grid" ? "false" : "true"); ?>,
			title: 'E. Poster Presentations / Technical Reports',
			useRp: true,
			rp: 15,
			showTableToggleBtn: true,
			width: 675,
			height: 200,
			nomsg: 'No Results', 
			buttons : [
	            {name: 'Add', bclass: 'add', onpress : addPosterReports},
	            {separator: true}, 
	            {name: 'Delete Selected', bclass: 'delete', onpress : deletePosterReports}
	            ]
			}
		);
			
		function addPosterReports(com,grid) {
	        if (com=='Add') {
	             window.location='<?php echo ENTRADA_URL; ?>/annualreport/research?section=add_poster_reports';
	        }            
	    }
	     
	    function editPosterReports(celDiv,id) {
	    	celDiv.innerHTML = "<a href='<?php echo ENTRADA_URL; ?>/annualreport/research?section=edit_poster_reports&amp;rid="+id+"' style=\"cursor: pointer; cursor: hand\" text-decoration: none><img src=\"<?php echo ENTRADA_RELATIVE; ?>/images/action-edit.gif\" style=\"border: none\"/></a>";
	    }
	    
	    function deletePosterReports(com,grid) {
		    if (com=='Delete Selected') {
		    	jQuery(function() {
					if(jQuery('.trSelected',grid).length>0) {
			    		// a workaround for a flaw in the demo system (http://dev.jqueryui.com/ticket/4375), ignore!
						jQuery("#dialog-confirm").dialog("destroy");
					
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
							       	
							       	window.setTimeout('poster_reports_grid.flexReload()', 1000);
									jQuery(this).dialog('close');
								},
								Cancel: function() {
									jQuery(this).dialog('close');
								}
							}
						});
			    	} else {
				    	jQuerydialog.dialog('open');
			    	}
				});
		    }          
		}
		<?php $fields = "ar_conference_papers,conference_papers_id,institution,lectures_papers_list,location,year_reported"; ?>
			conference_papers_grid = jQuery("#flex6").flexigrid
			(
				{
				url: '<?php echo ENTRADA_URL; ?>/api/ar_loadgrid_conference.api.php?id=<?php echo $ENTRADA_USER->getActiveId(); ?>&t=<?php echo $fields; ?>',
				dataType: 'json',
				method: 'POST',
				colModel : [
					{display: 'Institution', name : 'institution', width : 150, sortable : true, align: 'left'},
					{display: 'Invited Lectures / Conference Papers', name : 'lectures_papers_list', width : 243, sortable : true, align: 'left'},
					{display: 'Location', name : 'location', width : 150, sortable : true, align: 'left'},
					{display: 'Year', name : 'year_reported', width : 50, sortable : true, align: 'left'},
					{display: 'Edit', name : 'ctled', width : 25,  sortable : false, align: 'center', process:editConferencePapers}
					],
				searchitems : [
					{display: 'Institution', name : 'institution'},
					{display: 'Invited Lectures / Conference Papers', name : 'lectures_papers_list'},
					{display: 'Location', name : 'location'},
					{display: 'Year', name : 'year_reported', isdefault: true}
					],
				sortname: "year_reported",
				sortorder: "desc",
				resizable: false, 
				usepager: true,
				showToggleBtn: false,
				collapseTable: <?php echo ($_SESSION["research_expand_grid"] == "conference_papers_grid" ? "false" : "true"); ?>,
				title: 'F. Invited Lectures / Conference Papers',
				useRp: true,
				rp: 15,
				showTableToggleBtn: true,
				width: 675,
				height: 200,
				nomsg: 'No Results', 
				buttons : [
	                {name: 'Add', bclass: 'add', onpress : addConferencePapers},
	                {separator: true}, 
	                {name: 'Delete Selected', bclass: 'delete', onpress : deleteConferencePapers}
	                ]
				}
			);
				
			function addConferencePapers(com,grid) {
	            if (com=='Add') {
	                 window.location='<?php echo ENTRADA_URL; ?>/annualreport/research?section=add_conference_papers';
	            }            
	        }
	         
	        function editConferencePapers(celDiv,id) {
	        	celDiv.innerHTML = "<a href='<?php echo ENTRADA_URL; ?>/annualreport/research?section=edit_conference_papers&amp;rid="+id+"' style=\"cursor: pointer; cursor: hand\" text-decoration: none><img src=\"<?php echo ENTRADA_RELATIVE; ?>/images/action-edit.gif\" style=\"border: none\"/></a>";
		    }
		    
	        function deleteConferencePapers(com,grid) {
			    if (com=='Delete Selected') {
			    	jQuery(function() {
						if(jQuery('.trSelected',grid).length>0) {
				    		// a workaround for a flaw in the demo system (http://dev.jqueryui.com/ticket/4375), ignore!
							jQuery("#dialog-confirm").dialog("destroy");
						
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
								       	
								       	window.setTimeout('conference_papers_grid.flexReload()', 1000);
										jQuery(this).dialog('close');
									},
									Cancel: function() {
										jQuery(this).dialog('close');
									}
								}
							});
				    	} else {
					    	jQuerydialog.dialog('open');
				    	}
					});
			    }          
			}
		<?php $fields = "ar_scholarly_activity,scholarly_activity_id,scholarly_activity_type,description,location,year_reported"; ?>
		scholarly_grid = jQuery("#flex7").flexigrid
		(
			{
			url: '<?php echo ENTRADA_URL; ?>/api/ar_loadgrid.api.php?id=<?php echo $ENTRADA_USER->getActiveId(); ?>&t=<?php echo $fields; ?>',
			dataType: 'json',
			method: 'POST',
			colModel : [
				{display: 'Activity Type', name : 'scholarly_activity_type', width : 100, sortable : true, align: 'left'},
				{display: 'Description', name : 'description', width : 243, sortable : true, align: 'left'},
				{display: 'Location', name : 'location', width : 200, sortable : true, align: 'left'},
				{display: 'Year', name : 'year_reported', width : 50, sortable : true, align: 'left'},
				{display: 'Edit', name : 'ctled', width : 25,  sortable : false, align: 'center', process:editScholarly}
				],
			searchitems : [
				{display: 'Activity Type', name : 'scholarly_activity_type'},
				{display: 'Description', name : 'description'},
				{display: 'Location', name : 'location'},
				{display: 'Year', name : 'year_reported', isdefault: true}
				],
			sortname: "year_reported",
			sortorder: "desc",
			resizable: false, 
			usepager: true,
			showToggleBtn: false,
			collapseTable: <?php echo ($_SESSION["research_expand_grid"] == "scholarly_grid" ? "false" : "true"); ?>,
			title: 'G. Other Scholarly Activity', 
			useRp: true,
			rp: 15,
			showTableToggleBtn: true,
			width: 675,
			height: 200,
			nomsg: 'No Results', 
			buttons : [
	            {name: 'Add', bclass: 'add', onpress : addScholarly},
	            {separator: true}, 
	            {name: 'Delete Selected', bclass: 'delete', onpress : deleteScholarly}
	            ]
			}
		);
			
		function addScholarly(com,grid) {
	        if (com=='Add') {
	             window.location='<?php echo ENTRADA_URL; ?>/annualreport/research?section=add_scholarly';
	        }            
	    }
	     
	    function editScholarly(celDiv,id) {
	    	celDiv.innerHTML = "<a href='<?php echo ENTRADA_URL; ?>/annualreport/research?section=edit_scholarly&amp;rid="+id+"' style=\"cursor: pointer; cursor: hand\" text-decoration: none><img src=\"<?php echo ENTRADA_RELATIVE; ?>/images/action-edit.gif\" style=\"border: none\"/></a>";
	    }
	    
	    function deleteScholarly(com,grid) {
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
							       	
							       	window.setTimeout('scholarly_grid.flexReload()', 1000);
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
		
		<?php $fields = "ar_patent_activity,patent_activity_id,patent_activity_type,description,year_reported"; ?>
		patent_grid = jQuery("#flex8").flexigrid
		(
			{
			url: '<?php echo ENTRADA_URL; ?>/api/ar_loadgrid.api.php?id=<?php echo $ENTRADA_USER->getActiveId(); ?>&t=<?php echo $fields; ?>',
			dataType: 'json',
			method: 'POST',
			colModel : [
				{display: 'Type', name : 'patent_activity_type', width : 175, sortable : true, align: 'left'},
				{display: 'Description', name : 'description', width : 379, sortable : true, align: 'left'},
				{display: 'Year', name : 'year_reported', width : 50, sortable : true, align: 'left'},
				{display: 'Edit', name : 'ctled', width : 25,  sortable : false, align: 'center', process:editPatent}
				],
			searchitems : [
				{display: 'Type', name : 'patent_activity_type'},
				{display: 'Description', name : 'description'},
				{display: 'Year', name : 'year_reported', isdefault: true}
				],
			sortname: "year_reported",
			sortorder: "desc",
			resizable: false, 
			usepager: true,
			showToggleBtn: false,
			collapseTable: <?php echo ($_SESSION["research_expand_grid"] == "patent_grid" ? "false" : "true"); ?>,
			title: 'H. Patents',
			useRp: true,
			rp: 15,
			showTableToggleBtn: true,
			width: 675,
			height: 200,
			nomsg: 'No Results', 
			buttons : [
	            {name: 'Add', bclass: 'add', onpress : addPatent},
	            {separator: true}, 
	            {name: 'Delete Selected', bclass: 'delete', onpress : deletePatent}
	            ]
			}
		);
			
		function addPatent(com,grid) {
	        if (com=='Add') {
	             window.location='<?php echo ENTRADA_URL; ?>/annualreport/research?section=add_patent';
	        }            
	    }
	     
	    function editPatent(celDiv,id) {
	    	celDiv.innerHTML = "<a href='<?php echo ENTRADA_URL; ?>/annualreport/research?section=edit_patent&amp;rid="+id+"' style=\"cursor: pointer; cursor: hand\" text-decoration: none><img src=\"<?php echo ENTRADA_RELATIVE; ?>/images/action-edit.gif\" style=\"border: none\"/></a>";
	    }
	    
	    function deletePatent(com,grid) {
		    if (com=='Delete Selected') {
		    	jQuery(function() {
		    		var error = "false";
					if(jQuery('.trSelected',grid).length>0) {
			    		// a workaround for a flaw in the demo system (http://dev.jqueryui.com/ticket/4375), ignore!
						jQuery("#dialog-confirm").dialog("destroy");
						jQuery('.trSelected', grid).each(function() {  
	               			var reportYear = jQuery(this).find('div')[2].textContent;
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
							       	
							       	window.setTimeout('patent_grid.flexReload()', 1000);
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