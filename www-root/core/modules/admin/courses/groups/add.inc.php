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
 * This file is used to add groups.
 *
 * @author Organisation: University of Calgary
 * @author Unit: School of Medicine
 * @author Developer: Doug Hall <hall@ucalgary.ca>
 * @copyright Copyright 2011 University of Calgary. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_COURSE_GROUPS"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed(new CourseResource($COURSE_ID, $ENTRADA_USER->getActiveOrganisation()), 'update', false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	ini_set('auto_detect_line_endings',true);

//	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/eventtypes_list.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/AutoCompleteList.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
	$HEAD[] = "<link rel=\"stylesheet\" href=\"".  ENTRADA_URL ."/css/". $MODULE ."/". $MODULE .".css\" />";

	echo "<script language=\"text/javascript\">var DELETE_IMAGE_URL = '".ENTRADA_URL."/images/action-delete.gif';</script>";
	
	    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/course/groups?".replace_query(array("section" => "add")), "title" => "Add Course Group");

	$group_type = "individual";
	$group_populate = "group_number";
	$number_of_groups = "";
	$populate = 0;
	$GROUP_IDS = array();



	$course = Models_Course::get($COURSE_ID);

	if ($course) {
		$course_details = $course->toArray();
		courses_subnavigation($course_details,"groups");
		$curriculum_periods = Models_Curriculum_Period::fetchRowByCurriculumTypeIDCourseID($course->getCurriculumTypeID(), $course->getID());
	}

	// Error Checking
	switch($STEP) {
		case 2 :
			/*
			 *  CSV file format "group_name, first_name, last_name, status, entrada_id"
			 */
			$PROCESSED["updated_date"]	= time();
			$PROCESSED["updated_by"]	= $ENTRADA_USER->getID();
			
			/**
			 * Required field "prefix" / Group Name.
			 */
			if ((isset($_POST["prefix"])) && ($group_prefix = clean_input($_POST["prefix"], array("notags", "trim")))) {
				$PROCESSED["group_name"] = $group_prefix;
			} else {
				add_error("The <strong>Group Prefix</strong> field is required.");
			}

			if ((isset($_POST["cperiod_select"])) && ($cperiod_id = clean_input($_POST["cperiod_select"], array("int", "trim")))) {
				$PROCESSED["cperiod_id"] = $cperiod_id;
			} else {
				add_error("The <strong>Enrolment Period</strong> field is required.");
			}

			/**
			 * Non-required field "associated_faculty" / Associated Faculty (array of proxy ids).
			 * This is actually accomplished after the event is inserted below.
			 */
			if (isset($_POST["group_type"])) {
				$group_type = clean_input($_POST["group_type"], array("page_url"));

				switch($group_type) {
					case "individual" :
						if (!((isset($_POST["empty_group_number"])) && ($number_of_groups = clean_input($_POST["empty_group_number"], array("trim", "int"))))) {
							add_error("A <strong>Number of Groups</strong> value is required.");
						}
					break;
					case "populated" :
						/**
						 * Audience type is a required field. There shouldn't be anyway to not have it, but it checks to be sure.
						 */
						if (!(isset($_POST["enrolment"]) && $audience_type = clean_input($_POST["enrolment"],array("trim")))) {
							add_error("You have requested the groups be preopulated but have not specified where they should be populated from.");
							$audience_type = false;
						} else {
							$PROCESSED["enrolment"] = $audience_type;
						}

						if ($audience_type == "part_enrolment") {
							if (!isset($_POST["part_enrolment_val"]) || !count($_POST["part_enrolment_val"])) {
								add_error("You selected a custom audience to prepopulate the groups from but did not select any groups.");
							} else {
								$groups = array();
								$individuals = array();
								$i=0;
								foreach ($_POST["part_enrolment_val"] as $cohort) {
									$cohort = clean_input($cohort,array("trim", "int"));
									if ($_POST['enrolment_type_'.$cohort] == "group_id") {
										$groups[] = $cohort;
										$PROCESSED["associated_cohort_ids"][] = $cohort;
									} else {
										$individuals[$i]["id"] = $cohort;
										$PROCESSED["associated_proxy_ids"][] = $cohort;
										$i++;
									}

								}

							}
						} else {
							$course_audience_object = new Models_Course_Audience();
							$audience_result = $course_audience_object->fetchAllByCourseIDCperiodID($COURSE_ID, $PROCESSED["cperiod_id"]);

							if ($audience_result) {
								$i=0;
								foreach ($audience_result as $audience_object) {
									$audience = $audience_object->toArray();

									switch ($audience["audience_type"]) {
										case 'group_id':
											$groups[] = $audience["audience_value"];
											$PROCESSED["associated_cohort_ids"][] = $audience["audience_value"];
											break;
										case 'proxy_id':
											$individuals[$i]["id"] = $audience["audience_value"];
											$PROCESSED["associated_proxy_ids"][] = $audience["audience_value"];
											$i++;
											break;
									}
								}
							}
						}

						if (!((isset($_POST["number"])) && ($number_of_groups = clean_input($_POST["number"], array("trim", "int"))))) {
							$number_of_groups = 0;
						}
						if (!((isset($_POST["size"])) && ($size_of_groups = clean_input($_POST["size"], array("trim", "int"))))) {
							$size_of_groups = 0;
						}

						if ((isset($_POST["gender"]) && $gender = clean_input($_POST["gender"],array("trim")))) {
							if (isset($_POST["gender_choice"]) && $gender_choice = clean_input($_POST["gender_choice"],array("trim"))) {
								$PROCESSED["gender_choice"] = $gender_choice;
							} else {
								add_error("You have requested the groups be prepopulated by gender but have not specified method");
							}
						}



						if (isset($_POST["group_populate"])) {
							$group_populate = clean_input($_POST["group_populate"], array("page_url"));
							switch($group_populate) {
								case "group_number" :
									if (!$number_of_groups) {
										add_error("A value for <strong>Number of Groups</strong> is required.");
									} elseif ($number_of_groups <= 0) {
										add_error("Invalid value for <strong>Number of Groups</strong>.");
									}
								break;
								case "group_size" :
									if (!$size_of_groups) {
										add_error("A value for <strong>Group size</strong> is required.");
									} elseif ($size_of_groups <= 0) {
										add_error("Invalid value for <strong>Group size</strong>.");
									}
								break;
								default:
									add_error("Unable to proceed because the <strong>Groups</strong> style is unrecognized.");
								break;
							}
						} else {
							add_error("Unable to proceed because the <strong>Groups</strong> style is unrecognized.");
						}
						$populate = 1;
					break;
					default :
						add_error("Unable to proceed because the <strong>Grouping</strong> type is unrecognized.");

						application_log("error", "Unrecognized group_type [".$_POST["group_type"]."] encountered.");
					break;
				}
			} else {
				add_error("Unable to proceed because the <strong>Grouping</strong> type is unrecognized.");

				application_log("error", "The group_type field has not been set.");
			}

			$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = "index";

			if (!$ERROR) {
				$course_object = Models_Course::fetchRowByID($COURSE_ID);
				if ($course_object) {
					$course = $course_object->toArray();
					if ($course["permission"] == "closed") {
						$course_audience = true;
					} else {
						$course_audience = false;
					}
				}
				
				if (!$course_audience) {
					$course_audience_object = new Models_Course_Audience();
					$course_audience_record = $course_audience_object->getOneByCourseIDCurriculumDate($COURSE_ID, time());
					if ($course_audience_record) {
						$course_audience = true;
					}
					
				}
				
				$PROCESSED["course_id"] = $COURSE_ID;
				$PROCESSED["active"] = 1;

				if ($number_of_groups == 1) {

					$result = Models_Course_Group::fetchRowByGroupNameCourseIDCperiodID($PROCESSED["group_name"], $COURSE_ID, $PROCESSED["cperiod_id"]);
					if ($result) {
						add_error("The <strong>Group name</strong> already exists. The group was not created");
					} else {
						$group_object = new Models_Course_Group();
						if (!$insert = $group_object->fromArray($PROCESSED)->insert()) {
							add_error("There was an error while trying to add the <strong>Group</strong> ".$PROCESSED["group_name"].".<br /><br />The system administrator was informed of this error; please try again later.");
							application_log("error", "Unable to insert a new group ".$PROCESSED["group_name"]);
						}
						if ($insert) {
							$GROUP_IDS[] = $insert->getID();
						}
					}

				} else {
					$prefix = $PROCESSED["group_name"].' ';

					if ($group_populate == "group_size") {
						if ($groups) {
							$audience_members_object = new Models_Course_Group_Audience();
							$ordered = true;
							if (!isset($PROCESSED["gender_choice"])) {
								$ordered = false;
							}
							$cohort_members = $audience_members_object->getAllAudienceMembers(implode(",",$groups), $ordered);
						}

						if ($groups && $individuals) {
							$audience_members = array_merge($cohort_members, $individuals);
						} elseif(empty($individuals)) {
							$audience_members = $cohort_members;
						} else {
							$audience_members = $individuals;
						}

						$students = count($audience_members);
						$number_of_groups = ceil($students / $size_of_groups) ;
					}
					$dfmt = "%0".strlen((string) $number_of_groups)."d";

					$result = false;
					for ($i = 1; $i <= $number_of_groups && !$result; $i++) {
						$result = Models_Course_Group::fetchRowByGroupNameCourseIDCperiodID($PROCESSED["group_name"], $COURSE_ID, $PROCESSED["cperiod_id"])?true:$result;
					}
					if ($result) {
						add_error("A <strong>Group name</strong> already exists. The groups were not created");
					} else {
						for ($i = 1; $i <= $number_of_groups; $i++) {
							$PROCESSED["group_name"] = $prefix.sprintf($dfmt,$i);
							$PROCESSED["active"] = 1;
							$group_object = new Models_Course_Group();
							if (!$insert = $group_object->fromArray($PROCESSED)->insert()) {
								add_error("There was an error while trying to add the <strong>Group</strong> ".$PROCESSED["group_name"].".<br /><br />The system administrator was informed of this error; please try again later.");
								application_log("error", "Unable to insert a new group ".$PROCESSED["group_name"].".");
								break;
							}
							if ($insert) {
								$GROUP_IDS[] = $insert->getID();
							}
						}
					}
				}	
				if ($populate) {
					unset($PROCESSED["group_name"]);
					$PROCESSED["active"] = 1;
					if ((!isset($audience_members) || !$audience_members) && $groups) {
						/**
						 * if somehow the audience wasn't populated above, populate it here.
						 */

						if ($groups) {
							$audience_members_object = new Models_Course_Group_Audience();
							$ordered = true;
							if (!isset($PROCESSED["gender_choice"])) {
								$ordered = false;
							}
							$cohort_members = $audience_members_object->getAllAudienceMembers(implode(",",$groups), $ordered);
						}

						if ($groups && $individuals) {
							$audience_members = array_merge($cohort_members, $individuals);
						} elseif(empty($individuals)) {
							$audience_members = $cohort_members;
						} else {
							$audience_members = $individuals;
						}
					}

					$i = 0;
					$member_count = 0;

					if ($audience_members) {

						$gender_split = false;
						if (isset($PROCESSED["gender_choice"]) && $PROCESSED["gender_choice"] == "split") {
							$students = count($audience_members);
							$size_of_groups = ceil($students / $number_of_groups);
							$gender_split = true;
						}

						foreach ($audience_members as $result) {

							$insert = array();
							$insert["proxy_id"] =  $result["id"];
							$insert["updated_date"]	= time();
							$insert["updated_by"]	= $ENTRADA_USER->getID();
							$insert["cgroup_id"] =  $GROUP_IDS[$i];
							$insert["active"] =  1;
							$group_audience_object = new Models_Course_Group_Audience();

							if (!$group_audience_object->fromArray($insert)->insert()) {
								add_error("There was an error while trying to add an audience member to the database.<br /><br />The system administrator was informed of this error; please try again later.");
								application_log("error", "Unable to insert a new group member ".$PROCESSED["proxy_id"].".");
								break;
							}

							if ($gender_split) {
								$member_count++;
							}

							if ($member_count==$size_of_groups && $gender_split) {
								$member_count = 0;
								$i++;
							}

							if (!$gender_split) {
								$i++;
							}

							if ($i==$number_of_groups && !$gender_split) {
								$i = 0;
							}
						}
					}
				}
				if (!$ERROR) {
					switch($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"]) {
						case "content" :
							$url	= ENTRADA_URL."/admin/courses/groups?section=edit&id=".$COURSE_ID."&ids=".implode(",", $GROUP_IDS);
							$msg	= "You will now be redirected to the event content page; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
						break;
						case "new" :
							$url	= ENTRADA_URL."/admin/courses/groups?section=add&id=".$COURSE_ID;
							$msg	= "You will now be redirected to add more group(s); this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
						break;
						case "index" :
						default :
							$url	= ENTRADA_URL."/admin/courses/groups?id=".$COURSE_ID;
							$msg	= "You will now be redirected to the group index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
						break;
					}
	
					add_success("You have successfully added <strong>".$number_of_groups." course groups</strong> to the system.<br /><br />".$msg);
					$ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";
	
					application_log("success", "New course groups added for course [".$COURSE_ID."] added to the system.");
				}
			}

			if ($ERROR) {
				$STEP = 1;
			}
		break;
		case 1 :
		default :
			continue;
		break;
	}

	// Display Content
	switch($STEP) {
		case 2 :
			display_status_messages();
		break;
		case 1 :
		default :
			$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/elementresizer.js\"></script>\n";
			$HEAD[] = "<script src=\"".  ENTRADA_URL ."/javascript/". $MODULE ."/". $SUBMODULE ."/". $SUBMODULE ."_add.js\"></script>";
			$ONLOAD[] = "selectgroupOption('".$group_type."',0)";

			if ($ERROR) {
				echo display_error();
			}

			if (!isset($PROCESSED["periods"])) {
				$course_audience_object = new Models_Course_Audience();
				$audience_result = $course_audience_object->fetchAllByCourseID($COURSE_ID);

				$PROCESSED["periods"] = array();
				if ($audience_result) {
					foreach ($audience_result as $audience) {
						$member = $audience->toArray();
						$PROCESSED["periods"][$member["cperiod_id"]][]=$member;
					}
				}
			}

			?>
		<form id="frmSubmit" class="form-horizontal" action="<?php echo ENTRADA_URL; ?>/admin/courses/groups?<?php echo replace_query(array("step" => 2)); ?>" method="post" id="addGroupForm">

			<div class="row-fluid">
				<div class="span12">
					<div class="span5">
						<h1><?php echo $translate->_("Add Group"); ?></h1>
					</div>
					<div class="span7 no-printing">
						<?php
						if ($curriculum_periods) { ?>
							<div class="pull-right form-horizontal no-printing" style="margin-bottom:0; margin-top:18px">
								<div class="control-group">
									<label for="cperiod_select" class="control-label muted group-index-label">Period:</label>
									<div class="controls group-index-select">
										<select style="width:100%" id="cperiod_select" name="cperiod_select">
											<?php
											foreach ($curriculum_periods as $period) { ?>
												<option value="<?php echo html_encode($period->getID());?>" <?php echo (isset($PREFERENCES["selected_curriculum_period"]) && $PREFERENCES["selected_curriculum_period"] == $period->getID() ? "selected=\"selected\"" : ""); ?>>
													<?php echo (($period->getCurriculumPeriodTitle()) ? html_encode($period->getCurriculumPeriodTitle()) . " - " : "") . date("F jS, Y", html_encode($period->getStartDate()))." to ".date("F jS, Y", html_encode($period->getFinishDate())); ?>
												</option>
												<?php
											}
											?>
										</select>
										<input type="hidden" id="course_id" name="course_id" value="<?php echo $COURSE_ID; ?>">
									</div>
								</div>
							</div>
							<?php
						}
						?>
					</div>
				</div>
			</div>
				<h3><?php echo $translate->_("Group Details"); ?></h3>

                <div class="control-group">
                    <label class="control-label form-required" for="prefix"><?php echo $translate->_("Group Name Prefix"); ?></label>

                    <div class="controls">
                        <input type="text" id="prefix" name="prefix" class="span8" value="<?php echo (isset($PROCESSED["group_name"]) && $PROCESSED["group_name"] ? html_encode($PROCESSED["group_name"]) : ""); ?>" maxlength="255"/>
                        <div class="content-small space-above">The group prefix will be used to automatically create the sequential group names. For example, a group prefix of "Small Group" would result in group names of "Small Group 1", "Small Group 2", "Small Group 3", etc.</div>
                    </div>
                </div>

                <label class="control-label form-required"><?php echo $translate->_("Group Type"); ?></label>

                <div class="control-group">
                    <div class="controls">
                        <label class="radio" for="group_type_individual">
							<span class="radio-group-title">
								<input type="radio" name="group_type" id="group_type_individual" value="individual" checked="checked" />
								<?php echo $translate->_("Create"); ?>
								<input type="text" id="empty_group_number" name="empty_group_number" class="span1" value="<?php echo html_encode($number_of_groups); ?>" maxlength="3" />
								<?php echo $translate->_("empty groups"); ?>
							</span>

                        </label>
                        <label class="radio" for="group_type_populated">
							<span class="radio-group-title">
								<input type="radio" name="group_type" id="group_type_populated" value="populated" <?php echo (($group_type == "populated") ? " checked=\"checked\"" : ""); ?>/>
								<?php echo $translate->_("Automatically populate groups"); ?>
							</span>
						</label>
						<div class="hide v-divider">
							<div id="prepopulated_section">
								<label class="content-subheading"><?php echo $translate->_("Learners");?> </label>
								<label class="radio"  id="all_enrolment_label">
									<input type="radio" name="enrolment" class="enrolment" value="all_enrolment" checked="checked" />
									<span>All <span id="total_students"></span> learners enroled in <?php echo $course_details['course_name']?> from <span id="enrolment_name"></span></span>
								</label>
								<label class="radio"  id="part_enrolment_label">
									<input type="radio" name="enrolment" class="enrolment" value="part_enrolment"/>
									<span><?php echo $translate->_("Selected learners within");?> <?php echo $course_details['course_name']?></span>
								</label>
								<div class="v-divider">
									<div class="enrolment_groups hide"></div>
								</div>
							</div>
							<div class="group_members populated_members">
								<label class="content-subheading"><?php echo $translate->_("Groups");?></label><br>
								<label class="muted form-required"><?php echo $translate->_("Populate based on");?></label>
								<div class="control-group">
									<div class="controls">
										<label class="radio pull-left space-right span3" for="group_populate_group_number">
											<input type="radio" name="group_populate" id="group_populate_group_number" value="group_number" onclick="toggleGroupTextbox()" <?php echo (!isset($group_populate) || ($group_populate == "group_number") ? " checked=\"checked\"" : ""); ?> />
											<?php echo $translate->_("Number of Groups");?>
										</label>
										<input type="text" id="group_number" class="pull-left span2" name="number" value="<?php echo html_encode($number_of_groups); ?>"  style="<?php echo (!isset($group_populate) || ($group_populate == "group_number") ? "" : "display: none;"); ?>" />
									</div>
								</div>

								<div class="control-group">
									<div class="controls">
										<label class="radio pull-left space-right span3" for="group_populate_group_size">
											<input type="radio" name="group_populate" id="group_populate_group_size" value="group_size" onclick="toggleGroupTextbox()" <?php echo (($group_populate == "group_size") ? " checked=\"checked\"" : ""); ?> />
											<?php echo $translate->_("Group Size");?>
										</label>

										<input type="text" id="group_size" class="pull-left span2" name="size" value="<?php echo html_encode($size_of_groups); ?>"  style="<?php echo (($group_populate == "group_size") ? "" : "display: none;"); ?>" />
									</div>
								</div>
							</div> </br>
							<div id="gender_section">
								<label class="content-subheading"><?php echo $translate->_("Populate groups");?></label><br>
								<label class="checkbox" for="gender">
									<input type="checkbox" name="gender" id ="gender" value="gender" />
									<?php echo $translate->_("Based on gender"); ?>
								</label>
								<div class="v-divider">
									<div class="gender-radio hide">
										<label class="radio"  id="gender_equal">
											<input type="radio" name="gender_choice" value="equal" checked="checked" />
											<span><?php echo $translate->_("Equally populate groups with males, females, and not-specified.");?></span>
										</label>
										<label class="radio"  id="gender_split">
											<input type="radio" name="gender_choice" value="split"/>
											<span><?php echo $translate->_("Group the genders together into groups.");?></span>
										</label>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
                <div class="row-fluid">
                    <div class="pull-left">
                        <input type="button" class="btn" value="<?php echo $translate->_("Cancel");?>" onclick="window.location='<?php echo ENTRADA_URL; ?>/admin/courses/groups?id=<?php echo $COURSE_ID; ?>'" />
                    </div>

                    <div class="pull-right">
                        <input type="submit" class="btn btn-primary" value="<?php echo $translate->_("Add");?>" />
                    </div>
                </div>
			</form>
			<br /><br />
			<?php
		break;
	}
}
?>
