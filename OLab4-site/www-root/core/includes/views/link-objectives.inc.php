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
 * @author Organisation: The University of British Columbia
 * @author Unit: MedIT - Faculty of Medicine
 * @author Developer: Carlos Torchia <carlos.torchia@ubc.ca>
 * @copyright Copyright 2016 The University of British Columbia. All Rights Reserved.
 *
 */

if (!isset($this)) {
    throw new Exception("You cannot visit this file directly.");
}

$translate = $this->translate;

?>
<script type="text/javascript">
    jQuery(function() {
        var link_objectives_modal = jQuery("#link-objectives");
        var objective_container = jQuery("<?php echo $this->list_container; ?>");
        var allowed_tag_set_ids = <?php echo json_encode(isset($this->allowed_tag_set_ids) ? $this->allowed_tag_set_ids : array()); ?>;
        var exclude_tag_set_ids = <?php echo json_encode(isset($this->exclude_tag_set_ids) ? array_values($this->exclude_tag_set_ids) : array()); ?>;
        var allowed_objective_ids = <?php echo json_encode(isset($this->allowed_objective_ids) ? $this->allowed_objective_ids : array()); ?>;
        var context = {
            'event_id': <?php echo isset($this->event_id) ? json_encode($this->event_id) : "undefined"; ?>,
            'cunit_id': <?php echo isset($this->cunit_id) ? json_encode($this->cunit_id) : "undefined"; ?>,
            'course_id': <?php echo isset($this->course_id) ? json_encode($this->course_id) : "undefined"; ?>,
            'cperiod_id': <?php echo isset($this->cperiod_id) ? json_encode($this->cperiod_id) : "undefined"; ?>
        };
        link_objectives_view(link_objectives_modal, objective_container, allowed_tag_set_ids, exclude_tag_set_ids, allowed_objective_ids, context);
    });
</script>

<?php if (isset($this->version_id)): ?>
    <input id="version_id" type="hidden" name="version_id" value="<?php echo $this->version_id; ?>"/>
<?php endif; ?>

<span id="linked-objective-controls">
    <?php foreach ($this->linked_objectives as $objective_id => $target_objectives): ?>
        <?php foreach ($target_objectives as $target_objective_id => $target_objective): ?>
            <input
                type="hidden"
                name="linked_objectives[<?php echo $objective_id; ?>][<?php echo $target_objective_id; ?>]"
                value="<?php echo $target_objective_id; ?>"
                data-id="<?php echo $objective_id; ?>"
                data-target-id="<?php echo $target_objective_id; ?>"
                data-text="<?php echo $target_objective->getObjectiveText(true); ?>"/>
        <?php endforeach; ?>
    <?php endforeach; ?>
</span>

<div id="link-objectives" class="hide">
    <h2 id="source-objective-text"></h2>

    <div><ul id="linked-objectives"></ul></div>

    <div id="link-objective-search-container" class="control-group">
        <label for="link-objective-button" class="control-label"><?php echo $translate->_("Select Curriculum Tag to Link"); ?>:</label>
    </div>

    <h3 id="all-linked-objectives-title"><?php echo $translate->_("Elsewhere Linked to:"); ?></h3>
    <div><ul id="all-linked-objectives"></ul></div>
</div>
