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
 * Sync's LDAP server with class_list in groups table.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Brandon Thorn <bt37@queensu.ca>
 * @author Developer: Ryan Warner <ryan.warner@queensu.ca>
 * @copyright Copyright 2011 Queen's University. All Rights Reserved.
 * 
*/

@set_time_limit(0);
@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    dirname(__FILE__) . "/../core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

$org_blacklist = array (1);

$query = "SELECT a.`course_id`, a.`course_code`
            FROM `courses` AS a
            JOIN `curriculum_periods` AS b
            ON a.`curriculum_type_id` = b.`curriculum_type_id`
            WHERE a.`sync_ldap` = '1'
            AND a.`organisation_id` NOT IN(".implode(",", $org_blacklist).")
            AND a.`course_active` = '1'
            AND b.`active` = '1'
            AND UNIX_TIMESTAMP(NOW()) > b.`start_date` - 1209600 
            AND UNIX_TIMESTAMP(NOW()) < b.`finish_date`
            GROUP BY a.`course_id`";
$results = $db->GetAll($query);
if ($results) {
    foreach ($results as $result) {
        application_log("notice", "LDAP sync for [".$course["course_code"]."] start");
        $sync = new Entrada_Sync_Course_Ldap($result["course_id"]);
        application_log("notice", "LDAP sync for [".$course["course_code"]."] finish");
    }
}