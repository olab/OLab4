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
 * This file is used by quiz authors to disable a particular quiz.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_QUIZZES"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("quiz", "update", false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    $BREADCRUMB[] = array("title" => "Delete Quizzes");

    $delete_quizzes = array();
    $quiz_ids = array();
    ?>
    <h1>Delete Quizzes</h1>
    <?php
    /**
     * Check for multiple items being deleted (usually from the Quizzes index file).
     */
    if (isset($_POST["delete"]) && is_array($_POST["delete"]) && !empty($_POST["delete"])) {
        foreach ($_POST["delete"] as $quiz_id) {
            $quiz_id = (int) $quiz_id;
            if ($quiz_id) {
                $quiz_ids[] = $quiz_id;
            }
        }

        if ($quiz_ids) {
            $quizzes = array();
            foreach ($quiz_ids as $quiz_id) {
                $quizzes[] = Models_Quiz::fetchRowByID($quiz_id);
            }
            if ($quizzes) {
                foreach ($quizzes as $quiz) {
                    $q_id = $quiz->getQuizID();
                    if ($ENTRADA_ACL->amIAllowed(new QuizResource($q_id), "update")) {
                        $delete_quizzes[] = $quiz;
                    }
                }
            }
        }
    }

    if (!empty($delete_quizzes)) {
        $total_quizzes = count($delete_quizzes);

        if (isset($_POST["confirmed"]) && $_POST["confirmed"]) {
            if ($delete_quizzes) {
                foreach ($delete_quizzes as $quiz) {
                    if (!$quiz->fromArray(array("quiz_active" => "0"))->update()) {
                        $ERROR++;
                    }
                }
            }
            if (!$ERROR) {
				$ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 5000)";

                add_success("You have successfully deleted ".($total_quizzes != 1 ? "these quizzes" : "this quiz").".<br /><br />You will now be redirected back to the quiz index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".ENTRADA_URL."/admin/".$MODULE."\" style=\"font-weight: bold\">click here</a> to continue.");

				echo display_success();

				application_log("success", "Successfully deleted quiz_ids [".implode(", ", $quiz_ids)."].");
            } else {
				$ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 5000)";

                add_error("We were unable to delete the selected quiz".($total_quizzes != 1 ? "zes" : "")." at this time, please try again later.<br /><br />You will now be redirected back to the quiz index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".ENTRADA_URL."/admin/".$MODULE."\" style=\"font-weight: bold\">click here</a> to continue.");

                echo display_error();

                application_log("error", "Unable to deactivate quiz_ids [".implode(", ", $quiz_ids)."]. Database said: ".$db->ErrorMsg());
            }
        } else {
            ?>
			<script type="text/javascript">
				jQuery(document).ready(function () {
					jQuery("#delete-quizzes").on("click", function (event) {
						var checked = document.querySelectorAll("input.delete-control:checked").length === 0 ? false : true;
						if (!checked) {
							event.preventDefault();
							var errors = new Array();
							errors[0] = "You must select at least 1 quiz to delete by checking the checkbox to the left the quiz.";
							display_error(errors, "#msg");
						}
					});
				});
			</script>
            <div class="alert alert-block">
                <strong>Warning!</strong> Do you really wish to delete the quiz<?php echo ($total_quizzes != 1 ? "zes" : ""); ?> below? If you proceed with this action the selected quiz<?php echo ($total_quizzes != 1 ? "zes" : ""); ?> will no longer be available to learners.
            </div>
			<div id="msg"></div>
            <form action="<?php echo ENTRADA_RELATIVE; ?>/admin/<?php echo $MODULE; ?>?section=delete" method="post">
                <input type="hidden" name="confirmed" value="1" />
                <table class="table table-striped table-bordered" summary="List of Quizzes Pending Delete">
                    <thead>
                        <tr>
							<th width="5%">&nbsp;</th>
							<th width="30%">Quiz Title</th>
							<th width="25%">Author</th>
							<th width="15%">Questions</th>
							<th width="25%">Last Updated</th>
						</tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($delete_quizzes as $quiz) {
                            echo "<tr>\n";
                            echo "	<td class=\"modified\"><input class=\"delete-control\" type=\"checkbox\" name=\"delete[]\" value=\"".(int) $quiz->getQuizID()."\" checked=\"checked\" /></td>\n";
                            echo "	<td class=\"title\"><a href=\"".ENTRADA_RELATIVE."/admin/".$MODULE."?section=edit&amp;id=".(int) $quiz->getQuizID()."\">".html_encode($quiz->getQuizTitle())."</a></td>\n";
							echo "	<td class=\"author\"><a href=\"".ENTRADA_RELATIVE."/admin/".$MODULE."?section=edit&amp;id=".(int) $quiz->getQuizID()."\">".html_encode($quiz->getQuizAuthor()->getFullname())."</a></td>\n";
							echo "	<td class=\"questions\"><a href=\"".ENTRADA_RELATIVE."/admin/".$MODULE."?section=edit&amp;id=".(int) $quiz->getQuizID()."\">".count(Models_Quiz_Question::fetchAllRecords($quiz->getQuizID()))."</a></td>\n";
                            echo "	<td class=\"updated\"><a href=\"".ENTRADA_RELATIVE."/admin/".$MODULE."?section=edit&amp;id=".(int) $quiz->getQuizID()."\">".date("Y-m-d g:ia", $quiz->getUpdatedDate())."</a></td>\n";
                            echo "</tr>\n";
                        }
                        ?>
                    </tbody>
                </table>
				<div class="row-fluid">
					<a href="<?php echo ENTRADA_RELATIVE."/admin/".$MODULE; ?>" class="btn">Cancel</a>
                    <input id="delete-quizzes" type="submit" class="btn btn-danger pull-right" value="Confirm Delete" />
				</div>
            </form>
            <?php
        }
    } elseif ($RECORD_ID) {
		$quiz = Models_Quiz::fetchRowByID($RECORD_ID);
		if ($quiz && $ENTRADA_ACL->amIAllowed(new QuizResource($quiz->getQuizID()), "update")) {
			if ($quiz->fromArray(array("quiz_active" => "0"))->update()) {
				$ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 5000)";

                add_success("You have successfully deleted this quiz.<br /><br />You will now be redirected back to the quiz index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".ENTRADA_URL."/admin/".$MODULE."\" style=\"font-weight: bold\">click here</a> to continue.");

				echo display_success();

				application_log("success", "Successfully deleted quiz_id [".$RECORD_ID."].");
			} else {
				$ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 5000)";

                add_error("We were unable to delete this quiz at this time, please try again later.<br /><br />You will now be redirected back to the quiz index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".ENTRADA_URL."/admin/".$MODULE."\" style=\"font-weight: bold\">click here</a> to continue.");

                echo display_error();

                application_log("error", "Unable to deactivate quiz_id [".$RECORD_ID."]. Database said: ".$db->ErrorMsg());
            }
		} else {
			$ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 5000)";

			add_error("In order to delete a quiz you must provide a quiz identifier.<br /><br />You will now be redirected back to the quiz index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".ENTRADA_URL."/admin/".$MODULE."\" style=\"font-weight: bold\">click here</a> to continue.");

			echo display_error();

			application_log("notice", "Failed to provide a valid quiz identifer [".$RECORD_ID."] when attempting to delete a quiz.");
		}
	} else {
		$ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 5000)";
		add_error("In order to delete a quiz you must provide a quiz identifier.<br /><br />You will now be redirected back to the quiz index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".ENTRADA_URL."/admin/".$MODULE."\" style=\"font-weight: bold\">click here</a> to continue.");

		echo display_error();

		application_log("notice", "Failed to provide a quiz identifier when attempting to delete a quiz.");
	}
}