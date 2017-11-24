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
 * Loads the Learning Event file wizard when a teacher / director wants to add /
 * edit a file on the Manage Events > Content page.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
*/

@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    dirname(__FILE__) . "/../core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

ob_start("on_checkout");

if((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    echo "<div id=\"scripts-on-open\" style=\"display: none;\">\n";
    echo "alert('It appears as though your session has expired; you will now be taken back to the login page.');\n";
    echo "if(window.opener) {\n";
    echo "    window.opener.location = '".ENTRADA_URL.((isset($_SERVER["REQUEST_URI"])) ? "?url=".rawurlencode(clean_input($_SERVER["REQUEST_URI"], array("nows", "url"))) : "")."';\n";
    echo "    top.window.close();\n";
    echo "} else {\n";
    echo "    window.location = '".ENTRADA_URL.((isset($_SERVER["REQUEST_URI"])) ? "?url=".rawurlencode(clean_input($_SERVER["REQUEST_URI"], array("nows", "url"))) : "")."';\n";
    echo "}\n";
    echo "</div>\n";
    exit;
} else {

    $EVENT_ID            = 0;
    $EFILE_ID            = 0;

    if(isset($_GET["action"])) {
        $ACTION    = trim($_GET["action"]);
    }

    if((isset($_GET["step"])) && ((int) trim($_GET["step"]))) {
        $STEP = (int) trim($_GET["step"]);
    }

    if((isset($_GET["id"])) && ((int) trim($_GET["id"]))) {
        $EVENT_ID    = (int) trim($_GET["id"]);
    }

    if((isset($_GET["fid"])) && ((int) trim($_GET["fid"]))) {
        $EFILE_ID = (int) trim($_GET["fid"]);
    }

    $modal_onload = array();
    if($EVENT_ID) {
        $query    = "    SELECT a.*, b.`organisation_id`
                    FROM `events` AS a
                    LEFT JOIN `courses` AS b
                    ON b.`course_id` = a.`course_id`
                    WHERE a.`event_id` = ".$db->qstr($EVENT_ID);
        $result    = $db->GetRow($query);
        if($result) {
            $access_allowed = false;
            if (!$ENTRADA_ACL->amIAllowed(new EventContentResource($EVENT_ID, $result["course_id"], $result["organisation_id"]), "update")) {
                $query = "SELECT * FROM `events` WHERE `parent_id` = ".$db->qstr($EVENT_ID);
                if ($sessions = $db->GetAll($query)) {
                    foreach ($sessions as $session) {
                        if ($ENTRADA_ACL->amIAllowed(new EventContentResource($session["event_id"], $result["course_id"], $result["organisation_id"]), "update")) {
                            $access_allowed = true;
                        }
                    }
                }
            } else {
                $access_allowed = true;
            }
            if (!$access_allowed) {
                $modal_onload[]    = "closeWizard()";

                $ERROR++;
                $ERRORSTR[]    = "Your account does not have the permissions required to use this feature of this module. If you believe you are receiving this message in error please contact us for assistance.";

                echo display_error();

                application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to the file wizard.");
            } else {
                switch($ACTION) {
                    case "file" :
                        $action_field = "file_id";
                        $action = "file_download";
                        $viewsSQL = "   SELECT DISTINCT (stats.proxy_id), COUNT(*) AS views, users.firstname, users.lastname, MAX(stats.timestamp) as lastViewedTime
                                      FROM " . DATABASE_NAME . ".statistics AS stats, " . AUTH_DATABASE . ".user_data AS users
                                      WHERE (stats.module = 'events' OR stats.module = 'podcasts')
                                      AND stats.action = '" . $action . "'
                                      AND stats.action_field = '" . $action_field . "'
                                      AND stats.action_value = " . $EFILE_ID . " 
                                      AND stats.proxy_id = users.id
                                      GROUP BY stats.proxy_id
                                      ORDER BY users.lastname ASC";
                        $statistics = $db->GetAll($viewsSQL);
                        $totalViews = 0;
                        $userViews = 0;
                        $statsHTML = "";
                        foreach ($statistics as $stats) {
                          $statsHTML .=   "<li class='statsLI'><span class='sortStats sortStatsNameModel'>" . $stats["lastname"] . ", " . $stats["firstname"] . "</span><span class='sortStats sortStatsViewsModel'>" . $stats["views"] . "</span><span class='sortStats sortStatsDateModel'>" . date("m-j-Y g:ia", $stats["lastViewedTime"]) . "</span></li>";
                          $userViews++;
                          $totalViews = $totalViews + $stats["views"];
						  
                        }
						
						$is_podcast = (bool)$db->GetOne("SELECT * FROM `event_files` WHERE `efile_id`=".$db->qstr($EFILE_ID)." AND `file_category`='podcast'");
						$podcastviews = (int)$db->GetOne(
								"SELECT SUM(a.`views`) AS `total_views` 
								 FROM 
								 (
									SELECT DISTINCT (s.`proxy_id`), COUNT(*) AS `views` 
									FROM `statistics` as s, `".AUTH_DATABASE."`.`user_data` AS u
									WHERE s.`module`='podcasts'
									AND s.`action`='file_download'
									AND s.`action_field`='file_id'
									AND s.`action_value`=".$db->qstr($EFILE_ID)."
									AND s.`proxy_id`=u.`id`
									GROUP BY s.`proxy_id`
								 ) AS a");
						$directfileviews = (int)$db->GetOne(
								"SELECT SUM(a.`views`) AS `total_views` 
								 FROM 
								 (
									SELECT DISTINCT (s.`proxy_id`), COUNT(*) AS `views` 
									FROM `statistics` as s, `".AUTH_DATABASE."`.`user_data` AS u
									WHERE s.`module`='events'
									AND s.`action`='file_download'
									AND s.`action_field`='file_id'
									AND s.`action_value`=".$db->qstr($EFILE_ID)."
									AND s.`proxy_id`=u.`id`
									GROUP BY s.`proxy_id`
								 ) AS a");
                                
                        if($EFILE_ID) {           
                            $query    = "SELECT * FROM `event_files` WHERE `event_id` = ".$db->qstr($EVENT_ID)." AND `efile_id` = ".$db->qstr($EFILE_ID);
                            $result    = $db->GetRow($query);
                            if($result) {                
                                $PROCESSED["file_title"] = trim($result["file_title"]);
                                ?>
                                <script type="text/javascript">
                                    jQuery(document).ready(function(){
                                        jQuery(".sortStatsHeader").click(function() {
                                            var sortID = jQuery(this).attr("id");
                                            if(jQuery(this).hasClass("ASC")) {
                                                var sortOrder = "DESC";
                                            } else {
                                                var sortOrder = "ASC";
                                            }
                                            var eventID = "<?php echo $EVENT_ID?>";
                                            var EFILE_ID = "<?php echo $EFILE_ID?>";
                                            var action_field = "<?php echo $action_field?>";
                                            var action = "<?php echo $action?>";
                                            var dataString = 'sortOrder=' + sortOrder + '&sortID=' + sortID + '&EFILE_ID=' + EFILE_ID + '&action_field=' + action_field + '&action=' + action;
                                            var url = '<?php echo ENTRADA_URL . "/api/stats-event-file.php";?>'
                                            jQuery.ajax({
                                                type: "POST",
                                                url: url,
                                                data: dataString,
                                                dataType: "json",
                                                success: function(data) {
                                                    jQuery("#userViews").html("<strong>" + data["userViews"] + "</strong>");
                                                    jQuery("#totalViews").html("<strong>" + data["totalViews"] + "</strong>");
                                                    jQuery("#statsHTML").html(data["statsHTML"]);
                                                    if(jQuery("#" + sortID).hasClass("ASC")) {
                                                        jQuery(".sortStatsHeader").removeClass("ASC").addClass("DESC");;
                                                    } else {
                                                        jQuery(".sortStatsHeader").removeClass("DESC").addClass("ASC");
                                                    }
                                                }
                                             });
                                        });
                                    });
                                </script>
                                <div class="modal-dialog" id="file-edit-wizard-<?php echo $EFILE_ID; ?>">
                                    <div id="wizard">
                                        <h2 title="Event Statistics Section">File Statistics</h2>
                                        <h3><?php echo html_encode($PROCESSED["file_title"]); ?></h3>
                                        <div id="bodyStats">
                                            <ul class="statsUL">
                                                <li class="statsLI"><span class="statsLISpan1">Number of users who downloaded this file: </span><span id="userViews"><strong><?php echo $userViews?></strong></span></li>
												<?php if ($is_podcast) { ?>
                                                <li class="statsLI"><span class="statsLISpan1">Podcast downloads of this file: </span><span id="totalViews"><strong><?php echo $podcastviews?></strong></span></li>
                                                <li class="statsLI"><span class="statsLISpan1">Direct downloads of this file: </span><span id="totalViews"><strong><?php echo $directfileviews?></strong></span></li>
												<?php } ?>
                                                <li class="statsLI"><span class="statsLISpan1">Total downloads of this file: </span><span id="totalViews"><strong><?php echo $totalViews?></strong></span></li>
											</ul>
                                            <ul class="statsUL">
                                                <li class="statsLIHeader"><span class="sortStatsHeader ASC sortStatsNameModel" id="name">Name</span><span class="sortStatsHeader ASC sortStatsViewsModel" id="view">Saves</span><span class="sortStatsHeader ASC sortStatsDateModel" id="date">Last saved on</span></li>
                                                <div id="statsHTML"><?php echo $statsHTML ?></div>
                                            </ul>
                                            <p class="content-small">Click on title to change sort</p>
                                        </div>
                                        <div id="footer">
                                            <input type="button" class="btn" value="Close" onclick="closeWizard()" style="float: left; margin: 4px 0px 4px 10px" />
                                        </div>
                                    </div>
                                </div>
                            <?php
                            } else {
                                $ERROR++;
                                $ERRORSTR[] = "The provided file identifier does not exist in the provided event.";

                                echo display_error();

                                application_log("error", "file/link event statistics was accessed with a file id that was not found in the database.");
                            }
                        } else {
                            $ERROR++;
                            $ERRORSTR[] = "You must provide a file identifier when using the file wizard.";

                            echo display_error();

                            application_log("error", "File wizard was accessed without any file id.");
                        }
                    break;
                    case "link" :
                        $action_field = "link_id";
                        $action = "link_access";
                        $viewsSQL = "   SELECT DISTINCT (stats.proxy_id), COUNT(*) AS views, users.firstname, users.lastname, MAX(stats.timestamp) as lastViewedTime
                                      FROM " . DATABASE_NAME . ".statistics AS stats, " . AUTH_DATABASE . ".user_data AS users
                                      WHERE stats.module = 'events'
                                      AND stats.action = '" . $action . "'
                                      AND stats.action_field = '" . $action_field . "'
                                      AND stats.action_value = " . $EFILE_ID . " 
                                      AND stats.proxy_id = users.id
                                      GROUP BY stats.proxy_id
                                      ORDER BY users.lastname ASC";
                        $statistics = $db->GetAll($viewsSQL);
                        $totalViews = 0;
                        $userViews = 0;
                        $statsHTML = "";
                        foreach ($statistics as $stats) {
                          $statsHTML .=   "<li class='statsLI'><span class='sortStats sortStatsNameModel'>" . $stats["lastname"] . ", " . $stats["firstname"] . "</span><span class='sortStats sortStatsViewsModel'>" . $stats["views"] . "</span><span class='sortStats sortStatsDateModel'>" . date("m-j-Y g:ia", $stats["lastViewedTime"]) . "</span></li>";
                          $userViews++;
                          $totalViews = $totalViews + $stats["views"];
                        }
                        if($EFILE_ID) {
                            $query    = "SELECT * FROM `event_links` WHERE `event_id` = ".$db->qstr($EVENT_ID)." AND `elink_id` = ".$db->qstr($EFILE_ID);
                            $result    = $db->GetRow($query);
                            if($result) {
                                $PROCESSED["file_title"] = trim($result["link_title"]);
                                ?>
                                <script type="text/javascript">
                                    jQuery(document).ready(function(){
                                        jQuery(".sortStatsHeader").click(function() {                                        
                                            var sortID = jQuery(this).attr("id");
                                            if(jQuery(this).hasClass("ASC")) {
                                                var sortOrder = "DESC";
                                            } else {
                                                var sortOrder = "ASC";
                                            }
                                            var eventID = "<?php echo $EVENT_ID?>";
                                            var EFILE_ID = "<?php echo $EFILE_ID?>";
                                            var action_field = "<?php echo $action_field?>";
                                            var action = "<?php echo $action?>";
                                            var dataString = 'sortOrder=' + sortOrder + '&sortID=' + sortID + '&EFILE_ID=' + EFILE_ID + '&action_field=' + action_field + '&action=' + action;
                                            var url = '<?php echo ENTRADA_URL . "/api/stats-event-file.php";?>'
                                            jQuery.ajax({
                                                type: "POST",
                                                url: url,
                                                data: dataString,
                                                dataType: "json",
                                                success: function(data) {
                                                    jQuery("#userViews").html("<strong>" + data["userViews"] + "</strong>");
                                                    jQuery("#totalViews").html("<strong>" + data["totalViews"] + "</strong>");
                                                    jQuery("#statsHTML").html(data["statsHTML"]);
                                                    if(jQuery("#" + sortID).hasClass("ASC")) {
                                                        jQuery(".sortStatsHeader").removeClass("ASC").addClass("DESC");;
                                                    } else {
                                                        jQuery(".sortStatsHeader").removeClass("DESC").addClass("ASC");
                                                    }
                                                }
                                             });
                                        });
                                    });
                                </script>
                                <div class="modal-dialog" id="file-edit-wizard-<?php echo $EFILE_ID; ?>">
                                        <div id="wizard">
                                            <h2 title="Event Statistics Section">Link Statistics</h2>
                                            <h3><?php echo html_encode($PROCESSED["file_title"]); ?></h3>
                                            <div id="bodyStats">
                                                <ul class="statsUL">
                                                    <li class="statsLI"><span class="statsLISpan1">Number of users who viewed this link: </span><span id="userViews"><strong><?php echo $userViews?></strong></span></li>
                                                    <li class="statsLI"><span class="statsLISpan1">Total views of this link: </span><span id="totalViews"><strong><?php echo $totalViews?></strong></span></li>
                                                </ul>
                                                <ul class="statsUL">
                                                    <li class="statsLIHeader"><span class="sortStatsHeader ASC sortStatsNameModel" id="name">Name</span><span class="sortStatsHeader ASC sortStatsViewsModel" id="view">Hits</span><span class="sortStatsHeader ASC sortStatsDateModel" id="date">Last accessed</span></li>
                                                    <div id="statsHTML"><?php echo $statsHTML ?></div>
                                                </ul>
                                                <p class="content-small">Click on title to change sort</p>
                                            </div>
                                            <div id="footer">
                                                <input type="button" class="btn" value="Close" onclick="closeWizard()" style="float: left; margin: 4px 0px 4px 10px" />
                                            </div>
                                        </div>
                                </div>
                                <?php
                            } else {
                                $ERROR++;
                                $ERRORSTR[] = "The provided link identifier does not exist in the provided event.";

                                echo display_error();

                                application_log("error", "file/link event statistics was accessed with a link id that was not found in the database.");
                            }
                        } else {
                            $ERROR++;
                            $ERRORSTR[] = "You must provide a file identifier when using the file wizard.";

                            echo display_error();

                            application_log("error", "file/link event statistics was accessed without any file id.");
                        }             
                        break;
                    default :
    
                }
                ?>
                <div id="scripts-on-open" style="display: none;">
                <?php
                    foreach ($modal_onload as $string) {
                        echo $string.";\n";
                    }
                ?>
                </div>
                <?php
            }
        } else {
            $ERROR++;
            $ERRORSTR[] = "The provided event identifier does not exist in this system.";

            echo display_error();

            application_log("error", "File wizard was accessed without a valid event id.");
        }
    } else {
        $ERROR++;
        $ERRORSTR[] = "You must provide an event identifier when using the file wizard.";

        echo display_error();

        application_log("error", "File wizard was accessed without any event id.");
    }
}