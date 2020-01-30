<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Tool for migration for CBME.
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
echo "\n";
$query = "SELECT `form_type_id` FROM `cbl_assessments_lu_form_types` WHERE `shortname` = 'cbme_multisource_feedback'";
$ft_id = intval($db->getOne($query));
if (!$ft_id) {
    echo "Could not determine Multisource form type ID\n";
    exit();
}


/**
 * Template items / items groups
 */
$query = "DELETE FROM `cbl_assessments_form_blueprint_item_templates` WHERE `form_type_id` = ?";
if (!$db->Execute($query, array($ft_id))) {
    die("Failed to delete exiting template items: ".$db->ErrorMsg());
}

$query = "SELECT `item_group_id` FROM `cbl_assessments_lu_item_groups` WHERE `shortname` = 'cbme_multisource_rubric_concerns'";
if (!($ig_id = $db->getOne($query))) {
    $query = "insert into `cbl_assessments_lu_item_groups` (`item_group_id`, `form_type_id`, `item_type`, `shortname`, `title`, `description`, `active`, `created_date`, `created_by`, `updated_date`, `updated_by`, `deleted_date`) values(NULL, ?,'rubric','cbme_multisource_rubric_concerns','Concerns','Rubric for the CBME Multisource Feedback form','1',?,'1',NULL,NULL,NULL);";
    $db->debug = true;
    if (!$db->Execute($query, array($ft_id, time()))) {
        die("Failed to create item group: ".$db->ErrorMsg());
    }

    if (!$ig_id = $db->insert_Id()) {
        die("Failed to fetch newly created item group ID");
    }
}
$query = "INSERT INTO `cbl_assessments_form_blueprint_item_templates` (`afb_item_template_id`, `form_type_id`, `active`, `parent_item`, `ordering`, `component_order`, `item_definition`, `created_date`, `created_by`, `updated_date`, `updated_by`, `deleted_date`) VALUES(NULL, ?,'1','0','1','7','{\"element_type\":\"rubric\",\"element_definition\":{\"item_group_id\":".$ig_id.",\"item_text\":\"Concerns\",\"rating_scale_id\":null,\"comment_type\":\"flagged\"}}',?,'1',NULL,NULL,NULL);";
if (!$db->Execute($query, array($ft_id, time()))) {
    die("Failed to insert new template item: " . $db->ErrorMsg());
}
$concern_id = $db->insert_Id();

$query = "SELECT `item_group_id` FROM `cbl_assessments_lu_item_groups` WHERE `shortname` = 'cbme_multisource_rubric_concerns_item_1'";
if (!($ig_id = $db->getOne($query))) {
    $query = "insert into `cbl_assessments_lu_item_groups` (`item_group_id`, `form_type_id`, `item_type`, `shortname`, `title`, `description`, `active`, `created_date`, `created_by`, `updated_date`, `updated_by`, `deleted_date`) values(NULL, ".$ft_id.",'item','cbme_multisource_rubric_concerns_item_1','Do you have professionalism concerns about this resident\' performance\?','','1',".time().",'1',NULL,NULL,NULL);";
    if (!$db->Execute($query)) {
        die("Failed to create item group: ".$db->ErrorMsg());
    }

    if (!$ig_id = $db->insert_Id()) {
        die("Failed to fetch newly created item group ID");
    }
}
$query = "INSERT INTO `cbl_assessments_form_blueprint_item_templates` (`afb_item_template_id`, `form_type_id`, `active`, `parent_item`, `ordering`, `component_order`, `item_definition`, `created_date`, `created_by`, `updated_date`, `updated_by`, `deleted_date`) VALUES(NULL, ".$ft_id.",'1', ".$concern_id.",'2','0','{\"element_type\":\"item\",\"element_definition\":{\"item_group_id\":14,\"item_text\":\"Do you have professionalism concerns about this resident\'s performance?\",\"itemtype_shortname\":\"horizontal_multiple_choice_single\",\"item_code\":\"CBME_multisource_form_item\",\"mandatory\":null,\"responses\":{\"1\":\"\",\"2\":\"\"},\"comment_type\":\"flagged\",\"allow_default\":\"1\",\"default_response\":\"1\",\"rating_scale_id\":null,\"flagged_response\":{\"2\":2},\"descriptors\":{\"1\":591,\"2\":590},\"objectives\":[],\"item_description\":null}}',".time().",'1',NULL,NULL,NULL);";
if (!$db->Execute($query)) {
    die("Failed to insert new template item: " . $db->ErrorMsg());
}

