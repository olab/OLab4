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
 * View class for assessment form form information edit contorls.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Forms_Sections_ScaleInformation extends Views_Assessments_Forms_Sections_Base {

    protected function renderView($options = array()) {
        global $translate;

        $api_url            = $options["api_url"];
        $scale_id           = $options["rating_scale_id"];
        $scale_type         = $options["scale_type"];
        $rating_scale_types = $options["rating_scale_types"];
        $scale_title        = $options["scale_title"];
        $scale_description  = $options["scale_description"];
        $authors            = $options["authors"];
        $read_only          = $options["read_only"];

        $disabled_text = $read_only ? "disabled" : "";
        ?>
        <input type="hidden" name="rating_scale_id" value="<?php echo $scale_id; ?>" />
        <h2><?php echo $translate->_("Rating Scale Information"); ?></h2>
        <div class="control-group">
            <label class="control-label form-required" for="title"><?php echo $translate->_("Rating Scale Title"); ?></label>
            <div class="controls">
                <input class="span11 <?php echo $disabled_text?>" <?php echo $disabled_text?> type="text" name="title" id="title" value="<?php echo $scale_title; ?>"/>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="description"><?php echo $translate->_("Rating Scale Description"); ?></label>
            <div class="controls">
                <textarea id="description" name="description" class="expandable span11 <?php echo $disabled_text?>" <?php echo $disabled_text?>><?php echo $scale_description; ?></textarea>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label" for="rating_scale_type"><?php echo $translate->_("Rating Scale Type"); ?></label>
            <div class="controls">
                <select id="rating_scale_type" name="rating_scale_type" class="<?php echo $disabled_text?>" <?php echo $disabled_text?>>
                    <option value="0"><?php echo $translate->_("Default"); ?></option>
                    <?php foreach($rating_scale_types as $rating_scale_type ): ?>
                        <option value="<?php echo $rating_scale_type->getID(); ?>" <?php echo ($rating_scale_type->getID() == $scale_type) ? "selected" : ""; ?>><?php echo html_encode($rating_scale_type->getTitle()); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <?php
        if ($scale_id): ?>
            <div class="control-group">
                <label class="control-label"
                       for="item-permissions"><?php echo $translate->_('Scale Permissions'); ?></label>

                <div class="controls">
                    <input type="text" class="form-control search" name="contact_select"  placeholder="<?php echo $translate->_("Type to search ..."); ?>" id="contact-selector"/>
                    <script>
                        jQuery(function ($) {
                            $("#contact-selector").audienceSelector({
                                    "filter": "#contact-type",
                                    "target": "#author-list",
                                    "content_type": "item-author",
                                    "content_target": "<?php echo $scale_id; ?>",
                                    "api_url": "<?php echo $api_url;?>",
                                    "delete_attr": "data-aiauthor-id"
                                }
                            );
                        });
                    </script>
                    <select class="span5" name="contact_type" id="contact-type">
                        <option value="proxy_id"><?php echo $translate->_("Individual"); ?></option>
                        <option value="organisation_id"><?php echo $translate->_("Organisation"); ?></option>
                        <option value="course_id"><?php echo $translate->_("Course"); ?></option>
                    </select>
                    <?php if (!empty($authors)): ?>
                        <ul class="unstyled" id="author-list">
                            <?php foreach ($authors as $author): ?>
                                <li>
                                    <a href="#" class="remove-permission" data-aiauthor-id="<?php echo $author->getID(); ?>">
                                        <i class="icon-remove-circle"></i>
                                    </a>
                                    <?php echo $author->getAuthorName(); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif;
    }
}