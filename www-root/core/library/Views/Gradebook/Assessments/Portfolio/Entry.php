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
 * Renders an assessment portfolio folder artifact entry
 *
 * @author Organization: Elson S. Floyd College of Medicine
 * @author Developer: Sean Girard <sean.girard@wsu.edu>
 * @copyright Copyright 2017 Washington State University. All Rights Reserved.
 *
 */
class Views_Gradebook_Assessments_Portfolio_Entry extends Views_Gradebook_Base {

    protected $entry_model, $entry_options, $comment_class = "Views_Gradebook_Assessments_Portfolio_Comment";


    /**
     * Renders a portfolio folder artifact entry
     * @param array $options
     * @return string html
     */
    protected function renderView($options = array()) {
        global $ENTRADA_USER;
        global $translate;
        $entry = $this->entry_model;

        $html = [];
        if ( $entry->getIsAssessable() ) {
            $data = $entry->getEdataDecoded();

            $html[] = '<div class="card artifact-entry">';
            $html[] = '<h4 class="card-title">'.$data['title'].'</h4>';
            $html[] = '<div class="card-block">';
            if ($entry->getSubmittedDate()) {
                $html[] = '<span class="pull-right">' . $translate->_("Submitted") . ': ' . '<b>' . date('Y-m-d', $entry->getSubmittedDate()) . '</b></span>';
            }
            switch ( $entry->getType() ) {
                case 'reflection':
                    if ( !empty($data['description']) ) {
                        $html[] = $data['description'];
                    } else {
                        $html[] = '<div class="clearfix"></div>';
                        $html[] = '<div class="alert alert-info">';
                        $html[] = $translate->_("No Reflection provided").'.';
                        $html[] = '</div>'; // .alert\
                    }
                    break;
                case 'url':
                    if ( !empty($data['description']) ) {
                        $html[] = '<p>';
                        $html[] = '<a target="_blank" class="btn btn-success" href="' . $data['description'] . '">';
                        $html[] = '<i class="fa fa-share"></i> ' . $translate->_("Visit URL") . ' <span>(' . $translate->_("new window") . ')</span>';
                        $html[] = '</a>';
                        $html[] = '</p>';
                    } else {
                        $html[] = '<div class="clearfix"></div>';
                        $html[] = '<div class="alert alert-info">';
                        $html[] = $translate->_("No URL provided").'.';
                        $html[] = '</div>'; // .alert
                    }
                    break;
                case 'file':
                    $html[] = '<p>';
                    $html[] = $data['description'];
                    $html[] = '</p>';
                    if ( !empty($data['filename']) ) {
                        $html[] = '<p>';
                        $html[] = '<a class="btn btn-success" href="' . ENTRADA_URL . '/serve-eportfolio-entry.php?entry_id=' . $entry->getID() . '">';
                        $html[] = '<i class="fa fa-download"></i> ' . $translate->_("Download File");
                        $html[] = '</a>';
                        $html[] = '</p>';
                    } else {
                        $html[] = '<div class="clearfix"></div>';
                        $html[] = '<div class="alert alert-info">';
                        $html[] = $translate->_("No File provided").'.';
                        $html[] = '</div>'; // .alert
                    }

                    break;
            }

            if (Entrada_Settings::fetchValueByShortname("eportfolio_show_comments_in_gradebook_assessment", $ENTRADA_USER->getActiveOrganisation())) {
                $comments = $entry->getComments();
                if ( $comments ) {
                    $html[] = '<hr>';
                    foreach ($comments as $c => $comment) {
                        $comment_view = new $this->comment_class(['comment_model' => $comment]);
                        $html[] = $comment_view->render([], false);
                    }
                }
            }

            $html[] = '</div>'; // /.card-block
            $html[] = '</div>'; // /.card
        } else {
            //echo 'No entries flagged for assessment.';
        }
        echo implode(PHP_EOL, $html);
    }
}