$query = "SELECT `item_group_id` FROM `cbl_assessments_lu_item_groups` WHERE `shortname` = 'cbme_multisource_rubric_concerns_item_2'";
if (!($ig_id = $db->getOne($query))) {
    $query = "insert into `cbl_assessments_lu_item_groups` (`item_group_id`, `form_type_id`, `item_type`, `shortname`, `title`, `description`, `active`, `created_date`, `created_by`, `updated_date`, `updated_by`, `deleted_date`) values(NULL, ".$ft_id.",'item','cbme_multisource_rubric_concerns_item_2','Do you have patient safety concerns related to this resident\' performance?','','1',".time().",'1',NULL,NULL,NULL);";
    if (!$db->Execute($query)) {
        die("Failed to create item group: ".$db->ErrorMsg());
    }

    if (!$ig_id = $db->insert_Id()) {
        die("Failed to fetch newly created item group ID");
    }
}
$query = "INSERT INTO `cbl_assessments_form_blueprint_item_templates` (`afb_item_template_id`, `form_type_id`, `active`, `parent_item`, `ordering`, `component_order`, `item_definition`, `created_date`, `created_by`, `updated_date`, `updated_by`, `deleted_date`) VALUES(NULL, ".$ft_id.",'1',".$concern_id.",'1','0','{\"element_type\":\"item\",\"element_definition\":{\"item_group_id\":15,\"item_text\":\"Do you have patient safety concerns related to this resident\'s performance?\",\"itemtype_shortname\":\"horizontal_multiple_choice_single\",\"item_code\":\"CBME_multisource_form_item\",\"mandatory\":null,\"responses\":{\"1\":\"\",\"2\":\"\"},\"comment_type\":\"flagged\",\"allow_default\":\"1\",\"default_response\":\"1\",\"rating_scale_id\":null,\"flagged_response\":{\"2\":2},\"descriptors\":{\"1\":591,\"2\":590},\"objectives\":[],\"item_description\":null}}',".time().",'1',NULL,NULL,NULL);";
if (!$db->Execute($query)) {
    die("Failed to insert new template item: " . $db->ErrorMsg());
}

$query = "SELECT `item_group_id` FROM `cbl_assessments_lu_item_groups` WHERE `shortname` = 'cbme_multisource_rubric_concerns_item_3'";
if (!($ig_id = $db->getOne($query))) {
    $query = "insert into `cbl_assessments_lu_item_groups` (`item_group_id`, `form_type_id`, `item_type`, `shortname`, `title`, `description`, `active`, `created_date`, `created_by`, `updated_date`, `updated_by`, `deleted_date`) values(NULL, ".$ft_id.",'item','cbme_multisource_rubric_concerns_item_3','Are there other reasons to flag this assessment?','','1',".time().",'1',NULL,NULL,NULL);";
    if (!$db->Execute($query)) {
        die("Failed to create item group: ".$db->ErrorMsg());
    }

    if (!$ig_id = $db->insert_Id()) {
        die("Failed to fetch newly created item group ID");
    }
}
$query = "INSERT INTO `cbl_assessments_form_blueprint_item_templates` (`afb_item_template_id`, `form_type_id`, `active`, `parent_item`, `ordering`, `component_order`, `item_definition`, `created_date`, `created_by`, `updated_date`, `updated_by`, `deleted_date`) VALUES(NULL, ".$ft_id.",'1',".$concern_id.",'3','0','{\"element_type\":\"item\",\"element_definition\":{\"item_group_id\":16,\"item_text\":\"Are there other reasons to flag this assessment?\",\"itemtype_shortname\":\"horizontal_multiple_choice_single\",\"item_code\":\"CBME_multisource_form_item\",\"mandatory\":null,\"responses\":{\"1\":\"\",\"2\":\"\"},\"comment_type\":\"flagged\",\"allow_default\":\"1\",\"default_response\":\"1\",\"rating_scale_id\":null,\"flagged_response\":{\"2\":2},\"descriptors\":{\"1\":591,\"2\":590},\"objectives\":[],\"item_description\":null}}',".time().",'1',NULL,NULL,NULL);";
if (!$db->Execute($query)) {
    die("Failed to insert new template item: " . $db->ErrorMsg());
}

