<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Used to list all available polls within this page of a community.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Andrew Dos-Santos <andrew.dos-santos@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("COMMUNITY_INCLUDED")) || (!defined("IN_QUIZZES"))) {
    exit;
} elseif (!$COMMUNITY_LOAD) {
	exit;
}
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/elementresizer.js\"></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/wizard.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[] = "<link href=\"".ENTRADA_URL."/css/wizard.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
$HEAD[] = "<link href=\"".ENTRADA_RELATIVE."/javascript/calendar/css/xc2_default.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/livepipe/livepipe.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/livepipe/window.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/calendar/config/xc2_default.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/calendar/script/xc2_inpage.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
?>
<iframe id="upload-frame" name="upload-frame" onload="frameLoad()" style="display: none;"></iframe>
<a id="false-link" href="#placeholder"></a>
<div id="placeholder" style="display: none"></div>
<div id="module-header">
</div>
<script type="text/javascript">
	var ajax_url = '';
	var modalDialog;
	document.observe('dom:loaded', function() {
		modalDialog = new Control.Modal($('false-link'), {
			position:		'center',
			overlayOpacity:	0.75,
			closeOnClick:	'overlay',
			className:		'modal',
			fade:			true,
			fadeDuration:	0.30,
			beforeOpen: function(request) {
				eval($('scripts-on-open').innerHTML);
			},
			afterClose: function() {
				if (uploaded == true) {
                    location.reload();
				}
			}
		});
	});

	function openDialog (url) {
		if (url) {
			ajax_url = url;
			new Ajax.Request(ajax_url, {
				method: 'get',
				onComplete: function(transport) {
					modalDialog.container.update(transport.responseText);
					modalDialog.open();
				}
			});
		} else {
			$('scripts-on-open').update();
			modalDialog.open();
		}
	}
