<div class="row-fluid">
    <div class="span8 pull-left">
        <?php
        if (clean_input($event_info["event_description"], array("allowedtags", "nows")) != "") {
            echo "<div class=\"event-description\">";
            echo trim(strip_selected_tags($event_info["event_description"], array("font")));
            echo "</div>";
        }

        if (clean_input($event_info["event_message"], array("allowedtags", "nows")) != "") {
            echo "<div class=\"event-message\">\n";
            echo "	<h3>Required Preparation</h3>\n";
            echo	trim(strip_selected_tags($event_info["event_message"], array("font")));
            echo "</div>\n";
        }
        ?>
    </div>

    <div class="span4 pull-right">
        <table class="event-details">
            <tbody>
            <tr>
                <th>Date &amp; Time</th>
                <td><?php echo date(DEFAULT_DATE_FORMAT, $event_info["event_start"]); ?></td>
            </tr>
            <tr class="spacer">
                <td colspan="2"><hr></td>
            </tr>
            <tr>
                <th>Location</th>
                <td><?php echo (($event_info["event_location"]) ? $event_info["event_location"] : "To Be Announced"); ?></td>
            </tr>

            <tr>
                <th>Attendance</th>
                <td>
                    <?php echo (isset($event_info["attendance_required"]) && ($event_info["attendance_required"] == 0) ? "<em>Optional</em>" :  "Required"); ?>
                </td>
            </tr>
            <tr class="spacer">
                <td colspan="2"><hr></td>
            </tr>
            <tr>
                <th>Duration</th>
                <td>
                    <?php
                    echo (((int) $event_info["event_duration"]) ? $event_info["event_duration"]." Minutes" : "To Be Announced");

                    if ($event_types) {
                        echo "<br /><br />";
                        echo "<div class=\"content-small\">\n";
                        echo "<strong>Breakdown</strong><br />";
                        foreach($event_types as $type) {
                            echo "".$type["duration"]." minutes of ".strtolower($type["eventtype_title"])."<br />";
                        }
                        echo "</div>";
                    }
                    ?>
                </td>
            </tr>
            <?php
            if ($event_contacts) {
                if (isset($event_contacts["teacher"]) && ($count = count($event_contacts["teacher"]))) {
                    ?>
                    <tr class="spacer">
                        <td colspan="2"><hr></td>
                    </tr>
                    <tr>
                        <th><?php echo ($count != 1) ? $translate->_("Teachers") : $translate->_("Teacher"); ?></th>
                        <td>
                            <?php
                            foreach ($event_contacts["teacher"] as $contact) {
                                echo "<a href=\"".ENTRADA_RELATIVE."/people?id=".$contact["proxy_id"]."\" class=\"event-details-item\">".html_encode($contact["fullname"])."</a>\n";
                            }
                            ?>
                        </td>
                    </tr>
                    <?php
                }
                if (isset($event_contacts["tutor"]) && ($count = count($event_contacts["tutor"]))) {
                    ?>
                    <tr class="spacer">
                        <td colspan="2"><hr></td>
                    </tr>
                    <tr>
                        <th>Tutor<?php echo (($count != 1) ? "s" : ""); ?></th>
                        <td>
                            <?php
                            foreach ($event_contacts["tutor"] as $contact) {
                                echo "<a href=\"".ENTRADA_RELATIVE."/people?id=".$contact["proxy_id"]."\">".html_encode($contact["fullname"])."</a>\n";
                            }
                            ?>
                        </td>
                    </tr>
                    <?php
                }
                if (isset($event_contacts["ta"]) && ($count = count($event_contacts["ta"]))) {
                    ?>
                    <tr class="spacer">
                        <td colspan="2"><hr></td>
                    </tr>
                    <tr>
                        <th>TA<?php echo (($count != 1) ? "s" : ""); ?></th>
                        <td>
                            <?php
                            foreach ($event_contacts["ta"] as $contact) {
                                echo "<a href=\"".ENTRADA_RELATIVE."/people?id=".$contact["proxy_id"]."\">".html_encode($contact["fullname"])."</a><br />\n";
                            }
                            ?>
                        </td>
                    </tr>
                    <?php
                }
                if (isset($event_contacts["auditor"]) && ($count = count($event_contacts["auditor"]))) {
                    ?>
                    <tr class="spacer">
                        <td colspan="2"><hr></td>
                    </tr>
                    <tr>
                        <th>Auditor<?php echo (($count != 1) ? "s" : ""); ?></th>
                        <td>
                            <?php
                            foreach ($event_contacts["auditor"] as $contact) {
                                echo "<a href=\"".ENTRADA_RELATIVE."/people?id=".$contact["proxy_id"]."\">".html_encode($contact["fullname"])."</a><br />\n";
                            }
                            ?>
                        </td>
                    </tr>
                    <?php
                }
            }
            ?>
            <tr class="spacer">
                <td colspan="2"><hr></td>
            </tr>
            <?php


            if (($ENTRADA_USER->getActiveGroup() == "student" && $event->getAudienceVisible()) || $ENTRADA_USER->getActiveGroup() != "student") { ?>
                <tr>
                    <th>Audience</th>
                    <td>
                        <?php
                        if ($event_audience) {
                            foreach ($event_audience as $audience) {
                                $a = $audience->getAudience($event->getEventStart());
                                if ($audience->getCustomTime() && $audience->getCustomTime() != 0) {
                                    $custom_time_html = '<span class="time">' . date("g:i a", $audience->getCustomTimeStart()) . ' - ' . date("g:i a", $audience->getCustomTimeEnd()) . '</span>';
                                } else {
                                    $custom_time_html = "";
                                }

                                if ($a && method_exists($a, "getAudienceMembers") && is_array($a->getAudienceMembers())) {
                                    $link = false;
                                    switch ($audience->getAudienceType()) {
                                        case "proxy_id" :
                                            $css_class = "fa fa-user";
                                            break;
                                        case "course_id" :
                                        case "group_id" :
                                        case "cohort" :
                                            if ($ENTRADA_USER->getActiveGroup() == "student") {
                                                if (in_array($ENTRADA_USER->getActiveID(), array_keys($a->getAudienceMembers()))) {
                                                    $link = true;
                                                }
                                            } else {
                                                if (count($a->getAudienceMembers()) > 0) {
                                                    $link = true;
                                                }
                                            }
                                            $css_class = "fa fa-users";
                                            break;
                                        default:
                                            $css_class = "fa fa-users";
                                            break;
                                    }

                                    if ($a) {
                                        if ($link) {
                                            ?>
                                            <a href="#audience-<?php echo $audience->getEventAudienceID(); ?>" data-toggle="modal"><?php echo $a->getAudienceName(); ?></a>
                                            <?php
                                        } else {
                                            echo $a->getAudienceName();
                                        }

                                        if ($custom_time_html != "") {
                                            echo "<br />\n";
                                            echo $custom_time_html . "\n";
                                        }

                                        if ($a && $link && count($a->getAudienceMembers() > 0)) {
                                            ?>
                                            <div id="audience-<?php echo $audience->getEventAudienceID(); ?>" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                                                <div class="modal-header">
                                                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                                                    <h3 id="myModalLabel"><?php echo $a->getAudienceName(); ?> Group Members</h3>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row-fluid">
                                                        <?php
                                                        $count = round(count($a->getAudienceMembers()) / 3);
                                                        $i = 0;

                                                        echo "<div class=\"span4\">\n";
                                                        foreach ($a->getAudienceMembers() as $member) {
                                                            if (($i == $count || $i == $count * 2) && $count != 0) {
                                                                echo "</div><div class=\"span4\">\n";
                                                            }
                                                            echo $member["firstname"] . " " . $member["lastname"]."<br />\n";
                                                            $i++;
                                                        }
                                                        echo "</div>\n"
                                                        ?>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
                                                </div>
                                            </div>
                                            <?php
                                        }
                                    }
                                } elseif (method_exists($a, "getAudienceName")) {
                                    echo $a->getAudienceName();
                                    if ($custom_time_html != "") {
                                        echo "<br />\n";
                                        echo $custom_time_html . "\n";
                                    }
                                } else {
                                    echo "Not Available";
                                }

                                echo "<br />";
                            }
                        } else {
                            echo "Not Available";
                        }
                        ?>
                    </td>
                </tr>
                <tr class="spacer">
                    <td colspan="2"><hr></td>
                </tr>
            <?php }
            if ($assessments) {
                $html = "<tr><th>" . $translate->_("Assessment") . "</th><td>";
                $read_assessment = false;
                foreach ($assessments as $assessment) {
                    $course = Models_Course::fetchRowByID($assessment["course_id"]);
                    if ($ENTRADA_ACL->amIAllowed(new GradebookResource($course->getID(), $course->getOrganisationID()), "read")) {
                        $html .= "<a href=\"" . ENTRADA_RELATIVE . "/admin/gradebook/assessments?section=grade&id=" . $assessment["course_id"] . "&assessment_id=" . $assessment["assessment_id"] . "\" class=\"event-details-item\">" . limit_chars($assessment["name"]) . "</a>\n";
                        $read_assessment = true;
                    }
                }
                echo ($read_assessment ? $html . "</td></tr>" : "");
            }

            /**
             * @todo simpson This needs to be fixed as $event_audience_type is no longer for grad_year.
             */
            if (isset($event_audience_type) && $event_audience_type == "cohort") {
                $query = "	SELECT a.`event_id`, a.`event_title`, b.`audience_value` AS `event_cohort`
                                                    FROM `events` AS a
                                                    LEFT JOIN `event_audience` AS b
                                                    ON b.`event_id` = a.`event_id`
                                                    LEFT JOIN `courses` AS c
                                                    ON a.`course_id` = c.`course_id`
                                                    AND c.`organisation_id` = ".$db->qstr($event_info["organisation_id"])."
                                                    WHERE (a.`event_start` BETWEEN ".$db->qstr($event_info["event_start"])." AND ".$db->qstr(($event_info["event_finish"] - 1)).")
                                                    AND c.`course_active` = '1'
                                                    AND a.`event_id` <> ".$db->qstr($event_info["event_id"])."
                                                    AND b.`audience_type` = 'cohort'
                                                    AND b.`audience_value` IN (".$associated_cohorts_string.")
                                                    ORDER BY `event_title` ASC";
                $results = $db->GetAll($query);
                if ($results) {
                    echo "	<tr>\n";
                    echo "		<td colspan=\"2\">&nbsp;</td>\n";
                    echo "	</tr>\n";
                    echo "	<tr>\n";
                    echo "		<th>Overlapping Event".((count($results) != 1) ? "s" : "")."</th>\n";
                    echo "		<td>\n";
                    echo "          <ul class=\"menu\">\n";
                    foreach ($results as $result) {
                        echo "          <li class=\"link\"><a href=\"".ENTRADA_RELATIVE."/events?id=".$result["event_id"]."\">".html_encode($result["event_title"])."</a></li>\n";
                    }
                    echo "          </ul>\n";
                    echo "		</td>\n";
                    echo "	</tr>\n";
                }
            }
            ?>
            </tbody>
        </table>
    </div>
