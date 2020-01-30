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
 * Renders an assessment portfolio folder artifact
 *
 * @author Organization: Elson S. Floyd College of Medicine
 * @author Developer: Sean Girard <sean.girard@wsu.edu>
 * @copyright Copyright 2017 Washington State University. All Rights Reserved.
 *
 */
class Views_Gradebook_Assessments_Portfolio_Artifact extends Views_Gradebook_Base {

    protected $proxy_id, $artifact_model, $artifact_options, $entry_class = "Views_Gradebook_Assessments_Portfolio_Entry";


    /**
     * Renders a portfolio folder artifact
     * @param array $options
     * @return string html
     */
    protected function renderView($options = array()) {
        global $translate;
        $artifact = $this->artifact_model;

        $due_date = false;
        if ($artifact->getFinishDate()) {
            $due_date = $artifact->getFinishDate();
        } else {
            $due_date = $artifact->getStartDate();
        }
        if ($due_date) {
            $due_date_formatted = date('Y-m-d', $due_date);
        }

        $html[] = '<div class="card artifact-container">';
        $html[] = '<div class="card-title">';
        $html[] = '<h2>';
        if ($due_date_formatted) {
            $html[] = '<small class="pull-right text-warning">' . $translate->_("Due") . ': ' . $due_date_formatted . '</small>';
        }
        $html[] = $artifact->getTitle();
        $html[] = '</h2>';
        $html[] = '</div>'; // .card-title

        $html[] = '<div class="card-block">';
        $html[] = '<div class="artifact-description">'.$artifact->getDescription().'</div>';
        if ( $artifact->getHasEntry() ) {
            $entries = $artifact->getEntries($this->proxy_id);
            if ( $entries ) {
                foreach ($entries as $e => $entry) {
                    $entry_view = new $this->entry_class(['entry_model' => $entry]);
                    $html[] = $entry_view->render([], false);
                    //$html[] = 'my entry';
                }
            } else {
                $html[] = '<div class="alert alert-warning">';
                $html[] = $translate->_("Error retrieving entries").'.';
                $html[] = '</div>'; // .alert
            }
        } else {
            if ($artifact->getStartDate()) {
                $html[] = '<div class="alert alert-danger">';
            } else {
                $html[] = '<div class="alert alert-warning">';
            }
            $html[] = '<h4>';
            $html[] = $translate->_("This artifact has no entries").'.';
            $html[] = '</h4>';
            $html[] = '</div>'; // .alert
        }
        $html[] = '</div>'; // .card-block
        $html[] = '</div>'; // .card

        echo implode(PHP_EOL, $html);
    }
}
