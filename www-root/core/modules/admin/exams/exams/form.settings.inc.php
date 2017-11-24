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
 * The exam that allows users to add and edit exambank exams.
 *
 * @author Organisation: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Daniel Noji <dnoji@mednet.ucla.edu>
 * @copyright Copyright 2015 UC Regents. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("ADD_EXAM") && !defined("EDIT_EXAM"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("exam", "create", false)) {
	add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    load_rte('examadvanced', array('autogrow' => true, 'divarea' => true));
    switch ($STEP) {
        case 2 :

            if (isset($_POST["exam_title"]) && $tmp_input = clean_input($_POST["exam_title"], array("trim", "striptags"))) {
                $PROCESSED["title"] = $tmp_input;
            } else {
                add_error($SUBMODULE_TEXT["exam"]["text_error_title"]);
            }

            if (isset($_POST["exam_description"]) && $tmp_input = clean_input($_POST["exam_description"], array("trim", "striptags"))) {
                $PROCESSED["description"] = $tmp_input;
            }

            if (isset($_POST["display_questions"])) {
                $tmp_input = clean_input($_POST["display_questions"], array("trim", "striptags"));
                $PROCESSED["display_questions"] = $tmp_input;
            }

            if (isset($_POST["random"])) {
                $tmp_input = clean_input($_POST["random"], array("trim", "striptags"));
                $PROCESSED["random"] = $tmp_input;
            }

            if (!has_error()) {
                $PROCESSED["updated_date"] = time();
                $PROCESSED["updated_by"] = $ENTRADA_USER->getActiveID();
                $exam = new Models_Exam_Exam($PROCESSED);
                
                if ($exam->{$METHOD}()) {
                    switch ($METHOD) {
                        case "insert":
                            $author = array(
                                "exam_id"       => $exam->getID(),
                                "author_type"   => "proxy_id",
                                "author_id"     => $ENTRADA_USER->getID(),
                                "updated_date"  => time(),
                                "updated_by"    => $ENTRADA_USER->getID(),
                                "created_date"  => time(),
                                "created_by"    => $ENTRADA_USER->getID()
                            );
                            $a = new Models_Exam_Exam_Author($author);
                            $a->insert();

                            break;
                        case "update":

                            break;
                        default:
                            break;
                    }

                    Entrada_Utilities_Flashmessenger::addMessage($translate->_("Successfully updated the exam."), "success", $MODULE);

                    $url = ENTRADA_URL."/admin/".$MODULE."/".$SUBMODULE."?section=exam-settings&id=".$exam->getID();
                    header("Location: ".$url);
                } else {
                    add_error($translate->_("An error occurred while attempting to update the exam."));
                    $STEP = 1;
                }
            } else {
                $STEP = 1;
            }
            
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
            $random_checked = 0;
            $random_disabled = 0;
            $display_checked = "all";
            if ($exam) {
                $exam_elements  = $exam->getExamElements();
                $random         = $exam->getRandom();
                $display        = $exam->getDisplayQuestions();

                if (isset($random)) {
                    $random_checked = $random;
                }

                if (isset($display)) {
                    $display_checked = $display;
                    if ($display_checked == "page_breaks") {
                        $random_checked  = 0;
                        $random_disabled = 1;
                    }
                }
            }
        default:
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

            if (has_success()) {
                echo display_success();
            }
            if (has_error()) {
                echo display_error();
                echo display_success();
            }
            $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = \"". ENTRADA_URL ."\";</script>";
            $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/jquery/jquery.dataTables.min-1.10.1.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
            $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/jquery/dataTables.colVis.min.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
            $HEAD[] = "<link rel='stylesheet' type='text/css' href='". ENTRADA_RELATIVE . "/css/jquery/jquery.dataTables.css?release=".html_encode(APPLICATION_VERSION)."'>";
            $HEAD[] = "<link rel='stylesheet' type='text/css' href='". ENTRADA_RELATIVE . "/css/jquery/dataTables.colVis.css?release=".html_encode(APPLICATION_VERSION)."'>";
            $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.audienceselector.js?release=". html_encode(APPLICATION_VERSION) ."\"></script>";
            $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.audienceselector.css?release=". html_encode(APPLICATION_VERSION) ."\" />";
            $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.audienceselector.js?release=". html_encode(APPLICATION_VERSION) ."\"></script>";
            $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/" . $MODULE . "/" . $SUBMODULE . "/" . $MODULE . "-settings-admin.js?release=". html_encode(APPLICATION_VERSION) ."\"></script>";
            $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/" . $MODULE . "/questions/questions.js?release=". html_encode(APPLICATION_VERSION) ."\"></script>";
            ?>
            <link rel="stylesheet" type="text/css" href="<?php echo ENTRADA_URL . "/css/" . $MODULE . "/" . $MODULE . ".css"; ?>" />
            <link rel="stylesheet" type="text/css" href="<?php echo ENTRADA_URL . "/css/" . $MODULE . "/groups.css"; ?>" />
            <link rel="stylesheet" type="text/css" href="<?php echo ENTRADA_URL . "/css/" . $MODULE . "/questions.css"; ?>" />
            <script type="text/javascript">
                var ENTRADA_URL = "<?php echo ENTRADA_URL; ?>";
                var API_URL = "<?php echo ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-exams"; ?>";
                var submodule_text      = JSON.parse('<?php echo json_encode($SUBMODULE_TEXT); ?>');
                var default_text_labels = JSON.parse('<?php echo json_encode($DEFAULT_TEXT_LABELS); ?>');
            </script>
            <form id="exam-elements" action="<?php echo ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?section=" . $SECTION . "&id=" . $PROCESSED["exam_id"]; ?>" data-exam-id="<?php echo $PROCESSED["exam_id"]; ?>" class="form-horizontal" method="POST">
                <input type="hidden" name="step" value="2" />
                <div id="msgs"></div>
                <h2 title="<?php echo $SUBMODULE_TEXT["exam"]["title_exam_info"]; ?>"><?php echo $SUBMODULE_TEXT["exam"]["title_exam_info"]; ?></h2>
                <div id="<?php echo str_replace(" ", "-", strtolower($SUBMODULE_TEXT["exam"]["title_exam_info"])); ?>">
                    <div class="control-group">
                        <label class="control-label form-required" for="exam-title"><?php echo $SUBMODULE_TEXT["exam"]["label_exam_title"]; ?></label>
                        <div class="controls">
                            <input type="text" name="exam_title" id="exam-title" class="span11" value="<?php echo $PROCESSED["title"]; ?>"/>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="exam-description"><?php echo $SUBMODULE_TEXT["exam"]["label_exam_description"]; ?></label>
                        <div class="controls">
                            <textarea class="span11 expandable" name="exam_description" id="form-description"><?php echo $PROCESSED["description"]; ?></textarea>
                        </div>
                    </div>
                    <?php

                    ?>
                    <script type="text/javascript">
                        jQuery(function($) {
                            $("#contact-selector").audienceSelector({
                                "filter"        : "#contact-type",
                                "target"        : ".author-list",
                                "content_type"  : "exam-author",
                                "content_style" : "exam",
                                "delete_icon"   : "fa fa-2x fa-times-circle",
                                "content_target" : "<?php echo $PROCESSED["exam_id"]; ?>",
                                "api_url"       : "<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-exams" ; ?>",
                                "delete_attr"   : "data-author-id"
                            });
                        });
                    </script>

                    <div class="control-group exam-authors">
                        <label class="control-label" for="exam-permissions"><?php echo $SUBMODULE_TEXT["exam"]["label_exam_permissions"]; ?></label>
                        <div class="controls">
                            <input type="text" name="contact_select" id="contact-selector" />
                            <select name="contact_type" id="contact-type" class="span3">
                                <?php foreach ($DEFAULT_TEXT_LABELS["contact_types"] as $contact_type => $contact_type_name) { ?>
                                    <option value="<?php echo $contact_type; ?>"><?php echo $contact_type_name; ?></option>
                                <?php } ?>
                            </select>
                            <?php
                            $type_array     = array("organisation_id", "course_id", "proxy_id");
                            $exam_authors = Models_Exam_Exam_Author::fetchAllByExamIdGroupedByType($PROCESSED["exam_id"]);
                            // @todo once exam folders are added in replace this with that folder author query
                            $exam_folders = false;

                            foreach ($type_array as $type) {
                                if ($exam_authors && is_array($exam_authors)) {
                                    $exam_author_type = $exam_authors[$type];
                                } else {
                                    $exam_author_type = false;
                                }
                                echo $html = Views_Exam_Exam_Author::renderTypeUL($type, $exam_folders, $exam_author_type);
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <h2 title="<?php echo $SUBMODULE_TEXT["exam"]["settings"]["title_exam_settings"]; ?>"><?php echo $SUBMODULE_TEXT["exam"]["settings"]["title_exam_settings"]; ?></h2>
                <div id="<?php echo str_replace(" ", "-", strtolower($SUBMODULE_TEXT["exam"]["title_exam_info"])); ?>">
                    <div class="control-group">
                        <label class="control-label"><?php echo $SUBMODULE_TEXT["exam"]["settings"]["label_display"] ?></label>
                        <div class="controls">
                            <label class="radio" for="display_all">
                                <input type="radio" name="display_questions" id="display_all" value="all"<?php echo ($display_checked == "all") ? " checked" : "";?>>
                                <?php echo $SUBMODULE_TEXT["exam"]["settings"]["text_display_all"] ?>
                            </label><br />
                            <label class="radio" for="display_one">
                                <input type="radio" name="display_questions" id="display_one" value="one"<?php echo ($display_checked == "one") ? " checked" : "";?>>
                                <?php echo $SUBMODULE_TEXT["exam"]["settings"]["text_display_one"] ?>
                            </label><br />
                            <label class="radio" for="display_page_breaks">
                                <input type="radio" name="display_questions" id="display_page_breaks" value="page_breaks"<?php echo ($display_checked == "page_breaks") ? " checked" : "";?>>
                                <?php echo $SUBMODULE_TEXT["exam"]["settings"]["text_display_page_breaks"] ?>
                            </label>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label"><?php echo $SUBMODULE_TEXT["exam"]["settings"]["label_random"] ?></label>
                        <div class="controls">
                            <label class="radio">
                                <input type="radio" name="random" id="random_on" value="1" class="random" <?php echo ($random_checked == 1) ? " checked" : ""; echo ($random_disabled ? " disabled" : "")?>>
                                <?php echo $SUBMODULE_TEXT["exam"]["settings"]["text_random_on"] ?>
                            </label><br />
                            <label class="radio">
                                <input type="radio" name="random" id="random_off" value="0" class="random" <?php echo ($random_checked == 0) ? " checked" : "";?>>
                                <?php echo $SUBMODULE_TEXT["exam"]["settings"]["text_random_off"] ?>
                            </label>
                        </div>
                    </div>
                    <?php if (defined("EDIT_EXAM") && EDIT_EXAM === true) { ?>
                    <div class="row-fluid">
                        <input type="submit" class="btn btn-primary pull-right" value="<?php echo $DEFAULT_TEXT_LABELS["btn_save"]; ?>" />
                    </div>
                    <?php } ?>
                </div>
            </form>
            
            <?php
                $exam_points = Views_Exam_Exam::getExamPoints($exam);
            ?>

            <h2><?php echo $SUBMODULE_TEXT["exam"]["settings"]["text_exam_data"] ?></h2>
            <dl class="dl-horizontal" id="exam-data">
                <dt><?php echo $SUBMODULE_TEXT["exam"]["settings"]["text_questions"] ?></dt>
                <dd>
                    <?php echo $exam_points["question_count"]; ?>
                </dd>
                <dt><?php echo $SUBMODULE_TEXT["exam"]["settings"]["text_points"] ?></dt>
                <dd>
                    <?php echo $exam_points["point_count"]; ?>
                </dd>
                <?php if ($exam_points && is_array($exam_points) && !empty($exam_points["categories"])) {
                    ?>
                    <dt><?php echo $SUBMODULE_TEXT["exam"]["settings"]["text_curriculum_tags"] ?></dt>
                    <dd>
                        <?php
                        foreach ($exam_points["categories"] as $set => $tags) {
                            $category = Models_Objective::fetchRow($set);
                            if ($category && is_object($category)) {
                                echo "<h3>" .  $category->getName() . "</h3>";

                                echo "<table class=\"table table-bordered table-striped tag-report datatable\">";
                                if ($tags && is_array($tags) && !empty($tags)) {
                                    ksort($tags);
                                    echo "<thead>\n";
                                    echo "<tr>\n";
                                    echo "<th\n>";
                                    echo $SUBMODULE_TEXT["exam"]["settings"]["text_keyword"];
                                    echo "</th\n>";
                                    echo "<th\n>";
                                    echo $SUBMODULE_TEXT["exam"]["settings"]["text_count"];
                                    echo "</th\n>";
                                    echo "</tr>\n";
                                    echo "</thead>\n";
                                    echo "<tbody>\n";
                                    foreach ($tags as $tag => $count) {
                                        echo "<tr>\n";
                                        echo "<td>\n";
                                        echo $tag;
                                        echo "</td>\n";
                                        echo "<td>\n";
                                        echo $count;
                                        echo "</td>\n";
                                        echo "</tr>\n";
                                    }
                                }
                                echo "</tbody>\n";
                                echo "</table>";
                            }
                        }
                        ?>
                    </dd>
                <?php
                }
                ?>
            </dl>

            <a name="exam-files-section" id="exam-files-section"></a>
            <h1 class="space-above large">
                <?php echo $SUBMODULE_TEXT["exam"]["settings"]["text_exam_pdfs"]; ?>
            </h1>
            <div id="exam-files-delete-confirmation"></div>
            <div id="exam-files-container-loading" class="hide">
                <img src="<?php echo ENTRADA_URL ."/images/loading.gif" ?>" />
                <p id="exam_files_loading_msg">
                    <?php echo $SUBMODULE_TEXT["exam"]["settings"]["text_loading_exam_pdfs"]; ?>
                </p>
            </div>
            <div id="exam-files-container">
                <div class="row">
                    <a  href="#" id="exam-files-toggle" class="btn pull-right">
                        <?php echo $SUBMODULE_TEXT["exam"]["settings"]["text_add_pdf"]; ?>
                    </a>
                </div>
            </div>
            <div id="exam-files-resources-section">
                <div id="event-resources-container">
                    <div id="event-resource-timeframe-pre-container" class="resource-list">
                        <ul class="timeframe" id="exam_files">
                        </ul>
                    </div>
                </div>
            </div>

            <div id="delete-exam-files-modal" class="modal scrollable fade hide" style="max-height: 314px;">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title">
                                <?php echo $SUBMODULE_TEXT["exam"]["settings"]["text_delete_exam_pdfs"]; ?>
                            </h4>
                        </div>
                        <div class="modal-body">
                            <div id="delete-exam-files-msgs"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Close</button>
                            <button id="delete-exam-files" type="button" class="btn btn-danger">
                                <?php echo $SUBMODULE_TEXT["exam"]["settings"]["text_delete_PDF"]; ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div id="exam-files-view-modal" class="modal fade hide">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
                            <h4 id="exam-files-view-modal-heading" class="modal-title"></h4>
                        </div>
                        <div class="modal-body">
                            <div id="exam-files-view-msgs"></div>
                            <table id="resource-views-table" class="table table-striped table-bordered datatable">
                                <thead>
                                <tr>
                                    <th width="40%">Name</th>
                                    <th width="15%">Views</th>
                                    <th width="45%">Last Viewed</th>
                                </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default pull-right" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
            <style>

            </style>
            <div id="exam-files-modal" class="modal scrollable fade hide">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
                            <h4 id="exam_files_modal_title" class="modal-title">
                                <?php echo $SUBMODULE_TEXT["exam"]["settings"]["text_add_exam_pdf"]; ?>
                            </h4>
                        </div>
                        <div class="modal-body">
                            <form class="form-horizontal" action="<?php echo ENTRADA_URL . "/admin/events?section=api-resource-wizard" ?>" method="post" id="exam_files_form" enctype="multipart/form-data">
                                <input id="exam_id" type="hidden" name="event_id" value="<?php echo (is_object($exam) ? $exam->getID() : ""); ?>" />
                                <input id="file_id" type="hidden" name="file_id" value="" />
                                <input id="file_step" type="hidden" name="step" value="1" />
                                <input id="file_next_step" type="hidden" name="next_step" value="0" />
                                <input id="file_previous_step" type="hidden" name="previous_step" value="0" />
                                <input id="exam_files_attach_file" type="hidden" name="exam_files_attach_file" value="no" />
                                <input id="exam_files_file_title_value" type="hidden" name="exam_files_file_title_value" value="" />
                                <input id="upload" type="hidden" name="upload" value="upload" />
                                <input id="" type="hidden" name="method" value="add" />
                                <div id="exam-files-msgs"></div>
                                <div id="exam-files-step"></div>
                            </form>
                            <div id="exam_files_loading">
                                <img src="<?php echo ENTRADA_URL ."/images/loading.gif" ?>" />
                                <p id="exam_files_loading_msg"></p>
                            </div>
                            <div id="exam_files_drop_overlay" class="hide">
                                <div id="exam_files_drop_box"></div>
                                <p id="exam_files_loading_msg">
                                    <?php echo $SUBMODULE_TEXT["exam"]["settings"]["text_upload_pdf"]; ?>
                                </p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Close</button>
                            <button id="exam-files-previous" type="button" class="btn btn-default hide">Previous Step</button>
                            <button id="exam-files-next" type="button" class="btn btn-primary hide">Next Step</button>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        break;
    }
}