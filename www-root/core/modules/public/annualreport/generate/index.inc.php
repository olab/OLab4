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
	if(!isset($_SESSION["reports_expand_grid"])) {
		$_SESSION["reports_expand_grid"] = "reports_grid";
	}
	
	if($ENTRADA_USER->getClinical() == 0) {
		$clinical_member = "NO";
	} else {
		$clinical_member = "YES";
	}
	?>
	<h1>Section <?php echo ($ENTRADA_USER->getClinical() ? "VIII" : "VII"); ?> - Annual Report Generator</h1>
	
	<table id="flex1" style="display:none"></table>
	
	<?php $fields = "ar_profile,profile_id,report_completed,career_goals,consistent,year_reported"; ?>
	
	<script type="text/javascript" defer="defer">
	jQuery(document).ready(function() {
		reports_grid = jQuery("#flex1").flexigrid
		(
			{
			url: '<?php echo ENTRADA_URL; ?>/api/ar_loadgrid.api.php?id=<?php echo $ENTRADA_USER->getActiveId(); ?>&t=<?php echo $fields; ?>',
			dataType: 'json',
			method: 'POST',
			colModel : [
				{display: 'Report Completed', name : 'report_completed', width : 167, sortable : true, align: 'left'},
				{display: 'In Keeping With Career Goals', name : 'career_goals', width : 188, sortable : true, align: 'left'},
				{display: 'Consistent', name : 'consistent', width : 163, sortable : true, align: 'left'},
				{display: 'Year', name : 'year_reported', width : 50, sortable : true, align: 'left'},
				{display: 'Generate', name : 'ctlgo', width : 50,  sortable : false, align: 'center', process:reportGo}
				],
			searchitems : [
				{display: 'Report Completed', name : 'report_completed'},
				{display: 'In Keeping With Career Goals', name : 'career_goals'},
				{display: 'Consistent', name : 'consistent'},
				{display: 'Year', name : 'year_reported', isdefault: true}
				],
			sortname: "year_reported",
			sortorder: "desc",
			resizable: false, 
			usepager: true,
			showToggleBtn: false,
			singleSelect: true,
			collapseTable: <?php echo ($_SESSION["reports_expand_grid"] == "reports_grid" ? "false" : "true"); ?>,
			title: 'A. Generate Annual Report',
			useRp: true,
			rp: 15,
			showTableToggleBtn: true,
			width: 675,
			height: 200,
			nomsg: 'No Results', 
			buttons : [
	            {name: 'Generate Report', bclass: 'report_go', onpress : generateReport}
	            ]
			}
		);
	    
	    function generateReport(com,grid) {
	        if (com=='Generate Report') {
	        	jQuery(function() {
					if(jQuery('.trSelected',grid).length>0) {
						jQuery('.trSelected', grid).each(function() {
							var id = jQuery(this).attr('id');
							id = id.substring(id.lastIndexOf('row')+3);
							window.location='<?php echo ENTRADA_URL; ?>/annualreport/generate?section=generate-annual-report&amp;rid='+id+'&amp;proxy_id='+<?php echo $ENTRADA_USER->getActiveId(); ?>+'&amp;clinical=<?php echo $clinical_member; ?>';
						});
			    	} 
				});
	        }            
	    }
	     
	    function reportGo(celDiv,id) {
	    	celDiv.innerHTML = "<a href='<?php echo ENTRADA_URL; ?>/annualreport/generate?section=generate-annual-report&amp;rid="+id+"&amp;proxy_id="+<?php echo $ENTRADA_USER->getActiveId(); ?>+"&amp;clinical=<?php echo $clinical_member; ?>' style=\"cursor: pointer; cursor: hand\" text-decoration: none><img src=\"<?php echo ENTRADA_RELATIVE; ?>/css/jquery/images/report_go.gif\" style=\"border: none\"/></a>";
	    }
	});
	</script>
	<?php
}