<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to add new polls to a particular community. This action is available
 * only to community administrators.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Andrew Dos-Santos <andrew.dos-santos@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
*/

if ((!defined("COMMUNITY_INCLUDED")) || (!defined("IN_POLLS"))) {
	exit;
} elseif (!$COMMUNITY_LOAD) {
	exit;
}

$HEAD[] = "<link href=\"".ENTRADA_URL."/javascript/calendar/css/xc2_default.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/calendar/config/xc2_default.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/calendar/script/xc2_inpage.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"".COMMUNITY_URL."/javascript/polls.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/picklist.js\"></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/livepipe/livepipe.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/livepipe/window.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/livepipe/selectmultiplemod.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
	
if (isset($_GET["term"]) || isset($_POST["term"])) {
	$terminology = ucfirst(clean_input((isset($_POST["term"]) ? $_POST["term"] : $_GET["term"]), array("notags", "postclean")));
	if (strlen($terminology) < 2 || strlen($terminology) > 32) {
		$terminology = "Poll";
	}
} else {
	$terminology = "Poll";
}

Entrada_Utilities_Flashmessenger::displayMessages($MODULE);

echo "<h1>Add ".$terminology."</h1>\n";

$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=add-poll&term=".$terminology, "title" => "Add ".$terminology);

// Error Checking
switch($STEP) {
	case 2 :
		/**
		 * Required field "title" / Poll Title.
		 */
		if ((isset($_POST["poll_title"])) && ($title = clean_input($_POST["poll_title"], array("notags", "trim")))) {
			$PROCESSED["poll_title"] = $title;
		} else {
			$ERROR++;
			$ERRORSTR[] = "The <strong>".$terminology." Title</strong> field is required.";
		}
		/**
		 * Required field "question" / Poll Question.
		 */
		if ((isset($_POST["poll_question"])) && ($poll_question = clean_input($_POST["poll_question"], array("notags", "trim")))) {
			$PROCESSED["poll_question"] = $poll_question;
		} else {
			$ERROR++;
			$ERRORSTR[] = "The <strong>Question</strong> field is required.";
		}
		/**
		 * Non-Required field "description" / Poll Description.
		 */
		if ((isset($_POST["poll_description"])) && ($description = clean_input($_POST["poll_description"], array("notags", "trim")))) {
			$PROCESSED["poll_description"] = $description;
		} else {
			$PROCESSED["poll_description"] = "";
		}
		/**
		 * Required field "allow_multiple" / Allow Multiple Votes.
		 */
		if (isset($_POST['allow_multiple'])) {
			$PROCESSED["allow_multiple"] = htmlentities($_POST['allow_multiple']);
			if ($PROCESSED["allow_multiple"] == "1")
			{
				if ((isset($_POST["number_of_votes"])) && $number_of_votes = clean_input($_POST["number_of_votes"], array("int"))) {
					$PROCESSED["number_of_votes"] = $number_of_votes;
				}
				else 
				{
					$PROCESSED["number_of_votes"] = 0;
				}
			}
			else {
				$PROCESSED["number_of_votes"] = "";
			}
		} else {
			$ERROR++;
			$ERRORSTR[] = "The <strong>Allow Multiple Votes</strong> field is required.";
		}
		/**
		 * Required field "poll_responses" / Poll Responses.
		 */
		if ((isset($_POST["response"])) && is_array($_POST["response"]) && ($poll_responses = $_POST["response"])) {
			if (isset($_POST["itemListOrder"]) && ($response_keys = explode(',', clean_input($_POST["itemListOrder"], array("nows", "notags"))))) {
				foreach ($response_keys as $index) {
					if (($poll_response = clean_input($poll_responses[$index],  array("trim", "notags")))) {
						$PROCESSED["poll_responses"][] = $poll_responses[$index];
					}
				}
				$poll_responses = $PROCESSED["poll_responses"];
			}
			if (count($PROCESSED["poll_responses"]) < 2)
			{
				$ERROR++;
				$ERRORSTR[] = "You need to have at least two possible <strong>Responses</strong>.";
			}
		} else {
			$ERROR++;
			$ERRORSTR[] = "You need to have at least two possible <strong>Responses</strong>.";
		}
		
		/**
		 * Required fields "min_responses" and "max_responses" / Minimum and maximum number of responses allowed
		 */
		if ((isset($_POST["min_responses"]) && ($min = clean_input($_POST["min_responses"], array("trim", "int")))) && (isset($_POST["max_responses"]) && ($max = clean_input($_POST["max_responses"], array("trim", "int"))))) {
			$PROCESSED["maximum_responses"] = $max;
			$PROCESSED["minimum_responses"] =  $min;
			if ($min > count($PROCESSED["poll_responses"]) || $min < 1) {
				$ERROR++;
				$ERRORSTR[] = "The minimum number of responses for this question must be between 1 and the number of questions, inclusively.";
			} elseif ($max > count($PROCESSED["poll_responses"]) || $max < 1 || $max < $min) {
				$ERROR++;
				$ERRORSTR[] = "The maximum number of responses for this question must be between the minimum and the number of questions, inclusively.";
			}
		}
		
		/**
		 * Non-required processing
		 */
		if ((isset($_POST["acc_community_members"])) && ($member_ids = explode(",", $_POST["acc_community_members"])) && (is_array($member_ids)) && (count($member_ids)) && (!isset($_POST["all_members_vote"]) || (!$_POST["all_members_vote"]))) {
			foreach($member_ids as $member_id) {
				if ($member_id = (int) $member_id) {
					$CLEANED_MEMBERS_ARRAY[] = $member_id;
				}
			}
		}
		
		/**
		 * Permission checking for member access.
		 */
		// Used for writing specific member permissions.
		$specificMembers = false;
		if ((isset($_POST["allow_member_read"])) && (clean_input($_POST["allow_member_read"], array("int")) == 1)) {
			$PROCESSED["allow_member_read"]	= 1;
			$specificMembers = true;
		} else {
			$PROCESSED["allow_member_read"]	= 0;
		}
		if ((isset($_POST["allow_member_vote"])) && (clean_input($_POST["allow_member_vote"], array("int")) == 1)) {
			$PROCESSED["allow_member_vote"]	= 1;
			$specificMembers = true;
		} else {
			$PROCESSED["allow_member_vote"]	= 0;
		}
		if ((isset($_POST["allow_member_results"])) && (clean_input($_POST["allow_member_results"], array("int")) == 1)) {
			$PROCESSED["allow_member_results"]	= 1;
			$specificMembers = true;
		} else {
			$PROCESSED["allow_member_results"]	= 0;
		}
		if ((isset($_POST["allow_member_results_after"])) && (clean_input($_POST["allow_member_results_after"], array("int")) == 1)) {
			$PROCESSED["allow_member_results_after"]	= 1;
			$specificMembers = true;
		} else {
			$PROCESSED["allow_member_results_after"]	= 0;
		}
		
		/**
		 * Permission checking for troll access.
		 * This can only be done if the community_registration is set to "Open Community"
		 */
		if (!(int) $community_details["community_registration"]) {
			if ((isset($_POST["allow_troll_read"])) && (clean_input($_POST["allow_troll_read"], array("int")) == 1)) {
				$PROCESSED["allow_troll_read"]	= 1;
			} else {
				$PROCESSED["allow_troll_read"]	= 0;
			}
			if ((isset($_POST["allow_troll_vote"])) && (clean_input($_POST["allow_troll_vote"], array("int")) == 1)) {
				$PROCESSED["allow_troll_vote"]	= 1;
			} else {
				$PROCESSED["allow_troll_vote"]	= 0;
			}
			if ((isset($_POST["allow_troll_results"])) && (clean_input($_POST["allow_troll_results"], array("int")) == 1)) {
				$PROCESSED["allow_troll_results"]	= 1;
			} else {
				$PROCESSED["allow_troll_results"]	= 0;
			}
			if ((isset($_POST["allow_troll_results_after"])) && (clean_input($_POST["allow_troll_results_after"], array("int")) == 1)) {
				$PROCESSED["allow_troll_results_after"]	= 1;
			} else {
				$PROCESSED["allow_troll_results_after"]	= 0;
			}
		} else {
			$PROCESSED["allow_troll_read"]			= 0;
			$PROCESSED["allow_troll_vote"]			= 0;
			$PROCESSED["allow_troll_results"]		= 0;
			$PROCESSED["allow_troll_results_after"]	= 0;
		}

		/**
		 * Permission checking for public access.
		 * This can only be done if the community_protected is set to "Public Community"
		 */
		if (!(int) $community_details["community_protected"]) {
			if ((isset($_POST["allow_public_read"])) && (clean_input($_POST["allow_public_read"], array("int")) == 1)) {
				$PROCESSED["allow_public_read"]	= 1;
			} else {
				$PROCESSED["allow_public_read"]	= 0;
			}
			$PROCESSED["allow_public_vote"]				= 0;
			$PROCESSED["allow_public_results"]			= 0;
			$PROCESSED["allow_public_results_after"]	= 0;
		} else {
			$PROCESSED["allow_public_read"]				= 0;
			$PROCESSED["allow_public_vote"]				= 0;
			$PROCESSED["allow_public_results"]			= 0;
			$PROCESSED["allow_public_results_after"]	= 0;
		}

		/**
		 * Required field "release_from" / Release Start (validated through validate_calendars function).
		 * Non-required field "release_until" / Release Finish (validated through validate_calendars function).
		 */
		$release_dates = validate_calendars("release", true, false);
		if ((isset($release_dates["start"])) && ((int) $release_dates["start"])) {
			$PROCESSED["release_date"]	= (int) $release_dates["start"];
		} else {
			$ERROR++;
			$ERRORSTR[] = "The <strong>Release Start</strong> field is required.";
		}
		if ((isset($release_dates["finish"])) && ((int) $release_dates["finish"])) {
			$PROCESSED["release_until"]	= (int) $release_dates["finish"];
		} else {
			$PROCESSED["release_until"]	= 0;
		}

		if (!$ERROR) {
			$PROCESSED["community_id"]			= $COMMUNITY_ID;
			$PROCESSED["proxy_id"]				= $ENTRADA_USER->getID();
			$PROCESSED["poll_active"]			= 1;
			$PROCESSED["question_active"]		= 1;
			$PROCESSED["poll_order"]			= 0;
			$PROCESSED["updated_date"]			= time();
			$PROCESSED["updated_by"]			= $ENTRADA_USER->getID();
			$PROCESSED["cpage_id"]				= $PAGE_ID;
			$PROCESSED["poll_terminology"]		= $terminology;
			
			// Use $databaseResponses when inserting into community_polls_responses

			if ($db->AutoExecute("community_polls", $PROCESSED, "INSERT")) {
				if ($PROCESSED["cpolls_id"] = $db->Insert_Id()) {
					$POLL_ID = $PROCESSED["cpolls_id"];
					if ($db->AutoExecute("community_polls_questions", $PROCESSED, "INSERT")) {
						if ($QUESTION_ID = $db->Insert_Id()) {
							// Insert the possible responses now
							$RESPONSES = array();
							$RESPONSES["cpolls_id"] 	= $PROCESSED["cpolls_id"];
							$RESPONSES["cpquestion_id"] = $QUESTION_ID;
							
							foreach($poll_responses as $respKey => $respValue)
							{
								$SUCCESS = FALSE;
								$RESPONSES["response"] 				= $respValue;
								$RESPONSES["response_index"] 		= $respKey + 1;
								$RESPONSES["updated_date"]			= time();
								$RESPONSES["updated_by"]			= $ENTRADA_USER->getID();
								if ($db->AutoExecute("community_polls_responses", $RESPONSES, "INSERT")) {
									$SUCCESS = TRUE;
								}
							}
							
							if (!$SUCCESS) {
								$ERROR++;
								$ERRORSTR[] = "There was a problem inserting the responses for this <strong>".$terminology."</strong> into the system. The MEdTech Unit was informed of this error; please try again later.";
				
								application_log("error", "There was an error inserting the responses to a question (ID: ".$QUESTION_ID."). Database said: ".$db->ErrorMsg());
							}
							
							if ($specificMembers && isset($CLEANED_MEMBERS_ARRAY) && count($CLEANED_MEMBERS_ARRAY)) {
								$MEMBERS["cpolls_id"] 				= $PROCESSED["cpolls_id"];
								$MEMBERS["updated_date"]			= time();
								$MEMBERS["updated_by"]				= $ENTRADA_USER->getID();
								
								foreach($CLEANED_MEMBERS_ARRAY as $memberKey => $memberValue)
								{
									$SUCCESS = FALSE;
									
									$MEMBERS["proxy_id"] 			= $memberValue;
									if ($db->AutoExecute("community_polls_access", $MEMBERS, "INSERT")) {
										$SUCCESS = TRUE;
									}
								}
								
								if (!$SUCCESS) {
									$ERROR++;
									$ERRORSTR[] = "There was a problem inserting the specific member permissions for this ".$terminology." into the system. The MEdTech Unit was informed of this error; please try again later.";
					
									application_log("error", "There was an error inserting the specific member permissions to a poll (ID: ".$POLL_ID."). Database said: ".$db->ErrorMsg());
								}
							}
							
							if (!$SUCCESS) {
								$ERROR++;
								$ERRORSTR[] = "There was a problem inserting the responses for this question into the system. The MEdTech Unit was informed of this error; please try again later.";
				
								application_log("error", "There was an error inserting the responses to a question (ID: ".$QUESTION_ID."). Database said: ".$db->ErrorMsg());
							}
							
							$SUCCESS++;
							if (isset($_POST["poll_action"])) {
								if ($_POST["poll_action"] == "add-poll") {
									$url = COMMUNITY_URL . $COMMUNITY_URL . ":" . $PAGE_URL . "?section=add-poll&term=" . $terminology;
								} elseif ($_POST["poll_action"] == "add-question") {
									$url = COMMUNITY_URL . $COMMUNITY_URL . ":" . $PAGE_URL . "?section=add-question&id=" . $POLL_ID;
								} elseif ($_POST["poll_action"] == "edit-poll") {
									$url = COMMUNITY_URL . $COMMUNITY_URL . ":" . $PAGE_URL . "?section=edit-poll&id=" . $POLL_ID;
								} else {
									$url = COMMUNITY_URL . $COMMUNITY_URL . ":" . $PAGE_URL . "?term=" . $terminology;
								}
								Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("You have successfully added a new <strong>%s</strong> to the community."), $terminology), "success", $MODULE);
							} else {
								$url = COMMUNITY_URL . $COMMUNITY_URL . ":" . $PAGE_URL . "?term=" . $terminology;
								Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("You have successfully added a new <strong>%s</strong> to the community."), $terminology), "success", $MODULE);
							}
							add_statistic("community_polling", "poll_add", "cpolls_id", $POLL_ID);
							communities_log_history($COMMUNITY_ID, $PAGE_ID, $POLL_ID, "community_history_add_poll", 1);
							if (COMMUNITY_NOTIFICATIONS_ACTIVE && isset($_POST["notify_members"]) && $_POST["notify_members"]) {
								community_notify($COMMUNITY_ID, $POLL_ID, "poll", COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=vote-poll&id=".$POLL_ID, $COMMUNITY_ID, $PROCESSED["release_date"]);
							}
							header("Location: " . $url);
							exit;
						}
					}
				}
			}

			if (!$SUCCESS) {
				$ERROR++;
				$ERRORSTR[] = "There was a problem inserting this ".$terminology." into the system. The MEdTech Unit was informed of this error; please try again later.";

				application_log("error", "There was an error inserting a poll. Database said: ".$db->ErrorMsg());
			}
		}

		if ($ERROR) {
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
	case 1 :
	default :
		if ($ERROR) {
			echo display_error();
		}
		if ($NOTICE) {
			echo display_notice();
		}
		$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/selectchained.js\"></script>\n";
		$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/picklist.js\"></script>\n";
		$ONLOAD[] = 'Sortable.create(\'poll_responses\', {onUpdate: updateDatabase})';
		$ONLOAD[] = "$('itemListOrder').value = Sortable.sequence('poll_responses')";
		$results_js = "
					<script type=\"text/javascript\">
						var results = new Array(".count($poll_responses).");";
		if (isset($poll_responses) && is_array($poll_responses)) {
			foreach ($poll_responses as $index => $response) {
				$results_js .= "
						results[".$index."] = '".$response."';";
			}
		}
		$results_js .= "
					</script>";
		$HEAD[] = $results_js;
		$MEMBER_LIST = array();
		$query		= "
					SELECT b.`firstname`, b.`lastname`, b.`id`
					FROM `community_members` AS a, 
					`".AUTH_DATABASE."`.`user_data` AS b,
					`communities` AS c
					WHERE a.`proxy_id` = b.`id`
					AND a.`member_active` = '1'
					AND a.`community_id` = ".$db->qstr($COMMUNITY_ID)."
					AND a.`community_id` = c.`community_id`
					ORDER BY b.`lastname` ASC, b.`firstname` ASC";
		$results	= $db->GetAll($query);
		if ($results) {
			foreach($results as $key => $result) {
				$MEMBER_LIST[(int) $result["id"]] = array("lastname" => $result["lastname"], "firstname" => $result["firstname"]);
			}
		}
		?>
		<form style="width: 95%" action="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=add-poll&amp;step=2" method="post" onsubmit="selIt()">
			<table summary="Add <?php echo $terminology; ?>">
				<colgroup>
					<col style="width: 20%" />
					<col style="width: 80%" />
				</colgroup>
				<tfoot>
					<tr>
						<td colspan="2" style="padding-top: 15px; text-align: right;">
							<div style="position: absolute;">
								<span class="content-small" style="padding-right: 10px;">After Saving:</span>
								<select id="poll_action" name="poll_action">
									<option value="edit-poll">Edit This <?php echo $terminology; ?></option>
									<option value="add-question">Add Another Question</option>
									<option value="add-poll">Create A New <?php echo $terminology; ?></option>
									<option value="index">Return To Polls Index</option>
								</select>
							</div>
							<input type="submit" class="btn btn-primary" value="Proceed" />
						</td>
					</tr>
				</tfoot>
				<tbody>
					<tr>
						<td colspan="2"><h2><?php echo $terminology; ?> Details</h2></td>
					</tr>
					<tr>
						<td><label for="poll_title" class="form-required">Title</label></td>
						<td>
							<input type="text" id="poll_title" name="poll_title" value="<?php echo ((isset($PROCESSED["poll_title"])) ? html_encode($PROCESSED["poll_title"]) : ""); ?>" maxlength="64" style="width: 300px" />
						</td>
					</tr>
					<tr>
						<td><label for="poll_description" class="form-nrequired">Description</label></td>
						<td>
							<textarea id="poll_description" name="poll_description" style="width: 98%; height: 60px; resize: vertical" cols="50" rows="5"><?php echo ((isset($PROCESSED["poll_description"])) ? html_encode($PROCESSED["poll_description"]) : ""); ?></textarea>
						</td>
					</tr>
					<tr>
						<td style="vertical-align:middle;">
							<label for="allow_multiple" class="form-nrequired">Allow Multiple Votes</label>
						</td>
						<td colspan="2">
							<table class="table table-bordered no-thead" style="margin-bottom: 0;">
								<colgroup>
									<col style="width: 5%" />
									<col style="width: auto" />
								</colgroup>
								<?php 
									if (isset($PROCESSED["allow_multiple"]) && $PROCESSED["allow_multiple"] == "1")
									{
										$yesChecked = " checked=\"checked\"";
										$noChecked 	= "";
										$display	= "inline";
									}
									else 
									{
										$yesChecked	= "";
										$noChecked 	= " checked=\"checked\"";
										$display	= "none";
									}
								 ?>
								<tr>
								 	<td class="center">
								 		<input type="radio" name="allow_multiple" id="allow_multiple_0" value="0"<?php echo $noChecked; ?> onclick="showHide(this.value);" style="vertical-align: middle" />
								 	</td>
								 	<td>
								 		<label for="allow_multiple_0" class="form-nrequired" style="vertical-align: middle">No</label>
								 	</td>
								</tr>
								<tr>
								 	<td class="center">
								 		<input type="radio" name="allow_multiple" id="allow_multiple_1" value="1"<?php echo $yesChecked; ?> onclick="showHide(this.value);" style="vertical-align: middle" />
								 	</td>
								 	<td>
								 		<label for="allow_multiple_1" class="form-nrequired" style="vertical-align: middle">Yes</label>
								 	</td>
								</tr>
							</table>
							<input type="text" name="number_of_votes" id="number_of_votes" size="3" value="<?php echo (!isset($PROCESSED["number_of_votes"]) ? 0 : $PROCESSED["number_of_votes"]); ?>" style="display: <?php echo $display; ?>; vertical-align: middle; margin-top: 15px !important; width: 300px" />
							<span id="multiple_note" class="content-small" style="display: <?php echo $display; ?>; vertical-align: middle; margin-left:10px;">
								<strong>Note:</strong> Set to 0 for unlimited.
							</span>
						</td>
					</tr>
					<tr>
						<td colspan="2"><h2>Question Details</h2></td>
					</tr>
					<tr>
						<td><label for="poll_question" class="form-required">Question</label></td>
						<td>
							<input type="text" id="poll_question" name="poll_question" value="<?php echo ((isset($PROCESSED["poll_question"])) ? html_encode($PROCESSED["poll_question"]) : ""); ?>" maxlength="64" style="width: 300px" />
						</td>
					</tr>
					<tr>
						<td>
							<label for="multiple_responses" class="form-nrequired">Multiple Responses</label>
						</td>
						<td colspan="2">
							<table class="table table-bordered no-thead" style="margin-bottom: 0;">
								<colgroup>
									<col style="width: 5%" />
									<col style="width: auto" />
								</colgroup>
								<tr>
									<td class="center">
										<input type="checkbox" id="multiple_responses" name="multiple_responses" value="1" onclick="javascript: Effect.toggle($('responses_range'), 'Appear', {duration:0.3});"<?php echo (((int) $PROCESSED["maximum_responses"] > 1) ? " checked=\"checked\"" : "" ); ?> />
									</td>
									<td>
										<label class="form-nrequired" style="vertical-align: middle">Select this option to allow users to choose more than one response to this question.</label>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td style="width: 90%;<?php echo (((int) $PROCESSED["maximum_responses"] > 1) ? "" : " display: none;"); ?> float: left;" id="responses_range">
							<input type="text" id="min_responses" name="min_responses" maxlength="2" style="width: 10%;" value="<?php echo ((int) $PROCESSED["minimum_responses"] ? (int) $PROCESSED["minimum_responses"] : 1 ); ?>"/>&nbsp; To &nbsp;<input type="text" id="max_responses" name="max_responses" maxlength="2" style="width: 10%;" value="<?php echo ((int) $PROCESSED["maximum_responses"] ? (int) $PROCESSED["maximum_responses"] : 1 ); ?>"/>&nbsp; Responses Allowed.
						</td>
					</tr>
					<tr>
						<td style="vertical-align:top;">
							<label for="poll_responses" class="form-required" style="margin-top:10px;">Responses</label>
					  	</td>
						<td>
							<input type="text" style="width: 300px;" id="rowText" name="rowText" value="" maxlength="255" />
							<a class="btn btn-primary" style="height:20px; margin-top: -5px; margin-left: 5px;" onclick="addItem();"><i class="icon-plus icon-white" style="margin-top:3px;"></i></a>
							<script type="text/javascript" >
								$('rowText').observe('keypress', function(event){
								    if(event.keyCode == Event.KEY_RETURN) {
								        addItem();
								        Event.stop(event);
								    }
								});
							</script>
							<ul id="poll_responses" class="sortable-list" style="margin:10px 0 0 0; text-align:left;">
							<?php
								if (isset($poll_responses) && count($poll_responses) != 0)
								{
									foreach($poll_responses as $key => $value)
									{
										echo "<li id=\"poll_responses_".$key."\"><div style=\"float:left; text-align: left; width: auto;\" >".$value."</div><div style=\"float:right; text-align: right;\"><a class=\"btn btn-danger\" style=\"height:20px;\" onclick=\"removeItem(".$key.");\"><i class=\"icon-trash icon-white\" style=\"margin-top:3px;\"></i></a></div></li>";
									}
									$display = "block";
								}
								else 
								{
									$display = "none";
								}
							?>
							</ul>
		   					<div id="note" class="content-small" style="clear: both; padding-top: 15px;"><strong>Please Note:</strong> You can reorder responses by dragging and dropping the response.</div>
							<input type="hidden" id="itemCount" name="itemCount" value="<?php echo (isset($_POST['itemCount']) && $_POST['itemCount'] != "0" ? html_encode($_POST['itemCount']) : "0"); ?>" />
							<div id="pollResponses">
							<?php echo poll_responses_in_form($poll_responses); ?>
							</div>
							<input type="hidden" id="itemListOrder" name="itemListOrder" />
						</td>
					</tr>
					<tr>
						<td colspan="2"><h2><?php echo $terminology; ?> Permissions</h2></td>
					</tr>
					<tr>
						<td colspan="2">
							<table class="table table-striped table-bordered">
							<colgroup>
								<col style="width: 35%" />
								<col style="width: 15%" />
								<col style="width: 15%" />
								<col style="width: 15%" />
								<col style="width: 20%" />
							</colgroup>
							<thead>
								<tr>
									<td>Group</td>
									<td>View</td>
									<td>Vote</td>
									<td>View Results</td>
									<td>Post-Vote Results</td>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td><strong>Community Administrators</strong></td>
									<td class="on"><input type="checkbox" id="allow_admin_read" name="allow_admin_read" value="1" checked="checked" onclick="this.checked = true" /></td>
									<td><input type="checkbox" id="allow_admin_vote" name="allow_admin_vote" value="1" checked="checked" onclick="this.checked = true" /></td>
									<td><input type="checkbox" id="allow_admin_results" name="allow_admin_results" value="1" checked="checked" onclick="this.checked = true" /></td>
									<td><input type="checkbox" id="allow_admin_results_after" name="allow_admin_results_after" value="1" disabled="true" /></td>
								</tr>
								<tr>
									<td><strong>Community Members</strong></td>
									<td class="on"><input type="checkbox" id="allow_member_read" name="allow_member_read" value="1"<?php echo (((!isset($PROCESSED["allow_member_read"])) || ((isset($PROCESSED["allow_member_read"])) && ($PROCESSED["allow_member_read"] == 1))) ? " checked=\"checked\"" : ""); ?>onclick="showHideMembers()" /></td>
									<td><input type="checkbox" id="allow_member_vote" name="allow_member_vote" value="1"<?php echo (((!isset($PROCESSED["allow_member_vote"])) || ((isset($PROCESSED["allow_member_vote"])) && ($PROCESSED["allow_member_vote"] == 1))) ? " checked=\"checked\"" : ""); ?>onclick="showHideMembers(); setUnsetResults();" /></td>
									<td><input type="checkbox" id="allow_member_results" name="allow_member_results" value="1"<?php echo ((((isset($PROCESSED["allow_member_results"])) && ($PROCESSED["allow_member_results"] == 1))) ? " checked=\"checked\"" : ""); ?>onclick="showHideMembers(); setUnsetResults();" /></td>
									<td><input type="checkbox" id="allow_member_results_after" name="allow_member_results_after" value="1"<?php echo (((!isset($PROCESSED["allow_member_results_after"])) || ((isset($PROCESSED["allow_member_results_after"])) && ($PROCESSED["allow_member_results_after"] == 1))) ? " checked=\"checked\"" : ""); ?>onclick="showHideMembers()" /></td>
								</tr>
								<?php if (!(int) $community_details["community_registration"]) :  ?>
								<tr>
									<td><strong>Browsing Non-Members</strong></td>
									<td class="on"><input type="checkbox" id="allow_troll_read" name="allow_troll_read" value="1"<?php echo (((!isset($PROCESSED["allow_troll_read"])) || ((isset($PROCESSED["allow_troll_read"])) && ($PROCESSED["allow_troll_read"] == 1))) ? " checked=\"checked\"" : ""); ?> /></td>
									<td><input type="checkbox" id="allow_troll_vote" name="allow_troll_vote" value="1"<?php echo (((isset($PROCESSED["allow_troll_vote"])) && ($PROCESSED["allow_troll_vote"] == 1)) ? " checked=\"checked\"" : ""); ?> /></td>
									<td><input type="checkbox" id="allow_troll_results" name="allow_troll_results" value="1"<?php echo (((isset($PROCESSED["allow_troll_results"])) && ($PROCESSED["allow_troll_results"] == 1)) ? " checked=\"checked\"" : ""); ?> /></td>
									<td><input type="checkbox" id="allow_troll_results_after" name="allow_troll_results_after" value="1"<?php echo (((isset($PROCESSED["allow_troll_results_after"])) && ($PROCESSED["allow_troll_results_after"] == 1)) ? " checked=\"checked\"" : ""); ?> /></td>
								</tr>
								<?php endif; ?>
								<?php if (!(int) $community_details["community_protected"]) :  ?>
								<tr>
									<td><strong>Non-Authenticated / Public Users</strong></td>
									<td class="on"><input type="checkbox" id="allow_public_read" name="allow_public_read" value="1"<?php echo (((isset($PROCESSED["allow_public_read"])) && ($PROCESSED["allow_public_read"] == 1)) ? " checked=\"checked\"" : ""); ?> /></td>
									<td><input type="checkbox" id="allow_public_vote" name="allow_public_vote" value="0" onclick="noPublic(this)" /></td>
									<td><input type="checkbox" id="allow_public_results" name="allow_public_results" value="0" onclick="noPublic(this)" /></td>
									<td><input type="checkbox" id="allow_public_results_after" name="allow_public_results_after" value="0" onclick="noPublic(this)" /></td>
								</tr>
								<?php endif; ?>
							</tbody>
							</table>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<table class="table table-bordered no-thead space-above space-below">
								<colgroup>
									<col style="width: 5%" />
									<col style="width: auto" />
								</colgroup>
								<tr id="all_members">
									<td class="center">
										<input name="all_members_vote" id="all_members_vote" type="radio" value="1" <?php echo (!(isset($CLEANED_MEMBERS_ARRAY) && (is_array($CLEANED_MEMBERS_ARRAY)) && (count($CLEANED_MEMBERS_ARRAY))) ? "checked=\"checked\" " : ""); ?> onclick="showHideMembers()" />
									</td>
									<td>
										<label for="all_members_vote" class="form-nrequired">Allow all members to vote</label>
									</td>
								</tr>
								<tr id="specific_members">
									<td class="center">
										<input id="specific_members_vote" name="all_members_vote" type="radio" value="0" <?php echo (isset($CLEANED_MEMBERS_ARRAY) && (is_array($CLEANED_MEMBERS_ARRAY)) && (count($CLEANED_MEMBERS_ARRAY)) ? "checked=\"checked\" " : ""); ?> onclick="showHideMembers()" />
									</td>
									<td>
										<label for="specific_members_vote" class="form-nrequired">Select specific members to vote</label>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td colspan="2" width="100%">
							<div id="members-list" <?php echo (!(isset($CLEANED_MEMBERS_ARRAY) && (is_array($CLEANED_MEMBERS_ARRAY)) && (count($CLEANED_MEMBERS_ARRAY))) ? "style=\"display: none;\"" : "") ?>>
								<div id="members_note" class="content-small" style="padding-top: 15px;">
									<strong>Please Note:</strong> If you would like to restrict voting to only certain community members please add these members to the &quot;Selected Members&quot; column below.
								</div>
								<table summary="Add Member">
									<colgroup>
									<col style="width: 300px" />
									<col style="width: auto" />
								</colgroup>
									<tbody>	
										<tr>
											<td colspan="2">
												<h3>Members to be Added</h3>
											</td>
										</tr>	
										<tr>
											<td style="vertical-align: top">
												<div class="member-add-type" id="existing-member-add-type">
													<?php
													$nmembers_query			= "";
													$nmembers_results		= false;
													$nmembers_query	= "	SELECT a.`id` AS `proxy_id`, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, a.`username`, a.`organisation_id`, b.`group`, b.`role`
																		FROM `".AUTH_DATABASE."`.`user_data` AS a
																		LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
																		ON a.`id` = b.`user_id`
																		LEFT JOIN `community_members` AS c
																		ON a.`id` = c.`proxy_id`
																		WHERE b.`app_id` IN (".AUTH_APP_IDS_STRING.")
																		AND b.`account_active` = 'true'
																		AND (b.`access_starts` = '0' OR b.`access_starts` <= ".$db->qstr(time()).")
																		AND (b.`access_expires` = '0' OR b.`access_expires` > ".$db->qstr(time()).")
																		AND c.`community_id` = ".$db->qstr($COMMUNITY_ID)."
																		GROUP BY a.`id`
																		ORDER BY a.`lastname` ASC, a.`firstname` ASC";
													//Fetch list of categories
													$query	= "SELECT `organisation_id`,`organisation_title` FROM `".AUTH_DATABASE."`.`organisations` ORDER BY `organisation_title` ASC";
													$organisation_results	= $db->GetAll($query);
													if($organisation_results) {
														$organisations = array();
														foreach($organisation_results as $result) {
															if($ENTRADA_ACL->amIAllowed('resourceorganisation'.$result["organisation_id"], 'create')) {
																$member_categories[$result["organisation_id"]] = array('text' => $result["organisation_title"], 'value' => 'organisation_'.$result["organisation_id"], 'category'=>true);
															}
														}
													}

													$current_member_list	= array();
													$query		= "SELECT `proxy_id` FROM `community_members` WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `member_active` = '1'";
													$results	= $db->GetAll($query);
													if($results) {
														foreach($results as $result) {
															if($proxy_id = (int) $result["proxy_id"]) {
																$current_member_list[] = $proxy_id;
															}
														}
													}

													if($nmembers_query != "") {
														$nmembers_results = $db->GetAll($nmembers_query);
														if($nmembers_results) {
															$members = $member_categories;

															foreach($nmembers_results as $member) {

																$organisation_id = $member['organisation_id'];
																$group = $member['group'];
																$role = $member['role'];

																if($group == "student" && !isset($members[$organisation_id]['options'][$group.$role])) {
																	$members[$organisation_id]['options'][$group.$role] = array('text' => $group. ' > '.$role, 'value' => $organisation_id.'|'.$group.'|'.$role);
																} elseif ($group != "guest" && $group != "student" && !isset($members[$organisation_id]['options'][$group."all"])) {
																	$members[$organisation_id]['options'][$group."all"] = array('text' => $group. ' > all', 'value' => $organisation_id.'|'.$group.'|all');
																}
															}

															foreach($members as $key => $member) {
																if(isset($member['options']) && is_array($member['options']) && !empty($member['options'])) {
																	sort($members[$key]['options']);
																}
															}

															echo lp_multiple_select_inline('community_members', $members, array(
															'width'	=>'100%',
															'ajax'=>true,
															'selectboxname'=>'group and role',
															'default-option'=>'-- Select Group & Role --',
															'category_check_all'=>true));
														} else {
															echo "No One Available [1]";
														}
													} else {
														echo "No One Available [2]";
													}
													?>
					
													<input class="multi-picklist" id="community_members" name="community_members" style="display: none;">
												</div>
											</td>
											<td style="vertical-align: top; padding-left: 20px;">
												<input id="acc_community_members" style="display: none;" name="acc_community_members"/>
												<div id="community_members_list"></div>
											</td>
									</tbody>
								</table>
							</div>
						</td>
					</tr>
					<?php
					if (COMMUNITY_NOTIFICATIONS_ACTIVE) {
						?>
						<tr>
							<td colspan="2">
								<table class="table table-bordered no-thead">
									<colgroup>
										<col style="width: 5%" />
										<col style="width: auto" />
									</colgroup>
									<tr>
										<td class="center">
											<input type="checkbox" name="notify_members" id="notify_members" />
										</td>
										<td>
											<label for="notify_members" class="form-nrequired">Notify Community Members of <?php echo $terminology; ?></label>
										</td>
									</tr>
								</table>
							</td>
						</tr>
						<?php
					}
					?>
					<tr>
						<td colspan="2"><h2>Time Release Options</h2></td>
					</tr>
					<tr>
						<td colspan="2">
							<table class="date-time">
								<?php echo generate_calendars("release", "", true, true, ((isset($PROCESSED["release_date"])) ? $PROCESSED["release_date"] : time()), true, false, ((isset($PROCESSED["release_until"])) ? $PROCESSED["release_until"] : 0)); ?>
							</table>
						</td>
					</tr>
				</tbody>
			</table>
			<input type="hidden" id="term" name="term" value="<?php echo clean_input($terminology, "lower"); ?>" />
		</form>
		<script type="text/javascript">
			var people = [[]];
			var ids = [[]];
			//Updates the People Being Added div with all the options
			function updatePeopleList(newoptions, index) {
				people[index] = newoptions;
				table = people.flatten().inject(new Element('table', {'class':'member-list'}), function(table, option, i) {
					if(i%1 == 0) {
						row = new Element('tr');
						table.appendChild(row);
					}
					row.appendChild(new Element('td').update(option));
					return table;
				});
				$('community_members_list').update(table);
				ids[index] = $F('community_members').split(',').compact();
				$('acc_community_members').value = ids.flatten().join(',');
			}
		
		
			$('community_members_select_filter').observe('keypress', function(event){
			    if(event.keyCode == Event.KEY_RETURN) {
			        Event.stop(event);
			    }
			});
		
			//Reload the multiselect every time the category select box changes
			var multiselect;
		
			$('community_members_category_select').observe('change', function(event) {
				if ($('community_members_category_select').selectedIndex != 0) {
					$('community_members_scroll').update(new Element('div', {'style':'width: 100%; height: 100%; background: transparent url(<?php echo ENTRADA_URL;?>/images/loading.gif) no-repeat center'}));
			
					//Grab the new contents
					var updater = new Ajax.Updater('community_members_scroll', '<?php echo ENTRADA_URL."/communities?section=membersapi&action=memberlist&type=polls";?>',{
						method:'post',
						parameters: {
							'ogr':$F('community_members_category_select'),
							'community_id':'<?php echo $COMMUNITY_ID;?>'
						},
						onSuccess: function(transport) {
							//onSuccess fires before the update actually takes place, so just set a flag for onComplete, which takes place after the update happens
							this.makemultiselect = true;
						},
						onFailure: function(transport){
							$('community_members_scroll').update(new Element('div', {'class':'display-error'}).update('There was a problem communicating with the server. An administrator has been notified, please try again later.'));
						},
						onComplete: function(transport) {
							//Only if successful (the flag set above), regenerate the multiselect based on the new options
							if(this.makemultiselect) {
								if(multiselect) {
									multiselect.destroy();
								}
								multiselect = new Control.SelectMultiple('community_members','community_members_options',{
									labelSeparator: '; ',
									checkboxSelector: 'table.select_multiple_table tr td.select_multiple_checkbox input[type=checkbox]',
									categoryCheckboxSelector: 'table.select_multiple_table tr td.select_multiple_checkbox_category input[type=checkbox]',
									nameSelector: 'table.select_multiple_table tr td.select_multiple_name label',
									overflowLength: 70,
									filter: 'community_members_select_filter',
									afterCheck: function(element) {
										var tr = $(element.parentNode.parentNode);
										tr.removeClassName('selected');
										if(element.checked) {
											tr.addClassName('selected');
										}
									},
									updateDiv: function(options, isnew) {
										updatePeopleList(options, $('community_members_category_select').selectedIndex);
									}
								});
							}
						}
					});
				}
			});
		</script>
		<?php
	break;
}
?>
