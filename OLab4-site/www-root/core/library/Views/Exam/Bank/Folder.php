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

class Views_Exam_Bank_Folder extends Views_Deprecated_Base
{
    protected $default_fieldset = array(
        "folder_id",
        "parent_folder_id",
        "folder_title",
        "folder_description",
        "folder_order",
        "updated_date",
        "updated_date",
        "updated_by",
        "deleted_date"
    );

    protected $table_name = "exam_bank_folders";
    protected $primary_key = "folder_id";
    protected $default_sort_column = "`exam_bank_folders`.`folder_order`";
    protected $joinable_tables = array();
    protected $folder;

    public function __construct(Models_Exam_Bank_Folders $folder) {
        $this->folder = $folder;
    }

    public function render() {
        $folder = $this->folder;
        if ($folder) {
            $type       = $folder->getFolderType();
            switch ($type) {
                case "exam":
                    $folder_type = "exams";
                    break;
                case "question":
                    $folder_type = "questions";
                    break;
            }
            $image      = $folder->getImage();
            $image_view = new Views_Exam_Bank_Folder_Image($image);
            $actions    = $folder->getActions($folder_type);

            $sub_folder_html = "<li id=\"folder_" . $folder->getID() .  "\" class=\"bank-folder-li\" data-sortable-folder-id=\"" . $folder->getID() . "\">";
            $sub_folder_html .= "<span class=\"bank-folder\" data-folder-id=\"" . $folder->getID() . "\" data-parent-folder-id=\"" . $folder->getParentFolderID() . "\">";

            if (isset($image_view) && is_object($image_view)) {
                $sub_folder_html .= $image_view->render();
            }
            $sub_folder_html .= "<span class=\"folder-title\">" . $folder->getFolderTitle() . "</span>";
            $sub_folder_html .= "</span>";

            $sub_folder_html .= "<span class=\"badge\">" . $folder->getCount() . "</span>";

            if (isset($actions) && is_array($actions)) {
                $sub_folder_html .= $this->renderFolderActions($actions);
            }

            $sub_folder_html .= "</li>";
        }

        return $sub_folder_html;
    }

    public function getFolderURL() {
        $folder = $this->folder;
        $url = false;
        if ($folder) {
            $url = ENTRADA_URL . "/admin/exams/" . ($folder->getFolderType() === "exam" ? "exams" : "questions") . "/?folder_id=" . $folder->getID();
        }
        return $url ;
    }

    public function renderSimpleView() {
        $image = $this->folder->getImage();
        if ($image && is_object($image)) {
            $image_view = new Views_Exam_Bank_Folder_Image($image);
            if ($image_view && is_object($image_view)) {
                $sub_folder_html = $image_view->render();
                $sub_folder_html .= "<span class=\"folder-title\">" . $this->folder->getFolderTitle() . "</span>";
            }
        }

        return $sub_folder_html;
    }

    public function renderFolderActions($actions) {
        if (!empty($actions)) {
            $output = "<div class=\"btn-group folder-edit-btn pull-right\">\n";
            $output .= "<button class=\"btn btn-small dropdown-toggle\" data-toggle=\"dropdown\"><i class=\"fa fa-gear\"></i></button>\n";
            $output .= "<ul class=\"dropdown-menu toggle-left\">\n";
            foreach ($actions as $action) {
                switch ($action["type"]) {
                    case "Delete":
                        $output .= "<li><a href=\"#delete-folder-modal\" data-type=\"{$action['type']}\" data-id=\"{$action['id']}\">{$action['title']}</a></li>\n";
                        break;
                    case "Copy":
                        $output .= "<li><a href=\"#copy-folder-modal\" data-type=\"{$action['type']}\" data-id=\"{$action['id']}\">{$action['title']}</a></li>\n";
                        break;
                    case "Move":
                        $output .= "<li><a href=\"#move-folder-modal\" data-type=\"{$action['type']}\" data-id=\"{$action['id']}\">{$action['title']}</a></li>\n";
                        break;
                    case "Edit":
                        $output .= "<li><a href=\"{$action['href']}\" data-type=\"{$action['type']}\" >{$action['title']}</a></li>\n";
                        break;
                }
            }
            $output .= "</ul>\n";
            $output .= "</div>\n";
        }
        return $output;
    }

    public static function renderBackNavigation($parent_parent_folder) {
        $sub_folder_html = "<li class=\"bank-folder-li\"><span class=\"bank-folder\" data-folder-id=\"" . $parent_parent_folder . "\">";
            $sub_folder_html .= "<span><i class=\"fa fa-chevron-left\"></i></span>";
            $sub_folder_html .= "<span>Back</span>";
        $sub_folder_html .= "</span></li>";

        return $sub_folder_html;
    }

    public function renderFolderSelectorBackNavigation() {
        $nav_html = "<span class=\"qbf-back-nav\" data-folder-id=\"" . $this->folder->getParentFolderID() . "\">";
            $nav_html .= "<span><i class=\"fa fa-chevron-left\"></i></span>";
            $nav_html .= "<span>Back</span>";
        $nav_html .= "</span>";

        return $nav_html;
    }

