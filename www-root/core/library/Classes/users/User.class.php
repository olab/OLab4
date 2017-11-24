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
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jonathan Fingland <jonathan.fingland@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

require_once("Classes/organisations/Organisation.class.php");
require_once("Classes/users/Cohort.class.php");

/**
 * User class with basic information and access to user related info
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jonathan Fingland <jonathan.fingland@quensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 */
class User {
	private $id,
			$number,
			$username,
			$password,
            $salt,
			$organisation_id,
			$department,
			$prefix,
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
			$entry_year,
			$grad_year,
            $gender,
			$clinical,
			$cohort,
			$cached_country,
			$cached_province,
			$active_organisation,
			$all_organisations,
			$organisation_group_role,
			$default_access_id,
			$access_id,
			$group,
			$role,
			$private_hash,
            $active_id,
			$active_group,
			$active_role,
			$token;

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

	public function __construct() {}

	/**
	 * Returns the id of the user
	 * @return int
	 */
	public function getProxyId() {
		return $this->id;
	}

	/**
	 * Returns the id of the user
	 * @return int
	 */
	public function getID() {
		return $this->id;
	}

	/**
	 * Returns the real world student number/employee number
	 * @return int
	 */
	public function getNumber() {
		return $this->number;
	}

	/**
	 * Returns the username of the user
	 * @return string
	 */
	public function getUsername() {
		return $this->username;
	}

    /**
     * Returns the password of the user
     * @return string
     */
    public function getPassword() {
        return $this->password;
    }

    /**
	 * Returns the ID of the organisation to which the user belongs
	 * @return int
	 */
	public function getOrganisationId() {
		return $this->organisation_id;
	}

	/**
	 * Returns an array of all the organisation that this user
	 * belongs to.
	 *
	 * @return array
	 */
	public function getAllOrganisations() {
		return $this->all_organisations;
	}

	/**
	 * Sets the array of all orgs this user belongs to.
	 *
	 * @param <array> $value
	 */
	public function setAllOrganisations($value) {
		$this->all_organisations = $value;
        ksort($this->all_organisations);
	}

	/**
	 * Returns the currently active organisation.
	 * If not set then it returns the default org for this user found
	 * in the user_data table.
	 *
	 * @return int
	 */
	public function getActiveOrganisation() {
		if ($this->active_organisation) {
			return $this->active_organisation;
		} elseif (isset($_SESSION["permissions"][$this->getAccessId()]) && $_SESSION["permissions"][$this->getAccessId()]["organisation_id"]) {
			return $_SESSION["permissions"][$this->getAccessId()]["organisation_id"];
		} else {
			return $this->organisation_id;
		}
	}

	/**
	 * Sets the active organisation.
	 *
	 * @param <String> $value - the active, i.e., current org
	 */
	public function setActiveOrganisation($value){
		$this->active_organisation = $value;
	}

	/**
	 * @return Departments
	 */
	public function getDepartments() {
		return Departments::getByUser($this->user_id);
	}

	/**
	 * Returns the User's specified prefix, if any. e.g. Mr, Mrs, Dr,...
	 * @return string
	 */
	public function getPrefix() {
		return $this->prefix;
	}

	/**
	 * Returns the first name of the user
	 * @return string
	 */
	public function getFirstname(){
		return $this->firstname;
	}

	/**
	 * Returns the last name of the user
	 * @return string
	 */
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


	/**
	 * Returns the user's email address, if available
	 * @return string
	 */
	public function getEmail() {
		return $this->email;
	}

	/**
	 * Returns the user's alternate email address, if available
	 * @return string
	 */
	public function getEmailAlt() {
		return $this->email_alt;
	}

    /**
	 * Returns the user's alternate email address, if available
	 * @return string
	 */
	public function getEmailAlternate() {
		return $this->email_alt;
	}

    /**
	 * Returns the user's alternate email address, if available
	 * @return string
	 */
	public function getAlternateEmail() {
		return $this->email_alt;
	}


    /**
	 * Returns the last timestamp that the user updated their e-mail address.
	 * @return string
	 */
	public function getEmailUpdated() {
		return (int) $this->email_updated;
	}

    /**
	 * Returns the last timestamp that the user updated their e-mail address.
	 * @return string
	 */
	public function getGoogleId() {
		return $this->google_id;
	}

	/**
	 * @return string
	 */
	public function getTelephone() {
		return $this->telephone;
	}

	/**
	 * @return string
	 */
	public function getFax() {
		return $this->fax;
	}

	/**
	 * Returns the street address portion of a user's provided address. For excample: 123 Fourth Street
	 * @return string
	 */
	public function getAddress() {
		return $this->address;
	}

    /**
	 * @return string
	 */
	public function getCity() {
		return $this->city;
	}

	/**
	 * @return Region
	 */
	public function getProvince() {
		if (is_null($this->cached_province)) {
			$this->cached_province = new Region($this->province);
		}

		return $this->cached_province;
	}

	/**
	 * NOTE: also used for zip codes and the like
	 * @return string
	 */
	public function getPostalCode() {
		return $this->postcode;
	}

