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

class Models_Assessments_Form_Element extends Models_Base {
    protected $afelement_id, $one45_form_id, $one45_element_id, $form_id, $element_type, $element_id, $element_text, $rubric_id, $order, $allow_comments, $enable_flagging, $deleted_date, $updated_date, $updated_by;

    protected static $table_name = "cbl_assessment_form_elements";
    protected static $primary_key = "afelement_id";
    protected static $default_sort_column = "order";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->afelement_id;
    }

    public function getAfelementID() {
        return $this->afelement_id;
    }

    public function getOne45FormID() {
        return $this->one45_form_id;
    }

    public function getOne45ElementID() {
        return $this->one45_element_id;
    }

    public function getFormID() {
        return $this->form_id;
    }

    public function getElementType() {
        return $this->element_type;
    }

    public function getElementID() {
        return $this->element_id;
    }

    public function getElementText() {
        return $this->element_text;
    }

    public function getRubricID() {
        return $this->rubric_id;
    }

    public function getOrder() {
        return $this->order;
    }

    public function getAllowComments() {
        return $this->allow_comments;
    }

    public function getEnableFlagging() {
        return $this->enable_flagging;
    }
    
    public static function fetchNextOrder($form_id) {
        global $db;
        $query = "SELECT MAX(`order`) + 1 AS `next_order` FROM `cbl_assessment_form_elements` WHERE `form_id` = ?";
        $result = $db->GetOne($query, array($form_id));
        return $result ? $result : "0";
    }

    public static function fetchRowByID($afelement_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "afelement_id", "value" => $afelement_id, "method" => "=")
        ));
    }

    public static function fetchRowByElementIDFormIDElementType($element_id, $form_id, $element_type = "item", $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "element_id", "value" => $element_id, "method" => "="),
            array("key" => "form_id", "value" => $form_id, "method" => "="),
            array("key" => "element_type", "value" => $element_type, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchRowByElementIDFormIDElementTypeRubricID($element_id, $form_id, $element_type = "item", $rubric_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "element_id", "value" => $element_id, "method" => "="),
            array("key" => "form_id", "value" => $form_id, "method" => "="),
            array("key" => "element_type", "value" => $element_type, "method" => "="),
            array("key" => "rubric_id", "value" => $rubric_id, "method" => "="),
        ));
    }

    public static function fetchRowByElementIDElementType($element_id, $element_type = "item") {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "element_id", "value" => $element_id, "method" => "="),
            array("key" => "element_type", "value" => $element_type, "method" => "=")
        ));
    }
    
    public static function fetchAllByFormIDRubricID($form_id, $rubric_id) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "form_id", "value" => $form_id, "method" => "="),
            array("key" => "rubric_id", "value" => $rubric_id, "method" => "="),
            array("key" => "element_type", "value" => "item", "method" => "="),
            array("key" => "deleted_date", "value" => NULL, "method" => "IS")
        ));
    }

    public static function fetchAllByFormIDRubricIDNull($form_id) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "form_id", "value" => $form_id, "method" => "="),
            array("key" => "rubric_id", "value" => NULL, "method" => "IS"),
            array("key" => "element_type", "value" => "item", "method" => "="),
            array("key" => "deleted_date", "value" => NULL, "method" => "IS")
        ));
    }

    public static function fetchAllByRubricID($rubric_id) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "rubric_id", "value" => $rubric_id, "method" => "="),
            array("key" => "deleted_date", "value" => NULL, "method" => "IS")
        ));
    }

    /**
     * Fetch all rows that are for a given item for a given rubric.
     *
     * @param $item_id
     * @param $rubric_id
     * @return array
     */
    public static function fetchAllByItemIDRubricID($item_id, $rubric_id) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "element_id", "value" => $item_id, "method" => "="),
            array("key" => "element_type", "value" => "item", "method" => "="),
            array("key" => "rubric_id", "value" => $rubric_id, "method" => "="),
            array("key" => "deleted_date", "value" => NULL, "method" => "IS")
        ));
    }

    public static function fetchAllByFormID($form_id) {
        $self = new self();
        $constraints = array(
            array("key" => "form_id", "value" => $form_id, "method" => "=")
        );
        $constraints[] = array("key" => "deleted_date", "value" => NULL, "method" => "IS");
        return $self->fetchAll($constraints);
    }

    public static function fetchAllByFormIDOrdered($form_id) {
        global $db;
        $output = array();
        $query = "SELECT a.* FROM `cbl_assessment_form_elements` AS a
                  WHERE a.`form_id` = ?
                  AND a.`deleted_date` IS NULL
                  ORDER BY `order`,`afelement_id`";
        $results = $db->GetAll($query, array($form_id));
        if ($results) {
            foreach ($results as $result) {
                $output[] = new Models_Assessments_Form_Element($result);
            }
        }
        return $output;
    }

    public static function fetchAllByRubricIDLimited($rubric_id, $limit = null) {
        global $db;
        $limit_clause = "";
        if ($limit) {
            $limit_clause = "LIMIT ?";
        }
        $output = array();
        $query = "SELECT a.* FROM `".static::$database_name."`.`".static::$table_name."` AS a
                  WHERE a.`rubric_id` = ?
                  AND a.`deleted_date` IS NULL
                  ORDER BY `order`
                  $limit_clause";
        $options = array($rubric_id);
        if ($limit) {
            $options[] = $limit;
        }
        $results = $db->GetAll($query, $options);
        if ($results) {
            foreach ($results as $result) {
                $output[] = new Models_Assessments_Form_Element($result);
            }
        }
        return $output;
    }

    public static function fetchAllRecords($form_id = NULL) {
        $self = new self();
        
        $params = array(
            array("key" => "afelement_id", "value" => 0, "method" => ">="),
            array("key" => "deleted_date", "value" => NULL, "method" => "IS"),
        );

        if (!is_null($form_id)) {
            $params[] = array("key" => "form_id", "value" => $form_id, "method" => "=");
        }
        
        return $self->fetchAll($params, "=", "AND", "order", "ASC");
    }

    public static function getRubricElementsWithNoElementID($form_id, $element_type, $rubric_id) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "form_id", "value" => $form_id, "method" => "="),
            array("key" => "rubric_id", "value" => $rubric_id, "method" => "="),
            array("key" => "element_type", "value" => $element_type, "method" => "="),
            array("key" => "element_id", "value" => NULL, "method" => "IS")
        ));
    }

    public static function fetchAllByElementIDElementType($element_id, $element_type = "item") {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "element_id", "value" => $element_id, "method" => "="),
            array("key" => "element_type", "value" => $element_type, "method" => "=")
        ));
    }


    /**
     * Fetches all form elements, including any rubric items or form elements,
     * as well as item weights if an assessment id is provided
     * Also fetches the curriculum / objective tags, and format the display tag
     * depending on whether the objective_code is null or not
     *
     * @param  int          $assessment_id
     * @return array|null
     */
    public function fetchAllFormElementsRubricsItemsItemTypes($assessment_id = null, $proxy_id = null) {
        global $db;

        $select_extra = "";
        if ($assessment_id) {
            $select_extra .= ", f.assessment_id as assessment_id";
        }

        if ($proxy_id) {
            $select_extra .= ", f.gafelement_id as gafelement_id";
        }

        $query = "SELECT *, a.rubric_id as rubric_id, a.afelement_id as afelement_id".$select_extra." FROM `".DATABASE_NAME."`.`".static::$table_name."` a
                    LEFT JOIN `".DATABASE_NAME."`.cbl_assessments_lu_rubrics b
                    ON a.rubric_id = b.rubric_id
                    LEFT JOIN `".DATABASE_NAME."`.cbl_assessment_rubric_items c
                    ON a.element_id = c.item_id
                    LEFT JOIN `".DATABASE_NAME."`.cbl_assessments_lu_items d
                    ON a.element_id = d.item_id
                    LEFT JOIN `".DATABASE_NAME."`.`cbl_assessments_lu_itemtypes` e
                    ON d.itemtype_id = e.itemtype_id";

        if ($assessment_id) {
            $query .= " LEFT JOIN `".DATABASE_NAME."`.`gradebook_assessment_form_elements` f
                        ON a.afelement_id = f.afelement_id
                        AND f.assessment_id = ".$db->qstr($assessment_id);
        }

        if ($proxy_id) {
            $query .= " LEFT JOIN `".DATABASE_NAME."`.`assessment_grade_form_comments` g
                        ON f.gafelement_id = g.gafelement_id
                        AND g.proxy_id = ".$db->qstr($proxy_id);
        }

        $query .= " WHERE a.form_id = ?";

        $query .= " AND a.deleted_date IS NULL
                    AND c.deleted_date IS NULL
                    ORDER BY a.order ASC";


        // Get different results based on existence of assessment id
        $results = $db->getAll($query, $this->form_id);

        if ($results) {

            foreach ($results as $k => $r) {
                $objective_query = "SELECT b.objective_id, b.objective_code, b.objective_name, b.objective_description
                            FROM `".DATABASE_NAME."`.`cbl_assessment_item_objectives` a
                            JOIN `".DATABASE_NAME."`.`global_lu_objectives` b
                            ON a.objective_id = b.objective_id
                            WHERE a.item_id = ?";

                $objective_results = $db->getAll($objective_query, $r["item_id"]);

                if ($objective_results) {
                    $curriculum_tags = array();

                    foreach ($objective_results as $objective) {
                        $label = (strlen($objective['objective_code']) > 0 ? $objective['objective_code'] : $objective['objective_name']);
                        $curriculum_tags[] = array("id" => $objective["objective_id"], "code" => $objective["objective_code"], "name" => $objective["objective_name"],
                            "desc" => $objective["objective_description"], "label" => $label);
                    }
                    $results[$k]["curriculum-tags"] = $curriculum_tags;
                }
            }

            return $results;
        } else {
            return false;
        }
    }

    /**
     * Fetch the IDs of the forms that have this item ID attached to it.
     *
     * @param $item_id
     * @return array
     */
    public static function fetchFormIDsByItemID($item_id) {
        global $db;
        $form_ids = array();
        $query = "SELECT      DISTINCT(fe.`form_id`)
                  FROM        `cbl_assessment_form_elements` AS fe
                  LEFT JOIN   `cbl_assessments_lu_items`     AS i ON i.`item_id` = fe.`element_id`
                  LEFT JOIN   `cbl_assessments_lu_forms`     AS fr ON fr.`form_id` = fe.`form_id`
                  WHERE       fe.`element_type` = 'item'
                  AND         fe.`element_id` = ?
                  AND         i.`deleted_date` IS NULL
                  AND         fe.`deleted_date` IS NULL
                  AND         fr.`deleted_date` IS NULL";
        $forms = $db->GetAll($query, array($item_id));
        if (is_array($forms)) {
            foreach ($forms as $form_record) {
                $form_ids[] = (int)$form_record["form_id"];
            }
        }
        return $form_ids;
    }

    /**
     * Fetch the IDs of the forms that have this rubric ID attached to it.
     *
     * @param $rubric_id
     * @return array
     */
    public static function fetchFormIDsByRubricD($rubric_id) {
        global $db;
        $form_ids = array();
        $query = "SELECT      DISTINCT(fe.`form_id`)
                  FROM        `cbl_assessment_form_elements`  AS fe
                  LEFT JOIN   `cbl_assessments_lu_rubrics`    AS r ON r.`rubric_id` = fe.`rubric_id`
                  LEFT JOIN   `cbl_assessments_lu_forms`      AS fr ON fr.`form_id` = fe.`form_id`
                  WHERE       fe.`rubric_id` = ?
                  AND         r.`deleted_date` IS NULL
                  AND         fe.`deleted_date` IS NULL
                  AND         fr.`deleted_date` IS NULL";
        $forms = $db->GetAll($query, array($rubric_id));
        if (is_array($forms)) {
            foreach ($forms as $form_record) {
                $form_ids[] = (int)$form_record["form_id"];
            }
        }
        return $form_ids;
    }

    /**
     * Find all the forms that use the given items.
     * @param array $id_list
     * @return array|false
     */
    public static function fetchFormIDsByItemIDList($id_list = array()) {
        global $db;

        $sanitized = array_map(
            function($v) {
                return clean_input($v, array("trim", "int"));
            },
            $id_list
        );
        $imploded_id_list = implode(",", $sanitized);
        if (!$imploded_id_list) {
            return false;
        }

        $form_ids = array();

        // Ignores deleted dates.
        $query = "SELECT      DISTINCT(fe.`form_id`)
                  FROM        `cbl_assessment_form_elements` AS fe
                  LEFT JOIN   `cbl_assessments_lu_items`     AS i ON i.`item_id` = fe.`element_id`
                  LEFT JOIN   `cbl_assessments_lu_forms`     AS fr ON fr.`form_id` = fe.`form_id`
                  WHERE       fe.`element_type` = 'item'
                  AND         fe.`element_id` IN({$imploded_id_list})";

        $forms = $db->GetAll($query);
        if (is_array($forms)) {
            foreach ($forms as $form_record) {
                $form_ids[$form_record["form_id"]] = (int)$form_record["form_id"];
            }
        }
        return $form_ids;
    }
}