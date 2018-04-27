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
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2014 Regents of The University of California. All Rights Reserved.
 *
*/
if (!defined("IN_EXAMS")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: " . ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed(new ExamResource($_POST["exam-id"], true), "update")) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$GROUP."] and role [".$ROLE."] does not have access to this module [".$MODULE."]");
} else {
    $csv = json_decode($_POST['csv']);

    if (isset($csv) && !is_array($csv)) {
        $csv_array = json_decode($csv);
    }

    ob_clear_open_buffers();

    // Output headers
    $csv_filename = "Exam Progress Data";
    header("Content-Description: File Transfer");
    header("Content-Type: application/csv");
    header("Content-Disposition: attachment; filename=".preg_replace("/[^a-z0-9]+/", "_", strtolower($csv_filename)).".csv");
    header("Pragma: public");
    header("Expires: 0");

    // Open up stdout to push CSV to
    $out = fopen("php://output", "w");

    // Output the CSV
    $row_array = array();
    if (isset($csv_array) && is_array($csv_array)) {
        foreach ($csv_array as $row) {
            $row = str_getcsv($row, ",");
            fputcsv($out, $row);
        }
    }

//     Close stdout and exit
    fclose($out);

    exit();

}