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

if((!defined("PARENT_INCLUDED")) || (!defined("IN_POLLS"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif(!$ENTRADA_ACL->amIAllowed('poll', 'create')) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000);";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	$PROCESSED_ANSWERS	= array();
	$BREADCRUMB[]		= array("url" => ENTRADA_URL."/admin/polls?".replace_query(array("section" => "add")), "title" => "Adding Poll");

	echo "<h1>Adding Poll</h1>\n";

	// Error Checking
	switch($STEP) {
		case 2 :
			if((isset($_POST["poll_target"])) && ($poll_target = clean_input($_POST["poll_target"], "alphanumeric"))) {
				$PROCESSED["poll_target"] = $poll_target;
			} else {
				$ERROR++;
				$ERRORSTR[] = "You must select a valid target audience from the select box.";
			}

			if((isset($_POST["poll_question"])) && ($poll_question = clean_input($_POST["poll_question"], array("trim")))) {
				$PROCESSED["poll_question"] = $poll_question;
			} else {
				$ERROR++;
				$ERRORSTR[] = "You must enter a poll question in order to add a poll.";
			}

			if((isset($_POST["poll_answer_1"])) && ($poll_answer_1 = clean_input($_POST["poll_answer_1"], array("trim")))) {
				$PROCESSED_ANSWERS[0] = $poll_answer_1;
			} else {
				$ERROR++;
				$ERRORSTR[] = "You must provide an answer for poll answer 1.";
			}

			if((isset($_POST["poll_answer_2"])) && ($poll_answer_2 = clean_input($_POST["poll_answer_2"], array("trim")))) {
				$PROCESSED_ANSWERS[1] = $poll_answer_2;
			} else {
				$ERROR++;
				$ERRORSTR[] = "You must provide an answer for poll answer 2.";
			}

			if((isset($_POST["poll_answer_3"])) && ($poll_answer_3 = clean_input($_POST["poll_answer_3"], array("trim")))) {
				$PROCESSED_ANSWERS[2] = $poll_answer_3;
			}

			if((isset($_POST["poll_answer_4"])) && ($poll_answer_4 = clean_input($_POST["poll_answer_4"], array("trim")))) {
				$PROCESSED_ANSWERS[3] = $poll_answer_4;
			}

			if((isset($_POST["poll_answer_5"])) && ($poll_answer_5 = clean_input($_POST["poll_answer_5"], array("trim")))) {
				$PROCESSED_ANSWERS[4] = $poll_answer_5;
			}

			$display_date = validate_calendars("poll", true, false);
			if((isset($display_date["start"])) && ((int) $display_date["start"])) {
				$PROCESSED["poll_from"] = (int) $display_date["start"];
			} else {
				$ERROR++;
				$ERRORSTR[] = "You must select a valid display start date.";
			}

			if((isset($display_date["finish"])) && ((int) $display_date["finish"])) {
				$PROCESSED["poll_until"] = (int) $display_date["finish"];
			} else {
				$PROCESSED["poll_until"] = 0;
			}

			if(!$ERROR) {
				$PROCESSED["updated_date"]	= time();
				$PROCESSED["updated_by"]		= $ENTRADA_USER->getID();

				if($db->AutoExecute("poll_questions", $PROCESSED, "INSERT")) {
					if($POLL_ID = $db->Insert_Id()) {
						foreach($PROCESSED_ANSWERS as $order => $poll_answer) {
							$answer				= array();
							$answer["poll_id"]		= $POLL_ID;
							$answer["answer_text"]	= $poll_answer;
							$answer["answer_order"]	= $order;

							$db->AutoExecute("poll_answers", $answer, "INSERT");
						}

						application_log("success", "Successfully added poll ID [".$POLL_ID."]");
					} else {
						application_log("error", "Unable to fetch the newly inserted poll identifier for this poll.");
					}

					$url			= ENTRADA_URL."/admin/polls";
					$SUCCESS++;
					$SUCCESSSTR[]  = "You have successfully added a new poll to the system.<br /><br />You will now be redirected to the poll index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
					$ONLOAD[]		= "setTimeout('window.location=\\'".$url."\\'', 5000);";

				} else {
					$ERROR++;
					$ERRORSTR[] = "There was a problem inserting this poll into the system. The MEdTech Unit was informed of this error; please try again later.";

					application_log("error", "There was an error inserting a poll. Database said: ".$db->ErrorMsg());
				}
			}

			if($ERROR) {
				$STEP = 1;
			}
		break;
		case 1 :
		default :
			continue;
		break;
	}

	// Page Display
	switch($STEP) {
		case 2 :
			if($SUCCESS) {
				echo display_success();
			}
			if($NOTICE) {
				echo display_notice();
			}
			if($ERROR) {
				echo display_error();
			}
		break;
		case 1 :
		default :
			if($ERROR) {
				echo display_error();
			}
			?>
			
			<form action="<?php echo ENTRADA_URL; ?>/admin/polls?section=add&amp;step=2" method="post" class="form-horizontal">
			<h2>Poll Details</h2>
			<div class="control-group">
				<label for="poll_target" class="control-label form-required">Target Audience:</label>
				<div class="controls">
					<select id="poll_target" name="poll_target" style="width: 300px">
					<?php
					if(is_array($POLL_TARGETS)) {
						foreach($POLL_TARGETS as $key => $target_name) {
							echo "<option value=\"".$key."\"".((isset($PROCESSED["poll_target"]) && $PROCESSED["poll_target"] == $key) ? " selected=\"selected\"" : "").">".$target_name."</option>\n";
						}
					} else {
						echo "<option value=\"all\" selected=\"selected\">-- Poll everyone --</option>\n";
					}
					?>
					</select>
				</div>
			</div>
			<div class="control-group">
				<label for="poll_question" class="control-label form-required">Poll Question:</label>
				<div class="controls">
					<textarea id="poll_question" name="poll_question" cols="60" rows="7" style="width: 90%; height: 70px"><?php echo ((isset($PROCESSED["poll_question"])) ? html_encode(trim($PROCESSED["poll_question"])) : ""); ?></textarea>
				</div>
			</div>
			<div class="control-group">
				<label for="poll_answer_1" class="control-label form-required">Answer 1:</label>
				<div class="controls">
					<input type="text" id="poll_answer_1" name="poll_answer_1" style="width: 90%" value="<?php echo ((isset($PROCESSED_ANSWERS[0])) ? html_encode(trim($PROCESSED_ANSWERS[0])) : ""); ?>" />
				</div>
			</div>
			<div class="control-group">
				<label for="poll_answer_2" class="control-label form-required">Answer 2:</label>
				<div class="controls">
					<input type="text" id="poll_answer_2" name="poll_answer_2" style="width: 90%" value="<?php echo ((isset($PROCESSED_ANSWERS[1])) ? html_encode(trim($PROCESSED_ANSWERS[1])) : ""); ?>" />
				</div>
			</div>
			<div class="control-group">
				<label for="poll_answer_3" class="control-label form-required">Answer 3:</label>
				<div class="controls">
					<input type="text" id="poll_answer_3" name="poll_answer_3" style="width: 90%" value="<?php echo ((isset($PROCESSED_ANSWERS[2])) ? html_encode(trim($PROCESSED_ANSWERS[2])) : ""); ?>" />
				</div>
			</div>
			<div class="control-group">
				<label for="poll_answer_4" class="control-label form-required">Answer 4:</label>
				<div class="controls">
					<input type="text" id="poll_answer_4" name="poll_answer_4" style="width: 90%" value="<?php echo ((isset($PROCESSED_ANSWERS[3])) ? html_encode(trim($PROCESSED_ANSWERS[3])) : ""); ?>" />
				</div>
			</div>
			<div class="control-group">
				<label for="poll_answer_5" class="control-label form-required">Answer 5:</label>
				<div class="controls">
					<input type="text" id="poll_answer_5" name="poll_answer_5" style="width: 90%" value="<?php echo ((isset($PROCESSED_ANSWERS[4])) ? html_encode(trim($PROCESSED_ANSWERS[4])) : ""); ?>" />
				</div>
			</div>
			<h2>Time Release Options</h2>
			<table>
				<tr>
					<?php echo generate_calendars("poll", "", true, true, ((isset($PROCESSED["poll_from"])) ? $PROCESSED["poll_from"] : time()), true, false, ((isset($PROCESSED["poll_until"])) ? $PROCESSED["poll_until"] : 0)); ?>
				</tr>
			</table>
			
			<div class="row-fluid" style="margin:10px 0">
				<input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>'" />
				<div class="pull-right">
					<input type="submit" class="btn btn-primary" value="Save" />
				</div>
			</div>
		<!--	<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Adding Poll">
			<colgroup>
				<col style="width: 3%" />
				<col style="width: 20%" />
				<col style="width: 77%" />
			</colgroup>
			<tr>
				<td colspan="3"><h2>Poll Details</h2></td>
			</tr>
			<tr>
				<td></td>
				<td><label for="poll_target" class="form-required">Target Audience</label></td>
				<td>
					<select id="poll_target" name="poll_target" style="width: 300px">
					<?php
					if(is_array($POLL_TARGETS)) {
						foreach($POLL_TARGETS as $key => $target_name) {
							echo "<option value=\"".$key."\"".((isset($PROCESSED["poll_target"]) && $PROCESSED["poll_target"] == $key) ? " selected=\"selected\"" : "").">".$target_name."</option>\n";
						}
					} else {
						echo "<option value=\"all\" selected=\"selected\">-- Poll everyone --</option>\n";
					}
					?>
					</select>
				</td>
			</tr>
			<tr>
				<td></td>
				<td style="vertical-align: top"><label for="poll_question" class="form-required">Poll Question:</label></td>
				<td style="vertical-align: top">
					<textarea id="poll_question" name="poll_question" cols="60" rows="7" style="width: 100%; height: 70px"><?php echo ((isset($PROCESSED["poll_question"])) ? html_encode(trim($PROCESSED["poll_question"])) : ""); ?></textarea>
				</td>
			</tr>
			<tr>
				<td></td>
				<td><label for="poll_answer_1" class="form-required">Answer 1:</label></td>
				<td><input type="text" id="poll_answer_1" name="poll_answer_1" style="width: 100%" value="<?php echo ((isset($PROCESSED_ANSWERS[0])) ? html_encode(trim($PROCESSED_ANSWERS[0])) : ""); ?>" /></td>
			</tr>
			<tr>
				<td></td>
				<td><label for="poll_answer_2" class="form-required">Answer 2:</label></td>
				<td><input type="text" id="poll_answer_2" name="poll_answer_2" style="width: 100%" value="<?php echo ((isset($PROCESSED_ANSWERS[1])) ? html_encode(trim($PROCESSED_ANSWERS[1])) : ""); ?>" /></td>
			</tr>
			<tr>
				<td></td>
				<td><label for="poll_answer_3" class="form-nrequired">Answer 3:</label></td>
				<td><input type="text" id="poll_answer_3" name="poll_answer_3" style="width: 100%" value="<?php echo ((isset($PROCESSED_ANSWERS[2])) ? html_encode(trim($PROCESSED_ANSWERS[2])) : ""); ?>" /></td>
			</tr>
			<tr>
				<td></td>
				<td><label for="poll_answer_4" class="form-nrequired">Answer 4:</label></td>
				<td><input type="text" id="poll_answer_4" name="poll_answer_4" style="width: 100%" value="<?php echo ((isset($PROCESSED_ANSWERS[3])) ? html_encode(trim($PROCESSED_ANSWERS[3])) : ""); ?>" /></td>
			</tr>
			<tr>
				<td></td>
				<td><label for="poll_answer_5" class="form-nrequired">Answer 5:</label></td>
				<td><input type="text" id="poll_answer_5" name="poll_answer_5" style="width: 100%" value="<?php echo ((isset($PROCESSED_ANSWERS[4])) ? html_encode(trim($PROCESSED_ANSWERS[4])) : ""); ?>" /></td>
			</tr>
			<tr>
				<td colspan="3"><h2>Time Release Options</h2></td>
			</tr>
			<?php echo generate_calendars("poll", "", true, true, ((isset($PROCESSED["poll_from"])) ? $PROCESSED["poll_from"] : time()), true, false, ((isset($PROCESSED["poll_until"])) ? $PROCESSED["poll_until"] : 0)); ?>
			<tr>
				<td colspan="3" style="padding-top: 25px">
					<table style="width: 100%" cellspacing="0" cellpadding="0" border="0">
					<tr>
						<td style="width: 25%; text-align: left">
							<input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>'" />
						</td>
						<td style="width: 75%; text-align: right; vertical-align: middle">
							<input type="submit" class="btn btn-primary" value="Save" />
						</td>
					</tr>
					</table>
				</td>
			</tr>
			</table>-->
			</form>
			<?php
		break;
	}
}
?>