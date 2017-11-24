<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to view html documents in a community.
 * 
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2014 Regents of The University of California. All Rights Reserved.
 * 
*/


if ((!defined("COMMUNITY_INCLUDED")) || (!defined("IN_SHARES"))) {
	exit;
} elseif (!$COMMUNITY_LOAD) {
	exit;
}

//checks the role of the user and sets hidden to true if they're not a faculty, staff, or medtech memeber
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
					FROM `community_share_html` AS a
					LEFT JOIN `community_shares` AS b
					ON a.`cshare_id` = b.`cshare_id`
					LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS c
					ON a.`proxy_id` = c.`id`
					WHERE a.`proxy_id` = c.`id`
					AND a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
					AND a.`cshtml_id` = ".$db->qstr($RECORD_ID)."
					AND b.`cpage_id` = ".$db->qstr($PAGE_ID)."
					AND a.`html_active` = '1'
					AND b.`folder_active` = '1'".
                    ($hidden ? "AND a.`student_hidden` = '0' AND b.`student_hidden` = '0'" : "");
	$html_record	= $db->GetRow($query);
        

	if ($html_record) {
        //checks if a folders parent is hidden
        $parent_folder_hidden = Models_Community_Share::parentFolderHidden($html_record['cshare_id']);
        if ($parent_folder_hidden && $hidden) {
            application_log("error", "An attempt to view an HTML document with a parent folder hidden was made. cshare_id: [".$RECORD_ID."]");

            header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
            exit;
        }
        
        if (shares_html_module_access($RECORD_ID, "view-html")) {

            $BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-folder&id=".$html_record["cshare_id"], "title" => limit_chars($html_record["folder_title"], 32));
            $BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-html&id=".$RECORD_ID, "title" => limit_chars($html_record["html_title"], 32));
            
            $MOVE_HTML		= shares_html_module_access($html_record["cshtml_id"], "move-html");
            if ($html_record['access_method'] == 1) {
                ?>
                <style>
                    #navigator-container, #main-header, #main-footer, #sidebar, #bread-crumb-trail, .community-title, .page-action {
                        display:  none;
                    }
                    #content {
                        width: 100%;
                    }
                </style>
                <?php
            }


            $community_shares_select = community_shares_in_select_hierarchy($html_record["cshare_id"], $html_record["parent_folder_id"], $PAGE_ID);
            ?>
            <script type="text/javascript">
            <?php if ($community_shares_select != "") { ?>
                function htmlMove(id) {
                    Dialog.confirm('Do you really wish to move the HTML document '+ $('html-' + id + '-title').innerHTML +'?<br /><br />If you confirm this action, you will be moving the HTML document and all comments to the selected folder.<br /><br /><?php echo $community_shares_select; ?>',
                        {
                            id:				'requestDialog',
                            width:			350,
                            height:			205,
                            title:			'Move HTML',
                            className:		'medtech',
                            okLabel:		'Yes',
                            cancelLabel:	'No',
                            closable:		'true',
                            buttonClass:	'btn',
                            ok:				function(win) {
                                                window.location = '<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=move-html&id='+id+'&share_id='+$F('share_id');
                                                return true;
                                            }
                        }
                    );
                }
            <?php 
            } ?>
            </script>
            <?php
            /**
             * If there is time release properties, display them to the browsing users.
             */
            if (($release_date = (int) $html_record["release_date"]) && ($release_date > time())) {
                $NOTICE++;
                $NOTICESTR[] = "This HTML document will not be accessible to others until <strong>".date(DEFAULT_DATE_FORMAT, $release_date)."</strong>.";
            } elseif ($release_until = (int) $html_record["release_until"]) {
                if ($release_until > time()) {
                    $NOTICE++;
                    $NOTICESTR[] = "This HTML document will be accessible until <strong>".date(DEFAULT_DATE_FORMAT, $release_until)."</strong>.";
                } else {
                    /**
                     * Only administrators or people who wrote the post will get this.
                     */
                    $NOTICE++;
                    $NOTICESTR[] = "This HTML document was only accessible until <strong>".date(DEFAULT_DATE_FORMAT, $release_until)."</strong> by others.";
                }
            }

            if ($NOTICE) {
                echo display_notice();
            }
            if ($NAVIGATION) {
                echo "	<table style=\"width: 100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">\n";
                echo "	<tbody>\n";
                echo "		<tr>\n";
                echo "			<td style=\"text-align: left\">\n".(((int) $NAVIGATION["back"]) ? "<a href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-html&amp;id=".(int) $NAVIGATION["back"]."\">&laquo; Previous Link</a>" : "&nbsp;")."</td>";
                echo "			<td style=\"text-align: right\">\n".(((int) $NAVIGATION["next"]) ? "<a href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-html&amp;id=".(int) $NAVIGATION["next"]."\">Next Link &raquo;" : "&nbsp;")."</td>";
                echo "		</tr>\n";
                echo "	</tbody>\n";
                echo "	</table>\n";
            }
            ?>
            <a name="top"></a>
            <h4 id="html-<?php echo $RECORD_ID; ?>-title"><?php echo html_encode($html_record["html_title"]); ?></h4>
            <div>
                <?php echo html_encode($html_record["html_description"]); ?>
            </div>
            
            <!-- put new content here -->
            <div id="html-content">
                <?php echo $html_record["html_content"]; ?>
            </div>
            <?php
            if ($MOVE_HTML) {
                ?>
                <ul class="page-action" id="move-html-ul">
                    <?php if ($MOVE_HTML) : ?>
                    <li><a href="javascript:htmlMove(<?php echo $RECORD_ID; ?>)" class="btn btn-success"><i class="icon-plus-sign icon-white"></i> Move HTML</a></li>
                    <?php endif; ?>
                    <li class="top"><a href="#top">Top Of Page</a></li>
                </ul>
                <?php
            } 
            if ($LOGGED_IN) {
                add_statistic("community:".$COMMUNITY_ID.":shares", "html_view", "cshtml_id", $RECORD_ID);
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
		application_log("error", "The provided HTML document id was invalid [".$RECORD_ID."] (View HTML).");

		header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
		exit;
	}
} else {
	application_log("error", "No HTML document id was provided to view. (View HTML)");

	header("Location: ".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL);
	exit;
}
