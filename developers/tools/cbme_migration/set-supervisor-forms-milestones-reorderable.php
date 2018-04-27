<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Set the milestones for the supervisor forms to be reorderable
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

echo "\n\n";



$query = "  SELECT DISTINCT a.*
            FROM `cbl_assessments_lu_rubrics` AS a
            JOIN `cbl_assessment_form_elements` AS b ON b.`rubric_id` = a.`rubric_id`
            JOIN `cbl_assessments_lu_forms` AS c ON b.`form_id` = c.`form_id`
            JOIN `cbl_assessments_lu_form_blueprints` AS e ON c.`originating_id` = e.`form_blueprint_id`
            WHERE e.`form_type_id` = 2
            AND c.`origin_type` = 'blueprint'
            AND a.`rubric_title` = 'Milestones'
            AND a.`rubric_item_code` = 'CBME_rubric_from_scale'
            AND b.`element_type` = 'item'";


if ($rubrics = $db->getAll($query)) {
    echo "Found " . count($rubrics) . " rubrics to be updated\n\n";

    foreach ($rubrics AS $rubric) {
        if ($rubric["attributes"]) {
            $attributes = json_decode($rubric["attributes"], true);
        } else {
            $attributes = array();
        }

        $attributes["reorderable_in_form"] = true;

        $json = json_encode($attributes);

        $query = "UPDATE `cbl_assessments_lu_rubrics` SET `attributes` = ? WHERE `rubric_id` = ?";
        if (!$db->Execute($query, array($json, $rubric["rubric_id"]))) {
            echo "Failed to update rubric attributes for rubric id {$rubric["rubric_id"]} : " . $db->ErrorMsg() . "\n";
        }
    }
}

echo "\n";