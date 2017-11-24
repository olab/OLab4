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
 * Model for handling notifications for assessments
 *
 * @author Organisation: Queen's University
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */

class Models_Gradebook_Assessment_Graders extends Models_Base
{
    protected $ag_id, $assessment_id, $proxy_id, $grader_proxy_id;

    protected static $table_name = "assessment_graders";
    protected static $primary_key = "ag_id";
    protected static $default_sort_column = "assessment_id";

    /**
     * This function delete all the graders - learners association for an assessment
     * for the specified curriculum period.
     *
     * @param $assessment_id
     * @return bool
     */
    public static function deleteByAssessment($assessment_id) {
        global $db;

        $query = "DELETE FROM `assessment_graders`
                    WHERE `assessment_id` = ?";

        $result = $db->Execute($query, array($assessment_id));

        if ($result) {
            return $result;
        }

        return false;
    }

    /**
     * This function returns the list of the associated graders for an assessment for the
     * specified curriculum period.
     *
     * @param $assessment_id
     */
    public static function fetchGradersIdsByAssessment($assessment_id)
    {
        global $db;

        $query = "  SELECT DISTINCT grader_proxy_id 
                    FROM `assessment_graders`
                    WHERE `assessment_id` = ?";

        return $db->getCol($query, array($assessment_id));
    }

    /**
     * This function returns a list of learner(s) number for an assessment for the
     * specified curriculum period and grader proxy id.
     *
     * @param $assessment_id
     * @param $grader_proxy_id
     * @return mixed
     */
    public static function fetchLearnersByAssessmentGrader($assessment_id, $grader_proxy_id) {
        global $db;

        $query = "  SELECT DISTINCT t1.id 
                    FROM `".AUTH_DATABASE."`.`user_data` AS t1, `assessment_graders` AS t2
                    WHERE t1.`id`=t2.`proxy_id` 
                    AND t2.`assessment_id` = ?
                    AND t2.`grader_proxy_id` = ?";

        return $db->getCol($query, array($assessment_id, $grader_proxy_id));
    }

    /**
     * This function returns a list of learner(s) proxy_id for an assessment for the
     * specified curriculum period and grader proxy id.
     *
     * @param $assessment_id
     * @param $grader_proxy_id
     * @return mixed
     */
    public static function fetchLearnersProxyIdByAssessmentGrader($assessment_id, $grader_proxy_id = 0) {
        global $db;

        $constraints = array($assessment_id);

        $grader_sql = "";
        if ($grader_proxy_id) {
            $constraints[] = $grader_proxy_id;
            $grader_sql = "AND t2.`grader_proxy_id` = ?";
        }

        $query = "  SELECT DISTINCT t1.id 
                    FROM `".AUTH_DATABASE."`.`user_data` AS t1, `assessment_graders` AS t2
                    WHERE t1.`id`=t2.`proxy_id` 
                    AND t2.`assessment_id` = ?
                    ".$grader_sql;

        return $db->getCol($query, $constraints);
    }

    /**
     * Fetch the Graders associated with an assessement for the specified
     * curriculum period.
     *
     * @param $assessment_id
     * @param $course_id
     * @return array
     */
    public static function fetchGradersForGradersList($assessment_id, $course_id) {
        $graders_id = self::fetchGradersIdsByAssessment($assessment_id);

        $graders = array();

        foreach ($graders_id as $proxy_id) {
            $user = Models_User::fetchRowByID($proxy_id);
            $contact = Models_Course_Contact::fetchByProxyAndCourse($proxy_id, $course_id);
            if ($contact) {
                $user->role = $contact[0]->getContactType();
            } else {
                $user->role = '';
            }

            $graders[] = $user;
        }

        return $graders;
    }

    /**
     * This method remove a grader from an assessment
     * 
     * @param $grader_proxy_id
     * @param $assessment_id
     * @return mixed
     */
    public static function deleteGraderForAssessment($grader_proxy_id, $assessment_id) {
        global $db;

        $query = "DELETE FROM `assessment_graders`
                  WHERE `assessment_id` = ?
                  AND `grader_proxy_id` = ?";

        return $db->Execute($query, array($assessment_id, $grader_proxy_id));
    }

    /**
     * This methods checks if the users specified by proxy_id can mark/grade
     * the and assessment.
     * 
     * @param $proxy_id
     * @param $assessment_id
     * @return bool
     */
    public static function canGradeAssessment($proxy_id, $assessment_id) {
        global $db, $ENTRADA_USER;

        if (!$assessment_id || !$proxy_id) {
            return false;
        }
        
        $assessment = Models_Gradebook_Assessment::fetchRowByID($assessment_id);
        
        if (!$assessment) {
            return false;
        }

        /**
         * If current Entrada user is admin
         */
        if ($ENTRADA_USER->getActiveRole()=="admin") {
            return true;
        }

        /**
         * If current Entrada user is a director for the course associated with
         * that assessment.
         */
        $contacts = Models_Course_Contact::fetchByProxyAndCourse($proxy_id, $assessment->getCourseID());
        if ($contacts && is_array($contacts)) {
            foreach ($contacts as $contact) {
                if ($contact->getContactType() == "director") {
                    return true;
                }
            }
        }

        /**
         * If it's a self assessment, check if proxy_id = current Entrada user id
         */
        if($assessment->getSeflAssessment()) {
            return $proxy_id === $ENTRADA_USER->getActiveId();
        }

        /**
         * At last, check at the assignment level for a grader
         */
        $query = "SELECT count(*) 
                  FROM `assessment_graders` 
                  WHERE `grader_proxy_id` = ?
                  AND `proxy_id` = ?
                  AND `assessment_id` = ?";
        
        $is_grader = $db->getOne($query, array($ENTRADA_USER->getActiveId(), $proxy_id, $assessment_id));

        return $is_grader;
    }
}