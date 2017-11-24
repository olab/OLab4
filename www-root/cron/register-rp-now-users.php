<?php

/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Register users in RP-Now system.
 *
 * @author Organisation: Queen's University
 * @author Unit: Faculty of Medicine
 * @author Developer: Thaisa Almeida <trda@queensu.ca>
 * @copyright Copyright 2017 Queen's Univerity. All Rights Reserved.
 *
 */
@set_time_limit(0);
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

function flatten($array, $prefix = '') {
    $result = array();
    foreach($array as $key=>$value) {
        if(is_array($value)) {
            $result = $result + flatten($value, $prefix . $key . '.');
        }
        else {
            $result[$prefix . $key] = $value;
        }
    }
    ksort($result);
    return $result;
}

function key_value_concat($array) {
    $out = array();
    foreach($array as $key => $value)
        $out[] = "$key:".(is_bool($value) ? ($value ? 'true' : 'false') : $value);
    return $out;
}

function CalculateSignature($string){
    return base64_encode(hash_hmac('sha256', $string, RP_NOW_SSI_SECRET_KEY, true));
}

function GetHeaderParamsString($method){
    return $method.PHP_EOL."\n".
        'application/json'.PHP_EOL.
        gmdate('D, d M Y H:i:s T', time()).PHP_EOL;

}

function GetHeaderParams($method){
    return array(
        'content-type: application/json',
        'date:'.gmdate('D, d M Y H:i:s T', time()),
        'Authorization: SSI ' .RP_NOW_ACCESS_KEY_ID. ':' . CalculateSignature(GetParams($method))
    );
}

function GetParams($method){
    global $data;
    return GetHeaderParamsString($method).join(PHP_EOL,key_value_concat(flatten($data))).PHP_EOL;
}

function password_encrypt($data, $key) {
    $blockSize = mcrypt_get_block_size('tripledes', 'ecb');
    $len = strlen($data);
    $pad = $blockSize - ($len % $blockSize);
    $data .= str_repeat(chr($pad), $pad);

    $encData = mcrypt_encrypt('tripledes', $key, $data, 'ecb');
    return base64_encode($encData);
}

