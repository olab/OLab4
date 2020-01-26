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
 * API to search for users to assign as graders to an exam.
 *
 * @author Organisation: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Robert Fotino <robert.fotino@gmail.com>
 * @copyright Copyright 2015 Regents of the University of California. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_EXAMS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("exam", "create", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));
    echo display_error();
    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    // require_once("Classes/users/UserPhoto.class.php");
    // require_once("Classes/users/UserPhotos.class.php");
    ob_clear_open_buffers();    
    $results = array();
    $course_id = isset($_GET["course_id"]) ? (int)$_GET["course_id"] : 0;
    $term = isset($_GET["term"]) ? $_GET["term"] : "";
    $output = null;

    if ($course_id && $term) {
        $proxy_ids = array();
        $course_contacts = Models_Course_Contact::fetchAllByCourseIDContactType($course_id);
        
        foreach ($course_contacts as $contact) {
            $proxy_id = (int)$contact->getProxyId();
            $proxy_ids[$proxy_id] = $proxy_id;
        }
        $course_groups = Models_Course_Group::fetchAllByCourseID($course_id);
        
        foreach ($course_groups as $cgroup) {
            $cgroup_contacts = Models_Course_Group_Contact::fetchAllByCgroupID($cgroup->getCgroupId());
            
            foreach ($cgroup_contacts as $contact) {
                $proxy_id = (int)$contact->getProxyId();
                $proxy_ids[$proxy_id] =  $proxy_id;
            }
        }

        foreach ($proxy_ids as $proxy_id) {
            $user = Models_User::fetchRowByID($proxy_id); 
            if ((stripos($user->getName("%f"), $term) !== false) || (stripos($user->getName("%l"), $term) !== false)) {
                $row['proxy_id'] = $proxy_id;
                $row['fullname'] = $user->getFullname(true); //display lastname, firstname
                $row['email'] = $user->getEmail();
                $row['organisation_title'] = Models_Organisation::fetchRowByID($user->getOrganisationID())->getOrganisationTitle();
                $row['group'] = Models_User_Access::fetchRowByUserIDAppID($proxy_id)->getGroup();
                $results[] = $row;
            }
        }

        usort($results, function($a, $b) { return strcasecmp($a["fullname"], $b["fullname"]); });
    }
    
    if ($results) {
        $output .= json_encode($results);
    } else {
        $output .= json_encode(array(array("response" => $fullname." was not found")));
    }
    echo $output;
    exit;
}