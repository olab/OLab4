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

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_SECURE"))) {
    exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
}

$EXAM_TEXT = $translate->_("exams");
$SECURE_TEXT = $translate->_("secure");
$SECURE_TEXT = $SECURE_TEXT["exams"];
?>
<h1><?php echo $EXAM_TEXT["exams"]["my_exams"]["title"];?></h1>
<?php
$exam_posts = new Models_Exam_Post();
$user_event_exam_posts      = $exam_posts->fetchAllSecureEventExamsByProxyID($PROXY_ID, true);
$user_community_exam_posts  = $exam_posts->fetchAllSecureCommunityExamsByProxyID($PROXY_ID);
if ($user_event_exam_posts || $user_community_exam_posts) {
    $event_id			= 0;
    $curriculum_paths	= array();
    ?>

    <h2><?php echo $SECURE_TEXT["learning_event_exams"];?></h2>
    <br />
    <?php if ($user_event_exam_posts && is_array($user_event_exam_posts)) {

        $resource = false;
        $entity_timeframe_pre = array();
        $entity_timeframe_during = array();
        $entity_timeframe_post = array();
        $entity_timeframe_none = array();

        foreach ($user_event_exam_posts as $post) {
            if ($post && is_object($post)) {
                $show_post = false;
                if ($post->isResumeAttemptAllowedByUser($ENTRADA_USER)) {
                    $access_time = true;
                    $progress_results = Models_Exam_Progress::fetchAllByPostIDProxyID($post->getID(), $ENTRADA_USER->getID());
                    if ($progress_results && is_array($progress_results) && !empty($progress_results)) {
                        //use current progress and orders
                        $progress = false;
                        foreach ($progress_results as $progress_result) {
                            if ($progress_result && is_object($progress_result)) {
                                if ($progress_result->getProgressValue() === "inprogress") {
                                    $progress = $progress_result;
                                    $show_post = true;
                                    break;
                                }
                            }
                        }
                        if (!$progress) {
                            if ($post->isNewAttemptAllowedByUser($ENTRADA_USER)) {
                                $access_time = true;
                                $new_start = true;
                                $show_post = true;
                            } else {
                                $access_time = false;
                                $new_start = false;
                            }
                        }
                    } else {
                        if ($post->isNewAttemptAllowedByUser($ENTRADA_USER)) {
                            $access_time = true;
                            $new_start = true;
                            $show_post = true;
                        } else {
                            $access_time = false;
                            $new_start = false;
                        }
                    }
                } elseif ($post->isNewAttemptAllowedByUser($ENTRADA_USER)) {
                    $access_time = true;
                    $new_start = true;
                    $show_post = true;
                }

                if ($post->isNewAttemptAllowedByUser($ENTRADA_USER)) {
                    $show_post = true;
                }

                if ($show_post == true) {
                    $event  = $post->getEvent();
                    $entity = Models_Event_Resource_Entity::fetchRowByEventIDEntityValue($event->getID(), $post->getID());

                    if ($entity && is_object($entity)) {
                        $resource = $post->toArray();

                        $post_view                      = new Views_Exam_Post($post, $event);
                        $post_resources                 = $post_view->renderEventResource();
                        $resource["title"]              = $post_resources["title"];
                        $resource["description"]        = "";

                        if ($post_resources["description"] && $post_resources["description"] != "") {
                            $resource["description"]        = "<p class=\"muted resource-description\">" . $post_resources["description"] . "</p>";
                        }

                        $resource["description"]        .= "<p class=\"muted event-resource-release-dates\">" . $post_resources["available"] . "</p>";
                        $resource["type"]               = $EXAM_TEXT["title_singular"];
                        $resource["type_id"]            = $entity->getEntityType();
                        $resource["required"]           = $resource["mandatory"];
                        $resource["attempts_allowed"]   = $post_resources["attempts_allowed"];
                        $resource["time_limit"]         = $post_resources["time_limit"];

                        if ($resource) {
                            $resources[] = $resource;
                        }
                    } else {
                        add_error($SECURE_TEXT["errors"]["entity_missing_1"] . $post->getTitle() . " " . $SECURE_TEXT["errors"]["entity_missing_2"]);
                        application_log("error", $SECURE_TEXT["errors"]["entity_missing_1"] . $post->getTitle());
                        echo display_error();
                    }

                }
            }
        }
        ?>

        <div id="event-resources-section">
            <div id="event-resources-container">
                <div id="event-resource-timeframe-pre-container" class="resource-list">
                    <ul class="timeframe" id="exam_timeframe">
                        <?php
                        if ($resources && is_array($resources) && !empty($resources)) {
                            foreach ($resources as $entity) {
                                ?>
                                <li>
                                    <div>
                                        <?php echo $entity["title"]; ?>
                                        <?php echo $entity["description"]; ?>
                                    </div>
                                    <div>
                                        <?php
                                        if ($entity["required"] ==  "1") { ?>
                                            <span class="label label-important event-resource-stat-label">
                                            <?php echo $SECURE_TEXT["index"]["required"];?>
                                            </span>
                                            <?php
                                        } else { ?>
                                            <span class="label label-default event-resource-stat-label">
                                            <?php echo $SECURE_TEXT["index"]["optional"];?>
                                            </span>
                                            <?php
                                        }
                                        ?>
                                        <?php
                                        switch ($entity["type_id"]) {
                                            case 12 :
                                                if ($entity["attempts_allowed"]) {
                                                    echo "<span class=\"label label-default event-resource-stat-label\">\n";
                                                    echo $EXAM_TEXT["posts"]["table_headers"]["attempts"] . ": " . $entity["attempts_allowed"];
                                                    echo "</span>\n";
                                                }

                                                if ($entity["time_limit"]) {
                                                    echo "<span class=\"label label-default event-resource-stat-label\">\n";
                                                    echo $EXAM_TEXT["posts"]["table_headers"]["time_limit"] . $entity["time_limit"] . ": \n";
                                                    echo "</span>";
                                                }
                                                break;
                                        }
                                        ?>
                                    </div>
                                </li>
                            <?php
                            }
                        } else {
                        ?>
                            <div class="alert alert-info">
                                <?php echo $SECURE_TEXT["errors"]["no_exams"];?>
                            </div>
                        <?php
                        }
                        ?>
                    </ul>
                </div>
            </div>
        </div>

    <?php } else { ?>
        <div class="alert alert-info">
            <?php echo $SECURE_TEXT["errors"]["no_exams"];?>
        </div>
    <?php } ?>
    <br /><br />
    <h2><?php echo $SECURE_TEXT["community_exams"];?></h2>
    <br />
    <?php if ($user_community_exam_posts) { ?>
        <table class="tableList wrap datatable" cellspacing="0" summary="<?php echo $SECURE_TEXT["index"]["list_of_secure"];?>">
            <colgroup>
                <col class="modified" />
                <col class="title" />
                <col class="date" />
            </colgroup>
            <thead>
            <tr>
                <td class="date"><?php echo $SECURE_TEXT["index"]["community_title"];?></td>
                <td class="title sortedASC"><?php echo $SECURE_TEXT["index"]["exam_title"];?></td>
                <td class="general"><?php echo $SECURE_TEXT["index"]["exam_expires"];?></td>
            </tr>
            </thead>
            <tbody>
            <?php
            foreach ($user_community_exam_posts as $user_community_exam_post) {
                $community = $user_community_exam_post->getCommunity();
                $post_view = new Views_Exam_Post($user_community_exam_post, null, $community);
                echo $post_view->renderEventPost(false, true, true);
            }
            ?>
            </tbody>
        </table>
    <?php } else { ?>
        <div class="alert alert-info">
            <?php echo $SECURE_TEXT["errors"]["no_exams_community"];?>
        </div>
    <?php } ?>
    <div id="modal-access-quiz" class="modal fade">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">
                        <?php echo $SECURE_TEXT["index"]["start_exam"];?>
                    </h4>
                </div>
                <div class="modal-body">
                    <div class="modal-messages"></div>
                </div>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        jQuery(function($) {
            $(".start-exam").click(function(){
                var resource_id = $(this).data("resource-id");
                var download_url = $(this).data("href");
                var modal = $(".modal");
                var message = "<div class=\"alert alert-info\"><h4><?php echo $SECURE_TEXT["index"]["exam_loading"];?></h4><p><?php echo $SECURE_TEXT["index"]["please_wait"];?></p></div>";

                modal.find(".modal-messages").html(message);
                modal.modal("show");

                var formData = "method=get-file&resource_id=" + resource_id;
                $.ajax({
                    type: "GET",
                    url: "<?php echo ENTRADA_URL; ?>/secure/exams?section=api-exam-secure-resources",
                    data: formData,
                    dataType: "json",
                    success: function (data) {
                        var jsonResponse = data;

                        if (jsonResponse.status === "success") {
                            modal.modal("hide");
                            window.location = download_url;
                        } else if (jsonResponse.status === "error" || jsonResponse.status === "empty") {
                            var message = "<div class=\"alert alert-danger\">" + jsonResponse.data + "</div>";
                            modal.find(".modal-messages").html(message);
                        } else {
                            var message = "<div class=\"alert alert-danger\"><?php echo $SECURE_TEXT["errors"]["loading"];?></div>";
                            modal.find(".modal-messages").html(message);
                        }
                    }
                });
            });
        });
    </script>
    <?php
} else { ?>
    <div class="alert alert-info">
        There are currently <strong>no exams available</strong> for you to take in <strong>secure mode</strong>.
    </div>
<?php } ?>

