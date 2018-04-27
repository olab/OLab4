<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Cron job responsible for gathering statistics on assessment tools for each students.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
@set_time_limit(0);
@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    dirname(__FILE__) . "/../core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

/**
 * Starts by getting the assessment tools completion count for each students
 */
$query = "SELECT DISTINCT a.`course_id`, a.`form_id`, c.`target_value` as proxy_id, 
            ( SELECT COUNT(*)
              FROM `cbl_distribution_assessments` AS a1 
              JOIN `cbl_assessment_lu_types` AS b1 ON a1.`assessment_type_id` = b1.`assessment_type_id`
              JOIN `cbl_distribution_assessment_targets` AS c1 ON a1.`dassessment_id` = c1.`dassessment_id`
              JOIN `cbl_assessment_progress` AS e1 ON a1.`dassessment_id` = e1.`dassessment_id`
              WHERE c1.`target_type` = 'proxy_id'
              AND b1.`shortname` = 'cbme'
              AND NOT (a1.`assessor_type` = 'internal' AND a1.`assessor_value` = c1.`target_value`) 
              AND ((a1.`course_id` IS NULL AND a.`course_id` IS NULL) OR (a1.`course_id` = a.`course_id`)) 
              AND a1.`form_id` = a.`form_id`
              AND c1.`target_value` = c.`target_value`
              AND a1.`deleted_date` IS NULL 
              AND c1.`deleted_date` IS NULL
            ) as `count`
          FROM `cbl_distribution_assessments` AS a 
          JOIN `cbl_assessment_lu_types` AS b ON a.`assessment_type_id` = b.`assessment_type_id`
          JOIN `cbl_distribution_assessment_targets` AS c ON a.`dassessment_id` = c.`dassessment_id`
          JOIN `cbl_assessment_progress` AS e ON a.`dassessment_id` = e.`dassessment_id`
          WHERE c.`target_type` = 'proxy_id'
          AND b.`shortname` = 'cbme'
          AND NOT (a.`assessor_type` = 'internal' AND a.`assessor_value` = c.`target_value`)
          AND a.`deleted_date` IS NULL 
          AND c.`deleted_date` IS NULL";

// TODO: Iterate via offset/limit instead of fetching all.
$assessments = $db->getAll($query);

Models_Assessments_Form_Statistics::truncate();

if ($assessments) {
    foreach ($assessments as $assessment) {
        $assessments_form_stats = new Models_Assessments_Form_Statistics();
        if (!$assessments_form_stats->fromArray($assessment)->insert()) {
            application_log("error", "There was a problem saving an assessment form statistic, DB said: " . $db->ErrorMsg());
        }
    }
}
