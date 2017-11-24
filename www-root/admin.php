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
 * Serves as the main Entrada administrative request controller file.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/core",
    dirname(__FILE__) . "/core/includes",
    dirname(__FILE__) . "/core/library",
    dirname(__FILE__) . "/core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

ob_start("on_checkout");

$PATH_INFO = ((isset($_SERVER["PATH_INFO"])) ? clean_input($_SERVER["PATH_INFO"], array("url", "lowercase")) : "");
$PATH_SEPARATED = explode("/", $PATH_INFO);

/**
 * This section of code sets the $SUBMODULE variable.
 */
if ((isset($PATH_SEPARATED[2])) && (trim($PATH_SEPARATED[2]) != "")) {
	$SUBMODULE = $PATH_SEPARATED[2]; // This is sanitized when $PATH_SEPARATED is created.
} else {
	$SUBMODULE = false; // This is the default file that will be launched upon successful login.
}

if((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL.((isset($_SERVER["REQUEST_URI"])) ? "?url=".rawurlencode(clean_input($_SERVER["REQUEST_URI"], array("nows", "url"))) : ""));
	exit;
} else {
	define("IN_ADMIN", true);

	/**
	 * If they were logged into another application and came here, they should still be
	 * signed in, unfortunately I can't do that yet, so they're logged out.
	 */
	if($_SESSION["details"]["app_id"] != AUTH_APP_ID) {
		application_log("error", "User attempted to enter from a different app_id, they were forced to log out.");

		header("Location: ".ENTRADA_URL."/?action=logout");
		exit;
	}

	if(($_SESSION["details"]["expires"]) && ($_SESSION["details"]["expires"] <= time())) {
		header("Location: ".ENTRADA_URL."/?action=logout");
		exit;
	}

	/**
	 * This function controls setting the permission masking feature.
	 */
	permissions_mask();

	/**
	 * This function updates the users_online table.
	 */
	users_online();
	$proxy_id = $ENTRADA_USER->getActiveId();
	if(($proxy_id != $ENTRADA_USER->getID()) && ($_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["expires"] <= time())) {
		unset($proxy_id);
	}

	if(!isset($proxy_id)) {
		$proxy_id = $ENTRADA_USER->getID();
	}

	/**
	 * Redirect guests and students users away from the admin section.
	 */
	if (in_array($ENTRADA_USER->getActiveGroup(), array("guest", "student"))) {

		$course_contact = Models_Course_Contact::fetchRowByProxyIDContactType($proxy_id, "ta");
		if(!$course_contact) {
				header("Location: ".ENTRADA_URL);
				exit;
		}
		
	}

	if($PATH_SEPARATED[1] != "") {
		$MODULE = $PATH_SEPARATED[1];
	} else {
		/**
		 * @todo hbrundage Fix this to rely on permissions once the schema of start files has been established.
		 */
		if ((array_key_exists($_SESSION["permissions"][$proxy_id]["group"], $ADMINISTRATION)) && (array_key_exists($_SESSION["permissions"][$proxy_id]["role"], $ADMINISTRATION[$_SESSION["permissions"][$proxy_id]["group"]]))) {
			if (in_array($ADMINISTRATION[$_SESSION["permissions"][$proxy_id]["group"]][$_SESSION["permissions"][$proxy_id]["role"]]["start_file"], $ADMINISTRATION[$_SESSION["permissions"][$proxy_id]["group"]][$_SESSION["permissions"][$proxy_id]["role"]]["registered"])) {
				$MODULE = $ADMINISTRATION[$_SESSION["permissions"][$proxy_id]["group"]][$_SESSION["permissions"][$proxy_id]["role"]]["start_file"];
			} else {
				$ERROR++;
				$ERRORSTR[]	= "The start file for the &quot;".$_SESSION["permissions"][$proxy_id]["role"]."&quot; role in &quot;".$_SESSION["permissions"][$proxy_id]["group"]."&quot; group is not registered in file list this role is allow to access. Please fix this in the configuration file or have an administrator do this for you.";
				echo display_error();

				application_log("error", "Start file for ".$_SESSION["permissions"][$proxy_id]["role"]." role in ".$_SESSION["permissions"][$proxy_id]["group"]." group is not a registered access file.");
			}
		} else {
			$ERROR++;
			$ERRORSTR[]	= "Either the group [".$_SESSION["permissions"][$proxy_id]["group"]."] or role [".$_SESSION["permissions"][$proxy_id]["role"]."] in which you reside does not have access to the administration module. Please fix this in the configuration file or have an administrator do this for you.<br /><br />If you're attempting to access this file without a valid account, please note that all access attempts are logged for security purposes and regular audits do take place.";
			echo display_error();

			application_log("error", "Either the ".$_SESSION["details"]["role"]." role or the ".$_SESSION["details"]["group"]." group is not able to access administraiton module.");
		}
	}
}

