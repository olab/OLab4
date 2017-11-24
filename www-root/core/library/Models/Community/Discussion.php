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
 * A model for handling Community discussions
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2014 Regents of The University of California. All Rights Reserved.
 */

class Models_Community_Discussion extends Models_Base {
    protected   $cdiscussion_id,
                $community_id,
                $cpage_id,
                $forum_title,
                $forum_description,
                $forum_category,
                $forum_order,
                $forum_active,
                $admin_notifications,
                $allow_public_read,
                $allow_public_post,
                $allow_public_reply,
                $allow_troll_read,
                $allow_troll_post,
                $allow_troll_reply,
                $allow_member_read,
                $allow_member_post,
                $allow_member_reply,
                $release_date,
                $release_until,
                $updated_date,
                $updated_by;

    protected static $table_name = "community_discussions";
    protected static $primary_key = "cdiscussion_id";
    protected static $default_sort_column = "cdiscussion_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getCDiscussionID() {
        return $this->cdiscussion_id;
    }

    public function setCDiscussionID($cdiscussion_id) {
        $this->cdiscussion_id = $cdiscussion_id;
    }

    public function getCommunityID() {
        return $this->community_id;
    }

    public function getCPageID() {
        return $this->cpage_id;
    }

    public function getForumTitle() {
        return $this->forum_title;
    }

    public function getFormDescription() {
        return $this->forum_description;
    }

    public function getForumCategory() {
        return $this->forum_category;
    }

    public function getForumActive() {
        return $this->forum_active;
    }

    public function getAdminNotifications() {
        return $this->admin_notifications;
    }

    public function getAllowPublicRead() {
        return $this->allow_public_read;
    }

    public function getAllowPublicPost() {
        return $this->allow_public_post;
    }

    public function getAllowPublicReply() {
        return $this->allow_public_reply;
    }

    public function getAllowTrollRead() {
        return $this->allow_troll_read;
    }

    public function getAllowTrollPost() {
        return $this->allow_troll_post;
    }

    public function getAllowTrollReply() {
        return $this->allow_troll_reply;
    }

    public function getAllowMemberRead() {
        return $this->allow_member_read;
    }

    public function getAllowMemberPost() {
        return $this->allow_member_post;
    }

    public function getAllowMemberReply() {
        return $this->allow_member_reply;
    }

    public function getReleaseDate() {
        return $this->release_date;
    }

    public function getReleaseUntil() {
        return $this->release_until;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdateBy() {
        return $this->updated_by;
    }


    public function insert() {
        global $db;

        if ($db->AutoExecute($this->table_name, $this->toArray(), "INSERT")) {
            return $this;
        } else {
            return false;
        }
    }

    public static function fetchAllActiveByPage_ID($cpage_id = 0) {
        $self = new self();

        $constraints = array(
            array(
                "key"       => "cpage_id",
                "value"     => $cpage_id,
                "method"    => "="
            ),
            array(
                "key" => "forum_active",
                "value" => '1',
                "method"    => "="
            )
        );

        $objs = $self->fetchAll($constraints, "=", "AND", $sort_col, $sort_order);
        $output = array();

        if (!empty($objs)) {
            foreach ($objs as $o) {
                $output[] = $o;
            }
        }

        return $output;
    }

    public static function fetchAllActiveByCommunityID($community_id = 0) {
        $self = new self();

        $constraints = array(
            array(
                "key"       => "community_id",
                "value"     => $community_id,
                "method"    => "="
            ),
            array(
                "key" => "forum_active",
                "value" => '1',
                "method"    => "="
            )
        );

        $objs = $self->fetchAll($constraints, "=", "AND", $sort_col, $sort_order);
        $output = array();

        if (!empty($objs)) {
            foreach ($objs as $o) {
                $output[] = $o;
            }
        }

        return $output;
    }

    /**
     * @param int $community_id
     * @param $table_name
     * @param $id_field
     * @param $type_field
     * @param $active_filed
     * @return array
     */
    public static function fetchAllByCommunityIDCourseGroupMember($community_id = 0, $table_name, $id_field, $type_field, $active_filed) {
        global $db;

        $sql = "SELECT item.*
                FROM `" .$table_name . "` AS `item`
                JOIN `community_acl` AS `ca`
                ON `item`.`$id_field` = `ca`.`resource_value`
                WHERE `item`.`community_id` = " . $community_id . "
                AND `ca`.`assertion` = 'CourseGroupMember'
                AND `ca`.`resource_type` = '" . $type_field . "'
                AND `item`.`$active_filed`";
        $results = $db->GetAll($sql);
        $output = array();

        if (isset($results)) {
            if (is_array($results) && !empty($results)) {
                foreach ($results as $result) {
                    $self = new self();
                    $output[] = $self->fromArray($result);
                }
            }
        }

        return $output;
    }

    /**
     * @param int $PAGE_ID
     * @param int $COMMUNITY_ID
     * @return array
     */
    public static function fetchAllCategoriesByPagIdCommunityId($PAGE_ID, $COMMUNITY_ID) {
        global $db;
        $query_forum_cat = "    SELECT DISTINCT `forum_category`
                            FROM `community_discussions`
                            WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)."
                            AND `forum_active` = '1'
                            AND `cpage_id` = ".$db->qstr($PAGE_ID)."
                            ORDER BY `forum_category` ASC";
        $results_forum_cat	= $db->GetAll($query_forum_cat);
        return $results_forum_cat;
    }

    public static function fetchRowByID($cdiscussion_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "cdiscussion_id", "value" => $cdiscussion_id, "method" => "=")
        ));
    } 
}