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
} elseif (!$ENTRADA_ACL->amIAllowed('mydepartment', 'read', 'DepartmentHead') && !$ENTRADA_ACL->amIAllowed('myowndepartment', 'read', 'DepartmentRep')) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	// Attempt to get the departmentID from the department heads table as most of the time this file will
	// be accessed by department heads, however, there are also department reps that may access this file
	// therefore a fall back needs to be added to grab their department.
	$departmentID = is_department_head($ENTRADA_USER->getActiveId());
	
	if(!$departmentID || $departmentID == 0) {
		$departmentID = get_user_departments($ENTRADA_USER->getActiveId());
		
		$departmentID = $departmentID[0]["department_id"];
	}
	
	$departmentOuput = fetch_department_title($departmentID);
	
	$BREADCRUMB[]	= array("url" => "", "title" => "Research Grants for ".$departmentOuput);
	
	function display($results, $db)
    {	
    	setlocale(LC_MONETARY, 'en_US');

    	$outputArray = array();
	    foreach($results as $result)
	    {
	    	$formattedRec	= "";
	    	
	        if($formattedRec == "") {
	            if($result["grant_title"] != "") {
	                $formattedRec = html_encode($result["grant_title"]) . ", ";
	            }
	            
	            if($result["funding_status"] == "funded") {
		            if($result["amount_received"] != "") {
		                $formattedRec = $formattedRec . money_format('%(#10n', $result["amount_received"]) . ", ";
		            }
	            } else if($result["funding_status"] == "submitted") {
	            	 if($result["amount_received"] != "") {
		                $formattedRec = $formattedRec . money_format('%(#10n', $result["amount_received"]) . ", ";
		            }
	            }
	           
	            if($result["type"] != "") {
	                $formattedRec = $formattedRec . html_encode($result["type"]) . ", ";
	            }
	            
	            if(isset($result["agency"]) && $result["agency"] != "") {
	            	$formattedRec = $formattedRec . html_encode($result["agency"]) . ", ";
	            }
	            
	            if(isset($result["start_year"])) {
	            	$formattedRec = $formattedRec . html_encode($result['start_month']) ."-".html_encode($result['start_year']) . " / "
					.((html_encode($result['end_month']) == 0 ? "N/A" : html_encode($result['end_month']) ."-".html_encode($result['end_year']))).", ";
	            }
	            
	            if($result["principal_investigator"] != "") {
	                $formattedRec = $formattedRec . html_encode($result["principal_investigator"]);
	            }
	            
	            if($result["co_investigator_list"] != "") {
	                $formattedRec = $formattedRec . html_encode($result["co_investigator_list"]);
	            }
	            
	            // Check for existance of extra comma or colon at the end of the record
	            // if there is one remove it
	            $lengthOfRec = strlen($formattedRec) - 2;
	            $lastChar = substr($formattedRec, $lengthOfRec, 1);
	            if($lastChar == "," || $lastChar == ":") {
	                $formattedRec = substr($formattedRec, 0, $lengthOfRec);
	            }
	        }
	        
	        // Do not allow duplicates (i.e. multiple faculty report the same publication.
	        if(in_array($formattedRec, $outputArray) === false) {
	        	$outputArray[] = $formattedRec;
	        }
	    }
	    
	    if(count($outputArray) > 0) {
	    	for($u=0; $u<count($outputArray); $u++) {
	    		$ctr=$u + 1;
	    		$outputString = $outputArray[$u]."<br /><br />";
	    		echo $outputString;
	    	}
	    } else {
	    	echo "No Grants for the specified query.<br>";
	    }
    }
    
    if($STEP == 2) {
    	if(isset($_POST["start_year"]) && $_POST["start_year"] != "") {
    		$PROCESSED["start_year"] = (int)$_POST["start_year"];
    	}
    	
    	if(isset($_POST["end_year"]) && $_POST["end_year"] != "") {
    		$PROCESSED["end_year"] = (int)$_POST["end_year"];
    	}
    	
    	if(isset($_POST["end_year"]) && $_POST["end_year"] != "") {
    		$PROCESSED["end_year"] = (int)$_POST["end_year"];
    	}
    	
    	if(isset($_POST["funding_status"]) && $_POST["funding_status"] != "") {
			$PROCESSED["funding_status"] = $_POST["funding_status"];
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
		<form action="<?php echo ENTRADA_URL; ?>/annualreport/reports?section=<?php echo $SECTION; ?>&step=2" method="post">
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
				<td></td>
				<td><label for="start_year" class="form-required">Start Year</label></td>
				<td><select name="start_year" id="start_year" style="vertical-align: middle">
				<?php
					for($i=$AR_PAST_YEARS; $i<=$AR_FUTURE_YEARS; $i++)
					{
						if(isset($PROCESSED["start_year"]) && $PROCESSED["start_year"] != '')
						{
							$defaultStartYear = $PROCESSED["start_year"];
						}
						else 
						{
							$defaultStartYear = $AR_PAST_YEARS;
						}
						echo "<option value=\"".$i."\"".(($defaultStartYear == $i) ? " selected=\"selected\"" : "").">".$i."</option>\n";
					}
					echo "</select>";
				?>
				</td>
			</tr>
			<tr>
				<td></td>
				<td><label for="end_year" class="form-required">End Year</label></td>
				<td><select name="end_year" id="end_year" style="vertical-align: middle">
				<?php
					for($i=$AR_PAST_YEARS; $i<=$AR_FUTURE_YEARS; $i++)
					{
						if(isset($PROCESSED["end_year"]) && $PROCESSED["end_year"] != '')
						{
							$defaultEndYear = $PROCESSED["end_year"];
						}
						else 
						{
							$defaultEndYear = $AR_FUTURE_YEARS;
						}
						echo "<option value=\"".$i."\"".(($defaultEndYear == $i) ? " selected=\"selected\"" : "").">".$i."</option>\n";
					}
					echo "</select>";
				?>
				</td>
			</tr>
			<tr>
				<td></td>
				<td style="vertical-align: top;"><label for="funding_status" class="form-required">Funding Status</label></td>
				<td><select multiple name="funding_status[]" id="funding_status" size = "4">
				<option value = "*"<?php echo ((!isset($PROCESSED["funding_status"]) || in_array("*", $PROCESSED["funding_status"])) ? " selected=\"selected\"" : ""); ?>>All</option>
				<?php
					echo "<option value=\"funded\"".((isset($PROCESSED["funding_status"]) && in_array("funded", $PROCESSED["funding_status"])) ? " selected=\"selected\"" : "").">Funded</option>\n";						
					echo "<option value=\"submitted\"".((isset($PROCESSED["funding_status"]) && in_array("submitted", $PROCESSED["funding_status"])) ? " selected=\"selected\"" : "").">Submitted</option>\n";						
					echo "<option value=\"unfunded\"".((isset($PROCESSED["funding_status"]) && in_array("unfunded", $PROCESSED["funding_status"])) ? " selected=\"selected\"" : "").">Unfunded</option>\n";						
				?>
				</select>
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
	if($STEP == 2) {
		$fundingStatus		= $_POST["funding_status"];
		$dateWhere 				= "";
		$departmentMembers		= array();
		
		if(isset($_POST['start_year']) && $_POST['start_year'] != "") {
			$startYear = (int)$_POST['start_year'];
		} else {
			$startYear = $AR_PAST_YEARS;
		}
		
		if(isset($_POST['end_year']) && $_POST['end_year'] != "") {
			$endYear = (int)$_POST['end_year'];
		} else {
			$endYear = $AR_FUTURE_YEARS;
		}
		
		$dateWhere = " AND ((`start_year` >= $startYear AND `end_year` <= $endYear) OR `start_year` IS NULL OR `start_year` = 0)";
		
		$listOfDepartmentMembers = array();
		
		$departmentOuput = fetch_department_title($departmentID);
		
		if($isParentDepartment = fetch_department_children($departmentID)) {
			foreach($isParentDepartment as $userDepartment) {
				$thisDept = $userDepartment["department_id"];
				
				$departmentQuery .= " OR `dep_id`=".$db->qstr($thisDept);
			}
		}
		$usersInDepartmentQuery = "SELECT DISTINCT `user_id` FROM `".AUTH_DATABASE."`.`user_departments`
		WHERE `dep_id` = ".$db->qstr($departmentID).$departmentQuery;
		
		$departmentMembers = $db->GetAll($usersInDepartmentQuery);
			
		foreach($departmentMembers as $departmentMemberValue) {
			$listOfDepartmentMembers[] = $departmentMemberValue["user_id"];
		}
		
		$listOfDepartmentMembers = implode(",", $listOfDepartmentMembers);
		
		if(substr($departmentOuput, -1, 1) == "s") {
			$departmentOuput = $departmentOuput."'";
		} else {
			$departmentOuput = $departmentOuput."'s";
		}
		
		echo "<h1 style=\"page-break-before: avoid\">Department of ".$departmentOuput." Rearch Grants</h1>";
		echo "<div class=\"content-small\" style=\"margin-bottom: 10px\">\n";
		echo "	<strong>Date Range:</strong> ".$startYear." <strong>to</strong> ".$endYear;
		echo "</div>";
	
		if(is_array($fundingStatus) && in_array("*", $fundingStatus)) {
			$fundingStatus = array("funded", "submitted", "unfunded");
		} 
		
		foreach($fundingStatus as $fundingStatusSpecific) {
			$query = "SELECT *
			FROM `ar_research` 
			WHERE `proxy_id` IN(".$listOfDepartmentMembers.")
			AND `funding_status` = '$fundingStatusSpecific'".$dateWhere;
			
			$currentStatus = ucfirst($fundingStatusSpecific);
			$researchSidebar[] = $currentStatus;
			echo "<a name=\"".$currentStatus."\"></a>";
			echo "<h2>".$currentStatus."</h2>\n";
			
			if($results = $db->GetAll($query)) {
				display($results, $db);
			} else {
				echo "No Grants for the specified query.<br>";
			}
		}
		
		if(isset($researchSidebar)) {
			$sidebar_html  = "<ul class=\"menu\">\n";
			foreach($researchSidebar as $result) {
				$sidebar_html .= "	<li class=\"link\"><a href=\"#".$result."\" title=\"".html_encode($result)."\">".html_encode($result)."</a></li>\n";
			}
			$sidebar_html .= "</ul>";
			new_sidebar_item("Research List", $sidebar_html, "research-list", "open");
		}
	}
}
?>