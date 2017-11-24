<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to list the available discussion forums within this particular page
 * in a community.
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
$HEAD[] = "<link href='".ENTRADA_URL."/css/jquery/jquery-ui-1.9.2.custom.css?release=".html_encode(APPLICATION_VERSION)."' rel='stylesheet' type='text/css' />";
$HEAD[] = "<script type=\"text/javascript\">var api_url = " . json_encode(ENTRADA_URL . "/api/api-discussions.api.php") . "</script>";
$HEAD[] = "<script type=\"text/javascript\">var community_id = " . json_encode($COMMUNITY_ID) . "</script>";
$HEAD[] = "<script type=\"text/javascript\">var page_id = " . json_encode($PAGE_ID) . "</script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"".  COMMUNITY_URL ."/javascript/discussions/discussions.js\"></script>";

/**
 * Add the javascript for deleting forums.
 */
if (communities_module_access($COMMUNITY_ID, $MODULE_ID, "delete-forum")) {
	?>
	<script>
		function discussionDelete(id) {
			Dialog.confirm('Do you really wish to remove the '+ $('forum-' + id + '-title').innerHTML +' forum from this community?<br /><br />If you confirm this action, you will be deactivating this forum and all posts within it.',
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
										window.location = '<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=delete-forum&id='+id;
										return true;
									}
				}
			);
		}
	</script>
	<?php
}
?>
<div id="module-header" class="row-fluid">
	<div class="pull-left">
		<a href="<?php echo COMMUNITY_URL."/feeds".$COMMUNITY_URL.":".$PAGE_URL."/rss:".$PRIVATE_HASH; ?>" title="Subscribe to RSS"><i class="fa fa-rss-square fa-lg fa-fw"></i></a>
	</div>
	<?php
	if (communities_module_access($COMMUNITY_ID, $MODULE_ID, "add-forum")) {
		?>
		<div class="pull-right">
			<ul class="page-action">
				<li><a href="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=add-forum" class="btn btn-success">Add Discussion Forum</a></li>
			</ul>
		</div>
		<?php
	}
    ?>
</div> <!-- end module header -->
<?php
$num_forums_added = 0;
//Check if this community is connected to a course or not
$isCommunityCourse = Models_Community_Course::is_community_course($COMMUNITY_ID);

