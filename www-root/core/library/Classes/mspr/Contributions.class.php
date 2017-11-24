<?php

require_once("Contribution.class.php");
require_once("Classes/utility/Collection.class.php");

class Contributions extends Collection implements AttentionRequirable {
	public static function get(User $user) {
		global $db;
		$user_id = $user->getID();
		$query = "SELECT * FROM `student_contributions` WHERE `user_id` = ".$db->qstr($user_id)." ORDER BY `start_year` DESC, `start_month` DESC";
		$results = $db->getAll($query);
		$contributions = array();
		if ($results) {
			foreach ($results as $result) {
				$contribution =  Contribution::fromArray($result);
				$contributions[] = $contribution;
			}
		}
		return new self($contributions);
	}
	
	public function isAttentionRequired() {
		$att_req = false;
		foreach ($this as $element) {
			$att_req = $element->isAttentionRequired();
			if ($att_req) break;
		}
		return $att_req;
	}
}