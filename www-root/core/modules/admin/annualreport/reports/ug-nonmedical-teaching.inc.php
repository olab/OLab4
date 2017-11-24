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
	$BREADCRUMB[]	= array("url" => "", "title" => "Undergraduate Non-Medical Teaching" );
	
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
				<tr>
					<td></td>
					<td><label for="report_type" class="form-required">Faculty to Display</label></td>
					<td><select name="report_type" id="report_type" style="vertical-align: middle">
					<?php
						echo "<option value=\"All\"".(($PROCESSED["report_type"] == "All") ? " selected=\"selected\"" : "").">All Faculty</option>\n";
						echo "<option value=\"Clinical\"".(($PROCESSED["report_type"] == "Clinical") ? " selected=\"selected\"" : "").">Clinical Faculty</option>\n";
						echo "<option value=\"Non-Clinical\"".(($PROCESSED["report_type"] == "Non-Clinical") ? " selected=\"selected\"" : "").">Non-Clinical Faculty</option>\n";
						echo "</select>";
					?>
					</td>
				</tr>
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
			switch($PROCESSED["report_type"]) {
				case "Clinical":
					$title_suffix = " Clinical Facutly";
					$type_where	= " AND `".AUTH_DATABASE."`.`user_data`.`clinical` = '1'";
					break;
				case "Non-Clinical":
					$title_suffix = " Non-Clinical Facutly";
					$type_where	= " AND `".AUTH_DATABASE."`.`user_data`.`clinical` = '0'";
					break;
				default:
				case "All":
					$title_suffix = " All Facutly";
					$type_where = "";
					break;
			}
			
			$oringial_divisions = fetch_department_children($PROCESSED["department_id"]);
			$departmentString = fetch_department_title($PROCESSED["department_id"]);
			$prevDepartment = "";
			$prevProxyID = "";
			
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
			$divisionTotals = array();
			$totals = array();
			$firstTotalOutput = true;

			$query = "SELECT DISTINCT `proxy_id`, `firstname`, `lastname`, `department_title`, `dep_id`
			FROM `".DATABASE_NAME."`.`ar_undergraduate_nonmedical_teaching`, `".AUTH_DATABASE."`.`user_data`, `".AUTH_DATABASE."`.`user_departments`, `".AUTH_DATABASE."`.`departments`
			WHERE `year_reported` = ".$db->qstr($PROCESSED["year_reported"]).$type_where."
			AND `".DATABASE_NAME."`.`ar_undergraduate_nonmedical_teaching`.`proxy_id` = `".AUTH_DATABASE."`.`user_data`.`id` 
			AND `".AUTH_DATABASE."`.`user_data`.`id` = `".AUTH_DATABASE."`.`user_departments`.`user_id`
			AND `".AUTH_DATABASE."`.`user_departments`.`dep_id` = `".AUTH_DATABASE."`.`departments`.`department_id`
			AND `dep_id` IN(".$divisions.")
			ORDER BY `department_title` ASC, `lastname` ASC, `firstname` ASC";
			
			$results	= $db->GetAll($query);
			
			if ($results) {
				echo "<h2>Annual Report Undergraduate Non-Medical Education data for ".$title_suffix." in " . $departmentString."</h2>";
				echo "<div class=\"content-small\" style=\"margin-bottom: 10px\">\n";
				echo "	<strong>Reporting Period:</strong> ".$PROCESSED["year_reported"]." <strong>";
				echo "</div>\n";
				
				foreach ($results as $result) {
					if($multipleDivisions && $prevDepartment != $result["dep_id"]) {
						$divisionString = fetch_department_title($result["dep_id"]);
						if($prevDepartment != "") {
							if(isset($divisionTotals) && count($divisionTotals) > 0) {
								foreach($divisionTotals as $key=>$outputTotals) {
									if($firstTotalOutput == true) {
										$firstTotalOutput = false;
										echo "	<tr><td style=\"width: 5%; font-weight: bold\">Totals:</td>\n";
									} else {
										echo "	<tr><td style=\"width: 5%; font-weight: bold\">&nbsp;</td>\n";
									}
									echo "	<td style=\"width: 3%; font-weight: bold\">".$key."</td>\n";
									echo "	<td style=\"width: 1%; font-weight: bold\">".number_format($outputTotals["lec_hours"], 2)."</td>\n";
									echo "	<td style=\"width: 1%; font-weight: bold\">".number_format($outputTotals["lab_hours"], 2)."</td>\n";
									echo "	<td style=\"width: 1%; font-weight: bold\">".number_format($outputTotals["tut_hours"], 2)."</td>\n";
									echo "	<td style=\"width: 1%; font-weight: bold\">".number_format($outputTotals["sem_hours"], 2)."</td>\n";
									echo "	<td style=\"width: 1%; font-weight: bold\">".number_format($outputTotals["pbl_hours"], 2)."</td></tr>";
								}
							}
							$divisionTotals = array();
							echo "</tbody></table><br />";
							$firstTotalOutput = true;
						}
						$prevDepartment = $result["dep_id"];
						?>
						<table class="tableList" cellspacing="0" summary="Undergraduate Non-Medical Education Breakdown">
							<colgroup>
								<col style="width: 5%" />
								<col style="width: 3%">
								<col style="width: 1%">
								<col style="width: 1%">
								<col style="width: 1%">
								<col style="width: 1%">
								<col style="width: 1%">
							</colgroup>
							<thead>
								<tr>
									<td style="width: 5%">Name</td>
									<td style="width: 3%" >Course</td>
									<td style="width: 1%" >Lec</td>
									<td style="width: 1%" >Lab</td>
									<td style="width: 1%" >Tutorial</td>
									<td style="width: 1%" >Seminar</td>
									<td style="width: 1%" >PBL</td>
								</tr>
							</thead>
							<tbody>
							<?php
						echo "<h3>".$divisionString."</h3>";
					} else if($firstTotalOutput == true && !$multipleDivisions) {
						$firstTotalOutput = false;
						?>
						<table class="tableList" cellspacing="0" summary="Undergraduate Non-Medical Education Breakdown">
						<colgroup>
							<col style="width: 5%" />
							<col style="width: 3%">
							<col style="width: 1%">
							<col style="width: 1%">
							<col style="width: 1%">
							<col style="width: 1%">
							<col style="width: 1%">
						</colgroup>
						<thead>
							<tr>
								<td style="width: 5%">Name</td>
								<td style="width: 3%" >Course</td>
								<td style="width: 1%" >Lec</td>
								<td style="width: 1%" >Lab</td>
								<td style="width: 1%" >Tutorial</td>
								<td style="width: 1%" >Seminar</td>
								<td style="width: 1%" >PBL</td>
							</tr>
						</thead>
						<tbody>
						<?php
					}
					$query = "SELECT `course_number`, `lec_hours`, `lab_hours`, `tut_hours`, `sem_hours`, `pbl_hours`
					FROM `ar_undergraduate_nonmedical_teaching` 
					WHERE `proxy_id` = ".$db->qstr($result["proxy_id"])."
					AND `year_reported` = ".$db->qstr($PROCESSED["year_reported"]);
					
					$teachingResults	= $db->GetAll($query);
					
					if($teachingResults) {
						foreach($teachingResults as $teachingResult) {
							if($teachingResult["lec_hours"] > 0 || $teachingResult["lab_hours"] > 0 || $teachingResult["tut_hours"] > 0 || $teachingResult["sem_hours"] > 0 || $teachingResult["pbl_hours"]) {
								if(trim($teachingResult["course_number"]) == "" || $teachingResult["course_number"] == "?") {
									$teachingResult["course_number"] = "N/A";	
								} else {
									$teachingResult["course_number"] = strtoupper($teachingResult["course_number"]);
								}
								if($result["proxy_id"] != $prevProxyID) {
									$prevProxyID = $result["proxy_id"];
									echo "	<tr><td style=\"width: 5%\">".$result["lastname"]. ", " .$result["firstname"]."</td>\n";
									echo "	<td style=\"width: 3%\">".$teachingResult["course_number"]."</td>\n";
									echo "	<td style=\"width: 1%\">".$teachingResult["lec_hours"]."</td>\n";
									echo "	<td style=\"width: 1%\">".$teachingResult["lab_hours"]."</td>\n";
									echo "	<td style=\"width: 1%\">".$teachingResult["tut_hours"]."</td>\n";
									echo "	<td style=\"width: 1%\">".$teachingResult["sem_hours"]."</td>\n";
									echo "	<td style=\"width: 1%\">".$teachingResult["pbl_hours"]."</td></tr>";
								} else {
									echo "	<tr><td style=\"width: 5%\">&nbsp;</td>\n";	
									echo "	<td style=\"width: 3%\">".$teachingResult["course_number"]."</td>\n";
									echo "	<td style=\"width: 1%\">".$teachingResult["lec_hours"]."</td>\n";
									echo "	<td style=\"width: 1%\">".$teachingResult["lab_hours"]."</td>\n";
									echo "	<td style=\"width: 1%\">".$teachingResult["tut_hours"]."</td>\n";
									echo "	<td style=\"width: 1%\">".$teachingResult["sem_hours"]."</td>\n";
									echo "	<td style=\"width: 1%\">".$teachingResult["pbl_hours"]."</td></tr>";
								}
								
								$divisionTotals[$teachingResult["course_number"]]["lec_hours"] = $divisionTotals[$teachingResult["course_number"]]["lec_hours"] + $teachingResult["lec_hours"];
								$divisionTotals[$teachingResult["course_number"]]["lab_hours"] = $divisionTotals[$teachingResult["course_number"]]["lab_hours"] + $teachingResult["lab_hours"];
								$divisionTotals[$teachingResult["course_number"]]["tut_hours"] = $divisionTotals[$teachingResult["course_number"]]["tut_hours"] + $teachingResult["tut_hours"];
								$divisionTotals[$teachingResult["course_number"]]["sem_hours"] = $divisionTotals[$teachingResult["course_number"]]["sem_hours"] + $teachingResult["sem_hours"];
								$divisionTotals[$teachingResult["course_number"]]["pbl_hours"] = $divisionTotals[$teachingResult["course_number"]]["pbl_hours"] + $teachingResult["pbl_hours"];
								
								$totals[$teachingResult["course_number"]]["lec_hours"] = $totals[$teachingResult["course_number"]]["lec_hours"] + $teachingResult["lec_hours"];
								$totals[$teachingResult["course_number"]]["lab_hours"] = $totals[$teachingResult["course_number"]]["lab_hours"] + $teachingResult["lab_hours"];
								$totals[$teachingResult["course_number"]]["tut_hours"] = $totals[$teachingResult["course_number"]]["tut_hours"] + $teachingResult["tut_hours"];
								$totals[$teachingResult["course_number"]]["sem_hours"] = $totals[$teachingResult["course_number"]]["sem_hours"] + $teachingResult["sem_hours"];
								$totals[$teachingResult["course_number"]]["pbl_hours"] = $totals[$teachingResult["course_number"]]["pbl_hours"] + $teachingResult["pbl_hours"];
							}
						}
					}
				}
				if($multipleDivisions && isset($divisionTotals) && count($divisionTotals) > 0) {
					foreach($divisionTotals as $key=>$outputTotals) {
						if($firstTotalOutput == true) {
							$firstTotalOutput = false;
							echo "	<tr><td style=\"width: 5%; font-weight: bold\">Totals:</td>\n";
						} else {
							echo "	<tr><td style=\"width: 5%; font-weight: bold\">&nbsp;</td>\n";
						}
						echo "	<td style=\"width: 3%; font-weight: bold\">".$key."</td>\n";
						echo "	<td style=\"width: 1%; font-weight: bold\">".number_format($outputTotals["lec_hours"], 2)."</td>\n";
						echo "	<td style=\"width: 1%; font-weight: bold\">".number_format($outputTotals["lab_hours"], 2)."</td>\n";
						echo "	<td style=\"width: 1%; font-weight: bold\">".number_format($outputTotals["tut_hours"], 2)."</td>\n";
						echo "	<td style=\"width: 1%; font-weight: bold\">".number_format($outputTotals["sem_hours"], 2)."</td>\n";
						echo "	<td style=\"width: 1%; font-weight: bold\">".number_format($outputTotals["pbl_hours"], 2)."</td></tr>";
					}
				}
				?>
				</tbody></table>
				<table class="tableList" cellspacing="0" summary="Undergraduate Non-Medical Education Breakdown">
					<colgroup>
						<col style="width: 3%">
						<col style="width: 1%">
						<col style="width: 1%">
						<col style="width: 1%">
						<col style="width: 1%">
						<col style="width: 1%">
					</colgroup>
					<thead>
						<tr>
							<td style="width: 3%" >Course</td>
							<td style="width: 1%" >Lec</td>
							<td style="width: 1%" >Lab</td>
							<td style="width: 1%" >Tutorial</td>
							<td style="width: 1%" >Seminar</td>
							<td style="width: 1%" >PBL</td>
						</tr>
					</thead>
					<tbody>
					<?php
					echo "<br /><h3>Course Totals for ".$departmentString."</h3>";
					foreach($totals as $key=>$outputTotals) {
						echo "	<tr><td style=\"width: 3%; font-weight: bold\">".$key."</td>\n";
						echo "	<td style=\"width: 1%; font-weight: bold\">".number_format($outputTotals["lec_hours"], 2)."</td>\n";
						echo "	<td style=\"width: 1%; font-weight: bold\">".number_format($outputTotals["lab_hours"], 2)."</td>\n";
						echo "	<td style=\"width: 1%; font-weight: bold\">".number_format($outputTotals["tut_hours"], 2)."</td>\n";
						echo "	<td style=\"width: 1%; font-weight: bold\">".number_format($outputTotals["sem_hours"], 2)."</td>\n";
						echo "	<td style=\"width: 1%; font-weight: bold\">".number_format($outputTotals["pbl_hours"], 2)."</td></tr>";
						$lectureTotals = $lectureTotals + $outputTotals["lec_hours"];
						$labTotals = $labTotals + $outputTotals["lab_hours"];
						$tutTotals = $tutTotals + $outputTotals["tut_hours"];
						$semTotals = $semTotals + $outputTotals["sem_hours"];
						$pblTotals = $pblTotals + $outputTotals["pbl_hours"];
					}
					echo "	<tr><td style=\"width: 3%; font-weight: bold\">Totals:</td>\n";
					echo "	<td style=\"width: 1%; font-weight: bold\">".number_format($lectureTotals, 2)."</td>\n";
					echo "	<td style=\"width: 1%; font-weight: bold\">".number_format($labTotals, 2)."</td>\n";
					echo "	<td style=\"width: 1%; font-weight: bold\">".number_format($tutTotals, 2)."</td>\n";
					echo "	<td style=\"width: 1%; font-weight: bold\">".number_format($semTotals, 2)."</td>\n";
					echo "	<td style=\"width: 1%; font-weight: bold\">".number_format($pblTotals, 2)."</td></tr>";
					echo "</tbody></table>";
			} else {
				echo display_notice(array("There are no records in the system for the qualifiers you have selected."));
			}
		}
	} else {
		echo display_notice(array("There are no annual reports in the system yet."));
	}
}
?>