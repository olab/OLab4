<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to view the details of / download the specified file within a folder.
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
    //checks the role of the user and sets hidden to true if they're not a facluty, staff, or medtech memeber
    //used to control access to files if they're marked hidden from students
    $group = $ENTRADA_USER->getActiveGroup();
    if ($group == 'faculty' || $group == 'staff'  || $group == 'medtech') {
        $hidden = false;
    } else {
        $hidden = true;
    }    
if ($RECORD_ID) {
	$query			= "
					SELECT a.*, b.`folder_title`, CONCAT_WS(' ', c.`firstname`, c.`lastname`) AS `uploader_fullname`, c.`username` AS `uploader_username`
					FROM `community_share_files` AS a
					LEFT JOIN `community_shares` AS b
					ON a.`cshare_id` = b.`cshare_id`
					LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS c
					ON a.`proxy_id` = c.`id`
					WHERE a.`proxy_id` = c.`id`
					AND a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
					AND a.`csfile_id` = ".$db->qstr($RECORD_ID)."
					AND b.`cpage_id` = ".$db->qstr($PAGE_ID)."
					AND a.`file_active` = '1'
					AND b.`folder_active` = '1'".
                    ($hidden ? "AND a.`student_hidden` = '0' AND b.`student_hidden` = '0'" : "");
	$file_record	= $db->GetRow($query);
	if ($file_record) {
        //checks if a folders parent is hidden
        $parent_folder_hidden = Models_Community_Share::parentFolderHidden($file_record['cshare_id']);
        if ($parent_folder_hidden && $hidden) {
            application_log("error", "An attempt to view a file with a parent folder hidden was made. cshare_id: [".$RECORD_ID."]");

            header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
            exit;
        }              
        
		if ((isset($DOWNLOAD)) && ($DOWNLOAD)) {
			/**
			 * Check for valid permissions before checking if the file really exists.
			 */
			if (shares_file_module_access($RECORD_ID, "view-file")) {
				$file_version = false;
				if ((int) $DOWNLOAD) {
					/**
					 * Check for specified version.
					 */
					$query	= "
							SELECT *
							FROM `community_share_file_versions`
							WHERE `csfile_id` = ".$db->qstr($RECORD_ID)."
							AND `cshare_id` = ".$db->qstr($file_record["cshare_id"])."
							AND `community_id` = ".$db->qstr($COMMUNITY_ID)."
							AND `file_active` = '1'
							AND `file_version` = ".$db->qstr((int) $DOWNLOAD);
					$result	= $db->GetRow($query);
					if ($result) {
						$file_version = array();
						$file_version["csfversion_id"] = $result["csfversion_id"];
						$file_version["file_mimetype"] = $result["file_mimetype"];
						$file_version["file_filename"] = $result["file_filename"];
						$file_version["file_filesize"] = (int) $result["file_filesize"];
					}
				} else {
					/**
					 * Download the latest version.
					 */
					$query	= "
							SELECT *
							FROM `community_share_file_versions`
							WHERE `csfile_id` = ".$db->qstr($RECORD_ID)."
							AND `cshare_id` = ".$db->qstr($file_record["cshare_id"])."
							AND `community_id` = ".$db->qstr($COMMUNITY_ID)."
							AND `file_active` = '1'
							ORDER BY `file_version` DESC
							LIMIT 0, 1";
					$result	= $db->GetRow($query);
					if ($result) {
						$file_version = array();
						$file_version["csfversion_id"] = $result["csfversion_id"];
						$file_version["file_mimetype"] = $result["file_mimetype"];
						$file_version["file_filename"] = $result["file_filename"];
						$file_version["file_filesize"] = (int) $result["file_filesize"];
					}
				}

				if ($file_version && is_array($file_version)) {
					if (@file_exists($download_file = COMMUNITY_STORAGE_DOCUMENTS."/".$file_version["csfversion_id"]) && @is_readable($download_file)) {
						/**
						 * Clear open buffers
						 */
						ob_clear_open_buffers();

						/**
						 * Determine method that the file should be accessed (downloaded or viewed)
						 * and send the proper headers to the client.
						 */
						switch ($file_record["access_method"]) {
							case 1 :
								header("Pragma: public");
								header("Expires: 0");
								header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
								header("Content-Type: ".$file_version["file_mimetype"]);
								header("Content-Disposition: inline; filename=\"".$file_version["file_filename"]."\"");
								header("Content-Length: ".@filesize($download_file));
								header("Content-Transfer-Encoding: binary\n");
							break;
							case 0 :
							default :
								header("Pragma: public");
								header("Expires: 0");
								header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
								header("Content-Type: application/force-download");
								header("Content-Type: application/octet-stream");
								header("Content-Type: ".$file_version["file_mimetype"]);
								header("Content-Disposition: attachment; filename=\"".$file_version["file_filename"]."\"");
								header("Content-Length: ".@filesize($download_file));
								header("Content-Transfer-Encoding: binary\n");
							break;
						}

						if ($LOGGED_IN) {
							add_statistic("community:".$COMMUNITY_ID.":shares", "file_download", "csfile_id", $RECORD_ID);
						}

						echo @file_get_contents($download_file, FILE_BINARY);
						exit;
					}
				}
			}

			if (!has_error() || !has_notice()) {
				add_error("<strong>Unable to download the selected file.</strong><br /><br />The file you have selected cannot be downloaded at this time. ".(($LOGGED_IN) ? "Please try again later." : "Please log in to continue."));
			}

			if (has_notice()) {
				echo display_notice();
			}

			if (has_error()) {
				echo display_error();
			}
		} else {
			if (shares_file_module_access($RECORD_ID, "view-file")) {

				Models_Community_Share::getParentsBreadCrumbs($file_record["cshare_id"]);
				$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-file&id=".$RECORD_ID, "title" => limit_chars($file_record["file_title"], 32));

				$ADD_COMMENT	= shares_module_access($file_record["cshare_id"], "add-comment");
				$ADD_REVISION	= shares_file_module_access($file_record["csfile_id"], "add-revision");
				$MOVE_FILE		= shares_file_module_access($file_record["csfile_id"], "move-file");
				$NAVIGATION		= shares_file_navigation($file_record["cshare_id"], $RECORD_ID);

                $community_shares_select = community_shares_in_select_hierarchy($file_record["cshare_id"], $file_record["parent_folder_id"], $PAGE_ID);
				?>
				<script>
				function commentDelete(id) {
					Dialog.confirm('Do you really wish to deactivate this comment on the '+ $('file-<?php echo $RECORD_ID; ?>-title').innerHTML +' file?<br /><br />If you confirm this action, you will be deactivating this comment.',
						{
							id:				'requestDialog',
							width:			350,
							height:			165,
							title:			'Delete Confirmation',
							className:		'medtech',
							okLabel:		'Yes',
							cancelLabel:	'No',
							closable:		'true',
							buttonClass:	'btn',
							ok:				function(win) {
												window.location = '<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=delete-comment&id='+id;
												return true;
											}
						}
					);
				}
				<?php
				if ($community_shares_select != "") {
                    ?>
				function fileMove(id) {
					Dialog.confirm('Do you really wish to move the '+ $('file-' + id + '-title').innerHTML +' file?<br /><br />If you confirm this action, you will be moving the file and all comments to the selected folder.<br /><br /><?php echo $community_shares_select; ?>',
						{
							id:				'requestDialog',
							width:			350,
							height:			205,
							title:			'Move File',
							className:		'medtech',
							okLabel:		'Yes',
							cancelLabel:	'No',
							closable:		'true',
							buttonClass:	'btn',
							ok:				function(win) {
												window.location = '<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=move-file&id='+id+'&share_id='+$F('share_id');
												return true;
											}
						}
					);
				}
				<?php
				}
				if (shares_file_module_access($RECORD_ID, "delete-revision")) {
					?>
                    function revisionDelete(id) {
                        Dialog.confirm('Do you really wish to deactivate the '+ $('file-version-' + id + '-title').innerHTML +' revision?<br /><br />If you confirm this action, you will no longer be able to download this version of the file.',
                            {
                                id:				'requestDialog',
                                width:			350,
                                height:			125,
                                title:			'Delete Confirmation',
                                className:		'medtech',
                                okLabel:		'Yes',
                                cancelLabel:	'No',
                                closable:		'true',
                                buttonClass:	'btn',
                                ok:				function(win) {
                                                    window.location = '<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=delete-revision&id='+id;
                                                    return true;
                                                }
                            }
                        );
                    }
                    <?php
                }
                ?>
                </script>
                <?php
				/**
				 * If there is time release properties, display them to the browsing users.
				 */
				if (($release_date = (int) $file_record["release_date"]) && ($release_date > time())) {
					add_notice("This file will not be accessible to others until <strong>".date(DEFAULT_DATE_FORMAT, $release_date)."</strong>.");
				} elseif ($release_until = (int) $file_record["release_until"]) {
					if ($release_until > time()) {
						add_notice("This file will be accessible until <strong>".date(DEFAULT_DATE_FORMAT, $release_until)."</strong>.");
					} else {
						/**
						 * Only administrators or people who wrote the post will get this.
						 */
						add_notice("This file was only accessible until <strong>".date(DEFAULT_DATE_FORMAT, $release_until)."</strong> by others.");
					}
				}

                Entrada_Utilities_Flashmessenger::displayMessages($MODULE);

                if ($NAVIGATION && (int) $NAVIGATION["back"]) {
                    $url_back = COMMUNITY_URL . $COMMUNITY_URL . ":" . $PAGE_URL . "?section=view-file&amp;id=" . (int) $NAVIGATION["back"];

                    echo "<a class=\"btn pull-left\" href=\"" . $url_back . "\"><i class=\"icon-chevron-left\"></i></a>";
                }

                if ($NAVIGATION && (int) $NAVIGATION["next"]) {
                    $url_next = COMMUNITY_URL . $COMMUNITY_URL . ":" . $PAGE_URL . "?section=view-file&amp;id=" . (int) $NAVIGATION["next"];

                    echo "<a class=\"btn pull-right\" href=\"" . $url_next . "\"><i class=\"icon-chevron-right\"></i></a>";
                }
                ?>

                <div class="clearfix"></div>

				<a name="top"></a>
				<h1 id="file-<?php echo $RECORD_ID; ?>-title"><?php echo html_encode($file_record["file_title"]); ?></h1>
				<?php
                if (has_notice()) {
                    echo display_notice();
                }
				?>
				<p>
					<?php echo html_encode($file_record["file_description"]); ?>
				</p>
				<div id="file-<?php echo $RECORD_ID; ?>">
					<?php
					$query		= "
								SELECT a.*,  CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `uploader`, b.`username` AS `uploader_username`
								FROM `community_share_file_versions` AS a
								LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
								ON a.`proxy_id` = b.`id`
								WHERE a.`csfile_id` = ".$db->qstr($RECORD_ID)."
								AND a.`cshare_id` = ".$db->qstr($file_record["cshare_id"])."
								AND a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
								AND a.`file_active` = '1'
								ORDER BY a.`file_version` DESC";
					$results	= $db->GetAll($query);
					if ($results) {
						$total_releases	= @count($results);
						echo "<table class=\"table table-bordered no-thead\">\n";
						echo "<colgroup>\n";
						echo "	<col style=\"width: 5%\" />\n";
						echo "	<col style=\"width: 95%\" />\n";
						echo "</colgroup>\n";
						echo "<tbody>\n";
						echo "	<tr>\n";
						echo "		<td style=\"vertical-align: middle\"><a class=\"btn btn-primary\" style=\"height:20px;\" title=\"Save Latest Version\" href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-file&amp;id=".$RECORD_ID."&amp;download=latest\"><i class=\"icon-download icon-white\" style=\"margin-top:3px;\"></i></a></td>";
						echo "		<td style=\"vertical-align: top\">\n";
						echo "			<div id=\"file-download-latest\">\n";
						echo "				<a href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-file&amp;id=".$RECORD_ID."&amp;download=latest\"".(((int) $file_record["access_method"]) ? " target=\"_blank\"" : "").">".(((int) $file_record["access_method"]) ? " View" : "Download")." Latest (v".$results[0]["file_version"].")</a>\n";
						echo "				<div class=\"content-small\">\n";
						echo "					Filename: <span id=\"file-version-".$results[0]["csfversion_id"]."-title\">".html_encode($results[0]["file_filename"])." (v".$results[0]["file_version"].")</span> ".readable_size($results[0]["file_filesize"]);
						if ($total_releases > 1) {
							echo 				((shares_file_version_module_access($results[0]["csfversion_id"], "delete-revision")) ? " (<a class=\"action\" href=\"javascript:revisionDelete('".$results[0]["csfversion_id"]."')\" style=\"font-size: 10px; font-weight: normal\">delete</a>)" : "");
						}
						echo "					<br />\n";
						echo "					Uploaded ".date(DEFAULT_DATE_FORMAT, $results[0]["updated_date"])." by <a href=\"".ENTRADA_URL."/people?profile=".html_encode($results[0]["uploader_username"])."\" style=\"font-size: 10px; font-weight: normal\">".html_encode($results[0]["uploader"])."</a>.<br />";
						echo "				</div>\n";
						echo "			</div>\n";
						echo "		</td>\n";
						echo "	</tr>\n";
						if ($total_releases > 1) {
							echo "<table class=\"table table-striped table-bordered\">\n";
							echo "<thead>\n";
							echo "	<tr>\n";
							echo "		<td style=\"border-left: none\">\n";
							echo "			Older Versions\n";
							echo "		</td>\n";
							echo "	</tr>\n";
							echo "</thead>\n";
							foreach($results as $progress => $result) {
								if ((int) $progress > 0) { // Because I don't want to display the first file again.
									echo "<tr>\n";
									echo "		<td>\n";
									echo "			<a href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-file&amp;id=".$RECORD_ID."&amp;download=".$result["file_version"]."\" style=\"vertical-align: middle\"".(((int) $file_record["access_method"]) ? " target=\"_blank\"" : "")."><span id=\"file-version-".$result["csfversion_id"]."-title\">".html_encode($result["file_filename"])." (v".$result["file_version"].")</span></a> <span class=\"content-small\" style=\"vertical-align: middle\">".readable_size($result["file_filesize"])."</span>\n";
									echo 			((shares_file_version_module_access($result["csfversion_id"], "delete-revision")) ? " (<a class=\"action\" href=\"javascript:revisionDelete('".$result["csfversion_id"]."')\">delete</a>)" : "");
									echo "			<div class=\"content-small\">\n";
									echo "			Uploaded ".date(DEFAULT_DATE_FORMAT, $result["updated_date"])." by <a href=\"".ENTRADA_URL."/people?profile=".html_encode($result["uploader_username"])."\" style=\"font-size: 10px; font-weight: normal\">".html_encode($result["uploader"])."</a>.\n";
									echo "			</div>\n";
									echo "		</td>";
									echo "</tr>\n";
								}
							}
							echo "</table>\n";
						}
						echo "</tbody>\n";
						echo "</table>\n";
					}
					$query		= "
								SELECT a.*, CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `commenter_fullname`, b.`username` AS `commenter_username`
								FROM `community_share_comments` AS a
								LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
								ON b.`id` = a.`proxy_id`
								WHERE a.`proxy_id` = b.`id`
								AND a.`csfile_id` = ".$db->qstr($RECORD_ID)."
								AND a.`cshare_id` = ".$db->qstr($file_record["cshare_id"])."
								AND a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
								AND a.`comment_active` = '1'
								ORDER BY a.`release_date` ASC";
					$results	= $db->GetAll($query);
					$comments	= 0;
					if ($results) {
						if (($ADD_REVISION) || ($ADD_COMMENT) || ($MOVE_FILE)) {
							?>
							<ul class="page-action pull-right space-above">
								<?php if ($ADD_COMMENT) : ?>
								<li><a href="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=add-comment&id=<?php echo $RECORD_ID; ?>" class="btn btn-success">Add File Comment</a></li>
								<?php endif; ?>
								<?php if ($ADD_REVISION) : ?>
								<li><a href="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=add-revision&id=<?php echo $RECORD_ID; ?>" class="btn btn-success">Upload Revised File</a></li>
								<?php endif; ?>
								<?php if (($MOVE_FILE) && ($community_shares_select != "")) : ?>
								<li><a href="javascript:fileMove(<?php echo $RECORD_ID; ?>)">Move File</a></li>
								<?php endif; ?>								
							</ul>
                            <div class="clearfix"></div>
							<?php
						}
						?>
						<h2>File Comments</h2>
						<table class="table" style="width: 100%" cellspacing="0" cellpadding="0" border="0">
						<colgroup>
							<col style="width: 30%" />
							<col style="width: 70%" />
						</colgroup>
						<tbody>
						<?php
						foreach ($results as $result) {
							$comments++;
							?>
							<tr>
								<td style="border-bottom: none; border-right: none">
									<span class="content-small">By:</span> <a href="<?php echo ENTRADA_URL."/people?profile=".html_encode($result["commenter_username"]); ?>" style="font-size: 10px"><?php echo html_encode($result["commenter_fullname"]); ?></a>
								</td>
								<td style="border-bottom: none">
									<div style="float: left">
										<span class="content-small"><strong>Commented:</strong> <?php echo date(DEFAULT_DATE_FORMAT, $result["updated_date"]); ?></span>
									</div>
									<div style="float: right">
									<?php
									echo ((shares_comment_module_access($result["cscomment_id"], "edit-comment")) ? " (<a class=\"action\" href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=edit-comment&amp;id=".$result["cscomment_id"]."\">edit</a>)" : "");
									echo ((shares_comment_module_access($result["cscomment_id"], "delete-comment")) ? " (<a class=\"action\" href=\"javascript:commentDelete('".$result["cscomment_id"]."')\">delete</a>)" : "");
									?>
									</div>
								</td>
							</tr>
							<tr>
								<td colspan="2" class="content">
								<a name="comment-<?php echo (int) $result["cscomment_id"]; ?>"></a>
								<?php
									echo ((trim($result["comment_title"])) ? "<div style=\"font-weight: bold\">".html_encode(trim($result["comment_title"]))."</div>" : "");
									echo $result["comment_description"];

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
					}
					if (($ADD_REVISION) || ($ADD_COMMENT) || ($MOVE_FILE)) {
						?>
						<ul class="page-action pull-right space-above">
							<?php if ($ADD_COMMENT) : ?>
							<li><a href="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=add-comment&id=<?php echo $RECORD_ID; ?>" class="btn btn-success">Add File Comment</a></li>
							<?php endif; ?>
							<?php if ($ADD_REVISION) : ?>
							<li><a href="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=add-revision&id=<?php echo $RECORD_ID; ?>" class="btn btn-success">Upload Revised File</a></li>
							<?php endif; ?>
							<?php if ($MOVE_FILE) : ?>
							<li><a href="javascript:fileMove(<?php echo $RECORD_ID; ?>)" class="btn btn-success">Move File</a></li>
							<?php endif; ?>					
							<li><a class="btn btn-success" href="#top"><i class="icon-chevron-up icon-white"></i></a></li>
						</ul>
                        <div class="clearfix"></div>
						<?php
					}
					?>
				</div>
				<?php
				if ($LOGGED_IN) {
					add_statistic("community:".$COMMUNITY_ID.":shares", "file_view", "csfile_id", $RECORD_ID);
				}
			} else {
				if ($ERROR) {
					echo display_error();
				}
				if ($NOTICE) {
					echo display_notice();
				}
			}
		}
	} else {
		application_log("error", "The provided file id was invalid [".$RECORD_ID."] (View File).");

		header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
		exit;
	}
} else {
	application_log("error", "No file id was provided to view. (View File)");

	header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
	exit;
}
?>