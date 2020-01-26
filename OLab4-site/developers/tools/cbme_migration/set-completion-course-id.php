<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Set the course id for the completion records
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
@set_time_limit(0);
@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../../../www-root/core",
    dirname(__FILE__) . "/../../../www-root/core/includes",
    dirname(__FILE__) . "/../../../www-root/core/library",
    dirname(__FILE__) . "/../../../www-root/core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

$cperiods = Models_Curriculum_Period::fetchAllCurrentIDs();

echo "\n\n";

$query = "  SELECT a.*, b.`organisation_id`
            FROM `cbl_learner_objectives_completion` AS a
            JOIN `objective_organisation` AS b 
            ON a.`objective_id` = b.`objective_id`";

if ($results = $db->getAll($query)) {
    foreach ($results as $result) {
        if ($courses = Models_Course::getCoursesByProxyIDOrganisationID($result["proxy_id"], $result["organisation_id"], $cperiods)) {
            if (count($courses) > 1) {
                $course_ids = array_map(function($course) { return $course["course_id"]; }, $courses);

                $found = false;
                foreach ($courses as $course) {
                    if ($contacts = Models_Course_Contact::fetchAllByCourseIDContactType($course["course_id"], "ccmember")) {
                        foreach ($contacts as $contact) {
                            if ($contact->getProxyID() == $result["created_by"]) {
                                // Check if he's cc member for another student's course
                                if (Models_Course_Contact::countIsMemberOf($contact->getProxyID(), $course_ids, "ccmember") === 1) {
                                    $found = true;
                                    $query = "UPDATE `cbl_learner_objectives_completion` SET `course_id` = ? WHERE `lo_completion_id` = ?";
                                    if (!$db->execute($query, array($course["course_id"], $result["lo_completion_id"]))) {
                                        echo "Failed to update record {$result['lo_completion_id']}: " . $db->errorMsg() . "\n";
                                    }
                                }
                            }
                        }
                    }
                }

                if ( !$found) {
                    echo "More than one course found for proxy_id: {$result["proxy_id"]}, completion_id: {$result['lo_completion_id']}\n";
                }
            } else if (count($courses) == 1) {
                $query = "UPDATE `cbl_learner_objectives_completion` SET `course_id` = ? WHERE `lo_completion_id` = ?";
                if (!$db->execute($query, array($courses[0]["course_id"], $result["lo_completion_id"]))) {
                    echo "Failed to update record {$result['lo_completion_id']}: " . $db->errorMsg() . "\n";
                }
            } else {
                echo "Failed to find an active course for proxy id: {$result["proxy_id"]}, completion record: {$result["lo_completion_id"]}\n ";
            }
        }
    }
}

echo "All done\n\n";