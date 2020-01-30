<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Sets the weight of all rating scale responses with "Not observed" text to 0
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

$query = "SELECT * FROM `cbl_assessment_rating_scale_responses` WHERE `text` = 'Not Observed' AND `deleted_date` IS NULL";
$results = $db->GetAll($query);
echo "\n\n";
if ($results) {
    $total_rows = 0;
    foreach ($results as $result) {
        $query = "UPDATE `cbl_assessment_rating_scale_responses` SET `weight` = 0 WHERE `rating_scale_response_id` = " .$db->qstr($result["rating_scale_response_id"]);
        if ($db->Execute($query)) {
            echo "Updated weight for rating_scale_response_id: " . $result["rating_scale_response_id"] . "\n";
            $total_rows ++;
        }
    }
}
echo "Total rows affected: " . $total_rows;
echo "\n\n";