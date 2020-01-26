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
 * A model for handling Communities
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2014 Regents of The University of California. All Rights Reserved.
 */

class Models_Community_Member extends Models_Base {
    protected   $cmember_id,
                $community_id,
                $proxy_id,
                $member_active,
                $member_joined,
                $member_acl,
                $tutoring;

    protected static $table_name          = "community_members";
    protected static $primary_key         = "cmember_id";
    protected static $default_sort_column = "cmember_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->cmember_id;
    }

    public function getCMemberID() {
        return $this->cmember_id;
    }

    public function setCMemberID($cmember_id) {
        $this->cmember_id = $cmember_id;
    }

    public function getCommunityID() {
        return $this->community_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function getMemberActive() {
        return $this->member_active;
    }

    public function setMemberActive($member_active) {
        $this->member_active = $member_active;
    }

    public function getMemberJoined() {
        return $this->member_joined;
    }

    public function getMemberACL() {
        return $this->member_acl;
    }

    public function setMemberACL($member_acl) {
        $this->member_acl = $member_acl;
    }

    public function getTutoring() {
        return $this->tutoring;
    }

    /* @return bool|Models_Community_Member */
    public static function fetchRowByProxyIDCommunityID($proxy_id, $community_id = 0) {
        $self = new self();
        return $self->fetchRow(
            array(
                "proxy_id" => $proxy_id,
                "community_id" => $community_id
            )
        );
    }

    /* @return ArrayObject|Models_Community_Member[] */
    public static function fetchAllByProxyID($proxy_id, $member_active = 0) {
        $self = new self();
        
        $constraints = array (
            array("key" => "proxy_id", "method" => "=", "value" => $proxy_id)
        );

        if ($member_active) {
            $constraints[] = array("key" => "member_active", "method" => "=", "value" => $member_active);
        }

        $objs = $self->fetchAll($constraints, "=", "AND");
        $output = array();

        if (!empty($objs)) {
            foreach ($objs as $o) {
                $output[] = $o;
            }
        }

        return $output;
    }

    /* @return ArrayObject|Models_Community_Member[] */
    public static function fetchAllByCommunityID($community_id) {
        $self = new self();

        $constraints = array(
            array(
                "key"       => "community_id",
                "value"     => $community_id,
                "method"    => "="
            )
        );

        $objs = $self->fetchAll($constraints, "=", "AND");
        $output = array();

        if (!empty($objs)) {
            foreach ($objs as $o) {
                $output[] = $o;
            }
        }

        return $output;
    }

    public static function insert_members($proxy_id, $community_id, $ACL = 0, $tutoring = 0) {
        global $db;
        $member = self::fetchRowByProxyIDCommunityID($proxy_id, $community_id);
        if ($member) {
            $member->setMemberACL($ACL);
            $member->setMemberActive(1);
            if (!$member->update()) {
                application_log("error", "Error when updating member (" . $proxy_id .") to community (" . $community_id . "), DB said: " . $db->ErrorMsg());
            }
        } else {
            $new_member = new self(array(
                "community_id"  => $community_id,
                "proxy_id"      => $proxy_id,
                "member_active" => 1,
                "member_joined" => time(),
                "member_acl"    => $ACL,
                "tutoring"      => $tutoring
            ));
            if (!$new_member->insert()) {
                application_log("error", "Error when inserting new member (" . $proxy_id .") to community (" . $community_id . "), DB said: " . $db->ErrorMsg());
            }
        }
    }

    public function updateMultipleMembersACL($proxy_id_in, $community_id, $ACL = 0) {
        global $db;

        $query = "UPDATE `community_members` SET `member_acl` = ? WHERE `proxy_id` IN (".$proxy_id_in.") AND `community_id` = ? ";

        $result = $db->Execute($query, array($ACL,$community_id));

        if ($result) {
            return $result;
        }

        return false;
    }
}