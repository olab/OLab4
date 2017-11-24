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
 * This API file returns an HTML table of the possible audience information
 * based on the selected course.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
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
	if (isset($_POST["ajax"]) && ($_POST["ajax"] == 1)) {
		$use_ajax = true;
	} else {
		$use_ajax = false;
	}

	if ($use_ajax) {
		/**
		 * Clears all open buffers so we can return a plain response for the Javascript.
		 */
		ob_clear_open_buffers();

		$PROCESSED = array();
		$PROCESSED["course_id"] = 0;
		$EVENT_ID = 0;

		if (isset($_POST["course_id"]) && ($tmp_input = clean_input($_POST["course_id"], "int"))) {
			$PROCESSED["course_id"] = $tmp_input;
		}

		if (isset($_POST["event_id"]) && ($tmp_input = clean_input($_POST["event_id"], "int"))) {
			$EVENT_ID = $tmp_input;
		}
	}

	if ($PROCESSED["course_id"]) {
        /* Creates arrays used for model loading of audience */
        if (!$cohort_times_o ? $cohort_times_o = array() : "" );
        if (!$cohort_times_a ? $cohort_times_a = array() : "" );
        if (!$proxy_times_o  ? $proxy_times_o  = array() : "" );
        if (!$proxy_times_a  ? $proxy_times_a  = array() : "" );
        if (!$cgroup_times_o ? $cgroup_times_o = array() : "" );
        if (!$cgroup_times_a ? $cgroup_times_a = array() : "" );

		$query = "SELECT * FROM `courses` WHERE `course_id` = ".$db->qstr($PROCESSED["course_id"]);
		$course_info = $db->GetRow($query);
		if ($course_info) {
			$permission = $course_info["permission"];

			$query = "SELECT a.* FROM `course_audience` AS a JOIN `courses` AS b ON a.`course_id` = b.`course_id` AND a.`course_id` = ".$db->qstr($PROCESSED["course_id"])." WHERE a.`audience_active` = '1'";
			$course_list = $db->GetRow($query);

			$query = "SELECT * FROM `course_groups` WHERE `course_id` = ".$db->qstr($PROCESSED["course_id"]).($use_ajax ? " AND `active` = '1'" : "")." ORDER BY LENGTH(`group_name`), `group_name` ASC";
			$course_groups = $db->GetAll($query);

			$query = "SELECT * FROM `event_audience` WHERE `event_id` = ".$db->qstr($EVENT_ID)." AND `audience_type` != 'course_id'";
			$custom_audience = $db->GetAll($query);

			$ONLOAD[] = "selectEventAudienceOption('" . (isset($PROCESSED["event_audience_type"]) && $PROCESSED["event_audience_type"] && ($course_list || $permission == "closed") ? $PROCESSED["event_audience_type"] : "custom") . "')";
            ?>
            <div class="control-group">
                <label for="learner_name" class="control-label form-nrequired">Associated Learners:</label>
                <div class="controls">
					<table>
						<tbody>
							<?php
							if ($course_list || $permission == "closed") {
								?>
								<tr>
									<td style="vertical-align: top">
                                        <input type="radio" name="event_audience_type" id="event_audience_type_course" value="course" onclick="selectEventAudienceOption('course')" style="vertical-align: middle"<?php echo ((($PROCESSED["event_audience_type"] == "course") || !isset($PROCESSED["event_audience_type"])) ? " checked=\"checked\"" : ""); ?> />
                                    </td>
									<td colspan="2" style="padding-bottom: 15px">
										<label for="event_audience_type_course" class="radio-group-title">All Learners Enroled in <?php echo html_encode($course_info["course_code"]); ?></label>
										<div class="content-small">This event is intended for all learners enrolled in this <?php echo strtolower($translate->_("course")); ?>.</div>
									</td>
								</tr>
								<?php
							}
                            ?>
                            <tr>
                                <td style="vertical-align: top">
                                    <input type="radio" name="event_audience_type" id="event_audience_type_custom" value="custom" onclick="selectEventAudienceOption('custom')" style="vertical-align: middle"<?php echo (((isset($PROCESSED["event_audience_type"]) && $PROCESSED["event_audience_type"] == "custom") || (!$course_list && $permission != "closed")) ? " checked=\"checked\"" : ""); ?> />
                                </td>
                                <td colspan="2">
                                    <label for="event_audience_type_custom" class="radio-group-title">A Custom Event Audience</label>
                                    <div class="content-small">This event is intended for a custom selection of learners.</div>

                                    <div id="event_audience_type_custom_options" style="<?php echo ($course_list && !$custom_audience ? "display: none; " : ""); ?>position: relative; margin-top: 10px;">
                                        <select id="audience_type" onchange="showMultiSelect();">
                                            <option value="">-- Select an audience type --</option>
                                            <option value="cohorts">Cohorts/Course Lists of learners</option>
                                            <?php
                                            if ($course_groups) {
                                                ?>
                                                <option value="course_groups">Course specific small groups</option>
                                                <?php
                                            }
                                            ?>
                                            <option value="students">Individual learners</option>
                                        </select>

                                        <span id="options_loading" style="display:none; vertical-align: middle">
                                            <img src="<?php echo ENTRADA_RELATIVE; ?>/images/indicator.gif" width="16" height="16" alt="Please Wait" title="" style="vertical-align: middle" />
                                            Loading ...
                                        </span>
                                        <span id="options_container"></span>
                                        <?php
                                        if ($use_ajax) {
                                            /**
                                             * Compiles the list of groups from groups table (known as Cohorts).
                                             */
                                            $COHORT_LIST = array();
                                            $query = "	SELECT a.*
                                                        FROM `groups` AS a
                                                        JOIN `group_organisations` AS b
                                                        ON a.`group_id` = b.`group_id`
                                                        WHERE a.`group_active` = '1'
                                                        AND a.`group_type` = 'cohort'
                                                        AND b.`organisation_id` = '".$course_info["organisation_id"]."'
                                                        ORDER BY LENGTH(a.`group_name`), a.`group_name` ASC";
                                            $results = $db->GetAll($query);
                                            if ($results) {
                                                foreach($results as $result) {
                                                    $COHORT_LIST[$result["group_id"]] = $result;
                                                }
                                            }

                                            /**
                                             * Compiles the list of course small groups.
                                             */
                                            $GROUP_LIST = array();
                                            $query = "	SELECT *
                                                        FROM `course_groups`
                                                        WHERE `course_id` = ".$db->qstr($PROCESSED["course_id"])."
                                                        AND `active` = '1'
                                                        ORDER BY LENGTH(`group_name`), `group_name` ASC";
                                            $results = $db->GetAll($query);
                                            if ($results) {
                                                foreach($results as $result) {
                                                    $GROUP_LIST[$result["cgroup_id"]] = $result;
                                                }
                                            }

                                            /**
                                             * Compiles the list of students.
                                             */
                                            $STUDENT_LIST = array();
                                            $query = "	SELECT a.`id` AS `proxy_id`, b.`role`, CONCAT_WS(' ', a.`firstname`, a.`lastname`) AS `fullname`, a.`organisation_id`
                                                        FROM `".AUTH_DATABASE."`.`user_data` AS a
                                                        LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
                                                        ON a.`id` = b.`user_id`
                                                        WHERE b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
                                                        AND b.`account_active` = 'true'
                                                        AND (b.`access_starts` = '0' OR b.`access_starts` <= ".$db->qstr(time()).")
                                                        AND (b.`access_expires` = '0' OR b.`access_expires` > ".$db->qstr(time()).")
                                                        AND b.`group` = 'student'
                                                        AND a.`grad_year` >= '".(date("Y") - ((date("m") < 7) ?  2 : 1))."'
                                                        ORDER BY a.`grad_year` ASC, a.`lastname` ASC, a.`firstname` ASC";
                                            $results = $db->GetAll($query);
                                            if ($results) {
                                                foreach($results as $result) {
                                                    $STUDENT_LIST[$result["proxy_id"]] = array("proxy_id" => $result["proxy_id"], "fullname" => $result["fullname"], "organisation_id" => $result["organisation_id"]);
                                                }
                                            }
                                        }

                                        /**
                                         * Process cohorts.
                                         */
                                        if (isset($_POST["event_audience_cohorts"]) && $use_ajax) {
                                            $associated_audience = explode(",", $_POST["event_audience_cohorts"]);
                                            if (isset($associated_audience) && is_array($associated_audience) && count($associated_audience)) {
                                                foreach($associated_audience as $audience_id) {
                                                    if (strpos($audience_id, "group") !== false) {
                                                        if ($group_id = clean_input(preg_replace("/[a-z_]/", "", $audience_id), array("trim", "int"))) {
                                                            $query = "	SELECT a.*
                                                                        FROM `groups` AS a
                                                                        JOIN `group_organisations` AS b
                                                                        ON a.`group_id` = b.`group_id`
                                                                        WHERE a.`group_id` = ".$db->qstr($group_id)."
                                                                        AND a.`group_type` = 'cohort'
                                                                        AND a.`group_active` = 1
                                                                        AND b.`organisation_id` = '".$course_info["organisation_id"]."'";
                                                            $result	= $db->GetRow($query);
                                                            if ($result) {
                                                                $audience_type  = "cohort";
                                                                $audience_value = $group_id;
                                                                $PROCESSED["associated_cohort_ids"][] = $audience_value;
                                                                $audience = Models_Event_Audience::fetchRowByEventIdTypeValue($EVENT_ID, $audience_type, $audience_value);
                                                                if (!$audience) {
                                                                    $audience = new Models_Event_Audience(array(
                                                                        "event_id"          => $EVENT_ID,
                                                                        "audience_type"     => "cohort",
                                                                        "audience_value"    => $group_id,
                                                                        "custom_time"       => 0,
                                                                        "custom_time_start" => 0,
                                                                        "custom_time_end"   => 0,
                                                                        "updated_date"      => time(),
                                                                        "updated_by"        => $ENTRADA_USER->getID()
                                                                    ));
                                                                }

                                                                if (isset($cohort_times_a) && is_array($cohort_times_a) && !array_key_exists($audience_value, $cohort_times_a)) {
                                                                    $audience_array = $audience->toArray();
                                                                    $audience_array = Entrada_Utilities::buildAudienceArray($audience_array);
                                                                    $cohort_times_o[$audience_value] = $audience;
                                                                    $cohort_times_a[$audience_value] = $audience_array;
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }

                                        /**
                                         * Process course groups.
                                         */
                                        if (isset($_POST["event_audience_course_groups"]) && $use_ajax) {
                                            $associated_audience = explode(",", $_POST["event_audience_course_groups"]);
                                            if (isset($associated_audience) && is_array($associated_audience) && count($associated_audience)) {
                                                foreach($associated_audience as $audience_id) {
                                                    if (strpos($audience_id, "cgroup") !== false) {
                                                        if ($cgroup_id = clean_input(preg_replace("/[a-z_]/", "", $audience_id), array("trim", "int"))) {
                                                            $query = "	SELECT *
                                                                        FROM `course_groups`
                                                                        WHERE `cgroup_id` = ".$db->qstr($cgroup_id)."
                                                                        AND `course_id` = ".$db->qstr($PROCESSED["course_id"])."
                                                                        AND `active` = 1";
                                                            $result	= $db->GetRow($query);
                                                            if ($result) {
                                                                $audience_type  = "group_id";
                                                                $audience_value = $cgroup_id;
                                                                $PROCESSED["associated_cgroup_ids"][] = $audience_value;
                                                                $audience = Models_Event_Audience::fetchRowByEventIdTypeValue($EVENT_ID, $audience_type, $audience_value);
                                                                if (!$audience) {
                                                                    $audience = new Models_Event_Audience(array(
                                                                        "event_id"          => $EVENT_ID,
                                                                        "audience_type"     => "group_id",
                                                                        "audience_value"    => $cgroup_id,
                                                                        "custom_time"       => 0,
                                                                        "custom_time_start" => 0,
                                                                        "custom_time_end"   => 0,
                                                                        "updated_date"      => time(),
                                                                        "updated_by"        => $ENTRADA_USER->getID()
                                                                    ));
                                                                }
                                                                if (isset($cgroup_times_a) && is_array($cgroup_times_a) && !array_key_exists($audience_value, $cgroup_times_a)) {
                                                                    $audience_array = $audience->toArray();
                                                                    $audience_array = Entrada_Utilities::buildAudienceArray($audience_array);
                                                                    $cgroup_times_o[$audience_value] = $audience;
                                                                    $cgroup_times_a[$audience_value] = $audience_array;
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }

                                        /**
                                         * Process students.
                                         */
                                        if (isset($_POST["event_audience_students"]) && $use_ajax) {
                                            $associated_audience = explode(",", $_POST["event_audience_students"]);
                                            if (isset($associated_audience) && is_array($associated_audience) && count($associated_audience)) {
                                                foreach($associated_audience as $audience_id) {
                                                    if (strpos($audience_id, "student") !== false) {
                                                        if ($proxy_id = clean_input(preg_replace("/[a-z_]/", "", $audience_id), array("trim", "int"))) {
                                                            $query = "	SELECT a.*
                                                                        FROM `".AUTH_DATABASE."`.`user_data` AS a
                                                                        LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
                                                                        ON a.`id` = b.`user_id`
                                                                        WHERE a.`id` = ".$db->qstr($proxy_id)."
                                                                        AND b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
                                                                        AND b.`account_active` = 'true'
                                                                        AND (b.`access_starts` = '0' OR b.`access_starts` <= ".$db->qstr(time()).")
                                                                        AND (b.`access_expires` = '0' OR b.`access_expires` > ".$db->qstr(time()).")";
                                                            $result	= $db->GetRow($query);
                                                            if ($result) {
                                                                $audience_type  = "proxy_id";
                                                                $audience_value = $proxy_id;
                                                                if (isset($proxy_times) && is_array($proxy_times) && !empty($proxy_times)) {
                                                                    $custom_time        = $proxy_times[$proxy_id]["custom_time"];
                                                                    $custom_time_start  = $proxy_times[$proxy_id]["custom_time_start"];
                                                                    $custom_time_end    = $proxy_times[$proxy_id]["custom_time_end"];
                                                                }
                                                                $PROCESSED["associated_proxy_ids"][] = $audience_value;
                                                                $audience = Models_Event_Audience::fetchRowByEventIdTypeValue($EVENT_ID, $audience_type, $audience_value);
                                                                if (!$audience) {
                                                                    $audience = new Models_Event_Audience(array(
                                                                        "event_id"          => $EVENT_ID,
                                                                        "audience_type"     => "proxy_id",
                                                                        "audience_value"    => $proxy_id,
                                                                        "custom_time"       => 0,
                                                                        "custom_time_start" => 0,
                                                                        "custom_time_end"   => 0,
                                                                        "updated_date"      => time(),
                                                                        "updated_by"        => $ENTRADA_USER->getID()
                                                                    ));
                                                                }
                                                                if (isset($proxy_times_a) && is_array($proxy_times_a) && !array_key_exists($audience_value, $proxy_times_a)) {
                                                                    $audience_array = $audience->toArray();
                                                                    $audience_array = Entrada_Utilities::buildAudienceArray($audience_array);
                                                                    $proxy_times_o[$audience_value] = $audience;
                                                                    $proxy_times_a[$audience_value] = $audience_array;
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }

                                        if (!isset($PROCESSED["associated_cohort_ids"]) && !isset($PROCESSED["associated_cgroup_ids"]) && !isset($PROCESSED["associated_proxy_ids"]) && !isset($_POST["event_audience_cohorts"]) && !isset($_POST["event_audience_course_groups"]) && !isset($_POST["event_audience_students"]) && isset($EVENT_ID)) {
                                            if ($is_draft) {
												$audiences = Models_Event_Draft_Event_Audience::fetchAllByDraftEventID($PROCESSED["devent_id"]);
											} else {
												$audiences = Models_Event_Audience::fetchAllByEventID($EVENT_ID);
											}
                                            if (isset($audiences) && is_array($audiences) && !empty($audiences)) {
                                                $PROCESSED["event_audience_type"] = "custom";

                                                foreach($audiences as $audience) {
                                                    if (isset($audience) && is_object($audience)) {
                                                        $audience_type  = $audience->getAudienceType();
                                                        $audience_value = $audience->getAudienceValue();
                                                        switch($audience_type) {
                                                            case "course_id" :
                                                                $PROCESSED["event_audience_type"]       = "course";
                                                                $PROCESSED["associated_course_ids"]     = (int) $audience_value;
                                                                break;
                                                            case "cohort" :
                                                                $PROCESSED["associated_cohort_ids"][]   = (int) $audience_value;
                                                                if (!array_key_exists($audience_value, $cohort_times_a)) {
                                                                    $audience_array = $audience->toArray();
                                                                    $audience_array = Entrada_Utilities::buildAudienceArray($audience_array);
                                                                    $cohort_times_o[$audience_value] = $audience;
                                                                    $cohort_times_a[$audience_value] = $audience_array;
                                                                }
                                                                break;
                                                            case "group_id" :
                                                                $PROCESSED["associated_cgroup_ids"][]   = (int) $audience_value;
                                                                if (!array_key_exists($audience_value, $cgroup_times_a)) {
                                                                    $audience_array = $audience->toArray();
																	$audience_array = Entrada_Utilities::buildAudienceArray($audience_array);
                                                                    $cgroup_times_o[$audience_value] = $audience;
                                                                    $cgroup_times_a[$audience_value] = $audience_array;
                                                                }
                                                                break;
                                                            case "proxy_id" :
                                                                $PROCESSED["associated_proxy_ids"][]    = (int) $audience_value;
                                                                if (!array_key_exists($audience_value, $proxy_times_a)) {
                                                                    $audience_array = $audience->toArray();
                                                                    $audience_array = Entrada_Utilities::buildAudienceArray($audience_array);
                                                                    $proxy_times_o[$audience_value] = $audience;
                                                                    $proxy_times_a[$audience_value] = $audience_array;
                                                                }
                                                                break;
                                                        }
                                                    }
                                                }
                                            }
                                        }

                                        $cohort_ids_string  = "";
                                        $cgroup_ids_string  = "";
                                        $student_ids_string = "";

                                        if (isset($PROCESSED["associated_course_ids"]) && $PROCESSED["associated_course_ids"]) {
                                            $course_audience_included = true;
                                        } else {
                                            $course_audience_included = false;
                                        }

                                        if (isset($cohort_times_o) && is_array($cohort_times_o)) {
                                            foreach ($cohort_times_o as $cohort_id => $cohort) {
                                                if ($cohort_ids_string) {
                                                    $cohort_ids_string .= ",cohort_" . $cohort_id;
                                                } else {
                                                    $cohort_ids_string = "cohort_" . $cohort_id;
                                                }
                                            }
                                            $cohorts_custom_time_string = serialize($cohort_times_a);
                                        }

                                        if (isset($cgroup_times_o) && is_array($cgroup_times_o)) {
                                            foreach ($cgroup_times_o as $cgroup_id => $cgroup) {
                                                if ($cgroup_ids_string) {
                                                    $cgroup_ids_string .= ",cgroup_" . $cgroup_id;
                                                } else {
                                                    $cgroup_ids_string = "cgroup_" . $cgroup_id;
                                                }
                                            }
                                            $course_groups_custom_time_string = serialize($cgroup_times_a);
                                        }

                                        if (isset($proxy_times_o) && is_array($proxy_times_o)) {
                                            foreach ($proxy_times_o as $proxy_id => $proxy) {
                                                if ($student_ids_string) {
                                                    $student_ids_string .= ",student_" . $proxy_id;
                                                } else {
                                                    $student_ids_string = "student_" . $proxy_id;
                                                }
                                            }
                                            $proxy_custom_time_string = serialize($proxy_times_a);
                                        }
                                        ?>
                                        <input type="hidden" id="event_audience_cohorts" name="event_audience_cohorts" value="<?php echo $cohort_ids_string; ?>" />
                                        <input type="hidden" id="event_audience_course_groups" name="event_audience_course_groups" value="<?php echo $cgroup_ids_string; ?>" />
                                        <input type="hidden" id="event_audience_students" name="event_audience_students" value="<?php echo $student_ids_string; ?>" />
                                        <input type="hidden" id="event_audience_course" name="event_audience_course" value="<?php echo $course_audience_included ? "1" : "0"; ?>" />

                                        <input type="hidden" id="event_audience_students_custom_times" name="event_audience_students_custom_times" value=<?php echo $proxy_custom_time_string; ?> />
                                        <input type="hidden" id="event_audience_cohorts_custom_times" name="event_audience_cohorts_custom_times" value=<?php echo $cohorts_custom_time_string; ?> />
                                        <input type="hidden" id="event_audience_course_groups_custom_times" name="event_audience_course_groups_custom_times" value=<?php echo $course_groups_custom_time_string; ?> />

                                        <ul class="menu multiselect" id="audience_list" style="margin-top: 5px">
                                        <?php
                                            if (isset($cohort_times_o) && is_array($cohort_times_o) && !empty($cohort_times_o)) {
                                                foreach ($cohort_times_o as $obj) {
                                                    if (isset($obj) && is_object($obj)) {
                                                    	if (!$is_draft) {
                                                        	$cohort_view = new Views_Event_Audience($obj);
														} else {
															$cohort_view = new Views_Event_Draft_Audience($obj);
														}
														if (isset($cohort_view) && is_object($cohort_view)) {
                                                            echo $cohort_view->renderLI();
                                                        }
                                                    }
                                                }
                                            }

                                            if (isset($cgroup_times_o) && is_array($cgroup_times_o) && !empty($cgroup_times_o)) {
                                                foreach ($cgroup_times_o as $obj) {
                                                    if (isset($obj) && is_object($obj)) {
														if (!$is_draft) {
															$cgroup_view = new Views_Event_Audience($obj);
														} else {
															$cgroup_view = new Views_Event_Draft_Audience($obj);
														}

                                                        if (isset($cgroup_view) && is_object($cgroup_view)) {
                                                            echo $cgroup_view->renderLI();
                                                        }
                                                    }
                                                }
                                            }

                                            if (isset($proxy_times_o) && is_array($proxy_times_o) && !empty($proxy_times_o)) {
                                                foreach ($proxy_times_o as $obj) {
                                                    if (isset($obj) && is_object($obj)) {
														if (!$is_draft) {
															$proxy_view = new Views_Event_Audience($obj);
														} else {
															$proxy_view = new Views_Event_Draft_Audience($obj);
														}
                                                        if (isset($proxy_view) && is_object($proxy_view)) {
                                                            echo $proxy_view->renderLI();
                                                        }
                                                    }
                                                }
                                            }
                                        ?>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
						</tbody>
					</table>
                </div>
			</div>
			<?php
		}
	}

	/**
	 * If we are return this via Javascript,
	 * exit now so we don't get the entire page.
	 */
	if ($use_ajax) {
		exit;
	}
}