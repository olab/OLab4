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
 * @author Organisation: bitHeads Inc.
 * @author Developer: Jean-Benoit Lesage <jblesage@bitheads.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

if ($ENTRADA_ACL->isUserAuthorized("gradebook", "update", false, array("PARENT_INCLUDED", "IN_GRADEBOOK"))) {
    if ($COURSE_ID && $course = Models_Course::fetchRowByID($COURSE_ID)) {

        $page_title = $translate->_("Gradebook");
        $sub_title  = $translate->_("Assessments");

        // If user can read gradebooks, generate page
        if ($ENTRADA_ACL->amIAllowed(new GradebookResource($course->getID(), $course->getOrganisationID()), "read")) {
            // Add breadcrumb
            $BREADCRUMB[] = array("title" => $course->getCourseCode());
            $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/gradebook?".replace_query(array("section" => "view", "id" => $COURSE_ID, "step" => false)), "title" => $translate->_($page_title));
            $PREFERENCES = preferences_load("courses");

            $curriculum_periods = Models_Curriculum_Period::fetchAllByCurriculumTypeIDCourseID($course->getCurriculumTypeID(), $course->getID());

            if ($curriculum_periods && is_array($curriculum_periods) && count($curriculum_periods) == 1) {
                $period = $curriculum_periods[0];
                if ($period && is_object($period)) {
                    $PREFERENCES["selected_curriculum_period"] = $period->getID();
                    preferences_update("courses", $PREFERENCES);
                }
            }

            // Load css and javascript
            $HEAD[]   = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/gradebook/view.css?release=" . html_encode(APPLICATION_VERSION) . "\"/>";
            $JQUERY[] = '<script>var COURSE_ID = '.$COURSE_ID.', CPERIOD_ID = "' . $PREFERENCES["selected_curriculum_period"] . '", VIEW_ASSIGNMENT_TEXT = "'.$translate->_('View Drop Box').'", NEW_ASSIGNMENT_TEXT = "'.$translate->_('Add Drop Box').'", NO_RESULTS_MESSAGE = "'.$translate->_('No assessments found.').'", COPY_ASSESSMENTS_TEXT = "'.$translate->_('Copying...').'", DELETE_ASSESSMENTS_TEXT = "'.$translate->_('Deleting...').'";</script>';
            $JQUERY[] = '<script src="'.ENTRADA_URL.'/javascript/jquery/jquery.dataTables-1.10.11.min.js?release='.html_encode(APPLICATION_VERSION).'"></script>';
            $JQUERY[] = '<script src="'.ENTRADA_URL.'/javascript/jquery/jquery.modal.js?release='.html_encode(APPLICATION_VERSION).'"></script>';
            $JQUERY[] = '<script src="'.ENTRADA_URL.'/javascript/jquery/dataTables.rowReorder.min.js?release='.html_encode(APPLICATION_VERSION).'"></script>';
            $JQUERY[] = '<script src="'.ENTRADA_URL.'/javascript/gradebook/view.js?release='.html_encode(APPLICATION_VERSION).'"></script>';
            $JQUERY[] = '<script src="'.ENTRADA_URL.'/javascript/gradebook.js?release='.html_encode(APPLICATION_VERSION).'"></script>';
            $JQUERY[] = '<script src="'.ENTRADA_URL.'/javascript/gradebook/spreadsheet_modal.js"></script>';
            $HEAD[]   = '<link rel="stylesheet" type="text/css" href="' . ENTRADA_URL . '/css/gradebook/mark-assignment.css?release=' . html_encode(APPLICATION_VERSION) . '"/>';
            $HEAD[]   = '<link rel="stylesheet" type="text/css" href="' . ENTRADA_URL . '/css/assessments/assessments.css?release=' . html_encode(APPLICATION_VERSION) . '"/>';

            // Generate page header
            $page_header = new Views_Gradebook_PageHeader(array("course" => $course, "module" => "gradebook", "page_title" => $page_title));
            $page_header->render();

            // Get add new assignments URL
            $add_new_assignments_url = ENTRADA_URL."/admin/".$MODULE."/assessments/?".replace_query(array("section" => "add", "step" => false));

            // Get curriculum periods. If there are none, print a warning
            if ($curriculum_periods && is_array($curriculum_periods)) {
                // Get period selector; pass in the selected_curriculum_period setting
                $period_selector = new Views_Gradebook_PeriodSelector(array(
                    "id" => "select-period",
                    "course" => $course,
                    "curriculum_periods" => $curriculum_periods,
                    "class" => "pull-right form-horizontal",
                    "selected_curriculum_period" => $PREFERENCES["selected_curriculum_period"],
                    "label" => $translate->_("Period:"))
                );

                // Get search bar
                $search_bar = new Views_Gradebook_SearchBar(array(
                    "id" => "search-assessments",
                    "placeholder" => $translate->_("Search Assessments"))
                );

                // Get all records within ID array
                $assessment_model = new Models_Gradebook_Assessment();
                $assessments = $assessment_model->fetchAssessmentsByCurriculumPeriodIDWithAssignments($COURSE_ID, $PREFERENCES["selected_curriculum_period"]);

                // Get data table
                $datatable = new Views_Gradebook_DivTable(
                    array(
                        "id" => "datatable-assessments",
                        "class" => "table table-striped table-bordered",
                        "assessments" => $assessments
                    )
                );

                // Get copy assessments modal
                $copy_assessments_modal = new Views_Gradebook_Modal(array(
                    "id" => "modal-copy-assessments",
                    "title" => $translate->_("Copy Assessments"),
                    "dismiss_button" => array(
                        "text" => $translate->_("Cancel"),
                        "class" => "pull-left"
                    ),
                    "success_button" => array(
                        "text" => $translate->_("Copy Assessments"),
                        "class" => "btn-primary btn-submit-copy-assessments"
                    )
                ));

                // Get copy assessments period selector
                $copy_assessments_modal_period_selector = new Views_Gradebook_PeriodSelector(array(
                    "id" => "copy-assessments",
                    "course" => $course,
                    "curriculum_periods" => $curriculum_periods,
                    "class" => "form-horizontal",
                    "label" => $translate->_("New Target Audience:")
                ));

                // Get delete assessments modal
                $delete_assessments_modal = new Views_Gradebook_Modal(array(
                    "id" => "modal-delete-assessments",
                    "title" => $translate->_("Delete Assessments"),
                    "dismiss_button" => array(
                        "text" => $translate->_("Cancel"),
                        "class" => "pull-left"
                    ),
                    "success_button" => array(
                        "text" => $translate->_("Delete Assessments"),
                        "class" => "btn-danger btn-submit-delete-assessments"
                    )
                ));

                // Download CSV URL
                $csv_download_url = ENTRADA_URL."/admin/".$MODULE."?".replace_query(array("section" => "csv-download", "title" => $course->getCourseCode()));

                ?>
                <div class="row-fluid">
                    <div class="span6">
                        <h1 class="muted"><?php echo $page_title; ?></h1>
                    </div>
                    <div class="span6">
                        <?php $period_selector->render(); ?>
                    </div>
                </div>
                <div class="row-fluid">
                    <div class="span6">
                        <h2><?php echo $sub_title; ?> <small class="muted assessments-found hide"><span class="number-of-assessments"></span> <?php echo $translate->_("Assessments"); ?></small></h2>
                    </div>
                </div>
                <div class="row-fluid">
                    <div class="span6">
                        <?php $search_bar->render(); ?>
                    </div>
                    <div class="span6">
                        <a id="gradebook_assessment_add" href="<?php echo $add_new_assignments_url; ?>" class="btn btn-primary pull-right"><?php echo $translate->_("Add New Assessment"); ?></a>
                    </div>
                </div>
                <form id="form-assessments">
                    <?php $datatable->render(); ?>
                </form>

                <strong class="total-grade-weighting"><?php echo $translate->_("Total Weight:"); ?> <span id="grade-weighting"></span><?php echo $translate->_("%"); ?></strong>

                <div class="btn-toolbar">
                    <div class="span7">
                        <ul class="inline checkbox-buttons">
                            <li><a href="#" class="btn btn-danger btn-checkbox btn-delete-assessments disabled"><i class="fa fa-trash"></i> <?php echo $translate->_("Delete Selected"); ?></a></li>
                            <li><a href="#" class="btn btn-default btn-checkbox btn-copy-assessments disabled"><i class="fa fa-copy"></i> <?php echo $translate->_("Copy Selected"); ?></a></li>
                            <li><a href="#" class="btn btn-default btn-checkbox btn-add-assessments-to-collection disabled"><i class="fa fa-plus"></i> <?php echo $translate->_("Add To Collection"); ?></a></li>
                        </ul>
                    </div>
                    <div class="span5">
                        <ul class="inline pull-right">
                            <li><a id="fullscreen-edit" class="btn btn-default btn-sm" data-href="<?php echo ENTRADA_URL . "/admin/gradebook?" . replace_query(array("section" => "api-edit")); ?>" /><i class="fa fa-table"></i> <?php echo $translate->_("Grade Spreadsheet"); ?></a></li>
                            <li><a href="<?php echo $csv_download_url; ?>" class="btn btn-default btn-download-csv"><i class="fa fa-download"></i> <?php echo $translate->_("Export Grades"); ?></a></li>
                        </ul>
                    </div>
                </div>

                <div class="gradebook_edit" id="gradebook_spreadsheet_mark" style="display: none;"></div>

                <?php

                $spreadsheet_table_modal = new Views_Gradebook_Modal(array(
                    "id" 	=> "modal-spreadsheet-table",
                    "class" => "modal-spreadsheet-table fullscreen-modal spreadsheet-table-modal",
                    "dismiss_button" => $translate->_("Close")
                ));

                $spreadsheet_table_modal->render();

                $mark_assignment_modal = new Views_Gradebook_Modal(array(
                    "id" 	=> "modal-mark-assignment",
                    "class" => "modal-mark-assignment fullscreen-modal spreadsheet-modal",
                    "success_button" => array(
                        "text" => $translate->_("Save and Close"),
                        "class" => "btn-info btn-save-assignment"
                    ),
                    "dismiss_button" => $translate->_("Close")
                ));

                $mark_assignment_modal->setHeaderContent('<div class="selector-documents form-horizontal"></div>');
                $mark_assignment_modal->setBody('
					<div class="loading"><img src="'.ENTRADA_URL.'/images/loading.gif" alt="Loading..." /></div>
			      	<div class="container-fluid">
			      		<div class="file"></div>
			      		<div class="marking-scheme"></div>
			      	</div>
				');
                $mark_assignment_modal->setFooterContent('
					<ul class="inline">
						<li>
							<strong class="calculated-grade-text">'.$translate->_("Grade: ").'<span class="calculated-grade"></span></strong>
						</li>
						<li>
							<div class="custom-grade form-inline">
								<input type="checkbox" name="custom-grade" id="custom-grade" class="custom-grade">
								<label for="custom-grade">Custom Grade</label>
								<input type="text" id="custom-grade-value" class="custom-grade-value" name="custom-grade-value" value="">
								<span class="assessment-suffix"></span>
							</div>
						</li>
					</ul>
				');
                $mark_assignment_modal->render();

                $copy_assessments_modal->setBody('
                        <div id="copy-assessments-message-holder" class="display-generic">'.$translate->_("If you would like to create new assessments based on the selected assessments, select a valid target audience and press <strong>Copy Assessments</strong>.").'</div>'.
                    $copy_assessments_modal_period_selector->render(array(), false)
                );
                $copy_assessments_modal->render();

                $delete_assessments_modal->setBody('
                    <div class="alert alert-error alert-block">'.
                    $translate->_("Are you sure you want to delete the selected assessments? This action cannot be undone.")
                    .'</div>'
                );
                $delete_assessments_modal->render();

                // Add assessments to collections modal
                $add_to_collection_modal = new Views_Gradebook_Modal(array(
                    "id" => "modal-add-to-collection",
                    "title" => "Add Assessments to Collection",
                    "dismiss_button" => array(
                        "text" => $translate->_("Cancel"),
                        "class" => "pull-left btn-checkbox btn-cancel-add-to-collection"
                    ),
                    "success_button" => array(
                        "text" => "<span class='icon-plus'></span>Add to Collection",
                        "class" => "btn-default btn-checkbox btn-submit-add-to-collection"
                    )
                ));

                $add_to_collection_modal->setBody('
                    <div class="row-fluid modal-content assessment-collection-modal">
                        <div class="control-group">
                            <label class="control-label content-small form-required">Assessment Collection:</label>
                            <div class="controls">
                                <select id="assessment-collections-select" name="assessment-collections-select">
	                            </select>
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label content-small form-required">Collection Title:</label>
                            <div class="controls">
                                <input type="text" id="assessment-collections-title" name="assessment-collections-title" value="">
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label content-small">Collection Description:</label>
                            <div class="controls">
                                <textarea rows="4" cols="50" id="assessment-collections-desc" name="assessment-collections-desc" class="expandable"></textarea>
                            </div>
                        </div>
                        <div class="control-group">
                            <label id="add-to-collection-message"></label>
                        </div>
                        <input id="assessment-collections-id" name="assessment-collections-id" type="hidden" value="">
                    </div>
                ');

                $add_to_collection_modal->render();
                ?>

                <?php
                // Save the selected_curriculum_period setting in database `entrada_auth` table `user_preference` under module  'courses'
                preferences_update('courses', $PREFERENCES);
            } else {
                add_notice($translate->_("This course currently has no curriculum periods associated with it. This is because there are no Active Periods setup in the Course Setup section."));
                echo display_notice();
                application_log("notice", $translate->_("No curriculum periods found for a course when attempting to edit a gradebook"));
            }
        } else {
            add_error($translate->_("You do not have permission to view this Gradebook."));
            echo display_error();
            application_log("error", $translate->_("Failed to provide a valid course identifer when attempting to view a gradebook"));
        }

    } else {
        add_error($translate->_("In order to edit gradebook you must provide a valid course identifier. The provided ID does not exist in this system."));
        echo display_error();
        application_log("error", $translate->_("Failed to provide course identifer when attempting to edit a gradebook"));
    }
} else {
    // if user does not have permission, exit
    exit;
}