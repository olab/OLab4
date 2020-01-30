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

$get_objective_text = $this->get_objective_text;

?>

<?php if ($this->group_by_tag_set_ids): ?>
    <?php if ($this->report_on_mappings): ?>
        <?php if ($this->report_on_percentages): ?>
            <h2 style="page-break-before: avoid">Curriculum Tag Percentages of Mappings Report</h2>
        <?php else: ?>
            <h2 style="page-break-before: avoid">Curriculum Tag Mappings Report</h2>
        <?php endif; ?>
    <?php else: ?>
        <?php if ($this->report_on_percentages): ?>
            <h2 style="page-break-before: avoid">Curriculum Tag Percentages of Minutes Report</h2>
        <?php else: ?>
            <h2 style="page-break-before: avoid">Curriculum Tag Minutes Report</h2>
        <?php endif; ?>
    <?php endif; ?>
<?php else: ?>
    <?php if ($this->report_on_mappings): ?>
        <?php if ($this->report_on_percentages): ?>
            <h2 style="page-break-before: avoid">Curriculum Tag Minutes, Percentages, and Mappings Report</h2>
        <?php else: ?>
            <h2 style="page-break-before: avoid">Curriculum Tag Minutes and Mappings Report</h2>
        <?php endif; ?>
    <?php else: ?>
        <?php if ($this->report_on_percentages): ?>
            <h2 style="page-break-before: avoid">Curriculum Tag Minutes and Percentages Report</h2>
        <?php else: ?>
            <h2 style="page-break-before: avoid">Curriculum Tag Minutes Report</h2>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>

<?php if ($this->filter_objective_name): ?>
    <div class="content-small" style="margin-bottom: 10px">
        <strong>Filter Tag:</strong> <?php echo $this->filter_objective_name; ?>
    </div>
<?php endif; ?>

<?php if ($this->filter_week_id && isset($this->weeks[$this->filter_week_id])): ?>
    <div class="content-small" style="margin-bottom: 10px">
        <strong><?php echo $this->translate->_("Filter Week"); ?>:</strong> <?php echo $this->weeks[$this->filter_week_id]->getWeekTitle(); ?>
    </div>
<?php endif; ?>

<div class="content-small" style="margin-bottom: 10px">
    <strong>Date Range:</strong> <?php echo date(DEFAULT_DATE_FORMAT, $this->reporting_start); ?> <strong>to</strong> <?php echo date(DEFAULT_DATE_FORMAT, $this->reporting_finish); ?>.
</div>

<style type="text/css">
div.grid-container {
    max-height: 700px;
    overflow-x: auto;
    overflow-y: auto;
}
table.grid {
    min-width: 100%;
    background-color: #fff;
}

table.grid thead tr th {
    font-weight: 700;
    border-bottom: 2px #CCC solid;
    background-color: #f6f6f6;
    font-size: 12px;
}

table.grid tbody tr td {
    vertical-align:top;
    font-size: 11px;
    border-bottom: 1px #EEE solid;
}

table.grid tbody td:nth-child(1) {
    background: #f6f6f6;
}

table.grid tbody tr td a {
    font-size: 11px;
}

table.grid td.border-r {
    border-right: 1px #EEE solid;
}

table.grid td.objective-description a {
}

