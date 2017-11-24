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
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if (!defined("IN_CLERKSHIP")) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!(bool) $_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed('electives', 'update')) {
	$ONLOAD[]	= "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	$ERROR++;
	$ERRORSTR[]	= "You do not have the permissions required to use this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.";

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/calendar/script/xc2_timestamp.js\"></script>\n";
	
	$HEAD[] = "<link href=\"".ENTRADA_URL."/css/tabpane.css?release=".html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />\n";
	$HEAD[] = "<style type=\"text/css\">.dynamic-tab-pane-control .tab-page { height:auto; }</style>\n";

	$HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/tabpane/tabpane.js?release=".html_encode(APPLICATION_VERSION)."\"></script>\n";
	
	if (!isset($_POST["action"]) || $_POST["action"] != "results") {
		?>
		<div class="tab-pane" id="clerk-admin-tabs">
		<?php
	}
	
	$query = "	SELECT a.*
				FROM `".CLERKSHIP_DATABASE."`.`events` AS a
				JOIN `".CLERKSHIP_DATABASE."`.`electives` AS b
				ON a.`event_id` = b.`event_id`
				WHERE a.`event_type`= 'elective'
				AND a.`event_status` = 'approval'
				ORDER BY a.`event_start` ASC";
	$results = $db->GetAll($query);
	if ($results) {
		if ($ERROR) {
			echo display_error();
		}

		if (!isset($_POST["action"]) || $_POST["action"] != "results") {
			?>
			<div class="tab-page">
				<h3 class="tab">Electives Pending</h3>
                <?php
                $total_rows = count($results);
                $total_pages = (int)($total_rows / $_SESSION[APPLICATION_IDENTIFIER]["clerkship"]["pp"]) + ($total_rows % $_SESSION[APPLICATION_IDENTIFIER]["clerkship"]["pp"] > 0 ? 1 : 0);
                /**
                 * Check if pv variable is set and see if it's a valid page, other wise page 1 it is.
                 */
                if (isset($_GET["pv"])) {
                    $page_current = (int) trim($_GET["pv"]);

                    if (($page_current < 1) || ($page_current > $total_pages)) {
                        $page_current = 1;
                    }
                } else {
                    $page_current = 1;
                }
                if ($total_pages > 1) {
                    $pagination = new Entrada_Pagination($page_current, $_SESSION[APPLICATION_IDENTIFIER]["clerkship"]["pp"], $total_rows, ENTRADA_URL."/admin/".$MODULE, replace_query());
                    echo $pagination->GetPageBar("normal", "right", false);
                    echo $pagination->GetResultsLabel();
                }
                ?>

				<table class="table table-bordered table-striped" cellspacing="0" summary="List of Clerkship Rotations">
				<colgroup>
					<col class="modified" />
					<col class="date-small" />
					<col class="date-smallest" />
					<col class="region" />
					<col class="title" />
				</colgroup>
				<thead>
					<tr>
						<th class="modified">&nbsp;</th>
						<th class="date-small">Student</th>
						<th class="date-smallest">Start Date</th>
						<th class="region">Region</th>
						<th class="title">Category Title</th>
					</tr>
				</thead>
				<tbody>
				<?php
                for ($i = (($page_current - 1) * $_SESSION[APPLICATION_IDENTIFIER]["clerkship"]["pp"]); $i < (($page_current * $_SESSION[APPLICATION_IDENTIFIER]["clerkship"]["pp"]) < $total_rows ? ($page_current * $_SESSION[APPLICATION_IDENTIFIER]["clerkship"]["pp"]) : $total_rows); $i++) {
					$click_url	= ENTRADA_URL."/admin/clerkship/electives?section=edit&id=".$results[$i]["event_id"];

					if (!isset($results[$i]["region_name"]) || $results[$i]["region_name"] == "") {
						$result_region = clerkship_get_elective_location($results[$i]["event_id"]);
						$results[$i]["region_name"] = $result_region["region_name"];
						$results[$i]["city"]		   = $result_region["city"];
					} else {
						$results[$i]["city"] = "";
					}

					$getStudentsQuery	= "SELECT `etype_id`
					FROM ".CLERKSHIP_DATABASE.".`event_contacts`
					WHERE `event_id` = ".$db->qstr($results[$i]["event_id"]);

					$getStudentsResults = $db->GetAll($getStudentsQuery);
					foreach ($getStudentsResults as $student) {

						$name	= get_account_data("firstlast", $student["etype_id"]);

						echo "<tr>\n";
						echo "	<td class=\"modified\">&nbsp</td>\n";
						echo "	<td class=\"date-small\"><a href=\"".$click_url."\">".$name."</a></td>\n";
						echo "	<td class=\"date-smallest\"><a href=\"".$click_url."\">".date("M d/Y", $results[$i]["event_start"])."</a></td>\n";
						echo "	<td class=\"region\"><a href=\"".$click_url."\">".($result["city"] == "" ? html_encode(limit_chars(($results[$i]["region_name"]), 30)) : $results[$i]["city"])."</a></td>\n";
						echo "	<td class=\"title\"><a href=\"".$click_url."\">".limit_chars(html_decode($results[$i]["event_title"]), 55, true, false)."</a></td>\n";
						echo "</tr>\n";
					}
				}
				?>
				</tbody>
				</table>
			</div>
			<?php
		}
	}
			// Setup internal variables.
			$DISPLAY		= true;
			
			if ($DISPLAY) {
				if (isset($_GET["gradyear"]) && (($_GET["gradyear"]) || ($_GET["gradyear"] === "0"))) {
					$GRADYEAR	= trim($_GET["gradyear"]);
					@app_setcookie("student_search[gradyear]", trim($_GET["gradyear"]));
				} elseif (isset($_POST["gradyear"]) && (($_POST["gradyear"]) || ($_POST["gradyear"] === "0"))) {
					$GRADYEAR	= trim($_POST["gradyear"]);
					@app_setcookie("student_search[gradyear]", trim($_POST["gradyear"]));
				} elseif (isset($_COOKIE["student_search"]["gradyear"])) {
					$GRADYEAR = $_COOKIE["student_search"]["gradyear"];
				} else {
					$GRADYEAR = 0;	
				}
				
				switch (isset($_POST["action"]) && $_POST["action"]) {
					case "results" :
						?>
						<div class="content-heading">Student Search Results</div>
						<?php
                        if (((isset($_GET["year"]) && trim($_GET["year"]) != "") || (isset($_POST["year"]) && trim($_POST["year"]) != ""))) {
							if (trim($_POST["year"]) != "") {
								$query_year = trim($_POST["year"]);
							} else {
								$query_year = trim($_GET["year"]);
							}
							
							$query = "SELECT a.*, a.`id` AS `proxy_id`, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, b.`account_active`, b.`access_starts`, b.`access_expires`, b.`last_login`, b.`role`, b.`group`, d.`group_name`
											FROM `".AUTH_DATABASE."`.`user_data` AS a
											LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
											ON b.`user_id` = a.`id`
											AND b.`app_id` IN (".AUTH_APP_IDS_STRING.")
											JOIN `group_members` AS c
											ON a.`id` = c.`proxy_id`
											AND c.`member_active` = 1
											JOIN `groups` AS d
											ON c.`group_id` = d.`group_id`
											AND d.`group_active` = 1
											WHERE b.`app_id` IN (".AUTH_APP_IDS_STRING.")
											AND b.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
											AND b.`group` = 'student'
											AND d.`group_id` = ".$db->qstr($query_year)."
											GROUP BY a.`id`
											ORDER BY `fullname` ASC";
							$results	= $db->GetAll($query);

							if ($results) {
								$counter	= 0;
								$total	= count($results);
								$split	= (round($total / 2) + 1);
                                $student_classes = array();
                                $active_cohorts = groups_get_all_cohorts($ENTRADA_USER->getActiveOrganisation());
                                if (isset($active_cohorts) && !empty($active_cohorts)) {
                                    foreach ($active_cohorts as $cohort) {
                                        $student_classes[$cohort["group_id"]] = $cohort["group_name"];
                                    }
                                }
								echo "There are a total of <b>".$total."</b> student".(($total != "1") ? "s" : "")." in the <b>".checkslashes(trim($student_classes[$query_year]))."</b>. Please choose a student you wish to work with by clicking on their name, or if you wish to add an event to multiple students simply check the checkbox beside their name and click the &quot;Add Mass Event&quot; button.";
			
								echo "<form id=\"clerkship_form\" action=\"".ENTRADA_URL."/admin/clerkship/electives?section=add_core\" method=\"post\">\n";
								echo "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">\n";
								echo "<tr>\n";
								echo "	<td style=\"vertical-align: top\">\n";
								echo "		<ol start=\"1\">\n";
								foreach ($results as $result) {
									
									$elective_weeks = clerkship_get_elective_weeks($result["proxy_id"]);
									$remaining_weeks = (int)$CLERKSHIP_REQUIRED_WEEKS - (int)$elective_weeks["approved"];
									
									switch (htmlentities($_POST["qualifier"])) {
										case "*":
										default:
											$show 			= true;
											$weeksOutput 	= "";
											$noResults		= "No Results";
											break;
										case "deficient":
											if ($remaining_weeks > 0) {
												$show 			= true;
												$weeksOutput 	= " <span class=\"content-small\">(".$remaining_weeks." weeks remaining)</span>";									
											} else {
												$show 			= false;
											}
											$noResults		= "There are no students in the class of <b>".checkslashes(trim($query_year))."</b> that do not have 14 weeks of electives approved in the system.";
											break;
										case "attained":
											if ($remaining_weeks <= 0) {
												$show 			= true;
												$weeksOutput 	= "";
											} else {
												$show 			= false;
											}
											$noResults		= "There are no students in the class of <b>".checkslashes(trim($query_year))."</b> that have 14 weeks of electives approved in the system.";
											break;
									}
									
									if ($show) {
										$counter++;
										if ($counter == $split) {
											echo "		</ol>\n";
											echo "	</td>\n";
											echo "	<td style=\"vertical-align: top\">\n";
											echo "		<ol start=\"".$split."\">\n";
										}
										echo "	<li><input type=\"checkbox\" name=\"ids[]\" value=\"".$result["proxy_id"]."\" />&nbsp;<a href=\"".ENTRADA_URL."/admin/clerkship/clerk?ids=".$result["proxy_id"]."\" style=\"font-weight: bold\">".$result["fullname"]."</a>".$weeksOutput."</li>\n";
									}
								}
								
								if ($counter == 0) {
									echo "	<li>".$noResults."</li>\n";
								}
								echo "		</ol>\n";
								echo "	</td>\n";
								echo "</tr>\n";
								echo "<tr>\n";
								echo "	<td colspan=\"3\" style=\"border-top: 1px #333333 dotted; padding-top: 5px\">\n";
								echo "		<ul type=\"none\">\n";
								echo "		<li><input type=\"checkbox\" name=\"selectall\" value=\"1\" onClick=\"selection(this.form['ids[]'])\" />&nbsp;";
								echo "		<input type=\"button\" value=\"Add Mass Elective\" class=\"btn\" onclick=\"$('clerkship_form').action = '".ENTRADA_URL."/admin/clerkship/electives?section=add_elective'; $('clerkship_form').submit();\"/>\n";
								echo "		<input type=\"button\" value=\"Add Mass Core\" class=\"btn\" style=\"display: inline; margin-left: 10px;\" onclick=\"$('clerkship_form').action = '".ENTRADA_URL."/admin/clerkship/electives?section=add_core'; $('clerkship_form').submit();\"/></li>\n";
								echo "		</ul>\n";
								echo "	</td>\n";
								echo "</tr>\n";
								echo "</table>\n";
								echo "</form>\n";
							} else {
								$ERROR++;
								$ERRORSTR[] = "Unable to find students in the database with a graduating year of <b>".trim($query_year)."</b>. It's possible that these students are not yet added to this system, so please check the User Management module.";
			
								echo "<br />";
								echo display_error($ERRORSTR);
							}
						} elseif (trim($_GET["name"]) != "" || trim($_POST["name"]) != "") {
							if (trim($_POST["name"]) != "") {
								$query_name = trim($_POST["name"]);
							} else {
								$query_name = trim($_GET["name"]);
							}
                            $query	= "SELECT a.`id` AS `proxy_id`, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, a.`grad_year` AS `gradyear`
                                        FROM `".AUTH_DATABASE."`.`user_data` AS a
                                        LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS b
                                        ON b.`user_id` = a.`id`
                                        WHERE b.`app_id` = ".$db->qstr(AUTH_APP_ID)."
                                        AND CONCAT_WS(' ', a.`firstname`, a.`lastname`) LIKE ".($db->qstr("%".trim($query_name)."%"))."
                                        AND `group` = 'student'
                                        ORDER BY a.`lastname`, a.`firstname` ASC";
							$results	= $db->GetAll($query);
							if ($results) {
								$counter	= 0;
								$total	= count($results);
								$split	= (round($total / 2) + 1);
								
								echo "There are a total of <b>".$total."</b> student".(($total != "1") ? "s" : "")." that match the search term of <b>".checkslashes(trim($query_name), "display")."</b>. Please choose a student you wish to work with by clicking on their name, or if you wish to add an event to multiple students simply check the checkbox beside their name and click the &quot;Add Mass Event&quot; button.";
			
								echo "<form id=\"clerkship_form\" action=\"".ENTRADA_URL."/admin/clerkship/electives?section=add_core\" method=\"post\">\n";
								echo "<table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">\n";
								echo "<tr>\n";
								echo "	<td style=\"vertical-align: top\">\n";
								echo "		<ol start=\"1\">\n";
								foreach ($results as $result) {
									$counter++;
									if ($counter == $split) {
										echo "		</ol>\n";
										echo "	</td>\n";
										echo "	<td style=\"vertical-align: top\">\n";
										echo "		<ol start=\"".$split."\">\n";
									}
									echo "	<li><input type=\"checkbox\" name=\"ids[]\" value=\"".$result["proxy_id"]."\" />&nbsp;<a href=\"".ENTRADA_URL."/admin/clerkship/clerk?ids=".$result["proxy_id"]."\" style=\"font-weight: bold\">".$result["fullname"]."</a> <span class=\"content-small\">(Class of ".$result["gradyear"].")</span></li>\n";
								}
								echo "		</ol>\n";
								echo "	</td>\n";
								echo "</tr>\n";
								echo "<tr>\n";
								echo "	<td colspan=\"3\" style=\"border-top: 1px #333333 dotted; padding-top: 5px\">\n";
								echo "		<ul type=\"none\">\n";
								echo "		<li><input type=\"checkbox\" name=\"selectall\" value=\"1\" onClick=\"selection(this.form['ids[]'])\" />&nbsp;";
								echo "		<input type=\"button\" value=\"Add Mass Elective\" class=\"btn\" onclick=\"$('clerkship_form').action = '".ENTRADA_URL."/admin/clerkship/electives?section=add_elective'; $('clerkship_form').submit();\"/>\n";
								echo "		<input type=\"button\" value=\"Add Mass Core\" class=\"btn\" style=\"display: inline; margin-left: 10px;\" onclick=\"$('clerkship_form').action = '".ENTRADA_URL."/admin/clerkship/electives?section=add_core'; $('clerkship_form').submit();\"/></li>\n";
								echo "		</ul>\n";
								echo "	</td>\n";
								echo "</tr>\n";
								echo "</table>\n";
								echo "</form>\n";
							} else {
								$ERROR++;
								$ERRORSTR[] = "Unable to find any students in the database matching <b>".checkslashes(trim($query_name), "display")."</b>. It's possible that the student you're looking for is not yet added to this system, so please check the User Management module.";
			
								echo "<br />";
								echo display_error($ERRORSTR);
							}
						} else {
							$ERROR++;
							$ERRORSTR[] = "You must search either by graduating year or by students name at this time, please try again.";
							
							echo "<br />";
							echo display_error($ERRORSTR);
						}
					break;
					default :
						?>
					<div class="tab-page">
						<h3 class="tab">Student Search</h3>
						<span class="content-subheading">Graduating Year</span>
						<form action="<?php echo ENTRADA_URL; ?>/admin/clerkship" method="post">
                            <input type="hidden" name="action" value="results" />
                            <div class="control-group">
                                <label class="control-label">Select an elective qualifier:</label>
                                <div class="controls">
                                    <select name="qualifier" style="width: 205px">
                                        <option value="*">All</option>
                                        <option value="deficient">Deficient</option>
                                        <option value="attained">Attained</option>
                                    </select>
                                </div>
                            </div>
                            <?php
                            $student_classes = array();
                            $active_cohorts = groups_get_all_cohorts($ENTRADA_USER->getActiveOrganisation());
                            if (isset($active_cohorts) && !empty($active_cohorts)) {
                                foreach ($active_cohorts as $cohort) {
                                    $student_classes[$cohort["group_id"]] = $cohort["group_name"];
                                }
                            }
                            ?>
                            <div class="control-group">
                                <label class="control-label">Select the graduating year you wish to view students in:</label>
                                <div class="controls">
                                    <select name="year" style="width: 205px">
                                    <option value="">-- Select Graduating Year --</option>
                                    <?php
                                    if (isset($student_classes) && !empty($student_classes)) {
                                        foreach ($student_classes as $group_id => $class) {
                                            echo "<option value=\"".$group_id."\">".html_encode($class)."</option>\n";
                                        }
                                    }
                                    ?>
                                    </select>
                                </div>
                            </div>

                            <input type="submit" value="Proceed" class="btn btn-primary"/>
                            <hr/>
                        </form>
						<form action="<?php echo ENTRADA_URL; ?>/admin/clerkship" method="post">
                            <input type="hidden" name="action" value="results" />
                            <span class="content-subheading">Student Finder</span>
                            <div class="control-group">
                                <label class="control-label">Enter the first or lastname of the student:</label>
                                <div class="controls">
                                    <input type="text" name="name" value="" style="margin-bottom:0"/>
                                </div>
                            </div>
                            <input type="submit" value="Search" class="btn btn-primary" />
						</form>
					</div>
						<?php
					break;
				}
						
				$query		= "SELECT a.*, CONCAT_WS(' ', b.`firstname`, b.`lastname`) as `fullname`, c.`rotation_title`
								FROM `".CLERKSHIP_DATABASE."`.`logbook_overdue` AS a
								LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
								ON a.`proxy_id` = b.`id`
								LEFT JOIN `".CLERKSHIP_DATABASE."`.`global_lu_rotations` AS c
								ON a.`rotation_id` = c.`rotation_id`
								ORDER BY `logged_completed` DESC, `fullname` ASC";
				
				$results = $db->GetAll($query);
				
				if ($results && (!isset($_POST["action"]) || $_POST["action"] != "results")) {
					?>
					<div class="tab-page">
						<h3 class="tab">Clerks with overdue logging</h3>
						<br />		
						<table class="table table-bordered table-striped" cellspacing="0" summary="List of Clerkship Rotations">
						<colgroup>
							<col class="modified" />
							<col class="date-small" />
							<col class="title" />
							<col class="date-small" />
							<col class="date-small" />
						</colgroup>				
						<thead>
							<tr>
								<td class="modified">&nbsp;</td>
								<td class="date-small">Student</td>
								<td class="title">Rotation</td>
								<td class="date-small">Logged Objectives</td>
								<td class="date-small">Required Objectives</td>
							</tr>
						</thead>
						<tbody>
						<?php
						foreach ($results as $result) {
							$click_url = ENTRADA_URL."/admin/clerkship?section=clerk&ids=".$result["proxy_id"];
							echo "<tr>\n";
							echo "	<td class=\"modified\">&nbsp</td>\n";
							echo "	<td class=\"date-small\"><a href=\"".$click_url."\" >".$result["fullname"]."</a></td>\n";
							echo "	<td class=\"title\"><a href=\"".$click_url."\" >".$result["rotation_title"]."</a></td>\n";
							echo "	<td class=\"date-small\"><a href=\"".$click_url."\" >".$result["logged_completed"]."</a></td>\n";
							echo "	<td class=\"date-small\"><a href=\"".$click_url."\" >".$result["logged_required"]."</a></td>\n";
							echo "</tr>\n";
						}
						?>
						</tbody>
						</table>
						<br /><br />
					</div>
					<?php	
				}
				if (!isset($_POST["action"]) || $_POST["action"] != "results") {
				?>
			</div>
				<?php
				}
	} else {
		// Display the errors.
		echo display_error($ERRORSTR);
	}
}
?>