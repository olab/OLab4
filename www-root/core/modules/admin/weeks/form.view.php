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

if ($this->mode == "add" || $this->mode == "edit") {
    ?>
    <form class="form-horizontal" action="<?php echo ENTRADA_URL; ?>/admin/weeks?section=<?php echo $this->mode; ?>&step=2" method="POST">
        <?php if (isset($this->week)): ?>
            <input type="hidden" name="id" value="<?php echo $this->week->getID(); ?>" />
        <?php endif; ?>
        <div class="control-group">
            <label for="week_title" class="form-required control-label"><?php echo $this->translate->_("Week Title"); ?></label>
            <div class="controls">
                <?php if (isset($this->week)): ?>
                    <input type="text" id="week_title" name="week_title" value="<?php echo html_encode($this->week->getWeekTitle()); ?>" maxlength="85" class="span7">
                <?php else: ?>
                    <input type="text" id="week_title" name="week_title" value="" maxlength="85" class="span7">
                <?php endif; ?>
            </div>
        </div>
        <div class="control-group">
            <label for="curriculum_type_id" class="control-label form-required"><?php echo $this->translate->_("Curriculum Category"); ?></label>
            <div class="controls">
                <select id="curriculum_type_id" name="curriculum_type_id" style="width: 250px">
                    <?php if (!isset($this->week)): ?>
                        <option value="0" selected="selected">- <?php echo $this->translate->_("Select Curriculum Category"); ?> -</option>
                    <?php else: ?>
                        <option value="0">- <?php echo $this->translate->_("Select Curriculum Category"); ?> -</option>
                    <?php endif; ?>
                    <?php foreach ($this->curriculum_types as $curriculum_type): ?>
                        <?php if (isset($this->week) && ($curriculum_type->getID() == $this->week->getCurriculumTypeId())): ?>
                            <option value="<?php echo (int) $curriculum_type->getID(); ?>" selected="selected">
                                <?php echo html_encode($curriculum_type->getCurriculumTypeName()); ?>
                            </option>
                        <?php else: ?>
                            <option value="<?php echo (int) $curriculum_type->getID(); ?>">
                                <?php echo html_encode($curriculum_type->getCurriculumTypeName()); ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="control-group">
            <label for="week_order" class="form-required control-label"><?php echo $this->translate->_("Week Order"); ?></label>
            <div class="controls">
                <?php if (isset($this->week)): ?>
                    <input type="text" id="week_order" name="week_order" value="<?php echo $this->week->getWeekOrder(); ?>" class="span7">
                <?php else: ?>
                    <input type="text" id="week_order" name="week_order" value="0" class="span7">
                <?php endif; ?>
            </div>
        </div>
        <div class="btn-group pull-right">
            <input type="submit" class="btn btn-primary" value="<?php echo $this->translate->_("Save"); ?>" />
        </div>
    </form>
    <?php
} else {
    throw new InvalidArgumentException();
}
