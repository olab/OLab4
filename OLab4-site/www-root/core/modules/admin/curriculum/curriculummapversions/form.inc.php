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
 * @author Organisation: University of British Columbia
 * @author Unit: Faculty of Medicine
 * @author Developer: Matt Simpson <simpson@queensu.ca> and friends.
 * @copyright Copyright 2016 University of British Columbia. All Rights Reserved.
 *
 */

if((!defined("PARENT_INCLUDED")) || (!defined("IN_CURRICULUM_MAP_VERSIONS"))) {
    exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("configuration", "update", false)) {
    $ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."\\'', 15000)";

    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {

    $curriculum_map_version = new Models_Curriculum_Map_Versions();

    if (isset($VERSION_ID) && (int) $VERSION_ID) {
        $curriculum_map_version = $curriculum_map_version->fetchRowByID($VERSION_ID, $ORGANISATION_ID);
    }

    switch ($STEP) {
        case 2 :
            if (isset($_POST["title"]) && ($tmp_input = clean_input($_POST["title"], array("trim", "striptags", "max:255")))) {
                $curriculum_map_version->setTitle($tmp_input);
            } else {
                add_error("The Curriculum Map Version Title is required.");
            }

            if (isset($_POST["description"]) && ($tmp_input = clean_input($_POST["description"], array("trim", "striptags")))) {
                $curriculum_map_version->setDescription($tmp_input);
            }

            if (isset($_POST["curriculum_types"]) && is_array($_POST["curriculum_types"]) && !empty($_POST["curriculum_types"])) {
                $PROCESSED["curriculum_types"] = $_POST["curriculum_types"];
            } else {
                add_error("Curriculum types are required");
            }

            if (isset($_POST["curriculum_periods"]) && is_array($_POST["curriculum_periods"]) && !empty($_POST["curriculum_periods"])) {
                $PROCESSED["curriculum_periods"] = $_POST["curriculum_periods"];
            } else {
                add_error("Curriculum periods are required");
            }

            if (isset($_POST["status"]) && in_array($_POST["status"], array("draft", "published"))) {
                $curriculum_map_version->setStatus($_POST["status"]);
                if ($curriculum_map_version->getStatus() == "published" && is_array($PROCESSED["curriculum_periods"])) {
                    $published_version = $curriculum_map_version->getPublishedVersionByPeriods($PROCESSED["curriculum_periods"]);
                    if ($published_version && ($published_version->getID() != $curriculum_map_version->getID())) {
                        add_error("A published version for these curriculum period(s) already exists. Unpublish that version first.");
                    }
                }
            } else {
                add_error("Status is required");
            }

            if (isset($_POST["import_curriculum_map_version"]) && ($_POST["import_curriculum_map_version"] == 1) && isset($_POST["import_version_id"]) && ((int) $_POST["import_version_id"] || $_POST["import_version_id"] === "null")) {
                $PROCESSED["import_curriculum_map_version"] = true;
                $PROCESSED["import_version_id"] = $_POST["import_version_id"];
            } else {
                $PROCESSED["import_curriculum_map_version"] = false;
            }

            if (!has_error()) {
                if (defined("ADD_MAP_VERSION") && ADD_MAP_VERSION) {
                    $curriculum_map_version->setCreatedBy($ENTRADA_USER->getID());
                    $curriculum_map_version->setCreatedDate(time());

                    $operation = "add";

                    $curriculum_map_version_result = $curriculum_map_version->insert();

                    if ($curriculum_map_version_result && $curriculum_map_version_result->getVersionID()) {
                        $curriculum_map_version->insertOrganisation($ORGANISATION_ID);
                        $period_error = !$curriculum_map_version->insertPeriods($PROCESSED["curriculum_periods"]);

                        /*
                         * If the user is trying to import a previous Curriculum Map Version (entrada.linked_objectives)
                         * proceed with that.
                         */
                        if ($PROCESSED["import_curriculum_map_version"]) {
                            if ($PROCESSED["import_version_id"] === "null") {
                                $curriculum_map_version->copyUnversionedLinkedObjectives();
                            } else {
                                $import_curriculum_map_version = new Models_Curriculum_Map_Versions();
                                $old_version = $import_curriculum_map_version->fetchRowByID($PROCESSED["import_version_id"], $ORGANISATION_ID);
                                if ($old_version) {
                                    $old_version_id = $old_version->getVersionID();

                                    $curriculum_map_version->copyLinkedObjectives($old_version_id);
                                }
                            }
                        }
                    }
                } else {
                    $curriculum_map_version->setUpdatedBy($ENTRADA_USER->getID());
                    $curriculum_map_version->setUpdatedDate(time());

                    $operation = "update";

                    $curriculum_map_version_result = $curriculum_map_version->update();
                    if ($curriculum_map_version_result) {
                        $period_error = !$curriculum_map_version->updatePeriods($PROCESSED["curriculum_periods"]);
                    }
                }
                if (!$curriculum_map_version_result || $period_error) {
                    $ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/curriculum/curriculummapversions?org=" . $ORGANISATION_ID . "\\'', 5000)";

                    add_error("An error occurred while attempting to ".$operation." <strong>". $curriculum_map_version->getTitle()."</strong>. A system administrator has been informed, please try again later.<br /><br />You will now be redirected to the Curriculum Map Versions page, please <a href=\"".ENTRADA_URL."/admin/curriculum/curriculummapversions?org=" . $ORGANISATION_ID . "\">click here</a> if you do not wish to wait.");
                } else {
                    add_success("Successful ".$operation." of <strong>". $curriculum_map_version->getTitle()."</strong>. You will now be redirected to the Curriculum Map Version page, please <a href=\"".ENTRADA_URL."/admin/curriculum/curriculummapversions?org=" . $ORGANISATION_ID . "\">click here</a> if you do not wish to wait.");
                }
            } else {
                $STEP = 1;
            }
            break;
        case 1:
            if ($curriculum_map_version && $curriculum_map_version->getID()) {
                $PROCESSED["curriculum_periods"] = $curriculum_map_version->fetchPeriodIDs();
            }
            break;
    }

    switch ($STEP) {
        case 2 :
            if ($ERROR) {
                echo display_error();
            }
            if ($SUCCESS) {
                echo display_success();
                $ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/curriculum/curriculummapversions?org=" . $ORGANISATION_ID . "\\'', 5000)";
            }
            if ($NOTICE) {
                echo display_notice();
            }
            break;
        case 1 :
        default :
            if ($ERROR) {
                echo display_error();
            }
            if ($SUCCESS) {
                echo display_success();
            }
            if ($NOTICE) {
                echo display_notice();
            }
            ?>
            <form id="curriculum_map_version_form" class="form-horizontal" action="<?php echo ENTRADA_URL . "/admin/curriculum/curriculummapversions?org=" . $ORGANISATION_ID; ?>&amp;section=<?php echo defined("EDIT_MAP_VERSION") ? "edit&amp;id=" . $VERSION_ID : "add"; ?>&amp;step=2" method="post">
                <h2 title="Curriculum Map Version Details Section"><?php echo $translate->_("Curriculum Map Version Details"); ?></h2>
                <div id="curriculum-map-version-details-section">
                    <div class="control-group">
                        <label class="control-label form-required" for="title"><?php echo $translate->_("Map Version Title"); ?></label>
                        <div class="controls">
                            <input type="text" id="title" name="title" class="span8" value="<?php echo (isset($curriculum_map_version) && $curriculum_map_version->getTitle() ? $curriculum_map_version->getTitle() : ""); ?>" required="required" maxlength="255" />
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label" for="description"><?php echo $translate->_("Description"); ?></label>
                        <div class="controls">
                            <textarea id="description" name="description" class="span8 expandable"><?php echo (isset($curriculum_map_version) && $curriculum_map_version && $curriculum_map_version->getDescription() ? html_encode($curriculum_map_version->getDescription()) : ""); ?></textarea>
                        </div>
                    </div>

                    <div class="control-group">
                        <label class="control-label form-required" for="title"><?php echo $translate->_("Map Version Status"); ?></label>
                        <div class="controls">
                            <label class="radio"><input type="radio" id="status" name="status" class="item-control" value="draft"<?php echo (($curriculum_map_version->getStatus() == "draft" || $curriculum_map_version->getStatus() === null) ? " checked=\"checked\"" : ""); ?> /><?php echo $translate->_("Draft"); ?></label>
                            <label class="radio"><input type="radio" id="status" name="status" class="item-control" value="published"<?php echo (($curriculum_map_version->getStatus() == "published") ? " checked=\"checked\"" : ""); ?> /><?php echo $translate->_("Published"); ?></label>
                        </div>
                    </div>

                    <?php
                    if (defined("ADD_MAP_VERSION") && ADD_MAP_VERSION) {
                        $version = new Models_Curriculum_Map_Versions();
                        $results = $version->fetchAllRecords($ORGANISATION_ID);
                        ?>
                        <div class="control-group">
                            <label class="control-label">
                                <?php echo $translate->_("Import Options"); ?>
                            </label>
                            <div class="controls">
                                <label class="checkbox">
                                    <input type="checkbox" id="import_curriculum_map_version"
                                           name="import_curriculum_map_version"
                                           value="1" <?php echo ((isset($PROCESSED["import_curriculum_map_version"]) && $PROCESSED["import_curriculum_map_version"])) ? " checked=\"checked\"" : "" ?> />
                                    <?php echo $translate->_("Import the curriculum map from a previous version."); ?>
                                </label>
                                <div class="space-above hide" style="margin-left: 20px" id="import_version_id_options">
                                    <select name="import_version_id" id="import_version_id" class="span8">
                                        <option value="0">
                                            -- <?php echo $translate->_("Select Curriculum Map Version"); ?> --
                                        </option>
                                        <option value="null"<?php echo ((isset($PROCESSED["import_version_id"]) && ($PROCESSED["import_version_id"] === "null")) ? " selected=\"selected\"" : ""); ?>>
                                            <?php echo $translate->_("Unversioned Curriculum Map"); ?>
                                        </option>
                                        <?php
                                        if ($results) {
                                            foreach ($results as $result) {
                                                echo "<option value=\"" . $result["version_id"] . "\"" . ((isset($PROCESSED["import_version_id"]) && ($PROCESSED["import_version_id"] == $result["version_id"])) ? " selected=\"selected\"" : "") . ">" . html_encode($result["title"]) . "</option>\n";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <script>
                            jQuery(function ($) {
                                $('#import_curriculum_map_version:checked').each(function (el) {
                                    $('#import_version_id_options').show();
                                });

                                $('#import_curriculum_map_version').on('click', function () {
                                    $('#import_version_id_options').toggle('fast');
                                });
                            });
                        </script>
                        <?php
                    }
                    ?>

                </div>

                <h2 title="Curriculum Periods Section"><?php echo $translate->_("Associated Curriculum Periods"); ?></h2>
                <div id="curriculum-periods-section">
                    <?php require("periods.inc.php"); ?>
                </div>

                <div class="row-fluid">
                    <a href="<?php echo ENTRADA_URL."/admin/curriculum/curriculummapversions?org=" . $ORGANISATION_ID; ?>" class="btn" type="button"><?php echo $translate->_("global_button_cancel"); ?></a>
                    <input type="submit" class="btn btn-primary pull-right" value="<?php echo $translate->_("global_button_save"); ?>" />
                </div>
            </form>
            <?php
            break;
    }
}
