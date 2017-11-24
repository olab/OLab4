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
 * @author Organisation: David Geffen School of Medicine at UCLA
 * @author Unit: Instructional Design and Technology Unit
 * @author Developer: Robert Fotino <robert.fotino@gmail.com>
 * @copyright Copyright 2015 Regents of the University of California. All Rights Reserved.
 */
require_once("Classes/users/UserPhoto.class.php");
require_once("Classes/users/UserPhotos.class.php");

class Models_Exam_Grader extends Models_Base {
    /**
     * Synchronizes the exam_graders table with the array of proxy_ids for
     * the given exam and course group. Returns true on success, false on failure.
     * 
     * @global ADODB $db
     * @param int $post_id
     * @param int $cgroup_id
     * @param array $new_grader_proxy_ids
     * @return boolean
     */
    public static function syncGroup($post_id, $cgroup_id, $new_grader_proxy_ids) {
        global $db;
        $ret = true;
        $old_grader_proxy_ids = static::fetchGraderProxyIds($post_id, $cgroup_id);
        $ids_to_add = array_diff($new_grader_proxy_ids, $old_grader_proxy_ids);
        $ids_to_del = array_diff($old_grader_proxy_ids, $new_grader_proxy_ids);
        $db->StartTrans();
        foreach ($ids_to_add as $proxy_id) {
            $insert_array = array(
                "post_id" => $post_id,
                "cgroup_id" => $cgroup_id,
                "proxy_id" => $proxy_id
            );
            if (!$db->AutoExecute("`exam_graders`", $insert_array, "INSERT")) {
                $db->FailTrans();
                $ret = false;
            }
        }
        foreach ($ids_to_del as $proxy_id) {
            $query = "
                DELETE FROM `exam_graders`
                WHERE `post_id` = ".$db->qstr($post_id)."
                AND `cgroup_id` = ".$db->qstr($cgroup_id)."
                AND `proxy_id` = ".$db->qstr($proxy_id);
            if (!$db->Execute($query)) {
                $db->FailTrans();
                $ret = false;
            }
        }
        $db->CompleteTrans();
        return $ret;
    }

    public static function syncGroupsGrader($post_id, $cgroup_ids, $grader_proxy_id) {
        global $db;
        $ret = true;
        $db->StartTrans();
        foreach ($cgroup_ids as $cgroup_id) {
            $insert_array = array(
                "post_id" => $post_id,
                "cgroup_id" => $cgroup_id,
                "proxy_id" => $grader_proxy_id
            );
            if (!$db->AutoExecute("`exam_graders`", $insert_array, "INSERT")) {
                $db->FailTrans();
                $ret = false;
            }
        }
        $db->CompleteTrans();
        return $ret;
    }

    /**
     * Synchronizes the exam_graders table with the array of proxy_ids for
     * the given exam and given grader. Returns true on success, false on failure.
     * 
     * @global ADODB $db
     * @param int $post_id
     * @param int $proxy_id of the grader
     * @param array $learner_proxy_ids
     * @return boolean
     */
    public static function deleteAllGraderGroupsByPost($post_id) {
        global $db;
        $ret = false;
        if (isset($post_id) && $post_id) {
            
            $query = "
                SELECT `exam_grader_id` FROM `exam_graders`
                WHERE `post_id` = ".$db->qstr($post_id);

            if (count($db->Execute($query)) == 0) {
                $ret = true;
            } else {
                $db->StartTrans();
                $query = "
                    DELETE FROM `exam_graders`
                    WHERE `post_id` = ".$db->qstr($post_id);
 
                if (!$db->Execute($query)) {
                    $db->FailTrans();
                    $ret = false;
                } else {
                    $db->CompleteTrans();
                    $ret = true;
                }
            }
        }
        return $ret;
    }

