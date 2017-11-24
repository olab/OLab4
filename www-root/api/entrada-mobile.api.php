<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@qmed.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
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

$user_details = array();

if (!isset($_POST["method"]) || !$_POST["method"]) {
    $_POST = $_GET;
}

if (isset($_POST["method"]) && $tmp_input = clean_input($_POST["method"], array("trim", "striptags"))) {
	$method = $tmp_input;
}

if (isset($_POST["sub_method"]) && $tmp_input = clean_input($_POST["sub_method"], "alphanumeric")) {
	$sub_method = $tmp_input;
}

if (isset($_POST["course_id"]) && ((int)$_POST["course_id"])) {
    $course_id = ((int) $_POST["course_id"]);
}

if (isset($_POST["entry_id"]) && ((int)$_POST["entry_id"])) {
    $entry_id = ((int) $_POST["entry_id"]);
}

if (isset($_POST["org_id"]) && $tmp_input = clean_input($_POST["org_id"], "int")) {
	$org_id = $tmp_input;
}

if (isset($_POST["evaluation_id"]) && $tmp_input = clean_input($_POST["evaluation_id"], "alphanumeric")) {
	$evaluation_id = $tmp_input;
}

if (isset($_POST["total_evaluations"]) && $tmp_input = clean_input($_POST["total_evaluations"], "alphanumeric")) {
	$total_evaluations = $tmp_input;
}

