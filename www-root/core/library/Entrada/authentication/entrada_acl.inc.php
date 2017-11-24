<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Entrada Resource Tree Builder
 *
 * Used to create an ACL tree of Zend_ACL resources for application of permissions after.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Harry Brundage <hbrundage@qmed.ca>
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 */
class Entrada_ACL extends ACL_Factory {
	var $acl;
	var $default_ptable;
	var $ptable;
	var $modules = array (
		"mom" => array (
			"awards",
			"community" => array(
				"communitydiscussion" => array(
					"communitydiscussiontopic"
				),
				"communityfolder",
				"communityfile",
                "communitylink",
                "communityhtml"
			),
			"communityadmin",
			"configuration",
			"course" => array (
				"coursecontent",
                "coursegroup",
				"event" => array (
					"eventcontent"
				)
			),
			"evaluation" => array (
				"evaluationform" => array (
					"evaluationformquestion"
				),
				"evaluationquestion"
			),			
			"gradebook" => array(
				"assessment",
				"assignment"
			),						
			"regionaled" => array (
				"apartments",
				"regions",
				"schedules"
			),
			"regionaled_tab",
			"dashboard",
			"clerkship" => array (
				"electives",
				"logbook",
				"lottery",
                "categories"
			),
			"term",
			"objective",
			"clerkshipschedules",
			"discussion",
			"photo",
			"firstlogin",
			"library",
			"people",
			"podcast",
			"profile" => array(
				"mspr"
			),
			"observerships",
			"search",
			"notice",
			"permission",
			"poll",
			"report",
			"reportindex",
			"quiz" => array (
				"quizquestion",
				"quizresult"
			),
			"user" => array (
				"incident",
				"metadata"
			),
			"assistant_support",
			"resourceorganisation",
			"evaluations" => array (
									"forms",
									"notifications",
									"reports"
									),
			"annualreport",
			"annualreportadmin",
			"anonymous-feedback",
			"mydepartment",
			"myowndepartment",
			"group",
            "encounter_tracking",
			"eportfolio",
			"eportfolio-artifact",
			"masquerade",
			"exam",
            "examdashboard",
            "examquestion",
			"examquestiongroup",
			"examquestiongroupindex",
            "examfolder",
            "examgradefnb",
			"secure" => array(
				"secureaccesskey",
				"secureaccessfile"
			),
      "assessments",
      "assessmentcomponent",
      "rotationschedule",
      "assessor",
      "assessmentresult",
      "assessmentprogress",
      "academicadvisor",
      "assessmentreportadmin",
      "sandbox",
		)
	);
	/**
	 * Constructs the ACL upon instantiation of the class
	 *
	 * @param array $user,ils The user for which the ACL is being constructed details. $_SESSION["details"] is usually used
	 */
	function __construct($userdetails) {

		global $db;

		$this->default_ptable = "`".AUTH_DATABASE."`.`acl_permissions`";

		//Fetch all the different users this current user could masquerade as.
		$query = "	SELECT a.*, b.`id` AS `proxy_id`, CONCAT_WS(', ', b.`lastname`, b.`firstname`) AS `fullname`, b.`firstname`, b.`lastname`, b.`organisation_id`, c.`role`, c.`group`, c.`id` AS `access_id`
					FROM `permissions` AS a
					JOIN `".AUTH_DATABASE."`.`user_data` AS b
					ON b.`id` = a.`assigned_by`
					JOIN `".AUTH_DATABASE."`.`user_access` AS c
					ON c.`user_id` = b.`id` AND c.`app_id`=".$db->qstr(AUTH_APP_ID)."
					AND c.`account_active`='true'
					AND (c.`access_starts`='0' OR c.`access_starts`<=".$db->qstr(time()).")
					AND (c.`access_expires`='0' OR c.`access_expires`>=".$db->qstr(time()).")
					WHERE a.`assigned_to`=".$db->qstr($userdetails["id"])."
					AND a.`valid_from` <= ".$db->qstr(time())."
					AND a.`valid_until` >= ".$db->qstr(time())."
					ORDER BY `fullname` ASC";
		$results = $db->GetAll($query);
		if ($results) {
			foreach ($results as $result) {
				$permissions[$result["access_id"]] = array("id" => $result["proxy_id"], "access_id" => $result["access_id"], "permission_id" => $result["permission_id"], "group" => $result["group"], "role" => $result["role"], "organisation_id" => $result["organisation_id"], "starts" => $result["valid_from"], "expires" => $result["valid_until"], "fullname" => $result["fullname"], "firstname" => $result["firstname"], "lastname" => $result["lastname"]);
			}
		}

		// Add all user_access records to the $permissions tree by organisation.
		$query = "	SELECT b.`id` AS `proxy_id`, c.`id` as ua_id, e.`organisation_id`, e.`organisation_title`, CONCAT_WS(', ', b.`lastname`, b.`firstname`) AS `fullname`, b.`firstname`, b.`lastname`, c.`role`, c.`group`
					FROM `".AUTH_DATABASE."`.`user_data` AS b
					JOIN `".AUTH_DATABASE."`.`user_access` AS c
					ON c.`user_id` = b.`id`
					AND b.`id` = " . $db->qstr($userdetails["id"]) . "
					AND c.`app_id`=".$db->qstr(AUTH_APP_ID)."
					AND c.`account_active`='true'
					AND (c.`access_starts`='0' OR c.`access_starts`<=".$db->qstr(time()).")
					AND (c.`access_expires`='0' OR c.`access_expires`>=".$db->qstr(time()).")
					JOIN `".AUTH_DATABASE."`.`organisations` AS e
					ON e.`organisation_id` = c.`organisation_id`
					ORDER BY c.`group` ASC";
		$results = $db->GetAll($query);
		if ($results) {
			foreach ($results as $result) {
				$permissions[$result["ua_id"]] = array("group" => $result["group"], "role" => $result["role"], "organisation_id" => $result["organisation_id"], "fullname" => $result["fullname"], "firstname" => $result["firstname"], "lastname" => $result["lastname"]);
			}
		}

		// Next, fetch all the role-resource permissions related to all these users.
		$this->rr_permissions = $this->_fetchPermissions($permissions);

		/**
		 * Next, "Clean" the permissions. This should create a permissions record for each active user_access record associated with
		 * the user id passed in from the acl_permissions table. This may need to change so the permissions table simply uses an
		 * access_id in the future for more granular permissions granting, but in the mean-time, this will ensure each access record for
		 * the user will be granted the same custom access from a user `entity_type` record.
		 */
		$clean_permissions = array();
		foreach ($this->rr_permissions as $permissions_record) {
			if ($permissions_record["entity_type"] != "user") {
				$clean_permissions[] = $permissions_record;
			} else {
				$query = "	SELECT `id`
							FROM `".AUTH_DATABASE."`.`user_access`
							WHERE `user_id` = ".$db->qstr($permissions_record["entity_value"])."
							AND `app_id` = ".$db->qstr(AUTH_APP_ID)."
							AND `account_active` = 'true'
							AND (`access_starts` = '0' OR `access_starts` <= ".$db->qstr(time()).")
							AND (`access_expires` = '0' OR `access_expires` >= ".$db->qstr(time()).")";
				$access_ids = $db->getAll($query);
				if ($access_ids) {
					foreach ($access_ids as $access_id) {
						$permissions_record["entity_value"] = $access_id["id"];
						$clean_permissions[] = $permissions_record;
					}
				}
			}
		}

		// This adds all the resources referenced by the permissions to the ACL.
		$acl = $this->_build($permissions, $clean_permissions);

		// Add generic roles
		foreach (array("organisation", "group", "role", "user") as $entity_type) {
			$acl->addRole(new Zend_Acl_Role($entity_type));
		}

        // Add Organisations
		foreach ($permissions as $access_id => $permission_mask) {
			$cur_organisation_id = $permission_mask["organisation_id"];

			if (!$acl->hasRole("organisation".$cur_organisation_id)) {
				$acl->addRole(new Zend_Acl_Role("organisation".$cur_organisation_id), "organisation");
			}
        }

        // Prepare an array containing all the organisations that a group belongs to
        // This is needed because you have to add all the organisations at the time you create the group
        $group_organisations = array();
        foreach ($permissions as $access_id => $permission_mask) {
            $group_organisations[$permission_mask["group"]][] = "organisation".$permission_mask["organisation_id"];
        }

        // Now add Groups, Roles and Users
		foreach ($permissions as $access_id => $permission_mask) {
			$cur_access_id = $access_id;
			$cur_role = $permission_mask["role"];
			$cur_group = $permission_mask["group"];

			if (!$acl->hasRole("group".$cur_group)) {
				$acl->addRole(new Zend_Acl_Role("group".$cur_group), array_merge(array("group"), $group_organisations[$cur_group]));
			}

			if (!$acl->hasRole("role".$cur_role)) {
				$acl->addRole(new Zend_Acl_Role("role".$cur_role), array("role", "group".$cur_group));
			}

			$user_role = new Zend_Acl_Role("user".$cur_access_id);
			$acl->addRole($user_role, array("role".$cur_role, "user"));
		}

		//Instantiate ACL_Factory to facilitate application of rules
		$this->acl = new ACL_Factory($acl);

		//Create the final ACL
		$this->acl->create_acl($clean_permissions);
	}

	/**
	 * Asks the ACL if the $user is allowed to preform the $action on the $resource. Asserts by default.
	 *
	 * @param string|Zend_Acl_Role_Interface $user Either the string identifier or role object for the user being queried
	 * @param string|Zend_Acl_Resource_Interface $resource Either the string identifier or the resource object for the resource being queried
	 * @param string $action The action or priviledge being queried with.
	 * @param boolean $assert If false, any rules applying to this role resource pair but contingent on assertions will be counted, regardless of the assertion's outcome. Warning: the assertion applied must support this property.
	 * @return boolean
	 */
	function isAllowed($user, $resource, $action, $assert = true) {
		if ($resource instanceof Zend_Acl_Resource_Interface) {
			$resource->assert = $assert;
		} else {
		 	$resource = new EntradaAclResource($resource, $assert);
		}

		if (!($user instanceof Zend_Acl_Role_Interface)) {
			$user = new EntradaUser($user);
		}

		return $this->acl->isAllowed($user, $resource, $action);
	}

	/**
	 * Asks the ACL if the user role defined by the active proxy_id (the active permission mask) is allowed to preform the $action on the $resource. Asserts by default.
	 *
	 * @param string|Zend_Acl_Resource_Interface $resource
	 * @param <type> $action
	 * @param <type> $assert
	 * @return <type>
	 */
	function amIAllowed($resource, $action, $assert = true) {
		global $ENTRADA_USER;

		$user = new EntradaUser("user".$ENTRADA_USER->getAccessId());
		$current_details = $_SESSION["details"];
		$current_details["access_id"] = $ENTRADA_USER->getAccessId();
		$current_details["role"] = $ENTRADA_USER->getActiveRole();
		$current_details["group"] = $ENTRADA_USER->getActiveGroup();
		$current_details["organisation_id"] = $ENTRADA_USER->getActiveOrganisation();
		$user->details = $current_details;
		return $this->isAllowed($user, $resource, $action, $assert);
	}

	/**
	 * Placed at the beginning of a route file, this checks if the current user is authorized and has the right permissions to interact with that page.
	 * @param  string  $resource           Ex. "gradebook"
	 * @param  string  $action             Ex. "read", "update"
	 * @param  boolean $assert             
	 * @param  array   $constants_to_check Ex. array("IN_PARENT", "IN_GRADEBOOK")
	 * @return boolean                     If user is authorized and has permissions to proceed, return true
	 */
	function isUserAuthorized($resource, $action, $assert = true, $constants_to_check = array()) {

		// First, check for constants such as 'IN_PARENT' or 'IN_GRADEBOOK'
		if (!empty($constants_to_check)) {
			foreach ($constants_to_check as $constant) {
				if (!defined($constant)) {
					return false;
				}
			}
		}

		// Next, check if user is authorized. If not, redirect to homepage
		if ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
			header("Location: ".ENTRADA_URL);
			return false;

		// Next check if user has the permissions necessary
		} elseif (!$this->amIAllowed($resource, $action, $assert)) {
			$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

			$ERROR++;
			$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

			echo display_error();

			application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");

			return false;
		}

		// Returns true when user passes all of the auth conditions
		return true;
	}

	/**
	 * Asks the ACL if the currently logged in user role $ENTRADA_USER->getID() is allowed to preform the $action on the $resource. Asserts by default.
	 *
	 * @param string|Zend_Acl_Resource_Interface $resource
	 * @param <type> $action
	 * @param <type> $assert
	 * @return <type>
	 */
	function isLoggedInAllowed($resource, $action, $assert = true) {
		global $ENTRADA_USER;
		$user = new EntradaUser("user".$ENTRADA_USER->getAccessId());
		$user->details = $_SESSION["details"];
		return $this->isAllowed($user, $resource, $action, $assert);
	}

	/**
	 * Constructs and populates Zend_Acl_Interface
	 * with all the resources referenced by the role-resource permissions generated in _fetchPermissions().
	 *
	 * @param array $permission_masks An array of possible proxy ids
	 * @param array $rr_permissions Optional array of role-resource permissions as returned from the database. If not given, they will be fetched based on the supplied permission masks.
	 */
	function _build($permission_masks, $rr_permissions = null) {
		global $db;

		if (!isset($rr_permissions)) {
			$rr_permissions = $this->_fetchPermissions($permission_masks);
		}
		//First, add the base roles for each type of entity
		$acl = new Zend_Acl_Plus();

		$this->_parseResourceTree(null, $this->modules, $acl);

		foreach ($rr_permissions as $perm) {
			if (isset($perm["resource_type"]) && isset($perm["resource_value"]) && !$acl->has($perm["resource_type"].$perm["resource_value"])) {
				$acl->add(new Zend_Acl_Resource($perm["resource_type"].$perm["resource_value"]), $perm["resource_type"]);
			}
		}
		return $acl;
	}

	/**
	 * Takes a nested array of resources and parses them into the supplied ACL with inheritance intact. Operates only on the ACL supplied.
	 *
	 * @param string $parent The parent resource to be set for the resources given
	 * @param array $resources The optionally nested array of resources to be parsed into the ACL
	 * @param Zend_Acl $acl The acl object to be operated on
	 * @return boolean
	 */
	function _parseResourceTree($parent, $resources, &$acl) {
		if (!isset($resources)) {
			return false;
		}
		if (is_array($resources)) {
			foreach ($resources as $key => $value) {
				if (is_array($value)) {
					$acl->add(new Zend_Acl_Resource($key), $parent);
					$this->_parseResourceTree($key, $value, $acl);
				} else {
					$acl->add(new Zend_Acl_Resource($value), $parent);
				}
			}
		}
		return true;
	}

	/**
	 * 	Fetches all the relevant role-resource permissions (those pertinent to the possbile masks) from the default permissions table
	 *
	 * @param  array $permission_masks An array of possible proxy ids
	 * @return array
	 */
	function _fetchPermissions($permission_masks) {
		global $db;
		//Next, fetch all the role-resource permissions related to all these users.
		$table = $this->default_ptable;
		$query[] = "SELECT * FROM $table WHERE \n";
		$count = 0;
		foreach ($permission_masks as $access_id => $permission_mask) {
			// Initialize variables for use throughout creation
			$cur_access_id = $access_id;
			$cur_role = $permission_mask["role"];
			$cur_group = $permission_mask["group"];
			$cur_organisation_id = $permission_mask["organisation_id"];

			$access_query = "SELECT `user_id` FROM `".AUTH_DATABASE."`.`user_access` WHERE `id` = ".$db->qstr($cur_access_id);
			$cur_proxy_id = $db->GetOne($access_query);

			$query[] = ($count && $count > 0 ? "OR " : "(")."($table.`entity_value` = '".$cur_proxy_id."' AND $table.`entity_type` = 'user') OR
								($table.`entity_value` = '".$cur_role."' AND $table.`entity_type` = 'role') OR
								($table.`entity_value` = '".$cur_group."' AND $table.`entity_type` = 'group') OR
								($table.`entity_value` = '".$cur_organisation_id."' AND $table.`entity_type` = 'organisation') OR
								($table.`entity_value` = '".$cur_group.":".$cur_role."' AND $table.`entity_type` = 'group:role') OR
								($table.`entity_value` = '".$cur_organisation_id.":".$cur_group."' AND $table.`entity_type` = 'organisation:group') OR
								($table.`entity_value` = '".$cur_organisation_id.":".$cur_group.":".$cur_role."' AND $table.`entity_type` = 'organisation:group:role') ";
			$count++;
		}

		$query[] = "OR ($table.`entity_value` IS NULL AND $table.`entity_type` IS NULL))\n";
		$query[] = "AND ($table.`app_id` IS NULL OR $table.`app_id` = '".AUTH_APP_ID."')\n";
		$query[] = "ORDER BY $table.`resource_value` ASC, $table.`entity_value` ASC;";

		$complete_query = "";
		foreach ($query as $part) {
			$complete_query .= $part;
		}

		return $db->GetAll($complete_query);
	}
}

class MultipleAssertion implements Zend_Acl_Assert_Interface {
	var $assertions = array();

	function MultipleAssertion($a_assertions) {
		$this->assertions = $a_assertions;
	}

	public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {
		foreach ($this->assertions as $assertion) {
			$name = $assertion."Assertion";
			$assertion = new $name();
			if (!$assertion->assert($acl, $role, $resource, $privilege)) {
				return false;
			}
		}
		return true;
	}
}

/**
 * Assessor Assertion
 *
 * Used to assert that the assessor is allowed to complete the form associated with the supplied progress id.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Don Zuiker <don.zuiker@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 */
