<?php

require_once("Classes/utility/Collection.class.php");
require_once("ResearchCitation.class.php");
require_once("Classes/utility/Sequenceable.interface.php");

class ResearchCitations extends Collection implements AttentionRequirable, Sequenceable {
	public static function get(User $user) {
		global $db;
		$user_id = $user->getID();
		$query		= "SELECT * FROM `student_research` WHERE `user_id` = ".$db->qstr($user_id)." ORDER BY `priority` ASC";
		$results = $db->getAll($query);
		$citations = array();
		if ($results) {
			foreach ($results as $result) {
				$citation = ResearchCitation::fromArray($result);
				$citations[] = $citation;
			}
		}
		return new self($citations);
	}
	
	/**
	 * User is included to prevent tampering with another user's sequence.
	 * @param User $user
	 * @param array $ids
	 */
	public function setSequence($user_id, array $ids) {
		global $db;
		$stmt = $db->Prepare('update `student_research` set `priority`=? where `user_id`=? and `id`=?');
		foreach($ids as $priority=>$id) {
			if (!$db->Execute($stmt,array($priority, $user_id, $id))) {
				add_error("Failed to re-sequence Research Citations.");
				application_log("error", "Unable to modify a student_research record. Database said: ".$db->ErrorMsg());
				break;
			}
		}
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