<?php
/**
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 */

class Views_Exam_Exam_Sort extends Views_Deprecated_Base {
    protected $field, $sort_direction;

    public function __construct($field, $sort_direction) {
        $this->field = $field;
        $this->sort_direction = $sort_direction;
    }

    public function sort_field_numeric($array) {
        usort($array, array('Views_Exam_Exam_Sort', 'sort'));
        return $array;
    }

    public function sort_field_alpha($array) {
        usort($array, array('Views_Exam_Exam_Sort', 'sort_alpha'));
        return $array;
    }

    public function sort_alpha($a, $b) {
        $field = $this->field;
        $sort_direction = $this->sort_direction;

        $compare = strcmp($a[$field], $b[$field]);
        if ($sort_direction === "asc") {
            return $compare;
        } else {
            return $compare * -1;
        }
    }

    public function sort($a, $b) {
        $field = $this->field;
        $sort_direction = $this->sort_direction;

        if ($a[$field] == $b[$field]) return 0;

        if ($sort_direction === "asc") {
            return ($a[$field] > $b[$field]) ? 1 : -1;
        } else {
            return ($a[$field] < $b[$field]) ? 1 : -1;
        }
    }
}