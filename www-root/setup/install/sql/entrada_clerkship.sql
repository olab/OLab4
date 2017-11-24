/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `apartment_accounts` (
  `aaccount_id` int(12) NOT NULL AUTO_INCREMENT,
  `apartment_id` int(12) NOT NULL DEFAULT '0',
  `aaccount_company` varchar(128) NOT NULL DEFAULT '',
  `aaccount_custnumber` varchar(128) NOT NULL DEFAULT '',
  `aaccount_details` text NOT NULL,
  `updated_last` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  `account_status` varchar(12) NOT NULL DEFAULT '',
  PRIMARY KEY (`aaccount_id`),
  KEY `apartment_id` (`apartment_id`),
  KEY `aaccount_company` (`aaccount_company`),
  KEY `aaccount_custnumber` (`aaccount_custnumber`),
  KEY `account_status` (`account_status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `apartment_contacts` (
  `acontact_id` int(12) NOT NULL AUTO_INCREMENT,
  `apartment_id` int(12) NOT NULL DEFAULT '0',
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `department_id` int(12) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`acontact_id`),
  KEY `apartment_id` (`apartment_id`,`proxy_id`,`department_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `apartment_photos` (
  `aphoto_id` int(12) NOT NULL AUTO_INCREMENT,
  `apartment_id` int(12) NOT NULL DEFAULT '0',
  `aphoto_name` varchar(64) NOT NULL DEFAULT '',
  `aphoto_type` varchar(32) NOT NULL DEFAULT '',
  `aphoto_size` int(32) NOT NULL DEFAULT '0',
  `aphoto_desc` text NOT NULL,
  PRIMARY KEY (`aphoto_id`),
  KEY `apartment_id` (`apartment_id`),
  KEY `aphoto_name` (`aphoto_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `apartment_regionaled_users` (
  `aregionaled_id` int(12) NOT NULL AUTO_INCREMENT,
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`aregionaled_id`),
  KEY `proxy_id` (`proxy_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `apartment_schedule` (
  `aschedule_id` int(12) NOT NULL AUTO_INCREMENT,
  `apartment_id` int(12) NOT NULL DEFAULT '0',
  `event_id` int(12) NOT NULL DEFAULT '0',
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `occupant_title` varchar(64) NOT NULL,
  `occupant_type` varchar(16) NOT NULL DEFAULT 'undergrad',
  `confirmed` int(1) NOT NULL DEFAULT '1',
  `cost_recovery` int(1) NOT NULL DEFAULT '0',
  `notes` text NOT NULL,
  `inhabiting_start` bigint(64) NOT NULL DEFAULT '0',
  `inhabiting_finish` bigint(64) NOT NULL DEFAULT '0',
  `updated_last` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  `aschedule_status` varchar(12) NOT NULL DEFAULT '',
  PRIMARY KEY (`aschedule_id`),
  KEY `apartment_id` (`apartment_id`),
  KEY `event_id` (`event_id`),
  KEY `proxy_id` (`proxy_id`),
  KEY `inhabiting_start` (`inhabiting_start`),
  KEY `inhabiting_finish` (`inhabiting_finish`),
  KEY `aschedule_status` (`aschedule_status`),
  KEY `occupant_type` (`occupant_type`),
  KEY `confirmed` (`confirmed`),
  KEY `cost_recovery` (`cost_recovery`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `apartments` (
  `apartment_id` int(12) NOT NULL AUTO_INCREMENT,
  `countries_id` int(12) NOT NULL DEFAULT '0',
  `province_id` int(12) NOT NULL DEFAULT '0',
  `apartment_province` varchar(24) NOT NULL DEFAULT '',
  `region_id` int(12) NOT NULL DEFAULT '0',
  `department_id` int(12) NOT NULL DEFAULT '0',
  `apartment_title` varchar(86) NOT NULL DEFAULT '',
  `apartment_number` varchar(12) NOT NULL DEFAULT '',
  `apartment_address` varchar(86) NOT NULL DEFAULT '',
  `apartment_postcode` varchar(12) NOT NULL DEFAULT '',
  `apartment_phone` varchar(24) NOT NULL DEFAULT '',
  `apartment_email` varchar(128) NOT NULL DEFAULT '',
  `apartment_information` text NOT NULL,
  `super_firstname` varchar(32) NOT NULL,
  `super_lastname` varchar(32) NOT NULL,
  `super_phone` varchar(32) NOT NULL,
  `super_email` varchar(128) NOT NULL,
  `keys_firstname` varchar(32) NOT NULL,
  `keys_lastname` varchar(32) NOT NULL,
  `keys_phone` varchar(32) NOT NULL,
  `keys_email` varchar(128) NOT NULL,
  `max_occupants` int(8) NOT NULL DEFAULT '0',
  `apartment_longitude` varchar(24) NOT NULL DEFAULT '',
  `apartment_latitude` varchar(24) NOT NULL DEFAULT '',
  `available_start` bigint(64) NOT NULL DEFAULT '0',
  `available_finish` bigint(64) NOT NULL DEFAULT '0',
  `updated_last` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`apartment_id`),
  KEY `region_id` (`region_id`),
  KEY `apartment_title` (`apartment_title`),
  KEY `apartment_address` (`apartment_address`),
  KEY `apartment_province` (`apartment_province`),
  KEY `max_occupants` (`max_occupants`),
  KEY `apartment_longitude` (`apartment_longitude`),
  KEY `apartment_latitude` (`apartment_latitude`),
  KEY `available_start` (`available_start`),
  KEY `available_finish` (`available_finish`),
  KEY `countries_id` (`countries_id`),
  KEY `province_id` (`province_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `category_id` int(12) NOT NULL AUTO_INCREMENT,
  `category_parent` int(12) NOT NULL DEFAULT '0',
  `category_code` varchar(12) DEFAULT NULL,
  `category_type` int(12) NOT NULL DEFAULT '0',
  `category_name` varchar(128) NOT NULL DEFAULT '',
  `category_desc` text,
  `category_min` int(12) DEFAULT NULL,
  `category_max` int(12) DEFAULT NULL,
  `category_buffer` int(12) DEFAULT NULL,
  `category_start` bigint(64) NOT NULL DEFAULT '0',
  `category_finish` bigint(64) NOT NULL DEFAULT '0',
  `subcategory_strict` int(1) NOT NULL DEFAULT '0',
  `category_expiry` bigint(64) NOT NULL DEFAULT '0',
  `category_status` varchar(12) NOT NULL DEFAULT 'published',
  `category_order` int(3) NOT NULL DEFAULT '0',
  `organisation_id` int(12) DEFAULT NULL,
  `rotation_id` int(12) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(11) NOT NULL,
  PRIMARY KEY (`category_id`),
  KEY `category_parent` (`category_parent`),
  KEY `category_code` (`category_code`),
  KEY `category_type` (`category_type`),
  KEY `category_name` (`category_name`),
  KEY `category_min` (`category_min`),
  KEY `category_max` (`category_max`),
  KEY `category_start` (`category_start`),
  KEY `category_finish` (`category_finish`),
  KEY `subcategory_strict` (`subcategory_strict`),
  KEY `category_expiry` (`category_expiry`),
  KEY `category_status` (`category_status`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `categories` VALUES (1,0,NULL,12,'School of Medicine',NULL,NULL,NULL,NULL,0,0,0,0,'published',0,NULL,0,0,0),(2,1,NULL,13,'All Students',NULL,NULL,NULL,NULL,0,1924927200,0,0,'published',0,NULL,0,0,0),(3,2,NULL,17,'Example Stream',NULL,9,9,NULL,0,1924927200,0,0,'published',0,NULL,0,0,0),(4,3,NULL,32,'Pediatrics',NULL,9,9,NULL,0,1924927200,0,0,'published',4,NULL,4,0,0),(5,3,NULL,32,'Obstetrics & Gynecology',NULL,9,9,NULL,0,1924927200,0,0,'published',3,NULL,3,0,0),(6,3,NULL,32,'Perioperative',NULL,9,9,NULL,0,1924927200,0,0,'published',5,NULL,5,0,0),(7,3,NULL,32,'Surgery - Urology',NULL,9,9,NULL,0,1924927200,0,0,'published',7,NULL,7,0,0),(8,3,NULL,32,'Surgery - Orthopedic',NULL,9,9,NULL,0,1924927200,0,0,'published',8,NULL,8,0,0),(9,3,NULL,32,'Family Medicine',NULL,9,9,NULL,0,1924927200,0,0,'published',1,NULL,1,0,0),(10,3,NULL,32,'Psychiatry',NULL,9,9,NULL,0,1924927200,0,0,'published',6,NULL,6,0,0),(11,3,NULL,32,'Medicine',NULL,9,9,NULL,0,1924927200,0,0,'published',2,NULL,2,0,0),(12,3,NULL,32,'Integrated',NULL,2,2,NULL,0,1924927200,0,0,'published',9,NULL,9,0,0);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `category_departments` (
  `cdep_id` int(12) NOT NULL AUTO_INCREMENT,
  `category_id` int(12) NOT NULL DEFAULT '0',
  `department_id` int(12) NOT NULL DEFAULT '0',
  `contact_id` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cdep_id`),
  KEY `category_id` (`category_id`),
  KEY `department_id` (`department_id`),
  KEY `contact_id` (`contact_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `category_type` (
  `ctype_id` int(12) NOT NULL AUTO_INCREMENT,
  `ctype_parent` int(12) NOT NULL DEFAULT '0',
  `ctype_name` varchar(128) NOT NULL DEFAULT '',
  `ctype_desc` text NOT NULL,
  `require_min` int(11) NOT NULL DEFAULT '0',
  `require_max` int(11) NOT NULL DEFAULT '0',
  `require_buffer` int(11) NOT NULL DEFAULT '0',
  `require_start` int(11) NOT NULL DEFAULT '0',
  `require_finish` int(11) NOT NULL DEFAULT '0',
  `require_expiry` int(11) NOT NULL DEFAULT '0',
  `ctype_filterable` int(11) NOT NULL DEFAULT '0',
  `ctype_order` int(3) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ctype_id`),
  KEY `ctype_parent` (`ctype_parent`),
  KEY `require_start` (`require_start`),
  KEY `require_finish` (`require_finish`),
  KEY `require_expiry` (`require_expiry`),
  KEY `ctype_filterable` (`ctype_filterable`),
  KEY `ctype_order` (`ctype_order`)
) ENGINE=MyISAM AUTO_INCREMENT=33 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `category_type` VALUES (1,30,'Institution','',0,0,0,0,0,0,0,0),(2,30,'Faculty','',0,0,0,0,0,0,0,0),(12,30,'School','',0,0,0,0,0,0,0,0),(13,30,'Graduating Year','',0,0,0,0,0,0,0,0),(14,30,'Phase','',0,0,0,0,0,0,0,0),(15,30,'Unit','',0,0,0,0,0,0,0,0),(16,30,'Block','',0,0,0,0,0,0,0,0),(17,30,'Stream','',0,0,0,0,0,0,0,0),(19,30,'Selective','',0,0,0,0,0,0,0,0),(20,30,'Course Grouping','',0,0,0,0,0,0,0,0),(21,30,'Course','',0,0,0,0,0,0,0,0),(22,30,'Date Period','',0,0,0,0,0,0,0,0),(23,0,'Downtime','',0,0,0,0,0,0,0,1),(24,23,'Holiday Period','',0,0,0,0,0,0,0,0),(25,23,'Vacation Period','',0,0,0,0,0,0,0,0),(26,23,'Sick Leave','',0,0,0,0,0,0,0,0),(27,23,'Maternity Leave','',0,0,0,0,0,0,0,0),(28,23,'Personal Leave','',0,0,0,0,0,0,0,0),(29,23,'Leave Of Absense','',0,0,0,0,0,0,0,0),(30,0,'Default Types','',0,0,0,0,0,0,0,0),(31,30,'Elective','',0,0,0,0,0,0,0,0),(32,30,'Rotation','',0,0,0,0,0,0,0,0);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `electives` (
  `electives_id` int(12) NOT NULL AUTO_INCREMENT,
  `event_id` int(12) NOT NULL,
  `geo_location` varchar(15) NOT NULL DEFAULT 'National',
  `department_id` int(12) NOT NULL,
  `discipline_id` int(11) NOT NULL,
  `sub_discipline` varchar(100) DEFAULT NULL,
  `schools_id` int(11) NOT NULL,
  `other_medical_school` varchar(150) DEFAULT NULL,
  `objective` text NOT NULL,
  `preceptor_prefix` varchar(10) DEFAULT NULL,
  `preceptor_first_name` varchar(50) DEFAULT NULL,
  `preceptor_last_name` varchar(50) NOT NULL,
  `address` varchar(250) NOT NULL,
  `countries_id` int(12) NOT NULL,
  `city` varchar(100) NOT NULL,
  `prov_state` varchar(200) NOT NULL,
  `region_id` int(12) NOT NULL DEFAULT '0',
  `postal_zip_code` varchar(20) DEFAULT NULL,
  `fax` varchar(25) DEFAULT NULL,
  `phone` varchar(25) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(12) NOT NULL,
  PRIMARY KEY (`electives_id`),
  KEY `region_id` (`region_id`),
  KEY `event_id` (`event_id`),
  KEY `department_id` (`department_id`),
  KEY `discipline_id` (`discipline_id`),
  KEY `schools_id` (`schools_id`),
  KEY `countries_id` (`countries_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eval_answers` (
  `answer_id` int(12) NOT NULL AUTO_INCREMENT,
  `question_id` int(12) NOT NULL DEFAULT '0',
  `answer_type` varchar(50) NOT NULL DEFAULT '',
  `answer_label` varchar(50) NOT NULL DEFAULT '',
  `answer_value` varchar(50) NOT NULL DEFAULT '',
  `answer_lastmod` bigint(64) DEFAULT NULL,
  PRIMARY KEY (`answer_id`),
  KEY `question_id` (`question_id`),
  KEY `answer_value` (`answer_value`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eval_approved` (
  `approved_id` int(12) NOT NULL AUTO_INCREMENT,
  `notification_id` int(12) NOT NULL DEFAULT '0',
  `completed_id` int(12) NOT NULL DEFAULT '0',
  `modified_last` bigint(64) NOT NULL DEFAULT '0',
  `modified_by` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`approved_id`),
  KEY `notification_id` (`notification_id`),
  KEY `completed_id` (`completed_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eval_completed` (
  `completed_id` int(12) NOT NULL AUTO_INCREMENT,
  `notification_id` int(12) NOT NULL DEFAULT '0',
  `instructor_id` varchar(24) NOT NULL DEFAULT '0',
  `completed_status` varchar(12) NOT NULL DEFAULT 'pending',
  `completed_lastmod` bigint(64) NOT NULL DEFAULT '0',
  PRIMARY KEY (`completed_id`),
  KEY `notification_id` (`notification_id`),
  KEY `instructor_id` (`instructor_id`),
  KEY `completed_status` (`completed_status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eval_forms` (
  `form_id` int(12) NOT NULL AUTO_INCREMENT,
  `form_type` varchar(12) NOT NULL DEFAULT '',
  `nmessage_id` int(12) NOT NULL DEFAULT '0',
  `form_title` varchar(128) NOT NULL DEFAULT '',
  `form_author` int(12) NOT NULL DEFAULT '0',
  `form_desc` text NOT NULL,
  `form_status` varchar(12) NOT NULL DEFAULT 'published',
  `form_lastmod` bigint(64) DEFAULT NULL,
  PRIMARY KEY (`form_id`),
  KEY `form_type` (`form_type`),
  KEY `nmessage_id` (`nmessage_id`),
  KEY `form_status` (`form_status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eval_questions` (
  `question_id` int(12) NOT NULL AUTO_INCREMENT,
  `form_id` int(12) NOT NULL DEFAULT '0',
  `question_text` text NOT NULL,
  `question_style` varchar(50) NOT NULL DEFAULT '',
  `question_required` varchar(50) NOT NULL DEFAULT '',
  `question_lastmod` bigint(64) DEFAULT NULL,
  PRIMARY KEY (`question_id`),
  KEY `form_id` (`form_id`),
  KEY `question_required` (`question_required`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eval_results` (
  `result_id` int(12) NOT NULL AUTO_INCREMENT,
  `completed_id` int(12) NOT NULL DEFAULT '0',
  `answer_id` int(12) NOT NULL DEFAULT '0',
  `result_value` text NOT NULL,
  `result_lastmod` bigint(64) NOT NULL DEFAULT '0',
  PRIMARY KEY (`result_id`),
  KEY `completed_id` (`completed_id`),
  KEY `answer_id` (`answer_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluations` (
  `item_id` int(12) NOT NULL AUTO_INCREMENT,
  `form_id` int(12) NOT NULL DEFAULT '0',
  `active` int(1) NOT NULL DEFAULT '0',
  `category_id` int(12) NOT NULL DEFAULT '0',
  `category_recurse` int(2) NOT NULL DEFAULT '1',
  `item_title` varchar(128) NOT NULL DEFAULT '',
  `item_maxinstances` int(4) NOT NULL DEFAULT '1',
  `item_start` int(12) NOT NULL DEFAULT '1',
  `item_end` int(12) NOT NULL DEFAULT '30',
  `item_status` varchar(12) NOT NULL DEFAULT 'published',
  `modified_last` bigint(64) NOT NULL DEFAULT '0',
  `modified_by` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`item_id`),
  KEY `form_id` (`form_id`),
  KEY `category_id` (`category_id`),
  KEY `item_status` (`item_status`),
  KEY `item_end` (`item_end`),
  KEY `item_start` (`item_start`),
  KEY `item_maxinstances` (`item_maxinstances`),
  KEY `category_recurse` (`category_recurse`),
  KEY `active` (`active`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_contacts` (
  `econtact_id` int(12) NOT NULL AUTO_INCREMENT,
  `event_id` int(12) NOT NULL DEFAULT '0',
  `econtact_type` varchar(12) NOT NULL DEFAULT 'student',
  `etype_id` int(12) NOT NULL DEFAULT '0',
  `econtact_parent` int(12) NOT NULL DEFAULT '0',
  `econtact_desc` text,
  `econtact_start` bigint(64) NOT NULL DEFAULT '0',
  `econtact_finish` bigint(64) NOT NULL DEFAULT '0',
  `econtact_status` varchar(12) NOT NULL DEFAULT 'published',
  `econtact_order` int(3) NOT NULL DEFAULT '0',
  PRIMARY KEY (`econtact_id`),
  KEY `event_id` (`event_id`),
  KEY `econtact_type` (`econtact_type`),
  KEY `etype_id` (`etype_id`),
  KEY `econtact_parent` (`econtact_parent`),
  KEY `econtact_order` (`econtact_order`),
  KEY `econtact_status` (`econtact_status`),
  KEY `econtact_finish` (`econtact_finish`),
  KEY `econtact_start` (`econtact_start`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_locations` (
  `elocation_id` int(12) NOT NULL AUTO_INCREMENT,
  `event_id` int(12) NOT NULL DEFAULT '0',
  `location_id` int(12) NOT NULL DEFAULT '0',
  `elocation_start` bigint(64) NOT NULL DEFAULT '0',
  `elocation_finish` bigint(64) NOT NULL DEFAULT '0',
  `elocation_status` varchar(12) NOT NULL DEFAULT 'published',
  `elocation_order` int(3) NOT NULL DEFAULT '0',
  PRIMARY KEY (`elocation_id`),
  KEY `event_id` (`event_id`),
  KEY `location_id` (`location_id`),
  KEY `elocation_start` (`elocation_start`),
  KEY `elocation_finish` (`elocation_finish`),
  KEY `elocation_status` (`elocation_status`),
  KEY `elocation_order` (`elocation_order`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `events` (
  `event_id` int(12) NOT NULL AUTO_INCREMENT,
  `category_id` int(12) NOT NULL DEFAULT '0',
  `rotation_id` int(12) NOT NULL DEFAULT '0',
  `region_id` int(12) NOT NULL DEFAULT '0',
  `event_title` varchar(255) NOT NULL DEFAULT '',
  `event_desc` text,
  `event_start` bigint(64) NOT NULL DEFAULT '0',
  `event_finish` bigint(64) NOT NULL DEFAULT '0',
  `event_expiry` bigint(64) NOT NULL DEFAULT '0',
  `accessible_start` bigint(64) NOT NULL DEFAULT '0',
  `accessible_finish` bigint(64) NOT NULL DEFAULT '0',
  `modified_last` bigint(64) NOT NULL DEFAULT '0',
  `modified_by` int(12) NOT NULL DEFAULT '0',
  `event_type` varchar(12) NOT NULL DEFAULT 'academic',
  `event_access` varchar(12) NOT NULL DEFAULT 'public',
  `event_status` varchar(12) NOT NULL DEFAULT 'published',
  `requires_apartment` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`event_id`),
  KEY `category_id` (`category_id`),
  KEY `region_id` (`region_id`),
  KEY `event_type` (`event_type`),
  KEY `event_access` (`event_access`),
  KEY `event_status` (`event_status`),
  KEY `accessible_finish` (`accessible_finish`),
  KEY `accessible_start` (`accessible_start`),
  KEY `event_expiry` (`event_expiry`),
  KEY `event_finish` (`event_finish`),
  KEY `event_start` (`event_start`),
  KEY `requires_apartment` (`requires_apartment`),
  KEY `rotation_id` (`rotation_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `global_lu_rotations` (
  `rotation_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `rotation_title` varchar(24) DEFAULT NULL,
  `percent_required` int(3) NOT NULL,
  `percent_period_complete` int(3) NOT NULL,
  `course_id` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`rotation_id`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `global_lu_rotations` VALUES (1,'Family Medicine',50,50,0),(2,'Medicine',50,50,0),(3,'Obstetrics & Gynecology',50,50,0),(4,'Pediatrics',50,50,0),(5,'Perioperative',50,50,0),(6,'Psychiatry',50,50,0),(7,'Surgery-Urology',50,50,0),(8,'Surgery-Orthopedic',50,50,0),(9,'Integrated',50,50,0);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logbook_deficiency_plans` (
  `ldeficiency_plan_id` int(12) NOT NULL AUTO_INCREMENT,
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `rotation_id` int(12) NOT NULL DEFAULT '0',
  `plan_body` text,
  `timeline_start` int(12) NOT NULL DEFAULT '0',
  `timeline_finish` int(12) NOT NULL DEFAULT '0',
  `clerk_accepted` int(1) NOT NULL DEFAULT '0',
  `administrator_accepted` int(1) NOT NULL DEFAULT '0',
  `administrator_comments` text,
  `administrator_id` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ldeficiency_plan_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logbook_entries` (
  `lentry_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `proxy_id` int(12) unsigned NOT NULL,
  `encounter_date` int(12) NOT NULL,
  `updated_date` bigint(64) unsigned NOT NULL DEFAULT '0',
  `patient_info` varchar(30) NOT NULL,
  `agerange_id` int(12) unsigned NOT NULL DEFAULT '0',
  `gender` varchar(1) NOT NULL DEFAULT '0',
  `rotation_id` int(12) unsigned NOT NULL DEFAULT '0',
  `llocation_id` int(12) unsigned NOT NULL DEFAULT '0',
  `lsite_id` int(11) NOT NULL,
  `comments` text,
  `reflection` text NOT NULL,
  `participation_level` int(2) NOT NULL DEFAULT '2',
  `entry_active` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`lentry_id`),
  KEY `proxy_id` (`proxy_id`,`updated_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logbook_entry_checklist` (
  `lechecklist_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `proxy_id` int(12) unsigned NOT NULL,
  `rotation_id` int(12) unsigned NOT NULL,
  `checklist` bigint(64) unsigned NOT NULL,
  `updated_date` bigint(64) NOT NULL,
  PRIMARY KEY (`lechecklist_id`),
  UNIQUE KEY `proxy_id` (`proxy_id`,`rotation_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logbook_entry_evaluations` (
  `leevaluation_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `levaluation_id` int(12) unsigned NOT NULL,
  `item_status` int(1) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL,
  PRIMARY KEY (`leevaluation_id`),
  UNIQUE KEY `levaluation_id` (`levaluation_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logbook_entry_objectives` (
  `leobjective_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `lentry_id` int(12) unsigned NOT NULL DEFAULT '0',
  `objective_id` int(12) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`leobjective_id`),
  KEY `lentry_id` (`lentry_id`,`objective_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logbook_entry_procedures` (
  `leprocedure_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `lentry_id` int(12) unsigned NOT NULL DEFAULT '0',
  `lprocedure_id` int(12) unsigned NOT NULL DEFAULT '0',
  `level` smallint(6) NOT NULL COMMENT 'Level of involvement',
  PRIMARY KEY (`leprocedure_id`),
  KEY `lentry_id` (`lentry_id`,`lprocedure_id`),
  KEY `lentry_id_2` (`lentry_id`),
  KEY `lprocedure_id` (`lprocedure_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logbook_location_types` (
  `llocation_type_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `lltype_id` int(11) NOT NULL,
  `llocation_id` int(11) NOT NULL,
  PRIMARY KEY (`llocation_type_id`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `logbook_location_types` VALUES (1,1,1),(2,1,3),(3,2,6),(4,1,5),(5,1,9),(6,2,2),(7,2,4),(8,2,5),(9,2,7),(10,2,8),(11,3,10),(12,3,11);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logbook_lu_agerange` (
  `agerange_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `rotation_id` int(12) unsigned NOT NULL DEFAULT '0',
  `age` varchar(8) DEFAULT NULL,
  PRIMARY KEY (`agerange_id`)
) ENGINE=MyISAM AUTO_INCREMENT=22 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `logbook_lu_agerange` VALUES (1,0,'  < 1'),(2,0,' 1 - 4'),(3,0,' 5 - 14'),(4,0,'15 - 24'),(5,0,'25 - 34'),(6,0,'35 - 44'),(7,0,'45 - 54'),(8,0,'55 - 64'),(9,0,'65 - 74'),(10,0,'  75+'),(11,5,'  < 1m'),(12,5,'  < 1w'),(13,5,'  < 6m'),(14,5,'  < 12m'),(15,5,'  < 60m'),(16,5,'  5-12'),(17,5,'13 - 19'),(18,5,'20 - 64'),(19,6,' 5 - 11'),(20,6,'12 - 17'),(21,6,'18 - 34');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logbook_lu_checklist` (
  `lchecklist_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `rotation_id` int(12) unsigned NOT NULL,
  `line` int(11) DEFAULT NULL,
  `type` int(11) NOT NULL,
  `indent` int(11) DEFAULT NULL,
  `item` varchar(255) NOT NULL,
  PRIMARY KEY (`lchecklist_id`)
) ENGINE=MyISAM AUTO_INCREMENT=64 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `logbook_lu_checklist` VALUES (1,2,1,1,0,'Checklist for Family Medicine:'),(2,2,2,2,2,'Learning Plan (see core doc, p. 26)'),(3,2,3,1,1,'Midpoint Review (to be completed by 3rd Friday of rotation)'),(4,2,5,2,2,'Review Logbook with Preceptor'),(5,2,6,2,2,'Review Learning Plan'),(6,2,7,2,2,'Formative MCQ Exam (on 3rd Friday of rotation)'),(7,2,8,1,1,'In the final (6th) week:'),(8,2,9,2,2,'Show completed logbook'),(9,2,10,2,2,'Present Project  to clinic (by 6th Friday; see core doc, p. 28)'),(10,2,11,2,2,'Present Project to peers/examiners (by 6th Fri; core doc, p.28)'),(11,2,12,2,2,'Have completed 4 mini-CEX (see core doc, p. 28)'),(12,2,13,2,2,'Final ITER (due on 6th Friday of rotation)'),(13,2,14,2,2,'Summative MCQ Exam (on 6th Friday of rotation)'),(14,1,1,1,0,'Checklist for Emergency Medicine:'),(15,1,2,2,2,'Daily Shift Reports'),(16,1,3,1,1,'Midpoint Review '),(17,1,4,2,2,'Review Logbook with Preceptor'),(18,1,5,1,1,'In the final week:'),(19,1,6,2,2,'Show completed logbook'),(20,1,7,2,2,'Final ITER '),(21,1,8,2,2,'Summative MCQ Exam '),(22,3,1,1,0,'Checklist for Internal Medicine:'),(23,3,2,1,1,'Midpoint Review (to be completed by 6th Friday of rotation)'),(24,3,3,2,2,'Review Logbook with Preceptor'),(25,3,4,2,2,'Formative MCQ Exam (on 6th Friday of rotation)'),(26,3,5,2,2,'Formative Mid-term OSCE'),(27,3,6,1,1,'In the final (12th) week:'),(28,3,7,2,2,'Show completed logbook'),(29,3,8,2,2,'Final ITER (due on 12th Friday of rotation)'),(30,3,9,2,2,'Summative MCQ Exam'),(31,4,1,1,0,'Checklist for Obstetrics & Gynecology:'),(32,4,2,1,1,'Midpoint Review (to be completed by 2nd week of rotation)'),(33,4,3,2,2,'Review Logbook with Preceptor'),(34,4,4,2,2,'Formative MCQ Exam (on 2nd week of rotation)'),(35,4,5,1,1,'In the final (4th) week:'),(36,4,6,2,2,'Show completed logbook'),(37,4,7,2,2,'Final ITER (due on 4th Friday of rotation)'),(38,4,8,2,2,'Summative MCQ Exam'),(39,5,1,1,0,'Checklist for Pediatrics:'),(40,5,2,1,1,'Midpoint Review (to be completed by mid rotation)'),(41,5,3,2,2,'Review Logbook with Preceptor'),(42,5,4,2,2,'Formative OSCE (mid-point of rotation)'),(43,5,5,1,1,'In the final week:'),(44,5,6,2,2,'Show completed logbook'),(45,5,7,2,2,'Final ITER'),(46,5,8,2,2,'Summative MCQ Exam'),(47,6,1,1,0,'Checklist for Psychiatry:'),(48,6,2,1,1,'Midpoint Review (to be completed by mid rotation)'),(49,6,3,2,2,'Review Logbook with Preceptor'),(50,6,4,2,2,'Formative VOSCE (mid-point of rotation)'),(51,6,5,1,1,'In the final week:'),(52,6,6,2,2,'Show completed logbook'),(53,6,7,2,2,'Final ITER'),(54,6,8,2,2,'Summative MCQ Exam'),(55,6,9,2,2,'Evaluation of Psychiatric Interviewing Skills'),(56,7,1,1,0,'Checklist for Surgery / Anesthesia:'),(57,7,2,1,1,'Midpoint Review (to be completed by mid rotation)'),(58,7,3,2,2,'Review Logbook with Preceptor'),(59,7,4,2,2,'Formative MCQ (mid-point of rotation)'),(60,7,5,1,1,'In the final week:'),(61,7,6,2,2,'Show completed logbook'),(62,7,7,2,2,'Final ITER'),(63,7,8,2,2,'Summative MCQ Exam');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logbook_lu_evaluations` (
  `levaluation_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `rotation_id` int(12) unsigned NOT NULL,
  `line` int(11) DEFAULT NULL,
  `type` int(11) NOT NULL,
  `indent` int(11) DEFAULT NULL,
  `item` varchar(255) NOT NULL,
  PRIMARY KEY (`levaluation_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logbook_lu_location_types` (
  `lltype_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `location_type` varchar(32) DEFAULT NULL,
  `location_type_short` varchar(8) DEFAULT NULL,
  PRIMARY KEY (`lltype_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `logbook_lu_location_types` VALUES (1,'Ambulatory','Amb'),(2,'Inpatient','Inp'),(3,'Alternative','Alt');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logbook_lu_locations` (
  `llocation_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `location` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`llocation_id`)
) ENGINE=MyISAM AUTO_INCREMENT=17 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `logbook_lu_locations` VALUES (1,'Office / Clinic'),(2,'Hospital Ward'),(3,'Emergency'),(4,'OR'),(5,'OSCE'),(6,'Bedside Teaching Rounds'),(7,'Case Base Teaching Rounds'),(8,'Patients Home'),(9,'Nursing Home'),(10,'Community Site'),(11,'Computer Interactive Case'),(12,'Day Surgery'),(13,'Mega code'),(14,'Seminar Blocks'),(15,'HPS'),(16,'Nursery');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logbook_lu_procedures` (
  `lprocedure_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `procedure` varchar(60) NOT NULL,
  PRIMARY KEY (`lprocedure_id`)
) ENGINE=MyISAM AUTO_INCREMENT=26 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `logbook_lu_procedures` VALUES (1,'ABG'),(2,'Dictation-discharge'),(3,'Dictation-letter'),(4,'Cervical exam/labour'),(5,'Delivery, norm vaginal'),(6,'Delivery, placenta'),(7,'PAP smear'),(8,'Pelvic exam'),(9,'Perineal repair'),(10,'Pessary insert/remove'),(11,'Growth curve'),(12,'Infant/child immun'),(13,'Otoscopy, child'),(14,'Cast/splint'),(15,'ETT intubation'),(16,'Facemask ventilation'),(17,'IV catheter'),(18,'IV setup'),(19,'OR monitors'),(20,'PCA setup'),(21,'Slit lamp exam'),(22,'Suturing'),(23,'Venipuncture'),(24,'NG tube'),(25,'Surgical technique/OR assist');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logbook_lu_sites` (
  `lsite_id` int(11) NOT NULL AUTO_INCREMENT,
  `site_type` int(11) DEFAULT NULL,
  `site_name` varchar(64) NOT NULL,
  PRIMARY KEY (`lsite_id`)
) ENGINE=MyISAM AUTO_INCREMENT=15 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `logbook_lu_sites` VALUES (1,1,'Brockville General Hospital'),(2,1,'Brockville Pyschiatric Hospital'),(3,1,'CHEO'),(4,1,'Hotel Dieu Hospital'),(5,1,'Kingston General Hospital'),(6,1,'Lakeridge Health'),(7,1,'Markam Stouffville Hospital'),(8,1,'Ongwanada'),(9,1,'Peterborough Regional Health Centre'),(10,1,'Providence Continuing Care Centre'),(11,1,'Queensway Carleton Hospital'),(12,1,'Quinte Health Care'),(13,1,'Weenebayko General Hospital'),(14,1,'Queen\'s University');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logbook_mandatory_objective_locations` (
  `lmolocation_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `lmobjective_id` int(11) DEFAULT NULL,
  `lltype_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`lmolocation_id`)
) ENGINE=MyISAM AUTO_INCREMENT=118 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `logbook_mandatory_objective_locations` VALUES (1,98,1),(2,99,1),(3,100,1),(4,101,1),(5,102,1),(6,103,1),(7,104,1),(8,105,1),(9,106,1),(10,107,1),(11,108,1),(12,109,1),(13,110,1),(14,111,1),(15,112,1),(16,113,1),(17,77,1),(18,77,2),(19,78,1),(20,78,2),(21,79,1),(22,79,2),(23,80,1),(24,80,2),(25,81,1),(26,81,2),(27,82,1),(28,82,2),(29,83,1),(30,83,2),(31,84,1),(32,84,2),(33,85,2),(34,86,1),(35,86,2),(36,87,1),(37,87,2),(38,88,1),(39,88,2),(40,89,1),(41,89,2),(42,90,1),(43,90,2),(44,91,1),(45,91,2),(46,92,1),(47,92,2),(48,93,1),(49,93,2),(50,95,1),(51,95,2),(52,97,1),(53,97,2),(54,66,1),(55,67,1),(56,68,2),(57,69,2),(58,70,2),(59,71,1),(60,72,1),(61,73,1),(62,74,1),(63,75,1),(64,76,1),(65,13,2),(66,44,2),(67,49,1),(68,49,2),(69,51,1),(70,37,1),(71,37,2),(72,42,1),(73,42,2),(74,26,1),(75,26,2),(76,12,1),(77,12,2),(78,27,1),(79,27,2),(80,28,1),(81,28,2),(82,30,1),(83,30,2),(84,14,1),(85,15,1),(86,16,1),(87,17,1),(88,17,2),(89,18,1),(90,19,1),(91,19,2),(92,20,1),(93,21,1),(94,22,1),(95,22,2),(96,23,1),(97,23,2),(98,24,1),(99,24,2),(100,25,1),(101,25,2),(102,1,1),(103,2,1),(104,4,1),(105,4,2),(106,5,1),(107,6,1),(108,6,2),(109,7,1),(110,8,1),(111,9,1),(112,9,2),(113,10,1),(114,10,2),(115,11,1),(116,11,2),(117,13,1);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logbook_mandatory_objectives` (
  `lmobjective_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `rotation_id` int(12) unsigned DEFAULT NULL,
  `objective_id` int(12) unsigned DEFAULT NULL,
  `number_required` int(2) NOT NULL DEFAULT '1',
  `grad_year_min` int(11) NOT NULL DEFAULT '2011',
  `grad_year_max` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`lmobjective_id`),
  KEY `rotation_id` (`rotation_id`),
  KEY `objective_id` (`objective_id`),
  KEY `number_required` (`number_required`)
) ENGINE=MyISAM AUTO_INCREMENT=114 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `logbook_mandatory_objectives` VALUES (1,8,201,1,2011,0),(2,8,202,1,2011,0),(3,8,203,1,2011,0),(4,8,207,1,2011,0),(5,8,208,1,2011,0),(6,8,209,1,2011,0),(7,8,210,1,2011,0),(8,8,211,1,2011,0),(9,8,212,1,2011,0),(10,8,213,1,2011,0),(11,8,214,1,2011,0),(12,6,215,1,2011,0),(13,8,216,1,2011,0),(14,7,204,1,2011,0),(15,7,205,1,2011,0),(16,7,206,1,2011,0),(17,7,207,1,2011,0),(18,7,208,1,2011,0),(19,7,209,1,2011,0),(20,7,210,1,2011,0),(21,7,211,1,2011,0),(22,7,212,1,2011,0),(23,7,213,1,2011,0),(24,7,214,1,2011,0),(25,7,216,1,2011,0),(26,6,217,1,2011,0),(27,6,218,1,2011,0),(28,6,219,1,2011,0),(29,5,220,1,2011,0),(30,6,221,1,2011,0),(31,5,222,1,2011,0),(32,5,223,1,2011,0),(33,5,224,1,2011,0),(34,5,225,1,2011,0),(35,5,226,1,2011,0),(36,5,227,2,2011,0),(37,5,228,1,2011,0),(38,5,229,2,2011,0),(39,5,230,1,2011,0),(40,5,201,1,2011,0),(41,5,202,1,2011,0),(42,5,233,1,2011,0),(43,4,234,1,2011,0),(44,4,235,1,2011,0),(45,4,236,1,2011,0),(46,4,237,1,2011,0),(47,4,238,1,2011,0),(48,4,239,1,2011,0),(49,4,240,1,2011,0),(50,4,241,1,2011,0),(51,4,242,1,2011,0),(52,4,243,1,2011,0),(53,4,244,1,2011,0),(54,4,245,1,2011,0),(55,4,246,1,2011,0),(56,4,247,1,2011,0),(57,4,248,1,2011,0),(58,4,249,1,2011,0),(59,4,250,1,2011,0),(60,4,251,1,2011,0),(61,4,252,1,2011,0),(62,4,253,1,2011,0),(63,4,254,1,2011,0),(64,4,255,1,2011,0),(65,4,256,1,2011,0),(66,3,257,1,2011,0),(67,3,258,1,2011,0),(68,3,259,1,2011,0),(69,3,260,1,2011,0),(70,3,261,1,2011,0),(71,3,262,1,2011,0),(72,3,263,1,2011,0),(73,3,264,1,2011,0),(74,3,265,1,2011,0),(75,3,266,1,2011,0),(76,3,267,1,2011,0),(77,2,268,1,2011,0),(78,2,269,1,2011,0),(79,2,270,1,2011,0),(80,2,271,1,2011,0),(81,2,272,1,2011,0),(82,2,273,1,2011,0),(83,2,274,1,2011,0),(84,2,275,1,2011,0),(85,2,276,1,2011,0),(86,2,277,1,2011,0),(87,2,278,1,2011,0),(88,2,279,1,2011,0),(89,2,280,1,2011,0),(90,2,281,1,2011,0),(91,2,282,1,2011,0),(92,2,283,1,2011,0),(93,2,284,1,2011,0),(94,2,285,1,2011,0),(95,2,286,1,2011,0),(96,2,287,1,2011,0),(97,2,288,1,2011,0),(98,1,289,1,2011,0),(99,1,290,1,2011,0),(100,1,291,1,2011,0),(101,1,292,1,2011,0),(102,1,293,1,2011,0),(103,1,294,1,2011,0),(104,1,295,3,2011,0),(105,1,296,1,2011,0),(106,1,221,1,2011,0),(107,1,276,1,2011,0),(108,1,299,1,2011,0),(109,1,300,1,2011,0),(110,1,242,1,2011,0),(111,1,281,1,2011,0),(112,1,303,1,2011,0),(113,1,284,1,2011,0);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logbook_notification_history` (
  `lnhistory_id` int(12) NOT NULL AUTO_INCREMENT,
  `clerk_id` int(12) NOT NULL,
  `proxy_id` int(12) NOT NULL,
  `rotation_id` int(12) NOT NULL,
  `notified_date` int(12) NOT NULL,
  PRIMARY KEY (`lnhistory_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logbook_overdue` (
  `lologging_id` int(12) NOT NULL AUTO_INCREMENT,
  `proxy_id` int(12) NOT NULL,
  `rotation_id` int(12) NOT NULL,
  `event_id` int(12) NOT NULL,
  `logged_required` int(12) NOT NULL,
  `logged_completed` int(12) NOT NULL DEFAULT '0',
  `procedures_required` int(12) NOT NULL DEFAULT '0',
  `procedures_completed` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`lologging_id`),
  UNIQUE KEY `lologging_id` (`lologging_id`,`proxy_id`,`event_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logbook_preferred_procedure_locations` (
  `lpplocation_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `lpprocedure_id` int(11) DEFAULT NULL,
  `lltype_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`lpplocation_id`)
) ENGINE=MyISAM AUTO_INCREMENT=19 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `logbook_preferred_procedure_locations` VALUES (1,3,2),(2,2,2),(3,7,1),(4,5,2),(5,10,1),(6,8,1),(7,6,2),(8,4,2),(9,17,2),(10,15,2),(11,18,2),(12,16,2),(13,14,1),(14,19,2),(15,26,2),(16,24,2),(17,27,2),(18,29,2);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logbook_preferred_procedures` (
  `lpprocedure_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `rotation_id` int(12) unsigned NOT NULL,
  `order` smallint(6) NOT NULL,
  `lprocedure_id` int(12) unsigned NOT NULL,
  `number_required` int(2) NOT NULL DEFAULT '1',
  `grad_year_min` int(4) NOT NULL DEFAULT '2011',
  `grad_year_max` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`lpprocedure_id`),
  KEY `rotation_id` (`rotation_id`),
  KEY `order` (`order`),
  KEY `lprocedure_id` (`lprocedure_id`)
) ENGINE=MyISAM AUTO_INCREMENT=30 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `logbook_preferred_procedures` VALUES (1,2,0,1,1,2011,0),(2,2,0,2,1,2011,0),(3,2,0,3,1,2011,0),(4,3,0,4,1,2011,0),(5,3,0,5,1,2011,0),(6,3,0,6,1,2011,0),(7,3,0,7,1,2011,0),(8,3,0,8,1,2011,0),(9,3,0,9,1,2011,0),(10,3,0,10,1,2011,0),(11,4,0,11,1,2011,0),(12,4,0,12,1,2011,0),(13,4,0,13,1,2011,0),(14,5,0,14,1,2011,0),(15,5,0,15,1,2011,0),(16,5,0,16,3,2011,0),(17,5,0,17,3,2011,0),(18,5,0,18,1,2011,0),(19,5,0,19,1,2011,0),(20,5,0,20,1,2011,0),(21,5,0,21,1,2011,0),(22,5,0,22,1,2011,0),(23,5,0,23,2,2011,0),(24,7,0,24,1,2011,0),(25,7,0,25,4,2011,0),(26,7,0,22,1,2011,0),(27,8,0,24,1,2011,0),(28,8,0,25,4,2011,0),(29,8,0,22,1,2011,0);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logbook_rotation_comments` (
  `lrcomment_id` int(12) NOT NULL AUTO_INCREMENT,
  `clerk_id` int(12) NOT NULL,
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `rotation_id` int(12) NOT NULL DEFAULT '0',
  `comments` text NOT NULL,
  `updated_date` int(12) NOT NULL,
  `comment_active` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`lrcomment_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logbook_rotation_notifications` (
  `lrnotification_id` int(12) NOT NULL AUTO_INCREMENT,
  `proxy_id` int(12) NOT NULL,
  `rotation_id` int(12) NOT NULL,
  `notified` int(1) NOT NULL DEFAULT '0',
  `updated_date` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`lrnotification_id`),
  UNIQUE KEY `proxy_id` (`proxy_id`,`rotation_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logbook_rotation_sites` (
  `lrsite_id` smallint(6) NOT NULL AUTO_INCREMENT,
  `site_description` varchar(255) DEFAULT NULL,
  `rotation_id` smallint(6) DEFAULT NULL,
  PRIMARY KEY (`lrsite_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logbook_virtual_patient_objectives` (
  `lvpobjective_id` int(12) unsigned NOT NULL DEFAULT '0',
  `objective_id` int(12) unsigned DEFAULT NULL,
  `lvpatient_id` int(12) unsigned DEFAULT NULL,
  PRIMARY KEY (`lvpobjective_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logbook_virtual_patients` (
  `lvpatient_id` int(12) unsigned NOT NULL DEFAULT '0',
  `title` varchar(30) DEFAULT NULL,
  `url` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`lvpatient_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lottery_clerk_streams` (
  `lcstream_id` int(12) NOT NULL AUTO_INCREMENT,
  `lottery_clerk_id` int(12) NOT NULL DEFAULT '0',
  `category_id` int(12) NOT NULL DEFAULT '0',
  `rationale` text,
  `stream_order` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`lcstream_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lottery_clerks` (
  `lottery_clerk_id` int(12) NOT NULL AUTO_INCREMENT,
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `discipline_1` int(12) NOT NULL DEFAULT '0',
  `discipline_2` int(12) NOT NULL DEFAULT '0',
  `discipline_3` int(12) NOT NULL DEFAULT '0',
  `chosen_stream` int(12) NOT NULL DEFAULT '0',
  `chosen_rationale` text,
  `chosen_order` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`lottery_clerk_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notification_log` (
  `nlog_id` int(12) NOT NULL AUTO_INCREMENT,
  `notification_id` int(12) NOT NULL DEFAULT '0',
  `user_id` int(12) NOT NULL DEFAULT '0',
  `nlog_timestamp` bigint(64) NOT NULL DEFAULT '0',
  `nlog_address` varchar(128) NOT NULL DEFAULT '',
  `nlog_message` text NOT NULL,
  `prune_after` bigint(64) NOT NULL DEFAULT '0',
  PRIMARY KEY (`nlog_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notification_messages` (
  `nmessage_id` int(12) NOT NULL AUTO_INCREMENT,
  `form_type` varchar(12) NOT NULL DEFAULT 'rotation',
  `nmessage_title` varchar(128) NOT NULL DEFAULT '',
  `nmessage_version` int(4) NOT NULL DEFAULT '0',
  `nmessage_from_email` varchar(128) NOT NULL DEFAULT 'eval@meds.queensu.ca',
  `nmessage_from_name` varchar(64) NOT NULL DEFAULT 'Evaluation System',
  `nmessage_reply_email` varchar(128) NOT NULL DEFAULT 'eval@meds.queensu.ca',
  `nmessage_reply_name` varchar(64) NOT NULL DEFAULT 'Evaluation System',
  `nmessage_subject` varchar(255) NOT NULL DEFAULT '',
  `nmessage_body` text NOT NULL,
  `modified_last` bigint(64) NOT NULL DEFAULT '0',
  `modified_by` int(12) NOT NULL DEFAULT '0',
  `nmessage_status` varchar(12) NOT NULL DEFAULT 'published',
  PRIMARY KEY (`nmessage_id`),
  KEY `form_type` (`form_type`),
  KEY `nmessage_version` (`nmessage_version`),
  KEY `nmessage_status` (`nmessage_status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notification_monitor` (
  `nmonitor_id` int(12) NOT NULL AUTO_INCREMENT,
  `item_id` int(12) NOT NULL DEFAULT '0',
  `form_id` int(12) NOT NULL DEFAULT '0',
  `category_id` int(12) NOT NULL DEFAULT '0',
  `category_recurse` int(2) NOT NULL DEFAULT '1',
  `item_title` varchar(128) NOT NULL DEFAULT '',
  `item_maxinstances` int(12) NOT NULL DEFAULT '1',
  `item_start` int(12) NOT NULL DEFAULT '1',
  `item_end` int(12) NOT NULL DEFAULT '30',
  `item_status` varchar(12) NOT NULL DEFAULT 'published',
  `modified_last` bigint(64) NOT NULL DEFAULT '0',
  `modified_by` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`nmonitor_id`),
  KEY `item_id` (`item_id`),
  KEY `form_id` (`form_id`),
  KEY `category_id` (`category_id`),
  KEY `category_recurse` (`category_recurse`),
  KEY `item_start` (`item_start`),
  KEY `item_end` (`item_end`),
  KEY `item_status` (`item_status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `notification_id` int(12) NOT NULL AUTO_INCREMENT,
  `user_id` int(12) NOT NULL DEFAULT '0',
  `event_id` int(12) NOT NULL DEFAULT '0',
  `category_id` int(12) NOT NULL DEFAULT '0',
  `item_id` int(12) NOT NULL DEFAULT '0',
  `item_maxinstances` int(4) NOT NULL DEFAULT '1',
  `notification_status` varchar(16) NOT NULL DEFAULT 'initiated',
  `notified_last` bigint(64) NOT NULL DEFAULT '0',
  PRIMARY KEY (`notification_id`),
  KEY `user_id` (`user_id`),
  KEY `event_id` (`event_id`),
  KEY `category_id` (`category_id`),
  KEY `item_id` (`item_id`),
  KEY `item_maxinstances` (`item_maxinstances`),
  KEY `notification_status` (`notification_status`),
  KEY `notified_last` (`notified_last`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `other_teachers` (
  `oteacher_id` int(12) NOT NULL AUTO_INCREMENT,
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `firstname` varchar(50) NOT NULL DEFAULT '',
  `lastname` varchar(50) NOT NULL DEFAULT '',
  `email` varchar(150) NOT NULL DEFAULT '',
  PRIMARY KEY (`oteacher_id`),
  KEY `proxy_id` (`proxy_id`),
  KEY `firstname` (`firstname`),
  KEY `lastname` (`lastname`),
  KEY `email` (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `regions` (
  `region_id` int(12) NOT NULL AUTO_INCREMENT,
  `region_name` varchar(64) NOT NULL DEFAULT '',
  `province_id` int(12) NOT NULL DEFAULT '0',
  `countries_id` int(12) NOT NULL DEFAULT '0',
  `prov_state` varchar(200) DEFAULT NULL,
  `manage_apartments` int(1) NOT NULL DEFAULT '0',
  `is_core` int(1) NOT NULL DEFAULT '0',
  `region_active` int(1) NOT NULL DEFAULT '1',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`region_id`),
  KEY `region_name` (`region_name`),
  KEY `manage_apartments` (`manage_apartments`),
  KEY `province_id` (`province_id`),
  KEY `countries_id` (`countries_id`),
  KEY `is_core` (`is_core`)
) ENGINE=MyISAM AUTO_INCREMENT=81 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `regions` VALUES (1,'Alexandria',9,39,'Ontario',0,1,1,0,0),(2,'Almonte',9,39,'Ontario',0,1,1,0,0),(3,'Amherstview',9,39,'Ontario',0,1,1,0,0),(4,'Arnprior',9,39,'Ontario',0,1,1,0,0),(5,'Bancroft',9,39,'Ontario',0,1,1,0,0),(6,'Banff',1,39,'Alberta',0,1,1,0,0),(7,'Barrie',9,39,'Ontario',0,1,1,0,0),(8,'Barry\'s Bay',9,39,'Ontario',0,1,1,0,0),(9,'Belleville',9,39,'Ontario',1,1,1,0,0),(10,'Bowmanville',9,39,'Ontario',1,1,1,0,0),(11,'Brantford',9,39,'Ontario',0,1,1,0,0),(12,'Brighton',9,39,'Ontario',0,1,1,0,0),(13,'Brockville',9,39,'Ontario',1,1,1,0,0),(14,'Calabogie',9,39,'Ontario',0,1,1,0,0),(15,'Callander',9,39,'Ontario',0,1,1,0,0),(16,'Cambridge',9,39,'Ontario',0,1,1,0,0),(17,'Campbellford',9,39,'Ontario',0,1,1,0,0),(18,'Carleton',9,39,'Ontario',0,1,1,0,0),(19,'Carleton Place',9,39,'Ontario',0,1,1,0,0),(20,'Carp',9,39,'Ontario',0,1,1,0,0),(21,'Cobourg',9,39,'Ontario',1,1,1,0,0),(22,'Collingwood',9,39,'Ontario',0,1,1,0,0),(23,'Cornwall',9,39,'Ontario',1,1,1,0,0),(24,'Deep River',9,39,'Ontario',0,1,1,0,0),(25,'Fort Erie',9,39,'Ontario',0,1,1,0,0),(26,'Georgetown',9,39,'Ontario',0,1,1,0,0),(27,'Guelph',9,39,'Ontario',0,1,1,0,0),(28,'Haliburton',9,39,'Ontario',0,1,1,0,0),(29,'Ingersoll',9,39,'Ontario',0,1,1,0,0),(30,'Inuvik',0,39,'Northwest Territories',0,1,1,0,0),(31,'Iqaluit',8,39,'Nunavut',0,1,1,0,0),(32,'Kemptville',9,39,'Ontario',0,1,1,0,0),(33,'Kincardine',9,39,'Ontario',0,1,1,0,0),(34,'Kingston',9,39,'Ontario',0,1,1,0,0),(35,'Lanark',9,39,'Ontario',0,1,1,0,0),(36,'Lansdowne',9,39,'Ontario',0,1,1,0,0),(37,'Marathon',9,39,'Ontario',0,1,1,0,0),(38,'Markham',9,39,'Ontario',1,1,1,0,0),(39,'Merrickville',9,39,'Ontario',0,1,1,0,0),(40,'Midland',9,39,'Ontario',0,1,1,0,0),(41,'Milton',9,39,'Ontario',0,1,1,0,0),(42,'Moose Factory',9,39,'Ontario',0,1,1,0,0),(43,'Morrisburg',9,39,'Ontario',0,1,1,0,0),(44,'Napanee',9,39,'Ontario',0,1,1,0,0),(45,'Newmarket',9,39,'Ontario',0,1,1,0,0),(46,'Niagara Falls',9,39,'Ontario',0,1,1,0,0),(47,'Orangeville',9,39,'Ontario',0,1,1,0,0),(48,'Orillia',9,39,'Ontario',0,1,1,0,0),(49,'Oshawa',9,39,'Ontario',1,1,1,0,0),(50,'Pembroke',9,39,'Ontario',0,1,1,0,0),(51,'Perth',9,39,'Ontario',0,1,1,0,0),(52,'Peterborough',9,39,'Ontario',1,1,1,0,0),(53,'Picton',9,39,'Ontario',1,1,1,0,0),(54,'Port Colborne',9,39,'Ontario',0,1,1,0,0),(55,'Port Perry',9,39,'Ontario',1,1,1,0,0),(56,'Red Lake',9,39,'Ontario',0,1,1,0,0),(57,'Renfrew',9,39,'Ontario',0,1,1,0,0),(58,'Russell',9,39,'Ontario',0,1,1,0,0),(59,'Sackville',4,39,'New Brunswick',0,1,1,0,0),(60,'Sharbot Lake',9,39,'Ontario',1,1,1,0,0),(61,'Sioux Lookout',9,39,'Ontario',0,1,1,0,0),(62,'Smiths Falls',9,39,'Ontario',1,1,1,0,0),(63,'Sturgeon Falls',9,39,'Ontario',0,1,1,0,0),(64,'Sudbury',9,39,'Ontario',0,1,1,0,0),(65,'Sydenham',9,39,'Ontario',0,1,1,0,0),(66,'Tamworth',9,39,'Ontario',0,1,1,0,0),(67,'Temagami',9,39,'Ontario',0,1,1,0,0),(68,'Thornhill',9,39,'Ontario',0,1,1,0,0),(69,'Tofino',2,39,'British Columbia',0,1,1,0,0),(70,'Toronto',9,39,'Ontario',1,1,1,0,0),(71,'Trenton',9,39,'Ontario',0,1,1,0,0),(72,'Uxbridge',9,39,'Ontario',0,1,1,0,0),(73,'Valemount',2,39,'British Columbia',0,1,1,0,0),(74,'Vaughan',9,39,'Ontario',0,1,1,0,0),(75,'Verona',9,39,'Ontario',0,1,1,0,0),(76,'Waterloo',9,39,'Ontario',0,1,1,0,0),(77,'Welland',9,39,'Ontario',0,1,1,0,0),(78,'Wellington',9,39,'Ontario',0,1,1,0,0),(79,'Whistler',2,39,'British Columbia',0,1,1,0,0),(80,'Yarker',9,39,'Ontario',0,1,1,0,0);

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

