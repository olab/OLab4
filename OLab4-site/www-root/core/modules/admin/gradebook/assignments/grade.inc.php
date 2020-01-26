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
 * @author Developer: Brandon Thorn <brandon.thorn@queensu.ca>
 * @copyright Copyright 2012 Queen's University. All Rights Reserved.
 *
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_GRADEBOOK"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("gradebook", "update", false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	if (!isset($_SESSION[APPLICATION_IDENTIFIER]["assignments"]["sb"]) || !isset($_SESSION[APPLICATION_IDENTIFIER]["assignments"]["so"])) {
		$_SESSION[APPLICATION_IDENTIFIER]["assignments"]["sb"] = "student";
		$_SESSION[APPLICATION_IDENTIFIER]["assignments"]["so"] = "asc";
	}

	if (isset($_GET["sb"]) && $tmp_sb = clean_input($_GET["sb"],array("trim","notags"))) {
		if (in_array(strtolower($tmp_sb),array("student", "submitted", "grade", "number"))) {
			$_SESSION[APPLICATION_IDENTIFIER]["assignments"]["sb"] = $tmp_sb;
		}
	}

	if (isset($_GET["so"]) && $tmp_so = clean_input($_GET["so"],array("trim","notags"))) {
		if (in_array(strtolower($tmp_so),array("desc", "asc"))) {
			$_SESSION[APPLICATION_IDENTIFIER]["assignments"]["so"] = $tmp_so;
		}
	}

	/**
	 * Add PlotKit to the beginning of the $HEAD array.
	 */
	array_unshift($HEAD,
		// Mark Assignments Modal
		'<link href="'.ENTRADA_URL.'/css/gradebook/mark-assignment.css" rel="stylesheet" />',
		'<link href="'.ENTRADA_URL.'/css/assessments/assessments.css" rel="stylesheet" />',
		'<script src="'.ENTRADA_URL.'/javascript/gradebook/mark-assignment.js"></script>',
		'<script src="'.ENTRADA_URL.'/javascript/jquery/jquery.dataTables.min.js?release='.html_encode(APPLICATION_VERSION).'\"></script>',

		// MochiKit/PlotKit
		"<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/MochiKit/MochiKit.js\"></script>",
		"<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/PlotKit/excanvas.js\"></script>",
		"<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/PlotKit/Base.js\"></script>",
		"<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/PlotKit/Layout.js\"></script>",
		"<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/PlotKit/Canvas.js\"></script>",
		"<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/PlotKit/SweetCanvas.js\"></script>",
		"<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/PlotKit/EasyPlot.js\"></script>"
		);
		
	if ($COURSE_ID) {
		$course = Models_Course::fetchRowByID($COURSE_ID);
		$course_details	= $course->toArray();
		
		if ($course_details && $ENTRADA_ACL->amIAllowed(new GradebookResource($course_details["course_id"], $course_details["organisation_id"]), "read")) {			

			$query = "  SELECT a.*, b.`cohort`, c.`id` AS `marking_scheme_id`, c.`handler`, c.`description` as `marking_scheme_description`
                        FROM `assignments` AS a
                        JOIN `assessments` AS b
                        ON a.`assessment_id` = b.`assessment_id`
                        LEFT JOIN `assessment_marking_schemes` AS c
                        ON c.`id` = b.`marking_scheme_id`
                        WHERE a.`assignment_id` = ".$db->qstr($ASSIGNMENT_ID)."
                        AND b.`active` = 1
                        AND a.`assignment_active` = '1'";
			$assignment = $db->GetRow($query);
			if ($assignment) {
				if (isset($_GET["action"]) && $_GET["action"] == "import-zip") {
					$assignment_object = Models_Assignment::fetchRowByID($ASSIGNMENT_ID);
					if ($assignment_object) {
						if (isset($_FILES["assignment_archive"])) {
							switch ($_FILES["assignment_archive"]["error"]) {
								case 0 :
									$VALID_MAX_FILESIZE		= 31457280; // 30MB
									if (($file_filesize = (int)trim($_FILES["assignment_archive"]["size"])) <= $VALID_MAX_FILESIZE) {
											$PROCESSED["file_version"] = 1;
											$PROCESSED["file_mimetype"] = mime_content_type($_FILES["assignment_archive"]["tmp_name"]);
											$PROCESSED["file_filesize"] = $file_filesize;
											$PROCESSED["file_filename"] = useable_filename(trim($_FILES["assignment_archive"]["name"]));

										if ((!defined("FILE_STORAGE_PATH")) || (!@is_dir(FILE_STORAGE_PATH)) || (!@is_writable(FILE_STORAGE_PATH))) {
											add_error("There is a problem with the document storage directory on the server; the MEdTech Unit has been informed of this error, please try again later.");

											application_log("error", "The event document storage path [" . FILE_STORAGE_PATH . "] does not exist or is not writable.");
										} else {
											/**
											@TODO: Handle uploading files for assignments with multiple file uploads enabled. As per
											 * line 54 of the download-submission.inc.php (same directory as this), it expects there
											 * to be a zip file containing all the assignment files for each learner when it is
											 * attempting to download their assignment.
											 */
											if((@file_exists($_FILES["assignment_archive"]["tmp_name"])) && (@is_readable($_FILES["assignment_archive"]["tmp_name"]))) {
												$assignments_archive = new ZipArchive();
												$result = $assignments_archive->open($_FILES["assignment_archive"]["tmp_name"]);
												$number_of_files = $assignments_archive->numFiles;
												for ($i = 0; $i < $number_of_files; $i++) {
													if (strpos($assignments_archive->getNameIndex($i), "__MACOSX") !== 0) {
														$extension = pathinfo($assignments_archive->getNameIndex($i), PATHINFO_EXTENSION);
														$email_matches = array();
														preg_match_all("/[\.a-zA-Z0-9-+]+@[\.a-zA-Z0-9-]+/i", $assignments_archive->getNameIndex($i), $email_matches);
														$file = $assignments_archive->getFromIndex($i);
														if (is_array($email_matches) && @count($email_matches)) {
															if (isset($email_matches[0][0]) && ($email = str_replace($extension, "", $email_matches[0][0]))) {
																$email = rtrim($email, ".");
																$user = Models_User::fetchRowByEmail($email);
																if ($user) {
																	$assignment_files = Models_Assignment_File::fetchAllByAssignmentIDProxyID($ASSIGNMENT_ID, $user->getID());
																	if (@count($assignment_files) < $assignment_object->getMaxFileUploads()) {
																		$assignment_file = array();
																		$assignment_file["file_version"] = 1;
																		$assignment_file["file_type"] = "submission";
																		$assignment_file["file_title"] = useable_filename(trim($assignments_archive->getNameIndex($i)));
																		$assignment_file["assignment_id"] = $ASSIGNMENT_ID;
																		$assignment_file["proxy_id"] = $user->getID();
																		$assignment_file["file_active"] = 1;
																		$assignment_file["updated_date"] = time();
																		$assignment_file["updated_by"] = $ENTRADA_USER->getActiveID();
																		$assignment_file = new Models_Assignment_File($assignment_file);

																		if (!$assignment_file->insert()) {
																			add_error("Unable to create assignment file record.");
																		} else {
																			$assignment_file_version_array = $assignment_file->toArray();
																			$last_file_version = Models_Assignment_File_Version::fetchMostRecentByAFileID($assignment_file->getID());

																			if ($last_file_version) {
																				$assignment_file_version_array["file_version"] = $last_file_version->getFileVersion() + 1;
																			} else {
																				$assignment_file_version_array["file_version"] = 1;
																			}

																			$finfo = finfo_open(FILEINFO_MIME);
																			$mimetype = finfo_buffer($finfo, $file);
																			$assignment_file_version_array["file_mimetype"] = $mimetype;
																			$assignment_file_version_array["file_filesize"] = strlen($file);
																			$assignment_file_version_array["file_filename"] = useable_filename(trim($assignments_archive->getNameIndex($i)));
																			$assignment_file_version_array["updated_date"] = time();
																			$assignment_file_version_array["updated_by"] = $ENTRADA_USER->getActiveId();
																			$assignment_file_version = new Models_Assignment_File_Version($assignment_file_version_array);
																			if ($assignment_file_version->insert()) {
																				file_put_contents(FILE_STORAGE_PATH . "/A" . $assignment_file_version->getID(), $file);
																			} else {
																				add_error("Unable to create assignment file version record.");
																			}
																		}
																	} else {
																		add_error("The uploaded zip file contained more files than " . $assignment_object->getMaxFileUploads() . " for some students");
																	}
																}
															}
														}
													}
												}
											} else {
												add_error("There is a problem with the uploaded file; the MEdTech Unit has been informed of this error, please try again later.");

												application_log("error", "The uploaded file [" . $PROCESSED["file_filename"] . "] does not exist or is not readable.");
											}
										}
									}
									break;
								case 1 :
								case 2 :
									add_error("The file that was uploaded is larger than " . readable_size($VALID_MAX_FILESIZE) . ". Please make the file smaller and try again.");
									break;
								case 3 :
									add_error("The file that was uploaded did not complete the upload process or was interrupted; please try again.");
									break;
								case 4 :
									add_error("You did not select a file from your computer to upload. Please select a local file and try again.");
									break;
								case 6 :
								case 7 :
									add_error("Unable to store the new file on the server; the MEdTech Unit has been informed of this error, please try again later.");

									application_log("error", "Community file upload error: " . (($_FILES["filename"]["error"] == 6) ? "Missing a temporary folder." : "Failed to write file to disk."));
									break;
								default :
									application_log("error", "Unrecognized file upload error number [" . $_FILES["filename"]["error"] . "].");
									break;
							}
						}
					} else {
						echo "Assignment with provided ID not found.";
					}
				}

				$assessment_model = new Models_Gradebook_Assessment(array("assessment_id" => $assignment["assessment_id"]));
				$assessment = $assessment_model->fetchAssessmentByIDWithMarkingSchemeMetaAndAssignment();
				$assessment_utilities = new Entrada_Utilities_Assessment_Grade($assessment, $course->getStudentIDs($assessment["cperiod_id"]), $course->toArray());

				$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/gradebook/assessments?section=grade&id=" . $COURSE_ID . "&assessment_id=" . $assessment["assessment_id"], "title" => limit_chars($assessment["name"], 20));
				$BREADCRUMB[] = array("title" => $translate->_("Drop Box"));

				$COHORT = $assignment["cohort"];

				echo "<h1 id=\"page-top\">" . $course->getFullCourseTitle() . "</h1>";

				courses_subnavigation($course_details, "gradebook");

				?>
				<h1 class="muted"><?php echo $translate->_("Assignment Drop Box") ?></h1>

				<?php
				$students = $assessment_utilities->getAssessmentStudents();

				$query = "	SELECT * FROM `assessments` AS a
							JOIN `assessment_marking_schemes` AS b
							ON a.`marking_scheme_id` = b.`id`
							WHERE a.`assessment_id` = ".$db->qstr($assignment["assessment_id"])."
							AND a.`active` = 1";
				$assessment = $db->GetRow($query);
				?>

                <div class="pull-right">
                    <?php
                    if (extension_loaded("zip") && isset($students) && !(empty($students))) {
                        ?>
                        <a href="<?php echo ENTRADA_URL; ?>/admin/gradebook/assignments?section=download-submissions&assignment_id=<?php echo (int) $assignment["assignment_id"]; ?>" class="btn"><i class="icon-download-alt"></i> Download All Submissions</a>
                        <?php
                    }

					if (isset($assessment) && $assessment) {
						?>
						<a href="#upload-submissions-modal" data-toggle="modal" class="btn"><i class="icon-upload"></i> Upload Zip of Submissions</a>
						<?php
					}

                    if ($ENTRADA_ACL->amIAllowed(new CourseContentResource($course_details["course_id"], $course_details["organisation_id"]), "update")) { 
                        ?>
                        <a href="<?php echo ENTRADA_URL; ?>/admin/gradebook/assignments?<?php echo replace_query(array("section" => "edit","assignment_id"=>$assignment["assignment_id"], "step" => false)); ?>" class="btn">Edit Assignment</a>
                        <?php
                    }
                    ?>
                </div>
				<div id="upload-submissions-modal" class="modal hide fade">
					<form enctype="multipart/form-data" action="<?php echo ENTRADA_URL."/admin/gradebook/assignments?".replace_query(array("action" => "import-zip")); ?>" method="POST">
						<div class="modal-dialog">
							<div class="modal-content">
								<div class="modal-header">
									<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
									<h4 class="modal-title">Upload Learner Submissions</h4>
								</div>
								<div class="modal-body">
									<div id="upload-submissions-msgs"></div>
									<?php
									echo display_notice("Please ensure each file contained in the uploaded .zip archive contain the learner's <strong>e-mail address</strong> to indicate who it should be attributed to.");
									?>
									<input type="file" name="assignment_archive" />
								</div>
								<div class="modal-footer">
									<button type="button" class="btn btn-default pull-left" data-dismiss="modal">Close</button>
									<button id="upload-submissions" type="submit" class="btn btn-primary">Submit</button>
								</div>
							</div>
						</div>
					</form>
				</div>
				<div class="clearfix"></div>
				<?php
				if ($students && !empty($students)) {
                    ?>
					<span id="assessment_name" style="display: none;"><?php echo html_encode($assignment["assignment_title"]); ?></span>
                    <div class="row-fluid">
                        <span id="assignment_submissions" class="span12">
                            <table id ="datatable-student-list" class="table table-striped table-bordered">
								<thead>
									<tr>
										<th> Learner </th>
										<th> Student Number </th>
										<th> Grade </th>
										<th> Submitted </th>
									</tr>
								</thead>
                                <tbody>
                                    <?php
                                    foreach ($students as $key => $student) {
                                        ?>
                                        <tr id="grades<?php echo $student["id"]; ?>">
											<td>
											<a href="<?php echo ENTRADA_URL."/profile/gradebook/assignments?section=view&id=" .$COURSE_ID . "&assignment_id=".$ASSIGNMENT_ID."&pid=".$student["id"]; ?>" <?php echo (empty($student["submitted"]) ? "style='pointer-events: none; cursor: default; color:black'" : "") ?> >
												<?php echo (!isset($assignment["anonymous_marking"]) || !$assignment["anonymous_marking"] ? $student["fullname"] : "Anonymous Learner"); ?>
											</a>
											</td>
                                            <td><?php echo ($student["number"] != 0 ? $student["number"] : ""); ?></td>
											<td><?php echo $student["b0grade"]["content"]; ?></td>
											<td><?php
												if (!empty($student["submitted"])) {
														echo "<a href=" . ENTRADA_URL . "/admin/gradebook/assignments?section=download-submission&id=" . $COURSE_ID . "&assignment_id=" . $ASSIGNMENT_ID . "&sid=" . $student["id"] . "><i class='icon-download-alt'></i></a>";
												}
												echo $student["submitted"]; ?>
											</td>
                                        </tr>
                                        <?php
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </span>
                    </div>
					<script type="text/javascript">
					jQuery(document).ready(function() {
						jQuery('#datatable-student-list').dataTable({
							'bInfo': false,
							'bPaginate': false
						});
					});
					</script>
				    <?php
                } else {
					echo "<hr><div class='display-notice'>" . $translate->_("No one has submitted their assignment yet.") . "</div>";
				}
			} else {
				$ERROR++;
				$ERRORSTR[] = "In order to edit an assessment's grades you must provide a valid assignment identifier.";

				echo display_error();

				application_log("notice", "Failed to provide a valid assessment identifier when attempting to edit an assessment's grades.");
			}

		} else {
			$ERROR++;
			$ERRORSTR[] = "You don't have permission to edit this gradebook.";

			echo display_error();

			application_log("error", "User tried to edit gradebook without permission.");
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "In order to edit a course you must provide a valid course identifier.";

		echo display_error();

		application_log("notice", "Failed to provide course identifier when attempting to edit an assessment's grades.");
	}
}
?>