	/**
	 * Returns a Country object for the user's specified country. Legacy Support: Note that some users may not have country_id specified and rely on older country names. In those cases a new object is returned and can be operated in the same manner as newer country data
	 * @return Country
	 */
	public function getCountry() {
		if (is_null($this->cached_country)) {
			if ($this->country_id && ($country = Country::get($this->country_id))) {
				$this->cached_country = $country;
			} else {
				$this->cached_country = new Country($this->country);
			}
		}

		return $this->cached_country;
	}

	/**
	 * Returns the notes field.
	 * @return string
	 */
	public function getNotes() {
		return $this->notes;
	}

	/**
	 * @return string
	 */
	public function getOfficeHours() {
		return $this->office_hours;
	}

	/**
	 * Returns the user-specified (numeric) privacy level
	 * @return int
	 */
	public function getPrivacyLevel(){
		return $this->privacy_level;
	}

	/**
	 * Returns the copyright
	 * @return int
	 */
	public function getCopyright(){
		return $this->copyright;
	}

    /**
	 * Returns the user-specified (numeric) privacy level
	 * @return int
	 */
	public function getNotifications(){
		return $this->notifications;
	}

	/**
	 * Returns the year a student enetered med school, if available
	 * @return int
	 */
	public function getEntryYear() {
		return $this->entry_year;
	}

	/**
	 * Returns the graduating year of the user, if available
	 * @return int
	 */
	public function getGradYear() {
		return $this->grad_year;
	}

    /**
	 * Returns the numeric representation of the gender.
	 * @return int
	 */
	public function getGender() {
		return $this->gender;
	}

	/**
	 * Returns the int/boolean of the user's "clinical" status.
	 * @return int
	 */
	public function getClinical() {
		return $this->clinical;
	}

	/**
	 * Sets the int/boolean for the user's "clinical" status.
	 * @param int $value
	 */
	public function setClinical($value) {
		$this->clinical = $value;
	}

	/**
	 * Returns a collection of photos belonging to the user.
	 * @return UserPhotos
	 */
	public function getPhotos() {
		return UserPhotos::get($this->getID());
	}

	/**
	 * Returns the cohort of the user, if available
	 * @return int
	 */
	public function getCohort() {
		return $this->cohort;
	}

	/**
	 * Sets the cohort of the user, if available
	 * @param int $value : The cohort with which the given user is associated.
	 */
	public function setCohort($value) {
		$this->cohort = $value;
	}

	/**
	 * Returns the entire class of the same cohort
	 * @return Cohort
	 */
	public function getFullCohort() {
		if ($this->cohort) {
			return Cohort::get($this->cohort);
		}
	}

	/**
	 * Returns the access group to which this user belongs e.g. student, faculty, ...
	 * @return string
	 */
	public function getGroup() {
		if (is_null($this->group) && !$this->getAccess()) {
			return;
		}

		return $this->group;
	}

    /**
	 * Returns the active group for the active organisation.
	 *
	 * @return array
	 */
	public function getActiveGroup() {
		if ($_SESSION["permissions"][$this->getAccessId()]["group"]) {
			return $_SESSION["permissions"][$this->getAccessId()]["group"];
		} else {
			return $this->group;
		}
	}

	/**
	 * Sets the active group.
	 *
	 * @param type $string
	 */
	public function setActiveGroup($group) {
		$this->active_group = $group;
	}

	/**
	 * Returns the access role to which the user belongs
	 * @return string
	 */
	public function getRole() {
		if (is_null($this->role) && !$this->getAccess()) {
			return;
		}

		return $this->role;
	}

	/**
	 * Sets the active role.
	 *
	 * @param type string
	 */
	public function setRole($role) {
		$this->role = $role;
	}

	/**
	 * Returns the active role for the active organisation.
	 *
	 * @return array
	 */
	public function getActiveRole() {
		if ($_SESSION["permissions"][$this->getAccessId()]["role"]) {
			return $_SESSION["permissions"][$this->getAccessId()]["role"];
		} else {
			return $this->role;
		}
	}

	/**
	 * Sets the active role.
	 *
	 * @param type string
	 */
	public function setActiveRole($role) {
		$this->active_role = $role;
	}

	/**
	 * Returns the JWT token
	 *
	 * @return array
	 */
	public function getToken() {
		if ($_SESSION["details"]["token"]) {
			return $_SESSION["details"]["token"];
		} else {
			return $this->token;
		}
	}

	/**
	 * Sets the JWT token
	 *
	 * @param type string
	 */
	public function setToken($token) {
		if (!isset($_SESSION["details"])) {
			$_SESSION["details"] = array();
		}

		$_SESSION["details"]["token"] = $token;
		$this->token = $token;
	}

  /**
   * Unsure what this function is used for exactly.
   * @param type $value
   */
	public function setOrganisationGroupRole($value) {
		$this->organisation_group_role = $value;
	}

    /**
     * Unsure what this function is used for exactly.
     * @param type $value
     */
	public function getOrganisationGroupRole() {
		return $this->organisation_group_role;
	}

	/**
	 * Returns the active proxy_id of the user
	 * @return int
	 */
	public function getActiveId() {
		if ($this->active_id) {
			return $this->active_id;
		} elseif ($this->access_id) {
			$this->setActiveId($this->access_id);

			return $this->active_id;
		} else {
			return $this->id;
		}
	}

