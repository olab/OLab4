<?php
/**
 * @author Organisation: Queen's University
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Views_Deprecated_Item extends Views_Deprecated_Base {
    protected $default_fieldset = array("item_id", "one45_element_id", "organisation_id", "itemtype_id", "item_code", "item_text", "item_description", "comment_type", "created_date", "created_by", "updated_date", "updated_by", "deleted_date");

    protected $table_name               = "cbl_assessments_lu_items";
    protected $primary_key              = "item_id";
    protected $default_sort_column      = "`cbl_assessments_lu_items`.`item_id`";
    protected $joinable_tables          = array(
        "cbl_assessments_lu_item_relationships" => array(
            "fields" => array(
                "first_parent_id" => "first_parent_id",
                "immediate_parent_id" => "immediate_parent_id"
            ),
            "join_conditions" => "`cbl_assessments_lu_items`.`item_id` = `cbl_assessments_lu_item_relationships`.`item_id`",
            "left" => true
        ),
        "cbl_assessments_lu_item_responses" => array(
            "fields" => array(
                "iresponse_id" => "iresponse_id",
                "response_text" => "text",
                "response_order" => "order",
                "ardescriptor_id" => "ardescriptor_id",
                "allow_html" => "allow_html",
                "flag_response" => "flag_response"
            ),
            "join_conditions" => "`cbl_assessments_lu_items`.`item_id` = `cbl_assessments_lu_item_responses`.`item_id`",
            "left" => false
        ),
        "cbl_assessment_item_authors" => array(
            "fields" => array(
                "author_type" => "author_type",
                "author_id" => "author_id"
            ),
            "join_conditions" => "`cbl_assessments_lu_items`.`item_id` = `cbl_assessment_item_authors`.`item_id`",
            "left" => false
        ),
        "cbl_assessment_item_objectives" => array(
            "fields" => array(
                "objective_id" => "objective_id"
            ),
            "join_conditions" => "`cbl_assessments_lu_items`.`item_id` = `cbl_assessment_item_objectives`.`item_id`",
            "left" => true
        ),
        "global_lu_objectives" => array(
            "fields" => array(
                "objective_id" => "objective_id"
            ),
            "join_conditions" => "`cbl_assessment_item_objectives`.`objective_id` = `global_lu_objectives`.`objective_id`",
            "left" => false
        ),
        "cbl_assessment_item_tags" => array(
            "fields" => array(
                "tag_id" => "tag_id"
            ),
            "join_conditions" => "`cbl_assessments_lu_items`.`item_id` = `cbl_assessment_item_tags`.`item_id`",
            "left" => true
        ),
        "cbl_assessments_lu_tags" => array(
            "required_tables" => array("cbl_assessment_item_tags"),
            "fields" => array(
                "tag" => "tag"
            ),
            "join_conditions" => "`cbl_assessment_item_tags`.`tag_id` = `cbl_assessments_lu_tags`.`tag_id`",
            "left" => true
        ),
        "cbl_assessment_form_elements" => array(
            "fields" => array(
                "form_id" => "form_id",
                "element_text" => "element_text",
                "rubric_id" => "rubric_id",
                "element_order" => "order",
                "element_allow_comments" => "allow_comments",
                "element_mandatory_comments" => "allow_comments"
            ),
            "join_conditions" => "`cbl_assessment_form_elements`.`element_type` = 'item'
                                  AND `cbl_assessments_lu_items`.`item_id` = `cbl_assessment_form_elements`.`element_id`",
            "left" => true
        ),
        "cbl_assessments_lu_itemtypes" => array(
            "fields" => array(
                "shortname" => "shortname",
                "item_type_name" => "name",
                "item_type_description" => "description"
            ),
            "join_conditions" => "`cbl_assessments_lu_items`.`itemtype_id` = `cbl_assessments_lu_itemtypes`.`itemtype_id`",
            "left" => false
        )
    );
    protected $view_data = array();
    protected $distribution_data = array();

    public function fetchItemsByForm($form_id) {
        $fieldset = $this->default_fieldset;
        $fieldset[] = "item_type_name";
        $fieldset[] = "shortname";
        $this->setFields($fieldset);
        $this->addTableJoins("cbl_assessment_form_elements");
        $constraints = array(array("key" => "`".DATABASE_NAME."`.`cbl_assessment_form_elements`.`form_id`", "value" => $form_id));
        return $this->fetchAll($constraints);
    }

    public static function fetchItemByID($item_id) {
        $self = new self();

        $fieldset = $self->default_fieldset;
        $fieldset[] = "item_type_name";
        $fieldset[] = "shortname";

        $self->setFields($fieldset);
        $constraints = array(array("key" => "`cbl_assessments_lu_items`.`item_id`", "value" => $item_id));

        $self->view_data[] = $self->fetchRow($constraints);
        return $self;
    }

    public function fetchItemTags($item_id) {
        $fieldset[] = "tag_id";
        $fieldset[] = "tag";
        $this->setFields($fieldset);
        $constraints = array(array("key" => "`".DATABASE_NAME."`.`cbl_assessment_item_tags`.`item_id`", "value" => $item_id));

        return $this->fetchAll($constraints);
    }

    public static function fetchItemsByAuthor($author_id, $form_id) {
        $self = new self();

        $fieldset = $self->default_fieldset;
        $fieldset[] = "author_id";
        $fieldset[] = "author_type";
        $fieldset[] = "item_type_name";

        $self->setFields($fieldset);
        $constraints = array(
            array("key" => "`".DATABASE_NAME."`.`cbl_assessment_item_authors`.`author_id`", "value" => $author_id),
            array("key" => "`".DATABASE_NAME."`.`cbl_assessment_item_authors`.`author_type`", "value" => "proxy_id")
        );

        if (!is_null($form_id)) {
            $self->addTableJoins("cbl_assessment_form_elements");
            $constraints[] = array("key" => "`".DATABASE_NAME."`.`cbl_assessment_form_elements`.`afelement_id`", "value" => NULL, "method" => "IS");
        }

//        $self->view_data = $self->fetchAll($constraints);
//      @TODO: For demonstration purposes only
        global $db;
        $query = "SELECT a.*, b.*
                    FROM `cbl_assessments_lu_items` AS a
                    LEFT JOIN `cbl_assessment_form_elements` AS b
                    ON a.`item_id` = b.`element_id`
                    AND b.`form_id` = ".$db->qstr($form_id)."
                    WHERE b.`afelement_id` IS NULL
                    LIMIT 15";
        $self->view_data = $db->GetAll($query);
        return $self;
    }

    public function fetchItemResponses($item_id) {
        $this->addTableJoins("cbl_assessments_lu_item_responses");
        $fieldset = $this->default_fieldset;
        $fieldset[] = "ardescriptor_id";
        $fieldset[] = "iresponse_id";
        $fieldset[] = "flag_response";
        $fieldset[] = "response_text";
        $fieldset[] = "response_order";
        $this->setFields($fieldset);
        $constraints = array(
            array("key" => "`".DATABASE_NAME."`.`cbl_assessments_lu_item_responses`.`item_id`", "value" => $item_id),
            array("key" => "`".DATABASE_NAME."`.`cbl_assessments_lu_item_responses`.`deleted_date`", "value" => NULL, "method" => "IS"));
        return $this->fetchAll($constraints);
    }

    /**
     * Renders the curriculum tags added to an assessment ID that can be clicked to bring up information: tag name code and description
     * @param  array  $curriculum_tags  curriculum/objective tags object
     * @return string 				  html
     */
    public function renderCurriculumTags($curriculum_tags) {
        $html = array();

        if (is_array($curriculum_tags) && count($curriculum_tags)) {
            $i = 0;

            $html[] = '<span style="margin: 0px 8px;">';
            foreach ($curriculum_tags as $val) {

                if (isset($val['objective_id']) && $val['objective_id']) {
                    if ($i++ > 0) {
                        $html[] = ',';
                    }
                    $html[] = '<a href="#" onclick="return false;" class="curriculum-tag" data-toggle="popover" objective-id="' . $val['objective_id'] . '" title="' .
                        html_encode($val['objective_name']) . '" data-content="' . html_encode($val['objective_description']);
                    if ($val['objective_code'] != '') {
                        $html[] = '(' . $val['objective_code'] . ')"';
                    }
                    $html[] = '">';
                    $html[] = limit_chars($val['objective_name'], 20, true, true) . '</a>';
                }
            }
            $html[] = '</span>';
        }

        return implode("\n", $html);
    }

    private function buildHeader($item, $count, $display_mode = false, $control_array = NULL) {
        $html = "";

        $objectives = Models_Assessments_Item::fetchItemObjectives($item["item_id"]);

        $html .= "<table class=\"item-table " . str_replace("_", "-", $item["shortname"]) . "\">";
        if ($display_mode === false) {
            $html .= "  <tr class=\"type\">";
            $html .= "      <td colspan=\"" . $count . "\">
                                    <span class=\"item-type\"> " . $item["item_type_name"] . "</span>
                                    <div class=\"pull-right\">";
            if (is_null($control_array)) {
                $html .= "              <span class=\"btn select-item\"><input type=\"checkbox\" class=\"item-selector\" name=\"items[]\" value=\"" .html_encode($item["item_id"]). "\"/></span>
                                        <div class=\"btn-group\">
                                            <a href=\"" . ENTRADA_URL . "/admin/assessments/items?section=edit-item&item_id=" .html_encode($item["item_id"]). "\" title=\"Edit Item\" class=\"btn edit-item\"><i class=\"icon-pencil\"></i></a>
                                            <a href=\"#\" title=\"View Item Details\" class=\"btn item-details\"><i class=\"icon-eye-open\"></i></a>
                                        </div>";
            } else {
                if (is_array($control_array) && !empty($control_array)) {
                    foreach ($control_array as $control_group) {
                        $html .= "<div class=\"btn-group\">";
                        foreach ($control_group as $control) {
                            $html .= $control;
                        }
                        $html .= "</div>";
                    }
                }
            }
            $html .= "        </div>
                                </td>";
            $html .= "  </tr>";
        }
        $html .= "  <tr class=\"heading\">";
        $html .= "      <td colspan=\"". $count ."\">
                            <h3>". html_encode($item["item_text"]) ."</h3>
                            ".$this->renderCurriculumTags($objectives)."
                        </td>";
        $html .= "  </tr>";

        return $html;
    }

    public function renderHorizontalChoiceSingleResponse ($item = null, $display_mode = false, $control_array = NULL, $disabled = false, $is_pdf = false) {
        $html = "";
        $deleted_distribution = Models_Assessments_Distribution::fetchRowByID($this->distribution_data["adistribution_id"], time());
        $html .= "<div class=\"assessment-horizontal-choice-item item-container\" data-item-id=\"".html_encode($item["item_id"])."\" data-comment-type=\"" . html_encode($item["comment_type"]) . "\">";

        $responses = $this->fetchItemResponses($item["item_id"]);

        $count = count($responses);

        $html .= $this->buildHeader($item, $count, $display_mode, $control_array);

        $tags = $this->fetchItemTags($item["item_id"]);

        $flag_selected = false;

        if ($responses) {

            $column_width = (100 / $count);
            $html .= "  <tr class=\"horizontal-response-input item-response-view\">";
            foreach ($responses as $response) {
                $progress = Models_Assessments_Progress::fetchRowByID($this->distribution_data["aprogress_id"]);
                $progress_response = Models_Assessments_Progress_Response::fetchRowByAprogressIDIresponseID($this->distribution_data["aprogress_id"], $response["iresponse_id"]);
                if ($progress_response && $item["comment_type"] == "flagged" && $response["flag_response"]) {
                    $flag_selected = true;
                }
                $html .= "  <td width=\"". $column_width ."%\">
                                <label for=\"item-".$item["item_id"]."-response-".$response["iresponse_id"]."\">
                                    <input type=\"radio\" class=\"item-control\" ".(($progress && $progress->getProgressValue() != "inprogress") || $deleted_distribution || $disabled ? "DISABLED" : "")." data-item-id=\"" . html_encode($item["item_id"]) . "\" data-iresponse-id=\"" . html_encode($response["iresponse_id"]) . "\"" . ($response["flag_response"] ? "data-response-flagged=\"true\" " : "") . "id=\"item-" . html_encode($item["item_id"]) . "-response-" . html_encode($response["iresponse_id"]) . "\" name=\"item-" . html_encode($item["item_id"]) . "\" value=\"" . html_encode($response["iresponse_id"]) . "\" ".($progress_response ? " checked=\"checked\"" : "")."  />
                                </label>
                            </td>";
            }
            $html .= "  </tr>";
            $html .= "  <tr class=\"horizontal-response-label item-response-view\">";
            foreach ($responses as $response) {
                $response_descriptor = false;
                if (!is_null($response["ardescriptor_id"])) {
                    $response_descriptor = Models_Assessments_Response_Descriptor::fetchRowByIDIgnoreDeletedDate($response["ardescriptor_id"]);
                }

                $html .= "  <td width=\"". $column_width ."%\">
                                <label for=\"item-". html_encode($item["item_id"]) ."-response-". html_encode($response["iresponse_id"])."\">". ($response_descriptor ? html_encode($response_descriptor->getDescriptor()) : html_encode($response["response_text"])) ."</label>
                            </td>";
            }
            $html .= "  </tr>";
            if (!$is_pdf) {
                $html .= "  <tr class=\"item-detail-view hide\">";
                $html .= "      <td colspan=\"" . $count . "\">";
                $html .= "          <div class=\"item-details-container\">";
                $html .= "                  <h5>Item Code: <span>" . ($item["item_code"] ? $item["item_code"] : "N/A") . "</span></h5>";
                $html .= "                  <h5>Item Tagged With</h5>";
                $html .= "                  <div class=\"item-tags\">";
                if ($tags) {
                    foreach ($tags as $tag) {
                        $html .= "              <span><a href=\"#\">" . $tag["tag"] . "</a></span>";
                    }
                } else {
                    $html .= "<p>This item has no tags.</p>";
                }
                $html .= "                  </div>";
                $html .= "                  <ul>";
                switch ($item["comment_type"]) {
                    case "optional" :
                        $comment_type = "optional";
                        break;
                    case "mandatory" :
                        $comment_type = "mandatory";
                        break;
                    case "flagged" :
                        $comment_type = "mandatory for flagged responses";
                        break;
                    case "disabled" :
                    default :
                        $comment_type = "disabled";
                        break;
                }
                $html .= "                      <li><span>Comments</span> are " . $comment_type . " for this Item";
                $html .= "                      <li class=\"pull-right\"><span>Item was created on</span>: " . date("Y-m-d", $item["created_date"]) . "</li>";
                $html .= "                  </ul>";
                $html .= "          </div>";
                $html .= "     </td>";
                $html .= "  </tr>";
                if ($display_mode) {
                    $html .= $this->buildItemComments($item, $count, $flag_selected, $disabled);
                }
            }
        } else {
            $html .= "<tr><td colspan=\"". $count ."\">There are no responses...</td></tr>";
        }
        $html .= "</table>";
        $html .= "</div>";
        return $html;
    }

    public function renderVerticalChoiceSingleResponse ($item = null, $display_mode = false, $control_array = NULL, $disabled = false, $is_pdf = false) {
        $deleted_distribution = Models_Assessments_Distribution::fetchRowByID($this->distribution_data["adistribution_id"], time());
        $html = "";
        $html .= "<div class=\"assessment-horizontal-choice-item item-container\" data-item-id=\"".html_encode($item["item_id"])."\" data-comment-type=\"" . html_encode($item["comment_type"]) . "\">";

        $responses = $this->fetchItemResponses($item["item_id"]);
        $flag_selected = false;
        $tags = $this->fetchItemTags($item["item_id"]);

        if ($responses) {
            $count = count($responses);
            $html .= $this->buildHeader($item, $count, $display_mode, $control_array);
            $response_count = 1;
            foreach ($responses as $response) {
                $progress = Models_Assessments_Progress::fetchRowByID($this->distribution_data["aprogress_id"]);
                $progress_response = Models_Assessments_Progress_Response::fetchRowByAprogressIDIresponseID($this->distribution_data["aprogress_id"], $response["iresponse_id"]);
                if ($progress_response && $item["comment_type"] == "flagged" && $response["flag_response"]) {
                    $flag_selected = true;
                }
                $html .= "  <tr". ($response_count & 1 === 1 ? " class=\"item-response-view\"" : " class=\"row-stripe item-response-view\"") .">
                                <td width=\"5%\" class=\"vertical-response-input\">
                                    <input type=\"radio\" class=\"item-control\" ".(($progress && $progress->getProgressValue() != "inprogress") || $deleted_distribution || $disabled ? "DISABLED" : "")." data-item-id=\"" . html_encode($item["item_id"]) . "\" " . ($response["flag_response"] ? "data-response-flagged=\"true\" " : "") . "data-iresponse-id=\"" . html_encode($response["iresponse_id"]) . "\" id=\"item-" . html_encode($item["item_id"]) . "-response-" . html_encode($response["iresponse_id"]) . "\" name=\"item-" . html_encode($item["item_id"]) . "\" value=\"" . html_encode($response["iresponse_id"]) . "\" ".($progress_response ? " checked=\"checked\"" : "")." />
                                </td>
                                <td width=\"95%\" class=\"vertical-response-label\">
                                    <label for=\"item-" . html_encode($item["item_id"]) . "-response-" . html_encode($response["iresponse_id"]) . "\">". html_encode($response["response_text"]) ."</label>
                                </td>
                            </tr>";
                $response_count = $response_count + 1;
            }
        }
        if (!$is_pdf) {
            $html .= "  <tr class=\"item-detail-view hide\">";
            $html .= "      <td colspan=\"" . $count . "\">";
            $html .= "          <div class=\"item-details-container\">";
            $html .= "                  <h5>Item Code: <span>" . ($item["item_code"] ? html_encode($item["item_code"]) : "N/A") . "</span></h5>";
            $html .= "                  <h5>Item Tagged With</h5>";
            $html .= "                  <div class=\"item-tags\">";
            if ($tags) {
                foreach ($tags as $tag) {
                    $html .= "              <span><a href=\"#\">" . html_encode($tag["tag"]) . "</a></span>";
                }
            } else {
                $html .= "<p>This item has no tags.</p>";
            }
            $html .= "                  </div>";
            $html .= "                  <ul>";
            switch ($item["comment_type"]) {
                case "optional" :
                    $comment_type = "optional";
                    break;
                case "mandatory" :
                    $comment_type = "mandatory";
                    break;
                case "flagged" :
                    $comment_type = "mandatory for flagged responses";
                    break;
                case "disabled" :
                default :
                    $comment_type = "disabled";
                    break;
            }
            $html .= "                      <li><span>Comments</span> are " . $comment_type . " for this Item";
            $html .= "                      <li class=\"pull-right\"><span>Item was created on</span>: " . date("Y-m-d", $item["created_date"]) . "</li>";
            $html .= "                  </ul>";
            $html .= "          </div>";
            $html .= "     </td>";
            $html .= "  </tr>";
            if ($display_mode) {
                $html .= $this->buildItemComments($item, $count, $flag_selected, $disabled);
            }
        }
        $html .= "</table>";
        $html .= "</div>";
        return $html;
    }

    public function renderHorizontalChoiceMultipleResponse ($item = null, $display_mode = false, $control_array = NULL, $disabled = false, $is_pdf = false) {
        $deleted_distribution = Models_Assessments_Distribution::fetchRowByID($this->distribution_data["adistribution_id"], time());
        $html = "";
        $html .= "<div class=\"assessment-horizontal-choice-item item-container\" data-item-id=\"".html_encode($item["item_id"])."\" data-comment-type=\"" . html_encode($item["comment_type"]) . "\">";

        $responses = $this->fetchItemResponses($item["item_id"]);
        $tags = $this->fetchItemTags($item["item_id"]);
        $flag_selected = false;

        if ($responses) {
            $count = count($responses);
            $html .= $this->buildHeader($item, $count, $display_mode, $control_array);

            $column_width = (100 / $count);
            $html .= "  <tr class=\"horizontal-response-input item-response-view\">";
            foreach ($responses as $response) {
                $progress = Models_Assessments_Progress::fetchRowByID($this->distribution_data["aprogress_id"]);
                $progress_response = Models_Assessments_Progress_Response::fetchRowByAprogressIDIresponseID($this->distribution_data["aprogress_id"], $response["iresponse_id"]);
                if ($progress_response && $item["comment_type"] == "flagged" && $response["flag_response"]) {
                    $flag_selected = true;
                }
                $html .= "  <td width=\"". $column_width ."%\">
                                <label for=\"item-" . html_encode($item["item_id"]) . "-response-" . html_encode($response["iresponse_id"]) ."\">
                                    <input type=\"checkbox\" class=\"item-control\" ".(($progress && $progress->getProgressValue() != "inprogress") || $deleted_distribution || $disabled ? "DISABLED" : "")." data-item-id=\"" . html_encode($item["item_id"]) . "\" " . ($response["flag_response"] ? "data-response-flagged=\"true\" " : "") . "data-iresponse-id=\"" . html_encode($response["iresponse_id"]) . "\" id=\"item-" . html_encode($item["item_id"]) . "-response-" . html_encode($response["iresponse_id"]) . "\" name=\"item-" . html_encode($item["item_id"]) . "[]\" value=\"" . html_encode($response["iresponse_id"]) . "\" ".($progress_response ? " checked=\"checked\"" : "")." />
                                </label>
                            </td>";
            }
            $html .= "  </tr>";
            $html .= "  <tr class=\"horizontal-response-label item-response-view\">";
            foreach ($responses as $response) {
                $html .= "  <td width=\"". $column_width ."%\">
                                <label for=\"item-" . html_encode($item["item_id"]) . "-response-" . html_encode($response["iresponse_id"]) . "\">". $response["response_text"] . "</label>
                            </td>";
            }
            $html .= "  </tr>";
        }
        if (!$is_pdf) {
            $html .= "  <tr class=\"item-detail-view hide\">";
            $html .= "      <td colspan=\"" . $count . "\">";
            $html .= "          <div class=\"item-details-container\">";
            $html .= "                  <h5>Item Code: <span>" . ($item["item_code"] ? html_encode($item["item_code"]) : "N/A") . "</span></h5>";
            $html .= "                  <h5>Item Tagged With</h5>";
            $html .= "                  <div class=\"item-tags\">";
            if ($tags) {
                foreach ($tags as $tag) {
                    $html .= "              <span><a href=\"#\">" . html_encode($tag["tag"]) . "</a></span>";
                }
            } else {
                $html .= "<p>This item has no tags.</p>";
            }
            $html .= "                  </div>";
            $html .= "                  <ul>";
            switch ($item["comment_type"]) {
                case "optional" :
                    $comment_type = "optional";
                    break;
                case "mandatory" :
                    $comment_type = "mandatory";
                    break;
                case "flagged" :
                    $comment_type = "mandatory for flagged responses";
                    break;
                case "disabled" :
                default :
                    $comment_type = "disabled";
                    break;
            }
            $html .= "                      <li><span>Comments</span> are " . $comment_type . " for this Item";
            $html .= "                      <li class=\"pull-right\"><span>Item was created on</span>: " . date("Y-m-d", $item["created_date"]) . "</li>";
            $html .= "                  </ul>";
            $html .= "          </div>";
            $html .= "     </td>";
            $html .= "  </tr>";
            if ($display_mode) {
                $html .= $this->buildItemComments($item, $count, $flag_selected, $disabled);
            }
        }
        $html .= "</table>";
        $html .= "</div>";
        return $html;
    }

    public function renderVerticalChoiceMultipleResponse ($item = null, $display_mode = false, $control_array = NULL, $disabled = false, $is_pdf = false) {
        $deleted_distribution = Models_Assessments_Distribution::fetchRowByID($this->distribution_data["adistribution_id"], time());
        $html = "";
        $html .= "<div class=\"assessment-horizontal-choice-item item-container\" data-item-id=\"".html_encode($item["item_id"])."\" data-comment-type=\"" . html_encode($item["comment_type"]) . "\">";

        $responses = $this->fetchItemResponses($item["item_id"]);
        $tags = $this->fetchItemTags($item["item_id"]);
        $flag_selected = false;

        if ($responses) {
            $count = count($responses);
            $html .= $this->buildHeader($item, $count, $display_mode, $control_array);
            $response_count = 1;
            foreach ($responses as $response) {
                $progress = Models_Assessments_Progress::fetchRowByID($this->distribution_data["aprogress_id"]);
                $progress_response = Models_Assessments_Progress_Response::fetchRowByAprogressIDIresponseID($this->distribution_data["aprogress_id"], $response["iresponse_id"]);
                if ($progress_response && $item["comment_type"] == "flagged" && $response["flag_response"]) {
                    $flag_selected = true;
                }
                $html .= "  <tr". ($response_count & 1 === 1 ? " class=\"item-response-view\"" : " class=\"row-stripe item-response-view\"") .">
                                <td width=\"5%\" class=\"vertical-response-input\">
                                    <label for=\"item-" . html_encode($item["item_id"]) . "-response-" . html_encode($response["iresponse_id"]) ."\">
                                        <input type=\"checkbox\" class=\"item-control\"" .(($progress && $progress->getProgressValue() != "inprogress") || $deleted_distribution || $disabled ? "DISABLED" : ""). "  data-item-id=\"" . html_encode($item["item_id"]) . "\" data-iresponse-id=\"" . html_encode($response["iresponse_id"]) ."\" " . ($response["flag_response"] ? "data-response-flagged=\"true\" " : "") . " id=\"item-" . html_encode($item["item_id"]) . "-response-" . html_encode($response["iresponse_id"]) . "\" name=\"item-" . html_encode($item["item_id"]) ."[]\" value=\"" . html_encode($response["iresponse_id"]) ."\" ".($progress_response ? " checked=\"checked\"" : "")." />
                                    </label>
                                </td>
                                <td width=\"95%\" class=\"vertical-response-label\"><label for=\"item-" . html_encode($item["item_id"]) . "-response-" . html_encode($response["iresponse_id"]) ."\">". $response["response_text"] . "</label></td>
                            </tr>";
                $response_count = $response_count + 1;
            }
        }
        if (!$is_pdf) {
            $html .= "  <tr class=\"item-detail-view hide\">";
            $html .= "      <td colspan=\"" . $count . "\">";
            $html .= "          <div class=\"item-details-container\">";
            $html .= "                  <h5>Item Code: <span>" . ($item["item_code"] ? html_encode($item["item_code"]) : "N/A") . "</span></h5>";
            $html .= "                  <h5>Item Tagged With</h5>";
            $html .= "                  <div class=\"item-tags\">";
            if ($tags) {
                foreach ($tags as $tag) {
                    $html .= "              <span><a href=\"#\">" . html_encode($tag["tag"]) . "</a></span>";
                }
            } else {
                $html .= "<p>This item has no tags.</p>";
            }
            $html .= "                  </div>";
            $html .= "                  <ul>";
            switch ($item["comment_type"]) {
                case "optional" :
                    $comment_type = "optional";
                    break;
                case "mandatory" :
                    $comment_type = "mandatory";
                    break;
                case "flagged" :
                    $comment_type = "mandatory for flagged responses";
                    break;
                case "disabled" :
                default :
                    $comment_type = "disabled";
                    break;
            }
            $html .= "                      <li><span>Comments</span> are " . $comment_type . " for this Item";
            $html .= "                      <li class=\"pull-right\"><span>Item was created on</span>: " . date("Y-m-d", $item["created_date"]) . "</li>";
            $html .= "                  </ul>";
            $html .= "          </div>";
            $html .= "     </td>";
            $html .= "  </tr>";
            if ($display_mode) {
                $html .= $this->buildItemComments($item, $count, $flag_selected, $disabled);
            }
        }
        $html .= "</table>";
        $html .= "</div>";
        return $html;
    }

    public function renderDropdownSingleResponse ($item = null, $display_mode = false, $control_array = NULL, $disabled = false, $is_pdf = false) {
        $deleted_distribution = Models_Assessments_Distribution::fetchRowByID($this->distribution_data["adistribution_id"], time());
        $html = "";
        $html .= "<div class=\"item-container\" data-item-id=\"".html_encode($item["item_id"])."\" data-comment-type=\"" . html_encode($item["comment_type"]) . "\">";

        $responses = $this->fetchItemResponses($item["item_id"]);

        $tags = $this->fetchItemTags($item["item_id"]);

        $flag_selected = false;
        $response_selected = false;

        if ($responses) {
            $count = count($item);
            $html .= $this->buildHeader($item, $count, $display_mode, $control_array);
            $html .= "<tr class=\"item-response-view\">
                        <td class=\"item-type-control\">";

            $progress = Models_Assessments_Progress::fetchRowByID($this->distribution_data["aprogress_id"]);
            $html .= "      <select id=\"item-" . html_encode($item["item_id"]) . "\" data-item-id=\"".html_encode($item["item_id"])."\" name=\"item-" . html_encode($item["item_id"]) . "\" class=\"item-control\" " . (($progress && $progress->getProgressValue() != "inprogress") || $deleted_distribution || $disabled ? "DISABLED" : "").">";
            $html .= "          <option value=\"\" selected=\"selected\"></option>";
            foreach ($responses as $response) {
                $progress_response = Models_Assessments_Progress_Response::fetchRowByAprogressIDIresponseID($this->distribution_data["aprogress_id"], $response["iresponse_id"]);
                if ($progress_response) {
                    $response_selected = true;
                    if ($item["comment_type"] == "flagged" && $response["flag_response"]) {
                        $flag_selected = true;
                    }
                }
                $html .= "<option value=\"" . html_encode($response["iresponse_id"]) . "\"" . ($progress_response ? " selected=\"selected\"" : "") . "" . ($response["flag_response"] ? " data-response-flagged=\"true\"" : "") . ">" . html_encode($response["response_text"]) . "</option>";
            }
            if (!$response_selected && $item["comment_type"] == "flagged" && $flag_selected == false && $responses[0]["flag_response"]) {
                $flag_selected = true;
            }
            $html .= "      </select>
                       </td>
                </tr>";
        } else {
            $html .= "<tr><td>This item has no responses</td></tr>";
        }
        if (!$is_pdf) {
            $html .= "  <tr class=\"item-detail-view hide\">";
            $html .= "      <td>";
            $html .= "          <div class=\"item-details-container\">";
            $html .= "                  <h5>Item Code: <span>" . ($item["item_code"] ? html_encode($item["item_code"]) : "N/A") . "</span></h5>";
            $html .= "                  <h5>Item Tagged With</h5>";
            $html .= "                  <div class=\"item-tags\">";
            if ($tags) {
                foreach ($tags as $tag) {
                    $html .= "              <span><a href=\"#\">" . html_encode($tag["tag"]) . "</a></span>";
                }
            } else {
                $html .= "<p>This item has no tags.</p>";
            }
            $html .= "                  </div>";
            $html .= "                  <ul>";
            switch ($item["comment_type"]) {
                case "optional" :
                    $comment_type = "optional";
                    break;
                case "mandatory" :
                    $comment_type = "mandatory";
                    break;
                case "flagged" :
                    $comment_type = "mandatory for flagged responses";
                    break;
                case "disabled" :
                default :
                    $comment_type = "disabled";
                    break;
            }
            $html .= "                      <li><span>Comments</span> are " . $comment_type . " for this Item";
            $html .= "                      <li class=\"pull-right\"><span>Item was created on</span>: " . date("Y-m-d", $item["created_date"]) . "</li>";
            $html .= "                  </ul>";
            $html .= "          </div>";
            $html .= "     </td>";
            $html .= "  </tr>";
            if ($display_mode) {
                $html .= $this->buildItemComments($item, $count, $flag_selected, $disabled);
            }
        }
        $html .= "</table>";
        $html .= "</div>";
        return $html;
    }

    public function renderDropdownMultipleResponse ($item = null, $display_mode = false, $control_array = NULL, $disabled = false, $is_pdf = false) {
        $deleted_distribution = Models_Assessments_Distribution::fetchRowByID($this->distribution_data["adistribution_id"], time());
        $html = "";
        $html .= "<div class=\"item-container\" data-item-id=\"".html_encode($item["item_id"])."\" data-comment-type=\"" . html_encode($item["comment_type"]) . "\">";
        $responses = $this->fetchItemResponses($item["item_id"]);
        $tags = $this->fetchItemTags($item["item_id"]);
        $progress = Models_Assessments_Progress::fetchRowByID($this->distribution_data["aprogress_id"]);
        $flag_selected = false;

        if ($responses) {
            $count = 1;
            $html .= $this->buildHeader($item, $count, $display_mode, $control_array);

            $html .= "<tr class=\"item-response-view\">
                        <td class=\"item-type-control\">";
            $html .= "     <select " . (($progress && $progress->getProgressValue() != "inprogress") || $deleted_distribution || $disabled ? "disabled" : "") . " data-item-id=\"".html_encode($item["item_id"])."\" name=\"item-" . html_encode($item["item_id"]) . "[]\" class=\"form-control item-control\" multiple size=\"10\">";
            foreach ($responses as $response) {
                $progress_response = Models_Assessments_Progress_Response::fetchRowByAprogressIDIresponseID($this->distribution_data["aprogress_id"], $response["iresponse_id"]);
                if ($progress_response && $item["comment_type"] == "flagged" && $response["flag_response"]) {
                    $flag_selected = true;
                }
                $html .= " <option value=\"". $response["iresponse_id"] ."\"" . ($progress_response ? " selected=\"selected\"" : "") . "" . ($response["flag_response"] ? " data-response-flagged=\"true\"" : "") . ">". $response["response_text"] ."</option>";
            }
            $html .= "     </select>
                      </td>
                   </tr>";
        }
        if (!$is_pdf) {
            $html .= "  <tr class=\"item-detail-view hide\">";
            $html .= "      <td>";
            $html .= "          <div class=\"item-details-container\">";
            $html .= "                  <h5>Item Code: <span>" . ($item["item_code"] ? html_encode($item["item_code"]) : "N/A") . "</span></h5>";
            $html .= "                  <h5>Item Tagged With</h5>";
            $html .= "                  <div class=\"item-tags\">";
            if ($tags) {
                foreach ($tags as $tag) {
                    $html .= "              <span><a href=\"#\">" . html_encode($tag["tag"]) . "</a></span>";
                }
            } else {
                $html .= "<p>This item has no tags.</p>";
            }
            $html .= "                  </div>";
            $html .= "                  <ul>";
            switch ($item["comment_type"]) {
                case "optional" :
                    $comment_type = "optional";
                    break;
                case "mandatory" :
                    $comment_type = "mandatory";
                    break;
                case "flagged" :
                    $comment_type = "mandatory for flagged responses";
                    break;
                case "disabled" :
                default :
                    $comment_type = "disabled";
                    break;
            }
            $html .= "                      <li><span>Comments</span> are " . $comment_type . " for this Item";
            $html .= "                      <li class=\"pull-right\"><span>Item was created on</span>: " . date("Y-m-d", $item["created_date"]) . "</li>";
            $html .= "                  </ul>";
            $html .= "          </div>";
            $html .= "     </td>";
            $html .= "  </tr>";
            if ($display_mode) {
                $html .= $this->buildItemComments($item, $count, $flag_selected, $disabled);
            }
        }
        $html .= "</table>";
        $html .= "</div>";
        return $html;
    }

    public function renderFreeTextComments ($item = null, $display_mode = false, $control_array = NULL, $disabled = false, $is_pdf = false) {
        $deleted_distribution = Models_Assessments_Distribution::fetchRowByID($this->distribution_data["adistribution_id"], time());
        $html = "<div class=\"assessment-horizontal-choice-item item-container\" data-item-id=\"".html_encode($item["item_id"])."\" data-comment-type=\"" . html_encode($item["comment_type"]) . "\">";
        $tags = $this->fetchItemTags($item["item_id"]);

        $afelement = Models_Assessments_Form_Element::fetchRowByElementIDFormIDElementType($item["item_id"], $this->distribution_data["form_id"], "item");
        $progress = Models_Assessments_Progress::fetchRowByID($this->distribution_data["aprogress_id"]);
        if ($afelement) {
            $progress_response = Models_Assessments_Progress_Response::fetchRowByAprogressIDAfelementID($this->distribution_data["aprogress_id"], $afelement->getID());
        }

        $count = 1;
        $html .= $this->buildHeader($item, $count, $display_mode, $control_array);

        $html .= "  <tr class=\"response-label item-response-view\">";
        $html .= "      <td class=\"item-type-control\" colspan=\"". $count ."\">";
        if (!$is_pdf) {
            $html .= "          <textarea class=\"expandable\" ".(($progress && $progress->getProgressValue() != "inprogress") || $deleted_distribution || $disabled ? "disabled" : "")." id=\"item-" . html_encode($item["item_id"]) . "\" name=\"item-" . html_encode($item["item_id"]) . "\" ". ($afelement ? "data-afelement-id=\"" . html_encode($afelement->getID()) . "\"" : "") . ">".(isset($progress_response) && $progress_response ? $progress_response->getComments() : "")."</textarea>";
        } else {
            $html .= (isset($progress_response) && $progress_response ? "<p style='text-align:left'>" . nl2br($progress_response->getComments()) . "</p>" : "");
        }
        $html .= "      </td>";
        $html .= "  </tr>";

        if (!$is_pdf) {
            $html .= "  <tr class=\"item-detail-view hide\">";
            $html .= "      <td colspan=\"" . $count . "\">";
            $html .= "          <div class=\"item-details-container\">";
            $html .= "                  <h5>Item Code: <span>" . ($item["item_code"] ? html_encode($item["item_code"]) : "N/A") . "</span></h5>";
            $html .= "                  <h5>Item Tagged With</h5>";
            $html .= "                  <div class=\"item-tags\">";
            if ($tags) {
                foreach ($tags as $tag) {
                    $html .= "              <span><a href=\"#\">" . html_encode($tag["tag"]) . "</a></span>";
                }
            } else {
                $html .= "<p>This item has no tags.</p>";
            }
            $html .= "                  </div>";
            $html .= "                  <ul>";
            switch ($item["comment_type"]) {
                case "optional" :
                    $comment_type = "optional";
                    break;
                case "mandatory" :
                    $comment_type = "mandatory";
                    break;
                case "flagged" :
                    $comment_type = "mandatory for flagged responses";
                    break;
                case "disabled" :
                default :
                    $comment_type = "disabled";
                    break;
            }
            $html .= "                      <li><span>Comments</span> are " . $comment_type . " for this Item";
            $html .= "                      <li class=\"pull-right\"><span>Item was created on</span>: " . date("Y-m-d", $item["created_date"]) . "</li>";
            $html .= "                  </ul>";
            $html .= "          </div>";
            $html .= "     </td>";
            $html .= "  </tr>";

            if ($display_mode) {
                $html .= $this->buildItemComments($item, $count, false, $disabled);
            }
        }
        $html .= "</table>";
        $html .= "</div>";
        return $html;
    }

    public function renderDateSelector ($item = null, $display_mode = false, $control_array = NULL, $disabled = false, $is_pdf = false) {
        $deleted_distribution = Models_Assessments_Distribution::fetchRowByID($this->distribution_data["adistribution_id"], time());
        $html = "<div class=\"assessment-horizontal-choice-item item-container\" data-item-id=\"".html_encode($item["item_id"])."\">";
        $tags = $this->fetchItemTags($item["item_id"]);

        $afelement = Models_Assessments_Form_Element::fetchRowByElementIDFormIDElementType($item["item_id"], $this->distribution_data["form_id"], "item");
        $progress = Models_Assessments_Progress::fetchRowByID($this->distribution_data["aprogress_id"]);
        if ($afelement) {
            $progress_response = Models_Assessments_Progress_Response::fetchRowByAprogressIDAfelementID($this->distribution_data["aprogress_id"], $afelement->getID());
        }

        $count = 1;
        $html .= $this->buildHeader($item, $count, $display_mode, $control_array);

        $html .= "  <tr class=\"response-label item-response-view\">";
        $html .= "      <td class=\"item-type-control\">";
        $html .= "      <div class=\"input-append\">";
        $html .= "          <input type=\"text\" class=\"datepicker item-control input-large\"". (($progress && $progress->getProgressValue() != "inprogress") || $deleted_distribution || $disabled ? " disabled" : "") ." name=\"item-". html_encode($item["item_id"]) ."\"" .(isset($progress_response) && $progress_response ? " value=\"". date("Y-m-d", $progress_response->getComments())  ."\"" : ""). "\" />";
        $html .= "          <span class=\"add-on datepicker-icon\"><i class=\"icon-calendar\"></i></span>";
        $html .= "      </div>";
        $html .= "      </td>";
        $html .= "  </tr>";
        if (!$is_pdf) {
            $html .= "  <tr class=\"item-detail-view hide\">";
            $html .= "      <td colspan=\"" . $count . "\">";
            $html .= "          <div class=\"item-details-container\">";
            $html .= "                  <h5>Item Code: <span>" . ($item["item_code"] ? html_encode($item["item_code"]) : "N/A") . "</span></h5>";
            $html .= "                  <h5>Item Tagged With</h5>";
            $html .= "                  <div class=\"item-tags\">";
            if ($tags) {
                foreach ($tags as $tag) {
                    $html .= "              <span><a href=\"#\">" . html_encode($tag["tag"]) . "</a></span>";
                }
            } else {
                $html .= "<p>This item has no tags.</p>";
            }
            $html .= "                  </div>";
            $html .= "                  <ul>";
            switch ($item["comment_type"]) {
                case "optional" :
                    $comment_type = "optional";
                    break;
                case "mandatory" :
                    $comment_type = "mandatory";
                    break;
                case "flagged" :
                    $comment_type = "mandatory for flagged responses";
                    break;
                case "disabled" :
                default :
                    $comment_type = "disabled";
                    break;
            }
            $html .= "                      <li><span>Comments</span> are " . $comment_type . " for this Item";
            $html .= "                      <li class=\"pull-right\"><span>Item was created on</span>: " . date("Y-m-d", $item["created_date"]) . "</li>";
            $html .= "                  </ul>";
            $html .= "          </div>";
            $html .= "     </td>";
            $html .= "  </tr>";
        }
        $html .= "</table>";
        $html .= "</div>";
        return $html;
    }

    public function renderUserSelector() {
        return "<p>PLACEHOLDER</p>";
    }

    public function renderNumericField ($item = null, $display_mode = false, $control_array = NULL, $disabled = false, $is_pdf = false) {
        $deleted_distribution = Models_Assessments_Distribution::fetchRowByID($this->distribution_data["adistribution_id"], time());
        $afelement = Models_Assessments_Form_Element::fetchRowByElementIDFormIDElementType($item["item_id"], $this->distribution_data["form_id"], "item");
        $progress = Models_Assessments_Progress::fetchRowByID($this->distribution_data["aprogress_id"]);
        if ($afelement) {
            $progress_response = Models_Assessments_Progress_Response::fetchRowByAprogressIDAfelementID($this->distribution_data["aprogress_id"], $afelement->getID());
        }

        $html = "<div class=\"item-container\" data-item-id=\"".html_encode($item["item_id"])."\">";
        $tags = $this->fetchItemTags($item["item_id"]);

        $count = 1;
        $html .= $this->buildHeader($item, $count, $display_mode, $control_array);

        $html .= "  <tr class=\"item-response-view\">";
        $html .= "      <td class=\"item-type-control\">";
        $html .= "          <input" . (($progress && $progress->getProgressValue() != "inprogress") || $deleted_distribution || $disabled ? " disabled" : "") . " id=\"item-" . html_encode($item["item_id"]) . "\" name=\"item-" . html_encode($item["item_id"]) . "\" type=\"text\" class=\"form-control item-control input-small\" value=\"".(isset($progress_response) && $progress_response  ? $progress_response->getComments() : "")."\" />";
        $html .= "      </td>";
        $html .= "  </tr>";
        if (!$is_pdf) {
            $html .= "  <tr class=\"item-detail-view hide\">";
            $html .= "      <td>";
            $html .= "          <div class=\"item-details-container\">";
            $html .= "                  <h5>Item Code: <span>" . ($item["item_code"] ? html_encode($item["item_code"]) : "N/A") . "</span></h5>";
            $html .= "                  <h5>Item Tagged With</h5>";
            $html .= "                  <div class=\"item-tags\">";
            if ($tags) {
                foreach ($tags as $tag) {
                    $html .= "              <span><a href=\"#\">" . html_encode($tag["tag"]) . "</a></span>";
                }
            } else {
                $html .= "<p>This item has no tags.</p>";
            }
            $html .= "                  </div>";
            $html .= "                  <ul>";
            switch ($item["comment_type"]) {
                case "optional" :
                    $comment_type = "optional";
                    break;
                case "mandatory" :
                    $comment_type = "mandatory";
                    break;
                case "flagged" :
                    $comment_type = "mandatory for flagged responses";
                    break;
                case "disabled" :
                default :
                    $comment_type = "disabled";
                    break;
            }
            $html .= "                      <li><span>Comments</span> are " . $comment_type . " for this Item";
            $html .= "                      <li class=\"pull-right\"><span>Item was created on</span>: " . date("Y-m-d", $item["created_date"]) . "</li>";
            $html .= "                  </ul>";
            $html .= "          </div>";
            $html .= "     </td>";
            $html .= "  </tr>";
            if ($display_mode) {
                $html .= $this->buildItemComments($item, $count, false, $disabled);
            }
        }
        $html .= "</table>";
        $html .= "</div>";
        return $html;
    }

    public function buildItemComments ($item = null, $count, $flag_selected = false, $disabled = false) {
        global $translate;
        $html = "";

        if (isset($item) && $item) {
            $afelement = Models_Assessments_Form_Element::fetchRowByElementIDFormIDElementType($item["item_id"], $this->distribution_data["form_id"], "item");
            $progress = Models_Assessments_Progress::fetchRowByID($this->distribution_data["aprogress_id"]);
            if ($afelement) {
                $progress_response = Models_Assessments_Progress_Response::fetchRowByAprogressIDAfelementID($this->distribution_data["aprogress_id"], $afelement->getID());
            }
            $deleted_distribution = Models_Assessments_Distribution::fetchRowByID($this->distribution_data["adistribution_id"], time());

            $html .= "<tr class=\"heading" . ($item["comment_type"] == "disabled" || ($item["comment_type"] == "flagged" && !$flag_selected) ? " hide" : "") . "\" id=\"item-" .html_encode($item["item_id"]). "-comments-header\">";
            $html .= "  <td colspan=\"" . $count . "\">";
            $html .= "        <h3>" . $translate->_("Comments" . ($item["comment_type"] == "mandatory" || $item["comment_type"] == "flagged" ? " (Mandatory)" : "")) . "</h3>";
            $html .= "    </td>";
            $html .= "</tr>";
            $html .= "<tr id=\"item-" . html_encode($item["item_id"]) . "-comments-block\" class=\"item-response-view item-comment" . ($item["comment_type"] == "disabled" || ($item["comment_type"] == "flagged" && !$flag_selected) ? " hide" : "") . "\">";
            $html .= "  <td class=\"item-type-control\" colspan=\"" . $count . "\">";
            $html .= "      <textarea class=\"expandable\" " . (($progress && $progress->getProgressValue() != "inprogress") || $deleted_distribution || $disabled ? "disabled" : "") . " id=\"item-" . html_encode($item["item_id"]) . "-comments\" name=\"item-" . html_encode($item["item_id"]) . "-comments\" " . ($afelement ? "data-afelement-id=\"" . html_encode($afelement->getID()) . "\"" : "") . ">" . (isset($progress_response) && $progress_response ? html_encode($progress_response->getComments()) : "") . "</textarea>";
            $html .= "    </td>";
            $html .= "</tr>";
        }

        return $html;
    }

    public function render($display_mode = false, $control_array = NULL, $distribution_data = NULL, $disabled = NULL, $is_pdf = false) {
        global $translate;
        $MODULE_TEXT = $translate->_("assessments");
        $disable = (isset($disabled) ? $disabled : false);
        $this->distribution_data = $distribution_data;
        if (!empty($this->view_data)) {
            foreach ($this->view_data as $view_data) {
                switch ($view_data["itemtype_id"]) {
                    case 1 :
                        return $this->renderHorizontalChoiceSingleResponse ($view_data, $display_mode, $control_array, $disable, $is_pdf);
                        break;
                    case 2 :
                        return $this->renderVerticalChoiceSingleResponse ($view_data, $display_mode, $control_array, $disable, $is_pdf);
                        break;
                    case 3 :
                        return $this->renderDropdownSingleResponse ($view_data, $display_mode, $control_array, $disable, $is_pdf);
                        break;
                    case 4 :
                        return $this->renderHorizontalChoiceMultipleResponse ($view_data, $display_mode, $control_array, $disable, $is_pdf);
                        break;
                    case 5 :
                        return $this->renderVerticalChoiceMultipleResponse ($view_data, $display_mode, $control_array, $disable, $is_pdf);
                        break;
                    case 6 :
                        return $this->renderDropdownMultipleResponse ($view_data, $display_mode, $control_array, $disable, $is_pdf);
                        break;
                    case 7 :
                        return $this->renderFreeTextComments ($view_data, $display_mode, $control_array, $disable, $is_pdf);
                        break;
                    case 8 :
                        return $this->renderDateSelector ($view_data, $display_mode, $control_array, $disable, $is_pdf);
                        break;
                    case 9 :
                        return $this->renderUserSelector ();
                        break;
                    case 10 :
                        return $this->renderNumericField ($view_data, $display_mode, $control_array, $disable, $is_pdf);
                        break;
                    case 12 :
                        return $this->renderHorizontalChoiceSingleResponse ($view_data, $display_mode, $control_array, $disable, $is_pdf);
                        break;
                }
            }
        } else {
            echo display_notice($MODULE_TEXT["forms"]["add-element"]["no_available_items"]);
        }
    }

    public function renderItem ($form_id = 0) {
        global $ENTRADA_USER;
        $items = $this->fetchItemsByAuthor($ENTRADA_USER->getID(), $form_id);
        if ($items) {
            foreach ($items as $item) {
                $this->render();
            }
        }
    }
}