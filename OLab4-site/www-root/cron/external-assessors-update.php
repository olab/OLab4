<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Script to populate cbl course contacts
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Jordan L <jl250@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
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

require_once("init.inc.php");
global $db;

//Get all distribution assessors
$query = "	SELECT a.*, b.`assessor_type`, b.`assessor_value` FROM `cbl_assessment_distributions` AS a
            JOIN `cbl_assessment_distribution_assessors` AS b
            ON a.`adistribution_id` = b.`adistribution_id`
            WHERE a.`deleted_date` IS NULL
            AND a.`course_id` > 0
            AND ( b.`assessor_type` = 'external_hash' OR b.`assessor_type` = 'proxy_id' ) ";

$results = $db->GetAll($query);
if ($results) {
	foreach($results as $result){
        $assessor_type = $result["assessor_type"] == "external_hash" ? "external" : "internal";

        if (testInsertRecord($assessor_type, $result["assessor_value"], $result["organisation_id"], $result["course_id"])) {
            $query = "INSERT INTO `cbl_course_contacts` (`course_id`, `assessor_value`, `assessor_type`, `visible`, `created_by`, `created_date`, `updated_by`, `updated_date`, `deleted_date`) VALUES (" . $result["course_id"] . "," . $result["assessor_value"] . ",'" . $assessor_type . "',1," . $result["created_by"] . "," . $result["created_date"] . "," . $result["updated_by"] . "," . $result["updated_date"] . ", null)";
            if (!$db->Execute($query)) {
                echo "Unable to insert record from distribution assessors: " . $result["assessor_value"] . " DB said: " . $db->ErrorMsg();
                break;
            }
        }
	}
}

//Get all the distribution delegation assessors
$query = "	SELECT a.*, b.`assessor_type`, b.`assessor_value` FROM `cbl_assessment_distributions` AS a
            JOIN `cbl_assessment_distribution_delegation_assignments` AS b
            ON a.`adistribution_id` = b.`adistribution_id`
            WHERE a.`deleted_date` IS NULL
            AND a.`course_id` > 0
            AND b.`assessor_type` IS NOT NULL ";

$results = $db->GetAll($query);
if ($results) {
    foreach($results as $result){
        if (testInsertRecord($result["assessor_type"], $result["assessor_value"], $result["organisation_id"], $result["course_id"])) {
            $query = "INSERT INTO `cbl_course_contacts` (`course_id`, `assessor_value`, `assessor_type`, `visible`, `created_by`, `created_date`, `updated_by`, `updated_date`, `deleted_date`) VALUES (" . $result["course_id"] . "," . $result["assessor_value"] . ",'" . $result["assessor_type"] . "',1," .$result["created_by"] . "," .$result["created_date"] . "," .$result["updated_by"] . "," .$result["updated_date"] . ", null)";
            if (!$db->Execute($query)) {
                echo " Unable to insert record from external distribution delegation assignments: " . $result["assessor_value"] . " DB said: " . $db->ErrorMsg();
                break;
            }
        }
    }
}

//Final check to look through all the assessments done by assessors
$query = "	SELECT a.*, b.`assessor_type`, b.`assessor_value` FROM `cbl_assessment_distributions` AS a
            JOIN `cbl_distribution_assessments` AS b
            ON a.`adistribution_id` = b.`adistribution_id`
            WHERE a.`deleted_date` IS NULL
            AND a.`course_id` > 0 ";

$results = $db->GetAll($query);
if ($results) {
    foreach($results as $result){
        if (testInsertRecord($result["assessor_type"], $result["assessor_value"], $result["organisation_id"], $result["course_id"])) {
            $query = "INSERT INTO `cbl_course_contacts` (`course_id`, `assessor_value`, `assessor_type`, `visible`, `created_by`, `created_date`, `updated_by`, `updated_date`, `deleted_date`) VALUES (" . $result["course_id"] . "," . $result["assessor_value"] . ",'" . $result["assessor_type"] . "',1," .$result["created_by"] . "," .$result["created_date"] . "," .$result["updated_by"] . "," .$result["updated_date"] . ", null)";
            if (!$db->Execute($query)) {
                echo " Unable to insert record from distribution assessments: " . $result["assessor_value"] . " DB said: " . $db->ErrorMsg();
                break;
            }
        }
    }
}

function testInsertRecord($assessor_type, $assessor_value, $organisation_id, $course_id) {
    $insert_record = true;
    $course_contact_model = new Models_Assessments_Distribution_CourseContact();

    if ($assessor_type == "internal") {
        $roles = Models_User_Access::fetchAllByUserIDOrganisationID($assessor_value, $organisation_id);

        if (!empty($roles)) {
            $is_faculty = false;

            foreach ($roles as $role) {
                if ($role->getGroup() == "faculty") {
                    $is_faculty = true;
                }
            }

            if (!$is_faculty) {
                $insert_record = false;
            }
        }
    } else {
        if (!Models_Assessments_Distribution_ExternalAssessor::fetchRowByID($assessor_value)) {
            $insert_record = false;
        }
    }

    if ($course_contact_model->fetchRowByAssessorValueAssessorTypeCourseID($assessor_value, $assessor_type, $course_id)) {
        $insert_record = false;
    }

    return $insert_record;
}