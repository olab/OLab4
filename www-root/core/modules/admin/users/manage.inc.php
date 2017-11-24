<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Allows administrators to edit user incidents in the entrada_auth.user_incidents table.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_USERS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("user", "update", false)) {
    add_error("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$GROUP."] and role [".$ROLE."] do not have access to this module [".$MODULE."]");
} else {
    define("IN_MANAGE_USER", true);

    $user_record = false;
    $has_user_access = false;

    $user = Models_User::fetchRowByID($PROXY_ID);
    if ($user) {
        $user_record = $user->toArray();
    }

    if ($user_record && $router && $router->initRoute()) {
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/users/manage?id=".$PROXY_ID, "title" => html_encode($user_record["firstname"]." ".$user_record["lastname"]));

        $module_file = $router->getRoute();
        if ($module_file) {

            $has_user_access = (Models_User_Access::fetchAllByUserID($PROXY_ID) ? true : false);
            add_user_management_sidebar($has_user_access);
            
            $user_access = Models_User_Access::fetchRowByUserIDAppID($PROXY_ID);

            if ($user_access && $user_access->getGroup() == 'student') {
                add_student_management_sidebar();
            }

            require_once($module_file);
        }
    } else {
        $url = ENTRADA_URL."/admin/".$MODULE;
        application_log("error", "The Entrada_Router failed to load a request. The user was redirected to [".$url."].");

        header("Location: ".$url);
        exit;
    }
}

/**
 * Creates the profile sidebar to appear on all profile pages. The sidebar content will vary depending on the permissions of the user.
 *
 * @param bool $has_user_access - does the user have a user_access record? If not only the edit sidebar item should be available
 */
function add_user_management_sidebar ($has_user_access = true) {
    global $translate, $ENTRADA_ACL, $PROXY_ID;

    $baseurl = ENTRADA_URL."/admin/users/manage";

    $sidebar_html  = "<ul class=\"menu\">";
    if ($has_user_access) {
        $sidebar_html .= "<li class=\"link\"><a href=\"" . $baseurl . "?id=" . $PROXY_ID . "\">Overview</a></li>\n";
    }
    $sidebar_html .= "<li class=\"link\"><a href=\"" . $baseurl . "?section=edit&id=" . $PROXY_ID . "\">Edit Profile</a></li>\n";
    if ($has_user_access) {
        if ($ENTRADA_ACL->amIAllowed("masquerade", "read") && $PROXY_ID != $_SESSION["details"]["id"]) {
            $sidebar_html .= "<li class=\"link\"><a href=\"" . ENTRADA_URL . "/admin/users?section=masquerade&id=" . $PROXY_ID . "\">Login as User</a></li>\n";
        }
        $sidebar_html .= "<li class=\"link\"><a href=\"" . $baseurl . "/metadata?id=" . $PROXY_ID . "\">Edit Meta Data</a></li>\n";
        $sidebar_html .= "<li class=\"link\"><a href=\"" . $baseurl . "/incidents?id=" . $PROXY_ID . "\">Incidents</a></li>\n";
    }
    $sidebar_html .= "</ul>";

    new_sidebar_item($translate->_("User Management"), $sidebar_html, "user-management-nav", "open");
}

function add_student_management_sidebar () {
    global $translate, $PROXY_ID;
    
    $baseurl = ENTRADA_URL."/admin/users/manage/students";
    
    $sidebar_html  = "<ul class=\"menu\">";
    $sidebar_html .= "    <li class=\"link\"><a href=\"" . $baseurl . "?section=mspr&id=" . $PROXY_ID . "\">MSPR</a></li>\n";
    $sidebar_html .= "    <li class=\"link\"><a href=\"" . $baseurl . "?section=observerships&id=" . $PROXY_ID . "\">Observerships</a></li>\n";
    $sidebar_html .= "    <li class=\"link\"><a href=\"" . $baseurl . "?section=leavesofabsence&id=" . $PROXY_ID . "\">Leaves of Absence</a></li>\n";
    $sidebar_html .= "    <li class=\"link\"><a href=\"" . $baseurl . "?section=formalremediation&id=" . $PROXY_ID . "\">Formal Remediation</a></li>\n";
    $sidebar_html .= "    <li class=\"link\"><a href=\"" . $baseurl . "?section=disciplinaryactions&id=" . $PROXY_ID . "\">Disciplinary Actions</a></li>\n";
    $sidebar_html .= "</ul>";

    new_sidebar_item($translate->_("Student Management"), $sidebar_html, "student-management-nav", "open");
}

function add_mspr_management_sidebar () {
    global $translate, $PROXY_ID;

    $user = User::fetchRowByID($PROXY_ID);
    $year = $user->getGradYear();

    $sidebar_html  = "<ul class=\"menu\">";
    $sidebar_html .= "    <li class=\"link\"><a href=\"" . ENTRADA_RELATIVE . "/admin/users/manage/students?section=mspr-options&id=" . $PROXY_ID . "\">MSPR Options</a></li>";
    $sidebar_html .= "    <li class=\"link\"><a href=\"" . ENTRADA_RELATIVE . "/admin/users/manage/students?section=mspr-revisions&id=" . $PROXY_ID . "\">MSPR File Revisions</a></li>";
    $sidebar_html .= "    <li class=\"link\"><a href=\"" . ENTRADA_RELATIVE . "/admin/mspr?mode=year&year=" . $year . "\">Manage Class of ". $year ." MSPRs</a></li>";
    $sidebar_html .= "    <li class=\"link\"><a href=\"" . ENTRADA_RELATIVE . "/admin/mspr?mode=all\">Manage All MSPRs Requiring Attention</a></li>";
    $sidebar_html .= "</ul>";

    new_sidebar_item($translate->_("MSPR Management"), $sidebar_html, "mspr-management-nav", "open");
}
