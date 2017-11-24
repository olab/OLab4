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
 * This class acts as the primary point of interaction with assessment
 * form related functionality and data. All input/output of for data and form
 * manipulation should utilize this class.
 *
 * @author Organization: Queen's University
 * @author Unit: Health Sciences, Education Technology Unit
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
abstract class Entrada_Assessments_Forms_Base extends Entrada_Assessments_Base {

    protected $actor_proxy_id = null;
    protected $actor_organisation_id = null;
    protected $dataset = array();
    protected $error = array("status" => true, "messages" => array());

    public function getError() {
        return $this->error;
    }

    public function getErrorMessages() {
        return $this->error["messages"];
    }

    public function getErrorStatus() {
        return $this->error["status"];
    }

    public function setErrorStatus($status) {
        if ($status) {
            $this->error["status"] = true;
        } else {
            $this->error["status"] = false;
        }
        return $this->error["status"];
    }

    public function addErrorMessage($message) {
        $this->error["messages"][] = $message;
    }

    /**
     * Validate and load data into the dataset.
     *
     * @param array
     * @param bool $validate
     * @return bool
     */
    abstract public function loadData($data, $validate);

    /**
     * Save the current dataset to the database.
     *
     * @return bool
     */
    abstract public function saveData();

    /**
     * Fetch all related data points, return in a data structure.
     *
     * @return false|array
     */
    abstract public function fetchData();

    /**
     * Determine if this object is editable.
     *
     * @return bool
     */
    abstract protected function determineEditable();

    /**
     * Find whether this object is in use.
     *
     * @return bool
     */
    abstract protected function determineUsage();

    /**
     * In the given progress data array (derived by fetchFormData) find if the response
     * given is among the progress responses.
     *
     * @param $progress
     * @param $iresponse_id
     * @return bool
     */
    protected function isResponseSelected(&$progress, $iresponse_id) {
        if (empty($progress)) {
            return false;
        }
        if (empty($progress["progress_responses"])) {
            return false;
        }
        foreach ($progress["progress_responses"] as $progress_response) {
            if ($progress_response["iresponse_id"] == $iresponse_id) {
                return true;
            }
        }
        return false;
    }

    /**
     * Iterate through progress and responses and check if an element has a comment via flagging. Return the relevant information in an array.
     *
     * @param string $comment_type
     * @param array $responses
     * @param array|null $progress_responses (can be empty, or unset)
     * @return array
     */
    protected function getCommentFlaggingInfo($comment_type, &$responses, &$progress_responses) {
        $flagging_info = array(
            "comment_type" => "disabled",
            "afelement_id" => null,
            "comment_text" => null,
            "iresponse_id" => null,
            "render_comment_container" => false,
            "comment_container_visible" => false
        );
        if (!is_array($responses) || empty($responses) || !$comment_type ) {
            return $flagging_info;
        }
        if (!is_array($progress_responses) || empty($progress_responses)) {
            $progress_responses = array();
        }

        $flagging_info["comment_type"] = $comment_type;

        switch ($comment_type) {
            case "disabled":
                $flagging_info["render_comment_container"] = false;
                $flagging_info["comment_container_visible"] = false;
                break;
            case "optional":
            case "mandatory":
                $flagging_info["render_comment_container"] = true;
                $flagging_info["comment_container_visible"] = true;
                break;
            case "flagged":
                $flagging_info["render_comment_container"] = true;
                $flagging_info["comment_container_visible"] = false;
                break;
        }

        if ($comment_type != "disabled") {
            foreach ($progress_responses as $progress_response) {
                foreach ($responses as $iresponse_id => $response) {
                    if ($response["iresponse_id"] == $progress_response["iresponse_id"]) {
                        if ($progress_response["comments"]) {
                            $flagging_info["afelement_id"] = $progress_response["afelement_id"];
                            $flagging_info["comment_text"] = $progress_response["comments"];
                            $flagging_info["iresponse_id"] = $progress_response["iresponse_id"];
                        }
                    }
                }
            }
        }
        return $flagging_info;
    }

    protected function fetchTagsByItemID($item_id) {
        // Not implemented.
        return array();
    }

    /**
     * Fetch all responses for an for item.
     *
     * TODO: Move this to a model
     *
     * @param $item_id
     * @return array
     */
    protected function fetchResponsesByItemID($item_id) {

        $sql = "SELECT  a.`item_id`, a.`one45_element_id`, a.`organisation_id`, a.`itemtype_id`, a.`item_code`, a.`item_text`,
                        a.`item_description`, a.`comment_type`, a.`created_date`, a.`created_by`, a.`updated_date`, a.`updated_by`, a.`deleted_date`,
                        b.`ardescriptor_id` AS `ardescriptor_id`, b.`iresponse_id` AS `iresponse_id`, b.`flag_response` AS `flag_response`,
                        b.`text` AS `response_text`, b.`order` AS `response_order`

                FROM `cbl_assessments_lu_items` AS a
                JOIN `cbl_assessments_lu_item_responses` AS b ON a.`item_id` = b.`item_id`

                WHERE b.`item_id` = ?
                AND b.`deleted_date` IS NULL
                ORDER BY b.`order` ASC";
        return $this->getAllArrayIndexed("iresponse_id", $sql, array($item_id));
    }

    /**
     * Fetch a form item.
     *
     * TODO: Move this to a model
     *
     * @param $item_id
     * @return mixed
     */
    protected function fetchItemByItemID($item_id) {

        $sql = "SELECT  a.`item_id`, a.`one45_element_id`, a.`organisation_id`, a.`itemtype_id`, a.`item_code`, a.`item_text`, a.`item_description`, a.`comment_type`, a.`created_date`,
                        a.`created_by`, a.`updated_date`, a.`updated_by`, a.`deleted_date`,
                        b.`name` AS `item_type_name`, b.`shortname` AS `shortname`

                FROM `cbl_assessments_lu_items` AS a
                JOIN `cbl_assessments_lu_itemtypes` AS b ON a.`itemtype_id` = b.`itemtype_id`

                WHERE a.`item_id` = ?
                ORDER BY a.`item_id` ASC";
        global $db;
        return $db->GetRow($sql, array($item_id));
    }

    /**
     * Execute a db->GetAll() and reorder the results by specified key.
     *
     * @param string $index
     * @param string $query (SQL)
     * @param array $params
     * @return array
     */
    protected function getAllArrayIndexed($index, $query, $params = array()) {
        global $db;
        $results = $db->GetAll($query, $params);
        $ordered = array();
        if (is_array($results) && !empty($results)) {
            foreach ($results as $result) {
                $ordered[$result[$index]] = $result;
            }
        }
        return $ordered;
    }

}