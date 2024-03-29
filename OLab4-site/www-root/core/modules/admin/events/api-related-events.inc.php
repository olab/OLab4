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
 * This API file returns a selectbox containing all the events under the
 * given course id, discluding those which are listed below as having
 * their parent_id set to the current event.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2011 Queen's University. All Rights Reserved.
 *
 */

if (!defined("IN_EVENTS")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("event", "update", false)) {
	add_error("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$GROUP."] and role [".$ROLE."] do not have access to this module [".$MODULE."].");
} else {
	if (isset($_POST["ajax"]) && ($_POST["ajax"] == 1)) {
		$use_ajax = true;
	} else {
		$use_ajax = false;
	}

	$related_event_error = false;
	if ($use_ajax) {
		/**
		 * Clears all open buffers so we can return a plain response for the Javascript.
		 */
		ob_clear_open_buffers();

		$PROCESSED = array();
		$PROCESSED["course_id"] = 0;

		if (isset($_POST["event_id"]) && ($tmp_input = clean_input($_POST["event_id"], "int"))) {
			$PROCESSED["event_id"] = $tmp_input;
		}
		if (isset($_POST["course_id"]) && ($tmp_input = clean_input($_POST["course_id"], "int"))) {
			$PROCESSED["course_id"] = $tmp_input;
		} else {
			$related_event_error = true;
			$related_event_error_message = "There is currently no <strong>Course</strong> associated with this event. Please select one now to view a list of events which may be related to this one.";
		}

        if (isset($_POST["draft_id"]) && ($tmp_input = clean_input($_POST["draft_id"], ["trim", "int"]))) {
            $PROCESSED["devent_id"] = $tmp_input;
        }

        if (isset($PROCESSED["devent_id"]) && is_int($PROCESSED["devent_id"])) {
            $is_draft                = true;
            $tables["events"]        = "draft_events";
            $tables["primary_key"]   = "devent_id";
            $tables["parent"]        = "draft_parent_id";

            $event_model = new Models_Event_Draft_Event();
        } else {
            $is_draft                = false;
            $tables["events"]        = "events";
            $tables["primary_key"]   = "event_id";
            $tables["parent"]        = "parent_id";

            $event_model = new Models_Event();
        }

		if (isset($_POST["add_id"]) && ($tmp_input = $_POST["add_id"])) {
            if ($tmp_input != $PROCESSED[$tables["primary_key"]]) {
                $PROCESSED["add_id"] = $tmp_input;
                if (!$event_exists = $event_model->fetchRowByIDCourseId($PROCESSED["add_id"],$PROCESSED["course_id"])->toArray()) {
                    $related_event_error = true;
                    $related_event_error_message = "The event ID which you supplied was not related to the same course as this event. Please try again with an event ID which exists within the course.";
                }
            } else {
                $related_event_error = true;
                $related_event_error_message = "You cannot add this event id as a child event, it would create a paradox.";
            }
		}
		if (isset($_POST["remove_id"]) && ($tmp_input = $_POST["remove_id"])) {
			$PROCESSED["remove_id"] = $tmp_input;
		}
		if (isset($_POST["related_event_ids_clean"]) && ($tmp_input = explode(",", $_POST["related_event_ids_clean"])) && is_array($tmp_input)) {
			$PROCESSED["related_event_ids"] = $tmp_input;
			if ($PROCESSED["add_id"] && !$related_event_error) {
				$PROCESSED["related_event_ids"][] = $PROCESSED["add_id"];
			}
			if ($PROCESSED["remove_id"] && array_search($PROCESSED["remove_id"], $PROCESSED["related_event_ids"]) !== false) {
				unset($PROCESSED["related_event_ids"][array_search($PROCESSED["remove_id"], $PROCESSED["related_event_ids"])]);
			}
		}
	}

	if (isset($PROCESSED[$tables["primary_key"]]) && $PROCESSED[$tables["primary_key"]]) {
		if ($event = $event_model->fetchRowByID($PROCESSED[$tables["primary_key"]])->toArray()) {
			if (isset($event_exists) && $event_exists[$tables["parent"]] == ($event[$tables["parent"]] ? $event[$tables["parent"]] : $PROCESSED[$tables["primary_key"]])) {
				$related_event_error = true;
				$related_event_error_message = "The event ID which you supplied is already associated with this event. Please try again with an event ID which is not already related to the current event.";
			}
			$related_event_ids = "";
			$related_event_ids_clean = "";
			$related_events = array();
			if (isset($PROCESSED["related_event_ids"]) && is_array($PROCESSED["related_event_ids"]) && !$related_event_error) {
				foreach ($PROCESSED["related_event_ids"] as $event_id) {
                    if ($temp_event = $event_model->fetchRowByIDCourseId($event_id, $PROCESSED["course_id"])) {
						$related_events[] = $temp_event;
					}
				}
			} else {
				$related_events = $event_model->fetchAllByParentID($PROCESSED[$tables["primary_key"]]);
			}

			if ($related_events) {
				foreach ($related_events as $related_event) {
                    $related_event = $related_event->toArray();
					$related_event_ids .= ($related_event_ids ? ", ".$db->qstr($related_event[$tables["primary_key"]]) : $db->qstr($related_event[$tables["primary_key"]]));
					$related_event_ids_clean .= ($related_event_ids_clean ? ", ".$related_event[$tables["primary_key"]] : $related_event[$tables["primary_key"]]);
				}
			}
			?>
            <div class="control-group">
				<input type="hidden" id="parent_id" name="parent_id" value="<?php echo ($event[$tables["parent"]] ? $event[$tables["parent"]] : $PROCESSED[$tables["primary_key"]]); ?>" />
				<input id="related_event_ids_clean" name="related_event_ids_clean" type="hidden" value="<?php echo $related_event_ids_clean; ?>">
				<?php

                if ($related_events) {
                    foreach ($related_events as $related_event) {
                        $related_event = $related_event->toArray();
                        ?>
                        <input id="related_event_ids" name="related_event_ids[]" type="hidden" value="<?php echo $related_event[$tables["primary_key"]]; ?>">
                        <?php
                    }
                }
				?>
                <label for="related_events_select" class="control-label form-nrequired">Child Events:</label>
                <div class="controls">
                    <input type="text" id="related_event_title" name="related_event_title" class="span5" autocomplete="off" placeholder="<?php echo $translate->_("Search by title or ID"); ?>" />
					<input class="btn" type="button" value="Add" onclick="addRelatedEvent($('related_event_title').value)" />
					<script type="text/javascript">
						$("related_event_title").observe("keypress", function(event){
							if(event.keyCode == Event.KEY_RETURN) {
								Event.stop(event);
								addRelatedEvent($("related_event_title").value);
							}
						});
					</script>
					<?php
                    if ($related_event_error) {
                        echo "<div id=\"display-error-box\" class=\"alert alert-error space-above\">\n";
                        echo "<ul><li>".$related_event_error_message."</li></ul>";
                        echo "</div>\n";
                    }
					$ONLOAD[] = "generateEventAutocomplete()";
					?>
					<div class="autocomplete" id="events_autocomplete" style="max-height: 280px; overflow: auto;"></div>
                    <div id="related_events_list">
                        <ul class="unstyled" style="margin-top: 15px">
                            <?php
                            if ($related_events) {
                                foreach ($related_events as $related_event) {
                                    $related_event = $related_event->toArray();
                                    ?>
                                    <li id="related_event_<?php echo $related_event[$tables["primary_key"]]; ?>">
                                        <a href="<?php echo ENTRADA_URL . "/admin/events?section=edit&" . ($is_draft ? "mode=draft&" : "") . "id=" . $related_event[$tables["primary_key"]] ; ?>"><?php echo $related_event["event_title"]; ?></a>
                                        <img src="<?php echo ENTRADA_URL; ?>/images/action-delete.gif" onclick="removeRelatedEvent('<?php echo $related_event[$tables["primary_key"]]; ?>');" class="pull-right" style="cursor:pointer;" />
                                        <span class="content-small">Event on <?php echo date(DEFAULT_DATETIME_FORMAT, $related_event["event_start"]); ?></span>
                                    </li>
                                    <?php
                                }
                            }
                            ?>
                        </ul>
                    </div>
                </div>
			</div>
			<?php
			if ((($related_events && $related_event_ids) || (isset($PROCESSED["remove_id"]) && $PROCESSED["remove_id"])) && !$related_event_error) {
				$added_events = array();
				$query = "SELECT * FROM `" .$tables["events"]. "` WHERE `" .$tables["parent"]. "` = ".$db->qstr((isset($event[$tables["parent"]]) && $event[$tables["parent"]] ? $event[$tables["parent"]] : $event[$tables["primary_key"]])).($related_event_ids ? " AND `" . $tables["primary_key"] . "` NOT IN (".$related_event_ids.")" : "");
				$removed_events = $db->GetAll($query);
				$query = "SELECT * FROM `" .$tables["events"]. "` WHERE `" .$tables["parent"]. "` = ".$db->qstr((isset($event[$tables["parent"]]) && $event[$tables["parent"]] ? $event[$tables["parent"]] : $event[$tables["primary_key"]]))." AND `" .$tables["primary_key"]. "` IN (".$related_event_ids.")";
				$existing_events = $db->GetAll($query);
				foreach ($related_events as $related_event) {
                    $related_event = $related_event->toArray();
					if (array_search($related_event, $existing_events) === false) {
						$added_events[] = $related_event;
					}
				}
				if (isset($removed_events) && $removed_events) {
					foreach ($removed_events as $removed_event) {
						$query = "UPDATE `" .$tables["events"]. "` SET `" .$tables["parent"]. "` = NULL WHERE `" .$tables["primary_key"]. "` = ".$db->qstr($removed_event[$tables["primary_key"]]);
						if (!$db->Execute($query)) {
							application_log("error", "Unable to set parent_id of an event [".$removed_event["event_id"]."] to null to remove the relationship between it and the parent event. Database said: ".$db->ErrorMsg());
						}
					}
				}
				if (isset($added_events) && $added_events) {
					foreach ($added_events as $added_event) {
						$query = "UPDATE `" .$tables["events"]. "` SET `" . $tables["parent"]. "` = ".$db->qstr((isset($event[$tables["parent"]]) && $event[$tables["parent"]] ? $event[$tables["parent"]] : $event[$tables["primary_key"]])). " WHERE  `" .$tables["primary_key"]. "` = ".$db->qstr($added_event[$tables["primary_key"]]);
						if (!$db->Execute($query)) {
							application_log("error", "Unable to set parent_id [".(isset($event["parent_id"]) && $event["parent_id"] ? $event["parent_id"] : $event["event_id"])."] of an event [".$added_event["event_id"]."] to add a relationship between it and the parent event. Database said: ".$db->ErrorMsg());
						}
					}
				}
			}
		}
	} else {
		echo "<div id=\"display-error-box\" class=\"display-error\">\n";
		echo "<ul><li>No valid <strong>Event</strong> was identified to fetch the child or sibling events from the system for.</li></ul>";
		echo "</div>\n";
	}
}

if (isset($use_ajax) && $use_ajax) {
	exit;
}