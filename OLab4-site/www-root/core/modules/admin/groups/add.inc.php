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
 * This file is used to add groups.
 *
 * @author Organisation: University of Calgary
 * @author Unit: School of Medicine
 * @author Developer: Doug Hall <hall@ucalgary.ca>
 * @copyright Copyright 2017 University of Calgary. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_GROUPS"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed('group', 'create', false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 5000)";

    add_error(sprintf($translate->_("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%s\"> %s </a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	ini_set("auto_detect_line_endings", true);

	$HEAD[] = "<script type=\"text/javascript\" >var ENTRADA_URL = '". ENTRADA_URL ."';</script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/AutoCompleteList.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js\"></script>";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css\" />";

	$url = ENTRADA_URL. "/admin/$MODULE";

	$BREADCRUMB[] = array("url" => "${url}?".replace_query(array("section" => "add")), "title" => $translate->_("Adding Cohort"));

    $group_type = false;
	$GROUP_IDS = array();
    $PROCESSED = array();

	// Error Checking
	switch ($STEP) {
		case 2 :
			if (isset($_POST["post_action"])) {
				switch ($_POST["post_action"]) {
					case "content" :
						$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["post_action"] = "content";
						break;
					case "new" :
						$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["post_action"] = "new";
						break;
					case "index" :
					default :
						$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["post_action"] = "index";
						break;
				}
			} else {
				$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["post_action"] = "content";
			}
				
			/**
			 * Get the active organisation_id and add it to the PROCESSED array.
			 */
			$PROCESSED["group_name"] = "";
			$PROCESSED["group_type"] = "";
			$PROCESSED["organisation_id"] = $ENTRADA_USER->getActiveOrganisation();
			$PROCESSED["entrada_only"] = 1;
			$PROCESSED["created_date"] = time();
			$PROCESSED["created_by"] = $ENTRADA_USER->getID();
			$PROCESSED["updated_date"] = $PROCESSED["created_date"];
			$PROCESSED["updated_by"] = $PROCESSED["created_by"];

            /**
             * Required field "group_name" / Cohort Name.
             */
            if ((isset($_POST["group_name"])) && ($group_name = clean_input($_POST["group_name"], array("notags", "trim")))) {
                $PROCESSED["group_name"] = $group_name;
            } else {
                add_error($translate->_("The <strong>Cohort Name</strong> field is required."));
            }

            /**
             * Required field "group_type" / Cohort Type.
             */
            if ((isset($_POST["group_type"])) && ($group_type = clean_input($_POST["group_type"], array("trim"))) && in_array($group_type, array("course_list", "cohort"))) {
                $PROCESSED["group_type"] = $group_type;
            } else {
                add_error($translate->_("The <strong>Cohort Type</strong> field is required."));
            }

            /**
             * Required field "course_id" / Course ID (when group_type == course_list)
             */
            if (isset($PROCESSED["group_type"]) && $PROCESSED["group_type"] == "course_list") {
                if (isset($_POST["course_id"]) && $course_id = clean_input($_POST["course_id"], array("int"))) {
                    $PROCESSED["group_value"] = $course_id;
                } else {
                    add_error($translate->_("The <strong>Course</strong> field is required for course lists."));
                }
            } else {
                $PROCESSED["group_value"] = NULL;
            }

            if (!$ERROR) {
                if (Models_Group::fetchRowByName($PROCESSED["group_name"], $PROCESSED["organisation_id"])) {
                    add_error(sprintf($translate->_("The cohort name <strong>[%s]</strong> you are trying to create already exists."), $PROCESSED["group_name"]));
                } else {
                    $group = new Models_Group($PROCESSED);
                    if ($group->insert()) {
                        $entry = array("group_id" => $group->getID(), "organisation_id" => $PROCESSED["organisation_id"], "created_date" => $PROCESSED["created_date"], "created_by" => $PROCESSED["created_by"], "updated_date" => $PROCESSED["updated_date"], "updated_by" => $PROCESSED["updated_by"]);
                        if ($db->AutoExecute("group_organisations", $entry, "INSERT")) {
                            add_success(sprintf($translate->_("You have successfully added new group[cohort] <strong>%s</strong> to the system.<br />"), $PROCESSED["group_name"]));
                        } else {
                            add_error(sprintf($translate->_("There was an error while trying to add the <strong>Group</strong> %s. <br /><br />The system administrator was informed of this error; please try again later."), $PROCESSED["group_name"]));
                            application_log("error", "Unable to insert a new group organisation for group_id [" . $this->group_id . "[. Database said: " . $db->ErrorMsg());
                        }

                        if (isset($_POST["students"]) && $_POST["students"]) {
                            foreach ($_POST["students"] as $proxy_id) {
                                if ($tmp_input = clean_input($proxy_id, array("trim", "int"))) {
                                    $proxy_ids[] = $tmp_input;
                                }
                            }
                        }

                        if ($proxy_ids) {
                            $count = $group->addMembers($proxy_ids);
                            if ($count === false) {
                                add_error($translate->_("Failed to insert this learner into the group. Please contact a system administrator if this problem persists."));
                            } else {
                                add_success(sprintf($translate->_("Added <strong>%d</strong> members to Group <strong>%s</strong>."), $count, $PROCESSED["group_name"]));
                            }
                        }
                    } else {
                        add_error(sprintf($translate->_("There was an error while trying to add the <strong>Group</strong> %s.<br /> The system administrator was informed of this error; please try again later."), $PROCESSED["group_name"]));
                        break;
                    }

                    $GROUP_ID = $group->getID();

                    switch ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["post_action"]) {
                        case "new" :
                            $url .= "?section=add";
                            $msg = sprintf($translate->_("You will now be redirected to add another group ; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"%s\" style=\"font-weight: bold\">click here</a> to continue."), $url);
                            break;
                        case "content" :
                            $url .= "?section=edit&ids=$GROUP_ID";
                            $msg = sprintf($translate->_("You will now be redirected to edit the group ; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"%s\" style=\"font-weight: bold\">click here</a> to continue."), $url);
                            break;
                        case "index" :
                        default :
                            $msg = sprintf($translate->_("You will now be redirected to the group index ; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"%s\" style=\"font-weight: bold\">click here</a> to continue."), $url);
                            break;
                    }

                    $ONLOAD[] = "setTimeout('window.location=\\'${url}\\'', 5000)";
                    add_notice($msg);
                    application_log("success", "Added new cohort group [" . $GROUP_ID . "] \"" . $PROCESSED["group_name"] . "\" to org_id [" . $PROCESSED["organisation_id"] . "] the system.");
                }
            }

            if ($ERROR) {
                $STEP = 1;
            }
            break;
		case 1 :
		default :
			continue;
		break;
	}

	// Display Content

	echo "<h1>" . $translate->_("Add Cohort") . "</h1>\n";

	switch ($STEP) {
		case 2 :
			display_status_messages();
		break;
		case 1 :
		default :
			$HEAD[] = "<script type=\"text/javascript\" src=\"${url}/javascript/elementresizer.js\"></script>\n";

			if ($ERROR) {
				echo display_error();
			}

			$post_action = isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["post_action"])?$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["post_action"]:"index";
			?>

           <form id="frmSubmit" class="form-horizontal" action="<?php echo $url; ?>?section=add&amp;step=2" method="post">
				<div class="row-fluid"><h2><?php echo $translate->_("Cohort Details"); ?></h2></div>
				
                <div class="control-group">
                    <label class="form-required control-label" for="group_name"><?php echo $translate->_("Cohort Name"); ?></label>
                    <div class="controls">
                        <input type="text" id="group_name" name="group_name" value="<?php echo html_encode($PROCESSED["group_name"]); ?>" maxlength="55" />
                    </div>
                </div>
                <!--- End control-group ---->
				
                <div class="control-group">
                    <label class="form-required control-label" for="group_type"><?php echo $translate->_("Cohort Type"); ?></label>
                    <div class="controls">
							<select id="group_type" name="group_type" style="width: 250px">
							<option value="0"><?php echo $translate->_("-- Select a cohort type --"); ?></option>
                            <option value="cohort"<?php echo ($PROCESSED["group_type"] == "cohort" ? " selected=\"selected\"" : "") . ">" . $translate->_("Cohort"); ?></option>
							<option value="course_list"<?php echo ($PROCESSED["group_type"] == "course_list" ? " selected=\"selected\"" : "") . ">" . $translate->_("Course List"); ?></option>
							</select>
                    </div>
                </div>
                <!--- End control-group ---->
				
                <div id="course_select_row" class="control-group"<?php echo ($PROCESSED["group_type"] == "course_list" ? "" : " style=\"display:none;\""); ?>>
                    <label class="form-required control-label" for="group_type"><?php echo $translate->_("Course"); ?></label>
                    <div class="controls">
                        <select id="course_id" name="course_id" style="width: 250px">
                        <option value="0"><?php echo $translate->_("-- Select a course --"); ?></option>
                        <?php
                        $courses = courses_fetch_courses(true);
                        if ($courses) {
                            foreach ($courses as $course) {
                                ?>
                                <option value="<?php echo $course["course_id"];?>"<?php echo (isset($PROCESSED["group_value"]) && $PROCESSED["group_value"] == $course["course_id"])?' selected="selected"':'';?>><?php echo $course["course_code"]." : ".$course["course_name"];?></option>
                                <?php
                            }
                        }
                        ?>
                        </select>
                    </div>
                </div>
                <!--- End control-group ---->
				
               <div id="additions">
					<div class="row-fluid"><h2><?php echo $translate->_("Add Learners"); ?></h2></div>

					<div class="control-group member-add-type" id="existing-member-add-type">
						<label class="control-label" for="choose-members-btn"><?php echo $translate->_("Select Learners"); ?></label>
                        <div class="controls">
                            <button id="choose-members-btn" class="btn btn-search-filter" style="min-width: 220px; text-align: left;"><?php echo $translate->_("Browse All Learners"); ?> <i class="icon-chevron-down btn-icon pull-right"></i></button>
                        </div>
                    </div>
	                <!--- End control-group ---->				
				</div>

               <div class="row-fluid">
					<input type="button" class="btn" value="<?php echo $DEFAULT_TEXT_LABELS["btn_cancel"]; ?>" onclick="window.location='<?php echo $url; ?>'" />
					<div class="pull-right">
						<span class="content-small"><?php echo $translate->_("After saving"); ?></span>
						<select id="post_action" name="post_action">
							<option value="content"<?php echo ($post_action == "content" ? " selected=\"selected\"" : ""); ?>><?php echo $translate->_("Edit Cohort"); ?></option>
							<option value="new"<?php echo ($post_action == "new" ? " selected=\"selected\"" : ""); ?>><?php echo $translate->_("Add another cohort"); ?></option>
							<option value="index"<?php echo ($post_action == "index" ? " selected=\"selected\"" : ""); ?>><?php echo $translate->_("Return to group list"); ?></option>
						</select>
						<input type="submit" class="btn btn-primary" value="<?php echo $translate->_("Proceed"); ?>" />
					</div>
               </div>
           </form>

			<script type="text/javascript">
				jQuery(document).ready(function () {
				    jQuery("#group_type").change(function() {
                        if (jQuery(this).val() == "course_list") {
                            jQuery("#course_select_row").show();
                        } else {
                            jQuery("#course_select_row").hide();
                        }
                    });

                    jQuery("#choose-members-btn").advancedSearch({
                        api_url: "<?php echo ENTRADA_URL . "/admin/" . $MODULE . "?section=api-members"; ?>",
                        build_selected_filters: false,
                        reset_api_params: true,
                        resource_url: ENTRADA_URL,
                        filter_component_label: "Users",
                        select_all_enabled: true,
                        filters: {},
                        no_results_text: "<?php echo $translate->_("No users found matching the search criteria"); ?>",
                        list_data: {
                            selector: "#group_members_list",
                            background_value: "url(../images/list-community.gif) no-repeat scroll 0 4px transparent"
                        },
                        parent_form: jQuery("#frmSubmit"),
                        width: 300,
                        async: false,
                        target_name: "students"
                    });

                    jQuery.getJSON("<?php echo ENTRADA_URL . "/admin/" . $MODULE . "?section=api-members"; ?>", {method: "get-groups"} , function (json) {
                        jQuery.each(json.data, function (key, value) {
                            jQuery("#choose-members-btn").data("settings").filters[value.target_label] = {
                                label: value.target_label,
                                api_params: {
                                    group_id: value.target_id,
                                    parent_name: value.parent_name
                                },
                                data_source: "get-roles",
                                secondary_data_source: "get-role-members"
                            }
                        });
                    });
				});
			</script>
			<?php
		break;
	}
}
?>
