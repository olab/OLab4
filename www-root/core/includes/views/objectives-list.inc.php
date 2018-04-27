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

if (!isset($this)) {
    throw new Exception("You cannot visit this file directly because it is an include.");
}

?>

<?php foreach ($this->objectives as $objective): ?>
    <li class="objective-container">
        <div class="objective-title<?php echo (!$objective["has_links"] && $this->direction != "both") ? " objective-title-no-children" : " objective-title-children"; ?>"
             id="objective_title_<?php echo $objective["objective_id"]; ?>"
             data-title="<?php echo get_objective_text($objective); ?>"
             data-id="<?php echo $objective["objective_id"]; ?>"
             data-code="<?php echo $objective["objective_code"]; ?>"
             data-name="<?php echo $objective["objective_name"]; ?>"
             <?php if ($this->direction == "both"): ?>
                 data-has-children="true"
             <?php else: ?>
                 data-has-children="<?php echo $objective["has_links"]; ?>"
             <?php endif; ?>
             data-direction="<?php echo $this->direction; ?>"
             <?php if (isset($this->event_id)): ?>
                 data-event-id="<?php echo (int) $this->event_id; ?>"
             <?php endif; ?>
             <?php if (isset($this->cunit_id)): ?>
                 data-course-unit-id="<?php echo (int) $this->cunit_id; ?>"
             <?php endif; ?>
             <?php if (isset($this->course_id)): ?>
                 data-course-id="<?php echo (int) $this->course_id; ?>"
             <?php endif; ?>
             <?php if (isset($this->cperiod_id)): ?>
                 data-curriculum-period-id="<?php echo (int) $this->cperiod_id; ?>"
             <?php endif; ?>
             data-description="<?php echo $objective["objective_description"]; ?>">
            <strong><?php echo ($title = get_objective_text($objective)); ?></strong>
        </div>
        <div class="objective-description content-small">
            <?php if (trim($objective["objective_description"]) && $title == $objective["objective_name"]): ?>
                <?php echo $objective["objective_description"]; ?>
            <?php endif; ?>
        </div>
        <div class="objective-children" id="children_<?php echo $objective["objective_id"]; ?>">
            <ul class="objective-list" id="objective_list_<?php echo $objective["objective_id"]; ?>"></ul>
        </div>
        <div id="objective_controls_<?php echo $objective["objective_id"]; ?>" class="objective-controls"></div>
    </li>
<?php endforeach; ?>
