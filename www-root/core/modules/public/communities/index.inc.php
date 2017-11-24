<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Entrada is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Entrada is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Entrada.  If not, see <http://www.gnu.org/licenses/>.
 *
 * This file is used to add events to the entrada.events table.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2008 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_COMMUNITIES"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
}

Entrada_Utilities_Flashmessenger::displayMessages($MODULE);

?>

<div class="hero-unit tight">
    <h1><?php echo $translate->_("public_communities_heading_line"); ?></h1>
    <p><?php echo $translate->_("public_communities_tag_line"); ?></p>

        <?php
        if ($ENTRADA_ACL->amIAllowed("community", "create")) {
            ?>
            <a href="<?php echo ENTRADA_RELATIVE; ?>/communities?section=create" class="btn btn-primary btn-large"><?php echo $translate->_("public_communities_create"); ?></a>
            <?php
        }
        ?>
        <span class="muted pull-right">
            <?php echo vsprintf($translate->_("public_communities_count"), communities_count()); ?>
        </span>
        <div class="clearfix"></div>
    
</div>

<div class="community-body row-fluid">
	<div class="span4">
        <?php
        /*
         * 10 Most Active Communities
         */
        $query = "SELECT a.`community_id`, b.`community_url`, b.`community_title`
                    FROM `communities_most_active` AS a
                    LEFT JOIN `communities` AS b
                    ON a.`community_id` = b.`community_id`
                    ORDER BY `activity_order` ASC
                    LIMIT 0, 10";
        $results = $db->GetAll($query);
        if ($results) {
            ?>
            <h2>Most Active</h2>
            <ul class="list-arrows">
            <?php
            foreach ($results as $result) {
                ?>
                <li><a href="<?php echo ENTRADA_RELATIVE."/community".$result["community_url"]; ?>"><?php echo html_encode(limit_chars($result["community_title"], 35)); ?></a></li>
                <?php
            }
            ?>
            </ul>
            <?php
        }

        /*
         * 10 Newest Communities
         */
        $query = "SELECT a.`community_url`, a.`community_title`, a.`community_description`, a.`community_opened`
                    FROM `communities` AS a
                    WHERE a.`community_active` = '1'
                    ORDER BY a.`community_opened` DESC
                    LIMIT 0, 10";
        $results = $db->CacheGetAll(CACHE_TIMEOUT, $query);
        if ($results) {
            ?>
            <h2>Recently Created</h2>
            <ul class="list-arrows">
            <?php
            foreach ($results as $result) {
                ?>
                <li>
                    <a href="<?php echo ENTRADA_RELATIVE."/community".$result["community_url"]; ?>" title="<?php echo html_encode(limit_chars($result["community_description"], 400)); ?>"><?php echo html_encode(limit_chars($result["community_title"], 35)); ?></a>
                </li>
                <?php
            }
            ?>
            </ul>
            <?php
        }
        ?>
	</div>
	<div class="span8">
		<div class="inner-comm-body">
		    <?php
            /**
             * How many browse or search results to display per page.
             */
            $RESULTS_PER_PAGE = 10;

            switch($ACTION) {
                case "browse" :
                    $BREADCRUMB[] = array("url" => ENTRADA_URL."/".$MODULE, "title" => "Browse Communities");

                    /**
                     * Browsing communities within a category.
                     */
                    $CATEGORY_ID = 0;

                    /**
                     * The query that is actually be searched for.
                     */
                    if ((isset($_GET["category"])) && ((int) trim($_GET["category"]))) {
                        $CATEGORY_ID = (int) trim($_GET["category"]);

                        if (!$category_details = communities_fetch_category($CATEGORY_ID)) {
                            $CATEGORY_ID = 0;
                        }
                    }

                    if (!$CATEGORY_ID) {
                        header("Location: ".ENTRADA_URL."/communities");
                        exit;
                    }
                    ?>
                    <div class="row-fluid">
                        <div class="span8"><h2>Browse Communities</h2></div>
                        <div class="span4 alignRight">
                            <?php
                            if ($ENTRADA_ACL->amIAllowed("community", "create")) {
                                ?>
                                <a href="<?php echo ENTRADA_URL; ?>/communities?section=create&amp;category=<?php echo $CATEGORY_ID; ?>" class="btn btn-small">create new community</a>
                            <?php
                            }
                            ?>
                        </div>
                    </div>
                    <h4 class="categ-title"><?php echo html_encode($category_details["category_title"]); ?></h4>
                    <?php echo html_encode($community_details["category_description"]); ?>
                    <?php
                    $query_counter = "SELECT COUNT(*) AS `total_rows` FROM `communities` WHERE `category_id` = ".$db->qstr($CATEGORY_ID)." AND `community_active` = '1'";
                    $query_search = "SELECT `community_id`, `category_id`, `community_url`, `community_shortname`, `community_title`, `community_description`, `community_keywords` FROM `communities` WHERE `category_id` = ".$db->qstr($CATEGORY_ID)." AND `community_active` = '1' ORDER BY `community_title` ASC LIMIT %s, %s";

                    /**
                     * Get the total number of results using the generated queries above and calculate the total number
                     * of pages that are available based on the results per page preferences.
                     */
                    $result = ((USE_CACHE) ? $db->CacheGetRow(CACHE_TIMEOUT, $query_counter) : $db->GetRow($query_counter));
                    if ($result) {
                        $TOTAL_ROWS	= $result["total_rows"];

                        if ($TOTAL_ROWS <= $RESULTS_PER_PAGE) {
                            $TOTAL_PAGES = 1;
                        } elseif (($TOTAL_ROWS % $RESULTS_PER_PAGE) == 0) {
                            $TOTAL_PAGES = (int) ($TOTAL_ROWS / $RESULTS_PER_PAGE);
                        } else {
                            $TOTAL_PAGES = (int) ($TOTAL_ROWS / $RESULTS_PER_PAGE) + 1;
                        }
                    } else {
                        $TOTAL_ROWS		= 0;
                        $TOTAL_PAGES	= 1;
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

                    $PAGE_PREVIOUS	= (($PAGE_CURRENT > 1) ? ($PAGE_CURRENT - 1) : false);
                    $PAGE_NEXT	= (($PAGE_CURRENT < $TOTAL_PAGES) ? ($PAGE_CURRENT + 1) : false);

                    if ($TOTAL_PAGES > 1) {
                        $pagination = new Entrada_Pagination($PAGE_CURRENT, $RESULTS_PER_PAGE, $TOTAL_ROWS, ENTRADA_URL."/".$MODULE, replace_query());
                        echo $pagination->GetPageBar();
                        echo $pagination->GetResultsLabel();
                    }

                    /**
                     * Provides the first parameter of MySQLs LIMIT statement by calculating which row to start results from.
                     */
                    $limit_parameter = (int) (($RESULTS_PER_PAGE * $PAGE_CURRENT) - $RESULTS_PER_PAGE);
                    $query = sprintf($query_search, $limit_parameter, $RESULTS_PER_PAGE);
                    $results = $db->GetAll($query);
                    if ($results) {
                        echo "<div style=\"margin-left: 16px\">\n";
                        foreach ($results as $result) {
                            if ($result["community_description"]) {
                                $description = limit_chars($result["community_description"], 350);
                            } else {
                                $description = "";
                            }
                            echo "<div id=\"result-".$result["community_id"]."\" style=\"width: 100%; margin-bottom: 10px; line-height: 16px;\">\n";
                            echo "	<img src=\"".ENTRADA_URL."/images/list-community.gif\" width=\"11\" height=\"11\" alt=\"\" title=\"\" style=\"vertical-align: middle; margin-right: 5px\" /><a href=\"".ENTRADA_URL."/community".$result["community_url"]."\" style=\"vertical-align: middle; font-weight: bold\">".html_encode($result["community_title"])."</a>\n";
                            echo "	<div style=\"margin-left: 16px\">\n";
                            echo 		(($description) ? $description : "Community description not available.")."\n";
                            echo "		<div style=\"white-space: nowrap; overflow: hidden\"><a href=\"".ENTRADA_URL."/community".$result["community_url"]."\" style=\"color: green; font-size: 11px\" target=\"_blank\">".ENTRADA_URL."/community".$result["community_url"]."</a></div>\n";
                            echo "	</div>\n";
                            echo "</div>\n";
                        }
                        echo "</div>\n";
                    } else {
                        echo "<div class=\"display-notice\" style=\"margin-top: 20px; padding: 15px\">\n";
                        echo "	<div style=\"font-side: 13px; font-weight: bold\">No Communities</div>\n";
                        echo "	We have found no communities in this category. This is a great opportunity for you to <a href=\"".ENTRADA_URL."/communities?section=create&amp;category=".$CATEGORY_ID."\" style=\"color: #669900; font-weight: bold\">create a new community</a> to fill this niche!";
                        echo "</div>\n";
                    }
                break;
                case "api-search" :
                    ob_clear_open_buffers();

                    $search_query = false;

                    // Search query from request.
                    if (isset($_GET["q"]) && ($q = clean_input($_GET["q"], "trim"))) {
                        $search_query = str_ireplace(array("%", " AND ", " NOT "), array("%%", " +", " -"), $q);
                    }

                    if ($search_query) {
                        $results = Models_Community::search($search_query);
                        if ($results) {

                            foreach ($results as $result) {
                                $category_title = "";

                                if (($result["category_id"]) && ($category_details = communities_fetch_category($result["category_id"]))) {
                                    $category_title = $category_details["category_title"];
                                }

                                if ($result["community_description"]) {
                                    $description = search_description($search_query, $result["community_description"]);
                                } else {
                                    $description = "";
                                }

                                echo "<div id=\"result-".$result["community_id"]."\" class=\"space-below\">\n";
                                echo "	<img src=\"".ENTRADA_URL."/images/list-community.gif\" width=\"11\" height=\"11\" alt=\"\" title=\"\" style=\"vertical-align: middle; margin-right: 5px\" /><a href=\"".ENTRADA_URL."/community".$result["community_url"]."\" style=\"vertical-align: middle; font-weight: bold\">".html_encode($result["community_title"])."</a> <span style=\"color: #666666; font-size: 11px\">(".html_encode($category_title).")</span>\n";
                                echo "	<div style=\"margin-left: 16px\">\n";
                                echo 		(($description) ? $description : "Community description not available.")."\n";
                                echo "		<div style=\"white-space: nowrap; overflow: hidden\"><a href=\"".ENTRADA_URL."/community".$result["community_url"]."\" style=\"color: green; font-size: 11px\" target=\"_blank\">".ENTRADA_URL."/community".$result["community_url"]."</a></div>\n";
                                echo "	</div>\n";
                                echo "</div>\n";
                            }
                        } else {
                            echo "<div class=\"display-notice\" style=\"margin-top: 20px; padding: 15px\">\n";
                            echo "	<div style=\"font-side: 13px; font-weight: bold\">No Matching Communities</div>\n";
                            echo "	We have found no communities matching your search query in the system. This is a great opportunity for you to <a href=\"".ENTRADA_URL."/communities?section=create\" style=\"color: #669900; font-weight: bold\">create a new community</a> to fill this niche!";
                            echo "</div>\n";
                        }
                    }
                    exit;
                break;
                default :
                    /**
                     * Default page action (show community information).
                     */
                    ?>
                    <script>
                        jQuery(function($) {
                            $('.form-search').on('submit', function (e) {
                                $.ajax({
                                    url: '<?php echo ENTRADA_URL; ?>/communities',
                                    data: 'action=api-search&q=' + jQuery('#search-query').val(),
                                    success: function (data) {
                                        $('#search-results').html(data);

                                        $('html, body').animate({
                                            scrollTop: ($("#search-results").offset().top - 100)
                                        }, 1000);

                                    }
                                });

                                return false;
                            });
                        });
                    </script>

                    <h2>Search for a Community</h2>
                    <form class="form-search">
                        <div class="input-append">
                            <input type="text" class="search-query input-xlarge" id="search-query" name="q" value="<?php echo ((isset($_GET["q"])) ? html_encode(trim($_GET["q"])) : ""); ?>" autocomplete="off" />
                            <button class="btn" id="search-button"><i class="icon-search"></i> Search</button>
                        </div>
                    </form>

                    <div id="search-results"></div>

                    <div id="browse-communities">
                        <h2>Browse Communities</h2>
                        <div class="row-fluid">
                        <?php
                        $query = "SELECT *
                                    FROM `communities_categories`
                                    WHERE `category_parent` = '0'
                                    AND `category_visible` = '1'
                                    ORDER BY `category_title` ASC";
                        $results = $db->GetAll($query);
                        if ($results) {
                            /*
                             * Ug, this should be revisited as a list.
                             */
                            ?>
                            <table style="width: 100%" cellspacing="0" cellpadding="2" border="0">
                                <colgroup>
                                    <col style="width: 50%" />
                                    <col style="width: 50%" />
                                </colgroup>
                                <tbody>
                                <?php
                                foreach ($results as $result) {
                                    echo "<tr>\n";
                                    echo "	<td colspan=\"2\"><h4 class=\"categ-title\"> ".html_encode($result["category_title"])."</h4></td>\n";
                                    echo "</tr>\n";

                                    $query = "SELECT *
                                                FROM `communities_categories`
                                                WHERE `category_parent` = ?
                                                AND `category_visible` = '1'
                                                ORDER BY `category_title` ASC";
                                    $sresults = $db->GetAll($query, array($result["category_id"]));
                                    if ($sresults) {
                                        echo "<tr>\n";

                                        $total_sresults	= count($sresults);
                                        $count = 0;
                                        $column = 0;
                                        $max_columns = 2;

                                        foreach ($sresults as $sresult) {
                                            $count++;
                                            $column++;
                                            $communities = communities_count($sresult["category_id"]);
                                            echo "<td style=\"padding: 2px 2px 2px 3px\">";
                                            echo "	<a href=\"".ENTRADA_URL."/communities?".replace_query(array("action" => "browse", "category" => $sresult["category_id"]))."\" style=\"font-size: 13px; color: #006699\">".html_encode($sresult["category_title"])."</a> <span style=\"font-style: oblique\" class=\"content-small\">(".$communities.")</span>";
                                            echo "</td>\n";

                                            if (($count == $total_sresults) && ($column < $max_columns)) {
                                                for($i = 0; $i < ($max_columns - $column); $i++) {
                                                    echo "<td>&nbsp;</td>\n";
                                                }
                                            }

                                            if (($count == $total_sresults) || ($column == $max_columns)) {
                                                $column = 0;
                                                echo "</tr>\n";

                                                if ($count < $total_sresults) {
                                                    echo "<tr>\n";
                                                }
                                            }
                                        }
                                        echo "<tr>\n";
                                        echo "	<td colspan=\"2\">&nbsp;</td>\n";
                                        echo "</tr>\n";
                                    }
                                }
                                ?>
                                </tbody>
                            </table>
                            <?php
                        } else {
                            add_error("There does no seem to be any Community Categories in the database right now.<br /><br />The MEdTech Unit has been notified of this problem, please try again later. We apologize for any inconvenience this has caused.");

                            echo display_error();

                            application_log("error", "No community categories in the database. Database said: ".$db->ErrorMsg());
                        }
                        ?>
                    </div>
                    <?php
                break;
            }
            ?>
		</div>
	</div>
</div>