</div>

<div>
    <?php
    $query = "  SELECT ek.`keyword_id`, d.`descriptor_name`
                                    FROM `event_keywords` AS ek
                                    JOIN `mesh_descriptors` AS d
                                    ON ek.`keyword_id` = d.`descriptor_ui`
                                    AND ek.`event_id` = " . $db->qstr($EVENT_ID) . "
                                    ORDER BY `descriptor_name`";

    $results = $db->GetAll($query);
    if ($results && (!$event_info['keywords_hidden'] || $ENTRADA_USER->getActiveGroup() != "student") && isset($event_info['keywords_release_date']) && time() >= $event_info['keywords_release_date']) {
        $include_keywords = true;
        ?>
        <a name="event-keywords-section"></a>
        <h2 title="Event Keywords Section">Event Keywords</h2>
        <div id="event-keywords-section">
            <ul>
                <?php
                foreach($results as $result) {
                    echo "<li data-dui=\"" . $result['keyword_id'] . "\" data-dname=\"" . $result['descriptor_name'] . "\" id=\"tagged_keyword\">" . $result['descriptor_name'] . "</li>";
                }
                ?>
            </ul>
        </div>
        <?php
    }
    ?>
</div>

<div>
    <?php
    $query = "SELECT b.`objective_id`, b.`objective_name`
                                    FROM `event_objectives` AS a
                                    LEFT JOIN `global_lu_objectives` AS b
                                    ON b.`objective_id` = a.`objective_id`
                                    JOIN `objective_organisation` AS c
                                    ON b.`objective_id` = c.`objective_id`
                                    AND c.`organisation_id` = ".$db->qstr($event->getOrganisationID())."
                                    WHERE a.`objective_type` = 'event'
                                    AND b.`objective_active` = '1'
                                    AND a.`event_id` = ".$db->qstr($EVENT_ID)."
                                    ORDER BY b.`objective_name` ASC;";
    $clinical_presentations	= $db->GetAll($query);
    $show_event_objectives	= ((clean_input($event_info["event_objectives"], array("notags", "nows")) != "") ? true : false);
    $show_clinical_presentations = (($clinical_presentations) ? true : false);

    $show_curriculum_objectives = false;
    list($curriculum_objectives,$top_level_id) = courses_fetch_objectives($event->getOrganisationID(),array($event_info["course_id"]),-1, 1, false, false, $EVENT_ID, true);

    $temp_objectives = $curriculum_objectives["objectives"];
    foreach ($temp_objectives as $objective_id => $objective) {
        unset($curriculum_objectives["used_ids"][$objective_id]);
        $curriculum_objectives["objectives"][$objective_id]["objective_primary_children"] = 0;
        $curriculum_objectives["objectives"][$objective_id]["objective_secondary_children"] = 0;
        $curriculum_objectives["objectives"][$objective_id]["objective_tertiary_children"] = 0;
    }
    foreach ($curriculum_objectives["objectives"] as $objective_id => $objective) {
        if (isset($objective["event_objective"]) && $objective["event_objective"]) {
            foreach ($objective["parent_ids"] as $parent_id) {
                if ($objective["primary"] || $objective["secondary"] || $objective["tertiary"] || $curriculum_objectives["objectives"][$parent_id]["primary"] || $curriculum_objectives["objectives"][$parent_id]["secondary"] || $curriculum_objectives["objectives"][$parent_id]["tertiary"]) {
                    $curriculum_objectives["objectives"][$parent_id]["objective_".($objective["primary"] || ($curriculum_objectives["objectives"][$parent_id]["primary"] && !$objective["secondary"] && !$objective["tertiary"]) ? "primary" : ($objective["secondary"] || ($curriculum_objectives["objectives"][$parent_id]["secondary"] && !$objective["primary"] && !$objective["tertiary"]) ? "secondary" : "tertiary"))."_children"]++;
                    if ($curriculum_objectives["objectives"][$parent_id]["primary"]) {
                        $curriculum_objectives["objectives"][$objective_id]["primary"] = true;
                    } elseif ($curriculum_objectives["objectives"][$parent_id]["secondary"]) {
                        $curriculum_objectives["objectives"][$objective_id]["secondary"] = true;
                    } elseif ($curriculum_objectives["objectives"][$parent_id]["tertiary"]) {
                        $curriculum_objectives["objectives"][$objective_id]["tertiary"] = true;
                    }
                }
            }
            $show_curriculum_objectives = true;
        }
    }
    foreach ($temp_objectives as $objective_id => $objective) {
        if (!isset($objective["event_objective"]) || !$objective["event_objective"]) {
            if (isset($objective["primary"]) && $objective["primary"] && !$curriculum_objectives["objectives"][$objective_id]["objective_primary_children"]) {
                $curriculum_objectives["objectives"][$objective_id]["primary"] = false;
            } elseif (isset($objective["secondary"]) && $objective["secondary"] && !$curriculum_objectives["objectives"][$objective_id]["objective_secondary_children"]) {
                $curriculum_objectives["objectives"][$objective_id]["secondary"] = false;
            } elseif (isset($objective["tertiary"]) && $objective["tertiary"] && !$curriculum_objectives["objectives"][$objective_id]["objective_tertiary_children"]) {
                $curriculum_objectives["objectives"][$objective_id]["tertiary"] = false;
            }
        }
    }
    if (isset($event_info["objectives_release_date"]) && (($event_info["objectives_release_date"] == 0) || (time() >= $event_info["objectives_release_date"]))) {
    if ($show_event_objectives || $show_clinical_presentations || $show_curriculum_objectives) {
        $include_objectives = true;

        echo "<a name=\"event-objectives-section\"></a>\n";
        echo "<h2 title=\"" . $translate->_("Event Objectives Section") . "\">" . $translate->_("Event Objectives") . "</h2>\n";
        echo "<div id=\"event-objectives-section\">\n";

        if ($show_event_objectives) {
            echo "	<div class=\"section-holder\">\n";
            echo "		<h3>Free-Text Objectives</h3>\n";
            echo		trim(strip_selected_tags($event_info["event_objectives"], array("font")));
            echo "	</div>\n";
        }

    if ($show_clinical_presentations) { ?>
        <div class="section-holder mapped">
            <h2 title="<?php echo $translate->_("Clinical Presentation List"); ?>" class="list-heading"><?php echo $translate->_("Clinical Presentations"); ?></h2>
            <ul class="objective-list mapped-list">
                <?php
                foreach ($clinical_presentations as $key => $result) {
                    $set = Models_Objective::fetchObjectiveSet($result["objective_id"]);
                    ?>
                    <li>
                        <strong><?php echo $result["objective_name"]; ?></strong>
                        <div class="objective-description">From the <?php echo $translate->_("Curriculum Tag Set"); ?>: <strong><?php echo $set->getName(); ?></strong></div>
                    </li>
                    <?php
                }
                ?>
            </ul>
        </div>
    <?php
    }

    if ($show_curriculum_objectives) {
    ?>
        <script type="text/javascript">
            function renewList (hierarchy) {
                if (hierarchy != null && hierarchy) {
                    hierarchy = 1;
                } else {
                    hierarchy = 0;
                }
                new Ajax.Updater('objectives_list', '<?php echo ENTRADA_RELATIVE; ?>/api/objectives.api.php',
                    {
                        method:	'post',
                        parameters: 'course_ids=<?php echo $event_info["course_id"] ?>&hierarchy='+hierarchy+'&event_id=<?php echo $EVENT_ID; ?>'
                    }
                );
            }
        </script>
    <?php
    echo "<div class=\"section-holder\">\n";
    echo "	<h3>" . $translate->_("Curriculum Objectives") . "</h3>\n";
    echo "	<strong>The learner will be able to:</strong>";
    echo	course_objectives_in_list($curriculum_objectives, $top_level_id,$top_level_id, false, false, 1, true)."\n";
    echo "</div>\n";
    }
    }
    $c_objectives = array();
    foreach ($curriculum_objectives["objectives"] as $objective_id => $objective) {
        $c_objectives[] = $objective_id;
    }
    $COURSE_ID = $event_info["course_id"];
    $query = "	SELECT a.*, COALESCE(b.`objective_details`,a.`objective_description`) AS `objective_description`, COALESCE(b.`objective_type`,c.`objective_type`) AS `objective_type`,
                                    b.`importance`,c.`objective_details`, COALESCE(c.`eobjective_id`,0) AS `mapped`,
                                    COALESCE(b.`cobjective_id`,0) AS `mapped_to_course`
                                    FROM `global_lu_objectives` a
                                    LEFT JOIN `course_objectives` b
                                    ON a.`objective_id` = b.`objective_id`
                                    AND b.`course_id` = ".$db->qstr($COURSE_ID)."
                                    AND b.`active` = '1'
                                    LEFT JOIN `event_objectives` c
                                    ON c.`objective_id` = a.`objective_id`
                                    AND c.`event_id` = ".$db->qstr($EVENT_ID)."
                                    WHERE a.`objective_active` = '1'
                                    AND (c.`event_id` = ".$db->qstr($EVENT_ID)." OR b.`course_id` = ".$db->qstr($COURSE_ID).")
                                    GROUP BY a.`objective_id`
                                    ORDER BY a.`objective_id` ASC";
    $mapped_objectives = $db->GetAll($query);

    $explicit_event_objectives = false;
    if ($mapped_objectives) {
        foreach ($mapped_objectives as $objective) {
            //if its mapped to the event, but not the course, then it belongs in the event objective list
            if ($objective["mapped"] && !$objective["mapped_to_course"]) {
                $objective_name = $translate->_("events_filter_controls");
                $clinical_presentations_name = $objective_name["cp"]["global_lu_objectives_name"];
                if (!event_objective_parent_mapped_course($objective["objective_id"],$EVENT_ID)) {
                    $query = "SELECT b.`objective_id`, b.`objective_name`
                                FROM `event_objectives` AS a
                                LEFT JOIN `global_lu_objectives` AS b
                                ON b.`objective_id` = a.`objective_id`
                                JOIN `objective_organisation` AS c
                                ON b.`objective_id` = c.`objective_id`
                                AND c.`organisation_id` = ".$db->qstr($event->getOrganisationID())."
                                WHERE a.`objective_type` = 'event'
                                AND b.`objective_active` = '1'
                                AND a.`event_id` = ".$db->qstr($EVENT_ID)."
                                AND a.`objective_id` = ".$db->qstr($objective["objective_id"])."
                                ORDER BY b.`objective_name` ASC";
                    $result = $db->GetRow($query);
                    if (!$result) {
                        if (!in_array($objective["objective_id"], $c_objectives)) {
                            $explicit_event_objectives[] = $objective;
                        } else {
                            $query = "SELECT * FROM `course_objectives` WHERE `course_id` = ".$db->qstr($COURSE_ID)." AND `objective_id` = ".$db->qstr($objective["objective_id"]);
                            $result = $db->GetRow($query);
                            if ($result) {
                                $explicit_event_objectives[] = $objective;
                            }
                        }
                    }
                }
            }
        }
    }
    ?>
        <div class="section-holder">
            <div id="mapped_objectives" class="mapped">
                <div id="event-list-wrapper" <?php echo ($explicit_event_objectives)?'':' style="display:none;"';?>>
                    <a name="event-objective-list"></a>
                    <h2 id="event-toggle"  title="<?php echo $translate->_("Event Objective List"); ?>" class="list-heading"><?php echo $translate->_("Event Specific Objectives"); ?></h2>
                    <div id="event-objective-list">
                        <ul class="objective-list mapped-list" id="mapped_event_objectives" data-importance="event">
                            <?php
                            if ($explicit_event_objectives) {
                                foreach ($explicit_event_objectives as $objective) {
                                    $title = ($objective["objective_code"] ? $objective["objective_code"] . ': ' . $objective["objective_name"] : $objective["objective_name"]);
                                    ?>
                                    <li class = "mapped-objective"
                                        id = "mapped_objective_<?php echo $objective["objective_id"]; ?>"
                                        data-id = "<?php echo $objective["objective_id"]; ?>"
                                        data-title="<?php echo $title;?>"
                                        data-description="<?php echo htmlentities($objective["objective_description"]);?>"
                                        data-mapped="<?php echo $objective["mapped_to_course"]?1:0;?>">
                                        <strong><?php echo $title; ?></strong>
                                        <div class="objective-description">
                                            <?php
                                            $set = fetch_objective_set_for_objective_id($objective["objective_id"]);
                                            if ($set) {
                                                echo "From the " . $translate->_("Curriculum Tag Set") . ": <strong>".$set["objective_name"]."</strong><br/>";
                                            }

                                            echo $objective["objective_description"];
                                            ?>
                                        </div>
                                    </li>
                                    <?php
                                }
                            }
                            ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    <?php
    $query = "SELECT a.`topic_id`,a.`topic_name`, e.`topic_coverage`, e.`topic_time`
                                    FROM `events_lu_topics` AS a
                                    LEFT JOIN `topic_organisation` AS b
                                    ON a.`topic_id` = b.`topic_id`
                                    LEFT JOIN `courses` AS c
                                    ON b.`organisation_id` = c.`organisation_id`
                                    LEFT JOIN `events` AS d
                                    ON c.`course_id` = d.`course_id`
                                    JOIN `event_topics` AS e
                                    ON d.`event_id` = e.`event_id`
                                    AND a.`topic_id` = e.`topic_id`
                                    WHERE d.`event_id` = ".$db->qstr($EVENT_ID);
    $topic_results = $db->GetAll($query);
    if ($topic_results) {
    ?>
        <table style="width: 100%" cellspacing="0">
            <colgroup>
                <col style="width: 80%" />
                <col style="width: 10%" />
                <col style="width: 10%" />
            </colgroup>
            <tr>
                <td colspan="3">
                    <h2>Event Topics</h2>
                    <div class="content-small" style="padding-bottom: 10px">These topics will be covered in this learning event.</div>
                </td>
            </tr>
            <tr>
                <td><span style="font-weight: bold; color: #003366;">Hot Topic</span></td>
                <td><span style="font-weight: bold; color: #003366;">Major</span></td>
                <td><span style="font-weight: bold; color: #003366;">Minor</span></td>
            </tr>
            <?php
            foreach ($topic_results as $topic_result) {
                echo "<tr>\n";
                echo "	<td>".html_encode($topic_result["topic_name"])."</td>\n";
                echo "	<td>".(($topic_result["topic_coverage"] == "major") ? "<img src=\"".ENTRADA_URL."/images/question-correct.gif"."\" />" : "" )."</td>\n";
                echo "	<td>".(($topic_result["topic_coverage"] == "minor") ? "<img src=\"".ENTRADA_URL."/images/question-correct.gif"."\" />": "" )."</td>\n";
                echo "</tr>\n";
            }
            echo "<tr><td colspan=\"2\">&nbsp;</td></tr>";
            ?>
        </table>
        <?php
    }
    }
    ?>
