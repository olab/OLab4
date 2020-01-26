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
 * API to gather task information
 *
 * @author Organisation: University of Ottawa
 * @author Unit: School of Medicine
 * @author Developer: Alfreda Morrissey <amorriss@uottawa.ca>
 * @copyright Copyright 2017 Ottawa University. All Rights Reserved.
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
if ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    http_response_code(401);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("event", "read", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%s\">%s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");

    http_response_code(403);
    exit;
} else{
    ob_clear_open_buffers();
    
    $request_method = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));    
    $request = ${"_" . $request_method};
    
    switch ($request_method) {
        case "GET" :

            $arr=array();
            if ($request["filters"]){
                $decoded_html = html_decode($request["filters"]);
                $decoded_json = json_decode($decoded_html);

                switch (json_last_error()) {
                case JSON_ERROR_NONE:
                    break;
                default:
                    http_response_code(400);
                    exit;
                }
                
                $filterArray=$decoded_json;
            }
            
            $finalResult = array();
            
            if(isset($filterArray) && is_array($filterArray)) {
                foreach ($filterArray as $item) {
                    $class = "Entrada_Filter_".$item;
                    $filterObject = new $class();
                    $finalResult = array_merge($finalResult, $filterObject->toArray());
                }
            }
            
            echo json_encode ($finalResult);

            
        default:
            http_response_code(405);
            exit;
    }
    exit;
}