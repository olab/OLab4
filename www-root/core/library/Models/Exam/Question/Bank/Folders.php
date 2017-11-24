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
 * A Model to handle question bank folders
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 */

class Models_Exam_Question_Bank_Folders extends Models_Base {
    protected $folder_id, $parent_folder_id, $folder_title, $folder_description, $folder_order, $image_id, $updated_date, $updated_by, $deleted_date;
    protected $authors;

    protected static $table_name = "exam_question_bank_folders";
    protected static $primary_key = "folder_id";
    protected static $default_sort_column = "folder_order";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->folder_id;
    }

    public function getFolderID() {
        return $this->folder_id;
    }

    public function getParentFolderID() {
        return $this->parent_folder_id;
    }

    public function getFolderTitle() {
        return $this->folder_title;
    }

    public function getFolderDescription() {
        return $this->folder_description;
    }

    public function getFolderOrder() {
        return $this->folder_order;
    }

    public function getImageID() {
        return $this->image_id;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function setFolderOrder($order) {
        $this->folder_order = $order;
    }

    // A helper function for getting the complete path of a folder - for example, /folder1/folder2
    public function getCompleteFolderTitle() {
        if ($this->getParentFolderID()) {
            return static::fetchRowByID($this->getParentFolderID())->getCompleteFolderTitle()."/".$this->getFolderTitle();
        } else {
            return "/".$this->getFolderTitle();
        }
    }

    /* @return bool|Models_Exam_Question_Bank_Folders */
    public static function fetchRowByID($folder_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "folder_id", "value" => $folder_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Question_Bank_Folders[] */
    public static function fetchAllRecords($deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Question_Bank_Folders[] */
    public static function fetchAllByParentID($parent_folder_id, $deleted_date = NULL) {
        global $db, $ENTRADA_USER;
        $self = new self();

        $query = "  SELECT * FROM `exam_question_bank_folders` as `folder`
                    JOIN `exam_question_bank_folder_organisations` as `org`
                    ON `org`.`folder_id` = `folder`.`folder_id`
                    WHERE `folder`.`parent_folder_id` = " . $db->qstr($parent_folder_id) .
                    ($deleted_date ? " AND `folder`.`deleted_date` = " . $db->qstr($deleted_date) : " AND `folder`.`deleted_date` IS NULL") . "
                    AND `org`.`organisation_id` = " . $db->qstr($ENTRADA_USER->getActiveOrganisation()) . " 
                    ORDER BY `folder`.`"  . static::$default_sort_column . "`";

        $folders = $db->GetAll($query);

        $folder_array = array();
        if ($folders && is_array($folders) && !empty($folders)) {
            foreach ($folders as $folder) {
                $folder_array[] = new self($folder);
            }
        }

        return $folder_array;
    }

    /* @return bool|Models_Exam_Lu_Question_Bank_Folder_Images */
    public function getImage() {
        $image = Models_Exam_Lu_Question_Bank_Folder_Images::fetchRowByID($this->image_id);
        if (isset($image) && is_object($image)) {
            return $image;
        } else {
            return false;
        }
    }

    /* @return ArrayObject|Models_Exam_Question_Bank_Folder_Authors[] */
    public function getAuthors() {
        if (NULL === $this->authors){
            $this->authors = Models_Exam_Question_Bank_Folder_Authors::fetchAllByFolderID($this->folder_id);
        }
        return $this->authors;
    }

    public static function getChildrenFolders($folder_id, $folder_array) {
        $children = Models_Exam_Question_Bank_Folders::fetchAllByParentID($folder_id);

        if (isset($children) && is_array($children) && !empty($children)) {
            foreach ($children as $child) {
                if (isset($child) && is_object($child)) {
                    if (!in_array($child->getID(), $folder_array)) {
                        $folder_array[$child->getID()] = $child->getID();
                    }

                    $folder_array = static::getChildrenFolders($child->getID(), $folder_array);
                }
            }
        }

        return $folder_array;
    }

    public static function getChildrenQuestions($folder_id, $question_array) {
        $questions = Models_Exam_Question_Versions::fetchAllByFolderID($folder_id);
        $children  = Models_Exam_Question_Bank_Folders::fetchAllByParentID($folder_id);

        if (isset($questions) && is_array($questions)) {
            foreach ($questions as $question) {
                if (!in_array($question->getQuestionID(), $question_array)) {
                    $question_array[$question->getQuestionID()] = $question->getQuestionID();
                }
            }
        }

        if (isset($children) && is_array($children) && !empty($children)) {
            foreach ($children as $child) {
                if (isset($child) && is_object($child)) {
                    $question_array = static::getChildrenQuestions($child->getID(), $question_array);
                }
            }
        }

        return $question_array;
    }

    public function getQuestionCount() {
        $question_array = array();
        $questions = static::getChildrenQuestions($this->folder_id, $question_array);

        return count($questions);
    }

    public function getNextFolderOrder() {
        $folders = Models_Exam_Question_Bank_Folders::fetchAllByParentID($this->folder_id);
        if (isset($folders) && is_array($folders) && !empty($folders)) {
            $count_folders = count($folders) + 1;
        } else {
            $count_folders = 1;
        }
        return $count_folders;
    }

    /*
     * This function is used to check the permissions and generate a list of actions a user can do
     * @return array $actions
     */
    public function getActions() {
        global $ENTRADA_ACL;
        //todo move disabled for now
        $move   = false;
        $edit   = $ENTRADA_ACL->amIAllowed(new ExamFolderResource($this->getID(), true), "update");
        $delete = $ENTRADA_ACL->amIAllowed(new ExamFolderResource($this->getID(), true), "delete");

        $actions = array();

        if ($edit === true) {
            $actions[] = array(
                "href"  => "questions?section=edit-folder&id=" . $this->getFolderID(),
                "title" => "Edit",
                "type"  => "Edit",
                "id"    => $this->getFolderID()
            );
        }

        if ($move === true) {
            $actions[] = array(
                "href"  => "questions?section=move-folder&id=" . $this->getFolderID(),
                "title" => "Move",
                "type"  => "Move",
                "id"    => $this->getFolderID()
            );
        }

        if ($delete === true) {
            $actions[] = array(
                "href"  => "questions?section=delete-folder&id=" . $this->getFolderID(),
                "title" => "Delete",
                "type"  => "Delete",
                "id"    => $this->getFolderID()
            );
        }

        if (isset($actions) && is_array($actions) && !empty($actions)) {
            return $actions;
        } else {
            return false;
        }
    }

    /**
     * @param array $path
     * @return string $path_html
     */
    public function getBreadcrumbsByFolderID($path = array()) {
        global $BREADCRUMB_SEPARATOR;

        if ($this->parent_folder_id == 0) {
            if ($this->getFolderID() == 0) {
                $path[] = array(
                    "folder-id"     => $this->getFolderID(),
                    "folder-title"  => $this->getFolderTitle()
                );
            } else {
                $path[] = array(
                    "folder-id"     => $this->getFolderID(),
                    "folder-title"  => $this->getFolderTitle()
                );

                $path[] = array(
                    "folder-id"     => 0,
                    "folder-title"  => "Index"
                );
            }
            //sorts the path array backwards
            //and generates the breadcrumb list
            if (is_array($path)) {
                krsort($path);
                $last_row = false;
                $path_html = "<ul class=\"question-bank-breadcrumbs\">";
                foreach ($path as $path_key => $path_row) {
                    //since the order is reversed the key 0 is actually the last row
                    if ($path_key === 0) {
                        $last_row = true;
                    }
                    $path_html .= "<li>";
                    $path_html .= "<span class=\"bread-separator\"><i class=\"fa fa-angle-right\"></i></span>";

                    if ($last_row === true) {
                        $path_html .= "<strong class=\"active-folder\" data-id=\"" . $path_row["folder-id"] . "\">";
                        $path_html .= $path_row["folder-title"];
                        $path_html .= "</strong>";
                    } else {
                        $path_html .= "<a data-id=\"" . $path_row["folder-id"] . "\">";
                        $path_html .= $path_row["folder-title"];
                        $path_html .= "</a>";
                    }
                    $path_html .= "</li>";

                    if ($last_row === true) {
                        $path_html .= "</ul>";
                    }
                }
            }
            return $path_html;
        } else {
            $path[] = array(
                "folder-id" => $this->getFolderID(),
                "folder-title" => $this->getFolderTitle()
            );

            $parent_folder = Models_Exam_Question_Bank_Folders::fetchRowByID($this->parent_folder_id);
            if (isset($parent_folder) && is_object($parent_folder)) {
                $path_html = $parent_folder->getBreadcrumbsByFolderID($path);
                if (isset($path_html) && is_string($path_html)) {
                    return $path_html;
                }
            }
        }
    }
}