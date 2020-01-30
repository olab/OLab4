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
 * Module:	Registered Courses
 * Area:		Public
 * @author Unit: Medical Education Technology Unit
 * @author Director: Dr. Benjamin Chen <bhc@post.queensu.ca>
 * @author Developer: Matt Simpson <simpson@post.queensu.ca>
 * @version 3.0
 * @copyright Copyright 2006 Queen's University, MEdTech Unit
 *
 * $Id: courses.inc.php 1171 2010-05-01 14:39:27Z ad29 $
*/

if (!defined("PARENT_INCLUDED")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
}

define("IN_COURSES", true);

$BREADCRUMB[] = array("url" => ENTRADA_URL."/".$MODULE, "title" => $translate->_($MODULE));

if (($router) && ($router->initRoute())) {
	$COURSE_ID = 0;
	$ORGANISATION_ID = false;

	if ((isset($_GET["id"])) && ((int) trim($_GET["id"]))) {
		$COURSE_ID = (int) trim($_GET["id"]);
	}

	if ($COURSE_ID) {
        if ($course = Models_Course::get($COURSE_ID)) {
            /**
             * Check if alltraffic should be re-routed to an external URL
             */
            if ($course->getCourseRedirect() && $course->getCourseUrl() ) {
                header("Location: ".$course->getCourseUrl());
                exit;
            }
            unset($course);
        }
    }

	if ((isset($_GET["organisation_id"])) && ((int) trim($_GET["organisation_id"]))) {
		$ORGANISATION_ID = (int) trim($_GET["organisation_id"]);
	}

	$module_file = $router->getRoute();
	if ($module_file) {
		require_once($module_file);
	}
}