<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to edit existing discussion forums within a community. This action is
 * only available to community administrators.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
*/

if ((!defined("COMMUNITY_INCLUDED")) || (!defined("IN_DISCUSSIONS"))) {
	exit;
} elseif (!$COMMUNITY_LOAD) {
	exit;
}

$HEAD[] = "<link href=\"".ENTRADA_URL."/javascript/calendar/css/xc2_default.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
$HEAD[] = "<script src=\"".ENTRADA_URL."/javascript/calendar/config/xc2_default.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[] = "<script src=\"".ENTRADA_URL."/javascript/calendar/script/xc2_inpage.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[] = "<script src=\"".COMMUNITY_URL."/javascript/discussions.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";

echo "<h1>Edit Discussion Forum</h1>\n";

if ($RECORD_ID) {
	$query = "SELECT * FROM `community_discussions` WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `cpage_id` = ".$db->qstr($PAGE_ID)." AND `cdiscussion_id` = ".$db->qstr($RECORD_ID);
	$discussion_record = $db->GetRow($query);
	if ($discussion_record) {
		if ((int) $discussion_record["forum_active"]) {
			$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-forum&id=".$RECORD_ID, "title" => limit_chars($discussion_record["forum_title"], 32));
			$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=edit-forum&amp;id=".$RECORD_ID, "title" => "Edit Discussion Forum");

            //Check if this community is connected to a course or not

            $isCommunityCourse = Models_Community_Course::is_community_course($COMMUNITY_ID);

            if ($isCommunityCourse) {
                $course_groups_query = "SELECT a.*, b.`course_code`, b.`course_name`
                                          FROM `course_groups` AS a
                                          JOIN `courses` AS b
                                          ON b.`course_id` = a.`course_id`
                                          JOIN `community_courses` AS c
                                          ON c.`course_id` = b.`course_id`
                                          WHERE a.`active` = 1
                                          AND c.`community_id` = ".$db->qstr($COMMUNITY_ID);
                $community_course_groups = $db->GetAll($course_groups_query);

                $query = "SELECT `id`, `create`, `read`, `update`, `delete`, `assertion`
                            FROM `community_acl`
                            WHERE `resource_type` = 'communitydiscussion'
                            AND `resource_value` = " . $db->qstr($RECORD_ID);
                $permission_db = $db->GetRow($query);
                ?>
                <script>
                    jQuery(document).ready(function() {
                       function hideCourseGroups() {
                           if (jQuery("#course-group-checkbox").is(':checked')) {
                                jQuery(".course-group-permissions").show();
                           } else {
                                jQuery(".course-group-permissions").hide();
                           }
                       }
                       //Set the initial UI state
                       hideCourseGroups();

                       jQuery(".permission-type-checkbox").click(function() {
                           hideCourseGroups();
                       });
                    });
                </script>
                <?php
            }

			// Error Checking
			switch($STEP) {
				case 2 :
					/**
					 * Required field "title" / Forum Title.
					 */
					if ((isset($_POST["forum_title"])) && ($title = clean_input($_POST["forum_title"], array("notags", "trim")))) {
						$PROCESSED["forum_title"] = $title;
					} else {
						add_error("The <strong>Forum Title</strong> field is required.");
					}

					/**
					 * Non-Required field "description" / Forum Description.
					 */
					if ((isset($_POST["forum_description"])) && ($description = clean_input($_POST["forum_description"], array("notags", "trim")))) {
						$PROCESSED["forum_description"] = $description;
					} else {
						$PROCESSED["forum_description"] = "";
					}
					
					/**
					* Non-Required field "category" / Forum Category.
					*/
					if ((isset($_POST["forum_category"])) && ($description = clean_input($_POST["forum_category"], array("notags", "trim")))) {
						$PROCESSED["forum_category"] = $description;
					} else {
						$PROCESSED["forum_category"] = "";
					}
                    
                    /**
                     * Required field "permission_acl_style" for community courses
                     */
                    if ($isCommunityCourse) {
						//Checks if the ACL assertion is set
						if (!isset($_POST["permission_acl_style"])) {
							add_error("The <strong>Permission Level</strong> field is required.");
						}
					}
                    
                    /**
                     * Permission checking for member access.
                     */
                    if ((isset($_POST["allow_member_read"])) && (clean_input($_POST["allow_member_read"], array("int")) == 1)) {
                        $PROCESSED["allow_member_read"]	= 1;
                    } else {
                        $PROCESSED["allow_member_read"]	= 0;
                    }
                    if ((isset($_POST["allow_member_post"])) && (clean_input($_POST["allow_member_post"], array("int")) == 1)) {
                        $PROCESSED["allow_member_post"]	= 1;
                    } else {
                        $PROCESSED["allow_member_post"]	= 0;
                    }
                    if ((isset($_POST["allow_member_reply"])) && (clean_input($_POST["allow_member_reply"], array("int")) == 1)) {
                        $PROCESSED["allow_member_reply"]	= 1;
                    } else {
                        $PROCESSED["allow_member_reply"]	= 0;
                    }

                    /**
                     * Permission checking for troll access.
                     * This can only be done if the community_registration is set to "Open Community"
                     */
                    if (!(int) $community_details["community_registration"]) {
                        if ((isset($_POST["allow_troll_read"])) && (clean_input($_POST["allow_troll_read"], array("int")) == 1)) {
                            $PROCESSED["allow_troll_read"]	= 1;
                        } else {
                            $PROCESSED["allow_troll_read"]	= 0;
                        }
                        if ((isset($_POST["allow_troll_post"])) && (clean_input($_POST["allow_troll_post"], array("int")) == 1)) {
                                $PROCESSED["allow_troll_post"]	= 1;
                        } else {
                            $PROCESSED["allow_troll_post"]	= 0;
                        }
                        if ((isset($_POST["allow_troll_reply"])) && (clean_input($_POST["allow_troll_reply"], array("int")) == 1)) {
                            $PROCESSED["allow_troll_reply"]	= 1;
                        } else {
                            $PROCESSED["allow_troll_reply"]	= 0;
                        }
                    } else {
                        $PROCESSED["allow_troll_read"]		= 0;
                        $PROCESSED["allow_troll_post"]		= 0;
                        $PROCESSED["allow_troll_reply"]		= 0;
                    }

                    /**
                     * Permission checking for public access.
                     * This can only be done if the community_protected is set to "Public Community"
                     */
                    if (!(int) $community_details["community_protected"]) {
                        if ((isset($_POST["allow_public_read"])) && (clean_input($_POST["allow_public_read"], array("int")) == 1)) {
                            $PROCESSED["allow_public_read"]	= 1;
                        } else {
                            $PROCESSED["allow_public_read"]	= 0;
                        }
                        $PROCESSED["allow_public_post"]		= 0;
                        $PROCESSED["allow_public_reply"]	= 0;
                    } else {
                        $PROCESSED["allow_public_read"]		= 0;
                        $PROCESSED["allow_public_post"]		= 0;
                        $PROCESSED["allow_public_reply"]	= 0;
                    }

					/**
					 * Email Notificaions.
					 */
					if (isset($_POST["admin_notifications"])) {
						$PROCESSED["admin_notifications"] = $_POST["admin_notifications"];
					} elseif(isset($_POST["admin_notify"]) || isset($_POST["member_notify"])) {
						$PROCESSED["admin_notifications"] = $_POST["admin_notify"] + $_POST["member_notify"];
					}

					/**
					 * Required field "release_from" / Release Start (validated through validate_calendars function).
					 * Non-required field "release_until" / Release Finish (validated through validate_calendars function).
					 */
					$release_dates = validate_calendars("release", true, false);
					if ((isset($release_dates["start"])) && ((int) $release_dates["start"])) {
						$PROCESSED["release_date"]	= (int) $release_dates["start"];
					} else {
						add_error("The <strong>Release Start</strong> field is required.");
					}
					if ((isset($release_dates["finish"])) && ((int) $release_dates["finish"])) {
						$PROCESSED["release_until"]	= (int) $release_dates["finish"];
					} else {
						$PROCESSED["release_until"]	= 0;
					}

					if (!$ERROR) {
						$PROCESSED["updated_date"]	= time();
						$PROCESSED["updated_by"]	= $ENTRADA_USER->getID();

						if ($db->AutoExecute("community_discussions", $PROCESSED, "UPDATE", "`community_id` = ".$db->qstr($COMMUNITY_ID)." AND `cdiscussion_id` = ".$db->qstr($RECORD_ID))) {
							//add community course group permissions to community_acl_groups
                            if ($_POST["permission_acl_style"] === "CourseGroupMember" && $community_course_groups && count($community_course_groups)) {
                                 foreach ($community_course_groups as $community_course_group){ 
									//Set the default value to '0'
                                    $PROCESSED[$community_course_group['cgroup_id']] = array("create"=>0, "read"=>0, "update"=>0, "delete"=>0);

                                    if ($_POST[$community_course_group['cgroup_id']]){    
                                        foreach ($_POST[$community_course_group['cgroup_id']] as $perms) {
                                            //Update the value to '1' if it was submitted
                                            $PROCESSED[$community_course_group['cgroup_id']][clean_input($perms)] = 1;
                                        }
                                    }

                                    $query = "SELECT COUNT(*) AS `total_rows`
                                                FROM `community_acl_groups`
                                                WHERE `cgroup_id` = ".$db->qstr($community_course_group['cgroup_id'])."
                                                AND `resource_type` = 'communitydiscussion'
                                                AND `resource_value` = ".$db->qstr($RECORD_ID);
                                    $record = $db->GetRow($query);

                                    if ($record["total_rows"] > 0) {
                                        $db->AutoExecute("community_acl_groups", array("create" => $PROCESSED[$community_course_group['cgroup_id']]['create'], "read"=>$PROCESSED[$community_course_group['cgroup_id']]['read'], "update"=>$PROCESSED[$community_course_group['cgroup_id']]['update'], "delete"=>$PROCESSED[$community_course_group['cgroup_id']]['delete']), "UPDATE", "`cgroup_id` = ".$db->qstr($community_course_group['cgroup_id'])." AND `resource_type` = 'communitydiscussion' AND `resource_value` = ".$db->qstr($RECORD_ID));
                                    } else {
                                        $db->AutoExecute("community_acl_groups", array("cgroup_id" => $community_course_group['cgroup_id'], "resource_type"=>"communitydiscussion", "resource_value"=>$RECORD_ID,"create"=>$PROCESSED[$community_course_group['cgroup_id']]['create'], "read"=>$PROCESSED[$community_course_group['cgroup_id']]['read'], "update"=>$PROCESSED[$community_course_group['cgroup_id']]['update'], "delete"=>$PROCESSED[$community_course_group['cgroup_id']]['delete']), "INSERT");
                                    }
                                }
                            }

                            //If the user's role is 'admin', use the submitted form values.
                            if ($COMMUNITY_ADMIN) {
                            	$update_perm = array(
	                            	'read' => (($_POST['read']) ? 1 : 0),
                                    'create' => (($_POST['create']) ? 1 : 0),
                                    'update' => (($_POST['update']) ? 1 : 0),
                                    'delete' => (($_POST['delete']) ? 1 : 0),
                                    'assertion' => $_POST['permission_acl_style']
                                );
                            } else {
                            	//If the user is not an admin, set these default permissions 
                                $update_perm = array(
                                    'read' => 1,
                                    'create' => 1,
                                    'update' => 0,
                                    'delete' => 0,
                                    'assertion' => $_POST['permission_acl_style']
                                ); 
                            }
							$update_perm["resource_type"] = "communitydiscussion";
							$update_perm["resource_value"] = $RECORD_ID;
							
							if ($db->GetRow("SELECT * FROM `community_acl` WHERE `resource_type` = 'communitydiscussion' AND `resource_value` = ".$db->qstr($RECORD_ID))) {
								$results = $db->AutoExecute("`community_acl`", $update_perm, "UPDATE",
									"`resource_type` = 'communitydiscussion' AND `resource_value` = ".$db->qstr($RECORD_ID));
							} else {
								$results = $db->AutoExecute("`community_acl`", $update_perm, "INSERT");
							}
                            if ($results === false) {
                                add_error("Error updating the ACL permissions database.");
                            }

                            if (!$ERROR) {
                                Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("You have successfully updated <strong>%s</strong>."), $PROCESSED["forum_title"]), "success", $MODULE);

                                add_statistic("community:".$COMMUNITY_ID.":discussions", "forum_edit", "cdiscussion_id", $RECORD_ID);
                                communities_log_history($COMMUNITY_ID, $PAGE_ID, $RECORD_ID, "community_history_edit_forum", 1);

                                $url = COMMUNITY_URL . $COMMUNITY_URL .":" . $PAGE_URL . "?title=" . $PROCESSED["forum_title"];
                                header("Location: " . $url);
                                exit;
                            }
						} else {
						    add_error("There was a problem inserting this forum into the system.");
							application_log("error", "There was an error inserting a discussion forum. Database said: ".$db->ErrorMsg());
						}
					}

					if ($ERROR) {
						$STEP = 1;
					}
				break;
				case 1 :
				default :
					$PROCESSED = $discussion_record;
				break;
			}

			// Page Display
			switch($STEP) {
				case 1 :
				default :
					if ($ERROR) {
						echo display_error();
					}
					if ($NOTICE) {
						echo display_notice();
					}
					?>
					<form action="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=edit-forum&amp;id=<?php echo $RECORD_ID; ?>&amp;step=2" method="post">
                        <table class="community-add-table" summary="Edit Discussion Forum">
                            <colgroup>
                                <col style="width: 20%" />
                                <col style="width: 80%" />
                            </colgroup>
                            <tfoot>
                                <tr>
                                    <td colspan="2" style="padding-top: 15px; text-align: right">
                                        <input type="submit" class="btn btn-primary" value="<?php echo $translate->_("global_button_update"); ?>" />
                                    </td>
                                </tr>
                            </tfoot>
                            <tbody>
                                <tr>
                                    <td colspan="2">
                                        <h2>Forum Details</h2>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <label for="forum_title" class="form-required">Forum Title</label>
                                    </td>
                                    <td>
                                        <input type="text" id="forum_title" name="forum_title" value="<?php echo ((isset($PROCESSED["forum_title"])) ? html_encode($PROCESSED["forum_title"]) : ""); ?>" maxlength="64" style="width: 95%" />
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <label for="forum_description" class="form-nrequired">Forum Description</label>
                                    </td>
                                    <td>
                                        <textarea id="forum_description" name="forum_description" style="width: 95%; height: 60px" cols="50" rows="5"><?php echo ((isset($PROCESSED["forum_description"])) ? html_encode($PROCESSED["forum_description"]) : ""); ?></textarea>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <label for="forum_category" class="form-nrequired">Forum Category</label>
                                    </td>
                                    <td>
                                        <input type="text" id="forum_category" name="forum_category" value="<?php echo ((isset($PROCESSED["forum_category"])) ? html_encode($PROCESSED["forum_category"]) : ""); ?>" maxlength="64" style="width: 95%" />
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2"><h2>Forum Permissions</h2></td>
                                </tr>
                                <?php
                                if ($isCommunityCourse) {
                                    ?>
                                    <tr>
                                        <td style="vertical-align: top">
                                            <label for="permission_level" class="form-required">Permission Level</label>
                                        </td>
                                        <td>
                                            <table class="table table-bordered no-thead">
                                                <colgroup>
                                                    <col style="width: 5%" />
                                                    <col style="width: auto" />
                                                </colgroup>
                                                <tr>
                                                    <td>
                                                        <input id="community-all-checkbox" class="permission-type-checkbox" type="radio" name="permission_acl_style" value="CourseCommunityEnrollment" <?php if ($permission_db['assertion'] == 'CourseCommunityEnrollment') { echo "checked='checked'"; } ?> />
                                                    </td>
                                                    <td>
                                                        <label for="community-all-checkbox" class="content-small">All Community Members</label>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <input id="course-group-checkbox" class="permission-type-checkbox" type="radio" name="permission_acl_style" value="CourseGroupMember" <?php if ($permission_db['assertion'] == 'CourseGroupMember') { echo "checked='checked'"; } ?> />
                                                    </td>
                                                    <td>
                                                        <label for="course-group-checkbox" class="content-small">Course Groups</label>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <?php
                                    if ($COMMUNITY_ADMIN) {
                                        ?>
                                        <tr>
                                            <td colspan="2"><h3>Member Permissions</h3></td>
                                        </tr>
                                        <tr class="forum-permissions">
                                            <td colspan="2">
                                                <table class="table table-striped table-bordered table-community-centered">
                                                    <colgroup>
                                                        <col style="width: 50%" />
                                                        <col style="width: 50%" />
                                                    </colgroup>
                                                    <thead>
                                                    <tr>
                                                        <td>View Forum</td>
                                                        <td style="border-left: none">Write New Posts</td>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <tr>
                                                        <td class="on"><input type="checkbox" id="read" name="read" value="read" <?php echo (isset($permission_db['read']) && ($permission_db['read'] == 1)) ? " checked=\"checked\"" : ""; ?> /></td>
                                                        <td><input type="checkbox" id="create" name="create" value="create" <?php echo (isset($permission_db['create']) && ($permission_db['create'] == 1)) ? " checked=\"checked\"" : ""; ?> /></td>
                                                    </tr>
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                    ?>
                                    <tr class="course-group-permissions">
                                        <td colspan="3">
                                            <h3>Course Group Permissions</h3>
                                        </td>
                                    </tr>
                                    <tr class="course-group-permissions">
                                        <td colspan="3">
                                        <?php
                                        $course_ids = array_unique(array_map(function($item) { return (int)$item['course_id']; }, $community_course_groups));
                                        foreach ($course_ids as $course_id) {
                                            $course_groups = array_filter($community_course_groups, function($item) use ($course_id) {
                                                return (int)$item['course_id'] === $course_id;
                                            });
                                            usort($course_groups, function($a, $b) {
                                                if ($a['group_name'] < $b['group_name']) {
                                                    return -1;
                                                } else if ($a['group_name'] > $b['group_name']) {
                                                    return 1;
                                                } else {
                                                    return 0;
                                                }
                                            });
                                            $course_code = $course_groups[0]['course_code'];
                                            $course_name = $course_groups[0]['course_name'];

                                            echo "<strong>$course_code: $course_name</strong>";
                                            ?>
                                            <table class="table table-striped table-bordered table-community-centered-list">
                                                <colgroup>
                                                    <col style="width: 50%" />
                                                    <col style="width: 25%" />
                                                    <col style="width: 25%" />
                                                </colgroup>
                                                <thead>
                                                    <tr>
                                                        <td>Group</td>
                                                        <td style="border-left: none">View Forum</td>
                                                        <td style="border-left: none">Write New Posts</td>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                <?php
                                                foreach ($course_groups as $course_group) {
                                                    $query	= "SELECT `create`, `read`, `update`, `delete`
                                                       FROM `community_acl_groups`
                                                       WHERE `cgroup_id` = ".$db->qstr($course_group['cgroup_id'])."
                                                       AND `resource_value` = ".$db->qstr($RECORD_ID);
                                                    $community_course_perms	= $db->GetRow($query);
                                                    ?>
                                                    <tr>
                                                        <td class="left"><strong><?php echo $course_group['group_name']; ?></strong></td>
                                                        <td class="on"><input type="checkbox" id="<?php echo $course_group['cgroup_id']; ?>_read" name="<?php echo $course_group['cgroup_id']; ?>[]" value="read"<?php echo (isset($community_course_perms['read']) && ($community_course_perms['read'] == 1)) ? " checked=\"checked\"" : ""; ?> /></td>
                                                        <td><input type="checkbox" id="<?php echo $course_group['cgroup_id']; ?>_create" name="<?php echo $course_group['cgroup_id']; ?>[]" value="create"<?php echo (isset($community_course_perms['create']) && ($community_course_perms['create'] == 1)) ? " checked=\"checked\"" : ""; ?> /></td>
                                                    </tr>
                                                    <?php
                                                }
                                                ?>
                                                </tbody>
                                            </table>
                                            <?php
                                        }

                                        if (!(int) $community_details["community_registration"] || !(int) $community_details["community_protected"]) { ?>
                                            <h3>Non-Member Permissions</h3>
                                            <table class="table table-striped table-bordered table-community-centered-list">
                                            <colgroup>
                                                <col style="width: 50%" />
                                                <col style="width: 25%" />
                                                <col style="width: 25%" />
                                            </colgroup>
                                            <thead>
                                                <tr>
                                                    <td>Group</td>
                                                    <td style="border-left: none">View Forum</td>
                                                    <td style="border-left: none">Write New Posts</td>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php if (!(int) $community_details["community_registration"]) { ?>
                                            <tr>
                                                <td class="left"><strong>Browsing Non-Members</strong></td>
                                                <td class="on"><input type="checkbox" id="allow_troll_read" name="allow_troll_read" value="1"<?php echo (((!isset($PROCESSED["allow_troll_read"])) || ((isset($PROCESSED["allow_troll_read"])) && ($PROCESSED["allow_troll_read"] == 1))) ? " checked=\"checked\"" : ""); ?> /></td>
                                                <td><input type="checkbox" id="allow_troll_post" name="allow_troll_post" value="1"<?php echo (((isset($PROCESSED["allow_troll_post"])) && ($PROCESSED["allow_troll_post"] == 1)) ? " checked=\"checked\"" : ""); ?> /></td>
                                            </tr>
                                            <?php } ?>
                                            <?php if (!(int) $community_details["community_protected"]) {  ?>
                                            <tr>
                                                <td class="left"><strong>Non-Authenticated / Public Users</strong></td>
                                                <td class="on"><input type="checkbox" id="allow_public_read" name="allow_public_read" value="1"<?php echo (((isset($PROCESSED["allow_public_read"])) && ($PROCESSED["allow_public_read"] == 1)) ? " checked=\"checked\"" : ""); ?> /></td>
                                                <td><input type="checkbox" id="allow_public_post" name="allow_public_post" value="0" onclick="noPublic(this)" /></td>
                                            </tr>
                                            <?php } ?>
                                            </tbody>
                                            </table>
                                        <?php } ?>
                                        </td>
                                    </tr>
                                    <?php
                                } else {
                                    ?>
                                    <tr>
                                        <td colspan="3">
                                            <table class="table table-striped table-bordered table-community-centered-list">
                                            <colgroup>
                                                <col style="width: 40%" />
                                                <col style="width: 20%" />
                                                <col style="width: 20%" />
                                                <col style="width: 20%" />
                                            </colgroup>
                                            <thead>
                                                <tr>
                                                    <td>Group</td>
                                                    <td>View Forum</td>
                                                    <td>Write New Posts</td>
                                                    <td>Reply To Posts</td>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <strong>Community Administrators</strong>
                                                    </td>
                                                    <td class="on">
                                                        <input type="checkbox" id="allow_admin_read" name="allow_admin_read" value="1" checked="checked" onclick="this.checked = true" />
                                                    </td>
                                                    <td>
                                                        <input type="checkbox" id="allow_admin_post" name="allow_admin_post" value="1" checked="checked" onclick="this.checked = true" />
                                                    </td>
                                                    <td class="on">
                                                        <input type="checkbox" id="allow_admin_reply" name="allow_admin_reply" value="1" checked="checked" onclick="this.checked = true" />
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <strong>Community Members</strong>
                                                    </td>
                                                    <td class="on">
                                                         <input type="checkbox" id="allow_member_read" name="allow_member_read" value="1"<?php echo (((!isset($PROCESSED["allow_member_read"])) || ((isset($PROCESSED["allow_member_read"])) && ($PROCESSED["allow_member_read"] == 1))) ? " checked=\"checked\"" : ""); ?> />
                                                    </td>
                                                    <td>
                                                        <input type="checkbox" id="allow_member_post" name="allow_member_post" value="1"<?php echo (((!isset($PROCESSED["allow_member_post"])) || ((isset($PROCESSED["allow_member_post"])) && ($PROCESSED["allow_member_post"] == 1))) ? " checked=\"checked\"" : ""); ?> />
                                                    </td>
                                                    <td class="on">
                                                        <input type="checkbox" id="allow_member_reply" name="allow_member_reply" value="1"<?php echo (((!isset($PROCESSED["allow_member_reply"])) || ((isset($PROCESSED["allow_member_reply"])) && ($PROCESSED["allow_member_reply"] == 1))) ? " checked=\"checked\"" : ""); ?> />
                                                    </td>
                                                </tr>
                                                <?php if (!(int) $community_details["community_registration"]) {  ?>
                                                <tr>
                                                    <td>
                                                        <strong>Browsing Non-Members</strong>
                                                    </td>
                                                    <td class="on">
                                                        <input type="checkbox" id="allow_troll_read" name="allow_troll_read" value="1"<?php echo (((!isset($PROCESSED["allow_troll_read"])) || ((isset($PROCESSED["allow_troll_read"])) && ($PROCESSED["allow_troll_read"] == 1))) ? " checked=\"checked\"" : ""); ?> />
                                                    </td>
                                                    <td>
                                                        <input type="checkbox" id="allow_troll_post" name="allow_troll_post" value="1"<?php echo (((isset($PROCESSED["allow_troll_post"])) && ($PROCESSED["allow_troll_post"] == 1)) ? " checked=\"checked\"" : ""); ?> />
                                                    </td>
                                                    <td class="on">
                                                        <input type="checkbox" id="allow_troll_reply" name="allow_troll_reply" value="1"<?php echo (((isset($PROCESSED["allow_troll_reply"])) && ($PROCESSED["allow_troll_reply"] == 1)) ? " checked=\"checked\"" : ""); ?> />
                                                    </td>
                                                </tr>
                                                <?php } ?>
                                                <?php if (!(int) $community_details["community_protected"]) {  ?>
                                                <tr>
                                                    <td>
                                                        <strong>Non-Authenticated / Public Users</strong>
                                                    </td>
                                                    <td class="on">
                                                        <input type="checkbox" id="allow_public_read" name="allow_public_read" value="1"<?php echo (((isset($PROCESSED["allow_public_read"])) && ($PROCESSED["allow_public_read"] == 1)) ? " checked=\"checked\"" : ""); ?> />
                                                    </td>
                                                    <td>
                                                        <input type="checkbox" id="allow_public_post" name="allow_public_post" value="0" onclick="noPublic(this)" />
                                                    </td>
                                                    <td class="on">
                                                        <input type="checkbox" id="allow_public_reply" name="allow_public_reply" value="0" onclick="noPublic(this)" />
                                                    </td>
                                                </tr>
                                                <?php } ?>
                                            </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                    <?php
                                }
                                ?>
                                <tr>
                                    <td colspan="2">
                                        <h2>Time Release Options</h2>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <table class="date-time">
                                            <?php echo generate_calendars("release", "", true, true, ((isset($PROCESSED["release_date"])) ? $PROCESSED["release_date"] : time()), true, false, ((isset($PROCESSED["release_until"])) ? $PROCESSED["release_until"] : 0)); ?>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
					</form>
					<?php
				break;
			}
		} else {
			add_notice("The discussion forum that you are trying to edit was deactivated ".date(DEFAULT_DATE_FORMAT, $discussion_record["updated_date"])."</strong> by ".html_encode(get_account_data("firstlast", $discussion_record["updated_by"]))."</strong>.");

			echo display_notice();

			application_log("error", "The discussion forum record id [".$RECORD_ID."] is deactivated; however, ".$_SESSION["details"]["firstname"]." ".$_SESSION["details"]["lastname"]." [".$ENTRADA_USER->getID()."] has tried to edit it.");
		}
	} else {
		add_error("The discussion forum id that you have provided does not exist in the system. Please provide a valid record id to proceed.");

		echo display_error();

		application_log("error", "The provided discussion forum id was invalid [".$RECORD_ID."] (Edit Forum).");
	}
} else {
	add_error("Please provide a valid discussion forum id to proceed.");

	echo display_error();

	application_log("error", "No discussion forum id was provided to edit. (Edit Forum)");
}
