<?php /**
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
 * ePortfolio public index
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <ryan.warner@queensu.ca>
 * @author Developer: Josh Dillon <josh.dillon@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 *
 */

if (!defined("PARENT_INCLUDED")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("eportfolio", "read") || $ENTRADA_USER->getGroup() != "student") {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	$eportfolio = Models_Eportfolio::fetchRowByGroupID($ENTRADA_USER->getCohort());

	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/eportfolio.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
	$HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = '".ENTRADA_URL."'; var PROXY_ID = '".$ENTRADA_USER->getProxyId()."'; var PORTFOLIO_ID = '".($eportfolio ? $eportfolio->getID() : 0)."';</script>";
	load_rte("minimal");
	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/ckeditor/adapters/jquery.js\"></script>\n";

	$eportfolio_can_attach_to_gradebook_assessment = Entrada_Settings::fetchValueByShortname("eportfolio_can_attach_to_gradebook_assessment", $ENTRADA_USER->getActiveOrganisation());
	$eportfolio_entry_is_assessable_set_by_learner = Entrada_Settings::fetchValueByShortname("eportfolio_entry_is_assessable_set_by_learner", $ENTRADA_USER->getActiveOrganisation());
	$HEAD[] = "<script type=\"text/javascript\">
		var eportfolio_index_settings = {}; 
		eportfolio_index_settings.eportfolio_can_attach_to_gradebook_assessment = $eportfolio_can_attach_to_gradebook_assessment;
		eportfolio_index_settings.eportfolio_entry_is_assessable_set_by_learner = $eportfolio_entry_is_assessable_set_by_learner;
	</script>";

	$JAVASCRIPT_TRANSLATIONS[] = "var eportfolio_index_localization = {};";
	$JAVASCRIPT_TRANSLATIONS[] = "eportfolio_index_localization.create_my_own_artifact = '" . html_encode($translate->_("Create My Own Artifact")) . "';";
	$JAVASCRIPT_TRANSLATIONS[] = "eportfolio_index_localization.create_artifact_in = '" . html_encode($translate->_("Create Artifact in")) . "';";
	$JAVASCRIPT_TRANSLATIONS[] = "eportfolio_index_localization.submitted = '" . html_encode($translate->_("Submitted")) . "';";
	$JAVASCRIPT_TRANSLATIONS[] = "eportfolio_index_localization.error_saving_entry = '" . html_encode($translate->_("An error occurred while attempting save this entry")) . "';";
	$JAVASCRIPT_TRANSLATIONS[] = "eportfolio_index_localization.please_try_again = '" . html_encode($translate->_("Please try again")) . "';";
	$JAVASCRIPT_TRANSLATIONS[] = "eportfolio_index_localization.save_entry = '" . html_encode($translate->_("Save Entry")) . "';";
	$JAVASCRIPT_TRANSLATIONS[] = "eportfolio_index_localization.add_entry = '" . html_encode($translate->_("Add Entry")) . "';";
	$JAVASCRIPT_TRANSLATIONS[] = "eportfolio_index_localization.delete_entry = '" . html_encode($translate->_("Delete Entry")) . "';";
	$JAVASCRIPT_TRANSLATIONS[] = "eportfolio_index_localization.remove = '" . html_encode($translate->_("Remove")) . "';";
	$JAVASCRIPT_TRANSLATIONS[] = "eportfolio_index_localization.warning = '" . html_encode($translate->_("Warning")) . "';";
	$JAVASCRIPT_TRANSLATIONS[] = "eportfolio_index_localization.chosen_to_remove_artifact = '" . html_encode($translate->_("You have chosen to remove an artifact you have created")) . "';";
	$JAVASCRIPT_TRANSLATIONS[] = "eportfolio_index_localization.use_button_to_remove_artifact = '" . html_encode($translate->_("Please use the button below to remove the artifact")) . "';";
	$JAVASCRIPT_TRANSLATIONS[] = "eportfolio_index_localization.error_fetching_folder = '" . html_encode($translate->_("An error occurred while attempting to fetch this folder")) . "';";
	$JAVASCRIPT_TRANSLATIONS[] = "eportfolio_index_localization.please_try_again = '" . html_encode($translate->_("Please try again")) . "';";
	$JAVASCRIPT_TRANSLATIONS[] = "eportfolio_index_localization.read_reflection = '" . html_encode($translate->_("Read Reflection")) . "';";
	$JAVASCRIPT_TRANSLATIONS[] = "eportfolio_index_localization.download_file = '" . html_encode($translate->_("Download File")) . "';";
	$JAVASCRIPT_TRANSLATIONS[] = "eportfolio_index_localization.visit_url = '" . html_encode($translate->_("Visit URL")) . "';";
	$JAVASCRIPT_TRANSLATIONS[] = "eportfolio_index_localization.used_for_assessment = '" . html_encode($translate->_("Used For Assessment")) . "';";
	$JAVASCRIPT_TRANSLATIONS[] = "eportfolio_index_localization.not_used_for_assessment = '" . html_encode($translate->_("Not Used For Assessment")) . "';";
	$JAVASCRIPT_TRANSLATIONS[] = "eportfolio_index_localization.due = '" . html_encode($translate->_("Due")) . "';";
	$JAVASCRIPT_TRANSLATIONS[] = "eportfolio_index_localization.confirm_remove_entry = '" . html_encode($translate->_("Please confirm that you wish to remove the entry titled")) . "';";
	$JAVASCRIPT_TRANSLATIONS[] = "eportfolio_index_localization.na = '" . html_encode($translate->_("NA")) . "';";
	$JAVASCRIPT_TRANSLATIONS[] = "eportfolio_index_localization.confirm_entry_removal = '" . html_encode($translate->_("Confirm Entry Removal")) . "';";
	$JAVASCRIPT_TRANSLATIONS[] = "eportfolio_index_localization.type = '" . html_encode($translate->_("Type")) . "';";
	$JAVASCRIPT_TRANSLATIONS[] = "eportfolio_index_localization.title = '" . html_encode($translate->_("Title")) . "';";
	$JAVASCRIPT_TRANSLATIONS[] = "eportfolio_index_localization.reflection_body = '" . html_encode($translate->_("Reflection Body")) . "';";
	$JAVASCRIPT_TRANSLATIONS[] = "eportfolio_index_localization.attach_file = '" . html_encode($translate->_("Attach File")) . "';";
	$JAVASCRIPT_TRANSLATIONS[] = "eportfolio_index_localization.description = '" . html_encode($translate->_("Description")) . "';";
	$JAVASCRIPT_TRANSLATIONS[] = "eportfolio_index_localization.url = '" . html_encode($translate->_("URL")) . "';";
	$JAVASCRIPT_TRANSLATIONS[] = "eportfolio_index_localization.no_artifacts_require_entries = '" . html_encode($translate->_("There are no artifacts that require entries")) . "';";
	$JAVASCRIPT_TRANSLATIONS[] = "eportfolio_index_localization.no_artifacts_attached_entries = '" . html_encode($translate->_("There are no artifacts with attached entries")) . "';";
	$JAVASCRIPT_TRANSLATIONS[] = "eportfolio_index_localization.no_artifacts_for_folder = '" . html_encode($translate->_("You have not created any artifacts for this folder")) . "';";
	$JAVASCRIPT_TRANSLATIONS[] = "eportfolio_index_localization.error_fetching_artifact = '" . html_encode($translate->_("An error occurred while attempting to fetch the artifact")) . "';";
	$JAVASCRIPT_TRANSLATIONS[] = "eportfolio_index_localization.error_fetching_entry = '" . html_encode($translate->_("An error occurred while attempting to fetch the entry")) . "';";
	$JAVASCRIPT_TRANSLATIONS[] = "eportfolio_index_localization.error_fetching_artifact_entries = '" . html_encode($translate->_("An error occurred while attempting to fetch the entries associated with this artifact")) . "';";
	$JAVASCRIPT_TRANSLATIONS[] = "eportfolio_index_localization.error_fetching_folder_artifacts = '" . html_encode($translate->_("An error occurred while attempting to fetch the artifacts associated with this folder")) . "';";
	$JAVASCRIPT_TRANSLATIONS[] = "eportfolio_index_localization.to_add_entry_select_artifact = '" . html_encode($translate->_("To add an entry to an artifact select an artifact from the My Artifacts list")) . "';";
    $JAVASCRIPT_TRANSLATIONS[] = "eportfolio_index_localization.reviewed_by_my_advisor = '" . html_encode($translate->_("Reviewed by my advisor")) . "';";
    $JAVASCRIPT_TRANSLATIONS[] = "eportfolio_index_localization.flagged_by_my_advisor = '" . html_encode($translate->_("Flagged by my advisor")) . "';";

	// ToDo: move this css out of here
	?>
	<style type="text/css">
		.artifact-container.loading {
			background-image:url("<?php echo ENTRADA_URL; ?>/images/loading_med.gif");
			background-position: center;
			background-repeat:no-repeat;
			min-height:400px;
		}
		.artifact-list-item {
			position:relative;
		}
		.remove-artifact {
			cursor:pointer;
			position:absolute;
			top:7px;
			right:6px;
			z-index:1010;
		}
	</style>

	<h1><?php echo $translate->_("My ePortfolio"); ?></h1>
	<div id="msg"></div>

	<div id="portfolio-pulse" class="portfolio-pulse">
		<?php
		//$portfolio_pulse_view = new Views_Profile_Portfolio_Pulse(["portfolio_id"=>$eportfolio->getID(), "proxy_id"=>$ENTRADA_USER->getProxyId()]);
		//$portfolio_pulse_view->render(array("type"=>"html"), true);
		?>
	</div>

	<div class="row-fluid" style="margin-bottom: 1rem;">
		<div class="span8">
		<?php

		if ($eportfolio) {
			$folders = $eportfolio->getFolders();
			if ($folders) {
			?>
			<div class="btn-group">
				<a class="btn btn-primary btn-large dropdown-toggle" data-toggle="dropdown" href="#">
					<i class="fa fa-folder-open"></i>
					<span id="current-folder"><?php echo $folders[0]->getTitle(); ?></span>
					<span class="caret"></span>
				</a>
				<ul class="dropdown-menu" id="folder-list">
				<?php
				foreach ($folders as $folder) { ?>
					<li>
						<a href="#" data-id="<?php echo $folder->getID(); ?>" class="folder-item">
							<i class="fa fa-folder-open-o"></i>
							<span><?php echo $folder->getTitle(); ?></span>
						</a>
					</li>
				<?php
				}
				?>
					<li class="divider"></li>
					<li><a href="<?php echo ENTRADA_URL; ?>/profile/eportfolio?section=export-portfolio"><i class="fa fa-share"></i> <?php echo $translate->_("Export My Portfolio"); ?></a></li>
				</ul>
			</div>
			<span class="portfolio-required-artifacts"></span>
		</div> <!-- /.span -->
		<div class="span4">
			<div class="btn-group pull-right">
				<a class="btn btn-large btn-primary dropdown-toggle" data-toggle="dropdown" href="#">
					<i class="fa fa-file"></i> <?php echo $translate->_("My Artifacts"); ?>
					<span class="caret"></span>
				</a>
				<ul class="dropdown-menu" id="artifact-list">
					<li id="entries-required" class="entries-list-title"><strong><?php echo $translate->_("Artifacts that require entries"); ?></strong></li>
					<li id="entries-attached" class="entries-list-title"><strong><?php echo $translate->_("Artifacts with attached entries"); ?></strong></li>
					<li id="entries-user" class="entries-list-title"><strong><?php echo $translate->_("Artifacts created by you"); ?></strong></li>
					<li class="divider"></li>
					<li id="artifact-learner-create"></li>
				</ul>
			</div>

		</div> <!-- /.span -->
	</div><!-- /.row-fluid -->


	<div id="portfolio-folder-pulse" class="portfolio-pulse"></div>

	<div class="folder-container">
		<h2 id="folder-title"></h2>
		<div id="msgs"></div>
		<div id="artifact-container" class="artifact-container loading"></div>
	</div>
	<div class="modal hide fade" id="portfolio-modal" style="width:700px;">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
			<h3></h3>
		</div>
		<div class="modal-body">
			<div id="modal-msg"></div>
			<form action="" method="POST" class="form-horizontal" id="portfolio-form">
				<input type="hidden" value="create-artifact" class="method" name="content-type" id="method" />
			</form>
		</div>
		<div class="modal-footer">
			<a href="#" class="btn pull-left" data-dismiss="modal"><?php echo $translate->_("Cancel"); ?></a>
			<a href="#" class="btn btn-primary" id="save-button"><?php echo $translate->_("Save changes"); ?></a>
		</div>
	</div>
		<?php
		} else {
			echo display_notice($translate->_("Sorry but your eportfolio not yet have any folders created").".");
		}
	} else {
		echo display_notice($translate->_("Sorry but your class does not yet have an eportfolio created").". ".$translate->_("If you are receiving this message in error please use the feedback widget to contact a system administrator").".");
	}
}