$results_forum_cat = Models_Community_Discussion::fetchAllCategoriesByPagIdCommunityId($PAGE_ID, $COMMUNITY_ID);
if ($results_forum_cat) {
    //generate array of just categories
    foreach ($results_forum_cat as $forum_cat) {
        $category_array[] = $forum_cat['forum_category'];
    }
    ?>
    <div id="accordion">
    <?php
    $position = 0;
    $empty_category = false;
    foreach ($category_array as $category) {
        //insures we don't put the un-categorized in an accordion
        if ($category != "") {
            // Run this query if community is connected to course
            $query = "	SELECT a.*
                        FROM `community_discussions` AS a
                        WHERE a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
                        AND a.`forum_active` = '1'
                        AND a.`cpage_id` = ".$db->qstr($PAGE_ID)."
                        AND a.`forum_category` = ".$db->qstr($category)."
                        ORDER BY a.`forum_order` ASC, a.`forum_title` ASC";
            $filtered_results = array_filter($db->GetAll($query), function($result) {
                return discussions_module_access($result['cdiscussion_id'], "view-forum");
            });
            if ($filtered_results) {
                //if we have a results for accordion set the loop so we can check later for the miscellaneous category
                $looped = '1';
                ?>
                <h3 data-position="<?php echo $position ?>" data-category="<?php echo $category ?>" class="discussion_board_category">
                    <span class="category-title">
                        <?php echo $category?>
                    </span>
                </h3>
                <div>
                    <table class="table table-striped table-bordered">
                    <colgroup>
                        <col style="width: 50%" />
                        <col style="width: 10%" />
                        <col style="width: 10%" />
                        <col style="width: 30%" />
                    </colgroup>
                    <thead>
                        <tr>
                            <td>Forum Title</td>
                            <td style="border-left: none">Posts</td>
                            <td style="border-left: none">Replies</td>
                            <td style="border-left: none">Latest Post</td>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($filtered_results as $result) {
                        $num_forums_added++;
                        $topics = communities_discussions_latest($result["cdiscussion_id"]);

                        echo "<tr>\n";
                        echo "	<td>\n";
                        echo "		<a id=\"forum-".(int) $result["cdiscussion_id"]."-title\" href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-forum&amp;id=".$result["cdiscussion_id"]."\" style=\"font-weight: bold\" class=\"discussion_board_title\" data-id=\"" . $result["cdiscussion_id"] . "\">".html_encode($result["forum_title"])."</a>\n";
                        echo		((communities_module_access($COMMUNITY_ID, $MODULE_ID, "edit-forum")) ? " (<a class=\"action\" href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=edit-forum&amp;id=".$result["cdiscussion_id"]."\">edit</a>)" : "");
                        echo 		((communities_module_access($COMMUNITY_ID, $MODULE_ID, "delete-forum")) ? " (<a class=\"action\" href=\"javascript:discussionDelete('".$result["cdiscussion_id"]."')\">delete</a>)" : "");
                        echo "		<div class=\"content-small\">".html_encode(limit_chars($result["forum_description"], 125))."</div>\n";
                        echo "	</td>\n";
                        echo "	<td class=\"center\">".$topics["posts"]."</td>\n";
                        echo "	<td class=\"center\">".$topics["replies"]."</td>\n";
                        echo "	<td class=\"small\">\n";
                        if ((int) $topics["posts"]) {
                            if (defined('COMMUNITY_DISCUSSIONS_ANON') && COMMUNITY_DISCUSSIONS_ANON && !$COMMUNITY_ADMIN && isset($topics["anonymous"]) && $topics["anonymous"]) {
                                $display = "Anonymous";
                            } else {
                                $display = '<a href="'.ENTRADA_URL.'/people?profile='.html_encode($topics["username"]).'" style="font-size: 10px">'.html_encode($topics["fullname"]).'</a>';
                            }
                            echo "	<strong>Time:</strong> ".date("M d Y, g:ia", $topics["updated_date"])."<br />\n";
                            echo "	<strong>Topic:</strong> <a href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-post&amp;id=".$topics["cdtopic_id"]."\">".limit_chars(html_encode($topics["topic_title"]), 25, true)."</a><br />\n";
                            echo "	<strong>By: </strong>".$display."\n";
                        } else {
                            echo "	No topics in this forum.\n";
                        }
                        echo "	</td>\n";
                        echo "</tr>\n";
                    }

                    ?>
                    </tbody>
                    </table>
                </div>
                <?php
                $position++;
            }
        } else {
            $empty_category = true;
        }
    }
    //end accordion
    ?>
    </div>
    <?php

} else {
    $NOTICE++;
    $NOTICESTR[] = "There are currently no forums available in this community.<br /><br />".((communities_module_access($COMMUNITY_ID, $MODULE_ID, "add-forum")) ? "As a community administrator you can add forums by clicking <a href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=add-forum\">Add Discussion Forum</a>." : "Please check back later.");
    echo display_notice();
}

