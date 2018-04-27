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
 * This file edits an User Disclaimer already created for the currently active Organisation.
 *
 * @author Organisation: Queens University
 * @author Unit: Medtech Unit
 * @author Developer: Jonatan Caraballo <jch9@queensu.ca>
 * @copyright Copyright 2017 Queens University. All Rights Reserved.
 *
 */

if (!defined("PARENT_INCLUDED") || !defined("IN_CONFIGURATION")) {
    exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !$_SESSION["isAuthorized"]) {
    header("Location: " . ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "create", false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.");

    echo display_error();

    application_log("error", "Group [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"] . "] and role [" . $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"] . "] do not have access to this module [" . $MODULE . "]");
} else {

    $BREADCRUMB[]  = array("url" => ENTRADA_URL . "/admin/settings/manage/disclaimers?" . "org=" . $ORGANISATION_ID, "title" => "Edit User Disclaimer");
    $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.timepicker.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.inputselector.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js\"></script>";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.timepicker.css?release=".html_encode(APPLICATION_VERSION)."\" />";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.inputselector.css?release=".html_encode(APPLICATION_VERSION)."\" />";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css\" />";

    if (isset($_GET["disclaimer_id"]) && $tmp_input = clean_input($_GET["disclaimer_id"], "int")) {
        $disclaimer_id = $tmp_input;
        $disclaimer = Models_Disclaimers::fetchRowByID($disclaimer_id);

        $disclaimers_audience_users = Models_Disclaimer_Audience_Users::fetchAllByDisclaimerID($disclaimer_id);

        if($disclaimer) {
            $PROCESSED["disclaimer_id"] = $disclaimer->getID();
            $PROCESSED["disclaimer_title"] = $disclaimer->getDisclaimerTitle();
            $PROCESSED["disclaimer_text"] = $disclaimer->getDisclaimerText();
            $PROCESSED["organisation_id"] = $disclaimer->getOrganisationID();
            $PROCESSED["email_admin"] = $disclaimer->getEmailAdmin();
            $PROCESSED["created_date"] = $disclaimer->getCreatedDate();
            $PROCESSED["created_by"] = $disclaimer->getCreatedBy();

            $upon_decline = $disclaimer->getUponDecline();
            $disclaimer_issue_date = ($disclaimer->getDisclaimerIssueDate() ? date("Y-m-d", $disclaimer->getDisclaimerIssueDate()) : "");
            $disclaimer_expire_date = ($disclaimer->getDisclaimerExpireDate() ? date("Y-m-d", $disclaimer->getDisclaimerExpireDate()) : "");

            // Error Checking
            switch ($STEP) {
                case 2 :
                    $disclaimer_issue_date = "";
                    $disclaimer_expire_date = "";

                    /**
                     * Required field "disclaimer_title" / Disclaimer Title
                     */

                    if (isset($_POST["disclaimer_title"]) && ($disclaimer_title = clean_input($_POST["disclaimer_title"], array("notags", "trim")))) {
                        $PROCESSED["disclaimer_title"] = $disclaimer_title;
                    } else {
                        add_error($translate->_("The disclaimer title is a required field"));
                    }

                    /**
                     * Not-Required field "disclaimer_issue_date" / Disclaimer Issue Date
                     */

                    if (isset($_POST["disclaimer_issue_date"]) && ($tmp_input = clean_input($_POST["disclaimer_issue_date"], array("notags", "trim")))) {
                        $disclaimer_issue_date = $tmp_input;
                        $PROCESSED["disclaimer_issue_date"] = strtotime($disclaimer_issue_date);
                    } else {
                        $disclaimer_issue_date = "";
                    }

                    /**
                     * Not-Required field "disclaimer_expire_date" / Disclaimer Expire Date
                     */

                    if (isset($_POST["disclaimer_expire_date"]) && ($tmp_input = clean_input($_POST["disclaimer_expire_date"], array("notags", "trim")))) {
                        $disclaimer_expire_date = $tmp_input;
                        $PROCESSED["disclaimer_expire_date"] = strtotime($disclaimer_expire_date);
                    } else {
                        $disclaimer_expire_date = "";
                    }

                    if ($disclaimer_expire_date != "" && $disclaimer_issue_date != "" && ($disclaimer_expire_date <= $disclaimer_issue_date)) {
                        add_error($translate->_("The disclaimer Effective From date must be before the Effective Until date."));
                    }

                    /**
                     * Required field "disclaimer_text" / Disclaimer Text
                     */
                    if (!$disclaimers_audience_users) {
                        if (isset($_POST["disclaimer_text"]) && ($disclaimer_text = clean_input($_POST["disclaimer_text"], array("allowedtags")))) {
                            $PROCESSED["disclaimer_text"] = $disclaimer_text;
                        } else {
                            add_error($translate->_("The disclaimer text is a required field"));
                        }
                    }

                    /**
                     * Not-Required field "upon_decline" / Upon Decline
                     */

                    if (isset($_POST["upon_decline"]) && ($upon_decline = clean_input($_POST["upon_decline"], array("notags", "trim")))) {
                        $PROCESSED["upon_decline"] = $upon_decline;
                    } else {
                        $PROCESSED["upon_decline"] = "continue";
                    }

                    /**
                     * Not-Required field "trigger_type" / Trigger Type
                     */

                    if (isset($_POST["trigger_type"]) && ($trigger_type = clean_input($_POST["trigger_type"], array("notags", "trim")))) {
                        $PROCESSED["trigger_type"] = $trigger_type;
                    } else {
                        $PROCESSED["trigger_type"] = "page_load";
                    }

                    /**
                     * Not-Required field "upon_decline" / Upon Decline
                     */

                    if (isset($_POST["email_admin"])) {
                        $PROCESSED["email_admin"] = 1;
                    } else {
                        $PROCESSED["email_admin"] = 0;
                    }

                    /**
                     * Get User Disclaimer Audience
                     */

                    $roles = array();

                    if (isset($_POST["roles"]) && $_POST["roles"]) {
                        foreach ($_POST["roles"] as $role) {
                            if ($tmp_input = clean_input($role, array("trim", "int"))) {
                                $roles[] = $tmp_input;
                            }
                        }
                    }

                    $trigger_values = array();

                    /**
                     * Get Courses
                     */

                    if (isset($_POST["course"]) && $_POST["course"]) {
                        foreach ($_POST["course"] as $course) {
                            if ($tmp_input = clean_input($course, array("trim", "int"))) {
                                $trigger_values["course"][] = $tmp_input;
                            }
                        }
                    }

                    /**
                     * Get Communities
                     */

                    if (isset($_POST["community"]) && $_POST["community"]) {
                        foreach ($_POST["community"] as $community) {
                            if ($tmp_input = clean_input($community, array("trim", "int"))) {
                                $trigger_values["community"][] = $tmp_input;
                            }
                        }
                    }

                    if (!$ERROR) {
                        $PROCESSED["disclaimer_id"] = $disclaimer_id;
                        $PROCESSED["updated_date"] = time();
                        $PROCESSED["updated_by"] = $ENTRADA_USER->getID();
                        $disclaimer = new Models_Disclaimers($PROCESSED);
                        if ($disclaimer->update()) {
                            if ($disclaimer_id = $disclaimer->getID()) {
                                $trigger_type = $disclaimer->getTriggerType();
                                if ($trigger_type == 'course' || $trigger_type == 'community') {
                                    if (!empty($trigger_values[$trigger_type])) {
                                        $disclaimer_triggers = new Models_Disclaimer_Trigger();
                                        if ($disclaimer_triggers->deleteByDisclaimerID($disclaimer_id)) {
                                            foreach ($trigger_values[$trigger_type] as $value) {
                                                $trigger_data = array(
                                                    "disclaimer_id" => $disclaimer_id,
                                                    "disclaimer_trigger_type" => $trigger_type,
                                                    "disclaimer_trigger_value" => $value,
                                                    "updated_date" => time(),
                                                    "updated_by" => $ENTRADA_USER->getID()
                                                );
                                                $disclaimer_trigger = new Models_Disclaimer_Trigger($trigger_data);
                                                if (!$disclaimer_trigger->insert()) {
                                                    add_error($translate->_("Failed to insert this disclaimer trigger value into the User Disclaimer Triggers. Please contact a system administrator if this problem persists."));
                                                    application_log("error", "Error while inserting value into database. Database server said: " . $db->ErrorMsg());
                                                }
                                            }
                                        } else {
                                            add_error($translate->_("Failed to insert this disclaimer trigger value into the User Disclaimer Triggers. Please contact a system administrator if this problem persists."));
                                            application_log("error", "Error while inserting value into database. Database server said: " . $db->ErrorMsg());
                                        }
                                    }
                                }
                                if (!empty($roles)) {
                                    $audience = new Models_Disclaimer_Audience();
                                    if ($audience->deleteByDisclaimerID($disclaimer_id)) {
                                        foreach ($roles as $role) {
                                            $audience_data = array(
                                                "disclaimer_id" => $disclaimer_id,
                                                "disclaimer_audience_type" => "role_id",
                                                "disclaimer_audience_value" => $role,
                                                "updated_date" => time(),
                                                "updated_by" => $ENTRADA_USER->getID()
                                            );
                                            $disclaimer_audience = new Models_Disclaimer_Audience($audience_data);
                                            if (!$disclaimer_audience->insert()) {
                                                add_error($translate->_("Failed to insert this role into the User Disclaimer Audience. Please contact a system administrator if this problem persists."));
                                                application_log("error", "Error while inserting learner into database. Database server said: " . $db->ErrorMsg());
                                            }
                                        }
                                    } else {
                                        add_error($translate->_("Failed to insert this role into the User Disclaimer Audience. Please contact a system administrator if this problem persists."));
                                        application_log("error", "There was an error inserting a User Disclaimer Audience. Database said: " . $db->ErrorMsg());
                                    }
                                }
                                add_statistic("disclaimers", "update", "disclaimer_id", $disclaimer_id, $ENTRADA_USER->GetID());
                                $url = ENTRADA_URL . "/admin/settings/manage/disclaimers?org=" . $ORGANISATION_ID;
                                add_success(sprintf($translate->_("You have succesfully saved the changes for <strong>%s</strong> to the system.<br /><br />You will now be redirected to the User Disclaimers section; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"%s\" style = \"font-weight: bold\" >click here</a> to continue"), html_encode($PROCESSED["disclaimer_title"]), $url));

                                $ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";
                                application_log("success", "New User Disclaimer added to the system.");
                            } else {
                                add_error($translate->_("There was a problem inserting this User Disclaimer into the system. The system administrator was informed of this error; please try again later."));
                                application_log("error", "There was an error inserting a User Disclaimer. Database said: " . $db->ErrorMsg());
                            }
                        } else {
                            add_error($translate->_("There was a problem inserting this User Disclaimer into the system. The system administrator was informed of this error; please try again later."));
                            application_log("error", "There was an error inserting a User Disclaimer. Database said: " . $db->ErrorMsg());
                        }
                    }

                    if ($ERROR) {
                        $STEP = 1;
                    }
                    break;
                case 1 :
                default :

                    break;
            }

            // Display Content
            switch ($STEP) {
                case 2 :
                    if ($SUCCESS) {
                        echo display_success();
                    }

                    if ($NOTICE) {
                        echo display_notice();
                    }

                    if ($ERROR) {
                        echo display_error();
                    }
                    break;
                case 1 :
                default:
                    if ($ERROR) {
                        echo display_error();
                    }
            ?>
            <form class="form-horizontal" action="<?php echo ENTRADA_URL . "/admin/settings/manage/disclaimers" . "?" . replace_query(array("step" => 2)); ?>" id="disclaimer_add_form" method="post">

                <h1><?php echo $translate->_("Edit User Disclaimer"); ?></h1>
                <h2><?php echo $translate->_("Disclaimer Information"); ?></h2>

                <div class="control-group">
                    <label for="disclaimer_title" class="control-label form-required">
                        <?php echo $translate->_("Title"); ?>
                    </label>
                    <div class="controls">
                        <input class="span8" type="text" id="disclaimer_title" name="disclaimer_title" maxlength="255" value="<?php if (isset($PROCESSED["disclaimer_title"])) { echo $PROCESSED["disclaimer_title"]; } ?>"/>
                    </div>
                </div>

                <div class="control-group">
                    <label for="disclaimer_text" class="control-label form-required">
                        <?php echo $translate->_("Disclaimer Text"); ?>
                    </label>
                    <div class="controls">
                        <?php

                        if (!$disclaimers_audience_users) {
                            load_rte();
                            ?>
                            <textarea id="disclaimer_text" name="disclaimer_text" class="span8"><?php echo ((isset($PROCESSED["disclaimer_text"])) ? html_encode(trim(strip_selected_tags($PROCESSED["disclaimer_text"], array("font")))) : ""); ?></textarea>
                        <?php
                        } else {
                            ?>
                            <div class="alert alert-notice span8">
                                <?php echo $translate->_("<strong>Please Note:</strong> The following disclaimer has already been accepted by one or more users. Since it has already been accepted you cannot make any changes to it. If you need to make changes, please delete this disclaimer and create a new one."); ?>
                            </div>
                            <div class="span8 muted">
                            <?php echo $PROCESSED["disclaimer_text"]; ?>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>

                <div class="control-group">
                    <label for="disclaimer_issue_date" class="control-label">
                        <?php echo $translate->_("Effective from"); ?>
                    </label>
                    <div class="controls">
                        <div class="input-append space-right">
                            <input id="disclaimer_issue_date" type="text" class="input-small datepicker"
                                   value="<?php echo $disclaimer_issue_date; ?>"
                                   name="disclaimer_issue_date"
                                   data-default-date="<?php echo $disclaimer_issue_date; ?>"/>
                            <span class="add-on pointer">
                                <i class="icon-calendar"></i>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="control-group">
                    <label for="disclaimer_expire_date" class="control-label">
                        <?php echo $translate->_("Effective until"); ?>
                    </label>
                    <div class="controls">
                        <div class="input-append space-right">
                            <input id="disclaimer_expire_date" type="text" class="input-small datepicker"
                                   value="<?php echo $disclaimer_expire_date; ?>"
                                   name="disclaimer_expire_date"
                                   data-default-date="<?php echo $disclaimer_expire_date; ?>"/>
                            <span class="add-on pointer">
                                <i class="icon-calendar"></i>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label">
                        <?php echo $translate->_("Trigger this disclaimer"); ?>
                    </label>
                    <div class="controls">
                        <label class="radio-table" for="trigger_type1">
                            <input value="page_load" type="radio" id="trigger_type1" name="trigger_type" checked="checked"> <?php echo $translate->_("when any page is accessed."); ?>
                        </label>

                        <div>
                            <label class="radio-table" for="trigger_type2">
                                <input value="course" type="radio" id="trigger_type2" name="trigger_type"> <?php echo $translate->_("when a specific course or courses are accessed."); ?>
                            </label>
                            <button id="choose-course-btn" class="btn btn-search-filter space-above space-below hide" style="min-width: 220px; text-align: left;"><?php echo $translate->_("Browse All Courses"); ?> <i class="icon-chevron-down btn-icon pull-right"></i></button>
                        </div>

                        <div>
                            <label class="radio-table" for="trigger_type3">
                                <input value="community" type="radio" id="trigger_type3" name="trigger_type"> <?php echo $translate->_("when a specific community or communities are accessed."); ?>
                            </label>
                            <button id="choose-community-btn" class="btn btn-search-filter space-above space-below hide" style="min-width: 220px; text-align: left;"><?php echo $translate->_("Browse All Communities"); ?> <i class="icon-chevron-down btn-icon pull-right"></i></button>
                        </div>

                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label">
                        <?php echo $translate->_("When someone declines"); ?>
                    </label>
                    <div class="controls">
                        <label class="radio-table" for="upon_decline1">
                            <input value="continue" type="radio" id="upon_decline1" name="upon_decline"> <?php echo $translate->_("Allow them to continue anyways."); ?>
                        </label>
                        <label class="radio-table" for="upon_decline2">
                            <input value="log_out" type="radio" id="upon_decline2" name="upon_decline"> <?php echo $translate->_("Immediately log them out."); ?>
                        </label>
                        <label class="radio-table" for="upon_decline3">
                            <input value="deny_access" type="radio" id="upon_decline3" name="upon_decline"> <?php echo $translate->_("Don't allow them to access the course or community."); ?>
                        </label>

                        <br />
                        <label class="checkbox-table" for="email_admin"><input type="checkbox" id="email_admin" name="email_admin"> <?php echo $translate->_("Notify me via email when someone declines."); ?></label>

                    </div>
                </div>

                <h2><?php echo $translate->_("Disclaimer Audience"); ?></h2>

                <div class="control-group">
                    <label for="choose-audience-btn" class="control-label"><?php echo $translate->_("Select Audience"); ?></label>
                    <div class="controls">
                        <button id="choose-audience-btn" class="btn btn-search-filter" style="min-width: 220px; text-align: left;"><?php echo $translate->_("Browse All Users"); ?> <i class="icon-chevron-down btn-icon pull-right"></i></button>
                    </div>
                </div>

                <a href="<?php echo ENTRADA_URL; ?>/admin/settings/manage/disclaimers?org=<?php echo $ORGANISATION_ID; ?>" class="btn"><?php echo $translate->_("global_button_cancel"); ?></a>

                <input type="submit" class="btn btn-primary pull-right" value="<?php echo $translate->_("global_button_save"); ?>" />

                <?php
                if ($disclaimer_id) {
                    $disclaimer_audiences = Models_Disclaimer_Audience::fetchAllByDisclaimerID($disclaimer_id);
                    if ($disclaimer_audiences) {
                        foreach ($disclaimer_audiences as $disclaimer_audience) {
                            $target_id = (int) $disclaimer_audience->getDisclaimerAudienceValue();
                            if($rol = Models_System_Role::fetchRowByID($target_id)) {
                                $target_label = $rol->getRoleName();
                                if ($group = Models_System_Group::fetchRowByID($rol->getGroupID())) {
                                    $group_name = $group->getGroupName();
                                    echo "<input id=\"" . ucfirst($group_name) . "_" . $target_id . "\" class=\"search-target-control " . ucfirst($group_name) . "_search_target_control\" type=\"hidden\" name=\"roles[]\" value=\"" . $target_id . "\" data-id=\"" . $target_id . "\" data-label=\"" . ucfirst($target_label) . "\"/>";
                                }
                            }
                        }
                    }

                    $disclaimer_triggers = Models_Disclaimer_Trigger::fetchAllByDisclaimerID($disclaimer_id);
                    if ($disclaimer_triggers) {
                        foreach ($disclaimer_triggers as $disclaimer_trigger) {
                            $target_id = (int) $disclaimer_trigger->getDisclaimerTriggerValue();
                            $trigger_type = $disclaimer_trigger->getDisclaimerTriggerType();
                            $target_label = "";
                            switch ($trigger_type) {
                                case "course":
                                    if ($course = Models_Course::fetchRowByID($target_id)) {
                                        $target_label = $course->getCourseName();

                                        if ($course->getCourseCode() != "") {
                                            $target_label = $course->getCourseCode() . ": " . $target_label;
                                        }
                                    }

                                    break;
                                case "community":
                                    if ($community = Models_Community::fetchRowByID($target_id)) {
                                        $target_label = $community->getTitle();
                                    }
                                    break;
                            }
                                echo "<input id=\"" . $trigger_type . "_" . $target_id . "\" class=\"search-target-control " . $trigger_type . "_search_target_control\" type=\"hidden\" name=\"" . $trigger_type . "[]\" value=\"" . $target_id . "\" data-id=\"" . $target_id . "\" data-label=\"" . ucfirst($target_label) . "\"/>";
                        }
                    }

                } else {
                    if (!empty($roles)) {
                        foreach ($roles as $role) {
                            $target_id = (int) $role;
                            if ($rol = Models_System_Role::fetchRowByID($target_id)) {
                                $target_label = $rol->getRoleName();
                                if ($group = Models_System_Group::fetchRowByID($rol->getGroupID())) {
                                    $group_name = $group->getGroupName();
                                    echo "<input id=\"" . ucfirst($group_name) . "_" . $target_id . "\" class=\"search-target-control " . ucfirst($group_name) . "_search_target_control\" type=\"hidden\" name=\"roles[]\" value=\"" . $target_id . "\" data-id=\"" . $target_id . "\" data-label=\"" . ucfirst($target_label) . "\"/>";
                                }
                            }
                        }
                    }

                    if (!empty($courses)) {
                        foreach ($courses as $course_id) {
                            $target_id = (int) $course_id;
                            if ($course = Models_Course::fetchRowByID($target_id)) {
                                $target_label = $course->getCourseName();
                                echo "<input id=\"course_" . $target_id . "\" class=\"search-target-control course_search_target_control\" type=\"hidden\" name=\"course[]\" value=\"" . $target_id . "\" data-id=\"" . $target_id . "\" data-label=\"" . ucfirst($target_label) . "\"/>";
                            }
                        }
                    }

                    if (!empty($communities)) {
                        foreach ($communities as $community_id) {
                            $target_id = (int) $community_id;
                            if ($community = Models_Community::fetchRowByID($target_id)) {
                                $target_label = $community->getTitle();
                                echo "<input id=\"community_" . $target_id . "\" class=\"search-target-control community_search_target_control\" type=\"hidden\" name=\"community[]\" value=\"" . $target_id . "\" data-id=\"" . $target_id . "\" data-label=\"" . ucfirst($target_label) . "\"/>";
                            }
                        }
                    }
                }
                ?>
            </form>

            <script type="text/javascript">
                jQuery(document).ready(function(){

                    jQuery("#disclaimer_issue_date").datepicker({dateFormat: "yy-mm-dd"});
                    jQuery("#disclaimer_expire_date").datepicker({dateFormat: "yy-mm-dd"});
                    jQuery("input[name=\"upon_decline\"][value=\"<?php echo $upon_decline; ?>\"]").attr("checked", "checked");
                    jQuery("input[name=\"trigger_type\"][value=\"<?php echo $trigger_type; ?>\"]").attr("checked", "checked");
                    if (jQuery("input[name=\"trigger_type\"][value=\"page_load\"]").is(":checked")) {
                        jQuery("label[for=upon_decline3]").addClass("hide");
                    }
                    <?php echo ($PROCESSED["email_admin"] == 1 ? "jQuery('#email_admin').attr('checked', 'checked');" : ""); ?>

                    jQuery("#choose-audience-btn").advancedSearch({
                        api_url: ENTRADA_URL+"/api/disclaimers.api.php",
                        build_selected_filters: true,
                        build_form: true,
                        resource_url: ENTRADA_URL,
                        filter_component_label: "Users",
                        target_name: "roles",
                        select_all_enabled: true,
                        filters: {},
                        no_results_text: "<?php echo $translate->_("No users found matching the search criteria"); ?>",
                        parent_form: jQuery("#disclaimer_add_form"),
                        list_selections: true
                    });

                    jQuery.getJSON(ENTRADA_URL+"/api/disclaimers.api.php?organisation_id=<?php echo $ORGANISATION_ID; ?>",
                        {
                            method: "get-groups"
                        } , function (json) {
                            jQuery.each(json.data, function (key, value) {
                                jQuery("#choose-audience-btn").data("settings").filters[value.target_label] = {
                                    label: value.target_label,
                                    api_params: {
                                        group_id: value.target_id,
                                        organisation_id: <?php echo $ORGANISATION_ID; ?>
                                    },
                                    data_source: "get-roles"
                                }
                            });
                        });

                    jQuery("input[name*='upon_decline']").on("change", function() {
                        OnChangeUponDecline();
                    });
                    jQuery("input[name*='trigger_type']").on("change", function() {
                        OnChangeTriggerType();
                    });

                    // Advanced Search plugin for choose Courses.
                    jQuery("#choose-course-btn").advancedSearch({
                        api_url: ENTRADA_URL + "/api/disclaimers.api.php",
                        build_selected_filters: true,
                        build_form: true,
                        resource_url: ENTRADA_URL,
                        select_all_enabled: true,
                        filters: {
                            course: {
                                label: "<?php echo $translate->_("Course"); ?>",
                                data_source: "get-courses",
                                selector_control_name: "course"
                            }
                        },
                        target_name: "course",
                        control_class: "course-selector",
                        no_results_text: "<?php echo $translate->_("No courses found matching the search criteria"); ?>",
                        parent_form: jQuery("#disclaimer_add_form")
                    });

                    // Advanced Search plugin for choose Communities.
                    jQuery("#choose-community-btn").advancedSearch({
                        api_url: ENTRADA_URL+"/api/disclaimers.api.php",
                        build_selected_filters: true,
                        build_form: true,
                        resource_url: ENTRADA_URL,
                        select_all_enabled: true,
                        filters: {
                            community: {
                                label: "<?php echo $translate->_("Communities"); ?>",
                                data_source: "get-communities",
                                selector_control_name: "community"
                            }
                        },
                        target_name: "community",
                        control_class: "community-selector",
                        no_results_text: "<?php echo $translate->_("No communities found matching the search criteria"); ?>",
                        parent_form: jQuery("#disclaimer_add_form")
                    });

                    buildAdvancedSearchList(jQuery("#choose-audience-btn"));
                    buildAdvancedSearchList(jQuery("#choose-course-btn"));
                    buildAdvancedSearchList(jQuery("#choose-community-btn"));
                    OnChangeTriggerType();
                });
            </script>
                <?php
                break;
            }
        } else {
            add_error($translate->_("Has ocurred an error attempting to get data from this User Disclaimer"));

            echo display_error();

            application_log("error", $ERRORSTR . "User Disclaimer ID: " . $disclaimer_id);
        }
    } else {
        add_error($translate->_("The User Disclaimer ID has not been provided or it is not valid."));

        echo display_error();

        application_log("error", $ERRORSTR);
    }
}
