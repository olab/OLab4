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
 * @copyright Copyright 2016 Regents of The University of California. All Rights Reserved.
 */

class Views_Exam_Exam_File extends Views_Deprecated_Base
{
    protected $default_fieldset = array(
        "file_id",
        "exam_id",
        "file_name",
        "file_type",
        "file_title",
        "file_size",
        "updated_date",
        "updated_by",
        "deleted_date",
    );

    protected $table_name           = "exam_attached_files";
    protected $primary_key          = "file_id";
    protected $default_sort_column  = "file_name";

    protected $joinable_tables = array();
    protected $author;

    public function __construct(Models_Exam_Exam_File $file) {
        $this->file = $file;
    }

    public function renderRow() {
        $file = $this->file;

        if ($file && is_object($file)) {
            $user = Models_User::fetchRowByID($file->getUpdatedBy());
            $mime_type = $file->getFileType();
            switch ($mime_type) {
                case "application/pdf":
                    $type = "PDF";
                    break;
            }

            $href = ENTRADA_URL . "/file-exam.php?id=" . $file->getID();

            $html = "<li>\n";
            $html .= "    <div>\n";
            $html .= "        <strong>";
            $html .= "            <a class=\"resource-link edit-file\" href=\"$href\">";
            $html .=                html_encode(($file->getFileTitle() ? $file->getFileTitle() : $file->getFileName()));
            $html .= "            </a>";
            $html .= "        </strong>";
            $html .= "        <span class=\"btn btn-mini btn-danger pull-right delete-resource\" data-id=\"" . $file->getID() . "\">\n";
            $html .= "          <i class=\"fa fa-2x fa-trash\"></i>\n";
            $html .= "        </span>\n";
            $html .= "    </div>\n";
            $html .= "    <div>\n";
            $html .= "        <span class=\"label label-info event-resource-stat-label\">\n";
            $html .=                $type . " " . readable_size($file->getFileSize());
            $html .= "        </span>\n";
            $html .= "        <span class=\"label label-default event-resource-stat-label\">\n";
            $html .= "          Updated By: " . ($user ? $user->getFullname() : "NA") . " on " . date (DEFAULT_DATETIME_FORMAT, $file->getUpdatedDate());
            $html .= "        </span>\n";
            $html .= "    </div>\n";
            $html .= "</li>\n";

            return $html;
        }
    }
}