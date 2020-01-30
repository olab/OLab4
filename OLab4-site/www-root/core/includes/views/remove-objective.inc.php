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
        var remove_objective_modal = jQuery("#remove-objective");
        var objective_container = jQuery("<?php echo $this->list_container; ?>");
        var context = {
            'event_id': <?php echo isset($this->event_id) ? json_encode($this->event_id) : "undefined"; ?>,
            'cunit_id': <?php echo isset($this->cunit_id) ? json_encode($this->cunit_id) : "undefined"; ?>,
            'course_id': <?php echo isset($this->course_id) ? json_encode($this->course_id) : "undefined"; ?>,
            'cperiod_id': <?php echo isset($this->cperiod_id) ? json_encode($this->cperiod_id) : "undefined"; ?>
        };
        remove_objective_view(remove_objective_modal, objective_container, context);
    });
</script>

<div id="remove-objective" class="hide">
    <h2 id="source-objective-text"></h2>

    <p><?php echo $translate->_("Links to and from the following objectives will be deleted."); ?></p>

    <h3 id="to-objectives-title"><?php echo $translate->_("Objectives linked to"); ?></h3>
    <div><ul id="to-objectives"></ul></div>

    <h3 id="from-objectives-title"><?php echo $translate->_("Objectives linked from"); ?></h3>
    <div><ul id="from-objectives"></ul></div>
</div>
