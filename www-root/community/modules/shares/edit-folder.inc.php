<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to edit an existing folder with a page of a community. This action can
 * be used only by community administrators.
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

echo "<h1>Edit Shared Folder</h1>\n";

//Check if this community is connected to a course
$isCommunityCourse = Models_Community_Course::is_community_course($COMMUNITY_ID);

if ($RECORD_ID) {
	$query			= "SELECT * FROM `community_shares` WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `cpage_id` = ".$db->qstr($PAGE_ID)." AND `cshare_id` = ".$db->qstr($RECORD_ID);
	$folder_record	= $db->GetRow($query);
	if ($folder_record) {
		if ((int) $folder_record["folder_active"]) {

			Models_Community_Share::getParentsBreadCrumbs($RECORD_ID);
//            Models_Community_Share::getParentsBreadCrumbs($folder_record["parent_folder_id"]);
			$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-folder&id=".$RECORD_ID, "title" => limit_chars($folder_record["folder_title"], 32));
			$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=edit-folder&amp;id=".$RECORD_ID, "title" => "Edit Shared Folder");

            //Selects the current parent folder name and id
            if (isset($folder_record["parent_folder_id"])) {
                $parent_folder_id = $folder_record["parent_folder_id"];
            }

            $queryParentFolder = "SELECT `folder_title`, `cshare_id` FROM `community_shares` WHERE `cshare_id` = '".$folder_record["parent_folder_id"]."'";
            $parent_folder = $db->GetRow($queryParentFolder);
			
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

                $query = "  SELECT `id`, `create`, `read`, `update`, `delete`, `assertion`
                            FROM `community_acl`
                            WHERE `resource_type` = 'communityfolder'
                            AND `resource_value` = " . $db->qstr($RECORD_ID);
                
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
						if (!isset($_POST["permission_acl_style"])) {
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
					if(isset($_POST["admin_notifications"])) {
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

						if ($db->AutoExecute("community_shares", $PROCESSED, "UPDATE", "`community_id` = ".$db->qstr($COMMUNITY_ID)." AND `cpage_id` = ".$db->qstr($PAGE_ID)." AND `cshare_id` = ".$db->qstr($RECORD_ID))) {
                            //Add course group permissions to community_acl_groups
                            if ($_POST['permission_acl_style'] === 'CourseGroupMember' && $community_course_groups && !empty($community_course_groups)) {
                                 foreach ($community_course_groups as $community_course_group){ 
                                    //Set the default value to '0'
                                    $PROCESSED[$community_course_group['cgroup_id']] = array("create" => 0, "read" => 0, "update" => 0, "delete" => 0);

                                    if ($_POST[$community_course_group['cgroup_id']]){    
                                        foreach ($_POST[$community_course_group['cgroup_id']] as $perms){
                                            //Update the value to '1' if it was submitted
                                            $PROCESSED[$community_course_group['cgroup_id']][clean_input($perms)] = 1;
                                        }
                                    }

                                    $query	= " SELECT COUNT(*) AS `total_rows`
                                                FROM `community_acl_groups`
                                                WHERE `cgroup_id` = ".$db->qstr($community_course_group['cgroup_id'])."
                                                AND `resource_type` = 'communityfolder'
                                                AND `resource_value` = ".$db->qstr($RECORD_ID);
                                    $record    = $db->GetRow($query);  

                                    if ($record['total_rows'] > 0) { 
                                        $db->AutoExecute("community_acl_groups", array("create"=>$PROCESSED[$community_course_group['cgroup_id']]['create'], "read"=>$PROCESSED[$community_course_group['cgroup_id']]['read'], "update"=>$PROCESSED[$community_course_group['cgroup_id']]['update'], "delete"=>$PROCESSED[$community_course_group['cgroup_id']]['delete']), "UPDATE", "`cgroup_id` = ".$db->qstr($community_course_group['cgroup_id'])." AND `resource_type` = 'communityfolder' AND `resource_value` = ".$db->qstr($RECORD_ID));
                                    } else {
                                        $db->AutoExecute("community_acl_groups", array("cgroup_id"=>$community_course_group['cgroup_id'], "resource_type"=>"communityfolder", "resource_value"=>$RECORD_ID,"create"=>$PROCESSED[$community_course_group['cgroup_id']]['create'], "read"=>$PROCESSED[$community_course_group['cgroup_id']]['read'], "update"=>$PROCESSED[$community_course_group['cgroup_id']]['update'], "delete"=>$PROCESSED[$community_course_group['cgroup_id']]['delete']), "INSERT");
                                    }
                                }
                            }

                            //If the user's role is 'admin', use the submitted form values.
                            if ($COMMUNITY_ADMIN){
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
                            $update_perm["resource_type"] = "communityfolder";
                            $update_perm["resource_value"] = $RECORD_ID;
                            
                            //If entry exists in community_acl, update, else insert.
                            if ($db->GetRow("SELECT * FROM `community_acl` WHERE `resource_type` = 'communityfolder' AND `resource_value` = ".$db->qstr($RECORD_ID))) {
                                $results = $db->AutoExecute("`community_acl`", $update_perm, "UPDATE",
                                    "`resource_type` = 'communityfolder' AND `resource_value` = ".$db->qstr($RECORD_ID)
                                );
                            } else {
                                $results = $db->AutoExecute("`community_acl`", $update_perm, "INSERT");
                            }
                            if ($results === false) {
                                $ERROR++;
                                $ERRORSTR[] = "Error updating the community ACL.";
                            }

                            if (!$ERROR) {
                                Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("You have successfully updated <strong>%s</strong>."), $PROCESSED["folder_title"]), "success", $MODULE);
                                add_statistic("community:".$COMMUNITY_ID.":shares", "folder_edit", "cshare_id", $RECORD_ID);
                                communities_log_history($COMMUNITY_ID, $PAGE_ID, $RECORD_ID, "community_history_edit_share", 1);

                                $url = COMMUNITY_URL . $COMMUNITY_URL . ":" . $PAGE_URL;
                                header("Location: " . $url);
                                exit;
                            }
						}

						if (!$SUCCESS) {
							$ERROR++;
							$ERRORSTR[] = "There was a problem updating this shared folder in the system. The MEdTech Unit was informed of this error; please try again later.";

							application_log("error", "There was an error updating a shared folder. Database said: ".$db->ErrorMsg());
						}
					}

					if ($ERROR) {
						$STEP = 1;
					}
				break;
				case 1 :
				default :
					$PROCESSED = $folder_record;
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
					var folder_icon_number = '<?php echo $PROCESSED["folder_icon"]; ?>';
					</script>
					<form action="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=edit-folder&amp;id=<?php echo $RECORD_ID; ?>&amp;step=2" method="post">
                    	<table class="community-add-table" summary="Edit Shared Folder">
							<colgroup>
								<col style="width: 3%" />
								<col style="width: 20%" />
								<col style="width: 77%" />
							</colgroup>
							<tfoot>
								<tr>
									<td colspan="3" style="padding-top: 15px; text-align: right">
										<input type="submit" class="btn btn-primary" value="Save" />
									</td>
								</tr>
							</tfoot>
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
									<td colspan="2"><label for="folder_icon" class="form-nrequired">Folder Icon</label></td>
									<td>
										<input type="hidden" id="folder_icon" name="folder_icon" value="<?php echo $PROCESSED["folder_icon"]; ?>" />
										<div id="folder-icon-list">
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
												echo Models_Community_Share::selectParentFolderOptions($folder_record["cshare_id"], $folder_record["parent_folder_id"], $PAGE_ID);
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
                                            			<input id="community-all-checkbox" class="permission-type-checkbox" type="radio" name="permission_acl_style" value="CourseCommunityEnrollment"<?php if ($permission_db['assertion'] == 'CourseCommunityEnrollment') { echo " checked='checked'"; } ?> />
													</td>
													<td>
                                            			<label for="community-all-checkbox" class="content-small">All Community Members</label>
													</td>
                                    			</tr>
												<tr>
													<td>
														<input id="course-group-checkbox" class="permission-type-checkbox" type="radio" name="permission_acl_style" value="CourseGroupMember"<?php if ($permission_db['assertion'] == 'CourseGroupMember') { echo " checked='checked'"; } ?> />
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
								<tr class="folder-permissions">
									<td colspan="3">
										<h3>Folder Permissions</h3>
									</td>
								</tr>
								<tr class="folder-permissions">
									<td colspan="3">
										<table class="table table-striped table-bordered table-community-centered">
											<colgroup>
												<col style="width: 33%" />
												<col style="width: 33%" />
												<col style="width: 34%" />
											</colgroup>
											<thead>
												<tr>
													<td>View Folder</td>
													<td style="border-left: none">Upload Files/Links</td>
													<td style="border-left: none">Allow Comments</td>
												</tr>
											</thead>
											<tbody>
												<tr>
													<td class="on">
														 <input type="checkbox" id="read" name="read" value="read"<?php echo (isset($permission_db['read']) && ($permission_db['read'] == 1)) ? " checked=\"checked\"" : ""; ?> />
													</td>
													<td>
														<input type="checkbox" id="create" name="create" value="create"<?php echo (isset($permission_db['create']) && ($permission_db['create'] == 1)) ? " checked=\"checked\"" : ""; ?> />
													</td>
													<td class="on">
														<input type="checkbox" id="update" name="update" value="update"<?php echo (isset($permission_db['update']) && ($permission_db['update'] == 1)) ? " checked=\"checked\"" : ""; ?> />
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
												<td style="border-left: none">Browse Folder</td>
												<td style="border-left: none">Upload Files</td>
												<td style="border-left: none">Allow Comments</td>
											</tr>
										</thead>
										<tbody>
										<?php

										foreach ($course_groups as $course_group) {
											$query = "SELECT `create`, `read`, `update`, `delete`
													  FROM `community_acl_groups`
													  WHERE `cgroup_id` = ".$db->qstr($course_group['cgroup_id'])."
													  AND `resource_value` = ".$db->qstr($RECORD_ID)."
													  AND `resource_type` = 'communityfolder'";
											$community_course_perms = $db->GetRow($query);
											?>
											<tr>
												<td class="left"><strong><?php echo $course_group['group_name']; ?></strong></td>
												<td class="on"><input type="checkbox" id="<?php echo $course_group['cgroup_id']; ?>_read" name="<?php echo $course_group['cgroup_id']; ?>[]" value="read"<?php echo (isset($community_course_perms['read']) && ($community_course_perms['read'] == 1)) ? " checked=\"checked\"" : ""; ?> /></td>
												<td><input type="checkbox" id="<?php echo $course_group['cgroup_id']; ?>_create" name="<?php echo $course_group['cgroup_id']; ?>[]" value="create"<?php echo (isset($community_course_perms['create']) && ($community_course_perms['create'] == 1)) ? " checked=\"checked\"" : ""; ?> /></td>
												<td class="on"><input type="checkbox" id="<?php echo $course_group['cgroup_id']; ?>_update" name="<?php echo $course_group['cgroup_id']; ?>[]" value="update"<?php echo (isset($community_course_perms['update']) && ($community_course_perms['update'] == 1)) ? " checked=\"checked\"" : ""; ?> /></td>
											</tr>
											<?php
										}
										?>
										</tbody>
										</table>
										<?php
									}
									if (!(int) $community_details["community_registration"] || !(int) $community_details["community_protected"]) { ?>
										<h4>Non-members</h4>
										<table class="table table-striped table-bordered">
										<colgroup>
											<col style="width: 40%" />
											<col style="width: 20%" />
											<col style="width: 20%" />
											<col style="width: 20%" />
										</colgroup>
										<thead>
											<tr>
												<td>Group</td>
												<td style="border-left: none">Browse Folder</td>
												<td style="border-left: none">Upload Files</td>
												<td style="border-left: none">Allow Comments</td>
											</tr>
										</thead>
										<tbody>
										<?php
										if (!(int) $community_details["community_registration"]) {
										?>
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
										<table class="table table-bordered permissions" style="width: 100%" cellspacing="0" cellpadding="0" border="0">
										<colgroup>
											<col style="width: 30%" />
											<col style="width: 23%" />
											<col style="width: 23%" />
											<col style="width: 21%" />
										</colgroup>
										<thead>
											<tr>
												<td>Group</td>
												<td style="border-left: none; text-align: center">Browse Folder</td>
												<td style="border-left: none; text-align: center">Upload Files</td>
												<td style="border-left: none; text-align: center">Allow Comments</td>
											</tr>
										</thead>
										<tbody>
											<tr>
												<td class="left"><strong>Community Administrators</strong></td>
												<td class="on" style="text-align: center"><input type="checkbox" id="allow_admin_read" name="allow_admin_read" value="1" checked="checked" onclick="this.checked = true" /></td>
												<td style="text-align: center"><input type="checkbox" id="allow_admin_upload" name="allow_admin_upload" value="1" checked="checked" onclick="this.checked = true" /></td>
												<td class="on" style="text-align: center"><input type="checkbox" id="allow_admin_comment" name="allow_admin_comment" value="1" checked="checked" onclick="this.checked = true" /></td>
											</tr>
											<tr>
												<td class="left"><strong>Community Members</strong></td>
												<td class="on" style="text-align: center"><input type="checkbox" id="allow_member_read" name="allow_member_read" value="1"<?php echo (((!isset($PROCESSED["allow_member_read"])) || ((isset($PROCESSED["allow_member_read"])) && ($PROCESSED["allow_member_read"] == 1))) ? " checked=\"checked\"" : ""); ?> /></td>
												<td style="text-align: center"><input type="checkbox" id="allow_member_upload" name="allow_member_upload" value="1"<?php echo (((!isset($PROCESSED["allow_member_upload"])) || ((isset($PROCESSED["allow_member_upload"])) && ($PROCESSED["allow_member_upload"] == 1))) ? " checked=\"checked\"" : ""); ?> /></td>
												<td class="on" style="text-align: center"><input type="checkbox" id="allow_member_comment" name="allow_member_comment" value="1"<?php echo (((!isset($PROCESSED["allow_member_comment"])) || ((isset($PROCESSED["allow_member_comment"])) && ($PROCESSED["allow_member_comment"] == 1))) ? " checked=\"checked\"" : ""); ?> /></td>
											</tr>
											<?php if (!(int) $community_details["community_registration"]) { ?>
											<tr>
												<td class="left"><strong>Browsing Non-Members</strong></td>
												<td class="on" style="text-align: center"><input type="checkbox" id="allow_troll_read" name="allow_troll_read" value="1"<?php echo (((!isset($PROCESSED["allow_troll_read"])) || ((isset($PROCESSED["allow_troll_read"])) && ($PROCESSED["allow_troll_read"] == 1))) ? " checked=\"checked\"" : ""); ?> /></td>
												<td style="text-align: center"><input type="checkbox" id="allow_troll_upload" name="allow_troll_upload" value="1"<?php echo (((isset($PROCESSED["allow_troll_upload"])) && ($PROCESSED["allow_troll_upload"] == 1)) ? " checked=\"checked\"" : ""); ?> /></td>
												<td class="on" style="text-align: center"><input type="checkbox" id="allow_troll_comment" name="allow_troll_comment" value="1"<?php echo (((isset($PROCESSED["allow_troll_comment"])) && ($PROCESSED["allow_troll_comment"] == 1)) ? " checked=\"checked\"" : ""); ?> /></td>
											</tr>
											<?php } ?>
											<?php if (!(int) $community_details["community_protected"]) { ?>
											<tr>
												<td class="left"><strong>Non-Authenticated / Public Users</strong></td>
												<td class="on" style="text-align: center"><input type="checkbox" id="allow_public_read" name="allow_public_read" value="1"<?php echo (((isset($PROCESSED["allow_public_read"])) && ($PROCESSED["allow_public_read"] == 1)) ? " checked=\"checked\"" : ""); ?> /></td>
												<td style="text-align: center"><input type="checkbox" id="allow_public_upload" name="allow_public_upload" value="0" onclick="noPublic(this)" /></td>
												<td class="on" style="text-align: center"><input type="checkbox" id="allow_public_comment" name="allow_public_comment" value="0" onclick="noPublic(this)" /></td>
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
								<tr>
									<td colspan="3">
										<h2>Time Release Options</h2>
									</td>
								</tr>
								<tr>
									<td colspan="3">
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
			$NOTICE++;
			$NOTICESTR[] = "The shared folder that you are trying to edit was deactivated <strong>".date(DEFAULT_DATE_FORMAT, $folder_record["updated_date"])."</strong> by <strong>".html_encode(get_account_data("firstlast", $folder_record["updated_by"]))."</strong>.<br /><br />If there has been a mistake or you have questions relating to this issue please contact the MEdTech Unit directly.";

			echo display_notice();

			application_log("error", "The shared folder record id [".$RECORD_ID."] is deactivated; however, ".$_SESSION["details"]["firstname"]." ".$_SESSION["details"]["lastname"]." [".$ENTRADA_USER->getID()."] has tried to edit it.");
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "The shared folder id that you have provided does not exist in the system. Please provide a valid record id to proceed.";

		echo display_error();

		application_log("error", "The provided shared folder id was invalid [".$RECORD_ID."] (Edit Folder).");
	}
} else {
	$ERROR++;
	$ERRORSTR[] = "Please provide a valid shared folder id to proceed.";

	echo display_error();

	application_log("error", "No shared folder id was provided to edit. (Edit Folder)");
}
?>
