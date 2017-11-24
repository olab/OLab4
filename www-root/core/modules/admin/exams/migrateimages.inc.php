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
} elseif (!$ENTRADA_ACL->amIAllowed("examquestion", "update", false)) {
    add_error(sprintf($translate->_("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));
    echo display_error();
    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/exams/questions?section=import", "title" => $translate->_(""));
    $HEAD[] = "<script type='text/javascript' src='" . ENTRADA_URL . "/javascript/bootstrap-filestyle.min.js?release=".html_encode(APPLICATION_VERSION)."'></script>";
    
    $sub_navigation = Views_Exam_Exam::GetQuestionsSubnavigation("migrateimages");
    echo $sub_navigation;
    ?>
    <h1><?php echo $translate->_("Migrate ExamSoft Question Images"); ?></h1>
    <?php
    // Error checking
    switch($STEP) {
        case 3:
            // Add the images to the question stems here and set the images_added flag to 1
            $parsed = json_decode($_POST["questions"], true);
            $db->StartTrans();
            foreach ($parsed as $imported_question) {
                $question = Models_Exam_Question_Versions::fetchRowByVersionID($imported_question["question_id"]);
                if ($question->getExamsoftImagesAdded()) {
                    // We have already added images to this question, skip it.
                    continue;
                }
                $image_tags = "";
                foreach ($imported_question["images"] as $image) {
                    $img_data = explode(",", $image["src"]);
                    if ("data:image/png;base64" === $img_data[0]) {
                        $mime_type = "image/png";
                        $ext = ".png";
                    } else if ("data:image/jpeg;base64" === $img_data[0]) {
                        $mime_type = "image/jpeg";
                        $ext = ".jpg";
                    } else if ("data:image/gif;base64" === $img_data[0]) {
                        $mime_type = "image/gif";
                        $ext = ".gif";
                    } else {
                        add_error($translate->_("Image type not recognized").": ".$img_data[0]);
                        continue;
                    }
                    $img_data = str_replace(" ", "+", $img_data[1]);
                    $img_data = base64_decode($img_data);
                    // Save image info to database
                    $learning_object = new Models_LearningObject(array(
                        "filename" => "migrated_from_examsoft".$ext,
                        "filesize" => strlen($img_data),
                        "mime_type" => $mime_type,
                        "description" => "This is an image from an exam question that has been automatically migrated from ExamSoft.",
                        "proxy_id" => $ENTRADA_USER->getProxyID(),
                        "updated_date" => time(),
                        "updated_by" => $ENTRADA_USER->getProxyID()
                    ));
                    if (!$learning_object->insert()) {
                        add_error($translate->_("Error saving image information in the database."));
                        continue;
                    }
                    // Save image data to file system
                    $lor_dir = LOR_STORAGE_PATH."/".$ENTRADA_USER->getProxyID();
                    if (!is_dir($lor_dir)) {
                        mkdir($lor_dir);
                    }
                    $lo_file_id = $learning_object->getLoFileID();
                    $lor_file_location = $lor_dir."/".$lo_file_id;
                    if (!file_put_contents($lor_file_location, $img_data)) {
                        add_error($translate->_("Error saving image data to file system."));
                        continue;
                    }
                    // Add image tag to question stem
                    $image_url = ENTRADA_URL."/api/serve-learning-object.api.php?id=".$lo_file_id."&filename=".urlencode($learning_object->getFilename());
                    $image_tags .= "<img src=\"".$image_url."\" />\n<br />\n";
                }
                $question_text = $question->getQuestionText();
                $question_text = $image_tags.$question_text;
                $question->setQuestionText($question_text);
                // Set "images added" on this question so we don't try to add images to this question
                // from another exam.
                $question->setExamsoftImagesAdded(1);
                // Set this question to "flagged" so that we know it needs some additional touching up,
                // since the images are appended to the end of the question stem and probably aren't in
                // the right place.
                $question->setExamsoftFlagged(1);
                // Update the question in the database.
                if (!$question->update()) {
                    add_error($translate->_("Error updating question with added images."));
                    $db->FailTrans();
                }
            }
            if (has_error()) {
                $db->FailTrans();
            }
            $db->CompleteTrans();
            if (!has_error()) {
                add_success($translate->_("Successfully updated all questions with added images. They have been flagged in the system for further inspection, since the images might not be in the right location in the question stem."));
            }
            break;
        case 2:
            // Extract the images from the HTML file
            if (!isset($_FILES["questions"])) {
                add_error($translate->_("You must choose a questions file."));
            } else if ($_FILES["questions"]["error"]) {
                add_error($translate->_("There was an error uploading the provided questions file."));
            } else {
                $html_text = file_get_contents($_FILES["questions"]["tmp_name"]);
                // Warn the user if their file doesn't look like HTML
                $html_start = "<!doctype";
                if (strtolower(substr($html_text, 0, strlen($html_start))) !== $html_start) {
                    add_notice($translate->_("Your file may not be in HTML format."));
                }
                $parsed = Models_Exam_Question_Parser::parseExamsoftImages($html_text);
            }
            // If there was an error go back to step 1
            if (has_error()) {
                $STEP = 1;
            }
            break;
        case 1:
        default:
            // Do nothing
            break;
    }

    // Display content
    if (has_error()) {
        echo display_error();
    }
    if (has_notice()) {
        echo display_notice();
    }
    if (has_success()) {
        echo display_success();
    }
    switch ($STEP) {
        case 2:
            ?>
            <div class="alert alert-warning">
                <?php echo $translate->_("Please review the following images before confirming the import."); ?>
            </div>
            <?php
            foreach ($parsed as $question) {
                foreach ($question["images"] as $image) {
                    echo "<div style=\"text-align: center;\">\n";
                    echo "<hr />\n";
                    echo "<img src=\"".$image["src"]."\" width=\"".$image["width"]."\" height=\"".$image["height"]."\" />\n";
                    echo "</div>\n";
                }
            }
            ?>
            <form class="form" method="post">
                <input type="hidden" name="step" value="3" />
                <input type="hidden" name="questions" value="<?php echo html_encode(json_encode($parsed)); ?>" />
                <input type="submit" class="btn btn-primary pull-right" value="<?php echo $translate->_("Confirm Exam Import"); ?>" />
            </form>
            <?php
            break;
        case 1:
        case 3:
            ?>
            <form class="form form-horizontal" method="post" enctype="multipart/form-data">
                <input type="hidden" name="step" value="2" />
                <div class="control-group">
                    <div class="control-label">
                        <label for="questions" class="form-required"><?php echo $translate->_("Questions file (in HTML format)"); ?>:</label>
                    </div>
                    <div class="controls">
                        <input type="file" name="questions" id="questions" />
                        <script type="text/javascript">
                            jQuery("#questions").filestyle({
                                icon: true,
                                buttonText: " Find File"
                            });
                        </script>
                    </div>
                </div>
                <input type="submit" class="btn btn-primary pull-right" value="<?php echo $translate->_("Import Question Images"); ?>" />
            </form>
            <?php
            break;
    }
}