</style>
<?php if (count($this->output)): ?>
    <?php foreach ($this->output as $course_id => $data): ?>
        <h1><?php echo html_encode($this->course_list[$course_id]); ?></h1>
        <?php if ($this->show_graph): ?>
            <div style="text-align: center">
                <canvas id="graph_<?php echo $course_id; ?>" width="675" height="450"></canvas>
            </div>
            <script type="text/javascript">
            var options = {
               'IECanvasHTC': '<?php echo ENTRADA_RELATIVE; ?>/javascript/plotkit/iecanvas.htc',
               'yTickPrecision': 1,
               'xTicks': [<?php echo plotkit_statistics_lables($this->graph_labels[$course_id]); ?>]
            };

            var layout = new PlotKit.Layout('pie', options);
            layout.addDataset('results', [<?php echo plotkit_statistics_values($this->graph_values[$course_id]); ?>]);
            layout.evaluate();

            var canvas = MochiKit.DOM.getElement('graph_<?php echo $course_id; ?>');
            var plotter = new PlotKit.SweetCanvasRenderer(canvas, layout, options);
            plotter.render();
            </script>
        <?php endif; ?>
        <?php if ($this->group_by_tag_set_ids || $this->report_on_event_types): ?>
            <div class="grid-container">
                <table class="grid table" data-toggle="table" data-fixed-columns="true" data-fixed-number="1" cellspacing="0" summary="<?php echo html_encode($this->course_list[$course_id]); ?>">
                    <thead>
                        <tr>
                            <?php if ($this->report_on_event_types): ?>
                                <th class="border-r"><?php echo $this->translate->_("Event Type"); ?></th>
                            <?php endif; ?>
                            <?php foreach ($this->group_by_tag_sets_included[$course_id] as $group_by_tag_set): ?>
                                <th class="border-r"><?php echo $group_by_tag_set["objective_name"]; ?></th>
                            <?php endforeach; ?>
                            <?php if (!$this->report_on_mappings): ?>
                                <?php if ($this->report_on_percentages): ?>
                                    <th class="border-r">Total Percentage</th>
                                <?php else: ?>
                                    <th class="border-r">Total Minutes</th>
                                <?php endif; ?>
                            <?php else: ?>
                                <th class="border-r">Total Mappings</th>
                            <?php endif; ?>
                            <?php foreach ($this->objectives_included[$course_id] as $objective): ?>
                                <th class="border-r objective-description">
                                    <?php echo $get_objective_text($objective); ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $row): ?>
                            <tr>
                                <?php if ($this->report_on_event_types): ?>
                                    <?php if (isset($row["totals"]) && $row["totals"] === true): ?>
                                        <td class="border-r"><strong>Total</strong></td>
                                    <?php else: ?>
                                        <td class="border-r"><?php echo $row["event_type"]["eventtype_title"]; ?></td>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php if (isset($row["totals"]) && $row["totals"] === true): ?>
                                    <?php foreach ($this->group_by_tag_sets_included[$course_id] as $group_by_tag_set): ?>
                                        <td class="border-r"><strong>Total</strong></td>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <?php foreach ($row["group_objectives"] as $group_objective): ?>
                                        <td class="border-r objective-description">
                                            <?php echo $get_objective_text($group_objective); ?>
                                        </td>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <td class="border-r">
                                    <?php echo round(array_sum($row["values"]), 1); ?>
                                </td>
                                <?php foreach ($row["values"] as $value): ?>
                                    <td class="border-r"><?php echo round($value, 1); ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="grid-container">
                <table class="grid table" cellspacing="0" summary="<?php echo html_encode($this->course_list[$course_id]); ?>">
                    <colgroup>
                        <col style="width: 50%" />
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="border-r">Curriculum Tag</th>
                            <?php if ($this->report_on_mappings): ?>
                                <th class="border-r">Mappings</th>
                            <?php endif; ?>
                            <th class="border-r">Minutes</th>
                            <th class="border-r">Hours</th>
                            <?php if ($this->report_on_percentages): ?>
                                <th class="border-r">Percentage</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $objective): ?>
                            <tr>
                                <td class="border-r objective-description">
                                    <?php echo $get_objective_text($objective); ?>
                                </td>
                                <?php if ($this->report_on_mappings): ?>
                                    <td class="border-r"><?php echo isset($objective["number_of_mappings"]) ? (int) $objective["number_of_mappings"] : ""; ?></td>
                                <?php endif; ?>
                                <td class="border-r"><?php echo round($objective["duration"], 1); ?></td>
                                <td class="border-r"><?php echo round($objective["duration"] / 60.0, 1); ?></td>
                                <?php if ($this->report_on_percentages): ?>
                                    <td class="border-r"><?php echo round($objective["percentage"], 1); ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
<?php else: ?>
        <?php echo display_notice(array("There are no objectives linked to any events within the specified constraints.")); ?>
<?php endif; ?>
