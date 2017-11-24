<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Gives community administrators the ability to reorder an existing pages.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Harry Brundage <hbrundage@qmed.ca>
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("COMMUNITY_INCLUDED")) || (!defined("IN_PAGES")) || !COMMUNITY_INCLUDED || !IN_PAGES) {
	exit;
} elseif (!$COMMUNITY_LOAD) {
	exit;
}

if (($LOGGED_IN) && (!$COMMUNITY_MEMBER)) {
	$NOTICE++;
	$NOTICESTR[] = "You are not currently a member of this community, <a href=\"".ENTRADA_URL."/communities?section=join&community=".$COMMUNITY_ID."&step=2\" style=\"font-weight: bold\">want to join?</a>";

	echo display_notice();
} else {
	/**
	 * Recursive function to clean all dimensions of the array and reorient it so that the parent for a particular item can be easily looked up.
	 * array is the array being cleaned, count is the count of items in all the arrays for verification, valid_ids holds the id #s that are permitted to
	 * appear in the data, and when that id is happened upon the value at that location in the array is replaced with that id's parent in the new ordering.
	 *
	 * @param array $array
	 * @param int $count
	 * @param boolean $valid_ids
	 * @param array $order
	 * @param int $parent_id
	 * @param boolean $level_url
	 * @return <type>
	 */
	function deep_clean_and_orient(&$array, &$count, &$valid_ids, &$order, $parent_id = 0, $level_url = array()) {
		foreach($array as $key => &$item) {
			// Key 0 has an int item defining the parent for the next ones in the array.
			if ($key == 0) {
				if($item == -1) {
					if(isset($root_found) && $root_found == true) {
						return false;
					} else {
						$root_found = true;
					}

					// Skip the root key as it has no meaning, parent_id will be 0.
					continue;
				}
				
				if(!is_numeric($item) || !($item = clean_input($item, array('trim', 'int')))) {
					return false;
				}

				if(isset($valid_ids[$item]) && ($valid_ids[$item]['found'] === false)) { //valid id must be TRUE, not 1, for this id to be accepted
					$valid_ids[$item]['found'] = true; // set to parent so it is known no valid ids occured twice
					$level_url[] = $valid_ids[$item]['old_url_suffix'];
					$order[$count] = array("id"=>$item, "parent"=>$parent_id, "url"=>$level_url);
					$count++;
					$parent_id = $item;
				} else {
					return false;
				}
				
			} else {
				if( deep_clean_and_orient($item, $count, $valid_ids, $order, $parent_id, $level_url) === false) { //recursive call to clean all levels of the array.
					return false;
				}
			}
		}
		return true; //if anything has gone wrong the function will have returned by now.
	}
	
	$pageorder = str_replace('\\', '',  $_POST["pageorder"]);
	$pagelists = Zend_Json::decode($pageorder);

	if($pagelists !== null && $pagelists !== false) {
		$page_ids = array();


		$page_ids_query	= "SELECT `cpage_id`, `page_url` FROM `community_pages` WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `page_url` != '' AND `page_active` = '1'";
		$page_records	= $db->GetAll($page_ids_query);

		foreach($page_records as $record) {
            $pieces = explode("/", $record['page_url']);
			$url = end($pieces);
			$page_ids[$record['cpage_id']] = array('found' => false, 'old_url_suffix'=>$url, 'old_id'=>$record['cpage_id']); //set this array up so that if a page_id is in this community, $page_ids[id] = true
		}

		$count = 0; //account for home page that isn't sortable
		$order = array();
		if(deep_clean_and_orient($pagelists, $count, $page_ids, $order)) {
			$unaccounted_for_pages = false;
			foreach($page_ids as $key => $found) {
				if(!$found['found']) {
					$unaccounted_for_pages = true;
					break;
				}
			}
			
			if(isset($page_ids) && (count($page_ids) == $count) && !$unaccounted_for_pages) {
				//submitted data has been cleaned and all pages are accounted for, commit re-ordering
				$page_order = 1;
				foreach($order as $data) {
					$new_url = implode("/", $data['url']);
					$query = "UPDATE `community_pages` SET `parent_id`=".$db->qstr((int) $data['parent']).", `page_order`=".$db->qstr($page_order).", `page_url`=".$db->qstr($new_url).
							" WHERE `cpage_id`=".$db->qstr((int) $data['id']);
					$db->Execute($query);
					$page_order++;
				}
			} else {
				$ERROR++;
				$ERRORSTR[] = "There was a problem processing your reordering. Please try again.";
				application_log("error", "User tried to submit incomplete page reordering data for [".$COMMUNITY_ID."]");

				echo display_error();
			}
		} else {
			$ERROR++;
			$ERRORSTR[] = "There was a problem processing your reordering. Please try again.";

			echo display_error();
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "There was a problem processing your reordering. Please try again.";
		application_log("error", "User tried to submit bad page reordering data for [".$COMMUNITY_ID."]");

		echo display_error();
	}

	if (!$ERROR) {
		Entrada_Utilities_Flashmessenger::addMessage($translate->_("You have successfully updated the page ordering for the community"), "success", $MODULE);
		application_log("success", "Page ordering for  [".$COMMUNITY_ID."] updated in the system.");

        $url = ENTRADA_URL . "/community" . $community_details["community_url"] . ":pages";
        header("Location: " . $url);
        exit;
	}
}