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
    $HEAD[] = "<link href=\"".ENTRADA_URL."/css/assessments/assessment-public-index.css?release=" . html_encode(APPLICATION_VERSION)."\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";
    $HEAD[]	= "<script type=\"text/javascript\" src=\"".ENTRADA_URL."/javascript/assessments/assessment-targets.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";


    if (isset($_GET["dassessment_id"]) && $tmp_input = clean_input($_GET["dassessment_id"], array("trim", "int"))) {
        $PROCESSED["dassessment_id"] = $tmp_input;
    } else {
        add_error($translate->_("No Assessment identifier provided."));
    }

    if (!$ERROR) {
        $HEAD[]	= "<script type=\"text/javascript\">var ENTRADA_URL =\"" . ENTRADA_URL . "\"</script>";
        $assessment_api = new Entrada_Assessments_Assessment(
            array(
                "dassessment_id" => $PROCESSED["dassessment_id"],
                "actor_proxy_id" => $ENTRADA_USER->getActiveId(),
                "actor_organisation_id" => $ENTRADA_USER->getActiveOrganisation()
            )
        );

        $targets = array();
        $targets_pending = $targets_inprogress = $targets_complete = 0;

        $assessor_model = new Models_Assessments_Assessor();
        $assessment_record = $assessor_model->fetchRowByID($PROCESSED["dassessment_id"]);

        if ($assessment_record) {
            $targets = $assessment_api->getAssessmentTargetList();
            if ($targets) {
                foreach ($targets as $target) {
                    $targets_pending += $target["counts"]["pending"];
                    $targets_inprogress += $target["counts"]["inprogress"];
                    $targets_complete += $target["counts"]["complete"];
                }
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
                                <th width="15%"><?php echo $translate->_("Pending"); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ($targets as $target) {
                                if (in_array("pending", $target["progress"])) {
                                    $url = $assessment_api->getAssessmentURL($target["target_record_id"], $target["target_type"], false, $PROCESSED["dassessment_id"]);
                                    $user_data = Models_User::fetchRowByID($target["target_record_id"]);
                                    ?>
                                    <tr class="target-pending-block target-block">
                                        <td>
                                            <a href="<?php echo $url ?>"><?php echo html_encode($target["name"]); ?></a>
                                        </td>
                                        <td>
                                            <a href="<?php echo $url ?>"><?php echo html_encode($target["target_type"] == "proxy_id" && $user_data && !is_null($user_data->getEmail()) ? $user_data->getEmail() : "N/A") ?></a>
                                        </td>
                                        <td>
                                            <a href="<?php echo $url ?>"><?php echo html_encode($target["target_type"] == "proxy_id" && $user_data && !is_null($user_data->getNumber()) ? $user_data->getNumber() : "N/A") ?></a>
                                        </td>
                                        <td>
                                            <a href="<?php echo $url ?>"><?php echo html_encode($target["counts"]["pending"] . " / " . count($target["progress"])); ?></a>
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
                <ul id="targets-pending-grid" class="target-grid <?php echo ($PREFERENCES["target_view"] === "grid" ? "" : "hide") ?>">
                <?php
                foreach ($targets as $target) {
                    if (in_array("pending", $target["progress"])) {
                        $url = $assessment_api->getAssessmentURL($target["target_record_id"], $target["target_type"], false, $PROCESSED["dassessment_id"]);
                        $user_data = Models_User::fetchRowByID($target["target_record_id"]);
                        ?>
                        <li class="media assessment-target-media-list target-pending-block target-block">
                            <div class="assessment-target-media-list-wrapper">
                                <div class="assessment-target-media-list-container">
                                    <?php echo "<img src=\"" . webservice_url("photo", array($target["target_record_id"], (isset($uploaded_file_active) && $uploaded_file_active ? "upload" : (!file_exists(STORAGE_USER_PHOTOS . "/" . $target["target_record_id"] . "-official") && file_exists(STORAGE_USER_PHOTOS . "/" . $target["target_record_id"] . "-upload") ? "upload" : "official")))) . "\" width=\"42\" height=\"42\" alt=\"" . html_encode($_SESSION["details"]["firstname"] . " " . $_SESSION["details"]["lastname"]) . "\" class=\"img-circle\" />"; ?>

                                    <h3 class="media-heading"><?php echo html_encode($target["name"]) ?>
                                        <?php if ($target["target_type"] == "proxy_id" && $user_data && !is_null($user_data->getNumber())) : ?>
                                            <span><?php echo html_encode($user_data->getNumber()); ?></span>
                                        <?php endif; ?>
                                    </h3>

                                    <?php if ($target["target_type"] == "proxy_id" && $user_data && !is_null($user_data->getEmail())) : ?>
                                        <a href="mailto:<?php echo html_encode($user_data->getEmail()) ?>"><?php echo html_encode($user_data->getEmail()) ?></a>
                                    <?php endif; ?>
                                </div>
                                <div class="assessment-target-media-list-parent">
                                    <a href="<?php echo $url; ?>"><?php echo $translate->_("View Assessment") ?> &rtrif;</a>
                                </div>
                            </div>
                        </li>
                        <?php
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
                            <th width="15%"><?php echo $translate->_("Inprogress"); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        foreach ($targets as $target) {
                                if (in_array("inprogress", $target["progress"])) {
                                    $url = $assessment_api->getAssessmentURL($target["target_record_id"], $target["target_type"], false, $PROCESSED["dassessment_id"], $target["inprogress_aprogress_id"]);
                                    $user_data = Models_User::fetchRowByID($target["target_record_id"]);
                                    ?>
                                    <tr class="target-inprogress-block target-block">
                                        <td>
                                            <a href="<?php echo $url ?>"><?php echo html_encode($target["name"]) ?></a>
                                        </td>
                                        <td>
                                            <a href="<?php echo $url ?>"><?php echo html_encode($target["target_type"] == "proxy_id" && $user_data && !is_null($user_data->getEmail()) ? $user_data->getEmail() : "N/A") ?></a>
                                        </td>
                                        <td>
                                            <a href="<?php echo $url ?>"><?php echo html_encode($target["target_type"] == "proxy_id" && $user_data && !is_null($user_data->getNumber()) ? $user_data->getNumber() : "N/A") ?></a>
                                        </td>
                                        <td>
                                            <a href="<?php echo $url ?>"><?php echo html_encode($target["counts"]["inprogress"] . " / " . count($target["progress"])); ?></a>
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
                            $url = $assessment_api->getAssessmentURL($target["target_record_id"], $target["target_type"], false, $PROCESSED["dassessment_id"], $target["inprogress_aprogress_id"]);
                            $user_data = Models_User::fetchRowByID($target["target_record_id"]);
                            ?>

                            <li class="media assessment-target-media-list target-inprogress-block target-block">
                                <div class="assessment-target-media-list-wrapper">
                                    <div class="assessment-target-media-list-container">
                                        <?php echo "<img src=\"".webservice_url("photo", array($target["target_record_id"], (isset($uploaded_file_active) && $uploaded_file_active ? "upload" : (!file_exists(STORAGE_USER_PHOTOS."/".$target["target_record_id"]."-official") && file_exists(STORAGE_USER_PHOTOS."/".$target["target_record_id"]."-upload") ? "upload" : "official"))))."\" width=\"42\" height=\"42\" alt=\"".html_encode($_SESSION["details"]["firstname"]." ".$_SESSION["details"]["lastname"])."\" class=\"img-circle\" />"; ?>

                                        <h3 class="media-heading"><?php echo html_encode($target["name"]) ?>
                                            <?php if ($target["target_type"] == "proxy_id" && $user_data && !is_null($user_data->getNumber())) : ?>
                                                <span><?php echo html_encode($user_data->getNumber()); ?></span>
                                            <?php endif; ?>
                                        </h3>

                                        <?php if ($target["target_type"] == "proxy_id" && $user_data && !is_null($user_data->getEmail())) : ?>
                                            <a href="mailto:<?php echo html_encode($user_data->getEmail()) ?>"><?php echo html_encode($user_data->getEmail()) ?></a>
                                        <?php endif; ?>
                                    </div>
                                    <div class="assessment-target-media-list-parent">
                                        <a href="<?php echo $url; ?>"><?php echo $translate->_("View Assessment") ?> &rtrif;</a>
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
                                <th width="15%"><?php echo $translate->_("Complete"); ?></th>
                            </tr>
                        </thead>
                            <tbody>
                            <?php
                            foreach ($targets as $target) {
                                if (in_array("complete", $target["progress"])) {
                                    $url = $assessment_api->getAssessmentURL($target["target_record_id"], $target["target_type"], false, $PROCESSED["dassessment_id"], $target["complete_aprogress_id"]);
                                    $user_data = Models_User::fetchRowByID($target["target_record_id"]);
                                    ?>
                                    <tr class="target-complete-block target-block">
                                        <td>
                                            <a href="<?php echo $url ?>"><?php echo html_encode($target["name"]) ?></a>
                                        </td>
                                        <td>
                                            <a href="<?php echo $url ?>"><?php echo html_encode($target["target_type"] == "proxy_id" && $user_data && !is_null($user_data->getEmail()) ? $user_data->getEmail() : "N/A") ?></a>
                                        </td>
                                        <td>
                                            <a href="<?php echo $url ?>"><?php echo html_encode($target["target_type"] == "proxy_id" && $user_data && !is_null($user_data->getNumber()) ? $user_data->getNumber() : "N/A") ?></a>
                                        </td>
                                        <td>
                                            <a href="<?php echo $url ?>"><?php echo html_encode($target["counts"]["complete"] . " / " . count($target["progress"])); ?></a>
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
                <ul id="targets-complete-grid" class="target-grid <?php echo ($PREFERENCES["target_view"] === "grid" ? "" : "hide") ?>">
                    <?php
                    foreach ($targets as $target) {
                        if (in_array("complete", $target["progress"])) {
                            $url = $assessment_api->getAssessmentURL($target["target_record_id"], $target["target_type"], false, $PROCESSED["dassessment_id"], $target["complete_aprogress_id"]);
                            $user_data = Models_User::fetchRowByID($target["target_record_id"]);
                            ?>
                            <li class="media assessment-target-media-list target-complete-block target-block">
                                <div class="assessment-target-media-list-wrapper">
                                    <div class="assessment-target-media-list-container">
                                        <?php echo "<img src=\"".webservice_url("photo", array($target["target_record_id"], (isset($uploaded_file_active) && $uploaded_file_active ? "upload" : (!file_exists(STORAGE_USER_PHOTOS."/".$target["target_record_id"]."-official") && file_exists(STORAGE_USER_PHOTOS."/".$target["target_record_id"]."-upload") ? "upload" : "official"))))."\" width=\"42\" height=\"42\" alt=\"".html_encode($_SESSION["details"]["firstname"]." ".$_SESSION["details"]["lastname"])."\" class=\"img-circle\" />"; ?>

                                        <h3 class="media-heading"><?php echo html_encode($target["name"]) ?>
                                            <?php if ($target["target_type"] == "proxy_id" && $user_data && !is_null($user_data->getNumber())) : ?>
                                                <span><?php echo html_encode($user_data->getNumber()); ?></span>
                                            <?php endif; ?>
                                        </h3>

                                        <?php if ($target["target_type"] == "proxy_id" && $user_data && !is_null($user_data->getEmail())) : ?>
                                            <a href="mailto:<?php echo html_encode($user_data->getEmail()) ?>"><?php echo html_encode($user_data->getEmail()) ?></a>
                                        <?php endif; ?>
                                    </div>
                                    <div class="assessment-target-media-list-parent">
                                        <a href="<?php echo $url; ?>"><?php echo $translate->_("View Completed Assessment") ?> &rtrif;</a>
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
    }
}