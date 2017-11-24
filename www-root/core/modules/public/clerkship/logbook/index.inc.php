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
 * Allows students to add electives to the system which still need to be approved.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2009 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_CLERKSHIP"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("logbook", "read")) {
    $ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/".$MODULE."\\'', 15000)";

    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    if (isset($_GET["id"]) && ((int) $_GET["id"])) {
        $PROXY_ID = $_GET["id"];
        $student = false;
    } else {
        $PROXY_ID = $ENTRADA_USER->getID();
        $student = true;
    }

    /**
     * Update requested column to sort by.
     * Valid: date, teacher, title, phase
     */
    if (isset($_GET["sb"])) {
        if (in_array(trim($_GET["sb"]), array("rotation", "location", "site", "patient", "date", "age"))) {
            if (trim($_GET["sb"]) == "rotation") {
                $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["value"]	= "e.`rotation_title`";
                $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"]	= "e.`rotation_title`";
            } elseif (trim($_GET["sb"]) == "location") {
                $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["value"]	= "b.`location`";
                $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"]	= "b.`location`";
            } elseif (trim($_GET["sb"]) == "site") {
                $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["value"]	= "c.`site_name`";
                $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"]	= "c.`site_name`";
            } elseif (trim($_GET["sb"]) == "patient") {
                $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["value"]	= "a.`patient_info`";
                $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"]	= "a.`patient_info`";
            } elseif (trim($_GET["sb"]) == "date") {
                $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["value"]	= "a.`encounter_date`";
                $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"]	= "a.`encounter_date`";
            } elseif (trim($_GET["sb"]) == "age") {
                $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["value"]	= "f.`age`";
                $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"]	= "f.`agerange_id`";
            }
        }
    } else {
        if(!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"])) {
            $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["value"] = "e.`rotation_title`";
            $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] = "e.`rotation_title`";
        }
        $_GET["sb"] = "rotation";
    }

    $query = "SELECT `rotation_title`, `rotation_id` FROM `".CLERKSHIP_DATABASE."`.`global_lu_rotations`";
    $rotations = $db->GetAll($query);
    $rotation_names = array();
    if ($rotations) {
        foreach ($rotations as $rotation) {
            $rotation_names[$rotation["rotation_id"]] = $rotation["rotation_title"];
        }
    }

    $clerk_name = $db->GetOne("SELECT CONCAT_WS(' ', `firstname`, `lastname`) as `fullname` 
                                FROM `".AUTH_DATABASE."`.`user_data`
                                WHERE `id` = ".$db->qstr($PROXY_ID));

    echo "<h1>My Logbook</h1>\n";

    if (isset($rotation_name) && $rotation_name) {
        echo "<h2>For ".$rotation_name." Rotation</h2>";
    }

    $query = "SELECT ".$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["value"]." AS `sort_by`, a.`lentry_id`, e.`rotation_id`, a.`entry_active`
                FROM `".CLERKSHIP_DATABASE."`.`logbook_entries` AS a 
                LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_locations` AS b
                ON a.`llocation_id` = b.`llocation_id`
                LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_sites` AS c
                ON a.`lsite_id` = c.`lsite_id`
                LEFT JOIN `".CLERKSHIP_DATABASE."`.`events` AS d
                ON a.`rotation_id` = d.`event_id`
                LEFT JOIN `".CLERKSHIP_DATABASE."`.`global_lu_rotations` AS e
                ON d.`rotation_id` = e.`rotation_id`
                LEFT JOIN `".CLERKSHIP_DATABASE."`.`logbook_lu_agerange` AS f
                ON a.`agerange_id` = f.`agerange_id`
                WHERE a.`proxy_id` = ".$db->qstr($PROXY_ID)."
                ORDER BY ".$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"]." ASC";
    $results = $db->GetAll($query);
    if ($results) {
        $rotation_ids = Array();
        foreach ($results as $result) {
            if (array_search($result["rotation_id"], $rotation_ids) === false) {
                $rotation_ids[] = $result["rotation_id"];
            }
        }

        if (!$student) {
            $query = "	SELECT a.`course_id`, b.`organisation_id` 
                        FROM `".CLERKSHIP_DATABASE."`.`global_lu_rotations` AS a
                        LEFT JOIN `".DATABASE_NAME."`.`courses` AS b
                        ON a.`course_id` = b.`course_id`";
            $courses = $db->GetAll($query);
            $allow_view = false;
            foreach ($courses as $course) {
                if ($ENTRADA_ACL->amIAllowed(new CourseContentResource($course["course_id"], $course["organisation_id"]), 'update')) {
                    $allow_view = true;
                }
            }
        }

        if ($student || $allow_view) {
            ?>
            <script>
            function loadEntry (entry_id) {
                new Ajax.Updater({ success: 'entry' }, '<?php echo ENTRADA_RELATIVE; ?>/clerkship/logbook?section=api-entry&id='+entry_id, {
                    onCreate: function () {
                        $('entry').innerHTML = '<br /><br /><span class="content-small" style="align: center;">Loading... <img src="<?php echo ENTRADA_URL; ?>/images/indicator.gif" style="vertical-align: middle;" /></span>';
                    }
                });
                if ($('entry-'+entry_id).hasClassName('flagged') == false) {
                    $("current-entry").value = entry_id;
                } else {
                    $("current-entry").value = 0;
                }
                $$('.selected-entry').each(function (e) { e.removeClassName('selected-entry'); });
                $('entry-'+entry_id).addClassName('selected-entry');
            }
            function lastPage() {
                if (Number($('current-page').value) > 1) {
                    $('page-' + $('current-page').value).hide();
                    $('current-page').value = Number($('current-page').value) - 1;
                    $('page-' + $('current-page').value).show();

                    $('display-page-number').innerHTML = 'Page ' + $('current-page').value;
                }
            }
            function nextPage() {
                if (Number($('current-page').value) < $('max-page').value) {
                    $('page-' + $('current-page').value).hide();
                    $('current-page').value = Number($('current-page').value) + 1;
                    $('page-' + $('current-page').value).show();

                    $('display-page-number').innerHTML = 'Page ' + $('current-page').value;
                }
            }
            </script>
            <input id="current-entry" type="hidden" value="0" />

            <div class="row-fluid space-above space-below">
                <div class="pull-left">
                    <form class="form-horizontal">
                        <div class="control-group">
                            <label class="control-label">View Encounters By:</label>
                            <div class="controls">
                                <select name="view-type" id="view-type" onchange="window.location = '<?php echo ENTRADA_URL."/clerkship/logbook?".replace_query(array("sb" => false)); ?>&sb='+this.options[this.selectedIndex].value;">
                                    <option value="rotation"<?php echo (isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"]) && $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] == "e.`rotation_title`" ? " selected=\"selected\"" : "")?>>Rotation</option>
                                    <option value="date"<?php echo (isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"]) && $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] == "a.`encounter_date`" ? " selected=\"selected\"" : "")?>>Encounter Date</option>
                                    <option value="location"<?php echo (isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"]) && $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] == "b.`location`" ? " selected=\"selected\"" : "")?>>Location</option>
                                    <option value="site"<?php echo (isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"]) && $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] == "c.`site_name`" ? " selected=\"selected\"" : "")?>>Site</option>
                                    <option value="patient"<?php echo (isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"]) && $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] == "a.`patient_info`" ? " selected=\"selected\"" : "")?>>Patient</option>
                                    <option value="age"<?php echo (isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"]) && $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] == "f.`agerange_id`" ? " selected=\"selected\"" : "")?>>Patient Age</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <?php
                if ($student) {
                    ?>
                    <div class="pull-right">
                        <a href="<?php echo ENTRADA_URL."/clerkship/logbook?section=csv&id=".$PROXY_ID; ?>" class="btn space-left"><i class="fa fa-download"></i> Download Encounters</a>
                        <a href="<?php echo ENTRADA_URL."/clerkship/logbook?section=add"; ?>" class="btn btn-success"><i class="icon-plus-sign icon-white"></i> Log New Encounter</a>
                    </div>
                    <?php
                }
                ?>
            </div>
            <div class="row-fluid">
                <div class="span3">
                    <?php
                    if ($results && count($results) > 20) {
                        ?>
                        <div class="btn-group space-below">
                            <a href="javascript:lastPage()" class="btn"><i class="icon-chevron-left"></i></a>
                            <div class="btn" id="display-page-number">Page 1</div>
                            <a href="javascript:nextPage()" class="btn"><i class="icon-chevron-right"></i></a>
                        </div>
                        <?php
                    }
                    ?>

                    <input type="hidden" value="1" id="current-page" />

                    <ul class="nav nav-tabs nav-stacked" id="page-1">
                        <?php
                        $count = 0;
                        $page_count = 1;
                        foreach ($results as $result) {
                            $count++;
                            if ($count > 20) {
                                $count = 1;
                                $page_count++;
                                echo "</ul>\n";
                                echo "<ul class=\"nav nav-tabs nav-stacked\" style=\"display: none;\" id=\"page-".$page_count."\">\n";
                            }
                            ?>
                            <li>
                                <a id="entry-<?php echo $result["lentry_id"]; ?>" onclick="loadEntry(<?php echo $result["lentry_id"]; ?>)" class="logbook-entry">
                                    <?php
                                    if (in_array($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"], Array("b.`location`", "c.`site_name`", "a.`patient_info`", "e.`rotation_title`", "f.`agerange_id`"))) {
                                        echo ($result["sort_by"] ? $result["sort_by"] : "No ".ucfirst($_GET["sb"])." Set");
                                    } elseif ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] == "a.`encounter_date`") {
                                        echo date(DEFAULT_DATE_FORMAT, $result["sort_by"]);
                                    }
                                    ?>
                                </a>
                            </li>
                            <?php
                        }
                        ?>
                    </ul>
                    <input type="hidden" value="<?php echo $page_count; ?>" id="max-page" />
                </div>
                <div class="span9" id="entry">
                    <h2>Review Logged Encounters</h2>
                    You can select one of your logged encounters on the left to review.
                </div>
            </div>
            <?php
        } else {
            add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

            echo display_error();
        }
    } else {
        if ($student) {
            ?>
            <div class="pull-right space-below">
                <a href="<?php echo ENTRADA_URL."/clerkship/logbook?section=add"; ?>" class="btn btn-success"><i class="icon-plus-sign icon-white"></i> Log New Encounter</a>
            </div>
            <div class="clearfix"></div>
            <?php
        }

        if (array_key_exists($rotation_id, $rotation_names) && $rotation_names[$rotation_id]) {
            add_notice("No clerkship logbook entries for this rotation [".$rotation_names[$rotation_id]."] have been found at this time. You may view all entries for all rotations by <a href=\"".ENTRADA_URL."/clerkship/logbook?".replace_query(array("rotation" => false))."\" />clicking here</a>.");
        } else {
            add_notice("There are no clerkship logbook entries found at this time. To begin logging your encounters, click the Log New Encounter button above.");
        }

        echo display_notice();
    }
}