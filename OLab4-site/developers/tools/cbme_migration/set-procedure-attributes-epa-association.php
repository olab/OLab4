<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Associate current procedure criteria to each course EPAs.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
ini_set("memory_limit", "10G");
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
$count = array(
    "courses" => 0,
    "attributes" => 0
);


echo "\n";
echo "Fetching list of procedure attributes, ordered by course ID.\n";
$query = "  SELECT a.* FROM `cbme_course_objectives` AS a
            JOIN `global_lu_objectives` AS b ON a.`objective_id` = b.`objective_id`
            WHERE b.`objective_code` = 'procedure_attribute'
            AND a.`deleted_date` IS NULL
            AND b.`objective_active` = 1
            AND b.`objective_id` NOT IN (
                SELECT attribute_objective_id FROM `cbme_procedure_epa_attributes` WHERE course_id = a.`course_id`
            )
            ORDER BY `course_id`";
$attributes = $db->GetAll($query);
echo "Complete. Found ";
count($attributes);
echo " records.\n";
if ($attributes) {
    echo "Procedure attributes found, iterating....\n";
    $course_id = 0;
    $epas = array();
    echo "Creating new procedure attribute records for all EPAs of course ($course_id): ";
    foreach ($attributes as $attribute) {
        $count["attributes"]++;
        if ($course_id != $attribute["course_id"]) {
            $count["courses"]++;
            echo "\n\nFound new course, selecting all EPAs for this course.\n";
            $course_id = $attribute["course_id"];
            $query = "  SELECT * FROM `cbme_course_objectives` AS a
                        JOIN `global_lu_objectives` AS b ON a.`objective_id` = b.`objective_id`
                        JOIN `global_lu_objective_sets` AS c ON b.`objective_set_id` = c.`objective_set_id`
                        WHERE c.`shortname` = 'epa'
                        AND a.`course_id` = ?
                        AND b.`objective_set_id` = 1
                        AND a.`deleted_date` IS NULL
                        AND b.`objective_active` = 1
                        AND c.`deleted_date` IS NULL;";

            if (!$epas = $db->GetAll($query, array($course_id))) {
                $epas = array();
            }
        }
        echo "\n[Attribute objective ID: {$attribute["objective_id"]} ";
        $epa_add_count = 0;
        foreach ($epas as $epa) {
            $proc_epa = new Models_CBME_ProcedureEPAAttribute(array(
                "course_id" => $course_id,
                "epa_objective_id" => $epa["objective_id"],
                "attribute_objective_id" => $attribute["objective_id"],
                "created_by" => 1,
                "created_date" => time()
            ));
            $proc_epa->insert();
            $epa_add_count++;
        }
        echo "added $epa_add_count]";
    }
    echo "\n";
}

echo "{$count["attributes"]} attributes for {$count["courses"]} courses associated with its courses' epas\n\n";
