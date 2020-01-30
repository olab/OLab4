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
 * A view for rendering CBME navigation
 *
 * @author Organization: Queen's University.
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

class Views_CBME_NavigationTabs extends Views_HTML {
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

        if (!isset($options["pinned_view"])) {
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
        <ul class="nav nav-tabs pull-left">
            <li class="<?php echo ($options["active_tab"] == "stages" && !$options["pinned_view"] ? "active" : "") ?>"><a href="<?php echo html_encode($options["navigation_urls"]["stages"]) ?>"><?php echo $translate->_("Stages") ?></a></li>
            <li class="<?php echo ($options["active_tab"] == "assessments" && !$options["pinned_view"] ? "active" : "") ?> inline-block"><a class="inline-block" href="<?php echo html_encode($options["navigation_urls"]["assessments"]) ?>"><?php echo $translate->_("Assessments") ?></a><a class="inline-block unread-assessment-link" title="<?php echo $translate->_("View Unread Assessments"); ?>" data-toggle="tooltip" href="<?php echo html_encode($options["navigation_urls"]["unread_assessments"]) ?>"><span class="badge no-margin unread-assessment-count"><?php echo $options["unread_assessment_count"]; ?></span></a></li>
            <li class="<?php echo ($options["active_tab"] == "items" && !$options["pinned_view"] ? "active" : "") ?>"><a href="<?php echo html_encode($options["navigation_urls"]["items"]) ?>"><?php echo $translate->_("Assessments Items") ?></a></li>
            <li class="<?php echo ($options["active_tab"] == "trends" && !$options["pinned_view"] ? "active" : "") ?>"><a href="<?php echo html_encode($options["navigation_urls"]["trends"]) ?>"><?php echo $translate->_("Trends") ?></a></li>
            <li class="<?php echo ($options["active_tab"] == "comments" && !$options["pinned_view"] ? "active" : "") ?>"><a href="<?php echo html_encode($options["navigation_urls"]["comments"]) ?>"><?php echo $translate->_("Comments") ?></a></li>
            <li class="<?php echo ($options["active_tab"] == "pinned" || $options["pinned_view"] ? "active" : "") ?>"><a href="<?php echo html_encode($options["navigation_urls"]["assessment_pins"]) ?>"><?php echo $translate->_("Pins") ?></a></li>
            <li class="hide <?php echo ($options["active_tab"] == "stats" && !$options["pinned_view"] ? "active" : "") ?>"><a href="#"><?php echo $translate->_("Stats") ?></a></li>
        </ul>
        <div class="clearfix"></div>
    <?php
    }

    /**
     *
     * Adds required CSS and JS files to the $HEAD array and adds entry to the $BREADCRUMB array for this view.
     */
    protected function renderHead () {
        global $HEAD;
        $HEAD[] = "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . ENTRADA_URL . "/css/cbme/cbme.css?release=" . html_encode(APPLICATION_VERSION) . "\" />";
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