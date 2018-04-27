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
 * Renders an assessment portfolio folder artifact entry comment
 *
 * @author Organization: Elson S. Floyd College of Medicine
 * @author Developer: Sean Girard <sean.girard@wsu.edu>
 * @copyright Copyright 2017 Washington State University. All Rights Reserved.
 *
 */
class Views_Gradebook_Assessments_Portfolio_Comment extends Views_Gradebook_Base {

    protected $comment_model, $comment_options;


    /**
     * Renders a portfolio folder artifact entry comment
     * @param array $options
     * @return string html
     */
    protected function renderView($options = array()) {
        //global $translate;
        $comment = $this->comment_model;

        $commenter = User::fetchRowByID($comment->getProxyID());
        $commenter->getFullname(false);

        $html[] = '<blockquote>';
        $html[] = $comment->getComment();
        $html[] = '<small>';
        $html[] = '<b>' . $commenter->getFullname(false) . '</b>' . ' ' . $comment->getUpdateDate();
        $html[] = '</small>';
        $html[] = '</blockquote>';


        echo implode(PHP_EOL, $html);
    }
}
