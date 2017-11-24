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
 * This is the default section that is loaded when the quizzes module is
 * accessed without a defined section.
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

if (isset($_GET["view"]) && $_GET["view"] == "review") {
	$view = "review";
} elseif (isset($_GET["view"]) && $_GET["view"] == "attempt") { 
	$view = "attempt";
} else {
	$view = "index";
}

$evaluations = Classes_Evaluation::getEvaluatorEvaluations($ENTRADA_USER->getId(), $ENTRADA_USER->getActiveOrganisation());
$review_evaluations = Classes_Evaluation::getReviewerEvaluations();
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery.dataTables.min.js\"></script>";

if (isset($_GET["request"]) && $_GET["request"]) {
	if (isset($RECORD_ID) && $RECORD_ID) {
		$query = "SELECT * FROM `evaluations` WHERE `evaluation_id` = ".$db->qstr($RECORD_ID);
		$evaluation = $db->GetRow($query);
        if ($evaluation) {
            $evaluation_title = $evaluation["evaluation_title"];
            $target_evaluations = Classes_Evaluation::getTargetEvaluations();
            $found = false;
            if ($target_evaluations) {
                foreach ($target_evaluations as $target_evaluation) {
                    if ($target_evaluation["evaluation_id"] == $RECORD_ID) {
                        $found = true;
                    }
                }
            }
            if ($evaluation_title && $found) {
                if (isset($_POST["associated_evaluator"]) && ($associated_evaluator = clean_input($_POST["associated_evaluator"], array("trim", "int")))) {
                    $notifications_sent = 0;
                    $query = "SELECT *, COUNT(a.`evaluation_id`) AS `completed_evaluations` FROM `evaluation_progress` AS a
                                JOIN `evaluations` AS b
                                ON a.`evaluation_id` = b.`evaluation_id`
                                WHERE a.`evaluation_id` = ".$db->qstr($RECORD_ID)."
                                AND a.`proxy_id` = ".$db->qstr($associated_evaluator)."
                                AND a.`progress_value` = 'complete'
                                GROUP BY a.`evaluation_id`";
                    $all_targets_progress = $db->GetRow($query);
                    if (!$all_targets_progress || $all_targets_progress["max_submittable"] == 0 || $all_targets_progress["completed_evaluations"] < $all_targets_progress["max_submittable"]) {
                        $query = "SELECT * FROM `evaluation_progress` AS a
                                    JOIN `evaluations` AS b
                                    ON a.`evaluation_id` = b.`evaluation_id`
                                    WHERE a.`evaluation_id` = ".$db->qstr($RECORD_ID)."
                                    AND a.`proxy_id` = ".$db->qstr($associated_evaluator)."
                                    AND a.`target_record_id` = ".$db->qstr($ENTRADA_USER->getId())."
                                    AND a.`progress_value` = 'complete'
                                    GROUP BY a.`evaluation_id`";
                        $evaluation_progress = $db->GetRow($query);
                        if (!$evaluation_progress || $evaluation_progress["allow_repeat_targets"] == 1) {

                            $PROCESSED_REQUEST = array();
                            if ($evaluation["require_request_code"]) {
                                $PROCESSED_REQUEST["request_code"] = strtoupper(substr(md5($ENTRADA_USER->getId()."-".time()), 0, 6));
                            } else {
                                $PROCESSED_REQUEST["request_code"] = NULL;
                            }

                            if ($evaluation["request_timeout"]) {
                                $PROCESSED_REQUEST["request_expires"] = (time() + ($evaluation["request_timeout"] * 60));
                            } else {
                               $PROCESSED_REQUEST["request_expires"] = 0;
                            }

                            $PROCESSED_REQUEST["evaluation_id"] = $RECORD_ID;
                            $PROCESSED_REQUEST["proxy_id"] = $ENTRADA_USER->getId();
                            $PROCESSED_REQUEST["target_proxy_id"] = $associated_evaluator;
                            $PROCESSED_REQUEST["request_created"] = time();
                            $PROCESSED_REQUEST["request_fulfilled"] = 0;

                            if ($db->AutoExecute("evaluation_requests", $PROCESSED_REQUEST, "INSERT") && ($request_id = $db->Insert_Id())) {
                                require_once("Classes/notifications/Notification.class.php");
                                require_once("Classes/notifications/NotificationUser.class.php");
                                $notification_user = NotificationUser::get($associated_evaluator, "evaluation_request", $RECORD_ID, $ENTRADA_USER->getId());
                                if (!$notification_user) {
                                    $notification_user = NotificationUser::add($associated_evaluator, "evaluation_request", $RECORD_ID, $ENTRADA_USER->getId());
                                }
                                if (Notification::add($notification_user->getID(), $ENTRADA_USER->getId(), $RECORD_ID)) {
                                    $notifications_sent++;
                                } else {
                                    add_error("An issue was encountered while attempting to send a notification to a user [".get_account_data("wholename", $associated_evaluator)."] requesting that they complete an evaluation [".$evaluation_title."] for you. The system administrator has been notified of this error, please try again later.");
                                    application_log("Unable to send notification requesting an evaluation be completed to evaluator [".$associated_evaluator."] for evaluation_id [".$RECORD_ID."].");
                                }
                            } else {
                                add_error("Unable to create a request entry for this evaluation. The system administrator was notified of this error; please try again later.");
                                application_log("Unable to create a request entry for this evaluator [".$associated_evaluator."] for evaluation_id [".$RECORD_ID."]. Database said: ".$db->ErrorMsg());
                            }
                        } else {
                            add_error("The selected evaluator [".get_account_data("wholename", $associated_evaluator)."] has already completed this evaluation [".$evaluation_title."] for you, and is unable to attempt it again.");
                        }
                    } else {
                        add_error("The selected evaluator [".get_account_data("wholename", $associated_evaluator)."] has already completed this evaluation [".$evaluation_title."] the maximum number of times, and is therefore unable to attempt it again.");
                    }
                } else {
                    add_error("An evaluator must be selected to request an evaluation be completed for you.");
                }
            } else {
                add_error("A valid evaluation must be selected from the drop-down list to request an evaluation be completed for you.");
            }
        } else {
            add_error("A valid evaluation must be selected from the drop-down list to request an evaluation be completed for you.");
        }
	} else {
		add_error("An evaluation must be selected from the drop-down list to request an evaluation be completed for you.");
	}
	if (has_error()) {
		echo display_error();
	}
	if (isset($notifications_sent) && $notifications_sent) {
		add_success("Successfully requested that ".($notifications_sent > 1 ? $notifications_sent." evaluators" : get_account_data("wholename", $associated_evaluator))." fill out this evaluation [".$evaluation_title."] for you.");
		echo display_success();
	}
}

