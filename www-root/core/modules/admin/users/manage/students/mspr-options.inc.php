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
if (!defined("IN_MANAGE_USER_STUDENTS")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("mspr", "create", true)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

	add_error("Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");
	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
}  else {

	require_once("Classes/mspr/MSPRs.class.php");
	$PROXY_ID					= $user_record["id"];
	$user = User::fetchRowByID($user_record["id"]);
	
	$PAGE_META["title"]			= "MSPR Options";
	$PAGE_META["description"]	= "";
	$PAGE_META["keywords"]		= "";

	$BREADCRUMB[]	= array("url" => ENTRADA_URL."/admin/users/manage/students?section=mspr&id=".$PROXY_ID, "title" => "MSPR");
	$BREADCRUMB[]	= array("url" => ENTRADA_URL."/admin/users/manage/students?section=mspr-options&id=".$PROXY_ID, "title" => "MSPR Options");

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

	
	$mspr = MSPR::get($user);
	$year = $user->getGradYear();
	$class_data = MSPRClassData::get($year);
	
	$class_close = $class_data->getClosedTimestamp();
	$mspr_close = $mspr->getClosedTimestamp();
	
	add_mspr_management_sidebar();
	if ($_POST["action"]=="Update Options") {
		
		if (!isset($_POST['close_datetime'])) {
			//removing the custom deadline.... or simply not setting it?
			$timestamp=null;
			
		} else {
			$mspr_close_date = $_POST['close_datetime_date'];
			$mspr_close_hour = $_POST['close_datetime_hour'];
			$mspr_close_min = $_POST['close_datetime_min'];
	
			//error checking.... the fun part
			if (!$mspr_close_date || !checkDateFormat($mspr_close_date) ) { 
				add_error("Invalid date format. The submission deadline date must be in the format yyyy-mm-dd, and be a valid date.");
			}
			
			if (!$mspr_close_hour < 0 || $mspr_close_hour > 23 || $mspr_close_mins < 0 || $mspr_close_mins > 59) {
				add_error("Invalid time. Please check your values and try again.");
			}
			$parts = date_parse($mspr_close_date);  
			$timestamp = mktime($mspr_close_hour,$mspr_close_min, 0, $parts['month'],$parts['day'], $parts['year']); 
			
		}
		
		if (!has_error()){
			$is_early = $timestamp !== null && $timestamp < $class_close;
			if ($_POST["confirm"] != "Continue" && $is_early) {
				//the requested custom close is earlier than the class default. need to confirm
				$page_mode = "confirm"; 
			} else {
				
				$resolve_type = $_POST['resolve_type'];
				
				switch($resolve_type) {
					case "keep_original":
						header( "Location: ".ENTRADA_URL."/admin/users/manage/students?section=mspr&id=".$PROXY_ID );
						exit;
						break;
					case "remove_custom":
						$mspr->setClosedTimestamp(null);
						break;
					case "confirm_early":
					default:
						$mspr->setClosedTimestamp($timestamp);
				}
				
				
				if (!has_error()){
					add_success("MSPR options for ". $user->getFullname() ." successfully updated.<br /><br />You will be redirected to their MSPR page in 5 seconds.");
					$page_mode="complete";
					header( "refresh:5;url=".ENTRADA_URL."/admin/users/manage/students?section=mspr&id=".$PROXY_ID );
				}
			} 
		}
		
			
	}
	
	switch ($page_mode) {
		case "complete":
				display_status_messages();
			break;
		case "confirm":
		?>
		<div class="display-notice">The requested custom submission deadline is earlier than the class default. Please choose how this should be handled below.</div>
		<form action="<?php echo ENTRADA_URL; ?>/admin/users/manage/students?section=mspr-options&id=<?php echo $PROXY_ID; ?>" method="post">
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
							<input type="radio" name="resolve_type" value="remove_custom" checked="checked" /> Remove the custom setting and use the class default<br />
							<input type="radio" name="resolve_type" value="keep_original" /> Keep the existing deadline setting (<?php echo date("Y-m-d @ H:i",$mspr_close); ?>)<br />
							<input type="radio" name="resolve_type" value="confirm_early" /> Use the deadline specified (<?php echo $mspr_close_date . " " . $mspr_close_hour . ":" . $mspr_close_min; ?>)<br />
						</td>
					</tr>
				</tbody>
			</table>	
		</form>
			<?php
			break;
		default:
	?>
	<h1>MSPR Options for <?php echo $user->getFullname(); ?></h1>
	<?php display_status_messages(); ?>
	
	<div class="instructions">
		<strong>Instructions:</strong><p>To set a custom deadline, check the box on the left, and specify the date and time in the fields on the right. To restore the default, uncheck the box on the left.</p>
		<p><strong>Note: </strong>Although students may have custom deadlines for MSPR submissions, this is only intended to be used in extraordinary circumstances. </p>
	</div>
	<br />
	<p>Class of <?php echo $year; ?> default submission deadline: <?php echo ($class_close ? date("F j, Y \a\\t g:i a",$class_close) : "Unset"); ?> &nbsp;&nbsp;(<a href="<?php echo ENTRADA_URL; ?>/admin/mspr?section=mspr-options&year=<?php echo $year; ?>">change</a>)</p>

	<form action="<?php echo ENTRADA_URL; ?>/admin/users/manage/students?section=mspr-options&id=<?php echo $PROXY_ID; ?>" method="post">
		<input type="hidden" name="user_id" value="<?php echo $PROXY_ID; ?>"></input>
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
					$current_time = ($class_close != $mspr_close) ? $mspr_close : 0;
					echo generate_calendar("close_datetime","Custom Submission Deadline:",false,$current_time,true,false,false);
				?>
			</tbody>
		</table>	
	</form>
	<?php 
	}
}
