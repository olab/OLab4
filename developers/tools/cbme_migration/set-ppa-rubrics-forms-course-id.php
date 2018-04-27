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

$query = "  SELECT a.*, io.`objective_metadata`
            FROM `cbl_assessments_lu_forms` AS a
            JOIN `cbl_assessments_lu_form_types` AS ft ON a.`form_type_id` = ft.`form_type_id`
            JOIN `cbl_assessment_form_elements` AS fe ON a.`form_id` = fe.`form_id`
            JOIN `cbl_assessment_item_objectives` AS io ON fe.`element_id` = io.`item_id`
            WHERE a.`deleted_date` IS NULL
            AND ft.`deleted_date` IS NULL
            AND fe.`deleted_date` IS NULL
            AND io.`deleted_date` IS NULL
            AND fe.`element_type` = 'item'
            AND io.`objective_metadata` IS NOT NULL
            AND (ft.`shortname` = 'cbme_ppa_form' OR ft.`shortname` = 'cbme_rubric')";


if ($forms = $db->getAll($query)) {
    $processed = array();

    foreach ($forms as $form) {
        if (in_array($form["form_id"], $processed)) {
            continue;
        }

        $attributes = $form["attributes"] ? json_decode($form["attributes"], true) : array();
        $objective_meta = json_decode($form["objective_metadata"],true);

        if (!isset($objective_meta["tree_node_id"])) {
            continue;
        }

        if (!$node_id = intval($objective_meta["tree_node_id"])) {
            continue;
        }

        $query = "SELECT `course_id` FROM `cbme_objective_trees` WHERE `cbme_objective_tree_id` = ?";
        if (!$course_id = $db->getOne($query, array($node_id))) {
            continue;
        }

        $attributes["course_id"] = $course_id;

        $query = "UPDATE `cbl_assessments_lu_forms` SET `attributes` = ? WHERE `form_id` = ?";
        if (!$db->Execute($query, array(json_encode($attributes), $form["form_id"]))) {
            echo "Failed to update form {$form["title"]}: " . $db->ErrorMsg() . "\n";
            continue;
        }

        $processed[] = $form["form_id"];
    }

    echo "Updated " . count($processed) . " forms\n";
}

echo "\n";