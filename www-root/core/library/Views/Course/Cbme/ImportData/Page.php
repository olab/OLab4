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
 * View class for rendering the contextual variable import page
 *
 * @author Organization: Queen's University.
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_Course_Cbme_ImportData_Page extends Views_HTML {
    /**
     * Validate: ensure all attributes that the view requires are available to the renderView function
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        if (!isset($options["entrada_url"])) {
            return false;
        }

        if (!isset($options["course_id"])) {
            return false;
        }

        if (!isset($options["module"])) {
            return false;
        }

        if (!isset($options["course_epas"])) {
            return false;
        }

        if (!isset($options["course_milestones"])) {
            return false;
        }

        if (!isset($options["course_enabling_competencies"])) {
            return false;
        }

        if (!isset($options["course_key_competencies"])) {
            return false;
        }

        if (!isset($options["contextual_variable_responses"])) {
            return false;
        }

        if (!isset($options["cbme_standard_kc_ec_objectives"])) {
            return false;
        }

        return true;
    }

    /**
     * Render the curriculum tag import form.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate, $ENTRADA_USER, $MODULE;
        $this->renderHead($options["entrada_url"], $options["course_id"], $options["module"]);
        ?>
        <h1 class="muted"><?php echo $translate->_("Import CBME Data") ?></h1>
        <div>
            <?php
            /**
             * Render the Course CBME subnavigation
             */
            $navigation_view = new Views_Course_Cbme_Navigation();
            $navigation_view->render(array(
                "course_id" => $options["course_id"],
                "active_tab" => "import_cbme_data"
            ));
            ?>
            <div class="btn-group pull-right">
                <a href="#" class="btn btn-default dropdown-toggle pull-right" data-toggle="dropdown"><?php echo $translate->_("Download Example CSV Templates") ?> <span class="caret"></span></a>
                <ul class="dropdown-menu">
                    <li><a href="<?php echo $options["entrada_url"] . "/admin/courses/cbme?section=api-cbme&method=download-epa-csv" ?>"><?php echo $translate->_("Entrustable Professional Activity Template") ?></a></li>
                    <li><a href="<?php echo $options["entrada_url"] . "/admin/courses/cbme?section=api-cbme&method=download-key-competency-csv" ?>"><?php echo $translate->_("Key Competency Template") ?></a></li>
                    <li><a href="<?php echo $options["entrada_url"] . "/admin/courses/cbme?section=api-cbme&method=download-enabling-competency-csv" ?>"><?php echo $translate->_("Enabling Competency Template") ?></a></li>
                    <li><a href="<?php echo $options["entrada_url"] . "/admin/courses/cbme?section=api-cbme&method=download-milestone-csv" ?>"><?php echo $translate->_("Milestone Template") ?></a></li>
                    <li><a href="<?php echo $options["entrada_url"] . "/admin/courses/cbme?section=api-cbme&method=download-ec-map-csv" ?>"><?php echo $translate->_("Enabling Competency Map Template") ?></a></li>
                    <li><a href="<?php echo $options["entrada_url"] . "/admin/courses/cbme?section=api-cbme&method=download-contextual-variable-csv" ?>"><?php echo $translate->_("Contextual Variable Response Template") ?></a></li>
                </ul>
            </div>
        </div>
        <div class="space-above">
            <?php Entrada_Utilities_Flashmessenger::displayMessages($MODULE); ?>
        </div>

        <?php if ($ENTRADA_USER->getActiveGroup() == "medtech" && $ENTRADA_USER->getActiveRole() == "admin") : ?>
        <a href="#reset-cbme-modal" class="btn btn-danger pull-right" data-toggle="modal"><?php echo $translate->_("Reset CBME Data") ?></a>
        <?php endif; ?>
        <div class="clearfix"></div>
        <ol id="import-cbme-data-list">
            <li id="epa-import" class="cbme-data-item">
                <?php echo $translate->_("Import EPAs") ?>
                <?php if ($options["course_epas"]) : ?>
                    <div class="panel panel-icon">
                        <div class="panel-body">
                            <span class="fa fa-check-circle-o"></span>
                            <?php echo sprintf($translate->_("You have successfully imported a list of <strong>Entrustable Professional Activities</strong>. If you wish to upload additional items to this list, please use the uploader below. <a href=\"%s\"><strong>Click here</strong></a> if you wish to make changes to existing EPAs."), ENTRADA_URL . "/admin/courses/cbme?section=edit-epas&id=". html_encode($options["course_id"])); ?>
                        </div>
                    </div>
                <?php endif;
                $epa_uploader = new Views_Course_Cbme_ImportData_Uploader();
                $epa_uploader->render(array(
                    "course_id" => $options["course_id"],
                    "data_method" => "upload-curriculum-tag-set",
                    "curriculum_tag_shortname" => "epa",
                    "type" => $translate->_("EPA"),
                    "type_plural" => $translate->_("EPAs"),
                    "form_id" => "curriculum-tag",
                    "hide_submit_button" => false
                ));
                ?>
            </li>
            <?php if ($options["course_epas"]) : ?>
            <li id="ec-milestone-option" class="cbme-data-item">
                <?php echo $translate->_("Choose Competency and Curriculum Tag Options"); ?>
                <?php if (is_null($options["cbme_milestones"])) : ?>
                <form id="curriculum-tag-option-form" class="form-horizontal space-above medium">
                    <input id="course-id" name="course_id" type="hidden" value="<?php echo html_encode($options["course_id"]); ?>" />

                    <?php if (!(int) $options["cbme_standard_kc_ec_objectives"]) : ?>
                    <div class="control-group">
                        <label class="control-label"><?php echo $translate->_("Competency Options"); ?></label>
                        <div class="controls">
                            <label class="radio">
                                <input type="radio" name="course_cbme_standard_kc_ec_objectives" value="0"<?php echo (!(int) $options["course_cbme_standard_kc_ec_objectives"] ? " checked=\"checked\"" : ""); ?> />
                                <?php echo $translate->_("Use course specific key and enabling competencies."); ?>
                            </label>
                            <label class="radio">
                                <input type="radio" name="course_cbme_standard_kc_ec_objectives" value="1"<?php echo ((int) $options["course_cbme_standard_kc_ec_objectives"] ? " checked=\"checked\"" : ""); ?> />
                                <?php echo $translate->_("Use existing standard key and enabling competencies."); ?>
                            </label>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="control-group">
                        <label class="control-label"><?php echo $translate->_("Curriculum Tag Options"); ?></label>
                        <div class="controls">
                            <label class="radio">
                                <input type="radio" name="curriculum_tag_option" value="1" checked="checked" />
                                <?php echo $translate->_("This course will map to Milestones"); ?>
                            </label>
                            <label class="radio">
                                <input type="radio" name="curriculum_tag_option" value="0" />
                                <?php echo $translate->_("This course will map directly to Enabling Competencies"); ?>
                            </label>
                        </div>
                    </div>

                    <div class="clearfix">
                        <button id="curriculum-tag-option-btn" type="submit" class="btn btn-primary pull-right"><?php echo $translate->_("Save Competency and Curriculum Tag Options") ?></button>
                    </div>
                </form>
                <?php else : ?>
                    <div class="panel panel-icon">
                        <div class="panel-body">
                            <span class="fa fa-check-circle-o"></span>
                            <?php echo $translate->_("You have successfully selected a <strong>Curriculum Tag Option</strong>. If you wish to change this option, please contact your system administrator."); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </li>
            <?php endif; ?>
            <?php if (($options["cbme_standard_kc_ec_objectives"] == false) && ($options["course_cbme_standard_kc_ec_objectives"] == false)) : ?>
                <?php if ($options["course_epas"] && isset($options["cbme_milestones"])) : ?>
                <li id="kc-import" class="cbme-data-item">
                    <?php echo $translate->_("Import Key Competencies") ?>
                    <?php if ($options["course_key_competencies"]) : ?>
                        <div class="panel panel-icon">
                            <div class="panel-body">
                                <span class="fa fa-check-circle-o"></span>
                                <?php echo sprintf($translate->_("You have successfully imported a list of <strong>Key Competencies</strong>. If you wish to upload additional items to this list, please use the uploader below. <a href=\"%s\"><strong>Click here</strong></a> if you wish to make changes to existing Key Competencies."), ENTRADA_URL . "/admin/courses/cbme?section=edit-key-competencies&id=". html_encode($options["course_id"])); ?>
                            </div>
                        </div>
                    <?php endif;
                    $kc_uploader = new Views_Course_Cbme_ImportData_Uploader();
                    $kc_uploader->render(array(
                        "course_id" => $options["course_id"],
                        "data_method" => "upload-curriculum-tag-set",
                        "curriculum_tag_shortname" => "kc",
                        "type" => $translate->_("Key Competency"),
                        "type_plural" => $translate->_("Key Competencies"),
                        "form_id" => "key-competency",
                        "hide_submit_button" => false
                    ));
                    ?>
                </li>
                <?php endif; ?>
                <?php if ($options["course_epas"] && $options["course_key_competencies"] && isset($options["cbme_milestones"])) : ?>
                <li id="ec-import" class="cbme-data-item">
                    <?php echo $translate->_("Import Enabling Competencies"); ?>
                    <?php if ($options["course_enabling_competencies"]) : ?>
                        <div class="panel panel-icon">
                            <div class="panel-body">
                                <span class="fa fa-check-circle-o"></span>
                                <?php echo sprintf($translate->_("You have successfully imported a list of <strong>Enabling Competencies</strong>. If you wish to upload additional items to this list, please use the uploader below. <a href=\"%s\"><strong>Click here</strong></a> if you wish to make changes to existing Enabling Competencies."), ENTRADA_URL . "/admin/courses/cbme?section=edit-enabling-competencies&id=". html_encode($options["course_id"])); ?>
                            </div>
                        </div>
                    <?php endif;
                    $ec_uploader = new Views_Course_Cbme_ImportData_Uploader();
                    $ec_uploader->render(array(
                        "course_id" => $options["course_id"],
                        "data_method" => "upload-curriculum-tag-set",
                        "curriculum_tag_shortname" => "ec",
                        "type" => $translate->_("Enabling Competency"),
                        "type_plural" => $translate->_("Enabling Competencies"),
                        "form_id" => "ec-form",
                        "hide_submit_button" => false
                    ));
                    ?>
                </li>
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($options["course_epas"] && isset($options["cbme_milestones"])) : ?>
                <?php if ($options["cbme_milestones"]) : ?>
                    <?php if (((int) $options["cbme_standard_kc_ec_objectives"] || (int) $options["course_cbme_standard_kc_ec_objectives"]) || ($options["course_key_competencies"] && $options["course_enabling_competencies"])) : ?>
                    <li id="milestone-import" class="cbme-data-item">
                        <?php echo $translate->_("Import Milestones") ?>
                        <?php if ($options["course_milestones"]) : ?>
                            <div class="panel panel-icon">
                                <div class="panel-body">
                                    <span class="fa fa-check-circle-o"></span>
                                    <?php echo sprintf($translate->_("You have successfully imported a list of <strong>Milestones</strong>. If you wish to upload additional items to this list, please use the uploader below. <a href=\"%s\"><strong>Click here</strong></a> if you wish to make changes to existing Milestones."), ENTRADA_URL . "/admin/courses/cbme?section=edit-milestones&id=". html_encode($options["course_id"])); ?>
                                </div>
                            </div>
                        <?php endif;
                        $milestone_uploader = new Views_Course_Cbme_ImportData_Uploader();
                        $milestone_uploader->render(array(
                            "course_id" => $options["course_id"],
                            "data_method" => "upload-curriculum-tag-set",
                            "curriculum_tag_shortname" => "milestone",
                            "type" => $translate->_("Milestone"),
                            "type_plural" => $translate->_("Milestones"),
                            "form_id" => "milestone",
                            "hide_submit_button" => false
                        ));
                        ?>
                    </li>
                    <?php endif; ?>
                <?php else : ?>
                    <?php if (((int) $options["cbme_standard_kc_ec_objectives"] || (int) $options["course_cbme_standard_kc_ec_objectives"]) || ($options["course_key_competencies"] && $options["course_enabling_competencies"])) : ?>
                    <li id="ec-map-import" class="cbme-data-item">
                        <?php echo $translate->_("Import Enabling Competency Map") ?>
                        <?php if ($options["course_enabling_competencies"]) : ?>
                            <div class="panel panel-icon">
                                <div class="panel-body">
                                    <span class="fa fa-check-circle-o"></span>
                                    <?php echo $translate->_("You have successfully imported a map of <strong>Enabling Competencies</strong>. If you wish to add additional mappings, please upload them below."); ?>
                                </div>
                            </div>
                        <?php endif;
                        $enabling_competency_uploader = new Views_Course_Cbme_ImportData_Uploader();
                        $enabling_competency_uploader->render(array(
                            "course_id" => $options["course_id"],
                            "data_method" => "upload-ec-map",
                            "curriculum_tag_shortname" => "ec",
                            "type" => $translate->_("Enabling Competency"),
                            "type_plural" => $translate->_("Enabling Competencies"),
                            "form_id" => "enabling-competency",
                            "hide_submit_button" => false
                        ));
                        ?>
                    </li>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($options["course_epas"] && isset($options["cbme_milestones"]) && ((isset($options["course_enabling_competencies"]) && $options["course_enabling_competencies"]) || (isset($options["course_milestones"]) && $options["course_milestones"]))) : ?>
            <li id="cv-response-upload" class="cbme-data-item">
            <?php echo $translate->_("Import Contextual Variable Responses") ?>
            <?php if (isset($options["contextual_variable_responses"]) && !$options["contextual_variable_responses"]) :
                $cv_response_uploader = new Views_Course_Cbme_ImportData_Uploader();
                $cv_response_uploader->render(array(
                    "course_id" => $options["course_id"],
                    "data_method" => "upload-cv-responses",
                    "type" => $translate->_("Contextual Variable Responses"),
                    "type_plural" => $translate->_("Contextual Variable Responses"),
                    "curriculum_tag_shortname" => "contextual_variable_responses",
                    "form_id" => "contextual-variable-responses",
                    "hide_submit_button" => false
                ));
                ?>
            <?php else : ?>
                <div class="panel panel-icon">
                    <div class="panel-body">
                        <span class="fa fa-check-circle-o"></span>
                        <?php echo $translate->_("You have successfully imported a list of <strong>Contextual Variable Responses</strong>. Please click the tab above to manage your Contextual Variable Responses."); ?>
                    </div>
                </div>
            <?php endif; ?>
            </li>
            <?php endif; ?>
        </ol>
        <?php if ($ENTRADA_USER->getActiveGroup() == "medtech" && $ENTRADA_USER->getActiveRole() == "admin") : ?>
        <div id="reset-cbme-modal" class="modal hide fade">
            <form id="reset-cbme-data-form" method="POST" action="<?php echo ENTRADA_URL . "/admin/courses/cbme?section=api-cbme" ?>">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    <h3><?php echo $translate->_("Reset CBME Data") ?></h3>
                </div>
                <div class="modal-body">
                    <div id="modal-msgs" class="hide"></div>
                    <div class="alert alert-danger">
                        <p><?php echo $translate->_("<strong>Please Note</strong>: Performing this action will remove all CBME data associated with this course, including any created forms.") ?></p>
                    </div>
                    <input type="hidden" name="method" value="reset-cbme-data" />
                    <input type="hidden" name="course_id" value="<?php echo html_encode($options["course_id"]) ?>" />
                    <input type="hidden" name="organisation_id" value="<?php echo html_encode($options["organisation_id"]) ?>" />
                </div>
                <div class="modal-footer">
                    <button class="btn pull-left" data-dismiss="modal" aria-hidden="true">Close</button>
                    <a id="reset-cbme-data-btn" href="#" type="submit" class="btn btn-danger"><?php echo $translate->_("Reset CBME Data") ?></a>
                </div>
            </form>
        </div>
        <?php endif; ?>
        <?php
    }

    /**
     * @param string $entrada_url
     * @param int $course_id
     * @param string $module
     *
     * Adds required CSS and JS files to the $HEAD array and adds entry to the $BREADCRUMB array for this view.
     */
    protected function renderHead ($entrada_url, $course_id, $module) {
        global $translate, $BREADCRUMB, $HEAD;
        $BREADCRUMB[] = array("url" => $entrada_url."/admin/".$module."/cbme?".replace_query(array("section" => "curriculumtags", "id" => $course_id, "step" => false)), "title" => $translate->_("Import CBME Data"));
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $entrada_url . "/css/courses/curriculum-tags.css\" />";
        $HEAD[] = "<script type=\"text/javascript\" src=\"" . $entrada_url . "/javascript/courses/cbme/import-cbme-data.js\"></script>";
    }

    /**
     * Render a custom error message for this view.
     */
    protected function renderError() {
        global $translate;?>
        <div class="alert alert-danger">
            <strong><?php echo $translate->_("Unable to render CBME data page"); ?></strong>
        </div>
        <?php
    }
}