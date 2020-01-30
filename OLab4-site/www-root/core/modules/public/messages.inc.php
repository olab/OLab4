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
 * Module:	Dashboard Notices
 * Area:		Public
 * @author Unit: Medical Education Technology Unit
 * @author Developer: Matt Simpson <simpson@post.queensu.ca>
 * @copyright Copyright 2006 Queen's University, MEdTech Unit
 *
*/

if (!defined("PARENT_INCLUDED")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
}
$BREADCRUMB[] = array("url" => ENTRADA_RELATIVE."/messages", "title" => "Previously Read Messages");
if (($router) && ($router->initRoute())) {
	$module_file = $router->getRoute();
	if ($module_file) {
		require_once($module_file);
	}
}