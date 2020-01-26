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

class Models_Exam_Bank_Folders extends Models_Base {
    protected $folder_id;
    protected $parent_folder_id;
    protected $folder_title;
    protected $folder_description;
    protected $folder_order;
    protected $image_id;
    protected $folder_type;
    protected $organisation_id;
    protected $created_date;
    protected $created_by;
    protected $updated_date;
    protected $updated_by;
    protected $deleted_date;
    protected $authors;

    protected static $table_name = "exam_bank_folders";
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

    public function setFolderId($folder_id) {
        $this->folder_id = $folder_id;
    }

    public function getParentFolderID() {
        return $this->parent_folder_id;
    }

    public function setParentFolderId($parent_folder_id) {
        $this->parent_folder_id = $parent_folder_id;
    }

    public function getFolderTitle() {
        return $this->folder_title;
    }

    public function setFolderTitle($folder_title) {
        $this->folder_title = $folder_title;
    }

    public function getFolderDescription() {
        return $this->folder_description;
    }

    public function setFolderDescription($folder_description) {
        $this->folder_description = $folder_description;
    }

    public function getFolderOrder() {
        return $this->folder_order;
    }

    public function setFolderOrder($order) {
        $this->folder_order = $order;
    }

    public function getImageID() {
        return $this->image_id;
    }

    public function setImageId($image_id) {
        $this->image_id = $image_id;
    }

    public function getFolderType() {
        return $this->folder_type;
    }

    public function setFolderType($folder_type) {
        $this->folder_type = $folder_type;
    }

    /**
     * @return mixed
     */
    public function getOrganisationId() {
        return $this->organisation_id;
    }

