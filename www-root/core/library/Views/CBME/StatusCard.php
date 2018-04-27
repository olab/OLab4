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
 * This view is for rendering the completion status of an EPA.
 *
 * @author Organization: Queen's University.
 * @author Developer: Alex Ash <aa121@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_CBME_StatusCard extends Views_HTML {

    /**
     * Validate: ensure all attributes that the view requires are available to the renderView function
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        return true;
    }

    /**
     * Render the status card view.
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;
        $objective = $options["objective"];
        ?>
        <div class="status-card">
            <div class="status-card-header">
                <?php if ($objective->getDeletedDate() == NULL) : ?>
                    <h3 class="inline-block no-margin"><?php echo $translate->_("Status changed to Completed"); ?></h3>
                    <i class="pull-right fa fa-check-circle-o list-item-status-complete large-icon inline-block"></i>
                <?php else : ?>
                    <h3 class="inline-block no-margin"><?php echo $translate->_("Status changed to In Progress"); ?></h3>
                    <i class="pull-right fa fa-circle-o list-item-status-incomplete large-icon inline-block"></i>
                <?php endif; ?>
            </div>
            <div class="status-card-body">
                <div class="user-avatar inline-block align-top">
                    <img src="<?php echo webservice_url("photo", array($objective->getProxyID(), 0))."/"; ?>" class="img-polaroid user-photo" alt=" <?php echo html_encode($_SESSION["details"]["firstname"] . " " . $_SESSION["details"]["lastname"]);?>" />
                </div>
                <div class="inline-block status-information">
                    <div class="status-information inline-block space-left">
                        <span><strong><?php echo $options["creator_name"]; ?></strong></span>
                        <?php if ($objective->getDeletedDate() != NULL) : ?>
                            <span class="muted space-left"><?php echo date("F j, Y g:ia", $objective->getDeletedDate()); ?></span>
                        <?php elseif ($objective->getCreatedDate() != NULL) : ?>
                            <span class="muted space-left"><?php echo date("F j, Y g:ia", $objective->getCreatedDate()); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="status-description">
                        <?php if ($objective->getDeletedReason()) : ?>
                            <span><?php echo $objective->getDeletedReason(); ?></span>
                        <?php elseif ($objective->getCreatedReason()) : ?>
                            <span><?php echo $objective->getCreatedReason(); ?></span>
                        <?php endif ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}