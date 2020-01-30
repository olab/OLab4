<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Tool for migration for CBME.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
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
echo "\n";
$total_records = 0;
$query = "  SELECT a.`dassessment_id`, c.`course_id` FROM `cbl_distribution_assessments` AS a
            JOIN `cbl_assessments_lu_forms` AS b
            ON a.`form_id` = b.`form_id`
            JOIN `cbl_assessments_lu_form_blueprints` AS c
            ON b.`originating_id` = c.`form_blueprint_id`
            WHERE b.`form_type_id` = 2";

$results = $db->GetAll($query);
if ($results) {
    foreach ($results as $result) {
        $query = "UPDATE `cbl_distribution_assessments` SET `course_id` = " . $db->qstr($result["course_id"]) . " WHERE `dassessment_id` = " .$db->qstr($result["dassessment_id"]);
        if ($db->Execute($query)) {
            echo "Successfully updated assessment task: " . $result["dassessment_id"] . "\n";
            $total_records ++;
        }
    }
    echo "\n" . "Total rows affected: " . $total_records . "\n";
}