<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to view the full size photo within a gallery, as well as display any
 * of the associated comments on that photo.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
*/

if ((!defined("COMMUNITY_INCLUDED")) || (!defined("IN_GALLERIES"))) {
	exit;
} elseif (!$COMMUNITY_LOAD) {
	exit;
}

if ($RECORD_ID) {
	$query			= "	SELECT a.*, b.`gallery_title`, CONCAT_WS(' ', c.`firstname`, c.`lastname`) AS `uploader_fullname`, c.`username` AS `uploader_username`
						FROM `community_gallery_photos` AS a
						LEFT JOIN `community_galleries` AS b
						ON a.`cgallery_id` = b.`cgallery_id`
						LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS c
						ON a.`proxy_id` = c.`id`
						WHERE a.`proxy_id` = c.`id`
						AND a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
						AND a.`cgphoto_id` = ".$db->qstr($RECORD_ID)."
						AND b.`cpage_id` = ".$db->qstr($PAGE_ID)."
						AND a.`photo_active` = '1'
						AND b.`gallery_active` = '1'";
	$photo_record	= $db->GetRow($query);
	if ($photo_record) {
		switch($RENDER) {
			case "image" :
				$display_file = false;

				/**
				 * Check for valid permissions before checking if the file really exists.
				 */
				if (galleries_photo_module_access($RECORD_ID, "view-photo")) {
					if ((@file_exists(COMMUNITY_STORAGE_GALLERIES."/".$RECORD_ID)) && (@is_readable(COMMUNITY_STORAGE_GALLERIES."/".$RECORD_ID))) {
						$display_file = COMMUNITY_STORAGE_GALLERIES."/".$RECORD_ID;
					}
				}

				if (!$display_file) {
					$display_file = COMMUNITY_ABSOLUTE."/templates/".$COMMUNITY_TEMPLATE."/images/galleries-no-photo.gif";
				}

				/**
				 * This must be done twice in order to close both of the open buffers.
				 */
				@ob_clear_open_buffers();

				header("Cache-Control: max-age=2592000");
				header("Content-Type: ".$photo_record["photo_mimetype"]);
				header("Content-Length: ".@filesize($display_file));
				header("Content-Disposition: inline; filename=\"".$photo_record["photo_filename"]."\"");
				header("Content-Transfer-Encoding: binary\n");
				
				echo @file_get_contents($display_file, FILE_BINARY);
				exit;
			break;
			case "thumbnail" :
				$display_file = false;

				/**
				 * Check for valid permissions before checking if the file really exists.
				 */
				if (galleries_photo_module_access($RECORD_ID, "view-photo")) {
					if ((@file_exists(COMMUNITY_STORAGE_GALLERIES."/".$RECORD_ID."-thumbnail")) && (@is_readable(COMMUNITY_STORAGE_GALLERIES."/".$RECORD_ID."-thumbnail"))) {
						$display_file = COMMUNITY_STORAGE_GALLERIES."/".$RECORD_ID."-thumbnail";
					}
				}

				if (!$display_file) {
					$display_file = COMMUNITY_ABSOLUTE."/templates/".$COMMUNITY_TEMPLATE."/images/galleries-no-photo.gif";
				}
				
				/**
				 * This must be done twice in order to close both of the open buffers.
				 */
				@ob_end_clean();
				@ob_end_clean();
				
				header("Cache-Control: max-age=2592000");
				header("Content-Type: ".$photo_record["photo_mimetype"]);
				header("Content-Length: ".@filesize($display_file));
				header("Content-Disposition: inline; filename=\"thumbnail-".$photo_record["photo_filename"]."\"");
				header("Content-Transfer-Encoding: binary\n");
				
				echo @file_get_contents($display_file, FILE_BINARY);
				exit;
			break;
			default :
				if (galleries_photo_module_access($RECORD_ID, "view-photo")) {

					$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-gallery&id=".$photo_record["cgallery_id"], "title" => limit_chars($photo_record["gallery_title"], 32));
					$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-photo&id=".$RECORD_ID, "title" => limit_chars($photo_record["photo_title"], 32));

					$ADD_COMMENT	= galleries_module_access($photo_record["cgallery_id"], "add-comment");
					$NAVIGATION		= galleries_photo_navigation($photo_record["cgallery_id"], $RECORD_ID);

					$community_galleries_select = community_galleries_in_select($photo_record["cgallery_id"]);
					?>
					<script type="text/javascript">
					function photoDelete(id) {
						Dialog.confirm('Do you really wish to remove the '+ $('photo-' + id + '-title').innerHTML +' photo?<br /><br />If you confirm this action, you will be deactivating this photo and any comments.',
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
													window.location = '<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=delete-photo&id='+id;
													return true;
												}
							}
						);
					}

					<?php if ($community_galleries_select != "") : ?>
					function photoMove(id) {
						Dialog.confirm('Do you really wish to move the '+ $('photo-' + id + '-title').innerHTML +' photo?<br /><br />If you confirm this action, you will be moving the photo and all comments to the selected gallery.<br /><br /><?php echo $community_galleries_select; ?>',
							{
								id:				'requestDialog',
								width:			350,
								height:			165,
								title:			'Move Confirmation',
								className:		'medtech',
								okLabel:		'Yes',
								cancelLabel:	'No',
								closable:		'true',
								buttonClass:	'btn',
								ok:				function(win) {
													window.location = '<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=move-photo&id='+id+'&gallery_id='+$F('gallery_id');
													return true;
												}
							}
						);
					}
					<?php endif; ?>

					function commentDelete(id) {
						Dialog.confirm('Do you really wish to deactivate this comment on the '+ $('photo-<?php echo $RECORD_ID; ?>-title').innerHTML +' photo?<br /><br />If you confirm this action, you will be deactivating this comment.',
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
													window.location = '<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=delete-comment&id='+id;
													return true;
												}
							}
						);
					}
					</script>
					<?php
					/**
					 * If there is time release properties, display them to the browsing users.
					 */
					if (($release_date = (int) $photo_record["release_date"]) && ($release_date > time())) {
						$NOTICE++;
						$NOTICESTR[] = "This photo will not be accessible to others until <strong>".date(DEFAULT_DATE_FORMAT, $release_date)."</strong>.";
					} elseif ($release_until = (int) $photo_record["release_until"]) {
						if ($release_until > time()) {
							$NOTICE++;
							$NOTICESTR[] = "This photo will be accessible until <strong>".date(DEFAULT_DATE_FORMAT, $release_until)."</strong>.";
						} else {
							/**
							 * Only administrators or people who wrote the post will get this.
							 */
							$NOTICE++;
							$NOTICESTR[] = "This photo was only accessible until <strong>".date(DEFAULT_DATE_FORMAT, $release_until)."</strong> by others.";
						}
					}

					if ($NOTICE) {
						echo display_notice();
					}
					Entrada_Utilities_Flashmessenger::displayMessages($MODULE);
					?>
					<a name="top"></a>
						<?php
						if ($NAVIGATION) {
							echo "	<table>\n";
							echo "	<tbody>\n";
							echo "		<tr>\n";
							echo "			<td style=\"text-align: left\">\n".(((int) $NAVIGATION["back"]) ? "<a class=\"btn btn-success\" href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-photo&amp;id=".(int) $NAVIGATION["back"]."\"><i class=\"icon-chevron-left icon-white\"></i> Previous Photo</a>" : "&nbsp;")."</td>";
							echo "			<td style=\"text-align: right\">\n".(((int) $NAVIGATION["next"]) ? "<a class=\"btn btn-success\" href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-photo&amp;id=".(int) $NAVIGATION["next"]."\">Next Photo <i class=\"icon-chevron-right icon-white\"></a>" : "&nbsp;")."</td>";
							echo "		</tr>\n";
							echo "	</tbody>\n";
							echo "	</table>\n";
						}
						?>
						<table style="width: 100%">
							<colgroup>
								<col style="width: 20%" />
								<col style="width: 80%" />
							</colgroup>
							<tbody>
								<tr>
									<td colspan="2" style="padding-top: 15px; text-align: center;">
										<?php

										if ((@file_exists(COMMUNITY_STORAGE_GALLERIES."/".$RECORD_ID)) && (@is_readable(COMMUNITY_STORAGE_GALLERIES."/".$RECORD_ID))) {
											$photo_url	= COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-photo&amp;id=".$RECORD_ID."&amp;render=image";
											list($width, $height) = @getimagesize(COMMUNITY_STORAGE_GALLERIES."/".$RECORD_ID);
										} else {
											$photo_url	= COMMUNITY_RELATIVE."/templates/".$COMMUNITY_TEMPLATE."/images/galleries-no-photo.gif";
											$width		= 150;
											$height		= 150;
										}
										?>
										<img src="<?php echo $photo_url; ?>" width="<?php echo (int) $width; ?>" height="<?php echo (int) $height; ?>" alt="<?php echo html_encode($photo_record["photo_title"]); ?>" title="<?php echo html_encode($photo_record["photo_title"]); ?>" />
									</td>
								</tr>
								<tr>
									<td  colspan="2">
										<h2 id="photo-<?php echo $RECORD_ID; ?>-title"><?php echo html_encode($photo_record["photo_title"]); ?></h2>
									</td>
								</tr>
								<tr>
									<td colspan="2">
										<table class="table" style="margin-bottom: 0;">
											<tr>
												<td style="border-right: none"><span class="content-small">By:</span> <a href="<?php echo ENTRADA_URL."/people?profile=".html_encode($photo_record["uploader_username"]); ?>" style="font-size: 10px"><?php echo html_encode($photo_record["uploader_fullname"]); ?></a></td>
												<td>
													<div style="float: left">
														<span class="content-small"><strong>Uploaded:</strong> <?php echo date(DEFAULT_DATE_FORMAT, $photo_record["updated_date"]); ?></span>
													</div>
													<div style="float: right">
														<?php
														echo ((galleries_photo_module_access($RECORD_ID, "edit-photo")) ? " (<a class=\"action\" href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=edit-photo&amp;id=".$RECORD_ID."\">edit</a>)" : "");
														echo ((galleries_photo_module_access($RECORD_ID, "delete-photo")) ? " (<a class=\"action\" href=\"javascript:photoDelete('".$RECORD_ID."')\">delete</a>)" : "");
														if ($community_galleries_select != "") {
															echo ((galleries_photo_module_access($RECORD_ID, "move-photo")) ? " (<a class=\"action\" href=\"javascript:photoMove('".$RECORD_ID."')\">move</a>)" : "");
														}
														?>
													</div>
												</td>
											</tr>
											<tr>
												<td colspan="2">
													<?php echo nl2br(html_encode($photo_record["photo_description"])); ?>
												</td>
											</tr>
										</table>
									</td>
								</tr>
							</tbody>
						</table>
						<?php
						$query		= "	SELECT a.*, CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `commenter_fullname`, b.`username` AS `commenter_username`
										FROM `community_gallery_comments` AS a
										LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
										ON a.`proxy_id` = b.`id`
										WHERE a.`proxy_id` = b.`id`
										AND a.`cgphoto_id` = ".$db->qstr($RECORD_ID)."
										AND a.`cgallery_id` = ".$db->qstr($photo_record["cgallery_id"])."
										AND a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
										AND a.`comment_active` = '1'
										ORDER BY a.`release_date` ASC";
						$results	= $db->GetAll($query);
						$comments	= 0;
						if ($results) {
							if ($ADD_COMMENT) {
								?>
								<ul class="page-action">
									<li><a href="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=add-comment&id=<?php echo $RECORD_ID; ?>" class="btn btn-success">Add Photo Comment</a></li>
								</ul>
								<?php
							}
							?>
							<h2 style="margin-bottom: 0px">Photo Comments</h2>
							<tr>
								<td>
									<table class="table">
										<?php
										foreach($results as $result) {
											$comments++;
											?>
											<tr>
												<td style="border-bottom: none; border-right: none"><span class="content-small">By:</span> <a href="<?php echo ENTRADA_URL."/people?profile=".html_encode($result["commenter_username"]); ?>" style="font-size: 10px"><?php echo html_encode($result["commenter_fullname"]); ?></a></td>
												<td style="border-bottom: none">
													<div style="float: left">
														<span class="content-small"><strong>Commented:</strong> <?php echo date(DEFAULT_DATE_FORMAT, $result["updated_date"]); ?></span>
													</div>
													<div style="float: right">
														<?php
														echo ((galleries_comment_module_access($result["cgcomment_id"], "edit-comment")) ? " (<a class=\"action\" href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=edit-comment&amp;id=".$result["cgcomment_id"]."\">edit</a>)" : "");
														echo ((galleries_comment_module_access($result["cgcomment_id"], "delete-comment")) ? " (<a class=\"action\" href=\"javascript:commentDelete('".$result["cgcomment_id"]."')\">delete</a>)" : "");
														?>
													</div>
												</td>
											</tr>
											<tr>
												<td colspan="2" class="content">
												<a name="comment-<?php echo (int) $result["cgcomment_id"]; ?>"></a>
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
									</table>
								</td>
							</tr>
							<?php
						}
						if ($ADD_COMMENT) {
							?>
							<ul class="page-action">
								<li><a href="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=add-comment&id=<?php echo $RECORD_ID; ?>" class="btn btn-success">Add Photo Comment</a></li>
								<li  style="padding-right: 0px"><a href="#top" class="btn btn-success pull-right"><i class="icon-chevron-up icon-white"></i></a></li>
							</ul>
							<?php
						}
						?>
					<?php
				} else {
					if ($ERROR) {
						echo display_error();
					}
					if ($NOTICE) {
						echo display_notice();
					}
				}
			break;
		}
		if ($LOGGED_IN) {
			add_statistic("community:".$COMMUNITY_ID.":galleries", "photo_view", "cgphoto_id", $RECORD_ID);
		}
	} else {
		application_log("error", "The provided photo id was invalid [".$RECORD_ID."] (View Photo).");

		header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
		exit;
	}
} else {
	application_log("error", "No photo id was provided to view. (View Photo)");

	header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
	exit;
}
?>