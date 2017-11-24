<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to add new questions to a particular poll. This action is available
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



if ($RECORD_ID) {

	$HEAD[] = "<script type=\"text/javascript\" src=\"".COMMUNITY_URL."/javascript/polls.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
	
	
	$BREADCRUMB[] = array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=add-question&id=".$RECORD_ID, "title" => "Add Question");

	$terminology = $db->GetOne("SELECT `poll_terminology` FROM `community_polls` WHERE `cpolls_id` = ".$RECORD_ID);

	Entrada_Utilities_Flashmessenger::displayMessages($MODULE);

	echo "<h1>Add Question</h1>\n";
	
	// Error Checking
	switch($STEP) {
		case 2 :
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
				if ($min > count($PROCESSED["poll_responses"]) || $min < 1) {
					$ERROR++;
					$ERRORSTR[] = "The minimum number of responses for this question must be between 1 and the total number of responses minus 1, inclusively.";
				} elseif ($max > count($PROCESSED["poll_responses"]) || $max < 1 || $max < $min) {
					$ERROR++;
					$ERRORSTR[] = "The maximum number of responses for this question must be between the minimum and the number of responses, inclusively.";
				} else {
					$PROCESSED["maximum_responses"] = $max;
					$PROCESSED["minimum_responses"] =  $min;
				}
			}
	
			if (!$ERROR) {
				$PROCESSED["community_id"]			= $COMMUNITY_ID;
				$PROCESSED["proxy_id"]				= $ENTRADA_USER->getID();
				$PROCESSED["updated_date"]			= time();
				$PROCESSED["updated_by"]			= $ENTRADA_USER->getID();
				$PROCESSED["cpage_id"]				= $PAGE_ID;
				$PROCESSED["cpolls_id"]				= $RECORD_ID;
				$PROCESSED["question_order"]		= ((int)$db->GetOne("SELECT MAX(`question_order`) FROM `community_polls_questions` WHERE `cpolls_id` = ".$db->qstr($RECORD_ID)) + 1);
				$PROCESSED["question_active"]		= 1;
				
				// Use $databaseResponses when inserting into community_polls_responses
	
				if ($db->AutoExecute("community_polls_questions", $PROCESSED, "INSERT")) {
					if ($QUESTION_ID = $db->Insert_Id()) {
						// Insert the possible responses now
						$RESPONSES = array();
						$RESPONSES["cpolls_id"] 	= $PROCESSED["cpolls_id"];
						$RESPONSES["cpquestion_id"] = $QUESTION_ID;
						
						foreach($poll_responses as $respKey => $respValue) {
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
							$ERRORSTR[] = "There was a problem inserting the responses for this question into the system. The MEdTech Unit was informed of this error; please try again later.";
			
							application_log("error", "There was an error inserting the responses to a question (ID: ".$QUESTION_ID."). Database said: ".$db->ErrorMsg());
						} else {
                            if (isset($_POST["poll_action"])) {
                                if ($_POST["poll_action"] == "edit-poll") {
                                    $url = COMMUNITY_URL . $COMMUNITY_URL . ":" . $PAGE_URL . "?section=edit-poll&id=" . $RECORD_ID;
                                } elseif ($_POST["poll_action"] == "add-question") {
                                    $url = COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=add-question&id=".$PROCESSED["cpolls_id"];
                                } else {
                                    $url = COMMUNITY_URL . $COMMUNITY_URL . ":" . $PAGE_URL . "?confmsg=questionadd&term=" . $terminology;
                                }
                            } else {
                                $url = COMMUNITY_URL . $COMMUNITY_URL . ":" . $PAGE_URL . "?confmsg=questionadd&term=" . $terminology;
                            }
                            Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("You have successfully added a new question to the <strong>%s</strong>."), $terminology), "success", $MODULE);
                            add_statistic("community_polling", "question_add", "cpquestion_id", $QUESTION_ID);
                            communities_log_history($COMMUNITY_ID, $PAGE_ID, $PROCESSED["cpolls_id"], "community_history_edit_poll", 0);
                            header("Location: " . $url);
                            exit;
                        }
					}
				} else {
					$ERROR++;
					$ERRORSTR[] = "There was a problem inserting this question into the system. The MEdTech Unit was informed of this error; please try again later.";
	
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
						AND a.`member_acl` = '0'
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
			<form action="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=add-question&amp;step=2&id=".$RECORD_ID; ?>" method="post">
			<table summary="Add Question">
			<colgroup>
				<col style="width: 20%" />
				<col style="width: 80%" />
			</colgroup>
			<tfoot>
				<tr>
					<td colspan="2" style="padding-top: 15px; text-align: right;">
						<div style="position: relative; left: 0px; width: 100%;">
							<div style="position: absolute; left: 5px;">
								<span class="content-small" style="padding-right: 10px;">After Saving:</span>
								<select id="poll_action" name="poll_action">
									<option value="add-question">Add Another Question</option>
									<option value="edit-poll">Return to Editing This Poll</option>
									<option value="index">Return To Polls Index</option>
								</select>
							</div>
						</div>
						<input type="submit" class="btn btn-primary" value="Proceed" />
					</td>
				</tr>
			</tfoot>
			<tbody>
				<tr>
					<td colspan="2"><h2>Question Details</h2></td>
				</tr>
				<tr>
					<td><label for="poll_question" class="form-required">Question</label></td>
					<td >
						<input type="text" id="poll_question" name="poll_question" value="<?php echo ((isset($PROCESSED["poll_question"])) ? html_encode($PROCESSED["poll_question"]) : ""); ?>" style="width: 300px" />
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
			</tbody>
			</table>
			</form>
			<?php
		break;
	}
} else {
	$ERROR++;
	$ERRORSTR[] = "Please provide a valid <strong>ID</strong> to proceed.";
	echo display_error();
}
?>