class AssessorAssertion implements Zend_Acl_Assert_Interface {
    /**
     * Asserts that an Assessor has access to an Assessment.
     *
     * @param Zend_Acl $acl The ACL object isself (the one calling the assertion)
     * @param Zend_Acl_Role_Interface $role The role being queried
     * @param Zend_Acl_Resource_Interface $resource The resource being queried
     * @param string $privilege The privilege being queried
     * @return boolean
     */
    public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {

        if (!($resource instanceof AssessorResource)) {
            return false;
        }
        if (!isset($resource->dassessment_id)) {
            return false;
        }

        $role = $acl->_entrada_last_query_role;
        if (!isset($role->details["id"])) {
            return false;
        }
        
        $distribution_assessment = Models_Assessments_Assessor::fetchRowByID($resource->dassessment_id);
        if ($distribution_assessment){
            if ($distribution_assessment->getAssessorType() == "internal" && $distribution_assessment->getAssessorValue() == $role->details["id"]) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if the $user_id is a director or program coordinator of a course.
     *
     * @param string|integer $user_id The proxy_id to be checked
     * @param string|integer $aprogress_id The course id to be checked
	 * @param integer $adistribution_id
     * @return boolean
     */
    static function _checkAssessor($user_id, $aprogress_id, $adistribution_id) {
        //Logic taken from the old permissions_check() function.
        global $db;

        //ToDo: Add check for other types of assessors.
        $query	=  "SELECT *
                    FROM `cbl_assessment_distribution_assessors` a
                    JOIN `cbl_assessment_distributions` b
                    ON a.`adistribution_id` = b.`adistribution_id`
                    WHERE a.`adistribution_id` = ?
                    AND a.`assessor_value` = ?
                    AND a.`assessor_type` = 'proxy_id'
                    AND (a.`assessor_end` IS NULL
                        OR a.`assessor_end` > ?)
                    AND b.`deleted_date` IS NULL";
        $result = $db->GetRow($query, array($adistribution_id, $user_id, time()));

        if ($result) {
            if ($aprogress_id) {
                $query	=  "SELECT *
                            FROM `cbl_assessment_progress` a
                            WHERE a.`aprogress_id` = ?
                            AND a.`proxy_id` = ?
                            AND a.`deleted_date` IS NULL";

                $result = $db->GetRow($query, array($aprogress_id, $user_id));

                if ($result) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return true;
            }
        }

        return false;
    }
}


/**
 * Course Owner Assertion
 *
 * Used to assert that the course referenced by the course resource is owned by the user referenced by the user role.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Harry Brundage <hbrundage@qmed.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 */
class CourseOwnerAssertion implements Zend_Acl_Assert_Interface {
	/**
	* Asserts that the role references the director, coordinator, or secondary director of the course resource
	*
	* @param Zend_Acl $acl The ACL object isself (the one calling the assertion)
	* @param Zend_Acl_Role_Interface $role The role being queried
	* @param Zend_Acl_Resource_Interface $resource The resource being queried
	* @param string $privilege The privilege being queried
	* @return boolean
	*/
	public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {
		global $db;
		//If asserting is off then return true right away
		if ((isset($resource->assert) && $resource->assert == false) || (isset($acl->_entrada_last_query) && isset($acl->_entrada_last_query->assert) && $acl->_entrada_last_query->assert == false)) {
			return true;
		}

		if (isset($resource->course_id)) {
			$course_id = $resource->course_id;
		} else if (isset($acl->_entrada_last_query->course_id)) {
			$course_id = $acl->_entrada_last_query->course_id;
		} else {
			//Parse out the user ID and course ID
			$resource_id = $resource->getResourceId();
			$resource_type = preg_replace('/[0-9]+/', "", $resource_id);

			if ($resource_type !== "course" && $resource_type !== "coursecontent" && $resource_type !== "coursegroup") {
				//This only asserts for users on courses.
				return false;
			}

			$course_id = preg_replace('/[^0-9]+/', "", $resource_id);
		}

		$role_id = $role->getRoleId();
		$access_id	= preg_replace('/[^0-9]+/', "", $role_id);

		$query = "SELECT `user_id` FROM `".AUTH_DATABASE."`.`user_access` WHERE `id` = ".$db->qstr($access_id);
		$user_id = $db->GetOne($query);
		if (!isset($user_id) || !$user_id) {
			$role_id = $acl->_entrada_last_query_role->getRoleId();
			$access_id	= preg_replace('/[^0-9]+/', "", $role_id);

			$query = "SELECT `user_id` FROM `".AUTH_DATABASE."`.`user_access` WHERE `id` = ".$db->qstr($access_id);
			$user_id = $db->GetOne($query);
		}

		return $this->_checkCourseOwner($user_id, $course_id);
	}

	/**
	 * Checks if the $user_id is a director or program coordinator of a course.
	 *
	 * @param string|integer $user_id The proxy_id to be checked
	 * @param string|integer $course_id The course id to be checked
	 * @return boolean
	 */
	static function _checkCourseOwner($user_id, $course_id) {
		//Logic taken from the old permissions_check() function.
		global $db;

		$query	=  "SELECT a.`pcoord_id` AS `coordinator`, b.`proxy_id` AS `director_id`, d.`proxy_id` AS `admin_id`, e.`proxy_id` AS `pcoordinator`
					FROM `".DATABASE_NAME."`.`courses` AS a
					LEFT JOIN `".DATABASE_NAME."`.`course_contacts` AS b
					ON b.`course_id` = a.`course_id`
					AND b.`contact_type` = 'director'
					LEFT JOIN `".DATABASE_NAME."`.`community_courses` AS c
					ON c.`course_id` = a.`course_id`
					LEFT JOIN `".DATABASE_NAME."`.`community_members` AS d
					ON d.`community_id` = c.`community_id`
					AND d.`member_active` = '1'
					AND d.`member_acl` = '1'
					LEFT JOIN `".DATABASE_NAME."`.`course_contacts` AS e
					ON e.`course_id` = a.`course_id`
					AND (e.`contact_type` = 'pcoordinator'
                        OR e.`contact_type` = 'ccoordinator')
					WHERE a.`course_id` = ".$db->qstr($course_id)."
					AND (a.`pcoord_id` = ".$db->qstr($user_id)."
						OR b.`proxy_id` = ".$db->qstr($user_id)."
						OR d.`proxy_id` = ".$db->qstr($user_id)."
						OR e.`proxy_id` = ".$db->qstr($user_id)."
					)
					AND a.`course_active` = '1'
					LIMIT 0, 1";
		$result = $db->GetRow($query);
		if ($result) {
			foreach (array("director_id", "coordinator", "admin_id", "pcoordinator") as $owner) {
				if ($result[$owner] == $user_id) {
					return true;
				}
			}
		}

		return false;
	}
}

/**
 * Not Course Owner Assertion
 *
 * Used to assert that the course referenced by the course resource is not owned by the user referenced by the user role.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Harry Brundage <hbrundage@qmed.ca>, Don Zuiker <don.zuiker@queensu.ca>
 * @copyright Copyright 2010, 2013 Queen's University. All Rights Reserved.
 */
class NotCourseOwnerAssertion implements Zend_Acl_Assert_Interface {
	/**
	* Asserts that the role references the director, coordinator, or secondary director of the course resource
	*
	* @param Zend_Acl $acl The ACL object isself (the one calling the assertion)
	* @param Zend_Acl_Role_Interface $role The role being queried
	* @param Zend_Acl_Resource_Interface $resource The resource being queried
	* @param string $privilege The privilege being queried
	* @return boolean
	*/
	public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {
		global $db;
		//If asserting is off then return true right away
		if ((isset($resource->assert) && $resource->assert == false) || (isset($acl->_entrada_last_query) && isset($acl->_entrada_last_query->assert) && $acl->_entrada_last_query->assert == false)) {
			return false;
		}

		if (isset($resource->course_id)) {
			$course_id = $resource->course_id;
		} else if (isset($acl->_entrada_last_query->course_id)) {
			$course_id = $acl->_entrada_last_query->course_id;
		} else {
			//Parse out the user ID and course ID
			$resource_id = $resource->getResourceId();
			$resource_type = preg_replace('/[0-9]+/', "", $resource_id);

			if ($resource_type !== "course" && $resource_type !== "coursecontent") {
				//This only asserts for users on courses.
				return false;
			}

			$course_id = preg_replace('/[^0-9]+/', "", $resource_id);
		}

		$role_id = $role->getRoleId();
		$access_id	= preg_replace('/[^0-9]+/', "", $role_id);

		$query = "SELECT `user_id` FROM `".AUTH_DATABASE."`.`user_access` WHERE `id` = ".$db->qstr($access_id);
		$user_id = $db->GetOne($query);
		if (!isset($user_id) || !$user_id) {
			$role_id = $acl->_entrada_last_query_role->getRoleId();
			$access_id	= preg_replace('/[^0-9]+/', "", $role_id);

			$query = "SELECT `user_id` FROM `".AUTH_DATABASE."`.`user_access` WHERE `id` = ".$db->qstr($access_id);
			$user_id = $db->GetOne($query);
		}

		return $this->_checkCourseOwner($user_id, $course_id);
	}

	/**
	 * Checks if the $user_id is a director or program coordinator of a course.
	 *
	 * @param string|integer $user_id The proxy_id to be checked
	 * @param string|integer $course_id The course id to be checked
	 * @return boolean
	 */
	static function _checkCourseOwner($user_id, $course_id) {
		//Logic taken from the old permissions_check() function.
		global $db;

		$query	=  "SELECT a.`pcoord_id` AS `coordinator`, b.`proxy_id` AS `director_id`, d.`proxy_id` AS `admin_id`, e.`proxy_id` AS `pcoordinator`
					FROM `".DATABASE_NAME."`.`courses` AS a
					LEFT JOIN `".DATABASE_NAME."`.`course_contacts` AS b
					ON b.`course_id` = a.`course_id`
					AND b.`contact_type` = 'director'
					LEFT JOIN `".DATABASE_NAME."`.`community_courses` AS c
					ON c.`course_id` = a.`course_id`
					LEFT JOIN `".DATABASE_NAME."`.`community_members` AS d
					ON d.`community_id` = c.`community_id`
					AND d.`member_active` = '1'
					AND d.`member_acl` = '1'
					LEFT JOIN `".DATABASE_NAME."`.`course_contacts` AS e
					ON e.`course_id` = a.`course_id`
					AND (e.`contact_type` = 'pcoordinator'
                        OR e.`contact_type` = 'ccoordinator')
					WHERE a.`course_id` = ".$db->qstr($course_id)."
					AND (a.`pcoord_id` = ".$db->qstr($user_id)."
						OR b.`proxy_id` = ".$db->qstr($user_id)."
						OR d.`proxy_id` = ".$db->qstr($user_id)."
						OR e.`proxy_id` = ".$db->qstr($user_id)."
					)
					AND a.`course_active` = '1'
					LIMIT 0, 1";

		$result = $db->GetRow($query);
		if ($result) {
			foreach (array("director_id", "coordinator", "admin_id", "pcoordinator") as $owner) {
				if ($result[$owner] == $user_id) {
					return false;
				}
			}
		}

		return true;
	}
}

/**
 * Course Enrollment Assertion
 *
 * Used to assert that proxy_id is enrolled in a particular course based on their membership status
 * in the corresponding course website (community).
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 */
class CourseEnrollmentAssertion implements Zend_Acl_Assert_Interface {

	public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {
		global $db;
		//If asserting is off then return true right away
		if ((isset($resource->assert) && $resource->assert == false) || (isset($acl->_entrada_last_query) && isset($acl->_entrada_last_query->assert) && $acl->_entrada_last_query->assert == false)) {
			return false;
		}

		if (isset($resource->course_id)) {
			$course_id = $resource->course_id;
		} else if (isset($acl->_entrada_last_query->course_id)) {
			$course_id = $acl->_entrada_last_query->course_id;
		} else {
			// Parse out the user ID and course ID
			$resource_id = $resource->getResourceId();
			$resource_type = preg_replace('/[0-9]+/', "", $resource_id);

			if ($resource_type !== "course" && $resource_type !== "coursecontent") {
				// This only asserts for users on courses.
				return false;
			}

			$course_id = preg_replace("/[^0-9]+/", "", $resource_id);
		}

		$role_id = $role->getRoleId();
		$access_id = preg_replace('/[^0-9]+/', "", $role_id);

		$query = "SELECT `user_id` FROM `".AUTH_DATABASE."`.`user_access`
					WHERE `id` = ".$db->qstr($access_id);
		$user_id = $db->GetOne($query);

		if (!isset($user_id) || !$user_id) {
			$role_id = $acl->_entrada_last_query_role->getRoleId();
			$access_id	= preg_replace('/[^0-9]+/', "", $role_id);

			$query = "SELECT `user_id` FROM `".AUTH_DATABASE."`.`user_access`
						WHERE `id` = ".$db->qstr($access_id);
			$user_id = $db->GetOne($query);
		}
		return !($this->_checkCourseEnrollment($user_id, $course_id));
	}

	/**
	 * Checks if the $user_id is an active member of the corresponding
	 * course website (community).
	 *
	 * @param string|integer $user_id The proxy_id to be checked
	 * @param string|integer $course_id The course id to be checked
	 * @return boolean
	 */
	static function _checkCourseEnrollment($user_id, $course_id) {
        global $db;

		$query = "	SELECT *
					FROM `courses`
					WHERE `course_id` = " . $db->qstr($course_id);
		$result = $db->GetRow($query);

		if ($result["permission"] == "open") {
			return true;
		} else {
			$query = "	SELECT *
						FROM `community_courses`
						WHERE `course_id` = " . $db->qstr($course_id);
			$result = $db->GetRow($query);
			if ($result) {
				$query = "	SELECT *
							FROM `community_members`
							WHERE `community_id` = " . $db->qstr($result["community_id"]) . "
							AND `proxy_id` = " . $db->qstr($user_id) . "
							AND `member_active` = 1";
				$result = $db->GetRow($query);
				if ($result) {
					return true;
				}
			}
			$query = "  SELECT *
						FROM `course_audience`
						WHERE `course_id` = " . $db->qstr($course_id);
			$results = $db->GetAll($query);
			if ($results) {
				foreach ($results as $result) {
					switch ($result["audience_type"]) {
						case "proxy_id":
							if ($result["audience_value"] == $user_id) {
								return true;
							}
							break;
						case "group_id":
							$query = "  SELECT a.*
										FROM `group_members` AS a
										JOIN `groups` AS b
										ON a.`group_id` = b.`group_id`
										WHERE a.`group_id` = ".$db->qstr($result["audience_value"]) . "
										AND a.`proxy_id` = " . $db->qstr($user_id) . "
										AND a.`member_active` = 1
										AND b.`group_active` = 1
										AND (
											(UNIX_TIMESTAMP() >= a.`start_date` OR a.`start_date` = 0) AND
											(UNIX_TIMESTAMP() <= a.`finish_date` OR a.`finish_date` = 0)
										)";
							$result = $db->GetRow($query);

							if ($result) {
								return true;
							}
							break;
						default:
							break;
					}
				}
			}
			return false;
        }
    }
}

class NotEventEnrollmentAssertion implements Zend_Acl_Assert_Interface {
	public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {
		global $db;
		//If asserting is off then return false right away
		if ((isset($resource->assert) && $resource->assert == false) || (isset($acl->_entrada_last_query) && isset($acl->_entrada_last_query->assert) && $acl->_entrada_last_query->assert == false)) {
			return false;
		}

		if (isset($resource->event_id)) {
			$event_id = $resource->event_id;
		} else if (isset($acl->_entrada_last_query->event_id)) {
			$event_id = $acl->_entrada_last_query->event_id;
		} else {
			// Parse out the user ID and course ID
			$resource_id = $resource->getResourceId();
			$resource_type = preg_replace('/[0-9]+/', "", $resource_id);

			if ($resource_type !== "event") {
				// This only asserts for users on events.
				return false;
			}

			$event_id = preg_replace("/[^0-9]+/", "", $resource_id);
		}

		if (isset($resource->course_id)) {
			$course_id = $resource->course_id;
		} else if (isset($acl->_entrada_last_query->course_id)) {
			$course_id = $acl->_entrada_last_query->course_id;
		} else {
			// Parse out the user ID and course ID
			$resource_id = $resource->getResourceId();
			$resource_type = preg_replace('/[0-9]+/', "", $resource_id);

			if ($resource_type !== "event") {
				// This only asserts for users on events.
				return false;
			}

			$course_id = preg_replace("/[^0-9]+/", "", $resource_id);
		}

		$role_id = $role->getRoleId();
		$access_id	= preg_replace('/[^0-9]+/', "", $role_id);

		$query = "SELECT `user_id` FROM `".AUTH_DATABASE."`.`user_access`
					WHERE `id` = ".$db->qstr($access_id);
		$user_id = $db->GetOne($query);

		if (!isset($user_id) || !$user_id) {
			$role_id = $acl->_entrada_last_query_role->getRoleId();
			$access_id	= preg_replace('/[^0-9]+/', "", $role_id);

			$query = "SELECT `user_id` FROM `".AUTH_DATABASE."`.`user_access`
						WHERE `id` = ".$db->qstr($access_id);
			$user_id = $db->GetOne($query);
		}
		return !($this->_checkEventEnrollment($user_id, $event_id, $course_id));
	}

	/**
	 * Checks if the $user_id is an active member of the corresponding
	 * event.
	 *
	 * @param string|integer $user_id The proxy_id to be checked
	 * @param string|integer $event_id The event id to be checked
	 * @param string|integer $course_id The course id to be checked
	 * @return boolean
	 */
	static function _checkEventEnrollment($user_id, $event_id, $course_id) {
        global $db;

            $query = "    SELECT *
                        FROM `courses`
                        WHERE `course_id` = ".$db->qstr($course_id);
            $result = $db->GetRow($query);

            if ($result["permission"] == "open") {
                //return false so that the course resource acl permission is tested.
                return true;
            }

        $query = "    SELECT *
                    FROM `event_audience`
                    WHERE `event_id` = " . $db->qstr($event_id);
        $results = $db->GetAll($query);

        if ($results) {
            foreach($results as $result) {
                switch($result["audience_type"]) {
                    case "proxy_id":
                        if ($result["audience_value"] == $user_id) {
                            return true;
                        }
                        break;
                    case "cohort":
						$query = "  SELECT a.*
									FROM `group_members` AS a
									JOIN `groups` AS b
									ON a.`group_id` = b.`group_id`
									WHERE a.`group_id` = ".$db->qstr($result["audience_value"]) . "
									AND a.`proxy_id` = " . $db->qstr($user_id) . "
									AND a.`member_active` = 1
									AND b.`group_active` = 1
									AND (
										(UNIX_TIMESTAMP() >= a.`start_date` OR a.`start_date` = 0) AND
										(UNIX_TIMESTAMP() <= a.`finish_date` OR a.`finish_date` = 0)
									)";
                        $result = $db->GetRow($query);
                        if ($result) {
                            return true;
                        }
                        break;
                    case "group_id":
                    case "cgroup_id":
                        $query = "  SELECT *
                                    FROM `course_group_audience` cga
                                    JOIN `course_audience` AS ca
                                    ON `ca`.`course_id` = " .$db->qstr($course_id) ."
                                    JOIN `curriculum_periods` cp
                                    ON `ca`.`cperiod_id` = cp.`cperiod_id`
                                    WHERE cga.`cgroup_id` = ".$db->qstr($result["audience_value"]) . "
                                    AND cga.`proxy_id` = " . $db->qstr($user_id) . "
                                    AND    cga.`active` = 1";
                        $result = $db->GetRow($query);
                        if ($result) {
                            return true;
                        }
                        break;
                    case "course_id":
                        //hand off to course enrollment checking.
                        return true;
                    default:
                        break;
                }
            }
        }
        $query = "    SELECT *
                    FROM `course_contacts` AS cc
                    WHERE cc.`proxy_id` = " . $db->qstr($user_id) . "
                    AND cc.`course_id` = " . $db->qstr($course_id);

        $result = $db->GetRow($query);

        if ($result) {
            return true;
        }

        $query = "    SELECT *
                    FROM `event_contacts` AS ec
                    JOIN `events` as e
                    ON e.`event_id` = ec.`event_id`
                    WHERE ec.`proxy_id` = " . $db->qstr($user_id) . "
                    AND ec.`event_id` = " . $db->qstr($event_id) . "
                    AND e.`course_id` = " . $db->qstr($course_id);

        $result = $db->GetRow($query);

        if ($result) {
            return true;
        }

        return false;
    }
}

/**
 * Event Enrollment Assertion
 *
 * Used to assert that proxy_id is enrolled in a particular event based on their membership status
 * in the corresponding event audience.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Don Zuiker <don.zuiker@queensu.ca>
 * @copyright Copyright 2012 Queen's University. All Rights Reserved.
 */
class EventEnrollmentAssertion implements Zend_Acl_Assert_Interface {

	public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {
		global $db;
		//If asserting is off then return true right away
		if ((isset($resource->assert) && $resource->assert == false) || (isset($acl->_entrada_last_query) && isset($acl->_entrada_last_query->assert) && $acl->_entrada_last_query->assert == false)) {
			return true;
		}

		if (isset($resource->event_id)) {
			$event_id = $resource->event_id;
		} else if (isset($acl->_entrada_last_query->event_id)) {
			$event_id = $acl->_entrada_last_query->event_id;
		} else {
			// Parse out the user ID and course ID
			$resource_id = $resource->getResourceId();
			$resource_type = preg_replace('/[0-9]+/', "", $resource_id);

			if ($resource_type !== "event") {
				// This only asserts for users on events.
				return false;
			}

			$event_id = preg_replace("/[^0-9]+/", "", $resource_id);
		}

		if (isset($resource->course_id)) {
			$course_id = $resource->course_id;
		} else if (isset($acl->_entrada_last_query->course_id)) {
			$course_id = $acl->_entrada_last_query->course_id;
		} else {
			// Parse out the user ID and course ID
			$resource_id = $resource->getResourceId();
			$resource_type = preg_replace('/[0-9]+/', "", $resource_id);

			if ($resource_type !== "event") {
				// This only asserts for users on events.
				return false;
			}

			$course_id = preg_replace("/[^0-9]+/", "", $resource_id);
		}

		$role_id = $role->getRoleId();
		$access_id	= preg_replace('/[^0-9]+/', "", $role_id);

		$query = "SELECT `user_id` FROM `".AUTH_DATABASE."`.`user_access`
					WHERE `id` = ".$db->qstr($access_id);
		$user_id = $db->GetOne($query);

		if (!isset($user_id) || !$user_id) {
			$role_id = $acl->_entrada_last_query_role->getRoleId();
			$access_id	= preg_replace('/[^0-9]+/', "", $role_id);

			$query = "SELECT `user_id` FROM `".AUTH_DATABASE."`.`user_access`
						WHERE `id` = ".$db->qstr($access_id);
			$user_id = $db->GetOne($query);
		}
		return $this->_checkEventEnrollment($user_id, $event_id, $course_id);
	}

	/**
	 * Checks if the $user_id is an active member of the corresponding
	 * event.
	 *
	 * @param string|integer $user_id The proxy_id to be checked
	 * @param string|integer $event_id The event id to be checked
	 * @param string|integer $course_id The course id to be checked
	 * @return boolean
	 */
	static function _checkEventEnrollment($user_id, $event_id, $course_id) {
        global $db;

            $query = "    SELECT *
                        FROM `courses`
                        WHERE `course_id` = ".$db->qstr($course_id);
            $result = $db->GetRow($query);

            if ($result["permission"] == "open") {
                //return false so that the course resource acl permission is tested.
                return false;
            }

        $query = "    SELECT *
                    FROM `event_audience`
                    WHERE `event_id` = " . $db->qstr($event_id);
        $results = $db->GetAll($query);

        if ($results) {
            foreach($results as $result) {
                switch($result["audience_type"]) {
                    case "proxy_id":
                        if ($result["audience_value"] == $user_id) {
                            return true;
                        }
                        break;
                    case "cohort":
						$query = "  SELECT a.*
									FROM `group_members` AS a
									JOIN `groups` AS b
									ON a.`group_id` = b.`group_id`
									WHERE a.`group_id` = ".$db->qstr($result["audience_value"]) . "
									AND a.`proxy_id` = " . $db->qstr($user_id) . "
									AND a.`member_active` = 1
									AND b.`group_active` = 1
									AND (
										(UNIX_TIMESTAMP() >= a.`start_date` OR a.`start_date` = 0) AND
										(UNIX_TIMESTAMP() <= a.`finish_date` OR a.`finish_date` = 0)
									)";
                        $result = $db->GetRow($query);
                        if ($result) {
                            return true;
                        }
                        break;
                    case "group_id":
                    case "cgroup_id":
                        $query = "  SELECT *
                                    FROM `course_group_audience` cga
                                    JOIN `course_audience` AS ca
                                    ON `ca`.`course_id` = " .$db->qstr($course_id) ."
                                    JOIN `curriculum_periods` cp
                                    ON `ca`.`cperiod_id` = cp.`cperiod_id`
                                    WHERE cga.`cgroup_id` = ".$db->qstr($result["audience_value"]) . "
                                    AND cga.`proxy_id` = " . $db->qstr($user_id) . "
                                    AND    cga.`active` = 1";
                        $result = $db->GetRow($query);
                        if ($result) {
                            return true;
                        }
                        break;
                    case "course_id":
                        //hand off to course enrollment checking.
                        return false;
                    default:
                        break;
                }
            }
        }
        $query = "    SELECT *
                    FROM `course_contacts` AS cc
                    WHERE cc.`proxy_id` = " . $db->qstr($user_id) . "
                    AND cc.`course_id` = " . $db->qstr($course_id);

        $result = $db->GetRow($query);

        if ($result) {
            return true;
        }

        $query = "    SELECT *
                    FROM `event_contacts` AS ec
                    JOIN `events` as e
                    ON e.`event_id` = ec.`event_id`
                    WHERE ec.`proxy_id` = " . $db->qstr($user_id) . "
                    AND ec.`event_id` = " . $db->qstr($event_id) . "
                    AND e.`course_id` = " . $db->qstr($course_id);

        $result = $db->GetRow($query);

        if ($result) {
            return true;
        }

        return false;
    }
}

class IsEvaluatedAssertion implements Zend_Acl_Assert_Interface {

/**
 * Asserts that the role references the director, coordinator, or secondary director of the course resource
 *
 * @param Zend_Acl $acl The ACL object isself (the one calling the assertion)
 * @param Zend_Acl_Role_Interface $role The role being queried
 * @param Zend_Acl_Resource_Interface $resource The resource being queried
 * @param string $privilege The privilege being queried
 * @return boolean
 */
	public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {
		global $db;

		//If asserting is off then return true right away
		if ((isset($resource->assert) && $resource->assert == false) || (isset($acl->_entrada_last_query) && isset($acl->_entrada_last_query->assert) && $acl->_entrada_last_query->assert == false)) {
			return true;
		}
		$role_id = $role->getRoleId();
		$access_id	= preg_replace('/[^0-9]+/', "", $role_id);

		$query = "SELECT `user_id` FROM `".AUTH_DATABASE."`.`user_access`
					WHERE `id` = ".$db->qstr($access_id);
		$user_id = $db->GetOne($query);

		if (!isset($user_id) || !$user_id) {
			$role_id = $acl->_entrada_last_query_role->getRoleId();
			$access_id	= preg_replace('/[^0-9]+/', "", $role_id);

			$query = "SELECT `user_id` FROM `".AUTH_DATABASE."`.`user_access`
						WHERE `id` = ".$db->qstr($access_id);
			$user_id = $db->GetOne($query);
		}

		$query = "SELECT * FROM `".CLERKSHIP_DATABASE."`.`eval_completed` WHERE `instructor_id` = ".$db->qstr($user_id);
		$evaluated = $db->GetRow($query);

		if ($evaluated) {
			return 	true;
		} else {
			return false;
		}
	}
}

/**
 * Gradebook Owner Assertion
 *
 * Used to assert that the course referenced by the course resource is owned by the user referenced by the user role.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Harry Brundage <hbrundage@qmed.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 */
class GradebookOwnerAssertion extends CourseOwnerAssertion {

/**
 * Asserts that the role references the director, coordinator, or secondary director of the course resource
 *
 * @param Zend_Acl $acl The ACL object isself (the one calling the assertion)
 * @param Zend_Acl_Role_Interface $role The role being queried
 * @param Zend_Acl_Resource_Interface $resource The resource being queried
 * @param string $privilege The privilege being queried
 * @return boolean
 */
	public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {
		global $db;
		//If asserting is off then return true right away
		if ((isset($resource->assert) && $resource->assert == false) || (isset($acl->_entrada_last_query) && isset($acl->_entrada_last_query->assert) && $acl->_entrada_last_query->assert == false)) {
			return true;
		}

		if (isset($resource->course_id)) {
			$course_id = $resource->course_id;
		} else if (isset($acl->_entrada_last_query->course_id)) {
			$course_id = $acl->_entrada_last_query->course_id;
		} else {
			//Parse out the user ID and course ID
			$resource_id = $resource->getResourceId();
			$resource_type = preg_replace('/[0-9]+/', "", $resource_id);

			if ($resource_type !== "gradebook" && $resource_type !== "assessment") {
				//This only asserts for users on gradebooks.
				return false;
			}

			$course_id = preg_replace('/[^0-9]+/', "", $resource_id);
		}

		$role_id = $role->getRoleId();
		$access_id	= preg_replace('/[^0-9]+/', "", $role_id);

		$query = "SELECT `user_id` FROM `".AUTH_DATABASE."`.`user_access`
					WHERE `id` = ".$db->qstr($access_id);
		$user_id = $db->GetOne($query);

		if (!isset($user_id) || !$user_id) {
			$role_id = $acl->_entrada_last_query_role->getRoleId();
			$access_id	= preg_replace('/[^0-9]+/', "", $role_id);

			$query = "SELECT `user_id` FROM `".AUTH_DATABASE."`.`user_access`
						WHERE `id` = ".$db->qstr($access_id);
			$user_id = $db->GetOne($query);
		}
		// Inherited from course owner assertion
		return $this->_checkCourseOwner($user_id, $course_id);
	}
}

/**
 * Gradebook TA Assertion
 *
 * Used to assert that the course referenced by the course resource is accessible by the user referenced by the user role.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Eugene Bivol <ebivol@gmail.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */
class GradebookTAAssertion extends CourseOwnerAssertion {

	/**
	 * Asserts that the role references the director, coordinator, or secondary director of the course resource
	 *
	 * @param Zend_Acl $acl The ACL object isself (the one calling the assertion)
	 * @param Zend_Acl_Role_Interface $role The role being queried
	 * @param Zend_Acl_Resource_Interface $resource The resource being queried
	 * @param string $privilege The privilege being queried
	 * @return boolean
	 */
	public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {

		//If asserting is off then return true right away
		if ((isset($resource->assert) && $resource->assert == false) || (isset($acl->_entrada_last_query) && isset($acl->_entrada_last_query->assert) && $acl->_entrada_last_query->assert == false)) {
			return true;
		}

		if (isset($resource->course_id)) {
			$course_id = $resource->course_id;
		} else if (isset($acl->_entrada_last_query->course_id)) {
			$course_id = $acl->_entrada_last_query->course_id;
		} else {
			//Parse out the user ID and course ID
			$resource_id = $resource->getResourceId();
			$resource_type = preg_replace('/[0-9]+/', "", $resource_id);

			if ($resource_type !== "gradebook" && $resource_type !== "assessment") {
				//This only asserts for users on gradebooks.
				return false;
			}

			$course_id = preg_replace('/[^0-9]+/', "", $resource_id);
		}

		$role_id = $role->getRoleId();
		$access_id	= preg_replace('/[^0-9]+/', "", $role_id);

		$user = Models_User_Access::fetchRowByID($access_id);

		if (!isset($user) || !$user ) {
			$role_id = $acl->_entrada_last_query_role->getRoleId();
			$access_id	= preg_replace('/[^0-9]+/', "", $role_id);

			$user = Models_User_Access::fetchRowByID($access_id);
		}
		
		if (isset($user)) {
			$course_contact = Models_Course_Contact::fetchRowByProxyIDContactType($user->getUserID(), "ta");
			if($course_contact) {
				return true;
			}
		}
		return false;
	}
}


/**
 * GradebookDropbox Assertion
 *
 * Assert true if access should be granted to a gradebook for a user that is a dropbox contact for an assignment.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Harry Brundage <hbrundage@qmed.ca>, Don Zuiker <zuikerd@qmed.ca>
 * @copyright Copyright 2010, 2013 Queen's University. All Rights Reserved.
 */
class GradebookDropboxAssertion extends CourseOwnerAssertion {	
/**
 *
 * @param Zend_Acl $acl The ACL object isself (the one calling the assertion)
 * @param Zend_Acl_Role_Interface $role The role being queried
 * @param Zend_Acl_Resource_Interface $resource The resource being queried
 * @param string $privilege The privilege being queried
 * @return boolean
 */
	public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {
		global $db;			
		
		//If asserting is off then return true right away
		if ((isset($resource->assert) && $resource->assert == false) || (isset($acl->_entrada_last_query) && isset($acl->_entrada_last_query->assert) && $acl->_entrada_last_query->assert == false)) {
			return true;
		}
		
		if (isset($resource->course_id)) {
			$course_id = $resource->course_id;
		} else if (isset($acl->_entrada_last_query->course_id)) {
			$course_id = $acl->_entrada_last_query->course_id;
		} else {
			//Parse out the user ID and course ID
			$resource_id = $resource->getResourceId();
			$resource_type = preg_replace('/[0-9]+/', "", $resource_id);						

			if ($resource_type !== "gradebook" && $resource_type !== "assessment") {
				//This only asserts for users on gradebooks.
				return false;
			}

			$course_id = preg_replace('/[^0-9]+/', "", $resource_id);
		}
		
		$role_id = $role->getRoleId();
		$access_id	= preg_replace('/[^0-9]+/', "", $role_id);

		$query = "SELECT `user_id` FROM `".AUTH_DATABASE."`.`user_access`
					WHERE `id` = ".$db->qstr($access_id);
		$user_id = $db->GetOne($query);

		if (!isset($user_id) || !$user_id) {
			$role_id = $acl->_entrada_last_query_role->getRoleId();
			$access_id	= preg_replace('/[^0-9]+/', "", $role_id);

			$query = "SELECT `user_id` FROM `".AUTH_DATABASE."`.`user_access`
						WHERE `id` = ".$db->qstr($access_id);
			$user_id = $db->GetOne($query);
		}					
        if ($this->_checkGradebookDropbox($user_id, $course_id)) {
            return true;
        } else {
            return $this->_checkCourseOwner($user_id, $course_id);
        }
	}
	
	static function _checkGradebookDropbox($user_id, $course_id) {
		global $db;		
		
		$query		= "	SELECT *
						FROM `assignment_contacts` a
						JOIN `assignments` b
						ON a.`assignment_id` = b.`assignment_id`
						WHERE a.`proxy_id` = " . $db->qstr($user_id) . "
						AND b.`assignment_active` = 1
						AND b.`course_id` = " . $db->qstr($course_id);			
		$results	= $db->GetAll($query);
		
		if ($results) {
			return true;
		} else {
			return false;
		}
	}
}

/**
 * AssignmentContact Assertion
 *
 * Assert true if access should be granted to an assignment for a user that is listed as an Assignment Contact.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Harry Brundage <hbrundage@qmed.ca>, Don Zuiker <zuikerd@qmed.ca>
 * @copyright Copyright 2010, 2013 Queen's University. All Rights Reserved.
 */
class AssignmentContactAssertion implements Zend_Acl_Assert_Interface {
/**
 *
 * @param Zend_Acl $acl The ACL object isself (the one calling the assertion)
 * @param Zend_Acl_Role_Interface $role The role being queried
 * @param Zend_Acl_Resource_Interface $resource The resource being queried
 * @param string $privilege The privilege being queried
 * @return boolean
 */
	public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {
		global $db;

		//If asserting is off then return true right away
		if ((isset($resource->assert) && $resource->assert == false) || (isset($acl->_entrada_last_query) && isset($acl->_entrada_last_query->assert) && $acl->_entrada_last_query->assert == false)) {
			return true;
		}

		if (isset($resource->assignment_id)) {
			$assignment_id = $resource->assignment_id;
		} else if (isset($acl->_entrada_last_query->assignment_id)) {
			$assignment_id = $acl->_entrada_last_query->assignment_id;
		} else {
			//Parse out the user ID and course ID
			$resource_id = $resource->getResourceId();
			$resource_type = preg_replace('/[0-9]+/', "", $resource_id);

			if ($resource_type !== "assignment") {
				//This only asserts for users on gradebooks.
				return false;
			}

			$assignment_id = preg_replace('/[^0-9]+/', "", $resource_id);
		}

		$role_id = $role->getRoleId();
		$access_id	= preg_replace('/[^0-9]+/', "", $role_id);

		$query = "SELECT `user_id` FROM `".AUTH_DATABASE."`.`user_access`
					WHERE `id` = ".$db->qstr($access_id);
		$user_id = $db->GetOne($query);

		if (!isset($user_id) || !$user_id) {
			$role_id = $acl->_entrada_last_query_role->getRoleId();
			$access_id	= preg_replace('/[^0-9]+/', "", $role_id);

			$query = "SELECT `user_id` FROM `".AUTH_DATABASE."`.`user_access`
						WHERE `id` = ".$db->qstr($access_id);
			$user_id = $db->GetOne($query);
		}
		return $this->_checkAssignmentContacts($user_id, $assignment_id);
	}

	static function _checkAssignmentContacts($user_id, $assignment_id) {
		global $db;

		$query		= "	SELECT *
						FROM `assignment_contacts` a
						JOIN `assignments` b
						ON a.`assignment_id` = b.`assignment_id`
						WHERE a.`proxy_id` = " . $db->qstr($user_id) . "
						AND b.`assignment_active` = 1
						AND a.`assignment_id` = " . $db->qstr($assignment_id);
		$results	= $db->GetAll($query);
		if ($results) {
			return true;
		} else {
			return false;
		}
	}
}

/**
 * AssessmentContact Assertion
 *
 * Assert true if access should be granted to an assessment for a user that is an assignment contact of an assignment within the assessment.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Harry Brundage <hbrundage@qmed.ca>, Don Zuiker <zuikerd@qmed.ca>
 * @copyright Copyright 2010, 2013 Queen's University. All Rights Reserved.
 */
class AssessmentContactAssertion implements Zend_Acl_Assert_Interface {
/**
 *
 * @param Zend_Acl $acl The ACL object isself (the one calling the assertion)
 * @param Zend_Acl_Role_Interface $role The role being queried
 * @param Zend_Acl_Resource_Interface $resource The resource being queried
 * @param string $privilege The privilege being queried
 * @return boolean
 */
	public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {
		global $db;

		//If asserting is off then return true right away
		if ((isset($resource->assert) && $resource->assert == false) || (isset($acl->_entrada_last_query) && isset($acl->_entrada_last_query->assert) && $acl->_entrada_last_query->assert == false)) {
			return true;
		}

		if (isset($resource->assessment_id)) {
			$assessment_id = $resource->assessment_id;
		} else if (isset($acl->_entrada_last_query->assessment_id)) {
			$assessment_id = $acl->_entrada_last_query->assessment_id;
		} else {
			//Parse out the user ID and course ID
			$resource_id = $resource->getResourceId();
			$resource_type = preg_replace('/[0-9]+/', "", $resource_id);

			if ($resource_type !== "assessment") {
				//This only asserts for users on gradebooks.
				return false;
			}

			$assessment_id = preg_replace('/[^0-9]+/', "", $resource_id);
		}
		$role_id = $role->getRoleId();
		$access_id	= preg_replace('/[^0-9]+/', "", $role_id);

		$query = "SELECT `user_id` FROM `".AUTH_DATABASE."`.`user_access`
					WHERE `id` = ".$db->qstr($access_id);
		$user_id = $db->GetOne($query);

		if (!isset($user_id) || !$user_id) {
			$role_id = $acl->_entrada_last_query_role->getRoleId();
			$access_id	= preg_replace('/[^0-9]+/', "", $role_id);

			$query = "SELECT `user_id` FROM `".AUTH_DATABASE."`.`user_access`
						WHERE `id` = ".$db->qstr($access_id);
			$user_id = $db->GetOne($query);
		}
		return $this->_checkAssessmentContacts($user_id, $assessment_id);
	}

	static function _checkAssessmentContacts($user_id, $assessment_id) {
		global $db;

		$query		= "	SELECT *
						FROM `assignment_contacts` a
						JOIN `assignments` b
						ON a.`assignment_id` = b.`assignment_id`
						JOIN `assessments` c
						ON c.`assessment_id` = b.`assessment_id`
						WHERE a.`proxy_id` = " . $db->qstr($user_id) . "
						AND c.`active` = 1
						AND b.`assignment_active` = 1
						AND c.`assessment_id` = " . $db->qstr($assessment_id);
		$results	= $db->GetAll($query);
		if ($results) {
			return true;
		} else {
			return false;
		}
	}
}

/**
 * Event Owner Assertion
 *
 * Used to assert that the event referenced by the course resource is owned by the user referenced by the user role.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Harry Brundage <hbrundage@qmed.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 */
class EventOwnerAssertion implements Zend_Acl_Assert_Interface {
/**
 * Asserts that the role references the director, coordinator, or secondary director of the course resource
 *
 * @param Zend_Acl $acl The ACL object isself (the one calling the assertion)
 * @param Zend_Acl_Role_Interface $role The role being queried
 * @param Zend_Acl_Resource_Interface $resource The resource being queried
 * @param string $privilege The privilege being queried
 * @return boolean
 */
	public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {
		global $db;
		if ((isset($resource->assert) && $resource->assert == false) || (isset($acl->_entrada_last_query) && isset($acl->_entrada_last_query->assert) && $acl->_entrada_last_query->assert == false)) {
			return true;
		}

		if (isset($resource->event_id)) {
			$event_id = $resource->event_id;
		} else if (isset($acl->_entrada_last_query->event_id)) {
			$event_id = $acl->_entrada_last_query->event_id;
		} else {
			return false;

			$resource_id = $resource->getResourceId();
			$resource_type = preg_replace('/[0-9]+/', "", $resource_id);

			if ($resource_type !== "event" && $resource_type !== "eventcontent") {
			//This only asserts for events.
				return false;
			}

			$event_id = preg_replace('/[^0-9]+/', "", $resource_id);
		}

		$role_id = $role->getRoleId();
		$access_id	= preg_replace('/[^0-9]+/', "", $role_id);

		$query = "SELECT `user_id` FROM `".AUTH_DATABASE."`.`user_access`
					WHERE `id` = ".$db->qstr($access_id);
		$user_id = $db->GetOne($query);

		if (!isset($user_id) || !$user_id) {
			$role_id = $acl->_entrada_last_query_role->getRoleId();
			$access_id	= preg_replace('/[^0-9]+/', "", $role_id);

			$query = "SELECT `user_id` FROM `".AUTH_DATABASE."`.`user_access`
						WHERE `id` = ".$db->qstr($access_id);
			$user_id = $db->GetOne($query);
		}

		return $this->_checkEventOwner($user_id, $event_id);
	}

	/**
	 * Checks if the $user_id is either a lecturer teaching the event, or a director or program coordinator of the course the event belongs to.
	 *
	 * @param string|integer $user_id The proxy id to be checked
	 * @param string|integer $event_id The event id to be checked
	 * @return boolean
	 */
	static function _checkEventOwner($user_id, $event_id) {
		global $db;

		$query		= "	SELECT a.`event_id`, b.`proxy_id` AS `teacher`, c.`pcoord_id` AS `coordinator`, d.`proxy_id` AS `director_id`, e.`proxy_id` AS `pcoordinator`
						FROM `events` AS a
						LEFT JOIN `event_contacts` AS b
						ON b.`event_id` = a.`event_id`
						LEFT JOIN `courses` AS c
						ON c.`course_id` = a.`course_id`
						LEFT JOIN `course_contacts` AS d
						ON d.`course_id` = c.`course_id`
						AND d.`contact_type` = 'director'
						LEFT JOIN `course_contacts` AS e
						ON e.`course_id` = c.`course_id`
						AND (e.`contact_type` = 'pcoordinator'
                            OR e.`contact_type` = 'ccoordinator')
						WHERE a.`event_id` = ".$db->qstr($event_id)."
						AND c.`course_active` = '1'";
		$results	= $db->GetAll($query);
		if ($results) {
			foreach ($results as $result) {
				foreach (array("director_id", "coordinator", "teacher", "pcoordinator") as $owner) {
					if ($result[$owner] == $user_id) {
						return true;
					}
				}
			}
		}

		return false;
	}
}

/**
 * Is Student Assertion
 *
 * Used to assert that the user referenced is a student
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jonathan Fingland <jonathan.fingland@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 */
class IsStudentAssertion implements Zend_Acl_Assert_Interface {
/**
 * Asserts that the user group is student
 *
 * @param Zend_Acl $acl The ACL object isself (the one calling the assertion)
 * @param Zend_Acl_Role_Interface $role The role being queried
 * @param Zend_Acl_Resource_Interface $resource The resource being queried
 * @param string $privilege The privilege being queried
 * @return boolean
 */
	public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {

		if ((isset($resource->assert) && $resource->assert == false) || (isset($acl->_entrada_last_query) && isset($acl->_entrada_last_query->assert) && $acl->_entrada_last_query->assert == false)) {
			return true;
		}

		echo "Assertion required<br />";

		return ($acl && $acl->_entrada_last_query_role && $acl->_entrada_last_query_role->details && $acl->_entrada_last_query_role->details->group == "student");
	}
}

/**
 * Used to assert that the organisation this resource belongs to has the requested privlege for the asking role. Used to make blanket access rules for organisations's resources.
 * Extra: will also operate on courses and events who's organisation ID property has not been set.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Harry Brundage <hbrundage@qmed.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 */
class ResourceOrganisationAssertion implements Zend_Acl_Assert_Interface {
/**
 *
 * Asserts that the role has the requested privilege on the resource's organisation
 *
 * @param Zend_Acl $acl The ACL object isself (the one calling the assertion)
 * @param Zend_Acl_Role_Interface $role The role being queried
 * @param Zend_Acl_Resource_Interface $resource The resource being queried
 * @param string $privilege The privilege being queried
 * @return boolean
 */
	public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {
		//Return true right away if asserting is off.

		if (((isset($resource->assert) && $resource->assert == false) || (isset($acl->_entrada_last_query) && isset($acl->_entrada_last_query->assert) && $acl->_entrada_last_query->assert == false)) || (isset($acl->_entrada_last_query) && isset($acl->_entrada_last_query->assert) && $acl->_entrada_last_query->assert == false)) {
			return true;
		}

		//If the organisation_id has been supplied then go right ahead and check to see if this organisation has this privledge
		if (isset($resource->organisation_id) && $acl->has("resourceorganisation".$resource->organisation_id)) {
			return $acl->isAllowed($role, "resourceorganisation".$resource->organisation_id, $privilege);
		} else {
			//Otherwise, look at the object that the query was first made upon, which will have some information about it which hopefully can be used to figure out the organisation_id
			if (isset($acl->_entrada_last_query)) {
			//Use the organisation ID if provided
				if (isset($acl->_entrada_last_query->organisation_id)) {
					$organisation_id = $acl->_entrada_last_query->organisation_id;
				} else {
					global $db;
					//Use the course ID if nessecary
					if (isset($acl->_entrada_last_query->course_id) && ($acl->_entrada_last_query->course_id != 0)) {
						$query = "	SELECT `organisation_id` FROM `courses`
									WHERE `course_id` = ".$db->qstr($acl->_entrada_last_query->course_id)."
									AND `course_active` = '1'";
						$result = $db->GetRow($query);
						if ($result) {
							$organisation_id = $result["organisation_id"];
						}
					} elseif (isset($acl->_entrada_last_query->event_id) && ($acl->_entrada_last_query->event_id != 0)) {
						//Use the event ID if nessecary
						$query = "	SELECT a.`course_id`, b.`organisation_id` AS course_organisation_id, d.`audience_value` AS event_organisation_id
									FROM `events` AS a
									LEFT JOIN `courses` AS b
									ON b.`course_id` = a.`course_id`
									LEFT JOIN `event_audience` AS d
									ON d.`event_id` = ".$db->qstr($acl->_entrada_last_query->event_id)."
									AND d.`audience_type` = 'organisation_id'
									WHERE b.`course_active` = '1'
									ORDER BY b.`organisation_id`";
						$result = $db->GetRow($query);
						if ($result) {
							if (isset($result["course_organisation_id"])) {
								$organisation_id = $result["course_organisation_id"];
							} elseif (isset($result["event_organisation_id"])) {
								$organisation_id = $result["event_organisation_id"];
							}
						}
					}
				}

				if (isset($organisation_id) && $acl->has("resourceorganisation".$organisation_id)) {
					//Return this role's ability to preform this privilege on this organisation.
					return $acl->isAllowed($role, "resourceorganisation".$organisation_id, $privilege);
				}
			}
		}

		return false;
	}
}

/**
 * Community Assertion Class
 *
 * Asserts that a role is of a particular type for the community resource being queried.
 */
abstract class CommunityAssertion implements Zend_Acl_Assert_Interface {
	var $check_method;
	/**
	 *
	 *
	 * Asserts that the role has the requested privilege on the community
	 *
	 * @param Zend_Acl $acl The ACL object isself (the one calling the assertion)
	 * @param Zend_Acl_Role_Interface $role The role being queried
	 * @param Zend_Acl_Resource_Interface $resource The resource being queried
	 * @param string $privilege The privilege being queried
	 * @return boolean
	 */
	public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {
		global $db;
	//Return true right away if asserting is off.
		if ((isset($resource->assert) && $resource->assert == false) || (isset($acl->_entrada_last_query) && isset($acl->_entrada_last_query->assert) && $acl->_entrada_last_query->assert == false)) {
			return true;
		}

		if (isset($resource->community_id)) {
			$community_id = $resource->community_id;
		} else {
			if (isset($acl->_entrada_last_query->community_id)) {
				$community_id = $acl->_entrada_last_query->community_id;
			}
		}
		if (isset($community_id)) {
			$role_id = $role->getRoleId();
			$access_id	= preg_replace('/[^0-9]+/', "", $role_id);

			$query = "SELECT `user_id` FROM `".AUTH_DATABASE."`.`user_access`
						WHERE `id` = ".$db->qstr($access_id);
			$user_id = $db->GetOne($query);

			if (!isset($user_id) || !$user_id) {
				$role_id = $acl->_entrada_last_query_role->getRoleId();
				$access_id	= preg_replace('/[^0-9]+/', "", $role_id);

				$query = "SELECT `user_id` FROM `".AUTH_DATABASE."`.`user_access`
							WHERE `id` = ".$db->qstr($access_id);
				$user_id = $db->GetOne($query);
			}

			return $this->_checkCommunity($user_id, $community_id);
		}
		return false;
	}

	abstract public function _checkCommunity($user_id, $community_id);
}

/**
 * Community Owner Assertion Class
 *
 * Asserts that a role is an administrator for the community resource being queried.
 */
class CommunityOwnerAssertion extends CommunityAssertion {

	var $check_method = "_checkCommunityOwner";

	/**
	 *	Checks that a user can administer a community
	 *
	 * @param integer $user_id The user's proxy ID
	 * @param integer $community_id The community's ID
	 * @return boolean
	 */
	public function _checkCommunity ($user_id, $community_id) {
		global $db;
		$query	= "SELECT `proxy_id` FROM `community_members`
				WHERE `community_id` = ".$db->qstr($community_id)."
				AND `proxy_id` = ".$db->qstr($user_id)."
				AND `member_active` = '1'
				AND `member_acl` = '1'";
		$result	= $db->GetRow($query);
		if ($result) {
		//Query had a row
			return true;
		}
		return false;
	}
}

/**
 * Community Member Assertion Class
 *
 * Asserts that a role is an administrator for the community resource being queried.
 */
class CommunityMemberAssertion extends CommunityAssertion {
	var $check_method = "_checkCommunityMember";

	/**
	 *	Checks that a user can administer a community
	 *
	 * @param integer $user_id The user's proxy ID
	 * @param integer $community_id The community's ID
	 * @return boolean
	 */
	public function _checkCommunity($user_id, $community_id) {
		global $db, $ENTRADA_USER;
		$query	= "SELECT `proxy_id` FROM `community_members`
				WHERE `community_id` = ".$db->qstr($community_id)."
				AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId());
		$result	= $db->GetRow($query);
		if ($result) {
		//Query had a row
			return true;
		}
		return false;
	}
}

class CourseCommunityEnrollmentAssertion implements Zend_Acl_Assert_Interface {
    public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {
        global $db;
                
        //If asserting is off then return true right away
        if ((isset($resource->assert) && $resource->assert == false) || (isset($acl->_entrada_last_query) && isset($acl->_entrada_last_query->assert) && $acl->_entrada_last_query->assert == false)) {
            return false;
        }

        if (isset($resource->communityresource_id)) {
            $communityresource_id = $resource->communityresource_id;
        } else if (isset($acl->_entrada_last_query->communityresource_id)) {
            $communityresource_id = $acl->_entrada_last_query->communityresource_id;
        } else {
            // Parse out the user ID and communitydiscussion ID
            $resource_id = $resource->getResourceId();
            $communityresource_type = preg_replace('/[0-9]+/', "", $resource_id);
            $communityresource_id = preg_replace('/[^0-9]+/', "", $resource_id);
        }

        //community_discussions community_id
		switch ($communityresource_type) {
			case 'communitydiscussion' :
				$query = "SELECT `course_id`
						  FROM `community_courses` AS a
						  JOIN `community_discussions` AS b
						  ON a.`community_id` = b.`community_id`
						  WHERE b.`cdiscussion_id` = " . $db->qstr($communityresource_id);
			break;
			case 'communityfolder' :
				$query = "SELECT `course_id`
						  FROM `community_courses` AS a
						  JOIN `community_shares` AS b
						  ON a.`community_id` = b.`community_id`
						  WHERE b.`cshare_id` = ".$db->qstr($communityresource_id);
			break;
			case 'communityfile' :
				$query = "SELECT `course_id`
						  FROM `community_courses` AS a
						  JOIN `community_share_files` AS b
						  ON a.`community_id` = b.`community_id`
						  WHERE b.`csfile_id` = ".$db->qstr($communityresource_id);
			break;
			case 'communitylink' :
				$query = "SELECT `course_id`
						  FROM `community_courses` AS a
						  JOIN `community_share_links` AS b
						  ON a.`community_id` = b.`community_id`
						  WHERE b.`cslink_id` = ".$db->qstr($communityresource_id);
			break;
			case 'communityhtml' :
				$query = "SELECT `course_id`
						  FROM `community_courses` AS a
						  JOIN `community_share_html` AS b
						  ON a.`community_id` = b.`community_id`
						  WHERE b.`cshtml_id` = ".$db->qstr($communityresource_id);
			break;
		}
        $course_id = $db->GetOne($query);
        if ($course_id) {
            $role_id = $role->getRoleId();
            $access_id = preg_replace('/[^0-9]+/', "", $role_id);

            $query = "SELECT `user_id` FROM `".AUTH_DATABASE."`.`user_access`
                        WHERE `id` = ".$db->qstr($access_id);
            $user_id = $db->GetOne($query);

            if (!isset($user_id) || !$user_id) {
                $role_id = $acl->_entrada_last_query_role->getRoleId();
                $access_id = preg_replace('/[^0-9]+/', "", $role_id);

                    $query = "SELECT `user_id` FROM `".AUTH_DATABASE."`.`user_access`
                                WHERE `id` = ".$db->qstr($access_id);
                    $user_id = $db->GetOne($query);
                }

            return $this->_checkCourseCommunityEnrollment($user_id, $course_id);
        } else {
            return false;
        }
    }

