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

function weeks_process_form($week, $view) {
    global $translate, $ONLOAD, $ENTRADA_USER, $ERROR, $NOTICE, $SUCCESS;

    if (!$week) {
        $week = new Models_Week(array(
            "created_date" => time(),
            "created_by" => $ENTRADA_USER->getID(),
        ));
    }

    if ((isset($_POST["week_title"])) && ($tmp_input = clean_input($_POST["week_title"], array("notags", "trim")))) {
        $week->fromArray(array("week_title" => $tmp_input));
    } else {
        add_error("The <strong>" . $translate->_("Week Title") . "</strong> field is required.");
    }

    if ((isset($_POST["curriculum_type_id"])) && ($tmp_input = clean_input($_POST["curriculum_type_id"], array("nows", "int")))) {
        $week->fromArray(array("curriculum_type_id" => $tmp_input));
    } else {
        add_error("The <strong>" . $translate->_("Curriculum Category") . "</strong> field is required.");
    }

    if (isset($_POST["week_order"])) {
        $tmp_input = clean_input($_POST["week_order"], array("nows", "int"));
        $week->fromArray(array("week_order" => $tmp_input));
    } else {
        add_error("The <strong>" . $translate->_("Week Order") . "</strong> field is required.");
    }

    if (!$ERROR) {
        $week->fromArray(array(
            "updated_date" => time(),
            "updated_by" => $ENTRADA_USER->getID(),
        ));
        if ($week->getID()) {
            $result = $week->update();
        } else {
            $result = $week->insert();
        }
        if ($result) {
            add_success("You successfully updated " . $week->getWeekTitle() . ". You will now be redirected to the " . $translate->_("Weeks") . " index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"" . ENTRADA_URL . "/admin/weeks\" style=\"font-weight: bold\">click here</a> to continue.");
            $ONLOAD[] = "setTimeout('window.location=\\'" . ENTRADA_URL . "/admin/weeks\\'', 5000)";
        } else {
            add_error("Unable to update " . $week->getWeekTitle() . ". You will now be redirected to the " . $translate->_("Weeks") . " index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"" . ENTRADA_URL . "/admin/weeks\" style=\"font-weight: bold\">click here</a> to continue.");
            $ONLOAD[] = "setTimeout('window.location=\\'" . ENTRADA_URL . "/admin/weeks\\'', 5000)";
        }
    }

    if ($ERROR) {
        echo display_error();
        $curriculum_types = Models_Curriculum_Type::fetchAllByOrg($ENTRADA_USER->getActiveOrganisation());
        $view($week, $curriculum_types);
    }

    if ($NOTICE) {
        echo display_notice();
    }

    if ($SUCCESS) {
        echo display_success();
    }
}
