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
 * The form that allows users to add and edit question bank folders.
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED")) || (!defined("ADD_FOLDER") && !defined("EDIT_FOLDER"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("examfolder", "read", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

    if (isset($_GET["id"]) && $tmp_input = clean_input($_GET["id"], "int")) {
        $PROCESSED["folder_id"] = $tmp_input;
    }

    if (isset($_GET["parent_folder_id"]) && $tmp_input = clean_input($_GET["parent_folder_id"], "int")) {
        $PROCESSED["parent_folder_id"] = $tmp_input;
    } else if (!isset($PROCESSED["parent_folder_id"])) {
        $PROCESSED["parent_folder_id"] = 0;
    }
    $PROCESSED["parent_folder_id"] = (int)$PROCESSED["parent_folder_id"];

    $SECTION_TEXT = $SUBMODULE_TEXT[$SECTION];
    switch ($STEP) {
        case 2 :

            if (isset($_POST["parent_folder_id"]) && $tmp_input = clean_input($_POST["parent_folder_id"], array("trim", "numeric"))) {
                $PROCESSED["parent_folder_id"] = (int) $tmp_input;
            } elseif (isset($_POST["parent_folder_id"]) && $_POST["parent_folder_id"] != "") {
                $PROCESSED["parent_folder_id"] = 0;
            } else {
                add_error($translate->_("You must provide a <strong>Parent Folder</strong> for this folder."));
            }

            if (isset($_POST["folder_title"]) && $tmp_input = clean_input($_POST["folder_title"], array("trim", "striptags"))) {
                $PROCESSED["folder_title"] = $tmp_input;
            } else {
                $PROCESSED["folder_title"] = "";
                add_error($translate->_("You must provide a <strong>Title</strong> for this folder."));
            }

            if (isset($_POST["folder_description"]) && $tmp_input = clean_input($_POST["folder_description"], array("trim", "striptags"))) {
                $PROCESSED["folder_description"] = $tmp_input;
            } else {
                $PROCESSED["folder_description"] = "";
            }

            if (isset($_POST["image_id"]) && $tmp_input = clean_input($_POST["image_id"], array("trim", "numeric"))) {
                $PROCESSED["image_id"] = $tmp_input;
            } else {
                $PROCESSED["image_id"] = "";
                add_error($translate->_("You must provide a <strong>Color</strong> for this folder."));
            }

            if (!has_error()) {
                $PROCESSED["organisation_id"] = $ENTRADA_USER->getActiveOrganisation();
                $PROCESSED["created_date"] = time();
                $PROCESSED["updated_date"] = time();
                $PROCESSED["created_by"] = $ENTRADA_USER->getActiveID();
                $PROCESSED["updated_by"] = $ENTRADA_USER->getActiveID();

                $parent_folder = Models_Exam_Question_Bank_Folders::fetchRowByID($PROCESSED["parent_folder_id"]);
                if (isset($parent_folder) && is_object($parent_folder)) {
                    if ($METHOD == "insert") {
                        //only change order on new folders
                        $PROCESSED["folder_order"] = $parent_folder->getNextFolderOrder();
                    }
                } else {
                    //need to create object for parent folder of 0 = index
                    $parent_folder = new Models_Exam_Question_Bank_Folders(
                        array(
                            "folder_id" => 0
                        )
                    );
                    if ($METHOD == "insert") {
                        //only change order on new folders
                        $PROCESSED["folder_order"] = $parent_folder->getNextFolderOrder();
                    }
                }
                $folder = new Models_Exam_Question_Bank_Folders($PROCESSED);

                if ($folder->{$METHOD}()) {
                    if ($METHOD == "update") {
                        $url = ENTRADA_URL."/admin/".$MODULE."/".$SUBMODULE;
                        $success_msg = sprintf($translate->_("The folder has been successfully updated. You will be redirected to the question bank index, please <a href=\"%s\">click here</a> if you do not wish to wait."), $url);
                    } else {
                        $folder_author = new Models_Exam_Question_Bank_Folder_Authors(
                            array(
                                "folder_id"     => $folder->getID(),
                                "author_type"   => "proxy_id",
                                "author_id"     => $ENTRADA_USER->getActiveID(),
                                "created_date"  => time(),
                                "created_by"    => $ENTRADA_USER->getID()
                            )
                        );

                        if (!$folder_author->insert()) {
                            add_error($translate->_("An error occurred while attempting to save the folder author, database said: " . $db->ErrorMsg()));
                        }

                        $folder_org = new Models_Exam_Question_Bank_Folder_Organisations(
                            array(
                                "folder_id"         => $folder->getID(),
                                "organisation_id"   => $ENTRADA_USER->getActiveOrganisation(),
                                "updated_date"      => time(),
                                "updated_by"        => 1
                            )
                        );

                        if (!$folder_org->insert()) {
                            add_error($translate->_("An error occurred while attempting to save the folder organisation, database said: " . $db->ErrorMsg()));
                        }

                        $url = ENTRADA_URL."/admin/".$MODULE."/".$SUBMODULE;
                        $success_msg = sprintf($translate->_("The folder has been successfully added. You will be redirected to the question bank index, please <a href=\"%s\">click here</a> if you do not wish to wait."), $url);
                    }

                    if ($METHOD == "insert") {
                        add_success($success_msg);
                    } else {
                        add_success($success_msg);
                    }

                    $ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";
                } else {
                    add_error($translate->_("An error occurred while attempting to insert a folder, database said: " . $db->ErrorMsg()));

                    $STEP = 1;
                }
            } else {
                $STEP = 1;
            }

            break;
        case 1 :

            break;
    }

    switch ($STEP) {
        case 2 :
            if (has_success()) {
                echo display_success();
            }
            if (has_error()) {
                echo display_error();
            }
            break;
        case 1 :
        default:
            if (has_success()) {
                echo display_success();
            }
            if (has_notice()) {
                echo display_notice();
            }
            if (has_error()) {
                echo display_error();
            }
            //get colors
            $images = Models_Exam_Lu_Question_Bank_Folder_Images::fetchAllRecords();
            $initial_folders = Models_Exam_Question_Bank_Folders::fetchAllByParentID($PROCESSED["parent_folder_id"]);
            /**
             * Load the rich text editor.
             */
            load_rte('examadvanced', array('autogrow' => true));

            $HEAD[]	= "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/objectives.js?release=". html_encode(APPLICATION_VERSION) ."\"></script>";
            $HEAD[] = "<script type=\"text/javascript\">var API_URL = \"". ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-folders" ."\";</script>";
            $HEAD[] = "<script type=\"text/javascript\">var FOLDER_API_URL = \"". ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-folders" ."\";</script>";
            $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.audienceselector.js?release=". html_encode(APPLICATION_VERSION) ."\"></script>";
            $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.audienceselector.css?release=". html_encode(APPLICATION_VERSION) ."\" />";
            ?>
            <link rel="stylesheet" type="text/css" href="<?php echo ENTRADA_URL . "/css/" . $MODULE . "/" . $MODULE . ".css"; ?>" />
            <script type="text/javascript">
                var ENTRADA_URL = "<?php echo ENTRADA_URL; ?>";
                var submodule_text      = JSON.parse('<?php echo json_encode($SUBMODULE_TEXT); ?>');
                var default_text_labels = JSON.parse('<?php echo json_encode($DEFAULT_TEXT_LABELS); ?>');
                var ajax_in_progress = false;
            </script>
        <?php
            if ($PROCESSED["parent_folder_id"] === 0) {
                $root_folder = new Models_Exam_Question_Bank_Folders(
                    array(
                        "folder_id"     => 0,
                        "folder_title"  => "Index",
                        "image_id"      => 3
                    )
                );

                $initial_folder_view = new Views_Exam_Question_Bank_Folder($root_folder);
                if (isset($initial_folder_view) && is_object($initial_folder_view)) {
                    $title          = $initial_folder_view->renderFolderSelectorTitle();
                    $folder_view    = $initial_folder_view->renderSimpleView();
                }
            } else {
                $parent_folder = Models_Exam_Question_Bank_Folders::fetchRowByID($PROCESSED["parent_folder_id"]);
                if (isset($parent_folder) && is_object($parent_folder)) {
                    $parent_folder_view = new Views_Exam_Question_Bank_Folder($parent_folder);
                    $title              = $parent_folder_view->renderFolderSelectorTitle();
                    $folder_view        = $parent_folder_view->renderSimpleView();
                    $nav                = $parent_folder_view->renderFolderSelectorBackNavigation();
                }
            }

            if (isset($PROCESSED["image_id"])) {
                $current_folder_id = (int)$PROCESSED["image_id"];
            }

            if (isset($images) && is_array($images)) {
                $image_html = "";
                $count = 0;
                $first_div = 1;
                $last_div = 10;
                foreach ($images as $image) {
                    $count++;
                    if ($count === $first_div) {
                        $first_div = $first_div + 10;
                        $image_html .= "<div>";
                    }
                    if (is_object($image)) {
                        if ($current_folder_id === (int)$image->getID()) {
                            $active = 1;
                        } else {
                            $active = 0;
                        }
                        $image_view = new Views_Exam_Question_Bank_Folder_Image($image);
                        $image_html .= $image_view->render(1, $active, 1);
                    }

                    if ($count === $last_div) {
                        $last_div = $last_div + 10;
                        $image_html .= "</div>";
                    }
                }
            }
        ?>
            <script type="text/javascript" src="<?php echo ENTRADA_URL; ?>/javascript/<?php echo $MODULE; ?>/<?php echo $SUBMODULE; ?>/<?php echo $MODULE; ?>-<?php echo $SUBMODULE; ?>-admin.js"></script>
            <form id="question-exam-folder" action="<?php echo ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?step=2&section=" . $SECTION . ($METHOD == "update" ? "&id=" . $PROCESSED["folder_id"] : ""); ?>" class="form-horizontal" method="POST">
                <input type="hidden" name="folder_id" id="folder_id" value="<?php echo (isset($PROCESSED["folder_id"]) ? $PROCESSED["folder_id"] : ""); ?>" />
                <input type="hidden" name="parent_folder_id" id="parent_folder_id" value="<?php echo (isset($PROCESSED["parent_folder_id"]) ? $PROCESSED["parent_folder_id"] : ""); ?>" />
                <input type="hidden" name="image_id" id="image_id" value="<?php echo (isset($PROCESSED["image_id"]) ? $PROCESSED["image_id"] : ""); ?>" />
                <h2>Folder Information</h2>
                <div class="control-group">
                    <label class="control-label form-required" for="parent_folder_id"><?php echo $SUBMODULE_TEXT["folder"]["label_folder_parent_id"]; ?></label>
                    <div class="controls">
                        <div id="selected-parent-folder">
                            <?php echo $folder_view;?>
                            <a href="#parent-folder-modal" data-toggle="modal" class="btn btn-success" id="select_parent_folder_button">Select Parent Folder</a>
                        </div>
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label form-required" for="folder_title"><?php echo $SUBMODULE_TEXT["folder"]["label_folder_title"]; ?></label>
                    <div class="controls">
                        <input class="span11" type="text" name="folder_title" id="folder_title" value="<?php echo (isset($PROCESSED["folder_title"]) ? $PROCESSED["folder_title"] : ""); ?>"/>
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label form-nrequired" for="folder_description"><?php echo $SUBMODULE_TEXT["folder"]["label_folder_description"]; ?></label>
                    <div class="controls">
                        <input class="span11" type="text" name="folder_description" id="folder_description" value="<?php echo (isset($PROCESSED["folder_description"]) ? $PROCESSED["folder_description"] : ""); ?>"/>
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label form-required" for="image_id"><?php echo $SUBMODULE_TEXT["folder"]["label_folder_image_id"]; ?></label>
                    <div id="image-picker" class="controls">
                        <?php echo $image_html;?>
                    </div>
                </div>
                <?php if (defined("EDIT_FOLDER") && EDIT_FOLDER === true) { ?>
                    <script type="text/javascript">
                        jQuery(function($) {
                            $("#contact-selector").audienceSelector({
                                "filter"        : "#contact-type",
                                "target"        : ".author-list",
                                "content_type"  : "question-author",
                                "content_style" : "exam",
                                "delete_icon"   : "fa fa-2x fa-times-circle",
                                "content_target" : "<?php echo $PROCESSED["folder_id"]; ?>",
                                "api_url"       : "<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-folders" ; ?>",
                                "delete_attr"   : "data-author-id"
                            });
                        });
                    </script>
                    <div class="control-group exam-authors">
                        <label class="control-label form-nrequired" for="question-permissions"><?php echo $SUBMODULE_TEXT["folder"]["label_folder_author"]; ?></label>
                        <div class="controls">
                            <input class="span6" type="text" name="contact_select" id="contact-selector" />
                            <select class="span5" name="contact_type" id="contact-type" class="span3">
                                <?php foreach ($DEFAULT_TEXT_LABELS["contact_types"] as $contact_type => $contact_type_name) { ?>
                                    <option value="<?php echo $contact_type; ?>"><?php echo $contact_type_name; ?></option>
                                <?php } ?>
                            </select>
                            <?php

                            $type_array     = array("organisation_id", "course_id", "proxy_id");
                            $authors = Models_Exam_Question_Bank_Folder_Authors::fetchAllInheritedByFolderID($PROCESSED["folder_id"]);

                            foreach ($type_array as $type) {
                                echo $html = Views_Exam_Question_Bank_Folder_Author::renderTypeUL($type, $authors[$type]);
                            }
                            ?>
                        </div>
                    </div>
                <?php } ?>
                <div class="row-fluid space-above">
                    <a href="<?php echo $url; ?>" class="btn btn-default"><?php echo $DEFAULT_TEXT_LABELS["btn_back"]; ?></a>
                    <input type="submit" class="btn btn-primary pull-right" value="<?php echo $DEFAULT_TEXT_LABELS["btn_save"]; ?>" />
                </div>
            </form>
            <div id="parent-folder-modal" class="modal hide fade">
                <div class="modal-header"><h1></h1></div>
                <div class="modal-body">
                    <div id="qbf-selector">
                        <div id="qbf-title">
                            <span class="qbf-title"><?php echo $title;?></span>
                        </div>
                        <div id="qbf-nav">
                            <?php echo $nav;?>
                        </div>
                        <span id="qbf-folder-<?php echo $PROCESSED["parent_folder_id"];?>" class="qbf-folder active">
                            <table>
                                <?php
                                if (isset($initial_folders) && is_array($initial_folders) && !empty($initial_folders)) {
                                    if ($PROCESSED["parent_folder_id"] == 0) {
                                        echo $initial_folder_view->renderFolderSelectorRow();
                                    }

                                    foreach ($initial_folders as $folder) {
                                        if (is_object($folder)) {
                                            if ($folder->getID() == $PROCESSED["parent_folder_id"]) {
                                                $selected = true;
                                            } else {
                                                $selected = false;
                                            }
                                            $folder_view = new Views_Exam_Question_Bank_Folder($folder);
                                            echo $folder_view->renderFolderSelectorRow($selected);
                                        }
                                    }
                                } else {
                                    //no folder create yet so just show the index
                                    if ($PROCESSED["parent_folder_id"] == 0) {
                                        echo $initial_folder_view->renderFolderSelectorRow();
                                    }
                                }
                                ?>
                            </table>
                        </span>
                    </div>
                </div>
                <div class="modal-footer">
                    <div id="qpf-confirm">
                        <button class="btn btn-default pull-left" id="cancel-folder-move"><?php echo $DEFAULT_TEXT_LABELS["btn_cancel"]; ?></button>
                        <button class="btn btn-primary pull-right" id="confirm-folder-move" data-type="folder"><?php echo $DEFAULT_TEXT_LABELS["btn_move"]; ?></button>
                    </div>
                </div>
            </div>
        <?php
    }
}