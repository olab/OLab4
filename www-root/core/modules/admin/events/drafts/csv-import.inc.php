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
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

ini_set('auto_detect_line_endings', true);

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_EVENTS"))) {
	exit;
} else if ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("event", "create", false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");
	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	

    echo "<h1>Draft Event Import</h1>";

    $csv_headings = array(
        "original_event"            => array("title" => "Original Event"),
        "parent_event"              => array("title" => "Parent Event",          "required" => true),
        "course_code"               => array("title" => "Course Code",           "required" => true),
        "course_name"               => array("title" => "Course Name"),
        "term"                      => array("title" => "Term"),
        "date"                      => array("title" => "Date"),
        "start_time"                => array("title" => "Start Time",            "required" => true),
        "total_duration"            => array("title" => "Total Duration"),
        "event_type_durations"      => array("title" => "Event Type Durations",  "required" => true),
        "event_types"               => array("title" => "Event Types",           "required" => true),
        "event_title"               => array("title" => "Event Title",           "required" => true),
        "event_description"         => array("title" => "Event Description"),
        "location"                  => array("title" => "Location"),
        "location_room"                  => array("title" => "Location Room"),
        "audience_groups"           => array("title" => "Audience (Groups)"),
        "audience_cohorts"           => array("title" => "Audience (Cohorts)"),
        "audience_students"         => array("title" => "Audience (Students)"),
        "teacher_names"             => array("title" => "Teacher Names"),
        "teacher_numbers"           => array("title" => "Teacher Numbers"),
        "objectives_release_date"   => array("title" => "Objective Release Date"),
        "event_tutors"              => array("title" => "Event Tutors"),
        "recurring_event"           => array("title" => "Recurring Event")
    );
    
	$draft_id = (isset($_GET["draft_id"]) ? (int) $_GET["draft_id"] : 0);
    if ($draft_id) {
        
        switch ($STEP) {
            case 2 :
                
                if (isset($_POST["csv"]) && $tmp_input = clean_input($_POST["csv"], "alphanumeric")) {
                    $PROCESSED["csv_filename"] = $tmp_input;
                }
                
                if (isset($_POST["mapped_headings"]) && is_array($_POST["mapped_headings"])) {
                    foreach ($_POST["mapped_headings"] as $col => $heading) {
                        $PROCESSED["col_map"][(int) $col] = clean_input($heading, array("trim", "striptags"));
                    }
                }
                
                if (file_exists(CACHE_DIRECTORY."/".$PROCESSED["csv_filename"])) {
                    $csv_importer = new Entrada_Event_Draft_CsvImporter($draft_id, $ENTRADA_USER->getActiveId(), $PROCESSED["col_map"]);
                    $csv_importer->importCsv(CACHE_DIRECTORY."/".$PROCESSED["csv_filename"]);
                    
                    $csv_errors = $csv_importer->getErrors();
                    if ($csv_errors) {
                        $err_msg  = "The following errors occured while attempting to import draft learning events. Please review the errors below and correct them in your file. Once correct, please try again.<br /><br />";
                        $err_msg .= "<pre>";
                        foreach ($csv_errors as $rowid => $error) {
                            foreach ($error as $msg) {
                                $err_msg .= "Row ".$rowid.": ".html_encode($msg)."\n";
                            }
                        }
                        $err_msg .= "</pre>";
                        $err_msg .= "<br /><br />";
                        $err_msg .= "Please <a href=\"".ENTRADA_RELATIVE."/admin/events/drafts?section=edit&draft_id=".$draft_id."\">click here</a> to return to the draft.";

                        add_error($err_msg);
                        echo display_error();
                    } else {
                        if(!DEMO_MODE) {
                            $csv_success = $csv_importer->getSuccess();

                            add_success("Successfully imported <strong>".count($csv_success)."</strong> events from <strong>".html_encode($_FILES["csv_file"]["name"])."</strong><br /><br />You will now be redirected to the edit draft page; this will happen automatically in 5 seconds or <a href=\"".ENTRADA_URL."/admin/events/drafts?section=edit&draft_id=".$draft_id."\">click here</a> to continue.");
                            echo display_success();

                            $ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/events/drafts?section=edit&draft_id=".$draft_id."\\'', 5000)";

                            application_log("success", "Proxy_id [".$ENTRADA_USER->getActiveId()."] successfully imported ".count($csv_success)." events into draft_id [".$draft_id."].");
                        } else {
                            $csv_success = $csv_importer->getSuccess();

                            add_success("Entrada is in demo mode therefore the Entrada demo csv file was used for this import instead of <strong>".html_encode($_FILES["csv_file"]["name"])."</strong>.<br /><br />You will now be redirected to the edit draft page; this will happen automatically in 5 seconds or <a href=\"".ENTRADA_URL."/admin/events/drafts?section=edit&draft_id=".$draft_id."\">click here</a> to continue.");
                            echo display_success();

                            $ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/events/drafts?section=edit&draft_id=".$draft_id."\\'', 5000)";

                            application_log("success", "Proxy_id [".$ENTRADA_USER->getActiveId()."] successfully imported ".count($csv_success)." events into draft_id [".$draft_id."].");
                        }
                    }
                } else {
                    application_log("error", "Unable to find expected file [".CACHE_DIRECTORY."/".$PROCESSED["csv_filename"]."]");
                    add_error("An error ocurred while attempting to upload the CSV file. An administrator has been informed, please try again later.");
                }
                
            break;
            case 1 :
            default :
                $draft = Models_Event_Draft::fetchRowByID($draft_id);
                $draft_creator = Models_Event_Draft_Creator::fetchRowByDraftIDProxyID($draft_id, $proxy_id);
                if ($draft && $draft->getStatus() == "open" && $draft_creator) {

                    $unmapped_fields = $csv_headings;

                    if (isset($_FILES["csv_file"])) {
                        switch ($_FILES["csv_file"]["error"]) {
                            case 1 :
                            case 2 :
                            case 3 :
                                add_error("The file that uploaded did not complete the upload process or was interupted. Please <a href=\"".ENTRADA_RELATIVE."/admin/events/drafts?section=edit&draft_id=".$draft_id."\">click here</a> and try your CSV again.");
                            break;
                            case 4 :
                                add_error("You did not select a file on your computer to upload. Please <a href=\"".ENTRADA_RELATIVE."/admin/events/drafts?section=edit&draft_id=".$draft_id."\">click here</a> and try your CSV import again.");
                            break;
                            case 6 :
                            case 7 :
                                add_error("Unable to store the new file on the server, please <a href=\"".ENTRADA_RELATIVE."/admin/events/drafts?section=edit&draft_id=".$draft_id."\">click here</a> and try again.");
                            break;
                            default :
                                continue;
                            break;
                        }
                    } else {
                        add_error("To upload a file to this event you must select a file to upload from your computer.");
                    }

                    if (!has_error()) {
                        ?>
                        <style type="text/css">
                            #unmapped-fields {
                                margin-left:0px;
                                padding-bottom:14px;
                            }
                            ul.nostyle {
                                list-style:none;
                                margin:0px;
                                padding:0px;
                            }
                            .drop-target {
                                background:#D9EDF7!important;
                                border-top:1px dashed #C8DCE6 !important;
                                border-right:1px dashed #C8DCE6  !important;
                                border-bottom:1px dashed #C8DCE6  !important;
                                border-left:1px dashed #C8DCE6  !important;
                            }
                            .draggable-title {
                                cursor: pointer;
                                margin-right:4px;
                                margin-bottom:5px;
                            }
                        </style>
                        <?php
                        echo display_generic("Please use this interface to map the draft event columns to the appropriate CSV columns. We will try to automatically map the headings to the correct columns via the titles in the first row, but if there are no titles this will need to be done manually.");
                        echo "<h4>Unmapped Fields</h4>";
                        if (($handle = fopen($_FILES["csv_file"]["tmp_name"], "r")) !== FALSE) {
                            $tmp_name = explode("/", $_FILES["csv_file"]["tmp_name"]);
                            $new_filename = md5(end($tmp_name));
                            
//                            add some error handling here
                            copy($_FILES["csv_file"]["tmp_name"], CACHE_DIRECTORY."/".$new_filename);
                            
                            // we just want the headings
                            $i = 0;
                            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE && $i < 5) {
                                if ($i === 0) {
                                    $j = 0;
                                    foreach ($data as $d) {
                                        $mapped = false;
                                        $title = "";
                                        $key = strtolower(str_replace(" ", "_", clean_input($d, "boolops")));
                                        if (isset($csv_headings[$key])){
                                            $mapped = true;
                                            $title = $csv_headings[$key]["title"];
                                            unset($unmapped_fields[$key]);
                                        }

                                        $output[] = "<tr class=\"".($mapped === true && $csv_headings[$key]["required"] === true ? "success" : "")."\">\n";
                                        $output[] = "<td style=\"text-align:center!important;\">".($mapped === true ? "<a href=\"#\" class=\"remove\"><i class=\"icon-remove-sign\"></i></a>" : "")."</td>\n";
                                        $output[] = "<td class=\"".($mapped === false ? "droppable-title-container" : "")."\">".$title."<input type=\"hidden\" name=\"mapped_headings[".$j."]\" value=\"".$key."\" /></td>\n";
                                        $output[] = "<td><strong>".$d."</strong></td>\n";
                                        $output[] = "</tr>\n";

                                        $j++;
                                    }  
                                }
                                $output_data = array();
                                foreach ($data as $key => $field) {
                                    $clean_field = str_replace("'", "&#39;", $field);
                                    $output_data[$key] = $clean_field;
                                }
                                $json_rows[] = $output_data;
                                $i++;
                            }

                            fclose($handle);

                            if (!empty($unmapped_fields)) {
                                echo "<div class=\"space-below row well\" id=\"unmapped-fields\">";
                                foreach ($unmapped_fields as $field_name => $field) {
                                    echo "<span data-field-name=\"".$field_name."\" class=\"draggable-title label pull-left ".($field["required"] === true ? "label-important" : "")."\"><i class=\"icon-move icon-white\"></i> <span class=\"label-text\">".$field["title"]."</span></span>";
                                }
                                echo "</div>";
                            }

                            ?>

                            <script type="text/javascript">
                                var json_rows = <?php echo json_encode($json_rows); ?>;
                                jQuery(function($) {

                                    var current_row = 0;
                                    var max_row = (json_rows.length - 1);

                                    $(".row-nav").on("click", function(e) {
                                        var button = $(this);

                                        if (button.children("i").hasClass("icon-arrow-right")) {
                                            if (current_row < max_row) {
                                                current_row++;
                                                updateRows(json_rows[current_row]);
                                            }
                                        } else {
                                            if (current_row > 0) {
                                                current_row--;
                                                updateRows(json_rows[current_row]);
                                            }
                                        }

                                        e.preventDefault();
                                    });

                                    function updateRows(jsonData) {
                                        for (var i = 0; i < jsonData.length; i++) {
                                            $(".csv-map tbody tr").eq(i).children("td").eq(2).html("<strong>" + jsonData[i] + "</strong>");
                                        }
                                    }

                                    $(".draggable-title").draggable({
                                        start: function (event, ui) {
                                            $(".droppable-title-container").addClass("drop-target");
                                        },
                                        stop: function (event, ui) {
                                            $(".droppable-title-container").removeClass("drop-target");
                                        },
                                        revert: true
                                    });

                                    $(".droppable-title-container").droppable({
                                        drop: function (event, ui) {
                                            var drop_target = $(this);
                                            handleDrop(drop_target, event, ui);
                                        }
                                    });

                                    $(".csv-map").on("click", "a.remove", function(e) {
                                        var parent = $(this).closest("tr");
                                        var draggable_title = $(document.createElement("span"));
                                        var field_name = parent.children("td").eq(1).children("input[type=hidden]").val();
                                        parent.children("td").eq(1).children("input[type=hidden]").remove();
                                        draggable_title.addClass("label draggable-title pull-left " + (parent.hasClass("success") ? "label-important" : "")).attr("data-field-name", field_name).html("<i class=\"icon-move icon-white\"></i> <span class=\"label-text\">" + parent.children("td").eq(1).html() + "</span>").draggable({
                                            start: function (event, ui) {
                                                $(".droppable-title-container").addClass("drop-target");
                                            },
                                            stop: function (event, ui) {
                                                $(".droppable-title-container").removeClass("drop-target");
                                            },
                                            revert: true
                                        });

                                        $("#unmapped-fields").append(draggable_title);
                                        parent.removeClass("success");
                                        parent.children("td").eq(0).html("");
                                        parent.children("td").eq(1).html("").addClass("droppable-title-container").droppable({
                                            drop: function (event, ui) {
                                                var drop_target = $(this);
                                                handleDrop(drop_target, event, ui);
                                            }
                                        });
                                        e.preventDefault();
                                    });

                                    function handleDrop(drop_target, event, ui) {
                                        $(".droppable-title-container").removeClass("drop-target");
                                        drop_target.html(ui.draggable.children(".label-text").html()).removeClass("droppable-title-container").droppable("destroy");

                                        var input = $(document.createElement("input"));
                                        input.attr({type : 'hidden', value : ui.draggable.data("field-name"), name : 'mapped_headings['+drop_target.closest("tbody tr").index()+']'})

                                        drop_target.append(input);

                                        if (ui.draggable.hasClass("label-important")) {
                                            drop_target.closest("tr").addClass("success");
                                        }

                                        var remove_link = $(document.createElement("a"));
                                        remove_link.addClass("remove").attr("href", "#");
                                        var remove_icon = $(document.createElement("i"));
                                        remove_icon.addClass("icon-remove-sign").wrap($(document.createElement("a")));
                                        remove_link.append(remove_icon);
                                        drop_target.closest("tr").children("td").eq(0).append(remove_link);

                                        ui.draggable.remove();
                                    }
                                });
                            </script>
                            <form class="form" action="<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE . "/" . $SUBMODULE; ?>?section=csv-import&draft_id=<?php echo $draft_id; ?>" method="POST">
                                <input type="hidden" name="csv" value="<?php echo $new_filename; ?>" />
                                <input type="hidden" name="step" value="2" />
                                <table class="table table-striped table-bordered csv-map">
                                    <thead>
                                        <tr>
                                            <th width="6%"></th>
                                            <th width="47%">Mapped Field</th>
                                            <th width="47%">My CSV <div class="pull-right"><a href="#" class="row-nav"><i class="icon-arrow-left"></i></a><a href="#" class="row-nav"><i class="icon-arrow-right"></i></a></div></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php echo implode("", $output); ?>
                                    </tbody>
                                </table>
                                <div class="row-fluid">
                                    <a href="<?php echo ENTRADA_URL; ?>/admin/<?php echo $MODULE . "/" . $SUBMODULE; ?>?section=edit&draft_id=<?php echo $draft_id; ?>" class="btn">Back</a>
                                    <input class="btn btn-primary pull-right" type="Submit" value="Import" />
                                </div>
                            </form>
                            <?php
                        }
                    } else {
                        echo display_error();
                        $ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/events/drafts?section=edit&draft_id=".$draft_id."\\'', 5000)";
                    }
                } else {
                    add_error("We were unable to select the specified draft to import these events into. Please try again.");
                    echo display_error();

                    application_log("error", "Proxy_id [".$ENTRADA_USER->getActiveId()."] was unable to select the draft_id [".$draft_id."]. Database said: ".$db->ErrorMsg());
                }
            break;
        }
	} else {
		add_error("There was no draft id provided to import any events into.");
		echo display_error();

        application_log("error", "Proxy_id [".$ENTRADA_USER->getActiveId()."] did not provide a draft_id.");
	}
}