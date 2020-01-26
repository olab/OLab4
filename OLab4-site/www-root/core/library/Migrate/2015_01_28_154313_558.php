<?php
class Migrate_2015_01_28_154313_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        SET FOREIGN_KEY_CHECKS=0;

        CREATE TABLE IF NOT EXISTS `cbl_assessment_distributions` (
        `adistribution_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `one45_scenariosAttached_id` int(11) unsigned DEFAULT NULL,
        `form_id` int(11) unsigned NOT NULL,
        `organisation_id` int(11) NOT NULL,
        `title` varchar(2048) NOT NULL DEFAULT '',
        `description` text,
        `course_id` int(11) DEFAULT NULL,
        `min_submittable` tinyint(3) NOT NULL DEFAULT '0',
        `max_submittable` tinyint(3) NOT NULL DEFAULT '0',
        `repeat_targets` tinyint(1) NOT NULL DEFAULT '0',
        `submittable_by_target` tinyint(1) NOT NULL DEFAULT '0',
        `start_date` bigint(64) NOT NULL,
        `end_date` bigint(64) NOT NULL,
        `mandatory` tinyint(4) NOT NULL DEFAULT '0',
        `distributor_timeout` bigint(64) DEFAULT NULL,
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `created_date` bigint(64) NOT NULL,
        `created_by` int(11) NOT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`adistribution_id`),
        KEY `form_id` (`form_id`),
        CONSTRAINT `cbl_assessment_distributions_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `cbl_assessments_lu_forms` (`form_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `cbl_assessment_distribution_assessors` (
        `adassessor_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `adistribution_id` int(11) unsigned NOT NULL,
        `assessor_type` enum('proxy_id','cgroup_id','group_id','schedule_id','external_hash') NOT NULL DEFAULT 'proxy_id',
        `assessor_value` varchar(128) NOT NULL DEFAULT '',
        `assessor_name` varchar(128) DEFAULT NULL,
        `assessor_start` bigint(64) DEFAULT NULL,
        `assessor_end` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`adassessor_id`),
        KEY `adistribution_id` (`adistribution_id`),
        CONSTRAINT `cbl_assessment_distribution_assessors_ibfk_1` FOREIGN KEY (`adistribution_id`) REFERENCES `cbl_assessment_distributions` (`adistribution_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `cbl_assessment_distribution_authors` (
        `adauthor_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `adistribution_id` int(11) unsigned NOT NULL,
        `author_type` enum('proxy_id','organisation_id','course_id') NOT NULL DEFAULT 'proxy_id',
        `author_id` int(11) NOT NULL,
        `created_date` bigint(64) NOT NULL,
        `created_by` int(11) NOT NULL,
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`adauthor_id`),
        KEY `adistribution_id` (`adistribution_id`),
        CONSTRAINT `cbl_assessment_distribution_authors_ibfk_1` FOREIGN KEY (`adistribution_id`) REFERENCES `cbl_assessment_distributions` (`adistribution_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `cbl_assessment_distribution_delegators` (
        `addelegator_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `adistribution_id` int(11) unsigned NOT NULL,
        `delegator_type` enum('proxy_id','target') NOT NULL DEFAULT 'proxy_id',
        `delegetor_id` int(11) DEFAULT NULL,
        `start_date` bigint(64) DEFAULT NULL,
        `end_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`addelegator_id`),
        KEY `adistribution_id` (`adistribution_id`),
        CONSTRAINT `cbl_assessment_distribution_delegators_ibfk_1` FOREIGN KEY (`adistribution_id`) REFERENCES `cbl_assessment_distributions` (`adistribution_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `cbl_assessment_distribution_schedule` (
        `adschedule_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `adistribution_id` int(11) unsigned NOT NULL,
        `addelegator_id` int(11) DEFAULT NULL,
        `schedule_type` enum('schedule_id','schedule_parent_id','daily','weekly','monthly','yearly','course_id') NOT NULL DEFAULT 'schedule_id',
        `period_offset` bigint(64) DEFAULT NULL,
        `delivery_period` enum('start','middle','end') NOT NULL DEFAULT 'end',
        `schedule_id` int(11) DEFAULT NULL,
        `frequency` tinyint(3) NOT NULL DEFAULT '1',
        `start_date` bigint(64) DEFAULT NULL,
        `end_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`adschedule_id`),
        KEY `adistribution_id` (`adistribution_id`),
        CONSTRAINT `cbl_assessment_distribution_schedule_ibfk_1` FOREIGN KEY (`adistribution_id`) REFERENCES `cbl_assessment_distributions` (`adistribution_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `cbl_assessment_distribution_targets` (
        `adtarget_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `adistribution_id` int(11) unsigned NOT NULL,
        `adtto_id` int(11) unsigned NOT NULL,
        `target_id` int(11) unsigned NOT NULL,
        PRIMARY KEY (`adtarget_id`),
        KEY `adistribution_id` (`adistribution_id`),
        KEY `adtto_id` (`adtto_id`),
        CONSTRAINT `cbl_assessment_distribution_targets_ibfk_2` FOREIGN KEY (`adtto_id`) REFERENCES `cbl_assessments_lu_distribution_target_types_options` (`adtto_id`),
        CONSTRAINT `cbl_assessment_distribution_targets_ibfk_1` FOREIGN KEY (`adistribution_id`) REFERENCES `cbl_assessment_distributions` (`adistribution_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `cbl_assessment_form_authors` (
        `afauthor_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `form_id` int(11) unsigned NOT NULL,
        `author_type` enum('proxy_id','organisation_id','course_id') NOT NULL DEFAULT 'proxy_id',
        `author_id` int(11) NOT NULL,
        `created_date` bigint(64) NOT NULL,
        `created_by` int(11) NOT NULL,
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`afauthor_id`),
        KEY `form_id` (`form_id`),
        CONSTRAINT `cbl_assessment_form_authors_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `cbl_assessments_lu_forms` (`form_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `cbl_assessment_form_elements` (
        `afelement_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `one45_form_id` int(11) unsigned DEFAULT NULL,
        `form_id` int(11) unsigned NOT NULL,
        `element_type` enum('item','data_source','text') DEFAULT NULL,
        `element_id` int(11) unsigned DEFAULT NULL,
        `element_text` text,
        `rubric_id` int(11) unsigned DEFAULT NULL,
        `order` tinyint(3) NOT NULL DEFAULT '0',
        `allow_comments` tinyint(1) NOT NULL DEFAULT '0',
        `enable_flagging` tinyint(1) NOT NULL DEFAULT '0',
        `deleted_date` int(11) DEFAULT NULL,
        `updated_date` int(11) NOT NULL DEFAULT '0',
        `updated_by` int(11) NOT NULL DEFAULT '0',
        PRIMARY KEY (`afelement_id`),
        KEY `form_id` (`form_id`),
        CONSTRAINT `cbl_assessment_form_elements_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `cbl_assessments_lu_forms` (`form_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `cbl_assessment_item_authors` (
        `aiauthor_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `item_id` int(11) unsigned NOT NULL,
        `author_type` enum('proxy_id','organisation_id','course_id') NOT NULL DEFAULT 'proxy_id',
        `author_id` int(11) NOT NULL,
        `created_date` bigint(64) NOT NULL,
        `created_by` int(11) NOT NULL,
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`aiauthor_id`),
        KEY `item_id` (`item_id`),
        CONSTRAINT `cbl_assessment_item_authors_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `cbl_assessments_lu_items` (`item_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `cbl_assessment_item_objectives` (
        `aiobjective_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `item_id` int(11) unsigned NOT NULL,
        `objective_id` int(11) unsigned NOT NULL,
        `created_date` bigint(64) NOT NULL,
        `created_by` int(11) NOT NULL,
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`aiobjective_id`),
        KEY `item_id` (`item_id`),
        CONSTRAINT `cbl_assessment_item_objectives_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `cbl_assessments_lu_items` (`item_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `cbl_assessment_item_tags` (
        `aitag_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `item_id` int(11) unsigned NOT NULL,
        `tag_id` int(11) unsigned NOT NULL,
        PRIMARY KEY (`aitag_id`),
        KEY `item_id` (`item_id`),
        KEY `tag_id` (`tag_id`),
        CONSTRAINT `cbl_assessment_item_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `cbl_assessments_lu_tags` (`tag_id`),
        CONSTRAINT `cbl_assessment_item_tags_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `cbl_assessments_lu_items` (`item_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `cbl_assessment_progress` (
        `aprogress_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `one45_formsAttached_id` int(11) unsigned DEFAULT NULL,
        `adistribution_id` int(11) unsigned NOT NULL,
        `proxy_id` int(11) NOT NULL,
        `adtarget_id` int(11) unsigned NOT NULL,
        `target_record_id` int(11) DEFAULT NULL,
        `target_learning_context_id` int(11) DEFAULT NULL,
        `progress_value` enum('inprogress','complete','cancelled') DEFAULT NULL,
        `created_date` bigint(64) NOT NULL,
        `created_by` int(11) NOT NULL,
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `deleted_date` bigint(20) DEFAULT NULL,
        PRIMARY KEY (`aprogress_id`),
        KEY `adistribution_id` (`adistribution_id`),
        KEY `adtarget_id` (`adtarget_id`),
        CONSTRAINT `cbl_assessment_progress_ibfk_2` FOREIGN KEY (`adtarget_id`) REFERENCES `cbl_assessment_distribution_targets` (`adtarget_id`),
        CONSTRAINT `cbl_assessment_progress_ibfk_1` FOREIGN KEY (`adistribution_id`) REFERENCES `cbl_assessment_distributions` (`adistribution_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `cbl_assessment_progress_responses` (
        `epresponse_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `one45_answer_id` int(11) unsigned DEFAULT NULL,
        `aprogress_id` int(11) unsigned NOT NULL,
        `form_id` int(11) unsigned NOT NULL,
        `adistribution_id` int(11) unsigned NOT NULL,
        `proxy_id` int(11) NOT NULL,
        `afelement_id` int(11) unsigned NOT NULL,
        `iresponse_id` int(11) DEFAULT NULL,
        `comments` text,
        `created_date` bigint(64) NOT NULL,
        `created_by` int(11) NOT NULL,
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        PRIMARY KEY (`epresponse_id`),
        KEY `aprogress_id` (`aprogress_id`),
        KEY `form_id` (`form_id`),
        KEY `adistribution_id` (`adistribution_id`),
        KEY `afelement_id` (`afelement_id`),
        CONSTRAINT `cbl_assessment_progress_responses_ibfk_4` FOREIGN KEY (`afelement_id`) REFERENCES `cbl_assessment_form_elements` (`afelement_id`),
        CONSTRAINT `cbl_assessment_progress_responses_ibfk_1` FOREIGN KEY (`aprogress_id`) REFERENCES `cbl_assessment_progress` (`aprogress_id`),
        CONSTRAINT `cbl_assessment_progress_responses_ibfk_2` FOREIGN KEY (`form_id`) REFERENCES `cbl_assessments_lu_forms` (`form_id`),
        CONSTRAINT `cbl_assessment_progress_responses_ibfk_3` FOREIGN KEY (`adistribution_id`) REFERENCES `cbl_assessment_distributions` (`adistribution_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `cbl_assessment_report_audience` (
        `araudience_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `areport_id` int(11) unsigned NOT NULL,
        `audience_type` enum('proxy_id','organisation_id','cgroup_id','group_id','course_id','adtarget_id') NOT NULL DEFAULT 'proxy_id',
        `audience_value` int(11) DEFAULT NULL,
        PRIMARY KEY (`araudience_id`),
        KEY `areport_id` (`areport_id`),
        CONSTRAINT `cbl_assessment_report_audience_ibfk_1` FOREIGN KEY (`areport_id`) REFERENCES `cbl_assessment_reports` (`areport_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `cbl_assessment_report_source_targets` (
        `adtarget_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `arsource_id` int(11) unsigned NOT NULL,
        `areport_id` int(11) unsigned NOT NULL,
        `target_type` enum('proxy_id','course_id','schedule_id','organisation_id','adistribution_id') NOT NULL DEFAULT 'adistribution_id',
        `target_id` int(11) NOT NULL,
        PRIMARY KEY (`adtarget_id`),
        KEY `arsource_id` (`arsource_id`),
        KEY `areport_id` (`areport_id`),
        CONSTRAINT `cbl_assessment_report_source_targets_ibfk_2` FOREIGN KEY (`areport_id`) REFERENCES `cbl_assessment_reports` (`areport_id`),
        CONSTRAINT `cbl_assessment_report_source_targets_ibfk_1` FOREIGN KEY (`arsource_id`) REFERENCES `cbl_assessment_report_sources` (`arsource_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `cbl_assessment_report_sources` (
        `arsource_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `areport_id` int(11) unsigned NOT NULL,
        `source_type` enum('adistribution_id','form_id','item_id','afitem_id','objective_id','report','aprogress_id','afrubric_id','freetext') NOT NULL DEFAULT 'adistribution_id',
        `source_value` varchar(128) DEFAULT '',
        `description` longtext,
        `order` tinyint(3) NOT NULL DEFAULT '0',
        PRIMARY KEY (`arsource_id`),
        KEY `areport_id` (`areport_id`),
        CONSTRAINT `cbl_assessment_report_sources_ibfk_1` FOREIGN KEY (`areport_id`) REFERENCES `cbl_assessment_reports` (`areport_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `cbl_assessment_reports` (
        `areport_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `title` varchar(128) NOT NULL DEFAULT '',
        `description` text,
        `start_date` bigint(64) DEFAULT NULL,
        `end_date` bigint(64) DEFAULT NULL,
        `release_start` bigint(64) DEFAULT NULL,
        `release_end` bigint(64) DEFAULT NULL,
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(11) unsigned DEFAULT NULL,
        `created_date` bigint(64) NOT NULL,
        `created_by` int(11) unsigned NOT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`areport_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;


        CREATE TABLE IF NOT EXISTS `cbl_assessments_lu_data_source_types` (
        `dstype_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `shortname` varchar(32) NOT NULL DEFAULT '',
        `name` varchar(128) NOT NULL DEFAULT '',
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`dstype_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `cbl_assessments_lu_data_sources` (
        `dsource_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `dstype_id` int(10) unsigned NOT NULL,
        `source_value` varchar(255) NOT NULL DEFAULT '',
        `source_details` text,
        PRIMARY KEY (`dsource_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `cbl_assessments_lu_form_relationships` (
        `frelationship_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `form_id` int(11) unsigned NOT NULL,
        `first_parent_id` int(11) unsigned NOT NULL,
        `immediate_parent_id` int(11) unsigned NOT NULL,
        PRIMARY KEY (`frelationship_id`),
        KEY `form_id` (`form_id`),
        KEY `first_parent_id` (`first_parent_id`),
        KEY `immediate_parent_id` (`immediate_parent_id`),
        CONSTRAINT `cbl_assessments_lu_form_relationships_ibfk_3` FOREIGN KEY (`immediate_parent_id`) REFERENCES `cbl_assessments_lu_forms` (`form_id`),
        CONSTRAINT `cbl_assessments_lu_form_relationships_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `cbl_assessments_lu_forms` (`form_id`),
        CONSTRAINT `cbl_assessments_lu_form_relationships_ibfk_2` FOREIGN KEY (`first_parent_id`) REFERENCES `cbl_assessments_lu_forms` (`form_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `cbl_assessments_lu_forms` (
        `form_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `one45_form_id` int(11) DEFAULT NULL,
        `organisation_id` int(11) NOT NULL,
        `title` varchar(1024) NOT NULL DEFAULT '',
        `description` text,
        `created_date` bigint(64) NOT NULL,
        `created_by` int(11) NOT NULL,
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`form_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `cbl_assessments_lu_item_relationships` (
        `irelationship_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `item_id` int(11) unsigned NOT NULL,
        `first_parent_id` int(11) unsigned NOT NULL,
        `immediate_parent_id` int(11) unsigned NOT NULL,
        PRIMARY KEY (`irelationship_id`),
        KEY `immediate_parent_id` (`immediate_parent_id`),
        KEY `cbl_assessments_lu_item_relationships_ibfk_1` (`item_id`),
        KEY `cbl_assessments_lu_item_relationships_ibfk_2` (`first_parent_id`),
        CONSTRAINT `cbl_assessments_lu_item_relationships_ibfk_2` FOREIGN KEY (`first_parent_id`) REFERENCES `cbl_assessments_lu_items` (`item_id`),
        CONSTRAINT `cbl_assessments_lu_item_relationships_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `cbl_assessments_lu_items` (`item_id`),
        CONSTRAINT `cbl_assessments_lu_item_relationships_ibfk_3` FOREIGN KEY (`immediate_parent_id`) REFERENCES `cbl_assessments_lu_items` (`item_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `cbl_assessments_lu_item_responses` (
        `iresponse_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `one45_choice_id` int(11) unsigned DEFAULT NULL,
        `one45_response_num` int(11) unsigned DEFAULT NULL,
        `item_id` int(11) unsigned NOT NULL,
        `text` text NOT NULL,
        `order` tinyint(3) NOT NULL DEFAULT '0',
        `allow_html` tinyint(1) NOT NULL DEFAULT '0',
        `flag_response` tinyint(1) NOT NULL DEFAULT '0',
        `ardescriptor_id` int(11) unsigned DEFAULT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`iresponse_id`),
        KEY `item_id` (`item_id`),
        CONSTRAINT `cbl_assessments_lu_item_responses_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `cbl_assessments_lu_items` (`item_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `cbl_assessments_lu_items` (
        `item_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `one45_element_id` int(11) DEFAULT NULL,
        `organisation_id` int(11) NOT NULL,
        `itemtype_id` int(11) unsigned NOT NULL,
        `item_code` varchar(128) DEFAULT '',
        `item_text` text NOT NULL,
        `item_description` longtext,
        `allow_comments` tinyint(1) NOT NULL DEFAULT '1',
        `created_date` bigint(64) NOT NULL,
        `created_by` int(11) NOT NULL,
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`item_id`),
        KEY `itemtype_id` (`itemtype_id`),
        CONSTRAINT `cbl_assessments_lu_items_ibfk_1` FOREIGN KEY (`itemtype_id`) REFERENCES `cbl_assessments_lu_itemtypes` (`itemtype_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `cbl_assessments_lu_itemtypes` (
        `itemtype_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `shortname` varchar(32) NOT NULL DEFAULT '',
        `name` varchar(128) NOT NULL DEFAULT '',
        `description` text NOT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`itemtype_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        INSERT INTO `cbl_assessments_lu_itemtypes` (`itemtype_id`, `shortname`, `name`, `description`, `deleted_date`)
        VALUES
        (1,'horizontal_matrix_single','Horizontal Choice Matrix (single response)','The rating scale allows evaluators to rate each question based on the scale you provide (i.e. 1 = Not Demonstrated, 2 = Needs Improvement, 3 = Satisfactory, 4 = Above Average).',NULL),
        (2,'vertical_matrix_single','Vertical Choice Matrix (single response)','The rating scale allows evaluators to rate each question based on the scale you provide (i.e. 1 = Not Demonstrated, 2 = Needs Improvement, 3 = Satisfactory, 4 = Above Average).',NULL),
        (3,'selectbox_single','Drop Down (single response)','The dropdown allows evaluators to answer each question by choosing one of up to 100 options which have been provided to populate a select box.',NULL),
        (4,'horizontal_matrix_multiple','Horizontal Choice Matrix (multiple responses)','',NULL),
        (5,'vertical_matrix_multiple','Vertical Choice Matrix (multiple responses)','',NULL),
        (6,'selectbox_multiple','Drop Down (multiple responses)','',NULL),
        (7,'free_text','Free Text Comments','Allows the user to be asked for a simple free-text response. This can be used to get additional details about prior questions, or to simply ask for any comments from the evaluator regarding a specific topic.',NULL),
        (8,'date','Date Selector','',NULL),
        (9,'user','Individual Selector','',NULL),
        (10,'numeric','Numeric Field','',NULL),
        (11, 'rubric_line', 'Rubric Attribute (single response)', 'The items which make up the body of a rubric. Each item allows one response to be chosen. There must be at least one response that contains response text, while the Response Category for each one is mandatory (and will populate the header line at the top of the rubric).', NULL),
        (12, 'scale', 'Scale Item (single response)', 'The items which make up the body of a scale, sometimes called a Likert.  Each item allows one response to be chosen. The text of each response is optional, while the Response Category for each one is mandatory (and will populate the header line at the top of the scale)', NULL);


        CREATE TABLE IF NOT EXISTS `cbl_assessments_lu_response_descriptors` (
        `ardescriptor_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `one45_anchor_value` int(11) DEFAULT NULL,
        `organisation_id` int(11) NOT NULL,
        `descriptor` varchar(255) NOT NULL DEFAULT '',
        `reportable` tinyint(1) NOT NULL DEFAULT '1',
        `order` tinyint(3) NOT NULL DEFAULT '0',
        `created_date` bigint(64) NOT NULL,
        `created_by` int(11) NOT NULL,
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`ardescriptor_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        INSERT INTO `cbl_assessments_lu_response_descriptors` (`ardescriptor_id`, `one45_anchor_value`, `organisation_id`, `descriptor`, `reportable`, `order`, `created_date`, `created_by`, `updated_date`, `updated_by`, `deleted_date`)
        VALUES
        (1, NULL, 1, 'Opportunities for Growth', 1, 1, 1402703700, 1, NULL, NULL, NULL),
        (2, NULL, 1, 'Borderline LOW', 1, 2, 1402531209, 1, NULL, NULL, NULL),
        (3, NULL, 1, 'Developing', 1, 3, 1397670525, 1, NULL, NULL, NULL),
        (4, NULL, 1, 'Achieving', 1, 4, 1397670545, 1, NULL, NULL, NULL),
        (5, NULL, 1, 'Borderline HIGH', 1, 5, 1402531188, 1, NULL, NULL, NULL),
        (6, NULL, 1, 'Exceptional', 1, 6, 1402697384, 1, NULL, NULL, NULL),
        (7, NULL, 1, 'Not Applicable', 1, 7, 1402703794, 1, NULL, NULL, NULL);

        CREATE TABLE IF NOT EXISTS `cbl_assessments_lu_rubric_labels` (
        `rlabel_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `one45_choice_id` int(11) DEFAULT NULL,
        `one45_response_num` int(11) DEFAULT NULL,
        `label_type` enum('column','row','criteria','description') NOT NULL DEFAULT 'column',
        `rubric_id` int(11) unsigned NOT NULL,
        `item_id` int(11) unsigned DEFAULT NULL,
        `iresponse_id` int(11) unsigned DEFAULT NULL,
        `label` text,
        `order` tinyint(3) DEFAULT '0',
        PRIMARY KEY (`rlabel_id`),
        KEY `cbl_assessment_rubric_labels_ibfk_1` (`rubric_id`),
        CONSTRAINT `cbl_assessments_lu_rubric_labels_ibfk_1` FOREIGN KEY (`rubric_id`) REFERENCES `cbl_assessments_lu_rubrics` (`rubric_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `cbl_assessments_lu_rubrics` (
        `rubric_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `one45_element_id` int(11) DEFAULT NULL,
        `organisation_id` int(11) NOT NULL,
        `rubric_title` varchar(2048) DEFAULT NULL,
        `rubric_description` text,
        `is_scale` tinyint(1) NOT NULL DEFAULT '0',
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `created_date` bigint(64) NOT NULL,
        `created_by` int(11) NOT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`rubric_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `cbl_assessments_lu_distribution_target_types` (
        `adttype_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `name` varchar(128) NOT NULL DEFAULT '',
        `description` text,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`adttype_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        INSERT INTO `cbl_assessments_lu_distribution_target_types` (`adttype_id`, `name`, `description`, `deleted_date`)
        VALUES
        (1,'proxy_id',NULL,NULL),
        (2,'course_id',NULL,NULL),
        (3,'cgroup_id',NULL,NULL),
        (4,'group_id',NULL,NULL),
        (5,'schedule_id',NULL,NULL),
        (6,'organisation_id',NULL,NULL),
        (7,'self',NULL,NULL),
        (8,'adistribution_id',NULL,NULL),
        (9,'form_id',NULL,NULL);

        CREATE TABLE IF NOT EXISTS `cbl_assessments_lu_distribution_target_types_options` (
        `adtto_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `adttype_id` int(11) unsigned NOT NULL,
        `adtoption_id` int(11) unsigned NOT NULL,
        PRIMARY KEY (`adtto_id`,`adttype_id`,`adtoption_id`),
        KEY `adistribution_id` (`adttype_id`),
        KEY `adtoption_id` (`adtoption_id`),
        CONSTRAINT `cbl_assessments_lu_distribution_target_types_options_ibfk_2` FOREIGN KEY (`adtoption_id`) REFERENCES `cbl_assessments_lu_distribution_targets_options` (`adtoption_id`),
        CONSTRAINT `cbl_assessments_lu_distribution_target_types_options_ibfk_1` FOREIGN KEY (`adttype_id`) REFERENCES `cbl_assessments_lu_distribution_target_types` (`adttype_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        INSERT INTO `cbl_assessments_lu_distribution_target_types_options` (`adtto_id`, `adttype_id`, `adtoption_id`)
        VALUES
        (1,1,1),
        (2,1,3),
        (3,2,1),
        (4,2,2),
        (5,2,3),
        (6,3,1),
        (7,3,2),
        (8,3,3),
        (9,4,1),
        (10,4,2),
        (11,4,3),
        (12,5,1),
        (13,5,2),
        (14,6,1),
        (15,6,2),
        (16,7,2);

        CREATE TABLE IF NOT EXISTS `cbl_assessments_lu_distribution_targets_options` (
        `adtoption_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `name` varchar(128) NOT NULL DEFAULT '',
        `description` text,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`adtoption_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        INSERT INTO `cbl_assessments_lu_distribution_targets_options` (`adtoption_id`, `name`, `description`, `deleted_date`)
        VALUES
        (1,'group','Use the whole group.',NULL),
        (2,'individuals','Use Individuals.',NULL),
        (3,'schedule','Use schedule.',NULL);

        CREATE TABLE IF NOT EXISTS `cbl_assessment_rubric_authors` (
        `arauthor_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `rubric_id` int(11) unsigned NOT NULL,
        `author_type` enum('proxy_id','organisation_id','course_id') NOT NULL DEFAULT 'proxy_id',
        `author_id` int(11) NOT NULL,
        `created_date` bigint(64) NOT NULL,
        `created_by` int(11) NOT NULL,
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`arauthor_id`),
        KEY `rubric_id` (`rubric_id`),
        CONSTRAINT `cbl_assessment_rubric_authors_ibfk_1` FOREIGN KEY (`rubric_id`) REFERENCES `cbl_assessments_lu_rubrics` (`rubric_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `cbl_assessment_rubric_items` (
        `aritem_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `rubric_id` int(11) unsigned NOT NULL,
        `item_id` int(11) unsigned NOT NULL,
        `order` tinyint(3) NOT NULL DEFAULT '0',
        `enable_flagging` tinyint(1) NOT NULL DEFAULT '0',
        `deleted_date` int(11) DEFAULT NULL,
        PRIMARY KEY (`aritem_id`),
        KEY `rubric_id` (`rubric_id`),
        KEY `item_id` (`item_id`),
        CONSTRAINT `cbl_assessment_rubric_items_ibfk_1` FOREIGN KEY (`rubric_id`) REFERENCES `cbl_assessments_lu_rubrics` (`rubric_id`),
        CONSTRAINT `cbl_assessment_rubric_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `cbl_assessments_lu_items` (`item_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `cbl_assessments_lu_tags` (
        `tag_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `tag` varchar(32) NOT NULL DEFAULT '',
        `created_date` bigint(64) NOT NULL,
        `created_by` int(11) NOT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`tag_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `cbl_schedule` (
        `schedule_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `one45_rotation_id` int(11) DEFAULT NULL,
        `one45_owner_group_id` int(11) DEFAULT NULL,
        `title` varchar(128) NOT NULL DEFAULT '',
        `code` varchar(32) DEFAULT NULL,
        `description` varchar(2048) DEFAULT NULL,
        `schedule_type` enum('stream','block','rotation_stream','rotation_block') NOT NULL DEFAULT 'stream',
        `schedule_parent_id` int(11) unsigned DEFAULT '0',
        `organisation_id` int(11) NOT NULL,
        `course_id` int(11) DEFAULT NULL,
        `region_id` int(11) DEFAULT NULL,
        `facility_id` int(11) DEFAULT NULL,
        `cperiod_id` int(11) DEFAULT '0',
        `start_date` bigint(64) NOT NULL,
        `end_date` bigint(64) NOT NULL,
        `stream_block_length` int(2) DEFAULT NULL,
        `draft_id` int(11) DEFAULT NULL,
        `created_date` bigint(20) NOT NULL,
        `created_by` int(11) NOT NULL,
        `updated_date` bigint(20) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`schedule_id`),
        KEY `schedule_parent_id` (`schedule_parent_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `cbl_schedule_audience` (
        `saudience_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `one45_p_id` int(11) DEFAULT NULL,
        `schedule_id` int(11) unsigned NOT NULL,
        `schedule_slot_id` int(11) DEFAULT NULL,
        `audience_type` enum('proxy_id','course_id','cperiod_id') NOT NULL DEFAULT 'proxy_id',
        `audience_value` int(11) NOT NULL,
        `deleted_date` int(11) DEFAULT NULL,
        PRIMARY KEY (`saudience_id`),
        KEY `schedule_od` (`schedule_id`),
        CONSTRAINT `cbl_schedule_audience_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `cbl_schedule` (`schedule_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `cbl_schedule_draft_authors` (
        `cbl_schedule_draft_author_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `cbl_schedule_draft_id` int(11) DEFAULT NULL,
        `proxy_id` int(11) DEFAULT NULL,
        `created_date` int(11) DEFAULT NULL,
        `created_by` int(11) DEFAULT NULL,
        PRIMARY KEY (`cbl_schedule_draft_author_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `cbl_schedule_drafts` (
        `cbl_schedule_draft_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `draft_title` varchar(64) NOT NULL DEFAULT '',
        `status` enum('draft','live') NOT NULL DEFAULT 'draft',
        `course_id` int(11) DEFAULT NULL,
        `cperiod_id` int(11) DEFAULT '0',
        `created_date` int(11) NOT NULL,
        `created_by` int(11) NOT NULL,
        `deleted_date` int(11) DEFAULT NULL,
        `updated_date` int(11) NOT NULL,
        `updated_by` int(11) NOT NULL,
        PRIMARY KEY (`cbl_schedule_draft_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `cbl_schedule_slot_types` (
        `slot_type_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `slot_type_code` varchar(5) NOT NULL DEFAULT '',
        `slot_type_description` varchar(64) NOT NULL DEFAULT '',
        `deleted_date` int(11) DEFAULT NULL,
        PRIMARY KEY (`slot_type_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        INSERT INTO `cbl_schedule_slot_types` (`slot_type_code`, `slot_type_description`, `deleted_date`)
        VALUES
        ('OSL', 'On Service Learner', NULL),
        ('OFFSL', 'Off Service Learner', NULL);

        CREATE TABLE IF NOT EXISTS `cbl_schedule_slots` (
        `schedule_slot_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `schedule_id` int(11) NOT NULL,
        `slot_type_id` int(11) NOT NULL,
        `slot_spaces` int(11) NOT NULL DEFAULT '1',
        `created_date` int(11) NOT NULL,
        `created_by` int(11) NOT NULL,
        `deleted_date` int(11) DEFAULT NULL,
        `updated_date` int(11) NOT NULL,
        `updated_by` int(11) NOT NULL,
        PRIMARY KEY (`schedule_slot_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        SET FOREIGN_KEY_CHECKS=1;
        <?php
        $this->stop();

        return $this->run();
    }

    /**
     * Required: SQL / PHP that performs the downgrade migration.
     */
    public function down() {
        $this->record();
        ?>
        SET FOREIGN_KEY_CHECKS=0;

        DROP TABLE IF EXISTS `cbl_assessment_distribution_assessors`, `cbl_assessment_distribution_authors`, `cbl_assessment_distribution_delegators`, `cbl_assessment_distribution_schedule`, `cbl_assessment_distribution_targets`, `cbl_assessment_distributions`, `cbl_assessment_form_authors`, `cbl_assessment_form_elements`, `cbl_assessment_item_authors`, `cbl_assessment_item_objectives`, `cbl_assessment_item_tags`, `cbl_assessment_progress`, `cbl_assessment_progress_responses`, `cbl_assessment_report_audience`, `cbl_assessment_report_source_targets`, `cbl_assessment_report_sources`, `cbl_assessment_reports`, `cbl_assessment_rubric_authors`, `cbl_assessment_rubric_items`, `cbl_assessments_lu_data_source_types`, `cbl_assessments_lu_data_sources`, `cbl_assessments_lu_distribution_target_types`, `cbl_assessments_lu_distribution_target_types_options`, `cbl_assessments_lu_distribution_targets_options`, `cbl_assessments_lu_form_relationships`, `cbl_assessments_lu_forms`, `cbl_assessments_lu_item_relationships`, `cbl_assessments_lu_item_responses`, `cbl_assessments_lu_items`, `cbl_assessments_lu_itemtypes`, `cbl_assessments_lu_response_descriptors`, `cbl_assessments_lu_rubric_labels`, `cbl_assessments_lu_rubrics`, `cbl_assessments_lu_tags`, `cbl_schedule`, `cbl_schedule_audience`, `cbl_schedule_draft_authors`, `cbl_schedule_drafts`, `cbl_schedule_slot_types`, `cbl_schedule_slots`;

        SET FOREIGN_KEY_CHECKS=1;
        <?php
        $this->stop();

        return $this->run();
    }

    /**
     * Optional: PHP that verifies whether or not the changes outlined
     * in "up" are present in the active database.
     *
     * Return Values: -1 (not run) | 0 (changes not present or complete) | 1 (present)
     *
     * @return int
     */
    public function audit() {
        return -1;
    }
}
