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
} elseif (!$ENTRADA_ACL->amIAllowed('annualreport', 'update')) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
// Meta information for this page.
$PAGE_META["title"]		= "Annual Report Generation";
$PAGE_META["category"]	= "Generate a PDF version of your Annual Report";
$PAGE_META["keywords"]	= "";

$BREADCRUMB[]	= array("url" => ENTRADA_URL."/annualreport/generate?section=generate-annual-report", "title" => "Annual Report Generation");
define("QUFA_DISCLAIMER",		"The personal information collected on this form is collected under the legal authority of the Royal Charter of 1841, as amended.  The information collected is used for academic purposes.  Any questions may be directed to the Executive Vice-Dean for the Faculty of Health Sciences.  In accordance with Article 28.2.2, this standardized form for evaluation of members has been approved by the parties, Queen's University and QUFA effective August 29, 2011.");						// Static QUFA Messages

// This grid should be expanded upon redirecting back to the prizes index.
$_SESSION["reports_expand_grid"] = "reports_grid";

ob_start("on_checkout");

if((isset($_GET["rid"])) && (clean_input($_GET["rid"], array("trim", "int")))) {
	$RECORD_ID = clean_input($_GET["rid"], array("trim", "int"));
}
else 
{
	echo "Sorry you can't do this without providing a year.";
	exit;
}

if((isset($_GET["proxy_id"])) && (clean_input($_GET["proxy_id"], array("trim", "int")))) {
	$proxy_id = clean_input($_GET["proxy_id"], array("trim", "int"));
}
else 
{
	echo "Sorry you do not have permissions to generate this report.";
	exit;
}

$query = "SELECT `year_reported` FROM `ar_profile` WHERE `proxy_id` = ".$db->qstr($proxy_id)." AND `profile_id` = ".$db->qstr($RECORD_ID);

if($result = $db->GetRow($query)) {
	$REPORT_YEAR = $result["year_reported"];
} else {
	echo "Sorry you do not have permissions to generate this report.";
	exit;
}

if(isset($_GET["clinical"])) {
	$clinical_value = clean_input($_GET["clinical"], array("trim"));
	if($clinical_value == "NO" || $clinical_value == '0') {
		$clinical_value = false;
	} else {
		$clinical_value = true;
	}
}

function queryIt($proxy_id, $table, $startDate, $endDate, $db, $roleTable)
{
	$query = "SELECT * FROM `".$table."`, `".$roleTable."`, `ar_lu_publication_type`
    WHERE `".$table."`.`proxy_id` = '$proxy_id'
    AND `".$table."`.`type_id` = `ar_lu_publication_type`.`type_id`
    AND `".$table."`.`role_id` = `".$roleTable."`.`role_id`
    AND `".$table."`.`year_reported` BETWEEN '$startDate' AND '$endDate'
    ORDER BY `status` ASC";
	
    $results = $db->GetAll($query);
    
    return $results;
}

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
				if($month == 0) {
					$month = 1;
				}
			} else if(isset($result["status_date"]) && strlen($result["status_date"]) == 6) {
				$month 	= substr($result["status_date"], 0, 2);
				$year 	= substr($result["status_date"], 2, 4);
            	if($month == 0) {
            		$month = 1;
				} 
			}
			if($result["category"] == "E-Pub") {
					$formattedRec = $formattedRec . $month . "-" . $year . " (e-pub), ";
			} else {
				$formattedRec = $formattedRec . $month . "-" . $year . ", ";
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
            
            $formattedRec .=  " <b> - " . $result["role_description"] . " (" . $result["type_description"] . ")</b>";
        }
        
        $outputArray[$result["status"]][] = $formattedRec;
    }
    
    $keyHeader = "<b>(1) Published:</b><br>";
	echo $keyHeader;
	
    if(count($outputArray['Published']) > 0) {
    	for($u=0; $u<count($outputArray['Published']); $u++) {
    		$ctr=$u + 1;
    		$outputString = "<b>".$ctr." - </b>" . $outputArray['Published'][$u]."<br>";
    		echo $outputString;
    	}
    } else {
    	echo "No Records.<br>";
    }
    	    
    $keyHeader = "<br><b>(2) In Press:</b><br>";
	echo $keyHeader;
	
    if(count($outputArray['In Press']) > 0) {
		for($u=0; $u<count($outputArray['In Press']); $u++) {
    		$ctr=$u + 1;
    		$outputString = "<b>".$ctr." - </b>" . $outputArray['In Press'][$u]."<br>";
    		echo $outputString;
    	}
    } else {
    	echo "No Records.<br>";
    }
    
    $keyHeader = "<br><b>(3) Submitted:</b><br>";
	echo $keyHeader;
	
    if(count($outputArray['Submitted']) > 0) {
		for($u=0; $u<count($outputArray['Submitted']); $u++) {
    		$ctr=$u + 1;
    		$outputString = "<b>".$ctr." - </b>" . $outputArray['Submitted'][$u]."<br>";
    		echo $outputString;
    	}
    } else {
    	echo "No Records.<br>";
    }
    unset($outputArray);
}

$NEXT_YEAR 					= (int)$REPORT_YEAR + 1;

$oncologyGroup = array(4664, 3722, 495, 3334, 737, 805);
	
if($clinical_value == false || in_array($proxy_id, $oncologyGroup)) {
	$DUE_DATE 				= "February 1, " . $NEXT_YEAR;
} else {
	$DUE_DATE 				= "February 15, " . $NEXT_YEAR;
}

$PAGE_META["title"]			= "Your Annual Report for " . $REPORT_YEAR;
$PAGE_META["description"]	= "Your Annual report can be downloaded here.";
$PAGE_META["keywords"]		= "";
$ERROR					= 0;
$ERRORSTR				= array();

$STEP					= 1;

$lastname = get_account_data("lastname", $proxy_id);
$pdf_string 	= str_replace("'", "", $lastname) . "_" .$REPORT_YEAR;
$pdf_string 	= str_replace(" ", "_", $pdf_string);
$pdf_string 	= strtolower($pdf_string);
$output_file	= ANNUALREPORT_STORAGE."/".$pdf_string;

echo "<h1>Your Annual Report for ".$REPORT_YEAR."</h1>";

echo "<form><input class=\"btn\" type=\"button\" value=\"Download Report\" onClick=\"window.location.href='".ENTRADA_URL."/file-annualreport.php?file=".$pdf_string.".pdf'\"></form><h1></h1>";	

ob_end_flush();
ob_start("on_checkout");

if($clinical_value == true)
{
?>
<h1><center>Annual Report of Clinical Faculty Members</center></h1>
<h2>The personal information collected on this form is collected under the legal
authority of the royal Charter of 1841, as amended.  The information
collected is used for academic purposes.  Any questions may be directed to
the Associate Dean for the Faculty of Health Sciences.</h2>

<h2><center>Due: <?php echo $DUE_DATE; ?> <br /><br /> A Curriculum Vitae (electronic or hardcopy) is required:</center></h2>				
<?php
echo "
<center>(Please ensure a separate copy of the Role Definitions and Expectations is attached to the Head's Assessment 
of Annual Performance in addition to the Annual Report by the Clinical Faculty Member.)</center><br /><br />

The Annual Report by Clinical Faculty aligns two distinct evaluative processes:<br />
&nbsp;&nbsp;&nbsp;1.  Assessment of academic (teaching, research and service) responsibilities to Queen's University.<br />
&nbsp;&nbsp;&nbsp;2.  Assessment of clinical activity and clinical service contribution in the fulfillment of SEAMO's mission.<br />";
?>
<?php
} else {
?>
<h1><center>Annual Report of QUFA Faculty Members</center></h1>
<h2><center>Due: <?php echo $DUE_DATE; ?> <br /><br /> A Curriculum Vitae (electronic or hardcopy) is required:</center></h2>				
<?php
}
?>
<table class="tableListReport" border="1" border = "1" cellspacing="0" summary="Member Information">
	<tbody>	
	<?php	
		$query 	= "SELECT `department`, `cross_department` 
		FROM `ar_profile` 
		WHERE `year_reported` = ".$db->qstr($REPORT_YEAR)."
		AND `proxy_id` = ".$db->qstr($proxy_id);
		
		if($result = $db->GetRow($query))
		{
			$department = $result["department"];
			$fullname = get_account_data("fullname", $proxy_id);
			echo "<tr>\n";
			echo "	<td class=\"student_name\" width=\"25%\"><b>Name: </b>".$fullname."&nbsp;</td>\n";
			echo "	<td class=\"department\" width=\"75%\"><b>Department: </b>".$department."&nbsp;</td>\n";
			echo "</tr>";
			echo "<tr>\n";
			
			$staffNumber = getNumberFromProxy($proxy_id);
			
			echo "	<td class=\"student_name\" width=\"25%\"><b>Staff Number: </b>".$staffNumber."&nbsp;</td>\n";
			echo "	<td class=\"department\" width=\"75%\">&nbsp;</td>\n";
			echo "</tr>";
			echo "<tr>\n";
			echo "	<td class=\"student_name\" width=\"25%\"><b>Period: </b>Calendar Year ".$REPORT_YEAR."&nbsp;</td>\n";
			echo "	<td class=\"department\" width=\"75%\"><b>Cross-Appointments: </b>".$result["cross_department"]."&nbsp;</td>\n";
			echo "</tr>";
		}
	?>
	</tbody>
	</table>
	<br />	
	<?php
	if($clinical_value == true)
	{
		?>				
		<table class="tableListReport" border="1" border = "1" cellspacing="0" summary="Role Definitions">
		<tbody>	
		<?php
			$query 	= "SELECT * 
			FROM `ar_profile` 
			WHERE `year_reported` = ".$db->qstr($REPORT_YEAR)."
			AND `proxy_id` = ".$db->qstr($proxy_id);	
			
			if(!$result = $db->GetRow($query))
			{	
				echo "Error: You must have a profile record for this reporting period.<br><br>";
			}
			else 
			{
				echo "<tr>\n";
				echo "	<td class=\"student_name\" width=\"25%\"><b>Education: </b></td>\n";
				echo "	<td class=\"department\" width=\"75%\">".$result['education_comments']."&nbsp;</td>\n";
				echo "</tr>";
				echo "<tr>\n";
				echo "	<td class=\"student_name\" width=\"25%\"><b>Research / Scholarship: </b></td>\n";
				echo "	<td class=\"department\" width=\"75%\">".$result['research_comments']."&nbsp;</td>\n";
				echo "</tr>";
				echo "<tr>\n";
				echo "	<td class=\"student_name\" width=\"25%\"><b>Clinical Activity: </b></td>\n";
				echo "	<td class=\"department\" width=\"75%\">".$result['clinical_comments']."&nbsp;</td>\n";
				echo "</tr>";
				echo "<tr>\n";
				echo "	<td class=\"student_name\" width=\"25%\"><b>Service / Administration: </b></td>\n";
				echo "	<td class=\"department\" width=\"75%\">".$result['service_comments']."&nbsp;</td>\n";
				echo "</tr>";
			}
		?>
		</tbody>
		</table>
	<?php
	} 
	echo '<!-- FOOTER LEFT "$CHAPTER" --> <!-- FOOTER CENTER "" --> <!-- FOOTER RIGHT "$PAGE" --><!-- PAGE BREAK --><br><br>';
?>
<!-- Load information here  -->
<h1>Section I - Education</h1>
<h2>A. Course Instruction</h2>
<?php
//echo display_default_enrollment(true);
$undergraduateTeachingQuery = "SELECT * 
FROM `ar_undergraduate_teaching` 
WHERE `year_reported` = ".$db->qstr($REPORT_YEAR)."
AND `proxy_id` = ".$db->qstr($proxy_id);		

echo '<b>1a) Undergraduate - MEdTech Central:</b><br />';

