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
 * A view for rendering navigation tabs in the assessments page.
 *
 * @author Organization: Queen's University.
 * @author Developer: Alex Ash <aa121@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_CBME_AssessmentNavigationTabs extends Views_HTML {
    /**
     * Validate: ensure all attributes that the view requires are available to the renderView function
     *
     * @param array $options
     * @return bool
     */
    protected function validateOptions($options = array()) {
        if (!isset($options["active_tab"])) {
            return false;
        }

        if (!isset($options["proxy_id"])) {
            return false;
        }

        return true;
    }

    /**
     * Render the Stage assessments view.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate; ?>
        <ul class="nav nav-tabs" id="my-tab">
            <li class="<?php echo ($options["active_tab"] == "completed_assessments" ? "active" : "") ?>"><a href="<?php echo html_encode($options["navigation_urls"]["completed_assessments"]) ?>" id="completed-assessment-tab"><?php echo $translate->_("Completed"); ?></a></li>
            <li class="<?php echo ($options["active_tab"] == "inprogress" ? "active" : "") ?>"><a href="<?php echo html_encode($options["navigation_urls"]["inprogress_assessments"]) ?>" id="inprogress-assessment-tab"><?php echo $translate->_("In Progress"); ?></a></li>
            <li class="<?php echo ($options["active_tab"] == "pending" ? "active" : "") ?>"><a href="<?php echo html_encode($options["navigation_urls"]["pending_assessments"]) ?>" id="pending-assessment-tab"><?php echo $translate->_("Pending"); ?></a></li>
            <li class="<?php echo ($options["active_tab"] == "deleted" ? "active" : "") ?>"><a href="<?php echo html_encode($options["navigation_urls"]["deleted_assessments"]) ?>" id="pinned-comment-tab"><?php echo $translate->_("Deleted"); ?></a></li>
        </ul>
        <?php
    }

    /**
     * Render a custom error message for this view.
     */
    protected function renderError() {
        global $translate;?>
        <div class="alert alert-danger">
            <strong><?php echo $translate->_("Unable to render CBME navigation"); ?></strong>
        </div>
        <?php
    }
}