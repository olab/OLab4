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
 * Renders an assessment portfolio
 * 
 * @author Organization: Elson S. Floyd College of Medicine
 * @author Developer: Sean Girard <sean.girard@wsu.edu>
 * @copyright Copyright 2017 Washington State University. All Rights Reserved.
 * 
 */
class Views_Gradebook_Assessments_Portfolio extends Views_Gradebook_Base {

	protected $portfolio_id, $proxy_id, $portfolio_options, $menu_class = "Views_Gradebook_Assessments_Portfolio_Menu", $folder_class = "Views_Gradebook_Assessments_Portfolio_Folder";

	protected function renderView($options = array()) {
        global $translate;
        $html = [];

		if ($this->portfolio_id && $this->proxy_id) {

            $portfolio = Models_Eportfolio::fetchRow($this->portfolio_id);

			if ($portfolio) {
                $folders = $portfolio->getFolders();

                //$menu_view = new $this->menu_class(['portfolio_model'=>$portfolio]);
                //$html[] = $menu_view->render([], false);

                if ( $folders ) {
                    $html[] = '<div id="gradebook-assessment-portfolio-container" style="margin-bottom: 1rem;">';
                    $html[] = '<div class="accordion-group" style="border:none;">';

                    foreach ( $folders as $f => $folder ) {
                        $folder_options['visible'] = (0 == $f) ? true : false;

                        $folder_view = new $this->folder_class(['proxy_id'=>$this->proxy_id, 'folder_model'=>$folder, 'folder_options'=>$folder_options]);
                        $html[] = $folder_view->render([], false);
                    }
                    $html[] = '</div>'; // .accordion-group
                    $html[] = '</div>'; // .assessment-container
                } else {
                    $html[] = '<div class="alert alert-warning">';
                    $html[] = $translate->_("No folders have been defined for this portfolio").'.';
                    $html[] = '</div>'; // .alert
                }
            } else {
                $html[] = '<div class="alert alert-warning">';
                $html[] = $translate->_("Portfolio does not exist").'.';
                $html[] = '</div>'; // .alert
            }
		} else {
            $html = '';
        }

        echo implode(PHP_EOL, $html);
	}

}