if (isset($method)) {
    switch ($method) {
        case "authenticate" :
            if (isset($_POST["username"]) && $tmp_input = clean_input($_POST["username"], "credentials")) {
                $PROCESSED["username"] = $tmp_input;
            }

            if (isset($_POST["password"]) && $tmp_input = clean_input($_POST["password"], "trim")) {
                $PROCESSED["password"] = $tmp_input;
            }

            if (!isset($PROCESSED["username"]) || !isset($PROCESSED["password"])) {
                add_error("Please provide a username and password in order to authenticate.");
            }

            if (!$ERROR) {
                require_once("Entrada/authentication/authentication.class.php");
                $auth = new AuthSystem((((defined("AUTH_DEVELOPMENT")) && (AUTH_DEVELOPMENT != "")) ? AUTH_DEVELOPMENT : AUTH_PRODUCTION));
                $auth->setAppAuthentication(AUTH_APP_ID, AUTH_USERNAME, AUTH_PASSWORD);
                $auth->setEncryption(AUTH_ENCRYPTION_METHOD);
                $auth->setUserAuthentication($PROCESSED["username"], $PROCESSED["password"], AUTH_METHOD);
                $result = $auth->Authenticate(
                    array(
                        "id",
                        "access_id",
                        "prefix",
                        "firstname",
                        "lastname",
                        "email",
                        "email_alt",
                        "email_updated",
                        "google_id",
                        "telephone",
                        "role",
                        "group",
                        "organisation_id",
                        "access_starts",
                        "access_expires",
                        "last_login",
                        "privacy_level",
                        "copyright",
                        "notifications",
                        "private_hash",
                        "private-allow_podcasting",
                        "acl"
                    )
                );

                if ($ERROR == 0 && $result["STATUS"] == "success") {
                    if (($result["ACCESS_STARTS"]) && ($result["ACCESS_STARTS"] > time())) {
                        $ERROR++;
                        $ERRORSTR[] = "Your access to this system does not start until ".date("r", $result["ACCESS_STARTS"]);

                        application_log("mobile", "User[".$username."] tried to access account prior to activation date.");
                    } elseif (($result["ACCESS_EXPIRES"]) && ($result["ACCESS_EXPIRES"] < time())) {
                        $ERROR++;
                        $ERRORSTR[] = "Your access to this system expired on ".date("r", $result["ACCESS_EXPIRES"]);

                        application_log("mobile", "User[".$username."] tried to access account after expiration date.");
                    }

                    if (!$ERROR) {
                        // generate a unique hash here and return it to the device for future authentication.
                        $user_mobile_data = Models_UserMobileData::fetchRowByProxyID($result["ID"]);
                        if ($user_mobile_data) {
                            $hash = $user_mobile_data->getHash();
                            if ($user_mobile_data->getHashExpires() <= time()) {
                                $hash = hash("sha256", (uniqid(rand(), 1) . time() . $result["PRIVATE_HASH"]));
                                $hash_expires = strtotime("+60 days");
                                if(!$user_mobile_data->setHash($hash)->setHashExpires($hash_expires)->update()) {
                                    add_error("A problem occurred during authentication, please try again at a later time.");
                                    application_log("mobile", "An error occured while attempting to update this users [". $result["ID"] ."] mobile hash DB said: " . $db->ErrorMsg());
                                }
                            }
                        } else {
                            $hash = hash("sha256", (uniqid(rand(), 1) . time() . $result["PRIVATE_HASH"]));
                            $user_mobile_data = new Models_UserMobileData(
                                array(
                                    "proxy_id"              => $result["ID"],
                                    "hash"                  => $hash,
                                    "hash_expires"          => strtotime("+60 days"),
                                    "push_notifications"    => 1,
                                    "created_by"            => $result["ID"],
                                    "created_date"          => time(),
                                    "updated_by"            => $result["ID"],
                                    "updated_date"          => time()
                                )
                            );

                            if (!$user_mobile_data->insert()) {
                                add_error("A problem occurred during authentication, please try again at a later time.");
                                application_log("mobile", "An error occured while attempting to save this users [". $result["ID"] ."] mobile hash DB said: " . $db->ErrorMsg());
                                application_log("error", "An error occured while attempting to save this users [". $result["ID"] ."] mobile hash DB said: " . $db->ErrorMsg());
                            }
                        }

                        $data = array();
                        $data["hash"] =  $hash;
                        $data["id"] = $result["ID"];

                        if (!$ERROR) {
                            echo json_encode(array("status" => "success", "data" => $data));
                        } else {
                            echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                        }
                    }
                } else {
                    application_log("mobile", "The provided Username or Password is incorrect.");
                    echo json_encode(array("status" => "error", "data" => array("The Username or Password you have provided is incorrect.")));
                }
            } else {
                application_log("mobile", "Error encountered during authentication. Method: " . $method);
                echo json_encode(array("status" => "error", "data" => $ERRORSTR));
            }
        break;
        default :
            if (isset($_POST["hash"]) && $hash = clean_input($_POST["hash"], array("trim", "striptags"))) {
                $user_mobile_data = Models_UserMobileData::fetchRowByHash($hash);
                if ($user_mobile_data && $user_mobile_data->getHashExpires() >= time()) {
                    $_SESSION["isAuthorized"] = true;
                    switch ($method) {
                        case "photo" :
                            if (isset($_GET["proxy_id"]) && $proxy_id = clean_input($_GET["proxy_id"], array("trim", "int"))) {
                                $proxy_id = $proxy_id;
                            }

                            if (isset($proxy_id)) {
                                $query			= "	SELECT CONCAT_WS(' ', `firstname`, `lastname`) AS `fullname`, `username`, `privacy_level`
                                                    FROM `".AUTH_DATABASE."`.`user_data`
                                                    WHERE `id` = ".$db->qstr($proxy_id);

                                $photo_record	= $db->GetRow($query);
                                $photo_suffix	= "-official-thumbnail";

                                if ($photo_record) {
                                    $display_file = false;

                                    if ((@file_exists(STORAGE_USER_PHOTOS."/".$proxy_id.$photo_suffix)) && (@is_readable(STORAGE_USER_PHOTOS."/".$proxy_id.$photo_suffix))) {
                                        $display_file = STORAGE_USER_PHOTOS."/".$proxy_id.$photo_suffix;
                                    }

                                    if (!$display_file) {
                                        $display_file = ENTRADA_ABSOLUTE."/images/headshot-male.gif";
                                    }

                                    header("Cache-Control: max-age=2592000");
                                    header("Content-Type: ".(isset($photo_record["photo_mimetype"]) ? $photo_record["photo_mimetype"] : "image/jpeg"));
                                    header("Content-Length: ".@filesize($display_file));
                                    header("Content-Disposition: inline; filename=\"".$proxy_id.$photo_suffix.".jpg\"");
                                    header("Content-Transfer-Encoding: binary\n");
                                    unset($_SESSION["isAuthorized"]);
                                    echo @file_get_contents($display_file, FILE_BINARY);
                                }
                            }
                        break;
                        case "sync" :
                            if (isset($_POST["data_type"]) && $data_type = clean_input($_POST["data_type"], array("trim", "striptags"))) {
                                $data_type = $data_type;
                            } else {
                                //add_error($translate->_("No synchronization data type provided"));
                            }

                            if (isset($_POST["last_sync"]) && $last_sync = clean_input($_POST["last_sync"], array("trim", "int"))) {
                                $last_sync = $last_sync;
                            } else {
                                $last_sync = 0;
                            }

                            if (!$ERROR) {
                                switch ($data_type) {
                                    default :
                                        $data                           = array();
                                        $assessment_content             = array();
                                        $assessment_progress            = array();
                                        $assessment_progress_responses  = array();
                                        $assessment_distributions       = array();
                                        $assessment_forms               = array();
                                        $form_elements                  = array();
                                        $items                          = array();
                                        $item_responses                 = array();
                                        $item_objectives                = array();
                                        $assessment_targets             = array();
                                        $response_descriptors           = array();
                                        $objective_item_content         = array();

                                        if (isset($_POST["progress"])) {
                                            $progress_records = json_decode($_POST["progress"], true);
                                            $progress_response_records = json_decode($_POST["responses"], true);
                                            if ($progress_records) {
                                                foreach ($progress_records as $progress_record) {
                                                    $PROCESSED = array();
                                                    $tmp_aprogress_id = clean_input($progress_record["aprogress_id"], array("trim", "int"));
                                                    unset($progress_record["aprogress_id"]);
                                                    $PROCESSED["uuid"] = clean_input($progress_record["uuid"], array("trim", "striptags"));
                                                    $existing_progress_record = Models_Assessments_Progress::fetchRowByUuid($PROCESSED["uuid"]);
                                                    if ($existing_progress_record) {
                                                        if ($progress_record["updated_date"] > $existing_progress_record->getUpdatedDate()) {
                                                            // web app needs to updated
                                                            $PROCESSED["one45_formsAttached_id"]            = (is_null($progress_record["one45_formsAttached_id"]) ? null : clean_input($progress_record["one45_formsAttached_id"], array("trim", "int")));
                                                            $PROCESSED["one45_p_id"]                        = (is_null($progress_record["one45_p_id"]) ? null : clean_input($progress_record["one45_p_id"], array("trim", "int")));
                                                            $PROCESSED["one45_moment_id"]                   = (is_null($progress_record["one45_moment_id"]) ? null : clean_input($progress_record["one45_moment_id"], array("trim", "int")));
                                                            $PROCESSED["adistribution_id"]                  = clean_input($progress_record["adistribution_id"], array("trim", "int"));
                                                            $PROCESSED["assessor_type"]                     = clean_input($progress_record["assessor_type"], array("trim", "striptags"));
                                                            $PROCESSED["assessor_value"]                    = clean_input($progress_record["assessor_value"], array("trim", "int"));
                                                            $PROCESSED["dassessment_id"]                    = (is_null($progress_record["dassessment_id"]) ? null : clean_input($progress_record["dassessment_id"], array("trim", "int")));
                                                            $PROCESSED["adtarget_id"]                       = (is_null($progress_record["adtarget_id"]) ? null : clean_input($progress_record["adtarget_id"], array("trim", "int")));
                                                            $PROCESSED["target_record_id"]                  = (is_null($progress_record["target_record_id"]) ? null : clean_input($progress_record["target_record_id"], array("trim", "int")));
                                                            $PROCESSED["target_learning_context_id"]        = (is_null($progress_record["target_learning_context_id"]) ? null : clean_input($progress_record["target_learning_context_id"], array("trim", "int")));
                                                            $PROCESSED["progress_value"]                    = (is_null($progress_record["progress_value"]) ? null : clean_input($progress_record["progress_value"], array("trim", "striptags")));
                                                            $PROCESSED["uuid"]                              = (is_null($progress_record["uuid"]) ? null : clean_input($progress_record["uuid"], array("trim", "striptags")));
                                                            $PROCESSED["created_date"]                      = clean_input($progress_record["created_date"], array("trim", "int"));
                                                            $PROCESSED["created_by"]                        = clean_input($progress_record["created_by"], array("trim", "int"));
                                                            $PROCESSED["updated_date"]                      = (is_null ($progress_record["updated_date"]) ? null : clean_input($progress_record["updated_date"], array("trim", "int")));
                                                            $PROCESSED["deleted_date"]                      = (is_null($progress_record["deleted_date"]) ? null : clean_input($progress_record["deleted_date"], array("trim", "int")));
                                                            $PROCESSED["updated_by"]                        = (is_null($progress_record["updated_by"]) ? null : clean_input($progress_record["updated_by"], array("trim", "int")));

                                                            if (!$existing_progress_record->fromArray($PROCESSED)->update()) {
                                                                application_log("mobile", "An error occurred while attempting to update this progress record [aresponse_id ". $existing_progress_record->getID() ."] DB said: " . $db->ErrorMsg());
                                                            } else {
                                                                $distribution = Models_Assessments_Distribution::fetchRowByID($PROCESSED["adistribution_id"]);
                                                                if ($distribution) {
                                                                    $form = Models_Assessments_Form::fetchRowByID($distribution->getFormID());
                                                                    if ($form) {
                                                                        $form_elements = Models_Assessments_Form_Element::fetchAllByFormID($form->getID());
                                                                        if ($form_elements) {
                                                                            foreach ($form_elements as $form_element) {
                                                                                $PROCESSED_RESPONSE = array();
                                                                                switch ($form_element->getElementType()) {
                                                                                    case "item" :
                                                                                        $item = Models_Assessments_Item::fetchRowByID($form_element->getElementID());
                                                                                        if ($item) {
                                                                                            switch ($item->getItemtypeID()) {
                                                                                                case "1" :
                                                                                                case "2" :
                                                                                                case "3" :
                                                                                                case "9" :
                                                                                                case "11" :
                                                                                                case "12" :
                                                                                                    if ($progress_response_records) {
                                                                                                        foreach ($progress_response_records as $progress_response_record) {
                                                                                                            if ($progress_response_record["aprogress_id"] == $tmp_aprogress_id) {
                                                                                                                if ($form_element->getID() == $progress_response_record["afelement_id"]) {
                                                                                                                    $existing_response = Models_Assessments_Progress_Response::fetchRowByAprogressIDAfelementID($existing_progress_record->getID(), $form_element->getID());
                                                                                                                    if ($existing_response) {
                                                                                                                        if ($existing_response->getIresponseID() != $progress_response_record["iresponse_id"]) {
                                                                                                                            $PROCESSED_RESPONSE["iresponse_id"] = clean_input($progress_response_record["iresponse_id"], array("trim", "int"));
                                                                                                                            $PROCESSED_RESPONSE["updated_date"] = clean_input($progress_response_record["updated_date"], array("trim", "int"));
                                                                                                                            $PROCESSED_RESPONSE["updated_by"] = clean_input($progress_response_record["updated_by"], array("trim", "int"));

                                                                                                                            if (!$existing_response->fromArray($PROCESSED_RESPONSE)->update()) {
                                                                                                                                application_log("mobile", "An error occured while attempting to update this progress response [epresponse_id " . $exisiting_response->getID() . "] record DB said: " . $db->ErrorMsg());
                                                                                                                            }
                                                                                                                        }
                                                                                                                    } else {
                                                                                                                        $PROCESSED_RESPONSE["one45_answer_id"]          = (is_null($progress_response_record["one45_answer_id"]) ? null : clean_input($progress_response_record["one45_answer_id"], array("trim", "int")));
                                                                                                                        $PROCESSED_RESPONSE["aprogress_id"]             = $existing_progress_record->getID();
                                                                                                                        $PROCESSED_RESPONSE["form_id"]                  = clean_input($progress_response_record["form_id"], array("trim", "int"));
                                                                                                                        $PROCESSED_RESPONSE["adistribution_id"]         = clean_input($progress_response_record["adistribution_id"], array("trim", "int"));
                                                                                                                        $PROCESSED_RESPONSE["assessor_type"]            = clean_input($progress_response_record["assessor_type"], array("trim", "striptags"));
                                                                                                                        $PROCESSED_RESPONSE["assessor_value"]           = clean_input($progress_response_record["assessor_value"], array("trim", "int"));
                                                                                                                        $PROCESSED_RESPONSE["afelement_id"]             = clean_input($progress_response_record["afelement_id"], array("trim", "int"));
                                                                                                                        $PROCESSED_RESPONSE["iresponse_id"]             = (is_null($progress_response_record["iresponse_id"]) ? null : clean_input($progress_response_record["iresponse_id"], array("trim", "int")));
                                                                                                                        $PROCESSED_RESPONSE["comments"]                 = null;
                                                                                                                        $PROCESSED_RESPONSE["created_date"]             = clean_input($progress_response_record["created_date"], array("trim", "int"));
                                                                                                                        $PROCESSED_RESPONSE["created_by"]               = clean_input($progress_response_record["created_by"], array("trim", "int"));
                                                                                                                        $PROCESSED_RESPONSE["updated_date"]             = (is_null($progress_response_record["updated_date"]) ? null : clean_input($progress_response_record["updated_date"], array("trim", "int")));
                                                                                                                        $PROCESSED_RESPONSE["updated_by"]               = (is_null($progress_response_record["updated_by"]) ? null : clean_input($progress_response_record["updated_by"], array("trim", "int")));
                                                                                                                        $PROCESSED_RESPONSE["deleted_date"]             = (is_null($progress_response_record["deleted_date"]) ? null : clean_input($progress_response_record["deleted_date"], array("trim", "int")));

                                                                                                                        $response = new Models_Assessments_Progress_Response($PROCESSED_RESPONSE);
                                                                                                                        if (!$response->fromArray($PROCESSED_RESPONSE)->insert()) {
                                                                                                                            add_error($translate->_("We were unable to save the form at this time. Please try again at a later time."));
                                                                                                                            application_log("mobile", "A problem occurred while saving a progress response records DB said: " . $db->ErrorMsg());
                                                                                                                        }
                                                                                                                    }
                                                                                                                }
                                                                                                            }
                                                                                                        }
                                                                                                    }
                                                                                                    break;
                                                                                                case "4" :
                                                                                                case "5" :
                                                                                                case "6" :
                                                                                                    $query = "DELETE FROM `cbl_assessment_progress_responses` WHERE `aprogress_id` = ? AND `afelement_id` = ?";
                                                                                                    $result = $db->Execute($query, array($existing_progress_record->getID(), $form_element->getID()));
                                                                                                    if ($progress_response_records) {
                                                                                                        foreach ($progress_response_records as $progress_response_record) {
                                                                                                            if ($progress_response_record["aprogress_id"] == $tmp_aprogress_id) {
                                                                                                                if ($form_element->getID() == $progress_response_record["afelement_id"]) {
                                                                                                                    $PROCESSED_RESPONSE["iresponse_id"] = (is_null($progress_response_record["iresponse_id"]) ? null : clean_input($progress_response_record["iresponse_id"], array("trim", "int")));
                                                                                                                    $existing_response = Models_Assessments_Progress_Response::fetchRowByAprogressIDAfelementIDIresponseID($existing_progress_record->getID(), $form_element->getID(), $PROCESSED_RESPONSE["iresponse_id"]);
                                                                                                                    if (!$existing_response) {
                                                                                                                        $PROCESSED_RESPONSE["one45_answer_id"]          = (is_null($progress_response_record["one45_answer_id"]) ? null : clean_input($progress_response_record["one45_answer_id"], array("trim", "int")));
                                                                                                                        $PROCESSED_RESPONSE["aprogress_id"]             = $existing_progress_record->getID();
                                                                                                                        $PROCESSED_RESPONSE["form_id"]                  = clean_input($progress_response_record["form_id"], array("trim", "int"));
                                                                                                                        $PROCESSED_RESPONSE["adistribution_id"]         = clean_input($progress_response_record["adistribution_id"], array("trim", "int"));
                                                                                                                        $PROCESSED_RESPONSE["assessor_type"]            = clean_input($progress_response_record["assessor_type"], array("trim", "striptags"));
                                                                                                                        $PROCESSED_RESPONSE["assessor_value"]           = clean_input($progress_response_record["assessor_value"], array("trim", "int"));
                                                                                                                        $PROCESSED_RESPONSE["afelement_id"]             = clean_input($progress_response_record["afelement_id"], array("trim", "int"));
                                                                                                                        $PROCESSED_RESPONSE["comments"]                 = null;
                                                                                                                        $PROCESSED_RESPONSE["created_date"]             = clean_input($progress_response_record["created_date"], array("trim", "int"));
                                                                                                                        $PROCESSED_RESPONSE["created_by"]               = clean_input($progress_response_record["created_by"], array("trim", "int"));
                                                                                                                        $PROCESSED_RESPONSE["updated_date"]             = (is_null($progress_response_record["updated_date"]) ? null : clean_input($progress_response_record["updated_date"], array("trim", "int")));
                                                                                                                        $PROCESSED_RESPONSE["updated_by"]               = (is_null($progress_response_record["updated_by"]) ? null : clean_input($progress_response_record["updated_by"], array("trim", "int")));
                                                                                                                        $PROCESSED_RESPONSE["deleted_date"]             = (is_null($progress_response_record["deleted_date"]) ? null : clean_input($progress_response_record["deleted_date"], array("trim", "int")));

                                                                                                                        $response = new Models_Assessments_Progress_Response($PROCESSED_RESPONSE);
                                                                                                                        if (!$response->fromArray($PROCESSED_RESPONSE)->insert()) {
                                                                                                                            add_error($translate->_("We were unable to save the form at this time. Please try again at a later time."));
                                                                                                                            application_log("mobile", "A problem occurred while " . $method . "ing a progress response records DB said: " . $db->ErrorMsg());
                                                                                                                        }
                                                                                                                    }
                                                                                                                }
                                                                                                            }
                                                                                                        }
                                                                                                    }
                                                                                                    break;
                                                                                                case "7" :
                                                                                                case "10" :
                                                                                                    if ($progress_response_records) {
                                                                                                        foreach ($progress_response_records as $progress_response_record) {
                                                                                                            if ($progress_response_record["aprogress_id"] == $tmp_aprogress_id) {
                                                                                                                if ($form_element->getID() == $progress_response_record["afelement_id"]) {
                                                                                                                    $existing_response = Models_Assessments_Progress_Response::fetchRowByAprogressIDAfelementID($existing_progress_record->getID(), $form_element->getID());
                                                                                                                    if ($existing_response) {
                                                                                                                        $PROCESSED_RESPONSE["comments"] = clean_input($progress_response_record["comments"], array("trim", "striptags"));
                                                                                                                        $PROCESSED_RESPONSE["updated_date"] = clean_input($progress_response_record["updated_date"], array("trim", "int"));
                                                                                                                        $PROCESSED_RESPONSE["updated_by"] = clean_input($progress_response_record["updated_by"], array("trim", "int"));

                                                                                                                        if (!$existing_response->fromArray($PROCESSED_RESPONSE)->update()) {
                                                                                                                            application_log("mobile", "An error occured while attempting to update this progress response [epresponse_id " . $exisiting_response->getID() . "] record DB said: " . $db->ErrorMsg());
                                                                                                                        }
                                                                                                                    } else {
                                                                                                                        $PROCESSED_RESPONSE["one45_answer_id"]          = (is_null($progress_response_record["one45_answer_id"]) ? null : clean_input($progress_response_record["one45_answer_id"], array("trim", "int")));
                                                                                                                        $PROCESSED_RESPONSE["aprogress_id"]             = $existing_progress_record->getID();
                                                                                                                        $PROCESSED_RESPONSE["form_id"]                  = clean_input($progress_response_record["form_id"], array("trim", "int"));
                                                                                                                        $PROCESSED_RESPONSE["adistribution_id"]         = clean_input($progress_response_record["adistribution_id"], array("trim", "int"));
                                                                                                                        $PROCESSED_RESPONSE["assessor_type"]            = clean_input($progress_response_record["assessor_type"], array("trim", "striptags"));
                                                                                                                        $PROCESSED_RESPONSE["assessor_value"]           = clean_input($progress_response_record["assessor_value"], array("trim", "int"));
                                                                                                                        $PROCESSED_RESPONSE["afelement_id"]             = clean_input($progress_response_record["afelement_id"], array("trim", "int"));
                                                                                                                        $PROCESSED_RESPONSE["iresponse_id"]             = null;
                                                                                                                        $PROCESSED_RESPONSE["comments"]                 = clean_input($progress_response_record["comments"], array("trim", "striptags"));
                                                                                                                        $PROCESSED_RESPONSE["created_date"]             = clean_input($progress_response_record["created_date"], array("trim", "int"));
                                                                                                                        $PROCESSED_RESPONSE["created_by"]               = clean_input($progress_response_record["created_by"], array("trim", "int"));
                                                                                                                        $PROCESSED_RESPONSE["updated_date"]             = (is_null($progress_response_record["updated_date"]) ? null : clean_input($progress_response_record["updated_date"], array("trim", "int")));
                                                                                                                        $PROCESSED_RESPONSE["updated_by"]               = (is_null($progress_response_record["updated_by"]) ? null : clean_input($progress_response_record["updated_by"], array("trim", "int")));
                                                                                                                        $PROCESSED_RESPONSE["deleted_date"]             = (is_null($progress_response_record["deleted_date"]) ? null : clean_input($progress_response_record["deleted_date"], array("trim", "int")));

                                                                                                                        $response = new Models_Assessments_Progress_Response($PROCESSED_RESPONSE);
                                                                                                                        if (!$response->fromArray($PROCESSED_RESPONSE)->insert()) {
                                                                                                                            add_error($translate->_("We were unable to save the form at this time. Please try again at a later time."));
                                                                                                                            application_log("mobile", "A problem occurred while saving a progress response records DB said: " . $db->ErrorMsg());
                                                                                                                        }
                                                                                                                    }
                                                                                                                }
                                                                                                            }
                                                                                                        }
                                                                                                    }
                                                                                                    break;
                                                                                                case "8" :
                                                                                                    if ($progress_response_records) {
                                                                                                        foreach ($progress_response_records as $progress_response_record) {
                                                                                                            if ($progress_response_record["aprogress_id"] == $tmp_aprogress_id) {
                                                                                                                if ($form_element->getID() == $progress_response_record["afelement_id"]) {
                                                                                                                    $existing_response = Models_Assessments_Progress_Response::fetchRowByAprogressIDAfelementID($existing_progress_record->getID(), $form_element->getID());
                                                                                                                    if ($existing_response) {
                                                                                                                        $PROCESSED_RESPONSE["comments"] = clean_input($progress_response_record["comments"], array("trim", "int"));
                                                                                                                        $PROCESSED_RESPONSE["updated_date"] = clean_input($progress_response_record["updated_date"], array("trim", "int"));
                                                                                                                        $PROCESSED_RESPONSE["updated_by"] = clean_input($progress_response_record["updated_by"], array("trim", "int"));

                                                                                                                        if (!$existing_response->fromArray($PROCESSED_RESPONSE)->update()) {
                                                                                                                            application_log("mobile", "An error occured while attempting to update this progress response [epresponse_id " . $exisiting_response->getID() . "] record DB said: " . $db->ErrorMsg());
                                                                                                                        }
                                                                                                                    } else {
                                                                                                                        $PROCESSED_RESPONSE["one45_answer_id"]          = (is_null($progress_response_record["one45_answer_id"]) ? null : clean_input($progress_response_record["one45_answer_id"], array("trim", "int")));
                                                                                                                        $PROCESSED_RESPONSE["aprogress_id"]             = $existing_progress_record->getID();
                                                                                                                        $PROCESSED_RESPONSE["form_id"]                  = clean_input($progress_response_record["form_id"], array("trim", "int"));
                                                                                                                        $PROCESSED_RESPONSE["adistribution_id"]         = clean_input($progress_response_record["adistribution_id"], array("trim", "int"));
                                                                                                                        $PROCESSED_RESPONSE["assessor_type"]            = clean_input($progress_response_record["assessor_type"], array("trim", "striptags"));
                                                                                                                        $PROCESSED_RESPONSE["assessor_value"]           = clean_input($progress_response_record["assessor_value"], array("trim", "int"));
                                                                                                                        $PROCESSED_RESPONSE["afelement_id"]             = clean_input($progress_response_record["afelement_id"], array("trim", "int"));
                                                                                                                        $PROCESSED_RESPONSE["iresponse_id"]             = null;
                                                                                                                        $PROCESSED_RESPONSE["comments"]                 = clean_input($progress_response_record["comments"], array("trim", "int"));
                                                                                                                        $PROCESSED_RESPONSE["created_date"]             = clean_input($progress_response_record["created_date"], array("trim", "int"));
                                                                                                                        $PROCESSED_RESPONSE["created_by"]               = clean_input($progress_response_record["created_by"], array("trim", "int"));
                                                                                                                        $PROCESSED_RESPONSE["updated_date"]             = (is_null($progress_response_record["updated_date"]) ? null : clean_input($progress_response_record["updated_date"], array("trim", "int")));
                                                                                                                        $PROCESSED_RESPONSE["updated_by"]               = (is_null($progress_response_record["updated_by"]) ? null : clean_input($progress_response_record["updated_by"], array("trim", "int")));
                                                                                                                        $PROCESSED_RESPONSE["deleted_date"]             = (is_null($progress_response_record["deleted_date"]) ? null : clean_input($progress_response_record["deleted_date"], array("trim", "int")));

                                                                                                                        $response = new Models_Assessments_Progress_Response($PROCESSED_RESPONSE);
                                                                                                                        if (!$response->fromArray($PROCESSED_RESPONSE)->insert()) {
                                                                                                                            add_error($translate->_("We were unable to save the form at this time. Please try again at a later time."));
                                                                                                                            application_log("mobile", "A problem occurred while saving a progress response records DB said: " . $db->ErrorMsg());
                                                                                                                        }
                                                                                                                    }
                                                                                                                }
                                                                                                            }
                                                                                                        }
                                                                                                    }
                                                                                                    break;
                                                                                            }
                                                                                        }
                                                                                        break;
                                                                                    case "objective" :
                                                                                        if ($progress_response_records) {
                                                                                            foreach ($progress_response_records as $progress_response_record) {
                                                                                                if ($progress_response_record["aprogress_id"] == $tmp_aprogress_id) {
                                                                                                    if ($form_element->getID() == $progress_response_record["afelement_id"]) {
                                                                                                        $existing_response = Models_Assessments_Progress_Response::fetchRowByAprogressIDAfelementID($existing_progress_record->getID(), $form_element->getID());
                                                                                                        if ($existing_response) {
                                                                                                            if ($existing_response->getIresponseID() != $progress_response_record["iresponse_id"]) {
                                                                                                                $PROCESSED_RESPONSE["iresponse_id"] = clean_input($progress_response_record["iresponse_id"], array("trim", "int"));
                                                                                                                $PROCESSED_RESPONSE["updated_date"] = clean_input($progress_response_record["updated_date"], array("trim", "int"));
                                                                                                                $PROCESSED_RESPONSE["updated_by"] = clean_input($progress_response_record["updated_by"], array("trim", "int"));

                                                                                                                if (!$existing_response->fromArray($PROCESSED_RESPONSE)->update()) {
                                                                                                                    application_log("mobile", "An error occured while attempting to update this progress response [epresponse_id " . $exisiting_response->getID() . "] record DB said: " . $db->ErrorMsg());
                                                                                                                }
                                                                                                            }
                                                                                                        } else {
                                                                                                            $PROCESSED_RESPONSE["one45_answer_id"]          = (is_null($progress_response_record["one45_answer_id"]) ? null : clean_input($progress_response_record["one45_answer_id"], array("trim", "int")));
                                                                                                            $PROCESSED_RESPONSE["aprogress_id"]             = $existing_progress_record->getID();
                                                                                                            $PROCESSED_RESPONSE["form_id"]                  = clean_input($progress_response_record["form_id"], array("trim", "int"));
                                                                                                            $PROCESSED_RESPONSE["adistribution_id"]         = clean_input($progress_response_record["adistribution_id"], array("trim", "int"));
                                                                                                            $PROCESSED_RESPONSE["assessor_type"]            = clean_input($progress_response_record["assessor_type"], array("trim", "striptags"));
                                                                                                            $PROCESSED_RESPONSE["assessor_value"]           = clean_input($progress_response_record["assessor_value"], array("trim", "int"));
                                                                                                            $PROCESSED_RESPONSE["afelement_id"]             = clean_input($progress_response_record["afelement_id"], array("trim", "int"));
                                                                                                            $PROCESSED_RESPONSE["iresponse_id"]             = (is_null($progress_response_record["iresponse_id"]) ? null : clean_input($progress_response_record["iresponse_id"], array("trim", "int")));
                                                                                                            $PROCESSED_RESPONSE["comments"]                 = null;
                                                                                                            $PROCESSED_RESPONSE["created_date"]             = clean_input($progress_response_record["created_date"], array("trim", "int"));
                                                                                                            $PROCESSED_RESPONSE["created_by"]               = clean_input($progress_response_record["created_by"], array("trim", "int"));
                                                                                                            $PROCESSED_RESPONSE["updated_date"]             = (is_null($progress_response_record["updated_date"]) ? null : clean_input($progress_response_record["updated_date"], array("trim", "int")));
                                                                                                            $PROCESSED_RESPONSE["updated_by"]               = (is_null($progress_response_record["updated_by"]) ? null : clean_input($progress_response_record["updated_by"], array("trim", "int")));
                                                                                                            $PROCESSED_RESPONSE["deleted_date"]             = (is_null($progress_response_record["deleted_date"]) ? null : clean_input($progress_response_record["deleted_date"], array("trim", "int")));

                                                                                                            $response = new Models_Assessments_Progress_Response($PROCESSED_RESPONSE);
                                                                                                            if (!$response->fromArray($PROCESSED_RESPONSE)->insert()) {
                                                                                                                add_error($translate->_("We were unable to save the form at this time. Please try again at a later time."));
                                                                                                                application_log("mobile", "A problem occurred while saving a progress response records DB said: " . $db->ErrorMsg());
                                                                                                            }
                                                                                                        }
                                                                                                    }
                                                                                                }
                                                                                            }
                                                                                        }
                                                                                        break;
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }

                                                        if ($progress_record["updated_date"] < $existing_progress_record->getUpdatedDate()) {
                                                            // mobile app needs to be updated
                                                            $assessment_progress_record = $existing_progress_record->toArray();
                                                            $assessment_progress_record["aprogress_id"] = $tmp_aprogress_id;
                                                            $assessment_progress[] = $assessment_progress_record;
                                                            $assessment_responses = Models_Assessments_Progress_Response::fetchAllByAprogressID($existing_progress_record->getID());
                                                            if ($assessment_responses) {
                                                                foreach ($assessment_responses as $assessment_response) {
                                                                    $assessment_progress_response_record = $assessment_response->toArray();
                                                                    $assessment_progress_response_record["aprogress_id"] = $tmp_aprogress_id;
                                                                    $assessment_progress_responses[] = $assessment_progress_response_record;
                                                                }
                                                            }
                                                        }
                                                    } else {
                                                        // progress record doesn't exist and needs to be created
                                                        $PROCESSED["one45_formsAttached_id"]            = (is_null($progress_record["one45_formsAttached_id"]) ? null : clean_input($progress_record["one45_formsAttached_id"], array("trim", "int")));
                                                        $PROCESSED["one45_p_id"]                        = (is_null($progress_record["one45_p_id"]) ? null : clean_input($progress_record["one45_p_id"], array("trim", "int")));
                                                        $PROCESSED["one45_moment_id"]                   = (is_null($progress_record["one45_moment_id"]) ? null : clean_input($progress_record["one45_moment_id"], array("trim", "int")));
                                                        $PROCESSED["adistribution_id"]                  = clean_input($progress_record["adistribution_id"], array("trim", "int"));
                                                        $PROCESSED["assessor_type"]                     = clean_input($progress_record["assessor_type"], array("trim", "striptags"));
                                                        $PROCESSED["assessor_value"]                    = clean_input($progress_record["assessor_value"], array("trim", "int"));
                                                        $PROCESSED["dassessment_id"]                    = (is_null($progress_record["dassessment_id"]) ? null : clean_input($progress_record["dassessment_id"], array("trim", "int")));
                                                        $PROCESSED["adtarget_id"]                       = (is_null($progress_record["adtarget_id"]) ? null : clean_input($progress_record["adtarget_id"], array("trim", "int")));
                                                        $PROCESSED["target_record_id"]                  = (is_null($progress_record["target_record_id"]) ? null : clean_input($progress_record["target_record_id"], array("trim", "int")));
                                                        $PROCESSED["target_learning_context_id"]        = (is_null($progress_record["target_learning_context_id"]) ? null : clean_input($progress_record["target_learning_context_id"], array("trim", "int")));
                                                        $PROCESSED["progress_value"]                    = (is_null($progress_record["progress_value"]) ? null : clean_input($progress_record["progress_value"], array("trim", "striptags")));
                                                        $PROCESSED["uuid"]                              = (is_null($progress_record["uuid"]) ? null : clean_input($progress_record["uuid"], array("trim", "striptags")));
                                                        $PROCESSED["created_date"]                      = clean_input($progress_record["created_date"], array("trim", "int"));
                                                        $PROCESSED["created_by"]                        = clean_input($progress_record["created_by"], array("trim", "int"));
                                                        $PROCESSED["updated_date"]                      = (is_null ($progress_record["updated_date"]) ? null : clean_input($progress_record["updated_date"], array("trim", "int")));
                                                        $PROCESSED["deleted_date"]                      = (is_null($progress_record["deleted_date"]) ? null : clean_input($progress_record["deleted_date"], array("trim", "int")));
                                                        $PROCESSED["updated_by"]                        = (is_null($progress_record["updated_by"]) ? null : clean_input($progress_record["updated_by"], array("trim", "int")));

                                                        $progress = new Models_Assessments_Progress($PROCESSED);
                                                        if (!$progress->insert()) {
                                                            application_log("mobile", "An error occured while attempting to save this progress record.");
                                                        } else {
                                                            if ($progress_response_records) {
                                                                foreach ($progress_response_records as $progress_response_record) {
                                                                    if ($progress_response_record["aprogress_id"] == $tmp_aprogress_id) {
                                                                        $PROCESSED_RESPONSE["one45_answer_id"]          = (is_null($progress_response_record["one45_answer_id"]) ? null : clean_input($progress_response_record["one45_answer_id"], array("trim", "int")));
                                                                        $PROCESSED_RESPONSE["aprogress_id"]             = $progress->getID();
                                                                        $PROCESSED_RESPONSE["form_id"]                  = clean_input($progress_response_record["form_id"], array("trim", "int"));
                                                                        $PROCESSED_RESPONSE["adistribution_id"]         = clean_input($progress_response_record["adistribution_id"], array("trim", "int"));
                                                                        $PROCESSED_RESPONSE["assessor_type"]            = clean_input($progress_response_record["assessor_type"], array("trim", "striptags"));
                                                                        $PROCESSED_RESPONSE["assessor_value"]           = clean_input($progress_response_record["assessor_value"], array("trim", "int"));
                                                                        $PROCESSED_RESPONSE["afelement_id"]             = clean_input($progress_response_record["afelement_id"], array("trim", "int"));
                                                                        $PROCESSED_RESPONSE["iresponse_id"]             = (is_null($progress_response_record["iresponse_id"]) ? null : clean_input($progress_response_record["iresponse_id"], array("trim", "int")));
                                                                        $PROCESSED_RESPONSE["comments"]                 = clean_input($progress_response_record["comments"], array("trim", "striptags"));
                                                                        $PROCESSED_RESPONSE["created_date"]             = clean_input($progress_response_record["created_date"], array("trim", "int"));
                                                                        $PROCESSED_RESPONSE["created_by"]               = clean_input($progress_response_record["created_by"], array("trim", "int"));
                                                                        $PROCESSED_RESPONSE["updated_date"]             = (is_null($progress_response_record["updated_date"]) ? null : clean_input($progress_response_record["updated_date"], array("trim", "int")));
                                                                        $PROCESSED_RESPONSE["updated_by"]               = (is_null($progress_response_record["updated_by"]) ? null : clean_input($progress_response_record["updated_by"], array("trim", "int")));
                                                                        $PROCESSED_RESPONSE["deleted_date"]             = (is_null($progress_response_record["deleted_date"]) ? null : clean_input($progress_response_record["deleted_date"], array("trim", "int")));

                                                                        $progress_response = new Models_Assessments_Progress_Response($PROCESSED_RESPONSE);
                                                                        if (!$progress_response->insert()) {
                                                                            application_log("mobile", "An error occured while attempting to save this progress response record DB said: " . $db->ErrorMsg());
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }

                                        /*$new_progress_records = Models_Assessments_Progress::fetchAllBySyncDateAssessorTypeAssessorValue("internal", $user_mobile_data->getProxyID(), $last_sync);
                                        if ($new_progress_records) {
                                            foreach ($new_progress_records as $new_progress_record) {
                                                $assessment_progress[] = $new_progress_record->toArray();
                                                $new_progress_responses = Models_Assessments_Progress_Response::fetchAllByAprogressID($new_progress_record->getID());
                                                if ($new_progress_responses) {
                                                    foreach ($new_progress_responses as $new_progress_response) {
                                                        $assessment_progress_responses[] = $new_progress_response->toArray();
                                                    }
                                                }
                                            }
                                        }*/

                                        $objectives = Models_Objective::fetchByOrganisation(8, $active = 1);
                                        if ($objectives) {
                                            $objective_content = array();
                                            foreach ($objectives as $objective) {
                                                $objective_content[] = array("objective_id" => $objective->getID(), "objective_name" => $objective->getName(), "objective_parent" => $objective->getParent(), "associated_objective" => $objective->getAssociatedObjective(), "objective_order" => $objective->getOrder());
                                            }
                                        }

                                        $item_ids = array();
                                        if ($last_sync == 0) {
                                            $objective_items = Models_Assessments_Item_Objective::fetchAllRecords();
                                            if ($objective_items) {
                                                foreach ($objective_items as $objective_item) {
                                                    $item_ids[] = $objective_item->getObjectiveID();
                                                }

                                                foreach ($objective_items as $objective_item) {
                                                    $objective_item_content[] = $objective_item->toArray();
                                                    $item = Models_Assessments_Item::fetchRowByID($objective_item->getItemID());
                                                    if ($item) {
                                                        if (!in_array($item->getID(), $item_ids)) {
                                                            $items[] = $item->toArray();
                                                            $item_ids[] = $item->getID();
                                                            $responses = $item->getItemResponses();

                                                            if ($responses) {
                                                                foreach ($responses as $response) {
                                                                    if (!array_key_exists($response->getID(), $item_responses)) {
                                                                        $item_responses[$response->getID()] = $response->toArray();
                                                                    }
                                                                }
                                                            }
                                                            $item_ids[] = $item->getID();
                                                        }
                                                    }
                                                }
                                            }

                                            $descriptors = Models_Assessments_Response_Descriptor::fetchAllByOrganisationID(8);
                                            if ($descriptors) {
                                                foreach ($descriptors as $descriptor) {
                                                    $response_descriptors[] = $descriptor->toArray();
                                                }
                                            }
                                        }

                                        $form_ids = array();
                                        $distribution_ids = array();
                                        if ($last_sync !== 0) {
                                            $previous_assessments = Models_Assessments_Assessor::fetchAllPreviousAssessments("internal", $user_mobile_data->getProxyID(), true, $last_sync);
                                            if ($previous_assessments) {
                                                foreach ($previous_assessments as $previous_assessment) {
                                                    $distribution_ids[] = $previous_assessment->getADistributionID();
                                                    $previous_distribution = Models_Assessments_Distribution::fetchRowByID($previous_assessment->getADistributionID());
                                                    if ($previous_distribution) {
                                                        $form_ids[] = $previous_distribution->getFormID();
                                                    }
                                                }
                                            }
                                        }

                                        $assessments = Models_Assessments_Assessor::fetchAllByAssessorTypeAssessorValueSyncDate("internal", $user_mobile_data->getProxyID(), true, $last_sync);
                                        if ($assessments) {
                                            foreach ($assessments as $assessment) {
                                                $tmp_targets = array();
                                                $tmp_elements = array();
                                                $assessment_distribution = Models_Assessments_Distribution::fetchRowByID($assessment->getADistributionID());
                                                if ($assessment_distribution) {
                                                    $assessment_content[] = $assessment->toArray();
                                                    if (!in_array($assessment_distribution->getID(), $distribution_ids)) {
                                                        $assessment_distributions[] = array("adistribution_id" => $assessment_distribution->getID(), "one45_scenariosAttached_id" => $assessment_distribution->getOne45ScenariosAttachedID(), "form_id" => $assessment_distribution->getFormID(), "organisation_id" => $assessment_distribution->getOrganisationID(), "title" => $assessment_distribution->getTitle(), "description" => $assessment_distribution->getDescription(), "course_id" => $assessment_distribution->getCourseID(), "min_submittable" => $assessment_distribution->getMinSubmittable(), "max_submittable" => $assessment_distribution->getMaxSubmittable(), "repeat_targets" => $assessment_distribution->getRepeatTargets(), "submittable_by_target" => $assessment_distribution->getSubmittableByTarget(), "start_date" => date(DEFAULT_DATE_FORMAT, $assessment_distribution->getStartDate()), "end_date" => date(DEFAULT_DATE_FORMAT, $assessment_distribution->getEndDate()), "mandatory" => $assessment_distribution->getMandatory(), "distributor_timeout" => $assessment_distribution->getDistributorTimeout(), "created_date" => $assessment_distribution->getCreatedDate(), "created_by" => $assessment_distribution->getCreatedBy(), "updated_date" => $assessment_distribution->getUpdatedDate(), "updated_by" => $assessment_distribution->getUpdatedBy(), "deleted_date" => $assessment_distribution->getDeletedDate());  //$assessment_distribution->toArray();
                                                        $distribution_form = Models_Assessments_Form::fetchRowByID($assessment_distribution->getFormID());
                                                        if ($distribution_form) {
                                                            if (!in_array($distribution_form->getID(), $form_ids)) {
                                                                $assessment_forms[] = $distribution_form->toArray();
                                                                $assessment_form_elements = Models_Assessments_Form_Element::fetchAllRecords($distribution_form->getID());
                                                                if ($assessment_form_elements) {
                                                                    foreach ($assessment_form_elements as $element) {
                                                                        $form_elements[] = $element->toArray();
                                                                        if ($element->getElementType() === "item") {
                                                                            $item = Models_Assessments_Item::fetchRowByID($element->getElementID());
                                                                            if ($item) {
                                                                                if (!in_array($item->getID(), $item_ids)) {
                                                                                    $items[] = $item->toArray();
                                                                                    $responses = $item->getItemResponses();
                                                                                    if ($responses) {
                                                                                        foreach ($responses as $response) {
                                                                                            if (!array_key_exists($response->getID(), $item_responses)) {
                                                                                                $item_responses[$response->getID()] = $response->toArray();
                                                                                            }
                                                                                        }
                                                                                    }
                                                                                    $item_ids[] = $item->getID();
                                                                                }
                                                                            }
                                                                        }

                                                                        if ($element->getElementType() === "objective") {
                                                                            $fieldnote_item = Models_Assessments_Item::fetchFieldNoteItem($element->getElementID());
                                                                            if ($fieldnote_item) {
                                                                                foreach ($objectives as $objective) {
                                                                                    $item_objectives[] = $objective->toArray();
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                            $form_ids[] = $distribution_form->getID();
                                                        }
                                                        $distribution_ids[] = $assessment_distribution->getID();
                                                    }

                                                    $targets = Models_Assessments_Distribution_Target::getAssessmentTargets($assessment_distribution->getID(), $assessment->getID(), null, $user_mobile_data->getProxyID());
                                                    if ($targets) {
                                                        foreach ($targets as $target) {
                                                            if (in_array("inprogress", $target["progress"])) {
                                                                $target["distribution_id"] = $assessment_distribution->getID();
                                                                $target["dassessment_id"] = $assessment->getID();
                                                                $target["target_progress"] = "inprogress";
                                                                $assessment_targets[$assessment_distribution->getID()][$target["target_record_id"]] = $target;
                                                            } else {
                                                                $target["distribution_id"] = $assessment_distribution->getID();
                                                                $target["dassessment_id"] = $assessment->getID();
                                                                $target["target_progress"] = "pending";
                                                                $assessment_targets[$assessment_distribution->getID()][$target["target_record_id"]] = $target;
                                                            }

                                                            if (in_array("complete", $target["progress"])) {
                                                                $target["distribution_id"] = $assessment_distribution->getID();
                                                                $target["dassessment_id"] = $assessment->getID();
                                                                $target["target_progress"] = "complete";
                                                                $assessment_targets[$assessment_distribution->getID()][$target["target_record_id"]] = $target;
                                                            }
                                                        }
                                                    }

                                                    $progress_records = Models_Assessments_Progress::fetchAllByAdistributionIDAssessorTypeAssessorValue($assessment->getADistributionID(), "internal", $assessment->getAssessorValue());
                                                    if ($progress_records) {
                                                        foreach ($progress_records as $progress_record) {
                                                            $progress_responses = Models_Assessments_Progress_Response::fetchAllByAprogressID($progress_record->getID());
                                                            $assessment_progress[] = $progress_record->toArray();
                                                            if ($progress_responses) {
                                                                foreach ($progress_responses as $progress_response) {
                                                                    $assessment_progress_responses[] = $progress_response->toArray();
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }

                                        if (!$last_sync) {
                                            $data["objectives"]                     = $objective_content;
                                        }

                                        $data["forms"]                          = $assessment_forms;
                                        $data["form_elements"]                  = $form_elements;
                                        $data["items"]                          = $items;
                                        $data["item_responses"]                 = $item_responses;
                                        $data["item_objectives"]                = $objective_item_content;
                                        $data["distributions"]                  = $assessment_distributions;
                                        $data["assessments"]                    = $assessment_content;
                                        $data["assessment_progress"]            = $assessment_progress;
                                        $data["assessment_progress_responses"]  = $assessment_progress_responses;
                                        $data["targets"]                        = $assessment_targets;
                                        $data["response_descriptors"]           = $response_descriptors;
                                        $data["last_sync"]                      = time();
                                        unset($_SESSION["isAuthorized"]);
                                        echo json_encode(array("status" => "success", "data" => $data));
                                        break;
                                }
                            }
                        break;
                        case "events" :
                        break;
                        case "notices" :
                        break;
                        case "read-notice" :
                            if (isset($_POST["notice_id"]) && $tmp_input = clean_input($_POST["notice_id"], "alphanumeric")) {
                            	$notice_id = $tmp_input;
                            } else {
                                add_error("Invalid notice identifier supplied");
                            }

                            if (!$ERROR) {
                                $data = array("notice_id" => $notice_id);
                                add_statistic("notices", "read", "notice_id", $notice_id, $user_mobile_data->getProxyID());

                                echo json_encode(array("status" => "success", "data" => $data));
                            }
                        break;
                    }
                } else {
                    application_log("mobile", "No 'user mobile data' record was found using hash: " . $hash . " method: " . $method);
                    echo json_encode(array("status" => "authenticate"));
                }
            } else {
                application_log("mobile", "No mobile hash provided. " . $method);
                add_error("No hash provided");
            }

            if (!$ERROR) {

            } else {
                application_log("mobile", "No mobile hash provided. " . $method);
                echo json_encode(array("status" => "error", "data" => $ERRORSTR));
            }
        break;
    }
} else {
    application_log("mobile", "No method provided provided.");
    echo json_encode(array("status" => "error", "data" => array("No method provided.")));
}
?>