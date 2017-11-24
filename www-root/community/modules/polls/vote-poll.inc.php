<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Used to allow the user to actually vote on a specific poll.
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

if ($RECORD_ID) {
	$query				= "SELECT * FROM `community_polls` WHERE `community_id` = ".$db->qstr($COMMUNITY_ID)." AND `cpage_id` = ".$db->qstr($PAGE_ID)." AND `cpolls_id` = ".$db->qstr($RECORD_ID);
	$poll_record		= $db->GetRow($query);
	if ($poll_record) {
		$terminology = $poll_record["poll_terminology"];
		$BREADCRUMB[] 	= array("url" => COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=vote-poll&id=".$RECORD_ID, "title" => limit_chars($poll_record["poll_title"], 25));
		
		echo "<h1>".html_encode($poll_record["poll_title"])."</h1>";
		
		$now = time();
		if ((int) $poll_record["poll_active"] && (int) $poll_record['release_date'] < $now && ((int) $poll_record['release_until'] > $now || (int) $poll_record['release_until'] == 0)) {			
			// Check to see if this is a poll that this user can vote in.
			$specificMembers = communities_polls_specific_access($RECORD_ID);
			$allow_main_load = false;
			
			if ($COMMUNITY_ADMIN) {
				$allow_main_load = true;
			}
			else if (($COMMUNITY_MEMBER && (int)$poll_record['allow_member_vote'] == 1)
			|| (!(int) $community_details["community_protected"] && (int)$poll_record['allow_public_vote'] == 1)
			|| (!(int) $community_details["community_registration"] && (int)$poll_record['allow_troll_vote'] == 1)) {
				if ((count($specificMembers) == 0) || (is_array($specificMembers) && in_array($ENTRADA_USER->getActiveId(), $specificMembers)))
				{
					$allow_main_load = true;
				}
			}
			if ($allow_main_load) {				
				$vote_record = communities_polls_votes_cast_by_member($RECORD_ID, $ENTRADA_USER->getActiveId());
				
				if (((int)$poll_record["allow_multiple"] == 0 && (!isset($vote_record["votes"]) || (int)$vote_record["votes"] == 0))
				|| ((int)$poll_record["allow_multiple"] == 1 && (!isset($vote_record["votes"]) || ($poll_record["number_of_votes"] == 0 || (int)$vote_record["votes"] < (int)$poll_record["number_of_votes"]))))
				{
					switch($STEP) {
						case 2 :
							if (is_array($_POST["cpresponses_id"]) && ($responses = $_POST["cpresponses_id"])) {
								$count = 1;
								$total_response_count = 0;
								foreach($responses as $key => $response) {
									/**
									 * Required field "cpresponses_id" / Choice.
									 */
									$response_count = 0;
									$question = $db->GetRow("SELECT * FROM `community_polls_questions` WHERE `cpolls_id` = ".$db->qstr($RECORD_ID)." AND `question_active` = '1' ORDER BY `question_order` ASC LIMIT ".(((int)$count) - 1).", 1");	
									if (is_array($response) && ($question && ((int)$question["maximum_responses"]) != 1)) {
										$question_count = 0;
										$shown_to_exceed = false;
										foreach ($response as $single_result) {
											if ($response_count > $question["maximum_responses"] && !$shown_to_exceed) {
												$shown_to_exceed = true;
												add_error("You may only choose up to ".$question["maximum_responses"]." responses to question #".$count);
											} elseif (($choice = clean_input($single_result, array("trim","int")))) {
												$chosen_responses[$total_response_count]["key"] = $key;
												$chosen_responses[$total_response_count]["result"] = $choice;
												$response_count++;
												$total_response_count++;
											}
										}
										if ($response_count < $question["minimum_responses"]) {
											add_error("You must choose at least ".$question["minimum_responses"]." responses to question #".$count);
										}
									} elseif (($choice = clean_input($response, array("trim", "int")))) {
										$chosen_responses[$total_response_count]["result"] = $choice;
										$chosen_responses[$total_response_count]["key"] =  $key;
										$total_response_count++;
									} else {
										add_error("You haven't made a choice for question #".$count.".");
									}
									$count++;
								}
							} else {
								add_error("You haven't selected answers for all the questions, please try again.");
							}
							
							if (!$ERROR) {
								$PROCESSED["proxy_id"]				= $ENTRADA_USER->getActiveId();
								$PROCESSED["updated_date"]			= time();
								$PROCESSED["updated_by"]			= $ENTRADA_USER->getID();
								
								$query = "SELECT SUM(`minimum_responses`) AS `min`, SUM(`maximum_responses`) AS `max` FROM `community_polls_questions` WHERE `cpolls_id` = ".$db->qstr($RECORD_ID)." AND `question_active` = '1'";

								if (($result = $db->GetRow($query)) && is_array($chosen_responses) && (count($chosen_responses) >= ((int)$result["min"])) && (count($chosen_responses) <= ((int)$result["max"]))) {
									foreach ($chosen_responses as $response) {
										// Use $databaseResponses when inserting into community_polls_responses
										$PROCESSED["cpquestion_id"] = $response["key"];
										$PROCESSED["cpresponses_id"] = $response["result"];
										if (!$db->AutoExecute("community_polls_results", $PROCESSED, "INSERT")) {
											$ERROR++;
											$ERRORSTR[] = "There was a problem inserting your vote into the system. The MEdTech Unit was informed of this error; please try again later.";
											application_log("error", "There was an error inserting a vote. Database said: ".$db->ErrorMsg());
											break;
										}
									}
									if (!$ERROR) {
										if ((int)$poll_record["allow_member_results"] == 1 || (int)$poll_record["allow_member_results_after"] == 1) {
											$url = COMMUNITY_URL . $COMMUNITY_URL . ":" . $PAGE_URL . "?section=view-poll&id=" . $RECORD_ID;
										} else {
											$url = COMMUNITY_URL . $COMMUNITY_URL . ":" . $PAGE_URL;
										}
                                        Entrada_Utilities_Flashmessenger::addMessage($translate->_("You have successfully voted."), "success", $MODULE);
                                        add_statistic("community_polling", "poll_vote", "cpolls_id", $RECORD_ID);

                                        header("Location: " . $url);
                                        exit;
									}
								} else {
									$questions = $db->GetAll("SELECT * FROM `community_polls_questions` WHERE `cpolls_id` = ".$db->qstr($RECORD_ID)." AND `question_active` = '1' ORDER BY `question_order`");
									foreach ($questions as $key => $question) {
										if (count($_POST["cpresponses_id"][$question["cpquestion_id"]]) < $question["minimum_responses"]) {
											$ERROR++;
											$ERRORSTR[] = "You must select at least ".$question["minimum_responses"]." responses to question #".($key+1).", please try again.";
										} elseif (count($_POST["cpresponses_id"][$question["cpquestion_id"]]) > $question["maximum_responses"]) {
											$ERROR++;
											$ERRORSTR[] = "You may select at most ".$question["maximum_responses"]." responses to question #".($key+1).", please try again.";
										}
									}
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

							if (trim($poll_record["poll_description"]) != "") {
								echo "<div style=\"margin-bottom: 15px\">\n";
								echo html_encode($poll_record["poll_description"]);
								echo "</div>\n";
							}
							
							echo "	<form action=\"".COMMUNITY_URL.$COMMUNITY_URL.":".$PAGE_URL."?section=vote-poll&id=".$RECORD_ID."&step=2\" method=\"post\">\n";
							$query = "SELECT * FROM `community_polls_questions` WHERE `cpolls_id` = ".$db->qstr($RECORD_ID)." AND `question_active` = '1' ORDER BY `question_order` ASC";
							if (($questions = $db->GetAll($query))) {
								$count = 1;
								foreach ($questions as $question) {
									echo "	<h3 style=\"line-height:30px;\">".$count.". ".html_encode($question["poll_question"])."</h3>".($question["maximum_responses"] > 1 ? "<div class=\"content-small\" style=\"width: 100%; text-align: right; margin-right: 10px;\">".($question["maximum_responses"] == $question["minimum_responses"] ? "Choose ".$question["minimum_responses"]." responses." : "Choose between ".$question["minimum_responses"]." and ".$question["maximum_responses"]." responses.")."</div>" : "<div></div>");
		    						$query 		= "SELECT * FROM `community_polls_responses` WHERE `cpolls_id` = ".$db->qstr($RECORD_ID)." AND `cpquestion_id` = ".$question["cpquestion_id"]." ORDER BY `response_index` ASC";
		    						$results 	= $db->GetAll($query);
									if ($results) {
										echo "<table class=\"table table-bordered no-thead\">\n";
										echo "	<colgroup>";
										echo "		<col style=\"width: 5%\" />";
										echo "		<col style=\"width: auto\" />";
										echo "	</colgroup>";
										if ($question["maximum_responses"] == 1) {
			    					        foreach($results as $key => $value) {
			    					        	echo "<tr>";
			    					        	echo "	<td class=\"center\">";
			    					        	echo "		<input type=\"radio\" id=\"cpresponses_id_".$count."_".(int) $key."\" name=\"cpresponses_id[".$question["cpquestion_id"]."]\" value=\"".html_encode($value['cpresponses_id'])."\" ".($_POST["cpresponses_id"][$question["cpquestion_id"]] == $value['cpresponses_id'] ? "checked=\"checked\"" : "")."/>";
			    					        	echo "	</td>";
			    					        	echo "	<td>";
			    					        	echo "		<label for=\"cpresponses_id_".$count."_".(int) $key."\" class=\"form-nrequired\">".$value['response']."</label>";
			    					        	echo "	</td>";
			    					        	echo "</tr>";
			                                }
										} else {
			    					        foreach($results as $key => $value) {
			    					        	echo "<tr>";
			    					        	echo "	<td class=\"center\">";
			    					        	echo "		<input type=\"checkbox\" id=\"cpresponses_id_".$count."_".(int) $key."\" name=\"cpresponses_id[".$question["cpquestion_id"]."][".(int) $key."]\" value=\"".html_encode($value['cpresponses_id'])."\" ".($_POST["cpresponses_id"][$question["cpquestion_id"]][$key] ? "checked=\"checked\"" : "")."/>";
			    					        	echo "	</td>";
			    					        	echo "	<td>";
			    					        	echo "		<label for=\"cpresponses_id_".$count."_".(int) $key."\" class=\"form-nrequired\">".$value['response']."</label>";
			    					        	echo "	</td>";
			    					        	echo "</tr>";
			                                }
										}
										echo "</table>\n";
									} else {
										$ERROR++;
										$ERRORSTR[] = "There are currently no responses available for this question, we apologize for the inconvenience.";
										
										echo display_error();
										
										application_log("error", "There are no responses available for question id [".$question["cpquestion_id"]."] in community_id [".$COMMUNITY_ID."].");
									}
                                    $count++;
								}
								
								echo "		<div style=\"padding-top: 15px; text-align: right\">\n";
								echo "			<input type=\"submit\" class=\"btn btn-primary\" value=\"Submit\" />\n";
								echo "		</div>\n";
							}
							echo "	</form>\n";
						break;
					}
				} else {
					$ERROR++;
					$ERRORSTR[] = "You have already voted the maximum number of times in this ".$terminology.".";
					
					echo display_error();
					
					application_log("error", "You have already voted the maximum number of times in this poll [".$RECORD_ID."] (Vote Poll).");
				}
			} else {
				$ERROR++;
				$ERRORSTR[] = "The ability to particiate in this ".$terminology." is limited to certain community members.<br /><br />If you have questions relating to this ".$terminology." please contact a community administrator for further assistance.";
				
				echo display_error();
				
				application_log("error", "You do not have permission to take part in this poll [".$RECORD_ID."] (Vote Poll).");
			}
		} else {
			$NOTICE++;
			$NOTICESTR[] = "The ".$terminology." that you are trying to vote in was deactivated or has already concluded <strong>".date(DEFAULT_DATE_FORMAT, $poll_record["updated_date"])."</strong> by <strong>".html_encode(get_account_data("firstlast", $poll_record["updated_by"]))."</strong>.<br /><br />If there has been a mistake or you have questions relating to this issue please contact the MEdTech Unit directly.";
	
			echo display_notice();
	
			application_log("error", "The poll record id [".$RECORD_ID."] is deactivated; however, ".$_SESSION["details"]["firstname"]." ".$_SESSION["details"]["lastname"]." [".$ENTRADA_USER->getID()."] has tried to vote in it.");
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "The poll id that you have provided does not exist in the system or you do not have privileges to vote in it. Please provide a valid poll id to proceed.";
		
		echo display_error();
		
		application_log("error", "The provided poll id was invalid [".$RECORD_ID."] (Vote Poll).");
	}
} else {
	$ERROR++;
	$ERRORSTR[] = "Please provide a valid poll id to proceed.";

	echo display_error();

	application_log("error", "No poll id was provided to vote. (Vote Poll)");
}
?>