</div>
<?php

$event_resource_entities = Models_Event_Resource_Entity::fetchAllByEventID($EVENT_ID);

if ($event_resource_entities) {
    $resource = false;
    $entity_timeframe_pre = array();
    $entity_timeframe_during = array();
    $entity_timeframe_post = array();
    $entity_timeframe_none = array();
    foreach ($event_resource_entities as $entity) {
        $resource_obj = $entity->getResource();
        if ($resource_obj && is_object($resource_obj)) {
            $resource = $resource_obj->toArray();
            if ($resource) {
                if (!isset($resource["hidden"]) || ((int) $resource["hidden"] == 0) || (((int) $resource["hidden"] == 1) && ($ENTRADA_USER->getActiveGroup() != "student"))) {
                    switch ($entity->getEntityType()) {
                        case 1 :
                        case 5 :
                        case 6 :
                        case 11 :

                            if ($entity->getEntityType() == 1) {
                                $resource["type"] = "Audio / Video";
                            } else if ($entity->getEntityType() == 5) {
                                $resource["type"] = "Lecture Notes";
                            } else if ($entity->getEntityType() == 6) {
                                $resource["type"] = "Lecture Slides";
                            } else if ($entity->getEntityType() == 11) {
                                $resource["type"] = "Other";
                            }

                            $resource_statistic = $resource_obj->getViewed();
                            $resource["title"] = "";
                            $resource["description"] = "";

                            $title = ($resource["file_title"] != "" ? $resource["file_title"] : $resource["file_name"]);
                            if ((((!(int) $resource["release_date"]) || ($resource["release_date"] <= time())) && ((!(int) $resource["release_until"]) || ($resource["release_until"] >= time())))) {
                                $resource["title"] = "<a class=\"resource-link\" href=\"".ENTRADA_RELATIVE."/file-event.php?id=".html_encode($resource["efile_id"])."\" title=\"Click to download ".html_encode($title)."\"".(((int) $resource["access_method"]) ? " target=\"_blank\"" : "").">".html_encode($title)."</a>";
                            } else {
                                $resource["title"] = "<p class=\"resource-title\">". html_encode($title) ."</p>";
                            }

                            if ($resource_statistic) {
                                $resource["title"] .= (((int) $resource_statistic->getTimestamp()) ? (((int) $resource_statistic->getTimestamp() >= (int) $resource["updated_date"]) ? "<span class=\"resource-viewed pull-right\" title=\"You have already downloaded the latest version of this file.\"><i class=\"icon-ok icon-white\"></i></span>" : "<span class=\"resource-updated pull-right\" title=\"This file has been updated since you have last downloaded it.\"><i class=\"icon-exclamation-sign icon-white\"></i></span>") : "");
                            }

                            if (((int) $resource["release_date"]) && ($resource["release_date"] > time())) {
                                $resource["description"] .=  "<p class=\"muted resource-description\">This file will be available for downloading <strong>".date(DEFAULT_DATE_FORMAT, $resource["release_date"])."</strong>.</p>";
                            } elseif (((int) $resource["release_until"]) && ($resource["release_until"] < time())) {
                                $resource["description"] .= "<p class=\"muted resource-description\">This file was only available for download until <strong>".date(DEFAULT_DATE_FORMAT, $resource["release_until"])."</strong>. Please contact the primary teacher for assistance if required.</p>";
                            }

                            $resource["description"] .= "<p class=\"muted resource-description\">" . html_encode($resource["file_notes"]) . "</p>";
                            $resource["type"] =  html_encode($resource["type"] . " " . readable_size($resource["file_size"]));
                            $resource["resource_id"] = $entity->getEntityValue();
                            $resource["type_id"] = $entity->getEntityType();
                            $resource["viewed"] = $resource_obj->getViewed();
                            break;
                        case 2 :
                            $resource["title"] = "<p class=\"resource-title\">Class Work</p>";
                            $resource["description"] = "<p class=\"muted resource-description\">" . html_encode($resource["resource_class_work"]) . "</p>";
                            $resource["type"] = "Class Work";
                            $resource["type_id"] = $entity->getEntityType();
                            break;
                        case 3 :
                            $resource["title"] = "";
                            $resource["description"] = "";
                            $resource_statistic = $resource_obj->getViewed();

                            if ((((!(int) $resource["release_date"]) || ($resource["release_date"] <= time())) && ((!(int) $resource["release_until"]) || ($resource["release_until"] >= time())))) {
                                $resource["title"] = "<a class=\"resource-link\" href=\"".ENTRADA_RELATIVE."/link-event.php?id=".html_encode($resource["elink_id"])."\" title=\"Click to visit ".html_encode($resource["link"])."\"  target=\"_blank\">".(($resource["link_title"] != "") ? html_encode($resource["link_title"]) : html_encode($resource["link"]))."</a>\n";
                            } else {
                                $resource["title"] = "<p class=\"resource-title\">" . ($resource["link_title"] != "" ? html_encode($resource["link_title"]) : html_encode($resource["link"])) . "</p>";
                            }

                            if ($resource_statistic) {
                                $resource["title"] .= (((int) $resource_statistic->getTimestamp()) ? (((int) $resource_statistic->getTimestamp() >= (int) $resource["updated_date"]) ? "<span class=\"resource-viewed pull-right\" title=\"You have previously visited this link..\"><i class=\"icon-ok icon-white\"></i></span>" : "<span class=\"resource-updated pull-right\" title=\"An update to this link has been made, please re-visit it.\"><i class=\"icon-exclamation-sign icon-white\"></i></span>") : "");
                            }

                            if (((int) $resource["release_date"]) && ($resource["release_date"] > time())) {
                                $resource["description"] .=  "<p class=\"muted resource-description\">This link will become accessible <strong>".date(DEFAULT_DATE_FORMAT, $resource["release_date"])."</strong>.</p>";
                            } elseif (((int) $resource["release_until"]) && ($resource["release_until"] < time())) {
                                $resource["description"] .= "<p class=\"muted resource-description\">This link was only accessible until <strong>".date(DEFAULT_DATE_FORMAT, $resource["release_until"])."</strong>. Please contact the primary teacher for assistance if required.</p>";
                            }

                            $resource["description"] .= "<p class=\"muted resource-description\">" . html_encode($resource["link_notes"]) . "</p>";
                            $resource["type"] = "Link";
                            $resource["type_id"] = $entity->getEntityType();
                            $resource["viewed"] = $resource_obj->getViewed();
                            break;
                        case 4 :
                            $resource["title"] = "<p class=\"resource-title\">Homework</p>";
                            $resource["description"] = "<p class=\"muted resource-description\">" . html_encode($resource["resource_homework"]) . "</p>";
                            $resource["type"] = "Homework";
                            $resource["type_id"] = $entity->getEntityType();
                            break;
                        case 7 :
                            $resource["description"] = "";
                            $resource_statistic = $resource_obj->getViewed();

                            if ((((!(int) $resource["release_date"]) || ($resource["release_date"] <= time())) && ((!(int) $resource["release_until"]) || ($resource["release_until"] >= time())))) {
                                $resource["title"] = "<a class=\"resource-link\" href=\"".ENTRADA_RELATIVE."/link-event.php?id=".html_encode($resource["elink_id"])."\" title=\"Click to visit ".html_encode($resource["link"])."\"  target=\"_blank\">".(($resource["link_title"] != "") ? html_encode($resource["link_title"]) : html_encode($resource["link"]))."</a>\n";
                            } else {
                                $resource["title"] = "<p class=\"resource-title\">". (($resource["link_title"] != "") ? html_encode($resource["link_title"]) : html_encode($resource["link"])) ."</p>";
                            }

                            if ($resource_statistic) {
                                $resource["title"] .= (((int) $resource_statistic->getTimestamp()) ? (((int) $resource_statistic->getTimestamp() >= (int) $resource["updated_date"]) ? "<span class=\"resource-viewed pull-right\" title=\"You have previously visited this learning module\"><i class=\"icon-ok icon-white\"></i></span>" : "<span class=\"resource-updated pull-right\" title=\"An update to this learning module has been made, please re-visit it.\"><i class=\"icon-exclamation-sign icon-white\"></i></span>") : "");
                            }

                            if (((int) $resource["release_date"]) && ($resource["release_date"] > time())) {
                                $resource["description"] .=  "<p class=\"muted resource-description\">This learning module will become accessible <strong>".date(DEFAULT_DATE_FORMAT, $resource["release_date"])."</strong>.</p>";
                            } elseif (((int) $resource["release_until"]) && ($resource["release_until"] < time())) {
                                $resource["description"] .= "<p class=\"muted resource-description\">This learning module was only accessible until <strong>".date(DEFAULT_DATE_FORMAT, $resource["release_until"])."</strong>. Please contact the primary teacher for assistance if required.</p>";
                            }

                            $resource["description"] .= "<p class=\"muted resource-description\">" . html_encode($resource["link_notes"]) . "</p>";
                            $resource["type"] = "Online Learning Module";
                            $resource["type_id"] = $entity->getEntityType();
                            break;
                        case 8 :
                            $quiz = $resource_obj;
                            $total_questions = quiz_count_questions($quiz->getQuizID());
                            $resource_statistic = $resource_obj->getViewed();
                            $resource["title"] = "";
                            $resource["description"] = "";
                            $resource["attempts_history"] = "";

                            $attempts = 0;
                            $quiz_progress_records = Models_Quiz_Progress::fetchAllByAquizIDProxyID($entity->getEntityValue(), $ENTRADA_USER->getActiveID());

                            if ($quiz_progress_records) {
                                $attempts = count($quiz_progress_records);
                            }

                            $exceeded_attempts    = ((((int) $quiz->getQuizAttempts() === 0) || ($attempts < $quiz->getQuizAttempts())) ? false : true);

                            if (isset($quiz) && $quiz->getRequireAttendance() && !events_fetch_event_attendance_for_user($EVENT_ID,$ENTRADA_USER->getID())) {
                                $allow_attempt = false;
                            } elseif (((!(int) $quiz->getReleaseDate()) || ($quiz->getReleaseDate() <= time())) && ((!(int) $quiz->getReleaseUntil()) || ($quiz->getReleaseUntil() >= time())) && (!$exceeded_attempts)) {
                                $allow_attempt = true;
                            } else {
                                $allow_attempt = false;
                            }

                            if ($allow_attempt) {
                                $resource["title"] = "<a class=\"resource-link\" href=\"javascript: beginQuiz(".html_encode($resource["aquiz_id"]).")\" title=\"Take ".html_encode($resource["quiz_title"])."\">".html_encode($resource["quiz_title"])."</a>";
                            } else {
                                $resource["title"] = "<p class=\"resource-title\">". html_encode($resource["quiz_title"]) ."</p>";
                            }

                            if ($resource_statistic) {
                                $resource["title"] .= (((int) $resource_statistic->getTimestamp()) ? (((int) $resource_statistic->getTimestamp() >= (int) $resource["updated_date"]) ? "<span class=\"resource-viewed pull-right\" title=\"You have previously completed this quiz.\"><i class=\"icon-ok icon-white\"></i></span>" : "<span class=\"resource-updated pull-right\" title=\"This attached quiz has been updated since you last completed it.\"><i class=\"icon-exclamation-sign icon-white\"></i></span>") : "");
                            }

                            if ((int) $quiz->getReleaseDate() && (int) $quiz->getReleaseUntil()) {
                                $resource["description"] .= "<p class=\"muted resource-description\">This quiz " . ($quiz->getReleaseUntil() > time() ? "is" : "was only") .  " available from <strong>".date(DEFAULT_DATE_FORMAT, html_encode($quiz->getReleaseDate()))."</strong> to <strong>".date(DEFAULT_DATE_FORMAT, html_encode($quiz->getReleaseUntil()))."</strong>.</p>";
                            } elseif ((int) $quiz->getReleaseDate()) {
                                if ($quiz->getReleaseDate() > time()) {
                                    $resource["description"] .= "<p class=\"muted resource-description\">You will be able to attempt this quiz starting <strong>".date(DEFAULT_DATE_FORMAT, html_encode($quiz->getReleaseDate()))."</strong>.</p>";
                                } else {
                                    $resource["description"] .= "<p class=\"muted resource-description\">This quiz has been available since <strong>".date(DEFAULT_DATE_FORMAT, html_encode($quiz->getReleaseDate()))."</strong>.</p>";
                                }
                            } elseif ((int) $quiz->getReleaseUntil()) {
                                if ($quiz->getReleaseUntil() > time()) {
                                    $resource["description"] .= "<p class=\"muted resource-description\">You will be able to attempt this quiz until <strong>".date(DEFAULT_DATE_FORMAT, html_encode($quiz->getReleaseUntil()))."</strong>.</p>";
                                } else {
                                    $resource["description"] .= "<p class=\"muted resource-description\">This quiz was only available until <strong>".date(DEFAULT_DATE_FORMAT, html_encode($quiz->getReleaseUntil()))."</strong>. Please contact a teacher for assistance if required.</p>";
                                }
                            } else {
                                $resource["description"] .= "<p class=\"muted resource-description\">This quiz is available indefinitely.</p>";
                            }

                            switch ($quiz->getQuiztypeID()) {
                                case "1" :
                                    $quiz_type_code = "delayed";
                                    break;
                                case "2" :
                                    $quiz_type_code = "immediate";
                                    break;
                                case "3" :
                                    $quiz_type_code = "hide";
                                    break;
                            }

                            $resource["description"] .= "<p class=\"muted resource-description\">" . quiz_generate_description($quiz->getRequired(), $quiz_type_code, $quiz->getQuizTimeout(), $total_questions, $quiz->getQuizAttempts(), $quiz->getTimeframe(), $quiz->getRequireAttendance(), $event_info["course_id"]) . "</p>";
                            $resource["description"] .= "<p class=\"muted resource-description\">" . html_encode($resource["quiz_notes"]) . "</p>";

                            if ($quiz_progress_records) {
                                $resource["description"] .= "<br><p class=\"muted resource-description\"><strong>Your Attempts</strong></p>";
                                $resource["description"] .= "<ul class=\"menu\">";
                                foreach ($quiz_progress_records as $entry) {
                                    $quiz_start_time	= $entry->getUpdatedDate();
                                    $quiz_end_time		= (((int) $quiz->getQuizTimeout()) ? ($quiz_start_time + ($quiz->getQuizTimeout() * 60)) : 0);

                                    /**
                                     * Checking for quizzes that are expired, but still in progress.
                                     */
                                    if (($entry->getProgressValue() == "inprogress") && ((((int) $quiz->getReleaseUntil()) && ($quiz->getReleaseUntil() < time())) || (($quiz_end_time) && (time() > ($quiz_end_time + 30))))) {
                                        $quiz_progress_array	= array (
                                            "qprogress_id" => $entry->getQprogressID(),
                                            "aquiz_id" => $entry->getAquizID(),
                                            "content_type" => $entry->getContentType(),
                                            "content_id" => $entry->getContentID(),
                                            "quiz_id" => $entry->getQuizID(),
                                            "proxy_id" => $entry->getProxyID(),
                                            "progress_value" => "expired",
                                            "quiz_score" => "0",
                                            "quiz_value" => "0",
                                            "updated_date" => time(),
                                            "updated_by" => $ENTRADA_USER->getID()
                                        );

                                        $entry = new Models_Quiz_Progress($quiz_progress_array);

                                        if ($entry) {
                                            if (!$entry->update()) {
                                                application_log("error", "Unable to update the qprogress_id [".$entry->getQprogressID()."] to expired. Database said: ".$db->ErrorMsg());
                                            }
                                        }

                                        $entry->setProgressValue("expired");
                                    }

                                    switch ($entry->getProgressValue()) {
                                        case "complete" :
                                            if (($quiz_type_code == "delayed" && $quiz->getReleaseUntil() <= time()) || ($quiz_type_code == "immediate")) {
                                                $percentage = ((round(($entry->getQuizScore() / $entry->getQuizValue()), 2)) * 100);
                                                $resource["description"] .= "<li class=\"".(($percentage >= 60) ? "correct" : "incorrect")."\">";
                                                $resource["description"] .=	date(DEFAULT_DATE_FORMAT, $entry->getUpdatedDate())." <strong>Score:</strong> ".$entry->getQuizScore()."/".$entry->getQuizValue()." (".$percentage."%)";
                                                $resource["description"] .= "	( <a href=\"".ENTRADA_RELATIVE."/quizzes?section=results&amp;id=".html_encode($entry->getQprogressID())."\">review quiz</a> )";
                                                $resource["description"] .= "</li>";
                                            } elseif ($quiz_type_code == "hide") {
                                                $resource["description"] .= "<li>".date(DEFAULT_DATE_FORMAT, $entry->getUpdatedDate())." - <strong>Completed</strong></li>";
                                            } else {
                                                $resource["description"] .= "<li>".date(DEFAULT_DATE_FORMAT, $entry->getUpdatedDate())." <strong>Score:</strong> To Be Released ".date(DEFAULT_DATE_FORMAT, $quiz->getReleaseUntil())."</li>";
                                            }
                                            break;
                                        case "expired" :
                                            $resource["description"] .= "<li class=\"incorrect\">".date(DEFAULT_DATE_FORMAT, $entry->getUpdatedDate())." <strong>Expired Attempt</strong>: not completed.</li>";
                                            break;
                                        case "inprogress" :
                                            $resource["description"] .= "<li>".date(DEFAULT_DATE_FORMAT, $entry->getUpdatedDate())." <strong>Attempt In Progress</strong> ( <a href=\"".ENTRADA_RELATIVE."/quizzes?section=attempt&amp;id=".html_encode($quiz->getAquizID())."\">continue quiz</a> )</li>";
                                            break;
                                        default :
                                            continue;
                                            break;
                                    }
                                }
                                $resource["description"] .= "</ul>";
                            }

                            $resource["type"] = "Quiz";
                            $resource["type_id"] = $entity->getEntityType();
                            $resource["viewed"] = $quiz->getViewed();
                            break;
                        case 9 :
                            $resource["title"] = "<p class=\"resource-title\">Textbook Reading</p>";
                            $resource["description"] = "<p class=\"muted resource-description\">" . html_encode($resource["resource_textbook_reading"]) . "</p>";
                            $resource["type"] = "Textbook Reading";
                            $resource["type_id"] = $entity->getEntityType();
                            break;
                        case 10 :
                            $resource["title"] = "<p class=\"resource-title\">" . html_encode($resource["lti_title"]) . "</p>";
                            $resource["description"] = html_encode($resource["lti_notes"]);
                            $resource["type"] = "LTI Provider";
                            $resource["type_id"] = $entity->getEntityType();
                            break;
                        case 12 :
                            $EXAM_TEXT = $translate->_("exams");
                            $post_view                      = new Views_Exam_Post($resource_obj, $event);
                            $post_resources                 = $post_view->renderEventResource();
                            $resource["title"]              = $post_resources["title"];
                            $resource["description"]        = "";

                            if ($post_resources["description"] && $post_resources["description"] != "") {
                                $resource["description"]        = "<p class=\"muted resource-description\">" . $post_resources["description"] . "</p>";
                            }

                            $resource["description"]        .= "<p class=\"muted event-resource-release-dates\">" . $post_resources["available"] . "</p>";
                            $resource["type"]               = $EXAM_TEXT["title_singular"];
                            $resource["type_id"]            = $entity->getEntityType();
                            $resource["required"]           = $resource["mandatory"];
                            $resource["hidden"]             = $resource["hide_exam"];
                            $resource["attempts_allowed"]   = $post_resources["attempts_allowed"];
                            $resource["time_limit"]         = $post_resources["time_limit"];

                            break;
                    }

                    if ($resource) {
                        switch ($resource["timeframe"]) {
                            case "pre" :
                                $entity_timeframe_pre[] = $resource;
                                break;
                            case "during" :
                                $entity_timeframe_during[] = $resource;
                                break;
                            case "post" :
                                $entity_timeframe_post[] = $resource;
                                break;
                            case "none" :
                                $entity_timeframe_none[] = $resource;
                                break;
                        }
                    }
                }
            }
        } // end resource Obj
    }

    $display_pre    = false;
    $display_during = false;
    $display_post   = false;
    $display_none   = false;

    if ($entity_timeframe_pre && is_array($entity_timeframe_pre) && !empty($entity_timeframe_pre)) {
        $display_pre = true;
    }

    if ($entity_timeframe_during && is_array($entity_timeframe_during) && !empty($entity_timeframe_during)) {
        $display_during = true;
    }

    if ($entity_timeframe_post && is_array($entity_timeframe_post) && !empty($entity_timeframe_post)) {
        $display_post = true;
    }

    if ($entity_timeframe_none && is_array($entity_timeframe_none) && !empty($entity_timeframe_none)) {
        $display_none = true;
    }

    if ($display_pre || $display_during || $display_post || $display_none) {
        ?>
        <a name="event-resources-section"></a>
        <h2 title="Event Resources Section">Event Resources</h2>
        <div id="event-resources-section">
            <div id="event-resources-container">
                <?php
                if ($entity_timeframe_pre) { ?>
                    <div id="event-resource-timeframe-pre-container" class="resource-list">
                        <div class="resource-container-pre">
                            <p class="timeframe-heading">Before Class</p>
                            <ul class="timeframe-pre timeframe">
                                <?php
                                foreach ($entity_timeframe_pre as $entity) {
                                    ?>
                                    <li>
                                        <div>
                                            <?php echo $entity["title"]; ?>
                                            <?php echo $entity["description"]; ?>
                                        </div>
                                        <div>
                                            <?php
                                            if (isset($entity["hidden"]) && (int) $entity["hidden"]) {
                                                ?>
                                                <span class="label label-hidden event-resource-stat-label">Hidden</span>
                                                <?php
                                            }
                                            if (isset($entity["required"]) && (int) $entity["required"]) {
                                                ?>
                                                <span class="label label-important event-resource-stat-label">Required</span>
                                                <?php
                                            } else { ?>
                                                <span class="label label-default event-resource-stat-label">Optional</span>
                                                <?php
                                            }
                                            ?>
                                            <span class="label label-info event-resource-stat-label"><?php echo html_encode($entity["type"]); ?></span>
                                            <?php
                                            switch ($entity["type_id"]) {
                                                case 1 :
                                                case 5 :
                                                case 6 :
                                                case 11 :
                                                    if ((((!(int) $entity["release_date"]) || ($entity["release_date"] <= time())) && ((!(int) $entity["release_until"]) || ($entity["release_until"] >= time())))) {
                                                        echo "<span>";
                                                        echo "<a href=\"".ENTRADA_RELATIVE."/file-event.php?id=".html_encode($entity["efile_id"])."\" title=\"Click to download ".html_encode($title)."\"".(((int) $entity["access_method"]) ? " target=\"_blank\"" : "")."><span class=\"icon-download-alt\"></span></a>";
                                                        echo "</span>";
                                                    }
                                                    break;
                                                case 3 :
                                                    if ((((!(int) $entity["release_date"]) || ($entity["release_date"] <= time())) && ((!(int) $entity["release_until"]) || ($entity["release_until"] >= time())))) {
                                                        echo "<span>";
                                                        echo "<a href=\"".ENTRADA_RELATIVE."/link-event.php?id=".html_encode($entity["elink_id"])."\" title=\"Click to visit ".html_encode($entity["link"])."\"  target=\"_blank\"><span class=\"icon-globe\"></span></a>\n";
                                                        echo "</span>";
                                                    }
                                                    break;
                                                case 7 :
                                                    if ((((!(int) $entity["release_date"]) || ($entity["release_date"] <= time())) && ((!(int) $entity["release_until"]) || ($entity["release_until"] >= time())))) {
                                                        echo "<span>";
                                                        echo "<a href=\"".ENTRADA_RELATIVE."/link-event.php?id=".html_encode($entity["elink_id"])."\" title=\"Click to visit ".html_encode($entity["link"])."\"  target=\"_blank\"><span class=\"icon-globe\"></span></a>\n";
                                                        echo "</span>";
                                                    }
                                                    break;
                                                case 12 :

                                                    if ($entity["attempts_allowed"]) {
                                                        echo "<span class=\"label label-default event-resource-stat-label\">" . $EXAM_TEXT["posts"]["table_headers"]["attempts"] . ": " . $entity["attempts_allowed"] . "</span>";
                                                    }

                                                    if ($entity["time_limit"]) {
                                                        echo "<span class=\"label label-default event-resource-stat-label\">" .  $EXAM_TEXT["posts"]["table_headers"]["time_limit"] . $entity["time_limit"] . ": " . "</span>";
                                                    }
                                                    break;
                                            }
                                            ?>
                                        </div>
                                    </li>
                                    <?php
                                }
                                ?>
                            </ul>
                        </div>
                    </div>
                    <?php
                }

                if ($entity_timeframe_during) { ?>
                    <div id="event-resource-timeframe-during-container" class="resource-list">
                        <div class="resource-container-during">
                            <p class="timeframe-heading">During Class</p>
                            <ul class="timeframe-during timeframe">
                                <?php
                                foreach ($entity_timeframe_during as $entity) {
                                    ?>
                                    <li>
                                        <div>
                                            <?php echo $entity["title"]; ?>
                                            <?php echo  $entity["description"]; ?>
                                        </div>
                                        <div>
                                            <?php
                                            if (isset($entity["hidden"]) && (int) $entity["hidden"]) {
                                                ?>
                                                <span class="label label-hidden event-resource-stat-label">Hidden</span>
                                                <?php
                                            }
                                            if (isset($entity["required"]) && (int) $entity["required"]) {
                                                ?>
                                                <span class="label label-important event-resource-stat-label">Required</span>
                                                <?php
                                            } else {
                                                ?>
                                                <span class="label label-default event-resource-stat-label">Optional</span>
                                                <?php
                                            }
                                            ?>
                                            <span class="label label-info event-resource-stat-label"><?php echo html_encode($entity["type"]); ?></span>
                                            <?php
                                            switch ($entity["type_id"]) {
                                                case 1 :
                                                case 5 :
                                                case 6 :
                                                case 11 :
                                                    if ((((!(int) $entity["release_date"]) || ($entity["release_date"] <= time())) && ((!(int) $entity["release_until"]) || ($entity["release_until"] >= time())))) {
                                                        echo "<span>";
                                                        echo "<a href=\"".ENTRADA_RELATIVE."/file-event.php?id=".html_encode($entity["efile_id"])."\" title=\"Click to download ".html_encode($title)."\"".(((int) $entity["access_method"]) ? " target=\"_blank\"" : "")."><span class=\"icon-download-alt\"></span></a>";
                                                        echo "</span>";
                                                    }
                                                    break;
                                                case 3 :
                                                    if ((((!(int) $entity["release_date"]) || ($entity["release_date"] <= time())) && ((!(int) $entity["release_until"]) || ($entity["release_until"] >= time())))) {
                                                        echo "<span>";
                                                        echo "<a href=\"".ENTRADA_RELATIVE."/link-event.php?id=".html_encode($entity["elink_id"])."\" title=\"Click to visit ".html_encode($entity["link"])."\"  target=\"_blank\"><span class=\"icon-globe\"></span></a>\n";
                                                        echo "</span>";
                                                    }
                                                    break;
                                                case 7 :
                                                    if ((((!(int) $entity["release_date"]) || ($entity["release_date"] <= time())) && ((!(int) $entity["release_until"]) || ($entity["release_until"] >= time())))) {
                                                        echo "<span>";
                                                        echo "<a href=\"".ENTRADA_RELATIVE."/link-event.php?id=".html_encode($entity["elink_id"])."\" title=\"Click to visit ".html_encode($entity["link"])."\"  target=\"_blank\"><span class=\"icon-globe\"></span></a>\n";
                                                        echo "</span>";
                                                    }
                                                    break;
                                                case 12 :

                                                    if ($entity["attempts_allowed"]) {
                                                        echo "<span class=\"label label-default event-resource-stat-label\">" . $EXAM_TEXT["posts"]["table_headers"]["attempts"] . ": " . $entity["attempts_allowed"] . "</span>";
                                                    }

                                                    if ($entity["time_limit"]) {
                                                        echo "<span class=\"label label-default event-resource-stat-label\">" .  $EXAM_TEXT["posts"]["table_headers"]["time_limit"] . $entity["time_limit"] . ": " . "</span>";
                                                    }
                                                    break;
                                            }
                                            ?>
                                        </div>
                                    </li>
                                    <?php
                                }
                                ?>
                            </ul>
                        </div>
                    </div>
                    <?php
                }

                if ($entity_timeframe_post) { ?>
                    <div id="event-resource-timeframe-post-container" class="resource-list">
                        <div class="resource-container-post">
                            <p class="timeframe-heading">After Class</p>
                            <ul class="timeframe-post timeframe">
                                <?php
                                foreach ($entity_timeframe_post as $entity) {
                                ?>
                                <li>
                                    <div>
                                        <?php echo $entity["title"]; ?>
                                        <?php echo $entity["description"]; ?>
                                    </div>
                                    <div>
                                        <?php
                                        if (isset($entity["hidden"]) && (int) $entity["hidden"]) {
                                            ?>
                                            <span class="label label-hidden event-resource-stat-label">Hidden</span>
                                            <?php
                                        }
                                        if (isset($entity["required"]) && (int) $entity["required"]) {
                                            ?>
                                            <span class="label label-important event-resource-stat-label">Required</span>
                                            <?php
                                        } else { ?>
                                            <span class="label label-default event-resource-stat-label">Optional</span>
                                            <?php
                                        }
                                        ?>
                                        <span class="label label-info event-resource-stat-label"><?php echo html_encode($entity["type"]); ?></span>
                                        <?php
                                        switch ($entity["type_id"]) {
                                            case 1 :
                                            case 5 :
                                            case 6 :
                                            case 11 :
                                                if ((((!(int) $entity["release_date"]) || ($entity["release_date"] <= time())) && ((!(int) $entity["release_until"]) || ($entity["release_until"] >= time())))) {
                                                    echo "<span>";
                                                    echo "<a href=\"".ENTRADA_RELATIVE."/file-event.php?id=".html_encode($entity["efile_id"])."\" title=\"Click to download ".html_encode($title)."\"".(((int) $entity["access_method"]) ? " target=\"_blank\"" : "")."><span class=\"icon-download-alt\"></span></a>";
                                                    echo "</span>";
                                                }
                                                break;
                                            case 3 :
                                                if ((((!(int) $entity["release_date"]) || ($entity["release_date"] <= time())) && ((!(int) $entity["release_until"]) || ($entity["release_until"] >= time())))) {
                                                    echo "<span>";
                                                    echo "<a href=\"".ENTRADA_RELATIVE."/link-event.php?id=".html_encode($entity["elink_id"])."\" title=\"Click to visit ".html_encode($entity["link"])."\"  target=\"_blank\"><span class=\"icon-globe\"></span></a>\n";
                                                    echo "</span>";
                                                }
                                                break;
                                            case 7 :
                                                if ((((!(int) $entity["release_date"]) || ($entity["release_date"] <= time())) && ((!(int) $entity["release_until"]) || ($entity["release_until"] >= time())))) {
                                                    echo "<span>";
                                                    echo "<a href=\"".ENTRADA_RELATIVE."/link-event.php?id=".html_encode($entity["elink_id"])."\" title=\"Click to visit ".html_encode($entity["link"])."\"  target=\"_blank\"><span class=\"icon-globe\"></span></a>\n";
                                                    echo "</span>";
                                                }
                                                break;
                                            case 12 :

                                                if ($entity["attempts_allowed"]) {
                                                    echo "<span class=\"label label-default event-resource-stat-label\">" . $EXAM_TEXT["posts"]["table_headers"]["attempts"] . ": " . $entity["attempts_allowed"] . "</span>";
                                                }

                                                if ($entity["time_limit"]) {
                                                    echo "<span class=\"label label-default event-resource-stat-label\">" .  $EXAM_TEXT["posts"]["table_headers"]["time_limit"] . $entity["time_limit"] . ": " . "</span>";
                                                }
                                                break;
                                        }
                                        ?>
                                    </div>
                                    <?php
                                    }
                                    ?>
                            </ul>
                        </div>
                    </div>
                    <?php
                }

                if ($entity_timeframe_none) { ?>
                    <div id="event-resource-timeframe-none-container" class="resource-list">
                        <div class="resource-container-none">
                            <p class="timeframe-heading">No Timeframe</p>
                            <ul class="timeframe-none timeframe">
                                <?php
                                foreach ($entity_timeframe_none as $entity) {
                                    ?>
                                    <li>
                                        <div>
                                            <?php echo $entity["title"]; ?>
                                            <?php echo $entity["description"]; ?>
                                        </div>
                                        <div>
                                            <?php
                                            if (isset($entity["hidden"]) && (int) $entity["hidden"]) {
                                                ?>
                                                <span class="label label-hidden event-resource-stat-label">Hidden</span>
                                                <?php
                                            }
                                            if (isset($entity["required"]) && (int) $entity["required"]) {
                                                ?>
                                                <span class="label label-important event-resource-stat-label">Required</span>
                                                <?php
                                            } else {
                                                ?>
                                                <span class="label label-default event-resource-stat-label">Optional</span>
                                                <?php
                                            }
                                            ?>
                                            <span class="label label-info event-resource-stat-label"><?php echo html_encode($entity["type"]); ?></span>
                                            <?php
                                            switch ($entity["type_id"]) {
                                                case 1 :
                                                case 5 :
                                                case 6 :
                                                case 11 :
                                                    if ((((!(int) $entity["release_date"]) || ($entity["release_date"] <= time())) && ((!(int) $entity["release_until"]) || ($entity["release_until"] >= time())))) {
                                                        echo "<span>";
                                                        echo "<a href=\"".ENTRADA_RELATIVE."/file-event.php?id=".html_encode($entity["efile_id"])."\" title=\"Click to download ".html_encode($title)."\"".(((int) $entity["access_method"]) ? " target=\"_blank\"" : "")."><span class=\"icon-download-alt\"></span></a>";
                                                        echo "</span>";
                                                    }
                                                    break;
                                                case 3 :
                                                    if ((((!(int) $entity["release_date"]) || ($entity["release_date"] <= time())) && ((!(int) $entity["release_until"]) || ($entity["release_until"] >= time())))) {
                                                        echo "<span>";
                                                        echo "<a href=\"".ENTRADA_RELATIVE."/link-event.php?id=".html_encode($entity["elink_id"])."\" title=\"Click to visit ".html_encode($entity["link"])."\"  target=\"_blank\"><span class=\"icon-globe\"></span></a>\n";
                                                        echo "</span>";
                                                    }
                                                    break;
                                                case 7 :
                                                    if ((((!(int) $entity["release_date"]) || ($entity["release_date"] <= time())) && ((!(int) $entity["release_until"]) || ($entity["release_until"] >= time())))) {
                                                        echo "<span>";
                                                        echo "<a href=\"".ENTRADA_RELATIVE."/link-event.php?id=".html_encode($entity["elink_id"])."\" title=\"Click to visit ".html_encode($entity["link"])."\"  target=\"_blank\"><span class=\"icon-globe\"></span></a>\n";
                                                        echo "</span>";
                                                    }
                                                    break;
                                                case 12 :

                                                    if ($entity["attempts_allowed"]) {
                                                        echo "<span class=\"label label-default event-resource-stat-label\">" . $EXAM_TEXT["posts"]["table_headers"]["attempts"] . ": " . $entity["attempts_allowed"] . "</span>";
                                                    }

                                                    if ($entity["time_limit"]) {
                                                        echo "<span class=\"label label-default event-resource-stat-label\">" .  $EXAM_TEXT["posts"]["table_headers"]["time_limit"] . $entity["time_limit"] . ": " . "</span>";
                                                    }
                                                    break;
                                            }
                                            ?>
                                        </div>
                                    </li>
                                    <?php
                                }
                                ?>
                            </ul>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
        <?php
    } // end event resources display section

} // end event resources section
?>