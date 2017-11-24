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
 * A model for handeling a course audience
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 */

class Models_Organisation extends Models_Base {
    protected $organisation_id, 
              $organisation_title,
              $organisation_address1,
              $organisation_address2,
              $organisation_city,
              $organisation_province,
              $organisation_country,
              $organisation_postcode,
              $organisation_telephone,
              $organisation_fax,
              $organisation_email,
              $organisation_url,
              $organisation_twitter,
              $organisation_hashtags,
              $organisation_installation,
              $organisation_desc,
              $template,
              $aamc_institution_id,
              $aamc_institution_name,
              $aamc_program_id,
              $aamc_program_name,
              $organisation_active,
              $app_id;

    protected static $primary_key = "organisation_id";
    protected static $table_name = "organisations";
    protected static $default_sort_column = "organisation_title";
    protected static $database_name = AUTH_DATABASE;

    public function getID () {
        return $this->organisation_id;
    }
    
    public function getOrganisationTitle () {
        return $this->organisation_title;
    }
    
    public function getOrganisationAddress1 () {
        return $this->organisation_address1;
    }
    
    public function getOrganisationAddress2 () {
        return $this->organisation_address2;
    }
    
    public function getOrganisationCity () {
        return $this->organisation_city;
    }
    
    public function getOrganisationProvince () {
        return $this->organisation_province;
    }
    
    public function getOrganisationCountry () {
        return $this->organisation_country;
    }
    
    public function getOrganisationPostalCode () {
        return $this->organisation_postcode;
    }
    
    public function getOrganisationTelephone () {
        return $this->organisation_telephone;
    }
    
    public function getOrganisationFax () {
        return $this->organisation_fax;
    }
    
    public function getOrganisationEmail () {
        return $this->organisation_email;
    }
    
    public function getOrganisationUrl () {
        return $this->organisation_url;
    }

    public function getOrganisationTwitterHandle () {
        return $this->organisation_twitter;
    }

    public function getOrganisationTwitterHashtags () {
        return $this->organisation_hashtags;
    }

    public function getOrganisationInstallation () {
        return $this->organisation_installation;
    }
    
    public function getOrganisationDesc () {
        return $this->organisation_desc;
    }
    
    public function getTemplate () {
        return $this->template;
    }
    
    public function getAamcInstitutionID () {
        return $this->aamc_institution_id;
    }
    
    public function getAamcInstitutionName () {
        return $this->aamc_institution_name;
    }
    
    public function getAamcProgramID () {
        return $this->aamc_program_id;
    }
    
    public function getAamcProgramName () {
        return $this->aamc_program_name;
    }
    
    public function getActive () {
        return $this->organisation_active;
    }

    public function getTwitterHandle () {
        return $this->organisation_twitter;
    }

    public function getTwitterHashTags () {
        return $this->organisation_twitter;
    }
    
    public function getAppID () {
        return $this->app_id;
    }

    public static function organisationUsersToSearchable ($users = array()) {
        $searchable_users = array();
        if ($users) {
            foreach ($users as $user) {
                $searchable_users[] = array("target_id" => $user["proxy_id"], "target_label" => $user["firstname"] . " " . $user["lastname"]);
            }
        }
        return json_encode($searchable_users);
    }

    public static function fetchRowByID($organisation_id) {
        $self = new self();

        return $self->fetchRow(array("organisation_id" => $organisation_id));
    }

    public static function fetchAllOrganisations($search_term = null) {
        global $db;

        $query = "SELECT a.*
                    FROM `".AUTH_DATABASE."`.`organisations` AS a" .
                    (isset($search_term) && $search_term ? " WHERE a.`organisation_title` LIKE " . $db->qstr("%" . $search_term . "%") : "") . "
                    ORDER BY a.`organisation_title` ASC";

        $results = $db->GetAll($query);

        return $results ? $results : false;
    }