	/**
	 * Sets the active proxy_id of the user
	 * @return int
	 */
	public function setActiveId($value) {
		global $db;

		$query = "SELECT `user_id` FROM `".AUTH_DATABASE."`.`user_access` WHERE `id` = ".$db->qstr($value);
		$active_id = $db->GetOne($query);
		if ($active_id) {
			$this->active_id = $active_id;
		}
	}

	/**
	 * Returns the currently active private_hash from the active entrada_auth.user_access record.
	 *
	 * @return array
	 */
	public function getActivePrivateHash() {
		if ($_SESSION["permissions"][$this->getAccessId()]["private_hash"]) {
			return $_SESSION["permissions"][$this->getAccessId()]["private_hash"];
		} else {
			return $this->private_hash;
		}
	}

    /**
     * Returns the associated access_id from the user_access table.
     * @return int
     */
	public function getAccessId() {
        if (isset($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["access_id"]) && $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["access_id"]) {
            return $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["access_id"];
        } else {
            return $this->access_id;
        }
	}

    /**
     * Sets the active access_id from the user_access table.
     * @global type $db
     * @param type $value
     */
	public function setAccessId($value) {
		global $db;

		if ((!isset($value) || !$value) && isset($this->default_access_id) && $this->default_access_id) {
			$value = $this->default_access_id;
		} elseif ((!isset($value) || !$value) && (!isset($this->default_access_id) || !$this->default_access_id)) {
			$query = "SELECT `id` FROM `".AUTH_DATABASE."`.`user_access`
						WHERE `user_id` = ".$db->qstr($this->getID())."
						AND `app_id` = ".$db->qstr(AUTH_APP_ID)."
						AND `account_active` = 'true'
						AND (`access_starts` = '0' OR `access_starts` <= ".$db->qstr(time()).")
						AND (`access_expires` = '0' OR `access_expires` >= ".$db->qstr(time()).")
						AND `organisation_id` = ".$db->qstr(($this->getActiveOrganisation() ? $this->getActiveOrganisation() : $this->getOrganisationID()));
			$value = $db->getOne();
			$this->default_access_id = $value;
		}

        $this->setActiveId($value); // Set Proxy ID
		$this->access_id = $value; // Set Access ID

        $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["access_id"] = $value;
		// Get all of the users orgs
		$query = "SELECT b.`organisation_id`, b.`organisation_title`
					  FROM `" . AUTH_DATABASE . "`.`user_access` a
					  JOIN `" . AUTH_DATABASE . "`.`organisations` b
					  ON a.`organisation_id` = b.`organisation_id`
					  WHERE a.`user_id` = ?
					  AND a.`app_id` = ?";
		$results = $db->GetAll($query, array($this->getActiveId(), AUTH_APP_ID));

		// Every user should have at least one org.
		if ($results) {
			$organisation_list = array();
			foreach ($results as $result) {
				$organisation_list[$result["organisation_id"]] = html_encode($result["organisation_title"]);
			}
			$this->setAllOrganisations($organisation_list);
		}

		// Get all of the users groups and roles for each organisation
		$query = "SELECT b.`organisation_id`, b.`organisation_title`, a.`group`, a.`role`, a.`id`, c.`organisation_id` AS `default_organisation_id`
					FROM `" . AUTH_DATABASE . "`.`user_access` a
					JOIN `" . AUTH_DATABASE . "`.`organisations` b
					ON a.`organisation_id` = b.`organisation_id`
					JOIN `" . AUTH_DATABASE . "`.`user_data` c
					ON a.`user_id` = c.`id`
					WHERE a.`user_id` = ?
					AND a.`account_active` = 'true'
					AND (a.`access_starts` = '0' OR a.`access_starts` < ?)
					AND (a.`access_expires` = '0' OR a.`access_expires` >= ?)
					AND a.`app_id` = ?
					ORDER BY a.`id` ASC";
		$results = $db->getAll($query, array($this->getActiveId(), time(), time(), AUTH_APP_ID));
		if ($results) {
			$org_group_role = array();

            foreach ($results as $result) {
				$org_group_role[$result["organisation_id"]][] = array("group" => html_encode($result["group"]), "role" => html_encode($result["role"]), "access_id" => $result["id"]);
			}

			$this->setOrganisationGroupRole($org_group_role);
		}
	}

    /**
     * Returns the default access_id from the user_access table.
     * @return int
     */
	public function getDefaultAccessId() {
		return $this->default_access_id;
	}

    /**
     * Sets the associated default access_id from the user_access table.
     * @param int $value
     */
	public function setDefaultAccessId($value) {
		$this->default_access_id = $value;
	}

