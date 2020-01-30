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
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <ryan.warner@queensu.ca>
 * @copyright Copyright 2012 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_EVENTS"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("eventcontent", "update", false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."].");
} else {
    $draft_id = (int) $_GET["draft_id"];
    $draft = Models_Event_Draft::fetchRowByID($draft_id);

    $HEAD[]	= "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/events_exporter.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";

    $default_csv_headings = array(
        "event_id" => "Original Event",
        "parent_event" => "Parent Event",
        "parent_id" => "Parent ID",
        "recurring_id" => "Recurring Event",
        "event_term" => "Term",
        "course_code" => "Course Code",
        "course_name" => "Course Name",
        "event_start_date" => "Date",
        "event_start_time" => "Start Time",
        "total_duration" => "Total Duration",
        "event_type_durations" => "Event Type Durations",
        "event_types" => "Event Types",
        "event_title" => "Event Title",
        "event_description" => "Event Description",
        "event_location" => "Location",
        "audience_cohorts" => "Audience (Cohorts)",
        "audience_groups" => "Audience (Groups)",
        "audience_students" => "Audience (Students)",
        "teacher_numbers" => "Teacher Numbers",
        "teacher_names" => "Teacher Names",
        "objectives_release_date" => "Objectives Release Date"
    );

    $objective_name = $translate->_("events_filter_controls");
    $curriculum_objectives_name = $objective_name["co"]["global_lu_objectives_name"];
    $clinical_presentations_name = $objective_name["cp"]["global_lu_objectives_name"];

    $additional_csv_headings = array(
        "student_names" => "Student Names",
        "release_date" => "Release Date",
        "release_until" => "Release Until",
        "event_children" => "Child Events",
        "event_message" => "Required Preparation",
        "free_text_objectives" => "Free Text Objectives",
        "curriculum_objectives" => $curriculum_objectives_name,
        "clinical_presentations" => $clinical_presentations_name,
        "hot_topics" => "Hot Topics",
        "attached_files" => "Attached Files",
        "attached_links" => "Attached Links",
        "attached_quizzes" => "Attached Quizzes",
        "attendance" => "Attendance",
        "attendance_required" => "Attendance Required",
        "auditor_numbers" => "Auditor Numbers",
        "auditor_names" => "Auditor Names",
        "teachers_assistant_numbers" => "Teacher's Assistant Numbers",
        "teachers_assistant_names" => "Teacher's Assistant Names",
        "tutor_numbers" => "Tutor Numbers",
        "tutor_names" => "Tutor Names",
    );
    
	// Error Checking
	switch ($STEP) {
		case 2 :
            $PROCESSED["associated_proxy_ids"] = array();

			$i = 0;
            if (isset($_POST["options"]) && !empty($_POST["options"])) {
                foreach ($_POST["options"] as $option => $value) {
                    $PROCESSED["options"][$i]["option"] = clean_input($option, "alpha");
                    $PROCESSED["options"][$i]["value"] = 1;
                    $i++;
                }
            }
			
			/**
			* Required field "draft_name" / Draft Title.
			*/
			if ((isset($_POST["draft_name"])) && ($tmp_input = clean_input($_POST["draft_name"], array("notags", "trim")))) {
				$PROCESSED["name"] = $tmp_input;
			} else {
				add_error("The <strong>Draft Title</strong> field is required.");
			}

			/**
			* Non-Required field "draft_description" / Draft Description.
			*/
			if ((isset($_POST["draft_description"])) && ($tmp_input = clean_input($_POST["draft_description"], array("trim", "allowedtags")))) {
				$PROCESSED["description"] = $tmp_input;
			} else {
				$PROCESSED["description"] = "";
			}

			/**
			* Required field "associated_proxy_ids" / Draft Authors (array of proxy ids).
			* This is actually accomplished after the draft is inserted below.
			*/
			if ((isset($_POST["associated_proxy_ids"]))) {
				$associated_proxy_ids = explode(",", $_POST["associated_proxy_ids"]);
				foreach ($associated_proxy_ids as $contact_order => $proxy_id) {
					if ($proxy_id = clean_input($proxy_id, array("trim", "int"))) {
						$PROCESSED["associated_proxy_ids"][(int) $contact_order] = $proxy_id;
					}
				}
			} else {
                add_error("There were no <strong>Draft Authors</strong> provided.");
            }

			/**
			* The current draft author must be in the draft author list.
			*/
			if (!in_array($ENTRADA_USER->getActiveId(), $PROCESSED["associated_proxy_ids"])) {
				array_unshift($PROCESSED["associated_proxy_ids"], $ENTRADA_USER->getActiveId());
				add_notice("You cannot remove yourself as a <strong>Draft Author</strong>.");
			}

			if (!$ERROR) {
				if ($draft->fromArray(array("name" => $PROCESSED["name"], "description" => $PROCESSED["description"]))->update()) {
					/**
					* Delete existing draft contacts, so we can re-add them.
					*/
					if ($draft->deleteCreators()) {
                        /**
                        * Add the updated draft authors to the draft_contacts table.
                        */

                        if ((is_array($PROCESSED["associated_proxy_ids"])) && !empty($PROCESSED["associated_proxy_ids"])) {
                            foreach ($PROCESSED["associated_proxy_ids"] as $proxy_id) {
                                $creator = new Models_Event_Draft_Creator(array("draft_id" => $draft_id, "proxy_id" => $proxy_id));
                                if (!$creator->insert()) {
                                    add_error("There was an error while trying to attach a <strong>Draft Author</strong> to this draft.<br /><br />The system administrator was informed of this error; please try again later.");
                                    application_log("error", "Unable to insert a new draft_contact record while adding a new draft. Database said: ".$db->ErrorMsg());
                                }
                            }
                        }
                    }
					
                    if ($draft->deleteOptions()) {
                        if ($PROCESSED["options"]) {
                            foreach ($PROCESSED["options"] as $option) {
                                $option["draft_id"] = $draft_id;
                                $new_draft_option = new Models_Event_Draft_Option($option);
                                if (!$new_draft_option->insert()) {
                                    application_log("error", "Error when saving draft [".$draft_id."] options, DB said: ".$db->ErrorMsg());
                                }
                            }
                        }
                    }
					add_success("The <strong>Draft Information</strong> section has been successfully updated.");
					application_log("success", "Draft information for draft_id [".$draft_id."] was updated.");
				} else {
					add_error("There was a problem updating this draft. The system administrator was informed of this error; please try again later.");
					application_log("error", "There was an error updating draft information for draft_id [".$draft_id."]. Database said: ".$db->ErrorMsg());
				}
			}
		break;
        default :
            continue;
        break;
	}

	if ($draft && $draft->getStatus() == "open") {
        $BREADCRUMB[] = array("url" => "", "title" => limit_chars($draft->getName(), 32));

        $options = Models_Event_Draft_Option::fetchAllByDraftID($draft_id);
        if ($options) {
            foreach ($options as $option) {
                $draft_options[$option->getOption()] = true;
            }
        }

        $current_creators = array();
        $creators = Models_Event_Draft_Creator::fetchAllByDraftID($draft_id);
        if (!empty($creators)) {
            foreach ($creators as $creator) {
                $PROCESSED["associated_proxy_ids"][] = $creator->getProxyID();
            }
        }

        if (!in_array($ENTRADA_USER->getID(), $PROCESSED["associated_proxy_ids"])) {
            add_notice("Your account is not approved to work on this draft schedule.<br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");
            echo display_notice();
        } else {
            $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/AutoCompleteList.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
            ?>
            <h1><?php echo html_encode($draft->getName()); ?></h1>
            <?php
            if (has_success()) {
                fade_element("out", "display-success-box");
                echo display_success();
            }

            if (has_notice()) {
                fade_element("out", "display-notice-box", 100, 15000);
                echo display_notice();
            }

            if (has_error()) {
                echo display_error();
            }
            ?>

            <h2 class="collapsable collapsed" title="Draft Information Section">Draft Information</h2>
            <div id="draft-information-section">
                <form action="<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>/drafts/?section=edit&draft_id=<?php echo $draft_id; ?>" method="post" id="editDraftForm" onSubmit="picklist_select('proxy_id')" class="form-horizontal">
                    <input type="hidden" name="step" value="2" />

                    <div class="control-group">
                        <label class="control-label form-required" for="draft_name">Draft Name</label>
                        <div class="controls">
                            <input type="text" id="draft_name" name="draft_name" value="<?php echo html_encode($draft->getName()); ?>"  maxlength="255" placeholder="Example: <?php echo date("Y"); ?> Draft Teaching Schedule" class="span10" />
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label form-nrequired" for="draft_description">Optional Description</label>
                        <div class="controls">
                            <textarea type="text" name="draft_description" id="draft_description" class="span10 expandable"><?php echo clean_input($draft->getDescription(), array("trim", "encode")); ?></textarea>
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label form-required" for="associated_proxy_ids">Draft Authors</label>
                        <div class="controls">
                            <input type="text" id="author_name" name="fullname" size="30" autocomplete="off" style="width: 203px" />
                            <?php
                            $ONLOAD[] = "author_list = new AutoCompleteList({ type: 'author', url: '". ENTRADA_RELATIVE ."/api/personnel.api.php?type=coordinator', remove_image: '". ENTRADA_RELATIVE ."/images/action-delete.gif'})";
                            ?>
                            <div class="autocomplete" id="author_name_auto_complete"></div>
                            <input type="hidden" id="associated_author" name="associated_proxy_ids" value="" />
                            <input type="button" class="btn" id="add_associated_author" value="Add" style="vertical-align: middle" />
                            <span class="content-small">(<strong>Example:</strong> <?php echo html_encode($_SESSION["details"]["lastname"].", ".$_SESSION["details"]["firstname"]); ?>)</span>
                            <ul id="author_list" class="menu" style="margin-top: 15px">
                                <?php
                                if (is_array($creators) && !empty($creators)) {
                                    foreach ($creators as $creator) {
                                        ?>
                                        <li class="community" id="author_<?php echo $creator->getProxyID(); ?>" style="cursor: move;"><?php echo $creator->getCreator()->getFullName(); ?><img src="<?php echo ENTRADA_URL; ?>/images/action-delete.gif" onclick="author_list.removeItem('<?php echo $creator->getProxyID(); ?>');" class="list-cancel-image" /></li>
                                        <?php
                                    }
                                }
                                ?>
                            </ul>
                            <input type="hidden" id="author_ref" name="author_ref" value="" />
                            <input type="hidden" id="author_id" name="author_id" value="" />
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label form-nrequired">Copying Learning Resources</label>
                        <div class="controls">
                            <div class="alert alert-info">
                                <strong>Did you know:</strong> When you copy learning events forward you can select what learning resources are copied along with each event?
                            </div>

                            <label class="checkbox">
                                <input type="checkbox" name="options[files]"<?php echo ($draft_options["files"] ? " checked=\"checked\"" : ""); ?> />
                                Copy all <strong>attached files</strong>.
                            </label>
                            <br />

                            <label class="checkbox">
                                <input type="checkbox" name="options[links]"<?php echo ($draft_options["links"] ? " checked=\"checked\"" : ""); ?> />
                                Copy all <strong>attached links</strong>.
                            </label>
                            <br />

                            <label class="checkbox">
                                <input type="checkbox" name="options[objectives]"<?php echo ($draft_options["objectives"] ? " checked=\"checked\"" : ""); ?> />
                                Copy all <strong>attached learning objectives</strong>.
                            </label>
                            <br />
                        
                            <label class="checkbox">
                                <input type="checkbox" name="options[keywords]"<?php echo ($draft_options["keywords"] ? " checked=\"checked\"" : ""); ?> />
                                Copy all <strong>attached keywords</strong>.
                            </label>
                            <br />

                            <label class="checkbox">
                                <input type="checkbox" name="options[topics]"<?php echo ($draft_options["topics"] ? " checked=\"checked\"" : ""); ?> />
                                Copy all <strong>attached hot topics</strong>.
                            </label>
                            <br />

                            <label class="checkbox">
                                <input type="checkbox" name="options[quizzes]"<?php echo ($draft_options["quizzes"] ? " checked=\"checked\"" : ""); ?> />
                                Copy all <strong>attached quizzes</strong>.
                            </label>
                        </div>
                    </div>

                    <input type="submit" class="btn btn-primary pull-right" value="Save Changes" />
                </form>
            </div>
            <div class="clear_both"></div>

            <?php
            $draft_events = Models_Event_Draft_Event::fetchAllByDraftID($draft_id);
            ?>
            <script type="text/javascript">
                jQuery(function($){
                    $("#import-button").on("click", function() {
                        $("#csv-form").submit();
                    });
                    $("#export-button").on("click", function() {
                        $("#my_export_options_form").submit();
                    });
                });
            </script>
            <style type="text/css">
                #draftEvents_length {padding:5px 4px 0 0;}
                #draftEvents_filter {-moz-border-radius:10px 10px 0px 0px;-webkit-border-top-left-radius: 10px;-webkit-border-top-right-radius: 10px;border-radius: 10px 10px 0px 0px; border: 1px solid #9D9D9D;border-bottom:none;background-color:#FAFAFA;font-size: 0.9em;padding:3px;}
                #draftEvents_paginate a {margin:2px 5px;}
                #import-csv {display:none;}
            </style>

            <h2 class="collapsable" title="Learning Events Section">Learning Events in <?php echo html_encode($draft->getName()); ?></h2>
            <div id="learning-events-section">
                <?php
                $JQUERY[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/jquery/jquery.dataTables.min.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
                if ($ENTRADA_ACL->amIAllowed("event", "create", false)) {
                    ?>
                    <div class="row-fluid space-below">
                        <?php if (!empty($draft_events)) : ?><a href="<?php echo ENTRADA_RELATIVE . "/admin/events/drafts?section=preview&draft_id=" . $draft_id; ?>" class="btn btn-small btn-default"><i class="fa fa-calendar"></i> <?php echo $translate->_("Calendar Preview"); ?></a><?php endif; ?>
                        <div class="pull-right">
                            <?php if (!empty($draft_events)) : ?><a href="#export-results" id="export-results-button" class="btn btn-small btn-default" data-toggle="modal"><i class="fa fa-download"></i> <?php echo $translate->_("Export CSV File"); ?></a><?php endif; ?>
                            <a href="#import-csv" class="btn btn-small btn-default" data-toggle="modal"><i class="fa fa-upload"></i> Import CSV File</a>
                            <a href="<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE; ?>?section=add&mode=draft&draft_id=<?php echo $draft_id; ?>" class="btn btn-small btn-success"><i class="icon-plus-sign icon-white"></i> Add New Event</a>
                        </div>
                    </div>
                    <?php
                }
                ?>
                <div class="modal hide fade" id="import-csv">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <h3>Import CSV</h3>
                    </div>
                    <div class="modal-body">
                        <?php echo display_notice("Upon uploading a CSV you will be prompted to confirm the association between column headings and their data points."); ?>
                        <form id="csv-form" action="<?php echo ENTRADA_URL; ?>/admin/events/drafts?section=csv-import&draft_id=<?php echo $draft_id; ?>" enctype="multipart/form-data" method="POST">
                            <input type="hidden" name="draft_id" value="<?php echo $draft_id; ?>" />
                            <input type="file" name="csv_file" />
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button data-dismiss="modal" class="btn">Close</button>
                        <a href="#" class="btn btn-primary" id="import-button">Import</a>
                    </div>
                </div>
                <div class="modal hide fade" id="export-results">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <h3>Export Results</h3>
                    </div>
                    <div class="modal-body">
                        <?php echo display_notice("Select the fields you would like to export by dragging them from the left to the right.  Remove fields from the Export by dragging them from the right to the left."); ?>
                        <div id="available-wrap">
                            <h3>Available Fields:</h3>
                            <div id="available_export_options_container" class="ui-widget-content">
                                <ul id="available_export_options">
                                </ul>
                            </div>
                        </div>
                        <div id="export-wrap">
                            <h3>Export Fields:</h3>
                            <div id="selected_export_options_container" class="ui-widget-content">
                                <ul id="selected_export_options">
                                    <?php
                                    if ($default_csv_headings) {
                                        foreach($default_csv_headings as $key => $value) {
                                            echo "<li class=\"ui-widget-content ui-state-default\" data-field=\"" . $key . "\"><span class=\"ui-icon ui-icon-arrowthick-2-n-s\"></span>" . $value . "</li>";
                                        }
                                    }
                                    ?>
                                </ul>
                            </div>
                        </div>
                        <form id="my_export_options_form" action="<?php echo ENTRADA_URL . "/admin/events/drafts/export"; ?>">
                            <input type="hidden" name="my_export_options" value="" />
                            <input type="hidden" name="draft_id" value="<?php echo $draft_id; ?>" />
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button data-dismiss="modal" class="btn">Close</button>
                        <a href="#" class="btn btn-primary" id="export-button">Export</a>
                    </div>
                </div>
                <?php
                $count_modified = 0;
                if (!empty($draft_events)) {
                    $total_events = count($draft_events);
                    ?>
                    <p class="muted text-center space-above">
                        <small>
                            There is currently <strong><?php echo $total_events; ?> Learning Events</strong> in this draft.
                        </small>
                    </p>
                    <form name="frmSelect" id="draft_events_form" action="<?php echo ENTRADA_URL; ?>/admin/events?section=delete&mode=draft&draft_id=<?php echo $draft_id; ?>" method="post">
                        <table class="table table-striped table-bordered" id="draftEvents" cellspacing="0" cellpadding="1" summary="List of Events" style="margin-bottom:5px;">
                            <colgroup>
                                <col class="modified" />
                                <col class="date-smallest" />
                                <col class="accesses" />
                                <col class="general" />
                                <col class="title" />
                            </colgroup>
                            <thead>
                            <tr>
                                <th class="modified" width="5%"><input type="checkbox" id="check_all" /></th>
                                <th class="date-smallest" width="12%">Date</th>
                                <th class="accesses" width="7%">Time</th>
                                <th class="general">Duration</th>
                                <th class="title">Event Title</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            foreach ($draft_events as $draft_event) {
                                $url = "";
                                $accessible = true;

                                $url = ENTRADA_URL."/admin/events?section=edit&mode=draft&id=".$draft_event->getDeventID();

                                if ((($draft_event->getReleaseDate()) && ($draft_event->getReleaseDate() > time())) || (($draft_event->getReleaseUntil()) && ($draft_event->getReleaseUntil() < time()))) {
                                    $accessible = false;
                                }

                                echo "<tr id=\"event-".$draft_event->getEventID()."\" rel=\"".$draft_event->getDeventID()."\" class=\"event".((!$url) ? " np" : ((!$accessible) ? " na" : ""))."\">\n";
                                echo "	<td class=\"modified\"><input type=\"checkbox\" name=\"checked[]\" value=\"".$draft_event->getDeventID()."\" /></td>\n";
                                echo "	<td class=\"date-smallest\">".(($url) ? "<a href=\"".$url."\" title=\"Event Date\" class=\"date\">" : "").date("Y-m-d", $draft_event->getEventStart()).(($url) ? "</a>" : "")."</td>\n";
                                echo "	<td class=\"accesses\">".(($url) ? "<a href=\"".$url."\" title=\"Event Time\" class=\"time\">" : "").date("H:i", $draft_event->getEventStart()).(($url) ? "</a>" : "")."</td>\n";
                                echo "	<td class=\"general\">".(($url) ? "<a href=\"".$url."\" title=\"Duration\">" : "").$draft_event->getEventDuration().(($url) ? " minutes</a>" : "")."</td>\n";
                                echo "	<td class=\"title\">".(($url) ? "<a href=\"".$url."\" title=\"Event Title: ".html_encode($draft_event->getEventTitle())."\" class=\"title\">" : "").html_encode($draft_event->getEventTitle()).(($url) ? "</a>" : "")."</td>\n";
                                echo "</tr>\n";
                            }
                            ?>
                            </tbody>
                        </table>
                        <table width="100%">
                            <tr>
                                <td></td>
                                <td style="padding-top: 10px">
                                    <?php
                                    if ($ENTRADA_ACL->amIAllowed("event", "delete", false)) {
                                        ?>
                                        <input type="button" class="btn btn-danger" onclick="document.frmSelect.submit()" value="Delete Selected" />
                                    <?php
                                    }
                                    ?>
                                </td>
                                <td style="padding-top: 10px; text-align: right">
                                    <?php
                                    if ($ENTRADA_ACL->amIAllowed("event", "delete", false)) {
                                        ?>
                                        <input class="btn btn-primary" type="button" value="Publish Draft" onclick="window.location='<?php echo ENTRADA_URL . "/admin/events/drafts?section=status&action=approve&draft_id=".$draft_id; ?>';" />
                                    <?php
                                    }
                                    ?>
                                </td>
                            </tr>
                        </table>
                    </form>
                    <script>
                        jQuery("#check_all").on("click", function() {
                            jQuery("#draft_events_form input[type=checkbox]").prop("checked", this.checked);
                        });
                    </script>

                    <script type="text/javascript">
                        jQuery(document).ready(function($){
                            var default_items = {};
                            var additional_items = {};
                            <?php
                            echo "default_items = " . json_encode((object) $default_csv_headings) . ";";
                            echo "additional_items = " . json_encode((object) $additional_csv_headings) . ";";
                            ?>

                            $("ul#selected_export_options > li").sortable();

                            for (var key in additional_items) {
                                var item = $( "<li></li>" ).text( additional_items[key] ).addClass("ui-widget-content ui-state-default draggable");
                                item.attr("data-field", key);
                                item.appendTo( $("ul#available_export_options") );
                            }

                            var my_export_options = default_items;
                            $('input[name=my_export_options]').val(JSON.stringify(my_export_options));

                            $(".draggable").draggable({
                                revert:"invalid"
                            });
                        });
                    </script>

                    <style type="text/css">
                        #available-wrap {
                            float:left;
                            width:49%;
                        }

                        #export-wrap {
                            float:right;
                            width:49%;
                        }

                        #available_export_options_container {
                            border:none;
                            float: left;
                            width: 95%;
                            padding: 0.5em;
                        }

                        #selected_export_options_container {
                            border:none;
                            float: right;
                            width: 95%;
                            padding: 0.5em;
                        }

                        li.ui-widget-content {
                            width: 50%;
                        }

                        #selected_export_options, #available_export_options {
                            list-style-type: none;
                            margin: 0;
                            padding: 0;
                            width: 100%;
                        }

                        #selected_export_options li, #available_export_options li {
                            margin: 0 3px 3px 3px;
                            padding: 0.4em;
                            padding-left: 1.5em;
                            font-size: 1em;
                            height: 13px;
                            cursor: move;
                            width: 89%;
                        }

                        #available_export_options li {
                            padding-left: 0.5em; width: 94%
                        }

                        #selected_export_options li span {
                            position: absolute; margin-left: -1.3em;
                        }
                    </style>
                    <?php
                } else {
                    ?>
                    <div class="alert alert-info">
                        <strong>There are currently no Learning Events in this draft.</strong>
                        <p>To add Learning Events you can click the <strong>Import CSV</strong> or <strong>Add New Event</strong> buttons to begin.</p>
                    </div>
                    <?php
                }
                ?>
            </div>
            <?php
        }
	} else {
		add_error("This draft has been approved and can no longer be edited.");
		echo display_error();
	}
}
