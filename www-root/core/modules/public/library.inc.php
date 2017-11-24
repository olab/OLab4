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
 *
 * $Id: library.inc.php 1171 2010-05-01 14:39:27Z ad29 $
 */

if(!defined("PARENT_INCLUDED")) {
	exit;
} elseif(!$_SESSION["isAuthorized"]) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif(!$ENTRADA_ACL->amIAllowed('library', 'read')) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
/**
 * SEE: https://developer.entrada-project.org/ticket/30
 *
 * @todo This needs to be rewritten to support multiple proxy servers. Basically
 * what I imagine is that when a user clicks the "Library" tab (or any other
 * proxified link within the system) they are directed to a /proxy page that
 * allows them to choose which of the available proxy servers they are going to
 * be directed through.
 *
 * It should give them the option of choosing "Don't ask me again." and could
 * also provide a list of proxied URLs they have visted through the system.
 */
	if((check_proxy("library")) || (isset($_GET["override"]))) {
		header("Location: ".$PROXY_URLS["library"]["active"]);
		exit;
	} else {
		header("Location: ".$PROXY_URLS["library"]["inactive"]);
		exit;
	}
}
?>