	/**
	 * Gets the entire user object of the associated proxy_id.
	 * @param int proxy_id
	 * @return User
	 */
	public static function get($proxy_id = 0, $reload_cache = false) {
		global $db, $ENTRADA_CACHE;

        $proxy_id = (int) $proxy_id;

        if ($proxy_id) {
            if (!isset($ENTRADA_CACHE) || (bool) $reload_cache || !($user = $ENTRADA_CACHE->load("user_".AUTH_APP_ID."_".$proxy_id))) {
                $user = new User();
                $query = "SELECT a.*, b.`group`, b.`role`, b.`organisation_id`, b.`id` AS `access_id`
                            FROM `" . AUTH_DATABASE . "`.`user_data` AS a
                            JOIN `" . AUTH_DATABASE . "`.`user_access` AS b
                            ON a.`id` = b.`user_id`
                            AND b.`app_id` = ?
                            WHERE a.`id` = ?
                            AND b.`account_active` = 'true'
                            AND (b.`access_starts` = '0' OR b.`access_starts` < ?)
                            AND (b.`access_expires` = '0' OR b.`access_expires` >= ?)
                            ORDER BY b.`id`";
                $result = $db->GetRow($query, array(AUTH_APP_ID, $proxy_id, time(), time()));
                if ($result) {
                    $user = self::fromArray($result, $user);
                }

                if (isset($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["access_id"]) && $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["access_id"]) {
                    $query = "SELECT `id` FROM `".AUTH_DATABASE."`.`user_access`
                                WHERE `id` = ?
                                AND `account_active` = 'true'
                                AND (`access_starts` = '0' OR `access_starts` < ?)
                                AND (`access_expires` = '0' OR `access_expires` >= ?)
                                AND `app_id` = ?
                                AND `user_id` = ?";
                    $available = $db->GetRow($query, array($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["access_id"], time(), time(), AUTH_APP_ID, $user->getID()));
                    if ($available) {
                        $user->setAccessId($available["id"]);
                    } else {
                        $query = "SELECT b.`id` FROM `permissions` AS a
                                    JOIN `".AUTH_DATABASE."`.`user_access` AS b
                                    ON a.`assigned_by` = b.`user_id`
                                    WHERE b.`id` = ?
                                    AND b.`account_active` = 'true'
                                    AND (b.`access_starts` = '0' OR b.`access_starts` < ?)
                                    AND (b.`access_expires` = '0' OR b.`access_expires` >= ?)
                                    AND b.`app_id` = ?
                                    AND a.`assigned_to` = ?
                                    AND a.`valid_from` <= ?
                                    AND a.`valid_until` >= ?";
                        $mask_available = $db->GetRow($query, array($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["access_id"], time(), time(), AUTH_APP_ID, $user->getID(), time(), time()));
                        if ($mask_available) {
                            $user->setAccessId($mask_available["id"]);
                        } else {
                            $query = "SELECT a.`group`, a.`role`, a.`id`
                                FROM `" . AUTH_DATABASE . "`.`user_access` AS a
                                WHERE a.`user_id` = " . $db->qstr($user->getID()) . "
                                AND a.`organisation_id` = " . $db->qstr($user->getActiveOrganisation()) . "
                                AND a.`app_id` = " . $db->qstr(AUTH_APP_ID) . "
                                AND a.`account_active` = 'true'
                                AND (a.`access_starts` = '0' OR a.`access_starts` < ".$db->qstr(time()).")
                                AND (a.`access_expires` = '0' OR a.`access_expires` >= ".$db->qstr(time()).")
                                ORDER BY a.`id` ASC";
                            $result = $db->GetRow($query);
                            if ($result) {
                                $user->setAccessId($result["id"]);
                            }
                        }
                    }
                } else {
                    $query = "SELECT a.`group`, a.`role`, a.`id`
                                FROM `" . AUTH_DATABASE . "`.`user_access` AS a
                                WHERE a.`user_id` = " . $db->qstr($user->getID()) . "
                                AND a.`organisation_id` = " . $db->qstr($user->getActiveOrganisation()) . "
                                AND a.`app_id` = " . $db->qstr(AUTH_APP_ID) . "
                                AND a.`account_active` = 'true'
                                AND (a.`access_starts` = '0' OR a.`access_starts` < ".$db->qstr(time()).")
                                AND (a.`access_expires` = '0' OR a.`access_expires` >= ".$db->qstr(time()).")
                                ORDER BY a.`id` ASC";
                    $result = $db->GetRow($query);
                    if ($result) {
                        $user->setAccessId($result["id"]);
                    }
                }

                $query = "SELECT a.`group_id` FROM `groups` AS a
                            JOIN `group_members` AS b
                            ON a.`group_id` = b.`group_id`
                            WHERE a.`group_type` = 'cohort'
                            AND b.`proxy_id` = ?";
                $result = $db->GetOne($query, array($proxy_id));
                if ($result) {
                    $user->setCohort($result);
                }

                //get all of the users groups and roles for each organisation
                $query = "SELECT b.`organisation_id`, b.`organisation_title`, a.`group`, a.`role`, a.`id`
                            FROM `" . AUTH_DATABASE . "`.`user_access` AS a
                            JOIN `" . AUTH_DATABASE . "`.`organisations` AS b
                            ON a.`organisation_id` = b.`organisation_id`
                            WHERE a.`user_id` = ?
                            AND a.`account_active` = 'true'
                            AND (a.`access_starts` = '0' OR a.`access_starts` < ?)
                            AND (a.`access_expires` = '0' OR a.`access_expires` >= ?)
                            AND a.`app_id` = ?
                            ORDER BY a.`id` ASC";
                $results = $db->GetAll($query, array($proxy_id, time(), time(), AUTH_APP_ID));

                //every user should have at least one org.
                if ($results) {
                    $organisation_list = array();
                    $org_group_role = array();

                    foreach ($results as $result) {
                        $organisation_list[$result["organisation_id"]] = html_encode($result["organisation_title"]);
                    }

                    $user->setAllOrganisations($organisation_list);

                    foreach ($results as $result) {
                        if ((!isset($user->default_access_id) || !$user->default_access_id)) {
                            if (!isset($_SESSION["permissions"][$user->getAccessId()]["organisation_id"]) || !$_SESSION["permissions"][$user->getAccessId()]["organisation_id"]) {
                                $_SESSION["permissions"][$user->getAccessId()]["organisation_id"] = $result["organisation_id"];
                                $user->setActiveOrganisation($result["organisation_id"]);
                            }
                            $user->setDefaultAccessId($result["id"]);
                        }
                        $org_group_role[$result["organisation_id"]][] = array("group" => html_encode($result["group"]), "role" => html_encode($result["role"]), "access_id" => $result["id"]);
                    }

                    if ((!isset($_SESSION["permissions"][$user->getAccessId()]["organisation_id"]) || !$_SESSION["permissions"][$user->getAccessId()]["organisation_id"]) && isset($results[0]["organisation_id"]) && $results[0]["organisation_id"]) {
                        $_SESSION["permissions"][$user->getAccessId()]["organisation_id"] = $results[0]["organisation_id"];
                        $user->setActiveOrganisation($results[0]["organisation_id"]);
                    }

                    $user->setOrganisationGroupRole($org_group_role);
                }

                $ENTRADA_CACHE->save($user, "user_".AUTH_APP_ID."_".$proxy_id, array("auth"), 300);
                $ENTRADA_CACHE->save(md5(serialize($results)), "access_hash_" . $proxy_id, array("access_hash"), 300);
            }

    		return $user;
        } else {
            return false;
        }
	}

