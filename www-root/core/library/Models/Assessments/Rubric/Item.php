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
 * @author Organisation: Queen's University
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Models_Assessments_Rubric_Item extends Models_Base {
    protected $aritem_id, $rubric_id, $item_id, $order, $enable_flagging, $deleted_date;

    protected static $table_name = "cbl_assessment_rubric_items";
    protected static $primary_key = "aritem_id";
    protected static $default_sort_column = "order";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->aritem_id;
    }

    public function getAritemID() {
        return $this->aritem_id;
    }

    public function getRubricID() {
        return $this->rubric_id;
    }

    public function getItemID() {
        return $this->item_id;
    }

    public function getOrder() {
        return $this->order;
    }

    public function getEnableFlagging() {
        return $this->enable_flagging;
    }

    public function getDeletedDate()
    {
        return $this->deleted_date;
    }

    public static function fetchRowByID($aritem_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "aritem_id", "value" => $aritem_id, "method" => "=")
        ));
    }

    public static function fetchAllRecords() {
        $self = new self();
        return $self->fetchAll(array(array("key" => "aritem_id", "value" => 0, "method" => ">=")));
    }

    public static function fetchAllRecordsByRubricID($rubric_id, $mode = "AND", $sort_column = "use_default", $sort_order = "ASC", $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "rubric_id", "value" => $rubric_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchAllRecordsByRubricIDIncludeDeleted($rubric_id) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "rubric_id", "value" => $rubric_id, "method" => "=")
        ));
    }

    public static function fetchAllRecordsByRubricIDOrdered($rubric_id) {
        $self = new self();
        return $self->fetchAll(array(
                array("key" => "rubric_id", "value" => $rubric_id, "method" => "="),
                array("key" => "deleted_date", "value" => NULL, "method" => "IS")
            ), "=", "AND", "order", "ASC"
        );
    }

    public static function fetchAllByRubricIDOrdered($rubric_id, $include_deleted = true) {
        global $db;
        $AND_items_deleted = $include_deleted ? "" : "AND a.`deleted_date` IS NULL";
        $sql = "SELECT  a.`item_id`,
                        a.`one45_element_id`,
                        a.`organisation_id`,
                        a.`itemtype_id`, a.`item_code`, a.`item_text`,
                        a.`item_description`, a.`comment_type`, a.`created_date`, a.`created_by`, a.`updated_date`, a.`updated_by`, a.`deleted_date`,
                        b.`text` AS `response_text`, b.`order` AS `response_order`,
                        r.`aritem_id`, r.`rubric_id`

                FROM `cbl_assessments_lu_items` AS a
                JOIN `cbl_assessments_lu_item_responses` AS b ON a.`item_id` = b.`item_id`

                JOIN `cbl_assessment_rubric_items` AS r ON r.`item_id` = a.`item_id`

                WHERE r.`rubric_id` = ?
                AND b.`deleted_date` IS NULL
                AND r.`deleted_date` IS NULL
                $AND_items_deleted
                ORDER BY r.`order` ASC";
        $results = $db->GetAll($sql, array($rubric_id));
        return $results;
    }


    public static function fetchRowByItemIDRubricID($item_id = null, $rubric_id = null, $deleted_date = null) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "item_id", "value" => $item_id, "method" => "="),
            array("key" => "rubric_id", "value" => $rubric_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchRowByItemID($item_id = null, $deleted_date = null) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "item_id", "value" => $item_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchAllByItemID($item_id = null, $deleted_date = null) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "item_id", "value" => $item_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchAllByMultipleItemID($item_id_array = array(), $deleted_date = null) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "item_id", "value" => $item_id_array, "method" => "IN"),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchNextOrder($form_id) {
        global $db;
        $query = "SELECT MAX(`order`) + 1 AS `next_order` FROM `cbl_assessment_rubric_items` WHERE `rubric_id` = ?";
        $result = $db->GetOne($query, array($form_id));
        return $result ? $result : "0";
    }

    /**
     * Fetch all rubric IDs that use the given item ID.
     *
     * @param $item_id
     * @return array
     */
    public static function fetchRubricIDsByItemID($item_id) {
        global $db;
        $rubric_ids = array();
        $sql = "SELECT    DISTINCT(ri.`rubric_id`)
                FROM      `cbl_assessment_rubric_items` AS ri
                LEFT JOIN `cbl_assessments_lu_items` AS i ON i.`item_id` = ri.`item_id`
                WHERE     ri.`deleted_date` IS NULL
                AND       i.`deleted_date` IS NULL
                AND       ri.`item_id` = ?";
        $rubrics = $db->GetAll($sql, array($item_id));
        if (is_array($rubrics)) {
            foreach ($rubrics as $rubric) {
                $rubric_ids[] = $rubric["rubric_id"];
            }
        }
        return $rubric_ids;
    }

    /**
     * Fetch all rubric IDs that use the items in the given list.
     *
     * @param array $item_ids
     * @return array
     */
    public static function fetchRubricIDsByItemIDList($item_ids = array()) {
        global $db;

        $sanitized = array_map(
            function($v) {
                return clean_input($v, array("trim", "int"));
            },
            $item_ids
        );
        $imploded_id_list = implode(",", $sanitized);
        if (!$imploded_id_list) {
            return false;
        }

        $rubric_ids = array();
        // Does not honour deleted dates
        $sql = "SELECT    DISTINCT(ri.`rubric_id`)
                FROM      `cbl_assessment_rubric_items` AS ri
                LEFT JOIN `cbl_assessments_lu_items` AS i ON i.`item_id` = ri.`item_id`
                WHERE     ri.`item_id` IN({$imploded_id_list})";
        $rubrics = $db->GetAll($sql);
        if (is_array($rubrics)) {
            foreach ($rubrics as $rubric) {
                $rubric_ids[$rubric["rubric_id"]] = (int)$rubric["rubric_id"];
            }
        }
        return $rubric_ids;
    }
}