$evaluation_requests = Classes_Evaluation::getTargetRequests($ENTRADA_USER->GetID(), false, false, true);
if ($evaluation_requests) {
    $notice_msg = "The following Evaluation Request Codes are still active but unused: <br />";
    foreach ($evaluation_requests as $evaluation_request) {
        $requestee = get_account_data("wholename", $evaluation_request["target_proxy_id"]);
        $notice_msg .= "<br />".$evaluation_request["evaluation_title"]." [".$requestee."]: <strong>".$evaluation_request["request_code"]."</strong>";
    }
    add_notice($notice_msg);
    echo display_notice();
}

if ($evaluations && $view != "review") {
	?>
	<h1><?php echo $translate->_("My Clerkship Evaluations"); ?></h1>
	<?php
	if ($review_evaluations) {
		$sidebar_html  = "<ul class=\"menu\">\n";
		$sidebar_html .= "	<li class=\"link\"><a href=\"".ENTRADA_URL."/evaluations?view=review\">View Completed Evaluations Available for Review</a></li>\n";
		$sidebar_html .= "</ul>\n";

		new_sidebar_item("Evaluations Review", $sidebar_html, "view-review", "open", "1.9");
	}
	$evaluation_id = 0;
	echo "<div class=\"no-printing\">\n";
    echo "    <ul class=\"nav nav-tabs\">\n";
	echo "		<li class=\"active\" style=\"width:25%;\"><a id=\"available\" onclick=\"loadTab(this.id)\">Display Available</a></li>\n";
	echo "		<li style=\"width:25%;\"><a id=\"overdue\" onclick=\"loadTab(this.id)\">Display Overdue</a></li>\n";
	echo "		<li style=\"width:25%;\"><a id=\"complete\" onclick=\"loadTab(this.id)\">Display Completed</a></li>\n";
	echo "		<li style=\"width:25%;\"><a id=\"all\" onclick=\"loadTab(this.id)\">Display All</a></li>\n";
	echo "	</ul>\n";
	echo "</div>\n";
	echo "<br />";
	$HEAD[] = "<script type=\"text/javascript\">
	var eTable;
	jQuery(document).ready(function() {
		eTable = jQuery('#evaluations').dataTable(
			{    
				'sPaginationType': 'full_numbers',
				'aoColumns' : [ 
						null,
						null,
						{'sType': 'alt-string'},
						null,
						null,
                        { 'bVisible' : false }
					],
				'bInfo': false,
                'bAutoWidth': false
			}
		);
        eTable.fnFilter('available', 5);
	});
	
	function loadTab (value) {
		if (!$(value).hasClassName('active')) {
			new Ajax.Request('".ENTRADA_URL."/evaluations', {
				method: 'get',
				parameters: {
								'view_type': value,
								'ajax': 1
							}
			});
			var filterval = (value == 'all' ? '' : value)
			eTable.fnFilter(filterval, 5);
			$$('li.active').each(function (e) {
				e.removeClassName('active');
			});
			$(value).parentNode.addClassName('active');
		}
	}
	</script>";
	?>
    <table id="evaluations" class="tableList" cellspacing="0" summary="List of Evaluations and Assessments to Attempt">
    <thead>
        <tr>
            <td class="evaluation-type">Type</td>
            <td class="targets">Target(s)</td>
            <td class="date-smallest">Close Date</td>
            <td class="title">Title</td>
            <td class="submitted">Submitted</td>
            <td class="hide">Status</td>
        </tr>
    </thead>
    <tbody>
    <?php
    foreach ($evaluations as $evaluation) {
        if ($evaluation["click_url"]) {
            echo "<tr>\n";
            echo "	<td><a href=\"".$evaluation["click_url"]."\">".(!empty($evaluation["target_title"]) ? $evaluation["target_title"] : "No Type Found")."</a></td>\n";
            echo "	<td><a href=\"".$evaluation["click_url"]."\">".(!empty($evaluation["evaluation_target_title"]) ? $evaluation["evaluation_target_title"] : "No Target")."</a></td>\n";
            echo "	<td><a href=\"".$evaluation["click_url"]."\" alt=\"".$evaluation["evaluation_finish"]."\">".date("M d/y g:ia", $evaluation["evaluation_finish"])."</a></td>\n";
            echo "	<td><a href=\"".$evaluation["click_url"]."\">".html_encode($evaluation["evaluation_title"])."</a></td>\n";
            echo "	<td class=\"text-center\"><a href=\"".$evaluation["click_url"]."\">".($evaluation["completed_attempts"] ? ((int)$evaluation["completed_attempts"]) : "0")."/".($evaluation["max_submittable"] ? ((int)$evaluation["max_submittable"]) : "0")."</a></td>\n";
            echo "	<td class=\"hide\">".($evaluation["max_submittable"] > $evaluation["completed_attempts"] && $evaluation["evaluation_finish"] < time() ? "overdue available" : ($evaluation["max_submittable"] > $evaluation["completed_attempts"] ? "available" : "complete"))."</td>";
            echo "</tr>\n";
        } else {
            echo "<tr>\n";
            echo "	<td class=\"content-small\">".(!empty($evaluation["target_title"]) ? $evaluation["target_title"] : "No Type Found")."</td>\n";
            echo "	<td class=\"content-small\">".(!empty($evaluation["evaluation_target_title"]) ? $evaluation["evaluation_target_title"] : "No Target")."</td>\n";
            echo "	<td class=\"content-small\"><span alt=\"".$evaluation["evaluation_finish"]."\">".date("M d/y g:ia", $evaluation["evaluation_finish"])."</span></td>\n";
            echo "	<td class=\"content-small\">".html_encode($evaluation["evaluation_title"])."</td>\n";
            echo "	<td class=\"content-small text-center\">".($evaluation["completed_attempts"] ? ((int)$evaluation["completed_attempts"]) : "0")."/".($evaluation["max_submittable"] ? ((int)$evaluation["max_submittable"]) : "0")."</td>\n";
            echo "	<td class=\"hide\">".($evaluation["min_submittable"] > $evaluation["completed_attempts"] && $evaluation["evaluation_finish"] < time() ? "overdue available" : ($evaluation["max_submittable"] > $evaluation["completed_attempts"] ? "available" : "complete"))."</td>";
            echo "</tr>\n";
        }
    }
    ?>
    </tbody>
    </table>
	<?php
} elseif ($review_evaluations && $view != "attempt") {
	$HEAD[] = "<script type=\"text/javascript\">
	var eTable;
	jQuery(document).ready(function() {
		eTable = jQuery('#evaluations').dataTable(
			{    
				'sPaginationType': 'full_numbers',
				'aoColumns' : [ 
						null,
						{'sType': 'alt-string'},
						null,
						null
					],
				'bInfo': false,
                'bAutoWidth': false
			}
		);
	});
	</script>";
	?>
	<h1><?php echo $translate->_("Evaluations Available for Review"); ?></h1>
	<?php
	if ($evaluations) {
		$sidebar_html  = "<ul class=\"menu\">\n";
		$sidebar_html .= "	<li class=\"link\"><a href=\"".ENTRADA_URL."/evaluations?view=attempt\">View My Evaluations Available for Completion</a></li>\n";
		$sidebar_html .= "</ul>\n";

		new_sidebar_item("My Evaluations", $sidebar_html, "view-pending", "open", "1.9");
	}
	?>
	<table id="evaluations" class="tableList" cellspacing="0" summary="List of Evaluations and Assessments to Review">
	<thead>
		<tr>
			<td class="type">Type</td>
			<td class="date-small">Close Date</td>
			<td class="title">Title</td>
			<td class="submitted">Submitted</td>
		</tr>
	</thead>
	<tbody>
	<?php
	foreach ($review_evaluations as $evaluation) {
		$url = ENTRADA_URL."/evaluations?section=review&id=".$evaluation["evaluation_id"];

		echo "<tr>\n";
		echo "	<td><a href=\"".$url."\">".(!empty($evaluation["target_title"]) ? $evaluation["target_title"] : "No Type Found")."</a></td>\n";
		echo "	<td><a href=\"".$url."\" alt=\"".$evaluation["evaluation_finish"]."\">".date(DEFAULT_DATE_FORMAT, $evaluation["evaluation_finish"])."</a></td>\n";
		echo "	<td><a href=\"".$url."\">".html_encode($evaluation["evaluation_title"])."</a></td>\n";
		echo "	<td><a href=\"".$url."\">".($evaluation["completed_attempts"] ? ((int)$evaluation["completed_attempts"]) : "0")."</a></td>\n";
		echo "</tr>\n";
	}
	?>
	</tbody>
	</table>
	<?php
} else {
	if (!isset($evaluations) || !$evaluations) {
		if ($review_evaluations) {
			$sidebar_html  = "<ul class=\"menu\">\n";
			$sidebar_html .= "	<li class=\"link\"><a href=\"".ENTRADA_URL."/evaluations?view=review\">View Completed Evaluations Available for Review</a></li>\n";
			$sidebar_html .= "</ul>\n";

			new_sidebar_item("Evaluations Review", $sidebar_html, "view-review", "open", "1.9");
		}
		?>
		<div class="display-generic">
			There are no evaluations or assessments <strong>assigned to you</strong> in the system at this time.
		</div>
		<?php
	}
}


