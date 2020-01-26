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
 * @author Organisation: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Daniel Noji <dnoji@mednet.ucla.edu>
 * @copyright Copyright 2015 UC Regents. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed(new ExamQuestionGroupResource($PROCESSED["group_id"], true), "update")) {
    $link = sprintf("<a href=\"mailto:%s\">%s</a>", html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"]));
    $message = $SECTION_TEXT["error"]["03"] . "<br /><br />" . $SECTION_TEXT["error"]["01b"] .$link . $SECTION_TEXT["error"]["01c"];
    add_error($message);

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    $default_buttons = $translate->_("default");
    $flash_messages = Entrada_Utilities_Flashmessenger::getMessages($MODULE);
    if ($flash_messages) {
        foreach ($flash_messages as $message_type => $messages) {
            switch ($message_type) {
                case "error" :
                    echo display_error($messages);
                break;
                case "success" :
                    echo display_success($messages);
                break;
                case "notice" :
                default :
                    echo display_notice($messages);
                break;
            }
        }
    }
    ?>
    <div id="msgs" class="row-fluid"></div>
    <form id="group-form" action="<?php echo ENTRADA_URL."/admin/".$MODULE."/".$SUBMODULE."?section=api-group"; ?>" class="form-horizontal" method="post">
        <input type="hidden" id="group_id" name="group_id" value="<?php echo $PROCESSED["group_id"]; ?>" />
        <input type="hidden" id="exam_id" name="exam_id" value="<?php echo $PROCESSED["exam_id"]; ?>" />
        <div class="alert alert-notice">
            <?php echo $SECTION_TEXT["text_instructions"]; ?>
        </div>
        <div class="control-group">
            <label class="control-label form-required" for="group-title"><?php echo $SECTION_TEXT["label_title"]; ?></label>
            <div class="controls">
                <input type="text" name="group_title" id="group-title" class="span11" value="<?php echo $PROCESSED["group_title"]; ?>"/>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="group-description"><?php echo $SECTION_TEXT["label_description"]; ?></label>
            <div class="controls">
                <textarea name="group_description" id="group-description" class="span11 expandable"><?php echo $PROCESSED["group_description"]; ?></textarea>
            </div>
        </div>
        <?php if (defined("EDIT_GROUP") && EDIT_GROUP === true) {
            ?>
            <script type="text/javascript">
                jQuery(function($) {
                    $("#contact-selector").audienceSelector({
                        "filter" : "#contact-type",
                        "target" : "#author-list",
                        "content_type" : "group-author",
                        "content_target" : "<?php echo $PROCESSED["group_id"]; ?>",
                        "api_url" : "<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-group" ; ?>",
                        "delete_attr" : "data-egauthor-id"
                    });
                });
            </script>
            <div class="control-group">
                <label class="control-label" for="contact-selector"><?php echo $SECTION_TEXT["label_permission"]; ?></label>
                <div class="controls">
                    <input type="text" name="contact_select" id="contact-selector" />
                    <select name="contact_type" id="contact-type" class="span3">
                        <?php foreach ($DEFAULT_TEXT_LABELS["contact_types"] as $contact_type => $contact_type_name) { ?>
                            <option value="<?php echo $contact_type; ?>"><?php echo $contact_type_name; ?></option>
                        <?php } ?>
                    </select>
                    <?php
                    $authors = Models_Exam_Group_Author::fetchAllRecords($PROCESSED["group_id"]);
                    if (!empty($authors)) {
                        ?>
                        <ul class="unstyled" id="author-list">
                            <?php foreach ($authors as $author) { ?>
                                <li><a href="#" class="remove-permission" data-author-id="<?php echo $author->getID(); ?>" data-type="group"><i class="icon-remove-circle"></i></a> <?php echo $author->getAuthorName(); ?></li>
                            <?php } ?>
                        </ul>
                    <?php } ?>
                </div>
            </div>
            <div class="row-fluid">
                <?php
                if (isset($PROCESSED["exam_id"]) && $PROCESSED["exam_id"]) {
                    ?>
                    <a id="back-to-exam" class="btn btn-default pull-left" href="<?php echo ENTRADA_URL."/admin/exams/exams?section=edit-exam&id=".$PROCESSED["exam_id"]; ?>">
                        <?php echo $SECTION_TEXT["text_back_to_exam"]; ?>
                    </a>
                <?php
                }
                ?>
                <input id="save-button" type="submit" class="btn btn-primary pull-right" value="<?php echo $default_buttons["btn_save"]; ?>" />
            </div>
            <h2><?php echo $SECTION_TEXT["text_attached_questions"]; ?></h2>
            <div class="pull-right">
                <a href="#delete-group-question-modal" data-toggle="modal" class="btn btn-danger disabled" id="btn-delete-question"><i class="delete-icon fa fa-trash-o fa-fw"></i> <?php echo $SECTION_TEXT["text_delete_questions"]; ?></a>
                <div class="btn-group">
                    <a id="add-element" class="btn btn-success" href="<?php echo ENTRADA_URL."/admin/".$MODULE."/questions?element_type=group&group_id=" . $PROCESSED["group_id"]; ?>"><i class="add-icon fa fa-plus-circle"></i> <?php echo $SECTION_TEXT["text_attach_questions"]; ?></a>
                    <button class="btn btn-success dropdown-toggle" data-toggle="dropdown">
                        <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a href="<?php echo ENTRADA_URL."/admin/".$MODULE."/questions?section=add-question&element_type=group&group_id=" . $PROCESSED["group_id"]; ?>"><?php echo $SECTION_TEXT["text_add_and_attach"]; ?></a></li>
                    </ul>
                </div>
            </div><br /><br /><br />
            <?php
                $view = new Views_Exam_Group($group);
                echo $view->render(false, NULL, NULL, NULL, "questions");
            ?>
            <br />
        <?php } ?>
    </form>
    <div id="delete-group-question-modal" class="modal hide fade">
        <form id="delete-group-question-form-modal" class="form-horizontal" action="<?php echo ENTRADA_URL."/admin/".$MODULE."/".$SUBMODULE."?section=api-group"; ?>" method="POST" style="margin:0px;">
            <input id="agquestion_id" type="hidden" value="" />
            <div class="modal-header"><h1><?php echo $SECTION_TEXT["title_remove_from_group"]; ?></h1></div>
            <div class="modal-body">
                <p><?php echo $SECTION_TEXT["text_remove_from_group"]; ?></p>
                <div id="group-question-selected"></div>
            </div>
            <div class="modal-footer">
                <div class="row-fluid">
                    <a href="#" id="delete-group-cancel" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $default_buttons["btn_cancel"]; ?></a>
                    <input type="submit" id="delete-group-question-modal-delete" class="btn btn-primary" value="<?php echo $default_buttons["btn_remove"]; ?>" />
                </div>
            </div>
        </form>
    </div>
<?php

}