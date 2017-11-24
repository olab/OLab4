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
 * This file displays the list of all quizzes available to the particular
 * individual who is accessing this file.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_GRADEBOOK"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed('quiz', 'update', false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    if ($ASSESSMENT_ID) {
        $query = "SELECT * FROM `assessments`
                    WHERE `assessment_id` = ".$db->qstr($ASSESSMENT_ID)."
                    AND `active` = '1'";
        $assessment = $db->GetRow($query);
        if ($assessment) {
            $BREADCRUMB[] = array("url" => ENTRADA_URL . "/admin/gradebook/assessment?" . replace_query(array("section" => "attach-quiz", "step" => false)), "title" => "Attach Quiz");

            switch ($STEP) {
                case 2 :
                    if ((isset($_GET["aquiz_id"])) && ($aquiz_id = clean_input($_GET["aquiz_id"], array("int")))) {
                       $query = "SELECT a.*, c.`content_type`, c.`content_id`, h.`member_acl` AS `community_admin`, e.`course_id`, i.`organisation_id`
                                    FROM `quizzes` AS a
                                    LEFT JOIN `quiz_contacts` AS b
                                    ON a.`quiz_id` = b.`quiz_id`
                                    JOIN `attached_quizzes` AS c
                                    ON a.`quiz_id` = c.`quiz_id`
                                    LEFT JOIN `assessment_attached_quizzes` AS d
                                    ON d.`assessment_id` = ".$db->qstr($ASSESSMENT_ID)."
                                    AND d.`aquiz_id` = c.`aquiz_id`
                                    LEFT JOIN `events` AS e
                                    ON c.`content_type` = 'event'
                                    AND c.`content_id` = e.`event_id`
                                    LEFT JOIN `community_pages` AS f
                                    ON c.`content_type` = 'community_page'
                                    AND c.`content_id` = f.`cpage_id`
                                    LEFT JOIN `communities` AS g
                                    ON f.`community_id` = g.`community_id`
                                    LEFT JOIN `community_members` AS h
                                    ON g.`community_id` = h.`community_id`
                                    AND h.`proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
                                    AND h.`member_active` = 1
                                    LEFT JOIN `courses` AS i
                                    ON e.`course_id` = i.`course_id`
                                    WHERE c.`aquiz_id` = ".$db->qstr($aquiz_id)."
                                    AND d.`aquiz_id` IS NULL
                                    GROUP BY a.`quiz_id`";
                        $quiz = $db->GetRow($query);
                        if ($quiz) {
                            if ((($quiz["content_type"] == "event" && $ENTRADA_ACL->amIAllowed(new EventContentResource($quiz["content_id"], $quiz["course_id"], $quiz["organisation_id"]), "update")) || ($quiz["content_type"] == "community_page" && $quiz["community_admin"]))) {
                            $PROCESSED["quiz_title"]    = $quiz["quiz_title"];
                            $PROCESSED["aquiz_id"]       = $aquiz_id;
                        } else {
                            $ERROR++;
                                $ERRORSTR[] = "You do not have permission to view the results for the <strong>Quiz</strong> you selected.";
                            }
                        } else {
                            $ERROR++;
                            $ERRORSTR[] = "The <strong>Quiz</strong> you selected does not exist or is not enabled.";
                        }
                    } else {
                        $ERROR++;
                        $ERRORSTR[] = "Please select a <strong>Quiz</strong> to attach to the assessment.";
                    }

					if (!$ERROR) {
                        $PROCESSED["assessment_id"]	= (int) $ASSESSMENT_ID;
                        $PROCESSED["content_type"]  = "assessment";
						$PROCESSED["updated_date"]	= time();
						$PROCESSED["updated_by"]	= $ENTRADA_USER->getID();                        
						/**
						* Adding this quiz to the selected assessment.
						*/
						if ($db->AutoExecute("assessment_attached_quizzes", $PROCESSED, "INSERT")) {
							$url = ENTRADA_URL."/admin/gradebook/assessments?section=edit&id=".$assessment["course_id"]."&assessment_id=".$ASSESSMENT_ID;
							$SUCCESS++;
							$SUCCESSSTR[]	= "You have successfully attached <strong>".html_encode($quiz["quiz_title"])."</strong> to <strong>".$assessment["name"]."</strong>.";

							application_log("success", "Quiz [".$PROCESSED["aquiz_id"]."] was successfully attached to assessment [".$ASSESSMENT_ID."].");

						} else {
							$url = ENTRADA_URL."/admin/gradebook/assessments?section=edit&id=".$assessment["course_id"]."&assessment_id=".$ASSESSMENT_ID;
							$ERROR++;
							$ERRORSTR[] = "There was a problem attaching this quiz to <strong>".html_encode($assessment["name"])."</strong>. The system administrator was informed of this error; please try again later.";

							application_log("error", "There was an error attaching quiz [".$PROCESSED["aquiz_id"]."] to assessment [".$ASSESSMENT_ID."]. Database said: ".$db->ErrorMsg());
						}
                        if ($SUCCESS) {
							$url = ENTRADA_URL."/admin/gradebook/assessments?section=edit&id=".$assessment["course_id"]."&assessment_id=".$ASSESSMENT_ID;
                            $ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";

                            $SUCCESSSTR[(count($SUCCESSSTR) - 1)] .= "<br /><br />You will now be redirected back to the assessment edit page; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
                        } elseif ($ERROR) {
                            $STEP = 1;
                        }
                    } else {
                        $STEP = 1;
                    }
                break;
                case 1 :
                default :
                break;
            }
            
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
                default :
                    ?>
                    <div class="alert alert-info">Please select the quiz you would like to attach to this assessment:</div>
                    
                    <?php
                    if($ERROR) {
                        echo display_error();
                    }

                    if($NOTICE) {
                        echo display_notice();
                    }
                    ?>

                    <div style="float: right; padding-bottom: 10px;">


                        <?php
                        if ($total_pages > 1) {
                            echo "<form action=\"".ENTRADA_URL."/admin/gradebook/assessments\" method=\"get\" id=\"pageSelector\">\n";
                            foreach ($_GET as $name => $value) {
                                if ($name !== "step" && $name !== "pv") {
                                    echo "<input type=\"hidden\" name=\"".html_encode($name)."\" value=\"".html_encode($value)."\" />";
                                }
                            }
                            echo "<span style=\"width: 20px; vertical-align: middle; margin-right: 3px; text-align: left\">\n";
                            if ($page_previous) {
                                echo "<a href=\"".ENTRADA_URL."/admin/gradebook/assessments?".replace_query(array("pv" => $page_previous))."\"><img src=\"".ENTRADA_URL."/images/record-previous-on.gif\" border=\"0\" width=\"11\" height=\"11\" alt=\"Back to page ".$page_previous.".\" title=\"Back to page ".$page_previous.".\" style=\"vertical-align: middle\" /></a>\n";
                            } else {
                                echo "<img src=\"".ENTRADA_URL."/images/record-previous-off.gif\" width=\"11\" height=\"11\" alt=\"\" title=\"\" style=\"vertical-align: middle\" />";
                            }
                            echo "</span>";
                            echo "<span style=\"vertical-align: middle\">\n";
                            echo "<select name=\"pv\" onchange=\"$('pageSelector').submit();\"".(($total_pages <= 1) ? " disabled=\"disabled\"" : "").">\n";
                            for($i = 1; $i <= $total_pages; $i++) {
                                echo "<option value=\"".$i."\"".(($i == $page_current) ? " selected=\"selected\"" : "").">".(($i == $page_current) ? " Viewing" : "Jump To")." Page ".$i."</option>\n";
                            }
                            echo "</select>\n";
                            echo "</span>\n";
                            echo "<span style=\"width: 20px; vertical-align: middle; margin-left: 3px; text-align: right\">\n";
                            if ($page_current < $total_pages) {
                                echo "<a href=\"".ENTRADA_URL."/admin/gradebook/assessments?".replace_query(array("pv" => $page_next))."\"><img src=\"".ENTRADA_URL."/images/record-next-on.gif\" border=\"0\" width=\"11\" height=\"11\" alt=\"Forward to page ".$page_next.".\" title=\"Forward to page ".$page_next.".\" style=\"vertical-align: middle\" /></a>";
                            } else {
                                echo "<img src=\"".ENTRADA_URL."/images/record-next-off.gif\" width=\"11\" height=\"11\" alt=\"\" title=\"\" style=\"vertical-align: middle\" />";
                            }
                            echo "</span>\n";
                            echo "</form>\n";
                        }
                        ?>
                    </div>
                    <div class="clear"></div>
                    <?php
                    $query	= "	SELECT a.*, d.*, COUNT(DISTINCT c.`qquestion_id`) AS `question_total`,
                                    CASE
                                        WHEN f.`event_title` IS NOT NULL 
                                            THEN CONCAT('Event [', f.`event_title`, ' - ', DATE(FROM_UNIXTIME(f.`event_start`)), ']')
                                        WHEN g.`page_url` IS NOT NULL 
                                            THEN CONCAT('Community Page: ', h.`community_title`, ' [', g.`page_url`, ']')
                                    END AS `content_title`,
                                i.`member_acl` AS `community_admin`, f.`course_id`, j.`organisation_id`
                                FROM `quizzes` AS a
                                LEFT JOIN `quiz_contacts` AS b
                                ON a.`quiz_id` = b.`quiz_id`
                                JOIN `quiz_questions` AS c
                                ON a.`quiz_id` = c.`quiz_id`
                                AND c.`question_active` = 1
                                JOIN `attached_quizzes` AS d
                                ON a.`quiz_id` = d.`quiz_id`
                                LEFT JOIN `assessment_attached_quizzes` AS e
                                ON e.`assessment_id` = ".$db->qstr($ASSESSMENT_ID)."
                                AND e.`aquiz_id` = d.`aquiz_id`
                                LEFT JOIN `events` AS f
                                ON d.`content_type` = 'event'
                                AND d.`content_id` = f.`event_id`
                                LEFT JOIN `community_pages` AS g
                                ON d.`content_type` = 'community_page'
                                AND d.`content_id` = g.`cpage_id`
                                LEFT JOIN `communities` AS h
                                ON g.`community_id` = h.`community_id`
                                LEFT JOIN `community_members` AS i
                                ON h.`community_id` = i.`community_id`
                                AND i.`proxy_id` = ".$db->qstr($ENTRADA_USER->getID())."
                                AND i.`member_active` = 1
                                LEFT JOIN `courses` AS j
                                ON f.`course_id` = j.`course_id`
                                WHERE e.`aquiz_id` IS NULL
                                GROUP BY d.`aquiz_id`
                                ORDER BY a.`quiz_title`";
                    $results	= $db->GetAll($query);
                    if ($results) {
                        $HEAD[] = "
                                    <script type='text/javascript'>
                                    jQuery(document).ready(function () {
                                        jQuery('.tooltipper').qtip({tip: true});
                                    });
                                    </script>";
                        $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery.dataTables.min.js\"></script>";
                        $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery.qtip.min.js\"></script>";
                        $HEAD[] = "<script type=\"text/javascript\">
                        jQuery(document).ready(function() {
                            jQuery('#attachedQuizzes').dataTable(
                                {
                                    'sPaginationType': 'full_numbers',
                                    'bInfo': false,
                                    'bAutoWidth': false
                                }
                            );
                        });
                        </script>";
                        ?>
                        <form action="<?php echo ENTRADA_URL; ?>/admin/gradebook/assessments?<?php echo replace_query(array("step" => 2)); ?>" method="post">
                        <table id="attachedQuizzes" class="tableList" cellspacing="0" summary="List of Quizzes">
                        <colgroup>
                            <col class="modified" />
                            <col class="title" />
                            <col class="title" />
                            <col class="type" />
                        </colgroup>
                        <thead>
                            <tr>
                                <td class="modified">&nbsp;</td>
                                <td class="title">Quiz Title</td>
                                <td class="title">Quiz Location</td>
                                <td class="type">Quiz Questions</td>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ($results as $result) {
                                if ((($result["content_type"] == "event" && $ENTRADA_ACL->amIAllowed(new EventContentResource($result["content_id"], $result["course_id"], $result["organisation_id"]), "update")) || ($result["content_type"] == "community_page" && $result["community_admin"]))) {
                                echo "<tr id=\"quiz-".$result["aquiz_id"]."\">\n";
                                echo "	<td>&nbsp;</td>\n";
                                echo "	<td><a href=\"".ENTRADA_URL."/admin/gradebook/assessments?".replace_query(array("aquiz_id" => $result["aquiz_id"], "step" => 2))."\">".html_encode($result["quiz_title"])."</a></td>\n";
                                echo "	<td".(strlen(trim($result["content_title"])) > 48 ? " class=\"tooltipper\" title=\"".html_encode($result["content_title"])."\"" : "").">".html_encode($result["content_title"])."</td>\n";
                                echo "	<td>".html_encode($result["question_total"])."</td>\n";
                                echo "</tr>\n";
                            }
                            }
                            ?>
                        </tbody>
                        </table>
                        </form>
                        <?php
                    } else {
                        ?>
                        <div class="display-generic">
                            There are currently no available quizzes in the system which you can attach to this assessment. To gain access to a quiz for attaching to an assessment, an author of the quiz must grant you access by adding you as an additional author for the quiz.
                            <br /><br />
                            Alternatively, you may <strong><a href="<?php echo ENTRADA_URL . "/admin/quizzes?section=add&assessment_id=".$ASSESSMENT_ID; ?>">Create a New Quiz</a></strong> now to attach.
                        </div>
                        <?php
                    }
                break;
            }
        } else {
            add_error("The <strong>Assessment</strong> you selected does not exist or is not enabled.");
            echo display_error();
        }
    } else {
        add_error("A valid <strong>Assessment Identifier</strong> is required to select quizzes for attaching.");
        echo display_error();
    }
}