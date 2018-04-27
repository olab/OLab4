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
        $prefix = "",
        $suffix_gen = "",
        $suffix_post_nominal = "",
        $firstname,
        $lastname,
        $email,
        $email_alt = "",
        $email_updated,
        $google_id,
        $telephone = "",
        $fax = "",
        $address = "",
        $city = "",
        $province = "",
        $postcode = "",
        $country = "",
        $country_id,
        $province_id,
        $notes = "",
        $office_hours,
        $privacy_level = 0,
        $copyright = 0,
        $notifications = 1,
        $test_account,
        $admin_access,
        $entry_year,
        $grad_year,
        $gender,
        $clinical = 0,
        $uuid,
        $pin,
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

	/**
	 * Returns the User's specified suffix_gen, if any. e.g. Jr., Sr., III...
	 * @return string
	 */
	public function getSuffixGen() {
		return $this->suffix_gen;
	}

	/**
	 * Returns the User's specified suffix_post_nominal, if any. e.g. PhD, MD PA...
	 * @return string
	 */
	public function getSuffixPostNominal() {
        $suffixes_post_nominal_array = explode(',', $this->suffix_post_nominal);
        if (is_array($suffixes_post_nominal_array) && !empty($suffixes_post_nominal_array)) {
            $export = implode(', ', $suffixes_post_nominal_array);
        } else {
            $export = $this->suffix_post_nominal;
        }
		return $export;
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
	 * Returns the Last and First names formatted as "lastname, firstname"
	 * @return string
	 */
	public function getLongFullName() {
        $suffix_post_nominal = User::getSuffixPostNominal();
        $suffix_gen = User::getSuffixGen();
        $export = ($this->prefix ? $this->prefix . " " : "") . $this->getName("%f %l") . ($suffix_gen ? " " . $suffix_gen : "") . ($suffix_post_nominal ? ", " . $suffix_post_nominal : "");
        return $export;
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

    public function getPin() {
        return $this->pin;
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
    public static function fetchAllByCGroupIDSearchTerm($cgroup_id, $search_term = NULL, $limit = NULL, $offset = NULL) {
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

        if (!empty($limit)) {
            $query .= " LIMIT " . $limit;
        }
        if (!empty($offset)) {
            $query .= " OFFSET " . $offset;
        }

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
						WHERE b.`group` = 'faculty' ";

        $settings = new Entrada_Settings();

        if ($settings->read("personnel_api_director_show_all_faculty") != '1') {
            $query .= "AND (b.`role` = 'director' OR b.`role` = 'admin') ";
        }

        $query .= "AND b.`app_id` = '".AUTH_APP_ID."'
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
						WHERE b.`app_id` = '".AUTH_APP_ID."' ";

        $settings = new Entrada_Settings();

        if ($settings->read("personnel_api_curriculum_coord_show_all_faculty") != '1') {
            $query .= "AND (b.`group` = 'faculty' OR (b.`group` = 'staff' AND b.`role` = 'admin')) ";
        }

        $query .= "AND b.`account_active` = 'true'
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

    public static function getUserByIDandPIN($user_id, $pin) {
        global $db;
        $query	= "SELECT *
				   FROM `".AUTH_DATABASE."`.`user_data` AS a
				   WHERE a.`id`= ? AND pin = ?";
        $user = $db->GetRow($query, array($user_id, $pin));
        if ($user) {
            return new self($user);
        } else {
            return false;
        }
    }

    public static function fetchAllResidentsWithLeave($search_term = null, $excluded_ids = 0, $limit = null, $offset = null, $start_date, $end_date) {
        global $db;

        $query = "	SELECT a.`id` AS `proxy_id`, a.`firstname`, a.`lastname`, b.`group`, b.`role`, a.`email`
                    FROM `".AUTH_DATABASE."`.`user_data` AS a
                    LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
                    ON a.`id` = b.`user_id`
                    INNER JOIN `cbl_leave_tracking` AS c
					ON a.`id` = c.`proxy_id`
                    WHERE a.`id` NOT IN (".$excluded_ids.")
                    AND (
                        (c.`start_date` >= ? AND c.`end_date` <= ?) OR
                        ((c.`start_date` >= ? AND c.`start_date` <= ?) AND c.`end_date` >= ?) OR
                        (c.`start_date` <= ? AND (c.`end_date` <= ? AND c.`end_date` >= ?)) OR
                        (c.`start_date` <= ? AND c.`end_date` >= ?)
                    )
                    AND b.`account_active` = 'true'
                    AND (b.`access_starts` = '0' OR b.`access_starts` <= ?)
                    AND (b.`access_expires` = '0' OR b.`access_expires` > ?)
                    AND (
                            CONCAT(a.`firstname`, ' ' , a.`lastname`) LIKE ".$db->qstr("%".$search_term."%")." OR
                            CONCAT(a.`lastname`, ' ' , a.`firstname`) LIKE ".$db->qstr("%".$search_term."%")." OR
                            a.email LIKE ".$db->qstr("%".$search_term."%")."
                        )
                    AND (
                    		b.`group` = 'resident' OR
                    		(b.`group` = 'student' AND b.`organisation_id` = 8)
						)
                    GROUP BY a.`id`
                    ORDER BY a.`firstname` ASC, a.`lastname` ASC";

        if (!empty($limit)) {
            $query .= " LIMIT " . $limit;
        }

        if (!empty($offset)) {
            $query .= " OFFSET " . $offset;
        }
        $results = $db->GetAll($query, array($start_date, $end_date, $start_date, $start_date, $end_date, $start_date, $end_date, $end_date, $start_date, $end_date, time(), time()));

        return $results;
    }

    public function getStudentsByOrganisationID($organisation_id, $access_date, $role_date) {
        global $db;
        if ($role_date) {
            $role_date = date("Y", $role_date);
        } else {
            $role_date = (date("Y") - ((date("m") < 7) ?  2 : 1));
        }

        $query = "	SELECT a.`id` AS `proxy_id`, b.`role`, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, a.`organisation_id`
								FROM `".static::$database_name."`.`".static::$table_name."` AS a
								LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
								ON a.`id` = b.`user_id`
								WHERE b.`app_id` = ".AUTH_APP_ID."
								AND b.`account_active` = 'true'
								AND (b.`access_starts` = '0' OR b.`access_starts` <= ?)
								AND (b.`access_expires` = '0' OR b.`access_expires` > ?)
								AND b.`group` = 'student'
								AND b.`role` >= ?
								AND a.`organisation_id` = ?
								ORDER BY b.`role` ASC, a.`lastname` ASC, a.`firstname` ASC";

        $results = $db->GetAll($query, array($access_date, $access_date, $role_date, $organisation_id));

        if ($results) {
            return $results;
        }

        return false;
    }
  
    public function fetchAllByName($firstname = "", $lastname = "", $organisation_id = 0) {
        global $db;

        $output = [];

        $organisation_id = (int) $organisation_id;

        $params = [AUTH_APP_ID, $firstname, $lastname];

        $query = "SELECT a.*, b.`organisation_id`
                    FROM `" . AUTH_DATABASE . "`.`user_data` AS a
                    JOIN `" . AUTH_DATABASE . "`.`user_access` AS b
                    ON a.`id` = b.`user_id`
                    AND b.`app_id` = ?
                    WHERE (a.`firstname` LIKE ? AND a.`lastname` LIKE ?)
                    GROUP BY a.`id`";

        if ($organisation_id) {
            $query .= " AND b.`organisation_id` = ?";
            $params[] = $organisation_id;
        }

        $results = $db->GetAll($query, $params);
        if ($results) {
            foreach ($results as $result) {
                $output[] = new self($result);
            }
        }

        return $output;
    }

    /**
     *
     *  Returns the SQL rule based on the search type param
     *
     * @param string $search_type Type of search to create the query rules ('active', 'inactive', 'new', 'all')
     * @return string  SQL rule to based on the specified search type
     */
    private function get_search_type_query_rule($search_type) {
        global $db;
        //There will be no rule to add if the search type is equal to 'all'
        $rule = "";
        if ($search_type == "active") {
            $rule = "AND b.`account_active` = 'true'
					 AND b.`access_starts` < " . $db->qstr(time()) . "
					 AND (b.`access_expires` > " . $db->qstr(time()) . " OR b.`access_expires` = 0) AND";
        } else if ($search_type == "inactive") {
            $rule = "AND (b.`account_active` = 'false'
					 OR (b.`access_starts` > " . $db->qstr(time()) . "
					 OR (b.`access_expires` < " . $db->qstr(time()) . " AND b.`access_expires` != 0))) AND";
        } else if ($search_type == "new") {
            $rule = "b.`app_id` IS NULL AND";
        }
        return $rule;
    }

    /**
     *
     *  Builds the query rules base on the search term
     *
     * @param string $search_query The search term, in this case the name or user email
     * @return string
     */
    private function get_search_query_rule($search_query) {
        global $db;
        $clean_search_query = str_replace("%", "", $search_query);
        $clean_search_query = str_replace(",", "", $clean_search_query);
        $clean_search_query = str_replace("?", "", $clean_search_query);
        $query_items = explode(" ", $clean_search_query);
        $rules_a = array();
        foreach ($query_items as $query_item) {
            $rule = "(a.`number` LIKE " . $db->qstr("%" . $query_item . "%") . "
                     OR a.`username` LIKE " . $db->qstr("%" . $query_item . "%") . "
                     OR a.`email` LIKE " . $db->qstr("%" . $query_item . "%") . "
                     OR a.`firstname` LIKE " . $db->qstr("%" . $query_item . "%") . "
                     OR a.`lastname` LIKE " . $db->qstr("%" . $query_item . "%") . ")";
            array_push($rules_a, $rule);
        }
        return implode(" OR ", $rules_a);
    }

    /**
     *  Returns the SQL query to be executed on the user search based on the search type
     *
     * @param string $search_type The kind of search to be executed (all, active, inactive and new users).
     * @param string $search_query User name to be queried.
     * @return string SQL query to be executed while searching an user.
     * */
    public function get_search_query_sql($search_type, $search_query, $order_by) {
        global $db;

        $query = "SELECT a.*, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, b.`account_active`, b.`access_starts`, b.`access_expires`, b.`last_login`, b.`role`, b.`group`
                  FROM `" . AUTH_DATABASE . "`.`user_data` AS a
                  LEFT JOIN `" . AUTH_DATABASE . "`.`user_access` AS b
                  ON b.`user_id` = a.`id`
                  AND b.`app_id` = " . $db->qstr(AUTH_APP_ID) . "
                  WHERE " . Models_User::get_search_type_query_rule($search_type) . " " . Models_User::get_search_query_rule($search_query) . "
                  GROUP BY a.`id` $order_by LIMIT ?, ?";
        return $query;
    }

    /**
     *  Returns the SQL query to count the results on user search query
     *
     * @param string $search_type The kind of search to be executed (all, active, inactive and new users).
     * @param string $search_query User name to be queried.
     * @return string
     * */
    public function get_search_counter_sql($search_type, $search_query) {
        global $db;
        $query = "SELECT count(*) as `total_rows` FROM (SELECT COUNT(a.`id`) AS `total_rows`
					   FROM `" . AUTH_DATABASE . "`.`user_data` AS a
					   LEFT JOIN `" . AUTH_DATABASE . "`.`user_access` AS b
					   ON b.`user_id` = a.`id`
					   AND b.`app_id` = " . $db->qstr(AUTH_APP_ID) . "
					   WHERE " . Models_User::get_search_type_query_rule($search_type) . " " . Models_User::get_search_query_rule($search_query) . "
					   GROUP BY a.`id`) as t";
        return $query;
    }

    /**
     *  Executes the user search query passed by parameter
     *
     * @param string $query_search The string query to be looked at (name, email, username...).
     * @param int $page_current The current page on the pagination.
     * @param int $results_per_page The number of items to be shown in the pagination.
     * @param string $search_type the type of search to execute: (browse-group, browse-dept, browse-newest, search)
     * @return array: Array of users to be listed.
     * */

    public function search_user($query_search, $page_current, $results_per_page, $search_type) {
        global $db;
        $limit = (int)(($results_per_page * $page_current) - $results_per_page);
        if($search_type == "browse-newest"){
            // if the query is get-newest type, the limit and results are set already
            return $db->GetAll($query_search);
        }
        return $db->GetAll($query_search, array($limit, $results_per_page));
    }

}

