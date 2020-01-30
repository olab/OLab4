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
 * The file that allows users to add and edit learning objects.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Travis Obregon <travismobregon@gmail.com>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 *
 */

if (!defined("PARENT_INCLUDED") || (!defined("ADD_LOR") && !defined("EDIT_LOR"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: " . ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("lor", "update", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] . "] and role [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"] . "] does not have access to this module [" . $MODULE . "]");
} else {
    switch ($STEP) {
        case 2 :
            if (isset($_POST["learning_object_title"]) && $tmp_input = clean_input($_POST["learning_object_title"], array("trim", "striptags"))) {
                $PROCESSED["title"] = $tmp_input;
            } else {
                add_error("Please provide a " . $translate->_("Learning Object") . " title.");
            }

            if (isset($_POST["learning_object_description"]) && $tmp_input = clean_input($_POST["learning_object_description"], array("trim", "striptags"))) {
                $PROCESSED["description"] = $tmp_input;
            }

            if (isset($_POST["selected_internal_authors"]) && $_POST["selected_internal_authors"]) {
                $PROCESSED["authors"] = array();
                foreach ($_POST["selected_internal_authors"] as $author) {
                    $author = explode("_", $author);
                    $PROCESSED["authors"][] = array("author_type" => $author[0], "author_id" => $author[1]);
                }
            } else {
                add_error($translate->_("Please select at least one author from the list."));
            }

            if (isset($_POST["learning_object_primary_usage"]) && $tmp_input = clean_input($_POST["learning_object_primary_usage"], array("trim", "striptags"))) {
                $PROCESSED["primary_usage"] = $tmp_input;
            }

            if (isset($_POST["learning_object_tool"]) && $tmp_input = clean_input($_POST["learning_object_tool"], array("trim", "striptags"))) {
                $PROCESSED["tool"] = $tmp_input;
            }

            if (isset($_POST["learning_object_url"]) && $tmp_input = clean_input($_POST["learning_object_url"], array("trim", "striptags"))) {
                $PROCESSED["url"] = $tmp_input;
            } else {
                $PROCESSED["url"] = "";
            }

            if (isset($_POST["object_type"]) && $tmp_input = clean_input($_POST["object_type"], array("trim", "striptags"))) {
                $PROCESSED["object_type"] = $tmp_input;
            } else {
                add_error("Please provide a " . $translate->_("Learning Object Type."));
            }

            $viewable_dates = Entrada_Utilities::validate_calendars("viewable", false, false);
            if ((isset($viewable_dates["start"])) && ((int)$viewable_dates["start"])) {
                $PROCESSED["viewable_start"] = (int)$viewable_dates["start"];
            } else {
                $PROCESSED["viewable_start"] = 0;
            }

            if ((isset($viewable_dates["finish"])) && ((int)$viewable_dates["finish"])) {
                $PROCESSED["viewable_end"] = (int)$viewable_dates["finish"];
            } else {
                $PROCESSED["viewable_end"] = 0;
            }

            if (isset($_FILES["learning_object_module_filename"]["error"]) && $_FILES["learning_object_module_filename"]["error"] === UPLOAD_ERR_OK && $tmp_input = clean_input($_FILES["learning_object_module_filename"]["name"], array("trim", "striptags", "file"))) {
                $image_file_type = pathinfo(basename($_FILES["learning_object_module_filename"]["name"]), PATHINFO_EXTENSION);

                if ($image_file_type == "zip") {
                    $PROCESSED["filename_hashed"] = Entrada_Utilities_Files::getFileHash($_FILES["learning_object_module_filename"]["tmp_name"]);
                    $PROCESSED["filename"] = $_FILES["learning_object_module_filename"]["name"];
                } else {
                    add_error("Invalid file format for " . $translate->_("Learning Module") . " file: " . $image_file_type);
                }
            }

            if (isset($_FILES["learning_object_screenshot_filename"]["error"]) && $_FILES["learning_object_screenshot_filename"]["error"] === UPLOAD_ERR_OK && $tmp_input = clean_input($_FILES["learning_object_screenshot_filename"]["name"], array("trim", "striptags", "file"))) {
                $image_file_type = pathinfo(basename($_FILES["learning_object_screenshot_filename"]["name"]), PATHINFO_EXTENSION);

                // Check if image file is an actual image or fake image & only allow certain file formats
                $check = getimagesize($_FILES["learning_object_screenshot_filename"]["tmp_name"]);
                if ($check !== false && ($image_file_type == "jpg" || $image_file_type == "jpeg" || $image_file_type == "png" || $image_file_type == "gif")) {
                    $PROCESSED["screenshot_filename"] = Entrada_Utilities_Files::getFileHash($_FILES["learning_object_screenshot_filename"]["tmp_name"]) . "." . $image_file_type;
                } else {
                    add_error("Invalid file format for " . $translate->_("Learning Object") . " screenshot.");
                }
            } else {
                if (isset($_POST["learning_object_screenshot_hidden"]) && $tmp_input = clean_input($_POST["learning_object_screenshot_hidden"], array("trim", "striptags"))) {
                    if (strpos($_POST["learning_object_screenshot_hidden"], "image/jpeg")) {
                        $image_file_type = "jpg";
                    }
                    if (strpos($_POST["learning_object_screenshot_hidden"], "image/jpg")) {
                        $image_file_type = "jpg";
                    }
                    if (strpos($_POST["learning_object_screenshot_hidden"], "image/png")) {
                        $image_file_type = "png";
                    }
                    if (strpos($_POST["learning_object_screenshot_hidden"], "image/gif")) {
                        $image_file_type = "gif";
                    }
                    if ($image_file_type == "jpg" || $image_file_type == "jpeg" || $image_file_type == "png" || $image_file_type == "gif") {
                        $PROCESSED["screenshot_filename"] = sha1_file(STORAGE_LOR . "/" . $ENTRADA_USER->getActiveID() . "/" . $_SESSION["file-id"]) . "." . $image_file_type;
                    } else {
                        add_error("Invalid file format for " . $translate->_("Learning Object") . " screenshot.");
                    }
                } elseif (defined("ADD_LOR") && ADD_LOR === true) {
                    add_error("Please provide a " . $translate->_("Learning Object") . " screenshot.");
                }
            }


            if (!$ERROR) {
                if ($METHOD == "insert") {
                    $PROCESSED["created_date"] = time();
                    $PROCESSED["created_by"] = $ENTRADA_USER->getActiveID();
                }

                $PROCESSED["updated_date"] = time();
                $PROCESSED["updated_by"] = $ENTRADA_USER->getActiveID();
                $PREFERENCES = preferences_load($MODULE);

                $learning_object = new Models_LearningObject($PROCESSED);
                if ($learning_object->{$METHOD}()) {
                    if (isset($_FILES["learning_object_screenshot_filename"]["tmp_name"]) && $_FILES["learning_object_screenshot_filename"]["tmp_name"]) {
                        $destination = STORAGE_LOR . "/" . Entrada_Utilities_Files::getPathFromFilename($PROCESSED["screenshot_filename"]) . $PROCESSED["screenshot_filename"];

                        $stream = fopen($_FILES["learning_object_screenshot_filename"]["tmp_name"], "r+");
                        $filesystem->putStream($destination, $stream);
                        fclose($stream);
                    }

                    /**
                     * Learning modules handling
                     */
                    if (isset($_FILES["learning_object_module_filename"]["tmp_name"]) && $_FILES["learning_object_module_filename"]["tmp_name"]) {
                        $model_lm_upload = new Entrada_LearningObject_Upload(array(
                            "file" => $_FILES["learning_object_module_filename"],
                            "directory" => STORAGE_LOR
                        ));

                        $model_lm_upload->process();
                    }

                    if ($METHOD == "update") {
                        Models_LearningObject_Author::deleteAllByLearningResourceID($learning_object->getID());
                    }

                    foreach ($PROCESSED["authors"] as $author) {
                        $author["learning_object_id"] = $learning_object->getID();
                        $a = new Models_LearningObject_Author($author);

                        if (!$a->insert()) {
                            application_log("error", "Failed to " . $METHOD . " authors for learning object " . $learning_object->getID());
                        }
                    }

                    $url = ENTRADA_URL . "/admin/" . $MODULE;
                    add_success("You have successfully added a new " . $translate->_("Learning Object") . " to the system.<br /><br />You will now be redirected to the " . $translate->_("Learning Objects") . " index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"" . $url . "\" style=\"font-weight: bold\">click here</a> to continue.");

                    $ONLOAD[] = "setTimeout('window.location=\\'" . $url . "\\'', 5000)";
                } else {
                    add_error("An error occurred while attempting to " . $METHOD . " the " . $translate->_("Learning Object") . ".");
                    $STEP = 1;
                }
            } else {
                $STEP = 1;
            }
            break;
    }

    switch ($STEP) {
        case 2 :
            if ($SUCCESS) {
                echo display_success();
            }
            if ($ERROR) {
                echo display_error();
            }
            break;
        case 1 :
        default:
            if ($SUCCESS) {
                echo display_success();
            }
            if ($ERROR) {
                echo display_error();
            }
            ?>
            <script src="<?php echo ENTRADA_URL . "/javascript/" . $MODULE . ".js?release=" . APPLICATION_VERSION ?>"></script>

            <link rel="stylesheet" type="text/css"
                  href="<?php echo ENTRADA_RELATIVE . "/css/" . $MODULE . ".css?release=" . APPLICATION_VERSION; ?>"/>

            <script>
                var internal_author_label = "<?php echo $translate->_("Internal author"); ?>";
                var external_author_label = "<?php echo $translate->_("External author"); ?>";
            </script>

            <div id="msgs"></div>
            <form id="edit-learning-object-form"
                  action="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "?section=" . $SECTION . "&learning_object_id=" . $PROCESSED["learning_object_id"]; ?>"
                  class="form-horizontal" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="step" value="2"/>

                <h2 title="<?php echo $translate->_("Learning Object") . " Information"; ?>"><?php echo $translate->_("Learning Object Information"); ?></h2>

                <div id="<?php echo "learning-object-information"; ?>">
                    <div class="control-group">
                        <label class="control-label form-required"
                               for="learning-object-title"><?php echo $translate->_("Learning Object Title"); ?></label>

                        <div class="controls">
                            <input type="text" name="learning_object_title" id="learning-object-title" class="span11"
                                   value="<?php echo $PROCESSED["title"]; ?>"/>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label"
                               for="learning-object-description"><?php echo $translate->_("Learning Object Description"); ?></label>

                        <div class="controls">
                            <textarea class="span11 expandable" name="learning_object_description"
                                      id="learning-object-description"><?php echo $PROCESSED["description"]; ?></textarea>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label form-required"
                               for="learning-object-authors-search"><?php echo $translate->_("Select Author(s)"); ?></label>

                        <div id="autocomplete-container" class="controls">
                            <input type="text" name="learning_object_authors_search" id="learning-object-authors-search"
                                   class="span11 search"
                                   placeholder="<?php echo $translate->_("Type to search for authors..."); ?>"/>
                            <div id="autocomplete">
                                <div id="autocomplete-list-container"></div>
                            </div>
                        </div>
                    </div>
                    <div id="author-lists">
                        <div id="author-list-internal" class="<?php echo $PROCESSED["authors"] ? "" : "hide"; ?>">
                            <h3 id="selected-authors-list-heading"><?php echo $translate->_("Authors"); ?></h3>
                            <div id="internal-authors-list-container">
                                <?php
                                if ($PROCESSED["authors"]) { ?>
                                    <ul id="internal-authors-list" class="internal-authors-list menu">
                                        <?php
                                        $selected_internal_authors = "";
                                        foreach ($PROCESSED["authors"] as $author) { ?>
                                            <li class="community internal-author-list-item internal-author-<?php echo $author["author_id"]; ?>"
                                                data-id="<?php echo $author["author_id"]; ?>">
                                                <?php
                                                echo Models_LearningObject_Author::getAuthorName($author["author_id"], ucfirst($author["author_type"]));
                                                ?>
                                                <span class="pull-right selected-author-container">
                                                    <span class="selected-author-label"><?php echo ucfirst($author["author_type"]); ?>
                                                        author</span><span class="remove-selected-author">&times;</span>
                                                </span>
                                            </li>
                                            <?php
                                            $selected_internal_authors .= "<input id=\"selected-internal-author-" . $author["author_id"] . "\" name=\"selected_internal_authors[]\" type=\"hidden\" value=\"" . strtolower($author["author_type"]) . "_" . $author["author_id"] . "\" class=\"selected-internal-author-control\" />\n";
                                        }
                                        ?>
                                    </ul>
                                    <?php
                                    echo $selected_internal_authors;
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <div id="external-authors-controls" class="control-group hide">
                        <div class="form-inline">
                            <input id="author-firstname" name="author_firstname" class="form-control input-small"
                                   type="text" placeholder="<?php echo $translate->_("Firstname"); ?>"/>
                            <input id="author-lastname" name="author_lastname" class="form-control input-small"
                                   type="text" placeholder="<?php echo $translate->_("Lastname"); ?>"/>
                            <input id="author-email" name="author_email" class="form-control input-medium" type="text"
                                   placeholder="<?php echo $translate->_("Email Address"); ?>"/>
                            <a id="add-external-user-btn" href="#"
                               class="btn btn-mini btn-success"><?php echo $translate->_("Add Author"); ?></a>
                            <a id="cancel-author-btn" href="#" class="btn btn-mini">Cancel</a>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label"
                               for="learning-object-primary-usage"><?php echo $translate->_("Primary Usage"); ?></label>

                        <div class="controls">
                            <input type="text" name="learning_object_primary_usage" id="learning-object-primary-usage"
                                   class="span11" value="<?php echo $PROCESSED["primary_usage"]; ?>"/>
                        </div>
                    </div>

                    <?php echo Entrada_Utilities::generate_calendars("viewable", $translate->_("Viewable"), true, false, ((isset($PROCESSED["viewable_start"])) ? $PROCESSED["viewable_start"] : time()), true, false, ((isset($PROCESSED["viewable_end"])) ? $PROCESSED["viewable_end"] : 0), true, false, " starting", " until"); ?>

                    <div class="control-group">
                        <label class="control-label"
                               for="learning-object-tool"><?php echo $translate->_("Tool"); ?></label>

                        <div class="controls">
                            <input type="text" name="learning_object_tool" id="learning-object-tool" class="span11"
                                   value="<?php echo $PROCESSED["tool"]; ?>"/>
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label form-required"
                               for="learning-object-url"><?php echo $translate->_("Object Type"); ?></label>

                        <div class="controls">
                            <select id="object_type_select" name="object_type">
                                <option value="link"<?php echo ($PROCESSED["object_type"] == "link") ? " SELECTED" : ""; ?>>
                                    Link/URL
                                </option>
                                <option value="tincan"<?php echo ($PROCESSED["object_type"] == "tincan") ? " SELECTED" : ""; ?>>
                                    TinCan Learning Module
                                </option>
                                <option value="scorm"<?php echo ($PROCESSED["object_type"] == "scorm") ? " SELECTED" : ""; ?>>
                                    Scorm 1.2/2004 Learning Module
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Link type handling -->
                    <div class="control-group hide" id="link-group">
                        <label class="control-label form-required"
                               for="learning-object-url"><?php echo $translate->_("URL"); ?></label>

                        <div class="controls">
                            <input type="url" name="learning_object_url" id="learning-object-url" class="span11"
                                   value="<?php echo $PROCESSED["url"]; ?>"/>
                        </div>
                    </div>
                    <!-- Tincan learning modules handling -->
                    <div class="control-group hide" id="tincan-group">
                        <label class="control-label form-required"
                               for="learning-object-url"><?php echo $translate->_("Tincan Module"); ?></label>

                        <div class="controls space-below">
                            <input type="file" accept="application/zip" name="learning_object_tincan_filename"
                                   id="learning-object-tincan-filename" class="hide"/>
                            <a href="#modal-lm-wrapper" id="upload-tincan-modal-btn" data-toggle="modal"
                               class="btn btn-success"><i class="fa fa-upload space-right"
                                                          aria-hidden="true"></i><?php echo $translate->_("Upload Learning Module"); ?>
                            </a>
                            <div id="learning-object-tincan-title"><?php echo ($PROCESSED["object_type"] == "tincan" && $PROCESSED["filename"]) ? $PROCESSED["filename"] : "<i>No Files Uploaded</i>"; ?></div>
                        </div>
                    </div>
                    <!-- Scorm 1.2 or 2004 modules handling -->
                    <div class="control-group hide" id="scorm-group">
                        <label class="control-label form-required"
                               for="learning-object-url"><?php echo $translate->_("Scorm Module"); ?></label>

                        <div class="controls space-below">
                            <input type="file" accept="application/zip" name="learning_object_scorm_filename"
                                   id="learning-object-scorm-filename" class="hide"/>
                            <a href="#modal-lm-wrapper" id="upload-scorm-modal-btn" data-toggle="modal"
                               class="btn btn-success"><i class="fa fa-upload space-right"
                                                          aria-hidden="true"></i><?php echo $translate->_("Upload Learning Module"); ?>
                            </a>
                            <div id="learning-object-scorm-title"><?php echo ($PROCESSED["object_type"] == "scorm" && $PROCESSED["filename"]) ? $PROCESSED["filename"] : "<i>No Files Uploaded</i>"; ?></div>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label <?php echo(defined("ADD_LOR") && ADD_LOR === true ? "form-required" : ""); ?>"
                               for="learning-object-screenshot-filename"><?php echo $translate->_("Screenshot"); ?></label>
                        <div class="controls space-below">
                            <input type="file" accept="image/*" name="learning_object_screenshot_filename"
                                   id="learning-object-screenshot-filename" class="hide"/>
                            <a href="#modal-wrapper" id="upload-image-modal-btn" data-toggle="modal"
                               class="btn btn-success"><i class="fa fa-upload space-right"
                                                          aria-hidden="true"></i><?php echo $translate->_("Upload Photo"); ?>
                            </a>
                        </div>
                        <label class="control-label">Uploaded Image:</label>
                        <div class="controls space-above">
                            <span class="selected-screenshot-data span11">
                                <img id="screenshot-img" class="lor-image" alt="No Screenshot Uploaded"
                                     src="<?php echo(defined("EDIT_LOR") && EDIT_LOR === true ? "?section=api-lor&method=get-images&image=" . $PROCESSED["screenshot_filename"] . "\"" : ""); ?>">
                                <input type="hidden" name="learning_object_screenshot_hidden"
                                       id="learning-object-screenshot-hidden" value=""/>
                                <p class="span11" id="upload-image-label"></p>
                            </span>
                        </div>
                    </div>

                    <!-- Thumbnail modal upload -->
                    <div id="modal-wrapper" class="modal fade hide">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal"><span
                                        aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title"><?php echo $translate->_("Upload Image"); ?></h4>
                        </div>
                        <div class="modal-body screenshot-modal-body">
                            <div id="upload-container">
                                <div class="learning-object-upload-div">
                                    <img id="resource_file" src="../images/event-resource-file.png">
                                </div>
                                <p class="learning-object-upload-text"
                                   style="margin-top: 35px;"><?php echo $translate->_("You can drag and drop files into this window to upload."); ?></p>
                                <div class="learning-object-upload-input-div">
                                    <label class="btn btn-success span3 pull-left">Browse
                                        <input type="file" accept="image/*" id="learning-object-screenshot-filename"
                                               multiple name="learning_object_screenshot_filename" class="hide">
                                    </label>
                                    <span class="span6 learning-object-upload-span"><?php echo $translate->_("No File Selected"); ?></span>
                                </div>
                            </div>
                            <div id="learning_object_drop_overlay" class="hide">
                                <div id="learning_object_drop_box"></div>
                                <p id="learing-object-loading-msg"
                                   name="learning_object_loading_msg"><?php echo $translate->_("Drop the selected file anywhere to upload."); ?></p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button id="screenshot-close" data-dismiss="modal" class="btn btn-primary pull-right">Done
                            </button>
                        </div>
                    </div>
                    <!-- End thumbnail modal upload -->

                    <!-- Learning module modal upload -->
                    <div id="modal-lm-wrapper" class="modal fade hide">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal"><span
                                        aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title"><?php echo $translate->_("Upload Learning Module"); ?></h4>
                        </div>
                        <div class="modal-body lm-modal-body">
                            <div id="lm-upload-container">
                                <div class="learning-object-upload-div">
                                    <img id="resource_file" src="../images/event-resource-file.png">
                                </div>
                                <p class="learning-object-upload-text"
                                   style="margin-top: 35px;"><?php echo $translate->_("You can drag and drop files into this window to upload."); ?></p>
                                <div class="learning-object-upload-input-div">
                                    <label class="btn btn-success span3 pull-left"><?php echo $translate->_("Browse"); ?>
                                        <input type="file" accept="application/zip" id="learning-object-module-filename"
                                               multiple name="learning_object_module_filename" class="hide">
                                    </label>
                                    <span class="span6 learning-module-upload-span"><?php echo $translate->_("No File Selected"); ?></span>
                                </div>
                            </div>
                            <div id="lm_learning_object_drop_overlay" class="hide">
                                <div id="learning_object_drop_box"></div>
                                <p id="learing-object-loading-msg"
                                   name="learning_object_loading_msg"><?php echo $translate->_("Drop the selected file anywhere to upload."); ?></p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button id="module-upload-close" data-dismiss="modal"
                                    class="btn btn-primary pull-right"><?php echo $translate->_("Done"); ?></button>
                        </div>
                    </div>
                    <!-- End Learning module modal upload -->

                    <div class="row-fluid span11">
                        <div class="pull-right">
                            <a href="<?php echo ENTRADA_URL . "/admin/" . $MODULE; ?>"
                               class="btn btn-default space-right"><?php echo "Cancel"; ?></a>
                            <input type="submit" class="btn btn-primary" value="<?php echo $translate->_("Save"); ?>"/>
                        </div>
                    </div>
                </div>
            </form>
            <?php
            break;
    }
}