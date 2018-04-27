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
 * This file is used to add objectives in the entrada.global_lu_objectives table.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Thaisa Almeida <trda@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
*/

if (!defined("PARENT_INCLUDED") || !defined("IN_OBJECTIVES")) {
    exit;
} elseif (!isset($_SESSION["isAuthorized"]) || !(bool) $_SESSION["isAuthorized"]) {
    header("Location: ".ENTRADA_URL);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("curriculum", "create", false)) {
    $ONLOAD[] = "setTimeout('window.location=\\'".ENTRADA_URL."/admin/curriculum/".$MODULE."\\'', 15000)";

    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]."] and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    $BREADCRUMB[] = array("url" => ENTRADA_URL."/admin/curriculum/tags?".replace_query(array("section" => "add")), "title" => "Add Curriculum Tag");

    // Error Checking
    switch ($STEP) {
        case 2 :
            /**
             * Non-required field "objective_code" / Tag Set Code
             */
            if (isset($_POST["objective_code"]) && ($objective_code = clean_input($_POST["objective_code"], array("notags", "trim")))) {
                $PROCESSED["objective_code"] = $objective_code;
            } else {
                $PROCESSED["objective_code"] = "";
            }

            /**
             * Required field "objective_name" / Tag Set Name
             */
            if (isset($_POST["objective_name"]) && ($objective_name = clean_input($_POST["objective_name"], array("notags", "trim")))) {
                $PROCESSED["objective_name"] = $objective_name;
            } else {
                add_error($translate->_("The <strong>Tag Set Name</strong> is a required field."));
            }

            /**
             * Required field "shortname" / Tag Set Shortname
             */
            if (isset($_POST["objective_shortname"]) && $objective_shortname = clean_input($_POST["objective_shortname"], array("notags", "trim"))) {
                $PROCESSED["objective_shortname"] = $objective_shortname;
            } else {
                add_error($translate->_("The <strong>Tag Set Shortname</strong> is a required field."));
            }

            /**
             * Non-required field "objective_description" / Tag Set Description
             */
            if (isset($_POST["objective_description"]) && ($objective_description = clean_input($_POST["objective_description"], array("notags", "trim")))) {
                $PROCESSED["objective_description"] = $objective_description;
            } else {
                $PROCESSED["objective_description"] = "";
            }

            /**
             * Non-required field "standard" / Tag Set Standard
             */
            if (isset($_POST["standard"]) && ($tmp_input = clean_input($_POST["standard"], array("trim", "int")))) {
                $PROCESSED["standard"] = $tmp_input;
            } else {
                $PROCESSED["standard"] = 0;
            }

            /**
             * Non-required field "objective_audience"
             */
            if (isset($_POST["objective_audience"]) && $tmp_input = clean_input($_POST["objective_audience"], array("notags", "trim")) && ($tmp_input == "all" || $tmp_input == "none" || "selected")) {
                $PROCESSED["objective_audience"] = clean_input($_POST["objective_audience"], array("notags", "trim"));
            } else {
                $PROCESSED["objective_audience"] = "all";
            }

            /**
             * Non-required field "course_ids"
             */
            if (isset($_POST["course"]) && isset($PROCESSED["objective_audience"]) == "selected") {
                foreach ($_POST["course"] as $course) {
                    if ($tmp_input = clean_input($course, array("trim", "int"))) {
                        $PROCESSED["course_ids"][] = $tmp_input;
                    }
                }
                if (empty($PROCESSED["course_ids"])) {
                    $PROCESSED["objective_audience"] = "none";
                }
            }

            /**
             * Required field "languages" / Languages
             */
            if (isset($_POST["languages"])) {
                $PROCESSED["languages"] = json_encode($_POST["languages"], JSON_FORCE_OBJECT);
            } else {
                add_error($translate->_("The <strong>Languages</strong> is a required field."));
            }

            /**
             * Required field "requirements" / Detail Requirements
             */
            if (isset($_POST["requirements"])) {
                $requirements = [];
                $PROCESSED["requirements_fields"] = $_POST["requirements"];
                $required = false;

                foreach ($_POST["requirements"] as $requirement) {
                    if (isset($_POST["required"]) && in_array($requirement, $_POST["required"])) {
                        $requirements[$requirement] = ["required" => true];
                        $required = true;
                    } else {
                        $requirements[$requirement] = ["required" => false];
                    }
                }

                if ($required && $_POST["required"]) {
                    $PROCESSED["required"] = $_POST["required"];
                    $PROCESSED["requirements"] = json_encode($requirements);
                } else {
                    add_error($translate->_("You must include at least one detail, and one <strong>detail</strong> must be set as <strong>required</strong>."));
                }
            } else {
                add_error($translate->_("The <strong>Requirements</strong> is a required field."));
            }

            /**
             * Required field "max_level" / Max Hierarchical Levels
             */
            if (isset($_POST["max_level"]) && $tmp_input = clean_input($_POST["max_level"], array("trim", "int"))) {
                $PROCESSED["maximum_levels"] = $tmp_input;
                foreach ($_POST["level"] as $level) {
                    if ($tmp_input = clean_input($level, array("notags", "trim"))) {
                        $PROCESSED["levels"][] = $tmp_input;
                    } else {
                        $PROCESSED["levels"][] = "";
                    }
                }
            } else {
                add_error($translate->_("The <strong>Max Hierarchical Levels</strong> is a required field."));
            }

            /**
             * Required field "short_method_input" / Short Display Method
             */
            if (isset($_POST["short_method_input"]) && $tmp_input = clean_input($_POST["short_method_input"], ["trim"])) {
                $PROCESSED["short_method"] = $tmp_input;
            } else {
                add_error($translate->_("The <strong>Short Display Method</strong> is a required field."));
                $PROCESSED["short_method"] = "";
            }

            /**
             * Required field "long_method_input" / Long Display Method
             */
            if (isset($_POST["long_method_input"]) && $tmp_input = clean_input($_POST["long_method_input"], ["trim"])) {
                $PROCESSED["long_method"] = $tmp_input;
            } else {
                add_error($translate->_("The <strong>Long Display Method</strong> is a required field."));
            }

            //Tag Attributes
            if (isset($_POST["tagsets"]) && is_array($_POST["tagsets"])) {
                foreach ($_POST["tagsets"] as $tagset) {
                    if ($tmp_input = clean_input($tagset, array("trim", "int"))) {
                        $PROCESSED["tagset_ids"][] = $tmp_input;
                    }
                }
            }

            if (!has_error()) {
                $objective_set = array(
                    "code"          => $PROCESSED["objective_code"],
                    "title"         => $PROCESSED["objective_name"],
                    "description"   => $PROCESSED["objective_description"],
                    "shortname"     => $PROCESSED["objective_shortname"],
                    "languages"     => $PROCESSED["languages"],
                    "requirements"  => $PROCESSED["requirements"],
                    "maximum_levels"=> $PROCESSED["maximum_levels"],
                    "short_method"  => $PROCESSED["short_method"],
                    "long_method"   => $PROCESSED["long_method"],
                    "start_date"    => null,
                    "end_date"      => null,
                    "standard"      => $PROCESSED["standard"],
                    "created_date"  => time(),
                    "updated_date"  => time(),
                    "created_by"    => $ENTRADA_USER->getActiveId(),
                    "updated_by"    => $ENTRADA_USER->getActiveId()
                );
                $objective_set_model = new Models_ObjectiveSet($objective_set);
                if ($objective_set_model->insert()) {
                    $objective_set_id = $objective_set_model->getID();

                    $objective_tag = array(
                        "objective_code"        => $PROCESSED["objective_code"],
                        "objective_name"        => $PROCESSED["objective_name"],
                        "objective_description" => $PROCESSED["objective_description"],
                        "objective_parent"      => 0,
                        "objective_set_id"      => $objective_set_id,
                        "updated_date"          => time(),
                        "updated_by"            => $ENTRADA_USER->getActiveId()
                    );
                    
                    if ($db->AutoExecute("global_lu_objectives", $objective_tag, "INSERT")) {
                        $objective_id = $db->Insert_ID();
                        $objective = array(
                            "objective_id"      => $objective_id,
                            "objective_set_id"  => $objective_set_id,
                            "organisation_id"   => $ENTRADA_USER->getActiveOrganisation(),
                            "audience_type"     => "COURSE",
                            "audience_value"    => $PROCESSED["objective_audience"],
                            "updated_date"      => time(),
                            "updated_by"        => $ENTRADA_USER->getID()
                        );
                        $objective_org_model = new Models_Objective_Organisation($objective);
                        if ($objective_org_model->insert()) {

                            if ($PROCESSED["objective_audience"] == "all" || $PROCESSED["objective_audience"] == "none") {
                                $obj_audience_model = new Models_Objective_Audience();
                                if (!$obj_audience_model->fromArray($objective)->insert()) {
                                    add_error($translate->_("There was a problem adding the audience to the system. The system administrator was informed of this error; please try again later."));
                                    application_log("error", "There was an error associating an objective set with objective audience. Database said: " . $db->ErrorMsg());
                                }
                            } else if ($PROCESSED["objective_audience"] == "selected" && is_array($PROCESSED["course_ids"]) && !empty($PROCESSED["course_ids"])) {
                                foreach ($PROCESSED["course_ids"] as $course_id) {
                                    $obj_audience_model = new Models_Objective_Audience();
                                    $objective["audience_value"] = $course_id;
                                    if (!$obj_audience_model->fromArray($objective)->insert()) {
                                        add_error($translate->_("There was a problem adding the audience to the system. The system administrator was informed of this error; please try again later."));
                                        application_log("error", "There was an error associating an objective set with objective audience. Database said: " . $db->ErrorMsg());
                                    }
                                }
                            }

                            if (isset($PROCESSED["tagset_ids"]) && !empty($PROCESSED["tagset_ids"])) {
                                foreach ($PROCESSED["tagset_ids"] as $tagset_id) {
                                    $objective["target_objective_set_id"] = $tagset_id;
                                    $tag_attributes_model = new Models_Objective_TagAttribute($objective);
                                    if (!$tag_attributes_model->insert()) {
                                        add_error($translate->_("There was a problem adding the tag attributes to the system. The system administrator was informed of this error; please try again later."));
                                        application_log("error", "There was an error associating an objective set with tag set attributes. Database said: " . $db->ErrorMsg());
                                    }
                                }
                            }

                            if (isset($PROCESSED["levels"]) && !empty($PROCESSED["levels"])) {
                                foreach ($PROCESSED["levels"] as $key => $label) {
                                    $objective["level"] = $key + 1;
                                    $objective["label"] = $label;
                                    $level_model = new Models_Objective_TagLevel($objective);
                                    if (!$level_model->insert()) {
                                        add_error($translate->_("There was a problem adding the tag levels to the system. The system administrator was informed of this error; please try again later."));

                                        application_log("error", "There was an error associating an objective set with tag level attributes. Database said: " . $db->ErrorMsg());
                                    }
                                }
                            }
                        } else {
                            add_error($translate->_("There was a problem adding the organisation to the objective. The system administrator was informed of this error; please try again later."));
                            application_log("error", "There was an error associating an objective set with objective audience. Database said: " . $db->ErrorMsg());
                        }
                    } else {
                        add_error("There was a problem adding this objective to the system. The system administrator was informed of this error; please try again later.");
                        application_log("error", "There was an error updating an objective. Database said: ".$db->ErrorMsg());
                    }

                } else {
                    add_error($translate->_("There was a problem adding this Curriculum Tag to the system. The system administrator was informed of this error; please try again later."));

                    application_log("error", "There was an error associating an objective with an organisation. Database said: ".$db->ErrorMsg());
                }
            }

            if (has_error()) {
                $STEP = 1;
            } else {
                $url = ENTRADA_URL . "/admin/curriculum/tags";
                $url = ENTRADA_URL . "/admin/curriculum/tags/objectives?set_id=" . $objective_set_id;


                add_success("You have successfully added <strong>" . html_encode($PROCESSED["objective_name"]) . "</strong> to the system.<br /><br />You will now be redirected to the objectives index; this will happen <strong>automatically</strong> in 5 seconds or <a href=\"" . $url . "\" style=\"font-weight: bold\">click here</a> to continue.");

                $ONLOAD[] = "setTimeout('window.location=\\'" . $url . "\\'', 5000)";

                application_log("success", "New Objective Set [" . $OBJECTIVE_ID . "] added to the system.");
            }
            break;
    }

    // Display Content
    switch ($STEP) {
        case 2 :
            if (has_success()) {
                echo display_success();
            }

            if (has_notice()) {
                echo display_notice();
            }

            if (has_error()) {
                echo display_error();
            }
            break;
        case 1 :
        default:
            if (has_error()) {
                echo display_error();
            }
            $HEAD[] = "<script src=\"".ENTRADA_RELATIVE."/javascript/objectives.js?release=".html_encode(APPLICATION_VERSION)."\"></script>";
            $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/jquery/jquery.advancedsearch.js\"></script>";
            $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"".  ENTRADA_URL ."/css/jquery/jquery.advancedsearch.css\" />";
            $HEAD[] = "<script type=\"text/javascript\" src=\"".  ENTRADA_URL ."/javascript/objectives/tag_sets.js\"></script>";

            $ONLOAD[] = "selectObjective('#selectObjectiveField', ".(isset($PROCESSED["objective_parent"]) && $PROCESSED["objective_parent"] ? $PROCESSED["objective_parent"] : "0").")";
            $ONLOAD[] = "selectOrder('#selectOrderField', ".(isset($PROCESSED["objective_parent"]) && $PROCESSED["objective_parent"] ? $PROCESSED["objective_parent"] : "0").")";
            ?>
            <script type="text/javascript">
                var SITE_URL = "<?php echo ENTRADA_URL;?>";
                var org_id = "<?php echo $ENTRADA_USER->getActiveOrganisation(); ?>";
                var old_level = 0;
                var levels_label = JSON.parse('<?php echo json_encode($PROCESSED['levels']); ?>');
                var existing_labels = false;
                if (!jQuery.isEmptyObject(levels_label)) {
                    existing_labels = true;
                }
            </script>
            <h1><?php echo $translate->_("Add Curriculum Tag Set"); ?></h1>
            <h2 title="Tag Settings"><?php echo $translate->_("Tag Set Details"); ?></h2>

            <form action="<?php echo ENTRADA_URL."/admin/curriculum/tags?".replace_query(array("action" => "add", "step" => 2)); ?>" method="post" class="form-horizontal" id="tags_add_form">
                <div class="control-group">
                    <label for="objective_code" class="form-nrequired control-label">Code</label>
                    <div class="controls">
                        <input type="text" id="objective_code" name="objective_code" value="<?php echo ((isset($PROCESSED["objective_code"])) ? html_encode($PROCESSED["objective_code"]) : ""); ?>" class="span6">
                    </div>
                </div>

                <div class="control-group">
                    <label for="objective_name" class="form-required control-label">Title</label>
                    <div class="controls">
                        <input type="text" id="objective_name" name="objective_name" value="<?php echo ((isset($PROCESSED["objective_name"])) ? html_encode($PROCESSED["objective_name"]) : ""); ?>" class="span10" onkeyup="validateShortname(this.value)">
                    </div>
                </div>

                <div class="control-group">
                    <label for="objective_shortname" class="form-required control-label">Shortname</label>
                    <div class="controls">
                        <input type="text" id="objective_shortname" name="objective_shortname" value="<?php echo ((isset($PROCESSED["objective_shortname"])) ? html_encode($PROCESSED["objective_shortname"]) : ""); ?>" class="span6">
                    </div>
                </div>

                <div class="control-group">
                    <label for="objective_description" class="control-label">Description</label>
                    <div class="controls">
                        <textarea id="objective_description" name="objective_description" class="span11 expandable"><?php echo ((isset($PROCESSED["objective_description"])) ? html_encode($PROCESSED["objective_description"]) : ""); ?></textarea>
                    </div>
                </div>

                <div class="control-group">
                    <div class="controls">
                        <label class="checkbox">
                            <input type="checkbox" id="standard" value="1" name="standard" <?php echo ((isset($PROCESSED["standard"]) && $PROCESSED["standard"] == 1) ? "checked=\"checked\"" : ""); ?> />
                            <?php echo $translate->_("This is a standardized Curriculum Tag Set."); ?>
                        </label>
                    </div>
                </div>

                <div class="control-group">
                    <label for="objective_audience" class="form-required control-label">Applicable to</label>
                    <div class="controls">
                        <label class="radio">
                            <input type="radio" name="objective_audience" value="all" <?php echo ( !isset($PROCESSED["objective_audience"]) || (isset($PROCESSED["objective_audience"]) && $PROCESSED["objective_audience"] == "all") ? "checked=\"checked\"" : ""); ?>/> <?php echo $translate->_("all_courses"); ?>
                        </label>

                        <label class="radio">
                            <input type="radio" name="objective_audience" value="none" <?php echo (isset($PROCESSED["objective_audience"]) && $PROCESSED["objective_audience"] == "none" ? "checked=\"checked\"" : ""); ?>/> <?php echo $translate->_("no_courses"); ?>
                        </label>

                        <div>
                            <label class="radio">
                                <input type="radio" name="objective_audience" value="selected" <?php echo (isset($PROCESSED["objective_audience"]) && $PROCESSED["objective_audience"] == "selected" ? "checked=\"checked\"" : ""); ?> /> <?php echo $translate->_("selected_courses"); ?>
                            </label>
                            <button id="choose-course-btn" class="btn btn-search-filter space-above space-below hide" style="min-width: 220px; text-align: left;"><?php echo $translate->_("Browse All Courses"); ?> <i class="icon-chevron-down btn-icon pull-right"></i></button>
                        </div>
                    </div>
                </div>

                <h2 title="Tag Options" class="collapsable expanded"><?php echo $translate->_("Tag Options"); ?></h2>
                <div id="tag-options">
                    <div class="control-group">
                        <?php
                        $json_data = Entrada_Settings::fetchValueByShortname("language_supported");
                        if ($json_data) {
                            $language_supported = json_decode($json_data, true);
                        }
                        if (isset($language_supported) && !empty($language_supported) && count($language_supported) > 1) { ?>
                            <label class="form-required control-label"><?php echo $translate->_("Languages"); ?></label>
                            <div class="controls">
                                <?php foreach ($language_supported as $index => $value) { ?>
                                    <label class="checkbox"><input type="checkbox" <?php echo (!isset($PROCESSED["languages"]) || (isset($PROCESSED["languages"]) && in_array($index, json_decode($PROCESSED["languages"],true))) ? "checked=\"checked\"" : ""); ?> name="languages[]" id="lang-"<?php echo $value["name"]; ?> value="<?php echo $index; ?>">
                                        <?php echo $value["name"] ?>
                                    </label>
                                <?php } ?>
                            </div>
                        <?php } else { ?>
                            <input type="hidden" checked="checked" name="languages[]" value="<?php echo ($language_supported && !empty($language_supported) ? key($language_supported) : "en"); ?>">
                        <?php }?>
                    </div>

                    <div class="control-group">
                        <label class="form-required control-label no-padding"><?php echo $translate->_("Detail Requirements"); ?></label>
                        <div class="controls">
                            <div class="row-fluid">
                                <label class="checkbox span3"><input type="checkbox"  name="requirements[]" value="code" <?php echo (!isset($PROCESSED["requirements_fields"]) || (isset($PROCESSED["requirements_fields"]) && in_array("code", $PROCESSED["requirements_fields"])) ? "checked=\"checked\"" : ""); ?>>
                                    <?php echo $translate->_("Code"); ?>
                                </label>
                                <label class="checkbox span3 hide" id="req_code"><input type="checkbox" id="code_checkbox" name="required[]" value="code" <?php echo (isset($PROCESSED["required"]) && in_array("code", $PROCESSED["required"]) ? "checked=\"checked\"" : ""); ?>>
                                    <?php echo $translate->_("Required"); ?>
                                </label>
                            </div>
                            <div class="row-fluid">
                                <label class="checkbox span3"><input type="checkbox"  name="requirements[]" value="title" <?php echo (!isset($PROCESSED["requirements_fields"]) || (isset($PROCESSED["requirements_fields"]) && in_array("title", $PROCESSED["requirements_fields"])) ? "checked=\"checked\"" : ""); ?>>
                                    <?php echo $translate->_("Title"); ?>
                                </label>
                                <label class="checkbox span3 hide" id="req_title"><input type="checkbox" id="title_checkbox" name="required[]" value="title" <?php echo (!isset($PROCESSED["required"]) || (isset($PROCESSED["required"]) && in_array("title", $PROCESSED["required"])) ? "checked=\"checked\"" : ""); ?>>
                                    <?php echo $translate->_("Required"); ?>
                                </label>
                            </div>
                            <div class="row-fluid">
                                <label class="checkbox span3"><input type="checkbox"  name="requirements[]" value="description" <?php echo (!isset($PROCESSED["requirements_fields"]) || (isset($PROCESSED["requirements_fields"]) && in_array("description", $PROCESSED["requirements_fields"])) ? "checked=\"checked\"" : ""); ?>>
                                    <?php echo $translate->_("Description"); ?>
                                </label>
                                <label class="checkbox span3 hide" id="req_description"><input type="checkbox" id="description_checkbox" name="required[]"  value="description" <?php echo (isset($PROCESSED["required"]) && in_array("description", $PROCESSED["required"]) ? "checked=\"checked\"" : ""); ?>>
                                    <?php echo $translate->_("Required"); ?>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="control-group">
                        <label for="max_level" class="form-required control-label">
                            <?php echo $translate->_("Max Hierarchical Levels"); ?>
                        </label>
                        <div class="controls">
                            <input class="input-small" type="number" id="max_level" min="1"
                                   maxlength="<?php echo Entrada_Settings::fetchValueByShortname("curriculum_tagsets_max_allow_levels") ? Entrada_Settings::fetchValueByShortname("curriculum_tagsets_max_allow_levels ") : 9;?>"
                                   name="max_level"
                                   value="<?php echo(isset($PROCESSED["maximum_levels"]) ? $PROCESSED["maximum_levels"] : 1) ?>"/>
                        </div>
                        <div id="level-labels" class="space-above">
                        </div>
                    </div>
                </div>

                <h2 title="Tag Display Options" class="collapsable expanded"><?php echo $translate->_("Tag Display Options"); ?></h2>
                <div id="tag-display-options">
                        <div class="row-fluid">
                            <div class="span6">
                                <div class="control-group">
                                    <label
                                            class="form-required control-label"><?php echo $translate->_("Short Display Method"); ?></label>
                                    <div class="controls">
                                        <div class="code hide">
                                            <label class="checkbox"><input type="checkbox" name="short_method[]" id="short_method_code" value="%c" <?php echo (isset($PROCESSED["short_method"]) && (strpos($PROCESSED["short_method"], "%c") !== false) ? "checked=\"checked\"" : ""); ?>>
                                                <?php echo $translate->_("Code"). "<span class='muted'> (%c) </span>";; ?>
                                            </label>
                                        </div>
                                        <div class="title hide">
                                            <label class="checkbox"><input type="checkbox" name="short_method[]" id="short_method_title" value="%t" <?php echo (!isset($PROCESSED["short_method"]) || isset($PROCESSED["short_method"]) && (strpos($PROCESSED["short_method"], "%t") !== false) ? "checked=\"checked\"" : ""); ?>>
                                                <?php echo $translate->_("Title"). "<span class='muted'> (%t) </span>"; ?>
                                            </label>
                                        </div>
                                        <div class="description hide">
                                            <label class="checkbox"><input type="checkbox" name="short_method[]" id="short_method_description" value="%d" <?php echo (isset($PROCESSED["short_method"]) && (strpos($PROCESSED["short_method"], "%d") !== false) ? "checked=\"checked\"" : ""); ?>>
                                                <?php echo $translate->_("Description"). "<span class='muted'> (%d) </span>"; ?>
                                            </label>
                                        </div>
                                        <div class="input">
                                            <input  type="text" class="span8" name="short_method_input" id="short_method_input" value="<?php echo (isset($PROCESSED["short_method"]) ? $PROCESSED["short_method"] : "%t"); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="span6">
                                <p class="short_method_preview_label"><?php echo $translate->_("Short display method preview"); ?></p>
                                <div id="short_method_preview" class="well display_method_preview"></div>
                            </div>
                        </div>

                        <div class="row-fluid">
                            <div class="span6">
                                <div class="control-group">
                                    <label class="form-required control-label"><?php echo $translate->_("Long Display Method"); ?></label>
                                    <div class="controls">
                                        <div class="code hide">
                                            <label class="checkbox"><input type="checkbox" name="long_method[]" id="long_method_code" value="%c" <?php echo (isset($PROCESSED["long_method"]) && (strpos($PROCESSED["long_method"], "%c") !== false) ? "checked=\"checked\"" : ""); ?>>
                                                <?php echo $translate->_("Code") ?>
                                            </label>
                                        </div>
                                        <div class="title hide">
                                            <label class="checkbox"><input type="checkbox" name="long_method[]" id="long_method_title" value="%t" <?php echo (!isset($PROCESSED["long_method"]) || isset($PROCESSED["long_method"]) && (strpos($PROCESSED["long_method"], "%t") !== false) ? "checked=\"checked\"" : ""); ?>>
                                                <?php echo $translate->_("Title"); ?>
                                            </label>
                                        </div>
                                        <div class="description hide">
                                            <label class="checkbox"><input type="checkbox" name="long_method[]" id="long_method_description" value="%d" <?php echo (!isset($PROCESSED["long_method"]) || isset($PROCESSED["long_method"]) && (strpos($PROCESSED["long_method"], "%d") !== false) ? "checked=\"checked\"" : ""); ?>>
                                                <?php echo $translate->_("Description"); ?>
                                            </label>
                                        </div>
                                        <div class="input">
                                            <textarea class="hide" name="long_method_input" id="long_method_input"><?php echo (isset($PROCESSED["long_method"]) ? $PROCESSED["long_method"] : "%t <br> %d"); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="span6">
                                <p class="long_method_preview_label"><?php echo $translate->_("Long display method preview"); ?></p>
                                <div id="long_method_preview" class="well display_method_preview"></div>
                            </div>
                        </div>
                </div>

                <h2 title="Tag Attributes" class="collapsable expanded"><?php echo $translate->_("Mappable Curriculum Tag Sets"); ?></h2>
                <div id="tag-attributes">
                    <?php
                    if (Entrada_Settings::fetchValueByShortname("curriculum_tagsets_allow_attributes", $ENTRADA_USER->getActiveOrganisation())) { ?>
                        <div class="control-group">
                            <label for="choose-tagset-btn" class="control-label"><?php echo $translate->_("Tag Sets"); ?></label>
                            <div class="controls">
                                <button id="choose-tagset-btn" class="btn btn-search-filter" style="min-width: 220px; text-align: left;"><?php echo $translate->_("Browse All Tag Sets"); ?> <i class="icon-chevron-down btn-icon pull-right"></i></button>
                            </div>
                        </div>
                    <?php } ?>
                </div>

                <div class="control-group">
                    <a href="<?php echo ENTRADA_URL; ?>/admin/curriculum/tags" class="btn"><?php echo $translate->_("global_button_cancel"); ?></a>
                    <div class="pull-right">
                        <input type="submit" class="btn btn-primary" value="<?php echo $translate->_("global_button_save"); ?>" />
                    </div>
                </div>
                <?php
                if (!empty($PROCESSED["course_ids"])) {
                    foreach ($PROCESSED["course_ids"] as $course_id) {
                        $target_id = (int) $course_id;
                        if ($course = Models_Course::fetchRowByID($target_id)) {
                            $target_label = $course->getCourseName();

                            if ($course->getCourseCode() != "") {
                                $target_label = $course->getCourseCode() . ": " . $target_label;
                            }
                            echo "<input id=\"course_" . $target_id . "\" class=\"search-target-control course_search_target_control\" type=\"hidden\" name=\"course[]\" value=\"" . $target_id . "\" data-id=\"" . $target_id . "\" data-label=\"" . ucfirst($target_label) . "\"/>";
                        }
                    }
                }
                if (!empty($PROCESSED["tagset_ids"])) {
                    foreach ($PROCESSED["tagset_ids"] as $tagset_id) {
                        $target_id = (int) $tagset_id;
                        if ($tagset = Models_ObjectiveSet::fetchRowByID($target_id)) {
                            $target_label = $tagset->getTitle();

                            echo "<input id=\"tag-set_" . $target_id . "\" class=\"search-target-control tag_search_target_control\" type=\"hidden\" name=\"tagsets[]\" value=\"" . $target_id . "\" data-id=\"" . $target_id . "\" data-label=\"" . ucfirst($target_label) . "\"/>";
                        }
                    }
                }
                ?>
            </form>
            <script type="text/javascript">
                jQuery("#choose-course-btn").advancedSearch({
                    api_url: ENTRADA_URL + "/api/disclaimers.api.php",
                    build_selected_filters: true,
                    build_form: true,
                    resource_url: ENTRADA_URL,
                    select_all_enabled: true,
                    filters: {
                        course: {
                            label: "<?php echo $translate->_("Course"); ?>",
                            data_source: "get-courses",
                            selector_control_name: "course"
                        }
                    },
                    target_name: "course",
                    control_class: "course-selector",
                    no_results_text: "<?php echo $translate->_("No courses found matching the search criteria"); ?>",
                    parent_form: jQuery("#tags_add_form")
                });

                jQuery("#choose-tagset-btn").advancedSearch({
                    api_url: '<?php echo ENTRADA_URL; ?>/api/curriculum-tags.api.php',
                    build_selected_filters: true,
                    build_form: true,
                    resource_url: ENTRADA_URL,
                    select_all_enabled: true,
                    filters: {
                        "tag-set": {
                            label: '<?php echo $translate->_("Tag Set"); ?>',
                            data_source: 'get-tag-sets',
                            selector_control_name: "tagsets"
                        }
                    },
                    target_name: "tagsets",
                    no_results_text: "<?php echo $translate->_("No Tag Sets found matching the search criteria"); ?>",
                    parent_form: jQuery("#tags_add_form"),
                    width: 400
                });
            </script>
            <?php
        break;
    }
}
