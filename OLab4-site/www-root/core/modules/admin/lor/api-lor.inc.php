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
 * API for learning objects.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Travis Obregon <travismobregon@gmail.com>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 *
 */

if (!defined("PARENT_INCLUDED")) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: " . ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("lor", "update", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] . "] and role [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"] . "] does not have access to this module [" . $MODULE . "]");
} else {
    ob_clear_open_buffers();
    $request_method = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));

    $request = ${"_" . $request_method};

    switch ($request_method) {
        case "POST" :
            switch ($request["method"]) {
                case "delete-learning-objects" :
                    $PROCESSED["delete_ids"] = array();
                    if (isset($request["delete_ids"]) && is_array($request["delete_ids"])) {
                        foreach ($request["delete_ids"] as $delete_id) {
                            $tmp_input = clean_input($delete_id, "int");
                            if ($tmp_input) {
                                $PROCESSED["delete_ids"][] = $tmp_input;
                            }
                        }
                    }
                    if (!empty($PROCESSED["delete_ids"])) {
                        $deleted_learning_objects = array();
                        foreach ($PROCESSED["delete_ids"] as $learning_object_id) {
                            $learning_object = Models_LearningObject::fetchRowByID($learning_object_id);
                            if ($learning_object) {
                                $learning_object->fromArray(array("deleted_date" => time()));
                                if (!$learning_object->update()) {
                                    add_error("Unable to delete a " . $translate->_("Learning Object"));
                                } else {
                                    $deleted_learning_objects[] = $learning_object_id;
                                }
                            }
                        }
                        if (!$ERROR) {
                            echo json_encode(array("status" => "success", "msg" => sprintf("Successfully deleted %d " . $translate->_("Learning Object") . "(s).", count($deleted_learning_objects)), "learning_object_ids" => $deleted_learning_objects));
                        } else {
                            echo json_encode(array("status" => "error", "msg" => $translate->_("There was an error when attempting to delete a " . $translate->_("Learning Object") . ".")));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "msg" => $translate->_("Nothing to delete.")));
                    }
                    break;
                case "add-external-author" :
                    if (isset($request["firstname"]) && $tmp_input = clean_input($request["firstname"], array("trim", "striptags"))) {
                        $PROCESSED["firstname"] = $tmp_input;
                    } else {
                        add_error($translate->_("Please provide a <strong>Firstname</strong> for this author."));
                    }

                    if (isset($request["lastname"]) && $tmp_input = clean_input($request["lastname"], array("trim", "striptags"))) {
                        $PROCESSED["lastname"] = $tmp_input;
                    } else {
                        add_error($translate->_("Please provide a <strong>Lastname</strong> for this author."));
                    }

                    if (isset($request["email"]) && $tmp_input = clean_input($request["email"], array("trim", "striptags"))) {
                        $PROCESSED["email"] = $tmp_input;
                        if (!valid_address($PROCESSED["email"])) {
                            add_error($translate->_("Please provide a <strong>valid E-Mail Address</strong> for this author."));
                        }
                    } else {
                        add_error($translate->_("Please provide a <strong>E-Mail Address</strong> for this author."));
                    }

                    if (!$ERROR) {
                        $is_internal_user = Models_LearningObject_ExternalAuthor::internalUserExists($PROCESSED["email"]);
                        $is_external_user = Models_LearningObject_ExternalAuthor::externalUserExists($PROCESSED["email"]);

                        if (!$is_internal_user && !$is_external_user) {
                            $external_author = new Models_LearningObject_ExternalAuthor(
                                array(
                                    "firstname" => $PROCESSED["firstname"],
                                    "lastname" => $PROCESSED["lastname"],
                                    "email" => $PROCESSED["email"],
                                    "created_date" => time(),
                                    "created_by" => $ENTRADA_USER->getActiveID(),
                                    "updated_date" => time(),
                                    "updated_by" => $ENTRADA_USER->getActiveID()
                                )
                            );

                            if ($external_author->insert()) {
                                echo json_encode(array("status" => "success", "data" => array("id" => $external_author->getID(), "firstname" => $PROCESSED["firstname"], "lastname" => $PROCESSED["lastname"], "email" => $PROCESSED["email"])));
                            } else {
                                echo json_encode(array("status" => "error", "data" => array($translate->_("A problem occurred while attempting to save this author. Please try again later"))));
                            }
                        } else {
                            echo json_encode(array("status" => "error", "data" => array(sprintf($translate->_("There is already a %s user with E-Mail address <strong>%s</strong>"), APPLICATION_NAME, $PROCESSED["email"]))));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                    break;
            }
            break;
        case "GET" :
            switch ($request["method"]) {
                case "get-learning-objects" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    if (isset($request["offset"]) && $tmp_input = clean_input(strtolower($request["offset"]), array("int"))) {
                        $PROCESSED["offset"] = $tmp_input;
                    } else {
                        $PROCESSED["offset"] = 0;
                    }

                    $learning_objects = Models_LearningObject::fetchActiveResources($PROCESSED["search_value"], $PROCESSED["offset"]);
                    if ($learning_objects) {
                        $data = array();
                        $data["total_records"] = Models_LearningObject::countAllResources($PROCESSED["search_value"]);
                        foreach ($learning_objects as $learning_object) {
                            $data["learning_objects"][] = array("learning_object_id" => $learning_object->getID(), "title" => $learning_object->getTitle(), "authors" => $learning_object->getAuthors(), "updated_date" => (is_null($learning_object->getUpdatedDate()) ? "N/A" : date("F Y", $learning_object->getUpdatedDate())));
                        }
                        echo json_encode(array("status" => "success", "data" => $data));
                    } else {
                        echo json_encode(array("status" => "error", "data" => "No " . $translate->_("Learning Objects") . " were found."));
                    }
                    break;
                case "get-organisation-users" :
                    if (isset($request["term"]) && $tmp_input = clean_input(strtolower($request["term"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    $internal_users = Models_Organisation::fetchOrganisationUsers($PROCESSED["search_value"], $ENTRADA_USER->getActiveOrganisation());

                    $data = array();

                    if ($internal_users) {
                        foreach ($internal_users as $user) {
                            $data[] = array("value" => $user["proxy_id"], "label" => $user["firstname"] . " " . $user["lastname"], "lastname" => $user["lastname"], "role" => $translate->_("Internal"), "email" => $user["email"]);
                        }
                    }

                    $external_users = Models_LearningObject_ExternalAuthor::fetchAllBySearchValue($PROCESSED["search_value"], null);

                    if ($external_users) {
                        foreach ($external_users as $user) {
                            $data[] = array("value" => $user["eauthor_id"], "label" => $user["firstname"] . " " . $user["lastname"], "lastname" => $user["lastname"], "role" => $translate->_("External"), "email" => $user["email"]);
                        }
                    }

                    if ($data) {
                        echo json_encode($data);
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No Users found")));
                    }
                    break;
                case "get-images" :
                    // Retrieve a read-stream
                    $stream = $filesystem->readStream(STORAGE_LOR . "/" . Entrada_Utilities_Files::getPathFromFilename($request["image"]) . $request["image"]);

                    /**
                     * This must be done twice in order to close both of the open buffers.
                     */
                    @ob_clear_open_buffers();

                    header("Cache-Control: max-age=2592000");
                    header("Content-type: image/*");
                    header("Content-Disposition: inline; filename=\"" . $request["image"] . "\"");
                    header("Content-Transfer-Encoding: binary\n");

                    echo stream_get_contents($stream);
                    fclose($stream);

                    break;
            }
            break;
    }
    exit;
}