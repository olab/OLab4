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
 * This section is loaded when an individual wants to attempt to fill out an evaluation.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if((!defined("PARENT_INCLUDED")) || (!defined("IN_PUBLIC_EVALUATIONS"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
}

if (isset($_GET["target_id"]) && clean_input($_GET["target_id"], "trim")) {
	$view_target = clean_input($_GET["target_id"], "trim");
} else {
	$view_target = false;
}

if (isset($_GET["pid"]) && clean_input($_GET["pid"], "int")) {
	$progress_id = clean_input($_GET["pid"], "int");
} else {
	$progress_id = false;
}

if ($RECORD_ID) {
	?>
	<script type="text/javascript">
    document.observe('dom:loaded',function(){
		$$(".criteria-tooltip").each(function (e) {
			new Control.Window($(e.id),{  
				position: 'relative',  
				hover: true,  
				offsetLeft: 125,  
				width: 250,  
				className: 'criteria-tooltip-box'  
			});
		});
    }); 
	</script>
	<?php
	$query = "SELECT * FROM `evaluations` AS a
				JOIN `evaluation_forms` AS b
				ON a.`eform_id` = b.`eform_id`
                JOIN `evaluations_lu_targets` AS c
                ON b.`target_id` = c.`target_id`
				WHERE `evaluation_id` = ".$db->qstr($RECORD_ID);
	$evaluation = $db->GetRow($query);
	if ($evaluation) {
		$questions = array();
		$responses = array();
		$criteria_response_ids = array();
		$permissions = Classes_Evaluation::getReviewPermissions($evaluation["evaluation_id"]);
		$query = "SELECT * FROM `evaluation_form_questions` AS a
					JOIN `evaluations_lu_questions` AS b
					ON a.`equestion_id` = b.`equestion_id`
					WHERE a.`eform_id` = ".$db->qstr($evaluation["eform_id"])."
					ORDER BY a.`question_order`";
		$evaluation_questions = $db->GetAll($query);
		if ($evaluation_questions) {
			foreach ($evaluation_questions as $evaluation_question) {
				$query = "SELECT *, a.`eqresponse_id` FROM `evaluations_lu_question_responses` AS a
							LEFT JOIN `evaluations_lu_question_response_criteria` AS b
							ON a.`eqresponse_id` = b.`eqresponse_id`
							WHERE a.`equestion_id` = ".$db->qstr($evaluation_question["equestion_id"])."
							ORDER BY a.`response_order`";
				$evaluation_question_responses = $db->GetAll($query);
				if ($evaluation_question_responses) {
					$evaluation_question["response_ids"] = array();
					foreach ($evaluation_question_responses as $evaluation_question_response) {
						if ($evaluation_question_response["criteria_text"]) {
							$criteria_response_ids[] = $evaluation_question_response["eqresponse_id"];
							?>
							<div id="criteria-<?php echo $evaluation_question_response["eqresponse_id"]; ?>" style="display: none;">
								<span class="content-small">
									<strong>Criteria:</strong>
									<br />
									<?php
										echo nl2br($evaluation_question_response["criteria_text"]);
									?>
								</span>
							</div>
							<?php
						}
						$evaluation_question_response["selections"] = 0;
						$responses[$evaluation_question_response["eqresponse_id"]] = $evaluation_question_response;
						$evaluation_question["response_ids"][] = $evaluation_question_response["eqresponse_id"];
					}
				}
				$evaluation_question["selections"] = 0;
				$questions[$evaluation_question["equestion_id"]] = $evaluation_question;
			}
		}
	}
	if ($evaluation && $ENTRADA_ACL->amIAllowed(new EvaluationResource($evaluation["evaluation_id"], $evaluation["organisation_id"], true), "update", true)) {
		array_unshift($permissions, array("contact_type" => "reviewer"));
	}
	if ($evaluation && isset($permissions) && $permissions) {
		$available_target_ids = array();
		$available_targets = array();
		$target_attempts = array();
		$completed_attempts = Classes_Evaluation::getProgressRecordsByPermissions($RECORD_ID, $permissions, true, $evaluation["target_shortname"]);
		foreach ($completed_attempts as $completed_attempt) {
			if (!isset($progress_id) || !$progress_id || $completed_attempt["eprogress_id"] == $progress_id) {
                if (isset($completed_attempt["preceptor_proxy_id"]) && $completed_attempt["preceptor_proxy_id"]) {
                    $target_id = $completed_attempt["preceptor_proxy_id"];
                } elseif (isset($completed_attempt["event_id"]) && ((int)$completed_attempt["event_id"]) && isset($completed_attempt["target_record_id"]) && $completed_attempt["target_record_id"]) {
                    $target_id = ((int)$completed_attempt["target_record_id"]);
                } elseif (isset($completed_attempt["target_record_id"]) && ((int)$completed_attempt["target_record_id"])) {
                    $target_id = ((int)$completed_attempt["target_record_id"]);
                } else {
                    $target_id = ((int)$completed_attempt["target_value"]);
                }
				if (!array_search($target_id, $available_target_ids)) {
					$available_target_ids[] = $target_id;
				}
				if (!isset($target_attempts[$target_id])) {
					$target_attempts[$target_id] = array();
				}
				if (!array_key_exists($target_id, $available_targets)) {
					switch ($completed_attempt["target_type"]) {
						case "course_id" :
							$query = "SELECT * FROM `courses`
										WHERE `course_id` = ".$db->qstr($target_id);
							$course = $db->GetRow($query);
							if ($course) {
								$course["responses"] = array();
								foreach ($responses as $response) {
									$course["responses"][$response["equestion_id"]]["responses"][$response["eqresponse_id"]] = 0;
								}
                                $course["eprogress_id"] = $completed_attempt["eprogress_id"];
								$course["target_title"] = $course["course_name"]." - ".$course["course_code"];
								$course["sort_title"] = $course["course_name"]." - ".$course["course_code"];
								$available_targets[$target_id] = $course;
							}
						break;
						case "proxy_id" :
						case "cgroup_id" :
						case "cohort" :
						case "self" :
							$query = "SELECT * FROM `".AUTH_DATABASE."`.`user_data`
										WHERE `id` = ".$db->qstr($target_id);
							$user = $db->GetRow($query);
							if ($user) {
								$user["responses"] = array();
								foreach ($responses as $response) {
									$user["responses"][$response["equestion_id"]]["responses"][$response["eqresponse_id"]] = 0;
								}
                                $user["eprogress_id"] = $completed_attempt["eprogress_id"];
								$user["target_title"] = $user["firstname"]." ".$user["lastname"];
								$user["sort_title"] = $user["lastname"].", ".$user["firstname"];
								$available_targets[$target_id] = $user;
							}
						break;
						case "rotation_id" :
							if (isset($completed_attempt["preceptor_proxy_id"]) && $completed_attempt["preceptor_proxy_id"]) {
								$query = "SELECT * FROM `".CLERKSHIP_DATABASE."`.`global_lu_rotations` AS a
											JOIN `courses` AS b
											ON a.`course_id` = b.`course_id`
											JOIN `".CLERKSHIP_DATABASE."`.`events` AS c
											ON a.`rotation_id` = c.`rotation_id`
											JOIN `".AUTH_DATABASE."`.`user_data` AS d
											ON d.`id` = ".$db->qstr($completed_attempt["preceptor_proxy_id"])."
											WHERE c.`event_id` = ".$db->qstr($completed_attempt["event_id"]);
								$clerkship_preceptor = $db->CacheGetRow(LONG_CACHE_TIMEOUT, $query);
								if ($clerkship_preceptor) {
									$clerkship_preceptor["responses"] = array();
									foreach ($responses as $response) {
										$clerkship_preceptor["responses"][$response["equestion_id"]]["responses"][$response["eqresponse_id"]] = 0;
									}
                                    $clerkship_preceptor["eprogress_id"] = $completed_attempt["eprogress_id"];
									$clerkship_preceptor["target_title"] = $clerkship_preceptor["firstname"]." ".$clerkship_preceptor["lastname"];
                                    $clerkship_preceptor["event_title"] = $clerkship_preceptor["event_title"] . " (".date("d-m-Y", $clerkship_preceptor["event_start"])." to ".date("d-m-Y", $clerkship_preceptor["event_finish"]).")";
									$clerkship_preceptor["sort_title"] = $clerkship_preceptor["lastname"].", ".$clerkship_preceptor["firstname"];
									$available_targets[$completed_attempt["preceptor_proxy_id"]] = $clerkship_preceptor;
								} else {
                                    $query = "SELECT * FROM `".CLERKSHIP_DATABASE."`.`global_lu_rotations` AS a
                                                JOIN `courses` AS b
                                                ON a.`course_id` = b.`course_id`
                                                JOIN `".CLERKSHIP_DATABASE."`.`events` AS c
                                                ON a.`rotation_id` = c.`rotation_id`
                                                JOIN `".CLERKSHIP_DATABASE."`.`other_teachers` AS d
                                                ON CONCAT('OT-', d.`oteacher_id`) = ".$db->qstr($completed_attempt["preceptor_proxy_id"])."
                                                WHERE c.`event_id` = ".$db->qstr($completed_attempt["event_id"]);
                                    $clerkship_preceptor = $db->CacheGetRow(LONG_CACHE_TIMEOUT, $query);
                                    if ($clerkship_preceptor) {
                                        $clerkship_preceptor["responses"] = array();
                                        foreach ($responses as $response) {
                                            $clerkship_preceptor["responses"][$response["equestion_id"]]["responses"][$response["eqresponse_id"]] = 0;
                                        }
                                        $clerkship_preceptor["eprogress_id"] = $completed_attempt["eprogress_id"];
                                        $clerkship_preceptor["target_title"] = $clerkship_preceptor["firstname"]." ".$clerkship_preceptor["lastname"];
                                        $clerkship_preceptor["event_title"] = $clerkship_preceptor["event_title"] . " (".date("d-m-Y", $clerkship_preceptor["event_start"])." to ".date("d-m-Y", $clerkship_preceptor["event_finish"]).")";
                                        $clerkship_preceptor["sort_title"] = $clerkship_preceptor["lastname"].", ".$clerkship_preceptor["firstname"];
                                        $available_targets[$completed_attempt["preceptor_proxy_id"]] = $clerkship_preceptor;
                                    } 
                                }
							} else {
								$query = "SELECT * FROM `".CLERKSHIP_DATABASE."`.`events` AS a
											JOIN `".CLERKSHIP_DATABASE."`.`global_lu_rotations` AS b
											ON a.`rotation_id` = b.`rotation_id`
											LEFT JOIN `courses` AS c
											ON b.`course_id` = c.`course_id`
											WHERE a.`event_id` = ".$db->qstr($completed_attempt["event_id"]);
								$clerkship_event = $db->CacheGetRow(LONG_CACHE_TIMEOUT, $query);
								if ($clerkship_event) {
									$clerkship_event["responses"] = array();
									foreach ($responses as $response) {
										$clerkship_event["responses"][$response["equestion_id"]]["responses"][$response["eqresponse_id"]] = 0;
									}
									$clerkship_event["eprogress_id"] = $completed_attempt["eprogress_id"];
									$clerkship_event["target_title"] = $clerkship_event["rotation_title"];
									$clerkship_event["sort_title"] = $clerkship_event["rotation_title"].", ".$clerkship_event["event_title"];
									$available_targets[$target_id] = $clerkship_event;
								}
							}
						break;
					}
					$available_targets[$target_id]["questions"] = array();
				}/*
				$query = "SELECT a.*, b.`equestion_id` FROM `evaluation_responses` AS a
							LEFT JOIN `evaluation_form_questions` AS b
							ON a.`efquestion_id` = b.`efquestion_id`
							WHERE a.`eprogress_id` = ".$db->qstr($completed_attempt["eprogress_id"]);
				$evaluation_responses = $db->GetAll($query);
				if ($evaluation_responses) {
					$completed_attempt["responses"] = array();
					foreach ($evaluation_responses as $evaluation_response) {
						if (!isset($available_targets[$target_id]["questions"][$evaluation_response["equestion_id"]])) {
							$available_targets[$target_id]["questions"][$evaluation_response["equestion_id"]] = array(  "responses" => array(),
																														"comments" => array(),
																														"selections" => 0);
						}
						$completed_attempt["responses"][$evaluation_response["equestion_id"]] = $evaluation_response;
						if ($evaluation_response["comments"]) {
							if ($evaluation_response["eqresponse_id"]) {
								$responses[$evaluation_response["eqresponse_id"]]["comments"][$evaluation_response["eresponse_id"]]["text"] = $evaluation_response["comments"];
								$responses[$evaluation_response["eqresponse_id"]]["comments"][$evaluation_response["eresponse_id"]]["eqresponse_id"] = $evaluation_response["eqresponse_id"];
							}
							$available_targets[$target_id]["questions"][$evaluation_response["equestion_id"]]["comments"][$evaluation_response["eresponse_id"]]["text"] = $evaluation_response["comments"];
							$available_targets[$target_id]["questions"][$evaluation_response["equestion_id"]]["comments"][$evaluation_response["eresponse_id"]]["eqresponse_id"] = $evaluation_response["eqresponse_id"];
							$available_targets[$target_id]["questions"][$evaluation_response["equestion_id"]]["comments"][$evaluation_response["eresponse_id"]]["equestion_id"] = $evaluation_response["equestion_id"];
						}
						if ($evaluation_response["eqresponse_id"] && !isset($available_targets[$target_id]["questions"][$evaluation_response["equestion_id"]]["responses"][$evaluation_response["eqresponse_id"]])) {
							$available_targets[$target_id]["questions"][$evaluation_response["equestion_id"]]["responses"][$evaluation_response["eqresponse_id"]] = 0;
						}
						if ($evaluation_response["eqresponse_id"]) {
							$responses[$evaluation_response["eqresponse_id"]]["selections"]++;
							$questions[$evaluation_response["equestion_id"]]["selections"]++;
							$available_targets[$target_id]["questions"][$evaluation_response["equestion_id"]]["responses"][$evaluation_response["eqresponse_id"]]++;
							$available_targets[$target_id]["questions"][$evaluation_response["equestion_id"]]["selections"]++;
						}
					}
				}*/
			}
		}
		if ($available_targets) {
			function sort_by_title ($a, $b) {
				return strcmp($a["sort_title"], $b["sort_title"]);
			}
			uasort($available_targets, "sort_by_title");
			
			if (count($available_targets) > 1) {
				$sidebar_html  = "<ul class=\"menu\">\n";
				foreach ($available_targets as $target_id => $available_target) {
					if (!isset($selected_target) && !$view_target) {
						$selected_target = $available_target;
						$selected_target_id = $target_id;
					} elseif ($view_target && $view_target == $target_id) {
						$selected_target = $available_target;
						$selected_target_id = $target_id;
					}
					$sidebar_html .= "	<li class=\"".(isset($selected_target_id) && $selected_target_id == $target_id ? "on" : "off")."\"><a href=\"".ENTRADA_URL."/evaluations?section=review&id=".$RECORD_ID."&target_id=".$target_id."\">".$available_target["target_title"]."</a></li>\n";
				}
				//@todo: Should be added in later to allow viewing aggregate of results from a number of evaluation targets.
				//$sidebar_html .= "	<li class=\"link\"><a href=\"".ENTRADA_URL."/evaluations?section=review&id=".$RECORD_ID."&target_id=all\">Show All</a></li>\n";
				$sidebar_html .= "</ul>\n";

				new_sidebar_item("Targets to Review", $sidebar_html, "review-targets", "open", "1.9");
			} else {
				foreach ($available_targets as $target_id => $available_target) {
					$selected_target = $available_target;
					$selected_target_id = $target_id;
				}
			}
            if (isset($selected_target_id)) {
                foreach ($completed_attempts as $completed_attempt) {
                    if (!isset($progress_id) || !$progress_id || $completed_attempt["eprogress_id"] == $progress_id) {
                        if (isset($completed_attempt["preceptor_proxy_id"]) && $completed_attempt["preceptor_proxy_id"]) {
                            $target_id = $completed_attempt["preceptor_proxy_id"];
                        } elseif (isset($completed_attempt["event_id"]) && ((int)$completed_attempt["event_id"]) && isset($completed_attempt["target_record_id"]) && $completed_attempt["target_record_id"]) {
                            $target_id = ((int)$completed_attempt["target_record_id"]);
                        } elseif (isset($completed_attempt["target_record_id"]) && ((int)$completed_attempt["target_record_id"])) {
                            $target_id = ((int)$completed_attempt["target_record_id"]);
                        } else {
                            $target_id = ((int)$completed_attempt["target_value"]);
                        }
                        if ($target_id == $selected_target_id) {
                            $query = "SELECT a.*, b.`equestion_id` FROM `evaluation_responses` AS a
                                        LEFT JOIN `evaluation_form_questions` AS b
                                        ON a.`efquestion_id` = b.`efquestion_id`
                                        WHERE a.`eprogress_id` = ".$db->qstr($completed_attempt["eprogress_id"]);
                            $evaluation_responses = $db->CacheGetAll(LONG_CACHE_TIMEOUT, $query);
                            if ($evaluation_responses) {
                                $completed_attempt["responses"] = array();
                                foreach ($evaluation_responses as $evaluation_response) {
                                    if (!isset($available_targets[$target_id]["questions"][$evaluation_response["equestion_id"]])) {
                                        $available_targets[$target_id]["questions"][$evaluation_response["equestion_id"]] = array(  "responses" => array(),
                                                                                                                                    "comments" => array(),
                                                                                                                                    "selections" => 0);
                                    }
                                    $completed_attempt["responses"][$evaluation_response["equestion_id"]] = $evaluation_response;
                                    if ($evaluation_response["comments"]) {
                                        if ($evaluation_response["eqresponse_id"]) {
                                            $responses[$evaluation_response["eqresponse_id"]]["comments"][$evaluation_response["eresponse_id"]]["text"] = $evaluation_response["comments"];
                                            $responses[$evaluation_response["eqresponse_id"]]["comments"][$evaluation_response["eresponse_id"]]["eqresponse_id"] = $evaluation_response["eqresponse_id"];
                                        }
                                        $available_targets[$target_id]["questions"][$evaluation_response["equestion_id"]]["comments"][$evaluation_response["eresponse_id"]]["text"] = $evaluation_response["comments"];
                                        $available_targets[$target_id]["questions"][$evaluation_response["equestion_id"]]["comments"][$evaluation_response["eresponse_id"]]["eqresponse_id"] = $evaluation_response["eqresponse_id"];
                                        $available_targets[$target_id]["questions"][$evaluation_response["equestion_id"]]["comments"][$evaluation_response["eresponse_id"]]["equestion_id"] = $evaluation_response["equestion_id"];
                                        $available_targets[$target_id]["questions"][$evaluation_response["equestion_id"]]["comments"][$evaluation_response["eresponse_id"]]["proxy_id"] = $evaluation_response["proxy_id"];
                                    }
                                    if ($evaluation_response["eqresponse_id"] && !isset($available_targets[$target_id]["questions"][$evaluation_response["equestion_id"]]["responses"][$evaluation_response["eqresponse_id"]])) {
                                        $available_targets[$target_id]["questions"][$evaluation_response["equestion_id"]]["responses"][$evaluation_response["eqresponse_id"]] = 0;
                                    }
                                    if ($evaluation_response["eqresponse_id"]) {
                                        $responses[$evaluation_response["eqresponse_id"]]["selections"]++;
                                        $questions[$evaluation_response["equestion_id"]]["selections"]++;
                                        $available_targets[$target_id]["questions"][$evaluation_response["equestion_id"]]["responses"][$evaluation_response["eqresponse_id"]]++;
                                        $available_targets[$target_id]["questions"][$evaluation_response["equestion_id"]]["selections"]++;
                                    }
                                }
                            }
                        }
                    }
                }
            }
			$query = "SELECT `target_title` FROM `evaluations_lu_targets` AS a
						JOIN `evaluation_forms` AS b
						ON a.`target_id` = b.`target_id`
						JOIN `evaluations` AS c
						ON b.`eform_id` = c.`eform_id`
						WHERE c.`evaluation_id` = ".$db->qstr($RECORD_ID);
			$target_title = $db->GetOne($query);
			if ($target_title) {
				$content_type_name = strtolower($target_title);
			} else {
				$content_type_name = "evaluation";
			}
            $query = "SELECT `evaluation_title` FROM `evaluations` 
						WHERE `evaluation_id` = ".$db->qstr($RECORD_ID);
			$evalution_title = $db->GetOne($query);
            if (!$evalution_title) {
				$evalution_title =  "Untitled";
			}
			
			echo "<h1>Evaluation Details</h1>";
            echo "<h2>".ucwords($content_type_name)." Results for: ".$selected_target["target_title"]."</h2>";
            echo "<h3>".$evalution_title."</h3>";
            echo "<div id=\"evaluation-report-body\">\n";
			$count = 0;
			foreach ($questions as $question) {
				$count++;
				if ($count > 1) {
                    echo "<div class=\"row-fluid border-above-medium\">&nbsp;</div>\n";
				}
				echo "	<div class=\"row-fluid\">\n";
				echo "		<span class=\"span10 row-fluid\">\n";
				echo "			<h3 class=\"span3\">Question #".$count.":</h3>\n";
				echo "			<span class=\"span9 space-above medium question-text\">".$question["question_text"]."</span>\n";
				echo "		</span>\n";
				echo "	</div>\n";
				$total_selections = 0;
				if (isset($question["response_ids"]) && $question["response_ids"]) { 
					echo "	<br />\n";
					echo "	<div class=\"row-fluid responses-text\" style=\"font-weight: bold;\">\n";
					echo "		<span class=\"span8 border-below\">Response</span>\n";
					echo "		<span class=\"span2 border-below\">Percent</span>\n";
					echo "		<span class=\"span2 border-below\">Selections</span>\n";
					echo "	</div>\n";
					foreach ($question["response_ids"] as $response_id) {
						$total_selections += $responses[$response_id]["selections"];
					}
					foreach ($question["response_ids"] as $response_id) {
						$selections = (isset($available_targets[$selected_target_id]["questions"][$question["equestion_id"]]["responses"][$response_id]) ? $available_targets[$selected_target_id]["questions"][$question["equestion_id"]]["responses"][$response_id] : 0);
						echo "	<div class=\"row-fluid responses-text\">\n";
						echo "		<span class=\"span8\">".(array_search($response_id, $criteria_response_ids) !== false ? "<a class=\"criteria-tooltip\" id=\"tooltip-".$response_id."\" href=\"#criteria-".$response_id."\">".$responses[$response_id]["response_text"]."</a>" : $responses[$response_id]["response_text"])."</span>\n";
						echo "		<span class=\"span2\">".(isset($available_targets[$selected_target_id]["questions"][$question["equestion_id"]]["responses"]) && count($available_targets[$selected_target_id]["questions"][$question["equestion_id"]]["responses"]) ? round(($selections / $available_targets[$selected_target_id]["questions"][$question["equestion_id"]]["selections"] * 100), 1) : 0)."%</span>\n";
						echo "		<span class=\"span2\">".$selections."</span>\n";
						echo "	</div>\n";
					}
					if ($evaluation["show_comments"] || $permissions[0]["contact_type"] == "reviewer") {
						if (isset($available_targets[$selected_target_id]["questions"][$question["equestion_id"]]["comments"]) && count($available_targets[$selected_target_id]["questions"][$question["equestion_id"]]["comments"])) {
							echo "	<div class=\"row-fluid comments-row\" style=\"padding-top: 20px;\">&nbsp;</div>\n";
							echo "	<div class=\"row-fluid comments-row\">\n";
                            echo "  <ul>\n";
							$temp_responses = array();
							foreach ($question["response_ids"] as $response_id) {
								foreach ($available_targets[$selected_target_id]["questions"][$question["equestion_id"]]["comments"] as $comment) {
									if ($comment["eqresponse_id"] == $response_id) {
										if (!array_key_exists($response_id, $temp_responses)) {
											$temp_responses[$response_id]["response"] = $responses[$response_id]["response_text"];
											$temp_responses[$response_id]["comments"] = array();
										}
										$temp_responses[$response_id]["comments"][] = $comment["text"];
										$temp_responses[$response_id]["proxy_ids"][] = $comment["proxy_id"];
									}
								}
							}
							foreach ($temp_responses as $response) {
								echo "      <li>".$response["response"].":\n";
                                echo "          <ul>\n";
								foreach ($response["comments"] as $index => $comment) {
									echo "              <li>".($evaluation["identify_comments"] ? "<strong>".get_account_data("wholename", $response["proxy_ids"][$index])."</strong>: " : "").$comment."</li>\n";
								}
								echo "          </ul>\n";
                                echo "      </li>\n";
							}
							echo "	</ul>\n";
                            echo "</div>\n";
						}
					}
				} elseif (($evaluation["show_comments"] || $permissions[0]["contact_type"] == "reviewer") && isset($available_targets[$selected_target_id]["questions"][$question["equestion_id"]]) && $available_targets[$selected_target_id]["questions"][$question["equestion_id"]]) {
					echo "<div class=\"row-fluid comments-row\">\n";
					echo "	<span style=\"padding-top: 20px;\">Comments:</span>";
					echo "</div>\n";
					echo "<div class=\"row-fluid comments-row\">\n";
                    echo "  <ul>";
					$temp_responses = array();
					foreach ($available_targets[$selected_target_id]["questions"][$question["equestion_id"]]["comments"] as $eresponse_id => $comment) {
						if ($comment["equestion_id"] == $question["equestion_id"]) {
							echo "      <li>".($evaluation["identify_comments"] ? "<strong>".get_account_data("wholename", $comment["proxy_id"])."</strong>: " : "").$comment["text"]."</li>\n";
						}
					}
					echo "	</ul>\n";
                    echo "</div>\n";
				}
			}
            echo "</div>\n";
			if ($evaluation["show_comments"] || $permissions[0]["contact_type"] == "reviewer") {
				$HEAD[] = "
							<script type=\"text/javascript\">
								function toggleComments () {
									if ($('comments-display').hasClassName('on')) {
										$('comments-display').removeClassName('on');
										$('comments-display').addClassName('off');
										jQuery('#comments-display > a').text('Show Comments');
										jQuery('.comments-row').hide();
									} else {
										$('comments-display').removeClassName('off');
										$('comments-display').addClassName('on');
										jQuery('#comments-display > a').text('Hide Comments');
										jQuery('.comments-row').show();
									}
								}
							</script>";
				$sidebar_html  = "<ul class=\"menu\">\n";
				$sidebar_html .= "	<li id=\"comments-display\" class=\"on\" style=\"cursor: pointer;\"><a onclick=\"toggleComments()\">Hide Comments</a></li>\n";
				$sidebar_html .= "</ul>\n";

				new_sidebar_item("Show/Hide Comments", $sidebar_html, "view-comments", "open", "1.9");
			}
		}
	} else {
		$ERROR++;
		$ERRORSTR[] = "In order to review an evaluation, you must provide a valid evaluation identifier.";

		echo display_error();

		application_log("error", "Failed to provide a valid evaluation_id identifer [".$RECORD_ID."] when attempting to review an evaluation.");
	}
} else {
	$ERROR++;
	$ERRORSTR[] = "In order to review an evaluation, you must provide a valid evaluation identifier.";

	echo display_error();

	application_log("error", "Failed to provide an evaluation_id identifier when attempting to review an evaluation.");
}