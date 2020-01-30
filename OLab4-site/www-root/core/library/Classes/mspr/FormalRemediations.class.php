<?php

require_once("Classes/utility/Collection.class.php");
require_once("FormalRemediation.class.php");

class FormalRemediations extends Collection {
	public static function get(User $user) {
		global $db;
		$frs = array();
		$user_id = $user->getID();
		$query = "SELECT * FROM `student_formal_remediations` WHERE `user_id` = ".$db->qstr($user_id);
		$results = $db->getAll($query);
		if ($results) {
			foreach ($results as $result) {
				$fr = FormalRemediation::fromArray($result);
				$frs[] = $fr;
			}
		}
		return new self ($frs);
	}
}