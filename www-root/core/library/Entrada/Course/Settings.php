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
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <simpson@queensu.ca>
 * @copyright Copyright 2018 Queen's University. All Rights Reserved.
 */

class Entrada_Course_Settings {
    /**
     * @var int
     */
    protected $course_id = 0;

    /**
     * @var int
     */
    protected $organisation_id = 0;

    /**
     * Entrada_Course_Settings constructor.
     * @param $course_id
     * @param int $organisation_id
     * @param int $proxy_id
     */
    public function __construct($course_id, $organisation_id = 0, $proxy_id = 0) {
        /*
         * Always required.
         */
        $course_id = (int) $course_id;
        if ($course_id) {
            $this->course_id = $course_id;
        }

        /*
         * Only required for writing settings.
         */
        $organisation_id = (int) $organisation_id;
        if ($organisation_id) {
            $this->organisation_id = $organisation_id;
        }

        /*
         * Only required for writing settings.
         */
        $proxy_id = (int) $proxy_id;
        if ($proxy_id) {
            $this->proxy_id = $proxy_id;
        }
    }

    /**
     * Method reads and return course_setting values.
     * @param string $shortname
     * @return bool
     */
    public function read($shortname = "")
    {
        $course_id = (int) $this->course_id;
        if ($course_id && $shortname) {
            $result = Models_Course_Setting::fetchRowByCourseIDShortname($course_id, $shortname);
            if ($result) {
                return $result->getValue();
            }
        }

        return false;
    }

    /**
     * Method reads and return course_setting values.
     * @param string $shortname
     * @return bool
     */
    public function write($shortname = "", $value = "")
    {
        $course_id = (int) $this->course_id;
        $organisation_id = (int) $this->organisation_id;
        $proxy_id = (int) $this->proxy_id;

        if ($course_id && $shortname) {
            $course_setting = Models_Course_Setting::fetchRowByCourseIDShortname($course_id, $shortname);
            if ($course_setting && ($csetting_id = $course_setting->getID())) {
                $record = array(
                    "value" => $value,
                    "updated_date" => time(),
                    "updated_by" => $proxy_id,
                );

                if ($course_setting->fromArray($record)->update()) {
                    return true;
                }
            } else {
                $record = array(
                    "csetting_id" => $csetting_id,
                    "course_id" => $course_id,
                    "organisation_id" => $organisation_id,
                    "shortname" => $shortname,
                    "value" => $value,
                    "created_date" => time(),
                    "created_by" => $proxy_id,
                );
                $course_setting = new Models_Course_Setting($record);
                if ($course_setting->insert()) {
                    return true;
                }
            }
        }

        return false;
    }
}