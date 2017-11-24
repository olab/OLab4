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
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_CLERKSHIP"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("clerkship", "read")) {
    $ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

    add_error("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    $BREADCRUMB[] = array("url" => ENTRADA_URL."/clerkship", "title" => "Review");

    $HEAD[] = "<link href=\"".ENTRADA_URL."/css/tabpane.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />\n";
    $HEAD[] = "<style>.dynamic-tab-pane-control .tab-page { height:auto; }</style>\n";

    $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/tabpane/tabpane.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
    if (isset($_GET["ids"]) && $PROXY_ID = clean_input($_GET["ids"], "int")) {
        $student_name = get_account_data("firstlast", $PROXY_ID);

        $BREADCRUMB[] = array("url" => "", "title" => $student_name);

        if ($PROXY_ID && $ENTRADA_USER->getActiveGroup() != "student") {
            /**
             * Process local page actions.
             */
            $query = "SELECT a.*, c.*
                        FROM `" . CLERKSHIP_DATABASE . "`.`events` AS a
                        LEFT JOIN `" . CLERKSHIP_DATABASE . "`.`event_contacts` AS b
                        ON b.`event_id` = a.`event_id`
                        LEFT JOIN `" . CLERKSHIP_DATABASE . "`.`regions` AS c
                        ON c.`region_id` = a.`region_id`
                        WHERE b.`econtact_type` = 'student'
                        AND b.`etype_id` = " . $db->qstr($PROXY_ID) . "
                        ORDER BY a.`event_start` ASC";
            $results = $db->GetAll($query);
        if ($results) {
            $elective_weeks = clerkship_get_elective_weeks($PROXY_ID);
            $remaining_weeks = ((int)$CLERKSHIP_REQUIRED_WEEKS - (int)$elective_weeks["approved"]);

            $sidebar_html = "<ul class=\"menu\">\n";
            $sidebar_html .= "	<li><strong>" . $elective_weeks["approval"] . "</strong> Pending Approval</li>\n";
            $sidebar_html .= "	<li class=\"checkmark\"><strong>" . $elective_weeks["approved"] . "</strong> Weeks Approved</li>\n";
            $sidebar_html .= "	<li class=\"incorrect\"><strong>" . $elective_weeks["trash"] . "</strong> Weeks Rejected</li>\n";
            $sidebar_html .= "	<br />";
            if ((int)$elective_weeks["approval"] + ((int)$elective_weeks["approved"] > 0)) {
                $sidebar_html .= "	<li><a target=\"blank\" href=\"" . ENTRADA_RELATIVE . "/admin/clerkship/electives?section=disciplines&id=" . $PROXY_ID . "\">Discipline Breakdown</a></li>\n";
            }
            $sidebar_html .= "</ul>\n";

            $sidebar_html .= "<div class=\"space-above\">\n";
            $sidebar_html .= $student_name . " has " . $remaining_weeks . " required elective week" . (($remaining_weeks != 1) ? "s" : "") . " remaining.\n";
            $sidebar_html .= "</div>\n";

            new_sidebar_item("Elective Weeks", $sidebar_html, "page-clerkship", "open");

            echo "<h1>" . $student_name . "</h1>";
            ?>
            <div class="tab-pane" id="clerk-tabs">
                <div class="tab-page" id="schedule">
                    <h3 class="tab">Clerkship Schedule</h3>

                    <table class="tableList" cellspacing="0" summary="List of Clerkship Services">
                        <colgroup>
                            <col class="modified"/>
                            <col class="type"/>
                            <col class="date"/>
                            <col class="date"/>
                            <col class="region"/>
                            <col class="title"/>
                        </colgroup>
                        <thead>
                        <tr>
                            <td class="modified">&nbsp;</td>
                            <td class="type"><?php echo $translate->_("Event Type"); ?></td>
                            <td class="date-smallest">Start Date</td>
                            <td class="date-smallest">Finish Date</td>
                            <td class="region">Region</td>
                            <td class="title">Category Title</td>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        foreach ($results as $result) {
                            if ((time() >= $result["event_start"]) && (time() <= $result["event_finish"])) {
                                $bgcolor = "#E7ECF4";
                                $is_here = true;
                            } else {
                                $bgcolor = "#FFFFFF";
                                $is_here = false;
                            }

                            if ((bool)$result["manage_apartments"]) {
                                $aschedule_id = regionaled_apartment_check($result["event_id"], $ENTRADA_USER->getActiveId());
                                $apartment_available = (($aschedule_id) ? true : false);
                            } else {
                                $apartment_available = false;
                            }

                            $click_url = "";

                            if (!isset($result["region_name"]) || $result["region_name"] == "") {
                                $result_region = clerkship_get_elective_location($result["event_id"]);
                                $result["region_name"] = $result_region["region_name"];
                                $result["city"] = $result_region["city"];
                            } else {
                                $result["city"] = "";
                            }

                            $event_title = clean_input($result["event_title"], array("htmlbrackets", "trim"));

                            $cssclass = "";
                            $skip = false;

                            if ($result["event_type"] == "elective") {
                                switch ($result["event_status"]) {
                                    case "approval":
                                        $elective_word = "Pending";
                                        $cssclass = " class=\"in_draft\"";
                                        $skip = false;
                                        break;
                                    case "published":
                                        $elective_word = "Approved";
                                        $cssclass = " class=\"published\"";
                                        $skip = false;
                                        break;
                                    case "trash":
                                        $elective_word = "Rejected";
                                        $cssclass = " class=\"rejected\"";
                                        $skip = true;
                                        break;
                                    default:
                                        $elective_word = "";
                                        $cssclass = "";
                                        break;
                                }

                                $elective = true;
                            } else {
                                $elective = false;
                                $skip = false;
                            }

                            if (!$skip) {
                                echo "<tr" . (($is_here) && $cssclass != " class=\"in_draft\"" ? " class=\"current\"" : $cssclass) . ">\n";
                                echo "    <td class=\"modified\"><img src=\"" . ENTRADA_RELATIVE . "/images/" . (($apartment_available) ? "housing-icon-small.gif" : "pixel.gif") . "\" width=\"16\" height=\"16\" alt=\"" . (($apartment_available) ? "Detailed apartment information available." : "") . "\" title=\"" . (($apartment_available) ? "Detailed apartment information available." : "") . "\" /></td>\n";
                                echo "    <td class=\"type\">" . (($elective) ? "Elective" . (($elective_word != "") ? " (" . $elective_word . ")" : "") : "Core Rotation") . "</td>\n";
                                echo "    <td class=\"date-smallest\">" . date("D M d/y", $result["event_start"]) . "</td>\n";
                                echo "    <td class=\"date-smallest\">" . date("D M d/y", $result["event_finish"]) . "</td>\n";
                                echo "    <td class=\"region\">" . html_encode((($result["city"] == "") ? limit_chars(($result["region_name"]), 30) : $result["city"])) . "</td>\n";
                                echo "    <td class=\"title\">";
                                echo "      " . limit_chars(html_decode($event_title), 75);
                                echo "    </td>\n";
                                echo "</tr>\n";
                            }
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
                <div class="tab-page" id="encounters">
                    <h3 class="tab">Logged Encounters</h3>
                    <?php
                    /**
                     * Update requested column to sort by.
                     * Valid: date, teacher, title, phase
                     */
                    if (isset($_GET["sb"])) {
                        if (in_array(trim($_GET["sb"]), array("rotation", "location", "site", "patient", "date", "age"))) {
                            if (trim($_GET["sb"]) == "rotation") {
                                $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["value"] = "e.`rotation_title`";
                                $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] = "e.`rotation_title`";
                            } elseif (trim($_GET["sb"]) == "location") {
                                $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["value"] = "b.`location`";
                                $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] = "b.`location`";
                            } elseif (trim($_GET["sb"]) == "site") {
                                $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["value"] = "c.`site_name`";
                                $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] = "c.`site_name`";
                            } elseif (trim($_GET["sb"]) == "patient") {
                                $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["value"] = "a.`patient_info`";
                                $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] = "a.`patient_info`";
                            } elseif (trim($_GET["sb"]) == "date") {
                                $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["value"] = "a.`encounter_date`";
                                $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] = "a.`encounter_date`";
                            } elseif (trim($_GET["sb"]) == "age") {
                                $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["value"] = "f.`age`";
                                $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] = "f.`agerange_id`";
                            }
                        }
                    } else {
                        if (!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"])) {
                            $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["value"] = "e.`rotation_title`";
                            $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] = "e.`rotation_title`";
                        }
                        $_GET["sb"] = "rotation";
                    }

                    $query = "SELECT `rotation_title`, `rotation_id` FROM `" . CLERKSHIP_DATABASE . "`.`global_lu_rotations`";
                    $rotations = $db->GetAll($query);
                    $rotation_names = array();
                    if ($rotations) {
                        foreach ($rotations as $rotation) {
                            $rotation_names[$rotation["rotation_id"]] = $rotation["rotation_title"];
                        }
                    }

                    $clerk_name = $db->GetOne("SELECT CONCAT_WS(' ', `firstname`, `lastname`) as `fullname` 
                                        FROM `" . AUTH_DATABASE . "`.`user_data`
                                        WHERE `id` = " . $db->qstr($PROXY_ID));

                    if (isset($rotation_name) && $rotation_name) {
                        echo "<h2>For " . $rotation_name . " Rotation</h2>";
                    }

                    $query = "SELECT " . $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["value"] . " AS `sort_by`, a.`lentry_id`, e.`rotation_id`, a.`entry_active`
                        FROM `" . CLERKSHIP_DATABASE . "`.`logbook_entries` AS a 
                        LEFT JOIN `" . CLERKSHIP_DATABASE . "`.`logbook_lu_locations` AS b
                        ON a.`llocation_id` = b.`llocation_id`
                        LEFT JOIN `" . CLERKSHIP_DATABASE . "`.`logbook_lu_sites` AS c
                        ON a.`lsite_id` = c.`lsite_id`
                        LEFT JOIN `" . CLERKSHIP_DATABASE . "`.`events` AS d
                        ON a.`rotation_id` = d.`event_id`
                        LEFT JOIN `" . CLERKSHIP_DATABASE . "`.`global_lu_rotations` AS e
                        ON d.`rotation_id` = e.`rotation_id`
                        LEFT JOIN `" . CLERKSHIP_DATABASE . "`.`logbook_lu_agerange` AS f
                        ON a.`agerange_id` = f.`agerange_id`
                        WHERE a.`proxy_id` = " . $db->qstr($PROXY_ID) . "
                        ORDER BY " . $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] . " ASC";
                    $results = $db->GetAll($query);
                    if ($results) {
                        $rotation_ids = Array();
                        foreach ($results as $result) {
                            if (array_search($result["rotation_id"], $rotation_ids) === false) {
                                $rotation_ids[] = $result["rotation_id"];
                            }
                        }
                        ?>
                        <script>
                            function loadEntry(entry_id) {
                                new Ajax.Updater({success: 'entry'}, '<?php echo ENTRADA_RELATIVE; ?>/clerkship/logbook?section=api-entry&id=' + entry_id, {
                                    onCreate: function () {
                                        $('entry').innerHTML = '<br /><br /><span class="content-small" style="align: center;">Loading... <img src="<?php echo ENTRADA_URL; ?>/images/indicator.gif" style="vertical-align: middle;" /></span>';
                                    }
                                });
                                if ($('entry-' + entry_id).hasClassName('flagged') == false) {
                                    $("current-entry").value = entry_id;
                                } else {
                                    $("current-entry").value = 0;
                                }
                                $$('.selected-entry').each(function (e) {
                                    e.removeClassName('selected-entry');
                                });
                                $('entry-' + entry_id).addClassName('selected-entry');
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
                        <input id="current-entry" type="hidden" value="0"/>

                        <div class="row-fluid space-above space-below">
                            <div class="pull-left">
                                <form class="form-horizontal">
                                    <div class="control-group">
                                        <label class="control-label">View Encounters By:</label>
                                        <div class="controls">
                                            <select name="view-type" id="view-type"
                                                    onchange="window.location = '<?php echo ENTRADA_URL . "/clerkship/logbook?" . replace_query(array("sb" => false)); ?>&sb='+this.options[this.selectedIndex].value;">
                                                <option value="rotation"<?php echo(isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"]) && $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] == "e.`rotation_title`" ? " selected=\"selected\"" : "") ?>>
                                                    Rotation
                                                </option>
                                                <option value="date"<?php echo(isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"]) && $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] == "a.`encounter_date`" ? " selected=\"selected\"" : "") ?>>
                                                    Encounter Date
                                                </option>
                                                <option value="location"<?php echo(isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"]) && $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] == "b.`location`" ? " selected=\"selected\"" : "") ?>>
                                                    Location
                                                </option>
                                                <option value="site"<?php echo(isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"]) && $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] == "c.`site_name`" ? " selected=\"selected\"" : "") ?>>
                                                    Site
                                                </option>
                                                <option value="patient"<?php echo(isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"]) && $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] == "a.`patient_info`" ? " selected=\"selected\"" : "") ?>>
                                                    Patient
                                                </option>
                                                <option value="age"<?php echo(isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"]) && $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] == "f.`agerange_id`" ? " selected=\"selected\"" : "") ?>>
                                                    Patient Age
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="pull-right">
                                <a href="<?php echo ENTRADA_URL . "/clerkship/logbook?section=csv&id=" . $PROXY_ID; ?>"
                                   class="btn space-left"><i class="fa fa-download"></i> Download Encounters</a>
                            </div>
                        </div>
                        <div class="row-fluid">
                            <div class="span3">
                                <?php
                                if ($results && count($results) > 20) {
                                    ?>
                                    <div class="btn-group space-below">
                                        <a href="javascript:lastPage()" class="btn"><i
                                                    class="icon-chevron-left"></i></a>
                                        <div class="btn" id="display-page-number">Page 1</div>
                                        <a href="javascript:nextPage()" class="btn"><i
                                                    class="icon-chevron-right"></i></a>
                                    </div>
                                    <?php
                                }
                                ?>

                                <input type="hidden" value="1" id="current-page"/>

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
                                            echo "<ul class=\"nav nav-tabs nav-stacked\" style=\"display: none;\" id=\"page-" . $page_count . "\">\n";
                                        }
                                        ?>
                                        <li>
                                            <a id="entry-<?php echo $result["lentry_id"]; ?>"
                                               onclick="loadEntry(<?php echo $result["lentry_id"]; ?>)"
                                               class="logbook-entry">
                                                <?php
                                                if (in_array($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"], Array("b.`location`", "c.`site_name`", "a.`patient_info`", "e.`rotation_title`", "f.`agerange_id`"))) {
                                                    echo($result["sort_by"] ? $result["sort_by"] : "No " . ucfirst($_GET["sb"]) . " Set");
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
                                <input type="hidden" value="<?php echo $page_count; ?>" id="max-page"/>
                            </div>
                            <div class="span9" id="entry">
                                <h2>Review Logged Encounters</h2>
                                You can select one of your logged encounters on the left to review.
                            </div>
                        </div>
                        <?php
                    } else {
                        if (array_key_exists($rotation_id, $rotation_names) && $rotation_names[$rotation_id]) {
                            add_error("No clerkship logbook entries for this rotation [" . $rotation_names[$rotation_id] . "] have been found for this user in the system. You may view all entries for all rotations by clicking <a href=\"" . ENTRADA_URL . "/clerkship/logbook?" . replace_query(array("rotation" => false)) . "\" />here</a>.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:" . html_encode($AGENT_CONTACTS["administrator"]["email"]) . "\">" . html_encode($AGENT_CONTACTS["administrator"]["name"]) . "</a> for assistance.");
                        } else {
                            add_error("No clerkship logbook entries have been found for this user in the system.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:" . html_encode($AGENT_CONTACTS["administrator"]["email"]) . "\">" . html_encode($AGENT_CONTACTS["administrator"]["name"]) . "</a> for assistance.");
                        }

                        echo display_error();
                    }
                    ?>
                </div>
                <div class="tab-page" id="progress">
                    <h3 class="tab">Progress Report</h3>

                    <div id="progress-summary" style="min-height: 100px;">
                        <div style="width: 100%; text-align: center; margin-top: 80px;">
                            <div id="display-generic-box" class="display-generic">
                                <img src="<?php echo ENTRADA_RELATIVE; ?>/images/indicator.gif"/><span> Please wait while this clerk's <strong>Progress Report</strong> is loaded.</span>
                            </div>
                        </div>
                    </div>
                    <script>
                        new Ajax.Updater('progress-summary', '<?php echo ENTRADA_RELATIVE; ?>/api/clerkship-summary.api.php', {
                            method: 'get',
                            parameters: {
                                id: '<?php echo $PROXY_ID; ?>'
                            }
                        });
                    </script>
                    <?php
                    } else {
                        add_notice($student_name . " has no scheduled clerkship rotations / electives in the system at this time.  Click <a href=" . ENTRADA_URL . "/admin/clerkship/electives?section=add_core&ids=" . $PROXY_ID . " class=\"strong-green\">here</a> to add a new core rotation.");

                        echo display_notice();
                    }
                    ?>
                </div>
            </div>
            <script>
                setupAllTabs(false);
            </script>
            <?php
        } else {
            add_error("We were unable to locate the learner you are trying to view. Please double check the account information and try again.");

            echo display_error();
        }
    } else {
        add_error("We were unable to locate the learner you are trying to view. Please double check the account information and try again.");

        echo display_error();
    }
}
