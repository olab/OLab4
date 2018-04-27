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
 * @author Unit: Faculty of Medicine, MedIT
 * @author Developer: Carlos Torchia <carlos.torchia@ubc.ca>
 * @copyright Copyright 2016 University of British Columbia. All Rights Reserved.
 */

class Entrada_Api implements Entrada_IApi {

    protected $output_format;
    protected $auth;
    protected $callbacks;

    public function __construct($output_format, $auth, array $callbacks) {
        $this->output_format = $output_format;
        $this->auth = $auth;
        $this->callbacks = $callbacks;
    }

    public function handle() {
        global $translate;
        try {
            if (!$this->auth || (isset($_SESSION["isAuthorized"]) && (bool) $_SESSION["isAuthorized"])) {
                $http_request_method = strtoupper(clean_input($_SERVER["REQUEST_METHOD"], "alpha"));
                global ${"_".$http_request_method}; // e.g. $_GET, $_POST, etc.
                $this->request = $request = ${"_".$http_request_method};
                if (array_key_exists($http_request_method, $this->callbacks)) {
                    if (array_key_exists("method", $request)) {
                        $api_method = $request["method"];
                        if (array_key_exists($api_method, $this->callbacks[$http_request_method])) {
                            $function = $this->callbacks[$http_request_method][$api_method];
                            $bound_function = Closure::bind($function, $this, __CLASS__);
                            $response = $bound_function($request);
                            $this->respond($response);
                        } else {
                            throw new Entrada_Api_NotImplemented("API method ".$request["method"]." is not implemented");
                        }
                    } else {
                        throw new Entrada_Api_BadRequest("Missing API method");
                    }
                } else {
                    throw new Entrada_Api_NotImplemented("HTTP method ".$http_request_method." is not implemented");
                }
            } else {
                throw new Entrada_Api_Unauthorized();
            }
        } catch (Entrada_Api_NotImplemented $e) {
            application_log("error", $e->getMessage());
            $this->respondError("501 Not Implemented", $translate->_("This service is not implemented."));
        } catch (Entrada_Api_BadRequest $e) {
            application_log("error", $e->getMessage());
            $this->respondError("400 Bad Request", $translate->_("Invalid request"));
        } catch (Entrada_Api_NotFound $e) {
            application_log("error", $e->getMessage());
            $this->respondError("404 Not Found", $translate->_("This resource is not found."));
        } catch (Entrada_Api_Unauthorized $e) {
            application_log("error", $e->getMessage());
            $this->respondError("401 Unauthorized", $translate->_("Please login."));
        } catch (Entrada_Api_Forbidden $e) {
            application_log("error", $e->getMessage());
            $this->respondError("403 Forbidden", $translate->_("You do not have access to this resource. Contact your administrator."));
        } catch (Exception $e) {
            application_log("error", $e->getMessage());
            $this->respondError("500 Internal Server Error", $translate->_("An error occurred. Please try again later."));
        }
    }

    protected function respondError($http_status_code, $response) {
        ob_clean();
        header("HTTP/1.0 ".$http_status);
        echo json_encode(array("status" => "error", "data" => $response));
        ob_end_flush();
    }

    protected function respond($response) {
        ob_clean();
        switch ($this->output_format) {
        case "json":
            header("Content-Type: application/json");
            echo json_encode(array("status" => "success", "data" => $response));
            break;
        default:
            echo $response;
        }
        ob_end_flush();
    }

    protected function verifyAmIAllowed($resource, $action, $assert = true) {
        global $ENTRADA_ACL, $ENTRADA_USER;
        if (!$ENTRADA_ACL->amIAllowed($resource, $action, $assert)) {
            throw new Entrada_Api_Forbidden("Permission denied to ".$action." ".$resource.".");
        }
    }

    protected function verifyOrganisation($organisation_id, $action = "read", $assert = true) {
        global $ENTRADA_ACL, $ENTRADA_USER;
        if (!$ENTRADA_ACL->amIAllowed("resourceorganisation".$organisation_id, $action, $assert)) {
            application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this organisation [".$organisation_id."]");
            throw new Entrada_Api_NotFound("Organisation ".$organisation_id." not found or unaccessible.");
        }
    }

    protected function validateRequestField($key, array $rules, $required = true, $default = null) {
        if (array_key_exists($key, $this->request) && $tmp_input = clean_input($this->request[$key], $rules)) {
            return $tmp_input;
        } else if ($required) {
            throw new Entrada_Api_BadRequest("Expected request parameter ".$key);
        }
    }

    protected function validateProxyID() {
        global $ENTRADA_USER;
        if ($ENTRADA_USER) {
            if ($ENTRADA_USER->getActiveRole() == "admin") {
                if (isset($this->request["proxy_id"]) && $tmp_input = clean_input($this->request["proxy_id"], "int")) {
                    $proxy_id = $tmp_input;
                } else {
                    $proxy_id = $ENTRADA_USER->getActiveID();
                }
            } else {
                $proxy_id = $ENTRADA_USER->getActiveID();
            }
            return $proxy_id;
        } else {
            return null;
        }
    }

    protected function validateOrganisationID() {
        global $ENTRADA_USER;
        if (isset($this->request["org_id"]) && $tmp_input = clean_input($this->request["org_id"], "int")) {
            return $this->request["org_id"];
        } else {
            return $ENTRADA_USER->getActiveOrganisation();
        }
    }
}