if(!$undergraduateTeachingResults = $db->GetAll($undergraduateTeachingQuery))
{	
	echo "No Undergraduate - MEdTech Central teaching in the system for this reporting period.<br><br>";
	
} else {
	?>			
	<table class="tableListReport" border="1" cellspacing="0" summary="Education">
		<colgroup>
			<col class="delete" />
			<col class="course_number" style="width: 100px"/>
			<col class="course_name"  style="width: 100px"/>
			<col class="assigned" />
		</colgroup>
		<thead>
			<tr>
				<td class="delete" id="colDelete">#</td>
				<td class="course_number" id="colcourse_number" style="width: 100px">Course Number<?php if(AUTH_APP_ID == "101") { echo " / Level"; } ?></td>
				<td class="course_name" id="colcourse_name" style="width: 100px">Course Name</td>
				<td class="assigned" id="colassigned">Assigned</td>
			</tr>
		</thead>
		<tbody>
		<?php
			$ctr 			= 0;
			$commentsArray 	= array();
			$hoursArray 	= array();
			foreach($undergraduateTeachingResults as $result)
			{	
				$listing 								= $ctr+1;
				$hoursArray[$ctr]['course_number']		= $result['course_number'];
				$commentsArray[$ctr]['course_number']	= $result['course_number'];
				
				$hoursArray[$ctr]["lecture_hours"] = $result['lecture_hours'];
				$hoursArray[$ctr]["lab_hours"] = $result['lab_hours'];
				$hoursArray[$ctr]["small_group_hours"] = $result['small_group_hours'];
				$hoursArray[$ctr]["symposium_hours"] = $result['symposium_hours'];
				$hoursArray[$ctr]["directed_independant_learning_hours"] = $result['directed_independant_learning_hours'];
				$hoursArray[$ctr]["review_feedback_session_hours"] = $result['review_feedback_session_hours'];
				$hoursArray[$ctr]["examination_hours"] = $result['examination_hours'];
				$hoursArray[$ctr]["clerkship_seminar_hours"] = $result['clerkship_seminar_hours'];
				$hoursArray[$ctr]["other_hours"] = $result['other_hours'];
				if($clinical_value == true) {
					$hoursArray[$ctr]["coord_enrollment"] = $result['coord_enrollment'];
				}
				
				if($result['comments'] == '')
				{
					$commentsArray[$ctr]['comments'] = "No Comments";
				}
				else 
				{
					$commentsArray[$ctr]['comments'] = $result['comments'];
				}
				$curriculum_level = fetch_curriculum_level($result['course_number']);
				echo "<tr>\n";
				echo "	<td class=\"delete\">".$listing."&nbsp;</td>\n";					
				echo "	<td class=\"course_number\">".html_encode($result['course_number']);
				if(AUTH_APP_ID == "101") 
				{ 
					echo " / ".$curriculum_level; 
				} 
				echo "&nbsp;</td>\n";
				echo "	<td class=\"course_name\">".((strlen(html_encode($result['course_name'])) > 80) ? substr(html_encode($result['course_name']), 0, 79) . "..." : html_encode($result['course_name']))."&nbsp;</td>\n";
				echo "	<td class=\"assigned\">".html_encode($result['assigned'])."&nbsp;</td>\n";
				echo "</tr>\n";
				$ctr++;
			}
		?>
		</tbody>
	</table>
	<br />
	<table class="tableListReport" border="1" cellspacing="0" summary="Education Hours">
			<colgroup>
				<col class="delete" />
				<col class="small_numbers" />
				<col class="small_numbers" />	
				<col class="small_numbers" />
				<col class="small_numbers" />
				<col class="small_numbers" />
				<col class="small_numbers" />
				<col class="small_numbers" />
				<col class="small_numbers" />
				<col class="small_numbers" />
				<?php if($clinical_value == true) { ?>
					<col class="small_numbers" />
				<?php } ?>
			</colgroup>
			<thead>
				<tr>
					<td class="delete" id="colDelete" width=\"2%\">#</td>
					<td class="small_numbers" id="collec">Lec</td>
					<td class="small_numbers" id="collab">Lab</td>
					<td class="small_numbers" id="colsg">Small Group</td>
					<td class="small_numbers" id="colsym" style="width: 65px">Symposium</td>
					<td class="small_numbers" id="coldir">Directed Learning</td>
					<td class="small_numbers" id="colfb">Feedback</td>
					<td class="small_numbers" id="colexam">Exam</td>
					<td class="small_numbers" id="colsem">Clerkship Seminar</td>
					<td class="small_numbers" id="coloth">Other</td>
					<?php if($clinical_value == true) { ?>
						<td class="small_numbers" id="colcoord_enrollment">Co-Ord Enrol</td>
					<?php } ?>
				</tr>
			</thead>
			<tbody>
			<?php
				for($i=0; $i < count($hoursArray); $i++)
				{	
					$listing 								= $i+1;
					echo "<tr>\n";
					echo "	<td class=\"delete\" width=\"2%\">".$listing."&nbsp;</td>\n";
						echo "	<td class=\"small_numbers\">".html_encode($hoursArray[$i]['lecture_hours'])."&nbsp;</td>\n";
						echo "	<td class=\"small_numbers\">".html_encode($hoursArray[$i]['lab_hours'])."&nbsp;</td>\n";
						echo "	<td class=\"small_numbers\">".html_encode($hoursArray[$i]['small_group_hours'])."&nbsp;</td>\n";
						echo "	<td class=\"small_numbers\">".html_encode($hoursArray[$i]['symposium_hours'])."&nbsp;</td>\n";
						echo "	<td class=\"small_numbers\">".html_encode($hoursArray[$i]['directed_independant_learning_hours'])."&nbsp;</td>\n";
						echo "	<td class=\"small_numbers\">".html_encode($hoursArray[$i]['review_feedback_session_hours'])."&nbsp;</td>\n";
						echo "	<td class=\"small_numbers\">".html_encode($hoursArray[$i]['examination_hours'])."&nbsp;</td>\n";
						echo "	<td class=\"small_numbers\">".html_encode($hoursArray[$i]['clerkship_seminar_hours'])."&nbsp;</td>\n";
						echo "	<td class=\"small_numbers\">".html_encode($hoursArray[$i]['other_hours'])."&nbsp;</td>\n";
						if($clinical_value == true) {
							echo "	<td class=\"small_numbers\">".html_encode($hoursArray[$i]['coord_enrollment'])."&nbsp;</td>\n";
						}
					echo "</tr>\n";
				}
			?>
		</tbody>
	</table>
	<br />
	<table class="tableListReport" border="1" cellspacing="0" summary="Education Comments">
			<colgroup>
				<col class="delete" />
				<col class="course_number" />
				<col class="full_description" />
			</colgroup>
			<thead>
				<tr>
					<td class="delete" id="colDelete" width=\"2%\">#</td>
					<td class="course_number" id="colcourse_number" width=\"25%\" style="width: 100px">Course Number</td>
					<td class="full_description" id="colcourse_name" width=\"73%\">Comments</td>
				</tr>
			</thead>
			<tbody>
			<?php
				for($i=0; $i < count($commentsArray); $i++)
				{	
					$listing 								= $i+1;
					echo "<tr>\n";
					echo "	<td class=\"delete\" width=\"2%\">".$listing."&nbsp;</td>\n";					
					echo "	<td class=\"course_number\" width=\"25%\" style=\"width: 100px\">".html_encode($commentsArray[$i]['course_number'])."&nbsp;</td>\n";
					echo "	<td class=\"full_description\" width=\"73%\">".html_encode($commentsArray[$i]['comments'])."&nbsp;</td>\n";
					echo "</tr>\n";
				}
			?>
		</tbody>
	</table>
	<br />
<?php 
}
 
$undergraduateTeachingQuery = "SELECT * 
FROM `ar_undergraduate_nonmedical_teaching` 
WHERE `year_reported` = ".$db->qstr($REPORT_YEAR)."
AND `proxy_id` = ".$db->qstr($proxy_id);

echo '<b>1b) Undergraduate (Other):</b><br /><br />';

if(!$undergraduateTeachingResults = $db->GetAll($undergraduateTeachingQuery))
{	
	echo "No Undergraduate (Other) teaching in the system for this reporting period.<br><br>";
}
else {	
?>			
	<table class="tableListReport" border="1" cellspacing="0" summary="Education">
		<colgroup>
			<col class="delete" />
			<col class="course_number" style="width: 100px"/>
			<col class="course_name" />
			<col class="assigned" />
			<col class="small_numbers" />
			<col class="small_numbers" />	
			<col class="small_numbers" />
			<col class="small_numbers" />
			<col class="small_numbers" />
			<col class="small_numbers" />
			<col class="small_numbers" />
			<col class="small_numbers" />
			<?php if($clinical_value == true) { ?>
				<col class="small_numbers" />
			<?php } ?>
			<col class="small_numbers" />
		</colgroup>
		<thead>
			<tr>
				<td class="delete" id="colDelete">#</td>
				<td class="course_number" id="colcourse_number" style="width: 100px">Course Number</td>
				<td class="course_name" id="colcourse_name">Course Name</td>
				<td class="assigned" id="colassigned">Assigned</td>				
				<td class="small_numbers" id="collec_enrollment">Lec Enrol</td>				
				<td class="small_numbers" id="collec_hours">Lec Hrs</td>				
				<td class="small_numbers" id="collab_enrollment">Lab Enrol</td>				
				<td class="small_numbers" id="collab_hours">Lab Hrs</td>				
				<td class="small_numbers" id="coltut_enrollment">Tut Enrol</td>				
				<td class="small_numbers" id="coltut_hours">Tut Hrs</td>				
				<td class="small_numbers" id="colsem_enrollment">Sem Enrol</td>				
				<td class="small_numbers" id="colsem_hours">Sem Hrs</td>
				<?php if($clinical_value == true) { ?>
					<td class="small_numbers" id="colcoord_enrollment">Co-Ord Enrol</td>
				<?php } ?>
				<td class="small_numbers" id="colpbl_hours">PBL Hrs</td>				
			</tr>
		</thead>
		<tbody>
		<?php
			$ctr 			= 0;
			$commentsArray 	= array();
			foreach($undergraduateTeachingResults as $result)
			{	
				$listing 								= $ctr+1;
				$commentsArray[$ctr]['course_number']	= $result['course_number'];
				
				if($result['comments'] == '')
				{
					$commentsArray[$ctr]['comments'] = "No Comments";
				}
				else 
				{
					$commentsArray[$ctr]['comments'] = $result['comments'];
				}
				
				echo "<tr>\n";
				echo "	<td class=\"delete\">".$listing."&nbsp;</td>\n";					
				echo "	<td class=\"course_number\">".html_encode($result['course_number'])."&nbsp;</td>\n";
				echo "	<td class=\"course_name\">".((strlen(html_encode($result['course_name'])) > 80) ? substr(html_encode($result['course_name']), 0, 79) . "..." : html_encode($result['course_name']))."&nbsp;</td>\n";
				echo "	<td class=\"assigned\">".html_encode($result['assigned'])."&nbsp;</td>\n";					
				echo "	<td class=\"small_numbers\">".html_encode($result['lec_enrollment'])."&nbsp;</td>\n";					
				echo "	<td class=\"small_numbers\">".html_encode($result['lec_hours'])."&nbsp;</td>\n";					
				echo "	<td class=\"small_numbers\">".html_encode($result['lab_enrollment'])."&nbsp;</td>\n";					
				echo "	<td class=\"small_numbers\">".html_encode($result['lab_hours'])."&nbsp;</td>\n";					
				echo "	<td class=\"small_numbers\">".html_encode($result['tut_enrollment'])."&nbsp;</td>\n";					
				echo "	<td class=\"small_numbers\">".html_encode($result['tut_hours'])."&nbsp;</td>\n";					
				echo "	<td class=\"small_numbers\">".html_encode($result['sem_enrollment'])."&nbsp;</td>\n";					
				echo "	<td class=\"small_numbers\">".html_encode($result['sem_hours'])."&nbsp;</td>\n";					
				if($clinical_value == true) {
					echo "	<td class=\"small_numbers\">".html_encode($result['coord_enrollment'])."&nbsp;</td>\n";
				}
				echo "	<td class=\"small_numbers\">".html_encode($result['pbl_hours'])."&nbsp;</td>\n";					
				echo "</tr>\n";
				$ctr++;
			}
		?>
	</tbody>
</table>
<br />
<table class="tableListReport" border="1" cellspacing="0" summary="Education Comments">
		<colgroup>
			<col class="delete" />
			<col class="course_number" />
			<col class="full_description" />
		</colgroup>
		<thead>
			<tr>
				<td class="delete" id="colDelete" width=\"2%\">#</td>
				<td class="course_number" id="colcourse_number" width=\"25%\">Course Number</td>
				<td class="full_description" id="colcourse_name" width=\"73%\">Comments</td>
			</tr>
		</thead>
		<tbody>
		<?php
			for($i=0; $i < count($commentsArray); $i++)
			{	
				$listing 								= $i+1;
				echo "<tr>\n";
				echo "	<td class=\"delete\" width=\"2%\">".$listing."&nbsp;</td>\n";					
				echo "	<td class=\"course_number\" width=\"25%\">".html_encode($commentsArray[$i]['course_number'])."&nbsp;</td>\n";
				echo "	<td class=\"full_description\" width=\"73%\">".html_encode($commentsArray[$i]['comments'])."&nbsp;</td>\n";
				echo "</tr>\n";
			}
		?>
	</tbody>
</table>
<br />
<?php
}
$query 	= "SELECT * 
FROM `ar_graduate_teaching` 
WHERE `year_reported` = ".$db->qstr($REPORT_YEAR)."
AND `proxy_id` = ".$db->qstr($proxy_id)." 
ORDER BY `course_number` ASC, `course_name` ASC";	

echo '<b>2) Graduate:</b><br /><br />';

if(!$results = $db->GetAll($query))
{	
	echo "No Graduate teaching in the system for this reporting period.<br><br>";
}
else 
{
	?>			
	<table class="tableListReport" border="1" cellspacing="0" summary="Education">
		<colgroup>
			<col class="delete" />
			<col class="course_number" />
			<col class="course_name" />
			<col class="assigned" />
			<col class="small_numbers" />
			<col class="small_numbers" />	
			<col class="small_numbers" />
			<col class="small_numbers" />
			<col class="small_numbers" />
			<col class="small_numbers" />
			<col class="small_numbers" />
			<col class="small_numbers" />
			<?php if($clinical_value == true) { ?>
				<col class="small_numbers" />
			<?php } ?>
			<col class="small_numbers" />
		</colgroup>
		<thead>
			<tr>
				<td class="delete" id="colDelete">#</td>
				<td class="course_number" id="colcourse_number">Course Number</td>
				<td class="course_name" id="colcourse_name">Course Name</td>
				<td class="assigned" id="colassigned">Assigned</td>				
				<td class="small_numbers" id="collec_enrollment">Lec Enrol</td>				
				<td class="small_numbers" id="collec_hours">Lec Hrs</td>				
				<td class="small_numbers" id="collab_enrollment">Lab Enrol</td>				
				<td class="small_numbers" id="collab_hours">Lab Hrs</td>				
				<td class="small_numbers" id="coltut_enrollment">Tut Enrol</td>				
				<td class="small_numbers" id="coltut_hours">Tut Hrs</td>				
				<td class="small_numbers" id="colsem_enrollment">Sem Enrol</td>				
				<td class="small_numbers" id="colsem_hours">Sem Hrs</td>
				<?php if($clinical_value == true) { ?>
					<td class="small_numbers" id="colcoord_enrollment">Co-Ord Enrol</td>
				<?php } ?>
				<td class="small_numbers" id="colpbl_hours">PBL Hrs</td>				
			</tr>
		</thead>
		<tbody>
		<?php
			$ctr 			= 0;
			$commentsArray 	= array();
			foreach($results as $result)
			{	
				$listing 								= $ctr+1;
				
				if(!isset($result['course_number']) || trim($result['course_number']) == '')
				{
					$result['course_number'] = "N/A";
					$commentsArray[$ctr]['course_number'] 	= "N/A";
				}
				else 
				{
					$commentsArray[$ctr]['course_number']	= $result['course_number'];
				}
				
				if($result['comments'] == '')
				{
					$commentsArray[$ctr]['comments'] 		= "No Comments";
				}
				else 
				{
					$commentsArray[$ctr]['comments'] 		= $result['comments'];
				}
				
				echo "<tr>\n";
				echo "	<td class=\"delete\">".$listing."&nbsp;</td>\n";					
				echo "	<td class=\"course_number\">".((strlen(html_encode($result['course_number'])) > 15) ? substr(html_encode($result['course_number']), 0, 14) . "..." : html_encode($result['course_number']))."&nbsp;</td>\n";
				echo "	<td class=\"course_name\">".((strlen(html_encode($result['course_name'])) > 80) ? substr(html_encode($result['course_name']), 0, 79) . "..." : html_encode($result['course_name']))."&nbsp;</td>\n";
				echo "	<td class=\"assigned\">".html_encode($result['assigned'])."&nbsp;</td>\n";					
				echo "	<td class=\"small_numbers\">".html_encode($result['lec_enrollment'])."&nbsp;</td>\n";					
				echo "	<td class=\"small_numbers\">".html_encode($result['lec_hours'])."&nbsp;</td>\n";					
				echo "	<td class=\"small_numbers\">".html_encode($result['lab_enrollment'])."&nbsp;</td>\n";					
				echo "	<td class=\"small_numbers\">".html_encode($result['lab_hours'])."&nbsp;</td>\n";					
				echo "	<td class=\"small_numbers\">".html_encode($result['tut_enrollment'])."&nbsp;</td>\n";					
				echo "	<td class=\"small_numbers\">".html_encode($result['tut_hours'])."&nbsp;</td>\n";					
				echo "	<td class=\"small_numbers\">".html_encode($result['sem_enrollment'])."&nbsp;</td>\n";					
				echo "	<td class=\"small_numbers\">".html_encode($result['sem_hours'])."&nbsp;</td>\n";					
				if($clinical_value == true) {
					echo "	<td class=\"small_numbers\">".html_encode($result['coord_enrollment'])."&nbsp;</td>\n";
				}
				echo "	<td class=\"small_numbers\">".html_encode($result['pbl_hours'])."&nbsp;</td>\n";					
				echo "</tr>\n";
				$ctr++;
			}
		?>
		</tbody>
	</table>
	<br />
	<table class="tableListReport" border="1" cellspacing="0" summary="Education Comments">
			<colgroup>
				<col class="delete" />
				<col class="course_number" />
				<col class="full_description" />
			</colgroup>
			<thead>
				<tr>
					<td class="delete" id="colDelete" width="2%">#</td>
					<td class="course_number" id="colcourse_number" width="25%">Course Number</td>
					<td class="full_description" id="colcourse_name" width="73%">Comments</td>
				</tr>
			</thead>
			<tbody>
			<?php
				for($i=0; $i < count($commentsArray); $i++)
				{	
					$listing 								= $i+1;
					echo "<tr>\n";
					echo "	<td class=\"delete\" width=\"2%\">".$listing."&nbsp;</td>\n";					
					echo "	<td class=\"course_number\" width=\"25%\">".html_encode($commentsArray[$i]['course_number'])."&nbsp;</td>\n";
					echo "	<td class=\"full_description\" width=\"73%\">".html_encode($commentsArray[$i]['comments'])."&nbsp;</td>\n";
					echo "</tr>\n";
				}
			?>
		</tbody>
	</table>
	<br />
	<h2>B. Undergraduate / Graduate Supervision</h2>
	<br />
<?php
}
// Check the database for education
$query 	= "SELECT * 
FROM `ar_undergraduate_supervision` 
WHERE `year_reported` = ".$db->qstr($REPORT_YEAR)."
AND `proxy_id` = ".$db->qstr($proxy_id)."
ORDER BY `course_number` ASC, `student_name` ASC";

