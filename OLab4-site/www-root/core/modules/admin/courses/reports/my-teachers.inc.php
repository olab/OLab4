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
 * To Do: create this report.
 *
 * @author Organisation: Queen's University
 * @author Unit: MEdTech
 * @author Developer: Don Zuiker <don.zuiker@queensu.ca>
 * @author Developer: Josh Dillon <jdillon@qmed.ca>
 * @copyright Copyright 2013 Queen's University. All Rights Reserved.
 *
*/

if (!defined("IN_COURSE_REPORTS")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed(new CourseContentResource($COURSE_ID, $ENTRADA_USER->getActiveOrganisation(), true), "update")) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$GROUP."] and role [".$ROLE."] does not have access to this module [".$MODULE."]");
} else {
	if (isset($_POST["ajax"]) && $_POST["ajax"] == "ajax") {
		ob_clear_open_buffers();
		$clean_emails = array();
        
        if (isset($_POST["method"]) && $tmp_input = clean_input($_POST["method"], array("trim", "striptags"))) {
            $method = $tmp_input;
        }
        
        if (isset($method)) {
            if (isset($_POST["teacher_email"]) && is_array($_POST["teacher_email"])) {
                $clean_emails = false;
                foreach ($_POST["teacher_email"] as $email) {
                    if ($tmp_input = clean_input($email, array("trim"))) {
                        $clean_emails[] = $tmp_input;
                    }
                }
            } else {
                echo json_encode(array("status" => "error", "data" => array("You must select at least one teacher to email from the generated list.")));
            }
            switch ($method) {
                case "mail" :
                    if ($clean_emails) {
                        echo json_encode(array("status" => "success", "data" => implode(",", $clean_emails)));
                    } else {
                        echo json_encode(array("status" => "error", "data" => array("An error occured while attempting to get teacher email addresses.")));
                    }
                break;
                case "list" :
                    if ($clean_emails) {
                        echo json_encode(array("status" => "success", "data" => implode(";", $clean_emails)));
                    } else {
                        echo json_encode(array("status" => "error", "data" => array("An error occured while attempting to get teacher email addresses.")));
                    }
                break;
            }
        } else {
            echo json_encode(array("status" => "error", "data" => array("No method provided.")));
        }
        exit;
	}
    
    $course = Models_Course::get($COURSE_ID);
    
    if ($course) {
        
        courses_subnavigation($course->toArray(), "reports");
        
        $original_preferences = preferences_load("courses");
	
        if (isset($original_preferences["teacher_report_start"]) && isset($original_preferences["teacher_report_finish"])) {
            $PROCESSED["start_date"] = (int) $original_preferences["teacher_report_start"];
            $PROCESSED["finish_date"] = (int) $original_preferences["teacher_report_finish"];
            
            $teachers = $course->getTeachersByDates($PROCESSED["start_date"], $PROCESSED["finish_date"]);
            if (!$teachers) {
                add_notice("No Teachers found between " . date("Y-m-d", $PROCESSED["start_date"]) . " and " . date("Y-m-d", $PROCESSED["finish_date"])."");
            }
        }
        
        //Error checking
        switch ($STEP) {
            case 2 :
                if (isset($_POST["start_date"])) {
                    $PROCESSED["start_date"] = validate_calendar("Start Date", "start", false);
                }

                if (isset($_POST["finish_date"])) {
                    $PROCESSED["finish_date"] = validate_calendar("finish Date", "finish", false);
                }

                if (!$ERROR) {
                    if ($PROCESSED["start_date"] >= $PROCESSED["finish_date"]) {
                        add_error("The<strong> Start Date</strong> must come before the <strong>Finish Date</strong>.");
                    }
                }

                if (!$ERROR) {
                    $teachers = $course->getTeachersByDates($PROCESSED["start_date"], $PROCESSED["finish_date"]);
                    if (!$teachers) {
                        add_notice("No Teachers found between " . date("Y-m-d", $PROCESSED["start_date"]) . " and " . date("Y-m-d", $PROCESSED["finish_date"])."");
                    }  else {
                        if (has_notice()) {
                            clear_notice();
                        }
                    }

                    $_SESSION[APPLICATION_IDENTIFIER]["courses"]["teacher_report_start"] = $PROCESSED["start_date"];
                    $_SESSION[APPLICATION_IDENTIFIER]["courses"]["teacher_report_finish"] = $PROCESSED["finish_date"];
                    preferences_update("courses", $original_preferences);
                }
            break;
        }

        //Display content
        if ($ERROR) {
            echo display_error();
        }
        if ($NOTICE) {
            echo display_notice();
        }
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function() {

                var course_id = "<?php echo $COURSE_ID ?>";

                jQuery("#email_modal_close").on("click", function (e) {
                    e.preventDefault();
                    jQuery("#email-modal").modal("hide");
                })

                jQuery("#teacher-emails").on("click", function () {
                    jQuery("#teacher-emails").select();
                });

                if (jQuery("#teacher-emails").length) {
                    jQuery("#teacher-emails").select();
                }
                jQuery("#start_row a img, #finish_row a img ").css("vertical-align", "top").css("margin-top", "4px");
                
                jQuery("#generate_mailto").on("click", function () {
                    jQuery.ajax({
                        url: jQuery("#teacher_list_form").attr("action"),
                        data: "ajax=ajax&method=mail&course_id=" + course_id + "&" + jQuery("#teacher_list_form").serialize(),
                        type: "POST",
                        success: function(data) {
                            var response =  JSON.parse(data);
                            if (response.status == "success") {
                                if (jQuery(".alert-error").length) {
                                    jQuery(".alert-error").remove();
                                }
                                window.location.href = "mailto:"+ response.data;
                            } else {
                                display_error(response.data, "#msg");
                            }
                        }
                    });
                });
                
                jQuery("#email_list").on("click", function () {
                    jQuery.ajax({
                        url: jQuery("#teacher_list_form").attr("action"),
                        data: "ajax=ajax&method=list&course_id=" + course_id + "&" + jQuery("#teacher_list_form").serialize(),
                        type: "POST",
                        success: function(data) {
                            var response =  JSON.parse(data);
                            if (response.status == "success") {
                                if (jQuery(".alert-error").length) {
                                    jQuery(".alert-error").remove();
                                }
                                jQuery("#teacher-emails").val(response.data);
                                jQuery("#email-modal").modal("show");
                            } else {
                                display_error(response.data, "#msg");
                            }
                        }
                    });
                });
            });
        </script>
        <div id="msg"></div>
        <h1>My Teachers</h1>
        <h2>Teacher Search Options</h2>
        <form method="post" id="teacher_list_form" action="<?php echo ENTRADA_URL . "/admin/courses/reports?section=my-teachers&id=" . $COURSE_ID .replace_query(array("step" => 2)); ?>">
            <div class="row-fluid">
                <div class="control-group span6">
                    <table>
                        <?php echo generate_calendar("start", "Start Date", true, ((isset($PROCESSED["start_date"])) ? $PROCESSED["start_date"] : strtotime("1 September")), false, false, false, false, false); ?>
                        <?php echo generate_calendar("finish", "Finish Date", true, ((isset($PROCESSED["finish_date"])) ? $PROCESSED["finish_date"] : strtotime("31 August +1 year")), false, false, false, false, false); ?>
                    </table>
                </div>
                <div class="span6">
                    <input type="submit" value="Generate Teacher List" class="btn btn-primary pull-right" />
                </div>
            </div>
            <table class="table table-striped table-bordered" id="notice-list">
                <thead>
                    <tr>
                        <th width="5%"></th>
                        <th width="30%">First Name</th>
                        <th width="30%">Last Name</th>
                        <th width="35%">Email Address</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $teacher_emails = "";
                if ($teachers) {
                    foreach ($teachers as $teacher) { 
                        $teacher_emails .= $teacher->getEmail() . "; ";
                        ?>
                        <tr>
                            <td><input type="checkbox" checked="checked" name="teacher_email[]" value="<?php echo $teacher->getEmail(); ?>" /></td>
                            <td><?php echo $teacher->getFirstname(); ?></td>
                            <td><?php echo $teacher->getLastname(); ?></td>
                            <td><?php echo $teacher->getEmail(); ?></td>
                        </tr>
                    <?php	
                    }			
                } else { ?>
                    <tr>
                        <td colspan="4"><?php echo "There currently are no teachers to display. To generate a list of teachers to email use the Teacher Search Options above."; ?></td>
                    </tr>
                <?php	
                } 
                ?>
                </tbody>
            </table>
        </form>
        <div class="row-fluid space-below">
            <a href="<?php echo ENTRADA_URL . "/admin/courses/reports?id=" . $course->getID(); ?>" class="btn pull-left">Cancel</a>
            <?php
            if ($teachers) { ?>
                <div class="btn-group pull-right">
                    <a href="#" id="generate_mailto" class="btn btn-success"><i class="icon-envelope icon-white"></i> Mail Selected Teachers</a>
                    <button class="btn btn-success dropdown-toggle" data-toggle="dropdown">
                        <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a href="#" id="email_list">Copy Teacher Email's to clipboard</a></li>
                    </ul>
                </div>
            <?php
            }
            ?>
        </div>
        <div id="email-modal" class="modal hide fade">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h3>Copy Teacher email addresses to clipboard</h3>
            </div>
            <div class="modal-body">
            <?php
            if ($teacher_emails) { ?>
                <textarea rows="5" class="span12" id="teacher-emails"><?php echo rtrim(trim($teacher_emails), ";"); ?></textarea>
                <p class="muted"><strong>Tip: </strong> To copy all teacher email addresses to the clipboard, right click on the list and select copy.</p>
            <?php    
            } else { ?>
                <div class="alert">
                    No Teacher email addresses found.
                </div>
            <?php    
            }
            ?>
            </div>
            <a href="#" id="email_modal_close" class="btn pull-right">Close</a>
        </div>
    <?php
    } else { ?>
        <div class="alert">
            Invalid course ID provided.
        </div>
    <?php    
    }
}
?>