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
 * This is the view class for the Blueprint editor form. This class
 * encapsulates all other views that are rendered on the form page.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_FormBlueprints_Pages_Form extends Views_HTML {

    /**
     * Validate; ensure required variables exist.
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        $is_set = $this->validateIsSet($options,
            array(
                "objectives_title",
                "epas",
                "form_blueprint_id",
                "form_type_id",
                "actor_proxy_id",
                "actor_organisation_id",
                "title",
                "description",
                "published",
                "is_publishable",
                "include_instructions",
                "instructions",
                "first_editable_index",
                "course_related"
            )
        );
        if (!$is_set) {
            return false;
        }
        $is_set_a = $this->validateArray($options,
            array(
                "forms",
                "components",
                "elements",
                "init_data",
                "authors",
                "component_scales"
            )
        );
        if (!$is_set_a) {
            return false;
        }
        return true;
    }

    /**
     * Render the page.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;

        $form_blueprint_id      = $options["form_blueprint_id"];
        $form_type_id           = $options["form_type_id"];
        $actor_proxy_id         = $options["actor_proxy_id"];
        $actor_organisation_id  = $options["actor_organisation_id"];
        $authors                = $options["authors"];
        $blueprint_title        = $options["title"];
        $blueprint_description  = $options["description"];
        $include_instructions   = $options["include_instructions"];
        $instructions           = $options["instructions"];
        $components             = $options["components"];
        $elements               = $options["elements"];
        $standard_item_options  = $options["standard_item_options"];
        $objectives_title       = $options["objectives_title"];
        $epas                   = $options["epas"];
        $course_related         = $options["course_related"];
        $courses_list           = $options["courses_list"];
        $contextual_variables   = $options["contextual_variables"];
        $scales_list            = $options["scales_list"];
        $component_scales       = $options["component_scales"];
        $init_data              = $options["init_data"];
        $first_editable_index   = $options["first_editable_index"];
        $epas_desc              = $options["epas_desc"];
        $contextual_vars_desc   = $options["contextual_vars_desc"];
        $published              = $options["published"];
        $forms_created          = $options["forms_created"];
        $is_publishable         = $options["is_publishable"];
        $rubrics                = Entrada_Utilities::arrayValueOrDefault($options, "rubrics", array());
        $course_id              = (int)Entrada_Utilities::arrayValueOrDefault($options, "course_id", 0);
        $forms                  = $options["forms"];

        ?>
        <?php $this->renderHead(); ?>

        <h1><?php echo $translate->_("Edit Form Template") ?></h1>

        <div id="blueprint-page-loading-overlay" style="display: none;"></div>
        <form id="form-elements"
              action="<?php echo ENTRADA_URL . "/admin/assessments/blueprints?section=edit-blueprint&form_blueprint_id={$form_blueprint_id}"; ?>"
              data-form-blueprint-id="<?php echo html_encode($form_blueprint_id); ?>"
              class="form-horizontal"
              method="POST">

            <input type="hidden" name="step" value="2"/>

            <h2 title="<?php echo $translate->_("Form Template Information"); ?>"><?php echo $translate->_("Form Template Information"); ?></h2>

            <?php
                // Render "Form Information" input boxes
                $information_view = new Views_Assessments_FormBlueprints_Sections_BlueprintInformation();
                $information_view->render(
                    array(
                        "form_blueprint_id" => $form_blueprint_id,
                        "title" => $blueprint_title,
                        "objectives_title" => $objectives_title,
                        "form_type_id" => $form_type_id,
                        "course_id" => $course_id,
                        "courses_list" => $courses_list,
                        "course_related" => $course_related,
                        "description" => $blueprint_description,
                        "include_instructions" => $include_instructions,
                        "instructions" => $instructions,
                        "form_types" => Models_Assessments_Form_Type::fetchAllByOrganisationID($actor_organisation_id),
                        "authors" => $authors,
                        "published" => $published,
                        "forms_created" => $forms_created,
                        "forms" => $forms,
                        "is_publishable" => $is_publishable
                    )
                );
            ?>
        </form>
        <?php if ($course_id !== null): ?>
            <script type="text/javascript">
                var COURSE_ID = <?php echo $course_id; ?>;
            </script>
            <a href="#copy-form-blueprint-modal" data-toggle="modal" class="btn pull-right"><i class="icon-share"></i> <?php echo $translate->_("Copy Form Template"); ?></a>
            <h2><?php echo $translate->_("Template Components"); ?></h2>
            <div id="blueprint-components-information-error-msg" class="blueprint-components-information-error-msg"></div>
            <div class="" id="blueprint-components">
                <?php
                $view_options = array(
                    "form_blueprint_id" => $form_blueprint_id,
                    "disabled" => $published,
                    "elements" => $elements,
                    "components" => $components,
                    "course_id" => $course_id,
                    "rubrics" => $rubrics,
                    "actor_proxy_id" => $actor_proxy_id,
                    "actor_organisation_id" => $actor_organisation_id,
                    "epas" => $epas,
                    "contextual_variables" => $contextual_variables,
                    "scales_list" => $scales_list,
                    "component_scales" => $component_scales,
                    "standard_item_options" => $standard_item_options,
                    "init_data" => $init_data,
                    "first_editable_index" => $first_editable_index,
                    "epas_desc" => $epas_desc,
                    "contextual_vars_desc" => $contextual_vars_desc
                );
                $blueprint_view = new Views_Assessments_FormBlueprints_Blueprint();
                $blueprint_view->render($view_options);
                ?>
            </div>
        <?php endif;
    }

    /**
     * Generic error.
     */
    protected function renderError() {
        global $translate;
        ?>
        <div class="alert alert-block alert-error">
            <ul>
                <li><?php echo $translate->_("Unable to render form template editor."); ?></li>
            </ul>
        </div>
        <?php
    }

    /**
     * Render the header using $HEAD variable and set some scripts.
     */
    private function renderHead() {
        global $HEAD;
        $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.advancedsearch.js\"></script>";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/jquery/jquery.advancedsearch.css\" />";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/assessments/items.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/assessments/rubrics.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/assessments/assessments.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/assessments/assessment-form.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/assessments/assessment-blueprint.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
        $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = \"" . ENTRADA_URL . "\";</script>";
        $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.audienceselector.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/jquery/jquery.audienceselector.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
        $HEAD[] = Entrada_Utilities_jQueryHelper::addjQuery();
        $HEAD[] = Entrada_Utilities_jQueryHelper::addjQueryLoadTemplate();
        ?>
        <script type="text/javascript">
            var ENTRADA_URL = "<?php echo ENTRADA_URL; ?>";
            var API_URL = "<?php echo ENTRADA_URL . "/admin/assessments/blueprints?section=api-blueprints"; ?>";
            var assessment_blueprints_localization = {};
        </script>
        <script type="text/javascript" src="<?php echo ENTRADA_URL . "/javascript/assessments/forms/assessments-blueprints-admin.js?release=" . html_encode(APPLICATION_VERSION); ?>"></script>
        <?php
    }

}