echo '<b>1) Undergraduate Supervision:</b><br><br>';

if(!$results = $db->GetAll($query))
{
	echo "No Undergraduate Supervision in the system for this reporting period.<br><br>";
}
else 
{
?>
<table class="tableListReport" border="1" cellspacing="0" summary="Undergraduate Supervision">
		<colgroup>
			<col class="delete" />
			<col class="student_name" />
			<col class="small_numbers" />
			<col class="course_number" />
			<col class="course_number" />
			<col class="full_description" />
		</colgroup>
		<thead>
			<tr>
				<td class="delete" id="colDelete">#</td>
				<td class="student_name" id="colstudent_name">Student Name</td>
				<td class="small_numbers" id="coldegree">Degree</td>
				<td class="course_number" id="colcourse_number">Course Number</td>				
				<td class="course_number" id="colsupervision">Supervision</td>				
				<td class="full_description" id="colcomments">Comments</td>		
			</tr>
		</thead>
	<tbody>
		<?php
			$ctr 			= 1;
			foreach($results as $result)
			{
				echo "<tr>\n";
				echo "	<td class=\"delete\">".$ctr."&nbsp;</td>\n";					
				echo "	<td class=\"student_name\">".html_encode($result['student_name'])."&nbsp;</td>\n";
				echo "	<td class=\"course_name\">".html_encode($result['degree'])."&nbsp;</td>\n";
				echo "	<td class=\"course_number\">".html_encode($result['course_number'])."&nbsp;</td>\n";
				echo "	<td class=\"course_number\">".html_encode($result['supervision'])."&nbsp;</td>\n";
				echo "	<td class=\"full_description\">".(isset($result['comments']) && $result['comments'] != "" ? html_encode($result['comments']) : "N/A")."&nbsp;</td>\n";
				echo "</tr>\n";
				$ctr++;
			}
		?>
	</tbody>
	</table>
	<br />
	<?php 
	}
	// Check the database for education
	$query 	= "SELECT * 
	FROM `ar_graduate_supervision` 
	WHERE `year_reported` = ".$db->qstr($REPORT_YEAR)."
	AND `proxy_id` = ".$db->qstr($proxy_id)."
	ORDER BY `degree`, `student_name` ASC";	
	
	echo '<b>2) Graduate Supervision:</b><br /><br />';
	
	if(!$results = $db->GetAll($query))
	{	
		echo "No Graduate Supervision in the system for this reporting period.<br><br>";
	}
	else 
	{
	?>	
		<table class="tableListReport" border="1" cellspacing="0" summary="Graduate Supervision">
			<colgroup>
				<col class="delete" />
				<col class="student_name" />
				<col class="small_numbers" />
				<col class="small_numbers" />
				<col class="course_number" />
				<col class="small_numbers" />
				<col class="small_numbers" />
				<col class="full_description" />
			</colgroup>
			<thead>
				<tr>
					<td class="delete" id="colDelete" width="2%">#</td>
					<td class="student_name" id="colstudent_name" width="18%">Student Name</td>
					<td class="small_numbers" id="coldegree" width="15%">Degree</td>
					<td class="small_numbers" id="colacitve" width="10%">Status</td>
					<td class="course_number" id="colsupervision" width="15%">Supervision</td>
					<td class="small_numbers" id="colyear_started" width="10%">Start Year</td>				
					<td class="small_numbers" id="colthesis_defended" width="10%">Thesis Defended</td>		
					<td class="full_description" id="colcomments" width="20%">Comments</td>		
				</tr>
			</thead>
		<tbody>	
		<?php				
			$ctr 			= 1;
			foreach($results as $result)
			{
				echo "<tr>\n";
				echo "	<td class=\"delete\" width=\"2%\">".$ctr."&nbsp;</td>\n";					
				echo "	<td class=\"student_name\" width=\"18%\">".html_encode($result['student_name'])."&nbsp;</td>\n";
				echo "	<td class=\"small_numbers\" width=\"15%\">".html_encode($result['degree'])."&nbsp;</td>\n";
				echo "	<td class=\"small_numbers\" width=\"10%\">".html_encode($result['active'])."&nbsp;</td>\n";				
				echo "	<td class=\"course_number\" width=\"15%\">".html_encode($result['supervision'])."&nbsp;</td>\n";					
				echo "	<td class=\"small_numbers\" width=\"10%\">".html_encode($result['year_started'])."&nbsp;</td>\n";	
				echo "	<td class=\"small_numbers\" width=\"10%\">".html_encode($result['thesis_defended'])."&nbsp;</td>\n";	
				echo "	<td class=\"full_description\" width=\"20%\">".(isset($result['comments']) && $result['comments'] != "" ? html_encode($result['comments']) : "N/A")."&nbsp;</td>\n";		
				echo "</tr>\n";
				$ctr++;
			}
			?>
		</tbody>
		</table>
		<br />
<?php 
}
?>
<br />
	<?php 
	
	// Check the database for education
	$query 	= "SELECT * 
	FROM `ar_memberships` 
	WHERE `year_reported` = ".$db->qstr($REPORT_YEAR)."
	AND `proxy_id` = ".$db->qstr($proxy_id)."
	ORDER BY `student_name` ASC";	
	
	echo '<b>3) Membership on Graduate Examining and Supervisory Committees (Exclude Supervision):</b><br /><br />';
	
	if(!$results = $db->GetAll($query))
	{	
		echo "No Membership records in the system for this reporting period.<br><br>";
	}
	else 
	{
	?>	
		<table class="tableListReport" border="1" cellspacing="0" summary="Memberships">
			<colgroup>
				<col class="delete" />
				<col class="student_name" />
				<col class="small_numbers" />
				<col class="student_name" />
				<col class="student_name" />
				<col class="full_description" />
			</colgroup>
			<thead>
				<tr>
					<td class="delete" id="colDelete">#</td>
					<td class="student_name" id="colstudent_name">Student Name</td>
					<td class="small_numbers" id="coldegree">Degree</td>
					<td class="student_name" id="coldepartment">Department</td>
					<td class="student_name" id="coluniversity">University</td>
					<td class="full_description" id="colrole_description">Role / Description</td>		
				</tr>
			</thead>
		<tbody>	
		<?php				
			$ctr 			= 1;
			foreach($results as $result)
			{
				echo "<tr>\n";
				echo "	<td class=\"delete\">".$ctr."&nbsp;</td>\n";					
				echo "	<td class=\"student_name\">".html_encode($result['student_name'])."&nbsp;</td>\n";
				echo "	<td class=\"small_numbers\">".html_encode($result['degree'])."&nbsp;</td>\n";
				echo "	<td class=\"student_name\">".html_encode($result['department'])."&nbsp;</td>\n";				
				echo "	<td class=\"student_name\">".html_encode($result['university'])."&nbsp;</td>\n";
				echo "	<td class=\"full_description\">".html_encode($result['role']);
				if(isset($result['role_description']) && trim($result['role_description']) != '')
				{
					echo ": " .html_encode($result['role_description']);
				}
				echo "</td>\n";		
				echo "</tr>\n";
				$ctr++;
			}
			?>
		</tbody>
		</table>
		<?php 
			}
			if($clinical_value == true)
			{
			?>
			<br />
			<h2>C. Education of Clinical Trainees Including Clinical Clerks</h2>
			<?php 
			// Check the database for education
			$query 	= "SELECT * 
			FROM `ar_clinical_education` 
			WHERE `year_reported` = ".$db->qstr($REPORT_YEAR)."
			AND `proxy_id` = ".$db->qstr($proxy_id)."
			ORDER BY `level` ASC";	
			
			if(!$results = $db->GetAll($query))
			{	
				echo "No Clinical Education records in the system for this reporting period.<br><br>";
			}
			else 
			{
			?>	
				<table class="tableListReport" border="1" cellspacing="0" summary="Clinical Education">
					<colgroup>
						<col class="delete" />
						<col class="student_name" />
						<col class="student_name" />
						<col class="average_hours" />
						<col class="average_hours" />
						<col class="full_description" />
					</colgroup>
					<thead>
						<tr>
							<td class="delete" id="colDelete">#</td>
							<td class="student_name" id="collevel">Level of Trainee / Description</td>
							<td class="student_name" id="collocation">Location</td>
							<td class="average_hours" id="colaverage_hours">Avg. Hrs/Week</td>
							<td class="average_hours" id="colaverage_hours"> &gt; 75% Research</td>
							<td class="full_description" id="coldescription">Description</td>		
						</tr>
					</thead>
				<tbody>	
				<?php				
					$ctr 			= 1;
					foreach($results as $result)
					{
						echo "<tr>\n";
						echo "	<td class=\"delete\">".$ctr."&nbsp;</td>\n";					
						echo "	<td class=\"student_name\">".html_encode($result['level']);
						if(isset($result['level_description']) && trim($result['level_description']) != '')
						{
							echo ": " .html_encode($result['level_description']);
						}
						echo "</td>\n";
						echo "	<td class=\"student_name\">".html_encode($result['location']);
						if(isset($result['location_description']) && trim($result['location_description']) != '')
						{
							echo ": " .html_encode($result['location_description']);
						}
						echo "</td>\n";
						echo "	<td class=\"average_hours\">".html_encode($result['average_hours'])."&nbsp;</td>\n";
						if(isset($result['research_percentage']) && trim($result['research_percentage']) == '1')
						{
							$research_percentage = "Yes";
						} else {
							$research_percentage = "No";
						}
						echo "	<td class=\"average_hours\">".$research_percentage."&nbsp;</td>\n";
						echo "	<td class=\"full_description\">".html_encode($result['description'])."&nbsp;</td>\n";
						echo "</tr>\n";
						$ctr++;
					}
					?>
				</tbody>
				</table>
			<?php 
			}
			?>
			<br />
			<h2>D. Continuing Education Under The Aegis of Queen's</h2>
			<?php 
			// Check the database for education
			$query 	= "SELECT * 
			FROM `ar_continuing_education` 
			WHERE `year_reported` = ".$db->qstr($REPORT_YEAR)."
			AND `proxy_id` = ".$db->qstr($proxy_id)."
			ORDER BY `start_year` ASC";	
			
			if(!$results = $db->GetAll($query))
			{	
				echo "No Clinical Education records in the system for this reporting period.<br><br>";
			}
			else 
			{
			?>	
				<table class="tableListReport" border="1" cellspacing="0" summary="Continuing Education">
					<colgroup>
						<col class="delete" />
						<col class="student_name" />
						<col class="student_name" />
						<col class="average_hours" />
						<col class="full_description" />
						<col class="student_name" />
						<col class="small_numbers" />
					</colgroup>
					<thead>
						<tr>
							<td class="delete" id="colDelete">#</td>
							<td class="student_name" id="collevel">Organising Unit</td>
							<td class="student_name" id="collocation">Location</td>
							<td class="average_hours" id="colaverage_hours">Avg. Hrs/Week</td>
							<td class="full_description" id="coldescription">Description</td>		
							<td class="student_name" id="colduration">Duration</td>		
							<td class="small_numbers" id="coltotal_hours">Total Hours</td>		
						</tr>
					</thead>
				<tbody>	
				<?php				
					$ctr 			= 1;
					foreach($results as $result)
					{
						echo "<tr>\n";
						echo "	<td class=\"delete\">".$ctr."&nbsp;</td>\n";					
						echo "	<td class=\"student_name\">".html_encode($result['unit'])."&nbsp;</td>";
						echo "	<td class=\"student_name\">" .html_encode($result['location'])."&nbsp;</td>\n";
						echo "	<td class=\"average_hours\">".html_encode($result['average_hours'])."&nbsp;</td>\n";
						echo "	<td class=\"full_description\">".html_encode($result['description'])."&nbsp;</td>\n";
						echo "	<td class=\"student_name\">". html_encode($result['start_month']) ."-".html_encode($result['start_year']) . " / "
															 .((html_encode($result['end_month']) == 0 ? "N/A" : html_encode($result['end_month']) ."-".html_encode($result['end_year'])))."&nbsp;</td>";
						echo "	<td class=\"small_numbers\">".html_encode($result['total_hours'])."&nbsp;</td>";
						echo "</tr>";
						$ctr++;
					}
					?>
				</tbody>
				</table>
			<?php 
					}
			}
			?>
			<br />
			<h2><?php echo ($clinical_value == true ? "E" : "C"); ?>. Innovation in Education</h2>
			<?php
			// Check the database for education
			$query 	= "SELECT * 
			FROM `ar_innovation` 
			WHERE `year_reported` = ".$db->qstr($REPORT_YEAR)."
			AND `proxy_id` = ".$db->qstr($proxy_id)."
			ORDER BY `course_name` ASC";	
			
			if(!$results = $db->GetAll($query))
			{	
				echo "No Innovation In Education records in the system for this reporting period.<br><br>";
			}
			else 
			{
			?>	
				<table class="tableListReport" border="1" cellspacing="0" summary="Innovation In Education">
					<colgroup>
						<col class="delete" />
						<col class="course_name" />
						<col class="course_number" />
						<col class="student_name" />
						<col class="full_description" />
					</colgroup>
					<thead>
						<tr>
							<td class="delete" id="colDelete" width="2%">#</td>
							<td class="course_name" id="colcourse_name" width="25%">Course Name</td>
							<td class="course_number" id="colcourse_number" width="13%">Course Number</td>
							<td class="student_name" id="coltype" width="30%">Type</td>
							<td class="full_description" id="coldescription" width="30%">Description</td>	
						</tr>
					</thead>
				<tbody>	
				<?php				
					$ctr 			= 1;
					foreach($results as $result)
					{
						echo "<tr>\n";
						echo "	<td class=\"delete\" width=\"2%\">".$ctr."&nbsp;</td>\n";					
						echo "	<td class=\"course_name\" width=\"25%\">".html_encode($result['course_name'])."&nbsp;</td>";
						echo "	<td class=\"course_number\" width=\"13%\">" .html_encode($result['course_number'])."&nbsp;</td>\n";
						echo "	<td class=\"student_name\" width=\"30%\">".html_encode($result['type'])."&nbsp;</td>\n";
						echo "	<td class=\"full_description\" width=\"30%\">".html_encode($result['description'])."&nbsp;</td>\n";
						echo "</tr>";
						$ctr++;
					}
					?>
				</tbody>
				</table>
			<?php 
			}
		?>
		<br />
		<h2><?php echo ($clinical_value == true ? "F" : "D"); ?>. Other Education</h2>
		<?php 
		// Check the database for education
		$query 	= "SELECT * 
		FROM `ar_other` 
		WHERE `year_reported` = ".$db->qstr($REPORT_YEAR)."
		AND `proxy_id` = ".$db->qstr($proxy_id)."
		ORDER BY `course_name` ASC";	
		
		if(!$results = $db->GetAll($query))
		{	
			echo "No Other Education Activity records in the system for this reporting period.<br><br>";
		}
		else 
		{
		?>	
			<table class="tableListReport" border="1" cellspacing="0" summary="Other Activities In Education">
				<colgroup>
					<col class="delete" />
					<col class="course_name" />
					<col class="student_name" />
					<col class="full_description" />
				</colgroup>
				<thead>
					<tr>
						<td class="delete" id="colDelete" width="2%">#</td>
						<td class="course_name" id="colcourse_name" width="30%">Course Name</td>
						<td class="student_name" id="coltype" width="23%">Type</td>
						<td class="full_description" id="coldescription" width="45%">Description</td>	
					</tr>
				</thead>
			<tbody>	
			<?php				
				$ctr 			= 1;
				foreach($results as $result)
				{
					echo "<tr>\n";
					echo "	<td class=\"delete\" width=\"2%\">".$ctr."&nbsp;</td>\n";					
					echo "	<td class=\"course_name\" width=\"40%\">".html_encode($result['course_name'])."&nbsp;</td>";
					echo "	<td class=\"student_name\" width=\"13%\">".html_encode($result['type'])."&nbsp;</td>\n";
					echo "	<td class=\"full_description\" width=\"45%\">".html_encode($result['description'])."&nbsp;</td>\n";
					echo "</tr>";
					$ctr++;
				}
				?>
			</tbody>
			</table>
		<?php 
			}
			echo '<!-- FOOTER LEFT "$CHAPTER" --> <!-- FOOTER CENTER "" --> <!-- FOOTER RIGHT "$PAGE" --><!-- PAGE BREAK --><br><br>';
		?>
		<!-- Load information here  -->
