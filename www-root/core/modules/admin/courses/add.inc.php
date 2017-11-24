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
	$HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = '".ENTRADA_URL."';</script>";
	$HEAD[] = "<script type=\"text/javascript\">var ORGANISATION = '".$ENTRADA_USER->getActiveOrganisation()."';</script>";
	$HEAD[] = "<script type=\"text/javascript\">var SITE_URL = '".ENTRADA_URL."';</script>";
	$HEAD[] = "<script type=\"text/javascript\">var DELETE_IMAGE_URL = '".ENTRADA_URL."/images/minus-sign.png';</script>";
	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/picklist.js\"></script>\n";
	$HEAD[]	= "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/objectives.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
	$HEAD[]	= "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/objectives_course.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
	$HEAD[]	= "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/keywords_course.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery.advancedsearch.js\"></script>\n";
	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery.audienceselector.js?release=". html_encode(APPLICATION_VERSION) ."\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery.autocompletelist.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/". $MODULE ."/". $MODULE ."_edit.js\"></script>";
	$HEAD[] = "<link rel=\"stylesheet\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.audienceselector.css?release=". html_encode(APPLICATION_VERSION) ."\" />";
	$HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css\" />\n";
	$HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/courses/courses.css\" />\n";
	$HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/image/image-upload.css\" />\n";
	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery.imgareaselect.min.js\"></script>";
	$HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/imgareaselect-default.css\" />\n";
	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/image/image-upload.js\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/Twitter.js\"></script>";
	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery.iris.min.js?release=". html_encode(APPLICATION_VERSION) ."\"></script>";
	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/color-picker.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
    $HEAD[] = "<script type=\"text/javascript\">var COURSE_COLOR_PALETTE = ".json_encode($translate->_("course_color_palette")).";</script>\n";

	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/".$MODULE."?".replace_query(array("section" => "add")), "title" => "Adding " . $translate->_("course"));

	/**
	* Fetch the Clinical Presentation details.
	*/
	$clinical_presentations_list = array();
	$clinical_presentations = array();

	$results = fetch_clinical_presentations();
	if ($results) {
		foreach ($results as $result) {
			$clinical_presentations_list[$result["objective_id"]] = $result["objective_name"];
		}
	} else {
		$clinical_presentations_list = false;
	}

	if ((isset($_POST["clinical_presentations"])) && (is_array($_POST["clinical_presentations"])) && (count($_POST["clinical_presentations"]))) {
		$global_clinical_presentations_object = new Models_Objective();
		foreach ($_POST["clinical_presentations"] as $objective_id) {
			if ($objective_id = clean_input($objective_id, array("trim", "int"))) {
				$result = $global_clinical_presentations_object->getByIDAndOrganisation($objective_id,$ENTRADA_USER->getActiveOrganisation());
				if ($result) {
                    $clinical_presentations[$objective_id] = $clinical_presentations_list[$objective_id];
				}
			}
		}
	}

	echo "<h1>Adding " . $translate->_("course") . "</h1>\n";

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
				$PROCESSED["curriculum_type_id"] = 0;
			}

			/**
			 * Required field "course_name" / Course Name.
			 */
			if ((isset($_POST["course_name"])) && ($course_name = clean_input($_POST["course_name"], array("notags", "trim")))) {
				$PROCESSED["course_name"] = $course_name;
			} else {
				add_error("The <strong>" . $translate->_("course") .  " Name</strong> field is required.");
			}

			/**
			 * Required field "course_code" / Course Code.
			 */
			if ((isset($_POST["course_code"])) && ($course_code = clean_input($_POST["course_code"], array("notags", "trim")))) {
				$PROCESSED["course_code"] = $course_code;
			} else {
				add_error("The <strong>" . $translate->_("course") . " Code</strong> field is required and must be provided.");
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

			$posted_objectives = array();

			$PRIMARY_OBJECTIVES = array();
            if ((isset($_POST["primary_objectives"])) && ($objectives = $_POST["primary_objectives"]) && (count($objectives))) {
				foreach ($objectives as $objective_key => $objective) {
					$PRIMARY_OBJECTIVES[] = clean_input($objective, "int");
                    $objective_importance[$objective] = "1";
					$posted_objectives["primary"][] = clean_input($objective, "int");
				}
			}

			$SECONDARY_OBJECTIVES = array();
			if ((isset($_POST["secondary_objectives"])) && ($objectives = $_POST["secondary_objectives"]) && (count($objectives))) {
				foreach ($objectives as $objective_key => $objective) {
					$SECONDARY_OBJECTIVES[] = clean_input($objective, "int");
                    $objective_importance[$objective] = "2";
					$posted_objectives["secondary"][] = clean_input($objective, "int");
				}
			}

			$TERTIARY_OBJECTIVES = array();
			if ((isset($_POST["tertiary_objectives"])) && ($objectives = $_POST["tertiary_objectives"]) && (count($objectives))) {
				foreach ($objectives as $objective_key => $objective) {
					$TERTIARY_OBJECTIVES[] = clean_input($objective, "int");
                    $objective_importance[$objective] = "3";
					$posted_objectives["tertiary"][] = clean_input($objective, "int");
				}
			}

			$COURSE_TRACKS = array();
			if ((isset($_POST["course_track"])) && ($tracks = $_POST["course_track"]) && (count($_POST["course_track"]))) {
				foreach ($tracks as $track) {
                    $track = clean_input($track, "int");
                    $COURSE_TRACKS[] = $track;
                    if (isset($_POST["track_mandatory_".$track])) {
                        $PROCESSED["track_mandatory_".$track] = clean_input($_POST["track_mandatory_".$track], "int");
                    }
				}
			}

			/**
			 * Non-required field "pcoord_id" .
			 */
			if ((isset($_POST["pcoord_id"])) && ($pcoord_id = clean_input($_POST["pcoord_id"], "int"))) {
				$PROCESSED["pcoord_id"] = $pcoord_id;
			} else {
				$PROCESSED["pcoord_id"] = 0;
			}

			/**
			 * Non-required field "evalrep_id".
			 */
			if ((isset($_POST["evalrep_id"])) && ($evalrep_id = clean_input($_POST["evalrep_id"], "int"))) {
				$PROCESSED["evalrep_id"] = $evalrep_id;
			} else {
				$PROCESSED["evalrep_id"] = 0;
			}

			/**
			 * Non-required field "studrep_id" .
			 */
			if ((isset($_POST["studrep_id"])) && ($studrep_id = clean_input($_POST["studrep_id"], "int"))) {
				$PROCESSED["studrep_id"] = $studrep_id ;
			} else {
				$PROCESSED["studrep_id"] = 0;
			}

			/**
			 * Non-required field "ta_id" .
			 */
			if ((isset($_POST["ta_id"])) && ($ta_id = clean_input($_POST["ta_id"], "int"))) {
				$PROCESSED["ta_id"] = $ta_id ;
			} else {
				$PROCESSED["ta_id"] = 0;
			}

			if (isset($_POST["post_action"])) {
				switch ($_POST["post_action"]) {
					case "content" :
						$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = "content";
					break;
					case "new" :
						$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = "new";
					break;
					case "index" :
					default :
						$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = "index";
					break;
				}
			} else {
				$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = "content";
			}

			$period_list = array();
			if (isset($_POST["periods"]) && is_array($_POST["periods"]) && $periods = $_POST["periods"]) {
				foreach ($periods as $key=>$unproced_period) {
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

			if (isset($_POST["syllabus_id"]) && $tmp_input = clean_input($_POST["syllabus_id"], array("int"))) {
				$PROCESSED["syllabus"]["syllabus_id"] = $tmp_input;
			}

			if (isset($_POST["syllabus_enabled"]) && $tmp_input = clean_input($_POST["syllabus_enabled"], array("trim", "striptags"))) {
				$PROCESSED["syllabus"]["syllabus_enabled"] = $tmp_input == "enabled" ? 1 : 0;
			} else {
				$PROCESSED["syllabus"]["syllabus_enabled"] = 0;
			}
					
			if ($PROCESSED["syllabus"]["syllabus_enabled"] == 1) {
				if (isset($_POST["syllabus_template"]) && $tmp_input = clean_input($_POST["syllabus_template"], array("trim", "striptags"))) {
					$PROCESSED["syllabus"]["syllabus_template"] = $tmp_input;
				}
			}

			if (isset($_POST["course_report_ids"])) {						
				$PROCESSED["course_report_ids"] = array();
				foreach ($_POST["course_report_ids"] as $index => $tmp_input) {
					if ($course_report_id = clean_input($tmp_input, "int")) {								
						$PROCESSED["course_report_ids"][] = $course_report_id;
					}
				}
			}

            if (!has_error()) {
				$PROCESSED["updated_date"] = time();
				$PROCESSED["updated_by"] = $ENTRADA_USER->getID();
                $PROCESSED["course_redirect"] = 0;

				$courses_object = new Models_Course();

				if ($new_course = $courses_object->fromArray($PROCESSED)->insert()) {
					if ($COURSE_ID = $new_course->getID()) {

						/**
						 *  Uplaod the course image
						 */
						if (isset($_POST["imageaction"]) && $_POST["imageaction"] == "uploadimage") {
							$file_data = getimagesize($_FILES["image"]["tmp_name"]);
							$file_dimensions = $file_data[0] . "," . $file_data[1];

							if ($file_dimensions) {
								$dimensions = explode(",", $file_dimensions);
								foreach($dimensions as $dimension) {
									$tmp_dimensions[] = clean_input($dimension, "int");
								}
								$PROCESSED["dimensions"] = implode(",", $tmp_dimensions);
							}

							$sizes = array (1200, 600, 100);

							$percentage = array();
							foreach ($sizes as $size) {
								$percentage[] = round($size/$dimensions[0],2);
							}

							$sizes = array("large" => array("width" => 1200, "height" => round($dimensions[1] * $percentage[0])),
											"medium" => array("width" => 600, "height" => round($dimensions[1] * $percentage[1])),
											"small" => array("width" => 100, "height" => round($dimensions[1] * $percentage[2])),
							);

							$filesize = Entrada_Utilities_Image::uploadImage($_FILES["image"]["tmp_name"], $PROCESSED["dimensions"], $COURSE_ID, "course", $sizes);

							if ($filesize) {
								$PROCESSED_PHOTO["resource_id"] = $COURSE_ID;
								$PROCESSED_PHOTO["resource_type"] = "course";
								$PROCESSED_PHOTO["image_active"] = 1;
								$PROCESSED_PHOTO["image_filesize"] = $filesize;
								$PROCESSED_PHOTO["updated_date"] = time();

								$resource_image = new Models_Resource_Image();

								if (!$resource_image->fromArray($PROCESSED_PHOTO)->insert()) {
									add_error("We were unable to upload your course image at this time, please try again later.");

									application_log("error", "Unable to insert new course image into the database. Database said: " . $db->ErrorMsg());
								}
							} else {
								add_error("An error occurred while trying to upload your course image. Please select a new image file, and try again.");

								application_log("error", "Entrada_Utilities_Image::uploadImage() returned false when trying to upload a course image.");
							}
						}

						/**
						 * Insert Clinical Presentations.
						 */
						if ((is_array($clinical_presentations)) && (count($clinical_presentations))) {

							foreach ($clinical_presentations as $objective_id => $presentation_name) {
								$clinical_presentations_object = new Models_Course_Objective();
                                $objective_type = "event";
                                if (!$result = $clinical_presentations_object->fromArray(array("course_id" => $COURSE_ID, "objective_id" => $objective_id, "objective_type" => $objective_type, "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID()))->insert()) {
									add_error("There was an error when trying to insert a &quot;clinical presentation&quot; into the system. System administrators have been informed of this error; please try again later.");

									application_log("error", "Unable to insert a new clinical presentation to the database when adding a new event.");
								}
							}
						}


						if ((isset($_POST["associated_director"])) && ($associated_directors = explode(",", $_POST["associated_director"])) && (@is_array($associated_directors)) && (@count($associated_directors))) {
							$order = 0;

							foreach ($associated_directors as $proxy_id) {
								if ($proxy_id = clean_input($proxy_id, array("trim", "int"))) {
									$course_contacts_object = new Models_Course_Contact();
									if (!$result = $course_contacts_object->fromArray(array("course_id" => $COURSE_ID, "proxy_id" => $proxy_id, "contact_type" => "director", "contact_order" => $order))->insert()) {
										add_error("There was an error when trying to insert a &quot;" . $translate->_("course") . " Director&quot; into the system. The system administrator was informed of this error; please try again later.");

										application_log("error", "Unable to insert a new course_contact to the database when updating an event.");
									} else {
										$order++;
									}
								}
							}
						}


						if ((isset($_POST["associated_coordinator"])) && ($associated_coordinators = explode(",", $_POST["associated_coordinator"])) && (@is_array($associated_coordinators)) && (@count($associated_coordinators))) {
							foreach ($associated_coordinators as $proxy_id) {
								if ($proxy_id = clean_input($proxy_id, array("trim", "int"))) {
									$course_contacts_object = new Models_Course_Contact();
									if (!$result = $course_contacts_object->fromArray(array("course_id" => $COURSE_ID, "proxy_id" => $proxy_id, "contact_type" => "ccoordinator"))->insert()) {
										add_error("There was an error when trying to insert a &quot;Curriculum Coordinator&quot; into the system. The system administrator was informed of this error; please try again later.");

										application_log("error", "Unable to insert a new course_contact to the database when updating an event.");
									}
								}
							}
						}

						if ((isset($_POST["associated_faculty"])) && ($associated_faculties = explode(",", $_POST["associated_faculty"])) && (@is_array($associated_faculties)) && (@count($associated_faculties))) {
							foreach ($associated_faculties as $proxy_id) {
								if ($proxy_id = clean_input($proxy_id, array("trim", "int"))) {
									$course_contacts_object = new Models_Course_Contact();
									if (!$result = $course_contacts_object->fromArray(array("course_id" => $COURSE_ID, "proxy_id" => $proxy_id, "contact_type" => "faculty"))->insert()) {
										add_error("There was an error when trying to insert an &quot;Associated Faculty&quot; into the system. The system administrator was informed of this error; please try again later.");

										application_log("error", "Unable to insert a new course_contact to the database when updating an event.");
									}
								}
							}
						}

						if ((isset($_POST["associated_pcoordinator"])) && ($associated_program_coordinators = explode(",", $_POST["associated_pcoordinator"])) && (@is_array($associated_program_coordinators)) && (@count($associated_program_coordinators))) {
							foreach ($associated_program_coordinators as $proxy_id) {
								if ($proxy_id = clean_input($proxy_id, array("trim", "int"))) {
									$course_contacts_object = new Models_Course_Contact();
									if (!$result = $course_contacts_object->fromArray(array("course_id" => $COURSE_ID, "proxy_id" => $proxy_id, "contact_type" => "pcoordinator"))->insert()) {
										add_error("There was an error when trying to insert an &quot;Associated Project Coordinator&quot; into the system. The system administrator was informed of this error; please try again later.");

										application_log("error", "Unable to insert a new course_contact to the database when updating an event.");
									}
								}
							}
						}


						if ((isset($_POST["associated_evaluationrep"])) && ($associated_evaluationreps = explode(",", $_POST["associated_evaluationrep"])) && (@is_array($associated_evaluationreps)) && (@count($associated_evaluationreps))) {
							foreach ($associated_evaluationreps as $proxy_id) {
								if ($proxy_id = clean_input($proxy_id, array("trim", "int"))) {
									$course_contacts_object = new Models_Course_Contact();
									if (!$result = $course_contacts_object->fromArray(array("course_id" => $COURSE_ID, "proxy_id" => $proxy_id, "contact_type" => "evaluationrep"))->insert()) {
										add_error("There was an error when trying to insert an &quot;Associated Evaluation Rep&quot; into the system. The system administrator was informed of this error; please try again later.");

										application_log("error", "Unable to insert a new course_contact to the database when updating an event.");
									}
								}
							}
						}

						if ((isset($_POST["associated_studentrep"])) && ($associated_studentreps = explode(",", $_POST["associated_studentrep"])) && (@is_array($associated_studentreps)) && (@count($associated_studentreps))) {
							foreach ($associated_studentreps as $proxy_id) {
								if ($proxy_id = clean_input($proxy_id, array("trim", "int"))) {
									$course_contacts_object = new Models_Course_Contact();
									if (!$result = $course_contacts_object->fromArray(array("course_id" => $COURSE_ID, "proxy_id" => $proxy_id, "contact_type" => "studentrep"))->insert()) {
										add_error("There was an error when trying to insert an &quot;Associated Student Rep&quot; into the system. The system administrator was informed of this error; please try again later.");

										application_log("error", "Unable to insert a new course_contact to the database when updating an event. ");
									}
								}
							}
						}

						if ((isset($_POST["associated_ta"])) && ($associated_tas = explode(",", $_POST["associated_ta"])) && (@is_array($associated_tas)) && (@count($associated_tas))) {
							foreach ($associated_tas as $proxy_id) {
								if ($proxy_id = clean_input($proxy_id, array("trim", "int"))) {
									$course_contacts_object = new Models_Course_Contact();
									if (!$result = $course_contacts_object->fromArray(array("course_id" => $COURSE_ID, "proxy_id" => $proxy_id, "contact_type" => "ta"))->insert()) {
										add_error("There was an error when trying to insert an &quot;TA&quot; into the system. The system administrator was informed of this error; please try again later.");

										application_log("error", "Unable to insert a new course_contact to the database when updating an event. ");
									}
								}
							}
						}

						$course_keyword_object = new Models_Course_Keyword();
						// Update MeSH keywords
                        if (isset($_POST["delete_keywords"])) {
                            if (trim($_POST["delete_keywords"][0]) !== "") {                                                                        
                                $lis = explode(",", $_POST["delete_keywords"][0]);
                                $count = count($lis);                                                                 

                                if ($count > 0) {
                                    // Removed the keywords in the delete array.
                                    for ($i=0; $i<$count; $i++) {
                                        if (trim($lis[$i]) != "") {
											$course_keyword_object->deleteByCourseIDKeywordID($COURSE_ID, $lis[$i]);
                                        }
                                    }
                                }
                            }
                        }

                        if (isset($_POST["add_keywords"][0])) {
                            if (trim($_POST["add_keywords"][0]) !== "") {                                                                        
                                $lis = explode(",", $_POST["add_keywords"][0]);
                                $count = count($lis);                                                                 

                                if ($count > 0) {
                                    // Add the keywords n the add array.
                                    for ($i=0; $i<$count; $i++) {
                                        if (trim($lis[$i]) != "") {
											$course_keyword_object = new Models_Course_Keyword();
											$course_keyword_object->fromArray(array("course_id" => $COURSE_ID, "keyword_id" => $lis[$i], "updated_date" => time(), "updated_by" => $ENTRADA_USER->getID()))->insert();
                                        }
                                    }
                                }
                            }                                                                                                                             
                        }

						if (is_array($PRIMARY_OBJECTIVES) && count($PRIMARY_OBJECTIVES)) {
							foreach ($PRIMARY_OBJECTIVES as $objective_id) {
                                $objective = new Models_Course_Objective(array(
                                    "course_id"         => $COURSE_ID,
                                    "objective_id"      => $objective_id,
                                    "importance"        => "1",
                                    "objective_type"    => "course",
                                    "objective_start"   => time(),
                                    "objective_finish"  => NULL,
                                    "updated_date"      => time(),
                                    "updated_by"        => $ENTRADA_USER->getID(),
                                    "active"            => "1"
                                ));
                                $objective->insert();
							}
						}
						if (is_array($SECONDARY_OBJECTIVES) && count($SECONDARY_OBJECTIVES)) {
							foreach ($SECONDARY_OBJECTIVES as $objective_id) {
                                $objective = new Models_Course_Objective(array(
                                    "course_id"         => $COURSE_ID,
                                    "objective_id"      => $objective_id,
                                    "importance"        => "2",
                                    "objective_type"    => "course",
                                    "objective_start"   => time(),
                                    "objective_finish"  => NULL,
                                    "updated_date"      => time(),
                                    "updated_by"        => $ENTRADA_USER->getID(),
                                    "active"            => "1"
                                ));
                                $objective->insert();
							}
						}
						if (is_array($TERTIARY_OBJECTIVES) && count($TERTIARY_OBJECTIVES)) {
							foreach ($TERTIARY_OBJECTIVES as $objective_id) {
                                $objective = new Models_Course_Objective(array(
                                    "course_id"         => $COURSE_ID,
                                    "objective_id"      => $objective_id,
                                    "importance"        => "3",
                                    "objective_type"    => "course",
                                    "objective_start"   => time(),
                                    "objective_finish"  => NULL,
                                    "updated_date"      => time(),
                                    "updated_by"        => $ENTRADA_USER->getID(),
                                    "active"            => "1"
                                ));
                                $objective->insert();
							}
						}

						if (is_array($COURSE_TRACKS) && (count($COURSE_TRACKS) > 1 || $COURSE_TRACKS[0] != 0)) {
							foreach ($COURSE_TRACKS as $track_id) {
								$mandatory = clean_input($_POST["track_mandatory_".$track_id], "int");
								$result = Models_Course::insertTrackCourseRelationship($track_id,$COURSE_ID, $mandatory);
								if (!$result) {
									add_error("An error occurred while adding the track with id ".$track_id." as a course track.");
								}
							}
						}

						if (isset($PROCESSED["periods"]) && is_array($PROCESSED["periods"]) && $PROCESSED["periods"]) {

							foreach ($PROCESSED["periods"] as $period_id=>$period) {
								foreach ($period as $key=>$audience) {
									$audience["course_id"] = $COURSE_ID;
									$course_audience_object = new Models_Course_Audience();
									if (!$result = $course_audience_object->fromArray($audience)->insert()) {
										add_error("An error occurred while adding the student with id ".$member." as an audience member.");
									}
								}
							}
						}

						if (isset($PROCESSED["course_report_ids"]) && count($PROCESSED["course_report_ids"]) > 0) {
							foreach ($PROCESSED["course_report_ids"] as $index => $course_report_id) {
                                $course_reports_object = new Models_Course_Report();
								$PROCESSED["course_report_id"] = $course_report_id;		
								$PROCESSED["course_id"] = $COURSE_ID;								

								if ($result = $course_reports_object->fromArray($PROCESSED)->insert()) {
									add_statistic("Course Edit", "edit", "course_reports.course_report_id", $PROCESSED["course_report_id"], $ENTRADA_USER->getID());
								} else {
									add_error("An error occurred while adding course reports.  The system administrator was informed of this error; please try again later.");
									application_log("error", "Error inserting course reports for course id: " . $COURSE_ID);
								}									
							}
						}

						if (!has_error()) {
							switch ($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"]) {
								case "content" :
									$url = ENTRADA_URL."/admin/".$MODULE."?section=content&id=".$COURSE_ID;
									$msg = "You will now be redirected to the course content page; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
								break;
								case "new" :
									$url = ENTRADA_URL."/admin/".$MODULE."?section=add";
									$msg = "You will now be redirected to add a new course; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
								break;
								case "index" :
								default :
									$url = ENTRADA_URL."/admin/".$MODULE;
									$msg = "You will now be redirected to the course index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
								break;
							}
                            
                            if (isset($PROCESSED["syllabus"])) {
								
								$course_syllabus_object = new Models_Course_Syllabus();
								
                                if (!empty($period_list)) {
									$curriculum_period_object = new Models_Curriculum_Period();
									$period_data = $curriculum_period_object->fetchRowByMultipleIDAsc($period_list);
                                } else {
                                    $period_data["start_date"] = mktime(0, 0, 0, 1);
                                    $period_data["finish_date"] = mktime(0, 0, 0, 12);
                                }

                                $syllabus_start = date("n", $period_data["start_date"]);
                                $syllabus_finish = date("n", $period_data["finish_date"]);

                                $syllabus_data["course_id"] = $COURSE_ID;
                                $syllabus_data["template"] = $PROCESSED["syllabus"]["syllabus_template"];
                                $syllabus_data["active"] = $PROCESSED["syllabus"]["syllabus_enabled"];
                                $syllabus_data["repeat"] = 1;
                                $syllabus_data["syllabus_start"] = $syllabus_start;
                                $syllabus_data["syllabus_finish"] = $syllabus_finish;


								if (isset($PROCESSED["syllabus"]["syllabus_id"])) {
									$update_syllabi = $course_syllabus_object->fetchRowByID($PROCESSED["syllabus"]["syllabus_id"]);
									if (!$update_syllabi->fromArray($syllabus_data)->update()) {
										add_error("An error occurred while attempting to update the course syllabus, an administrator has been informed, please try again later.");
										application_log("error", "Error on course syllabus update.");
									}
								} else {
									if (!$new_syllabi = $course_syllabus_object->fromArray($syllabus_data)->insert()) {
										add_error("An error occurred while attempting to update the course syllabus, an administrator has been informed, please try again later.");
										application_log("error", "Error on course syllabus insert.");
									}
								}
                            }

							$ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";
							add_success("You have successfully added <strong>".html_encode($PROCESSED["course_name"])."</strong> to this system.<br /><br />".$msg);

							application_log("success", "New course [".$COURSE_ID."] added to the system.");
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

			$course_directors	= array();
			$curriculum_coordinators = array();
			$chosen_course_directors	= array();
			$faculty	= array();

			$user_object = new Models_User();
			$results = $user_object->getDirectors($ENTRADA_USER->getActiveOrganisation());
			if ($results) {
				foreach ($results as $result) {
					$course_directors[$result["proxy_id"]] = array('proxy_id'=>$result["proxy_id"], 'fullname'=>$result["fullname"], 'organisation_id'=>$result['organisation_id']);
				}
				$DIRECTOR_LIST = $course_directors;
			}

			
			$results = $user_object->getCurriculumCoordinators();
			if ($results) {
				foreach ($results as $result) {
					$curriculum_coordinators[$result["proxy_id"]] = array('proxy_id'=>$result["proxy_id"], 'fullname'=>$result["fullname"], 'organisation_id'=>$result['organisation_id']);
				}
				$COORDINATOR_LIST = $curriculum_coordinators;
			}

			$results = $user_object->getFaculties();
			if ($results) {
				foreach ($results as $result) {
					$faculty[$result["proxy_id"]] = array('proxy_id'=>$result["proxy_id"], 'fullname'=>$result["fullname"], 'organisation_id'=>$result['organisation_id']);
				}
				$ASSOCIATED_FACULTY_LIST = $faculty;
			}

			/**
			 * Non-required field "associated_faculty" / Associated Faculty (array of proxy ids).
			 * This is actually accomplished after the event is inserted below.
			 */
			if ((isset($_POST["associated_director"]))) {
				$associated_director = explode(',', $_POST["associated_director"]);
				foreach ($associated_director as $contact_order => $proxy_id) {
					if ($proxy_id = clean_input($proxy_id, array("trim", "int"))) {
						$chosen_course_directors[(int) $contact_order] = $proxy_id;
					}
				}
			}

			if ((isset($_POST["associated_coordinator"]))) {
				$associated_coordinator = explode(',',$_POST["associated_coordinator"]);
				foreach ($associated_coordinator as $contact_order => $proxy_id) {
					if ($proxy_id = clean_input($proxy_id, array("trim", "int"))) {
						$chosen_ccoordinators[] = $proxy_id;
					}
				}
			}

			if ((isset($_POST["associated_faculty"]))) {
				$associated_faculty = explode(',',$_POST["associated_faculty"]);
				foreach ($associated_faculty as $contact_order => $proxy_id) {
					if ($proxy_id = clean_input($proxy_id, array("trim", "int"))) {
						$chosen_associated_faculty[] = $proxy_id;
					}
				}
			}

		if ((isset($_POST["associated_pcoordinator"]))) {
			$program_coordinator = explode(',',$_POST["associated_pcoordinator"]);
			foreach ($program_coordinator as $contact_order => $proxy_id) {
				if ($proxy_id = clean_input($proxy_id, array("trim", "int"))) {
					$chosen_program_coordinators[] = $proxy_id;
				}
			}
		}

		if ((isset($_POST["associated_evaluationrep"]))) {
			$evaluationreps = explode(',',$_POST["associated_evaluationrep"]);
			foreach ($evaluationreps as $contact_order => $proxy_id) {
				if ($proxy_id = clean_input($proxy_id, array("trim", "int"))) {
					$chosen_evaluationreps[] = $proxy_id;
				}
			}
		}

		if ((isset($_POST["associated_studentrep"]))) {
			$studentreps = explode(',',$_POST["associated_studentrep"]);
			foreach ($studentreps as $contact_order => $proxy_id) {
				if ($proxy_id = clean_input($proxy_id, array("trim", "int"))) {
					$chosen_studentreps[] = $proxy_id;
				}
			}
		}

		if ((isset($_POST["associated_ta"]))) {
			$tas = explode(',',$_POST["associated_ta"]);
			foreach ($tas as $contact_order => $proxy_id) {
				if ($proxy_id = clean_input($proxy_id, array("trim", "int"))) {
					$chosen_tas[] = $proxy_id;
				}
			}
		}


			// Compiles Program Coordinator list
			$programcoodinators= array();


			$results = $user_object->getProgramCoordinators($ENTRADA_USER->getActiveOrganisation());
			if ($results) {
				foreach ($results as $result) {
					$programcoodinators[$result["proxy_id"]] = array('proxy_id'=>$result["proxy_id"], 'fullname'=>$result["fullname"]);
				}
				$ASSOCIATED_PROGRAM_COORDINATORS_LIST = $programcoodinators;
			}

			// Compiles Evaluation Representative (evalrep_id)  list
			$evaluationreps = array();


			$results = $user_object->getEvaluationReps();
			if ($results) {
				foreach ($results as $result) {
					$evaluationreps[$result["proxy_id"]] = array('proxy_id'=>$result["proxy_id"], 'fullname'=>$result["fullname"]);
				}
				$ASSOCIATED_EVALUATIONREP_LIST = $evaluationreps;
			}

			// Compiles Student Representative (evalrep_id)  list
			$studentreps = array();
			$results = $user_object->getStudentReps();
			if ($results) {
				foreach ($results as $result) {
					$studentreps[$result["proxy_id"]] = array('proxy_id'=>$result["proxy_id"], 'fullname'=>$result["fullname"]);
				}
				$ASSOCIATED_STUDENTREP_LIST = $studentreps;

			}

			$tas = array();
			$results = $user_object->getStudentReps();
			if ($results) {
				foreach ($results as $result) {
					$tas[$result["proxy_id"]] = array('proxy_id'=>$result["proxy_id"], 'fullname'=>$result["fullname"]);
				}
				$ASSOCIATED_TA_LIST = $tas;

			}
			if (has_error()) {
				echo display_error();
			}
			?>
			<form class="form-horizontal" action="<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>?<?php echo replace_query(array("step" => 2)); ?>" method="post" id="courseForm" enctype="multipart/form-data">
				<h2 title="Course Setup Section"><?php echo $translate->_("Course Setup"); ?></h2>
                <div id="course-setup-section">
                    <div class="control-group">
                        <label for="curriculum_type_id" class="control-label form-nrequired"><?php echo $translate->_("Curriculum Layout"); ?></label>
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
                        <label for="course_name" class="form-required control-label"><?php echo $translate->_("course"); ?> Name</label>
                        <div class="controls">
                            <input type="text" id="course_name" name="course_name" value="<?php echo html_encode((isset($PROCESSED["course_name"]) && $PROCESSED["course_name"] ? $PROCESSED["course_name"] : "")); ?>"maxlength="85" class="span7" />
                        </div>
                    </div>

                    <div class="control-group">
                        <label for="course_code" class="form-required control-label"><?php echo $translate->_("course"); ?> Code</label>
                        <div class="controls">
                        <input type="text" id="course_code" name="course_code" value="<?php echo html_encode((isset($PROCESSED["course_code"]) && $PROCESSED["course_code"] ? $PROCESSED["course_code"] : "")); ?>"/>
                        </div>
                    </div>

                    <div class="control-group">
                        <label for="course_color" class="form-nrequired control-label"><?php echo $translate->_("course")." ".$translate->_("Colour"); ?></label>
                        <div class="controls">
                            <input type="text" id="course_color" name="course_color" value="<?php echo html_encode(!empty($PROCESSED["course_color"]) ? $PROCESSED["course_color"] : ""); ?>" maxlength="20" class="span3">
                        </div>
                    </div>

                    <div class="control-group">
                        <label for="course_credit" class="control-label"><?php echo $translate->_("course"); ?> Credit</label>
                        <div class="controls">
                            <input type="text" id="course_credit" name="course_credit" value="<?php echo html_encode($PROCESSED["course_credit"]); ?>" maxlength="3">
                        </div>
                    </div>

					<div class="control-group">
						<label class="form-nrequired control-label">Course Type</label>
						<div class="controls">
							<label for="course_mandatory_on" class="radio">
								<input type="radio" name="course_mandatory" id="course_mandatory_on" value="1"<?php echo ((!isset($PROCESSED["course_mandatory"]) || (isset($PROCESSED["course_mandatory"]) && $PROCESSED["course_mandatory"])) ? " checked=\"checked\"" : ""); ?> />
								This is a <strong>core</strong> course for this program.
							</label>
							<label for="course_mandatory_off" class="radio">
								<input type="radio" name="course_mandatory" id="course_mandatory_off" value="0"<?php echo ((isset($PROCESSED["course_mandatory"]) && !$PROCESSED["course_mandatory"]) ? " checked=\"checked\"" : ""); ?> />
								This is an <strong>option</strong> course for this program.
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
                                        foreach($COURSE_TRACKS as $course_track_id) {
                                            $course_track = Models_Curriculum_Track::fetchRowByID($course_track_id);
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
                                    ?>
                                </tbody>
                                <input id="curriculum_tracks_values" name="curriculum_tracks_values" style="display: none;">
                            </table>
                        </div>
                    </div>
                    <?php } ?>
                    <div class="control-group">
                        <label class="control-label form-nrequired"><?php echo $translate->_("course"); ?> Permissions</label>
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
								<div class="well well-small content-small">Please enter a comma separated list of alphanumeric course codes you wish to synchronize with in the textarea below. You can add additional individuals and groups manually using the <strong>Course Enrolment</strong> section below.</div>
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
					<h2 title="Course Twitter Section"><?php echo $translate->_("course"); ?> Twitter Settings</h2>
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

                <h2 title="Course Contacts Section"><?php echo $translate->_("course"); ?> Contacts</h2>
                    <div id="course-contacts-section">
                        <div id="autocomplete">
                            <div id="autocomplete-list-container"></div>
                        </div>
                        <div class="control-group">
                            <label for="director_name" class="control-label form-nrequired"><?php echo $translate->_("Course Directors"); ?></label>
                            <div class="controls">
                                <input id="director_name" type="text" class="form-control search"  name="fullname" placeholder="<?php echo $translate->_("Type to search for directors..."); ?>">
                                <input type="hidden" id="associated_director" name="associated_director" />
                                <ul id="director_list" class="menu" style="margin-top: 15px">
                                    <?php
                                    if (is_array($chosen_course_directors) && count($chosen_course_directors) && isset($DIRECTOR_LIST) && !empty($DIRECTOR_LIST)) {
                                        foreach ($chosen_course_directors as $director) {
                                            if ((array_key_exists($director, $DIRECTOR_LIST)) && is_array($DIRECTOR_LIST[$director])) {
                                                ?>
                                                    <li class="community" id="director_<?php echo $DIRECTOR_LIST[$director]["proxy_id"]; ?>" data-proxy-id="<?php echo $DIRECTOR_LIST[$director]["proxy_id"]; ?>" style="cursor: move;"><?php echo $DIRECTOR_LIST[$director]["fullname"]; ?><img src="<?php echo ENTRADA_URL; ?>/images/action-delete.gif" class="list-cancel-image" onclick="removeItem('<?php echo $DIRECTOR_LIST[$director]["proxy_id"]; ?>', 'director');"/></li>
                                                <?php
                                            }
                                        }
                                    }
                                    ?>
                                </ul>
                                <input type="hidden" id="director_ref" name="director_ref" value="" />
                                <input type="hidden" id="director_id" name="director_id" value="" />
                            </div>
                        </div>

                        <div class="control-group">
                            <label for="coordinator_name" class="control-label form-nrequired"><?php echo $translate->_("Curriculum Coordinators"); ?></label>
                            <div class="controls">
                                <input id="coordinator_name" type="text" class="form-control search"  name="fullname" placeholder="<?php echo $translate->_("Type to search for coordinators..."); ?>">
                                <input type="hidden" id="associated_coordinator" name="associated_coordinator" />
                                <ul id="coordinator_list" class="menu" style="margin-top: 15px">
                                    <?php
                                    if (isset($chosen_ccoordinators) && is_array($chosen_ccoordinators) && $chosen_ccoordinators && isset($COORDINATOR_LIST) && !empty($COORDINATOR_LIST)) {
                                        foreach ($chosen_ccoordinators as $coordinator) {
                                            if ((array_key_exists($coordinator, $COORDINATOR_LIST)) && is_array($COORDINATOR_LIST[$coordinator])) {
                                                ?>
                                                <li class="community" id="coordinator_<?php echo $COORDINATOR_LIST[$coordinator]["proxy_id"]; ?>" data-proxy-id="<?php echo $COORDINATOR_LIST[$coordinator]["proxy_id"]; ?>" style="cursor: move;"><?php echo $COORDINATOR_LIST[$coordinator]["fullname"]; ?><img src="<?php echo ENTRADA_URL; ?>/images/action-delete.gif" class="list-cancel-image" onclick="removeItem('<?php echo $COORDINATOR_LIST[$coordinator]["proxy_id"]; ?>', 'coordinator');"/></li>
                                                <?php
                                            }
                                        }
                                    }
                                    ?>
                                </ul>
                                <input type="hidden" id="coordinator_ref" name="coordinator_ref" value="" />
                                <input type="hidden" id="coordinator_id" name="coordinator_id" value="" />
                            </div>
                        </div>

						<div class="control-group">
							<label for="faculty_name" class="control-label form-nrequired"><?php echo $translate->_("Associated Faculty"); ?></label>
							<div class="controls">
								<input id="faculty_name" type="text" class="form-control search"  name="fullname" placeholder="<?php echo $translate->_("Type to search for faculty..."); ?>">
								<input type="hidden" id="associated_faculty" name="associated_faculty" />
								<ul id="faculty_list" class="menu" style="margin-top: 15px">
									<?php
									if (isset($chosen_associated_faculty) && is_array($chosen_associated_faculty) && $chosen_associated_faculty && isset($ASSOCIATED_FACULTY_LIST) && !empty($ASSOCIATED_FACULTY_LIST)) {
										foreach ($chosen_associated_faculty as $chosen_faculty) {
											if ((array_key_exists($chosen_faculty, $ASSOCIATED_FACULTY_LIST)) && is_array($ASSOCIATED_FACULTY_LIST[$chosen_faculty])) {
												?>
												<li class="community" id="faculty_<?php echo $ASSOCIATED_FACULTY_LIST[$chosen_faculty]["proxy_id"]; ?>" data-proxy-id="<?php echo $ASSOCIATED_FACULTY_LIST[$chosen_faculty]["proxy_id"]; ?>" style="cursor: move;"><?php echo $ASSOCIATED_FACULTY_LIST[$chosen_faculty]["fullname"]; ?><img src="<?php echo ENTRADA_URL; ?>/images/action-delete.gif" class="list-cancel-image" onclick="removeItem('<?php echo $ASSOCIATED_FACULTY_LIST[$chosen_faculty]["proxy_id"]; ?>', 'faculty');"/></li>
												<?php
											}
										}
									}
									?>
								</ul>
								<input type="hidden" id="faculty_ref" name="faculty_ref" value="" />
								<input type="hidden" id="faculty_id" name="faculty_id" value="" />
							</div>
						</div>

						<div class="control-group">
							<label for="pcoordinator_name" class="control-label form-nrequired"><?php echo $translate->_("Program Coordinator"); ?></label>
							<div class="controls">
								<input id="pcoordinator_name" type="text" class="form-control search"  name="fullname" placeholder="<?php echo $translate->_("Type to search for program coordinator..."); ?>">
								<input type="hidden" id="associated_pcoordinator" name="associated_pcoordinator" />
								<ul id="pcoordinator_list" class="menu" style="margin-top: 15px">
									<?php
									if (isset($chosen_program_coordinators) && is_array($chosen_program_coordinators) && $chosen_program_coordinators && isset($ASSOCIATED_PROGRAM_COORDINATORS_LIST) && !empty($ASSOCIATED_PROGRAM_COORDINATORS_LIST)) {
										foreach ($chosen_program_coordinators as $chosen_coordinator) {
											if ((array_key_exists($chosen_coordinator, $ASSOCIATED_PROGRAM_COORDINATORS_LIST)) && is_array($ASSOCIATED_PROGRAM_COORDINATORS_LIST[$chosen_coordinator])) {
												?>
												<li class="community" id="pcoordinator_<?php echo $ASSOCIATED_PROGRAM_COORDINATORS_LIST[$chosen_coordinator]["proxy_id"]; ?>" data-proxy-id="<?php echo $ASSOCIATED_PROGRAM_COORDINATORS_LIST[$chosen_coordinator]["proxy_id"]; ?>" style="cursor: move;"><?php echo $ASSOCIATED_PROGRAM_COORDINATORS_LIST[$chosen_coordinator]["fullname"]; ?><img src="<?php echo ENTRADA_URL; ?>/images/action-delete.gif" class="list-cancel-image" onclick="removeItem('<?php echo $ASSOCIATED_PROGRAM_COORDINATORS_LIST[$chosen_coordinator]["proxy_id"]; ?>', 'pcoordinator');"/></li>
												<?php
											}
										}
									}
									?>
								</ul>
								<input type="hidden" id="pcoordinator_ref" name="pcoordinator_ref" value="" />
								<input type="hidden" id="pcoordinator_id" name="pcoordinator_id" value="" />
							</div>
						</div>
						<div class="control-group">
							<label for="evaluationrep_name" class="control-label form-nrequired"><?php echo $translate->_("Evaluation Rep"); ?></label>
							<div class="controls">
								<input id="evaluationrep_name" type="text" class="form-control search"  name="fullname" placeholder="<?php echo $translate->_("Type to search for program evaluation rep..."); ?>">
								<input type="hidden" id="associated_evaluationrep" name="associated_evaluationrep" />
								<ul id="evaluationrep_list" class="menu" style="margin-top: 15px">
									<?php
									if (isset($chosen_evaluationreps) && is_array($chosen_evaluationreps) && $chosen_evaluationreps && isset($ASSOCIATED_EVALUATIONREP_LIST) && !empty($ASSOCIATED_EVALUATIONREP_LIST)) {
										foreach ($chosen_evaluationreps as $chosen_evaluationrep) {
											if ((array_key_exists($chosen_evaluationrep, $ASSOCIATED_EVALUATIONREP_LIST)) && is_array($ASSOCIATED_EVALUATIONREP_LIST[$chosen_evaluationrep])) {
												?>
												<li class="community" id="evaluationrep_<?php echo $ASSOCIATED_EVALUATIONREP_LIST[$chosen_evaluationrep]["proxy_id"]; ?>" data-proxy-id="<?php echo $ASSOCIATED_EVALUATIONREP_LIST[$chosen_evaluationrep]["proxy_id"]; ?>" style="cursor: move;"><?php echo $ASSOCIATED_EVALUATIONREP_LIST[$chosen_evaluationrep]["fullname"]; ?><img src="<?php echo ENTRADA_URL; ?>/images/action-delete.gif" class="list-cancel-image" onclick="removeItem('<?php echo $ASSOCIATED_EVALUATIONREP_LIST[$chosen_evaluationrep]["proxy_id"]; ?>', 'evaluationrep');"/></li>
												<?php
											}
										}
									}
									?>
								</ul>
								<input type="hidden" id="evaluationrep_ref" name="evaluationrep_ref" value="" />
								<input type="hidden" id="evaluationrep_id" name="evaluationrep_id" value="" />
							</div>
						</div>
						<div class="control-group">
							<label for="studentrep_name" class="control-label form-nrequired"><?php echo $translate->_("Student Rep"); ?></label>
							<div class="controls">
								<input id="studentrep_name" type="text" class="form-control search"  name="fullname" placeholder="<?php echo $translate->_("Type to search for program student rep..."); ?>">
								<input type="hidden" id="associated_studentrep" name="associated_studentrep" />
								<ul id="studentrep_list" class="menu" style="margin-top: 15px">
									<?php
									if (isset($chosen_studentreps) && is_array($chosen_studentreps) && $chosen_studentreps && isset($ASSOCIATED_STUDENTREP_LIST) && !empty($ASSOCIATED_STUDENTREP_LIST)) {
										foreach ($chosen_studentreps as $chosen_studentrep) {
											if ((array_key_exists($chosen_studentrep, $ASSOCIATED_STUDENTREP_LIST)) && is_array($ASSOCIATED_STUDENTREP_LIST[$chosen_studentrep])) {
												?>
												<li class="community" id="studentrep_<?php echo $ASSOCIATED_STUDENTREP_LIST[$chosen_studentrep]["proxy_id"]; ?>" data-proxy-id="<?php echo $ASSOCIATED_STUDENTREP_LIST[$chosen_studentrep]["proxy_id"]; ?>" style="cursor: move;"><?php echo $ASSOCIATED_STUDENTREP_LIST[$chosen_studentrep]["fullname"]; ?><img src="<?php echo ENTRADA_URL; ?>/images/action-delete.gif" class="list-cancel-image" onclick="removeItem('<?php echo $ASSOCIATED_STUDENTREP_LIST[$chosen_studentrep]["proxy_id"]; ?>', 'studentrep');"/></li>
												<?php
											}
										}
									}
									?>
								</ul>
								<input type="hidden" id="studentrep_ref" name="studentrep_ref" value="" />
								<input type="hidden" id="studentrep_id" name="studentrep_id" value="" />
							</div>
						</div>
						<div class="control-group">
							<label for="ta_name" class="control-label form-nrequired"><?php echo $translate->_("Teacher Assistant"); ?></label>
							<div class="controls">
								<input id="ta_name" type="text" class="form-control search"  name="fullname" placeholder="<?php echo $translate->_("Type to search for TA ..."); ?>">
								<input type="hidden" id="associated_ta" name="associated_ta" />
								<ul id="ta_list" class="menu" style="margin-top: 15px">
									<?php
									if (isset($chosen_tas) && is_array($chosen_tas) && $chosen_tas && isset($ASSOCIATED_TA_LIST) && !empty($ASSOCIATED_TA_LIST)) {
										foreach ($chosen_tas as $chosen_ta) {
											if ((array_key_exists($chosen_ta, $ASSOCIATED_TA_LIST)) && is_array($ASSOCIATED_TA_LIST[$chosen_ta])) {
												?>
												<li class="community" id="ta_<?php echo $ASSOCIATED_TA_LIST[$chosen_ta]["proxy_id"]; ?>" data-proxy-id="<?php echo $ASSOCIATED_TA_LIST[$chosen_ta]["proxy_id"]; ?>" style="cursor: move;"><?php echo $ASSOCIATED_TA_LIST[$chosen_ta]["fullname"]; ?><img src="<?php echo ENTRADA_URL; ?>/images/action-delete.gif" class="list-cancel-image" onclick="removeItem('<?php echo $ASSOCIATED_TA_LIST[$chosen_ta]["proxy_id"]; ?>', 'ta');"/></li>
												<?php
											}
										}
									}
									?>
								</ul>
								<input type="hidden" id="ta_ref" name="ta_ref" value="" />
								<input type="hidden" id="ta_id" name="ta_id" value="" />
							</div>
						</div>

					</div>
                    <?php
                    /**
                     * Test to see if the MeSH tables have been loaded or not,
                     * since this is an optional Entrada feature.
                     */
					$base_model = new Models_Base();
                    if ($base_model->tableExists(DATABASE_NAME,'mesh_terms')) {
                        ?>
                        <a name="course-keywords-section"></a>
                        <h2 title="Course Keywords Section"><?php echo $translate->_("course"); ?> Keywords</h2>
                        <div id="course-keywords-section">
                            <div class="keywords half left">
                                <h3>Keyword Search</h3>
                                <div>
                                    Search MeSH Keywords
                                    <input id="search" autocomplete="off" type="text" name="keyword">
                                </div>

                                <div id="search_results">
                                    <div id="inserted"></div>
                                    <div id="results"><ul></ul></div>
                                </div>
                            </div>
                            <div class="mapped_keywords right">
                                <h3>Attached Keywords</h3>
                                <div class="clearfix">
                                    <ul class="page-action" style="float: right">
                                        <div class="row-fluid space-below">
                                            <a href="javascript:void(0)" class="keyword-toggle btn btn-success btn-small pull-right" keyword-toggle="show" id="toggle_sets"><i class="icon-plus-sign icon-white"></i> Show Keyword Search</a>
                                        </div>
                                    </ul>
                                </div>
                                <p class="well well-small content-small">
                                    <strong>Helpful Tip:</strong> Click <strong>Show Keyword Search</strong> to search from the MeSH keyword database. Click + to add to, - to remove from, the course.
                                </p>

                                <div id="tagged">
                                    <div id="right1">
                                        <ul>
                                        <?php
                                        $course_keyword_object = new Models_Course_Keyword();
                                        $results = $course_keyword_object->getAllDescriptorsByCourseID($COURSE_ID);
                                        if ($results) {
                                            foreach ($results as $result) {
                                                echo "<li data-dui=\"" . $result['keyword_id'] . "\" data-dname=\"" . $result['descriptor_name'] . "\" id=\"tagged_keyword\" onclick=\"removeval(this, '" . $result['keyword_id'] . "')\"><i class=\"icon-minus-sign \"></i> " . $result['descriptor_name'] . "</li>";
                                            }
                                        }
                                        ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="delete_keywords[]" id="delete_keywords" value=""/>
                            <input type="hidden" name="add_keywords[]" id="add_keywords" value=""/>
                        </div>
                        <?php
                    }
                    ?>

                    <div style="clear:both;"></div>

                    <?php
                    require_once(ENTRADA_ABSOLUTE."/javascript/courses.js.php");

                    $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/elementresizer.js\"></script>\n";

                    $objectives_object = new Models_Objective();
                    $objectives = $objectives_object->getAllByCourseAndOrganisation($COURSE_ID, $ENTRADA_USER->getActiveOrganisation());
                    if ($objectives) {
                        $objective_name = $translate->_("events_filter_controls");
                        $hierarchical_name = $objective_name["co"]["global_lu_objectives_name"];
                        ?>
                        <a name="course-objectives-section"></a>
                        <h2 title="<?php echo $translate->_("Course Objectives Section"); ?>"><?php echo $translate->_("course"); ?> <?php echo $translate->_("Objectives"); ?></h2>
                        <div id="course-objectives-section">
                        <div class="objectives half left">
                            <h3><?php echo $translate->_("Curriculum Tag Sets"); ?></h3>
                            <ul class="tl-objective-list" id="objective_list_0">
                                <?php
                                foreach ($objectives as $objective) {
                                    ?>
                                    <li class = "objective-container objective-set"
                                        id = "objective_<?php echo $objective["objective_id"]; ?>"
                                        data-list="<?php echo $objective["objective_name"] == $hierarchical_name?'hierarchical':'flat'; ?>"
                                        data-id="<?php echo $objective["objective_id"];?>">
                                        <?php $title = ($objective["objective_code"]?$objective["objective_code"].': '.$objective["objective_name"] : $objective["objective_name"]); ?>
                                        <div class="objective-title"
                                                id="objective_title_<?php echo $objective["objective_id"]; ?>"
                                                data-title="<?php echo $title;?>"
                                                data-id = "<?php echo $objective["objective_id"]; ?>"
                                                data-code = "<?php echo $objective["objective_code"]; ?>"
                                                data-name = "<?php echo $objective["objective_name"]; ?>"
                                                data-description = "<?php echo $objective["objective_description"]; ?>">
                                            <h4><?php echo $title; ?></h4>
                                        </div>
                                        <div class="objective-controls" id="objective_controls_<?php echo $objective["objective_id"];?>">
                                        </div>
                                        <div class="objective-children"
                                                id="children_<?php echo $objective["objective_id"]; ?>">
                                                <ul class="objective-list" id="objective_list_<?php echo $objective["objective_id"]; ?>">
                                                </ul>
                                        </div>
                                    </li>
                                    <?php
                                }
                                ?>
						    </ul>
					    </div>

					    <div class="mapped_objectives right droppable" id="mapped_objectives" data-resource-type="course" data-resource-id="<?php echo $COURSE_ID;?>">
                            <h3>Mapped <?php echo $translate->_("Objectives"); ?></h3>
                            <div class="clearfix">
                                <ul class="page-action" style="float: right">
                                    <div class="row-fluid space-below">
                                        <a href="javascript:void(0)" class="mapping-toggle btn btn-success btn-small pull-right" data-toggle="show" id="toggle_sets"><i class="icon-plus-sign icon-white"></i> Show <?php echo $translate->_("Curriculum Tag Sets"); ?></a>
                                    </div>
                                </ul>
                            </div>
                            <p class="well well-small content-small">
                                <strong>Helpful Tip:</strong> Click <strong>Show All <?php echo $translate->_("Objectives"); ?></strong> to view the list of available objectives. Select an objective from the list on the left to map it to the course.
                            </p>
							<?php
                            $mapped_objectives = $objectives_object->getAllMappedByCourse($COURSE_ID);
                            $primary = false;
                            $secondary = false;
                            $tertiary = false;
                            $hierarchical_objectives = array();
                            $flat_objectives = array();
                            $objective_importance = array();
                            if ($mapped_objectives) {
                                foreach ($mapped_objectives as $objective) {
                                    // @todo this should be using id from language file, not hardcoded to 1
                                    if ($objective["objective_type"] == "course") {
                                        $hierarchical_objectives[] = $objective;
                                        $objective_importance[$objective["importance"]][] = $objective;
                                    } else {
                                        $flat_objectives[] = $objective;
                                    }
                                }
                            }
                            ?>
                            <a name="curriculum-objective-list"></a>
                            <h2 id="hierarchical-toggle" title="<?php echo $translate->_("Curriculum Objective List"); ?>" class="list-heading"><?php echo $translate->_("Curriculum Objectives"); ?></h2>
                            <div id="curriculum-objective-list">
                                <ul class="objective-list mapped-list" id="mapped_hierarchical_objectives" data-importance="hierarchical">
									<?php
                                    if ($hierarchical_objectives) {
                                        foreach ($hierarchical_objectives as $objective) {
                                            $title = ($objective["objective_code"]?$objective["objective_code"].': '.$objective["objective_name"]:$objective["objective_name"]);
                                            ?>
                                            <li class = "mapped-objective"
                                                id = "mapped_objective_<?php echo $objective["objective_id"]; ?>"
                                                data-title="<?php echo $title;?>"
                                                data-description="<?php echo $objective["objective_description"];?>">
                                                <strong><?php echo $title; ?></strong>
                                                <div class="objective-description">
                                                    <?php
                                                    $set = fetch_objective_set_for_objective_id($objective["objective_id"]);
                                                    if ($set) {
                                                        echo "From the ".$translate->_("Curriculum Tag Set").": <strong>".$set["objective_name"]."</strong><br/>";
                                                    }
                                                    ?>
                                                    <?php echo $objective["objective_description"];?>
                                                </div>
                                                <div class="objective-controls">
                                                    <select class="importance mini input-small" data-id="<?php echo $objective["objective_id"]; ?>" data-value="<?php echo $objective["importance"]; ?>">
                                                        <option value="1"<?php echo $objective["importance"] == 1?' selected="selected"':''; ?>>Primary</option>
                                                        <option value="2"<?php echo $objective["importance"] == 2?' selected="selected"':''; ?>>Secondary</option>
                                                        <option value="3"<?php echo $objective["importance"] == 3?' selected="selected"':''; ?>>Tertiary</option>
                                                    </select>
                                                    <img 	src="<?php echo ENTRADA_URL;?>/images/action-delete.gif"
                                                            class="objective-remove list-cancel-image"
                                                            id="objective_remove_<?php echo $objective["objective_id"];?>"
                                                            data-id="<?php echo $objective["objective_id"];?>">
                                                </div>
                                            </li>
                                            <?php
                                        }
                                    }
                                    ?>
							    </ul>
						    </div>

                            <a name="other-objective-list"></a>
                            <h2 id="flat-toggle" title="Other <?php echo $translate->_("Objective"); ?> List" class="collapsed list-heading">Other <?php echo $translate->_("Objectives"); ?></h2>
                            <div id="other-objective-list">
                                <ul class="objective-list mapped-list" id="mapped_flat_objectives" data-importance="flat">
                                    <?php
                                    if ($flat_objectives) {
                                        foreach ($flat_objectives as $objective) {
                                            $title = ($objective["objective_code"]?$objective["objective_code"].': '.$objective["objective_name"]:$objective["objective_name"]);
                                            ?>
                                            <li class = "mapped-objective"
                                                id = "mapped_objective_<?php echo $objective["objective_id"]; ?>"
                                                data-title="<?php echo $title;?>"
                                                data-description="<?php echo $objective["objective_description"];?>">
                                                <strong><?php echo $title; ?></strong>
                                                <div class="objective-description">
                                                    <?php
                                                    $set = fetch_objective_set_for_objective_id($objective["objective_id"]);
                                                    if ($set) {
                                                        echo "From the ".$translate->_("Curriculum Tag Set").": <strong>".$set["objective_name"]."</strong><br/>";
                                                    }
                                                    ?>
                                                    <?php echo $objective["objective_description"];?>
                                                </div>
                                                <div class="objective-controls">
                                                    <img 	src="<?php echo ENTRADA_URL;?>/images/action-delete.gif"
                                                            class="objective-remove list-cancel-image"
                                                            id="objective_remove_<?php echo $objective["objective_id"];?>"
                                                            data-id="<?php echo $objective["objective_id"];?>">
                                                </div>
                                            </li>
                                            <?php
                                        }
                                    }
                                    ?>
                                </ul>
                            </div>

                            <select id="primary_objectives_select" name="primary_objectives[]" multiple="multiple" style="display:none;">
                                <?php
                                if (isset($objective_importance[1]) && $objective_importance[1]) {
                                    foreach ($objective_importance[1] as $objective) {
                                        if ($objective["importance"] == 1) {
                                            $title = ($objective["objective_code"]?$objective["objective_code"].': '.$objective["objective_name"] : $objective["objective_name"]);
                                            ?>
                                            <option value = "<?php echo $objective["objective_id"]; ?>" selected="selected"><?php echo $title; ?></option>
                                            <?php
                                        }
                                    }
                                }
                                ?>
                            </select>

                            <select id="secondary_objectives_select" name="secondary_objectives[]" multiple="multiple" style="display:none;">
                                <?php
                                if (isset($objective_importance[2]) && $objective_importance[2]) {
                                    foreach ($objective_importance[2] as $objective) {
                                        if ($objective["importance"] == 2) {
                                            $title = ($objective["objective_code"]?$objective["objective_code"].': '.$objective["objective_name"] : $objective["objective_name"]);
                                            ?>
                                            <option value = "<?php echo $objective["objective_id"]; ?>" selected="selected"><?php echo $title; ?></option>
                                            <?php
                                        }
                                    }
                                }
                                ?>
                            </select>

                            <select id="tertiary_objectives_select" name="tertiary_objectives[]" multiple="multiple" style="display:none;">
                                <?php
                                if (isset($objective_importance[3]) && $objective_importance[3]) {
                                    foreach ($objective_importance[3] as $objective) {
                                        if ($objective["importance"] == 3) {
                                            $title = ($objective["objective_code"]?$objective["objective_code"].': '.$objective["objective_name"]:$objective["objective_name"]);
                                            ?>
                                            <option value = "<?php echo $objective["objective_id"]; ?>" selected="selected"><?php echo $title; ?></option>
                                            <?php
                                        }
                                    }
                                }
                                ?>
                            </select>

                            <select id="clinical_objectives_select" name="clinical_presentations[]" multiple="multiple" style="display:none;">
                                <?php
                                if ($flat_objectives) {
                                    foreach ($flat_objectives as $objective) {
                                        $title = ($objective["objective_code"]?$objective["objective_code"].': '.$objective["objective_name"] : $objective["objective_name"]);
                                        ?>
                                        <option value = "<?php echo $objective["objective_id"]; ?>" selected="selected"><?php echo $title; ?></option>
                                        <?php
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <div style="clear:both;"></div>
                    </div>
                    <?php
                }
                ?>

				<h2 title="Course Reports Section"><?php echo $translate->_("course"); ?> Reports</h2>
				<div id="course-reports-section">
					<div class="control-group">
						<label for="course_report_ids" class="control-label form-nrequired">Report Types:</label>
						<div class="controls">
							<?php
							$course_reports_object = new Models_Course_Report();
							$results = $course_reports_object->getAllReportsByOrganisation($ENTRADA_USER->getActiveOrganisation());

							if ($results) {
								?>
								<select id="course_report_ids" name="course_report_ids[]" multiple data-placeholder="Choose reports..." class="chosen-select">
									<?php
									foreach ($results as $result) {
										$selected = false;
										if (isset($PROCESSED["course_report_ids"]) && $PROCESSED["course_report_ids"] && in_array($result["course_report_id"], $PROCESSED["course_report_ids"])) {
											$selected = true;
										}
										echo build_option($result["course_report_id"], $result["course_report_title"], $selected);
									}
								?>
								</select>
								<?php
							} 

							if (isset($PROCESSED["course_reports"]) && is_array($PROCESSED["course_reports"])) {
								foreach ($PROCESSED["course_reports"] as $course_report) {
									echo "<li id=\"type_".$course_report[0]."\" class=\"\">" . $course_report[2] . "<a href=\"#\" onclick=\"$(this).up().remove(); return false;\" class=\"remove\"><img src=\"".ENTRADA_URL."/images/action-delete.gif\"></a></li>";
								}
							}
							?>                               
						</div>
					</div>
				</div>

                <!-- Course Enrolment-->
                <h2 title="Course Enrolment Section"><?php echo $translate->_("course"); ?> Enrolment</h2>
				<div id="course-enrolment-section">
                    <div class="control-group">
                        <label for="period" class="control-label form-nrequired">Enrolment Periods</label>
                        <div class="controls">
                            <div id="curriculum_type_periods">
                                <?php
                                if (isset($PROCESSED["curriculum_type_id"]) && $PROCESSED["curriculum_type_id"]) {
									$curriculum_period_object = new Models_Curriculum_Period();
                                    if ($periods = $curriculum_period_object->getAllByFinishDateCurriculumType($PROCESSED["curriculum_type_id"], time())) {
                                        ?>
                                        <select name="curriculum_period" id="period_select">
                                            <option value="0" selected="selected">-- Select a Period --</option>
                                            <?php
                                            foreach ($periods as $period) {
                                                echo "<option value=\"".$period["cperiod_id"]."\" ".((array_key_exists($period["cperiod_id"], $PROCESSED["periods"]))?" disabled=\"disabled\"":"").">". (($period["curriculum_period_title"]) ? $period["curriculum_period_title"] . " - " : "") .date("F jS, Y" ,$period["start_date"])." to ".date("F jS, Y" ,$period["finish_date"])."</option>";
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
                                <h2>Active Periods</h2>
                                <?php
                                foreach ($PROCESSED["periods"] as $key => $period) {
                                    $period_data = Models_Curriculum_Period::fetchRowByID($key)
                                    ?>
                                    <div class="period_item" id="period_item_<?php echo $key;?>" style="margin-top:20px;">
                                        <div class="clearfix clear_both">
                                            <i class="icon-minus-sign remove_period" id="remove_period_<?php echo $key;?>"></i>&nbsp;<strong><?php echo (($period_data->getCurriculumPeriodTitle()) ? $period_data->getCurriculumPeriodTitle() . " - " : ""); ?></strong><span class="content-small"><?php echo date("F jS, Y",$period_data->getStartDate())." to ".date("F jS, Y",$period_data->getFinishDate()); ?></span><a href="javascript:void(0)" class="enrollment-toggle strong-green pull-right" id="add_audience_<?php echo $key;?>">Add Audience</a>
                                        </div>
                                        <div class="audience_selector span12 pull-left" id="audience_type_select_<?php echo $key;?>" style="display: none; margin-top: 20px;">
                                            <select class="audience_type_select" id="audience_type_select_<?php echo $key;?>" onchange="showSelect(<?php echo $key;?>,this.options[this.selectedIndex].value)">
                                                <option value="0">-- Select Audience Type --</option>
												<option value="cohort">Cohort</option>
												<option value="course_list">Course List</option>
                                                <option value="individual">Individual</option>
                                            </select>
                                            <select style="display:none;" class="type_select" id="cohort_select_<?php echo $key;?>" onchange="addAudience(<?php echo $key;?>,this.options[this.selectedIndex].text,'cohort',this.options[this.selectedIndex].value)"><option value="0">-- Add Cohort --</option>
                                                <?php
                                                foreach ($period as $audience) {
                                                    switch ($audience["audience_type"]) {
                                                        case "group_id":
                                                            $group_ids[$key][] = $audience["audience_value"];
                                                            break;

                                                        case "proxy_id":
                                                            $proxy_ids[$key][] = $audience["audience_value"];
                                                            break;
                                                    }
                                                }
                                                foreach ($groups as $group) {
                                                    echo "<option value=\"" . $group["group_id"] . "\"" . ((in_array($group["group_id"],$group_ids[$key])) ? " disabled=\"disabled\"" : "") . ">" . $group["group_name"] . "</option>";
                                                }
                                                ?>
                                            </select>
                                            <input style="display:none;width:203px;vertical-align: middle;margin-left:10px;margin-right:10px;" type="text" name="fullname" class="type_select" id="student_<?php echo $key;?>_name" autocomplete="off"/><input style="display:none;" type="button" class="btn type_select individual_add_btn" id="add_associated_student_<?php echo $key;?>" value="Add" style="vertical-align: middle" />
                                            <div class="autocomplete" id="student_<?php echo $key;?>_name_auto_complete"></div>
                                            <div style="display:none; margin-left: 240px;" id="student_example_<?php echo $key;?>">(Example: <?php echo $ENTRADA_USER->getFullname(true); ?>)</div>
                                            <input type="hidden" name="group_audience_members[]" id="group_audience_members_<?php echo $key;?>" value="<?php echo ($group_ids[$key] ? implode(',',$group_ids[$key]) : ""); ?>"/>
                                            <input type="hidden" name="individual_audience_members[]" id="associated_student_<?php echo $key;?>"/>
                                            <input type="hidden" name="student_id[]" id="student_<?php echo $key;?>_id"/>
                                            <input type="hidden" name="student_ref[]" id="student_<?php echo $key;?>_ref"/>
                                            <input type="hidden" name="periods[]" value="<?php echo $key;?>"/>
                                            <?php
                                            $ONLOAD[] = "jQuery('#student_".$key."_name').autocompletelist({ type: 'student_".$key."', url: '".ENTRADA_RELATIVE."/api/personnel.api.php?type=student', remove_image: '".ENTRADA_RELATIVE."/images/minus-sign.png'})";
                                            ?>
                                        </div>

                                        <div class="audience_section span11 pull-left" id="audience_section_<?php echo $key;?>" style="display:block; margin-top: 20px; margin-bottom: 20px; border-bottom:1px solid #D3D3D3;">
                                            <div class="audience_list" id="audience_list_<?php echo $key;?>" style="margin-bottom:10px;">
                                                <ul id="audience_list_container_<?php echo $key;?>" class="listContainer" style="display: none;">
                                                    <li><strong>Cohorts</strong>
                                                        <ol id="audience_container_<?php echo $key;?>" class="sortableList">
                                                            <?php
                                                            foreach ($period as $audience) {
                                                                switch ($audience["audience_type"]) {
                                                                    case "group_id" :
                                                                        $group = Models_Group::fetchRowByID($audience["audience_value"]);
                                                                        if ($group && is_object($group)) {
                                                                            $group_ids[$key][] = $audience["audience_value"];
                                                                            $audience["type"] = 'cohort';
                                                                            $audience["title"] = $group->getGroupName();
                                                                            ?>
                                                                                    <li id="audience_<?php echo $audience["type"]."_".$audience["audience_value"];?>" class="audience_cohort"><?php echo $audience["title"];?><i class="icon-minus-sign remove_audience pull-right"></i></li>
                                                                            <?php
                                                                        }

                                                                    break;
                                                                }
                                                            }
                                                            ?>
                                                        </ol>
                                                    </li>
                                                </ul>
                                                <ul id="student_<?php echo $key;?>_list_container" class="listContainer" style="display: none;">
                                                    <li><strong>Students</strong>
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
                                                                        <li id="student_<?php echo $key . "_" . $audience["audience_value"]; ?>" style="cursor: move; position: relative;" class="user"><?php echo $audience["title"]; ?><img src="<?php echo ENTRADA_URL; ?>/images/minus-sign.png" class="list-cancel-image remove_student" /></li>
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
                <h2 title="Course Syllabus Section">Course Syllabus</h2>
                <div id="course-syllabus-section">
                    <?php
                        $course_syllabus = Models_Syllabus::fetchRowByCourseID($COURSE_ID);
                        $syllabi = glob($ENTRADA_TEMPLATE->absolute() . "/syllabus/*.php");
                    ?>
                    <?php if (!isset($PROCESSED["periods"])) { ?>
                    <div class="display-notice" id="enrollment-required">
                        <ul>
                            <li>An enrollment period must be selected before syllabus generation can be enabled.</li>
                        </ul>
                    </div>
                    <?php } ?>
                    <input type="hidden" name="syllabus_id" value="<?php echo $course_syllabus->getID(); ?>" />
                    <div class="control-group">
                        <label class="control-label">Automatic Generation</label>
                        <div class="controls">
                            <label class="radio"><input type="radio" name="syllabus_enabled" <?php echo !isset($PROCESSED["periods"]) ? "disabled=\"disabled\"" : ""; ?> value="enabled" <?php echo $course_syllabus->getActive() ? "checked=\"checked\"" : ""; ?> /> Enabled</label>
                            <label class="radio"><input type="radio" name="syllabus_enabled" <?php echo !isset($PROCESSED["periods"]) ? "disabled=\"disabled\"" : ""; ?> <?php echo !$course_syllabus->getActive() ? "checked=\"checked\"" : ""; ?> /> Disabled</label>
                        </div>
                    </div>
                    <div id="syllabus-settings" style="<?php echo !$course_syllabus->getActive() ? "display:none;" : ""; ?>">
                        <div class="control-group" id="syllabus-template">
                            <label class="control-label">Template</label>
                            <div class="controls">
                                <select name="syllabus_template">
                                    <?php
                                    foreach ($syllabi as $syllabus) {
                                        $syllabus_template = trim(substr($syllabus, strrpos($syllabus, "/") + 1));
                                        $syllabus_template = substr($syllabus_template, 0, strlen($syllabus_template) - 4);
                                        if ($syllabus_template != "page-whitelist.inc") {
                                            ?>
                                            <option value="<?php echo $syllabus_template; ?>" <?php echo $course_syllabus->getTemplate() == $syllabus_template ? "selected=\"selected\"" : ""; ?> ><?php echo $syllabus_template; ?></option>
                                            <?php
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row-fluid span12">
                    <div class="pull-left">
                        <input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/admin/courses'" />
                    </div>
                    <div class="pull-right">                        
                        <span class="content-small">After saving:</span>
                        <select id="post_action" name="post_action">
                            <option value="content"<?php echo (((!isset($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"])) || ($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "content")) ? " selected=\"selected\"" : ""); ?>>Add content to course</option>
                            <option value="new"<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "new") ? " selected=\"selected\"" : ""); ?>>Add another course</option>
                            <option value="index"<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "index") ? " selected=\"selected\"" : ""); ?>>Return to course list</option>
                        </select>
                        <input type="submit" class="btn btn-primary" value="Save" />
                    </div>
                </div>
			</form>
			<?php
		break;
	}
}
