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
 * @author Organisation: University of Calgary
 * @author Unit: Faculty of Medicine
 * @author Developer: Doug Hall<hall@ucalgary.ca>
 * @copyright Copyright 2011 University of Calgary. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_COURSE_GROUPS"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed(new CourseResource($COURSE_ID, $ENTRADA_USER->getActiveOrganisation()), 'update',false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE.(isset($SUBMODULE) && $SUBMODULE ? "/".$SUBMODULE : "")."?id=".$COURSE_ID."\\'', 15000)";

	add_error("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	// ERROR CHECKING
	$course = Models_Course::get($COURSE_ID);

	if ($course) {
		$course_details = $course->toArray();
	}

	switch ($STEP) {
		case "2" :
			if(isset($_POST["add_group_id"]) && $tmp = clean_input($_POST["add_group_id"], "int")) {
				$PROCESSED["cgroup_id"] = $tmp;
			} else {
				header("Location: ".ENTRADA_URL."/admin/".$MODULE.(isset($SUBMODULE) && $SUBMODULE ? "/".$SUBMODULE : "")."?id=".$COURSE_ID);
			}

            $proxy_ids = array();

            if (isset($_POST["student"]) && is_array($_POST["student"])) {
            	foreach ($_POST["student"] as $proxy_id) {
            		if ($tmp_input = clean_input($proxy_id, array("trim", "int"))) {
						$proxy_ids[] = $tmp_input;
					}
				}
            }

			$PROCESSED["updated_date"] = time();
			$PROCESSED["updated_by"] = $ENTRADA_USER->getID();

			$count = $added = 0;
			foreach($proxy_ids as $proxy_id) {
				if(($proxy_id = (int) trim($proxy_id))) {
					$count++;
					$group_audiences = Models_Course_Group_Audience::fetchAllByCGroupIDProxyID($PROCESSED["cgroup_id"], $proxy_id);
                    if ($group_audiences) {
                        foreach ($group_audiences as $group_audience) {
                            // if the user was previously in the audience, just re-enable them
                            if (!($group_audience->getActive())) {
                                if (!$group_audience->setActive(1)->update()) {
                                    add_error("Failed to update this member into the group. Please contact a system administrator if this problem persists.");
                                    application_log("error", "Error while updating a Course Group Audience member into database. Database server said: " . $db->ErrorMsg());
                                }
                            }
                        }
                    } else {
                        // add new user to the course group audience
                        $PROCESSED["proxy_id"]	= $proxy_id;
                        $PROCESSED["active"] = 1;
                        $added++;
                        $audience_object = new Models_Course_Group_Audience();
                        if (!$audience_object->fromArray($PROCESSED)->insert()) {
                            add_error("Failed to insert this member into the group. Please contact a system administrator if this problem persists.");
                            application_log("error", "Error while inserting member into database. Database server said: ".$db->ErrorMsg());
                        }
                    }
                }
            }
			if ((isset($_POST["associated_facultyorstaff"])) && ($associated_tutors = explode(",", $_POST["associated_facultyorstaff"])) && (@is_array($associated_tutors)) && (@count($associated_tutors))) {
				$order = 0;
				$group_contacts_object = new Models_Course_Group_Contact();
				if ($group_contacts_object->deleteByGroupID($PROCESSED["cgroup_id"])) {
					foreach ($associated_tutors as $proxy_id) {
						if ($proxy_id = clean_input($proxy_id, array("trim", "int"))) {
							$group_contacts_object = new Models_Course_Group_Contact();
							$PROCESSED["proxy_id"]	= $proxy_id;
                            $PROCESSED["contact_order"] = $order;
							if (!$result = $group_contacts_object->fromArray($PROCESSED)->insert()) {
								add_error("There was an error when trying to insert a &quot;" . $translate->_("group") . " Tutor&quot; into the system. The system administrator was informed of this error; please try again later.");

								application_log("error", "Unable to insert a new group_contact to the database when updating an event.");
							} else {
								$order++;
							}
						}
					}
				}
			}

			if ((isset($_POST["cperiod_id"])) && ($cperiod_id = clean_input($_POST["cperiod_id"], array("int", "trim")))) {
				$PROCESSED["cperiod_id"] = $cperiod_id;
			} else {
				add_error("The <strong>Enrolment Period</strong> field is required.");
			}

			if ((isset($_POST["group_name"])) && ($group_name = clean_input($_POST["group_name"], array("notags", "trim")))) {
				$PROCESSED["group_name"] = $group_name;

				$result = Models_Course_Group::fetchRowByGroupNameCourseIDCperiodID($PROCESSED["group_name"], $COURSE_ID, $PROCESSED["cperiod_id"]);

				if ($result) {
					add_error("The <strong>Group name</strong> already exists.");
				} else {
					$course_group = Models_Course_Group::fetchRowByID($PROCESSED["cgroup_id"]);
					if (!$course_group->fromArray(array("group_name" => $PROCESSED["group_name"], "cperiod_id" => $PROCESSED["cperiod_id"]))->update()) {
						add_error($translate->_("Unable to update Group Name"));
					}
				}
			} else {
				add_error("The <strong>Group Name</strong> field is required.");
			}

			if(!$count) {
				add_error("You must select a user(s) to add to this group. Please be sure that you select at least one user to add this event to from the interface.");
			}

			$url = ENTRADA_URL."/admin/".$MODULE.(isset($SUBMODULE) && $SUBMODULE ? "/".$SUBMODULE : "")."?id=".$COURSE_ID;
			$msg = "You will now be redirected to the course index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";

			$ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";
			add_success("You have successfully edited <strong>".html_encode($PROCESSED["group_name"])."</strong> in the system.<br /><br />".$msg);

		break;
		default :
			// No error checking for step 1.
		break;
	}
	
	// PAGE DISPLAY
	switch ($STEP) {
        // step 2
        case "2" :
            echo display_success();
        break;

        // step 1
		default :
			$group_ids = array();
			if ((!isset($PROCESSED["cgroup_id"]) || !(int)$PROCESSED["cgroup_id"]) && isset($_POST["cgroup_id"]) && (int)$_POST["cgroup_id"]) {
				$PROCESSED["cgroup_id"] = (int)$_POST["cgroup_id"];
			}
			if(isset($PROCESSED["cgroup_id"]) && (int)$PROCESSED["cgroup_id"]) {
				$GROUP_ID = $PROCESSED["cgroup_id"];
			} else {
				$GROUP_ID = 0;
			}
			if (isset($_GET["ids"])) {
				$_SESSION["gids"] = array(htmlentities($_GET["ids"]));
			} elseif (isset($_POST["checked"])) {
				$_SESSION["gids"] = $_POST["checked"];
			} elseif((isset($_POST["group_id"])) && ((int) trim($_POST["group_id"]))) {
				$GROUP_ID = (int) trim($_POST["group_id"]);
			} elseif((isset($_GET["gid"])) && ((int) trim($_GET["gid"]))) {
				$GROUP_ID = (int) trim($_GET["gid"]);
			}

			if ((!isset($_SESSION["gids"]) || !is_array($_SESSION["gids"])) || (!@count($_SESSION["gids"]))) {
				header("Location: ".ENTRADA_URL."/admin/courses/groups?id=".$COURSE_ID);
				exit;
			}

			$group_ids = $_SESSION["gids"];

			$course_groups_object = new Models_Course_Group();
			$course_groups	= $course_groups_object->getAllByMultipleGroupID($group_ids);

			if (!$course_groups) {
				header("Location: ".ENTRADA_URL."/admin/".$MODULE.(isset($SUBMODULE) && $SUBMODULE ? "/".$SUBMODULE : "")."?id=".$COURSE_ID);
			}
			if (!$GROUP_ID) {
				$GROUP_ID = $course_groups[0]["cgroup_id"];
			}
			
			/**
			 * Add any existing associated reviewers from the evaluation_contacts table
			 * into the $PROCESSED["associated_reviewers"] array.
			 */
			$results = Models_Course_Group_Contact::fetchAllByCgroupID($GROUP_ID);

			if ($results) {
				foreach($results as $contact_order => $result_object) {
					$result = $result_object->toArray();
					$PROCESSED["associated_tutors"][(int) $contact_order] = $result["proxy_id"];
				}
			}
			
			/**
			* Compiles the full list of faculty members.
			*/
		    $TUTOR_LIST = array();
			$user_object = new Models_User();

		    $results = $user_object->getTutors();
		    if ($results) {
		        foreach($results as $result) {
				    $TUTOR_LIST[$result["proxy_id"]] = array('proxy_id'=>$result["proxy_id"], 'fullname'=>$result["fullname"], 'organisation_id'=>$result['organisation_id']);
			   }
		   }

			$group = Models_Course_Group::fetchRowByID($GROUP_ID);
			$group_name = $group->getGroupName();

			$ONLOAD[]	= "showgroup('".html_encode($group_name)."',".$GROUP_ID.")";

			$HEAD[] = "<script>var ENTRADA_URL = \"". ENTRADA_URL ."\";</script>";
			$HEAD[] = "<script type=\"text/javascript\">var ORGANISATION = '".$ENTRADA_USER->getActiveOrganisation()."';</script>";
            $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = '".ENTRADA_URL."';</script>";
            $HEAD[] = "<script type=\"text/javascript\">var DELETE_IMAGE_URL = '".ENTRADA_URL."/images/action-delete.gif';</script>";
			$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery.autocompletelist.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
			$HEAD[] = "<script src=\"".  ENTRADA_URL ."/javascript/". $MODULE ."/". $SUBMODULE ."/". $SUBMODULE ."_manage.js\"></script>";
            $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js\"></script>";
            $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css\" />";
			$HEAD[] = "<link rel=\"stylesheet\" href=\"".  ENTRADA_URL ."/css/". $MODULE ."/". $MODULE .".css\" />";

			$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/courses/groups?section=manage&id=".$COURSE_ID, "title" => "Manage Course Groups");

			courses_subnavigation($course_details,"groups");
			$curriculum_periods = Models_Curriculum_Period::fetchRowByCurriculumTypeIDCourseID($course_details['curriculum_type_id'], $course_details['course_id']);

            // determine the current curriculum period to use
            if ($group->getCPeriodID()) {
                $current_cperiod_id = $group->getCPeriodID();
            } elseif (isset($PREFERENCES["selected_curriculum_period"]) && (int) $PREFERENCES["selected_curriculum_period"]) {
                $current_cperiod_id = $PREFERENCES["selected_curriculum_period"];
            } elseif ($curriculum_periods) {
                $current_cperiod_id = $curriculum_periods[0]->getID();
            } else {
                $current_cperiod_id = 0;
            }

			?>
			<form class="form-horizontal" action="<?php echo ENTRADA_URL."/admin/".$MODULE.(isset($SUBMODULE) && $SUBMODULE ? "/".$SUBMODULE : "")."?".replace_query(array("section" => "manage", "type" => "add", "step" => 2)); ?>" method="post" id="groupForm">
			<div class="row-fluid">
				<div class="span12">
					<div class="span5">
						<h1>Edit Group</h1>
					</div>
					<div class="span7 no-printing">
						<?php
                        // if the group we are editing does not have a curriculum period, show the selector
						if (!$group->getCPeriodID()) {
							if ($curriculum_periods) { ?>
								<div class="control-group">
									<label for="cperiod_select"
										   class="control-label muted group-index-label">Period:</label>
									<div class="controls group-index-select">
										<select style="width:100%" id="cperiod_select" name="cperiod_id">
											<?php
											foreach ($curriculum_periods as $period) { ?>
												<option
													value="<?php echo html_encode($period->getID()); ?>" <?php echo ($current_cperiod_id == $period->getID() ? "selected=\"selected\"" : ""); ?>>
													<?php echo (($period->getCurriculumPeriodTitle()) ? html_encode($period->getCurriculumPeriodTitle()) . " - " : "") . date("F jS, Y", html_encode($period->getStartDate())) . " to " . date("F jS, Y", html_encode($period->getFinishDate())); ?>
												</option>
												<?php
											}
											?>
										</select>
									</div>
								</div>
								<?php
							}
						} else { ?>
						<div class="right">
							<input type="hidden" name="cperiod_id" value="<?php echo $group->getCPeriodID(); ?>">
							<?php
							$current_cperiod = Models_Curriculum_Period::fetchRowByID($current_cperiod_id);
							echo "Period: ".(($current_cperiod->getCurriculumPeriodTitle()) ? html_encode($current_cperiod->getCurriculumPeriodTitle()) . " - " : "") . date("F jS, Y", html_encode($current_cperiod->getStartDate())) . " to " . date("F jS, Y", html_encode($current_cperiod->getFinishDate()));
							?>
						</div>
						<?php
						}
						?>
					</div>
				</div>
			</div>
				<h2 title="Group Section">Group Details</h2>
				<div id="group-section">
					<div class="control-group">
						<label for="group_name" class="form-nrequired control-label">Group Name</label>
						<div class="controls">
							<input type="text" id="group_name" name="group_name" value="<?php echo html_encode((isset($group_name) && $group_name ? $group_name : "")); ?>"maxlength="85" class="span7" />
							<input type="hidden" name="cgroup_id" value="<?php echo $GROUP_ID ?>">
						</div>
					</div>
					<div class="control-group">
						<label for="facultyorstaff_name" class="control-label form-nrequired">Tutors</label>
						<div class="controls">
							<input id="facultyorstaff_name" type="text" class="form-control search"  name="fullname" placeholder="<?php echo $translate->_("Type to search for tutors..."); ?>">
							<div id="autocomplete">
								<div id="facultyorstaff_name_auto_complete"></div>
							</div>
							<input type="hidden" id="associated_facultyorstaff" name="associated_facultyorstaff" />
							<ul id="facultyorstaff_list" class="menu" style="margin-top: 15px; width: 350px;">
								<?php
								if (is_array($PROCESSED["associated_tutors"]) && count($PROCESSED["associated_tutors"])) {
									foreach ($PROCESSED["associated_tutors"] as $tutor) {
										if ((array_key_exists($tutor, $TUTOR_LIST)) && is_array($TUTOR_LIST[$tutor])) {
											?>
											<li class="user" id="facultyorstaff_<?php echo $TUTOR_LIST[$tutor]["proxy_id"]; ?>" data-proxy-id="<?php echo $TUTOR_LIST[$tutor]["proxy_id"]; ?>" style="cursor: move;margin-bottom:10px;"><?php echo $TUTOR_LIST[$tutor]["fullname"]; ?> <img src="<?php echo ENTRADA_URL; ?>/images/action-delete.gif" class="list-cancel-image" /></li>
											<?php
										}
									}
								}
								?>
							</ul>
							<input type="hidden" id="facultyorstaff_ref" name="facultyorstaff_ref" value="" />
							<input type="hidden" id="facultyorstaff_id" name="facultyorstaff_id" value="" />
						</div>
					</div>
				</div>
				<div id="member-items-container">
					<h2 style="margin-top: 10px"  title="Group Members Section"><?php echo $translate->_("View Members"); ?></h2>
					<div id="group-members-section">
						<input type="hidden" id="element_type" name="element_type" value="<?php echo (isset($PROCESSED["element_type"]) ? $PROCESSED["element_type"] : ""); ?>" />
						<input type="hidden" id="id" name="id" value="<?php echo (isset($PROCESSED["id"]) ? $PROCESSED["id"] : ""); ?>" />
						<input type="hidden" id="form_id" name="form_id" value="<?php echo (isset($PROCESSED["form_id"]) ? $PROCESSED["form_id"] : ""); ?>" />
						<input type="hidden" id="group-id" name="group-id" value="<?php echo (isset($GROUP_ID) ? $GROUP_ID : ""); ?>" />
						<div id="search-bar" class="search-bar">
							<div class="row-fluid space-below medium">
								<div class="pull-right">
									<a href="#delete-members-modal" data-toggle="modal" class="btn btn-danger space-right"><i class="icon-minus-sign icon-white"></i> <?php echo $translate->_("Delete Members"); ?></a>
								</div>
							</div>
							<div id="item-summary"></div>
						</div>
						<div id="search-container" class="hide space-below medium"></div>
						<div id="item-summary"></div>
						<div id="member-msgs">
							<div id="member-loading" class="enrolment-loading hide">
								<p><?php echo $translate->_("Loading members..."); ?></p>
								<img src="<?php echo ENTRADA_URL ."/images/loading.gif" ?>" />
							</div>
						</div>
						<div id="member-table-container">
							<table id="members-table" class="table table-bordered table-striped">
								<thead>
								<th width="5%"><input type="checkbox" id="checkAll" name="checkAll"></th>
								<th width="60%" class="general"><?php echo $translate->_("Name"); ?><i class="fa fa-sort member-sort" aria-hidden="true" data-name="code" data-order=""></i></th>
								<th width="35%" class="title"><?php echo $translate->_("Group & Role"); ?></th>
								</thead>
								<tbody>
								<tr id="no-items">
									<td colspan="5"><?php echo $translate->_("No members to display"); ?></td>
								</tr>
								</tbody>
							</table>
						</div>
						<div id="item-detail-container" class="hide"></div>
						<div class="row-fluid">
							<a id="load-members" class="btn btn-block"><?php echo $translate->_("Load More Members"); ?> <span class="bleh"></span></a>
						</div>
					</div>
				</div>
				<br />
				<div id="additions">
					<h2 style="margin-top: 10px" title="Add Members Section"><?php echo $translate->_("Add Members"); ?></h2>
                    <p>If you would like to add users that already exist in the course enrolment to this group, you can do so by clicking the checkbox beside their name from the list below.
                        Once you have reviewed the list at the bottom and are ready, click the <strong>Proceed</strong> button at the bottom to complete the process.
                    </p>

                    <div class="row-fluid">
                        <div id="group_name_title"></div>
                    </div>
                    <div class="row-fluid">
                        <div class="member-add-type" id="existing-member-add-type">
                            <label for="choose-members-btn" class="control-label form-required"><?php echo $translate->_("Select Members"); ?></label>
                            <div class="controls">
                                <button id="choose-members-btn" class="btn btn-search-filter" style="min-width: 220px; text-align: left;"><?php echo $translate->_("Search All Members"); ?> <i class="icon-chevron-down btn-icon pull-right"></i></button>
                            </div>
                        </div>

                        <div id="group_members_list">
                            <h3>Members to be Added on Submission</h3>
                        </div>
                    </div>

                    <div class="row-fluid">
                        <input type="button" class="btn" value="<?php echo $translate->_("Cancel"); ?>" onclick="window.location='<?php echo ENTRADA_URL; ?>/admin/courses/groups?id=<?php echo $COURSE_ID; ?>'" />
                        <input type="submit" class="btn btn-primary pull-right" value="<?php echo $translate->_("Proceed"); ?>" style="vertical-align: middle" id="add_member_submit"/>
                    </div>

                    <input type="hidden" id="add_group_id" name="add_group_id" value="<?php echo $GROUP_ID; ?>" />
                </div>
            </form>
            <script type="text/javascript">
                jQuery(document).ready(function ($) {
                    $("#choose-members-btn").advancedSearch({
                        api_url: "<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/groups?section=api-enrolment"; ?>",
                        build_selected_filters: false,
                        reset_api_params: true,
                        resource_url: ENTRADA_URL,
                        filter_component_label: "Users",
                        filters: {
                            student: {
                                api_params: {
                                    course_id: "<?php echo $COURSE_ID; ?>",
                                    cperiod_id: "<?php echo $current_cperiod_id; ?>",
                                    cgroup_id: "<?php echo $GROUP_ID; ?>",
                                    organisation_id: "<?php echo $course->getOrganisationID(); ?>"
                                },
                                label: "<?php echo $translate->_("Students"); ?>",
                                data_source: "search-enrolment"
                            }
                        },
                        list_data: {
                            selector: "#group_members_list",
                            background_value: "url(../../images/list-community.gif) no-repeat scroll 0 4px transparent"
                        },
                        no_results_text: "<?php echo $translate->_("No Users found matching the search criteria"); ?>",
                        parent_form: $("#groupForm"),
                        width: 300,
                        async: false
                    });
                });
            </script>
			<div id="delete-members-modal" class="modal hide fade">
				<form id="delete-members-modal-item" class="form-horizontal" action="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-members"; ?>" method="POST" style="margin:0px;">
					<input type="hidden" name="step" value="2" />
					<div class="modal-header"><h1> <?php echo $translate->_("Delete Group Members"); ?></h1></div>
					<div class="modal-body">
						<div id="no-members-selected" class="hide">
							<p><?php echo $translate->_("No Member Selected to delete."); ?></p>
						</div>
						<div id="members-selected" class="hide">
							<p><?php echo $translate->_("Please confirm you would like to proceed with the selected Member(s)?"); ?></p>
							<div id="delete-members-container"></div>
						</div>
					</div>
					<div class="modal-footer">
						<div class="row-fluid">
							<a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Cancel"); ?></a>
							<input id="delete-members-modal-delete" type="submit" class="btn btn-danger" value="<?php echo $translate->_("Delete"); ?>" />
						</div>
					</div>
				</form>
			</div>
            <br /><br />
		<?php
		break;	

	}
}