$request_evaluations = array();
$target_evaluations = Classes_Evaluation::getTargetEvaluations();
if ($target_evaluations) {
	foreach ($target_evaluations as $target_evaluation) {
		if (isset($target_evaluation["allow_target_request"]) && $target_evaluation["allow_target_request"]) {
			$request_evaluations[] = $target_evaluation;
		}
	}
}


$query = "SELECT * FROM `evaluation_requests` AS a
            JOIN `evaluations` AS b
            ON a.`evaluation_id` = b.`evaluation_id`
            WHERE `target_proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
            AND `request_code` IS NOT NULL
            AND (
                `request_expires` = 0
                OR `request_expires` > ".$db->qstr(time())."
            )";
$requested_evaluations = $db->GetAll($query);

if (isset($requested_evaluations) && count($requested_evaluations)) {
	$sidebar_html  = "<form method=\"post\" action=\"".ENTRADA_RELATIVE."/evaluations?section=attempt\">\n";
	$sidebar_html .= "	<label class=\"form-nrequired\" for=\"request_code\">Request Code: </label>";
    $sidebar_html .= "  <input type=\"text\" id=\"request_code\" name=\"request_code\">";
	$sidebar_html .= "	<br /><br /><input type=\"submit\" class=\"btn btn-small btn-primary\" value=\"Submit\" />";
	$sidebar_html .= "</form>";

	new_sidebar_item("Fulfill an Evaluation Request", $sidebar_html, "evaluation-request-code", "open", "1.9");
}

if (isset($request_evaluations) && count($request_evaluations)) {
	$HEAD[] = "
	<script type=\"text/javascript\">
		function loadEvaluators(evaluation_id) {
			new Ajax.Updater('request_evaluators', '".ENTRADA_URL."/api/evaluations-request-evaluators.api.php', {
                parameters: {
                    id: evaluation_id
                }, 
                method: 'post'
            });
		}
	</script>";
	$sidebar_html  = "<form method=\"post\" action=\"".ENTRADA_RELATIVE."/evaluations?request=1\">\n";
	$sidebar_html .= "	<label class=\"form-nrequired\" for=\"evaluation_request_id\">Evaluation/Assessment: </label>";
	$sidebar_html .= "	<select style=\"width: 150px; overflow: none;\" name=\"id\" onchange=\"loadEvaluators(this.value)\">";
	$sidebar_html .= "		<option value=\"0\">-- Select an Evaluation or Assessment --</option>";
	foreach ($request_evaluations as $request_evaluation) {
		$sidebar_html .= "		<option value=\"".$request_evaluation["evaluation_id"]."\"".(isset($RECORD_ID) && $RECORD_ID == $request_evaluation["evaluation_id"] ? " selecte=\"selected\"" : "").">".$request_evaluation["evaluation_title"]."</option>";
	}
	$sidebar_html .= "	</select>";
	$sidebar_html .= "  <div id=\"request_evaluators\">&nbsp;</div>";
	$sidebar_html .= "</form>";

	new_sidebar_item("Request an Evaluation", $sidebar_html, "request-evaluation", "open", "1.9");
}