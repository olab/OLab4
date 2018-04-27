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
 * Aggregates learner comments for questions on this exam.
 *
 * @author Organisation: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Samuel Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2016 UC Regents. All Rights Reserved.
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
    if (isset($_GET["id"]) && $tmp_input = clean_input($_GET["id"], "int")) {
        $PROCESSED["id"] = $tmp_input;
    }

    $exam = Models_Exam_Exam::fetchRowByID($PROCESSED["id"]);
    $SECTION_TEXT = $SUBMODULE_TEXT["reports"]["category"]["admin"];

    if ($exam) {
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/exams/exams?section=edit-exam&id=".$exam->getID(), "title" => $exam->getTitle());
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/exams/exams?section=reports&id=".$exam->getID(), "title" => "Reports");
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/exams/exams?section=category-learner-setup&id=".$exam->getID(), "title" => $SECTION_TEXT["title"]);
        if ($ENTRADA_ACL->amIAllowed(new ExamResource($exam->getID(), true), "read")) {
            ?>
            <style>
                #category-report-admin h3 {
                    font-size: 16px;;
                }
                ul.category-report li {
                    list-style: none;
                    font-size: 14px;
                }

                ul.category-report li .alert {
                    font-size: 12px;
                    margin: 10px;
                }
            </style>
            <div id="category-report-admin">
                <h1 id="exam_title"><?php echo $exam->getTitle(); ?></h1>
                <?php
                $exam_view = new Views_Exam_Exam($exam);
                echo $exam_view->examNavigationTabs($SECTION);
                ?>
                <h2><?php echo $SECTION_TEXT["title"];?></h2>
                <h3><?php echo $SECTION_TEXT["posts"];?></h3>
                <ul class="category-report">
                    <?php
                    $posts = Models_Exam_Post::fetchAllByExamID($exam->getID());
                    if ($posts && is_array($posts) && !empty($posts)) {
                        foreach ($posts as $post) {
                            $type = $post->getTargetType();
                            if ($post->getTargetType() === "event") {
                                $target_id = $post->getTargetID();
                                $event = Models_Event::fetchRowByID($target_id);
                                if ($event && is_object($event)) {
                                    $title = $event->getEventTitle();
                                }

                                $report = Models_Exam_Category::fetchRowByPostID($post->getID());
                                if ($report) {
                                    $url = ENTRADA_RELATIVE . "/admin/exams/exams?section=edit-category&id=" . $report->getCategoryID();
                                } else {
                                    $url = ENTRADA_RELATIVE . "/admin/exams/exams?section=add-category&post_id=" .  $post->getID();
                                }
                                ?>
                                <li>
                                    <a href="<?php echo $url; ?>">
                                        <strong><?php echo $post->getTitle(); ?></strong> <?php echo($title ? " - Event: " . $title : "") ?>
                                    </a>
                                </li>
                                <?php
                            }
                        }
                    }
                    ?>
                </ul>
            </div>
            <?php
        } else {
            add_error(sprintf($translate->_("Your account does not have the permissions required to view this exam.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

            echo display_error();

            application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this exam [".$PROCESSED["id"]."]");
        }
    } else {
        $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "/" . $SECTION, "title" => $SECTION_TEXT["title"]);
        ?>
        <h1><?php echo $SUBMODULE_TEXT["exams"]["title"]; ?></h1>
        <?php
        echo display_error($SECTION_TEXT["exam_not_found"]);
    }
}