if ($isCommunityCourse) {
    //only search if we know there is an empty categories
    if ($empty_category) {
        //default query with no categories
        $query		= "	SELECT a.*
                        FROM `community_discussions` AS a
                        WHERE a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
                        AND a.`forum_active` = '1'
                        AND a.`cpage_id` = ".$db->qstr($PAGE_ID)."
                            AND a.`forum_category` = ''
                        ORDER BY a.`forum_order` ASC, a.`forum_title` ASC";
        $filtered_results	= array_filter($db->GetAll($query), function($item) {
            return discussions_module_access($item['cdiscussion_id'], "view-forum");
        });
        if ($filtered_results) {
            if ($looped) {
                echo '<h3>Miscellaneous</h3>';
            }
            ?>
            <table class="table table-striped table-bordered">
            <colgroup>
                <col style="width: 50%" />
                <col style="width: 10%" />
                <col style="width: 10%" />
                <col style="width: 30%" />
            </colgroup>
            <thead>
                <tr>
                    <td>Forum Title</td>
                    <td style="border-left: none">Posts</td>
                    <td style="border-left: none">Replies</td>
                    <td style="border-left: none">Latest Post</td>
                </tr>
            </thead>
            <tbody>
            <?php
                foreach($filtered_results as $result) {
                $num_forums_added++;
                $topics	= communities_discussions_latest($result["cdiscussion_id"]);

                echo "<tr>\n";
                echo "	<td>\n";
                echo "		<a id=\"forum-".(int) $result["cdiscussion_id"]."-title\" href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-forum&amp;id=".$result["cdiscussion_id"]."\" style=\"font-weight: bold\">".html_encode($result["forum_title"])."</a>\n";
                echo		((communities_module_access($COMMUNITY_ID, $MODULE_ID, "edit-forum")) ? " (<a class=\"action\" href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=edit-forum&amp;id=".$result["cdiscussion_id"]."\">edit</a>)" : "");
                echo 		((communities_module_access($COMMUNITY_ID, $MODULE_ID, "delete-forum")) ? " (<a class=\"action\" href=\"javascript:discussionDelete('".$result["cdiscussion_id"]."')\">delete</a>)" : "");
                echo "		<div class=\"content-small\">".html_encode(limit_chars($result["forum_description"], 125))."</div>\n";
                if ($result["forum_category"]) {
                    echo "	<div class=\"content-small\">Category: " . html_encode(limit_chars($result["forum_category"], 125)) . "</div>\n";
                }
                echo "	</td>\n";
                echo "	<td class=\"center\">".$topics["posts"]."</td>\n";
                echo "	<td class=\"center\">".$topics["replies"]."</td>\n";
                echo "	<td class=\"small\">\n";
                if ((int) $topics["posts"]) {
                    if(defined('COMMUNITY_DISCUSSIONS_ANON') && COMMUNITY_DISCUSSIONS_ANON && !$COMMUNITY_ADMIN && isset($topics["anonymous"]) && $topics["anonymous"]){
                        $display = "Anonymous";
                    } else {
                        $display = '<a href="'.ENTRADA_URL.'/people?profile='.html_encode($topics["username"]).'" style="font-size: 10px">'.html_encode($topics["fullname"]).'</a>';
                    }
                    echo "	<strong>Time:</strong> ".date("M d Y, g:ia", $topics["updated_date"])."<br />\n";
                    echo "	<strong>Topic:</strong> <a href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-post&amp;id=".$topics["cdtopic_id"]."\">".limit_chars(html_encode($topics["topic_title"]), 25, true)."</a><br />\n";
                    echo "	<strong>By: </strong>".$display."\n";
                } else {
                    echo "	No topics in this forum.\n";
                }
                echo "	</td>\n";
                echo "</tr>\n";
            }
            ?>
            </tbody>
            </table>
            <?php
        } else if ($num_forums_added === 0) {
            $NOTICE++;
            $NOTICESTR[] = "There are currently no forums available in this community.<br /><br />".((communities_module_access($COMMUNITY_ID, $MODULE_ID, "add-forum")) ? "As a community adminstrator you can add forums by clicking <a href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=add-forum\">Add Discussion Forum</a>." : "Please check back later.");
            echo display_notice();
        }
    }
} else {
    //only search if we know there is an empty categories
    if ($empty_category) {
        //default query with no categories
        $query		= "	SELECT a.*
                        FROM `community_discussions` AS a
                        WHERE a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
                        AND a.`forum_active` = '1'
                        AND a.`cpage_id` = ".$db->qstr($PAGE_ID)."
                        AND a.`forum_category` = ''
                        ORDER BY a.`forum_order` ASC, a.`forum_title` ASC";
        $filtered_results = array_filter($db->GetAll($query), function($result) {
            return discussions_module_access($result['cdiscussion_id'], "view-forum");
        });

        if ($filtered_results) {
            if ($looped) {
                echo '<h3>Miscellaneous</h3>';
            }
            ?>
            <table class="table table-striped table-bordered">
            <colgroup>
                <col style="width: 50%" />
                <col style="width: 10%" />
                <col style="width: 10%" />
                <col style="width: 30%" />
            </colgroup>
            <thead>
                <tr>
                    <td>Forum Title</td>
                    <td style="border-left: none">Posts</td>
                    <td style="border-left: none">Replies</td>
                    <td style="border-left: none">Latest Post</td>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach($filtered_results as $result) {
                    $num_forums_added++;
                    $topics		= communities_discussions_latest($result["cdiscussion_id"]);

                    echo "<tr>\n";
                    echo "	<td>\n";
                    echo "		<a id=\"forum-".(int) $result["cdiscussion_id"]."-title\" href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-forum&amp;id=".$result["cdiscussion_id"]."\" style=\"font-weight: bold\">".html_encode($result["forum_title"])."</a>\n";
                    echo		((communities_module_access($COMMUNITY_ID, $MODULE_ID, "edit-forum")) ? " (<a class=\"action\" href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=edit-forum&amp;id=".$result["cdiscussion_id"]."\">edit</a>)" : "");
                    echo 		((communities_module_access($COMMUNITY_ID, $MODULE_ID, "delete-forum")) ? " (<a class=\"action\" href=\"javascript:discussionDelete('".$result["cdiscussion_id"]."')\">delete</a>)" : "");
                    echo "		<div class=\"content-small\">".html_encode(limit_chars($result["forum_description"], 125))."</div>\n";
                    if ($result["forum_category"]) {
                        echo "	<div class=\"content-small\">Category: " . html_encode(limit_chars($result["forum_category"], 125)) . "</div>\n";
                    }
                    echo "	</td>\n";
                    echo "	<td class=\"center\">".$topics["posts"]."</td>\n";
                    echo "	<td class=\"center\">".$topics["replies"]."</td>\n";
                    echo "	<td class=\"small\">\n";
                    if ((int) $topics["posts"]) {
                        if (defined('COMMUNITY_DISCUSSIONS_ANON') && COMMUNITY_DISCUSSIONS_ANON && !$COMMUNITY_ADMIN && isset($topics["anonymous"]) && $topics["anonymous"]){
                            $display = "Anonymous";
                        } else {
                            $display = '<a href="'.ENTRADA_URL.'/people?profile='.html_encode($topics["username"]).'" style="font-size: 10px">'.html_encode($topics["fullname"]).'</a>';
                        }
                        echo "	<strong>Time:</strong> ".date("M d Y, g:ia", $topics["updated_date"])."<br />\n";
                        echo "	<strong>Topic:</strong> <a href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=view-post&amp;id=".$topics["cdtopic_id"]."\">".limit_chars(html_encode($topics["topic_title"]), 25, true)."</a><br />\n";
                        echo "	<strong>By: </strong>".$display."\n";
                    } else {
                        echo "	No topics in this forum.\n";
                    }
                    echo "	</td>\n";
                    echo "</tr>\n";
                }
                ?>
            </tbody>
            </table>
            <?php
        } else if ($num_forums_added === 0) {
            $NOTICE++;
            $NOTICESTR[] = "There are currently no forums available in this community.<br /><br />".((communities_module_access($COMMUNITY_ID, $MODULE_ID, "add-forum")) ? "As a community administrator you can add forums by clicking <a href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=add-forum\">Add Discussion Forum</a>." : "Please check back later.");
            echo display_notice();
        }
    }
}//end not connected to community loop