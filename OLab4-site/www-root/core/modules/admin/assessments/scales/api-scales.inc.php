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
 * API to handle interaction with learning object repository.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

if((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "update", false)) {
    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
    header("Location: ".ENTRADA_URL);
    exit;
} else {
    ob_clear_open_buffers();
    $forms_api = new Entrada_Assessments_Forms(array("actor_proxy_id" => $ENTRADA_USER->getActiveId(), "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation()));
    $request_method = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));
    $request = ${"_" . $request_method};

    if ($ENTRADA_USER->getActiveRole() == "admin") {
        if (isset($request["proxy_id"]) && $tmp_input = clean_input($request["proxy_id"], "int")) {
            $PROCESSED["proxy_id"] = $tmp_input;
        } else {
            $PROCESSED["proxy_id"] = $ENTRADA_USER->getActiveID();
        }
    } else {
        $PROCESSED["proxy_id"] = $ENTRADA_USER->getActiveID();
    }

    switch ($request_method) {
        // handler for all POST requests
        case "POST" :
            switch ($request["method"]) {
                case "delete-scales":
                    $time = time();
                    $PROCESSED["delete_ids"] = array();
                    if (isset($request["delete_ids"]) && is_array($request["delete_ids"])) {
                        foreach ($request["delete_ids"] as $scale_id) {
                            $tmp_input = clean_input($scale_id, "int");
                            if ($tmp_input) {
                                $PROCESSED["delete_ids"][] = $tmp_input;
                            }
                        }
                    }
                    if (!empty($PROCESSED["delete_ids"])) {
                        if (!$forms_api->deleteScales($PROCESSED["delete_ids"])) {
                            foreach ($forms_api->getErrorMessages() as $error_message) {
                                add_error($error_message);
                            }
                        }
                        if (!$ERROR) {
                            $success_message = (count($PROCESSED["delete_ids"]) > 1 ? sprintf($translate->_("Successfully deleted %d Rating Scales."), count($PROCESSED["delete_ids"])) : $translate->_("Successfully deleted Rating Scale."));
                            Entrada_Utilities_Flashmessenger::addMessage($success_message, "success", "assessments");
                            echo json_encode(array("status" => "success", "msg" => $success_message, "scale_ids" => $PROCESSED["delete_ids"]));
                        } else {
                            echo json_encode(array("status" => "error", "msg" => $ERRORSTR));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "msg" => $translate->_("Nothing to delete.")));
                    }
                    break;
                case "add-permission" :
                    if (isset($request["member_id"]) && $tmp_input = clean_input($request["member_id"], "int")) {
                        $PROCESSED["member_id"] = $tmp_input;
                    }

                    if (isset($request["member_type"]) && $tmp_input = clean_input($request["member_type"], array("trim", "striptags"))) {
                        $PROCESSED["member_type"] = $tmp_input;
                    }

                    if (isset($request["content_target"]) && $tmp_input = clean_input($request["content_target"], "int")) {
                        $PROCESSED["rating_scale_id"] = $tmp_input;
                    }

                    if ($PROCESSED["member_id"] && $PROCESSED["member_type"] && $PROCESSED["rating_scale_id"]) {
                        $added = 0;
                        $ratingScale = Models_Assessments_RatingScale_Author::fetchRowByRatingScaleIDAuthorIDAuthorType($PROCESSED["rating_scale_id"], $PROCESSED["member_id"], $PROCESSED["member_type"]);
                        if ($ratingScale) {
                            if ($ratingScale->getDeletedDate()) {
                                if ($ratingScale->fromArray(array("deleted_date" => NULL))->update()) {
                                    $added++;
                                }
                            } else {
                                application_log("notice", "Rating Scale author [".$ratingScale->getID()."] is already an active author. API should not have returned this author as an option.");
                            }
                        } else {
                            $ratingScale = new Models_Assessments_RatingScale_Author(
                                array(
                                    "rating_scale_id" => $PROCESSED["rating_scale_id"],
                                    "author_type" => $PROCESSED["member_type"],
                                    "author_id" => $PROCESSED["member_id"],
                                    "created_date" => time(),
                                    "created_by" => $ENTRADA_USER->getActiveID(),
                                    "updated_date" => time(),
                                    "updated_by" => $ENTRADA_USER->getActiveID()
                                )
                            );
                            if ($ratingScale->insert()) {
                                $ENTRADA_LOGGER->log($translate->_("Rating Scale Bank"), "add-permission", "aiauthor_id", $ratingScale->getID(), 4, __FILE__, $ENTRADA_USER->getID());
                                $added++;
                            }
                        }

                        if ($added >= 1) {
                            echo json_encode(array("status" => "success", "data" => array("author_id" => $ratingScale->getID())));
                        } else {
                            echo json_encode(array("status" => "error", "data" => array("Failed to add author")));
                        }
                    }
                    break;
                case "remove-permission" :
                    if (isset($request["aiauthor_id"]) && $tmp_input = clean_input($request["aiauthor_id"], "int")) {
                        $PROCESSED["rating_scale_author_id"] = $tmp_input;
                    }
                    if (isset($PROCESSED["rating_scale_author_id"])) {
                        $author = Models_Assessments_RatingScale_Author::getRowByID($PROCESSED["rating_scale_author_id"]);
                        if (($author->getAuthorType() == "proxy_id" && $author->getAuthorID() != $ENTRADA_USER->getActiveID()) || $author->getAuthorType() != "proxy_id") {
                            if ($author->fromArray(array("deleted_date" => time(), "updated_date" => time(), "updated_by" => $ENTRADA_USER->getActiveID()))->update()) {
                                $ENTRADA_LOGGER->log("Rating Scale Bank", "remove-permission", "aiauthor_id", $PROCESSED["rating_scale_author_id"], 4, __FILE__, $ENTRADA_USER->getID());
                                echo json_encode(array("status" => "success", $translate->_("success.")));
                            } else {
                                echo json_encode(array("status" => "error", "You can't delete yourself."));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("You can't delete yourself.")));
                        }

                    } else {
                        echo json_encode(array("status" => "error"));
                    }
                    break;
                case "set-filter-preferences" :
                    if (isset($request["scale_type"]) && is_array($request["scale_type"])) {
                        $PROCESSED["filters"]["scale_type"] = array_filter($request["scale_type"], function ($scale_type) {
                            return (int) $scale_type;
                        });
                    }

                    if (isset($request["author"]) && is_array($request["author"])) {
                        $PROCESSED["filters"]["author"] = array_filter($request["author"], function ($author) {
                            return (int) $author;
                        });
                    }

                    if (isset($request["course"]) && is_array($request["course"])) {
                        $PROCESSED["filters"]["course"] = array_filter($request["course"], function ($course) {
                            return (int) $course;
                        });
                    }

                    if (isset($request["organisation"]) && is_array($request["organisation"])) {
                        $PROCESSED["filters"]["organisation"] = array_filter($request["organisation"], function ($organisation) {
                            return (int) $organisation;
                        });
                    }

                    foreach (array("scale_type", "author", "course", "organisation") as $filter_type) {
                        Entrada_Utilities_AdvancedSearchHelper::cleanupSessionFilters($request, $MODULE, $SUBMODULE, $filter_type);
                    }

                    if (isset($PROCESSED["filters"])) {
                        Models_Assessments_RatingScale::saveFilterPreferences($PROCESSED["filters"]);
                        $forms_api->updateAssessmentPreferences("assessments");
                        echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully saved the selected filters")));
                    }
                    break;
                case "remove-filter" :
                    if (isset($request["filter_type"]) && $tmp_input = clean_input($request["filter_type"], array("trim", "striptags"))) {
                        $PROCESSED["filter_type"] = $tmp_input;
                    } else {
                        add_error($translate->_("Invalid filter type provided."));
                    }

                    if (isset($request["filter_target"]) && $tmp_input = clean_input($request["filter_target"], array("trim", "int"))) {
                        $PROCESSED["filter_target"] = $tmp_input;
                    } else {
                        add_error($translate->_("Invalid filter target provided."));
                    }

                    if (isset($PROCESSED["filter_type"]) && isset($PROCESSED["filter_target"])) {
                        Entrada_Utilities_AdvancedSearchHelper::removeSessionFilter($MODULE, $SUBMODULE, $PROCESSED["filter_type"], $PROCESSED["filter_target"]);
                        $forms_api->updateAssessmentPreferences("assessments");
                        echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully removed the selected filter")));
                    } else {
                        echo json_encode(array("status" => "error", "msg" => $ERRORSTR));
                    }
                    break;
                case "remove-all-filters" :
                    unset($_SESSION[APPLICATION_IDENTIFIER][$MODULE][$SUBMODULE]["selected_filters"]);
                    $forms_api->updateAssessmentPreferences("assessments");
                    echo json_encode(array("status" => "success", "msg" => $translate->_("Successfully removed all filters")));
                    break;
                default:
                    echo json_encode(array("status" => "error", "data" => $translate->_("Invalid POST method.")));
                    break;
            }
            break;
        // handler for all GET requests & AJAX-GET calls
        case "GET" :
            if (isset($request["rating_scale_id"]) && $tmp_input = clean_input($request["rating_scale_id"], "int")) {
                $PROCESSED["rating_scale_id"] = $tmp_input;
            }
            switch ($request["method"]) {
                // handles request to get the list of all rating scales
                case "get-scales" :
                    $PROCESSED["filters"] = array();

                    // Check which sub module is calling this method. If it's the scales submodule, then we honour the filtering.
                    if (isset($request["submodule"]) && $tmp_input = clean_input(strtolower($request["submodule"]), array("trim", "striptags"))) {
                        $PROCESSED["submodule"] = $tmp_input;
                    } else {
                        $PROCESSED["submodule"] = $SUBMODULE;
                    }

                    // Honour filters only in scales subsection.
                    if ($PROCESSED["submodule"] == "scales") {
                        if (isset($_SESSION[APPLICATION_IDENTIFIER]["assessments"]["scales"]["selected_filters"])) {
                            $PROCESSED["filters"] = $_SESSION[APPLICATION_IDENTIFIER]["assessments"]["scales"]["selected_filters"];
                        }
                    }

                    if (isset($request["search_term"]) && $tmp_input = clean_input(strtolower($request["search_term"]), array("trim", "striptags"))) {
                        $PROCESSED["search_term"] = "%".$tmp_input."%";
                    } else {
                        $PROCESSED["search_term"] = "";
                    }

                    if (isset($request["limit"]) && $tmp_input = clean_input(strtolower($request["limit"]), array("trim", "int"))) {
                        $PROCESSED["limit"] = $tmp_input;
                    } else {
                        $PROCESSED["limit"] = 50;
                    }

                    if (isset($request["offset"]) && $tmp_input = clean_input(strtolower($request["offset"]), array("trim", "int"))) {
                        $PROCESSED["offset"] = $tmp_input;
                    } else {
                        $PROCESSED["offset"] = 0;
                    }

                    if (isset($request["sort_direction"]) && $tmp_input = clean_input(strtolower($request["sort_direction"]), array("trim", "int"))) {
                        $PROCESSED["sort_direction"] = $tmp_input;
                    } else {
                        $PROCESSED["sort_direction"] = "ASC";
                    }

                    if (isset($request["sort_column"]) && $tmp_input = clean_input(strtolower($request["sort_column"]), array("trim", "int"))) {
                        $PROCESSED["sort_column"] = $tmp_input;
                    } else {
                        $PROCESSED["sort_column"] = "rating_scale_id";
                    }

                    if (isset($request["rating_scale_id"]) && $tmp_input = clean_input(strtolower($request["rating_scale_id"]), array("trim", "int"))) {
                        $PROCESSED["rating_scale_id"] = $tmp_input;
                    } else {
                        $PROCESSED["rating_scale_id"] = 0;
                    }

                    if (isset($request["rating_scale_type"])) {
                        if ($request["rating_scale_type"] == 0) {
                            $PROCESSED["rating_scale_type"] = 0;
                        } else {
                            $PROCESSED["rating_scale_type"] = clean_input(strtolower($request["rating_scale_type"]), array("trim", "int"));
                        }
                    } else {
                        $PROCESSED["rating_scale_type"] = null;
                    }
                    $scales = Models_Assessments_RatingScale::fetchAllRecordsBySearchTerm($PROCESSED["search_term"], $PROCESSED["organisation_id"], $PROCESSED["limit"], $PROCESSED["offset"], $PROCESSED["sort_direction"], $PROCESSED["sort_column"], $PROCESSED["filters"], $PROCESSED["rating_scale_type"]);
                    $scale_count = Models_Assessments_RatingScale::fetchCountAllRecordsBySearchTerm($PROCESSED["search_term"], $PROCESSED["organisation_id"], $PROCESSED["limit"], $PROCESSED["offset"], $PROCESSED["sort_direction"], $PROCESSED["sort_column"], $PROCESSED["filters"], $PROCESSED["rating_scale_type"]);
                    $scale_type_name = html_encode($translate->_("Unknown Rating Scale type."));
                    if ($PROCESSED["rating_scale_type"] && $scale_type_record = Models_Assessments_RatingScale_Type::fetchRowByID($PROCESSED["rating_scale_type"])) {
                        $scale_type_name = html_encode($scale_type_record->getTitle());
                    }
                    if ($scales) {
                        $data = array();
                        foreach ($scales as $scale) {
                            $data[] = array(
                                "target_id" => $scale["rating_scale_id"],
                                "target_label" => $scale["rating_scale_title"],
                                "rating_scale_id" => $scale["rating_scale_id"],
                                "rating_scale_title" => $scale["rating_scale_title"],
                                "rating_scale_type" => $scale["rating_scale_type_name"],
                                "created_date" => ($scale["created_date"]) ? date("Y-m-d", $scale["created_date"]) : "N/A"
                            );
                        }
                        echo json_encode(
                            array(
                                "status" => "success",
                                "data" => $data,
                                "current_scales" => count($data),
                                "total_scales" => $scale_count,
                                "level_selectable" => 1
                            )
                        );
                    } else {
                        if ($PROCESSED["rating_scale_type"]) {
                            echo json_encode(array("status" => "error", "data" => sprintf($translate->_("\"%s\" Rating Scales not found."), $scale_type_name)));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $translate->_("No Rating Scales found.")));
                        }
                    }
                    break;
                case "get-scale-types":
                    $scale_types = $forms_api->getScaleTypesInUse($ENTRADA_USER->getActiveOrganisation());
                    if (!empty($scale_types)) {
                        $data = array();
                        foreach ($scale_types as $scale_type) {
                            $data[] = array("target_id" => $scale_type["rating_scale_type_id"], "target_label" => $scale_type["title"], "shortname" => $scale_type["shortname"]);
                        }
                        echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("There are no Rating Scales Types defined.")));
                    }
                    break;
                case "get-filtered-audience" :
                    if (isset($request["item_id"]) && $tmp_input = clean_input(strtolower($request["item_id"]), array("trim", "int"))) {
                        $PROCESSED["item_id"] = $tmp_input;
                    }

                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = "%".$tmp_input."%";
                    }

                    if (isset($request["filter_type"]) && $tmp_input = clean_input($request["filter_type"], array("trim", "striptags"))) {
                        $PROCESSED["filter_type"] = $tmp_input;
                    }

                    if (isset($request["content_target"]) && $tmp_input = clean_input($request["content_target"], "int")) {
                        $PROCESSED["item_id"] = $tmp_input;
                    }

                    $results = Models_Assessments_Item_Author::fetchAvailableAuthors($PROCESSED["filter_type"], $PROCESSED["item_id"], $PROCESSED["search_value"]);
                    if ($results) {
                        echo json_encode(array("results" => count($results), "data" => $results));
                    } else {
                        echo json_encode(array("results" => "0", "data" => array($translate->_("No results"))));
                    }
                    break;
                case "get-user-courses" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    $user_courses = Models_Course::getUserCourses($ENTRADA_USER->getActiveID(), $ENTRADA_USER->getActiveOrganisation(), $PROCESSED["search_value"]);
                    if ($user_courses) {
                        $data = array();
                        foreach ($user_courses as $course) {
                            $data[] = array("target_id" => $course->getID(), "target_label" => $course->getCourseName());
                        }
                        echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No courses were found.")));
                    }
                    break;
                case "get-user-organisations" :
                    $user_organisations = $ENTRADA_USER->getAllOrganisations();
                    if ($user_organisations) {
                        $data = array();
                        foreach ($user_organisations as $key => $organisation) {
                            $data[] = array("target_id" => $key, "target_label" => $organisation);
                        }
                        echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No organisations were found.")));
                    }
                    break;
                case "get-scale-authors" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    $authors = Models_Assessments_RatingScale_Author::searchByAuthor($PROCESSED["search_value"]);
                    if ($authors) {
                        $data = array();
                        foreach ($authors as $author) {
                            $author_name = ($author->getAuthorName() ? $author->getAuthorName() : $translate->_("N/A"));
                            $data[] = array("target_id" => $author->getAuthorID(), "target_label" => $author_name);
                        }
                        echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No authors were found.")));
                    }
                    break;
                case "get-scale-responses":
                    if (isset($request["rating_scale_id"]) && $tmp_input = clean_input(strtolower($request["rating_scale_id"]), array("trim", "int"))) {
                        $scale_id = $tmp_input;
                    } else {
                        echo json_encode(array("status" => "error", "data" => "Invalid scale ID"));
                        exit();
                    }
                    $scale_data = $forms_api->fetchScaleData($scale_id);
                    if (empty($scale_data)) {
                        echo json_encode(array("status" => "error", "data" => "Rating Scale data not found."));
                        exit();
                    }
                    $responses = array();
                    foreach($scale_data["responses"] as $response) {
                        // Copy the descriptor text over to the returned responses array so the JavasScript has access to it.
                        if (@$scale_data["descriptors"][$response["ardescriptor_id"]]["descriptor"]) {
                            $response["descriptor"] = $scale_data["descriptors"][$response["ardescriptor_id"]]["descriptor"];
                        } else {
                            $response["descriptor"] = "";
                        }
                        $responses[] = $response;
                    }
                    echo json_encode(array("status" => "success", "data" => $responses));
                    break;
                default:
                    echo json_encode(array("status" => "error", "data" => $translate->_("Invalid GET method.")));
                    break;
            }
    }
    exit;
}