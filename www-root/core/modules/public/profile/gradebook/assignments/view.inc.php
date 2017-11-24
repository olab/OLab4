<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Used to view the details of / download the specified file within a folder.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Brandon Thorn <brandon.thorn@queensu.ca>
 * @copyright Copyright 2012 Queen's University. All Rights Reserved.
 *
*/
if ((!defined("IN_PUBLIC_ASSIGNMENTS"))) {
	exit;
}

if (!$RECORD_ID) {
	if (isset($_GET["assignment_id"]) && $tmp = clean_input($_GET["assignment_id"], "int")) {
		$RECORD_ID = $tmp;
	}
}
if (!isset($DOWNLOAD) || !$DOWNLOAD) {
	if (isset($_GET["download"]) && $tmp = clean_input($_GET["download"], "int")) {
		$DOWNLOAD = $tmp;
	}
}

$iscontact = Models_Assignment_Contacts::fetchRowByAssignmentIDProxyID($RECORD_ID, $ENTRADA_USER->getID());

if ($RECORD_ID) {

	$assignment = Models_Assignment::getRowAssignmentCourse($RECORD_ID);
    $assessment = Models_Gradebook_Assessment::fetchRowByID($assignment["assessment_id"]);
    load_rte();

	if (isset($_GET["pid"]) && $tmp = clean_input($_GET["pid"], "int")) {
		if ($iscontact) {
			$USER_ID = $tmp;
		} elseif ($assignment && $ENTRADA_ACL->amIAllowed(new CourseResource($assignment["course_id"], $assignment["organisation_id"]), "update")) {
			$iscontact = true;
			$USER_ID = $tmp;
		}else {
			$USER_ID = false;
		}

	} elseif($iscontact) {
		header("Location: ".ENTRADA_URL."/admin/gradebook/assignments?section=grade&id=".$assignment["course_id"]."&assignment_id=".$RECORD_ID);
	} else {
		$USER_ID = $ENTRADA_USER->getID();
	}

	if ($USER_ID) {
		if($assignment){
			$course_ids = groups_get_enrolled_course_ids($USER_ID);
			if(in_array($assignment["course_id"],$course_ids)){

				$file_record	= Models_Assignment_File::getRowFileAssignmentByAssignmentIDProxyID($RECORD_ID, $USER_ID);

					$FILE_ID = $file_record["afile_id"];
					if ((isset($DOWNLOAD)) && ($DOWNLOAD)) {
						/**
						 * Check for valid permissions before checking if the file really exists.
						 */
						if(isset($_GET["file_id"]) && $tmp_id = (int)$_GET["file_id"]){
							$dfile_id = $tmp_id;
						}else{
							$dfile_id = 0;
						}
						$file_version = false;
						if ($DOWNLOAD) {

							/**
							 * Check for specified version.
							 */

                            $result = Models_Assignment_File_Version::fetchOneByAssignmentIDFileIDFileVersion($RECORD_ID, $dfile_id, $DOWNLOAD);

							if ($result) {
								$file_version = array();
								$file_version["afversion_id"] = $result->getAfversionID();
								$file_version["file_mimetype"] = $result->getFileMimetype();
								$file_version["file_filename"] = $result->getFileFilename();
								$file_version["file_filesize"] = (int) $result->getFileFilesize();
							}
						} 

						if (($file_version) && (is_array($file_version))) {
							$download_file = FILE_STORAGE_PATH."/A".$file_version["afversion_id"];
							if ((file_exists($download_file)) && (is_readable($download_file))) {
								ob_clear_open_buffers();
                                header("Pragma: public");
                                header("Expires: 0");
                                header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                                header("Content-Type: application/force-download");
                                header("Content-Type: application/octet-stream");
                                header("Content-Type: ".$file_version["file_mimetype"]);
                                if (isset($assignment["anonymous_marking"]) && $assignment["anonymous_marking"]) {
                                    $file_extension = pathinfo($file_version['file_filename'], PATHINFO_EXTENSION);
                                    header("Content-Disposition: attachment; filename=\"" . $file_record["number"] . "_" . $file_version["afversion_id"] . "." . $file_extension . "\"");
                                } else {
                                header("Content-Disposition: attachment; filename=\"".$file_version["file_filename"]."\"");
                                }
                                header("Content-Length: ".@filesize($download_file));
                                header("Content-Transfer-Encoding: binary\n");
								add_statistic("community:".$COMMUNITY_ID.":shares", "file_download", "csfile_id", $RECORD_ID);
								echo @file_get_contents($download_file, FILE_BINARY);
								exit;
							}
						}



						if ((!$ERROR) || (!$NOTICE)) {
							add_error("<strong>Unable to download the selected file.</strong><br /><br />The file you have selected cannot be downloaded at this time, please try again later.");
						}

						if ($NOTICE) {
							echo display_notice();
						}
						if ($ERROR) {
							echo display_error();
						}

					} else {
						if (isset($iscontact) && $iscontact) {
                            $user_object = Models_User::fetchRowByID($file_record["proxy_id"]);
							$user_name = $user_object->getFullname();
							$BREADCRUMB = array();
							$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/gradebook", "title" => "Gradebooks");
							$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/gradebook?".replace_query(array("section" => "view", "id" => $file_record["course_id"])), "title" => "Assignments");
							$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/gradebook/assignments?".replace_query(array("section" => "grade", "id" => $file_record["course_id"], "assignment_id"=>$file_record["assignment_id"], "step" => false)), "title" => $assessment->getName());
                            $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/gradebook/assignments?".replace_query(array("section" => "grade", "id" => $file_record["course_id"], "assignment_id"=>$file_record["assignment_id"], "step" => false)), "title" => ($assignment["anonymous_marking"] ? html_encode($file_record["number"]) : html_encode($user_name)."'s Submission"));
						} else {
							$BREADCRUMB[] = array("url" => ENTRADA_URL."/profile/gradebook/assignments?section=view&assignment_id=".$RECORD_ID, "title" => limit_chars($assessment->getName(), 32));
						}
						$ADD_COMMENT	= true;//shares_module_access($file_record["cshare_id"], "add-comment");
						$ADD_REVISION	= $assignment["assignment_uploads"] == 1 ? true : false;//shares_file_module_access($file_record["csfile_id"], "add-revision");
						$MOVE_FILE		= false;//shares_file_module_access($file_record["csfile_id"], "move-file");
						$NAVIGATION		= false;//shares_file_navigation($file_record["cshare_id"], $RECORD_ID);
						//$community_shares_select = community_shares_in_select($file_record["cshare_id"]);
						?>

                        <?php

						if ($NOTICE) {
							echo display_notice();
						}
						?>
						<a name="top"></a>
                        <h1>Assignment Submission</h1>
                        <?php
                        $max_files = (int)$assignment["max_file_uploads"];
                        ?>
                        <p>You may upload <?php echo $max_files; ?> file<?php echo $max_files !== 1 ? 's' : ''; ?> for this assignment.</p>

                        <?php

                        $file_records = Models_Assignment_File_Version::getAllUserAssignmentFilesByAssignmentIDProxyID($RECORD_ID, $USER_ID);

                        if ($file_records) {
                            foreach ($file_records as $file_record) {
                            ?>
                                <div id="file-<?php echo $file_record["afile_id"]; ?>" style="padding-top: 15px; clear: both">
                                    <?php

                                    $results	= Models_Assignment_File_Version::getAllUserAssignmentFilesVersionsByAssignmentIDFileID($RECORD_ID, $file_record["afile_id"]);

                                    if ($results) {
                                        $total_releases	= count($results);
                                        ?>
                                       <h2 id="file-<?php echo $file_record["afile_id"] ?>-title"><?php echo ($assignment["anonymous_marking"] ? html_encode($file_record["number"]) : html_encode($file_record["file_title"])) ?> </h2>
                                       <p><?php echo html_encode($file_record["file_description"]); ?> </p>

                                        <table class="table table-bordered table-striped tableList" cellspacing="0" summary="List of Gradebooks">
                                            <thead>
                                            <tr>
                                                <th width="30%"><?php echo $translate->_("Filename"); ?></th>
                                                <th width="10%"><?php echo $translate->_("File Size"); ?></td>
                                                <th width="20%" ><?php echo $translate->_("Upload Date"); ?></th>
                                                <th width="25%"><?php echo $translate->_("Upload By"); ?></th>
                                                <th width="10%"><?php echo $translate->_("Download"); ?></th>
                                                <th width="5%"></th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php
                                            $i=0;
                                            foreach ($results as $result) {
                                                ?>
                                                <tr>
                                                    <td><?php
                                                        if (!isset($assignment["anonymous_marking"]) || !$assignment["anonymous_marking"]) {
                                                            echo "<span id=\"file-version-".$result["afversion_id"]."\">".html_encode($result["file_filename"])." (v". $result["file_version"] .")</span>";
                                                        } else {
                                                            $file_extension = pathinfo($result['file_filename'], PATHINFO_EXTENSION);
                                                            echo "<span id=\"file-version-".$result["afversion_id"]."\">".$file_record["number"] . "_" . $result["afversion_id"] . "." . $file_extension." (v". $result["file_version"] .")</span>";
                                                        }
                                                        if ($i==0) {
                                                            echo "<i class=\"icon-ok-sign\"></i>";
                                                        }

                                                        ?>
                                                    </td>
                                                    <td><?php echo readable_size($result["file_filesize"]); ?></td>
                                                    <td><?php echo  date(DEFAULT_DATE_FORMAT, $result["updated_date"]); ?></td>
                                                    <td><?php
                                                        if (!isset($assignment["anonymous_marking"]) || !$assignment["anonymous_marking"] || $result["proxy_id"] == $ENTRADA_USER->getID()) {
                                                            echo html_encode($result["uploader"]);
                                                        }
                                                        ?>
                                                    </td>
                                                    <td><a href="<?php echo ENTRADA_URL ?>/profile/gradebook/assignments?section=view&assignment_id=<?php echo $RECORD_ID."&amp;".(isset($iscontact) && $iscontact?"pid=".$USER_ID."&amp;":""); ?>download=<?php echo $result["file_version"];?>&file_id=<?php echo $result["afile_id"]; ?>"><?php echo $translate->_("Download"); ?></a></td>
                                                    <td>
                                                        <a class="assignment_file_delete" data-fileVersionId="<?php echo $result["afversion_id"]?>">
                                                            <?php
                                                            if ($result["proxy_id"] == $ENTRADA_USER->getID()) {
                                                            ?>
                                                            <img src="<?php echo ENTRADA_URL; ?>/images/btn-delete.gif"></a>

                                                        <?php } ?>
                                                    </td>                                                
                                                </tr>
                                                <?php
                                                $i++;
                                            }
                                            ?>
                                            </tbody>
                                        </table>

                                    <?php }  ?>
                                    <?php
                                    //Teacher response

                                    $results	= Models_Assignment_File_Version::getAllTeacherAssignmentFilesVersionsByAssignmentIDFileID($RECORD_ID, $file_record["afile_id"]);
                                    $teacher_file = false;

                                    if ($results) {
                                        $teacher_file = true;
                                        $TEACHER_FILE_RECORD = $results[0]["afile_id"];
                                        $total_releases	= count($results);
                                        ?>
                                        <h2 ><?php echo $translate->_("Teacher's Response"); ?> </h2>
                                        <table class="table table-bordered table-striped tableList" cellspacing="0" summary="List of Gradebooks">
                                            <thead>
                                            <tr>
                                                <th width="30%"><?php echo $translate->_("Filename"); ?></th>
                                                <th width="10%"><?php echo $translate->_("File Size"); ?></td>
                                                <th width="20%" ><?php echo $translate->_("Upload Date"); ?></th>
                                                <th width="25%"><?php echo $translate->_("Upload By"); ?></th>
                                                <th width="10%"><?php echo $translate->_("Download"); ?></th>
                                                <th width="5%"></th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php
                                            $i = 0;
                                            foreach ($results as $result) {
                                                ?>
                                                <tr>
                                                    <td><?php
                                                        if (!isset($assignment["anonymous_marking"]) || !$assignment["anonymous_marking"]) {
                                                            echo html_encode($result["file_filename"])." (v". $result["file_version"] .")";
                                                        } else {
                                                            $file_extension = pathinfo($result['file_filename'], PATHINFO_EXTENSION);
                                                            echo $file_record["number"] . "_" . $result["afversion_id"] . "." . $file_extension." (v". $result["file_version"] .")";
                                                        }
                                                        if ($i==0) {
                                                            echo "<i class=\"icon-ok-sign\"></i>";
                                                        }


                                                         ?>
                                                    </td>
                                                    <td><?php echo readable_size($result["file_filesize"]); ?></td>
                                                    <td><?php echo  date(DEFAULT_DATE_FORMAT, $result["updated_date"]); ?></td>
                                                    <td><?php
                                                        if (!isset($assignment["anonymous_marking"]) || !$assignment["anonymous_marking"] || $result["proxy_id"] == $ENTRADA_USER->getID()) {
                                                            echo html_encode($result["uploader"]);
                                                        }
                                                         ?>
                                                    </td>
                                                    <td><a href="<?php echo ENTRADA_URL ?>/profile/gradebook/assignments?section=view&assignment_id=<?php echo $RECORD_ID."&amp;".(isset($iscontact) && $iscontact?"pid=".$USER_ID."&amp;":""); ?>download=<?php echo $result["file_version"];?>&file_id=<?php echo $result["afile_id"]; ?>"><?php echo $translate->_("Download"); ?></a></td>
                                                    <td>
                                                        <a href="">
                                                            <?php
                                                            if ($result["proxy_id"] == $ENTRADA_USER->getID()) {
                                                                ?>
                                                            <img src="<?php echo ENTRADA_URL; ?>/images/btn-delete.gif"></a>

                                                            <?php } ?>
                                                    </td>
                                                </tr>
                                                <?php
                                                $i++;
                                            }
                                            ?>
                                            </tbody>
                                        </table>

                                    <?php }

                                ?>

                                </div>
                            <?php
                                if (($ADD_REVISION) || ($MOVE_FILE)) {
                                    ?>
                                    <div class="page-action">
                                        <?php if (isset($iscontact) && $iscontact) {
                                            if ($teacher_file) {?>
                                                <a class="upload-revised-file" data-fid="<?php echo $TEACHER_FILE_RECORD; ?>"><button class="btn"><i class="icon-upload"></i>Upload Response Revision</button></a>
                                        <?php } else {
                                            ?><a class="upload-teacher-file" data-parentid="<?php echo $file_record['afile_id'];; ?>"><button class="btn"><i class="icon-upload"></i>Hand Back Response</button></a><?php
                                            }
                                        } elseif ($ADD_REVISION) {?>
                                            <a class="upload-revised-file" data-fid="<?php echo $file_record['afile_id']; ?>"><button class="btn"><i class="icon-upload"></i>Upload Revised File</button></a>
                                        <?php } ?>
                                    </div>
                                    <?php
                                }
                            }
                            ?>
                            <?php
                        }
                        ?>
                            <div class="assignment-file-upload">
                                <?php
                                $num_files = 0;
                                if ($file_records) {
                                    $num_files = count($file_records);
                                }

                                if ($num_files < $max_files && $USER_ID == $ENTRADA_USER->getID()) { ?>
                                    <a href="#upload-file-mod" data-toggle="modal">
                                        <button class="btn btn-primary"><i class="icon-plus icon-white"></i>Add <?php echo ($num_files) ? "Another" : ""; ?> File</button>
                                    </a>
                                <?php } ?>
                            </div>

                            <h2 style="margin-bottom: 0px">Assignment Comments</h2>
                            <?php

                            $results	= Models_Assignment_Comments::getAllByAssignmentIDProxyID($RECORD_ID, $USER_ID);
                            $comments	= 0;
                            if ($results) { ?>
                                <table class="discussions posts" style="width: 100%" cellspacing="0" cellpadding="0" border="0">
                                    <tbody>
                                    <?php
                                    foreach($results as $result) {
                                        $comments++;
                                        ?>
                                        <tr>
                                            <?php
                                            if (!isset($assignment["anonymous_marking"]) || !$assignment["anonymous_marking"] || $result["proxy_id"] == $ENTRADA_USER->getID()) {
                                                ?>
                                                <td>
                                                    <div class="img-comments">
                                                        <img src="<?php echo ENTRADA_URL?>/api/photo.api.php/<?php echo $result["proxy_id"]; ?>" class="img-polaroid">
                                                    </div>
                                                    <div class="middle-comments">
                                                        <a href="<?php echo ENTRADA_URL . "/people?profile=" . html_encode($result["commenter_username"]); ?>" style="font-size: 10px"><?php echo html_encode($result["commenter_fullname"]); ?></a><br /> <?php echo date(DEFAULT_DATE_FORMAT, $result["updated_date"]); ?>
                                                    </div>
                                                <?php
                                            } else {
                                                ?>
                                                <td>
                                                    <span style="font-size: 10px">Anonymous Commenter <br/> <?php echo date(DEFAULT_DATE_FORMAT, $result["updated_date"]); ?></span>
                                                <?php
                                            }
                                            ?>
                                                <div class="comment-right">
                                                <?php
                                                    if ($result["proxy_id"] == $ENTRADA_USER->getID()) { ?>
                                                        <a class="assignment_comment_edit" data-commentId="<?php echo $result["acomment_id"]?>" data-assignmentId="<?php echo $RECORD_ID ?>">Edit</a>
                                                        <a class="assignment_comment_delete" data-commentId="<?php echo $result["acomment_id"]?>"><img src="<?php echo ENTRADA_URL; ?>/images/btn-delete.gif"></a>
                                                <?php } ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" class="content">
                                            <a name="comment-<?php echo (int) $result["acomment_id"]; ?>"></a>
                                            <?php
                                                echo ((trim($result["comment_title"])) ? "<div style=\"font-weight: bold\">".html_encode(trim($result["comment_title"]))."</div>" : "");
                                                echo "<span id=\"comment-". $result["acomment_id"] ."\">".$result["comment_description"]."</span>";

                                                if ($result["release_date"] != $result["updated_date"]) {
                                                    echo "<div class=\"content-small\" style=\"margin-top: 15px\">\n";
                                                    echo "	<strong>Last updated:</strong> ".date(DEFAULT_DATE_FORMAT, $result["updated_date"])." by ".(($result["proxy_id"] == $result["updated_by"]) ? html_encode($result["commenter_fullname"]) : html_encode(get_account_data("firstlast", $result["updated_by"]))).".";
                                                    echo "</div>\n";
                                                }
                                            ?>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                    ?>
                                    </tbody>
                                </table>

                            <?php
                            } else {
                                echo "<p>No comments found.</p>";
                            }
                            if ($ADD_COMMENT) {
                            ?>
                                <div class="assignment-comment">
                                        <button class="btn btn-primary" id="add_assignment_comment"><i class="icon-plus icon-white"></i>Add Assignment Comment</button>
                                </div>
                            <?php
                            }
                            ?>

<!--                            <div id="dialog-confirm" title="Delete?" style="display: none">-->
<!--                                <p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>This item will be permanently deleted and cannot be recovered. Are you sure you want to delete it?</p>-->
<!--                            </div>-->

                        <div id="upload-file-mod" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="label" aria-hidden="true">
                            <form name="upload_file_form" id="upload_file_form" action="<?php echo ENTRADA_URL; ?>/profile" method="post" enctype="multipart/form-data">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                                    <h3 id="label">Upload Assignment File</h3>
                                </div>
                                <div class="modal-body">
                                    <div class="description">
                                        <div id="upload-file-result"></div>
                                        <div class="decription-large"><i class="fa fa-upload fa-md" aria-hidden="true"></i><br/><?php echo $translate->_("Drag and drop you file here to upload");?></div>
                                        <div class="decription-large ready hide"></div>
                                        <div class="decription-medium ready hide"></div>
                                        <div class="decription-medium"><?php echo $translate->_("Or use the choose file button below to browse for a file");?></div>
                                        <div class="decription-small"><?php echo $translate->_("Maximum file size: 50 MB");?></div>
                                    </div>
                                    <div class="file-comment">
                                        <table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Add File Comment">
                                            <colgroup>
                                                <col style="width: 20%" />
                                                <col style="width: 80%" />
                                            </colgroup>
                                            <tbody>
                                            <tr>
                                                <td ><label for="file_title" class="form-required"><?php echo $translate->_("File Title"); ?></label></td>
                                                <td style="text-align: right"><input type="text" id="file_title" name="file_title" value="" maxlength="128" style="width: 95%" /></td>
                                            </tr>
                                            <tr>
                                                <td ><label for="file_description" class="form-nrequired"><?php echo $translate->_("File Comment");?></label></td>
                                                <td style="text-align: right">
                                                    <textarea id="file_description" name="file_description" class="expandable" cols="48" rows="7"></textarea></td>
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <div class="form-values">
                                        <input type="hidden" id="method" name="method" value="file-upload">
                                        <input type="hidden" id="file_afile_id" name="file_afile_id">
                                        <input type="hidden" id="file_parent_id" name="file_parent_id">
                                        <input type="hidden" id="file_assignment_id" name="file_assignment_id" value="<?php echo $RECORD_ID?>">
                                        <input type="hidden" id="file_proxy_id" name="file_proxy_id" value="<?php echo $USER_ID?>">
                                        <input type="hidden" id="file_type" name="file_type" value="<?php if($USER_ID == $ENTRADA_USER->getID()) { echo "submission"; } else { echo "response"; }  ?>">
                                        <input type="file" name="assignment_file" id="assignment_file" />
                                        <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo $translate->_("Cancel");?></button>
                                        <button id="upload-file-button" class="btn btn-primary"><?php echo $translate->_("Upload");?></button>
                                    </div>
                                    <div class="upload-progress-bar hide"></div>
                                </div>
                            </form>
                        </div>

                        <div id="edit-comment" class="modal hide" tabindex="-1" role="dialog" aria-labelledby="label" aria-hidden="true">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                                <h3 id="label"><?php echo $translate->_("Add/Edit Comment");?></h3>
                            </div>
                            <div class="modal-body">
                                <div id="update-comment-result"></div>
                                <table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Add Assignment Comment">
                                    <colgroup>
                                        <col style="width: 20%" />
                                        <col style="width: 80%" />
                                    </colgroup>
                                    <tbody>
                                    <tr>
                                        <td ><label for="comment_title" class="form-nrequired"><?php echo $translate->_("Comment Title"); ?></label></td>
                                        <td style="text-align: right"><input type="text" id="comment_title" name="comment_title" value="" maxlength="128" style="width: 95%" /></td>
                                    </tr>
                                    <tr>
                                        <td colspan="2"><label for="comment_description" class="form-required"><?php echo $translate->_("Comment Body");?></label></td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <textarea id="comment_description" name="comment_description" style="width: 100%; height: 200px" cols="68" rows="12"></textarea>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="modal-footer">
                                <input type="hidden" id="modal_comment_id">
                                <input type="hidden" id="modal_assignment_id" value="<?php echo $RECORD_ID?>">
                                <input type="hidden" id="assignment_proxy_id" value="<?php echo $USER_ID?>">
                                <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo $translate->_("Cancel");?></button>
                                <button id="save-assignment-comment-button" class="btn btn-primary"><?php echo $translate->_("Save");?></button>
                            </div>
                        </div>

                        <div id="delete-comment-modal" class="modal hide fade">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                                <h1><?php echo $translate->_("Delete Assignment Comment"); ?></h1>
                            </div>
                            <div class="modal-body">
                                <div>
                                    <p><b><?php echo $translate->_("Please confirm you would like to delete this Assignment Comment?"); ?></b></p>
                                    <div id="delete-comment-container"></div>
                                    <div id="delete-comment-result"></div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <input type="hidden" id="delete_modal_comment_id">
                                <button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
                                <button id="delete-assignment-comment-button" class="btn btn-primary"><?php echo $translate->_("Delete"); ?></button>
                            </div>
                        </div>

                        <div id="delete-file-modal" class="modal hide fade">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                                <h1><?php echo $translate->_("Delete Assignment File"); ?></h1>
                            </div>
                            <div class="modal-body">
                                <div>
                                    <p><b><?php echo $translate->_("Please confirm you would like to delete this Assignment File?"); ?></b></p>
                                    <div id="delete-file-container"></div>
                                    <div id="delete-file-result"></div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <input type="hidden" id="delete_file_version_id">
                                <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo $translate->_("Cancel");?></button>
                                <button id="delete-assignment-file-button" class="btn btn-primary"><?php echo $translate->_("Delete"); ?></button>
                            </div>
                        </div>

                        <?php
					}
            } else {
				echo display_error("You do not have authorization to view this resource.");
			}
		} else {
				application_log("error", "The provided file id was invalid [".$RECORD_ID."] (View File).");
				add_error('Invalid id specified. No assignment found for that id.');
				echo display_error();
				exit;
		}

	} else {
		add_error('You do not have authorization to view this resource');
		echo display_error();
	}
} else {
	$url = ENTRADA_URL."/admin/gradebook";
	$ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";

	application_log("error", "No course_id, assignment id or permission was provided to view. (View File)");
	add_error('You are not permitted to view this assignment.<br /><br />You will now be redirected to the <strong>Gradebook index</strong> page.  This will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.');

	echo display_error();
}
?>
