<?php


require_once("ClinicalPerformanceEvaluation.class.php");
require_once("Classes/utility/Collection.class.php");

class ClinicalPerformanceEvaluations extends Collection {
	public static function get(User $user) {
		global $db;
		$user_id = $user->getID();
		$query = "SELECT * FROM `student_clineval_comments` WHERE `user_id` = ".$db->qstr($user_id)." ORDER BY `source` ASC";
		$results = $db->getAll($query);
		$clinevals = array();
		if ($results) {
			foreach ($results as $result) {
				$clineval = ClinicalPerformanceEvaluation::fromArray($result);
				$clinevals[] = $clineval;
			}
		}
		return new self($clinevals);
	}
}