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
 * @author Organisation: University of British Columbia
 * @author Unit: Faculty of Medicine - Med IT
 * @author Developer: Carlos Torchia <carlos.torchia@ubc.ca>
 * @copyright Copyright 2016 University of British Columbia. All Rights Reserved.
 *
 */

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    dirname(__FILE__) . "/../core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

if (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
    header("Location: ".ENTRADA_URL);
    exit;
} else {
    $request_method = strtoupper(clean_input($_SERVER["REQUEST_METHOD"], "alpha"));

    $request = $_REQUEST;

    if ($ENTRADA_USER->getActiveRole() == "admin") {
        if (isset($request["proxy_id"]) && $tmp_input = clean_input($request["proxy_id"], "int")) {
            $PROCESSED["proxy_id"] = $tmp_input;
        } else {
            $PROCESSED["proxy_id"] = $ENTRADA_USER->getActiveID();
        }
    } else {
        $PROCESSED["proxy_id"] = $ENTRADA_USER->getActiveID();
    }

    /*
     * At least ensure at the highest level that the user is able to read "objective".
     */
    if ($ENTRADA_ACL->amIAllowed("objective", "read", false)) {
        $data = parseData($_REQUEST["data"]);

        switch ($request_method) {
            case "POST":
                switch ($request["method"]) {
                    case "save-table-columns-options":
                        $table_columns = array();
                        if (isset($data) && is_array($data)) {
                            foreach ($data as $column) {
                                $table_columns[$column] = true;
                            }
                        } else {
                            $table_columns = array("objective_name" => true);
                        }
                        $_SESSION["curriculum_tag_table_columns"] = $table_columns;
                        print json_encode($_SESSION["curriculum_tag_table_columns"]);
                        break;

                    case "export-csv":
                        $finalResult = array();
                        $objective_set_id = 0;
                        if (isset($request["set_id"]) && $tmp_input = clean_input($request["set_id"], array("int"))) {
                            $objective_set_id = $tmp_input;
                        }
                        $tags = Models_Objective::fetchAllBySetID($objective_set_id);
                        $set_name = Models_ObjectiveSet::fetchRowByID($objective_set_id)->getTitle();
                        if ($tags && is_array($tags)) {
                            $rows = array();
                            foreach ($tags as $tag) {
                                $rows[] = $tag->toArray();
                            }
                            try {
                                $finalResult = Entrada_Reporting_JSONProcessor::processData(htmlentities(json_encode($rows)));
                                $report = new Entrada_Reporting_CSVReportHandler(date("Ymd") . "_" . clean_input($set_name, ["lower", "underscores", "file"]) . ".csv");
                                $report->_ExcelExportData($finalResult);
                            } catch (InvalidArgumentException $e) {
                                http_response_code(400);
                                application_log("error", "Invalid Arguement " . $e->getMessage());
                                exit;
                            } catch (UnexpectedValueException $e) {
                                http_response_code(500);
                                application_log("error", "UnexpectedValueException " . $e->getMessage());
                                exit;
                            }
                        } else {
                            http_response_code(400);
                            application_log("error", "Error: no data to export");
                            exit;
                        }
                        break;

                    case "import-csv":
                        if (!$ENTRADA_ACL->amIAllowed("objective", "create", false)) {
                            add_error($translate->_("You do not have the permissions required to import curriculum tags."));
                        }

                        $objective_set_id = 0;
                        $file_name = "";
                        $req_headings = array();
                        $parent_tag = false;

                        if (isset($request["set_id"]) && $tmp_input = clean_input($request["set_id"], array("int"))) {
                            $objective_set_id = $tmp_input;
                        }

                        if (isset($request["parent_tag"]) && $tmp_input = clean_input($request["parent_tag"], array("int"))) {
                            $parent_tag = $tmp_input;
                        }

                        // Build CSV headings based on the objective set required fields

                        if ($objective_set = Models_ObjectiveSet::fetchRowByID($objective_set_id)) {
                            $requirements = json_decode($objective_set->getRequirements(), true);
                            foreach ($requirements as $field => $required) {
                                if ($required["required"]) {
                                    $req_headings[] = "objective_" . ($field == "title" ? "name" : $field);
                                }
                            }
                        }

                        if (isset($request["demo"])) {
                            $set_name = "curiculum_tags";
                            if ($objective_set) {
                                $set_name = clean_input($objective_set->getTitle(), ["lower", "underscores", "file"]);
                            }
                            ob_clear_open_buffers();
                            header("Pragma: public");
                            header("Expires: 0");
                            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                            header("Content-Type: application/force-download");
                            header("Content-Type: application/octet-stream");
                            header("Content-Type: text/csv");
                            header("Content-Disposition: attachment; filename=" . $set_name . "_template.csv");
                            header("Content-Transfer-Encoding: binary");
                            $fp = fopen("php://output", "w");

                            $row = array();
                            $data = array();

                            if ($objective_set->getShortname() == "kc" || $objective_set->getShortname() == "ec") {
                                $key_competencies = [];
                                $key_competencies[] = ["objective_code" => "ME1"];
                                $key_competencies[] = ["objective_code" => "ME2"];
                                $key_competencies[] = ["objective_code" => "ME3"];
                                $key_competencies[] = ["objective_code" => "ME4"];
                                $key_competencies[] = ["objective_code" => "ME5"];
                                $key_competencies[] = ["objective_code" => "CM1"];
                                $key_competencies[] = ["objective_code" => "CM2"];
                                $key_competencies[] = ["objective_code" => "CM3"];
                                $key_competencies[] = ["objective_code" => "CM4"];
                                $key_competencies[] = ["objective_code" => "CM5"];
                                $key_competencies[] = ["objective_code" => "CL1"];
                                $key_competencies[] = ["objective_code" => "CL2"];
                                $key_competencies[] = ["objective_code" => "CL3"];
                                $key_competencies[] = ["objective_code" => "CL4"];
                                $key_competencies[] = ["objective_code" => "CL5"];
                                $key_competencies[] = ["objective_code" => "LD1"];
                                $key_competencies[] = ["objective_code" => "LD2"];
                                $key_competencies[] = ["objective_code" => "LD3"];
                                $key_competencies[] = ["objective_code" => "LD4"];
                                $key_competencies[] = ["objective_code" => "LD5"];
                                $key_competencies[] = ["objective_code" => "HA1"];
                                $key_competencies[] = ["objective_code" => "HA2"];
                                $key_competencies[] = ["objective_code" => "SC1"];
                                $key_competencies[] = ["objective_code" => "SC2"];
                                $key_competencies[] = ["objective_code" => "SC3"];
                                $key_competencies[] = ["objective_code" => "SC4"];
                                $key_competencies[] = ["objective_code" => "PR1"];
                                $key_competencies[] = ["objective_code" => "PR2"];
                                $key_competencies[] = ["objective_code" => "PR3"];
                                $key_competencies[] = ["objective_code" => "PR4"];

                                $enabling_competencies = [];
                                $enabling_competencies[] = ["objective_code" => "ME1.1"];
                                $enabling_competencies[] = ["objective_code" => "ME1.2"];
                                $enabling_competencies[] = ["objective_code" => "ME1.3"];
                                $enabling_competencies[] = ["objective_code" => "ME1.4"];
                                $enabling_competencies[] = ["objective_code" => "ME1.5"];
                                $enabling_competencies[] = ["objective_code" => "ME1.6"];
                                $enabling_competencies[] = ["objective_code" => "ME2.1"];
                                $enabling_competencies[] = ["objective_code" => "ME2.2"];
                                $enabling_competencies[] = ["objective_code" => "ME2.3"];
                                $enabling_competencies[] = ["objective_code" => "ME2.4"];
                                $enabling_competencies[] = ["objective_code" => "ME3.1"];
                                $enabling_competencies[] = ["objective_code" => "ME3.2"];
                                $enabling_competencies[] = ["objective_code" => "ME3.3"];
                                $enabling_competencies[] = ["objective_code" => "ME3.4"];
                                $enabling_competencies[] = ["objective_code" => "ME4.1"];
                                $enabling_competencies[] = ["objective_code" => "ME5.1"];
                                $enabling_competencies[] = ["objective_code" => "ME5.2"];
                                $enabling_competencies[] = ["objective_code" => "CM1.1"];
                                $enabling_competencies[] = ["objective_code" => "CM1.2"];
                                $enabling_competencies[] = ["objective_code" => "CM1.3"];
                                $enabling_competencies[] = ["objective_code" => "CM1.4"];
                                $enabling_competencies[] = ["objective_code" => "CM1.5"];
                                $enabling_competencies[] = ["objective_code" => "CM1.6"];
                                $enabling_competencies[] = ["objective_code" => "CM2.1"];
                                $enabling_competencies[] = ["objective_code" => "CM2.2"];
                                $enabling_competencies[] = ["objective_code" => "CM2.3"];
                                $enabling_competencies[] = ["objective_code" => "CM3.1"];
                                $enabling_competencies[] = ["objective_code" => "CM3.2"];
                                $enabling_competencies[] = ["objective_code" => "CM4.1"];
                                $enabling_competencies[] = ["objective_code" => "CM4.2"];
                                $enabling_competencies[] = ["objective_code" => "CM4.3"];
                                $enabling_competencies[] = ["objective_code" => "CM5.1"];
                                $enabling_competencies[] = ["objective_code" => "CM5.2"];
                                $enabling_competencies[] = ["objective_code" => "CM5.3"];
                                $enabling_competencies[] = ["objective_code" => "CL1.1"];
                                $enabling_competencies[] = ["objective_code" => "CL1.2"];
                                $enabling_competencies[] = ["objective_code" => "CL1.3"];
                                $enabling_competencies[] = ["objective_code" => "CL2.1"];
                                $enabling_competencies[] = ["objective_code" => "CL2.2"];
                                $enabling_competencies[] = ["objective_code" => "CL3.1"];
                                $enabling_competencies[] = ["objective_code" => "CL3.2"];
                                $enabling_competencies[] = ["objective_code" => "LD1.1"];
                                $enabling_competencies[] = ["objective_code" => "LD1.2"];
                                $enabling_competencies[] = ["objective_code" => "LD1.3"];
                                $enabling_competencies[] = ["objective_code" => "LD1.4"];
                                $enabling_competencies[] = ["objective_code" => "LD2.1"];
                                $enabling_competencies[] = ["objective_code" => "LD2.2"];
                                $enabling_competencies[] = ["objective_code" => "LD3.1"];
                                $enabling_competencies[] = ["objective_code" => "LD3.2"];
                                $enabling_competencies[] = ["objective_code" => "LD4.1"];
                                $enabling_competencies[] = ["objective_code" => "LD4.2"];
                                $enabling_competencies[] = ["objective_code" => "LD4.3"];
                                $enabling_competencies[] = ["objective_code" => "HA1.1"];
                                $enabling_competencies[] = ["objective_code" => "HA1.2"];
                                $enabling_competencies[] = ["objective_code" => "HA1.3"];
                                $enabling_competencies[] = ["objective_code" => "HA2.1"];
                                $enabling_competencies[] = ["objective_code" => "HA2.2"];
                                $enabling_competencies[] = ["objective_code" => "HA2.3"];
                                $enabling_competencies[] = ["objective_code" => "SC1.1"];
                                $enabling_competencies[] = ["objective_code" => "SC1.2"];
                                $enabling_competencies[] = ["objective_code" => "SC1.3"];
                                $enabling_competencies[] = ["objective_code" => "SC2.1"];
                                $enabling_competencies[] = ["objective_code" => "SC2.2"];
                                $enabling_competencies[] = ["objective_code" => "SC2.3"];
                                $enabling_competencies[] = ["objective_code" => "SC2.4"];
                                $enabling_competencies[] = ["objective_code" => "SC2.5"];
                                $enabling_competencies[] = ["objective_code" => "SC2.6"];
                                $enabling_competencies[] = ["objective_code" => "SC3.1"];
                                $enabling_competencies[] = ["objective_code" => "SC3.2"];
                                $enabling_competencies[] = ["objective_code" => "SC3.3"];
                                $enabling_competencies[] = ["objective_code" => "SC3.4"];
                                $enabling_competencies[] = ["objective_code" => "SC4.1"];
                                $enabling_competencies[] = ["objective_code" => "SC4.2"];
                                $enabling_competencies[] = ["objective_code" => "SC4.3"];
                                $enabling_competencies[] = ["objective_code" => "SC4.4"];
                                $enabling_competencies[] = ["objective_code" => "SC4.5"];
                                $enabling_competencies[] = ["objective_code" => "PR1.1"];
                                $enabling_competencies[] = ["objective_code" => "PR1.2"];
                                $enabling_competencies[] = ["objective_code" => "PR1.3"];
                                $enabling_competencies[] = ["objective_code" => "PR1.4"];
                                $enabling_competencies[] = ["objective_code" => "PR1.5"];
                                $enabling_competencies[] = ["objective_code" => "PR2.1"];
                                $enabling_competencies[] = ["objective_code" => "PR2.2"];
                                $enabling_competencies[] = ["objective_code" => "PR3.1"];
                                $enabling_competencies[] = ["objective_code" => "PR3.2"];
                                $enabling_competencies[] = ["objective_code" => "PR3.3"];
                                $enabling_competencies[] = ["objective_code" => "PR4.1"];
                                $enabling_competencies[] = ["objective_code" => "PR4.2"];
                                $enabling_competencies[] = ["objective_code" => "PR4.3"];

                                $row["objective_code"] = "objective_code";
                                $row["objective_name"] = "objective_name";

                                fputcsv($fp, $row);

                                $tag_sets = ($objective_set->getShortname() == "kc" ? $key_competencies : $enabling_competencies);
                                foreach ($tag_sets as $index => $value) {
                                    fputcsv($fp, [$value["objective_code"], ""]);
                                }
                            } else {
                                foreach ($requirements as $field => $required) {
                                    if ($required["required"]) {
                                        switch ($field) {
                                            case "code":
                                                $data[] = "Code Ipsum";
                                                $row["objective_code"] = "objective_code";
                                                break;
                                            case "title":
                                                $data[] = "Title Ipsum";
                                                $row["objective_name"] = "objective_name";
                                                break;
                                            case "description":
                                                $data[] = "Description ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.";
                                                $row["objective_description"] = "objective_description";
                                                break;
                                            default:
                                                $data[] = null;
                                                break;
                                        }
                                    }
                                }
                                fputcsv($fp, $row);
                                fputcsv($fp, $data);
                            }

                            fclose($fp);
                            exit;
                        }

                        if (isset($_FILES["csv"])) {
                            switch ($_FILES["csv"]["error"]) {
                                case 1 :
                                case 2 :
                                case 3 :
                                    add_error($translate->_("The file that uploaded did not complete the upload process or was interupted. Please try your CSV again."));
                                    break;
                                case 4 :
                                    add_error($translate->_("To import curriculum tags you must select a file to upload from your computer."));
                                    break;
                                case 6 :
                                case 7 :
                                    add_error($translate->_("Unable to store the new file on the server, please try again."));
                                    break;
                                default :
                                    continue;
                                    break;
                            }
                        } else {
                            add_error($translate->_("To import curriculum tags you must select a file to upload from your computer."));
                        }

                        if (!has_error()) {
                            if (!in_array(mime_content_type($_FILES["csv"]["tmp_name"]), array("text/csv", "text/plain", "application/vnd.ms-excel", "text/comma-separated-values", "application/csv", "application/excel", "application/vnd.ms-excel", "application/vnd.msexcel", "application/octet-stream"))) {
                                add_error($translate->_("Invalid <strong>file type</strong> uploaded. Must be a CSV file in the proper format, please try again."));
                            } else {
                                if (($handle = fopen($_FILES["csv"]["tmp_name"], "r")) !== FALSE) {
                                    $tmp_name = explode("/", $_FILES["csv_file"]["tmp_name"]);
                                    $file_name = md5(end($tmp_name));
                                    copy($_FILES["csv"]["tmp_name"], CACHE_DIRECTORY . "/" . $file_name);
                                    fclose($handle);
                                }

                                if (file_exists(CACHE_DIRECTORY . "/" . $file_name)) {
                                    $csv_importer = new Entrada_Curriculum_Import($objective_set_id, $ENTRADA_USER->getActiveId(), $req_headings, $parent_tag);
                                    $csv_importer->importCsv(CACHE_DIRECTORY . "/" . $file_name);
                                } else {
                                    application_log("error", "Unable to find expected file [" . CACHE_DIRECTORY . "/" . $PROCESSED["csv_filename"] . "]");
                                    add_error($translate->_("An error ocurred while attempting to upload the CSV file. An administrator has been informed, please try again later."));
                                }
                            }
                        }

                        if (!has_error()) {
                            $csv_success = $csv_importer->getSuccess();
                            add_success(sprintf($translate->_("Successfully imported <strong>%d</strong> %s curriculum tags from <strong>%s</strong><br /><br /> 
                                                                   You will now be redirected to the edit curriculum tags page; this will happen automatically in 5 seconds 
                                                                   or <a href=\"%s\">click here</a> to continue."), count($csv_success["add"]), (isset($csv_success["update"]) ? "and updated <strong>" . count($csv_success["update"]) . "</strong>" : ""), html_encode($_FILES["csv"]["name"]), ENTRADA_URL . "/admin/curriculum/tags/objectives?set_id=" . $objective_set_id));

                            echo json_encode(array("status" => "success", "data" => $SUCCESSSTR));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        }

                        break;
                }
                break;
            case "GET" :
                switch ($request["method"]) {
                    case "get-table-columns-options":
                        if (isset($_SESSION["curriculum_tag_table_columns"]) && is_array($_SESSION["curriculum_tag_table_columns"])) {
                            $table_columns = $_SESSION["curriculum_tag_table_columns"];
                        } else {
                            $table_columns = array("objective_name" => true);
                        }
                        print json_encode($table_columns);
                        break;

                    case "get-objectives-by-tag-set-id":
                        /**
                         * Service to fetch all objectives within a given tag set.
                         */
                        $PROCESSED["organisation_id"] = $request["organisation_id"];

                        if (!$ENTRADA_ACL->amIAllowed("resourceorganisation" . $PROCESSED["organisation_id"], "read")) {
                            application_log("error", "Group [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] . "] and role [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"] . "] does not have access to this organisation [" . $PROCESSED["organisation_id"] . "]");
                            throw new Exception("You do not have access to organisation [" . $PROCESSED["organisation_id"] . "]");
                        }

                        $PROCESSED["tag_set_id"] = $request["tag_set_id"];

                        $objective_repository = Models_Repository_Objectives::getInstance();

                        if ($PROCESSED["organisation_id"] == -1) {
                            $objectives = $objective_repository->fetchAllByTagSetID($PROCESSED["tag_set_id"]);
                        } else {
                            $objectives = $objective_repository->fetchAllByTagSetIDAndOrganisationID($PROCESSED["tag_set_id"], $PROCESSED["organisation_id"]);
                        }

                        echo json_encode($objective_repository->toArrays($objectives));
                        break;
                    case "get-linked-objectives":
                        /**
                         * Service to fetch all linked objectives linked from a specific objective.
                         * The course_id, cunit_id, and event_id (TODO: cperiod_id) parameters
                         * can be used to constrain links only for the given context(s).
                         */
                        $PROCESSED["objective_id"] = $request["objective_id"];

                        if (isset($request["org_id"])) {
                            $PROCESSED["organisation_id"] = $request["org_id"];
                            if (!$ENTRADA_ACL->amIAllowed("resourceorganisation" . $PROCESSED["organisation_id"], "read")) {
                                application_log("error", "Group [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] . "] and role [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"] . "] does not have access to this organisation [" . $PROCESSED["organisation_id"] . "]");
                                throw new Exception("You do not have access to organisation [" . $PROCESSED["organisation_id"] . "]");
                            }
                        } else {
                            $PROCESSED["organisation_id"] = $ENTRADA_USER->getActiveOrganisation();
                        }

                        if (in_array($request["direction"], array("to", "from"))) {
                            $PROCESSED["direction"] = $request["direction"];
                        } else {
                            throw new Exception("Unexpected objective link direction \"" . $request["direction"] . "\"");
                        }

                        $PROCESSED["exclude_tag_set_ids"] = isset($request["exclude_tag_set_ids"]) ? $request["exclude_tag_set_ids"] : array();
                        $PROCESSED["version_id"] = $request["version_id"];
                        $PROCESSED["event_id"] = isset($request["event_id"]) ? $request["event_id"] : null;
                        $PROCESSED["cunit_id"] = isset($request["cunit_id"]) ? $request["cunit_id"] : null;
                        $PROCESSED["course_id"] = isset($request["course_id"]) ? $request["course_id"] : null;
                        $PROCESSED["cperiod_id"] = isset($request["cperiod_id"]) ? $request["cperiod_id"] : null;
                        $PROCESSED["not"] = !empty($request["not"]) ? true : false;
                        $PROCESSED["show_has_links"] = !empty($request["show_has_links"]) ? true : false;

                        $CONTEXT = new Entrada_Curriculum_Context(array(
                            "event_id" => $PROCESSED["event_id"],
                            "cunit_id" => $PROCESSED["cunit_id"],
                            "course_id" => $PROCESSED["course_id"],
                            "cperiod_id" => $PROCESSED["cperiod_id"],
                        ));

                        $objective_repository = Models_Repository_Objectives::getInstance();
                        $objectives = $objective_repository->excludeByTagSetIDs($objective_repository->fetchLinkedObjectivesByID($PROCESSED["direction"], $PROCESSED["objective_id"], $PROCESSED["version_id"], $CONTEXT, $PROCESSED["not"]), $PROCESSED["exclude_tag_set_ids"]);

                        if ($PROCESSED["show_has_links"]) {
                            $objectives_have_links = $objective_repository->fetchHasLinks($PROCESSED["direction"], $objectives, $PROCESSED["version_id"], $PROCESSED["exclude_tag_set_ids"], $CONTEXT);
                            $objective_rows = $objective_repository->populateHasLinks($objective_repository->toArrays($objectives), $objectives_have_links);
                        } else {
                            $objective_rows = $objective_repository->toArrays($objectives);
                        }

                        $objectives_by_tag_set = $objective_repository->groupArraysByTagSet($objective_rows);
                        echo json_encode($objectives_by_tag_set);
                        break;
                    case "get-objectives":
                        /**
                         * Service for fetching a list of objectives in a matching a query.
                         *
                         * Useful for populating a list of relevant objectives for a user
                         * to choose for adding to some course, unit, event, etc.
                         */
                        try {
                            if (!$ENTRADA_ACL->amIAllowed("search", "read")) {
                                throw new Exception("Permission denied");
                            }

                            $objective_repository = Models_Repository_Objectives::getInstance();

                            $PROCESSED["parent_id"] = clean_input($request["parent_id"], array("trim", "int"));

                            if (isset($request["search_value"])) {
                                $PROCESSED["search_value"] = clean_input(strtolower($request["search_value"]), array("trim", "striptags"));
                            } else {
                                $PROCESSED["search_value"] = null;
                            }

                            if (isset($request["show_codes"]) && $request["show_codes"]) {
                                $PROCESSED["show_codes"] = true;
                            } else {
                                $PROCESSED["show_codes"] = false;
                            }

                            if (isset($request["from_objective_id"])) {
                                $PROCESSED["from_objective_id"] = clean_input($request["from_objective_id"], array("trim", "int"));
                                list($from_tag_set) = $objective_repository->fetchTagSetByObjectives(array(Models_Objective::fetchRow($PROCESSED["from_objective_id"])));
                            }

                            if (isset($request["allowed_tag_set_ids"]) && is_array($request["allowed_tag_set_ids"]) && !empty($request["allowed_tag_set_ids"])) {
                                if (isset($from_tag_set) && isset($request["allowed_tag_set_ids"][$from_tag_set->getID()])) {
                                    $PROCESSED["allowed_tag_set_ids"] = array_map(function ($tag_set_id) {
                                        return clean_input($tag_set_id, array("trim", "int"));
                                    }, $request["allowed_tag_set_ids"][$from_tag_set->getID()]);
                                } else {
                                    $PROCESSED["allowed_tag_set_ids"] = array_map(function ($tag_set_id) {
                                        return clean_input($tag_set_id, array("trim", "int"));
                                    }, $request["allowed_tag_set_ids"]);
                                }
                            }

                            if (isset($request["exclude_tag_set_ids"]) && is_array($request["exclude_tag_set_ids"]) && !empty($request["exclude_tag_set_ids"])) {
                                $PROCESSED["exclude_tag_set_ids"] = array_map(function ($tag_set_id) {
                                    return clean_input($tag_set_id, array("trim", "int"));
                                }, $request["exclude_tag_set_ids"]);
                            }

                            if (isset($request["allowed_objective_ids"]) && is_array($request["allowed_objective_ids"]) && !empty($request["allowed_objective_ids"])) {
                                $PROCESSED["allowed_objective_ids"] = array_map(function (array $objective_ids) {
                                    return array_map(function ($objective_id) {
                                        return clean_input($objective_id, array("trim", "int"));
                                    }, $objective_ids);
                                }, $request["allowed_objective_ids"]);
                            }

                            if (isset($PROCESSED["allowed_objective_ids"][$PROCESSED["parent_id"]])) {
                                $allowed_objectives = Models_Repository_Objectives::getInstance()->fetchAllByIDs($PROCESSED["allowed_objective_ids"][$PROCESSED["parent_id"]]);
                                $objectives = array_filter($allowed_objectives, function (Models_Objective $objective) use ($PROCESSED) {

                                    $objective_name = strtolower($objective->getName());
                                    $objective_description = strtolower($objective->getDescription());
                                    if ($PROCESSED["search_value"]) {
                                        $in_name = (strpos($objective_name, $PROCESSED["search_value"]) !== false);
                                        $in_description = (strpos($objective_description, $PROCESSED["search_value"]) !== false);

                                        return ($in_name || $in_description);
                                    } else {

                                        return true;
                                    }
                                });
                            } else {
                                $objectives = Models_Objective::fetchByOrganisationSearchNameDescription($ENTRADA_USER->getActiveOrganisation(), $PROCESSED["search_value"], $PROCESSED["parent_id"], 200);
                            }

                            $data = array();

                            foreach ($objectives as $objective) {

                                $is_tag_set = ($objective->getParent() == 0);
                                $is_allowed_tag_set = (!isset($PROCESSED["allowed_tag_set_ids"]) || in_array($objective->getID(), $PROCESSED["allowed_tag_set_ids"]));
                                $is_not_excluded_tag_set = (!isset($PROCESSED["exclude_tag_set_ids"]) || !in_array($objective->getID(), $PROCESSED["exclude_tag_set_ids"]));
                                if (!$is_tag_set || ($is_allowed_tag_set && $is_not_excluded_tag_set)) {
                                    $total_child_objectives = (int)Models_Objective::countObjectiveChildren($objective->getID());
                                    $data[] = array(
                                        "target_id" => $objective->getID(),
                                        "target_parent" => $objective->getParent(),
                                        "target_label" => $objective->getObjectiveText($PROCESSED["show_codes"]),
                                        "target_children" => $total_child_objectives,
                                        "level_selectable" => ($total_child_objectives > 0 ? 0 : 1),
                                    );
                                }
                            }

                            echo json_encode(array("status" => "success", "data" => $data));
                        } catch (Exception $e) {
                            echo json_encode(array("status" => "error", "data" => $translate->_($e->getMessage())));
                        }

                        break;
                    case "get-course-objectives":
                        /**
                         * Fetch the objectives associated with this course.
                         *
                         * Useful for updating a view of a course objectives when the user changes
                         * the period you're displaying them for.
                         */
                        try {
                            $PROCESSED["organisation_id"] = $ENTRADA_USER->getActiveOrganisation();

                            if (isset($request["course_id"]) && $tmp_input = clean_input($request["course_id"], "int")) {
                                $PROCESSED["course_id"] = $tmp_input;
                            } else {
                                throw new Exception("No course specified.");
                            }

                            if (isset($request["cperiod_id"]) && $tmp_input = clean_input($request["cperiod_id"], "int")) {
                                $PROCESSED["cperiod_id"] = $tmp_input;
                            } else {
                                $PROCESSED["cperiod_id"] = 0;
                            }

                            if (isset($request["show_codes"]) && $request["show_codes"]) {
                                $PROCESSED["show_codes"] = true;
                            } else {
                                $PROCESSED["show_codes"] = false;
                            }

                            $objective_repository = Models_Repository_Objectives::getInstance();
                            $objectives_by_course = $objective_repository->fetchAllByCourseIDsAndCperiodID(array($PROCESSED["course_id"]), $PROCESSED["cperiod_id"]);
                            if (array_key_exists($PROCESSED["course_id"], $objectives_by_course)) {
                                $objectives = $objectives_by_course[$PROCESSED["course_id"]];
                            } else {
                                $objectives = array();
                            }

                            $objective_rows = array_reduce($objectives, function (array $objective_rows, Models_Objective $objective) use ($PROCESSED) {

                                $objective_rows[] = array(
                                    "objective_id" => $objective->getID(),
                                    "objective_parent" => $objective->getParent(),
                                    "objective_text" => $objective->getObjectiveText($PROCESSED["show_codes"]),
                                );

                                return $objective_rows;
                            }, array());

                            header("Content-Type: application/json");
                            echo json_encode(array("status" => "success", "data" => $objective_rows));
                        } catch (Exception $e) {
                            header("Content-Type: application/json");
                            echo json_encode(array("status" => "error", "data" => $translate->_($e->getMessage())));
                        }

                        break;
                    case "get-course-linked-objectives":
                        /**
                         * Service for fetching linked objectives for a course context, that is
                         * which objectives are linked to which other objectives for this specific course.
                         *
                         * Useful for updating a view of a course's linked objectives when the user changes
                         * the period or version you're displaying them for.
                         */
                        try {
                            $PROCESSED["organisation_id"] = $ENTRADA_USER->getActiveOrganisation();

                            if (isset($request["course_id"]) && $tmp_input = clean_input($request["course_id"], "int")) {
                                $PROCESSED["course_id"] = $tmp_input;
                            } else {
                                throw new Exception("No course specified.");
                            }

                            if (isset($request["cperiod_id"]) && $tmp_input = clean_input($request["cperiod_id"], "int")) {
                                $PROCESSED["cperiod_id"] = $tmp_input;
                            } else {
                                $PROCESSED["cperiod_id"] = 0;
                            }

                            if (isset($request["version_id"]) && $tmp_input = clean_input($request["version_id"], "int")) {
                                $PROCESSED["version_id"] = $tmp_input;
                            } else {
                                $PROCESSED["version_id"] = 0;
                            }

                            if (isset($request["show_codes"]) && $request["show_codes"]) {
                                $PROCESSED["show_codes"] = true;
                            } else {
                                $PROCESSED["show_codes"] = false;
                            }

                            $course = new Models_Course(array("course_id" => $PROCESSED["course_id"]));
                            $objectives = $course->getObjectives($PROCESSED["cperiod_id"]);
                            $linked_objectives = $course->getLinkedObjectives($PROCESSED["version_id"], $objectives);

                            $objective_rows = array();

                            foreach ($linked_objectives as $from_objective_id => $to_objectives) {

                                foreach ($to_objectives as $to_objective_id => $objective) {

                                    $objective_rows[] = array(
                                        "objective_id" => $from_objective_id,
                                        "target_objective_id" => $to_objective_id,
                                        "target_objective_parent" => $objective->getParent(),
                                        "target_objective_text" => $objective->getObjectiveText($PROCESSED["show_codes"]),
                                    );
                                }
                            }

                            header("Content-Type: application/json");
                            echo json_encode(array("status" => "success", "data" => $objective_rows));
                        } catch (Exception $e) {
                            header("Content-Type: application/json");
                            echo json_encode(array("status" => "error", "data" => $translate->_($e->getMessage())));
                        }

                        break;

                    case "get-curriculum-tag-sets" :

                        $tag_sets = Models_ObjectiveSet::fetchAllActiveByOrganisationID($ENTRADA_USER->getActiveOrganisation());

                        if ($tag_sets) {
                            $data = array();
                            foreach ($tag_sets as $tag_set) {
                                $data[] = $tag_set->toArray();
                            }
                            echo json_encode(array("status" => "success", "data" => $data));
                        } else {
                            echo json_encode(array("status" => "error", "data" => "No data provided"));
                        }

                        break;
                    case "get-tags" :
                        if (isset($request["parent_id"]) && $tmp_input = clean_input($request["parent_id"], "int")) {
                            $PROCESSED["parent_id"] = $tmp_input;
                        } else {
                            if (isset($request["first_level"]) && $tmp_input = clean_input($request["first_level"], "int")) {
                                $PROCESSED["parent_id"] = $tmp_input;
                            } else {
                                $PROCESSED["parent_id"] = 0;
                            }
                        }

                        if (isset($request["max_level"]) && $tmp_input = clean_input($request["max_level"], "int")) {
                            $PROCESSED["max_level"] = $tmp_input;
                        } else {
                            $PROCESSED["max_level"] = 0;
                        }

                        $curriculum_tags = Models_Objective::fetchAllByParentID($ENTRADA_USER->getActiveOrganisation(), $PROCESSED["parent_id"]);
                        $data = array();
                        if ($curriculum_tags) {
                            foreach ($curriculum_tags as $tag) {
                                if ($PROCESSED["max_level"] > 0) {
                                    $current_level = Models_Objective::getLevel($tag->getID());
                                    if ($current_level < $PROCESSED["max_level"]) {
                                        $child_tags = Models_Objective::fetchAllByParentID($ENTRADA_USER->getActiveOrganisation(), $tag->getID());
                                    } else {
                                        $child_tags = false;
                                    }
                                } else {
                                    $child_tags = Models_Objective::fetchAllByParentID($ENTRADA_USER->getActiveOrganisation(), $tag->getID());
                                }
                                $target_label = $tag->getShortMethod();

                                $data[] = array("target_id" => $tag->getID(), "target_label" => $target_label, "target_name" => "linked_tags", "target_children" => ($child_tags ? 1 : 0), "no_back_btn" => true);
                            }
                        }
                        $parent = Models_Objective::fetchRow($PROCESSED["parent_id"]);

                        if ($data) {
                            echo json_encode(array("status" => "success", "data" => $data, "parent_id" => ($parent && $parent->getParent() == 0 ? 0 : $parent->getParent()), "parent_name" => ($parent && $parent->getParent() == 0 ? "0" : $parent->getShortMethod()), "no_back_btn" => true));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("No Tag Sets were found.")));
                        }
                        break;
                    case "get-tag-sets" :
                        $data = array();
                        $curriculum_tag_sets = Models_ObjectiveSet::fetchAllActiveByOrganisationID($ENTRADA_USER->getActiveOrganisation());
                        if ($curriculum_tag_sets) {
                            foreach ($curriculum_tag_sets as $set) {
                                $target_label = $set->getTitle();

                                $data[] = array("target_id" => $set->getID(), "target_label" => $target_label, "target_name" => "linked_tags", "target_children" => 0, "level_selectable" => 1);
                            }
                        }

                        if ($data) {
                            echo json_encode(array("status" => "success", "data" => $data));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("No Tag Sets were found.")));
                        }
                        break;
                    case "get-tag-attributes" :
                        if (isset($request["set_id"]) && $tmp_input = clean_input($request["set_id"], "int")) {
                            $PROCESSED["set_id"] = $tmp_input;
                        } else {
                            add_error($translate->_("Objective set id is required."));
                        }
                        $attributes = Models_Objective_TagAttribute::fetchAllByObjectiveSetID($PROCESSED["set_id"]);
                        $data = array();
                        if ($attributes) {
                            foreach ($attributes as $attribute) {
                                $obj_set = Models_ObjectiveSet::fetchRowByID($attribute->getTargetObjectiveSetId());
                                $obj = Models_Objective::fetchRowBySetIDParentID($obj_set->getID(), 0);
                                $data[] = ["target_id" => $obj->getID(), "target_label" => $obj->getShortMethod()];
                            }
                        }

                        if ($data) {
                            echo json_encode(array("status" => "success", "data" => $data));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("No tag attributes were found.")));
                        }
                        break;

                    case "get-curriculum-tags" :
                        getCurriculumTags($data);
                        break;

                    case "get-linked-tags-by-map-version" :
                        if (isset($request["map_version"]) && $tmp_input = clean_input($request["map_version"], "int")) {
                            $PROCESSED["map_version"] = $tmp_input;
                        } else {
                            $PROCESSED["map_version"] = null;
                        }

                        if (isset($request["objective_id"]) && $tmp_input = clean_input($request["objective_id"], "int")) {
                            $PROCESSED["objective_id"] = $tmp_input;
                        } else {
                            add_error($translate->_("Objective ID is required."));
                        }

                        $data = array();
                        if (!has_error()) {
                            $linked_objectives = Models_Objective_LinkedObjective::fetchAllByObjectiveID($PROCESSED["objective_id"], $PROCESSED["map_version"]);
                            if ($linked_objectives) {
                                foreach ($linked_objectives as $linked_objective) {
                                    $objective = Models_Objective::fetchRow($linked_objective->getTargetObjectiveId());
                                    $root = $objective->getRoot();
                                    $root_title = str_replace(" ", "-", $root->getShortMethod());

                                    $data[] = [
                                        "id" => $linked_objective->getTargetObjectiveId(),
                                        "title" => $objective->getShortMethod(),
                                        "root_title" => $root_title,
                                        "root_id" => $root->getID(),
                                    ];
                                }
                            }
                        }

                        if ($data) {
                            echo json_encode(array("status" => "success", "data" => $data));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("No linked objectives were found.")));
                        }
                        break;
                    default:
                        header("HTTP/1.0 400 Bad Request");
                        echo json_encode(array("status" => "error", "data" => "No such method " . $request["method"]));
                        break;
                }
                break;

            case "DELETE" :
                switch ($request["method"]) {
                    case "delete-tag":
                        if (!$ENTRADA_ACL->amIAllowed("curriculum", "delete", false)) {
                            echo json_encode(array("status" => "error", "data" => $translate->_("You do not have permission to delete objectives.")));
                        } else {
                            if (!empty($data) && is_array($data) && !empty($data["delete_ids"]) && is_array($data[delete_ids])) {
                                foreach ($data["delete_ids"] as $id) {
                                    $objective = Models_Objective::fetchRow($id);
                                    if ($objective) {
                                        if ($objective->delete()) {
                                            $objective_org = new Models_Objective_Organisation();

                                            if ($linked_objectives = Models_Objective_LinkedObjective::fetchAllByObjectiveID($id)) {
                                                foreach ($linked_objectives as $linked_objective) {
                                                    $linked_objective->setActive(0);
                                                    $linked_objective->update();
                                                }
                                            }
                                            if ($linked_objectives = Models_Objective_LinkedObjective::fetchAllByTargetObjectiveID($id)) {
                                                foreach ($linked_objectives as $linked_objective) {
                                                    $linked_objective->setActive(0);
                                                    $linked_objective->update();
                                                }
                                            }
                                            if ($objective_org->fromArray(array("objective_id" => $id, "organisation_id" => $ENTRADA_USER->getActiveOrganisation()))->delete()) {
                                                $objective->deleteChildren($id);
                                                add_success("This objective was deleted successfully: " . $id);
                                            } else {
                                                add_error("There was an error deleting this objective: " . $id);
                                            }
                                        } else {
                                            add_error("There was an error deleting this objective: " . $id);
                                        }
                                    } else {
                                        add_error("There is no objectives related to this ID: " . $id);
                                    }

                                    if (!has_error()) {
                                        echo json_encode(array("status" => "success", "data" => $data["delete_ids"]));
                                    } else {
                                        echo json_encode(array("status" => "error", "data" => $translate->_("There was an error deleting these objectives: " . implode(" ", $ERRORSTR))));
                                    }
                                }
                            } else {
                                echo json_encode(array("status" => "error", "data" => $translate->_("There is no data provided")));
                            }
                        }
                        break;
                    case "delete-tag-set":
                        if (!$ENTRADA_ACL->amIAllowed("curriculum", "delete", false)) {
                            echo json_encode(array("status" => "error", "data" => $translate->_("You do not have permission to delete objectives")));
                        } else {
                            if (!empty($data) && is_array($data) && !empty($data["delete_ids"]) && is_array($data["delete_ids"])) {
                                foreach ($data["delete_ids"] as $id) {
                                    $objective_set = Models_ObjectiveSet::fetchRowByID($id);
                                    if ($objective_set) {
                                        $set_deleted = false;
                                        if ($objective_set->fromArray(array("deleted_date" => time()))->update()) {
                                            $objective = Models_Objective::fetchRowBySetIDParentID($objective_set->getID(), 0);
                                            if ($objective->delete()) {
                                                $objective_org = new Models_Objective_Organisation();
                                                if ($objective_org->fromArray(array("objective_id" => $objective->getID(), "organisation_id" => $ENTRADA_USER->getActiveOrganisation()))->delete()) {
                                                    add_success($translate->_("This objectives set was deleted successfully: " . $id));
                                                    $set_deleted = true;
                                                }
                                            }
                                        }
                                        if (!$set_deleted) {
                                            add_error($translate->_("There was an error deleting this objectives set: " . $id));
                                        }
                                    } else {
                                        add_error($translate->_("There is no objectives sets related to this ID: " . $id));
                                    }
                                }
                            } else {
                                add_error($translate->_("There is no data provided"));
                            }

                            if (!has_error()) {
                                echo json_encode(array("status" => "success", "data" => $data["delete_ids"]));
                            } else {
                                echo json_encode(array("status" => "error", "data" => $translate->_("There was an error deleting these objectives sets: " . $ERRORSTR)));
                            }
                        }
                        break;
                    default:
                        header("HTTP/1.0 400 Bad Request");
                        echo json_encode(array("status" => "error", "data" => "No such method " . $request["method"]));
                        break;
                }
                break;
            default:
                header("HTTP/1.0 400 Bad Request");
                echo json_encode(array("status" => "error", "data" => "No such method " . $request["method"]));
                break;
        }
    }
}

