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
 * A view for rendering the CBME assessment card header
 *
 * @author Organization: Queen's University.
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_CBME_Card_Header extends Views_HTML {
    /**
     * Validate: ensure all attributes that the view requires are available to the renderView function
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("form_type", "title", "updated_date", "card_type", "encounter_date"));
    }

    /**
     * Render the Stage assessments view.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate; ?>
            <div class="list-card-header">
                <div class="list-card-title"><?php echo html_encode($options["title"]) ?></div>
                <?php if ($options["card_type"] == "pending") : ?>
                    <div class="list-card-label"><?php echo html_encode($options["assessment_created_date"]); ?></div>
                <?php endif; ?>
                <?php if ($options["card_type"] == "deleted") : ?>
                    <div class="list-card-label">
                        <?php echo html_encode($options["deleted_date"]); ?>
                    </div>
                    <?php if ($options["deleted_by"]) : ?>
                        <div class="list-card-label small-space-right">
                            <?php echo sprintf($translate->_("Deleted by %s |"), $options["deleted_by"]); ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ($options["card_type"] == "completed" || $options["card_type"] == "inprogress" || $options["card_type"] == "assessment") : ?>
                    <div class="list-card-label"><?php echo html_encode($options["encounter_date"] ? $options["encounter_date"] : (is_null($options["updated_date"]) ? $options["created_date"] : $options["updated_date"])) ?></div>
                <?php endif; ?>
            </div>
        <?php
    }

    /**
     * Render a custom error message for this view.
     */
    protected function renderError() {
        global $translate;?>
        <div class="alert alert-danger">
            <strong><?php echo $translate->_("Unable to render CBME assessment card header"); ?></strong>
        </div>
        <?php
    }
}