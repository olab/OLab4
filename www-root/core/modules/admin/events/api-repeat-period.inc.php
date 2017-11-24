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
 * This API file returns an interface allowing the user to select how often the 
 * current event should be repeated when passed the action "select", and outputs
 * a line with a start date/time and a title field for each repeated event
 * when passed valid frequency/period data for repetition.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 *
 */

if (!defined("IN_EVENTS")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("event", "create", false)) {
	add_error("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$GROUP."] and role [".$ROLE."] do not have access to this module [".$MODULE."]");
} else {
    /**
     * Clears all open buffers so we can return a plain response for the Javascript.
     */
    ob_clear_open_buffers();
    
    if (isset($_GET["frequency"]) && in_array($_GET["frequency"], array("daily", "weekly", "monthly"))) {
        $period = $_GET["frequency"];
    } elseif (isset($_POST["frequency"]) && in_array($_POST["frequency"], array("daily", "weekly", "monthly"))) {
        $period = $_POST["frequency"];
    }
        
    switch ($ACTION) {
        case "select" :
            if (isset($_GET["event_start"]) && ((int)$_GET["event_start"])) {
                $event_start = $_GET["event_start"];
            } elseif (isset($_POST["event_start"]) && ((int)$_POST["event_start"])) {
                $event_start = $_POST["event_start"];
            }
            echo "<form id=\"recurring-form\">";
            if (isset($period) && $period) {
                echo "<input type=\"hidden\" name=\"frequency\" value=\"".$period."\" />";
                echo "<input type=\"hidden\" name=\"event_start\" value=\"".$event_start."\" />";
                echo "<div id=\"error-messages\">&nbsp;</div>";
                echo "<div class=\"row-fluid\">\n";
                switch ($period) {
                    case "daily" :
                    default :
                        echo "<span class=\"span1\">&nbsp;</span>\n";
                        echo "<span class=\"span11\">Repeat every \n";
                        echo "<input type=\"textbox\" class=\"input-small space-left space-right\" name=\"offset\" />\n";
                        echo " days.</span>";
                    break;
                    case "weekly" :
                        echo "<span class=\"span1\">&nbsp;</span>\n";
                        echo "<span class=\"span11\">Repeat on the following days: \n";
                        echo "<div class=\"btn-group\" id=\"days-container\" data-toggle=\"buttons-checkbox\">\n";
                        echo "  <a class=\"btn toggle-days\" data-value=\"1\">Mon</a>";
                        echo "  <a class=\"btn toggle-days\" data-value=\"2\">Tues</a>";
                        echo "  <a class=\"btn toggle-days\" data-value=\"3\">Wed</a>";
                        echo "  <a class=\"btn toggle-days\" data-value=\"4\">Thurs</a>";
                        echo "  <a class=\"btn toggle-days\" data-value=\"5\">Fri</a>";
                        echo "  <a class=\"btn toggle-days\" data-value=\"6\">Sat</a>";
                        echo "  <a class=\"btn toggle-days\" data-value=\"7\">Sun</a>";
                        echo "</div>";
                        echo "</span>";
                    break;
                    case "monthly" :
                        echo "<span class=\"span1\">&nbsp;</span>\n";
                        echo "<span class=\"span11\">Repeat every \n";
                        echo "<select class=\"input-small\" id=\"week_offset\" name=\"week_offset\">\n";
                            echo "<option value=\"first\">First</option>\n";
                            echo "<option value=\"second\">Second</option>\n";
                            echo "<option value=\"third\">Third</option>\n";
                            echo "<option value=\"fourth\">Fourth</option>\n";
                            echo "<option value=\"last\">Last</option>\n";
                        echo "</select>\n";
                        echo "<select class=\"input-small\" id=\"monthly_weekday\" name=\"monthly_weekday\">\n";
                            echo "<option value=\"monday\">Monday</option>\n";
                            echo "<option value=\"tuesday\">Tuesday</option>\n";
                            echo "<option value=\"wednesday\">Wednesday</option>\n";
                            echo "<option value=\"thursday\">Thursday</option>\n";
                            echo "<option value=\"friday\">Friday</option>\n";
                            echo "<option value=\"saturday\">Saturday</option>\n";
                            echo "<option value=\"sunday\">Sunday</option>\n";
                        echo "</select>\n";
                        echo "of the month.</span>";
                    break;
                }
            } else {
                add_error("Please select a valid frequency from the <strong>Repeat Frequency</strong> selectbox.");
                echo display_error();
            }
                    
            ?>
            </div>
            <div class="row-fluid space-above">
                <span class="span1">&nbsp;</span>
                <label class="control-label span2" for="recurring_end">Until: </label>
                <span class="span9">
                    <div class="input-append">
                        <input type="text" class="input-small datepicker" value="<?php echo (isset($recurring_end) && $recurring_end ? date("Y-m-d", $recurring_end) : ""); ?>" name="recurring_end" id="recurring_end" />
                        <span class="add-on pointer"><i class="icon-calendar"></i></span>
                    </div>
                </span>
            </div>
            </form>
            <?php
        break;
        case "results" :
            $output = array();
            
            if (isset($_POST["event_start"]) && $_POST["event_start"]) {
                $event_start = (int) $_POST["event_start"];
            }
            
            if (isset($_POST["recurring_end"]) && $_POST["recurring_end"]) {
                $recurring_end = strtotime($_POST["recurring_end"]." 23:59:59");
            } else {
                $output["status"] = "error";
                add_error("Please ensure you select a valid date when the recurring events will be created until.");
                $output["message"] = display_error();
            }
            
            if (isset($_POST["offset"]) && $_POST["offset"]) {
                $offset = ((int) $_POST["offset"]);
            }
            
            if (isset($_POST["week_offset"]) && in_array($_POST["week_offset"], array("first", "second", "third", "fourth", "last"))) {
                $week_offset = $_POST["week_offset"];
            }
            
            $weekdays = array();
            if (isset($_POST["weekdays"]) && is_array($_POST["weekdays"]) && @count($_POST["weekdays"])) {
                foreach ($_POST["weekdays"] as $day) {
                    if (((int)$day) && ((int)$day >= 1) && ((int)$day <= 7)) {
                        $weekdays[] = ((int)$day);
                    }
                }
            } elseif (isset($_POST["monthly_weekday"]) && in_array($_POST["monthly_weekday"], array("monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"))) {
                $weekdays[] = $_POST["monthly_weekday"];
            }
            if ($period == "daily" && !isset($offset)) {
                $output["status"] = "error";
                add_error("Please ensure you enter a number of days which this event should repeat after.");
                $output["message"] = display_error();
            } elseif ($period == "weekly" && !@count($weekdays)) {
                $output["status"] = "error";
                add_error("Please ensure you select at least one day of the week which this event should repeat on.");
                $output["message"] = display_error();
            }
            
            if (!isset($output["status"]) || $output["status"] != "error") {
                if (isset($period) && $period) {
                    $dates = events_process_recurring_eventtimes($period, $event_start, (isset($offset) && $offset ? $offset : (isset($week_offset) && $week_offset ? $week_offset : "1")), $weekdays, $recurring_end);
                    $output["events"] = array();
                    if (@count($dates)) {
                        $restricted_days = Models_RestrictedDays::fetchAll($ENTRADA_USER->getActiveOrganisation());
                        foreach ($dates as $date) {
                            $restricted = false;
                            if ($restricted_days && @count($restricted_days)) {
                                $date_string = date("Y-m-d", $date);
                                foreach ($restricted_days as $restricted_day) {
                                    $restricted_string = date("Y-m-d", $restricted_day->getCalculatedDate(date("Y", $date), date("n", $date), $date));
                                    if ($restricted_string == $date_string) {
                                        $restricted = true;
                                        break;
                                    }
                                }
                            }
                            $output["events"][] = array("date" => (isset($output["dates"]) && $output["dates"] ? "\n" : "").date("Y-m-d", $date), "restricted" => $restricted);
                        }
                        $output["status"] = "success";
                    } else {
                        $output["status"] = "error";
                        add_error("No valid dates were found to create recurring events in. Please ensure there are dates which will fall between the event date [".date("Y-m-d", $event_start)."] and the date when recurring events will end which meet all the criteria you have set.");
                        $output["message"] = display_error();
                    }
                } else {
                    $output["status"] = "error";
                    add_error("No valid repeat frequency was selected. Please close this dialog and select a new frequency.");
                    $output["message"] = display_error();
                }
            }
            
            echo json_encode($output);
        break;
    }
    exit;
}