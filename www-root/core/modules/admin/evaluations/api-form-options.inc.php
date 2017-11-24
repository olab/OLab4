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
 * This API file returns an HTML table of the possible targets for the selected
 * evaluation form. For instance, if the selected form is a course evaluation
 * it will return HTML used by the administrator to select which course / courses
 * they wish to evaluate.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 */
if (!defined("IN_EVALUATIONS")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("evaluation", "create", false)) {
	add_error("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$GROUP."] and role [".$ROLE."] do not have access to this module [".$MODULE."]");
} else {
	if (isset($_POST["ajax"]) && ($_POST["ajax"] == 1)) {
		$use_ajax = true;
	} else {
		$use_ajax = false;
	}
	if ($use_ajax) {
		/**
		 * Clears all open buffers so we can return a plain response for the Javascript.
		 */
		ob_clear_open_buffers();

		$PROCESSED = Classes_Evaluation::processTargets($_POST);
	}
	if ($PROCESSED["eform_id"]) {
		Classes_Evaluation::getTargetControls($PROCESSED, "", $PROCESSED["eform_id"]);
	}

	/**
	 * If we are return this via Javascript,
	 * exit now so we don't get the entire page.
	 */
	if ($use_ajax) {
		exit;
	}
}