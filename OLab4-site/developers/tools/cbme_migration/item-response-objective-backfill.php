<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Fills the cbl_assessments_lu_item_response_objectives table with records
 * from existing item responses
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Alex Ash <aa121@queensu.ca>
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @copyright Copyright 2018 Queen's University. All Rights Reserved.
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

echo "\nBeginning data migration\n";

$output_count = 0;
$response_objective_count = 0;
$ppa_rubric_form_count = 0;
$ppa_rubric_response_objective_count = 0;
$temp_objective_count = 0;
$existing_item_objective_linkages = 0;
$existing_blueprint_response_objective_linkages = 0;
$existing_ppa_response_objective_linkages = 0;

$query = "  CREATE TABLE IF NOT EXISTS `global_lu_objectives_temp` (
            `objective_id` int(12) NOT NULL,
            `objective_code` varchar(24) DEFAULT NULL,
            `objective_name` varchar(255) NOT NULL,
            `objective_set_id` int(12) NOT NULL,
            PRIMARY KEY (`objective_id`),
            KEY `objective_code` (`objective_code`),
            KEY `ft_objective_search` (`objective_code`,`objective_name`)
            ) ENGINE=Innodb DEFAULT CHARSET=utf8";

if ($db->execute($query)) {
    $objective_set_model = new Models_ObjectiveSet();
    $cv_objective_set = $objective_set_model->fetchRowByShortname("contextual_variable");
    $cv_response_objective_set = $objective_set_model->fetchRowByShortname("contextual_variable_responses");

    if ($cv_objective_set && $cv_response_objective_set) {
        $objective_set_ids = array();
        $objective_set_ids[] = $cv_objective_set->getShortname();
        $objective_set_ids[] = $cv_response_objective_set->getShortname();

        $query = "  SELECT b.`objective_id`, b.`objective_code`, b.`objective_name`, b.`objective_set_id` FROM `global_lu_objective_sets` AS a
                    JOIN `global_lu_objectives` AS b
                    ON a.`objective_set_id` = b.`objective_set_id`
                    WHERE a.`shortname` IN ('" . implode("','", $objective_set_ids) . "')";
        $results = $db->GetAll($query);
        if ($results) {
            foreach ($results as $result) {
                $query = "INSERT INTO `global_lu_objectives_temp` (`objective_id`, `objective_code`, `objective_name`, `objective_set_id`)
                          VALUES (?, ?, ?, ?)";
                $insert_result = $db->execute($query, array($result["objective_id"], $result["objective_code"], $result["objective_name"], $result["objective_set_id"]));
                if ($insert_result) {
                    echo "Successfully inserted temporary objective record for objective [". $result["objective_id"] ."]\n";
                    $temp_objective_count ++;
                } else {
                    echo "An error occurred while attempting to insert a temporary objective record for objective [". $result["objective_id"] ."]. DB said: ". $db->ErrorMsg() ."\n";
                }
            }
        }
    }

    echo "\nInserted " . $temp_objective_count . " temporary objectives\n";
    echo "\nMigrating contextual variable item objectives\n";

    $query = "  SELECT `item_id`, go.`objective_id`
                FROM `cbl_assessments_lu_items` as it
                JOIN `global_lu_objectives_temp` as go
                ON it.`item_text` = go.`objective_name`
                JOIN `global_lu_objective_sets` os
                on go.`objective_set_id` = os.`objective_set_id`
                WHERE `item_code` = 'CBME_contextual_variables'
                AND os.`shortname` = 'contextual_variable'";

    $item_objectives = $db->GetAll($query);
    if ($item_objectives) {
        foreach ($item_objectives as $item) {
            $item_objective = new Models_Assessments_Item_Objective();
            if (!$item_objective->fetchRowByItemIDObjectiveID($item["item_id"], $item["objective_id"])) {
                $item_objective_model = new Models_Assessments_Item_Objective(array(
                    "item_id"            => $item["item_id"],
                    "objective_id"       => $item["objective_id"],
                    "objective_metadata" => NULL,
                    "created_date"       => time(),
                    "created_by"         => 1
                ));

                if (!$item_objective_model->insert()) {
                    echo "An error occurred while attempting to insert an item objective record for item " . $item["item_id"] . ". DB said: " . $db->ErrorMsg() . "\n";
                } else {
                    $output_count++;
                    echo "Successfully inserted an item objective record for item_id[" . $item["item_id"] . "] and objective [". $item["objective_id"] ."]\n" ;
                }
            } else {
                echo "Linkage for item_id[" . $item["item_id"] . "] and objective [". $item["objective_id"] ."] already exists\n" ;
                $existing_item_objective_linkages ++;
            }
        }
    }

    echo "\nTotal item objectives added: " . $output_count . "\n";
    echo "Total existing item objectives linkages: " . $existing_item_objective_linkages . "\n";
    echo "\nMigrating Blueprint based contextual variable item response objectives\n";

    $query = "  SELECT go.`objective_id`, ir.`iresponse_id` FROM `cbl_assessments_lu_forms` AS forms
                JOIN `cbl_assessments_lu_form_blueprints` AS fb
                ON forms.`originating_id` = fb.`form_blueprint_id`
                JOIN `cbl_assessment_form_elements` as fe
                ON forms.`form_id` = fe.`form_id`
                JOIN `cbl_assessments_lu_items` as it FORCE INDEX(PRIMARY)
                ON fe.`element_id` = it.`item_id`
                JOIN `global_lu_objectives_temp` AS glo
                ON it.`item_text` = glo.`objective_name`
                JOIN `global_lu_objective_sets` AS glos
                ON glo.`objective_set_id` = glos.`objective_set_id`
                AND glos.`shortname` = 'contextual_variable'
                JOIN `cbl_assessments_lu_item_responses` as ir
                ON it.`item_id` = ir.`item_id`
                JOIN `global_lu_objectives_temp` as go
                ON ir.`text` = go.`objective_name`
                JOIN `cbme_course_objectives` as co
                ON go.`objective_id` = co.`objective_id`
                WHERE forms.`origin_type` =  'blueprint'
                AND fe.`element_type` = 'item'
                AND it.`item_code` = 'CBME_contextual_variables'
                AND co.`course_id` = fb.`course_id`
                AND go.`objective_code` = glo.`objective_code`";


    $results = $db->GetAll($query);
    if ($results) {
        foreach ($results as $result) {
            $item_response_objective = new Models_Assessments_Item_Response_Objective();
            if (!$item_response_objective->fetchRowByIresponseIDObjectiveID($result["iresponse_id"], $result["objective_id"])) {
                $objective_response_model = new Models_Assessments_Item_Response_Objective(array(
                    "iresponse_id"   => $result["iresponse_id"],
                    "objective_id"   => $result["objective_id"],
                    "created_date"   => time(),
                    "created_by"     => 1
                ));

                if (!$objective_response_model->insert()) {
                    echo "An error occurred while attempting to insert an item response objective record for item response" . $result["iresponse_id"] . ". DB said: " . $db->ErrorMsg() . "\n";
                } else {
                    $response_objective_count ++;
                    echo "Successfully inserted an item response objective record for ireponse_id[" .$result["iresponse_id"] . "] and objective [". $result["objective_id"] ."]\n" ;
                }
            } else {
                echo "Linkage for ireponse_id[" .$result["iresponse_id"] . "] and objective [". $result["objective_id"] ."] already exists\n" ;
                $existing_blueprint_response_objective_linkages ++;
            }
        }
    }
    echo "\nInserted " . $response_objective_count . " rows into the cbl_assessments_lu_item_response_objectives table\n";
    echo "Total existing item response objectives linkages: " . $existing_blueprint_response_objective_linkages . "\n";
    /**
     * Handle CBME Rubric and PPA forms which don't have a blueprint to work from
     */
    echo "\nMigrating CBME rubric and PPA form contextual variable item response objectives\n";
    $query = "ALTER TABLE `cbl_assessments_lu_forms` ADD COLUMN `course_id` int(11) DEFAULT NULL AFTER `attributes`";
    if ($db->execute($query)) {
        $form_type_ids = array();
        $form_type_model = new Models_Assessments_Form_Type();
        $form_type_ppa = $form_type_model->fetchRowByShortname("cbme_ppa_form");
        $form_type_rubric = $form_type_model->fetchRowByShortname("cbme_rubric");

        if ($form_type_ppa) {
            $form_type_ids[] = $form_type_ppa->getID();
        }

        if ($form_type_rubric) {
            $form_type_ids[] = $form_type_rubric->getID();
        }

        if ($form_type_ids) {
            $query = "SELECT `form_id`, `attributes` FROM `cbl_assessments_lu_forms` WHERE `form_type_id` IN ('" . implode("','", $form_type_ids) . "') AND `attributes` IS NOT NULL";
            $results = $db->GetAll($query);
            if ($results) {
                foreach ($results as $result) {
                    $attributes = json_decode($result["attributes"], true);
                    if ($attributes && is_array($attributes) && array_key_exists("course_id", $attributes)) {
                        $query = "UPDATE `cbl_assessments_lu_forms` SET `course_id` = ? WHERE `form_id` = ?";
                        if (!$db->execute($query, array($attributes["course_id"], $result["form_id"]))) {
                            echo "An error occurred while attempting to set course_id " . $attributes["course_id"] . "  for form" . $result["form_id"] . ". DB said: " . $db->ErrorMsg() . "\n";
                        } else {
                            $ppa_rubric_form_count++;
                            echo "Form " . $result["form_id"] . " course value successfully set to " . $attributes["course_id"] . "\n";
                        }
                    }
                }
                echo "\nSuccessfully updated " . $ppa_rubric_form_count . " form records.\n\n";
                $query = "  SELECT go.`objective_id`, ir.`iresponse_id` FROM `cbl_assessments_lu_forms` AS forms
                            JOIN `cbl_assessment_form_elements` as fe
                            ON forms.`form_id` = fe.`form_id`
                            JOIN `cbl_assessments_lu_items` as it FORCE INDEX(PRIMARY)
                            ON fe.`element_id` = it.`item_id`
                            JOIN `global_lu_objectives_temp` AS glo
                            ON it.`item_text` = glo.`objective_name`
                            JOIN `global_lu_objective_sets` AS glos
                            ON glo.`objective_set_id` = glos.`objective_set_id`
                            AND glos.`shortname` = 'contextual_variable'
                            JOIN `cbl_assessments_lu_item_responses` as ir
                            ON it.`item_id` = ir.`item_id`
                            JOIN `global_lu_objectives_temp` as go
                            ON ir.`text` = go.`objective_name`
                            JOIN `cbme_course_objectives` as co
                            ON go.`objective_id` = co.`objective_id`
                            WHERE forms.`form_type_id` IN ('" . implode("','", $form_type_ids) . "')
                            AND fe.`element_type` = 'item'
                            AND it.`item_code` = 'CBME_contextual_variables'
                            AND co.`course_id` = forms.`course_id`
                            AND go.`objective_code` = glo.`objective_code`";
                $results = $db->GetAll($query);
                if ($results) {
                    foreach ($results as $result) {
                        $item_response_objective = new Models_Assessments_Item_Response_Objective();
                        if (!$item_response_objective->fetchRowByIresponseIDObjectiveID($result["iresponse_id"], $result["objective_id"])) {
                            $objective_response_model = new Models_Assessments_Item_Response_Objective(array(
                                "iresponse_id" => $result["iresponse_id"],
                                "objective_id" => $result["objective_id"],
                                "created_date" => time(),
                                "created_by" => 1
                            ));

                            if (!$objective_response_model->insert()) {
                                echo "An error occurred while attempting to insert an item response objective record for item response" . $result["iresponse_id"] . ". DB said: " . $db->ErrorMsg() . "\n";
                            } else {
                                $ppa_rubric_response_objective_count++;
                                echo "Successfully inserted an item response objective record for ireponse_id[" . $result["iresponse_id"] . "] and objective [" . $result["objective_id"] . "] \n";
                            }

                        } else {
                            echo "Linkage for ireponse_id[" .$result["iresponse_id"] . "] and objective [". $result["objective_id"] ."] already exists\n" ;
                            $existing_ppa_response_objective_linkages ++;
                        }
                    }
                    echo "\nInserted " . $ppa_rubric_response_objective_count . " item response objective records\n";
                    echo "Total existing item response objectives linkages: " . $existing_ppa_response_objective_linkages . "\n";
                }
            } else {
                echo "\nNo CBME Rubric or PPA forms found to migrate.\n";
            }
            $query = "ALTER TABLE `cbl_assessments_lu_forms` DROP COLUMN `course_id`";
            if (!$db->execute($query)) {
                echo "A problem occurred while attempting to remove the temporary course_id field from cbl_assessments_lu_forms. DB said: " . $db->ErrorMsg();
            }
        } else {
            echo "\nNo matching form_type_ids found matching shortnames: cbme_ppa_form, cbme_rubric\n";
        }
    } else {
        echo "A problem occurred while attempting to add the course_id to cbl_assessments_lu_forms. DB said: " . $db->ErrorMsg();
    }

    $query = "DROP TABLE IF EXISTS `global_lu_objectives_temp`";
    $result = $db->execute($query);
    if ($result) {
        echo "\nSuccessfully dropped the global_lu_objectives_temp table\n";
    } else {
        echo "\nAn error occurred while attempting to drop the global_lu_objectives_temp table. DB said: ". $db->ErrorMsg() ."\n";
    }
} else {
    echo "An error occurred while attempting to create global_lu_objectives_temp table. DB said: ". $db->ErrorMsg() ."\n";
}