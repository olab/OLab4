<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * This file gives Entrada users the ability to update their user profile.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jonathan Fingland <jonathan.fingland@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_MSPR_ADMIN"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("mspr", "create", false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

	add_error("Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");
	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
}  else {
	
	require_once("Classes/mspr/MSPRs.class.php");
	
	$PAGE_META["title"]			= "MSPR Class Options";
	$PAGE_META["description"]	= "";
	$PAGE_META["keywords"]		= "";

	if (isset($_GET['year'])) {
		$year = $_GET['year'];
		if (!is_numeric($year)) {
			unset($year);
		}
	}
	
	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/mspr?year=".$year, "title" => "Class of ".$year );
	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/mspr?section=mspr-options?year=".$year, "title" => "MSPR Class Options");

	$PROCESSED		= array();
	
	if ((is_array($_SESSION["permissions"])) && ($total_permissions = count($_SESSION["permissions"]) > 1)) {
		$sidebar_html  = "The following individual".((($total_permissions - 1) != 1) ? "s have" : " has")." given you access to their ".APPLICATION_NAME." permission levels:";
		$sidebar_html .= "<ul class=\"menu\">\n";
		foreach ($_SESSION["permissions"] as $access_id => $result) {
			if ($access_id != $ENTRADA_USER->getDefaultAccessId()) {
				$sidebar_html .= "<li class=\"checkmark\"><strong>".html_encode($result["fullname"])."</strong><br /><span class=\"content-small\">Exp: ".(($result["expires"]) ? date("D M d/y", $result["expires"]) : "Unknown")."</span></li>\n";
			}
		}
		$sidebar_html .= "</ul>\n";

		new_sidebar_item("Delegated Permissions", $sidebar_html, "delegated-permissions", "open");
	}

	add_mspr_admin_sidebar($year);
	
	$class_data = MSPRClassData::get($year);
	
	$class_close = $class_data->getClosedTimestamp();
	
	if ($_POST["action"]=="Update Options") {
		
		$class_close_date = $_POST['close_datetime_date'];
		$class_close_hour = $_POST['close_datetime_hour'];
		$class_close_min = $_POST['close_datetime_min'];

		//error checking.... the fun part
		if (!$class_close_date || !checkDateFormat($class_close_date) ) { 
			add_error("Invalid date format. The submission deadline date must be in the format yyyy-mm-dd, and be a valid date.");
		}
		
		if (!$class_close_hour < 0 || $class_close_hour > 23 || $class_close_mins < 0 || $class_close_mins > 59) {
			add_error("Invalid time. Please check your values and try again.");
		}
		
		$parts = date_parse($class_close_date);  
		$timestamp = mktime($class_close_hour,$class_close_min, 0, $parts['month'],$parts['day'], $parts['year']); 

		if (!has_error()){
			$has_custom = MSPRs::hasCustomDeadlines_Year($year);
			if ($_POST["confirm"] != "Continue" && $has_custom) {
				//there are set custom deadlines. need to present another set of options
				$page_mode = "confirm"; 
			} else {
				
				$resolve_type = $_POST['resolve_type'];
				$class_data->setClosedTimestamp($timestamp);
				
				switch($resolve_type) {
					case "update_earlier":
						MSPRs::clearCustomDeadlinesEarlierThan_Year($year,$timestamp);
						break;
					case "update_all":
						MSPRs::clearCustomDeadlines_Year($year);
						break;
				}
				
				
				if (!has_error()){
					add_success("MSPR options for the class of ". $year ." successfully updated.<br /><br />You will be redirected to the MSPR Class page in 5 seconds.");
					$page_mode = "complete";
					header( "refresh:5;url=".ENTRADA_URL."/admin/mspr?year=".$year );
				}	
			} 
		}
	}

	switch($page_mode) {
		case "complete":
			display_status_messages();
			break;
		case "confirm":
			?>
		<div class="display-notice">Some students in this class have custom submission deadlines. Please choose how these should be handled below.</div>
		<form action="<?php echo ENTRADA_URL; ?>/admin/mspr?section=mspr-options&year=<?php echo $year; ?>" method="post">
			<?php 
				foreach ($_POST as $name=>$value) {
					
					//unlikely to be necessary, just so we don't have someone injecting arbitrary script/html 
					$safe_name = filter_var($name, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
					$safe_value = filter_var($value, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
					echo "<input type='hidden' name='".$safe_name."' value='".$safe_value."' />";
				}
			?>
			<table class="mspr_form">
				<colgroup>
					<col width="3%"></col>
					<col width="25%"></col>
					<col width="72%"></col>
				</colgroup>
				<tfoot>
					<tr>
						<td colspan="3">&nbsp;</td>
					</tr>
					<tr>
						<td colspan="3" style="border-top: 2px #CCCCCC solid; padding-top: 5px; text-align: right">
							<input type="submit" class="btn btn-primary" name="confirm" value="Continue" />
						</td>
					</tr>
				</tfoot>
				<tbody>
					<tr>
						<td>
							&nbsp;
						</td>
						<td valign="top">
							<label class="form-required" for="resolve_type">Custom Deadline Resolution:</label>
						</td>
						<td>
							<input type="radio" name="resolve_type" value="update_earlier" checked="checked" /> Remove custom deadlines earlier than the new class deadline (if any)<br />
							<input type="radio" name="resolve_type" value="update_all" /> Remove all custom deadlines<br />
							<input type="radio" name="resolve_type" value="do_nothing" /> Do not modify any custom deadlines (Not Recommended)<br />
						</td>
					</tr>
				</tbody>
			</table>	
		</form>
			<?php
			break;
		default:
	?>
	<h1>MSPR Options for Class of <?php echo $year; ?></h1>
	<?php display_status_messages(); ?>
	<div class="instructions">
	</div>
	<br />

	<form action="<?php echo ENTRADA_URL; ?>/admin/mspr?section=mspr-options&year=<?php echo $year; ?>" method="post">
		<table class="mspr_form">
			<colgroup>
				<col width="3%"></col>
				<col width="25%"></col>
				<col width="72%"></col>
			</colgroup>
			<tfoot>
				<tr>
					<td colspan="3">&nbsp;</td>
				</tr>
				<tr>
					<td colspan="3" style="border-top: 2px #CCCCCC solid; padding-top: 5px; text-align: right">
						<input type="submit" class="btn btn-primary" name="action" value="Update Options" />
					</td>
				</tr>
			</tfoot>
			<tbody>
				<?php
					$current_time = ($class_close) ? $class_close : 0;
					echo generate_calendar("close_datetime","Submission Deadline:",true,$current_time,true,false,false,false,false);
				?>
			</tbody>
		</table>	
	</form>
	<?php 
	}
}