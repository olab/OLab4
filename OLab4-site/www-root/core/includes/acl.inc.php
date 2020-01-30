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
 * Loads the users ACL either from cache or constructs a new one.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Harry Brundage <hbrundage@qmed.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

require_once("Entrada/authentication/aclfactory.inc.php");
require_once("Entrada/authentication/entrada_acl.inc.php");

if (isset($_SESSION["isAuthorized"]) && $_SESSION["isAuthorized"] && isset($_SESSION["details"])) {
	$access_hash_flag = (User::getAccessHash() == $ENTRADA_CACHE->load("access_hash_" . $ENTRADA_USER->getID())) ? true : false;
	if ((isset($ENTRADA_CACHE)) && (!DEVELOPMENT_MODE)) {
		if (!($ENTRADA_CACHE->test("acl_"  . AUTH_APP_ID . "_" . $ENTRADA_USER->getID())) || !$access_hash_flag) {
			$ENTRADA_ACL = new Entrada_Acl($_SESSION["details"]);
			$ENTRADA_CACHE->save($ENTRADA_ACL, "acl_" . AUTH_APP_ID . "_" . $ENTRADA_USER->getID());
		} else {
			$ENTRADA_ACL = $ENTRADA_CACHE->load("acl_" . AUTH_APP_ID . "_" . $ENTRADA_USER->getID());
		}
	} else {
		$ENTRADA_ACL = new Entrada_Acl($_SESSION["details"]);
	}
}