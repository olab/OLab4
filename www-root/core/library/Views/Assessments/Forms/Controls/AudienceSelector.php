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
 * View class for rendering preview dialog on form.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Forms_Controls_AudienceSelector extends Views_Assessments_Forms_Controls_Base {

    protected function validateOptions($options = array()) {
        if (!$this->validateIsSet($options, array("related-data-key"))) {
            return false;
        }
        if (!$this->validateIsSet($options, array("authors"))) {
            return false;
        }
        return true;
    }

    /**
     * Render view
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;
        $authors = $options["authors"];
        $related_data_key = $options["related-data-key"];
        ?>
        <div class="control-group">
            <label class="control-label"><?php echo $translate->_("Permissions"); ?></label>
            <div class="controls">
                <input type="text" name="contact_select" id="contact-selector"/>
                <select name="contact_type" id="contact-type" class="span3">
                    <?php // TODO: Should we use settings table to derive these? ?>
                    <option value="proxy_id"><?php echo $translate->_("Individual"); ?></option>
                    <option value="organisation_id"><?php echo $translate->_("Organisation"); ?></option>
                    <option value="course_id"><?php echo $translate->_("Course"); ?></option>
                </select>
                <?php if (!empty($authors)): ?>
                    <ul class="unstyled" id="author-list">
                        <?php foreach ($authors as $author): ?>
                            <li>
                                <a href="#" <?php echo $related_data_key; ?>="<?php echo $author->getID(); ?>" class="remove-permission">
                                    <i class="icon-remove-circle"></i>
                                </a>
                                <?php echo $author->getAuthorName(); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}