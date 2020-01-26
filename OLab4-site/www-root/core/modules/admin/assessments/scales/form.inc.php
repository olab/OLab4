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
 * The form that allows users to add and edit formbank forms.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED")) || (!defined("ADD_SCALE") && !defined("EDIT_SCALE"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "update", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));
    echo display_error();
    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    $forms_api = new Entrada_Assessments_Forms(array("actor_proxy_id" => $ENTRADA_USER->getActiveId(), "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation()));
    /**
     * Load the rich text editor.
     */
    load_rte("list");

    $PROCESSED["responses"] = array();

    $scale_in_use = false;
    if ($PROCESSED["rating_scale_id"] && !empty($scale_data)) {
        $scale_in_use = $forms_api->isScaleInUse($PROCESSED["rating_scale_id"]);
        foreach ($scale_data["responses"] as $i => $response_data) {
            $PROCESSED["responses"][$i] = $response_data["ardescriptor_id"];
        }
    }

    switch ($STEP) {
        case 2 :
            if (isset($_POST["rating_scale_id"]) && $tmp_input = clean_input($_POST["rating_scale_id"], "int")) {
                $PROCESSED["rating_scale_id"] = $tmp_input;
            } else {
                $PROCESSED["rating_scale_id"] = null;
            }

            if (isset($_POST["title"]) && $tmp_input = clean_input($_POST["title"], array("trim", "striptags"))) {
                $PROCESSED["rating_scale_title"] = $tmp_input;
            } else {
                $PROCESSED["rating_scale_title"] = "";
                add_error($translate->_("You must provide a <strong>Title</strong> for this scale."));
            }

            if (isset($_POST["rating_scale_type"]) && $tmp_input = clean_input($_POST["rating_scale_type"], array("trim", "striptags"))) {
                $PROCESSED["rating_scale_type"] = $tmp_input;
            } else {
                $PROCESSED["rating_scale_type"] = 0;
            }

            if (isset($_POST["description"]) && $tmp_input = clean_input($_POST["description"], "striptags")) {
                $PROCESSED["rating_scale_description"] = $tmp_input;
            } else {
                $PROCESSED["rating_scale_description"] = null;
            }

            if (isset($_POST["selected_ardescriptor_ids"]) && !empty($_POST["selected_ardescriptor_ids"])) {
                ksort($_POST["selected_ardescriptor_ids"]); // make sure natural order is honoured
                $PROCESSED["responses"] = array(); // blow away the previous responses as we've posted new ones
                foreach ($_POST["selected_ardescriptor_ids"] as $ordinal => $selected_ardescriptor_id) {
                    $PROCESSED["responses"][] = clean_input($selected_ardescriptor_id, array("trim", "int"));
                }
            }

            if (empty($PROCESSED["responses"])) {
                add_error($translate->_("Please select response descriptors."));
            } else {
                $response_position = 0;
                foreach ($PROCESSED["responses"] as $response_index => $ardescriptor_id) {
                    $response_position++;
                    if (!$ardescriptor_id) {
                        add_error(sprintf($translate->_("Please select a response descriptor for <strong>Response %s</strong>."), $response_position));
                    }
                }
                if (!$ERROR) {
                    if (count($PROCESSED["responses"]) != count(array_unique($PROCESSED["responses"]))) {
                        add_error($translate->_("Unable to add responses with duplicate response descriptor(s). Please ensure you have selected unique response descriptors."));
                    }
                }
            }

            if (!$ERROR) {
                $scale_data = array(
                    "rating_scale" => array(
                        "rating_scale_id" => $PROCESSED["rating_scale_id"],
                        "rating_scale_type" => $PROCESSED["rating_scale_type"],
                        "rating_scale_title" => $PROCESSED["rating_scale_title"],
                        "rating_scale_description" => $PROCESSED["rating_scale_description"]
                    ),
                    "responses" => $PROCESSED["responses"]
                );

                if ($forms_api->saveScale($scale_data, $PROCESSED["rating_scale_id"])) {
                    $rating_scale_id = $forms_api->getScaleID();
                    $url = ENTRADA_URL."/admin/assessments/scales?section=edit-scales&rating_scale_id={$rating_scale_id}";
                    $success_msg = sprintf($translate->_("The scale has been successfully saved. You will be redirected back to the rating scale. Please <a href=\"%s\">click here</a> if you do not wish to wait."), $url);

                    add_success($success_msg);
                    $ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";
                }  else {
                    foreach ($forms_api->getErrorMessages() as $error_message) {
                        add_error($error_message);
                    }
                    $STEP = 1;
                }

            } else {
                $STEP = 1;
            }

            if ($SUCCESS) {
                echo display_success();
            }
            if ($ERROR) {
                echo display_error();
            }
        break;
    }

    if ($STEP == 1) {
        $HEAD[] = "<script src=\"" . ENTRADA_URL . "/javascript/objectives.js\"></script>";
        $HEAD[] = "<script>var API_URL = \"" . ENTRADA_URL . "/admin/assessments/scakes?section=api-scales" . "\";</script>";
        $HEAD[] = "<script src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.audienceselector.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<link rel=\"stylesheet\" href=\"" . ENTRADA_URL . "/css/jquery/jquery.audienceselector.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
        $HEAD[] = "<script src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.advancedsearch.js\"></script>";
        $HEAD[] = "<link rel=\"stylesheet\" href=\"" . ENTRADA_URL . "/css/jquery/jquery.advancedsearch.css\" />";
        $HEAD[] = Entrada_Utilities_jQueryHelper::addjQuery();
        $HEAD[] = Entrada_Utilities_jQueryHelper::addjQueryLoadTemplate();
        ?>
        <script src="<?php echo ENTRADA_URL; ?>/javascript/assessments/scales/scales-admin.js"></script>
        <script src="<?php echo ENTRADA_URL; ?>/javascript/assessments/scales/scales-responses.js"></script>
        <script>
            var assessment_item_localization = {};
            assessment_item_localization.response_item_template = "<?php echo $translate->_("Response <span>%s</span>"); ?>";
            var ENTRADA_URL = '<?php echo ENTRADA_URL; ?>';
        </script>

        <?php if ($scale_in_use): ?>
            <div class="alert alert-info">
                <ul>
                    <li>
                        <p><?php echo $translate->_("This <strong>Rating Scale</strong> is in use by one or more <strong>Items</strong> or <strong>Grouped Items</strong>. Only permissions can be modified when a <strong>Rating Scale</strong> is in use."); ?></p>
                    </li>
                </ul>
            </div>
        <?php endif; ?>

        <form id="item-form" action="<?php echo ENTRADA_URL . "/admin/assessments/scales?step=2&section=" . $SECTION . ($METHOD == "update" ? "&rating_scale_id=" . $PROCESSED["rating_scale_id"] : ""); ?>" class="form-horizontal" method="POST">
            <?php
            if (isset($PROCESSED["rating_scale_id"]) && $PROCESSED["rating_scale_id"]) {
                $authors = Models_Assessments_RatingScale_Author::getAllAuthors($PROCESSED["rating_scale_id"]);
            } else {
                $authors = null;
            }
            $rating_scale_types = Models_Assessments_RatingScale_Type::fetchAllByOrganisationID($ENTRADA_USER->getActiveOrganisation());

            $scale_information_view = new Views_Assessments_Forms_Sections_ScaleInformation();
            $scale_information_view->render(array(
                "api_url" => ENTRADA_URL . "/admin/assessments/scales?section=api-scales",
                "responses_count" => (isset($PROCESSED["responses"])) ? count($PROCESSED["responses"]) : 0,
                "rating_scale_id" => (isset($PROCESSED["rating_scale_id"]) ? $PROCESSED["rating_scale_id"] : 0),
                "scale_type" => (isset($PROCESSED["rating_scale_type"]) ? $PROCESSED["rating_scale_type"] : ""),
                "rating_scale_types" => $rating_scale_types,
                "scale_title" => (isset($PROCESSED["rating_scale_title"]) ? $PROCESSED["rating_scale_title"] : ""),
                "scale_description" => (isset($PROCESSED["rating_scale_description"]) ? $PROCESSED["rating_scale_description"] : ""),
                "read_only" => $scale_in_use,
                "method" => $METHOD,
                "authors" => $authors
            ));

            $response_descriptors_records = Models_Assessments_Response_Descriptor::fetchAllByOrganisationIDSystemType($ENTRADA_USER->getActiveOrganisation(), "entrada");
            $response_descriptors = array();
            foreach ($response_descriptors_records as $descriptors_record) {
                $response_descriptors[$descriptors_record->getID()] = $descriptors_record->toArray();
            }

            $descriptor_search_datasource = array();
            if ($response_descriptors) {
                foreach ($response_descriptors as $i => $response_descriptor) {
                    $descriptor_search_datasource[$i] = array("target_id" => $response_descriptor["ardescriptor_id"], "target_label" => $response_descriptor["descriptor"]);
                }
            }

            usort($descriptor_search_datasource, function($a, $b) {
                return ($a["target_label"] > $b["target_label"]);
            });

            $response_section_view = new Views_Assessments_Forms_Sections_ScaleResponses(array("id" => "response-section"));
            $response_section_view->render(array(
                "response_descriptors" => $PROCESSED["responses"],
                "readonly_override" => $scale_in_use,
                "descriptors" => $descriptor_search_datasource
            ));
            ?>
            <div class="row-fluid space-above">
                <?php $url = ENTRADA_URL . "/admin/assessments/scales"; ?>
                <a href="<?php echo $url; ?>" class="btn btn-default"><?php echo $translate->_("Back") ?></a>
                <input type="submit" class="btn btn-primary pull-right" value="<?php echo $translate->_("Save"); ?>"/>
            </div>
        </form>
        <?php
        // Render templates for loadTemplate functionality
        $response_row_template = new Views_Assessments_Forms_Templates_ScaleResponseRow();
        $response_row_template->render();
    }
}