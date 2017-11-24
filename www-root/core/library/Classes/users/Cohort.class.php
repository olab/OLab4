<?php

require_once("Classes/utility/Collection.class.php");
require_once("User.class.php");

/**
 * Provides a Collection wrapper to User objects. Methods provide means of getting all users belonging to a supplied cohort
 * @author Jonathan Fingland
 *
 */
class Cohort extends Collection {
	/**
	 * Cohort. 
	 * @var int
	 */
	private $cohort;
	
	/**
	 * Returns a collection of User objects belonging to the provided cohort
	 * <code>
	 * $class = Cohort::get(2014);
	 * foreach ($class as $student) { ... }
	 * </code>
	 * 
	 * @param int $cohort
	 * @return Cohort
	 */
	public static function get($cohort) {
		global $db;
		$query = "SELECT * FROM `".AUTH_DATABASE."`.`user_data` AS a 
				JOIN `group_members` AS b 
				ON a.`id` = b.`proxy_id`
				WHERE b.`group_id` = ".$db->qstr($cohort);
		
		$results = $db->getAll($query);
		$users = array();
		if ($results) {
			foreach ($results as $result) {
				$user = new User();
				$user =  User::fromArray($result, $user);
				$users[] = $user;
			}
		}
		
		return new self($users,$cohort);
	}
	
	function __construct($users, $cohort) {
		parent::__construct($users);
		$this->cohort = $cohort;
	}
	
	/**
	 * returns the cohort for this collection
	 * @return int
	 */
	public function getCohort() {
		return $this->cohort;
	}
	
	/**
	 * alias of getCohort()
	 * @see Cohort::getCohort()
	 * @return int
	 */
	public function getID() {
		return $this->getCohort();
	}
}