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
 * Serves as a dashboard type file for the Clerkship module.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/

if((!defined("PARENT_INCLUDED")) || (!defined("IN_CLERKSHIP"))) {
	exit;
} elseif((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
}

switch($_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]) {
	case "student" :
		switch($ACTION) {
			case "remove" :
				/**
				 * remove evaluations which they no longer want on their Clerkship tab.
				 */
				if((isset($_POST["mark_done"])) && (@is_array($_POST["mark_done"]))) {
					foreach($_POST["mark_done"] as $notification_id) {
						if($notification_id = (int) $notification_id) {
							$query	= "	SELECT a.`item_maxinstances` AS `remaining_entries`, b.`item_maxinstances` AS `total_entries`
										FROM `".CLERKSHIP_DATABASE."`.`notifications` AS a
										LEFT JOIN `".CLERKSHIP_DATABASE."`.`evaluations` AS b
										ON b.`item_id` = a.`item_id`
										WHERE a.`user_id` = ".$db->qstr($ENTRADA_USER->getActiveId())."
										AND (
											a.`notification_status` <> 'complete'
											OR a.`notification_status` <> 'cancelled'
										)
										AND a.`notification_id` = ".$db->qstr($notification_id)."
										AND b.`item_status` = 'published'";
							$result	= $db->GetRow($query);
							if($result) {
								$PROCESSED = array();
								$PROCESSED["item_maxinstances"] = 0;
								if($result["remaining_entries"] == $result["total_entries"]) {
									$PROCESSED["notification_status"] = "cancelled";
								} else {
									$PROCESSED["notification_status"] = "complete";
								}

								if(!$db->AutoExecute(CLERKSHIP_DATABASE.".notifications", $PROCESSED, "UPDATE", "`notification_id` = ".$db->qstr($notification_id)." AND `user_id` = ".$db->qstr($ENTRADA_USER->getActiveId()))) {
									application_log("error", "Unable to cancel notification_id [".$notification_id."]. Database said: ".$db->ErrorMsg());
								}
							}
						}
					}
				}
				$_SERVER["QUERY_STRING"] = replace_query(array("action" => false));
			break;
			default :
				continue;
			break;
		}

		/**
		 * Display available Clerkship evaluations to the student.
		 */
		clerkship_display_available_evaluations();

		?>
		<script type="text/javascript">
		function showEventDetails(event_id) {
			if ($('rotation-img-' + event_id).src == '<?php echo ENTRADA_URL; ?>/images/tree/plus0.gif') {
				$('event-' + event_id).show();
				$('rotation-img-'+event_id).src = '<?php echo ENTRADA_URL; ?>/images/tree/minus0.gif';
			} else {
				$('event-' + event_id).hide();
				$('rotation-img-' + event_id).src = '<?php echo ENTRADA_URL; ?>/images/tree/plus0.gif';
			}
		}
		</script>

		<h1><?php echo $translate->_("My Clerkship Schedule"); ?></h1>
		<?php
		if ($SHOW_LOGBOOK) {
			?>
			<div class="display-generic"><strong>Please take note</strong> of all clinical presentations in all rotations and keep track of each one you encounter at least once to reduce the chance of becoming deficient in later rotations. A list of all clinical presentations <a href="<?php echo ENTRADA_URL; ?>/clerkship?section=objectives">can be found here</a>.</div>
			<?php
		}
        ?>
		<div class="pull-right space-below">
            <a href="<?php echo ENTRADA_URL."/clerkship/electives?section=add";?>" class="btn btn-success"><i class="icon-plus-sign icon-white"></i> Add Elective</a>
            <?php
            if ($SHOW_LOGBOOK) {
                ?>
                <a href="<?php echo ENTRADA_URL."/clerkship/logbook?section=add";?>" class="btn btn-success"><i class="icon-plus-sign icon-white"></i> Log Encounter</a>
                <?php
            }
            ?>
        </div>
		<div class="clearfix"></div>

		<?php
		if ($SHOW_LOGBOOK || time() >= CLERKSHIP_SCHEDULE_RELEASE) {
			$query		= "	SELECT a.*, c.*, d.`category_name`
					FROM `".CLERKSHIP_DATABASE."`.`events` AS a
					LEFT JOIN `".CLERKSHIP_DATABASE."`.`event_contacts` AS b
					ON b.`event_id` = a.`event_id`
					LEFT JOIN `".CLERKSHIP_DATABASE."`.`regions` AS c
					ON c.`region_id` = a.`region_id`
							LEFT JOIN `".CLERKSHIP_DATABASE."`.`categories` AS d
							ON a.`category_id` = d.`category_id`
					WHERE b.`econtact_type` = 'student'
					AND b.`etype_id` = ".$db->qstr($ENTRADA_USER->getActiveId())."
					ORDER BY a.`event_start` ASC";
		$results = $db->GetAll($query);
	
		if($results) {
			?>
			<input type="hidden" id="selected-event" value="0" />
				<table class="table table-bordered table-striped" cellspacing="0" summary="List of Clerkship Schedule" style="border-collapse: separate;">
				<colgroup>
					<col class="modified" />
					<col class="type" style="width: 90px;" />
					<col class="date" />
					<col class="date" />
					<col class="region" style="width: 60px;" />
					<col class="title" />
				</colgroup>
				<thead>
					<tr>
						<th class="modified">&nbsp;</th>
						<th class="type"><?php echo $translate->_("Event Type"); ?></th>
						<th class="date-smallest">Start Date</th>
						<th class="date-smallest">Finish Date</th>
						<th class="region">Region</th>
						<th class="title">Event Title</th>
					</tr>
				</thead>
				<tbody>
				<?php
				foreach ($results as $result) {
					if ((time() >= $result["event_start"]) && (time() <= $result["event_finish"])) {
						$bgcolour = "#E7ECF4";
						$is_here = true;
					} else {
						$bgcolour = "#FFFFFF";
						$is_here = false;
					}

                    if ((bool) $result["manage_apartments"]) {
                        $aschedule_id = regionaled_apartment_check($result["event_id"], $PROXY_ID);
                        $apartment_available = (($aschedule_id) ? true : false);
                    } else {
                        $apartment_available = false;
                    }

                    if ($apartment_available) {
                        $apartment_url = ENTRADA_URL."/clerkship?section=details&id=".$result["event_id"];
                    } else {
                        $apartment_url = "";
                    }

					if (!isset($result["region_name"]) || $result["region_name"] == "") {
						$result_region = clerkship_get_elective_location($result["event_id"]);
						$result["region_name"] = $result_region["region_name"];
						$result["city"] = $result_region["city"];
					} else {
						$result["city"] = "";
					}

					$event_title = clean_input($result["event_title"], array("htmlbrackets", "trim"));
					
					$cssclass = "";
					$skip = false;

					if ($result["event_type"] == "elective") {
						switch ($result["event_status"]) {
							case "approval":
								$elective_word = "Pending";
								$cssclass = " class=\"in_draft\"";
								$click_url = ENTRADA_URL."/clerkship/electives?section=edit&id=".$result["event_id"];
								$skip = false;
							break;
							case "published":
								$elective_word = "Approved";
								$cssclass = " class=\"published\"";
								$click_url = ENTRADA_URL."/clerkship/electives?section=view&id=".$result["event_id"];
								$skip = false;
							break;
							case "trash":
								$elective_word = "Rejected";
								$cssclass = " class=\"rejected\"";
								$click_url = ENTRADA_URL."/clerkship/electives?section=edit&id=".$result["event_id"];
								$skip = true;
							break;
							default:
								$elective_word = "";
								$cssclass = "";
							break;
						}

						$elective = true;
					} else {
						$elective = false;
						$skip = false;
					}

                    $apartment_message = false;
					if ((bool) $result["manage_apartments"] && $elective) {
                        $apartment_message = true;
					}
                    
					if (!$skip) {
						echo "<tbody>\n";
						echo "<tr".(($is_here) && $cssclass != " class=\"in_draft\"" ? " class=\"current\"" : $cssclass).">\n";
						echo "		<td class=\"modified\" ".($SHOW_LOGBOOK ? "onclick=\"showEventDetails(".$result["event_id"].")\"><img src=\"".ENTRADA_URL."/images/tree/".(($is_here) && $cssclass != " class=\"in_draft\"" ? "minus" : "plus")."0.gif\" id=\"rotation-img-".$result["event_id"]."\"/>" : "/>".(($apartment_available) ? "<a href=\"".$apartment_url."\">" : "")."<img src=\"".ENTRADA_URL."/images/".(($apartment_available) ? "housing-icon-small.gif" : "pixel.gif")."\" width=\"16\" height=\"16\" alt=\"".(($apartment_available) ? "Detailed apartment information available." : "")."\" title=\"".(($apartment_available) ? "Detailed apartment information available." : "")."\" style=\"border: 0px\" />".(($apartment_available) ? "</a>" : ""))."</td>\n";
						echo "		<td class=\"type\">".(($elective) ? "<a href=\"".$click_url."\" style=\"font-size: 11px\">" : "").(($elective) ? "Elective".(($elective_word != "") ? " (".$elective_word.")" : "") : "Core Rotation").(($elective) ? "</a>" : "")."</td>\n";
						echo "		<td class=\"date-smallest\">".(($elective) ? "<a href=\"".$click_url."\" style=\"font-size: 11px\">" : "").date("D M d/y", $result["event_start"]).(($elective) ? "</a>" : "")."</td>\n";
						echo "		<td class=\"date-smallest\">".(($elective) ? "<a href=\"".$click_url."\" style=\"font-size: 11px\">" : "").date("D M d/y", $result["event_finish"]).(($elective) ? "</a>" : "")."</td>\n";
						echo "		<td class=\"region\">".(($apartment_available) ? "<a href=\"".$apartment_url."\" style=\"font-size: 11px\">" : "").html_encode((($result["city"] == "") ? limit_chars(($result["region_name"]), 15) : limit_chars(($result["city"]), 15))).(($apartment_available) ? "</a>" : "")."</td>\n";
						echo "		<td class=\"title\">";
						echo "			".(($elective) ? "<a href=\"".$click_url."\" style=\"font-size: 11px\">" : "")."<span title=\"".$event_title."\">".limit_chars(html_decode($event_title), 50)."</span>".(($elective) ? "</a>" : "");
						echo "		</td>\n";
						$rotation_id = $db->GetOne("SELECT `rotation_id` FROM `".CLERKSHIP_DATABASE."`.`categories` WHERE `category_id` = ".$db->qstr($result["category_id"]));
						echo "	</tr>\n";
						echo "</tbody>\n";
						if ($SHOW_LOGBOOK) {
							echo "<tbody id=\"event-".$result["event_id"]."\"".(($is_here) && $cssclass != " class=\"in_draft\"" ? "" : " style=\"display: none;\"")." class=\"event-details\">\n";
							echo "	<tr>\n";
							echo "		<td class=\"modified\">&nbsp;</td>\n";
							if ($SHOW_LOGBOOK) {
								echo "		<td colspan=\"3\"><img width=\"15px\" height=\"15px\" src=\"".ENTRADA_URL."/images/icon-lecture-notes-on.gif\" style=\"padding-right: 5px; vertical-align: bottom;\" /><strong>".($cssclass == " class=\"in_draft\"" ? "<span class=\"content-small\">Awaiting Approval</span>" : "<a style=\"font-size: 11px;\" href=\"".ENTRADA_URL."/clerkship/logbook?section=add&event=".$result["event_id"]."\">Log Patient Encounter</a>")."</strong></td>\n";
							} else {
								echo "		<td colspan=\"3\"><img width=\"15px\" height=\"15px\" src=\"".ENTRADA_URL."/images/icon-lecture-notes-on.gif\" style=\"padding-right: 5px; vertical-align: bottom;\" /><strong><span class=\"content-small\">Logging Not Yet Active</span></strong></td>\n";
							}
							echo "		<td colspan=\"2\">".(($apartment_available) ? "<a href=\"".$apartment_url."\"><strong>Housing Details</strong><img src=\"".ENTRADA_URL."/images/housing-icon-small.gif\" width=\"16\" height=\"16\" alt=\"Detailed apartment information available.\" title=\"Detailed apartment information available.\" style=\"border: 0px; padding-left: 5px; vertical-align: bottom;\" />" : ($apartment_message ? "Contact <a href=\"mailto:regional@queensu.ca\">Regional Education</a> to arrange housing." : "No Housing Available")).($apartment_available ? "</a>" : "")."</td>\n";
		//					echo "		<td colspan=\"2\"><a href=\"".ENTRADA_URL."/clerkship/logbook?section=checklist&id=$result[event_id]\"/>Rotation Checklist</a></td>\n";
						echo "</tr>\n";
							echo "</tbody>\n";
						}
					}
				}
				?>
				</tbody>
			</table>
			<?php
		} else {
			$NOTICE++;
			$NOTICESTR[] = "You currently have no scheduled " . $translate->_("clerkship") . " core rotations or electives in the system.<br /><br />Your core rotation schedule will be added by the Undergraduate office, and you can begin to enter electives by clicking the <strong>Add Elective</strong> link above.";

			echo display_notice();
		}
		} else {
				$NOTICE++;
				$NOTICESTR[] = "You currently have no scheduled " . $translate->_("clerkship") . " core rotations or electives in the system.<br /><br />Your core rotation schedule will be released on ".date(DEFAULT_DATETIME_FORMAT, CLERKSHIP_SCHEDULE_RELEASE).", please check back then.";
	
				echo display_notice();
    }
	break;
	default :
		$HEAD[] = "<link href=\"".ENTRADA_URL."/javascript/calendar/css/xc2_default.css\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
		$HEAD[] = "<script language=\"javascript\" type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/calendar/config/xc2_default.js\"></script>\n";
		$HEAD[] = "<script language=\"javascript\" type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/calendar/script/xc2_inpage.js\"></script>\n";
		$HEAD[] = "<script language=\"javascript\" type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/calendar/script/xc2_timestamp.js\"></script>\n";

		$DATE_INFO			= array();
		$DATE_START			= 0;
		$DATE_FINISH		= 0;
		$RICC				= 0;

		$category_types		= array();

		/**
		 * If a department ID is provided, check to ensure it's valid.
		 */
		if((isset($_GET["d"])) && ($tmp_department = clean_input($_GET["d"], array("trim", "int")))) {
			$query = "SELECT `department_title` FROM `".AUTH_DATABASE."`.`departments` WHERE `department_id` = ".$db->qstr($tmp_department);
			$result	= $db->GetRow($query);
			if($result) {
				$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["department_id"] = $tmp_department;
				$rotation_id = "";
			}
		} else {
			if(!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["department_id"])) {
				$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["department_id"] = "";
			}
		}

		/**
		 * If a timestamp is provided, validate it and set the internal variables.
		 */
		if((isset($_GET["dstamp"])) && ($tmp_timestamp = clean_input($_GET["dstamp"], array("trim", "int")))) {
			$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["timestamp"]	= $tmp_timestamp;
		} else {
			if(!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["timestamp"])) {
				$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["timestamp"] = time();
			}
		}

		/**
		 * If a rotation is provided, validate it and set the internal variables.
		 */
		if((isset($_GET["r"])) && ($tmp_rotation = clean_input($_GET["r"], array("trim", "int")))) {
			$rotation_id	= $tmp_rotation;
			$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["department_id"] = "";
			$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["block_current"] = "";
		} else {
			if(!isset($rotation_id)) {
				$rotation_id = "";
			}
		}

		if((isset($_GET["b"])) && ($tmp_block = clean_input($_GET["b"], array("trim", "int")))) {
			$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["block_current"]	= $tmp_block;
		} else {
			if(!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["block_current"])) {
				$_SESSION[APPLICATION_IDENTIFIER][$MODULE]["block_current"] = "";

			}
		}
		
		/**
		 * Update requsted number of rows per page.
		 * Valid: any integer really.
		 */
		if ((isset($_GET["pp"])) && ((int) trim($_GET["pp"]))) {
			$integer = (int) trim($_GET["pp"]);
	
			if (($integer > 0) && ($integer <= 250)) {
				$_SESSION[APPLICATION_IDENTIFIER]["clerkship"]["pp"] = $integer;
			}
	
			$_SERVER["QUERY_STRING"] = replace_query(array("pp" => false));
		} else {
			if (!isset($_SESSION[APPLICATION_IDENTIFIER]["clerkship"]["pp"])) {
				$_SESSION[APPLICATION_IDENTIFIER]["clerkship"]["pp"] = DEFAULT_ROWS_PER_PAGE;
			}
		}

		$DATE_INFO		= getdate($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["timestamp"]);
		$DATE_START		= mktime(0, 0, 0, date("n", $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["timestamp"]), 1, date("Y", $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["timestamp"]));
		$DATE_FINISH	= mktime(23, 59, 59, date("n", $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["timestamp"]), date("t", $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["timestamp"]), date("Y", $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["timestamp"]));
		?>
		<script language="JavaScript" type="text/javascript">
		function setDateValue(field, date) {
			timestamp = getMSFromDate(date);
			if(field.value != timestamp) {
				window.location = '<?php echo ENTRADA_URL."/".$MODULE."?".(($_SERVER["QUERY_STRING"] != "") ? replace_query(array("dstamp" => false))."&" : ""); ?>dstamp='+timestamp;
			}
			return;
		}
		</script>
        
		<div class="tab-pane" id="clerkship-tabs">
            <div class="tab-page">
                <h3 class="tab"><?php echo $translate->_("Departmental Clerkship Schedule"); ?></h3>
                <div class="select-dep clearfix">
                    <form id="department-change-form" action="<?php echo ENTRADA_URL; ?>/clerkship" method="get" class="form-horizontal">
                        <div class="control-group">
                            <label class="control-label form-required" for="department" >Select Department:</label>
                            <div class="controls">
                                <select class="clear-width" id="department" name="d" onchange="$('department-change-form').submit()">
                                    <option value="">-- Select the Department to Browse --</option>
                                    <?php
                                    $query = "	SELECT a.`department_id`, a.`department_title`, a.`organisation_id`, b.`entity_title`, c.`organisation_title`
                                                FROM `".AUTH_DATABASE."`.`departments` AS a
                                                LEFT JOIN `".AUTH_DATABASE."`.`entity_type` AS b
                                                ON a.`entity_id` = b.`entity_id`
                                                LEFT JOIN `".AUTH_DATABASE."`.`organisations` AS c
                                                ON a.`organisation_id` = c.`organisation_id`
                                                WHERE a.`department_active` = '1'
                                                ORDER BY c.`organisation_title` ASC, a.`department_title`";
                                    $results = $db->GetAll($query);
                                    if ($results) {
                                        $organisation_title = "";

                                        foreach ($results as $key => $result) {
                                            if ($organisation_title != $result["organisation_title"]) {
                                                if ($key) {
                                                    echo "</optgroup>";
                                                }
                                                echo "<optgroup label=\"".html_encode($result["organisation_title"])."\">";

                                                $organisation_title = $result["organisation_title"];
                                            }
                                            echo "<option value=\"".(int) $result["department_id"]."\"".(((isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["department_id"])) && ((int) $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["department_id"]) && ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["department_id"] == $result["department_id"])) ? " selected=\"selected\"" : "").">".html_encode($result["department_title"])."</option>\n";
                                        }
                                        echo "</optgroup>";
                                    }
                                    ?>
                                    </select>
                            </div> <!-- controls -->
                        </div>  <!-- control-group -->
                    </form>
                </div><!-- /select-dep -->
            </div>
    <?php
            if ($ENTRADA_ACL->amIAllowed('clerkship', 'read')) {
                $query = "	SELECT a.`rotation_id`, a.`rotation_title`, a.`course_id`, b.`organisation_id`
                            FROM `".CLERKSHIP_DATABASE."`.`global_lu_rotations` AS a
                            LEFT JOIN `".DATABASE_NAME."`.`courses` AS b
                            ON a.`course_id` = b.`course_id`
                            WHERE b.`course_active` = '1'
                            ORDER BY a.`rotation_id`";
                $results = $db->GetAll($query);
                if ($results) {
                    $rotations = array();

                    foreach($results as $result) {
                        if ($ENTRADA_ACL->amIAllowed(new CourseContentResource($result["course_id"], $result["organisation_id"]), 'update')) {
                            $rotations[] = $result;
                        }
                    }

                    if (!empty($rotations)) {
                        ?>
                        <div class="tab-page">
                            <h3 class="tab"><?php echo $translate->_("Clerkship Rotations"); ?></h3>
                            <form id="rotation-logbook-form" action="<?php echo ENTRADA_URL; ?>/clerkship" method="get">
                                <table style="width: 100%" cellspacing="1" cellpadding="2" border="0" summary="Select Rotation">
                                <colgroup>
                                    <col style="width: 3%" />
                                    <col style="width: 25%" />
                                    <col style="width: 72%" />
                                </colgroup>
                                <tbody>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td><label for="rotation" class="form-required">Select Rotation:</label></td>
                                        <td>
                                            <select id="rotation" name="r" style="width: 95%" onchange="$('rotation-logbook-form').submit()">
                                            <option value="">-- Select the Rotation to View --</option>
                                            <?php
                                            foreach($rotations as $result) {
                                                if (!(stristr($result["rotation_title"], "ricc") === FALSE)) { // Look for RICC
                                                    $result["rotation_title"] = 'Rural Integrated Clerkship';
                                                    $RICC = $result["rotation_id"];
                                                }

                                                if ($ENTRADA_ACL->amIAllowed(new CourseContentResource($result["course_id"], $result["organisation_id"]), 'update')) {
                                                    echo "<option value=\"".(int) $result["rotation_id"]."\"".(( ($rotation_id == $result["rotation_id"])) ? " selected=\"selected\"" : "").">".html_encode($result["rotation_title"])."</option>\n";
                                                }
                                            }
                                            ?>
                                            </select>
                                        </td>
                                    </tr>
                                </tbody>
                                </table>
                            </form>
                        </div>
                        <?php
                    }
                }
            }
            ?>
            <div class="tab-page">
                <h3 class="tab">Student Search</h3>
                <br />
                <form action="<?php echo ENTRADA_URL; ?>/clerkship?section=results" method="post" class="form-horizontal">
                    <input type="hidden" name="action" value="results" />
                    <fieldset>
                        <legend class="content-subheading">Graduating Year</legend>
                        <div class="control-group">
                            <label class="control-label" style="width:335px;text-align:left;">Select an elective qualifier:</label>
                            <div class="controls">
                                <select name="qualifier">
                                    <option value="*">All</option>
                                    <option value="deficient">Deficient</option>
                                    <option value="attained">Attained</option>
                                </select>
                            </div>
                        </div> <!--/control-group -->
                        <div class="control-group">
                                <label class="control-label" style="width:335px;text-align:left;">Select the graduating year you wish to view students in:</label>
                                <div class="controls">
                                    <select name="year">
                                        <option value="">-- Select Graduating Year --</option>
                                        <?php

                                        $student_classes = array();
                                        $active_cohorts = groups_get_all_cohorts($ENTRADA_USER->getActiveOrganisation());
                                        if (isset($active_cohorts) && !empty($active_cohorts)) {
                                            foreach ($active_cohorts as $cohort) {
                                                $student_classes[$cohort["group_id"]] = $cohort["group_name"];
                                            }
                                        }

                                        if (isset($student_classes) && !empty($student_classes)) {
                                            foreach ($student_classes as $group_id => $class) {
                                                echo "<option value=\"".$group_id."\">".html_encode($class)."</option>\n";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                        </div> <!--/control-group -->
                        <div class="form-actions" style="padding-left:335px;padding-top:0">
                            <input type="submit" value="Proceed" class="btn"/>
                        </div>
                    </fieldset>
                    <hr />
                    <fieldset>
                        <legend class="content-subheading">Student Finder</legend>
                        <div class="control-group">
                            <label class="control-label" style="width:335px;text-align:left;">Enter the first or lastname of the student:</label>
                            <div class="controls">
                                <input type="text" name="name" value="" />
                            </div>
                        </div> <!--/control-group -->
                        <div class="form-actions" style="padding-left:335px;padding-top:0">
                            <input type="submit" value="Search" class="btn btn-primary"/>
                        </div>

                    </fieldset>
                </form>
            </div>
        </div>
        <script>
            setupAllTabs(false);
        </script>
		<?php
        /**
         * If a department is selected, display the schedule.
         */
        if((isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["department_id"])) && ((int) $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["department_id"])) {
            $department_title = fetch_department_title($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["department_id"]);
            ?>
            <h2><?php echo html_encode($department_title); ?></h2>
            <div class="row-fluid space-below">
                <div class="span5">
                    <div class="btn-group">
                        <a class="btn" href="<?php echo ENTRADA_URL; ?>/clerkship?<?php echo replace_query(array("dstamp" => strtotime("-1 month", $DATE_START))); ?>"><i class="icon-chevron-left"></i></a>
                        <a class="btn disabled" href="#"><?php echo date("D, M jS, Y", $DATE_START)." to ".date("D, M jS, Y", $DATE_FINISH); ?></a>
                        <a class="btn" href="<?php echo ENTRADA_URL; ?>/clerkship?<?php echo replace_query(array("dstamp" => (strtotime("+1 day", $DATE_FINISH)))); ?>"><i class="icon-chevron-right"></i></a>
                    </div>
                </div>
                <div class="span2">
                    <a class="btn" href="<?php echo ENTRADA_URL; ?>/clerkship?<?php echo replace_query(array("dstamp" => time())); ?>"><i class="icon-refresh"></i></a>
                    <a class="btn" href="javascript:showCalendar('', document.getElementById('dstamp'), document.getElementById('dstamp'), '<?php echo html_encode($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["dstamp"]); ?>', 'calendar-holder', 8, 8, 1);" id="calendar-holder"><i class="icon-calendar"></i></a>
                </div>
                <div class="span5">
                    <h3 class="text-right"><?php echo html_encode($DATE_INFO["month"]); ?> Schedule</h3>
                </div>
            </div>
            <?php
            $query	= "
                    SELECT b.*, d.`region_name`, e.`id` AS `proxy_id`, e.`number`, CONCAT_WS(', ', e.`lastname`, e.`firstname`) AS `fullname`, e.`email`, f.`role`, h.`ctype_name`
                    FROM `".CLERKSHIP_DATABASE."`.`category_departments` AS a
                    LEFT JOIN `".CLERKSHIP_DATABASE."`.`events` AS b
                    ON b.`category_id` = a.`category_id`
                    LEFT JOIN `".CLERKSHIP_DATABASE."`.`event_contacts` AS c
                    ON c.`event_id` = b.`event_id`
                    LEFT JOIN `".CLERKSHIP_DATABASE."`.`regions` AS d
                    ON d.`region_id` = b.`region_id`
                    LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS e
                    ON e.`id` = c.`etype_id`
                    LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS f
                    ON f.`user_id` = e.`id`
                    LEFT JOIN `".CLERKSHIP_DATABASE."`.`categories` AS g
                    ON g.`category_id` = a.`category_id`
                    LEFT JOIN `".CLERKSHIP_DATABASE."`.`category_type` AS h
                    ON h.`ctype_id` = g.`category_type`
                    WHERE a.`department_id` = ".$db->qstr($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["department_id"])."
                    AND (
                        (".$db->qstr($DATE_START)." BETWEEN b.`event_start` AND b.`event_finish`)
                        OR (".$db->qstr($DATE_FINISH)." BETWEEN b.`event_start` AND b.`event_finish`)
                        OR (b.`event_start` BETWEEN ".$db->qstr($DATE_START)." AND ".$db->qstr($DATE_FINISH).")
                        OR (b.`event_finish` BETWEEN ".$db->qstr($DATE_START)." AND ".$db->qstr($DATE_FINISH).")
                    )
                    AND c.`econtact_type` = 'student'
                    AND f.`app_id` = ".$db->qstr(AUTH_APP_ID)."
                    GROUP BY b.`event_id`
                    ORDER BY b.`event_start` ASC, b.`event_finish` ASC, `fullname` ASC";
            $results = $db->CacheGetAll(CACHE_TIMEOUT, $query);
            if($results) {
                ?>
                <p class="muted text-center">
                    <small>Found <?php echo $total_rows = @count($results); ?> clerk<?php echo (($total_rows != 1) ? "s" : ""); ?> in <strong><?php echo $DATE_INFO["month"]; ?></strong> of <strong><?php echo $DATE_INFO["year"]; ?></strong></small>
                </p>
                <table class="table table-bordered table-striped" cellspacing="0" summary="List of clerks in <?php echo html_encode($department_title); ?>">
                <colgroup>
                    <col class="modified" />
                    <col class="teacher" />
                    <col class="phase" />
                    <col class="date-small" />
                    <col class="date-small" />
                    <col class="region" />
                    <col class="title" />
                </colgroup>
                <thead>
                    <tr>
                        <th class="modified">&nbsp;</th>
                        <th class="teacher">Fullname</th>
                        <th class="phase">Class</th>
                        <th class="date-smallest">Date Starts</th>
                        <th class="date-smallest">Date Finishes</th>
                        <th class="region">Region</th>
                        <th class="title">Rotation Name</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach($results as $result) {
                        $letters = 1;
                        while((array_key_exists($category_acronym = substr($result["ctype_name"], 0, $letters), $category_types)) && (strlen($result["ctype_name"]) <= $letters)) {
                            if($letters > 15) {
                                break;
                            }

                            $letters++;
                            $category_acronym = substr($result["ctype_name"], 0, $letters);
                        }

                        $category_types[$category_acronym] = "<strong>".html_encode($category_acronym)."</strong> = ".html_encode($result["ctype_name"]);

                        echo "<tr id=\"event-".$result["event_id"]."\" class=\"event\">\n";
                        echo "	<td class=\"modified\">".html_encode($category_acronym)."</td>\n";
                        echo "	<td class=\"teacher\"><a href=\"mailto:".html_encode($result["email"])."\">".html_encode($result["fullname"])."</a></td>\n";
                        echo "	<td class=\"phase\">".html_encode($result["role"])."</td>\n";
                        echo "	<td class=\"date-smallest\">".date("D M d/y", $result["event_start"])."</td>\n";
                        echo "	<td class=\"date-smallest\">".date("D M d/y", $result["event_finish"])."</td>\n";
                        echo "	<td class=\"region\">".html_encode($result["region_name"])."</td>\n";
                        echo "	<td class=\"title\">".limit_chars(html_decode($result["event_title"]), 55, true, false)."</td>\n";
                        echo "</tr>\n";
                    }
                    ?>
                </tbody>
                </table>
                <div class="content-small" style="margin-top: 5px">
                    <?php echo implode(", ", $category_types); ?>
                </div>
                <form action="#" method="get">
                <input type="hidden" id="dstamp" name="dstamp" value="<?php echo $DATE_START; ?>" />
                </form>
                <?php
            } else {
                $NOTICE++;
                $NOTICESTR[] = "There are no entries in the " . $translate->_("Clerkship") . " schedule for the <strong>" . html_encode($department_title) . "</strong> department in <strong>" . html_encode($DATE_INFO["month"] . " " . $DATE_INFO["year"]) . "</strong>.<br /><br />If you believe there is a problem, please contact the Undergrad office for more information.";

                echo display_notice();
            }
        }
        if ($ENTRADA_ACL->amIAllowed('clerkship', 'read')) {
            // Display Clerkship Schedule for Mandatory rotations
            if((isset($rotation_id)) && ((int) $rotation_id)) {
                $rotation = clerkship_get_rotation($rotation_id);
                /*
                // Get the core blocks for this rotation for this class year via 'categories' table
                $query = "  SELECT `category_name`, `category_start`, `category_finish` FROM `".CLERKSHIP_DATABASE."`.`categories`
                    WHERE `category_parent` IN (SELECT a.`category_id` FROM `".CLERKSHIP_DATABASE."`.`categories` a, `".CLERKSHIP_DATABASE."`.`category_type` b
                        WHERE a.`category_type` = b.`ctype_id` and b.`ctype_name` like 'Rotation' and a.`category_parent` IN
                        (SELECT `category_type` FROM `".CLERKSHIP_DATABASE."`.`categories`
                        WHERE `category_name` like 'Class of 2010') and `rotation_id` = ".$db->qstr($rotation["id"]).")
                    Order by category_start";
                $blocks = $db->GetAll($query);
                if (!$blocks) {
                $query = "  SELECT `category_name`, `category_start`, `category_finish`
                        FROM `".CLERKSHIP_DATABASE."`.`categories`
                        WHERE `category_parent` IN
                        (   SELECT `category_id` FROM `".CLERKSHIP_DATABASE."`.`categories`
                            WHERE `category_name` like 'Class of 2010'
                            OR `category_parent` IN
                            (	SELECT `category_id` FROM `".CLERKSHIP_DATABASE."`.`categories`
                            WHERE `category_name` like 'Class of 2010'
                            )
                        )
                        AND `category_name` LIKE '%block%'
                        Order by category_start";
                    $blocks = $db->GetAll($query);
                }
                // Set current block
                if(!((isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["block_current"])) && ((int) $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["block_current"]))) {
                    foreach ($blocks as $block) {
                        if ($block["category_start"] <= time() && $block["category_finish"] >= time()) {
                        $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["block_current"] = $block["category_start"];
                        }
                    }
                }*/
                if($tmp_rotation == $RICC) {
                ?>
                    <h2>Rural Integrated Clerkslog</h2>
                    <table style="width: 100%" cellspacing="0" cellpadding="0" border="0" summary="ClerksLogbook">
                        <colgroup>
                        <col style="width: 28%" />
                        <col style="width: 72%" />
                        </colgroup>
                        <tr >
                        <td>&nbsp</td>
                        <td class="sidebar-head" style="text-align: center;">Select a clerk</td>
                        </tr>
                        <tr>
                        <td>&nbsp</td>
                        <td style="vertical-align:top;padding-top:5px;">
                        <?php
                            // Get the clerks from RICC
                            $query = "	SELECT distinct a.`id`, CONCAT_WS(' ', a.`firstname` , a.`lastname`) AS `fullname`, a.`email`
                                FROM `".AUTH_DATABASE."`.`user_data` a
                                INNER JOIN  `".CLERKSHIP_DATABASE."`.`event_contacts` b ON a.`id` = b.`etype_id`
                                INNER JOIN `".CLERKSHIP_DATABASE."`.`events` c ON b.`event_id` = c.`event_id`
                                WHERE  c.`event_title` LIKE CONVERT(_utf8 '%surg%' USING latin1)
                                ORDER BY a.`lastname`, a.`firstname` ";
                            $clerks = $db->GetAll($query);

                            foreach ($clerks as $clerk) {
                            echo "<a href=\"".ENTRADA_URL."/people?id=".$clerk["id"]."\">$clerk[fullname]<br>";
                            }
                        ?>
                        </td>
                        </tr>
                    </table>
                <?php
                } else {
                /**
                 * Update requested length of time to display.
                 * Valid: day, week, month, year
                 */
                if(isset($_GET["dtype"])) {
                    if(in_array(trim($_GET["dtype"]), array("day", "week", "month", "year"))) {
                        $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["dtype"] = trim($_GET["dtype"]);
                    }

                    $_SERVER["QUERY_STRING"] = replace_query(array("dtype" => false));
                } else {
                    if(!isset($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["dtype"])) {
                        $_SESSION[APPLICATION_IDENTIFIER][$MODULE]["dtype"] = "week";
                    }
                }

                /**
                 * Update requested timestamp to display.
                 * Valid: Unix timestamp
                 */
                if(isset($_GET["dstamp"])) {
                    $integer = (int) trim($_GET["dstamp"]);
                    if($integer) {
                        $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["dstamp"] = $integer;
                    }

                    $_SERVER["QUERY_STRING"] = replace_query(array("dstamp" => false));
                } else {
                    if(!isset($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["dstamp"])) {
                        $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["dstamp"] = time();
                    }
                }

                echo "<form action=\"\" method=\"get\">\n";
                echo "<input type=\"hidden\" id=\"dstamp\" name=\"dstamp\" value=\"".html_encode($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["dstamp"])."\" />\n";
                echo "</form>\n";
                /**
                 * This fetches the unix timestamps from the first and last second of the day, week, month, year, etc.
                 */
                $DISPLAY_DURATION = fetch_timestamps($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["dtype"], $_SESSION[APPLICATION_IDENTIFIER]["tmp"]["dstamp"]);

                // Get the clerks for this core block and this rotation
                $query = "  SELECT a.`id`, c.`role`, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`
                                FROM `".AUTH_DATABASE."`.`user_data` AS a
                                LEFT JOIN `".AUTH_DATABASE."`.`user_access` AS c
                                ON a.`id` = c.`user_id`
                                AND c.`app_id` = ".$db->qstr(AUTH_APP_ID).",
                                (
                                    SELECT DISTINCT c.`etype_id`
                                    FROM `".CLERKSHIP_DATABASE."`.`event_contacts` AS c
                                    INNER JOIN `".CLERKSHIP_DATABASE."`.`events` AS e 
                                    ON c.`event_id` = e.`event_id`
                                    WHERE e.`rotation_id` = ".$db->qstr($rotation["id"])."
                                    GROUP BY c.`etype_id`, e.`rotation_id`
                                    HAVING 
                                    (
                                        (
                                            MIN(e.`event_start`) >= ".$db->qstr($DISPLAY_DURATION["start"])."
                                            AND MIN(e.`event_start`) <= ".$db->qstr($DISPLAY_DURATION["end"])."
                                        )
                                        OR 
                                        (
                                            MAX(e.`event_finish`) >= ".$db->qstr($DISPLAY_DURATION["start"])."
                                            AND MAX(e.`event_finish`) <= ".$db->qstr($DISPLAY_DURATION["end"])."
                                        )
                                        OR 
                                        (
                                            MIN(e.`event_start`) <= ".$db->qstr($DISPLAY_DURATION["start"])."
                                            AND MAX(e.`event_finish`) >= ".$db->qstr($DISPLAY_DURATION["start"])."
                                        )
                                    )
                                ) AS b
                                WHERE a.`id` = b.`etype_id`
                                ORDER BY c.`role` DESC, a.`lastname`, a.`firstname` ASC";
                $clerks = $db->CacheGetAll(LONG_CACHE_TIMEOUT, $query);
                $clerk_count = count($clerks);
                $total_pages = (int)($clerk_count / $_SESSION[APPLICATION_IDENTIFIER]["clerkship"]["pp"]) + ($clerk_count % $_SESSION[APPLICATION_IDENTIFIER]["clerkship"]["pp"] > 0 ? 1 : 0);
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
                ?>
                <h2><?php echo html_encode($rotation["title"]); ?></h2>
                <div class="row-fluid space-above">
                    <div class="span4">
                        <div class="btn-group">
                            <a class="btn" href="<?php echo ENTRADA_URL."/clerkship?".replace_query(array("dstamp" => ($DISPLAY_DURATION["start"] - 2))); ?>"><i class="icon-chevron-left"></i></a>
                            <a class="btn<?php echo ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["dtype"] == "day" ? " active" : ""); ?>" href="<?php echo $link_prefix . "?" . replace_query(array("dtype" => "day")); ?>">Day</a>
                            <a class="btn<?php echo ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["dtype"] == "week" ? " active" : ""); ?>" href="<?php echo $link_prefix . "?" . replace_query(array("dtype" => "week")); ?>">Week</a>
                            <a class="btn<?php echo ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["dtype"] == "month" ? " active" : ""); ?>" href="<?php echo $link_prefix . "?" . replace_query(array("dtype" => "month")); ?>">Month</a>
                            <a class="btn<?php echo ($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["dtype"] == "year" ? " active" : ""); ?>" href="<?php echo $link_prefix . "?" . replace_query(array("dtype" => "year")); ?>">Year</a>
                            <a class="btn" href="<?php echo $link_prefix . "?" . replace_query(array("dstamp" => ($learning_events["duration_end"] + 1))); ?>"><i class="icon-chevron-right"></i></a>
                        </div>
                    </div>
                    <div class="span2">
                        <a class="btn" href="<?php echo ENTRADA_URL."/clerkship?" . replace_query(array("dstamp" => time())); ?>"><i class="icon-refresh"></i></a>
                        <a class="btn" href="javascript:showCalendar('', document.getElementById('dstamp'), document.getElementById('dstamp'), '<?php echo html_encode($_SESSION[APPLICATION_IDENTIFIER]["tmp"]["dstamp"]); ?>', 'calendar-holder', 8, 8, 1);" id="calendar-holder"><i class="icon-calendar"></i></a>
                    </div>
                    <div class="span6">
                        <?php
                        if ($total_pages > 1) {
                            $pagination = new Entrada_Pagination($page_current, $_SESSION[APPLICATION_IDENTIFIER]["clerkship"]["pp"], $clerk_count, ENTRADA_URL."/".$MODULE, replace_query());
                            echo $pagination->GetPageBar("normal", "right", false);
                        }
                        ?>
                    </div>
                </div>
                <table style="width: 100%" cellspacing="1" cellpadding="2" border="0" summary="Select Block">
                <colgroup>
                    <col style="width: 3%" />
                    <col style="width: 25%" />
                    <col style="width: 72%" />
                </colgroup>
                <tbody>
                    <tr>
                        <td colspan="3">&nbsp;</td>
                    </tr>
                    <tr>
                        <td colspan="3">
                            <p class="muted text-center">
                                <small>
                                            <?php
                                            switch($_SESSION[APPLICATION_IDENTIFIER][$MODULE]["dtype"]) {
                                                case "day" :
                                                    echo "Found ".$clerk_count." clerk".(($clerk_count != 1) ? "s" : "")." that are in <strong>".$rotation["title"]."</strong> on <strong>".date("D, M jS, Y", $DISPLAY_DURATION["start"])."</strong>.\n";
                                                    break;
                                                case "month" :
                                                    echo "Found ".$clerk_count." clerk".(($clerk_count != 1) ? "s" : "")." that are in <strong>".$rotation["title"]."</strong> during <strong>".date("F", $DISPLAY_DURATION["start"])."</strong> of <strong>".date("Y", $DISPLAY_DURATION["start"])."</strong>.\n";
                                                    break;
                                                case "year" :
                                                    echo "Found ".$clerk_count." clerk".(($clerk_count != 1) ? "s" : "")." that are in <strong>".$rotation["title"]."</strong> during <strong>".date("Y", $DISPLAY_DURATION["start"])."</strong>.\n";
                                                    break;
                                                default :
                                                case "week" :
                                                    echo "Found ".$clerk_count." clerk".(($clerk_count != 1) ? "s" : "")." that are in <strong>".$rotation["title"]."</strong> from <strong>".date("D, M jS, Y", $DISPLAY_DURATION["start"])."</strong> to <strong>".date("D, M jS, Y", $DISPLAY_DURATION["end"])."</strong>.\n";
                                                    break;
                                            }
                                            ?>
                                </small>
                            </p>
                            <table style="width: 100%;" cellspacing="0" cellpadding="0" border="0" class="table table-bordered table-striped" summary="ClerksLogbook">
                            <thead>
                                <tr >
                                    <th>
                                        Clerk
                                    </th>
                                    <th>
                                        Class
                                    </th>
                                    <th>
                                        Entries Logged
                                    </th>
                                    <th>
                                        Objectives Progress
                                    </th>
                                    <th>
                                        Tasks Progress
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                for ($i = (($page_current - 1) * $_SESSION[APPLICATION_IDENTIFIER]["clerkship"]["pp"]); $i < (($page_current * $_SESSION[APPLICATION_IDENTIFIER]["clerkship"]["pp"]) < $clerk_count ? ($page_current * $_SESSION[APPLICATION_IDENTIFIER]["clerkship"]["pp"]) : $clerk_count); $i++) {
                                    $query  = "SELECT COUNT(*) FROM `".CLERKSHIP_DATABASE."`.`logbook_entries` WHERE `proxy_id` = ".$db->qstr($clerks[$i]["id"])." AND `entry_active` = 1 AND
                                                `rotation_id` IN (Select e.`event_id` FROM `".CLERKSHIP_DATABASE."`.`events` as e
                                                WHERE e.`rotation_id` = ".$db->qstr($rotation["id"]).")";
                                    $entries = $db->CacheGetOne(LONG_CACHE_TIMEOUT, $query);

                                    $procedures_required = 0;
                                    $objectives_required = 0;
                                    $objectives_recorded = 0;
                                    $procedures_recorded = 0;
                                    $grad_year = get_account_data("grad_year", $clerks[$i]["id"]);

                                    $query = "SELECT `objective_id`, `lmobjective_id`, MAX(`number_required`) AS `required`
                                                FROM `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objectives`
                                                WHERE `rotation_id` = ".$db->qstr($rotation["id"])."
                                                AND `grad_year_min` <= ".$db->qstr($grad_year)."
                                                AND (`grad_year_max` = 0 OR `grad_year_max` >= ".$db->qstr($grad_year).")
                                                GROUP BY `objective_id`";
                                    $required_objectives = $db->CacheGetAll(LONG_CACHE_TIMEOUT, $query);
                                    if ($required_objectives) {
                                        foreach ($required_objectives as $required_objective) {
                                            $objectives_required += $required_objective["required"];
                                            $llocation_ids_string = "";
                                            if (CLERKSHIP_SETTINGS_REQUIREMENTS) {
                                                $query = "SELECT c.`llocation_id` FROM `".CLERKSHIP_DATABASE."`.`logbook_mandatory_objective_locations` AS a
                                                            JOIN `logbook_location_types` AS b
                                                            ON a.`lltype_id` = b.`lltype_id`
                                                            JOIN `logbook_lu_locations` AS c
                                                            ON b.`llocation_id` = c.`llocation_id
                                                            WHERE a.`lmobjective_id` = ".$db->qstr($required_objective["lmobjective_id"]);
                                                $valid_locations = $db->GetAll($query);
                                                if ($valid_locations) {
                                                    foreach ($valid_locations as $location) {
                                                        if ($llocation_ids_string) {
                                                            $llocation_ids_string .= ", ".$db->qstr($location["llocation_id"]);
                                                        } else {
                                                            $llocation_ids_string = $db->qstr($location["llocation_id"]);
                                                        }
                                                    }
                                                }
                                            }
                                            $query = "	SELECT COUNT(a.`objective_id`) AS `recorded`
                                                        FROM `".CLERKSHIP_DATABASE."`.`logbook_entry_objectives` AS a
                                                        JOIN `".CLERKSHIP_DATABASE."`.`logbook_entries` AS b
                                                        ON `entry_active` = '1' 
                                                        AND `proxy_id` = ".$db->qstr($clerks[$i]["id"])."
                                                        AND a.`lentry_id` = b.`lentry_id`
                                                        WHERE `objective_id` = ".$db->qstr($required_objective["objective_id"])."
                                                        ".($llocation_ids_string ? "AND a.`llocation_id` IN (".$llocation_ids_string.")" : "")."
                                                        GROUP BY `objective_id`";
                                            $recorded = $db->CacheGetOne(LONG_CACHE_TIMEOUT, $query);

                                            if ($recorded) {
                                                $objectives_recorded += ($recorded <= $required_objective["required"] ? $recorded : $required_objective["required"]);
                                            }
                                        }
                                    }
                                    $query = "	SELECT `lprocedure_id`, `lpprocedure_id`, MAX(`number_required`) AS `required`
                                                FROM `".CLERKSHIP_DATABASE."`.`logbook_preferred_procedures`
                                                WHERE `rotation_id` = ".$db->qstr($rotation["id"])."
                                                AND `grad_year_min` <= ".$db->qstr($grad_year)."
                                                AND (`grad_year_max` = 0 OR `grad_year_max` >= ".$db->qstr($grad_year).")
                                                GROUP BY `lprocedure_id`";
                                    $required_procedures = $db->CacheGetAll(LONG_CACHE_TIMEOUT, $query);
                                    if ($required_procedures) {
                                        foreach ($required_procedures as $required_procedure) {
                                            $procedures_required += $required_procedure["required"];
                                            $llocation_ids_string = "";
                                            if (CLERKSHIP_SETTINGS_REQUIREMENTS) {
                                                $query = "SELECT c.`llocation_id` FROM `".CLERKSHIP_DATABASE."`.`logbook_preferred_procedure_locations` AS a
                                                            JOIN `logbook_location_types` AS b
                                                            ON a.`lltype_id` = b.`lltype_id`
                                                            JOIN `logbook_lu_locations` AS c
                                                            ON b.`llocation_id` = c.`llocation_id
                                                            WHERE a.`lpprocedure_id` = ".$db->qstr($required_procedure["lpprocedure_id"]);
                                                $valid_locations = $db->GetAll($query);
                                                if ($valid_locations) {
                                                    foreach ($valid_locations as $location) {
                                                        if ($llocation_ids_string) {
                                                            $llocation_ids_string .= ", ".$db->qstr($location["llocation_id"]);
                                                        } else {
                                                            $llocation_ids_string = $db->qstr($location["llocation_id"]);
                                                        }
                                                    }
                                                }
                                            }
                                            $query = "	SELECT COUNT(`lprocedure_id`) AS `recorded`
                                                        FROM `".CLERKSHIP_DATABASE."`.`logbook_entry_procedures` AS a
                                                        JOIN `".CLERKSHIP_DATABASE."`.`logbook_entries` AS b
                                                        ON `entry_active` = '1' 
                                                        AND `proxy_id` = ".$db->qstr($clerks[$i]["id"])."
                                                        AND a.`lentry_id` = b.`lentry_id`
                                                        WHERE `lprocedure_id` = ".$db->qstr($required_procedure["lprocedure_id"])."
                                                        ".($llocation_ids_string ? "AND a.`llocation_id` IN (".$llocation_ids_string.")" : "")."
                                                        GROUP BY `lprocedure_id`";
                                            $recorded = $db->CacheGetOne(LONG_CACHE_TIMEOUT, $query);

                                            if ($recorded) {
                                                $procedures_recorded += ($recorded <= $required_procedure["required"] ? $recorded : $required_procedure["required"]);
                                            }
                                        }
                                    }

                                    $url = ENTRADA_URL."/clerkship?section=clerk&ids=".$clerks[$i]["id"];
                                    echo "<tr>";
                                    echo "<td><a href=\"".$url."\">".$clerks[$i]["fullname"]."</td>";
                                    echo "<td><a href=\"".$url."\">".$clerks[$i]["role"]."</td>";
                                    echo "<td><a href=\"".$url."\" style=\"color:#222;\">".($entries ? $entries : "0")."</a></td>";
                                    echo "<td><a href=\"".$url."\" style=\"color:#222;\">".($objectives_recorded ? $objectives_recorded : "0")." / ".($objectives_required ? $objectives_required : "0")."</a></td>";
                                    echo "<td><a href=\"".$url."\" style=\"color:#222;\">".($procedures_recorded ? $procedures_recorded : "0")." / ".($procedures_required ? $procedures_required : "0")."</a></td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                            </table>
                        </td>
                    </tr>
                </tbody>
                </table>
            <?php
            }
        }
	}
	break;
}
