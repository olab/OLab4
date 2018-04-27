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
 * A model for handling gradebook assessment options
 *
 * @author Organisation: Queen's University
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Models_Gradebook_Assessment_Option extends Models_Base {
    protected $aoption_id,
        $assessment_id,
        $option_id,
        $option_active;

    protected $meta_option_Lu;

    protected static $database_name = DATABASE_NAME;
    protected static $table_name    = "assessment_options";
    protected static $default_sort_column = "assessment_id";
    protected static $primary_key   = "aoption_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getID() {
        return $this->aoption_id;
    }

    public function getAoptionID() {
        return $this->aoption_id;
    }

    public function getAssessmentID() {
        return $this->assessment_id;
    }

    public function getOptionID() {
        return $this->option_id;
    }

    public function getOptionActive() {
        return $this->option_active;
    }

    public function setOptionActive($active) {
        $this->option_active = $active;
    }

    /* @return bool|Models_Gradebook_Assessment_LuMeta_Option */
    public function getGradeBookMetaOptionLu() {
        if (NULL === $this->meta_option_Lu) {
            $this->meta_option_Lu = Models_Gradebook_Assessment_LuMeta_Option::fetchRowByID($this->option_id);
        }

        return $this->meta_option_Lu;
    }

    /*
     *
     * this function returns an array of assessment options id's for an array of Models_Gradebook_Assessment_Option
     */
    public static function getAssessmentOptionIDs($assessment_id_array, $implode = true, $array_key = false) {

        if (isset($assessment_id_array)) {
            if (is_array($assessment_id_array)) {
                $ids = array();
                foreach ($assessment_id_array as $assessment_obj) {
                    if (is_object($assessment_obj)) {
                        if ($array_key == true) {
                            $ids[$assessment_obj->getOptionID()] = $assessment_obj->getAoptionID();
                        } else {
                            $ids[] = $assessment_obj->getAoptionID();
                        }
                    }
                }
                if ($implode == true) {
                    $ids_implode = implode(",", $ids);
                    return $ids_implode;
                } else {
                    return $ids;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /* @return bool|Models_Gradebook_Assessment_Option */
    public static function fetchRowByID($aoption_id, $option_active) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "aoption_id", "value" => $aoption_id, "method" => "="),
            array("key" => "option_active", "value" => $option_active, "method" => "=")
        ));
    }

    /* @return bool|Models_Gradebook_Assessment_Option */
    public static function fetchRowByAssessmentIDOptionID($assessment_id, $option_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "assessment_id", "value" => $assessment_id, "method" => "="),
            array("key" => "option_id", "value" => $option_id, "method" => "=")
        ));
    }

    /* @return ArrayObject|Models_Gradebook_Assessment_Option[] */
    public static function fetchAllByAssessmentID($assessment_id, $option_active = null) {
        $self = new self();

        $active_query = $option_active ? array("key" => "option_active", "value" => $option_active, "method" => "=") : null;

        return $self->fetchAll(array(
            array("key" => "assessment_id", "value" => $assessment_id, "method" => "="),
            $active_query
        ));
    }
}