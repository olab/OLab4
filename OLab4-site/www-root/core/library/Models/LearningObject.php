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
 * Model for a learning object.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Travis Obregon <travismobregon@gmail.com>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 *
 */

class Models_LearningObject extends Models_Base {
    public $learning_object_id,
        $title,
        $description,
        $primary_usage,
        $tool,
        $object_type,
        $url,
        $filename,
        $filename_hashed,
        $screenshot_filename,
        $viewable_start,
        $viewable_end,
        $deleted_date,
        $created_date,
        $created_by,
        $updated_date,
        $updated_by;

    protected static $table_name = "learning_objects";
    protected static $primary_key = "learning_object_id";
    protected static $default_sort_column = "title";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->learning_object_id;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getPrimaryUsage() {
        return $this->primary_usage;
    }

    public function getTool() {
        return $this->tool;
    }

    public function getObjectType() {
        return $this->object_type;
    }

    public function getUrl() {
        return $this->url;
    }

    public function getFilename() {
        return $this->filename;
    }

    public function getFilenameHashed() {
        return $this->filename_hashed;
    }

    public function getScreenshotFilename() {
        return $this->screenshot_filename;
    }

    public function getViewableStart() {
        return $this->viewable_start;
    }

    public function getViewableEnd() {
        return $this->viewable_end;
    }

    public function getDeletedDate() {
        return $this->deleted_date;
    }

    public function getCreatedDate() {
        return $this->created_date;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function getAuthors() {
        $return = "";
        $counter = 0;
        $authors = Models_LearningObject_Author::fetchAllByLearningResourceID($this->learning_object_id);
        $count = count($authors);

        foreach ($authors as $author) {
            if ($count == 1) {
                $return .= Models_LearningObject_Author::getAuthorName($author->getAuthorID(), $author->getAuthorType());
                break;
            } elseif ($counter == $count - 1) {
                $format = " and %s";
            } elseif ($counter == 0) {
                $format = "%s";
            } else {
                $format = ", %s";
            }

            $return .= sprintf($format, Models_LearningObject_Author::getAuthorName($author->getAuthorID(), $author->getAuthorType()));
            $counter++;
        }

        return $return;
    }

    public static function fetchRowByID($learning_object_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "learning_object_id", "value" => $learning_object_id, "method" => "=")
        ));
    }

    public static function fetchActiveResources($search_value = "", $offset = 0, $limit = 50, $object_type = "") {
        global $db;
        $learning_objects = false;

        $query = " SELECT a.`learning_object_id`, a.`title`, a.`description`, a.`primary_usage`, a.`url`, a.`screenshot_filename`, a.`updated_date`, a.`object_type`
                   FROM `learning_objects` AS a
                   WHERE a.`deleted_date` IS NULL
                       AND
                       (
                           (
                               a.`title` LIKE (" . $db->qstr("%" . $search_value . "%") . ")
                               OR a.`description` LIKE (" . $db->qstr("%" . $search_value . "%") . ")
                           )
                       )
                       AND a.`object_type` LIKE (" . $db->qstr("%" . $object_type . "%") . ")
                   ORDER BY a.`title`
                   LIMIT " . (int) $offset . ", " . (int) $limit;

        $results = $db->GetAll($query);
        if ($results) {
            foreach ($results as $learning_object) {
                $learning_objects[] = new self($learning_object);
            }
        }

        return $learning_objects;
    }

    public static function countAllResources($search_value = "", $object_type = "") {
        global $db;
        $total_learning_objects = 0;

        $query = " SELECT COUNT(a.`learning_object_id`) AS `total_learning_objects`
                   FROM `learning_objects` AS a
                   WHERE a.`deleted_date` IS NULL
                   AND
                   (
                       (
                           a.`title` LIKE (" . $db->qstr("%" . $search_value . "%") . ")
                           OR a.`description` LIKE (" . $db->qstr("%" . $search_value . "%") . ")
                       )
                   )
                   AND a.`object_type` LIKE (" . $db->qstr("%" . $object_type . "%") . ")";

        $result = $db->GetOne($query);
        if ($result) {
            $total_learning_objects = $result;
        }

        return $total_learning_objects;
    }
}