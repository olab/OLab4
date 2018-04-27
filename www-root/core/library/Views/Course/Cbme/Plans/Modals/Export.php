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
 * View class for rendering the assessment plan exporter
 *
 * @author Organization: Queen's University.
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
class Views_Course_Cbme_Modals_ExportPlan extends Views_HTML {
    /**
     * Validate: ensure all attributes that the view requires are available to the renderView function
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        return true;
    }

    /**
     * Render the EPA editor
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate; ?>
        <div class="modal fade" id="export-assessment-plan-modal" style="display:none">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h3><?php echo $translate->_("Export Assessment Plan"); ?></h3>
            </div>
            <div class="modal-body">
                <form class="form-horizontal">
                <?php foreach ($options["objectives"] as $objective) : ?>
                    <label class="checkbox">
                        <input type="checkbox" name="export_objectives[]" value="<?php echo $objective["objective_id"]?>" />
                        <?php echo html_encode($objective["objective_name"]) ?>
                    </label>
                <?php endforeach; ?>
                <input type="submit" value="<?php echo $translate->_("Export Assessment Plan") ?>">
                </form>
            </div>
            <div class="modal-footer">
                <div class="row-fluid procedure-uploaded-criteria-modal-btns">
                    <a href="#" class="btn btn-default pull-left" data-dismiss="modal"><?php echo $translate->_("Cancel"); ?></a>
                    <button id="export-assessment-plan-btn" type="submit" class="btn btn-primary pull-right"><?php echo $translate->_("Export Assessment Plan") ?></button>
                </div>
            </div>
        </div>
    <?php
    }
}