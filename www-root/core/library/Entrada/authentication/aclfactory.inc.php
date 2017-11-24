<?php
/**
 * Extended Zend_Acl_Role_Registry to support passing of role and resource objects instead of just their interfaces. See links.
 *
 * @link http://www.aviblock.com/blog/2009/03/19/acl-in-zend-framework%5C/, http://zendframework.com/issues/browse/ZF-1721
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Harry Brundage <hbrundage@qmed.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 */
class RoleRegistry extends Zend_Acl_Role_Registry {
	public function get($role) {
		if ($role instanceof Zend_Acl_Role_Interface) {
			return $role;
		}

		return parent::get($role);

	}
}

/**
 * Extended Zend_Acl to support passing of role and resource objects instead of their interfaces. See links.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Harry Brundage <hbrundage@qmed.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 */
class Zend_Acl_Plus extends Zend_Acl {
	/**
	 * Storage for the resource being queried, to enable easy access by assertions
	 * @var Zend_Acl_Resource_Interface
	 */
	public $_entrada_last_query;

	/**
	 * Storage for the querying role to enable easy access by assertions
	 * @var Zend_Acl_Resource_Interface
	 */
	public $_entrada_last_query_role;
}

/**
 * Generic factory for the application of role-resource permissions to an ACL
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Harry Brundage <hbrundage@qmed.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 */
class ACL_Factory {
/**
 * The true ACL object containing all the roles, resources, and rules.
 * @var Zend_Acl
 */
	var $acl;

	var $crud = array("create", "read", "update", "delete");

	/**
	 * Sets up this instance of ACL_Factory
	 * @param Zend_Acl $a_acl The base acl object to work with
	 */
	function __construct(Zend_Acl $a_acl) {
		$this->acl = $a_acl;
	}

	/**
	 * Applies the supplied rules to $this->acl
	 *
	 * @param array $rrpermissions
	 */
	function create_acl($rrpermissions) {
		foreach($rrpermissions as $perm) {
		//Check for specific group:role combination permissions
			
			if(isset($perm["entity_type"]) && $perm["entity_type"] == "group:role") {
				//Group:Role
				if(!isset($perm["entity_value"])) {
					application_log("error", "Permission [".$perm["permission_id"]."] cannot have a null entity value. Please fix this in the database.");
					continue;
				}

				$entity_vals = explode(":", $perm["entity_value"]);

				if(!isset($entity_vals[1])) {
					application_log("error", "Permission [".$perm["permission_id"]."] needs to have both a group AND a role seperated by a colon. Please fix this in the database.");
					continue;
				}

				$perm["entity_type"] = "role";
				$perm["entity_value"] = $entity_vals[1];
			
			} else if(isset($perm["entity_type"]) && $perm["entity_type"] == "organisation:group") {
					
					//Organisation:group
					if(!isset($perm["entity_value"])) {
						application_log("error", "Permission [".$perm["permission_id"]."] cannot have a null entity value. Please fix this in the database.");
						continue;
					}

					$entity_vals = explode(":", $perm["entity_value"]);

					if(!isset($entity_vals[1])) {
						application_log("error", "Permission [".$perm["permission_id"]."] needs to have both an organisation AND a group seperated by a colon. Please fix this in the database.");
						continue;
					}

					$perm["entity_type"] = "group";
					$perm["entity_value"] = $entity_vals[1];
				} else if(isset($perm["entity_type"]) && $perm["entity_type"] == "organisation:group:role") {
					
					//Organisation:group:role
						if(!isset($perm["entity_value"])) {
							application_log("error", "Permission [".$perm["permission_id"]."] cannot have a null entity value. Please fix this in the database.");
							continue;
						}

						$entity_vals = explode(":", $perm["entity_value"]);

						if(!isset($entity_vals[1]) || !isset($entity_vals[2])) {
							application_log("error", "Permission [".$perm["permission_id"]."] needs to have both a group, role AND organisation seperated by a colon. Please fix this in the database.");
							continue;
						}

						$perm["entity_type"] = "role";
						$perm["entity_value"] = $entity_vals[2];
					}

			$isset = ''.
				(isset($perm["resource_type"])	? 1 : 0).
				(isset($perm["resource_value"])	? 1 : 0).
				(isset($perm["entity_type"])	? 1 : 0).
				(isset($perm["entity_value"])	? 1 : 0);
			$add = true;
			switch ($isset) {
				case '1011':
				//Allow this specific entity to access this type of resource by giving the parent most entity access to the generic resource type.
				//This will cover both the generics tree and the nested generics resource tree.
					$entity = $perm["entity_type"].$perm["entity_value"];
					$resource = $perm["resource_type"];
					break;
				case '1000':
				//Allow any entity to access this type of resource by giving the parent most entity access to the generic resource type
				//This will cover both the generics and the nested generics resource tree.
					$entity = "organisation";
					$resource = $perm["resource_type"];
					break;
				case '1111':
				//Allow a specific entitiy to access a specific resource type. Whambamjam.
					$entity = $perm["entity_type"].$perm["entity_value"];
					$resource = $perm["resource_type"].$perm["resource_value"];
					break;
				case '1100':
				//Allow any entity to access this specific resource by giving the parent most entity access, so all it's children will inherit.
					$entity = "organisation";
					$resource = $perm["resource_type"].$perm["resource_value"];
					break;
				case '0011':
				//Allow a specific entity to access any resource by giving the entity access to the parent most resource (mom)
					$entity = $perm["entity_type"].$perm["entity_value"];
					$resource = "mom";
					break;
				default:
					application_log("error", "Permission[".$perm["permission_id"]."] pertains to a non permitted series of designators. Please fix this in the database.");
					$add = false;
					break;
			}

			if($add) {
				if (!$this->acl->has($resource)) {
                    //Error! The tree builder should have added all the resources needed.
                    //This could happen if say two Entrada instances are using the same auth database,
                    //which may or may not have different ACL resource sets.
                    if (DEVELOPMENT_MODE) {
                        application_log("error", "Resource [".$resource."] isn't defined in the ACL resource tree. Please fix this in the entrada_acl.php.");
                    }
				} else {
					$asserter = null;
                    if (isset($perm["assertion"])) {
                        $assertions = explode('&', $perm["assertion"]);

                        if (isset($assertions[1])) {
                            $asserter = new MultipleAssertion($assertions);
                        } else {
                            $assertion_name = $perm["assertion"] . "Assertion";
                            if (class_exists($assertion_name, true)) {
                                $asserter = new $assertion_name();
                            }
                        }
                    }

                    $permissions_to_be_granted	= array();
                    $permissions_to_be_denyed	= array();

                    foreach ($this->crud as $individual_perm) {
                        //Only set a rule if the permission is 1 or 0, disregard null values.
                        if($perm[$individual_perm] == '1') {
                            $permissions_to_be_granted[] = $individual_perm;
                        } else if ($perm[$individual_perm] == '0') {
                            $permissions_to_be_denyed[] = $individual_perm;
                        }
                    }

                    if (count($permissions_to_be_granted) > 0) {
                        $this->acl->allow($entity, $resource, $permissions_to_be_granted, $asserter);
                        //echo "Granting $entity ".$permissions_to_be_granted[0].$permissions_to_be_granted[1].$permissions_to_be_granted[2].$permissions_to_be_granted[3]." on $resource with ".get_class($asserter).". \n";
                    }

                    if (count($permissions_to_be_denyed) > 0) {
                        $this->acl->deny($entity, $resource, $permissions_to_be_denyed, $asserter);
                    }
                }
			}
		}
	}

