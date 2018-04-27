<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Tool for migration for CBME.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Josh Belanger <jb301@queensu.ca>
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

global $db;

echo "\n";
$total_records = 0;
$query = "  SELECT a.`dassessment_id`, d.`course_id` FROM `cbl_distribution_assessments` AS a
            JOIN `cbl_assessment_distributions` AS d 
            ON a.`adistribution_id` = d.`adistribution_id`
            WHERE (a.`course_id` = '' OR a.`course_id` IS NULL)
            AND (d.`course_id` IS NOT NULL AND d.`course_id` > 0)
            GROUP BY a.`dassessment_id`";

$results = $db->GetAll($query);
if ($results) {
    foreach ($results as $result) {
        $query = "UPDATE `cbl_distribution_assessments` SET `course_id` = " . $db->qstr($result["course_id"]) . " WHERE `dassessment_id` = " .$db->qstr($result["dassessment_id"]);
        if ($db->Execute($query)) {
            echo "Successfully updated assessment task: " . $result["dassessment_id"] . "\n";
            $total_records ++;
        } else {
            echo $db->ErrorMsg();
        }
    }
    echo "\n" . "Total rows affected: " . $total_records . "\n";
}