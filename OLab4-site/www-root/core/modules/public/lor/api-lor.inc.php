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
} elseif (!isset($_SESSION["isAuthorized"]) || !$_SESSION["isAuthorized"]) {
    header("Location: " . ENTRADA_URL);
    exit;
}

ob_clear_open_buffers();
$request_method = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));

$request = ${"_" . $request_method};

switch ($request_method) {
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

                if (isset($request["object_type"]) && $tmp_input = clean_input(strtolower($request["object_type"]), array("trim", "striptags"))) {
                    $PROCESSED["object_type"] = $tmp_input;
                } else {
                    $PROCESSED["object_type"] = "";
                }

                $learning_objects = Models_LearningObject::fetchActiveResources($PROCESSED["search_value"], $PROCESSED["offset"], 50, $PROCESSED["object_type"]);
                if ($learning_objects) {
                    $data = array();
                    $data["total_records"] = Models_LearningObject::countAllResources($PROCESSED["search_value"], $PROCESSED["object_type"]);
                    foreach ($learning_objects as $learning_object) {
                        switch ($learning_object->getObjectType()) {
                            case "tincan":
                            case "scorm":
                                $url = ENTRADA_URL . "/object?id=" . $learning_object->getID();
                                break;

                            default:
                                $url = $learning_object->getUrl();
                                break;
                        }
                        $data["learning_objects"][] = array("url" => $url, "screenshot_filename" => $learning_object->getScreenshotFilename(), "title" => $learning_object->getTitle(), "description" => $learning_object->getDescription(), "authors" => $learning_object->getAuthors(), "primary_usage" => $learning_object->getPrimaryUsage(), "updated_date" => (is_null($learning_object->getUpdatedDate()) ? "Never" : date(DEFAULT_DATE_FORMAT, $learning_object->getUpdatedDate())));
                    }
                    echo json_encode(array("status" => "success", "data" => $data));
                } else {
                    echo json_encode(array("status" => "error", "data" => $translate->_("No Learning Objects were found.")));
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
            case "update-view-preferences" :
                if (isset($request["lor_view"]) && $tmp_input = clean_input(strtolower($request["lor_view"]), array("trim", "striptags"))) {
                    $lor_view = $tmp_input;
                    $old_preferences = $new_preferences = preferences_load("lor");
                    $new_preferences["lor_view"] = $lor_view;
                    preferences_update_user("lor", $ENTRADA_USER->getID(), $old_preferences, $new_preferences);
                    $_SESSION[APPLICATION_IDENTIFIER]["lor"]["lor_view"] = $lor_view;
                } else {
                    add_error("No learning object view was passed");
                }
                if (isset($lor_view)) {
                    echo json_encode(array("status" => "success", "data" => $lor_view));
                } else {
                    echo json_encode(array("status" => "error", "data" => $translate->_("No preferences were found")));
                }
                break;
        }
        break;
}
exit;