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
 * @author Organisation: University of Calgary
 * @author Unit: Faculty of Medicine
 * @author Developer: Doug Hall<hall@ucalgary.ca>
 * @copyright Copyright 2017 University of Calgary. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_GROUPS"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed('group', 'update')) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/$MODULE\\'', 1000)";

	add_error(sprintf($translate->_("You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%s\"> %s </a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    $HEAD[] = "<script type=\"text/javascript\" >var ENTRADA_URL = '". ENTRADA_URL ."';</script>";
    $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js\"></script>";
    $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css\" />";

	$URL = ENTRADA_URL."/admin/$MODULE";
	
    // ERROR CHECKING
	switch ($STEP) {
		case "2" :
			if ((isset($_POST["add_group_id"])) && ((int) trim($_POST["add_group_id"]))) {
				$PROCESSED["group_id"] = (int) trim($_POST["add_group_id"]);
			} else {
				header("Location: $URL");
			}

            $proxy_ids = array();

			if (isset($_POST["students"]) && $_POST["students"]) {
				foreach ($_POST["students"] as $proxy_id) {
					if ($tmp_input = clean_input($proxy_id, array("trim", "int"))) {
						$proxy_ids[] = $tmp_input;
					}
				}
			}

            if ($proxy_ids) {
				$group = Models_Group::fetchRowByID($PROCESSED["group_id"]);
				if ($group) {
					$group->fromArray(array("updated_date" => time(), "updated_by" => $ENTRADA_USER->getID()));
					$count = $group->addMembers($proxy_ids);
					if ($count === false) {
						add_error($translate->_("Failed to insert this learner into the group. Please contact a system administrator if this problem persists."));
					} else {
						add_success(sprintf($translate->_("Added <strong>%d</strong> members to Group <strong>\"%s\"</strong>."), $count, $group->getGroupName()));
                    }
				} else {
					add_error($translate->_("There is no valid cohort / group associated with this identifies."));
				}
            }
			$STEP = 1;

		break;

		case "3" :
			if ((isset($_POST["group_type"])) && ($group_type = clean_input($_POST["group_type"], array("trim"))) && in_array($group_type, array("course_list", "cohort"))) {
				$PROCESSED["group_type"] = $group_type;
			} else {
				add_error($translate->_("The <strong>Cohort Type</strong> field is required."));
			}

			if (isset($PROCESSED["group_type"]) && $PROCESSED["group_type"] == 'course_list') {
				if (isset($_POST["course_id"]) && $course_id = clean_input($_POST["course_id"], array("int"))) {
					$PROCESSED["group_value"] = $course_id;
				} else {
					add_error($translate->_("The <strong>Course</strong> field is required for course lists."));
				}
			} else {
				$PROCESSED["group_value"] = NULL;
			}

			if (!$ERROR) {
				if (isset($_POST["group_id"]) && $group_id = clean_input($_POST["group_id"], array("int"))) {
					$PROCESSED["updated_date"] = time();
					$PROCESSED["updated_by"] = $ENTRADA_USER->getID();
					
					$group = Models_Group::fetchRowByID($group_id);

					if ($group) {
						if ($group->fromArray($PROCESSED)->update()) {
                            add_success(sprintf($translate->_("You have successfully updated <strong> %s </strong> to the system.<br /><br />"), $group->getGroupName()));
                        } else {
                            add_error(sprintf($translate->_("Unable to edit group types of group %s. Please contact a system administrator if this problem persists."), $group->getGroupName()));
                        }
					} else {
						add_error($translate->_("There is no valid cohort / group associated with this identifies."));
					}
				}
			}
			$STEP = 1;

			break;
		default :
			// No error checking for step 1.
		break;
	}

	// PAGE DISPLAY
	switch ($STEP) {
		case "2" :			// Step 2
            add_success($translate->_("You have successfully added this learner."));
			echo display_success($SUCCESSSTR);
		break;

		default :			// Step 1
            /**
             * Receives edit requests from both posts & gets
             */
			if (isset($PROCESSED["group_id"]) && (int)$PROCESSED["group_id"]) { // Current group to view
				$GROUP_ID = $PROCESSED["group_id"];
			} else {
				$GROUP_ID = 0;
			}
			if (isset($_GET["ids"])) { // Receive a group or groups
				$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["ids"] = explode(",",htmlentities($_GET["ids"]));
			} elseif (isset($_POST["checked"])) { // Receive groups
				$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["ids"] = $_POST["checked"];
			} elseif ((isset($_POST["group_id"])) && ((int) trim($_POST["group_id"]))) {
				$GROUP_ID = (int) trim($_POST["group_id"]);
			} elseif ((isset($_GET["id"])) && ((int) trim($_GET["id"]))) {
				$GROUP_ID = (int) trim($_GET["id"]);
			}

		    // Manage current set of group(s), quit if none
			if ((!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["ids"]) || !is_array($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["ids"])) || (!@count($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["ids"]))) {
				header("Location: $URL");
				exit;
			}

			$groups = Models_Group::fetchGroupsInList($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["ids"]);

			if (!$groups) {
				header("Location: $URL");
			}

			if (!$GROUP_ID) {
				$GROUP_ID = $groups{0}->getID(); // The first group;
			}
			
			/**
			 * Update requested order to sort by.
			 * Valid: asc, desc
			 */
			if (isset($_GET["so"])) {
				$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"] = ((strtolower($_GET["so"]) == "desc") ? "DESC" : "ASC");
			} else if (!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"])) {
				$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"] = "ASC";
			}
			
			/**
			 * Update requested column to sort by.
			 */
			if (isset($_GET["sb"])) {
				$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] = $_GET["sb"];
			} else if (!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"])) {
				$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] = "fullname";
			}

			/**
			 * Provide the queries with the columns to order by.
			 */
			switch ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"]) {
				case "grouprole" :
					$order_by = "ORDER BY `grouprole` ".strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"]);
				break;
				case "fullname" :
				default :
					$order_by = "ORDER BY `fullname` ".strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"]);
				break;
			}

			$selected = Models_Group::fetchRowByID($GROUP_ID);

			$ONLOAD[]	= "showgroup('".$selected->getGroupName()."',".$GROUP_ID.")";

			$BREADCRUMB[] = array("url" => "${URL}?section=edit", "title" => $translate->_("Edit Cohort"));

			echo "<h1>".$translate->_("Edit Cohort")."</h1>";
			echo "<h2>".sprintf($translate->_("Selected Cohort%s"), (count($groups)> 1 ? "s" : "")) ."</h2>";
			echo display_status_messages();
			?>
			<form class="form-horizontal" action="<?php echo $URL; ?>?section=edit&step=1" method="post" id="select-group-form">
				<input type="hidden" id="step" name="step" value="1" />
				<input type="hidden" id="group_id" name="group_id" value="" />
				<table class="table table-striped" cellspacing="1" cellpadding="1">
					<colgroup>
						<col style="width: 6%" />
						<col style="width: 54%" />
						<col style="width: 30%" />
						<col style="width: 10%" />
					</colgroup>
					<thead>
							<tr>
							<th></th>
								<th ><?php echo $translate->_("Cohort Name"); ?></th>
								<th ><?php echo $translate->_("Learners"); ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody>
					    <?php
                        foreach($groups as $group) {
                            $membership = $group->membership();
                            echo "<tr class=\"group".((!$group->getGroupActive()) ? " na" : (($membership["inactive"]) ? " np" : ""))."\">";
                            echo "	<td><input type=\"radio\" name=\"groups\" value=\"".$group->getID()."\" onclick=\"selectgroup(".$group->getID().",'".$group->getGroupName()."');\"".(($group->getID() == $GROUP_ID) ?" checked=\"checked\"" : "")."/></td>\n";
                            echo "	<td><a href=\"${URL}?section=edit&id=".$group->getID()."\" >".html_encode($group->getGroupName())."</a></td>";
                            echo "	<td><a href=\"${URL}?section=edit&id=".$group->getID()."\" >".$membership["members"]."</a></td>";
                            echo "	<td>
                                        <a href=\"${URL}?section=manage&gids=".$group->getID()."\"><img src=\"".ENTRADA_URL."/images/action-edit.gif\" width=\"16\" height=\"16\" alt=\"Rename Group\" title=\"Rename Group\" border=\"0\" /></a>&nbsp;
                                        <a href=\"${URL}?section=manage&ids=".$group->getID()."\"><img src=\"".ENTRADA_URL."/images/action-delete.gif\" width=\"16\" height=\"16\" alt=\"Delete/Activate Group\" title=\"Delete/Activate Group\" border=\"0\" /></a>
                                    </td>\n";
                            echo "</tr>";
                        }
					    ?>
					</tbody>
				</table>
			</form>

            <?php
			$members = $selected->members($order_by);
			echo "<h2 class=\"collapsable\" title=\"".$translate->_("Selected Group Members")."\">" . sprintf($translate->_("View Learner%s in <b>%s</b>"), count($members) > 1 ? "s": "", html_encode($selected->getGroupName())) ."</h2>";
			echo "<div id=\"selected-group-members\">";
			if ($members) {
                ?>
				<form action="<?php echo $URL; ?>?section=manage" method="post">
					<table class="table table-striped" cellspacing="1" cellpadding="1">
						<colgroup>
							<col style="width: 6%" />
							<col style="width: 54%" />
							<col style="width: 30%" />
							<col style="width: 10%" />
						</colgroup>
						<thead>
							<tr>
								<th>&nbsp;</th>
								<th class="title<?php echo (($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] == "fullname") ? " sorted".strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"]) : ""); ?>"><?php echo admin_order_link("fullname", "Full Name"); ?></th>
								<th class="grouprole<?php echo (($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["sb"] == "grouprole") ? " sorted".strtoupper($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["so"]) : ""); ?>"><?php echo admin_order_link("grouprole", "Group &amp; Role"); ?></th>
								<th>&nbsp;</th>
							</tr>
						</thead>
						<tbody>
						    <?php
                            $current_members_ids = array();

                            foreach ($members as $member) {
                                echo "<tr class=\"event" . (!$member["member_active"] ? " na" : "") . "\">";
                                echo "	<td><input type=\"checkbox\" class=\"delchk\" name=\"checked[]\" value=\"" . $member["gmember_id"] . "\" /></td>\n";
                                echo "	<td><a href=\"" . ENTRADA_URL . "/people?profile=" . $member["username"] . "\" >" . html_encode($member["fullname"]) . "</a></td>";
                                echo "	<td><a href=\"" . ENTRADA_URL . "/people?profile=" . $member["username"] . "\" >" . $member["grouprole"] . "</a></td>";
                                echo "	<td>
                                        <a href=\"${URL}?section=manage&mids=" . $member["gmember_id"] . "\"><img src=\"" . ENTRADA_URL . "/images/action-delete.gif\" width=\"16\" height=\"16\" alt=\"".$translate->_("Delete/Activate Member")."\" title=\"".$translate->_("Delete/Activate Member")."\" border=\"0\" /></a>
                                    </td>\n";
                                echo "</tr>";

                                $current_members_ids[] = $member["proxy_id"];
                            }
                            ?>
                        </tbody>
                    </table>

                    <div id="delbutton" style="padding-top: 15px; text-align: right;">
                        <input type="hidden" name="coa" id="coa" value=""/>
                        <input type="submit" class="btn btn-success" value="<?php echo $DEFAULT_TEXT_LABELS["activate"]; ?>" style="vertical-align: middle"
                               onClick="$('coa').value='activate'"/>
                        <input type="submit" class="btn btn-danger" value="<?php echo $translate->_("Delete") ."/". $DEFAULT_TEXT_LABELS["deactivate"]; ?>"
                               style="vertical-align: middle" onClick="$('coa').value='delete';"/>
                        <div class="muted">
                            <p>
                                <small><?php echo $translate->_("Select a learner to deactivate, activate or permanently delete from this cohort."); ?></small>
                            </p>
                        </div>
                    </div>
					<input type="hidden" name="members" value="1" />
				</form>
                <?php
				} else {
                    add_notice($translate->_("This Cohort has no Learners."));
                    echo display_notice();
				}
                ?>
			</div>
            <?php
            if (!isset($PROCESSED["group_type"])) {
				$PROCESSED["group_type"] = $group_type = $selected->getGroupType();
				$PROCESSED["group_value"] = $selected->getGroupValue();
            }
            ?>

			<h2 class=\"collapsable\"><?php echo $translate->_("Group Type"); ?></h2>
			<form id="edit-group-type" class="form-horizontal" action="<?php echo $URL; ?>?section=edit&step=3" method="post">
				<input type="hidden" id="group_id" name="group_id" value="<?php echo $GROUP_ID; ?>" />
				<div class="row-fluid space-below">
					<label for="group_type" class="control-label"><?php echo $translate->_("Group Type"); ?></label>
					<div class="controls">
						<select id="group_type" name="group_type" class="span5">
							<option value="0">-- Select a group type --</option>
							<option value="course_list"<?php echo ($PROCESSED["group_type"] == "course_list" ? " selected=\"selected\"" : ""); ?>><?php echo $translate->_("Course list"); ?></option>
							<option value="cohort"<?php echo ($PROCESSED["group_type"] == "cohort" ? " selected=\"selected\"" : ""); ?>><?php echo $translate->_("Cohort"); ?></option>
						</select>
					</div>
				</div>
				<div id="course_select_row" <?php echo $PROCESSED["group_type"] == "course_list"? "": "style=\"display:none;\"";?>>
					<label for="course_id" class="control-label">Course</label>
					<div class="controls">
						<select id="course_id" name="course_id" class="span5">
							<option value="0">-- Select a course --</option>
							<?php
							$courses = courses_fetch_courses(true);
							if ($courses) {
								foreach ($courses as $course){
									?><option value="<?php echo $course["course_id"];?>"<?php echo isset($PROCESSED["group_value"]) && $PROCESSED["group_value"] == $course["course_id"]?' selected="selected"':'';?>><?php echo $course["course_code"]." : ".$course["course_name"];?></option><?php
								}
							} ?>
						</select>
					</div>
				</div>
                <input type="submit" class="btn btn-primary pull-right" value="<?php echo $translate->_("Save"); ?>" />
			</form>

			<h2>Add Learners</h2>
            <div class="row-fluid">
                <div class="offset3" id="group_name_title"></div>
            </div>

            <form action="<?php echo $URL."?".replace_query(array("section" => "edit", "type" => "add", "step" => 2)); ?>" method="post" name="add-members-form" id="add-members-form" class="form-horizontal">
                <div class="row-fluid">
					<div class="member-add-type" id="existing-member-add-type">
						<label for="choose-members-btn" class="control-label"><?php echo $translate->_("Select Learners"); ?></label>
						<div class="controls">
							<button id="choose-members-btn" class="btn btn-search-filter" style="min-width: 220px; text-align: left;"><?php echo $translate->_("Browse All Learners"); ?> <i class="icon-chevron-down btn-icon pull-right"></i></button>
						</div>
					</div>
                    <div class="pull-right">
                        <input type="submit" class="btn btn-primary" value="<?php echo $translate->_("Proceed"); ?>" />
                    </div>
                </div>
                <input type="hidden" id="add_group_id" name="add_group_id" value="" />
            </form>

            <script>
				var excluded_target_ids = <?php echo isset($current_members_ids) ?  json_encode(array_unique($current_members_ids)) : 0; ?>;

                jQuery(document).ready(function () {
					jQuery('#delbutton .btn').hide();

					jQuery("#group_type").change(function () {
						if (jQuery(this).val() == "course_list") {
							jQuery("#course_select_row").show();
						} else {
							jQuery("#course_select_row").hide();
						}
					});

                	jQuery(".delchk").on("change", function () {
						if(jQuery(".delchk:checked").length > 0) {
							jQuery('#delbutton .btn').show();
							jQuery('#delbutton .muted').hide();
						} else {
							jQuery('#delbutton .btn').hide();
							jQuery('#delbutton .muted').show();
						}
					});

					jQuery("#choose-members-btn").advancedSearch({
						api_url: "<?php echo $URL . "?section=api-members"; ?>",
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
						parent_form: jQuery("#add-members-form"),
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

                function selectgroup(group,name) {
                    $('group_id').value = group;
                    $('select-group-form').submit();
                }

                function showgroup(name,group) {
                    $('group_name_title').update(new Element('div',{'style':'font-size:14px; font-weight:600; color:#153E7E'}).update(name));
                    $('add_group_id').value = group;
                }

            </script>
            <?php
		break;
	}
}