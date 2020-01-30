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
 * A model for handling Community HTML shares
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2014 Regents of The University of California. All Rights Reserved.
 */

class Models_Community_Share_Html extends Models_Base {
    protected   $cshtml_id,
                $cshare_id,
                $community_id,
                $proxy_id,
                $html_title,
                $html_description,
                $html_content,
                $html_active,
                $allow_member_read,
                $allow_troll_read,
                $access_method,
                $student_hidden,
                $release_date,
                $release_until,
                $updated_date,
                $updated_by,
                $notify;

    protected static $table_name = "community_share_html";
    protected static $default_sort_column = "cshtml_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getCSHtmlID() {
        return $this->cshtml_id;
    }

    public function setCSHtmlID($cshtml_id) {
        $this->cshtml_id = $cshtml_id;
    }

    public function getCShareID() {
        return $this->cshare_id;
    }

    public function getCommunityID() {
        return $this->community_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function getHtmlTitle() {
        return $this->html_title;
    }

    public function getHtmlDescription() {
        return $this->html_description;
    }

    public function getHtmlContent() {
        return $this->html_content;
    }

    public function getHtmlActive() {
        return $this->html_active;
    }

    public function getAllowMemberRead() {
        return $this->allow_member_read;
    }

    public function getAllowTrollRead() {
        return $this->allow_troll_read;
    }

    public function getAccessMethod() {
        return $this->access_method;
    }

    public function getStudentHidden() {
        return $this->student_hidden;
    }

    public function getReleaseDate() {
        return $this->release_date;
    }

    public function getReleaseUntil() {
        return $this->release_until;
    }

    public function getUpdateDate() {
        return $this->updated_date;
    }

    public function getUpdateBy() {
        return $this->updated_by;
    }

    public function getNotify() {
        return $this->notify;
    }

    public function insert() {
        global $db;

        if ($db->AutoExecute($this->table_name, $this->toArray(), "INSERT")) {
            return $this;
        } else {
            return false;
        }
    }

    public function update() {
        global $db;
        if ($db->AutoExecute($this->table_name, $this->toArray(), "UPDATE", "`cshtml_id` = " . $db->qstr($this->getCSHtmlID()))) {
            return true;
        } else {
            return false;
        }
    }

    public static function fetchAllActiveByCShareID($cshare_id = 0) {
        $self = new self();

        $constraints = array(
            array(
                "key"       => "cshare_id",
                "value"     => $cshare_id,
                "method"    => "="
            ),
            array(
                "key" => "html_active",
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

    public static function fetchRowByID($cshtml_id = 0) {
        $self = new self();
        return $self->fetchRow(array("cshtml_id" => $cshtml_id));
    }
}