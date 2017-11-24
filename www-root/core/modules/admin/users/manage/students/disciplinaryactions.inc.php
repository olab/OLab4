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
	require_once("Classes/mspr/DisciplinaryActions.class.php");
		
	$user = User::fetchRowByID($user_record["id"]);
	
	$PAGE_META["title"]			= "Disciplinary Actions";
	$PAGE_META["description"]	= "";
	$PAGE_META["keywords"]		= "";

	$PROXY_ID					= $user_record["id"];

	$BREADCRUMB[]	= array("url" => ENTRADA_URL."/admin/users/manage/students?section=disciplinaryactions&id=".$PROXY_ID, "title" => "Disciplinary Actions");

	
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
	
	process_mspr_details($translate, "disciplinary_actions");
	display_status_messages();

	
?>
<h1>Disciplinary Actions: <?php echo $user->getFullname(); ?></h1>

<?php 
	$show_da_form =  ($_GET['show'] == "da_form");
?>		
	
	<div id="add_disciplinary_action_link" style="float: right;<?php if ($show_da_form) { echo "display:none;"; }   ?>">
		<ul class="page-action">
			<li><a id="add_disciplinary_action" href="<?php echo ENTRADA_URL; ?>/admin/users/manage/students?section=disciplinaryactions&show=da_form&id=<?php echo $PROXY_ID; ?>" class="strong-green">Add Disciplinary Action</a></li>
		</ul>
	</div>
	<div class="clear">&nbsp;</div>
	
<form id="disciplinary_actions_form" action="<?php echo ENTRADA_URL; ?>/admin/users/manage/students?section=disciplinaryactions&id=<?php echo $user->getID(); ?>" method="post" <?php if (!$show_da_form) { echo "style=\"display:none;\""; }   ?> >
	<input type="hidden" name="action" value="add"></input>
	<input type="hidden" name="user_id" value="<?php echo $user->getID(); ?>"></input>
	<table class="disciplinary_actions">
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
					<input type="submit" class="btn btn-primary" value="Add Action" />
					<div id="hide_disciplinary_action_link" style="display:inline-block;">
						<ul class="page-action-cancel">
							<li><a id="hide_disciplinary_action" href="<?php echo ENTRADA_URL; ?>/admin/users/manage/students?section=disciplinaryactions&id=<?php echo $PROXY_ID; ?>" class="strong-green">[ Cancel Adding Disciplinary Action ]</a></li>
						</ul>
					</div>
				</td>
			</tr>
		</tfoot>
		<tbody>
			<tr>
				<td>&nbsp;</td>
				<td >
					<label for="details" class="form-required">Details of Action:</label>
				</td>
				<td >
					<textarea id="details" name="details" style="width: 100%; height: 100px;" cols="65" rows="20"></textarea>	
				</td>
			</tr>
		</tbody>
	</table>
</form>
<div class="clear">&nbsp;</div>




		<table class="disciplinary_actions tableList" cellspacing="0">
			<colgroup>
				<col width="95%"></col>
				<col width="5%"></col>
			</colgroup>
			<thead>
				<tr>
					<td class="general">
						Disciplinary Actions
					</td>
					<td class="general">&nbsp;</td>
				</tr>
				</thead>
				<tbody>
				
		<?php 
		$das = DisciplinaryActions::get($user);
		if ($das) {
			foreach($das as $da) {
				?>
				<tr>
					<td class="clineval_comment">
						<?php echo clean_input($da->getDetails(), array("notags", "specialchars")) ?>	
					</td>
			
					<td class="controls">
						<form class="remove_disciplinary_actions_form" action="<?php echo ENTRADA_URL; ?>/admin/users/manage/students?section=disciplinaryactions&id=<?php echo $user->getID(); ?>" method="post" >
							<input type="hidden" name="user_id" value="<?php echo $user->getID(); ?>"></input>
							<input type="hidden" name="action" value="remove"></input>
							<input type="hidden" name="entity_id" value="<?php echo $da->getID(); ?>"></input>
							
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

	var disciplinary_actions = new PassiveDataEntryProcessor({
		new_form: $('disciplinary_actions_form'),
		new_button: $('add_disciplinary_action_link'),
		hide_button: $('hide_disciplinary_action')
		
	});

	</script>
<?php
}
?>