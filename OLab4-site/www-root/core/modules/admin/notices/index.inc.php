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
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_NOTICES"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("notice", "update", false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery.dataTables.min.js"."\"></script>";
	$notices = Models_Notice::fetchOrganisationNotices();
	if (isset($_GET["ajax"]) && $_GET["ajax"] && isset($_GET["method"]) && $_GET["method"] == "list") {
		ob_clear_open_buffers();
		$output = array("aaData" => array());
		$count = 0;
		if ($notices) {
			/*
			* Ordering
			*/
			if (isset($_GET["iSortCol_0"]) && in_array($_GET["iSortCol_0"], array(1, 2, 3))) {
				$aColumns = array("notice_id", "display_until", "notice_author", "notice_summary");
				$sort_array = array();
				foreach ($notices as $notice) {
					$notice_array = $notice;
					$sort_array[] = $notice_array[$aColumns[clean_input($_GET["iSortCol_0"], "int")]];
				}
				array_multisort($sort_array, (isset($_GET["sSortDir_0"]) && $_GET["sSortDir_0"] == "desc" ? SORT_DESC : SORT_ASC), (clean_input($_GET["iSortCol_0"], "int") == 1 ? SORT_NUMERIC : SORT_STRING), $notices);
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
				$notice["notice_summary"] = (strlen($notice["notice_summary"]) > 45 ? substr($notice["notice_summary"], 0, 45)."..." : $notice["notice_summary"]);
				if (!isset($search_value) || stripos(date("Y-m-d g:ia", $notice["display_until"]), $search_value) !== false || stripos($notice["notice_author"], $search_value) !== false || stripos($notice["notice_summary"], $search_value) !== false) {
					if ($count >= $start && $count < ($start + $limit)) {
						$row = array();
						$row["modified"] = "<input class=\"delete-control\" type=\"checkbox\" name=\"delete[]\" value=\"".$notice["notice_id"]."\" />";
						$row["display_until"] = "<a href=\"". ENTRADA_RELATIVE."/admin/notices?section=edit&amp;id=".$notice["notice_id"] ."\">".date("Y-m-d g:ia", $notice["display_until"])."</a>";
						$row["notice_author"] = "<a href=\"". ENTRADA_RELATIVE."/admin/notices?section=edit&amp;id=".$notice["notice_id"] ."\">". $notice["notice_author"] ."</a>";
						$row["notice_summary"] = "<a href=\"". ENTRADA_RELATIVE."/admin/notices?section=edit&amp;id=".$notice["notice_id"] ."\">". $notice["notice_summary"] ."</a>";
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
			jQuery("#delete-notices").on("click", function (event) {
				var checked = document.querySelectorAll("input.delete-control:checked").length === 0 ? false : true;
				if (!checked) {
					event.preventDefault();
					var errors = new Array();
					errors[0] = "You must select at least 1 notice to delete by checking the checkbox to the left the notice.";
					display_error(errors, "#msg");
				}
			});
			
			jQuery('#notice-list').dataTable(
				{
					'sPaginationType': 'full_numbers',
					'bInfo': false,
					'bAutoWidth': false,
					'sAjaxSource': '?ajax=true&method=list',
					'bServerSide': true,
					'bProcessing': true,
					'aoColumns': [
						{ 'mDataProp': 'modified', 'bSortable': false },
						{ 'mDataProp': 'display_until' },
						{ 'mDataProp': 'notice_author' },
						{ 'mDataProp': 'notice_summary' }
					],
					'aoColumnDefs': [ {
						'aTargets': [0,1,2,3],
						'fnCreatedCell': function (nTd, sData, oData, iRow, iCol) {
							if (iCol == 0) {
								jQuery(nTd).addClass('modified')
							} else if ( iCol == 1 ) {
								jQuery(nTd).addClass('until')
							} else if ( iCol == 2 ) {
								jQuery(nTd).addClass('author')
							} else if ( iCol == 3 ) {
								jQuery(nTd).addClass('summary')
							}
						}
					}],
					'fnRowCallback': function(nRow, aData, iDisplayIndex, iDisplayIndexFull) {
						jQuery(nRow).attr('data-id', aData.id);

						return nRow;
					},
					'oLanguage': {
						'sEmptyTable': 'There are currently no notices in the system.',
						'sZeroRecords': 'No notices found.'
					},
                    'aaSorting': [[ 1, 'desc' ]]

				}
			);
		}); 
	</script>
    <h1><?php echo $MODULES[strtolower($MODULE)]["title"]; ?></h1>
	<div class="display-generic">
		These notices will be displayed to the user directly on their <?php echo APPLICATION_NAME; ?> dashboard as well as on a publicly accessible RSS feed. <strong>Please note</strong> we do not recommend posting confidential information inside these notices.
	</div>
	<div id="msg"></div>
	
    <?php
	if ($ENTRADA_ACL->amIAllowed("notice", "create", false)) {
		?>
		<div class="row-fluid space-below">
			<a href="<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>?section=add" class="pull-right btn btn-primary">Add New Notice</a>
		</div>
		<?php
	}
		?>
		<form action="<?php echo ENTRADA_URL; ?>/admin/notices?section=delete" method="post">
			<table id="notice-list" class="table table-striped table-bordered" summary="List of Notices">
				<thead>
					<tr>
						<th width="5%"></th>
						<th width="25%">Display Until</th>
						<th width="25%">Notice Author</th>
						<th width="45%">Notice Summary</th>
					</tr>
				</thead>
				<tbody>
					
				</tbody>
			</table>
			<?php 
			if ($notices) { ?>
			<div class="row-fluid">
				<input id="delete-notices" type="submit" class="btn btn-danger" value="Delete Selected" />
			</div>
			<?php 
			}
			?>
		</form>
		<?php
}