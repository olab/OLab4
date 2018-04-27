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
class Views_Gradebook_Assessments_Portfolio_Folder extends Views_Gradebook_Base {

    protected $proxy_id, $folder_model, $folder_options, $artifact_class = "Views_Gradebook_Assessments_Portfolio_Artifact";


    /**
     * Renders a portfolio folder
     * @param array $options
     * @return string html
     */
    protected function renderView($options = array()) {
        global $translate;
        $in = (true == $this->folder_options['visible']) ? ' in' : '';
        $folder = $this->folder_model;

        $html[] = '<div id="gradebook-assessment-portfolio-folder-'.$folder->getID().'" class="gradebook-assessment-portfolio-folder collapse'.$in.'" data-title="' . $folder->getTitle() . '">';
        $html[] = '<h1><i class="fa fa-folder-open-o"></i> '.$folder->getTitle().'</h1>';
        //$html[] = '<p><b>'.$folder->getDescription().'</b></p>';
        $html[] = '<hr>';

        $artifacts = $folder->getArtifacts($this->proxy_id);
        if ( $artifacts ) {
            foreach ( $artifacts as $a => $artifact ) {
                $artifact_view = new $this->artifact_class(['proxy_id'=>$this->proxy_id, 'artifact_model'=>$artifact]);
                $html[] = $artifact_view->render([], false);
            }
        } else {
            $html[] = '<div class="alert alert-warning">';
            $html[] = $translate->_("This folder has no artifacts").'.';
            $html[] = '</div>'; // .alert
        }

        $html[] = '</div>'; // .collapse

        echo implode(PHP_EOL, $html);

    }
}
