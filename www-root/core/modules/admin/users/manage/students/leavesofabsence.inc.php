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
} elseif (!$ENTRADA_ACL->amIAllowed("user", "create", false)) {
	header( "refresh:15;url=".ENTRADA_URL."/admin/users" );
	
	add_error("Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");
	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
}  else {
	
	require_once("Classes/users/User.class.php");
	require_once("Classes/mspr/LeavesOfAbsence.class.php");
		
	$user = User::fetchRowByID($user_record["id"]);
	
	$PAGE_META["title"]			= "Leaves of Absence";
	$PAGE_META["description"]	= "";
	$PAGE_META["keywords"]		= "";

	$PROXY_ID					= $user_record["id"];

	$BREADCRUMB[]	= array("url" => ENTRADA_URL."/admin/users/manage/students?section=leavesofabsences&id=".$PROXY_ID, "title" => "Leaves of Absence");

	
	$HEAD[] = "<script language='javascript' src='".ENTRADA_URL."/javascript/PassiveDataEntryProcessor.js'></script>";
	
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
	
	process_mspr_details($translate, "leaves_of_absence");
	display_status_messages();

	
?>
<h1>Leaves of Absence: <?php echo $user->getFullname(); ?></h1>

<?php 
	$show_loa_form =  ($_GET['show'] == "loa_form");
?>		
	
	<div id="add_leave_of_absence_link" style="float: right;<?php if ($show_loa_form) { echo "display:none;"; }   ?>">
		<ul class="page-action">
			<li><a id="add_leave_of_absence" href="<?php echo ENTRADA_URL; ?>/admin/users/manage/students?section=leavesofabsence&show=loa_form&id=<?php echo $PROXY_ID; ?>" class="strong-green">Add Leave of Absence</a></li>
		</ul>
	</div>
	<div class="clear">&nbsp;</div>
	
<form id="leave_of_absence_form" action="<?php echo ENTRADA_URL; ?>/admin/users/manage/students?section=leavesofabsence&id=<?php echo $user->getID(); ?>" method="post" <?php if (!$show_loa_form) { echo "style=\"display:none;\""; }   ?> >
	<input type="hidden" name="action" value="add"></input>
	<input type="hidden" name="user_id" value="<?php echo $user->getID(); ?>"></input>
	<table class="leave_of_absence">
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
					<input type="submit" class="btn btn-primary" value="Add Absence" />
					<div id="hide_leave_of_absence_link" style="display:inline-block;">
						<ul class="page-action-cancel">
							<li><a id="hide_leave_of_absence" href="<?php echo ENTRADA_URL; ?>/admin/users/manage/students?section=leavesofabsence&id=<?php echo $PROXY_ID; ?>" class="strong-green">[ Cancel Adding Leave of Absence ]</a></li>
						</ul>
					</div>
				</td>
			</tr>
		</tfoot>
		<tbody>
			<tr>
				<td>&nbsp;</td>
				<td >
					<label for="details" class="form-required">Details of Absence:</label>
				</td>
				<td >
					<textarea id="details" name="details" style="width: 100%; height: 100px;" cols="65" rows="20"></textarea>	
				</td>
			</tr>
		</tbody>
	</table>
</form>
<div class="clear">&nbsp;</div>

	<table class="leave_of_absences tableList" cellspacing="0">
		<colgroup>
			<col width="95%"></col>
			<col width="5%"></col>
		</colgroup>
		<thead>
			<tr>
				<td class="general">
					Leaves of Absence
				</td>
				<td class="general">&nbsp;</td>
			</tr>
			</thead>
			<tbody>
			
	<?php 
	$frs = LeavesOfAbsence::get($user);
	if ($frs) {
		foreach($frs as $fr) {
			?>
			<tr>
				<td class="leave_of_absence_details">
					<?php echo clean_input($fr->getDetails(), array("notags", "specialchars")) ?>	
				</td>
		
				<td class="controls">
					<form class="remove_leave_of_absence_form" action="<?php echo ENTRADA_URL; ?>/admin/users/manage/students?section=leavesofabsence&id=<?php echo $user->getID(); ?>" method="post" >
						<input type="hidden" name="user_id" value="<?php echo $user->getID(); ?>"></input>
						<input type="hidden" name="action" value="remove"></input>
						<input type="hidden" name="entity_id" value="<?php echo $fr->getID(); ?>"></input>
						
						<input type="image" src="<?php echo ENTRADA_URL ?>/images/action-delete.gif"></input> 
					</form>
					
				</td>
			</tr>
			<?php 
			
		}
	}
	?>
	</table>
		
	<script language="javascript">

	var leave_of_absences = new PassiveDataEntryProcessor({
		new_form: $('leave_of_absence_form'),
		new_button: $('add_leave_of_absence_link'),
		hide_button: $('hide_leave_of_absence')
		
	});

	</script>
<?php
}
?>