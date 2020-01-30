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
 * This is the first page someone comes to when taking an exam.
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 *
 */

if((!defined("PARENT_INCLUDED")) || (!defined("IN_PUBLIC_EXAMS"))) {
    exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
}

if (isset($_GET["id"])) {
    $post_id = $_GET["id"];
}

$BREADCRUMB[] = array("url" => ENTRADA_URL."/exams/confirmation", "title" => "Exam Post");

$MODULE_TEXT = $translate->_($MODULE);
$SECTION_TEXT = $MODULE_TEXT["exams"]["posts"];

$HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" .  ENTRADA_URL . "/css/exams/exams.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
$HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" .  ENTRADA_URL . "/css/exams/questions.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
$HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" .  ENTRADA_URL . "/css/exams/exams-public-attempt.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
$HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" .  ENTRADA_URL . "/css/jquery/jquery.growl.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";

$HEAD[] = "<script type=\"text/javascript\" src=\"" .  ENTRADA_URL . "/javascript/jquery.growl.js\"></script>";
$HEAD[] = "<script type=\"text/javascript\">var SECTION_TEXT = ". json_encode($SECTION_TEXT) . "</script>";
?>
<script>
    jQuery(document).ready(function($) {
        var post_id = <?php echo $post_id;?>;

        $("#start_new_attempt").click(function() {
            window.location = '<?php echo ENTRADA_RELATIVE; ?>/exams?section=attempt&action=start&id='+ post_id;
        });

        $(".resume").click(function() {
            var progress_id = $(this).data("id");
            window.location = '<?php echo ENTRADA_RELATIVE; ?>/exams?section=attempt&action=resume&id='+ post_id + '&progress_id=' + progress_id;
        });

        $(".feedback").click(function() {
            var progress_id = $(this).data("id");
            window.location = '<?php echo ENTRADA_RELATIVE; ?>/exams?section=feedback&progress_id=' + progress_id;
        });

    });
</script>
<style>
    #top-menu {
        margin: 0px 0px 10px 0px;
    }
    table#exam-settings tr.headers th, table#exam-settings td {
        text-align: center;
    }

    table#exam-settings h4, table#exam-progress-records h4 {
        margin: 2px 0px;
    }
</style>
<?php
// Error checking
switch ($STEP) {
    case 2 :

        if ($ERROR) {
            $STEP = 1;
        }
        break;
    case 1 :
    default :
        continue;
        break;
}

