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
 * API to handle interaction with assessment learning events.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
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

if((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("gradebook", "create", false)) {
	$ERROR++;
	$ERRORSTR[]	= "You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    
    $request = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));
	
	$request_var = "_".$request;
	
	$method = clean_input(${$request_var}["method"], array("trim", "striptags"));
    
    switch ($request) {
        case "POST" :
        break;
        case "GET" :
            switch ($method) {
                case "title_search" :
                    if(isset(${$request_var}["title"]) && $tmp_input = clean_input(${$request_var}["title"], array("trim", "striptags"))) {
						$title = $tmp_input;
					} else {
						add_error("To search for learning events, begin typing the title of the event you wish to find in the search box.");
					}
                    
                    if(isset(${$request_var}["course_id"]) && $tmp_input = clean_input(${$request_var}["course_id"], array("trim", "int"))) {
						$course_id = $tmp_input;
					} else {
						add_error("No course ID provided.");
					}
                    
                    if(isset(${$request_var}["cperiod"]) && $tmp_input = clean_input(${$request_var}["cperiod"], array("trim", "int"))) {
						$cperiod = $tmp_input;
					} else {
						add_error("No audience ID provided.");
					}
                    
                    if (!$ERROR) {
                        /*
                        $ca = new Models_Course_Audience();
                        $course_audience = $ca->fetchRowByCourseIDAudienceTypeAudienceValue($course_id, "group_id", $audience);
                        
                        if ($course_audience) {
                            $curriculum_period = $course_audience->getCurriculumPeriod($course_audience->getCperiodID());
                        }
                        */
                        if ($cperiod) {
                            $curriculum_period = Models_Curriculum_Period::fetchRowByID($cperiod);
                        }
                        
                        $e = new Models_Event();
                        
                        if ($curriculum_period) {
                            $events = $e->fetchAllByCourseIdTitleDates($course_id, $title, $curriculum_period->getStartDate(), $curriculum_period->getFinishDate());
                        } else {
                            $events = $e->fetchAllByCourseIdTitle($course_id, $title);
                        }
                        
                        if ($events) {
                            $events_array = array();
                            foreach ($events as $event) {
                                $events_array[] = array(
                                    "event_id" => $event->getID(),
                                    "event_title" => $event->getEventTitle(),
                                    "event_start" => date("D M d/y g:ia", $event->getEventStart())
                                );
                            }
                            echo json_encode(array("status" => "success", "data" => $events_array));
                        } else {
                            echo json_encode(array("status" => "error", "data" => array("No events found with a title containing <strong>". $title ."</strong>")));
                        }
                    } else {
                        echo json_encode(array("status" => "error", "data" => $ERRORSTR));
                    }
                break;
            }
        break;
    }
}
?>