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
 * @copyright Copyright 2011 University of Calgary. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_GROUPS"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed('group', 'create', false)) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	ini_set('auto_detect_line_endings',true);

	$HEAD[] = "<script type=\"text/javascript\" >var ENTRADA_URL = '". ENTRADA_URL ."';</script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/AutoCompleteList.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js\"></script>";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css\" />";

	echo "<script language=\"text/javascript\">var DELETE_IMAGE_URL = '".ENTRADA_URL."/images/action-delete.gif';</script>";

	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/groups?".replace_query(array("section" => "add")), "title" => $translate->_("Adding Cohort"));

	$group_type = "individual";
	$group_populate = "group_number";
	$group_active = "true";
	$number_of_groups ="";
	$populate = 0;
	$GROUP_IDS = array();
    $PROCESSED = array();

	echo "<h1>" . $translate->_("Add Cohort") . "</h1>\n";

	// Error Checking
	switch ($STEP) {
		case 2 :
			/**
			 * Get the active organisation_id and add it to the PROCESSED array.
			 */
			$PROCESSED["organisation_id"] = $ENTRADA_USER->getActiveOrganisation();

			/**
			 * Required field "group_name" / Cohort Name.
			 */
			if ((isset($_POST["group_name"])) && ($group_name = clean_input($_POST["group_name"], array("notags", "trim")))) {
				$PROCESSED["group_name"] = $group_name;
			} else {
				add_error("The <strong>Cohort Name</strong> field is required.");
			}

			/**
			 * Required field "group_type" / Cohort Type.
			 */
			if ((isset($_POST["group_type"])) && ($group_type = clean_input($_POST["group_type"], array("trim"))) && in_array($group_type, array("course_list", "cohort"))) {
				$PROCESSED["group_type"] = $group_type;
			} else {
				add_error("The <strong>Cohort Type</strong> field is required.");
			}

			/**
			 * Required field "course_id" / Course ID (when group_type == course_list)
			 */
			if (isset($PROCESSED["group_type"]) && $PROCESSED["group_type"] == 'course_list') {
				if (isset($_POST["course_id"]) && $course_id = clean_input($_POST["course_id"], array("int"))) {
					$PROCESSED["group_value"] = $course_id;
				} else {
					add_error("The <strong>Course</strong> field is required for course lists.");
				}
			} else {
				$PROCESSED["group_value"] = false;
			}

			if (isset($_POST["post_action"])) {
				switch ($_POST["post_action"]) {
					case "content" :
						$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = "content";
					break;
					case "new" :
						$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = "new";
					break;
					case "index" :
					default :
						$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = "index";
					break;
				}
			} else {
				$_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] = "content";
			}

			$proxy_ids = array();

			if (isset($_POST["students"]) && $_POST["students"]) {
				foreach ($_POST["students"] as $proxy_id) {
					if ($tmp_input = clean_input($proxy_id, array("trim", "int"))) {
						$proxy_ids[] = $tmp_input;
					}
				}
			}

            $PROCESSED["entrada_only"] = 1;
            $PROCESSED["created_date"] = time();
            $PROCESSED["created_by"] = $ENTRADA_USER->getID();
			$PROCESSED["updated_date"] = time();
			$PROCESSED["updated_by"] = $ENTRADA_USER->getID();

			if (!$ERROR) { 
                $query = "SELECT a.`group_id`
                         FROM `groups` AS a
                         JOIN `group_organisations` AS b
                         ON a.`group_id` = b.`group_id`
                         AND b.`organisation_id` = ".$db->qstr($PROCESSED["organisation_id"])."
                         WHERE a.`group_name` = ".$db->qstr($PROCESSED["group_name"]);
				$result = $db->GetRow($query);
				if ($result) {
					add_error("Lucky you, the <strong>cohort name</strong> you are trying to create already exists.");
				} else {
					if (!$db->AutoExecute("groups", $PROCESSED, "INSERT")) {
						add_error("There was an error while trying to add the <strong>Cohort</strong> ".$PROCESSED["group_name"].".<br /><br />The system administrator was informed of this error; please try again later.");
						application_log("error", "Unable to insert a new cohort ".$PROCESSED["group_name"].". Database said: ".$db->ErrorMsg());
					}

					$GROUP_ID = $db->Insert_Id();
					$PROCESSED["group_id"] = $GROUP_ID;
					if (!$db->AutoExecute("group_organisations", $PROCESSED, "INSERT")) {
						add_error("There was an error while trying to add the <strong>Cohort</strong> ".$PROCESSED["group_name"].".<br /><br />The system administrator was informed of this error; please try again later.");
						application_log("error", "Unable to insert a new group organisation for group_id [".$GROUP_ID."[. Database said: ".$db->ErrorMsg());
					} else {
						if (!empty($proxy_ids)) {
							foreach ($proxy_ids as $proxy_id) {
								$PROCESSED["proxy_id"] = $proxy_id;
								if (!$db->AutoExecute("group_members", $PROCESSED, "INSERT")) {
									add_error("Failed to insert this learner into the group. Please contact a system administrator if this problem persists.");
									application_log("error", "Error while inserting learner into database. Database server said: " . $db->ErrorMsg());
								}
							}
						}
					}
				}

				switch ($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"]) {
					case "new" :
						$url = ENTRADA_URL."/admin/groups?section=add";
						$msg = "You will now be redirected to add another group; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
					break;
					case "index" :
					default :
						$url = ENTRADA_URL."/admin/groups";
						$msg = "You will now be redirected to the group index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.";
					break;
				}

				add_success("You have successfully added <strong>".html_encode($PROCESSED["event_title"])."</strong> to the system.<br /><br />".$msg);
				$ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";

				application_log("success", "Added new cohort group [".$GROUP_ID." / ".$PROCESSED["group_name"]."] to org_id [".$PROCESSED["organisation_id"]."] the system.");
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
	switch ($STEP) {
		case 2 :
			display_status_messages();
		break;
		case 1 :
		default :
			$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/elementresizer.js\"></script>\n";

			if ($ERROR) {
				echo display_error();
			}
			?>

			<form id="frmSubmit" class="form-horizontal" action="<?php echo ENTRADA_URL; ?>/admin/groups?section=add&amp;step=2" method="post">
				<table style="width: 100%" cellspacing="0" cellpadding="2" border="0" summary="Adding Cohort">
					<colgroup>
						<col style="width: 3%" />
						<col style="width: 20%" />
						<col style="width: 77%" />
					</colgroup>
					<tfoot>
						<tr>
							<td colspan="3" style="padding-top: 25px">
								<table style="width: 100%" cellspacing="0" cellpadding="0" border="0">
									<tr>
										<td style="width: 25%; text-align: left">
											<input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/admin/groups'" />
										</td>
										<td style="width: 75%; text-align: right; vertical-align: middle">
											<span class="content-small">After saving:</span>
											<select id="post_action" name="post_action">
												<option value="new"<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "new") ? " selected=\"selected\"" : ""); ?>>Add another group</option>
												<option value="index"<?php echo (($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["post_action"] == "index") ? " selected=\"selected\"" : ""); ?>>Return to group list</option>
											</select>
											<input type="submit" class="btn btn-primary" value="Proceed" />
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</tfoot>
					<tr>
						<td colspan="3"><h2><?php echo $translate->_("Cohort Details"); ?></h2></td>
					</tr>
					<tr class="prefixR">
						<td></td>
						<td><label for="group_name" class="form-required"><?php echo $translate->_("Cohort Name"); ?></label></td>
						<td><input type="text" id="group_name" name="group_name" value="<?php echo html_encode($PROCESSED["group_name"]); ?>" maxlength="255" style="width: 45%" /></td>
					</tr>
					<tr>
						<td colspan="3">&nbsp;</td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<td><label for="group_type" class="form-required"><?php echo $translate->_("Cohort Type"); ?></label></td>
						<td>
							<select id="group_type" name="group_type" style="width: 250px">
								<option value="0">-- Select a cohort type --</option>
								<option value="course_list"<?php echo ($PROCESSED["group_type"] == "course_list" ? " selected=\"selected\"" : ""); ?>>Course list</option>
								<option value="cohort"<?php echo ($PROCESSED["group_type"] == "cohort" ? " selected=\"selected\"" : ""); ?>>Cohort</option>
							</select>
						</td>
					</tr>
					<tr id="course_select_row"<?php echo $PROCESSED["group_type"] == 'course_list'?'':' style="display:none;"';?>>
						<td>&nbsp;</td>
						<td><label for="group_type" class="form-required">Course</label></td>
						<td>
							<select id="course_id" name="course_id" style="width: 250px">
							<option value="0">-- Select a course --</option>
							<?php
							$courses = courses_fetch_courses(true);
							if ($courses) {
								foreach ($courses as $course){
									?><option value="<?php echo $course["course_id"];?>"<?php echo $PROCESSED["group_value"] == $course["course_id"]?' selected="selected"':'';?>><?php echo $course["course_code"]." : ".$course["course_name"];?></option><?php
								}
							} ?>
							</select>
						</td>
					</tr>
					<tr>
						<td colspan="3">&nbsp;</td>
					</tr>
					<tr>
						<td colspan="3">
							<br />
							<div id="additions">
								<h2 style="margin-top: 10px"><?php echo $translate->_("Add Learners"); ?></h2>
								<table style="margin-top: 1px; width: 100%" cellspacing="0" cellpadding="2" border="0" summary="<?php echo $translate->_("Add Learner"); ?>">
									<colgroup>
										<col style="width: 45%" />
										<col style="width: 10%" />
										<col style="width: 45%" />
									</colgroup>
									<tbody>
										<tr>
											<td colspan="3" style="vertical-align: top">
												If you would like to add users that already exist in the system to this group yourself, you can do so by clicking the checkbox beside their name from the list below.
												Once you have reviewed the list at the bottom and are ready, click the <strong>Proceed</strong> button at the bottom to complete the process.
											</td>
										</tr>
										<tr>
											<td colspan="2"></td>
											<td>
												<div id="group_name_title"></div>
											</td>
										</tr>
										<tr>
											<td colspan="2" style="vertical-align: top">
												<div class="member-add-type" id="existing-member-add-type">
													<label for="choose-members-btn" class="control-label"><?php echo $translate->_("Select Learners"); ?></label>
													<div class="controls">
														<button id="choose-members-btn" class="btn btn-search-filter" style="min-width: 220px; text-align: left;"><?php echo $translate->_("Browse All Learners"); ?> <i class="icon-chevron-down btn-icon pull-right"></i></button>
													</div>
												</div>
											</td>
										</tr>
									</tbody>
								</table>
							</div>
						</td>
					</tr>
				</table>
			</form>

			<script>
				jQuery(document).ready(function () {
					jQuery("#group_type").change(function () {
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
