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
 * This file looks a bit different because it is called only by AJAX requests
 * and returns status codes based on it's ability to complete the requested
 * action. In this case, the requested action is to submit a response to an
 * answered quiz questions.
 *
 * 0	Unable to start processing request.
 * 200	There were no errors, the response was recorded successfully.
 * 400: Unable to save response because no aevaluation_id was provided.
 * 401: Unable to save response because no valid aevaluation_id was provided.
 * 402: Attempted to submit a response to a question before the quiz release period.
 * 404: Attempted to submit a response to a question when they have already completed the quiz the maximum number of times.
 * 405: Unable to locate a current progress record.
 * 406: Unable to update the evaluation_progress.updated_date field to the current timestamp.
 * 407: Quiz Question ID was not provided.
 * 408: Quiz Question Response ID was not provided.
 * 409: Unable to record a response to a question.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
 * @version $Id: save-response.inc.php 1170 2010-05-01 14:35:01Z simpson $
 *
*/

if((!defined("PARENT_INCLUDED")) || (!defined("IN_PUBLIC_EVALUATIONS"))) {
	/**
	 * @exception 0: Unable to start processing request.
	 */
	echo 0;
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	/**
	 * @exception 0: Unable to start processing request.
	 */
	echo 0;
	exit;
}
ob_clear_open_buffers();

if ($RECORD_ID) {
	if (isset($_POST["event_id"]) && ($event_id = clean_input($_POST["event_id"], "int"))) {
		if (isset($_POST["preceptor_proxy_id"]) && ($tmp_input = clean_input($_POST["preceptor_proxy_id"], "int"))) {
			$preceptor_proxy_id = $tmp_input;
		}
		$output = Classes_Evaluation::getPreceptorSelect($RECORD_ID, $event_id, $ENTRADA_USER->getID(), (isset($preceptor_proxy_id) && $preceptor_proxy_id ? $preceptor_proxy_id : 0));
		if ($output) {
			echo "<br /><div class=\"content-small\">Please choose a clerkship preceptor to evaluate: \n";
			echo $output;
			echo "</div>\n";
		}
	}
}
exit;