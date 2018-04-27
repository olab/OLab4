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

class Views_Deprecated_Form extends Views_Deprecated_Base {
    protected $default_fieldset = array(
        "form_id", "one45_form_id", "organisation_id", "title", "description", "created_date", "created_by", "updated_date", "updated_by", "deleted_date",
    );

    protected $table_name               = "cbl_assessments_lu_forms";
    protected $primary_key              = "form_id";
    protected $default_sort_column      = "`cbl_assessments_lu_forms`.`cbl_assessments_lu_forms`";
    protected $joinable_tables          = array(
        
    );

    public static function renderFormElements($form_id, $display_mode = false, $distribution_data = false, $public = false, $objectives = array(), $disabled = null, $echo = true, $is_pdf = false, $hide_from_approver = false) {
        global $translate, $ENTRADA_USER;
        $MODULE_TEXT = $translate->_("assessments");
        $form_elements = Models_Assessments_Form_Element::fetchAllRecords($form_id);
        $distribution = Models_Assessments_Distribution::fetchRowByID($distribution_data["adistribution_id"]);

        if ($form_elements) {

            $i = 0;
            $rubrics = array();
            $html = array();
            foreach ($form_elements as $form_element) {

                switch ($form_element->getElementType()) {
                    case "item" :
                        $rubric_id = $form_element->getRubricID();
                        if (!isset($rubric_id) || !$rubric_id) {
                            $html[] = "<div class=\"form-item\" data-afelement-id=\"" . html_encode($form_element->getID()) . "\">\n";
                            $control_array = array(
                                array(
                                    "<span class=\"btn\"><input type=\"checkbox\" value=\"" . html_encode($form_element->getID()) . "\" name=\"delete[]\" class=\"delete\"></span>"
                                ),
                                array(
                                    "<a class=\"btn edit-item\" title=\"Edit Item\" href=\"" . ENTRADA_URL . "/admin/assessments/items?section=edit-item&element_type=form&id=" . html_encode($form_element->getElementID()) . "&form_id=" . $form_element->getFormID() . "\"><i class=\"icon-pencil\"></i></a>",
                                    "<a class=\"btn item-details\" title=\"View Item Details\" href=\"" . ENTRADA_URL . "/admin/assessments/items?section=edit-item&id=" . html_encode($form_element->getElementID()) . "\"><i class=\"icon-eye-open\"></i></a>",
                                    "<a class=\"btn move\" title=\"Move\" href=\"#\"><i class=\"icon-move\"></i></a>"
                                )
                            );
                            $item_view = Views_Deprecated_Item::fetchItemByID($form_element->getElementID());
                            $html[] = $item_view->render($display_mode, $control_array, $distribution_data, (isset($disabled) ? $disabled : false), $is_pdf);
                            $html[] = "</div>";
                        } elseif (isset($rubric_id) && $rubric_id && !in_array($rubric_id, $rubrics)) {
                            $html[] = "<div class=\"form-item\" data-afelement-id=\"" . html_encode($form_element->getID()) . "\">\n";
                            $control_array = array(
                                array(
                                    "<span class=\"btn\"><input type=\"checkbox\" value=\"" . html_encode($form_element->getID()) . "\" name=\"delete[]\" class=\"delete\"></span>"
                                ),
                                array(
                                    "<a class=\"btn edit-item\" title=\"Edit Item\" href=\"" . ENTRADA_URL . "/admin/assessments/rubrics?section=edit-rubric&rubric_id=" . html_encode($form_element->getRubricID()) . "&form_id=" . html_encode($form_element->getFormID()) . "\"><i class=\"icon-pencil\"></i></a>",
                                    "<a class=\"btn move\" title=\"Move\" href=\"#\"><i class=\"icon-move\"></i></a>"
                                )
                            );
                            $rubrics[] = $rubric_id;
                            $item_view = Views_Deprecated_Rubric::fetchRubricByID($rubric_id);
                            $html[] = $item_view->render($display_mode, $control_array, $distribution_data, (isset($disabled) ? $disabled : false));
                            $html[] = "</div>";
                        }

                    break;
                    case "text" :
                        $element_text = $form_element->getElementText();
                        if (!$display_mode) {
                            $html[] = "<div class=\"form-item\" data-afelement-id=\"". html_encode($form_element->getAfelementID()) ."\">";
                            $html[] = "    <div class=\"item-container\">";
                            $html[] = "        <table class=\"item-table\">";
                            $html[] = "            <tr class=\"type\">";
                            $html[] = "                <td>
                                                        <span class=\"item-type\">Free Text</span>
                                                        <div class=\"pull-right\">
                                                            <div class=\"btn-group\">
                                                                <a href=\"#\" class=\"btn save-element\" data-text-element-id=\"".$form_element->getID()."\">Save</a>
                                                                <span href=\"#\" class=\"btn\"><input type=\"checkbox\" class=\"delete\" name=\"delete[]\" value=\"" . $form_element->getID() . "\"/></span>
                                                                <a href=\"#\" title=\"Attach to Form\" class=\"btn move\"><i class=\"icon-move\"></i></a>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>";
                            $html[] = "<tr><td style=\"padding:0px 10px\">";
                            $html[] = "<div class=\"row-fluid space-above space-below\"><textarea id=\"element-".html_encode($form_element->getID())."\" name=\"text-element[".html_encode($form_element->getID())."]\">".html_encode($element_text)."</textarea></div>";
                            $html[] = "</td></tr>";
                            $html[] = "</table></div></div>";
                        } else {
                            $html[] = "<div class=\"form-text-container\">";
                            $html[] =    $element_text;
                            $html[] = "</div>";
                        }
                    break;
                    case "objective" :
                        if ($public) {
                            $objective = Models_Objective::fetchRow($form_element->getElementID(), $active = 1);
                            
                            if ($objective) {
                                // If the user is logged in, use their active organisation to fetch objectives.
                                // If the user is not logged in, assume that they are an external assessor which will have the organisation id passed in the distribution_data.
                                if ((isset($_SESSION["isAuthorized"]) && ($_SESSION["isAuthorized"]))) {
                                    $organisation_id = $ENTRADA_USER->getActiveOrganisation();
                                } else {
                                    $organisation_id = $distribution_data["organisation_id"];
                                }
                                $objective_children = Models_Objective::fetchAllByParentID(8, $objective->getID(), $active = 1);

                                $html[] = "<div class=\"item-container\">";
                                $html[] =    "<table class=\"item-table\">";
                                $html[] =        "<tr class=\"heading\">";
                                $html[] =            "<td colspan=\"1\">";
                                $html[] =                "<h3>". ($objective ? html_encode($objective->getName()) : "") ."</h3>";
                                $html[] =            "</td>";
                                $html[] =        "</tr>";
                                $html[] =        "<tr class=\"item-response-view\">";
                                $html[] =            "<td colspan=\"1\" id=\"objective-cell-". html_encode($form_element->getID()) ."\">";
                                                    $selected_objective_id = 0;
                                                    if (is_array($objectives) && array_key_exists($form_element->getID(), $objectives)) {
                                                        $total_objectives = count($objectives[$form_element->getID()]);
                                                        $data_indent = ($total_objectives * 14);
                                                        $indent = 0;
                                $html[] =                    "<ul id=\"selected-objective-list-". html_encode($form_element->getID()) ."\" data-indent=\"". html_encode($data_indent - 14) ."\" class=\"assessment-objective-list selected-objective-list\">";
                                                            foreach ($objectives[$form_element->getID()] as $objective_id) {
                                                                
                                                                if ($selected_objective_id < $objective_id) {
                                                                    $selected_objective_id = $objective_id;
                                                                }
                                                                
                                                                $afelement_objective = Models_Objective::fetchRow($objective_id, $active = 1);
                                                                if ($afelement_objective) {
                                $html[] =                                "<li data-objective-name=\"". html_encode($afelement_objective->getName()) ."\" data-objective-id=\"". html_encode($afelement_objective->getID()) ."\" class=\"collapse-objective-". html_encode($form_element->getID()) ."\" style=\"padding-left: ". html_encode(($indent)) ."px\">";
                                $html[] =                                    "<a href=\"#\" data-afelement-id=\"". html_encode($form_element->getID()) ."\" data-objective-name=\"". html_encode($afelement_objective->getName()) ."\" data-objective-id=\"". html_encode($afelement_objective->getID()) ."\" class=\"collapse-objective-btn\" >";
                                $html[] =                                        "<span class=\"assessment-objective-list-spinner hide\">&nbsp;</span>";
                                $html[] =                                        "<span class=\"ellipsis\">&bull;&bull;&bull;</span>";
                                $html[] =                                        "<span class=\"assessment-objective-name\">". html_encode($afelement_objective->getName()) ."</span>";
                                $html[] =                                    "</a>";
                                $html[] =                                "</li>";
                                                                }
                                                                $objective_children = Models_Objective::fetchAllByParentID($organisation_id,  $afelement_objective->getID(), $active = 1);
                                                                $indent += 14;
                                                            }
                                $html[] =                    "</ul>";
                                                    }

                                                    if ($objective_children) {
                                $html[] =                    "<ul id=\"objective-list-". html_encode($form_element->getID()) ."\" class=\"assessment-objective-list\">";                
                                                            foreach ($objective_children as $child_objective) {
                                $html[] =                            "<li><a href=\"#\" class=\"expand-objective-btn\" data-afelement-id=\"". html_encode($form_element->getID()) ."\" data-objective-name=\"". html_encode($child_objective->getName()) ."\" data-objective-id=\"". html_encode($child_objective->getID()) ."\"><span id=\"objective-spinner-". html_encode($child_objective->getID()) ."\" class=\"assessment-objective-list-spinner hide\">&nbsp;</span><span id=\"expand-objective-". html_encode($child_objective->getID()) ."\" class=\"plus-sign\">". html_encode("+") ."</span><span class=\"assessment-objective-name\">" . html_encode($child_objective->getName()) ."</span></a></li>";
                                                            }
                                $html[] =                    "</ul>";
                                                    } else {
                                                        $fieldnote_item = Models_Assessments_Item::fetchFieldNoteItem($selected_objective_id);
                                                        
                                                        if ($fieldnote_item) {
                                $html[] =                        "<div id=\"item-fieldnote-container-". html_encode($form_element->getID()) ."\" class=\"item-fieldnote-container\">";
                                $html[] =                            "<h3>". $fieldnote_item->getItemText() ."</h3>";
                                                                $item_responses = Models_Assessments_Item_Response::fetchAllRecordsByItemID($fieldnote_item->getID());
                                                                if ($item_responses) {
                                $html[] =                                "<div class=\"fieldnote-responses-container\">";
                                                                    foreach ($item_responses as $response) {
                                                                        $progress_response = Models_Assessments_Progress_Response::fetchRowByAprogressIDIresponseID($distribution_data["aprogress_id"], $response->getIresponseID());

                                $html[] =                                    "<div class=\"fieldnote-response-container\">";
                                                                            $response_descriptor = Models_Assessments_Response_Descriptor::fetchRowByIDIgnoreDeletedDate($response->getARDescriptorID());
                                                                            if ($response_descriptor) {
                                $html[] =                                            "<label class=\"radio\">";
                                $html[] =                                                "<input type=\"radio\" value=\"". html_encode($response->getID()) . "\" name=\"objective-" . html_encode($form_element->getElementID()) . "\" ". ($progress_response ? "checked=\"checked\"" : "") ." />";
                                $html[] =                                                html_encode($response_descriptor->getDescriptor());
                                $html[] =                                            "</label>";
                                                                            }
                                $html[] =                                        $response->getText();
                                $html[] =                                    "</div>";
                                                                    }
                                $html[] =                                "</div>";       
                                                                }
                                $html[] =                        "</div>";
                                                        }
                                                    }
                                $html[] =            "</td>";
                                $html[] =        "</tr>";
                                $html[] =    "</table>";
                                $html[] = "</div>";
                            }
                        } else {
                            $html[] = "<div class=\"item-container\">";
                            $html[] =    "<table class=\"item-table\">";
                            $html[] =        "<tr class=\"type\">";
                            $html[] =            "<td class=\"type\">";
                            $html[] =                "<span class=\"item-type\">" . $translate->_("Curriculum Tag Set") . "</span>";
                                                if (!$display_mode) {
                            $html[] =                    "<div class=\"pull-right\">";
                            $html[] =                        "<div class=\"btn-group\">";
                            $html[] =                            "<a class=\"btn save-objective\" data-element-id=\"". html_encode($form_element->getID()) ."\">". $translate->_("Save") ."</a>";
                            $html[] =                            "<span class=\"btn\">";
                            $html[] =                                "<input type=\"checkbox\" value=\"". html_encode($form_element->getID()) ."\" name=\"delete[]\" class=\"delete\" />";
                            $html[] =                            "</span>";
                            $html[] =                            "<a title=\"Move\" class=\"btn move\"><i class=\"icon-move\"></i></a>";
                            $html[] =                        "</div>";
                            $html[] =                    "</div>";
                                                }
                            $html[] =            "</td>";
                            $html[] =        "</tr>";
                            $html[] =        "<tr class=\"heading\">";
                            $html[] =            "<td>";
                            $html[] =                "<h3>". $translate->_("Select a Curriculum Tag Set") ."</h3>";
                            $html[] =            "</td>";
                            $html[] =        "</tr>";
                            $html[] =        "<tr class=\"item-response-view\">";
                            $html[] =            "<td class=\"item-type-control\">";
                                                $objectives = Models_Objective::fetchAllByOrganisationParentID($ENTRADA_USER->getActiveOrganisation());
                            $html[] =                "<div id=\"element-". html_encode($form_element->getID()) ."\" data-element-id=\"". html_encode($form_element->getID()) ."\">";
                                                if ($objectives) {
                                                    foreach ($objectives as $objective) {
                            $html[] =                        "<label class=\"radio form-item-objective-label\">";
                            $html[] =                            "<input type=\"radio\" name=\"form_item_objective_". html_encode($form_element->getID()) ."\" value=\"". html_encode($objective->getID()) ."\" data-element-id=\"". html_encode($form_element->getID()) ."\" " . ($form_element->getElementID() === $objective->getID() ? "checked=\"checked\"" : "") . " />";
                            $html[] =                            html_encode($objective->getName());
                            $html[] =                        "</label>";
                                                    }
                                                } else {
                            $html[] =                    "No objectives found to display";
                                                }
                            $html[] =                "</div>";
                            $html[] =            "</td>";
                            $html[] =        "</tr>";
                            $html[] =    "</table>";
                            $html[] = "</div>";
                        }
                        
                    break;
                    default:
                        $html[] = $form_element->getElementType();
                    break;
                }

                $rubric_id = $form_element->getRubricID();
                $i++;
            }

            if ($distribution) {
                if ($distribution->getFeedbackRequired()) {
                    Views_Deprecated_Form::renderFeedbackElements($distribution_data, $html, $disabled, $hide_from_approver, $is_pdf);
                }
            }
            if ($echo) {
                echo implode("\n", $html);
            } else {
                return $html;
            }
        } else {
            if ($echo) {
                echo $MODULE_TEXT["forms"]["edit-form"]["no_form_elements"];
            } else {
                return $MODULE_TEXT["forms"]["edit-form"]["no_form_elements"];
            }
        }
    }

