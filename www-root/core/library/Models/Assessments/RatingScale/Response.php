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
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Models_Assessments_RatingScale_Response extends Models_Base {
    protected $rating_scale_response_id, $rating_scale_id, $text, $ardescriptor_id, $order, $flag_response, $weight, $deleted_date;

    protected static $table_name = "cbl_assessment_rating_scale_responses";
    protected static $default_sort_column = "rating_scale_id";
    protected static $primary_key = "rating_scale_response_id";

    public function __construct($arr = null) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->rating_scale_response_id;
    }

    public function getRatingScaleResponseID() {
        return $this->rating_scale_response_id;
    }

    public function getRatingScaleID() {
        return $this->rating_scale_id;
    }

    public function getText() {
        return $this->text;
    }

    public function getArDescriptorID() {
        return $this->ardescriptor_id;
    }

    public function getOrder() {
        return $this->order;
    }

    public function getFlagResponse() {
        return $this->flag_response;
    }

    public function getWeight() {
        return $this->weight;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public static function fetchRowByID($rating_scale_response_id, $deleted_date = null) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "rating_scale_response_id", "value" => $rating_scale_response_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : null), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchRowByRatingScaleARDescriptorID($rating_scale_id, $ardescriptor_id, $deleted_date = null) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "rating_scale_id", "value" => $rating_scale_id, "method" => "="),
            array("key" => "ardescriptor_id", "value" => $ardescriptor_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : null), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    public static function fetchRowsByRatingScaleID($rating_scale_id, $deleted_date = null, $order = "order", $direction = "ASC") {
        $self = new self();

        $result = $self->fetchAll(
            array(
                array("key" => "rating_scale_id", "value" => $rating_scale_id, "method" => "="),
                array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : null), "method" => ($deleted_date ? "<=" : "IS"))
            ),
            '=', 'AND', $order, $direction
        );

        return $result;
    }

    public static function fetchRowsByRatingScaleIDExcludingZeroWeight($rating_scale_id, $deleted_date = null, $order = "order", $direction = "ASC") {
        global $db;

        $params = array($rating_scale_id);

        if ($deleted_date === null) {
            $deleted_query = " AND deleted_date IS NULL";
        } else {
            $deleted_query = " AND deleted_date = ?";
            $params[] = $deleted_date;
        }

        $query = "SELECT *
                  FROM `cbl_assessment_rating_scale_responses`
                  WHERE `rating_scale_id` = ?
                  $deleted_query
                  AND (`weight` IS NULL OR `weight` <> 0)
                  ORDER BY ? ?";

        $params[] = $order; $params[] = $direction;

        $responses = $db->GetAll($query, $params);

        $result = array();
        foreach ($responses as $response) {
            $self = new self();
            $result[] = $self->fromArray($response);
        }

        return $result;
    }

    public static function fetchAllRecords($deleted_date = null, $sort_column = null, $sort_direction = null) {
        $self = new self();
        return $self->fetchAll(array(array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : null), "method" => ($deleted_date ? "<=" : "IS"))), "=", "AND", $sort_column, $sort_direction);
    }

    /**
     * Delete rating scale from specified ID
     *
     * @param $rating_scale_id
     * @return mixed
     */
    public static function deleteRowsByRatingScaleID($rating_scale_id) {
        global $db;
        $query = "DELETE FROM `".self::$table_name."`
                  WHERE rating_scale_id = ?";

        return $db->execute($query, array($rating_scale_id));
    }

    /**
     * Fetch Scale Responses by rating_scale_id including the response descriptor
     *
     * @param $rating_scale_id
     * @return array
     */
    public function fetchAllByRatingScaleIDIncludeDescriptor ($rating_scale_id = 0, $include_zero_weight = false) {
        global $db;

        $weight_qry = "";
        if (!$include_zero_weight) {
            $weight_qry = " AND (a.`weight` <> 0 OR a.`weight` IS NULL)";
        }

        $query = "  SELECT a.*, b.* FROM `cbl_assessment_rating_scale_responses` AS a
                    JOIN `cbl_assessments_lu_response_descriptors` AS b
                    ON a.`ardescriptor_id` = b.`ardescriptor_id`
                    WHERE a.`rating_scale_id` = ?
                    $weight_qry
                    AND a.`deleted_date` IS NULL
                    AND b.`deleted_date` IS NULL";
        $results = $db->GetAll($query, array($rating_scale_id));
        if (!$results) {
            return array();
        }
        return $results;
    }

    /**
     * Fetch Scale Responses by rating_scale_id ignoring any responses that have a weight of 0
     * @param int $item_id
     * @return array
     */
    public function fetchAllByItemRatingScaleIDIncludeItemResponses($item_id = 0) {
        global $db;
        $query = "  SELECT b.`iresponse_id`, c.* FROM `cbl_assessments_lu_items` AS a
                    JOIN `cbl_assessments_lu_item_responses` AS b
                    ON a.`item_id` = b.`item_id`
                    JOIN `cbl_assessment_rating_scale_responses` AS c
                    ON b.`ardescriptor_id` = c.`ardescriptor_id`
                    AND a.`rating_scale_id` = c.`rating_scale_id`
                    WHERE a.`item_id` = ?
                    AND (c.`weight` <> 0 OR c.`weight` IS NULL)
                    AND a.`deleted_date` IS NULL
                    AND b.`deleted_date` IS NULL
                    AND c.`deleted_date` IS NULL
                    GROUP BY c.`rating_scale_response_id`
                    ORDER BY b.`order`";
        $results = $db->GetAll($query, array($item_id));
        if (!$results) {
            return array();
        }
        return $results;
    }

    /**
     * Fetch Scale Responses by rating_scale_id.  Include the 0 weight responses
     * @param int $item_id
     * @return array
     */
    public function fetchAllByItemRatingScaleID($item_id = 0) {
        global $db;
        $query = "  SELECT b.`iresponse_id`, c.* FROM `cbl_assessments_lu_items` AS a
                    JOIN `cbl_assessments_lu_item_responses` AS b
                    ON a.`item_id` = b.`item_id`
                    JOIN `cbl_assessment_rating_scale_responses` AS c
                    ON b.`ardescriptor_id` = c.`ardescriptor_id`
                    AND a.`rating_scale_id` = c.`rating_scale_id`
                    WHERE a.`item_id` = ?
                    AND a.`deleted_date` IS NULL
                    AND b.`deleted_date` IS NULL
                    AND c.`deleted_date` IS NULL
                    GROUP BY c.`rating_scale_response_id`
                    ORDER BY b.`order`";
        $results = $db->GetAll($query, array($item_id));
        if (!$results) {
            return array();
        }
        return $results;
    }
}