<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 *
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Doug Hall <hall@ucalgary.ca>
 * @copyright Copyright 2016 University of Calgary. All Rights Reserved.
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
} elseif (!isset($_POST["associated_organisation_id"]) && !isset($_SESSION["load_category"])) {
	header("Location: ".$_SERVER['HTTP_REFERER']);
	exit;
} else {

	$url = ENTRADA_URL . "/admin/users/metadata";

	require_once("Entrada/metadata/functions.inc.php");
	require_once("Classes/organisations/Organisations.class.php");

	$BREADCRUMB[] = array("url" => ENTRADA_URL . "/admin/users/metadata?section=load", "title" => $translate->_("metadata_bc_load"));

	if (isset($_POST["associated_cat_id"])) {
		$_SESSION['load_category'] = filter_input_array(INPUT_POST, array(
			"associated_organisation_id" => FILTER_SANITIZE_NUMBER_INT,
			"associated_group" => FILTER_SANITIZE_STRING,
			"associated_role" => FILTER_SANITIZE_STRING,
			"associated_cat_id" => FILTER_SANITIZE_NUMBER_INT));
	}
	$opts = $_SESSION['load_category'];

	$organisation_id = $opts["associated_organisation_id"];
	$organisation = Organisation::get($organisation_id);

	switch ($STEP) {
		case 2 :
			if(!$opts["associated_cat_id"]) {
				add_error("Invalid or unspecified Category.");
				header("HTTP/1.0 500 Internal Error");
				echo display_status_messages(false);
				exit;
			} else {
				$category = MetaDataType::get($opts["associated_cat_id"]);
			}

			if (!$opts["associated_group"]){
				add_error("Invalid or unspecified Group");
			} else {
				$group = $opts["associated_group"];
			}
			if ($opts["associated_role"] == "all") {
				$role = null;
			} else {
				$role = $opts["associated_role"];
			}
			$results = dumpMetaDataTable_Category($organisation_id, $group, $role, null, $category);

			if ($results) {
				$ONLOAD[]	= "setTimeout('window.location=\\'$url/?section=load&step=1\\'', 5000)";
				$pieces = array_shift($results);
				$file = strtr($pieces[0],"/ ","-_").($role?"_${role}_":""). date("dmy", startof("day")) . ".csv";
				ob_clear_open_buffers();

				header("Content-Type:  application/vnd.ms-excel");
				header("Content-Disposition: \"$file\"; filename=\"$file\"");
				echo $translate->_("metadata_heading_excel");
				foreach ($results as $result) {
					echo implode(",", $result) . "\n";
				}
				exit();
//				unset($_SESSION['csv']);
			}
			break;
		default:

			if ($ERROR) {
				echo display_error();
			}
			$restricted = MetaDataType::get($opts["associated_cat_id"])->getRestricted();
			?>
			<script type="text/javascript">
				jQuery(function($){
					$("#import-button").on("click", function() {
						$("#csv-form").submit();
					});
				});
			</script>
			<style type="text/css">
				#draftEvents_length {padding:5px 4px 0 0;}
				#draftEvents_filter {-moz-border-radius:10px 10px 0px 0px;-webkit-border-top-left-radius: 10px;-webkit-border-top-right-radius: 10px;border-radius: 10px 10px 0px 0px; border: 1px solid #9D9D9D;border-bottom:none;background-color:#FAFAFA;font-size: 0.9em;padding:3px;}
				#draftEvents_paginate a {margin:2px 5px;}
				#import-csv {display:none;}
			</style>
			
			<div class="container-fluid">

				<div class="row content-heading"><?php echo $translate->_("metadata_bc_load") ?></div>

				<div class="row span9">
					<?php if ($restricted) { ?>
						<div class="row-fluid  space-above content-subheading"><?php echo $translate->_(" Administrative: Not Public Viewable") ?>
					<?php } ?>
						<a href="#import-csv" class="btn btn-primary pull-right" data-toggle="modal"><?php echo $translate->_("metadata_button_import") ?></a>
						<?php if ($restricted) { echo "</div>"; } ?>
				</div>
				
				<div class="row span9 space-below space-above" style="background-color: <?php echo ($restricted?"#ffE6E6":"#f5f5f5"); ?>">
					<div class="row  space-above">
					<div class="offset1 span3 alignRight bold"><?php echo $translate->_("metadata_organization") ?>:</div>
					<div class="span4"><?php echo $organisation->getTitle() ?></div>
				</div>

				<div class="row">
					<div class="offset1 span3 alignRight bold"><?php echo $translate->_("metadata_category") ?>:</div>
					<div
						class="span4"><?php echo($opts["associated_cat_id"] ? MetaDataType::get($opts["associated_cat_id"]) : ""); ?></div>
				</div>

				<div class="row">
					<div class="offset1 span3 alignRight bold"><?php echo $translate->_("metadata_group") ?>:</div>
					<div
						class="span4"><?php echo($opts["associated_group"] ? ucwords($opts["associated_group"]) : ""); ?></div>
					</div>

				<div class="row">
					<div class="offset1 span3 alignRight bold"><?php echo $translate->_("metadata_role") ?>:</div>
					<div
						class="span4 space-below"><?php echo($opts["associated_role"] ? ucwords($opts["associated_role"]) : ""); ?></div>
				</div>
				</div>
				<div class="row span9">
					<a href="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE; ?>" class="btn btn-default"><?php echo $translate->_("Back"); ?></a>
					<input type="button" class="pull-right btn-info btn" 
						   value="<?php echo $translate->_("metadata_button_csv")." - ". MetaDataType::get($opts["associated_cat_id"]) ?>"
							   onclick="window.location='<?php echo $url; ?>/?section=load&step=2'"/>
					</div>

				<div class="modal hide fade" id="import-csv">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
						<h3>Import CSV</h3>
					</div>
					<div class="modal-body">
						<?php echo display_notice($translate->_("metadata_notice_import")); ?>
						<form id="csv-form" action="<?php echo ENTRADA_URL; ?>/admin/users/metadata?section=csv-import" enctype="multipart/form-data" method="POST">
							<input type="hidden" name="organisation_id" value="<?php echo $opts["associated_organisation_id"]; ?>" />
							<input type="hidden" name="group" value="<?php echo $opts["associated_group"]; ?>" />
							<input type="hidden" name="role" value="<?php echo $opts["associated_role"]; ?>" />
							<input type="hidden" name="cat_id" value="<?php echo $opts["associated_cat_id"]; ?>" />
							<input type="file" name="csv_file" />
						</form>
					</div>
					<div class="modal-footer">
						<button data-dismiss="modal" class="btn">Close</button>
						<a href="#" class="btn btn-primary" id="import-button">Import</a>
					</div>
				</div>
			</div>
			<hr>
			<?php
			break;
	}
}