<h1>Section II - Scholarship, Research and Other Creative Activity</h1>
<h2>A. Research Projects, Research Grants, Contracts and Research in Education</h2>
<?php 
// Check the database for research
$query 	= "SELECT * 
FROM `ar_research` 
WHERE `year_reported` = ".$db->qstr($REPORT_YEAR)."
AND `proxy_id` = ".$db->qstr($proxy_id)."
AND `funding_status` = \"funded\"
ORDER BY `type` ASC, `role` DESC, `grant_title` ASC";	

if(!$results = $db->GetAll($query))
{	
	echo "No Funded Research Projects, Research Grants, Contracts and Research in Education records in the system for this reporting period.<br><br>";
}
else 
{	
		$ctr 			= 1;
		foreach($results as $result)
		{
			if($ctr >1)
			{
				echo "<br /><br />";
			}
			echo "<table class=\"tableListReport\" border=\"1\" cellspacing=\"0\" summary=\"Research\">
				<colgroup>
					<col class=\"delete\" />
					<col class=\"full_description\" />
					<col class=\"student_name\" />
				</colgroup>
				<thead>
					<tr>
						<td class=\"delete\" id=\"colDelete\" width=\"2%\">#</td>
						<td class=\"full_description\" id=\"colgrant_title\" width=\"49%\">Research / Grant Title</td>
						<td class=\"student_name\" id=\"coldescription\" width=\"49%\">Amount Received</td>	
					</tr>
				</thead>
			<tbody>";
			
			echo "<tr>\n";
			echo "	<td class=\"delete\" width=\"2%\">".$ctr."&nbsp;</td>\n";
			echo "	<td class=\"full_description\" width=\"49%\">".html_encode($result['grant_title'])."&nbsp;</td>";
			echo "	<td class=\"student_name\" width=\"49%\">".html_encode($result['amount_received'])."&nbsp;</td>\n";
			echo "</tr>
			<thead>
				<tr>
					<td class=\"delete\" id=\"colDelete\" width=\"2%\"></td>
					<td class=\"full_description\" id=\"colagency\" width=\"49%\"><b>Type</b></td>
					<td class=\"student_name\" id=\"colduration\" width=\"49%\"><b>Role</b></td>	
				</tr>
			</thead>";
			echo "<tr>\n";
			echo "	<td class=\"delete\" width=\"2%\"></td>\n";					
			echo "	<td class=\"full_description\" width=\"49%\">".html_encode($result['type'])."&nbsp;</td>";
			echo "	<td class=\"student_name\" width=\"49%\">".html_encode($result['role'])."&nbsp;</td>";
			echo "</tr>
			<thead>
				<tr>
					<td class=\"delete\" id=\"colDelete\" width=\"2%\"></td>
					<td class=\"full_description\" id=\"colagency\" width=\"49%\"><b>Agency</b></td>
					<td class=\"student_name\" id=\"colduration\" width=\"49%\"><b>Duration</b></td>	
				</tr>
			</thead>";
			echo "<tr>\n";
			echo "	<td class=\"delete\" width=\"2%\"></td>\n";					
			echo "	<td class=\"full_description\" width=\"49%\">".html_encode($result['agency'])."&nbsp;</td>";
			echo "	<td class=\"student_name\" width=\"49%\">". html_encode($result['start_month']) ."-".html_encode($result['start_year']) . " / "
												.((html_encode($result['end_month']) == 0 ? "N/A" : html_encode($result['end_month']) ."-".html_encode($result['end_year'])))."&nbsp;</td>";
			if($clinical_value == true) {
			echo "</tr>
			<thead>
				<tr>
					<td class=\"delete\" id=\"colDelete\" width=\"2%\"></td>
					<td class=\"full_description\" id=\"colpi\" width=\"49%\"><b>Principal Investigator</b></td>
					<td class=\"student_name\" id=\"colstatus\" width=\"49%\"><b>Status</b></td>	
				</tr>
			</thead>";
			} else {
				echo "</tr>
				<thead>
					<tr>
						<td class=\"delete\" id=\"colDelete\" width=\"2%\"></td>
						<td class=\"full_description\" id=\"colpi\" colspan=\"2\"><b>Principal Investigator</b></td>
					</tr>
				</thead>";
			}
			echo "<tr>\n";
			echo "	<td class=\"delete\"></td>\n";					
			if($clinical_value == true) {
				echo "	<td class=\"full_description\" width=\"49%\">".html_encode($result['principal_investigator'])."&nbsp;</td>";
				echo "	<td class=\"student_name\" width=\"49%\">".($result['status'] == "" ? "N/A" : html_encode($result['status']))."&nbsp;</td>";
			} else {
				echo "	<td class=\"full_description\" colspan=\"2\">".html_encode($result['principal_investigator'])."&nbsp;</td>";
			}
			echo "</tr>
			<thead>
				<tr>
					<td class=\"delete\" id=\"colDelete\"></td>
					<td class=\"full_description\" id=\"colco_investigator_list\" colspan=\"2\"><b>Co-Investigator(s)</b></td>
				</tr>
			</thead>";
			echo "<tr>\n";
			echo "	<td class=\"delete\"></td>\n";					
			echo "	<td class=\"full_description\" colspan=\"2\">".html_encode($result['co_investigator_list'])."&nbsp;</td>";
			echo "</tr>";
			$ctr++;
			
			echo "	</tbody>
			</table>";
		}
	}
?>
<br />
<h2>B. Titles of Submitted Research Projects, Research Grants, Contracts and Research in Education</h2>
<?php 
// Check the database for research
$query 	= "SELECT * 
FROM `ar_research` 
WHERE `year_reported` = ".$db->qstr($REPORT_YEAR)."
AND `proxy_id` = ".$db->qstr($proxy_id)."
AND `funding_status` = \"submitted\"
ORDER BY `type` ASC, `grant_title` ASC";	

if(!$results = $db->GetAll($query))
{	
	echo "No Submitted Research Projects, Research Grants, Contracts and Research in Education records in the system for this reporting period.<br><br>";
}
else 
{				
		$ctr 			= 1;
		foreach($results as $result)
		{
			if($ctr >1)
			{
				echo "<br /><br />";
			}
			echo "<table class=\"tableListReport\" border=\"1\" cellspacing=\"0\" summary=\"Research\">
				<colgroup>
					<col class=\"delete\" />
					<col class=\"full_description\" />
					<col class=\"student_name\" />
				</colgroup>
				<thead>
					<tr>
						<td class=\"delete\" id=\"colDelete\" width=\"2%\">#</td>
						<td class=\"full_description\" id=\"colgrant_title\" width=\"49%\">Research / Grant Title</td>
						<td class=\"student_name\" id=\"coldescription\" width=\"49%\">Amount Requested</td>	
					</tr>
				</thead>
			<tbody>";
			
			echo "<tr>\n";
			echo "	<td class=\"delete\" width=\"2%\">".$ctr."&nbsp;</td>\n";					
			echo "	<td class=\"full_description\" width=\"49%\">".html_encode($result['grant_title'])."&nbsp;</td>";
			echo "	<td class=\"student_name\" width=\"49%\">".html_encode($result['amount_received'])."&nbsp;</td>\n";
			echo "</tr>
			<thead>
				<tr>
					<td class=\"delete\" id=\"colDelete\" width=\"2%\"></td>
					<td class=\"full_description\" id=\"colagency\" width=\"49%\"><b>Type</b></td>
					<td class=\"student_name\" id=\"colduration\" width=\"49%\"><b>Role</b></td>	
				</tr>
			</thead>";
			echo "<tr>\n";
			echo "	<td class=\"delete\" width=\"2%\"></td>\n";					
			echo "	<td class=\"full_description\" width=\"49%\">".html_encode($result['type'])."&nbsp;</td>";
			echo "	<td class=\"student_name\" width=\"49%\">".html_encode($result['role'])."&nbsp;</td>";
			echo "</tr>
			<thead>
				<tr>
					<td class=\"delete\" id=\"colDelete\" width=\"2%\"></td>
					<td class=\"full_description\" id=\"colagency\" width=\"49%\"><b>Agency</b></td>
					<td class=\"student_name\" id=\"colduration\" width=\"49%\"><b>Duration</b></td>	
				</tr>
			</thead>";
			echo "<tr>\n";
			echo "	<td class=\"delete\" width=\"2%\"></td>\n";					
			echo "	<td class=\"full_description\" width=\"49%\">".html_encode($result['agency'])."&nbsp;</td>";
			echo "	<td class=\"student_name\" width=\"49%\">". html_encode($result['start_month']) ."-".html_encode($result['start_year']) . " / "
												.((html_encode($result['end_month']) == 0 ? "N/A" : html_encode($result['end_month']) ."-".html_encode($result['end_year'])))."&nbsp;</td>";
			echo "</tr>
			<thead>
				<tr>
					<td class=\"delete\" id=\"colDelete\"></td>
					<td class=\"full_description\" id=\"colprincipal_investigator\" colspan=\"2\"><b>Principal Investigator</b></td>
				</tr>
			</thead>";
			echo "<tr>\n";
			echo "	<td class=\"delete\"></td>\n";					
			echo "	<td class=\"full_description\" colspan=\"2\">".html_encode($result['principal_investigator'])."&nbsp;</td>";
			echo "</tr>
			<thead>
				<tr>
					<td class=\"delete\" id=\"colDelete\"></td>
					<td class=\"full_description\" id=\"colprincipal_investigator\" colspan=\"2\"><b>Co-Investigator(s)</b></td>
				</tr>
			</thead>";
			echo "<tr>\n";
			echo "	<td class=\"delete\"></td>\n";					
			echo "	<td class=\"full_description\" colspan=\"2\">".html_encode($result['co_investigator_list'])."&nbsp;</td>";
			echo "</tr>";
			$ctr++;
			
			echo "</tbody>
			</table>";
		}
	}
?>
<br />
<h2>C. Ongoing Unfunded Research Activities</h2>
<?php 
// Check the database for research
$query 	= "SELECT * 
FROM `ar_research` 
WHERE `year_reported` = ".$db->qstr($REPORT_YEAR)."
AND `proxy_id` = ".$db->qstr($proxy_id)."
AND `funding_status` = \"unfunded\"
ORDER BY `type` ASC, `grant_title` ASC";	