    /**
     * @param mixed $organisation_id
     */
    public function setOrganisationId($organisation_id) {
        $this->organisation_id = $organisation_id;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function setCreatedBy($created_by) {
        $this->created_by = $created_by;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function setCreatedDate($created_date) {
        $this->created_date = $created_date;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function setUpdatedDate($updated_date) {
        $this->updated_date = $updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function setUpdatedBy($updated_by) {
        $this->updated_by = $updated_by;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function setDeletedDate($deleted_date) {
        $this->deleted_date = $deleted_date;
    }

    // A helper function for getting the complete path of a folder - for example, /folder1/folder2
    public function getCompleteFolderTitle() {
        if ($this->getParentFolderID()) {
            return static::fetchRowByID($this->getParentFolderID())->getCompleteFolderTitle()."/".$this->getFolderTitle();
        } else {
            return "/".$this->getFolderTitle();
        }
    }

    /* @return bool|Models_Exam_Bank_Folders */
    public static function fetchRowByID($folder_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "folder_id", "value" => $folder_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Bank_Folders[] */
    public static function fetchAllRecords($deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Bank_Folders[] */
    public static function fetchAllByTypeAuthor($folder_type, $proxy_id, $deleted_date = NULL) {
        global $db, $ENTRADA_USER;

        $query = "  SELECT * FROM `exam_bank_folders` as `folder`
                    JOIN `exam_bank_folder_organisations` as `org`
                    ON `org`.`folder_id` = `folder`.`folder_id`
                    INNER JOIN `exam_bank_folder_authors` as `author`
                    ON `author`.`folder_id` = `folder`.`folder_id`
                    AND `author`.`author_id` = " . $db->qstr($proxy_id) . "
                    AND `author`.`author_type` = 'proxy_id'
                    WHERE `folder`.`folder_type` = " . $db->qstr($folder_type) .
                    ($deleted_date ? " AND `folder`.`deleted_date` = " . $db->qstr($deleted_date) : " AND `folder`.`deleted_date` IS NULL") . "
                    AND `org`.`organisation_id` = " . $db->qstr($ENTRADA_USER->getActiveOrganisation()) . " 
                    ORDER BY `folder`.`folder_order`";

        $folders = $db->GetAll($query);

        $folder_array = array();
        if ($folders && is_array($folders) && !empty($folders)) {
            foreach ($folders as $folder) {
                if ($folder && is_array($folder)) {
                    $folder_array[] = new self($folder);
                }
            }
        }

        return $folder_array;
    }

    /* @return ArrayObject|Models_Exam_Bank_Folders[] */
    public static function fetchAllByType($type, $organisation_id, $deleted_date = NULL) {
        $self = new self();
        return $self->fetchAll(array(
            array("key" => "folder_type", "value" => $type, "method" => "="),
            array("key" => "organisation_id", "value" => $organisation_id, "method" => "="),
            array("key" => "deleted_date", "value" => ($deleted_date ? $deleted_date : NULL), "method" => ($deleted_date ? "<=" : "IS"))
        ));
    }

    /* @return ArrayObject|Models_Exam_Bank_Folders[] */
    public static function fetchAllByParentID($parent_folder_id, $folder_type = "question", $deleted_date = NULL) {
        global $db, $ENTRADA_USER;

        $query = "  SELECT * FROM `exam_bank_folders` as `folder`
                    JOIN `exam_bank_folder_organisations` as `org`
                    ON `org`.`folder_id` = `folder`.`folder_id`
                    WHERE `folder`.`parent_folder_id` = " . $db->qstr($parent_folder_id) . "
                    AND `folder`.`folder_type` = " . $db->qstr($folder_type) .
                    ($deleted_date ? " AND `folder`.`deleted_date` = " . $db->qstr($deleted_date) : " AND `folder`.`deleted_date` IS NULL") . "
                    AND `org`.`organisation_id` = " . $db->qstr($ENTRADA_USER->getActiveOrganisation()) . " 
                    ORDER BY `folder`.`folder_order`";

        $folders = $db->GetAll($query);

        $folder_array = array();
        if ($folders && is_array($folders) && !empty($folders)) {
            foreach ($folders as $folder) {
                if ($folder && is_array($folder)) {
                    $folder_array[] = new self($folder);
                }
            }
        }

        return $folder_array;
    }

    /* @return bool|Models_Exam_Lu_Bank_Folder_Images */
    public function getImage() {
        $image = Models_Exam_Lu_Bank_Folder_Images::fetchRowByID($this->image_id);
        if (isset($image) && is_object($image)) {
            return $image;
        } else {
            return false;
        }
    }

    /* @return ArrayObject|Models_Exam_Bank_Folder_Authors[] */
    public function getAuthors() {
        if (NULL === $this->authors) {
            $this->authors = Models_Exam_Bank_Folder_Authors::fetchAllByFolderID($this->folder_id);
        }
        return $this->authors;
    }

    /**
     * Whether this folder has children. Use the $include_folders tag to count subfolders as children.
     *
     * @param bool $include_folders
     * @return bool
     */
    public function hasChildren($include_folders = false) {
        if ($include_folders) {
            $folders = Models_Exam_Bank_Folders::fetchAllByParentID($this->folder_id, $this->folder_type);

            if ($folders && count($folders) > 0) {
                return true;
            }
        }

        if ($this->folder_type == "exam") {
            $exams = Models_Exam_Exam::fetchAllByFolderID($this->folder_id);

            if ($exams && count($exams) > 0) {
                return true;
            }
        } else if ($this->folder_type == "question") {
            $questions = Models_Exam_Question_Versions::fetchAllByFolderID($this->folder_id);

            if ($questions && count($questions) > 0) {
                return true;
            }
        }

        return false;
    }

    public static function getChildrenFolders($folder_id, $folder_array = array(), $folder_type = "exam") {
        $children = Models_Exam_Bank_Folders::fetchAllByParentID((int)$folder_id, $folder_type);


        if (isset($children) && is_array($children) && !empty($children)) {
            foreach ($children as $child) {
                if (isset($child) && is_object($child)) {
                    if (!in_array((int)$child->getID(), $folder_array)) {
                        $folder_array[(int)$child->getID()] = (int)$child->getID();
                    }

                    $folder_array = static::getChildrenFolders((int)$child->getID(), $folder_array, $folder_type);
                }
            }
        }

        return $folder_array;
    }

    public static function getChildrenQuestions($folder_id, $question_array) {
        $questions = Models_Exam_Question_Versions::fetchAllByFolderID($folder_id);
        $children  = Models_Exam_Bank_Folders::fetchAllByParentID($folder_id, "question");

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

    public static function getChildrenExams($folder_id, $array) {
        $exams      = Models_Exam_Exam::fetchAllByFolderID($folder_id);
        $children   = Models_Exam_Bank_Folders::fetchAllByParentID($folder_id, "exam");

        if (isset($exams) && is_array($exams)) {
            foreach ($exams as $exam) {
                if ($exam && is_object($exam)) {
                    if (!in_array($exam->getExamID(), $array)) {
                        $array[$exam->getExamID()] = $exam->getExamID();
                    }
                }
            }
        }

        if (isset($children) && is_array($children) && !empty($children)) {
            foreach ($children as $child) {
                if (isset($child) && is_object($child)) {
                    $array = static::getChildrenExams($child->getID(), $array);
                }
            }
        }

        return $array;
    }

    public static function copyFolderAndExams($folder_id, $destination_folder_id, $folders_copied = array(), $exams_copied = array(), $count = 0) {
        global $ENTRADA_USER, $translate;

        $count++;

        $folder = Models_Exam_Bank_Folders::fetchRowByID($folder_id);
        if ($folder && is_object($folder)) {

            $new_folder_array = $folder->toArray();

            unset($new_folder_array["folder_id"]);
            $new_folder_array["parent_folder_id"] = $destination_folder_id;
            $new_folder_array["folder_title"] = "Copy of " . $new_folder_array["folder_title"];
            $new_folder_array["updated_date"] = time();
            $new_folder_array["updated_by"] = $ENTRADA_USER->getActiveID();

            $new_folder = new Models_Exam_Bank_Folders($new_folder_array);

            if (!$new_folder->insert()) {
                add_error($translate->_("Error inserting new exam bank folder."));
            } else {
                // update
                $new_folder_id = $new_folder->getID();

                $ebf_organisations = Models_Exam_Bank_Folder_Organisations::fetchAllByFolderID($folder_id);
                if ($ebf_organisations && is_array($ebf_organisations) && !empty($ebf_organisations)) {
                    foreach ($ebf_organisations as $ebf_organisation) {
                        // $ebf_organisation
                        $ebf_organisation_array = $ebf_organisation->toArray();
                        if ($ebf_organisation_array && is_array($ebf_organisation_array)) {
                            unset($ebf_organisation_array["folder_org_id"]);
                            $ebf_organisation_array["folder_id"] = $new_folder_id;
                            $new_ebf_organisation = new Models_Exam_Bank_Folder_Organisations($ebf_organisation_array);
                            if ($new_ebf_organisation && is_object($new_ebf_organisation)) {
                                if (!$new_ebf_organisation->insert()) {
                                    add_error("Error inserting exam bank folder organisation.");
                                }
                            }
                        }
                    }
                }


                $ebf_authors = Models_Exam_Bank_Folder_Authors::fetchAllByFolderID($folder_id);
                if ($ebf_authors && is_array($ebf_authors) && !empty($ebf_authors)) {
                    foreach ($ebf_authors as $ebf_author) {
                        // $ebf_author
                        $ebf_author_array = $ebf_author->toArray();
                        if ($ebf_author_array && is_array($ebf_author_array)) {
                            unset($ebf_author_array["efauthor_id"]);
                            $ebf_author_array["folder_id"] = $new_folder_id;

                            $new_ebf_author = new Models_Exam_Bank_Folder_Authors($ebf_author_array);
                            if ($new_ebf_author && is_object($new_ebf_author)) {
                                if (!$new_ebf_author->insert()) {
                                    add_error("Error inserting exam bank folder author.");
                                }
                            }
                        }
                    }
                }

                $folders_copied[] = $new_folder_id;

                $exams      = Models_Exam_Exam::fetchAllByFolderID($folder_id);
                if (isset($exams) && is_array($exams)) {
                    foreach ($exams as $exam) {
                        if ($exam && is_object($exam)) {

                            $new_exam_array = $exam->toArray();

                            unset($new_exam_array["exam_id"]);
                            $new_exam_array["folder_id"] = $new_folder_id;
                            $new_exam_array["title"] = "Copy of " . $new_exam_array["title"];
                            $new_exam_array["updated_date"] = time();
                            $new_exam_array["updated_by"] = $ENTRADA_USER->getActiveID();

                            $new_exam = new Models_Exam_Exam($new_exam_array);

                            if (!$new_exam->insert()) {
                                add_error($translate->_("Error inserting new exam."));
                            } else {
                                $exams_copied[] = $new_exam->getID();
                            }
                        }
                    }
                }

                $array = array(
                    "folders_copied" => $folders_copied,
                    "exams_copied" => $exams_copied,
                    "count" => $count
                );

                $children   = Models_Exam_Bank_Folders::fetchAllByParentID($folder_id, "exam");

                if (isset($children) && is_array($children) && !empty($children)) {
                    foreach ($children as $child) {
                        if (isset($child) && is_object($child)) {
                            $array = static::copyFolderAndExams($child->getID(), $new_folder_id, $folders_copied, $exams_copied, $count);
                        }
                    }
                }
            }
        }

        if ($count > 99) {
            return false;
        } else {
            return array(
                "folders_copied" => $array["folders_copied"],
                "exams_copied" => $array["exams_copied"],
                "count" => $array["count"]
            );
        }

    }

    public function getCount() {
        $folder_type = $this->folder_type;
        $count_array = array();
        switch ($folder_type) {
            case "question":
                $count = static::getChildrenQuestions($this->folder_id, $count_array);
                break;
            case "exam":
                $count = static::getChildrenExams($this->folder_id, $count_array);
                break;
        }


        return count($count);
    }

    public function getNextFolderOrder() {
        $folders = Models_Exam_Bank_Folders::fetchAllByParentID($this->folder_id);
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
    public function getActions($type = "question") {
        global $ENTRADA_ACL, $ENTRADA_USER;

        if ($ENTRADA_USER->getGroup() !== "student") {
            $edit   = $ENTRADA_ACL->amIAllowed(new ExamFolderResource($this->getID(), true), "update");
        } else {
            $edit = false;
        }

        $delete = $ENTRADA_ACL->amIAllowed(new ExamFolderResource($this->getID(), true), "delete");

        $actions = array();

        if ($edit === true && $this->getFolderType() == "exam") {
            $actions[] = array(
                "href"  => $type . "?section=copy-folder&id=" . $this->getFolderID(),
                "title" => "Copy",
                "type"  => "Copy",
                "id"    => $this->getFolderID()
            );
        }

        if ($delete === true && $this->getID() != 1) {
            if ($this->getFolderType() == "exam" && $this->getID() != 1) {
                $actions[] = array(
                    "href" => $type . "?section=delete-folder&id=" . $this->getFolderID(),
                    "title" => "Delete",
                    "type" => "Delete",
                    "id" => $this->getFolderID()
                );
            } else if ($this->getFolderType() != "exam"){
                $actions[] = array(
                    "href" => $type . "?section=delete-folder&id=" . $this->getFolderID(),
                    "title" => "Delete",
                    "type" => "Delete",
                    "id" => $this->getFolderID()
                );
            }
        }

        if ($edit === true) {
            $actions[] = array(
                "href"  => $type . "?section=edit-folder&id=" . $this->getFolderID(),
                "title" => "Edit & Manage Authors",
                "type"  => "Edit",
                "id"    => $this->getFolderID()
            );
        }

        if ($edit === true && $this->getFolderType() == "exam") {
            $actions[] = array(
                "href"  => $type . "?section=move-folder&id=" . $this->getFolderID(),
                "title" => "Move",
                "type"  => "Move",
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
     * @param string $return_style
     * @return string $path_html
     */
    public function getBreadcrumbsByFolderID($path = array(), $return_style = "UL") {

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

            if ($return_style === "UL") {
                return $this->exportFormattedFolders($path);
            } else {
                return $path;
            }

        } else {
            $path[] = array(
                "folder-id" => $this->getFolderID(),
                "folder-title" => $this->getFolderTitle()
            );

            $parent_folder = Models_Exam_Bank_Folders::fetchRowByID($this->parent_folder_id);

            if (isset($parent_folder) && is_object($parent_folder)) {
                return $parent_folder->getBreadcrumbsByFolderID($path, $return_style);
            }
            return false;
        }
    }

    /**
     * @param array $path
     * @return string $path_html
     */
    public function exportFormattedFolders($path) {
        $path_html = "";
        // sorts the path array backwards
        // and generates the breadcrumb list
        if (is_array($path)) {
            krsort($path);
            $last_row = false;
            $path_html .= "<ul class=\"question-bank-breadcrumbs\">";
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
    }
}