    public function toArray() {
		$arr = false;
		$class_vars = get_class_vars(get_called_class());
		if (isset($class_vars)) {
			foreach ($class_vars as $class_var => $value) {
                if ($class_var != "format_keys") {
                    $arr[$class_var] = $this->$class_var;
                }
			}
        }
		return $arr;
	}

	public static function getAccessHash() {
		global $db, $ENTRADA_USER;
		//get all of the users groups and roles for each organisation
		$query = "		SELECT b.`organisation_id`, b.`organisation_title`, a.`group`, a.`role`, a.`id`
						FROM `" . AUTH_DATABASE . "`.`user_access` a
						JOIN `" . AUTH_DATABASE . "`.`organisations` b
						ON a.`organisation_id` = b.`organisation_id`
						WHERE a.`user_id` = ?
						AND a.`account_active` = 'true'
						AND (a.`access_starts` = '0' OR a.`access_starts` < ?)
						AND (a.`access_expires` = '0' OR a.`access_expires` >= ?)
						AND a.`app_id` = ?
						ORDER BY a.`id` ASC";

		$results = $db->getAll($query, array($ENTRADA_USER->getID(), time(), time(), AUTH_APP_ID));
		return md5(serialize($results));
	}

	/**
	 * Returns a User object created using the array inputs supplied
	 * @param array $arr
	 * @return User
	 */
	public static function fromArray(array $arr, User $user) {
        foreach ($arr as $class_var_name => $value) {
			$user->$class_var_name = $value;
		}
        /*
		$user->id = $arr["id"];
		$user->number = $arr["number"];
		$user->username = $arr["username"];
		$user->password = $arr["password"];
		$user->salt = $arr["salt"];
		$user->organisation_id = $arr["organisation_id"];
		$user->department = $arr["department"];
		$user->prefix = $arr["prefix"];
		$user->firstname = $arr["firstname"];
		$user->lastname = $arr["lastname"];
		$user->email = $arr["email"];
		$user->email_alt = $arr["email_alt"];
		$user->email_updated = $arr["email_updated"];
		$user->google_id = $arr["google_id"];
		$user->telephone = $arr["telephone"];
		$user->fax = $arr["fax"];
		$user->address = $arr["address"];
		$user->city = $arr["city"];
		$user->province = $arr["province"];
		$user->postcode = $arr["postcode"];
		$user->country = $arr["country"];
		$user->country_id = $arr["country_id"];
		$user->province_id = $arr["province_id"];
		$user->notes = $arr["notes"];
		$user->office_hours = $arr["office_hours"];
		$user->privacy_level = $arr["privacy_level"];
		$user->notifications = $arr["notifications"];
		$user->entry_year = $arr["entry_year"];
		$user->grad_year = $arr["grad_year"];
		$user->gender = $arr["gender"];
		$user->clinical = $arr["clinical"];
		$user->group = $arr["group"];
		$user->role = $arr["role"];
		$user->access_id = $arr["access_id"];
		$user->active_id = $arr["id"];
        */
		return $user;
	}

	/**
	 * Internal function for getting access information for a user
	 * @return bool
	 */
	private function getAccess() {
		global $db;
		$query = "	SELECT *
					FROM `".AUTH_DATABASE."`.`user_access`
					WHERE `user_id` = ?
					AND `account_active` = 'true'
					AND (`access_starts` = '0' OR `access_starts` < ?)
					AND (`access_expires` = '0' OR `access_expires` >= ?)
					AND `app_id` = ?";
		$result = $db->getRow($query, array($this->getID(), time(), time(), AUTH_APP_ID));
		if ($result) {
			$this->group = $result["group"];
			$this->role = $result["role"];

			return true;
		}
	}

