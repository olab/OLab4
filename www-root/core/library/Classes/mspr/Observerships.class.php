<?php

require_once("Observership.class.php");
require_once("Classes/utility/Collection.class.php");

class Observerships extends Collection {
	public static function get($where = null, $limit = null) {
		
		global $db;
		
		$allowed_fields = array("student_id", "preceptor_proxy_id", "preceptor_email", "status");
		
		$where_clause = "";
		if ($where && (is_array($where) || get_class($where) == "User")) {
			$where_clause = "WHERE ";
			if (is_array($where)) {
				$i = 0;
				foreach ($where as $key => $value) {
					if (in_array($key, $allowed_fields)) {
						$where_clause .= ($i > 0 ? " AND " : "" ). "`".$key."` = ".$db->qstr($value)." ";
						$i++;
					}
				}
			} else if (get_class($where) == "User") {
				$where_clause .= "`student_id` = ".$db->qstr($where->getID())." AND `status` = 'confirmed'";
				$limit = 8;
			}
			$limit_clause = "";
			if (is_int($limit)) {
				$limit = (int) $limit;
				$limit_clause = " LIMIT " . $limit;
			}

			$query = "SELECT * FROM `student_observerships` ".$where_clause." ORDER BY `order` ASC, `start` ASC ".$limit_clause;
			$results = $db->getAll($query);
			$obss = array();
			if ($results) {
				foreach ($results as $result) {
					$obs = Observership::fromArray($result, "fetch");
					$obss[] = $obs;
				}
			} 
			return new self($obss);
		} else {
			return false;
		}
	}
}