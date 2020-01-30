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
 * This file contains all of the functions used within Entrada.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jonathan Fingland <jonathan.fingland@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

require_once("ExternalAwardReceipt.class.php");
require_once("Classes/utility/Collection.class.php");

/**
 * Utility Class for getting a list of Award Recipients
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jonathan Fingland <jonathan.fingland@quensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 */
class ExternalAwardReceipts extends Collection implements AttentionRequirable {
	
	/**
	 * Returns an array of AwardRecipient objects representing students who have been given the award provided by $award_id 
	 * @param int $award_id
	 * @return array
	 */
	static public function get(User $obj) {
		$receipts = self::getByUser($obj);
		return $receipts;
	}
	static private function getByUser(User $user) {
		global $db;
		$query		= "SELECT a.id as `award_receipt_id`, a.* 
				FROM `". DATABASE_NAME ."`.`student_awards_external` a 
				WHERE a.`user_id` = ".$db->qstr($user->getID()) ." 
				order by a.year desc";
		
		$results	= $db->GetAll($query);
		$receipts = array();
		if ($results) {
			foreach ($results as $result) {
				
				$award = ExternalAward::fromArray($result);
				$result['award'] = $award;
				$receipt = ExternalAwardReceipt::fromArray( $result);
				$receipts[] = $receipt;
			}
		}
		return new self($receipts);
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