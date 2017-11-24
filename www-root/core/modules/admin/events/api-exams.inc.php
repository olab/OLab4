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
 * API to handle interaction with exam postings
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_EVENTS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("exam", "create", false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

    ob_clear_open_buffers();

    $request_method = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));

    $request = ${"_" . $request_method};

    switch ($request_method) {

        case "POST" :
            switch ($request["method"]) {
                case "exam-wizard" :

                    if (isset($request["step"]) && $tmp_input = clean_input($request["step"], array("trim", "int"))) {
                        $PROCESSED["step"] = $tmp_input;
                    } else {
                        add_error($translate->_("No Step provided."));
                    }

                    if (isset($request["next_step"]) && $tmp_input = clean_input($request["next_step"], array("trim", "int"))) {
                        $PROCESSED["next_step"] = $tmp_input;
                    }

                    if (isset($request["post_id"]) && $tmp_input = clean_input($request["post_id"], array("trim", "int"))) {
                        $post_id = $tmp_input;
                    } else {
                        $post_id = null;
                    }

                    if (isset($PROCESSED["step"])) {
                        switch ($PROCESSED["step"]) {
                            case 1 :
                                $exam_controller = new Controllers_Exam_Post($request, false, true, 1);
                                $PROCESSED = $exam_controller->getValidatedData();

                                $next_step = 2;
                                break;
                            case 2 :
                                $exam_controller = new Controllers_Exam_Post($request, false, true, array(1, 2));
                                $PROCESSED = $exam_controller->getValidatedData();

                                if (isset($PROCESSED['secure']) && $PROCESSED['secure'] == 1) {
                                    $next_step = 3;
                                    $previous_step = 2;
                                } else {
                                    $next_step = 4;
                                    $previous_step = 2;
                                }
                                break;
                            case 3 :
                                $exam_controller = new Controllers_Exam_Post($request, false, true, array(1, 2, 3));
                                $PROCESSED = $exam_controller->getValidatedData();

                                $next_step = 4;

                                if (isset($PROCESSED['secure']) && $PROCESSED['secure'] == 1) {
                                    $previous_step = 3;
                                } else {
                                    $previous_step = 2;
                                }

                                break;
                            case 4 :
                                $exam_controller = new Controllers_Exam_Post($request, false, true, array(1, 2, 3, 4));
                                $PROCESSED = $exam_controller->getValidatedData();

                                $next_step = 5;

                                break;
                            case 5 :
                                $exam_controller = new Controllers_Exam_Post($request, false, true, array(1, 2, 3, 4, 5));
                                $PROCESSED = $exam_controller->getValidatedData();

                                $next_step = 6;

                                break;
                            case 6:
                                $exam_controller = new Controllers_Exam_Post($request, false, true, array(1, 2, 3, 4, 5, 6));
                                $PROCESSED = $exam_controller->getValidatedData();

                                if (isset($request["secure_mode"]) && $tmp_input = clean_input(strtolower($request["secure_mode"]), array("trim", "striptags"))) {
                                    $PROCESSED["secure_mode"] = $tmp_input;
                                }

                                if (isset($request["mode"]) && $tmp_input = clean_input(strtolower($request["mode"]), array("trim", "striptags"))) {
                                    $PROCESSED["mode"] = $tmp_input;
                                }

                                if (!has_error()) {
                                    if ($exam_controller->save()) {
                                        $post_id = $exam_controller->getPost()->getID();
                                        $post_model = Models_Exam_Post::fetchRowByID($post_id);

                                        if (isset($PROCESSED["secure_mode"]) && $PROCESSED["secure_mode"] == "seb") {

                                                /**
                                             * Process the Secure Access files and keys
                                             */
                                            if (isset($request["secure_file"]) && is_array($request["secure_file"])) {
                                                $PROCESSED["secure_file"] = $request["secure_file"];
                                            }
                                            if (isset($PROCESSED["secure_file"])) {
                                                foreach ($PROCESSED["secure_file"] as $secure_file) {
                                                    if (isset($secure_file['file']) && $secure_file !== "") {
                                                        $secure_key["resource_type"] = "exam_post";
                                                        $secure_key["resource_id"] = $post_id;
                                                        $file = new Models_Secure_AccessFiles($secure_file);
                                                        if (!$file->insert()) {
                                                            application_log("error", "An error occurred while attempting to save the secure file for this exam. DB:" . $db->ErrorMsg());
                                                        }
                                                    }
                                                }
                                            }

                                            if (isset($request["secure_key"]) && is_array($request["secure_key"])) {
                                                $PROCESSED["secure_key"] = $request["secure_key"];
                                            }

                                            if (isset($PROCESSED["secure_key"])) {
                                                foreach ($PROCESSED["secure_key"] as $secure_key) {
                                                    if (isset($secure_key['key']) && $secure_key !== "") {
                                                        $secure_key["resource_type"] = "exam_post";
                                                        $secure_key["resource_id"] = $exam_controller->getPost()->getID();
                                                        $key = new Models_Secure_AccessKeys($secure_key);
                                                        if (!$key->insert()) {
                                                            application_log("error", "An error occurred while attempting to save secure keys for this exam. DB:" . $db->ErrorMsg());
                                                        }
                                                    }
                                                }
                                            }

                                            $rp_now = Models_Secure_RpNow::fetchRowByPostID($post_id);

                                            if ($rp_now && !$rp_now->fromArray(array("deleted_date" => time(), "updated_date" => time(), "updated_by" => $ENTRADA_USER->getActiveID()))->update()) {
                                                application_log("error", "An error occurred while attempting to delete rpnow id [".$rp_now->getID()."]");
                                            }

                                        } else if (isset($PROCESSED["secure_mode"]) && $PROCESSED["secure_mode"] == "rp_now") {
                                            if (isset($PROCESSED["resume_password_rp_now"]) && isset($PROCESSED["exam_url"]) && isset($PROCESSED["exam_sponsor"])) {

                                                if (isset($PROCESSED["rpnow_reviewed_exam"]) && $tmp_input = clean_input($PROCESSED["rpnow_reviewed_exam"], array("trim", "int"))) {
                                                    $PROCESSED["rpnow_reviewed_exam"] = $tmp_input;
                                                } else {
                                                    $PROCESSED["rpnow_reviewed_exam"] = 0;
                                                }

                                                if (isset($PROCESSED["rpnow_reviewer_notes"]) && $tmp_input = clean_input($PROCESSED["rpnow_reviewer_notes"], array("trim"))) {
                                                    $PROCESSED["rpnow_reviewer_notes"] = $tmp_input;
                                                } else {
                                                    $PROCESSED["rpnow_reviewer_notes"] = "";
                                                }

                                                if (isset($PROCESSED["exam_sponsor"]) && $tmp_input = clean_input($PROCESSED["exam_sponsor"], array("trim", "int"))) {
                                                    $PROCESSED["exam_sponsor"] = $tmp_input;
                                                }

                                                if (isset($PROCESSED["exam_url"]) && $tmp_input = clean_input($PROCESSED["exam_url"], array("nows","nohtml"))) {
                                                    $PROCESSED["exam_url"] = $tmp_input;
                                                }

                                                $rp_now = Models_Secure_RpNow::fetchRowByPostID($post_id);

                                                $rp_now_arr["exam_url"] = $PROCESSED["exam_url"];
                                                $rp_now_arr["exam_sponsor"] = $PROCESSED["exam_sponsor"];
                                                $rp_now_arr["rpnow_reviewed_exam"] = $PROCESSED["rpnow_reviewed_exam"];
                                                $rp_now_arr["rpnow_reviewer_notes"] = $PROCESSED["rpnow_reviewer_notes"];
                                                $rp_now_arr["exam_post_id"] = $post_id;
                                                $rp_now_arr["updated_date"] = time();
                                                $rp_now_arr["updated_by"] = $ENTRADA_USER->getActiveID();

                                                if ($rp_now) {
                                                    if (!$db->AutoExecute("rp_now_config", $rp_now_arr, "UPDATE", "rpnow_id = " . $rp_now->getID())) {
                                                        application_log("error", "An error occurred while attempting to save rp-now con:" . $db->ErrorMsg());
                                                    }
                                                } else {
                                                    if ($db->AutoExecute("rp_now_config", $rp_now_arr, "INSERT")) {
                                                        $rp_now_id = $db->Insert_ID();
                                                    } else {
                                                        add_error($translate->_("An error occurred while attempting to save rp-now"));
                                                        application_log("error", "An error occurred while attempting to save rp-now con:" . $db->ErrorMsg());
                                                    }

                                                    if (!has_error()) {

                                                        foreach ($post_model->getAudience() as $proxy_id) {
                                                            $rp_now_user = new Models_Secure_RpNowUsers();
                                                            $exam_code = $rp_now_user->generateCode(5) . "-" . $post_id . "-" . $proxy_id;

                                                            $rp_now_user_arr["proxy_id"] = $proxy_id;
                                                            $rp_now_user_arr["exam_code"] = $exam_code;
                                                            $rp_now_user_arr["ssi_record_locator"] = null;
                                                            $rp_now_user_arr["rpnow_config_id"] = $rp_now_id;
                                                            $rp_now_user_arr["created_date"] = time();
                                                            $rp_now_user_arr["created_by"] = $ENTRADA_USER->getActiveID();
                                                            $rp_now_user_arr["updated_date"] = time();
                                                            $rp_now_user_arr["updated_by"] = $ENTRADA_USER->getActiveID();

                                                            if (!$db->AutoExecute("rp_now_users", $rp_now_user_arr, "INSERT")) {
                                                                application_log("error", "An error occurred while attempting to save rp-now con:" . $db->ErrorMsg());
                                                            }
                                                        }
                                                    }
                                                }

                                                // Add Secure Files to remove array
                                                $secure_files = Models_Secure_AccessFiles::fetchAllByResourceTypeResourceID("exam_post", $post_id);
                                                foreach ($secure_files as $secure_file) {
                                                    $PROCESSED["secure_file_delete"][] = $secure_file->getID();
                                                }

                                                // Add Secure Key to remove array
                                                $secure_keys = Models_Secure_AccessKeys::fetchAllByResourceTypeResourceID("exam_post", $post_id);
                                                foreach ($secure_keys as $secure_key) {
                                                    $PROCESSED["secure_key_delete"][] = $secure_key->getID();
                                                }
                                            }
                                        } else if (isset($PROCESSED["secure_mode"]) && $PROCESSED["secure_mode"] == "basic") {
                                            if (isset($request["resume_password_basic"])) {
                                                $rp_now = Models_Secure_RpNow::fetchRowByPostID($post_id);

                                                if ($rp_now && !$rp_now->fromArray(array("deleted_date" => time(), "updated_date" => time(), "updated_by" => $ENTRADA_USER->getActiveID()))->update()) {
                                                    application_log("error", "An error occurred while attempting to delete rpnow id [".$rp_now->getID()."]");
                                                }

                                                // Add Secure Files to remove array
                                                $secure_files = Models_Secure_AccessFiles::fetchAllByResourceTypeResourceID("exam_post", $post_id);
                                                foreach ($secure_files as $secure_file) {
                                                    $PROCESSED["secure_file_delete"][] = $secure_file->getID();
                                                }

                                                // Add Secure Key to remove array
                                                $secure_keys = Models_Secure_AccessKeys::fetchAllByResourceTypeResourceID("exam_post", $post_id);
                                                foreach ($secure_keys as $secure_key) {
                                                    $PROCESSED["secure_key_delete"][] = $secure_key->getID();
                                                }
                                            }
                                        }


                                        //Remove the files that are no longer present
                                        if (isset($request["secure_file_delete"]) && is_array($request["secure_file_delete"])) {
                                            $PROCESSED["secure_file_delete"] = $request["secure_file_delete"];
                                        }

                                        if (isset($PROCESSED["secure_file_delete"])) {
                                            foreach ($PROCESSED["secure_file_delete"] as $i => $secure_file_delete) {
                                                $file_to_delete = Models_Secure_AccessFiles::fetchRowByID($secure_file_delete);

                                                if ($file_to_delete) {
                                                    if ($ENTRADA_ACL->amIAllowed(new SecureAccessFileResource($file_to_delete->getID()), "delete")) {
                                                        if ($file_to_delete->delete()) {
                                                            $deleted_file[$i]['id'] = $file_to_delete->getID();
                                                            $deleted_file[$i]['file'] = $file_to_delete->getFileName();
                                                            $deleted_file[$i]['updated_date'] = $file_to_delete->getUpdatedDate();
                                                        } else {
                                                            $error_file[$i]['id'] = $file_to_delete->getID();
                                                            $error_file[$i]['file'] = $file_to_delete->getFileName();
                                                            $error_file[$i]['updated_date'] = $file_to_delete->getUpdatedDate();
                                                            application_log("error", "An error occurred while attempting to delete secure file for this exam. DB:" . $db->ErrorMsg());
                                                        }
                                                    } else {
                                                        $restricted_file[$i]['id'] = $file_to_delete->getID();
                                                        $restricted_file[$i]['file'] = $file_to_delete->getFileName();
                                                        $restricted_file[$i]['updated_date'] = $file_to_delete->getUpdatedDate();
                                                    }
                                                }
                                            }
                                        }

                                        //Remove the keys that are no longer present
                                        if (isset($request["secure_key_delete"]) && is_array($request["secure_key_delete"])) {
                                            $PROCESSED["secure_key_delete"] = $request["secure_key_delete"];
                                        }

                                        if (isset($PROCESSED["secure_key_delete"])) {
                                            foreach ($PROCESSED["secure_key_delete"] as $i => $secure_key_delete) {
                                                $key_to_delete = Models_Secure_AccessKeys::fetchRowByID($secure_key_delete);

                                                if ($key_to_delete) {
                                                    if ($ENTRADA_ACL->amIAllowed(new SecureAccessKeyResource($key_to_delete->getID()), "delete")) {
                                                        if ($key_to_delete->delete()) {
                                                            $deleted_keys[$i]['id'] = $key_to_delete->getID();
                                                            $deleted_keys[$i]['key'] = $key_to_delete->getKey();
                                                            $deleted_keys[$i]['version'] = $key_to_delete->getVersion();
                                                        } else {
                                                            $error_keys[$i]['id'] = $key_to_delete->getID();
                                                            $error_keys[$i]['key'] = $key_to_delete->getKey();
                                                            $error_keys[$i]['version'] = $key_to_delete->getVersion();
                                                            application_log("error", "An error occurred while attempting to delete secure keys for this exam. DB:" . $db->ErrorMsg());
                                                        }
                                                    } else {
                                                        $restricted_keys[$i]['id'] = $key_to_delete->getID();
                                                        $restricted_keys[$i]['key'] = $key_to_delete->getKey();
                                                        $restricted_keys[$i]['version'] = $key_to_delete->getVersion();
                                                    }
                                                }
                                            }
                                        }
                                    } else {
                                        add_error("Failed to save post");
                                    }
                                }
                                $next_step = 7;
                                break;
                            default :
                                add_error($translate->_("Invalid step provided"));
                                break;
                        }

                        if (!$ERROR) {
                            if (isset($PROCESSED["next_step"])) {
                                $step = $PROCESSED["next_step"];
                            } else {
                                $step = $next_step;
                            }

                            $validation_rules = $exam_controller->getValidationRules();
                            $data_with_rules = array();

                            foreach ($PROCESSED as $key => $exam_detail) {
                                $data_with_rules[$key]["value"] = $exam_detail;
                                $data_with_rules[$key]["label"] = $validation_rules[$key]["label"];
                                if ($step == 6) {
                                    $data_with_rules[$key]["display"] = $exam_controller->displayData($validation_rules[$key], $exam_detail);
                                }
                            }

                            $data = array("step" => $step, "post" => $data_with_rules, "rules" => $validation_rules, "post_id" => $post_id);

                            if (isset($previous_step)) {
                                $data["previous_step"] = $previous_step;
                            }

                            echo json_encode(array("status" => "success", "data" => $data));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        }
                    }
                    break;
                case "delete-exam-posts" :
                    if (isset($request["post_ids"]) && is_array($request["post_ids"]) && !empty($request["post_ids"])) {
                        $PROCESSED["delete_allowed"] = array();
                        $PROCESSED["delete_denied"] = array();
                        foreach ($request["post_ids"] as $post_id) {
                            $tmp_input = clean_input(strtolower($post_id), array("trim", "int"));
                            $post = Models_Exam_Post::fetchRowByID($tmp_input);
                            $can_i_delete = $ENTRADA_ACL->amIAllowed(new ExamResource($post->getExamID(), true), "delete");
                            if ($can_i_delete) {
                                $PROCESSED["delete_allowed"][] = $tmp_input;
                            } else {
                                $PROCESSED["delete_denied"][] = $tmp_input;
                            }
                        }
                    } else {
                        add_error($translate->_("Please provide a <strong>Post ID</strong>."));
                    }

                    $deleted_count = 0;
                    $failed_count = 0;
                    $failed = false;
                    $deleted = false;

                    if (isset($PROCESSED["delete_allowed"]) && is_array($PROCESSED["delete_allowed"]) && !empty($PROCESSED["delete_allowed"])) {
                        foreach ($PROCESSED["delete_allowed"] as $post_id) {
                            $post = Models_Exam_Post::fetchRowByID($post_id);
                            $post->setDeletedDate(time());
                            if ($post->update()) {
                                $deleted_count++;
                                $deleted = true;
                            } else {
                                $failed_count++;
                                $failed = true;
                            }
                        }
                    }

                    $message = ($deleted ? "<p>" . $deleted_count . $translate->_(" exam post deleted") . "</p>" : "");
                    $message .= ($failed ? "<p>" . $failed_count . $translate->_(" exam post failed to deleted") . "</p>" : "");
                    $message .= ($PROCESSED["delete_denied"] ? "<p>" . $PROCESSED["delete_denied"] . $translate->_(" exam post denied permission to deleted") . "</p>" : "");

                    if ($deleted == true && $failed == false) {
                        $message .= "<p>" . $translate->_("This page will reload in 5 seconds.") . "</p>";
                        echo json_encode(array(
                            "status" => "success",
                            "msg" => $message
                        ));
                    } else if ($deleted == true && $failed == true) {
                        echo json_encode(array(
                            "status" => "warning",
                            "msg" => $message
                        ));
                    } else if ($PROCESSED["delete_denied"]) {
                        echo json_encode(array("status" => "error", "msg" => $translate->_("Permission to delete deleted")));
                    } else  {
                        echo json_encode(array("status" => "error", "msg" => $translate->_("No Exam Post Deleted")));
                    }
                    break;
                case "delete-secure-keys" :
                    if (isset($request["delete"]) && is_array($request["delete"]) && !empty($request["delete"])) {
                        $PROCESSED["delete"] = $request["delete"];
                    } else {
                        add_error($translate->_("Please provide a <strong>Secure Key ID</strong>."));
                    }

                    if ($PROCESSED["delete"]) {
                        $deleted_keys = array();
                        $error_keys = array();
                        $restricted_keys = array();
                        foreach ($PROCESSED["delete"] as $i => $key_id){
                            $secure_key = Models_Secure_AccessKeys::fetchRowByID($key_id);
                            if ($secure_key) {
                                if ($ENTRADA_ACL->amIAllowed(new SecureAccessKeyResource($secure_key->getID()), "delete")) {
                                    if ($secure_key->delete()) {
                                        $deleted_keys[$i]['id'] = $secure_key->getID();
                                        $deleted_keys[$i]['key'] = $secure_key->getKey();
                                        $deleted_keys[$i]['version'] = $secure_key->getVersion();
                                    } else {
                                        $error_keys[$i]['id'] = $secure_key->getID();
                                        $error_keys[$i]['key'] = $secure_key->getKey();
                                        $error_keys[$i]['version'] = $secure_key->getVersion();
                                    }
                                } else {
                                    $restricted_keys[$i]['id'] = $secure_key->getID();
                                    $restricted_keys[$i]['key'] = $secure_key->getKey();
                                    $restricted_keys[$i]['version'] = $secure_key->getVersion();
                                }
                            }
                        }
                        if (!empty($deleted_keys) && empty($error_keys) && empty($restricted_keys)){
                            echo json_encode(array("status" => "success", "data" => array('deleted' => $deleted_keys)));
                        } elseif (!empty($deleted_keys) && (!empty($error_keys) || !empty($restricted_keys))){
                            echo json_encode(array("status" => "warning", "data" => array('deleted' => $deleted_keys, 'error' => $error_keys, 'restricted' => $restricted_keys)));
                        } elseif (empty($deleted_keys)){
                            echo json_encode(array("status" => "error", "data" => array('error' => $error_keys, 'restricted' => $restricted_keys)));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No keys to delete were submitted")));
                    }
                    break;
                case "add-secure-file":
                    if (isset($request["upload"]) && $tmp_input = clean_input($request["upload"], array("trim", "alpha"))) {
                        $PROCESSED["upload"] = "upload";
                    }

                    if (!$ERROR) {
                        /**
                         * File upload
                         */
                        if (isset($request["post_id"]) && $tmp_input = clean_input($request["post_id"], array("trim", "int"))) {
                            $PROCESSED["post_id"] = $tmp_input;
                        } else {
                            add_error($translate->_("Please provide a <strong>Post ID</strong>."));
                        }
                        if (isset($PROCESSED["post_id"])) {
                            if (isset($request["resource_attach_file"]) && $tmp_input = clean_input($request["resource_attach_file"], array("trim", "alpha"))) {
                                $PROCESSED["resource_attach_file"] = $tmp_input;
                            } else {
                                add_error("Please indicate if you would like to replace the existing file");
                            }
                        }

                        if (isset($_FILES["file"]["name"])) {
                            switch ($_FILES["file"]["error"]) {
                                case 0 :
                                    $PROCESSED["file_size"]		= (int) trim($_FILES["file"]["size"]);
                                    $PROCESSED["file_name"]		= useable_filename(trim($_FILES["file"]["name"]));
                                    $PROCESSED["file_title"]        = $PROCESSED["file_name"]; //Set the file title to the file name by default

                                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                                    $PROCESSED["file_type"] = "application/seb";

                                    finfo_close($finfo);
                                    break;
                                case 1 :
                                case 2 :
                                    add_error("The uploaded file exceeds the allowed file size limit.");
                                    break;
                                case 3 :
                                    add_error("The file that uploaded did not complete the upload process or was interupted. Please try again.");
                                    break;
                                case 4 :
                                    add_error("You did not select a file on your computer to upload. Please select a local file.");
                                    break;
                                case 5 :
                                    add_error("A problem occurred while attempting to upload the file. An administrator has been informed of this error, please try again later.");
                                    break;
                                case 6 :
                                case 7 :
                                    add_error("Unable to store the new file on the server. An Administrator has been informed of this error, please try again later.");
                                    break;
                            }
                        } else {
                            add_error("You did not select a file on your computer to upload. Please select a local file.");
                        }

                        $existing_resource_files = Models_Secure_AccessFiles::fetchAllByResourceTypeResourceID('exam_post', $PROCESSED['post_id']);

                        $PROCESSED["updated_date"] = time();
                        $PROCESSED["updated_by"] = $ENTRADA_USER->getActiveID();
                        $PROCESSED["resource_type"] = "exam_post";
                        $PROCESSED["resource_id"] = $PROCESSED["post_id"];

                        $resource_file = new Models_Secure_AccessFiles($PROCESSED);
                        if ($resource_file->insert()) {
                            if ((@is_dir(SECURE_ACCESS_STORAGE_PATH)) && (@is_writable(SECURE_ACCESS_STORAGE_PATH))) {

                                $resourcePath = SECURE_ACCESS_STORAGE_PATH . "/" . $resource_file->getID();

                                if (@move_uploaded_file($_FILES["file"]["tmp_name"], $resourcePath)) {

                                    //Delete previous file if one exists
                                    if ($existing_resource_files){
                                        foreach ($existing_resource_files as $existing_resource_file){

                                            if ($existing_resource_file->delete()){
                                                application_log("success", "Successfully removed the secure access file " . $existing_resource_file->getID());
                                            } else {
                                                application_log("error", "A problem occurred removing the secure access file " . $existing_resource_file->getID() . " DB said:" . $db->ErrorMsg());
                                            }
                                        }
                                    }
                                }
                            }

                            application_log("success", "Successfully updated secure access file " . $resource_file->getID() . " for Exam Post ID " . $resource_file->getResourceID());
                            echo json_encode(array("status" => "success", "data" => array("secure_access_file" => array("id" => $resource_file->getID(), "resource_type" => $resource_file->getResourceType(), "resource_id" => $resource_file->getResourceID(), "file_name" => $resource_file->getFileName(), "updated_date" => date(DEFAULT_DATE_FORMAT, $resource_file->getUpdatedDate())))));
                        } else {
                            add_error("A problem occurred while attempting to update the secure access access file. Please try again later.");
                            application_log("error", "Failed to update Secure Access File entity " . $PROCESSED["id"] . " for Resource: " . $PROCESSED["post_id"] . " DB said:" . $db->ErrorMsg());
                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
            }
            break;
        case "GET" :
            switch ($request["method"]) {
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
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

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
                case "get-user-exams" :
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    $exams = Models_Exam_Exam::fetchAllByOwner($ENTRADA_USER->getActiveID(), $PROCESSED["search_value"]);

                    if (isset($exams) && is_array($exams) && !empty($exams)) {
                        $data = array();
                        foreach ($exams as $exam) {
                            $data[] = array("target_id" => $exam->getID(), "target_label" => $exam->getTitle());
                        }
                        echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("results" => "0", "data" => $translate->_("No forms found.")));
                    }
                    break;
                case "get-exam-grade-books":
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    if (isset($request["course_id"])) {
                        $tmp_input = (int)$request["course_id"];
                        $PROCESSED["course_id"] = $tmp_input;
                    }

                    //get the exam grade book id for your org
                    $meta = Models_Gradebook_Assessment_LuMeta::fetchRowByOrganisationIdTypeTitle($ENTRADA_USER->getActiveOrganisation(), "exam", "Computer Exam Module");
                    if (isset($meta) && is_object($meta)) {
                        if ($PROCESSED["course_id"]) {
                            $grade_books = Models_Gradebook_Assessment::fetchAllByCourseIdMetaId($PROCESSED["course_id"], $meta->getID());
                            if ($grade_books) {
                                $data = array();
                                foreach ($grade_books as $grade_books) {
                                    $data[] = array("target_id" => $grade_books->getAssessmentID(), "target_label" => $grade_books->getName());
                                }
                                echo json_encode(array("status" => "success", "data" => $data, "level_selectable" => 1));
                            } else {
                                echo json_encode(array("results" => "0", "data" => $translate->_("No Grade Books found in this course.")));
                            }
                        } else {
                            echo json_encode(array("results" => "0", "data" => $translate->_("No Course ID")));
                        }
                    } else {
                        echo json_encode(array("results" => "0", "data" => $translate->_("No Exam Grade Book types for your organisation")));
                    }

                    break;
                case "get-exception-audience":
                    if (isset($request["search_value"]) && $tmp_input = clean_input(strtolower($request["search_value"]), array("trim", "striptags"))) {
                        $PROCESSED["search_value"] = $tmp_input;
                    } else {
                        $PROCESSED["search_value"] = "";
                    }

                    if (isset($request["event_id"])) {
                        $tmp_input = (int)$request["event_id"];
                        $PROCESSED["event_id"] = $tmp_input;
                        if ($PROCESSED["event_id"]) {
                            $event = Models_Event::fetchRowByID($PROCESSED["event_id"]);
                            if ($event && is_object($event)) {
                                $event_start = $event->getEventStart();
                            }
                        }
                    }

                    $audience           = array();
                    $searched_users     = array();
                    $filtered_audience  = array();
                    $event_audiences    = Models_Event_Audience::fetchAllByEventID($PROCESSED["event_id"]);
                    if ($PROCESSED["search_value"] != "") {
                        $filter_users       = User::fetchUsersBySearchQueryGroup($PROCESSED["search_value"]);

                        if (isset($filter_users) && is_array($filter_users) && !empty($filter_users)) {
                            foreach($filter_users as $user) {
                                if (isset($user) && is_object($user)) {
                                    $user_id = $user->getID();
                                    if (!in_array($user_id, $searched_users)) {
                                        $searched_users[] = (int)$user_id;
                                    }
                                }
                            }

                            if (isset($event_audiences) && is_array($event_audiences) && !empty($event_audiences)) {
                                foreach ($event_audiences as $event_audience) {
                                    if (isset($event_audience) && is_object($event_audience)) {
                                        $audience[] = $event_audience->getAudience($event_start);
                                    }
                                }

                                if (is_array($audience) && !empty($audience)) {
                                    $audience_members = Models_Event_Audience::buildAudienceMembers($audience);
                                }
                                $event_audience = array();
                                if (isset($audience_members) && is_array($audience_members) && !empty($audience_members)) {
                                    foreach ($audience_members as $member) {
                                        $member_obj = User::fetchRowByID($member);
                                        if (isset($member_obj) && is_object($member_obj)) {
                                            $name   = $member_obj->getName("%l, %f");
                                            $id     = $member;
                                            if (in_array($id, $searched_users)) {
                                                // name is a searched user match
                                                if (!array_key_exists($id, $filtered_audience)) {
                                                    $filtered_audience[$id] = array("target_id" => $id, "target_label" => $name);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        if (isset($event_audiences) && is_array($event_audiences) && !empty($event_audiences)) {
                            foreach ($event_audiences as $event_audience) {
                                if (isset($event_audience) && is_object($event_audience)) {
                                    $audience[] = $event_audience->getAudience($event_start);
                                }
                            }

                            if (is_array($audience) && !empty($audience)) {
                                $audience_members = Models_Event_Audience::buildAudienceMembers($audience);
                            }

                            $event_audience = array();
                            if (isset($audience_members) && is_array($audience_members) && !empty($audience_members)) {
                                foreach ($audience_members as $member) {
                                    $member_obj = User::fetchRowByID($member);
                                    if (isset($member_obj) && is_object($member_obj)) {
                                        $name = $member_obj->getName("%l, %f");
                                        $id = $member;

                                        if (!array_key_exists($id, $filtered_audience)) {
                                            $filtered_audience[$id] = array("target_id" => $id, "target_label" => $name);
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if ($filtered_audience) {
                        echo json_encode(array("status" => "success", "data" => $filtered_audience, "level_selectable" => 1));
                    } else {
                        echo json_encode(array("results" => "0", "data" => $translate->_("No learners found.")));
                    }
                    break;
                case "get-post-data" :
                    if (isset($request["post_id"]) && $tmp_input = clean_input(strtolower($request["post_id"]), array("trim", "int"))) {
                        $PROCESSED["post_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("Please provide a <strong>Post ID</strong>."));
                    }

                    if ($PROCESSED["post_id"]) {
                        $post = Models_Exam_Post::fetchRowByID($PROCESSED["post_id"]);
                        $exam = Models_Exam_Exam::fetchRowByID($post->getExamID());
                        if ($post) {

                            $data = $post->toArray();

                            $data["time_limit_hours"] = (int)floor($data["time_limit"] / 60);
                            $data["time_limit_mins"] = (int)$data["time_limit"] % 60;
                            $data["exam_title"] = $exam->getTitle();

                            $grade_book_assessment = Models_Gradebook_Assessment::fetchRowByID($post->getGradeBook());
                            if ($grade_book_assessment && is_object($grade_book_assessment)) {
                                $data["grade_book_title"] = $grade_book_assessment->getName();
                            }

                            $secure_file = $post->getSecureAccessFile();
                            if ($secure_file) {
                                $data["secure_access_file"]['id'] = $secure_file->getID();
                                $data["secure_access_file"]['file_name'] = $secure_file->getFileName();
                                $data["secure_access_file"]['updated_date'] = date(DEFAULT_DATE_FORMAT, $secure_file->getUpdatedDate());
                            }

                            $secure_keys = $post->getSecureAccessKeys();
                            if ($secure_keys) {
                                foreach($secure_keys as $key => $secure_key){
                                    $data["secure_access_keys"][$key]['id'] = $secure_key->getID();
                                    $data["secure_access_keys"][$key]['key'] = $secure_key->getKey();
                                    $data["secure_access_keys"][$key]['version'] = $secure_key->getVersion();
                                }
                            }

                            //exceptions
                            $exam_exceptions = $post->getExamExceptions();
                            if (isset($exam_exceptions) && is_array($exam_exceptions) && !empty($exam_exceptions)) {
                                foreach ($exam_exceptions as $exam_exception) {
                                    if (isset($exam_exception) && is_object($exam_exception)) {
                                        $proxy_id = $exam_exception->getProxyID();
                                        $user = User::fetchRowByID($proxy_id);
                                        $label = $user->getName("%l, %f");
                                        $data["exam_exceptions"][$proxy_id]["use_exception_max_attempts"] = $exam_exception->getUseExceptionMaxAttempts();
                                        $data["exam_exceptions"][$proxy_id]["max_attempts"] = $exam_exception->getAttempts();
                                        $data["exam_exceptions"][$proxy_id]["exception_start_date"] = $exam_exception->getStartDate();
                                        $data["exam_exceptions"][$proxy_id]["exception_end_date"] = $exam_exception->getEndDate();
                                        $data["exam_exceptions"][$proxy_id]["exception_submission_date"] = $exam_exception->getSubmissionDate();
                                        $data["exam_exceptions"][$proxy_id]["use_exception_start_date"] = $exam_exception->getUseStartDate();
                                        $data["exam_exceptions"][$proxy_id]["use_exception_end_date"] = $exam_exception->getUseEndDate();
                                        $data["exam_exceptions"][$proxy_id]["use_exception_submission_date"] = $exam_exception->getUseSubmissionDate();
                                        $data["exam_exceptions"][$proxy_id]["exception_time_factor"] = $exam_exception->getExceptionTimeFactor();
                                        $data["exam_exceptions"][$proxy_id]["use_exception_time_factor"] = $exam_exception->getUseExceptionTimeFactor();
                                        $data["exam_exceptions"][$proxy_id]["excluded"] = $exam_exception->getExcluded();
                                        $data["exam_exceptions"][$proxy_id]["label"] = $label;
                                    }
                                }
                            }
                        }
                    }

                    if ($data) {
                        echo json_encode(array("status" => "success", "data" => $data));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No Post Data Found")));
                    }
                    break;
                case "get-exam-post-row" :

                    if (isset($request["post_id"]) && $tmp_input = clean_input(strtolower($request["post_id"]), array("trim", "int"))) {
                        $PROCESSED["post_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("Please provide a <strong>Post ID</strong>."));
                    }

                    if ($PROCESSED["post_id"]) {
                        $post = Models_Exam_Post::fetchRowByID($PROCESSED["post_id"]);
                        $exam = Models_Exam_Exam::fetchRowByID($post->getExamID());
                        if (isset($post) && is_object($post)) {
                            $post_view = new Views_Exam_Post($post);
                            if (isset($post_view) && is_object($post_view)) {
                                $post_view_row = $post_view->renderEventPostAdminRow();
                            }
                        }
                    }

                    if ($post_view) {
                        echo json_encode(array("status" => "success", "post_view" => $post_view_row));
                    } else {
                        echo json_encode(array("status" => "error", "data" => $translate->_("No Post Data Found")));
                    }
                    break;

                case "get-secure-keys" :
                    if (isset($request["post_id"]) && $tmp_input = clean_input(strtolower($request["post_id"]), array("trim", "int"))) {
                        $PROCESSED["post_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("Please provide a <strong>Post ID</strong>."));
                    }
                    if (isset($PROCESSED["post_id"]) && $PROCESSED["post_id"] !== "") {
                        $post = Models_Exam_Post::fetchRowByID($PROCESSED["post_id"]);
                        if ($post) {
                            $data = array();
                            $secure_keys = $post->getSecureAccessKeys();
                            if ($secure_keys){
                                foreach($secure_keys as $key => $secure_key){
                                    $data["secure_access_keys"][$key]['id'] = $secure_key->getID();
                                    $data["secure_access_keys"][$key]['key'] = $secure_key->getKey();
                                    $data["secure_access_keys"][$key]['version'] = $secure_key->getVersion();
                                }
                            }
                        }
                    }

                    if (isset($data)) {
                        echo json_encode(array("status" => "success", "data" => $data));
                    } else {
                        echo json_encode(array("status" => "empty", "data" => $translate->_("Please add a <strong>Secure Key</strong> for this exam")));
                    }
                    break;
                case "get-secure-file" :
                    if (isset($request["post_id"]) && $tmp_input = clean_input(strtolower($request["post_id"]), array("trim", "int"))) {
                        $PROCESSED["post_id"] = $tmp_input;
                    } else {
                        add_error($translate->_("Please provide a <strong>Post ID</strong>."));
                    }
                    if (isset($PROCESSED["post_id"]) && $PROCESSED["post_id"] !== "") {
                        $post = Models_Exam_Post::fetchRowByID($PROCESSED["post_id"]);

                        if ($post) {
                            $data = array();
                            $secure_file = $post->getSecureAccessFile();
                            if ($secure_file && is_object($secure_file)) {
                                $data["secure_access_file"]["id"] = $secure_file->getID();
                                $data["secure_access_file"]["file_name"] = $secure_file->getFileName();
                                $data["secure_access_file"]["updated_date"] = $secure_file->getUpdatedDate();
                            }
                        }
                    }

                    if (isset($data)) {
                        echo json_encode(array("status" => "success", "data" => $data));
                    } else {
                        echo json_encode(array("status" => "empty", "data" => $translate->_("Please add a <strong>Secure File</strong> for this exam")));
                    }
                    break;

            }

            break;


    }
    exit;
}