    public static function renderFeedbackElements($distribution_data, &$html, $disabled = false, $hide_from_approver = false, $is_pdf = false) {
        global $ENTRADA_USER, $translate;

        // Only attempt to render progress for logged in users. Distributions do not allow external feedback, but you can still add an external as an additional task to a distribution that does allow feedback.
        if (isset($ENTRADA_USER) && $ENTRADA_USER) {

            $progress_record = Models_Assessments_Progress::fetchRowByID($distribution_data["aprogress_id"]);
            $assessment_record = Models_Assessments_Assessor::fetchRowByID($distribution_data["dassessment_id"]);
            // Disable the controls if the distribution has been deleted.
            $distribution = Models_Assessments_Distribution::fetchRowByIDIgnoreDeletedDate($distribution_data["adistribution_id"]);
            /*
            // Determine if the viewer is a distribution author.
            $author = false;
            if ($distribution_data["target_record_id"] != $ENTRADA_USER->getActiveId() && $distribution_data["proxy_id"] != $ENTRADA_USER->getActiveId()) {
                $distribution_authors = Models_Assessments_Distribution_Author::fetchAllByDistributionID($distribution->getID());
                foreach ($distribution_authors as $distribution_author) {
                    if ($distribution_author->getAuthorID() == $ENTRADA_USER->getActiveId()) {
                        $author = true;
                    }
                }
            }
            */
            if ($assessment_record && !$hide_from_approver) {

                // Display all feedback for non-preceptors (learner, PAs, distribution authors, etc.).
                if ($assessment_record->getAssessorValue() != $ENTRADA_USER->getActiveId()) {
                    if (!$progress_record || $progress_record->getAssessorType() == "internal") {

                        $html[] = "<h3 class=\"assessment-feedback-heading\">" . $translate->_("Assessment Feedback") . "</h3>";
                        $feedback_record = Models_Assessments_AssessorTargetFeedback::fetchRowByAssessorTarget($distribution_data["dassessment_id"], "internal", $assessment_record->getAssessorValue(), "internal", $distribution_data["target_record_id"]);
                        $html[] = "<div class=\"form-item\">";
                        $html[] = "  <div class=\"item-container\">";
                        $html[] = "      <table class=\"item-table\">";
                        $html[] = "          <tbody>";
                        $html[] = "              <tr class=\"heading\">";
                        $html[] = "                  <td colspan=\"2\">";
                        $html[] = "                      <h3>" . $translate->_("<strong>Preceptor response: </strong>Did you meet with this trainee to discuss their performance?") . "</h3>";
                        $html[] = "                  </td>";
                        $html[] = "              </tr>";
                        $html[] = "              <tr class=\"vertical-choice-row\">";
                        $html[] = "                  <td class=\"vertical-response-input\" width=\"5%\">";
                        $html[] = "                      <input id=\"assessor_feedback_yes\" type=\"radio\" name=\"assessor_feedback_response\" value=\"yes\"" . ($feedback_record && $feedback_record->getAssessorFeedback() == "1" ? " checked=\"checked\"" : "") . (($progress_record && $progress_record->getProgressValue() == "complete") || ($distribution->getDeletedDate() || $disabled) ? " disabled" : "") . " />";
                        $html[] = "                  </td>";
                        $html[] = "                  <td class=\"vertical-response-label\" width=\"95%\">";
                        $html[] = "                      <label for=\"assessor_feedback_yes\">";
                        $html[] =                        $translate->_("Yes");
                        $html[] = "                      </label>";
                        $html[] = "                  </td>";
                        $html[] = "              </tr>";
                        $html[] = "              <tr class=\"vertical-choice-row\">";
                        $html[] = "                  <td class=\"vertical-response-input\" width=\"5%\">";
                        $html[] = "                      <input id=\"assessor_feedback_no\" type=\"radio\" name=\"assessor_feedback_response\" value=\"no\"" . ($feedback_record && $feedback_record->getAssessorFeedback() == "0" ? " checked=\"checked\"" : "") . (($progress_record && $progress_record->getProgressValue() == "complete") || ($distribution->getDeletedDate() || $disabled) ? " disabled" : "") . " />";
                        $html[] = "                  </td>";
                        $html[] = "                  <td class=\"vertical-response-label\" width=\"95%\">";
                        $html[] = "                      <label for=\"assessor_feedback_no\">";
                        $html[] =                         $translate->_("No");
                        $html[] = "                      </label>";
                        $html[] = "                  </td>";
                        $html[] = "              </tr>";
                        $html[] = "          </tbody>";
                        $html[] = "      </table>";
                        $html[] = "  </div>";
                        $html[] = "</div>";

                        // Only display learner feedback if the feedback is complete. It should be disabled for everyone but the learner themselves.
                        if ($progress_record && $progress_record->getProgressValue() == "complete") {
                            $html[] = "<div class=\"form-item\">";
                            $html[] = "  <div class=\"item-container\">";
                            $html[] = "      <table class=\"item-table\">";
                            $html[] = "          <tbody>";
                            $html[] = "              <tr class=\"heading\">";
                            $html[] = "                  <td colspan=\"2\">";
                            $html[] = "                      <h3>" . $translate->_("Did you meet with your preceptor to discuss your performance?") . "</h3>";
                            $html[] = "                  </td>";
                            $html[] = "              </tr>";
                            $html[] = "              <tr class=\"vertical-choice-row\">";
                            $html[] = "                  <td class=\"vertical-response-input\" width=\"5%\">";
                            $html[] = "                      <input id=\"feedback_yes\" type=\"radio\" name=\"feedback_response\" value=\"yes\"" . ($feedback_record && $feedback_record->getTargetFeedback() == "1" ? " checked=\"checked\"" : "") . ((isset($feedback_record) && $feedback_record && $feedback_record->getTargetProgressValue() == "complete") || ($distribution->getDeletedDate() || $distribution_data["target_record_id"] != $ENTRADA_USER->getActiveId()) ? " disabled" : "") . " />";
                            $html[] = "                  </td>";
                            $html[] = "                  <td class=\"vertical-response-label\" width=\"95%\">";
                            $html[] = "                      <label for=\"feedback_yes\">";
                            $html[] = $translate->_("Yes");
                            $html[] = "                      </label>";
                            $html[] = "                  </td>";
                            $html[] = "              </tr>";
                            $html[] = "              <tr class=\"vertical-choice-row\">";
                            $html[] = "                  <td class=\"vertical-response-input\" width=\"5%\">";
                            $html[] = "                      <input id=\"feedback_no\" type=\"radio\" name=\"feedback_response\" value=\"no\"" . ($feedback_record && $feedback_record->getTargetFeedback() == "0" ? " checked=\"checked\"" : "") . ((isset($feedback_record) && $feedback_record && $feedback_record->getTargetProgressValue() == "complete") || ($distribution->getDeletedDate() || $distribution_data["target_record_id"] != $ENTRADA_USER->getActiveId()) ? " disabled" : "") . " />";
                            $html[] = "                  </td>";
                            $html[] = "                  <td class=\"vertical-response-label\" width=\"95%\">";
                            $html[] = "                      <label for=\"feedback_no\">";
                            $html[] = $translate->_("No");
                            $html[] = "                      </label>";
                            $html[] = "                  </td>";
                            $html[] = "              </tr>";
                            $html[] = "          </tbody>";
                            $html[] = "      </table>";
                            $html[] = "  </div>";
                            $html[] = "</div>";
                            $html[] = "<div class=\"form-item\">";
                            $html[] = "    <div class=\"item-container\">";
                            $html[] = "        <table class=\"item-table\">";
                            $html[] = "            <tbody>";
                            $html[] = "                <tr class=\"heading\">";
                            $html[] = "                    <td colspan=\"2\">";
                            $html[] = "                        <h3>" . $translate->_("Comments (optional)") . "</h3>";
                            $html[] = "                    </td>";
                            $html[] = "                </tr>";
                            $html[] = "                <tr class=\"item-response-view\">";
                            $html[] = "                    <td class=\"item-type-control\">";
                            $html[] = "                        <textarea class=\"expandable\" name=\"feedback_meeting_comments\"" . (($feedback_record && $feedback_record->getTargetProgressValue() == "complete") || ($distribution->getDeletedDate() || $distribution_data["target_record_id"] != $ENTRADA_USER->getActiveId()) ? " disabled" : "") . ">" . ($feedback_record && $feedback_record->getComments() ? html_encode($feedback_record->getComments()) : "") . "</textarea>";
                            $html[] = "                    </td>";
                            $html[] = "                </tr>";
                            $html[] = "            </tbody>";
                            $html[] = "        </table>";
                            $html[] = "    </div>";
                            $html[] = "</div>";

                            // Display submission buttons for target learners when the feedback has not been completed.
                            if (!$is_pdf && $distribution_data["target_record_id"] == $ENTRADA_USER->getActiveId() && (!$feedback_record || ($feedback_record && $feedback_record->getTargetProgressValue() != "complete"))) {
                            //if ((!$feedback_record) || ($feedback_record && $feedback_record->getTargetProgressValue() != "complete" && !$author)) {
                                $html[] = "<div class=\"row-fluid\">";
                                $html[] = "    <div class=\"pull-right\">";
                                $html[] = "        <input type=\"submit\" id=\"save-form\" class=\"btn btn-warning\" name=\"save_form_progress\" value=\"" . $translate->_("Save as Draft") . "\" />";
                                $html[] = "        <span class=\"or\">or</span>";
                                $html[] = "        <input class=\"btn btn-primary\" type=\"submit\" id=\"submit_form\" name=\"submit_form\" value=\"" . $translate->_("Submit") . "\"/>";
                                $html[] = "    </div>";
                                $html[] = "</div>";
                            }
                        }
                    }
                } else {

                    // Note that the submit buttons for this case will be rendered as part of the overall assessment form, as it is the preceptor completing feedback on a target.
                    $progress_record = Models_Assessments_Progress::fetchRowByID($distribution_data["aprogress_id"]);
                    $feedback_record = Models_Assessments_AssessorTargetFeedback::fetchRowByAssessorTarget($distribution_data["dassessment_id"], "internal", $assessment_record->getAssessorValue(), "internal", $distribution_data["target_record_id"]);
                    $html[] = "<h3 class=\"assessment-feedback-heading\">" . $translate->_("Assessment Feedback") . "</h3>";
                    $html[] = "<div class=\"form-item\">";
                    $html[] = "  <div class=\"item-container\">";
                    $html[] = "      <table class=\"item-table\">";
                    $html[] = "          <tbody>";
                    $html[] = "              <tr class=\"heading\">";
                    $html[] = "                  <td colspan=\"2\">";
                    $html[] = "                      <h3>" . $translate->_("Did you meet with this trainee to discuss their performance?") . "</h3>";
                    $html[] = "                  </td>";
                    $html[] = "              </tr>";
                    $html[] = "              <tr class=\"vertical-choice-row\">";
                    $html[] = "                  <td class=\"vertical-response-input\" width=\"5%\">";
                    $html[] = "                      <input id=\"assessor_feedback_yes\" type=\"radio\" name=\"assessor_feedback_response\" value=\"yes\"" . ($feedback_record && $feedback_record->getAssessorFeedback() == "1" ? " checked=\"checked\"" : "") . (($progress_record && $progress_record->getProgressValue() == "complete") || ($distribution->getDeletedDate() || $disabled) ? " disabled" : "") . " />";
                    $html[] = "                  </td>";
                    $html[] = "                  <td class=\"vertical-response-label\" width=\"95%\">";
                    $html[] = "                      <label for=\"feedback_yes\">";
                    $html[] = $translate->_("Yes");
                    $html[] = "                      </label>";
                    $html[] = "                  </td>";
                    $html[] = "              </tr>";
                    $html[] = "              <tr class=\"vertical-choice-row\">";
                    $html[] = "                  <td class=\"vertical-response-input\" width=\"5%\">";
                    $html[] = "                      <input id=\"assessor_feedback_no\" type=\"radio\" name=\"assessor_feedback_response\" value=\"no\"" . ($feedback_record && $feedback_record->getAssessorFeedback() == "0" ? " checked=\"checked\"" : "") . (($progress_record && $progress_record->getProgressValue() == "complete") || ($distribution->getDeletedDate() || $disabled) ? " disabled" : "") . " />";
                    $html[] = "                  </td>";
                    $html[] = "                  <td class=\"vertical-response-label\" width=\"95%\">";
                    $html[] = "                      <label for=\"feedback_no\">";
                    $html[] = $translate->_("No");
                    $html[] = "                      </label>";
                    $html[] = "                  </td>";
                    $html[] = "              </tr>";
                    $html[] = "          </tbody>";
                    $html[] = "      </table>";
                    $html[] = "  </div>";
                    $html[] = "</div>";
                }
            }

            $html[] = "<input type=\"hidden\" name=\"feedback_proxy_id\" value=\"" . html_encode($distribution_data["assessor_value"]) . "\" />";
        }
    }
}