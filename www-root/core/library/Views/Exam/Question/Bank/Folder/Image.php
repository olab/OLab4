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

class Views_Exam_Question_Bank_Folder_Image extends Views_Deprecated_Base
{
    protected $default_fieldset = array(
        "image_id",
        "file_name",
        "color",
        "order",
        "deleted_date"
    );

    protected $table_name = "exam_lu_question_bank_folder_images";
    protected $primary_key = "image_id";
    protected $default_sort_column = "`exam_lu_question_bank_folder_images`.`order`";
    protected $joinable_tables = array();
    protected $image;

    public function __construct(Models_Exam_Lu_Question_Bank_Folder_Images $image) {
        $this->image = $image;
    }

    public function render($include_select = 0, $active = 0, $stacked = 0) {
        $html = "<span class=\"folder-image\" data-image-id=\"" . $this->image->getID() . "\">";
        if ($stacked === 1) {
            $html .= "    <span class=\"folder-stacked\">";
        }
        if ($include_select === 1) {
            $html .= "      <img class=\"" . ($active ? " active " : "") . "folder-select\" src=\"" . ENTRADA_URL . "/images/folders/folder_selected.png\" alt=\"folder_selected\">";
        }
        $html .= "        <img class=\"folder-color\" src=\"" . ENTRADA_URL . "/images/folders/" . $this->image->getFileName() . "\" alt=\"" . $this->image->getColor() . " Folder\">";
        if ($stacked === 1) {
            $html .= "    </span>";
        }
        $html .= "</span>";
        return $html;
    }

}