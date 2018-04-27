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
 * Renders an assessment portfolio folder
 *
 * @author Organization: Elson S. Floyd College of Medicine
 * @author Developer: Sean Girard <sean.girard@wsu.edu>
 * @copyright Copyright 2017 Washington State University. All Rights Reserved.
 *
 */
class Views_Gradebook_Assessments_Portfolio_Menu extends Views_Gradebook_Base {

    protected $portfolio_id, $proxy_id, $menu_options;

    /**
     * Renders a menu of portfolio folders
     * @param array $options
     * @return string html
     */
    protected function renderView($options = array()) {
        global $translate;
        $html = [];

        if ($this->portfolio_id && $this->proxy_id) {

            $portfolio = Models_Eportfolio::fetchRow($this->portfolio_id);


            $folders = $portfolio->getFolders();
            if ($folders) {
                $html[] = '<div class="btn-group">';
                $html[] = '<button class="btn btn-info btn-large dropdown-toggle" data-toggle="dropdown">';
                $html[] = '<i class="fa fa-folder-open"></i>';
                $html[] = '<span id="gradebook-assessment-portfolio-folder-title">'.$translate->_("Choose a portfolio folder").'&hellip;</span>';
                $html[] = '<span class="caret"></span>';
                $html[] = '</button>';
                $html[] = '<ul class="dropdown-menu" id="">';
                foreach ($folders as $folder) {
                    $html[] = '<li class="text-left">';
                    $html[] = '<a href="#"
                               data-target="#gradebook-assessment-portfolio-folder-' . $folder->getID() . '" data-id="' . $folder->getID() . '"
                               data-parent="#gradebook-assessment-portfolio-container"
                               data-toggle="collapse">';
                    $html[] = '<i class="fa fa-folder-open-o"></i></i>';
                    $html[] = '<span>' . $folder->getTitle() . '</span>';
                    $html[] = '</a>';
                    $html[] = '</li>';
                }
                $html[] = '</ul>';
                $html[] = '</div>'; //.btn-group
            }

            echo implode(PHP_EOL, $html);
        }
    }
}
