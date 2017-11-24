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
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
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
    $distribution = false;

    if (isset($_GET["adistribution_id"]) && $tmp_input = clean_input($_GET["adistribution_id"], array("trim", "int"))) {
        $DISTRIBUTION_ID = $PROCESSED["adistribution_id"] = $tmp_input;
    } else {
        add_error($translate->_("No distribution identifier provided."));
        echo display_error();
    }

    if (isset($_GET["addelegation_id"]) && $tmp_input = clean_input($_GET["addelegation_id"], array("trim", "int"))) {
        $DELEGATION_ID = $PROCESSED["addelegation_id"] = $tmp_input;
    } else {
        add_error($translate->_("No delegation identifier provided."));
        echo display_error();
    }

    if (!$ERROR) {
        $distribution = Models_Assessments_Distribution::fetchRowByID($DISTRIBUTION_ID);
        $delegation = Models_Assessments_Distribution_Delegation::fetchRowByID($DELEGATION_ID);

        if (!$delegation) {
            echo display_error(array($translate->_("No delegation task found.")));
        } else if (!$distribution) {
            echo display_error(array($translate->_("No distribution found.")));
        } else if ($delegation->getAdistributionID() != $distribution->getID()) {
            echo display_error(array($translate->_("Delegation task does not correspond with distribution.")));
        } else {
            $BREADCRUMB[] = array("url" => ENTRADA_URL . "/$MODULE/$SUBMODULE/", "title" => ($distribution ? sprintf($translate->_("%s - Confirm Assessments"), $distribution->getTitle()) : $translate->_("Confirm Assessments")));
            $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = '" . ENTRADA_URL . "'</script>";
            $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/assessments/delegation.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
            $HEAD[] = "<link href=\"" . ENTRADA_URL . "/css/assessments/delegation.css\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";

            $distribution_delegation = new Entrada_Utilities_Assessments_DistributionDelegation(array("adistribution_id" => $distribution->getID(), "addelegation_id" => $delegation->getID()));

            $success_url = ENTRADA_URL."/assessments";
            $success_delegation_url = ENTRADA_URL . "/assessments/delegation?addelegation_id=$DELEGATION_ID&adistribution_id=$DISTRIBUTION_ID";

            if ($ENTRADA_USER->getActiveID() != $delegation->getDelegatorID()) {
                $success_url .= "/faculty?proxy_id=" . $delegation->getDelegatorID();
            }
            $previous_page_url = ENTRADA_URL."/assessments/delegation?section=selection&addelegation_id=$DELEGATION_ID&adistribution_id=$DISTRIBUTION_ID";
            ?>
            <script type="text/javascript">
                var previous_page_url = "<?php echo $previous_page_url; ?>";
                var success_url = "<?php echo $success_url; ?>";
                var success_delegation_url = "<?php echo $success_delegation_url; ?>";
                var confirm_assessments_msgs = {};
                confirm_assessments_msgs.error_default = "<?php echo $translate->_("Unknown error, please try again later."); ?>";
                confirm_assessments_msgs.error_creating_assessments = "<?php echo $translate->_("Error creating assessment tasks. Please try again later."); ?>";
                confirm_assessments_msgs.success_added_assessors_auto_completed = "<?php echo $translate->_("Assessment tasks successfully added. <strong>This delegation task as been automatically marked complete.</strong> You will be automatically redirected in 5 seconds, or you can <a href='$success_url'>click here</a> if you do not wish to wait."); ?> ";
                confirm_assessments_msgs.success_added_assessors_delegation = "<?php echo $translate->_("Assessment tasks successfully added. You will be automatically redirected in 5 seconds, or you can <a href='$success_delegation_url'>click here</a> if you do not wish to wait."); ?> ";
                confirm_assessments_msgs.success_added_assessors_auto_completed_delegation = "<?php echo $translate->_("Assessment tasks successfully added. <strong>This delegation task as been automatically marked complete.</strong> You will be automatically redirected in 5 seconds, or you can <a href='$success_delegation_url'>click here</a> if you do not wish to wait."); ?> ";
            </script>
            <?php
            $request_method = strtoupper(clean_input($_SERVER['REQUEST_METHOD'], "alpha"));
            $request = ${"_" . $request_method};

            $date_range_start = isset($request["date_range_start"]) ? $request["date_range_start"] : $delegation->getStartDate();
            $date_range_end = isset($request["date_range_end"]) ? $request["date_range_end"] : $delegation->getEndDate();

            $form_posted_targets = isset($request["selected_targets"]) ? $request["selected_targets"] : array();
            $posted_targets = array();
            foreach ($form_posted_targets as $i => $posted_target) {
                $exp_target = explode("-", $posted_target);
                if (count($exp_target) != 3) {
                    add_error($translate->_("Error with submitted targets."));
                } else {
                    $target_id = clean_input($exp_target[2], array("int"));
                    $posted_targets[$target_id]["type"] = $exp_target[0];
                    $posted_targets[$target_id]["scope"] = $exp_target[1];
                    $posted_targets[$target_id]["target_id"] = $target_id;
                }
            }

            $form_posted_assessors = isset($request["selected_assessors"]) ? $request["selected_assessors"] : array();
            $posted_assessors = array();
            foreach ($form_posted_assessors as $i => $posted_assessor) {
                $exp_assessor = explode("-", $posted_assessor);
                if (count($exp_assessor) != 2) {
                    add_error($translate->_("Error with submitted assessors."));
                } else {
                    $posted_assessors[$i]["assessor_type"] = $exp_assessor[0];
                    $posted_assessors[$i]["assessor_value"] = clean_input($exp_assessor[1], array("int"));
                }
            }

            if (empty($posted_targets)) {
                add_error($translate->_("No targets provided."));
                echo display_error();
            } else if (empty($posted_assessors)) {
                add_error($translate->_("No assessors selected."));
                echo display_error();
            } else {

                // Create a list of assessments to approve
                $combinations = $distribution_delegation->getTargetAssessorCombinations($posted_targets, $posted_assessors, false, true);
                $distribution_range_text = $distribution_delegation->getConcatenatedBlockOrDateString($date_range_start, $date_range_end);

                $all_targets = $distribution_delegation->getDelegationTargetsAndAssessors($date_range_start, $date_range_end, false);
                $all_selected_target_proxies = array();
                $all_targets_not_selected = array();

                foreach ($combinations as $combination) {
                    $all_selected_target_proxies[] = $combination["target"]["target_id"];
                }

                foreach ($all_targets as $target) {
                    if (!in_array(($target["use_members"]) ? $target["member_id"] : $target["id"], $all_selected_target_proxies)) {
                        $all_targets_not_selected[] = ($target["use_members"]) ? $target["member_fullname"] : $target["entity_name"];
                    }
                }
                // Keep our original posted information about targets, so we can return to the previous page.
                $auto_check_mark_complete = false;
                ?>
                <form id="selection-form-posted" class="hide" method="post" action="<?php echo $previous_page_url; ?>">
                    <?php if (isset($request["original_post"])): // rebuild original posting ?>
                        <?php foreach ($request["original_post"] as $original_index => $original_post_value) :?>
                            <input type="hidden" name="<?php echo $original_index; ?>" value="<?php echo $original_post_value; ?>">
                            <?php
                            if ($original_index == "all_targets_selected") {
                                $auto_check_mark_complete = $original_post_value == "1";
                            }
                            ?>
                        <?php endforeach; ?>
                        <?php foreach ($posted_assessors as $pa): // also save any checkboxes we want to prepopulate on return ?>
                            <input type="hidden" name="prepopulate_checkboxes[]" value="<?php echo "assessor-{$pa["assessor_type"]}-{$pa["assessor_value"]}";?>">
                        <?php endforeach; ?>
                    <?php endif; ?>
                </form>

                <div class="delegation-interface-container">
                    <h1 class="no-margin center"><?php echo $distribution->getTitle(); ?> &mdash; <?php echo $translate->_("Delegation"); ?></h1>
                    <?php if ($distribution_range_text): ?>
                        <h2 id="delegation-assignment-title" class="center no-margin clearfix">
                            <strong><?php echo $distribution_range_text; ?></strong>
                            <span>
                                <?php echo strftime("%Y-%m-%d", $date_range_start) . $translate->_(" to ") . strftime("%Y-%m-%d", $date_range_end);?>
                            </span>
                        </h2>
                    <?php endif; ?>


                    <div id="msgs" class="space-above medium">
                        <?php if ($ERROR) echo display_error(); ?>
                        <div id="pending-target-warning" class="alert alert-warning hide">
                            <button type="button" class="close" data-dismiss="alert">×</button>
                            <span><?php echo sprintf($translate->_("The targets %s may have no assessors assigned."), implode(", ", $all_targets_not_selected)); ?></span>
                        </div>
                    </div>
                    <?php if (!$ERROR): ?>
                    <div id="assessor-assignment-container">
                        <?php if (!empty($combinations)): ?>
                            <h2><?php echo $translate->_("Confirm Assessments:"); ?></h2>
                            <div id="assessment-tasks-to-create-container">
                                <table class="target-table table table-striped">
                                    <thead>
                                        <tr>
                                            <th><?php echo $translate->_("Target"); ?></th>
                                            <th><?php echo $translate->_("Assessor"); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($combinations as $combination):?>
                                        <tr>
                                            <td width="50%">
                                                <?php if ($combination["meta"]["target_is_person"]): ?>
                                                    <div class="userAvatar target-selection-avatar pull-left">

                                                        <?php if ($combination["target"]["type"] == "external_hash"): ?>
                                                            <img src="<?php echo ENTRADA_URL . "/images/headshot-male.gif"; ?>" alt="<?php echo $combination["target"]["member_fullname"] ?>" class="img-polaroid user-image-size" />
                                                        <?php else: ?>
                                                            <img src="<?php echo webservice_url("photo", array($combination["target"]["target_id"], "official")); ?>" alt="<?php echo $combination["target"]["member_fullname"] ?>" class="img-polaroid user-image-size" />
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="pull-left">
                                                    <?php if ($combination["meta"]["target_is_person"]): ?>
                                                        <strong><?php echo html_encode($combination["target"]["member_fullname"]); ?></strong>
                                                        <?php if ($combination["target"]["member_email"]): ?>
                                                            <p><?php echo html_encode($combination["target"]["member_email"]); ?></p>
                                                        <?php endif; ?>
                                                        <?php if ($combination["target"]["type"] == "external_hash"): ?>
                                                            <p><?php echo $translate->_("External Target"); ?></p>
                                                        <?php endif; ?>
                                                    <?php else: // not a person ?>
                                                        <h3 class="target-non-person-entity-label"><?php echo html_encode($combination["target"]["entity_name"]); ?></h3>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <?php if (!empty($combination["assessors"])): // multiple assessors, show all assessors for a target on the same line ?>
                                                <td>
                                                    <table>
                                                        <tbody>
                                                        <?php foreach ($combination["assessors"] as $assessor): ?>
                                                            <tr>
                                                                <td class="delegation-assessor-info">
                                                                    <div class="space-below userAvatar target-selection-avatar pull-left">
                                                                        <?php if ($assessor["assessor_type"] == "internal"): ?>
                                                                            <img src="<?php echo webservice_url("photo", array($assessor["assessor_value"], "official")); ?>" alt="<?php echo $assessor["fullname"] ?>" class="img-polaroid user-image-size" />
                                                                        <?php else: ?>
                                                                            <img src="<?php echo ENTRADA_URL . "/images/headshot-male.gif"; ?>" alt="<?php echo $assessor["fullname"] ?>" class="img-polaroid user-image-size" />
                                                                        <?php endif; ?>
                                                                    </div>

                                                                    <div class="pull-left">
                                                                        <strong><?php echo html_encode($assessor["fullname"]); ?></strong>

                                                                        <?php if ($assessor["email"]): ?>
                                                                            <p><?php echo html_encode($assessor["email"]); ?></p>
                                                                        <?php endif; ?>
                                                                        <?php if ($assessor["assessor_type"] == "external"): ?>
                                                                            <p><?php echo $translate->_("External Assessor"); ?></p>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </td>
                                            <?php else: // One assessor per target, show one line each ?>
                                                <td>
                                                    <div class="userAvatar target-selection-avatar pull-left">
                                                        <?php if ($combination["assessor"]["assessor_type"] == "internal"): ?>
                                                            <img src="<?php echo webservice_url("photo", array($combination["assessor"]["assessor_value"], "official")); ?>" alt="<?php echo $combination["assessor"]["fullname"] ?>" class="img-polaroid user-image-size" />
                                                        <?php else: ?>
                                                            <img src="<?php echo ENTRADA_URL . "/images/headshot-male.gif"; ?>" alt="<?php echo $combination["assessor"]["fullname"] ?>" class="img-polaroid user-image-size" />
                                                        <?php endif; ?>
                                                    </div>

                                                    <div class="pull-left">
                                                        <strong><?php echo html_encode($combination["assessor"]["fullname"]); ?></strong>

                                                        <?php if ($combination["assessor"]["email"]): ?>
                                                            <p><?php echo html_encode($combination["assessor"]["email"]); ?></p>
                                                        <?php endif; ?>
                                                        <?php if ($combination["assessor"]["assessor_type"] == "external"): ?>
                                                            <p><?php echo $translate->_("External Assessor"); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <div class="control-group">
                                    <input type="checkbox" id="auto-mark-complete" name="auto-mark-complete" value="1" <?php echo ($auto_check_mark_complete) ? "checked=\"checked\"" : ""?>/>
                                    <label class="space-left" for="auto-mark-complete"><?php echo $translate->_("<strong>Automatically mark this delegation as complete.</strong>"); ?></label>
                                </div>
                                <a id="confirm-create-tasks-btn" class="btn btn-success pull-right" href="#"><i class="icon-plus-sign icon-white"></i> <?php echo $translate->_("Create Assessment Tasks"); ?></a>
                                <a id="confirm-cancel-previous-btn" class="btn btn-default pull-left" href="#"><?php echo $translate->_("Previous Step");?></a>
                            </div>
                        <?php else: ?>
                            <h3><?php echo $translate->_("There are no assessments to delegate."); ?></h3>
                            <a id="confirm-cancel-previous-btn" class="btn btn-default pull-left" href="#"><?php echo $translate->_("Previous Step");?></a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <form id="delegation-add-assessors-form" name="add_assessments_form" class="hide">
                        <input type="hidden" name="adistribution_id" value="<?php echo $DISTRIBUTION_ID; ?>">
                        <input type="hidden" name="addelegation_id" value="<?php echo $DELEGATION_ID; ?>">
                        <?php foreach ($posted_targets as $target): ?>
                            <input type="hidden"
                                   name="selected_targets[]"
                                   data-target-type="<?php echo $target["type"]?>"
                                   data-target-id="<?php echo $target["target_id"]?>"
                                   data-target-scope="<?php echo $target["scope"]?>"
                                   value="<?php echo $target["type"]?>-<?php echo $target["scope"]?>-<?php echo $target["target_id"]?>">
                        <?php endforeach; ?>
                        <?php foreach ($posted_assessors as $assessor): ?>
                            <input type="hidden"
                                   name="selected_assessors[]"
                                   data-assessor-value="<?php echo $assessor["assessor_value"]?>"
                                   data-assessor-type="<?php echo $assessor["assessor_type"]?>"
                                   value="<?php echo "{$assessor["assessor_type"]}-{$assessor["assessor_value"]}";?>">
                        <?php endforeach; ?>
                    </form>
                </div>
                <?php
            }
        } // END if distribution
    } // END if !ERROR
} // END authorized