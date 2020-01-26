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
 * @author Unit: Health Sciences Education Technology Unit
 * @author Developer: Matt Simpson <simpson@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

if (!defined("PARENT_INCLUDED") || !defined("IN_CONFIGURATION")) {
	exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
	header("Location: ".ENTRADA_URL);
	exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "update", false)) {
	add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

	echo display_error();

	application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] do not have access to this module [".$MODULE."]");
} else {
    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/settings/manage/characteristics?".replace_query(array("section" => "edit"))."&amp;org=".$ORGANISATION_ID, "title" => "Edit " . $translate->_("Characteristic"));
    ?>

    <h1>Edit <?php echo $translate->_("Characteristic"); ?></h1>

    <?php
    $characteristic = false;

	if (isset($_GET["id"]) && ($id = clean_input($_GET["id"], "int"))) {
        $characteristic = Models_Assessment_Characteristic::get($id);
    }

    // if ($characteristic && $ENTRADA_ACL->amIAllowed(new AssessmentCharacteristicResource($id), "update", true))
    if ($characteristic) {
        $mapped_medbiq_assessment_method = $characteristic->getMappedMedbiqAssessmentMethod();
        if ($mapped_medbiq_assessment_method) {
            $PROCESSED["medbiq_assessment_method_id"] = $mapped_medbiq_assessment_method->getID();
        } else {
            $PROCESSED["medbiq_assessment_method_id"] = 0;
        }

        // Error Checking
        switch ($STEP) {
            case 2 :
                $PROCESSED["id"] = $characteristic->getID();
                $PROCESSED["organisation_id"] = $characteristic->getOrganisationId();

                /**
                 * Required field "type" / Characteristic Category
                 */
                if (isset($_POST["type"]) && ($type = clean_input($_POST["type"], "alpha")) && (in_array($type, Models_Assessment_Characteristic::getTypeOptions()))) {
                    $PROCESSED["type"] = $type;
                } else {
                    add_error("The <strong>" . $translate->_("Characteristic") . " Category</strong> is a required field.");
                }

                /**
                 * Required field "title" / Title
                 */
                if (isset($_POST["title"]) && ($title = clean_input($_POST["title"], array("notags", "trim")))) {
                    $PROCESSED["title"] = $title;
                } else {
                    add_error("The <strong>Title</strong> is a required field.");
                }

                /**
                 * Non-required field "description" / Description
                 */
                if (isset($_POST["description"]) && ($description = clean_input($_POST["description"], array("notags", "trim")))) {
                    $PROCESSED["description"] = $description;
                } else {
                    $PROCESSED["description"] = "";
                }

                if (isset($_POST["medbiq_assessment_method_id"]) && $tmp_input = clean_input($_POST["medbiq_assessment_method_id"], "int")) {
                    $PROCESSED["medbiq_assessment_method_id"] = $tmp_input;
                } else {
                    $PROCESSED["medbiq_assessment_method_id"] = 0;
                }

                $PROCESSED["active"] = 1;

                if (!$ERROR) {
                    $characteristic = new Models_Assessment_Characteristic($PROCESSED);
                    if ($characteristic->update()) {

                        $mapped_method = Models_Assessment_MapAssessmentsMeta::fetchRowByAssessmentMethodID($characteristic->getID());
                        if ($mapped_method) {
                            $mapped_method->delete();
                        }

                        if ($PROCESSED["medbiq_assessment_method_id"]) {
                            $mapped_method = new Models_Assessment_MapAssessmentsMeta(array(
                                "fk_assessment_method_id" => $PROCESSED["medbiq_assessment_method_id"],
                                "fk_assessments_meta_id" => $id,
                                "updated_date" => time(),
                                "updated_by" => $ENTRADA_USER->getID()
                            ));

                            if (!$mapped_method->insert()) {
                                add_error("An error occurred while attempting to save the selected MedBiquitous Assessment Method.");

                                application_log("error", "An error occurred while attempting to insert the MedBiquitous Assessment Method " . $mapped_method->getID() . ". DB said: " . $db->ErrorMsg());
                            }
                        }
                    } else {
                        add_error("There was a problem editing this assessment characteristic in the system. The system administrator was informed of this error; please try again later.");

                        application_log("error", "There was an error updating an assessment characteristic. Database said: ".$db->ErrorMsg());
                    }

                    if (!$ERROR) {
                        $url = ENTRADA_URL . "/admin/settings/manage/characteristics?org=".$ORGANISATION_ID;

                        add_success("You have successfully updated <strong>".html_encode($PROCESSED["title"])."</strong>.<br /><br />You will now be redirected to the " . $translate->_("Assessment Types") . " index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"".$url."\" style=\"font-weight: bold\">click here</a> to continue.");
                        $ONLOAD[] = "setTimeout('window.location=\\'".$url."\\'', 5000)";

                        application_log("success", "Updated Assessment Characteristic ID [".$id."].");
                    }
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
                if ($SUCCESS) {
                    echo display_success();
                }

                if ($NOTICE) {
                    echo display_notice();
                }
            break;
            case 1 :
            default:
                if ($ERROR) {
                    echo display_error();
                }
                ?>
                <form action="<?php echo ENTRADA_RELATIVE."/admin/settings/manage/characteristics?".replace_query(array("action" => "edit", "id" => $characteristic->getID(), "step" => 2, "org" => $ORGANISATION_ID)); ?>" method="post" class="form-horizontal">
                    <div class="control-group">
                        <label for="eventtype_title" class="form-required control-label"><?php echo $translate->_("Characteristic"); ?> Category</label>
                        <div class="controls">
                            <select id="type" name="type" class="input-large">
                                <?php
                                foreach ($characteristic->getTypeOptions() as $type) {
                                    echo "<option value=\"$type\"".($characteristic->getType() == $type ? " selected=\"selected\"" : "").">".ucwords(strtolower($type))."</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="control-group">
                        <label for="eventtype_title" class="form-required control-label"><?php echo $translate->_("Characteristic"); ?> Title</label>
                        <div class="controls">
                            <input type="text" class="span10" id="title" name="title" value="<?php echo html_encode($characteristic->getTitle()); ?>" maxlength="60" />
                        </div>
                    </div>
                    <div class="control-group">
                        <label for="eventtype_description" class="form-nrequired control-label"><?php echo $translate->_("Characteristic"); ?> Description</label>
                        <div class="controls">
                            <textarea id="description" name="description" class="span10 expandable"><?php echo $characteristic->getDescription(); ?></textarea>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="form-nrequired control-label">MedBiquitous Assessment Method</label>
                        <div class="controls">
                            <div class="radio">
                                <label for="assessment_method_0">
                                    <input type="radio" name="medbiq_assessment_method_id" id="assessment_method_0" value="0"<?php echo (!isset($PROCESSED["medbiq_assessment_method_id"]) || !$characteristic->getMappedMedbiqAssessmentMethod() ? " checked=\"checked\"" : "") ?>>
                                    Not Applicable
                                </label>
                            </div>
                            <hr />
                            <?php
                            $medbiq_assessment_methods = Models_MedbiqAssessmentMethod::fetchAllMedbiqAssessmentMethods();
                            if ($medbiq_assessment_methods) {
                                foreach ($medbiq_assessment_methods as $medbiq_method) {
                                    ?>
                                    <div class="radio">
                                        <label for="assessment_method_<?php echo $medbiq_method->getID(); ?>">
                                            <input type="radio" name="medbiq_assessment_method_id" id="assessment_method_<?php echo $medbiq_method->getID(); ?>" value="<?php echo $medbiq_method->getID(); ?>"<?php echo (isset($PROCESSED["medbiq_assessment_method_id"]) && $medbiq_method->getID() == $PROCESSED["medbiq_assessment_method_id"] ? " checked=\"checked\"" : "") ?>>
                                            <?php echo $medbiq_method->getAssessmentMethod(); ?>
                                        </label>
                                    </div>
                                    <?php
                                }
                            }
                            ?>
                        </div>
                    </div>
                    <div class="control-group">
                        <input type="button" class="btn" value="Cancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/admin/settings/manage/characteristics?org=<?php echo $ORGANISATION_ID;?>'" />
                        <input type="submit" class="btn btn-primary pull-right" value="<?php echo $translate->_("global_button_save"); ?>" />
                    </div>
                </form>
            <?php
            break;
        }
    }
}
