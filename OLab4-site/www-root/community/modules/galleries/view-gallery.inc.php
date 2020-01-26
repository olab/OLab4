<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to view the photos within an existing photo gallery.
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
	$query = "SELECT * FROM `community_galleries` WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `cpage_id` = ".$db->qstr($PAGE_ID)." AND `cgallery_id` = ".$db->qstr($RECORD_ID);
	$gallery_record	= $db->GetRow($query);
	if ($gallery_record) {
        $BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-gallery&id=".$RECORD_ID, "title" => $gallery_record["gallery_title"]);

        $community_galleries_select = community_galleries_in_select($gallery_record["cgallery_id"]);
        ?>
        <script>
            function galleryDelete(id) {
                Dialog.confirm('Do you really wish to remove the <?php echo html_encode($gallery_record["gallery_title"]); ?> gallery from this community?<br /><br />If you confirm this action, you will be deactivating this gallery and all photos within it.',
                    {
                        id:				'requestDialog',
                        width:			350,
                        height:			200,
                        title:			'Delete Confirmation',
                        className:		'medtech',
                        okLabel:		'Yes',
                        cancelLabel:	'No',
                        closable:		'true',
                        buttonClass:	'btn',
                        ok:				function(win) {
                            window.location = '<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=delete-gallery&id='+id;
                            return true;
                        }
                    }
                );
            }

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
                        height:			225,
                        title:			'Move Photo',
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
        </script>
        <?php

        /**
         * Update requested sort column.
         * Valid: date, title
         */
        if (isset($_GET["sb"])) {
            if (@in_array(trim($_GET["sb"]), array("date", "title", "poster"))) {
                $_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["sb"] = trim($_GET["sb"]);
            }

            $_SERVER["QUERY_STRING"] = replace_query(array("sb" => false));
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

            $_SERVER["QUERY_STRING"] = replace_query(array("so" => false));
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
                $_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"] = 9;
            }
        }

        /**
         * Provide the queries with the columns to order by.
         */
        switch($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["sb"]) {
            case "title" :
                $SORT_BY = "a.`photo_title` ".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"]).", a.`updated_date` DESC";
            break;
            case "poster" :
                $SORT_BY = "CONCAT_WS(', ', b.`lastname`, b.`firstname`) ".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"]).", a.`updated_date` DESC";
            break;
            case "date" :
            default :
                $SORT_BY = "a.`updated_date` ".strtoupper($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["so"]);
            break;
        }

        /**
         * Get the total number of results using the generated queries above and calculate the total number
         * of pages that are available based on the results per page preferences.
         */
        $query = "SELECT COUNT(*) AS `total_rows`
                    FROM `community_gallery_photos` AS a
                    LEFT JOIN `community_galleries` AS c
                    ON a.`cgallery_id` = c.`cgallery_id`
                    WHERE a.`cgallery_id` = ".$db->qstr($RECORD_ID)."
                    AND a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
                    AND a.`photo_active` = '1'
                    ".((!$LOGGED_IN) ? " AND c.`allow_public_read` = '1'" : (($COMMUNITY_MEMBER) ? ((!$COMMUNITY_ADMIN) ? " AND c.`allow_member_read` = '1'" : "") : " AND c.`allow_troll_read` = '1'"))."
                    ".((!$COMMUNITY_ADMIN) ? ($LOGGED_IN ? " AND ((a.`proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId()).") OR " : " AND (")."(a.`release_date` = '0' OR a.`release_date` <= ".$db->qstr(time()).") AND (a.`release_until` = '0' OR a.`release_until` > ".$db->qstr(time())."))" : "");
        $result	= $db->GetRow($query);
        if ($result) {
            $TOTAL_ROWS	= $result["total_rows"];

            if ($TOTAL_ROWS <= $_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"]) {
                $TOTAL_PAGES = 1;
            } elseif (($TOTAL_ROWS % $_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"]) == 0) {
                $TOTAL_PAGES = (int) ($TOTAL_ROWS / $_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"]);
            } else {
                $TOTAL_PAGES = (int) ($TOTAL_ROWS / $_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"]) + 1;
            }
        } else {
            $TOTAL_ROWS = 0;
            $TOTAL_PAGES = 1;
        }

        /**
         * Check if pv variable is set and see if it's a valid page, other wise page 1 it is.
         */
        if (isset($_GET["pv"])) {
            $PAGE_CURRENT = (int) trim($_GET["pv"]);

            if (($PAGE_CURRENT < 1) || ($PAGE_CURRENT > $TOTAL_PAGES)) {
                $PAGE_CURRENT = 1;
            }
        } else {
            $PAGE_CURRENT = 1;
        }

        if ($TOTAL_PAGES > 1) {
            $pagination = new Entrada_Pagination($PAGE_CURRENT, $_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"], $TOTAL_ROWS, COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL, replace_query());
        }

        $PAGE_PREVIOUS = (($PAGE_CURRENT > 1) ? ($PAGE_CURRENT - 1) : false);
        $PAGE_NEXT = (($PAGE_CURRENT < $TOTAL_PAGES) ? ($PAGE_CURRENT + 1) : false);

        Entrada_Utilities_Flashmessenger::displayMessages($MODULE);

        /**
         * Provides the first parameter of MySQLs LIMIT statement by calculating which row to start results from.
         */
        $limit_parameter = (int) (($_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"] * $PAGE_CURRENT) - $_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"]);
        ?>
        <h1 style="display: inline"><?php echo html_encode($gallery_record["gallery_title"]); ?></h1>
        <?php
        /*
         * Edit and Delete links if the user has access.
         */
        echo ((communities_module_access($COMMUNITY_ID, $MODULE_ID, "edit-gallery")) ? " (<a class=\"action\" href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=edit-gallery&amp;id=".$RECORD_ID."\">edit</a>)" : "");
        echo ((communities_module_access($COMMUNITY_ID, $MODULE_ID, "delete-gallery")) ? " (<a class=\"action\" href=\"javascript:galleryDelete('".$RECORD_ID."')\">delete</a>)" : "");
        ?>

        <p><?php echo nl2br(html_encode($gallery_record["gallery_description"])); ?></p>

        <div id="module-header">
            <?php
            if ($TOTAL_PAGES > 1) {
                echo $pagination->GetPageBar("normal", "right");
            }
            ?>
        </div>

        <div style="padding-top: 10px; clear: both">
            <?php if ($LOGGED_IN && COMMUNITY_NOTIFICATIONS_ACTIVE && $_SESSION["details"]["notifications"]) { ?>
                <div id="notifications-toggle"></div>
                <script type="text/javascript">
                    function promptNotifications(enabled) {
                        Dialog.confirm('Do you really wish to '+ (enabled == 1 ? "stop" : "begin") +' receiving notifications for new photos in this gallery?',
                            {
                                id:				'requestDialog',
                                width:			350,
                                height:			100,
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
                                            height:			100,
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
                                            url:			'<?php echo ENTRADA_URL."/api/notifications.api.php?community_id=".$COMMUNITY_ID."&id=".$RECORD_ID; ?>&type=photo&action=edit&active='+(enabled == 1 ? '0' : '1'),
                                            onClose:			function () {
                                                new Ajax.Updater('notifications-toggle', '<?php echo ENTRADA_URL."/api/notifications.api.php?community_id=".$COMMUNITY_ID."&id=".$RECORD_ID; ?>&type=photo&action=view');
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
                $ONLOAD[] = "new Ajax.Updater('notifications-toggle', '".ENTRADA_URL."/api/notifications.api.php?community_id=".$COMMUNITY_ID."&id=".$RECORD_ID."&type=photo&action=view')";
            }
            if (galleries_module_access($RECORD_ID, "add-photo")) {
                ?>
                <div style="float: right; padding-top: 10px;">
                    <ul class="page-action">
                        <li><a href="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=add-photo&id=<?php echo $RECORD_ID; ?>" class="btn btn-success">Upload Photo</a></li>
                    </ul>
                </div>
                <div style="clear: both"></div>
                <?php
            }

            $query		= "
                                    SELECT a.*, CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `original_poster_fullname`, b.`username` AS `original_poster_username`
                                    FROM `community_gallery_photos` AS a
                                    LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
                                    ON a.`proxy_id` = b.`id`
                                    LEFT JOIN `community_galleries` AS c
                                    ON a.`cgallery_id` = c.`cgallery_id`
                                    WHERE a.`cgallery_id` = ".$db->qstr($RECORD_ID)."
                                    AND a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
                                    AND a.`photo_active` = '1'
                                    ".((!$LOGGED_IN) ? " AND c.`allow_public_read` = '1'" : (($COMMUNITY_MEMBER) ? ((!$COMMUNITY_ADMIN) ? " AND c.`allow_member_read` = '1'" : "") : " AND c.`allow_troll_read` = '1'"))."
                                    ".((!$COMMUNITY_ADMIN) ? ($LOGGED_IN ? " AND ((a.`proxy_id` = ".$db->qstr($ENTRADA_USER->getActiveId()).") OR " : " AND (")."(a.`release_date` = '0' OR a.`release_date` <= ".$db->qstr(time()).") AND (a.`release_until` = '0' OR a.`release_until` > ".$db->qstr(time())."))" : "")."
                                    ORDER BY %s
                                    LIMIT %s, %s";
            $query		= sprintf($query, $SORT_BY, $limit_parameter, $_SESSION[APPLICATION_IDENTIFIER]["cid_".$COMMUNITY_ID][$PAGE_URL]["pp"]);
            $results	= $db->GetAll($query);
            if ($results) {
                $total_photos	= count($results);
                $column			= 0;
                ?>
                <table summary="Listing Of Photos">
                    <colgroup>
                        <col style="width: 33%" />
                        <col style="width: 34%" />
                        <col style="width: 33%" />
                    </colgroup>
                    <tbody>
                    <tr>
                        <?php
                        foreach($results as $progress => $result) {
                            $column++;
                            $accessible	= true;
                            if ((($result["release_date"]) && ($result["release_date"] > time())) || (($result["release_until"]) && ($result["release_until"] < time()))) {
                                $accessible = false;
                            }

                            echo "<td".((!$accessible) ? " class=\"na\"" : "")." style=\"vertical-align: top; text-align: center\">";
                            echo "	<a href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-photo&amp;id=".$result["cgphoto_id"]."\">".communities_galleries_fetch_thumbnail($result["cgphoto_id"])."</a>";
                            echo "	<div class=\"content-small\">\n";
                            echo "	<strong id=\"photo-".$result["cgphoto_id"]."-title\">".html_encode(limit_chars($result["photo_title"], 26))."</strong>";
                            echo "	<br />";
                            echo ((galleries_photo_module_access($result["cgphoto_id"], "edit-photo")) ? " (<a class=\"action\" href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=edit-photo&amp;id=".$result["cgphoto_id"]."\">edit</a>)" : "");
                            echo ((galleries_photo_module_access($result["cgphoto_id"], "delete-photo")) ? " (<a class=\"action\" href=\"javascript:photoDelete('".$result["cgphoto_id"]."')\">delete</a>)" : "");
                            if ($community_galleries_select != "") {
                                echo ((galleries_photo_module_access($result["cgphoto_id"], "move-photo")) ? " (<a class=\"action\" href=\"javascript:photoMove('".$result["cgphoto_id"]."')\">move</a>)" : "");
                            }
                            echo "	</div>";
                            echo "</td>";

                            if ((($progress + 1) == $total_photos) && ($column != 3)) {
                                echo "<td colspan=\"".(3 - $column)."\">&nbsp;</td>\n";
                            } elseif ($column == 3) {
                                $column = 0;
                                echo "<tr>\n";
                                echo "	<td colspan=\"2\">&nbsp;</td>\n";
                                echo "</tr>\n";
                                echo "<tr>\n";
                                echo "	<td colspan=\"2\">&nbsp;</td>\n";
                                echo "</tr>\n";
                            }
                        }
                        ?>
                    </tr>
                    </tbody>
                </table>
                <?php
            } else {
                $NOTICE++;
                $NOTICESTR[] = "<strong>No photos in this gallery.</strong><br /><br />".((galleries_module_access($RECORD_ID, "add-photo")) ? "If you would like to upload a new photo, <a href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=add-photo&id=".$RECORD_ID."\">click here</a>." : "Please check back later.");

                echo display_notice();
            }
            ?>
        </div>
        <?php
		if (galleries_module_access($RECORD_ID, "view-gallery")) {
			if ($LOGGED_IN) {
				add_statistic("community:".$COMMUNITY_ID.":galleries", "gallery_view", "cgallery_id", $RECORD_ID);
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
		application_log("error", "The provided photo gallery id was invalid [".$RECORD_ID."] (View Gallery).");

		header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
		exit;
	}
} else {
	application_log("error", "No photo gallery id was provided to view. (View Gallery)");

	header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
	exit;
}
