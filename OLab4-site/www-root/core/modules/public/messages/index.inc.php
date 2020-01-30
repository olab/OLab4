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
 * This is the main dashboard that people see when they log into Entrada
 * and have not requested another page or module.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@qmed.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 *
*/

if (!defined("PARENT_INCLUDED")) exit;

if (!$ENTRADA_ACL->amIAllowed("dashboard", "read")) {

	add_error("Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else { 
	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery.dataTables.min.js"."\"></script>";
	
	if (isset($_GET["ajax"]) && $_GET["ajax"] && isset($_GET["method"]) && $_GET["method"] == "list") {
		ob_clear_open_buffers();
		$output = array("aaData" => array());
		$notices = Models_Notice::fetchUserNotices(false, true);
		$count = 0;
		if ($notices) {
			/*
			* Ordering
			*/
			if (isset($_GET["iSortCol_0"]) && in_array($_GET["iSortCol_0"], array(0, 1, 2))) {
				$aColumns = array("updated_date", "notice_summary", "last_read");
				$sort_array = array();
				foreach ($notices as $notice) {
					$notice_array = $notice;
					$sort_array[] = $notice_array[$aColumns[clean_input($_GET["iSortCol_0"], "int")]];
				}
				array_multisort($sort_array, (isset($_GET["sSortDir_0"]) && $_GET["sSortDir_0"] == "desc" ? SORT_DESC : SORT_ASC), (clean_input($_GET["iSortCol_0"], "int") == 2 ? SORT_NUMERIC : SORT_STRING), $notices);
			}
			if (isset($_GET["iDisplayStart"]) && isset($_GET["iDisplayLength"]) && $_GET["iDisplayLength"] != "-1" ) {
				$start = (int)$_GET["iDisplayStart"];
				$limit = (int)$_GET["iDisplayLength"];
			} else {
				$start = 0;
				$limit = count($notices) - 1;
			}
			if ($_GET["sSearch"] != "") {
				$search_value = $_GET["sSearch"];
			}
			foreach ($notices as $notice) {
				$notice["notice_summary"] = strip_tags($notice["notice_summary"]);
				$notice["notice_summary"] = (strlen($notice["notice_summary"]) > 40 ? substr($notice["notice_summary"], 0, 40)."..." : $notice["notice_summary"]);
				if (!isset($search_value) || stripos(date("Y-m-d g:ia", $notice["updated_date"]), $search_value) !== false || stripos($notice["notice_summary"], $search_value) !== false || stripos(date("Y-m-d g:ia", $notice["last_read"]), $search_value) !== false) {
					if ($count >= $start && $count < ($start + $limit)) {
						$row = array();
						$row["updated_date"] = "<a href=\"". ENTRADA_URL ."/messages?section=view&notice_id=". $notice["notice_id"] ."\">".date("Y-m-d g:ia", $notice["updated_date"])."</a>";
						$row["notice_summary"] = "<a href=\"". ENTRADA_URL ."/messages?section=view&notice_id=". $notice["notice_id"] ."\">".$notice["notice_summary"]."</a>";
						$row["last_read"] = "<a href=\"". ENTRADA_URL ."/messages?section=view&notice_id=". $notice["notice_id"] ."\">".date("Y-m-d g:ia", $notice["last_read"])."</a>";
						$row["id"] = $notice["notice_id"];
						$output["aaData"][] = $row;
					}
					$count++;
				}
			}
		}
		$output["iTotalRecords"] = (is_array($notices) ? @count($notices) : 0);
		$output["iTotalDisplayRecords"] = $count;
		$output["sEcho"] = clean_input($_GET["sEcho"], "int");
		if ($output && count($output)) {
		   echo json_encode($output);
		}
		exit;
	}
	?>
	<script type="text/javascript">
		jQuery(document).ready(function () {
			jQuery('#notice-list').dataTable(
				{
					'sPaginationType': 'full_numbers',
					'bInfo': false,
					'bAutoWidth': false,
					'sAjaxSource': '?ajax=true&method=list',
					'bServerSide': true,
					'bProcessing': true,
					'aoColumns': [
						{ 'mDataProp': 'updated_date' },
						{ 'mDataProp': 'notice_summary' },
						{ 'mDataProp': 'last_read' },
					],
					'aoColumnDefs': [ {
						'aTargets': [0,1,2,],
						'fnCreatedCell': function (nTd, sData, oData, iRow, iCol) {
							if ( iCol == 0 ) {
								jQuery(nTd).addClass('date')
							} else if ( iCol == 1 ) {
								jQuery(nTd).addClass('notice')
							} else if ( iCol == 2 ) {
								jQuery(nTd).addClass('last-read')
							}
						}
					}],
					'fnRowCallback': function(nRow, aData, iDisplayIndex, iDisplayIndexFull) {
						jQuery(nRow).attr('data-id', aData.id);

						return nRow;
					},
					'oLanguage': {
						'sEmptyTable': 'There are currently no previoulsy viewed messages in the system.',
						'sZeroRecords': 'No previoulsy viewed messages to display.'
					}

				}
			);
		}); 
		
	</script>
	<h1>Previously Read Messages</h1>
	<table class="table table-striped table-bordered" id="notice-list">
		<thead>
			<tr>
				<th width="30%">Date Posted</th>
				<th width="40%">Message Summary</th>
				<th width="30%">Last Read</th>
			</tr>
		</thead>
		<tbody>

		</tbody>
	</table>
<?php
}