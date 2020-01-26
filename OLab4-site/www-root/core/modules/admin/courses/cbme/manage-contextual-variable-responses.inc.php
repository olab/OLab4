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
 * @author Unit: School of Medicine
 * @author Developer: Jordan L <jl250@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_CBME"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("coursecontent", "update", false)) {
    $ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

    $ERROR++;
    $ERRORSTR[]	= sprintf($translate->_("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"%s\">%s</a> for assistance."), "mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"]));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    $forms_api = new Entrada_Assessments_Forms(array("actor_proxy_id" => $ENTRADA_USER->getActiveId(), "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation()));
    $course = Models_Course::get($COURSE_ID);
    if ($course && $ENTRADA_ACL->amIAllowed(new CourseContentResource($course->getID(), $course->getOrganisationID()), "update")) {
        courses_subnavigation($course->toArray(), "cbme");

        $BREADCRUMB[] = array("url" => ENTRADA_URL . "/admin/" . $MODULE . "/cbme?" . replace_query(array("section" => "curriculumtags", "id" => $COURSE_ID, "step" => false)), "title" => $translate->_("Contextual Variable Responses"));

        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/courses/curriculum-tags.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
        $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/courses/curriculumtags/curriculumtags.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.advancedsearch.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/jquery/jquery.advancedsearch.css\" />";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_RELATIVE . "/css/font-awesome/css/font-awesome.min.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";

        echo "<h1 class=\"muted\">" . $translate->_("Contextual Variable Responses") . "</h1>";

        include("cbme-setup.inc.php");

        if ($cbme_checked) : ?>
            <div class="space-below">
                <?php
                /**
                 * Render the Course CBME subnavigation
                 */
                $navigation_view = new Views_Course_Cbme_Navigation();
                $navigation_view->render(array(
                    "course_id" => $COURSE_ID,
                    "active_tab" => "contextual_variable_responses"
                ));
                ?>
            </div>
        <?php
        $objective_set_model = new Models_ObjectiveSet();
        $contextual_variable_parent = $objective_set_model->fetchRowByShortname("contextual_variable");
        $objective_model = new Models_Objective();
        $objective_parents = $objective_model->fetchAllByObjectiveSetID($contextual_variable_parent->getID(), $ENTRADA_USER->getActiveOrganisation());
        if ($objective_parents) :
            $objective_displayed = false; ?>
            <input id="course-id" type="hidden" value="<?php echo html_encode($COURSE_ID) ?>" />
            <input id="organisation-id" type="hidden" value="<?php echo $ENTRADA_USER->getActiveOrganisation(); ?>" />
            <div id="msgs" class="space-above"></div>
            <?php foreach ($objective_parents as $objective_parent) :
                $show_criteria = false;
                if ($objective_parent->getCode() == "procedure") {
                    $show_criteria = true;
                }
                ?>
                <div class="cv-response-objective hide">
                    <a class="toggle-cv-response" id="<?php echo $objective_parent->getCode(); ?>">
                        <h3 class="cv-response-code"><?php echo html_encode($objective_parent->getName()); ?></h3>
                        <span class="pull-right toggle-cv-response-text"><?php echo $translate->_("Show"); ?></span>
                    </a>
                    <div class="toggle-cv-response-group collapsed">
                        <table class="table table-striped table-bordered remove-bottom-margin <?php echo $objective_parent->getCode(); ?>"
                               id="<?php echo $objective_parent->getCode(); ?>">
                            <thead>
                            <tr>
                                <th width="5%"><?php echo $translate->_("Delete"); ?></th>
                                <th width="30%"><?php echo $translate->_("Response"); ?></th>
                                <th width="55%"><?php echo $translate->_("Description (optional)"); ?></th>
                                <th width="5%"
                                    class="<?php echo $show_criteria ? "" : "hide" ?>"><?php echo $translate->_("Criteria"); ?></th>
                                <th width="5%"><?php echo $translate->_("Save"); ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            $contextual_variable_responses = $objective_model->fetchChildrenByObjectiveSetShortnameObjectiveCodeCourseIDIgnoreActive("contextual_variable_responses", $objective_parent->getCode(), $COURSE_ID);
                            if ($contextual_variable_responses) :
                                foreach ($contextual_variable_responses as $contextual_variable_response) :
                                    $response_count = Models_Objective::countObjectiveChildren($contextual_variable_response->getID());
                                    if ($contextual_variable_response->getActive()) :
                                        $objective_displayed = true; ?>
                                        <tr data-objective-id="<?php echo $contextual_variable_response->getID(); ?>"
                                            data-objective-code="<?php echo $contextual_variable_response->getCode(); ?>">
                                            <td class="remove-response-row"><a class="btn cv-response-remove"
                                                                               data-original-title="<?php echo $translate->_("Remove Response") ?>"
                                                                               data-placement="bottom"
                                                                               href="#delete-contextual-variable-response-modal"
                                                                               data-toggle="modal"><i
                                                            class="fa fa-minus-circle red-icon fa-lg"></i></a></td>
                                            <td><input class="input-xlarge objective-name" type="text"
                                                       value="<?php echo htmlspecialchars($contextual_variable_response->getName(), ENT_QUOTES, 'UTF-8'); ?>"/>
                                            </td>
                                            <td><?php echo sprintf('<textarea class="cv-response-description objective-description" rows="1">%s</textarea>', $contextual_variable_response->getDescription() ? $contextual_variable_response->getDescription() : ""); ?></td>
                                            <td class="view-upload-criteria <?php echo $show_criteria ? "" : "hide" ?>">
                                                <a class="btn cv-upload-criteria" data-toggle="tooltip"
                                                   data-original-title="<?php echo $translate->_("Upload New Assessment Criteria") ?>"
                                                   data-placement="bottom">
                                                    <i id="procedure-upload-icon-<?php echo $contextual_variable_response->getID(); ?>"
                                                       class="fa <?php echo ($response_count) ? "fa-check green-icon" : "fa-upload black-icon"; ?> aria-hidden="
                                                       true"></i>
                                                </a>
                                            </td>
                                            <td class="save-response-row"><a class="btn cv-response-save"
                                                                             data-toggle="tooltip"
                                                                             data-original-title="<?php echo $translate->_("Save Response") ?>"
                                                                             data-placement="bottom"><i
                                                            class="fa fa-floppy-o fa-lg green-icon"
                                                            aria-hidden="true"></i></a></td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                        <button id="<?php echo $objective_parent->getCode(); ?>_button"
                                class="btn btn-default btn-block space-below cv-response-add"
                                data-objective-code="<?php echo $objective_parent->getCode(); ?>"><?php echo $translate->_("Add Response"); ?>
                            <i class="icon-plus-sign"></i></button>
                    </div>
                </div>
            <?php endforeach;
            $template_view = new Views_Course_Cbme_ImportContextualVariableResponses_DeleteContextualVariableModal();
            $template_view->render();

            $criteria_list_template = new Views_CBME_Templates_CriteriaListItem();
            $criteria_list_template->render();

            $epas = $forms_api->fetchEPADescriptionsArray($COURSE_ID);
            $upload_procedure_view = new Views_Course_Cbme_Modals_UploadProcedureCriteria();
            $upload_procedure_view->render(array(
                "course_id" => $COURSE_ID,
                "title" => $translate->_("Assessment criteria"),
                "epas" => $epas
            ));

                if (!$objective_displayed) :
                    echo display_notice($translate->_("Please click on <strong>Import CBME Data</strong> to add some Contextual Variable Responses."));
                endif;
            else : ;
                echo display_error(sprintf($translate->_("No objective parents found within the system. Please contact the <a href=\"%s\">Education Technology support</a>."), "mailto:healthsci.suport@queensu.ca"));
            endif;
        endif;
    } else {
        add_error($translate->_("You do not have the required permissions to edit this course resource."));

        echo display_error();

        application_log("notice", "Failed to provide a valid course identifer when attempting to edit a course.");
    }
}
