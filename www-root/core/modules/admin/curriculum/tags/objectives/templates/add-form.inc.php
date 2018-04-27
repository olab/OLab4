<h1><?php echo $translate->_("Add Tag"); ?></h1>
<form class="form-horizontal"
      action="<?php echo ENTRADA_URL . "/admin/curriculum/tags/objectives?" . replace_query(array("step" => 2)); ?>"
      method="post" id="objectivesForm">
    <div id="error-msgs"></div>
    <!-- Nav tabs -->
    <div class="row-fluid">
        <ul class="control-group nav nav-tabs">
            <li role="presentation" class="active">
                <a href="#details" aria-controls="details" role="tab"
                   data-toggle="tab"><?php echo $translate->_("Details"); ?></a>
            </li>
            <?php if ($attributes) { ?>
                <li role="presentation">
                    <a href="#tagAttributes" aria-controls="tagAttributes" role="tab"
                       data-toggle="tab"><?php echo $translate->_("Map Curriculum Tags"); ?></a>
                </li>
            <?php }
            if ($ENTRADA_ACL->amIAllowed("objectivenotes", "read", false)) {
                ?>
                <li role="presentation">
                    <a href="#adminNotes" aria-controls="adminNotes" role="tab"
                       data-toggle="tab"><?php echo $translate->_("Admin Notes"); ?></a>
                </li>
                <?php
            }
            ?>
        </ul>
    </div>

    <div class="row-fluid">
        <div class="tab-content">
            <!-- Details Tab -->
            <div role="tabpanel" class="tab-pane active" id="details">
                <h2 title="Tag Details" class="collapsable expanded"><?php echo $translate->_("Tag Details"); ?></h2>
                <div id="tag-details">
                    <div class="control-group">
                        <label for="objective_status_id" class="form-nrequired control-label">
                            <?php echo $translate->_("Status"); ?>
                        </label>
                        <div class="controls">
                            <select id="objective_status_id" name="objective_status_id" class="span5">
                                <?php
                                if ($status) {
                                    $default_status = (Entrada_Settings::fetchValueByShortname("curriculum_tags_default_status") ? Entrada_Settings::fetchValueByShortname("curriculum_tags_default_status ") - 1 : 1);
                                    foreach ($status as $key => $value) {
                                        $selected = false;
                                        if ((isset($PROCESSED["objective_status_id"]) && $PROCESSED["objective_status_id"] == $value["objective_status_id"]) || (!isset($PROCESSED["objective_status_id"]) && $key == $default_status)) {
                                            $selected = true;
                                        }
                                        echo "<option value=\"" . html_encode($value["objective_status_id"]) . "\"" . ($selected ? " selected=\"selected\" " : "") . ">" . $translate->_($value["objective_status_description"]) . "</option>\n";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <?php
                    if ($num_languages > 1) { ?>
                        <div class="control-group">
                            <label for="objective_translation_status_id" class="form-nrequired control-label">
                                <?php echo $translate->_("Translation"); ?>
                            </label>
                            <div class="controls">
                                <select id="objective_translation_status_id" name="objective_translation_status_id" class="span5">
                                    <?php
                                    if ($translationStatus) {
                                        foreach ($translationStatus as $key => $value) {
                                            $selected = false;
                                            if (isset($PROCESSED["objective_translation_status_id"]) && $PROCESSED["objective_translation_status_id"] == $value["objective_translation_status_id"] || !isset($PROCESSED["objective_translation_status_id"]) && $key == 0) {
                                                $selected = true;
                                            }
                                            echo "<option value=\"" . html_encode($value["objective_translation_status_id"]) . "\"" . ($selected ? " selected=\"selected\" " : "") . ">" . $translate->_($value["objective_translation_status_description"]) . "</option>\n";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    <?php }

                    if ($objective_set->getRequirements() != null) {
                        $requirements = json_decode($objective_set->getRequirements(), true);
                        ?>
                        <?php foreach ($languages as $key => $language) {
                            $name_language = json_decode(Entrada_Settings::fetchValueByShortname("language_supported"), true);
                            foreach ($requirements as $index => $value) {
                                if ($index != "code" || $index == "code" && $key == 0) {
                                    ?>
                                    <div class="control-group">
                                        <label for="objective_<?php echo $index; ?>"
                                               class="<?php echo($value["required"] ? "form-required" : "form-nrequired") ?> control-label"><?php echo(count($languages) > 1 && $index != "code" ? $name_language[$language]["name"] . " " . ucfirst($index) : ucfirst($index)); ?></label>
                                        <div class="controls">
                                            <?php if ($index == "description") { ?>
                                                <textarea
                                                        id="objective_<?php echo $index . "[" . $language . "]"; ?>"
                                                        name="objective_<?php echo $index . "[" . $language . "]"; ?>"
                                                        class="<?php echo($value["required"] ? "form-required" : "form-nrequired") ?> span10 expandable"><?php echo ((isset($PROCESSED["objective_".$index][$language])) ? html_encode($PROCESSED["objective_".$index][$language]) : ""); ?></textarea>
                                            <?php } else { ?>
                                                <input type="text"
                                                       id="objective_<?php echo $index . ($index == "title" ? "[" . $language . "]" : ""); ?>"
                                                       name="objective_<?php echo $index . ($index == "title" ? "[" . $language . "]" : ""); ?>"
                                                       class="<?php echo($index == "code" ? "span5" : "span10") ?>"
                                                       value="<?php echo ($index == "code" && isset($PROCESSED["objective_".$index]) ? $PROCESSED["objective_".$index] : ((isset($PROCESSED["objective_".$index][$language])) ? html_encode($PROCESSED["objective_".$index][$language]) : "")); ?>">
                                            <?php } ?>
                                        </div>
                                    </div>
                                <?php }
                            }
                        }
                    }

                    if ($objectives_order) { ?>
                        <div class="control-group">
                            <label for="objective_order" class="form-nrequired control-label">
                                <?php echo $translate->_("Display Order"); ?>
                            </label>
                            <div class="controls">
                                <select id="objective_order" name="objective_order" class="span5">
                                    <?php
                                    $count = count($objectives_order);
                                    foreach ($objectives_order as $key => $value) {
                                        $selected = false;
                                        if ((isset($PROCESSED["objective_order"]) && $PROCESSED["objective_order"] == $value->getOrder())) {
                                            $selected = true;
                                        }
                                        echo "<option value=\"" . html_encode($key) . "\"" . ($selected ? " selected=\"selected\" " : "") . ">" . sprintf($translate->_("Before %s"), $value->getShortMethod()) . "</option>\n";
                                    }
                                    echo "<option value=\"" . html_encode($count) . "\"" . ((isset($PROCESSED["objective_order"]) && $PROCESSED["objective_order"] == $count) || !isset($PROCESSED["objective_order"]) ? " selected=\"selected\" " : "") .">" . sprintf($translate->_("After %s"), $objectives_order[$count-1]->getShortMethod()) . "</option>\n";
                                    ?>
                                </select>
                            </div>
                        </div>
                    <?php } ?>

                    <div class="control-group">
                        <div class="controls">
                            <label class="checkbox">
                                <input type="checkbox" id="non_examinable" value="1"
                                       name="non_examinable" <?php echo((isset($PROCESSED["non_examinable"]) && $PROCESSED["non_examinable"] == 1) ? "checked=\"checked\"" : ""); ?> />
                                <?php echo $translate->_("Non-Examinable"); ?>
                            </label>
                        </div>
                        <div class="controls">
                            <label class="checkbox">
                                <input type="checkbox" id="objective_loggable" value="1"
                                       name="objective_loggable" <?php echo((isset($PROCESSED["objective_loggable"]) && $PROCESSED["objective_loggable"] == 1) ? "checked=\"checked\"" : ""); ?> />
                                <?php echo $translate->_("This curriculum tag should be loggable in the Experience Logbook"); ?>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            if (!empty($PROCESSED["linked_tags"])) {
                foreach ($PROCESSED["linked_tags"] as $linked_tag) {
                    $target_id = (int) $linked_tag;
                    if ($tag = Models_Objective::fetchRow($target_id)) {
                        $root = $tag->getRoot();
                        $root_title = str_replace(" ", "-", $root->getShortMethod());
                        $target_label = $tag->getShortMethod();
                        echo "<input id=" . $root_title . "_" . $target_id . " class=\"search-target-control " . $root->getID() . "_search_target_control\" type=\"hidden\" name=\"linked_tags[]\" value=\"" . $target_id . "\" data-id=\"" . $target_id . "\" data-label=\"" . ucfirst($target_label) . "\"/>";
                    }
                }
            } if  ($attributes) { ?>
                <!-- Global Tag Mapping Tab -->
                <div role="tabpanel" class="tab-pane" id="tagAttributes">
                    <?php include("add-attributes.inc.php"); ?>
                </div>
            <?php }
            if ($ENTRADA_ACL->amIAllowed("objectivenotes", "read", false)) {
                ?>
                <!-- Admin Notes Tab -->
                <div role="tabpanel" class="tab-pane" id="adminNotes">
                    <?php include("admin-notes.inc.php"); ?>
                </div>
                <?php
            } ?>
        </div>
    </div>

    <!-- Cancel & Save buttons -->
    <p/>
    <div class="row-fluid buttons">
        <div class="pull-left">
            <input type="button" class="btn" id="btnCancel" onclick="window.location='<?php echo ENTRADA_URL; ?>/admin/curriculum/tags/objectives?set_id=<?php echo $objective_set->getID(); ?>'"
                   value="<?php echo $translate->_("Cancel"); ?>"/>
        </div>
        <div class="pull-right">
            <input type="submit" name="saveClose" class="btn btn-primary"
                   value="<?php echo $translate->_("Save & Close"); ?>"/>
        </div>
    </div>
    <?php
        if (isset($PARENT_ID)) {
            echo "<input type=\"hidden\" id=\"parent_id\" name=\"parent_id\" value=\"$PARENT_ID\">";
        }
    ?>
</form>
