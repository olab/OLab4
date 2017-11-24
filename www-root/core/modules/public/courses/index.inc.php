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
 * This file displays the list of objectives pulled
 * from the entrada.global_lu_objectives table.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 *
*/
if((!defined("PARENT_INCLUDED")) || (!defined("IN_COURSES"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
		header("Location: ".ENTRADA_URL);
		exit;
} else {
    $BREADCRUMB[]	= array("url" => ENTRADA_URL."/".$MODULE, "title" => "View " . $translate->_($MODULE));

	/**
	 * Check for groups which have access to the administrative side of this module
	 * and add the appropriate toggle sidebar item.
	 */
	if ($ENTRADA_ACL->amIAllowed("coursecontent", "update", false)) {
		switch ($_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]) {
			case "admin" :
				$admin_wording	= "Administrator View";
				$admin_url		= ENTRADA_URL."/admin/".$MODULE.(($COURSE_ID) ? "?".replace_query(array("section" => "edit", "id" => $COURSE_ID)) : "");
			break;
			case "pcoordinator" :
				$admin_wording	= "Coordinator View";
				$admin_url		= ENTRADA_URL."/admin/".$MODULE.(($COURSE_ID) ? "?".replace_query(array("section" => "content", "id" => $COURSE_ID)) : "");
			break;
			case "director" :
				$admin_wording	= "Director View";
				$admin_url		= ENTRADA_URL."/admin/".$MODULE.(($COURSE_ID) ? "?".replace_query(array("section" => "content", "id" => $COURSE_ID)) : "");
			break;
			default :
				$admin_wording	= "";
				$admin_url		= "";
			break;
		}

		$sidebar_html  = "<ul class=\"menu\">\n";
		$sidebar_html .= "	<li class=\"on\"><a href=\"".ENTRADA_URL."/".$MODULE.(($COURSE_ID) ? "?".replace_query(array("id" => $COURSE_ID, "action" => false)) : "")."\">Learner View</a></li>\n";
		if (($admin_wording) && ($admin_url)) {
			$sidebar_html .= "<li class=\"off\"><a href=\"".$admin_url."\">".html_encode($admin_wording)."</a></li>\n";
		}
		$sidebar_html .= "</ul>\n";

		new_sidebar_item("Display Style", $sidebar_html, "display-style", "open");
	}
	if(!$ORGANISATION_ID){
		$query = "SELECT `organisation_id` FROM `courses` WHERE `course_id` = ".$db->qstr($COURSE_ID);
		if($result = $db->GetOne($query)){
			$ORGANISATION_ID = $result;
			$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["organisation_id"] = $result;
		}
		else
			$ORGANISATION_ID	= $_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["organisation_id"];
	}

	$COURSE_LIST = array();

	$results = courses_fetch_courses(true, true);
	if ($results) {
		foreach ($results as $result) {
			$COURSE_LIST[$result["course_id"]] = html_encode(($result["course_code"] ? $result["course_code"] . ": " : "") . $result["course_name"]);
		}
	}

	/**
	 * If we were going into the $COURSE_ID
	 */
	if ($COURSE_ID) {
		$query = "	SELECT b.`community_url` FROM `community_courses` AS a
					JOIN `communities` AS b
					ON a.`community_id` = b.`community_id`
					WHERE a.`course_id` = ".$db->qstr($COURSE_ID);
		$course_community = $db->GetOne($query);
		if ($course_community) {
			header("Location: ".ENTRADA_URL."/community".$course_community);
			exit;
		}

		$query = "	SELECT * FROM `courses`
					WHERE `course_id` = ".$db->qstr($COURSE_ID)."
					AND `course_active` = '1'";
		$course_details	= ((USE_CACHE) ? $db->CacheGetRow(CACHE_TIMEOUT, $query) : $db->GetRow($query));
		if (!$course_details) {
			$ERROR++;
			$ERRORSTR[] = "The course identifier that was presented to this page currently does not exist in the system.";

			echo display_error();
		} else {
			if ($ENTRADA_ACL->amIAllowed(new CourseResource($COURSE_ID, $ENTRADA_USER->getActiveOrganisation), "read")) {
				add_statistic($MODULE, "view", "course_id", $COURSE_ID);

				$BREADCRUMB[] = array("url" => ENTRADA_URL."/".$MODULE."?".replace_query(array("id" => $course_details["course_id"])), "title" => $course_details["course_name"].(($course_details["course_code"]) ? ": ".$course_details["course_code"] : ""));

				$OTHER_DIRECTORS = array();

				$sub_query = "SELECT `proxy_id` FROM `course_contacts` WHERE `course_contacts`.`course_id`=".$db->qstr($COURSE_ID)." AND `course_contacts`.`contact_type` = 'director' ORDER BY `contact_order` ASC";
				$sub_results = $db->GetAll($sub_query);
				if ($sub_results) {
					foreach ($sub_results as $sub_result) {
						$OTHER_DIRECTORS[] = $sub_result["proxy_id"];
					}
				}

				// Meta information for this page.
				$PAGE_META["title"]			= $course_details["course_name"].(($course_details["course_code"]) ? ": ".$course_details["course_code"] : "")." - ".APPLICATION_NAME;
				$PAGE_META["description"]	= trim(str_replace(array("\t", "\n", "\r"), " ", html_encode(strip_tags($course_details["course_description"]))));
				$PAGE_META["keywords"]		= "";

				$course_details_section			= true;
				$course_description_section		= false;
				$course_objectives_section		= false;
				$course_assessment_section		= false;
				$course_textbook_section		= false;
				$course_message_section			= false;
				$course_resources_section		= true;
				?>
				<div class="no-printing text-right" >
					<form class="form-horizontal">
					<label for="course-quick-select" class="content-small"><?php echo $translate->_("course"); ?> Quick Select:</label>
					<select id="course-quick-select" name="course-quick-select" onchange="window.location='<?php echo ENTRADA_URL; ?>/courses?id='+this.options[this.selectedIndex].value">
					<option value="">-- Select a <?php echo $translate->_("course"); ?> --</option>
					<?php
					foreach ($COURSE_LIST as $key => $course_name) {
						echo "<option value=\"".$key."\"".(($key == $COURSE_ID) ? " selected=\"selected\"" : "").">".$course_name."</option>\n";
					}
					?>
					</select>
					</form>
				</div>
				<div>
					<div class="no-printing pull-right space-above">
						<a 	href="<?php echo ENTRADA_URL."/".$MODULE."?id=".$course_details["course_id"]; ?>">
							<img 	src="<?php echo ENTRADA_URL; ?>/images/page-link.gif"
									width="16"
									height="16"
									alt="Link to this page"
									title="Link to this page"
									border="0"/>
						</a>
						<a href="<?php echo ENTRADA_URL."/".$MODULE."?id=".$course_details["course_id"]; ?>">
						Link to this page
						</a>
						<a href="javascript:window.print()">
							<img 	src="<?php echo ENTRADA_URL; ?>/images/page-print.gif"
									width="16"
									height="16"
									alt="Print this page"
									title="Print this page"
									border="0"/>
						</a>

						<a href="javascript: window.print()">
							Print this page
						</a>
					</div>

					<h1><?php echo html_encode($course_details["course_name"].(($course_details["course_code"]) ? ": ".$course_details["course_code"] : "")); ?></h1>
				</div>

				<a name="course-details-section"></a>
				<h2 title="Course Details Section"><?php echo $translate->_("course"); ?> Details</h2>
				<div id="course-details-section">
					<?php
					if ($course_url = clean_input($course_details["course_url"], array("notags", "nows"))) { ?>
					<div class="control-group">
						<label for="external_website" class="form-nrequired control-label"></label>
						<div class="controls">
							<a href="<?php echo html_encode($course_url);?>" target="_blank">
								View <strong><?php echo html_encode($course_details["course_name"]);?> Website</strong>
							</a>
						</div>
					</div>
					<?php
					} ?>

					<div class="control-group">
						<label for="course_directors" class="form-nrequired control-label"><strong><?php echo $translate->_("Course Directors"); ?></strong></label>
						<div class="controls">
						<?php
							$squery = "	SELECT a.`proxy_id`, CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `fullname`
										FROM `course_contacts` AS a
										LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
										ON b.`id` = a.`proxy_id`
										WHERE a.`course_id` = ".$db->qstr($course_details["course_id"])."
										AND a.`contact_type` = 'director'
										AND b.`id` IS NOT NULL
										ORDER BY a.`contact_order` ASC";
							$results = $db->GetAll($squery);
							if ($results) {
								foreach ($results as $key => $sresult) {
									echo "<a href=\"".ENTRADA_RELATIVE."/people?id=".$sresult["proxy_id"]."\" class=\"event-details-item\">".html_encode($sresult["fullname"])."</a><br />\n";
								}
							} else {
								echo "To Be Announced";
							}
						?>
						</div>
					</div>

					<div class="control-group">
						<label for="curriculum_coordinators" class="form-nrequired control-label"><strong><?php echo $translate->_("Curriculum Coordinators"); ?></strong></label>
						<div class="controls">
							<?php
								$squery = "	SELECT a.`proxy_id`, CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `fullname`
											FROM `course_contacts` AS a
											LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
											ON b.`id` = a.`proxy_id`
											WHERE a.`course_id` = ".$db->qstr($course_details["course_id"])."
											AND a.`contact_type` = 'ccoordinator'
											AND b.`id` IS NOT NULL
											ORDER BY a.`contact_order` ASC";
								$results = $db->GetAll($squery);
								if ($results) {
									foreach ($results as $key => $sresult) {
                                        echo "<a href=\"".ENTRADA_RELATIVE."/people?id=".$sresult["proxy_id"]."\" class=\"event-details-item\">".html_encode($sresult["fullname"])."</a><br />\n";
									}
								} else {
									echo "To Be Announced";
								}
							?>
						</div>
					</div>

					<div class="control-group">
						<label for="instructors" class="form-nrequired control-label"><strong><?php echo $translate->_("Faculty"); ?></strong></label>
						<div class="controls">
							<?php
							$squery = "	SELECT a.`proxy_id`, CONCAT_WS(' ', b.`firstname`, b.`lastname`) AS `fullname`, b.`email`
											FROM `course_contacts` AS a
											LEFT JOIN `".AUTH_DATABASE."`.`user_data` AS b
											ON b.`id` = a.`proxy_id`
											WHERE a.`course_id` = ".$db->qstr($course_details["course_id"])."
											AND a.`contact_type` = 'associated_faculty'
											AND b.`id` IS NOT NULL
											ORDER BY a.`contact_order` ASC";
							$results = $db->GetAll($squery);
							if ($results) {
								foreach ($results as $key => $sresult) {
                                    echo "<a href=\"".ENTRADA_RELATIVE."/people?id=".$sresult["proxy_id"]."\" class=\"event-details-item\">".html_encode($sresult["fullname"])."</a><br />\n";
                                }
                            } else {
                                echo "To Be Announced";
							}
							?>
						</div>
					</div>

					<?php
					if((int) $course_details["pcoord_id"]) { ?>
						<div class="control-group">
							<label for="program_coordinator" class="form-nrequired control-label"><strong><?php echo $translate->_("Program Coordinator"); ?></strong></label>
							<div class="controls">
								<a href="mailto:<?php echo get_account_data("email", $course_details["pcoord_id"]);?>"><?php echo get_account_data("fullname", $course_details["pcoord_id"]);?></a>
							</div>
						</div>
					<?php
					}

					if((int) $course_details["evalrep_id"]) { ?>
						<div class="control-group">
							<label for="eval_rep" class="form-nrequired control-label"><strong><?php echo $translate->_("Evaluation Rep"); ?></strong></label>
							<div class="controls">
								<a href="mailto:<?php echo get_account_data("email", $course_details["evalrep_id"]);?>"><?php echo get_account_data("fullname", $course_details["evalrep_id"]);?></a>
							</div>
						</div>
					<?php
					}

					if((int) $course_details["studrep_id"]) { ?>
						<div class="control-group">
							<label for="stud_rep" class="form-nrequired control-label"><strong><?php echo $translate->_("Student Rep"); ?></strong></label>
							<div class="controls">
								<a href="mailto:<?php echo get_account_data("email", $course_details["studrep_id"]);?>"><?php echo get_account_data("fullname", $course_details["studrep_id"]);?></a>
							</div>
						</div>
					<?php
					}

					if (clean_input($course_details["course_description"], array("allowedtags", "nows")) != "") { ?>
						<div class="control-group">
							<label for="course_description" class="form-nrequired control-label">&nbsp;</label>
							<div class="controls">
								<h3><?php echo $translate->_("course") . " Description"; ?></h3>
								<?php echo trim(strip_selected_tags($course_details["course_description"], array("font"))); ?>
							</div>
						</div>
					<?php
					}

					if (clean_input($course_details["course_message"], array("allowedtags", "nows")) != "") { ?>
						<div class="control-group">
							<label for="director_message" class="form-nrequired control-label">&nbsp;</label>
							<div class="controls">
								<h3>Director's Messages</h3>
								<?php echo trim(strip_selected_tags($course_details["course_message"], array("font"))); ?>
							</div>
						</div>
					<?php
					} ?>
				</div>

				<?php
				$show_objectives = false;
				list($objectives,$top_level_id) = courses_fetch_objectives($ORGANISATION_ID,array($COURSE_ID));
				foreach ($objectives["objectives"] as $objective) {
					if ((isset($objective["primary"]) && $objective["primary"]) || (isset($objective["secondary"]) && $objective["secondary"]) || (isset($objective["tertiary"]) && $objective["tertiary"])) {
						$show_objectives = true;
						break;
					}
				}
				$query = "	SELECT COUNT(*) FROM course_objectives WHERE course_id = ".$db->qstr($COURSE_ID);
				$result = $db->GetOne($query);
				if ($result) { ?>
					<script type="text/javascript">
					function renewList (hierarchy) {
						if (hierarchy != null && hierarchy) {
							hierarchy = 1;
						} else {
							hierarchy = 0;
						}
						new Ajax.Updater('objectives_list', '<?php echo ENTRADA_URL; ?>/api/objectives.api.php',
							{
								method:	'post',
								parameters: 'course_ids=<?php echo $COURSE_ID ?>&hierarchy='+hierarchy
							}
						);
					}
					</script>

					<a name="course-objectives-section"></a>
					<h2 title="<?php echo $translate->_("Course Objectives Section"); ?>"><?php echo $translate->_("course") . " " . $translate->_("Objectives"); ?></h2>
					<div id="course-objectives-section">
					<?php
					if (clean_input($course_details["course_objectives"], array("allowedtags", "nows"))) {
						$course_objectives_section = true;
						echo trim(strip_selected_tags($course_details["course_objectives"], array("font")));
					}
                    ?>
					<h3><?php echo $translate->_("Curriculum Objectives"); ?></h3>
					<p>The learner will be able to:</p>
					<div id="objectives_list">
						<?php echo course_objectives_in_list($objectives, $top_level_id,$top_level_id); ?>
					</div>
					<?php
					$query = "	SELECT b.*
								FROM `course_objectives` AS a
								JOIN `global_lu_objectives` AS b
								ON a.`objective_id` = b.`objective_id`
								JOIN `objective_organisation` AS c
								ON b.`objective_id` = c.`objective_id`
								AND c.`organisation_id` = ".$db->qstr($ENTRADA_USER->getActiveOrganisation())."
								WHERE a.`objective_type` = 'event'
                                AND a.`active` = '1'
								AND b.`objective_active` = '1'
								AND a.`course_id` = ".$db->qstr($COURSE_ID)."
								GROUP BY b.`objective_id`
								ORDER BY b.`objective_order`";
					$results = $db->GetAll($query);
					if ($results) {
                        ?>
                        <h3><?php echo $translate->_("Clinical Presentations"); ?></h3>
                        <ul class="objective-list">
                        <?php
                        foreach ($results as $result) {
                            if ($result["objective_name"]) {
                                ?>
                                <li>
                                    <?php echo html_encode($result["objective_name"]); ?>
                                </li>
                                <?php
                            }
                        }
                        ?>
                        </ul>
                        <?php
					}
                ?>
				</div>
                <div class="clear_both"></div>
                <?php
				} ?>
				<a name="course-resources-section"></a>
				<h2 title="Course Resources Section"><?php echo $translate->_("course"); ?> Resources</h2>
				<div id="course-resources-section">
					<?php
					$query = "	SELECT `course_files`.*, MAX(`statistics`.`timestamp`) AS `last_visited`
								FROM `course_files`
								LEFT JOIN `statistics`
								ON `statistics`.`module`=".$db->qstr($MODULE)."
								AND `statistics`.`proxy_id`=".$db->qstr($ENTRADA_USER->getActiveId())."
								AND `statistics`.`action`='file_download'
								AND `statistics`.`action_field`='file_id'
								AND `statistics`.`action_value`=`course_files`.`id`
								WHERE `course_files`.`course_id`=".$db->qstr($COURSE_ID)."
								GROUP BY `course_files`.`id`
								ORDER BY `file_category` ASC, `file_title` ASC";
					$results = $db->GetAll($query);
					?>
					<table class="tableList" cellspacing="0" summary="List of File Attachments">
						<colgroup>
							<col class="modified" />
							<col class="file-category" />
							<col class="title" />
							<col class="date" />
						</colgroup>
						<thead>
							<tr>
								<td class="modified">&nbsp;</td>
								<td class="file-category sortedASC"><div class="noLink">File Category</div></td>
								<td class="title"><div class="noLink">File Title</div></td>
								<td class="date">Last Updated</td>
							</tr>
						</thead>
						<tbody>
					<?php
					if ($results) {
						foreach ($results as $result) {
							$filename	= $result["file_name"];
							$parts		= pathinfo($filename);
							$ext		= $parts["extension"];
							?>
							<tr id="file-<?php echo $result["id"];?>" style="vertical-align: top;">
								<td class="modified">
									<?php echo (((int) $result["last_visited"]) ? (((int) $result["last_visited"] >= (int) $result["updated_date"]) ? "<img src=\"".ENTRADA_URL."/images/accept.png\" width=\"16\" height=\"16\" alt=\"You have already downloaded the latest version.\" title=\"You have already downloaded the latest version.\" />" : "<img src=\"".ENTRADA_URL."/images/exclamation.png\" width=\"16\" height=\"16\" alt=\"An updated version of this file is available.\" title=\"An updated version of this file is available.\" />") : "");?>
								</td>
								<td class="file-category">
									<?php echo ((isset($RESOURCE_CATEGORIES["course"][$result["file_category"]])) ? html_encode($RESOURCE_CATEGORIES["course"][$result["file_category"]]) : "Unknown Category");?>
								</td>
								<td class="title" style="white-space: normal; overflow: visible">
									<img 	src="<?php echo ENTRADA_URL;?>/serve-icon.php?ext=<?php echo $ext;?>"
											width="16"
											height="16"
											alt="<?php echo strtoupper($ext);?> Document"
											title="<?php echo strtoupper($ext);?> Document"/>
							<?php
							if (((!(int) $result["valid_from"]) || ($result["valid_from"] <= time())) && ((!(int) $result["valid_until"]) || ($result["valid_until"] >= time()))) { ?>
									<a 	href="<?php echo ENTRADA_URL;?>/file-course.php?id=<?php echo $result["id"];?>"
										title="Click to download <?php echo html_encode($result["file_title"]);?>"
										<?php echo (((int) $result["access_method"]) ? " target=\"_blank\"" : "");?>>
										<strong><?php echo html_encode($result["file_title"]);?></strong>
									</a>
							<?php
							} else { ?>
									<span class="content-small">
										<strong><?php echo html_encode($result["file_title"]);?></strong>
									</span>
							<?php
							} ?>
									<span class="content-small">(<?php echo readable_size($result["file_size"]);?>)</span>
										<div class="content-small space-above">
							<?php
							if (((int) $result["valid_from"]) && ($result["valid_from"] > time())) { ?>
											This file will be available for downloading <strong><?php echo date(DEFAULT_DATE_FORMAT, $result["valid_from"]);?></strong>.
							<?php
							} elseif (((int) $result["valid_until"]) && ($result["valid_until"] < time())) { ?>
											This file was only available for download until <strong><?php echo date(DEFAULT_DATE_FORMAT, $result["valid_until"]);?></strong>. Please contact the primary teacher for assistance if required.
							<?php
							}

							if (clean_input($result["file_notes"], array("allowedtags", "nows")) != "") {
								echo "<div class=\"clearfix\">".trim(strip_selected_tags($result["file_notes"], array("font")))."</div>";
							} ?>

										</div>
								</td>
								<td class="date">
									<?php echo (((int) $result["updated_date"]) ? date(DEFAULT_DATE_FORMAT, $result["updated_date"]) : "Unknown");?>
								</td>
							</tr>
						<?php
						}
					} else { ?>
							<tr>
								<td colspan="4">
									<div class="well well-small content-small">
										There have been no file downloads added to this course.
									</div>
								</td>
							</tr>
					<?php
					} ?>
						</tbody>
					</table>
					<br />
					<?php
					$query = "SELECT * FROM `course_links` WHERE `course_id`=".$db->qstr($COURSE_ID)." ORDER BY `link_title` ASC";
					$results = $db->GetAll($query);
					?>
					<table class="tableList" cellspacing="0" summary="List of Linked Resources">
						<colgroup>
							<col class="modified" />
							<col class="title" />
							<col class="date" />
						</colgroup>
						<thead>
							<tr>
								<td class="modified">&nbsp;</td>
								<td class="title sortedASC"><div class="noLink">Linked Resource</div></td>
								<td class="date">Last Updated</td>
							</tr>
						</thead>
						<tbody>
					<?php
					if ($results) {
						foreach ($results as $result) { ?>
							<tr style="vertical-align: top;">
								<td class="modified">
									<img 	src="<?php echo ENTRADA_URL;?>/images/url<?php echo (($result["proxify"] == "1") ? "-proxy" : "");?>.gif"
											width="16"
											height="16"
											alt="" />
								</td>
								<td class="title" style="overflow: visible">
							<?php
							if (((!(int) $result["valid_from"]) || ($result["valid_from"] <= time())) && ((!(int) $result["valid_until"]) || ($result["valid_until"] >= time()))) { ?>
									<a 	href="<?php echo ENTRADA_URL;?>/link-course.php?id=<?php echo $result["id"];?>"
										title="Click to visit <?php echo $result["link"];?>"
										target="_blank">
										<strong>
										<?php echo (($result["link_title"] != "") ? html_encode($result["link_title"]) : $result["link"]);?>
										</strong>
									</a>
							<?php
							} else { ?>
									<span style="color: #666666;">
										<strong>
										<?php echo (($result["link_title"] != "") ? html_encode($result["link_title"]) : "Untitled Link");?>
										</strong>
									</span>
							<?php
							} ?>

									<div class="content-small">
							<?php
							if (((int) $result["valid_from"]) && ($result["valid_from"] > time())) { ?>
										This link will become accessible <strong><?php echo date(DEFAULT_DATE_FORMAT, $result["valid_from"]);?></strong>.<br /><br />
							<?php
							} elseif (((int) $result["valid_until"]) && ($result["valid_until"] < time())) { ?>
										This link was only accessible until <strong><?php echo date(DEFAULT_DATE_FORMAT, $result["valid_until"]);?></strong>. Please contact the primary teacher for assistance if required.<br /><br />
							<?php
							}

							if (clean_input($result["link_notes"], array("allowedtags", "nows")) != "") {
									echo "<div class=\"clearfix\">".trim(strip_selected_tags($result["link_notes"], array("font")))."</div>";
							} ?>
									</div>
								</td>
								<td class="date">
									<?php echo (((int) $result["updated_date"]) ? date(DEFAULT_DATE_FORMAT, $result["updated_date"]) : "Unknown");?>
								</td>
							</tr>
						<?php
						}
					} else {
                        ?>
                        <tr>
                            <td colspan="3">
                                <div class="well well-small content-small">
                                    There have been no linked resources added to this course.
                                </div>
                            </td>
                        </tr>
                        <?php
					}
                    ?>
						</tbody>
					</table>
                    <br />
                    <?php
                    $query = "SELECT * FROM `course_lti_consumers` WHERE `course_id`=".$db->qstr($COURSE_ID)." ORDER BY `lti_title` ASC";
                    $results = $db->GetAll($query);
                    ?>
                    <script type="text/javascript">
                        var ajax_url = '';
                        var modalDialog;

                        function submitLTIForm() {
                            jQuery('#ltiSubmitForm').submit();
                        }

                        function openLTIDialog(url) {
                            var width  = jQuery(window).width() * 0.9,
                                height = jQuery(window).height() * 0.9;

                            if(width < 400) { width = 400; }
                            if(height < 400) { height = 400; }

                            modalDialog = new Control.Modal($('#false-link'), {
                                position:		'center',
                                overlayOpacity:	0.75,
                                closeOnClick:	'overlay',
                                className:		'modal',
                                fade:			true,
                                fadeDuration:	0.30,
                                width: width,
                                height: height,
                                afterOpen: function(request) {
                                    eval($('scripts-on-open').innerHTML);
                                },
                                beforeClose: function(request) {
                                    jQuery('#ltiContainer').remove();
                                }
                            });

                            new Ajax.Request(url, {
                                method: 'get',
                                parameters: 'width=' + width + '&height=' + height,
                                onComplete: function(transport) {
                                    modalDialog.container.update(transport.responseText);
                                    modalDialog.open();
                                }
                            });
                        }

                        function closeLTIDialog() {
                            modalDialog.close();
                        }
                    </script>

                    <table class="tableList" cellspacing="0" summary="LTI Provider of Resources">
                        <colgroup>
                            <col class="modified" />
                            <col class="title" />
                            <col class="date" />
                        </colgroup>
                        <thead>
                        <tr>
                            <td class="modified">&nbsp;</td>
                            <td class="title sortedASC"><div class="noLink">LTI Provider</div></td>
                            <td class="date">Last Updated</td>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        if ($results) {
                            foreach ($results as $result) { ?>
                                <tr style="vertical-align: top;">
                                    <td class="modified"></td>
                                    <td class="title" style="overflow: visible">
                                        <?php
                                        if (((!(int) $result["valid_from"]) || ($result["valid_from"] <= time())) && ((!(int) $result["valid_until"]) || ($result["valid_until"] >= time()))) { ?>
                                            <a href="javascript:void(0)"
                                               onclick="openLTIDialog('<?php echo ENTRADA_URL;?>/api/lti-consumer-runner.api.php?ltiid=<?php echo $result["id"];?>')"
                                               title="Click to visit <?php echo $result["lti_title"];?>">
                                                <strong>
                                                    <?php echo (($result["lti_title"] != "") ? html_encode($result["lti_title"]) : '');?>
                                                </strong>
                                            </a>
                                        <?php
                                        } else { ?>
                                            <span style="color: #666666;">
                                                <strong>
                                                    <?php echo (($result["lti_title"] != "") ? html_encode($result["lti_title"]) : '');?>
                                                </strong>
                                            </span>
                                        <?php
                                        } ?>

                                        <div class="content-small">
                                            <?php
                                            if (((int) $result["valid_from"]) && ($result["valid_from"] > time())) { ?>
                                                This link will become accessible <strong><?php echo date(DEFAULT_DATE_FORMAT, $result["valid_from"]);?></strong>.<br /><br />
                                            <?php
                                            } elseif (((int) $result["valid_until"]) && ($result["valid_until"] < time())) { ?>
                                                This link was only accessible until <strong><?php echo date(DEFAULT_DATE_FORMAT, $result["valid_until"]);?></strong>. Please contact the primary teacher for assistance if required.<br /><br />
                                            <?php
                                            }

                                            if (clean_input($result["link_notes"], array("allowedtags", "nows")) != "") {
                                                echo "<div class=\"clearfix\">".trim(strip_selected_tags($result["link_notes"], array("font")))."</div>";
                                            } ?>
                                        </div>
                                    </td>
                                    <td class="date">
                                        <?php echo (((int) $result["updated_date"]) ? date(DEFAULT_DATE_FORMAT, $result["updated_date"]) : "Unknown");?>
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            ?>
                            <tr>
                                <td colspan="3">
                                    <div class="well well-small content-small">
                                        There have been no linked resources added to this course.
                                    </div>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
				<?php
				/**
				 * Sidebar item that will provide the links to the different sections within this page.
				 */
				$sidebar_html  = "<ul class=\"menu\">\n";
				if ($course_details_section) {
					$sidebar_html .= "	<li class=\"link\"><a href=\"#course-details-section\" title=\"Course Details\">" . $translate->_("course") . " Details</a></li>\n";
				}
				if ($course_objectives_section) {
					$sidebar_html .= "	<li class=\"link\"><a href=\"#course-objectives-section\" title=\"" . $translate->_("Course Objectives") . "\">" . $translate->_("course") . " " . $translate->_("Objectives") . "</a></li>\n";
				}
				if ($course_resources_section) {
					$sidebar_html .= "	<li class=\"link\"><a href=\"#course-resources-section\" title=\"Course Resources\">" . $translate->_("course") . " Resources</a></li>\n";
				}
				$sidebar_html .= "</ul>\n";

				new_sidebar_item("Page Anchors", $sidebar_html, "page-anchors", "open", "1.9");
			} else {
				$ERROR++;
				$ERRORSTR[] = "You do not have the permissions required to view this course. If you believe that you have received this message in error, please contact a system administrator.";

				echo display_error();
			}
		}
	} else {
		$sidebar_html  = "<div><form action=\"".ENTRADA_RELATIVE."/curriculum/search\" method=\"get\" style=\"margin:0\">\n";
		$sidebar_html .= "<label for=\"q\" class=\"form-nrequired\">Search the curriculum:</label><br />";
		$sidebar_html .= "<input type=\"text\" id=\"q\" name=\"q\" value=\"\" style=\"width: 95%\" /><br />\n";
		$sidebar_html .= "<span style=\"float: left; padding-top: 7px;\"><a href=\"".ENTRADA_RELATIVE."/curriculum/search\" style=\"font-size: 11px\">Advanced Search</a></span>\n";
		$sidebar_html .= "<span style=\"float: right; padding-top: 4px;\"><input type=\"submit\" class=\"btn btn-primary\" value=\"Search\" /></span>\n";
		$sidebar_html .= "</form></div>\n";

		new_sidebar_item("Our Curriculum", $sidebar_html, "curriculum-search-bar", "open");
		if ($COURSE_LIST) {
		?>
		<div class="row-fluid">
			<div class="pull-left">
			<?php $query	= "SELECT * FROM `curriculum_lu_types` WHERE `curriculum_type_active` = '1' ORDER BY `curriculum_type_order` ASC";
			$terms	= $db->GetAll($query);
				if ($terms) {
					echo "<h2>". $translate->_("course") . " Listing</h2>\n";
			}
			?>
			</div>
			<form class="pull-right form-horizontal">
				<div class="control-group">
					<label for="course-quick-select" class="control-label content-small"><?php echo $translate->_("course"); ?> Quick Select:</label>
					<div class="controls">
					<select id="course-quick-select" name="course-quick-select" onchange="window.location='<?php echo ENTRADA_URL; ?>/courses?org=<?php echo $ORGANISATION_ID;?>&id='+this.options[this.selectedIndex].value">
					<option value="">-- Select a <?php echo $translate->_("course"); ?> --</option>
					<?php
					foreach ($COURSE_LIST as $course_id => $course_name) {
						echo "<option value=\"".$course_id."\">".$course_name."</option>\n";
					}
					?>
					</select>
					</div><!--/controls-->
				</div>
			</form>
		</div> <!--/row-fluid-->
		<?php
		}
		$query	= "SELECT * FROM `curriculum_lu_types` WHERE `curriculum_type_active` = '1' ORDER BY `curriculum_type_order` ASC";
		$terms	= $db->GetAll($query);
		$course_flag = false;
		if ($terms) {
			echo "<ol class=\"curriculum-layout\">\n";
			foreach ($terms as $term) {
				$courses = courses_fetch_courses(true, true, $term["curriculum_type_id"]);
				if ($courses) {
					$course_flag = true;
					echo "<li><h3>".html_encode($term["curriculum_type_name"])."</h3>\n";
					echo "	<ul class=\"course-list\">\n";
					foreach ($courses as $course) {
						$query = "	SELECT b.`community_url` FROM `community_courses` AS a
									JOIN `communities` AS b
									ON a.`community_id` = b.`community_id`
									WHERE a.`course_id` = ".$db->qstr($course["course_id"]);
						$course_community = $db->GetOne($query);

						$course_code = strtoupper(clean_input($course_community, "alphanumeric"));
						
						$syllabus = Models_Syllabus::fetchRowByCourseID($course["course_id"], $active = 1);
						if ($course_community && $syllabus->getActive()) {
							
							$syllabi = glob(SYLLABUS_STORAGE."/".$course_code."*-syllabus-" . ($year != 0 ? $year : date("Y", time())). "*");
                            
                            if (!$syllabi) {
                                $syllabi = glob(SYLLABUS_STORAGE."/".$course["course_code"]."*-syllabus-" . ($year != 0 ? $year : date("Y", time())). "*");
                            }
                            
							$prefix = substr($syllabi[0], strrpos($syllabi[0], "/") + 1);
							$prefix = substr($prefix, 0, strpos($prefix, "-"));

							if ($syllabi) {
								$syllabus_month = 0;
								foreach ($syllabi as $syllabus) {
									$month = substr($syllabus, strrpos($syllabus, "-") + 1, strlen($syllabus));
									$month = substr($month, 0, strrpos($month, ".pdf"));
									if ($month > $syllabus_month) {
										$syllabus_month = $month;
									}
								}
							}

							$file_realpath = SYLLABUS_STORAGE."/".$prefix."-syllabus-". ($year != 0 ? $year : date("Y", time())) . "-".$syllabus_month.".pdf";
							if (file_exists($file_realpath)) {
								$syllabi[$course["course_code"]] = array(
									"url" => ENTRADA_URL."/community".$course_community."?id=".$course["course_id"]."&method=serve-syllabus&course_code=".$course_code."&month=".$syllabus_month
								);
							}
						}
						echo "<li><a href=\"".ENTRADA_URL."/courses?id=".$course["course_id"]."\">".html_encode($course["course_code"]." - ".$course["course_name"])."</a>".(isset($syllabi[$course["course_code"]]) ? " <a href=\"".$syllabi[$course["course_code"]]["url"]."\" title=\"Download Syllabus\"><i class=\"icon-file\"></i></a>" : "")."</li>\n";
					}
					echo "	</ul>\n";
					echo "</li>\n";
				}
			}
			echo "</ol>\n";
		}
		if (!$course_flag) {
			echo display_notice(array("There are no courses to display."));
		}
	}
}
