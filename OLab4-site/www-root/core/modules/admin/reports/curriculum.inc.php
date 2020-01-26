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
 * This file is used to show a report of objectives within a curriculum tag set and its mapped objectives
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jonatan Caraballo <jch9@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

if ((!defined("PARENT_INCLUDED")) || (!defined("IN_REPORTS"))) {
    exit;
} elseif ((!isset($_SESSION["isAuthorized"])) || (!$_SESSION["isAuthorized"])) {
    header("Location: ".ENTRADA_RELATIVE);
    exit;
} elseif (!$ENTRADA_ACL->amIAllowed("report", "read", false)) {
    add_error("Your account does not have the permissions required to use this feature of this module.<br /><br />If you believe you are receiving this message in error please contact <a href=\"mailto:".html_encode($AGENT_CONTACTS["administrator"]["email"])."\">".html_encode($AGENT_CONTACTS["administrator"]["name"])."</a> for assistance.");

    echo display_error();

    application_log("error", "Group [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["group"]." and role [".$_SESSION["permissions"][$ENTRADA_USER->getAccessId()]["role"]."] does not have access to this module [".$MODULE."]");
} else {
    if (isset($_GET["mode"]) && $_GET["mode"] == "ajax") {
        ob_clear_open_buffers();
        if (isset($_GET["method"]) && $tmp_var = clean_input($_GET["method"], array("trim", "notags"))) {
            $method = $tmp_var;
            $parent_id = 0;
            $mapping_direction = "to";

            if (isset($_GET["objective_id"]) && $tmp_var = clean_input($_GET["objective_id"], array("int"))) {
                $objective_id = $tmp_var;
            }

            switch ($method) {
                case "get-curriculum-tags":
                    $tags = Models_Objective::getObjectiveChildren($objective_id);
                    $tag_set = Models_Objective::fetchRow($objective_id);
                    if ($tags && $tag_set) {
                        echo json_encode(array("status" => "success", "data" => $tags, "tag_set_name" => $tag_set->getName(), "tag_set_description" => $tag_set->getDescription(), "path" => $tag_set->getPath($objective_id)));
                    } else {
                        echo json_encode(array("status" => "error", "data" => "No tags within this curriculum tag set"));
                    }
                    break;

                case "get-mapped-tags":

                    if (isset($_GET["mapping_direction"]) && $tmp_var = clean_input($_GET["mapping_direction"], array("trim", "notags"))) {
                        $mapping_direction = $tmp_var;
                    }

                    $objective = new Models_Objective();
                    if ($tags = ($mapping_direction == "from" ? Models_Objective::fetchObjectivesMappedFrom($objective_id) : Models_Objective::fetchObjectivesMappedTo($objective_id))) {
                        $children = array();
                        foreach ($tags as $tag) {
                            $path = $objective->getPath($tag["objective_parent"]);
                            $tag["path"] = $path;
                            $children[] = $tag;
                        }
                        echo json_encode(array("status" => "success", "data" => $children));
                    } else if ($tags = Models_Objective::fetchAllByParentID($ENTRADA_USER->getActiveOrganisation(), $objective_id)) {
                        $children = array();
                        foreach ($tags as $tag) {
                            $tag = $tag->toArray();
                            $path = $objective->getPath($tag["objective_parent"]);
                            $tag["path"] = $path;
                            $children[] = $tag;
                        }
                        echo json_encode(array("status" => "success", "data" => $children));
                    } else {
                        echo json_encode(array("status" => "error", "data" => "No tags mapped to this curriculum tag"));
                    }
                    break;

                default:
                    echo json_encode(array("status" => "error", "data" => "The method provided does not exist"));
                    break;
            }
        } else {
            echo json_encode(array("status" => "error", "data" => "No method provided"));
        }
        exit;
    } else {
        $BREADCRUMB[] = array("url" => "", "title" => "Curriculum Tags Report");
        $HEAD[] = "<script type=\"text/javascript\" src=\"".ENTRADA_RELATIVE."/javascript/curriculum_reports.js\"></script>\n";
        ?>
        <h1 class="report_title"><?php echo $translate->_("Curriculum Tag Mapping Report"); ?></h1>

        <form class="space-above form-horizontal" id="curriculum-report-form" method="post">
            <div class="control-group">
                <label class="control-label"><?php echo $translate->_("Curriculum Tag Set"); ?></label>
                <div class="controls">
                    <select name="tag-set" id="tag-set" class="span6">
                        <?php
                        $tag_sets = Models_Objective::fetchAllByOrganisationParentID($ENTRADA_USER->getActiveOrganisation(), 0);
                        if ($tag_sets) {
                            foreach ($tag_sets as $tag_set) {
                                ?>
                                <option value="<?php echo $tag_set->getID(); ?>"><?php echo $tag_set->getName(); ?></option>
                                <?php
                            }
                        }
                        ?>
                    </select>
                    <button type="button" id="print-button" class="btn btn-primary pull-right"><i class="fa fa-print"></i> <?php echo $translate->_("Print Report"); ?></button>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label">Mapping Direction</label>
                <div class="controls">
                    <div class="btn-group">
                        <button type="button" class="btn mapping_direction_btn active" data-direction="to"><i class="fa fa-arrow-down"></i> Mapped to</button>
                        <button type="button" class="btn mapping_direction_btn" data-direction="from"><i class="fa fa-arrow-up"></i> Mapped from</button>
                    </div>
                    <div class="row-fluid">
                        <div class="alert alert-info span8 space-above">
                            <p class="muted">
                                <small>
                                    <strong>Mapped to</strong> shows all the tags mapped to a specific curriculum tag<br>
                                    <strong>Mapped from</strong> shows all the tags that a specific curriculum tag is mapped from
                                </small>
                            </p>
                        </div>
                    </div>
                    <input type="hidden" id="mapping_direction" name="mapping_direction" value="to">
                </div>
            </div>
            <div id="tags-list"></div>
        </form>
        <?php
    }
}
