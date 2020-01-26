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
 * @author Organisation: University of British Columbia
 * @author Unit: Faculty of Medicine
 * @author Developer: Carlos Torchia <carlos.torchia@ubc.ca>
 * @copyright Copyright 2016 University of British Columbia. All Rights Reserved.
 *
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_WEEKS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("weekcontent", "update", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    require_once(dirname(__FILE__) . "/form.controller.php");

    $view = function ($week, $curriculum_types) use ($translate) {
        $form_view = new Zend_View();
        $form_view->setScriptPath(dirname(__FILE__));
        $form_view->translate = $translate;
        $form_view->mode = "edit";
        $form_view->week = $week;
        $form_view->curriculum_types = $curriculum_types;
        ?>
        <h1><?php echo $translate->_("Edit Week"); ?></h1>
        <?php echo $form_view->render("form.view.php"); ?>
        <?php
    };

    if (isset($WEEK_ID) && $WEEK_ID) {
        $week = Models_Week::fetchRowByID($WEEK_ID);
        if ($week) {
            $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/weeks?section=edit&id=" . $week->getID(), "title" => $week->getWeekTitle());
            switch ($STEP) {
            case 2:
                weeks_process_form($week, $view);
                break;
            case 1 :
            default:
                $curriculum_types = Models_Curriculum_Type::fetchAllByOrg($ENTRADA_USER->getActiveOrganisation());
                $view($week, $curriculum_types);
                break;
            }
        } else {
            $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/weeks?section=edit", "title" => $translate->_("Edit Week"));
            add_error($translate->_("Week not found."));
            echo display_error();
        }
    } else {
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/weeks?section=edit", "title" => $translate->_("Edit Week"));
        add_error($translate->_("No Week specified."));
        echo display_error();
    }
}
