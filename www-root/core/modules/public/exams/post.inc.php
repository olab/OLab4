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

$MODULE_TEXT    = $translate->_($MODULE);
$SECTION_TEXT   = $MODULE_TEXT["posts"];

$HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" .  ENTRADA_URL . "/css/exams/exams.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
$HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" .  ENTRADA_URL . "/css/exams/questions.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
$HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" .  ENTRADA_URL . "/css/exams/exams-public-attempt.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
$HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" .  ENTRADA_URL . "/css/jquery/jquery.growl.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
$HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/". $MODULE . "/" . "post.js?release=". html_encode(APPLICATION_VERSION) ."\"></script>";

$HEAD[] = "<script type=\"text/javascript\" src=\"" .  ENTRADA_URL . "/javascript/jquery.growl.js?release=\". html_encode(APPLICATION_VERSION) .\"\"></script>";
$HEAD[] = "<script type=\"text/javascript\">var SECTION_TEXT = ". json_encode($SECTION_TEXT) . "</script>";
?>
<script>
    jQuery(document).ready(function($) {
        var post_id = <?php echo $post_id;?>;

        $("#start_new_attempt").click(function() {
            window.location = '<?php echo ENTRADA_RELATIVE; ?>/exams?section=attempt&action=start&id=' + post_id;
        });

        $(".resume").click(function() {
            var progress_id = $(this).data("id");
            window.location = '<?php echo ENTRADA_RELATIVE; ?>/exams?section=attempt&action=resume&id=' + post_id + '&progress_id=' + progress_id;
        });

        $(".feedback").click(function() {
            var progress_id = $(this).data("id");
            window.location = '<?php echo ENTRADA_RELATIVE; ?>/exams?section=feedback&progress_id=' + progress_id;
        });

        $("#start_new_attempt_password").on("click", function() {
            var password = $("#exam-password").val();
            var data = {"method" : "verify-exam-password", "post_id" : post_id, "password" : password};
            $.ajax({
                url: ENTRADA_RELATIVE +  "/exams?section=api-exams",
                data: data,
                type: "POST",
                success: function(data) {
                    var jsonResponse = JSON.parse(data);
                    if (jsonResponse.status == "success") {
                        window.location = ENTRADA_RELATIVE + "/exams?section=attempt&action=start&id=" + post_id;
                    } else {
                        $("#display-error-box").empty();
                        display_error(jsonResponse.data, "#display-error-box", "append");
                    }
                }
            });
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

        if (has_error()) {
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
        if (has_success()) {
            echo display_success();
        }
        break;
    case 1:

    default :
        if (isset($post_id)) {
            $access_time    = false;
            $access_audience = false;
            $grant_access = true;
            $post = Models_Exam_Post::fetchRowByID($post_id);

            if (isset($post) && is_object($post)) {
                $exam_exceptions = Models_Exam_Post_Exception::fetchRowByPostIdProxyId($post->getID(), $ENTRADA_USER->getID());

                if ($post->getHideExam() == 1 && $ENTRADA_USER->getActiveGroup() === "student") {
                    add_error($SECTION_TEXT["text_not_viewable_students"]);

                    echo display_error();

                    application_log("error", $SECTION_TEXT["text_not_viewable_students"]);
                } else {
                    if ($post->getSecure() === "1" && $post->getSecureMode() === "rp_now" && strpos(strtolower($_SERVER["HTTP_USER_AGENT"]), "rpnow") === false) {
                        $grant_access = false;
                        $rp_now_user = Models_Secure_RpNowUsers::fetchRowByRpnowConfigIdProxyId(Models_Secure_RpNow::fetchRowByPostID($post->getID())->getID(), $ENTRADA_USER->getID());
                        add_notice(sprintf($translate->_("You are required to use the RPnow exam browser to start this exam. You can download RPnow by clicking <a href=\"%s\" target=\"_blank\">here</a>.<br>Your Exam Code is <strong>%s</strong>"), RP_NOW_DOWNLOAD_URL, ($rp_now_user ? $rp_now_user->getExamCode() : "n/a")));
                        echo display_notice();
                    }
                    //show exam to user
                    ?>
                    <div id="display-error-box" class="clear"></div>
                    <div id="exam-post-content">
                        <h1> <?php echo html_encode($post->getTitle()); ?></h1>
                    <?php

                    $post_type = $post->getTargetType();

                    if ($post_type == "event") {
                        $access_audience = $post->check_event_audience($ENTRADA_USER);
                    } else if ($post_type == "community") {
                        //get community audience
                        $community_id = $post->getTargetID();
                    } else if ($post_type == "preview") {
                        $access_audience = $post->canEditExam();
                    }

                    $new_attempt         = $post->isNewAttemptAllowedByUser($ENTRADA_USER);
                    $disable_new_attempt = ($post->isNewAttemptAllowedByUser($ENTRADA_USER)) ? false : true;

                    if ($access_audience === true) {
                        $progress_attempts = Models_Exam_Progress::fetchAllByPostIDProxyID($post->getID(), $ENTRADA_USER->getID());
                        if (isset($progress_attempts) && is_array($progress_attempts)) {
                            $attempt_count = Models_Exam_Progress::getAttemptCount($progress_attempts);
                            if ($exam_exceptions && is_object($exam_exceptions)) {
                                $allowed_count = Views_Exam_Progress::getAllowedAttemptsCount($exam_exceptions, $post);
                            } else {
                                $allowed_count = $post->getMaxAttempts();
                            }

                            if ($attempt_count >= $allowed_count && $allowed_count != 0) {
                                $disable_new_attempt = true;
                            }
                        }
                        ?>
                        <div class="row-fluid" id="top-menu">
                            <?php
                             if ($new_attempt === true && $grant_access) {
                                 if ($post->getResumePassword() != null && $post->getSecureMode() != "seb") {
                                     ?>
                                     <div class="control-group pull-right">
                                         <label class="control-label" for="exam-password">Exam Password:</label>
                                         <div class="controls">
                                             <input class="input-large space-right" name="exam-password" id="exam-password" type="password" value="" autocomplete="off"/>
                                             <div class="pull-right">
                                                 <button id="start_new_attempt_password" class="btn btn-primary" <?php echo ($disable_new_attempt ? "disabled=\"disabled\"" : "")?>>Start New Attempt</button>
                                             </div>
                                         </div>
                                     </div>
                                     <?php
                                 } else {
                                     ?>
                                     <div class="pull-right">
                                         <button id="start_new_attempt" class="btn btn-primary" <?php echo ($disable_new_attempt ? "disabled=\"disabled\"" : "")?>>Start New Attempt</button>
                                     </div>
                                     <div class="clear"></div>
                                     <?php
                                 }
                            } else if ($post->getStartDate() > time()) {
                                 $post_start_date = new DateTime('@' . $post->getStartDate());
                                 ?>
                                 <div id="attempt_countdown" class="alert alert-info">
                                     This exam will be available in <strong class="countdown"></strong>
                                 </div>
                                 <script>
                                    var end = new Date(<?php echo $post->getStartDate()*1000; ?>);
                                    var _second = 1000;
                                    var _minute = _second * 60;
                                    var _hour = _minute * 60;
                                    var _day = _hour * 24;
                                    var post_timer;

                                    post_timer = setInterval(function(){
                                        var now = new Date();
                                        var distance = end - now;
                                        if (distance < 0) {
                                            window.location.reload();
                                            return false;
                                        }
                                        var days = Math.floor(distance / _day);
                                        var hours = Math.floor((distance % _day) / _hour);
                                        var minutes = Math.floor((distance % _hour) / _minute);
                                        var seconds = Math.floor((distance % _minute) / _second);

                                        html = (days > 0 ? days + ' days ' : '');
                                        html += (hours > 0 ? hours + ' hours ' : (days > 0 ? '0 hours ' : ''));
                                        html += (minutes > 0 ? minutes + ' mins ' : (hours > 0 ? '0 mins ' : ''));
                                        html += (seconds > 0 ? seconds + ' secs ' : (minutes > 0 ? '0 secs ' : ''));
                                        jQuery("#attempt_countdown .countdown").html(html);
                                    }, 1000);
                                 </script>
                                 <?php
                            }
                            ?>
                        </div>
                        <div class="well" id="exam-settings">
                            <h4><?php echo $SECTION_TEXT["text_exam_information"];?></h4>
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
                                    <th colspan="6"><h4><?php echo $SECTION_TEXT["label_exam_activity"];?></h4></th>
                                </tr>
                                <tr class="headers">
                                    <th><?php echo $SECTION_TEXT["table_headers"]["progress_value"];?></th>
                                    <th><?php echo $SECTION_TEXT["table_headers"]["submission_date"];?></th>
                                    <th><?php echo $SECTION_TEXT["table_headers"]["exam_points"];?></th>
                                    <th><?php echo $SECTION_TEXT["table_headers"]["exam_value"];?></th>
                                    <th><?php echo $SECTION_TEXT["table_headers"]["exam_score"];?></th>
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
                        add_error($SECTION_TEXT["text_not_in_audience"] . " " . $SECTION_TEXT["text_please_contact"]);

                        echo display_error();

                        application_log("error", $SECTION_TEXT["text_not_in_audience_user"]);
                    }
                 } // end hide exam
            } else {
                add_error($MODULE_TEXT["attempt"]["text"]["id_invalid"]);

                echo display_error();

                application_log("error", $MODULE_TEXT["attempt"]["text"]["id_invalid"]);
            }

        } else {
            add_error($MODULE_TEXT["attempt"]["text"]["no_id_error"]);

            echo display_error();

            application_log("error", $MODULE_TEXT["attempt"]["text"]["no_post_id_error"]);
        }
        break;
}
