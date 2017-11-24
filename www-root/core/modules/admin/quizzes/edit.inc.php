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
 * This file is used to author and share quizzes with other folks who have
 * administrative permissions in the system.
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
} elseif (!$ENTRADA_ACL->amIAllowed('quiz', 'update', false)) {
    $ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";


    $ERRORSTR[]	= "Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    if ($RECORD_ID) {
        $quiz = Models_Quiz::fetchRowByID($RECORD_ID);

        $PROCESSED = $quiz->toArray();

        if ($PROCESSED && $ENTRADA_ACL->amIAllowed(new QuizResource($PROCESSED["quiz_id"]), "update")) {
            $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/".$MODULE."?section=edit&id=".$RECORD_ID, "title" => limit_chars($PROCESSED["quiz_title"], 32));

            $PROCESSED["associated_proxy_ids"] = array();

            /**
             * Load the rich text editor.
             */
            load_rte();

            // Error Checking
            switch ($STEP) {
                case 2 :
                    /**
                     * Required field "quiz_title" / Quiz Title.
                     */
                    if ((isset($_POST["quiz_title"])) && ($tmp_input = clean_input($_POST["quiz_title"], array("notags", "trim")))) {
                        $PROCESSED["quiz_title"] = $tmp_input;
                    } else {
                        add_error("The <strong>Quiz Title</strong> field is required.");
                    }

                    /**
                     * Non-Required field "quiz_description" / Quiz Description.
                     */
                    if ((isset($_POST["quiz_description"])) && ($tmp_input = clean_input($_POST["quiz_description"], array("trim", "allowedtags")))) {
                        $PROCESSED["quiz_description"] = $tmp_input;
                    } else {
                        $PROCESSED["quiz_description"] = "";
                    }

                    /**
                     * Required field "associated_proxy_ids" / Quiz Authors (array of proxy ids).
                     * This is actually accomplished after the quiz is inserted below.
                     */
                    if((isset($_POST["associated_proxy_ids"]))) {
                        $associated_proxy_ids = explode(",", $_POST["associated_proxy_ids"]);
                        foreach($associated_proxy_ids as $contact_order => $proxy_id) {
                            if($proxy_id = clean_input($proxy_id, array("trim", "int"))) {
                                $PROCESSED["associated_proxy_ids"][(int) $contact_order] = $proxy_id;
                            }
                        }
                    }

                    /**
                     * The current quiz author must be in the quiz author list.
                     */
                    if (!in_array($ENTRADA_USER->getActiveId(), $PROCESSED["associated_proxy_ids"])) {
                        array_unshift($PROCESSED["associated_proxy_ids"], $ENTRADA_USER->getActiveId());

                        add_notice("You cannot remove yourself as a <strong>Quiz Author</strong>.");
                    }

                    /**
                     * Get a list of all current quiz authors, and then check to see if
                     * one quiz author is attempting to remove any other quiz authors. If
                     * they are attempting to remove an existing quiz author, then we need
                     * to check and see if that quiz author has already assigned this quiz
                     * to any of their learning events. If they have, then they cannot be
                     * removed because it will pose a data integrity problem.
                     */
                    $contacts = Models_Quiz_Contact::fetchAllRecords($RECORD_ID);
                    if ($contacts) {
                        foreach ($contacts as $contact) {
                            $result = $contact->toArray();
                            if (!in_array($result["proxy_id"], $PROCESSED["associated_proxy_ids"])) {
                                $sresult	= Models_Quiz_Attached::getCurrentContact($RECORD_ID, $result["proxy_id"]);
                                if ($sresult) {
                                    $PROCESSED["associated_proxy_ids"][] = $result["proxy_id"];

                                    add_notice("Unable to remove <strong>".html_encode(get_account_data("fullname", $result["proxy_id"]))."</strong> from the <strong>Quiz Authors</strong> section because they have already attached this quiz to one or more events or communities.");
                                }
                            }
                        }
                    }

                    if (!$ERROR) {
                        $PROCESSED["updated_date"] = time();
                        $PROCESSED["updated_by"] = $ENTRADA_USER->getID();

                        if ($quiz->fromArray($PROCESSED)->update()) {
                            /**
                             * Delete existing quiz contacts, so we can re-add them.
                             */
                            Models_Quiz_Contact::deleteContacts($RECORD_ID);

                            /**
                             * Add the updated quiz authors to the quiz_contacts table.
                             */
                            if ((is_array($PROCESSED["associated_proxy_ids"])) && !empty($PROCESSED["associated_proxy_ids"])) {
                                foreach ($PROCESSED["associated_proxy_ids"] as $proxy_id) {
                                    $contact = new Models_Quiz_Contact(array("quiz_id" => $RECORD_ID, "proxy_id" => $proxy_id, "updated_date" => time(), "updated_by" => $ENTRADA_USER->getActiveID()));
                                    if (!$contact->insert()) {
                                        add_error("There was an error while trying to attach a <strong>Quiz Author</strong> to this quiz.<br /><br />The system administrator was informed of this error; please try again later.");

                                        application_log("error", "Unable to insert a new quiz_contact record while adding a new quiz. Database said: ".$db->ErrorMsg());
                                    }
                                }
                            }

                            add_success("The <strong>Quiz Information</strong> section has been successfully updated.");

                            application_log("success", "Quiz information for quiz_id [".$quiz_id."] was updated.");
                        } else {
                            add_error("There was a problem updating this quiz. The system administrator was informed of this error; please try again later.");

                            application_log("error", "There was an error updating quiz information for quiz_id [".$quiz_id."]. Database said: ".$db->ErrorMsg());
                        }
                    }
                    break;
                case 1 :
                default :

                    $quiz_contacts = Models_Quiz_Contact::fetchAllRecords($RECORD_ID);

                    if ($quiz_contacts) {
                        foreach ($quiz_contacts as $quiz_contact) {
                            $PROCESSED["associated_proxy_ids"][] = $quiz_contact->getProxyID();
                        }
                    }
                    break;
            }

            // Display Content
            switch ($STEP) {
                case 2 :
                case 1 :
                default :
                    if (!$ALLOW_QUESTION_MODIFICATIONS) {
                        echo display_notice(array("<p><strong>Please note</strong> this quiz has already been attempted by at least one person, therefore the questions cannot be modified. If you would like to make modifications to the quiz questions you must copy it first using the Copy Quiz button below and then make your modifications.</p>"));
                    }

                    $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/elementresizer.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
                    $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/AutoCompleteList.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
                    ?>
                    <a name="quiz_information_section"></a>
                    <h2 id="quiz_information_section" title="Quiz Information Section">Quiz Information</h2>
                    <div id="quiz-information-section">
                        <form action="<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>?section=edit&amp;id=<?php echo $RECORD_ID; ?>" method="post" id="editQuizForm" onsubmit="picklist_select('proxy_id')" class="form-horizontal">
                            <input type="hidden" name="step" value="2" />
                            <?php
                            if ($SUCCESS) {
                                fade_element("out", "display-success-box");
                                echo display_success();
                            }

                            if ($NOTICE) {
                                fade_element("out", "display-notice-box", 100, 15000);
                                echo display_notice();
                            }

                            if ($ERROR) {
                                echo display_error();
                            }
                            ?>
                            <div class="control-group">
                                <label for="quiz_title" class="control-label form-required">Quiz Title:</label>
                                <div class="controls">
                                    <input type="text" id="quiz_title" name="quiz_title" class="span10" value="<?php echo html_encode($PROCESSED["quiz_title"]); ?>" maxlength="64" />
                                </div>
                            </div>
                            <div class="control-group">
                                <label for="quiz_description" class="control-label form-nrequired">Quiz Description:</label>
                                <div class="controls">
                                    <textarea id="quiz_description" name="quiz_description" class="expandable span10" rows="3"><?php echo clean_input($PROCESSED["quiz_description"], array("trim", "striptags", "nl2br")); ?></textarea>
                                </div>
                            </div>
                            <div class="control-group">
                                <?php
                                $ONLOAD[] = "author_list = new AutoCompleteList({ type: 'author', url: '". ENTRADA_RELATIVE ."/api/personnel.api.php?type=facultyorstaff', remove_image: '". ENTRADA_RELATIVE ."/images/action-delete.gif'})";
                                ?>
                                <label for="associated_proxy_ids" class="control-label form-required">Quiz Authors:
                                    <div class="content-small" style="margin-top: 15px">
                                        <strong>Tip:</strong> Select any other individuals you would like to give access to assigning or modifying this quiz.
                                    </div>
                                </label>
                                <div class="controls">
                                    <div class="input-append">
                                        <input type="text" id="author_name" name="fullname" class="input-large" autocomplete="off" placeholder="Example: <?php echo html_encode($ENTRADA_USER->getLastname().", ".$ENTRADA_USER->getFirstname()); ?>" />
                                        <button class="btn" type="button" id="add_associated_author">Add</button>
                                    </div>

                                    <div class="autocomplete" id="author_name_auto_complete"></div>
                                    <input type="hidden" id="associated_author" name="associated_proxy_ids" value="" />
                                    <ul id="author_list" class="menu" style="margin-top: 15px">
                                        <?php
                                        if (is_array($PROCESSED["associated_proxy_ids"]) && !empty($PROCESSED["associated_proxy_ids"])) {
                                            foreach ($PROCESSED["associated_proxy_ids"] as $proxy_id) {
                                                $u = User::fetchRowByID($proxy_id);
                                                if ($u && $u->getID()) {
                                                    ?>
                                                    <li class="user" id="author_<?php echo $u->getID(); ?>" style="cursor: move;"><?php echo $u->getFullName(false); ?><img src="<?php echo ENTRADA_URL; ?>/images/action-delete.gif" onclick="author_list.removeItem('<?php echo $u->getID(); ?>');" class="list-cancel-image" /></li>
                                                    <?php
                                                }
                                            }
                                        }
                                        ?>
                                    </ul>
                                    <input type="hidden" id="author_ref" name="author_ref" value="" />
                                    <input type="hidden" id="author_id" name="author_id" value="" />
                                </div>
                            </div>
                            <div class="row-fluid">
                                <button href="#delete-quiz-confirmation-box" id="quiz-control-delete" class="btn btn-danger">Delete Quiz</button>
                                <button href="#copy-quiz-confirmation-box" id="quiz-control-copy" class="btn">Copy Quiz</button>
                                <div class="pull-right">
                                    <input type="submit" class="btn btn-primary" value="Save Changes" />
                                </div>
                            </div>
                        </form>
                    </div>

                    <a name="quiz_questions_section"></a>
                    <h2 id="quiz_questions_section" title="Quiz Content Questions">Quiz Questions</h2>
                    <div id="quiz-content-questions">
                        <?php
                        $questions = Models_Quiz_Question::fetchAllRecords($RECORD_ID);
                        if ($ALLOW_QUESTION_MODIFICATIONS) {
                            $question_types = Models_Quiz_QuestionType::fetchAllRecords();
                            if ($question_types) {
                                ?>
                                <div class="row-fluid space-below">
                                    <?php if (isset($questions) && $questions) { ?>
                                        <a href="#delete-question-confirmation-box" class="btn btn-danger" id="delete-questions" data-toggle="modal">Delete Selected</a>
                                        <a href="#" class="btn" id="group-questions">Group Selected</a>
                                    <?php } ?>
                                    <div class="pull-right">
                                        <div class="btn-group">
                                            <a href="<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>?section=add-question&amp;id=<?php echo $RECORD_ID; ?>&type=1" class="btn btn-success">Add Multiple Choice Question</a>
                                            <button class="btn btn-success dropdown-toggle" data-toggle="dropdown">
                                                <span class="caret"></span>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <?php
                                                foreach ($question_types as $question_type) {
                                                    if ($question_type->getQuestionTypeID() != 1) {
                                                        ?>
                                                        <li><a href="<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>?section=add-question&amp;id=<?php echo $RECORD_ID; ?>&type=<?php echo $question_type->getQuestionTypeID(); ?>">Add <?php echo $question_type->getQuestionTypeTitle(); ?></a></li>
                                                        <?php
                                                    }
                                                }
                                                ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                        }

                        if ($questions) {
                            $i = 0;
                            $last_group_id = NULL;
                            ?>
                            <style type="text/css">
                                .question-group .question-text, .drag-handle {
                                    cursor:pointer;
                                }
                                .question-group {
                                    background:#F8F8F8;
                                    border-right:1px solid #DADADA;
                                    margin-bottom:3px!important;
                                }
                                .question-group .question-group-inner {
                                    padding:0px 20px;
                                }
                                .sortable-placeholder {
                                    background: grey;
                                    width:100%;
                                    height:20px;
                                }

                                .skip {
                                    list-style-type:none;
                                }

                            </style>
                            <div class="quiz-questions">
                                <ol start="<?php echo $i; ?>" class="questions skip <?php echo !is_null($questions[0]->getQquestionGroupID()) ? "question-group" : ""; ?>">
                                    <?php
                                    foreach ($questions as $question) {

                                    if ($question->getQuestionTypeID() != 3) {
                                        $i++;
                                    }

                                    if (is_null($question->getQquestionGroupID()) || ($i > 1 && $last_group_id != $question->getQquestionGroupID())) {
                                    ?>
                                </ol>

                                <ol start="<?php echo $i; ?>" class="questions skip <?php echo !is_null($question->getQquestionGroupID()) ? "question-group" : ""; ?>">
                                    <?php
                                    }
                                    ?>
                                    <li class="question">
                                        <div class="question">
                                            <?php
                                            if ($question->getQuestionTypeID() != 3) {
                                                echo $i.". ";

                                            }
                                            if ($ALLOW_QUESTION_MODIFICATIONS) { ?>
                                                <input type="checkbox" class="question-ids" name="qquestion_ids[]" value="<?php echo $question->getQquestionID(); ?>" data-qquestion-group-id="<?php echo !is_null($question->getQquestionGroupID()) ? $question->getQquestionGroupID() : "0"; ?>" />
                                            <?php } ?>
                                            <span class="question-text"><?php echo $question->getQuestionText(); ?></span>
                                            <?php if ($ALLOW_QUESTION_MODIFICATIONS) { ?>
                                                <div class="pull-right">
                                                    <i class="icon-move drag-handle"></i>
                                                    <a href="<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>?section=edit-question&amp;id=<?php echo $question->getQquestionID(); ?>">
                                                        <i class="icon-pencil question-controls" title="Edit Question"></i>
                                                    </a>
                                                </div>
                                            <?php } ?>
                                        </div>
                                        <div class="row-fluid responses">
                                            <?php
                                            $responses = Models_Quiz_Question_Response::fetchAllRecords($question->getQquestionID());
                                            if ($responses) {
                                                ?>
                                                <ul class="responses">
                                                    <?php foreach ($responses as $response) { ?>
                                                        <li class="<?php echo(($response->getResponseCorrect() == 1) ? "display-correct" : "display-incorrect"); ?>"><?php echo clean_input($response->getResponseText(), (($response->getResponseIsHTML() == 1) ? "trim" : "encode")); ?></li>
                                                    <?php } ?>
                                                </ul>
                                                <?php
                                            }
                                            ?>
                                        </div>
                                    </li>
                                    <?php
                                    $last_group_id = $question->getQquestionGroupID();
                                    }
                                    ?>
                                </ol>
                            </div>


                        <?php
                        if ($ALLOW_QUESTION_MODIFICATIONS) {
                        ?>
                            <div id="delete-question-confirmation-box" class="modal hide fade">
                                <div class="modal-header">
                                    <h1>Delete Quiz <strong>Question</strong> Confirmation</h1>
                                </div>
                                <div class="modal-body">
                                    Do you really wish to remove this question from your quiz?
                                    <br />
                                    <br />
                                    <blockquote>
                                        <div id="delete-question-confirmation-content" class="content">
                                            <ul>

                                            </ul>
                                        </div>
                                    </blockquote>
                                    If you confirm this action, the question will be permanently removed.
                                </div>
                                <div class="modal-footer">
                                    <a href="#" class="btn" data-dismiss="modal">Cancel</a>
                                    <a href="#" class="btn btn-danger" id="delete-questions-confirm">Delete</a>
                                </div>
                            </div>
                            <script type="text/javascript">
                                jQuery(function($) {

                                    $("input.question-ids:checked").prop("checked", false);

                                    $("#group-questions").on("click", function(e) {
                                        var container;

                                        if ($(this).hasClass("ungroup")) {
                                            var questions = new Array();
                                            var counter = 0;
                                            $("input.question-ids").each(function(i, input) {
                                                if ($(input).closest("ol.questions").children("li.question").length > 1 && !$(input).is(":checked")) {
                                                    if ($(".question-ids[value=" + $(input).val() + "]").length > 0) {
                                                        questions[counter] = $(input).closest("ol.questions").clone();
                                                        $(input).closest("ol.questions").remove();
                                                        counter++;
                                                    }
                                                } else {
                                                    questions[counter] = $(input).closest("li.question").clone();
                                                    $(input).closest("li.question").remove();
                                                    counter++;
                                                }

                                                if ($(input).closest("ol.questions").children("li.question").length <= 0) {
                                                    $(input).closest("ol.questions").remove();
                                                }
                                            });

                                            if (questions.length >= 1) {
                                                var start = 1;
                                                $(questions).each(function(i, question) {
                                                    if ($(question).hasClass("question")) {
                                                        var question_parent = $(document.createElement("ol"));
                                                        question_parent.attr("start", start).addClass("questions").append(question);
                                                        $("div.quiz-questions").append(question_parent);
                                                        start++;
                                                    } else {
                                                        $(question).attr("start", start);
                                                        $("div.quiz-questions").append(question);
                                                        start = start + question.children("li").length;
                                                    }
                                                });
                                            }

                                        } else {
                                            $(".question-ids:checked").each(function(i, v) {
                                                if (i == 0) {
                                                    container = $(this).closest("ol.questions");
                                                    if (!container.hasClass("question-group")) {
                                                        container.addClass("question-group");
                                                    }
                                                } else {
                                                    var question = $(this).closest("li.question");
                                                    var question_parent = question.closest("ol.questions");
                                                    if (question_parent.attr("start") != container.attr("start")) {
                                                        container.append(question.clone());
                                                        question.remove();
                                                        if (question_parent.children("li").length >= 0) {
                                                            question_parent.remove();
                                                        }
                                                    }
                                                }
                                            });
                                        }
                                        saveQuestionOrder();
                                        $("input.question-ids:checked").prop("checked", false);
                                        $(this).removeClass("ungroup").html("Group Selected");
                                        e.preventDefault();
                                    });

                                    $("#delete-questions").on("click", function(e) {
                                        $("#delete-question-confirmation-content").children("ul").empty()
                                        $(".question-ids:checked").each(function(i, v) {
                                            var question = $(v).parent("div").children(".question-text").clone();
                                            var new_li = $(document.createElement("li"))
                                            $("#delete-question-confirmation-content ul").append(new_li.append(question));
                                        });
                                    });

                                    $("#delete-questions-confirm").on("click", function(e) {
                                        var delete_ids = "";

                                        $(".question-ids:checked").each(function(i, v) {
                                            delete_ids += (i != 0 ? "," : "") + $(v).val();
                                        });

                                        $.ajax({
                                            type : "POST",
                                            data : { method: "delete-question", qquestion_ids : delete_ids },
                                            url  : "<?php echo ENTRADA_URL . "/admin/" . $MODULE . "?section=api"; ?>",
                                            success: function(data) {
                                                var jsonResponse = JSON.parse(data);
                                                if (jsonResponse.status == "success") {
                                                    $(jsonResponse.data.qquestion_ids).each(function(i, v) {
                                                        var input = $("input.question-ids[value="+v+"]");
                                                        var input_parent = input.closest("li.question");
                                                        var input_container = input.closest("ol.questions");
                                                        input_parent.remove();
                                                        if (input_container.children("li").length >= 0) {
                                                            input_container.remove();
                                                        }
                                                    });
                                                }
                                                if ($("ol.questions").length <= 0) {
                                                    $("#display-no-question-message").removeClass("hide");
                                                }
                                                $("#delete-question-confirmation-box").modal("hide");
                                            }
                                        });

                                        e.preventDefault();
                                    });

                                    $(".quiz-questions").on("change", "input.question-ids", function(e) {
                                        var group = $(this).closest("ol.questions");

                                        if (!$(this).prop("checked")) {
                                            group.find("input.question-ids").removeAttr("checked");
                                        } else {
                                            group.find("input.question-ids").attr("checked", "checked");
                                        }

                                        var grouped = true;
                                        var checked_count = 0;

                                        $("input.question-ids").each(function(i, ui) {
                                            if ($(ui).prop("checked")) {
                                                if (!$(ui).closest("ol.questions").hasClass("question-group")) {
                                                    grouped = false;
                                                    return false;
                                                }
                                                checked_count++;
                                            }
                                        });

                                        if (grouped == true) {
                                            $("#group-questions").html("Ungroup Selected").addClass("ungroup");
                                        } else {
                                            $("#group-questions").html("Group Selected").removeClass("ungroup");
                                        }

                                        if (checked_count <= 0) {
                                            $("#group-questions").html("Group Selected").removeClass("ungroup");
                                        }

                                    });

                                    var temp_qquestion_id = 0;

                                    $(".question-controls-delete").on("click", function(e) {
                                        $("#delete-question-confirmation-content").html($(this).closest("li").find(".question-text").html());
                                        temp_qquestion_id = $(this).closest("li").data("qquestion-id");
                                    });

                                    $("ol.question-group").sortable({
                                        handle : ".question-text",
                                        placeholder: "sortable-placeholder",
                                        helper: "clone",
                                        cursor: "move",
                                        forceHelperSize: true,
                                        stop: function() {
                                            saveQuestionOrder();
                                        }
                                    });
                                    $(".quiz-questions").sortable({
                                        handle : ".drag-handle",
                                        placeholder: "sortable-placeholder",
                                        helper: "clone",
                                        cursor: "move",
                                        forceHelperSize: true,
                                        stop: function() {
                                            saveQuestionOrder();
                                        }
                                    });

                                    function saveQuestionOrder() {
                                        var group = 0;
                                        var current_group = "NULL";
                                        var order_counter = 1;
                                        $("ol.questions").each(function(i, v) {
                                            if ($(v).children("li").length > 1) {
                                                group++;
                                                current_group = group;
                                            } else {
                                                current_group = "NULL";
                                            }
                                            $(v).find("input.question-ids").each(function(i, v) {
                                                $.ajax({
                                                    type : "POST",
                                                    data : { method: "update-question-order", qquestion_id : $(v).val(), order : order_counter, group : current_group },
                                                    url  : "<?php echo ENTRADA_URL . "/admin/" . $MODULE . "?section=api"; ?>",
                                                    success: function(data) {

                                                    }
                                                });
                                                order_counter++;
                                            });
                                        })

                                        var start = 1;
                                        $("ol.questions").each(function(i, v) {
                                            $(v).attr("start", start);
                                            $(v).children("li").each(function(j, w) {
                                                start++;
                                            });
                                        });
                                    }
                                });
                            </script>
                            <?php
                        }
                        } ?>
                        <div id="display-no-question-message" class="display-generic <?php echo ($questions) ? "hide" : ""; ?>">
                            There are currently <strong>no quiz questions</strong> associated with this quiz.<br /><br />To create questions in this quiz click the <strong>Add Question</strong> link above.
                        </div>
                    </div>

                    <div id="delete-quiz-confirmation-box" class="modal-confirmation">
                        <form action="<?php echo ENTRADA_URL."/admin/".$MODULE."?section=delete&amp;id=".$RECORD_ID; ?>" method="post" id="deleteQuizForm" class="form-horizontal">
                            <h1>Delete <strong>Quiz</strong> Confirmation</h1>

                            <div class="alert alert-block alert-danger">
                                <strong>Warning!</strong> Do you really wish to delete the &quot;<span id="delete-quiz-confirmation-content"><strong><?php echo html_encode($PROCESSED["quiz_title"]); ?></strong></span>&quot; quiz? If you proceed with this action the quiz it will no longer be available to learners.
                            </div>

                            <input type="button" class="btn" value="Cancel" onclick="Control.Modal.close()" />
                            <input type="submit" class="btn btn-danger pull-right" value="Delete Quiz" />
                        </form>
                    </div>
                    <div id="copy-quiz-confirmation-box" class="modal-confirmation">
                        <form action="<?php echo ENTRADA_RELATIVE; ?>/admin/<?php echo $MODULE; ?>?section=copy&amp;id=<?php echo $RECORD_ID; ?>" method="post" id="copyQuizForm" class="form-horizontal">
                            <h1>Copy <strong>Quiz</strong> Confirmation</h1>
                            <div class="display-generic">
                                If you would like to create a new quiz based on the existing questions in this quiz, provide a new title and press <strong>Copy Quiz</strong>.
                            </div>

                            <div class="control-group">
                                <label for="quiz_title" class="control-label form-required">New Quiz Title:</label>
                                <div class="controls">
                                    <input type="text" id="quiz_title" name="quiz_title" value="<?php echo html_encode($PROCESSED["quiz_title"]); ?>" maxlength="64" style="width: 96%" />
                                </div>
                            </div>

                            <input type="button" class="btn" value="Cancel" onclick="Control.Modal.close()" />
                            <input type="submit" class="btn btn-primary pull-right" value="Copy Quiz" />
                        </form>
                    </div>
                    <script type="text/javascript" defer="defer">
                        document.observe('dom:loaded', function() {
                            try {
                                // Modal control for deleting quiz.
                                new Control.Modal('quiz-control-delete', {
                                    overlayOpacity:	0.75,
                                    closeOnClick:	'overlay',
                                    className:		'modal-confirmation',
                                    fade:			true,
                                    fadeDuration:	0.30
                                });

                                // Modal control for copying quiz.
                                new Control.Modal('quiz-control-copy', {
                                    overlayOpacity:	0.75,
                                    closeOnClick:	'overlay',
                                    className:		'modal-confirmation',
                                    fade:			true,
                                    fadeDuration:	0.30
                                });
                            } catch (e) {
                                clog(e);
                            }
                        });
                    </script>

                    <a name="learning_events_section"></a>
                    <h2 id="learning_events_section" class="collapsed" title="Learning Events">Learning Events</h2>
                    <div id="learning-events">
                        <?php
                        /**
                         * If there are no questions in this quiz, then
                         * a generic notice is spit out that gives the
                         * user information on when they can assign this
                         * quiz to a learning event.
                         */
                        if (!(int) count($questions)) {
                            ?>
                            <div class="display-generic">
                                Once you create questions for this quiz you will be able to assign it to learning events you are teaching.
                            </div>
                            <?php
                        } else {
                            ?>
                            <a href="<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>?section=attach&amp;id=<?php echo $RECORD_ID; ?>" class="btn btn-success pull-right"><i class="icon-plus-sign icon-white"></i> Attach To Learning Event</a>
                            <div class="clear" style="margin-bottom: 15px"></div>
                            <?php
                            $event_attached_quizzes = Models_Quiz_Attached_Event::fetchAllByQuizID($RECORD_ID);

                            if ($event_attached_quizzes) {
                                ?>
                                <table class="tableList" cellspacing="0" summary="List of Learning Events">
                                    <colgroup>
                                        <col class="modified" />
                                        <col class="date" />
                                        <col class="title" />
                                        <col class="title" />
                                        <col class="completed" />
                                    </colgroup>
                                    <thead>
                                    <tr>
                                        <td class="modified">&nbsp;</td>
                                        <td class="date sortedDESC" style="border-left: 1px solid #999999"><div class="noLink">Date &amp; Time</div></td>
                                        <td class="title">Event Title</td>
                                        <td class="title">Quiz Title</td>
                                        <td class="completed">Completed</td>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    foreach($event_attached_quizzes as $attached_quiz) {
                                        $url = ENTRADA_URL."/admin/events?section=content&id=".$attached_quiz->getEventID();
                                        $completed_attempts = $attached_quiz->getCompletedAttempts();

                                        echo "<tr id=\"event-".$attached_quiz->getEventID()."\" class=\"event\">\n";
                                        echo "	<td class=\"modified\">\n";
                                        if ($completed_attempts > 0) {
                                            echo "	<a href=\"".ENTRADA_URL."/admin/quizzes?section=results&amp;id=".$attached_quiz->getAQuizID()."\"><img src=\"".ENTRADA_URL."/images/view-stats.gif\" width=\"16\" height=\"16\" alt=\"View results of ".html_encode($attached_quiz->getQuizTitle())."\" title=\"View results of ".html_encode($attached_quiz->getQuizTitle())."\" style=\"vertical-align: middle\" border=\"0\" /></a>\n";
                                        } else {
                                            echo "	<img src=\"".ENTRADA_URL."/images/view-stats-disabled.gif\" width=\"16\" height=\"16\" alt=\"No completed quizzes at this time.\" title=\"No completed quizzes at this time.\" style=\"vertical-align: middle\" border=\"0\" />\n";
                                        }
                                        echo "	</td>\n";
                                        echo "	<td class=\"date\"><a href=\"".$url."\" title=\"Event Date\">".date(DEFAULT_DATE_FORMAT, $attached_quiz->getEventStart())."</a></td>\n";
                                        echo "	<td class=\"title\"><a href=\"".$url."\" title=\"Event Title: ".html_encode($attached_quiz->getEventTitle())."\">".html_encode($attached_quiz->getEventTitle())."</a></td>\n";
                                        echo "	<td class=\"title\"><a href=\"".$url."\" title=\"Quiz Title: ".html_encode($attached_quiz->getQuizTitle())."\">".html_encode($attached_quiz->getQuizTitle())."</a></td>\n";
                                        echo "	<td class=\"completed\">".(int) $completed_attempts."</td>\n";
                                        echo "</tr>\n";
                                    }
                                    ?>
                                    </tbody>
                                </table>
                                <?php
                            } else {
                                echo display_notice(array("This quiz is not currently attached to any learning events.<br /><br />To add this quiz to an event you are teaching, click the <strong>Attach To Learning Event</strong> link above."));
                            }
                        }
                        ?>
                    </div>

                    <a name="community_pages_section"></a>
                    <h2 id="community_pages_section" class="collapsed" title="Community Pages Section">Community Pages</h2>
                    <div id="community-pages-section">
                        <?php
                        /**
                         * If there are no questions in this quiz, then
                         * a generic notice is spit out that gives the
                         * user information on when they can assign this
                         * quiz to a learning event.
                         */
                        if (!(int) count($questions)) {
                            ?>
                            <div class="display-generic">
                                Once you create questions for this quiz you will be able to assign it to pages in communities you administrate.
                            </div>
                            <?php
                        } else {
                            ?>
                            <a href="<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>?section=attach&amp;community=true&amp;id=<?php echo $RECORD_ID; ?>" class="btn btn-success pull-right"><i class="icon-plus-sign icon-white"></i> Attach To Community Page</a>
                            <div class="clear" style="margin-bottom: 15px"></div>
                            <?php
                            $community_page_attachments = Models_Quiz_Attached_CommunityPage::fetchAllByQuizID($RECORD_ID);
                            if($community_page_attachments) {
                                ?>
                                <table class="tableList" cellspacing="0" summary="List of Community Pages">
                                    <colgroup>
                                        <col class="modified" />
                                        <col class="title" />
                                        <col class="title" />
                                        <col class="completed" />
                                    </colgroup>
                                    <thead>
                                    <tr>
                                        <td class="modified">&nbsp;</td>
                                        <td class="title sortedASC">Community Page</td>
                                        <td class="title">Quiz Title</td>
                                        <td class="completed">Completed</td>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    foreach($community_page_attachments as $community_page_attachment) {
                                        $result = $community_page_attachment->toArray();
                                        $url = ENTRADA_URL."/community".$result["community_url"].":".$result["page_url"];
                                        $completed_attempts = $community_page_attachment->getCompletedAttempts();

                                        echo "<tr id=\"community-page-".$result["cpage_id"]."\" class=\"community-page\">\n";
                                        echo "	<td class=\"modified\">\n";
                                        if ($completed_attempts > 0) {
                                            echo "	<a href=\"".ENTRADA_URL."/admin/quizzes?section=results&amp;community=true&amp;id=".$result["aquiz_id"]."\"><img src=\"".ENTRADA_URL."/images/view-stats.gif\" width=\"16\" height=\"16\" alt=\"View results of ".html_encode($result["quiz_title"])."\" title=\"View results of ".html_encode($result["quiz_title"])."\" style=\"vertical-align: middle\" border=\"0\" /></a>\n";
                                        } else {
                                            echo "	<img src=\"".ENTRADA_URL."/images/view-stats-disabled.gif\" width=\"16\" height=\"16\" alt=\"No completed quizzes at this time.\" title=\"No completed quizzes at this time.\" style=\"vertical-align: middle\" border=\"0\" />\n";
                                        }
                                        echo "	</td>\n";
                                        echo "	<td class=\"title\"><a href=\"".$url."\" title=\"Community Page: ".html_encode($result["page_title"])."\">".html_encode($result["page_title"])."</a></td>\n";
                                        echo "	<td class=\"title\"><a href=\"".$url."\" title=\"Quiz Title: ".html_encode($result["quiz_title"])."\">".html_encode($result["quiz_title"])."</a></td>\n";
                                        echo "	<td class=\"completed\">".(int) $completed_attempts."</td>\n";
                                        echo "</tr>\n";
                                    }
                                    ?>
                                    </tbody>
                                </table>
                                <?php
                            } else {
                                echo display_notice(array("This quiz is not currently attached to any community pages.<br /><br />To add this quiz to an page you are have administrative rights to, click the <strong>Attach To Community Page</strong> link above."));
                            }
                        }
                        ?>
                    </div>

                    <?php
                    /**
                     * Sidebar item that will provide the links to the different sections within this page.
                     */
                    $sidebar_html  = "<ul class=\"menu\">\n";
                    $sidebar_html .= "	<li class=\"link\"><a href=\"#quiz_information_section\" onclick=\"$('quiz_information_section').scrollTo(); return false;\" title=\"Quiz Information\">Quiz Information</a></li>\n";
                    $sidebar_html .= "	<li class=\"link\"><a href=\"#quiz_questions_section\" onclick=\"$('quiz_questions_section').scrollTo(); return false;\" title=\"Quiz Questions\">Quiz Questions</a></li>\n";
                    $sidebar_html .= "	<li class=\"link\"><a href=\"#learning_events_section\" onclick=\"$('learning_events_section').scrollTo(); return false;\" title=\"Learning Events\">Learning Events</a></li>\n";
                    $sidebar_html .= "	<li class=\"link\"><a href=\"#community_pages_section\" onclick=\"$('community_pages_section').scrollTo(); return false;\" title=\"Learning Events\">Community Pages</a></li>\n";
                    $sidebar_html .= "</ul>\n";

                    new_sidebar_item("Page Anchors", $sidebar_html, "page-anchors", "open", "1.9");
                    break;
            }
        } else {
            add_error("In order to edit a quiz, you must provide a valid quiz identifier.");

            echo display_error();

            application_log("notice", "Failed to provide a valid quiz identifer [".$RECORD_ID."] when attempting to edit a quiz.");
        }
    } else {
        add_error("In order to edit a quiz, you must provide a quiz identifier.");

        echo display_error();

        application_log("notice", "Failed to provide a quiz identifier to edit a quiz.");
    }
}