	/**
	 * Checks the Acl to see if this $user (role) can preform this $action on this $resource. If no specific rules have been defined for this $resource, the resource's type will be found
	 * and it will be checked.
	 *
	 * @param string|Zend_Acl_Role_Interface $user The user to check
	 * @param string|Zend_Acl_Resource_Interface $resource The resource to check
	 * @param string $action The privilege to check
	 * @return boolean
	 */
	function isAllowed($user, $resource, $action) {
		//Store role for use by assertions
		if($user instanceof Zend_Acl_Role_Interface) {
			$this->acl->_entrada_last_query_role = $user;
		} else {
			$this->acl->_entrada_last_query_role = new Zend_Acl_Role($user);
		}

		//Grab resource ID and store resource for use by assertions
		if($resource instanceof Zend_Acl_Resource_Interface) {
			$resource_id				= $resource->getResourceId();
			$this->acl->_entrada_last_query		= $resource;
		} else {
			$resource_id = $resource;
			$this->acl->_entrada_last_query		= new Zend_Acl_Resource($resource);
		}
		
		$resourcetype = preg_replace('/[0-9]+/', '', $resource_id);

		if($this->acl->has($resource)) {
			return $this->acl->isAllowed($user, $resource, $action);
		} else if($this->acl->has($resourcetype)) {
			if($resource instanceof Zend_Acl_Resource_Interface) {
				$resourcetype = $resource;
				$resourcetype->specific = false;
			}
			return $this->acl->isAllowed($user, $resourcetype, $action);
		}
		return false;
	}

	/**
	 * Checks the Acl to see if this $user (role) can preform this $action on this $resource. If no specific rules have been defined for this $resource or the specific resource doesn't exist,
	 * this function will return false.
	 *
	 * @param string|Zend_Acl_Role_Interface $user The user to check
	 * @param string|Zend_Acl_Resource_Interface $resource The resource to check
	 * @param string $action The privilege to check
	 * @return boolean
	 */
	function isSpecificallyAllowed($user, $resource, $action) {
		if($this->acl->has($resource)) {
			return $this->acl->isAllowed($user, $resource, $action);
		}
		return false;
	}
}
