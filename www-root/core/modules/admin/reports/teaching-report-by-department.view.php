<?php
/**
 * Teaching report by department (View)
 * Module:	Reports
 * Area:		Admin
 * @author Ryan Sherrington <ryan.sherrington@ubc.ca>
 * @copyright Copyright 2017 UBC
 *
 */
?>
<a name="top"></a>
<div class="no-printing">
    <form action="<?php echo ENTRADA_URL; ?>/admin/reports?section=<?php echo $this->SECTION; ?>&step=2" method="post">
        <input type="hidden" name="update" value="1" />
        <table style="width: 100%" cellspacing="0" cellpadding="2" border="0">
            <colgroup>
                <col style="width: 3%" />
                <col style="width: 20%" />
                <col style="width: 77%" />
            </colgroup>
            <tbody>
            <tr>
                <td colspan="3"><h2>Report Options</h2></td>
            </tr>
            <?php echo generate_calendars("reporting", "Reporting Date", true, true, $_SESSION[$this->APPLICATION_IDENTIFIER][$this->MODULE]["reporting_start"], true, true, $_SESSION[$this->APPLICATION_IDENTIFIER][$this->MODULE]["reporting_finish"]);?>
            <tr>
                <td colspan="3" style="padding-top: 15px">
                    <input type="checkbox" id="show_all_teachers" name="show_all_teachers" value="1" style="vertical-align: middle"<?php echo (($this->PROCESSED["show_all_teachers"]) ? " checked=\"checked\"" : ""); ?> /> <label for="show_all_teachers" class="form-nrequired" style="vertical-align: middle">Display teachers in departments who are not currently teaching.</label>
                </td>
            </tr>
            <tr>
                <td colspan="3" style="text-align: right; padding-top: 10px"><input type="submit" class="btn btn-primary" value="Update Report" /></td>
            </tr>
            </tbody>
        </table>
    </form>
