<?php
class Migrate_2016_06_10_182935_900 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>

        CREATE TABLE `exam_lu_question_bank_folder_images` (
        `image_id` int(12) NOT NULL AUTO_INCREMENT,
        `file_name` varchar(64) NOT NULL DEFAULT '0',
        `color` varchar(64) NOT NULL,
        `order` int(12) NOT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`image_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

        CREATE TABLE `exam_lu_questiontypes` (
        `questiontype_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `shortname` varchar(128) NOT NULL DEFAULT '',
        `name` varchar(256) NOT NULL DEFAULT '',
        `description` text NOT NULL,
        `order` int(11) DEFAULT '0',
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`questiontype_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

        CREATE TABLE `exams` (
        `exam_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `organisation_id` int(11) NOT NULL,
        `title` varchar(1024) NOT NULL DEFAULT '',
        `description` text,
        `display_questions` enum('all','one','page_breaks') DEFAULT 'all',
        `random` int(1) DEFAULT NULL,
        `examsoft_exam_id` int(11) DEFAULT NULL,
        `created_date` bigint(64) NOT NULL,
        `created_by` int(11) NOT NULL,
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`exam_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

        CREATE TABLE `exam_questions` (
        `question_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
        `folder_id` int(12) DEFAULT '0',
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`question_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

        CREATE TABLE `exam_question_versions` (
        `version_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
        `question_id` int(12) unsigned NOT NULL,
        `version_count` int(11) NOT NULL DEFAULT '1',
        `questiontype_id` int(11) unsigned NOT NULL,
        `question_text` text NOT NULL,
        `question_description` longtext,
        `question_rationale` text,
        `question_correct_text` varchar(2000) DEFAULT NULL,
        `question_code` varchar(128) DEFAULT '',
        `grading_scheme` enum('full','partial','penalty') NOT NULL DEFAULT 'partial',
        `organisation_id` int(11) NOT NULL,
        `created_date` bigint(64) NOT NULL,
        `created_by` int(11) NOT NULL,
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        `examsoft_id` varchar(30) DEFAULT NULL,
        `examsoft_images_added` int(1) DEFAULT '0',
        `examsoft_flagged` int(1) DEFAULT '0',
        PRIMARY KEY (`version_id`),
        KEY `question_id` (`question_id`),
        KEY `exam_questions_versions_fk_1` (`questiontype_id`),
        KEY `examsoft_id` (`examsoft_id`),
        CONSTRAINT `exam_questions_versions_fk_1` FOREIGN KEY (`questiontype_id`) REFERENCES `exam_lu_questiontypes` (`questiontype_id`),
        CONSTRAINT `exam_questions_versions_fk_2` FOREIGN KEY (`question_id`) REFERENCES `exam_questions` (`question_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

        CREATE TABLE `exam_groups` (
        `group_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `organisation_id` int(11) NOT NULL,
        `group_title` varchar(2048) DEFAULT NULL,
        `group_description` text,
        `is_scale` tinyint(1) NOT NULL DEFAULT '0',
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `created_date` bigint(64) NOT NULL,
        `created_by` int(11) NOT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`group_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

        CREATE TABLE `exam_elements` (
        `exam_element_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `exam_id` int(11) unsigned NOT NULL,
        `element_type` enum('question','data_source','text','objective','page_break') DEFAULT NULL,
        `element_id` int(11) unsigned DEFAULT NULL,
        `element_id_version` int(11) NOT NULL DEFAULT '0',
        `element_text` text,
        `group_id` int(11) unsigned DEFAULT NULL,
        `order` tinyint(3) NOT NULL DEFAULT '0',
        `points` decimal(10,2) DEFAULT NULL,
        `deleted_date` int(11) DEFAULT NULL,
        `updated_date` int(11) NOT NULL DEFAULT '0',
        `updated_by` int(11) NOT NULL DEFAULT '0',
        `not_scored` tinyint(1) DEFAULT NULL,
        PRIMARY KEY (`exam_element_id`),
        KEY `exam_id` (`exam_id`),
        KEY `exam_elements_ibfk_2` (`group_id`),
        CONSTRAINT `exam_elements_fk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`exam_id`),
        CONSTRAINT `exam_elements_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `exam_groups` (`group_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

        CREATE TABLE `exam_adjustments` (
        `ep_adjustment_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `exam_element_id` int(11) unsigned NOT NULL,
        `exam_id` int(11) unsigned DEFAULT NULL,
        `type` enum('update_points','throw_out','full_credit','correct','incorrect','make_bonus') NOT NULL,
        `value` varchar(255) DEFAULT NULL,
        `created_date` bigint(20) NOT NULL,
        `created_by` int(11) NOT NULL,
        `deleted_date` bigint(20) DEFAULT NULL,
        PRIMARY KEY (`ep_adjustment_id`),
        KEY `exam_element_id` (`exam_element_id`),
        KEY `post_id` (`exam_id`),
        CONSTRAINT `exam_adjustments_ibfk_1` FOREIGN KEY (`exam_element_id`) REFERENCES `exam_elements` (`exam_element_id`) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `exam_adjustments_ibfk_2` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`exam_id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;

        CREATE TABLE `exam_authors` (
        `aeauthor_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `exam_id` int(11) unsigned NOT NULL,
        `author_type` enum('proxy_id','organisation_id','course_id') NOT NULL DEFAULT 'proxy_id',
        `author_id` int(11) unsigned DEFAULT NULL,
        `created_date` bigint(64) NOT NULL,
        `created_by` int(11) NOT NULL,
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`aeauthor_id`),
        KEY `exam_id` (`exam_id`),
        CONSTRAINT `exam_authors_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`exam_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

        CREATE TABLE `exam_element_highlight` (
        `highlight_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
        `exam_element_id` int(11) unsigned NOT NULL,
        `element_text` text,
        `exam_progress_id` int(12) unsigned NOT NULL,
        `proxy_id` int(12) unsigned NOT NULL,
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        PRIMARY KEY (`highlight_id`),
        KEY `exam_element_id` (`exam_element_id`),
        CONSTRAINT `exam_e_highlights_fk_1` FOREIGN KEY (`exam_element_id`) REFERENCES `exam_elements` (`exam_element_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE `exam_group_authors` (
        `egauthor_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `group_id` int(11) unsigned NOT NULL,
        `author_type` enum('proxy_id','organisation_id','course_id') NOT NULL DEFAULT 'proxy_id',
        `author_id` int(11) unsigned DEFAULT NULL,
        `created_date` bigint(64) NOT NULL,
        `created_by` int(11) NOT NULL,
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`egauthor_id`),
        KEY `group_id` (`group_id`),
        CONSTRAINT `exam_group_authors_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `exam_groups` (`group_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

        CREATE TABLE `exam_group_questions` (
        `egquestion_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `group_id` int(11) unsigned NOT NULL,
        `question_id` int(11) unsigned NOT NULL,
        `version_id` int(12) unsigned NOT NULL,
        `order` tinyint(3) NOT NULL DEFAULT '0',
        `updated_by` int(11) DEFAULT NULL,
        `updated_date` int(11) DEFAULT NULL,
        `deleted_date` int(11) DEFAULT NULL,
        PRIMARY KEY (`egquestion_id`),
        KEY `question_id` (`question_id`),
        KEY `exam_group_questions_ibfk_1` (`group_id`),
        KEY `exam_group_questions_ibfk_3` (`version_id`),
        CONSTRAINT `exam_group_questions_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `exam_groups` (`group_id`),
        CONSTRAINT `exam_group_questions_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `exam_questions` (`question_id`),
        CONSTRAINT `exam_group_questions_ibfk_3` FOREIGN KEY (`version_id`) REFERENCES `exam_question_versions` (`version_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

        CREATE TABLE `exam_posts` (
        `post_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `exam_id` int(11) unsigned NOT NULL,
        `target_type` enum('event','community','preview') DEFAULT NULL,
        `target_id` int(11) unsigned DEFAULT NULL,
        `title` varchar(255) DEFAULT NULL,
        `description` text,
        `instructions` text,
        `max_attempts` int(11) DEFAULT NULL,
        `mandatory` smallint(6) DEFAULT NULL,
        `backtrack` int(1) DEFAULT '1',
        `secure` tinyint(1) DEFAULT '0',
        `use_resume_password` int(1) DEFAULT '0',
        `resume_password` varchar(20) DEFAULT '0',
        `mark_faculty_review` tinyint(1) DEFAULT '0',
        `hide_exam` int(1) DEFAULT '0',
        `auto_save` int(5) DEFAULT '30',
        `auto_submit` int(1) DEFAULT '0',
        `use_time_limit` int(1) DEFAULT '0',
        `time_limit` int(20) DEFAULT NULL,
        `use_exam_start_date` int(1) DEFAULT '0',
        `use_exam_end_date` int(1) DEFAULT '0',
        `start_date` bigint(20) DEFAULT NULL,
        `end_date` bigint(20) DEFAULT NULL,
        `use_exam_submission_date` int(1) DEFAULT '0',
        `exam_submission_date` bigint(20) DEFAULT NULL,
        `grade_book` int(11) DEFAULT NULL,
        `release_score` int(1) DEFAULT NULL,
        `use_release_start_date` int(1) DEFAULT NULL,
        `use_release_end_date` int(1) DEFAULT NULL,
        `release_start_date` bigint(20) DEFAULT NULL,
        `release_end_date` bigint(20) DEFAULT NULL,
        `release_feedback` int(1) DEFAULT NULL,
        `use_re_attempt_threshold` int(1) DEFAULT '0',
        `re_attempt_threshold` decimal(10,2) DEFAULT NULL,
        `re_attempt_threshold_attempts` int(5) DEFAULT '0',
        `created_date` bigint(20) DEFAULT NULL,
        `created_by` int(11) DEFAULT NULL,
        `updated_date` bigint(20) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `deleted_date` bigint(20) DEFAULT NULL,
        PRIMARY KEY (`post_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

        CREATE TABLE `exam_post_exceptions` (
        `ep_exception_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `post_id` int(11) unsigned NOT NULL,
        `proxy_id` int(12) unsigned NOT NULL,
        `use_exception_max_attempts` int(1) DEFAULT '0',
        `max_attempts` int(11) DEFAULT NULL,
        `exception_start_date` bigint(20) DEFAULT NULL,
        `exception_end_date` bigint(20) DEFAULT NULL,
        `exception_submission_date` bigint(20) DEFAULT NULL,
        `use_exception_start_date` int(1) DEFAULT '0',
        `use_exception_end_date` int(1) DEFAULT '0',
        `use_exception_submission_date` int(1) DEFAULT '0',
        `use_exception_time_factor` int(1) DEFAULT '0',
        `exception_time_factor` int(5) DEFAULT '0',
        `excluded` int(1) DEFAULT '0',
        `created_date` bigint(20) DEFAULT NULL,
        `created_by` int(11) DEFAULT NULL,
        `updated_date` bigint(20) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `deleted_date` bigint(20) DEFAULT NULL,
        PRIMARY KEY (`ep_exception_id`),
        KEY `exam_post_exceptions_fk_1` (`post_id`),
        CONSTRAINT `exam_post_exceptions_fk_1` FOREIGN KEY (`post_id`) REFERENCES `exam_posts` (`post_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

        CREATE TABLE `exam_graders` (
        `exam_grader_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
        `post_id` int(11) unsigned NOT NULL,
        `cgroup_id` int(11) NOT NULL,
        `proxy_id` int(12) unsigned NOT NULL,
        PRIMARY KEY (`exam_grader_id`),
        UNIQUE KEY `exam_graders_unique_key_1` (`post_id`,`cgroup_id`,`proxy_id`),
        KEY `cgroup_id` (`cgroup_id`),
        KEY `proxy_id` (`proxy_id`),
        KEY `post_id` (`post_id`),
        CONSTRAINT `exam_graders_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `exam_posts` (`post_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

        CREATE TABLE `exam_progress` (
        `exam_progress_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
        `post_id` int(12) unsigned NOT NULL,
        `exam_id` int(12) unsigned NOT NULL,
        `proxy_id` int(12) unsigned NOT NULL,
        `progress_value` varchar(20) DEFAULT 'inprogress',
        `submission_date` int(11) DEFAULT NULL,
        `late` int(5) DEFAULT '0',
        `exam_score` int(12) DEFAULT NULL,
        `exam_value` int(11) DEFAULT NULL,
        `exam_points` decimal(10,2) DEFAULT NULL,
        `menu_open` int(1) NOT NULL DEFAULT '1',
        `created_date` bigint(64) NOT NULL DEFAULT '0',
        `created_by` int(11) NOT NULL DEFAULT '0',
        `updated_date` bigint(64) NOT NULL,
        `updated_by` int(12) unsigned NOT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`exam_progress_id`),
        KEY `content_id` (`post_id`,`proxy_id`),
        KEY `exam_id` (`exam_id`),
        KEY `post_id` (`post_id`),
        CONSTRAINT `exam_progresss_fk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`exam_id`),
        CONSTRAINT `exam_progresss_fk_2` FOREIGN KEY (`post_id`) REFERENCES `exam_posts` (`post_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

        CREATE TABLE `exam_progress_responses` (
        `exam_progress_response_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `exam_progress_id` int(11) unsigned NOT NULL,
        `exam_id` int(11) unsigned NOT NULL,
        `post_id` int(11) unsigned NOT NULL,
        `proxy_id` int(11) NOT NULL,
        `exam_element_id` int(11) unsigned NOT NULL,
        `epr_order` int(11) NOT NULL,
        `question_count` int(11) unsigned DEFAULT NULL,
        `question_type` varchar(20) DEFAULT NULL,
        `flag_question` int(5) DEFAULT NULL,
        `strike_out_answers` varchar(100) DEFAULT NULL,
        `grader_comments` text,
        `learner_comments` text,
        `mark_faculty_review` tinyint(1) DEFAULT '0',
        `score` decimal(10,2) DEFAULT NULL,
        `regrade` int(11) DEFAULT NULL,
        `graded_by` bigint(64) DEFAULT NULL,
        `graded_date` int(11) DEFAULT NULL,
        `view_date` int(11) DEFAULT NULL,
        `created_date` bigint(64) NOT NULL,
        `created_by` int(11) NOT NULL,
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `deleted_date` int(11) DEFAULT NULL,
        PRIMARY KEY (`exam_progress_response_id`),
        KEY `exam_progress_id` (`exam_progress_id`),
        KEY `exam_id` (`exam_id`),
        KEY `post_id` (`post_id`),
        KEY `aeelement_id` (`exam_element_id`),
        CONSTRAINT `exam_progresss_responses_fk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`exam_id`),
        CONSTRAINT `exam_progresss_responses_fk_2` FOREIGN KEY (`post_id`) REFERENCES `exam_posts` (`post_id`),
        CONSTRAINT `exam_progresss_responses_fk_3` FOREIGN KEY (`exam_progress_id`) REFERENCES `exam_progress` (`exam_progress_id`),
        CONSTRAINT `exam_progresss_responses_fk_4` FOREIGN KEY (`exam_element_id`) REFERENCES `exam_elements` (`exam_element_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

        CREATE TABLE `exam_progress_response_answers` (
        `epr_answer_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `epr_id` int(11) unsigned NOT NULL,
        `eqa_id` int(11) unsigned DEFAULT NULL,
        `eqm_id` int(11) unsigned DEFAULT NULL,
        `response_element_order` int(11) DEFAULT NULL,
        `response_element_letter` varchar(10) DEFAULT NULL,
        `response_value` text,
        `created_date` bigint(64) NOT NULL,
        `created_by` int(11) NOT NULL,
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`epr_answer_id`),
        KEY `epr_id` (`epr_id`),
        CONSTRAINT `epr_fk_1` FOREIGN KEY (`epr_id`) REFERENCES `exam_progress_responses` (`exam_progress_response_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

        CREATE TABLE `exam_question_answers` (
        `qanswer_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `question_id` int(11) unsigned NOT NULL,
        `version_id` int(12) unsigned NOT NULL,
        `answer_text` text,
        `answer_rationale` text,
        `correct` int(1) DEFAULT '0',
        `weight` varchar(10) DEFAULT '0',
        `order` int(4) DEFAULT '0',
        `deleted_date` bigint(64) DEFAULT NULL,
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        PRIMARY KEY (`qanswer_id`),
        KEY `question_id` (`qanswer_id`),
        KEY `exam_questions_answers_fk_1` (`question_id`),
        KEY `exam_questions_answers_fk_2` (`version_id`),
        CONSTRAINT `exam_questions_answers_fk_1` FOREIGN KEY (`question_id`) REFERENCES `exam_questions` (`question_id`),
        CONSTRAINT `exam_questions_answers_fk_2` FOREIGN KEY (`version_id`) REFERENCES `exam_question_versions` (`version_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

        CREATE TABLE `exam_question_authors` (
        `eqauthor_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `question_id` int(11) unsigned NOT NULL,
        `version_id` int(12) unsigned NOT NULL,
        `author_type` enum('proxy_id','organisation_id','course_id') NOT NULL DEFAULT 'proxy_id',
        `author_id` int(11) NOT NULL,
        `created_date` bigint(64) NOT NULL,
        `created_by` int(11) NOT NULL,
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`eqauthor_id`),
        KEY `question_id` (`question_id`),
        KEY `exam_questions_authors_fk_2` (`version_id`),
        CONSTRAINT `exam_questions_authors_fk_1` FOREIGN KEY (`question_id`) REFERENCES `exam_questions` (`question_id`),
        CONSTRAINT `exam_questions_authors_fk_2` FOREIGN KEY (`version_id`) REFERENCES `exam_question_versions` (`version_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

        CREATE TABLE `exam_question_bank_folder_authors` (
        `efauthor_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `folder_id` int(11) unsigned NOT NULL,
        `author_type` enum('proxy_id','organisation_id','course_id') NOT NULL DEFAULT 'proxy_id',
        `author_id` int(11) NOT NULL,
        `created_date` bigint(64) NOT NULL,
        `created_by` int(11) NOT NULL,
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`efauthor_id`),
        KEY `folder_id` (`folder_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

        CREATE TABLE `exam_question_bank_folders` (
        `folder_id` int(12) NOT NULL AUTO_INCREMENT,
        `parent_folder_id` int(12) NOT NULL DEFAULT '0',
        `folder_title` varchar(64) NOT NULL,
        `folder_description` text,
        `folder_order` int(10) NOT NULL DEFAULT '0',
        `image_id` int(10) DEFAULT '1',
        `organisation_id` int(12) NOT NULL DEFAULT '1',
        `created_date` bigint(64) NOT NULL DEFAULT '0',
        `created_by` int(12) NOT NULL DEFAULT '0',
        `updated_date` bigint(64) NOT NULL DEFAULT '0',
        `updated_by` int(12) NOT NULL DEFAULT '0',
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`folder_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

        CREATE TABLE `exam_question_bank_folder_organisations` (
        `folder_org_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
        `folder_id` int(12) NOT NULL,
        `organisation_id` int(12) NOT NULL DEFAULT '1',
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`folder_org_id`),
        KEY `folder_id` (`folder_id`),
        CONSTRAINT `exam_qbf_org_fk_1` FOREIGN KEY (`folder_id`) REFERENCES `exam_question_bank_folders` (`folder_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

        CREATE TABLE `exam_question_fnb_text` (
        `fnb_text_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `qanswer_id` int(11) unsigned NOT NULL,
        `text` text,
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`fnb_text_id`),
        KEY `qanswer_id` (`qanswer_id`),
        KEY `exam_questions_fnb_text_fk_1` (`qanswer_id`),
        CONSTRAINT `exam_questions_fnb_text_fk_1` FOREIGN KEY (`qanswer_id`) REFERENCES `exam_question_answers` (`qanswer_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE `exam_question_match` (
        `match_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `version_id` int(12) unsigned NOT NULL,
        `match_text` text,
        `order` int(4) DEFAULT '0',
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`match_id`),
        KEY `exam_questions_match_fk_1` (`version_id`),
        CONSTRAINT `exam_questions_match_fk_1` FOREIGN KEY (`version_id`) REFERENCES `exam_question_versions` (`version_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

        CREATE TABLE `exam_question_match_correct` (
        `eqm_correct_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `match_id` int(11) unsigned NOT NULL,
        `qanswer_id` int(11) unsigned NOT NULL,
        `correct` int(1) DEFAULT '0',
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`eqm_correct_id`),
        KEY `exam_questions_match_correct_fk_1` (`match_id`),
        KEY `exam_questions_match_correct_fk_2` (`qanswer_id`),
        CONSTRAINT `exam_questions_match_correct_fk_1` FOREIGN KEY (`match_id`) REFERENCES `exam_question_match` (`match_id`),
        CONSTRAINT `exam_questions_match_correct_fk_2` FOREIGN KEY (`qanswer_id`) REFERENCES `exam_question_answers` (`qanswer_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

        CREATE TABLE `exam_question_objectives` (
        `qobjective_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `question_id` int(11) unsigned NOT NULL,
        `objective_id` int(11) unsigned NOT NULL,
        `created_date` bigint(64) NOT NULL,
        `created_by` int(11) NOT NULL,
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`qobjective_id`),
        KEY `question_id` (`question_id`),
        CONSTRAINT `exam_questions_objectives_fk_1` FOREIGN KEY (`question_id`) REFERENCES `exam_questions` (`question_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE `exam_question_version_highlight` (
        `highlight_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
        `version_id` int(12) unsigned NOT NULL,
        `q_order` int(11) DEFAULT NULL,
        `type` varchar(64) NOT NULL,
        `question_text` text NOT NULL,
        `exam_progress_id` int(12) unsigned NOT NULL,
        `proxy_id` int(12) unsigned NOT NULL,
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        PRIMARY KEY (`highlight_id`),
        KEY `version_id` (`version_id`),
        CONSTRAINT `exam_q_v_highlights_fk_1` FOREIGN KEY (`version_id`) REFERENCES `exam_question_versions` (`version_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

        CREATE TABLE `exam_versions` (
        `exam_version_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `exam_id` int(11) unsigned DEFAULT NULL,
        `title` varchar(1024) NOT NULL DEFAULT '',
        `description` text,
        `created_date` bigint(64) NOT NULL,
        `created_by` int(11) NOT NULL,
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`exam_version_id`),
        KEY `exam_versions_fk_1` (`exam_id`),
        CONSTRAINT `exam_versions_fk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`exam_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
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

        DROP TABLE `exam_versions`;
        DROP TABLE `exam_question_version_highlight`;
        DROP TABLE `exam_question_objectives`;
        DROP TABLE `exam_question_match_correct`;
        DROP TABLE `exam_question_match`;
        DROP TABLE `exam_question_fnb_text`;
        DROP TABLE `exam_question_bank_folder_organisations`;
        DROP TABLE `exam_question_bank_folders`;
        DROP TABLE `exam_question_bank_folder_authors`;
        DROP TABLE `exam_question_authors`;
        DROP TABLE `exam_question_answers`;
        DROP TABLE `exam_progress_response_answers`;
        DROP TABLE `exam_progress_responses`;
        DROP TABLE `exam_progress`;
        DROP TABLE `exam_graders`;
        DROP TABLE `exam_post_exceptions`;
        DROP TABLE `exam_posts`;
        DROP TABLE `exam_group_questions`;
        DROP TABLE `exam_group_authors`;
        DROP TABLE `exam_element_highlight`;
        DROP TABLE `exam_authors`;
        DROP TABLE `exam_adjustments`;
        DROP TABLE `exam_elements`;
        DROP TABLE `exam_groups`;
        DROP TABLE `exam_question_versions`;
        DROP TABLE `exam_questions`;
        DROP TABLE `exams`;
        DROP TABLE `exam_lu_questiontypes`;
        DROP TABLE `exam_lu_question_bank_folder_images`;

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
        $migration = new Models_Migration();

        if ($migration->tableExists(DATABASE_NAME, "exams")) {
            return 1;
        } else {
            return 0;
        }
    }
}