$query = "SELECT `item_group_id` FROM `cbl_assessments_lu_item_groups` WHERE `shortname` = 'cbme_multisource_feedback'";
if (!($ig_id = $db->getOne($query))) {
    $query = "insert into `cbl_assessments_lu_item_groups` (`item_group_id`, `form_type_id`, `item_type`, `shortname`, `title`, `description`, `active`, `created_date`, `created_by`, `updated_date`, `updated_by`, `deleted_date`) values(NULL, ?,'item','cbme_multisource_feedback','Feedback to Resident','','1',?,'1',NULL,NULL,NULL);";
    if (!$db->Execute($query, array($ft_id, time()))) {
        die("Failed to create item group: ".$db->ErrorMsg());
    }

    if (!$ig_id = $db->insert_Id()) {
        die("Failed to fetch newly created item group ID");
    }
}
$query = "INSERT INTO `cbl_assessments_form_blueprint_item_templates` (`afb_item_template_id`, `form_type_id`, `active`, `parent_item`, `ordering`, `component_order`, `item_definition`, `created_date`, `created_by`, `updated_date`, `updated_by`, `deleted_date`) VALUES(NULL, ?,'1','0','1','6','{\"element_type\":\"item\",\"element_definition\":{\"item_group_id\":17,\"item_text\":\"Feedback to Resident\",\"itemtype_shortname\":\"free_text\",\"item_code\":\"CBME_multisource_freetext\",\"mandatory\":null,\"responses\":[],\"comment_type\":\"disabled\",\"rating_scale_id\":null,\"flagged_response\":[],\"descriptors\":[],\"objectives\":[],\"item_description\":null}}',?,'1',NULL,NULL,NULL);";
if (!$db->Execute($query, array($ft_id, time()))) {
    die("Failed to insert new template item: " . $db->ErrorMsg());
}

$query = "SELECT `item_group_id` FROM `cbl_assessments_lu_item_groups` WHERE `shortname` = 'cbme_multisource_cvar_setting'";
if (!($ig_id = $db->getOne($query))) {
    $query = "insert into `cbl_assessments_lu_item_groups` (`item_group_id`, `form_type_id`, `item_type`, `shortname`, `title`, `description`, `active`, `created_date`, `created_by`, `updated_date`, `updated_by`, `deleted_date`) values(NULL, ?,'item','cbme_multisource_cvar_setting','Setting','Multisource Contextual Variable : Setting','1',NULL,'1',NULL,NULL,NULL);";
    if (!$db->Execute($query, array($ft_id, time()))) {
        die("Failed to create item group: ".$db->ErrorMsg());
    }

    if (!$ig_id = $db->insert_Id()) {
        die("Failed to fetch newly created item group ID");
    }
}
$query = "INSERT INTO `cbl_assessments_form_blueprint_item_templates` (`afb_item_template_id`, `form_type_id`, `active`, `parent_item`, `ordering`, `component_order`, `item_definition`, `created_date`, `created_by`, `updated_date`, `updated_by`, `deleted_date`) VALUES(NULL, ?,'1','0','1','1','{\"element_type\":\"item\",\"element_definition\":{\"item_group_id\":18,\"item_text\":\"Setting\",\"itemtype_shortname\":\"selectbox_single\",\"item_code\":\"CBME_Contextual_variable_item\",\"mandatory\":1,\"responses\":{\"1\":\"Inpatient\",\"2\":\"Outpatient\",\"3\":\"Emergency Department\",\"4\":\"OR\"},\"comment_type\":\"disabled\",\"rating_scale_id\":null,\"flagged_response\":null,\"descriptors\":[],\"objectives\":[],\"item_description\":null}}',?,'1',NULL,NULL,NULL);";
if (!$db->Execute($query, array($ft_id, time()))) {
    die("Failed to insert new template item: " . $db->ErrorMsg());
}