    public function renderFolderSelectorInterface() {
        global $translate, $ENTRADA_USER;
        $folder             = $this->folder;
        $folder_id          = $folder->getFolderID();
        $parent_folder_id   = $folder->getParentFolderID();
        $folder_type        = $folder->getFolderType();
        $sub_folder_html    = "";


        $restrict_to_folder = false;
        $restrict_folder_ids = array();
        $restrict_folder_array_children = array();
        $access_folder_allowed_array = array();
        $subfolder_html = "";

        $group = $ENTRADA_USER->getActiveGroup();
        if ($group === "student") {
            $allowed_folders = Models_Exam_Bank_Folders::fetchAllByTypeAuthor("question", $ENTRADA_USER->getID());

            if ($allowed_folders && is_array($allowed_folders) && !empty($allowed_folders)) {
                foreach ($allowed_folders as $folder) {
                    $restrict_folder_ids[] = (int)$folder->getID();
                }
            }

            if ($restrict_folder_ids && is_array($restrict_folder_ids) && !empty($restrict_folder_ids)) {
                foreach ($restrict_folder_ids as $folder_restricted) {
                    if (!in_array($folder_restricted, $restrict_folder_array_children)) {
                        $restrict_folder_array_children[(int)$folder_restricted] = (int)$folder_restricted;
                    }
                    $restrict_folder_array_children = Models_Exam_Bank_Folders::getChildrenFolders($folder_restricted, $restrict_folder_array_children, "question");
                }
            }
        }

        $folders            = Models_Exam_Bank_Folders::fetchAllByParentID($folder_id, $folder_type);

        if (isset($folders) && is_array($folders) && !empty($folders)) {
            // Provides restrictions for limiting folders students can access
            if ($restrict_folder_array_children && is_array($restrict_folder_array_children) && !empty($restrict_folder_array_children)) {
                foreach ($folders as $folder) {
                    if ($folder && is_object($folder)) {
                        if (in_array($folder->getID(), $restrict_folder_array_children)) {
                            $access_folder_allowed_array[] = $folder;
                        }
                    }
                }
            } else {
                $access_folder_allowed_array = $folders;
            }

            $sub_folder_html .= "<table>";

            if ($folder_id === 0) {
                $root_folder = new Models_Exam_Bank_Folders(
                    array(
                        "folder_id"     => 0,
                        "folder_title"  => "Index",
                        "image_id"      => 3
                    )
                );

                $root_folder_view = new Views_Exam_Bank_Folder($root_folder);
                $sub_folder_html .= $root_folder_view->renderFolderSelectorRow(false, true);
            }

            foreach ($access_folder_allowed_array as $folder) {
                $show_folder_arrow = false;
                $selected = false;
                if ($folder && is_object($folder)) {
                    if ($folder->getID() == $parent_folder_id) {
                        $selected = true;
                    }
                    $folder_view = new Views_Exam_Bank_Folder($folder);
                    if (isset($folder_view)) {
                        $sub_folders = Models_Exam_Bank_Folders::fetchAllByParentID($folder->getID(), $folder_type);
                        if ($sub_folders) {
                            $show_folder_arrow = true;
                        }
                        $sub_folder_html .= $folder_view->renderFolderSelectorRow($selected, $show_folder_arrow);
                    }
                }
            }
            $sub_folder_html .= "</table>";
        } else {
            $sub_folder_html .= "<table>";
            $sub_folder_html .= "<tr>";
            $sub_folder_html .= "<td>";
            $sub_folder_html .= "<span class=\"folder-empty\">" . $translate->_("No sub folders found in this folder.") . "</span>";
            $sub_folder_html .= "</td>";
            $sub_folder_html .= "</tr>";
            $sub_folder_html .= "</table>";
        }

        return $sub_folder_html;
    }

    public function renderFolderSelectorTitle() {
        $html = "<h3>";
        if ($this->folder && is_object($this->folder)) {
            $html .= $this->folder->getFolderTitle();
        }
        $html .= "</h3>";
        return $html;
    }

    public function renderFolderSelectorRow($selected = false, $show_folder_arrow = false) {
        $image = $this->folder->getImage();
        if ($image && is_object($image)) {
            $image_view = new Views_Exam_Bank_Folder_Image($image);
            $html = "<tr>";
            $html .= "<td class=\"folder-selector" . ($selected ? " folder-selected" : "") . "\" data-id=\"" . $this->folder->getID() . "\">";
            $html .= $image_view->render();
            $html .= "<span class=\"folder-title\">" . $this->folder->getFolderTitle() . "</span>";
            $html .= "</td>";
            if ($this->folder->getID() != 0 && $show_folder_arrow === true) {
                $html .= "<td class=\"sub-folder-selector\" data-id=\"" . $this->folder->getID() . "\">";
                $html .= "<span>";
                $html .= "<i class=\"fa fa-chevron-right\"></i>";
                $html .= "</span>";
                $html .= "</td>";
            } else {
                $html .= "<td>";
                $html .= "</td>";
            }
            $html .= "</tr>";
        }

        return $html;
    }
}