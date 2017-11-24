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

require_once("Classes/utility/SimpleCache.class.php");
require_once("Classes/users/User.class.php");
require_once("InternalAward.class.php");

require_once("Classes/utility/Editable.interface.php");

/**
 * Class for modelling the receipt of an internal award. Each issuance/receipt is a unique object.  
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jonathan Fingland <jonathan.fingland@quensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 */
class InternalAwardReceipt implements Editable {
	/**
	 * Internal ID of this award receipt
	 * @var int
	 */
	private $award_receipt_id;
	/**
	 * Internal ID of the related award 
	 * @var int
	 */
	private $award_id;
	/**
	 * Proxy ID/User ID of the related user  
	 * @var int
	 */
	private $user_id;
	/**
	 * Year the award was received
	 * @var int
	 */
	private $year;
	
	/**
	 * Constructs new awards
	 * @param int $user_id
	 * @param int $award_id
	 * @param int $award_receipt_id
	 * @param int $year
	 */
	function __construct($user_id, $award_id, $award_receipt_id, $year){
		$this->user_id = $user_id;
		$this->award_id = $award_id;
		$this->award_receipt_id = $award_receipt_id;
		$this->year = $year;
	}
	
	/**
	 * Returns the internal ID of this award receipt
	 * @return int
	 */
	public function getID() {
		return $this->award_receipt_id;
	}
	
	/**
	 * Returns the year the award was issued/received
	 * @return number
	 */
	public function getAwardYear() {
		return $this->year;
	}
	
	/**
	 * Returns the User object of the related user
	 * @return User
	 */
	public function getUser() {
		return User::fetchRowByID($this->user_id);
	}
	
	/**
	 * Returns the InternalAward object of the related award
	 * @return InternalAward
	 */
	public function getAward() {
		return InternalAward::get($this->award_id);
	}
	
	/**
	 * Creates a new InternalAwardReceipt in the database. Input retrieves values from an array with elements: 'user_id', 'award_id', and 'year'. 
	 * @param array $input_arr
	 */
	static public function create(array $input_arr) {
		extract($input_arr);
		global $db;
		$query = "INSERT INTO `student_awards_internal` (`user_id`,`award_id`, `year`) VALUES (?,?,?)";
		if(!$db->Execute($query, array($user_id, $award_id, $year))) {
			add_error("Failed to add award recipient to database. Please check your values and try again.");
			application_log("error", "Unable to insert a student_awards_internal record. Database said: ".$db->ErrorMsg());
		} else {
			add_success("Successfully added Award Recipient.");
		}
	}
	
	/**
	 * 
	 * @param int $award_receipt_id
	 * @return AwardRecipient
	 */
	static public function get($award_receipt_id) {
		global $db;
		$query		= "SELECT a.id as award_receipt_id, user_id, award_id, c.title, c.award_terms, c.disabled, a.year 
				FROM `". DATABASE_NAME ."`.`student_awards_internal` a 
				left join `". DATABASE_NAME ."`.`student_awards_internal_types` c on c.id = a.award_id 
				WHERE a.id = ".$db->qstr($award_receipt_id);
		
		$result	= $db->GetRow($query);
			
		if ($result) {
			$award = InternalAward::fromArray($result);
			return InternalAwardReceipt::fromArray( $result);
		} else {
			add_error("Failed to retreive award receipt from database.");
			application_log("error", "Unable to retrieve a student_awards_internal record. Database said: ".$db->ErrorMsg());
		}
			 
	} 
	
	/**
	 * Returns an InternalAwardReceipt constructed from an array with elements: 'user_id', 'award_id', 'award_receipt_id', 'year'
	 * @param array $arr
	 * @return InternalAwardReceipt
	 */
	public static function fromArray(array $arr) {
		return new self($arr['user_id'], $arr['award_id'], $arr['award_receipt_id'], $arr['year']);
	}
	
	/**
	 * Deletes this award receipt from the database
	 */
	public function delete() {
		global $db;
	
		$query = "DELETE FROM `student_awards_internal` where `id`=".$db->qstr($this->award_receipt_id);
		if(!$db->Execute($query)) {
			add_error("Failed to remove award receipt from database.");
			application_log("error", "Unable to delete a student_awards_internal record. Database said: ".$db->ErrorMsg());
		} else {
			add_success("Successfully removed award receipt.");
		}
	}
	
	/**
	 * Compares award receipts by 'year' received, or by award 'title'
	 * @param InternalAwardReceipt $ar
	 * @param unknown_type $compare_by
	 * @return number
	 */
	public function compare($ar, $compare_by="year") {
		switch($compare_by) {
			case 'year':
				return $this->year == $ar->year ? 0 : ( $this->year > $ar->year ? 1 : -1 );
				break;
			case 'title':
				$award = $this->getAward();
				$other_award = $ar->getAward();
				return $award->compare($other_award);
				break;
		}
	}
	
	/**
	 * Updates the award receipt information with array inputs. See create() documents for element list  
	 * @param array $input_arr
	 */
	public function update (array $input_arr) {
		extract($input_arr);
		if (is_null($user_id)) {
			$user_id = $this->user_id;
		}
		global $db;
		$query = "update `student_awards_internal` set
				 `award_id`=?, `year`=?, `user_id`=?  
				 where `id`=?";
		if(!$db->Execute($query, array($award_id, $year, $user_id, $this->getID()))) {
			add_error("Failed to update award receipt.");
			application_log("error", "Unable to update a student_awards_internal record. Database said: ".$db->ErrorMsg());
		} else {
			add_success("Successfully updated award receipt.");
		}	
		
	}
}