if(!$results = $db->GetAll($query))
{	
	echo "No Unfunded Research Projects, Research Grants, Contracts and Research in Education records in the system for this reporting period.<br><br>";
}
else 
{
	$ctr 			= 1;
	foreach($results as $result)
	{
		if($ctr >1)
		{
			echo "<br /><br />";
		}
		echo "<table class=\"tableListReport\" border=\"1\" cellspacing=\"0\" summary=\"Research\">
			<colgroup>
				<col class=\"delete\" />
				<col class=\"full_description\" />
				<col class=\"student_name\" />
			</colgroup>
			<thead>
				<tr>
					<td class=\"delete\" id=\"colDelete\" width=\"2%\">#</td>
					<td class=\"full_description\" id=\"colgrant_title\" width=\"49%\">Research / Grant Title</td>
					<td class=\"student_name\" id=\"coldescription\" width=\"49%\">Duration</td>	
				</tr>
			</thead>
		<tbody>";
		
		echo "<tr>\n";
		echo "	<td class=\"delete\" width=\"2%\">".$ctr."&nbsp;</td>\n";					
		echo "	<td class=\"full_description\" width=\"49%\">".html_encode($result['grant_title'])."&nbsp;</td>";
		echo "	<td class=\"student_name\" width=\"49%\">". html_encode($result['start_month']) ."-".html_encode($result['start_year']) . " / "
											.((html_encode($result['end_month']) == 0 ? "N/A" : html_encode($result['end_month']) ."-".html_encode($result['end_year'])))."&nbsp;</td>";
		echo "</tr>
		<thead>
			<tr>
				<td class=\"delete\" id=\"colDelete\" width=\"2%\"></td>
				<td class=\"full_description\" id=\"colagency\" width=\"49%\"><b>Type</b></td>
				<td class=\"student_name\" id=\"colduration\" width=\"49%\"><b>Role</b></td>	
			</tr>
		</thead>";
		echo "<tr>\n";
		echo "	<td class=\"delete\" width=\"2%\"></td>\n";					
		echo "	<td class=\"full_description\" width=\"49%\">".html_encode($result['type'])."&nbsp;</td>";
		echo "	<td class=\"student_name\" width=\"49%\">".html_encode($result['role'])."&nbsp;</td>";
		echo "</tr>
		<thead>
			<tr>
				<td class=\"delete\" id=\"colDelete\"></td>
				<td class=\"full_description\" id=\"colprincipal_investigator\" colspan=\"2\"><b>Principal Investigator</b></td>
			</tr>
		</thead>";
		echo "<tr>\n";
		echo "	<td class=\"delete\"></td>\n";					
		echo "	<td class=\"full_description\" colspan=\"2\">".html_encode($result['principal_investigator'])."&nbsp;</td>";
		echo "</tr>
		<thead>
			<tr>
				<td class=\"delete\" id=\"colDelete\"></td>
				<td class=\"full_description\" id=\"colco_investigator_list\" colspan=\"2\"><b>Co-Investigator(s)</b></td>
			</tr>
		</thead>";
		echo "<tr>\n";
		echo "	<td class=\"delete\"></td>\n";					
		echo "	<td class=\"full_description\" colspan=\"2\">".html_encode($result['co_investigator_list'])."&nbsp;</td>";
		echo "</tr>";
		$ctr++;
		
		echo "</tbody>
		</table>";
	}
}
?>
<br />
<h2>D. Peer Reviewed Publications</h2>
<?php
	$noRecOutput = 'No records in the system for this reporting period.<br>';
	$startDate = $REPORT_YEAR;
	$endDate = $REPORT_YEAR;

	$table = "ar_peer_reviewed_papers";
	if($ENTRADA_USER->getClinical() && $REPORT_YEAR > '2010') {
		$roleTable = "ar_lu_pr_roles";
	} else {
		$roleTable = "global_lu_roles";
	}
	
if($results = queryIt($proxy_id, $table, $startDate, $endDate, $db, $roleTable)) {
	display($results, $db, $typeDesc = true);
} else {
	echo $noRecOutput;
}
?>
<br />
<h2>E. Non-Peer Reviewed Publications</h2>
<?php
	$noRecOutput = 'No records in the system for this reporting period.<br>';
	$startDate = $REPORT_YEAR;
	$endDate = $REPORT_YEAR;

	$table = "ar_non_peer_reviewed_papers";
	$roleTable = "global_lu_roles";

if($results = queryIt($proxy_id, $table, $startDate, $endDate, $db, $roleTable)) {
	display($results, $db, $typeDesc = true);
} else {
	echo $noRecOutput;
}
?>
<br />
<h2>F. Books / Chapters / Monographs / Editorials</h2>
<?php
	$noRecOutput = 'No records in the system for this reporting period.<br>';
	$startDate = $REPORT_YEAR;
	$endDate = $REPORT_YEAR;

	$table = "ar_book_chapter_mono";
	$roleTable = "global_lu_roles";
	
if($results = queryIt($proxy_id, $table, $startDate, $endDate, $db, $roleTable)) {
	display($results, $db, $typeDesc = true);
} else {
	echo $noRecOutput;
}
?>
<br />
<h2>G. Poster Presentations / Technical Reports</h2>
<?php
	$noRecOutput = 'No records in the system for this reporting period.<br>';
	$startDate = $REPORT_YEAR;
	$endDate = $REPORT_YEAR;

	$table = "ar_poster_reports";
	$roleTable = "global_lu_roles";

if($results = queryIt($proxy_id, $table, $startDate, $endDate, $db, $roleTable)) {
	display($results, $db, $typeDesc = true);
} else {
	echo $noRecOutput;
}
?>
<br>
<a name=\"conference_papers\"></a><h2>H. Invited Lectures / Conference Papers</h2>
<br>
<?php 
	// Check the database for research
	$query 	= "SELECT * 
	FROM `ar_conference_papers` 
	WHERE `year_reported` = ".$db->qstr($REPORT_YEAR)."
	AND `proxy_id` = '$proxy_id'
	ORDER BY `year_reported` ASC, `lectures_papers_list` ASC, `status` ASC";
	
	
	if(!$results = $db->GetAll($query))
	{	
		echo "No Conference Papers in the system for this reporting period.<br><br>";
	}
	else 
	{
	?>
		<table class="tableListReport" border="1" cellspacing="0" summary="Education">
		<colgroup>
			<col class="delete" />
			<col class="student_name" />
			<col class="small_numbers" />
			<col class="small_numbers" />
			<col class="assigned" />
		</colgroup>
		<thead>
			<tr>
				<td class="delete" id="colDelete">#</td>
				<td class="student_name" id="collectures_papers_list">Invited Lectures / Conference Papers</td>
				<td class="small_numbers" id="colstatus">Status</td>
				<td class="course_name" id="colinstitution">Institution</td>
				<td class="course_name" id="collocation">Location</td>
				<td class="small_numbers" id="coltype">Type</td>
				<td class="assigned" id="colyear_reported">Year</td>
			</tr>
		</thead>
		<tbody>
			<?php
			$modified	= 0;
			//$rid		= $limit_parameter;
			$ctr 			= 1;
			foreach($results as $result) {	
					$url			= ENTRADA_URL."/research?section=edit_conference_papers&rid=".$result["conference_papers_id"];
					echo "<tr id=\"event-".$result["conference_papers_id"]."\" class=\"event".(($is_modified) ? " modified" : "")."\">\n";
					echo "	<td class=\"delete\">".$ctr."&nbsp;</td>\n";					
					echo "	<td class=\"student_name\">".html_encode($result["lectures_papers_list"])."&nbsp;</td>\n";
					echo "	<td class=\"small_numbers\">".html_encode($result["status"])."&nbsp;</td>\n";
					echo "	<td class=\"course_name\">".html_encode($result["institution"])."&nbsp;</td>\n";
					if($REPORT_YEAR < '2011') {
						echo "	<td class=\"small_numbers\">".html_encode($result["location"])."&nbsp;</td>\n";
					} else {
						echo "	<td class=\"small_numbers\">".html_encode($result["city"] . ", " . $result["prov_state"])."&nbsp;</td>\n";
					}
					echo "	<td class=\"small_numbers\">".html_encode($result["type"])."&nbsp;</td>\n";	
					echo "	<td class=\"assigned\">".html_encode($result['year_reported'])."&nbsp;</td>\n";
					echo "</tr>\n";
					$ctr++;
				
				//$rid++;
			}
			?>
		</tbody>
		</table>
		<br>
	<?php 
	}
?>
<br />
<h2>I. Other Scholarly Activity</h2>
<?php 
// Check the database for research
$query 	= "SELECT * 
FROM `ar_scholarly_activity` 
WHERE `year_reported` = ".$db->qstr($REPORT_YEAR)."
AND `proxy_id` = ".$db->qstr($proxy_id)."
ORDER BY `scholarly_activity_type` ASC";

if(!$results = $db->GetAll($query))
{	
	echo "No Other Scholarly Activity records in the system for this reporting period.<br><br>";
}
else 
{
?>	
	<table class="tableListReport" border="1" cellspacing="0" summary="Other Scholarly Activity">
		<colgroup>
			<col class="delete" />
			<col class="course_name" />
			<col class="full_description" />
		</colgroup>
		<thead>
			<tr>
				<td class="delete" id="colDelete" width="2%">#</td>
				<td class="course_name" id="coltype" width="23%">Activity Type</td>
				<td class="full_description" id="coldescription" width="50%">Description</td>
				<td class="full_description" id="coldescription" width="25%">Category</td>
			</tr>
		</thead>
	<tbody>	
	<?php				
		$ctr 			= 1;
		foreach($results as $result)
		{
			echo "<tr>\n";
			echo "	<td class=\"delete\" width=\"2%\">".$ctr."&nbsp;</td>\n";					
			echo "	<td class=\"course_name\" width=\"23%\">".html_encode($result['scholarly_activity_type'])."&nbsp;</td>";
				echo "	<td class=\"full_description\" width=\"50%\">".html_encode($result['description'])."&nbsp;</td>\n";
				echo "	<td class=\"full_description\" width=\"25%\">".html_encode($result['location'])."&nbsp;</td>\n";
			echo "</tr>";
			$ctr++;
		}
		?>
	</tbody>
	</table>
	<?php 
		}
	?>
	<br />
<h2>J. Patents, Agreements and Licenses</h2>
<?php 
// Check the database for research
$query 	= "SELECT * 
FROM `ar_patent_activity` 
WHERE `year_reported` = ".$db->qstr($REPORT_YEAR)."
AND `proxy_id` = ".$db->qstr($proxy_id)."
ORDER BY `patent_activity_type` ASC";

