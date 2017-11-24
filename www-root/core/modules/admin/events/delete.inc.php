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

if((!defined("PARENT_INCLUDED")) || (!defined("IN_EVENTS"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif(!$ENTRADA_ACL->amIAllowed('event', 'delete', false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	if (isset($_GET["mode"]) && $_GET["mode"] == "draft") {
		if (isset($_GET["draft_id"])) {
			$draft_id = (int) $_GET["draft_id"];
		}
		$tables["events"] = "draft_events";
		$is_draft = true;
		$wheretype = "devent_id";
	} else {
		$tables["events"] = "events";
		$wheretype = "event_id";
	}
	$BREADCRUMB[]	= array("url" => "", "title" => "Delete Events");

	echo "<h1>Delete Events</h1>";

	$EVENT_IDS = array();

	// Error Checking
	switch($STEP) {
		case 2 :
		case 1 :
		default :
			if(((!isset($_POST["checked"])) || (!is_array($_POST["checked"])) || (!@count($_POST["checked"]))) && (!isset($_GET["id"]) || !$_GET["id"])) {
				header("Location: ".ENTRADA_URL."/admin/events");
				exit;
			} else {
				if ((isset($_POST["checked"])) && (is_array($_POST["checked"])) && (@count($_POST["checked"]))) {
					foreach($_POST["checked"] as $event_id) {
						$event_id = (int) trim($event_id);
						if($event_id) {
							$EVENT_IDS[] = $event_id;
						}
					}
				} elseif (isset($_GET["id"]) && ($event_id = clean_input($_GET["id"], array("trim", "int")))) {
					$EVENT_IDS[] = $event_id;
				}

				if(!@count($EVENT_IDS)) {
					$ERROR++;
					$ERRORSTR[] = "There were no valid event identifiers provided to delete. Please ensure that you access this section through the event index.";
				}
			}

			if($ERROR) {
				$STEP = 1;
			}
		break;
	}

	// Display Page
	switch($STEP) {
		case 2 :
			$removed = array();

			foreach($EVENT_IDS as $event_id) {
				$allow_removal = false;
				
				if($event_id = (int) $event_id) {
					if ($is_draft) {
						$query = "SELECT `event_title` FROM `draft_events` WHERE `devent_id` = ".$event_id;
						$event = $db->GetRow($query);
						
						$query = "DELETE FROM `draft_contacts` WHERE `devent_id` = ".$db->qstr($event_id);
						if ($db->Execute($query)) {
							$query = "DELETE FROM `draft_eventtypes` WHERE `devent_id` = ".$db->qstr($event_id);
							if ($db->Execute($query)) {
								$query = "DELETE FROM `draft_audience` WHERE `devent_id` = ".$db->qstr($event_id);
								if ($db->Execute($query)) {
									$query = "DELETE FROM `draft_events` WHERE `devent_id` = ".$db->qstr($event_id);
									if ($db->Execute($query)) {
										$removed[$event_id]["event_title"] = $event["event_title"];
									}
								}
							}
						} else {
							$ERROR++;
							$ERRORSTR[] = "An error occured when deleting events. ";
						}
					} else {
						$query	= "	SELECT a.`event_id`, a.`course_id`, a.`event_title`, b.`organisation_id`
									FROM `events` AS a
									LEFT JOIN `courses` AS b
									ON b.`course_id` = a.`course_id`
									WHERE a.`event_id` = ".$db->qstr($event_id)."
									AND b.`course_active` = '1'";
						$result	= $db->GetRow($query);
						if ($result) {
							if($ENTRADA_ACL->amIAllowed(new EventResource($result["event_id"], $result["course_id"], $result["organisation_id"]), 'delete')) {

								/**
								* Check to see if any quizzes are attached to this event.
								*/
								$query		= "	SELECT a.*
												FROM `attached_quizzes` AS a
												LEFT JOIN `quiz_progress` AS b
												ON b.`aquiz_id` = a.`aquiz_id`
												WHERE a.`content_type` = 'event' 
												AND a.`content_id` = ".$db->qstr($event_id);
								$quizzes	= $db->GetAll($query);
								if (($quizzes) && (count($quizzes) > 0)) {
									$ERROR++;
									$ERRORSTR[] = "You cannot delete <a href=\"".ENTRADA_URL."/admin/events?section=content&amp;id=".$event_id."\" style=\"font-weight: bold\">".html_encode($result["event_title"])."</a> at this time because there are quizzes attached. If you need to delete this event please remove any attached quizzes first.";
								} else {
									/**
									* Remove all records from event_eventtypes table.
									*/
									$query		= "SELECT * FROM `event_eventtypes` WHERE `event_id` = ".$db->qstr($event_id);
									$results	= $db->GetAll($query);
									if($results) {
										foreach($results as $result) {
											$removed[$event_id][$result["eventtype_id"]][] = $result["duration"];
										}

										$query = "DELETE FROM `event_eventtypes` WHERE `event_id` = ".$db->qstr($event_id);
										$db->Execute($query);
									}

									/**
									* Remove all records from event_audience table.
									*/
									$query		= "SELECT * FROM `event_audience` WHERE `event_id` = ".$db->qstr($event_id);
									$results	= $db->GetAll($query);
									if($results) {
										foreach($results as $result) {
											$removed[$event_id][$result["audience_type"]][] = $result["audience_value"];
										}

										$query = "DELETE FROM `event_audience` WHERE `event_id` = ".$db->qstr($event_id);
										$db->Execute($query);
									}

									/**
									* Remove all records from event_contacts table.
									*/
									$query		= "SELECT * FROM `event_contacts` WHERE `event_id` = ".$db->qstr($event_id);
									$results	= $db->GetAll($query);
									if($results) {
										foreach($results as $result) {
											$removed[$event_id]["contacts"][] = $result["proxy_id"];
										}

										$query = "DELETE FROM `event_contacts` WHERE `event_id` = ".$db->qstr($event_id);
										$db->Execute($query);
									}

									/**
									* Remove all records from event_discussions table.
									*/
									$query		= "SELECT * FROM `event_discussions` WHERE `event_id` = ".$db->qstr($event_id);
									$results	= $db->GetAll($query);
									if($results) {
										foreach($results as $result) {
											$removed[$event_id]["discussion_title"][] = $result["discussion_title"];
										}

										$query = "DELETE FROM `event_discussions` WHERE `event_id` = ".$db->qstr($event_id);
										$db->Execute($query);
									}

									/**
									* Remove all records from event_topics table.
									*/
									$query		= "SELECT * FROM `event_topics` WHERE `event_id` = ".$db->qstr($event_id);
									$results	= $db->GetAll($query);
									if($results) {
										foreach($results as $result) {
											$removed[$event_id]["etopic_id"][] = $result["etopic_id"];
										}

										$query = "DELETE FROM `event_topics` WHERE `event_id` = ".$db->qstr($event_id);
										$db->Execute($query);
									}

									/**
									* Remove all records from event_lti_consumers table.
									*/
									$query		= "SELECT * FROM `event_lti_consumers` WHERE `event_id` = ".$db->qstr($event_id);
									$results	= $db->GetAll($query);
									if($results) {
										foreach($results as $result) {
//											$removed[$event_id]["ed11_id"][] = $result["ed11_id"];
										}

										$query = "DELETE FROM `event_lti_consumers` WHERE `event_id` = ".$db->qstr($event_id);
										$db->Execute($query);
									}

									/**
									* Remove all records from event_files table, and remove the
									* files from the file system.
									*/
									$query		= "SELECT * FROM `event_files` WHERE `event_id` = ".$db->qstr($event_id);
									$results	= $db->GetAll($query);
									if($results) {
										foreach($results as $result) {
											$removed[$event_id]["files"][] = $result["file_name"];

											if(@file_exists(FILE_STORAGE_PATH."/".$result["efile_id"])) {
												@unlink(FILE_STORAGE_PATH."/".$result["efile_id"]);
											}
										}

										$query = "DELETE FROM `event_files` WHERE `event_id` = ".$db->qstr($event_id);
										$db->Execute($query);
									}

									/**
									* Remove all records from event_links table.
									*/
									$query		= "SELECT * FROM `event_links` WHERE `event_id` = ".$db->qstr($event_id);
									$results	= $db->GetAll($query);
									if($results) {
										foreach($results as $result) {
											$removed[$event_id]["links"][] = $result["link"];
										}

										$query = "DELETE FROM `event_links` WHERE `event_id` = ".$db->qstr($event_id);
										$db->Execute($query);
									}

									/**
									* Remove all records from event_objectives table.
									*/
									$query		= "SELECT * FROM `event_objectives` WHERE `event_id` = ".$db->qstr($event_id);
									$results	= $db->GetAll($query);
									if($results) {
										foreach($results as $result) {
											$removed[$event_id]["objective_id"][] = $result["objective_id"];
										}

										$query = "DELETE FROM `event_objectives` WHERE `event_id` = ".$db->qstr($event_id);
										$db->Execute($query);
									}

									/**
									* Remove all records from event_related table.
									*/
									$query		= "SELECT * FROM `event_related` WHERE `event_id` = ".$db->qstr($event_id)." OR (`related_type` = 'event_id' AND `related_value` = ".$db->qstr($event_id).")";
									$results	= $db->GetAll($query);
									if($results) {
										foreach($results as $result) {
											$removed[$event_id]["event_id"][] = $result["event_id"];
										}
									}

									/**
									* Remove all records from event_history table.
									*/
									$query		= "SELECT * FROM `event_history` WHERE `event_id` = ".$db->qstr($event_id);
									$results	= $db->GetAll($query);
									if($results) {
										foreach($results as $result) {
											$removed[$event_id]["history"][] = $result["history_message"];
										}

										$query = "DELETE FROM `event_history` WHERE `event_id` = ".$db->qstr($event_id);
										$db->Execute($query);
									}

									/**
									* Remove event_id record from events table.
									*/
									$query		= "SELECT * FROM `events` WHERE `event_id` = ".$db->qstr($event_id);
									$results	= $db->GetAll($query);
									if($results) {
										foreach($results as $result) {
											$removed[$event_id]["event_title"] = $result["event_title"];

											$query = "DELETE FROM `event_related` WHERE `event_id` = ".$db->qstr($event_id)." OR (`related_type` = 'event_id' AND `related_value` = ".$db->qstr($event_id).")";
											$db->Execute($query);
										}

										/**
										* Remove event_id record from events table.
										*/
										$query		= "SELECT * FROM `events` WHERE `event_id` = ".$db->qstr($event_id);
										$results	= $db->GetAll($query);
										if($results) {
											foreach($results as $result) {
												$removed[$event_id]["event_title"] = $result["event_title"];
											}
											$query = "DELETE FROM `events` WHERE `event_id` = ".$db->qstr($event_id);
											$db->Execute($query);
										}
									}
								}
							} else {
								$ERROR++;
								$ERRORSTR[] = "You do not have the permissions required to delete <a href=\"".ENTRADA_URL."/admin/events?section=content&amp;id=".$event_id."\" style=\"font-weight: bold\">".html_encode($result["event_title"])."</a>.<br /><br />If you believe you are receiving this message in error, please contact the administrator.";
							}
						}
					}
				}
			}

			$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/events".(($is_draft) ? "/drafts?section=edit&draft_id=".$draft_id : "")."\\'', 5000)";

			if($total_removed = @count($removed)) {
				$SUCCESS++;
				$SUCCESSSTR[$SUCCESS]  = "You have successfully removed ".$total_removed." event".(($total_removed != 1) ? "s" : "")." from the system:";
				$SUCCESSSTR[$SUCCESS] .= "<div style=\"padding-left: 15px; padding-bottom: 15px; font-family: monospace\">\n";
				foreach($removed as $result) {
					$SUCCESSSTR[$SUCCESS] .=  html_encode($result["event_title"])."<br />";
				}
				$SUCCESSSTR[$SUCCESS] .= "</div>\n";
				$SUCCESSSTR[$SUCCESS] .= "You will be automatically redirected to the event index in 5 seconds, or you can <a href=\"".ENTRADA_URL."/admin/events\">click here</a> if you do not wish to wait.";

				echo display_success();

				application_log("success", "Successfully removed event ids: ".implode(", ", $EVENT_IDS));
			} else {
				$ERROR++;
				$ERRORSTR[] = "Unable to remove the requested events from the system.<br /><br />The system administrator has been informed of this issue and will address it shortly; please try again later.";

				application_log("error", "Failed to remove all events from the remove request. Database said: ".$db->ErrorMsg());
			}

			if ($ERROR) {
				echo display_error();
			}
		break;
		case 1 :
		default :
			if($ERROR) {
				echo display_error();
			} else {
				$total_events	= count($EVENT_IDS);
				
				$query		= "	SELECT a.*, CONCAT_WS(', ', c.`lastname`, c.`firstname`) AS `fullname`, d.`course_id`, d.organisation_id
								FROM ".$tables["events"]." AS a
								LEFT JOIN `event_contacts` AS b
								ON b.`event_id` = a.`event_id`
								AND b.`contact_order` = '0'
								LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS c
								ON c.`id` = b.`proxy_id`
								LEFT JOIN `courses` AS d
								ON d.`course_id` = a.`course_id`
								WHERE a.`".$wheretype."` IN (".implode(", ", $EVENT_IDS).")
								AND d.`course_active` = '1'
								ORDER BY a.`event_start` ASC";
				$results	= $db->GetAll($query);
				if($results) {
					echo display_notice(array("Please review the following event".(($total_events != 1) ? "s" : "")." to ensure that you wish to <strong>permanently delete</strong> ".(($total_events != 1) ? "them" : "it").".<br /><br />This will also remove any attached resources, contacts, etc. and this action cannot be undone."));
					?>
					<form action="<?php echo ENTRADA_URL; ?>/admin/events?section=delete&amp;step=2<?php echo (($is_draft) ? "&mode=draft&draft_id=".$draft_id : "" ); ?>" method="post">
					<table class="tableList" cellspacing="0" summary="List of Events" width="100%">
					<colgroup>
						<col class="modified" />
						<col class="date" />
						<col class="phase" />
						<col class="teacher" />
						<col class="title" />
						<?php echo ((!$is_draft) ? "<col class=\"attachment\" />" : ""); ?>
					</colgroup>
					<thead>
						<tr>
							<td class="modified" style="font-size: 12px">&nbsp;</td>
							<td class="date sortedASC" style="font-size: 12px"><div class="noLink">Date &amp; Time</div></td>
							<td class="phase" style="font-size: 12px">Phase</td>
							<td class="teacher" style="font-size: 12px"><?php echo $translate->_("Teacher"); ?></td>
							<td class="title" style="font-size: 12px">Event Title</td>
							<?php echo ((!$is_draft) ? "<td class=\"attachment\" style=\"font-size: 12px\">&nbsp;</td>" : ""); ?>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<td></td>
							<td colspan="5" style="padding-top: 10px">
								<input type="submit" class="btn btn-danger" value="Confirm Removal" />
							</td>
						</tr>
					</tfoot>
					<tbody>
						<?php
						foreach($results as $result) {
							$url			= "";
							$accessible		= true;
							$administrator	= false;

							if($ENTRADA_ACL->amIAllowed(new EventResource($result["event_id"], $result["course_id"], $result["organisation_id"]), 'delete')) {
								$administrator = true;
							} else {
								if((($result["release_date"]) && ($result["release_date"] > time())) || (($result["release_until"]) && ($result["release_until"] < time()))) {
									$accessible = false;
								}
							}
							if($administrator) {
								$url 	= ENTRADA_URL."/admin/events?section=edit&amp;id=".$result[$wheretype];
								
								echo "<tr id=\"event-".$result[$wheretype]."\" class=\"event".((!$url) ? " np" : ((!$accessible) ? " na" : ""))."\">\n";
								echo "	<td class=\"modified\"><input type=\"checkbox\" name=\"checked[]\" value=\"".$result[$wheretype]."\" checked=\"checked\" /></td>\n";
								echo "	<td class=\"date".((!$url) ? " np" : "")."\">".(($url) ? "<a href=\"".$url."\" title=\"Event Date\">" : "").date(DEFAULT_DATE_FORMAT, $result["event_start"]).(($url) ? "</a>" : "")."</td>\n";
								echo "	<td class=\"phase".((!$url) ? " np" : "")."\">".(($url) ? "<a href=\"".$url."\" title=\"Intended For Phase ".html_encode($result["event_phase"])."\">" : "").html_encode($result["event_phase"]).(($url) ? "</a>" : "")."</td>\n";
								echo "	<td class=\"teacher".((!$url) ? " np" : "")."\">".(($url) ? "<a href=\"".$url."\" title=\"Primary Teacher: ".html_encode($result["fullname"])."\">" : "").html_encode($result["fullname"]).(($url) ? "</a>" : "")."</td>\n";
								echo "	<td class=\"title".((!$url) ? " np" : "")."\">".(($url) ? "<a href=\"".$url."\" title=\"Event Title: ".html_encode($result["event_title"])."\">" : "").html_encode($result["event_title"]).(($url) ? "</a>" : "")."</td>\n";
								echo ((!$is_draft) ? "	<td class=\"attachment\">".(($url) ? "<a href=\"".ENTRADA_URL."/admin/events?section=content&amp;id=".$result["event_id"]."\"><img src=\"".ENTRADA_URL."/images/event-contents.gif\" width=\"16\" height=\"16\" alt=\"Manage Event Content\" title=\"Manage Event Content\" border=\"0\" /></a>" : "<img src=\"".ENTRADA_URL."/images/pixel.gif\" width=\"16\" height=\"16\" alt=\"\" title=\"\" />")."</td>\n" : "" );
								echo "</tr>\n";
							}
						}
						?>
					</tbody>
					</table>
					</form>
					<?php
				} else {
					application_log("error", "The confirmation of removal query returned no results... curious Database said: ".$db->ErrorMsg());
					
					header("Location: ".ENTRADA_URL."/admin/events");
					exit;	
				}
			}
		break;
	}
}
