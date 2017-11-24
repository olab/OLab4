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
 * A model to handle quizzes attached to community pages
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */
class Models_Quiz_Attached_CommunityPage extends Models_Quiz_Attached {
    
    protected $community_id, $community_url, $community_title, $page_title, $page_url;
    
    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }
    
    public static function fetchAllByQuizID($quiz_id) {
        global $db;
        
        $output = false;
        
        $query = "SELECT a.*, b.`community_id`, b.`community_url`, b.`community_title`, CONCAT('[', b.`community_title`, '] ', bp.`menu_title`) AS `page_title`, bp.`page_url`
                    FROM `attached_quizzes` AS a
                    JOIN `communities` AS b
                    ON a.`content_type` = 'community_page'
                    JOIN `community_pages` AS bp
                    ON a.`content_type` = 'community_page'
                    AND	bp.`cpage_id` = a.`content_id`
                    AND bp.`community_id` = b.`community_id`
                    WHERE a.`quiz_id` = ".$db->qstr($quiz_id)."
                    AND b.`community_active` = '1'
                    AND bp.`page_active` = '1'
                    ORDER BY b.`community_title` ASC";
        $results = $db->GetAll($query);
        if ($results) {
            $output = array();
            foreach ($results as $result) {
                $output[] = new self($result);
            }
        }
        
        return $output;
    }
    
    public static function fetchRowByID($aquiz_id = null) {
        global $db;
        
        $output = false;
        
        $query = "SELECT a.*, b.`community_id`, b.`community_url`, b.`community_title`, CONCAT('[', b.`community_title`, '] ', bp.`menu_title`) AS `page_title`, bp.`page_url`
                    FROM `attached_quizzes` AS a
                    JOIN `communities` AS b
                    ON a.`content_type` = 'community_page'
                    JOIN `community_pages` AS bp
                    ON a.`content_type` = 'community_page'
                    AND	bp.`cpage_id` = a.`content_id`
                    AND bp.`community_id` = b.`community_id`
                    WHERE a.`aquiz_id` = ".$db->qstr($aquiz_id)."
                    AND b.`community_active` = '1'
                    AND bp.`page_active` = '1'
                    ORDER BY b.`community_title` ASC";
        $results = $db->GetRow($query);
        if ($results) {
            $output = new self($results);
        }
        
        return $output;
    }
    
    public function getCommunityID() {
        return $this->community_id;
    }

    public function getCommunityURL() {
        return $this->community_url;
    }

    public function getCommunityTitle() {
        return $this->community_title;
    }

    public function getPageTitle() {
        return $this->page_title;
    }

    public function getPageURL() {
        return $this->page_url;
    }
    
}

?>
