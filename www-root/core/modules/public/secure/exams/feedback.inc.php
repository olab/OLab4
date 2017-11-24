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
 * This section is a secure wrapper which loads the exam feedback interface
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Daniel Noji <dnoji@mednet.ucla.edu>
 * @copyright Copyright 2016 Regents of The University of California. All Rights Reserved.
 *
 */

if((!defined("PARENT_INCLUDED")) || (!defined("IN_PUBLIC_EXAMS"))) {
    exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
}
if (isset($_GET["progress_id"]) && $tmp_input = clean_input($_GET["progress_id"], "int")) {
    $PROCESSED["exam_progress_id"] = $tmp_input;
}

if (isset($PROCESSED["exam_progress_id"]) && $PROCESSED["exam_progress_id"] != ""){
    $exam_progress = Models_Exam_Progress::fetchRowByID($PROCESSED["exam_progress_id"]);
    if ($exam_progress){
        $exam_post = $exam_progress->getExamPost();
        if ($exam_post) {
            $access_file = Models_Secure_AccessFiles::fetchAllByResourceTypeResourceID("exam_post", $exam_post->getID());
            /**
             * Validate Keys
             */
            $grant_access = false;
            if ($access_file) {
                $access_keys = Models_Secure_AccessKeys::fetchAllByResourceTypeResourceID("exam_post", $exam_post->getID());
                if ($access_keys) {
                    foreach ($access_keys as $access_key) {
                        if (hash("sha256", getCurrentUrl() . $access_key->getKey()) === trim($_SERVER["HTTP_X_SAFEEXAMBROWSER_REQUESTHASH"])) {
                            $grant_access = true;
                        }
                    }
                }
            } else {
                add_error("An error occurred while attempting to validate your Safe Exam Browser Configuration. Please contact an administrator for help");
            }
            if (true === $grant_access) {
                $MODULE = "exams";
                $EXAM_MODE = "secure";

                require_once(ENTRADA_ABSOLUTE . "/core/modules/public/exams/feedback.inc.php");
            } else {
                add_error("You must use an approved version of Safe Exam Browser to attempt this exam.");
                echo display_error();
                application_log("error", "The SEB header did not match any of the keys added for this exam");
            }
        } else {
            add_error("The Exam Post could not be located in the system. Please try again or contact a system administrator.");
            echo display_error();
        }
    } else {
        add_error("Your exam attempt could not be located in the system. Please try again or contact a system administrator.");
        echo display_error();
    }
} else {
    add_error("You must provide a valid Exam Progress ID. Please try again.");
    echo display_error();
}