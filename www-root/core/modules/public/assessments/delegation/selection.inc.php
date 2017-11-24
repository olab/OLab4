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
            $BREADCRUMB[] = array(
                "url" => ENTRADA_URL . "/$MODULE/$SUBMODULE/",
                "title" => ($distribution ? sprintf($translate->_("%s - Select Assessors"), $distribution->getTitle()) : '')
            );
            $HEAD[] = "<script type=\"text/javascript\">var ENTRADA_URL = '" . ENTRADA_URL . "'</script>";
            $HEAD[] = "<script type=\"text/javascript\" src=\"" . ENTRADA_URL . "/javascript/assessments/delegation.js?release=" . html_encode(APPLICATION_VERSION) . "\"></script>";
            $HEAD[] = "<link href=\"" . ENTRADA_URL . "/css/assessments/delegation.css\" rel=\"stylesheet\" type=\"text/css\" media=\"all\" />";

            $distribution_delegation = new Entrada_Utilities_Assessments_DistributionDelegation(array("adistribution_id" => $distribution->getID(), "addelegation_id" => $delegation->getID()));

            $additional_assessors_form_action = ENTRADA_URL. "/assessments/delegation?section=selection&addelegation_id=$DELEGATION_ID&adistribution_id=$DISTRIBUTION_ID";
            $success_url = ENTRADA_URL."/assessments/delegation?section=confirm&addelegation_id=$DELEGATION_ID&adistribution_id=$DISTRIBUTION_ID";
            $previous_page_url = ENTRADA_URL."/assessments/delegation?addelegation_id=$DELEGATION_ID&adistribution_id=$DISTRIBUTION_ID";
            ?>
            <script type="text/javascript">
                var previous_page_url = "<?php echo $previous_page_url; ?>";
                var success_url = "<?php echo $success_url; ?>";
                var select_assessors_msgs = {};
                var additional_assessors_msgs = {};

                select_assessors_msgs.error_default = "<?php echo $translate->_("Unknown error, please try again later."); ?>";
                select_assessors_msgs.error_select_assessors = "<?php echo $translate->_("Please select one or more available assessors."); ?>";

                additional_assessors_msgs.internal_label = "<?php echo $translate->_("Internal Assessor"); ?>";
                additional_assessors_msgs.external_label = "<?php echo $translate->_("External Assessor"); ?>";
                additional_assessors_msgs.error_adding_additional_assessments = "<?php echo $translate->_("Unable to add additional assessors to distribution."); ?>";

            </script>
            <?php
            $request_method = strtoupper(clean_input($_SERVER["REQUEST_METHOD"], "alpha"));
            $request = ${"_" . $request_method};
            if (isset($request["original_post"]) && !empty($request["original_post"])) {
                foreach ($request["original_post"] as $i => $op) {
                    $request[$i] = $op;
                }
            }

            $prepopulate_checkboxes = array();
            if (isset($request["prepopulate_checkboxes"])) {
                $prepopulate_checkboxes = $request["prepopulate_checkboxes"];
            }

            $form_selected_targets = array();
            $selected_targets = array();

            $date_range_start = isset($request["date_range_start"]) ? $request["date_range_start"] : $delegation->getStartDate();
            $date_range_end = isset($request["date_range_end"]) ? $request["date_range_end"] : $delegation->getEndDate();

            // Gather the posted IDs and fetch the related target information
            foreach ($request as $posted_input_name => $posted_input_value) {
                $input_vals = explode('-', $posted_input_name); // turns into array(0=>"target_assign_value", 1=>type, 2=>scope, 3=>member id)
                if (!empty($input_vals)) {
                    if ($input_vals[0] == "target_assign_value") {
                        $target_id = clean_input($input_vals[3]);
                        $form_selected_targets[$target_id] = array(
                            "target_id" => $target_id,
                            "type" => $input_vals[1],
                            "scope" => $input_vals[2]);
                    }
                }
            }

            if (empty($form_selected_targets)) {
                echo display_error(array($translate->_("No targets provided.")));
            } else {
                // Using the sanitized posted variables, fetch the target entities
                $selected_targets = $distribution_delegation->getDelegationTargetsByIDs($form_selected_targets);

                // Fetch the list of all possible assessors for this assessment
                $all_assessors = $distribution_delegation->getPossibleAssessors();
                $distribution_range_text = $distribution_delegation->getConcatenatedBlockOrDateString($date_range_start, $date_range_end);
                ?>
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

                    <div id="msgs" class="space-above medium"></div>
                    <div id="assessor-assignment-container">
                        <h2><?php echo $translate->_("Selected Targets:"); ?></h2>
                        <div id="selected-targets-container">
                            <table>
                                <tbody>
                                <?php if (!empty($selected_targets)): ?>
                                    <?php foreach ($selected_targets as $selected_target): ?>
                                        <?php if ($selected_target["use_members"]) :?>
                                            <tr>
                                                <td id="<?php echo "target-{$selected_target["type"]}-{$selected_target["id"]}"; ?>" class="space-below">
                                                    <div class="space-below userAvatar target-selection-avatar pull-left">
                                                        <?php if ($selected_target["type"] == "external_hash"): ?>
                                                            <img src="<?php echo ENTRADA_URL . "/images/headshot-male.gif"; ?>" alt="<?php echo $selected_target["member_fullname"] ?>" class="img-polaroid user-image-size" />
                                                        <?php else: ?>
                                                            <img src="<?php echo webservice_url("photo", array($selected_target["member_id"], "official")); ?>" alt="<?php echo $selected_target["member_fullname"] ?>" class="img-polaroid user-image-size" />
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="pull-left">
                                                        <strong><?php echo $selected_target["member_fullname"]; ?></strong>
                                                        <?php if ($selected_target["member_email"]): ?>
                                                            <p><a href="#"><?php echo $selected_target["member_email"]; ?></a></p>
                                                        <?php endif; ?>
                                                        <?php if ($selected_target["type"] == "external_hash"): ?>
                                                            <p><?php echo $translate->_("External Target"); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <tr>
                                                <td class="space-below medium">
                                                    <h3 class="target-non-person-entity-label" id="<?php echo "target-{$selected_target["type"]}-{$selected_target["id"]}"; ?>"><strong><?php echo $selected_target["entity_name"];?></strong></h3>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td>
                                            <p id="no-targets" class="no-targets no-margin"><?php echo $translate->_("No targets selected.") ?></p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <h2><?php echo $translate->_("Available Assessors:");?></h2>

                        <div id="assessor-search-block" class="space-above clearfix">
                            <input class="search-icon" type="text" id="assessor-search-input" placeholder="<?php echo $translate->_("Search Assessors...") ?>"/>
                        </div>

                        <div id="available-assessors-list" class="clearfix">
                            <ul class="clearfix">
                                <?php if (!empty($all_assessors)) :?>
                                    <?php foreach ($all_assessors as $i => $possible_assessor): ?>
                                    <li class="assessor-block">
                                        <label class="checkbox pull-left assessor-selection-card">
                                            <?php
                                            $checked_status = "";
                                            $assessor_string_id = "assessor-{$possible_assessor["assessor_type"]}-{$possible_assessor["assessor_value"]}";
                                            foreach ($prepopulate_checkboxes as $ppc) {
                                                if ($ppc == $assessor_string_id) {
                                                    $checked_status = "checked";
                                                }
                                            }
                                            ?>
                                            <input type="checkbox" name="add_assessors[]"
                                                   id="<?php echo $assessor_string_id?>"
                                                   value="<?php echo $possible_assessor["name"];?>"
                                                   data-assessor-value="<?php echo $possible_assessor["assessor_value"]?>"
                                                   data-assessor-type="<?php echo $possible_assessor["assessor_type"]?>" <?php echo $checked_status?>>

                                            <div id="<?php echo "assessor-container-{$possible_assessor["assessor_type"]}-{$possible_assessor["assessor_value"]}"; ?>">
                                                <?php if ($possible_assessor["assessor_type"] == "internal"): ?>
                                                    <div class="userAvatar target-selection-avatar pull-left">
                                                        <img src="<?php echo webservice_url("photo", array($possible_assessor["proxy_id"], "official")); ?>" alt="<?php echo $possible_assessor["name"] ?>" class="img-polaroid user-image-size" />
                                                    </div>
                                                <?php else: ?>
                                                    <div class="userAvatar target-selection-avatar pull-left">
                                                        <img src="<?php echo ENTRADA_URL . "/images/headshot-male.gif" ?>" alt="<?php echo $possible_assessor["name"] ?>" class="img-polaroid user-image-size" />
                                                    </div>
                                                <?php endif; ?>

                                                <div class="pull-left">
                                                    <strong><?php echo $possible_assessor["name"]; ?></strong>
                                                    <?php if ($possible_assessor["email"]): ?>
                                                        <p><a href="#"><?php echo $possible_assessor["email"]; ?></a></p>
                                                    <?php endif; ?>
                                                    <?php if ($possible_assessor["assessor_type"] == "external"): ?>
                                                        <p><?php echo $translate->_("External Assessor"); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </label>
                                    </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li>
                                        <p id="no-assessors" class="no-targets no-margin"><?php echo $translate->_("No assessors available.") ?></p>
                                    </li>
                                <?php endif; ?>
                            </ul>

                            <div id="add-unlisted-assessor-container">
                                <div id="add-unlisted-assessor" class="btn btn-default">
                                    <i class="icon-plus-sign"></i> <?php echo $translate->_("Add Additional Assessor");?>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($selected_targets) && !empty($all_assessors)): ?>
                            <a id="assessor-select-cancel-btn" class="btn btn-default pull-left" href="#"><?php echo $translate->_("Previous Step"); ?></a>
                            <a id="assessor-select-continue-btn" class="btn btn-success pull-right" href="#"><?php echo $translate->_("Proceed to Confirm Assessments"); ?></a>
                            <form id="delegation-add-assessors-form" name="add_assessors_form" class="hide" method="post" action="<?php echo $success_url?>">
                                <input type="hidden" name="adistribution_id" value="<?php echo $DISTRIBUTION_ID; ?>">
                                <input type="hidden" name="addelegation_id" value="<?php echo $DELEGATION_ID; ?>">
                                <input type="hidden" name="date_range_start" value="<?php echo $date_range_start; ?>">
                                <input type="hidden" name="date_range_end" value="<?php echo $date_range_end; ?>">
                                <input type="hidden" id="form-repost-url" name="form_repost_url" value="<?php echo $additional_assessors_form_action; ?>">
                                <?php foreach ($request as $original_index => $original_post_value) :?>
                                    <?php if (!is_array($original_post_value)) :?>
                                        <input type="hidden" name="original_post[<?php echo $original_index; ?>]" value="<?php echo $original_post_value; ?>">
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <?php foreach ($selected_targets as $target): ?>
                                    <input type="hidden"
                                           name="selected_targets[]"
                                           data-target-type="<?php echo $target["type"]?>"
                                           data-target-id="<?php echo $target["id"]?>"
                                           data-target-scope="<?php echo $target["scope"]?>"
                                           value="<?php echo $target["type"]?>-<?php echo $target["scope"]?>-<?php echo $target["id"]?>">
                                <?php endforeach; ?>
                                <?php foreach ($prepopulate_checkboxes as $ppc) :?>
                                    <input type="hidden" name="preopopulate_checkboxes[]" value="<?php echo $ppc; ?>">
                                <?php endforeach; ?>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-------- Duplicate Assessors Confirmation Modal --------->

                <div id="duplicate-assessor-error-modal" class="modal delegation-modal fade hide">

                    <div class="modal-header text-center">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <h2><?php echo $translate->_("Duplicate Assessments Detected"); ?></h2>
                    </div>

                    <div class="modal-body text-center">
                        <p><?php echo $translate->_("The following identical assessments are already assigned and will be ignored:"); ?></p>
                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <th width="50%"><?php echo $translate->_("Target"); ?></th>
                                <th><?php echo $translate->_("Assessor"); ?></th>
                            </tr>
                            </thead>
                            <tbody id="duplicate-assessments-model-body">
                            </tbody>
                        </table>
                    </div>

                    <div class="modal-footer text-center">
                        <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Cancel"); ?></a>
                        <a href="#" class="btn btn-primary pull-right" data-dismiss="modal" id="assessor-select-continue-forced-btn"><?php echo $translate->_("Continue"); ?></a>
                    </div>
                </div>

                <!-------- Add Additional Assessors Modal --------->
                <?php

                $add_additional_assessors_modal = new Views_Assessments_Modals_AddAdditionalAssessors();
                $add_additional_assessors_modal->render();
            }
        } // END if distribution
    } // END if !ERROR
} // END authorized