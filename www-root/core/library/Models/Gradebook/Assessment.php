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
 * A model to handle gradebook assessments.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */
class Models_Gradebook_Assessment extends Models_Base {

    protected $assessment_id, $course_id, $cohort, $cperiod_id, $collection_id, $form_id, $name, $description, $type,
        $marking_scheme_id, $numeric_grade_points_total, $grade_weighting = 0,
        $narrative = 0, $required = 1, $characteristic_id, $show_learner = 0, $due_date = 0, $self_assessment = 0, 
        $group_assessment = 0, $release_date = 0, $release_until = 0, $order, $grade_threshold = 0, $notify_threshold = 0, $active = 1,
        $created_date, $created_by, $updated_date, $updated_by, $published = 1;
    
    protected static $table_name          = "assessments";
    protected static $default_sort_column = "order";
    protected static $primary_key         = "assessment_id";
    
    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }

    public static function fetchRowByID($assessment_id, $active = 1) {
        $self = new self();
        return $self->fetchRow(array(
                array("key" => "assessment_id", "value" => $assessment_id, "method" => "=", "mode" => "AND"),
                array("key" => "active", "value" => $active, "method" => "=", "mode" => "AND")
            )
        );
    }

    public static function fetchAllRecords($course_id = NULL, $cperiod_id = NULL, $active = 1, $sort_col = NULL, $sort_order = NULL) {
        $self = new self();

        $constraints = array(
            array(
                "mode"      => "AND",
                "key"       => "active",
                "value"     => $active,
                "method"    => "="
            )
        );

        if (!is_null($cperiod_id)) {
            $constraints[] = array(
                "mode"      => "AND",
                "key"       => "cohort",
                "value"     => $cperiod_id,
                "method"    => "="
            );
        }

        if (!is_null($course_id)) {
            $constraints[] = array(
                "mode"      => "AND",
                "key"       => "course_id",
                "value"     => $course_id,
                "method"    => "="
            );
        }

        $objs = $self->fetchAll($constraints, "=", "AND", $sort_col, $sort_order);
        $output = array();

        if (!empty($objs)) {
            foreach ($objs as $o) {
                $output[] = $o;
            }
        }

        return $output;
    }

    public function getAssessmentID() {
        return $this->assessment_id;
    }

    public function getCourseID() {
        return $this->course_id;
    }

    public function getCohort() {
        return $this->cohort;
    }

    public function getCurriculumPeriodID() {
        return $this->cperiod_id;   
    }

    public function setCurriculumPeriodID($cperiod_id) {
        $this->cperiod_id = $cperiod_id;
    }

    public function getCollectionID() {
        return $this->collection_id;
    }

    public function setCollectionID($collection_id) {
        $this->collection_id = $collection_id;
    }

    public function getFormID() {
        return $this->form_id;
    }

    public function setFormID($form_id) {
        $this->form_id = $form_id;
    }

    public function getName() {
        return $this->name;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getType() {
        return $this->type;
    }

    public function getMarkingSchemeID() {
        return $this->marking_scheme_id;
    }

    public function getNumericGradePointsTotal() {
        return $this->numeric_grade_points_total;
    }

    public function getGradeWeighting() {
        return $this->grade_weighting;
    }

    public function getNarrative() {
        return $this->narrative;
    }

    public function getSeflAssessment() {
        return $this->self_assessment;
    }

    public function getGroupAssessment() {
        return $this->group_assessment;
    }

    public function setGroupAssessment($group_assessment) {
        $this->group_assessment = $group_assessment;
    }

    public function getRequired() {
        return $this->required;
    }

    public function getCharacteristicID() {
        return $this->characteristic_id;
    }

    public function getShowLearner() {
        return $this->show_learner;
    }

    public function getDueDate() {
        return $this->due_date;
    }

    public function getReleaseDate() {
        return $this->release_date;
    }

    public function getReleaseUntil() {
        return $this->release_until;
    }

    public function getOrder() {
        return $this->order;
    }

    /**
     * Set the order in which the assessment should appear
     * @param int $order 
     */
    public function setOrder($order) {
        $this->order = $order;
    }

    public function getGradeThreshold() {
        return $this->grade_threshold;
    }

    public function getActive() {
        return $this->active;
    }

    /**
     * Set the active flag. 
     * @param int $active 1|0
     */
    public function setActive($active) {
        $this->active = $active;
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

    public function fetchAssessmentByIDWithMarkingScheme() {
        global $db;

        $query = "SELECT * FROM `".DATABASE_NAME."`.`".static::$table_name."` a
                    INNER JOIN `".DATABASE_NAME."`.`assessment_marking_schemes` b
                    ON a.marking_scheme_id = b.id
                    WHERE a.assessment_id = ?";

        $result = $db->getRow($query, $this->assessment_id);

        if ($result) {
            return $result;
        }

        return false;
    }

    /**
     * Returns an assessment with marking scheme, meta info and assignment (if one is attached)
     * @return array|false
     */
    public function fetchAssessmentByIDWithMarkingSchemeMetaAndAssignment() {
        global $db;

        $query = "SELECT a.*, b.`id` as `marking_scheme_id`, b.`handler`, b.`description` as `marking_scheme_description`, c.`type` as `assessment_type`, d.start_date, d.finish_date, e.`assignment_id`, e.`notice_id`, e.`assignment_title`, e.`due_date`
                    FROM `".static::$table_name."` a
                    LEFT JOIN `assessment_marking_schemes` b
                    ON b.`id` = a.`marking_scheme_id`
                    LEFT JOIN `assessments_lu_meta` c
                    ON c.`id` = a.`characteristic_id`
                    LEFT JOIN `curriculum_periods` d
                    ON a.cperiod_id = d.cperiod_id
                    LEFT JOIN `assignments` e
                    ON a.assessment_id = e.assessment_id
                    WHERE a.`assessment_id` = ?
                    AND a.`active` = '1'";
        
        $result = $db->getRow($query, $this->assessment_id);

        if ($result) {
            return $result;
        }

        return false;
    }

    /**
     * DEPRECATED. The Views information is no longer used in Entrada. use fetchAssessmentsByCurriculumPeriodIDWithAssignments() instead
     * @TODO REMOVE in release 1.10
     * Returns a list of assessments, with any attached assignments and number of views that assessment received
     * @param  int    $course_id
     * @param  int    $cperiod_id
     * @return array|false
     */
    public function fetchAssessmentsByCurriculumPeriodIDWithAssignmentsAndViews($course_id, $cperiod_id, $search = '', $view = false) {
        global $db;

        if ($view) {
            $query = "SELECT a.*, b.assignment_id, b.assignment_title, COUNT(c.`statistic_id`) AS `views`, d.`title`, d.`description` AS `desc` FROM `".DATABASE_NAME."`.`".static::$table_name."` a
                    LEFT JOIN `".DATABASE_NAME."`.`assignments` b 
                    ON a.`".static::$primary_key."` = b.`".static::$primary_key."`
                    LEFT JOIN `".DATABASE_NAME."`.`assessment_collections` d
                    ON a.`collection_id` = d.`collection_id` 
                    LEFT JOIN `".DATABASE_NAME."`.`statistics` c
                    ON c.`action_value` = a.`".static::$primary_key."`
                    AND c.module = 'gradebook'
                    AND c.action = 'view'
                    AND c.action_field = '".static::$primary_key."'
                    WHERE a.course_id = ?
                    AND a.cperiod_id = ?
                    AND a.active = 1
                    GROUP BY a.`".static::$primary_key."`";
        } else {
            $query = "SELECT a.*, b.assignment_id, b.assignment_title AS `views`, d.`title`, d.`description` AS `desc` FROM `".DATABASE_NAME."`.`".static::$table_name."` a
                    LEFT JOIN `".DATABASE_NAME."`.`assignments` b 
                    ON a.`".static::$primary_key."` = b.`".static::$primary_key."`
                    LEFT JOIN `".DATABASE_NAME."`.`assessment_collections` d
                    ON a.`collection_id` = d.`collection_id` 
                    WHERE a.course_id = ?
                    AND a.cperiod_id = ?
                    AND a.active = 1
                    GROUP BY a.`".static::$primary_key."`";
        }
      
        if (!empty($search)) {

            $terms = explode(" ", $search);
            $clauses = array();

            foreach ($terms as $term) {
                $clauses[] = "a.`name` LIKE '%" . $term . "%'";
            }

            $query .= " HAVING (" . implode(" OR ",$clauses) . ")";
        }

        $query .= " ORDER BY a.`".static::$default_sort_column."` ASC";

        $result = $db->GetAll($query, array($course_id, $cperiod_id));
        if ($result) {
            return $result;
        }
        
        return false;
    }

    /**
     * Returns a list of assessments, with any attached assignments
     * @param  int    $course_id
     * @param  int    $cperiod_id
     * @param  string $search
     * @return array|false
     */
    public function fetchAssessmentsByCurriculumPeriodIDWithAssignments($course_id, $cperiod_id, $search = "") {
        global $db;

        $query = "SELECT a.*, b.`assignment_id`, b.`assignment_title`, d.`title`, d.`description` AS `desc` 
                FROM `".DATABASE_NAME."`.`".static::$table_name."` a
                LEFT JOIN `".DATABASE_NAME."`.`assignments` b 
                ON a.`".static::$primary_key."` = b.`".static::$primary_key."`
                AND b.`assignment_active` = 1
                LEFT JOIN `".DATABASE_NAME."`.`assessment_collections` d
                ON a.`collection_id` = d.`collection_id` 
                WHERE a.course_id = ?
                AND a.cperiod_id = ?
                AND a.active = 1
                GROUP BY a.`".static::$primary_key."`";

        if (!empty($search)) {

            $terms = explode(" ", $search);
            $clauses = array();

            foreach ($terms as $term) {
                $clauses[] = "a.`name` LIKE '%" . $term . "%'";
            }

            $query .= " HAVING (" . implode(" OR ",$clauses) . ")";
        }

        $query .= " ORDER BY a.`".static::$default_sort_column."` ASC";

        $result = $db->GetAll($query, array($course_id, $cperiod_id));
        if ($result) {
            return $result;
        }

        return false;
    }

    /**
     * Gets a list of assessments for a given course and cperiod, with joined marking scheme handler 
     * @param  int $course_id
     * @param  int $cperiod_id
     * @return array|false
     */
    public function fetchAssessmentsByCurriculumPeriodIDWithMarkingScheme($course_id, $cperiod_id) {
        global $db;

        $query = "SELECT a.*, c.assignment_id, b.handler AS `handler` FROM `".DATABASE_NAME."`.`".static::$table_name."` a
                    LEFT JOIN `".DATABASE_NAME."`.`assessment_marking_schemes` b 
                    ON a.marking_scheme_id = b.id
                    LEFT JOIN `".DATABASE_NAME."`.`assignments` c 
                    ON a.`".static::$primary_key."` = c.`".static::$primary_key."`
                    WHERE a.course_id = ?
                    AND a.cperiod_id = ?
                    AND a.active = 1
                    ORDER BY a.`".static::$default_sort_column."` ASC";
        $result = $db->getAll($query, array($course_id, $cperiod_id));

        if ($result) {
            return $result;
        } else {
            return false;
        }
    }

    /**
     * Query to select all marks for a given list of students and list of assessments
     * @param  array        $assessments   
     * @param  array        $student_ids
     * @param  string       $first_column   "number" or "fullname"
     * @param  bool         $get_grade_id   specify whether to select due date in query
     * @param  bool         $get_proxy_id   specify whetehr to select proxy_id in query
     * @return array|false                  
     */
    public function fetchStudentMarksAndGradeWeightingsPerAssessment($assessments, $student_ids, $first_column = "number", $get_grade_id = false, $get_proxy_id = false) {
        global $db;

        $fullname_select = "CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`";
        $number_select = "a.number";

        // Query gets built as array entries, then concatenated at the end
        // 
        $fields = $first_column == "number" ? $number_select . ", " . $fullname_select : $fullname_select . ", " . $number_select;
        $query = array("SELECT " . $fields . ",");

        if ($get_proxy_id) {
            $query[] = "a.id,";
        }

        // Get assignment due date
        if ($get_grade_id) {
            foreach ($assessments as $i => $assessment) {
                $query[] = "b".$i.".grade_id as `b".$i."grade_id`,"; // b1.grade_id as `b1grade_id`,
            }
        }

        // add result fields
        foreach ($assessments as $i => $assessment) {
            $query[] = "b".$i.".value as `b".$i."grade`,"; // b1.value as `b1result`,
        }

        // add grade_weighting fields
        foreach ($assessments as $i => $assessment) {
            $display_comma = $i + 1 == count($assessments) ? '' : ',';

            $query[] = "c".$i.".`grade_weighting` as `c".$i."weight`".$display_comma; //c1.value as `c1weight`,
        }

        // select from user_data table
        $query[] = "FROM `".AUTH_DATABASE."`.`user_data` a";

        // left joins for student marks
        foreach ($assessments as $i => $assessment) {
            $query[] = "LEFT JOIN `".DATABASE_NAME."`.`assessment_grades` AS b".$i." ON a.id = b".$i.".proxy_id and b".$i.".assessment_id = ".$assessment['assessment_id'];
        }

        // left joins for grade weightings exceptions
        foreach ($assessments as $i => $assessment) {
            $query[] = "LEFT JOIN `".DATABASE_NAME."`.`assessment_exceptions` AS c".$i." ON a.id = c".$i.".proxy_id and c".$i.".assessment_id = ".$assessment['assessment_id'];
        }

        // implode student id array into comma-separated list
        $student_ids = implode(",", array_map(array($db, 'qstr'), $student_ids));

        // where id within selected student ids
        $query[] = "WHERE a.id IN (".$student_ids.")";

        // order by last name
        $query[] = "ORDER BY `fullname` ASC";

        // implode query to string
        $query = implode("\n", $query);

        // run query
        $result = $db->getAll($query);

        if ($result) {
            return $result;
        } else {
            return false;
        }
    }

    /**
     * Fetch all students and their respective group for a given assessment
     * @return array|false
     */
    public function fetchAudienceInGroups() {
        global $db;

        $query = "SELECT * from `".DATABASE_NAME."`.`course_group_audience` a
                    JOIN `".DATABASE_NAME."`.`course_groups` b
                    ON a.cgroup_id = b.cgroup_id
                    JOIN `".DATABASE_NAME."`.`assessment_groups` c
                    ON a.cgroup_id = c.cgroup_id
                    WHERE c.assessment_id = ?";

        $results = $db->getAll($query, array($this->assessment_id));

        if ($results) {
            return $results;
        }

        return false;
    }

    public function fetchGradeWeightingExceptions() {
        global $db;

        $query = "SELECT a.`id` AS `proxy_id`, CONCAT_WS(', ', a.`lastname`, a.`firstname`) AS `fullname`, b.aexception_id, b.`grade_weighting`
                    FROM `".AUTH_DATABASE."`.`user_data` AS a
                    JOIN `assessment_exceptions` AS b
                    ON a.`id` = b.`proxy_id`
                    AND b.`assessment_id` = ?
                    ORDER BY a.`lastname`;";

        $results = $db->getAll($query, $this->assessment_id);

        if ($results) {
            return $results;
        }

        return false;
    }

    public static function fetchAssessmentsInIDArray($assessment_ids = array(), $course_id = NULL, $cperiod_id = NULL, $active = 1, $sort_col = NULL, $sort_order = NULL) {
        global $db;

        // Create comma-separated list from $db->qstr sanitized array
        $assessment_id_list = implode(",", array_map(array($db, 'qstr'), $assessment_ids));


        $query = "SELECT * FROM `".DATABASE_NAME."`.`".static::$table_name."` a
                    WHERE a.`assessment_id` IN (" . $assessment_id_list . ")";

        $results = $db->getAll($query);

        if ($results) {
            $output = array();

            foreach ($results as $result) {
                $class = get_called_class();
                $output[] = new $class($result);
            }

            return $output;
        } else {
            return false;
        }
    }

    public static function fetchAssessmentsByCollectionIds($collection_ids = array()) {
        global $db;

        if (empty($collection_ids)) {
            return false;
        }

        // Create comma-separated list from $db->qstr sanitized array
        $collection_id_list = implode(",", array_map(array($db, 'qstr'), $collection_ids));

        $query = "SELECT * FROM `".DATABASE_NAME."`.`".static::$table_name."` a
                    WHERE a.`collection_id` IN (" . $collection_id_list . ")";

        $results = $db->getAll($query);

        if ($results) {
            $output = array();

            foreach ($results as $result) {
                $class = get_called_class();
                $output[] = new $class($result);
            }
            return $output;
        } else {
            return false;
        }
    }

    public static function fetchNextOrder($course_id, $group_id = NULL, $active = 1) {
        global $db;

        $query = "SELECT MAX(`order`) + 1
                    FROM `assessments`
                    WHERE `course_id` = ?
                    AND `cohort` = ?
                    AND `active` = ?";
        $result = $db->getOne($query, array($course_id, $group_id, $active));
        
        if ($result) {
            return $result;
        } else {
            return "0";
        }
    }

    public function insert() {
        global $db;

        if ($db->AutoExecute(static::$table_name, $this->toArray(), "INSERT")) {
            $this->assessment_id = $db->Insert_ID();
            return $this;
        } else {
            return false;
        }

    }

    /**
     * Same as insert method, but removes the assessment_id if one exists, because having an ID
     * stops the insert from taking place.
     * This method was created to avoid breaking any existing functionality.
     * If found that it does not, merge into insert() method.
     * @return object|false $this
     */
    public function insertRemoveID() {
        global $db;

        // Convert object to array
        $assessment = $this->toArray();

        // Remove assessment_id as this prevents insert from taking place
        if (isset($assessment['assessment_id'])) {
            unset($assessment['assessment_id']);
        }

        // Insert to db
        if ($db->AutoExecute(static::$table_name, $assessment, "INSERT")) {
            $this->assessment_id = $db->Insert_ID();
            return $this;
        } else {
            return false;
        }

    }

    /**
     * Updates the database with values in the model. By default updates all values regardless of null status. 
     * Optional parameter allows to specify which values will get updated.
     * @param  array|null $fields_to_update Ex. array('assessment_id', 'order')
     * @return $this 
     */
    public function update($fields_to_update = null) {
        global $db;

        // if fields_to_update is not set or not an array, use the standard toArray()
        if (!$fields_to_update || !is_array($fields_to_update)) {
            $update_array = $this->toArray();
        } else {
            $update_array = array();

            foreach($fields_to_update as $field) {
                $update_array[$field] = $this->$field;
            }
        }

        if ($db->AutoExecute(static::$table_name, $update_array, "UPDATE", "`assessment_id` = ".$this->assessment_id)) {
            return $this;
        } else {
            return false;
        }
    }

    public function attachQuizzes($quiz_ids) {
        global $db, $ENTRADA_USER, $ENTRADA_ACL;

        if (!$this->assessment_id) {
            return false;
        }

        if (!is_array($quiz_ids) || (!count($quiz_ids))) {
            return false;
        }

        foreach ($quiz_ids as $quiz_id) {
            $query = "  SELECT a.*, c.`content_type`, c.`content_id`, h.`member_acl` AS `community_admin`, e.`course_id`, i.`organisation_id` 
                        FROM `quizzes` AS a 
                        LEFT JOIN `quiz_contacts` AS b 
                        ON a.`quiz_id` = b.`quiz_id` 
                        JOIN `attached_quizzes` AS c 
                        ON a.`quiz_id` = c.`quiz_id` 
                        LEFT JOIN `assessment_attached_quizzes` AS d 
                        ON d.`assessment_id` = ".$db->qstr($this->assessment_id)."
                        AND d.`aquiz_id` = c.`aquiz_id` 
                        LEFT JOIN `events` AS e 
                        ON c.`content_type` = 'event' 
                        AND c.`content_id` = e.`event_id` 
                        LEFT JOIN `community_pages` AS f 
                        ON c.`content_type` = 'community_page' 
                        AND c.`content_id` = f.`cpage_id` 
                        LEFT JOIN `communities` AS g 
                        ON f.`community_id` = g.`community_id` 
                        LEFT JOIN `community_members` AS h 
                        ON g.`community_id` = h.`community_id` 
                        AND h.`proxy_id` = ".$db->qstr($ENTRADA_USER->getID())." 
                        AND h.`member_active` = 1 
                        LEFT JOIN `courses` AS i 
                        ON e.`course_id` = i.`course_id` 
                        WHERE c.`aquiz_id` = ".$db->qstr($quiz_id)." 
                        AND d.`aquiz_id` IS NULL 
                        GROUP BY a.`quiz_id`";

            $quiz = $db->GetRow($query);
            if ($quiz) {
                if ((($quiz["content_type"] == "event" && $ENTRADA_ACL->amIAllowed(new EventContentResource($quiz["content_id"], $quiz["course_id"], $quiz["organisation_id"]), "update")) || ($quiz["content_type"] == "community_page" && $quiz["community_admin"]))) {
                    $item["quiz_title"] = $quiz["quiz_title"];
                    $item["aquiz_id"] = $quiz_id;
                } else {
                    add_error("You do not have permission to view the results for the <strong>Quiz</strong> you selected.");
                    return false;
                }
            } 

            $item["assessment_id"]	= (int) $this->assessment_id;
            $item["content_type"]  = "assessment";
            $item["updated_date"]	= time();
            $item["updated_by"]	= $ENTRADA_USER->getID();

            /**
             * Adding this quiz to the selected assessment.
             */
            if (!$db->AutoExecute("assessment_attached_quizzes", $item, "INSERT")) {
                add_error("There was a problem attaching this quiz to <strong>".html_encode($this->name)."</strong>. The system administrator was informed of this error; please try again later.");
                return false;
            }
        }
    }

    public function attachQuizQuestion($question_ids, $aquiz_ids) {
        global $db;

        if (!is_array($question_ids) || !count($question_ids)) {
            return false;
        }

        foreach ($question_ids as $question_id) {
            $query = "SELECT quiz_id FROM quiz_questions WHERE qquestion_id=".$db->qstr($question_id);
            $quiz_id = $db->GetRow($query);

            if ($quiz_id) {
                $query = "SELECT aq.*
                     FROM `attached_quizzes` AS aq
                     JOIN `quizzes` AS b
                     ON aq.`quiz_id` = b.`quiz_id`
                     WHERE aq.`quiz_id`=".$db->qstr($quiz_id["quiz_id"])."
                     AND aq.aquiz_id IN(".implode(",", $aquiz_ids) .")
                     GROUP BY aq.`aquiz_id`";
                $attached_quiz = $db->GetRow($query);

                if (!$attached_quiz) {
                    add_error("The <strong>Attached Quiz</strong> you selected does not exist or is not enabled." . $query);
                    return false;
                }
            } else {
                add_error("The <strong>Quiz</strong> you selected does not exist or is not enabled.");
                return false;
            }
            
            $item["aquiz_id"] = $attached_quiz["aquiz_id"];
            $item["assessment_id"] = $this->assessment_id;
            $item["qquestion_id"] = $question_id;

            if (!$db->AutoExecute("assessment_quiz_questions", $item, "INSERT")) {
                die($db->ErrorMsg());
                add_error("There was a problem attaching this quiz question to <strong>".html_encode($this->name)."</strong>. The system administrator was informed of this error; please try again later.");
                return false;
            }
        }
    }

    public function updateAssignementDueDate() {
        global $db;

        $query = "UPDATE `assignments` SET 
                  due_date = ?
                  WHERE `assessment_id` = ?";

        return $db->Execute($query, array($this->due_date, $this->assessment_id));
    }

    public static function fetchAssessmentsByCourseIdAndGraderId($course_id, $grader_proxy_id) {
        global $db;

        $query = "SELECT a.*, 
                    (SELECT IF( a.due_date !=0, a.due_date, am.due_date )) AS due_date
                  FROM assessments AS a, assessment_graders AS ag, assignments AS am
                  WHERE a.assessment_id=ag.assessment_id 
                  AND a.course_id = ? 
                  AND ag.grader_proxy_id = ?
                  AND a.assessment_id = am.assessment_id 
                  AND a.active=1
                  GROUP BY assessment_id";

        return $db->getAll($query, array($course_id, $grader_proxy_id));
    }

    public function fetchAssessmentIDsByFormID($form_id) {
        global $db;
        $query = "SELECT a.`assessment_id` FROM `assessments` AS a WHERE a.`form_id` = {$db->qstr($form_id)}";
        return $db->GetAll($query);
    }
}