$query = "SELECT `item_group_id` FROM `cbl_assessments_lu_item_groups` WHERE `shortname` = 'cbme_multisource_cvar_assessor_role'";
if (!($ig_id = $db->getOne($query))) {
    $query = "insert into `cbl_assessments_lu_item_groups` (`item_group_id`, `form_type_id`, `item_type`, `shortname`, `title`, `description`, `active`, `created_date`, `created_by`, `updated_date`, `updated_by`, `deleted_date`) values(NULL, ?,'item','cbme_multisource_cvar_assessor_role','Assessor Role','Multisource Contextual Variable : Assessor Role','1',?,'1',NULL,NULL,NULL);";
    if (!$db->Execute($query, array($ft_id, time()))) {
        die("Failed to create item group: ".$db->ErrorMsg());
    }

    if (!$ig_id = $db->insert_Id()) {
        die("Failed to fetch newly created item group ID");
    }
}
$query = "INSERT INTO `cbl_assessments_form_blueprint_item_templates` (`afb_item_template_id`, `form_type_id`, `active`, `parent_item`, `ordering`, `component_order`, `item_definition`, `created_date`, `created_by`, `updated_date`, `updated_by`, `deleted_date`) VALUES(NULL, ?,'1','0','1','2','{\"element_type\":\"item\",\"element_definition\":{\"item_group_id\":21,\"item_text\":\"Assessor Role\",\"itemtype_shortname\":\"selectbox_single\",\"item_code\":\"CBME_Contextual_variable_item\",\"mandatory\":1,\"responses\":{\"1\":\"Nurse\",\"2\":\"Nutritionist\",\"3\":\"Dietician\",\"4\":\"Social worker\",\"5\":\"OT\",\"6\":\"PT\",\"7\":\"Other\"},\"comment_type\":\"disabled\",\"rating_scale_id\":null,\"flagged_response\":null,\"descriptors\":[],\"objectives\":[],\"item_description\":null}}',?,'1',NULL,NULL,NULL);";
if (!$db->Execute($query, array($ft_id, time()))) {
    die("Failed to insert new template item: " . $db->ErrorMsg());
}

$query = "SELECT `item_group_id` FROM `cbl_assessments_lu_item_groups` WHERE `shortname` = 'cbme_multisource_cvar_encounters'";
if (!($ig_id = $db->getOne($query))) {
    $query = "insert into `cbl_assessments_lu_item_groups` (`item_group_id`, `form_type_id`, `item_type`, `shortname`, `title`, `description`, `active`, `created_date`, `created_by`, `updated_date`, `updated_by`, `deleted_date`) values(NULL, ?,'item','cbme_multisource_cvar_encounters','Encounters with Resident','CBME Multisource contextual variable: Encounters with Resident ','1',?,'1',NULL,NULL,NULL);";
    if (!$db->Execute($query, array($ft_id, time()))) {
        die("Failed to create item group: ".$db->ErrorMsg());
    }

    if (!$ig_id = $db->insert_Id()) {
        die("Failed to fetch newly created item group ID");
    }
}
$query = "INSERT INTO `cbl_assessments_form_blueprint_item_templates` (`afb_item_template_id`, `form_type_id`, `active`, `parent_item`, `ordering`, `component_order`, `item_definition`, `created_date`, `created_by`, `updated_date`, `updated_by`, `deleted_date`) VALUES(NULL, ?,'1','0','1','3','{\"element_type\":\"item\",\"element_definition\":{\"item_group_id\":22,\"item_text\":\"Encounters with Resident\",\"itemtype_shortname\":\"selectbox_single\",\"item_code\":\"CBME_Contextual_variable_item\",\"mandatory\":1,\"responses\":{\"1\":\"1-5\",\"2\":\"5-10\",\"3\":\"More than 10\"},\"comment_type\":\"disabled\",\"rating_scale_id\":null,\"flagged_response\":null,\"descriptors\":[],\"objectives\":[],\"item_description\":null}}',?,'1',NULL,NULL,NULL);";
if (!$db->Execute($query, array($ft_id, time()))) {
    die("Failed to insert new template item: " . $db->ErrorMsg());
}