	/**
	 * @return Users
	 */
	public function getAssistants() {
		global $db;

        $time = time();
		$users = array();

		$query = "SELECT b.*, a.*
                    FROM `permissions` AS a
                    LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
                    ON b.`id` = a.`assigned_to`
                    WHERE a.`assigned_by`=?
                    AND (a.`valid_from` = '0' OR a.`valid_from` <= ?) AND (a.`valid_until` = '0' OR a.`valid_until` > ?)
                    ORDER BY `valid_until` ASC";
		$results = $db->GetAll($query, array($this->getID(), $time, $time));
		if ($results) {
			foreach ($results as $result) {
				$user = Assistant::fromArray($result);
				$users[] = $user;
			}
		}

		return new Users($users);
	}

	/**
	 * Creates a user account and updates object, returns true or false.
	 * $user_data requires: "username", "firstname", "lastname", "email", "password", "organisation_id"
	 * $user_access requires: "group", "role", "app_id"
	 *
	 * @param array $user_data User data array, keys match table fields. Ex: array("id" => "1", "username" => "foo").
	 * @param array $user_access User access array, keys match table fields. Ex: array("group" => "admin").
	 * @return boolean
	 */
	public function createUser(array $user_data, array $user_access) {
		global $db;

		$required_user_data = array("username", "firstname", "lastname", "email", "password", "organisation_id");
		$required_user_access = array("group", "role", "app_id");

		foreach ($required_user_data as $data) {
			if (!array_key_exists($data, $user_data)) {
				$error = true;
			}
		}

		foreach ($required_user_access as $data) {
			if (!array_key_exists($data, $user_access)) {
				$error = true;
			}
		}

		if (!$error) {
			foreach ($user_data as $fieldname => $data) {
				$processed["user_data"][$fieldname] = clean_input($data, array("trim", "striptags"));
			}

			foreach ($user_access as $fieldname => $data) {
				$processed["user_access"][$fieldname] = clean_input($data, array("trim", "striptags"));
			}

			if ($db->AutoExecute("`".AUTH_DATABASE."`.`user_data`", $processed["user_data"], "INSERT")) {

				$processed["user_data"]["id"]			= $db->Insert_ID();
				$processed["user_access"]["user_id"]	= $processed["user_data"]["id"];

				if (!isset($processed["user_access"]["organisation_id"])) { $processed["user_access"]["organisation_id"] = $processed["user_data"]["organisation_id"]; }
				if (!isset($processed["user_access"]["access_starts"])) { $processed["user_access"]["access_starts"] = time(); }
				if (!isset($processed["user_access"]["account_active"])) { $processed["user_access"]["account_active"] = "true"; }
				if (!isset($processed["user_access"]["private_hash"])) { $processed["user_access"]["private_hash"]	= generate_hash(); }

				if (!$db->AutoExecute("`".AUTH_DATABASE."`.`user_access`", $processed["user_access"], "INSERT")) {
					application_log("error", "Failed to add user, DB said: ".$db->ErrorMsg());
					$return = false;
				} else {

					$params = get_class_vars(__CLASS__);

					foreach ($params as $param_name => $param) {
						$this->$param_name = (isset($processed["user_data"][$param_name]) ? $processed["user_data"][$param_name] : (isset($processed["user_access"][$param_name]) ? $processed["user_access"][$param_name] : $param));
					}

					$return = true;
				}

			} else {
				application_log("error", "Failed to add user, DB said: ".$db->ErrorMsg());
				$return = false;
			}

		} else {
			$return = false;
		}

		return $return;
	}

	/**
	 * Updates a user account and updates object, returns true or false.
	 * @param $user_data User data array, keys match table fields. Ex: array("id" => "1", "username" => "foo")
	 * @param $user_access User access array, keys match table fields. Assumes user_id from $user_data["id"]. Ex: array("group" => "admin").
	 * @return boolean
	 */
	public function updateUser(array $user_data, array $user_access = array()) {
		global $db;

		if (!isset($user_data["id"]) || empty($user_data["id"])) {
			$processed["user_data"]["id"] = $this->getID();
		}

		foreach ($user_data as $fieldname => $data) {
			$processed["user_data"][$fieldname] = clean_input($data, array("trim", "striptags"));
		}

		if (!empty($user_access)) {
			foreach ($user_access as $fieldname => $data) {
				$processed["user_access"][$fieldname] = clean_input($data, array("trim", "striptags"));
			}
		}

		if ($db->AutoExecute("`".AUTH_DATABASE."`.`user_data`", $processed["user_data"], "UPDATE", "id = ".$db->qstr($processed["user_data"]["id"]))) {
			if (!empty($processed["user_access"])) {
				if (!$db->AutoExecute("`".AUTH_DATABASE."`.`user_access`", $processed["user_access"], "UPDATE", "user_id = ".$db->qstr($processed["user_data"]["id"]))) {
					application_log("error", "Failed to update user [".$processed["user_data"]["id"]."], DB said: ".$db->ErrorMsg());
					$return = false;
				}
			}

			$params = get_class_vars(__CLASS__);

			foreach ($params as $param_name => $param) {
				$this->$param_name = (isset($processed["user_data"][$param_name]) ? $processed["user_data"][$param_name] : (isset($processed["user_access"][$param_name]) ? $processed["user_access"][$param_name] : $param));
			}
		} else {
			application_log("error", "Failed to update user [".$processed["user_data"]["id"]."], DB said: ".$db->ErrorMsg());
			$return = false;
		}

		return $return;
	}

