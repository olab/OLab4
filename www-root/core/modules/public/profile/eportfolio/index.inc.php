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
	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/eportfolio.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
	$HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = '".ENTRADA_URL."'; var PROXY_ID = '".$ENTRADA_USER->getProxyId()."';</script>";
	load_rte("minimal");
	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/ckeditor/adapters/jquery.js\"></script>\n";
	?>
	<h1>My Portfolio</h1>
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
	<div id="msg"></div>
	<?php
	$eportfolio = Models_Eportfolio::fetchRowByGroupID($ENTRADA_USER->getCohort());
	if ($eportfolio) {
		$folders = $eportfolio->getFolders();
        if ($folders) {
		?>
		<div class="btn-group">
			<a class="btn btn-info btn-large dropdown-toggle" data-toggle="dropdown" href="#">
				<i class="icon-folder-open icon-white"></i> 
				<span id="current-folder"><?php echo $folders[0]->getTitle(); ?></span>
				<span class="caret"></span>
			</a>
			<ul class="dropdown-menu" id="folder-list">
			<?php 
			foreach ($folders as $folder) { ?>
				<li>
					<a href="#" data-id="<?php echo $folder->getID(); ?>" class="folder-item">
						<i class="icon-folder-open"></i> 
						<span><?php echo $folder->getTitle(); ?></span>
					</a>
				</li>
			<?php
			}
			?>
				<li class="divider"></li>
				<li><a href="<?php echo ENTRADA_URL; ?>/profile/eportfolio?section=export-portfolio">Export My Portfolio</a></li>
			</ul>
		</div>
        <div class="btn-group pull-right">
			<a class="btn btn-large btn-success dropdown-toggle" data-toggle="dropdown" href="#">
				<i class="icon-white icon-folder-open"></i> My Artifacts
				<span class="caret"></span>
			</a>
			<ul class="dropdown-menu" id="artifact-list">
				<li id="entries-required" class="entries-list-title"><strong>Artifacts that require entries</strong></li>
				<li id="entries-attached" class="entries-list-title"><strong>Artifacts with attached entries</strong></li>
				<li id="entries-user" class="entries-list-title"><strong>Artifacts created by you</strong></li>
				<li class="divider"></li>
				<li>
					<a href="#" data-id="2" id="create-artifact" data-toggle="modal" data-target="#portfolio-modal">
						<i class="icon-plus"></i> Create My Own Artifact
					</a>
				</li>
			</ul>
		</div>
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
				<a href="#" class="btn pull-left" data-dismiss="modal">Cancel</a>
				<a href="#" class="btn btn-primary" id="save-button">Save changes</a>
			</div>
		</div>
        <?php 
        } else {
            echo display_notice("Sorry, but your eportfolio not yet have any folders created.");
        }
	} else {
		echo display_notice("Sorry, but your class does not yet have an eportfolio created. If you are receiving this message in error please use the feedback widget to contact a system administrator.");
	}
}