$query = "SELECT `item_group_id` FROM `cbl_assessments_lu_item_groups` WHERE `shortname` = 'cbme_multisource_cvar_scope'";
if (!($ig_id = $db->getOne($query))) {
    $query = "insert into `cbl_assessments_lu_item_groups` (`item_group_id`, `form_type_id`, `item_type`, `shortname`, `title`, `description`, `active`, `created_date`, `created_by`, `updated_date`, `updated_by`, `deleted_date`) values(NULL, ?,'item','cbme_multisource_cvar_scope','Scope of assessment','Multisource Contextual Variable : Scope of assessment','1',?,'1',NULL,NULL,NULL);";
    if (!$db->Execute($query, array($ft_id, time()))) {
        die("Failed to create item group: ".$db->ErrorMsg());
    }

    if (!$ig_id = $db->insert_Id()) {
        die("Failed to fetch newly created item group ID");
    }
}
$query = "INSERT INTO `cbl_assessments_form_blueprint_item_templates` (`afb_item_template_id`, `form_type_id`, `active`, `parent_item`, `ordering`, `component_order`, `item_definition`, `created_date`, `created_by`, `updated_date`, `updated_by`, `deleted_date`) VALUES(NULL, ?,'1','0','1','4','{\"element_type\":\"item\",\"element_definition\":{\"item_group_id\":32,\"item_text\":\"Scope of assessment\",\"itemtype_shortname\":\"selectbox_single\",\"item_code\":\"CBME_Contextual_variable_item\",\"mandatory\":1,\"responses\":{\"1\":\"Single assessor\",\"2\":\"Group of assessors \"},\"comment_type\":\"disabled\",\"rating_scale_id\":null,\"flagged_response\":null,\"descriptors\":[],\"objectives\":[],\"item_description\":null}}',?,'1',NULL,NULL,NULL);";
if (!$db->Execute($query, array($ft_id, time()))) {
    die("Failed to insert new template item: " . $db->ErrorMsg());
}

// Components
$query = "delete from `cbl_assessments_form_type_component_settings` where `form_type_id` = ?";
if (!$db->Execute($query, array($ft_id))) {
    die ("Failed to delete previous components version: ".$db->ErrorMsg());
}

$query = "insert into `cbl_assessments_form_type_component_settings` (`aftc_setting_id`, `form_type_id`, `component_order`, `settings`, `created_date`, `created_by`, `updated_date`, `updated_by`, `deleted_date`) values(NULL, ?,'0','{\"element_text\":\"<p><strong>Your participation</strong> is completely <u>voluntary</u>. you are under no obligation to respond and may skip any item you are uncomfortable rating.</p>\r\n\r\n<p><strong>Confidentiality: </strong>your feedback is anonymous and will be discussed by the academic advisor with the resident in a private meeting.</p>\r\n\r\n<p><strong>Instructions: </strong>please consider your most recent encounters with the resident. review each item in the \"developing\"; column of the rubric and decide whether the resident\'s performance was below (opportunities for growth), at (developing), or above that level (achieving)</p>\",\"editable\":1,\"is_instruction\":0}',?,'1',NULL,NULL,NULL);";
if (!$db->Execute($query, array($ft_id, time()))) {
    die("Failed to insert new template component: " . $db->ErrorMsg());
}