    /**
     * Returns an array of the proxy_ids that are assigned to be graders of
     * the given exam post for the given course group.
     * 
     * @global ADODB $db
     * @param int $post_id
     * @param int $cgroup_id
     * @return array
     */
    public static function fetchGraderProxyIds($post_id, $cgroup_id = null) {
        global $db;
        $query = "
            SELECT DISTINCT(`proxy_id`)
            FROM `exam_graders`
            WHERE `post_id` = ".$db->qstr($post_id)."
            ".(null !== $cgroup_id ? "AND `cgroup_id` = ".$db->qstr($cgroup_id) : "");
        $results = $db->GetAll($query);
        $proxy_ids = array();
        if ($results) {
            foreach ($results as $result) {
                $proxy_ids[] = (int)$result["proxy_id"];
            }
        }
        return $proxy_ids;
    }
    
    /**
     * Builds the editing array for the JavaScript edit graders interface.
     * 
     * @param int $post_id
     * @return mixed
     */
    public static function fetchEditingArray($post_id) {
        $js_data = array();
        $post = Models_Exam_Post::fetchRowByID($post_id);
        $course = $post->getCourse();
        if (!$course) {
            return $js_data;
        }
        $course_id = $course->getID();
        $course_groups = Models_Course_Group::fetchAllByCourseID($course_id);
        foreach ($course_groups as $course_group) {
            $cgroup_id = (int)$course_group->getCgroupId();
            $graders = array();
            $old_grader_ids = static::fetchGraderProxyIds($post_id, $cgroup_id);
            if (count($old_grader_ids) > 0) {
                foreach ($old_grader_ids as $proxy_id) {
                    $user = User::fetchRowByID($proxy_id);
                    if (!$user) {
                        continue;
                    }
                    $photos = UserPhotos::get($proxy_id);
                    $photo_url = null;
                    foreach ($photos as $photo) {
                        if ($photo->isActive()) {
                            $photo_url = $photo->getThumbnail();
                        }
                    }
                    if (null === $photo_url) {
                        $photo_url = webservice_url("photo");
                    }
                    $graders[] = array(
                        "name" => $user->getFullname(false),
                        "proxy_id" => $proxy_id,
                        "photo_url" => $photo_url
                    );
                }
                $js_data[$cgroup_id] = array(
                    "checked" => true,
                    "graders" => $graders
                );
            } else {
                $cgroup_contacts = Models_Course_Group_Contact::fetchAllByCgroupID($cgroup_id);
                foreach ($cgroup_contacts as $contact) {
                    $user = User::fetchRowByID($contact->getProxyID());
                    if (!$user) {
                        continue;
                    }
                    $proxy_id = (int)$user->getProxyID();
                    $photos = UserPhotos::get($proxy_id);
                    $photo_url = null;
                    foreach ($photos as $photo) {
                        if ($photo->isActive()) {
                            $photo_url = $photo->getThumbnail();
                        }
                    }
                    if (null == $photo_url) {
                        $photo_url = webservice_url("photo");
                    }
                    if ($proxy_id) {
                        $graders[] = array(
                            "name" => $user->getFullname(false),
                            "proxy_id" => $proxy_id,
                            "photo_url" => $photo_url
                        );
                    }
                }
                $js_data[$cgroup_id] = array(
                    "checked" => false,
                    "graders" => $graders
                );
            }
        }
        return $js_data;
    }
    
    /**
     * Gets the submitted attempts for the given post_id for all students that the
     * given grader_proxy_id can grade, in order by submission date.
     * 
     * @param int $grader_proxy_id
     * @param int $post_id
     * @return ArrayObject|Models_Exam_Progress[]
     */
    public static function fetchGradableSubmissionsForPost($grader_proxy_id, $post_id) {
        $students = Models_Exam_Grader::fetchGradableStudentsForPost($grader_proxy_id, $post_id);
        $submissions = array();
        foreach ($students as $student) {
            $submissions = array_merge($submissions, Models_Exam_Progress::fetchAllByPostIDProxyID($post_id, $student->getProxyId()));
        }
        usort($submissions, function($a, $b) { return $a->getSubmissionDate() - $b->getSubmissionDate(); });
        return $submissions;
    }
    
