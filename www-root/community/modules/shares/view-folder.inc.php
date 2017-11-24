<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to list the files that exist within the specified folder.
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
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/wizard.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[] = "<link href=\"".ENTRADA_URL."/css/wizard.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/jquery/jquery.dataTables.min.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";

if ($RECORD_ID) {
    //checks the role of the user and sets hidden to true if they're not a facluty, staff, or medtech memeber
    //used to control access to files if they're marked hidden from students
    $group = $ENTRADA_USER->getActiveGroup();
    if ($group == 'faculty' || $group == 'staff'  || $group == 'medtech') {
        $hidden = false;
    } else {
        $hidden = true;
    }
    $query            = "   SELECT * 
                            FROM `community_shares` 
                            WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)." 
                            AND `cshare_id` = ".$db->qstr($RECORD_ID).
                            ($hidden ? "AND `student_hidden` = '0'" : "");
	$folder_record	= $db->GetRow($query);
	if ($folder_record) {
		if (shares_module_access($RECORD_ID, "view-folder")) {

            Models_Community_Share::getParentsBreadCrumbs($folder_record["parent_folder_id"]);

			$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-folder&id=".$RECORD_ID, "title" => $folder_record["folder_title"]);

			/**
			 * Update requested sort column.
			 * Valid: date, title
			 */
			if (isset($_GET["sb"])) {
				if (@in_array(trim($_GET["sb"]), array("date", "title", "owner"))) {
					$_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["sb"] = trim($_GET["sb"]);
				}

				$_SERVER["QUERY_STRING"]	= replace_query(array("sb" => false));
			} else {
				if (!isset($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["sb"])) {
					$_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["sb"] = "date";
				}
			}

			/**
			 * Update requested order to sort by.
			 * Valid: asc, desc
			 */
			if (isset($_GET["so"])) {
				$_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"] = ((strtolower($_GET["so"]) == "desc") ? "desc" : "asc");

				$_SERVER["QUERY_STRING"]	= replace_query(array("so" => false));
			} else {
				if (!isset($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"])) {
					$_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"] = "desc";
				}
			}

			/**
			 * Update requsted number of rows per page.
			 * Valid: any integer really.
			 */
			if ((isset($_GET["pp"])) && ((int) trim($_GET["pp"]))) {
				$integer = (int) trim($_GET["pp"]);

				if (($integer > 0) && ($integer <= 250)) {
					$_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"] = $integer;
				}

				$_SERVER["QUERY_STRING"] = replace_query(array("pp" => false));
			} else {
				if (!isset($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"])) {
					$_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"] = 15;
				}
			}

			/**
			 * Provide the queries with the columns to order by.
			 */
			switch($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["sb"]) {
				case "title" :
                    $SORT_BY_FOLDER   = "a.`folder_title` ".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"]).", a.`updated_date` DESC";
                    $SORT_BY_FILES    = "`title` ".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"]).", `updated_date` DESC";
				break;
				case "owner" :
                    $SORT_BY_FILES    = "`owner` ".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"]).", `updated_date` DESC";
                    $SORT_BY_FOLDER   = $SORT_BY_FILES;
				break;
				case "date" :
				default :
                    $SORT_BY_FILES    = "`updated_date` ".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"]);
                    $SORT_BY_FOLDER   = $SORT_BY_FILES;
				break;
			}

			/**
			 * Check if pv variable is set and see if it's a valid page, other wise page 1 it is.
			 */
			if (isset($_GET["pv"])) {
				$PAGE_CURRENT = (int) trim($_GET["pv"]);

				if (($PAGE_CURRENT < 1) || (isset($TOTAL_PAGES) && !empty($TOTAL_PAGES) && $PAGE_CURRENT > $TOTAL_PAGES)) {
					$PAGE_CURRENT = 1;
				}
			} else {
				$PAGE_CURRENT = 1;
			}

			/**
			 * Get the total number of results using the generated queries above and calculate the total number
			 * of pages that are available based on the results per page preferences.
			 */
            
            //selects all the immediate sub folders           
            $query_total    = "
                    SELECT a.`cshare_id` AS `ID`, 'folder' AS `type`
                                FROM `community_shares` as a
                                WHERE a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
                                AND a.`parent_folder_id` = ".$db->qstr($RECORD_ID)."
                                AND a.`folder_active` = '1'
                                ".((!$LOGGED_IN) ? " AND a.`allow_public_read` = '1'" : (($COMMUNITY_MEMBER) ? ((!$COMMUNITY_ADMIN) ? " AND a.`allow_member_read` = '1'" : "") : " AND a.`allow_troll_read` = '1'"))."
                                ".((!$COMMUNITY_ADMIN) ? ($LOGGED_IN ? " AND ( " : " AND (")."(a.`release_date` = '0' OR a.`release_date` <= ".$db->qstr(time()).") AND (a.`release_until` = '0' OR a.`release_until` > ".$db->qstr(time())."))" : "").
                                ($hidden ? "AND a.`student_hidden` = '0'" : "");              
                
            $query_total    .= "
                    UNION ALL
					SELECT a.`csfile_id` AS `ID`, 'file' AS `type`
					FROM `community_share_files` AS a
					LEFT JOIN `community_shares` AS c
					ON a.`cshare_id` = c.`cshare_id`
					WHERE a.`cshare_id` = ".$db->qstr($RECORD_ID)."
					AND a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
					AND c.`cpage_id` = ".$db->qstr($PAGE_ID)."
					AND a.`file_active` = '1'";
            
            $query_total    .= "
                    UNION ALL
                    SELECT a.`cslink_id` AS `ID`, 'link' AS `type`
                    FROM `community_share_links` AS a
                    LEFT JOIN `community_shares` AS c
                    ON a.`cshare_id` = c.`cshare_id`
                    WHERE a.`cshare_id` = ".$db->qstr($RECORD_ID)."
                    AND a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
                    AND c.`cpage_id` = ".$db->qstr($PAGE_ID)."
                    AND a.`link_active` = '1'";   

            $query_total    .= "
                    UNION ALL
                    SELECT a.`cshtml_id` AS `ID`, 'html' AS `type`
                    FROM `community_share_html` AS a
                    LEFT JOIN `community_shares` AS c
                    ON a.`cshare_id` = c.`cshare_id`
                    WHERE a.`cshare_id` = ".$db->qstr($RECORD_ID)."
                    AND a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
                    AND c.`cpage_id` = ".$db->qstr($PAGE_ID)."
                    AND a.`html_active` = '1'";

            $active_results = $db->GetAll($query_total);

            if (isset($active_results)) {
                if (is_array($active_results)) {
                    $result = array_filter($active_results, function($item) {
                        if ($item['type'] === 'folder') {
                            return shares_module_access($item['ID'], "view-folder");
                        } else if ($item['type'] === 'file') {
                            return shares_file_module_access($item['ID'], "view-file");
                        } else if ($item['type'] === 'link') {
                            return shares_link_module_access($item['ID'], "view-link");
                        } else if ($item['type'] === 'html') {
                            return shares_html_module_access($item['ID'], "view-html");
                        }
                        return false;
                    });
                }
            }

            $rowCountTotal = count($result);
            if ($result) {
                $TOTAL_ROWS    = $rowCountTotal;

				if ($TOTAL_ROWS <= $_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"]) {
					$TOTAL_PAGES = 1;
				} elseif (($TOTAL_ROWS % $_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"]) == 0) {
					$TOTAL_PAGES = (int) ($TOTAL_ROWS / $_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"]);
				} else {
					$TOTAL_PAGES = (int) ($TOTAL_ROWS / $_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"]) + 1;
				}

				if ($TOTAL_PAGES > 1) {
					$pagination = new Entrada_Pagination($PAGE_CURRENT, $_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"], $TOTAL_ROWS, COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL, replace_query());
				}
			} else {
                $TOTAL_ROWS        = 0;
				$TOTAL_PAGES = 1;
			}

			$PAGE_PREVIOUS = (($PAGE_CURRENT > 1) ? ($PAGE_CURRENT - 1) : false);
			$PAGE_NEXT = (($PAGE_CURRENT < $TOTAL_PAGES) ? ($PAGE_CURRENT + 1) : false);

			/**
			 * Provides the first parameter of MySQLs LIMIT statement by calculating which row to start results from.
			 */
			$limit_parameter = (int) ($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"] * ($PAGE_CURRENT - 1));

            /**
             * Add the javascript for deleting forums.
             */
            if (communities_module_access($COMMUNITY_ID, $MODULE_ID, "delete-folder")) {
                ?>
                <script type="text/javascript">
                    function folderDelete(id) {
                        Dialog.confirm('Do you really wish to remove the '+ $('folder-' + id + '-title').innerHTML +' folder from this community?<br /><br />If you confirm this action, you will be deactivating the folder and all files within it.',
                            {
                                id:             'requestDialog',
                                width:          350,
                                height:         125,
                                title:          'Delete Confirmation',
                                className:      'medtech',
                                okLabel:        'Yes',
                                cancelLabel:    'No',
                                closable:       'true',
                                buttonClass:    'btn',
                                ok:             function(win) {
                                                    window.location = '<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=delete-folder&id='+id;
                                                    return true;
                                                }
                            }
                        );
                    }
                </script>
            <?php
            }
                $community_shares_select_folder = community_shares_in_select_hierarchy($folder_record["cshare_id"], $folder_record["parent_folder_id"], $PAGE_ID, "folder");
                $community_shares_select_documents = community_shares_in_select_hierarchy($folder_record["cshare_id"], $folder_record["parent_folder_id"], $PAGE_ID, "documents");                
			?>
			<script type="text/javascript">
				function fileDelete(id) {
					Dialog.confirm('Do you really wish to remove the '+ $('file-' + id + '-title').innerHTML +' file?<br /><br />If you confirm this action, you will be deactivating this file and any comments.',
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
												window.location = '<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=delete-file&id='+id;
												return true;
											}
						}
					);
				}

                function linkDelete(id) {
                    Dialog.confirm('Do you really wish to remove the '+ $('link-' + id + '-title').innerHTML +' link?<br /><br />If you confirm this action, you will be deactivating this link and any comments.',
                        {
                            id:             'requestDialog',
                            width:          350,
                            height:         165,
                            title:          'Delete Confirmation',
                            className:      'medtech',
                            okLabel:        'Yes',
                            cancelLabel:    'No',
                            closable:       'true',
                            buttonClass:    'btn',
                            ok:             function(win) {
                                                window.location = '<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=delete-link&id='+id;
                                                return true;
                                            }
                        }
                    );
                }                

                function htmlDelete(id) {
                    Dialog.confirm('Do you really wish to remove the '+ $('html-' + id + '-title').innerHTML +' html document?<br /><br />If you confirm this action, you will be deactivating this html document and any comments.',
                        {
                            id:             'requestDialog',
                            width:          350,
                            height:         165,
                            title:          'Delete Confirmation',
                            className:      'medtech',
                            okLabel:        'Yes',
                            cancelLabel:    'No',
                            closable:       'true',
                            buttonClass:    'btn',
                            ok:             function(win) {
                                                window.location = '<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=delete-html&id='+id;
                                                return true;
                                            }
                        }
                    );
                }    

                <?php
                if ($community_shares_select_folder || $community_shares_select_documents) {
                ?>
                var community_shares_select_folder = '<?php echo $community_shares_select_folder; ?>';
                var community_shares_select_documents = '<?php echo $community_shares_select_documents; ?>';
                var current_share_id = <?php echo $RECORD_ID;?>;
				function fileMove(id) {
					Dialog.confirm('Do you really wish to move the '+ $('file-' + id + '-title').innerHTML +' file?<br /><br />If you confirm this action, you will be moving the file and all comments to the selected folder.<br /><br />' + community_shares_select_documents,
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
												window.location = '<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=move-file&id='+id+'&share_id='+$F('share_id')+'&current_share_id=' + current_share_id;
												return true;
											}
						}
					);
				}
                function linkMove(id) {
                    Dialog.confirm('Do you really wish to move the '+ $('link-' + id + '-title').innerHTML +' link?<br /><br />If you confirm this action, you will be moving the link to the selected folder.<br /><br />' + community_shares_select_documents,
                        {
                            id:             'requestDialog',
                            width:          350,
                            height:         205,
                            title:          'Move Link',
                            className:      'medtech',
                            okLabel:        'Yes',
                            cancelLabel:    'No',
                            closable:       'true',
                            buttonClass:    'btn',
                            ok:             function(win) {
                                                window.location = '<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=move-link&id='+id+'&share_id='+$F('share_id')+'&current_share_id=' + current_share_id;
                                                return true;
                                            }
                        }
                    );
                }
                function htmlMove(id) {
                    Dialog.confirm('Do you really wish to move the '+ $('html-' + id + '-title').innerHTML +' html?<br /><br />If you confirm this action, you will be moving the html document to the selected folder.<br /><br />' + community_shares_select_documents,
                        {
                            id:             'requestDialog',
                            width:          350,
                            height:         205,
                            title:          'Move HTML',
                            className:      'medtech',
                            okLabel:        'Yes',
                            cancelLabel:    'No',
                            closable:       'true',
                            buttonClass:    'btn',
                            ok:             function(win) {
                                                window.location = '<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=move-html&id='+id+'&share_id='+$F('share_id')+'&current_share_id=' + current_share_id;
                                                return true;
                                            }
                        }
                    );
                }
                function folderMove(id) {
                    var community_shares_select_clean_doc = community_shares_select_documents.replace('value="' + id + '"', 'value="' + id + '" disabled="disabled"');
                    Dialog.confirm('Do you really wish to move the folder: <strong>'+ $('folder-' + id + '-title').innerHTML +'</strong>?<br /><br />If you confirm this action, you will be moving the folder to the selected folder.<br /><br />' + community_shares_select_clean_doc,
                        {
                            id:             'requestDialog',
                            width:          350,
                            height:         205,
                            title:          'Move Folder',
                            className:      'medtech',
                            okLabel:        'Yes',
                            cancelLabel:    'No',
                            closable:       'true',
                            buttonClass:    'btn',
                            ok:             function(win) {
                                                window.location = '<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=move-folder&id='+id+'&share_id='+$F('share_id')+'&current_share_id=' + current_share_id;
                                                return true;
                                            }
                        }
                    );
                }
                
                <?php
                };
                ?>
			</script>
            <style>
                div.share-notifications div#notifications-toggle {
                    display: none;
                }
                div.content {
                    overflow: visible;
                }
            </style>
            <?php
                Entrada_Utilities_Flashmessenger::displayMessages($MODULE);
            ?>
            <h1><?php echo html_encode($folder_record["folder_title"]); ?></h1>
            <div class="module-subTitle">
				<?php echo nl2br(html_encode($folder_record["folder_description"])); ?>
			</div>
            <?php
            if ($TOTAL_PAGES > 1) {
                echo $pagination->GetPageBar("normal", "right");
            }
            ?>
            <div class="space-below">
                <?php
                if (COMMUNITY_NOTIFICATIONS_ACTIVE && $LOGGED_IN && $_SESSION["details"]["notifications"]) {
                    ?>
                    <div id="notifications-toggle"></div>
                    <script>
                        function promptNotifications(enabled) {
                            Dialog.confirm('Do you really wish to '+ (enabled == 1 ? "stop" : "begin") +' receiving notifications for new files on this page?',
                                {
                                    id:				'requestDialog',
                                    width:			350,
                                    height:			95,
                                    title:			'Notification Confirmation',
                                    className:		'medtech',
                                    okLabel:		'Yes',
                                    cancelLabel:	'No',
                                    closable:		'true',
                                    buttonClass:	'btn',
                                    destroyOnClose:	true,
                                    ok:				function(win) {
                                        new Window(	{
                                                id:				'resultDialog',
                                                width:			350,
                                                                height:			75,
                                                title:			'Notification Result',
                                                className:		'medtech',
                                                okLabel:		'close',
                                                buttonClass:	'btn',
                                                resizable:		false,
                                                draggable:		false,
                                                minimizable:	false,
                                                maximizable:	false,
                                                recenterAuto:	true,
                                                destroyOnClose:	true,
                                                url:			'<?php echo ENTRADA_URL."/api/notifications.api.php?community_id=".$COMMUNITY_ID."&id=".$RECORD_ID; ?>&type=file&action=edit&active='+(enabled == 1 ? '0' : '1'),
                                                onClose:			function () {
                                                    new Ajax.Updater('notifications-toggle', '<?php echo ENTRADA_URL."/api/notifications.api.php?community_id=".$COMMUNITY_ID."&id=".$RECORD_ID; ?>&type=file&action=view');
                                                }
                                            }
                                        ).showCenter();
                                        return true;
                                    }
                                }
                            );
                        }
                    </script>
                    <?php
                    $ONLOAD[] = "new Ajax.Updater('notifications-toggle', '".ENTRADA_URL."/api/notifications.api.php?community_id=".$COMMUNITY_ID."&id=".$RECORD_ID."&type=file&action=view')";
                }
                ?>
				<div style="float: right; padding-top: 10px;">
					<ul class="page-action">
						<?php
                        if (shares_module_access($RECORD_ID, "add-file")) { ?>
                            <li><a href="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=add-file&amp;id=<?php echo $RECORD_ID; ?>" class="btn btn-success">Upload File</a></li>
                            <li><a href="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=add-link&amp;id=<?php echo $RECORD_ID; ?>" class="btn btn-success">Add Link</a></li>
                            <li><a href="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=add-html&amp;id=<?php echo $RECORD_ID; ?>" class="btn btn-success">Add HTML</a></li>
						<?php }
                        if (communities_module_access($COMMUNITY_ID, $MODULE_ID, "add-folder")) {
                        ?>
                            <li><a href="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=add-folder&amp;id=<?php echo $RECORD_ID; ?>" class="btn btn-success">Add Folder</a></li>
                            <li><a href="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=edit-folder&amp;id=<?php echo $RECORD_ID; ?>" class="btn btn-primary">Edit Folder</a></li>
                        <?php
                    }
                    ?>
					</ul>
                </div>
				<div style="clear: both"></div>
				<?php
                //selects all the immediate sub folders
                $query_sub            = "   SELECT a.* , CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `owner`, b.`username` AS `username`
                                            FROM `community_shares` as a
                                            LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
                                            ON a.`updated_by` = b.`id`
                                            WHERE a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
                                            AND a.`parent_folder_id` = ".$db->qstr($RECORD_ID)."
                                            AND a.`folder_active` = '1'
                                            ".($hidden ? "AND a.`student_hidden` = '0'" : "")."
                                            ORDER BY %s";
                $query_sub        = sprintf($query_sub, $SORT_BY_FOLDER);
                $folder_record_sub_all = array_filter($db->GetAll($query_sub), function($item) {
                    return shares_module_access($item['cshare_id'], "view-folder");
                });
                //Can't use SQL's LIMIT syntax to get the current page's folders,
                //since the query might return folders that the user doesn't have
                //access to.
                $folder_record_sub = array_slice($folder_record_sub_all, $limit_parameter, (int)$_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"]);

                //selects the files
                $query        = " SELECT 'file' as type, a.`csfile_id` AS `ID`, a.`cshare_id`, a.`community_id`, a.`proxy_id`,
                                a.`file_title` AS `title`, a.`file_description` AS `description`, a.`file_active` AS `active`, 'file' AS url,
                                a.`allow_member_revision`, a.`allow_troll_revision`, a.`access_method`, a.`student_hidden`, a.`release_date`, a.`release_until`, a.`updated_date`, a.`updated_by`, a.`notify`,
                                CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `owner`, b.`username` AS `owner_username`
                                FROM `community_share_files` AS a
                                LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
                                ON a.`proxy_id` = b.`id`
                                LEFT JOIN `community_shares` AS c
                                ON a.`cshare_id` = c.`cshare_id`
                                WHERE a.`cshare_id` = ".$db->qstr($RECORD_ID).
                                ($hidden ? "AND a.`student_hidden` = '0'" : "")."
								AND a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
								AND a.`file_active` = '1'";
                //selects the links
                $query       .= "UNION ALL
                                SELECT 'link' as type, a.`cslink_id` AS `ID`, a.`cshare_id`, a.`community_id`, a.`proxy_id`,
                                a.`link_title` AS `title`, a.`link_description` AS `description`, a.`link_active` AS `active`, a.`link_url` AS `url`,
                                a.`allow_member_revision`, a.`allow_troll_revision`, a.`access_method`, a.`student_hidden`, a.`release_date`, a.`release_until`, a.`updated_date`, a.`updated_by`, a.`notify`,
                                CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `owner`, b.`username` AS `owner_username`
                                FROM `community_share_links` AS a
                                LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
                                ON a.`proxy_id` = b.`id`
                                LEFT JOIN `community_shares` AS c
                                ON a.`cshare_id` = c.`cshare_id`
                                WHERE a.`cshare_id` = ".$db->qstr($RECORD_ID).
                                ($hidden ? "AND a.`student_hidden` = '0'" : "")."
                                AND a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
                                AND a.`link_active` = '1'";
                $query       .= "UNION ALL
                                SELECT 'html' as type, a.`cshtml_id` AS `ID`, a.`cshare_id`, a.`community_id`, a.`proxy_id`,
                                a.`html_title` AS `title`, a.`html_description` AS `description`, a.`html_active` AS `active`, 'html' AS `url`,
                                a.`allow_member_read`, a.`allow_troll_read`, a.`access_method`, a.`student_hidden`, a.`release_date`, a.`release_until`, a.`updated_date`, a.`updated_by`, a.`notify`,
                                CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `owner`, b.`username` AS `owner_username`
                                FROM `community_share_html` AS a
                                LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
                                ON a.`proxy_id` = b.`id`
                                LEFT JOIN `community_shares` AS c
                                ON a.`cshare_id` = c.`cshare_id`
                                WHERE a.`cshare_id` = ".$db->qstr($RECORD_ID).
                                ($hidden ? "AND a.`student_hidden` = '0'" : "")."
                                AND a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
                                AND a.`html_active` = '1'";
                $query .=       "ORDER BY %s";
                $query        = sprintf($query, $SORT_BY_FILES);

                $results	= $db->GetAll($query);
                //Filter out the files and links the user does not have access to

                if ($results && is_array($results)) {
                    $document_results_all = array_filter($results, function($item) {
                        if ($item['type'] === 'file') {
                            return shares_file_module_access($item['ID'], "view-file");
                        } else if ($item['type'] === 'link') {
                            return shares_link_module_access($item['ID'], "view-link");
                        } else if ($item['type'] === 'html') {
                            return shares_html_module_access($item['ID'], "view-html");
                        }
                        return false;
                    });

                    $document_results = array_slice($document_results_all, $limit_parameter, (int)$_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"]);
                }

                if ($document_results || $folder_record_sub) {
                    ?>
                    <table class="table table-striped table-bordered">
                        <?php if ($COMMUNITY_ADMIN) { ?>
                        <colgroup>
                            <col style="width: 60%" />
                            <col style="width: 18%" />
                            <col style="width: 18%" />
                            <col style="width: 7%" />
                        </colgroup>
                        <?php } else { ?>
                        <colgroup>
                            <col style="width: 55%" />
                            <col style="width: 20%" />
						    <col style="width: 21%" />
                        </colgroup>
                        <?php } ?>
                        <thead>
                            <tr>
                                <td <?php echo (($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["sb"] == "title") ? " class=\"sorted".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"])."\"" : ""); ?> >
                                    <?php echo communities_order_link("title", "Title"); ?>
                                </td>
                                <td<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["sb"] == "owner") ? " class=\"sorted".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"])."\"" : ""); ?> style="border-left: none">
                                    <?php echo communities_order_link("owner", "Owner"); ?>
                                </td>
                                <td<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["sb"] == "date") ? " class=\"sorted".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"])."\"" : ""); ?> style="border-left: none">
                                    <?php echo communities_order_link("date", "Last Updated"); ?>
                                </td>
                                <?php
                                if ($COMMUNITY_ADMIN) {
                                    ?>
                                    <td>
                                        Views
                                    </td>
                                    <?php
                                }
                                ?>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        //if there are subfolders then echos out the subfolders
                        if ($folder_record_sub) {
                            foreach ($folder_record_sub as $folder_sub) {
                            // if (static::isAllowed($community_id, $folder, "read", $is_community_course))
                                $is_allowed = Models_Community_Share::isAllowed($COMMUNITY_ID, $folder_sub, "read");
    
                                if ($is_allowed == true) {
                                    // $folder_sub
                                    $cumulative_count = Models_Community_Share::getFilesLinksCumulativeCount($folder_sub["cshare_id"]);
                                    $accessible	= true;
                                    $student_hidden_folder   = $folder_sub["student_hidden"];
                                    if ((($folder_sub["release_date"]) && ($folder_sub["release_date"] > time())) || (($folder_sub["release_until"]) && ($folder_sub["release_until"] < time()))) {
                                        $accessible = false;
                                        $student_hidden_folder = true;
                                    }
    
                                    /*
                                     * Models_Community_Share::getEditMenu expects the $resource to have
                                     * an 'id' and a 'type', so we must set these first.
                                     */
                                    $folder_sub["id"] = $folder_sub["cshare_id"];
                                    $folder_sub["type"] = "folder";
                                    $menu = Models_Community_Share::getEditMenu($COMMUNITY_ID, $folder_sub);
                                    echo "<tr>\n";
                                        echo "<td style=\"vertical-align: top\">\n";
                                            echo "<img src=\"".ENTRADA_URL."/community/templates/course/images/list-folder-".$folder_sub["folder_icon"].($student_hidden_folder ? "-hidden" : "").".gif\" width=\"16\" height=\"16\" style=\"vertical-align: middle; margin-right: 4px\" />";
                                            echo "<a " . ($student_hidden_folder ? "class='hidden_shares'" : "") ."id=\"folder-".(int) $folder_sub["cshare_id"]."-title\" href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-folder&amp;id=".$folder_sub["cshare_id"]."\" style=\"font-weight: bold; vertical-align: middle\">".limit_chars(html_encode($folder_sub["folder_title"]), 50, true)."</a>\n";
                                            echo (!$accessible) ? "<span><i class='icon-time'></i> </span>" : "";
                                            echo $menu;
                                            echo "<div>";
                                            echo "<span class='content-small'>(" . $cumulative_count['total_docs'] . " documents)</span>";
                                            echo "<div class='content-small'>".(($folder_sub["folder_description"] != "") ? html_encode(limit_chars($folder_sub["folder_description"], 125)) : "")."</div>";
                                        echo "</td>\n";
                                        echo "<td style=\"font-size: 10px; white-space: nowrap; overflow: hidden\"><a href=\"".ENTRADA_URL."/people?profile=".html_encode($folder_sub["username"])."\" style=\"font-size: 10px\">".html_encode($folder_sub["owner"])."</a></td>\n";
                                        echo "<td style=\"font-size: 10px; white-space: nowrap; overflow: hidden\">".date(DEFAULT_DATE_FORMAT, $folder_sub["updated_date"])."</td>\n";
                                        if ($COMMUNITY_ADMIN) {
                                            echo "<td class=\"accesses\" style=\"text-align: center\"><strong>-</strong></td>";
                                        }
                                    echo "</tr>\n";
                                }
                            }
                        }

                        if ($document_results) {
                            foreach ($document_results as $document_result) {
                                $accessible	= true;
                                $parts          = pathinfo($document_result["title"]);
                                //get the extension from the mime type
                                $ext = Models_Community_Share::getExtension($document_result['ID'], $document_result['type'], $document_result['student_hidden']);
                                $student_hidden = $document_result["student_hidden"];
                                if ((($document_result["release_date"]) && ($document_result["release_date"] > time())) || (($document_result["release_until"]) && ($document_result["release_until"] < time()))) {
                                    $accessible = false;
                                    $student_hidden = true;
                                }
                                /**
                                 * Models_Community_Share::getEditMenu() expects the $resource to
                                 * have an 'id', so we must set this first.
                                 */
                                $document_result["id"] = $document_result["ID"];
                                $document_menu = Models_Community_Share::getEditMenu($COMMUNITY_ID, $document_result);

                                if ($document_result['type'] == 'file') {
                                    $params = array(
                                        "action"        => "file_download",
                                        "action_field"  => "csfile_id",
                                        "action_value"  => $document_result["ID"],
                                        "module"        => "community:" . $COMMUNITY_ID . ":shares"
                                    );

                                // get the time the last version was edited

                                    $file_versions = Models_Community_Share_File_Version::fetchAllByCSFile_ID($document_result["id"]);
                                    if ($file_versions && is_array($file_versions)) {
                                        $version_count = count($file_versions);
                                        $latest_version = $file_versions[$version_count - 1];
                                        $last_updated = $latest_version->getUpdatedDate();
                                    }

                                    $statistics = Models_Statistic::getCountByParams($params);
                                    $download_button = Models_Community_Share_File::getDownloadButton($document_result["ID"]);
                                    echo "<tr>\n";
                                        echo "<td style=\"vertical-align: top\">\n";
                                            //echo "<img src=\"".ENTRADA_URL."/serve-icon.php?ext=" . $ext['ext'] . "&hidden=" . $student_hidden . "\" width=\"16\" height=\"16\" alt=\"" . $ext['english'] . " \" title=\"" . $ext['english'] . " \" style=\"vertical-align: middle; margin-right: 4px\" />";
                                            echo $document_menu;
                                            echo "<a " . ($student_hidden ? "class='hidden_shares'" : "") . "id=\"file-".(int) $document_result["ID"]."-title\" href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-file&amp;id=".$document_result["ID"]."\" style=\"font-weight: bold; vertical-align: middle\">".limit_chars(html_encode($document_result["title"]), 47, true)."</a>\n";
                                            echo $download_button;
                                            echo (!$accessible) ? "<span><i class='icon-time'></i> </span>" : "";
                                            echo "<div  class=\"content-small\" style=\"padding-left: 23px\">".html_encode(limit_chars($document_result["description"], 125))."</div>";
                                        echo "</td>\n";
                                            echo "<td style=\"font-size: 10px; white-space: nowrap; overflow: hidden\"><a href=\"".ENTRADA_URL."/people?profile=".html_encode($document_result["owner_username"])."\" style=\"font-size: 10px\">".html_encode($document_result["owner"])."</a></td>\n";
                                        echo "<td style=\"font-size: 10px; white-space: nowrap; overflow: hidden\">".date(DEFAULT_DATE_FORMAT, $last_updated)."</td>\n";
                                        if ($COMMUNITY_ADMIN) {
                                            if ($statistics['views'] && $statistics['views'] != '0') {
                                                echo "<td class=\"accesses\" style=\"text-align: center\"><a class=\"views-dialog\" href=\"#file-views\" data-action='" . $params['action'] . "' data-action_field='" . $params['action_field'] . "' data-action_value='" . $params['action_value'] . "' title=\"Click to see access log ".html_encode($statistics['views'])."\" style=\"font-weight: bold\">".html_encode($statistics['views'])."</a></td>\n";
                                            } else {
                                                echo "<td class=\"accesses\" style=\"text-align: center\"><strong>0</strong></td>";
                                            }
                                        }
                                    echo "</tr>\n";
                                } else if ($document_result['type'] == 'link') {
                                    $params = array(
                                        "action"        => "link_view",
                                        "action_field"  => "cslink_id",
                                        "action_value"  => $document_result["ID"],
                                        "module"        => "community:" . $COMMUNITY_ID . ":shares"
                                    );
                                    $statistics = Models_Statistic::getCountByParams($params);

                                    if ($document_result["access_method"] == 1) {
                                        $access = " target='_blank'";
                                        $access_location = "header";
                                    } else {
                                        $access = null;
                                        $access_location = "iframe";
                                    }

                                    echo "<tr>\n";
                                    echo "<td style='vertical-align: top'>\n";
                                    echo $document_menu;
                                    echo "<a " . ($student_hidden ? "class='hidden_shares'" : "") . $access . " id='link-".(int) $document_result["ID"]."-title' href='".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-link&amp;id=".$document_result["ID"]."&amp;access=".$access_location."' style='font-weight: bold; vertical-align: middle' >".limit_chars(html_encode($document_result["title"]), 50, true)."</a>\n";
                                    echo (!$accessible) ? "<span><i class='icon-time'></i> </span>" : "";
                                    echo "<div  class=\"content-small\" style=\"padding-left: 23px\">".html_encode(limit_chars($document_result["description"], 125))."</div>";
                                    echo "</td>\n";
                                    echo "<td style=\"font-size: 10px; white-space: nowrap; overflow: hidden\"><a href=\"".ENTRADA_URL."/people?profile=".html_encode($document_result["owner_username"])."\" style=\"font-size: 10px\">".html_encode($document_result["owner"])."</a></td>\n";
                                    echo "<td style=\"font-size: 10px; white-space: nowrap; overflow: hidden\">".date(DEFAULT_DATE_FORMAT, $document_result["updated_date"])."</td>\n";
                                    if ($COMMUNITY_ADMIN) {
                                        if ($statistics['views'] && $statistics['views'] != '0') {
                                            echo "<td class=\"accesses\" style=\"text-align: center\"><a class=\"views-dialog\" href=\"#file-views\" data-action='" . $params['action'] . "' data-action_field='" . $params['action_field'] . "' data-action_value='" . $params['action_value'] . "' title=\"Click to see access log ".html_encode($statistics['views'])."\" style=\"font-weight: bold\">".html_encode($statistics['views'])."</a></td>\n";
                                        } else {
                                            echo "<td class=\"accesses\" style=\"text-align: center\"><strong>0</strong></td>";
                                        }
                                    }
                                    echo "</tr>\n";
                                } else {
                                    //html document
                                    $params = array(
                                        "action"        => "html_view",
                                        "action_field"  => "cshtml_id",
                                        "action_value"  => $document_result["ID"],
                                        "module"        => "community:" . $COMMUNITY_ID . ":shares"
                                    );
                                    $statistics = Models_Statistic::getCountByParams($params);

                                    if ($document_result["access_method"] == 1) {
                                        $access = " target='_blank'";
                                        $access_location = "header";
                                    } else {
                                        $access = null;
                                        $access_location = "entrada";
                                    }

                                    echo "<tr>\n";
                                    echo "<td style='vertical-align: top'>\n";
                                    echo $document_menu;
                                    echo "<a " . ($student_hidden ? "class='hidden_shares'" : "") . $access . " id='html-".(int) $document_result["ID"]."-title' href='".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-html&amp;id=".$document_result["ID"]."&amp;access=".$access_location."' style='font-weight: bold; vertical-align: middle' >".limit_chars(html_encode($document_result["title"]), 50, true)."</a>\n";
                                    echo (!$accessible) ? "<span><i class='icon-time'></i> </span>" : "";
                                    echo "<div  class=\"content-small\" style=\"padding-left: 23px\">".html_encode(limit_chars($document_result["description"], 125))."</div>";
                                    echo "</td>\n";
                                    echo "<td style=\"font-size: 10px; white-space: nowrap; overflow: hidden\"><a href=\"".ENTRADA_URL."/people?profile=".html_encode($document_result["owner_username"])."\" style=\"font-size: 10px\">".html_encode($document_result["owner"])."</a></td>\n";
                                    echo "<td style=\"font-size: 10px; white-space: nowrap; overflow: hidden\">".date(DEFAULT_DATE_FORMAT, $document_result["updated_date"])."</td>\n";
                                    if ($COMMUNITY_ADMIN) {
                                        if ($statistics['views'] && $statistics['views'] != '0') {
                                            echo "<td class=\"accesses\" style=\"text-align: center\"><a class=\"views-dialog\" href=\"#file-views\" data-action='" . $params['action'] . "' data-action_field='" . $params['action_field'] . "' data-action_value='" . $params['action_value'] . "' title=\"Click to see access log ".html_encode($statistics['views'])."\" style=\"font-weight: bold\">".html_encode($statistics['views'])."</a></td>\n";
                                        } else {
                                             echo "<td class=\"accesses\" style=\"text-align: center\"><strong>0</strong></td>";
                                        }
                                    }
                                    echo "</tr>\n";
                                }
                            }
                        }
                        ?>
                        </tbody>
                    </table>
                    <?php
                    $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/jquery/jquery.dataTables.min.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
                    ?>
                    <script>
                        jQuery(function($) {

                            var file_views_table = $("#file-views-table").DataTable({
                                "bPaginate": false,
                                "bInfo": false,
                                "bFilter": false
                            });

                            $(".views-dialog").on("click", function(e) {
                                //updates stats in table
                                var clicked = jQuery(this);
                                var action = clicked.data('action');
                                var action_field = clicked.data('action_field');
                                var action_value = clicked.data('action_value');
                                var module = '<?php echo "community:" . $COMMUNITY_ID . ":shares";?>';
                                var url = '<?php echo ENTRADA_URL . "/api/stats-community-file.api.php";?>'

                                var dataObject = {
                                        action: action,
                                        action_field: action_field,
                                        action_value: action_value,
                                        module: module
                                };

                                jQuery.ajax({
                                    type: "POST",
                                    url: url,
                                    data: dataObject,
                                    dataType: "json",
                                    success: function(data) {
                                        var jsonResponse = data;
                                        if (jsonResponse.status == "success") {
                                            if (jsonResponse.data.length > 0) {
                                                //resets table
                                                file_views_table.fnClearTable();
                                                //styles button as bootstrap
                                                jQuery(".ui-dialog-buttonset button").addClass("btn btn-primary");
                                                //adds new data
                                                file_views_table.fnAddData(jsonResponse.data);
                                                //changes height of table

                                                var height_table = jQuery("#file-views-table").height() + 15;
                                                if (height_table < 400) {
                                                    jQuery("#file-views").height(height_table);
                                                } else {
                                                    jQuery("#file-views").height(400);
                                                }

                                                var fvSettings = file_views_table.fnSettings();
                                                fvSettings.oScroll.sY = "200px";
                                                file_views_table.fnDraw();
                                            }
                                        }
                                    }
                                });

                                switch (action_field) {
                                    case "cslink_id":
                                        var title = "Link Views";
                                        break;
                                    case "cshare_id" :
                                        var title = "Folder Views"
                                        break;
                                    case "csfile_id":
                                    default:
                                        var title = "File Views";
                                }

                                $("#file-views").dialog({
                                    title: title,
                                    draggable: false,
                                    resizable: false,
                                    modal: true,
                                    height: 200,
                                    width: 500,
                                    buttons: [
                                        {
                                            text: "Close",
                                            click: function() {
                                                $( this ).dialog( "close" );
                                            }
                                        }
                                    ]
                                });
                            });
                        });
                    </script>
                    <div id="file-views" class="hide">
                        <table class="table table-bordered table-striped" id="file-views-table" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Views</th>
                                    <th>Last Viewed</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                    <?php
                } else {
                    add_notice("<strong>No files in this shared folder.</strong><br /><br />".((shares_module_access($RECORD_ID, "add-file")) ? "If you would like to upload a new file, <a href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=add-file&id=".$RECORD_ID."\">click here</a>." : "Please check back later."));

                    echo display_notice();
                }
                ?>
            </div>
            <?php
			if ($LOGGED_IN) {
				add_statistic("community:".$COMMUNITY_ID.":shares", "folder_view", "cshare_id", $RECORD_ID);
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
        application_log("error", "The provided shared folder id was invalid [" . $RECORD_ID . "] (View Folder).");
        header("Location: " . COMMUNITY_URL . $COMMUNITY_URL . ":" . $PAGE_URL);
        exit;
    }
} else {
	application_log("error", "No shared folder id was provided to view. (View Folder)");

	header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
	exit;
}