	/**
	 * Deactivates a user account and returns true or false.
	 * @param int $id The userid to activate. Uses objects ID if empty.
	 * @return boolean
	 */
	public function deactivateUser($id = "") {
		global $db;

		if (!empty($id)) {
			$proxy_id = (int) $id;
		} else {
			$proxy_id = $this->getID();
		}

		if ($proxy_id) {
			$processed["account_active"] = "false";
			if (!$db->AutoExecute("`".AUTH_DATABASE."`.`user_access`", $processed, "UPDATE", "user_id = ".$db->qstr($proxy_id))) {
				application_log("error", "Failed to set account_active to false for user [".$processed["user_data"]["id"]."], DB said: ".$db->ErrorMsg());
				$return = false;
			} else {
				$return = true;
			}
		} else {
			application_log("error", "Unable to deactivate user account, no proxy_id.");
			$return = false;
		}

		return $return;
	}

    /**
     * This static function returns the course permission for a user in the format of array("<role>" => <course_id>)
     * e.g.
     * array("director" => 444)
     * array("pcoordinator" => 1234)
     *
     * @return array
     */
    public static function getCoursePermissions() {
        global $db, $ENTRADA_USER;

        $course_permissions = array();
        $query = "  SELECT *
                    FROM `course_contacts`
                    WHERE (`contact_type` = 'director'
                          OR `contact_type` = 'ccoordinator'
                          OR `contact_type` = 'pcoordinator')
                    AND `proxy_id` = ?";

        $results = $db->GetAll($query, $ENTRADA_USER->getActiveID());

        if ($results) {
            foreach($results as $result) {
                $course_permissions[$result["contact_type"]][] = $result["course_id"];
            }
        }
        $query = "  SELECT *
                    FROM `courses`
                    WHERE `course_active` = 1
                    AND `organisation_id` = ?
                    AND `pcoord_id` = ?";

        $results = $db->GetAll($query, array($ENTRADA_USER->getActiveOrganisation(), $ENTRADA_USER->getActiveID()));

        if ($results) {
			foreach($results as $result) {
				$course_permissions["pcoord_id"][] = $result["course_id"];
			}
        }

        return $course_permissions;
    }

    /*
     * Gets the abbreviated user object of the associated proxy_id. Data from user_data and user_access tables only
     * @param int proxy_id
     * @return User
     */
    public static function fetchRowByID($proxy_id, $organisation_id = null, $auth_app_id = null) {
        global $db;

        $user = false;

        $query = "  SELECT a.*, b.`group`, b.`role`, b.`organisation_id`, b.`id` AS `access_id`
                    FROM `" . AUTH_DATABASE . "`.`user_data` AS a
                    JOIN `" . AUTH_DATABASE . "`.`user_access` AS b
                    ON a.`id` = b.`user_id`
                    WHERE a.`id` = ?
					AND b.`account_active` = 'true'
					AND (b.`access_starts` = '0' OR b.`access_starts` < ?)
					AND (b.`access_expires` = '0' OR b.`access_expires` >= ?)"
            .(isset($organisation_id) && $organisation_id ? " AND b.`organisation_id` = ?" : "")
            .(isset($auth_app_id) && $auth_app_id ? " AND b.`app_id` = ?" : "");

        $constraints = array($proxy_id, time(), time());
        if (isset($organisation_id) && $organisation_id) {
            $constraints[] = $organisation_id;
        }
        if (isset($auth_app_id) && $auth_app_id) {
            $constraints[] = $auth_app_id;
        }

        $result = $db->GetRow($query, $constraints);

        if ($result) {
            $user = new User();
            $user = self::fromArray($result, $user);
        }

        return $user;
    }

    public static function fetchProxyByNumber($number) {
        global $db;

        $query = "  SELECT `id` FROM `".AUTH_DATABASE."`.`user_data`
                    WHERE `number` = ?";

        $id = $db->GetOne($query, array($number));

        return $id;
    }

    public static function searchByTermOrg($search_term, $organisation_id) {
        global $db;

        $query = "SELECT a.*
                    FROM `" . AUTH_DATABASE . "`.`user_data` AS a
                    JOIN `" . AUTH_DATABASE . "`.`user_access` AS b
                    ON a.`id` = b.`user_id`
                    AND b.`organisation_id` = ?
                    WHERE LCASE(CONCAT(a.`firstname`, ' ', a.`lastname`)) LIKE (?)";
        $results = $db->GetAll($query, array($organisation_id, "%" . $search_term . "%"));
        if ($results) {
            foreach ($results as $result) {
                $output[] = self::fromArray($result, new self);
            }
            return $output;
        } else {
            return false;
        }
    }

