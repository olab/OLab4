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
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 */

class Views_Exam_Exam_Author extends Views_Deprecated_Base
{
    protected $default_fieldset = array(
        "aeauthor_id",
        "exam_id",
        "author_type",
        "author_id",
        "created_date",
        "created_by",
        "updated_date",
        "updated_by",
        "deleted_date"
    );

    protected $table_name           = "exam_question_authors";
    protected $primary_key          = "aeauthor_id";
    protected $default_sort_column  = "question_id";

    protected $joinable_tables = array();
    protected $author;

    public function __construct(Models_Exam_Exam_Author $author) {
        $this->author = $author;
    }

    public function render($edit = 0) {
        $author = $this->author;

        $html  = "<li class=\"clearfix\">";
        if ($edit === 0) {
            $html .= "<span class=\"remove-permission-1 pull-right\">";
            $html .= "<a href=\"#\" class=\"remove-permission\" data-author-id=\"" . $author->getID() . "\">";
            $html .= "<i class=\"fa fa-2x fa-times-circle\"></i>";
            $html .= "</a>";
            $html .= "</span>";
            $html .= "<span class=\"remove-permission-2\">";
            $html .= "<strong>" . $author->getAuthorName() . "</strong>";
            $html .= "</span>";
        } else {
            $html .= "<span class=\"remove-permission-1 pull-right\">";
            $html .= "<i class=\"fa fa-2x fa-expeditedssl\"></i>";
            $html .= "</span>";
            $html .= "<span class=\"remove-permission-2\">";
            $html .= "<strong class=\"no-edit-author\">" . $author->getAuthorName() . "</strong>";
            $html .= "</span>";
        }
        $html .= "</span>";
        $html .=  "</li>";
        return $html;
    }

    /*
     * This function generates the Title and UL with the current authors for the specified type
     *
     * @param string $type
     * @param ArrayObject|Models_Exam_Bank_Folder_Authors[] $authors
     * * @param ArrayObject|Models_Exam_Question_Authors[] $authors
     * @return string $html
     */

    public static function renderTypeUL($type, $folder_authors, $authors) {
        global $translate, $ENTRADA_USER;

        $MODULE_TEXT = $translate->_("exams");
        $SUBMODULE_TEXT = $MODULE_TEXT["questions"]["add-permission"];

        $lis = "";
        $class = "hide";

        if ($folder_authors && is_array($folder_authors) && $authors && is_array($authors)) {
            $author_array = $authors;
            // Folder author can be the same person as the author creator. The array should
            // have only unique users, even if the array has different object types
            // Lets save the authors ids
            $authors_ids = [];
            foreach ($authors as $author) {
                array_push($authors_ids, $author["object"]->getAuthorID());
            }
            // Now only add the folder author to the author array if the author isn't in the question permission
            foreach ($folder_authors as $folder_author) {
                if (!in_array($folder_author["object"]->getAuthorID(), $authors_ids)) {
                    array_push($author_array, $folder_author);
                }
            }
            usort($author_array, "Models_Exam_Bank_Folder_Authors::sortByAuthorName");
        } else if ($folder_authors && is_array($folder_authors)) {
            $author_array = $folder_authors;
        } else if ($authors && is_array($authors)) {
            $author_array = $authors;
        }

        if (isset($author_array) && is_array($author_array) && !empty($author_array)) {
            $class = "";
            foreach ($author_array as $item) {
                $author = $item["object"];
                $level  = $item["level"];
                $object_type = $item["object_type"];
                if ($object_type == "exam") {
                    if (isset($author) && is_object($author)) {
                        $author_view = new Views_Exam_Exam_Author($author);
                        if (($author->getAuthorType() == "proxy_id" && $author->getAuthorID() == $ENTRADA_USER->getActiveID())) {
                            $lis .= $author_view->render(false);
                        } else {
                            $lis .= $author_view->render($level);
                        }
                    }
                } else if ($object_type == "folder") {
                    if (isset($author) && is_object($author)) {
                        $author_view = new Views_Exam_Bank_Folder_Author($author);
                        if (($author->getAuthorType() == "proxy_id" && $author->getAuthorID() == $ENTRADA_USER->getActiveID())) {
                            $lis .= $author_view->render(false);
                        } else {
                            $lis .= $author_view->render($level);
                        }
                    }
                }
            }
        }

        $html = "<div id=\"author-list-" . $type ."-container\" class=\"" . $class . "\">";
        $html .= "<h5>" . $SUBMODULE_TEXT["contact_types"][$type] . "</h5>";
        $html .= "<ul class=\"unstyled author-list\" id=\"author-list-" . $type ."\">";
        $html .= $lis;
        $html .= "</ul>";
        $html .= "</div>";

        return $html;
    }
}