function parseData($data)
{
    if (!isset($data) || empty($data)) {
        return array();
    }
    $decoded_html = html_decode($data);
    $decoded_json = json_decode($decoded_html);

    switch (json_last_error()) {
        case JSON_ERROR_NONE:
            break;
        default:
            application_log("error", "curriculum-tags.api.php=> Invalid Data");
            header("HTTP/1.0 501 Not Implemented");
            echo json_encode(array("status" => "error", "data" => "Invalid Data"));
            break;
    }
    return get_object_vars($decoded_json);
}

function getCurriculumTags($arr)
{
    global $ENTRADA_USER;
    $search_term = null;
    $status_filters = array();
    $attribute_filters = array();
    $parent_title = "";

    if (is_array($arr) && !empty($arr)) {
        foreach ($arr as $key => $value) {
            if ($key == "KeywordSearch") {
                $search_term = $value;
            } else if ($key == "objective_status_id") {
                $status_filters["objective_status_id"] = $value;
            } else if ($key == "objective_translation_status_id") {
                $status_filters["objective_translation_status_id"] = $value;
            } else {
                $attribute_filters[$key] = $value;
            }
        }

        if ($objective_set_id = $arr["objective_set_id"]) {
            $maximum_levels = 1;
            if (isset($arr["parent_id"]) && $tmp_input = clean_input($arr["parent_id"], array("trim", "int"))) {
              $parent_id = $tmp_input;
                  if ($parent_objective = Models_Objective::fetchRow($parent_id, 1, $ENTRADA_USER->getActiveOrganisation())) {
                      $level_id = $parent_objective->getParent();
                      $parent_title = $parent_objective->getName();
                      $current_level = Models_Objective::getLevel($parent_id);
                      if ($parent_objective = Models_Objective::fetchRow($level_id, 1, $ENTRADA_USER->getActiveOrganisation())) {
                          $parent = $parent_objective->getParent();
                      }
                  }
            } else {
              $parent_objective = Models_Objective::fetchAllBySetIDParentID($objective_set_id, 0);
              $parent_id = $parent_objective[0]->getID();
              $current_level = Models_Objective::getLevel($parent_id);
            }
            if ($objective_set = Models_ObjectiveSet::fetchRowByID($objective_set_id)) {
                $maximum_levels = $objective_set->getMaximumLevels();
            }
            $linked_tags = false;
            if (Models_Objective_TagAttribute::fetchAllByObjectiveSetID($objective_set_id)) {
                $linked_tags = true;
            }
            $children = Models_Objective::fetchFilteredObjectives($ENTRADA_USER->getActiveOrganisation(), $search_term, $objective_set_id, $parent_id, 1, $status_filters, $attribute_filters, $arr["rows_per_page"], $arr["current_page"]);

            if ($children["data"] && !empty($children["data"])) {
                echo json_encode(array("status" => "success", "data" => $children["data"], "total_rows" => $children["total_rows"], "current_level" => $current_level, "maximum_levels" => $maximum_levels, "level_id" => $level_id, "linked_tags" => $linked_tags, "parent_id" => $parent, "parent_title" => $parent_title));
            } else {
                echo json_encode(array("status" => "error", "data" => "There are no curriculum tags for this set"));
            }
        } else {
            echo json_encode(array("status" => "error", "data" => "There is an error with this tag set"));
        }
    } else {
        echo json_encode(array("status" => "error", "data" => "No data provided"));
    }
}

function delete($arr)
{
    global $ENTRADA_ACL, $translate, $ENTRADA_USER;
    if ($ENTRADA_ACL->amIAllowed("objective", "delete", false)) {
        echo json_encode(array("status" => "error", "data" => $translate->_("You do not have permission to delete objectives")));
    } else {
        if (!empty($arr) && is_array($arr) && !empty($arr["delete_ids"]) && is_array($arr[delete_ids])) {
            foreach ($arr["delete_ids"] as $id) {
                $objective = Models_Objective::fetchRow($id, 1, $ENTRADA_USER->getActiveOrganisation());
                if ($objective) {
                    if($objective->fromArray(array("objective_active" => "0"))->update()) {
                        add_success("This objective was deleted successfully: " . $id);
                    } else {
                        add_error("There was an error deleting this objective: " . $id);
                    }
                } else {
                    add_error("There is no objectives related to this ID: " . $id);
                }

                if (!has_error()) {
                    echo json_encode(array("status" => "success", "data" => $arr["delete_ids"]));
                } else {
                    echo json_encode(array("status" => "error", "data" => $translate->_("There was an error deleting these objectives")));
                }

            }
        }
    }
}