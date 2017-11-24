<?php
/**
 * @author Organisation: Queen's University
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @author Don Zuiker <don.zuiker@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Views_Deprecated_Rubric extends Views_Deprecated_Base {
    protected $default_fieldset = array(    "rubric_id",
                                            "one45_element_id",
                                            "rubric_title",
                                            "rubric_description",
                                            "updated_date",
                                            "updated_by",
                                            "created_date",
                                            "created_by",
                                            "deleted_date");

    protected $table_name               = "cbl_assessments_lu_rubrics";
    protected $primary_key              = "rubric_id";
    protected $default_sort_column      = "`cbl_assessment_rubric_items`.`order`";
    protected $joinable_tables          = array(
        "cbl_assessment_rubric_items" => array(
            "fields" => array(
                "aritem_id" => "aritem_id",
                "arrubric_id" => "rubric_id",
                "ritem_id" => "item_id",
                "order" => "order",
                "enable_flagging" => "enable_flagging",
                "deleted_date" => "deleted_date"
            ),
            "join_conditions" => "`cbl_assessment_rubric_items`.`rubric_id` = `cbl_assessments_lu_rubrics`.`rubric_id`",
            "left" => true
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
    );
    protected $view_data = array();
    protected $distribution_data = array();
    protected $response_category_editable = false;

    public static function fetchRubricByID($rubric_id, $response_category_editable = false) {
        $self = new self();

        $fieldset = $self->default_fieldset;
        $fieldset[] = "ritem_id";
        $fieldset[] = "aritem_id";
        $self->setFields($fieldset);
        $constraints = array(
                        array("key" => "`".DATABASE_NAME."`.`cbl_assessment_rubric_items`.`rubric_id`", "value" => $rubric_id),
                        array("key" => "`".DATABASE_NAME."`.`cbl_assessment_rubric_items`.`deleted_date`", "value" => NULL, "method" => "IS"));

        $self->response_category_editable = $response_category_editable;
        $self->view_data = $self->fetchAll($constraints);
        return $self;
    }

    public function fetchItemTags($item_id) {
        $fieldset[] = "tag_id";
        $fieldset[] = "tag";
        $this->setFields($fieldset);
        $constraints = array(array("key" => "`".DATABASE_NAME."`.`cbl_assessment_item_tags`.`item_id`", "value" => $item_id));

        return $this->fetchAll($constraints);
    }

    public function renderRubric ($rubric_items = null, $display_mode = false, $control_array, $disabled = false) {
        global $translate, $ENTRADA_USER;
        
        $MODULE_TEXT = $translate->_("assessments");



        $rubric_id = $rubric_items[0]["rubric_id"];
        $html = "";
        $html .= "<div class=\"assessment-horizontal-choice-rubric rubric-container\" data-item-id=\"".$rubric_id."\">";
        $html .= "<div id=\"rubric-error-msg\"></div>";
        $html .= "<div id=\"table-responsive\">";
        $html .= "<table class=\"table table-bordered table-striped rubric-table\">";
        $html .= "<thead>";
        if ($rubric_items) {
            //Take the first item in this rubric and get the descriptor labels.
            $item_responses = Models_Assessments_Item_Response::fetchAllRecordsByItemID($rubric_items[0]["ritem_id"]);
            if ($display_mode === false && isset($control_array) && $control_array) {
                $html .= "<tr class=\"rubric-controls\">";
                $html .= "<th colspan=\"".(count($item_responses)+1)."\"><div class=\"pull-right\">";
                foreach ($control_array as $control_group) {
                    foreach ($control_group as $control) {
                        $html .= $control;
                    }
                }
                $html .= "</div></th></tr>";
            }

            if ($display_mode === true || (isset($control_array) && !empty($control_array))) {
                if (!is_null($rubric_items[0]["rubric_title"])) {
                    $html .= "<tr class=\"rubric-title\">";
                    $html .= "<th colspan=\"".(count($item_responses)+1)."\"><div class=\"pull-left\">";
                    $html .= "<h2>".html_encode($rubric_items[0]["rubric_title"])."</h2>";
                    $html .= "</div></th></tr>";
                }

                if ($rubric_items[0]["rubric_description"]) {
                    $html .= "<tr><th></th>";
                    $html .= "<tr class=\"rubric-description\">";
                    $html .= "<th colspan=\"".(count($item_responses)+1)."\"><div class=\"pull-left\">";
                    $html .= "<p>".html_encode($rubric_items[0]["rubric_description"])."</p>";
                    $html .= "</div></th></tr>";
                }
            }
            if ($item_responses) {
                $html .= "<tr><th></th>";
                foreach($item_responses as $item) {
                    $response_descriptor = Models_Assessments_Response_Descriptor::fetchRowByIDIgnoreDeletedDate($item->getArdescriptorID());
                    $descriptor_id = "";
                    $descriptor_text = "";
                    if ($response_descriptor) {
                        $descriptor_id = $response_descriptor->getID();
                        $descriptor_text = $response_descriptor->getDescriptor();
                    }
                    $html .= "<th class=\"label-cell " . ($this->response_category_editable === true ? "category-editable" : "" ). "\"><h3>".$descriptor_text."</h3>";

                    if ($this->response_category_editable === true) {

                        $response_descriptors = Models_Assessments_Response_Descriptor::fetchAllByOrganisationIDSystemType($ENTRADA_USER->getActiveOrganisation(), "entrada");
                        if ($response_descriptors) {
                            $html .= "<select class=\"descriptor-select\">";
                                foreach ($response_descriptors as $descriptor) {
                                $html .= "  <option value=\"" . $descriptor->getID() ."\" " . ($descriptor_id == $descriptor->getID() ? "selected=\"selected\"" : "") . ">". $descriptor->getDescriptor() . "</option>";
                                }
                            $html .= "</select>";
                        }

                        $html .= "<br/><a class=\"btn btn-mini btn-category-remove hide\">Cancel</a>";
                        $html .= "<a class=\"btn btn-mini btn-category-ok hide\" data-descriptor-id=\"" .$descriptor_id. "\" data-rubric-id=\"" .$rubric_id. "\">Save</a>";
                        $html .= "<img src=\"". ENTRADA_URL ."/images/loading.gif\"/ class=\"category-loading hide\">";
                        $html .= "<i class=\"icon-pencil icon-category-pencil hide\"></i>";
                    }

                    $html .= "</th>";
                }
                $html .= "</tr>";
            }
            $html .= "</thead>";
            $html .= "<tbody>";
            $row_count = 1;
            foreach($rubric_items as $ritem) {
                $row_count++;
                if (!$ritem["deleted_date"]) {
                    $item = Models_Assessments_Item::fetchRowByIDIncludeDeleted($ritem["ritem_id"]);
                    $item = $item->toArray();

                    $objectives = Models_Assessments_Item::fetchItemObjectives($item["item_id"]);

                    $responses = Models_Assessments_Item_Response::fetchAllRecordsByItemID($item["item_id"]);
                    $count = count($responses);

                    $rubric_label = Models_Assessments_Rubric_Label::fetchRowByRubricIDItemID($rubric_id, $item["item_id"]);
                    $rubric_label_text = "&nbsp;";
                    $rlabel_id = "";
                    if ($rubric_label) {
                        $rubric_label_text = $rubric_label->getLabel();
                        $rlabel_id = $rubric_label->getID();
                    }

                    $html .= "<tbody class=\"sortable-rubric-item ui-draggable\" data-comment-type=\"".$item["comment_type"]."\" data-aritem-id=\"".$ritem["aritem_id"]."\">";
                    if ($responses) {
                        $column_width = "";
                        if ($ritem["one45_element_id"]) {
                            $column_width = ((100 - 30) / $count) . "%";
                        }
                        if ($display_mode === false  && (!isset($control_array) || !$control_array)) {
                            $html .=    "<tr class=\"rubric-controls\">";
                            $html .=    "<td colspan=\"". ($count + 1) ."\">
                                            <div class=\"btn-group rubric-actions pull-right\">
                                                <a href=\"".ENTRADA_URL."/admin/assessments/items?section=edit-item&element_type=rubric&id=".$ritem["ritem_id"]."&rubric_id=".$ritem["rubric_id"]."\" class=\"btn edit-rubric-item\"><i class=\"icon-pencil\"></i></a>
                                                <a href=\"#delete-rubric-item-modal\" data-toggle=\"modal\" class=\"btn delete-rubric-item\"><i class=\"icon-trash\"></i></a>
                                                <a href=\"#\" class=\"btn move-rubric-item\"><i class=\"icon-move\"></i></a>
                                            </div>
                                        </td>";
                            $html .=    "</tr>";
                        }

                        $html .= "  <tr class=\"rubric-response-input item-response-view\" data-aritem-id=\"".$ritem["aritem_id"]."\">";
                        $html .= "  <td width=\"30%\"><div class=\"rubric-item-text\">".html_encode($item["item_text"])."</div>";
                        if (is_array($objectives) && count($objectives)) {
                            $deprecated_view = new Views_Deprecated_Item();
                            $html .= "<br />". $deprecated_view->renderCurriculumTags($objectives);
                        }
                        if ($display_mode === false && (!isset($control_array) || !$control_array)) {
                            $html .= "<br /><span class=\"edit-rubric-label\" id=\"".$rlabel_id."\" data-item-id=\"".$item["item_id"]."\">".$rubric_label_text."</span><a class=\"edit-rubric-label-link\" data-item-id=\"".$item["item_id"]."\"><i class=\"icon-edit\"></a>";
                        }
                        $html .= "</td>";
                        $column_count = 1;
                        $cell_class = "rubric-response-no-text";
                        foreach ($responses as $response) {
                            if ($response->getText()) {
                                $cell_class = "rubric-response";
                                break;
                            }
                        }

                        $progress_response_comment = "";

                        $show = true;
                        foreach ($responses as $response) {
                            $progress = Models_Assessments_Progress::fetchRowByID($this->distribution_data["aprogress_id"]);
                            $progress_response = Models_Assessments_Progress_Response::fetchRowByAprogressIDIresponseID($this->distribution_data["aprogress_id"], $response->getIresponseID());

                            if (is_object($progress_response) && $progress_response->getComments()) {
                                $progress_response_comment = $progress_response->getComments();
                            }

                            if ($display_mode === false || $disabled) {
                                $disabled = "DISABLED";
                            }

                            $deleted_distribution = Models_Assessments_Distribution::fetchRowByID($this->distribution_data["adistribution_id"], time());
                            if ($item["comment_type"] == "flagged" && $response->getFlagResponse() && !$progress_response) {
                                $show = false;
                            }

                            $html .= "  <td class=\"".$cell_class."\" width=\"".$column_width."\">
                                            <label for=\"response-" . html_encode($response->getID()) . "\">
                                                <input " . $disabled . " type=\"radio\" class=\"item-control\" ".(($progress && $progress->getProgressValue() != "inprogress") || $deleted_distribution ? "DISABLED" : "")." name=\"rubric-item-" . html_encode($rubric_id) . "-" . html_encode($item["item_id"])."\" id=\"response-" . html_encode($response->getID()) ."\" data-item-id=\"" . html_encode($item["item_id"]) . "\" data-iresponse-id=\"" . html_encode($response->getID()) . "\" value=\"" . html_encode($response->getID()) . "\"" . ($progress_response ? "checked=\"checked\"" : "") . ($response->getFlagResponse() ? "data-response-flagged=true" : "") . " />";
                            $html .= "          <div class=\"rubric-response-text\">".
                                                    nl2br($response->getText())."
                                                </div>
                                            </label>";
                            $html .=    "</td>";
                            $column_count++;
                        }

                        if ($item["comment_type"] !== "disabled") {
                            $html .= "<tr class=\"item-response-view rubric-comment " . ($show ? "" :  "hide") . "\" id=\"rubric-item-$rubric_id-" . html_encode($item["item_id"]) . "-comments-block\"".">
                                          <td></td>
                                          <td colspan=\"".$column_count."\">
                                                <div>Comment</div>
                                                <textarea " . (($progress && $progress->getProgressValue() != "inprogress") || $deleted_distribution || $disabled ? "DISABLED" : ""). " name=\"item-" . html_encode($item["item_id"])."-comments\" id=\"item-" . html_encode($item["item_id"])."-comments\" class=\"span11 expandable\">". html_encode($progress_response_comment) ."</textarea>
                                          </td>";
                            $html .= "</tr>";
                        }

                        $html .= "  </tr>";
                    }
                }
                $html .= "</tbody>";
            }
            $html .= "</tbody>";
        }
        $html .= "</table>";
        $html .= "</div>";
        $html .= "</div>";
        return $html;
    }

    public function render($display_mode = false, $control_array = false, $distribution_data = false, $disabled = false) {
        global $translate;
        $MODULE_TEXT = $translate->_("assessments");
        $this->distribution_data = $distribution_data;

        if (!empty($this->view_data)) {
            return $this->renderRubric($this->view_data, $display_mode, $control_array, $disabled);
        } else {
            echo display_notice($MODULE_TEXT["rubrics"]["rubric"]["no_available_items"]);
        }
    }
}