</div>
<?php
if ($this->STEP == 2) {
    echo "<h1>Faculty Teaching Report By Department (Workforce)</h1>";
    echo "<div class=\"content-small\" style=\"margin-bottom: 10px\">\n";
    echo "	<strong>Date Range:</strong> " . date($this->DEFAULT_DATE_FORMAT, $_SESSION[$this->APPLICATION_IDENTIFIER][$this->MODULE]["reporting_start"]) . " <strong>to</strong> " . date($this->DEFAULT_DATE_FORMAT, $_SESSION[$this->APPLICATION_IDENTIFIER][$this->MODULE]["reporting_finish"]);
    echo "</div>";

    if ((is_array($this->view_data['departments'])) && (count($this->view_data['departments']))) {
        foreach ($this->view_data['departments'] as $department_name => $department_data) {
            echo "<div style=\"float: right\">\n";
            echo "	<a href=\"#top\">(top)</a>\n";
            echo "</div>\n";
            echo "<a name=\"" . $department_data['department_link'] . "\"></a>\n";
            echo "<h2>" . html_encode($department_name) . "</h2>";

            ?>
            <table class="tableList" cellspacing="0"
                   summary="Summary Report For <?php echo html_encode($department_name); ?>">
                <colgroup>
                    <col class="modified"/>
                    <col class="general"/>
                    <col class="report-hours-lg" style="background-color: #F3F3F3"/>
                    <col class="report-hours-lg"/>
                    <col class="report-hours-lg" style="background-color: #F3F3F3"/>
                    <col class="report-hours-lg"/>
                    <col class="report-hours-lg" style="background-color: #F3F3F3"/>
                    <col class="report-hours-lg"/>
                    <col class="report-hours-lg" style="background-color: #F3F3F3"/>
                    <col class="report-hours-lg"/>
                    <col class="report-hours-lg" style="background-color: #F3F3F3"/>
                    <col class="report-hours-lg"/>
                    <col class="report-hours-lg" style="background-color: #F3F3F3"/>
                    <col class="report-hours-lg"/>
                    <col class="report-hours-lg" style="background-color: #F3F3F3"/>
                    <col class="report-hours-lg"/>
                </colgroup>
                <thead>
                <tr>
                    <td class="modified">&nbsp;</td>
                    <td class="general">&nbsp;</td>
                    <?php
                    foreach ($this->view_data['event_types'] as $event_type_title) {
                        echo "<td class='report-hours-lg'>$event_type_title</td>";
                    }
                    ?>
                    <td class="report-hours-lg">Total Hours</td>
                    <td class="report-hours-lg">Total Sessions</td>
                </tr>
                </thead>
                <tbody>
                <?php
                if ((is_array($department_data['department_entries'])) && (count($department_data['department_entries']))) {
                    foreach ($department_data['department_entries'] as $division_name => $division_entries) {
                        echo "<tr>\n";
                        echo "	<td colspan='".(4 + sizeof($this->view_data['event_types']))."' style=\"padding-left: 2%\"><strong>" . html_encode($division_name) . "</strong></td>\n";
                        echo "</tr>\n";

                        if ((is_array($division_entries["people"])) && (count($division_entries["people"]))) {
                            foreach ($division_entries["people"] as $result) {
                                if (($this->PROCESSED["show_all_teachers"]) || ((bool)$result["contributor"])) {
                                    ?>
                                    <tr <?php echo((!$result["number"]) ? " class=\"np\"" : ""); ?>>
                                        <td class="modified<?php echo((!(bool)$result["contributor"]) ? " np" : ""); ?>"><?php echo((!$result["number"]) ? "<img src=\"" . ENTRADA_URL . "/images/checkbox-no-number.gif\" width=\"14\" height=\"14\" alt=\"No Number\" title=\"No Number\" />" : "&nbsp;"); ?></td>
                                        <td class="general<?php echo((!(bool)$result["contributor"]) ? " np" : ""); ?>"><?php echo html_encode($result["fullname"]); ?></td>
                                        <?php
                                        foreach ($this->view_data["event_types"] as $event_type_id => $event_type_title) {
                                            $session_other_event_type = $result['session_other_event_types'][$event_type_id];
                                            ?>
                                            <td class="report-hours-lg<?php echo((!(bool)$result["contributor"]) ? " np" : ""); ?>"><?php echo(($session_other_event_type) ? display_half_days($session_other_event_type, $event_type_title) : "&nbsp;"); ?></td>
                                            <?php
                                        }
                                        ?>
                                        <td class="report-hours-lg<?php echo((!(bool)$result["contributor"]) ? " np" : ""); ?>"><?php echo(($result['duration_total']) ? display_hours($result['duration_total']) : "&nbsp;"); ?></td>
                                        <td class="report-hours-lg<?php echo((!(bool)$result["contributor"]) ? " np" : ""); ?>"><?php echo(($result['session_total']) ? $result['session_total'] : "&nbsp;"); ?></td>
                                    </tr>
                                    <?php
                                    $i++;
                                }
                            }
                        }
                        if (is_array($division_entries["courses"]) && count($division_entries["courses"]) &&
                            $division_entries['division_duration_final_total'] && $division_entries['division_session_final_total']) {
                            ?>
                            <tr>
                                <td colspan="<?php echo (2 + sizeof($this->view_data['event_types'])) ?>">&nbsp;</td>
                            </tr>
                            <tr class="modified" style="font-weight: normal">
                                <td class="modified">&nbsp;</td>
                                <td class="general"><?php echo html_encode($division_name); ?> Totals:</td>
                                <?php
                                foreach ($this->view_data["event_types"] as $event_type_id => $event_type_title) {
                                    $session_total_other_event_type = $division_entries['division_session_total_other_event_types'][$event_type_id];
                                    ?>
                                    <td class="report-hours-lg"><?php echo(($session_total_other_event_type) ? display_half_days($session_total_other_event_type, $event_type_title) : "&nbsp;"); ?></td>
                                    <?php
                                }
                                ?>
                                <td class="report-hours-lg"><?php echo(($division_entries['division_duration_final_total']) ? display_hours($division_entries['division_duration_final_total']) : "&nbsp;"); ?></td>
                                <td class="report-hours-lg"><?php echo(($division_entries['division_session_final_total']) ? $division_entries['division_session_final_total'] : "&nbsp;"); ?></td>
                            </tr>
                            <?php
                        }

                        echo "<tr>\n";
                        echo "	<td colspan='".(4 + sizeof($this->view_data['event_types']))."'>&nbsp;</td>\n";
                        echo "</tr>\n";
                    }

                    ?>
                    <tr class="na" style="font-weight: bold">
                        <td class="modified">&nbsp;</td>
                        <td class="general"><?php echo html_encode($department_name); ?> Totals:</td>
                        <?php
                        // the above totals aren't set anyway so I'm just creating the columns
                        foreach ($this->view_data['event_types'] as $event_type_title) {
                            echo "<td class='report-hours-lg'></td>";
                        }
                        ?>
                        <td class="report-hours-lg"><?php echo(($department_data['department_duration_final_total']) ? display_hours($department_data['department_duration_final_total']) : "&nbsp;"); ?></td>
                        <td class="report-hours-lg"><?php echo(($department_data['department_session_final_total']) ? $department_data['department_session_final_total'] : "&nbsp;"); ?></td>
                    </tr>
                    <?php
                }
                ?>
                </tbody>
            </table>
            <br/>
            <?php
        }
    }

    ?>
    <table class="tableList" cellspacing="0" summary="Total Report Summary">
        <colgroup>
            <col class="modified" />
            <col class="general" />
            <col class="report-hours-lg" style="background-color: #F3F3F3" />
            <col class="report-hours-lg" />
            <col class="report-hours-lg" style="background-color: #F3F3F3" />
            <col class="report-hours-lg" />
            <col class="report-hours-lg" style="background-color: #F3F3F3" />
            <col class="report-hours-lg" />
            <col class="report-hours-lg" style="background-color: #F3F3F3" />
            <col class="report-hours-lg" />
            <col class="report-hours-lg" style="background-color: #F3F3F3" />
            <col class="report-hours-lg" />
            <col class="report-hours-lg" style="background-color: #F3F3F3" />
            <col class="report-hours-lg" />
            <col class="report-hours-lg" style="background-color: #F3F3F3" />
            <col class="report-hours-lg" />
        </colgroup>
        <thead>
            <tr>
                <td class="modified">&nbsp;</td>
                <td class="general">&nbsp;</td>
                <?php
                foreach ($this->view_data['event_types'] as $event_type_title) {
                    echo "<td class='report-hours-lg'>$event_type_title</td>";
                }
                ?>
                <td class="report-hours-lg">Total Hours</td>
                <td class="report-hours-lg">Total Sessions</td>
            </tr>
        </thead>
        <tbody>
            <?php
            if ((is_array($this->view_data["courses"])) && (count($this->view_data["courses"]))) {
                if ($this->view_data["courses"]['duration_final_total'] && $this->view_data["courses"]['session_final_total']) {
                    ?>
                    <tr>
                        <td colspan="<?php echo (2 + sizeof($this->view_data['event_types'])) ?>">&nbsp;</td>
                    </tr>
                    <tr style="background-color: #DEE6E3; font-weight: bold">
                        <td class="modified">&nbsp;</td>
                        <td class="general">Final Totals:</td>
                        <?php
                            foreach ($this->view_data["event_types"] as $event_type_id => $event_type_title) {
                                $other_event_type_entry = $this->view_data["courses"]["session_total_other_event_types"][$event_type_id];
                                ?>
                                <td class="report-hours-lg"><?php echo (($other_event_type_entry) ? display_half_days($other_event_type_entry, $event_type_title) : "&nbsp;"); ?></td>
                                <?php
                            }
                        ?>
                        <td class="report-hours-lg"><?php echo (($this->view_data["courses"]['duration_final_total']) ? display_hours($this->view_data["courses"]['duration_final_total']) : "&nbsp;"); ?></td>
                        <td class="report-hours-lg"><?php echo (($this->view_data["courses"]['session_final_total']) ? $this->view_data["courses"]['session_final_total'] : "&nbsp;"); ?></td>
                    </tr>
                    <?php
                }
            }
            ?>
        </tbody>
    </table>
    <?php
    if ((is_array($this->view_data['no_staff_number'])) && $this->view_data['total_no_staff_number']) {
        ?>
        <div class="no-printing">
            <h2>Numberless Faculty</h2>
            In order to increase the accuracy of our reporting we need to ensure that all faculty members have their staff number attached to their MEdTech profile.
            There currently <?php echo $this->view_data['total_no_staff_number']; ?> faculty member<?php echo (($this->view_data['total_no_staff_number'] != 1) ? "s" : ""); ?> in the system that have no
            staff numbers associated with them; they are therefore put into an &quot;Unknown or N/A&quot; department.
            <br /><br />
            <table style="width: 100%" cellspacing="0" summary="Faculty Members Without Staff Numbers">
                <tbody>
                <tr>
                    <?php
                    $i = 0;
                    $columns = 0;
                    $max_columns = 4;
                    foreach($this->view_data['no_staff_number'] as $result) {
                        $i++;
                        $columns++;
                        echo "\t<td".((($i == $this->view_data['total_no_staff_number']) && ($columns < $max_columns)) ? " colspan=\"".(($max_columns - $columns) + 1)."\"" :"").">".html_encode($result["fullname"])."</td>\n";

                        if (($columns == $max_columns) || ($i == $this->view_data['total_no_staff_number'])) {
                            $columns = 0;
                            echo "</tr>\n";

                            if ($i < $this->view_data['total_no_staff_number']) {
                                echo "<tr>\n";
                            }
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    $sidebar_html  = "<ul class=\"menu\">\n";

    foreach($this->view_data['department_sidebar'] as $result) {
        $sidebar_html .= "	<li class=\"link\"><a href=\"".$result["department_link"]."\" title=\"".html_encode($result["department_name"])."\">".html_encode($result["department_name"])."</a></li>\n";
    }

    $sidebar_html .= "</ul>";
    new_sidebar_item("Department List", $sidebar_html, "department-list", "open");
}
