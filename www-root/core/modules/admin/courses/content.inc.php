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
 * Admin section of the courses module which allows
 * users with access to edit the content of a course.
 *
 * @author Organisation: Queen's University
 * @author Unit: Medical Education Technology Unit
 * @author Director: Dr. Benjamin Chen <bhc@post.queensu.ca>
 * @author Developer: Matt Simpson <simpson@post.queensu.ca>
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @version 3.0
 * @copyright Copyright 2010 Queen's University, MEdTech Unit
 *
 * $Id: content.inc.php 1169 2010-05-01 14:18:49Z simpson $
*/

if((!defined("PARENT_INCLUDED")) || (!defined("IN_COURSES"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif($ENTRADA_ACL->amIAllowed('coursecontent', 'update ', false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	if($COURSE_ID) {
		if(!isset($ORGANISATION_ID) || !$ORGANISATION_ID){
			$query = "SELECT `organisation_id` FROM `courses` WHERE `course_id` = ".$db->qstr($COURSE_ID);
			$result = $db->GetOne($query);
			if($result)
				$ORGANISATION_ID = (int)$result;
			else
				$ORGANISATION_ID = 1;
		}


		list($course_objectives,$top_level_id) = courses_fetch_objectives($ORGANISATION_ID,array($COURSE_ID),-1, 1, false, false, 0, true);
		$query			= "	SELECT * FROM `courses`
							WHERE `course_id` = ".$db->qstr($COURSE_ID)."
							AND `course_active` = '1'";
		$course_details	= $db->GetRow($query);
		if($course_details) {
			if(!$ENTRADA_ACL->amIAllowed(new CourseContentResource($course_details["course_id"], $course_details["organisation_id"]), 'update')) {
				application_log("error", "A program coordinator attempted to modify content for a course [".$COURSE_ID."] that they were not the coordinator of.");

				header("Location: ".ENTRADA_URL."/admin/".$MODULE);
				exit;
			} else {
				$BREADCRUMB[]	= array("title" => $course_details["course_code"]);
				$BREADCRUMB[]	= array("url" => ENTRADA_URL."/admin/".$MODULE."?".replace_query(array("section" => "content", "id" => $COURSE_ID)), "title" => $translate->_("Content"));

				$query	= "	SELECT a.*, b.`community_url`
							FROM `community_courses` AS a
							JOIN `communities` AS b
							ON a.`community_id` = b.`community_id`
							WHERE a.`course_id` = ".$db->qstr($COURSE_ID);
				$course_community = $db->getRow($query);

				$PROCESSED		= $course_details;
				/**
				 * If the type variable is set, there should be some work to do.
				 */
				if(isset($_POST["type"])) {
					switch($_POST["type"]) {
						case "text" :
							$PROCESSED["updated_date"]	= time();
							$PROCESSED["updated_by"]	= $ENTRADA_USER->getID();

							/**
							 * Not-Required: course_url | External Website Url
							 */
							if((isset($_POST["course_url"])) && ($tmp_input = clean_input($_POST["course_url"], array("notags", "nows"))) && ($tmp_input != "http://")) {
								$PROCESSED["course_url"] = $tmp_input;
							} else {
								$PROCESSED["course_url"] = "";
							}

							/**
							 * Redirect All traffic to External Website Url set abovee
							 */
							if((isset($_POST["course_redirect"])) && ($tmp_input = clean_input($_POST["course_redirect"], array("int"))) ) {
								$PROCESSED["course_redirect"] = $tmp_input;
							} else {
								$PROCESSED["course_redirect"] = 0;
							}

							/**
							 * Not-Required: course_description | Course Description
							 */
							if((isset($_POST["course_description"])) && (clean_input($_POST["course_description"], array("allowedtags", "nows")))) {
								$PROCESSED["course_description"] = clean_input($_POST["course_description"], array("allowedtags"));
							} else {
								$PROCESSED["course_description"] = "";
							}

							/**
							 * Not-Required: course_objectives | Course Objectives
							 */
							if((isset($_POST["course_objectives"])) && (clean_input($_POST["course_objectives"], array("allowedtags", "nows")))) {
								$PROCESSED["course_objectives"] = clean_input($_POST["course_objectives"], array("allowedtags"));
							} else {
								$PROCESSED["course_objectives"] = "";
							}

							/**
							 * Not-Required: course_message | Director's Message
							 */
							if((isset($_POST["course_message"])) && (clean_input($_POST["course_message"], array("allowedtags", "nows")))) {
								$PROCESSED["course_message"] = clean_input($_POST["course_message"], array("allowedtags"));
							} else {
								$PROCESSED["course_message"] = "";
							}

							if($db->AutoExecute("courses", $PROCESSED, "UPDATE", "`course_id` = ".$db->qstr($COURSE_ID))) {

								$SUCCESS++;
								$SUCCESSSTR[] = "You have successfully updated the <strong>".html_encode($course_details["course_name"])."</strong> " . $translate->_("course") . " details section.";

								application_log("success", "Successfully updated course_id [".$COURSE_ID."] course setup.");
							} else {
								if($db->ErrorMsg()) {
									application_log("error", "Failed to update the course page content for course_id [".$COURSE_ID."]. Database said: ".$db->ErrorMsg());
								}
							}
						break;
						case "objectives" :
							if (isset($_POST["course_objectives"]) && ($objectives = $_POST["course_objectives"]) && (is_array($objectives))) {
								foreach ($objectives as $objective => $status) {
									if ($objective) {
										if (isset($_POST["objective_text"][$objective]) && $_POST["objective_text"][$objective]) {
											$objective_text = clean_input($_POST["objective_text"][$objective], array("notags"));
										} else {
											$objective_text = false;
										}
										$PROCESSED_OBJECTIVES[$objective] = $objective_text;
									}
								}
							}

							if (is_array($PROCESSED_OBJECTIVES)) {
								foreach ($PROCESSED_OBJECTIVES as $objective_id => $objective) {
                                    $objective = Models_Course_Objective::fetchRowByCourseIDObjectiveID($COURSE_ID, $objective_id);
									if ($objective) {
                                        $objective->fromArray(array("objective_details" => $objective, "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID()))->update();
									} else {
                                        $objective = new Models_Course_Objective(array(
                                            "course_id" => $COURSE_ID, 
                                            "objective_id" => $objective_id, 
                                            "objective_details" => $objective, 
                                            "objective_start" => time(), 
                                            "importance" => 0, 
                                            "updated_date" => time(), 
                                            "updated_by" => $ENTRADA_USER->getID(),
                                            "active" => "1"
                                        ));
										$objective->insert();
									}

								}
							}

							$SUCCESS++;
							$SUCCESSSTR[] = "You have successfully updated the <strong>".html_encode($course_details["course_name"])."</strong> " . $translate->_("course") . " objectives section.";

							application_log("success", "Successfully updated course_id [".$COURSE_ID."] course objectives.");
						break;
						case "files" :
							$FILE_IDS = array();

							if((!isset($_POST["delete"])) || (!is_array($_POST["delete"])) || (!@count($_POST["delete"]))) {
								$ERROR++;
								$ERRORSTR[] = "You must select at least 1 file to delete by checking the checkbox to the left the file title.";

								application_log("notice", "User pressed the Delete file button without selecting any files to delete.");
							} else {
								foreach($_POST["delete"] as $file_id) {
									$file_id = (int) trim($file_id);
									if($file_id) {
										$FILE_IDS[] = (int) trim($file_id);
									}
								}

								if(!@count($FILE_IDS)) {
									$ERROR++;
									$ERRORSTR[] = "There were no valid file identifiers provided to delete.";
								} else {
									foreach($FILE_IDS as $file_id) {
										$query	= "SELECT * FROM `course_files` WHERE `id`=".$db->qstr($file_id)." AND `course_id`=".$db->qstr($COURSE_ID);
										$sresult	= $db->GetRow($query);
										if($sresult) {
											$query = "DELETE FROM `course_files` WHERE `id`=".$db->qstr($file_id)." AND `course_id`=".$db->qstr($COURSE_ID);
											if($db->Execute($query)) {
												if($db->Affected_Rows()) {
													if(@unlink(FILE_STORAGE_PATH."/C".$file_id)) {
														$SUCCESS++;
														$SUCCESSSTR[] = "Successfully deleted ".$sresult["file_name"]." from this course.";

														application_log("success", "Deleted ".$sresult["file_name"]." [ID: ".$file_id."] from filesystem.");
													}

													application_log("success", "Deleted ".$sresult["file_name"]." [ID: ".$file_id."] from database.");
												} else {
													application_log("error", "Trying to delete ".$sresult["file_name"]." [ID: ".$file_id."] from database, but there were no rows affected. Database said: ".$db->ErrorMsg());
												}
											} else {
												$ERROR++;
												$ERRORSTR[] = "We are unable to delete ".$sresult["file_name"]." from the course at this time. The system administrator has been informed of the error, please try again later.";

												application_log("error", "Trying to delete ".$sresult["file_name"]." [ID: ".$file_id."] from database, but the execute statement returned false. Database said: ".$db->ErrorMsg());
											}
										}
									}
								}
							}
						break;
						case "links" :
							$LINK_IDS = array();

							if((!isset($_POST["delete"])) || (!is_array($_POST["delete"])) || (!@count($_POST["delete"]))) {
								$ERROR++;
								$ERRORSTR[] = "You must select at least 1 link to delete by checking the checkbox to the left the link.";

								application_log("notice", "User pressed the Delete link button without selecting any files to delete.");
							} else {
								foreach($_POST["delete"] as $link_id) {
									$link_id = (int) trim($link_id);
									if($link_id) {
										$LINK_IDS[] = (int) trim($link_id);
									}
								}

								if(!@count($LINK_IDS)) {
									$ERROR++;
									$ERRORSTR[] = "There were no valid link identifiers provided to delete.";
								} else {
									foreach($LINK_IDS as $link_id) {
										$query	= "SELECT * FROM `course_links` WHERE `id`=".$db->qstr($link_id)." AND `course_id`=".$db->qstr($COURSE_ID);
										$sresult	= $db->GetRow($query);
										if($sresult) {
											$query = "DELETE FROM `course_links` WHERE `id`=".$db->qstr($link_id)." AND `course_id`=".$db->qstr($COURSE_ID);
											if($db->Execute($query)) {
												if($db->Affected_Rows()) {
													application_log("success", "Deleted course ".$sresult["link"]." [ID: ".$link_id."] from database.");
												} else {
													application_log("error", "Trying to delete course ".$sresult["link"]." [ID: ".$link_id."] from database, but there were no rows affected. Database said: ".$db->ErrorMsg());
												}
											} else {
												$ERROR++;
												$ERRORSTR[] = "We are unable to delete ".$sresult["link"]." from the course at this time. The system administrator has been informed of the error, please try again later.";

												application_log("error", "Trying to delete course ".$sresult["link"]." [ID: ".$link_id."] from database, but the execute statement returned false. Database said: ".$db->ErrorMsg());
											}
										}
									}
								}
							}
						break;
                        case "lti":
                            $LTI_IDS = array();

                            if((!isset($_POST["delete"])) || (!is_array($_POST["delete"])) || (!@count($_POST["delete"]))) {
                                $ERROR++;
                                $ERRORSTR[] = "You must select at least 1 LTI Provider to delete by checking the checkbox to the left the LTI Provider.";

                                application_log("notice", "User pressed the Delete LTI Provider button without selecting any files to delete.");
                            } else {
                                foreach($_POST["delete"] as $lti_id) {
                                    $lti_id = (int) trim($lti_id);
                                    if($lti_id) {
                                        $LTI_IDS[] = (int) trim($lti_id);
                                    }
                                }

                                if(!@count($LTI_IDS)) {
                                    $ERROR++;
                                    $ERRORSTR[] = "There were no valid LTI Provider identifiers provided to delete.";
                                } else {
                                    foreach($LTI_IDS as $lti_id) {
                                        $query	= "SELECT * FROM `course_lti_consumers` WHERE `id`=".$db->qstr($lti_id)." AND `course_id`=".$db->qstr($COURSE_ID);
                                        $sresult	= $db->GetRow($query);
                                        if($sresult) {
                                            $query = "DELETE FROM `course_lti_consumers` WHERE `id`=".$db->qstr($lti_id)." AND `course_id`=".$db->qstr($COURSE_ID);
                                            if($db->Execute($query)) {
                                                if($db->Affected_Rows()) {
                                                    application_log("success", "Deleted course ".$sresult["lti_title"]." [ID: ".$lti_id."] from database.");
                                                } else {
                                                    application_log("error", "Trying to delete course ".$sresult["lti_title"]." [ID: ".$lti_id."] from database, but there were no rows affected. Database said: ".$db->ErrorMsg());
                                                }
                                            } else {
                                                $ERROR++;
                                                $ERRORSTR[] = "We are unable to delete ".$sresult["lti_title"]." from the course at this time. The system administrator has been informed of the error, please try again later.";

                                                application_log("error", "Trying to delete course ".$sresult["lti_title"]." [ID: ".$link_id."] from database, but the execute statement returned false. Database said: ".$db->ErrorMsg());
                                            }
                                        }
                                    }
                                }
                            }
                            break;
                            default :
                            continue;
					}
				}

				/**
				 * Load the rich text editor.
				 */
				load_rte();

				$OTHER_DIRECTORS	= array();

				$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/elementresizer.js\"></script>";
				$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/wizard.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
				$HEAD[] = "<link href=\"".ENTRADA_URL."/css/wizard.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
				?>

				<iframe id="upload-frame" name="upload-frame" onload="frameLoad()" style="display: none;"></iframe>
				<a id="false-link" href="#placeholder"></a>
				<div id="placeholder" style="display: none"></div>
				<script type="text/javascript">
				var ajax_url = '';
				var modalDialog;
				document.observe('dom:loaded', function() {
					modalDialog = new Control.Modal($('false-link'), {
						position:		'center',
						overlayOpacity:	0.75,
						closeOnClick:	'overlay',
						className:		'modal',
						fade:			true,
						fadeDuration:	0.30,
						beforeOpen: function(request) {
							eval($('scripts-on-open').innerHTML);
						},
						afterClose: function() {
							if (uploaded == true) {
									window.location = '<?php echo ENTRADA_URL."/admin/courses?".replace_query(); ?>';
							}
						}
					});
				});

				function openDialog (url) {
					if (url && url != ajax_url) {
						ajax_url = url;
						new Ajax.Request(ajax_url, {
							method: 'get',
							onComplete: function(transport) {
								modalDialog.container.update(transport.responseText);
								modalDialog.open();
							}
						});
					} else {
						$('scripts-on-open').update();
						modalDialog.open();
					}
				}

				function confirmFileDelete() {
					ask_user = confirm("Press OK to confirm that you would like to delete the selected file or files from this course, otherwise press Cancel.");

					if (ask_user == true) {
						$('file-listing').submit();
					} else {
						return false;
					}
				}

				function confirmLinkDelete() {
					ask_user = confirm("Press OK to confirm that you would like to delete the selected link or links from this course, otherwise press Cancel.");

					if (ask_user == true) {
						$('link-listing').submit();
					} else {
						return false;
					}
				}

                function confirmLTIDelete() {
                    ask_user = confirm("Press OK to confirm that you would like to delete the selected LTI Provider or LTI Providers from this course, otherwise press Cancel.");

                    if (ask_user == true) {
                        $('lti-listing').submit();
                    } else {
                        return false;
                    }
                }

				var text = new Array();

				function objectiveClick(element, id, default_text) {
					if (element.checked) {
						var textarea = document.createElement('textarea');
						textarea.name = 'objective_text['+id+']';
						textarea.id = 'objective_text_'+id;
						if (text[id] != null) {
							textarea.innerHTML = text[id];
						} else {
							textarea.innerHTML = default_text;
						}
						textarea.className = "expandable objective";
						$('objective_'+id+"_append").insert({after: textarea});
						setTimeout('jQuery("#objective_text_'+id+'").textareaAutoSize();', 100);
					} else {
						if ($('objective_text_'+id)) {
							text[id] = $('objective_text_'+id).value;
							$('objective_text_'+id).remove();
						}
					}
				}
				</script>
				<?php

				$sub_query		= "SELECT `proxy_id` FROM `course_contacts` WHERE `course_contacts`.`course_id`=".$db->qstr($COURSE_ID)." AND `course_contacts`.`contact_type` = 'director' ORDER BY `contact_order` ASC";
				$sub_results	= $db->GetAll($sub_query);
				if($sub_results) {
					foreach($sub_results as $sub_result) {
						$OTHER_DIRECTORS[] = $sub_result["proxy_id"];
					}
				}
				require_once(ENTRADA_ABSOLUTE."/javascript/courses.js.php");

				$course = Models_Course::get($COURSE_ID);

				echo "<h1 id=\"page-top\">" . $course->getFullCourseTitle() . "</h1>";

				courses_subnavigation($course_details,"content");

				echo "<h1 class=\"muted\">Content</h1>";

				if($SUCCESS) {
					echo display_success();
				}

				if($NOTICE) {
					echo display_notice();
				}

				if($ERROR) {
					echo display_error();
				}
				?>
				<a name="course-details-section"></a>
				<h2 title="Course Setup Section"><?php echo $translate->_("Course Setup"); ?></h2>
				<div id="course-setup-section" class="clearfix">
					<form class="form-horizontal" action="<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>?<?php echo replace_query(); ?>" method="post">
						<input type="hidden" name="type" value="text" />
						<div class="control-group">
							<label for="course_url" class="form-nrequired control-label">External Website URL</label>
							<div class="controls">
								<?php
								if (!$course_community) { ?>
									<input type="text" id="course_url" name="course_url" value="<?php echo ((isset($PROCESSED["course_url"]) && ($PROCESSED["course_url"] != "")) ? html_encode($PROCESSED["course_url"]) : "http://"); ?>" class="span11"/>
									<?php
								} else { ?>
									<a href="<?php echo ENTRADA_URL."/community" . $course_community["community_url"];?>">
										<?php echo ENTRADA_URL."/community" . $course_community["community_url"];?>
									</a>
								<?php
								} ?>
							</div>
						</div>

						<?php if (!$course_community): ?>
						<div class="control-group">
							<div class="controls">
								<label for="course_redirect" class="checkbox form-nrequired"><?php echo $translate->_("Redirect all course clicks to External Website (i.e. bypass Course Dashboard)");?>
									<input type="checkbox" id="course_redirect" name="course_redirect" value="1"<?php echo (isset($PROCESSED["course_redirect"]) && $PROCESSED["course_redirect"]) ? " checked" : ""; ?>>
								</label>
							</div>
						</div>
						<?php endif; ?>

						<div class="control-group">
							<label for="course_directors" class="form-nrequired control-label"><?php echo $translate->_("Course Directors"); ?></label>
							<div class="controls">
								<?php
									$squery		= "	SELECT a.`proxy_id`, CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `fullname`, b.`email`
													FROM `course_contacts` AS a
													LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
													ON b.`id` = a.`proxy_id`
													WHERE a.`course_id` = ".$db->qstr($course_details["course_id"])."
													AND a.`contact_type` = 'director'
													AND b.`id` IS NOT NULL
													ORDER BY a.`contact_order` ASC";
									$results	= $db->GetAll($squery);
									if($results) {
										foreach($results as $key => $sresult) {
											echo "<a href=\"mailto:".html_encode($sresult["email"])."\">".html_encode($sresult["fullname"])."</a><br />\n";
										}
									} else {
										echo "To Be Announced";
									}
								?>
							</div>
						</div>

						<div class="control-group">
							<label for="curriculum_coordinators" class="form-nrequired control-label"><?php echo $translate->_("Curriculum Coordinators"); ?></label>
							<div class="controls">
								<?php
									$squery		= "	SELECT a.`proxy_id`, CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `fullname`, b.`email`
													FROM `course_contacts` AS a
													LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
													ON b.`id` = a.`proxy_id`
													WHERE a.`course_id` = ".$db->qstr($course_details["course_id"])."
													AND a.`contact_type` = 'ccoordinator'
													AND b.`id` IS NOT NULL
													ORDER BY a.`contact_order` ASC";
									$results	= $db->GetAll($squery);
									if($results) {
										foreach($results as $key => $sresult) {
											echo "<a href=\"mailto:".html_encode($sresult["email"])."\">".html_encode($sresult["fullname"])."</a><br />\n";
										}
									} else {
										echo "To Be Announced";
									}
								?>
							</div>
						</div>

						<div class="control-group">
							<label for="associated_faculty" class="form-nrequired control-label"><?php echo $translate->_("Associated Faculty"); ?></label>
							<div class="controls">
								<?php
								$query		= "	SELECT a.`proxy_id`, CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `fullname`, b.`email`
													FROM `course_contacts` AS a
													LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
													ON b.`id` = a.`proxy_id`
													WHERE a.`course_id` = ".$db->qstr($course_details["course_id"])."
													AND a.`contact_type` = 'faculty'
													AND b.`id` IS NOT NULL
													ORDER BY a.`contact_order` ASC";
								$results	= $db->GetAll($query);
								if($results) {
									foreach($results as $key => $sresult) {
										echo "<a href=\"mailto:".html_encode($sresult["email"])."\">".html_encode($sresult["fullname"])."</a><br />\n";
									}
								} else {
									echo "To Be Announced";
								}
								?>
							</div>
						</div>

						<?php
						if (isset($course_details["pcoord_id"]) && (int)$course_details["pcoord_id"]) { ?>
						<div class="control-group">
							<label for="program_coordinator" class="form-nrequired control-label"><?php echo $translate->_("Program Coordinator"); ?></label>
							<div class="controls">
								<a href="mailto: <?php echo get_account_data("email", $course_details["pcoord_id"]);?>">
									<?php echo get_account_data("wholename", $course_details["pcoord_id"]);?>
								</a>
							</div>
						</div>
						<?php
						}

						if (isset($course_details["evalrep_id"]) && (int)$course_details["evalrep_id"]) { ?>
						<div class="control-group">
							<label for="evaluation_rep" class="form-nrequired control-label"><?php echo $translate->_("Evaluation Rep"); ?></label>
							<div class="controls">
								<a href="mailto: <?php echo get_account_data("email", $course_details["evalrep_id"]);?>">
									<?php echo get_account_data("wholename", $course_details["evalrep_id"]);?>
								</a>
							</div>
						</div>
						<?php
						}

						if (isset($course_details["studrep_id"]) && (int)$course_details["studrep_id"]) { ?>
						<div class="control-group">
							<label for="evaluation_rep" class="form-nrequired control-label"><?php echo $translate->_("Student Rep"); ?></label>
							<div class="controls">
								<a href="mailto: <?php echo get_account_data("email", $course_details["studrep_id"]);?>">
									<?php echo get_account_data("wholename", $course_details["studrep_id"]);?>
								</a>
							</div>
						</div>
						<?php
						} ?>

						<div class="control-group">
							<label for="course_description" class="form-nrequired control-label"><?php echo $translate->_("course") . " Description";?></label>
							<div class="controls">
								<textarea id="course_description" name="course_description" cols="70" rows="10"><?php echo ((isset($PROCESSED["course_description"])) ? html_encode(trim(strip_selected_tags($PROCESSED["course_description"], array("font")))) : "");?></textarea>
							</div>
						</div>

						<div class="control-group">
							<label for="course_message" class="form-nrequired control-label">Director's Message</label>
							<div class="controls">
								<textarea id="course_message" name="course_message" cols="70" rows="10"><?php echo ((isset($PROCESSED["course_message"])) ? html_encode(trim(strip_selected_tags($PROCESSED["course_message"], array("font")))) : "");?></textarea>
							</div>
						</div>
						<div class="pull-right clearfix">
							<input type="submit" value="Save" class="btn btn-primary"/>
						</div>
					</form>
				</div>

				<?php
				$query = "	SELECT COUNT(*) FROM course_objectives WHERE course_id = ".$db->qstr($COURSE_ID);
				$result = $db->GetOne($query);


				if ($result) {
					?>
				<a name="course-objectives-section"></a>
				<h2 title="<?php echo $translate->_("Course Objectives Section"); ?>"><?php echo $translate->_("course"); ?> <?php echo $translate->_("Objectives"); ?></h2>
				<div id="course-objectives-section">
					<form action="<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>?<?php echo replace_query(); ?>" method="post">
					<input type="hidden" name="type" value="objectives" />
					<input type="hidden" id="objectives_head" name="course_objectives" value="" />
						<?php
						if (is_array($course_objectives["primary_ids"])) {
							foreach ($course_objectives["primary_ids"] as $objective_id) {
								echo "<input type=\"hidden\" class=\"primary_objectives\" id=\"primary_objective_".$objective_id."\" name=\"primary_objectives[]\" value=\"".$objective_id."\" />\n";
							}
						}
						if (is_array($course_objectives["secondary_ids"])) {
							foreach ($course_objectives["secondary_ids"] as $objective_id) {
								echo "<input type=\"hidden\" class=\"secondary_objectives\" id=\"secondary_objective_".$objective_id."\" name=\"secondary_objectives[]\" value=\"".$objective_id."\" />\n";
							}
						}

						$results = Models_Course_Objective::fetchAllByOrganisationIDCourseID($ENTRADA_USER->getActiveOrganisation(), $COURSE_ID);

					if ($results) { ?>
						<h3>Clinical Presentations</h3>
						<ul class="objectives">
						<?php
						foreach ($results as $result) {
							if ($result["objective_name"]) { ?>
								<li><?php echo $result["objective_name"];?></li>
							<?php
							}
						} ?>
						</ul>


					<?php
					} ?>
                        <div class="clear_both"></div>
						<div id="objectives_list">
							<h3><?php echo $translate->_("Curriculum Objectives"); ?></h3>
							<strong>The learner will be able to:</strong>
							<?php echo event_objectives_in_list($course_objectives, $top_level_id, $top_level_id, true, false, 1, true, true, "primary", false, $COURSE_ID); ?>
						</div>
						<div class="clearfix pull-right">
							<input type="submit" value="Save" class="btn btn-primary" />
						</div>
					</form>
				</div>
					<?php
					if ((@is_array($edit_ajax)) && (@count($edit_ajax))) {
						echo "<script type=\"text/javascript\">\n";
						foreach ($edit_ajax as $objective_id) {
							echo "var editor_".$objective_id." = new Ajax.InPlaceEditor('objective_description_".$objective_id."', '".ENTRADA_RELATIVE."/api/objective-details.api.php', { rows: 7, cols: 62, okText: \"Save Changes\", cancelText: \"Cancel Changes\", externalControl: \"edit_mode_".$objective_id."\", submitOnBlur: \"true\", callback: function(form, value) { jQuery('#clear_objective_".$objective_id."').toggle((value.length !== 0 && value.trim())); return 'id=".$objective_id."&cids=".$COURSE_ID."&objective_details='+escape(value) } });\n";
						}
						echo "</script>\n";
					}
				}
				?>
                <div class="clearfix"></div>
				<a name="course-resources-section"></a>
				<h2 title="Course Resources Section"><?php echo $translate->_("course"); ?> Resources</h2>
				<div id="course-resources-section">
					<div class="space-bottom medium">
						<div class="clearfix">
							<div class="pull-left space-bottom">
								<h3>Attached Files</h3>
							</div>
							<div class="pull-right space-bottom">
								<a href="#page-top" onclick="openDialog('<?php echo ENTRADA_URL; ?>/api/file-wizard-course.api.php?action=add&id=<?php echo $COURSE_ID; ?>')" class="btn btn-primary">Add A File</a>
							</div>
						</div>

						<?php
						$query		= "SELECT * FROM `course_files` WHERE `course_id`=".$db->qstr($COURSE_ID)." ORDER BY `file_category` ASC, `file_title` ASC";
						$results	= ((USE_CACHE) ? $db->CacheGetAll(CACHE_TIMEOUT, $query) : $db->GetAll($query));
						?>
						<form id="file-listing" action="<?php echo ENTRADA_URL;?>/admin/<?php echo $MODULE."?".replace_query();?>" method="post">
							<input type="hidden" name="type" value="files" />
							<table class="tableList" cellspacing="0" summary="List of Files">
								<colgroup>
									<col class="modified wide"/>
									<col class="file-category" />
									<col class="title" />
									<col class="date" />
									<col class="date" />
									<col class="accesses" />
								</colgroup>
								<thead>
									<tr>
										<td class="modified">&nbsp;</td>
										<td class="file-category sortedASC"><div class="noLink">Category</div></td>
										<td class="title">File Title</td>
										<td class="date-small">Accessible Start</td>
										<td class="date-small">Accessible Finish</td>
										<td class="accesses">Saves</td>
									</tr>
								</thead>
								<tfoot>
									<tr class="space-above">
										<td>&nbsp;</td>
										<td colspan="5" style="padding-top: 10px">
										<?php
										echo (($results) ? "<input type=\"button\" class=\"btn btn-danger\" value=\"Delete Selected\" onclick=\"confirmFileDelete()\" />" : "&nbsp;");
										?>
										</td>
									</tr>
								</tfoot>
								<tbody>
						<?php
						if($results) {
							foreach($results as $result) {
								$filename	= $result["file_name"];
								$parts		= pathinfo($filename);
								$ext		= $parts["extension"];
								?>
									<tr id="file-<?php echo $result["id"];?>">
										<td class="modified wide">
											<input type="checkbox" name="delete[]" value="<?php echo $result["id"];?>"/>
											<a href="<?php echo ENTRADA_URL;?>/file-course.php?id=<?php echo $result["id"];?>">
												<img 	src="<?php echo ENTRADA_URL;?>/images/btn_save.gif"
														width="16"
														height="16"
														alt="Download <?php echo html_encode($result["file_name"]);?> to your computer."
														title="Download <?php echo html_encode($result["file_name"]);?> to your computer."
														border="0" />
											</a>
										</td>
										<td class="file-category">
											<?php echo ((isset($RESOURCE_CATEGORIES["course"][$result["file_category"]])) ? html_encode($RESOURCE_CATEGORIES["course"][$result["file_category"]]) : "Unknown Category");?>
										</td>
										<td class="title">
											<img 	src="<?php echo ENTRADA_URL;?>/serve-icon.php?ext=<?php echo $ext;?>"
													width="16"
													height="16"
													alt="<?php echo strtoupper($ext);?> Document"
													title="<?php echo strtoupper($ext);?> Document"/>
											<a 	href="#"
												onclick="openDialog('<?php echo ENTRADA_URL;?>/api/file-wizard-course.api.php?action=edit&id=<?php echo $COURSE_ID."&fid=".$result["id"];?>')"
												title="Click to edit <?php echo html_encode($result["file_title"]);?>">
												<strong>
												<?php echo html_encode($result["file_title"]);?>
												</strong>
											</a>
										</td>
										<td class="date-small">
											<span class="content-date">
												<?php echo (((int) $result["valid_from"]) ? date(DEFAULT_DATE_FORMAT, $result["valid_from"]) : "No Restrictions");?>
											</span>
										</td>
										<td class="date-small">
											<span class="content-date">
												<?php echo (((int) $result["valid_until"]) ? date(DEFAULT_DATE_FORMAT, $result["valid_until"]) : "No Restrictions");?>
											</span>
										</td>
										<td class="accesses text-center"><?php echo $result["accesses"];?></td>
									</tr>
							<?php
							}
						} else { ?>
									<tr>
										<td colspan="6">
											<div class="well well-small content-small">
												There have been no files added to this <?php echo strtolower($translate->_("course"));?>. To <strong>add a new file</strong>, simply click the Add File button.
											</div>
										</td>
									</tr>
						<?php
						} ?>
								</tbody>
							</table>
						</form>
					</div>

					<div class="space-bottom medium">
						<div class="clearfix">
						<div class="pull-left space-below">
							<h3>Attached Links</h3>
						</div>
						<div class="pull-right space-below">
							<a 	href="#page-top"
								onclick="openDialog('<?php echo ENTRADA_URL; ?>/api/link-wizard-course.api.php?action=add&id=<?php echo $COURSE_ID; ?>')"
								class="btn btn-primary">
								Add A Link
							</a>
						</div>
						</div>
						<?php
						$query		= "SELECT * FROM `course_links` WHERE `course_id`=".$db->qstr($COURSE_ID)." ORDER BY `link_title` ASC";
						$results	= ((USE_CACHE) ? $db->CacheGetAll(CACHE_TIMEOUT, $query) : $db->GetAll($query));
						?>
						<form id="link-listing" action="<?php echo ENTRADA_URL."/admin/".$MODULE."?".replace_query();?>" method="post">
							<input type="hidden" name="type" value="links" />
							<table class="tableList" cellspacing="0" summary="List of Linked Resources">
								<colgroup>
									<col class="modified wide"/>
									<col class="title" />
									<col class="date" />
									<col class="date" />
									<col class="accesses" />
								</colgroup>
								<thead>
									<tr>
										<td class="modified">&nbsp;</td>
										<td class="title sortedASC"><div class="noLink">Linked Resource</div></td>
										<td class="date-small">Accessible Start</td>
										<td class="date-small">Accessible Finish</td>
										<td class="accesses">Hits</td>
									</tr>
								</thead>
								<tfoot>
									<tr>
										<td>&nbsp;</td>
										<td colspan="4" style="padding-top: 10px">
											<?php
											echo (($results) ? "<input type=\"button\" class=\"btn btn-danger\" value=\"Delete Selected\" onclick=\"confirmLinkDelete()\" />" : "&nbsp;")."\n";
											?>
										</td>
									</tr>
								</tfoot>
								<tbody>
							<?php
							if($results) {
								foreach($results as $result) { ?>
									<tr>
										<td class="modified wide">
											<input type="checkbox" name="delete[]" value="<?php echo $result["id"];?>"/>
											<a href="<?php echo ENTRADA_URL;?>/link-course.php?id=<?php echo $result["id"];?>" target="_blank">
												<img 	src="<?php echo ENTRADA_URL;?>/images/url-visit.gif"
														width="16"
														height="16"
														alt="Visit <?php echo html_encode($result["link"]);?>"
														title="Visit <?php echo html_encode($result["link"]);?>"
														border="0" />
											</a>
										</td>
										<td class="title">
											<a 	href="#"
												onclick="openDialog('<?php echo ENTRADA_URL;?>/api/link-wizard-course.api.php?action=edit&id=<?php echo $COURSE_ID."&lid=".$result["id"];?>')"
												title="Click to edit <?php echo html_encode($result["link"]);?>">
												<strong>
												<?php echo (($result["link_title"] != "") ? html_encode($result["link_title"]) : $result["link"]);?>
												</strong>
											</a>
										</td>
										<td class="date-small">
											<span class="content-date">
												<?php echo (((int) $result["valid_from"]) ? date(DEFAULT_DATE_FORMAT, $result["valid_from"]) : "No Restrictions");?>
											</span>
										</td>
										<td class="date-small">
											<span class="content-date">
												<?php echo (((int) $result["valid_until"]) ? date(DEFAULT_DATE_FORMAT, $result["valid_until"]) : "No Restrictions");?>
											</span>
										</td>
										<td class="accesses text-center">
											<?php echo $result["accesses"];?>
										</td>
									</tr>
								<?php
								}
							} else { ?>
									<tr>
										<td colspan="5">
											<div class="well well-small content-small">
												There have been no links added to this <?php echo strtolower($translate->_("course"));?>. To <strong>add a new link</strong>, simply click the Add Link button.
											</div>
										</td>
									</tr>
							<?php
							} ?>
								</tbody>
							</table>
						</form>
					</div>
                    <div class="space-bottom medium">
                        <div class="clearfix">
                            <div class="pull-left space-below">
                                <h3>Attached LTI Providers</h3>
                            </div>
                            <div class="pull-right space-below">
                                <a 	href="#page-top"
                                      onclick="openDialog('<?php echo ENTRADA_URL; ?>/api/lti-wizard-course.api.php?action=add&id=<?php echo $COURSE_ID; ?>')"
                                      class="btn btn-primary">
                                    Add LTI Provider
                                </a>
                            </div>
                        </div>
                        <?php
                        $query		= "SELECT * FROM `course_lti_consumers` WHERE `course_id`=".$db->qstr($COURSE_ID)." ORDER BY `lti_title` ASC";
                        $results	= ((USE_CACHE) ? $db->CacheGetAll(CACHE_TIMEOUT, $query) : $db->GetAll($query));
                        ?>
                        <form id="lti-listing" action="<?php echo ENTRADA_URL."/admin/".$MODULE."?".replace_query();?>" method="post">
                            <input type="hidden" name="type" value="lti" />
                            <table class="tableList" cellspacing="0" summary="List of LTI Providers">
                                <colgroup>
                                    <col class="modified wide"/>
                                    <col class="title" />
                                    <col class="title" />
                                    <col class="date" />
                                    <col class="date" />
                                </colgroup>
                                <thead>
                                <tr>
                                    <td class="modified">&nbsp;</td>
                                    <td class="title sortedASC"><div class="noLink">LTI Provider Title</div></td>
                                    <td class="title">Launch URL</td>
                                    <td class="date-small">Accessible Start</td>
                                    <td class="date-small">Accessible Finish</td>
                                </tr>
                                </thead>
                                <tfoot>
                                <tr>
                                    <td>&nbsp;</td>
                                    <td colspan="4" style="padding-top: 10px">
                                        <?php
                                        echo (($results) ? "<input type=\"button\" class=\"btn btn-danger\" value=\"Delete Selected\" onclick=\"confirmLTIDelete()\" />" : "&nbsp;")."\n";
                                        ?>
                                    </td>
                                </tr>
                                </tfoot>
                                <tbody>
                                <?php
                                if($results) {
                                    foreach($results as $result) { ?>
                                        <tr>
                                            <td class="modified wide">
                                                <input type="checkbox" name="delete[]" value="<?php echo $result["id"];?>"/>
                                            </td>
                                            <td class="title">
                                                <a 	href="#"
                                                      onclick="openDialog('<?php echo ENTRADA_URL;?>/api/lti-wizard-course.api.php?action=edit&id=<?php echo $COURSE_ID."&ltiid=".$result["id"];?>')"
                                                      title="Click to edit <?php echo html_encode($result["lti_title"]);?>">
                                                    <strong>
                                                        <?php echo (($result["lti_title"] != "") ? html_encode($result["lti_title"]) : $result["lti_title"]);?>
                                                    </strong>
                                                </a>
                                            </td>
                                            <td class="title">
                                                <?php echo (($result["launch_url"] != "") ? html_encode($result["launch_url"]) : $result["launch_url"]);?>
                                            </td>
                                            <td class="date-small">
											<span class="content-date">
												<?php echo (((int) $result["valid_from"]) ? date(DEFAULT_DATE_FORMAT, $result["valid_from"]) : "No Restrictions");?>
											</span>
                                            </td>
                                            <td class="date-small">
											<span class="content-date">
												<?php echo (((int) $result["valid_until"]) ? date(DEFAULT_DATE_FORMAT, $result["valid_until"]) : "No Restrictions");?>
											</span>
                                            </td>
                                        </tr>
                                    <?php
                                    }
                                } else { ?>
                                    <tr>
                                        <td colspan="5">
                                            <div class="well well-small content-small">
                                                There have been no LTI Providers added to this <?php echo strtolower($translate->_("course"));?>. To <strong>add a new LTI Provider</strong>, simply click the button.
                                            </div>
                                        </td>
                                    </tr>
                                <?php
                                } ?>
                                </tbody>
                            </table>
                        </form>
                    </div>
				</div>
				<?php
				/**
				 * Sidebar item that will provide the links to the different sections within this page.
				 */
				$sidebar_html  = "<ul class=\"menu\">\n";
				$sidebar_html .= "	<li class=\"link\"><a href=\"#course-details-section\" onclick=\"$('course-details-section').scrollTo(); return false;\" title=\"Course Setup\">" . $translate->_("Course Setup") . "</a></li>\n";
				$sidebar_html .= "	<li class=\"link\"><a href=\"#course-objectives-section\" onclick=\"$('course-objectives-section').scrollTo(); return false;\" title=\"" . $translate->_("Course Objectives") . "\">" . $translate->_("course") . " " . $translate->_("Objectives") . "</a></li>\n";
				$sidebar_html .= "	<li class=\"link\"><a href=\"#course-resources-section\" onclick=\"$('course-resources-section').scrollTo(); return false;\" title=\"Course Resources\">" . $translate->_("course") . " Resources</a></li>\n";
				$sidebar_html .= "</ul>\n";

				new_sidebar_item("Page Anchors", $sidebar_html, "page-anchors", "open", "1.9");

				/**
				 * Sidebar item that will provide link to reports.
				 */
				$sidebar_html  = "<ul class=\"menu\">\n";
				$sidebar_html .= "	<li class=\"link\"><a href=\"".ENTRADA_URL."/admin/courses?section=course-eventtype-report&amp;id=".$COURSE_ID."\" title=\"" . $translate->_("Event Types") . " Report\">" . $translate->_("Event Types") . " Report</a></li>\n";
				$sidebar_html .= "</ul>\n";

				new_sidebar_item("Reports", $sidebar_html, "reports", "open", "1.9");
			}
		} else {
			add_error("In order to edit a course you must provide a valid course identifier. The provided ID does not exist in this system.");

			echo display_error();

			application_log("notice", "Failed to provide a valid course identifier when attempting to edit a course.");
		}
	} else {
		add_error("In order to edit a course you must provide the course identifier.");

		echo display_error();

		application_log("notice", "Failed to provide course identifier when attempting to edit a course.");
	}
}
