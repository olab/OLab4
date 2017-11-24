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
 * A model for handling Communities pages
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2014 Regents of The University of California. All Rights Reserved.
 */

class Models_Community_Page extends Models_Base {
    protected   $cpage_id,
                $community_id,
                $parent_id,
                $page_order,
                $page_type,
                $menu_title,
                $page_title,
                $page_url,
                $page_content,
                $page_active,
                $page_visible,
                $allow_member_view,
                $allow_troll_view,
                $allow_public_view,
                $updated_date,
                $updated_by;

    protected static $table_name = "community_pages";
    protected static $default_sort_column = "cpage_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getCPageID() {
        return $this->cpage_id;
    }

    public function setCPageID($cpage_id) {
        $this->cpage_id = $cpage_id;
    }

    public function getCommunityID() {
        return $this->community_id;
    }

    public function getParentID() {
        return $this->parent_id;
    }

    public function setParentID($parent_id) {
        $this->parent_id = $parent_id;
    }

    public function getPageOrder() {
        return $this->page_order;
    }

    public function getPageType() {
        return $this->page_type;
    }

    public function getMenuTitle() {
        return $this->menu_title;
    }

    public function getPageTitle() {
        return $this->page_title;
    }

    public function getPageURL() {
        return $this->page_url;
    }

    public function getPageContent() {
        return $this->page_content;
    }

    public function getPageActive() {
        return $this->page_active;
    }

    public function getPageVisible() {
        return $this->page_visible;
    }

    public function getAllowMemberView() {
        return $this->allow_member_view;
    }

    public function getAllowTrollView() {
        return $this->allow_troll_view;
    }

    public function getAllowPublicView() {
        return $this->allow_public_view;
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

    public function update() {
        global $db;
        if ($db->AutoExecute($this->table_name, $this->toArray(), "UPDATE", "`cpage_id` = " . $db->qstr($this->getCPageID()))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Deletes model in the database.
     *
     * @return bool - true on success, false on failure.
     *
     */
    public function delete() {
        global $db;

        if (isset($this->cpage_id)) {
            $query = "DELETE FROM `" . $this->table_name . "` WHERE `cpage_id` = ?";

            if ($db->Execute($query, array($this->cpage_id))) {
                return true;
            } else {
                application_log("error", "Failed to update Bookmark id[" . $this->cpage_id . "].  DB Said: " . $db->ErrorMsg());
                return false;
            }
        } else {
            return false;
        }
    }

    public static function fetchRowByPageID($cpage_id = 0) {
        $self = new self();
        return $self->fetchRow(array("cpage_id" => $cpage_id));
    }

    public static function fetchRowByCommunityIDandMenuTitle($community_id = 0, $menu_title = "") {
        $self = new self();
        $constraints = array(
            array(
                "key"       => "community_id" ,
                "value"     => $community_id,
                "method"    => "="
            ),
            array(
                "mode"      => "AND",
                "key"       => "menu_title" ,
                "value"     => $menu_title,
                "method"    => "="
            )
        );
        return $self->fetchRow($constraints);
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
                "key" => "page_active",
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

    public static function fetchAllByCommunityID($community_id = 0) {
        $self = new self();

        $constraints = array(
            array(
                "key"       => "community_id",
                "value"     => $community_id,
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
}
?>