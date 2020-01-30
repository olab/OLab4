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
 * HTML view for an assessment form. This view instantiates
 * all of the required item and form control views.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Forms_Form extends Views_Assessments_Forms_Base {

    protected function validateOptions($options = array()) {
        if (!$this->validateArray($options, array("elements"))) {
            return false;
        }
        return true;
    }

    protected function renderView($options = array()) {
        global $translate;

        $mode = $this->getMode();

        $form_elements        = $options["elements"];   // All of the form elements to render
        $rubrics              = array_key_exists("rubrics", $options) ? (is_array($options["rubrics"]) ? $options["rubrics"] : array()) : array(); // All of the data for rubrics, grouped accordingly
        $disabled             = array_key_exists("disabled", $options) ? $options["disabled"] : true;
        $public               = array_key_exists("public", $options) ? $options["public"] : true; // Which type of objective set to render
        $aprogress_id         = array_key_exists("aprogress_id", $options) ? $options["aprogress_id"] : null;
        $progress             = array_key_exists("progress", $options) ? $options["progress"] : array(); // All of the progress for this form (empty array when there is none)
        $selected_objectives  = array_key_exists("objectives", $options) ? $options["objectives"] : array(); // An array of objective IDs
        $all_objectives       = array_key_exists("all_objectives", $options) ? $options["all_objectives"] : array(); // All organisation specific Objectives objects
        $referrer_hash        = array_key_exists("referrer_hash", $options) ? $options["referrer_hash"] : null;
        $mutators             = Entrada_Utilities::arrayValueOrDefault($options, "form_mutators", array());

        if (!is_array($progress)) {
            $progress = array();
        }
        $rendered_rubrics = array();

        if ($form_elements) {

            // Render each form element given to us.
            foreach ($form_elements as $afelement_id => $form_element) {
                $rubric_id = $form_element["element"]["rubric_id"];
                $element_type = $form_element["element"]["element_type"];

                if ($element_type == "item" && $rubric_id) {

                    /* Render a rubric */

                    // Each of the rubric's elements are stored as part of the form, so only render it once.
                    if (!in_array($rubric_id, $rendered_rubrics)) {
                        $rendered_rubrics[] = $rubric_id;
                        if (!isset($rubrics[$rubric_id]) || empty($rubrics[$rubric_id])) {
                            $rubrics[$rubric_id] = array(); // If it isn't set for some reason, set it empty, and let the view handle the error.
                        }

                        $rubric_view = new Views_Assessments_Forms_Rubric(array("mode" => $mode));
                        $rubric_view->render(
                            array(
                                "afelement_id" => $afelement_id,
                                "rubric_id" => $rubric_id,
                                "rubric_data" => $rubrics[$rubric_id],
                                "progress" => $progress,
                                "aprogress_id" => $aprogress_id,
                                "disabled" => $disabled,
                                "referrer_hash" => $referrer_hash,
                                "mutators" => $mutators
                            )
                        );
                    }

                } else if ($element_type == "item" && !$rubric_id) {

                    /* Render single form item */

                    // Get the attributes
                    $attributes = Entrada_Utilities::arrayValueOrDefault($form_element["item"], "attributes");
                    if ($attributes) {
                        $attributes = json_decode($attributes, true);
                    }

                    // Item header controls
                    $form_controls_view = new Views_Assessments_Forms_Controls_ElementHeaderControls(array("mode" => $mode));
                    $item_header_html = $form_controls_view->render(
                        array(
                            "form_id" => $form_element["element"]["form_id"],
                            "afelement_id" => $afelement_id,
                            "element_id" => $form_element["item"]["item_id"],
                            "itemtype_shortname" => $form_element["item"]["shortname"],
                            "referrer_hash" => $referrer_hash,
                            "deleted_date" => $form_element["item"]["deleted_date"],
                            "attributes" => $attributes
                        ),
                        false // Do not echo (this item header HTML is passed along to the item)
                    );

                    // Standard set of data passed to form items
                    $item_options = array(
                        "afelement_id" => $afelement_id,
                        "item" => $form_element["item"],
                        "itemtype_shortname" => $form_element["item"]["shortname"],
                        "element" => $form_element["element"],
                        "responses" => $form_element["responses"],
                        "progress" => $progress,
                        "tags" => $form_element["tags"],
                        "disabled" => $disabled,
                        "header_html" => $item_header_html,
                        "referrer_hash" => $referrer_hash,
                        "mutators" => $mutators
                    );
                    $item_view = new Views_Assessments_Forms_Item(array("mode" => $mode));
                    $item_view->render($item_options);

                } else if ($element_type == "text") {

                    /* Render free-text label (not a comment) */

                    $free_text_view = new Views_Assessments_Forms_Controls_FreeTextLabel(array("mode" => $mode));
                    $free_text_view->render(
                        array(
                            "afelement_id" => $form_element["element"]["afelement_id"],
                            "element_text" => $form_element["element"]["element_text"]
                        )
                    );

                } else if ($element_type == "objective") {

                    /* Render objective selector */

                    if ($public) {

                        $public_objective = new Views_Assessments_Forms_Objectives_PublicObjectiveSelector(array("mode" => $mode));
                        $public_objective->render(
                            array(
                                "afelement_id" => $afelement_id,
                                "objective_id" => $form_element["element"]["element_id"],
                                "objectives" => $selected_objectives,
                                "aprogress_id" => $aprogress_id,
                                "organisation_id" => $form_element["element"]["organisation_id"]
                            )
                        );

                    } else {

                        $admin_objective = new Views_Assessments_Forms_Objectives_AdminObjectiveSetSelector(array("mode" => $mode));
                        $admin_objective->render(
                            array(
                                "afelement_id" => $afelement_id,
                                "objective_id" => $form_element["element"]["element_id"],
                                "objectives" => $all_objectives
                            )
                        );
                    }

                } else {

                    echo $translate->_("Unable to render unsupported form element type.");

                }
            }

        } else { ?>
            <div class="no-items-attached-message">
                <?php echo $translate->_("There are currently no items attached to this form."); ?>
            </div>
        <?php
        }
    }

    /**
     * Render a generic error message.
     */
    protected function renderError() {
        global $translate;?>
        <div class="alert alert-danger">
            <strong><?php echo $translate->_("Unable to render form"); ?></strong>
        </div>
        <?php
    }

}