    /**
     * Gets the gradable exam elements for the given exam post.
     * 
     * @param int $post_id
     * @return ArrayObject|Models_Exam_Exam_Element[]
     */
    public static function fetchGradableExamElementsForPost($post_id) {
        global $ENTRADA_ACL;
        $post = Models_Exam_Post::fetchRowByID($post_id);
        $gradable_types = array("short", "essay");
        if ($ENTRADA_ACL->amIAllowed("examgradefnb", "update", false)) {
            $gradable_types[] = "fnb";
        }
        $questions = array();
        $elements_all = Models_Exam_Exam_Element::fetchAllByExamIDElementType($post->getExamID(), "question");
        foreach ($elements_all as $elem) {
            $question = Models_Exam_Question_Versions::fetchRowByVersionID($elem->getElementID());
            if ($question && in_array($question->getQuestionType()->getShortname(), $gradable_types)) {
                $questions[] = $elem;
            }
        }
        return $questions;
    }
    
    /**
     * Returns an array of students that are gradable for the given post_id
     * by the given grader_proxy_id, in alphabetical order by last name.
     * 
     * @global ADODB $db
     * @param int $grader_proxy_id
     * @param int $post_id
     * @return ArrayObject|User[]
     */
    public static function fetchGradableStudentsForPost($grader_proxy_id, $post_id) {
        global $db;
        $students = array();
        $cgroups_query = "
            SELECT DISTINCT(a.`proxy_id`)
            FROM `course_group_audience` AS a
            JOIN `exam_graders` AS b
            ON b.`cgroup_id` = a.`cgroup_id`
            JOIN `exam_progress` AS c
            ON c.`post_id` = ".$db->qstr($post_id)."
            AND c.`proxy_id` = a.`proxy_id`
            AND c.`progress_value` = 'submitted'
            WHERE b.`proxy_id` = ".$db->qstr($grader_proxy_id)."
            AND b.`post_id` = ".$db->qstr($post_id);
        $cgroup_results = $db->GetAll($cgroups_query);
        if ($cgroup_results) {
            foreach ($cgroup_results as $row) {
                $user = User::fetchRowById($row["proxy_id"]);
                if ($user) {
                    $students[] = $user;
                }
            }
        }
        usort($students, function($a, $b) { return strcmp($a->getFullname(), $b->getFullname()); });
        return $students;
    }
    
    /**
     * Gets all of the posts that can be graded by the given proxy_id.
     * 
     * @global ADODB $db
     * @param int $grader_proxy_id
     * @return ArrayObject|Models_Exam_Post[]
     */
    public static function fetchGradableExamPosts($grader_proxy_id) {
        global $db;
        $posts = array();
        $post_ids_query = "
            SELECT `post_id`
            FROM `exam_graders`
            WHERE `proxy_id` = ".$db->qstr($grader_proxy_id)."
            GROUP BY `post_id`";
        $post_ids = $db->GetAll($post_ids_query);
        if ($post_ids) {
            foreach ($post_ids as $post_id) {
                $posts[] = Models_Exam_Post::fetchRowByID($post_id["post_id"]);
            }
        }
        return $posts;
    }
    
