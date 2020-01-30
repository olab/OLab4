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
 * A model for handling gradebook assessment weighting
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2015 Regents of The University of California. All Rights Reserved.
 */

class Models_Gradebook_Assessment_Weighting extends Models_Base {
    protected   $aexception_id,
                $assessment_id,
                $proxy_id,
                $grade_weighting;

    protected static $table_name = "assessment_exceptions";
    protected static $primary_key = "aexception_id";
    protected static $default_sort_column = "aexception_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getAExceptionID() {
        return $this->aexception_id;
    }

    public function getAssessmentID() {
        return $this->assessment_id;
    }

    public function getProxyID() {
        return $this->proxy_id;
    }

    public function getGradeWeighting() {
        return $this->grade_weighting;
    }

    /* @return bool|Models_Gradebook_Assessment_Weighting */
    public static function fetchRowByID($aexception_id) {
        $self = new self();
        return $self->fetchRow(array(
            array("key" => "aexception_id", "value" => $aexception_id, "method" => "=")
        ));
    }

    /* @return bool|Models_Gradebook_Assessment_Weighting */
    public static function fetchRowByAssessmentIDProxyID($assessment_id, $proxy_id) {
        $self = new self();
        return $self->fetchRow(array(
                array("key" => "assessment_id", "value" => $assessment_id, "method" => "=", "mode" => "AND"),
                array("key" => "proxy_id", "value" => $proxy_id, "method" => "=", "mode" => "AND")
            )
        );
    }

    /* @return ArrayObject|Models_Gradebook_Assessment_Weighting[] */
    public static function fetchAllByAssessmentID($assessment_id) {
        $self = new self();

        $constraints = array(
            array(
                "mode"      => "AND",
                "key"       => "assessment_id",
                "value"     => $assessment_id,
                "method"    => "="
            )
        );

        $objs = $self->fetchAll($constraints, "=", "AND");
        $output = array();

        if (!empty($objs)) {
            foreach ($objs as $o) {
                $output[] = $o;
            }
        }

        return $output;
    }
}