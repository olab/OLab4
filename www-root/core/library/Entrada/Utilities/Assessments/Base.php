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
 * This base class is for assessment-related utility functionality.
 *
 * @author Organisation: Queen's University
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */
class Entrada_Utilities_Assessments_Base extends Entrada_Assessments_Base {

    public function __construct($arr = array()) {
        parent::__construct($arr);
    }

    public function getAssessmentPreferences($module) {
        global $db, $ENTRADA_USER;
        $query	= "SELECT `preferences` FROM `".AUTH_DATABASE."`.`user_preferences` WHERE `app_id`=".$db->qstr(AUTH_APP_ID)." AND `proxy_id`=".$db->qstr($ENTRADA_USER->getID())." AND `module`=".$db->qstr($module);
        $result	= $db->GetRow($query);
        if($result) {
            if($result["preferences"]) {
                $preferences = @unserialize($result["preferences"]);
                if(@is_array($preferences)) {
                    $_SESSION[APPLICATION_IDENTIFIER][$module] = $preferences;
                }
            }
        }
        return ((isset($_SESSION[APPLICATION_IDENTIFIER][$module])) ? $_SESSION[APPLICATION_IDENTIFIER][$module] : array());
    }

    function updateAssessmentPreferences($module) {
        global $db, $ENTRADA_USER;
        if(isset($_SESSION[APPLICATION_IDENTIFIER][$module])) {
            $query	= "SELECT `preference_id` FROM `".AUTH_DATABASE."`.`user_preferences` WHERE `app_id`=".$db->qstr(AUTH_APP_ID)." AND `proxy_id`=".$db->qstr($ENTRADA_USER->getID())." AND `module`=".$db->qstr($module);
            $result	= $db->GetRow($query);
            if($result) {
                if(!$db->AutoExecute("`".AUTH_DATABASE."`.`user_preferences`", array("preferences" => @serialize($_SESSION[APPLICATION_IDENTIFIER][$module]), "updated" => time()), "UPDATE", "preference_id = ".$db->qstr($result["preference_id"]))) {
                    application_log("error", "Unable to update the users database preferences for this module. Database said: ".$db->ErrorMsg());
                    return false;
                }
            } else {
                if(!$db->AutoExecute(AUTH_DATABASE.".user_preferences", array("app_id" => AUTH_APP_ID, "proxy_id" => $ENTRADA_USER->getID(), "module" => $module, "preferences" => @serialize($_SESSION[APPLICATION_IDENTIFIER][$module]), "updated" => time()), "INSERT")) {
                    application_log("error", "Unable to insert the users database preferences for this module. Database said: ".$db->ErrorMsg());
                    return false;
                }
            }
        }
        return true;
    }
}