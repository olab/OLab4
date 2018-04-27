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
 * API that save LRS statements to the stats table, used for tincan and scorm
 * module player when no valid LRS settings are found in the database.
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
    dirname(__FILE__) . "/../core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

// Verifying if this is a authenticated request
if (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"] || !isset($_SERVER["REQUEST_METHOD"])) {
    header("Location: " . ENTRADA_URL);
    exit;
}

$request_method = strtoupper(clean_input($_SERVER["REQUEST_METHOD"], "alpha"));

/*
 * Which xAPI API is being called.
 */
$uri_action = "";

/*
 * URI parameters.
 */
$uri_parameters = [];

/*
 * Parse the URI parameters into an array.
 */
if (isset($_SERVER["REQUEST_URI"])) {
    $uri = preg_replace("#[\w-/]+XAPI/#", "", $_SERVER["REQUEST_URI"]);
    $request = parse_url($uri);

    if (!empty($request["path"])) {
        $uri_action = str_replace("/api/lrs-to-stats.api.php/", "", clean_input($request["path"], ["nows", "lower"]));
    }

    if (!empty($request["query"])) {
        $parameters = explode("&", $request["query"]);
        if ($parameters) {
            foreach ($parameters as $parameter) {
                $element = explode("=", $parameter);
                if (isset($element[0]) && ($key = clean_input($element[0], ["decode", "striptags", "nows"])) && isset($element[1]) && ($value = clean_input($element[1], ["decode", "striptags", "trim"]))) {
                    $uri_parameters[$key] = $value;
                }
            }
        }
    }
}

/*
 * Fetch and store the payload being sent in this request.
 */
$input = fopen("php://input", "r");
$payload = "";
while ($data = fread($input, 1024)) {
    $payload .= $data;
}

/*
 * Switch the different xAPI APIs.
 */
switch ($uri_action) {
    case "activities/state" :
        if (!empty($uri_parameters["agent"]) && !empty($uri_parameters["stateId"]) && !empty($uri_parameters["activityId"])) {
            $proxy_id = 0;

            $agent = json_decode(rawurldecode($uri_parameters["agent"]));
            if (!empty($agent->mbox)) {
                $email = str_replace("mailto:", "", clean_input($agent->mbox, ["striptags", "nows", "lower"]));
                $query = "SELECT * 
                          FROM `" . AUTH_DATABASE . "`.`user_data` 
                          WHERE `email` = ?";
                $result = $db->GetRow($query, [$email]);
                if ($result) {
                    $proxy_id = $result["id"];
                }

                if ($ENTRADA_USER && ($ENTRADA_USER->getActiveId() != $proxy_id)) {
                    application_log("notice", "Interesting: lrs-to-stats.api.php is receiving an e-mail address / proxy_id [" . $email . " / " . $proxy_id . "] that doesn't belong to this logged in user.");
                }
            }

            $state_id = str_replace("http://", "", rawurldecode($uri_parameters["stateId"]));
            $activity_id = str_replace("http://", "", rawurldecode($uri_parameters["activityId"]));

            if ($proxy_id) {
                if ($request_method == "PUT") {
                    /*
                     * Writing the progress that the learner has made to the learning_objects_progress table.
                     */
                    if ($payload) {
                        $record = [
                            "proxy_id" => $proxy_id,
                            "learning_objects_activity_id" => $activity_id,
                            "learning_objects_state_id" => $state_id,
                            "data" => $payload,
                            "created_date" => time()
                        ];

                        $progress = new Models_LearningObject_Progress();
                        $progress->fromArray($record)->insert();
                    }
                } elseif ($request_method == "GET") {
                    /*
                     * Reading the progress that the learner may have made in the learning_objects_progress table.
                     */
                    $progress_record = Models_LearningObject_Progress::fetchLastByProxyIDActivityID($proxy_id, $activity_id, $state_id);
                    if ($progress_record) {
                        ob_clear_open_buffers();

                        $json_str = json_encode(["data" => $progress_record["data"]]);

                        header("Access-Control-Allow-Header: Content-Type,Content-Length,Authorization,If-Match,If-None-Match,X-Experience-API-Version,X-Experience-API-Consistent-Through");
                        header("Access-Control-Allow-Origin: *");
                        header("Access-Control-Expose-Header: Last-Modified,Cache-Control,Content-Type,Content-Length,WWW-Authenticate,X-Experience-API-Version,X-Experience-API-Consistent-Through");
                        header("Cache-Control: no-cache");
                        header("Connection: keep-alive");
                        header("Authorization: " . (isset($_SERVER["HTTP_AUTHORIZATION"]) ? $_SERVER["HTTP_AUTHORIZATION"] : ""));
                        header("Content-Type: application/json");
                        header("X-Experience-API-Version: 1.0.12");
                        header("Access-Control-Allow-Methods: HEAD,GET,POST,PUT,DELETE");
                        header("Content-Length: " . strlen($json_str));

                        echo $json_str;
                        exit;
                    }
                } else {
                    application_log("notice", "Unknown xAPI request_method for activities/state [" . $request_method . "]. Just FYI.");
                }
            }
        }
        break;
    case "statements" :
        $payload = json_decode($payload);
        if (is_object($payload) && !empty($payload->actor->mbox)) {
            $proxy_id = 0;

            $email = str_replace("mailto:", "", clean_input($payload->actor->mbox, ["striptags", "nows", "lower"]));
            $query = "SELECT * 
                      FROM `" . AUTH_DATABASE . "`.`user_data` 
                      WHERE `email` = ?";
            $result = $db->GetRow($query, [$email]);
            if ($result) {
                $proxy_id = $result["id"];
            }

            if ($ENTRADA_USER && ($ENTRADA_USER->getActiveId() != $proxy_id)) {
                application_log("notice", "Interesting: lrs-to-stats.api.php is receiving an e-mail address / proxy_id [" . $email . " / " . $proxy_id . "] that doesn't belong to this logged in user.");
            }

            $timestamp = strtotime($payload->timestamp);
            $verb = clean_input(str_ireplace("http://adlnet.gov/expapi/verbs/", "", $payload->verb->id), ["nows", "alphanumeric"]);
            $object = clean_input(str_ireplace("http://", "", $payload->object->id), ["nows", "alphanumeric"]);

            $definition = isset($payload->object->definition->name->und) ? $payload->object->definition->name->und : "";

            add_statistic("learningobject", $verb, $object, $definition, $proxy_id);
        }
        break;
    default :
        
        application_log("notice", "Unknown xAPI uri_action [" . $uri_action . "]. Just FYI.");
        break;
}
