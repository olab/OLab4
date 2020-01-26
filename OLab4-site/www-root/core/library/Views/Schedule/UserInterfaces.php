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
 * @copyright Copyright 2011-2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Schedule_UserInterfaces extends Views_HTML {

    protected $view_data = array();

    public function __construct(Models_Schedule $schedule, $has_children = false) {
        $this->view_data = $schedule->toArray();
        $this->view_data["has_children"] = $has_children;
    }

    private function renderTitle($error) {
        $html = array();

        $html[] = "<div class=\"control-group ".($error ? "error" : "")."\">";
        $html[] = "<label class=\"control-label form-required\">Schedule Title</label>";
        $html[] = "<div class=\"controls\">";
        $html[] = "<input class=\"span10\" type=\"text\" id=\"title\" name=\"title\" value=\"".$this->view_data["title"]."\" />";
        $html[] = "</div>";
        $html[] = "</div>";

        return implode("\n", $html);
    }

    private function renderDescription($error) {
        $html = array();

        $html[] = "<div class=\"control-group ".($error ? "error" : "")."\">";
        $html[] = "<label class=\"control-label\">Schedule Description</label>";
        $html[] = "<div class=\"controls\">";
        $html[] = "<textarea class=\"span10\" type=\"text\" id=\"description\" name=\"description\">".$this->view_data["description"]."</textarea>";
        $html[] = "</div>";
        $html[] = "</div>";

        return implode("\n", $html);
    }

    private function renderCalendar($label, $name, $error) {
        $html = array();
        $html[] = "<div class=\"control-group ".($error ? "error" : "")."\">";
        $html[] = "<label class=\"control-label\" for=\"". str_replace("_", "-", $name) ."\">" . $label . "</label>";
        $html[] = "<div class=\"controls\">";
        $html[] = "<div class=\"input-append span12\">";
        $html[] = "    <input ".($this->view_data["cperiod_id"] && ($this->view_data["schedule_parent_id"] == "0" || is_null($this->view_data["schedule_parent_id"])) ? "readonly=\"readonly\" class=\"span3\"" : "class=\"datepicker span3\"")." id=\"" . str_replace("_", "-", $name) . "\" name=\"" . $name . "\" type=\"text\" value=\"" . ($this->view_data[$name] ? date("Y-m-d", $this->view_data[$name]) : "")."\" />";
        $html[] = "    <span class=\"add-on\"><i class=\"icon-calendar\"></i></span>";
        $html[] = "</div>";
        $html[] = "</div>";
        $html[] = "</div>";

        return implode("\n", $html);
    }

    private function renderStreamType($has_children) {
        $html = array();

        $block_types = Models_BlockType::fetchAllRecords();

        $html[] = "<div class=\"control-group\">";
        $html[] = "<label class=\"control-label form-required\">Block Type</label>";
        $html[] = "<div class=\"controls\">";

        if ($has_children) {
            $html[] = "<input type=\"hidden\" name=\"block_type_id\" value=\"" . $this->view_data["block_type_id"] . "\" />";
            $html[] = "<select disabled=\"disabled\">";
        } else {
            $html[] = "<select name=\"block_type_id\">";
        }

        foreach ($block_types as $block_type) {
            $html[] = "<option value=\"".$block_type->getID()."\" ".(isset($this->view_data["block_type_id"]) && $this->view_data["block_type_id"] == $block_type->getID() ? "selected=\"selected\"" : "").">".$block_type->getName()."</option>";
        }
        $html[] = "</select>";
        $html[] = "</div>";
        $html[] = "</div>";

        return implode("\n", $html);
    }

    private function renderBlockEndDay($has_children) {
        $html = array();
        if ($has_children == false) {
            $block_end_days = array("sunday", "monday", "tuesday", "wednesday", "thursday", "friday", "saturday");
            $this->view_data["block_end_day"] = isset($this->view_data["block_end_day"]) ? $this->view_data["block_end_day"] : "monday";
            $html[] = "<div class=\"control-group\">";
            $html[] = "<label class=\"control-label\">Block End Day</label>";
            $html[] = "<div class=\"controls\">";
            $html[] = "<select class=\"\" name=\"block_end_day\">";
            foreach ($block_end_days as $block_end_day) {
                $html[] = "<option value=\"".$block_end_day."\" ".($this->view_data["block_end_day"] == $block_end_day ? "selected=\"selected\"" : "").">".ucfirst($block_end_day)."</option>";
            }
            $html[] = "</select>";
            $html[] = "</div>";
            $html[] = "</div>";
        }
        return implode("\n", $html);
    }

    public function renderCourseSelector($course_id, $org_id, $readonly = false) {
        $html = array();

        $html[] = "<div class=\"control-group\">";
        $html[] = "<label class=\"control-label\">Course</label>";
        $html[] = "<div class=\"controls\">";
        if (!$readonly) {
            $html[] = "<select name=\"course_id\" id=\"course-id\">";
            $courses = Models_Course::fetchAllByOrg($org_id);
            if ($courses) {
                foreach ($courses as $course) {
                    $html[] = "<option value=\"" . $course->getID() . "\" " . ($course->getID() == $course_id ? "selected=\"selected\"" : "") . ">" . $course->getCourseCode() . " - " . $course->getCourseName() . "</option>";
                }
            }
            $html[] = "</select>";
        } else {
            $course = Models_Course::fetchRowByID($course_id);
            $html[] = "<input type=\"hidden\" name=\"course_id\" readonly=\"readonly\" value=\"".$course->getID()."\" />";
            $html[] = "<input type=\"text\" readonly=\"readonly\" value=\"".$course->getCourseCode() . " - " . $course->getCourseName() ."\" />";
        }
        $html[] = "</div>";
        $html[] = "</div>";

        return implode("\n", $html);
    }

    public function renderCode() {
        $html = array();
        if (!empty($this->view_data["code"])) {
            $course = Models_Course::fetchRowByID($this->view_data["course_id"]);
            $html[] = "<div class=\"control-group\">";
            $html[] = "<label class=\"control-label\">Code</label>";
            $html[] = "<div class=\"controls\">";
            $html[] = "<div class=\"input-prepend span12\">";
            $html[] = "    <span class=\"add-on\">".strtoupper($course->getCourseCode())."-</span>";
            $html[] = "    <input type=\"text\" value=\"" . $this->view_data["code"] . "\" name=\"code\" />";
            $html[] = "</div>";
            $html[] = "</div>";
            $html[] = "</div>";
        }
        return implode("\n", $html);
    }

    public function renderScheduleInformation($errorElements = array()) {
        global $ENTRADA_USER;
        $html = array();
        $html[] = $this->renderTitle(in_array("title", $errorElements));
        $html[] = $this->renderDescription(in_array("title", $errorElements));
        switch ($this->view_data["schedule_type"]) {
            case "organisation" :
            break;
            case "academic_year" :
            break;
            case "stream" :
                $html[] = $this->renderStreamType($this->view_data["has_children"]);
                $html[] = $this->renderBlockEndDay($this->view_data["has_children"]);
            break;
            case "block" :
            break;
            case "rotation_stream" :
                $html[] = $this->renderCourseSelector($this->view_data["course_id"], $ENTRADA_USER->getActiveOrganisation(), "readonly");
                $html[] = $this->renderCode();
            break;
        }
        $html[] = $this->renderCalendar("Start Date", "start_date", in_array("start_date", $errorElements));
        $html[] = $this->renderCalendar("End Date", "end_date", in_array("end_data", $errorElements));
        return implode("\n", $html);
    }

    public function renderScheduleTree() {
        $html = array();
        $html[] = "<ul>";
        $html[] = "<li><input class=\"schedule-id\" type=\"checkbox\" name=\"schedule_id[]\" value=\"".$this->view_data["schedule_id"]."\" />" . $this->view_data["title"];
        $schedule = new Models_Schedule($this->view_data);
        $children = $schedule->getChildren();
        if ($children) {
            foreach ($children as $child) {
                $self = new self($child);
                $html[] = $self->renderScheduleTree();
            }
        }
        $html[] = "</li>";
        $html[] = "</ul>";
        return implode("\n", $html);
    }

    private static function renderScheduleTable($block_types, $block_data, $schedule_type, $learner_data, $slot_type_id = "1", $draft_id = NULL) {
        global $translate, $MODULE;
        $longest_block_cell_count = 0;
        $longest_block_type_id = 0;

        // Determine the highest number of blocks possible from the provided block types.
        foreach ($block_types as $block_type) {
            if (!$longest_block_cell_count || $block_type->getNumberOfBlocks() < $longest_block_cell_count) {
                $longest_block_cell_count = $block_type->getNumberOfBlocks();
                $longest_block_type_id = $block_type->getID();
            }
        }
        if (!empty($learner_data["members"])) {

            global $ENTRADA_USER;
            // Aggregate all of the learner IDs and attempt to fetch and cache their photos.
            $cache = new Entrada_Utilities_Cache();
            $assessment_user = new Entrada_Utilities_AssessmentUser();
            $learners = array();
            foreach ($learner_data["members"] as $learner) {
                $learners[$learner["proxy_id"]] = array("id" => (string)$learner["proxy_id"]);
            }
            if (!empty($learners)) {
                $assessment_user->cacheUserCardPhotos($learners);
            }

            ?>
            <div class="table-outer-wrapper">
                <div class="table-wrapper">

                    <!-- Left Fixed Content -->
                    <div class="left-end-cap">
                        <div class="table-row header-row"> <!-- Header -->
                            <div class="table-cell">
                                <?php echo $translate->_("Learner"); ?>
                            </div>
                        </div>
                        <?php
                        if (!empty($learner_data["members"])) {
                            // Each learner will have a row in the parent table.
                            foreach ($learner_data["members"] as $learner) {
                                $name = explode(", ", $learner["name"]);
                                $learner_name = html_encode($name[1] . " " . $name[0]);

                                // Whether or not the learner has been scheduled into any blocks.
                                $scheduled = array_key_exists("slots", $learner) && !empty($learner["slots"]) ? true : false;

                                // Load user photo from the cache.
                                $image_data = $cache->loadCache($learner["proxy_id"]);
                                if ($image_data === false) {
                                    $image_data = $cache->loadCache("default_photo");
                                }
                                $mime_type = $image_data["mime_type"];
                                $encoded_image = $image_data["photo"];
                                $photo_src = "data:{$mime_type};base64,{$encoded_image}";
                                ?>
                                <div class="table-row learner-row"> <!-- Learner 1 -->
                                    <div class="table-cell <?php echo $scheduled ? "scheduled" : "unscheduled"; ?>">
                                        <img class="learner-cell-img pull-left" src="<?php echo $photo_src; ?>" width="40" height="40" />
                                        <div class="learner-cell-info pull-left">
                                            <a class="pull-left" href="<?php echo ENTRADA_URL . "/admin/" . $MODULE; ?>?section=learner-schedule-overview&proxy_id=<?php echo $learner["proxy_id"] . (!is_null($draft_id) ? "&draft_id=" . $draft_id : ""); ?>"><strong><?php echo $learner_name; ?></strong></a>
                                            <?php if ($learner["learner_level"]) { ?>
                                                <small class="pull-right"><?php echo $learner["learner_level"]; ?></small>
                                            <?php } ?>
                                            <div class="clearfix"></div>
                                            <span class="label"><?php echo $scheduled ? "Scheduled" : "Unscheduled"; ?></span>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                        }
                        ?>
                    </div>

                    <!-- Responsive Table Content -->
                    <div class="table-content">
                        <div class="table-row header-row"> <!-- Header -->
                            <!-- Block Header -->
                            <?php
                            // Construct the header dates based on a set of the "largest" block type.
                            $draft_end_date = false;

                            foreach ($block_data[$longest_block_type_id] as $order => $order_blocks) {
                                if ($order == @count($block_data[$longest_block_type_id])) {
                                    $draft_end_date = html_encode(date("Y-m-d", $order_blocks[0]["end_date"]));
                                }
                                ?>
                                <div class="table-cell date-header<?php echo(time() >= $order_blocks[0]["start_date"] && time() <= $order_blocks[0]["end_date"] ? " current-block-header" : ""); ?>">
                                    <span class="left-date"><?php echo html_encode(date("Y-m-d", $order_blocks[0]["start_date"])); ?></span>
                                    <span class="center-block-type"><?php echo html_encode($translate->_("Block ") . $order); ?></span>
                                    <span class="right-date"><?php echo html_encode(date("Y-m-d", $order_blocks[0]["end_date"])); ?></span>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                        <?php
                        // Each learner will have a row in the parent table.
                        foreach ($learner_data["members"] as $learner) {
                            $name = explode(", ", $learner["name"]);
                            $learner_name = html_encode($name[1] . " " . $name[0]);

                            $block_type_orders = array();
                            // We need to keep an array of ordinals for each block type.
                            foreach ($block_types as $block_type) {
                                $block_type_orders[$block_type->getID()] = 0;
                            }
                            ?>
                            <div class="table-row block-row"> <!-- Leaner Row -->
                                <?php
                                foreach ($block_data[$longest_block_type_id] as $order => $order_blocks) {

                                    $current_block = time() >= $order_blocks[0]["start_date"] && time() <= $order_blocks[0]["end_date"] ? true : false;

                                    // Headers and the following cell are styled to 300px ?>
                                    <div class="table-cell<?php echo ($current_block ? " current-block" : ""); ?>">

                                        <?php
                                        // Build a row for each block type.
                                        foreach ($block_types as $block_type) {

                                            $overall_cell_ctr = &$block_type_orders[$block_type->getID()];

                                            // Number of cells of this block type that can fit within the largest block type (the headers).
                                            $number_of_cells_within_largest = $block_type->getNumberOfBlocks() / $longest_block_cell_count;
                                            $number_of_cells_within_largest_ctr = 0;

                                            // 300px is the width as defined in the CSS file. This will need to be adjusted if it is changed.
                                            $width_percentage = 100 / $number_of_cells_within_largest;
                                            ?>

                                            <div class="inner-table">
                                                <?php

                                                while ($overall_cell_ctr < $block_type->getNumberOfBlocks() && $number_of_cells_within_largest_ctr < $number_of_cells_within_largest) {

                                                    $order = $overall_cell_ctr + 1;
                                                    $occupied_slot = false;
                                                    $current_slots = false;

                                                    // Check for collisions with other previously scheduled blocks/slots across all rotations.
                                                    if (array_key_exists("slots", $learner) && array_key_exists($order, $block_data[$block_type->getID()])) {
                                                        $schedules = $block_data[$block_type->getID()][$order];
                                                        foreach ($schedules as $schedule) {
                                                            foreach ($learner["slots"] as $slot_block_types) {
                                                                foreach ($slot_block_types as $slot_orders) {
                                                                    foreach ($slot_orders as $slot) {
                                                                        if (($slot["start_date"] >= $schedule["start_date"] && $slot["start_date"] <= $schedule["end_date"]) ||
                                                                            ($slot["end_date"] >= $schedule["start_date"] && $slot["end_date"] <= $schedule["end_date"]) ||
                                                                            ($slot["start_date"] <= $schedule["start_date"] && $slot["end_date"] >= $schedule["end_date"])
                                                                        ) {
                                                                            $occupied_slot = true;
                                                                            if ($slot["schedule_id"] == $schedule["schedule_id"]) {
                                                                                $current_slots[] = $slot;
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }

                                                    // Block state CSS class logic.
                                                    $class = "";
                                                    if ($slot_type_id == 1) {
                                                        $class = "learner-slot";
                                                        if ($occupied_slot) {
                                                            if ($current_slots) {
                                                                $class .= " currently-booked";
                                                            }
                                                        }
                                                    } elseif ($slot_type_id == 2) {
                                                        if ($occupied_slot) {
                                                            if ($current_slots) {
                                                                $class = "remove-learner-cell currently-booked";
                                                            }
                                                        }
                                                    }
                                                    if ($current_block) {
                                                        $class .= " current-block";
                                                    }
                                                    ?>
                                                    <div class="inner-table-cell <?php echo $class; ?>"
                                                         style="width: <?php echo $width_percentage; ?>%"
                                                         data-block-type-id="<?php echo $block_type->getID(); ?>"
                                                         data-number-of-blocks="<?php echo $block_type->getNumberOfBlocks(); ?>"
                                                         data-block-order="<?php echo $order; ?>"
                                                         data-proxy-id="<?php echo $learner["proxy_id"]; ?>"
                                                         data-name="<?php echo $learner_name; ?>"
                                                         data-draft-end-date="<?php echo $draft_end_date; ?>"
                                                    >
                                                        <?php if ($block_type->getID() == $longest_block_type_id) { ?>
                                                            <span class="background-block-number"><?php echo $order; ?></span>
                                                        <?php }
                                                        if ($current_slots) {
                                                            usort($current_slots, array("Views_Schedule_UserInterfaces", "sortRotationCodeByTitle"));
                                                            foreach ($current_slots as $key => $slot) {
                                                                ?>
                                                                <span class="slot-code"><?php echo html_encode($slot["code"]); ?></span>
                                                                <span class="fa fa-pencil"></span>
                                                                <?php
                                                                if ($key < (count($current_slots) - 1)) {
                                                                    echo " / ";
                                                                }
                                                            }
                                                        } else {
                                                            ?>
                                                            <span class="fa fa-plus-circle"></span>
                                                            <?php
                                                        }
                                                        ?>
                                                    </div>
                                                    <?php
                                                    $overall_cell_ctr++;
                                                    $number_of_cells_within_largest_ctr++;
                                                } ?>
                                            </div>
                                        <?php } ?>
                                    </div>
                                <?php } ?>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
            <?php
        } else {
            if ($schedule_type == "on-service") {
                ?>
                <div class="alert alert-info"><?php echo $translate->_("The learners in this course have not been added to the rotation schedule."); ?></div>
                <?php
            } else {
                ?>
                <div class="alert"><?php echo $translate->_("All learners in this course have been added to the rotation schedule."); ?></div>
                <?php
            }
        }
    }

    public static function renderScheduleTables($schedule_table_data, $draft_id = NULL) {
        global $translate;
        
        if ($schedule_table_data) {
            $on_service_learners = $schedule_table_data["on_service_audience"];
            if ($schedule_table_data["unscheduled_on_service_audience"]) {
                $on_service_learners = array_merge($on_service_learners, $schedule_table_data["unscheduled_on_service_audience"]);
            }

            ?>
            <h4 title="<?php echo $translate->_("Scheduled Learners"); ?>"><?php echo $translate->_("On Service Learners"); ?></h4>
            <div id="<?php echo strtolower(str_replace(" ", "-", $translate->_("On Service Learners"))); ?>" class="learner-table-wrapper">
                <?php self::renderScheduleTable($schedule_table_data["block_types"], $schedule_table_data["blocks"], "on-service", array("members" => $on_service_learners), "1", $draft_id); ?>
            </div>
            <?php
            if ($schedule_table_data["off_service_audience"]) {
                ?>
                <h4 title="<?php echo $translate->_("Off Service Learners"); ?>"><?php echo $translate->_("Off Service Learners"); ?></h4>
                <button class="btn btn-small current-block-button space-below"><?php echo $translate->_("Current Block"); ?></button>
                <div id="<?php echo strtolower(str_replace(" ", "-", $translate->_("Off Service Learners"))); ?>" class="learner-table-wrapper">
                    <?php self::renderScheduleTable($schedule_table_data["block_types"], $schedule_table_data["blocks"], $schedule_table_data["schedules"], array("members" => $schedule_table_data["off_service_audience"]), "2", $draft_id); ?>
                </div>
                <?php
            }
        }
    }

    public static function renderScheduleNavTabs($current_section) {
        global $translate, $MODULE, $SUBMODULE, $SECTION;
        ?>
        <ul class="nav nav-tabs">
            <li class="<?php echo $current_section == "index" && !$SUBMODULE ? "active" : ""; ?>"><a href="<?php echo ENTRADA_URL . "/admin/" . $MODULE; ?>"><?php echo $translate->_("My Published Schedules"); ?></a></li>
            <li class="<?php echo $current_section == "drafts" ? "active" : ""; ?>"><a href="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "?section=drafts"; ?>"><?php echo $translate->_("My Draft Schedules"); ?></a></li>
            <li class="<?php echo $SUBMODULE == "leave" ? "active" : ""; ?>"><a href="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "/leave" ; ?>"><?php echo $translate->_("Leave Tracking"); ?></a></li>
        </ul>
        <?php
    }

    public static function renderFullLearnerSchedule($proxy_id, $draft_id, $learner_name = "", $admin_tools = false) {
        global $JQUERY, $HEAD, $MODULE, $SECTION, $translate;

        $draft = Models_Schedule_Draft::fetchRowByID($draft_id);
        $cperiod_id = $draft->getCPeriodID();
        $cperiod = Models_Curriculum_Period::fetchRowByID($cperiod_id);

        $JQUERY[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/jquery/jquery.moment.min.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
        $JQUERY[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/jquery/jquery.fullcalendar.min.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
        $JQUERY[] = "<link href=\"".ENTRADA_RELATIVE."/css/jquery/jquery.fullcalendar.min.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />\n";
        $JQUERY[] = "<link href=\"".ENTRADA_RELATIVE."/css/jquery/jquery.fullcalendar.print.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"print\" />\n";
        $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js\"></script>";
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css\" />";
        $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/jquery/jquery.dataTables.min.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
        $HEAD[] = "<script type=\"text/javascript\">
                    var ENTRADA_URL = '". ENTRADA_URL ."';
                    var MODULE = \"".$MODULE."\";
                    var API_URL = \"".ENTRADA_URL . "/admin/" . $MODULE . "?section=api-schedule\";
                    var active_cell;
                    var draft_id = \"".$draft_id."\";

                    var translation = {
                        \"off_service_rotation\" : \"".$translate->_("Off Service Rotations")."\",
                        \"on_service_rotation\" : \"".$translate->_("On Service Rotations")."\",
                        \"remove_learner_from_slot\" : \"".$translate->_("I would like to remove this learner from this slot")."\",
                        \"rotation_name\" : \"".$translate->_("Rotation Name")."\",
                        \"rotation_dates\" : \"".$translate->_("Rotation Dates")."\",
                        \"short_name\" : \"".$translate->_("Short Name")."\",
                        \"learners_not_yet_added\" : \"".$translate->_("The learners in this course have not been added to the rotation schedule.")."\"
                    };
                    </script>";
        ?>
        <style type="text/css">
            .fc-other-month .fc-day-number {
                display:none!important;
            }
            .fc-event {
                cursor:pointer;
            }
            .modal {
                position:fixed;
                z-index: 100!important;
            }
            .modal-backdrop {
                z-index: 90;
            }
            #ui-datepicker-div, #ui-timepicker-div {
                z-index:110!important;
            }
            .modal-body {
                min-height:240px;
            }
            #comments {
                min-height: 30px;
            }
            .rotation-list-item {
                list-style-type: none;
            }
        </style>
        <script type="text/javascript">
            jQuery(function($) {

                $("#choose-rotations-btn").advancedSearch({
                    api_url: ENTRADA_URL + "/admin/rotationschedule?section=api-schedule",
                    resource_url: ENTRADA_URL,
                    filters: {
                        rotation: {
                            label: "Rotation Name",
                            data_source: "get-slot-blocks"
                        }
                    },list_data: {
                        selector: "#rotation-container-location"
                    },
                    modal: true,
                    no_results_text: "No Rotations found matching the search criteria",
                    parent_form: $("#rotation-form"),
                    results_parent: $("#book-slot"),
                    search_target_form_action: ENTRADA_URL + "/admin/rotationschedule/form",
                    width: 325
                });

                $.each($('.rotation-schedule'), function(i, v) {
                    var data = new Array;

                    $(v).fullCalendar({
                        "header": {
                            "left": false,
                            "center" : "title",
                            "right" : false
                        },
                        "height" : 400,
                        "defaultDate" : $(v).data("moment"),
                        "events" : "<?php echo ENTRADA_URL . "/api/api-schedule.api.php?method=get-schedule-data&proxy_id=".$proxy_id."&draft_id=".$draft_id; ?>",
                        "nextDayThreshold" : "01:00:00",
                        "eventRender" : function(event, element) {
                            $(element).attr("data-schedule-id", event.schedule_parent_id);
                            event.schedule_parent_id ? $(element).addClass("rotation-schedule") : $(element).addClass("leave");
                            $(element).find(".fc-time").remove();
                        },
                        "eventClick" : function(calEvent, jsEvent, view) {
                            <?php if ($admin_tools) { ?>
                                if (calEvent.event_type == "rotation") {
                                    $("#repeater-group").remove();
                                    $("#rotation-end-date-group").remove();
                                    populateModal(<?php echo $proxy_id; ?>, <?php echo $draft_id; ?>, calEvent.order, calEvent.block_type_id);
                                    $("#book-slot .modal-header h4").empty().append("Block Slot " + calEvent.order + " for " + "<?php echo $learner_name?>");
                                    $("#book-slot-redirect-information").show();
                                    $("#book-slot").modal("show");
                                } else if (calEvent.event_type == "leave") {
                                    var leave_id_input = $(document.createElement("input")).attr({"type" : "hidden", "value" : calEvent.leave_id, "name" : "leave_id", "id" : "leave-id"});
                                    $("#new-leave .modal-header h4").html("Update Leave");
                                    $("#add-leave-btn").html("Save");
                                    $("#leave-form").append(leave_id_input);
                                    $("#start-date").val(calEvent.start.format("YYYY-MM-DD"));
                                    $("#start-time").val(calEvent.start.format("HH:mm"));
                                    $("#end-date").val(calEvent.end.format("YYYY-MM-DD"));
                                    $("#end-time").val(calEvent.end.format("HH:mm"));
                                    $("#days-used").val(calEvent.days_used);
                                    $("select[name=leave_type] option[value="+calEvent.leave_type+"]").attr("selected", "selected");
                                    $("#new-leave").modal("show");
                                }
                            <?php } else { ?>
                            <?php } ?>
                        },
                        "eventAfterAllRender" : function () {
                            $.each($('.fc-event'), function (index, value) {
                                $(this).attr("rel", "popover");
                                $(this).attr("data-toggle", "popover");
                            });
                        }
                    });
                });

                $("#book-slot").on("hidden", function(e) {
                    $(".rotation-schedule").fullCalendar("refetchEvents");
                    $(".rotation-schedule").fullCalendar("rerenderEvents");
                });
                
                $("#new-leave").on("hidden", function(e) {
                    // Leave can span multiple months, so we have to refresh every calendar.
                    $(".rotation-schedule").fullCalendar("refetchEvents");
                    $(".rotation-schedule").fullCalendar("rerenderEvents");
                    clearModal();
                });

                function clearModal() {
                    $("#msgs").empty();
                    $(".name-container").remove();
                    $("#user-search").show();
                    $("#start-date").val("");
                    $("#end-date").val("");
                    $("#start-time").val("");
                    $("#end-time").val("");
                    $("select[name=leave_type] option").removeProp("selected");
                    $("#add-leave-btn").html("Add");
                    $("#leave-id").remove();
                    $("#book-slot-redirect-information").hide();
                    activeCalendar = "";
                }
            });
        </script>
        <div class="row-fluid" style="height:400px!important;">
            <?php
            $j = 0;
            for ($i = 0; $i < 12; $i++) {
            if ($j >= 3) {
            ?>
        </div>
        <div class="row-fluid" style="height:400px!important;">
            <?php
            $j = 0;
            }
            ?>
            <div class="span4"><div class="rotation-schedule" data-start="<?php echo strtotime("+".$i." months", $cperiod->getStartDate()); ?>" data-moment="<?php echo date("Y-m-d", strtotime("+".$i." months", $cperiod->getStartDate())); ?>"></div></div>
            <?php
            $j++;
            }
            ?>
        </div>
        <?php

        if ($admin_tools) {

            $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery-ui.min.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
            $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.audienceselector.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
            $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/jquery/jquery.audienceselector.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
            $JQUERY[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/jquery/jquery.timepicker.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
            $HEAD[] = "<script type=\"text/javascript\">
                        var ENTRADA_URL = '". ENTRADA_URL ."';
                        var MODULE = \"".$MODULE."\";
                        var SECTION = \"".$SECTION."\";
                    </script>";
            $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/".$MODULE."/leave.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
            $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/".$MODULE."/rotationschedule.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";

            self::renderSlotBookingModal($draft->getCourseID(), $draft_id, $proxy_id);
            self::renderLeaveBookingModal($proxy_id);
        }
    }

    public static function renderSlotBookingModal($course_id = 0, $draft_id, $proxy_id) {
        global $translate, $MODULE;

        if (isset($_GET["draft_id"])) {
            $PROCESSED["draft_id"] = $_GET["draft_id"];
        }

        $user_data = User::fetchRowByID($proxy_id);
        if ($user_data) {
            $name = $user_data->getFirstname()." ".$user_data->getLastname();
        }
        ?>
        <div id="book-slot" class="modal modal-lg hide fade responsive-modal">
            <form id="rotation-form" class="form-horizontal" action="<?php echo ENTRADA_URL . "/admin/" . $MODULE . "?section=api-schedule"; ?>" method="POST">
                <input type="hidden" name="proxy_id" value="" />
                <input type="hidden" name="course_id" value="<?php echo ((int)$course_id); ?>" />
                <input type="hidden" name="draft_id" value="<?php echo $draft_id;?>" />
                <input type="hidden" name="block_order" value="" />
                <input type="hidden" name="block_type_id" value="" />
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>

                    <h4><?php echo $translate->_("Book Slot "); ?></h4>
                    <p id="rotation-dates"></p>
                    <div id="book-slot-redirect-information" class="alert alert-block alert-info hide">
                        <?php $url = ENTRADA_URL . "/admin/" . $MODULE . "?section=edit-draft&draft_id=" . $PROCESSED["draft_id"];
                        echo sprintf($translate->_("Checking or unchecking rotation(s) will add or remove them from this block only. If you wish to add or remove a rotation(s) across multiple blocks click '<a href=\"%s\">here</a>' edit the Rotation Schedule."), $url); ?>
                    </div>
                    <div id="book-slot-error" class="alert alert-block alert-danger hide">
                        <p></p>
                    </div>
                </div>
                <div id="rotationschedule-modal-body" class="modal-body">
                    <div id="book-slot-no-results" class="alert alert-block alert-warning hide">
                        <p><?php echo $translate->_("No rotations are available for this slot."); ?></p>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="choose-rotations-btn"><?php echo $translate->_("Select Rotations:"); ?></label>
                        <div class="controls">
                            <button id="choose-rotations-btn" class="btn">
                                <?php echo $translate->_("Browse Rotations"); ?>
                                <i class="icon-chevron-down"></i>
                            </button>
                        </div>
                    </div>
                    <div id= "repeater-group" class="control-group">
                        <label class="control-label" for="repeater_input"><?php echo $translate->_("Block Span:"); ?></label>
                        <div class="controls">
                            <input class="input-mini" id="repeater_input" type="number" min="1" value="1" max="13" name="repeater">
                        </div>
                    </div>
                    <div id= "rotation-end-date-group" class="control-group">
                        <label class="control-label"><?php echo $translate->_("Rotation End Date: "); ?></label>
                        <div class="controls">
                            <p id="calculated-end-date" style="margin-top: 5px"></p>
                        </div>
                    </div>
                    <div id="book-slot-warning" class="alert alert-block alert-warning hide">
                        <p><?php echo $translate->_("Please note removing any rotations with a Block span will delete adjacent rotations."); ?></p>
                    </div>
                    <div id="on-service"></div>
                    <div id="off-service"></div>
                </div>

                <div class="modal-footer" id="book-slot-modal-footer">
                    <div class="pull-left">
                        <input id="book-slot-delete" type="submit" class="btn btn-danger hide" value="<?php echo $translate->_("Delete"); ?>" />
                    </div>
                    <a href="#" class="btn btn-default" data-dismiss="modal"><?php echo $translate->_("Close"); ?></a>
                    <input id="book-slot-submit" type="submit" class="btn btn-primary" value="<?php echo $translate->_("Save"); ?>" />
                </div>
            </form>
        </div>
        <?php
    }

    public static function renderLeaveBookingModal($proxy_id = NULL, $add_btn_class = "") {
        global $translate;
        ?>
        <div class="modal hide fade responsive-modal" id="new-leave">
            <div class="modal-header">
                <h4>
                    <?php echo $translate->_("New Leave");
                    if ($proxy_id) {
                        $user = User::fetchRowByID($proxy_id);
                        if ($user) {
                            echo sprintf($translate->_(" for %s %s"), $user->getFirstname(), $user->getLastname());
                        }
                    } ?>
                </h4>
            </div>
            <div class="modal-body">
                <div id="msgs"></div>
                <form class="form-horizontal" id="leave-form">
                    <?php if (is_null($proxy_id)) { ?>
                    <div class="control-group">
                        <label for="user-search" class="control-label"><?php echo $translate->_("Name"); ?></label>
                        <div class="controls">
                            <input id="user-search" type="text" />
                        </div>
                    </div>
                    <?php } else { ?>
                    <input type="hidden" name="proxy_id" value="<?php echo $proxy_id; ?>" />
                    <?php } ?>
                    <div class="control-group">
                        <label for="start-date" class="control-label"><?php echo $translate->_("Start Date"); ?></label>
                        <div class="controls">
                            <div class="input-append">
                                <input id="start-date" name="start_date" type="text" class="datepicker span8" />
                                <span class="add-on"><i class="icon-calendar"></i></span>
                            </div>
                            <div class="input-append">
                                <input id="start-time" name="start_time" type="text" class="timepicker span4" />
                                <span class="add-on"><i class="icon-time"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="control-group">
                        <label for="end-date" class="control-label"><?php echo $translate->_("End Date"); ?></label>
                        <div class="controls">
                            <div class="input-append">
                                <input id="end-date" name="end_date" type="text" class="datepicker span8" />
                                <span class="add-on"><i class="icon-calendar"></i></span>
                            </div>
                            <div class="input-append">
                                <input id="end-time" name="end_time" type="text" class="timepicker span4" />
                                <span class="add-on"><i class="icon-time"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="control-group">
                        <label for="days-used" class="control-label"><?php echo $translate->_("Total Days Used"); ?></label>
                        <div class="controls">
                            <input id="days-used" name="days_used" type="text" class="span4" />
                        </div>
                    </div>
                    <div class="control-group">
                        <label for="weekdays-used" class="control-label"><?php echo $translate->_("Weekdays Used"); ?></label>
                        <div class="controls">
                            <input id="weekdays-used" name="weekdays_used" type="text" class="span2" />
                        </div>
                    </div>
                    <div class="control-group">
                        <label for="weekend-days-used" class="control-label"><?php echo $translate->_("Weekend Days Used"); ?></label>
                        <div class="controls">
                            <input id="weekend-days-used" name="weekend_days_used" type="text" class="span2" />
                        </div>
                    </div>
                    <div class="control-group">
                        <label for="leave-type" class="control-label"><?php echo $translate->_("Leave Type"); ?></label>
                        <div class="controls">
                            <select name="leave_type">
                                <?php foreach(Models_Leave_Type::fetchAllRecords() as $leave_type) { ?>
                                    <option value="<?php echo html_encode(strtolower(str_replace(' ', '', $leave_type->getID()))); ?>"><?php echo html_encode(ucwords($leave_type->getTypeValue())); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="control-group">
                        <label for="comments" class="control-label"><?php echo $translate->_("Comments"); ?></label>
                        <div class="controls">
                            <textarea id="comments" name="comments" class="expandable space-above"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <a href="#" data-dismiss="modal" class="btn btn-default"><?php echo $translate->_("Close"); ?></a>
                <a href="#" id="add-leave-btn" class="btn btn-primary <?php echo $add_btn_class; ?>"><?php echo $translate->_("Add"); ?></a>
            </div>
        </div>
        <?php
    }

    private static function sortRotationCodeByTitle($item1, $item2) {
        return strcmp($item1["title"], $item2["title"]);
    }

}
