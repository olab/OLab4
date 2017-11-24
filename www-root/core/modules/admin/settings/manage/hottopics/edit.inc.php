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
 * @author Unit: MEdTech Unit
 * @author Developer: Brandon Thorn <brandon.thorn@queensu.ca>
 * @copyright Copyright 2011 Queen's University. All Rights Reserved.
 *
*/

if (!defined("PARENT_INCLUDED") || !defined("IN_CONFIGURATION")) {
	exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "read", false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {

	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/settings/manage/hottopics?".replace_query(array("section" => "edit"))."&amp;org=".$ORGANISATION_ID, "title" => "Edit Hot Topic");
	
	if ((isset($_GET["topic_id"])) && ($topic = clean_input($_GET["topic_id"], array("notags", "trim")))) {
		$PROCESSED["topic_id"] = $topic;
	}
	
	
	// Error Checking
	switch ($STEP) {
		case 2 :
			/**
			 * Required field "objective_name" / Objective Name
			 */
			if (isset($_POST["topic_name"]) && ($topic_name = clean_input($_POST["topic_name"], array("notags", "trim")))) {
				$PROCESSED["topic_name"] = $topic_name;
			} else {
				$ERROR++;
				$ERRORSTR[] = "The <strong>Hot Topic Name</strong> is a required field.";
			}

			/**
			 * Non-required field "objective_description" / Objective Description
			 */
			if (isset($_POST["topic_description"]) && ($topic_description = clean_input($_POST["topic_description"], array("notags", "trim")))) {
				$PROCESSED["topic_description"] = $topic_description;
			} else {
				$PROCESSED["topic_description"] = "";
			}

		
			if (!$ERROR) {
				
				// Check to see if the topic_id is used in more than one organisation
				$query = "	SELECT `organisation_id`, `topic_id`
							FROM `topic_organisation` WHERE `topic_id` = ".$db->qstr($PROCESSED["topic_id"]);
				$results = $db->GetAssoc($query);

				if (count($results) > 1) {
					// if the topic_id is used in multiple organisations we are going to create a new entry and remove the old one
					$action = "INSERT";
					$where = FALSE;
					
					// we need a list of event_ids that are associated with this topic_id
					$query = "	SELECT b.`event_id`, c.`topic_id`
								FROM `courses` AS a
								LEFT JOIN `events` AS b
								ON a.`course_id` = b.`course_id`
								LEFT JOIN `event_topics` AS c
								ON b.`event_id` = c.`event_id`
								WHERE a.`organisation_id` = ".$db->qstr($ORGANISATION_ID)."
								AND c.`topic_id` = ".$db->qstr($PROCESSED["topic_id"]);
					$events_list = $db->GetAssoc($query);
				} else {
					// if the eventtype_id is not in multiple organisations update it as normal
					$action = "UPDATE";
					$where = "`topic_id` = ".$db->qstr($PROCESSED["topic_id"]);
				}
				
				$params = array("topic_name" => $PROCESSED["topic_name"],"topic_description"=>$PROCESSED["topic_description"], "updated_date"=>time(),"updated_by"=>$ENTRADA_USER->getID());
				
				if ($db->AutoExecute("`events_lu_topics`", $params, $action, $where)) {
					
					if ($action == "INSERT") {
						// if creating a new topic_id we will need to delete the old one, then update all of the previously fetched events to the new one.
						$new_topic_id = $db->Insert_ID();
						
						$query = "	DELETE FROM `topic_organisation`
									WHERE `topic_id` = ".$db->qstr($PROCESSED["topic_id"])."
									AND `organisation_id` = ".$db->qstr($ORGANISATION_ID);
						$db->Execute($query);
						
						$query = "	INSERT INTO `topic_organisation` (`topic_id`, `organisation_id`)
									VALUES (".$db->qstr($new_topic_id).", ".$db->qstr($ORGANISATION_ID).")";
						$db->Execute($query);
						
						$query = "	UPDATE `event_topics`
									SET `topic_id` = ".$db->qstr($new_topic_id)."
									WHERE `event_id` IN ('".implode("', '", array_keys($events_list))."')";
						$db->Execute($query);
					}

					$url = ENTRADA_URL . "/admin/settings/manage/hottopics?org=".$ORGANISATION_ID;
					$SUCCESS++;
					$SUCCESSSTR[] = "You have successfully added <strong>".html_encode($PROCESSED["topic_name"])."</strong> to the system.<br /><br />You will now be redirected to the Hot Topics index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
					$ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";
					application_log("success", "New Event Type [".$PROCESSED["topic_id"]."] added to the system.");

				} else {
					$ERROR++;
					$ERRORSTR[] = "There was a problem inserting this Hot Topic into the system. The system administrator was informed of this error; please try again later.";

					application_log("error", "There was an error inserting a Hot Topic. Database said: ".$db->ErrorMsg());
				}
			}

			if ($ERROR) {
				$STEP = 1;
			}
		break;
		case 1 :
		default :

			
			$query = "SELECT * FROM `events_lu_topics` WHERE `topic_id` = ".$db->qstr($PROCESSED["topic_id"]);
			$result = $db->GetRow($query);
			if($result){
				$PROCESSED["topic_name"] = $result["topic_name"];
				$PROCESSED["topic_description"] = $result["topic_description"];				
			}

			
			
		break;
	}

	// Display Content
	switch ($STEP) {
		case 2 :
			if ($SUCCESS) {
				echo display_success();
			}

			if ($NOTICE) {
				echo display_notice();
			}

			if ($ERROR) {
				echo display_error();
			}
		break;
		case 1 :
		default:	
			if ($ERROR) {
				echo display_error();
			}
						
			?>
			<form action="<?php echo ENTRADA_URL."/admin/settings/manage/hottopics"."?".replace_query(array("action" => "edit", "step" => 2))."&org=".$ORGANISATION_ID; ?>" method="post">
				<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Editing Page">
					<colgroup>
						<col style="width: 25%" />
						<col style="width: 75%" />
					</colgroup>
					<thead>
						<tr>
							<td colspan="2"><h1>Edit Hot Topic</h1></td>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<td colspan="2" style="padding-top: 15px;">
								<input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/admin/settings/manage/hottopics?org=<?php echo $ORGANISATION_ID;?>'" />
								<div class="pull-right">
									<input type="submit" class="btn btn-primary" value="<?php echo $translate->_("global_button_save"); ?>" />
								</div>
							</td>
						</tr>
					</tfoot>
					<tbody>
						<tr>
							<td><label for="topic_name" class="form-required">Hot Topic Name</label></td>
							<td><input type="text" id="topic_name" name="topic_name" value="<?php echo ((isset($PROCESSED["topic_name"])) ? html_encode($PROCESSED["topic_name"]) : ""); ?>" maxlength="60" class="span11" /></td>
						</tr>
						<tr>
							<td style="vertical-align: top;"><label for="topic_description" class="form-nrequired">Hot Topic Description</label></td>
							<td>
								<textarea id="topic_description" name="topic_description" class="span11 expandable"><?php echo ((isset($PROCESSED["topic_description"])) ? html_encode($PROCESSED["topic_description"]) : ""); ?></textarea>
							</td>
						</tr>
					</tbody>
				</table>
			</form>
			<?php
		break;
	}

}

