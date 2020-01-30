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
 * This API is for handling requests from the academic advisor meetings interface.
 *
 * @author Organisation: Queen's University
 * @author Unit: Education Technology
 * @author Developer: Alex Ash <aa121@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    dirname(__FILE__) . "/../core/library/vendor",
    get_include_path(),
)));
require_once("init.inc.php");

if ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed('dashboard', 'read')) {
    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
    exit;
} else {
    ob_clear_open_buffers();
    $request = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));
    $request_var = "_" . $request;
    $method = clean_input(${$request_var}["method"], array("trim", "striptags"));
    $MODULE = "assessments";
    switch ($request) {
        case "POST" :
            switch ($method) {
                case "upload-meeting-files" :
                    if (isset($_POST["course_id"]) && $tmp_input = clean_input($_POST["course_id"], "striptags")) {
                        $PROCESSED["meeting_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("Invalid meeting ID"));
                    }
                    if (isset($_POST["file-name"]) && $tmp_input = clean_input($_POST["file-name"], "striptags")) {
                        $PROCESSED["file-name"] = $tmp_input;
                    } else {
                        add_error($translate->_("File name not specified."));
                    }
                    if (!is_dir(CBME_UPLOAD_STORAGE_PATH)
                        || !is_writable(CBME_UPLOAD_STORAGE_PATH)
                    ) {
                        add_error($translate->_("Unable to write to file path."));
                    }
                    if (!has_error()) {
                        $meeting_model = new Models_AcademicAdvisor_Meeting();
                        $meeting = $meeting_model->fetchRowByID($PROCESSED["meeting_id"]);
                        if ($meeting) {
                            if ($ENTRADA_ACL->amIAllowed(new CompetencyCommitteeResource($meeting->getMeetingMemberID(), true), 'read', true) || $ENTRADA_ACL->amIAllowed(new AcademicAdvisorResource($meeting->getMeetingMemberID(), true), "read", true)) {
                                foreach ($_FILES as $FILE) {
                                    if ($FILE["error"] == 0) {
                                        $allowed_mime_types = array(
                                            "image/jpeg", "image/png", "application/pdf",
                                            "application/x-pdf", "application/excel",
                                            "application/vnd.ms-excel", "application/msword",
                                            "application/mspowerpoint", "application/vnd.ms-powerpoint",
                                            "text/richtext", "application/rtf", "application/x-rtf",
                                            "application/zip",
                                            "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                                            "application/vnd.openxmlformats-officedocument.wordprocessingml.template",
                                            "application/vnd.ms-word.document.macroEnabled.12application/vnd.ms-word.template.macroEnabled.12",
                                            "application/vnd.ms-excel",
                                            "application/vnd.ms-excel",
                                            "application/vnd.ms-excel",
                                            "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                                            "application/vnd.openxmlformats-officedocument.spreadsheetml.template",
                                            "application/vnd.ms-excel.sheet.macroEnabled.12",
                                            "application/vnd.ms-excel.template.macroEnabled.12",
                                            "application/vnd.ms-excel.addin.macroEnabled.12",
                                            "application/vnd.ms-excel.sheet.binary.macroEnabled.12",
                                            "application/vnd.ms-powerpoint",
                                            "application/vnd.ms-powerpoint",
                                            "application/vnd.ms-powerpoint",
                                            "application/vnd.ms-powerpoint",
                                            "application/vnd.openxmlformats-officedocument.presentationml.presentation",
                                            "application/vnd.openxmlformats-officedocument.presentationml.template",
                                            "application/vnd.openxmlformats-officedocument.presentationml.slideshow",
                                            "application/vnd.ms-powerpoint.addin.macroEnabled.12",
                                            "application/vnd.ms-powerpoint.presentation.macroEnabled.12",
                                            "application/vnd.ms-powerpoint.template.macroEnabled.12",
                                            "application/vnd.ms-powerpoint.slideshow.macroEnabled.12");

                                        $allowed_mime_names = "JPEG, PNG, PDF, Excel, Word, Powerpoint, Rich Text, ZIP";

                                        $finfo = new finfo(FILEINFO_MIME);
                                        $type = $finfo->file($FILE["tmp_name"]);
                                        $mime_type = explode("; ", $type);

                                        if (in_array($mime_type[0], $allowed_mime_types)) {
                                            // insert the file upload record here
                                            $meeting_model = new Models_AcademicAdvisor_Meeting();
                                            $file_model = new Models_AcademicAdvisor_File();
                                            $file_order = $file_model->getFileOrderByMeetingID($PROCESSED["meeting_id"]);

                                            $file_model->setName($FILE["name"]);
                                            $file_model->setTitle($PROCESSED["file-name"]);
                                            $file_model->setSize($FILE["size"]);
                                            $file_model->setType($FILE["type"]);
                                            $file_model->setFileOrder(isset($file_order["highest_order"]) ? $file_order["highest_order"] + 1 : 0);
                                            $file_model->setMeetingID($PROCESSED["meeting_id"]);
                                            $file_model->setCreatedDate(time());
                                            $file_model->setCreatedBy($ENTRADA_USER->getActiveID());
                                            $file_model->insert();

                                            $new_filename = CBME_UPLOAD_STORAGE_PATH . "/advisor-files/{$PROCESSED["meeting_id"]}-{$FILE["name"]}";

                                            if (file_exists($new_filename)) {
                                                application_log("notice", "File ID [$new_filename] already existed and was overwritten with newer file.");
                                            }
                                            if (move_uploaded_file($FILE["tmp_name"], $new_filename)) {
                                                application_log("success", "File ID " . $FILE["name"] . " was successfully added to the database and filesystem for meeting ID [" . $PROCESSED["meeting_id"] . "].");
                                            }
                                        } else {
                                            add_error(sprintf($translate->_("Invalid file type. Please upload one of the following file types: %s"), $allowed_mime_names));
                                        }
                                    } else {
                                        add_error($translate->_("Invalid file uploaded."));
                                    }
                                }
                            } else {
                                add_error($translate->_("You do not have the proper permissions to upload a file"));
                            }
                        }
                    }
                    if (has_error()) {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    } else {
                        echo json_encode(array("status" => "success", "data" => $translate->_("The file was successfully uploaded")));
                    }
                    break;

                case "serve-meeting-files" :
                    if (isset($_POST["meeting_id"]) && $tmp_input = clean_input($_POST["meeting_id"], array("trim", "int"))) {
                        $PROCESSED["meeting_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("Invalid meeting ID"));
                    }
                    if (isset($_POST["meeting_file_id"]) && $tmp_input = clean_input($_POST["meeting_file_id"], array("trim", "int"))) {
                        $PROCESSED["meeting_file_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("Invalid file ID"));
                    }
                    if (isset($_POST["file_name"]) && $tmp_input = clean_input($_POST["file_name"], "striptags")) {
                        $PROCESSED["file_name"] = $tmp_input;
                    } else {
                        add_error($translate->_("Invalid file name"));
                    }
                    if (isset($_POST["file_type"]) && $tmp_input = clean_input($_POST["file_type"], "striptags")) {
                        $PROCESSED["file_type"] = $tmp_input;
                    } else {
                        add_error($translate->_("Invalid file type"));
                    }
                    if (isset($_POST["file_size"]) && $tmp_input = clean_input($_POST["file_size"], "striptags")) {
                        $PROCESSED["file_size"] = $tmp_input;
                    } else {
                        add_error($translate->_("Invalid file size"));
                    }
                    // Fetch the given file by ID, and verify that the filename given matches what we have in the database (this acts as a sanity check)
                    if (!$ERROR) {
                        $meeting_model = new Models_AcademicAdvisor_Meeting();
                        $meeting = $meeting_model->fetchRowByID($PROCESSED["meeting_id"]);
                        if ($meeting) {
                            if (($ENTRADA_ACL->amIAllowed(new CBMEMeetingResource($meeting->getMeetingMemberID(), true), 'read', true)) || ($ENTRADA_ACL->amIAllowed(new CompetencyCommitteeResource($meeting->getMeetingMemberID(), true), 'read', true) || $ENTRADA_ACL->amIAllowed(new AcademicAdvisorResource($meeting->getMeetingMemberID(), true), "read", true))) {
                                $meeting_file = new Models_AcademicAdvisor_File();
                                if ($found_file = $meeting_file->fetchRowByID($PROCESSED["meeting_file_id"])) {
                                    $found_data = $found_file->toArray();
                                    if ($found_data["deleted_date"]) {
                                        add_error($translate->_("This file was deleted."));
                                    } else if ($found_data["name"] != $PROCESSED["file_name"]) {
                                        add_error($translate->_("Incorrect filename."));
                                    }
                                } else {
                                    add_error($translate->_("File not found."));
                                }
                            } else {
                                add_error($translate->_("You do not have permission to download this file"));
                            }
                        }
                    }
                    if (!$ERROR) {
                        $filename_real_path = CBME_UPLOAD_STORAGE_PATH . "/advisor-files/{$PROCESSED["meeting_id"]}-{$PROCESSED["file_name"]}";
                        if (file_exists($filename_real_path) && is_readable($filename_real_path)) {

                            header("Pragma: public");
                            header("Expires: 0");
                            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                            header("Content-Type: application/force-download");
                            header("Content-Type: application/octet-stream");
                            header("Content-Type: " .$PROCESSED["file_type"] . "");
                            header("Content-Disposition: attachment; filename=\"" . $PROCESSED["file_name"] . "\"");
                            header("Content-Length: " . $PROCESSED["file_size"]);
                            header("Content-Transfer-Encoding: binary");

                            echo file_get_contents($filename_real_path);
                            exit;
                        } else {
                            // TODO: Localize this
                            $TITLE = "Not Found: " . html_encode($PROCESSED["file_name"]);
                            $BODY = display_notice(array("The file that you are trying to download (<strong>" . html_encode($PROCESSED["file_name"]) . "</strong>) does not exist in the filesystem.<br /><br />Please contact a system administrator or the course directory listed on the <a href=\"" . ENTRADA_URL . "/courses?id=" . $result["course_id"] . "\" style=\"font-weight: bold\">course website</a>."));

                            $template_html = fetch_template("global/external");
                            if ($template_html) {
                                echo str_replace(array("%DEFAULT_CHARSET%", "%ENTRADA_URL%", "%TITLE%", "%BODY%"), array(DEFAULT_CHARSET, ENTRADA_URL, $TITLE, $BODY), $template_html);
                            }
                            exit;
                        }
                    }
                    break;

                case "delete-meeting-file":
                    if (isset($_POST["file_id"]) && $tmp_input = clean_input($_POST["file_id"], "striptags")) {
                        $PROCESSED["file_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("Invalid file ID"));
                    }
                    if (isset($_POST["proxy_id"]) && ($tmp_input = clean_input($_POST["proxy_id"], array("trim", "int")))) {
                        $PROCESSED["proxy_id"] = $tmp_input;
                    } else {
                        add_error($translate->_('There was no user ID provided'));
                    }
                    if (!$ERROR) {
                        if ($ENTRADA_ACL->amIAllowed(new CompetencyCommitteeResource($PROCESSED["proxy_id"], true), 'read', true) || $ENTRADA_ACL->amIAllowed(new AcademicAdvisorResource($PROCESSED["proxy_id"], true), "read", true)) {
                            $file_model = new Models_AcademicAdvisor_File();
                            $file = $file_model->fetchRowByID($PROCESSED["file_id"]);
                            if ($file) {
                                $file->setDeletedDate(time());
                                $file->setDeletedBy($ENTRADA_USER->getActiveID());
                                $file->update();
                                Entrada_Utilities_Flashmessenger::addMessage($translate->_("Successfully removed the file from the meeting."), "success", $MODULE);
                            }
                        } else {
                            Entrada_Utilities_Flashmessenger::addMessage($translate->_("You do not have permission to delete this file."), "error", $MODULE);
                        }
                    } else {
                        Entrada_Utilities_Flashmessenger::addMessage($translate->_("An error occurred while trying to delete this file.  Please try again."), "error", $MODULE);
                    }
                    header("Location: " . ENTRADA_URL . "/assessments/meetings?proxy_id=" . $PROCESSED["proxy_id"]);
                break;

                case "delete-meeting":
                    if (isset($_POST["meeting_id"]) && $tmp_input = clean_input($_POST["meeting_id"], "striptags")) {
                        $PROCESSED["meeting_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("Invalid file ID"));
                    }
                    if (isset($_POST["proxy_id"]) && ($tmp_input = clean_input($_POST["proxy_id"], array("trim", "int")))) {
                        $PROCESSED["proxy_id"] = $tmp_input;
                    } else {
                        add_error($translate->_('There was no user ID provided'));
                    }
                    if (!$ERROR) {
                        if ($ENTRADA_ACL->amIAllowed(new CompetencyCommitteeResource($PROCESSED["proxy_id"], true), 'read', true) || $ENTRADA_ACL->amIAllowed(new AcademicAdvisorResource($PROCESSED["proxy_id"], true), "read", true)) {
                            $meeting_model = new Models_AcademicAdvisor_Meeting();
                            $meeting = $meeting_model->fetchRowByID($PROCESSED["meeting_id"]);
                            if ($meeting) {
                                //We want to make sure that the meeting being deleted belongs to the current user
                                if ($meeting->getCreatedBy() === $ENTRADA_USER->getActiveID()) {
                                    $meeting->setDeletedDate(time());
                                    $meeting->setDeletedBy($ENTRADA_USER->getActiveID());
                                    $meeting->update();
                                    Entrada_Utilities_Flashmessenger::addMessage($translate->_("Successfully removed the meeting."), "success", $MODULE);
                                } else {
                                    Entrada_Utilities_Flashmessenger::addMessage($translate->_("You do not have permission to delete this meeting."), "error", $MODULE);
                                }
                            }
                        } else {
                            Entrada_Utilities_Flashmessenger::addMessage($translate->_("You do not have permission to delete this meeting."), "error", $MODULE);
                        }
                    } else {
                        Entrada_Utilities_Flashmessenger::addMessage($translate->_("An error occurred while trying to delete the meeting.  Please try again."), "error", $MODULE);
                    }
                    header("Location: " . ENTRADA_URL . "/assessments/meetings?proxy_id=" . $PROCESSED["proxy_id"]);
                    break;
                break;
            }
            break;
    }
}
