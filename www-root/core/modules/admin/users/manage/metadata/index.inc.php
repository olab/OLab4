<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 *
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jonathan Fingland <jonathan.fingland@queensu.ca>
 * @copyright Copyright 2011 Queen's University. All Rights Reserved.
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_MANAGE_USER_DATA"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("user", "update", false)) {
	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

require_once("Entrada/metadata/functions.inc.php");


$eUser = User::fetchRowByID($PROXY_ID);
$SCRIPT[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/meta_data.js\"></script>";
$ONLOAD[] = "api_url = \"".ENTRADA_URL."/admin/users/manage/metadata?section=api-metadata&id=".$PROXY_ID."\";page_init();";
?>
<h1><?php echo $translate->translate("Manage User Meta Data"); ?></h1>
<form id="meta_data_form" method="post">
<?php
if (!empty($eUser)) {
    echo editMetaDataTable_User($eUser);
} else {
    echo display_notice("There are currently no Meta Data Categories applicable to this user.");
}
?>
</form>
<div id="errModal" class="modal-description">
	<div id="errModal_content" class="status"></div>
	<div class="footer">
		<button class="btn pull-right modal-close">Close</button>
	</div>
</div>
<div id="loadingModal" class="modal-description">
	<img src="<?php echo ENTRADA_URL; ?>/images/loading.gif" /> 
</div>
<script type="text/javascript">
/**
 * Sets up resources needed by the page
 */
function page_init() {
	var errModal = new Control.Modal('errModal', {
		overlayOpacity:	0.75,
		closeOnClick:	'overlay',
		className:		'modal-description',
		fade:			true,
		fadeDuration:	0.30
	});
	display_error = ErrorHandler(errModal);
	
	var loadingModal = new Control.Modal('loadingModal', {
		overlayOpacity:	0.75,
		closeOnClick:	false,
		className:		'modal-description',
		fade:			true,
		fadeDuration:	0.30
	});
	
	window.addRowReq = mkEvtReq(/^cat_head_(\d+)$/,addCategoryRow);
	window.deleteRowReq = mkEvtReq(/^value_edit_(\d+)$/, deleteRow);


	document.observe("click", clickHandler);
	
	document.observe('MetaData:onBeforeUpdate', function () {loadingModal.open();});
	document.observe('MetaData:onAfterUpdate', function () {loadingModal.close();});
	
	table_init();
}
</script>
<?php 
}