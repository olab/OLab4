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
 * This file is used to add AAMC Curriculum Inventory records to the
 * entrada.reports_aamc_ci table.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <simpson@queensu.ca>
 * @copyright Copyright 2012 Queen's University. All Rights Reserved.
 *
*/

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_AAMC_CI"))) {
	exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("report", "read", false)) {
	$ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/".$MODULE."\\'', 15000)";

	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
	$BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/reports/aamc".replace_query(array("section" => "add")), "title" => "Create Report");

	$PROCESSED["organisation_id"] = $ENTRADA_USER->getActiveOrganisation();

	$org = Organisation::get($PROCESSED["organisation_id"]);

    $academic_level_ids = array();

    $aamc = new Models_Reports_Aamc($PROCESSED["organisation_id"]);
    $academic_levels = $aamc->getCurriculumTypes();
    if ($academic_levels) {
        foreach ($academic_levels as $level) {
            $academic_level_ids[] = $level["curriculum_type_id"];
        }
    }

	echo "<h1>Create AAMC Curriculum Inventory Report</h1>\n";

	// Error Checking
	switch($STEP) {
		case 2 :
            $PROCESSED["report_date"] = time();
            $PROCESSED["report_language"] = "en-us";
            $PROCESSED["report_active"] = 1;
            $PROCESSED["report_status"] = "published";

            /**
             * Required field "report_year" / Reporting Period.
             */
            if (isset($_POST["report_year"]) && $report_year = (int) $_POST["report_year"]) {
                $PROCESSED["report_year"] = $report_year;

                $PROCESSED["report_start"] = $report_year . "-07-01";
                $PROCESSED["report_finish"] = ($report_year + 1) . "-06-30";
                $PROCESSED["collection_start"] = strtotime("July 1 " . $report_year . " 00:00:00");
                $PROCESSED["collection_finish"] = strtotime("June 30 " . ($report_year + 1) . " 23:59:59");

            } else {
                add_error("You must select a <strong>Reporting Period</strong>.");
            }

            /**
			 * Required field "report_title" / Report Title.
			 */
			if ((isset($_POST["report_title"])) && ($report_title = clean_input($_POST["report_title"], array("notags", "trim")))) {
				$query = "SELECT * FROM `reports_aamc_ci` WHERE `report_title` LIKE ".$db->qstr($report_title)." AND `report_active` = 1";
				$result = $db->GetRow($query);
				if (!$result) {
					$PROCESSED["report_title"] = $report_title;
				} else {
					add_error("The <strong>Report Title</strong> field that you entered is not unique.");
				}
			} else {
				add_error("The <strong>Report Title</strong> field is required.");
			}

			/**
			 * Non-Required field "report_description" / Report Description.
			 */
			if ((isset($_POST["report_description"])) && ($report_description = clean_input($_POST["report_description"], array("allowedtags", "trim")))) {
				$PROCESSED["report_description"] = $report_description;
			} else {
				$PROCESSED["report_description"] = "";
			}

			/**
			 * Non-Required field "report_supporting_link" / Supporting Link.
			 */
			if ((isset($_POST["report_supporting_link"])) && ($report_supporting_link = clean_input($_POST["report_supporting_link"], array("notags", "trim"))) && ($report_supporting_link != "http://")) {
				$PROCESSED["report_supporting_link"] = $report_supporting_link;
			} else {
				$PROCESSED["report_supporting_link"] = "";
			}

            /**
             * Non-Required field "program_level_objective_id" / Program Level Objectives.
             */
            if (isset($_POST["program_level_objective_id"]) && ($program_level_objective_id = clean_input($_POST["program_level_objective_id"], "int"))) {
                $PROCESSED["program_level_objective_id"] = $program_level_objective_id;
            } else {
                $PROCESSED["program_level_objective_id"] = NULL;
            }

            $PROCESSED["report_params"] = "";
            if (isset($_POST["academic_levels"]) && is_array($_POST["academic_levels"]) && !empty($_POST["academic_levels"])) {
                $report_params = array();

                foreach ($_POST["academic_levels"] as $academic_level) {
                    $academic_level = (int) $academic_level;

                    if (in_array($academic_level, $academic_level_ids)) {
                        if (isset($_POST["academic-level-".$academic_level."-proxy-ids"]) && $proxy_ids = clean_input($_POST["academic-level-".$academic_level."-proxy-ids"], array("striptags", "trim"))) {
                            $ids = explode(",", $proxy_ids);

                            $report_params[$academic_level] = array();

                            foreach ($ids as $id) {
                                $id = (int) $id;

                                if ($id) {
                                    $report_params[$academic_level][] = $id;
                                }
                            }
                        }
                    }
                }

                if (!empty($report_params)) {
                    $PROCESSED["report_params"] = json_encode($report_params);
                } else {
                    add_error("You must select at least one curriculum level to report on, and provide students who represent that academic level.");
                }
            } else {
                add_error("You must select at least one curriculum level to report on.");
            }

			if (!$ERROR) {
				$PROCESSED["updated_date"] = time();
				$PROCESSED["updated_by"] = $ENTRADA_USER->getID();

				if ($db->AutoExecute("reports_aamc_ci", $PROCESSED, "INSERT")) {
					if ($report_id = $db->Insert_Id()) {
						$url = ENTRADA_URL . "/admin/reports/aamc";

						add_success("You have successfully created <strong>".html_encode($PROCESSED["report_title"])."</strong>.<br /><br />You will now be redirected back to the report index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.");

						$ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";

						application_log("success", "New AAMC curriculum inventory report [".$report_id."] created.");
					}
				} else {
					add_error("There was a problem creating this report at this time. The system administrator was informed of this error; please try again later.");

					application_log("error", "There was an error inserting an AAMC curriculum inventory report. Database said: ".$db->ErrorMsg());
				}
			}

			if ($ERROR) {
				$STEP = 1;
			}
		break;
		case 1 :
		default :
			$timestamp = time();
			$start_year = (date("Y") - ($timestamp < strtotime(ACADEMIC_YEAR_START_DATE) ? 2 : 1));

			$PROCESSED["report_date"] = $timestamp;
            $PROCESSED["report_year"] = $start_year;


			$PROCESSED["report_start"]	= strtotime("September 1st ".$start_year." 00:00:00");
			$PROCESSED["report_finish"] = strtotime("August 31st ".($start_year + 1)." 23:59:59");

			$PROCESSED["report_title"] = $org->getAAMCInstitutionName() . " Curriculum " . date("Y", $PROCESSED["report_start"]) . "-" . date("Y", $PROCESSED["report_finish"]);
		break;
	}

	// Display Content
	switch($STEP) {
		case 2 :
			display_status_messages();
		break;
		case 1 :
		default :
			display_status_messages();
			?>
			<form action="<?php echo ENTRADA_RELATIVE; ?>/admin/reports/aamc?section=add&amp;step=2" method="post" id="addAAMCCiReport" class="form-horizontal">
                <div class="control-group">
                    <label class="control-label form-required" for="report_year">Reporting Period:</label>
                    <div class="controls">
                        <select id="report_year" name="report_year">
							<?php
							$start_year = (fetch_first_year() - 4);
							for ($year = $start_year; $year >= ($start_year - 3); $year--) {
                                ?>
								<option value="<?php echo $year; ?>"<?php echo ($year == $PROCESSED["report_year"]) ? " selected=\"selected\"" : "" ; ?>><?php echo "July 1st " . $year . " - June 30th " . ($year + 1); ?></option>
                                <?php
                            }
                            ?>
						</select>
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label form-required" for="report_title">Report Title:</label>
                    <div class="controls">
                        <input type="text" id="report_title" name="report_title" class="span10" value="<?php echo ((isset($PROCESSED["report_title"]) && $PROCESSED["report_title"]) ? html_encode($PROCESSED["report_title"]) : ""); ?>" maxlength="255" />
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label form-nrequired" for="report_description">Report Description:</label>
                    <div class="controls">
						<textarea id="report_description" name="report_description" class="span10 expandable"><?php echo html_encode($PROCESSED["report_description"]); ?></textarea>
                    </div>
                </div>

                <div class="control-group">
                    <label class="control-label form-nrequired" for="report_description">Supporting Link:</label>
                    <div class="controls">
						<input type="text" id="report_supporting_link" name="report_supporting_link" class="span10" value="<?php echo ((isset($PROCESSED["report_supporting_link"]) && $PROCESSED["report_supporting_link"]) ? html_encode($PROCESSED["report_supporting_link"]) : "http://"); ?>" maxlength="255" />
                    </div>
                </div>

                <?php
                /**
                 * Fetch a list of Curriculum Tag Sets for this organization.
                 */
                $curriculum_tag_sets = Models_Objective::fetchAllByOrganisationParentID($PROCESSED["organisation_id"]);

                if ($curriculum_tag_sets) {
                    ?>
                    <div class="control-group">
                        <label class="control-label form-nrequired" for="report_description">Program Level Objectives:</label>
                        <div class="controls">
                            If you have defined <em>Program Level Objectives</em> that you would like to articulate in the Curriculum Inventory Report, select which of your <a href="<?php echo ENTRADA_RELATIVE; ?>/admin/curriculum/objectives" target="_blank">Curriculum Tag Sets</a> represents these.

                            <select class="span10 space-above" id="program_level_objective_id" name="program_level_objective_id">
                                <option value="0">-- Select Program Objectives Curriculum Tag Set --</option>
                                <?php
                                foreach ($curriculum_tag_sets as $tag_set) {
                                    echo "<option value=\"" . $tag_set->getId() . "\"" . ((isset($PROCESSED["program_level_objective_id"]) && $PROCESSED["program_level_objective_id"] == $tag_set->getId()) ? " selected=\"selected\"" : "")  . ">" . html_encode($tag_set->getName()) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <?php
                }
                ?>

                <div class="control-group">
                    <label class="control-label form-required" for="report_description">Academic Levels:</label>
                    <div class="controls">
                        Please select the <a href="<?php echo ENTRADA_RELATIVE; ?>/admin/settings/manage/curriculumtypes?org=<?php echo $ACTIVE_ORG->getID(); ?>" target="_blank">Curriculum Layout</a> options that you wish to include in your curriculum inventory report, as well as a representative learner that best describes your curriculum during the selected reporting period.
                        <style>
                            div.academic-level-audience {
                                margin-left: 25px;
                                display: none;
                                position: relative;
                                margin-bottom: 10px;
                            }
                        </style>

                        <hr />

                        <?php
                        if ($academic_levels) {
                            foreach ($academic_levels as $level) {
                                ?>
                                <div class="academic-level-container">
                                    <label class="checkbox"><input type="checkbox" name="academic_levels[]" id="academic-level-<?php echo $level["curriculum_type_id"]; ?>" value="<?php echo $level["curriculum_type_id"]; ?>"<?php echo (((!isset($PROCESSED["academic_levels"]) || in_array($level["curriculum_type_id"], $PROCESSED["academic_levels"])) && (int) $level["curriculum_type_active"]) ? " checked=\"checked\"" : ""); ?> onclick="jQuery('#academic-level-<?php echo $level["curriculum_type_id"]; ?>-audience').toggle('slow');" /> <?php echo html_encode($level["curriculum_type_name"]); ?></label>
                                    <div class="academic-level-audience" id="academic-level-<?php echo $level["curriculum_type_id"]; ?>-audience">
                                        <label for="academic-level-<?php echo $level["curriculum_type_id"]; ?>-proxy-ids">Comma separated proxy_ids of students representing <?php echo html_encode($level["curriculum_type_name"]); ?>:</label><br />
                                        <input type="text" id="academic-level-<?php echo $level["curriculum_type_id"]; ?>-proxy-ids" name="academic-level-<?php echo $level["curriculum_type_id"]; ?>-proxy-ids" value="<?php echo (isset($PROCESSED["academic_level_proxy_ids"][$level["curriculum_type_id"]]) ? $PROCESSED["academic_level_proxy_ids"][$level["curriculum_type_id"]] : ""); ?>" autocomplete="off" />
                                    </div>
                                </div>
                                <?php
                            }
                        } else {
                            echo display_notice(array("This organisation does not have any <a href=\"".ENTRADA_RELATIVE."/admin/settings/manage/curriculumtypes?org=".$ACTIVE_ORG->getID()."\">Curriculum Types</a> defined."));
                        }
                        ?>
                    </div>
                </div>

                <div class="control-group">
                    <a class="btn" href="<?php echo ENTRADA_RELATIVE; ?>/admin/reports/aamc">Cancel</a>
                    <div class="pull-right">
                        <input type="submit" class="btn btn-primary" value="Create" />
                    </div>
                </div>
			</form>
            <script type="text/javascript">
            jQuery(function($) {
                jQuery('div.academic-level-container input[type=checkbox]:checked').each(function(el) {
                    jQuery('#' + this.id + '-audience').show();
                });
            });
            </script>
			<?php
		break;
	}
}