</script>
<?php
if ($COMMUNITY_ADMIN) {
    ?>
    <div style="float: right; margin-bottom: 5px">
        <ul class="page-action">
            <li><a href="<?php echo ENTRADA_URL; ?>/admin/quizzes?section=add" class="btn btn-success">Create New Quiz</a></li>
            <li><a href="#" onclick="openDialog('<?php echo ENTRADA_URL; ?>/api/quiz-wizard.api.php?type=community_page&action=add&id=<?php echo $PAGE_ID; ?>')" class="btn btn-success">Attach Existing Quiz</a></li>
        </ul>
    </div>
    <div class="clear"></div>
    <?php
}
?>
<div style="padding-top: 10px; clear: both">
    <div class="section-holder">
    <?php
	/**
	 * This query will retrieve all of the quizzes associated with this evevnt.
	 */
	$query	= "	SELECT a.*, b.`quiztype_code`, b.`quiztype_title`
				FROM `attached_quizzes` AS a
				LEFT JOIN `quizzes_lu_quiztypes` AS b
				ON b.`quiztype_id` = a.`quiztype_id`
				WHERE a.`content_type` = 'community_page'
				AND a.`content_id` = ".$db->qstr($PAGE_ID)."
				GROUP BY a.`aquiz_id`
				ORDER BY a.`required` DESC, a.`quiz_title` ASC, a.`release_until` ASC";
	$quizzes = $db->GetAll($query);
	if ($quizzes) {
        foreach ($quizzes as $quiz_record) {
            $quiz_attempts = 0;
            $total_questions = quiz_count_questions($quiz_record["quiz_id"]);

            if ($LOGGED_IN) {
                $query = "	SELECT *
							FROM `quiz_progress`
							WHERE `aquiz_id` = ".$db->qstr($quiz_record["aquiz_id"])."
							AND `proxy_id` = ".$db->qstr($ENTRADA_USER->getID());
                $progress_records	= $db->GetAll($query);

                if ($progress_records) {
                    $quiz_attempts = count($progress_records);
                    $quiz_record["last_visited"] = 0;
                    foreach ($progress_records as $progress_record) {
                        if ($progress_record["updated_date"] > $quiz_record["last_visited"]) {
                            $quiz_record["last_visited"] = $progress_record["updated_date"];
                        }
                    }
                } else {
                    $quiz_attempts = 0;
                }
            }

            $exceeded_attempts	= ((((int) $quiz_record["quiz_attempts"] === 0) || ($quiz_attempts < $quiz_record["quiz_attempts"])) ? false : true);

            if ($LOGGED_IN && ((!(int) $quiz_record["release_date"]) || ($quiz_record["release_date"] <= time())) && ((!(int) $quiz_record["release_until"]) || ($quiz_record["release_until"] >= time())) && (!$exceeded_attempts)) {
                $allow_attempt = true;
            } else {
                $allow_attempt = false;
            }

            $attempt_url = ENTRADA_URL."/community".$COMMUNITY_URL.":".$PAGE_URL."?section=attempt&amp;community=true&amp;id=".$quiz_record["aquiz_id"];
            $results_url = ENTRADA_URL."/admin/quizzes?section=results&amp;community=true&amp;id=".$quiz_record["aquiz_id"];
            ?>

            <div id="quiz-<?php echo $quiz_record["aquiz_id"]; ?>">
                <div class="row-fluid space-below quiz-header-row">
                    <div class="span8">
                        <span class="quiz-header-title">
                            <?php
                            if ($allow_attempt) {
                                ?>
                                <a href="<?php echo $attempt_url; ?>" title="Take <?php echo html_encode($quiz_record["quiz_title"]); ?>">
                                    <?php echo html_encode($quiz_record["quiz_title"]); ?>
                                </a>
                                <?php
                            } else {
                                ?>
                                    <span class="attempt-not-allowed"><?php echo html_encode($quiz_record["quiz_title"]); ?></span>
                                <?php
                            }
                            ?>
                        </span>
                    </div>

                    <div class="span4 quiz-icons">
                        <div class="pull-right">
                            <?php
                            if ($COMMUNITY_ADMIN && $_SESSION["details"]["group"] != "student") {
                                ?>
                                <span class="fa-stack fa-md quiz-header" title="Edit <?php echo html_encode($quiz_record["quiz_title"]); ?>">
                                    <a href="#" onclick="openDialog('<?php echo ENTRADA_URL."/api/quiz-wizard.api.php?action=edit&type=community_page&id=".$PAGE_ID."&qid=".$quiz_record["aquiz_id"]; ?>')">
                                        <i class="fa fa-square-o fa-stack-2x quiz-header primary"></i>
                                        <i class="fa fa-pencil fa-stack-1x quiz-header primary"></i>
                                    </a>
                                </span>

                                <span class="fa-stack fa-md quiz-header" title="View Results">
                                    <a href="<?php echo $results_url; ?>">
                                        <i class="fa fa-square-o fa-stack-2x quiz-header primary"></i>
                                        <i class="fa fa-bar-chart fa-stack-1x quiz-header primary"></i>
                                    </a>
                                </span>
                                <?php
                            }

                            if ($progress_records) {
                                $temp_progress_records = $progress_records;

                                // Get latest progress record.
                                usort($temp_progress_records, function ($a, $b) {
                                    return $a["updated_date"] < $b["updated_date"];
                                });

                                switch ($temp_progress_records[0]["progress_value"]) {
                                    case "complete" :
                                        ?>
                                        <span class="fa-stack fa-md quiz-header" title="Completed">
                                            <i class=" fa fa-square-o fa-stack-2x quiz-header success"></i>
                                            <i class="fa fa-check fa-stack-1x quiz-header success"></i>
                                        </span>
                                        <?php
                                    break;
                                    case "inprogress" :
                                        ?>
                                        <span class="fa-stack fa-md quiz-header" title="In Progress">
                                            <i class=" fa fa-square-o fa-stack-2x quiz-header warning"></i>
                                            <i class="fa fa-exclamation fa-stack-1x quiz-header warning"></i>
                                        </span>
                                        <?php
                                    break;
                                    case "expired" :
                                        ?>
                                        <span class="fa-stack fa-md quiz-header" title="Expired">
                                            <i class=" fa fa-square-o fa-stack-2x quiz-header danger"></i>
                                            <i class="fa fa-remove fa-stack-1x quiz-header danger"></i>
                                        </span>
                                        <?php
                                    break;
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <div class="row-fluid">
                    <?php
                    $quiz_description = "";

                    if ((int) $quiz_record["release_date"] && (int) $quiz_record["release_until"]) {
                        $quiz_description .= "<p>This quiz " . ($quiz_record["release_until"] > time() ? "is" : "was only") .  " available from <strong>".date(DEFAULT_DATE_FORMAT, html_encode($quiz_record["release_date"]))."</strong> to <strong>".date(DEFAULT_DATE_FORMAT, html_encode($quiz_record["release_until"]))."</strong>.</p>";
                    } elseif ((int) $quiz_record["release_date"]) {
                        if ($quiz_record["release_date"] > time()) {
                            $quiz_description .= "<p>You will be able to attempt this quiz starting <strong>".date(DEFAULT_DATE_FORMAT, html_encode($quiz_record["release_date"]))."</strong>.</p>";
                        } else {
                            $quiz_description .= "<p>This quiz has been available since <strong>".date(DEFAULT_DATE_FORMAT, html_encode($quiz_record["release_date"]))."</strong>.</p>";
                        }
                    } elseif ((int) $quiz_record["release_until"]) {
                        if ($quiz_record["release_until"] > time()) {
                            $quiz_description .= "<p>You will be able to attempt this quiz until <strong>".date(DEFAULT_DATE_FORMAT, html_encode($quiz_record["release_until"]))."</strong>.</p>";
                        } else {
                            $quiz_description .= "<p>This quiz was only available until <strong>".date(DEFAULT_DATE_FORMAT, html_encode($quiz_record["release_until"]))."</strong>. Please contact a teacher for assistance if required.</p>";
                        }
                    } else {
                        $quiz_description .= "<p>This quiz is available indefinitely.</p>";
                    }

                    $quiz_description .= "<p>".quiz_generate_description($quiz_record["required"], $quiz_record["quiztype_code"], $quiz_record["quiz_timeout"], $total_questions, $quiz_record["quiz_attempts"], $quiz_record["timeframe"])."</p>";

                    echo $quiz_description;
                    ?>
                </div>
            </div>

            <?php
            if ($progress_records) {
                echo "<strong>Your Attempts</strong>";
                echo "<ul class=\"menu\">";
                foreach ($progress_records as $entry) {
                    $quiz_start_time	= $entry["updated_date"];
                    $quiz_end_time		= (((int) $quiz_record["quiz_timeout"]) ? ($quiz_start_time + ($quiz_record["quiz_timeout"] * 60)) : 0);

                    /**
                     * Checking for quizzes that are expired, but still in progress.
                     */
                    if (($entry["progress_value"] == "inprogress") && ((((int) $quiz_record["release_until"]) && ($quiz_record["release_until"] < time())) || (($quiz_end_time) && (time() > ($quiz_end_time + 30))))) {
                        $quiz_progress_array	= array (
                            "progress_value" => "expired",
                            "quiz_score" => "0",
                            "quiz_value" => "0",
                            "updated_date" => time(),
                            "updated_by" => $ENTRADA_USER->getID()
                        );
                        if (!$db->AutoExecute("quiz_progress", $quiz_progress_array, "UPDATE", "qprogress_id = ".$db->qstr($entry["qprogress_id"]))) {
                            application_log("error", "Unable to update the qprogress_id [".$qprogress_id."] to expired. Database said: ".$db->ErrorMsg());
                        }
                        $entry["progress_value"] = "expired";
                    }

                    switch ($entry["progress_value"]) {
                        case "complete" :
                            if (($quiz_record["quiztype_code"] != "delayed") || ($quiz_record["release_until"] <= time())) {
                                $percentage = ((round(($entry["quiz_score"] / $entry["quiz_value"]), 2)) * 100);
                                echo "<li class=\"".(($percentage >= 60) ? "correct" : "incorrect")."\">";
                                echo	date(DEFAULT_DATE_FORMAT, $entry["updated_date"])." <strong>Score:</strong> ".$entry["quiz_score"]."/".$entry["quiz_value"]." (".$percentage."%)";
                                echo "	( <a href=\"".ENTRADA_URL."/community".$COMMUNITY_URL.":".$PAGE_URL."?section=results&amp;id=".$entry["qprogress_id"]."\">review quiz</a> )";
                                echo "</li>";
                            } else {
                                echo "<li>".date(DEFAULT_DATE_FORMAT, $entry["updated_date"])." <strong>Score:</strong> To Be Released ".date(DEFAULT_DATE_FORMAT, $quiz_record["release_until"])."</li>";
                            }
                            break;
                        case "expired" :
                            echo "<li class=\"incorrect\">".date(DEFAULT_DATE_FORMAT, $entry["updated_date"])." <strong>Expired Attempt</strong>: not completed.</li>";
                            break;
                        case "inprogress" :
                            echo "<li>".date(DEFAULT_DATE_FORMAT, $entry["updated_date"])." <strong>Attempt In Progress</strong> ( <a href=\"".ENTRADA_URL."/community".$COMMUNITY_URL.":".$PAGE_URL."?section=attempt&amp;community=true&amp;id=".$quiz_record["aquiz_id"]."\">continue quiz</a> )</li>";
                            break;
                        default :
                            continue;
                            break;
                    }
                }
                echo "</ul>";
            }

            echo "<hr>";
        }
	} else {
        echo display_generic("There are currently no online quizzes available on this page.");
	}
	?>
    </div>
</div>