    public static function fetchAllByOrgGroup($organisation_id, $group) {
        global $db;

        $query = "SELECT a.*
                    FROM `" . AUTH_DATABASE . "`.`user_data` AS a
                    JOIN `" . AUTH_DATABASE . "`.`user_access` AS b
                    ON a.`id` = b.`user_id`
                    AND b.`organisation_id` = ?
                    WHERE b.`group` LIKE ?
					AND b.`account_active` = 'true'
					AND (b.`access_starts` = '0' OR b.`access_starts` < ?)
					AND (b.`access_expires` = '0' OR b.`access_expires` >= ?)
					AND b.`app_id` = ?
                    GROUP BY a.`id`";
        $results = $db->GetAll($query, array($organisation_id, $group, time(), time(), AUTH_APP_ID));
        if ($results) {
            foreach ($results as $result) {
                $output[] = self::fromArray($result, new self);
            }
            return $output;
        } else {
            return false;
        }
    }

    public static function fetchAllAudienceByCourse($search_value, $course_id, $start_date, $end_date = false) {
        global $db;

        $output = array();

        $query = "SELECT c.* FROM `course_audience` AS a
                    LEFT JOIN `group_members` AS b
                    ON a.`audience_type` = 'group_id'
                    AND a.`audience_value` = b.`group_id`
                    AND b.`member_active` = 1
                    AND
                    (
                        (
                          b.`start_date` = 0
                          AND b.`finish_date` = 0
                        )
                        OR
                        (
                            (
                                b.`start_date` <= " . $db->qstr($start_date) . "
                            )
                            AND
                            (
                                b.`finish_date` = 0
                                OR b.`finish_date` >= " . $db->qstr($start_date) . "
                            )
                        )".
                        ($end_date ? "
                        OR
                        (
                            (
                                b.`start_date` <= " . $db->qstr($end_date) . "
                            )
                            AND
                            (
                                b.`finish_date` = 0
                                OR b.`finish_date` >= " . $db->qstr($end_date) . "
                            )
                        )
                        OR
                        (
                            b.`start_date` >= " . $db->qstr($start_date) . "
                            AND b.`finish_date` <= " . $db->qstr($end_date) . "
                        )
                        " : "")."
                    )
                    JOIN `".AUTH_DATABASE."`.`user_data` AS c
                    ON
                    (
                        (
                            a.`audience_type` = 'proxy_id'
                            AND a.`audience_value` = c.`id`
                        )
                        OR
                        (
                            b.`proxy_id` = c.`id`
                        )
                    )
                    WHERE a.`course_id` = ".$db->qstr($course_id)."
                    ".($search_value ? "AND CONCAT_WS(' ', c.`firstname`, c.`lastname`) LIKE ".$db->qstr("%".$search_value."%") : "")."
                    GROUP BY c.`id`";
        $results = $db->GetAll($query);
        if ($results) {
            foreach ($results as $result) {
                $user = new User();
                $user = self::fromArray($result, $user);
                $output[] = $user;
            }
        }

        return $output;
    }

	public static function fetchUsersByGroups($search_term = null, $group = null, $organisation_id = null, $app_id = null, $excluded_ids = 0, $limit = null, $offset = null) {
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
                    AND (
                            CONCAT(a.`firstname`, ' ' , a.`lastname`) LIKE ".$db->qstr("%".$search_term."%")." OR
                            CONCAT(a.`lastname`, ' ' , a.`firstname`) LIKE ".$db->qstr("%".$search_term."%")." OR
                            a.email LIKE ".$db->qstr("%".$search_term."%")."
                        )".
                    (isset($groups_string) && $groups_string ? " AND b.`group` IN (".$groups_string.")" : (isset($group) && $group ? " AND b.`group` = ?" : "")).
					(isset($organisation_id) && $organisation_id ? " AND b.`organisation_id` = " . $db->qstr($organisation_id) : "").
					(isset($app_id) && $app_id ? " AND b.`app_id` = " . $db->qstr($app_id) : "")."
                    GROUP BY a.`id`
                    ORDER BY a.`firstname` ASC, a.`lastname` ASC";
        if (!empty($limit)) {
            $query .= " LIMIT " . $limit;
        }

        if (!empty($offset)) {
            $query .= " OFFSET " . $offset;
        }
		$results = $db->GetAll($query, ($groups_string ? array(time(), time()) : ($group ? array(time(), time(), $group) : array(time(), time()))));
		return $results;
	}

	public static function fetchAllResidents($search_term = null, $excluded_ids = 0) {
		global $db;

		$query = "	SELECT a.`id` AS `proxy_id`, a.`firstname`, a.`lastname`, b.`group`, b.`role`, a.`email`
                    FROM `".AUTH_DATABASE."`.`user_data` AS a
                    LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
                    ON a.`id` = b.`user_id`
                    WHERE a.`id` NOT IN (".$excluded_ids.")
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

		$results = $db->GetAll($query, array(time(), time()));
		return $results;
	}

    /**
     * Gets the first proxy_id of the user entry matched by comparing value against supplied column
     * @param value - the value to find in the database
     * @param field_name - the field name in the user_data table to match against
     * @return int proxy_id+
     */
    public static function fetchProxyBySuppliedField($value, $field_name = "number") {
        global $db;
        $query = "  SELECT `id` FROM `".AUTH_DATABASE."`.`user_data`
                    WHERE `".$field_name."` = ?";
        $id = $db->GetOne($query, array($value));
        return $id;
    }
}
