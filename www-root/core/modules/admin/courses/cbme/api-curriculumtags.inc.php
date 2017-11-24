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
 * API to handle course curriculum tag sets
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_CBME"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("course", "update", false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

    ob_clear_open_buffers();

    $request_method = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));

    $request = ${"_" . $request_method};

    if (isset($request["method"]) && $tmp_input = clean_input($request["method"], array("trim", "striptags"))) {
        $method = $tmp_input;
    } else {
        add_error("No method provided.");
    }

    if (!$ERROR) {
        switch ($request_method) {
            case "POST" :
                switch ($method) {
                    case "upload-curriculum-tag-set" :
                        if (isset($_POST["curriculum_tag_shortname"]) && $tmp_input = clean_input($_POST["curriculum_tag_shortname"], array("trim", "striptags"))) {
                            $PROCESSED["curriculum_tag_shortname"] = $tmp_input;
                        } else {
                            add_error("You must select a <strong>Curriculum Tag Set</strong>");
                        }

                        if (isset($_POST["course_id"]) && $tmp_input = clean_input($_POST["course_id"], array("trim", "int"))) {
                            $PROCESSED["course_id"] = $tmp_input;
                        } else {
                            add_error("You must provide a <strong>Course ID</strong>");
                        }

                        if (!$ERROR) {
                            if (isset($_FILES["files"]["name"])) {
                                switch ($_FILES["files"]["error"]) {
                                    case 0 :
                                        $objective_set_model = new Models_ObjectiveSet();
                                        $objective_set = $objective_set_model->fetchRowByShortname($PROCESSED["curriculum_tag_shortname"]);
                                        if (!$objective_set) {
                                            add_error("An Error occurred while attempting to fetch a curriculum tag set.");
                                        } else {
                                            $objective_model = new Models_Objective();
                                            $curriculum_tag_set_objectives = $objective_model->fetchAllChildrenByObjectiveSetID($objective_set->getID());
                                            if ($curriculum_tag_set_objectives) {
                                                add_error("You have already imported a list of <strong>". $objective_set->getTitle() ."</strong>.");
                                            }
                                        }

                                        if (!$ERROR) {
                                            $finfo = finfo_open(FILEINFO_MIME_TYPE);
                                            $PROCESSED["file_type"] = finfo_file($finfo, $_FILES["files"]["tmp_name"]);
                                            if (in_array($PROCESSED["file_type"], array("text/csv", "text/plain"))) {
                                                if ($_FILES["files"]["tmp_name"]) {
                                                    ini_set("auto_detect_line_endings", true);
                                                    $fp = fopen($_FILES["files"]["tmp_name"], "r");
                                                    if ($fp) {
                                                        $file_error = false;
                                                        while (($data = fgetcsv($fp)) !== false) {
                                                            if (empty($data[0]) || empty($data[1])) {
                                                                $file_error = true;
                                                            }
                                                        }

                                                        if (!$file_error) {
                                                            rewind($fp);
                                                            fgetcsv($fp);

                                                            $objective_set_model = new Models_ObjectiveSet();
                                                            $objectives = array();
                                                            $objective_parent = $objective_set_model->fetchRowByIDObjectiveParent($objective_set->getID(), $ENTRADA_USER->getActiveOrganisation());
                                                            $objective_order = 0;

                                                            while (($data = fgetcsv($fp)) !== false) {
                                                                $html = "";
                                                                switch ($PROCESSED["curriculum_tag_shortname"]) {
                                                                    case "epa" :
                                                                        $objective = array(
                                                                            "objective_code" => clean_input($data[0], array("trim", "striptags")),
                                                                            "objective_name" => clean_input($data[1], array("trim", "striptags")),
                                                                            "objective_description" => preg_replace("/[\n\r]/", "<br /><br />", clean_input($data[2], array("allowedtags"))),
                                                                            "objective_secondary_description" => clean_input($data[3], array("trim", "striptags")),
                                                                            "objective_parent" => ($objective_parent ? $objective_parent["objective_id"] : 0),
                                                                            "objective_set_id" => $objective_set->getID(),
                                                                            "objective_order" => $objective_order,
                                                                            "overall_order" => null,
                                                                            "objective_loggable" => 0,
                                                                            "objective_active" => 1,
                                                                            "updated_date" => time(),
                                                                            "updated_by" => $ENTRADA_USER->getActiveId()
                                                                        );
                                                                        break;
                                                                    case "milestone" :
                                                                        $objective = array(
                                                                            "objective_code" => clean_input($data[0], array("trim", "striptags")),
                                                                            "objective_name" => clean_input($data[1], array("trim", "striptags")),
                                                                            "objective_parent" => ($objective_parent ? $objective_parent["objective_id"] : 0),
                                                                            "objective_set_id" => $objective_set->getID(),
                                                                            "objective_order" => $objective_order,
                                                                            "overall_order" => null,
                                                                            "objective_loggable" => 0,
                                                                            "objective_active" => 1,
                                                                            "updated_date" => time(),
                                                                            "updated_by" => $ENTRADA_USER->getActiveId()
                                                                        );
                                                                        break;
                                                                }

                                                                $objective_model = new Models_Objective();
                                                                if ($objective_model->fromArray($objective)->insert()) {
                                                                    $cbme_course_objective = array(
                                                                        "objective_id" => $objective_model->getID(),
                                                                        "course_id" => $PROCESSED["course_id"],
                                                                        "created_date" => time(),
                                                                        "created_by" => $ENTRADA_USER->getActiveId()
                                                                    );

                                                                    $cbme_course_objective_model = new Models_CBME_CourseObjective();
                                                                    if (!$cbme_course_objective_model->fromArray($cbme_course_objective)->insert()) {
                                                                        add_error("A problem occurred while attempting to insert at least one of the provided objectives.");
                                                                        application_log("error", "There was an error inserting an objective. Database said: " . $db->ErrorMsg());
                                                                    }

                                                                    $objective_organisation = array(
                                                                        "objective_id" => $objective_model->getID(),
                                                                        "organisation_id" => $ENTRADA_USER->getActiveOrganisation()
                                                                    );

                                                                    $objective_organisation_model = new Models_Objective_Organisation();
                                                                    if (!$objective_organisation_model->fromArray($objective_organisation)->insert()) {
                                                                        add_error("A problem occurred while attempting to insert at least one of the provided objectives.");
                                                                        application_log("error", "There was an error inserting an objective. Database said: " . $db->ErrorMsg());
                                                                    }

                                                                    if (!$ERROR) {
                                                                        $objectives[] = $objective;
                                                                    }
                                                                } else {
                                                                    add_error("A problem occurred while attempting to insert at least one of the provided objectives.");
                                                                    application_log("error", "There was an error inserting an objective. Database said: " . $db->ErrorMsg());
                                                                }
                                                                $objective_order++;
                                                            }
                                                        } else {
                                                            add_error("The uploaded file is <strong>missing required information</strong>, please update the file and re-upload it.");
                                                        }
                                                        fclose($fp);
                                                    }
                                                }
                                            } else {
                                                add_error("Invalid file type provided, please provide a CSV file.");
                                            }
                                        }
                                        break;
                                    case 1 :
                                    case 2 :
                                        add_error("The uploaded file exceeds the allowed file size limit.");
                                        break;
                                    case 3 :
                                        add_error("The file that uploaded did not complete the upload process or was interupted. Please try again.");
                                        break;
                                    case 4 :
                                        add_error("You did not select a file on your computer to upload. Please select a file.");
                                        break;
                                    case 5 :
                                        add_error("A problem occurred while attempting to upload the file; the MEdTech Unit has been informed of this error, please try again later.");
                                        break;
                                    case 6 :
                                    case 7 :
                                        add_error("Unable to store the new file on the server; the MEdTech Unit has been informed of this error, please try again later.");
                                        break;
                                }
                            } else {
                                add_error("You did not select a file on your computer to upload. Please select a file.");
                            }
                        }

                        if (!$ERROR) {
                            echo json_encode(array("status" => "success", "data" => array("objectives" => $objectives)));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        }
                        break;
                }
                break;
            case "GET" :
                switch ($method) {
                    case "download-epa-csv" :
                        header("Pragma: public");
                        header("Expires: 0");
                        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                        header("Content-Type: application/force-download");
                        header("Content-Type: application/octet-stream");
                        header("Content-Type: text/csv");
                        header("Content-Disposition: attachment; filename=\"epa-template.csv\"");
                        header("Content-Transfer-Encoding: binary");

                        $fp = fopen("php://output", "w");
                        $row = array("Code", "Title", "Detailed Description", "Entrustment (When required)");
                        fputcsv($fp, $row);
                        fclose($fp);
                        break;
                    case "download-milestone-csv" :
                        header("Pragma: public");
                        header("Expires: 0");
                        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                        header("Content-Type: application/force-download");
                        header("Content-Type: application/octet-stream");
                        header("Content-Type: text/csv");
                        header("Content-Disposition: attachment; filename=\"milestone-template.csv\"");
                        header("Content-Transfer-Encoding: binary");

                        $fp = fopen("php://output", "w");
                        $row = array("Code", "Title");
                        fputcsv($fp, $row);
                        fclose($fp);
                        break;
                }
                break;
        }
    }
    exit;
}