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
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_COURSES"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("course", "create", false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	$HEAD[] = "<script>var SITE_URL = '".ENTRADA_URL."';</script>";
	$HEAD[] = "<script>var ORGANISATION = '".$ENTRADA_USER->getActiveOrganisation()."';</script>";
	$HEAD[] = "<script>var DELETE_IMAGE_URL = '".ENTRADA_URL."/images/minus-sign.png';</script>";
	$HEAD[] = "<script src=\"".ENTRADA_RELATIVE."/javascript/picklist.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
	$HEAD[]	= "<script src=\"".ENTRADA_RELATIVE."/javascript/objectives.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
	$HEAD[]	= "<script src=\"".ENTRADA_RELATIVE."/javascript/objectives_course.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
	$HEAD[]	= "<script src=\"".ENTRADA_RELATIVE."/javascript/keywords_course.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
	$HEAD[] = "<script src=\"".ENTRADA_RELATIVE."/javascript/jquery/jquery.advancedsearch.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
	$HEAD[] = "<script src=\"".ENTRADA_RELATIVE."/javascript/jquery/jquery.audienceselector.js?release=". html_encode(APPLICATION_VERSION) ."\"></script>";
    $HEAD[] = "<script src=\"".ENTRADA_RELATIVE."/javascript/jquery/jquery.autocompletelist.js?release=". html_encode(APPLICATION_VERSION) ."\"></script>";
	$HEAD[] = "<script src=\"".ENTRADA_RELATIVE."/javascript/". $MODULE ."/". $MODULE ."_edit.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
    $HEAD[] = "<script src=\"".ENTRADA_RELATIVE."/javascript/color-picker.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
    $HEAD[] = "<script>var COURSE_COLOR_PALETTE = ".json_encode($translate->_("course_color_palette")).";</script>\n";
	$HEAD[] = "<link rel=\"stylesheet\" href=\"".ENTRADA_RELATIVE."/css/jquery/jquery.audienceselector.css?release=". html_encode(APPLICATION_VERSION) ."\" />";
	$HEAD[] = "<link rel=\"stylesheet\" href=\"".ENTRADA_RELATIVE."/css/jquery/jquery.advancedsearch.css?release=".html_encode(APPLICATION_VERSION)."\" />\n";
	$HEAD[] = "<link rel=\"stylesheet\" href=\"".ENTRADA_RELATIVE."/css/courses/courses.css?release=".html_encode(APPLICATION_VERSION)."\" />\n";
	$HEAD[] = "<link rel=\"stylesheet\" href=\"".ENTRADA_RELATIVE."/css/image/image-upload.css?release=".html_encode(APPLICATION_VERSION)."\" />\n";
	$HEAD[] = "<script src=\"".ENTRADA_RELATIVE."/javascript/jquery/jquery.imgareaselect.min.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
	$HEAD[] = "<link rel=\"stylesheet\" href=\"".  ENTRADA_RELATIVE ."/css/imgareaselect-default.css?release=".html_encode(APPLICATION_VERSION)."\" />\n";
	$HEAD[] = "<script src=\"".ENTRADA_RELATIVE."/javascript/image/image-upload.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
    $HEAD[] = "<script src=\"".ENTRADA_RELATIVE."/javascript/Twitter.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
	$HEAD[] = "<script src=\"".ENTRADA_RELATIVE."/javascript/jquery/jquery.iris.min.js?release=". html_encode(APPLICATION_VERSION) ."\"></script>";

	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/".$MODULE."?".replace_query(array("section" => "add")), "title" => "Adding " . $translate->_("course"));

	echo "<h1>Adding " . $translate->_("Course") . "</h1>\n";

    /**
     * Default values.
     */
	$PROCESSED["permission"] = "open";
	$PROCESSED["sync_ldap"] = 0;
	$PROCESSED["sync_ldap_courses"] = NULL;
    $PROCESSED["course_redirect"] = 0;
    $PROCESSED["created_date"] = time();
    $PROCESSED["created_by"] = $ENTRADA_USER->getID();
    $PROCESSED["updated_date"] = 0;
    $PROCESSED["updated_by"] = 0;

	// Error Checking
	switch ($STEP) {
		case 2 :
			if ($ENTRADA_ACL->amIAllowed(new CourseResource(null, $ENTRADA_USER->getActiveOrganisation()), "create")) {
				$PROCESSED["organisation_id"] = $ENTRADA_USER->getActiveOrganisation();
			} else {
				add_error("You do not have permission to add a course for this organisation. This error has been logged and will be investigated.");
				application_log("error", "Proxy id [".$ENTRADA_USER->getID()."] tried to create a course within an organisation [".$ENTRADA_USER->getActiveOrganisation()."] they didn't have permissions on. ");
			}

			/**
			 * Non-required field "curriculum_type_id" / Curriculum Category
			 */
			if ((isset($_POST["curriculum_type_id"])) && ($curriculum_type_id = clean_input($_POST["curriculum_type_id"], array("int")))) {
				$PROCESSED["curriculum_type_id"] = $curriculum_type_id;
			} else {
				add_error("Please select the <strong>Curriculum Layout</strong> this " . $translate->_("course") ." is in.");
			}

			/**
			 * Required field "course_name" / Course Name.
			 */
			if ((isset($_POST["course_name"])) && ($course_name = clean_input($_POST["course_name"], array("notags", "trim")))) {
				$PROCESSED["course_name"] = $course_name;
			} else {
				add_error("The <strong>" . $translate->_("Course") .  " Name</strong> field is required.");
			}

			/**
			 * Required field "course_code" / Course Code.
			 */
			if ((isset($_POST["course_code"])) && ($course_code = clean_input($_POST["course_code"], array("notags", "trim")))) {
				$PROCESSED["course_code"] = $course_code;
			} else {
				add_error("The <strong>" . $translate->_("Course") . " Code</strong> field is required and must be provided.");
			}

			/**
			 * Non-Required field "course_color" / Course Colour.
			 */
			if ((isset($_POST["course_color"])) && ($course_color = clean_input($_POST["course_color"], array("notags", "trim")))) {
				$PROCESSED["course_color"] = $course_color;
			} else {
				$PROCESSED["course_color"] = null;
			}

            /**
             * Parse the Non-Required  field "course_credit" / Course Credit
             */
            if ((isset($_POST["course_credit"])) && ($course_credit = clean_input($_POST["course_credit"], "float"))) {
                /* round to the nearest 0.5 increment */
                $PROCESSED["course_credit"] = round(($course_credit * 2), 0) / 2;
            } elseif ((isset($_POST["course_credit"])) && !$_POST["course_credit"]) {
                $PROCESSED["course_credit"] = NULL;
            } else {
                $PROCESSED["course_credit"] = 0.0;
            }

            /**
			 * Check to see if notifications are enabled or not for events in this course.
			 */
			if ((isset($_POST["notifications"])) && (!clean_input($_POST["notifications"], "int"))) {
				$PROCESSED["notifications"] = 0;
			} else {
				$PROCESSED["notifications"] = 1;
			}

			/**
			 * Check to see if course is mandatory
			 */
			if ((isset($_POST["course_mandatory"])) && ($tmp = clean_input($_POST["course_mandatory"], "int"))) {
				$PROCESSED["course_mandatory"] = $tmp;
			} else {
				$PROCESSED["course_mandatory"] = 0;
			}
			/**
			 * Check to see if whether this course is open or closed.
			 */
			if ((isset($_POST["permission"])) && ($_POST["permission"] == "closed")) {
				$PROCESSED["permission"] = "closed";
			} else {
				$PROCESSED["permission"] = "open";
			}

			/**
			 * Check to see if this course audience should syncronize with LDAP or not.
			 */
			$PROCESSED["sync_ldap_courses"] = "";
			if ((isset($_POST["sync_ldap"])) && ($_POST["sync_ldap"] == "1")) {
				$PROCESSED["sync_ldap"] = 1;
			} else {
				$PROCESSED["sync_ldap"] = 0;
			}
			
            /*
             * Process the ldap sync course list.
             */
            $PROCESSED["sync_ldap_courses"] = "";
            $clean_ldap_course_codes = array();
            if (isset($_POST["sync_ldap_courses"]) && !empty($_POST["sync_ldap_courses"])) {
                $sync_ldap_courses = explode(",", $_POST["sync_ldap_courses"]);
                foreach ($sync_ldap_courses as $course_code) {
                    if ($tmp_input = clean_input($course_code, array("trim", "striptags", "alphanumeric"))) {
                        if (!in_array(strtoupper($tmp_input), $clean_ldap_course_codes)) {
                            $clean_ldap_course_codes[] = strtoupper($tmp_input);
                        }
                    }
                }
                if (isset($clean_ldap_course_codes) && !empty($clean_ldap_course_codes)) {
                    $PROCESSED["sync_ldap_courses"] = implode(", ", $clean_ldap_course_codes);
                }
            }

            if (empty($PROCESSED["sync_ldap_courses"]) && $PROCESSED["sync_ldap"] != 0) {
                add_error("The LDAP synchronization course list can not be empty.");
            }

            /**
             * Check to see if the course groups should syncronize with LDAP or not.
             */
            if ((isset($_POST["sync_groups"])) && ($_POST["sync_groups"] == "1")) {
                $PROCESSED["sync_groups"] = 1;
            } else {
                $PROCESSED["sync_groups"] = 0;
            }

			/**
			 * Field "course_twitter_handle" / Course Twitter Handle.
			 */
			if ((isset($_POST["course_twitter_handle"])) && ($course_twitter_handle = clean_input($_POST["course_twitter_handle"], array("notags", "trim")))) {
				$PROCESSED["course_twitter_handle"] = $course_twitter_handle;
			} else {
				$PROCESSED["course_twitter_handle"] = "";
			}

			/**
			 * Field "course_twitter_hashtags" / Course Twitter Hashtags.
			 */
			if (isset($_POST["course_twitter_hashtags"])) {
				$PROCESSED["course_twitter_hashtags"] = array();
				foreach ($_POST["course_twitter_hashtags"] as $index => $tmp_input) {
					if ($course_twitter_hashtags = clean_input($tmp_input, array("trim", "notags"))) {
						$PROCESSED["course_twitter_hashtags"][] = $course_twitter_hashtags;
					}
				}
				$PROCESSED["course_twitter_hashtags"] = implode(" ", $PROCESSED["course_twitter_hashtags"]);
			} else {
				$PROCESSED["course_twitter_hashtags"] = "";
			}

			$COURSE_TRACKS = array();
			if ((isset($_POST["course_track"])) && ($tracks = $_POST["course_track"]) && (count($_POST["course_track"]))) {
				foreach ($tracks as $track) {
                    $COURSE_TRACKS[] = clean_input($track, "int");
                    if (isset($_POST["track_mandatory_".$track])) {
                        $PROCESSED["track_mandatory_" . $track] = clean_input($_POST["track_mandatory_" . $track], "int");
                    }
				}
			}

			$period_list = array();
			if (isset($_POST["periods"]) && is_array($_POST["periods"]) && $periods = $_POST["periods"]) {
				foreach ($periods as $key => $unproced_period) {
					$period_id = (int)$unproced_period;

					$period_list[] = $period_id;
					$cohort_members = array();
					$course_list_members = array();
					$individual_members = array();

					if (isset($_POST["cohort_audience_members"][$key]) && strlen($_POST["cohort_audience_members"][$key]) && $cohort_member_string = clean_input($_POST["cohort_audience_members"][$key],array("trim","notags"))) {
						$cohort_members = explode(",",$cohort_member_string);
						if ($cohort_members) {
							foreach ($cohort_members as $member) {
								$cohort_list[$period_id][] = $member;
								$PROCESSED["periods"][$period_id][] = array("audience_type"=>'group_id',"audience_value"=>$member,"cperiod_id"=>$period_id,"audience_active"=>1);
							}
						}
					}

					if (isset($_POST["individual_audience_members"][$key]) && strlen($_POST["individual_audience_members"][$key]) && $individual_member_string = clean_input($_POST["individual_audience_members"][$key],array("trim","notags"))) {
						$individual_members = explode(",",$individual_member_string);
						if ($individual_members) {
							foreach ($individual_members as $member) {
                                $individual_list[$period_id][] = $member;
								$PROCESSED["periods"][$period_id][]=array("audience_type"=>'proxy_id',"audience_value"=>$member,"cperiod_id"=>$period_id,"audience_active"=>1);
							}
						}
					}

					if (!$cohort_members && !$individual_members) {
						$curriculum_period_result = Models_Curriculum_Period::fetchRowByID($unproced_period);
						$curriculum_period = $curriculum_period_result->toArray();
						if ($curriculum_period["curriculum_period_title"]) {
							add_error("The <strong>" . $curriculum_period["curriculum_period_title"] . "</strong> curriculum period requires an audience.");
						} else {
							$error_title =  date("F jS, Y",$curriculum_period["start_date"])." to ".date("F jS, Y",$curriculum_period["finish_date"]);
							add_error("The <strong>" . $error_title . "</strong> curriculum period requires an audience.");
						}
						$PROCESSED["periods"][$period_id][]=array("audience_type"=>'',"audience_value"=>0,"cperiod_id"=>$period_id,"audience_active"=>0);
					}
				}
			}

            if (!has_error()) {
				$courses_object = new Models_Course();
				if ($new_course = $courses_object->fromArray($PROCESSED)->insert()) {
					if ($COURSE_ID = $new_course->getID()) {
						$delete_old_tracks = Models_Course::deleteTrackCourseRelationshipByCourseId($COURSE_ID);
						if (is_array($COURSE_TRACKS) && count($COURSE_TRACKS)) {
							if ($delete_old_tracks && is_array($COURSE_TRACKS) && (count($COURSE_TRACKS) > 0)) {
								foreach ($COURSE_TRACKS as $track_id) {
									$mandatory = clean_input($_POST["track_mandatory_" . $track_id], "int");
									$result = Models_Course::insertTrackCourseRelationship($track_id, $COURSE_ID, $mandatory);
									if (!$result) {
										add_error("An error occurred while adding the track with id " . $track_id . " as a course track.");
									}
								}
							}
						}

						$course_audience_object = new Models_Course_Audience();

						if (isset($PROCESSED["periods"]) && is_array($PROCESSED["periods"]) && $PROCESSED["periods"]) {
							foreach ($PROCESSED["periods"] as $period_id => $period) {
								foreach ($period as $key=>$audience) {
									$audience["course_id"] = $COURSE_ID;

									if (!$row = $course_audience_object->fetchRowByCourseIDPeriodIDAudienceTypeAudienceValue($COURSE_ID, $audience["cperiod_id"], $audience["audience_type"], $audience["audience_value"])) {
										$add_audience = new Models_Course_Audience($audience);
										if (!$add_audience->insert()) {
											add_error("An error occurred while adding the student with id ".$member." as an audience member.");
										}
									}
								}
							}
						}

						if (!has_error()) {
							Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("You have successfully created <strong>%s</strong>."), html_encode($PROCESSED["course_name"])), "success", $MODULE);

							application_log("success", "New " . $translate->_("Course") . " [".$COURSE_ID."] added to the system.");

							header("Location: " . ENTRADA_URL . "/admin/courses?section=edit&id=" . $COURSE_ID);
							exit;
						}
					}
				} else {
					add_error("There was a problem inserting this course into the system. The system administrator was informed of this error; please try again later.");

					application_log("error", "There was an error updating a course.");
				}
			}

			if (has_error()) {
				$STEP = 1;
			}
		break;
		case 1 :
		default :
			continue;
		break;
	}

	// Display Content
	switch ($STEP) {
		case 2 :
			if ($SUCCESS) {
				echo display_success();
			}
			if ($NOTICE) {
				echo display_notice();
			}
			if ($ERROR) {
				echo display_error();
			}
		break;
		case 1 :
		default :
			/**
             * Assemble the list of course tracks to display. If the COURSE_TRACKS array exists, it means we are displaying after step 2 processing,
             * and we want to display what is in that list. Otherwise, we fetch the list of tracks currently associated with the course
             */
            if (isset($COURSE_TRACKS)) {
                $temp_tracks = array();
                foreach ($COURSE_TRACKS as $curriculum_track_id) {
                    $track = Models_Curriculum_Track::fetchRowByID($curriculum_track_id)->toArray();
                    $track["track_mandatory"] = $PROCESSED["track_mandatory_".$curriculum_track_id];
                    $temp_tracks[] = $track;
                }
                $COURSE_TRACKS = $temp_tracks;
            }

			if (has_error()) {
				echo display_error();
			}
			?>
			<form class="form-horizontal" action="<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>?<?php echo replace_query(array("step" => 2)); ?>" method="post" id="courseForm">
				<h2 title="Course Setup Section"><?php echo $translate->_("Course Setup"); ?></h2>
                <div id="course-setup-section">
                    <div class="control-group">
                        <label for="curriculum_type_id" class="control-label form-required"><?php echo $translate->_("Curriculum Layout"); ?></label>
                        <div class="controls">
                            <select id="curriculum_type_id" name="curriculum_type_id" onchange="loadCurriculumPeriods(this.options[this.selectedIndex].value)" class="span7">
                                <option value="0"<?php echo (((!isset($PROCESSED["curriculum_type_id"])) || (!(int) $PROCESSED["curriculum_type_id"])) ? " selected=\"selected\"" : ""); ?>>- Select <?php echo $translate->_("Curriculum Layout"); ?> -</option>
                                <?php
								$results = Models_Curriculum_Type::fetchAllByOrg($ENTRADA_USER->getActiveOrganisation());
                                if ($results) {
                                    foreach ($results as $result) {
                                        echo "<option value=\"".(int) $result->getID() ."\"".(((isset($PROCESSED["curriculum_type_id"])) && ($PROCESSED["curriculum_type_id"] == $result->getID())) ? " selected=\"selected\"" : "").">".html_encode($result->getCurriculumTypeName())."</option>\n";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="control-group">
                        <label for="course_name" class="form-required control-label"><?php echo $translate->_("Course"); ?> Name</label>
                        <div class="controls">
                            <input type="text" id="course_name" name="course_name" value="<?php echo html_encode((isset($PROCESSED["course_name"]) && $PROCESSED["course_name"] ? $PROCESSED["course_name"] : "")); ?>" maxlength="85" class="span7"/>
                        </div>
                    </div>

                    <div class="control-group">
                        <label for="course_code" class="form-required control-label"><?php echo $translate->_("Course"); ?> Code</label>
                        <div class="controls">
                        <input type="text" id="course_code" name="course_code" value="<?php echo html_encode((isset($PROCESSED["course_code"]) && $PROCESSED["course_code"] ? $PROCESSED["course_code"] : "")); ?>" maxlength="16" class="span3"/>
                        </div>
                    </div>

                    <div class="control-group">
                        <label for="course_color" class="form-nrequired control-label"><?php echo $translate->_("Course")." ".$translate->_("Colour"); ?></label>
                        <div class="controls">
                            <input type="text" id="course_color" name="course_color" value="<?php echo html_encode(!empty($PROCESSED["course_color"]) ? $PROCESSED["course_color"] : ""); ?>" maxlength="20" class="span3">
                        </div>
                    </div>

                    <div class="control-group">
                        <label for="course_credit" class="control-label"><?php echo $translate->_("Course"); ?> Credit</label>
                        <div class="controls">
                            <input type="text" id="course_credit" name="course_credit" value="<?php echo html_encode($PROCESSED["course_credit"]); ?>" maxlength="3" class="span3" placeholder="i.e 3.0">
                        </div>
                    </div>

					<div class="control-group">
						<label class="form-nrequired control-label"><?php echo $translate->_("Course"); ?> Type</label>
						<div class="controls">
							<label for="course_mandatory_on" class="radio">
								<input type="radio" name="course_mandatory" id="course_mandatory_on" value="1"<?php echo ((!isset($PROCESSED["course_mandatory"]) || (isset($PROCESSED["course_mandatory"]) && $PROCESSED["course_mandatory"])) ? " checked=\"checked\"" : ""); ?> />
                                This is <strong>core</strong> curriculum this program.
							</label>
							<label for="course_mandatory_off" class="radio">
								<input type="radio" name="course_mandatory" id="course_mandatory_off" value="0"<?php echo ((isset($PROCESSED["course_mandatory"]) && !$PROCESSED["course_mandatory"]) ? " checked=\"checked\"" : ""); ?> />
                                This is an <strong>option</strong> for this program.
							</label>
						</div>
					</div>
                    <?php
                    $results = Models_Curriculum_Track::fetchAllByOrg($ENTRADA_USER->getActiveOrganisation());
                    if ($results) {
                    ?>
                    <div class="control-group">
                        <label for="curriculum_track_ids" class="control-label form-nrequired">Curriculum Tracks</label>
                        <div class="controls">
                            <button id="curriculum_track_ids" class="btn btn-search-filter" style="min-width: 220px; text-align: left;">Curriculum Tracks<i class="icon-chevron-down btn-icon pull-right"></i></button>
                            <table id="tracks_container" class="table tracks-container space-above">
                                <tbody>
                                    <?php
                                    if (is_array($COURSE_TRACKS)) {
                                        $class_hide = "";
                                        if (isset($PROCESSED["course_mandatory"]) && $PROCESSED["course_mandatory"]) {
                                            $class_hide = " hide";
                                        }
                                        foreach ($COURSE_TRACKS as $course_track) {
                                            $course_track = Models_Curriculum_Track::fetchRowByID($course_track["curriculum_track_id"]);
                                            if ($course_track && is_object($course_track)) {
                                                ?>
                                                <tr id="track_<?php echo $course_track->getID();?>">
                                                    <td class="track-name"><?php echo html_encode($course_track->getCurriculumTrackName());?></td>
                                                    <td class="track-options">
                                                        <input class="track<?php echo $class_hide; ?>" type="radio" name="track_mandatory_<?php echo $course_track->getID(); ?>" value="1"<?php echo ((isset($PROCESSED["track_mandatory_".$course_track->getID()]) && $PROCESSED["track_mandatory_".$course_track->getID()]) ? " checked=\"checked\"" : ""); ?> />
                                                        <span class="track<?php echo $class_hide; ?>">Mandatory</span><br>
                                                        <input class="track<?php echo $class_hide; ?>" type="radio" name="track_mandatory_<?php echo $course_track->getID(); ?>" value="0"<?php echo ((isset($PROCESSED["track_mandatory_".$course_track->getID()]) && !$PROCESSED["track_mandatory_".$course_track->getID()]) ? " checked=\"checked\"" : ""); ?> />
                                                        <span class="track<?php echo $class_hide; ?>">Additional</span>
                                                    </td>
                                                    <td>
                                                        <a href="#" onclick="$(this).up().up().remove(); jQuery('input#event_types_' + $(this).value).remove(); return false;" class="remove"><img src="<?php echo ENTRADA_URL; ?>/images/action-delete.gif"></a>
                                                    </td>
                                                    <input type="hidden" name="course_track[]" value="<?php echo $course_track->getID();?>" />
                                                    <input type="hidden" name="event_types[]" value="<?php echo $course_track->getID();?>" id="event_types_<?php echo $course_track->getID();?>" data-filter="event_types" data-id="<?php echo $course_track->getID();?>" data-label="<?php echo html_encode($course_track->getCurriculumTrackName());?>" class="search-target-control event_types_search_target_control" />
                                                </tr>
                                                <?php
                                            }
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                            <input id="curriculum_tracks_values" name="curriculum_tracks_values" style="display: none;">
                        </div>
                    </div>
                    <?php } ?>
                    <div class="control-group">
                        <label class="form-nrequired control-label">Reminder Notifications</label>
                        <div class="controls">
                            <label for="notification_on" class="radio">
                              <input type="radio" name="notifications" id="notification_on" value="1"<?php echo (((!isset($PROCESSED["notifications"])) || ((isset($PROCESSED["notifications"])) && ($PROCESSED["notifications"]))) ? " checked=\"checked\"" : ""); ?> />
                               Send e-mail notifications to faculty for events under this <?php echo strtolower($translate->_("course")); ?>.
                            </label>
                            <label for="notification_off" class="radio">
                              <input type="radio" name="notifications" id="notification_off" value="0"<?php echo (((isset($PROCESSED["notifications"])) && (!(int) $PROCESSED["notifications"])) ? " checked=\"checked\"" : ""); ?> />
                              <strong>Do not</strong> send e-mail notifications to faculty for events under this <?php echo strtolower($translate->_("course")); ?>.
                            </label>
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label form-nrequired"><?php echo $translate->_("Course"); ?> Permissions</label>
                        <div class="controls">
                            <label for="visibility_on" class="radio">
                                <input type="radio" name="permission" id="visibility_on" value="open"<?php echo (((!isset($PROCESSED["permission"])) || ((isset($PROCESSED["permission"])) && ($PROCESSED["permission"] == "open"))) ? " checked=\"checked\"" : ""); ?> />
                                This <?php echo strtolower($translate->_("course")); ?> is <strong>open</strong> and visible to all logged in users.
                            </label>
                            <label for="visibility_off" class="radio">
                                <input type="radio" name="permission" id="visibility_off" value="closed"<?php echo (((isset($PROCESSED["permission"])) && ($PROCESSED["permission"] == "closed")) ? " checked=\"checked\"" : ""); ?> />
                                This <?php echo strtolower($translate->_("course")); ?> is <strong>private</strong> and only visible to logged in users enrolled in the <?php echo strtolower($translate->_("course")); ?>.
                            </label>
                        </div>
                    </div>
                    <div class="control-group">
						<label class="control-label form-nrequired">Audience Sync</label>
						<div class="controls">
							<label for="sync_off" class="radio">
								<input type="radio" name="sync_ldap" id="sync_off" value="0"<?php echo (((!isset($PROCESSED["sync_ldap"])) || (isset($PROCESSED["sync_ldap"])) && (!(int)$PROCESSED["sync_ldap"])) ? " checked=\"checked\"" : ""); ?> />The audience will be managed manually and <strong>should not</strong> be synced with the LDAP server.
							</label>
							<label for="sync_on" class="radio">
								<input type="radio" name="sync_ldap" id="sync_on" value="1"<?php echo ((((isset($PROCESSED["sync_ldap"])) && ($PROCESSED["sync_ldap"]))) ? " checked=\"checked\"" : ""); ?> /> This course <strong>should</strong> have its audience synced with the LDAP server.
							</label>
							<div class="<?php echo ((((isset($PROCESSED["sync_ldap"])) && ($PROCESSED["sync_ldap"]))) ? "" : "hide"); ?> ldap-course-sync-list">
								<div class="well well-small content-small">Please enter a comma separated list of alphanumeric course codes you wish to synchronize with in the textarea below. You can add additional individuals and groups manually using the <strong>Course <?php echo $translate->_("Enrolment"); ?></strong> section below.</div>
								<textarea name="sync_ldap_courses" class="span12"><?php echo (isset($PROCESSED["sync_ldap_courses"]) ? $PROCESSED["sync_ldap_courses"] : $PROCESSED["course_code"]); ?></textarea>

                                <label for="sync_groups" class="checkbox" style="margin-top: 15px">
                                    <input type="checkbox" name="sync_groups" id="sync_groups" value="1"<?php echo ((((isset($PROCESSED["sync_groups"])) && ($PROCESSED["sync_groups"]))) ? " checked=\"checked\"" : ""); ?> />
                                    Automatically create and syncronize any <strong>course groups</strong> defined in the LDAP server.
                                </label>
							</div>
						</div>
					</div>
				</div>
				<?php if (Entrada_Twitter::widgetIsActive()) { ?>
					<h2 title="Course Twitter Section"><?php echo $translate->_("Course"); ?> Twitter Settings</h2>
					<div class="control-group">
						<label for="course_twitter_handle" class="control-label"><?php echo $translate->_("course"); ?> Twitter Handle</label>
						<div class="controls">
							<input type="text" id="course_twitter_handle" name="course_twitter_handle" value="<?php echo html_encode($PROCESSED["course_twitter_handle"]); ?>" maxlength="16" class="span7">
						</div>
					</div>
					<div class="control-group">
						<label for="course_twitter_hashtags" class="control-label"><?php echo $translate->_("course"); ?> Twitter Hashtags</label>
						<div class="controls">
							<select class="chosen-select" multiple id="twitter_hashtags" name="course_twitter_hashtags[]">
								<?php
								$select_options_array = explode(" ", $PROCESSED["course_twitter_hashtags"]);
								foreach ($select_options_array as $select_option) {
									echo "<option selected value=\"" . $select_option . "\">".$select_option."</option>";
								}
								?>
							</select>
						</div>
					</div>
				<?php } ?>

                <!-- Course Enrolment-->
                <h2 title="Course Enrolment Section"><?php echo $translate->_("Course"); ?> <?php echo $translate->_("Enrolment"); ?></h2>
				<div id="course-enrolment-section" class="clearfix">
                    <div class="control-group">
                        <label for="period" class="control-label form-nrequired"><?php echo $translate->_("Enrolment"); ?> Periods</label>
                        <div class="controls">
                            <div id="curriculum_type_periods">
                                <?php
                                if (isset($PROCESSED["curriculum_type_id"]) && $PROCESSED["curriculum_type_id"]) {
									$curriculum_period_object = new Models_Curriculum_Period();
                                    $periods = $curriculum_period_object->getAllByFinishDateCurriculumType($PROCESSED["curriculum_type_id"]);
                                    if ($periods) {
                                        ?>
                                        <select name="curriculum_period" id="period_select">
                                            <option value="0" selected="selected">-- Select a Period --</option>
                                            <?php
                                            foreach ($periods as $period) {
                                                echo "<option value=\"".$period["cperiod_id"]."\" ".((array_key_exists($period["cperiod_id"], $PROCESSED["periods"]))?" disabled=\"disabled\"":"").">". (($period["curriculum_period_title"]) ? $period["curriculum_period_title"] . " - " : "") . date("F jS, Y" ,$period["start_date"])." to ".date("F jS, Y" ,$period["finish_date"])."</option>";
                                            }
                                            ?>
                                        </select>
                                        <?php
                                    } else {
                                        echo "<div class=\"display-notice\"><ul><li>No periods have been found for the selected <strong>". $translate->_("Curriculum Layout") . "</strong>.</li></ul></div>";
                                    }
                                } else {
                                    echo "<div class=\"display-notice\"><ul><li>No <strong>". $translate->_("Curriculum Layout") . "</strong> has been selected.</li></ul></div>";
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div id="period_list" class="span12">
                            <?php
                            if (isset($PROCESSED["periods"])) {
                                ?>
                                <h3>Active Periods</h3>
                                <?php
                                foreach ($PROCESSED["periods"] as $key => $period) {
                                    $period_data = Models_Curriculum_Period::fetchRowByID($key);
									$cohorts = Models_Group::fetchAllByGroupTypeCourseID("cohort", $ENTRADA_USER->getActiveOrganisation());
                                    ?>
                                    <div class="period_item clearfix" id="period_item_<?php echo $key;?>">
                                        <div class="clearfix">
                                            <div class="pull-left"><h4 class="period-item-title"><?php echo (($period_data->getCurriculumPeriodTitle()) ? $period_data->getCurriculumPeriodTitle() . " - " : ""); ?><?php echo date("F jS, Y",$period_data->getStartDate())." to ".date("F jS, Y",$period_data->getFinishDate()); ?></h4></div><div class="pull-right"><a href="javascript:void(0)" class="enrollment-toggle btn btn-success" id="add_audience_<?php echo $key;?>">Add Audience</a><span class="fa fa-close remove_period" id="remove_period_<?php echo $key;?>"></span></div>
                                        </div>
                                        <div class="audience_selector pull-left" id="audience_type_select_<?php echo $key;?>" style="display: none;">
                                            <select class="audience_type_select" id="audience_type_select_<?php echo $key;?>" onchange="showSelect(<?php echo $key;?>,this.options[this.selectedIndex].value)">
                                                <option value="0">-- Select Audience Type --</option>
                                                <?php if (is_array($cohorts) && !empty($cohorts)) :?>
												    <option value="cohort">Cohort</option>
												<?php endif; ?>
                                                <option value="individual">Individual</option>
                                            </select>
                                            <?php
											foreach ($period as $audience) {
												switch ($audience["audience_type"]) {
													case "group_id":
														$group_object = Models_Group::fetchRowByID($audience["audience_value"]);
                                                        if ($group_object && is_object($group_object)) {
                                                            if ($group_object->getGroupType() == "cohort") {
                                                                $cohort_ids[$key][] = $audience["audience_value"];
                                                            } else {
                                                                $course_list_ids[$key][] = $audience["audience_value"];
                                                            }
                                                        }
													break;
													case "proxy_id":
														$proxy_ids[$key][] = $audience["audience_value"];
													break;
												}
											}
											?>
											<?php if (is_array($cohorts) && !empty($cohorts)) :?>
												<select style="display:none;" class="type_select" id="cohort_select_<?php echo $key;?>" onchange="addAudience(<?php echo $key;?>,this.options[this.selectedIndex].text,'cohort',this.options[this.selectedIndex].value)"><option value="0">-- Add Cohort --</option>
													<?php
													foreach ($cohorts as $cohort_obj) {
														$cohort = $cohort_obj->toArray();
														echo "<option value=\"".$cohort["group_id"]."\"".((isset($cohort_ids[$key]) && in_array($cohort["group_id"],$cohort_ids[$key]))?" disabled=\"disabled\"":"").">".$cohort["group_name"]."</option>";
													}
													?>
												</select>
											<?php endif; ?>

                                            <input style="display:none;width:203px;vertical-align: middle;margin-left:10px;margin-right:10px;" type="text" name="fullname" class="type_select form-control search" id="student_<?php echo $key;?>_name" autocomplete="off" placeholder="<?php echo $translate->_("Type to search for student ..."); ?>"/>
                                            <input style="display:none;" type="button" class="btn type_select individual_add_btn" id="add_associated_student_<?php echo $key;?>" value="Add" style="vertical-align: middle" />
                                            <div class="autocomplete" id="student_<?php echo $key;?>_name_auto_complete"></div>
                                            <div style="display:none; margin-left: 240px;" id="student_example_<?php echo $key;?>">(Example: <?php echo $ENTRADA_USER->getFullname(true); ?>)</div>
											<input type="hidden" name="cohort_audience_members[]" id="cohort_audience_members_<?php echo $key;?>" value="<?php echo ($cohort_ids[$key] ? implode(',',$cohort_ids[$key]) : ""); ?>"/>
                                            <input type="hidden" name="individual_audience_members[]" id="associated_student_<?php echo $key;?>"/>
                                            <input type="hidden" name="student_id[]" id="student_<?php echo $key;?>_id"/>
                                            <input type="hidden" name="student_ref[]" id="student_<?php echo $key;?>_ref"/>
                                            <input type="hidden" name="periods[]" value="<?php echo $key;?>"/>
                                            <?php
                                            $ONLOAD[] = "jQuery('#student_".$key."_name').autocompletelist({ type: 'student_".$key."', url: '".ENTRADA_RELATIVE."/api/personnel.api.php?type=student&organisation_id=".$course_details["organisation_id"]."', remove_image: '".ENTRADA_RELATIVE."/images/minus-sign.png'})";
                                            ?>
                                        </div>

                                        <div class="audience_section" id="audience_section_<?php echo $key;?>" style="display:block;">
                                            <div class="audience_list" id="audience_list_<?php echo $key;?>">
                                                <ul id="cohort_container_<?php echo $key;?>" class="listContainer">
                                                    <li><h5>Cohorts</h5>
                                                        <ol id="cohort_audience_container_<?php echo $key;?>" class="sortableList">
                                                            <?php
                                                            foreach ($period as $audience) {
                                                                switch ($audience["audience_type"]) {
                                                                    case "group_id":
                                                                        $group = Models_Group::fetchRowByID($audience["audience_value"]);
                                                                        if ($group && is_object($group)) {
                                                                            if ($group->getGroupType() == "cohort") {
                                                                                $title = $group->getGroupName();
                                                                                if ($title) {
                                                                                    $group_ids[$key][] = $audience["audience_value"];

                                                                                    $audience["type"] = 'cohort';
                                                                                    $audience["title"] = $title;
                                                                                    ?>
                                                                                    <li id="audience_<?php echo $audience["type"] . "_" . $audience["audience_value"]; ?>" class="audience_cohort"><?php echo $audience["title"]; ?>
                                                                                        <span class="fa fa-close remove-list-item cohort remove_audience"></span>
                                                                                    </li>
                                                                                    <?php
                                                                                }
                                                                            }
                                                                        }
                                                                    break;
                                                                }
                                                            }
                                                            ?>
                                                        </ol>
                                                    </li>
                                                </ul>
                                                <ul id="student_<?php echo $key;?>_list_container" class="listContainer">
                                                    <li><h5>Students</h5>
                                                        <ol id="student_<?php echo $key;?>_list" class="sortableList">
                                                        <?php
                                                        foreach ($period as $audience) {
                                                            switch ($audience["audience_type"]) {
                                                                case "proxy_id":
                                                                    $student = Models_User::fetchRowByID($audience["audience_value"]);
                                                                    if ($student && is_object($student)) {
                                                                        $audience["type"] = "individual";
                                                                        $audience["title"] = $student->getFullname();
                                                                        ?>
                                                                        <li id="student_<?php echo $key . "_" . $audience["audience_value"]; ?>" data-proxy-id="<?php echo $audience["audience_value"]; ?>" style="cursor: move; position: relative;" class="user"><?php echo $audience["title"]; ?><span class="fa fa-close remove-list-item list-cancel-image remove_student"></span></li>
                                                                        <?php
                                                                    }
                                                                break;
                                                            }
                                                        }
                                                        ?>
                                                        </ol>
                                                    </li>
                                                </ul>
                                                <?php
                                                if (count($period) == 1 && $period[0]["audience_value"] == 0) {
                                                    ?>
                                                    <div id="no_audience_msg_<?php echo $key; ?>" class="alert alert-block alert-info no_audience_msg" style="margin-top: 20px;">
                                                        Please use the <strong>Add Audience</strong> link above to add an audience to this enrollment period.
                                                    </div>
                                                    <?php
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <div class="row-fluid space-above">
                    <div class="pull-left">
                        <a href="<?php echo ENTRADA_RELATIVE; ?>/admin/courses" class="btn"><?php echo $translate->_("Cancel"); ?></a>
                    </div>
                    <div class="pull-right">
                        <input type="submit" class="btn btn-primary" value="<?php echo $translate->_("Proceed"); ?>" />
                    </div>
                </div>
			</form>
			<?php
		break;
	}
}
