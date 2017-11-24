<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to upload files to a specific folder of a community.
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

echo "<h1>Upload File</h1>\n";

//Check to see if the community is connected to a course
$isCommunityCourse = Models_Community_Course::is_community_course($COMMUNITY_ID);

if ($RECORD_ID) {
	$query			= "SELECT * FROM `community_shares` WHERE `cshare_id` = ".$db->qstr($RECORD_ID)." AND `cpage_id` = ".$db->qstr($PAGE_ID)." AND `community_id` = ".$db->qstr($COMMUNITY_ID);
	$folder_record	= $db->GetRow($query);
	if ($folder_record) {

		$query = "SELECT COUNT(*) FROM `community_share_files` WHERE `cshare_id` = ".$db->qstr($RECORD_ID)." AND `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId())." AND `file_active` = 1";
		
		if (!$db->GetOne($query) || ($COMMUNITY_MEMBER && $folder_record["allow_member_read"]) || (!$COMMUNITY_MEMBER && $folder_record["allow_troll_read"]) || $COMMUNITY_ADMIN) {
			if (shares_module_access($RECORD_ID, "add-file")) {
                Models_Community_Share::getParentsBreadCrumbs($folder_record["cshare_id"]);
				$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=add-file&id=".$RECORD_ID, "title" => "Upload File");
	
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

				$file_uploads = array();

                if ((array_count_values($copyright_settings = (array) $translate->_("copyright")) > 1) && isset($copyright_settings["copyright-uploads"]) && strlen($copyright_settings["copyright-uploads"])) {
                    $COPYRIGHT = true;
                } else {
                    $COPYRIGHT = false;
                }

				// Error Checking
				switch($STEP) {
					case 2 :
						if (isset($_FILES["uploaded_file"]) && is_array($_FILES["uploaded_file"])) {
							foreach($_FILES["uploaded_file"]["name"] as $tmp_file_id=>$file_name) {
								switch($_FILES["uploaded_file"]["error"][$tmp_file_id]) {
									case 0 :
										if (strpos($_FILES["uploaded_file"]["name"][$tmp_file_id], ".") === false) {
											$ERROR++;
											$ERRORSTR[] = "You cannot upload a file without an extension (.doc, .ppt, etc).";

											application_log("error", "User {$ENTRADA_USER->getID()} uploaded a file to shares without an extension.");
										} else {
											if (($file_filesize = (int) trim($_FILES["uploaded_file"]["size"][$tmp_file_id])) <= $VALID_MAX_FILESIZE) {
												$finfo = new finfo(FILEINFO_MIME);
												$type = $finfo->file($_FILES["uploaded_file"]["tmp_name"][$tmp_file_id]);
												$type_array = explode(";", $type);
												$mimetype = $type_array[0];
												$PROCESSED["file_mimetype"]		= strtolower(trim($_FILES["uploaded_file"]["type"][$tmp_file_id]));
												switch($PROCESSED["file_mimetype"]) {
													case "application/x-forcedownload":
													case "application/octet-stream":
													case "\"application/octet-stream\"":
													case "application/download":
													case "application/force-download":
														$PROCESSED["file_mimetype"] = $mimetype;
													break;
												}

												$PROCESSED["file_version"]		= 1;
												$PROCESSED["file_filesize"]		= $file_filesize;
												$PROCESSED["file_filename"]		= useable_filename(trim($file_name));

												if ((!defined("COMMUNITY_STORAGE_DOCUMENTS")) || (!@is_dir(COMMUNITY_STORAGE_DOCUMENTS)) || (!@is_writable(COMMUNITY_STORAGE_DOCUMENTS))) {
													$ERROR++;
													$ERRORSTR[] = "There is a problem with the document storage directory on the server; the " . SUPPORT_UNIT . " has been informed of this error, please try again later.";

													application_log("error", "The community document storage path [".COMMUNITY_STORAGE_DOCUMENTS."] does not exist or is not writable.");
												}
											} else {
												$ERROR++;
												$ERRORSTR[] = "The file that was uploaded is larger than ".readable_size($VALID_MAX_FILESIZE).". Please make the file smaller and try again.";

												application_log("error", "User {$ENTRADA_USER->getID()} unable to upload a file, the file size is larger than the limit.");
											}
										}
									break;
									case 1 :
									case 2 :
										$ERROR++;
										$ERRORSTR[] = "The file that was uploaded is larger than ".readable_size($VALID_MAX_FILESIZE).". Please make the file smaller and try again.";
									break;
									case 3 :
										$ERROR++;
										$ERRORSTR[]	= "The file that was uploaded did not complete the upload process or was interrupted; please try again.";
									break;
									case 4 :
										$ERROR++;
										$ERRORSTR[]	= "You did not select a file from your computer to upload. Please select a local file and try again. The file's id was ".$tmp_file_id.".";
									break;
									case 6 :
									case 7 :
										$ERROR++;
										$ERRORSTR[]	= "Unable to store the new file on the server; the MEdTech Unit has been informed of this error, please try again later.";

										application_log("error", "Community file upload error: ".(($_FILES["filename"]["error"] == 6) ? "Missing a temporary folder." : "Failed to write file to disk."));
									break;
									default :
										application_log("error", "Unrecognized file upload error number [".$_FILES["filename"]["error"]."].");
									break;
								}

								/**
								 * Required field "title" / File Title.
								 */
								if ((isset($_POST["file_title"][$tmp_file_id])) && ($title = clean_input($_POST["file_title"][$tmp_file_id], array("notags", "trim")))) {
									$PROCESSED["file_title"] = $title;
									$file_uploads[$tmp_file_id]["file_title"] = $title;
								} elseif ((isset($PROCESSED["file_filename"])) && ($PROCESSED["file_filename"])) {
									$PROCESSED["file_title"] = $PROCESSED["file_filename"];
									$file_uploads[$tmp_file_id]["file_title"] = $PROCESSED["file_filename"];
								} else {
									$ERROR++;
									$ERRORSTR[] = "The <strong>File Title</strong> field is required.";
								}

								/**
								 * Non-Required field "description" / File Description.
								 *
								 */
								if ((isset($_POST["file_description"][$tmp_file_id])) && $description = clean_input($_POST["file_description"][$tmp_file_id], array("notags", "trim"))) {
									$PROCESSED["file_description"] = $description;
									$file_uploads[$tmp_file_id]["file_description"] = $description;
								} else {
									$PROCESSED["file_description"] = "";
									$file_uploads[$tmp_file_id]["file_description"] = "";
								}

								/**
								 * Non-Required field "access_method" / View Method.
								 */
								if ((isset($_POST["access_method"][$tmp_file_id])) && clean_input($_POST["access_method"][$tmp_file_id], array("int")) == 1) {
									$PROCESSED["access_method"] = 1;
									$file_uploads[$tmp_file_id]["access_method"] = 1;
								} else {
									$PROCESSED["access_method"] = 0;
									$file_uploads[$tmp_file_id]["access_method"] = 0;
								}

								/**
								 * Non-Required field "student_hidden" / View Method.
								 */
								if ((isset($_POST["student_hidden"][$tmp_file_id])) && clean_input($_POST["student_hidden"][$tmp_file_id], array("int")) == 1) {
									$PROCESSED["student_hidden"] = 1;
									$file_uploads[$tmp_file_id]["student_hidden"] = 1;
								} else {
									$PROCESSED["student_hidden"] = 0;
									$file_uploads[$tmp_file_id]["student_hidden"] = 0;
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
									$PROCESSED["allow_member_revision"]	= 1;
								} else {
									$PROCESSED["allow_member_revision"]	= 0;
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
										$PROCESSED["allow_troll_revision"]	= 1;
									}
								}

								/**
								 * Required field "release_from" / Release Start (validated through validate_calendars function).
								 * Non-required field "release_until" / Release Finish (validated through validate_calendars function).
								 */
								if (($LOGGED_IN && $folder_record["allow_troll_read"]) || ($LOGGED_IN && $COMMUNITY_MEMBER && $folder_record["allow_member_read"]) || $COMMUNITY_ADMIN){
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
								} else{
									$PROCESSED["release_date"] = time();
								}

								if (!$ERROR) {
									$PROCESSED["cshare_id"]		= $RECORD_ID;
									$PROCESSED["community_id"]	= $COMMUNITY_ID;
									$PROCESSED["proxy_id"]		= $ENTRADA_USER->getActiveId();
									$PROCESSED["file_active"]	= 1;
									$PROCESSED["updated_date"]	= time();
									$PROCESSED["updated_by"]	= $ENTRADA_USER->getID();


									unset($PROCESSED["csfile_id"]);
									if ($db->AutoExecute("community_share_files", $PROCESSED, "INSERT")) {
										if ($FILE_ID = $db->Insert_Id()) {
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

													$db->AutoExecute("community_acl_groups", array("cgroup_id" => $community_course_group['cgroup_id'], "resource_type" => "communityfile", "resource_value" => $FILE_ID, "create" => $PROCESSED[$community_course_group['cgroup_id']]['create'], "read" => $PROCESSED[$community_course_group['cgroup_id']]['read'], "update" => $PROCESSED[$community_course_group['cgroup_id']]['update'], "delete" => $PROCESSED[$community_course_group['cgroup_id']]['delete']), "INSERT");
												}
											}

											//If the user's role is 'admin', use the submitted form values
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

											$results = $db->AutoExecute("`community_acl`", array(
													"resource_type" => "communityfile",
													"resource_value" => $FILE_ID,
													"create" => $update_perm['create'],
													"read" => $update_perm['read'],
													"update" => $update_perm['update'],
													"delete" => $update_perm['delete'],
													"assertion" => $update_perm['assertion']
												), "INSERT");

											if ($results === false) {
												$ERROR++;
												$ERRORSTR[] = "Error updating the community ACL.";
											}

											$PROCESSED["csfile_id"]	= $FILE_ID;

											if ($db->AutoExecute("community_share_file_versions", $PROCESSED, "INSERT")) {
												if ($VERSION_ID = $db->Insert_Id()) {
													if (communities_shares_process_file($_FILES["uploaded_file"]["tmp_name"][$tmp_file_id], $VERSION_ID)) {
														if ($LOGGED_IN) {
															if ($COMMUNITY_MEMBER) {
																if (($COMMUNITY_ADMIN) || ($folder_record["allow_member_read"] == 1)) {
																	$url = COMMUNITY_URL . $COMMUNITY_URL . ":" . $PAGE_URL . "?section=view-folder&id=" . $RECORD_ID;
																} elseif ($folder_record["allow_member_upload"] == 1){
																	$url = COMMUNITY_URL . $COMMUNITY_URL . ":" . $PAGE_URL;
																}
															} else {
																if ($folder_record["allow_troll_read"] == 1) {
																	$url = COMMUNITY_URL . $COMMUNITY_URL . ":" . $PAGE_URL . "?section=view-folder&id=" . $RECORD_ID;
																} elseif ($folder_record["allow_troll_upload"] == 1) {
																	$url = COMMUNITY_URL . $COMMUNITY_URL . ":" . $PAGE_URL;
																}
															}
														}
                                                        Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("You have successfully uploaded <strong>%s</strong>."), $PROCESSED["file_filename"]), "success", $MODULE);
														add_statistic("community:".$COMMUNITY_ID.":shares", "file_add", "csfile_id", $VERSION_ID);
														communities_log_history($COMMUNITY_ID, $PAGE_ID, $FILE_ID, "community_history_add_file", 1, $RECORD_ID);
                                                        if (COMMUNITY_NOTIFICATIONS_ACTIVE) {
                                                            community_notify($COMMUNITY_ID, $FILE_ID, "file", COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-file&id=".$FILE_ID, $RECORD_ID, $PROCESSES["release_date"]);
                                                        }

                                                        header("Location: " . $url);
                                                        exit;
													}
												}
											}
										}
									}

									if (!$SUCCESS) {
										/**
										 * Because there was no success, check if the file_id was set... if it
										 * was we need to delete the database record :( In the future this will
										 * be handled with transactions like it's supposed to be.
										 */
										if ($FILE_ID) {
											$query	= "DELETE FROM `community_share_files` WHERE `csfile_id` = ".$db->qstr($FILE_ID)." AND `cshare_id` = ".$db->qstr($RECORD_ID)." AND `community_id` = ".$db->qstr($COMMUNITY_ID)." LIMIT 1";
											@$db->Execute($query);

											/**
											 * Also delete the file version, again, hello transactions.
											 */
											if ($VERSION_ID) {
												$query	= "DELETE FROM `community_share_file_versions` WHERE `csfversion_id` = ".$db->qstr($VERSION_ID)." AND `csfile_id` = ".$db->qstr($FILE_ID)." AND `cshare_id` = ".$db->qstr($RECORD_ID)." AND `community_id` = ".$db->qstr($COMMUNITY_ID)." LIMIT 1";
												@$db->Execute($query);
											}
										}

										$ERROR++;
										$ERRORSTR[]	= "Unable to store the new file on the server; the MEdTech Unit has been informed of this error, please try again later.";

										application_log("error", "Failed to move the uploaded Community file to the storage directory [".COMMUNITY_STORAGE_DOCUMENTS."/".$VERSION_ID."].");
									}
								}
	
								if ($ERROR) {
									$STEP = 1;
								}
						
							}
						} else {
							$ERROR++;
							$ERRORSTR[]	 = "To upload a file to this folder you must select a local file from your computer.";
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
						if(count($file_uploads)<1){
							$file_uploads[] = array();
						}
						if ($ERROR) {
							echo display_error();
							add_notice("There was an error while trying to upload your file(s). You will need to reselect the file(s) you wish to upload.");
						}
						if ($NOTICE) {
							echo display_notice();
						}
						?>

						<script>
						var is_admin = <?php if (($LOGGED_IN && $folder_record["allow_troll_read"]) || ($LOGGED_IN && $COMMUNITY_MEMBER && $folder_record["allow_member_read"]) || $COMMUNITY_ADMIN) echo 'true'; else echo 'false';?>;
						var addFileHTML =	'	<div id="file_#{file_id}" class="file-upload">'+
											'		<table>'+
											'			<tr>'+
                                            '				<td colspan="3">' +
                                            '                  <h2>File #{file_number} Details</h2>' +
                                            '               </td>'+
											'			</tr>'+
											'			<tr>'+
                                            '				<td colspan="3">' +
                                            '                   <div style="text-align: right">(<a class="action" href="#" onclick="$(\'file_#{file_id}\').remove();">remove</a>)</div>' +
                                            '               </td>'+
											'			</tr>'+
											'			<tr>'+
                                            '				<td colspan="2" style="vertical-align: top">' +
                                            '                   <label for="uploaded_file" class="form-required">Select Local File</label>' +
                                            '               </td>'+
											'				<td>'+
											'					<input type="file" id="uploaded_file_#{file_id}" name="uploaded_file[#{file_id}]" onchange="fetchFilename(#{file_id})" />'+
											'					<div class="content-small">'+
											'						<strong>Notice:</strong> You may upload files under <?php echo readable_size($VALID_MAX_FILESIZE); ?>.'+
											'					</div>'+
											'				</td>'+
											'			</tr>'+
											'			<tr>' +
                                            '				<td colspan="3">&nbsp;</td>'+
											'			</tr>'+
											'			<tr>'+
                                            '				<td colspan="2">' +
                                            '                  <label for="file_title" class="form-required">File Title</label>' +
                                            '               </td>'+
                                            '				<td>' +
                                            '                   <input type="text" id="file_#{file_id}_title" name="file_title[#{file_id}]" value="<?php echo ((isset($PROCESSED["file_title"])) ? html_encode($PROCESSED["file_title"]) : ""); ?>" maxlength="84" style="width: 95%" />' +
                                            '               </td>'+
											'			</tr>'+
											'			<tr>'+
                                            '				<td colspan="2">' +
                                            '                   <label for="file_description" class="form-nrequired">File Description</label>' +
                                            '               </td>'+
											'				<td>'+
                                            '					<textarea style="width: 95%" id="file_#{file_id}_description" name="file_description[#{file_id}]"><?php echo ((isset($PROCESSED["file_description"])) ? html_encode($PROCESSED["file_description"]) : ""); ?></textarea>'+
											'				</td>'+
											'			</tr>'+
											'			<tr>'+
                                            '				<td colspan="3">&nbsp;</td>'+
											'			</tr>';
											
						if (is_admin) {
                        addFileHTML +=
											'<tr>' +
                                            '    <td colspan="2">' +
                                            '        <label for="access_method" class="form-nrequired">Access Method</label>' +
                                            '    </td>' +
                                            '    <td>' +
                                            '        <table class="table table-bordered no-thead">' +
                                            '            <colgroup>' +
                                            '                <col style="width: 5%" />' +
                                            '                <col style="width: auto" />' +
                                            '            </colgroup>' +
                                            '            <tbody>' +
                                            '            <tr>' +
                                            '                <td class="center">' +
                                            '                    <input type="radio" id="access_method_0_#{file_id}" name="access_method[#{file_id}]" value="0"<?php echo (((!isset($PROCESSED["access_method"])) || ((isset($PROCESSED["access_method"])) && (!(int) $PROCESSED["access_method"]))) ? " checked=\"checked\"" : ""); ?> />' +
                                            '                </td>' +
											'		<td>'+
                                            '                    <label for="access_method_0_#{file_id}" class="content-small">Download this file to their computer first, then open it.</label>' +
                                            '                </td>' +
                                            '            </tr>' +
                                            '            <tr>' +
                                            '                <td class="center">' +
                                            '                    <input type="radio" id="access_method_1_#{file_id}" name="access_method[#{file_id}]" value="1"<?php echo (((isset($PROCESSED["access_method"])) && ((int) $PROCESSED["access_method"])) ? " checked=\"checked\"" : ""); ?> />' +
                                            '                </td>' +
                                            '                <td>' +
                                            '                    <label for="access_method_1_#{file_id}" class="content-small">Attempt to view it directly in the web-browser.</label>' +
                                            '                </td>' +
                                            '            </tr>' +
                                            '            </tbody>' +
                                            '        </table>' +
                                            '    </td>' +
                                            '</tr>' +
                                            '<tr>' +
                                            '    <td colspan="2">' +
                                            '        <label for="student_hidden" class="form-nrequired">Would you like to hide this file from students?</label>' +
                                            '    </td>' +
                                            '    <td>' +
                                            '        <table class="table table-bordered no-thead">' +
                                            '            <colgroup>' +
                                            '                <col style="width: 5%" />' +
                                            '                <col style="width: auto" />' +
                                            '            </colgroup>' +
                                            '            <tbody>' +
                                            '            <tr>' +
                                            '                <td class="center">' +
                                            '                    <input type="radio" id="student_hidden_0_#{file_id}" name="student_hidden[#{file_id}]" value="0" style="vertical-align: middle"<?php echo (((!isset($PROCESSED["student_hidden"])) || ((isset($PROCESSED["student_hidden"])) && (!(int) $PROCESSED["student_hidden"]))) ? " checked=\"checked\"" : ""); ?> />' +
                                            '                </td>' +
                                            '                <td>' +
                                            '                    <label for="student_hidden_0_#{file_id}" class="content-small">Allow students to view this file.</label>' +
                                            '                </td>' +
                                            '            </tr>' +
                                            '                    <tr>'+
                                            '                <td class="center">' +
                                            '                    <input type="radio" id="student_hidden_1_#{file_id}" name="student_hidden[#{file_id}]" value="1" style="vertical-align: middle"<?php echo (((isset($PROCESSED["student_hidden"])) && ((int) $PROCESSED["student_hidden"])) ? " checked=\"checked\"" : ""); ?> />' +
                                            '                </td>' +
                                            '                <td>' +
                                            '                    <label for="student_hidden_1_#{file_id}" class="content-small">Hide this file from students.</label>' +
                                            '                </td>' +
                                            '            </tr>' +
                                            '                </tbody>'+
                                            '            </table>'+
											'	    </td>'+
											'    </tr>';
						}
						
						addFileHTML +=		'		</table>'+
											'	</div>';
						</script>
                        <style>
                        .page-action li a {
                            color:#FFF;
                            font-weight:700;
                        }
                        .page-action li {
                            display:inline;
                            background: none;
                            padding:0;
                        }
                        </style>                        
						<div style="float: right">
							<ul class="page-action">
                                <li><a style="cursor: pointer" onclick="addFile(addFileHTML)" class="btn btn-success"><i class="icon-plus-sign icon-white"></i> Add Another File</a></li>
							</ul>
						</div>
						<form id="upload-file-form" action="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=add-file&amp;id=<?php echo $RECORD_ID; ?>&amp;step=2" method="post" enctype="multipart/form-data" onsubmit="uploadFile()">
                            <input type="hidden" name="MAX_UPLOAD_FILESIZE" value="<?php echo $VALID_MAX_FILESIZE; ?>" />
                            <table class="community-add-table" summary="Upload File">
                                <colgroup>
                                    <col style="width: 3%" />
                                    <col style="width: 20%" />
                                    <col style="width: 77%" />
                                </colgroup>
	                            <tfoot>
	                            <?php
								if ($COPYRIGHT) {
								?>
								<tr>
									<td colspan="3">&nbsp;<hr></td>
								</tr>
								<tr>
									<td colspan="3">
										<h2><?php echo $translate->_("copyright_title"); ?></h2>
									</td>
								</tr>
								<tr>
									<td colspan="3">
										<div class="display-generic">
											<?php echo $copyright_settings["copyright-uploads"]; ?>
											<label class="checkbox">
												<input type="checkbox" value="1" onchange="acceptButton(this)"> <?php echo $translate->_("copyright_accept_label"); ?>
											</label>
										</div>
									</td>
								</tr>
	                            <?php
	                            } ?>
	                            <tr>
                                    <td colspan="3" style="padding-top: 1px; text-align: right">
			                            <div id="display-upload-button" style="padding-top: 15px; text-align: right">
				                            <input type="submit" class="btn btn-primary" id="upload-button" value="Upload File(s)" <?php echo ($COPYRIGHT ? " disabled=\"disabled\"" : ""); ?> />
			                            </div>
		                            </td>
	                            </tr>
	                            </tfoot>
                                <tbody>
                                    <tr>
                                        <td colspan="3">
                                            <div id="file_list">
                                                <?php foreach($file_uploads as $tmp_file_id=>$file_upload){
                                                    if (!$file_upload["success"]) {
                                                    ?>
                                                        <div id="file_<?php echo $tmp_file_id;?>" class="file-upload">
	                                                        <table>
	                                                            <tr>
                                                                    <td colspan="3">
	                                                                	<h2>File <?php echo $tmp_file_id+1;?> Details</h2>
	                                                                </td>
	                                                            </tr>
	                                                            <tr>
                                                                    <td colspan="2" style="vertical-align: top">
	                                                                	<label for="uploaded_file" class="form-required">Select Local File</label>
	                                                                </td>
	                                                                <td>
	                                                                    <input type="file" id="uploaded_file_<?php echo $tmp_file_id;?>" name="uploaded_file[<?php echo $tmp_file_id;?>]" onchange="fetchFilename(<?php echo $tmp_file_id;?>)" />
	                                                                    <div class="content-small">
	                                                                        <strong>Notice:</strong> You may upload files under <?php echo readable_size($VALID_MAX_FILESIZE); ?>.
	                                                                    </div>
	                                                                </td>
	                                                            </tr>
	                                                            <tr>
                                                                    <td colspan="3">&nbsp;</td>
                                                                </tr>
                                                                <tr>
                                                                    <td colspan="2">
	                                                                	<label for="file_title" class="form-required">File Title</label>
	                                                                </td>
	                                                                <td>
                                                                        <input type="text" id="file_<?php echo $tmp_file_id;?>_title" name="file_title[<?php echo $tmp_file_id;?>]" value="<?php echo ((isset($file_upload["file_title"])) ? html_encode($file_upload["file_title"]) : ""); ?>" maxlength="84" style="width: 95%" />
	                                                                </td>
	                                                            </tr>
	                                                            <tr>
                                                                    <td colspan="2" style="vertical-align: top">
                                                                        <label for="file_description" class="form-nrequired">File Description</label>
                                                                    </td>
                                                                    <td style="vertical-align: top">
                                                                        <textarea style="width: 95%" id="file_<?php echo $tmp_file_id;?>_description" name="file_description[<?php echo $tmp_file_id;?>]" cols="50" rows="5"><?php echo ((isset($file_upload["file_description"])) ? html_encode($file_upload["file_description"]) : ""); ?></textarea>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td colspan="3">
                                                                        &nbsp;
	                                                                </td>
	                                                            </tr>
	                                                            <script>
	                                                            if (is_admin) {
                                                                        document.write(
                                                                            '<tr>' +
                                                                            '    <td colspan="2">' +
                                                                            '        <label for="access_method" class="form-nrequired">Access Method</label>' +
                                                                            '    </td>' +
																			'		<td>'+
                                                                            '        <table class="table table-bordered no-thead">' +
                                                                            '            <colgroup>' +
                                                                            '                <col style="width: 5%" />' +
                                                                            '                <col style="width: auto" />' +
                                                                            '            </colgroup>' +
                                                                            '            <tbody>' +
                                                                            '            <tr>' +
                                                                            '                <td class="center">' +
                                                                            '                    <input type="radio" id="access_method_0_<?php echo $tmp_file_id;?>" name="access_method" value="0"<?php echo (((!isset($PROCESSED["access_method"])) || ((isset($PROCESSED["access_method"])) && (!(int) $PROCESSED["access_method"]))) ? " checked=\"checked\"" : ""); ?> />' +
                                                                            '                </td>' +
                                                                            '                <td>' +
                                                                            '                    <label for="access_method_0_<?php echo $tmp_file_id;?>" class="content-small">Download this file to their computer first, then open it.</label>' +
                                                                            '                </td>' +
                                                                            '            </tr>' +
																			'                    <tr>'+
                                                                            '                <td class="center">' +
                                                                            '                    <input type="radio" id="access_method_1_<?php echo $tmp_file_id;?>" name="access_method" value="1"<?php echo (((isset($PROCESSED["access_method"])) && ((int) $PROCESSED["access_method"])) ? " checked=\"checked\"" : ""); ?> />' +
                                                                            '                </td>' +
                                                                            '                <td>' +
                                                                            '                    <label for="access_method_1_<?php echo $tmp_file_id;?>" class="content-small">Attempt to view it directly in the web-browser.</label>' +
                                                                            '                </td>' +
                                                                            '            </tr>' +
																			'                </tbody>'+
																			'            </table>'+
																			'		</td>'+
                                                                            '</tr>' +
                                                                            '<tr>' +
                                                                            '    <td colspan="2">' +
                                                                            '        <label for="student_hidden" class="form-nrequired">Would you like to hide this file from students?</label>' +
                                                                            '    </td>' +
                                                                            '    <td>' +
                                                                            '        <table class="table table-bordered no-thead">' +
                                                                            '            <colgroup>' +
                                                                            '                <col style="width: 5%" />' +
                                                                            '                <col style="width: auto" />' +
                                                                            '            </colgroup>' +
                                                                            '            <tbody>' +
                                                                            '            <tr>' +
                                                                            '                <td class="center">' +
                                                                            '                    <input type="radio" id="student_hidden_0_<?php echo $tmp_file_id;?>" name="student_hidden" value="0" style="vertical-align: middle"<?php echo (((!isset($PROCESSED["student_hidden"])) || ((isset($PROCESSED["student_hidden"])) && (!(int) $PROCESSED["student_hidden"]))) ? " checked=\"checked\"" : ""); ?> />' +
                                                                            '                </td>' +
                                                                            '                <td>' +
                                                                            '                    <label for="student_hidden_0_<?php echo $tmp_file_id;?>" class="content-small">Allow students to view this file.</label>' +
                                                                            '                </td>' +
                                                                            '            </tr>' +
                                                                            '            <tr>' +
                                                                            '                <td class="center">' +
                                                                            '                    <input type="radio" id="student_hidden_1_<?php echo $tmp_file_id;?>" name="student_hidden" value="1" style="vertical-align: middle"<?php echo (((isset($PROCESSED["student_hidden"])) && ((int) $PROCESSED["student_hidden"])) ? " checked=\"checked\"" : ""); ?> />' +
                                                                            '                </td>' +
                                                                            '                <td>' +
                                                                            '                    <label for="student_hidden_1_<?php echo $tmp_file_id;?>" class="content-small">Hide this file from students.</label>' +
                                                                            '                </td>' +
                                                                            '            </tr>' +
                                                                            '            </tbody>' +
                                                                            '        </table>' +
                                                                            '    </td>' +
                                                                            '</tr>'
																		);
	                                                            }
																</script>
	                                                        </table>
                                                        </div>
                                            <?php	}

                                                }
                                                ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="3"><h2>Batch File Permissions</h2></td>
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
														<td><input id="community-all-checkbox" class="permission-type-checkbox" type="radio" name="permission_acl_style" value="CourseCommunityEnrollment" checked="checked" /></td>
														<td><label for="community-all-checkbox" class="content-small">All Community Members</label></td>
													</tr>
													<tr>
														<td><input id="course-group-checkbox" class="permission-type-checkbox" type="radio" name="permission_acl_style" value="CourseGroupMember" /></td>
														<td><label for="course-group-checkbox" class="content-small">Course Groups</label></td>
													</tr>
											</table>
										</td>
									</tr>

                                	<?php if ($COMMUNITY_ADMIN) { ?>
                                    <tr class="file-permissions">
                                        <td colspan="3"><h3>File Permissions</h3></td>
                                    </tr>                        
                                    <tr class="file-permissions">
                                        <td colspan="3">
                                            <table class="table table-bordered table-striped table-community-centered">
                                                <colgroup>
                                                    <col style="width: 50%" />
                                                    <col style="width: 50%" />
                                                </colgroup>
                                                <thead>
                                                    <tr>
                                                        <td>View File</td>
                                                        <td style="border-left: none">Upload New Version</td>
                                                    </tr>
                                                </thead>
												<tbody>
													<tr>
                                                        <td class="on"><input type="checkbox" id="read" name="read" value="read" checked="checked" /></td>
                                                        <td><input type="checkbox" id="update" name="update" value="update" /></td>
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
                                                <col style="width: 30%" />
                                                <col style="width: 30%" />
                                            </colgroup>
                                            <thead>
                                                <tr>
                                                    <td>Group</td>
                                                    <td style="border-left: none">View File</td>
                                                    <td style="border-left: none">Upload New Version</td>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php

                                            foreach ($course_groups as $course_group) {
                                                ?>
                                                <tr>
                                                    <td class="left"><strong><?php echo $course_group['group_name']; ?></strong></td>
                                                    <td class="on"><input type="checkbox" id="<?php echo $course_group['cgroup_id']; ?>_read" name="<?php echo $course_group['cgroup_id']; ?>[]" value="read" /></td>
                                                    <td><input type="checkbox" id="<?php echo $course_group['cgroup_id']; ?>_update" name="<?php echo $course_group['cgroup_id']; ?>[]" value="update" /></td>
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
                                                    <td style="border-left: none">View File</td>
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
                                            <table class="table table-bordered table-striped table-community-centered-list">
                                            <colgroup>
                                                <col style="width: 40%" />
                                                <col style="width: 30%" />
                                                <col style="width: 30%" />
                                            </colgroup>
                                            <thead>
                                                <tr>
                                                    <td>Group</td>
                                                    <td style="border-left: none">View File</td>
                                                    <td style="border-left: none">Upload New Version</td>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td class="left"><strong>Community Administrators</strong></td>
                                                    <td class="on"><input type="checkbox" id="allow_admin_read" name="allow_admin_read" value="1" checked="checked" onclick="this.checked = true" /></td>
                                                    <td><input type="checkbox" id="allow_admin_revision" name="allow_admin_revision" value="1" checked="checked" onclick="this.checked = true" /></td>
                                                </tr>
                                                <tr>
                                                    <td class="left"><strong>Community Members</strong></td>
                                                    <td class="on"><input type="checkbox" id="allow_member_read" name="allow_member_read" value="1"<?php echo (((!isset($PROCESSED["allow_member_read"])) || ((isset($PROCESSED["allow_member_read"])) && ($PROCESSED["allow_member_read"] == 1))) ? " checked=\"checked\"" : ""); ?> /></td>
                                                    <td><input type="checkbox" id="allow_member_revision" name="allow_member_revision" value="1"<?php echo (((!isset($PROCESSED["allow_member_revision"])) || ((isset($PROCESSED["allow_member_revision"])) && ($PROCESSED["allow_member_revision"] == 1))) ? " checked=\"checked\"" : ""); ?> /></td>
                                                </tr>
                                                <?php if (!(int) $community_details["community_registration"]) {  ?>
                                                <tr>
                                                    <td class="left"><strong>Browsing Non-Members</strong></td>
                                                    <td class="on"><input type="checkbox" id="allow_troll_read" name="allow_troll_read" value="1"<?php echo (((!isset($PROCESSED["allow_troll_read"])) || ((isset($PROCESSED["allow_troll_read"])) && ($PROCESSED["allow_troll_read"] == 1))) ? " checked=\"checked\"" : ""); ?> /></td>
                                                    <td><input type="checkbox" id="allow_troll_revision" name="allow_troll_revision" value="1"<?php echo (((isset($PROCESSED["allow_troll_revision"])) && ($PROCESSED["allow_troll_revision"] == 1)) ? " checked=\"checked\"" : ""); ?> /></td>
				                                    </tr>
                                                <?php } ?>
												</tbody>
											</table>
										</td>
									</tr>
                                    <?php } ?>
                                    <tr>
                                        <td colspan="3"><h2>Batch Time Release Options</h2></td>
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
                                </tbody>
                            </table>
					    </form>
						<div id="display-upload-status" style="display: none">
							<div style="text-align: left; background-color: #EEEEEE; border: 1px #666666 solid; padding: 10px">
								<div style="color: #003366; font-size: 18px; font-weight: bold">
									<img src="<?php echo ENTRADA_URL; ?>/images/loading.gif" width="32" height="32" alt="File Uploading" title="Please wait while this file is being uploaded." style="vertical-align: middle" /> Please Wait: this file is being uploaded.
								</div>
								<br /><br />
								This can take time depending on your connection speed and the filesize.
							</div>
						</div>
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
			
			$ERROR++;
			$ERRORSTR[] = "Your access level only allows you to upload one file and revisions of it. Any additional files can be uploaded as a new revision of that file without overwriting the current file.";
			
			if ($ERROR) {
				echo display_error();
			}
			if ($NOTICE) {
				echo display_notice();
			}
		}
	} else {
		application_log("error", "The provided folder id was invalid [".$RECORD_ID."] (Upload File).");

		header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
		exit;
	}
} else {
	application_log("error", "No folder id was provided to upload into. (Upload File)");

	header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
	exit;
}
?>
