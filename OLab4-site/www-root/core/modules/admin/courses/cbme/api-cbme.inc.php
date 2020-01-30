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
set_time_limit(0);
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_CBME"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: " . ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("coursecontent", "update", false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:" . html_encode($AGENT_CONTACTS["administrator"]["email"]) . "\">" . html_encode($AGENT_CONTACTS["administrator"]["name"]) . "</a> for assistance.");

    echo display_error();

    application_log("error", "Group [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] . "] and role [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"] . "] does not have access to this module [" . $MODULE . "]");
} else {

    ob_clear_open_buffers();

    $request_method = strtoupper(clean_input($_SERVER["REQUEST_METHOD"], "alpha"));

    $request = ${"_" . $request_method};

    if (isset($request["method"]) && $tmp_input = clean_input($request["method"], array("trim", "striptags"))) {
        $method = $tmp_input;
    } else {
        add_error("No method provided.");
    }

    if (isset($request["search_term"]) && $tmp_input = clean_input($request["search_term"], array("trim", "striptags"))) {
        $search_term = $tmp_input;
    } else {
        $search_term = "";
    }

    /*
     * If a course_id is provided in the request, make sure that the course exists and that the user has access to it.
     * At this point, I can't assume that all API calls have a course_id, although we should be able to assume this.
     */
    if (isset($request["course_id"]) && $tmp_input = clean_input($request["course_id"], array("trim", "int"))) {
        $course = Models_Course::get($tmp_input);
        if ($course && $ENTRADA_ACL->amIAllowed(new CourseContentResource($course->getID(), $course->getOrganisationID()), "update")) {
            $PROCESSED["course_id"] = $tmp_input;
        } else {
            add_error("The provided <strong>Course ID</strong> is invalid.");
            application_log("error", "Attempt to access the api-cbme for a course_id [" . $tmp_input . "] the user did not have access to or the course was not found.");
        }
    }

    if (!$ERROR) {
        switch ($request_method) {
            case "POST" :
                switch ($method) {
                    case "save-curriculum-tag-option" :
                        if (isset($request["course_id"]) && $tmp_input = clean_input($request["course_id"], array("trim", "int"))) {
                            $PROCESSED["course_id"] = $tmp_input;
                        } else {
                            add_error("You must provide a <strong>Course ID</strong>");
                        }

                        $settings = new Entrada_Settings();
                        $cbme_standard_kc_ec_objectives = (int) $settings->read("cbme_standard_kc_ec_objectives");

                        if ($cbme_standard_kc_ec_objectives || (isset($request["course_cbme_standard_kc_ec_objectives"]) && clean_input($request["course_cbme_standard_kc_ec_objectives"], array("trim", "int")))) {
                            $PROCESSED["course_cbme_standard_kc_ec_objectives"] = 1;
                        } else {
                            $PROCESSED["course_cbme_standard_kc_ec_objectives"] = 0;
                        }

                        if (isset($request["curriculum_tag_option"])) {
                            if ($tmp_input = clean_input($request["curriculum_tag_option"], array("trim", "int"))) {
                                $PROCESSED["curriculum_tag_option"] = $tmp_input;
                            } else {
                                $PROCESSED["curriculum_tag_option"] = 0;
                            }
                        } else {
                            add_error("You must select a <strong>Curriculum tag Option</strong>");
                        }

                        if (!$ERROR) {
                            $course = Models_Course::get($PROCESSED["course_id"]);
                            if ($course) {
                                $course_settings = new Entrada_Course_Settings($PROCESSED["course_id"], $ENTRADA_USER->getActiveOrganisation(), $ENTRADA_USER->getActiveId());
                                if (!$course_settings->write("cbme_standard_kc_ec_objectives", $PROCESSED["course_cbme_standard_kc_ec_objectives"])) {
                                    add_error($translate->_("An error occurred while attempting to save the competency option. Please try again at a later time."));
                                }

                                if (!$course->fromArray(array("cbme_milestones" => $PROCESSED["curriculum_tag_option"]))->update()) {
                                    add_error($translate->_("An error occurred while attempting to save the curriculum tag option. Please try again at a later time."));
                                }
                            } else {
                                add_error("No course was found.");
                            }
                        }

                        if (!$ERROR) {
                            echo json_encode(array("status" => "success", "data" => array($translate->_("Successfully saved the selected competency and curriculum tag options."))));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        }
                        break;
                    case "upload-curriculum-tag-set" :
                        if (isset($request["curriculum_tag_shortname"]) && $tmp_input = clean_input($request["curriculum_tag_shortname"], array("trim", "striptags"))) {
                            $PROCESSED["curriculum_tag_shortname"] = $tmp_input;
                        } else {
                            add_error("You must select a <strong>Curriculum Tag Set</strong>");
                        }

                        if (isset($request["course_id"]) && $tmp_input = clean_input($request["course_id"], array("trim", "int"))) {
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
                                        }

                                        if (!$ERROR) {
                                            $objective_code_exclusion = array();
                                            $objective_model = new Models_Objective();
                                            $finfo = finfo_open(FILEINFO_MIME_TYPE);
                                            $PROCESSED["file_type"] = finfo_file($finfo, $_FILES["files"]["tmp_name"]);
                                            if (in_array($PROCESSED["file_type"], array("text/csv", "text/plain"))) {
                                                if ($_FILES["files"]["tmp_name"]) {
                                                    ini_set("auto_detect_line_endings", true);
                                                    $fp = fopen($_FILES["files"]["tmp_name"], "r");
                                                    if ($fp) {

                                                        $cbme_file_upload_history = new Models_CBME_UploadHistory();
                                                        $cbme_file_upload_history->storeFileUploadHistory(
                                                            $_FILES["files"]["tmp_name"],
                                                            $_FILES["files"]["name"],
                                                            $PROCESSED["file_type"],
                                                            $ENTRADA_USER->getActiveID(),
                                                            $PROCESSED["course_id"],
                                                            $PROCESSED["curriculum_tag_shortname"]
                                                        );

                                                        $file_error = false;
                                                        /**
                                                         * @todo: objective code validation needs to be added in each case below to ensure that the provided code matches the expected format of the type of objective being added.
                                                         */
                                                        while (($data = fgetcsv($fp)) !== false) {
                                                            if (array(null) !== $data) {
                                                                switch ($PROCESSED["curriculum_tag_shortname"]) {
                                                                    case "milestone" :
                                                                        if (empty($data[1]) || empty($data[2])) {
                                                                            if (empty($data[1]) && empty($data[2])) {
                                                                                continue;
                                                                            }
                                                                            $file_error = true;
                                                                        }
                                                                        break;
                                                                    case "epa" :
                                                                    case "kc":
                                                                    case "ec":
                                                                        if (empty($data[0]) || empty($data[1])) {
                                                                            if (empty($data[0]) && empty($data[1])) {
                                                                                continue;
                                                                            }
                                                                            $file_error = true;
                                                                        }
                                                                        break;
                                                                }
                                                            }
                                                        }

                                                        if (!$file_error) {
                                                            rewind($fp);
                                                            fgetcsv($fp);

                                                            $objective_set_model = new Models_ObjectiveSet();
                                                            $objective_parent = $objective_set_model->fetchRowByIDObjectiveParent($objective_set->getID(), $ENTRADA_USER->getActiveOrganisation());
                                                            $epa_order = 0; $kc_order = 0; $ec_order = 0; $milestone_order = 0;

                                                            // Initialize an objective tree object
                                                            $tree_object = new Entrada_CBME_ObjectiveTree(array(
                                                                "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                                                                "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                                                                "course_id" => $PROCESSED["course_id"]
                                                            ));

                                                            if (!$tree_object->getRootNodeID()) {
                                                                // Initialize a new tree. By default, this will create a primary tree if no other trees exist.
                                                                $tree_object->createNewTree();
                                                            }

                                                            while (($data = fgetcsv($fp)) !== false) {
                                                                $milestone_objective_id = 0;
                                                                if (empty($data[0]) && empty($data[1])) {
                                                                    continue;
                                                                }
                                                                $html = "";

                                                                /**
                                                                 * The data in this $milestone variable won't always actually be a milestone code
                                                                 */
                                                                $skip = false;
                                                                $skipped_entries = $translate->_("The following entries were <b>not uploaded</b>: </br><ul>");
                                                                $objective = array();

                                                                switch ($PROCESSED["curriculum_tag_shortname"]) {
                                                                    case "epa" :
                                                                        $code = clean_input($data[0], array("trim", "striptags", "msword"));
                                                                        $name = clean_input($data[1], array("trim", "striptags", "msword"));
                                                                        $description = (isset($data[2]) ? (clean_input($data[2], array("allowedtags", "msword")) ? preg_replace("/[\n\r]/", "<br /><br />", $data[2]) : NULL) : NULL);
                                                                        $secondary_description = (isset($data[3]) ? (clean_input($data[3], array("trim", "striptags", "msword")) ? $data[3] : NULL) : NULL);
                                                                        $objective_parent_id = ($objective_parent ? $objective_parent["objective_id"] : 0);
                                                                        $order = $epa_order;

                                                                        if ($code == false || $name == false) {
                                                                            $skipped_entries .= sprintf($translate->_("<li>%s %s - Invalid characters</li>"), $data[0], $data[1]);
                                                                            $skip = true;
                                                                        }

                                                                        if (defined("CBME_EPA_PATTERN")) {
                                                                            $valid_code = preg_match(CBME_EPA_PATTERN, $code);
                                                                            if (!$valid_code) {
                                                                                $skipped_entries .= sprintf($translate->_("<li>%s - Invalid code</li>"), $data[0]);
                                                                                $skip = true;
                                                                            }
                                                                        }
                                                                        $epa_order++;
                                                                        break;
                                                                    case "milestone" :
                                                                        $code = clean_input($data[1], array("trim", "striptags", "msword"));
                                                                        $name = clean_input($data[2], array("trim", "striptags", "msword"));
                                                                        $description = (!empty($data[3]) ? (clean_input($data[3], array("striptags", "msword")) ? $data[3] : NULL) : NULL);
                                                                        $secondary_description = NULL;
                                                                        $objective_parent_id = ($objective_parent ? $objective_parent["objective_id"] : 0);
                                                                        $order = $milestone_order;

                                                                        if (clean_input($data[1], array("trim", "striptags", "msword")) == false || clean_input($data[2], array("trim", "striptags", "msword")) == false) {
                                                                            $skipped_entries .= "<li>" . $data[1] . " " . $data[2] . "</li>";
                                                                            $skip = true;
                                                                        }

                                                                        if (defined("CBME_MILESTONE_PATTERN")) {
                                                                            $valid_code = preg_match(CBME_MILESTONE_PATTERN, $code);
                                                                            if (!$valid_code) {
                                                                                $skipped_entries .= sprintf($translate->_("<li>%s - Invalid code</li>"), $data[1]);
                                                                                $skip = true;
                                                                            }
                                                                        }
                                                                        $milestone_order++;
                                                                        break;
                                                                    case "kc" :
                                                                    case "ec" :
                                                                        $code = clean_input($data[0], array("trim", "striptags", "msword"));
                                                                        $name = clean_input($data[1], array("trim", "striptags", "msword"));
                                                                        $description = (!empty($data[2]) ? (clean_input($data[2], array("striptags", "msword")) ? $data[2] : NULL) : NULL);
                                                                        $secondary_description = NULL;
                                                                        $objective_parent_id = ($objective_parent ? $objective_parent["objective_id"] : 0);

                                                                        if ($PROCESSED["curriculum_tag_shortname"] == "kc") {
                                                                            $order = $kc_order;
                                                                            if (defined("CBME_KEY_COMPETENCY_PATTERN")) {
                                                                                $valid_code = preg_match(CBME_KEY_COMPETENCY_PATTERN, $code);
                                                                                if (!$valid_code) {
                                                                                    $skipped_entries .= sprintf($translate->_("<li>%s - Invalid code</li>"), $data[0]);
                                                                                    $skip = true;
                                                                                }
                                                                            }
                                                                            $kc_order++;
                                                                        } else {
                                                                            if (defined("CBME_ENABLING_COMPETENCY_PATTERN")) {
                                                                                $valid_code = preg_match(CBME_ENABLING_COMPETENCY_PATTERN, $code);
                                                                                if (!$valid_code) {
                                                                                    $skipped_entries .= sprintf($translate->_("<li>%s - Invalid code</li>"), $data[0]);
                                                                                    $skip = true;
                                                                                }
                                                                            }
                                                                            $order = $ec_order;
                                                                            $ec_order++;
                                                                        }

                                                                        if (clean_input($data[0], array("trim", "striptags", "msword")) == false || clean_input($data[1], array("trim", "striptags", "msword")) == false) {
                                                                            $skipped_entries .= "<li>" . $data[0] . " " . $data[1] . "</li>";
                                                                            $skip = true;
                                                                        }
                                                                        break;
                                                                }

                                                                if ($skip == false) {
                                                                    $objective = array(
                                                                        "objective_code" => $code,
                                                                        "objective_name" => $name,
                                                                        "objective_description" => $description,
                                                                        "objective_secondary_description" => $secondary_description,
                                                                        "objective_parent" => $objective_parent_id,
                                                                        "objective_set_id" => $objective_set->getID(),
                                                                        "objective_order" => $epa_order,
                                                                        "overall_order" => null,
                                                                        "objective_loggable" => 0,
                                                                        "objective_active" => 1,
                                                                        "updated_date" => time(),
                                                                        "updated_by" => $ENTRADA_USER->getActiveId()
                                                                    );
                                                                    $objective_exists = $objective_model->fetchRowByObjectiveCodeCourseID($PROCESSED["curriculum_tag_shortname"], $objective["objective_code"], $PROCESSED["course_id"]);
                                                                    if (!$objective_exists) {
                                                                        if ($db->AutoExecute("global_lu_objectives", $objective, "INSERT") && $OBJECTIVE_ID = $db->Insert_Id()) {
                                                                            if ($objective_set->getShortname() != "role" && $objective_set->getShortname() != "stage") {
                                                                                $cbme_course_objective = array(
                                                                                    "objective_id" => $OBJECTIVE_ID,
                                                                                    "course_id" => $PROCESSED["course_id"],
                                                                                    "created_date" => time(),
                                                                                    "created_by" => $ENTRADA_USER->getActiveId()
                                                                                );

                                                                                $cbme_course_objective_model = new Models_CBME_CourseObjective();
                                                                                if (!$cbme_course_objective_model->fromArray($cbme_course_objective)->insert()) {
                                                                                    add_error($translate->_("A problem occurred while attempting to insert at least one of the provided objectives."));
                                                                                    application_log("error", "There was an error inserting an objective. Database said: " . $db->ErrorMsg());
                                                                                }
                                                                            }

                                                                            $objective_organisation = array(
                                                                                "objective_id" => $OBJECTIVE_ID,
                                                                                "organisation_id" => $ENTRADA_USER->getActiveOrganisation()
                                                                            );

                                                                            $objective_organisation_model = new Models_Objective_Organisation();
                                                                            if (!$objective_organisation_model->fromArray($objective_organisation)->insert()) {
                                                                                add_error($translate->_("A problem occurred while attempting to insert at least one of the provided objectives."));
                                                                                application_log("error", "There was an error inserting an objective. Database said: " . $db->ErrorMsg());
                                                                            }
                                                                        } else {
                                                                            add_error($translate->_("A problem occurred while attempting to insert at least one of the provided objectives."));
                                                                            application_log("error", "There was an error inserting an objective. Database said: " . $db->ErrorMsg());
                                                                        }
                                                                    }

                                                                    /**
                                                                     * If the objective being uploaded is a milestone then build a tree branch
                                                                     */
                                                                    if ($PROCESSED["curriculum_tag_shortname"] == "milestone") {
                                                                        if (!empty($data[0])) {

                                                                            /**
                                                                             * Sanitize each EPA code
                                                                             */
                                                                            $PROCESSED["epa_codes"] = array();
                                                                            $epa_ids = explode(",", $data[0]);
                                                                            if ($epa_ids) {
                                                                                foreach ($epa_ids as $epa_id) {
                                                                                    if ($tmp_input = clean_input($epa_id, array("trim", "striptags"))) {
                                                                                        $PROCESSED["epa_codes"][] = $tmp_input;
                                                                                    }
                                                                                }
                                                                            }

                                                                            /**
                                                                             * Determine the objective_id used when adding the milestone branch node
                                                                             */
                                                                            $existing_milestone = $objective_model->fetchRowByObjectiveCodeCourseID("milestone", $code, $PROCESSED["course_id"]);
                                                                            if ($existing_milestone) {
                                                                                $milestone_objective_id = $existing_milestone["objective_id"];
                                                                            }

                                                                            /**
                                                                             * Get any EPAs that match the supplied EPA code(s)
                                                                             */
                                                                            $course_epas = $objective_model->fetchAllByObjectiveCodeCourseID("epa", $PROCESSED["epa_codes"], $PROCESSED["course_id"]);
                                                                            if ($course_epas && isset($milestone_objective_id) && $milestone_objective_id) {

                                                                                $settings = new Entrada_Settings();
                                                                                $course_settings = new Entrada_Course_Settings($PROCESSED["course_id"]);

                                                                                /*
                                                                                 * If either the system setting or course setting for cbme_standard_kc_ec_objectives is true then use it.
                                                                                 */
                                                                                if ((int) $settings->read("cbme_standard_kc_ec_objectives") || (int) $course_settings->read("cbme_standard_kc_ec_objectives")) {
                                                                                    $cbme_standard_kc_ec_objectives = true;
                                                                                } else {
                                                                                    $cbme_standard_kc_ec_objectives = false;
                                                                                }

                                                                                /**
                                                                                 * There are course specific EPAs which match values from the uploaded spreadsheet
                                                                                 * so an entire branch can be linked
                                                                                 */
                                                                                foreach ($course_epas as $course_epa) {

                                                                                    /**
                                                                                     * Extract the stage code from the EPA code and fetch the corresponding objective
                                                                                     */
                                                                                    $stage_code = substr($course_epa["objective_code"], 0, 1);
                                                                                    $stage = $objective_model->fetchRowByShortnameCode("stage", $stage_code);

                                                                                    /**
                                                                                     * Extract the role code from the EPA code and fetch the corresponding objective
                                                                                     */
                                                                                    $role_code = substr($data[1], 2, 2);
                                                                                    $role = $objective_model->fetchRowByShortnameCode("role", $role_code);

                                                                                    /**
                                                                                     * Extract the key competency and enabling competency codes from the milestone code
                                                                                     */
                                                                                    $code_pieces = explode(".", substr(clean_input($data[1], array("trim", "striptags", "msword")), 2));

                                                                                    /**
                                                                                     * Set the key competency code and fetch its corresponding objective
                                                                                     */
                                                                                    $kc_code = $code_pieces[0];

                                                                                    /**
                                                                                     * Set the enabling competency code and fetch its corresponding objective
                                                                                     */
                                                                                    $ec_code = $code_pieces[0] . "." . $code_pieces[1];

                                                                                    /**
                                                                                     * Set the key and enabling competency codes and fetch their corresponding objectives
                                                                                     */
                                                                                    if ($cbme_standard_kc_ec_objectives) {
                                                                                        $kc = $objective_model->fetchRowByShortnameCode("kc", $kc_code);
                                                                                        $ec = $objective_model->fetchRowByShortnameCode("ec", $ec_code);
                                                                                    } else {
                                                                                        $kc = $objective_model->fetchRowByObjectiveCodeCourseID("kc", $kc_code, $PROCESSED["course_id"]);
                                                                                        $ec = $objective_model->fetchRowByObjectiveCodeCourseID("ec", $ec_code, $PROCESSED["course_id"]);
                                                                                    }

                                                                                    if ($stage && $role && $kc && $ec) {
                                                                                        $branch = array();
                                                                                        $branch[] = $stage["objective_id"];
                                                                                        $branch[] = $course_epa["objective_id"];
                                                                                        $branch[] = $role["objective_id"];
                                                                                        $branch[] = $kc["objective_id"];
                                                                                        $branch[] = $ec["objective_id"];
                                                                                        $branch[] = $milestone_objective_id;

                                                                                        if (!$tree_object->addBranch($tree_object->getRootNodeID(), $branch)) {
                                                                                            add_error($translate->_("An error occurred while attempting to create this branch."));
                                                                                        }
                                                                                    }
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                } else {
                                                                    $skipped_entries .= "</ul>";
                                                                    Entrada_Utilities_Flashmessenger::addMessage($skipped_entries, "warning", $MODULE);
                                                                }

                                                            }
                                                        } else {
                                                            add_error($translate->_("The uploaded file is <strong>missing required information</strong>, please update the file and re-upload it."));
                                                        }
                                                        fclose($fp);
                                                    }
                                                }
                                            } else {
                                                add_error($translate->_("Invalid file type provided, please provide a CSV file."));
                                            }
                                        }
                                        break;
                                    case 1 :
                                    case 2 :
                                        add_error($translate->_("The uploaded file exceeds the allowed file size limit."));
                                        break;
                                    case 3 :
                                        add_error($translate->_("The file that uploaded did not complete the upload process or was interrupted. Please try again."));
                                        break;
                                    case 4 :
                                        add_error($translate->_("You did not select a file on your computer to upload. Please select a file."));
                                        break;
                                    case 5 :
                                        add_error($translate->_("A problem occurred while attempting to upload the file; the MEdTech Unit has been informed of this error, please try again later."));
                                        break;
                                    case 6 :
                                    case 7 :
                                        add_error($translate->_("Unable to store the new file on the server; the MEdTech Unit has been informed of this error, please try again later."));
                                        break;
                                }
                            } else {
                                add_error($translate->_("You did not select a file on your computer to upload. Please select a file."));
                            }
                        }

                        if (!$ERROR) {
                            echo json_encode(array("status" => "success"));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        }
                        break;
                    case "upload-cv-responses" :
                        if (isset($request["course_id"]) && $tmp_input = clean_input($request["course_id"], array("trim", "int"))) {
                            $PROCESSED["course_id"] = $tmp_input;
                        } else {
                            add_error("You must provide a <strong>Course ID</strong>");
                        }

                        if (isset($_FILES["files"]["name"])) {
                            switch ($_FILES["files"]["error"]) {
                                case 0 :
                                    if (!$ERROR) {
                                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                                        $PROCESSED["file_type"] = finfo_file($finfo, $_FILES["files"]["tmp_name"]);
                                        if (in_array($PROCESSED["file_type"], array("text/csv", "text/plain"))) {
                                            if ($_FILES["files"]["tmp_name"]) {
                                                ini_set("auto_detect_line_endings", true);
                                                $fp = fopen($_FILES["files"]["tmp_name"], "r");

                                                if ($fp) {
                                                    fgetcsv($fp);

                                                    $cbme_file_upload_history = new Models_CBME_UploadHistory();
                                                    $cbme_file_upload_history->storeFileUploadHistory(
                                                        $_FILES["files"]["tmp_name"],
                                                        $_FILES["files"]["name"],
                                                        $PROCESSED["file_type"],
                                                        $ENTRADA_USER->getActiveID(),
                                                        $PROCESSED["course_id"],
                                                        "cv_responses"
                                                    );

                                                    $objective_model = new Models_Objective();
                                                    $ctr = 1;

                                                    while (($data = fgetcsv($fp)) !== false) {
                                                        $ctr++;

                                                        if (empty($data[0]) && empty($data[1]) && empty($data[2])) {
                                                            continue;
                                                        }

                                                        if (empty($data[0]) || empty($data[1])) {
                                                            add_error("Either a response code or response text is missing in row: " . $ctr . ", please add the missing data and re-upload your CV responses.");
                                                        }

                                                        if (isset($data[0]) && $tmp_input = clean_input($data[0], array("trim", "html", "msword"))) {
                                                            if (!empty($tmp_input)) {
                                                                $contextual_variable = $objective_model->fetchRowByObjectiveCode($tmp_input);
                                                                if (!$contextual_variable) {
                                                                    add_error("The uploaded file has a <strong>Response Code - $data[0]</strong> that does not match the system codes, please update the file and re-upload it.");
                                                                    break;
                                                                }
                                                            } else {
                                                                add_error("The uploaded file has a <strong>Response Code</strong> that does not match the system codes, please update the file and re-upload it.");
                                                                break;
                                                            }
                                                        } else {
                                                            add_error("There appear to be invalid characters in the Response Code for row: " . $ctr . ". This problem most often occurs when special characters such as comas or apostrophes are copy and pasted from Microsoft Word into your CSV. To resolve this, locate special characters in the indicated row, delete and re-type the character. Once you have done this, save your CSV and re-upload.");
                                                        }

                                                        if (isset($data[1]) && $tmp_input = clean_input($data[1], array("trim", "striptags", "msword"))) {
                                                            if (empty($tmp_input)) {
                                                                add_error("The uploaded file has a <strong>Response</strong> that does not match the system codes, please update the file and re-upload it.");
                                                                break;
                                                            }
                                                        } else {
                                                            add_error("There appear to be invalid characters in the Response for row: " . $ctr . ". This problem most often occurs when special characters such as comas or apostrophes are copy and pasted from Microsoft Word into your CSV. To resolve this, locate special characters in the indicated row, delete and re-type the character. Once you have done this, save your CSV and re-upload.");
                                                        }
                                                    }

                                                    if (!$ERROR) {
                                                        rewind($fp);
                                                        fgetcsv($fp);

                                                        $contextual_variable_order = 0;

                                                        while (($data = fgetcsv($fp)) !== false) {
                                                            if (empty($data[0]) && empty($data[1]) && empty($data[2])) {
                                                                continue;
                                                            }
                                                            $objective_set_model = new Models_ObjectiveSet();
                                                            $contextual_variable_parent = $objective_set_model->fetchRowByShortname("contextual_variable_responses");
                                                            if ($contextual_variable_parent) {
                                                                $contextual_variable = false;

                                                                if ($objective_code = clean_input($data[0], array("trim", "striptags", "msword"))) {
                                                                    $contextual_variable = $objective_model->fetchRowByShortnameCode("contextual_variable", $objective_code);
                                                                }

                                                                $objective_name = clean_input($data[1], array("trim", "striptags", "msword"));
                                                                $objective_description = isset($data[2]) ? clean_input($data[2], array("striptags", "msword")) : "";

                                                                $objective = array(
                                                                    "objective_code" => $objective_code,
                                                                    "objective_name" => $objective_name,
                                                                    "objective_description" => $objective_description,
                                                                    "objective_parent" => $contextual_variable ? $contextual_variable["objective_id"] : 0,
                                                                    "objective_set_id" => $contextual_variable_parent->getID(),
                                                                    "objective_order" => $contextual_variable_order++,
                                                                    "overall_order" => null,
                                                                    "objective_loggable" => 0,
                                                                    "objective_active" => 1,
                                                                    "updated_date" => time(),
                                                                    "updated_by" => $ENTRADA_USER->getActiveId()
                                                                );

                                                                if ($db->AutoExecute("global_lu_objectives", $objective, "INSERT") && $OBJECTIVE_ID = $db->Insert_Id()) {
                                                                    $cbme_course_objective = array(
                                                                        "objective_id" => $OBJECTIVE_ID,
                                                                        "course_id" => $PROCESSED["course_id"],
                                                                        "created_date" => time(),
                                                                        "created_by" => $ENTRADA_USER->getActiveId()
                                                                    );

                                                                    $cbme_course_objective_model = new Models_CBME_CourseObjective();
                                                                    if ($cbme_course_objective_model->fromArray($cbme_course_objective)->insert()) {
                                                                        $objective_organisation = array(
                                                                            "objective_id" => $OBJECTIVE_ID,
                                                                            "organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                                                                            "created_date" => time(),
                                                                            "created_by" => $ENTRADA_USER->getActiveId()
                                                                        );

                                                                        $objective_organisation_model = new Models_Objective_Organisation();
                                                                        if (!$objective_organisation_model->fromArray($objective_organisation)->insert()) {
                                                                            add_error("A problem occurred while attempting to insert at least one of the provided objectives.");
                                                                            application_log("error", "There was an error inserting an objective. Database said: " . $db->ErrorMsg());
                                                                            break;
                                                                        }
                                                                    } else {
                                                                        add_error("A problem occurred while attempting to insert at least one of the provided objectives.");
                                                                        application_log("error", "There was an error inserting an objective. Database said: " . $db->ErrorMsg());
                                                                        break;
                                                                    }
                                                                } else {
                                                                    add_error("A problem occurred while attempting to insert at least one of the provided objectives.");
                                                                    application_log("error", "There was an error inserting an objective. Database said: " . $db->ErrorMsg());
                                                                    break;
                                                                }

                                                            } else {
                                                                add_error("A problem occurred while attempting to insert at least one of the provided objectives.");
                                                                application_log("error", "There was an error inserting an objective. Database said: " . $db->ErrorMsg());
                                                                break;
                                                            }
                                                        }
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
                                    add_error("The file that uploaded did not complete the upload process or was interrupted. Please try again.");
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

                        if (!$ERROR) {
                            echo json_encode(array("status" => "success", "data" => array("success")));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        }
                        break;
                    case "upload-ec-map" :
                        if (isset($request["course_id"]) && $tmp_input = clean_input($request["course_id"], array("trim", "int"))) {
                            $PROCESSED["course_id"] = $tmp_input;
                        } else {
                            add_error("You must provide a <strong>Course ID</strong>");
                        }

                        if (!$ERROR) {
                            if (isset($_FILES["files"]["name"])) {
                                switch ($_FILES["files"]["error"]) {
                                    case 0 :
                                        if (!$ERROR) {
                                            $objective_model = new Models_Objective();
                                            $finfo = finfo_open(FILEINFO_MIME_TYPE);
                                            $PROCESSED["file_type"] = finfo_file($finfo, $_FILES["files"]["tmp_name"]);
                                            if (in_array($PROCESSED["file_type"], array("text/csv", "text/plain"))) {
                                                if ($_FILES["files"]["tmp_name"]) {
                                                    ini_set("auto_detect_line_endings", true);
                                                    $fp = fopen($_FILES["files"]["tmp_name"], "r");

                                                    if ($fp) {

                                                        $cbme_file_upload_history = new Models_CBME_UploadHistory();
                                                        $cbme_file_upload_history->storeFileUploadHistory(
                                                            $_FILES["files"]["tmp_name"],
                                                            $_FILES["files"]["name"],
                                                            $PROCESSED["file_type"],
                                                            $ENTRADA_USER->getActiveID(),
                                                            $PROCESSED["course_id"],
                                                            "ec_map"
                                                        );

                                                        $file_error = false;
                                                        $ctr = 1;
                                                        while (($data = fgetcsv($fp)) !== false) {
                                                            //Skip the headings
                                                            if ($ctr == 1) {
                                                                $ctr++;
                                                                continue;
                                                            }
                                                            $ctr++;

                                                            if (empty($data[0]) && empty($data[1])) {
                                                                continue;
                                                            }
                                                            if (empty($data[0]) || empty($data[1])) {
                                                                add_error(sprintf($translate->_("Either an EPA code or an Enabling Competency code is missing in row: %s, please add the missing data and re-upload your Enabling Competencies."), $ctr - 1));
                                                                break;
                                                            }

                                                            if (isset($data[0])) {
                                                                $epa_ids = explode(",", $data[0]);
                                                                if ($epa_ids) {
                                                                    foreach ($epa_ids as $epa_id) {
                                                                        $epa_id = trim($epa_id);
                                                                        $epa_code = $objective_model->fetchRowByObjectiveCode($epa_id);
                                                                        if (!$epa_code) {
                                                                            add_error(sprintf($translate->_("The uploaded file has an <strong>EPA Code - %s</strong> that does not match the system codes, please update the file and re-upload it."), $data[0]));
                                                                            break;
                                                                        }
                                                                    }
                                                                } else {
                                                                    add_error($translate->_("The uploaded file has a missing <strong>EPA Code</strong>, please update the file and re-upload it."));
                                                                    break;
                                                                }
                                                            } else {
                                                                add_error(sprintf($translate->_("There appears to be invalid characters in the <strong>EPA Code</strong> for row: <strong>%s</strong>. This problem most often occurs when special characters such as comas or apostrophes are copy and pasted from Microsoft Word into your CSV. To resolve this, locate special characters in the indicated row, delete and re-type the character. Once you have done this, save your CSV and re-upload."), $ctr - 1));
                                                            }

                                                            if (isset($data[1]) && $tmp_input = clean_input($data[1], array("trim", "striptags", "msword"))) {
                                                                if (!empty($tmp_input)) {
                                                                    $enabling_competency_code = $objective_model->fetchRowByObjectiveCode($tmp_input);
                                                                    if (!$enabling_competency_code) {
                                                                        add_error(sprintf($translate->_("The uploaded file has an <strong>Enabling Competency Code - %s</strong> that does not match the system codes, please update the file and re-upload it."), $data[1]));
                                                                        break;
                                                                    }
                                                                } else {
                                                                    add_error($translate->_("The uploaded file has a missing <strong>Enabling Competency Code</strong>, please update the file and re-upload it."));
                                                                    break;
                                                                }
                                                            } else {
                                                                add_error(sprintf($translate->_("There appears to be invalid characters in the <strong>Enabling Competency Code</strong> for row: <strong>%s</strong>. This problem most often occurs when special characters such as comas or apostrophes are copy and pasted from Microsoft Word into your CSV. To resolve this, locate special characters in the indicated row, delete and re-type the character. Once you have done this, save your CSV and re-upload."), $ctr - 1));
                                                            }
                                                        }

                                                        if (!$ERROR) {
                                                            // Initialize an objective tree object
                                                            $tree_object = new Entrada_CBME_ObjectiveTree(array(
                                                                "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                                                                "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                                                                "course_id" => $PROCESSED["course_id"]
                                                            ));

                                                            if (!$tree_object->getRootNodeID()) {
                                                                // Initialize a new tree. By default, this will create a primary tree if no other trees exist.
                                                                $tree_object->createNewTree();
                                                            }

                                                            rewind($fp);
                                                            fgetcsv($fp);

                                                            while (($data = fgetcsv($fp)) !== false) {

                                                                /**
                                                                 * Sanitize each EPA code
                                                                 */
                                                                $PROCESSED["epa_codes"] = array();
                                                                $epa_ids = explode(",", $data[0]);
                                                                if ($epa_ids) {
                                                                    foreach ($epa_ids as $epa_id) {
                                                                        if ($tmp_input = clean_input($epa_id, array("trim", "striptags"))) {
                                                                            $PROCESSED["epa_codes"][] = $tmp_input;
                                                                        }
                                                                    }
                                                                }

                                                                /**
                                                                 * Get any EPAs that match the supplied EPA code(s)
                                                                 */
                                                                $course_epas = $objective_model->fetchAllByObjectiveCodeCourseID("epa", $PROCESSED["epa_codes"], $PROCESSED["course_id"]);
                                                                if ($course_epas) {

                                                                    /**
                                                                     * There are course specific EPAs which match values from the uploaded spreadsheet
                                                                     * so an entire branch can be linked
                                                                     */
                                                                    foreach ($course_epas as $course_epa) {
                                                                        /**
                                                                         * Extract the stage code from the EPA code and fetch the corresponding objective
                                                                         */
                                                                        $stage_code = substr($course_epa["objective_code"], 0, 1);
                                                                        $stage = $objective_model->fetchRowByShortnameCode("stage", $stage_code);

                                                                        /**
                                                                         * Extract the role code from the EPA code and fetch the corresponding objective
                                                                         */
                                                                        $role_code = substr($data[1], 0, 2);
                                                                        $role = $objective_model->fetchRowByShortnameCode("role", $role_code);

                                                                        /**
                                                                         * Extract the key competency and enabling competency codes from the milestone code
                                                                         */
                                                                        $code_pieces = explode(".", substr(clean_input($data[1], array("trim", "striptags", "msword")), 2));

                                                                        /**
                                                                         * Set the key competency code and fetch its corresponding objective
                                                                         */
                                                                        $kc_code = $role_code . $code_pieces[0];

                                                                        /**
                                                                         * Set the enabling competency code and fetch its corresponding objective
                                                                         */
                                                                        $ec_code = $role_code . $code_pieces[0] . "." . $code_pieces[1];

                                                                        /**
                                                                         * Set the key and enabling competency codes and fetch their corresponding objectives
                                                                         */
                                                                        $settings = new Entrada_Settings();
                                                                        $course_settings = new Entrada_Course_Settings($PROCESSED["course_id"]);

                                                                        if (((int) $settings->read("cbme_standard_kc_ec_objectives")) || ((int) $course_settings->read("cbme_standard_kc_ec_objectives"))) {
                                                                            $kc = $objective_model->fetchRowByShortnameCode("kc", $kc_code);
                                                                            $ec = $objective_model->fetchRowByShortnameCode("ec", $ec_code);
                                                                        } else {
                                                                            $kc = $objective_model->fetchRowByObjectiveCodeCourseID("kc", $kc_code, $PROCESSED["course_id"]);
                                                                            $ec = $objective_model->fetchRowByObjectiveCodeCourseID("ec", $ec_code, $PROCESSED["course_id"]);
                                                                        }

                                                                        if ($stage && $role && $kc && $ec) {
                                                                            $branch = array();
                                                                            $branch[] = $stage["objective_id"];
                                                                            $branch[] = $course_epa["objective_id"];
                                                                            $branch[] = $role["objective_id"];
                                                                            $branch[] = $kc["objective_id"];
                                                                            $branch[] = $ec["objective_id"];

                                                                            if (!$tree_object->addBranch($tree_object->getRootNodeID(), $branch)) {
                                                                                add_error($translate->_("An error occurred while attempting to create this branch."));
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
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
                                        add_error("The file that uploaded did not complete the upload process or was interrupted. Please try again.");
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
                            echo json_encode(array("status" => "success", "data" => array("success")));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        }
                        break;
                    case "upload-procedure-criteria" :
                        if (isset($request["course_id"]) && $tmp_input = clean_input($request["course_id"], array("trim", "int"))) {
                            $PROCESSED["course_id"] = $tmp_input;
                        } else {
                            add_error($translate->_("You must provide a <strong>Course ID</strong>"));
                        }

                        if (isset($request["procedure_id"]) && $tmp_input = clean_input($request["procedure_id"], array("trim", "int"))) {
                            $PROCESSED["procedure_id"] = $tmp_input;
                        } else {
                            add_error($translate->_("You must provide a <strong>Procedure ID</strong>"));
                        }

                        $tree_objective_ids = Models_CBME_ProcedureEPAAttribute::fetchProcedureAttributeObjectiveIDs($ENTRADA_USER->getActiveOrganisation(), $PROCESSED["procedure_id"]);

                        if (isset($request["selected_epa"]) && is_array($request["selected_epa"])) {
                            $PROCESSED["epas"] = array_map(function ($epa) {
                                return clean_input($epa, array("trim", "int"));
                            }, $request["selected_epa"]);
                        } else {
                            add_error($translate->_("You must provide at least one <strong>EPA</strong>"));
                        }

                        $attributes = array();
                        $rubrics = array();
                        $rubric_lines = array();

                        // Fetch the objective set model for the procedures attributes
                        $objective_set_model = new Models_ObjectiveSet();
                        $objective_set = $objective_set_model->fetchRowByShortname("procedure_attribute");

                        if (isset($_FILES["files"]["name"])) {
                            switch ($_FILES["files"]["error"]) {
                                case 0 :
                                    if (!$ERROR) {
                                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                                        $PROCESSED["file_type"] = finfo_file($finfo, $_FILES["files"]["tmp_name"]);
                                        if (in_array($PROCESSED["file_type"], array("text/csv", "text/plain"))) {
                                            if ($_FILES["files"]["tmp_name"]) {
                                                ini_set("auto_detect_line_endings", true);
                                                $fp = fopen($_FILES["files"]["tmp_name"], "r");

                                                if ($fp) {

                                                    $cbme_file_upload_history = new Models_CBME_UploadHistory();
                                                    $cbme_file_upload_history->storeFileUploadHistory(
                                                        $_FILES["files"]["tmp_name"],
                                                        $_FILES["files"]["name"],
                                                        $PROCESSED["file_type"],
                                                        $ENTRADA_USER->getActiveID(),
                                                        $PROCESSED["course_id"],
                                                        "procedure_criteria"
                                                    );

                                                    $file_error = false;
                                                    while (($data = fgetcsv($fp)) !== false) {
                                                        if (empty($data[0]) || empty($data[1])) {
                                                            $file_error = true;
                                                        }
                                                    }

                                                    if (!$file_error) {
                                                        // Clear existing criteria
                                                        /* Moved to EPA deletion
                                                        if ($current_objectives = Models_Objective::fetchAllByParentID($ENTRADA_USER->getActiveOrganisation(), $PROCESSED["procedure_id"])) {
                                                            Models_Objective::deleteAllWithAllChildren($current_objectives, $ENTRADA_USER->getActiveOrganisation());
                                                        }
                                                        */

                                                        rewind($fp);
                                                        fgetcsv($fp);

                                                        $attribute_order = 1;
                                                        $attribute_objective_id = 0;
                                                        $rubric_objective_id = 0;
                                                        $line_objective_id = 0;

                                                        if (!empty($tree_objective_ids) && !Models_CBME_ProcedureEPAAttribute::disableByCourseIDEPAObjectivesIDAttributes($ENTRADA_USER->getActiveId(), $PROCESSED["course_id"], $PROCESSED["epas"], $tree_objective_ids)) {
                                                            add_error($translate->_("Failed to delete previous EPA association. Upload cannot be processed."));
                                                            application_log("error", "There was an error deleting EPA association. Database said: " . $db->ErrorMsg());
                                                        } else {

                                                            while (($data = fgetcsv($fp)) !== false) {
                                                                $objective_type = clean_input($data[0], array("trim", "html", "msword"));

                                                                switch ($objective_type) {
                                                                    case "H":
                                                                        $rubric_objective_id = 0;
                                                                        $rubric_order = 1;
                                                                        $objective_name = clean_input($data[1], array("trim", "html", "msword"));
                                                                        $objective_model = new Models_Objective();
                                                                        $objective_data = array(
                                                                            "objective_code" => $objective_set->getShortname(),
                                                                            "objective_name" => $objective_name,
                                                                            "objective_description" => null,
                                                                            "objective_parent" => $PROCESSED["procedure_id"],
                                                                            "objective_set_id" => $objective_set->getID(),
                                                                            "objective_order" => $attribute_order++,
                                                                            "overall_order" => null,
                                                                            "objective_loggable" => 0,
                                                                            "objective_active" => 1,
                                                                            "updated_date" => time(),
                                                                            "updated_by" => $ENTRADA_USER->getActiveId()
                                                                        );
                                                                        if (!$db->AutoExecute("global_lu_objectives", $objective_data, "INSERT") || !($attribute_objective_id = $db->Insert_Id())) {
                                                                            echo $attribute_objective_id . "\n";
                                                                            add_error($translate->_("Failed to add objective for attribute: ") . $attribute);
                                                                            application_log("error", "There was an error inserting an objective. Database said: " . $db->ErrorMsg());
                                                                            break;
                                                                        }

                                                                        $cbme_course_objective = array(
                                                                            "objective_id" => $attribute_objective_id,
                                                                            "course_id" => $PROCESSED["course_id"],
                                                                            "created_date" => time(),
                                                                            "created_by" => $ENTRADA_USER->getActiveId()
                                                                        );

                                                                        $cbme_course_objective_model = new Models_CBME_CourseObjective();
                                                                        if ($cbme_course_objective_model->fromArray($cbme_course_objective)->insert()) {
                                                                            $objective_organisation = array(
                                                                                "objective_id" => $attribute_objective_id,
                                                                                "organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                                                                                "created_date" => time(),
                                                                                "created_by" => $ENTRADA_USER->getActiveId()
                                                                            );

                                                                            $objective_organisation_model = new Models_Objective_Organisation();
                                                                            if (!$objective_organisation_model->fromArray($objective_organisation)->insert()) {
                                                                                add_error($translate->_("Failed to link objective to organisation: ") . $attribute);
                                                                                application_log("error", "There was an error inserting an objective. Database said: " . $db->ErrorMsg());
                                                                                break;
                                                                            }
                                                                        } else {
                                                                            add_error($translate->_("Failed to link objective to course: ") . $attribute);
                                                                            application_log("error", "There was an error inserting an objective. Database said: " . $db->ErrorMsg());
                                                                            break;
                                                                        }

                                                                        /**
                                                                         * Link EPA to attribute
                                                                         */
                                                                        if (!Models_CBME_ProcedureEPAAttribute::insertBulkByCourseIDObjectiveIDEPAObjectives($ENTRADA_USER->getActiveID(), $PROCESSED["course_id"], $attribute_objective_id, $PROCESSED["epas"])) {
                                                                            add_error($translate->_("Failed to insert EPA association"));
                                                                            application_log("error", "There was an error inserting EPA association. Database said: " . $db->ErrorMsg());
                                                                        }

                                                                        break;

                                                                    case "R":
                                                                        if ($attribute_objective_id) {
                                                                            $line_order = 1;
                                                                            $objective_name = clean_input($data[1], array("trim", "html", "msword"));
                                                                            $objective_model = new Models_Objective();
                                                                            $objective_data = array(
                                                                                "objective_code" => $objective_set->getShortname(),
                                                                                "objective_name" => $objective_name,
                                                                                "objective_description" => null,
                                                                                "objective_parent" => $attribute_objective_id,
                                                                                "objective_set_id" => $objective_set->getID(),
                                                                                "objective_order" => $rubric_order++,
                                                                                "overall_order" => null,
                                                                                "objective_loggable" => 0,
                                                                                "objective_active" => 1,
                                                                                "updated_date" => time(),
                                                                                "updated_by" => $ENTRADA_USER->getActiveId()
                                                                            );
                                                                            if (!$db->AutoExecute("global_lu_objectives", $objective_data, "INSERT") || !($rubric_objective_id = $db->Insert_Id())) {
                                                                                add_error($translate->_("Failed to add objective for rubric: ") . $rubric);
                                                                                application_log("error", "There was an error inserting an objective. Database said: " . $db->ErrorMsg());
                                                                                break;
                                                                            }

                                                                            $cbme_course_objective = array(
                                                                                "objective_id" => $rubric_objective_id,
                                                                                "course_id" => $PROCESSED["course_id"],
                                                                                "created_date" => time(),
                                                                                "created_by" => $ENTRADA_USER->getActiveId()
                                                                            );

                                                                            $cbme_course_objective_model = new Models_CBME_CourseObjective();
                                                                            if ($cbme_course_objective_model->fromArray($cbme_course_objective)->insert()) {
                                                                                $objective_organisation = array(
                                                                                    "objective_id" => $rubric_objective_id,
                                                                                    "organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                                                                                    "created_date" => time(),
                                                                                    "created_by" => $ENTRADA_USER->getActiveId()
                                                                                );

                                                                                $objective_organisation_model = new Models_Objective_Organisation();
                                                                                if (!$objective_organisation_model->fromArray($objective_organisation)->insert()) {
                                                                                    add_error($translate->_("Failed to link objective to organisation: ") . $rubric);
                                                                                    application_log("error", "There was an error inserting an objective. Database said: " . $db->ErrorMsg());
                                                                                    break;
                                                                                }
                                                                            } else {
                                                                                add_error($translate->_("Failed to link objective to course: ") . $rubric);
                                                                                application_log("error", "There was an error inserting an objective. Database said: " . $db->ErrorMsg());
                                                                                break;
                                                                            }

                                                                            /**
                                                                             * Link EPA to attribute
                                                                             */
                                                                            if (!Models_CBME_ProcedureEPAAttribute::insertBulkByCourseIDObjectiveIDEPAObjectives($ENTRADA_USER->getActiveID(), $PROCESSED["course_id"], $rubric_objective_id, $PROCESSED["epas"])) {
                                                                                add_error($translate->_("Failed to insert EPA association"));
                                                                                application_log("error", "There was an error inserting EPA association. Database said: " . $db->ErrorMsg());
                                                                            }
                                                                        } else {
                                                                            add_error($translate->_("Rubric without a header"));
                                                                        }

                                                                        break;

                                                                    case "L":
                                                                        if ($rubric_objective_id) {
                                                                            $objective_name = clean_input($data[1], array("trim", "html", "msword"));
                                                                            $objective_model = new Models_Objective();
                                                                            $objective_data = array(
                                                                                "objective_code" => $objective_set->getShortname(),
                                                                                "objective_name" => $objective_name,
                                                                                "objective_description" => null,
                                                                                "objective_parent" => $rubric_objective_id,
                                                                                "objective_set_id" => $objective_set->getID(),
                                                                                "objective_order" => $line_order++,
                                                                                "overall_order" => null,
                                                                                "objective_loggable" => 0,
                                                                                "objective_active" => 1,
                                                                                "updated_date" => time(),
                                                                                "updated_by" => $ENTRADA_USER->getActiveId()
                                                                            );
                                                                            if (!$db->AutoExecute("global_lu_objectives", $objective_data, "INSERT") || !($line_objective_id = $db->Insert_Id())) {
                                                                                add_error($translate->_("Failed to add objective for rubric line: ") . $rubric_line);
                                                                                application_log("error", "There was an error inserting an objective. Database said: " . $db->ErrorMsg());
                                                                                break;
                                                                            }

                                                                            $cbme_course_objective = array(
                                                                                "objective_id" => $line_objective_id,
                                                                                "course_id" => $PROCESSED["course_id"],
                                                                                "created_date" => time(),
                                                                                "created_by" => $ENTRADA_USER->getActiveId()
                                                                            );

                                                                            $cbme_course_objective_model = new Models_CBME_CourseObjective();
                                                                            if ($cbme_course_objective_model->fromArray($cbme_course_objective)->insert()) {
                                                                                $objective_organisation = array(
                                                                                    "objective_id" => $line_objective_id,
                                                                                    "organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                                                                                    "created_date" => time(),
                                                                                    "created_by" => $ENTRADA_USER->getActiveId()
                                                                                );

                                                                                $objective_organisation_model = new Models_Objective_Organisation();
                                                                                if (!$objective_organisation_model->fromArray($objective_organisation)->insert()) {
                                                                                    add_error($translate->_("Failed to link objective to organisation: ") . $rubric_line);
                                                                                    application_log("error", "There was an error inserting an objective. Database said: " . $db->ErrorMsg());
                                                                                    break;
                                                                                }
                                                                            } else {
                                                                                add_error($translate->_("Failed to link objective to course: ") . $rubric_line);
                                                                                application_log("error", "There was an error inserting an objective. Database said: " . $db->ErrorMsg());
                                                                                break;
                                                                            }

                                                                            /**
                                                                             * Link EPA to attribute
                                                                             */
                                                                            if (!Models_CBME_ProcedureEPAAttribute::insertBulkByCourseIDObjectiveIDEPAObjectives($ENTRADA_USER->getActiveID(), $PROCESSED["course_id"], $line_objective_id, $PROCESSED["epas"])) {
                                                                                add_error($translate->_("Failed to insert EPA association"));
                                                                                application_log("error", "There was an error inserting EPA association. Database said: " . $db->ErrorMsg());
                                                                            }
                                                                        } else {
                                                                            add_error($translate->_("Rubric line outside a rubric"));
                                                                        }

                                                                        break;
                                                                    default:
                                                                        break;
                                                                } // End switch objective types
                                                            } // End while file lines parsing
                                                        }
                                                    } else {
                                                        add_error($translate->_("Invalid CSV data format"));
                                                    }
                                                }
                                            }
                                        } else {
                                            add_error($translate->_("Invalid file type: " . $PROCESSED["file_type"]));
                                        }
                                    }
                                    break;
                                case 1 :
                                case 2 :
                                    add_error("The uploaded file exceeds the allowed file size limit.");
                                    break;
                                case 3 :
                                    add_error("The file that uploaded did not complete the upload process or was interrupted. Please try again.");
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
                        }

                        if (!$ERROR) {
                            echo json_encode(array("status" => "success", "data" => array("success")));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        }
                        break;
                    case "remove-cv-responses" :
                        if (isset($_POST["objective_id"]) && $tmp_input = clean_input($_POST["objective_id"], array("trim", "int"))) {
                            $PROCESSED["objective_id"] = $tmp_input;
                        } else {
                            add_error($translate->_("No <strong>Objective ID</strong> was provided."));
                        }

                        if (!$ERROR) {
                            $objective = Models_Objective::fetchRow($PROCESSED["objective_id"]);

                            if ($objective) {
                                $objective->setObjectiveActive(0);
                                $objective->setUpdateDate(time());
                                $objective->setUpdateBy($ENTRADA_USER->getActiveId());

                                if (!$objective->update(false)) {
                                    add_error($translate->_("Unable to delete the response at this time."));
                                }
                            } else {
                                add_error($translate->_("The response provided was not found."));
                            }
                        }

                        if (!$ERROR) {
                            echo json_encode(array("status" => "success", "data" => $translate->_("Successfully deleted response.")));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        }
                        break;
                    case "save-cv-responses" :
                        if (isset($_POST["objective_id"]) && $tmp_input = clean_input($_POST["objective_id"], array("trim", "int"))) {
                            $PROCESSED["objective_id"] = $tmp_input;
                        } else {
                            add_error($translate->_("No <strong>Objective ID</strong> was provided."));
                        }

                        if (isset($_POST["objective_title"]) && $tmp_input = clean_input($_POST["objective_title"], array("trim", "striptags"))) {
                            $PROCESSED["objective_title"] = $tmp_input;
                        } else {
                            add_error($translate->_("No <strong>Response</strong> was provided."));
                        }

                        $PROCESSED["objective_description"] = "";
                        if (isset($_POST["objective_description"]) && $tmp_input = clean_input($_POST["objective_description"], array("trim", "html"))) {
                            $PROCESSED["objective_description"] = $tmp_input;
                        }

                        $PROCESSED["objective_code"] = "";
                        if (isset($_POST["objective_code"]) && $tmp_input = clean_input($_POST["objective_code"], array("trim", "striptags"))) {
                            $PROCESSED["objective_code"] = $tmp_input;
                        }

                        $PROCESSED["course_id"] = false;
                        if (isset($_POST["course_id"]) && $tmp_input = clean_input($_POST["course_id"], array("trim", "int"))) {
                            $PROCESSED["course_id"] = $tmp_input;
                        }

                        $objective_id = null;
                        $success_message = $translate->_("Successfully updated response.");

                        if (!$ERROR) {
                            if ($PROCESSED["objective_id"] != -1) {
                                $objective = Models_Objective::fetchRow($PROCESSED["objective_id"]);

                                if ($objective) {
                                    $objective->setObjectiveName($PROCESSED["objective_title"]);
                                    $objective->setObjectiveDescription($PROCESSED["objective_description"]);
                                    $objective->setUpdateDate(time());
                                    $objective->setUpdateBy($ENTRADA_USER->getActiveId());

                                    if (!$objective->update(false)) {
                                        add_error($translate->_("Unable to update the response at this time."));
                                    }
                                } else {
                                    add_error($translate->_("The objective provided was not found."));
                                }
                            } else {
                                $success_message = $translate->_("Successfully added response.");

                                $objective_model = new Models_Objective();
                                $objective_set_model = new Models_ObjectiveSet();

                                if ($PROCESSED["objective_code"] && $PROCESSED["course_id"]) {
                                    //$contextual_variable = $objective_model->fetchRowByObjectiveCode($PROCESSED["objective_code"]);
                                    $contextual_variable = $objective_model->fetchParentForSetBySetShortnameObjectiveCode("contextual_variable", $PROCESSED["objective_code"]); // find the first, top-level objective
                                    $contextual_variable_set_record = $objective_set_model->fetchRowByShortname("contextual_variable_responses");
                                    $contextual_variable_responses = $objective_model->fetchChildrenByObjectiveSetShortnameCourseID("contextual_variable_responses", $PROCESSED["course_id"]);

                                    if ($contextual_variable && $contextual_variable_set_record) {
                                        $objective = new Models_Objective(
                                            null,
                                            $PROCESSED["objective_code"],
                                            $PROCESSED["objective_title"],
                                            $PROCESSED["objective_description"],
                                            null,
                                            $contextual_variable->getID(),
                                            $contextual_variable_set_record->getID(),
                                            null,
                                            count($contextual_variable_responses),
                                            0,
                                            1,
                                            time(),
                                            $ENTRADA_USER->getActiveId()
                                        );

                                        if (!$objective->insert(false)) {
                                            add_error($translate->_("Unable to insert a response at this time."));
                                        }
                                    } else {
                                        add_error($translate->_("Unable to find contextual variable at this time."));
                                    }
                                } else {
                                    add_error($translate->_("Information needed to insert record is missing."));
                                }

                                if (!$ERROR) {
                                    $objective_id = $objective->getID();
                                    $cbme_course_objective = new Models_CBME_CourseObjective(array(
                                        "objective_id" => $objective->getID(),
                                        "course_id" => $PROCESSED["course_id"],
                                        "created_date" => time(),
                                        "created_by" => $ENTRADA_USER->getActiveId()
                                    ));

                                    if (!$cbme_course_objective->insert()) {
                                        add_error($translate->_("Unable to insert CBME course objective at this time."));
                                    }
                                }

                                if (!$ERROR) {
                                    $objective_organisation = new Models_Objective_Organisation(array(
                                        "objective_id" => $objective->getID(),
                                        "organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                                        "created_date" => time(),
                                        "created_by" => $ENTRADA_USER->getActiveId()
                                    ));

                                    if (!$objective_organisation->insert()) {
                                        add_error($translate->_("Unable to insert an objective organisation at this time."));
                                    }
                                }
                            }
                        }

                        if (!$ERROR) {
                            echo json_encode(array("status" => "success", "data" => $success_message, "objective_id" => $objective_id));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        }
                        break;
                    case "reset-cbme-data":
                        if (isset($_POST["course_id"]) && $tmp_input = clean_input($_POST["course_id"], array("trim", "int"))) {
                            $PROCESSED["course_id"] = $tmp_input;
                        } else {
                            add_error($translate->_("A course identifier is required."));
                        }

                        if (($ENTRADA_USER->getActiveGroup() != "medtech") || ($ENTRADA_USER->getActiveRole() != "admin")) {
                            add_error($translate->_("Unauthorized attempt to reset cbme course data."));
                            application_log("error", "Unauthorized attempt to reset cbme course data for course_id [ " . $PROCESSED["course_id"] . "].");
                        }

                        if (isset($_POST["organisation_id"]) && $tmp_input = clean_input($_POST["organisation_id"], array("trim", "int"))) {
                            $PROCESSED["organisation_id"] = $tmp_input;
                        } else {
                            add_error($translate->_("A course identifier is required."));
                        }

                        if (!$ERROR) {
                            /**
                             * Fetch course specific objectives
                             */
                            $course_objectives_model = new Models_CBME_CourseObjective();
                            $course_objectives = $course_objectives_model->fetchAllByCourseIDOrganisationID($PROCESSED["course_id"], $PROCESSED["organisation_id"]);

                            if ($course_objectives) {
                                $error = false;

                                foreach ($course_objectives as $course_objective) {
                                    $objective = Models_Objective::fetchRow($course_objective->getObjectiveID());
                                    if ($objective) {
                                        /**
                                         * Remove each objective data point associated with this course.
                                         */
                                        $query = "DELETE FROM `global_lu_objectives` WHERE `objective_id` = " . $db->qstr($course_objective->getObjectiveID());
                                        if ($db->Execute($query)) {
                                            $query = "DELETE FROM `objective_organisation` WHERE `objective_id` = " . $db->qstr($course_objective->getObjectiveID());
                                            if (!$db->Execute($query)) {
                                                $error = true;
                                            }

                                            $query = "DELETE FROM `cbme_course_objectives` WHERE `objective_id` = " . $db->qstr($course_objective->getObjectiveID()) . " AND `course_id` = " . $db->qstr($PROCESSED["course_id"]);
                                            if (!$db->Execute($query)) {
                                                $error = true;
                                            }

                                            $query = "SELECT * FROM `cbl_assessment_form_objectives` WHERE `objective_id` = " . $db->qstr($course_objective->getObjectiveID()) . " AND `organisation_id` = " . $db->qstr($PROCESSED["organisation_id"]) . " AND `course_id` = " . $db->qstr($PROCESSED["course_id"]) . " AND `deleted_date` IS NULL ";
                                            $result = $db->GetRow($query);
                                            if ($result) {
                                                $form_model = new Models_Assessments_Form();
                                                $form = $form_model->fetchRowByID($result["form_id"]);
                                                if ($form->fromArray(array("deleted_date" => time()))->update()) {
                                                    $query = "DELETE FROM `cbl_assessment_form_objectives` WHERE `form_id` = " . $db->qstr($form->getID()) . " AND `objective_id` = " . $db->qstr($course_objective->getObjectiveID()) . " AND `course_id` = " . $db->qstr($PROCESSED["course_id"]) . " AND `organisation_id` = " . $db->qstr($PROCESSED["organisation_id"]) . " AND `deleted_date` IS NULL ";
                                                    if (!$db->Execute($query)) {
                                                        $error = true;
                                                    }
                                                } else {
                                                    $error = true;
                                                }
                                            }

                                            $query = "DELETE FROM `cbl_assessments_form_blueprint_objectives` WHERE `objective_id` = " . $db->qstr($course_objective->getObjectiveID()) . " AND `organisation_id` = " . $db->qstr($PROCESSED["organisation_id"]);
                                            if (!$db->Execute($query)) {
                                                $error = true;
                                            }
                                        } else {
                                            $error = true;
                                        }
                                    }
                                }

                                $query = "DELETE FROM `cbme_objective_trees` WHERE `course_id` = " . $db->qstr($PROCESSED["course_id"]) . " AND `organisation_id` = " . $db->qstr($PROCESSED["organisation_id"]);
                                if (!$db->Execute($query)) {
                                    $error = true;
                                }

                                $form_blueprint_model = new Models_Assessments_Form_Blueprint();
                                $course_blueprints = $form_blueprint_model->fetchAllByCourseIDIgnoreActive($PROCESSED["course_id"]);
                                if ($course_blueprints) {
                                    foreach ($course_blueprints as $course_blueprint) {
                                        if (!$course_blueprint->fromArray(array("deleted_date" => time()))->update()) {
                                            $error = true;
                                        }
                                    }
                                }

                                $course = Models_Course::get($PROCESSED["course_id"]);
                                if ($course) {
                                    if (!$course->fromArray(array("cbme_milestones" => NULL))->update()) {
                                        add_error($translate->_("An error occurred while attempting to save the curriculum tag option."));
                                    }
                                }

                                if ($error) {
                                    add_error($translate->_("An error occurred while attempting to delete the some course objective data"));
                                }
                            }
                        } else {
                            add_error($translate->_("An error occurred while attempting to delete the some course objective data"));
                        }

                        if (!$ERROR) {
                            echo json_encode(array("status" => "success", "data" => "success"));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        }
                        break;
                    case "save-objective" :
                        if (isset($_POST["objective_id"]) && $tmp_input = clean_input($_POST["objective_id"], array("trim", "int"))) {
                            $PROCESSED["objective_id"] = $tmp_input;
                        } else {
                            add_error($translate->_("An objective identifier is required."));
                        }

                        if (isset($_POST["objective_set_shortname"]) && $tmp_input = clean_input($_POST["objective_set_shortname"], array("trim", "striptags"))) {
                            $PROCESSED["objective_set_shortname"] = $tmp_input;
                        } else {
                            add_error($translate->_("An objective set is required."));
                        }

                        if (isset($_POST["objective_name"]) && $tmp_input = clean_input($_POST["objective_name"], array("trim", "striptags"))) {
                            $PROCESSED["objective_name"] = $tmp_input;
                        } else {
                            add_error($translate->_("Please provide a <strong>title</strong>."));
                        }

                        if (isset($_POST["objective_description"]) && $tmp_input = clean_input($_POST["objective_description"], array("trim", "striptags"))) {
                            $PROCESSED["objective_description"] = $tmp_input;
                        } else {
                            $PROCESSED["objective_description"] = "";
                        }

                        if (isset($_POST["objective_secondary_description"]) && $tmp_input = clean_input($_POST["objective_secondary_description"], array("trim", "striptags"))) {
                            $PROCESSED["objective_secondary_description"] = $tmp_input;
                        } else {
                            $PROCESSED["objective_secondary_description"] = "";
                        }

                        if (!$ERROR) {
                            $objective_model = new Models_Objective();
                            $objective_set_model = new Models_ObjectiveSet();

                            switch ($PROCESSED["objective_set_shortname"]) {
                                case "milestone" :
                                    $objective_set = $objective_set_model->fetchRowByShortname("milestone");
                                    break;
                                default :
                                    $objective_set = $objective_set_model->fetchRowByShortname("epa");
                                    break;
                            }

                            if ($objective_set) {
                                $objective = $objective_model->fetchRow($PROCESSED["objective_id"]);
                                if ($objective) {
                                    $objective->setObjectiveName($PROCESSED["objective_name"]);

                                    if ($PROCESSED["objective_set_shortname"] == "epa") {
                                        $objective->setObjectiveDescription($PROCESSED["objective_description"]);
                                        $objective->setObjectiveSecondaryDescription($PROCESSED["objective_secondary_description"]);
                                    }

                                    $objective->setUpdateDate(time());
                                    $objective->setUpdateBy($ENTRADA_USER->getActiveId());
                                    if (!$objective->update()) {
                                        add_error($translate->_("An error occurred while attempting to update this objective. Please try again at a later time."));
                                        application_log("error", "An error occurred while attempting to update objective: ". $PROCESSED["objective_id"] .". Database said: " . $db->ErrorMsg());
                                    }
                                }
                            }
                        }

                        if (!$ERROR) {
                            echo json_encode(array("status" => "success", "data" => array("objective_id" => $PROCESSED["objective_id"], "messages" => array($translate->_("Successfully updated this objective.")))));
                        } else {
                            echo json_encode(array("status" => "error", "data" => array("objective_id" => $PROCESSED["objective_id"], "messages" => $ERRORSTR)));
                        }

                        break;
                }
                break;
            case "GET" :
                switch ($method) {
                    case "epas" :
                        if (isset($request["course_id"]) && $tmp_input = clean_input($request["course_id"], array("trim", "int"))) {
                            $course_id = $tmp_input;
                        } else {
                            add_error("No course identifier provided.");
                        }

                        if (isset($request["objective_id"]) && $tmp_input = clean_input($request["objective_id"], array("trim", "int"))) {
                            $objective_id = $tmp_input;
                        } else {
                            add_error("No role was provided");
                        }

                        if (!$ERROR) {
                            $epas = array();
                            $objective_model = new Models_Objective();
                            $stage = $objective_model->fetchRow($objective_id);

                            if ($stage) {
                                $epa_objectives = $objective_model->fetchObjectivesByShortnameCourseIDStage("epa", $course_id, $stage->getCode(), $search_term);
                                if ($epa_objectives) {
                                    foreach ($epa_objectives as $objective) {
                                        $epas[] = $objective->toArray();
                                    }
                                }

                                if ($epas) {
                                    echo json_encode(array("status" => "success", "data" => array("bucket" => "epa", "objectives" => $epas)));
                                } else {
                                    add_error("No <strong>EPAs</strong> were found to display");
                                    echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                                }
                            }
                        } else {
                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        }
                        break;
                    case "roles" :
                        $roles = array();
                        $objective_model = new Models_Objective();
                        $role_objectives = $objective_model->fetchChildrenByObjectiveSetShortname("role", $ENTRADA_USER->getActiveOrganisation(), $search_term);

                        if ($role_objectives) {
                            foreach ($role_objectives as $objective) {
                                $roles[] = $objective->toArray();
                            }
                        }

                        if ($roles) {
                            echo json_encode(array("status" => "success", "data" => array("bucket" => "role", "objectives" => $roles)));
                        } else {
                            add_error("No <strong>Roles</strong> were found to display");
                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        }
                        break;
                    case "key-competencies" :
                        if (isset($request["objective_id"]) && $tmp_input = clean_input($request["objective_id"], array("trim", "int"))) {
                            $objective_id = $tmp_input;
                        } else {
                            add_error("No role was provided");
                        }

                        if (!$ERROR) {
                            $key_competencies = array();
                            $objective_model = new Models_Objective();
                            $key_competency_objectives = $objective_model->fetchTemplateObjectivesByObjectiveParent($objective_id, $ENTRADA_USER->getActiveOrganisation(), $search_term);

                            if ($key_competency_objectives) {
                                foreach ($key_competency_objectives as $objective) {
                                    $key_competencies[] = $objective->toArray();
                                }
                            }

                            if ($key_competencies) {
                                echo json_encode(array("status" => "success", "data" => array("bucket" => "key-competency", "parent" => $objective_id, "objectives" => $key_competencies)));
                            } else {
                                add_error("No <strong>Key Competencies</strong> were found to display");
                                echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        }
                        break;
                    case "enabling-competencies" :
                        if (isset($request["course_id"]) && $tmp_input = clean_input($request["course_id"], array("trim", "int"))) {
                            $course_id = $tmp_input;
                        } else {
                            add_error("No course identifier provided.");
                        }

                        if (isset($request["objective_id"]) && $tmp_input = clean_input($request["objective_id"], array("trim", "int"))) {
                            $objective_id = $tmp_input;
                        } else {
                            add_error("No key competency was provided");
                        }

                        if (isset($_GET["objective_codes"])) {
                            $codes = json_decode($_GET["objective_codes"], true);
                            foreach ($codes as $code) {
                                if ($tmp_input = clean_input($code["code"], array("trim", "striptags"))) {
                                    $PROCESSED["codes"][] = $tmp_input;
                                }
                            }
                        } else {
                            add_error("No objective codes provided");
                        }

                        if (!$ERROR) {
                            $enabling_competencies = array();
                            $objective_model = new Models_Objective();

                            foreach ($PROCESSED["codes"] as $code) {
                                $enabling_competency_objectives = $objective_model->fetchAllByShortnameCode("ec", $code, $search_term);
                                if ($enabling_competency_objectives) {
                                    foreach ($enabling_competency_objectives as $enabling_competency_objective) {
                                        $enabling_competencies[] = $enabling_competency_objective;
                                    }
                                }
                            }

                            if ($enabling_competencies) {
                                echo json_encode(array("status" => "success", "data" => array("objectives" => $enabling_competencies)));
                            } else {
                                add_error("No <strong>Enabling Competencies</strong> were found to display");
                                echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                            }
                        }
                        break;
                    case "milestones" :
                        if (isset($request["course_id"]) && $tmp_input = clean_input($request["course_id"], array("trim", "int"))) {
                            $course_id = $tmp_input;
                        } else {
                            add_error("No course identifier provided.");
                        }

                        if (isset($request["objective_id"]) && $tmp_input = clean_input($request["objective_id"], array("trim", "int"))) {
                            $objective_id = $tmp_input;
                        } else {
                            add_error("No role was provided");
                        }

                        if (isset($_GET["objective_codes"])) {
                            $codes = json_decode($_GET["objective_codes"], true);
                            foreach ($codes as $code) {
                                if ($tmp_input = clean_input($code["code"], array("trim", "striptags"))) {
                                    $PROCESSED["codes"][] = $tmp_input;
                                }
                            }
                        } else {
                            add_error("No objective codes provided");
                        }

                        if (!$ERROR) {
                            $milestones = array();
                            $objective_model = new Models_Objective();

                            foreach ($PROCESSED["codes"] as $key => $code) {
                                $milestone_objectives = $objective_model->fetchCbmeCourseObjectivesByCode("milestone", $course_id, $code);
                                if ($milestone_objectives) {
                                    foreach ($milestone_objectives as $milestone_objective) {
                                        $milestones[] = $milestone_objective;
                                    }
                                }
                            }

                            if ($milestones) {
                                echo json_encode(array("status" => "success", "data" => array("objectives" => $milestones)));
                            } else {
                                add_error("No <strong>Milestones</strong> were found to display");
                                echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        }
                        break;
                    case "stages" :
                        break;
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
                        $row = array("EPA Code (optional)", "Milestone Code", "Title (255 character limit)");
                        fputcsv($fp, $row);
                        fclose($fp);
                        break;
                    case "download-contextual-variable-csv" :
                        header("Pragma: public");
                        header("Expires: 0");
                        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                        header("Content-Type: application/force-download");
                        header("Content-Type: application/octet-stream");
                        header("Content-Type: text/csv");
                        header("Content-Disposition: attachment; filename=\"contextual-variable-response-template.csv\"");
                        header("Content-Transfer-Encoding: binary");

                        $fp = fopen("php://output", "w");
                        $row = array("Response Code", "Response", "Description (optional)");
                        fputcsv($fp, $row);
                        $example_row = array("assessors_role");
                        fputcsv($fp, $example_row);
                        $example_row = array("basis_of_assessment");
                        fputcsv($fp, $example_row);
                        $example_row = array("case_complexity");
                        fputcsv($fp, $example_row);
                        $example_row = array("case_type");
                        fputcsv($fp, $example_row);
                        $example_row = array("clinical_presentation");
                        fputcsv($fp, $example_row);
                        $example_row = array("clinical_setting");
                        fputcsv($fp, $example_row);
                        $example_row = array("diagnosis", "Heart Attack");
                        fputcsv($fp, $example_row);
                        $example_row = array("encounters_with_resident");
                        fputcsv($fp, $example_row);
                        $example_row = array("organ_system");
                        fputcsv($fp, $example_row);
                        $example_row = array("patient_demographics");
                        fputcsv($fp, $example_row);
                        $example_row = array("procedure");
                        fputcsv($fp, $example_row);
                        $example_row = array("scope_of_assessment");
                        fputcsv($fp, $example_row);
                        $example_row = array("technical_difficulty");
                        fputcsv($fp, $example_row);
                        fclose($fp);
                        break;
                    case "download-ec-map-csv" :
                        header("Pragma: public");
                        header("Expires: 0");
                        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                        header("Content-Type: application/force-download");
                        header("Content-Type: application/octet-stream");
                        header("Content-Type: text/csv");
                        header("Content-Disposition: attachment; filename=\"enabling-competency-map.csv\"");
                        header("Content-Transfer-Encoding: binary");

                        $fp = fopen("php://output", "w");
                        $row = array("EPA Code", "Enabling Competency Code");
                        fputcsv($fp, $row);

                        /**
                         * Fetch Enabling Competencies and write them to the CSV
                         */
                        $objective_model = new Models_Objective();
                        $enabling_competencies = $objective_model->fetchChildrenByObjectiveSetShortname("ec", $ENTRADA_USER->getActiveOrganisation());
                        if ($enabling_competencies) {
                            foreach ($enabling_competencies as $enabling_competency) {
                                $ec_row = array("", $enabling_competency->getCode());
                                fputcsv($fp, $ec_row);
                            }
                        }

                        fclose($fp);
                        break;
                    case "download-key-competency-csv":
                        header("Pragma: public");
                        header("Expires: 0");
                        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                        header("Content-Type: application/force-download");
                        header("Content-Type: application/octet-stream");
                        header("Content-Type: text/csv");
                        header("Content-Disposition: attachment; filename=\"key-competency-template.csv\"");
                        header("Content-Transfer-Encoding: binary");

                        $fp = fopen("php://output", "w");
                        $row = array("Key Competency Code", "Title (255 character limit)", "Optional Description");
                        fputcsv($fp, $row);
                        $row = array("ME1");
                        fputcsv($fp, $row);
                        $row = array("ME2");
                        fputcsv($fp, $row);
                        $row = array("ME3");
                        fputcsv($fp, $row);
                        $row = array("ME4");
                        fputcsv($fp, $row);
                        $row = array("ME5");
                        fputcsv($fp, $row);
                        $row = array("CM1");
                        fputcsv($fp, $row);
                        $row = array("CM2");
                        fputcsv($fp, $row);
                        $row = array("CM3");
                        fputcsv($fp, $row);
                        $row = array("CM4");
                        fputcsv($fp, $row);
                        $row = array("CM5");
                        fputcsv($fp, $row);
                        $row = array("CL1");
                        fputcsv($fp, $row);
                        $row = array("CL2");
                        fputcsv($fp, $row);
                        $row = array("CL3");
                        fputcsv($fp, $row);
                        $row = array("LD1");
                        fputcsv($fp, $row);
                        $row = array("LD2");
                        fputcsv($fp, $row);
                        $row = array("LD3");
                        fputcsv($fp, $row);
                        $row = array("LD4");
                        fputcsv($fp, $row);
                        $row = array("HA1");
                        fputcsv($fp, $row);
                        $row = array("HA2");
                        fputcsv($fp, $row);
                        $row = array("SC1");
                        fputcsv($fp, $row);
                        $row = array("SC2");
                        fputcsv($fp, $row);
                        $row = array("SC3");
                        fputcsv($fp, $row);
                        $row = array("SC4");
                        fputcsv($fp, $row);
                        $row = array("PR1");
                        fputcsv($fp, $row);
                        $row = array("PR2");
                        fputcsv($fp, $row);
                        $row = array("PR3");
                        fputcsv($fp, $row);
                        $row = array("PR4");
                        fputcsv($fp, $row);
                        fclose($fp);
                        break;
                    case "download-enabling-competency-csv":
                        header("Pragma: public");
                        header("Expires: 0");
                        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                        header("Content-Type: application/force-download");
                        header("Content-Type: application/octet-stream");
                        header("Content-Type: text/csv");
                        header("Content-Disposition: attachment; filename=\"enabling-competency-template.csv\"");
                        header("Content-Transfer-Encoding: binary");

                        $fp = fopen("php://output", "w");
                        $row = array("Enabling Competency Code", "Title (255 character limit)", "Optional Description");
                        fputcsv($fp, $row);
                        $row = array("ME1.1");
                        fputcsv($fp, $row);
                        $row = array("ME1.2");
                        fputcsv($fp, $row);
                        $row = array("ME1.3");
                        fputcsv($fp, $row);
                        $row = array("ME1.4");
                        fputcsv($fp, $row);
                        $row = array("ME1.5");
                        fputcsv($fp, $row);
                        $row = array("ME1.6");
                        fputcsv($fp, $row);
                        $row = array("ME2.1");
                        fputcsv($fp, $row);
                        $row = array("ME2.2");
                        fputcsv($fp, $row);
                        $row = array("ME2.3");
                        fputcsv($fp, $row);
                        $row = array("ME2.4");
                        fputcsv($fp, $row);
                        $row = array("ME3.1");
                        fputcsv($fp, $row);
                        $row = array("ME3.2");
                        fputcsv($fp, $row);
                        $row = array("ME3.3");
                        fputcsv($fp, $row);
                        $row = array("ME3.4");
                        fputcsv($fp, $row);
                        $row = array("ME4.1");
                        fputcsv($fp, $row);
                        $row = array("ME5.1");
                        fputcsv($fp, $row);
                        $row = array("ME5.2");
                        fputcsv($fp, $row);
                        $row = array("CM1.1");
                        fputcsv($fp, $row);
                        $row = array("CM1.2");
                        fputcsv($fp, $row);
                        $row = array("CM1.3");
                        fputcsv($fp, $row);
                        $row = array("CM1.4");
                        fputcsv($fp, $row);
                        $row = array("CM1.5");
                        fputcsv($fp, $row);
                        $row = array("CM1.6");
                        fputcsv($fp, $row);
                        $row = array("CM2.1");
                        fputcsv($fp, $row);
                        $row = array("CM2.2");
                        fputcsv($fp, $row);
                        $row = array("CM2.3");
                        fputcsv($fp, $row);
                        $row = array("CM3.1");
                        fputcsv($fp, $row);
                        $row = array("CM3.2");
                        fputcsv($fp, $row);
                        $row = array("CM4.1");
                        fputcsv($fp, $row);
                        $row = array("CM4.2");
                        fputcsv($fp, $row);
                        $row = array("CM4.3");
                        fputcsv($fp, $row);
                        $row = array("CM5.1");
                        fputcsv($fp, $row);
                        $row = array("CM5.2");
                        fputcsv($fp, $row);
                        $row = array("CM5.3");
                        fputcsv($fp, $row);
                        $row = array("CL1.1");
                        fputcsv($fp, $row);
                        $row = array("CL1.2");
                        fputcsv($fp, $row);
                        $row = array("CL1.3");
                        fputcsv($fp, $row);
                        $row = array("CL2.1");
                        fputcsv($fp, $row);
                        $row = array("CL2.2");
                        fputcsv($fp, $row);
                        $row = array("CL3.1");
                        fputcsv($fp, $row);
                        $row = array("CL3.2");
                        fputcsv($fp, $row);
                        $row = array("LD1.1");
                        fputcsv($fp, $row);
                        $row = array("LD1.2");
                        fputcsv($fp, $row);
                        $row = array("LD1.3");
                        fputcsv($fp, $row);
                        $row = array("LD1.4");
                        fputcsv($fp, $row);
                        $row = array("LD2.1");
                        fputcsv($fp, $row);
                        $row = array("LD2.2");
                        fputcsv($fp, $row);
                        $row = array("LD3.1");
                        fputcsv($fp, $row);
                        $row = array("LD3.2");
                        fputcsv($fp, $row);
                        $row = array("LD4.1");
                        fputcsv($fp, $row);
                        $row = array("LD4.2");
                        fputcsv($fp, $row);
                        $row = array("LD4.3");
                        fputcsv($fp, $row);
                        $row = array("HA1.1");
                        fputcsv($fp, $row);
                        $row = array("HA1.2");
                        fputcsv($fp, $row);
                        $row = array("HA1.3");
                        fputcsv($fp, $row);
                        $row = array("HA2.1");
                        fputcsv($fp, $row);
                        $row = array("HA2.2");
                        fputcsv($fp, $row);
                        $row = array("HA2.3");
                        fputcsv($fp, $row);
                        $row = array("SC1.1");
                        fputcsv($fp, $row);
                        $row = array("SC1.2");
                        fputcsv($fp, $row);
                        $row = array("SC1.3");
                        fputcsv($fp, $row);
                        $row = array("SC2.1");
                        fputcsv($fp, $row);
                        $row = array("SC2.2");
                        fputcsv($fp, $row);
                        $row = array("SC2.3");
                        fputcsv($fp, $row);
                        $row = array("SC2.4");
                        fputcsv($fp, $row);
                        $row = array("SC2.5");
                        fputcsv($fp, $row);
                        $row = array("SC2.6");
                        fputcsv($fp, $row);
                        $row = array("SC3.1");
                        fputcsv($fp, $row);
                        $row = array("SC3.2");
                        fputcsv($fp, $row);
                        $row = array("SC3.3");
                        fputcsv($fp, $row);
                        $row = array("SC3.4");
                        fputcsv($fp, $row);
                        $row = array("SC4.1");
                        fputcsv($fp, $row);
                        $row = array("SC4.2");
                        fputcsv($fp, $row);
                        $row = array("SC4.3");
                        fputcsv($fp, $row);
                        $row = array("SC4.4");
                        fputcsv($fp, $row);
                        $row = array("SC4.5");
                        fputcsv($fp, $row);
                        $row = array("PR1.1");
                        fputcsv($fp, $row);
                        $row = array("PR1.2");
                        fputcsv($fp, $row);
                        $row = array("PR1.3");
                        fputcsv($fp, $row);
                        $row = array("PR1.4");
                        fputcsv($fp, $row);
                        $row = array("PR1.5");
                        fputcsv($fp, $row);
                        $row = array("PR2.1");
                        fputcsv($fp, $row);
                        $row = array("PR2.2");
                        fputcsv($fp, $row);
                        $row = array("PR3.1");
                        fputcsv($fp, $row);
                        $row = array("PR3.2");
                        fputcsv($fp, $row);
                        $row = array("PR3.3");
                        fputcsv($fp, $row);
                        $row = array("PR4.1");
                        fputcsv($fp, $row);
                        $row = array("PR4.2");
                        fputcsv($fp, $row);
                        $row = array("PR4.3");
                        fputcsv($fp, $row);
                        fclose($fp);
                        break;
                    case "get-uploaded-msg":
                        if (isset($request["course_id"]) && $tmp_input = clean_input($request["course_id"], array("trim", "int"))) {
                            $course_id = $tmp_input;
                        } else {
                            add_error($translate->_("No course identifier provided."));
                        }

                        if (isset($request["procedure_id"]) && $tmp_input = clean_input($request["procedure_id"], array("trim", "int"))) {
                            $procedure_id = $tmp_input;
                        } else {
                            add_error($translate->_("No Procedure ID provided"));
                        }

                        if (isset($request["organisation_id"]) && $tmp_input = clean_input($request["organisation_id"], array("trim", "int"))) {
                            $organisation_id = $tmp_input;
                        } else {
                            add_error($translate->_("No organisation identifier provided."));
                        }

                        if (!$ERROR) {
                            $html = array();
                            $sorted = array();
                            $procedure_attribute_model = new Models_CBME_ProcedureEPAAttribute();
                            $title = "";
                            $created_date = $translate->_("N/A");
                            $epas = $procedure_attribute_model->getUploadedAttributesEPAsList($course_id, $procedure_id);
                            if (!$epas || empty($epas) || !is_array($epas) || !count($epas)) {
                                $title = $translate->_("There are already attributes attached to this response. Uploading a new file will overwrite them.");
                            } else {
                                $title = $translate->_("There are already attributes attached to this response for the following EPA(s):");
                                $procedure_details = array();
                                foreach ($epas as $epa) {
                                    $attribute_ids = $procedure_attribute_model->getAttributesByObjectiveIDProcedureID($epa["epa_objective_id"], $procedure_id);
                                    $details = array();
                                    $details["objective_code"] = html_encode($epa["objective_code"]);
                                    $details["objective_id"] = html_encode($epa["epa_objective_id"]);
                                    $details["objective_name"] = html_encode($epa["objective_name"]);
                                    $sorted[] = $epa["objective_code"];
                                    foreach($attribute_ids as $attribute_id) {
                                        $procedure_criteria = $procedure_attribute_model->buildProcedureAttributeTree($organisation_id, $attribute_id["attribute_objective_id"]);
                                        $details[] = array("procedure_heading" => html_encode($attribute_id["objective_name"]));
                                        $created_date = date("m-d-Y", $epa["created_date"]);
                                        foreach ($procedure_criteria as $criteria) {
                                            $sub_details = array();
                                            $sub_details["title"] = $criteria["objective_name"];
                                            foreach ($criteria["children"] as $child) {
                                                $sub_details[] = $child["objective_name"];
                                            }
                                            $details[] = $sub_details;
                                        }
                                    }
                                    $procedure_details[] = $details;
                                }
                                $data = array("title" => $title, "upload_date" => $created_date, "epa_list" => $sorted, "criteria" => $procedure_details);
                            }
                            echo json_encode(array("status" => "success", "data" => $data));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        }

                        break;
                    case "get-form-objectives":
                        if (isset($request["form_id"]) && $tmp_input = clean_input($request["form_id"], array("trim", "int"))) {
                            $PROCESSED["form_id"] = $tmp_input;
                        } else {
                            add_error($translate->_("No form provided."));
                        }

                        if (isset($request["objective_id"]) && $tmp_input = clean_input($request["objective_id"], array("trim", "int"))) {
                            $PROCESSED["objective_id"] = $tmp_input;
                        } else {
                            add_error($translate->_("No objective provided."));
                        }

                        if (!$ERROR) {
                            $contextual_variable_objectives = array();
                            $item_code = "";

                            /**
                             * Instantiate the forms api
                             */
                            $forms_api = new Entrada_Assessments_Forms(array(
                                "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                                "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation(),
                            ));

                            $form_model = new Models_Assessments_Form();
                            $form = $form_model->fetchRowByID($PROCESSED["form_id"]);

                            if ($form) {
                                $contextual_variable_objectives = $forms_api->fetchFormContextualVariablesByObjectiveID($PROCESSED["form_id"], $PROCESSED["objective_id"]);
                            } else {
                                add_error($translate->_("No form found."));
                            }

                            /**
                             * Get the form entrustment item and its responses
                             */
                            $assessment_plan = new Entrada_CBME_AssessmentPlan(array(
                                "actor_proxy_id" => $ENTRADA_USER->getActiveID(),
                                "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation()
                            ));

                            $entrustment_item = $assessment_plan->fetchFormEntrustmentItem($PROCESSED["form_id"]);
                            if ($entrustment_item) {
                                $entrustment_item["responses"] = $assessment_plan->fetchFormEntrustmentItemResponses($entrustment_item["item_id"]);
                            }
                        }

                        if (!$ERROR) {
                            echo json_encode(array("status" => "success", "data" => array("objectives" => $contextual_variable_objectives, "form_id" => $form->getID(), "form_title" => $form->getTitle(), "entrustment_item" => $entrustment_item)));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        }
                        break;
                    case "get-cv-responses" :
                        if (isset($request["objective_id"]) && $tmp_input = clean_input($request["objective_id"], array("trim", "int"))) {
                            $PROCESSED["objective_id"] = $tmp_input;
                        } else {
                            add_error($translate->_("No contextual variable provided."));
                        }

                        if (isset($request["course_id"]) && $tmp_input = clean_input($request["course_id"], array("trim", "int"))) {
                            $PROCESSED["course_id"] = $tmp_input;
                        } else {
                            add_error($translate->_("No course provided."));
                        }

                        if (!$ERROR) {
                            $objectives = array();

                            /**
                             * Fetch the objective
                             */
                            $objective_model = new Models_Objective();
                            $objective = $objective_model->fetchRow($PROCESSED["objective_id"]);
                            $order_by = "";

                            if ($objective) {
                                /**
                                 * Fetch contextual variable responses by the provided objective_id and course_id
                                 */
                                $contextual_variable_response_objectives = Models_Objective::fetchAllByParentIDCBMECourseObjective($PROCESSED["objective_id"], $PROCESSED["course_id"], $ENTRADA_USER->getActiveOrganisation(), 1, "a.`objective_name` ASC");
                                if ($contextual_variable_response_objectives) {
                                    foreach ($contextual_variable_response_objectives as $contextual_variable_response_objective) {
                                        $objectives[] = $contextual_variable_response_objective->toArray();
                                    }
                                } else {
                                    add_error($translate->_("This contextual variable has no responses."));
                                }
                            } else {
                                add_error($translate->_("No objective found"));
                            }
                        }

                        if (!$ERROR) {
                            echo json_encode(array("status" => "success", "data" => array("objectives" => $objectives, "objective_id" => $objective->getID(), "objective_name" => $objective->getName())));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        }
                        break;
                }
                break;
        }
    }
    exit;
}