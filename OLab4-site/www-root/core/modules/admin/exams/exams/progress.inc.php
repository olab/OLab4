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
 * This file loads details for any exam activity, posts, progress, submissions, etc
 * Tools like regrade, reopen, analytics
 *
 * @author Organisation: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Samuel Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2015 UC Regents. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_EXAMS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("exam", "create", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    ?>
    <?php
    $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = \"". ENTRADA_URL ."\";</script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/jquery/jquery.dataTables.min-1.10.1.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/jquery/dataTables.colVis.min.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
    $HEAD[] = "<link rel='stylesheet' type='text/css' href='". ENTRADA_RELATIVE . "/css/jquery/jquery.dataTables.css?release=".html_encode(APPLICATION_VERSION)."'>";
    $HEAD[] = "<link rel='stylesheet' type='text/css' href='". ENTRADA_RELATIVE . "/css/jquery/dataTables.colVis.css?release=".html_encode(APPLICATION_VERSION)."'>";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" .  ENTRADA_URL . "/css/jquery/jquery.growl.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" .  ENTRADA_URL . "/javascript/jquery.growl.js\"></script>";
    ?>
    <style>
        .ColVis {
            margin: 0px 5px;
        }
    </style>
    <?php
    if (isset($_GET["id"]) && $tmp_input = clean_input($_GET["id"], "int")) {
        $PROCESSED["id"] = $tmp_input;
    }

    $progress = Models_Exam_Progress::fetchRowByID($PROCESSED["id"]);

    if (isset($progress) && is_object($progress)) {
        $proxy_id = $progress->getProxyID();
        $exam_id = $progress->getExamID();
        $post_id = $progress->getPostID();

        $exam = Models_Exam_Exam::fetchRowByID($exam_id);
        $user = User::fetchRowByID($proxy_id, null, null, 1);
    }

    $SECTION_TEXT = $SUBMODULE_TEXT[$SECTION];

    if (isset($exam) && is_object($exam)) {
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?section=post&id=" . $exam->getID() , "title" => $exam->getTitle());
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?section=activity&id=" . $post_id , "title" => "Activity");
        $BREADCRUMB[] = array("title" => "Details");

        if ($ENTRADA_ACL->amIAllowed(new ExamResource($exam->getID(), true), "update")) {
            ?>
            <h1><?php echo $SECTION_TEXT["title"]; ?></h1>
            <h2><?php echo $exam->getTitle(); ?></h2>
            <?php if ($user) {
                ?>
                <h4><?php echo $user->getFullName(); ?></h4>
            <?php
            }
            $exam_elements = Models_Exam_Exam_Element::fetchAllByExamID($exam_id);
            if (isset($exam_elements) && is_array($exam_elements)) {
                $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/" . $MODULE ."/" . $SUBMODULE . "/". $SECTION . ".js?release=". html_encode(APPLICATION_VERSION) ."\"></script>"
                ?>
                <div id="show_columns">
                    <input type="button" class="btn pull-right" value="Download CSV" id="download-csv"/>
                </div>
                <table class="table table-bordered table-striped" id="posts-table">
                    <thead>
                    <tr>
                        <th><?php echo $SECTION_TEXT["table_headers"]["element_order"]; ?></th>
                        <th><?php echo $SECTION_TEXT["table_headers"]["question_id"]; ?></th>
                        <th><?php echo $SECTION_TEXT["table_headers"]["question_text"]; ?></th>
                        <th><?php echo $SECTION_TEXT["table_headers"]["question_code"]; ?></th>
                        <th><?php echo $SECTION_TEXT["table_headers"]["question_type"]; ?></th>
                        <th><?php echo $SECTION_TEXT["table_headers"]["student_comments"]; ?></th>
                        <th><?php echo $SECTION_TEXT["table_headers"]["response"]; ?></th>
                        <th><?php echo $SECTION_TEXT["table_headers"]["letter"]; ?></th>
                        <th><?php echo $SECTION_TEXT["table_headers"]["correct_letter"]; ?></th>
                        <th><?php echo $SECTION_TEXT["table_headers"]["points"]; ?></th>
                        <th><?php echo $SECTION_TEXT["table_headers"]["scored"]; ?></th>
                        <th><?php echo $SECTION_TEXT["table_headers"]["grade_comments"]; ?></th>
                        <th><?php echo $SECTION_TEXT["table_headers"]["regrade"]; ?></th>
                        <th><?php echo $SECTION_TEXT["table_headers"]["graded_date"]; ?></th>
                        <th><?php echo $SECTION_TEXT["table_headers"]["graded_by"]; ?></th>
                        <th><?php echo $SECTION_TEXT["table_headers"]["created"]; ?></th>
                        <th><?php echo $SECTION_TEXT["table_headers"]["creator"]; ?></th>
                        <th><?php echo $SECTION_TEXT["table_headers"]["updated"]; ?></th>
                        <th><?php echo $SECTION_TEXT["table_headers"]["updater"]; ?></th>
                        <th class="edit_menu"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $question_type = Models_Exam_Lu_Questiontypes::fetchRowByShortname("text");
                    if ($question_type) {
                        $shortname_type_id = $question_type->getID();
                    } else {
                        $shortname_type_id = NULL;
                    }

                    foreach ($exam_elements as $exam_element) {
                        if (is_object($exam_element) && $exam_element->getElementType() != "text" || $exam_element->getElementType() != "page_break") {
                            $response = Models_Exam_Progress_Responses::fetchRowByProgressIDExamIDPostIDProxyIDElementID($progress->getID(), $exam_id, $post_id, $proxy_id, $exam_element->getID());
                            if (isset($response) && is_object($response)) {
                                if ($response->getQuestionType() != "text") {
                                    $response_view = new Views_Exam_Progress_Response($response, $exam_element);
                                    echo $response_view->render();
                                }
                            }
                        }
                    }

                    ?>
                    </tbody>
                </table>
                <form enctype="multipart/form-data" method="post" action="<?php echo ENTRADA_URL."/admin/exams/exams?".replace_query(array("section" => "progress-csv"));?>" id="csv-form">
                    <input type="hidden" name="csv" id="csv-hidden-field" />
                    <input type="hidden" name="exam-id" id="exam-id" value="<?php echo $exam_id; ?>" />
                </form>

            <?php
            }

        } else {
            add_error(sprintf($translate->_("Your account does not have the permissions required to edit this exam.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

            echo display_error();

            application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this exam [".$PROCESSED["id"]."]");
        }
    } else {
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "/" . $SECTION, "title" => $SECTION_TEXT["title"]);
        ?>
        <h1><?php echo $SUBMODULE_TEXT["posts"]["title"]; ?></h1>
        <?php
        echo display_error($SUBMODULE_TEXT["posts"]["post_not_found"]);
    }
}