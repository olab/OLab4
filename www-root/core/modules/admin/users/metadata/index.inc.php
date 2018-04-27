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
	add_error($translate->_("module_no_permission") . str_ireplace(array("%admin_email%","%admin_name%"), array(html_encode($AGENT_CONTACTS["administrator"]["email"]),html_encode($AGENT_CONTACTS["administrator"]["name"])), $translate->_("module_assistance")));

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

require_once("Entrada/metadata/functions.inc.php");
require_once("Classes/organisations/Organisations.class.php");

$SCRIPT[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/meta_data.js\"></script>";
$ONLOAD[] = "api_url = \"".ENTRADA_URL."/admin/users/metadata?section=api-metadata\";page_init();";
?>
<h1><?php echo $translate->_("metadata_heading_manage"); ?></h1>

<form method="post" id="table_selector">
	<input type="hidden" name="request" value="categories" />
	<table style="width:100%;">
		<colgroup>
			<col width="3%"/>
			<col width="25%"/>
			<col width="72%"/>
		</colgroup>
		<tfoot>
			<tr>
				<td style="padding-top: 25px;" colspan="3"><input class="btn btn-info btn-small" type="submit" name="show_table" id="show_table_btn" value="<?php echo $translate->_("metadata_show"); ?>"/></td>
			</tr>
		</tfoot>
		<tbody>
		<tr>
			<td>&nbsp;</td>
			<td><label for="associated_organisation_id" class="form-required"><?php echo $translate->_("Organisation"); ?></label></td>
			<td>
				<select id="associated_organisation_id" name="associated_organisation_id" style="width: 203px">
					<?php
					$organisations = Organisations::get();
					if ($organisations) {
						foreach($organisations as $organisation) {
							$organisation_id = $organisation->getID();
							
							if ($ENTRADA_ACL->amIAllowed('resourceorganisation'.$organisation_id, 'create')) { 
								$organisation_title = $organisation->getTitle();
								echo build_option($organisation_id, html_encode($organisation_title), ($ENTRADA_USER->getActiveOrganisation() == $organisation_id) );
							}
						}
					}
					?>
				</select>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td><label for="associated_group" class="form-required"><?php echo $translate->_("metadata_group"); ?></label></td>
			<td><select id="associated_group" name="associated_group">
			<?php 
			$groups = array_keys($SYSTEM_GROUPS) ; arsort($groups);
			foreach ($groups as $group) {
				echo build_option($group, ucwords($group));
			}
			?>
			</select></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td><label for="associated_role" class="form-required"><?php echo $translate->_("metadata_role"); ?></label></td>
			<td><select id="associated_role" name="associated_role"></select></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td><label for="associated_cat_id" class="form-required"><?php echo $translate->_("metadata_category"); ?></label></td>
			<td id="assoc_cat_holder"></td>
		</tr>
		</tbody>
	</table>
</form>

<form id="meta_data_form" method="post">
</form>

<script type="text/javascript">
var user_groups = [];

<?php
function ucwords_alt($str, $key) {
	return ucwords($str);
}

foreach ($SYSTEM_GROUPS as $group=>$roles) {
	array_walk($roles, 'ucwords_alt');
	echo "user_groups[\"".$group."\"] = [\"".implode("\",\"",$roles)."\",\"All\"];";
}
?>

function loadpage() {
	$('table_selector').action = '<?php echo ENTRADA_URL?>/admin/users/meta_data?section=load';
	$('table_selector').submit();
}

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
	
	document.observe('MetaData:onBeforeUpdate', function () {loadingModal.open();});
	document.observe('MetaData:onAfterUpdate', function () {loadingModal.close();});
	
	document.observe("click", clickHandler);
	
	window.addRowReq = mkEvtReq(/^user_head_(\d+)$/,addUserRow);
	window.deleteRowReq = mkEvtReq(/^value_edit_(\d+)$/, deleteRow);

	$('associated_group').observe('change', setRoleList);
	$('associated_group').observe('change', getCategories);
	$('associated_organisation_id').observe('change', getCategories);
	$('associated_role').observe('change', getCategories);
	setRoleList();
	getCategories();
	
	$('table_selector').observe('submit', getTable);
}
</script>

<div id="errModal" class="modal-description">
	<div id="errModal_content" class="status"></div>
	<div class="footer">
		<button class="btn pull-right modal-close">Close</button>
	</div>
</div>
<div id="loadingModal" class="modal-description">
	<img src="<?php echo ENTRADA_URL; ?>/images/loading.gif" /> 
</div>
<?php 
}
