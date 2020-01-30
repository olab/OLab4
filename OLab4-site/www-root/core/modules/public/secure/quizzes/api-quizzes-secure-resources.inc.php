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
 * API to handle interaction with the front page resource wizard.
 *
 * @author Organisation: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Daniel Noji <dnoji@mednet.ucla.edu>
 * @copyright Copyright 2014 UC Regents. All Rights Reserved.
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
} else {
    unset($_SESSION['app-'.AUTH_APP_ID]['secure']['active_quiz']); //Reset
    ob_clear_open_buffers();
    
    $request = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));
    $request_var = "_".$request;
    $method = clean_input(${$request_var}["method"], array("trim", "striptags"));
    
    if (isset(${$request_var}["aquiz_id"]) && $tmp_input = clean_input(${$request_var}["aquiz_id"], array("trim", "int"))) {
        $PROCESSED["aquiz_id"] = $tmp_input;
    }
    
    $attached_quiz = Models_Quiz_Attached::fetchRowByAquizID($PROCESSED["aquiz_id"]);
    if ($attached_quiz){
        
        switch ($request) {

            case "GET" :
                switch ($method) {
                    case "get-file" :

                        $access_file = Models_Quiz_Attached_AccessFiles::fetchRowByAquizID($attached_quiz->getAquizID());
                        if ($access_file){
                            if((@file_exists(SECURE_QUIZ_STORAGE_PATH."/".$access_file->getID())) && (@is_readable(SECURE_QUIZ_STORAGE_PATH."/".$access_file->getID()))) {
                                /*
                                 * It is apparently, not possible to access parameters passed through query strings when serving uploaded files through an application link (e.g. seb://).
                                 * This sets the active quiz in the session, so that the correct SEB quiz file is served in file-seb.php
                                 */
                                $_SESSION['app-'.AUTH_APP_ID]['secure']['active_quiz'] = $attached_quiz->getAquizID();
                                echo json_encode(array("status" => "success", "data" => "Quiz is ready!"));
                            } else {
                                echo json_encode(array("error" => "success", "data" => "The Safe Exam Browser file needed to access this quiz could not be found on the server. Please contact an administrator."));
                            }

                        } else {
                            echo json_encode(array("status" => "empty", "data" => array("The Safe Exam Browser(SEB) file needed to access this secure quiz, has not been added to the system. Please contact an administrator.")));
                        }
                    break;
                }
            break;
        }
    } else {
        echo json_encode(array("status" => "error", "data" => "An error occurred while loading this secure quiz. Please contact an administrator"));
    }
    exit;
}