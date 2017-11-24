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
 * A model for handling community courses
 *
 * @author Organization: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Sam Payne <spayne@mednet.ucla.edu>
 * @copyright Copyright 2014 Regents of The University of California. All Rights Reserved.
 */
class Models_Community_Course extends Models_Base {
    protected   $community_course_id,
                $community_id,
                $course_id;

    protected static $table_name = "community_courses";
    protected static $default_sort_column = "community_course_id";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public function getCommunityCourseID() {
        return $this->community_course_id;
    }

    public function getCommunityID() {
        return $this->community_id;
    }

    public function getCourseID() {
        return $this->course_id;
    }

    public function insert() {
        global $db;

        if ($db->AutoExecute($this->table_name, $this->toArray(), "INSERT")) {
            return $this;
        } else {
            return false;
        }
    }

    public static function fetchRowByCourseID($course_id = 0) {
        $self = new self();
        return $self->fetchRow(array("course_id" => $course_id));
    }

    public static function fetchRowByCommunityID($community_id = 0) {
        $self = new self();
        return $self->fetchRow(array("community_id" => $community_id));
    }

    public function fetchRowByCommunityIDProxyID($community_id = 0, $proxy_id) {
        $self = new self();
        return $self->fetchRow(array("community_id" => $community_id, "proxy_id" => $proxy_id));
    }
    /**
     * This function checks if the community is linked to a course
     * @param int $community_id
     * @return bool
     *
     */
    public static function is_community_course($community_id = 0) {
        $isCommunityCourse = false;
        $community_course = self::fetchRowByCommunityID($community_id);
        if ($community_course) {
            if (is_object($community_course) && !empty($community_course)) {
                $isCommunityCourse = true;
            }
        }
        return $isCommunityCourse;
    }

}
