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
 * This file is used to author and share quizzes with other folks who have
 * administrative permissions in the system.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_QUIZZES"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed('quizresult', 'read')) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	if (isset($_POST["mode"]) && $_POST["mode"] == "ajax") {
		ob_clear_open_buffers();
		
		$response_id = (int) $_POST["response_id"];
		$quiz_id = (int) $_POST["quiz_id"];
		
		$query = "	SELECT `b`.`question_text`, `a`.`response_text`
					FROM `quiz_question_responses` AS `a`
					JOIN `quiz_questions` AS `b`
					ON `a`.`qquestion_id` = `b`.`qquestion_id`
					WHERE `a`.`qqresponse_id` = ".$db->qstr($response_id)."
					AND b.`questiontype_id` = '1'";
		$quiz_details = $db->GetRow($query);
		
		$query = "	SELECT `a`.`proxy_id`, `a`.`qprogress_id`, `b`.`number`, CONCAT(`b`.`lastname`, ', ', `b`.`firstname`) AS `name`
					FROM `".DATABASE_NAME."`.`quiz_progress_responses` AS `a`
					JOIN `".AUTH_DATABASE."`.`user_data` AS `b`
					ON `a`.`proxy_id` = `b`.`id`
					WHERE `a`.`qqresponse_id` = ".$db->qstr($response_id)."
					AND `a`.`aquiz_id` = ".$db->qstr($quiz_id);
		$results = $db->GetAll($query);
		
		if (!empty($results)) {
			echo "<div style=\"width:500px;height:400px;overflow-y:scroll;\">\n";
			echo "<table class=\"quizResults\">\n";
			echo "\t<tbody>\n";
			echo "\t\t<tr>\n";
			echo "\t\t\t<td width=\"20%\" class=\"borderless bold\">Question:</th>\n";
			echo "\t\t\t<td class=\"borderless\">".$quiz_details["question_text"]."</th>\n";
			echo "\t\t</tr>\n";
			echo "\t\t<tr>\n";
			echo "\t\t\t<td class=\"borderless bold\">Response:</th>\n";
			echo "\t\t\t<td class=\"borderless\">".$quiz_details["response_text"]."</th>\n";
			echo "\t\t</tr>\n";
			echo "\t</tbody>\n";
			echo "</table>\n";
			echo "<br />";
			echo "<table class=\"quizResults\">\n";
			echo "\t<tbody>\n";
			echo "\t\t<tr>\n";
			echo "\t\t\t<td class=\"borderless bold\">Fullname</td>\n";
			echo "\t\t\t<td class=\"borderless bold\">Number</td>\n";
			echo "\t\t</tr>\n";			
			foreach ($results as $result) {
				echo "\t\t<tr>\n";
				echo "\t\t\t<td><a href=\"".ENTRADA_URL."/quizzes?section=results&amp;id=".$result["qprogress_id"]."\">".$result["name"]."</a></td>\n";
				echo "\t\t\t<td><a href=\"".ENTRADA_URL."/quizzes?section=results&amp;id=".$result["qprogress_id"]."\">".$result["number"]."</a></td>\n";
				echo "\t\t</tr>\n";
			}
			echo "\t</tbody>\n";
			echo "</table>\n";
			echo "</div>";
		}
		
		exit;
	}
		
	if ($RECORD_ID) {
		if ($QUIZ_TYPE == "event") {
			$query		= "	SELECT a.*, b.`course_id`, b.`event_title` AS `content_title`, d.`audience_type`, d.`audience_value` AS `event_cohort`, e.`quiz_title` AS `default_quiz_title`, e.`quiz_description` AS `default_quiz_description`, f.`quiztype_code`, g.`organisation_id`
							FROM `attached_quizzes` AS a
							LEFT JOIN `events` AS b
							ON a.`content_type` = 'event' 
							AND b.`event_id` = a.`content_id`
							LEFT JOIN `event_audience` AS d
							ON a.`content_type` = 'event' 
							AND d.`event_id` = a.`content_id`
							LEFT JOIN `quizzes` AS e
							ON e.`quiz_id` = a.`quiz_id`
							LEFT JOIN `quizzes_lu_quiztypes` AS f
							ON f.`quiztype_id` = a.`quiztype_id`
							LEFT JOIN `courses` AS g
							ON g.`course_id` = b.`course_id`
							WHERE a.`aquiz_id` = ".$db->qstr($RECORD_ID)."
							AND g.`course_active` = '1'";
		} else {
			$query		= "	SELECT a.*, b.`community_url`, b.`community_id`, bp.`page_title` AS `content_title`, bp.`page_url`, c.`quiz_title` AS `default_quiz_title`, c.`quiz_description` AS `default_quiz_description`, d.`quiztype_code`
							FROM `attached_quizzes` AS a
							LEFT JOIN `community_pages` AS bp
							ON a.`content_type` = 'community_page' 
							AND bp.`cpage_id` = a.`content_id`
							LEFT JOIN `communities` AS b
							ON b.`community_id` = bp.`community_id`
							LEFT JOIN `quizzes` AS c
							ON c.`quiz_id` = a.`quiz_id`
							LEFT JOIN `quizzes_lu_quiztypes` AS d
							ON d.`quiztype_id` = a.`quiztype_id`
							WHERE a.`aquiz_id` = ".$db->qstr($RECORD_ID);
		}
		$quiz_record	= $db->GetRow($query);
		if ($quiz_record) {
			if ($QUIZ_TYPE == "community_page") {
				$query = "	SELECT * FROM `community_members`
											WHERE `community_id` = ".$db->qstr($quiz_record["community_id"])."
											AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
											AND `member_active` = '1'
											AND `member_acl` = '1'";
				$community_admin = $db->GetRow($query);
			}
			if ($QUIZ_TYPE == "event" && !$ENTRADA_ACL->amIAllowed(new EventContentResource($quiz_record["content_id"], $quiz_record["course_id"], $quiz_record["organisation_id"]), "update")) {
				application_log("error", "Someone attempted to view the results of an aquiz_id [".$RECORD_ID."] that they were not entitled to view.");

				header("Location: ".ENTRADA_URL."/admin/events?section=content&id=".$quiz_record["content_id"]);
				exit;
			} elseif ($QUIZ_TYPE == "community_page" && !$community_admin) {
				application_log("error", "Someone attempted to view the results of an aquiz_id [".$RECORD_ID."] that they were not entitled to view.");

				header("Location: ".ENTRADA_URL."/community/".$quiz_record["community_url"].":".$quiz_record["page_url"]);
				exit;
			} else {
				$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/livepipe/progressbar.js?release=".APPLICATION_VERSION."\"></script>";
				if ($QUIZ_TYPE == "event") {
					$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/events?section=content&id=".$quiz_record["content_id"], "title" => limit_chars($quiz_record["content_title"], 32));	
					if ($ENTRADA_ACL->amIAllowed(new EventContentResource($quiz_record["content_id"], $quiz_record["course_id"], $quiz_record["organisation_id"]), "update")) {
						$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/".$MODULE."?section=edit&id=".$quiz_record["quiz_id"], "title" => limit_chars($quiz_record["quiz_title"], 32));
					}
				} else {
					$BREADCRUMB[] = array("url" => ENTRADA_URL."/community".$quiz_record["community_url"].":".$quiz_record["page_url"], "title" => limit_chars($quiz_record["content_title"], 32));
					if ($community_admin) {
						$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/".$MODULE."?section=edit&id=".$quiz_record["quiz_id"], "title" => limit_chars($quiz_record["quiz_title"], 32));
					}
				}
				$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/".$MODULE."?section=results&id=".$RECORD_ID, "title" => "Quiz Results");

				if ($QUIZ_TYPE == "event" && $quiz_record["audience_type"] == "cohort") {
					$event_cohort = $quiz_record["event_cohort"];
				} else {
					$event_cohort = 0;
				}

				$calculation_targets			= array();
				$calculation_targets["all"]		= "all quiz respondents";
				$calculation_targets["student"]	= "all students";

				$active_cohorts = groups_get_active_cohorts($ENTRADA_USER->getActiveOrganisation());
				if (isset($active_cohorts) && !empty($active_cohorts)) {
					foreach ($active_cohorts as $cohort) {
						$calculation_targets["student:".$cohort["group_id"]]	= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".html_encode($cohort["group_name"]);
					}
				}

				if (($event_cohort) && (!array_key_exists("student:".$event_cohort, $calculation_targets))) {
					$calculation_targets["student:".$event_cohort] = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".groups_get_name($event_cohort);
				}
				
				$calculation_targets["resident"]	= "all residents";
				$calculation_targets["faculty"]		= "all faculty";
				$calculation_targets["staff"]		= "all staff";

				/**
				 * Update calculation target.
				 * Valid: any key from the $calculation_targets array.
				 */
				if (isset($_GET["target"])) {
					if (array_key_exists(trim($_GET["target"]), $calculation_targets)) {
						$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["target"] = trim($_GET["target"]);
					}

					$_SERVER["QUERY_STRING"] = replace_query(array("target" => false));
				} else {
					if (!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["target"])) {
						$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["target"] = (($event_cohort) ? "student:".$event_cohort : "all");
					}
				}

				$pieces = explode(":", $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["target"]);
				$target_group	= false;
				$target_role	= false;
				if (isset($pieces[0])) {
					$target_group	= clean_input($pieces[0], "alphanumeric");
				}

				if (isset($pieces[1])) {
					$target_role	= clean_input($pieces[1], "alphanumeric");
				}

				/**
				 * Update calculation attempts.
				 * Valid: first, last, all
				 */
				if (isset($_GET["attempt"])) {
					if (in_array(trim($_GET["attempt"]), array("first", "last", "best", "all"))) {
						$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["attempt"] = trim($_GET["attempt"]);
					}

					$_SERVER["QUERY_STRING"] = replace_query(array("attempt" => false));
				} else {
					if (!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["attempt"])) {
						$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["attempt"] = "all";
					}
				}

				echo "<div class=\"content-small\">";
				if ($QUIZ_TYPE == "event" && $quiz_record["course_id"]) {
					$curriculum_path = curriculum_hierarchy($quiz_record["course_id"]);

					if ((is_array($curriculum_path)) && (count($curriculum_path))) {
						echo implode(" &gt; ", $curriculum_path);
					}
				} else {
					echo "No Associated Course";
				}
				echo " &gt; ".html_encode($quiz_record["content_title"]);
				echo "</div>\n";
				echo "<h1 class=\"event-title\">".html_encode($quiz_record["quiz_title"])."</h1>\n";

				/**
				 * Check to make sure people have completed the quiz before trying to display
				 * results of the quiz.
				 */
				if ($quiz_record["accesses"] > 0) {
					$questions		= array();
					$respondents	= array();
					$attempts		= array();
					$total_attempts	= 0;

					$query		= "	SELECT a.*
									FROM `quiz_questions` AS a
									WHERE a.`quiz_id` = ".$db->qstr($quiz_record["quiz_id"])."
									AND a.`question_active` = '1'
									AND a.`questiontype_id` = '1'
									ORDER BY a.`question_order` ASC";
					$results	= $db->GetAll($query);
					if ($results) {
						foreach ($results as $question) {
							$qquestion_id = $question["qquestion_id"];

							$questions[$qquestion_id]["question"]	= $question;
							$questions[$qquestion_id]["responses"]	= array();

							$query		= "	SELECT a.*
											FROM `quiz_question_responses` AS a
											WHERE a.`qquestion_id` = ".$db->qstr($question["qquestion_id"])."
											AND `response_active` = '1'
											ORDER BY a.`response_order` ASC";
							$responses	= $db->GetAll($query);
							if ($responses) {
								foreach ($responses as $response) {
									$questions[$qquestion_id]["responses"][$response["qqresponse_id"]] = $response;
									$questions[$qquestion_id]["responses"][$response["qqresponse_id"]]["response_selected"] = 0;
								}
							}
						}
					}

					if (($target_group == "student") && ($target_role)) {
						$query			= "	SELECT a.`id` AS `proxy_id`, a.`number`, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, b.`group`
											FROM `".AUTH_DATABASE."`.`user_data` AS a
											LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
											ON b.`user_id` = a.`id`
											LEFT JOIN `group_members` AS c
											ON b.`user_id` = c.`proxy_id`
											WHERE b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
											AND b.`group` = 'student'
											".(($target_role) ? " AND c.`group_id` = ".$db->qstr($target_role) : "")."
											ORDER BY b.`group` ASC, b.`role` ASC, `fullname` ASC";
						$respondents	= $db->GetAll($query);
					} else {
						$query			= "	SELECT a.`proxy_id`, b.`number`, CONCAT_WS(', ', b.`lastname`, b.`firstname`) AS `fullname`, c.`group`
											FROM `quiz_progress` AS a
											LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
											ON b.`id` = a.`proxy_id`
											LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS c
											ON c.`user_id` = a.`proxy_id`
											WHERE a.`aquiz_id` = ".$db->qstr($RECORD_ID)."
											AND a.`progress_value` = 'complete'
											".((($target_group != "all") && ($target_group)) ? " AND c.`group` = ".$db->qstr($target_group) : "")."
											".((($target_group != "all") && ($target_role)) ? " AND c.`role` = ".$db->qstr($target_role) : "")."
											GROUP BY a.`proxy_id`
											ORDER BY c.`group` ASC, c.`role` ASC, `fullname` ASC";
						$respondents	= $db->GetAll($query);
					}

					if ($QUIZ_TYPE == "community_page") {
						$query 			= " SELECT a.`proxy_id`, c.`number`, CONCAT_WS(', ', c.`lastname`, c.`firstname`) AS `fullname`, d.`group`, b.`progress_value` 
 											FROM `community_members` as a 
											LEFT JOIN quiz_progress as b
											ON a.`proxy_id` = b.`proxy_id` 
											AND b.`content_type` = 'community_page' 
											AND b.aquiz_id = ".$db->qstr($RECORD_ID)." 
											LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS c
											ON c.`id` = a.`proxy_id`
											LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS d
											ON d.`user_id` = c.`id`
											WHERE a.community_id = ".$db->qstr($quiz_record["community_id"])."
											".((($target_group != "all") && ($target_group)) ? " AND d.`group` = ".$db->qstr($target_group) : "")."
											".((($target_group != "all") && ($target_role)) ? " AND d.`role` = ".$db->qstr($target_role) : "")."
											AND (b.progress_value != 'complete' OR b.progress_value IS NULL) 
											AND a.`member_active` = '1'
											AND a.`member_acl` != '1'
											GROUP BY a.`proxy_id`
											ORDER BY d.`group` ASC, d.`role` ASC, `fullname` ASC";

						$members_with_no_attempts = $db->GetAll($query);

					} else {
						$members_with_no_attempts = "";
					}

					if ($respondents) {
						foreach ($respondents as $respondent) {
							$query		= "	SELECT a.*
											FROM `quiz_progress` AS a
											WHERE a.`aquiz_id` = ".$db->qstr($RECORD_ID)."
											AND a.`proxy_id` = ".$db->qstr($respondent["proxy_id"])."
											AND a.`progress_value` = 'complete'
											AND a.`content_type` = ".$db->qstr($QUIZ_TYPE);
							switch ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["attempt"]) {
								case "last" :
									$query .= "	ORDER BY a.`updated_date` DESC
												LIMIT 0, 1";
								break;
								case "best" :
									$query .= "	ORDER BY a.`quiz_score` DESC
												LIMIT 0, 1";
								break;
								case "all" :
									$query .= "	ORDER BY a.`updated_date` ASC";
								break;
								case "first" :
								default :
									$query .= "	ORDER BY a.`updated_date` ASC
												LIMIT 0, 1";
								break;
							}
							$results	= $db->GetAll($query);
							if ($results) {
								$total_attempts += count($results);

								foreach ($results as $result) {
									$attempts[$respondent["proxy_id"]][] = $result;

									$query		= "	SELECT a.*
													FROM `quiz_progress_responses` AS a
													JOIN `quiz_question_responses` AS b
													ON a.`qqresponse_id` = b.`qqresponse_id`
													WHERE a.`qprogress_id` = ".$db->qstr($result["qprogress_id"])."
													AND a.`content_type` = ".$db->qstr($QUIZ_TYPE)."
													AND b.`response_active` = '1'";
									$responses = $db->GetAll($query);
									if ($responses) {
										foreach ($responses as $response) {
											$questions[$response["qquestion_id"]]["responses"][$response["qqresponse_id"]]["response_selected"]++;
										}
									}
								}
							} else {
								$attempts[$respondent["proxy_id"]] = 0;
							}
		
						}
					}
					
					if ($total_attempts) {	
						if ((isset($_GET["download"])) && (in_array($_GET["download"], array("csv"))) && (!in_array($_GET["noattempts"], array("true"))) && (count($respondents))) {
							ob_start();
							echo '"Number","Fullname","Completed","Score","Out Of","Percent"', "\n";

							$quiz_value	= 0;
							foreach ($respondents as $respondent) {
								$quiz_score	= 0;
								$proxy_id	= $respondent["proxy_id"];

								if ((isset($attempts[$proxy_id])) && ($attempts[$proxy_id]) && (is_array($attempts[$proxy_id]))) {
									foreach ($attempts[$proxy_id] as $attempt) {
										$quiz_score	= $attempt["quiz_score"];
										$quiz_value = $attempt["quiz_value"];

										$cols	= array();
										$cols[]	= (($respondent["group"] == "student") ? $respondent["number"] : 0);
										$cols[]	= $respondent["fullname"];
										$cols[] = (((int) $attempt["updated_date"]) ? date(DEFAULT_DATE_FORMAT, $attempt["updated_date"]) : "Unknown");
										$cols[]	= $quiz_score;
										$cols[]	= $quiz_value;
										$cols[]	= number_format(((round(($quiz_score / $quiz_value), 3)) * 100), 1)."%";
									}
								} else {
									$cols	= array();
									$cols[]	= $respondent["number"];
									$cols[]	= $respondent["fullname"];
									$cols[] = "Not Completed";
									$cols[]	= "0";
									$cols[]	= $quiz_value;
									$cols[]	= "0%";
								}

								echo '"'.implode('","', $cols).'"', "\n";
							}
							$contents = ob_get_contents();

							ob_clear_open_buffers();
							
							header("Pragma: public");
							header("Expires: 0");
							header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
							header("Content-Type: application/force-download");
							header("Content-Type: application/octet-stream");
							header("Content-Type: text/csv");
							header("Content-Disposition: attachment; filename=\"".date("Y-m-d")."_".useable_filename($quiz_record["content_title"]."_".$quiz_record["quiz_title"]).".csv\"");
							header("Content-Length: ".strlen($contents));
							header("Content-Transfer-Encoding: binary\n");

							echo $contents;
							exit;
						} elseif ((isset($_GET["download"])) && (in_array($_GET["download"], array("csv"))) && (in_array($_GET["noattempts"], array("true"))) && (count($members_with_no_attempts))) {
							ob_start();
							echo '"Number","Fullname","Quiz Status"', "\n";
							$quiz_value	= 0;
							foreach ($members_with_no_attempts as $respondent) {
								$cols	= array();
								$cols[]	= $respondent["number"];
								$cols[]	= $respondent["fullname"];
								if ($respondent["progress_value"] == "inprogress") {
									$cols[]	= "In Progress";
								} elseif ($respondent["progress_value"] == "expired") {
									$cols[]	= "Expired";
								} else {
									$cols[]	= "Not Attempted";
								}
								echo '"'.implode('","', $cols).'"', "\n";
							}
							$contents = ob_get_contents();

							ob_clear_open_buffers();

							header("Pragma: public");
							header("Expires: 0");
							header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
							header("Content-Type: application/force-download");
							header("Content-Type: application/octet-stream");
							header("Content-Type: text/csv");
							header("Content-Disposition: attachment; filename=\"".date("Y-m-d")."_".useable_filename($quiz_record["content_title"]."_NOT_ATTEMPTED_".$quiz_record["quiz_title"]).".csv\"");
							header("Content-Length: ".strlen($contents));
							header("Content-Transfer-Encoding: binary\n");

							echo $contents;
							exit;
						}
						?>
						<script type="text/javascript">
							jQuery(function(){
								
								jQuery(".respondents").click(function(){
									jQuery.ajax({
										type: 'POST',
										url: '<?php echo ENTRADA_URL."/admin/".$MODULE.'?section=results'; ?>',
										data: 'mode=ajax&response_id='+jQuery(this).attr("rel")+'&quiz_id=<?php echo $RECORD_ID.(isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["attempt"]) && in_array(trim($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["attempt"]), array("first", "last", "best", "all")) ? "&attempt=".$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["attempt"] : ""); ?>',
										success: function(data) {
											jQuery(data).dialog({
												modal: true,
												resizable: false,
												draggable: false,
												width: 500,
												height: 400,
												title: 'Question Respondents',
												buttons: {
													"OK": function() { jQuery(this).dialog("close"); }
												}
											});
										}
									});
									return false;
								});
							});
						</script>
						<a name="question-breakdown-section"></a>
						<h2 title="Question Breakdown Section">Quiz Results by Question Breakdown</h2>
						<div id="question-breakdown-section">
							<div class="content-small">Based on <strong><?php echo $total_attempts; ?></strong> response<?php echo (($total_attempts != 1) ? "s" : ""); ?>.</div>
							<table class="quizResults">
							<colgroup>
								<col style="width: 3%" />
								<col style="width: 3%" />
								<col style="width: 40%" />
								<col style="width: 38%" />
								<col style="width: 8%" />
								<col style="width: 8%" />
							</colgroup>
							<tbody>
								<tr>
									<td class="borderless" colspan="4">&nbsp;</td>
									<td class="borderless left bold">Percent</td>
									<td class="borderless center bold">Count</td>
								</tr>

								<?php
								$response_count = 0;
								foreach ($questions as $qquestion_id => $question) {
									$response_count++;

									echo "<tr>\n";
									echo "	<td>".$response_count.")</td>\n";
									echo "	<td colspan=\"5\">".clean_input($question["question"]["question_text"], "allowedtags")."</td>";
									echo "</tr>";

									$response_correct = 0;

									foreach ($question["responses"] as $qqresponse_id => $response) {

										if ($response["response_correct"] == 1) {
											$response_correct += $response["response_selected"];
										}

										$percent = number_format(((round(($response["response_selected"] / $total_attempts), 3)) * 100), 1);
										echo "<tr>\n";
										echo "	<td>&nbsp;</td>\n";
										echo "	<td><img src=\"".ENTRADA_URL."/images/question-".((($response["response_correct"] == 1)) ? "correct" : "incorrect").".gif\" width=\"16\" height=\"16\" /></td>";
										echo "	<td>".clean_input($response["response_text"], (($response["response_is_html"] == 1) ? "trim" : "encode"))."</td>";
										echo "	<td>\n";
										echo "		<div id=\"response-".$qqresponse_id."\" class=\"stats-container".(($response["response_correct"] == 1) ? " correct" : "")."\"></div>\n";
										echo "		<script type=\"text/javascript\">\n";
										echo "			new Control.ProgressBar('response-".$qqresponse_id."').setProgress('".(int) $percent."');\n";
										echo "		</script>\n";
										echo "	</td>\n";
										echo "	<td class=\"left\">".$percent."%</td>";
										echo "	<td class=\"center\">".(($response["response_selected"] > 0) ? "<a href=\"#\" class=\"respondents\" rel=\"".$response["qqresponse_id"]."\">" : "" ).$response["response_selected"].(($response["response_selected"] > 0) ? "</a>" : "" )."</td>";
										echo "</tr>";
									}
									echo "<tr>\n";
									echo "	<td colspan=\"3\" class=\"borderless\">&nbsp;</td>\n";
									echo "	<td colspan=\"3\" class=\"borderless\" style=\"padding-bottom: 10px\">\n";
									echo "		<span class=\"content-small\">".number_format(((round(($response_correct / $total_attempts), 3)) * 100), 1)."% Responded Correctly</span>\n";
									echo "	</td>";
									echo "</tr>";
								}
								?>
							</tbody>
							</table>
						</div>

						<a name="quiz-respondent-section"></a>
						<h2 title="Quiz Respondent Section">Quiz Results by Respondent</h2>
						<div id="quiz-respondent-section">
							<table class="quizResults">
							<colgroup>
								<col style="width: 3%" />
								<col style="width: 13%" />
								<col style="width: 35%" />
								<col style="width: 23%" />
								<col style="width: 8%" />
								<col style="width: 2%" />
								<col style="width: 8%" />
								<col style="width: 8%" />
							</colgroup>
							<tfoot>
								<tr>
									<td>&nbsp;</td>
									<td colspan="6" style="padding-top: 10px">
										<button class="btn" onclick="window.location='<?php echo ENTRADA_URL."/admin/".$MODULE."?".replace_query(array("download" => "csv")); ?>'">Download CSV</button>
									</td>
								</tr>
							</tfoot>
							<tbody>
								<tr>
									<td class="borderless">&nbsp;</td>
									<td class="borderless bold">Number</td>
									<td class="borderless bold">Fullname</td>
									<td class="borderless bold">Completed</td>
									<td class="borderless right bold">Score</td>
									<td class="borderless center bold">/</td>
									<td class="borderless left bold">Out Of</td>
									<td class="borderless right bold">Percent</td>
								</tr>

								<?php
								$quiz_value	= 0;
								foreach ($respondents as $respondent) {
									$quiz_score	= 0;
									$proxy_id	= $respondent["proxy_id"];

									if ((isset($attempts[$proxy_id])) && ($attempts[$proxy_id]) && (is_array($attempts[$proxy_id]))) {
										foreach ($attempts[$proxy_id] as $attempt) {
											$quiz_score		= $attempt["quiz_score"];
											$quiz_value		= $attempt["quiz_value"];
											$quiz_percent	= number_format(((round(($quiz_score / $quiz_value), 3)) * 100), 1);

											echo "<tr>\n";
											echo "	<td><img src=\"".ENTRADA_URL."/images/question-".((($quiz_percent > 60)) ? "correct" : "incorrect").".gif\" width=\"16\" height=\"16\" /></td>";
											echo "	<td><a href=\"".ENTRADA_URL."/quizzes?section=results&amp;id=".$attempt["qprogress_id"]."\">".html_encode((($respondent["group"] == "student") ? $respondent["number"] : 0))."</a></td>\n";
											echo "	<td><a href=\"".ENTRADA_URL."/quizzes?section=results&amp;id=".$attempt["qprogress_id"]."\">".html_encode($respondent["fullname"])."</a></td>\n";
											echo "	<td><a href=\"".ENTRADA_URL."/quizzes?section=results&amp;id=".$attempt["qprogress_id"]."\">".(((int) $attempt["updated_date"]) ? date(DEFAULT_DATE_FORMAT, $attempt["updated_date"]) : "Unknown")."</a></td>\n";
											echo "	<td class=\"right\">".$quiz_score."</td>\n";
											echo "	<td class=\"center\">/</td>\n";
											echo "	<td class=\"left\">".$quiz_value."</td>\n";
											echo "	<td class=\"right\">".$quiz_percent."%</td>\n";
											echo "</tr>";
										}
									} else {
										echo "<tr>\n";
										echo "	<td><img src=\"".ENTRADA_URL."/images/question-incorrect.gif\" width=\"16\" height=\"16\" /></td>";
										echo "	<td>".html_encode((($respondent["group"] == "student") ? $respondent["number"] : 0))."</td>\n";
										echo "	<td>".html_encode($respondent["fullname"])."</td>\n";
										echo "	<td>Not Completed</td>\n";
										echo "	<td class=\"right\">0</td>\n";
										echo "	<td class=\"center\">/</td>\n";
										echo "	<td class=\"left\">".$quiz_value."</td>\n";
										echo "	<td class=\"right\">0%</td>\n";
										echo "</tr>";
									}
								}
								?>
							</tbody>
							</table>
						</div>

						<?php
						if ($QUIZ_TYPE == "community_page") {
							if (is_array($members_with_no_attempts) && sizeof($members_with_no_attempts) > 0) {?>
								<a name="quiz-no-attempts-section"></a>
								<h2 title="Quiz No Attempts Section">Incomplete Quiz Respondents</h2>
								<div id="quiz-no-attempts-section">
									<table class="quizResults">
										<tbody>
											<tr>
												<td class="borderless bold">Number</td>
												<td class="borderless bold">Full Name</td>
												<td class="borderless bold">Quiz Status</td>
											</tr>
											<?php
											$quiz_value	= 0;
											foreach ($members_with_no_attempts as $respondent) {
												echo "<tr>\n";
												echo "	<td>".html_encode( $respondent["number"])."</td>\n";
												echo "	<td>".html_encode($respondent["fullname"])."</td>\n";
												echo "	<td>";
												if ($respondent["progress_value"] == "inprogress") {
													echo "In Progress";
												} elseif ($respondent["progress_value"] == "expired") {
													echo "Expired";
												} else {
													echo "Not Attempted";
												}
												echo "</td>\n";
												echo "</tr>";
											}
											?>
										</tbody>
										<tfoot>
											<tr>
												<td colspan="3" style="padding-top: 10px">
													<button class="btn" onclick="window.location='<?php echo ENTRADA_URL."/admin/".$MODULE."?".replace_query(array("download" => "csv", "noattempts" => "true")); ?>'">Download CSV</button>
												</td>
											</tr>
										</tfoot>
									</table>
								</div>
								<?php
							}
						}
					} else {
						?>
						<div class="display-notice">
							<h3>No Completed Quizzes</h3>
							There have been no quizzes completed by
							<?php
							switch($target_group) {
								case "faculty" :
									echo "<strong>faculty members</strong>";
								break;
								case "staff" :
									echo "<strong>staff members</strong>";
								break;
								case "student" :
									echo "<strong>students</strong>".(($target_role) ? " in the <strong>".groups_get_name($target_role)."</strong>" : "");
								break;
								case "all" :
								default :
									echo "anyone";
								break;
							}
							?>
							at this point.
							<br /><br />
							Try changing the group that results are calculated for in the <strong>Result Calculation</strong> menu.
						</div>
						<?php
					}

					/**
					 * Sidebar item that will provide a method for choosing which results to display.
					 */
					$sidebar_html  = "Calculate results for:\n";
					$sidebar_html .= "<ul class=\"menu\">\n";
					if (is_array($calculation_targets)) {
						foreach ($calculation_targets as $key => $target_name) {
							$sidebar_html .= "	<li class=\"".((strtolower($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["target"]) == $key) ? "on" : "off")."\"><a href=\"".ENTRADA_URL."/admin/".$MODULE."?".replace_query(array("target" => $key))."\" title=\"".trim(html_decode($target_name))."\">".$target_name."</a></li>\n";
						}
					}
					$sidebar_html .= "</ul>\n";
					$sidebar_html .= "Results based on:\n";
					$sidebar_html .= "<ul class=\"menu\">\n";
					$sidebar_html .= "	<li class=\"".((strtolower($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["attempt"]) == "first") ? "on" : "off")."\"><a href=\"".ENTRADA_URL."/admin/".$MODULE."?".replace_query(array("attempt" => "first"))."\" title=\"The First Attempt\">only the first attempt</a></li>\n";
					$sidebar_html .= "	<li class=\"".((strtolower($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["attempt"]) == "last") ? "on" : "off")."\"><a href=\"".ENTRADA_URL."/admin/".$MODULE."?".replace_query(array("attempt" => "last"))."\" title=\"The Last Attempt\">only the last attempt</a></li>\n";
					$sidebar_html .= "	<li class=\"".((strtolower($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["attempt"]) == "best") ? "on" : "off")."\"><a href=\"".ENTRADA_URL."/admin/".$MODULE."?".replace_query(array("attempt" => "best"))."\" title=\"The Best Attempt\">only the highest scored attempt</a></li>\n";
					$sidebar_html .= "	<li class=\"".((strtolower($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["attempt"]) == "all") ? "on" : "off")."\"><a href=\"".ENTRADA_URL."/admin/".$MODULE."?".replace_query(array("attempt" => "all"))."\" title=\"All Attempts\">all attempts</a></li>\n";
					$sidebar_html .= "</ul>\n";

					new_sidebar_item("Result Calculation", $sidebar_html, "sort-results", "open");

					/**
					 * Sidebar item that will provide the links to the different sections within this page.
					 */
					$sidebar_html  = "<ul class=\"menu\">\n";
					$sidebar_html .= "	<li class=\"link\"><a href=\"#question-breakdown-section\" onclick=\"$('question-breakdown-section').scrollTo(); return false;\" title=\"Results by Question Breakdown\">Results by Question</a></li>\n";
					$sidebar_html .= "	<li class=\"link\"><a href=\"#quiz-respondent-section\" onclick=\"$('quiz-respondent-section').scrollTo(); return false;\" title=\"Results by Respondent\">Results by Respondent</a></li>\n";
					$sidebar_html .= "</ul>\n";

					new_sidebar_item("Page Anchors", $sidebar_html, "page-anchors", "open");

				} else {
					add_notice("There have been no completed attempts of this quiz to date. Please check back again later.");

					echo display_notice();
				}
			}
		}  else {
			add_error("In order to view the results of a quiz, you must provide a quiz identifier.");

			echo display_error();

			application_log("notice", "Failed to provide a valid quiz identifer [".$RECORD_ID."] when attempting to view quiz results.");
		}
	} else {
		add_error("In order to view the results of a quiz, you must provide a quiz identifier.");

		echo display_error();

		application_log("notice", "Failed to provide a quiz identifier to view results for.");
	}
}