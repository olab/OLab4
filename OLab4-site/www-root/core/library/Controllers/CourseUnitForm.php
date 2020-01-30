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

class Controllers_CourseUnitForm
{
    public static function processAdd() {
        global $ENTRADA_USER, $COURSE_ID;
        $unit = new Models_Course_Unit();
        $unit->fromArray(array(
            "course_id" => $COURSE_ID,
            "created_date" => time(),
            "created_by" => $ENTRADA_USER->getID(),
        ));
        return self::process($unit);
    }

    public static function processEdit(Models_Course_Unit $unit) {
        global $COURSE_ID, $CUNIT_ID;
        if ($unit->getCourseID() != $COURSE_ID) {
            echo display_error("Invalid course for unit");
            application_log("error", "Invalid course [".$COURSE_ID."] for unit [".$CUNIT_ID."].");
            return array(false, array());
        } else {
            return self::process($unit);
        }
    }

    private static function process(Models_Course_Unit $unit) {
        global $translate, $ONLOAD, $ENTRADA_USER, $ERROR, $NOTICE, $SUCCESS;

        $PROCESSED = array();
        if ((isset($_POST["unit_code"])) && ($tmp_input = clean_input($_POST["unit_code"], array("notags", "trim")))) {
            $PROCESSED["unit_code"] = $tmp_input;
        } else {
            $PROCESSED["unit_code"] = "";
        }
        if ((isset($_POST["unit_title"])) && ($tmp_input = clean_input($_POST["unit_title"], array("notags", "trim")))) {
            $PROCESSED["unit_title"] = $tmp_input;
        } else {
            add_error("The <strong>" . $translate->_("Unit Title") . "</strong> field is required.");
        }
        if ((isset($_POST["unit_description"])) && ($tmp_input = clean_input($_POST["unit_description"], array("allowedtags", "trim")))) {
            $PROCESSED["unit_description"] = $tmp_input;
        } else {
            $PROCESSED["unit_description"] = "";
        }
        if ((isset($_POST["cperiod_select"])) && ($tmp_input = clean_input($_POST["cperiod_select"], array("nows", "int")))) {
            $PROCESSED["cperiod_id"] = $tmp_input;
        } else {
            add_error("The <strong>" . $translate->_("Curriculum Period") . "</strong> field is required.");
        }
        if ((isset($_POST["week_id"])) && ($tmp_input = clean_input($_POST["week_id"], array("nows", "int")))) {
            $PROCESSED["week_id"] = $tmp_input;
        } else {
            $PROCESSED["week_id"] = null;
        }
        if (isset($_POST["unit_order"])) {
            $tmp_input = clean_input($_POST["unit_order"], array("nows", "int"));
            $PROCESSED["unit_order"] = $tmp_input;
        } else {
            add_error("The <strong>" . $translate->_("Unit Order") . "</strong> field is required.");
        }
        if (isset($_POST["associated_week_faculty"])) {
            $PROCESSED["associated_faculty"] = array();
            $associated_faculty = explode(",", $_POST["associated_week_faculty"]);
            foreach($associated_faculty as $proxy_id) {
                if ($proxy_id = clean_input($proxy_id, array("trim", "int"))) {
                    $PROCESSED["associated_faculty"][] = $proxy_id;
                }
            }
        }
        $PROCESSED["objectives"] = self::processObjectives();
        list($PROCESSED["version_cperiod_id"], $PROCESSED["version_id"]) = Controllers_VersionSelect::processVersionSelect();
        $PROCESSED["linked_objectives"] = Controllers_LinkedObjectives::processLinkedObjectives();

        if (!$ERROR) {
            $unit->fromArray(array(
                "unit_code" => $PROCESSED["unit_code"],
                "unit_title" => $PROCESSED["unit_title"],
                "unit_description" => $PROCESSED["unit_description"],
                "cperiod_id" => $PROCESSED["cperiod_id"],
                "week_id" => $PROCESSED["week_id"],
                "unit_order" => $PROCESSED["unit_order"],
                "updated_date" => time(),
                "updated_by" => $ENTRADA_USER->getID(),
            ));
            if ($unit->getID()) {
                $result = $unit->update();
            } else {
                $result = $unit->insert();
            }
            if ($result) {
                try {
                    $unit->updateAssociatedFaculty($PROCESSED["associated_faculty"]);
                } catch (Exception $e) {
                    add_error("Unable to save associated faculty.");
                    application_log("error", "Unable to update associated faculty in course unit ".$unit->getID().": ".$e->getMessage());
                    $result = false;
                }
            }
            if ($result) {
                try {
                    $unit->updateObjectives($PROCESSED["objectives"]);
                } catch (Exception $e) {
                    add_error("Unable to save unit objectives.");
                    application_log("error", "Unable to update objectives in course unit ".$unit->getID().": ".$e->getMessage());
                    $result = false;
                }
            }
            if ($result) {
                try {
                    $unit->updateLinkedObjectives($PROCESSED["objectives"], $PROCESSED["linked_objectives"], $PROCESSED["version_id"]);
                } catch (Exception $e) {
                    add_error("Unable to save linked objectives.");
                    application_log("error", "Unable to update linked objectives in course unit ".$unit->getID().": ".$e->getMessage());
                    $result = false;
                }
            }
            $url = ENTRADA_URL."/admin/courses/units?id=".$unit->getCourseID();
            $ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";
            $msg = "You will now be redirected to the units index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
            if ($result) {
                add_success("Successfully saved course unit. ".$msg);
                application_log("success", "Saved course unit '".$unit->getUnitTitle()."' [".$unit->getID()."] for course [".$unit->getCourseID()."] added to the system.");
                $saved = true;
            } else {
                add_error("Unable to save course unit. ".$msg);
                application_log("error", "Could not save course unit '".$unit->getUnitTitle()."' [".$unit->getID()."] for course [".$unit->getCourseID()."] to the system.");
                $saved = false;
            }
        } else {
            $saved = false;
        }

        if ($NOTICE) {
            echo display_notice();
        }
        if ($ERROR) {
            echo display_error();
        }
        if ($SUCCESS) {
            echo display_success();
        }

        return array($saved, $PROCESSED);
    }

    public static function getFacultyList() {
        global $db;
        $query = "
            SELECT a.`id` AS `proxy_id`, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, a.`organisation_id`
            FROM `".AUTH_DATABASE."`.`user_data` AS a
            LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
            ON b.`user_id` = a.`id`
            WHERE b.`app_id` = '".AUTH_APP_ID."'
            AND b.`group` = 'faculty'
            ORDER BY a.`lastname` ASC, a.`firstname` ASC";
        $results = $db->GetAll($query);
        if ($results === false) {
            throw new Exception($db->ErrorMsg());
        } else {
            $faculty = array();
            foreach($results as $result) {
                $faculty[$result["proxy_id"]] = array(
                    "proxy_id" => $result["proxy_id"],
                    "fullname" => $result["fullname"],
                    "organisation_id" => $result["organisation_id"]
                );
            }
            return $faculty;
        }
    }

    public static function processObjectives() {
        if (isset($_POST["unit_tag"]) && is_array($_POST["unit_tag"])) {
            $objective_ids = array_filter(array_map(function ($unit_tag_id) { return (int) $unit_tag_id; }, $_POST["unit_tag"]));
            $objective_repository = Models_Repository_Objectives::getInstance();
            return $objective_repository->fetchAllByIDs($objective_ids);
        } else {
            return array();
        }
    }

}
