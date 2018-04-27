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
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
class Views_Assessments_Sidebar_Assessor extends Views_HTML {

    /**
     * Validate required assessor data.
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        return $this->validateIsSet($options, array("assessor_full_name"));
    }

    /**
     * Render the sidebar target.
     *
     * @param $options
     */
    protected function renderView($options = array()) {
        global $translate;

        $assessor_full_name     = $options["assessor_full_name"];
        $assessor_email         = array_key_exists("assessor_email", $options) ? $options["assessor_email"] : null;
        $assessor_image_uri     = array_key_exists("assessor_image_uri", $options) ? $options["assessor_image_uri"] : null;
        $assessor_group         = array_key_exists("assessor_group", $options) ? $options["assessor_group"] : null;
        $assessor_organisation  = array_key_exists("assessor_organisation", $options) ? $options["assessor_organisation"] : null;
        ?>
        <div id="assessor-card" class="match-height">
            <h3 class="heading"><?php echo $translate->_("Assessor"); ?></h3>
            <?php if ($assessor_image_uri): ?>
                <div class="user-image">
                    <img src="<?php echo $assessor_image_uri; ?>">
                </div>
            <?php endif; ?>
            <div class="user-metadata">
                <p class="user-fullname"><?php echo html_encode($assessor_full_name); ?></p>
                <?php if ($assessor_group && $assessor_organisation): ?>
                    <p class="user-organisation"><?php echo html_encode($assessor_group) ?> <span>•</span> <?php echo html_encode($assessor_organisation) ?></p>
                <?php elseif ($assessor_group): ?>
                    <p class="user-organisation"><?php echo html_encode($assessor_group) ?></p>
                <?php elseif($assessor_organisation): ?>
                    <p class="user-organisation"><?php echo html_encode($assessor_organisation) ?></p>
                <?php endif; ?>
                <?php if ($assessor_email): ?>
                    <div class="email-wrapper">
                        <a class="user-email" href="#"><?php echo html_encode($assessor_email) ?></a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}