    public static function fetchOrganisationUsers($search_term = null, $organisation_id = null, $group = null, $limit = null, $offset = null) {
        global $db;

        $groups_string = "";
        if (is_array($group)) {
            foreach ($group as $group_name) {
                $groups_string .= ($groups_string ? ", " : "").$db->qstr($group_name);
            }
        }

        $query = "	SELECT a.`id` AS `proxy_id`, a.`firstname`, a.`lastname`, b.`group`, b.`role`, a.`email`
                    FROM `".AUTH_DATABASE."`.`user_data` AS a
                    LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
                    ON a.`id` = b.`user_id`
                    WHERE b.`app_id` IN (".AUTH_APP_IDS_STRING.")
                    AND b.`account_active` = 'true'
                    AND (b.`access_starts` = '0' OR b.`access_starts` <= ?)
                    AND (b.`access_expires` = '0' OR b.`access_expires` > ?)
                    AND b.`organisation_id` = ?
                    AND (
                            CONCAT(a.`firstname`, ' ' , a.`lastname`) LIKE ".$db->qstr("%".$search_term."%")." OR
                            CONCAT(a.`lastname`, ' ' , a.`firstname`) LIKE ".$db->qstr("%".$search_term."%")." OR
                            a.email LIKE ".$db->qstr("%".$search_term."%")."
                        )
                    ".(isset($groups_string) && $groups_string ? "AND b.`group` IN (".$groups_string.")" : (isset($group) && $group ? "AND b.`group` = ?" : ""))."
                    GROUP BY a.`id`
                    ORDER BY a.`firstname` ASC, a.`lastname` ASC";


        if (!empty($limit)) {
            $query .= " LIMIT " . $limit;
        }

        if (!empty($offset)) {
            $query .= " OFFSET " . $offset;
        }


        $results = $db->GetAll($query, ($groups_string ? array(time(), time(), $organisation_id) : ($group ? array(time(), time(), $organisation_id, $group) : array(time(), time(), $organisation_id))));
        return $results;
    }

    public static function fetchOrganisationUsersWithoutAppID($search_term = null, $organisation_id = null, $group = null, $excluded_ids = 0) {
        global $db;

        $groups_string = "";
        if (is_array($group)) {
            foreach ($group as $group_name) {
                $groups_string .= ($groups_string ? ", " : "").$db->qstr($group_name);
            }
        }

        $query = "	SELECT a.`id` AS `proxy_id`, a.`firstname`, a.`lastname`, b.`group`, b.`role`, a.`email`
                    FROM `".AUTH_DATABASE."`.`user_data` AS a
                    LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
                    ON a.`id` = b.`user_id`
                    WHERE a.`id` NOT IN (".$excluded_ids.")
                    AND b.`account_active` = 'true'
                    AND (b.`access_starts` = '0' OR b.`access_starts` <= ?)
                    AND (b.`access_expires` = '0' OR b.`access_expires` > ?)
                    AND b.`organisation_id` = ?
                    AND (
                            CONCAT(a.`firstname`, ' ' , a.`lastname`) LIKE ".$db->qstr("%".$search_term."%")." OR
                            CONCAT(a.`lastname`, ' ' , a.`firstname`) LIKE ".$db->qstr("%".$search_term."%")." OR
                            a.email LIKE ".$db->qstr("%".$search_term."%")."
                        )
                    ".(isset($groups_string) && $groups_string ? "AND b.`group` IN (".$groups_string.")" : (isset($group) && $group ? "AND b.`group` = ?" : ""))."
                    GROUP BY a.`id`
                    ORDER BY a.`firstname` ASC, a.`lastname` ASC";

        $results = $db->GetAll($query, ($groups_string ? array(time(), time(), $organisation_id) : ($group ? array(time(), time(), $organisation_id, $group) : array(time(), time(), $organisation_id))));
        return $results;
    }

    public static function fetchOrganisationUsersByGroup ($organisation_id = null, $group = null) {
        global $db;

        $query = "	SELECT a.`id` AS `proxy_id`, a.`firstname`, a.`lastname`, b.`group`, b.`role`, a.`email`
                    FROM `".AUTH_DATABASE."`.`user_data` AS a
                    LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
                    ON a.`id` = b.`user_id`
                    WHERE b.`account_active` = 'true'
                    AND (b.`access_starts` = '0' OR b.`access_starts` <= ?)
                    AND (b.`access_expires` = '0' OR b.`access_expires` > ?)
                    AND b.`organisation_id` = ?
                    AND (b.`group` = 'faculty' OR b.`group` = 'staff')
                    GROUP BY a.`id`
                    ORDER BY a.`firstname` ASC, a.`lastname` ASC";

        $results = $db->GetAll($query, array(time(), time(), $organisation_id, $group));
        return $results;
    }

    public static function fetchOrganisationsWithDepartments() {
        global $db;

        $query = "SELECT DISTINCT(a.`organisation_id`), b.`organisation_title`
                    FROM `".AUTH_DATABASE."`.`departments` AS a
                    INNER JOIN `".AUTH_DATABASE."`.`organisations` AS b
                    ON a.`organisation_id` = b.`organisation_id`
                    WHERE a.`department_active` = '1'
                    AND b.`organisation_active` = '1'
                    ORDER BY b.`organisation_title`";
        
        $results = $db->GetAll($query);

        return $results;
    }
}