$query = "insert into `cbl_assessments_form_type_component_settings` (`aftc_setting_id`, `form_type_id`, `component_order`, `settings`, `created_date`, `created_by`, `updated_date`, `updated_by`, `deleted_date`) values(NULL, ?,'5','{\"locked\":1,\"scale_id\":9,\"default_response\":1,\"min_roles\":1,\"max_roles\":6,\"roles_list\":{\"1\":{\"objective_code\":\"\",\"objective_name\":\"Multi-source Feedback\",\"responses\":[{\"objectives\":[\"CM1.1\",\"CM1.4\",\"CM1.6\",\"ME3.1\"],\"responses_text\":[\"\",\"Did not respond to patient\'s needs for comfort and support\",\"Responded to patient\'s needs for comfort and support\",\"Anticipated patient\'s needs and planed accordingly\"]},{\"objectives\":[\"ME2.3\",\"CM1.6\"],\"responses_text\":[\"\",\"Ignored rights and choices of patient when planning care\",\"Considered rights and choices of patient when planning care \",\"Used rights and choices of patient to guide care planning\"]},{\"objectives\":[\"CM4.1\",\"CM4.2\",\"CM4.3\",\"ME2.3\"],\"responses_text\":[\"\",\"Did not respond to patient\'s need for info\",\"Discussed plan of care with patient\",\"Provided on-going info to patient\"]},{\"objectives\":[\"HA1.1\"],\"responses_text\":[\"\",\"Appeared overwhelmed by demanding interpersonal patient situations\",\"Identified need for additional patient services\",\"Advocated for patients\' access to services\"]},{\"objectives\":[\"CM1.5\"],\"responses_text\":[\"\",\"Did not consider need for additional patient services\",\"Handled demanding interpersonal patient situations\",\"Managed demanding interpersonal patient situations with compassion\"]},{\"objectives\":[\"CM5.1\",\"CM5.2\"],\"responses_text\":[\"\",\"Inaccurate/incomplete documentation\",\"Documentation was unclear at times\",\"Documentation was clear and complete\"]},{\"objectives\":[\"CL1.3\"],\"responses_text\":[\"\",\"Did not always treat co-workers with respect\",\"Treated co-workers with respect\",\"Treated co-workers with respect and contributed to a positive working environment\"]},{\"objectives\":[\"CL2.2\"],\"responses_text\":[\"\",\"Did not express ideas clearly to team\",\"Expressed ideas clearly to team\",\"Adapted explanations with ease to ensure team understanding\"]},{\"objectives\":[\"CL1.3\",\"SC1.2\"],\"responses_text\":[\"\",\"Ignored input about patient care from team\",\"Receptive to input about patient care from team\",\"Actively sought team input about patient care\"]},{\"objectives\":[\"LD3.1\",\"ME1.4\",\"ME1.5\"],\"responses_text\":[\"\",\"Ignored urgent requests or pages\",\"Usually prioritized urgent requests or pages\",\"Prioritized urgent requests or pages\"]},{\"objectives\":[\"ME1.1\"],\"responses_text\":[\"\",\"Avoided responsibility for patient care\",\"Aware of patient care responsibilities\",\"Assumed responsibility for patient care\"]},{\"objectives\":[\"ME1.5\"],\"responses_text\":[\"\",\"Appeared overwhelmed by workload\",\"Rarely overwhelmed by workload\",\"Managed workload effectively\"]},{\"objectives\":[\"CL1.2\"],\"responses_text\":[\"\",\"Overwhelmed team members\",\"Delegated workload strategically\",\"Inspired confidence/Supported excellence\"]},{\"objectives\":[\"LD3.1\"],\"responses_text\":[\"\",\"Uncertain/indecisive\",\"Solved problems/made decisions with minimal delay\",\"Solved problems/made decisions as they arose\"]},{\"objectives\":[\"PR4.1\"],\"responses_text\":[\"\",\"Did not remain professional in stressful situations\",\"Remained professional in stressful situations\",\"Was professional in stressful situations and helped others cope\"]},{\"objectives\":[\"ME1.5\",\"ME1.6\",\"SC1.2\"],\"responses_text\":[\"\",\"Did not seek assistance when required\",\"Aware of personal limitations but hesitant to seek assistance\",\"Sought consultation/supervision freely\"]}]}}}',?,'1',NULL,NULL,NULL);";
if (!$db->Execute($query, array($ft_id, time()))) {
    die("Failed to insert new template component: " . $db->ErrorMsg());
}

echo "\nSuccessfully configured new multisource template \n";