if ((@is_dir(CACHE_DIRECTORY)) && (@is_writable(CACHE_DIRECTORY))) {
    /**
     * Lock present: application busy: quit
     */
    if (!file_exists(CACHE_DIRECTORY."/rp_now.lck")) {
        if (@file_put_contents(CACHE_DIRECTORY."/rp_now.lck", "L_O_C_K")) {

            $rp_now_users = Models_Secure_RpNowUsers::getAllUsersNeedUpdate();

            if (is_array($rp_now_users) && !empty($rp_now_users)) {

                application_log("notice", "Found " . count($rp_now_users) . " users for registering in RP-Now system.");

                foreach ($rp_now_users as $rp_now_user) {

                    $rp_now = Models_Secure_RpNow::fetchRowByID($rp_now_user->getRpnowConfigId());
                    if ($rp_now) {
                        $post = $rp_now->getPost();
                        if ($post) {
                            $exam = $post->getExam();
                            $course = $post->getEvent()->getCourse();
                        } else {
                            add_error("Exam post not found");
                            application_log("error", "Exam post not found");
                        }
                    } else {
                        add_error("RpNow model not found");
                        application_log("error", "RpNow model not found");
                    }

                    if (!has_error()) {
                        $exception = Models_Exam_Post_Exception::fetchRowByPostIdProxyId($post->getID(), $rp_now_user->getProxyId());
                        $PROCESSED_EXCEPTION = array();
                        if ($exception) {
                            if (!is_null($exception->getStartDate())) {
                                $PROCESSED_EXCEPTION["start_date_exception"] = date("m/d/Y h:i:s A", $exception->getStartDate());
                            }
                            if (!is_null($exception->getEndDate())) {
                                $PROCESSED_EXCEPTION["end_date_exception"] = date("m/d/Y h:i:s A", $exception->getEndDate());
                            } else {
                                $PROCESSED_EXCEPTION["end_date_exception"] = 0;
                            }
                            if ($exception->getUseExceptionTimeFactor() && !is_null($exception->getExceptionTimeFactor()) && $exception->getExceptionTimeFactor() > 0) {
                                $PROCESSED_EXCEPTION["duration"] = $post->getTimeLimit() * (1 + ((int)$exception->getExceptionTimeFactor() / 100));
                            }
                        }

                        $data = array(
                            "organization" => RP_NOW_ORGANIZATION,
                            "ssiProduct" => "rp-now",
                            "examSponsor" => Models_User::fetchRowByID($rp_now->getExamSponsor())->getFullname(false),
                            "examName" => clean_input(preg_replace("/[^a-zA-Z0-9-_\.]/", " ", $exam->getTitle()), array("trim")),
                            "examCode" => $rp_now_user->getExamCode(),
                            "examPassword" => password_encrypt($post->getResumePassword(), RP_NOW_CRYPTO_KEY),
                            "examUrl" => $rp_now->getExamUrl(),
                            "duration" => (isset($PROCESSED_EXCEPTION["duration"]) ? $PROCESSED_EXCEPTION["duration"] : clean_input($post->getTimeLimit(), array("trim", "int"))),
                            "reviewerNotes" => $rp_now->getRpnowReviewerNotes(),
                            "simulatedExam" => false,
                            "reviewedExam" => clean_input($rp_now->getRpnowReviewedExam(), array("trim", "bool")),
                            "orgExtra" => array(
                                "examId" => $exam->getID(),
                                "courseID" => $course->getID(),
                                "courseName" => clean_input(preg_replace("/[^a-zA-Z0-9-_\.]/", " ", $course->getCourseName()), array("trim")),
                                "examStartDate" => (isset($PROCESSED_EXCEPTION["start_date_exception"]) ? $PROCESSED_EXCEPTION["start_date_exception"] : date("m/d/Y h:i:s A", $post->getStartDate())),
                                "examEndDate" => (isset($PROCESSED_EXCEPTION["end_date_exception"]) ? $PROCESSED_EXCEPTION["end_date_exception"] : ($post->getEndDate() != 0 ? date("m/d/Y h:i:s A", $post->getEndDate()) : 0)),
                                "studentName" => Models_User::fetchRowByID($rp_now_user->getProxyId())->getFullname(false),
                                "studentEmail" => Models_User::fetchRowByID($rp_now_user->getProxyId())->getEmail(),
                            )
                        );


                        $data_json = json_encode($data);

                        $PROCESSED["updated_date"] = time();
                        $PROCESSED["updated_by"] = 1;

                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, RP_NOW_API . "exams/registration/");
                        curl_setopt($ch, CURLOPT_HTTPHEADER, GetHeaderParams("POST"));
                        curl_setopt($ch, CURLOPT_POST, 1);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                        $response = curl_exec($ch);

                        if (!curl_errno($ch)) {
                            $rpnow_response = json_decode($response, true);

                            switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
                                case 201:
                                    if ($rp_now_user->getSsiRecordLocator() == null) {
                                        $PROCESSED["ssi_record_locator"] = $rpnow_response["ssiRecordLocator"];
                                    }
                                    if (!$db->AutoExecute("rp_now_users", $PROCESSED, "UPDATE", "rpnow_id = " . $rp_now_user->getID())) {
                                        application_log("error", "An error occurred while attempting to save rp-now con:" . $db->ErrorMsg());
                                    }
                                    break;
                                default:
                                    add_error("An error occurred while attempting to connect to RpNow, HTTP code: " . $http_code);
                                    application_log("error", "An error occurred while attempting to connect to RpNow, HTTP code:" . $http_code);
                                    application_log("error", "rpnow response: " . $response);
                            }
                        }
                    }
                }
            }
            if (unlink(CACHE_DIRECTORY."/rp_now.lck")) {
                application_log("success", "Lock file deleted.");
            } else {
                application_log("error", "Unable to delete RpNow lock file: ".CACHE_DIRECTORY."/rp_now.lck");
            }
        } else {
            application_log("error", "Could not write RpNow lock file, exiting.");
        }
    } else {
        application_log("error", "RpNow lock file found, exiting.");
    }
} else {
    application_log("error", "Error with cache directory [".CACHE_DIRECTORY."], not found or not writable.");
}