switch ($STEP) {
    case 2 :
        if ($SUCCESS) {
            echo display_success();
        }
        break;
    case 1:

    default :
        if (isset($post_id)) {
            $post = Models_Exam_Post::fetchRowByID($post_id);
            if (isset($post) && is_object($post)) {
                ?>
                <div id="exam-post-content">
                <?php
                echo "<h1>".html_encode($post->getTitle())."</h1>";
                $post_type = $post->getTargetType();

                if ($post_type == "event") {
                    $event_id = $post->getTargetID();
                    $event = Models_Event::fetchRowByID($event_id);

                    $course_contacts = Models_Course_Contact::fetchAllByCourseID($event->getCourseID());
                    $course_contact_members = array();

                    //check if user is in audience for target post or is in course contacts or medtech admin
                    if (isset($course_contacts) && is_array($course_contacts) && !empty($course_contacts)) {
                        foreach ($course_contacts as $course_contact) {
                            $course_contact_array = $course_contact->toArray();
                            if (!in_array($course_contact_array["proxy_id"], $course_contact_members)) {
                                $course_contact_members[] = $course_contact_array["proxy_id"];
                            }
                        }
                    }

                    $event_audiences = Models_Event_Audience::fetchAllByEventID($event_id);

                    if (isset($event_audiences) && is_array($event_audiences) && !empty($event_audiences)) {

                        foreach ($event_audiences as $event_audience) {
                            if (isset($event_audience) && is_object($event_audience)) {
                                $audience[] = $event_audience->getAudience($event_id);
                            }
                        }

                        if (is_array($audience) && !empty($audience)) {
                            $audience_members = Models_Event_Audience::buildAudienceMembers($audience);
                        }

                        if (in_array($ENTRADA_USER->getID(), $audience_members) || in_array($ENTRADA_USER->getID(), $course_contact_members) || $ENTRADA_USER->getActiveRole() == "admin") {
                            $access_audience = true;
                        }

                    } else {
                        //no audience for the event
                    }
                } else {
                    //get community audience
                    $community_id = $post->getTargetID();
                }

                if ($post->getStartDate() <= time() && $post->getEndDate() >= time()) {
                    $access_time = true;
                }

                if ($access_audience === true) {
                    $progress_attempts = Models_Exam_Progress::fetchAllByPostIDProxyID($post->getID(), $ENTRADA_USER->getID());
                    if (isset($progress_attempts) && is_array($progress_attempts)) {
                        $attempt_count = Models_Exam_Progress::getAttemptCount($progress_attempts);
                    }
                    ?>
                    <div class="row-fluid" id="top-menu">
                        <?php
                        if ($access_time === true) {
                            ?>
                             <div class="pull-right">
                                 <button id="start_new_attempt" class="btn btn-primary" <?php echo ($attempt_count == $post->getMaxAttempts() && $post->getMaxAttempts() != 0 ? "disabled=\"disabled\"" : "")?>>Start New Attempt</button>
                             </div>
                             <div class="clear"></div>
                        <?php
                        }
                        ?>
                    </div>
                    <div class="well" id="exam-settings">
                        <h4><?php echo $translate->_($SECTION_TEXT["text_exam_information"]);?></h4>
                        <?php
                        $post_view = new Views_Exam_Post($post);
                        echo $post_view->renderPublicPostSettings();
                        ?>
                    </div>
                    <?php
                    if (isset($progress_attempts) && is_array($progress_attempts) && !empty($progress_attempts)) {
                        ?>
                        <table class="table table-bordered table-striped" id="exam-progress-records">
                            <tr>
                                <th colspan="6"><h4><?php echo $translate->_($SECTION_TEXT["label_exam_activity"]);?></h4></th>
                            </tr>
                            <tr class="headers">
                                <th><?php echo $translate->_($SECTION_TEXT["table_headers"]["progress_value"]);?></th>
                                <th><?php echo $translate->_($SECTION_TEXT["table_headers"]["submission_date"]);?></th>
                                <th><?php echo $translate->_($SECTION_TEXT["table_headers"]["exam_points"]);?></th>
                                <th><?php echo $translate->_($SECTION_TEXT["table_headers"]["exam_value"]);?></th>
                                <th><?php echo $translate->_($SECTION_TEXT["table_headers"]["exam_score"]);?></th>
                                <th></th>
                            </tr>
                        <?php
                        foreach ($progress_attempts as $progress) {
                            if (isset($progress) && is_object($progress)) {
                                $progress_view = new Views_Exam_Progress($progress);
                                echo $progress_view->renderPublicRow();
                            }
                        }
                        ?>
                        </table>
                        <?php
                    }
                    ?>

                    </div> <!-- end exam-post-content -->
                <?php
                } else {
                    add_error($translate->_($SECTION_TEXT["text_not_in_audience"]) . " " . $translate->_($SECTION_TEXT["text_please_contact"]));

                    echo display_error();

                    application_log("error", $translate->_($SECTION_TEXT["text_not_in_audience_user"]));
                }
            } else {
                add_error($translate->_($SECTION_TEXT["text"]["id_invalid"]));

                echo display_error();

                application_log("error", $translate->_($SECTION_TEXT["text"]["id_invalid"]));
            }

        } else {
            add_error($translate->_($SECTION_TEXT["text"]["no_id_error"]));

            echo display_error();

            application_log("error", $translate->_($SECTION_TEXT["text"]["no_post_id_error"]));
        }
        break;
}
