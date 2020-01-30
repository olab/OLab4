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
	
	$departmentOutput = fetch_department_title($departmentID);
	
	$BREADCRUMB[]	= array("url" => "", "title" => "Publications for ".$departmentOutput);
	
	function display($results, $db)
    {	
	    foreach($results as $result)
	    {	
	    	$formattedRec	= "";
	    	
	        if($formattedRec == "") {
	            if($result["author_list"] != "") {
	                $formattedRec = html_encode($result["author_list"]) . ", ";
	            }
	            
	            if($result["title"] != "") {
	                $formattedRec = $formattedRec . html_encode($result["title"])  . ", ";
	            }
	            
	            if(isset($result["status_date"]) && strlen($result["status_date"]) == 5) {
					$month 	= substr($result["status_date"], 0, 1);
					$year 	= substr($result["status_date"], 1, 4);
					$formattedRec = $formattedRec . $month . "-" . $year . ", ";
				} else if(isset($result["status_date"]) && strlen($result["status_date"]) == 6) {
					$month 	= substr($result["status_date"], 0, 2);
					$year 	= substr($result["status_date"], 2, 4);
	            	$formattedRec = $formattedRec . $month . "-" . $year . ", ";
	            } else {
	                $formattedRec = $formattedRec . " Pending Approval, ";
	            }
	            
	            if($result["source"] != "") {
	                $formattedRec = $formattedRec . html_encode($result["source"]) . ", ";
	            }
	            
	            if(isset($result["editor_list"])) {
	            	$formattedRec . "Ed. " . html_encode($result["editor_list"]) . ", ";
	            }
	            
	            if($result["volume"] != "" && $result["edition"] != "") {
	                $formattedRec = $formattedRec . "Vol. " . html_encode($result["volume"]) . "(". html_encode($result["edition"]) . "):";
	            } else if($result["volume"] != "" && $result["edition"] == "") {
	            	$formattedRec = $formattedRec . "Vol. " . html_encode($result["volume"]) . ", ";
	            } else if($result["volume"] == "" && $result["edition"] != "") {
	            	$formattedRec = $formattedRec . html_encode($result["edition"]) . ":";
	            }
	            
	            if($result["pages"] != "") {
	                $formattedRec = $formattedRec . html_encode($result["pages"]);
	            }
	            
	            // Check for existance of extra comma or colon at the end of the record
	            // if there is one remove it
	            $lengthOfRec = strlen($formattedRec) - 2;
	            $lastChar = substr($formattedRec, $lengthOfRec, 1);
	            if($lastChar == "," || $lastChar == ":") {
	                $formattedRec = substr($formattedRec, 0, $lengthOfRec);
	            }
	        }
	        
	        if(!isset($outputArray[$result["status"]])) {
	        	$outputArray[$result["status"]] = array();
	        }
	        
	        // Do not allow duplicates (i.e. multiple faculty report the same publication.
	        //if(in_array($formattedRec, $outputArray[$result["status"]]) === false) {
	        	$outputArray[$result["status"]][] = $formattedRec;
	        //}
	    }
	    
	    $keyHeader = "<b>(1) Published:</b><br>";
		echo $keyHeader;
		
	    if(count($outputArray['Published']) > 0) {
	    	for($u=0; $u<count($outputArray['Published']); $u++) {
	    		$ctr=$u + 1;
	    		$outputString = $outputArray['Published'][$u]."<br /><br />";
	    		echo "<strong>" . $ctr . " - </strong>" .$outputString;
	    	}
	    	$totalCount = $ctr;
	    } else {
	    	echo "No Records.<br>";
	    }
	    	    
	    $keyHeader = "<br><b>(2) In Press:</b><br>";
		echo $keyHeader;		
		
	    if(count($outputArray['In Press']) > 0) {
    		for($u=0; $u<count($outputArray['In Press']); $u++) {
	    		$ctr=$u + 1;
	    		$outputString = $outputArray['In Press'][$u]."<br /><br />";
	    		echo "<strong>" . $ctr . " - </strong>" .$outputString;
	    	}
	    } else {
	    	echo "No Records.<br>";
	    }
	    
	    $keyHeader = "<br><b>(3) Submitted:</b><br>";
		echo $keyHeader;
		
	    if(count($outputArray['Submitted']) > 0) {
    		for($u=0; $u<count($outputArray['Submitted']); $u++) {
	    		$ctr=$u + 1;
	    		$outputString = $outputArray['Submitted'][$u]."<br /><br />";
	    		echo "<strong>" . $ctr . " - </strong>" .$outputString;
	    	}
	    } else {
	    	echo "No Records.<br>";
	    }
	    unset($outputArray);
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
			<tr>
				<td></td>
				<td style="vertical-align: top;"><label for="type_id" class="form-required">Publication Types</label></td>
				<td><select multiple name="type_id[]" id="type_id" size = "10">
				<option value = "*"<?php echo (!is_array($PROCESSED["type_id"]) ? " selected=\"selected\"" : ""); ?>>All</option>
				<?php
					$types = getPublicationTypes();
					
					foreach($types as $type) {
						echo "<option value=\"".$type["type_id"]."\"".((isset($PROCESSED["type_id"]) && in_array($type["type_id"], $PROCESSED["type_id"])) ? " selected=\"selected\"" : "").">".$type["type_description"]."</option>\n";						
					}
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
		$publicationTypes		= $_POST["type_id"];
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
		
		foreach($departmentMembers as $departmentMemberValue) {
			$departmentMember = $departmentMemberValue["user_id"];
			$departmentMemberName = $departmentMemberValue["firstname"] . " " . $departmentMemberValue["lastname"];
			
			foreach($PROCESSED["type_id"] as $typeID) {
				switch ($typeID) {
					case 1:
					case 4: 
						$table = "`".DATABASE_NAME."`.`ar_peer_reviewed_papers`";
						break;
					case 2:
					case 5: 
						$table = "`".DATABASE_NAME."`.`ar_non_peer_reviewed_papers`";
						break;
					case 3:
					case 6: 
					case 7: 
					case 8: 
						$table = "`".DATABASE_NAME."`.`ar_book_chapter_mono`";
						break;
					case 10: 
					case 11: 
						$table = "`".DATABASE_NAME."`.`ar_poster_reports`";
						break;
				}
				
				$query = "SELECT *
				FROM ".$table.", `".AUTH_DATABASE."`.`user_data`
				WHERE `proxy_id` = ".$departmentMember."
				AND `proxy_id` = `".AUTH_DATABASE."`.`user_data`.`id`
				AND `type_id` = '$typeID'".$dateWhere;
				
				if($results = $db->GetAll($query)) {
					if($departmentMember != $lastMember) {
						echo "<h2>".$departmentMemberName."</h2>";
						$outputBreak = false;
					} else {
						$outputBreak = true;
					}
					$currentType = getPublicationTypesSpecificFromID($typeID);
					if(substr($currentType, -1, 1) != "s") {
						$currentType = $currentType . "s";
					}
					$publicationSidebar[] = $currentType;
					echo "<a name=\"".$departmentMemberName."\"></a>";
					if($outputBreak == true) {
						echo "<br>";
					}
					echo "<h3>".$currentType."</h3>\n";
					display($results, $db);
					$lastMember = $departmentMember;
				}
			}
		}
		
		/*if(isset($publicationSidebar)) {
			$sidebar_html  = "<ul class=\"menu\">\n";
			foreach($publicationSidebar as $result) {
				$sidebar_html .= "	<li class=\"link\"><a href=\"#".$result."\" title=\"".html_encode($result)."\">".html_encode($result)."</a></li>\n";
			}
			$sidebar_html .= "</ul>";
			new_sidebar_item("Publication List", $sidebar_html, "publication-list", "open");
		}*/
	}
}
?>