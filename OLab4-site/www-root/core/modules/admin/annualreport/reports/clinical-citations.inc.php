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
 * This file is used to display facutly completion of their annual report
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Andrew Dos-Santos <andrew.dos-santos@queensu.ca>
 * @copyright Copyright 2011 Queen's University. All Rights Reserved.
 *
*/

if((!defined("PARENT_INCLUDED")) || (!defined("IN_ANNUAL_REPORT"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif(!$ENTRADA_ACL->amIAllowed('annualreportadmin', 'read', false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {	
	$BREADCRUMB[]	= array("url" => "", "title" => "Clinical Citations");
	$csv_delimiter = ",";
	$csv_enclosure = '"';
	
	$years = getMinMaxARYears();
	
	if(isset($years["start_year"]) && $years["start_year"] != "") {
		$PROCESSED["report_type"] = $_POST['report_type'];
		$PROCESSED["year_reported"] = $_POST['year_reported'];
		$PROCESSED["department_id"] = $_POST['department_id'];
		?>
		<style type="text/css">
		h1 {
			page-break-before:	always;
			border-bottom:		2px #CCCCCC solid;
			font-size:			24px;
		}
		
		h2 {
			font-weight:		normal;
			border:				0px;
			font-size:			18px;
		}
		
		div.top-link {
			float: right;
		}
		</style>
		<a name="top"></a>
		<div class="no-printing">
			<form action="<?php echo ENTRADA_URL; ?>/admin/annualreport/reports?section=<?php echo $SECTION; ?>&step=2" method="post">
			<input type="hidden" name="update" value="1" />
			<table style="width: 100%" cellspacing="0" cellpadding="2" border="0">
			<colgroup>
				<col style="width: 1%" />
				<col style="width: 20%" />
				<col style="width: 77%" />
			</colgroup>
			<tbody>
				<tr>
					<td colspan="3"><h2>Report Options</h2></td>
				</tr>
				<tr>
					<td></td>
					<td><label for="year_reported" class="form-required">Reporting Period</label></td>
					<td><select name="year_reported" id="year_reported" style="vertical-align: middle">
					<?php
						for($i=$years["start_year"]; $i<=$years["end_year"]; $i++)
						{
							if(isset($PROCESSED["year_reported"]) && $PROCESSED["year_reported"] != '')
							{
								$defaultStartYear = $PROCESSED["year_reported"];
							}
							else 
							{
								$defaultStartYear = $years["end_year"];
							}
							echo "<option value=\"".$i."\"".(($defaultStartYear == $i) ? " selected=\"selected\"" : "").">".$i."</option>\n";
						}
						echo "</select>";
					?>
					</td>
				</tr>
				<!--
				<tr>
					<td></td>
					<td><label for="report_type" class="form-required">Faculty to Display</label></td>
					<td><select name="report_type" id="report_type" style="vertical-align: middle">
					<?php
						//echo "<option value=\"All\"".(($PROCESSED["report_type"] == "All") ? " selected=\"selected\"" : "").">All Faculty</option>\n";
						//echo "<option value=\"Clinical\"".(($PROCESSED["report_type"] == "Clinical") ? " selected=\"selected\"" : "").">Clinical Faculty</option>\n";
						//echo "<option value=\"Non-Clinical\"".(($PROCESSED["report_type"] == "Non-Clinical") ? " selected=\"selected\"" : "").">Non-Clinical Faculty</option>\n";
						//echo "</select>";
					?>
					</td>
				</tr>
				-->
				<tr>
					<td></td>
					<td><label for="department_id" class="form-required">Department</label></td>
					<td><select name="department_id" id="department_id" style="vertical-align: middle">
					<?php
						$departments = get_distinct_user_departments();
						foreach($departments as $department) {
							echo "<option value=\"".$department["department_id"]."\"".(($PROCESSED["department_id"] == $department["department_id"]) ? " selected=\"selected\"" : "").">".$department["department_title"]."</option>\n";
						}						
						echo "</select>";
					?>
					</td>
				</tr>
				<tr>
					<td colspan="3" style="text-align: right; padding-top: 10px"><input type="submit" class="btn btn-primary" value="Create Report" /></td>
				</tr>
			</tbody>
			</table>
			</form>
		</div>
		<?php
		if ($STEP == 2) {
			$oringial_divisions = fetch_department_children($PROCESSED["department_id"]);
			$departmentString = fetch_department_title($PROCESSED["department_id"]);
			$prevDepartment = "";
			$co = 0;
			$pi = 0;
			
			if($oringial_divisions === false) {
				$divisions = $PROCESSED["department_id"];
				$multipleDivisions = false;
			} else {
				$divisions = array();
				foreach($oringial_divisions as $division_id) {
					$divisions[] = $division_id["department_id"];
				}
				$divisions = implode(",", $divisions);
				$divisions.=",".$PROCESSED["department_id"];
				$multipleDivisions = true;
			}
			$divisionCo = 0;
			$divisionPi = 0;			
			$totalsCo = 0;
			$totalsPi = 0;
			$citationArray = array();
			
			$controlBreak = false;
			$firstTotalOutput = true;

			$query = "SELECT DISTINCT `proxy_id`, `firstname`, `lastname`, `department_title`, `parent_id`, `dep_id`
			FROM `".DATABASE_NAME."`.`ar_peer_reviewed_papers`, `".AUTH_DATABASE."`.`user_data`, `".AUTH_DATABASE."`.`user_departments`, `".AUTH_DATABASE."`.`departments`
			WHERE `year_reported` = ".$db->qstr($PROCESSED["year_reported"])."
			AND `".AUTH_DATABASE."`.`user_data`.`clinical` = '1'
			AND `".DATABASE_NAME."`.`ar_peer_reviewed_papers`.`proxy_id` = `".AUTH_DATABASE."`.`user_data`.`id` 
			AND `".AUTH_DATABASE."`.`user_data`.`id` = `".AUTH_DATABASE."`.`user_departments`.`user_id`
			AND `".AUTH_DATABASE."`.`user_departments`.`dep_id` = `".AUTH_DATABASE."`.`departments`.`department_id`
			AND `dep_id` IN(".$divisions.")
			ORDER BY `department_title` ASC, `lastname` ASC, `firstname` ASC";

			$results	= $db->GetAll($query);
			if ($results) {
				$output = array();
				foreach ($results as $result) {
					if($multipleDivisions && $prevDepartment != $result["dep_id"]) {
						$divisionString = fetch_department_title($result["dep_id"]);
						if($prevDepartment != "") {
							$firstTotalOutput = true;
						}
						$prevDepartment = $result["dep_id"];
						$division = $divisionString;
					} else if($firstTotalOutput == true && !$multipleDivisions) {
						$firstTotalOutput = false;
						$division = "";
					}
					
					$query = "SELECT `title`, `source`, `author_list`, `volume`, `edition`, `pages`, `role_description`, `status`, `category`
					FROM `ar_peer_reviewed_papers`, `ar_lu_pr_roles`
					WHERE `proxy_id` = ".$db->qstr($result["proxy_id"])."
					AND `ar_peer_reviewed_papers`.`role_id` =`ar_lu_pr_roles`.`role_id`
					AND `type_id` = '1'
					AND `year_reported` = ".$db->qstr($PROCESSED["year_reported"])."
					ORDER BY `title` ASC, `source` ASC";

					if($researchResults	= $db->GetAll($query)) {
						foreach($researchResults as $researchResult) {
							$key = $researchResult["title"].$researchResult["source"].$researchResult["role_description"];
							if(!in_array($key, $citationArray)) {
								$citationArray[] = $key;
								if($controlBreak == false) {
									$lastname = $result["lastname"];
									$firstname = $result["firstname"];
									$controlBreak = true;
								} else {
									$firstname = "";
									$lastname = "";
								}

								if ($result["parent_id"] == 0) {
									$department = $result["department_title"];
								} else {
									$query = "SELECT * FROM `". AUTH_DATABASE ."`.`departments` WHERE `department_id` = ?";
									$department_result = $db->GetRow($query, array($result["parent_id"]));
									if ($department_result) {
										$department = $department_result["department_title"];
									}
								}

								$title = $researchResult["title"];
								$source = $researchResult["source"];
								$author_list = $researchResult["author_list"];
								$volume = $researchResult["volume"];
								$edition = $researchResult["edition"];
								$pages = $researchResult["pages"];
								$role = $researchResult["role_description"];
								$status = $researchResult["status"];
								$output[] = array($department, $division, $lastname, $firstname, $title, $source, $author_list, $volume, $edition, $pages, $role, $status);
							}
						}
						$controlBreak = false;
					}
				}

				if (isset($output)) {
					ob_clear_open_buffers();
					header("Pragma: public");
					header("Expires: 0");
					header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
					header("Content-Type: application/force-download");
					header("Content-Type: application/octet-stream");
					header("Content-Type: text/csv");
					header("Content-Disposition: attachment; filename=\"clinical-citations-" . $PROCESSED["year_reported"] . "-" . $department . ".csv\"");
					header("Content-Transfer-Encoding: binary");

					$fp = fopen("php://output", "w");
					$headings = array("Department", "Division", "Last Name", "First Name", "Title", "Source", "Author List", "Volume", "Edition", "Pages", "Role", "Status");

					fputcsv($fp, $headings, $csv_delimiter, $csv_enclosure);
					foreach ($output as $row) {
						fputcsv($fp, $row, $csv_delimiter, $csv_enclosure);
					}

					fclose($fp);

					exit;
				} else {
					echo display_notice(array("There are no records in the system for the qualifiers you have selected."));
				}
			} else {
				echo display_notice(array("There are no records in the system for the qualifiers you have selected."));
			}
		}
	} else {
		echo display_notice(array("There are no annual reports in the system yet."));
	}
}
?>