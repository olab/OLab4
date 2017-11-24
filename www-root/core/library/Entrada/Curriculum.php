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
 * A class to handle general curriculum concepts and details
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Entrada_Curriculum {
    
    public static function getObjectiveSets() {
        global $db, $ENTRADA_USER;
        
        $query = "SELECT a.*, c.`audience_value` FROM `global_lu_objectives` AS a
                        LEFT JOIN `objective_organisation` AS b
                        ON a.`objective_id` = b.`objective_id`
                        LEFT JOIN `objective_audience` AS c
                        ON a.`objective_id` = c.`objective_id`
                        WHERE a.`objective_parent` = '0'
                        AND a.`objective_active` = '1'
                        AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
                        GROUP BY a.`objective_id`
                        ORDER BY a.`objective_order` ASC";
        return $db->GetAssoc($query);
    }
    
    public static function getCohorts() {
        global $db, $ENTRADA_USER;
        
        $query = "SELECT a.*
										FROM `groups` AS a
										JOIN `group_organisations` AS b
										ON a.`group_id` = b.`group_id`
										WHERE b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
										AND a.`group_active` = 1
										ORDER BY `group_name` DESC";
        return $db->GetAll($query);
    }
    
    public static function getCourses() {
        global $db, $ENTRADA_USER;
        
        $query = "SELECT * FROM `courses`
                    WHERE `organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
                    AND `course_active` = '1'
                    ORDER BY `course_code` ASC";
        return $db->GetAll($query);
    }
    
}
