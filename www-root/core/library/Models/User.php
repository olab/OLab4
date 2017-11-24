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
 * A model for handling user data records.
 *
 * @author Organisation: Queen's University
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 */

class Models_User extends Models_Base {
    const TABLE_NAME = "user_data";
    protected
        $id,
        $number,
        $username,
        $password,
        $salt,
        $organisation_id,
        $department,
        $prefix,
        $suffix_gen,
        $suffix_post_nominal,
        $firstname,
        $lastname,
        $email,
        $email_alt,
        $email_updated,
        $google_id,
        $telephone,
        $fax,
        $address,
        $city,
        $province,
        $postcode,
        $country,
        $country_id,
        $province_id,
        $notes,
        $office_hours,
        $privacy_level,
        $copyright,
        $notifications,
        $test_account,
        $entry_year,
        $grad_year,
        $gender,
        $clinical,
        $uuid,
        $updated_date,
        $updated_by;

    protected static $database_name = AUTH_DATABASE;
    protected static $table_name = "user_data";
    protected static $primary_key = "id";
    protected static $default_sort_column = "lastname";



    /**
     * lookup array for formatting user information
     * <code>
     * $format_keys = array(
     *								"f" => "firstname",
     *								"l" => "lastname",
     *								"p" => "prefix"
     *								);
     *
     * //Usage:
     * if ($user->getPrefix()) {
     *   echo $user->getName("%p. %f %l"); //i.e. Dr. John Smith
     * } else {
     *   echo $user->getName("%f %l"); //i.e. John Smith
     * }
     * </code>
     * @var array
     */
    private static $format_keys = array(
        "f" => "firstname",
        "l" => "lastname",
        "p" => "prefix"
    );

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->id;
    }

   /**
	 * Returns the id of the user
	 * @return int
	 */
	public function getProxyId() {
		return $this->id;
	}

    public function getNumber() {
        return $this->number;
    }

    public function getUsername() {
        return $this->username;
    }

    public function getPassword() {
        return $this->password;
    }

    public function getSalt() {
        return $this->salt;
    }

    public function getOrganisationID() {
        return $this->organisation_id;
    }

    public function getDepartment() {
        return $this->department;
    }

    public function getPrefix() {
        return $this->prefix;
    }

    public function getFirstname() {
        return $this->firstname;
    }

    public function getLastname() {
        return $this->lastname;
    }

    /**
     * Returns the Last and First names formatted as "lastname, firstname"
     * @return string
     */
    public function getFullname($reverse = true) {
        if ($reverse) {
            return $this->getName("%l, %f");
        } else {
            return $this->getName("%f %l");
        }
    }

    /**
     * Returns the user's name formatted according to the format string supplied. Default format is "%f %l" (firstname, lastname)
     * <code>
     * if ($user->getPrefix()) {
     *   echo $user->getName("%p. %f %l"); //i.e. Dr. John Smith
     * } else {
     *   echo $user->getName("%f %l"); //i.e. John Smith
     * }
     * </code>
     * @see User::$format_keys
     * @param string $format
     * @return string
     */
    public function getName($format = "%f %l") {
        foreach (self::$format_keys as $key => $var) {
            $pattern = "/([^%])%".$key."|^%".$key."|(%%)%".$key."/";
            $format = preg_replace($pattern, "$1$2".$this->{$var}, $format);
        }

        $format = preg_replace("/%%/", "%", $format);

        return $format;
    }

    public function getEmail() {
        return $this->email;
    }

    public function getEmailAlt() {
        return $this->email_alt;
    }

    public function getEmailUpdated() {
        return $this->email_updated;
    }

    public function getGoogleID() {
        return $this->google_id;
    }

    public function getTelephone() {
        return $this->telephone;
    }

    public function getFax() {
        return $this->fax;
    }

    public function getAddress() {
        return $this->address;
    }

    public function getCity() {
        return $this->city;
    }

    public function getProvince() {
        return $this->province;
    }

    public function getPostcode() {
        return $this->postcode;
    }

    public function getCountry() {
        return $this->country;
    }

    public function getCountryID() {
        return $this->country_id;
    }

    public function getProvinceID() {
        return $this->province_id;
    }

    public function getNotes() {
        return $this->notes;
    }

    public function getOfficeHours() {
        return $this->office_hours;
    }

    public function getPrivacyLevel() {
        return $this->privacy_level;
    }

    public function getCopyright() {
        return $this->copyright;
    }

    public function getNotifications() {
        return $this->notifications;
    }

    public function getEntryYear() {
        return $this->entry_year;
    }

    public function getGradYear() {
        return $this->grad_year;
    }

    public function getGender() {
        return $this->gender;
    }

    public function getClinical() {
        return $this->clinical;
    }

    public function getUuid() {
        return $this->uuid;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    /* @return bool|Models_User */
    public static function fetchRowByID($id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "id", "value" => $id, "method" => "=")
        ));
    }

    /* @return bool|Models_User */
    public static function fetchRowByNumber($number) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "number", "value" => $number, "method" => "=")
        ));
    }

    /* @return bool|Models_User */
    public static function fetchRowByEmail($email) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "email", "value" => $email, "method" => "=")
        ));
    }

    /* @return bool|Models_User */
    public static function fetchRowByUsername($username) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "username", "value" => $username, "method" => "=")
        ));
    }

    /* @return ArrayObject|Models_User[] */
    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "id", "value" => 0, "method" => ">=")));
    }

    /* @return ArrayObject|Models_User[] */
    public static function fetchAllByCGroupIDSearchTerm($cgroup_id, $search_term = NULL) {
        global $db;

        $output = array();

        if (!$search_term || !trim($search_term)) {
            $search_term = NULL;
        }

        $constraints = array($cgroup_id);

        $query = "SELECT a.* FROM `".static::$database_name."`.`".static::$table_name."` AS a
                    JOIN `course_group_audience` AS b
                    ON a.`id` = b.`proxy_id`
                    JOIN `course_groups` AS c
                    ON b.`cgroup_id` = c.`cgroup_id`
                    JOIN `courses` AS d
                    ON c.`course_id` = d.`course_id`
                    WHERE b.`active` = 1
                    AND b.`cgroup_id` = ?";
        if ($search_term) {
            $query .= "\n AND (CONCAT(a.`firstname`, ' ', a.`lastname`) LIKE ? OR a.`email` LIKE ? OR d.`course_name` LIKE ? OR d.`course_code` LIKE ?)";
            $constraints[] = "%".$search_term."%";
            $constraints[] = "%".$search_term."%";
            $constraints[] = "%".$search_term."%";
            $constraints[] = "%".$search_term."%";
        }
        $query .= "\n GROUP BY a.`id`";
        $users = $db->getAll($query, $constraints);
        if ($users) {
            foreach ($users as $user) {
                $output[] = new self($user);
            }
        }

        return $output;
    }

    public function getDirectors($organisation_id) {
        global $db;

        $query	= "SELECT a.`id` AS `proxy_id`, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, c.`organisation_id`
						FROM `".static::$database_name."`.`".static::$table_name."` AS a
						LEFT JOIN `".AUTH_DATABASE."`.`user_access` as b
						ON b.`user_id` = a.`id`
						LEFT JOIN `".AUTH_DATABASE."`.`organisations` as c
						ON b.`organisation_id` = c.`organisation_id`
						WHERE b.`group` = 'faculty'
						AND (b.`role` = 'director' OR b.`role` = 'admin')
						AND b.`app_id` = '".AUTH_APP_ID."'
						AND b.`account_active` = 'true'
						AND b.`organisation_id` = ?
						ORDER BY `fullname` ASC";

        $results = ((USE_CACHE) ? $db->CacheGetAll(AUTH_CACHE_TIMEOUT, $query, array($organisation_id)) : $db->GetAll($query, array($organisation_id)));

        if ($results) {
            return $results;
        }

        return false;
    }

    public function getCurriculumCoordinators() {
        global $db;

        $query = "SELECT a.`id` AS `proxy_id`, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, c.`organisation_id`
						FROM `".static::$database_name."`.`".static::$table_name."` AS a
						LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
						ON b.`user_id` = a.`id`
						LEFT JOIN `".AUTH_DATABASE."`.`organisations` AS c
						ON a.`organisation_id` = c.`organisation_id`
						WHERE b.`group` = 'staff'
						AND b.`role` = 'admin'
						AND b.`app_id` = '".AUTH_APP_ID."'
						AND b.`account_active` = 'true'
						ORDER BY `fullname` ASC";

        $results = ((USE_CACHE) ? $db->CacheGetAll(AUTH_CACHE_TIMEOUT, $query) : $db->GetAll($query));

        if ($results) {
            return $results;
        }

        return false;
    }

    public function getFaculties() {
        global $db;

        $query = "SELECT a.`id` AS `proxy_id`, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, c.`organisation_id`
							FROM `".static::$database_name."`.`".static::$table_name."` AS a
							LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
							ON b.`user_id` = a.`id`
							LEFT JOIN `".AUTH_DATABASE."`.`organisations` AS c
							ON a.`organisation_id` = c.`organisation_id`
							WHERE (b.`group` = 'faculty' OR (b.`group` = 'resident' AND b.`role` = 'lecturer'))
							AND b.`app_id` = '".AUTH_APP_ID."'
							AND b.`account_active` = 'true'
							ORDER BY `fullname` ASC";

        $results = ((USE_CACHE) ? $db->CacheGetAll(AUTH_CACHE_TIMEOUT, $query) : $db->GetAll($query));

        if ($results) {
            return $results;
        }

        return false;
    }

    public function getProgramCoordinators($organisation_id) {
        global $db;

        $query = "SELECT a.`id` AS `proxy_id`, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, a.`id`, c.`organisation_id`
						FROM `".static::$database_name."`.`".static::$table_name."` AS a
						LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
						ON b.`user_id` = a.`id`
						LEFT JOIN `".AUTH_DATABASE."`.`organisations` AS c
						ON b.`organisation_id` = c.`organisation_id`
						WHERE b.`role` = 'pcoordinator'
						AND b.`app_id` = '".AUTH_APP_ID."'
						AND b.`account_active` = 'true'
						AND b.`organisation_id` = ?
						ORDER BY `fullname` ASC";

        $results = ((USE_CACHE) ? $db->CacheGetAll(AUTH_CACHE_TIMEOUT, $query, array($organisation_id)) : $db->GetAll($query, array($organisation_id)));

        if ($results) {
            return $results;
        }

        return false;
    }

    public function getEvaluationReps() {
        global $db;

        $query = "SELECT a.`id` AS `proxy_id`, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, a.`id`, c.`organisation_id`
						FROM `".static::$database_name."`.`".static::$table_name."` AS a
						LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
						ON b.`user_id` = a.`id`
						LEFT JOIN `".AUTH_DATABASE."`.`organisations` AS c
						ON a.`organisation_id` = c.`organisation_id`
						WHERE b.`group` = 'faculty'
						AND b.`app_id` = '".AUTH_APP_ID."'
						AND b.`account_active` = 'true'
						ORDER BY `fullname` ASC";

        $results = ((USE_CACHE) ? $db->CacheGetAll(AUTH_CACHE_TIMEOUT, $query) : $db->GetAll($query));

        if ($results) {
            return $results;
        }

        return false;
    }

    public function getStudentReps() {
        global $db;

        $query = "SELECT a.`id` AS `proxy_id`, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, a.`id`, c.`organisation_id`
						FROM `".static::$database_name."`.`".static::$table_name."` AS a
						LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
						ON b.`user_id` = a.`id`
						LEFT JOIN `".AUTH_DATABASE."`.`organisations` AS c
						ON a.`organisation_id` = c.`organisation_id`
						WHERE b.`group` = 'student'
						AND b.`app_id` = '".AUTH_APP_ID."'
						AND b.`account_active` = 'true'
						ORDER BY `fullname` ASC";

        $results = ((USE_CACHE) ? $db->CacheGetAll(AUTH_CACHE_TIMEOUT, $query) : $db->GetAll($query));

        if ($results) {
            return $results;
        }

        return false;
    }

    public function getStudents() {
        global $db;

        $query = "	SELECT a.`id` AS `proxy_id`, b.`role`, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, a.`organisation_id`
								FROM `".static::$database_name."`.`".static::$table_name."` AS a
								LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
								ON a.`id` = b.`user_id`
								WHERE b.`app_id` = ".AUTH_APP_ID."
								AND b.`account_active` = 'true'
								AND (b.`access_starts` = '0' OR b.`access_starts` <= ".time().")
								AND (b.`access_expires` = '0' OR b.`access_expires` > ".time().")
								AND b.`group` = 'student'
								AND b.`role` >= '".(date("Y") - ((date("m") < 7) ?  2 : 1))."'
								ORDER BY b.`role` ASC, a.`lastname` ASC, a.`firstname` ASC";

        $results = $db->GetAll($query);

        if ($results) {
            return $results;
        }

        return false;
    }
    
    /**
     * Takes in an optional search value
     * Gets all organisations for a valid user applying
     * an optional filter on the organisation title
     * Returns a key value array or results
     * @param string $search_value
     * @return array|bool
     */
    
    public function getOrganisations($search_value = "") {
        global $db;
        global $ENTRADA_USER;

        $output = false;

        $query = "  SELECT c.`organisation_id`, c.`organisation_title` FROM `" . static::$database_name . "`.`" . static::$table_name . "` AS a
                    JOIN `" . AUTH_DATABASE . "`.`user_access` AS b
                    ON a.`id` = b.`user_id`
                    JOIN `" . AUTH_DATABASE . "`.`organisations` AS c
                    ON b.organisation_id = c.`organisation_id`
                    WHERE b.`app_id` = ". $db->qstr(AUTH_APP_ID) ."
                    AND b.`account_active` = 'true'
                    AND (b.`access_starts` = '0' OR b.`access_starts` <= ". $db->qstr(time()) .")
                    AND (b.`access_expires` = '0' OR b.`access_expires` > ". $db->qstr(time()) .")
                    AND b.`user_id` = ". $db->qstr($ENTRADA_USER->getActiveId()) ."";


        if ($search_value != null) {
            $query .= " AND c.`organisation_title` LIKE (" . $db->qstr("%" . $search_value . "%") . ")";
        }
        
        $query .= "GROUP BY c.`organisation_id`";

        $results = $db->GetAll($query);
        if ($results) {
            foreach ($results as $result) {
                $output[] = $result;
            }
        }

        return $output;
    }

    public function getTutors() {
        global $db;

        $query = "SELECT a.`id` AS `proxy_id`, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, a.`organisation_id`
                  FROM `".AUTH_DATABASE."`.`user_data` AS a
                  LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
                  ON b.`user_id` = a.`id`
                  WHERE b.`app_id` = '".AUTH_APP_ID."'
                  AND (b.`group` = 'faculty' OR (b.`group` = 'resident' AND b.`role` = 'lecturer') OR b.`group` = 'staff' OR b.`group` = 'medtech')
                  ORDER BY a.`lastname` ASC, a.`firstname` ASC";

        $results = $db->GetAll($query);
        if ($results) {
            return $results;
        }

        return false;
    }

    public static function getAssistants($user_id) {
        global $db;

        $query = "SELECT a.`permission_id`, a.`assigned_to`, a.`valid_from`, a.`valid_until`, CONCAT_WS(', ', b.`lastname`, b.`firstname`) AS `fullname`
									FROM `permissions` AS a
									LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
									ON b.`id` = a.`assigned_to`
									WHERE a.`assigned_by`= ?
									ORDER BY `valid_until` ASC";
        $results = $db->GetAll($query, array($user_id));

        if ($results) {
            return $results;
        }

        return false;
    }

    public function getCourseAudienceByOrganisationID($organisation_id = null) {
        global $db;

        $query	= "	SELECT a.`id` AS `proxy_id`, CONCAT_WS(' ', a.`firstname`, a.`lastname`) AS `fullname`, a.`username`, a.`organisation_id`, b.`group`, b.`role`
                            FROM `".AUTH_DATABASE."`.`user_data` AS a
                            JOIN `".AUTH_DATABASE."`.`user_access` AS b
                            ON a.`id` = b.`user_id`
                            WHERE b.`app_id` IN (?)
                            AND b.`account_active` = 'true'
                            AND (b.`access_starts` = '0' OR b.`access_starts` <= ?)
                            AND (b.`access_expires` = '0' OR b.`access_expires` > ?)
                            AND b.`organisation_id` = ?
                            GROUP BY a.`id`
                            ORDER BY a.`lastname` ASC, a.`firstname` ASC";


        $course_audience = $db->GetAll($query, array(AUTH_APP_IDS_STRING, time(), time(), $organisation_id));

        if ($course_audience) {
            return $course_audience;
        }
        return false;
    }

    public static function getUserByIDAndPass ($user_id, $password) {
        global $db;

        $query = "SELECT * FROM `".AUTH_DATABASE."`.`user_data` 
                    WHERE `id` = ? 
                    AND ((`salt` IS NULL AND `password` = MD5(?)) 
                    OR (`salt` IS NOT NULL AND `password` = SHA1(CONCAT(? , `salt`))))";
        $results = $db->GetRow($query, array($user_id, $password, $password));

        if ($results) {
            return new self($results);
        }

        return false;
    }

    public static function deleteAsistants($permision_id, $user_id) {
        global $db;

        $query = "DELETE FROM `permissions` WHERE `permission_id`= ? AND `assigned_by`= ? ";

        $result = $db->Execute($query, array($permision_id, $user_id));

        if ($result) {
            return $result;
        }

        return false;
    }

    public static function getUserByIDAndGroup ($user_id, $group = "student") {
        global $db;

        $query	= "SELECT a.`id` AS `proxy_id`, CONCAT_WS(' ', a.`firstname`, a.`lastname`) AS `fullname`
						FROM `".AUTH_DATABASE."`.`user_data` AS a
						LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
						ON b.`user_id` = a.`id` AND b.`app_id`='1' AND b.`account_active`='true' AND b.`group`<> ?
						WHERE a.`id`= ? ";

        $results = $db->GetRow($query, array($group, $user_id));

        if ($results) {
            return $results;
        }

        return false;
    }
}