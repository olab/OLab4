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
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 *
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_ASSESSMENT"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("assessments", "read", false)) {
    add_error(sprintf($translate->_("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:%s\">%s</a> for assistance."), html_encode($AGENT_CONTACTS["administrator"]["email"]), html_encode($AGENT_CONTACTS["administrator"]["name"])));

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    $BREADCRUMB[] = array("url" => ENTRADA_URL."/".$MODULE."/".$SUBMODULE."/".$SECTION, "title" => $translate->_("Assessment Targets"));
    $HEAD[] = "<link href=\"".ENTRADA_URL."/css/assessments/assessment-public-index.css\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
    $HEAD[]	= "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/assessments/assessment-targets.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";

    if (isset($_GET["adistribution_id"]) && $tmp_input = clean_input($_GET["adistribution_id"], array("trim", "int"))) {
        $PROCESSED["adistribution_id"] = $tmp_input;
    } else {
        add_error($translate->_("No Distribution identifier provided"));
    }

    if (isset($_GET["dassessment_id"]) && $tmp_input = clean_input($_GET["dassessment_id"], array("trim", "int"))) {
        $PROCESSED["dassessment_id"] = $tmp_input;
    } else {
        add_error($translate->_("No Assessment identifier provided."));
    }

    if (!$ERROR) {
        $HEAD[]	= "<script type=\"text/javascript\">var distribution_id =\"" . $PROCESSED["adistribution_id"] . "\"</script>";
        $HEAD[]	= "<script type=\"text/javascript\">var ENTRADA_URL =\"" . ENTRADA_URL . "\"</script>";
        $distribution = Models_Assessments_Distribution::fetchRowByIDIgnoreDeletedDate($PROCESSED["adistribution_id"]);

        if ($distribution) {
            $targets = array();
            $overall_max_submittable = 0;
            $individual_max_submittable = 0;
            $targets_pending = 0;
            $targets_inprogress = 0;
            $targets_complete = 0;
            $overall_attempts_completed = 0;

            $assessor_model = new Models_Assessments_Assessor();
            $assessment_record = $assessor_model->fetchRowByID($PROCESSED["dassessment_id"]);

            if ($assessment_record) {
                $assessment_overall_progress = $assessment_record->getOverallProgressDetails($assessment_record->getAssessorValue(), ($assessment_record->getAssessorType() == "external" ? true : false));

                $overall_max_submittable = $assessment_overall_progress["max_overall_attempts"];
                $individual_max_submittable = $assessment_overall_progress["max_individual_attempts"];
                $targets_pending = $assessment_overall_progress["targets_pending"];
                $targets_inprogress = $assessment_overall_progress["targets_inprogress"];
                $targets_complete = $assessment_overall_progress["targets_complete"];
                $overall_attempts_completed = (isset($assessment_overall_progress["overall_attempts_completed"]) && $assessment_overall_progress["overall_attempts_completed"] ? $assessment_overall_progress["overall_attempts_completed"] : 0);

                $delegator = (isset($assessment_overall_progress["delegator"]) && $assessment_overall_progress["delegator"] ? $assessment_overall_progress["delegator"] : false);

                //(isset($assessment_overall_progress["targets"]) && $assessment_overall_progress["targets"] ? $assessment_overall_progress["targets"] : 0);
                if (isset($assessment_overall_progress["targets"]) && !empty($assessment_overall_progress["targets"])) {
                    $targets = $assessment_overall_progress["targets"];
                }
            }

            if ($targets) {
                if (!isset($PREFERENCES["target_view"]) || (isset($PREFERENCES["target_view"]) && $PREFERENCES["target_view"] != "") || (isset($PREFERENCES["target_view"]) && !$PREFERENCES["target_view"])) {
                    $PREFERENCES["target_view"] = "list";
                }

                if (isset($_GET["target_status_view"]) && $tmp_input = clean_input($_GET["target_status_view"], array("trim", "striptags"))) {
                    $PREFERENCES["target_status_view"] = $tmp_input;
                }
                ?>
                <div id="assessment-block" class="clearfix space-below">
                    <div id="targets-pending-card" class="span4 assessment-card">
                        <h4 class="pending"><?php echo $translate->_("Not Started"); ?></h4>
                        <div class="assessment-card-count pending"><?php echo ($targets_pending < 10 ? "0" . $targets_pending : $targets_pending); ?></div>
                        <p class="assessment-card-description pending"><?php echo sprintf($translate->_("You have %s form(s) that have not been started."), $targets_pending); ?></p>
                        <a class="target-status-btn <?php echo (!isset($PREFERENCES["target_status_view"]) ? "active" : (isset($PREFERENCES["target_status_view"]) && $PREFERENCES["target_status_view"] === "pending" ? "active" : "")); ?>" id="targets-pending-btn" data-target-status="pending" href="#"><?php echo $translate->_("View Pending Targets"); ?></a>
                    </div>
                    <div id="targets-inprogress-card" class="span4 assessment-card">
                        <h4 class="inprogress"><?php echo $translate->_("In Progress"); ?></h4>
                        <div class="assessment-card-count inprogress"><?php echo ($targets_inprogress < 10 ? "0" . $targets_inprogress : $targets_inprogress); ?></div>
                        <p class="assessment-card-description inprogress"><?php echo sprintf($translate->_("You have %s form(s) that are in progress but not complete."), $targets_inprogress); ?></p>
                        <a class="target-status-btn <?php echo (isset($PREFERENCES["target_status_view"]) && $PREFERENCES["target_status_view"] === "inprogress" ? "active" : "") ?>" id="targets-inprogress-btn" data-target-status="inprogress" href="#"><?php echo $translate->_("View Targets In Progress"); ?></a>
                    </div>
                    <div id="targets-complete-card" class="span4 assessment-card">
                        <h4 class="complete"><?php echo $translate->_("Completed"); ?></h4>
                        <div class="assessment-card-count complete"><?php echo ($targets_complete < 10 ? "0" . $targets_complete : $targets_complete); ?></div>
                        <p class="assessment-card-description complete"><?php echo sprintf($translate->_("You have %s form(s) that are complete."), $targets_complete); ?></p>
                        <a class="target-status-btn <?php echo (isset($PREFERENCES["target_status_view"]) && $PREFERENCES["target_status_view"] === "complete" ? "active" : "") ?>" id="targets-complete-btn" data-target-status="complete" href="#"><?php echo $translate->_("View Completed Targets"); ?></a>
                    </div>
                </div>
                <div id="target-search-block" class="clearfix space-below medium">
                    <input class="search-icon" type="text" id="target-search-input" placeholder="<?php echo $translate->_("Search Targets...") ?>" />
                    <div id="item-view-controls" class="btn-group space-left">
                        <a href="#" data-view="list" id="list-view" class="btn view-toggle <?php echo ($PREFERENCES["target_view"] === "list" ? "active" : ""); ?>" title="<?php $translate->_("Toggle List View"); ?>"><i class="icon-align-justify"></i></a>
                        <a href="#" data-view="grid" id="detail-view" class="btn view-toggle <?php echo ($PREFERENCES["target_view"] === "grid" ? "active" : ""); ?>" title="<?php $translate->_("Toggle Gird View"); ?>"><i class="icon-th-large"></i></a>
                    </div>
                </div>
                <?php
                echo "<div id=\"targets-pending-container\" class=\"targets-container " . (!isset($PREFERENCES["target_status_view"]) ? "" : (isset($PREFERENCES["target_status_view"]) && $PREFERENCES["target_status_view"] === "pending" ? "" : "hide")) . "\">";
                echo "<h2>" . $translate->_("Pending Targets") . "</h2>";
                if ($targets_pending) { ?>
                    <div id="targets-pending-table" class="target-table <?php echo ($PREFERENCES["target_view"] === "grid" ? "hide" : ""); ?>">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th width="40%"><?php echo $translate->_("Name"); ?></th>
                                    <th width="45%"><?php echo $translate->_("Email"); ?></th>
                                    <th width="15%"><?php echo $translate->_("Number"); ?></th>
                                    <th width="15%"><?php echo $translate->_("Attempts"); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach ($targets as $target) {
                                    if (in_array("pending", $target["progress"])) {
                                        if (!in_array("inprogress", $target["progress"]) && (!isset($target["completed_attempts"]) || !$target["completed_attempts"] || $individual_max_submittable > $target["completed_attempts"])) {
                                            $url = ENTRADA_URL . "/assessments/assessment?adistribution_id=" . html_encode($distribution->getID()) . "&schedule_id=" . (isset($schedule) && $schedule ? html_encode($schedule->getID()) : "") . "&target_record_id=" . html_encode($target["target_record_id"]) . "&dassessment_id=" . $PROCESSED["dassessment_id"]; ?>
                                            <tr class="target-pending-block target-block">
                                                <td>
                                                    <a href="<?php echo $url ?>"><?php echo html_encode($target["name"]); ?></a>
                                                </td>
                                                <td>
                                                    <a href="<?php echo $url ?>"><?php echo html_encode(isset($target["email"]) ? $target["email"] : "N/A") ?></a>
                                                </td>
                                                <td>
                                                    <a href="<?php echo $url ?>"><?php echo html_encode(isset($target["number"]) ? $target["number"] : "N/A") ?></a>
                                                </td>
                                                <td>
                                                    <a href="<?php echo $url ?>"><?php echo html_encode((isset($target["completed_attempts"]) && $target["completed_attempts"] ? $target["completed_attempts"] : "0") . " / " . $individual_max_submittable) ?></a>
                                                </td>
                                            </tr>
                                            <?php
                                        }
                                    }
                                }
                                ?>
                                <tr class="hide no-search-targets">
                                    <td colspan="3"><?php echo $translate->_("No targets found matching your search criteria."); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <ul id="targets-pending-grid" class="target-grid <?php echo ($PREFERENCES["target_view"] === "grid" ? "" : "hide") ?>">
                    <?php
                    foreach ($targets as $target) {
                        if (in_array("pending", $target["progress"])) {
                            if (!in_array("inprogress", $target["progress"]) && (!isset($target["completed_attempts"]) || !$target["completed_attempts"] || $individual_max_submittable > $target["completed_attempts"])) {
                                $url = ENTRADA_URL . "/assessments/assessment?adistribution_id=" . html_encode($distribution->getID()) . "&schedule_id=" . (isset($schedule) && $schedule ? html_encode($schedule->getID()) : "") . "&target_record_id=" . html_encode($target["target_record_id"]) . "&dassessment_id=" . $PROCESSED["dassessment_id"]; ?>
                                <li class="media assessment-target-media-list target-pending-block target-block">
                                    <div class="assessment-target-media-list-wrapper">
                                        <div class="assessment-target-media-list-container">
                                            <?php echo "<img src=\"" . webservice_url("photo", array($ENTRADA_USER->getID(), (isset($uploaded_file_active) && $uploaded_file_active ? "upload" : (!file_exists(STORAGE_USER_PHOTOS . "/" . $ENTRADA_USER->getID() . "-official") && file_exists(STORAGE_USER_PHOTOS . "/" . $ENTRADA_USER->getID() . "-upload") ? "upload" : "official")))) . "\" width=\"42\" height=\"42\" alt=\"" . html_encode($_SESSION["details"]["firstname"] . " " . $_SESSION["details"]["lastname"]) . "\" class=\"img-circle\" />"; ?>
                                            <h3 class="media-heading"><?php echo html_encode($target["name"]) ?><span><?php echo html_encode($target["number"]); ?></span></h3>
                                            <a href="mailto:<?php echo html_encode($target["email"]) ?>"><?php echo html_encode($target["email"]) ?></a>
                                        </div>
                                        <div class="assessment-target-media-list-parent">
                                            <a href="<?php echo $url; ?>">View Assessment &rtrif;</a>
                                        </div>
                                    </div>
                                </li>
                                <?php
                            }
                        }
                    }
                    ?>
                    </ul>
                <?php } else { ?>
                    <p id="no-pending-targets" class="no-targets"><?php echo $translate->_("There are currently no targets pending.")?></p>
                <?php }
                echo "</div>";
                echo "<div id=\"targets-inprogress-container\" class=\"targets-container ". (isset($PREFERENCES["target_status_view"]) && $PREFERENCES["target_status_view"] === "inprogress" ? "" : "hide") ."\">";
                echo "<h2>" . $translate->_("Targets In Progress") . "</h2>";
                if ($targets_inprogress) { ?>
                    <div id="targets-inprogress-table" class="target-table <?php echo ($PREFERENCES["target_view"] == "list" ? "" : "hide"); ?>">
                        <table class="table table-striped table-bordered">
                            <thead>
                            <tr>
                                <th width="40%"><?php echo $translate->_("Name"); ?></th>
                                <th width="45%"><?php echo $translate->_("Email"); ?></th>
                                <th width="15%"><?php echo $translate->_("Number"); ?></th>
                                <th width="15%"><?php echo $translate->_("Attempts"); ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($targets as $target) {
                                    if (in_array("inprogress", $target["progress"])) {
                                        $url = ENTRADA_URL . "/assessments/assessment?adistribution_id=" . html_encode($distribution->getID()) . "&schedule_id=" . (isset($schedule) && $schedule ? html_encode($schedule->getID()) : "") . "&target_record_id=" . html_encode($target["target_record_id"]) . (array_key_exists("aprogress_id", $target) ? "&aprogress_id=" . html_encode($target["aprogress_id"]) : "") . "&dassessment_id=" . $PROCESSED["dassessment_id"]; ?>
                                        <tr class="target-inprogress-block target-block">
                                            <td>
                                                <a href="<?php echo $url ?>"><?php echo html_encode($target["name"]) ?></a>
                                            </td>
                                            <td>
                                                <a href="<?php echo $url ?>"><?php echo html_encode($target["email"]) ?></a>
                                            </td>
                                            <td>
                                                <a href="<?php echo $url ?>"><?php echo html_encode($target["number"]) ?></a>
                                            </td>
                                            <td>
                                                <a href="<?php echo $url ?>"><?php echo html_encode((isset($target["completed_attempts"]) && $target["completed_attempts"] ? $target["completed_attempts"] : "0") . " / " . $individual_max_submittable) ?></a>
                                            </td>
                                        </tr>
                                    <?php
                                    }
                                }
                                ?>
                                <tr class="hide no-search-targets">
                                    <td colspan="3"><?php echo $translate->_("No targets found matching your search criteria."); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <ul id="targets-inprogress-grid" class="target-grid <?php echo ($PREFERENCES["target_view"] === "grid" ? "" : "hide") ?>">
                        <?php
                        foreach ($targets as $target) {
                            if (in_array("inprogress", $target["progress"])) {
                                $url = ENTRADA_URL . "/assessments/assessment?adistribution_id=" . html_encode($distribution->getID()) . "&schedule_id=" . (isset($schedule) && $schedule ? html_encode($schedule->getID()) : "") . "&target_record_id=" . html_encode($target["target_record_id"]) . (array_key_exists("inprogress_aprogress_id", $target) ? "&aprogress_id=" . html_encode($target["inprogress_aprogress_id"]) : "") . "&dassessment_id=" . $PROCESSED["dassessment_id"]; ?>
                                <li class="media assessment-target-media-list target-inprogress-block target-block">
                                    <div class="assessment-target-media-list-wrapper">
                                        <div class="assessment-target-media-list-container">
                                            <?php echo "<img src=\"".webservice_url("photo", array($ENTRADA_USER->getID(), (isset($uploaded_file_active) && $uploaded_file_active ? "upload" : (!file_exists(STORAGE_USER_PHOTOS."/".$ENTRADA_USER->getID()."-official") && file_exists(STORAGE_USER_PHOTOS."/".$ENTRADA_USER->getID()."-upload") ? "upload" : "official"))))."\" width=\"42\" height=\"42\" alt=\"".html_encode($_SESSION["details"]["firstname"]." ".$_SESSION["details"]["lastname"])."\" class=\"img-circle\" />"; ?>
                                            <h3 class="media-heading"><?php echo html_encode($target["name"]) ?><span><?php echo html_encode($target["number"]); ?></span></h3>
                                            <a href="mailto:<?php echo html_encode($target["email"]) ?>"><?php echo html_encode($target["email"]) ?></a>
                                        </div>
                                        <div class="assessment-target-media-list-parent">
                                            <a href="<?php echo $url; ?>">View Assessment &rtrif;</a>
                                        </div>
                                    </div>
                                </li>
                            <?php
                            }
                        }
                        ?>
                    </ul>
                <?php
                } else {
                    echo "<p id=\"no-inprogress-targets\" class=\"no-targets\">". $translate->_("There are currently no targets in progress.") ."</p>";
                }
                echo "</div>";
                echo "<div id=\"targets-complete-container\" class=\"targets-container ". (isset($PREFERENCES["target_status_view"]) && $PREFERENCES["target_status_view"] === "complete" ? "" : "hide") ."\">";
                echo "<h2>" . $translate->_("Targets Complete") . "</h2>";
                if ($targets_complete) { ?>
                    <div id="targets-complete-table" class="target-table <?php echo ($PREFERENCES["target_view"] === "list" ? "" : "hide"); ?>">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th width="40%"><?php echo $translate->_("Name"); ?></th>
                                    <th width="45%"><?php echo $translate->_("Email"); ?></th>
                                    <th width="15%"><?php echo $translate->_("Number"); ?></th>
                                    <th width="15%"><?php echo $translate->_("Attempts"); ?></th>
                                </tr>
                            </thead>
                                <tbody>
                                <?php
                                foreach ($targets as $target) {
                                    if (in_array("complete", $target["progress"])) {
                                        $url = ENTRADA_URL . "/assessments/assessment?adistribution_id=" . html_encode($distribution->getID()) . "&schedule_id=" . (isset($schedule) && $schedule ? html_encode($schedule->getID()) : "") . "&target_record_id=" . html_encode($target["target_record_id"]) . (array_key_exists("completed_aprogress_id", $target) ? "&aprogress_id=" . html_encode($target["completed_aprogress_id"]) : "") . "&dassessment_id=" . $PROCESSED["dassessment_id"]; ?>
                                        <tr class="target-complete-block target-block">
                                            <td><a href="<?php echo $url ?>"><?php echo html_encode($target["name"]) ?></a></td>
                                            <td><a href="<?php echo $url ?>"><?php echo html_encode($target["email"]) ?></a></td>
                                            <td><a href="<?php echo $url ?>"><?php echo html_encode($target["number"]) ?></a></td>
                                            <td><a href="<?php echo $url ?>"><?php echo html_encode((isset($target["completed_attempts"]) && $target["completed_attempts"] ? $target["completed_attempts"] : "0") . " / " . $individual_max_submittable) ?></a></td>
                                        </tr>
                                    <?php
                                    }
                                }
                                ?>
                                <tr class="hide no-search-targets">
                                    <td colspan="3"><?php echo $translate->_("No targets found matching your search criteria."); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <ul id="targets-complete-grid" class="target-grid <?php echo ($PREFERENCES["target_view"] === "grid" ? "" : "hide") ?>">
                        <?php
                        foreach ($targets as $target) {
                            if (in_array("complete", $target["progress"])) {
                                $url = ENTRADA_URL . "/assessments/assessment?adistribution_id=" . html_encode($distribution->getID()) . "&schedule_id=" . (isset($schedule) && $schedule ? html_encode($schedule->getID()) : "") . "&target_record_id=" . html_encode($target["target_record_id"]) . (array_key_exists("aprogress_id", $target) ? "&aprogress_id=" . html_encode($target["aprogress_id"]) : "") . "&dassessment_id=" . $PROCESSED["dassessment_id"]; ?>
                                <li class="media assessment-target-media-list target-complete-block target-block">
                                    <div class="assessment-target-media-list-wrapper">
                                        <div class="assessment-target-media-list-container">
                                            <?php echo "<img src=\"".webservice_url("photo", array($ENTRADA_USER->getID(), (isset($uploaded_file_active) && $uploaded_file_active ? "upload" : (!file_exists(STORAGE_USER_PHOTOS."/".$ENTRADA_USER->getID()."-official") && file_exists(STORAGE_USER_PHOTOS."/".$ENTRADA_USER->getID()."-upload") ? "upload" : "official"))))."\" width=\"42\" height=\"42\" alt=\"".html_encode($_SESSION["details"]["firstname"]." ".$_SESSION["details"]["lastname"])."\" class=\"img-circle\" />"; ?>
                                            <h3 class="media-heading"><?php echo html_encode($target["name"]) ?><span><?php echo html_encode($target["number"]); ?></span></h3>
                                            <a href="mailto:<?php echo html_encode($target["email"]) ?>"><?php echo html_encode($target["email"]) ?></a>
                                        </div>
                                        <div class="assessment-target-media-list-parent">
                                            <a href="<?php echo $url; ?>">View Completed Assessment &rtrif;</a>
                                        </div>
                                    </div>
                                </li>
                            <?php
                            }
                        }
                        ?>
                    </ul>
                <?php
                } else {
                    echo "<p id=\"no-complete-targets\" class=\"no-targets\">". $translate->_("There are currently no targets completed.") ."</p>";
                }
                echo "</div>";
            } else {
                add_error($translate->_("No Targets found"));
            }
        } else {
            add_error($translate->_("No Distribution found"));
        }
    }
}