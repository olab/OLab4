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

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getActiveId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getActiveId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	// Attempt to get the departmentID from the department heads table as most of the time this file will
	// be accessed by department heads, however, there are also department reps that may access this file
	// therefore a fall back needs to be added to grab their department.
	$departmentID = is_department_head($ENTRADA_USER->getActiveId());
	
	if(!$departmentID || $departmentID == 0) {
		$departmentID = get_user_departments($ENTRADA_USER->getActiveId());
		
		$departmentID = $departmentID[0]["department_id"];
	}
	
	$departmentOutput = fetch_department_title($departmentID);
	
	$BREADCRUMB[]	= array("url" => "", "title" => "Publications for ".$departmentOutput);
    
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
    	
    	if(isset($_POST["type_id"]) && $_POST["type_id"] != "") {
    		if(in_array("*", $_POST["type_id"])) {
    			$types = getPublicationTypes();
    			foreach($types as $typeIDResult) {
    				$PROCESSED["type_id"][] = $typeIDResult["type_id"];
    			}
    		} else {
    			$PROCESSED["type_id"] = $_POST["type_id"];
    		}
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
				<td colspan="3" style="text-align: right; padding-top: 10px"><input type="submit" class="btn btn-primary" value="Create Report" /></td>
			</tr>
		</tbody>
		</table>
		</form>
	</div>
	<?php
	if($STEP == 2) {
		$dateWhere 				= "";
		$departmentMembers		= array();
		$publicationCountArray 	= array();
		$csvOutput				= array();
		
		$pdf_string = "journal_count";
		$txtfname 		= ANNUALREPORT_STORAGE."/".$pdf_string.".txt";
		
		echo "<form><input class=\"btn\" type=\"button\" value=\"Download Report\" onClick=\"window.location.href='".ENTRADA_URL."/file-annualreport.php?file=".$pdf_string.".txt'\"></form>";
		
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
		
		$dateWhere = " AND ((SUBSTRING(status_date, -4, 4) >= $startYear AND SUBSTRING(status_date, -4, 4) <= $endYear) OR status_date IS NULL OR status_date = 00)";
		
		$listOfDepartmentMembers = array();
		
		$departmentOutput = fetch_department_title($departmentID);
		
		if($isParentDepartment = fetch_department_children($departmentID)) {
			foreach($isParentDepartment as $userDepartment) {
				$thisDept = $userDepartment["department_id"];
				
				$departmentQuery .= " OR `dep_id`=".$db->qstr($thisDept);
			}
		}
		
		if(substr($departmentOutput, -1, 1) == "s") {
			$departmentOutput = $departmentOutput."'";
		} else {
			$departmentOutput = $departmentOutput."'s";
		}
		
		echo "<h1 style=\"page-break-before: avoid\">Department of ".$departmentOutput." Publications by Member</h1>";
		echo "<div class=\"content-small\" style=\"margin-bottom: 10px\">\n";
		echo "	<strong>Date Range:</strong> ".$startYear." <strong>to</strong> ".$endYear;
		echo "</div>";
		
		$usersInDepartmentQuery = "SELECT DISTINCT `user_id`, `firstname`, `lastname` FROM `".AUTH_DATABASE."`.`user_departments`, `".AUTH_DATABASE."`.`user_data`
		WHERE (`dep_id` = ".$db->qstr($departmentID).$departmentQuery.")
		AND `user_id` = `user_data`.`id`
		ORDER BY `lastname` ASC, `firstname` ASC";
		
		$departmentMembers = $db->GetAll($usersInDepartmentQuery);
		$lastMember = "";
		?>
		<table class="tableList" cellspacing="0" summary="System Report">
		<colgroup>
			<col class="general" />
			<col class="general" />
			<col class="general" />
			<col class="general" />
			<col class="general" />
		</colgroup>
		<thead>
			<tr>
				<td class="general borderl">Name</td>
				<td class="general">Articles Published</td>
				<td class="general">Articles In Press</td>
				<td class="general">Abstracts Published</td>
				<td class="general">Abstracts In Press</td>
			</tr>
		</thead>
		<tbody>
		<?php
		foreach($departmentMembers as $departmentMemberValue) {
			$departmentMember = $departmentMemberValue["user_id"];
			$departmentMemberName = $departmentMemberValue["firstname"] . " " . $departmentMemberValue["lastname"];
			
			$table = "`".DATABASE_NAME."`.`ar_peer_reviewed_papers`";
			
			$query = "SELECT *
			FROM ".$table.", `".AUTH_DATABASE."`.`user_data`
			WHERE `proxy_id` = ".$departmentMember."
			AND `proxy_id` = `".AUTH_DATABASE."`.`user_data`.`id`
			AND (`status` = \"In Press\" OR `status` = \"Published\")
			AND (`type_id` = '1' OR `type_id` = '4')".$dateWhere."
		  	ORDER BY `type_id` ASC";
			
			if($results = $db->GetAll($query)) {
				/*if($departmentMember != $lastMember) {
					echo "<h2>".$departmentMemberName."</h2>";
					$outputBreak = false;
				} else {
					$outputBreak = true;
				}*/
				
				//echo "<a name=\"".$departmentMemberName."\"></a>";
				/*if($outputBreak == true) {
					echo "<br>";
				}*/
				$data = "";
				foreach($results as $result)
	    		{
	    			$type = $result["type_id"];
	    			$status =  $result["status"];
	    			$source = $result["source"];
	    			
	    			if(isset($publicationCountArray[$departmentMember][$type][$status][$source])) {
	    				$publicationCountArray[$departmentMember][$type][$status][$source] = $publicationCountArray[$departmentMember][$type][$status][$source] + 1;
	    			} else {
	    				$publicationCountArray[$departmentMember][$type][$status][$source] = 1;
	    			}			
	    		}
	    		
	    		echo "<tr><td class=\"general\">".$departmentMemberName . "</td>";
	    		if(isset($publicationCountArray[$departmentMember][1]["Published"])) {
	    			echo "<td class=\"general\" style=\"white-space: normal\">";
	    			$ctr = 0;
	    			foreach($publicationCountArray[$departmentMember][1]["Published"] as $sourceKey => $value) {
	    				if($ctr == 0) {
	    					echo $sourceKey . "(" . $value . ")";
	    				} else {
	    					echo ", " . $sourceKey . "(" . $value . ")";
	    				}
	    				$ctr++;
	    			}
	    			echo "</td>";
	    		} else {
	    			$publicationCountArray[$departmentMember][1]["Published"]["N/A"] = 0;
	    			echo "<td class=\"general\" style=\"white-space: normal\">No Articles Publised</td>";
	    		}
	    		
	    		if(isset($publicationCountArray[$departmentMember][1]["In Press"])) {
	    			echo "<td class=\"general\" style=\"white-space: normal\">";
	    			$ctr = 0;
	    			foreach($publicationCountArray[$departmentMember][1]["In Press"] as $sourceKey => $value) {
	    				if($ctr == 0) {
	    					echo $sourceKey . "(" . $value . ")";
	    				} else {
	    					echo ", " . $sourceKey . "(" . $value . ")";
	    				}
	    				$ctr++;
	    			}
	    			echo "</td>";
	    		} else {
	    			$publicationCountArray[$departmentMember][1]["In Press"]["N/A"] = 0;
	    			echo "<td class=\"general\" style=\"white-space: normal\">No Articles In Press</td>";
	    		}
	    		
	    		if(isset($publicationCountArray[$departmentMember][4]["Published"])) {
	    			echo "<td class=\"general\" style=\"white-space: normal\">";
	    			$ctr = 0;
	    			foreach($publicationCountArray[$departmentMember][4]["Published"] as $sourceKey => $value) {
	    				if($ctr == 0) {
	    					echo $sourceKey . "(" . $value . ")";
	    				} else {
	    					echo ", " . $sourceKey . "(" . $value . ")";
	    				}
	    				$ctr++;
	    			}
	    			echo "</td>";
	    		} else {
	    			$publicationCountArray[$departmentMember][4]["Published"]["N/A"] = 0;
	    			echo "<td class=\"general\" style=\"white-space: normal\">No Abstracts Publised</td>";
	    		}
	    		
	    		if(isset($publicationCountArray[$departmentMember][4]["In Press"])) {
	    			echo "<td class=\"general\" style=\"white-space: normal\">";
	    			$ctr = 0;
	    			foreach($publicationCountArray[$departmentMember][4]["In Press"] as $sourceKey => $value) {
	    				if($ctr == 0) {
	    					echo $sourceKey . "(" . $value . ")";
	    				} else {
	    					echo ", " . $sourceKey . "(" . $value . ")";
	    				}
	    				$ctr++;
	    			}
	    			echo "</td>";
	    		} else {
	    			$publicationCountArray[$departmentMember][4]["In Press"]["N/A"] = 0;
	    			echo "<td class=\"general\" style=\"white-space: normal\">No Abstracts In Press</td>";
	    		}
	    		echo "</tr>";
				$lastMember = $departmentMember;
			}
		}
	}
	$fp = fopen($txtfname,'w');
	$data = "Name|Articles Published|Articles In Press|Abstracts Published|Abstracts In Press\r\n";
	fwrite($fp,$data);
	$inPressData = "";
	$publishedData = "";
	foreach($publicationCountArray as $key=>$value) {
		$name = get_account_data("wholename", $key);
		$data = $name;
    	foreach($value as $newKey=>$newValue) {    		
    		foreach($newValue as $newestKey=>$newestValue) {
    			if($newestKey == "In Press") {
    				foreach($newestValue as $newNewestKey=>$newNewestValue) {
    					if($inPressData == "") {
    						if($newNewestKey != "N/A") {
    							$inPressData = $newNewestKey."(".$newNewestValue.")";
    						} else {
    							$inPressData = "N/A";
    						}
    					} else {
    						if($newNewestKey != "N/A") {
    							$inPressData .= ", ".$newNewestKey."(".$newNewestValue.")";
    						} else {
    							$inPressData .= "|N/A";
    						}
    					}
    				}
    			} else {
    				foreach($newestValue as $newNewestKey=>$newNewestValue) {
    					if($publishedData == "") {
    						if($newNewestKey != "N/A") {
    							$publishedData = $newNewestKey."(".$newNewestValue.")";
    						} else {
    							$publishedData = "N/A";
    						}
    					} else {
    						if($newNewestKey != "N/A") {
    							$publishedData .= ", ".$newNewestKey."(".$newNewestValue.")";
    						} else {
    							$publishedData .= "|N/A";
    						}
    					}
    				}
    			}
    		}
    	}
    	$data .= "|".$publishedData."|".$inPressData."\r\n";
		fwrite($fp,$data);
		$data = "";
		$publishedData= "";
		$inPressData = "";
	}
	fclose($fp);
	?>
	</tbody>
	</table>
	<?php
}
?>