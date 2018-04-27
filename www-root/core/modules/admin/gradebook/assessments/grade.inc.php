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
 * Gradebook / Assessments / Grade. 
 * View the stats and student list for an assessment.
 *
 * @author Organisation: bitHeads Inc.
 * @author Developer: Jean-Benoit Lesage <jblesage@bitheads.com>
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

if ($ENTRADA_ACL->isUserAuthorized("gradebook", "update", false, array("PARENT_INCLUDED", "IN_GRADEBOOK"))) {
	if ($COURSE_ID && $ASSESSMENT_ID) {

		// Get course info
        $course = Models_Course::fetchRowByID($COURSE_ID);

        // If user can read gradebooks, generate page
		if ($course && $ENTRADA_ACL->amIAllowed(new GradebookResource($course->getID(), $course->getOrganisationID()), "read")) {

			// Add css and js to page
			array_unshift($HEAD,
				// Mark assignments modal and page-specific
				'<link href="'.ENTRADA_URL.'/css/gradebook/mark-assignment.css" rel="stylesheet" />',
				'<link href="'.ENTRADA_URL.'/css/assessments/assessments.css" rel="stylesheet" />',
                '<link href="'.ENTRADA_URL.'/css/assessments/items.css" rel="stylesheet" />',
                '<link href="'.ENTRADA_URL.'/css/assessments/rubrics.css" rel="stylesheet" />',
                '<link href="'.ENTRADA_URL.'/css/assessments/assessment-form.css" rel="stylesheet" />',

				'<script>var COURSE_ID = "'.$COURSE_ID.'", CPERIOD_ID = "'.$assessment["cperiod_id"].'", ASSESSMENT_ID = "'.$ASSESSMENT_ID.'", TITLE = "'.$course->getCourseCode().'", DELETE_EXCEPTION_TEXT = "'.$translate->_("Delete Grade Exception").'", DELETE_LINK_TITLE_TEXT = "'.$translate->_("Delete Grade Weighting Exception for").'", WEIGHTING_TEXT = "'.$translate->_("Weighting:").'", VIEW_GROUP_MEMBERS_TEXT = "'.$translate->_("View Group Members").'";</script>',

                '<script src="'.ENTRADA_URL.'/javascript/gradebook/mark-assignment.js"></script>',
				'<script src="'.ENTRADA_URL.'/javascript/gradebook/grade.js"></script>',
				'<script type="text/javascript" src="'.ENTRADA_URL.'/javascript/assessments/forms/view.js?release='.html_encode(APPLICATION_VERSION).'"></script>',

				// Graders
				'<script type="text/javascript" src="'.ENTRADA_URL.'/javascript/gradebook/graders.js"></script>',

				// DataTable
				'<script src="'.ENTRADA_URL.'/javascript/jquery/jquery.dataTables-1.10.11.min.js?release='.html_encode(APPLICATION_VERSION).'"></script>',

				// Plotkit
				'<script type="text/javascript" src="'.ENTRADA_URL.'/javascript/MochiKit/MochiKit.js"></script>',
				'<script type="text/javascript" src="'.ENTRADA_URL.'/javascript/PlotKit/excanvas.js"></script>',
				'<script type="text/javascript" src="'.ENTRADA_URL.'/javascript/PlotKit/Base.js"></script>',
				'<script type="text/javascript" src="'.ENTRADA_URL.'/javascript/PlotKit/Layout.js"></script>',
				'<script type="text/javascript" src="'.ENTRADA_URL.'/javascript/PlotKit/Canvas.js"></script>',
				'<script type="text/javascript" src="'.ENTRADA_URL.'/javascript/PlotKit/SweetCanvas.js"></script>',
				'<script type="text/javascript" src="'.ENTRADA_URL.'/javascript/PlotKit/EasyPlot.js"></script>'
			);

			// Get assessment details
			$assessment_model = new Models_Gradebook_Assessment(array("assessment_id" => $ASSESSMENT_ID));
			$assessment = $assessment_model->fetchAssessmentByIDWithMarkingSchemeMetaAndAssignment();

			$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/gradebook/assessments?".replace_query(array("section" => "grade", "id" => $COURSE_ID, "step" => false)), "title" => limit_chars($assessment["name"], 20));

			if ($assessment["form_id"]) {
				$JQUERY[] = '<script>var FORM_ID = '.$assessment["form_id"].';</script>';
			}
			
            $posts = Models_Exam_Post::fetchAllByGradeBookAssessmentID($assessment_model->getID());
            if (!empty($posts)) {
                $number_posts = count($posts);
                if ($number_posts == 1 && is_object($number_posts)) {
                    // if it's a single post make it an array for easier handling
                    $posts = array($posts);
                }

                if ($number_posts > 1 || is_array($posts)) {
                // Should be an array of posts
                    if (is_array($posts)) {
                        foreach ($posts as $post) {
                            $post_info[] = array(
                                "title"     => $post->getTitle(),
                                "type"      => $post->getTargetType(),
                                "target_id" => $post->getTargetID(),
                                "exam_id"   => $post->getExamID(),
                                "post_id"	=> $post->getID()
                            );
                        }
                    }
                }
            }

			// Get students
			if (isset($_POST["grader-filter"]) && $tmp_uploads = clean_input($_POST["grader-filter"],array("trim","int"))){
				$grader_filter = $tmp_uploads;
			} else {
				$grader_filter = 0;
			}

			if ($grader_filter) {
				$assessment_utilities = new Entrada_Utilities_Assessment_Grade(
					$assessment,
					Models_Gradebook_Assessment_Graders::fetchLearnersProxyIdByAssessmentGrader($assessment["assessment_id"], $grader_filter),
					$course->toArray()
				);
			} else {
				$assessment_utilities = new Entrada_Utilities_Assessment_Grade($assessment, $course->getStudentIDs($assessment["cperiod_id"]), $course->toArray());
			}
			$students = $assessment_utilities->getAssessmentStudents();

			// Generate page header
            $page_header = new Views_Gradebook_PageHeader(array("course" => $course, "module" => "gradebook", "page_title" => $page_title));
            
            // Get curriculum period
            $curriculum_period = new Models_Curriculum_Period(array("start_date" => $assessment["start_date"], "finish_date" => $assessment["finish_date"]));

            // Get search bar
            $search_bar = new Views_Gradebook_SearchBar(array("id" => "search-learners", "placeholder" => $translate->_("Search Learners")));

            // Get editable class for table
            $editable_class = $ENTRADA_ACL->amIAllowed(new GradebookResource($course->getID(), $course->getOrganisationID()), "update") ? "gradebook_editable" : "gradebook_not_editable";

            // Get "has-form" class for table
            $has_form_class = $assessment["form_id"] ? "has-form" : "";

            // Get dataTable
            $datatable = new Views_Gradebook_DataTable(
                array(
                    "id" => "datatable-student-list",
                    "class" => "table table-striped table-bordered ".$editable_class." ".$has_form_class,
                    "columns" => $assessment_utilities->getColumnsForDataTable(),
                    "body_data" => $students,
                    "ignore_fields" => array("b0grade_id", "c0weight", "id")
                )
            );

			$select_graders_list = Models_Gradebook_Assessment_Graders::fetchGradersForGradersList($assessment["assessment_id"], $COURSE_ID);
			$graders_filter = new Views_Gradebook_Assessments_Graders_Filter(array(
				"id" => "select-grader-filter",
				"name" => "grader-filter",
				"class" => "form-horizontal pull-right",
				"label" => $translate->_("View Graders:"),
				"graders" => $select_graders_list,
				"selected_grader" => $grader_filter
			));

            // Initiate options array with empty option
            $options = array();
            $empty_option = array(
				"text" => $translate->_("-- Select a Student --"),
				"value" => 0
			);

			if ($students) {
				$options = array_map(function($proxy_id, $student) {
            		return array(
            			"text" => $student["fullname"],
            			"value" => $proxy_id,
            			"class" => !is_null($student["c0weight"]) ? "hide" : "",
            			"id" => "exception_student_" . $proxy_id
            		);
            	}, array_keys($students), $students);
			}

			// Add empty option to top of array
			array_unshift($options, $empty_option);

            // Generate dropdown list for grade weighting exceptions
            $select_learner_grade_exceptions = new Views_Gradebook_Select(array(
            	"id" => "dropdown-learner-grade-exceptions",
            	"label" => $translate->_("Learner Name:"),
            	"options" => $options,
            	"data_attr" => array(
            		"assessment-id" => $assessment["assessment_id"]
            	)
            ));

            // Generate Modal for importing grades via CSV
            $import_csv_modal = new Views_Gradebook_Modal(array(
            	"id" => "modal-import-grades-csv",
                "title" => $translate->_("Import Grades from CSV"),
                "dismiss_button" => array(
                    "text" => $translate->_("Cancel"),
                    "class" => "pull-left"
                ),
                "success_button" => array(
                    "text" => $translate->_("Import CSV"),
                    "class" => "btn-primary btn-submit-import-grades"
                )
            ));

            // Generate Modal for importing grades via attached quiz
            $import_quiz_modal = new Views_Gradebook_Modal(array(
            	"id" => "modal-import-grades-quiz",
                "title" => $translate->_("Import Grades from quiz"),
                "dismiss_button" => array(
                    "text" => $translate->_("Cancel"),
                    "class" => "pull-left"
                ),
                "success_button" => array(
                    "text" => $translate->_("Import Grades"),
                    "class" => "btn-primary btn-submit-import-grades"
                )
            ));

            // Generate Modal for previewing an assessment form
            $preview_assessment_form_modal = new Views_Gradebook_Modal(array(
            	"id" => "modal-preview-assessment-form",
            	"class" => "modal-lg",
                "title" => $translate->_("Assessment Form Preview"),
                "dismiss_button" => array(
                    "text" => $translate->_("Close"),
                    "class" => "pull-left"
                )
            ));

            // Generate modal for marking assignments
            $mark_assignment_modal = new Views_Gradebook_Modal(array(
				"id" 	=> "modal-mark-assignment",
				"class" => "modal-mark-assignment fullscreen-modal",
				"additional_button" => array(
					"text" => $translate->_("Save and Close"),
					"class" => "btn-primary btn-save-assignment"
				),
				"success_button" => array(
					"text" => $translate->_("Save and Go to Next"),
					"class" => "btn-primary btn-save-assignment btn-save-go-to-next"
				),
				"dismiss_button" => $translate->_("Close")
			));

            // Start rendering the page with the header
            $page_header->render();
            ?>
            <div class="page-content">

            	<div class="row-fluid">
	                <div class="span6">
	                    <h1><?php echo html_encode($assessment["name"]); ?></h1>
	                </div>
	                <div class="span6">
	                    <small class="pull-right content-small margin-top-20"><?php echo $curriculum_period->getDateRangeString(); ?></small>
	                </div>
	            </div>

	            <p><?php echo $assessment["description"]; ?></p>

	            <div class="btn-toolbar">
	            	<div class="span6">
	            		<div class="btn-group">
							<?php if ($assessment["assignment_id"]) { ?>
	            				<a href="<?php echo ENTRADA_URL; ?>/admin/gradebook/assignments?section=grade&id=<?php echo $COURSE_ID;?>&assignment_id=<?php echo $assessment["assignment_id"]; ?>" class="btn btn-default"><i class="fa fa-eye"></i> <?php echo $translate->_("View Drop Box"); ?></a>
							<?php } ?>

	            			<?php if ($assessment["form_id"]) { ?>
	            				<a href="#modal-preview-assessment-form" class="btn btn-default" data-toggle="modal"><i class="fa fa-eye"></i> <?php echo $translate->_("Preview Assessment Form"); ?></a>
	            			<?php } ?>
	            		</div>
	            	</div>
	            	<div class="span6">
	            		<ul class="inline pull-right">
	            			<li>
	            				<div class="btn-group">
								  <a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
								    <?php echo $translate->_("Import / Export"); ?>
								    <span class="caret"></span>
								  </a>
								  <ul class="dropdown-menu">
								  	<?php if ($assessment["assessment_type"] == "quiz" && !empty($attached_quizzes)) { ?>
										<li>
											<a href="#modal-import-grades-quiz" id="import-quiz-button" data-toggle="modal" role="button">
												<?php echo $translate->_("Import grades from attached quiz"); ?>
											</a>
										</li>
									<?php } ?>

								    <li>
								    	<a href="#modal-import-grades-csv" id="import-csv-button" data-toggle="modal" role="button">
								    		<?php echo $translate->_("Import grades from CSV file"); ?>
								    	</a>
								    </li>
								    <li>
								    	<a href="#" id="export-csv-button" role="button">
								    		<?php echo $translate->_("Export grades to CSV file"); ?>
								    	</a>
								    </li>
								  </ul>
								</div>
	            			</li>
	            			<li>
	            				<a href="<?php echo ENTRADA_URL; ?>/admin/gradebook/assessments/?section=edit&amp;id=<?php echo $COURSE_ID; ?>&amp;assessment_id=<?php echo $ASSESSMENT_ID; ?>" class="btn btn-primary"><i class="fa fa-pencil"></i> <?php echo $translate->_("Edit Assessment"); ?></a>
	            			</li>
	            		</ul>
	            	</div>
	            </div>

	            <div class="statistics">
	            	<h2><?php echo $translate->_("Assessment Statistics"); ?></h2>

	            	<div class="row-fluid">

		            	<div class="span7">
		            		<?php $graph_data = $assessment_utilities->renderGraphDataJSON(); ?>
							<p><strong class="span6"><?php echo $translate->_("Assessment Type:"); ?></strong> <?php echo $assessment["type"]; ?> <?php echo $translate->_("Assessment"); ?></p>
							<p><strong class="span6"><?php echo $translate->_("Assessment Weighting:"); ?></strong> <?php echo $assessment["grade_weighting"]."%"; ?></p>
							<p><strong class="span6"><?php echo $translate->_("Unentered Grades:"); ?></strong> <?php echo $assessment_utilities->getUnenteredGrades(); ?></p>

							<?php if ($graph_data["mean"]) { ?>
								<p><strong class="span6"><?php echo $translate->_("Mean Grade:"); ?></strong> <?php echo $graph_data["mean"]; ?>%</p>
							<?php } ?>

							<?php if ($graph_data["median"]) { ?>
								<p><strong class="span6"><?php echo $translate->_("Median Grade:"); ?></strong> <?php echo $graph_data["median"]; ?>%</p>
							<?php } ?>

							<?php if ($graph_data["standard_deviation"]) { ?>
								<p><strong class="span6"><?php echo $translate->_("Standard Deviation"); ?></strong> <?php echo $graph_data["standard_deviation"]; ?>%</p>
							<?php } ?>
						<?php
						if ($post_info && is_array($post_info) && !empty($post_info)) {
							$post_numbers = count($post_info) - 1;
                            ?>
                            <p><strong class="span6">Post:</strong>
                                <?php
                                foreach ($post_info as $key => $post_details) {
                                    //todo update with correct link
                                    $link = "";
                                    if ($post_details["type"] === "event") {
                                        // Event
                                        $link = ENTRADA_RELATIVE . "/admin/events?rid=" . $post_details["target_id"] . "&section=content&id=" . $post_details["target_id"];
                                    } else {
                                        // Todo update link with community URL link
                                        // Community
                                    }
                                    $stats = "<a href=\"" . ENTRADA_RELATIVE . "/admin/exams/exams?section=activity&id=" . $post_details["post_id"] . "\" target=\"_blank\">";
                                    $stats .= "<img src=\"".ENTRADA_URL."/images/view-stats.gif\" width=\"16\" height=\"16\" style=\"vertical-align: middle\" border=\"0\" />";
                                    $stats .= "</a>\n";

									if ($key !== 0) {
                                        echo "<br />\n";
                                        echo "<span class=\"span6 gb-exam-spacer\">&nbsp;</span>\n";
                                    }

									echo "<a href=\"" . $link . "\" class=\"post_link\" target=\"_blank\">" . $post_details["title"] . "</a>";
									echo $stats;

								}
                            ?>
                            </p>
                            <?php
						}
                        ?>
		            	</div>

						<div class="span5">
							<div id="graph" class="pull-right"></div>
							<?php if ($graph_data) { ?>
								<script type="text/javascript" charset="utf-8">
									var data = <?php echo $graph_data["data"]; ?>;
									var plotter = PlotKit.EasyPlot(
										"<?php echo $graph_data["chart_type"]; ?>",
										{
											"xTicks": <?php echo $graph_data["xTicks"]; ?>
										},
										$("graph"),
										[data]
									);
								</script>
							<?php } ?>
						</div>

					</div>
	            </div>

	            <div class="grades">
	            	<h2><?php echo $translate->_("Grades"); ?></h2>
	            	<p><?php echo $translate->_("Percentage of assessments graded:"); ?></p>
	            	<div class="progress">
						<div class="bar" style="width: <?php echo $assessment_utilities->getEnteredGradesRatio() * 100; ?>%;"></div>
					</div>
					<div class="row-fluid">
						<div class="span6">
							<?php $search_bar->render(); ?>
						</div>
						<div class="span6 text-right">
							<?php
							$graders_filter->render();
							?>
						</div>
					</div>
					<div class="viewing-graders">
						<script type="text/javascript">
							var CPERIOD_ID = <?php echo $assessment["cperiod_id"]; ?>;
						</script>
						<h3 style="line-height: 15px;">Viewing Graders</h3>

						<div id="viewing-graders"></div>
						<?php
						$modal_body = "<div class=\"alert alert-block hide\"></div>
											<table id=\"table-modal-remove-grader\" class=\"table table-bordered table-striped\">
												<thead>
												<tr>
													<th>Grader</th>
													<th>Assigned Learners</th>
												</tr>
												</thead>
												<tbody>
			
												</tbody>
											</table>";

						$modal_remove_grader = new Views_Gradebook_Assignments_Modal(array(
							"id" => "modal-remove-grader-from-list",
							"title" => $translate->_("Remove Grader"),
							"body" => $modal_body,
							"dismiss_button" => array(
								"text" => $translate->_("Cancel"),
								"class" => "pull-left close-modal-remove-grader"
							),
							"success_button" => array(
								"text" => $translate->_("Remove Grader"),
								"class" => "pull-right btn-primary btn-modal-remove-grader-from-list"
							)
						));

						$modal_remove_grader->render();
						?>
					</div>
	            </div>

	            <?php $datatable->render(); ?>

	            <div class="grade-calculation-exceptions">
	            	<h2><?php echo $translate->_("Grade Calculation Exceptions"); ?></h2>
	            	<p><?php echo $translate->_("You can use the following exception creator to modify the calculations used to create the students final grade in this course."); ?></p>

	            	<div class="browse-learners">
	            		<?php $select_learner_grade_exceptions->render(); ?>
	            	</div>

	            	<h3><?php echo $translate->_("Learners with Modified Weighting"); ?></h3>

	            	<ul id="exception_container" class="sortableList" data-assessment-id="<?php echo $assessment["assessment_id"]; ?>"></ul>
					<div id="exception-notice" class="display-notice hide"><?php echo $translate->_("There are currently no students with custom grade weighting in the system for this assessment."); ?></div>
	            </div>

            </div>            
            <?php

            $boolean_content = $assessment["handler"] == "Boolean" ? '<p>'.$translate->_("By default, importing a Pass/Fail grade counts any numeric grade other than 0 as a Pass. Alternatively, check off the box below to select the minimum numeric grade required to be considered a pass.").'</p>
	                                <p><input type="checkbox" id="enable_grade_threshold" onclick="jQuery(\'#grade_threshold_holder\').toggle(this.checked)" name="enable_grade_threshold" value="1" /> <label for="enable_grade_threshold">'.$translate->_("Enable custom minimum passing value for imported grades").'</label></p>
	                                <div style="display: none;" id="grade_threshold_holder">
	                                	<label for="grade_threshold">'.$translate->_("Minimum Pass Value:").'</label> 
	                                	<input class="space-left" style="width: 40px;" type="text" name="grade_threshold" id="grade_threshold" value="60" />
	                                </div>' : '';

            $import_csv_modal->setBody('
            	<form enctype="multipart/form-data" action="'.ENTRADA_URL."/admin/".$MODULE."?".replace_query(array("section" => "csv-upload", "assessment_id" => $ASSESSMENT_ID)).'" method="POST">
	                <div id="display-notice-box" class="display-notice">
	                    <ul>
	                        <li>
	                            <strong>'.$translate->_("Important Notes:").'</strong>
	                            <p>'.$translate->_("Format for the CSV should be [Student Number, Grade] with each entry on a separate line (without the brackets).").'</p>
	                            <p>'.$translate->_("Any grades entered will be overwritten if present in the CSV.").'</p>

	                            '.$boolean_content.'
	                        </li>
	                    </ul>
	                </div>
					<input type="file" name="file" />
				</form>
            ');
            $import_csv_modal->render();

            $import_quiz_modal->setBody('
            	<div id="display-notice-box" class="display-notice">
					<ul>
					<li><strong>'.$translate->_("Important Notes:").'</strong><br />
						'.$translate->_("This will import the results for the attached questions from ").
						(isset($attached_quiz) && $attached_quiz ? $translate->_("the quiz").'<strong>'.$attached_quiz["quiz_title"].'</strong>' : '<strong>'.count($attached_quizzes).'</strong> '.$translate->_("attached quizzes"))
						.'.'.$translate->_("Any existing grades will be overwritten during this import process. Only students who have completed the quiz will be graded.").'</li>
					</ul>
				</div>
				<form action="'.ENTRADA_URL."/admin/".$MODULE."?".replace_query(array("section" => "import-quiz", "assessment_id" => $ASSESSMENT_ID)).'" method="POST" class="row-fluid">
					<label class="span4 form-required" for="import_type">'.$translate->_("Import action").'</label>
					<span class="span7 offset1">
						<select name="import_type" id="import_type">
							<option value="all">'.$translate->_("Average of all attempts").'</option>
							<option value="first">'.$translate->_("First attempt").'</option>
							<option value="last">'.$translate->_("Last attempt").'</option>
							<option value="best">'.$translate->_("Best (highest marked) attempt").'</option>
						</select>
					</span>
					<input type="hidden" name="course_id" value="'.$assessment["course_id"].'" />
					<input type="hidden" name="assessment_id" value="'.$assessment["assessment_id"].'" />
				</form>
            ');
			$import_quiz_modal->render();

			if ($assessment["form_id"]) {

				$is_admin = $ENTRADA_USER->getActiveRole() == "admin";
				$is_director = false;
				$course_directors = Models_Course_Contact::fetchAllByCourseIDContactType($COURSE_ID, "director");
				
			    foreach ($course_directors as $course_director) {
			        if ($course_director->getProxyID() == $ENTRADA_USER->getID()) {
			            $is_director = true;
			        }
			    }

				$preview_assessment_form_modal->render();

				// Mark assignment

				$mark_assignment_modal->setHeaderContent('<div class="selector-documents form-horizontal"></div><div class="selector-portfolio"></div>');
				$mark_assignment_modal->setBody('
					<div class="loading"><img src="'.ENTRADA_URL.'/images/loading.gif" alt="Loading..." /></div>
			      	<div class="container-fluid">
			      		<div class="file"></div>
			      		<div class="portfolio"></div>
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
								<input type="checkbox" name="custom-grade" id="custom-grade" class="custom-grade" ' . (($is_admin || $is_director) ? '' : 'disabled="disabled"') . '>
								<label for="custom-grade">Custom Grade</label>
								<input type="text" id="custom-grade-value" class="custom-grade-value" name="custom-grade-value" value="" ' . (($is_admin || $is_director) ? '' : 'disabled="disabled"') . '>
								<span class="assessment-suffix">'.assessment_suffix($assessment).'</span>
							</div>
						</li>
					</ul>
				');
				$mark_assignment_modal->render(); 
			}
			
		}
		else {
            add_error($translate->_("You do not have permission to view this Gradebook."));
            echo display_error();
            application_log("notice", $translate->_("Failed to provide a valid course identifer when attempting to view a gradebook"));
        }
	}
	else {
        add_error($translate->_("In order to edit assessments you must provide the course identifier."));
        echo display_error();
        application_log("notice", $translate->_("Failed to provide course identifer when attempting to edit a gradebook"));
    }
}
else {
	// exit if not authorized
	exit;
}