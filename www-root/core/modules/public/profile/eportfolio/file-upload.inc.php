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
} elseif (!$ENTRADA_ACL->amIAllowed("eportfolio", "read")) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	
	switch ($STEP) {
		case 2 :
			
			if (isset($_POST["pfartifact_id"]) && $tmp_input = clean_input($_POST["pfartifact_id"], "int")) {
				$PROCESSED["pfartifact_id"] = $tmp_input;
			} else {
				add_error("Invalid pfartifact ID");
			}
			
			if (isset($_POST["description"]) && $tmp_input = clean_input($_POST["description"], array("trim", "allowedtags"))) {
				$PROCESSED["description"] = $tmp_input;
			} else {
				add_error("Invalid description");
			}
			
			if (isset($_FILES["file"]) && !empty($_FILES["file"])) {
				$filename = clean_input($_FILES["file"]["name"], array("trim", "striptags"));
			} else {
				add_error("No file");
			}
			
			if (!$ERROR) {
				$PROCESSED["proxy_id"] = $ENTRADA_USER->getID(); // @todo: this needs to be fixed
				$PROCESSED["submitted_date"] = time();
				$PROCESSED["reviewed_date"] = "0";
				$PROCESSED["flag"] = "0";
				$PROCESSED["flagged_by"] = "0";
				$PROCESSED["flagged_date"] = "0";
				$PROCESSED["order"] = "0";
				$PROCESSED["updated_date"] = date(time());
				$PROCESSED["updated_by"] = $ENTRADA_USER->getID();
				$PROCESSED["_edata"] = serialize(array("description" => $PROCESSED["description"], "filename" => $filename));
				$PROCESSED["type"] = "FILE";
				
				$pentry = new Models_Eportfolio_Entry();
				
				if ($pentry->fromArray($PROCESSED)->insert()) {
					echo "inserted";
					
					$pfartifact = $pentry->getPfartifact();
	
					$pfolder = $pfartifact->getFolder();

					$portfolio = $pfolder->getPortfolio();
					$file_realpath = EPORTFOLIO_STORAGE_PATH."/portfolio-".$portfolio->getID()."/folder-".$pfolder->getID()."/artifact-".$pfartifact->getID()."/user-".$pentry->getProxyID()."/".$pentry->getID();
					
					if (!copy($_FILES["file"]["tmp_name"], $file_realpath)) {
						add_error("Failed to copy file.");
					} else {
						echo "copied";
					}
				}
				
			} else {
				echo display_error();
			}
			
		break;
	}
	
	switch ($STEP) {
		case 1 :
			?>
			<h1>Entrada ePortfolio</h1>
			<?php

				$eportfolio = Models_Eportfolio::fetchRowByGroupID($ENTRADA_USER->getCohort());

				echo "<h2>".$eportfolio->getPortfolioName()."</h2>";

			?>
			<form action="<?php echo ENTRADA_URL; ?>/profile/eportfolio?section=file-upload" method="POST" enctype="multipart/form-data" class="form-horizontal">
				<input type="hidden" name="pfartifact_id" value="1826" />
				<input type="hidden" name="step" value="2" />
				<div class="control-group">
					<label class="control-label" for="desc">Description</label>
					<div class="controls">
						<textarea id="desc" name="description"></textarea>
					</div>
				</div>
				<div class="control-group">
					<label class="control-label" for="file"></label>
					<div class="controls">
						<input type="file" name="file" id="file" />
					</div>
				</div>
				<input type="submit" class="btn btn-primary" value="Submit" />
			</form>
			<?php
		break;
	}
}