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
 * The default file that is loaded when /admin/exams/exams is accessed.
 *
 * @author Organisation: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Daniel Noji <dnoji@mednet.ucla.edu>
 * @copyright Copyright 2015 UC Regents. All Rights Reserved.
 *
*/
if ((!defined("PARENT_INCLUDED")) || (!defined("IN_EXAMS"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("exam", "create", false)) {
	add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%1\$s\">%2\$s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

    $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = \"". ENTRADA_URL ."\";</script>";
    $HEAD[] = "<script type=\"text/javascript\">var API_URL = \"". ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-exams" ."\";</script>";
    $HEAD[] = "<script type=\"text/javascript\">var INDEX_TEXT = ". json_encode($SUBMODULE_TEXT["index"]) . "</script>";
    $HEAD[] = "<script type=\"text/javascript\">var VIEW_PREFERENCE = \"". (isset($PREFERENCES["questions"]["selected_view"]) ? $PREFERENCES["questions"]["selected_view"] : "detail") ."\";</script>";
    $HEAD[] = "<script type=\"text/javascript\">var EXAM_VIEW_PREFERENCE = \"". (isset($PREFERENCES["exams"]["selected_view"]) ? $PREFERENCES["exams"]["selected_view"] : "detail") ."\";</script>";
    $HEAD[] = "<script type=\"text/javascript\">var exam_in_progress = \"". ($exam_in_progress ? $exam_in_progress : 0 ) ."\";</script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.inputselector.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/" . $MODULE . "/" . $SUBMODULE . "/" . $MODULE . "-" .$SUBMODULE . "-admin.js?release=". html_encode(APPLICATION_VERSION) ."\"></script>";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css\" />";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.inputselector.css?release=".html_encode(APPLICATION_VERSION)."\" />";

    if (isset($_SESSION[APPLICATION_IDENTIFIER]["exams"]["exams"]["selected_filters"]) && !empty($_SESSION[APPLICATION_IDENTIFIER]["exams"]["exams"]["selected_filters"])) {
        $sidebar_html = "";
        foreach ($_SESSION[APPLICATION_IDENTIFIER]["exams"]["exams"]["selected_filters"] as $key => $filter_type) {
            $sidebar_html .= "<span>". sprintf($translate->_("%s Filters"), ucwords(str_replace("_", " ", $key))) . "</span>";
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
        $sidebar_html .= "<a href=\"#\" class=\"clear-filters\">".$translate->_("Clear All Filters")."</a>";
        new_sidebar_item($translate->_("Selected Exam Bank Filters"), $sidebar_html, "exam-filters", "open", 2);
    }

    if ($_GET["event_id"] && $event_id = (int)$_GET["event_id"]) {
        $post_exam = 1;
        $HEAD[] = "<script type=\"text/javascript\">var event_id = \"". ($event_id ? $event_id : 0 ) ."\";</script>";
    } else {
        $post_exam = 0;
    }

    $HEAD[] = "<script type=\"text/javascript\">var post_exam = \"". ($post_exam ? $post_exam : 0 ) ."\";</script>";
    ?>
    <script type="text/javascript">
        jQuery(function($) {
            $("#advanced-search").advancedSearch(
                {
                    api_url : "<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-exams" ; ?>",
                    resource_url: ENTRADA_URL,
                    filters : {
                        course : {
                            label : "<?php echo $translate->_("Courses"); ?>",
                            data_source : "get-user-courses"
                        },
                        curriculum_tag : {
                            label : "<?php echo $translate->_("Curriculum Tags"); ?>",
                            data_source : "get-objectives",
                            secondary_data_source: "get-child-objectives"
                        },
                        author : {
                            label : "<?php echo $translate->_("Exam Authors"); ?>",
                            data_source : "get-exam-authors"
                        }
                    },
                    load_data_function: "get_exams",
                    no_results_text: "<?php echo $translate->_("No Exams found matching the search criteria"); ?>",
                    reload_page_flag: true,
                    selected_list_container: $("#selected_list_container"),
                    results_parent: $("#exams-container"),
                    width: 400
                }
            );
        });

        get_exams();
    </script>
    <h1><?php echo $SUBMODULE_TEXT["title"]; ?></h1>
    <div id="exams-container">
        <form id="exam-table-form" action="<?php echo ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE . "?section=delete&step=1"; ?>" method="POST">
            <div class="row-fluid">
                <div class="span12 search-bar">
                    <input type="text" id="exam-search" placeholder="<?php echo $SUBMODULE_TEXT["placeholders"]["exam_bank_search"]?>" class="input-block-level search-icon">
                    <a href="#" id="advanced-search" class="btn" type="button"><?php echo $translate->_("Advanced Search"); ?> <i class="fa fa-chevron-down"></i></a>
                </div>
            </div>
            <div class="row-fluid space-below">
                <div class="pull-right">
                    <div class="btn-group btn-actions-group">
                        <button class="btn dropdown-toggle btn-actions" disabled="disabled" data-toggle="dropdown">
                            <i class="fa fa-wrench"></i> Actions
                            <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a href="#delete-exam-modal" data-toggle="modal"><i class="delete-icon fa fa-trash-o fa-fw"></i> <?php echo $SUBMODULE_TEXT["buttons"]["delete_exam"]; ?></a></li>
<!--                            <li><a href="#move-exam-modal" data-toggle="modal"><i class="move-icon fa fa-arrows-v fa-fw"></i> --><?php //echo $SUBMODULE_TEXT["buttons"]["move_exam"]; ?><!--</a></li>-->
                            <li><a href="#copy-exam-modal" data-toggle="modal"><i class="copy-icon fa fa-files-o fa-fw"></i> <?php echo $SUBMODULE_TEXT["buttons"]["copy_exam"]; ?></a></li>
                        </ul>
                    </div>

                    <?php if ($ENTRADA_ACL->amIAllowed("exam", "create", false)) {
                        if (!$post_exam) { ?>
                        <a href="#add-exam-modal" data-toggle="modal" class="btn btn-success pull-right">
                            <i class="add-icon fa fa-plus-circle"></i>
                            <?php echo $SUBMODULE_TEXT["buttons"]["add_exam"]; ?>
                        </a>
                    <?php } else {
                        ?>
                        <button id="post-exam" class="btn btn-success pull-right" disabled="disabled">
                            <i class="add-icon fa fa-plus-circle"></i>
                            Post Exam
                        </button>
                        <?php
                        }
                    } ?>
                </div>
            </div>
            <div id="exam-msgs">
                <div id="exam-exams-loading" class="hide">
                    <p><?php echo $translate->_("Loading Exams..."); ?></p>
                    <img src="<?php echo ENTRADA_URL ."/images/loading.gif" ?>" />
                </div>
            </div>
            <table class="table table-bordered table-striped" id="exams-table">
                <thead>
                <tr>
                    <th width="5%"></th>
                    <th width="65%"><?php echo $SUBMODULE_TEXT["index"]["title_heading"]; ?></th>
                    <th width="15%"><?php echo $SUBMODULE_TEXT["index"]["updated_heading"]; ?></th>
                    <th width="10%"><?php echo $SUBMODULE_TEXT["index"]["questions_heading"]; ?></th>
                    <th width="5%"><?php echo $SUBMODULE_TEXT["index"]["posts_heading"]; ?></th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </form>
        <div id="post-info-modal"  class="modal hide fade">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo $SUBMODULE_TEXT["index"]["post-info"]["title_post"]; ?></h4>
                    </div>
                    <div class="modal-body">

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo $SUBMODULE_TEXT["buttons"]["btn_cancel"];?></button>
                    </div>
                </div><!-- /.modal-content -->
            </div><!-- /.modal-dialog -->
        </div><!-- /.modal -->
        <div id="add-exam-modal" class="modal hide fade">
            <form class="form-horizontal" action="<?php echo ENTRADA_URL."/admin/" . $MODULE . "/" . $SUBMODULE; ?>?section=add-exam" method="POST" style="margin:0px;">
                <input type="hidden" name="step" value="2" />
                <div class="modal-header"><h1><?php echo $SUBMODULE_TEXT["add-exam"]["title"]; ?></h1></div>
                <div class="modal-body">
                    <div class="control-group" style="margin:0px;">
                        <label class="control-label form-required" for="exam-title"><?php echo $translate->_("Exam Name"); ?></label>
                        <div class="controls">
                            <input type="text" name="exam_title" id="exam-title" />
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row-fluid">
                        <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $DEFAULT_TEXT_LABELS["btn_cancel"]; ?></a>
                        <input type="submit" class="btn btn-primary" value="<?php echo $SUBMODULE_TEXT["buttons"]["add_exam"]; ?>" />
                    </div>
                </div>
            </form>
        </div>
        <div id="delete-exam-modal" class="modal hide fade">
            <form id="delete-exam-modal-form" class="form-horizontal" action="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-exams"; ?>" method="POST" style="margin:0px;">
                <input type="hidden" name="step" value="2" />
                <div class="modal-header"><h1><?php echo $SUBMODULE_TEXT["index"]["title_modal_delete_exams"]; ?></h1></div>
                <div class="modal-body">
                    <div id="no-exams-selected" class="hide">
                        <p><?php echo $SUBMODULE_TEXT["index"]["text_modal_no_exams_selected"] ?></p>
                    </div>
                    <div id="exams-selected" class="hide">
                        <p><?php echo $SUBMODULE_TEXT["index"]["text_modal_delete_exams"] ?></p>
                        <div id="delete-exams-container"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row-fluid">
                        <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $DEFAULT_TEXT_LABELS["btn_cancel"]; ?></a>
                        <input id="delete-exams-modal-button" type="submit" class="btn btn-primary" value="<?php echo $DEFAULT_TEXT_LABELS["btn_delete"]; ?>" />
                    </div>
                </div>
            </form>
        </div>
        <div id="copy-exam-modal" class="modal hide fade">
            <form id="copy-exam-modal-form" class="form-horizontal" action="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-exams"; ?>" method="POST" style="margin:0px;">
                <input type="hidden" name="step" value="2" />
                <div class="modal-header"><h1><?php echo $SUBMODULE_TEXT["index"]["title_modal_copy_exams"]; ?></h1></div>
                <div class="modal-body">
                    <div id="no-exams-selected-copy" class="hide">
                        <p><?php echo $SUBMODULE_TEXT["index"]["text_modal_no_exams_selected_c"] ?></p>
                    </div>
                    <div id="exams-selected-copy" class="hide">
                        <p><?php echo $SUBMODULE_TEXT["index"]["text_modal_copy_exams"] ?></p>
                        <div id="copy-exams-container"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row-fluid">
                        <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $DEFAULT_TEXT_LABELS["btn_cancel"]; ?></a>
                        <input id="copy-exams-modal-button" type="submit" class="btn btn-primary" value="<?php echo $DEFAULT_TEXT_LABELS["btn_copy"]; ?>" />
                    </div>
                </div>
            </form>
        </div>
        <div id="move-exam-modal" class="modal hide fade">
            <form id="move-exam-modal-form" class="form-horizontal" action="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/" . $SUBMODULE . "?section=api-exams"; ?>" method="POST" style="margin:0px;">
                <input type="hidden" name="step" value="2" />
                <div class="modal-header"><h1><?php echo $SUBMODULE_TEXT["index"]["title_modal_move_exams"]; ?></h1></div>
                <div class="modal-body">
                    <div id="no-exams-selected-move" class="hide">
                        <p><?php echo $SUBMODULE_TEXT["index"]["text_modal_no_exams_selected_m"] ?></p>
                    </div>
                    <div id="exams-selected-move" class="hide">
                        <p><?php echo $SUBMODULE_TEXT["index"]["text_modal_move_exams"] ?></p>
                        <div id="move-exams-container"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row-fluid">
                        <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $DEFAULT_TEXT_LABELS["btn_cancel"]; ?></a>
                        <input id="move-exams-modal-button" type="submit" class="btn btn-primary" value="<?php echo $DEFAULT_TEXT_LABELS["btn_move"]; ?>" />
                    </div>
                </div>
            </form>
        </div>
        <div class="row-fluid">
            <div class="span12">
                <div id="exams-loaded-display" class="space-below"></div>
            </div>
        </div>
        <div id="per_page_nav" class="row-fluid">
            <div class="span5">
                <button class="btn btn-default" id="load-previous-exams" disabled="disabled">
                    <i class="fa fa-chevron-left"></i>
                    <?php echo $DEFAULT_TEXT_LABELS["btn_previous_page"]; ?>
                </button>
            </div>
            <div class="span2">
                <button class="btn btn-default input-mini input-selector" id="number_exams_pp" type="text" name="number_exams_pp" value="50">
                    50 - Per Page
                </button>
            </div>
            <div class="span5">
                <button class="btn btn-default" id="load-exams">
                    <?php echo $DEFAULT_TEXT_LABELS["btn_next_page"]; ?>
                    <i class="fa fa-chevron-right"></i>
                </button>
            </div>
        </div>
        <div id="selected_list_container"></div>
    </div>
    <?php 
    if (isset($_SESSION[APPLICATION_IDENTIFIER]["exams"]["exams"]["selected_filters"]) && !empty($_SESSION[APPLICATION_IDENTIFIER]["exams"]["exams"]["selected_filters"])) {
        echo "<form id=\"search-targets-form\">";
        foreach ($_SESSION[APPLICATION_IDENTIFIER]["exams"]["exams"]["selected_filters"] as $key => $filter_type) {
            foreach ($filter_type as $target_id => $target_label) {
                echo "<input id=\"". html_encode($key) ."_". html_encode($target_id) ."\" class=\"search-target-control " . html_encode($key) . "_search_target_control\" type=\"hidden\" name=\"". html_encode($key) ."[]\" value=\"". html_encode($target_id) ."\" data-label=\"". html_encode($target_label) ."\"/>";
            }
        }
        echo "</form>";
    }
}