<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to view a particular discussion forum as well as any additional replies
 * that were received for this post.
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

if ($RECORD_ID) {	
    require_once("Classes/users/UserPhoto.class.php");
    require_once("Classes/users/UserPhotos.class.php");
    
	$query			= "SELECT a.*, b.`forum_title`, c.`firstname`, c.`lastname`, CONCAT_WS(' ', c.`firstname`, c.`lastname`) AS `poster_fullname`, c.`username` AS `poster_username`
					FROM `community_discussion_topics` AS a
					LEFT JOIN `community_discussions` AS b
					ON a.`cdiscussion_id` = b.`cdiscussion_id`
					LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS c
					ON a.`proxy_id` = c.`id`
					WHERE a.`proxy_id` = c.`id`
					AND a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
					AND b.`cpage_id` = ".$db->qstr($PAGE_ID)." 
					AND a.`cdtopic_id` = ".$db->qstr($RECORD_ID)."
					AND a.`cdtopic_parent` = '0'
					AND a.`topic_active` = '1'
					AND b.`forum_active` = '1'";
	$topic_record	= $db->GetRow($query);
	if ($topic_record) {
        $create_allowed = (discussions_module_access($topic_record["cdiscussion_id"], "add-post")) ? 1 : 0;
        $read_allowed =  (discussions_module_access($topic_record["cdiscussion_id"], "view-post")) ? 1 : 0; 
        $delete_allowed = (discussion_topic_module_access($topic_record["cdtopic_id"], "delete-post")) ? 1 : 0; 
        $update_allowed = (discussion_topic_module_access($topic_record["cdtopic_id"], "edit-post")) ? 1 : 0; 

        if ($read_allowed) {
            if ((isset($DOWNLOAD)) && ($DOWNLOAD)) {

                if (isset($_GET["cdfile_id"])) {
                    $PROCESSED["cdfile_id"] = clean_input($_GET["cdfile_id"], "trim");
                }

                if (isset($_GET["reply_id"])) {
                    $PROCESSED["reply_id"] = clean_input($_GET["reply_id"], "trim");
                }
                /**
                 * Check for valid permissions before checking if the file really exists.
                 */

                $file_version = false;
                if ((int) $DOWNLOAD) {
                    /**
                     * Check for specified version.
                     */
                    $query	= "
                                    SELECT *
                                    FROM `community_discussion_file_versions`
                                    WHERE `cdtopic_id` = ".$db->qstr($PROCESSED["reply_id"])."
                                    AND `cdfile_id` = ".$db->qstr($PROCESSED["cdfile_id"])."
                                    AND `community_id` = ".$db->qstr($COMMUNITY_ID)."
                                    AND `file_active` = '1'
                                    AND `cdfversion_id` = ".$db->qstr((int) $DOWNLOAD);
                    $result	= $db->GetRow($query);
                    if ($result) {
                            $file_version = array();
                            $file_version["cdfversion_id"] = $result["cdfversion_id"];
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
                                FROM `community_discussion_file_versions`
                                WHERE `cdtopic_id` = ".$db->qstr($PROCESSED["reply_id"])."
                                AND `cdfile_id` = ".$db->qstr($PROCESSED["cdfile_id"])."
                                AND `community_id` = ".$db->qstr($COMMUNITY_ID)."
                                AND `file_active` = '1'
                                ORDER BY `cdfversion_id` DESC
                                LIMIT 0, 1";
                    $result	= $db->GetRow($query);
                    if ($result) {
                            $file_version = array();
                            $file_version["cdfversion_id"] = $result["cdfversion_id"];
                            $file_version["file_mimetype"] = $result["file_mimetype"];
                            $file_version["file_filename"] = $result["file_filename"];
                            $file_version["file_filesize"] = (int) $result["file_filesize"];
                    }
                }

                if (($file_version) && (is_array($file_version))) {
                    if ((@file_exists($download_file = COMMUNITY_STORAGE_DOCUMENTS_DISCUSSION."/".$file_version["cdfversion_id"])) && (@is_readable($download_file))) {
                        /**
                         * This must be done twice in order to close both of the open buffers.
                         */
                        @ob_end_clean();
                        @ob_end_clean();

                        /**
                         * Determine method that the file should be accessed (downloaded or viewed)
                         * and send the proper headers to the client.
                         */

                        switch($file_record["access_method"]) {
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
                            add_statistic("community:".$COMMUNITY_ID.":discussions", "file_download", "cdfile_id", $RECORD_ID);
                        }
                        echo @file_get_contents($download_file, FILE_BINARY);

                        exit;
                    }
                }

                if ((!$ERROR) || (!$NOTICE)) {
                        $ERROR++;
                        $ERRORSTR[] = "<strong>Unable to download the selected file.</strong><br /><br />The file you have selected cannot be downloaded at this time, ".(($LOGGED_IN) ? "please try again later." : "Please log in to continue.");
                }

                if ($NOTICE) {
                        echo display_notice();
                }
                if ($ERROR) {
                    echo display_error();
                }
            }

			$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-forum&id=".$topic_record["cdiscussion_id"], "title" => limit_chars($topic_record["forum_title"], 32));
			$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-post&id=".$RECORD_ID, "title" => limit_chars($topic_record["topic_title"], 32));

			?>
			<script type="text/javascript">
			function postDelete(id, type) {
				if (type && type == 'post') {
					var message = 'Do you really wish to deactivate the '+ $('post-<?php echo $RECORD_ID; ?>-title').innerHTML +' discussion post?<br /><br />If you confirm this action, you will be deactivating this discussion post and any replies.';
				} else {
					var message = 'Do you really wish to deactivate this reply to '+ $('post-<?php echo $RECORD_ID; ?>-title').innerHTML +'?';
				}

				Dialog.confirm(message,
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
											window.location = '<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=delete-post&id='+id;
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
			if (($release_date = (int) $topic_record["release_date"]) && ($release_date > time())) {
				$NOTICE++;
				$NOTICESTR[] = "This discussion post will not be accessible to others until ".date(DEFAULT_DATE_FORMAT, $release_date)."</strong>.";
			} elseif ($release_until = (int) $topic_record["release_until"]) {
				if ($release_until > time()) {
					$NOTICE++;
					$NOTICESTR[] = "This discussion post will be accessible until ".date(DEFAULT_DATE_FORMAT, $release_until)."</strong>.";
				} else {
					/**
					 * Only administrators or people who wrote the post will get this.
					 */
					$NOTICE++;
					$NOTICESTR[] = "This discussion post was only accessible until ".date(DEFAULT_DATE_FORMAT, $release_until)."</strong> by others.";
				}
			}

                //gets the correct picture choosing uploaded first, returns false if permission deney it
                //if false then it uses the generic headshot
                $user_photo = UserPhotos::getPhotoWithPrivacyLevel($topic_record["proxy_id"]);
                if ($user_photo) {
                    $file_path = $user_photo->getFilename();
                } else {
                    $file_path = ENTRADA_URL. "/images/headshot-male.gif";
                }
                
			if ($NOTICE) {
				echo display_notice();
			}
            Entrada_Utilities_Flashmessenger::displayMessages($MODULE);

			?>
			<a name="top"></a>
			<div id="post-<?php echo $RECORD_ID; ?>">                
                <h1 id="post-<?php echo $RECORD_ID; ?>-title"><?php echo html_encode($topic_record["topic_title"]); ?></h1>
				<table class="table post">
				<colgroup>
					<col style="width: 30%" />
					<col style="width: 70%" />
				</colgroup>
				<tbody>
					<tr>
                        <td style="border-bottom: none; border-right: none">
                            <?php if (defined('COMMUNITY_DISCUSSIONS_ANON') && COMMUNITY_DISCUSSIONS_ANON && isset($topic_record["anonymous"]) && $topic_record["anonymous"] && !$COMMUNITY_ADMIN) {?>
                                <img class="img-polaroid db-profile-photo" src='<?php echo ENTRADA_URL; ?>/images/headshot-male.gif' alt="No Photo Available" title="No Photo Available" />
                                <span style="font-size: 10px">Anonymous</span>
                            <?php } else {?>
                                <img class="img-polaroid db-profile-photo" src='<?php echo $file_path;?>' alt='<?php echo html_encode($topic_record["poster_fullname"])?>'/>
                                <span><a href='<?php echo ENTRADA_URL.'/people?profile='.html_encode($topic_record["poster_username"])?>' ><?php echo html_encode($topic_record["firstname"]) . '<br/>' . html_encode($topic_record["lastname"])?></a></span>
                            <?php } ?>
                        </td>
						<td style="border-bottom: none">
							<div style="float: left">
								<span class="content-small">Posted: <?php echo date(DEFAULT_DATE_FORMAT, $topic_record["updated_date"]); ?></span>
							</div>
							<div style="float: right">
							<?php
                                echo (($update_allowed) ? " (<a class=\"action\" href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=edit-post&amp;id=".$RECORD_ID."\">edit</a>)" : "");
                                echo (($delete_allowed) ? " (<a class=\"action\" href=\"javascript:postDelete('".$RECORD_ID."', 'post')\">delete</a>)" : "");
							?>
							</div>
						</td>
					</tr>
					<tr>
						<td colspan="2" class="content">
							<?php echo $topic_record["topic_description"]; ?>
						</td>
					</tr>
                    <?php
                    $query		= "	SELECT a.*, b.`username` AS `owner_username`
                                    FROM `community_discussions_files` AS a
                                    LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
                                    ON a.`proxy_id` = b.`id`
                                    LEFT JOIN `community_discussion_topics` AS c
                                    ON a.`cdtopic_id` = c.`cdtopic_id`
                                    WHERE a.`cdtopic_id` = ".$db->qstr($RECORD_ID)."
                                    AND a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
                                    AND a.`file_active` = '1'
                                    ".((!$COMMUNITY_ADMIN) ? ($LOGGED_IN ? " AND ((a.`proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId()).") OR " : " AND (")."(a.`release_date` = '0' OR a.`release_date` <= ".$db->qstr(time()).") AND (a.`release_until` = '0' OR a.`release_until` > ".$db->qstr(time())."))" : "");
                    $results	= $db->GetAll($query);
                    if ($results) {
                        ?>
                        <tr>
                            <td colspan="2">
                            <?php
                            $fileloop = 0;
                            foreach($results as $result) {
                                $fileloop++;
                                
                                $query = "  SELECT *
                                            FROM `community_discussion_file_versions`
                                            WHERE `cdfile_id` = '".$result["cdfile_id"]."'
                                            AND `file_active` = '1'
                                            ORDER BY `cdfversion_id`
                                            LIMIT 1";
                                $results = $db->GetAll($query);
                                
                                if ($fileloop > 1) {
                                    echo '<br/>';
                                }                                
                                echo '<a href="'.COMMUNITY_URL.$COMMUNITY_URL.':'.$PAGE_URL.'?section=view-post&amp;id='.$RECORD_ID.'&amp;reply_id='.$result["cdtopic_id"].'&amp;cdfile_id='.$result["cdfile_id"].'&amp;download=latest">'.$result["file_title"] . '</a> - ' . formatSizeUnits($results[0]["file_filesize"]);
                                if (isset($result["file_description"]) && $result["file_description"] != "") {
                                    echo '<br/>'.$result["file_description"];
                                }
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
				$query		= "
                                SELECT a.*, b.`firstname`, b.`lastname`, CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `poster_fullname`, b.`username` AS `poster_username`
							FROM `community_discussion_topics` AS a
							LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
							ON a.`proxy_id` = b.`id`
							WHERE a.`proxy_id` = b.`id`
							AND a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
							AND a.`cdtopic_parent` = ".$db->qstr($RECORD_ID)."
							AND a.`topic_active` = '1'
							ORDER BY a.`release_date` ASC";
				$results	= $db->GetAll($query);
				$replies	= 0;
				if ($results) {
                    if ($create_allowed) {
                    ?>
                    <ul class="page-action">
                        <li>
                            <a href="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=reply-post&id=<?php echo $RECORD_ID; ?>" class="btn btn-success">
                                Reply To Post
                            </a>
                        </li>
                        <!--
                        Activate section after email is merged
                        <li>
                            <a href="<?php echo ENTRADA_URL . "/email?section=compose&step=4&subject=" . clean_input($topic_record["topic_title"], "htmlspecialchars") . "&proxy_id=" . $topic_record['proxy_id'] . "&ref=". urlencode(COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-post&id=".$RECORD_ID); ?>" class="btn btn-success">
                                Email Author
                            </a>
                        </li>
                        -->
                    </ul>
                    <?php
					}
					?>
					<h2 style="margin-top:30px; margin-bottom: 0px">Posted Replies</h2>
					<table class="table post">
					<colgroup>
						<col style="width: 30%" />
						<col style="width: 70%" />
					</colgroup>
					<tbody>
					<?php
					foreach($results as $result) {
						$replies++;
                        
                        $delete_allowed = discussion_topic_module_access($result["cdtopic_id"], "delete-post");
                        $update_allowed = discussion_topic_module_access($result["cdtopic_id"], "edit-post");

                        $user_photo = UserPhotos::getPhotoWithPrivacyLevel($result["proxy_id"]);
                        if ($user_photo) {
                            $file_path = $user_photo->getFilename();
                        } else {
                            $file_path = ENTRADA_URL. "/images/headshot-male.gif";
                        }

						?>
						<tr>
                            <td style="border-bottom: none; border-right: none">
                                <?php if (defined('COMMUNITY_DISCUSSIONS_ANON') && COMMUNITY_DISCUSSIONS_ANON && isset($result["anonymous"]) && $result["anonymous"] && !$COMMUNITY_ADMIN) {?>
                                    <img class='db-profile-photo' src='<?php echo ENTRADA_URL; ?>/images/headshot-male.gif' alt='No Photo Available' title='No Photo Available' />
                                    <span style="font-size: 10px">Anonymous</span>
                                <?php } else {?>
                                    <img class='img-polaroid db-profile-photo' src='<?php echo $file_path;?>' alt='<?php echo html_encode($topic_record["poster_fullname"])?>'/>
                                    <span><a href='<?php echo ENTRADA_URL.'/people?profile='.html_encode($result["poster_username"])?>' ><?php echo html_encode($result["firstname"]) . '<br/>' . html_encode($result["lastname"])?></a></span>
                                <?php } ?>
                            </td>
							<td style="border-bottom: none">
								<div style="float: left">
									<span class="content-small">Replied:<?php echo date(DEFAULT_DATE_FORMAT, $result["updated_date"]); ?></span>
								</div>
								<div style="float: right">
								<?php
                                    echo (($update_allowed) ? " (<a class=\"action\" href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=edit-post&amp;id=".$result["cdtopic_id"]."\">edit</a>)" : "");
                                    echo (($delete_allowed) ? " (<a class=\"action\" href=\"javascript:postDelete('".$result["cdtopic_id"]."', 'reply')\">delete</a>)" : "");
								?>
								</div>
							</td>
						</tr>
						<tr>
							<td colspan="2" class="content">
							<a name="post-<?php echo (int) $result["cdtopic_id"]; ?>"></a>
							<?php
								echo $result["topic_description"];
								if ($result["release_date"] != $result["updated_date"]) {
									echo "<div class=\"content-small\" style=\"margin-top: 15px\">\n";
									echo "	Last updated:</strong> ".date(DEFAULT_DATE_FORMAT, $result["updated_date"])." by ".(($result["proxy_id"] == $result["updated_by"]) ? html_encode($result["poster_fullname"]) : html_encode(get_account_data("firstlast", $result["updated_by"]))).".";
									echo "</div>\n";
								}
							?>
							</td>
						</tr>
                        <!-- File attachment on reply -->
                        <?php
                        $query = "SELECT a.*, b.`username` AS `owner_username`
                                  FROM `community_discussions_files` AS a
                                  LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
                                  ON a.`proxy_id` = b.`id`
                                  LEFT JOIN `community_discussion_topics` AS c
                                  ON a.`cdtopic_id` = c.`cdtopic_id`
                                  WHERE a.`cdtopic_id` = ".$db->qstr($result["cdtopic_id"])."
                                  AND a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
                                  AND a.`file_active` = '1'
                                  ".((!$COMMUNITY_ADMIN) ? ($LOGGED_IN ? " AND ((a.`proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId()).") OR " : " AND (")."(a.`release_date` = '0' OR a.`release_date` <= ".$db->qstr(time()).") AND (a.`release_until` = '0' OR a.`release_until` > ".$db->qstr(time())."))" : "");
                        $results = $db->GetAll($query);
                        if($results) {
                        ?>
                        <tr>
                            <td colspan="2">
                            <?php
                            $fileloop = 0;
                            foreach($results as $result) {
                                $fileloop++;
                                $query = "  SELECT *
                                            FROM `community_discussion_file_versions`
                                  WHERE `cdfile_id`='".$result["cdfile_id"]."'
                                            AND `file_active` = '1'
                                  ORDER BY `cdfversion_id`
                                  LIMIT 1";
                                $results = $db->GetAll($query);
                                                               
                                if ($fileloop > 1) {
                                    echo '<br/>';
                                }
                                
                                echo '<a href="'.COMMUNITY_URL.$COMMUNITY_URL.':'.$PAGE_URL.'?section=view-post&amp;id='.$RECORD_ID.'&amp;reply_id='.$result["cdtopic_id"].'&amp;cdfile_id='.$result["cdfile_id"].'&amp;download=latest">'.$result["file_title"] . '</a> - ' . formatSizeUnits($results[0]["file_filesize"]);
                                
                                if (isset($result["file_description"]) && $result["file_description"] != "") {
                                    echo '<br/>'.$result["file_description"];
                                }
                            }
                            ?>
                            </td>
                        </tr>
						<?php
                        }   
                    }
					?>
					</tbody>
					</table>
					<?php
				}
                
                if ($create_allowed) {
                ?>
                    <ul class="page-action">
                        <li>
                            <a href="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=reply-post&id=<?php echo $RECORD_ID; ?>" class="btn btn-success">
                                Reply To Post
                            </a>
                        </li>
                        <!--                        
                        Activate section after email is merged
                        <li>
                            <a href="<?php echo ENTRADA_URL . "/email?section=compose&step=4&subject=" . clean_input($topic_record["topic_title"], "htmlspecialchars") . "&proxy_id=" . $topic_record['proxy_id'] . "&ref=". urlencode(COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-post&id=".$RECORD_ID); ?>" class="btn btn-success">
                                Email Author
                            </a>
                        </li>
                        -->
                        <li>
                            <a href="#top" class="btn btn-success"><i class="icon-chevron-up icon-white"></i></a>
                        </li>
                    </ul>
                <?php
				}
				?>
			</div>
			<?php
			if ($LOGGED_IN) {
				add_statistic("community:".$COMMUNITY_ID.":discussions", "post_view", "cdtopic_id", $RECORD_ID);
			}
		} else {
            add_error("You do not have access to this discussion post.<br /><br />If you believe there has been a mistake, please contact a community administrator for assistance.");
			echo display_error();
			if ($NOTICE) {
				echo display_notice();
			}
		}
	} else {
		application_log("error", "The provided discussion post id was invalid [".$RECORD_ID."] (View Post).");

		header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
		exit;
	}
} else {
	application_log("error", "No discussion post id was provided to view. (View Post)");

	header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
	exit;
}
