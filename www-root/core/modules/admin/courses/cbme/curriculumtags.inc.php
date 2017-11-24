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
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_CBME"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed('course', 'update', false)) {
    $ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

    $ERROR++;
    $ERRORSTR[]	= "You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    $course = Models_Course::get($COURSE_ID);

    if ($course) {
        courses_subnavigation($course->toArray(), "cbme");
    }

    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/".$MODULE."/cbme?".replace_query(array("section" => "curriculumtags", "id" => $COURSE_ID, "step" => false)), "title" => $translate->_("Import Curriculum Tags"));
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/courses/curriculum-tags.css\" />";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/courses/curriculumtags/curriculumtags.js\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.advancedsearch.js\"></script>";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/jquery/jquery.advancedsearch.css\" />";

    $curriculum_tag_set_model = new Models_ObjectiveSet();
    $curriculum_tag_sets = $curriculum_tag_set_model->fetchAllByStandardOrganisationID(0, $ENTRADA_USER->getActiveOrganisation());

    echo "<h1>". $translate->_("Manage Curriculum Tags") ."</h1>"; ?>
    <script type="text/javascript">
        var ENTRADA_URL = "<?php echo ENTRADA_URL ?>";
        jQuery(document).ready(function ($) {
            $("#curriculum-tag-sets").advancedSearch({
                filters: {
                    curriculum_tag_shortname: {
                        label: "<?php echo $translate->_("Curriculum Tag Sets"); ?>",
                        data_source: <?php echo json_encode(($curriculum_tag_sets ? $curriculum_tag_set_model->getAdvancedSearchData($curriculum_tag_sets) : "")); ?>,
                        mode: "radio",
                        selector_control_name: "curriculum_tag_shortname",
                        search_mode: false
                    }
                },
                control_class: "curriculum-tag-set-selector",
                no_results_text: "<?php echo $translate->_(""); ?>",
                parent_form: $("#curriculum-tag-form"),
                width: 300,
                modal: false
            });
        });
    </script>
    <div>
        <div class="btn-group">
            <a href="<?php echo ENTRADA_URL . "/admin/courses/cbme?id=" . html_encode($COURSE_ID) ?>" class="btn"><?php echo $translate->_("CBME Dashboard") ?></a>
            <a href="<?php echo ENTRADA_URL . "/admin/courses/cbme?section=curriculumtags&id=" . html_encode($COURSE_ID) ?>" class="btn active"><?php echo $translate->_("Import Curriculum Tags") ?></a>
        </div>
        <div class="btn-group pull-right">
            <a href="#" class="btn btn-default dropdown-toggle pull-right" data-toggle="dropdown"><?php echo $translate->_("Download Example CSV Templates") ?> <span class="caret"></span></a>
            <ul class="dropdown-menu">
                <li><a href="<?php echo ENTRADA_URL . "/admin/courses/cbme?section=api-curriculumtags&method=download-epa-csv" ?>"><?php echo $translate->_("Entrusbable Professional Activity Template") ?></a></li>
                <li><a href="<?php echo ENTRADA_URL . "/admin/courses/cbme?section=api-curriculumtags&method=download-milestone-csv" ?>"><?php echo $translate->_("Milestone Template") ?></a></li>
            </ul>
        </div>
    </div>
    <form id="curriculum-tag-form" class="form-horizontal space-above medium" enctype="multipart/form-data">
        <input id="course-id" name="course_id" type="hidden" value="<?php echo html_encode($COURSE_ID) ?>" />
        <div id="msgs" class="hide"></div>
        <div class="control-group">
            <label for="curriculum-tag-sets" class="control-label form-required"><?php echo $translate->_("Select a Curriculum Tag Set") ?></label>
            <div class="controls">
                <button class="btn btn-default" id="curriculum-tag-sets"><?php echo $translate->_("Click here to select a Curriculum Tag Set") ?> <i class="icon-chevron-down btn-icon pull-right"></i></button>
            </div>
        </div>
        <div id="upload-instructions" class="control-group">
            <div id="upload-instructions-controls" class="controls well">
                <span id="instruction-text">
                    <strong><?php echo $translate->_("Select a Curriculum Tag Set to begin.") ?></strong>
                </span>
            </div>
        </div>
        <?php
        if ($curriculum_tag_sets) {
            foreach ($curriculum_tag_sets as $curriculum_tag_set) {
                $cbme_course_objective_model = new Models_CBME_CourseObjective();
                $curriculum_tags = $cbme_course_objective_model->fetchAllByObjectiveSetIDOrgIDCourseID($curriculum_tag_set->getID(), $ENTRADA_USER->getActiveOrganisation(), $COURSE_ID);
                if ($curriculum_tags) {
                    echo "<div id=\"" . html_encode($curriculum_tag_set->getShortname() . "-container") . "\" class=\"bucket-list hide\">";
                    echo "<div class=\"alert alert-success\">";
                    echo sprintf($translate->_("You have successfully imported a list of <strong>%s</strong>. If you need to upload an updated version of this list, please contact <a href=\"mailto:healthsci.suport@queensu.ca\">Education Technology support</a>."), $curriculum_tag_set->getTitle());
                    echo "</div>";
                    switch ($curriculum_tag_set->getShortname()) {
                        case "epa": ?>
                            <table class="table table-striped table-bordered">
                                <thead>
                                <tr>
                                    <th width="15%"><?php echo $translate->_("Code") ?></th>
                                    <th width="25%"><?php echo $translate->_("Title") ?></th>
                                    <th width="30%"><?php echo $translate->_("Description") ?></th>
                                    <th width="30%"><?php echo $translate->_("Entrustment") ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                foreach ($curriculum_tags as $curriculum_tag) { ?>
                                    <tr>
                                        <td><?php echo html_encode($curriculum_tag->getCode()) ?></td>
                                        <td><?php echo html_encode($curriculum_tag->getName()) ?></td>
                                        <td><?php echo $curriculum_tag->getDescription() ? $curriculum_tag->getDescription() : "N/A" ?></td>
                                        <td><?php echo $curriculum_tag->getSecondaryDescription() ? html_encode($curriculum_tag->getSecondaryDescription()) : "N/A" ?></td>
                                    </tr>
                                    <?php
                                }
                                ?>
                                </tbody>
                            </table>
                            <?php
                            break;
                        case "milestone": ?>
                            <table class="table table-striped table-bordered">
                                <thead>
                                <tr>
                                    <th width="15%"><?php echo $translate->_("Code") ?></th>
                                    <th width="85%"><?php echo $translate->_("Title") ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                foreach ($curriculum_tags as $curriculum_tag) { ?>
                                    <tr>
                                        <td><?php echo html_encode($curriculum_tag->getCode()) ?></td>
                                        <td><?php echo html_encode($curriculum_tag->getName()) ?></td>
                                    </tr>
                                    <?php
                                }
                                ?>
                                </tbody>
                            </table>
                            <?php
                            break;
                    }
                    echo "</div>";
                }
            }
        }
        ?>
        <div id="upload-controls" class="control-group hide">
            <label class="control-label form-required"><?php echo $translate->_("Upload CSV") ?></label>
            <div class="controls well" id="curriculum-tag-upload">
                <div class="form-input">
                    <input class="form-file" type="file" name="files" id="file" />
                    <label id="file-label" for="file">
                        <?php echo $translate->_("<span class=\"input-label\">Choose a file</span><span class=\"form-dragndrop\"> or drag and drop it here</span>.") ?>
                    </label>
                </div>
                <div class="form-uploading"><?php echo $translate->_("Uploading&hellip;") ?></div>
                <div class="form-success"><?php echo $translate->_("Done") ?></div>
                <div class="form-error"><?php echo $translate->_("Error") ?></div>
            </div>
        </div>
        <div>
            <button type="submit" class="btn btn-primary pull-right"><?php echo $translate->_("Import Curriculum Tags") ?></button>
            <a href="<?php echo ENTRADA_URL . "/admin/courses/cbme?id=" . html_encode($COURSE_ID) ?>" class="btn pull-left"><?php echo $translate->_("Cancel") ?></a>
        </div>
    </form>
    <?php
}