    /**
    * Checks if the $user_id is an active member of the corresponding
    * course website (community).
    *
    * @param string|integer $user_id The proxy_id to be checked
    * @param string|integer $course_id The course id to be checked
    * @return boolean
    */
    static function _checkCourseCommunityEnrollment($user_id, $course_id) {
        global $db;

        $query = "SELECT *
                    FROM `courses`
                    WHERE `course_id` = " . $db->qstr($course_id);
        $result = $db->CacheGetRow(CACHE_TIMEOUT, $query);

        if ($result) {
            if ($result["permission"] == "open") {
                return true;
            } else {
                $query = "SELECT *
                            FROM `community_courses`
                            WHERE `course_id` = " . $db->qstr($course_id);
                $result = $db->CacheGetRow(CACHE_TIMEOUT, $query);
                if ($result) {
                    $query = "SELECT *
                                FROM `community_members`
                                WHERE `community_id` = " . $db->qstr($result["community_id"]) . "
                                AND `proxy_id` = " . $db->qstr($user_id) . "
                                AND `member_active` = 1";
                    $result = $db->CacheGetRow(CACHE_TIMEOUT, $query);
                    if ($result) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}


/**
 * Course Group Member Assertion
 *
 * Used to assert that user is a course group member and checks permissions
 * in the corresponding course website (community). Grants access to community 
 * administrators
 *
 * @author Organisation: UCLA
 * @author Unit: David Geffen School of Medicine
 * @author Developer: Daniel Noji <dnoji@mednet.ucla.edu>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 */
class CourseGroupMemberAssertion implements Zend_Acl_Assert_Interface {

	public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {
		global $db;
                
		//If asserting is off then return true right away
		if ((isset($resource->assert) && $resource->assert == false) || (isset($acl->_entrada_last_query) && isset($acl->_entrada_last_query->assert) && $acl->_entrada_last_query->assert == false)) {
			return false;
		}
                
		if (isset($resource->communityresource_id)) {
			$communityresource_id = $resource->communityresource_id;
		} else if (isset($acl->_entrada_last_query->communityresource_id)) {
			$communityresource_id = $acl->_entrada_last_query->communityresource_id;
		} else {
			// Parse out the user ID and communitydiscussion ID
			$resource_id = $resource->getResourceId();
			$communityresource_type = preg_replace('/[0-9]+/', "", $resource_id);
            $communityresource_id = preg_replace('/[^0-9]+/', "", $resource_id);
		}
                
		$role_id = $role->getRoleId();
		$access_id = preg_replace('/[^0-9]+/', "", $role_id);
                
		$query = "SELECT `user_id` FROM `".AUTH_DATABASE."`.`user_access`
					WHERE `id` = ".$db->qstr($access_id);
		$user_id = $db->GetOne($query);

		if (!isset($user_id) || !$user_id) {
			$role_id = $acl->_entrada_last_query_role->getRoleId();
			$access_id	= preg_replace('/[^0-9]+/', "", $role_id);

			$query = "SELECT `user_id` FROM `".AUTH_DATABASE."`.`user_access`
						WHERE `id` = ".$db->qstr($access_id);
			$user_id = $db->GetOne($query);
		}
            return $this->_checkCourseGroupMember($user_id, $communityresource_type, $communityresource_id, $privilege);
                
        }  
        static function _checkCourseGroupMember ($user_id, $communityresource_type, $communityresource_id, $privilege){
            global $db;
            $course_group_member = false;
            
            //Check Community Membership
            //Get the community ID
			switch ($communityresource_type) {
				case 'communitydiscussion' :
		            $query = "SELECT `community_id` FROM `community_discussions`
		                      WHERE `cdiscussion_id` = ".$db->qstr($communityresource_id);
				break;
				case 'communityfolder' :
					$query = "SELECT `community_id` FROM `community_shares`
							  WHERE `cshare_id` = ".$db->qstr($communityresource_id);
				break;
				case 'communityfile' :
					$query = "SELECT `community_id` FROM `community_share_files`
							  WHERE `csfile_id` = ".$db->qstr($communityresource_id);
				break;
				case 'communitylink' :
					$query = "SELECT `community_id` FROM `community_share_links`
							  WHERE `cslink_id` = ".$db->qstr($communityresource_id);
				break;
				case 'communityhtml' :
					$query = "SELECT `community_id` FROM `community_share_html`
							  WHERE `cshtml_id` = ".$db->qstr($communityresource_id);
				break;
			}
            $result	= $db->GetRow($query);
            if ($result) {
            //Check for the user's membership in the community
                
                $query	= "
                        SELECT `proxy_id`, `member_acl` FROM `community_members`
                        WHERE `community_id` = ".$db->qstr($result['community_id'])."
                        AND `proxy_id` = ".$db->qstr($user_id)."
                        AND `member_active` = '1'";
                $membership	= $db->GetRow($query);
                
                if ($membership){
                    /*
                     * Grant Access if the user is an administrator of the community
                     */
                    if ($membership['member_acl'] == 1) {
                        return true;
                    } else {
                        /*
                         * If the user is not an administrator, try to grant access by course group membership permissions
                         */
						
                        //Look up the course group permissions for the supplied $communityresource_id
                        $query = " SELECT `cgroup_id`
                                    FROM `community_acl_groups`
                                    WHERE `resource_type` =  ". $db->qstr($communityresource_type) ."
                                    AND `resource_value` = ". $db->qstr($communityresource_id) ."
                                    AND `" . clean_input($privilege) . "` = '1'";
                        $cgroups = $db->GetAll($query);

                        if ($cgroups){
                            foreach ($cgroups as $cgroup) {

                                //Check if user is a member of the course group
                                $query = " SELECT COUNT(*) as `user_access`
                                            FROM `course_group_audience`
                                            WHERE `cgroup_id` = " . $db->qstr($cgroup['cgroup_id'])."
                                            AND `proxy_id` = " .$db->qstr($user_id) ."
                                            AND `active` = '1'";

                                $result = $db->GetRow($query); 

                                //If the user is a member of the course group, give them access
                                if ($result['user_access'] != 0){
                                    $course_group_member = true;
									break;
                                }
								
								//Check if user is a tutor for the course group
								$query = "SELECT COUNT(*) as `user_access`
										  FROM `course_group_contacts`
										  WHERE `cgroup_id` = ".$db->qstr($cgroup['cgroup_id'])."
										  AND `proxy_id` = ".$db->qstr($user_id);
								$result = $db->GetRow($query);
								
								//If the user is a tutor for the course group, give them access
								if ($result['user_access'] != 0) {
									$course_group_member = true;
									break;
								}
                            }
                        }
                        return ($course_group_member) ? true : false;
                    }
                }
            }
        }
}

/**
 * Community Discussion Post Owner Assertion Class
 *
 * Asserts that a role is an owner of the discussion post.
 */
class CommunityDiscussionTopicOwnerAssertion implements Zend_Acl_Assert_Interface {

	public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {
		global $db;
                
		//If asserting is off then return true right away
		if ((isset($resource->assert) && $resource->assert == false) || (isset($acl->_entrada_last_query) && isset($acl->_entrada_last_query->assert) && $acl->_entrada_last_query->assert == false)) {
			return false;
		}
                
		if (isset($resource->communitydiscussiontopic_id)) {
			$communityresource_id = $resource->communitydiscussiontopic_id;
		} else if (isset($acl->_entrada_last_query->communitydiscussiontopic_id)) {
			$communityresource_id = $acl->_entrada_last_query->communitydiscussiontopic_id;
		} else {
			// Parse out the user ID and communitydiscussion ID
			$resource_id = $resource->getResourceId();
			$communityresource_type = preg_replace('/[0-9]+/', "", $resource_id);
            $communityresource_id = preg_replace('/[^0-9]+/', "", $resource_id);
            
            if ($communityresource_type !== "communitydiscussiontopic") {
            //This only asserts for communitydiscussiontopics.
                return false;
            }
		}
                
		$role_id = $role->getRoleId();
		$access_id = preg_replace('/[^0-9]+/', "", $role_id);
                
		$query = "SELECT `user_id` FROM `".AUTH_DATABASE."`.`user_access`
					WHERE `id` = ".$db->qstr($access_id);
		$user_id = $db->GetOne($query);

		if (!isset($user_id) || !$user_id) {
			$role_id = $acl->_entrada_last_query_role->getRoleId();
			$access_id	= preg_replace('/[^0-9]+/', "", $role_id);

			$query = "SELECT `user_id` FROM `".AUTH_DATABASE."`.`user_access`
						WHERE `id` = ".$db->qstr($access_id);
			$user_id = $db->GetOne($query);
		}
       
        return $this->_checkCommunityDiscussionTopicOwner($user_id, $communityresource_type, $communityresource_id, $privilege);
    }
    
    static function _checkCommunityDiscussionTopicOwner ($user_id, $communityresource_type, $communityresource_id, $privilege){
        global $db;
        
        $query = "SELECT EXISTS(SELECT 1 FROM `community_discussion_topics` WHERE `cdtopic_id` = ".$db->qstr($communityresource_id)." AND `proxy_id` = ".$db->qstr($user_id)." ) as `exists`";
        $isDiscussionTopicOwner = $db->getRow($query);

        if ($isDiscussionTopicOwner['exists'] == 1){
            return true;
        } else {
            return false;
        }
    }        
}

/**
 * Not Guest assertion class
 *
 * Asserts that a role is not a guest
 */
class NotGuestAssertion implements Zend_Acl_Assert_Interface {
	public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {
		global $db;
		$role = $acl->_entrada_last_query_role;
		if (isset($role->details) && isset($role->details["group"])) {
			$GROUP = $role->details["group"];
		} else {
/**
 * @todo This needs to be fixed, or perhaps this would never even happen? The user_data table doesn't contain group or role fields, that's in user_access.
 */
			$role_id = $role->getRoleId();
			$access_id	= preg_replace('/[^0-9]+/', "", $role_id);

			$query = "SELECT `user_id` FROM `".AUTH_DATABASE."`.`user_access`
						WHERE `id` = ".$db->qstr($access_id);
			$user_id = $db->GetOne($query);
			$query = "SELECT `group`, `role` FROM `".AUTH_DATABASE."`.`user_data` WHERE `id` = ".$db->qstr($user_id);
			$result = $db->GetRow($query);
			if ($result) {
				$GROUP = $result["group"];
			} else {
			//Return false cause this person could be a guest.
				return false;
			}

		}
		if ($GROUP == "guest") {
			return false;
		}	 else {
			return true;
		}

	}

}

/**
 * Not Student assertion class
 *
 * Asserts that a role is not a student
 */
class NotStudentAssertion implements Zend_Acl_Assert_Interface {
	public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {
		global $db;
		$role = $acl->_entrada_last_query_role;
		if (isset($role->details) && isset($role->details["group"])) {
			$GROUP = $role->details["group"];
		} else {
/**
 * @todo This needs to be fixed, or perhaps this would never even happen? The user_data table doesn't contain group or role fields, that's in user_access.
 */
			$role_id = $role->getRoleId();
			$access_id	= preg_replace('/[^0-9]+/', "", $role_id);

			$query = "SELECT `user_id` FROM `".AUTH_DATABASE."`.`user_access`
						WHERE `id` = ".$db->qstr($access_id);
			$user_id = $db->GetOne($query);
			$query = "SELECT `group`, `role` FROM `".AUTH_DATABASE."`.`user_data` WHERE `id` = ".$db->qstr($user_id);
			$result = $db->GetRow($query);
			if ($result) {
				$GROUP = $result["group"];
			} else {
			//Return false cause this person could be a guest.
				return false;
			}

		}
		if ($GROUP == "student") {
			return false;
		}	 else {
			return true;
		}

	}

}

/**
 * Clerkship Assertion Class
 *
 * Asserts that a role's graduating year makes it eligble for clerkship
 */
class ClerkshipAssertion implements Zend_Acl_Assert_Interface {
	public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {

		if (!($role instanceof EntradaUser) || !isset($role->details) || !isset($role->details["grad_year"])) {
			if (isset($acl->_entrada_last_query_role)) {
				$role = $acl->_entrada_last_query_role;
				if (($role instanceof EntradaUser) && isset($role->details) && isset($role->details["grad_year"])) {
					$GRAD_YEAR = preg_replace("/[^0-9]+/i", "", $role->details["grad_year"]);
				}
			}
		} else {
			$GRAD_YEAR = preg_replace("/[^0-9]+/i", "", $role->details["grad_year"]);
		}

		if (!isset($GRAD_YEAR)) {
			return false;
		}

		if ((time() < $end_timestamp = mktime(0, 0, 0, 7, 13, intval($GRAD_YEAR))) && (time() >= strtotime("-26 months", $end_timestamp))) {
			return true;
		} else {
			return false;
		}
	}
}

/**
 * Clerkship Lottery Assertion Class
 *
 * Asserts that a role's graduating year makes it eligble for the clerkship lottery
 */
class ClerkshipLotteryAssertion implements Zend_Acl_Assert_Interface {
	public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {

		if (!($role instanceof EntradaUser) || !isset($role->details) || !isset($role->details["grad_year"])) {
			if (isset($acl->_entrada_last_query_role)) {
				$role = $acl->_entrada_last_query_role;
				if (($role instanceof EntradaUser) || isset($role->details) || isset($role->details["grad_year"])) {
					$GRAD_YEAR = $role->details["grad_year"];
				}
			}
		} else {
			$GRAD_YEAR = $role->details["grad_year"];
		}

		if (!isset($GRAD_YEAR)) {
			return false;
		}

		if ((date("Y",strtotime("+2 Years")) == $GRAD_YEAR) && ((time() >= CLERKSHIP_LOTTERY_START && time() <= CLERKSHIP_LOTTERY_FINISH) || time() >= CLERKSHIP_LOTTERY_RELEASE)) {
			return true;
		} else {
			return false;
		}
	}
}

/**
 * Clerkship Director Assertion Class
 *
 * Checks to see if the faculty:director's proxy_id is in the $AGENT_CONTACTS["agent-clerkship"]["director_ids"]
 * which therefore gives them access to the Manage Clerkship tab.
 */
class ClerkshipDirectorAssertion implements Zend_Acl_Assert_Interface {
	public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {
		global $AGENT_CONTACTS;

		if (!($role instanceof EntradaUser) || !isset($role->details) || !isset($role->details["id"])) {
			if (isset($acl->_entrada_last_query_role)) {
				$role = $acl->_entrada_last_query_role;
				if (($role instanceof EntradaUser) || isset($role->details) || isset($role->details["id"])) {
					$proxy_id = $role->details["id"];
				}
			}
		} else {
			$proxy_id = $role->details["id"];
		}

		if ((isset($proxy_id)) && ((int) $proxy_id)) {
			if ((isset($AGENT_CONTACTS)) && (is_array($AGENT_CONTACTS)) && (isset($AGENT_CONTACTS["agent-clerkship"]["director_ids"]))) {
				$director_ids = array();

				foreach ((array) $AGENT_CONTACTS["agent-clerkship"]["director_ids"] as $director_id) {
					if ((int) $director_id) {
						$director_ids[] = $director_id;
					}
				}

				if (count($director_ids)) {
					if (in_array($proxy_id, $director_ids)) {
						return true;
					}
				}
			}
		}

		return false;
	}
}

/**
 * Regional Education Has Accommodations Class
 *
 * Checks to see if the resident has regional accommodations assigned to them
 * by the regional education office.
 */
class HasAccommodationsAssertion implements Zend_Acl_Assert_Interface {
	public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {
		global $db;

		if (!($role instanceof EntradaUser) || !isset($role->details) || !isset($role->details["id"])) {
			if (isset($acl->_entrada_last_query_role)) {
				$role = $acl->_entrada_last_query_role;
				if (($role instanceof EntradaUser) || isset($role->details) || isset($role->details["id"])) {
					$proxy_id = $role->details["id"];
				}
			}
		} else {
			$proxy_id = $role->details["id"];
		}

		if ((isset($proxy_id)) && ((int) $proxy_id)) {
			$query = "SELECT COUNT(*) AS `total` FROM `".CLERKSHIP_DATABASE."`.`apartment_schedule` WHERE `proxy_id` = ".$db->qstr($proxy_id);
			$result = $db->GetRow($query);

			if ($result && ($result["total"] > 0)) {
				return true;
			}
		}

		return false;
	}
}

class QuizOwnerAssertion implements Zend_Acl_Assert_Interface {
	public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {
		global $db;

		//If asserting is off then return true right away
		if ((isset($resource->assert) && $resource->assert == false) || (isset($acl->_entrada_last_query) && isset($acl->_entrada_last_query->assert) && $acl->_entrada_last_query->assert == false)) {
			return true;
		}

		if (isset($resource->quiz_id)) {
			$quiz_id = $resource->quiz_id;
		} else if (isset($acl->_entrada_last_query->quiz_id)) {
			$quiz_id = $acl->_entrada_last_query->quiz_id;
		} else {
			//Parse out the user ID and course ID
			$resource_id = $resource->getResourceId();
			$resource_type = preg_replace('/[0-9]+/', "", $resource_id);

			if ($resource_type !== "quiz" || $resource_type !== "quizquestion" || $resource_type !== "quizresult") {
			//This only asserts for users on quizzes.
				return false;
			}

			$quiz_id = preg_replace('/[^0-9]+/', "", $resource_id);
		}

		$role_id = $role->getRoleId();
		$access_id	= preg_replace('/[^0-9]+/', "", $role_id);

		$query = "SELECT `user_id` FROM `".AUTH_DATABASE."`.`user_access`
					WHERE `id` = ".$db->qstr($access_id);
		$user_id = $db->GetOne($query);

		if (!isset($user_id) || !$user_id) {
			$role_id = $acl->_entrada_last_query_role->getRoleId();
			$access_id	= preg_replace('/[^0-9]+/', "", $role_id);

			$query = "SELECT `user_id` FROM `".AUTH_DATABASE."`.`user_access`
						WHERE `id` = ".$db->qstr($access_id);
			$user_id = $db->GetOne($query);
		}

		return $this->_checkQuizOwner($user_id, $quiz_id);
	}

	static function _checkQuizOwner($user_id, $quiz_id) {
		global $db;

		$query		= "	SELECT a.`proxy_id`
						FROM `quiz_contacts` AS a
						WHERE a.`quiz_id` = ".$db->qstr($quiz_id);
		$results	= $db->GetAll($query);
		if ($results) {
			foreach ($results as $result) {
				if ($result["proxy_id"] == $user_id) {
					return true;
				}
			}
		}

		return false;
	}
}

/**
 * Base class for smart Entrada resource objects. Used for dummy checks and non assertion checks.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Harry Brundage <hbrundage@qmed.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 */
class EntradaAclResource implements Zend_Acl_Resource_Interface {
/**
 * Wheather or not rules concering this resource need assert. True if so.
 * @var boolean
 */
	var $assert = true;

	/**
	 * Wheather or not this resource references as specific instance of it's resource type or the resource type. Used to drop down to blanket rules once is assured no rules concerning this instance have been defined
	 * @var boolean
	 */
	var $specific = true;

	/**
	 * The unique resource identifier of this object
	 * @var string
	 */
	var $resource_id = "";

	/**
	 * Creates a new untyped resource for easy checks. Should be overridden.
	 * @param string $id The resource ID to be returned by this when checked
	 * @param boolean $assert If assertions should be preformed or not.
	 */
	function __construct($id, $assert = true) {
		$this->resource_id = $id;
		$this->assert = $assert;
	}

	/**
	 * ACL method for keeping track. Required by Zend_Acl_Resource_Interface
	 * @return string
	 */
	public function getResourceId() {
		if ($this->specific) {
			return $this->resource_id;
		} else {
			return preg_replace('/[0-9]+/', "", $this->resource_id);
		}
	}
}

class UserResource extends EntradaAclResource {
/**
 * This user's organisation ID, used for ResourceOrganisationAssertion.
 * @see ResourceOrganisationAssertion()
 * @var integer
 */
	var $organisation_id;

	/**
	 * This user's proxy id.
	 * @var integer
	 */
	var $user_id;

	/**
	 * Constructs this user resource with the supplied values
	 * @param integer $user_id The proxy ID to represent
	 * @param integer $organisation_id The organisation ID this user belongs to
	 * @param boolean $assert Wheather or not to make an assertion
	 */
	function __construct($user_id, $organisation_id, $assert = null) {
		$this->user_id = $user_id;
		$this->organisation_id = $organisation_id;
		if (isset($assert)) {
			$this->assert = $assert;
		}
	}

	/**
	 * ACL method for keeping track. Required by Zend_Acl_Resource_Interface.
	 * Will return based on specifc property of this resource instance.
	 * @return string
	 */
	public function getResourceId() {
		return "user".($this->specific ? $this->user_id : "");
	}
}

/**
 * Assessor resource object for the EntradaACL.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 */
class AssessorResource extends EntradaAclResource {

    var $dassessment_id;

    /**
     * Constructs this Assessor resource with the supplied parameters
     * @param integer $aprogress_id
     * @param boolean $assert whether or not to make an assertion
     */
    function __construct($dassessment_id, $assert = null) {
        $this->dassessment_id = $dassessment_id;
        if (isset($assert)) {
            $this->assert = $assert;
        }
    }

    /**
     * ACL method for keeping track. Required by Zend_Acl_Resource_Interface.
     * Will return based on specific property of this resource instance.
     * @return string
     */
    public function getResourceId() {
        return "assessor".($this->specific ? $this->dassessment_id : "");
    }
}

/**
 * Assessment result resource object for the EntradaACL.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 */
class AssessmentResultResource extends EntradaAclResource {
    /**
     * The aprogress_id for this resource
     * @var integer
     */
    var $aprogress_id;

    /**
     * Constructs this Assessor resource with the supplied parameters
     * @param integer $aprogress_id
     * @param boolean $assert whether or not to make an assertion
     */
    function __construct($aprogress_id, $assert = null) {
        $this->aprogress_id = $aprogress_id;
        if (isset($assert)) {
            $this->assert = $assert;
        }
    }

    /**
     * ACL method for keeping track. Required by Zend_Acl_Resource_Interface.
     * Will return based on specific property of this resource instance.
     * @return string
     */
    public function getResourceId() {
        return "assessmentresult".($this->specific ? $this->aprogress_id : "");
    }
}


class AssessmentResultAssertion implements Zend_Acl_Assert_Interface {
    public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {
        if (!($resource instanceof AssessmentResultResource)) {
            return false;
        }
        if (!isset($resource->aprogress_id)) {
            return false;
        }

        $role = $acl->_entrada_last_query_role;
        if (!isset($role->details["id"])) {
            return false;
        }
        $assessment_progress = Models_Assessments_Progress::fetchRowByID($resource->aprogress_id);
        if ($assessment_progress) {
            if ($assessment_progress->getAssessorType() == "internal" && ($assessment_progress->getAssessorValue() == $role->details["id"] || Models_Course_Group::facultyMemberIsTutor($role->details["id"], $assessment_progress->getAssessorValue(), $role->details["organisation_id"]))) {
                return true;
            } else {
                $distribution_target = Models_Assessments_Distribution_Target::fetchRowByID($assessment_progress->getAdtargetID());
                if ($distribution_target && $assessment_progress->getProgressValue() == "complete" && (in_array($distribution_target->getTargetType(), array("proxy_id", "group_id", "cgroup_id", "course_id", "schedule_id", "organisation_id")) && (in_array($distribution_target->getTargetScope(), array("faculty","internal_learners","external_learners","all_learners")) || $distribution_target->getTargetType() == "proxy_id")) && ($assessment_progress->getTargetRecordID() == $role->details["id"] || Models_Course_Group::facultyMemberIsTutor($role->details["id"], $assessment_progress->getTargetRecordID(), $role->details["organisation_id"]))) {
                    return true;
                }
            }
			$distribution_authors = Models_Assessments_Distribution_Author::fetchAllByDistributionID($assessment_progress->getAdistributionID());
			if (isset($distribution_authors) && @count($distribution_authors) >= 1) {
				foreach ($distribution_authors as $distribution_author) {
					if ($distribution_author->getAuthorType() == "proxy_id" && $distribution_author->getAuthorID() == $role->details["id"]) {
						return true;
					} elseif ($distribution_author->getAuthorType() == "course_id" && Models_Course::checkCourseOwner($distribution_author->getAuthorID(), $role->details["id"])) {
						return true;
					}
				}
			}
			$distribution_reviewers = Models_Assessments_Distribution_Reviewer::fetchAllByDistributionID($assessment_progress->getAdistributionID());
			if (isset($distribution_reviewers) && @count($distribution_reviewers) >= 1) {
				foreach ($distribution_reviewers as $distribution_reviewer) {
					if ($distribution_reviewer->getProxyID() == $role->details["id"]) {
						return true;
					}
				}
			}
			$distribution = Models_Assessments_Distribution::fetchRowByID($assessment_progress->getAdistributionID());
			if ($distribution) {
				if ($distribution->getCourseID() && Models_Course::checkCourseOwner($distribution->getCourseID(), $role->details["id"])) {
					return true;
				}
			}
        }

        return false;
    }
}

/**
 * Assessment progress resource object for the EntradaACL.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 */
class AssessmentProgressResource extends EntradaAclResource {
    /**
     * The aprogress_id for this resource
     * @var integer
     */
    var $aprogress_id;

    /**
     * Constructs this Assessor resource with the supplied parameters
     * @param integer $aprogress_id
     * @param boolean $assert whether or not to make an assertion
     */
    function __construct($aprogress_id, $assert = null) {
        $this->aprogress_id = $aprogress_id;
        if (isset($assert)) {
            $this->assert = $assert;
        }
    }

    /**
     * ACL method for keeping track. Required by Zend_Acl_Resource_Interface.
     * Will return based on specific property of this resource instance.
     * @return string
     */
    public function getResourceId() {
        return "assessmentprogress".($this->specific ? $this->aprogress_id : "");
    }
}


class AssessmentProgressAssertion implements Zend_Acl_Assert_Interface {
    public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {
        if (!($resource instanceof AssessmentProgressResource)) {
            return false;
        }
        if (!isset($resource->aprogress_id)) {
            return false;
        }

        $role = $acl->_entrada_last_query_role;
        if (!isset($role->details["id"])) {
            return false;
        }

        $assessment_progress = Models_Assessments_Progress::fetchRowByID($resource->aprogress_id);
        if ($assessment_progress){
            if ($assessment_progress->getAssessorType() == "internal" && $assessment_progress->getAssessorValue() == $role->details["id"]) {
                return true;
            }
        }

        return false;
    }
}


/**
 * Smart course resource object for the EntradaACL.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Harry Brundage <hbrundage@qmed.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 */
class CourseResource extends EntradaAclResource {
/**
 * The course ID for this resource
 * @var integer
 */
	var $course_id;

	/**
	 * This course's organisation ID, used for ResourceOrganisationAssertion.
	 * @see ResourceOrganisationAssertion()
	 * @var integer
	 */
	var $organisation_id;

	/**
	 * Constructs this course resource with the supplied values
	 * @param integer $course_id The course ID to represent
	 * @param integer $organisation_id The organisation ID this course belongs to
	 * @param boolean $assert Wheather or not to make an assertion
	 */
	function __construct($course_id, $organisation_id, $assert = null) {
		$this->course_id = $course_id;
		$this->organisation_id = $organisation_id;
		if (isset($assert)) {
			$this->assert = $assert;
		}
	}

	/**
	 * ACL method for keeping track. Required by Zend_Acl_Resource_Interface.
	 * Will return based on specifc property of this resource instance.
	 * @return string
	 */
	public function getResourceId() {
		return "course".($this->specific ? $this->course_id : "");
	}
}
/**
 *  Creates a community discussion resource
 * 
 * @author Organisation: UCLA
 * @author Unit: David Geffen School of Medicine
 * @author Developer: Daniel Noji <dnoji@mednet.ucla.edu>
 * */

class CommunityDiscussionResource extends EntradaAclResource{
    var $communitydiscussion_id;
            
    function __construct($communitydiscussion_id, $assert = true){
        $this->communitydiscussion_id = $communitydiscussion_id;
        
        if (isset($assert)){
            $this->assert = $assert;
        }
    }
    public function getResourceId() {
        return "communitydiscussion". ($this->specific ? $this->communitydiscussion_id : "");
    }
}
/**
 *  Creates a community discussion topic resource
 * 
 * @author Organisation: UCLA
 * @author Unit: David Geffen School of Medicine
 * @author Developer: Daniel Noji <dnoji@mednet.ucla.edu>
 * */

class CommunityDiscussionTopicResource extends EntradaAclResource{
    var $communitydiscussiontopic_id;
            
    function __construct($communitydiscussiontopic_id, $assert = true){
        $this->communitydiscussiontopic_id = $communitydiscussiontopic_id;
        
        if (isset($assert)){
            $this->assert = $assert;
        }
    }
    public function getResourceId() {
        return "communitydiscussiontopic". ($this->specific ? $this->communitydiscussiontopic_id : "");
    }
}

/**
 * Creates a group (cohort) resource
 * 
 * @author Organisation: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Robert Fotino <robert.fotino@gmail.com>
 */
class GroupResource extends EntradaAclResource {
    var $organisation_id;
    var $group_id;
    
    function __construct($organisation_id, $group_id = null, $assert = true) {
        $this->organisation_id = $organisation_id;
        $this->group_id = $group_id;
        $this->assert = $assert;
    }
    public function getResourceId() {
        return "group".($this->specific ? $this->group_id : "");
    }
}

/**
 * Smart gradebook resource object for the EntradaACL.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Harry Brundage <hbrundage@qmed.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 */
class GradebookResource extends CourseResource {
	/**
	 * ACL method for keeping track. Required by Zend_Acl_Resource_Interface.
	 * Will return based on specifc property of this resource instance.
	 * @return string
	 */
	public function getResourceId() {
		return "gradebook".($this->specific ? $this->course_id : "");
	}
}

class AssessmentResource extends GradebookResource {
	var $assessment_id;
	
	function __construct($course_id, $organisation_id, $assessment_id, $assert = null) {		
		$this->assessment_id = $assessment_id;
		parent::__construct($course_id, $organisation_id, $assert);
		if (isset($assert)) {
			$this->assert = $assert;
		}
	}
	
	public function getResourceId() {
		return "assessment".($this->specific ? $this->assessment_id : "");
	}
}

class AssignmentResource extends GradebookResource {
	var $assignment_id;
	
	function __construct($course_id, $organisation_id, $assignment_id, $assert = null) {		
		$this->assignment_id = $assignment_id;
		
		parent::__construct($course_id, $organisation_id, $assert);
		
		if (isset($assert)) {
			$this->assert = $assert;
		}
	}
	
	public function getResourceId() {
		return "assignment".($this->specific ? $this->assignment_id : "");
	}
}

/**
 * Creates a photo resource.
 */
class PhotoResource extends EntradaAclResource {
	var $proxy_id;

	var $privacy_level;

	var $photo_type;

	function __construct($proxy_id, $privacy_level, $photo_type, $assert = null) {
		$this->proxy_id			= $proxy_id;
		$this->privacy_level	= $privacy_level;
		$this->photo_type		= $photo_type;

		if (isset($assert)) {
			$this->assert = $assert;
		}
	}

	public function getResourceId() {
		return "photo".($this->specific ? $this->proxy_id : "");
	}
}

class PhotoAssertion implements Zend_Acl_Assert_Interface {
	public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {
		if (!($resource instanceof PhotoResource)) {
			return false;
		}
		if (!isset($resource->proxy_id) && !isset($resource->privacy_level) && !isset($resource->photo_type)) {
			return false;
		}

		$role = $acl->_entrada_last_query_role;
		if (!isset($role->details["id"])) {
			return false;
		}
		if (($resource->proxy_id == $role->details["id"]) || ((($resource->photo_type == "official") && ((int) $resource->privacy_level >= 2)) || (($resource->photo_type == "upload") && ((int) $resource->privacy_level >= 2)))){
			return true;
		}

		return false;
	}
}

/**
 * Smart course resource object for the EntradaACL.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Harry Brundage <hbrundage@qmed.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 */
class NoticeResource extends EntradaAclResource {
	/**
	 * This notices targe organisation's ID, used for ResourceOrganisationAssertion.
	 * @see ResourceOrganisationAssertion()
	 * @var integer
	 */
	var $organisation_id;

	/**
	 * Constructs this course resource with the supplied values
	 * @param integer $course_id The course ID to represent
	 * @param integer $organisation_id The organisation ID this course belongs to
	 * @param boolean $assert Wheather or not to make an assertion
	 */
	function __construct($organisation_id, $assert = null) {
		$this->organisation_id = $organisation_id;
		if (isset($assert)) {
			$this->assert = $assert;
		}
	}

	/**
	 * ACL method for keeping track. Required by Zend_Acl_Resource_Interface.
	 * Will return based on specifc property of this resource instance.
	 * @return string
	 */
	public function getResourceId() {
		return "notice";
	}
}

/**
 * Configuration Resource
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 */
class ConfigurationResource extends EntradaAclResource {
	var $organisation_id;

	function __construct($organisation_id, $assert = null) {
		$this->organisation_id = $organisation_id;
		if (isset($assert)) {
			$this->assert = $assert;
		}
	}

	public function getResourceId() {
		return "configuration";
	}
}

/**
 * Smart event resource object for the EntradaACL.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Harry Brundage <hbrundage@qmed.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 */
class EventResource extends EntradaAclResource {
	/**
	 * The event ID this resource represents
	 * @var integer
	 */
	var $event_id;

	/**
	 * The course ID for the course this event belongs to
	 * @var integer
	 */
	var $course_id;

	/**
	 * This event's parent course's organisation ID, used for ResourceOrganisationAssertion.
	 * @see ResourceOrganisationAssertion()
	 * @var integer
	 */
	var $organisation_id;

	/**
	 * Creates this event resource with the supplied information
	 * @param integer $event_id This event's ID
	 * @param integer $course_id This event's parent course's ID
	 * @param integer $organisation_id This event's parent course's organisation ID
	 * @param boolean $assert Wheather or not to use assertions when looking at rules
	 */
	function __construct($event_id, $course_id= null, $organisation_id = null, $assert = null) {
		$this->course_id = $course_id;
		$this->event_id = $event_id;
		$this->organisation_id = $organisation_id;
		if (isset($assert)) {
			$this->assert = $assert;
		}
	}

	/**
	 * ACL method for keeping track. Required by Zend_Acl_Resource_Interface.
	 * Will return based on specifc property of this resource instance.
	 * @return string
	 */
	public function getResourceId() {
		return "event".($this->specific ? $this->event_id : "");
	}
}

/**
 * Smart event resource object for the EntradaACL.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Harry Brundage <hbrundage@qmed.ca>
 * @copyright Copyright 2008 Queen's University. All Rights Reserved.
 */
class ObjectiveResource extends EntradaAclResource {
	/**
	 * The objective ID this resource represents
	 * @var integer
	 */
	var $objective_id;

	/**
	 * The id of the top level parent of this objective
	 * @var integer
	 */
	var $objective_type;

	/**
	 * Creates this event resource with the supplied information
	 * @param integer $event_id This event's ID
	 * @param integer $course_id This event's parent course's ID
	 * @param integer $organisation_id This event's parent course's organisation ID
	 * @param boolean $assert Wheather or not to use assertions when looking at rules
	 */
	function __construct($objective_id, $objective_type= null, $assert = null) {
		$this->objective_id = $objective_id;
		$this->objective_type = $objective_type;
		if (isset($assert)) {
			$this->assert = $assert;
		}
	}

	/**
	 * ACL method for keeping track. Required by Zend_Acl_Resource_Interface.
	 * Will return based on specifc property of this resource instance.
	 * @return string
	 */
	public function getResourceId() {
		return "objective".($this->specific ? $this->objective_id : "");
	}
}

/**
 * Community resource object for the EntradaACL.
 */
class CommunityAdminResource extends EntradaAclResource {
	/**
	 * This community target organisation's ID, used for ResourceOrganisationAssertion.
	 * @see ResourceOrganisationAssertion()
	 * @var integer
	 */
	public $organisation_id = 0;

	/**
	 * Constructs this course resource with the supplied values
	 * @param integer $community_id The community id for this resource.
	 * @param boolean $assert Whether or not to make an assertion
	 */
	function __construct($community_id, $assert = true) {
        $community_course = Models_Community_Course::fetchRowByCommunityID($community_id);
        if ($community_course) {
            $course = Models_Course::get($community_course->getCourseID());
            if ($course) {
                $this->organisation_id = $course->getOrganisationID();
            }
        }
        $this->assert = (bool)$assert;
	}

	/**
	 * ACL method for keeping track. Required by Zend_Acl_Resource_Interface.
	 * Will return based on specifc property of this resource instance.
	 * @return string
	 */
	public function getResourceId() {
		return "communityadmin";
	}
}

/**
 * Smart course content resource object for the EntradaACL.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Harry Brundage <hbrundage@qmed.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 */
class CourseContentResource extends CourseResource {
	/**
	 * ACL method for keeping track. Required by Zend_Acl_Resource_Interface.
	 * Will return based on specifc property of this resource instance.
	 * @return string
	 */
	public function getResourceId() {
		return "coursecontent".($this->specific ? $this->course_id : "");
	}
}

/**
 * Smart course group resource object for the EntradaACL.
 *
 * @author Organisation: David Geffen School of Medicine at UCLA
 * @author Unit: IDTU
 * @author Developer: Samuel Payne <spayne@mednet.ucla.edu>
 */
class CourseGroupResource extends CourseResource {
	/**
	 * ACL method for keeping track. Required by Zend_Acl_Resource_Interface.
	 * Will return based on specifc property of this resource instance.
	 * @return string
	 */
	public function getResourceId() {
		return "coursegroup".($this->specific ? $this->course_id : "");
	}
}

/**
 * Smart event content resource object for the EntradaACL.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Harry Brundage <hbrundage@qmed.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 */
class EventContentResource extends EventResource {
	/**
	 * ACL method for keeping track. Required by Zend_Acl_Resource_Interface.
	 * Will return based on specifc property of this resource instance.
	 * @return string
	 */
	public function getResourceId() {
		return "eventcontent".($this->specific ? $this->course_id : "");
	}
}

class QuizResource extends EntradaAclResource {
	var $quiz_id;

	function __construct($quiz_id, $assert = null) {
		$this->quiz_id = $quiz_id;
	}


	public function getResourceId() {
		return "quiz".($this->specific ? $this->quiz_id : "");
	}
}

class QuizResultResource extends QuizResource {
	public function getResourceId() {
		return "quizresult".($this->specific ? $this->quiz_id : "");
	}
}

class QuizQuestionResource extends QuizResource {
	var $quiz_question_id;

	function __construct($quiz_question_id, $quiz_id, $assert = null) {
		$this->quiz_question_id = $quiz_question_id;
		$this->quiz_id = $quiz_id;
	}

	public function getResourceId() {
		return "quizquestion".($this->specific ? $this->quiz_id : "");
	}
}

class EvaluationResource extends EntradaAclResource {
	var $evaluation_id;

	function __construct($evaluation_id, $organisation_id, $assert = null) {
		$this->evaluation_id = $evaluation_id;
		$this->organisation_id = $organisation_id;
	}

	public function getResourceId() {
		return "evaluation".($this->specific ? $this->evaluation_id : "");
	}
}

class EvaluationReviewerAssertion implements Zend_Acl_Assert_Interface {
	public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {
		global $db;

		//If asserting is off then return true right away
		if ((isset($resource->assert) && $resource->assert == false) || (isset($acl->_entrada_last_query) && isset($acl->_entrada_last_query->assert) && $acl->_entrada_last_query->assert == false)) {
			return true;
		}

		if (isset($resource->evaluation_id)) {
			$evaluation_id = $resource->evaluation_id;
		} else if (isset($acl->_entrada_last_query->evaluation_id)) {
			$evaluation_id = $acl->_entrada_last_query->evaluation_id;
		} else {
			//Parse out the user ID and course ID
			$resource_id = $resource->getResourceId();
			$resource_type = preg_replace('/[0-9]+/', "", $resource_id);

			if ($resource_type !== "evaluation") {
			//This only asserts for users reviewing evaluations.
				return false;
			}

			$evaluation_id = preg_replace('/[^0-9]+/', "", $resource_id);
		}

		$role_id = $role->getRoleId();
		$access_id	= preg_replace('/[^0-9]+/', "", $role_id);

		$query = "SELECT `user_id` FROM `".AUTH_DATABASE."`.`user_access`
					WHERE `id` = ".$db->qstr($access_id);
		$user_id = $db->GetOne($query);

		if (!isset($user_id) || !$user_id) {
			$role_id = $acl->_entrada_last_query_role->getRoleId();
			$access_id	= preg_replace('/[^0-9]+/', "", $role_id);

			$query = "SELECT `user_id` FROM `".AUTH_DATABASE."`.`user_access`
						WHERE `id` = ".$db->qstr($access_id);
			$user_id = $db->GetOne($query);
		}

		$permissions = Classes_Evaluation::getReviewPermissions($evaluation_id);
		if (count($permissions)) {
			return true;
		} else {
			return false;
		}
	}
}

class EvaluationFormAuthorAssertion implements Zend_Acl_Assert_Interface {
	public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {
		global $db;

		//If asserting is off then return true right away
		if ((isset($resource->assert) && $resource->assert == false) || (isset($acl->_entrada_last_query) && isset($acl->_entrada_last_query->assert) && $acl->_entrada_last_query->assert == false)) {
			return true;
		}

		if (isset($resource->eform_id)) {
			$eform_id = $resource->eform_id;
		} else if (isset($acl->_entrada_last_query->eform_id)) {
			$eform_id = $acl->_entrada_last_query->eform_id;
		} else {
			//Parse out the user ID and course ID
			$resource_id = $resource->getResourceId();
			$resource_type = preg_replace('/[0-9]+/', "", $resource_id);

			if ($resource_type !== "evaluationform") {
			//This only asserts for users authoring evaluation forms.
				return false;
			}

			$eform_id = preg_replace('/[^0-9]+/', "", $resource_id);
		}

		$role_id = $role->getRoleId();
		$access_id	= preg_replace('/[^0-9]+/', "", $role_id);

		$query = "SELECT `user_id` FROM `".AUTH_DATABASE."`.`user_access`
					WHERE `id` = ".$db->qstr($access_id);
		$user_id = $db->GetOne($query);

		if (!isset($user_id) || !$user_id) {
			$role_id = $acl->_entrada_last_query_role->getRoleId();
			$access_id	= preg_replace('/[^0-9]+/', "", $role_id);

			$query = "SELECT `user_id` FROM `".AUTH_DATABASE."`.`user_access`
						WHERE `id` = ".$db->qstr($access_id);
			$user_id = $db->GetOne($query);
		}

		$permissions = Classes_Evaluation::getFormAuthorPermissions($eform_id);
		if ($permissions) {
			return true;
		} else {
			return false;
		}
	}
}

class EvaluationResultResource extends EvaluationResource {
	public function getResourceId() {
		return "evaluationresult".($this->specific ? $this->evaluation_id : "");
	}
}

class EvaluationFormResource extends EvaluationResource {
	var $eform_id;

	function __construct($eform_id, $organisation_id, $assert = null) {
		$this->eform_id = $eform_id;
		$this->organisation_id = $organisation_id;
	}
	public function getResourceId() {
		return "evaluationform".($this->specific ? $this->evaluation_id : "");
	}
}

class EvaluationQuestionResource extends EvaluationResource {
	var $equestion_id;

	function __construct($equestion_id, $organisation_id, $assert = null) {
		$this->equestion_id = $equestion_id;
		$this->organisation_id = $organisation_id;
	}
	public function getResourceId() {
		return "evaluationquestion".($this->specific ? $this->equestion_id : "");
	}
}

class EvaluationFormQuestionResource extends EvaluationResource {
	var $evaluation_form_question_id;

	function __construct($evaluation_form_question_id, $evaluation_id, $assert = null) {
		$this->evaluation_form_question_id = $evaluation_form_question_id;
		$this->evaluation_id = $evaluation_id;
	}

	public function getResourceId() {
		return "evaluationformquestion".($this->specific ? $this->evaluation_id : "");
	}
}



class CommunityResource extends EntradaAclResource {
	/**
	 * This community's ID
	 * @var integer
	 */
	var $community_id;

	/**
	 * Constructs this community resource with the supplied values
	 * @param integer $community_id The ID of the community this resource is representing
	 * @param boolean $assert Wheather or not to make an assertion
	 */
	function __construct($community_id, $assert = null) {
		$this->community_id = $community_id;
		if (isset($assert)) {
			$this->assert = $assert;
		}
	}

	/**
	 * ACL method for keeping track. Required by Zend_Acl_Resource_Interface.
	 * Will return based on specifc property of this resource instance.
	 * @return string
	 */
	public function getResourceId() {
		return "community".($this->specific ? $this->community_id : "");
	}
}

class EntradaUser implements Zend_Acl_Role_Interface {
	var $userid;
	var $details;
	function EntradaUser($a_userid) {
		$this->userid = $a_userid;
	}
	function getRoleId() {
		return $this->userid;
	}
}

/**
 * Department Head Assertion Class
 *
 * Checks to see if the faculty department head's proxy_id is in the department_heads table
 * which therefore gives them access to the Department Reports section within My Reports.
 */
class DepartmentHeadAssertion implements Zend_Acl_Assert_Interface {
	public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {
		global $db, $ENTRADA_USER;

		// This was done so that the correct proxy_id was being used as $role->details["id"] was not using the "masked" id.
		// I'm sure there is a way to get this ID without using the SESSION but I needed to get this into production ASAP.
		// I will fix this as soon as I find out how to access the masked ID without going through the session.
		if (!(is_department_head($ENTRADA_USER->getActiveId()))) {
			return false;
		} else {
			return true;
		}

		return false;
	}
}
/**
 * Dean Assertion Class
 *
 * Checks to see if the if the user is a dean
 * which therefore gives them access to their performance appraisal within the profile section.
 */
class DeanAssertion implements Zend_Acl_Assert_Interface {
	public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {
		global $db, $ENTRADA_USER;

		// This was done so that the correct proxy_id was being used as $role->details["id"] was not using the "masked" id.
		// I'm sure there is a way to get this ID without using the SESSION but I needed to get this into production ASAP.
		// I will fix this as soon as I find out how to access the masked ID without going through the session.
		if (!(is_dean($ENTRADA_USER->getActiveId()))) {
			return false;
		} else {
			return true;
		}

		return false;
	}
}
/**
 * Logbook Assertion Class
 *
 * Checks to see if the user has access to a course with loggable objectives
 * associated with it.
 */
class LoggableFoundAssertion implements Zend_Acl_Assert_Interface {
	public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {
		$courses = Models_Logbook::getLoggingCourses();
		if ($courses && @count($courses)) {
			return true;
		} else {
			return false;
		}

		return false;
	}
}

class EportfolioOwnerAssertion implements Zend_Acl_Assert_Interface {
	public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {
		return true;
	}
}

class ExamResource extends EntradaAclResource {

    /**
     * The exam resource ID ie. question_id, exam_id or group_id
     * @var integer
     */
    var $exam_resource_id;

    /**
     * Constructs this exams resource with the supplied values
     * @param integer $exam_resource_id The exams resource ID to represent
     * @param boolean $assert Wheather or not to make an assertion
     */
    function __construct($exam_resource_id = null, $assert = null) {

        $this->exam_resource_id = $exam_resource_id;

        if (isset($assert)) {

            $this->assert = $assert;
        }
    }

    /**
     * ACL method for keeping track. Required by Zend_Acl_Resource_Interface.
     * Will return based on specifc property of this resource instance.
     * @return string
     */
    public function getResourceId() {
        return "exam".($this->specific ? $this->exam_resource_id : "");
    }
}

class ExamOwnerAssertion implements Zend_Acl_Assert_Interface {
    public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {
        global $db, $ENTRADA_USER;

        if (isset($resource->exam_resource_id)) {
            $exam_resource_id = $resource->exam_resource_id;
        } else if (isset($acl->_entrada_last_query->exam_resource_id)) {
            $exam_resource_id = $acl->_entrada_last_query->exam_resource_id;
        } else {
            //Parse out the resource ID
            $resource_id = $resource->getResourceId();
            $exam_resource_id = preg_replace('/[^0-9]+/', "", $resource_id);
        }

		$exam_authors = Models_Exam_Exam_Author::fetchAllByExamID($exam_resource_id);
        $resource_authors = (is_array($exam_authors) && !empty($exam_authors)) ? $exam_authors : false;
        $is_owner = false;

        if ($resource_authors) {
            foreach ($resource_authors as $author) {
                switch ($author->getAuthorType()) {
                    case "course_id" :

                        $is_course_owner = Models_Course::checkCourseOwner($author->getAuthorID(), $ENTRADA_USER->getActiveID());

                        if ($is_course_owner) {
                            $is_owner = true;
                        }

                        break;
                    case "proxy_id" :

                        if ((int) $author->getAuthorID() === (int) $ENTRADA_USER->getActiveID()) {
                            $is_owner = true;
                        }

                        break;
                    case "organisation_id" :

                        if ((int) $author->getAuthorID() === (int) $ENTRADA_USER->getActiveOrganisation()) {
                            $is_owner = true;
                        }

                        break;
                }
            }
        }

        return $is_owner;
    }
}

class ExamQuestionResource extends EntradaAclResource {

    /**
     * The exam resource ID ie. question_id, exam_id or group_id
     * @var integer
     */
    var $exam_resource_id;

    /**
     * The exam resource type ie. question, exam, or group
     * @var integer
     */
    var $exam_resource_type;

    /**
     * Constructs this exams resource with the supplied values
     * @param integer $exam_resource_id The exams resource ID to represent
     * @param boolean $assert Wheather or not to make an assertion
     */
    function __construct($exam_resource_id = null, $assert = null) {

        $this->exam_resource_id = $exam_resource_id;

        if (isset($assert)) {

            $this->assert = $assert;
        }
    }

    /**
     * ACL method for keeping track. Required by Zend_Acl_Resource_Interface.
     * Will return based on specifc property of this resource instance.
     * @return string
     */
    public function getResourceId() {
        return "examquestion".($this->specific ? $this->exam_resource_id : "");
    }
}

class ExamQuestionOwnerAssertion implements Zend_Acl_Assert_Interface {
    public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {
        global $db, $ENTRADA_USER, $ENTRADA_ACL;

        if (isset($resource->exam_resource_id)) {
            $exam_resource_id = $resource->exam_resource_id;
        } else if (isset($acl->_entrada_last_query->exam_resource_id)) {
            $exam_resource_id = $acl->_entrada_last_query->exam_resource_id;
        } else {
            //Parse out the resource ID
            $resource_id = $resource->getResourceId();
            $exam_resource_id = preg_replace('/[^0-9]+/', "", $resource_id);
        }

        $resource_authors = false;
        $resource_authors = Models_Exam_Question_Authors::fetchAllByVersionID($exam_resource_id, $ENTRADA_USER->getActiveOrganisation());

        $is_owner = false;

        if ($resource_authors) {
            foreach ($resource_authors as $author) {
                switch ($author->getAuthorType()) {
                    case "course_id" :

                        $is_course_owner = Models_Course::checkCourseOwner($author->getAuthorID(), $ENTRADA_USER->getActiveID());

                        if ($is_course_owner) {
                            $is_owner = true;
                        }

                        break;
                    case "proxy_id" :

                        if ((int) $author->getAuthorID() === (int) $ENTRADA_USER->getActiveID()) {
                            $is_owner = true;
                        }

                        break;
                    case "organisation_id" :

                        if ((int) $author->getAuthorID() === (int) $ENTRADA_USER->getActiveOrganisation()) {
                            $is_owner = true;
                        }

                        break;
                }
            }
        }

        if ($is_owner === false) {
            //check permission to the folder the question is in
            $question = Models_Exam_Question_Versions::fetchRowByVersionID($exam_resource_id);
			if (isset($question) && is_object($question)) {
				$folder = $question->getParentFolder();
				if (isset($folder) && is_object($folder)) {
					$update_folder = $ENTRADA_ACL->amIAllowed(new ExamFolderResource($folder->getID(), true), "update");
					if ($update_folder) {
						$is_owner = true;
					}
				}
            }
        }

        return $is_owner;
    }
}


class ExamQuestionGroupResource extends EntradaAclResource {

	/**
	 * The exam resource ID ie. question_id, exam_id, group_id, etc.
	 * @var integer
	 */
	var $exam_resource_id;

	/**
	 * The exam resource type ie. question, exam, or group
	 * @var integer
	 */
	var $exam_resource_type;

	/**
	 * Constructs this exams resource with the supplied values
	 * @param integer $exam_resource_id The exams resource ID to represent
	 * @param boolean $assert Wheather or not to make an assertion
	 */
	function __construct($exam_resource_id = null, $assert = null) {

		$this->exam_resource_id = $exam_resource_id;
		if (isset($assert)) {
			$this->assert = $assert;
		}
	}

	/**
	 * ACL method for keeping track. Required by Zend_Acl_Resource_Interface.
	 * Will return based on specifc property of this resource instance.
	 * @return string
	 */
	public function getResourceId() {
		return "examquestiongroup".($this->specific ? $this->exam_resource_id : "");
	}
}

class ExamQuestionGroupOwnerAssertion implements Zend_Acl_Assert_Interface {
	public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {
		global $db, $ENTRADA_USER, $ENTRADA_ACL;

		if (isset($resource->exam_resource_id)) {
			$exam_resource_id = $resource->exam_resource_id;
		} else if (isset($acl->_entrada_last_query->exam_resource_id)) {
			$exam_resource_id = $acl->_entrada_last_query->exam_resource_id;
		} else {
			//Parse out the resource ID
			$resource_id = $resource->getResourceId();
			$exam_resource_id = preg_replace('/[^0-9]+/', "", $resource_id);
		}
		$resource_authors = false;
		$resource_authors = Models_Exam_Group_Author::fetchAllByGroupID($exam_resource_id);

		$is_owner = false;
		if ($resource_authors && is_array($resource_authors)) {
			foreach ($resource_authors as $author) {
				if ($author && is_object($author)) {
					switch ($author->getAuthorType()) {
						case "course_id" :
							$is_course_owner = Models_Course::checkCourseOwner($author->getAuthorID(), $ENTRADA_USER->getActiveID());
							if ($is_course_owner) {
								return true;
							}
							break;
						case "proxy_id" :
							if ((int) $author->getAuthorID() === (int) $ENTRADA_USER->getActiveID()) {
								return true;
							}
							break;
						case "organisation_id" :
							if ((int) $author->getAuthorID() === (int) $ENTRADA_USER->getActiveOrganisation()) {
								return true;
							}
							break;
					}
				}
			}
		}

		$resource_questions = Models_Exam_Group_Question::fetchAllByGroupID($exam_resource_id);
		if ($resource_questions && is_array($resource_questions)) {
			foreach ($resource_questions as $question) {
				if ($question && is_object($question)) {
					if ($ENTRADA_ACL->amIAllowed(new ExamQuestionResource($question->getVersionID(), true), "update")) {
						return true;
					}
				}
			}
		}

		return $is_owner;
	}
}

class ExamFolderResource extends EntradaAclResource {

    /**
     * The exam resource ID ie. question_id, exam_id or group_id
     * @var integer
     */
    var $exam_resource_id;

    /**
     * The exam resource type ie. question, exam, or group
     * @var integer
     */
    var $exam_resource_type;

    /**
     * Constructs this exams resource with the supplied values
     * @param integer $exam_resource_id The exams resource ID to represent
     * @param string $exam_resource_type The exams resource type to represent
     * @param boolean $assert Wheather or not to make an assertion
     */
    function __construct($exam_resource_id = null, $assert = null) {

        $this->exam_resource_id = $exam_resource_id;

        if (isset($assert)) {

            $this->assert = $assert;
        }
    }

    /**
     * ACL method for keeping track. Required by Zend_Acl_Resource_Interface.
     * Will return based on specifc property of this resource instance.
     * @return string
     */
    public function getResourceId() {
        return "examfolder".($this->specific ? $this->exam_resource_id : "");
    }
}

class ExamFolderOwnerAssertion implements Zend_Acl_Assert_Interface {
    public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {
        global $db, $ENTRADA_USER;

        if (isset($resource->exam_resource_id)) {
            $exam_resource_id = $resource->exam_resource_id;
        } else if (isset($acl->_entrada_last_query->exam_resource_id)) {
            $exam_resource_id = $acl->_entrada_last_query->exam_resource_id;
        } else {
            //Parse out the resource ID
            $resource_id = $resource->getResourceId();
            $exam_resource_id = preg_replace('/[^0-9]+/', "", $resource_id);
        }

        $resource_authors 	= false;
        $resource_authors 	= Models_Exam_Question_Bank_Folder_Authors::fetchAllInheritedByFolderID($exam_resource_id, false);
		$resource_orgs 		= Models_Exam_Question_Bank_Folder_Organisations::fetchAllByFolderID($exam_resource_id, false);

        $is_owner 			= false;
		$org_valid 			= false;
		$orgs 				= array();

		if ($resource_orgs && is_array($resource_orgs) && !empty($resource_orgs)) {
			foreach ($resource_orgs as $org) {
				$orgs[] = $org->getOrganisationId();
			}

			if ($orgs && is_array($orgs)) {
				if (in_array($ENTRADA_USER->getActiveOrganisation(), $orgs)) {
					$org_valid = true;
				}
			}
		}

        if ($resource_authors && $org_valid) {
            foreach ($resource_authors as $type => $authors) {
                switch ($type) {
                    case "course_id" :
                        if (isset($authors) && is_array($authors) && !empty($authors)) {
                            foreach ($authors as $item) {
                                $author = $item["object"];
                                if (isset($author) && is_object($author)) {
                                    $is_course_owner = Models_Course::checkCourseOwner($author->getAuthorID(), $ENTRADA_USER->getActiveID());

                                    if ($is_course_owner) {
                                        $is_owner = true;
                                        break;
                                    }
                                }
                            }
                        }

                        break;
                    case "proxy_id" :
                        if (isset($authors) && is_array($authors) && !empty($authors)) {
                            foreach ($authors as $item) {
                                $author = $item["object"];
                                if (isset($author) && is_object($author)) {
                                    if ((int) $author->getAuthorID() === (int) $ENTRADA_USER->getActiveID()) {
                                        $is_owner = true;
                                        break;
                                    }

                                }
                            }
                        }

                        break;
                    case "organisation_id" :
                        if (isset($authors) && is_array($authors) && !empty($authors)) {
                            foreach ($authors as $item) {
                                $author = $item["object"];
                                if (isset($author) && is_object($author)) {
                                    if ((int) $author->getAuthorID() === (int) $ENTRADA_USER->getActiveOrganisation()) {
                                        $is_owner = true;
                                        break;
                                    }
                                }
                            }
                        }

                        break;
                }
            }
        }

        return $is_owner;
    }
}

class AssessmentComponentResource extends EntradaAclResource {
    
    /**
     * The assessment resource ID ie. item_id, form_id or rubric_id
     * @var integer
     */
    var $assessment_resource_id;
    
    /**
     * The assessment resource type ie. item, form, or rubric
     * @var integer
     */
    var $assessment_resource_type;

    /**
     * Constructs this assessments resource with the supplied values
     * @param integer $assessment_resource_id The assessments resource ID to represent
     * @param string $assessment_resource_type The assessments resource type to represent
     * @param boolean $assert Wheather or not to make an assertion
     */
    function __construct($assessment_resource_id = null, $assessment_resource_type = null, $assert = null) {
        
        $this->assessment_resource_id = $assessment_resource_id;
        
        $this->assessment_resource_type = $assessment_resource_type;
         

        if (isset($assert)) {
            
            $this->assert = $assert;
        }
    }

    /**
     * ACL method for keeping track. Required by Zend_Acl_Resource_Interface.
     * Will return based on specifc property of this resource instance.
     * @return string
     */
    public function getResourceId() {
        return "assessmentcomponent".($this->specific ? $this->assessment_resource_id : "");
    }
}

class AssessmentComponentAssertion implements Zend_Acl_Assert_Interface {
    public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {
        global $db, $ENTRADA_USER;
        
        if (isset($resource->assessment_resource_id)) {
            $assessment_resource_id = $resource->assessment_resource_id;
        } else if (isset($acl->_entrada_last_query->assessment_resource_id)) {
            $assessment_resource_id = $acl->_entrada_last_query->assessment_resource_id;
        } else {
            //Parse out the resource ID
            $resource_id = $resource->getResourceId();
            $assessment_resource_id = preg_replace('/[^0-9]+/', "", $resource_id);
        }
        
        $resource_authors = false;
        
        switch ($resource->assessment_resource_type) {
            case "item" :
                $resource_authors = Models_Assessments_Item_Author::fetchAllByItemID($assessment_resource_id, $ENTRADA_USER->getActiveOrganisation());
            break;
            case "form" :
                $resource_authors = Models_Assessments_Form_Author::fetchAllByFormID($assessment_resource_id, $ENTRADA_USER->getActiveOrganisation());
            break;
            case "rubric" :
                $resource_authors = Models_Assessments_Rubric_Author::fetchAllByRubricID($assessment_resource_id, $ENTRADA_USER->getActiveOrganisation());
            break;
        }
        
        $is_owner = false;
        
        if ($resource_authors) {
            foreach ($resource_authors as $author) {
                switch ($author->getAuthorType()) {
                    case "course_id" :
                        
                        $is_course_owner = Models_Course::checkCourseOwner($author->getAuthorID(), $ENTRADA_USER->getActiveID());
                        
                        if ($is_course_owner) {
                            $is_owner = true;
                        }

                    break;
                    case "proxy_id" :
                        
                        if ((int) $author->getAuthorID() === (int) $ENTRADA_USER->getActiveID()) {
                            $is_owner = true;
                        }
                        
                    break;
                    case "organisation_id" :
                        
                        if ((int) $author->getAuthorID() === (int) $ENTRADA_USER->getActiveOrganisation()) {
                            $is_owner = true;
                        }
                        
                    break;
                }
            }
        }
        
        return $is_owner;
        
	}
}

class CourseGroupContact implements Zend_Acl_Assert_Interface {
	public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {

		if (!($resource instanceof CourseGroupResource)) {
			return false;
		}
		if (!isset($resource->cgroup_id)) {
			return false;
		}

		$role = $acl->_entrada_last_query_role;
		if (!isset($role->details["id"])) {
			return false;
		}

		$course_group_contact = Models_Course_Group_Contact::fetchRowByProxyIDCGroupID($role->details["id"], $resource->cgroup_id);
		if ($course_group_contact) {
			return true;
		}

		return false;
	}
}

class AcademicAdvisorResource extends EntradaAclResource {
	var $proxy_id;

	function __construct($proxy_id, $assert = null) {
		$this->proxy_id = $proxy_id;
	}

	public function getResourceId() {
		return "academicadvisor".($this->specific ? $this->proxy_id : "");
	}
}

class AcademicAdvisorAssertion implements Zend_Acl_Assert_Interface {
	public function assert(Zend_Acl $acl, Zend_Acl_Role_Interface $role = null, Zend_Acl_Resource_Interface $resource = null, $privilege = null) {
		//If asserting is off then return true right away
		if ((isset($resource->assert) && $resource->assert == false) || (isset($acl->_entrada_last_query) && isset($acl->_entrada_last_query->assert) && $acl->_entrada_last_query->assert == false)) {
			return true;
		}

		if (!($resource instanceof AcademicAdvisorResource)) {
			return false;
		}
		if (!isset($resource->proxy_id)) {
			return false;
		}

		$role = $acl->_entrada_last_query_role;
		if (!isset($role->details["id"])) {
			return false;
		}
		if (!isset($role->details["organisation_id"])) {
			return false;
		}
		$course_group_contact = Models_Course_Group::facultyMemberIsTutor($role->details["id"], $resource->proxy_id, $role->details["organisation_id"]);
		if ($course_group_contact) {
			return true;
		}
        $courses = Models_Course::getUserCourses($role->details["id"], $role->details["organisation_id"]);
        if ($courses) {
            foreach ($courses as $course) {
                if (CourseOwnerAssertion::_checkCourseOwner($role->details["id"], $course->getID())) {
                    $audience = $course->getAudience();
                    foreach ($audience as $audience_member) {
                        if ($audience_member->getAudienceType() == "group_id") {
                            $group_members = $audience_member->getMembers();
                            if ($group_members) {
                                foreach ($group_members as $member) {
                                    if ($member->getID() == $resource->proxy_id) {
                                        return true;
                                    }
                                }
                            }
                        } else {
                            if ($audience_member->getAudienceValue() == $resource->proxy_id) {
                                return true;
                            }
                        }
                    }
                }
            }
        }

		return false;
	}
}

class SecureAccessKeyResource extends EntradaAclResource {
	/**
	 * This Access Key ID
	 * @var integer
	 */
	var $key_id;

	/**
	 * Constructs this secure access key  resource with the supplied values
	 * @param integer $key_id The ID of the secure access key this resource is representing
	 * @param boolean $assert Whether or not to make an assertion
	 */
	function __construct($key_id, $assert = null) {
		$this->key_id = $key_id;
		if (isset($assert)) {
			$this->assert = $assert;
		}
	}

	/**
	 * ACL method for keeping track. Required by Zend_Acl_Resource_Interface.
	 * Will return based on specifc property of this resource instance.
	 * @return string
	 */
	public function getResourceId() {
		return "secureaccesskey".($this->specific ? $this->key_id : "");
	}
}
class SecureAccessFileResource extends EntradaAclResource {
	/**
	 * This Access File ID
	 * @var integer
	 */
	var $file_id;

	/**
	 * Constructs this secure access file  resource with the supplied values
	 * @param integer $file_id The ID of the secure access file this resource is representing
	 * @param boolean $assert Whether or not to make an assertion
	 */
	function __construct($file_id, $assert = null) {
		$this->file_id = $file_id;
		if (isset($assert)) {
			$this->assert = $assert;
		}
	}

	/**
	 * ACL method for keeping track. Required by Zend_Acl_Resource_Interface.
	 * Will return based on specifc property of this resource instance.
	 * @return string
	 */
	public function getResourceId() {
		return "secureaccessfile".($this->specific ? $this->file_id : "");
	}
}
