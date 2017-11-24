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
} else if((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL.((isset($_SERVER["REQUEST_URI"])) ? "?url=".rawurlencode(clean_input($_SERVER["REQUEST_URI"], array("nows", "url"))) : ""));
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed('annualreport', 'read')) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 5000)";

	$ERROR++;
	$ERRORSTR[]	= "You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	$BREADCRUMB[]	= array("url" => "", "title" => "Copy Forward" );
	
	$arrayOfTables = array( 
						"ar_undergraduate_nonmedical_teaching|Non-Captured Undergraduate Teaching",
						"ar_graduate_teaching|Non-Captured Graduate Teaching",
						"ar_undergraduate_supervision|Undergraduate Supervision",
						"ar_graduate_supervision|Graduate Supervision",
						"ar_memberships|Membership on Graduate Examining and Supervisory Committees (Excluding Supervision)",
						"ar_continuing_education|Continuing Education",
						"ar_innovation|Innovation in Education",
						"ar_other|Other Education",
						"ar_ward_supervision|Ward Supervision",
						"ar_clinics|Clinics",
						"ar_consults|In-Hospital Consultations",
						"ar_on_call|On-Call Responsibility",
						"ar_procedures|Procedures",
						"ar_other_activity|Other Professional Activity",
						"ar_research|Projects / Grants / Contracts",
						"ar_conference_papers|Invited Lectures / Conference Papers",
						"ar_scholarly_activity|Other Scholarly Activity",
						"ar_patent_activity|Patents",
						"ar_internal_contributions|Service Contributions on Behalf of Queen's University",
						"ar_external_contributions|External Contributions",
						"ar_self_education|Self Education",
						"ar_prizes|Prizes",
						"ar_profile|Activity Profile");
						
	$arrayOfClinicalTables = array( 
						"ar_undergraduate_nonmedical_teaching|Non-Captured Undergraduate Teaching",
						"ar_graduate_teaching|Non-Captured Graduate Teaching",
						"ar_undergraduate_supervision|Undergraduate Supervision",
						"ar_graduate_supervision|Graduate Supervision",
						"ar_memberships|Membership on Graduate Examining and Supervisory Committees (Excluding Supervision)",
						"ar_clinical_education|Education of Clinical Trainees Including Clinical Clerks",
						"ar_continuing_education|Continuing Education",
						"ar_innovation|Innovation in Education",
						"ar_other|Other Education",
						"ar_clinical_activity|Clinical Activity",
						"ar_ward_supervision|Ward Supervision",
						"ar_clinics|Clinics",
						"ar_consults|In-Hospital Consultations",
						"ar_on_call|On-Call Responsibility",
						"ar_procedures|Procedures",
						"ar_other_activity|Other Professional Activity",
						"ar_clinical_innovation|Innovation in Clinical Activity",
						"ar_research|Projects / Grants / Contracts",
						"ar_conference_papers|Invited Lectures / Conference Papers",
						"ar_scholarly_activity|Other Scholarly Activity",
						"ar_patent_activity|Patents",
						"ar_internal_contributions|Service Contributions on Behalf of Queen's University",
						"ar_external_contributions|External Contributions",
						"ar_self_education|Self Education",
						"ar_prizes|Prizes",
						"ar_profile|Activity Profile");
	
	if($ENTRADA_USER->getClinical()) {
		$tablesToUse = $arrayOfClinicalTables;
	} else {
		$tablesToUse = $arrayOfTables;
	}
	
    if($STEP == 2) {
    	if(isset($_POST["copy_from"]) && $copy_from = clean_input($_POST["copy_from"], array("int"))) {
    		$PROCESSED["copy_from"] = $copy_from;
    	} else {
    		$ERROR++;
    		$ERRORSTR[] = "You must select a year to <strong>Copy From</strong>.";
    	}
    	
    	if(isset($_POST["copy_to"]) && ($copy_to = clean_input($_POST["copy_to"], array("int")))) {
    		$PROCESSED["copy_to"] = $copy_to;    		
    	} else {
    		$ERROR++;
    		$ERRORSTR[] = "You must select a year to <strong>Copy To</strong>.";
    	}
    	
    	if($_POST["copy_from"] >= $_POST["copy_to"]) {
    		$PROCESSED["copy_to"] = $_POST["copy_to"]; 
    		
    		$ERROR++;
    		$ERRORSTR[] = "<strong>Copy To</strong> must be greater than <strong>Copy From</strong>.";
    	}
    	
    	if(!isset($_POST["copy"]) || !is_array($_POST["copy"])) {
			$ERROR++;
			$ERRORSTR[] = "You must select at least one subsection to <strong>Copy</strong>.";
		} else {
			$PROCESSED["copy"] = $_POST["copy"];
		}
		if(!$ERROR) {
			$tablesCopied = array();
			$tablesNotCopied = array();
			$tablesErrored = array();
			
			foreach($PROCESSED["copy"] as $copyData) {
				$copy = explode("|", $copyData);
				$table = $copy[0];
				$title = $copy[1];
				
				$getRecordsToCopy = "	SELECT * 
										FROM `".$table."`
										WHERE `proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId())."
										AND `year_reported` = ".$db->qstr($PROCESSED["copy_from"]);
				
				// If they are attempting to copy their activity profile ensure they do not already have record for the year they are copying to
				if($table == "ar_profile") {
					$doubleCheckProfile = "	SELECT * 
										FROM `".$table."`
										WHERE `proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId())."
										AND `year_reported` = ".$db->qstr($PROCESSED["copy_to"]);
					
					if($checkResult = $db->GetRow($doubleCheckProfile)) {
						$skip = true;
						$tablesErrored[] = $title;
					} else {
						$skip = false;
					}
				} else {
					$skip = false;
				}
				
				if($results = $db->GetAll($getRecordsToCopy)) {
					if(!$skip) {
						foreach($results as $result) {
							$result["year_reported"] = $PROCESSED["copy_to"];
							$result["report_completed"] = "no";
							$result["updated_date"]	= time();
							$result["updated_by"] = $ENTRADA_USER->getID();
							$result["proxy_id"]	= $ENTRADA_USER->getActiveId();
							
							// Remove the ID from the array so that the insert can happen as if it were a new record.
							array_shift($result);
							
							if($db->AutoExecute($table, $result, "INSERT")) {
								if(!in_array($title, $tablesCopied)) {
									$tablesCopied[] = $title;
								}
							} else {
								$ERROR++;
								$ERRORSTR[] = "There was a problem Copying Forward. The MEdTech Unit was informed of this error; please try again later.";
				
								application_log("error", "There was an error Copying Forward. Database said: ".$db->ErrorMsg());
							}
						}
					}
				} else {
					$tablesNotCopied[] = $title;
				}
			}
			
			echo "<div id=\"display-notice-box\" class=\"display-generic\">\n";
		    echo "<strong>Here is the audit report from your Copy Forward Request: <br /><br /></strong>";
		    
			$url 	= ENTRADA_URL."/annualreport/tools";

			if(isset($tablesCopied) && count($tablesCopied) > 0) {
				echo "The following sections were copied from " . $PROCESSED["copy_from"] . " to " . $PROCESSED["copy_to"] . ": <br /><br />";
			
				$tableString = "<tt>";
				
				foreach($tablesCopied as $table) {
					$tableString = $tableString . $table ."<br />";
				}
				$tableString = 	$tableString."</tt><br />";
				echo $tableString;	
			}
			
			if(isset($tablesNotCopied) && count($tablesNotCopied) > 0) {
				echo "The following sections were <strong>NOT</strong> copied because there was no data in them for " . $PROCESSED["copy_from"] . ": <br /><br />";
				
				$tableString = "<tt>";
				
				foreach($tablesNotCopied as $table) {
					$tableString = $tableString . $table ."<br />";
				}
				$tableString = 	$tableString."</tt><br />";
				echo $tableString;	
			}
			
			if(isset($tablesErrored) && count($tablesErrored) > 0) {
				echo "The following sections were <strong>NOT</strong> copied because you already have a record for " . $PROCESSED["copy_to"] . " in that section and you are only allowed to have one record per year: <br /><br />";
				
				$tableString = "<tt>";
				
				foreach($tablesErrored as $table) {
					$tableString = $tableString . $table ."<br />";
				}
				$tableString = 	$tableString."</tt><br />";
				echo $tableString;	
			}
			
			echo "Once you are finished reviewing this audit report click <a href=\"".$url."\">here</a> to return to the Tools page";
			echo "</div>";
			application_log("success", "User ID: ".$ENTRADA_USER->getID()." - Copied forward from ". $PROCESSED["copy_from"] ." to ". $PROCESSED["copy_to"] .".");
		} else {
			if($ERROR) {
				echo display_error();
			}
			$STEP = 1;
		}
    }
	?>

	<SCRIPT LANGUAGE="JavaScript">
		function selectAll() {
	    	jQuery("input[type='checkbox']:not([disabled='disabled'])").attr('checked', true);
		}
		
		function deselectAll() {
	    	jQuery("input[type='checkbox']:not([disabled='disabled'])").attr('checked', false);
		}
	</script>
	<?php
	switch($STEP) {
		case 2 :
			if($SUCCESS) {
				echo display_success();
			}
			if($NOTICE) {
				echo display_notice();
			}
			if($ERROR) {
				echo display_error();
			}
		break;
		case 1 :
		default :
			echo "<div id=\"display-error-box\" class=\"display-generic\">\n";
		    echo "<strong>Note:</strong> Copying forward should only be used once a year to copy records from the previous year. 
		    If records are copied by accident you can simply go back and delete them. If records exist in the system for the year you are copying forward to these records
		    will remain untouched.";
		    echo "</div>";
			?>
		<div class="no-printing">
			<form name="myForm" action="<?php echo ENTRADA_URL; ?>/annualreport/tools?section=<?php echo $SECTION; ?>&step=2" method="post">
			<input type="hidden" name="update" value="1" />
			<table style="width: 100%" cellspacing="0" cellpadding="2" border="0">
			<colgroup>
				<col style="width: 3%" />
				<col style="width: 20%" />
				<col style="width: 77%" />
			</colgroup>
			<tbody>
				<tr>
					<td colspan="3"><h2>Copy Forward Options</h2></td>
				</tr>
				<tr>
					<td></td>
					<td><label for="copy_from" class="form-required">Copy From:</label></td>
					<td><select name="copy_from" id="copy_from" style="vertical-align: middle">
					<?php
						$getProfileDatesQuery = "	SELECT DISTINCT `year_reported` 
													FROM `ar_profile` 
													WHERE `proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId())."
													ORDER BY `year_reported` DESC";
						if($resutls = $db->GetAll($getProfileDatesQuery)) {
							foreach($resutls as $result) {
								if(isset($PROCESSED["copy_from"]) && $PROCESSED["copy_from"] != '') {
									$defaultFromYear = $PROCESSED["copy_from"];
								}
								echo "<option value=\"".$result["year_reported"]."\"".(($defaultFromYear == $result["year_reported"]) ? " selected=\"selected\"" : "").">".$result["year_reported"]."</option>\n";
							}
						}
						echo "</select>";
					?>
					</td>
				</tr>
				<tr>
					<td></td>
					<td><label for="copy_to" class="form-required">Copy To:</label></td>
					<td><select name="copy_to" id="copy_to" style="vertical-align: middle">
					<?php
						$copy_to = array();
						// If it is between July and December then allow them to copy forward to THIS year
						// otherwise they need to copy forward to NEXT Year
						if((int)date("m") > 7 && (int)date("m") <= 12) {	
							$copy_to[] = date("Y");
							$copy_to[] = date("Y") + 1;
						} else { 
							$copy_to[] = date("Y", strtotime("-1 year"));
							$copy_to[] = date("Y");
						}
						
						foreach($copy_to as $i) {
							if(isset($PROCESSED["copy_to"]) && $PROCESSED["copy_to"] != "")
							{
								$defaultToYear = $PROCESSED["copy_to"];
							}
							else 
							{
								$defaultToYear = $AR_FUTURE_YEARS;
							}
							echo "<option value=\"".$i."\"".(($defaultToYear == $i) ? " selected=\"selected\"" : "").">".$i."</option>\n";
						}
						echo "</select>";
					?>
					</td>
				</tr>
				<tr>
					<td colspan="3">&nbsp;</td>
				</tr>
				<tr id="test">
					<td></td>
					<td valign=top><label class="form-required">Sections to Copy:</label></td>
					<td>
						<table class="tableList" cellspacing="0" style="white-space:normal;" cellpadding="1" border="0">
							<colgroup>
								<col style="width: 100%" />
							</colgroup>
							<tbody>
								<?php
									echo "<tr class=\"details\">
										<td>
											<input class=\"btn\" type=\"button\" name=\"checkAll\" value=\"Check All\" onclick=\"selectAll();\"/>
											<input class=\"btn\" type=\"button\" name=\"unCheckAll\" value=\"Uncheck All\" onclick=\"deselectAll();\"/>
										</td>
									</tr>
									<tr>
										<td colspan=\"3\">&nbsp;</td>
									</tr>";
									foreach($tablesToUse as $outputTable) {
										$values = explode("|", $outputTable);
										echo "<tr class=\"details\">
											<td>
												<label><input type=\"checkbox\" name=\"copy[]\" id=\"copy\" value=\"".$outputTable."\" selected=\"selected\" \>
												".$values[1]."
												</label>
											</td>
										</tr>";
									}
								?>
							</tbody>
						</table>
					</td>
				</tr>
				<tr>
					<td colspan="3" style="text-align: right; padding-top: 10px"><input type="submit" class="btn btn-primary" value="Copy Forward" /></td>
				</tr>
			</tbody>
			</table>
			</form>
		</div>
		<?php
		break;
	}
}
?>