/**
 * Initialize Entrada_Router so it can load the requested modules.
 */
$router = new Entrada_Router();
$router->setBasePath(ENTRADA_CORE.DIRECTORY_SEPARATOR."modules".DIRECTORY_SEPARATOR."admin");
$router->setSection($SECTION);

define("PARENT_INCLUDED", true);

if (isset($_SESSION["isAuthorized"]) && (bool) $_SESSION["isAuthorized"] && isset($ENTRADA_USER)) {
	if (($cached_user = $ENTRADA_CACHE->load("user_".AUTH_APP_ID."_".$ENTRADA_USER->getID())) && $cached_user != $ENTRADA_USER) {
		$ENTRADA_CACHE->save($ENTRADA_USER, "user_".AUTH_APP_ID."_".$ENTRADA_USER->getID(), array("auth"), 300);
	}
}

switch ($MODULE) {
	case "exams":
		if ($SECTION === "summary" || $SECTION === "category") {
			require_once (ENTRADA_ABSOLUTE."/templates/".$ENTRADA_TEMPLATE->activeTemplate()."/layouts/admin/header_no_prototype.tpl.php");
		} else {
            require_once (ENTRADA_ABSOLUTE."/templates/".$ENTRADA_TEMPLATE->activeTemplate()."/layouts/admin/header.tpl.php");
		}
	break;

	default:
		require_once (ENTRADA_ABSOLUTE."/templates/".$ENTRADA_TEMPLATE->activeTemplate()."/layouts/admin/header.tpl.php");
		break;
}

if ($router && ($route = $router->initRoute($MODULE))) {
    /**
     * Responsible for displaying the permission masks sidebar item
     * if they have more than their own permission set available.
     */
    if (isset($_SESSION["permissions"]) && is_array($_SESSION["permissions"]) && (count($_SESSION["permissions"]) > 1)) {
        $sidebar_html  = "<form id=\"masquerade-form\" action=\"".ENTRADA_URL."/admin\" method=\"get\">\n";
        $sidebar_html .= "<label for=\"permission-mask\">Available permission masks:</label><br />";
        $sidebar_html .= "<select id=\"permission-mask\" name=\"mask\" style=\"width: 100%\" onchange=\"window.location='".ENTRADA_URL."/".$MODULE."/?".str_replace("&#039;", "'", replace_query(array("mask" => "'+this.options[this.selectedIndex].value")))."\">\n";
        $display_masks = false;
        $added_users = array();
        foreach ($_SESSION["permissions"] as $access_id => $result) {
            if (is_int($access_id) && ((isset($result["mask"]) && $result["mask"]) || $access_id == $ENTRADA_USER->getDefaultAccessId()) && array_search($result["id"], $added_users) === false) {
                if (isset($result["mask"]) && $result["mask"]) {
                    $display_masks = true;
                }
                $added_users[] = $result["id"];
                $sidebar_html .= "<option value=\"".(($access_id == $ENTRADA_USER->getDefaultAccessId()) ? "close" : $result["permission_id"])."\"".(($result["id"] == $ENTRADA_USER->getActiveId()) ? " selected=\"selected\"" : "").">".html_encode($result["fullname"]) . "</option>\n";
            }
        }
        $sidebar_html .= "</select>\n";
        $sidebar_html .= "</form>\n";
        if ($display_masks) {
            new_sidebar_item("Permission Masks", $sidebar_html, "permission-masks", "open");
        }
    }

	$module_file = $router->getRoute();
	if ($module_file) {
		require_once($module_file);
	}
} else {
	application_log("error", "The Entrada_Router failed to load a request. The user was redirected to [".ENTRADA_URL."].");

	header("Location: ".ENTRADA_URL);
	exit;
}

require_once (ENTRADA_ABSOLUTE."/templates/".$ENTRADA_TEMPLATE->activeTemplate()."/layouts/admin/footer.tpl.php");

/**
 * Add the Feedback Sidebar Window.
 */
if (isset($_SESSION["isAuthorized"]) && (bool) $_SESSION["isAuthorized"]) {
    $settings = new Entrada_Settings();
    if ($settings->read("bookmarks_display_sidebar")) {
        Models_Bookmarks::showSidebar();
    }
    add_feedback_sidebar($ENTRADA_USER->getActiveGroup());
    Entrada_Utilities::myEntradaSidebar();
    Entrada_Utilities::orgSelectorSidebar();
}