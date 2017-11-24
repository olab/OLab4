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
 * Secondary controller file used by the draft schedule module within the learning events module.
 * /admin/events/drafts
 *
 * @author Organisation: Univeristy of Calgary
 * @author Unit: Faculty of Medicine
 * @author Developer: Ryan Warner <ryan.warner@queensu.ca>
 * @copyright Copyright 2012 University of Calgary. All Rights Reserved.
 *
*/

if (!defined("IN_EVENTS")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} else {
	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/events/drafts", "title" => "Learning Event Draft Schedule");

	if (($router) && ($router->initRoute())) {
		$module_file = $router->getRoute();
		if ($module_file) {
			require_once($module_file);
		}
	} else {
		$url = ENTRADA_URL."/admin/".$MODULE;
		application_log("error", "The Entrada_Router failed to load a request. The user was redirected to [".$url."].");
		header("Location: ".$url);
		exit;
	}
}