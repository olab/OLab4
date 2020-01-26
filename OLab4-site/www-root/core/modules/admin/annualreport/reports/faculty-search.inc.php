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
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
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
	$BREADCRUMB[]	= array("url" => "", "title" => "Faculty Search" );
	
	$years = getMinMaxARYears();
	if(isset($years["start_year"]) && $years["start_year"] != "") {
		$PROCESSED["year_reported"] = $_POST['year_reported'];
		
		if ($STEP == 2) {
			if ((isset($_POST["q"])) && ($query = clean_input($_POST["q"], array("trim", "notags")))) {
				$search_query = $query;
				
				$query	= "	SELECT `firstname`, `lastname`, `year_reported`, `report_completed`, `".AUTH_DATABASE."`.`user_data`.`clinical`, `department_title`, `ar_profile`.`profile_id`, `ar_profile`.`proxy_id`
							FROM `".DATABASE_NAME."`.`ar_profile`, `".AUTH_DATABASE."`.`user_data`, `".AUTH_DATABASE."`.`user_departments`, `".AUTH_DATABASE."`.`departments` 
							WHERE `year_reported` = ".$db->qstr($PROCESSED["year_reported"])."
							AND (`".AUTH_DATABASE."`.`user_data`.`number` LIKE ".$db->qstr("%%".str_replace("%", "", $search_query)."%%")."
							OR `".AUTH_DATABASE."`.`user_data`.`username` LIKE ".$db->qstr("%%".str_replace("%", "", $search_query)."%%")."
							OR `".AUTH_DATABASE."`.`user_data`.`email` LIKE ".$db->qstr("%%".str_replace("%", "", $search_query)."%%")."
							OR `".AUTH_DATABASE."`.`user_data`.`firstname` LIKE ".$db->qstr("%%".str_replace("%", "", $search_query)."%%")."
							OR `".AUTH_DATABASE."`.`user_data`.`lastname` LIKE ".$db->qstr("%%".str_replace("%", "", $search_query)."%%").")
							AND `".DATABASE_NAME."`.`ar_profile`.`proxy_id` = `".AUTH_DATABASE."`.`user_data`.`id` 
							AND `".AUTH_DATABASE."`.`user_data`.`id` = `".AUTH_DATABASE."`.`user_departments`.`user_id`
							AND `dep_id` = `department_id`
							ORDER BY `lastname` ASC";
			}
		}
		
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
				<col style="width: 3%" />
				<col style="width: 20%" />
				<col style="width: 77%" />
			</colgroup>
			<tbody>
				<tr>
					<td colspan="3"><h2>Report Options</h2></td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td style="vertical-align: top"><label for="q" class="form-required">Search Criteria:</label></td>
					<td>
						<input type="text" id="q" name="q" value="<?php echo html_encode($search_query); ?>" style="width: 350px" />
						<div class="content-small" style="margin-top: 10px">
							<strong>Note:</strong> You can search for last name, username, e-mail address or staff / student number.
						</div>
					</td>
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
					<td colspan="3" style="text-align: right; padding-top: 10px"><input type="submit" class="btn btn-primary" value="Create Report" /></td>
				</tr>
			</tbody>
			</table>
			</form>
		</div>
		<?php
		if ($STEP == 2) {
			echo "<h2>Annual Report Faculty Search</h2>";
			echo "<div class=\"content-small\" style=\"margin-bottom: 10px\">\n";
			echo "	<strong>Reporting Period:</strong> ".$PROCESSED["year_reported"]." <strong>";
			echo "</div>\n";
			
			$results	= $db->GetAll($query);
			
			if ($results) {
				?>
				<table class="tableList" cellspacing="0" summary="Annual Reporting Faculty Search Results">
				<colgroup>
					<col class="general" />
					<col class="general" />
					<col class="title" />
					<col class="completed" style="width: 75px; text-align: left;"/>
					<col class="completed" style="width: 75px; text-align: left;"/>
					<col class="modified" />
				</colgroup>
				<thead>
					<tr>
						<td class="general" style="border-left: 1px #666 solid">Firstname</td>
						<td class="general" >Lastname</td>
						<td class="title">Department</td>
						<td class="completed" style="width: 75px; text-align: left;">Clinical</td>
						<td class="completed" style="width: 75px; text-align: left;">Status</td>
						<td class="modified"></td>
					</tr>
				</thead>
				<tbody>
				<?php
				$count = 0;
				foreach ($results as $result) {
					$count++;
					
					if($result["report_completed"] == "yes") {
						$status = "Completed";
						$cell = "<td class=\"modified\"><a href=\"javascript: void(0)\" onclick=\"window.open('".ENTRADA_URL . "/annualreport/generate?section=generate-annual-report&amp;rid=".$result["profile_id"]."&amp;proxy_id=".$result["proxy_id"]."&amp;clinical=".$result["clinical"]."');\" style=\"cursor: pointer; cursor: hand\" text-decoration: none><img src=\"".ENTRADA_RELATIVE."/css/jquery/images/report_go.gif\" style=\"border: none\"/></a></td>";
					} else if($result["report_completed"] == "no") {
						$status = "Started";
						$cell = "<td class=\"modified\"><a href=\"javascript: void(0)\" onclick=\"window.open('".ENTRADA_URL . "/annualreport/generate?section=generate-annual-report&amp;rid=".$result["profile_id"]."&amp;proxy_id=".$result["proxy_id"]."&amp;clinical=".$result["clinical"]."');\" style=\"cursor: pointer; cursor: hand\" text-decoration: none><img src=\"".ENTRADA_RELATIVE."/css/jquery/images/report_go.gif\" style=\"border: none\"/></a></td>";
					} else {
						$status = "Not Started";
						$cell = "<td class=\"modified\">&nbsp;</td>";
					}
					echo "<tr>\n";
					echo "	<td class=\"general\">".$result["firstname"]."</td>\n";
					echo "	<td class=\"general\">".$result["lastname"]."</td>\n";
					echo "	<td class=\"title\" style=\"white-space: normal\">".$result["department_title"]."</td>\n";
					echo "	<td class=\"completed\" style=\"width: 75px; text-align: left;\">".($result["clinical"] == 1 ? "Yes" : "No")."</td>\n";
					echo "	<td class=\"completed\" style=\"width: 75px; text-align: left;\">".$status."</td>\n";
					echo $cell;
					echo "</tr>\n";
				}
				?>
				</tbody>
				</table>
				<?php	
				echo "<h2>Total: ".$count."</h1>";			
			} else {
				echo display_notice(array("There are no annual reports in the system for the period you have selected."));	
			}
		}
	} else {
		echo display_notice(array("There are no annual reports in the system yet."));
	}
}
?>