if(!$results = $db->GetAll($query))
{	
	echo "No Patent records in the system for this reporting period.<br><br>";
}
else 
{
?>	
	<table class="tableListReport" border="1" cellspacing="0" summary="Patents">
		<colgroup>
			<col class="delete" />
			<col class="course_name" />
			<col class="full_description" />
		</colgroup>
		<thead>
			<tr>
				<td class="delete" id="colDelete" width="2%">#</td>
				<td class="course_name" id="coltype" width="23%">Activity Type</td>				
				<td class="full_description" id="coldescription" width="75%">Description</td>
			</tr>
		</thead>
	<tbody>	
	<?php				
		$ctr 			= 1;
		foreach($results as $result)
		{
			echo "<tr>\n";
			echo "	<td class=\"delete\" width=\"2%\">".$ctr."&nbsp;</td>\n";					
			echo "	<td class=\"course_name\" width=\"23%\">".html_encode($result['patent_activity_type'])."&nbsp;</td>";
			echo "	<td class=\"full_description\" width=\"75%\">".html_encode($result['description'])."&nbsp;</td>\n";
			echo "</tr>";
			$ctr++;
		}
		?>
	</tbody>
	</table>
	<?php 
		}
		echo '<!-- FOOTER LEFT "$CHAPTER" --> <!-- FOOTER CENTER "" --> <!-- FOOTER RIGHT "$PAGE" --><!-- PAGE BREAK --><br><br>';
	if($clinical_value == true)
	{
		?>
		<h1>Section III - Clinical</h1>
		<h2>A. Clinical Activity</h2>
		<?php 
		// Check the database for clinical
		$query 	= "SELECT * 
		FROM `ar_clinical_activity` 
		WHERE `year_reported` = ".$db->qstr($REPORT_YEAR)."
		AND `proxy_id` = ".$db->qstr($proxy_id)."
		ORDER BY `site` ASC";	
		
		if(!$results = $db->GetAll($query))
		{	
			echo "No Clinical Activity records in the system for this reporting period.<br><br>";
		}
		else 
		{
		?>	
			<table class="tableListReport" border="1" cellspacing="0" summary="Clinical">
				<colgroup>
					<col class="delete" />
					<col class="student_name" />
					<col class="full_description" />
					<col class="average_hours" />
				</colgroup>
				<thead>
					<tr>
						<td class="delete" id="colDelete" width="2%">#</td>
						<td class="student_name" id="colsite" width="23%">Site / Site Desc</td>
						<td class="full_description" id="coldescription" width="60%">Description</td>
						<td class="average_hours" id="colaverage_hours" width="15%">Avg. Hrs/Week</td>	
					</tr>
				</thead>
			<tbody>	
			<?php				
				$ctr 			= 1;
				foreach($results as $result)
				{
					echo "<tr>\n";
					echo "	<td class=\"delete\" width=\"2%\">".$ctr."&nbsp;</td>\n";					
					echo "	<td class=\"student_name\" width=\"23%\">".html_encode($result['site']);
					if(isset($result['site_description']) && trim($result['site_description']) != '')
					{
						echo ": " .html_encode($result['site_description']);
					}
					echo "</td>\n";	
					echo "	<td class=\"full_description\" width=\"60%\">".html_encode($result['description'])."&nbsp;</td>";
					echo "	<td class=\"average_hours\" width=\"15%\">".html_encode($result['average_hours'])."&nbsp;</td>\n";
					echo "</tr>";
					$ctr++;
				}
				?>
			</tbody>
			</table>
		<?php 
			}
		?>
		<br />
		<h2>B. Ward Supervision</h2>
		<?php 
		// Check the database for clinical
		$query 	= "SELECT * 
		FROM `ar_ward_supervision` 
		WHERE `year_reported` = ".$db->qstr($REPORT_YEAR)."
		AND `proxy_id` = ".$db->qstr($proxy_id)."
		ORDER BY `service` ASC";	
		
		if(!$results = $db->GetAll($query))
		{	
			echo "No Ward Supervision records in the system for this reporting period.<br><br>";
		}
		else 
		{
		?>	
			<table class="tableListReport" border="1" cellspacing="0" summary="Clinical">
				<colgroup>
					<col class="delete" />
					<col class="student_name" />
					<col class="average_hours" />
					<col class="average_hours" />
					<col class="average_hours" />
				</colgroup>
				<thead>
					<tr>
						<td class="delete" id="colDelete" width="2%">#</td>
						<td class="student_name" id="colsite" width="44%">Service</td>
						<td class="average_hours" id="colaverage_patients" width="18%">Avg. # Patients</td>
						<td class="average_hours" id="colmonths" width="18%">Avg. # Months</td>	
						<td class="average_hours" id="colaverage_clerks" width="18%">Avg. # Clerks / Residents</td>	
					</tr>
				</thead>
			<tbody>	
			<?php				
				$ctr 			= 1;
				foreach($results as $result)
				{
					echo "<tr>\n";
					echo "	<td class=\"delete\" width=\"2%\">".$ctr."&nbsp;</td>\n";					
					echo "	<td class=\"student_name\" width=\"44%\">".html_encode($result['service'])."&nbsp;</td>\n";	
					echo "	<td class=\"average_hours\" width=\"18%\">".html_encode($result['average_patients'])."&nbsp;</td>";
					echo "	<td class=\"average_hours\" width=\"18%\">".html_encode($result['months'])."&nbsp;</td>\n";
					echo "	<td class=\"average_hours\" width=\"18%\">".html_encode($result['average_clerks'])."&nbsp;</td>\n";
					echo "</tr>";
					$ctr++;
				}
				?>
			</tbody>
			</table>
		<?php 
			}
		?>
		<br />
		<h2>C. Clinics</h2>
		<?php 
		// Check the database for clinical
		$query 	= "SELECT * 
		FROM `ar_clinics` 
		WHERE `year_reported` = ".$db->qstr($REPORT_YEAR)."
		AND `proxy_id` = ".$db->qstr($proxy_id)."
		ORDER BY `clinic` ASC";	
		
		if(!$results = $db->GetAll($query))
		{	
			echo "No Clinics in the system for this reporting period.<br><br>";
		}
		else 
		{
		?>	
			<table class="tableListReport" border="1" cellspacing="0" summary="Clinical">
				<colgroup>
					<col class="delete" />
					<col class="student_name" />
					<col class="average_hours" />
					<col class="average_hours" />
					<col class="average_hours" />
					<col class="average_hours" />
					<col class="average_hours" />
				</colgroup>
				<thead>
					<tr>
						<td class="delete" id="colDelete" width="2%">#</td>
						<td class="student_name" id="colclinic" width="13%">Clinic</td>
						<td class="average_hours" id="colpatients" width="17%">Approx. # Patients / Clinic</td>
						<td class="average_hours" id="colhalf_days" width="17%"># Half Days / Week</td>	
						<td class="average_hours" id="colnew_repeat" width="17%">New / Repeat Patients Ratio</td>	
						<td class="average_hours" id="colweeks" width="17%"># Weeks / Year</td>	
						<td class="average_hours" id="colaverage_clerks" width="17%">Avg. # Clerks / Residents</td>	
					</tr>
				</thead>
			<tbody>	
			<?php				
				$ctr 			= 1;
				foreach($results as $result)
				{
					echo "<tr>\n";
					echo "	<td class=\"delete\" width=\"2%\">".$ctr."&nbsp;</td>\n";					
					echo "	<td class=\"student_name\" width=\"13%\">".html_encode($result['clinic'])."&nbsp;</td>\n";	
					echo "	<td class=\"average_hours\" width=\"17%\">".html_encode($result['patients'])."&nbsp;</td>";
					echo "	<td class=\"average_hours\" width=\"17%\">".html_encode($result['half_days'])."&nbsp;</td>\n";
					echo "	<td class=\"average_hours\" width=\"17%\">".html_encode($result['new_repeat'])."&nbsp;</td>\n";
					echo "	<td class=\"average_hours\" width=\"17%\">".html_encode($result['weeks'])."&nbsp;</td>\n";
					echo "	<td class=\"average_hours\" width=\"17%\">".html_encode($result['average_clerks'])."&nbsp;</td>\n";
					echo "</tr>";
					$ctr++;
				}
				?>
			</tbody>
			</table>
		<?php 
			}
		?>
		<br />
		<h2>D. In-Hospital Consultations</h2>
		<?php 
		// Check the database for clinical
		$query 	= "SELECT * 
		FROM `ar_consults` 
		WHERE `year_reported` = ".$db->qstr($REPORT_YEAR)."
		AND `proxy_id` = ".$db->qstr($proxy_id)."
		ORDER BY `activity` ASC";	
		
		if(!$results = $db->GetAll($query))
		{	
			echo "No Consultation records in the system for this reporting period.<br><br>";
		}
		else 
		{
		?>	
			<table class="tableListReport" border="1" cellspacing="0" summary="Clinical">
				<colgroup>
					<col class="delete" />
					<col class="student_name" />
					<col class="student_name" />
					<col class="average_hours" />
					<col class="average_hours" />
				</colgroup>
				<thead>
					<tr>
						<td class="delete" id="colDelete" width="2%">#</td>
						<td class="student_name" id="colactivity" width="23%">Activity</td>
						<td class="student_name" id="colsite" width="40%">Site / Description</td>
						<td class="average_hours" id="colmonths" width="17%"># Months / Year</td>	
						<td class="average_hours" id="colaverage_consults" width="18%">Avg. # Consultations / Month</td>	
					</tr>
				</thead>
			<tbody>	
			<?php				
				$ctr 			= 1;
				foreach($results as $result)
				{
					echo "<tr>\n";
					echo "	<td class=\"delete\" width=\"2%\">".$ctr."&nbsp;</td>\n";					
					echo "	<td class=\"student_name\" width=\"23%\">".html_encode($result['activity'])."&nbsp;</td>\n";	
					echo "	<td class=\"student_name\" width=\"40%\">".html_encode($result['site']);
					if(isset($result['site_description']) && trim($result['site_description']) != '')
					{
						echo ": " .html_encode($result['site_description']);
					}
					echo "</td>\n";
					echo "	<td class=\"average_hours\" width=\"17%\">".html_encode($result['months'])."&nbsp;</td>\n";
					echo "	<td class=\"average_hours\" width=\"18%\">".html_encode($result['average_consults'])."&nbsp;</td>\n";
					echo "</tr>";
					$ctr++;
				}
				?>
			</tbody>
			</table>
		<?php 
			}
		?>
		<br />
		<h2>E. On-Call Responsibility</h2>
		<?php 
		// Check the database for clinical
		$query 	= "SELECT * 
		FROM `ar_on_call` 
		WHERE `year_reported` = ".$db->qstr($REPORT_YEAR)."
		AND `proxy_id` = ".$db->qstr($proxy_id)."
		ORDER BY `site` ASC";	
		
		if(!$results = $db->GetAll($query))
		{	
			echo "No On-Call Responsibility records in the system for this reporting period.<br><br>";
		}
		else 
		{
		?>	
			<table class="tableListReport" border="1" cellspacing="0" summary="Clinical">
				<colgroup>
					<col class="delete" />
					<col class="student_name" />
					<col class="average_hours" />
					<col class="full_description" />
				</colgroup>
				<thead>
					<tr>
						<td class="delete" id="colDelete" width="2%">#</td>
						<td class="student_name" id="colsite" width="50%">Site / Description</td>
						<td class="average_hours" id="colfrequency" width="18%">Frequency</td>
						<td class="full_description" id="colspecial_features" width="30%">Special Features</td>	
					</tr>
				</thead>
			<tbody>	
			<?php				
				$ctr 			= 1;
				foreach($results as $result)
				{
					echo "<tr>\n";
					echo "	<td class=\"delete\" width=\"2%\">".$ctr."&nbsp;</td>\n";
					echo "	<td class=\"student_name\" width=\"50%\">".html_encode($result['site']);
					if(isset($result['site_description']) && trim($result['site_description']) != '')
					{
						echo ": " .html_encode($result['site_description']);
					}
					echo "</td>\n";
					echo "	<td class=\"average_hours\" width=\"18%\">".html_encode($result['frequency'])."&nbsp;</td>\n";
					echo "	<td class=\"full_description\" width=\"30%\">".html_encode($result['special_features'])."&nbsp;</td>\n";
					echo "</tr>";
					$ctr++;
				}
				?>
			</tbody>
			</table>
		<?php 
			}
		?>
		<br />
		<h2>F. Procedures</h2>
		<?php 
		// Check the database for clinical
		$query 	= "SELECT * 
		FROM `ar_procedures` 
		WHERE `year_reported` = ".$db->qstr($REPORT_YEAR)."
		AND `proxy_id` = ".$db->qstr($proxy_id)."
		ORDER BY `site` ASC";	
		
		if(!$results = $db->GetAll($query))
		{	
			echo "No Procedure records in the system for this reporting period.<br><br>";
		}
		else 
		{
		?>	
			<table class="tableListReport" border="1" cellspacing="0" summary="Clinical">
				<colgroup>
					<col class="delete" />
					<col class="student_name" />
					<col class="average_hours" />
					<col class="full_description" />
				</colgroup>
				<thead>
					<tr>
						<td class="delete" id="colDelete" width="2%">#</td>
						<td class="student_name" id="colsite" width="50%">Site / Description</td>
						<td class="average_hours" id="colaverage_hours" width="18%">Avg. Hrs/Week</td>
						<td class="full_description" id="colspecial_features" width="30%">Special Features</td>	
					</tr>
				</thead>
			<tbody>	
			<?php				
				$ctr 			= 1;
				foreach($results as $result)
				{
					echo "<tr>\n";
					echo "	<td class=\"delete\" width=\"2%\">".$ctr."&nbsp;</td>\n";
					echo "	<td class=\"student_name\" width=\"50%\">".html_encode($result['site']);
					if(isset($result['site_description']) && trim($result['site_description']) != '')
					{
						echo ": " .html_encode($result['site_description']);
					}
					echo "</td>\n";
					echo "	<td class=\"average_hours\" width=\"18%\">".html_encode($result['average_hours'])."&nbsp;</td>\n";
					echo "	<td class=\"full_description\" width=\"30%\">".html_encode($result['special_features'])."&nbsp;</td>\n";
					echo "</tr>";
					$ctr++;
				}
				?>
			</tbody>
			</table>
		<?php 
			}
		?>
		<br />
		<h2>G. Other Professional Activity</h2>
		<?php 
		// Check the database for clinical
		$query 	= "SELECT * 
		FROM `ar_other_activity` 
		WHERE `year_reported` = ".$db->qstr($REPORT_YEAR)."
		AND `proxy_id` = ".$db->qstr($proxy_id)."
		ORDER BY `site` ASC";	
		
		if(!$results = $db->GetAll($query))
		{	
			echo "No Other Professional Activity records in the system for this reporting period.<br><br>";
		}
		else 
		{
		?>	
			<table class="tableListReport" border="1" cellspacing="0" summary="Clinical">
				<colgroup>
					<col class="delete" />
					<col class="student_name" />
					<col class="average_hours" />
					<col class="full_description" />
				</colgroup>
				<thead>
					<tr>
						<td class="delete" id="colDelete" width="2%">#</td>
						<td class="student_name" id="colsite" width="50%">Site / Description</td>
						<td class="average_hours" id="colaverage_hours" width="18%">Avg. Hrs/Week</td>
						<td class="full_description" id="colspecial_features" width="30%">Special Features</td>	
					</tr>
				</thead>
			<tbody>	
			<?php				
				$ctr 			= 1;
				foreach($results as $result)
				{
					echo "<tr>\n";
					echo "	<td class=\"delete\" width=\"2%\">".$ctr."&nbsp;</td>\n";
					echo "	<td class=\"student_name\" width=\"50%\">".html_encode($result['site']);
					if(isset($result['site_description']) && trim($result['site_description']) != '')
					{
						echo ": " .html_encode($result['site_description']);
					}
					echo "</td>\n";
					echo "	<td class=\"average_hours\" width=\"18%\">".html_encode($result['average_hours'])."&nbsp;</td>\n";
					echo "	<td class=\"full_description\" width=\"30%\">".html_encode($result['special_features'])."&nbsp;</td>\n";
					echo "</tr>";
					$ctr++;
				}
				?>
			</tbody>
			</table>
		<?php 
			}
		?>
		<br />
		<h2>H. Innovation in Clinical Activity</h2>
		<?php 
		// Check the database for clinical
		$query 	= "SELECT * 
		FROM `ar_clinical_innovation` 
		WHERE `year_reported` = ".$db->qstr($REPORT_YEAR)."
		AND `proxy_id` = ".$db->qstr($proxy_id)."
		ORDER BY `description` ASC";	
		
		if(!$results = $db->GetAll($query))
		{	
			echo "No Clinical Innovation records in the system for this reporting period.<br><br>";
		}
		else 
		{
		?>	
			<table class="tableListReport" border="1" cellspacing="0" summary="Clinical">
				<colgroup>
					<col class="delete" />
					<col class="full_description" />
				</colgroup>
				<thead>
					<tr>
						<td class="delete" id="colDelete" width="2%">#</td>
						<td class="full_description" id="colsdescription" width="98%">Description</td>	
					</tr>
				</thead>
			<tbody>	
			<?php				
				$ctr 			= 1;
				foreach($results as $result)
				{
					echo "<tr>\n";
					echo "	<td class=\"delete\" width=\"2%\">".$ctr."&nbsp;</td>\n";
					echo "	<td class=\"full_description\" width=\"98%\">".html_encode($result['description'])."&nbsp;</td>\n";
					echo "</tr>";
					$ctr++;
				}
				?>
			</tbody>
			</table>
		<?php 
			}
		?>
		<br />
		<?php 
			echo '<!-- FOOTER LEFT "$CHAPTER" --> <!-- FOOTER CENTER "" --> <!-- FOOTER RIGHT "$PAGE" --><!-- PAGE BREAK --><br><br>';
	}
	?>
	<h1>Section <?php echo ($clinical_value == true ? "IV" : "III"); ?> - Service</h1>
	<h2>A. Service Internal to Queen's University</h2>
	<?php 
	// Check the database for academic
	$query 	= "SELECT * 
	FROM `ar_internal_contributions` 
	WHERE `year_reported` = ".$db->qstr($REPORT_YEAR)."
	AND `proxy_id` = ".$db->qstr($proxy_id)."
	ORDER BY `type` ASC, `activity_type` ASC";	
	
	$previousType = '';
	
	if(!$results = $db->GetAll($query))
	{	
		echo "No Service Internal to Queen's University recorded in the system for this reporting period.<br><br>";
	}
	else 
	{
		$ctr 				= 1;
		$totalCtr 			= 1;
		
		foreach($results as $result)
		{
			if($previousType != $result['type'])
			{
				if($ctr != 1)
				{
					echo "</tbody>
						</table>";
					$totalCtr++;
					$ctr = 1;
				}
				if($totalCtr > 1)
				{
					echo "<br />";
				}
				echo "<b>" . $totalCtr. ") " .$result['type'] . "</b><br />";
				?>
				<table class="tableListReport" border="1" cellspacing="0" summary="Service">
					<colgroup>
						<col class="delete" />
						<col class="student_name" />
						<col class="student_name" />
						<col class="full_description" />
						<col class="average_hours" />
						<col class="student_name" />
					</colgroup>
					<thead>
						<tr>
							<td class="delete" id="colDelete" width="2%">#</td>
							<td class="student_name" id="colactivity_type" width="23%">Activity / Description</td>
							<td class="student_name" id="colrole" width="20%">Role / Description</td>
							<td class="full_description" id="coldescription" width="30%">Description of Involvment</td>	
							<td class="average_hours" id="coltime_commitment" width="12%">Commitment</td>	
							<td class="student_name" id="<?php echo ($clinical_value == true ? "colduration" : "colmeetings"); ?>" width="13%"><?php echo ($clinical_value == true ? "Duration" : "% Meetings Attended"); ?></td>
						</tr>
					</thead>
				<tbody>	
			<?php
			}
			echo "<tr>\n";
			echo "	<td class=\"delete\" width=\"2%\">".$ctr."&nbsp;</td>\n";					
			echo "	<td class=\"student_name\" width=\"23%\">".html_encode($result['activity_type']);
			if(isset($result['activity_type_description']) && trim($result['activity_type_description']) != '')
			{
				echo ": " .html_encode($result['activity_type_description']);
			}
			echo "</td>\n";
			echo "	<td class=\"student_name\" width=\"20%\">".html_encode($result['role']);
			if(isset($result['role_description']) && trim($result['role_description']) != '')
			{
				echo ": " .html_encode($result['role_description']);
			}
			echo "</td>\n";
			echo "	<td class=\"full_description\" width=\"30%\">".html_encode($result['description'])."&nbsp;</td>\n";
			echo "	<td class=\"average_hours\" width=\"12%\">".(html_encode($result['commitment_type']) != "variable" ? html_encode($result['time_commitment'])." Hours / " .html_encode($result['commitment_type']) : "")."&nbsp;</td>\n";
			echo "  <td class=\"student_name\" width=\"13%\">";
			if($clinical_value == true) {
				echo html_encode($result['start_month']) ."-".html_encode($result['start_year']) . " / "
				.((html_encode($result['end_month']) == 0 ? "N/A" : html_encode($result['end_month']) ."-".html_encode($result['end_year'])));
			} else {
				echo html_encode($result['meetings_attended']);
			}
			echo "</td>";
			echo "</tr>";
			$ctr++;
			$previousType = $result['type'];
		}
		?>
		</tbody>
		</table>
	<?php 
		}
	?>
	<br />
	<h2>B. Service External to Queen's</h2>
	<?php 
	// Check the database for academic
	$query 	= "SELECT * 
	FROM `ar_external_contributions` 
	WHERE `year_reported` = ".$db->qstr($REPORT_YEAR)."
	AND `proxy_id` = ".$db->qstr($proxy_id)."
	ORDER BY `organisation` ASC";	
	
	if(!$results = $db->GetAll($query))
	{	
		echo "No Service External to Queen's recorded in the system for this reporting period.<br><br>";
	}
	else 
	{
	?>	
		<table class="tableListReport" border="1" cellspacing="0" summary="Service">
			<colgroup>
				<col class="delete" />
				<col class="organisation" />
				<col class="city_country" />
				<col class="description" />
				<col class="average_hours" />
			</colgroup>
			<thead>
				<tr>
					<td class="delete" id="colDelete" width="2%">#</td>
					<td class="organisation" id="colsite" width="23%">Name of Organisation</td>
					<td class="city_country" id="colaverage_hours" width="15%">Location</td>
					<td class="description" id="colspecial_features" width="50%">Description of Involvement</td>	
					<td class="average_hours" id="colaverage_hours" width="10%">Days/Year</td>
				</tr>
			</thead>
		<tbody>	
		<?php				
			$ctr 			= 1;
			foreach($results as $result)
			{
				echo "<tr>\n";
				echo "	<td class=\"delete\" width=\"2%\">".$ctr."&nbsp;</td>\n";
				echo "	<td class=\"organisation\" width=\"23%\">".html_encode($result['organisation']);
				if($REPORT_YEAR < '2011') {
					echo "	<td class=\"city_country\">".html_encode($result["city_country"])."&nbsp;</td>\n";
				} else {
					echo "	<td class=\"city_country\">".html_encode($result["city"] . ", " . $result["prov_state"])."&nbsp;</td>\n";
				}
				echo "	<td class=\"description\" width=\"50%\">".html_encode($result['description'])."&nbsp;</td>\n";
				echo "	<td class=\"average_hours\" width=\"10%\">".html_encode($result['days_of_year'])."&nbsp;</td>\n";
				echo "</tr>";
				$ctr++;
			}
			?>
		</tbody>
		</table>
	<?php 
		}
		echo '<!-- FOOTER LEFT "$CHAPTER" --> <!-- FOOTER CENTER "" --> <!-- FOOTER RIGHT "$PAGE" --><!-- PAGE BREAK --><br><br>';
	?>
	<br />
	<h1>Section <?php echo ($clinical_value == true ? "V" : "IV"); ?> - Self Education/Faculty Development</h1>
	<h2>A. Scientific Meetings/Courses Attended and Other Self-Education Activities</h2>
	<?php 
	// Check the database for academic
	$query 	= "SELECT * 
	FROM `ar_self_education` 
	WHERE `year_reported` = ".$db->qstr($REPORT_YEAR)."
	AND `proxy_id` = ".$db->qstr($proxy_id)."
	ORDER BY `description` ASC, `activity_type` ASC";	
	
	if(!$results = $db->GetAll($query))
	{	
		echo "No Scientific Meetings/Courses or Other Self-Education Activity records in the system for this reporting period.<br><br>";
	}
	else 
	{
		$ctr 				= 1;
		?>
		<table class="tableListReport" border="1" cellspacing="0" summary="Scientific Meetings/Courses and Other Self-Education Activities">
					<colgroup>
						<col class="delete" />
						<col class="full_description" />
						<col class="student_name" />
						<col class="student_name" />
						<col class="student_name" />
					</colgroup>
					<thead>
						<tr>
							<td class="delete" id="colDelete" width="2%">#</td>
							<td class="full_description" id="coldescription" width="48%">Description of Activity</td>	
							<td class="student_name" id="colactivity_type" width="15%">Activity</td>
							<td class="student_name" id="colinstitution" width="25%">Organising Unit / Institution</td>
							<td class="student_name" id="colduration" width="10%">Duration</td>
						</tr>
					</thead>
				<tbody>	
		<?php
		foreach($results as $result)
		{	
			echo "<tr>\n";
			echo "	<td class=\"delete\" width=\"2%\">".$ctr."&nbsp;</td>\n";					
			echo "	<td class=\"full_description\" width=\"48%\">".html_encode($result['description'])."&nbsp;</td>\n";
			echo "	<td class=\"student_name\" width=\"15%\">".html_encode($result['activity_type'])."&nbsp;</td>\n";
			echo "	<td class=\"student_name\" width=\"25%\">".html_encode($result['institution'])."&nbsp;</td>\n";
			echo "	<td class=\"student_name\" width=\"10%\">". html_encode($result['start_month']) ."-".html_encode($result['start_year']) . " / "
													 .((html_encode($result['end_month']) == 0 ? "N/A" : html_encode($result['end_month']) ."-".html_encode($result['end_year'])))."&nbsp;</td>";
			echo "</tr>";
			$ctr++;
		}
		?>
		</tbody>
		</table>
	<?php 
		}
		echo '<!-- FOOTER LEFT "$CHAPTER" --> <!-- FOOTER CENTER "" --> <!-- FOOTER RIGHT "$PAGE" --><!-- PAGE BREAK --><br><br>';
	?>
	<br />
	<h1>Section <?php echo ($clinical_value == true ? "VI" : "V"); ?> - Prizes, Honours and Awards</h1>
	<h2>A. Prizes, Honours and Awards</h2>
	<?php 
	// Check the database for academic
	$query 	= "SELECT * 
	FROM `ar_prizes` 
	WHERE `year_reported` = ".$db->qstr($REPORT_YEAR)."
	AND `proxy_id` = ".$db->qstr($proxy_id)."
	ORDER BY `category` ASC, `prize_type` ASC";	
	
	if(!$results = $db->GetAll($query))
	{	
		echo "No Prizes, Honours and Awards records in the system for this reporting period.<br><br>";
	}
	else 
	{
		$ctr 				= 1;
		?>
		<table class="tableListReport" border="1" cellspacing="0" summary="Scientific Meetings/Courses and Other Self-Education Activities">
					<colgroup>
						<col class="delete" />
						<col class="student_name" />
						<col class="student_name" />
						<col class="full_description" />
					</colgroup>
					<thead>
						<tr>
							<td class="delete" id="colDelete" width="2%">#</td>
							<td class="student_name" id="colcategory" width="18%">Category</td>
							<td class="student_name" id="colprize_type" width="20%">Type of Award</td>
							<td class="full_description" id="coldescription" width="60%">Description</td>
						</tr>
					</thead>
				<tbody>	
		<?php
		foreach($results as $result)
		{	
			echo "<tr>\n";
			echo "	<td class=\"delete\" width=\"2%\">".$ctr."&nbsp;</td>\n";
			echo "	<td class=\"student_name\" width=\"18%\">".html_encode($result['category'])."&nbsp;</td>\n";
			echo "	<td class=\"student_name\" width=\"20%\">".html_encode($result['prize_type'])."&nbsp;</td>\n";
			echo "	<td class=\"full_description\" width=\"60%\">".html_encode($result['description'])."&nbsp;</td>\n";
			echo "</tr>";
			$ctr++;
		}
		?>
		</tbody>
		</table>
	<?php 
		}
		echo '<!-- FOOTER LEFT "$CHAPTER" --> <!-- FOOTER CENTER "" --> <!-- FOOTER RIGHT "$PAGE" --><!-- PAGE BREAK --><br><br>';
	?>
	<br />
	<h1>Section <?php echo ($clinical_value == true ? "VII" : "VI"); ?> - Activity Profile</h1>
	<h2>1.A. Percentage of your total professional time spent on the following activities during this reporting period.</h2>
	<?php
	// Check the database for profile information
	$query 	= "SELECT * 
	FROM `ar_profile` 
	WHERE `year_reported` = ".$db->qstr($REPORT_YEAR)."
	AND `proxy_id` = ".$db->qstr($proxy_id);	
	
	if(!$result = $db->GetRow($query))
	{	
		echo "Error: You must have a profile record for this reporting period.<br><br>";
	}
	else 
	{
		if($clinical_value == true)
		{	
			?>
			<table class="tableListReport" border="1" cellspacing="0" summary="Activity Profile">
						<colgroup>
							<col class="full_description" />
							<col class="average_hours" />
						</colgroup>
						<thead>
							<tr>
								<td class="full_description" id="coldescription" width="75%">Description</td>
								<td class="average_hours" id="colpercentage" width="25%">%</td>
							</tr>
						</thead>
					<tbody>	
				<?php
					echo "<tr>\n";
					echo "	<td class=\"full_description\" width=\"75%\">Education Outside Clinical Setting</td>\n";
					echo "	<td class=\"average_hours\" width=\"25%\">".html_encode($result['education'])."&nbsp;</td>\n";
					echo "</tr>";
					echo "<tr>\n";
					echo "	<td class=\"full_description\" width=\"75%\">Scholarship / Research</td>\n";
					echo "	<td class=\"average_hours\" width=\"25%\">".html_encode($result['research'])."&nbsp;</td>\n";
					echo "</tr>";
					echo "<tr>\n";
					echo "	<td class=\"full_description\" width=\"75%\">Non-Teaching Clinical Activity</td>\n";
					echo "	<td class=\"average_hours\" width=\"25%\">".html_encode($result['clinical'])."&nbsp;</td>\n";
					echo "</tr>";
					echo "<tr>\n";
					echo "	<td class=\"full_description\" width=\"75%\">Combined Clinical / Education Activity</td>\n";
					echo "	<td class=\"average_hours\" width=\"25%\">".html_encode($result['combined'])."&nbsp;</td>\n";
					echo "</tr>";
					echo "<tr>\n";
					echo "	<td class=\"full_description\" width=\"75%\">Service / Administration</td>\n";
					echo "	<td class=\"average_hours\" width=\"25%\">".html_encode($result['service'])."&nbsp;</td>\n";
					echo "</tr>";
					echo "<tr>\n";
					echo "	<td class=\"full_description\" width=\"75%\">Total</td>\n";
					echo "	<td class=\"average_hours\" width=\"25%\">".html_encode($result['total'])."&nbsp;</td>\n";
					echo "</tr>";
				?>
			</tbody>
			</table>
		<?php 
		} else {
			?>
			<table class="tableListReport" border="1" cellspacing="0" summary="Activity Profile">
						<colgroup>
							<col class="full_description" />
							<col class="average_hours" />
						</colgroup>
						<thead>
							<tr>
								<td class="full_description" id="coldescription" width="75%">Description</td>
								<td class="average_hours" id="colpercentage" width="25%">%</td>
							</tr>
						</thead>
					<tbody>	
				<?php
					echo "<tr>\n";
					echo "	<td class=\"full_description\" width=\"75%\">Education (%)</td>\n";
					echo "	<td class=\"average_hours\" width=\"25%\">".html_encode($result['education'])."&nbsp;</td>\n";
					echo "</tr>";
					echo "<tr>\n";
					echo "	<td class=\"full_description\" width=\"75%\">Scholarship / Research (%)</td>\n";
					echo "	<td class=\"average_hours\" width=\"25%\">".html_encode($result['research'])."&nbsp;</td>\n";
					echo "</tr>";
					echo "<tr>\n";
					echo "	<td class=\"full_description\" width=\"75%\">Service / Administration (%)</td>\n";
					echo "	<td class=\"average_hours\" width=\"25%\">".html_encode($result['service'])."&nbsp;</td>\n";
					echo "</tr>";
					echo "<tr>\n";
					echo "	<td class=\"full_description\" width=\"75%\">Total (%)</td>\n";
					echo "	<td class=\"average_hours\" width=\"25%\">".html_encode($result['total'])."&nbsp;</td>\n";
					echo "</tr>";
				?>
			</tbody>
			</table>
		<?php
		}
	}
	if($clinical_value == true)
	{
	?>
		<br />
		<h2>1.B. Average number of weekly hours in hospital and on-call.</h2>						
		<table class="tableListReport" border="1" cellspacing="0" summary="Activity Profile">
					<colgroup>
						<col class="student_name" />
						<col class="student_name" />
					</colgroup>
					<thead>
						<tr>
							<td class="student_name" id="colhospital_hours" width="50%">Average Weekly Hospital Hours</td>
							<td class="student_name" id="colon_call_hours" width="50%">Average Weekly On-Call Hours</td>
						</tr>
					</thead>
				<tbody>	
			<?php
					echo "<tr>\n";
					echo "	<td class=\"student_name\" width=\"50%\">".html_encode($result['hospital_hours'])."&nbsp;</td>\n";
					echo "	<td class=\"student_name\" width=\"50%\">".html_encode($result['on_call_hours'])."&nbsp;</td>\n";
					echo "</tr>";
			?>
			</tbody>
			</table>
		<br />
	<?php 
	}
	?>
	<h2>2.A. Are these percentages consisent with the current workload standard of your Department / School / Unit and your
	assigned role for the calendar year?</h2>						
	<table class="tableListReport" border="1" cellspacing="0" summary="Activity Profile">
				<colgroup>
					<col class="average_hours" />
					<col class="full_description" />
				</colgroup>
				<thead>
					<tr>
						<td class="average_hours" id="colconsistent" width="15%">Yes / No</td>
						<td class="full_description" id="colconsistent_comments" width="85%">Comments</td>
					</tr>
				</thead>
			<tbody>	
		<?php
				echo "<tr>\n";
				echo "	<td class=\"average_hours\" width=\"15%\">".html_encode($result['consistent'])."&nbsp;</td>\n";
				echo "	<td class=\"full_description\" width=\"85%\">". ($result['consistent_comments'] == "" ? "N/A" : html_encode($result['consistent_comments']))."&nbsp;</td>\n";
				echo "</tr>";
		?>
		</tbody>
		</table>
	<br />
	<h2>2.B. Are these percentages in keeping with your overall career goals?</h2>						
	<table class="tableListReport" border="1" cellspacing="0" summary="Activity Profile">
				<colgroup>
					<col class="average_hours" />
					<col class="full_description" />
				</colgroup>
				<thead>
					<tr>
						<td class="average_hours" id="colcareer_goals" width="15%">Yes / No</td>
						<td class="full_description" id="colcareer_comments" width="85%">Comments</td>
					</tr>
				</thead>
			<tbody>	
		<?php
				echo "<tr>\n";
				echo "	<td class=\"average_hours\" width=\"15%\">".html_encode($result['career_goals'])."&nbsp;</td>\n";
				echo "	<td class=\"full_description\" width=\"85%\">". ($result['career_comments'] == "" ? "N/A" : html_encode($result['career_comments']))."&nbsp;</td>\n";
				echo "</tr>";
		?>
		</tbody>
		</table>
		<?php
		if($clinical_value == true)
		{
		?>
			<br />
			<h2>3. What role(s) do you see yourself in?  Are your current professional activities compatible with that (those) role(s)?</h2>						
			<table class="tableListReport" border="1" cellspacing="0" summary="Activity Profile">
						<colgroup>
							<col class="student_name" />
							<col class="average_hours" />
							<col class="full_description" />
						</colgroup>
						<thead>
							<tr>
								<td class="student_name" id="colroles" width="25%">Role</td>
								<td class="average_hours" id="colroles_compatible" width="15%">Compatible</td>
								<td class="full_description" id="colroles_comments" width="60%">Comments</td>
							</tr>
						</thead>
					<tbody>	
				<?php
						echo "<tr>\n";
						echo "	<td class=\"student_name\" width=\"25%\">".html_encode($result['roles'])."&nbsp;</td>\n";
						echo "	<td class=\"average_hours\" width=\"15%\">".html_encode($result['roles_compatible'])."&nbsp;</td>\n";
						echo "	<td class=\"full_description\" width=\"60%\">". ($result['roles_comments'] == "" ? "N/A" : html_encode($result['roles_comments']))."&nbsp;</td>\n";
						echo "</tr>";
				?>
				</tbody>
				</table>
			<?php 
		}
		?>
	<br />
	<h2><?php echo ($clinical_value == true ? "4" : "3"); ?>. Additional Comments</h2>						
	<table class="tableListReport" border="1" cellspacing="0" summary="Activity Profile">
				<colgroup>
					<col class="full_description" />
				</colgroup>
				<thead>
					<tr>
						<td class="full_description" id="colcomments" width="100%">Comments</td>
					</tr>
				</thead>
			<tbody>	
		<?php
				echo "<tr>\n";
				echo "	<td class=\"full_description\" width=\"100%\">". ($result['comments'] == "" ? "N/A" : html_encode($result['comments']))."&nbsp;</td>\n";
				echo "</tr>";
		?>
		</tbody>
		</table>
