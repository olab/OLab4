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
 * Render the stateful "Back to ..." button, to render on forms using a referrer.
 *
 * Referrer types can be form, rubric, or null.
 *
 * @author Organization: Queen's University.
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Assessments_Forms_Controls_BackToReferrerButton extends Views_Assessments_Forms_Controls_Base {

    /**
     * Render view.
     *
     * @param array $options
     */
    protected function renderView($options = array()) {
        global $translate;
        $referrer_url   = @$options["referrer_url"];
        $referrer_type  = @$options["referrer_type"];
        $css_classes    = @$options["css_classes"] ? $options["css_classes"] : "space-below pull-left";
        if ($referrer_url): ?>
            <a id="back-to-referrer-link" href="<?php echo $referrer_url ?>" class="btn <?php echo $css_classes ?>"><i class="icon-circle-arrow-left"></i> <?php
                switch ($referrer_type) {
                    case "form":
                        echo $translate->_("Back to Form");
                        break;
                    case "rubric":
                        echo $translate->_("Back to Grouped Item");
                        break;
                    default:
                        echo $translate->_("Back");
                        break;
                } ?>
            </a>
        <?php endif;
    }

}