    /**
     * Returns true if the given grader can grade the given exam post,
     * or false otherwise.
     * 
     * @param int $post_id
     * @param int $grader_proxy_id
     * @return boolean
     */
    public static function isExamPostGradableBy($post_id, $grader_proxy_id) {
        $gradable_posts = static::fetchGradableExamPosts($grader_proxy_id);
        foreach ($gradable_posts as $post) {
            if ($post->getID() === $post_id) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Checks if a student's submission for the given exam post is gradable by
     * the given grader.
     * 
     * @param int $student_proxy_id
     * @param int $post_id
     * @param int $grader_proxy_id
     * @return boolean
     */
    public static function isStudentGradableBy($student_proxy_id, $post_id, $grader_proxy_id) {
        $gradable_students = static::fetchGradableStudentsForPost($grader_proxy_id, $post_id);
        foreach ($gradable_students as $student) {
            if ((int)$student_proxy_id === (int)$student->getProxyId()) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Returns the amount of questions in the given exam post that are of a
     * gradable type (short answer or essay).
     * 
     * @global ADODB $db
     * @param int $post_id
     * @return int
     */
    public static function fetchGradableQuestionCount($post_id) {
        global $db, $ENTRADA_ACL;
        $post = Models_Exam_Post::fetchRowByID($post_id);
        if (!$post) {
            return 0;
        }
        $question_types_arr = array("short", "essay");
        if ($ENTRADA_ACL->amIAllowed("examgradefnb", "update", false)) {
            $question_types_arr[] = "fnb";
        }
        $question_types = implode(", ", array_map(function($i) use ($db) { return $db->qstr($i); }, $question_types_arr));
        $query = "
            SELECT COUNT(*)
            FROM `exam_elements` AS a
            JOIN `exam_question_versions` AS b
            ON a.`element_type` = 'question'
            AND b.`version_id` = a.`element_id`
            JOIN `exam_lu_questiontypes` AS d
            ON d.`questiontype_id` = b.`questiontype_id`
            WHERE a.`exam_id` = ".$db->qstr($post->getExamId())."
            AND `a`.`deleted_date` IS NULL
            AND d.`shortname` IN (".$question_types.")";
        return (int)$db->GetOne($query);
    }
    
    /**
     * Returns the amount of questions for the given student and exam post
     * that have already been graded. Useful for testing whether this student's
     * submission has not been graded, is being graded, or has been completely graded.
     * 
     * @global ADODB $db
     * @param int $exam_progress_id
     * @return int
     */
    public static function fetchGradedQuestionCount($exam_progress_id) {
        global $db, $ENTRADA_ACL;
        $question_types_arr = array("short", "essay");
        if ($ENTRADA_ACL->amIAllowed("examgradefnb", "update", false)) {
            $question_types_arr[] = "fnb";
        }
        $question_types = implode(", ", array_map(function($i) use ($db) { return $db->qstr($i); }, $question_types_arr));
        $query = "
            SELECT COUNT(*)
            FROM `exam_progress` AS a
            JOIN `exam_progress_responses` AS b
            ON b.`exam_progress_id` = a.`exam_progress_id`
            AND b.`question_type` IN (".$question_types.")
            WHERE a.`exam_progress_id` = ".$db->qstr($exam_progress_id)."
            AND b.`graded_by` IS NOT NULL";
        return (int)$db->GetOne($query);
    }
    
    /**
     * Returns the "gradable" responses for the given exam post for the
     * given student. Gradable responses are responses to short answer or essay
     * questions.
     * 
     * @param int $exam_progress_id
     * @return ArrayObject|Models_Exam_Progress_Responses[]
     */
    public static function fetchGradableResponses($exam_progress_id) {
        global $ENTRADA_ACL;
        $question_types = array("short", "essay");
        if ($ENTRADA_ACL->amIAllowed("examgradefnb", "update", false)) {
            $question_types[] = "fnb";
        }
        $exam_progress = Models_Exam_Progress::fetchRowByID($exam_progress_id);
        $all_responses = Models_Exam_Progress_Responses::fetchAllByProgressID($exam_progress->getID());
        $gradable_responses = array_filter($all_responses, function($r) use ($question_types) { return in_array($r->getQuestionType(), $question_types); });
        usort($gradable_responses, function($a, $b) { return $a->getQuestionCount() - $b->getQuestionCount(); });
        return $gradable_responses;
    }
    
    /**
     * Returns an array of users that have not been assigned a grader for the
     * given exam post.
     * 
     * @global ADODB $db
     * @param int $post_id
     * @return ArrayObject|User[]
     */
    public static function fetchUnassignedStudents($post_id) {
        global $db;
        $post = Models_Exam_Post::fetchRowByID($post_id);
        if (!$post) {
            return array();
        }
        $course = $post->getCourse();
        if (!$course) {
            return array();
        }
        // Get all students in the audience
        $audience_students = array();
        if ($post->getTargetType() === "event") {
            $event = Models_Event::fetchRowByID($post->getTargetID());
            if (!$event) {
                return array();
            }
            $event_audiences = $event->getEventAudience();
            if ($event_audiences && is_array($event_audiences)) {
                foreach ($event_audiences as $event_audience) {
                    if ($event_audience && is_object($event_audience)) {
                        $a = $event_audience->getAudience($event->getEventStart());
                        if ($a) {
                            $audience_students = array_merge($audience_students, array_keys($a->getAudienceMembers()));
                        }
                    }
                }
            }
        } else if ($post->getTargetType() === "community") {
            $community_members = Models_Community_Member::fetchAllByCommunityID($post->getTargetID());
            $audience_students = array_map(function($a) { return (int)$a->getProxyId(); }, $community_members);
        } else {
            return array();
        }
        // Get all the students who are assigned to at least one grader
        $query = "
            SELECT DISTINCT(a.`proxy_id`)
            FROM `course_group_audience` AS a
            JOIN `exam_graders` AS b
            ON b.`cgroup_id` = a.`cgroup_id`
            WHERE b.`post_id` = ".$db->qstr($post->getID());
        $results = $db->GetAll($query);
        $assigned_students = array();
        if ($results) {
            foreach ($results as $result) {
                $assigned_students[] = (int)$result["proxy_id"];
            }
        }
        
        // Return students who are in the audience but not assigned
        $unassigned_proxy_ids = array_diff($audience_students, $assigned_students);
        $unassigned_students = array();
        foreach ($unassigned_proxy_ids as $proxy_id) {
            $student = User::fetchRowByID($proxy_id);
            if ($student) {
                $unassigned_students[] = $student;
            }
        }
        return $unassigned_students;
    }
    
    /**
     * Returns an array of all students that are assigned to the given grader,
     * but may not have submitted their exam yet.
     * 
     * @param int $grader_proxy_id
     * @param int $post_id
     */
    public static function fetchAssignedStudents($post_id, $grader_proxy_id = null) {
        global $db;
        
        $query = "
            SELECT DISTINCT(a.`proxy_id`)
            FROM `course_group_audience` AS a
            JOIN `exam_graders` AS b
            ON b.`cgroup_id` = a.`cgroup_id`
            WHERE b.`proxy_id` = ".$db->qstr($grader_proxy_id)."
            AND b.`post_id` = ".$db->qstr($post_id)."
            AND a.`active` = 1";
        
        $results = $db->GetAll($query);
        $assigned_students = array();
        if ($results) {
            foreach ($results as $result) {
                $student = User::fetchRowByID($result["proxy_id"]);
                if ($student) {
                    $assigned_students[] = $student;
                }
            }
        }
        usort($assigned_students, function($a, $b) { return strcmp($a->getFullname(), $b->getFullname()); });
        return $assigned_students;
    }

    /**
     * Returns an array of all course groups that are assigned to the given grader
     * 
     * @param int $grader_proxy_id
     * @param int $post_id
     */
    public static function fetchAssignedCourseGroups($post_id, $grader_proxy_id = null) {
        global $db;
        
        $query = "
            SELECT DISTINCT(a.`cgroup_id`)
            FROM `exam_graders` AS a
            WHERE a.`post_id` = ".$db->qstr($post_id);

        if ($grader_proxy_id) {
            $query .= " AND a.`proxy_id` = ".$db->qstr($grader_proxy_id);
        }
        
        $results = $db->GetAll($query);
        $assigned_groups = array();
        if ($results) {
            foreach ($results as $result) {
                $group = Models_Course_Group::fetchRowByID($result["cgroup_id"]);
                if ($group) {
                    $assigned_groups[] = $group;
                }
            }
        }
        usort($assigned_groups, function($a, $b) { return strcmp($a->getGroupName(), $b->getGroupName()); });
        return $assigned_groups;
    }

    public static function fetchGradersIdsbyPostId($post_id) {
        global $db;
        $query = "
            SELECT DISTINCT(a.`proxy_id`)
            FROM `exam_graders` AS a
            WHERE a.`post_id` = ".$db->qstr($post_id);
        
        $results = $db->GetAll($query);
        $graders = array();
        if ($results) {
            foreach ($results as $result) {
                $grader = User::fetchRowByID($result["proxy_id"]);
                if ($grader) {
                    $graders[] = $grader;
                }
            }
        }
        usort($graders, function($a, $b) { return strcmp($a->getFullname(), $b->getFullname()); });
        return $graders;
    }
}