<br /><br />
<br />
<?php	
	echo "<h2>Member - I attest that this is my annual report for the " . $REPORT_YEAR ." year.</h2>";
	echo "<b>Date:</b> " . date("Y-m-d") . "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>Member Signature:</b> ______________________________________";
?>
<br /><br /><br />
<?php
	echo "<b>ANNUAL REPORT FORMS MUST BE SUBMITTED TO YOUR DEPARTMENT HEAD PRIOR TO TRANSMISSION TO THE FACULTY OFFICE.  UNSIGNED REPORTS WILL BE RETURNED</b>";
	echo '<!-- FOOTER LEFT "$CHAPTER" --> <!-- FOOTER CENTER "" --> <!-- FOOTER RIGHT "$PAGE" --><!-- PAGE BREAK --><br><br>';
?>
<br />
<?php 

if($clinical_value == true) {
	?>
	<h2>Department Head's Assessment of Annual Performance (Based on Role Definition and Expectations)</h2>						
	<table class="tableListReport" border="1" cellspacing="0" summary="Department Head">
				<colgroup>
					<col class="student_name" />
					<col class="full_description" />
					<col class="student_name" />
				</colgroup>
				<thead>
					<tr>
						<td class="student_name" id="coltype" width="25%">&nbsp;</td>
						<td class="full_description" id="colcomments" width="65%">Comments</td>
						<td class="student_name" id="colappraisal" width="10%">Appraisal (A, B, C)</td>
					</tr>
				</thead>
			<tbody>	
		<?php
				echo "<tr>\n";
				echo "	<td class=\"student_name\" width=\"25%\">1) Education</td>\n";
				echo "	<td class=\"full_description\" width=\"65%\"><br /><br /><br /></td>\n";
				echo "	<td class=\"student_name\" width=\"10%\">&nbsp;<br /><br /><br /><br /></td>\n";
				echo "</tr>";
				echo "<tr>\n";
				echo "	<td class=\"student_name\" width=\"25%\">2) Research / Scholarship</td>\n";
				echo "	<td class=\"full_description\" width=\"65%\"><br /><br /><br /></td>\n";
				echo "	<td class=\"student_name\" width=\"10%\">&nbsp;<br /><br /><br /><br /></td>\n";
				echo "</tr>";
				echo "<tr>\n";
				echo "	<td class=\"student_name\" width=\"25%\">3) Clinical Activity</td>\n";
				echo "	<td class=\"full_description\" width=\"65%\"><br /><br /><br /></td>\n";
				echo "	<td class=\"student_name\" width=\"10%\">&nbsp;<br /><br /><br /><br /></td>\n";
				echo "</tr>";
				echo "<tr>\n";
				echo "	<td class=\"student_name\" width=\"25%\">4) Service / Administration</td>\n";
				echo "	<td class=\"full_description\" width=\"65%\"><br /><br /><br /></td>\n";
				echo "	<td class=\"student_name\" width=\"10%\">&nbsp;<br /><br /><br /><br /></td>\n";
				echo "</tr>";
		?>
		</tbody>
		</table>
		<b>A</b> = Meets and Exceeds Expectations, <b>B</b> = Meets Expectations, <b>C</b> = Does Not Meet Expectations
		<br /><br /><hr>
		Department Head's / Director's Overall Appraisal:
		<br /><br /><br /><br /><br /><br />
		Recommended Action:
		<br /><br /><br /><br /><br /><br />
		Role Definition Modification:
		<br /><br /><br /><br /><br /><br />
		Comments on Career Development:
		<br /><br /><br /><br /><br /><br />
		Member's Comments:
		<br /><br /><br /><br /><br /><br />
		Department Head's Signature:_______________________________&nbsp;&nbsp;Date:_____________<br /><br />
		Member's Signature:_______________________________&nbsp;&nbsp;Date:_____________<br /><br />
		<br /><br /><br /><br /><br /><br />
	<?php
} else {
	?>
	Faculty Member: <?php echo html_encode($faculty_member_GivenName). " " . html_encode($faculty_member_SurName); ?>&nbsp;&nbsp;Department: <?php echo $department; ?><br /><br />
	Calendar Year:<?php echo $REPORT_YEAR; ?>
	<h2 align="center">Department Head's (School of Medicine) / Director's Assessment of Annual Performance</h2>
	<br />
	<div align="center">CONFIDENTIAL</div>
	<table border="0" cellspacing="0" summary="Department Head" align="center">
		<colgroup>
			<col class="full_description" />
			<col class="student_name" />
		</colgroup>
		<thead>
			<tr>
				<td class="full_description" id="coltype" width="70%">&nbsp;</td>
				<td class="student_name" id="colcomments" width="30%">&nbsp;</td>
			</tr>
		</thead>
		<tbody>	
		<tr>
			<td class="full_description" width="70%" align="center">
				Please appraise the performance of the individual faculty member in relation to the <em>Collective Agreement Article</em> on 
				academic responsibilities, and in particular in relation to 
				your expectations.
			</td>
			<td class="student_name" width="30%"  align="center">
				3 - Above expectations<br />
				2 - Meets expectations<br />
				1 - Below expectations<br />
				NA - Not applicable&nbsp;&nbsp;&nbsp;&nbsp;
			</td>
		</tr>
		</tbody>	
	</table>
	<br />
	<table class="tableListReport" border="1" cellspacing="0" summary="Department Head">
		<colgroup>
			<col class="student_name" />
			<col class="full_description" />
			<col class="student_name" />
		</colgroup>
		<thead>
			<tr>
				<td class="student_name" id="coltype" width="25%">Category</td>
				<td class="full_description" id="colcomments" width="65%">Director's / Head's Comments</td>
				<td class="student_name" id="colappraisal" width="10%" align="center">Appraisal</td>
			</tr>
		</thead>
		<tbody>	
			<tr>
				<td class="student_name" id="coltype" width="25%">Teaching</td>
				<td class="full_description" id="colcomments" width="65%" colspan="2">&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp;<br /></td>
			</tr>
			<tr>
				<td class="student_name" id="coltype" width="25%" colspan="2">Teaching Effectiveness</td>
				<td class="student_name" id="colappraisal" width="10%" align="center"><font size="1"> 3 | 2 | 1 | NA </font></td>
			</tr>
		</tbody>
	</table>
	<br />
	<table class="tableListReport" border="1" cellspacing="0" summary="Department Head">
		<colgroup>
			<col class="student_name" />
			<col class="full_description" />
			<col class="student_name" />
		</colgroup>
		<thead>
			<tr>
				<td class="student_name" id="coltype" width="25%">Category</td>
				<td class="full_description" id="colcomments" width="65%">Director's / Head's Comments</td>
				<td class="student_name" id="colappraisal" width="10%" align="center">Appraisal</td>
			</tr>
		</thead>
		<tbody>	
			<tr>
				<td class="student_name" id="coltype" width="25%">Scholarship</td>
				<td class="full_description" id="colcomments" width="65%" colspan="2">&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp;<br /></td>
			</tr>
			<tr>
				<td class="student_name" id="coltype" width="25%" colspan="2">Scholarship Contribution</td>
				<td class="student_name" id="colappraisal" width="10%" align="center"><font size="1"> 3 | 2 | 1 | NA </font></td>
			</tr>
		</tbody>
	</table>
	<br />
	<table class="tableListReport" border="1" cellspacing="0" summary="Department Head">
		<colgroup>
			<col class="student_name" />
			<col class="full_description" />
			<col class="student_name" />
		</colgroup>
		<thead>
			<tr>
				<td class="student_name" id="coltype" width="25%">Category</td>
				<td class="full_description" id="colcomments" width="65%">Director's / Head's Comments</td>
				<td class="student_name" id="colappraisal" width="10%" align="center">Appraisal</td>
			</tr>
		</thead>
		<tbody>	
			<tr>
				<td class="student_name" id="coltype" width="25%">Service</td>
				<td class="full_description" id="colcomments" width="65%" colspan="2">&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp;<br />&nbsp;<br /></td>
			</tr>
			<tr>
				<td class="student_name" id="coltype" width="25%" colspan="2">Service Contribution</td>
				<td class="student_name" id="colappraisal" width="10%" align="center"><font size="1"> 3 | 2 | 1 | NA </font></td>
			</tr>
		</tbody>
	</table>
	<br /><br /><hr>
	Department Head's / Director's Overall Appraisal:
	<br /><br /><br /><br /><br /><br />
	Member's Comments:
	<br /><br /><br /><br /><br /><br />
	Department Head's Signature:_______________________________&nbsp;&nbsp;Date:_____________<br /><br />
	Member's Signature:_______________________________&nbsp;&nbsp;Date:_____________<br /><br />
	<br /><br /><br /><br /><br /><br />
	<?php
}
?>
		<table class="tableListReport" border="0" cellspacing="0" summary="Department Head">
		<tbody>	
			<tr>
				<td width="100%"><?php echo ($clinical_value == false ? "<font size=\"1\">".QUFA_DISCLAIMER."</font>" : ""); ?></td>
			</tr>
			</tbody>
		</table>
		</div>
	</div>	
