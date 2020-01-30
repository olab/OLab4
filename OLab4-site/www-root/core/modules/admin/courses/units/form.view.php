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
 * @author Developer: Carlos Torchia <carlos.torchia@ubc.ca>
 * @copyright Copyright 2016 University of British Columbia. All Rights Reserved.
 *
 */

call_user_func($this->onload, "week_faculty_list = new AutoCompleteList({ type: 'week_faculty', url: '". ENTRADA_RELATIVE ."/api/personnel.api.php?type=faculty', remove_image: '". ENTRADA_RELATIVE ."/images/action-delete.gif'})");

$translate = $this->translate;

?>
<form id="add-unit-form" class="form-horizontal" action="<?php echo $this->action_url; ?>" method="post">
    <div class="row-fluid">
        <div class="span12">
            <?php if ($this->mode == "add"): ?>
                <div class="span5">
                    <h1><?php echo $translate->_("Add Unit"); ?></h1>
                </div>
            <?php endif; ?>
            <?php if ($this->mode == "edit"): ?>
                <div class="span5">
                    <h1><?php echo $translate->_("Edit Unit"); ?></h1>
                </div>
            <?php endif; ?>
            <div class="span7 no-printing">
                <?php if ($this->curriculum_periods): ?>
                    <div class="pull-right form-horizontal no-printing" style="margin-bottom:0; margin-top:18px">
                        <div class="control-group">
                            <label for="cperiod_select" class="control-label muted unit-index-label"><?php echo $translate->_("Period"); ?>:</label>
                            <div class="controls unit-index-select">
                                <select style="width:100%" id="<?php echo ($this->mode == "edit") ? "cperiod_select_disabled" : "cperiod_select"; ?>" name="<?php echo ($this->mode == "edit") ? "cperiod_select_disabled" : "cperiod_select"; ?>"<?php echo ($this->mode == "edit") ? " disabled" : ""; ?>>
                                    <?php foreach ($this->curriculum_periods as $period): ?>
                                        <option value="<?php echo html_encode($period->getID());?>" <?php echo (isset($this->cperiod_id) && $this->cperiod_id == $period->getID() ? "selected=\"selected\"" : ""); ?>>
                                            <?php echo (($period->getCurriculumPeriodTitle()) ? html_encode($period->getCurriculumPeriodTitle()) . " - " : "") . date("F jS, Y", html_encode($period->getStartDate()))." to ".date("F jS, Y", html_encode($period->getFinishDate())); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($this->mode == "edit"): ?>
                                    <input type="hidden" id="cperiod_select" name="cperiod_select" value="<?php echo html_encode($this->cperiod_id) ?>" />
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <h2><?php echo $translate->_("Unit Details"); ?></h2>

    <div class="control-group">
        <label class="control-label form-nrequired" for="unit_code"><?php echo $translate->_("Unit Code"); ?></label>
        <div class="controls">
            <input type="text" id="unit_code" name="unit_code" class="span8" value="<?php echo ($this->unit_code ? html_encode($this->unit_code) : ""); ?>"/>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label form-required" for="unit_title"><?php echo $translate->_("Unit Title"); ?></label>
        <div class="controls">
            <input type="text" id="unit_title" name="unit_title" class="span8" value="<?php echo ($this->unit_title ? html_encode($this->unit_title) : ""); ?>"/>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label form-nrequired" for="unit_description"><?php echo $translate->_("Unit Description"); ?></label>
        <div class="controls">
            <textarea id="unit_description" name="unit_description" style="width: 100%; height: 100px" cols="70" rows="10"><?php echo html_encode(trim(strip_selected_tags($this->unit_description, array("font")))); ?></textarea>
        </div>
    </div>

    <div class="control-group">
        <label for="week_id" class="control-label form-nrequired"><?php echo $translate->_("Week"); ?>:</label>
        <div class="controls">
            <select id="week_id" class="span8" name="week_id">
                <option value="" <?php echo ((!$this->week_id) ? "selected=\"selected\"" : ""); ?>>- <?php echo $translate->_("Select a Week"); ?> -</option>
                <?php foreach ($this->weeks as $week): ?>
                    <option value="<?php echo html_encode($week->getID()); ?>" <?php echo ($this->week_id == $week->getID() ? "selected=\"selected\"" : ""); ?>>
                        <?php echo (($week->getWeekTitle()) ? html_encode($week->getWeekTitle()) : ""); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="control-group">
        <label for="week_faculty_name" class="control-label form-nrequired"><?php echo $translate->_("Week Chair"); ?>:</label>
        <div class="controls">
            <input type="text" id="week_faculty_name" name="fullname" autocomplete="off" placeholder="Example: <?php echo html_encode($this->user->getLastname().", ".$this->user->getFirstname()); ?>" />
            <div class="autocomplete" id="week_faculty_name_auto_complete"></div>
            <input type="hidden" id="associated_week_faculty" name="associated_week_faculty" />
            <input type="button" class="btn" id="add_associated_week_faculty" value="Add" />
            <script type="text/javascript">
                jQuery(function(){
                    jQuery("#week_faculty_list").on("click", "img.list-cancel-image", function(){
                        var proxy_id = jQuery(this).attr("rel");
                        if ($("week_faculty_"+proxy_id)) {
                            var associated_week_faculty = jQuery("#associated_week_faculty").val().split(",");
                            var remove_index = associated_week_faculty.indexOf(proxy_id);

                            associated_week_faculty.splice(remove_index, 1);

                            jQuery("#associated_week_faculty").val(associated_week_faculty.join());

                            $("week_faculty_"+proxy_id).remove();
                        }
                    });
                });
            </script>
            <ul id="week_faculty_list" class="menu" style="margin-top: 15px">
                <?php foreach ($this->associated_faculty as $faculty): ?>
                    <?php if (isset($this->faculty_list[$faculty])): ?>
                        <li class="user" id="week_faculty_<?php echo $this->faculty_list[$faculty]["proxy_id"]; ?>" style="cursor: move;margin-bottom:10px;width:350px;"><?php echo $this->faculty_list[$faculty]["fullname"]; ?><img src="<?php echo ENTRADA_URL; ?>/images/action-delete.gif" rel="<?php echo $this->faculty_list[$faculty]["proxy_id"]; ?>" class="list-cancel-image" /></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
            <input type="hidden" id="week_faculty_ref" name="week_faculty_ref" value="" />
            <input type="hidden" id="week_faculty_id" name="week_faculty_id" value="" />
        </div>
    </div>

    <div class="control-group">
        <label class="control-label form-required" for="unit_order"><?php echo $translate->_("Unit Order"); ?></label>
        <div class="controls">
            <input type="text" id="unit_order" name="unit_order" class="span8" value="<?php echo isset($this->unit_order) ? $this->unit_order : ""; ?>"/>
        </div>
    </div>

    <?php
    $tag_quick_search = new Zend_View();
    $tag_quick_search->setScriptPath(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . "/includes/views/");
    $tag_quick_search->id ="unit-tag";
    $tag_quick_search->container_id = "unit-tag-container";
    $tag_quick_search->form_id = "add-unit-form";
    $tag_quick_search->admin = true;
    $tag_quick_search->objectives = $this->objectives;
    $tag_quick_search->translate = $translate;
    $tag_quick_search->title = "Unit Tags";
    $tag_quick_search->filter_label = "Curriculum Tags";
    $tag_quick_search->allowed_tag_set_ids = array_keys($this->allowed_tag_set_ids);
    echo $tag_quick_search->render("tag-quick-search.inc.php");

    if (defined("WEEK_OBJECTIVES_SHOW_LINKS") && WEEK_OBJECTIVES_SHOW_LINKS) {
        $link_objectives = new Zend_View();
        $link_objectives->setScriptPath(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . "/includes/views/");
        $link_objectives->translate = $translate;
        $link_objectives->version_id = $this->version_id;
        $link_objectives->cunit_id = isset($this->cunit_id) ? $this->cunit_id : null;
        $link_objectives->cperiod_id = isset($this->cperiod_id) ? $this->cperiod_id : null;
        $link_objectives->linked_objectives = $this->linked_objectives;
        $link_objectives->allowed_tag_set_ids = $this->allowed_tag_set_ids;
        $link_objectives->allowed_objective_ids = $this->allowed_linked_objective_ids;
        $link_objectives->list_container = "#unit-tag-container";
        echo $link_objectives->render("link-objectives.inc.php");

        $remove_objective = new Zend_View();
        $remove_objective->setScriptPath(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . "/includes/views/");
        $remove_objective->translate = $translate;
        $remove_objective->cunit_id = isset($this->cunit_id) ? $this->cunit_id : null;
        $remove_objective->list_container = "#unit-tag-container";
        $remove_objective->cperiod_id = isset($this->cperiod_id) ? $this->cperiod_id : null;
        echo $remove_objective->render("remove-objective.inc.php");
    }
    ?>

    <div class="row-fluid">
        <div class="pull-left">
            <input type="button" class="btn" value="<?php echo $translate->_("Cancel");?>" onclick="window.location='<?php echo ENTRADA_URL; ?>/admin/courses/units?id=<?php echo $this->course_id; ?>'" />
        </div>

        <div class="pull-right">
            <input type="submit" class="btn btn-primary" value="<?php echo $translate->_("Save");?>" />
        </div>
    </div>
</form>
