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
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Robert Fotino <robert.fotino@gmail.com>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 */

if (!defined("PARENT_INCLUDED")) {
    exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("examquestion", "create", false)) {
    add_error(sprintf($translate->_("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));
    echo display_error();
    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    $MODULE_TEXT = $translate->_($MODULE);
    $SUBMODULE_TEXT = $MODULE_TEXT["questions"][$SUBMODULE];

    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/exams/questions?section=import", "title" => $translate->_("Import"));
    
    $sub_navigation = Views_Exam_Exam::GetQuestionsSubnavigation("import");
    echo $sub_navigation;
    ?>
    <h1><?php echo $SUBMODULE_TEXT["title_import"]; ?></h1>
    <?php
    $default_folder_id = isset($_POST["folder_id"]) ? (int)$_POST["folder_id"] : 0;
    // Error checking
    switch ($STEP) {
        case 3:
            $questions = json_decode($_POST["questions"], true);
            if ($questions && is_array($questions)) {
                foreach ($questions as $index => $question) {
                    Models_Exam_Question_Parser::import($question, $index + 1);
                }
            }

            if (!has_error()) {
                $message = $SUBMODULE_TEXT["text_import_success_01"] . " " . count($questions) . " " . $SUBMODULE_TEXT["text_import_success_02"];
                Entrada_Utilities_Flashmessenger::addMessage($message, "success", $MODULE);
                if ($default_folder_id != 0) {
                    $url = ENTRADA_URL . "/admin/" . $MODULE . "/questions?folder_id=" . $default_folder_id;
                    $message = $SUBMODULE_TEXT["text_redirect_01"] . "<strong><a href=\"" . $url . "\">" . $SUBMODULE_TEXT["text_redirect_02"] . "</a></strong>" . $SUBMODULE_TEXT["text_redirect_03"];
                    Entrada_Utilities_Flashmessenger::addMessage($message, "success", $MODULE);
                }
            }
            break;
        case 2:
            $question_text  = isset($_POST["question_text"]) ? (string)$_POST["question_text"] : "";
            $questions      = Models_Exam_Question_Parser::parse($question_text);

            if ($questions && is_array($questions)) {
                if (0 === count($questions)) {
                    Entrada_Utilities_Flashmessenger::addMessage($SUBMODULE_TEXT["text_no_questions"], "error", $MODULE);
                } else {
                    foreach ($questions as $index => &$question) {
                        Models_Exam_Question_Parser::validate($question, $index + 1, $default_folder_id);
                    }
                    unset($question);
                }
            }
            if (has_error()) {
                echo display_error();
                $STEP = 1;
            }
            break;
        case 1:
        default:
            // Do nothing
            break;
    }
    
    // Display content
    $success  = Entrada_Utilities_Flashmessenger::getMessages($MODULE, "success", false);

    Entrada_Utilities_Flashmessenger::displayMessages($MODULE);

    if ($success) {
        if ($default_folder_id != 0) {
            $ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";
        }
    }

    switch ($STEP) {
        case 2:
            ?>
            <div class="alert alert-warning">
                <?php echo $SUBMODULE_TEXT["text_please_review"]; ?>
            </div>
            <?php
            // Show a confirmation before importing the questions.
            foreach ($questions as $question) {
                $question_view = new Views_Exam_Question_ImportPreview($question);
                echo $question_view->render();
            }
            ?>
            <form class="form" method="post">
                <input type="hidden" name="step" value="3" />
                <input type="hidden" name="folder_id" value="<?php echo $default_folder_id;?>" />
                <input type="hidden" name="questions" value="<?php echo html_encode(json_encode($questions)); ?>" />
                <input type="submit" class="btn btn-primary pull-right" value="<?php echo $SUBMODULE_TEXT["btn_confirm"]; ?>" />
            </form>
            <?php
            break;
        case 1:
        case 3:
            ?>
            <div class="alert alert-info">
                <?php echo $translate->_('
                This is a tool to allow you to import one or more exam questions using a specific
                text format. Questions are separated by a blank line. The format for each
                question is as follows:<br /><br />

                Each question begins with a question stem, which begins with "Q:" or "1."
                where 1 can be any number, so you can number your questions sequentially.
                Question stems can span multiple lines, and may contain blank lines. The
                following are valid question stems:<br /><br />

                Q: What is your favorite color?<br /><br />

                1. What is<br />
                your favorite<br />
                <br />
                color?<br /><br />

                2. What is your favorite color?<br /><br />

                The question stem is followed by question choices and attributes, each of
                which must be on a separate line. Question choices begin with a letter followed
                by a period, like "a." or "b.". Attributes begin with a word followed by
                a colon, like "type:" or "folder:".<br /><br />

                The "type" attribute is required for all question types. The "folder" attribute
                is required if no default folder is selected. For multiple choice questions, at
                least one answer choice is required, as well as the "answer" attribute. Optional
                attributes are "description", "rationale", "correct_text", and "code". A full
                example of a multiple choice question is shown below.<br /><br />

                1. What is your favorite color?<br />
                a. blue<br />
                b. red <br />
                c. green<br />
                answer: a<br />
                type: mc_v<br />
                folder: /some/folder
                '); ?>
            </div>
            <form class="form" method="post">
                <input type="hidden" name="step" value="2" />
                <div class="control-group">
                    <div class="control-label">
                        <label for="folder_id"><?php echo $SUBMODULE_TEXT["text_default_folder"];?>:</label>
                    </div>
                    <div class="controls">
                        <select id="folder_id" name="folder_id">
                            <option value="0">-- <?php echo $SUBMODULE_TEXT["text_select_folder"]; ?> --</option>
                            <?php
                            function output_folder_options($parent_folder_id = 0, $prefix = "/") {
                                $folders = Models_Exam_Question_Bank_Folders::fetchAllByParentID($parent_folder_id);
                                foreach ($folders as $folder) {
                                    $folder_path = $prefix . $folder->getFolderTitle();
                                    $selected = isset($_POST["folder_id"]) && $_POST["folder_id"] == $folder->getID();
                                    echo "<option value=\"" . $folder->getID() . "\"" . ($selected ? " selected" : "") . ">" . html_encode($folder_path) . "</option>\n";
                                    output_folder_options($folder->getID(), $folder_path."/");
                                }
                            }
                            output_folder_options();
                            ?>
                        </select>
                    </div>
                </div>
                <div class="control-group">
                    <div class="control-label">
                        <label for="question_text"><?php echo $SUBMODULE_TEXT["label_question_text"];?>:</label>
                    </div>
                    <div class="controls">
                        <textarea id="question_text" name="question_text" style="width: 100%; resize: both; box-sizing: border-box;" rows="10"><?php echo $question_text; ?></textarea>
                    </div>
                </div>
                <input type="submit" class="btn btn-primary pull-right" value="<?php echo $SUBMODULE_TEXT["btn_import_q"];?>" />
            </form>
            <?php
            break;
    }
}