</form>
	
	<?php
	echo '<!-- FOOTER LEFT "$CHAPTER" --> <!-- FOOTER CENTER "" --> <!-- FOOTER RIGHT "$PAGE" --> <!-- PAGE BREAK --><br><br>';
	
	$content = ob_get_contents();
	
	if(!file_put_contents($output_file.".html", $content)) 
	{
		$ERROR++;
		$ERRORSTR[] = "Error downloading Annual Report.";

		echo display_error();

		application_log("error", "Error downloading Annual Report Document (".$output_file.".html".") for ".$proxy_id.".");
	}

if((is_array($APPLICATION_PATH)) && (isset($APPLICATION_PATH["htmldoc"])) && (@is_executable($APPLICATION_PATH["htmldoc"]))) {
$exec_command    = <<<COMMAND
/usr/bin/htmldoc \
--format pdf14 \
--charset iso-8859-1 \
--size Letter \
--pagemode document \
--portrait \
--no-duplex \
--encryption \
--compression=6 \
--permissions print \
--permissions no-modify \
--browserwidth 800 \
--top 1cm \
--bottom 1cm \
--left 2cm \
--right 2cm \
--header \
--footer \
--embedfonts \
--bodyfont Helvetica \
--headfootsize 8 \
--headfootfont Courier \
--quiet \
--fontsize 10 \
--webpage $output_file.html \
--outfile $output_file.pdf
COMMAND;

	@exec($exec_command);
	@chmod($output_file.".pdf", 0644);
} else {
	application_log("error", "Unable to locate the executable HTMLDoc application that is required to generate the annual report'");
}
}
?>