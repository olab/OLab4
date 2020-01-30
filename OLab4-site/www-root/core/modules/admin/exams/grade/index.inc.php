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
 * The default file that is loaded when /admin/exams/grade is accessed.
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Robert Fotino <robert.fotino@gmail.com>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_GRADE_EXAMS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("examdashboard", "read", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));
    echo display_error();
    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery.dataTables.min.js?release=".html_encode(APPLICATION_VERSION).""."\"></script>";
    ?>

    <div class="no-printing">
        <ul class="nav nav-tabs">
            <li class="active"><a href="<?php echo ENTRADA_RELATIVE."/admin/exams/grade?".replace_query(array("section" => null)); ?>"><?php echo $translate->_("Grade By Student"); ?></a></li>
            <li><a href="<?php echo ENTRADA_RELATIVE."/admin/exams/grade?".replace_query(array("section" => "question-list")); ?>"><?php echo $translate->_("Grade By Question"); ?></a></li>
        </ul>
    </div>

    <h1><?php echo sprintf($translate->_("Grading %s"), $exam_post->getTitle()); ?></h1>
    
    <?php
    $progress_type          = isset($_GET["progress_type"]) ? $_GET["progress_type"] : "pending";
    $submissions_all        = Models_Exam_Grader::fetchGradableSubmissionsForPost($ENTRADA_USER->getActiveId(), $exam_post->getID());
    $gradable_question_num  = Models_Exam_Grader::fetchGradableQuestionCount($exam_post->getID());
    
    $submissions_pending        = array();
    $submissions_in_progress    = array();
    $submissions_completed      = array();
    foreach ($submissions_all as $submission) {
        $graded_question_num = Models_Exam_Grader::fetchGradedQuestionCount($submission->getID());
        if (0 === $graded_question_num) {
            $submissions_pending[] = $submission;
        } else if ($gradable_question_num === $graded_question_num) {
            $submissions_completed[] = $submission;
        } else {
            $submissions_in_progress[] = $submission;
        }
    }
    ?>
    <div class="clearfix space-above space-below">
        <div id="exam-pending-card" class="span4 exam-card">
            <h4 class="pending"><?php echo $translate->_("Not Started"); ?></h4>
            <div class="exam-card-count pending"><?php echo count($submissions_pending); ?></div>
            <p class="exam-card-description pending"><?php echo sprintf($translate->_("There are %s submission(s) that have not been started."), count($submissions_pending)); ?></p>
            <a class="exam-card-status-btn <?php echo $progress_type === "pending" ? "active" : ""; ?>" data-status="pending">
                <?php echo $translate->_("Pending Grading"); ?> <span class="down-arrow"></span>
            </a>
        </div>
        <div id="exam-in-progress-card" class="span4 exam-card">
            <h4 class="in-progress"><?php echo $translate->_("In Progress"); ?></h4>
            <div class="exam-card-count in-progress"><?php echo count($submissions_in_progress); ?></div>
            <p class="exam-card-description in-progress"><?php echo sprintf($translate->_("There are %s submission(s) that are in progress."), count($submissions_in_progress)); ?></p>
            <a class="exam-card-status-btn <?php echo $progress_type === "in-progress" ? "active" : ""; ?>" data-status="in-progress">
                <?php echo $translate->_("Grading In Progress"); ?> <span class="down-arrow"></span>
            </a>
        </div>
        <div id="exam-complete-card" class="span4 exam-card">
            <h4 class="complete"><?php echo $translate->_("Completed"); ?></h4>
            <div class="exam-card-count complete"><?php echo count($submissions_completed); ?></div>
            <p class="exam-card-description complete"><?php echo sprintf($translate->_("There are %s submission(s) that are complete."), count($submissions_completed)); ?></p>
            <a class="exam-card-status-btn <?php echo $progress_type === "complete" ? "active" : ""; ?>" data-status="complete">
                <?php echo $translate->_("Completed Grading"); ?> <span class="down-arrow"></span>
            </a>
        </div>
    </div>

    <div class="grading-table-wrapper" id="grading-pending-table-wrapper"<?php echo $progress_type !== "pending" ? " style=\"display:none\"" : ""; ?>>
        <h2>Pending Grading</h2>
        <table class="table table-striped table-bordered grading-table">
            <colgroup>
                <col style="width: 30%" />
                <col style="width: 30%" />
                <col style="width: 15%" />
                <col style="width: 25%" />
            </colgroup>
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Submission Date</th>
                    <th>Attempt</th>
                    <th>Progress</th>
                </tr>
            </thead>
            <tbody>
            <?php
            foreach ($submissions_pending as $submission) {
                $link = ENTRADA_URL."/admin/exams/grade?section=student&post_id=".$exam_post->getID()."&progress_id=".$submission->getID();
                $student = User::fetchRowById($submission->getProxyId(), null, null, 1);
                if ($student) {
                    $status = "Not Started";
                    $attempt_num = Models_Exam_Progress::fetchAttemptNumber($submission);
                    echo "<tr>\n";
                    echo "<td><a href=\"".$link."\">".$student->getFullname()."</a></td>\n";
                    echo "<td><a href=\"".$link."\">".date("M j Y h:i a", $submission->getSubmissionDate())."</a></td>\n";
                    echo "<td><a href=\"".$link."\">".$attempt_num."</a></td>\n";
                    echo "<td><a href=\"".$link."\">".$status."</a></td>\n";
                    echo "</tr>\n";
                }
            }
            ?>
            </tbody>
        </table>
    </div>
    <div class="grading-table-wrapper"  id="grading-in-progress-table-wrapper"<?php echo $progress_type !== "in-progress" ? " style=\"display:none\"" : ""; ?>>
        <h2>Grading In Progress</h2>
        <table class="table table-striped table-bordered grading-table">
            <colgroup>
                <col style="width: 30%" />
                <col style="width: 30%" />
                <col style="width: 15%" />
                <col style="width: 25%" />
            </colgroup>
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Submission Date</th>
                    <th>Attempt</th>
                    <th>Progress</th>
                </tr>
            </thead>
            <tbody>
            <?php
            foreach ($submissions_in_progress as $submission) {
                $link = ENTRADA_URL."/admin/exams/grade?section=student&post_id=".$exam_post->getID()."&progress_id=".$submission->getID();
                $student = User::fetchRowById($submission->getProxyId(), null, null, 1);
                if ($student) {
                    $graded_question_num = Models_Exam_Grader::fetchGradedQuestionCount($submission->getID());
                    $status = "In Progress ($graded_question_num/$gradable_question_num)";
                    $attempt_num = Models_Exam_Progress::fetchAttemptNumber($submission);
                    echo "<tr>\n";
                    echo "<td><a href=\"".$link."\">".$student->getFullname()."</a></td>\n";
                    echo "<td><a href=\"".$link."\">".date("M j Y h:i a", $submission->getSubmissionDate())."</a></td>\n";
                    echo "<td><a href=\"".$link."\">".$attempt_num."</a></td>\n";
                    echo "<td><a href=\"".$link."\">".$status."</a></td>\n";
                    echo "</tr>\n";
                }
            }
            ?>
            </tbody>
        </table>
    </div>
    <div class="grading-table-wrapper"  id="grading-complete-table-wrapper"<?php echo $progress_type !== "complete" ? " style=\"display:none\"" : ""; ?>>
        <h2>Completed Grading</h2>
        <table class="table table-striped table-bordered grading-table">
            <colgroup>
                <col style="width: 30%" />
                <col style="width: 25%" />
                <col style="width: 15%" />
                <col style="width: 15%" />
                <col style="width: 15%" />
            </colgroup>
            <thead>
                <th>Student Name</th>
                <th>Submission Date</th>
                <th>Attempt</th>
                <th>Score</th>
                <th>Progress</th>
            </thead>
            <tbody>
            <?php
            foreach ($submissions_completed as $submission) {
                $link = ENTRADA_URL."/admin/exams/grade?section=student&post_id=".$exam_post->getID()."&progress_id=".$submission->getID();
                $student = User::fetchRowById($submission->getProxyId(), null, null, 1);
                if ($student) {
                    $graded_question_num = Models_Exam_Grader::fetchGradedQuestionCount($submission->getID());
                    $status = "Completed";
                    $attempt_num = Models_Exam_Progress::fetchAttemptNumber($submission);
                    echo "<tr>\n";
                    echo "<td><a href=\"".$link."\">".$student->getFullname()."</a></td>\n";
                    echo "<td><a href=\"".$link."\">".date("M j Y h:i a", $submission->getSubmissionDate())."</a></td>\n";
                    echo "<td><a href=\"".$link."\">".$attempt_num."</a></td>\n";
                    echo "<td><a href=\"".$link."\">".$submission->getExamPoints()." / ".$submission->getExamValue()." (".$submission->getExamScore()."%)</a></td>\n";
                    echo "<td><a href=\"".$link."\">".$status."</a></td>\n";
                    echo "</tr>\n";
                }
            }
            ?>
            </tbody>
        </table>
    </div>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('.grading-table').dataTable({
            sPaginationType: 'full_numbers',
            bSortClasses: false,
            bPaginate: false,
            iDisplayLength: 25,
            sDom: '<"top">rt<"bottom"p><"clear">',
            aaSorting: [[ 0, "asc" ]]
        });
        $('.exam-card-status-btn').on('click', function() {
            $('.grading-table-wrapper').hide();
            $('.exam-card-status-btn').removeClass('active');
            $(this).addClass('active');
            var statusType = $(this).data('status');
            switch (statusType) {
                case 'pending':
                    $('#grading-pending-table-wrapper').show();
                    break;
                case 'in-progress':
                    $('#grading-in-progress-table-wrapper').show();
                    break;
                case 'complete':
                    $('#grading-complete-table-wrapper').show();
                    break;
            }
        });
    });
    </script>
    <?php
}
