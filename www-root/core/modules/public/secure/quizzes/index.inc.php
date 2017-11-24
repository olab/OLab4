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

if((!defined("PARENT_INCLUDED")) || (!defined("IN_SECURE"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
}
$HEAD[] = "<link href=\"".ENTRADA_RELATIVE."/css/windows/default.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" />";
$HEAD[] = "<link href=\"".ENTRADA_RELATIVE."/css/windows/medtech.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" />";
$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/windows/window.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
?>
<h1>Secure Quizzes</h1>
<?php
$quizzes = new Models_Quiz_Attached_Event();
$userQuizzes = $quizzes->fetchAllSecureByProxyID($PROXY_ID);

$communityQuizzes = new Models_Quiz_Attached_CommunityPage();
$userCommunityQuizzes = $communityQuizzes->fetchAllSecureByProxyID($PROXY_ID);
if ($userQuizzes || $communityQuizzes) {
	$event_id			= 0;
	$curriculum_paths	= array();
    	?>
<br />
<h2>Learning Events Quizzes</h2>
<br />
<?php if ($userQuizzes){ ?>
	<table class="tableList wrap datatable" cellspacing="0" summary="List of Secure Quizzes">
        <colgroup>
           <col class="modified" />
            <col class="title" />
            <col class="date" />
        </colgroup>
	<thead>
		<tr>
			<td class="date">Course Code</td>
            <td class="title sortedASC">Quiz Title</td>
			<td class="general">Quiz Expires</td>
		</tr>
	</thead>
	<tbody>
	<?php 
    
    foreach ($userQuizzes as $userQuiz) { 
        $quizProgress = Models_Quiz_Progress::fetchAllByAquizIDProxyID($userQuiz->getAquizID(), $PROXY_ID);
        $quiz_attempts = count($quizProgress);

        $exceeded_attempts	= ((((int) $userQuiz->getQuizAttempts() === 0) || ($quiz_attempts < $userQuiz->getQuizAttempts())) ? false : true);
        $user_event_attendance = Models_Event_Attendance::fetchRowByEventIDProxyID($userQuiz->getEventID(), $PROXY_ID);
        

        if ($userQuiz->getRequireAttendance() === 1 && $user_event_attendance->getActive() !== 1) {
            $allow_attempt = false;
            $reason = "";
        } elseif (((!(int) $userQuiz->getReleaseDate()) || ($userQuiz->getReleaseDate() <= time())) && ((!(int) $userQuiz->getReleaseUntil()) || ($userQuiz->getReleaseUntil() >= time())) && (!$exceeded_attempts) && (($userQuiz->getRequireAttendance() == 1 && $user_event_attendance->getActive() == 1) || ($userQuiz->getRequireAttendance() != 1 ))) {
            $allow_attempt = true;
        } else {
            $allow_attempt = false;
        }
        
        $quizType = Models_Quiz_QuizType::fetchRowByID($userQuiz->getQuiztypeID());
        if ($quizType){
            $total_questions = count(Models_Quiz_Question::fetchAllRecords($userQuiz->getQuizID()));
        }
        
        ?>
		<tr>
            <td><?php echo html_encode($userQuiz->getCourseCode()); ?></td>
            <td>
                <?php if ($allow_attempt){ ?>
                <?php 
                $access_file = Models_Quiz_Attached_AttachedAccessFiles::fetchRowByAquizID($userQuiz->getAquizID());
                ?>
                <a class="start-quiz" data-aquiz="<?php echo $userQuiz->getAquizID(); ?>" data-href="<?php echo ENTRADA_URL; ?>/file-seb.php?release=<?php echo html_encode(APPLICATION_VERSION); ?>&id=<?php echo $userQuiz->getAquizID(); ?>" title="Take <?php echo html_encode($userQuiz->getQuizTitle()); ?>"><strong><?php echo html_encode($userQuiz->getQuizTitle()); ?></strong></a><br />
                <?php } else { ?>
                <strong class="muted"><?php echo html_encode($userQuiz->getQuizTitle()); ?></strong><br />
                <?php } ?>
                <?php echo quiz_generate_description($userQuiz->getRequired(), $quizType->getQuiztypeCode(), $userQuiz->getQuizTimeout(), $total_questions, $userQuiz->getQuizAttempts(), $userQuiz->getTimeframe(), $userQuiz->getRequireAttendance(), $userQuiz->getCourseID()); ?>
                <?php if ($quizProgress){ ?>
                <br />
                <strong>Your Attempts</strong>
                <ul class="menu">
                    <?php 
                    foreach ($quizProgress as $entry) { 
                        $quiz_end_time		= (((int) $userQuiz->getQuizTimeout()) ? ($entry->getUpdatedDate() + ($userQuiz->getQuizTimeout() * 60)) : 0);
                        
                        if (($entry->getProgressValue() == "inprogress") && ((((int) $userQuiz->getReleaseUntil()) && ($userQuiz->getReleaseUntil() < time())) || (($quiz_end_time) && (time() > ($quiz_end_time + 30))))) {
                            $quiz_progress_array	= array (
                                "progress_value" => "expired",
                                "updated_date" => time(),
                                "updated_by" => $ENTRADA_USER->getID()
                            );
                            
                            $entry->fromArray($quiz_progress_array);
                            
                            if (!$entry->update()) {
                                application_log("error", "Unable to update the qprogress_id [".$entry->getQprogressID()."] to expired. Database said: ".$db->ErrorMsg());
                            }
                        }
                        
                        switch ($entry->getProgressValue()) {
                            case "complete" :
                                if (($quizType->getQuiztypeCode() == "delayed" && $userQuiz->getReleaseUntil() <= time()) || ($quizType->getQuiztypeCode() == "immediate")) {
                                    $percentage = ((round(($entry->getQuizScore() / $entry->getQuizValue()), 2)) * 100);
                                    echo "<li class=\"".(($percentage >= 60) ? "correct" : "incorrect")."\">";
                                    echo	date(DEFAULT_DATE_FORMAT, $entry->getUpdatedDate())." <strong>Score:</strong> ".$entry->getQuizScore()."/".$entry->getQuizValue()." (".$percentage."%)";
                                    echo "</li>";
                                } elseif ($quizType->getQuiztypeCode() == "hide") {
                                    echo "<li>".date(DEFAULT_DATE_FORMAT, $entry->getUpdatedDate())." - <strong>Completed</strong></li>";
                                } else {
                                    echo "<li>".date(DEFAULT_DATE_FORMAT, $entry->getUpdatedDate())." <strong>Score:</strong> To Be Released ".date(DEFAULT_DATE_FORMAT, $userQuiz->getReleaseUntil())."</li>";
                                }
                                break;
                            case "expired" :
                                echo "<li class=\"incorrect\">".date(DEFAULT_DATE_FORMAT, $entry->getUpdatedDate())." <strong>Expired Attempt</strong>: not completed.</li>";
                                break;
                            case "inprogress" :
                                echo "<li>".date(DEFAULT_DATE_FORMAT, $entry->getUpdatedDate())." <strong>Attempt In Progress</strong> ( <a class=\"start-quiz\" data-aquiz=\"". $userCommunityQuiz->getAquizID()."\" data-href=\"". ENTRADA_URL."/file-seb.php?id=". $userQuiz->getAquizID()."\" title=\"Take ".html_encode($userQuiz->getQuizTitle())."\">continue quiz</a> )</li>";
                                
                                break;
                            default :
                                break;
                        }
                    } 
                    ?>
                </ul>
                <?php } ?>
            </td>
            <td><?php echo (((int) $userQuiz->getReleaseUntil()) ? date(DEFAULT_DATE_FORMAT, $userQuiz->getReleaseUntil()) : "No Expiration"); ?></td>
		</tr>
	<?php } ?>
	</tbody>
	</table>
<?php } else { ?>
<div class="alert alert-info">There are no available secure quizzes for your <strong>learning events</strong>.</div>
<?php } ?>
    <br /><br />
    <h2>Community Quizzes</h2>
    <br />
    <?php if ($userCommunityQuizzes) { ?>
	<table class="tableList wrap datatable" cellspacing="0" summary="List of Secure Quizzes">
        <colgroup>
           <col class="modified" />
            <col class="title" />
            <col class="date" />
        </colgroup>
	<thead>
		<tr>
			<td class="date">Community Title</td>
            <td class="title sortedASC">Quiz Title</td>
			<td class="general">Quiz Expires</td>
		</tr>
	</thead>
	<tbody>
	<?php 
    foreach ($userCommunityQuizzes as $userCommunityQuiz) { 
        $quizProgress = Models_Quiz_Progress::fetchAllByAquizIDProxyID($userCommunityQuiz->getAquizID(), $PROXY_ID);
        $quiz_attempts = count($quizProgress);

        $exceeded_attempts	= ((((int) $userCommunityQuiz->getQuizAttempts() === 0) || ($quiz_attempts < $userCommunityQuiz->getQuizAttempts())) ? false : true);

        if (((!(int) $userCommunityQuiz->getReleaseDate()) || ($userCommunityQuiz->getReleaseDate() <= time())) && ((!(int) $userCommunityQuiz->getReleaseUntil()) || ($userCommunityQuiz->getReleaseUntil() >= time())) && (!$exceeded_attempts)) {
            $allow_attempt = true;
        } else {
            $allow_attempt = false;
        }
        
        $quizType = Models_Quiz_QuizType::fetchRowByID($userCommunityQuiz->getQuiztypeID());
        if ($quizType){
            $total_questions = count(Models_Quiz_Question::fetchAllRecords($userCommunityQuiz->getQuizID()));
        }
        
        ?>
		<tr>
            <td><?php echo html_encode($userCommunityQuiz->getCommunityTitle()); ?></td>
            <td>
                <?php if ($allow_attempt){ ?>
                <a class="start-quiz" data-aquiz="<?php echo $userCommunityQuiz->getAquizID(); ?>" data-href="<?php echo ENTRADA_URL; ?>/file-seb.php?id=<?php echo $userCommunityQuiz->getAquizID(); ?>" title="Take <?php echo html_encode($userCommunityQuiz->getQuizTitle()); ?>"><strong><?php echo html_encode($userCommunityQuiz->getQuizTitle()); ?></strong></a><br />
                <?php } else { ?>
                <strong class="muted"><?php echo html_encode($userCommunityQuiz->getQuizTitle()); ?></strong><br />
                <?php } ?>
                <?php echo quiz_generate_description($userCommunityQuiz->getRequired(), $quizType->getQuiztypeCode(), $userCommunityQuiz->getQuizTimeout(), $total_questions, $userCommunityQuiz->getQuizAttempts(), $userCommunityQuiz->getTimeframe()); ?>
                <?php if ($quizProgress){ ?>
                <br />
                <strong>Your Attempts</strong>
                <ul class="menu">
                    <?php 
                    foreach ($quizProgress as $entry) { 
                        $quiz_end_time		= (((int) $userCommunityQuiz->getQuizTimeout()) ? ($entry->getUpdatedDate() + ($userCommunityQuiz->getQuizTimeout() * 60)) : 0);
                        
                        if (($entry->getProgressValue() == "inprogress") && ((((int) $userCommunityQuiz->getReleaseUntil()) && ($userCommunityQuiz->getReleaseUntil() < time())) || (($quiz_end_time) && (time() > ($quiz_end_time + 30))))) {
                            $quiz_progress_array	= array (
                                "progress_value" => "expired",
                                "updated_date" => time(),
                                "updated_by" => $ENTRADA_USER->getID()
                            );
                            
                            $entry->fromArray($quiz_progress_array);
                            
                            if (!$entry->update()) {
                                application_log("error", "Unable to update the qprogress_id [".$entry->getQprogressID()."] to expired. Database said: ".$db->ErrorMsg());
                            }
                        }
                        
                        switch ($entry->getProgressValue()) {
                            case "complete" :
                                if (($quizType->getQuiztypeCode() == "delayed" && $userCommunityQuiz->getReleaseUntil() <= time()) || ($quizType->getQuiztypeCode() == "immediate")) {
                                    $percentage = ((round(($entry->getQuizScore() / $entry->getQuizValue()), 2)) * 100);
                                    echo "<li class=\"".(($percentage >= 60) ? "correct" : "incorrect")."\">";
                                    echo	date(DEFAULT_DATE_FORMAT, $entry->getUpdatedDate())." <strong>Score:</strong> ".$entry->getQuizScore()."/".$entry->getQuizValue()." (".$percentage."%)";
                                    echo "</li>";
                                } elseif ($quizType->getQuiztypeCode() == "hide") {
                                    echo "<li>".date(DEFAULT_DATE_FORMAT, $entry->getUpdatedDate())." - <strong>Completed</strong></li>";
                                } else {
                                    echo "<li>".date(DEFAULT_DATE_FORMAT, $entry->getUpdatedDate())." <strong>Score:</strong> To Be Released ".date(DEFAULT_DATE_FORMAT, $userCommunityQuiz->getReleaseUntil())."</li>";
                                }
                                break;
                            case "expired" :
                                echo "<li class=\"incorrect\">".date(DEFAULT_DATE_FORMAT, $entry->getUpdatedDate())." <strong>Expired Attempt</strong>: not completed.</li>";
                                break;
                            case "inprogress" :
                                echo "<li>".date(DEFAULT_DATE_FORMAT, $entry->getUpdatedDate())." <strong>Attempt In Progress</strong> ( <a class=\"start-quiz\" data-aquiz=\"". $userCommunityQuiz->getAquizID()."\" data-href=\"". ENTRADA_URL."/file-seb.php?id=". $userCommunityQuiz->getAquizID()."\" title=\"Take ".html_encode($userCommunityQuiz->getQuizTitle())."\">continue quiz</a> )</li>";
                                break;
                            default :
                                break;
                        }
                    } 
                    ?>
                </ul>
                <?php } ?>
            </td>
            <td><?php echo (((int) $userCommunityQuiz->getReleaseUntil()) ? date(DEFAULT_DATE_FORMAT, $userCommunityQuiz->getReleaseUntil()) : "No Expiration"); ?></td>
		</tr>
	<?php } ?>
	</tbody>
	</table>
    <?php } else { ?>
    <div class="alert alert-info">There are currently no available secure quizzes for your <strong>communities</strong>.</div>
    <?php } ?>
    <div id="modal-access-quiz" class="modal fade">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Start Quiz</h4>
                </div>
                <div class="modal-body">
                    <div class="modal-messages"></div>

                   
                </div>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        jQuery(function($) {
            $('.start-quiz').click(function(){
                var aQuizId = $(this).data('aquiz');
                var aQuizUrl = $(this).data('href');
                var modal = $('.modal');

                modal.find('.modal-messages').html('<div class="alert alert-info"><h4>Quiz Loading...</h4><p>Please wait while your quiz loads. Your quiz will start automatically once this is complete.</p></div>');
                modal.modal('show');

                var formData = "method=get-file&aquiz_id=" + aQuizId; 
                $.ajax({
                    type: "GET",
                    url: "<?php echo ENTRADA_URL; ?>/secure/quizzes?section=api-quizzes-secure-resources",
                    data: formData,
                    dataType: "json",
                    success: function (data) {
                        var jsonResponse = data;

                        if (jsonResponse.status === "success") {
                            modal.modal('hide');
                            window.location = aQuizUrl;
                        } else {
                            modal.find('.modal-messages').html('<div class="alert alert-danger">An error occurred while loading this quiz. Please try again. If the problem persists, please contact an administrator.</div>');
                        }
                    }
                });
            });
        });
	</script>
	<?php
} else { ?>
        <div class="alert alert-info">There are currently <strong>no quizzes available</strong> for you to take in <strong>secure mode</strong>.</div>
<?php } ?>

