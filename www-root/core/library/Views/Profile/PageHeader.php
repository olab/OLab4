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
 * Generates the page header to Gradebook pages
 *
 * @author Organization: bitHeads, Inc.
 * @author Developer: Eugene Bivol <ebivol@gmail.com>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */

class Views_Profile_PageHeader extends Views_HTML {
    protected $page_title;

    /**
     * Get page title
     * @return string
     */
    public function getPageTitle() {
        return $this->page_title;
    }

    /**
     * Returns the output  of profile notification subnavigation
     * @return string html
     */
    public static function getCoursesSubnavigation($tab) {
        global $ENTRADA_ACL;

        $output = "";
        $output .= "<div class=\"no-printing\">\n";
        $output .= "    <ul class=\"nav nav-tabs\">\n";
        if($ENTRADA_ACL->isLoggedInAllowed('profile', 'read')) {
            $output .=  "<li".($tab=="activenotifications"?" class=\"active\"":"")." style=\"width:25%;\"><a href=\"".ENTRADA_RELATIVE."/profile?section=activenotifications\" >Active Notifications</a></li>\n";
        }
        if($ENTRADA_ACL->isLoggedInAllowed('profile', 'read')) {
            $output .=  "<li".($tab=="communitynotifications"?" class=\"active\"":"")." style=\"width:25%;\"><a href=\"".ENTRADA_RELATIVE."/profile?section=communitynotifications\" >Community Notifications</a></li>\n";
        }
        $output .=  "	</ul>\n";
        $output .=  "</div>\n";

        return $output;
    }

}