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
 * The Groups index page.
 * 
 * @author Organisation: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Daniel Noji <dnoji@mednet.ucla.edu>
 * @copyright Copyright 2015 UC Regents. All Rights Reserved.
 *
 */
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_GROUPS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("examquestiongroupindex", "read", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    $SECTION_TEXT = $SUBMODULE_TEXT[$SECTION];

    $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = \"". ENTRADA_URL ."\";</script>";
    $HEAD[] = "<script type=\"text/javascript\">var API_URL = \"". ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-group" ."\";</script>";
    $HEAD[] = "<script type=\"text/javascript\">var VIEW_PREFERENCE = \"". (isset($PREFERENCES["groups"]["selected_view"]) ? $PREFERENCES["groups"]["selected_view"] : "list") ."\";</script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery.dataTables.min.js\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/exams/groups/groups-admin.js\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js\"></script>";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css\" />";


    if (isset($_SESSION[APPLICATION_IDENTIFIER]["exams"]["groups"]["selected_filters"]) && !empty($_SESSION[APPLICATION_IDENTIFIER]["exams"]["groups"]["selected_filters"])) {
        $sidebar_html = "";
        foreach ($_SESSION[APPLICATION_IDENTIFIER]["exams"]["groups"]["selected_filters"] as $key => $filter_type) {
            $sidebar_html .= "<span>". ucwords(str_replace("_", " ", $key)) . " Filters</span>";
            $sidebar_html .= "<ul class=\"menu none\">";
            foreach ($filter_type as $target_id => $target_label) {
                $sidebar_html .= "<li>";
                $sidebar_html .= "<a href=\"#\" class=\"remove-target-toggle\" data-id=\"". html_encode($target_id) ."\" data-filter=\"". html_encode($key) ."\">";
                $sidebar_html .= "<img src=\"". ENTRADA_URL ."/images/checkbox-on.gif\" class=\"remove-target-toggle\" data-id=\"". html_encode($target_id) ."\" data_filter=\"". html_encode($key) ."\" />";
                $sidebar_html .= "<span> ". html_encode($target_label) ."</span>";
                $sidebar_html .= "</a>";
                $sidebar_html .= "</li>";
            }
            $sidebar_html .= "</ul>";
        }
        $sidebar_html .= "<a href=\"#\" class=\"clear-filters\">Clear All Filters</a>";
        new_sidebar_item("Selected Grouped Item Filters", $sidebar_html, "exam-filters", "open");
    }

    if (isset($_GET["element_type"]) && $tmp_input = clean_input($_GET["element_type"], array("trim", "striptags"))) {
        $PROCESSED["element_type"] = $tmp_input;
    }

    if (isset($_GET["exam_id"]) && $tmp_input = clean_input($_GET["exam_id"], "int")) {
        $PROCESSED["exam_id"] = $tmp_input;
    }

    if (isset($_GET["group_id"]) && $tmp_input = clean_input($_GET["group_id"], "int")) {
        $PROCESSED["group_id"] = $tmp_input;
    } elseif (isset($_POST["group_id"]) && $tmp_input = clean_input($_POST["group_id"], "int")) {
        $PROCESSED["group_id"] = $tmp_input;
    }
    ?>
    <h1><?php echo $SECTION_TEXT['title']; ?></h1>
    <?php
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
    switch ($STEP) {
        case 2 :

            if (isset($_POST["element_type"]) && $tmp_input = clean_input($_POST["element_type"], array("trim", "striptags"))) {
                $PROCESSED["element_type"] = $tmp_input;
            }

            if (isset($_POST["exam_id"]) && $tmp_input = clean_input($_POST["exam_id"], "int")) {
                $PROCESSED["exam_id"] = $tmp_input;
            }

            if (isset($_POST["groups"]) && is_array($_POST["groups"])) {
                $PROCESSED["groups"] = array_filter($_POST["groups"], function ($id) {
                    return (int) $id;
                });
            }

            if ($PROCESSED["exam_id"] && $PROCESSED["groups"]) {
                $SUCCESS = 0;
                switch ($PROCESSED["element_type"]) {
                    case "exam" :
                        $url = ENTRADA_URL."/admin/".$MODULE."/exams?section=edit-exam&id=".$PROCESSED["exam_id"];
                        $exam = Models_Exam_Exam::fetchRowByID($PROCESSED["exam_id"]);
                        if ($exam) {
                            foreach ($PROCESSED["groups"] as $group_id) {
                                $exam_group = Models_Exam_Group::fetchRowByID($group_id);

                                if (isset($exam_group) && is_object($exam_group)) {
                                    $exam_group_questions = $exam_group->getGroupQuestions();

                                    if ($exam_group_questions) {
                                        foreach ($exam_group_questions as $group_question) {
                                            if ($exam->hasQuestion($group_question->getQuestionVersion())) {
                                                $ERROR++;
                                                add_error($SECTION_TEXT["already_attached"] . ": <strong>" . $exam_group->getGroupTitle() . "</strong>");
                                                break;
                                            }
                                        }

                                        if (!$ERROR) {
                                            foreach ($exam_group_questions as $group_question) {
                                                $exam_element_data = array(
                                                    "exam_id" => $PROCESSED["exam_id"],
                                                    "element_type" => "question",
                                                    "element_id" => $group_question->getQuestionVersion()->getVersionID(),
                                                    "group_id" => $exam_group->getID(),
                                                    "order" => Models_Exam_Exam_Element::fetchNextOrder($PROCESSED["exam_id"]),
                                                    "points" => 1,
                                                    "allow_comments" => 0,
                                                    "enable_flagging" => 0,
                                                    "updated_date" => time(),
                                                    "updated_by" => $ENTRADA_USER->GetID()
                                                );
                                                $exam_element = new Models_Exam_Exam_Element($exam_element_data);

                                                if ($exam_element->insert()) {
                                                    $SUCCESS++;
                                                } else {
                                                    add_error($translate->_("Unfortunately, we were unable to add one of the question groups to the form."));
                                                }
                                            }
                                        }
                                    } else {
                                        $ERROR++;
                                        add_error($translate->_("The question group <strong>" . $exam_group->getGroupTitle() . "</strong> was not added to the exam because it is empty!"));
                                    }
                                } else {
                                    $ERROR++;
                                    add_error($translate->_("The question group <strong>(ID: " . $group_id . ")</strong> was not added to the exam because it could not be found in the system."));
                                }
                            }
                        } else {
                            $ERROR++;
                            add_error($translate->_($SESSION_TEXT["no_exam_found"]));
                        }
                        
                        break;
                }

                if ($SUCCESS && !$ERROR) {
                    Entrada_Utilities_Flashmessenger::addMessage(sprintf($translate->_("Successfully added the Group to the %s."), $PROCESSED["element_type"]), "success", $MODULE);
                    header("Location: ". $url);
                } else {
                    $STEP = 1;
                }
            }

            break;
    }

    switch ($STEP) {
        case 2 :
            if ($ERROR) {
                echo display_error();
            }
            if ($SUCCESS) {
                echo display_success();
                $ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."/".$SUBMODULE."\\'', 5000)";
            }

            break;
        case 1 :

        default :
            if ($ERROR) {
                echo display_error();
            }
            if ($SUCCESS) {
                echo display_success();
            }
            ?>
            <script type="text/javascript">
                jQuery(function($) {
                    $("#advanced-search").advancedSearch(
                        {
                            api_url: "<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-group" ; ?>",
                            resource_url: ENTRADA_URL,
                            filters: {
                                curriculum_tag: {
                                    label: "<?php echo $translate->_("Curriculum Tag"); ?>",
                                    data_source: "get-objectives",
                                    secondary_data_source: "get-child-objectives"
                                },
                                author: {
                                    label: "<?php echo $translate->_("Grouped Item Authors"); ?>",
                                    data_source: "get-group-authors"
                                },
                                course: {
                                    label: "<?php echo $translate->_("Courses"); ?>",
                                    data_source: "get-user-courses"
                                },
                                organisation: {
                                    label: "<?php echo $translate->_("Organisations"); ?>",
                                    data_source: "get-user-organisations"
                                }
                            },
                            load_data_function: "get_groups",
                            no_results_text: "<?php echo $translate->_("No Items found matching the search criteria"); ?>",
                            reload_page_flag: true,
                            selected_list_container: $("#selected_list_container"),
                            results_parent: $("#exam-groups-container"),
                            width: 400
                        }
                    );
                });
            </script>
            <div id="exam-groups-container">
                <form id="group-table-form" action="<?php echo ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?step=2"; ?><?php echo (isset($PROCESSED["element_type"]) ? "&element_type=".$PROCESSED["element_type"] : ""); ?><?php echo (isset($PROCESSED["exam_id"]) ? "&exam_id=".$PROCESSED["exam_id"] : ""); ?>" method="POST">
                    <input type="hidden" id="element_type" name="element_type" value="<?php echo (isset($PROCESSED["element_type"]) ? $PROCESSED["element_type"] : ""); ?>" />
                    <input type="hidden" id="id" name="exam_id" value="<?php echo (isset($PROCESSED["exam_id"]) ? $PROCESSED["exam_id"] : ""); ?>" />
                    <div class="row-fluid space-below">
                        <div class="span12 space-right">
                            <input type="text" id="group-search" placeholder="<?php echo $translate->_("Begin typing to search..."); ?>" class="input-block-level search-icon">
                        </div>
                        <a href="#" id="advanced-search" class="btn" type="button">Advanced Search <i class="icon-chevron-down"></i></a>
                        <div class="pull-right">
                            <?php
                            if (isset($PROCESSED["exam_id"]) && isset($PROCESSED["element_type"]) || isset($PROCESSED["group_id"])) {
                                $back_url = ENTRADA_URL."/admin/".$MODULE."/exams?section=edit-exam&id=".$PROCESSED["exam_id"]; ?>
                                <a href="<?php echo $back_url; ?>" class="btn btn-default"><?php echo $translate->_("Back"); ?></a>
                                <div class="btn-group">
                                    <input type="submit" class="btn btn-success" value="<?php echo $SECTION_TEXT["btn_attach_group"]; ?>" />
                                    <button class="btn btn-success dropdown-toggle" data-toggle="dropdown">
                                        <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a href="<?php echo ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?section=add-item&element_type=" . $PROCESSED["element_type"] . "&id=" . $PROCESSED["exam_id"]; ?>"><?php echo $translate->_("Create and Attach"); ?></a></li>
                                    </ul>
                                </div>
                            <?php
                            } else { ?>
                                <a href="#delete-group-modal" data-toggle="modal" class="btn btn-danger space-right" id="delete-groups"><i class="fa fa-trash-o"></i> <?php echo $SUBMODULE_TEXT["group"]["btn_delete_group"]; ?></a>
                                <a href="#add-group-modal" data-toggle="modal" class="btn btn-success"><i class="icon-plus-sign icon-white"></i> <?php echo $translate->_("Add Group"); ?></a>
                            <?php
                            }
                            ?>
                        </div>
                    </div>
                    <div id="exam-msgs">
                        <div id="exam-groups-loading" class="hide">
                            <p>Loading Question Groups...</p>
                            <img src="<?php echo ENTRADA_URL ."/images/loading.gif" ?>" />
                        </div>
                    </div>
                    <div id="msgs"></div>
                    <div id="group-table-container">
                        <table class="table table-bordered table-striped" id="groups-table" summary="List of Groups">
                            <colgroup>
                                <col class="modified" />
                                <col class="title" />
                                <col class="actions" />
                            </colgroup>
                            <thead>
                                <tr>
                                    <th width="5%"></th>
                                    <th width="80%"><?php echo $translate->_("Title"); ?></th>
                                    <th width="15%"><?php echo $translate->_("Date Created"); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </form>
                <div id="add-group-modal" class="modal hide fade">
                    <form id="add-group-exam-modal" class="form-horizontal" action="<?php echo ENTRADA_URL."/admin/".$MODULE."/".$SUBMODULE."?section=api-group"; ?>" method="POST" style="margin:0px;">
                        <div class="modal-header"><h1><?php echo $SECTION_TEXT["title_modal_add_group"]; ?></h1></div>
                        <div class="modal-body">
                            <div class="control-group" style="margin:0px;">
                                <label class="control-label form-required" for="group-title"><?php echo $SUBMODULE_TEXT["group"]["label_group_title"]; ?></label>
                                <div class="controls">
                                    <input type="text" name="group_title" id="group-title" />
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <div class="row-fluid">
                                <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Cancel"); ?></a>
                                <input type="submit" class="btn btn-primary" value="<?php echo $SUBMODULE_TEXT["buttons"]["add_group"]; ?>" />
                            </div>
                        </div>
                    </form>
                </div>
                <div id="delete-group-modal" class="modal hide fade">
                    <form id="delete-group-form-modal" class="form-horizontal" action="<?php echo ENTRADA_URL."/admin/".$MODULE."/".$SUBMODULE."?section=api-group"; ?>" method="POST" style="margin:0px;">
                        <div class="modal-header"><h1><?php echo $SECTION_TEXT["title_modal_delete_groups"]; ?></h1></div>
                        <div class="modal-body">
                            <div id="no-groups-selected" class="hide">
                                <p><?php echo $translate->_("No Question Groups selected to delete."); ?></p>
                            </div>
                            <div id="groups-selected" class="hide">
                                <p><?php echo $translate->_("Delete Question Groups"); ?></p>
                                <div id="delete-groups-container"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <div class="row-fluid">
                                <a href="#" id="delete-group-cancel" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Cancel"); ?></a>
                                <input type="submit" id="delete-groups-modal-delete" class="btn btn-primary" value="<?php echo $translate->_("Delete"); ?>" />
                            </div>
                        </div>
                    </form>
                </div>
                <div class="row-fluid">
                    <a id="load-groups" class="btn btn-block"><?php echo $translate->_("Load More Grouped Items"); ?></a>
                </div>
            </div>
            <?php 
            if (isset($_SESSION[APPLICATION_IDENTIFIER]["exams"]["groups"]["selected_filters"]) && !empty($_SESSION[APPLICATION_IDENTIFIER]["exams"]["groups"]["selected_filters"])) {
                echo "<form id=\"search-targets-form\">";
                foreach ($_SESSION[APPLICATION_IDENTIFIER]["exams"]["groups"]["selected_filters"] as $key => $filter_type) {
                    foreach ($filter_type as $target_id => $target_label) {
                        echo "<input id=\"". html_encode($key) ."_". html_encode($target_id) ."\" class=\"search-target-control " . html_encode($key) . "_search_target_control\" type=\"hidden\" name=\"". html_encode($key) ."[]\" value=\"". html_encode($target_id) ."\" data-label=\"". html_encode($target_label) ."\"/>";
                    }
                }
                echo "</form>";
            }
        break;
    }
}