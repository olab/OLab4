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

class Models_Assessments_Item_Response extends Models_Base {
    protected $iresponse_id, $one45_choice_id, $one45_response_num, $item_id, $text, $order, $allow_html, $flag_response, $ardescriptor_id, $deleted_date;

    protected static $table_name = "cbl_assessments_lu_item_responses";
    protected static $primary_key = "iresponse_id";
    protected static $default_sort_column = "order";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->iresponse_id;
    }

    public function getIresponseID() {
        return $this->iresponse_id;
    }

    public function getOne45ChoiceID() {
        return $this->one45_choice_id;
    }

    public function getOne45ResponseNum() {
        return $this->one45_response_num;
    }

    public function getItemID() {
        return $this->item_id;
    }

    public function getText() {
        return $this->text;
    }

    public function getOrder() {
        return $this->order;
    }

    public function getAllowHtml() {
        return $this->allow_html;
    }

    public function getFlagResponse() {
        return $this->flag_response;
    }
    
    public function getARDescriptorID() {
        return $this->ardescriptor_id;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function setDeletedDate($date) {
        $this->deleted_date = $date;
    }

    public static function fetchRowByID($iresponse_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "iresponse_id", "value" => $iresponse_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchAllByItemIDsAndARDescriptorID($item_ids, $ardescriptor_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "item_id", "value" => $item_ids, "method" => "IN"),
            array("key" => "ardescriptor_id", "value" => $ardescriptor_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /**
     * Get all items and their response descriptors given an array of item IDs
     * @param  array        $item_ids
     * @return array|null   
     */
    public static function fetchAllByItemIDsWithResponseDescriptors($item_ids, $assessment_id = null, $proxy_id = null) {
        global $db;

        $item_id_list = implode(",", array_map(array($db, 'qstr'), $item_ids));

        $extra_selects = $assessment_id ? ", c.gairesponse_id as gairesponse_id, c.score as item_response_score" : "";
        $extra_selects .= $proxy_id ? ", d.score as proxy_score" : "";

        $query = "SELECT *, a.iresponse_id as `iresponse_id` ".$extra_selects." from `".DATABASE_NAME."`.`".static::$table_name."` a
                    LEFT JOIN `".DATABASE_NAME."`.cbl_assessments_lu_response_descriptors b
                    ON a.ardescriptor_id = b.ardescriptor_id";

        $query .= $assessment_id ? " LEFT JOIN `".DATABASE_NAME."`.`gradebook_assessment_item_responses` c
                                        ON a.iresponse_id = c.iresponse_id
                                        AND c.assessment_id = ".$db->qstr($assessment_id) : "";

        $query .= $proxy_id ?       " LEFT JOIN `".DATABASE_NAME."`.`assessment_grade_form_elements` d
                                        ON c.gairesponse_id = d.gairesponse_id
                                        AND d.proxy_id = ".$db->qstr($proxy_id) : "";

        $query .= " WHERE a.item_id IN (" . $item_id_list . ")
                    AND a.deleted_date IS NULL";

        $results = $db->getAll($query);

        if ($results) {
            return $results;
        }
        else {
            return false;
        }
    }

    public static function fetchAllRecords($deleted_date = NULL) {
        $self = new self();

        return $self->fetchAll(array(array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))));
    }

    public static function fetchAllRecordsByItemID($item_id = null, $deleted_date = NULL) {
        $self = new self();
        $params = array(
            array("key" => "item_id", "value" => $item_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        );

        return $self->fetchAll($params, "=", "AND", "order", "ASC");
    }

    public static function fetchAllRecordsByARDescriptorID($ardescriptor_id = null, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "ardescriptor_id", "value" => $ardescriptor_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchAllRecordsByItemIDIgnoreDeleted($item_id) {
        $self = new self();
        $params = array(
            array("key" => "item_id", "value" => $item_id, "method" => "="),
        );
        return $self->fetchAll($params, "=", "AND", "iresponse_id", "ASC");
    }

    public static function deleteByID($id) {
        if ($response = self::fetchRowByID($id)) {
            $response->setDeletedDate(time());
            return $response->update();
        }
        return false;
    }

}