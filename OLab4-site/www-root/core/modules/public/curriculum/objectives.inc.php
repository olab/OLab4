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
 * This file displays the list of objectives pulled
 * from the entrada.global_lu_objectives table.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <simpson@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 *
*/

if((!defined("PARENT_INCLUDED")) || (!defined("IN_CURRICULUM"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
}  else {
    //Translators only have one view, but they do not have admin access so they need to access
    //the module from the learner view menu, but still have all their special access fields available
    if ($ENTRADA_USER->getActiveRole() == "translator") {
        $LEARNER_VIEW = false;
    } else {
        $LEARNER_VIEW = true;
    }
    
    $router->setBasePath(ENTRADA_CORE.DIRECTORY_SEPARATOR."modules".DIRECTORY_SEPARATOR."admin");
    require_once ("modules/admin/curriculum/objectives.inc.php");
}