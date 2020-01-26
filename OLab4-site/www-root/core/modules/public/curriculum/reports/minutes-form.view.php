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

?>
<style>
h1,h2 {
    page-break-before:    always;
}
</style>

<h1><?php echo $this->translate->_("Curriculum Tag Minutes and Mapping Report"); ?></h1>

<div class="no-printing">
    <h2><?php echo $this->translate->_("Report Options"); ?></h2>

    <form action="<?php echo ENTRADA_RELATIVE; ?>/curriculum/reports/minutes?step=2" method="post" onsubmit="sel_course(); sel_group_by_tag_set();" class="form-horizontal">
        <input type="hidden" id="organisation_id" name="organisation_id" value="<?php echo $this->organisation_id; ?>">

        <?php echo Entrada_Utilities::generate_calendars("reporting", "Reporting Date", true, true, $this->reporting_start, true, true, $this->reporting_finish); ?>

        <?php Views_UI_PickList::render(
                  "course",
                  "course_ids",
                  "Courses Included:",
                  $this->course_list,
                  $this->course_ids,
                  function ($course) { return $course; }); ?>
        <div class="control-group">
            <label for="main_tag_set_id" class="control-label form-required">Curriculum Tag Set</label>
            <div class="controls">
                <select id="main_tag_set_id" name="main_tag_set_id" style="width: 375px">
                    <?php foreach ($this->tag_sets as $objective): ?>
                        <?php if ($this->main_tag_set_id == $objective["objective_id"]): ?>
                            <option value="<?php echo (int) $objective["objective_id"]; ?>" selected="selected"><?php echo html_encode($objective["objective_name"]); ?></option>
                        <?php else: ?>
                            <option value="<?php echo (int) $objective["objective_id"]; ?>"><?php echo html_encode($objective["objective_name"]); ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <?php Views_UI_PickList::render(
                  "group_by_tag_set",
                  "group_by_tag_set_ids",
                  "Group By Additional Curriculum Tag Set(s)",
                  $this->group_by_tag_sets,
                  $this->group_by_tag_set_ids,
                  function ($objective) {
                      return $objective["objective_name"];
                  }); ?>
        <div class="control-group">
            <label class="form-nrequired control-label">Filter Tag Set</label>
            <div class="controls">
                <select id="filter_tag_set_id" name="filter_tag_set_id" style="width: 375px">
                    <option value=""></option>
                    <?php foreach ($this->tag_sets as $objective): ?>
                        <?php if ($this->filter_tag_set_id == $objective["objective_id"]): ?>
                            <option value="<?php echo (int) $objective["objective_id"]; ?>" selected="selected"><?php echo html_encode($objective["objective_name"]); ?></option>
                        <?php else: ?>
                            <option value="<?php echo (int) $objective["objective_id"]; ?>"><?php echo html_encode($objective["objective_name"]); ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div id="filter-objective-control-group" class="control-group"<?php if (!$this->filter_objective_id): ?> style="display:none"<?php endif; ?>>
            <label class="form-nrequired control-label">Filter Tag</label>
            <div class="controls">
                <select id="filter_objective_id" name="filter_objective_id" style="width: 177px">
                    <?php if (!empty($this->filter_objectives)): ?>
                        <?php foreach ($this->filter_objectives as $objective): ?>
                            <?php if ($this->filter_objective_id == $objective["objective_id"]): ?>
                                <option value="<?php echo (int) $objective["objective_id"]; ?>" selected="selected"><?php echo html_encode($objective["objective_name"]); ?></option>
                            <?php else: ?>
                                <option value="<?php echo (int) $objective["objective_id"]; ?>"><?php echo html_encode($objective["objective_name"]); ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
        </div>
        <?php if (!empty($this->filter_weeks)) : ?>
        <div id="filter-week-control-group" class="control-group">
            <label class="form-nrequired control-label"><?php echo $this->translate->_("Filter Week"); ?></label>
            <div class="controls">
                <select id="filter_week_id" name="filter_week_id" style="width: 177px">
                    <option value=""<?php echo $this->filter_week_id ? "" : "selected=\"selected\""; ?>>- <?php echo $this->translate->_("Select week"); ?> -</option>
                    <?php foreach ($this->filter_weeks as $week): ?>
                        <option value="<?php echo (int) $week->getID(); ?>"<?php echo ($this->filter_week_id == $week->getID()) ? "selected=\"selected\"" : ""; ?>><?php echo html_encode($week->getWeekTitle()); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <?php endif; ?>
        <script type="text/javascript">
            function updateFilterObjectiveSelect(ENTRADA_RELATIVE, tag_set_id, filter_objective_id, organisation_id) {
                var select = jQuery('#filter_objective_id');
                select.empty();
                if (tag_set_id) {
                    jQuery('#filter-objective-control-group').show();
                    jQuery.ajax({
                        url: ENTRADA_RELATIVE + '/api/curriculum-tags.api.php',
                        type: 'get',
                        data: 'method=get-objectives-by-tag-set-id&tag_set_id=' + tag_set_id + '&organisation_id=' + organisation_id,
                        success: function(data) {
                            var objectives = JSON.parse(data);
                            jQuery.each(objectives, function (objective_id, objective) {
                                var option = jQuery('<option>', {
                                    value: objective_id,
                                    text: objective['objective_name']
                                });
                                if (filter_objective_id) {
                                    if (objective_id == filter_objective_id) {
                                        option.attr('selected', true);
                                    }
                                }
                                select.append(option);
                            });
                        }
                    });
                } else {
                    jQuery('#filter-objective-control-group').hide();
                }
            }
            jQuery(document).ready(function() {
                var ENTRADA_RELATIVE = '<?php echo ENTRADA_RELATIVE; ?>';
                var filter_tag_set_id = <?php echo json_encode($this->filter_tag_set_id); ?>;
                var filter_objective_id = <?php echo json_encode($this->filter_objective_id); ?>;
                var organisation_id = <?php echo $this->organisation_id; ?>;
                jQuery('#filter_tag_set_id').change(function() {
                    var tag_set_id = jQuery('#filter_tag_set_id').val();
                    updateFilterObjectiveSelect(ENTRADA_RELATIVE, tag_set_id, filter_objective_id, organisation_id);
                });
            });
        </script>
        <div class="control-group">
            <div class="controls">
                <label class="checkbox">
                    <?php if ($this->report_on_event_types) : ?>
                    <input name="report_on_event_types" type="checkbox" checked="checked">
                    <?php else : ?>
                    <input name="report_on_event_types" type="checkbox">
                    <?php endif; ?>
                    <?php echo $this->translate->_("Report on Learning Event Types"); ?>
                </label>

                <label class="checkbox">
                    <?php if ($this->report_on_mappings) : ?>
                    <input name="report_on_mappings" type="checkbox" checked="checked">
                    <?php else : ?>
                    <input name="report_on_mappings" type="checkbox"></input>
                    <?php endif; ?>
                    <?php echo $this->translate->_("Report on number of mappings"); ?>
                </label>

                <label class="checkbox">
                    <?php if ($this->report_on_percentages) : ?>
                    <input name="report_on_percentages" type="checkbox" checked="checked">
                    <?php else : ?>
                    <input name="report_on_percentages" type="checkbox">
                    <?php endif; ?>
                    <?php echo $this->translate->_("Report on percentages"); ?>
                </label>

                <label class="checkbox">
                    <?php if ($this->show_graph) : ?>
                    <input name="show_graph" type="checkbox" checked="checked">
                    <?php else : ?>
                    <input name="show_graph" type="checkbox">
                    <?php endif; ?>
                    <?php echo $this->translate->_("Show pie charts"); ?>
                </label>

                <label class="checkbox">
                    <input name="export_csv" type="checkbox" <?php echo ($this->export_csv === true ? "checked" : "") ?>>
                    <?php echo $this->translate->_("Export CSV"); ?>
                </label>
            </div>
        </div>
        <div class="pull-right">
            <input type="submit" class="btn btn-primary" value="<?php echo $this->translate->_("Create Report"); ?>" />
        </div>
    </form>
</div>
