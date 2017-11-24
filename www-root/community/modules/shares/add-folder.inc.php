<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to create folders within a specific page of a community. This action is
 * available only to community administrators.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
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

echo "<h1>Add Shared Folder</h1>\n";

Models_Community_Share::getParentsBreadCrumbs($RECORD_ID);
$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=add-folder", "title" => "Add Shared Folder");
$parent_folder_id = $RECORD_ID;

$queryParentFolder = "SELECT `folder_title`, `cshare_id` FROM `community_shares` WHERE `cshare_id` = '" . $parent_folder_id . "'";
$parent_folder = $db->GetRow($queryParentFolder);

//Check to see if the community is connected to a course
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
		 * Required field "title" / Folder Title.
		 */
		if ((isset($_POST["folder_title"])) && ($title = clean_input($_POST["folder_title"], array("notags", "trim")))) {
			$PROCESSED["folder_title"] = $title;
		} else {
			$ERROR++;
			$ERRORSTR[] = "The <strong>Folder Title</strong> field is required.";
		}

		/**
		 * Non-Required field "description" / Folder Description.
		 */
		if ((isset($_POST["folder_description"])) && ($description = clean_input($_POST["folder_description"], array("notags", "trim")))) {
			$PROCESSED["folder_description"] = $description;
		} else {
			$PROCESSED["folder_description"] = "";
		}

		/**
		 * Non-Required field "folder_icon" / Folder Icon.
		 */
		if ((isset($_POST["folder_icon"])) && ($folder_icon = clean_input($_POST["folder_icon"], "int")) && ($folder_icon > 0) && ($folder_icon <= 6)) {
			$PROCESSED["folder_icon"] = $folder_icon;
		} else {
			$PROCESSED["folder_icon"] = 1;
		}

		/**
         * Required field "permission_acl_style" for community courses
         */
        if ($isCommunityCourse) {
            //Checks if the ACL assertion is set
            if (!isset($_POST['permission_acl_style'])) {
                $ERROR++;
                $ERRORSTR[] = "The <strong>Permission Level</strong> field is required.";
            }
        }
        
        /**
		 * Permission checking for member access.
		 */
		if ((isset($_POST["allow_member_read"])) && (clean_input($_POST["allow_member_read"], array("int")) == 1)) {
			$PROCESSED["allow_member_read"]		= 1;
		} else {
			$PROCESSED["allow_member_read"]		= 0;
		}
		if ((isset($_POST["allow_member_upload"])) && (clean_input($_POST["allow_member_upload"], array("int")) == 1)) {
			$PROCESSED["allow_member_upload"]	= 1;
		} else {
			$PROCESSED["allow_member_upload"]	= 0;
		}
		if ((isset($_POST["allow_member_comment"])) && (clean_input($_POST["allow_member_comment"], array("int")) == 1)) {
			$PROCESSED["allow_member_comment"]	= 1;
		} else {
			$PROCESSED["allow_member_comment"]	= 0;
		}

		/**
		 * Permission checking for troll access.
		 * This can only be done if the community_registration is set to "Open Community"
		 */
		if (!(int) $community_details["community_registration"]) {
			if ((isset($_POST["allow_troll_read"])) && (clean_input($_POST["allow_troll_read"], array("int")) == 1)) {
				$PROCESSED["allow_troll_read"]		= 1;
			} else {
				$PROCESSED["allow_troll_read"]		= 0;
			}
			if ((isset($_POST["allow_troll_upload"])) && (clean_input($_POST["allow_troll_upload"], array("int")) == 1)) {
				$PROCESSED["allow_troll_upload"]	= 1;
			} else {
				$PROCESSED["allow_troll_upload"]	= 0;
			}
			if ((isset($_POST["allow_troll_comment"])) && (clean_input($_POST["allow_troll_comment"], array("int")) == 1)) {
				$PROCESSED["allow_troll_comment"]	= 1;
			} else {
				$PROCESSED["allow_troll_comment"]	= 0;
			}
		} else {
			$PROCESSED["allow_troll_read"]			= 0;
			$PROCESSED["allow_troll_upload"]		= 0;
			$PROCESSED["allow_troll_comment"]		= 0;
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
			$PROCESSED["allow_public_upload"]	= 0;
			$PROCESSED["allow_public_comment"]	= 0;
		} else {
			$PROCESSED["allow_public_read"]		= 0;
			$PROCESSED["allow_public_upload"]	= 0;
			$PROCESSED["allow_public_comment"]	= 0;
		}

        /*
         * Sets the parent folder
         * 
         */
        if(isset($_POST["parent_folder_id"])) {
            $PROCESSED["parent_folder_id"] = $_POST["parent_folder_id"];
        }      
        
        
        /**
         * Non-Required field "student_hidden" / View Method.
         */
        if ((isset($_POST["student_hidden"])) && clean_input($_POST["student_hidden"], array("int")) == 1) {
            $PROCESSED["student_hidden"] = 1;
        } else {
            $PROCESSED["student_hidden"] = 0;
        }        
        
		/**
		 * Email Notificaions.
		 */
		if(isset($_POST["admin_notify"]) || isset($_POST["member_notify"])) {
			$PROCESSED["admin_notifications"] = $_POST["admin_notify"] + $_POST["member_notify"];
		} else {
			$PROCESSED["admin_notifications"] = 0;
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
			$PROCESSED["community_id"]		= $COMMUNITY_ID;
			$PROCESSED["proxy_id"]			= $ENTRADA_USER->getActiveId();
			$PROCESSED["folder_active"]		= 1;
			$PROCESSED["updated_date"]		= time();
			$PROCESSED["updated_by"]		= $ENTRADA_USER->getID();
			$PROCESSED["cpage_id"]			= $PAGE_ID;

			if ($db->AutoExecute("community_shares", $PROCESSED, "INSERT")) {
				if ($FOLDER_ID = $db->Insert_Id()) {
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
                            
                            $db->AutoExecute("community_acl_groups", array("cgroup_id" => $community_course_group['cgroup_id'], "resource_type" => "communityfolder", "resource_value" => $FOLDER_ID, "create"=>$PROCESSED[$community_course_group['cgroup_id']]['create'], "read"=>$PROCESSED[$community_course_group['cgroup_id']]['read'], "update"=>$PROCESSED[$community_course_group['cgroup_id']]['update'], "delete"=>$PROCESSED[$community_course_group['cgroup_id']]['delete']), "INSERT");
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
                            'update' => 1,
                            'delete' => 0,
                            'assertion' => $_POST['permission_acl_style']
                        );
                    }
                    $results = $db->AutoExecute("`community_acl`", array(
                            "resource_type" => "communityfolder",
                            "resource_value" => $FOLDER_ID,
                            "create" => $update_perm['create'],
                            "read" => $update_perm['read'],
                            "update" => $update_perm['update'],
                            "delete" => $update_perm['delete'],
                            "assertion" => $update_perm['assertion']
                        ),
                        "INSERT"
                    );
                    if ($results === false) {
                        $ERROR++;
                        $ERRORSTR[] = "Error updating the community ACL.";
                    }
                    
                    if (!$ERROR) {
                        Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("You have successfully added <strong>%s</strong>."), $PROCESSED["folder_title"]), "success", $MODULE);
                        add_statistic("community:".$COMMUNITY_ID.":shares", "folder_add", "cshare_id", $FOLDER_ID);
                        communities_log_history($COMMUNITY_ID, $PAGE_ID, $FOLDER_ID, "community_history_add_share", 1);

                        $url = COMMUNITY_URL . $COMMUNITY_URL . ":" . $PAGE_URL;
                        header("Location: " . $url);
                        exit;
				    }
			    }
			}

			if (!$SUCCESS) {
				$ERROR++;
				$ERRORSTR[] = "There was a problem inserting this folder into the system. The MEdTech Unit was informed of this error; please try again later.";

				application_log("error", "There was an error inserting a shared folder. Database said: ".$db->ErrorMsg());
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

// Page Display
switch($STEP) {
	case 1 :
	default :
		if ((!isset($PROCESSED["folder_icon"])) || (!(int) $PROCESSED["folder_icon"]) || ($PROCESSED["folder_icon"] < 1) || ($PROCESSED["folder_icon"] > 6) ) {
			$PROCESSED["folder_icon"] = 1;
		}

		$ONLOAD[] = "updateFolderIcon('".$PROCESSED["folder_icon"]."')";

		if ($ERROR) {
			echo display_error();
		}
		if ($NOTICE) {
			echo display_notice();
		}
		?>
		<script type="text/javascript">
		var folder_icon_number	= '<?php echo $PROCESSED["folder_icon"]; ?>';
		</script>
		<form action="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=add-folder&amp;step=2" method="post">
        	<table class="community-add-table" summary="Add Shared Folder">
				<colgroup>
					<col style="width: 3%" />
					<col style="width: 20%" />
					<col style="width: 77%" />
				</colgroup>
				<tbody>
					<tr>
						<td colspan="3">
							<h2>Folder Details</h2>
               			</td>
					</tr>
					<tr>
                		<td colspan="2">
							<label for="folder_title" class="form-required">Folder Title</label>
						</td>
						<td>
                    		<input type="text" id="folder_title" name="folder_title" value="<?php echo ((isset($PROCESSED["folder_title"])) ? html_encode($PROCESSED["folder_title"]) : ""); ?>" maxlength="84" style="width: 95%" />
						</td>
					</tr>
					<tr>
                		<td colspan="2">
							<label for="folder_description" class="form-nrequired">Folder Description</label>
						</td>
						<td>
                    		<textarea id="folder_description" name="folder_description" style="width: 95%; height: 60px" cols="50" rows="5"><?php echo ((isset($PROCESSED["folder_description"])) ? html_encode($PROCESSED["folder_description"]) : ""); ?></textarea>
						</td>
					</tr>
					<tr>
                		<td colspan="3">
                    		&nbsp;
						</td>
					</tr>
					<tr>
                		<td colspan="2">
							<label for="folder_icon" class="form-nrequired">Folder Icon</label>
						</td>
						<td>
							<input type="hidden" id="folder_icon" name="folder_icon" value="<?php echo $PROCESSED["folder_icon"]; ?>" />
							<div id="color-icon-list">
								<img id="folder-icon-1" src="<?php echo COMMUNITY_URL."/templates/".$COMMUNITY_TEMPLATE."/images/list-folder-1.gif"; ?>" width="32" height="32" alt="Folder Icon 1" title="Folder Icon 1" onclick="updateFolderIcon('1')" />
								<img id="folder-icon-2" src="<?php echo COMMUNITY_URL."/templates/".$COMMUNITY_TEMPLATE."/images/list-folder-2.gif"; ?>" width="32" height="32" alt="Folder Icon 2" title="Folder Icon 2" onclick="updateFolderIcon('2')" />
								<img id="folder-icon-3" src="<?php echo COMMUNITY_URL."/templates/".$COMMUNITY_TEMPLATE."/images/list-folder-3.gif"; ?>" width="32" height="32" alt="Folder Icon 3" title="Folder Icon 3" onclick="updateFolderIcon('3')" />
								<img id="folder-icon-4" src="<?php echo COMMUNITY_URL."/templates/".$COMMUNITY_TEMPLATE."/images/list-folder-4.gif"; ?>" width="32" height="32" alt="Folder Icon 4" title="Folder Icon 4" onclick="updateFolderIcon('4')" />
								<img id="folder-icon-5" src="<?php echo COMMUNITY_URL."/templates/".$COMMUNITY_TEMPLATE."/images/list-folder-5.gif"; ?>" width="32" height="32" alt="Folder Icon 5" title="Folder Icon 5" onclick="updateFolderIcon('5')" />
								<img id="folder-icon-6" src="<?php echo COMMUNITY_URL."/templates/".$COMMUNITY_TEMPLATE."/images/list-folder-6.gif"; ?>" width="32" height="32" alt="Folder Icon 6" title="Folder Icon 6" onclick="updateFolderIcon('6')" />
							</div>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<label for="parent_folder" class="form-nrequired">Parent Folder</label>
						</td>
						<td>
							<select name="parent_folder_id" id="parent_folder_id" style="width: 95%">
								<?php
								echo Models_Community_Share::selectParentFolderOptions(0, $parent_folder_id, $PAGE_ID, "add");
								?>
							</select>
						</td>
					</tr>
					<tr>
						<td colspan="3">
							<h2>Folder Permissions</h2>
						</td>
					</tr>
					<?php
					if ($isCommunityCourse) {
					?>
					<tr>
						<td colspan="2">
							<label for="permission_level" class="form-required">Permission Level: </label>
						</td>
						<td>
							<table class="table table-bordered no-thead">
								<colgroup>
									<col style="width: 5%" />
									<col style="width: auto" />
								</colgroup>
								<tr>
									<td>
										<input id="community-all-checkbox" class="permission-type-checkbox" type="radio" name="permission_acl_style" value="CourseCommunityEnrollment" checked="checked" />
									</td>
									<td>
										<label for="community-all-checkbox" class="content-small">All Community Members</label>
									</td>
								</tr>
							<tr>
									<td>
										<input id="course-group-checkbox" class="permission-type-checkbox" type="radio" name="permission_acl_style" value="CourseGroupMember" />
									</td>
									<td>
										<label for="course-group-checkbox" class="content-small">Course Groups</label>
									</td>
								</tr>
							</table>
						</td>
					</tr>
            		<?php if ($COMMUNITY_ADMIN) { ?>
					<tr class="folder-permissions">
						<td colspan="3">
							<h3>Folder Permissions</h3>
						</td>
					</tr>
					<tr class="folder-permissions">
						<td colspan="3">
							<table class="table table-striped table-bordered">
								<colgroup>
									<col style="width: 33%" />
									<col style="width: 33%" />
									<col style="width: 34%" />
								</colgroup>
								<thead>
									<tr>
										<td>View Folder</td>
										<td>Upload Files/Links</td>
										<td>Allow Comments</td>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td class="on">
											<input type="checkbox" id="read" name="read" value="read" checked="checked" />
										</td>
										<td>
											<input type="checkbox" id="create" name="create" value="create" />
										</td>
										<td class="on">
											<input type="checkbox" id="update" name="update" value="update" />
										</td>
									</tr>
								</tbody>
							</table>
						</td>
					</tr>
            		<?php } ?>
					<tr class="course-group-permissions">
						<td colspan="3"><h3>Course Group Permissions</h3></td>
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
									<col style="width: 20%" />
									<col style="width: 20%" />
									<col style="width: 20%" />
								</colgroup>
								<thead>
									<tr>
										<td>Group</td>
										<td>Browse Folder</td>
										<td>Upload Files</td>
										<td>Allow Comments</td>
									</tr>
								</thead>
								<tbody>
							<?php
							foreach ($course_groups as $course_group) {
								?>
								<tr>
									<td class="left"><strong><?php echo $course_group['group_name']; ?></strong></td>
									<td class="on"><input type="checkbox" id="<?php echo $course_group['cgroup_id']; ?>_read" name="<?php echo $course_group['cgroup_id']; ?>[]" value="read" /></td>
									<td><input type="checkbox" id="<?php echo $course_group['cgroup_id']; ?>_create" name="<?php echo $course_group['cgroup_id']; ?>[]" value="create" /></td>
									<td class="on"><input type="checkbox" id="<?php echo $course_group['cgroup_id']; ?>_update" name="<?php echo $course_group['cgroup_id']; ?>[]" value="update" /></td>
								</tr>
								<?php
							}
							?>
							</tbody>
							</table>
							<?php
						}
						?>

						<?php if (!(int) $community_details["community_registration"] || !(int) $community_details["community_protected"]) { ?>
							<h4>Non-members</h4>
							<table class="table table-striped table-bordered table-community-centered">
							<colgroup>
								<col style="width: 40%" />
								<col style="width: 20%" />
								<col style="width: 20%" />
								<col style="width: 20%" />
							</colgroup>
							<thead>
								<tr>
									<td>Group</td>
									<td>Browse Folder</td>
									<td>Upload Files</td>
									<td>Allow Comments</td>
								</tr>
							</thead>
							<tbody>
							<?php if (!(int) $community_details["community_registration"]) { ?>
								<tr>
									<td class="left">
										<strong>Browsing Non-Members</strong>
									</td>
									<td class="on">
										<input type="checkbox" id="allow_troll_read" name="allow_troll_read" value="1"<?php echo (((!isset($PROCESSED["allow_troll_read"])) || ((isset($PROCESSED["allow_troll_read"])) && ($PROCESSED["allow_troll_read"] == 1))) ? " checked=\"checked\"" : ""); ?> />
									</td>
									<td>
										<input type="checkbox" id="allow_troll_upload" name="allow_troll_upload" value="1"<?php echo (((isset($PROCESSED["allow_troll_upload"])) && ($PROCESSED["allow_troll_upload"] == 1)) ? " checked=\"checked\"" : ""); ?> />
									</td>
									<td class="on">
										<input type="checkbox" id="allow_troll_comment" name="allow_troll_comment" value="1"<?php echo (((isset($PROCESSED["allow_troll_comment"])) && ($PROCESSED["allow_troll_comment"] == 1)) ? " checked=\"checked\"" : ""); ?> />
									</td>
								</tr>
							<?php } ?>
							<?php if (!(int) $community_details["community_protected"]) { ?>
							<tr>
								<td class="left">
									<strong>Non-Authenticated / Public Users</strong>
								</td>
								<td class="on">
									<input type="checkbox" id="allow_public_read" name="allow_public_read" value="1"<?php echo (((isset($PROCESSED["allow_public_read"])) && ($PROCESSED["allow_public_read"] == 1)) ? " checked=\"checked\"" : ""); ?> />
								</td>
								<td>
									<input type="checkbox" id="allow_public_upload" name="allow_public_upload" value="0" onclick="noPublic(this)" />
								</td>
								<td class="on">
									<input type="checkbox" id="allow_public_comment" name="allow_public_comment" value="0" onclick="noPublic(this)" />
								</td>
							</tr>
							<?php } ?>
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
									<col style="width: 40%" />
									<col style="width: 20%" />
									<col style="width: 20%" />
									<col style="width: 20%" />
								</colgroup>
								<thead>
									<tr>
										<td>Group</td>
										<td>Browse Folder</td>
										<td>Upload Files</td>
										<td>Allow Comments</td>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td class="left">
											<strong>Community Administrators</strong>
										</td>
										<td class="on">
											<input type="checkbox" id="allow_admin_read" name="allow_admin_read" value="1" checked="checked" onclick="this.checked = true" />
										</td>
										<td>
											<input type="checkbox" id="allow_admin_upload" name="allow_admin_upload" value="1" checked="checked" onclick="this.checked = true" />
										</td>
										<td class="on">
											<input type="checkbox" id="allow_admin_comment" name="allow_admin_comment" value="1" checked="checked" onclick="this.checked = true" />
										</td>
									</tr>
									<tr>
										<td class="left">
											<strong>Community Members</strong>
										</td>
										<td class="on">
											<input type="checkbox" id="allow_member_read" name="allow_member_read" value="1"<?php echo (((!isset($PROCESSED["allow_member_read"])) || ((isset($PROCESSED["allow_member_read"])) && ($PROCESSED["allow_member_read"] == 1))) ? " checked=\"checked\"" : ""); ?> />
										</td>
										<td>
											<input type="checkbox" id="allow_member_upload" name="allow_member_upload" value="1"<?php echo ((((isset($PROCESSED["allow_member_upload"])) && ($PROCESSED["allow_member_upload"] == 1))) ? " checked=\"checked\"" : ""); ?> />
										</td>
										<td class="on">
											<input type="checkbox" id="allow_member_comment" name="allow_member_comment" value="1"<?php echo ((((isset($PROCESSED["allow_member_comment"])) && ($PROCESSED["allow_member_comment"] == 1))) ? " checked=\"checked\"" : ""); ?> />
										</td>
									</tr>
									<?php if (!(int) $community_details["community_registration"]) {  ?>
									<tr>
										<td class="left">
											<strong>Browsing Non-Members</strong>
										</td>
										<td class="on">
											<input type="checkbox" id="allow_troll_read" name="allow_troll_read" value="1"<?php echo (((!isset($PROCESSED["allow_troll_read"])) || ((isset($PROCESSED["allow_troll_read"])) && ($PROCESSED["allow_troll_read"] == 1))) ? " checked=\"checked\"" : ""); ?> />
										</td>
										<td>
											<input type="checkbox" id="allow_troll_upload" name="allow_troll_upload" value="1"<?php echo (((isset($PROCESSED["allow_troll_upload"])) && ($PROCESSED["allow_troll_upload"] == 1)) ? " checked=\"checked\"" : ""); ?> />
										</td>
										<td class="on">
											<input type="checkbox" id="allow_troll_comment" name="allow_troll_comment" value="1"<?php echo (((isset($PROCESSED["allow_troll_comment"])) && ($PROCESSED["allow_troll_comment"] == 1)) ? " checked=\"checked\"" : ""); ?> />
										</td>
									</tr>
									<?php
									}
									if (!(int) $community_details["community_protected"]) { ?>
									<tr>
										<td class="left">
											<strong>Non-Authenticated / Public Users</strong>
										</td>
										<td class="on">
											<input type="checkbox" id="allow_public_read" name="allow_public_read" value="1"<?php echo (((isset($PROCESSED["allow_public_read"])) && ($PROCESSED["allow_public_read"] == 1)) ? " checked=\"checked\"" : ""); ?> />
										</td>
										<td>
											<input type="checkbox" id="allow_public_upload" name="allow_public_upload" value="0" onclick="noPublic(this)" />
										</td>
										<td class="on">
											<input type="checkbox" id="allow_public_comment" name="allow_public_comment" value="0" onclick="noPublic(this)" />
										</td>
									</tr>
									<?php } ?>
								</tbody>
							</table>
						</td>
					</tr>
            		<?php } ?>
					<tr>
						<td colspan="3">
							<h2>Hide Folder</h2>
						</td>
					</tr>
					<tr>
						<td colspan="2" style="vertical-align: top;">
							<label for="student_hidden" class="form-nrequired">Would you like to hide this folder from students?</label>
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
											<input type="radio" id="student_hidden_0" name="student_hidden" value="0" style="vertical-align: middle"<?php echo (((!isset($PROCESSED["student_hidden"])) || ((isset($PROCESSED["student_hidden"])) && (!(int) $PROCESSED["student_hidden"]))) ? " checked=\"checked\"" : ""); ?> />
										</td>
										<td>
											<label for="student_hidden_0" class="content-small">Allow students to view this folder.</label>
										</td>
									</tr>
									<tr>
										<td class="center">
											<input type="radio" id="student_hidden_1" name="student_hidden" value="1" style="vertical-align: middle"<?php echo (((isset($PROCESSED["student_hidden"])) && ((int) $PROCESSED["student_hidden"])) ? " checked=\"checked\"" : ""); ?> />
										</td>
										<td>
											<label for="student_hidden_1" class="content-small">Hide this folder from students.</label>
										</td>
									</tr>
								</tbody>
							</table>
						</td>
					</tr>

				</tbody>
			</table>

			<table class="date-time">
				<tr>
					<td colspan="3">
						<h2>Time Release Options</h2>
					</td>
				</tr>
				<tr>
					<td colspan="3">
						<?php echo generate_calendars("release", "", true, true, ((isset($PROCESSED["release_date"])) ? $PROCESSED["release_date"] : time()), true, false, ((isset($PROCESSED["release_until"])) ? $PROCESSED["release_until"] : 0)); ?>
					</td>
				</tr>
			</table>

			<div class="space-above">
				<input type="button" class="btn button-right pull-left" value="<?php echo $translate->_("global_button_cancel"); ?>" onclick="window.location='<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-folder&id=".$RECORD_ID ?>'" />
				<input type="submit" class="btn btn-primary pull-right" value="Save" />
			</div>

		</form>
		<?php
	break;
}
?>
