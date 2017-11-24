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
 * @author Organisation: Queen's University
 * @author Unit: MEdTech Unit
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2011 Queen's University. All Rights Reserved.
 *
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_ROTATION_SCHEDULE"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("rotationschedule", "read",false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {

    $HEAD[] = "<script type=\"text/javascript\">
                    var ENTRADA_URL = '" . ENTRADA_URL . "';
                    var MODULE = \"" . $MODULE . "\";
                    var SECTION = \"" . $SECTION . "\";
                    var API_URL = \"" . ENTRADA_URL . "/admin/" . $MODULE . "?section=api-schedule\";
                    var active_cell;
                    var draft_id = \"" . $PROCESSED["draft_id"] . "\";

                    var translation = {
                        \"off_service_rotation\" : \"" . $translate->_("Off Service Rotations") . "\",
                        \"on_service_rotation\" : \"" . $translate->_("On Service Rotations") . "\",
                        \"remove_learner_from_slot\" : \"" . $translate->_("I would like to remove this learner from this slot") . "\",
                        \"rotation_name\" : \"" . $translate->_("Rotation Name") . "\",
                        \"rotation_dates\" : \"" . $translate->_("Rotation Dates") . "\",
                        \"short_name\" : \"" . $translate->_("Short Name") . "\",
                        \"learners_not_yet_added\" : \"" . $translate->_("The learners in this course have not been added to the rotation schedule.") . "\"
                    };

                    sidebarBegone();
               </script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.audienceselector.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/jquery/jquery.audienceselector.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.advancedsearch.js\"></script>";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/jquery/jquery.advancedsearch.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.dataTables.min.js\"></script>";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/" . $MODULE . "/" . $MODULE . ".css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
    $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/" . $MODULE . "/" . $MODULE . ".js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";

    if (!isset($_SESSION[APPLICATION_IDENTIFIER]["rotationschedule"][$SECTION]["active-tab"])) {
        $_SESSION[APPLICATION_IDENTIFIER]["rotationschedule"][$SECTION]["active-tab"] = "rotations";
    }

    Entrada_Utilities_Flashmessenger::displayMessages();

    if (isset($_POST["save"])) {
        $method = "save";
    }
    if (isset($_POST["delete"])) {
        $method = "delete";
    }
    if (isset($_POST["publish"])) {
        $method = "publish";
    }

    if (isset($_GET["draft_id"])) {
        $PROCESSED["draft_id"] = $_GET["draft_id"];
    }

    switch ($STEP) {
        case 2 :
            switch ($method) {
                case "save" :
                    if (isset($_POST["draft_title"]) && $tmp_input = clean_input($_POST["draft_title"], array("trim", "striptags"))) {
                        $PROCESSED["draft_title"] = $tmp_input;
                    }

                    if (isset($_POST["draft_author_proxy_id"]) && (is_array($_POST["draft_author_proxy_id"]))) {
                        foreach ($_POST["draft_author_proxy_id"] as $proxy_id) {
                            if ($tmp_input = clean_input($proxy_id, array("trim", "int"))) {
                                $PROCESSED["draft_author_proxy_id"][] = $tmp_input;
                            }
                        }
                    }

                    if (isset($_GET["draft_id"]) && $tmp_input = clean_input($_GET["draft_id"], array("trim", "striptags"))) {
                        $PROCESSED["draft_id"] = $tmp_input;
                    }

                    if (defined("EDIT_DRAFT") && EDIT_DRAFT == true) {
                        $update_method = "update";
                        $draft_data = array(
                            "draft_title" => $PROCESSED["draft_title"],
                            "course_id" => $draft->getCourseID(),
                            "updated_date" => time(),
                            "updated_by" => $ENTRADA_USER->getActiveID()
                        );
                    } else {
                        if (isset($_POST["course_id"]) && $tmp_input = clean_input($_POST["course_id"], array("int"))) {
                            if (Models_Course::checkCourseOwner($tmp_input, $ENTRADA_USER->getActiveId()) || $ENTRADA_USER->getActiveGroup() == "admin") {
                                $PROCESSED["course_id"] = $tmp_input;
                            } else {
                                add_error($translate->_("You do not have permission to modify drafts in this course."));
                            }
                        }

                        $update_method = "insert";
                        $draft_data = array(
                            "draft_title" => $PROCESSED["draft_title"],
                            "course_id" => $PROCESSED["course_id"],
                            "updated_date" => time(),
                            "updated_by" => $ENTRADA_USER->getActiveID(),
                            "created_date" => time(),
                            "created_by" => $ENTRADA_USER->getActiveID(),
                            "status" => "draft"
                        );
                    }

                    if (!$ERROR) {
                        $draft = $draft->fromArray($draft_data)->$update_method();

                        if (defined("EDIT_DRAFT") && EDIT_DRAFT == true) {
                            if (isset($PROCESSED["draft_author_proxy_id"]) && is_array($PROCESSED["draft_author_proxy_id"])) {
                                foreach ($PROCESSED["draft_author_proxy_id"] as $author_proxy) {
                                    $draft_author_data = array(
                                        "cbl_schedule_draft_id" => $PROCESSED["draft_id"],
                                        "proxy_id" => $author_proxy,
                                        "created_date" => time(),
                                        "created_by" => $ENTRADA_USER->getActiveID()
                                    );

                                    $draft_authors = new Models_Schedule_Draft_Author($draft_author_data);
                                    $draft_authors_exits = Models_Schedule_Draft_Author::isAuthor($PROCESSED["draft_id"], $author_proxy);
                                    $user = Models_User::fetchRowByID($author_proxy);
                                    if (!$draft_authors_exits) {
                                        if (!$draft_authors->insert()) {
                                            add_error("There was a problem adding " . $user->getFirstname() . " " . $user->getLastname() . " to this draft.");
                                        }
                                    } else {
                                        add_error($user->getFirstname() . " " . $user->getLastname() . " is already an author of this draft.");
                                    }
                                }
                            }
                            $STEP = 1;
                            $url = ENTRADA_URL . "/admin/" . $MODULE . "?section=edit-draft&draft_id=" . $draft->getID();
                            add_success("Successfully updated draft. You will automatically be redirected in 5 seconds or click <a href=\"" . $url . "\">here</a> if you do not want to wait. ");
                            echo display_success();
                            echo "<script>setTimeout('window.location=\\'" . $url . "\\'', 5000);</script>";
                            exit;
                        } else {
                            $url = ENTRADA_URL . "/admin/" . $MODULE . "?section=edit-draft&draft_id=" . $draft->getID();
                            header("Location: " . $url);
                        }
                    }
                    break;
            }
            break;
        case 1 :
        default :
            continue;
            break;
    }

    if ($SUCCESS) {
        echo display_success();
    }
    if ($ERROR) {
        echo display_error();
    }
    if ($NOTICE) {
        echo display_notice();
    }

    $courses = Models_Course::fetchAllByOrg($ENTRADA_USER->getActiveOrganisation());

    ?>
    <script type="text/javascript">
        jQuery(function ($) {
            $("#choose-rotations-btn").advancedSearch({
                api_url: ENTRADA_URL + "/admin/rotationschedule?section=api-schedule",
                resource_url: ENTRADA_URL,
                filters: {
                    rotation: {
                        label: "Rotation Name",
                        data_source: "get-slot-blocks"
                    }
                }, list_data: {
                    selector: "#rotation-container-location"
                },
                no_results_text: "No Rotations found matching the search criteria",
                parent_form: $("#rotation-form"),
                results_parent: $("#book-slot"),
                search_target_form_action: ENTRADA_URL + "/admin/rotationschedule/form",
                width: 325
            });
        });
    </script>
    <form class="form-horizontal" id="my-drafts" method="POST" action="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "?section=" . $SECTION . "&draft_id=" . $draft->getID(); ?>">
        <input type="hidden" name="step" value="<?php echo $STEP + 1; ?>"/>
        <h2 class="collapsable <?php echo (defined("EDIT_DRAFT") && EDIT_DRAFT == true) ? "collapsed" : ""; ?>" title="Draft Information"><?php echo($draft->getStatus() == "live" ? $translate->_("Published Rotation Schedule Information") : $translate->_("Draft Rotation Schedule Information")); ?></h2>

        <div id="draft-information">
            <div class="control-group">
                <label class="control-label" for="draft-title"><?php echo $translate->_("Draft Title"); ?></label>
                <div class="controls">
                    <input type="text" id="draft-title" name="draft_title" value="<?php echo $draft->getTitle(); ?>"/>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label" for="draft-authors"><?php echo $translate->_("Draft Authors"); ?></label>
                <div class="controls">
                    <input type="text" id="draft-authors" name="draft_authors"/>
                    <div class="draft_author_container"></div>
                    <?php
                    if ($draft_authors) {
                        ?>
                        <ul class="unstyled">
                            <?php
                            foreach ($draft_authors as $author) {
                                $user = ($author ? $author->getUser() : false);
                                if ($user) {
                                    ?>
                                    <li>
                                        <i id="<?php echo $user->getID(); ?>" class="icon-remove-circle remove-draft-author"></i> <?php echo $user->getFullname(true); ?>
                                    </li>
                                    <?php
                                }
                            }
                            ?>
                        </ul>
                        <?php
                    }
                    ?>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label" for="course"><?php echo $translate->_("Course"); ?></label>
                <div class="controls">
                    <?php
                    $course = Models_Course::fetchRowByID($draft->getCourseID());
                    if ($course) {
                        ?>
                        <input type="text" readonly="readonly" value="<?php echo $course->getCourseCode() . " - " . $course->getCourseName(); ?>"/>
                        <?php
                    }
                    ?>
                </div>
            </div>
            <div class="row-fluid">
                <input type="submit" name="save" value="<?php echo $translate->_("Save"); ?>" class="btn btn-primary pull-right"/>
            </div>
        </div>
        <?php if (defined("EDIT_DRAFT") && EDIT_DRAFT == true) {
        $schedules = Models_Schedule::fetchAllByDraftID($PROCESSED["draft_id"], "rotation_stream"); ?>
        <div class="tabbable">
            <ul class="nav nav-tabs">
                <li class="rotations-tab-toggle <?php echo $_SESSION[APPLICATION_IDENTIFIER]["rotationschedule"][$SECTION]["active-tab"] == "rotations" ? " active" : ""; ?>">
                    <a href="#rotations" data-toggle="tab"><?php echo $translate->_("Rotations"); ?></a></li>
                <li class="learners-tab-toggle <?php echo $_SESSION[APPLICATION_IDENTIFIER]["rotationschedule"][$SECTION]["active-tab"] == "learners" ? "active" : ""; ?>">
                    <a href="#learners" data-toggle="tab"><?php echo $translate->_("Learners"); ?></a></li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane <?php echo $_SESSION[APPLICATION_IDENTIFIER]["rotationschedule"][$SECTION]["active-tab"] == "rotations" ? " active" : ""; ?>" id="rotations">
                    <div class="row-fluid space-below">
                        <?php if ($schedules) { ?>
                            <input type="submit" name="delete" class="btn btn-danger delete-rotation" value="<?php echo $translate->_("Delete"); ?>"/>
                        <?php } ?>
                        <div class="btn-group pull-right">
                            <a href="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "?section=import&draft_id=" . $draft->getID(); ?>" class="btn btn-success"><?php echo $translate->_("Add Rotation"); ?></a>
                            <a href="#" class="btn btn-success dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a href="#import-csv" data-toggle="modal"><?php echo $translate->_("Import Learner Rotation Schedule"); ?></a>
                                </li>
                                <li>
                                    <a href="#import-rotations-csv" data-toggle="modal"><?php echo $translate->_("Import Rotation Structure"); ?></a>
                                </li>
                                <li>
                                    <a href="#copy-draft-rotations" data-toggle="modal"><?php echo $translate->_("Copy Existing Rotations"); ?></a>
                                </li>
                                <li>
                                    <a href="#export-csv" data-toggle="modal"><?php echo $translate->_("Export Report"); ?></a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <h4><?php echo $translate->_("My Rotations"); ?></h4>
                    <table class="table table-bordered table-striped" id="on-service-rotation-table">
                        <thead>
                        <tr>
                            <th width="3%"></th>
                            <th><?php echo $translate->_("Name"); ?></th>
                            <th width="15%"><?php echo $translate->_("Shortname"); ?></th>
                            <th width="10%"><?php echo $translate->_("Course"); ?></th>
                            <th width="12%"><?php echo $translate->_("Start"); ?></th>
                            <th width="12%"><?php echo $translate->_("Finish"); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        if ($schedules) {
                            $HEAD[] = "<script type=\"text/javascript\">
                            jQuery(document).ready(function() {
                                jQuery('#on-service-rotation-table').dataTable(
                                    {
                                        'aaSorting': [[1, 'asc']],
                                        'bPaginate': false,
                                        'bInfo': false,
                                        'bAutoWidth': false,
                                        'bFilter': false,
                                        'aoColumns': [
                                            {'bSortable': false},
                                            null,
                                            null,
                                            null,   
                                            null,
                                            null
                                        ]
                                    }
                                );
                            });
                            </script>";
                            foreach ($schedules as $schedule) {
                                $course = Models_Course::get($schedule->getCourseID());
                                $children = $schedule->getChildren();
                                $url = ENTRADA_URL . "/admin/" . $MODULE . "?section=edit&schedule_id=" . $schedule->getID();
                                ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="delete[]" value="<?php echo $schedule->getID(); ?>"/>
                                    </td>
                                    <td><a href="<?php echo $url; ?>"><?php echo $schedule->getTitle(); ?></a></td>
                                    <td><a href="<?php echo $url; ?>"><?php echo $schedule->getCode(); ?></a></td>
                                    <td><a href="<?php echo $url; ?>"><?php echo $course->getCourseName(); ?></a></td>
                                    <td>
                                        <a href="<?php echo $url; ?>"><?php echo date("Y-m-d", $schedule->getStartDate()); ?></a>
                                    </td>
                                    <td>
                                        <a href="<?php echo $url; ?>"><?php echo date("Y-m-d", $schedule->getEndDate()); ?></a>
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            ?>
                            <tr>
                                <td colspan="7"><?php echo $translate->_("There are no on service rotations in this draft."); ?></td>
                            </tr>
                            <?php
                        }
                        ?>
                        </tbody>
                    </table>
                    <h4><?php echo $translate->_("Available Off Service Rotations"); ?></h4>
                    <?php
                    $off_service_schedules = Models_Schedule::fetchOffService($draft->getID(), $draft->getCPeriodID(), $draft->getCourseID());
                    if ($off_service_schedules) {
                        $HEAD[] = "<script type=\"text/javascript\">
                            jQuery(document).ready(function() {
                                jQuery('#off-service-rotation-table').dataTable(
                                    {
                                        'aaSorting': [[0, 'asc']],
                                        'bPaginate': false,
                                        'bInfo': false,
                                        'bAutoWidth': false,
                                        'bFilter': false
                                    }
                                );
                            });
                            </script>";
                        ?>
                        <table class="table table-bordered table-striped" id="off-service-rotation-table">
                            <thead>
                            <tr>
                                <th><?php echo $translate->_("Name"); ?></th>
                                <th width="15%"><?php echo $translate->_("Shortname"); ?></th>
                                <th width="15%"><?php echo $translate->_("Course"); ?></th>
                                <th width="12%"><?php echo $translate->_("Start"); ?></th>
                                <th width="12%"><?php echo $translate->_("Finish"); ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            foreach ($off_service_schedules as $schedule) {
                                $os_course = Models_Course::fetchRowByID($schedule->getCourseID());
                                $url = ENTRADA_URL . "/admin/" . $MODULE . "?section=view-offservice&schedule_id=" . $schedule->getID() . "&draft_id=" . $draft->getID();
                                ?>
                                <tr>
                                    <td><a href="<?php echo $url; ?>"><?php echo $schedule->getTitle(); ?></a></td>
                                    <td><?php echo strtoupper($os_course->getCourseCode() . "-" . $schedule->getCode()); ?></td>
                                    <td><?php echo $os_course->getCourseName(); ?></td>
                                    <td><?php echo date("Y-m-d", $schedule->getStartDate()); ?></td>
                                    <td><?php echo date("Y-m-d", $schedule->getEndDate()); ?></td>
                                </tr>
                                <?php
                            }
                            ?>
                            </tbody>
                        </table>
                        <?php
                    } else {
                        ?>
                        <p><?php echo $translate->_("There are currently no off service rotations available for this draft."); ?></p>
                        <?php
                    }
                    ?>
                    </tbody>
                    </table>
                    <div class="row-fluid">
                        <a href="<?php echo ENTRADA_URL . "/admin/" . $MODULE . ($draft->getStatus() == "draft" ? "?section=drafts" : ""); ?>" class="btn btn-default"><?php echo $translate->_("Back"); ?></a>
                        <?php if ($draft->getStatus() == "draft") { ?>
                            <input type="submit" class="btn btn-primary pull-right publish-draft" value="<?php echo $translate->_("Publish"); ?>"/><?php } ?>
                    </div>
                    <?php } ?>
                </div>
                <div class="tab-pane <?php echo $_SESSION[APPLICATION_IDENTIFIER]["rotationschedule"][$SECTION]["active-tab"] == "learners" ? " active" : ""; ?>" id="learners">
                    <div id="navigation-buttons-wrapper">
                        <div id="navigation-buttons-fixed-wrapper">
                            <div id="navigation-buttons">
                                <div class="btn-group">
                                    <button class="btn btn-small scroll-left"><i class="icon-chevron-left"></i></button>
                                    <button class="btn btn-small current-block-button"><?php echo $translate->_("Current Block"); ?></button>
                                    <button class="btn btn-small scroll-right"><i class="icon-chevron-right"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div style="position:relative;">
                        <?php
                        $table_data = $draft->getScheduleTable();
                        if ($table_data) {
                            Views_Schedule_UserInterfaces::renderScheduleTables($table_data, $PROCESSED["draft_id"]);
                        } else {
                            echo $translate->_("Before learners can be scheduled at least one rotation must be added to this draft.");
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <?php /** Figure out how to get proxy id and then pass it to the code below or figure out how to query from JS in rotationschedule.js  **/
    Views_Schedule_UserInterfaces::renderSlotBookingModal($course->getID(), $PROCESSED["draft_id"], false); ?>
    <div id="import-csv" class="modal hide fade">
        <form action="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "?section=api-schedule"; ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="method" value="import-csv"/>
            <input type="hidden" name="draft_id" value="<?php echo $PROCESSED["draft_id"]; ?>"/>
            <div class="modal-header">
                <h1><?php echo $translate->_("Import Learner Rotation Schedule"); ?></h1>
            </div>
            <div class="modal-body">
                <input type="file" name="csv" style="padding:5px;"/>
            </div>
            <div class="modal-footer">
                <a href="<?php echo ENTRADA_URL; ?>/templates/default/demo/demo_import_learner_rotation_schedule.csv" class="pull-left"><?php echo $translate->_("Download Example CSV file"); ?></a>
                <a href="" id="cancel" class="btn btn-default"><?php echo $translate->_("Cancel"); ?></a>
                <input type="submit" class="btn btn-success" value="<?php echo $translate->_("Import"); ?>"/>
            </div>
        </form>
    </div>
    <div id="import-rotations-csv" class="modal hide fade">
        <form action="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "?section=api-schedule"; ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="method" value="import-rotations-csv"/>
            <input type="hidden" name="draft_id" value="<?php echo $PROCESSED["draft_id"]; ?>"/>
            <div class="modal-header">
                <h1><?php echo $translate->_("Import Rotation Schedule Structure"); ?></h1>
            </div>
            <div class="modal-body">
                <h2> Select a file:</h2>
                <input type="file" name="rotation-csv" style="padding:5px;"/>
                <h2>Select a Template:</h2>
                <?php
                $draft_curriculum_period = $draft->getCPeriodID();
                $curriculum_type = Models_Curriculum_Type::fetchRowByCPeriodID($draft_curriculum_period);

                if ($curriculum_type) {
                    $curriculum_period = Models_Curriculum_Period::fetchRowByID($draft_curriculum_period);
                    if ($curriculum_period) {
                        ?>
                        <h3><?php echo $curriculum_type->getCurriculumTypeName(); ?></h3>
                        <table class="table table-bordered table-striped">
                            <?php
                            $schedules = Models_Schedule::fetchAllTemplatesByCPeriodID($curriculum_period->getCperiodID());
                            if ($schedules) {
                                ?>
                                <tbody>
                                <?php
                                foreach ($schedules as $schedule) {
                                    ?>
                                    <tr>
                                        <td><input type="radio" id="block_type_id_<?php echo $schedule->getBlockTypeID(); ?>" name="block_type_id" value="<?php echo $schedule->getBlockTypeID(); ?>" /></td>
                                        <td><?php echo $schedule->getTitle(); ?></td>
                                        <td><?php echo date("Y-m-d", $schedule->getStartDate()); ?></td>
                                        <td><?php echo date("Y-m-d", $schedule->getEndDate()); ?></td>
                                        <td><?php echo count($schedule->getChildren()); ?> blocks</td>
                                    </tr>
                                    <?php
                                }
                                ?>
                                </tbody>
                                <?php
                            } else {
                                echo "No schedule templates have been found. Please ensure the Curriculum Period that you selected for this draft is correct";
                                $ERROR++;
                            }
                            ?>
                        </table>
                        <?php
                    } else {
                        echo "No curriculum period found. Please ensure the Curriculum Period that you selected for this draft is correct.";
                        $ERROR++;
                    }
                } else {
                    echo "No curriculum type found. Please ensure the Curriculum Period that you selected for this draft is correct.";
                    $ERROR++;
                }
                ?>
            </div>
            <div class="modal-footer">
                <a href="<?php echo ENTRADA_URL; ?>/templates/default/demo/demo_import_rotation_schedule_structure.csv" class="pull-left"><?php echo $translate->_("Download Example CSV file"); ?></a>
                <a href="" id="cancel" class="btn btn-default"><?php echo $translate->_("Cancel"); ?></a>
                <input type="submit" class="btn btn-success" value="<?php echo $translate->_("Import Rotations"); ?>" <?php echo($ERROR != 0 ? "disabled" : "") ?> />
            </div>
        </form>
    </div>
    <div id="copy-draft-rotations" class="modal hide fade">
        <form id="copy-draft-rotations-form" action="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "?section=api-schedule"; ?>" method="POST">
        <input type="hidden" id="draft_id" value="<?php echo $PROCESSED["draft_id"]; ?>"/>
        <div class="modal-header center">
            <h1><?php echo $translate->_("Copy Rotation Schedules"); ?></h1>
        </div>
        <div class="modal-body center">
            <div id="copy-draft-rotations-success" class="hide"></div>
            <div id="copy-draft-rotations-error" class="hide"></div>
            <?php
            $drafts = array();
            $previous_drafts = Models_Schedule_Draft::fetchAllByProxyID($ENTRADA_USER->getActiveID(), "live");
            $unpublished_drafts = Models_Schedule_Draft::fetchAllByProxyID($ENTRADA_USER->getActiveID(), "draft");
            if ($previous_drafts) {
                $drafts = array_merge($drafts, $previous_drafts);
                if ($unpublished_drafts) {
                    $drafts = array_merge($drafts, $unpublished_drafts);
                }
            }
            if (count($drafts) > 1) { ?>
                <h2><?php echo $translate->_("Select a schedule to copy"); ?></h2>
                <select id="copy-rotations-draft-selector" class="space-below">
                    <?php
                    foreach ($drafts as $previous_draft) {
                        // Do not include the draft we are editing.
                        if ($previous_draft->getID() != $PROCESSED["draft_id"]) {
                            $previous_draft_cperiod = Models_Curriculum_Period::fetchRowByID($previous_draft->getCPeriodID());
                            ?>
                            <option value="<?php echo html_encode($previous_draft->getID()); ?>">
                                <?php
                                echo html_encode($previous_draft->getTitle());
                                if ($previous_draft_cperiod) {
                                    echo html_encode(" (" . date("Y-m-d", $previous_draft_cperiod->getStartDate()) . " to " . date("Y-m-d", $previous_draft_cperiod->getFinishDate()) . ")");
                                }
                                ?>
                            </option>
                            <?php
                        }
                    } ?>
                </select>
                <?php
            } else {
                ?>
                <h2><?php echo $translate->_("No previous rotation schedules found."); ?></h2>
                <?php
            }
            ?>
            </div>
            <div class="modal-footer">
                <a href="" id="cancel" class="btn btn-default pull-left"><?php echo $translate->_("Cancel"); ?></a>
                <button id="copy-draft-rotations-confirm" class="btn btn-success pull-right" <?php echo(!$drafts ? "disabled" : "") ?>><?php echo $translate->_("Copy Rotations"); ?></button>
            </div>
        </form>
    </div>
    <div id="export-csv" class="modal hide fade">
        <form id="export-form" class="form-horizontal">
            <div class="modal-header center">
                <h1><?php echo $translate->_("Export Learner Rotation Schedule"); ?></h1>
            </div>
            <div class="modal-body center">
                <div class="control-group pull-left">
                    <label class="control-label" for="block-type"><?php echo $translate->_("Block Type"); ?></label>
                    <div class="controls">
                        <select id="block-type">
                            <option value="1" selected="selected"><?php echo $translate->_("One Week"); ?></option>
                            <option value="2"><?php echo $translate->_("Two Week"); ?></option>
                            <option value="3"><?php echo $translate->_("Four Week"); ?></option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" id="cancel" class="btn btn-default"><?php echo $translate->_("Cancel"); ?></a>
                <a id="export-btn" class="btn btn-success" download href="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "?section=api-schedule&amp;method=export-csv&amp;draft_id=".$draft->getID()."&amp;"; ?>"><?php echo $translate->_("Export"); ?></a>
            </div>
        </form>
    </div>
    <?php
}
