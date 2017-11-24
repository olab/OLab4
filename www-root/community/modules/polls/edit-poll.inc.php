<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to edit existing polls within a community. This action is available only
 * to existing community administrators.
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
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/livepipe/livepipe.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/livepipe/window.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/livepipe/selectmultiplemod.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";

if ($RECORD_ID) {
	$query				= "SELECT * FROM `community_polls` WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `cpage_id` = ".$db->qstr($PAGE_ID)." AND `cpolls_id` = ".$db->qstr($RECORD_ID)." AND `poll_active` = '1'";
	$poll_record		= $db->GetRow($query);
	if ($poll_record) {
		$terminology		= $poll_record["poll_terminology"];

        Entrada_Utilities_Flashmessenger::displayMessages($MODULE);

		echo "<h1>Edit ".$terminology."</h1>\n";
		
		$query = "	SELECT COUNT(b.`cpresults_id`) 
					FROM `community_polls_responses` as a
					JOIN `community_polls_results` as b
					ON a.`cpresponses_id` = b.`cpresponses_id`
					WHERE a.`cpolls_id` = ".$db->qstr($poll_record["cpolls_id"]);
		
		$response_records = $db->GetOne($query);
		
		$fully_editable = (isset($response_records) && $response_records > 0 ? false : true);
		
		$BREADCRUMB[] 	= array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=edit-poll&amp;id=".$RECORD_ID, "title" => "Edit ".$terminology);
		
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
				 * Non-Required field "description" / Poll Description.
				 */
				if ((isset($_POST["poll_description"])) && ($description = clean_input($_POST["poll_description"], array("notags", "trim")))) {
					$PROCESSED["poll_description"] = $description;
				} else {
					$PROCESSED["poll_description"] = "";
				}
				
				/**
				 * Can only be edited when no votes have been cast.
				 */
				if ($fully_editable) {
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
				}
				
		/**
		 * Required field "poll_responses" / Poll Responses.
		 */
		
		$query = "	SELECT `cpquestion_id` FROM `community_polls_questions`
					WHERE `cpolls_id` = ".$db->qstr($RECORD_ID)."
					AND `question_active` = '1'
					ORDER BY `question_order` ASC";
		
		if (($questions = $db->GetAll($query))) {
			if (isset($_POST["itemListOrder"]) && ($question_keys = explode(',', clean_input($_POST["itemListOrder"], array("nows", "notags"))))) {
				$count = 1;
				foreach ($question_keys as $index) {
					$questions[$index-1]["question_order"] = $count;
					$count++;
				}
			}
			if (count($questions) < 1)
			{
				$ERROR++;
				$ERRORSTR[] = "You need to have at least one <strong>Question</strong>.";
			}
		} else {
			$ERROR++;
			$ERRORSTR[] = "You need to have at least one <strong>Question</strong>.";
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
				 * Non-required processing
				 */
				if ((isset($_POST["acc_community_members"])) && ($member_ids = explode(",", $_POST["acc_community_members"])) && (is_array($member_ids)) && (count($member_ids)) && (!isset($_POST["all_members_vote"]) || (!$_POST["all_members_vote"]))) {
					$CLEANED_MEMBERS_ARRAY = array();
					foreach($member_ids as $member_id) {
						if (($member_id = (int) $member_id) && array_search($member_id, $CLEANED_MEMBERS_ARRAY) === false) {
							$CLEANED_MEMBERS_ARRAY[] = $member_id;
						}
					}
				}
				
				/**
				 * Email Notificaions.
				 */
				if(isset($_POST["poll_notifications"])) {
					$PROCESSED["poll_notifications"] = $_POST["poll_notifications"];
				} elseif(isset($_POST["member_notify"])) {
					$PROCESSED["poll_notifications"] = $_POST["member_notify"];
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
					$PROCESSED["proxy_id"]				= $ENTRADA_USER->getActiveId();
					$PROCESSED["poll_active"]			= 1;
					$PROCESSED["poll_order"]			= 0;
//					$PROCESSED["poll_notifications"]	= 0;
					$PROCESSED["updated_date"]			= time();
					$PROCESSED["updated_by"]			= $ENTRADA_USER->getID();
					
					// Use $databaseResponses when inserting into community_polls_responses
					if ($db->AutoExecute("community_polls", $PROCESSED, "UPDATE", "`community_id` = ".$db->qstr($COMMUNITY_ID)." AND `cpage_id` = ".$db->qstr($PAGE_ID)." AND `cpolls_id` = ".$db->qstr($RECORD_ID))) 
					{
						
						if ($fully_editable) {
							foreach ($questions as $question) {
								if (!$db->AutoExecute("community_polls_questions", $question, "UPDATE", "`cpquestion_id` = ".$db->qstr($question["cpquestion_id"])." AND `question_active` = '1'")) {
									$ERROR++;
									$ERRORSTR[] = "There was a problem updating the order of the questions for this ".$terminology." into the system. The MEdTech Unit was informed of this error; please try again later.";
					
									application_log("error", "There was an error updating the order of the questions in a poll (ID: ".$RECORD_ID."). Database said: ".$db->ErrorMsg());
								}
							}
						}
						
						$query = "DELETE FROM `community_polls_access` WHERE `cpolls_id` = ".$db->qstr($RECORD_ID);
						$db->Execute($query);
						if ($PROCESSED["release_date"] != $poll_record["release_date"] && COMMUNITY_NOTIFICATIONS_ACTIVE) {
							$notification = $db->GetRow("SELECT * FROM `community_notifications` WHERE `record_id` = ".$db->qstr($RECORD_ID)." AND `type` = 'poll'");
							if ($notification) {
								$notification["release_time"] = $PROCESSED["release_date"];
								$db->AutoExecute("community_notifications", $notification, "UPDATE", "`cnotification_id` = ".$db->qstr($notification["cnotification_id"]));
							}
						}
						
						// Only insert individual users if there are any to add.
						if ($specificMembers && isset($CLEANED_MEMBERS_ARRAY) && count($CLEANED_MEMBERS_ARRAY)) {
							$MEMBERS["cpolls_id"] 				= $RECORD_ID;
							$MEMBERS["updated_date"]			= time();
							$MEMBERS["updated_by"]				= $ENTRADA_USER->getID();
							
							foreach($CLEANED_MEMBERS_ARRAY as $memberKey => $memberValue) {
								$SUCCESS = FALSE;
								
								$MEMBERS["proxy_id"] 			= $memberValue;
								if ($db->AutoExecute("community_polls_access", $MEMBERS, "INSERT")) {
									$SUCCESS = TRUE;
								}
							}
							
							if (!$SUCCESS) {
								$ERROR++;
								$ERRORSTR[] = "There was a problem inserting the specific member permissions for this ".$terminology." into the system. The MEdTech Unit was informed of this error; please try again later.";
				
								application_log("error", "There was an error inserting the specific member permissions to a poll (ID: ".$RECORD_ID."). Database said: ".$db->ErrorMsg());
							}
						}
						
						$url = COMMUNITY_URL . $COMMUNITY_URL . ":" . $PAGE_URL;

						$SUCCESS++;
                        Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("You have successfully updated <strong>%s</strong>."), $PROCESSED["poll_title"]), "success", $MODULE);

                        add_statistic("community_polling", "poll_edit", "cpolls_id", $RECORD_ID);
						communities_log_history($COMMUNITY_ID, $PAGE_ID, $RECORD_ID, "community_history_edit_poll", 0);

						header("Location: " . $url);
                        exit;
					}
		
					if (!$SUCCESS) {
						$ERROR++;
						$ERRORSTR[] = "There was a problem editing this ".$terminology." in the system. The MEdTech Unit was informed of this error; please try again later.";
		
						application_log("error", "There was an error editing a poll. Database said: ".$db->ErrorMsg());
					}
				}
		
				if ($ERROR) {
					$STEP = 1;
				}
			break;
			case 1 :
			default :
				$PROCESSED = $poll_record;
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
				$member_id_string = "";
				if ($results) {
					foreach($results as $key => $result) {
						if ($member_id_string) {
							$member_id_string .= ",".((int)$result["id"]);
						} else {
							$member_id_string = ((int)$result["id"]);
						}
						$MEMBER_LIST[(int) $result["id"]] = array("lastname" => $result["lastname"], "firstname" => $result["firstname"]);
					}
				}
				?>
				<form action="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=edit-poll&amp;id=<?php echo $RECORD_ID; ?>&amp;step=2" method="post" onsubmit="selIt()">
				<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Edit <?php echo $terminology; ?>">
				<colgroup>
					<col style="width: 20%" />
					<col style="width: 80%" />
				</colgroup>
				<tfoot>
					<tr>
						<td colspan="3" style="padding-top: 15px; text-align: right">
                            <input type="submit" class="btn btn-primary" value="<?php echo $translate->_("global_button_save"); ?>" />                     
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
							<textarea id="poll_description" name="poll_description" style="width: 98%; height: 60px" cols="50" rows="5"><?php echo ((isset($PROCESSED["poll_description"])) ? html_encode($PROCESSED["poll_description"]) : ""); ?></textarea>
						</td>
					</tr>
					<?php
					if ($fully_editable) {
						?>
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
						<?php
					}		
					?>	
					<tr>
						<td colspan="2">
							<table summary="Questions">
							<colgroup>
								<col style="width: 20%" />
								<col style="width: 80%" />
							</colgroup>
							<tr>
								<td colspan="2">
									<a name="poll_questions_section"></a><h2 id="poll_questions_section" title="Questions">Questions</h2>
								</td>
							</tr>
							<tbody id="poll-content-questions">
								<tr>
									<td colspan="2">
										<?php
										if ($fully_editable) {
											?>
											<div>
												<ul class="page-action" style="margin-left: 0;">
													<li><a href="<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=add-question&amp;id=<?php echo $RECORD_ID; ?>">Add New Question</a></li>
												</ul>
											</div>
											<?php
										}
		
										$query		= "	SELECT *
														FROM `community_polls_questions`
														WHERE `cpolls_id` = ".$db->qstr($RECORD_ID)."
														AND `question_active` = '1'
														ORDER BY `question_order` ASC";
										$questions	= $db->GetAll($query);
										if ($questions) {
											?>
											<div class="poll-questions" id="poll-content-questions-holder">
												<ol class="questions" id="poll-questions-list" style="margin-left: 0; margin-bottom: 15px;">
												<?php
												$count = 1;
												foreach ($questions as $question) {
													echo "<li id=\"question_".$count++."\" class=\"question\">";
													echo "	<div class=\"question-title".((!$fully_editable) ? " editable" : " editable")."\">\n";
		
													if ($fully_editable) {
														echo "	<div style=\"float: right\">\n";
														echo "		<a class=\"btn btn-primary\" href=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=edit-question&amp;id=".$question["cpquestion_id"]."\" style=\"height: 20px;\"><i class=\"icon-edit icon-white\" style=\"margin-top: 3px;\"></i></a>";
														echo "		<a class=\"btn btn-danger\" id=\"question_delete_".$question["cpquestion_id"]."\" class=\"question-controls-delete\" href=\"javascript:questionDelete(".$question["cpquestion_id"].")\" title=\"".$question["cpquestion_id"]."\" style=\"height: 20px;\"><i class=\"icon-trash icon-white\" style=\"margin-top: 3px;\"></i></a>";
														echo "	</div>\n";
													}
		
													echo "		<span id=\"question_text_".$question["cpquestion_id"]."\">".clean_input($question["poll_question"], "allowedtags")."</span>";
													echo "	</div>\n";
													echo "	<ul class=\"responses\">\n";
													$query		= "	SELECT a.*
																	FROM `community_polls_responses` AS a
																	WHERE a.`cpquestion_id` = ".$db->qstr($question["cpquestion_id"])."
																	ORDER BY ".(($question["randomize_responses"] == 1) ? "RAND()" : "a.`response_index` ASC");
													$responses	= $db->GetAll($query);
													if ($responses) {
														foreach ($responses as $response) {
															echo "<li>".$response["response"]."</li>\n";
														}
													}
													echo "	</ul>\n";
													echo "</li>\n";
												}
												?>
												</ol>
												<div id="note" class="content-small" style="clear: both; margin-left: 20px;"><strong>Please Note:</strong> You can reorder questions by dragging and dropping the question.</div>
											</div>
											<?php
											if ($fully_editable) {
												$ONLOAD[] = "Sortable.create('poll-questions-list', { handles : $$('#poll-questions-list div.question'), onUpdate : function () { $('itemListOrder').value = Sortable.sequence('poll-questions-list'); } })";
												$ONLOAD[] = "$('itemListOrder').value = Sortable.sequence('poll-questions-list')";
												?>
												<script type="text/javascript">
												var deleteQuestion_id = 0;
												
												function questionDelete(id) {
												Dialog.confirm('Do you really wish to remove this question from the <?php echo $terminology; ?>?<br /><br />If you confirm this action, you will be deactivating this question.',
													{
														id:				'requestDialog',
														width:			350,
														height:			125,
														title:			'Delete Confirmation',
														className:		'medtech',
														okLabel:		'Yes',
														cancelLabel:	'No',
														closable:		'true',
														buttonClass:	'btn',
														ok:				function(win) {
																			window.location = '<?php echo COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL; ?>?section=delete-question&id='+id;
																			return true;
																		}
													}
												);
											}
												</script>
												<?php
											}
										} else {
											$ONLOAD[] = "$('display-no-question-message').show()";
										}
										?>
										<input type="hidden" id="itemListOrder" name="itemListOrder" value =""  />
										<div id="display-no-question-message" class="display-generic" style="display: none">
											There are currently <strong>no questions</strong> associated with this <?php echo $terminology; ?>.<br /><br />To create questions in this <?php echo $terminology; ?> click the <strong>Add Question</strong> link above.
										</div>
									</td>
								</tr>
							</tbody>
							</table>
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
									<td class="on"><input type="checkbox" id="allow_member_read" name="allow_member_read" value="1"
									<?php 
									$membersDisplay = "none";
									if (((!isset($PROCESSED["allow_member_read"])) || ((isset($PROCESSED["allow_member_read"])) && ($PROCESSED["allow_member_read"] == 1)))) {
										$membersDisplay = "inline";
										echo " checked=\"checked\"";
									}
									?> onclick="showHideMembers()" /></td>
									<td><input type="checkbox" id="allow_member_vote" name="allow_member_vote" value="1"
									<?php 
									if (((!isset($PROCESSED["allow_member_vote"])) || ((isset($PROCESSED["allow_member_vote"])) && ($PROCESSED["allow_member_vote"] == 1)))) {
										$membersDisplay = "inline";
										echo " checked=\"checked\"";
									}
									?> onclick="showHideMembers(); setUnsetResults();" /></td>
									<td><input type="checkbox" id="allow_member_results" name="allow_member_results" value="1"
									<?php 
									if ((((isset($PROCESSED["allow_member_results"])) && ($PROCESSED["allow_member_results"] == 1)))) {
										$membersDisplay = "inline";
										echo " checked=\"checked\"";
									}
									?> onclick="showHideMembers(); setUnsetResults();" /></td>
									<td><input type="checkbox" id="allow_member_results_after" name="allow_member_results_after" value="1"
									<?php 
									if (((!isset($PROCESSED["allow_member_results_after"])) || ((isset($PROCESSED["allow_member_results_after"])) && ($PROCESSED["allow_member_results_after"] == 1)))) {
										$membersDisplay = "inline";
										echo " checked=\"checked\"";
									}
									?> onclick="showHideMembers()" /></td>
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
					<?php	
						if (!isset($CLEANED_MEMBERS_ARRAY))
						{
							$query					= "SELECT `proxy_id`, `firstname`, `lastname`
							FROM `community_polls_access` as a,
							`".AUTH_DATABASE."`.`user_data` AS b
							WHERE a.`proxy_id` = b.`id`
							AND a.`cpolls_id` = ".$db->qstr($RECORD_ID)."";
							
							$poll_member_array		= $db->GetAll($query);
							$poll_member_id_string 	= "";
							
							foreach($poll_member_array as $result)
							{
								$CLEANED_MEMBERS_ARRAY[(int)$result["proxy_id"]]["lastname"]		= $result["lastname"];
								$CLEANED_MEMBERS_ARRAY[(int)$result["proxy_id"]]["firstname"]		= $result["firstname"];
								if ($poll_member_id_string) {
									$poll_member_id_string .= ",".((int)$result["proxy_id"]);
								} else {
									$poll_member_id_string = ((int)$result["proxy_id"]);
								}
								$MEMBER_LIST[(int) $result["id"]] = array("lastname" => $result["lastname"], "firstname" => $result["firstname"]);
							}
						}
						if (!(isset($CLEANED_MEMBERS_ARRAY) && (is_array($CLEANED_MEMBERS_ARRAY)) && (count($CLEANED_MEMBERS_ARRAY)))) {
							$membersDisplay = "none";
						}
					?>
					<tr>
						<td colspan="2">
							<table class="table table-bordered no-thead">
								<colgroup>
									<col style="width: 5%" />
									<col style="width: auto" />
								</colgroup>
								<tr id="all_members">
									<td class="center">
										<input name="all_members_vote" id="all_members_vote" type="radio" value="1" <?php echo (!(isset($CLEANED_MEMBERS_ARRAY) && (is_array($CLEANED_MEMBERS_ARRAY)) && (count($CLEANED_MEMBERS_ARRAY))) ? "checked=\"checked\" " : ""); ?> onclick="showHideMembers()"/>
									</td>
									<td>
										<label for="all_members_vote" class="form-nrequired">Allow all members to vote</label>
									</td>
								</tr>
								<tr id="specific_members">
									<td class="center">
										<input id="specific_members_vote" name="all_members_vote" type="radio" value="0" <?php echo (isset($CLEANED_MEMBERS_ARRAY) && (is_array($CLEANED_MEMBERS_ARRAY)) && (count($CLEANED_MEMBERS_ARRAY)) ? "checked=\"checked\" " : ""); ?> onclick="showHideMembers()"/>
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
				</form>
				<script type="text/javascript">
					var people = [[]];
					var ids = [[]];
					<?php
						foreach ($access_ids as $key => $ids) {
							echo "\nids[".$key."] = []";
							foreach ($ids as $id) {
								echo "\nids[".$key."].push(".$id.");";
							}
						}
					?>
					//Updates the People Being Added div with all the options
					function updatePeopleList(newoptions, isnew, index) {
						if (isnew) {
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
						}
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
							var updater = new Ajax.Updater('community_members_scroll', '<?php echo ENTRADA_URL."/communities?section=membersapi&action=memberlist&type=polls&poll_id=".$RECORD_ID;?>',{
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
											selectedCheckboxes: 'prior_community_members',
											filter: 'community_members_select_filter',
											afterCheck: function(element) {
												var tr = $(element.parentNode.parentNode);
												tr.removeClassName('selected');
												if(element.checked) {
													tr.addClassName('selected');
												}
											},
											updateDiv: function(options, isnew) {
												if (isnew == null) {
													isnew = true;
												}
												updatePeopleList(options, isnew, $('community_members_category_select').selectedIndex);
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
	} else {
		echo "<h1>Edit Poll</h1>";
		$ERROR++;
		$ERRORSTR[] = "The id that you have provided does not exist in the system. Please provide a valid poll id to proceed.";
		
		echo display_error();
		
		application_log("error", "The provided poll id was invalid [".$RECORD_ID."] (Edit Poll).");
	}
} else {
	echo "<h1>Edit Poll</h1>";
	$ERROR++;
	$ERRORSTR[] = "Please provide a valid id to proceed.";

	echo display_error();

	application_log("error", "No poll id was provided to edit. (Edit Poll)");
}
?>
