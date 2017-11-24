<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to edit existing files in a community. This action can be called by
 * either the user who originally uploaded the file or by any community
 * administrator.
 * 
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2013 Regents of The University of California. All Rights Reserved.
 * 
*/

if ((!defined("COMMUNITY_INCLUDED")) || (!defined("IN_SHARES"))) {
	exit;
} elseif (!$COMMUNITY_LOAD) {
	exit;
}

$HEAD[] = "<link href=\"".ENTRADA_URL."/javascript/calendar/css/xc2_default.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/calendar/config/xc2_default.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/calendar/script/xc2_inpage.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"".COMMUNITY_URL."/javascript/shares.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";

echo "<h1>Edit Link</h1>\n";

//Check to see if the community is connected to a course
$isCommunityCourse = Models_Community_Course::is_community_course($COMMUNITY_ID);

if ($RECORD_ID) {
	$query			= "
					SELECT a.*, b.`folder_title`, b.`admin_notifications`
					FROM `community_share_links` AS a
					LEFT JOIN `community_shares` AS b
					ON a.`cshare_id` = b.`cshare_id`
					WHERE a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
					AND a.`cslink_id` = ".$db->qstr($RECORD_ID)."
					AND b.`cpage_id` = ".$db->qstr($PAGE_ID)."
					AND a.`link_active` = '1'
					AND b.`folder_active` = '1'";
	$file_record	= $db->GetRow($query);
	if ($file_record) {
		if ((int) $file_record["link_active"]) {
			if (shares_link_module_access($RECORD_ID, "edit-link")) {
                Models_Community_Share::getParentsBreadCrumbs($RECORD_ID);
                $BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-link&id=".$RECORD_ID, "title" => limit_chars($file_record["link_title"], 32));
				$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=edit-link&amp;id=".$RECORD_ID, "title" => "Edit Link");

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
                              WHERE `resource_type` = 'communitylink'
                              AND `resource_value` = ".$db->qstr($RECORD_ID);
                    $permission_db = $db->GetRow($query);

                    ?>
                    <script type="text/javascript">
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
                            * Required field "title" / Link Title.
                            */
                            if ((isset($_POST["link_title"])) && ($title = clean_input($_POST["link_title"], array("notags", "trim")))) {
                                $PROCESSED["link_title"] = $title;
                                $link_uploads["link_title"] = $title;
                            } else {
                                $ERROR++;
                                $ERRORSTR[] = "The <strong>Link Title</strong> field is required.";
                            }

                            /**
                            * Required field "url" / Link URL.
                            */
                            if ((isset($_POST["link_url"])) && ($link_url = clean_input($_POST["link_url"], array("trim")))) {
                                $PROCESSED["link_url"] = $link_url;
                                $link_uploads["link_url"] = $link_url;
                            } else {
                                $ERROR++;
                                $ERRORSTR[] = "The <strong>Link URL</strong> field is required.";
                            }

                            /**
                            * Non-Required field "description" / Link Description.
                            *
                            */
                            if ((isset($_POST["link_description"])) && $description = clean_input($_POST["link_description"], array("notags", "trim"))) {
                                $PROCESSED["link_description"] = $description;
                                $link_uploads["link_description"] = $description;
                            } else {
                                $PROCESSED["link_description"] = "";
                                $link_uploads["link_description"] = "";
                            }
                            
                            /**
                             * Non-Required field "access_method" / View Method.
                             */
                            if ((isset($_POST["access_method"])) && clean_input($_POST["access_method"], array("int")) == 1) {
                                $PROCESSED["access_method"] = 1;
                                $link_uploads["access_method"] = 1;
                            } else {
                                $PROCESSED["access_method"] = 0;
                                $link_uploads["access_method"] = 0;
                            }
                            
                            /** Required "iframe_resize" / iframe_resize Method.
                             */
                            if ((isset($_POST["iframe_resize"])) && (clean_input($_POST["iframe_resize"], array("int")) == 1)) {
                                $PROCESSED["iframe_resize"]	= 1;
                                $link_uploads["iframe_resize"] = 1;
                            } else {
                                $PROCESSED["iframe_resize"]	= 0;
                                $link_uploads["iframe_resize"] = 0;
                            }
                            
                            /**
                             * Non-Required field "access_method" / View Method.
                             */
                            if ((isset($_POST["session_variables"])) && clean_input($_POST["session_variables"], array("int")) == 1) {
                                $PROCESSED["session_variables"] = 1;
                                $link_uploads["session_variables"] = 1;
                            } else {
                                $PROCESSED["session_variables"] = 0;
                                $link_uploads["session_variables"] = 0;
                            }
							
                            /**
                             * Non-Required field "student_hidden" / View Method.
                             */
                            if ((isset($_POST["student_hidden"])) && clean_input($_POST["student_hidden"], array("int")) == 1) {
                                $PROCESSED["student_hidden"] = 1;
                                $link_uploads["student_hidden"] = 1;
                            } else {
                                $PROCESSED["student_hidden"] = 0;
                                $link_uploads["student_hidden"] = 0;
                            }

                            /**
                             * Required field "permission_acl_style" for community courses
                             */
	                        if ($isCommunityCourse) {
	                            if (!isset($_POST["permission_acl_style"])) {
	                                $ERROR++;
	                                $ERRORSTR[] = "The <strong>Permission Level</strong> field is required.";
	                            }
	                        }
                            
                            /**
                             * Permission checking for member access.
                             */
                            if ((isset($_POST["allow_member_read"])) && (clean_input($_POST["allow_member_read"], array("int")) == 1)) {
                                $PROCESSED["allow_member_read"] = 1;
                            } else {
                                $PROCESSED["allow_member_read"] = 0;
                            }
                            if ((isset($_POST["allow_member_revision"])) && (clean_input($_POST["allow_member_revision"], array("int")) == 1)) {
                                $PROCESSED["allow_member_revision"] = 1;
                            } else {
                                $PROCESSED["allow_member_revision"] = 0;
                            }

                            /**
                             * Permission checking for troll access.
                             * This can only be done if the community_registration is set to "Open Community"
                             */
                            $PROCESSED["allow_troll_read"] = 0;
                            $PROCESSED["allow_troll_revision"] = 0;
                            if (!(int) $community_details["community_registration"]) {
                                if ((isset($_POST["allow_troll_read"])) && (clean_input($_POST["allow_troll_read"], array("int")) == 1)) {
                                    $PROCESSED["allow_troll_read"] = 1;
                                }
                                if ((isset($_POST["allow_troll_revision"])) && (clean_input($_POST["allow_troll_revision"], array("int")) == 1)) {
                                    $PROCESSED["allow_troll_revision"] = 1;
                                }
                            }

                            /**
                            * Required field "release_from" / Release Start (validated through validate_calendars function).
                            * Non-required field "release_until" / Release Finish (validated through validate_calendars function).
                            */
                            $release_dates = validate_calendars("release", true, false);
                            if ((isset($release_dates["start"])) && ((int) $release_dates["start"])) {
                                $PROCESSED["release_date"]	= (int) $release_dates["start"];
                            } else {
                                $ERROR++;
                                $ERRORSTR[] = "The <strong>Release Start</strong> field is required.";
                            }
                            if ((isset($release_dates["finish"])) && ((int) $release_dates["finish"])) {
                                $PROCESSED["release_until"]	= (int) $release_dates["finish"];
                            } else {
                                $PROCESSED["release_until"]	= 0;
                            }

                            if (!$ERROR) {
                                $PROCESSED["updated_date"]	= time();
                                $PROCESSED["updated_by"]	= $ENTRADA_USER->getID();
                                
                                if ($db->AutoExecute("community_share_links", $PROCESSED, "UPDATE", "`community_id` = ".$db->qstr($COMMUNITY_ID)." AND `cslink_id` = ".$db->qstr($RECORD_ID))) {
									//Add course group permissions to community_acl_groups
	                                if ($_POST['permission_acl_style'] === 'CourseGroupMember' && $community_course_groups && !empty($community_course_groups)) {
	                                    foreach ($community_course_groups as $community_course_group) {
	                                        //Set the default value to '0'
	                                        $PROCESSED[$community_course_group['cgroup_id']] = array("create" => 0, "read" => 0, "update" => 0, "delete" => 0);

	                                        if ($_POST[$community_course_group['cgroup_id']]) {
	                                            foreach ($_POST[$community_course_group['cgroup_id']] as $perms) {
	                                                //Update the value to '1' if it was submitted
	                                                $PROCESSED[$community_course_group['cgroup_id']][clean_input($perms)] = 1;
	                                            }
	                                        }

	                                        $query = "SELECT COUNT(*) AS `total_rows`
	                                                  FROM `community_acl_groups`
	                                                  WHERE `cgroup_id` = ".$db->qstr($community_course_group['cgroup_id'])."
	                                                  AND `resource_type` = 'communitylink'
	                                                  AND `resource_value` = ".$db->qstr($RECORD_ID);
	                                        $record = $db->GetRow($query);
	                                        if ($record['total_rows'] > 0) {
	                                            $db->AutoExecute("community_acl_groups", array("create" => $PROCESSED[$community_course_group['cgroup_id']]['create'], "read" => $PROCESSED[$community_course_group['cgroup_id']]['read'], "update" => $PROCESSED[$community_course_group['cgroup_id']]['update'], "delete" => $PROCESSED[$community_course_group['cgroup_id']]['delete']), "UPDATE", "`cgroup_id` = ".$db->qstr($community_course_group['cgroup_id'])." AND `resource_type` = 'communitylink' AND `resource_value` = ".$db->qstr($RECORD_ID));
	                                        } else {
	                                            $db->AutoExecute("community_acl_groups", array("cgroup_id" => $community_course_group['cgroup_id'], "resource_type" => "communitylink", "resource_value" => $RECORD_ID, "create" => $PROCESSED[$community_course_group['cgroup_id']]['create'], "read" => $PROCESSED[$community_course_group['cgroup_id']]['read'], "update" => $PROCESSED[$community_course_group['cgroup_id']]['update'], "delete" => $PROCESSED[$community_course_group['cgroup_id']]['delete']), "INSERT");
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
	                                        'create' => 0,
	                                        'update' => 1,
	                                        'delete' => 0,
	                                        'assertion' => $_POST['permission_acl_style']
	                                    );
	                                }
									$update_perm["resource_type"] = "communitylink";
									$update_perm["resource_value"] = $RECORD_ID;
									
									if ($db->GetRow("SELECT * FROM `community_acl` WHERE `resource_type` = 'communitylink' AND `resource_value` = ".$db->qstr($RECORD_ID))) {
										$results = $db->AutoExecute("`community_acl`", $update_perm, "UPDATE",
											"`resource_type` = 'communitylink' AND `resource_value` = ".$db->qstr($RECORD_ID));
									} else {
										$results = $db->AutoExecute("`community_acl`", $update_perm, "INSERT");
									}
	                                if ($results === false) {
	                                    $ERROR++;
	                                    $ERRORSTR[] = "Error updating the community ACL.";
	                                }

                                    if (COMMUNITY_NOTIFICATIONS_ACTIVE) {
                                        if ($PROCESSED["release_date"] != $file_record["release_date"]) {
                                            $notification = $db->GetRow("SELECT * FROM `community_notifications` WHERE `record_id` = ".$db->qstr($RECORD_ID)." AND `type` = 'link'");
                                            if ($notification) {
                                                $notification["release_time"] = $PROCESSED["release_date"];
                                                $db->AutoExecute("community_notifications", $notification, "UPDATE", "`cnotification_id` = ".$db->qstr($notification["cnotification_id"]));
                                            }
                                        }
                                    }
                                    Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("You have successfully updated <strong>%s</strong>."), $PROCESSED["link_title"]), "success", $MODULE);
                                    add_statistic("community:".$COMMUNITY_ID.":shares", "link_edit", "cslink_id", $RECORD_ID);
                                    communities_log_history($COMMUNITY_ID, $PAGE_ID, $RECORD_ID, "community_history_edit_link", 1, $file_record["cshare_id"]);

                                    $url = COMMUNITY_URL . $COMMUNITY_URL . ":" . $PAGE_URL . "?section=view-folder&id=" . $file_record["cshare_id"];
                                    header("Location: " . $url);
                                    exit;
                                }     
                            }

                            if ($ERROR) {
                                $STEP = 1;
                            }
					break;
					case 1 :
					default :
						$PROCESSED = $file_record;
					break;
				}

				// Page Display
				switch($STEP) {
					case 2 :
						if ($NOTICE) {
							echo display_notice();
						}
						if ($SUCCESS) {
                            echo display_success();
						}
					break;
					case 1 :
					default :
						if ($ERROR) {
							echo display_error();
						}
						if ($NOTICE) {
							echo display_notice();
						}

                        if ($PROCESSED["access_method"] == 1) {
						?>
                           <style>
                           .iframe-resize-control {
                               display: none;
                           }

                           </style>
                        <?php
                        }
                        ?>


                    <form class="form-horizontal" action="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=edit-link&amp;id=<?php echo $RECORD_ID; ?>&amp;step=2" method="post">
                    <div id="link_list" >
                        <table class="community-add-table" summary="Upload Link">
                        <colgroup>
                            <col style="width: 3%" />
                            <col style="width: 20%" />
                            <col style="width: 77%" />
                        </colgroup>
                        <tbody>
                            <tr>
                                <td colspan="2">
                                    <label class="form-required" for="link_url">Link URL</label>
                                </td>
                                <td>
                                    <input type="text" id="link_url" name="link_url"  value="<?php echo ((isset($PROCESSED["link_url"])) ? html_encode($PROCESSED["link_url"]) : ""); ?>" style="width: 70%; margin-bottom: 10px;" />
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <label class="form-required" for="link_title">Link Title</label>
                                </td>
                                <td>
                                    <input type="text" id="link_title" name="link_title" value="<?php echo ((isset($PROCESSED["link_title"])) ? html_encode($PROCESSED["link_title"]) : ""); ?>" maxlength="84" style="width: 70%; margin-bottom: 10px;" />
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <label class="form-nrequired" for="link_description">Link Description</label>
                                </td>
                                <td>
                                    <textarea id="link_description" name="link_description" style="width: 70%;resize: vertical;"><?php echo ((isset($PROCESSED["link_description"])) ? html_encode($PROCESSED["link_description"]) : ""); ?></textarea>
                                </td>
                            </tr>

                            <tr>
                                <td colspan="2">
                                   <label for="access_method" class="form-nrequired space-above">Access Method</label>
                                </td>
                                <td>
                                    <table class="table table-bordered no-thead space-above">
                                        <colgroup>
                                            <col style="width: 5%" />
                                            <col style="width: auto" />
                                        </colgroup>
                                        <tbody>
                                        <tr>
                                            <td class="center">
                                                <input type="radio" id="access_method_0" class="access_method" name="access_method" value="0"<?php echo (((isset($PROCESSED["access_method"])) && ((int) $PROCESSED["access_method"]) == 0) ? " checked" : ""); ?> />
                                            </td>
                                            <td>
                                                <label for="access_method_0" class="content-small">Open this URL in a <?php echo APPLICATION_NAME;?> iframe page. (May not work with all sites)</label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="center">
                                                <input type="radio" id="access_method_1" class="access_method" name="access_method" value="1" <?php echo (((isset($PROCESSED["access_method"])) && ((int) $PROCESSED["access_method"]) == 1) ? " checked" : ""); ?> />
                                            </td>
                                            <td>
                                                <label for="access_method_1" class="content-small">Open this URL in a new window.</label>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>

                                    <tr class="iframe-resize-control" id="iframe_resize">
                                        <td colspan="2">
                                            <label for="iframe_resize" class="form-nrequired">
                                               Iframe Javascript Resizing
                                               <?php if (strtolower($ENTRADA_USER->getActiveGroup()) == "medtech") { ?>
                                               <br/><span class="dev-notes-link" id="resizer-notes-link">(Show Developer Notes)</span>
                                               <?php } ?>
                                           </label>
                                        </td>
                                        <td>
                                            <table class="table table-bordered no-thead">
                                                <colgroup>
                                                    <col style="width: 5%" />
                                                    <col style="width: auto" />
                                                </colgroup>
                                                <tbody>
                                                <tr>
                                                    <td class="center">
                                                        <input type="radio" id="iframe_resize_1" name="iframe_resize" value="1" <?php echo (((isset($PROCESSED["iframe_resize"])) && ((int) $PROCESSED["iframe_resize"]) == 1) ? " checked" : ""); ?> />
                                                    </td>
                                                    <td>
                                                        <label for="iframe_resize_1" class="content-small">Use iframe javascript resizer method</label>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="center">
                                                        <input type="radio" id="iframe_resize_0" name="iframe_resize" value="0" <?php echo (((isset($PROCESSED["iframe_resize"])) && ((int) $PROCESSED["iframe_resize"]) == 0) ? " checked" : ""); ?> />
                                                    </td>
                                                    <td>
                                                        <label for="iframe_resize_0" class="content-small"> No iframe resizing.</label>
                                                    </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                       <?php if (strtolower($ENTRADA_USER->getActiveGroup()) == "medtech") { ?>
                                        <tr class="dev-notes-row" id="iframe-dev-notes">
                                           <td colspan="2">
                                           </td>
                                           <td class="alert alert-info dev-notes">
                                            <h3>Iframe Javascript Resizer Developer Instructions</h3>
                                              <a href="<?php echo ENTRADA_RELATIVE . "/javascript/iframeResizer.contentWindow.min.js";?>">iframeResizer.contentWindow.min.js</a><br/>
                                              for documentation go here: <a href="https://github.com/davidjbradshaw/iframe-resizer">https://github.com/davidjbradshaw/iframe-resizer</a>
                                           </td>
                                        </tr>
                                       <?php } ?>

                                    <tr>
                                        <td colspan="2">
                                            <label for="session_variables" class="form-nrequired">
                                               Session Variables
                                               <?php if (strtolower($ENTRADA_USER->getActiveGroup()) == "medtech") { ?>
                                               <br/><span class="dev-notes-link" id="variables-notes-link">(Show Developer Notes)</span>
                                               <?php } ?>
                                           </label>
                                        </td>
                                        <td>
                                            <table class="table table-bordered no-thead">
                                                <colgroup>
                                                    <col style="width: 5%" />
                                                    <col style="width: auto" />
                                                </colgroup>
                                                <tbody>
                                                <tr>
                                                    <td class="center">
                                                        <input type="radio" id="session_var_0" name="session_variables" value="0"<?php echo (((isset($PROCESSED["session_variables"])) && ((int) $PROCESSED["session_variables"]) == 0) ? " checked" : ""); ?> />
                                                    </td>
                                                    <td>
                                                        <label for="session_var_0" class="content-small">No variables passed.</label>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="center">
                                                        <input type="radio" id="session_var_1" name="session_variables" value="1" <?php echo (((isset($PROCESSED["session_variables"])) && ((int) $PROCESSED["session_variables"]) == 1) ? " checked" : ""); ?> />
                                                    </td>
                                                    <td>
                                                        <label for="session_var_1" class="content-small">Pass variables.</label>
                                                    </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                       <?php if (strtolower($ENTRADA_USER->getActiveGroup()) == "medtech") { ?>
                                        <tr class="dev-notes-row" id="variables-dev-notes">
                                           <td colspan="2">
                                           </td>
                                           <td class="alert alert-info dev-notes">
                                               <h3>Session Variables Developer Instructions</h3>
                                             Passes the Username, Course ID, and Community ID through $_POST when opened in a new page.
                                           </td>
                                        </tr>
                                       <?php } ?>


                                    <tr>
                                        <td colspan="2">
                                            <label for="student_hidden" class="form-nrequired">Would you like to hide this link from students?</label>
                                        </td>
                                        <td>
                                            <table class="table table-bordered no-thead">
                                                <colgroup>
                                                    <col style="width: 5%" />
                                                    <col style="width: auto" />
                                                </colgroup>
                                                <tbody>
                                                <tr>
                                                    <td class="center">
                                                        <input type="radio" id="student_hidden_0" name="student_hidden" value="0" <?php echo (((isset($PROCESSED["student_hidden"])) && ((int) $PROCESSED["student_hidden"]) == 0) ? " checked" : ""); ?> />
                                                    </td>
                                                    <td>
                                                        <label for="student_hidden_0" class="content-small">Allow students to view this link.</label>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="center">
                                                        <input type="radio" id="student_hidden_1" name="student_hidden" value="1" <?php echo (((isset($PROCESSED["student_hidden"])) && ((int) $PROCESSED["student_hidden"]) == 1) ? " checked" : ""); ?> />
                                                    </td>
                                                    <td>
                                                        <label for="student_hidden_1" class="content-small">Hide this link from students.</label>
                                                    </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="3">
                                            <h2>Link Permissions</h2>
                                        </td>
                                    </tr>

                                <?php if ($isCommunityCourse) { ?>
                                <tr>
                                    <td colspan="2" style="vertical-align: top !important">
                                        <label for="permission_level" class="form-required">Permission Level: </label>
                                    </td>
                                    <td style="vertical-align: top">
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
                                <?php if ($COMMUNITY_ADMIN) { ?>
                                <tr class="file-permissions">
                                    <td colspan="3"><h3>HTML Document Permissions</h3></td>
                                </tr>
                                <tr class="file-permissions">
                                    <td colspan="3">
                                        <table class="table table-striped table-bordered table-community-centered">
                                            <colgroup>
                                                <col style="width: 50%" />
                                                <col style="width: 50%" />
                                            </colgroup>
                                            <thead>
                                            <tr>
                                                <td>View HTML Document</td>
                                                <td style="border-left: none">&nbsp;</td>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <tr>
                                            <td class="on">
                                                <label for="read" class="content-small">
                                                    <input type="checkbox" id="read" name="read" value="read"<?php echo (isset($permission_db['read']) && ($permission_db['read'] == 1)) ? " checked=\"checked\"" : ""; ?> />
                                                    Read
                                                </label>
                                            </td>
                                            <td>
                                                <label for="update" class="content-small">
                                                    <input type="checkbox" id="update" name="update" value="update"<?php echo (isset($permission_db['update']) && ($permission_db['update'] == 1)) ? " checked=\"checked\"" : ""; ?> />
                                                    Update
                                                </label>
                                            </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            <?php } ?>
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

                                    echo "<h4>$course_code: $course_name</h4>";
                                    ?>
                                    <table class="table table-striped table-bordered table-community-centered-list">
                                        <colgroup>
                                            <col style="width: 40%" />
                                            <col style="width: 30%" />
                                            <col style="width: 30%" />
                                        </colgroup>
                                        <thead>
                                        <tr>
                                            <td>Group</td>
                                            <td style="border-left: none">View Link</td>
                                            <td style="border-left: none">Upload New Version</td>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        foreach ($course_groups as $course_group) {
                                            $query = "SELECT `create`, `read`, `update`, `delete`
                                                                  FROM `community_acl_groups`
                                                                  WHERE `cgroup_id` = ".$db->qstr($course_group['cgroup_id'])."
                                                                  AND `resource_value` = ".$db->qstr($RECORD_ID)."
                                                                  AND `resource_type` = 'communitylink'";
                                            $community_course_perms = $db->GetRow($query);
                                            ?>
                                            <tr>
                                                <td class="left"><strong><?php echo $course_group['group_name']; ?></strong></td>
                                                <td class="on"><input type="checkbox" id="<?php echo $course_group['cgroup_id']; ?>_read" name="<?php echo $course_group['cgroup_id']; ?>[]" value="read"<?php echo (isset($community_course_perms['read']) && $community_course_perms['read'] == 1 ? " checked=\"checked\"" : ""); ?> /></td>
                                                <td><input type="checkbox" id="<?php echo $course_group['cgroup_id']; ?>_update" name="<?php echo $course_group['cgroup_id']; ?>[]" value="update" <?php echo (isset($community_course_perms['update']) && $community_course_perms['update'] == 1 ? " checked=\"checked\"" : ""); ?> /></td>
                                            </tr>
                                        <?php
                                        }
                                        ?>
                                        </tbody>
                                    </table>
                                <?php
                                }
                                ?>

                                <?php if (!(int) $community_details["community_registration"]) { ?>
                                    <h4>Non-members</h4>
                                    <table class="table table-striped table-bordered table-community-centered-list">
                                        <colgroup>
                                            <col style="width: 40%" />
                                            <col style="width: 30%" />
                                            <col style="width: 30%" />
                                        </colgroup>
                                        <thead>
                                        <tr>
                                            <td>Group</td>
                                            <td style="border-left: none">View Link</td>
                                            <td style="border-left: none">Upload New Version</td>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <td class="left"><strong>Browsing Non-Members</strong></td>
                                            <td class="on"><input type="checkbox" id="allow_troll_read" name="allow_troll_read" value="1"<?php echo (((!isset($PROCESSED["allow_troll_read"])) || ((isset($PROCESSED["allow_troll_read"])) && ($PROCESSED["allow_troll_read"] == 1))) ? " checked=\"checked\"" : ""); ?> /></td>
                                            <td><input type="checkbox" id="allow_troll_revision" name="allow_troll_revision" value="1"<?php echo (((isset($PROCESSED["allow_troll_revision"])) && ($PROCESSED["allow_troll_revision"] == 1)) ? " checked=\"checked\"" : ""); ?> /></td>
                                        </tr>
                                        </tbody>
                                    </table>
                                <?php } ?>
                                </td>
                            </tr>

                            <?php } else { ?>
                                <tr>
                                    <td colspan="3">
                                        <table class="table table-striped table-bordered table-community-centered-list">
                                            <colgroup>
                                                <col style="width: 50%" />
                                                <col style="width: 50%" />
                                            </colgroup>
                                            <thead>
                                            <tr>
                                                <td>Group</td>
                                                <td>View HTML Document</td>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <tr>
                                                <td class="left"><strong>Community Administrators</strong></td>
                                                <td class="on"><input type="checkbox" id="allow_admin_read" name="allow_admin_read" value="1" checked="checked" onclick="this.checked = true" /></td>
                                            </tr>
                                            <tr>
                                                <td class="left"><strong>Community Members</strong></td>
                                                <td class="on"><input type="checkbox" id="allow_member_read" name="allow_member_read" value="1"<?php echo (((!isset($PROCESSED["allow_member_read"])) || ((isset($PROCESSED["allow_member_read"])) && ($PROCESSED["allow_member_read"] == 1))) ? " checked=\"checked\"" : ""); ?> /></td>
                                            </tr>
                                            <?php if (!(int) $community_details["community_registration"]) : ?>
                                                <tr>
                                                    <td class="left"><strong>Browsing Non-Members</strong></td>
                                                    <td class="on"><input type="checkbox" id="allow_troll_read" name="allow_troll_read" value="1"<?php echo (((!isset($PROCESSED["allow_troll_read"])) || ((isset($PROCESSED["allow_troll_read"])) && ($PROCESSED["allow_troll_read"] == 1))) ? " checked=\"checked\"" : ""); ?> /></td>
                                                </tr>
                                            <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            <?php } ?>
                                <tr>
                                    <td colspan="3"><h2>Time Release Options</h2></td>
                                </tr>
                                <tr>
                                    <td colspan="3">
                                        <table class="date-time">
                                            <?php
                                            echo generate_calendars("release", "", true, true, ((isset($PROCESSED["release_date"])) ? $PROCESSED["release_date"] : time()), true, false, ((isset($PROCESSED["release_until"])) ? $PROCESSED["release_until"] : 0));
                                            ?>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3" style="padding-top: 15px; text-align: right">
                                        <input type="submit" class="btn btn-primary" value="<?php echo $translate->_("global_button_save"); ?>" />
                                    </td>
                                </tr>
                        </tbody>
                        </table>
                    </div>
                </form>
						<?php
					break;
				}
			} else {
				if ($ERROR) {
					echo display_error();
				}
				if ($NOTICE) {
					echo display_notice();
				}
			}
		} else {
			$NOTICE++;
			$NOTICESTR[] = "The link that you are trying to edit was deactivated <strong>".date(DEFAULT_DATE_FORMAT, $file_record["updated_date"])."</strong> by <strong>".html_encode(get_account_data("firstlast", $file_record["updated_by"]))."</strong>.<br /><br />If there has been a mistake or you have questions relating to this issue please contact the MEdTech Unit directly.";

			echo display_notice();

			application_log("error", "The link record id [".$RECORD_ID."] is deactivated; however, ".$_SESSION["details"]["firstname"]." ".$_SESSION["details"]["lastname"]." [".$ENTRADA_USER->getID()."] has tried to edit it.");
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "The link id that you have provided does not exist in the system. Please provide a valid record id to proceed.";

		echo display_error();

		application_log("error", "The provided link id was invalid [".$RECORD_ID."] (Edit Link).");
	}
} else {
	$ERROR++;
	$ERRORSTR[] = "Please provide a valid link id to proceed.";

	echo display_error();

	application_log("error", "No link id was provided to edit. (Edit Link)");
}
