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
CREATE TABLE `ar_book_chapter_mono` (
  `book_chapter_mono_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` text NOT NULL,
  `source` varchar(200) NOT NULL,
  `author_list` varchar(200) NOT NULL,
  `editor_list` varchar(200) DEFAULT NULL,
  `category` varchar(10) DEFAULT NULL,
  `epub_url` text,
  `status_date` varchar(8) DEFAULT NULL,
  `epub_date` varchar(8) NOT NULL,
  `volume` varchar(25) DEFAULT NULL,
  `edition` varchar(25) DEFAULT NULL,
  `pages` varchar(25) DEFAULT NULL,
  `role_id` int(3) NOT NULL,
  `type_id` int(3) NOT NULL,
  `status` varchar(25) NOT NULL,
  `group_id` int(3) DEFAULT NULL,
  `hospital_id` int(3) DEFAULT NULL,
  `pubmed_id` varchar(200) NOT NULL,
  `year_reported` int(4) NOT NULL,
  `proxy_id` int(11) DEFAULT NULL,
  `visible_on_website` int(1) DEFAULT '0',
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`book_chapter_mono_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_clinical_activity` (
  `clinical_activity_id` int(11) NOT NULL AUTO_INCREMENT,
  `site` varchar(150) NOT NULL DEFAULT '',
  `site_description` text,
  `description` text NOT NULL,
  `average_hours` int(11) DEFAULT NULL,
  `year_reported` int(4) NOT NULL DEFAULT '0',
  `proxy_id` int(11) DEFAULT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`clinical_activity_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_clinical_education` (
  `clinical_education_id` int(11) NOT NULL AUTO_INCREMENT,
  `level` varchar(150) NOT NULL DEFAULT '',
  `level_description` varchar(255) DEFAULT NULL,
  `location` varchar(150) NOT NULL DEFAULT '',
  `location_description` varchar(255) DEFAULT NULL,
  `average_hours` int(11) NOT NULL DEFAULT '0',
  `research_percentage` int(1) DEFAULT '0',
  `description` text NOT NULL,
  `year_reported` int(4) NOT NULL DEFAULT '0',
  `proxy_id` int(11) DEFAULT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`clinical_education_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_clinical_innovation` (
  `clinical_innovation_id` int(11) NOT NULL AUTO_INCREMENT,
  `description` text NOT NULL,
  `year_reported` int(4) NOT NULL DEFAULT '0',
  `proxy_id` int(11) DEFAULT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`clinical_innovation_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_clinics` (
  `clinics_id` int(11) NOT NULL AUTO_INCREMENT,
  `clinic` varchar(150) NOT NULL DEFAULT '',
  `patients` int(11) NOT NULL DEFAULT '0',
  `half_days` int(11) NOT NULL DEFAULT '0',
  `new_repeat` varchar(25) NOT NULL DEFAULT '',
  `weeks` int(2) NOT NULL DEFAULT '0',
  `average_clerks` int(11) NOT NULL DEFAULT '0',
  `year_reported` int(4) NOT NULL DEFAULT '0',
  `proxy_id` int(11) DEFAULT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`clinics_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_conference_papers` (
  `conference_papers_id` int(11) NOT NULL AUTO_INCREMENT,
  `lectures_papers_list` text NOT NULL,
  `status` varchar(25) NOT NULL DEFAULT '',
  `institution` text NOT NULL,
  `location` varchar(250) DEFAULT NULL,
  `countries_id` int(12) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `prov_state` varchar(200) DEFAULT NULL,
  `type` varchar(30) NOT NULL DEFAULT '',
  `year_reported` int(4) NOT NULL DEFAULT '0',
  `proxy_id` int(11) DEFAULT NULL,
  `visible_on_website` int(1) DEFAULT '0',
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`conference_papers_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_consults` (
  `consults_id` int(11) NOT NULL AUTO_INCREMENT,
  `activity` varchar(250) NOT NULL DEFAULT '',
  `site` varchar(150) NOT NULL DEFAULT '',
  `site_description` text,
  `months` int(2) NOT NULL DEFAULT '0',
  `average_consults` int(11) NOT NULL DEFAULT '0',
  `year_reported` int(4) NOT NULL DEFAULT '0',
  `proxy_id` int(11) DEFAULT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`consults_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_continuing_education` (
  `continuing_education_id` int(11) NOT NULL AUTO_INCREMENT,
  `unit` varchar(150) NOT NULL DEFAULT '',
  `location` varchar(150) NOT NULL DEFAULT '',
  `average_hours` int(11) NOT NULL DEFAULT '0',
  `description` text NOT NULL,
  `start_month` int(2) NOT NULL DEFAULT '0',
  `start_year` int(4) NOT NULL DEFAULT '0',
  `end_month` int(2) NOT NULL DEFAULT '0',
  `end_year` int(4) NOT NULL DEFAULT '0',
  `total_hours` int(11) NOT NULL DEFAULT '0',
  `year_reported` int(4) NOT NULL DEFAULT '0',
  `proxy_id` int(11) DEFAULT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`continuing_education_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_external_contributions` (
  `external_contributions_id` int(11) NOT NULL AUTO_INCREMENT,
  `organisation` varchar(255) NOT NULL DEFAULT '',
  `city_country` text,
  `countries_id` int(12) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `prov_state` varchar(200) DEFAULT NULL,
  `role` varchar(150) DEFAULT NULL,
  `role_description` text,
  `description` text NOT NULL,
  `days_of_year` int(3) NOT NULL DEFAULT '0',
  `year_reported` int(4) NOT NULL DEFAULT '0',
  `proxy_id` int(11) DEFAULT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`external_contributions_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_graduate_supervision` (
  `graduate_supervision_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_name` varchar(150) NOT NULL DEFAULT '',
  `degree` varchar(25) NOT NULL DEFAULT '',
  `active` varchar(8) NOT NULL DEFAULT '',
  `supervision` varchar(7) NOT NULL DEFAULT '',
  `year_started` int(4) NOT NULL DEFAULT '0',
  `thesis_defended` char(3) NOT NULL DEFAULT '',
  `comments` text,
  `year_reported` int(4) NOT NULL DEFAULT '0',
  `proxy_id` int(11) DEFAULT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`graduate_supervision_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_graduate_teaching` (
  `graduate_teaching_id` int(11) NOT NULL AUTO_INCREMENT,
  `course_number` varchar(25) NOT NULL DEFAULT '',
  `course_name` text NOT NULL,
  `assigned` char(3) NOT NULL DEFAULT '',
  `lec_enrollment` int(11) NOT NULL DEFAULT '0',
  `lec_hours` int(11) NOT NULL DEFAULT '0',
  `lab_enrollment` int(11) NOT NULL DEFAULT '0',
  `lab_hours` int(11) NOT NULL DEFAULT '0',
  `tut_enrollment` int(11) NOT NULL DEFAULT '0',
  `tut_hours` int(11) NOT NULL DEFAULT '0',
  `sem_enrollment` int(11) NOT NULL DEFAULT '0',
  `sem_hours` int(11) NOT NULL DEFAULT '0',
  `coord_enrollment` int(11) NOT NULL DEFAULT '0',
  `pbl_hours` int(11) NOT NULL DEFAULT '0',
  `comments` text,
  `year_reported` int(4) NOT NULL DEFAULT '0',
  `proxy_id` int(11) DEFAULT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`graduate_teaching_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_innovation` (
  `innovation_id` int(11) NOT NULL AUTO_INCREMENT,
  `course_number` varchar(25) DEFAULT NULL,
  `course_name` text,
  `type` varchar(150) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `year_reported` int(4) NOT NULL DEFAULT '0',
  `proxy_id` int(11) DEFAULT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`innovation_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_internal_contributions` (
  `internal_contributions_id` int(11) NOT NULL AUTO_INCREMENT,
  `activity_type` varchar(150) NOT NULL DEFAULT '',
  `activity_type_description` text,
  `role` varchar(150) NOT NULL DEFAULT '',
  `role_description` text,
  `description` text NOT NULL,
  `time_commitment` int(11) NOT NULL DEFAULT '0',
  `commitment_type` varchar(10) NOT NULL DEFAULT 'week',
  `start_month` int(2) NOT NULL DEFAULT '0',
  `start_year` int(4) DEFAULT NULL,
  `end_month` int(2) DEFAULT '0',
  `end_year` int(4) DEFAULT '0',
  `meetings_attended` int(3) DEFAULT NULL,
  `type` varchar(50) NOT NULL DEFAULT '',
  `year_reported` int(4) NOT NULL DEFAULT '0',
  `proxy_id` int(11) DEFAULT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`internal_contributions_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_lu_activity_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `activity_type` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `ar_lu_activity_types` VALUES (1,'Lecture'),(2,'Seminar'),(3,'Workshop'),(4,'Other');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_lu_clinical_locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `clinical_location` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `ar_lu_clinical_locations` VALUES (1,'Hospital A'),(2,'Hospital B'),(3,'Hospital C'),(4,'Other (specify)');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_lu_conference_paper_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conference_paper_type` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `ar_lu_conference_paper_types` VALUES (1,'Invited Lecture'),(2,'Invited Conference Paper');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_lu_consult_locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `consult_location` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `ar_lu_consult_locations` VALUES (1,'Hospital A'),(2,'Hospital B'),(3,'Hospital C'),(4,'Other (specify)');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_lu_contribution_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contribution_role` varchar(50) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `ar_lu_contribution_roles` VALUES (1,'Advisor',1),(2,'Chair',1),(3,'Co-Chair',1),(4,'Consultant',1),(5,'Delegate',1),(6,'Deputy Head',1),(7,'Director',1),(8,'Head',1),(9,'Member',1),(10,'Past President',1),(11,'President',1),(12,'Representative',1),(13,'Secretary',1),(14,'Vice Chair',1),(15,'Vice President',1),(16,'Other (specify)',1),(17,'Site Leader on a Clinical Trial',1);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_lu_contribution_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contribution_type` varchar(50) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `ar_lu_contribution_types` VALUES (1,'Accreditation Committee',1),(2,'Committee (specify)',1),(3,'Council (specify)',1),(4,'Faculty Board',1),(5,'Search Committee (specify)',1),(6,'Senate',1),(7,'Senate Committee (specify)',1),(8,'Subcommittee (specify)',1),(9,'Other (specify)',1);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_lu_degree_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `degree_type` varchar(50) NOT NULL DEFAULT '',
  `visible` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `ar_lu_degree_types` VALUES (1,'BA',1),(2,'BSc',1),(3,'BNSc',1),(4,'MA',1),(5,'MD',1),(6,'M ED',1),(7,'MES',1),(8,'MSc',1),(9,'MScOT',1),(10,'MSc OT (Project)',1),(11,'MScPT',1),(12,'MSC PT (Project)',1),(13,'PDF',1),(14,'PhD',1),(15,'Clinical Fellow',1),(16,'Summer Research Student',1),(17,'MPA Candidate',1);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_lu_education_locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `education_location` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `ar_lu_education_locations` VALUES (1,'Hospital A'),(2,'Hospital B'),(3,'Hospital C'),(4,'Other (specify)');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_lu_focus_groups` (
  `group_id` int(11) NOT NULL AUTO_INCREMENT,
  `focus_group` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`group_id`),
  KEY `focus_group` (`focus_group`)
) ENGINE=MyISAM AUTO_INCREMENT=25 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `ar_lu_focus_groups` VALUES (1,'Cancer'),(2,'Neurosciences'),(3,'Cardiovascular, Circulatory and Respiratory'),(4,'Gastrointestinal'),(5,'Musculoskeletal\n'),(6,'Health Services Research'),(15,'Other'),(7,'Protein Function and Discovery'),(8,'Reproductive Sciences'),(9,'Genetics'),(10,'Nursing'),(11,'Primary Care Studies'),(12,'Emergency'),(13,'Critical Care'),(14,'Nephrology'),(16,'Educational Research'),(17,'Microbiology and Immunology'),(18,'Urology'),(19,'Psychiatry'),(20,'Anesthesiology'),(22,'Obstetrics and Gynecology'),(23,'Rehabilitation Therapy'),(24,'Occupational Therapy');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_lu_hospital_location` (
  `hosp_id` int(11) NOT NULL DEFAULT '0',
  `hosp_desc` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`hosp_id`),
  KEY `hosp_desc` (`hosp_desc`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `ar_lu_hospital_location` VALUES (1,'Hospital A'),(2,'Hospital B'),(3,'Hospital C');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_lu_innovation_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `innovation_type` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `ar_lu_innovation_types` VALUES (1,'Course Design'),(2,'Curriculum Development'),(3,'Educational Materials Development'),(4,'Software Development'),(5,'Educational Planning and Policy Development'),(6,'Development of Innovative Teaching Methods');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_lu_membership_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `membership_role` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `ar_lu_membership_roles` VALUES (1,'Examining Committee'),(2,'Comprehensive Exam Committee'),(3,'Mini Masters'),(4,'Supervisory Committee'),(5,'Other (specify)');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_lu_on_call_locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `on_call_location` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `ar_lu_on_call_locations` VALUES (1,'Hospital A'),(2,'Hospital B'),(3,'Hospital C'),(4,'Other (specify)');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_lu_other_locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `other_location` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `ar_lu_other_locations` VALUES (1,'Hospital A'),(2,'Hospital B'),(3,'Hospital C'),(4,'Other (specify)');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_lu_patent_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patent_type` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `ar_lu_patent_types` VALUES (1,'License Granted'),(2,'Non-Disclosure Agreement'),(3,'Patent Applied For'),(4,'Patent Obtained');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_lu_pr_roles` (
  `role_id` int(11) NOT NULL DEFAULT '0',
  `role_description` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`role_id`),
  KEY `role_description` (`role_description`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `ar_lu_pr_roles` VALUES (1,'First Author'),(2,'Corresponding Author'),(3,'Contributing Author');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_lu_prize_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `prize_category` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `ar_lu_prize_categories` VALUES (1,'Research'),(2,'Teaching'),(3,'Service');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_lu_prize_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `prize_type` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `ar_lu_prize_types` VALUES (1,'Award'),(2,'Honour'),(3,'Prize');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_lu_profile_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `profile_role` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `ar_lu_profile_roles` VALUES (1,'Researcher/Scholar'),(2,'Educator/Scholar'),(3,'Clinician/Scholar');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_lu_publication_statuses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `publication_status` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `ar_lu_publication_statuses` VALUES (1,'Accepted'),(2,'In Press'),(3,'Presented'),(4,'Published'),(5,'Submitted');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_lu_publication_type` (
  `type_id` int(11) NOT NULL DEFAULT '0',
  `type_description` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`type_id`),
  KEY `type_description` (`type_description`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `ar_lu_publication_type` VALUES (1,'Peer-Reviewed Article'),(2,'Non-Peer-Reviewed Article'),(3,'Chapter'),(4,'Peer-Reviewed Abstract'),(5,'Non-Peer-Reviewed Abstract'),(6,'Complete Book'),(7,'Monograph'),(8,'Editorial'),(9,'Published Conference Proceeding'),(10,'Poster Presentations'),(11,'Technical Report');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_lu_research_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `research_type` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `ar_lu_research_types` VALUES (1,'Infrastructure'),(2,'Operating'),(3,'Salary'),(4,'Training');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_lu_scholarly_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `scholarly_type` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `ar_lu_scholarly_types` VALUES (1,'Granting Body Referee'),(2,'Journal Editorship'),(3,'Journal Referee'),(4,'Other (specify)');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_lu_self_education_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `self_education_type` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `ar_lu_self_education_types` VALUES (1,'Clinical'),(2,'Research'),(3,'Teaching'),(4,'Service/Administrative'),(5,'Other');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_lu_supervision_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `supervision_type` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `ar_lu_supervision_types` VALUES (1,'Joint'),(2,'Sole');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_lu_trainee_levels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `trainee_level` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `ar_lu_trainee_levels` VALUES (1,'Clerk(s)'),(2,'Clinical Fellow(s)'),(3,'International Med. Graduate'),(4,'PGY (specify)'),(5,'Other (specify)');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_lu_undergraduate_supervision_courses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `undergarduate_supervision_course` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=25 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `ar_lu_undergraduate_supervision_courses` VALUES (1,'ANAT-499'),(2,'BCHM-421'),(3,'BCHM-422'),(4,'MICR-499'),(5,'PATH-499'),(6,'PHAR-499'),(7,'PHGY-499'),(8,'NURS-490'),(9,'ANAT499'),(10,'BCHM421'),(11,'BCHM422'),(12,'MICR499'),(13,'PATH499'),(14,'PHAR499'),(15,'PHGY499'),(16,'NURS490'),(17,'ANAT 499'),(18,'BCHM 421'),(19,'BCHM 422'),(20,'MICR 499'),(21,'PATH 499'),(22,'PHAR 499'),(23,'PHGY 499'),(24,'NURS 490');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_memberships` (
  `memberships_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_name` varchar(150) NOT NULL DEFAULT '',
  `degree` varchar(25) NOT NULL DEFAULT '',
  `department` varchar(150) NOT NULL DEFAULT '',
  `university` varchar(255) NOT NULL DEFAULT '',
  `role` varchar(100) NOT NULL DEFAULT '',
  `role_description` text,
  `year_reported` int(4) NOT NULL DEFAULT '0',
  `proxy_id` int(11) DEFAULT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`memberships_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_non_peer_reviewed_papers` (
  `non_peer_reviewed_papers_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` text NOT NULL,
  `source` varchar(200) NOT NULL,
  `author_list` varchar(200) NOT NULL,
  `category` varchar(10) DEFAULT NULL,
  `epub_url` text,
  `status_date` varchar(8) DEFAULT NULL,
  `epub_date` varchar(8) NOT NULL,
  `volume` varchar(25) DEFAULT NULL,
  `edition` varchar(25) DEFAULT NULL,
  `pages` varchar(25) DEFAULT NULL,
  `role_id` int(3) NOT NULL,
  `type_id` int(3) NOT NULL,
  `status` varchar(25) NOT NULL,
  `group_id` int(3) DEFAULT NULL,
  `hospital_id` int(3) DEFAULT NULL,
  `pubmed_id` varchar(200) NOT NULL,
  `year_reported` int(4) NOT NULL,
  `proxy_id` int(11) DEFAULT NULL,
  `visible_on_website` int(1) DEFAULT '0',
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`non_peer_reviewed_papers_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_on_call` (
  `on_call_id` int(11) NOT NULL AUTO_INCREMENT,
  `site` varchar(150) NOT NULL DEFAULT '',
  `site_description` text,
  `frequency` varchar(250) DEFAULT NULL,
  `special_features` text NOT NULL,
  `year_reported` int(4) NOT NULL DEFAULT '0',
  `proxy_id` int(11) DEFAULT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`on_call_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_other` (
  `other_id` int(11) NOT NULL AUTO_INCREMENT,
  `course_name` text NOT NULL,
  `type` varchar(150) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `year_reported` int(4) NOT NULL DEFAULT '0',
  `proxy_id` int(11) DEFAULT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`other_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_other_activity` (
  `other_activity_id` int(11) NOT NULL AUTO_INCREMENT,
  `site` varchar(150) NOT NULL DEFAULT '',
  `site_description` text,
  `average_hours` int(11) DEFAULT NULL,
  `special_features` text NOT NULL,
  `year_reported` int(4) NOT NULL DEFAULT '0',
  `proxy_id` int(11) DEFAULT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`other_activity_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_patent_activity` (
  `patent_activity_id` int(11) NOT NULL AUTO_INCREMENT,
  `patent_activity_type` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `year_reported` int(4) NOT NULL DEFAULT '0',
  `proxy_id` int(11) DEFAULT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`patent_activity_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_peer_reviewed_papers` (
  `peer_reviewed_papers_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` text NOT NULL,
  `source` varchar(200) NOT NULL,
  `author_list` varchar(200) NOT NULL,
  `category` varchar(10) DEFAULT NULL,
  `epub_url` text,
  `status_date` varchar(8) DEFAULT NULL,
  `epub_date` varchar(8) NOT NULL,
  `volume` varchar(25) DEFAULT NULL,
  `edition` varchar(25) DEFAULT NULL,
  `pages` varchar(25) DEFAULT NULL,
  `role_id` int(3) NOT NULL,
  `type_id` int(3) NOT NULL,
  `status` varchar(25) NOT NULL,
  `group_id` int(3) DEFAULT NULL,
  `hospital_id` int(3) DEFAULT NULL,
  `pubmed_id` varchar(200) NOT NULL,
  `keywords` text,
  `year_reported` int(4) NOT NULL,
  `proxy_id` int(11) DEFAULT NULL,
  `visible_on_website` int(1) DEFAULT '0',
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`peer_reviewed_papers_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_poster_reports` (
  `poster_reports_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` text NOT NULL,
  `source` varchar(200) NOT NULL,
  `author_list` varchar(200) NOT NULL,
  `editor_list` varchar(200) DEFAULT NULL,
  `epub_url` text,
  `status_date` varchar(8) DEFAULT NULL,
  `epub_date` varchar(8) NOT NULL,
  `volume` varchar(25) DEFAULT NULL,
  `edition` varchar(25) DEFAULT NULL,
  `pages` varchar(25) DEFAULT NULL,
  `role_id` int(3) NOT NULL,
  `type_id` int(3) NOT NULL,
  `status` varchar(25) NOT NULL,
  `group_id` int(3) DEFAULT NULL,
  `hospital_id` int(3) DEFAULT NULL,
  `pubmed_id` varchar(200) NOT NULL,
  `year_reported` int(4) NOT NULL,
  `proxy_id` int(11) DEFAULT NULL,
  `visible_on_website` int(1) DEFAULT '0',
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`poster_reports_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_prizes` (
  `prizes_id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(150) NOT NULL DEFAULT '',
  `prize_type` varchar(150) DEFAULT NULL,
  `description` text,
  `year_reported` int(4) NOT NULL DEFAULT '0',
  `proxy_id` int(11) DEFAULT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`prizes_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_procedures` (
  `procedures_id` int(11) NOT NULL AUTO_INCREMENT,
  `site` varchar(150) NOT NULL DEFAULT '',
  `site_description` text,
  `average_hours` int(11) DEFAULT NULL,
  `special_features` text NOT NULL,
  `year_reported` int(4) NOT NULL DEFAULT '0',
  `proxy_id` int(11) DEFAULT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`procedures_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_profile` (
  `profile_id` int(11) NOT NULL AUTO_INCREMENT,
  `education` float(5,2) NOT NULL DEFAULT '0.00',
  `research` float(5,2) NOT NULL DEFAULT '0.00',
  `clinical` float(5,2) NOT NULL DEFAULT '0.00',
  `combined` float(5,2) NOT NULL DEFAULT '0.00',
  `service` float(5,2) NOT NULL DEFAULT '0.00',
  `total` float(5,2) NOT NULL DEFAULT '0.00',
  `hospital_hours` int(11) NOT NULL DEFAULT '0',
  `on_call_hours` int(11) NOT NULL DEFAULT '0',
  `consistent` char(3) NOT NULL DEFAULT '',
  `consistent_comments` text,
  `career_goals` char(3) NOT NULL DEFAULT '',
  `career_comments` text,
  `roles` text NOT NULL,
  `roles_compatible` char(3) NOT NULL DEFAULT '',
  `roles_comments` text,
  `education_comments` text,
  `research_comments` text,
  `clinical_comments` text,
  `service_comments` text,
  `comments` text,
  `year_reported` int(4) NOT NULL DEFAULT '0',
  `department` text NOT NULL,
  `cross_department` text,
  `report_completed` char(3) DEFAULT NULL,
  `proxy_id` int(11) DEFAULT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`profile_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_research` (
  `research_id` int(11) NOT NULL AUTO_INCREMENT,
  `status` varchar(10) DEFAULT NULL,
  `grant_title` text NOT NULL,
  `type` varchar(50) NOT NULL,
  `location` varchar(25) DEFAULT NULL,
  `multiinstitutional` varchar(3) DEFAULT NULL,
  `agency` text,
  `role` varchar(50) NOT NULL,
  `principal_investigator` varchar(100) NOT NULL DEFAULT '',
  `co_investigator_list` text,
  `amount_received` decimal(20,2) NOT NULL DEFAULT '0.00',
  `start_month` int(2) NOT NULL DEFAULT '0',
  `start_year` int(4) NOT NULL DEFAULT '0',
  `end_month` int(2) DEFAULT '0',
  `end_year` int(4) DEFAULT '0',
  `year_reported` int(4) NOT NULL DEFAULT '0',
  `funding_status` varchar(9) NOT NULL DEFAULT '',
  `proxy_id` int(11) DEFAULT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`research_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_scholarly_activity` (
  `scholarly_activity_id` int(11) NOT NULL AUTO_INCREMENT,
  `scholarly_activity_type` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `location` varchar(25) DEFAULT NULL,
  `year_reported` int(4) NOT NULL DEFAULT '0',
  `proxy_id` int(11) DEFAULT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`scholarly_activity_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_self_education` (
  `self_education_id` int(11) NOT NULL AUTO_INCREMENT,
  `description` text NOT NULL,
  `activity_type` varchar(150) NOT NULL DEFAULT '',
  `institution` varchar(255) NOT NULL DEFAULT '',
  `start_month` int(2) NOT NULL DEFAULT '0',
  `start_year` int(4) NOT NULL DEFAULT '0',
  `end_month` int(2) NOT NULL DEFAULT '0',
  `end_year` int(4) NOT NULL DEFAULT '0',
  `year_reported` int(4) NOT NULL DEFAULT '0',
  `proxy_id` int(11) DEFAULT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`self_education_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_undergraduate_nonmedical_teaching` (
  `undergraduate_nonmedical_teaching_id` int(11) NOT NULL AUTO_INCREMENT,
  `course_number` varchar(25) NOT NULL DEFAULT '',
  `course_name` text NOT NULL,
  `assigned` char(3) NOT NULL DEFAULT '',
  `lec_enrollment` int(11) NOT NULL DEFAULT '0',
  `lec_hours` int(11) NOT NULL DEFAULT '0',
  `lab_enrollment` int(11) NOT NULL DEFAULT '0',
  `lab_hours` int(11) NOT NULL DEFAULT '0',
  `tut_enrollment` int(11) NOT NULL DEFAULT '0',
  `tut_hours` int(11) NOT NULL DEFAULT '0',
  `sem_enrollment` int(11) NOT NULL DEFAULT '0',
  `sem_hours` int(11) NOT NULL DEFAULT '0',
  `coord_enrollment` int(11) NOT NULL DEFAULT '0',
  `pbl_hours` int(11) NOT NULL DEFAULT '0',
  `comments` text,
  `year_reported` int(4) NOT NULL DEFAULT '0',
  `proxy_id` int(11) DEFAULT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`undergraduate_nonmedical_teaching_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_undergraduate_supervision` (
  `undergraduate_supervision_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_name` varchar(150) NOT NULL DEFAULT '',
  `degree` varchar(25) NOT NULL DEFAULT '',
  `course_number` varchar(25) DEFAULT NULL,
  `supervision` varchar(7) NOT NULL DEFAULT '',
  `comments` text,
  `year_reported` int(4) NOT NULL DEFAULT '0',
  `proxy_id` int(11) DEFAULT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`undergraduate_supervision_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_undergraduate_teaching` (
  `undergraduate_teaching_id` int(11) NOT NULL AUTO_INCREMENT,
  `course_number` varchar(25) NOT NULL DEFAULT '',
  `course_name` text NOT NULL,
  `lecture_phase` varchar(6) DEFAULT NULL,
  `assigned` char(3) NOT NULL DEFAULT '',
  `lecture_hours` decimal(20,2) DEFAULT '0.00',
  `lab_hours` decimal(20,2) DEFAULT '0.00',
  `small_group_hours` decimal(20,2) DEFAULT '0.00',
  `patient_contact_session_hours` decimal(20,2) DEFAULT '0.00',
  `symposium_hours` decimal(20,2) DEFAULT '0.00',
  `directed_independant_learning_hours` decimal(20,2) DEFAULT '0.00',
  `review_feedback_session_hours` decimal(20,2) DEFAULT '0.00',
  `examination_hours` decimal(20,2) DEFAULT '0.00',
  `clerkship_seminar_hours` decimal(20,2) DEFAULT '0.00',
  `other_hours` decimal(20,2) DEFAULT '0.00',
  `coord_enrollment` int(11) DEFAULT '0',
  `comments` text,
  `year_reported` int(4) NOT NULL DEFAULT '0',
  `proxy_id` int(11) DEFAULT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` varchar(7) NOT NULL DEFAULT '',
  PRIMARY KEY (`undergraduate_teaching_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ar_ward_supervision` (
  `ward_supervision_id` int(11) NOT NULL AUTO_INCREMENT,
  `service` varchar(150) NOT NULL DEFAULT '',
  `average_patients` int(11) NOT NULL DEFAULT '0',
  `months` int(2) NOT NULL DEFAULT '0',
  `average_clerks` int(11) NOT NULL DEFAULT '0',
  `year_reported` int(4) NOT NULL DEFAULT '0',
  `proxy_id` int(11) DEFAULT NULL,
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`ward_supervision_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assessment_attached_quizzes` (
  `aaquiz_id` int(12) NOT NULL AUTO_INCREMENT,
  `assessment_id` int(12) NOT NULL DEFAULT '0',
  `aquiz_id` int(12) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`aaquiz_id`),
  KEY `assessment_id` (`assessment_id`),
  KEY `quiz_id` (`aquiz_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assessment_collections` (
  `collection_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int(12) unsigned DEFAULT NULL,
  `title` varchar(128) NOT NULL,
  `description` text NOT NULL,
  `active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`collection_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assessment_events` (
  `assessment_event_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `assessment_id` int(12) unsigned NOT NULL,
  `event_id` int(12) unsigned NOT NULL,
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) unsigned NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`assessment_event_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assessment_exceptions` (
  `aexception_id` int(12) NOT NULL AUTO_INCREMENT,
  `assessment_id` int(12) NOT NULL,
  `proxy_id` int(12) NOT NULL,
  `grade_weighting` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`aexception_id`),
  KEY `proxy_id` (`assessment_id`,`proxy_id`),
  KEY `assessment_id` (`assessment_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assessment_grade_form_comments` (
  `agfcomment_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `gafelement_id` int(11) unsigned DEFAULT NULL,
  `assessment_id` int(11) unsigned DEFAULT NULL,
  `proxy_id` int(12) unsigned DEFAULT NULL,
  `comment` text,
  PRIMARY KEY (`agfcomment_id`),
  KEY `gafelement_id` (`gafelement_id`),
  KEY `assessment_id` (`assessment_id`),
  CONSTRAINT `assessment_grade_form_comments_ibfk_1` FOREIGN KEY (`gafelement_id`) REFERENCES `gradebook_assessment_form_elements` (`gafelement_id`),
  CONSTRAINT `assessment_grade_form_comments_ibfk_2` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`assessment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assessment_grade_form_elements` (
  `agfelement_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `gairesponse_id` int(11) unsigned DEFAULT NULL,
  `assessment_id` int(11) unsigned DEFAULT NULL,
  `proxy_id` int(12) unsigned DEFAULT NULL,
  `score` float DEFAULT NULL,
  PRIMARY KEY (`agfelement_id`),
  KEY `gairesponse_id` (`gairesponse_id`),
  KEY `assessment_id` (`assessment_id`),
  CONSTRAINT `assessment_grade_form_elements_ibfk_2` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`assessment_id`),
  CONSTRAINT `assessment_grade_form_elements_ibfk_1` FOREIGN KEY (`gairesponse_id`) REFERENCES `gradebook_assessment_item_responses` (`gairesponse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assessment_graders` (
  `ag_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `assessment_id` int(12) unsigned NOT NULL,
  `proxy_id` int(12) unsigned NOT NULL,
  `grader_proxy_id` int(12) unsigned NOT NULL,
  PRIMARY KEY (`ag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assessment_grades` (
  `grade_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `assessment_id` int(10) unsigned NOT NULL,
  `proxy_id` int(10) unsigned NOT NULL,
  `value` float NOT NULL,
  `threshold_notified` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`grade_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assessment_grading_range` (
  `agrange_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `agscale_id` int(11) unsigned NOT NULL,
  `numeric_grade_min` int(11) DEFAULT NULL,
  `letter_grade` varchar(128) DEFAULT NULL,
  `gpa` decimal(5,2) DEFAULT NULL,
  `notes` varchar(128) DEFAULT NULL,
  `updated_date` bigint(64) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `deleted_date` bigint(64) DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`agrange_id`),
  KEY `lgs_id` (`agscale_id`),
  CONSTRAINT `assessment_grading_range_ibfk_1` FOREIGN KEY (`agscale_id`) REFERENCES `assessment_grading_scale` (`agscale_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `assessment_grading_range` VALUES (1,1,90,'A+',4.30,NULL,1483495903,1,NULL,NULL),(2,1,85,'A',4.00,NULL,1483495903,1,NULL,NULL),(3,1,80,'A-',3.70,NULL,1483495903,1,NULL,NULL),(4,1,77,'B+',3.30,NULL,1483495903,1,NULL,NULL),(5,1,73,'B',3.00,NULL,1483495903,1,NULL,NULL),(6,1,70,'B-',2.70,NULL,1483495903,1,NULL,NULL),(7,1,67,'C+',2.30,NULL,1483495903,1,NULL,NULL),(8,1,63,'C',2.00,NULL,1483495903,1,NULL,NULL),(9,1,60,'C-',1.70,NULL,1483495903,1,NULL,NULL),(10,1,57,'D+',1.30,NULL,1483495903,1,NULL,NULL),(11,1,53,'D',1.00,NULL,1483495903,1,NULL,NULL),(12,1,50,'D-',0.70,NULL,1483495903,1,NULL,NULL),(13,1,0,'F',0.00,NULL,1483495903,1,NULL,NULL);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assessment_grading_scale` (
  `agscale_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `organisation_id` int(11) DEFAULT NULL,
  `title` varchar(256) DEFAULT NULL,
  `applicable_from` bigint(64) DEFAULT NULL,
  `updated_date` bigint(64) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `deleted_date` bigint(64) DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`agscale_id`),
  KEY `applicable_from` (`applicable_from`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `assessment_grading_scale` VALUES (1,1,'Default Grading Scale for Your University',NULL,1483495903,1,NULL,NULL);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assessment_groups` (
  `agroup_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `cgroup_id` int(11) NOT NULL,
  `assessment_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`agroup_id`),
  KEY `cgroup_id` (`cgroup_id`),
  KEY `assessment_id` (`assessment_id`),
  CONSTRAINT `assessment_groups_ibfk_1` FOREIGN KEY (`cgroup_id`) REFERENCES `course_groups` (`cgroup_id`),
  CONSTRAINT `assessment_groups_ibfk_2` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`assessment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assessment_marking_schemes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `handler` varchar(255) NOT NULL DEFAULT 'Boolean',
  `description` text NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `assessment_marking_schemes` VALUES (1,'Pass/Fail','Boolean','Enter P for Pass, or F for Fail, in the student mark column.',1),(2,'Percentage','Percentage','Enter a percentage in the student mark column.',1),(3,'Numeric','Numeric','Enter a numeric total in the student mark column.',1),(4,'Complete/Incomplete','IncompleteComplete','Enter C for Complete, or I for Incomplete, in the student mark column.',1);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assessment_notificatons` (
  `at_notificaton_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `assessment_id` int(12) unsigned NOT NULL,
  `proxy_id` int(12) unsigned NOT NULL,
  `updated_date` bigint(64) unsigned NOT NULL,
  `updated_by` int(12) unsigned NOT NULL,
  PRIMARY KEY (`at_notificaton_id`),
  KEY `assessment_id` (`assessment_id`,`proxy_id`,`updated_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Notification list for assessments';
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assessment_objectives` (
  `aobjective_id` int(12) NOT NULL AUTO_INCREMENT,
  `assessment_id` int(12) NOT NULL DEFAULT '0',
  `objective_id` int(12) NOT NULL DEFAULT '0',
  `importance` int(11) DEFAULT NULL,
  `objective_details` text,
  `objective_type` enum('curricular_objective','clinical_presentation') NOT NULL DEFAULT 'curricular_objective',
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(12) NOT NULL,
  PRIMARY KEY (`aobjective_id`),
  KEY `assessment_id` (`assessment_id`),
  KEY `objective_id` (`objective_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assessment_option_values` (
  `aovalue_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `aoption_id` int(12) NOT NULL,
  `proxy_id` int(12) NOT NULL,
  `value` varchar(32) DEFAULT '',
  PRIMARY KEY (`aovalue_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assessment_options` (
  `aoption_id` int(12) NOT NULL AUTO_INCREMENT,
  `assessment_id` int(12) NOT NULL DEFAULT '0',
  `option_id` int(12) NOT NULL DEFAULT '0',
  `option_active` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`aoption_id`),
  KEY `assessment_id` (`assessment_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assessment_quiz_questions` (
  `aqquestion_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `assessment_id` int(11) NOT NULL,
  `qquestion_id` int(11) NOT NULL,
  PRIMARY KEY (`aqquestion_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assessment_statistics` (
  `assessment_statistic_id` int(12) NOT NULL AUTO_INCREMENT,
  `proxy_id` int(12) NOT NULL,
  `created_date` bigint(64) DEFAULT NULL,
  `module` varchar(64) DEFAULT NULL,
  `sub_module` varchar(64) DEFAULT NULL,
  `action` varchar(64) DEFAULT NULL,
  `assessment_id` varchar(64) NOT NULL,
  `distribution_id` varchar(64) NOT NULL,
  `target_id` varchar(64) NOT NULL,
  `progress_id` varchar(64) DEFAULT NULL,
  `prune_after` bigint(64) DEFAULT NULL,
  PRIMARY KEY (`assessment_statistic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assessments` (
  `assessment_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int(10) unsigned NOT NULL,
  `cohort` varchar(35) NOT NULL,
  `cperiod_id` int(11) DEFAULT NULL,
  `collection_id` int(10) DEFAULT NULL,
  `form_id` int(11) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `type` varchar(255) NOT NULL,
  `marking_scheme_id` int(10) unsigned NOT NULL,
  `numeric_grade_points_total` float unsigned DEFAULT NULL,
  `grade_weighting` float NOT NULL DEFAULT '0',
  `narrative` tinyint(1) NOT NULL DEFAULT '0',
  `self_assessment` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `group_assessment` int(1) unsigned NOT NULL DEFAULT '0',
  `required` tinyint(1) NOT NULL DEFAULT '1',
  `characteristic_id` int(4) NOT NULL,
  `show_learner` tinyint(1) NOT NULL DEFAULT '0',
  `due_date` bigint(64) unsigned NOT NULL DEFAULT '0',
  `release_date` bigint(64) NOT NULL DEFAULT '0',
  `release_until` bigint(64) NOT NULL DEFAULT '0',
  `order` smallint(6) NOT NULL DEFAULT '0',
  `grade_threshold` float NOT NULL DEFAULT '0',
  `notify_threshold` int(1) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_date` bigint(64) NOT NULL,
  `created_by` int(11) NOT NULL,
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(11) NOT NULL,
  `published` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`assessment_id`),
  KEY `order` (`order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assessments_lu_meta` (
  `id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `organisation_id` int(12) unsigned NOT NULL DEFAULT '0',
  `type` enum('rating','project','exam','paper','assessment','presentation','quiz','RAT','reflection') DEFAULT NULL,
  `title` varchar(60) NOT NULL,
  `description` text,
  `active` tinyint(1) unsigned DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=17 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `assessments_lu_meta` VALUES (1,1,'rating','Faculty, resident or preceptor rating',NULL,1),(2,1,'project','Final project',NULL,1),(3,1,'exam','Final written examination',NULL,1),(4,1,'exam','Laboratory or practical examination (except OSCE/SP)',NULL,1),(5,1,'exam','Midterm examination',NULL,1),(6,1,'exam','NBME subject examination',NULL,1),(7,1,'exam','Oral exam',NULL,1),(8,1,'exam','OSCE/SP examination',NULL,1),(9,1,'paper','Paper',NULL,1),(10,1,'assessment','Peer-assessment',NULL,1),(11,1,'presentation','Presentation',NULL,1),(12,1,'quiz','Quiz',NULL,1),(13,1,'RAT','RAT',NULL,1),(14,1,'reflection','Reflection',NULL,1),(15,1,'assessment','Self-assessment',NULL,1),(16,1,'assessment','Other assessments',NULL,1);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assessments_lu_meta_options` (
  `id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(60) NOT NULL,
  `active` tinyint(1) unsigned DEFAULT '1',
  `type` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `assessments_lu_meta_options` VALUES (1,'Essay questions',1,NULL),(2,'Fill-in, short answer questions',1,NULL),(3,'Multiple-choice, true/false, matching questions',1,NULL),(4,'Problem-solving written exercises',1,NULL),(5,'Track Late Submissions',1,'reflection, project, paper'),(6,'Track Resubmissions',1,'reflection, project, paper');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assignment_comments` (
  `acomment_id` int(12) NOT NULL AUTO_INCREMENT,
  `proxy_to_id` int(12) NOT NULL DEFAULT '0',
  `assignment_id` int(12) NOT NULL DEFAULT '0',
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `comment_title` varchar(128) NOT NULL,
  `comment_description` text NOT NULL,
  `comment_active` int(1) NOT NULL DEFAULT '1',
  `release_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  `notify` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`acomment_id`),
  KEY `assignment_id` (`assignment_id`,`proxy_id`,`comment_active`,`updated_date`,`updated_by`),
  KEY `afile_id` (`proxy_to_id`),
  KEY `release_date` (`release_date`),
  FULLTEXT KEY `comment_title` (`comment_title`,`comment_description`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assignment_contacts` (
  `acontact_id` int(11) NOT NULL AUTO_INCREMENT,
  `assignment_id` int(11) NOT NULL,
  `proxy_id` int(11) NOT NULL,
  `contact_order` int(11) DEFAULT '1',
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(11) NOT NULL,
  PRIMARY KEY (`acontact_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assignment_file_versions` (
  `afversion_id` int(11) NOT NULL AUTO_INCREMENT,
  `afile_id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `proxy_id` int(11) NOT NULL,
  `file_mimetype` varchar(64) NOT NULL,
  `file_version` int(5) DEFAULT NULL,
  `file_filename` varchar(128) NOT NULL,
  `file_filesize` int(32) NOT NULL,
  `file_active` int(1) NOT NULL DEFAULT '1',
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(11) NOT NULL,
  PRIMARY KEY (`afversion_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assignment_files` (
  `afile_id` int(11) NOT NULL AUTO_INCREMENT,
  `assignment_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT '0',
  `proxy_id` int(11) NOT NULL,
  `file_type` varchar(24) NOT NULL DEFAULT 'submission',
  `file_title` varchar(40) NOT NULL,
  `file_description` text,
  `file_active` int(1) NOT NULL DEFAULT '1',
  `updated_date` int(64) NOT NULL,
  `updated_by` int(11) NOT NULL,
  PRIMARY KEY (`afile_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assignments` (
  `assignment_id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `assessment_id` int(11) NOT NULL,
  `notice_id` int(11) DEFAULT NULL,
  `assignment_title` varchar(40) NOT NULL,
  `assignment_description` text NOT NULL,
  `assignment_active` int(11) NOT NULL,
  `required` int(1) NOT NULL,
  `due_date` bigint(64) NOT NULL DEFAULT '0',
  `assignment_uploads` int(11) NOT NULL DEFAULT '0',
  `max_file_uploads` int(11) NOT NULL DEFAULT '1',
  `release_date` bigint(64) NOT NULL DEFAULT '0',
  `release_until` bigint(64) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(11) NOT NULL,
  PRIMARY KEY (`assignment_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attached_quizzes` (
  `aquiz_id` int(12) NOT NULL AUTO_INCREMENT,
  `content_type` enum('event','community_page','assessment') NOT NULL DEFAULT 'event',
  `content_id` int(12) NOT NULL DEFAULT '0',
  `required` int(1) NOT NULL DEFAULT '0',
  `require_attendance` int(1) NOT NULL DEFAULT '0',
  `random_order` int(1) NOT NULL DEFAULT '0',
  `timeframe` varchar(64) NOT NULL,
  `quiz_id` int(12) NOT NULL DEFAULT '0',
  `quiz_title` varchar(128) NOT NULL,
  `quiz_notes` longtext NOT NULL,
  `quiztype_id` int(12) NOT NULL DEFAULT '0',
  `quiz_timeout` int(4) NOT NULL DEFAULT '0',
  `quiz_attempts` int(3) NOT NULL DEFAULT '0',
  `accesses` int(12) NOT NULL DEFAULT '0',
  `release_date` bigint(64) NOT NULL DEFAULT '0',
  `release_until` bigint(64) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`aquiz_id`),
  KEY `content_id` (`content_id`),
  KEY `required` (`required`),
  KEY `timeframe` (`timeframe`),
  KEY `quiztype_id` (`quiztype_id`),
  KEY `quiz_id` (`quiz_id`),
  KEY `accesses` (`accesses`),
  KEY `release_date` (`release_date`,`release_until`),
  KEY `quiz_timeout` (`quiz_timeout`),
  KEY `quiz_attempts` (`quiz_attempts`),
  KEY `content_id_2` (`content_id`,`release_date`,`release_until`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bookmarks` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uri` varchar(255) NOT NULL DEFAULT '',
  `bookmark_title` varchar(255) DEFAULT NULL,
  `proxy_id` int(11) DEFAULT NULL,
  `updated_date` bigint(64) DEFAULT NULL,
  `order` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bookmarks_default` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(64) DEFAULT NULL,
  `entity_value` varchar(64) DEFAULT NULL,
  `uri` varchar(255) NOT NULL DEFAULT '',
  `bookmark_title` varchar(255) DEFAULT NULL,
  `updated_date` bigint(64) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessment_additional_tasks` (
  `additional_task_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `adistribution_id` int(11) unsigned NOT NULL,
  `target_id` int(11) NOT NULL,
  `assessor_value` int(11) unsigned NOT NULL,
  `assessor_type` enum('internal','external') DEFAULT NULL,
  `delivery_date` bigint(64) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_date` bigint(64) NOT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  `deleted_date` bigint(64) DEFAULT NULL,
  PRIMARY KEY (`additional_task_id`),
  KEY `cbl_assessment_additional_tasks_ibfk_1` (`adistribution_id`),
  KEY `target_id` (`target_id`),
  KEY `assessor_type` (`assessor_type`,`assessor_value`),
  CONSTRAINT `cbl_assessment_additional_tasks_ibfk_1` FOREIGN KEY (`adistribution_id`) REFERENCES `cbl_assessment_distributions` (`adistribution_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessment_deleted_tasks` (
  `deleted_task_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `adistribution_id` int(11) unsigned NOT NULL,
  `target_id` int(11) NOT NULL,
  `assessor_value` int(11) unsigned NOT NULL,
  `assessor_type` enum('internal','external') DEFAULT NULL,
  `delivery_date` bigint(64) NOT NULL,
  `deleted_reason_id` int(11) unsigned NOT NULL,
  `deleted_reason_notes` varchar(255) DEFAULT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int(11) NOT NULL,
  `created_date` bigint(64) NOT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  `deleted_date` bigint(64) DEFAULT NULL,
  PRIMARY KEY (`deleted_task_id`),
  KEY `cbl_assessment_deleted_tasks_ibfk_1` (`adistribution_id`),
  KEY `cbl_assessment_deleted_tasks_ibfk_2` (`deleted_reason_id`),
  KEY `target_id` (`target_id`),
  KEY `assessor_type` (`assessor_type`,`assessor_value`),
  CONSTRAINT `cbl_assessment_deleted_tasks_ibfk_1` FOREIGN KEY (`adistribution_id`) REFERENCES `cbl_assessment_distributions` (`adistribution_id`),
  CONSTRAINT `cbl_assessment_deleted_tasks_ibfk_2` FOREIGN KEY (`deleted_reason_id`) REFERENCES `cbl_assessment_lu_task_deleted_reasons` (`reason_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessment_distribution_approvers` (
  `adapprover_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `adistribution_id` int(11) unsigned NOT NULL,
  `proxy_id` int(11) NOT NULL,
  `created_date` bigint(64) NOT NULL,
  `created_by` int(11) NOT NULL,
  PRIMARY KEY (`adapprover_id`),
  KEY `adistribution_id` (`adistribution_id`),
  CONSTRAINT `cbl_assessment_distribution_approvers_ibfk_1` FOREIGN KEY (`adistribution_id`) REFERENCES `cbl_assessment_distributions` (`adistribution_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessment_distribution_assessors` (
  `adassessor_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `adistribution_id` int(11) unsigned NOT NULL,
  `assessor_type` enum('proxy_id','cgroup_id','group_id','schedule_id','external_hash','course_id','organisation_id','eventtype_id') NOT NULL DEFAULT 'proxy_id',
  `assessor_scope` enum('self','children','faculty','internal_learners','external_learners','all_learners','attended_learners') NOT NULL DEFAULT 'self',
  `assessor_role` enum('learner','faculty','any') NOT NULL DEFAULT 'any',
  `assessor_value` varchar(128) NOT NULL DEFAULT '',
  `one45_p_id` int(11) DEFAULT NULL,
  `assessor_name` varchar(128) DEFAULT NULL,
  `assessor_start` bigint(64) DEFAULT NULL,
  `assessor_end` bigint(64) DEFAULT NULL,
  PRIMARY KEY (`adassessor_id`),
  KEY `adistribution_id` (`adistribution_id`),
  CONSTRAINT `cbl_assessment_distribution_assessors_ibfk_1` FOREIGN KEY (`adistribution_id`) REFERENCES `cbl_assessment_distributions` (`adistribution_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessment_distribution_authors` (
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
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessment_distribution_delegation_assignments` (
  `addassignment_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `addelegation_id` int(11) unsigned NOT NULL,
  `adistribution_id` int(11) unsigned NOT NULL,
  `dassessment_id` int(11) unsigned NOT NULL,
  `delegator_id` int(11) unsigned NOT NULL,
  `deleted_date` bigint(64) unsigned DEFAULT NULL,
  `deleted_reason_id` int(11) DEFAULT NULL,
  `deleted_reason` text CHARACTER SET utf8,
  `assessor_type` enum('internal','external') CHARACTER SET utf8 DEFAULT NULL,
  `assessor_value` int(11) unsigned DEFAULT NULL,
  `target_type` enum('proxy_id','external_hash','course_id','schedule_id') CHARACTER SET utf8 DEFAULT NULL,
  `target_value` int(11) unsigned DEFAULT NULL,
  `created_date` bigint(64) unsigned DEFAULT NULL,
  `created_by` int(11) unsigned DEFAULT NULL,
  `updated_date` bigint(64) unsigned DEFAULT NULL,
  `updated_by` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`addassignment_id`),
  KEY `adistribution_id` (`adistribution_id`),
  CONSTRAINT `cbl_assessment_distribution_delegation_assignments_ibfk_1` FOREIGN KEY (`adistribution_id`) REFERENCES `cbl_assessment_distributions` (`adistribution_id`)
) ENGINE=InnoDB AUTO_INCREMENT=230 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessment_distribution_delegations` (
  `addelegation_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `adistribution_id` int(11) unsigned NOT NULL,
  `delegator_id` int(11) unsigned NOT NULL,
  `delegator_type` enum('proxy_id','external_assessor_id') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'proxy_id',
  `start_date` bigint(64) unsigned DEFAULT NULL,
  `end_date` bigint(64) unsigned DEFAULT NULL,
  `delivery_date` bigint(64) unsigned DEFAULT NULL,
  `completed_by` int(11) DEFAULT NULL,
  `completed_reason` text CHARACTER SET utf8,
  `completed_date` bigint(64) unsigned DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_date` bigint(64) unsigned DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_date` bigint(64) unsigned DEFAULT NULL,
  `deleted_date` bigint(64) unsigned DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`addelegation_id`),
  KEY `adistribution_id` (`adistribution_id`),
  CONSTRAINT `cbl_assessment_distribution_delegations_ibfk_1` FOREIGN KEY (`adistribution_id`) REFERENCES `cbl_assessment_distributions` (`adistribution_id`)
) ENGINE=InnoDB AUTO_INCREMENT=209 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessment_distribution_delegators` (
  `addelegator_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `adistribution_id` int(11) unsigned NOT NULL,
  `delegator_type` enum('proxy_id','target') NOT NULL DEFAULT 'proxy_id',
  `delegator_id` int(11) DEFAULT NULL,
  `start_date` bigint(64) DEFAULT NULL,
  `end_date` bigint(64) DEFAULT NULL,
  PRIMARY KEY (`addelegator_id`),
  KEY `adistribution_id` (`adistribution_id`),
  CONSTRAINT `cbl_assessment_distribution_delegators_ibfk_1` FOREIGN KEY (`adistribution_id`) REFERENCES `cbl_assessment_distributions` (`adistribution_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessment_distribution_eventtypes` (
  `deventtype_id` int(11) NOT NULL AUTO_INCREMENT,
  `adistribution_id` int(11) NOT NULL,
  `eventtype_id` int(12) NOT NULL,
  PRIMARY KEY (`deventtype_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessment_distribution_methods` (
  `admethod_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `method_title` varchar(128) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_date` bigint(64) unsigned DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_date` bigint(64) unsigned DEFAULT NULL,
  `deleted_date` bigint(64) unsigned DEFAULT NULL,
  PRIMARY KEY (`admethod_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `cbl_assessment_distribution_methods` VALUES (1,'Rotation Schedule',1,1483495904,NULL,NULL,NULL),(2,'Delegation',1,1483495904,NULL,NULL,NULL),(3,'Learning Event',1,1483495904,NULL,NULL,NULL),(4,'Date Range',1,1483495904,NULL,NULL,NULL);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessment_distribution_reviewers` (
  `adreviewer_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `adistribution_id` int(11) unsigned NOT NULL,
  `proxy_id` int(11) NOT NULL,
  `created_date` bigint(64) NOT NULL,
  `created_by` int(11) NOT NULL,
  `updated_date` bigint(64) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `deleted_date` bigint(64) DEFAULT NULL,
  PRIMARY KEY (`adreviewer_id`),
  KEY `adistribution_id` (`adistribution_id`),
  CONSTRAINT `cbl_assessment_distribution_reviewers_ibfk_1` FOREIGN KEY (`adistribution_id`) REFERENCES `cbl_assessment_distributions` (`adistribution_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessment_distribution_schedule` (
  `adschedule_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `one45_moment_id` int(11) DEFAULT NULL,
  `adistribution_id` int(11) unsigned NOT NULL,
  `addelegator_id` int(11) DEFAULT NULL,
  `schedule_type` enum('block','rotation','repeat','course_id','rotation_id') NOT NULL DEFAULT 'block',
  `period_offset` bigint(64) DEFAULT NULL,
  `delivery_period` enum('after-start','before-middle','after-middle','before-end','after-end') NOT NULL DEFAULT 'after-start',
  `schedule_id` int(11) DEFAULT NULL,
  `frequency` tinyint(3) NOT NULL DEFAULT '1',
  `start_date` bigint(64) DEFAULT NULL,
  `end_date` bigint(64) DEFAULT NULL,
  PRIMARY KEY (`adschedule_id`),
  KEY `adistribution_id` (`adistribution_id`),
  CONSTRAINT `cbl_assessment_distribution_schedule_ibfk_1` FOREIGN KEY (`adistribution_id`) REFERENCES `cbl_assessment_distributions` (`adistribution_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessment_distribution_targets` (
  `adtarget_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `adistribution_id` int(11) unsigned NOT NULL,
  `target_type` enum('proxy_id','group_id','cgroup_id','course_id','schedule_id','organisation_id','self','eventtype_id') NOT NULL DEFAULT 'proxy_id',
  `target_scope` enum('self','children','faculty','internal_learners','external_learners','all_learners') NOT NULL DEFAULT 'self',
  `target_role` enum('learner','faculty','any') NOT NULL DEFAULT 'any',
  `target_id` int(11) DEFAULT NULL,
  `one45_p_id` int(11) DEFAULT NULL,
  `one45_moment_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`adtarget_id`),
  KEY `adistribution_id` (`adistribution_id`),
  CONSTRAINT `cbl_assessment_distribution_targets_ibfk_1` FOREIGN KEY (`adistribution_id`) REFERENCES `cbl_assessment_distributions` (`adistribution_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessment_distributions` (
  `adistribution_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `one45_scenariosAttached_id` int(11) unsigned DEFAULT NULL,
  `form_id` int(11) unsigned NOT NULL,
  `organisation_id` int(11) NOT NULL,
  `title` varchar(2048) NOT NULL DEFAULT '',
  `description` text,
  `cperiod_id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `assessment_type` enum('assessment','evaluation') NOT NULL,
  `assessor_option` enum('faculty','learner','individual_users') NOT NULL DEFAULT 'individual_users',
  `min_submittable` tinyint(3) NOT NULL DEFAULT '0',
  `max_submittable` tinyint(3) NOT NULL DEFAULT '0',
  `repeat_targets` tinyint(1) NOT NULL DEFAULT '0',
  `submittable_by_target` tinyint(1) NOT NULL DEFAULT '0',
  `flagging_notifications` enum('disabled','reviewers','pcoordinators','directors','authors') NOT NULL DEFAULT 'disabled',
  `start_date` bigint(64) NOT NULL,
  `end_date` bigint(64) NOT NULL,
  `release_start_date` bigint(64) NOT NULL,
  `release_end_date` bigint(64) NOT NULL,
  `release_date` bigint(64) DEFAULT NULL,
  `mandatory` tinyint(4) NOT NULL DEFAULT '0',
  `feedback_required` tinyint(1) NOT NULL DEFAULT '0',
  `distributor_timeout` bigint(64) DEFAULT NULL,
  `notifications` tinyint(1) NOT NULL DEFAULT '1',
  `visibility_status` enum('visible','hidden') NOT NULL DEFAULT 'visible',
  `delivery_date` bigint(64) DEFAULT NULL,
  `updated_date` bigint(64) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_date` bigint(64) NOT NULL,
  `created_by` int(11) NOT NULL,
  `deleted_date` bigint(64) DEFAULT NULL,
  PRIMARY KEY (`adistribution_id`),
  KEY `form_id` (`form_id`),
  CONSTRAINT `cbl_assessment_distributions_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `cbl_assessments_lu_forms` (`form_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessment_form_authors` (
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
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessment_form_elements` (
  `afelement_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `one45_form_id` int(11) unsigned DEFAULT NULL,
  `form_id` int(11) unsigned NOT NULL,
  `element_type` enum('item','data_source','text','objective') DEFAULT NULL,
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
  KEY `rubric_id` (`rubric_id`),
  CONSTRAINT `cbl_assessment_form_elements_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `cbl_assessments_lu_forms` (`form_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessment_item_authors` (
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
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessment_item_objectives` (
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
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessment_item_tags` (
  `aitag_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `item_id` int(11) unsigned NOT NULL,
  `tag_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`aitag_id`),
  KEY `item_id` (`item_id`),
  KEY `tag_id` (`tag_id`),
  CONSTRAINT `cbl_assessment_item_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `cbl_assessments_lu_tags` (`tag_id`),
  CONSTRAINT `cbl_assessment_item_tags_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `cbl_assessments_lu_items` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessment_lu_task_deleted_reasons` (
  `reason_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(11) unsigned NOT NULL,
  `reason_details` varchar(128) NOT NULL,
  `notes_required` tinyint(1) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_date` bigint(64) NOT NULL,
  `created_by` int(11) NOT NULL,
  `deleted_date` bigint(64) DEFAULT NULL,
  PRIMARY KEY (`reason_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `cbl_assessment_lu_task_deleted_reasons` VALUES (1,3,'Other (Please Specify)',1,NULL,NULL,1483495903,1,NULL),(2,1,'Did not work with the target',0,NULL,NULL,1456515087,1,NULL),(3,2,'Completed all relevant tasks on relevant targets',0,NULL,NULL,1456515087,1,NULL);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessment_notifications` (
  `anotification_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `adistribution_id` int(11) unsigned NOT NULL,
  `assessment_value` int(11) unsigned NOT NULL,
  `assessment_type` enum('assessment','delegation','approver') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'assessment',
  `notified_value` int(11) unsigned NOT NULL,
  `notified_type` enum('proxy_id','external_assessor_id') NOT NULL DEFAULT 'proxy_id',
  `notification_id` int(11) unsigned NOT NULL,
  `nuser_id` int(11) unsigned NOT NULL,
  `notification_type` enum('delegator_start','delegator_late','assessor_start','assessor_reminder','assessment_approver','assessor_late','flagged_response','assessment_removal','assessment_task_deleted','delegation_task_deleted','assessment_submitted','assessment_delegation_assignment_removed','assessment_submitted_notify_approver','assessment_submitted_notify_learner') NOT NULL DEFAULT 'assessor_start',
  `schedule_id` int(11) unsigned DEFAULT NULL,
  `sent_date` bigint(64) NOT NULL,
  PRIMARY KEY (`anotification_id`),
  KEY `adistribution_id` (`adistribution_id`),
  KEY `schedule_id` (`schedule_id`),
  CONSTRAINT `cbl_assessment_notifications_ibfk_1` FOREIGN KEY (`adistribution_id`) REFERENCES `cbl_assessment_distributions` (`adistribution_id`),
  CONSTRAINT `cbl_assessment_notifications_ibfk_2` FOREIGN KEY (`schedule_id`) REFERENCES `cbl_schedule` (`schedule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessment_progress` (
  `aprogress_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `one45_formsAttached_id` int(11) unsigned DEFAULT NULL,
  `one45_p_id` int(11) DEFAULT NULL,
  `one45_moment_id` int(11) DEFAULT NULL,
  `adistribution_id` int(11) unsigned NOT NULL,
  `dassessment_id` int(11) DEFAULT NULL,
  `uuid` varchar(36) NOT NULL,
  `assessor_type` enum('internal','external') NOT NULL DEFAULT 'internal',
  `assessor_value` int(12) NOT NULL,
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
  KEY `dassessment_id` (`dassessment_id`),
  CONSTRAINT `cbl_assessment_progress_ibfk_1` FOREIGN KEY (`adistribution_id`) REFERENCES `cbl_assessment_distributions` (`adistribution_id`),
  CONSTRAINT `cbl_assessment_progress_ibfk_2` FOREIGN KEY (`adtarget_id`) REFERENCES `cbl_assessment_distribution_targets` (`adtarget_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessment_progress_approvals` (
  `adapprover_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `aprogress_id` int(11) unsigned NOT NULL,
  `adistribution_id` int(11) unsigned NOT NULL,
  `approver_id` int(11) NOT NULL,
  `release_status` tinyint(1) NOT NULL DEFAULT '0',
  `comments` text,
  `created_date` bigint(64) NOT NULL,
  `created_by` int(11) NOT NULL,
  PRIMARY KEY (`adapprover_id`),
  KEY `aprogress_id` (`aprogress_id`),
  KEY `adistribution_id` (`adistribution_id`),
  CONSTRAINT `cbl_assessment_progress_approvals_ibfk_1` FOREIGN KEY (`adistribution_id`) REFERENCES `cbl_assessment_distributions` (`adistribution_id`),
  CONSTRAINT `cbl_assessment_progress_approvals_ibfk_2` FOREIGN KEY (`aprogress_id`) REFERENCES `cbl_assessment_progress` (`aprogress_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessment_progress_responses` (
  `epresponse_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `one45_answer_id` int(11) unsigned DEFAULT NULL,
  `aprogress_id` int(11) unsigned NOT NULL,
  `form_id` int(11) unsigned NOT NULL,
  `adistribution_id` int(11) unsigned NOT NULL,
  `assessor_type` enum('internal','external') NOT NULL DEFAULT 'internal',
  `assessor_value` int(12) NOT NULL,
  `afelement_id` int(11) unsigned NOT NULL,
  `iresponse_id` int(11) DEFAULT NULL,
  `comments` text,
  `created_date` bigint(64) NOT NULL,
  `created_by` int(11) NOT NULL,
  `updated_date` bigint(64) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `deleted_date` bigint(64) DEFAULT NULL,
  PRIMARY KEY (`epresponse_id`),
  KEY `aprogress_id` (`aprogress_id`),
  KEY `form_id` (`form_id`),
  KEY `adistribution_id` (`adistribution_id`),
  KEY `afelement_id` (`afelement_id`),
  CONSTRAINT `cbl_assessment_progress_responses_ibfk_1` FOREIGN KEY (`aprogress_id`) REFERENCES `cbl_assessment_progress` (`aprogress_id`),
  CONSTRAINT `cbl_assessment_progress_responses_ibfk_2` FOREIGN KEY (`form_id`) REFERENCES `cbl_assessments_lu_forms` (`form_id`),
  CONSTRAINT `cbl_assessment_progress_responses_ibfk_3` FOREIGN KEY (`adistribution_id`) REFERENCES `cbl_assessment_distributions` (`adistribution_id`),
  CONSTRAINT `cbl_assessment_progress_responses_ibfk_4` FOREIGN KEY (`afelement_id`) REFERENCES `cbl_assessment_form_elements` (`afelement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessment_report_audience` (
  `araudience_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `areport_id` int(11) unsigned NOT NULL,
  `audience_type` enum('proxy_id','organisation_id','cgroup_id','group_id','course_id','adtarget_id') NOT NULL DEFAULT 'proxy_id',
  `audience_value` int(11) DEFAULT NULL,
  PRIMARY KEY (`araudience_id`),
  KEY `areport_id` (`areport_id`),
  CONSTRAINT `cbl_assessment_report_audience_ibfk_1` FOREIGN KEY (`areport_id`) REFERENCES `cbl_assessment_reports` (`areport_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessment_report_caches` (
  `arcache_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `report_key` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `report_param_hash` varchar(32) COLLATE utf8_unicode_ci DEFAULT '',
  `report_meta_hash` varchar(32) COLLATE utf8_unicode_ci DEFAULT '',
  `target_type` enum('proxy_id','organisation_id','cgroup_id','group_id','course_id','adtarget_id','schedule_id','eventtype_id') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'proxy_id',
  `target_value` int(11) NOT NULL,
  `created_date` int(64) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`arcache_id`),
  KEY `report_key` (`report_key`),
  KEY `proxy_id_target_type_target_value_report_meta_hash` (`target_type`,`target_value`,`report_meta_hash`),
  KEY `proxy_id_target_type_target_value_report_param_hash` (`target_type`,`target_value`,`report_param_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessment_report_source_targets` (
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
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessment_report_sources` (
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
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessment_reports` (
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
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessment_rubric_authors` (
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
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessment_rubric_items` (
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
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessment_ss_current_tasks` (
  `current_task_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `adistribution_id` int(11) unsigned NOT NULL,
  `dassessment_id` int(11) unsigned DEFAULT NULL,
  `assessor_type` enum('internal','external') DEFAULT NULL,
  `assessor_value` int(11) unsigned NOT NULL,
  `target_type` enum('proxy_id','cgroup_id','group_id','schedule_id','external_hash','course_id','organisation_id') DEFAULT NULL,
  `target_value` int(11) NOT NULL,
  `title` text,
  `rotation_start_date` bigint(64) DEFAULT '0',
  `rotation_end_date` bigint(64) DEFAULT '0',
  `delivery_date` bigint(64) NOT NULL,
  `schedule_details` text,
  `created_by` int(11) NOT NULL,
  `created_date` bigint(64) NOT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  `deleted_date` bigint(64) DEFAULT NULL,
  PRIMARY KEY (`current_task_id`),
  KEY `cbl_assessment_ss_current_tasks_ibfk_1` (`adistribution_id`),
  KEY `cbl_assessment_ss_current_tasks_ibfk_2` (`dassessment_id`),
  CONSTRAINT `cbl_assessment_ss_current_tasks_ibfk_1` FOREIGN KEY (`adistribution_id`) REFERENCES `cbl_assessment_distributions` (`adistribution_id`),
  CONSTRAINT `cbl_assessment_ss_current_tasks_ibfk_2` FOREIGN KEY (`dassessment_id`) REFERENCES `cbl_distribution_assessments` (`dassessment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessment_ss_future_tasks` (
  `future_task_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `adistribution_id` int(11) unsigned NOT NULL,
  `assessor_type` enum('internal','external') DEFAULT NULL,
  `assessor_value` int(11) unsigned NOT NULL,
  `target_type` enum('proxy_id','cgroup_id','group_id','schedule_id','external_hash','course_id','organisation_id') DEFAULT NULL,
  `target_value` int(11) NOT NULL,
  `title` text,
  `rotation_start_date` bigint(64) DEFAULT '0',
  `rotation_end_date` bigint(64) DEFAULT '0',
  `delivery_date` bigint(64) NOT NULL,
  `schedule_details` text,
  `created_by` int(11) NOT NULL,
  `created_date` bigint(64) NOT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  `deleted_date` bigint(64) DEFAULT NULL,
  PRIMARY KEY (`future_task_id`),
  KEY `cbl_assessment_future_tasks_ibfk_1` (`adistribution_id`),
  CONSTRAINT `cbl_assessment_ss_future_tasks_ibfk_1` FOREIGN KEY (`adistribution_id`) REFERENCES `cbl_assessment_distributions` (`adistribution_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessments_lu_data_source_types` (
  `dstype_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `shortname` varchar(32) NOT NULL DEFAULT '',
  `name` varchar(128) NOT NULL DEFAULT '',
  `deleted_date` bigint(64) DEFAULT NULL,
  PRIMARY KEY (`dstype_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessments_lu_data_sources` (
  `dsource_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `dstype_id` int(10) unsigned NOT NULL,
  `source_value` varchar(255) NOT NULL DEFAULT '',
  `source_details` text,
  PRIMARY KEY (`dsource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessments_lu_form_relationships` (
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
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessments_lu_forms` (
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
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessments_lu_item_relationships` (
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
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessments_lu_item_responses` (
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
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessments_lu_items` (
  `item_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `one45_element_id` int(11) DEFAULT NULL,
  `organisation_id` int(11) NOT NULL,
  `itemtype_id` int(11) unsigned NOT NULL,
  `item_code` varchar(128) DEFAULT '',
  `item_text` text NOT NULL,
  `item_description` longtext,
  `mandatory` tinyint(1) DEFAULT '1',
  `comment_type` enum('disabled','optional','mandatory','flagged') NOT NULL DEFAULT 'disabled',
  `created_date` bigint(64) NOT NULL,
  `created_by` int(11) NOT NULL,
  `updated_date` bigint(64) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `deleted_date` bigint(64) DEFAULT NULL,
  PRIMARY KEY (`item_id`),
  KEY `itemtype_id` (`itemtype_id`),
  CONSTRAINT `cbl_assessments_lu_items_ibfk_1` FOREIGN KEY (`itemtype_id`) REFERENCES `cbl_assessments_lu_itemtypes` (`itemtype_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessments_lu_itemtypes` (
  `itemtype_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `shortname` varchar(128) NOT NULL DEFAULT '',
  `classname` varchar(128) DEFAULT NULL,
  `name` varchar(256) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `deleted_date` bigint(64) DEFAULT NULL,
  PRIMARY KEY (`itemtype_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `cbl_assessments_lu_itemtypes` VALUES (1,'horizontal_multiple_choice_single','HorizontalMultipleChoiceSingleResponse','Horizontal Multiple Choice (single response)','The rating scale allows evaluators to rate each question based on the scale you provide (i.e. 1 = Not Demonstrated, 2 = Needs Improvement, 3 = Satisfactory, 4 = Above Average).',NULL),(2,'vertical_multiple_choice_single','VerticalMultipleChoiceSingleResponse','Vertical Multiple Choice (single response)','The rating scale allows evaluators to rate each question based on the scale you provide (i.e. 1 = Not Demonstrated, 2 = Needs Improvement, 3 = Satisfactory, 4 = Above Average).',NULL),(3,'selectbox_single','DropdownSingleResponse','Drop Down (single response)','The dropdown allows evaluators to answer each question by choosing one of up to 100 options which have been provided to populate a select box.',NULL),(4,'horizontal_multiple_choice_multiple','HorizontalMultipleChoiceMultipleResponse','Horizontal Multiple Choice (multiple responses)','',NULL),(5,'vertical_multiple_choice_multiple','VerticalMultipleChoiceMultipleResponse','Vertical Multiple Choice (multiple responses)','',NULL),(6,'selectbox_multiple','DropdownMultipleResponse','Drop Down (multiple responses)','',NULL),(7,'free_text','FreeText','Free Text Comments','Allows the user to be asked for a simple free-text response. This can be used to get additional details about prior questions, or to simply ask for any comments from the evaluator regarding a specific topic.',NULL),(8,'date','Date','Date Selector','',NULL),(9,'user','User','Individual Selector','',NULL),(10,'numeric','Numeric','Numeric Field','',NULL),(11,'rubric_line','RubricLine','Rubric Attribute (single response)','The items which make up the body of a rubric. Each item allows one response to be chosen. There must be at least one response that contains response text, while the Response Category for each one is mandatory (and will populate the header line at the top of the rubric).',NULL),(12,'scale','Scale','Scale Item (single response)','The items which make up the body of a scale, sometimes called a Likert.  Each item allows one response to be chosen. The text of each response is optional, while the Response Category for each one is mandatory (and will populate the header line at the top of the scale)',NULL);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessments_lu_response_descriptors` (
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `cbl_assessments_lu_response_descriptors` VALUES (1,NULL,1,'Opportunities for Growth',1,1,1402703700,1,NULL,NULL,NULL),(2,NULL,1,'Borderline LOW',1,2,1402531209,1,NULL,NULL,NULL),(3,NULL,1,'Developing',1,3,1397670525,1,NULL,NULL,NULL),(4,NULL,1,'Achieving',1,4,1397670545,1,NULL,NULL,NULL),(5,NULL,1,'Borderline HIGH',1,5,1402531188,1,NULL,NULL,NULL),(6,NULL,1,'Exceptional',1,6,1402697384,1,NULL,NULL,NULL),(7,NULL,1,'Not Applicable',1,7,1402703794,1,NULL,NULL,NULL);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessments_lu_rubric_labels` (
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
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessments_lu_rubrics` (
  `rubric_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `one45_element_id` int(11) DEFAULT NULL,
  `organisation_id` int(11) NOT NULL,
  `rubric_title` varchar(2048) DEFAULT NULL,
  `rubric_description` text,
  `rubric_item_code` varchar(128) DEFAULT '',
  `is_scale` tinyint(1) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_date` bigint(64) NOT NULL,
  `created_by` int(11) NOT NULL,
  `deleted_date` bigint(64) DEFAULT NULL,
  PRIMARY KEY (`rubric_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessments_lu_tags` (
  `tag_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tag` varchar(32) NOT NULL DEFAULT '',
  `created_date` bigint(64) NOT NULL,
  `created_by` int(11) NOT NULL,
  `deleted_date` bigint(64) DEFAULT NULL,
  PRIMARY KEY (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_assessor_target_feedback` (
  `atfeedback_id` int(12) NOT NULL AUTO_INCREMENT,
  `dassessment_id` int(12) NOT NULL,
  `assessor_type` enum('internal','external') DEFAULT NULL,
  `assessor_value` int(11) DEFAULT NULL,
  `assessor_feedback` tinyint(1) DEFAULT NULL,
  `target_type` enum('internal','external') DEFAULT NULL,
  `target_value` int(11) DEFAULT NULL,
  `target_feedback` tinyint(1) DEFAULT NULL,
  `target_progress_value` enum('inprogress','complete') DEFAULT NULL,
  `comments` text,
  `updated_date` bigint(64) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_date` bigint(64) NOT NULL,
  `created_by` int(11) NOT NULL,
  `deleted_date` bigint(64) DEFAULT NULL,
  PRIMARY KEY (`atfeedback_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_course_contacts` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `assessor_value` int(11) NOT NULL,
  `assessor_type` enum('internal','external') NOT NULL DEFAULT 'internal',
  `visible` tinyint(1) DEFAULT '1',
  `created_by` int(11) NOT NULL,
  `created_date` int(11) NOT NULL,
  `updated_by` int(11) NOT NULL,
  `updated_date` int(11) NOT NULL,
  `deleted_date` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_distribution_assessment_assessors` (
  `aassessor_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `dassessment_id` int(12) NOT NULL,
  `adistribution_id` int(11) unsigned NOT NULL,
  `assessor_type` enum('proxy_id','cgroup_id','group_id','schedule_id','external_hash','course_id','organisation_id') NOT NULL DEFAULT 'proxy_id',
  `assessor_value` int(11) DEFAULT NULL,
  `delegation_list_id` int(11) DEFAULT NULL,
  `published` tinyint(1) NOT NULL DEFAULT '0',
  `created_date` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `updated_date` int(11) NOT NULL,
  `updated_by` int(11) NOT NULL,
  `deleted_date` int(11) DEFAULT NULL,
  PRIMARY KEY (`aassessor_id`),
  KEY `cbl_distribution_assessment_assessors_ibfk_1` (`adistribution_id`),
  CONSTRAINT `cbl_distribution_assessment_assessors_ibfk_1` FOREIGN KEY (`adistribution_id`) REFERENCES `cbl_assessment_distributions` (`adistribution_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_distribution_assessment_targets` (
  `atarget_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `dassessment_id` int(12) NOT NULL,
  `adistribution_id` int(11) unsigned NOT NULL,
  `target_type` enum('proxy_id','cgroup_id','group_id','schedule_id','external_hash','course_id','organisation_id','event_id') NOT NULL DEFAULT 'proxy_id',
  `target_value` int(11) DEFAULT NULL,
  `delegation_list_id` int(11) DEFAULT NULL,
  `created_date` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `updated_date` int(11) NOT NULL,
  `updated_by` int(11) NOT NULL,
  `deleted_date` int(11) DEFAULT NULL,
  PRIMARY KEY (`atarget_id`),
  KEY `cbl_distribution_assessment_targets_ibfk_1` (`adistribution_id`),
  KEY `target_type` (`target_type`,`target_value`),
  KEY `dassessment_id` (`dassessment_id`),
  CONSTRAINT `cbl_distribution_assessment_targets_ibfk_1` FOREIGN KEY (`adistribution_id`) REFERENCES `cbl_assessment_distributions` (`adistribution_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_distribution_assessments` (
  `dassessment_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `adistribution_id` int(11) unsigned DEFAULT NULL,
  `assessor_type` enum('internal','external') NOT NULL DEFAULT 'internal',
  `assessor_value` int(12) NOT NULL,
  `associated_record_id` int(11) unsigned DEFAULT NULL,
  `associated_record_type` enum('event_id','proxy_id','course_id','group_id','schedule_id') DEFAULT NULL,
  `number_submitted` int(11) unsigned DEFAULT '0',
  `min_submittable` int(11) unsigned DEFAULT '0',
  `max_submittable` int(11) unsigned DEFAULT '0',
  `published` tinyint(1) NOT NULL DEFAULT '0',
  `start_date` bigint(64) NOT NULL DEFAULT '0',
  `end_date` bigint(64) NOT NULL DEFAULT '0',
  `delivery_date` bigint(64) DEFAULT NULL,
  `rotation_start_date` bigint(64) DEFAULT '0',
  `rotation_end_date` bigint(64) DEFAULT '0',
  `external_hash` varchar(32) DEFAULT NULL,
  `additional_assessment` tinyint(1) DEFAULT '0',
  `created_date` bigint(64) NOT NULL,
  `created_by` int(11) NOT NULL,
  `updated_date` bigint(64) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `deleted_date` bigint(64) DEFAULT NULL,
  PRIMARY KEY (`dassessment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_external_assessor_email_history` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `eassessor_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_date` int(11) NOT NULL,
  `updated_by` int(11) NOT NULL,
  `updated_date` int(11) NOT NULL,
  `deleted_date` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_external_assessors` (
  `eassessor_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `firstname` varchar(35) NOT NULL DEFAULT '',
  `lastname` varchar(35) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL DEFAULT '',
  `created_date` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `deleted_date` int(11) DEFAULT NULL,
  `updated_date` int(11) NOT NULL,
  `updated_by` int(11) NOT NULL,
  PRIMARY KEY (`eassessor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_leave_tracking` (
  `leave_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `proxy_id` int(11) NOT NULL,
  `type_id` int(12) DEFAULT NULL,
  `start_date` int(11) NOT NULL,
  `end_date` int(11) NOT NULL,
  `days_used` int(12) DEFAULT NULL,
  `weekdays_used` int(12) DEFAULT NULL,
  `weekend_days_used` int(12) DEFAULT NULL,
  `comments` text,
  `created_date` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `updated_date` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `deleted_date` int(11) DEFAULT NULL,
  PRIMARY KEY (`leave_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_lu_leave_tracking_types` (
  `type_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type_value` varchar(128) NOT NULL DEFAULT '',
  `updated_date` bigint(64) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_date` bigint(64) NOT NULL,
  `created_by` int(11) NOT NULL,
  `deleted_date` bigint(64) DEFAULT NULL,
  PRIMARY KEY (`type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `cbl_lu_leave_tracking_types` VALUES (1,'Absence',NULL,NULL,0,1,NULL),(2,'Academic half day',NULL,NULL,0,1,NULL),(3,'Conference',NULL,NULL,0,1,NULL),(4,'Education days',NULL,NULL,0,1,NULL),(5,'Elective',NULL,NULL,0,1,NULL),(6,'Interview',NULL,NULL,0,1,NULL),(7,'Maternity',NULL,NULL,0,1,NULL),(8,'Medical',NULL,NULL,0,1,NULL),(9,'Other',NULL,NULL,0,1,NULL),(10,'Paternity',NULL,NULL,0,1,NULL),(11,'Professional development',NULL,NULL,0,1,NULL),(12,'Research',NULL,NULL,0,1,NULL),(13,'Sick',NULL,NULL,0,1,NULL),(14,'Stat',NULL,NULL,0,1,NULL),(15,'Study days',NULL,NULL,0,1,NULL),(16,'Vacation',NULL,NULL,0,1,NULL);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_schedule` (
  `schedule_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `one45_rotation_id` int(11) DEFAULT NULL,
  `one45_owner_group_id` int(11) DEFAULT NULL,
  `one45_moment_id` int(11) DEFAULT NULL,
  `title` varchar(256) DEFAULT NULL,
  `code` varchar(128) DEFAULT NULL,
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
  `block_type_id` int(11) DEFAULT NULL,
  `draft_id` int(11) DEFAULT NULL,
  `schedule_order` int(11) DEFAULT NULL,
  `copied_from` int(11) DEFAULT NULL,
  `created_date` bigint(20) NOT NULL,
  `created_by` int(11) NOT NULL,
  `updated_date` bigint(20) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `deleted_date` bigint(64) DEFAULT NULL,
  PRIMARY KEY (`schedule_id`),
  KEY `schedule_parent_id` (`schedule_parent_id`),
  KEY `draft_id` (`draft_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_schedule_audience` (
  `saudience_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `one45_p_id` int(11) DEFAULT NULL,
  `schedule_id` int(11) unsigned NOT NULL,
  `schedule_slot_id` int(11) DEFAULT NULL,
  `audience_type` enum('proxy_id','course_id','cperiod_id') NOT NULL DEFAULT 'proxy_id',
  `audience_value` int(11) NOT NULL,
  `deleted_date` int(11) DEFAULT NULL,
  `one45_rotation_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`saudience_id`),
  KEY `schedule_od` (`schedule_id`),
  KEY `audience_type` (`audience_type`,`audience_value`),
  KEY `schedule_id` (`schedule_id`),
  CONSTRAINT `cbl_schedule_audience_ibfk_1` FOREIGN KEY (`schedule_id`) REFERENCES `cbl_schedule` (`schedule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_schedule_draft_authors` (
  `cbl_schedule_draft_author_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `cbl_schedule_draft_id` int(11) DEFAULT NULL,
  `proxy_id` int(11) DEFAULT NULL,
  `created_date` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`cbl_schedule_draft_author_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_schedule_drafts` (
  `cbl_schedule_draft_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `draft_title` varchar(64) NOT NULL DEFAULT '',
  `status` enum('draft','live') NOT NULL DEFAULT 'draft',
  `course_id` int(11) DEFAULT NULL,
  `cperiod_id` int(11) DEFAULT '0',
  `created_date` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `deleted_date` int(11) DEFAULT NULL,
  `updated_date` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`cbl_schedule_draft_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_schedule_lu_block_types` (
  `block_type_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `number_of_blocks` tinyint(3) NOT NULL,
  `deleted_date` bigint(64) DEFAULT NULL,
  PRIMARY KEY (`block_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `cbl_schedule_lu_block_types` VALUES (1,'1 Week',52,NULL),(2,'2 Week',26,NULL),(3,'4 Week',13,NULL);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_schedule_slot_types` (
  `slot_type_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `slot_type_code` varchar(5) NOT NULL DEFAULT '',
  `slot_type_description` varchar(64) NOT NULL DEFAULT '',
  `deleted_date` int(11) DEFAULT NULL,
  PRIMARY KEY (`slot_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `cbl_schedule_slot_types` VALUES (1,'OSL','On Service Learner',NULL),(2,'OFFSL','Off Service Learner',NULL);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbl_schedule_slots` (
  `schedule_slot_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `schedule_id` int(11) NOT NULL,
  `slot_type_id` int(11) NOT NULL,
  `slot_spaces` int(11) NOT NULL DEFAULT '1',
  `course_id` int(11) DEFAULT NULL,
  `created_date` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `deleted_date` int(11) DEFAULT NULL,
  `updated_date` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`schedule_slot_id`),
  KEY `schedule_id` (`schedule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cbme_course_objectives` (
  `cbme_course_objective_id` int(12) NOT NULL AUTO_INCREMENT,
  `objective_id` int(12) NOT NULL,
  `course_id` int(12) NOT NULL,
  `created_date` bigint(64) NOT NULL,
  `created_by` int(12) NOT NULL,
  `updated_date` bigint(64) DEFAULT NULL,
  `updated_by` int(12) DEFAULT NULL,
  `deleted_date` bigint(64) DEFAULT NULL,
  PRIMARY KEY (`cbme_course_objective_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `communities` (
  `community_id` int(12) NOT NULL AUTO_INCREMENT,
  `community_parent` int(12) NOT NULL DEFAULT '0',
  `category_id` int(12) NOT NULL DEFAULT '0',
  `community_url` text NOT NULL,
  `octype_id` int(12) NOT NULL DEFAULT '1',
  `community_template` varchar(30) NOT NULL DEFAULT 'default',
  `community_theme` varchar(12) NOT NULL DEFAULT 'default',
  `community_shortname` varchar(32) NOT NULL,
  `community_title` varchar(64) NOT NULL,
  `community_description` text NOT NULL,
  `community_keywords` varchar(255) NOT NULL,
  `community_email` varchar(128) NOT NULL,
  `community_website` text NOT NULL,
  `community_protected` int(1) NOT NULL DEFAULT '1',
  `community_registration` int(1) NOT NULL DEFAULT '1',
  `community_members` text NOT NULL,
  `community_active` int(1) NOT NULL DEFAULT '1',
  `community_opened` bigint(64) NOT NULL DEFAULT '0',
  `community_notifications` int(1) NOT NULL DEFAULT '0',
  `sub_communities` int(1) NOT NULL DEFAULT '0',
  `storage_usage` int(32) NOT NULL DEFAULT '0',
  `storage_max` bigint(64) NOT NULL DEFAULT '1073741824',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  `community_twitter_handle` varchar(16) DEFAULT NULL,
  `community_twitter_hashtags` text,
  PRIMARY KEY (`community_id`),
  KEY `sub_communities` (`sub_communities`),
  KEY `community_parent` (`community_parent`,`category_id`,`community_protected`,`community_registration`,`community_opened`,`updated_date`,`updated_by`),
  KEY `community_shortname` (`community_shortname`),
  KEY `max_storage` (`storage_max`),
  KEY `storage_usage` (`storage_usage`),
  KEY `community_active` (`community_active`),
  FULLTEXT KEY `community_title` (`community_title`,`community_description`,`community_keywords`),
  FULLTEXT KEY `community_url` (`community_url`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `communities_categories` (
  `category_id` int(12) NOT NULL AUTO_INCREMENT,
  `category_parent` int(12) NOT NULL DEFAULT '0',
  `category_title` varchar(64) NOT NULL,
  `category_description` text NOT NULL,
  `category_keywords` varchar(255) NOT NULL,
  `category_visible` int(1) NOT NULL DEFAULT '1',
  `category_status` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`category_id`),
  KEY `category_parent` (`category_parent`,`category_keywords`),
  KEY `category_status` (`category_status`),
  FULLTEXT KEY `category_description` (`category_description`,`category_keywords`)
) ENGINE=MyISAM AUTO_INCREMENT=19 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `communities_categories` VALUES (1,0,'Official Communities','','',1,0),(2,0,'Other Communities','','',1,0),(4,1,'Administration','A container for official administrative units to reside.','',1,0),(5,1,'Courses, etc.','A container for official course groups and communities to reside.','',1,0),(7,2,'Health & Wellness','','',1,0),(8,2,'Sports & Leisure','','',1,0),(9,2,'Learning & Teaching','','',1,0),(15,2,'Careers in Health Care','','',1,0),(11,2,'Miscellaneous','','',1,0),(12,1,'Committees','','',1,0),(14,2,'Social Responsibility','','',1,0),(16,2,'Cultures & Communities','','',1,0),(17,2,'Business & Finance','','',1,0),(18,2,'Arts & Entertainment','','',1,0);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `communities_modules` (
  `module_id` int(12) NOT NULL AUTO_INCREMENT,
  `module_shortname` varchar(32) NOT NULL,
  `module_version` varchar(8) NOT NULL DEFAULT '1.0.0',
  `module_title` varchar(64) NOT NULL,
  `module_description` text NOT NULL,
  `module_active` int(1) NOT NULL DEFAULT '1',
  `module_visible` int(1) NOT NULL DEFAULT '1',
  `module_permissions` text NOT NULL,
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`module_id`),
  KEY `module_shortname` (`module_shortname`),
  KEY `module_active` (`module_active`),
  FULLTEXT KEY `module_title` (`module_title`,`module_description`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `communities_modules` VALUES (1,'announcements','1.0.0','Announcements','The Announcements module allows you to post Announcements to your community.',1,1,'a:4:{s:3:\"add\";i:1;s:6:\"delete\";i:1;s:4:\"edit\";i:1;s:5:\"index\";i:0;}',1173116408,1),(2,'discussions','1.0.0','Discussions','The Discussions module is a simple method you can use to host discussions.',1,1,'a:10:{s:9:\"add-forum\";i:1;s:8:\"add-post\";i:0;s:12:\"delete-forum\";i:1;s:11:\"delete-post\";i:0;s:10:\"edit-forum\";i:1;s:9:\"edit-post\";i:0;s:5:\"index\";i:0;s:10:\"reply-post\";i:0;s:10:\"view-forum\";i:0;s:9:\"view-post\";i:0;}',1173116408,1),(3,'galleries','1.0.0','Galleries','The Galleries module allows you to add photo galleries and images to your community.',1,1,'a:13:{s:11:\"add-comment\";i:0;s:11:\"add-gallery\";i:1;s:9:\"add-photo\";i:0;s:10:\"move-photo\";i:0;s:14:\"delete-comment\";i:0;s:14:\"delete-gallery\";i:1;s:12:\"delete-photo\";i:0;s:12:\"edit-comment\";i:0;s:12:\"edit-gallery\";i:1;s:10:\"edit-photo\";i:0;s:5:\"index\";i:0;s:12:\"view-gallery\";i:0;s:10:\"view-photo\";i:0;}',1173116408,1),(4,'shares','1.0.0','Document Sharing','The Document Sharing module gives you the ability to upload and share documents within your community.',1,1,'a:25:{s:11:\"add-comment\";i:0;s:10:\"add-folder\";i:1;s:8:\"add-file\";i:0;s:8:\"add-html\";i:0;s:8:\"add-link\";i:0;s:9:\"move-file\";i:0;s:9:\"move-link\";i:0;s:9:\"move-html\";i:0;s:12:\"add-revision\";i:0;s:14:\"delete-comment\";i:0;s:13:\"delete-folder\";i:1;s:11:\"delete-file\";i:0;s:11:\"delete-link\";i:0;s:11:\"delete-html\";i:0;s:15:\"delete-revision\";i:0;s:12:\"edit-comment\";i:0;s:11:\"edit-folder\";i:1;s:9:\"edit-file\";i:0;s:9:\"edit-link\";i:0;s:9:\"edit-html\";i:0;s:5:\"index\";i:0;s:11:\"view-folder\";i:0;s:9:\"view-file\";i:0;s:9:\"view-link\";i:0;s:9:\"view-html\";i:0;}',1173116408,1),(5,'polls','1.0.0','Polling','This module allows communities to create their own polls for everything from adhoc open community polling to individual community member votes.',1,1,'a:10:{s:8:\"add-poll\";i:1;s:12:\"add-question\";i:1;s:13:\"edit-question\";i:1;s:15:\"delete-question\";i:1;s:11:\"delete-poll\";i:1;s:9:\"edit-poll\";i:1;s:9:\"view-poll\";i:0;s:9:\"vote-poll\";i:0;s:5:\"index\";i:0;s:8:\"my-votes\";i:0;}',1216256830,1408),(6,'events','1.0.0','Events','The Events module allows you to post events to your community which will be accessible through iCalendar ics files or viewable in the community.',1,1,'a:4:{s:3:\"add\";i:1;s:6:\"delete\";i:1;s:4:\"edit\";i:1;s:5:\"index\";i:0;}',1225209600,1),(7,'quizzes','1.0.0','Quizzes','This module allows communities to create their own quizzes for summative or formative evaluation.',1,1,'a:4:{s:5:\"index\";i:0;s:7:\"attempt\";i:0;s:7:\"results\";i:0;s:13:\"save-response\";i:0;}',1216256830,1),(8,'mtdtracking','1.0.0','MTD Tracking','The MTD Tracking module allows Program Assistants to enter the weekly schedule for each of their Residents.',0,0,'a:2:{s:4:\"edit\";i:1;s:5:\"index\";i:0;}',1216256830,5440);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `communities_most_active` (
  `cmactive_id` int(12) NOT NULL AUTO_INCREMENT,
  `community_id` int(12) NOT NULL,
  `activity_order` int(2) NOT NULL,
  PRIMARY KEY (`cmactive_id`,`community_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `communities_template_permissions` (
  `ctpermission_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `permission_type` enum('category_id','group') DEFAULT NULL,
  `permission_value` varchar(32) DEFAULT NULL,
  `template` varchar(32) NOT NULL,
  PRIMARY KEY (`ctpermission_id`),
  KEY `permission_index` (`permission_type`,`permission_value`,`template`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `communities_template_permissions` VALUES (1,'','','default'),(2,'group','faculty,staff,medtech','course'),(3,'category_id','5','course'),(4,'group','faculty,staff,medtech','committee'),(5,'category_id','12','committee'),(6,'group','faculty,staff,medtech','learningmodule'),(7,'group','faculty,staff,medtech','virtualpatient'),(9,'category_id','','virtualpatient'),(8,'category_id','','learningmodule');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_acl` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `resource_type` varchar(64) DEFAULT NULL,
  `resource_value` int(11) DEFAULT NULL,
  `create` tinyint(11) DEFAULT NULL,
  `read` tinyint(11) DEFAULT NULL,
  `update` tinyint(11) DEFAULT NULL,
  `delete` tinyint(11) DEFAULT NULL,
  `assertion` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_acl_groups` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `cgroup_id` int(11) DEFAULT NULL,
  `resource_type` varchar(64) DEFAULT NULL,
  `resource_value` int(11) DEFAULT NULL,
  `create` tinyint(11) DEFAULT NULL,
  `read` tinyint(11) DEFAULT NULL,
  `update` tinyint(11) DEFAULT NULL,
  `delete` tinyint(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_announcements` (
  `cannouncement_id` int(12) NOT NULL AUTO_INCREMENT,
  `community_id` int(12) NOT NULL DEFAULT '0',
  `cpage_id` int(12) NOT NULL DEFAULT '0',
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `announcement_active` int(1) NOT NULL DEFAULT '1',
  `pending_moderation` int(1) NOT NULL DEFAULT '0',
  `release_date` bigint(64) NOT NULL DEFAULT '0',
  `release_until` bigint(64) NOT NULL DEFAULT '0',
  `announcement_title` varchar(128) NOT NULL,
  `announcement_description` text NOT NULL,
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cannouncement_id`),
  KEY `community_id` (`community_id`,`proxy_id`,`release_date`,`release_until`,`updated_date`,`updated_by`),
  FULLTEXT KEY `announcement_title` (`announcement_title`,`announcement_description`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_courses` (
  `community_course_id` int(12) NOT NULL AUTO_INCREMENT,
  `community_id` int(12) NOT NULL,
  `course_id` int(12) NOT NULL,
  PRIMARY KEY (`community_course_id`),
  KEY `community_id` (`community_id`,`course_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_discussion_file_versions` (
  `cdfversion_id` int(12) NOT NULL AUTO_INCREMENT,
  `cdfile_id` int(12) NOT NULL DEFAULT '0',
  `cdtopic_id` int(12) NOT NULL DEFAULT '0',
  `community_id` int(12) NOT NULL DEFAULT '0',
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `file_version` int(5) NOT NULL DEFAULT '1',
  `file_mimetype` varchar(128) NOT NULL,
  `file_filename` varchar(128) NOT NULL,
  `file_filesize` int(32) NOT NULL DEFAULT '0',
  `file_active` int(1) NOT NULL DEFAULT '1',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cdfversion_id`),
  KEY `cdtopic_id` (`cdfile_id`,`cdtopic_id`,`community_id`,`proxy_id`,`file_version`,`updated_date`,`updated_by`),
  KEY `file_active` (`file_active`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_discussion_topics` (
  `cdtopic_id` int(12) NOT NULL AUTO_INCREMENT,
  `cdtopic_parent` int(12) NOT NULL DEFAULT '0',
  `cdiscussion_id` int(12) NOT NULL DEFAULT '0',
  `community_id` int(12) NOT NULL DEFAULT '0',
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `anonymous` int(1) NOT NULL DEFAULT '0',
  `topic_title` varchar(128) NOT NULL DEFAULT '',
  `topic_description` text NOT NULL,
  `topic_active` int(1) NOT NULL DEFAULT '1',
  `release_date` bigint(64) NOT NULL DEFAULT '0',
  `release_until` bigint(64) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  `notify` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cdtopic_id`),
  KEY `cdiscussion_parent` (`cdtopic_parent`,`community_id`,`proxy_id`,`updated_date`,`updated_by`),
  KEY `cdiscussion_id` (`cdiscussion_id`),
  KEY `topic_active` (`topic_active`),
  KEY `release_date` (`release_date`,`release_until`),
  KEY `community_id` (`cdtopic_id`,`community_id`),
  KEY `cdtopic_parent` (`cdtopic_parent`,`community_id`),
  KEY `user` (`cdiscussion_id`,`community_id`,`topic_active`,`cdtopic_parent`,`proxy_id`,`release_date`,`release_until`),
  KEY `admin` (`cdiscussion_id`,`community_id`,`topic_active`,`cdtopic_parent`),
  KEY `post` (`proxy_id`,`community_id`,`cdtopic_id`,`cdtopic_parent`,`topic_active`),
  KEY `release` (`proxy_id`,`community_id`,`cdtopic_parent`,`topic_active`,`release_date`),
  KEY `community` (`cdtopic_id`,`community_id`),
  FULLTEXT KEY `topic_title` (`topic_title`,`topic_description`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_discussions` (
  `cdiscussion_id` int(12) NOT NULL AUTO_INCREMENT,
  `community_id` int(12) NOT NULL DEFAULT '0',
  `cpage_id` int(12) NOT NULL DEFAULT '0',
  `forum_title` varchar(64) NOT NULL DEFAULT '',
  `forum_description` text NOT NULL,
  `forum_category` text NOT NULL,
  `forum_order` int(6) NOT NULL DEFAULT '0',
  `forum_active` int(1) NOT NULL DEFAULT '1',
  `admin_notifications` int(1) NOT NULL DEFAULT '0',
  `allow_public_read` int(1) NOT NULL DEFAULT '0',
  `allow_public_post` int(1) NOT NULL DEFAULT '0',
  `allow_public_reply` int(1) NOT NULL DEFAULT '0',
  `allow_troll_read` int(1) NOT NULL DEFAULT '1',
  `allow_troll_post` int(1) NOT NULL DEFAULT '0',
  `allow_troll_reply` int(1) NOT NULL DEFAULT '0',
  `allow_member_read` int(1) NOT NULL DEFAULT '1',
  `allow_member_post` int(1) NOT NULL DEFAULT '1',
  `allow_member_reply` int(1) NOT NULL DEFAULT '1',
  `release_date` bigint(64) NOT NULL DEFAULT '0',
  `release_until` bigint(64) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cdiscussion_id`),
  KEY `community_id` (`community_id`,`forum_order`,`allow_member_post`,`allow_member_reply`),
  KEY `release_date` (`release_date`),
  KEY `release_until` (`release_until`),
  KEY `allow_member_read` (`allow_member_read`),
  KEY `allow_public_read` (`allow_public_read`),
  KEY `allow_troll_read` (`allow_troll_read`),
  KEY `allow_troll_post` (`allow_troll_post`),
  KEY `allow_troll_reply` (`allow_troll_reply`),
  KEY `allow_public_post` (`allow_public_post`),
  KEY `allow_public_reply` (`allow_public_reply`),
  KEY `forum_active` (`forum_active`),
  KEY `admin_notification` (`admin_notifications`),
  KEY `page_id` (`cdiscussion_id`,`cpage_id`,`community_id`),
  KEY `community_id2` (`community_id`,`forum_active`,`cpage_id`,`forum_order`,`forum_title`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_discussions_files` (
  `cdfile_id` int(12) NOT NULL AUTO_INCREMENT,
  `cdtopic_id` int(12) NOT NULL DEFAULT '0',
  `cdiscussion_id` int(12) NOT NULL DEFAULT '0',
  `community_id` int(12) NOT NULL DEFAULT '0',
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `file_title` varchar(128) NOT NULL,
  `file_description` text NOT NULL,
  `file_active` int(1) NOT NULL DEFAULT '1',
  `allow_member_revision` int(1) NOT NULL DEFAULT '0',
  `allow_troll_revision` int(1) NOT NULL DEFAULT '0',
  `access_method` int(1) NOT NULL DEFAULT '0',
  `release_date` bigint(64) NOT NULL DEFAULT '0',
  `release_until` bigint(64) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  `notify` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cdfile_id`),
  KEY `cdfile_id` (`cdfile_id`,`community_id`,`proxy_id`,`updated_date`,`updated_by`),
  KEY `file_active` (`file_active`),
  KEY `release_date` (`release_date`,`release_until`),
  KEY `allow_member_edit` (`allow_member_revision`,`allow_troll_revision`),
  KEY `access_method` (`access_method`),
  FULLTEXT KEY `file_title` (`file_title`,`file_description`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_discussions_open` (
  `cdopen_id` int(12) NOT NULL AUTO_INCREMENT,
  `community_id` int(12) NOT NULL DEFAULT '0',
  `page_id` int(12) NOT NULL DEFAULT '0',
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `discussion_open` varchar(1000) NOT NULL,
  PRIMARY KEY (`cdopen_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_events` (
  `cevent_id` int(12) NOT NULL AUTO_INCREMENT,
  `community_id` int(12) NOT NULL DEFAULT '0',
  `cpage_id` int(12) NOT NULL DEFAULT '0',
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `event_active` int(1) NOT NULL DEFAULT '1',
  `pending_moderation` int(1) NOT NULL DEFAULT '0',
  `event_start` bigint(64) NOT NULL DEFAULT '0',
  `event_finish` bigint(64) NOT NULL DEFAULT '0',
  `event_location` varchar(128) NOT NULL,
  `release_date` bigint(64) NOT NULL DEFAULT '0',
  `release_until` bigint(64) NOT NULL DEFAULT '0',
  `event_title` varchar(128) NOT NULL,
  `event_description` text NOT NULL,
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cevent_id`),
  KEY `community_id` (`community_id`,`cpage_id`,`proxy_id`,`event_start`,`event_finish`,`release_date`,`release_until`,`updated_date`,`updated_by`),
  FULLTEXT KEY `event_title` (`event_title`,`event_description`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_galleries` (
  `cgallery_id` int(12) NOT NULL AUTO_INCREMENT,
  `community_id` int(12) NOT NULL DEFAULT '0',
  `cpage_id` int(12) NOT NULL DEFAULT '0',
  `gallery_title` varchar(64) NOT NULL,
  `gallery_description` text NOT NULL,
  `gallery_cgphoto_id` int(12) NOT NULL DEFAULT '0',
  `gallery_order` int(6) NOT NULL DEFAULT '0',
  `gallery_active` int(1) NOT NULL DEFAULT '1',
  `admin_notifications` int(1) NOT NULL DEFAULT '0',
  `allow_public_read` int(1) NOT NULL DEFAULT '0',
  `allow_public_upload` int(1) NOT NULL DEFAULT '0',
  `allow_public_comment` int(1) NOT NULL DEFAULT '0',
  `allow_troll_read` int(1) NOT NULL DEFAULT '1',
  `allow_troll_upload` int(1) NOT NULL DEFAULT '0',
  `allow_troll_comment` int(1) NOT NULL DEFAULT '0',
  `allow_member_read` int(1) NOT NULL DEFAULT '1',
  `allow_member_upload` int(1) NOT NULL DEFAULT '1',
  `allow_member_comment` int(1) NOT NULL DEFAULT '1',
  `release_date` bigint(64) NOT NULL DEFAULT '0',
  `release_until` bigint(64) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cgallery_id`),
  KEY `community_id` (`community_id`,`gallery_order`,`allow_member_upload`,`allow_member_comment`),
  KEY `release_date` (`release_date`),
  KEY `release_until` (`release_until`),
  KEY `allow_member_read` (`allow_member_read`),
  KEY `allow_public_read` (`allow_public_read`),
  KEY `allow_troll_read` (`allow_troll_read`),
  KEY `allow_troll_upload` (`allow_troll_upload`),
  KEY `allow_troll_comments` (`allow_troll_comment`),
  KEY `allow_public_upload` (`allow_public_upload`),
  KEY `allow_public_comments` (`allow_public_comment`),
  KEY `gallery_active` (`gallery_active`),
  KEY `admin_notification` (`admin_notifications`),
  KEY `gallery_cgphoto_id` (`gallery_cgphoto_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_gallery_comments` (
  `cgcomment_id` int(12) NOT NULL AUTO_INCREMENT,
  `cgphoto_id` int(12) NOT NULL DEFAULT '0',
  `cgallery_id` int(12) NOT NULL DEFAULT '0',
  `community_id` int(12) NOT NULL DEFAULT '0',
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `comment_title` varchar(128) NOT NULL,
  `comment_description` text NOT NULL,
  `comment_active` int(1) NOT NULL DEFAULT '1',
  `release_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  `notify` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cgcomment_id`),
  KEY `cgallery_id` (`cgallery_id`,`community_id`,`proxy_id`,`comment_active`,`updated_date`,`updated_by`),
  KEY `cgphoto_id` (`cgphoto_id`),
  KEY `release_date` (`release_date`),
  FULLTEXT KEY `comment_title` (`comment_title`,`comment_description`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_gallery_photos` (
  `cgphoto_id` int(12) NOT NULL AUTO_INCREMENT,
  `cgallery_id` int(12) NOT NULL DEFAULT '0',
  `community_id` int(12) NOT NULL DEFAULT '0',
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `photo_mimetype` varchar(64) NOT NULL,
  `photo_filename` varchar(128) NOT NULL,
  `photo_filesize` int(32) NOT NULL DEFAULT '0',
  `photo_title` varchar(128) NOT NULL,
  `photo_description` text NOT NULL,
  `photo_active` int(1) NOT NULL DEFAULT '1',
  `release_date` bigint(64) NOT NULL DEFAULT '0',
  `release_until` bigint(64) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  `notify` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cgphoto_id`),
  KEY `cgallery_id` (`cgallery_id`,`community_id`,`proxy_id`,`photo_filesize`,`updated_date`,`updated_by`),
  KEY `photo_active` (`photo_active`),
  KEY `release_date` (`release_date`,`release_until`),
  FULLTEXT KEY `photo_title` (`photo_title`,`photo_description`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_history` (
  `chistory_id` int(12) NOT NULL AUTO_INCREMENT,
  `community_id` int(12) NOT NULL DEFAULT '0',
  `cpage_id` int(12) DEFAULT '0',
  `record_id` int(12) NOT NULL DEFAULT '0',
  `record_parent` int(12) NOT NULL DEFAULT '0',
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `history_key` varchar(255) DEFAULT NULL,
  `history_message` text NOT NULL,
  `history_display` int(1) NOT NULL DEFAULT '0',
  `history_timestamp` bigint(64) NOT NULL DEFAULT '0',
  PRIMARY KEY (`chistory_id`),
  KEY `community_id` (`community_id`,`history_display`),
  KEY `history_timestamp` (`history_timestamp`),
  KEY `cpage_id` (`cpage_id`),
  KEY `record_id` (`record_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_mailing_list_members` (
  `cmlmember_id` int(12) NOT NULL AUTO_INCREMENT,
  `community_id` int(12) NOT NULL DEFAULT '0',
  `proxy_id` int(12) NOT NULL,
  `email` varchar(64) NOT NULL,
  `member_active` int(1) NOT NULL DEFAULT '0',
  `list_administrator` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cmlmember_id`),
  UNIQUE KEY `member_id` (`community_id`,`proxy_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_mailing_lists` (
  `cmlist_id` int(12) NOT NULL AUTO_INCREMENT,
  `community_id` int(12) NOT NULL DEFAULT '0',
  `list_name` varchar(64) NOT NULL,
  `list_type` enum('announcements','discussion','inactive') NOT NULL DEFAULT 'inactive',
  `last_checked` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cmlist_id`),
  KEY `community_id` (`community_id`,`list_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_members` (
  `cmember_id` int(12) NOT NULL AUTO_INCREMENT,
  `community_id` int(12) NOT NULL DEFAULT '0',
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `member_active` int(1) NOT NULL DEFAULT '1',
  `member_joined` bigint(64) NOT NULL DEFAULT '0',
  `member_acl` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cmember_id`),
  KEY `community_id` (`community_id`,`proxy_id`,`member_joined`,`member_acl`),
  KEY `member_active` (`member_active`),
  KEY `community_id_2` (`community_id`,`proxy_id`,`member_active`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_modules` (
  `cmodule_id` int(12) NOT NULL AUTO_INCREMENT,
  `community_id` int(12) NOT NULL DEFAULT '0',
  `module_id` int(12) NOT NULL DEFAULT '0',
  `module_active` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cmodule_id`),
  KEY `community_id` (`community_id`,`module_id`,`module_active`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_notifications` (
  `cnotification_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `release_time` bigint(64) NOT NULL DEFAULT '0',
  `community` varchar(128) NOT NULL,
  `type` varchar(64) NOT NULL,
  `subject` varchar(128) NOT NULL DEFAULT '',
  `record_id` int(12) NOT NULL DEFAULT '0',
  `author_id` int(12) NOT NULL DEFAULT '0',
  `body` text NOT NULL,
  `url` varchar(45) NOT NULL,
  PRIMARY KEY (`cnotification_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_notify_members` (
  `cnmember_id` int(12) NOT NULL AUTO_INCREMENT,
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `record_id` int(12) NOT NULL DEFAULT '0',
  `community_id` int(12) NOT NULL DEFAULT '0',
  `notify_type` varchar(32) NOT NULL DEFAULT 'announcement',
  `notify_active` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cnmember_id`),
  KEY `idx_notify_members` (`community_id`,`record_id`,`notify_type`,`notify_active`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_page_navigation` (
  `cpnav_id` int(12) NOT NULL AUTO_INCREMENT,
  `community_id` int(12) NOT NULL,
  `cpage_id` int(12) NOT NULL DEFAULT '0',
  `nav_page_id` int(11) DEFAULT NULL,
  `show_nav` int(1) NOT NULL DEFAULT '1',
  `nav_title` varchar(100) NOT NULL DEFAULT 'Next',
  `nav_type` enum('next','previous') NOT NULL DEFAULT 'next',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cpnav_id`),
  KEY `cpage_id` (`cpage_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_page_options` (
  `cpoption_id` int(12) NOT NULL AUTO_INCREMENT,
  `community_id` int(12) NOT NULL,
  `cpage_id` int(12) NOT NULL DEFAULT '0',
  `option_title` varchar(32) NOT NULL,
  `option_value` int(12) NOT NULL DEFAULT '1',
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cpoption_id`,`community_id`,`cpage_id`),
  KEY `cpage_id` (`cpage_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_pages` (
  `cpage_id` int(12) NOT NULL AUTO_INCREMENT,
  `community_id` int(12) NOT NULL DEFAULT '0',
  `parent_id` int(12) NOT NULL DEFAULT '0',
  `page_order` int(3) NOT NULL DEFAULT '0',
  `page_type` varchar(16) NOT NULL DEFAULT 'default',
  `menu_title` varchar(48) NOT NULL,
  `page_title` text NOT NULL,
  `page_url` varchar(329) NOT NULL,
  `page_content` longtext NOT NULL,
  `page_active` int(1) NOT NULL DEFAULT '1',
  `page_visible` int(1) NOT NULL DEFAULT '1',
  `allow_member_view` int(1) NOT NULL DEFAULT '1',
  `allow_troll_view` int(1) NOT NULL DEFAULT '1',
  `allow_public_view` int(1) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cpage_id`),
  KEY `cpage_id` (`cpage_id`,`community_id`,`page_url`,`page_active`),
  KEY `community_id` (`community_id`,`parent_id`,`page_url`,`page_active`),
  KEY `page_order` (`page_order`),
  KEY `community_id_2` (`community_id`,`page_url`),
  KEY `community_id_3` (`community_id`,`page_type`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_permissions` (
  `cpermission_id` int(12) NOT NULL AUTO_INCREMENT,
  `community_id` int(12) NOT NULL DEFAULT '0',
  `module_id` int(12) NOT NULL DEFAULT '0',
  `action` varchar(64) NOT NULL,
  `level` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cpermission_id`),
  KEY `community_id` (`community_id`,`module_id`,`action`,`level`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_polls` (
  `cpolls_id` int(12) NOT NULL AUTO_INCREMENT,
  `community_id` int(12) NOT NULL DEFAULT '0',
  `cpage_id` int(12) NOT NULL DEFAULT '0',
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `poll_title` varchar(64) NOT NULL,
  `poll_description` text NOT NULL,
  `poll_terminology` varchar(32) NOT NULL DEFAULT 'Poll',
  `poll_active` int(1) NOT NULL DEFAULT '1',
  `poll_order` int(6) NOT NULL DEFAULT '0',
  `poll_notifications` int(1) NOT NULL DEFAULT '0',
  `allow_multiple` int(1) NOT NULL DEFAULT '0',
  `number_of_votes` int(4) DEFAULT NULL,
  `allow_public_read` int(1) NOT NULL DEFAULT '0',
  `allow_public_vote` int(1) NOT NULL DEFAULT '0',
  `allow_public_results` int(1) NOT NULL DEFAULT '0',
  `allow_public_results_after` int(1) NOT NULL DEFAULT '0',
  `allow_troll_read` int(1) NOT NULL DEFAULT '0',
  `allow_troll_vote` int(1) NOT NULL DEFAULT '0',
  `allow_troll_results` int(1) NOT NULL DEFAULT '0',
  `allow_troll_results_after` int(1) NOT NULL DEFAULT '0',
  `allow_member_read` int(1) NOT NULL DEFAULT '1',
  `allow_member_vote` int(1) NOT NULL DEFAULT '1',
  `allow_member_results` int(1) NOT NULL DEFAULT '0',
  `allow_member_results_after` int(1) NOT NULL DEFAULT '1',
  `release_date` bigint(64) NOT NULL DEFAULT '0',
  `release_until` bigint(64) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cpolls_id`),
  KEY `community_id` (`community_id`),
  KEY `proxy_id` (`proxy_id`),
  KEY `poll_title` (`poll_title`),
  KEY `poll_notifications` (`poll_notifications`),
  KEY `release_date` (`release_date`),
  KEY `release_until` (`release_until`),
  KEY `allow_multiple` (`allow_multiple`),
  KEY `allow_member_read` (`allow_member_read`),
  KEY `allow_member_vote` (`allow_member_vote`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_polls_access` (
  `cpaccess_id` int(12) NOT NULL AUTO_INCREMENT,
  `cpolls_id` int(12) NOT NULL DEFAULT '0',
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cpaccess_id`),
  KEY `proxy_id` (`proxy_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_polls_questions` (
  `cpquestion_id` int(12) NOT NULL AUTO_INCREMENT,
  `cpolls_id` int(12) NOT NULL DEFAULT '0',
  `community_id` int(12) NOT NULL DEFAULT '0',
  `cpage_id` int(12) NOT NULL DEFAULT '0',
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `updated_date` int(12) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  `poll_question` text NOT NULL,
  `question_order` int(2) NOT NULL DEFAULT '0',
  `minimum_responses` int(2) NOT NULL DEFAULT '1',
  `maximum_responses` int(2) NOT NULL DEFAULT '1',
  `question_active` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`cpquestion_id`),
  KEY `cpolls_id` (`cpolls_id`),
  KEY `community_id` (`community_id`),
  KEY `cpage_id` (`cpage_id`),
  FULLTEXT KEY `poll_question` (`poll_question`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_polls_responses` (
  `cpresponses_id` int(12) NOT NULL AUTO_INCREMENT,
  `cpquestion_id` int(12) NOT NULL,
  `cpolls_id` int(12) NOT NULL DEFAULT '0',
  `response` text NOT NULL,
  `response_index` int(5) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cpresponses_id`),
  KEY `cpolls_id` (`cpolls_id`),
  KEY `response_index` (`response_index`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_polls_results` (
  `cpresults_id` int(12) NOT NULL AUTO_INCREMENT,
  `cpresponses_id` int(12) NOT NULL DEFAULT '0',
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cpresults_id`),
  KEY `proxy_id` (`proxy_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_share_comments` (
  `cscomment_id` int(12) NOT NULL AUTO_INCREMENT,
  `csfile_id` int(12) NOT NULL DEFAULT '0',
  `cshare_id` int(12) NOT NULL DEFAULT '0',
  `community_id` int(12) NOT NULL DEFAULT '0',
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `comment_title` varchar(128) NOT NULL,
  `comment_description` text NOT NULL,
  `comment_active` int(1) NOT NULL DEFAULT '1',
  `release_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  `notify` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cscomment_id`),
  KEY `cshare_id` (`cshare_id`,`community_id`,`proxy_id`,`comment_active`,`updated_date`,`updated_by`),
  KEY `csfile_id` (`csfile_id`),
  KEY `release_date` (`release_date`),
  FULLTEXT KEY `comment_title` (`comment_title`,`comment_description`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_share_file_versions` (
  `csfversion_id` int(12) NOT NULL AUTO_INCREMENT,
  `csfile_id` int(12) NOT NULL DEFAULT '0',
  `cshare_id` int(12) NOT NULL DEFAULT '0',
  `community_id` int(12) NOT NULL DEFAULT '0',
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `file_version` int(5) NOT NULL DEFAULT '1',
  `file_mimetype` varchar(128) NOT NULL,
  `file_filename` varchar(128) NOT NULL,
  `file_filesize` int(32) NOT NULL DEFAULT '0',
  `file_active` int(1) NOT NULL DEFAULT '1',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`csfversion_id`),
  KEY `cshare_id` (`csfile_id`,`cshare_id`,`community_id`,`proxy_id`,`file_version`,`updated_date`,`updated_by`),
  KEY `file_active` (`file_active`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_share_files` (
  `csfile_id` int(12) NOT NULL AUTO_INCREMENT,
  `cshare_id` int(12) NOT NULL DEFAULT '0',
  `community_id` int(12) NOT NULL DEFAULT '0',
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `file_title` varchar(128) NOT NULL,
  `file_description` text NOT NULL,
  `file_active` int(1) NOT NULL DEFAULT '1',
  `allow_member_read` int(1) NOT NULL DEFAULT '1',
  `allow_member_revision` int(1) NOT NULL DEFAULT '0',
  `allow_troll_read` int(1) NOT NULL DEFAULT '0',
  `allow_troll_revision` int(1) NOT NULL DEFAULT '0',
  `access_method` int(1) NOT NULL DEFAULT '0',
  `student_hidden` int(1) NOT NULL DEFAULT '1',
  `release_date` bigint(64) NOT NULL DEFAULT '0',
  `release_until` bigint(64) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  `notify` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`csfile_id`),
  KEY `cshare_id` (`cshare_id`,`community_id`,`proxy_id`,`updated_date`,`updated_by`),
  KEY `file_active` (`file_active`),
  KEY `release_date` (`release_date`,`release_until`),
  KEY `allow_member_edit` (`allow_member_revision`,`allow_troll_revision`),
  KEY `access_method` (`access_method`),
  FULLTEXT KEY `file_title` (`file_title`,`file_description`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_share_html` (
  `cshtml_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `cshare_id` int(12) NOT NULL DEFAULT '0',
  `community_id` int(12) NOT NULL DEFAULT '0',
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `html_title` varchar(128) NOT NULL,
  `html_description` varchar(256) DEFAULT NULL,
  `html_content` text NOT NULL,
  `html_active` int(1) NOT NULL DEFAULT '1',
  `allow_member_read` int(1) NOT NULL DEFAULT '1',
  `allow_troll_read` int(1) NOT NULL DEFAULT '0',
  `access_method` int(1) NOT NULL DEFAULT '0',
  `student_hidden` int(1) NOT NULL DEFAULT '0',
  `release_date` bigint(64) NOT NULL DEFAULT '0',
  `release_until` bigint(64) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  `notify` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cshtml_id`),
  KEY `cshtml_id` (`cshare_id`,`community_id`,`proxy_id`,`updated_date`,`updated_by`),
  KEY `html_active` (`html_active`),
  KEY `release_date` (`release_date`,`release_until`),
  KEY `allow_read` (`allow_member_read`,`allow_troll_read`),
  KEY `access_method` (`access_method`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_share_links` (
  `cslink_id` int(12) NOT NULL AUTO_INCREMENT,
  `cshare_id` int(12) NOT NULL DEFAULT '0',
  `community_id` int(12) NOT NULL DEFAULT '0',
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `link_title` varchar(128) NOT NULL,
  `link_url` varchar(128) NOT NULL,
  `link_description` text NOT NULL,
  `link_active` int(1) NOT NULL DEFAULT '1',
  `allow_member_read` int(1) NOT NULL DEFAULT '1',
  `allow_member_revision` int(1) NOT NULL DEFAULT '0',
  `allow_troll_read` int(1) NOT NULL DEFAULT '0',
  `allow_troll_revision` int(1) NOT NULL DEFAULT '0',
  `access_method` int(1) NOT NULL DEFAULT '1',
  `iframe_resize` int(1) NOT NULL DEFAULT '0',
  `session_variables` int(1) NOT NULL DEFAULT '0',
  `student_hidden` int(1) NOT NULL DEFAULT '0',
  `release_date` bigint(64) NOT NULL DEFAULT '0',
  `release_until` bigint(64) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  `notify` int(1) NOT NULL DEFAULT '0',
  `angel_entry_id` varchar(100) DEFAULT NULL,
  `angel_parent_id` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`cslink_id`),
  KEY `cshare_id` (`cshare_id`,`community_id`,`proxy_id`,`updated_date`,`updated_by`),
  KEY `link_active` (`link_active`),
  KEY `release_date` (`release_date`,`release_until`),
  KEY `allow_member_edit` (`allow_member_revision`,`allow_troll_revision`),
  FULLTEXT KEY `link_title` (`link_title`,`link_description`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_shares` (
  `cshare_id` int(12) NOT NULL AUTO_INCREMENT,
  `community_id` int(12) NOT NULL DEFAULT '0',
  `cpage_id` int(12) NOT NULL DEFAULT '0',
  `parent_folder_id` int(12) NOT NULL DEFAULT '0',
  `folder_title` varchar(64) NOT NULL,
  `folder_description` text NOT NULL,
  `folder_icon` int(3) NOT NULL DEFAULT '1',
  `folder_order` int(6) NOT NULL DEFAULT '0',
  `folder_active` int(1) NOT NULL DEFAULT '1',
  `student_hidden` int(1) NOT NULL DEFAULT '0',
  `admin_notifications` int(1) NOT NULL DEFAULT '0',
  `allow_public_read` int(1) NOT NULL DEFAULT '0',
  `allow_public_upload` int(1) NOT NULL DEFAULT '0',
  `allow_public_comment` int(1) NOT NULL DEFAULT '0',
  `allow_troll_read` int(1) NOT NULL DEFAULT '1',
  `allow_troll_upload` int(1) NOT NULL DEFAULT '0',
  `allow_troll_comment` int(1) NOT NULL DEFAULT '0',
  `allow_member_read` int(1) NOT NULL DEFAULT '1',
  `allow_member_upload` int(1) NOT NULL DEFAULT '1',
  `allow_member_comment` int(1) NOT NULL DEFAULT '1',
  `release_date` bigint(64) NOT NULL DEFAULT '0',
  `release_until` bigint(64) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cshare_id`),
  KEY `community_id` (`community_id`,`folder_order`,`allow_member_upload`,`allow_member_comment`),
  KEY `release_date` (`release_date`),
  KEY `release_until` (`release_until`),
  KEY `allow_member_read` (`allow_member_read`),
  KEY `allow_public_read` (`allow_public_read`),
  KEY `allow_troll_read` (`allow_troll_read`),
  KEY `allow_troll_upload` (`allow_troll_upload`),
  KEY `allow_troll_comments` (`allow_troll_comment`),
  KEY `allow_public_upload` (`allow_public_upload`),
  KEY `allow_public_comments` (`allow_public_comment`),
  KEY `folder_active` (`folder_active`),
  KEY `admin_notification` (`admin_notifications`),
  KEY `folder_icon` (`folder_icon`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_shares_open` (
  `cshareopem_id` int(12) NOT NULL AUTO_INCREMENT,
  `community_id` int(12) NOT NULL DEFAULT '0',
  `page_id` int(12) NOT NULL DEFAULT '0',
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `shares_open` varchar(1000) NOT NULL,
  PRIMARY KEY (`cshareopem_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_templates` (
  `template_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `template_name` varchar(60) NOT NULL,
  `template_description` text,
  `organisation_id` int(12) unsigned DEFAULT NULL,
  `group` int(12) unsigned DEFAULT NULL,
  `role` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`template_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `community_templates` VALUES (1,'default','',NULL,NULL,NULL),(2,'committee','',NULL,NULL,NULL),(3,'virtualpatient','',NULL,NULL,NULL),(4,'learningmodule','',NULL,NULL,NULL),(5,'course','',NULL,NULL,NULL);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_type_page_options` (
  `ctpoption_id` int(12) NOT NULL AUTO_INCREMENT,
  `ctpage_id` int(12) NOT NULL,
  `option_title` varchar(32) NOT NULL,
  `option_value` int(12) NOT NULL DEFAULT '1',
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ctpoption_id`,`ctpage_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `community_type_page_options` VALUES (1,39,'community_title',1,1,0),(2,49,'community_title',1,1,0);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_type_pages` (
  `ctpage_id` int(12) NOT NULL AUTO_INCREMENT,
  `type_id` int(12) NOT NULL DEFAULT '0',
  `type_scope` enum('organisation','global') NOT NULL DEFAULT 'global',
  `parent_id` int(12) NOT NULL DEFAULT '0',
  `page_order` int(3) NOT NULL DEFAULT '0',
  `page_type` varchar(16) NOT NULL DEFAULT 'default',
  `menu_title` varchar(48) NOT NULL,
  `page_title` text NOT NULL,
  `page_url` varchar(512) DEFAULT NULL,
  `page_content` longtext NOT NULL,
  `page_active` tinyint(1) NOT NULL DEFAULT '1',
  `page_visible` tinyint(1) NOT NULL DEFAULT '1',
  `allow_member_view` tinyint(1) NOT NULL DEFAULT '1',
  `allow_troll_view` tinyint(1) NOT NULL DEFAULT '1',
  `allow_public_view` tinyint(1) NOT NULL DEFAULT '0',
  `lock_page` tinyint(1) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ctpage_id`),
  KEY `type_id` (`type_id`,`type_scope`)
) ENGINE=MyISAM AUTO_INCREMENT=58 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `community_type_pages` VALUES (1,1,'global',0,0,'default','Home','Home','','',1,1,1,1,1,1,1362062187,1),(2,1,'global',0,1,'announcements','Announcements','Announcements','announcements','',1,1,1,1,0,0,1362062187,1),(3,1,'global',0,2,'discussions','Discussions','Discussions','discussions','',1,1,1,1,0,0,1362062187,1),(8,1,'global',0,3,'shares','Document Sharing','Document Sharing','shares','',1,1,1,1,0,0,1362062187,1),(4,1,'global',0,4,'events','Events','Events','events','',1,1,1,1,0,0,1362062187,1),(5,1,'global',0,5,'galleries','Galleries','Galleries','galleries','',1,1,1,1,0,0,1362062187,1),(6,1,'global',0,6,'polls','Polling','Polling','polls','',1,1,1,1,0,0,1362062187,1),(7,1,'global',0,7,'quizzes','Quizzes','Quizzes','quizzes','',1,1,1,1,0,0,1362062187,1),(9,2,'global',0,0,'course','Background','Background Information','',' ',1,1,1,0,1,1,1362062187,1),(10,2,'global',0,1,'course','Course Calendar','Course Calendar','course_calendar',' ',1,1,1,0,1,1,1362062187,1),(11,2,'global',0,2,'default','Prerequisites','Prerequisites (Foundational Knowledge)','prerequisites',' ',1,1,1,0,1,1,1362062187,1),(12,2,'global',0,3,'default','Course Aims','Aims of the Course','course_aims',' ',1,1,1,0,1,1,1362062187,1),(13,2,'global',0,4,'course','Learning Objectives','Learning Objectives','objectives',' ',1,1,1,0,1,1,1362062187,1),(14,2,'global',0,5,'course','MCC Presentations','MCC Presentations','mcc_presentations',' ',1,1,1,0,1,1,1362062187,1),(15,2,'global',0,6,'default','Teaching Strategies','Teaching and Learning Strategies','teaching_strategies',' ',1,1,1,0,1,1,1362062187,1),(16,2,'global',0,7,'default','Assessment Strategies','Assessment Strategies','assessment_strategies',' ',1,1,1,0,1,1,1362062187,1),(17,2,'global',0,8,'default','Resources','Resources','resources',' ',1,1,1,0,1,1,1362062187,1),(18,2,'global',0,9,'default','Expectations of Students','What is Expected of Students','expectations_of_students',' ',1,1,1,0,1,1,1362062187,1),(19,2,'global',0,10,'default','Expectations of Faculty','What is Expected of Course Faculty','expectations_of_faculty',' ',1,1,1,0,1,1,1362062187,1),(20,1,'organisation',0,0,'default','Home','Home','','',1,1,1,1,1,1,1362062187,1),(21,1,'organisation',0,0,'announcements','Announcements','Announcements','announcements','',1,1,1,1,0,0,1362062187,1),(22,1,'organisation',0,1,'discussions','Discussions','Discussions','discussions','',1,1,1,1,0,0,1362062187,1),(23,1,'organisation',0,3,'events','Events','Events','events','',1,1,1,1,0,0,1362062187,1),(24,1,'organisation',0,4,'galleries','Galleries','Galleries','galleries','',1,1,1,1,0,0,1362062187,1),(25,1,'organisation',0,5,'polls','Polling','Polling','polls','',1,1,1,1,0,0,1362062187,1),(26,1,'organisation',0,6,'quizzes','Quizzes','Quizzes','quizzes','',1,1,1,1,0,0,1362062187,1),(27,1,'organisation',0,2,'shares','Document Sharing','Document Sharing','shares','',1,1,1,1,0,0,1362062187,1),(28,2,'organisation',0,0,'course','Background','Background Information','',' ',1,1,1,0,1,1,1362062187,1),(29,2,'organisation',0,1,'course','Course Calendar','Course Calendar','course_calendar',' ',1,1,1,0,1,1,1362062187,1),(30,2,'organisation',0,2,'default','Prerequisites','Prerequisites (Foundational Knowledge)','prerequisites',' ',1,1,1,0,1,1,1362062187,1),(31,2,'organisation',0,3,'default','Course Aims','Aims of the Course','course_aims',' ',1,1,1,0,1,1,1362062187,1),(32,2,'organisation',0,4,'course','Learning Objectives','Learning Objectives','objectives',' ',1,1,1,0,1,1,1362062187,1),(33,2,'organisation',0,5,'course','MCC Presentations','MCC Presentations','mcc_presentations',' ',1,1,1,0,1,1,1362062187,1),(34,2,'organisation',0,6,'default','Teaching Strategies','Teaching and Learning Strategies','teaching_strategies',' ',1,1,1,0,1,1,1362062187,1),(35,2,'organisation',0,7,'default','Assessment Strategies','Assessment Strategies','assessment_strategies',' ',1,1,1,0,1,1,1362062187,1),(36,2,'organisation',0,8,'default','Resources','Resources','resources',' ',1,1,1,0,1,1,1362062187,1),(37,2,'organisation',0,9,'default','Expectations of Students','What is Expected of Students','expectations_of_students',' ',1,1,1,0,1,1,1362062187,1),(38,2,'organisation',0,10,'default','Expectations of Faculty','What is Expected of Course Faculty','expectations_of_faculty',' ',1,1,1,0,1,1,1362062187,1),(39,3,'global',0,0,'default','Community Title','Community Title','',' ',1,1,1,1,1,0,0,1),(40,3,'global',0,7,'default','Credits','Credits','credits','',1,1,1,1,1,0,0,1),(41,3,'global',0,4,'default','Formative Assessment','Formative Assessment','formative_assessment','',1,1,1,1,1,0,0,1),(42,3,'global',0,3,'default','Foundational Knowledge','Foundational Knowledge','foundational_knowledge','',1,1,1,1,1,0,0,1),(43,3,'global',0,1,'default','Introduction','Introduction','introduction','',1,1,1,1,1,0,0,1),(44,3,'global',0,2,'default','Objectives','Objectives','objectives','',1,1,1,1,1,0,0,1),(45,3,'global',0,8,'url','Print Version','Print Version','print_version','',1,1,1,1,1,0,0,1),(46,3,'global',0,6,'default','Summary','Summary','summary','',1,1,1,1,1,0,0,1),(47,3,'global',0,5,'default','Test your understanding','Test your understanding','test_your_understanding','',1,1,1,1,1,0,0,1),(49,3,'organisation',0,0,'default','Community Title','Community Title','',' ',1,1,1,1,1,0,0,1),(50,3,'organisation',0,7,'default','Credits','Credits','credits','',1,1,1,1,1,0,0,1),(51,3,'organisation',0,4,'default','Formative Assessment','Formative Assessment','formative_assessment','',1,1,1,1,1,0,0,1),(52,3,'organisation',0,3,'default','Foundational Knowledge','Foundational Knowledge','foundational_knowledge','',1,1,1,1,1,0,0,1),(53,3,'organisation',0,1,'default','Introduction','Introduction','introduction','',1,1,1,1,1,0,0,1),(54,3,'organisation',0,2,'default','Objectives','Objectives','objectives','',1,1,1,1,1,0,0,1),(55,3,'organisation',0,8,'url','Print Version','Print Version','print_version','',1,1,1,1,1,0,0,1),(56,3,'organisation',0,6,'default','Summary','Summary','summary','',1,1,1,1,1,0,0,1),(57,3,'organisation',0,5,'default','Test your understanding','Test your understanding','test_your_understanding','',1,1,1,1,1,0,0,1);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_type_templates` (
  `cttemplate_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `template_id` int(12) unsigned NOT NULL,
  `type_id` int(12) unsigned NOT NULL,
  `type_scope` enum('organisation','global') NOT NULL DEFAULT 'global',
  PRIMARY KEY (`cttemplate_id`)
) ENGINE=MyISAM AUTO_INCREMENT=17 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `community_type_templates` VALUES (1,1,1,'global'),(2,2,1,'global'),(3,3,1,'global'),(4,4,1,'global'),(5,5,1,'global'),(6,5,2,'global'),(7,1,1,'organisation'),(8,2,1,'organisation'),(9,3,1,'organisation'),(10,4,1,'organisation'),(11,5,1,'organisation'),(12,5,2,'organisation'),(13,4,3,'global'),(14,3,3,'global'),(15,4,3,'organisation'),(16,3,3,'organisation');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_audience` (
  `caudience_id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `audience_type` enum('proxy_id','group_id') NOT NULL,
  `audience_value` int(11) NOT NULL,
  `cperiod_id` int(11) NOT NULL,
  `ldap_sync_date` bigint(64) NOT NULL DEFAULT '0',
  `enroll_start` bigint(20) NOT NULL,
  `enroll_finish` bigint(20) NOT NULL,
  `audience_active` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`caudience_id`),
  KEY `course_id` (`course_id`),
  KEY `audience_type` (`audience_type`),
  KEY `audience_value` (`audience_value`),
  KEY `audience_active` (`audience_active`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_contacts` (
  `contact_id` int(12) NOT NULL AUTO_INCREMENT,
  `course_id` int(12) NOT NULL DEFAULT '0',
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `contact_type` varchar(15) NOT NULL DEFAULT 'director',
  `contact_order` int(2) NOT NULL DEFAULT '0',
  PRIMARY KEY (`contact_id`),
  KEY `course_id` (`course_id`),
  KEY `proxy_id` (`proxy_id`),
  KEY `contact_type` (`contact_type`),
  KEY `contact_order` (`contact_order`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_files` (
  `id` int(12) NOT NULL AUTO_INCREMENT,
  `course_id` int(12) NOT NULL DEFAULT '0',
  `required` int(1) NOT NULL DEFAULT '0',
  `timeframe` varchar(64) NOT NULL DEFAULT 'none',
  `file_category` varchar(32) NOT NULL DEFAULT 'other',
  `file_type` varchar(255) NOT NULL,
  `file_size` varchar(32) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_title` varchar(128) NOT NULL,
  `file_notes` longtext NOT NULL,
  `valid_from` bigint(64) NOT NULL DEFAULT '0',
  `valid_until` bigint(64) NOT NULL DEFAULT '0',
  `access_method` int(1) NOT NULL DEFAULT '0',
  `accesses` int(12) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `course_id` (`course_id`),
  KEY `required` (`required`),
  KEY `valid_from` (`valid_from`),
  KEY `valid_until` (`valid_until`),
  KEY `access_method` (`access_method`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_group_audience` (
  `cgaudience_id` int(11) NOT NULL AUTO_INCREMENT,
  `cgroup_id` int(11) NOT NULL,
  `proxy_id` int(11) NOT NULL,
  `entrada_only` int(1) DEFAULT '0',
  `start_date` bigint(64) NOT NULL,
  `finish_date` bigint(64) NOT NULL,
  `active` int(1) NOT NULL,
  PRIMARY KEY (`cgaudience_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_group_contacts` (
  `cgcontact_id` int(12) NOT NULL AUTO_INCREMENT,
  `cgroup_id` int(12) NOT NULL DEFAULT '0',
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `contact_order` int(6) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cgcontact_id`),
  UNIQUE KEY `event_id_2` (`cgroup_id`,`proxy_id`),
  KEY `contact_order` (`contact_order`),
  KEY `event_id` (`cgroup_id`),
  KEY `proxy_id` (`proxy_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_groups` (
  `cgroup_id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(11) NOT NULL,
  `cperiod_id` int(11) DEFAULT NULL,
  `group_name` varchar(30) NOT NULL,
  `active` int(1) DEFAULT NULL,
  PRIMARY KEY (`cgroup_id`),
  KEY `course_id` (`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_keywords` (
  `ckeyword_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int(12) NOT NULL,
  `keyword_id` varchar(12) NOT NULL DEFAULT '',
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(12) NOT NULL,
  PRIMARY KEY (`ckeyword_id`),
  KEY `course_id` (`course_id`),
  KEY `keyword_id` (`keyword_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_links` (
  `id` int(12) NOT NULL AUTO_INCREMENT,
  `course_id` int(12) NOT NULL DEFAULT '0',
  `required` int(1) NOT NULL DEFAULT '0',
  `proxify` int(1) NOT NULL DEFAULT '0',
  `timeframe` varchar(64) NOT NULL DEFAULT 'none',
  `link` text NOT NULL,
  `link_title` varchar(255) NOT NULL,
  `link_notes` text NOT NULL,
  `valid_from` bigint(64) NOT NULL DEFAULT '0',
  `valid_until` bigint(64) NOT NULL DEFAULT '0',
  `accesses` int(12) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `course_id` (`course_id`),
  KEY `required` (`required`),
  KEY `valid_from` (`valid_from`),
  KEY `valid_until` (`valid_until`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_lti_consumers` (
  `id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int(12) NOT NULL,
  `is_required` int(1) NOT NULL DEFAULT '0',
  `valid_from` bigint(64) NOT NULL,
  `valid_until` bigint(64) NOT NULL,
  `launch_url` text NOT NULL,
  `lti_key` varchar(300) NOT NULL,
  `lti_secret` varchar(300) NOT NULL,
  `lti_params` text NOT NULL,
  `lti_title` varchar(300) NOT NULL,
  `lti_notes` text NOT NULL,
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(12) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_lu_reports` (
  `course_report_id` int(12) NOT NULL AUTO_INCREMENT,
  `course_report_title` varchar(250) NOT NULL DEFAULT '',
  `section` varchar(250) NOT NULL DEFAULT '',
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(12) NOT NULL,
  PRIMARY KEY (`course_report_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `course_lu_reports` VALUES (1,'Report Card','report-card',1449685603,1),(2,'My Teachers','my-teachers',1449685603,1);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_objectives` (
  `cobjective_id` int(12) NOT NULL AUTO_INCREMENT,
  `course_id` int(12) NOT NULL DEFAULT '0',
  `objective_id` int(12) NOT NULL DEFAULT '0',
  `importance` int(2) NOT NULL DEFAULT '1',
  `objective_type` enum('event','course') DEFAULT 'course',
  `objective_details` text,
  `objective_start` int(12) DEFAULT NULL,
  `objective_finish` int(12) DEFAULT NULL,
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(12) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`cobjective_id`),
  KEY `course_id` (`course_id`),
  KEY `objective_id` (`objective_id`),
  FULLTEXT KEY `ft_objective_details` (`objective_details`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_report_organisations` (
  `crorganisation_id` int(12) NOT NULL AUTO_INCREMENT,
  `organisation_id` int(12) NOT NULL DEFAULT '0',
  `course_report_id` int(12) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(12) NOT NULL,
  PRIMARY KEY (`crorganisation_id`),
  KEY `organisation_id` (`organisation_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `course_report_organisations` VALUES (1,1,1,1449685603,1),(2,1,2,1449685603,1);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_reports` (
  `creport_id` int(12) NOT NULL AUTO_INCREMENT,
  `course_id` int(12) NOT NULL DEFAULT '0',
  `course_report_id` int(12) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(12) NOT NULL,
  PRIMARY KEY (`creport_id`),
  KEY `course_id` (`course_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_syllabi` (
  `syllabus_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `course_id` int(12) DEFAULT NULL,
  `syllabus_start` smallint(2) DEFAULT NULL,
  `syllabus_finish` smallint(2) DEFAULT NULL,
  `template` varchar(255) DEFAULT NULL,
  `repeat` tinyint(1) DEFAULT '1',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`syllabus_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_tracks` (
  `curriculum_track_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `track_mandatory` int(1) DEFAULT '0',
  PRIMARY KEY (`curriculum_track_id`,`course_id`),
  UNIQUE KEY `curriculum_track_id` (`curriculum_track_id`,`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `courses` (
  `course_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `organisation_id` int(12) NOT NULL DEFAULT '0',
  `curriculum_type_id` int(12) unsigned NOT NULL DEFAULT '0',
  `parent_id` int(12) NOT NULL DEFAULT '0',
  `pcoord_id` int(12) unsigned NOT NULL DEFAULT '0',
  `evalrep_id` int(12) unsigned NOT NULL DEFAULT '0',
  `studrep_id` int(12) unsigned NOT NULL DEFAULT '0',
  `course_name` varchar(85) NOT NULL DEFAULT '',
  `course_code` varchar(16) NOT NULL DEFAULT '',
  `course_credit` decimal(10,1) DEFAULT NULL,
  `course_description` text,
  `course_mandatory` int(1) NOT NULL DEFAULT '0',
  `course_objectives` text,
  `course_url` text,
  `course_redirect` tinyint(1) NOT NULL DEFAULT '0',
  `course_message` text NOT NULL,
  `permission` enum('open','closed') NOT NULL DEFAULT 'closed',
  `sync_ldap` int(1) NOT NULL DEFAULT '0',
  `sync_ldap_courses` text,
  `sync_groups` tinyint(1) NOT NULL DEFAULT '0',
  `notifications` int(1) NOT NULL DEFAULT '1',
  `course_active` int(1) NOT NULL DEFAULT '1',
  `course_twitter_handle` varchar(16) DEFAULT NULL,
  `course_twitter_hashtags` text,
  `course_color` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`course_id`),
  KEY `notifications` (`notifications`),
  KEY `pcoord_id` (`pcoord_id`),
  KEY `evalrep_id` (`evalrep_id`),
  KEY `studrep_id` (`studrep_id`),
  KEY `parent_id` (`parent_id`),
  KEY `curriculum_type_id` (`curriculum_type_id`),
  KEY `course_code` (`course_code`),
  KEY `course_active` (`course_active`),
  FULLTEXT KEY `course_description` (`course_description`),
  FULLTEXT KEY `course_objectives` (`course_objectives`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cron_community_notifications` (
  `ccnotification_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `cnotification_id` int(12) NOT NULL,
  `proxy_id` int(12) NOT NULL,
  PRIMARY KEY (`ccnotification_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `curriculum_level_organisation` (
  `cl_org_id` int(12) NOT NULL AUTO_INCREMENT,
  `org_id` int(12) NOT NULL,
  `curriculum_level_id` int(11) NOT NULL,
  PRIMARY KEY (`cl_org_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `curriculum_level_organisation` VALUES (1,1,1),(2,1,2);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `curriculum_lu_levels` (
  `curriculum_level_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `curriculum_level` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`curriculum_level_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `curriculum_lu_levels` VALUES (1,'Undergraduate'),(2,'Postgraduate');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `curriculum_lu_track_organisations` (
  `curriculum_track_id` int(11) NOT NULL,
  `organisation_id` int(11) NOT NULL,
  PRIMARY KEY (`curriculum_track_id`,`organisation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `curriculum_lu_tracks` (
  `curriculum_track_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `curriculum_track_name` varchar(60) NOT NULL,
  `curriculum_track_description` text,
  `curriculum_track_url` varchar(255) DEFAULT NULL,
  `curriculum_track_order` int(1) NOT NULL DEFAULT '0',
  `created_date` bigint(64) NOT NULL,
  `created_by` int(11) NOT NULL,
  `updated_date` bigint(64) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `deleted_date` bigint(64) DEFAULT NULL,
  PRIMARY KEY (`curriculum_track_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `curriculum_lu_types` (
  `curriculum_type_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(12) unsigned NOT NULL DEFAULT '0',
  `curriculum_type_name` varchar(60) NOT NULL,
  `curriculum_type_description` text,
  `curriculum_type_order` int(12) unsigned NOT NULL DEFAULT '0',
  `curriculum_type_active` int(1) unsigned NOT NULL DEFAULT '1',
  `curriculum_level_id` int(12) DEFAULT NULL,
  `updated_date` bigint(64) unsigned NOT NULL,
  `updated_by` int(12) unsigned NOT NULL,
  PRIMARY KEY (`curriculum_type_id`),
  KEY `curriculum_type_order` (`curriculum_type_order`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `curriculum_lu_types` VALUES (1,0,'Term 1',NULL,0,1,NULL,1250538588,1),(2,0,'Term 2',NULL,1,1,NULL,1250538588,1),(3,0,'Term 3',NULL,2,1,NULL,1250538588,1),(4,0,'Term 4',NULL,3,1,NULL,1250538588,1),(5,0,'Term 5',NULL,4,1,NULL,1250538588,1),(6,0,'Term 6',NULL,5,1,NULL,1250538588,1),(7,0,'Term 7',NULL,6,1,NULL,1250538588,1),(8,0,'Term 8',NULL,7,1,NULL,1250538588,1);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `curriculum_periods` (
  `cperiod_id` int(11) NOT NULL AUTO_INCREMENT,
  `curriculum_type_id` int(11) NOT NULL,
  `curriculum_period_title` varchar(200) DEFAULT '',
  `start_date` bigint(64) NOT NULL,
  `finish_date` bigint(64) NOT NULL,
  `active` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`cperiod_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `curriculum_type_organisation` (
  `curriculum_type_id` int(11) NOT NULL,
  `organisation_id` int(11) NOT NULL,
  PRIMARY KEY (`curriculum_type_id`,`organisation_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `curriculum_type_organisation` VALUES (1,1),(2,1),(3,1),(4,1),(5,1),(6,1),(7,1),(8,1);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `draft_audience` (
  `daudience_id` int(12) NOT NULL AUTO_INCREMENT,
  `eaudience_id` int(12) NOT NULL,
  `devent_id` int(12) NOT NULL DEFAULT '0',
  `event_id` int(12) NOT NULL DEFAULT '0',
  `audience_type` enum('proxy_id','grad_year','cohort','organisation_id','group_id','course_id') NOT NULL,
  `audience_value` varchar(16) NOT NULL,
  `custom_time` int(1) DEFAULT '0',
  `custom_time_start` bigint(64) DEFAULT '0',
  `custom_time_end` bigint(64) DEFAULT '0',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`daudience_id`),
  KEY `eaudience_id` (`eaudience_id`),
  KEY `event_id` (`event_id`),
  KEY `target_value` (`audience_value`),
  KEY `target_type` (`audience_type`),
  KEY `event_id_2` (`event_id`,`audience_type`,`audience_value`),
  KEY `audience_type` (`audience_type`,`audience_value`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `draft_contacts` (
  `dcontact_id` int(12) NOT NULL AUTO_INCREMENT,
  `econtact_id` int(12) DEFAULT NULL,
  `devent_id` int(12) NOT NULL DEFAULT '0',
  `event_id` int(12) NOT NULL DEFAULT '0',
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `contact_role` enum('teacher','tutor','ta','auditor') NOT NULL,
  `contact_order` int(6) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`dcontact_id`),
  KEY `econtact_id` (`econtact_id`),
  KEY `contact_order` (`contact_order`),
  KEY `event_id` (`event_id`),
  KEY `proxy_id` (`proxy_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `draft_creators` (
  `create_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `draft_id` int(11) NOT NULL,
  `proxy_id` int(11) NOT NULL,
  PRIMARY KEY (`create_id`),
  KEY `DRAFT` (`draft_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `draft_events` (
  `devent_id` int(12) NOT NULL AUTO_INCREMENT,
  `event_id` int(12) DEFAULT NULL,
  `draft_id` int(11) DEFAULT NULL,
  `parent_id` int(12) DEFAULT NULL,
  `event_children` int(12) DEFAULT NULL,
  `recurring_id` int(12) DEFAULT '0',
  `region_id` int(12) DEFAULT '0',
  `course_id` int(12) NOT NULL DEFAULT '0',
  `event_phase` varchar(12) DEFAULT NULL,
  `event_title` varchar(255) NOT NULL,
  `event_description` text,
  `include_parent_description` tinyint(1) NOT NULL DEFAULT '1',
  `event_goals` text,
  `event_objectives` text,
  `objectives_release_date` bigint(64) DEFAULT '0',
  `event_message` text,
  `include_parent_message` tinyint(1) NOT NULL DEFAULT '1',
  `event_location` varchar(64) DEFAULT NULL,
  `room_id` int(11) unsigned DEFAULT NULL,
  `event_start` bigint(64) NOT NULL,
  `event_finish` bigint(64) NOT NULL,
  `event_duration` int(64) NOT NULL,
  `attendance_required` tinyint(1) DEFAULT '1',
  `audience_visible` tinyint(1) NOT NULL DEFAULT '1',
  `release_date` bigint(64) NOT NULL,
  `release_until` bigint(64) NOT NULL,
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(12) NOT NULL,
  PRIMARY KEY (`devent_id`),
  KEY `event_id` (`event_id`),
  KEY `course_id` (`course_id`),
  KEY `region_id` (`region_id`),
  KEY `recurring_id` (`recurring_id`),
  KEY `release_date` (`release_date`,`release_until`),
  KEY `event_start` (`event_start`,`event_duration`),
  KEY `event_start_2` (`event_start`,`event_finish`),
  KEY `event_phase` (`event_phase`),
  KEY `event_start_3` (`event_start`,`event_finish`,`release_date`,`release_until`),
  KEY `parent_id` (`parent_id`),
  FULLTEXT KEY `event_title` (`event_title`,`event_description`,`event_goals`,`event_objectives`,`event_message`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `draft_eventtypes` (
  `deventtype_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `eeventtype_id` int(12) DEFAULT NULL,
  `devent_id` int(12) NOT NULL,
  `event_id` int(12) DEFAULT NULL,
  `eventtype_id` int(12) NOT NULL,
  `duration` int(12) NOT NULL,
  `order` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`deventtype_id`),
  KEY `eeventtype_id` (`eeventtype_id`),
  KEY `event_id` (`devent_id`),
  KEY `eventtype_id` (`eventtype_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `draft_options` (
  `draft_id` int(11) NOT NULL,
  `option` varchar(255) NOT NULL DEFAULT '',
  `value` varchar(30) NOT NULL DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `drafts` (
  `draft_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `status` text,
  `name` text,
  `description` text,
  `created` int(11) DEFAULT NULL,
  `preserve_elements` binary(4) DEFAULT NULL,
  PRIMARY KEY (`draft_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluation_contacts` (
  `econtact_id` int(12) NOT NULL AUTO_INCREMENT,
  `evaluation_id` int(12) NOT NULL DEFAULT '0',
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `contact_role` enum('reviewer','tutor','author') NOT NULL DEFAULT 'reviewer',
  `contact_order` int(6) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`econtact_id`),
  UNIQUE KEY `event_id_2` (`evaluation_id`,`proxy_id`),
  KEY `contact_order` (`contact_order`),
  KEY `event_id` (`evaluation_id`),
  KEY `proxy_id` (`proxy_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluation_evaluator_exclusions` (
  `eeexclusion_id` int(12) NOT NULL AUTO_INCREMENT,
  `evaluation_id` int(12) NOT NULL DEFAULT '0',
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`eeexclusion_id`),
  UNIQUE KEY `event_id_2` (`evaluation_id`,`proxy_id`),
  KEY `event_id` (`evaluation_id`),
  KEY `proxy_id` (`proxy_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluation_evaluators` (
  `eevaluator_id` int(12) NOT NULL AUTO_INCREMENT,
  `evaluation_id` int(12) NOT NULL,
  `evaluator_type` enum('proxy_id','grad_year','cohort','organisation_id','cgroup_id') NOT NULL DEFAULT 'proxy_id',
  `evaluator_value` int(12) NOT NULL,
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(12) NOT NULL,
  PRIMARY KEY (`eevaluator_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluation_form_contacts` (
  `econtact_id` int(12) NOT NULL AUTO_INCREMENT,
  `eform_id` int(12) NOT NULL DEFAULT '0',
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `contact_role` enum('reviewer','author') NOT NULL DEFAULT 'author',
  `contact_order` int(6) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`econtact_id`),
  UNIQUE KEY `event_id_2` (`eform_id`,`proxy_id`),
  KEY `contact_order` (`contact_order`),
  KEY `event_id` (`eform_id`),
  KEY `proxy_id` (`proxy_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluation_form_question_objectives` (
  `efqobjective_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `efquestion_id` int(12) NOT NULL,
  `objective_id` int(12) NOT NULL,
  `updated_date` bigint(64) DEFAULT NULL,
  `updated_by` int(12) DEFAULT NULL,
  PRIMARY KEY (`efqobjective_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluation_form_questions` (
  `efquestion_id` int(12) NOT NULL AUTO_INCREMENT,
  `eform_id` int(121) NOT NULL,
  `equestion_id` int(12) NOT NULL,
  `question_order` tinyint(3) NOT NULL DEFAULT '0',
  `allow_comments` tinyint(1) NOT NULL DEFAULT '1',
  `send_threshold_notifications` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`efquestion_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluation_forms` (
  `eform_id` int(12) NOT NULL AUTO_INCREMENT,
  `organisation_id` int(12) unsigned NOT NULL DEFAULT '0',
  `target_id` int(12) NOT NULL,
  `form_parent` int(12) NOT NULL,
  `form_title` varchar(64) NOT NULL,
  `form_description` text NOT NULL,
  `form_active` tinyint(1) NOT NULL,
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(12) NOT NULL,
  PRIMARY KEY (`eform_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluation_progress` (
  `eprogress_id` int(12) NOT NULL AUTO_INCREMENT,
  `evaluation_id` int(12) NOT NULL,
  `etarget_id` int(12) NOT NULL,
  `target_record_id` int(11) DEFAULT NULL,
  `proxy_id` int(12) NOT NULL,
  `progress_value` enum('inprogress','complete','cancelled') NOT NULL DEFAULT 'inprogress',
  `updated_date` int(11) NOT NULL,
  `updated_by` int(11) NOT NULL,
  PRIMARY KEY (`eprogress_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluation_progress_clerkship_events` (
  `epcevent_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `eprogress_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `preceptor_proxy_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`epcevent_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluation_progress_patient_encounters` (
  `eppencounter_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `encounter_name` varchar(255) DEFAULT NULL,
  `encounter_complexity` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`eppencounter_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluation_question_objectives` (
  `eqobjective_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `equestion_id` int(12) NOT NULL,
  `objective_id` int(12) NOT NULL,
  `updated_date` bigint(64) DEFAULT NULL,
  `updated_by` int(12) DEFAULT NULL,
  PRIMARY KEY (`eqobjective_id`),
  KEY `equestion_id` (`equestion_id`),
  KEY `objective_id` (`objective_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluation_question_response_descriptors` (
  `eqrdescriptor_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `eqresponse_id` int(12) unsigned NOT NULL,
  `erdescriptor_id` int(12) unsigned NOT NULL,
  PRIMARY KEY (`eqrdescriptor_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluation_requests` (
  `erequest_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `request_expires` bigint(64) NOT NULL DEFAULT '0',
  `request_code` varchar(255) DEFAULT NULL,
  `evaluation_id` int(11) DEFAULT NULL,
  `proxy_id` int(11) DEFAULT NULL,
  `target_proxy_id` int(11) DEFAULT NULL,
  `request_created` bigint(64) NOT NULL DEFAULT '0',
  `request_fulfilled` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`erequest_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluation_responses` (
  `eresponse_id` int(12) NOT NULL AUTO_INCREMENT,
  `eprogress_id` int(12) NOT NULL,
  `eform_id` int(12) NOT NULL,
  `proxy_id` int(12) NOT NULL,
  `efquestion_id` int(12) NOT NULL,
  `eqresponse_id` int(12) NOT NULL,
  `comments` text,
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(12) NOT NULL,
  PRIMARY KEY (`eresponse_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluation_rubric_questions` (
  `efrquestion_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `erubric_id` int(11) DEFAULT NULL,
  `equestion_id` int(11) DEFAULT NULL,
  `question_order` int(3) NOT NULL DEFAULT '0',
  PRIMARY KEY (`efrquestion_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluation_targets` (
  `etarget_id` int(12) NOT NULL AUTO_INCREMENT,
  `evaluation_id` int(12) NOT NULL,
  `target_id` int(11) NOT NULL,
  `target_value` int(12) NOT NULL,
  `target_type` varchar(24) NOT NULL DEFAULT 'course_id',
  `target_active` tinyint(1) NOT NULL DEFAULT '1',
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(12) NOT NULL,
  PRIMARY KEY (`etarget_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluations` (
  `evaluation_id` int(12) NOT NULL AUTO_INCREMENT,
  `eform_id` int(12) NOT NULL,
  `organisation_id` int(12) unsigned NOT NULL DEFAULT '0',
  `evaluation_title` varchar(128) NOT NULL,
  `evaluation_description` text NOT NULL,
  `evaluation_active` tinyint(1) NOT NULL,
  `evaluation_start` bigint(64) NOT NULL,
  `evaluation_finish` bigint(64) NOT NULL,
  `evaluation_completions` int(12) NOT NULL DEFAULT '0',
  `min_submittable` tinyint(3) NOT NULL DEFAULT '1',
  `max_submittable` tinyint(3) NOT NULL DEFAULT '1',
  `evaluation_mandatory` tinyint(1) NOT NULL DEFAULT '1',
  `allow_target_review` tinyint(1) NOT NULL DEFAULT '0',
  `allow_target_request` tinyint(1) NOT NULL DEFAULT '0',
  `allow_repeat_targets` tinyint(1) NOT NULL DEFAULT '0',
  `show_comments` tinyint(1) NOT NULL DEFAULT '0',
  `identify_comments` tinyint(1) NOT NULL DEFAULT '0',
  `require_requests` tinyint(1) NOT NULL DEFAULT '0',
  `require_request_code` tinyint(1) NOT NULL DEFAULT '0',
  `request_timeout` bigint(64) NOT NULL DEFAULT '0',
  `threshold_notifications_type` enum('reviewers','tutors','directors','pcoordinators','authors','disabled') NOT NULL DEFAULT 'disabled',
  `release_date` bigint(64) NOT NULL,
  `release_until` bigint(64) NOT NULL,
  `updated_date` bigint(64) NOT NULL,
  `updated_by` bigint(64) NOT NULL,
  PRIMARY KEY (`evaluation_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluations_lu_question_response_criteria` (
  `eqrcriteria_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `eqresponse_id` int(11) DEFAULT NULL,
  `criteria_text` text,
  PRIMARY KEY (`eqrcriteria_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluations_lu_question_responses` (
  `eqresponse_id` int(12) NOT NULL AUTO_INCREMENT,
  `efresponse_id` int(12) NOT NULL,
  `equestion_id` int(12) NOT NULL,
  `response_text` longtext NOT NULL,
  `response_order` tinyint(3) NOT NULL DEFAULT '0',
  `response_is_html` tinyint(1) NOT NULL DEFAULT '0',
  `minimum_passing_level` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`eqresponse_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluations_lu_questions` (
  `equestion_id` int(12) NOT NULL AUTO_INCREMENT,
  `organisation_id` int(12) unsigned NOT NULL DEFAULT '0',
  `efquestion_id` int(12) NOT NULL DEFAULT '0',
  `question_parent_id` int(12) NOT NULL DEFAULT '0',
  `questiontype_id` int(12) NOT NULL,
  `question_code` varchar(48) DEFAULT NULL,
  `question_text` longtext NOT NULL,
  `question_description` longtext,
  `allow_comments` tinyint(1) NOT NULL DEFAULT '1',
  `question_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`equestion_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluations_lu_questiontypes` (
  `questiontype_id` int(12) NOT NULL AUTO_INCREMENT,
  `questiontype_shortname` varchar(32) NOT NULL,
  `questiontype_title` varchar(64) NOT NULL,
  `questiontype_description` text NOT NULL,
  `questiontype_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`questiontype_id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `evaluations_lu_questiontypes` VALUES (1,'matrix_single','Horizontal Choice Matrix (single response)','The rating scale allows evaluators to rate each question based on the scale you provide (i.e. 1 = Not Demonstrated, 2 = Needs Improvement, 3 = Satisfactory, 4 = Above Average).',1),(2,'descriptive_text','Descriptive Text','Allows you to add descriptive text information to your evaluation form. This could be instructions or other details relevant to the question or series of questions.',1),(3,'rubric','Rubric','The rating scale allows evaluators to rate each question based on the scale you provide, while also providing a short description of the requirements to meet each level on the scale (i.e. Level 1 to 4 of \\\"Professionalism\\\" for an assignment are qualified with what traits the learner is expected to show to meet each level, and while the same scale is used for \\\"Collaborator\\\", the requirements at each level are defined differently).',1),(4,'free_text','Free Text Comments','Allows the user to be asked for a simple free-text response. This can be used to get additional details about prior questions, or to simply ask for any comments from the evaluator regarding a specific topic.',1),(5,'selectbox','Drop Down (single response)','The dropdown allows evaluators to answer each question by choosing one of up to 100 options which have been provided to populate a select box.',1),(6,'vertical_matrix','Vertical Choice Matrix (single response)','The rating scale allows evaluators to rate each question based on the scale you provide (i.e. 1 = Not Demonstrated, 2 = Needs Improvement, 3 = Satisfactory, 4 = Above Average).',1);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluations_lu_response_descriptors` (
  `erdescriptor_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `organisation_id` int(12) unsigned NOT NULL,
  `descriptor` varchar(256) NOT NULL DEFAULT '',
  `reportable` tinyint(1) NOT NULL DEFAULT '1',
  `order` int(12) NOT NULL,
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(12) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`erdescriptor_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `evaluations_lu_response_descriptors` VALUES (1,1,'Opportunities for Growth',1,1,1449685604,1,1),(2,1,'Developing',1,2,1449685604,1,1),(3,1,'Achieving',1,3,1449685604,1,1),(4,1,'Not Applicable',0,4,1449685604,1,1);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluations_lu_rubrics` (
  `erubric_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `rubric_title` varchar(32) DEFAULT NULL,
  `rubric_description` text,
  `efrubric_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`erubric_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluations_lu_targets` (
  `target_id` int(11) NOT NULL AUTO_INCREMENT,
  `target_shortname` varchar(32) NOT NULL,
  `target_title` varchar(64) NOT NULL,
  `target_description` text NOT NULL,
  `target_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`target_id`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `evaluations_lu_targets` VALUES (1,'course','Course Evaluation','',1),(2,'teacher','Teacher Evaluation','',1),(3,'student','Student Assessment','',1),(4,'rotation_core','Clerkship Core Rotation Evaluation','',1),(5,'rotation_elective','Clerkship Elective Rotation Evaluation','',1),(6,'preceptor','Clerkship Preceptor Evaluation','',1),(7,'peer','Peer Assessment','',1),(8,'self','Self Assessment','',1),(9,'resident','Patient Encounter Assessment','',1);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluations_related_questions` (
  `erubric_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `related_equestion_id` int(11) unsigned NOT NULL,
  `equestion_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`erubric_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_attendance` (
  `eattendance_id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL,
  `proxy_id` int(11) NOT NULL,
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(11) NOT NULL,
  PRIMARY KEY (`eattendance_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_audience` (
  `eaudience_id` int(12) NOT NULL AUTO_INCREMENT,
  `event_id` int(12) NOT NULL DEFAULT '0',
  `audience_type` enum('proxy_id','grad_year','cohort','organisation_id','group_id','course_id') NOT NULL,
  `audience_value` varchar(16) NOT NULL,
  `custom_time` int(1) DEFAULT '0',
  `custom_time_start` bigint(64) DEFAULT '0',
  `custom_time_end` bigint(64) DEFAULT '0',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`eaudience_id`),
  KEY `event_id` (`event_id`),
  KEY `target_value` (`audience_value`),
  KEY `target_type` (`audience_type`),
  KEY `event_id_2` (`event_id`,`audience_type`,`audience_value`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_contacts` (
  `econtact_id` int(12) NOT NULL AUTO_INCREMENT,
  `event_id` int(12) NOT NULL DEFAULT '0',
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `contact_role` enum('teacher','tutor','ta','auditor') NOT NULL,
  `contact_order` int(6) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`econtact_id`),
  UNIQUE KEY `event_id_2` (`event_id`,`proxy_id`),
  KEY `contact_order` (`contact_order`),
  KEY `event_id` (`event_id`),
  KEY `proxy_id` (`proxy_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_discussions` (
  `ediscussion_id` int(12) NOT NULL AUTO_INCREMENT,
  `event_id` int(12) NOT NULL DEFAULT '0',
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `parent_id` int(12) NOT NULL DEFAULT '0',
  `discussion_title` varchar(128) NOT NULL,
  `discussion_comment` text NOT NULL,
  `discussion_active` int(1) NOT NULL DEFAULT '1',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ediscussion_id`),
  KEY `event_id` (`event_id`),
  KEY `proxy_id` (`proxy_id`),
  KEY `parent_id` (`parent_id`),
  FULLTEXT KEY `discussion_title` (`discussion_title`,`discussion_comment`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_eventtypes` (
  `eeventtype_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` int(12) NOT NULL,
  `eventtype_id` int(12) NOT NULL,
  `duration` int(12) NOT NULL,
  PRIMARY KEY (`eeventtype_id`),
  KEY `event_id` (`event_id`),
  KEY `eventtype_id` (`eventtype_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_files` (
  `efile_id` int(12) NOT NULL AUTO_INCREMENT,
  `event_id` int(12) NOT NULL DEFAULT '0',
  `required` int(1) NOT NULL DEFAULT '0',
  `timeframe` varchar(64) NOT NULL DEFAULT 'none',
  `file_category` varchar(32) NOT NULL DEFAULT 'other',
  `file_type` varchar(255) NOT NULL,
  `file_size` varchar(32) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_title` varchar(128) NOT NULL,
  `file_notes` longtext NOT NULL,
  `access_method` int(1) NOT NULL DEFAULT '0',
  `accesses` int(12) NOT NULL DEFAULT '0',
  `release_date` bigint(64) NOT NULL DEFAULT '0',
  `release_until` bigint(64) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`efile_id`),
  KEY `required` (`required`),
  KEY `access_method` (`access_method`),
  KEY `event_id` (`event_id`),
  KEY `release_date` (`release_date`,`release_until`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_history` (
  `ehistory_id` int(12) NOT NULL AUTO_INCREMENT,
  `event_id` int(12) DEFAULT '0',
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `history_message` text NOT NULL,
  `history_display` int(1) NOT NULL DEFAULT '0',
  `history_timestamp` bigint(64) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ehistory_id`),
  KEY `history_timestamp` (`history_timestamp`),
  KEY `event_id` (`event_id`),
  KEY `proxy_id` (`proxy_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_keywords` (
  `ekeyword_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` int(12) NOT NULL,
  `keyword_id` varchar(12) NOT NULL DEFAULT '',
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(12) NOT NULL,
  PRIMARY KEY (`ekeyword_id`),
  KEY `event_id` (`event_id`),
  KEY `keyword_id` (`keyword_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_links` (
  `elink_id` int(12) NOT NULL AUTO_INCREMENT,
  `event_id` int(12) NOT NULL DEFAULT '0',
  `required` int(1) NOT NULL DEFAULT '0',
  `timeframe` varchar(64) NOT NULL DEFAULT 'none',
  `proxify` int(1) NOT NULL DEFAULT '0',
  `link` text NOT NULL,
  `link_title` varchar(255) NOT NULL,
  `link_notes` text NOT NULL,
  `accesses` int(12) NOT NULL DEFAULT '0',
  `release_date` bigint(64) NOT NULL DEFAULT '0',
  `release_until` bigint(64) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`elink_id`),
  KEY `lecture_id` (`event_id`),
  KEY `required` (`required`),
  KEY `release_date` (`release_date`,`release_until`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_lti_consumers` (
  `id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` int(12) NOT NULL,
  `required` int(1) NOT NULL,
  `valid_from` bigint(64) NOT NULL,
  `valid_until` bigint(64) NOT NULL,
  `timeframe` varchar(64) NOT NULL,
  `launch_url` text NOT NULL,
  `lti_key` varchar(300) NOT NULL,
  `lti_secret` varchar(300) NOT NULL,
  `lti_params` text NOT NULL,
  `lti_title` varchar(300) NOT NULL,
  `lti_notes` text NOT NULL,
  `release_date` int(12) NOT NULL DEFAULT '0',
  `release_until` int(12) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(12) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_lu_resource_types` (
  `event_resource_type_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `resource_type` varchar(100) DEFAULT NULL,
  `description` text,
  `updated_date` int(12) NOT NULL,
  `updated_by` int(12) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`event_resource_type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `event_lu_resource_types` VALUES (1,'Podcast','Attach a podcast to this learning event.',1449685604,1,1),(2,'Bring to Class','Attach a description of materials students should bring to class.',1449685604,1,0),(3,'Link','Attach links to external websites that relate to the learning event.',1449685604,1,1),(4,'Homework','Attach a description to indicate homework tasks assigned to students.',1449685604,1,0),(5,'Lecture Notes','Attach files such as documents, pdfs or images.',1449685604,1,1),(6,'Lecture Slides','Attach files such as documents, powerpoint files, pdfs or images.',1449685604,1,1),(7,'Online Learning Module','Attach links to external learning modules.',1449685604,1,1),(8,'Quiz','Attach an existing quiz to this learning event.',1449685604,1,1),(9,'Textbook Reading','Attach a reading list related to this learning event.',1449685604,1,0),(10,'LTI Provider','',1449685604,1,0),(11,'Other Files','Attach miscellaneous media files to this learning event.',1449685604,1,1),(12,'Exam','Attach an exam to this learning event.',1492431737,1,1);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_objectives` (
  `eobjective_id` int(12) NOT NULL AUTO_INCREMENT,
  `event_id` int(12) NOT NULL DEFAULT '0',
  `objective_id` int(12) NOT NULL DEFAULT '0',
  `objective_details` text,
  `objective_type` enum('event','course') NOT NULL DEFAULT 'event',
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(12) NOT NULL,
  PRIMARY KEY (`eobjective_id`),
  KEY `event_id` (`event_id`),
  KEY `objective_id` (`objective_id`),
  FULLTEXT KEY `ft_objective_details` (`objective_details`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_related` (
  `erelated_id` int(12) NOT NULL AUTO_INCREMENT,
  `event_id` int(12) NOT NULL DEFAULT '0',
  `related_type` enum('event_id') NOT NULL DEFAULT 'event_id',
  `related_value` varchar(16) NOT NULL,
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`erelated_id`),
  KEY `event_id` (`event_id`),
  KEY `related_type` (`related_type`),
  KEY `related_value` (`related_value`),
  KEY `event_id_2` (`event_id`,`related_type`,`related_value`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_resource_class_work` (
  `event_resource_class_work_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` int(12) NOT NULL,
  `resource_class_work` text,
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `timeframe` enum('none','pre','during','post') NOT NULL DEFAULT 'none',
  `release_required` tinyint(1) NOT NULL DEFAULT '0',
  `release_date` int(12) NOT NULL DEFAULT '0',
  `release_until` int(12) NOT NULL DEFAULT '0',
  `updated_date` int(12) NOT NULL,
  `updated_by` int(12) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`event_resource_class_work_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_resource_entities` (
  `event_resource_entity_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` int(12) NOT NULL,
  `entity_type` int(12) NOT NULL,
  `entity_value` int(12) NOT NULL,
  `release_date` bigint(64) NOT NULL DEFAULT '0',
  `release_until` bigint(64) NOT NULL DEFAULT '0',
  `updated_date` int(12) NOT NULL,
  `updated_by` int(12) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`event_resource_entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_resource_homework` (
  `event_resource_homework_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` int(12) NOT NULL,
  `resource_homework` text,
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `timeframe` enum('none','pre','during','post') NOT NULL DEFAULT 'none',
  `release_required` tinyint(1) NOT NULL DEFAULT '0',
  `release_date` int(12) NOT NULL DEFAULT '0',
  `release_until` int(12) NOT NULL DEFAULT '0',
  `updated_date` int(12) NOT NULL,
  `updated_by` int(12) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`event_resource_homework_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_resource_textbook_reading` (
  `event_resource_textbook_reading_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` int(12) NOT NULL,
  `resource_textbook_reading` text,
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `timeframe` enum('none','pre','during','post') NOT NULL DEFAULT 'none',
  `release_required` tinyint(1) NOT NULL DEFAULT '0',
  `release_date` int(12) NOT NULL DEFAULT '0',
  `release_until` int(12) NOT NULL DEFAULT '0',
  `updated_date` int(12) NOT NULL,
  `updated_by` int(12) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`event_resource_textbook_reading_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_resources` (
  `event_resources_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `fk_resource_id` int(11) NOT NULL,
  `fk_event_id` int(11) NOT NULL,
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(11) NOT NULL,
  PRIMARY KEY (`event_resources_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `event_topics` (
  `etopic_id` int(12) NOT NULL AUTO_INCREMENT,
  `event_id` int(12) NOT NULL DEFAULT '0',
  `topic_id` int(12) NOT NULL DEFAULT '0',
  `topic_coverage` enum('major','minor') NOT NULL,
  `topic_time` varchar(25) DEFAULT NULL,
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(12) NOT NULL,
  PRIMARY KEY (`etopic_id`),
  KEY `event_id` (`event_id`),
  KEY `topic_id` (`topic_id`),
  KEY `topic_coverage` (`topic_coverage`),
  KEY `topic_time` (`topic_time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `events` (
  `event_id` int(12) NOT NULL AUTO_INCREMENT,
  `parent_id` int(12) DEFAULT NULL,
  `event_children` int(12) DEFAULT NULL,
  `recurring_id` int(12) DEFAULT '0',
  `region_id` int(12) DEFAULT '0',
  `course_id` int(12) NOT NULL DEFAULT '0',
  `event_phase` varchar(12) DEFAULT NULL,
  `event_title` varchar(255) NOT NULL,
  `event_description` text,
  `include_parent_description` tinyint(1) NOT NULL DEFAULT '1',
  `event_goals` text,
  `event_objectives` text,
  `keywords_hidden` int(1) DEFAULT '0',
  `keywords_release_date` bigint(64) DEFAULT '0',
  `objectives_release_date` bigint(64) DEFAULT '0',
  `event_message` text,
  `include_parent_message` tinyint(1) NOT NULL DEFAULT '1',
  `event_location` varchar(64) DEFAULT NULL,
  `room_id` int(11) unsigned DEFAULT NULL,
  `event_start` bigint(64) NOT NULL,
  `event_finish` bigint(64) NOT NULL,
  `event_duration` int(64) NOT NULL,
  `attendance_required` tinyint(1) DEFAULT '1',
  `release_date` bigint(64) NOT NULL,
  `release_until` bigint(64) NOT NULL,
  `audience_visible` tinyint(1) NOT NULL DEFAULT '1',
  `event_color` varchar(20) DEFAULT NULL,
  `draft_id` int(11) DEFAULT NULL,
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(12) NOT NULL,
  PRIMARY KEY (`event_id`),
  KEY `course_id` (`course_id`),
  KEY `region_id` (`region_id`),
  KEY `recurring_id` (`recurring_id`),
  KEY `release_date` (`release_date`,`release_until`),
  KEY `event_start` (`event_start`,`event_duration`),
  KEY `event_start_2` (`event_start`,`event_finish`),
  KEY `event_phase` (`event_phase`),
  FULLTEXT KEY `event_title` (`event_title`,`event_description`,`event_goals`,`event_objectives`,`event_message`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `events_lu_eventtypes` (
  `eventtype_id` int(12) NOT NULL AUTO_INCREMENT,
  `eventtype_title` varchar(64) DEFAULT NULL,
  `eventtype_description` text NOT NULL,
  `eventtype_active` int(1) NOT NULL DEFAULT '1',
  `eventtype_order` int(6) NOT NULL,
  `eventtype_default_enrollment` varchar(50) DEFAULT NULL,
  `eventtype_report_calculation` varchar(100) DEFAULT NULL,
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(12) NOT NULL,
  PRIMARY KEY (`eventtype_id`),
  KEY `eventtype_order` (`eventtype_order`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `events_lu_eventtypes` VALUES (1,'Lecture','Faculty member speaks to a whole group of students for the session. Ideally, the lecture is interactive, with brief student activities to apply learning within the talk or presentation. The focus, however, is on the faculty member speaking or presenting to a group of students.',1,0,NULL,NULL,1250877835,1),(2,'Lab','In this session, practical learning, activity and demonstration take place, usually with specialized equipment, materials or methods and related to a class, or unit of teaching.',1,1,NULL,NULL,1250877835,1),(3,'Small Group','In the session, students in small groups work on specific questions, problems, or tasks related to a topic or a case, using discussion and investigation. Faculty member facilitates. May occur in:\r\n<ul>\r\n<li><strong>Expanded Clinical Skills:</strong> demonstrations and practice of clinical approaches and assessments occur with students in small groups of 25 or fewer.</li>\r\n<li><strong>Team Based Learning Method:</strong> students are in pre-selected groups for the term to work on directed activities, often case-based. One-two faculty facilitate with all 100 students in small teams.</li>\r\n<li><strong>Peer Instruction:</strong> students work in partners on specific application activities throughout the session.</li>\r\n<li><strong>Seminars:</strong> Students are in small groups each with a faculty tutor or mentor to facilitate or coach each small group. Students are active in these groups, either sharing new information, working on tasks, cases, or problems. etc. This may include Problem Based Learning as a strategy where students research and explore aspects to solve issues raised by the case with faculty facilitating. Tutorials may also be incorporated here.</li>\r\n<li><strong>Clinical Skills:</strong> Students in the Clinical and Communication Skills courses work in small groups on specific tasks that allow application of clinical skills.</li>\r\n</ul>',1,2,NULL,NULL,1219434863,1),(4,'Patient Contact Session','The focus of the session is on the patient(s) who will be present to answer students\' and/or professor\'s questions and/or to offer a narrative about their life with a condition, or as a member of a specific population. Medical Science Rounds are one example.',1,4,NULL,NULL,1219434863,1),(5,'Symposium / Student Presentation','For one or more hours, a variety of speakers, including students, present on topics to teach about current issues, research, etc.',1,6,NULL,NULL,1219434863,1),(6,'Directed Independent Learning','Students work independently (in groups or on their own) outside of class sessions on specific tasks to acquire knowledge, and develop enquiry and critical evaluation skills, with time allocated into the timetable. Directed Independent Student Learning may include learning through interactive online modules, online quizzes, working on larger independent projects (such as Community Based Projects or Critical Enquiry), or completing reflective, research or other types of papers and reports. While much student independent learning is done on the students own time, for homework, in this case, directed student time is built into the timetable as a specific session and linked directly to other learning in the course.',1,3,NULL,NULL,1219434863,1),(7,'Review / Feedback Session','In this session faculty help students to prepare for future learning and assessment through de-briefing about previous learning in a quiz or assignment, through reviewing a week or more of learning, or through reviewing at the end of a course to prepare for summative examination.',1,5,NULL,NULL,1219434863,1),(8,'Examination','Scheduled course examination time, including mid-term as well as final examinations. <strong>Please Note:</strong> These will be identified only by the Curricular Coordinators in the timetable.',1,7,NULL,NULL,1219434863,1),(9,'Clerkship Seminars','Case-based, small-group sessions emphasizing more advanced and integrative topics. Students draw upon their clerkship experience with patients and healthcare teams to participate and interact with the faculty whose role is to facilitate the discussion.',1,8,NULL,NULL,1250878869,1),(10,'Other','These are sessions that are not a part of the UGME curriculum but are recorded in MEdTech Central. Examples may be: Course Evaluation sessions, MD Management. NOTE: these will be identified only by the Curricular Coordinators in the timetable.',1,9,NULL,NULL,1250878869,1);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `events_lu_objectives` (
  `objective_id` int(12) NOT NULL AUTO_INCREMENT,
  `objective_name` varchar(60) NOT NULL,
  `objective_description` text,
  `objective_order` int(12) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(12) NOT NULL,
  PRIMARY KEY (`objective_id`),
  KEY `objective_order` (`objective_order`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `events_lu_resources` (
  `resource_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `organisation_id` int(11) DEFAULT '0',
  `resource` varchar(250) NOT NULL DEFAULT '',
  `description` text,
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(11) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`resource_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `events_lu_topics` (
  `topic_id` int(12) NOT NULL AUTO_INCREMENT,
  `topic_name` varchar(60) NOT NULL,
  `topic_description` text,
  `topic_type` enum('ed10','ed11','other') NOT NULL DEFAULT 'other',
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(12) NOT NULL,
  PRIMARY KEY (`topic_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `events_recurring` (
  `recurring_id` int(12) NOT NULL AUTO_INCREMENT,
  `recurring_date` bigint(64) NOT NULL,
  `recurring_until` bigint(64) NOT NULL,
  `recurring_type` enum('daily','weekly','monthly','yearly') NOT NULL,
  `recurring_frequency` int(12) NOT NULL,
  `recurring_number` int(12) NOT NULL,
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(12) NOT NULL,
  PRIMARY KEY (`recurring_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eventtype_organisation` (
  `eventtype_id` int(12) NOT NULL,
  `organisation_id` int(12) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `eventtype_organisation` VALUES (1,1),(2,1),(3,1),(4,1),(5,1),(6,1),(7,1),(8,1),(9,1),(10,1);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exam_attached_files` (
  `file_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `exam_id` int(11) unsigned NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_type` varchar(255) DEFAULT NULL,
  `file_title` varchar(128) DEFAULT NULL,
  `file_size` varchar(32) DEFAULT NULL,
  `updated_date` bigint(64) DEFAULT NULL,
  `updated_by` int(12) DEFAULT NULL,
  `deleted_date` int(11) DEFAULT NULL,
  PRIMARY KEY (`file_id`),
  KEY `exam_attached_files_fk_1` (`exam_id`),
  CONSTRAINT `exam_attached_files_fk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`exam_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exam_category` (
  `category_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int(11) unsigned NOT NULL,
  `exam_id` int(11) unsigned NOT NULL,
  `use_release_start_date` int(1) DEFAULT NULL,
  `use_release_end_date` int(1) DEFAULT NULL,
  `release_start_date` bigint(20) DEFAULT NULL,
  `release_end_date` bigint(20) DEFAULT NULL,
  `updated_date` bigint(64) DEFAULT NULL,
  `updated_by` int(12) DEFAULT NULL,
  `deleted_date` int(11) DEFAULT NULL,
  PRIMARY KEY (`category_id`),
  KEY `exam_cat_fk_3` (`exam_id`),
  KEY `exam_cat_fk_4` (`post_id`),
  CONSTRAINT `exam_cat_fk_3` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`exam_id`),
  CONSTRAINT `exam_cat_fk_4` FOREIGN KEY (`post_id`) REFERENCES `exam_posts` (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exam_category_audience` (
  `audience_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(11) unsigned NOT NULL,
  `proxy_id` int(11) unsigned NOT NULL,
  `updated_date` bigint(64) DEFAULT NULL,
  `updated_by` int(12) DEFAULT NULL,
  `deleted_date` int(11) DEFAULT NULL,
  PRIMARY KEY (`audience_id`),
  KEY `exam_cat_fk_6` (`category_id`),
  CONSTRAINT `exam_cat_fk_6` FOREIGN KEY (`category_id`) REFERENCES `exam_category` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exam_category_result` (
  `result_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int(11) unsigned NOT NULL,
  `exam_id` int(11) unsigned NOT NULL,
  `objective_id` int(12) NOT NULL,
  `set_id` int(12) NOT NULL,
  `average` decimal(10,2) DEFAULT NULL,
  `min` decimal(10,2) DEFAULT NULL,
  `max` decimal(10,2) DEFAULT NULL,
  `possible_value` decimal(10,2) DEFAULT NULL,
  `updated_date` bigint(64) DEFAULT NULL,
  `deleted_date` int(11) DEFAULT NULL,
  PRIMARY KEY (`result_id`),
  KEY `exam_cat_fk_1` (`exam_id`),
  KEY `exam_cat_fk_2` (`post_id`),
  CONSTRAINT `exam_cat_fk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`exam_id`),
  CONSTRAINT `exam_cat_fk_2` FOREIGN KEY (`post_id`) REFERENCES `exam_posts` (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exam_category_result_detail` (
  `detail_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `proxy_id` int(11) NOT NULL,
  `exam_progress_id` int(12) unsigned NOT NULL,
  `post_id` int(11) unsigned NOT NULL,
  `exam_id` int(11) unsigned NOT NULL,
  `objective_id` int(12) NOT NULL,
  `set_id` int(12) NOT NULL,
  `score` decimal(10,2) DEFAULT NULL,
  `value` decimal(10,2) DEFAULT NULL,
  `possible_value` decimal(10,2) DEFAULT NULL,
  `updated_date` bigint(64) DEFAULT NULL,
  `updated_by` int(12) DEFAULT NULL,
  `deleted_date` int(11) DEFAULT NULL,
  PRIMARY KEY (`detail_id`),
  KEY `exam_cat_detail_fk_1` (`exam_id`),
  KEY `exam_cat_detail_fk_2` (`post_id`),
  KEY `exam_cat_detail_fk_3` (`exam_progress_id`),
  CONSTRAINT `exam_cat_detail_fk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`exam_id`),
  CONSTRAINT `exam_cat_detail_fk_2` FOREIGN KEY (`post_id`) REFERENCES `exam_posts` (`post_id`),
  CONSTRAINT `exam_cat_detail_fk_3` FOREIGN KEY (`exam_progress_id`) REFERENCES `exam_progress` (`exam_progress_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exam_category_set` (
  `set_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `objective_set_id` int(11) unsigned NOT NULL,
  `category_id` int(11) unsigned NOT NULL,
  `updated_date` bigint(64) DEFAULT NULL,
  `updated_by` int(12) DEFAULT NULL,
  `deleted_date` int(11) DEFAULT NULL,
  PRIMARY KEY (`set_id`),
  KEY `exam_cat_fk_5` (`category_id`),
  CONSTRAINT `exam_cat_fk_5` FOREIGN KEY (`category_id`) REFERENCES `exam_category` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exam_lu_question_bank_folder_images` (
  `image_id` int(12) NOT NULL AUTO_INCREMENT,
  `file_name` varchar(64) NOT NULL DEFAULT '0',
  `color` varchar(64) NOT NULL,
  `order` int(12) NOT NULL,
  `deleted_date` bigint(64) DEFAULT NULL,
  PRIMARY KEY (`image_id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `exam_lu_question_bank_folder_images` VALUES (1,'list-folder-1.png','light blue',1,NULL),(2,'list-folder-2.png','medium blue',2,NULL),(3,'list-folder-3.png','teal',3,NULL),(4,'list-folder-4.png','yellow green',4,NULL),(5,'list-folder-5.png','medium green',5,NULL),(6,'list-folder-6.png','dark green',6,NULL),(7,'list-folder-7.png','light yellow',7,NULL),(8,'list-folder-8.png','yellow',8,NULL),(9,'list-folder-9.png','orange',9,NULL),(10,'list-folder-10.png','dark orange',10,NULL),(11,'list-folder-11.png','red',11,NULL),(12,'list-folder-12.png','magenta',12,NULL),(13,'list-folder-13.png','light pink',13,NULL),(14,'list-folder-14.png','pink',14,NULL),(15,'list-folder-15.png','light purple',15,NULL),(16,'list-folder-16.png','purple',16,NULL),(17,'list-folder-17.png','cream',17,NULL),(18,'list-folder-18.png','light brown',18,NULL),(19,'list-folder-19.png','medium brown',19,NULL),(20,'list-folder-20.png','dark blue',20,NULL);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exam_lu_questiontypes` (
  `questiontype_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `shortname` varchar(128) NOT NULL DEFAULT '',
  `name` varchar(256) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `order` int(11) DEFAULT '0',
  `deleted_date` bigint(64) DEFAULT NULL,
  PRIMARY KEY (`questiontype_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `exam_lu_questiontypes` VALUES (1,'mc_h','Multiple Choice Horizontal','A Multiple Choice Question layed out horizontaly',3,NULL),(2,'mc_v','Multiple Choice Vertical','A Multiple Choice Question layed out verticaly',1,NULL),(3,'short','Short Answer','A Short Answer or Fill in the bank question, correct answers can be added to the system.',5,NULL),(4,'essay','Essay','A long form essay question, graded manually',6,NULL),(5,'match','Matching','A question type where you identify from a list of options',7,NULL),(6,'text','Text','Instructional or Information text to display to the student, no answer.',10,NULL),(7,'mc_h_m','Multiple Choice Horizontal (multiple responses)','A Multiple Choice Question layed out horizontaly, with checkboxes for multipule anwsers.',4,NULL),(8,'mc_v_m','Multiple Choice Vertical (multiple responses)','A Multiple Choice Question layed out verticaly, with checkboxes for multipule anwsers.',2,NULL),(9,'drop_s','Drop Down','The dropdown allows students to answer each question by choosing one of up to 100 options which have been provided to populate a select box.',8,1441323576),(10,'drop_m','Drop Down (multiple responses)','The dropdown allows students to answer each question by choosing multiple options which have been provided to populate a select box.',9,1441323576),(11,'fnb','Fill in the Blank','A question type composed of short answers in a paragraph form with predefined correct options for the short answers.',11,NULL);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `secure_mode` varchar(32) DEFAULT NULL,
  `mark_faculty_review` tinyint(1) DEFAULT '0',
  `use_calculator` int(1) DEFAULT '0',
  `hide_exam` int(1) DEFAULT '0',
  `auto_save` int(5) DEFAULT '30',
  `auto_submit` int(1) DEFAULT '0',
  `use_time_limit` int(1) DEFAULT '0',
  `time_limit` int(20) DEFAULT NULL,
  `use_self_timer` int(1) DEFAULT '0',
  `use_exam_start_date` int(1) DEFAULT '0',
  `use_exam_end_date` int(1) DEFAULT '0',
  `start_date` bigint(20) DEFAULT NULL,
  `end_date` bigint(20) DEFAULT NULL,
  `use_exam_submission_date` int(1) DEFAULT '0',
  `exam_submission_date` bigint(20) DEFAULT NULL,
  `timeframe` enum('none','pre','during','post') NOT NULL DEFAULT 'none',
  `grade_book` int(11) DEFAULT NULL,
  `release_score` int(1) DEFAULT NULL,
  `use_release_start_date` int(1) DEFAULT NULL,
  `use_release_end_date` int(1) DEFAULT NULL,
  `release_start_date` bigint(20) DEFAULT NULL,
  `release_end_date` bigint(20) DEFAULT NULL,
  `release_feedback` int(1) DEFAULT NULL,
  `release_incorrect_responses` int(1) DEFAULT '0',
  `use_re_attempt_threshold` int(1) DEFAULT '0',
  `re_attempt_threshold` decimal(10,2) DEFAULT NULL,
  `re_attempt_threshold_attempts` int(5) DEFAULT '0',
  `created_date` bigint(20) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_date` bigint(20) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `deleted_date` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exam_progress` (
  `exam_progress_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` int(12) unsigned NOT NULL,
  `exam_id` int(12) unsigned NOT NULL,
  `proxy_id` int(12) unsigned NOT NULL,
  `progress_value` varchar(20) DEFAULT 'inprogress',
  `submission_date` int(11) DEFAULT NULL,
  `late` int(5) DEFAULT '0',
  `exam_value` int(11) DEFAULT NULL,
  `exam_points` decimal(10,2) DEFAULT NULL,
  `menu_open` int(1) NOT NULL DEFAULT '1',
  `use_self_timer` int(1) DEFAULT '0',
  `self_timer_start` bigint(64) DEFAULT NULL,
  `self_timer_length` bigint(64) DEFAULT NULL,
  `created_date` bigint(64) NOT NULL DEFAULT '0',
  `created_by` int(11) NOT NULL DEFAULT '0',
  `started_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(12) unsigned NOT NULL,
  `deleted_date` bigint(64) DEFAULT NULL,
  PRIMARY KEY (`exam_progress_id`),
  KEY `content_id` (`post_id`,`proxy_id`),
  KEY `exam_id` (`exam_id`),
  KEY `post_id` (`post_id`),
  CONSTRAINT `exam_progresss_fk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`exam_id`),
  CONSTRAINT `exam_progresss_fk_2` FOREIGN KEY (`post_id`) REFERENCES `exam_posts` (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exam_questions` (
  `question_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `folder_id` int(12) DEFAULT '0',
  `deleted_date` bigint(64) DEFAULT NULL,
  PRIMARY KEY (`question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `filetypes` (
  `id` int(12) NOT NULL AUTO_INCREMENT,
  `ext` varchar(8) NOT NULL,
  `mime` varchar(128) NOT NULL DEFAULT '',
  `classname` varchar(128) DEFAULT NULL,
  `english` varchar(64) NOT NULL,
  `image` blob NOT NULL,
  `hidden` varchar(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `ext` (`ext`)
) ENGINE=MyISAM AUTO_INCREMENT=50 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `filetypes` VALUES (1,'pdf','application/pdf','GoogleDocsViewer','PDF Document',0x47494638396110001000E60000660000C0C0C58E8E92828282EB060A666666E07E80D58E93DEDEDFFF6666BF4446D7C9CEFF4B4EB4B4B4E77171A10406C7ADB289898BEFEFF0D5D5D6D80406EB9E9FD28C8FF4292BFE7679A3A3A7FDA9A9990000CCCCCCC28386FEC2C3E6E6E8CFA8ADEEB0B09999998C0002F7F7F7FA7274A51819FF2125E9ADAFE28282EDC1C2ECA7A9707074E9D4D6FF7E80EAA7A8BCBCBFEA9394E8C6C8C2C1C7A50E0FFDBCBEE1E0E3E9E9ECEB8787F40E11ABABACCFCFD4E4060AFF2B2EFFFFFF9C9CA1FF4F51FF9999E98D8EB6B6BAD6D6DCE67778FFCCCCEBB5B59F0607FF8182DFC3C6EAB5B6E9CCCD86868BECE6E7E3E0E2C4C4C5D79396FF7072FF7A7CA40608BDC5C5E6DEE6FF292CA5A5ADE78787EBC2C4E6D6DEFF0F12FF333300000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000021F9040514003E002C00000000100010000007D1803E823E0D038687880D833E4424123750433F933F11438B135A2D1F1105029D052C8A833B28595B2219053F03194D97A43732421308501F300B10338B1C1F372B1547472F450620508B3BBF37381231474C4A3BC9831C361F1221152A121F44D4BD0836374E0E373708131C018B01B53636291F36441C33300A26FB343454541B460058176048830457929C703105480902144648FAD10041822E498260E08241C3051E1B74F820B1C307060609A40049D2C305831C48B09014E4A1668D2807721EB0D041C78445A43870D841B4D6A240003B,'0'),(2,'gif','image/gif','Image','GIF Image',0x47494638396110001000E600000E2345CBD6DCB87F659999998282829B705DF0F0F0B1B8DC8098CFB9B9BF4F484C5D85B7C3B9AD4E71C11D325F94B7FC8C8B8B435789B5A48AFFFFFF423637666B7A909AB3DEDEDECCCCCC7A9CF26F8EDCA48868706663B5B5B5B19C83AEAEAEF7F7F7779CE863749CAEBEE1C0C2C4291D207C7678C6C7D477829DA8A8A8B5ADAD8AACF7E8E8E8AFA28C334268A0A5B1BDBDBDC5C9E59999CC644E506073ACD6D6D6C1BBB4CFBDA799BBF6A5A5ADB8BFC94C3E3CDBDDE9C5C5C5BDBDC57D7D7DC5D1E3A7A1B400000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000021F90405140013002C00000000100010000007AD8013131D048586871D828235822C1F189082208C8A9403171D293F1720938A13943D293D1D10189D948B9D061D2C9DAFA9A09D303C1D3006B806B135201735070A101F3D2C2C189F35AC0B15251C26272991953D230D2E053B15323109898A1824192B33022816001124309F904038144121340E2F1F09EC352D12010F191A22033E5C60878181870D3774209800E2D8A71E357A48EC61E343C34F8220D6A80109C687588A2E881C596320C640003B,'0'),(3,'jpg','image/jpeg','Image','JPEG Image',0x47494638396110001000D500000E2345CCCCCCAEAEAE8C8C8C435789DEDEDE7066636F8EDCB8BFC9F7F7F7828282AEBEE1334268909AB3B19C839B705D8AACF763749CC1BCB41D325FF0F0F0C5D1E34F484C5D85B74236379999994E71C14C3E3CA48868779CE8C3B9ADFFFFFF666B7A291D20B5B5B5C6C7D49999CCD6D6D677829D8098CF7C7678C5C5C5B87F6599BBF6E7E7E7A0A5B1A3A3A3644E50AFA28C6073ACC5C9E594B7FCCBD6DCCFBDA77A9CF2C0C1C5B1B8DCDBDDE9BDBDBDB5B5BDB5ADADA7A1B4B5A48AC0C2C421F9040514001F002C00000000100010000006A3C0CF47A4281A8F22A1B044F9293F8968A2F4A46606898C2EE5CA4CABD981382508B0BECAD23435F8E928BA00859A8EEA72A237654F5F260A2538160302292C2C01551422172021062823658969290B1A0C0F1B2024323A494A013F36102F2A260D0004373A4F0101152B183D1D31132D02A0420125303E3433360711190205AE011E0E1C35082750944A292529D529120209D1D225DDAF3A027D4F05E4E525C74F1F41003B,'0'),(4,'zip','application/x-zip-compressed',NULL,'Zip File',0x47494638396110001000C400001C1C1CE6E6E6CCCCCCB5B6B58D8D8D7B7B7B666666F7F7F73F3F3FD6D6D6ADADADFFFFFFEFEFEF5D5D5DC5C5C5DDDDDD999999333333757575BDBDBD85858599999900000000000000000000000000000000000000000000000000000000000021F9040514000B002C0000000010001000000583E0B20853699E8E2826ABE24C8FCACAE2434C94F41CC7BC2E87470142904C78BE058BC76C2613CCC4C4C188AA943CC6D111600AAED003C3711074198CAF4A901510B8E8805A2440A713E6803EB5B6530E0D7A0F090357750C011405080F130A8F867A898B09109601575C7A8A080203010C570B9A89040D8EA1A202AB020611009FA221003B,'0'),(5,'exe','application/octet-stream',NULL,'Windows Executable',0x47494638396110001000B30000999999EFEFEFDEDEDECCCCCCB5B5B5FFFFFFF7F7F7E6E6E6D5D5D5BDBDBD99999900000000000000000000000000000021F90405140005002C0000000010001000000453B09404AABD494AA1BBE69FA71906B879646A1660EA1A4827BC6FFCD169606F6FE007871D8BF4FB1D0E830EA2681420929A2533F8CC4481C7E3A0425821B24701A100F37CB34E42D91C760E08420D58403F8822003B,'0'),(6,'html','text/html',NULL,'HTML Document',0x47494638396110001000E60000052E49E6E6E6BBC3AD7D7D7D80BF35669933D6D6D6FFFFFF45834280A256CCCCCCA2A2A2BDBDBDBDEE744882578FE32EEFEFEF21564E6FBB3A9999998DDE2C9999991F4D617676768DB756DEDEDEB5B5B571A82E9EE438F7F7F797CF4039724A7CA08BC4C4C4184D54D3DBCA87B16A29614484D6317BB834518845ABABAB5EA338ABE74C5B886194BD5907385888AD5A225A5725576CB5C5AD99E333C2CBB588C739487F3991E12F7DB83DC6CEBE64AA3874AB317FBE35BFC8B25F9C3C82A55894E6318EB8588CAD5A00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000021F904041400FF002C00000000100010000007A98007070C138586870C828219821D190A0A191D93068A078C10022D1E1E2D02101D958A9234381F252A37353DA196191041083036003A0F1810A28B10043E310E22110F3C01BA9710271C24202C2E2627C5AE1D2F142B0D0D16122F012196061D233B14333328053906898A061010233F1B1B09E82129DEEDF8B9290BFCF701FFFF180CB830C1980180011ED983A0C0D28183FF1E3158D0CA2144892952185B074941888F011C1E0804003B,'0'),(7,'mpg','video/mpeg',NULL,'MPEG Movie',0x47494638396110001000D50000737373C7D3DCAEB7C13E86C4E6E6E69999995792C5C1CCD4F7F7F74A99D899B9D0778EA7D8DADC7494B1298BE0B5C2CBADADAD8585854DADEDBEBEBE85A7C0FFFFFF8C8C8C3292E3AEC5D5D6D6D6A4A4A43D93D8CCCCCCBEC6CDC3C3C34295DEA7BFD24B9BE472A0C22C94ED579BCEE0E2E4469DDF8CADC9D1D4D8B6B6B6C5C9CD3E8BCF4593CBA9BDCDD2D7DBDEDEDEEAEAEB4A9CDEB5C5D64AADF7A9C6DCBDC9D36699CCD0D6DB00000000000000000000000000000000000000000000000021F90405140015002C00000000100010000006A1C04A4513291A8F1AA19091617038150E24038165941506240228400A298BE775555221DD8845C32E95851902624E9FBBB199179D202FC198782A08253617172426341E28782508061B201809210C4E7819280E357C2E120F4F781E1D232807142F262D29504A280C04262204010D1F2A2A13584F3018332C032B0B101A8C4A1C137C370A27021508C6C7710425254ED058424FD52F4C136F582FE2E24C2FD91541003B,'0'),(8,'pps','application/vnd.ms-powerpoint','GoogleDocsViewer','Microsoft PowerPoint',0x47494638396110001000D50000C35D0478AFE084A4CC9FA1A5838484F4F3EFBF8B5DDE8A8CE8EBEED4C9C0F3B076E4E2E0E5A971CCCCCCDEDEDED2D590FF7A04BFBFBFD5D5D5FCA151ADB2B5D5C0ADB5BDBDA2DDF5FF9933FFFFFFF67908BB7F92F7D1A9E6E0C9FE860EF1BF92DDCFC3F5B67DDB95A4C6C4C1DCE889F6F6F6CC8A52B9C3CFFFCC99DCCDBFF2B27AFFB655EDEBEAFF7C08E7E7E7A7AAAEFFB66AEBE3BFFF8C10EFEFEFB5B5B5FF8207C5BDBDE1D3C5F9BE7EECAE76F7F7EFD7CBC0A6E1FCC7C5C300000000000021F90405140019002C0000000010001000000699C08C70482C661C92A472996C3490C4128B55721493999236237115AC5A2D56DB884446E888233C2ECD662781A05178CF4AC9F08C15E05D366E335E0D1281770F240722192C2E0B2C84522C331905311D25192E4A910808161414032FA1140404143309381F0A21AF0C392A39060B8F1915181C13102DBEBE1A000E2C42152B2830351ECBCB1A26944233292937D520D3293B0546DCDDDE4641003B,'0'),(9,'ppt','application/vnd.ms-powerpoint','GoogleDocsViewer','Microsoft PowerPoint',0x47494638396110001000E60000C35D048EBCE294AAC5ADADAD858585EBE9E6E4E1DFBADDF3BE8A5CD0D4D8EFAF77B1CDD6DFCEBDA1BDDEFF7A04EAAD75FFFFFFDEDEDEFCA151A2B5CBF2BF92A1D6F5FF9933D5C0ADF67908B7D5DDF7F7F7C9F5FFCCCCCC8DACD6FE860EF9BE7EB3C5CEF7D1A9E6E6E6D6D8DFD7C8BBA0BDDF98BAD2D6D6D6CED6DEB5CEDEA4C2D491BFE4EFEFEFDEE0E3EFB078FFB66AE9EAEBCC8A52D2F1FCFFCC99FFB655ADBFD0FF7C08FF8C1094B5DD9CB4C9E4E3E1EFB57BDED6D6FF8207EFB573DEE6E6BDDEF7BFCCD394ADD6BBC3CF9CBDD795C6E8E9EBEFAAC0D9FFFFFF00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000021F90405140048002C00000000100010000007AA804882838485481109898A091C8D8E1C11271093101A1197981A1A1027929A1A2C8B892C2C1A9D95229D20290B193241A41A89953A09233944262A0735B1899A11071B1D2B0102471330301A1C27A011451522D3053AD63A301C09CF1D0DDE384322919DDA1A462D22CA3022E81C0404032C241F140A2EF7F80F0806063048171642487060A360410C0022F8FB4763C68B1E1E2246C41083C520790C326624C19184454320438A2C1408003B,'0'),(10,'pptx','application/vnd.openxmlformats-officedocument.presentationml.presentation','GoogleDocsViewer','Microsoft PowerPoint',0x47494638396110001000E60000C35D048EBCE294AAC5ADADAD858585EBE9E6E4E1DFBADDF3BE8A5CD0D4D8EFAF77B1CDD6DFCEBDA1BDDEFF7A04EAAD75FFFFFFDEDEDEFCA151A2B5CBF2BF92A1D6F5FF9933D5C0ADF67908B7D5DDF7F7F7C9F5FFCCCCCC8DACD6FE860EF9BE7EB3C5CEF7D1A9E6E6E6D6D8DFD7C8BBA0BDDF98BAD2D6D6D6CED6DEB5CEDEA4C2D491BFE4EFEFEFDEE0E3EFB078FFB66AE9EAEBCC8A52D2F1FCFFCC99FFB655ADBFD0FF7C08FF8C1094B5DD9CB4C9E4E3E1EFB57BDED6D6FF8207EFB573DEE6E6BDDEF7BFCCD394ADD6BBC3CF9CBDD795C6E8E9EBEFAAC0D9FFFFFF00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000021F90405140048002C00000000100010000007AA804882838485481109898A091C8D8E1C11271093101A1197981A1A1027929A1A2C8B892C2C1A9D95229D20290B193241A41A89953A09233944262A0735B1899A11071B1D2B0102471330301A1C27A011451522D3053AD63A301C09CF1D0DDE384322919DDA1A462D22CA3022E81C0404032C241F140A2EF7F80F0806063048171642487060A360410C0022F8FB4763C68B1E1E2246C41083C520790C326624C19184454320438A2C1408003B,'0'),(11,'png','image/png','Image','PNG Image',0x47494638396110001000E600000E2345CCCCCCAEAEAEA48868435789DEDEDE6666666F8EDCB9B9BF848484F7F7F78AACF7334268909AB39B705DA2A2A263749CB8BFC91D325FEFEFEFC5D1E38C8B8BAEBEE14F484C5D85B7C3B9AD4236374E71C14C3E3C779CE8B5B5B5666B7AAFA28CFFFFFF291D20C6C7D49999CCD6D6D6999999B19C8377829DE8E9E98098CF7C7678B87F65C0C2C499BBF6A0A5B1644E506073ACC5C9E5C1BBB4BDBDBD70666394B7FCCBD6DCCFBDA77A9CF2B1B8DCDBDDE9B5ADADC5C5C4A5A5ADBDBDC5B5A48AA7A1B400000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000021F90405140021002C00000000100010000007AD8021211E098586871E828225210A8C253D131E3D8E8A218C2915010935022508958A250A99020908250F01A18B0A0A9229AEB28CA2AE343B1E3413BB13B4AD05253A1715023D292901962592181F22352B233E01C9A23D161B0C0E1C1F243208898A012D390B302C280D00042D3496D4142E1A411D31122F0208EF2520403736390E403021A0C0BB00194E0CC0114145A36A8A7A40EA4171860005102396D8488D86274B8A0A881C59C220C840003B,'0'),(12,'doc','application/msword','GoogleDocsViewer','Microsoft Word',0x47494638396110001000D500004476CADEDEDECCCCCCAFB8C784A6DAEAECEE92B8F5BDCBDFA5A5A56095EEF6F4F2D1D8E0A2B2CFE2E5E9548CEAADC2E6D6D6D6FFFFFFCFD7E37A9FD98BAFEFC1C1C1ADC5DEBCCFEAE8F1FD7698D4D6DEE9EBEAE7ABC3EFC2D0E4EBEBE9DFE0E3B5B5B5E8EAEC8FA5CD548DEDD5DAE1AAAAAABAC9DACAD7EBE6E5E2F0EFEFC5CEDEC2C2C293B0E3B3C7EBB3C7E4457AD29DBDF4E7E6E5E6DEDEF7EFEFF6F6F6D7D8DBC8D4E67B9CDEC0D1EE598FEE588CE8B5C5DEAEC8F4CFDBEDC8D3E100000021F90405140011002C00000000100010000006AAC048C42380188F479450B8593A9710A7875628A4141E4F2A458B2E3D9154AF852BE02E85AE73430B3D4E05DFC3A6517899B4C6A3B1D93C34282977111B0A31361D31312E24280583858A1F1D0B2E0B1F1B83313431241F311F07240128024E9B1F360B2828070B1001A64B81281F1224123E35462B4E1F0501C101BB20082515BE05317D1B28100808204F2C061C1423D82F0C1F4F112C18303C09393A0003DD112E260413ED1922DD41003B,'0'),(13,'docx','application/vnd.openxmlformats-officedocument.wordprocessingml.document','GoogleDocsViewer','Microsoft Word',0x47494638396110001000D500004476CADEDEDECCCCCCAFB8C784A6DAEAECEE92B8F5BDCBDFA5A5A56095EEF6F4F2D1D8E0A2B2CFE2E5E9548CEAADC2E6D6D6D6FFFFFFCFD7E37A9FD98BAFEFC1C1C1ADC5DEBCCFEAE8F1FD7698D4D6DEE9EBEAE7ABC3EFC2D0E4EBEBE9DFE0E3B5B5B5E8EAEC8FA5CD548DEDD5DAE1AAAAAABAC9DACAD7EBE6E5E2F0EFEFC5CEDEC2C2C293B0E3B3C7EBB3C7E4457AD29DBDF4E7E6E5E6DEDEF7EFEFF6F6F6D7D8DBC8D4E67B9CDEC0D1EE598FEE588CE8B5C5DEAEC8F4CFDBEDC8D3E100000021F90405140011002C00000000100010000006AAC048C42380188F479450B8593A9710A7875628A4141E4F2A458B2E3D9154AF852BE02E85AE73430B3D4E05DFC3A6517899B4C6A3B1D93C34282977111B0A31361D31312E24280583858A1F1D0B2E0B1F1B83313431241F311F07240128024E9B1F360B2828070B1001A64B81281F1224123E35462B4E1F0501C101BB20082515BE05317D1B28100808204F2C061C1423D82F0C1F4F112C18303C09393A0003DD112E260413ED1922DD41003B,'0'),(14,'xls','application/vnd.ms-excel','GoogleDocsViewer','Microsoft Excel',0x47494638396110001000D50000079F04C0F2BFD6D6D6B5B5B574D8714FC14D49B645F6F6F6A7A7A7EEE9EE2DC52ACCCCCCE5E5E566CC66DEDDDEFFFFFF9AD799A4C0A40ABD048CC18B60D55CBABABA4ACE4771BA6FA8D4A7EFEFEFE6DEE610BE0AC5C5C5AFAFAFF4F4FF4BBF4853C24F66CC66EFEFF70BBB0606A60232C52C00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000021F9040514000F002C000000001000100000067AC0C783B110188F478650A85C3A850267E2E0701C32D7EB23BA641C8C592C96CBFC0AAE99F4D8E9058BB3E4A1390C6767DCE1BBDDF838F8FD090B760B1C071E1E62447B461C480E1C76559202151D1D904B0E19099C091A020808034F0D140A0A12A924134D4E0D0104161BB300114F42101805051F1F0617B741003B,'0'),(15,'xls','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','GoogleDocsViewer','Microsoft Excel',0x47494638396110001000D50000079F04C0F2BFD6D6D6B5B5B574D8714FC14D49B645F6F6F6A7A7A7EEE9EE2DC52ACCCCCCE5E5E566CC66DEDDDEFFFFFF9AD799A4C0A40ABD048CC18B60D55CBABABA4ACE4771BA6FA8D4A7EFEFEFE6DEE610BE0AC5C5C5AFAFAFF4F4FF4BBF4853C24F66CC66EFEFF70BBB0606A60232C52C00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000021F9040514000F002C000000001000100000067AC0C783B110188F478650A85C3A850267E2E0701C32D7EB23BA641C8C592C96CBFC0AAE99F4D8E9058BB3E4A1390C6767DCE1BBDDF838F8FD090B760B1C071E1E62447B461C480E1C76559202151D1D904B0E19099C091A020808034F0D140A0A12A924134D4E0D0104161BB300114F42101805051F1F0617B741003B,'0'),(16,'swf','application/x-shockwave-flash',NULL,'Macromedia Flash',0x47494638396110001000D50000032F51D4D6DEB5B8BC8C99A5808E9C6E8397EAEDEF557790C4CAD09CA9B7F7F7F7DFE3E67A8B9B33536D949FAED0D4D7999999C1C5C97676760B4568FFFFFFABABABA2A2A2B5BDC4DEDEDE1C44615B87A3AEB2B59999999AA5B1E4E7EB8A96A3BDBDBDD7DCE2CBD1D9D6D6D659788F7D7D7DC4C4C410486C9BA6B2DFE0E6E6E6E6CCCCCCEFEFEF8493A0B3BFCAB5B5B5B8BCC0949CA596A1AED8DCE3C4C8CB00000000000000000000000000000000000000000000000000000000000000000021F90405140014002C00000000100010000006A0400A0504291A8F20A110235460562B8C62CA5432151BD9E522DBB09C4A8A1446282042AA81006C65C52E8DCC6CFE618DC218162A6500905C33322A776D1D0614052709330E2A0F780A2D210A0B131A01040B34900F2D221E09070434230261232C2C9D0C0C04110F2615A7A9B4761B16B8A706BB062A2A2025121084420FBEBE4FB20A2B6114C6C82B20160A8FCD0F0B0B4F201515C4610F502B26E32ACD1441003B,'0'),(17,'txt','text/plain','GoogleDocsViewer','Plain Text File',0x47494638396110001000D50000513D32D8D1CDC5C5C5AFAEA86C7879DEDEDEF0F0F0694E468799BBAFB6C0888173AAA8A5F7F7F99DB4DECCCCCC474837828282945147E6E6E6999999D0A183B2B2B2E2D7D08D423A575349989685FFFFFFBDBDBDD6D6D6504C2EA5B5D15E443BB4B0A07B7B7B5E39308F8578D4D7DCDAE1E4474A424F4231D6D6CEADADADE3D6CE00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000021F9040514001A002C0000000010001000000691408DA6E010188F488650C3590A194A0DC36058720C28C9C620585C22A04255C82998CD8391E860428D99857219A3F89C3A8E3747E2E0EC1F190026046E56447D051E09080D0D247A7C051B657D1C026286667E1B95027986971C95A20E5BA0A205461B1B15157A1C1B4802132110154E1C01010EA5AB137C4E4C16142571BC0B4DC1572A121271021BC14206717ED6D241003B,'0'),(18,'rtf','text/richtext','GoogleDocsViewer','Rich Text File',0x47494638396110001000D50000513D32D8D1CDC5C5C5AFAEA86C7879DEDEDEF0F0F0694E468799BBAFB6C0888173AAA8A5F7F7F99DB4DECCCCCC474837828282945147E6E6E6999999D0A183B2B2B2E2D7D08D423A575349989685FFFFFFBDBDBDD6D6D6504C2EA5B5D15E443BB4B0A07B7B7B5E39308F8578D4D7DCDAE1E4474A424F4231D6D6CEADADADE3D6CE00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000021F9040514001A002C0000000010001000000691408DA6E010188F488650C3590A194A0DC36058720C28C9C620585C22A04255C82998CD8391E860428D99857219A3F89C3A8E3747E2E0EC1F190026046E56447D051E09080D0D247A7C051B657D1C026286667E1B95027986971C95A20E5BA0A205461B1B15157A1C1B4802132110154E1C01010EA5AB137C4E4C16142571BC0B4DC1572A121271021BC14206717ED6D241003B,'0'),(19,'mp3','audio/x-mp3',NULL,'MP3 Audio',0x47494638396110001000D500004B914FD5CFC8A5B1B59999998C8C8CE1E1E3B1CCD07E7E7EEFEFEF6DB36B87B3889FB397CCCCCCDEDDDEF7F7F7BDBDBDB4CDF3A8B8AFEBE4B383DE738AA98B66996680A381BCBFD7A5A5A5C6DDB6FFFFFFB5B6B591BA959CC0A1D6D6D6B6C5B9C4C4C44FA752E6E6E67AB769B4DBCAD1D2D8E9E1D6E7E8ECADADAD8BB68B61A15C93AF91B5B6BD8CB4919CB2A096C8929EC4A355A15A00000000000000000000000000000000000000000000000000000000000000000000000000000000000021F9040514001A002C00000000100010000006A3408DE681291A8F0FA1D0C3F4141A1B28A2C1506A3C070C6AA0C5340884A4D213DE0E508F8D8800B27A340E84071460201C8E92DBD1508D4C122F02777A630E2C2D212B0B13092508556327250E1F140C2E1505905625270D2711160F0E29222291427617102429770A22056D4A760D0619310909000D0D62AA27C20D1D301C150C0F28560CA7CE051E03D205CCCF4F0C6A08561A20A74F0D1ECADADB0CE5E66927DB41003B,'0'),(20,'mov','video/mpeg',NULL,'MPEG Movie',0x47494638396110001000D50000666666C7D3DCAEB7C13E86C4E6E6E69999995792C5C1CCD4F7F7F74A99D899B9D0778EA77494B1D8DADC298BE0A7A7A7B5C2CB828282C3C3C34DADED85A7C08B8B8BFFFFFF3292E3AEC5D5D6D6D6BEBEBE3D93D8ADADADCCCCCCBEC6CDEEEEEE4295DEA7BFD24B9BE472A0C22C94ED579BCEE0E2E4469DDF8CADC9D1D4D8B5B5B5C5C9CD3E8BCF4593CBA9BDCDD2D7DBDEDEDEEAEAEB4A9CDEB5C5D64AADF7A9C6DCBDC9D36699CCD0D6DB00000000000000000000000000000000000000000021F90405140016002C00000000100010000006A3408BE511291A8F0FA1B0618A3554A94F27D3E148949646A5D8A970228F422183CD00C4E24A2722EE940988B83C6E222B33303901DE6CD885192B08263717172527351229652608061B211809220D0D6E7719290E367B2F13101D9880121E2429071430272E2AA316290D04272304010C202B2B1A58A23118342D032C0B0F0F8C4A1D1A7B380A28021608C8C9197B262697D25842A2D7307E1A7F5830E4E47E30DB1641003B,'0'),(21,'pdf','application/pdf','GoogleDocsViewer','PDF Document',0x47494638396110001000E60000000000FFFFFFFFA9AAFBA8A9F8A6A7F5A6A7FFAFB0FFB2B3FFB3B4FBB2B3FFBEBFDFA8A9D6A3A4FFCBCCFDCCCDFFCFD0FECECFFFD1D2F1A4A6FFBFC1F7D8D9F1D8D9EFD6D7F8DFE0F7DFE0FEE7E8F8E9EAF0D6D8F7ECEDEBE2E3F7EFF0EEE0E2F4E9EBF1ECEEF6F0F3F8F6F7DFDFE2F0F0F2E9E9EBE8E8EAD6D6D8D3D3D5CBCBCDF9F9FAF7F7F8F6F6F7F4F4F5F3F3F4EEEEEFE7E7E8E5E5E6DEDEDFDCDCDDD4D4D5FFB6B6DEA6A6DAA3A3DCA6A6DFACACFFC8C8E8BCBCC8A3A3FFD1D1F7CCCCF6CECEFFDADAF8D4D4F7D4D4F5D2D2F4D1D1F7D6D6FEE0E0F8DCDCE9D3D3F9E3E3FEE9E9F7E2E2F8E5E5F8E9E9F7EBEBFCFCFCF0F0F0ECECECEAEAEAE4E4E4E1E1E1DADADAD2D2D2C8C8C8FFFFFF00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000021F90401000059002C00000000100010000007D180018201545786878854830125502B2C533234933435328B511A1E2D3558289D582A8A83304C43225633583457332997A42C4F46512F532D31211D268B522D2C17484D4D1840451F538B30BF2C422B144D1C2030C983522E2D2B4A484E2B2D25D4BD2F2E2C233F2C2C2F5152278B27B52E2E442D2E255226313C3AFB0B0B37373818F458774206951D072218F0F160820302121848A241E5C50E1B1182401000E148820238AA048002230004053B1A4C8880C087820139489014B4A466860A1B726EB090A44A9445A4A4488141B4D6A240003B,'1'),(22,'gif','image/gif','Image','image/gif',0x47494638396110001000E60000000000FFFFFFC7BFC0BBB6B7B2ADAFBFBDBFDFDDE4DADAECEBEBEFE6E6E8E3E5F2EAECF5F2F3F7C6CDE1D7DAE3CBD6F2BBC2D4C7CDDCB6BBC8CED2DCC8CACFBFCCE9CFDCFAD5E1FCD1DAEECEDCF7D9E5FEADB5C5E2E8F4DDDFE3DAE6FCEAEEF5A8B0BCE5E8ECC5D3E5E8E9EAECF0F2E2DDD6E9E6E2DED4C8E3DCD3E5DED5EEE7DFE9E6E4E5D1C8DBCBC5CBC8C7BFB9B9D0CECED6D5D5FCFCFCFAFAFAF7F7F7F3F3F3F0F0F0ECECECEAEAEAE7E7E7E5E5E5E2E2E2E0E0E0DADADAD2D2D2D0D0D0FFFFFF00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000021F90401000040002C00000000100010000007AD8001013A3E8586873A82823682343B379082328C8A943D353A3C3F3532938A0194383C383A31379D948B9D333A349DAFA9A09D390C3A3933B833B1363235360A05313B383434379F36AC2214042E30083C9195381C15122D2F14070B09898A37231617022C130E201023399F901F1E0306190D1B1D3B09EC362529241A160F113D76D46077C3048A132A42600820E3D8271C367048C4B16247C34F8220DAB00129C78E588A6A881C696320C640003B,'1'),(23,'ppt','application/vnd.ms-powerpoint','GoogleDocsViewer','Microsoft PowerPoint',0x47494638396110001000D50000000000FFFFFFF3D5D6F2D9DEE6D1D8E2E3E5DFE0E2DCDDDFE6E9EED3DEECF7F8F9CEE2F4DFF4FEDDF3FBE5E7E7F2F7D4EFF0D7FBFBF9F8F8F7F5F5F4F8F5E8F6F4ECFFE5C2EFECE8FDE8D1FFD2A6FED3A8FFDAB6FFE5CAFCEEE0F4EFEAF3EEE9F2EDE8EBEAE9FFCFA4FFD0A6FCCFA6E9C5A4FBE5D0F8E2CEF5E0CCFFECDAFAE8D8FEDDC1ECD5C1FBE3CEFAE3CFE8D5C5F0E8E2F1ECE8FCFCFCF9F9F9F7F7F7F3F3F3F0F0F0ECECECE8E8E8D3D3D3FFFFFF00000000000000000000000000000021F9040100003A002C0000000010001000000699C08070482C066AB6A47299BCDD9044994422AB159301993660A345AC5A2D567BC3E142685C2D3C96CD668844E21679CF64C9F04CB26034086E335E37368177100F0203011234131284521233011114153201344A910A0A0E05050706A1053939053317182A2D26AF28272E272F138F01301B1D2B2223BEBE24253512423016291C191ACBCB242C94423320201ED51FD320311146DCDDDE4641003B,'1'),(24,'jpg','image/jpeg','Image','JPEG Image',0x47494638396110001000D50000000000FFFFFFC7BFC0BBB6B7B2ADAFBFBDBFDFDDE4DADAECEBEBEFE3E5F2EAECF5F2F3F7C6CDE1D7DAE3CBD6F2BBC2D4C7CDDCB6BBC8CED2DCC8CACFBFCCE9CFDCFAD5E1FCD1DAEECEDCF7D9E5FEADB5C5E2E8F4DDDFE3DAE6FCEAEEF5A8B0BCE5E8ECC5D3E5E8E9EAECF0F2E9E7E4E2DDD6E9E6E2DED4C8E3DCD3E5DED5EEE7DFE5D1C8DBCBC5CBC8C7BFB9B9D0CECEFCFCFCFAFAFAF7F7F7F3F3F3F0F0F0ECECECEAEAEAE7E7E7E5E5E5E2E2E2DEDEDEDADADAD6D6D6D2D2D2FFFFFF00000021F9040100003E002C00000000100010000006A2C04000D72B1A8F38A19016132903B0288CF6A4EE78B0DDCDA6DB4DAB599ED896ABC9BE4ADAD4C613DD62B75A8C9A8EDE16B8776C4F5FC2663409053C39363232355531382113042D2F08658969361B14112C2E13070A37494A35221516022B120D1F0F6E4F35351E1D0306180C1A1C39A042353425292319150E103B3933AD352628272A201750944A363436D436243930D0D134DCAE37397D4F33E3E434C64F0141003B,'1'),(25,'zip','application/x-zip-compressed',NULL,'Zip File',0x47494638396110001000C40000000000FFFFFFFCFCFCF9F9F9F6F6F6F3F3F3F0F0F0ECECECEAEAEAE7E7E7E5E5E5E2E2E2DADADAD6D6D6D3D3D3D0D0D0CDCDCDC8C8C8C5C5C5BABABAB6B6B6ADADADFFFFFF00000000000000000000000000000000000000000000000000000021F90401000016002C000000001000100000058360101C49699E88281AEB822485CACA62D1240E5408C2BC0682C283D1802478BE008BC76C260D4C43023188AA94BCC11141601EAE50C1002138740783AFEA903D34B868825A7440A70D66823EB5B63B04127A05060A577503040E0F1305090B8F867A898B060C9604575C7A8A13070A040357019A890D128EA1A207AB071114159FA221003B,'1'),(26,'zip','application/zip',NULL,'Zip File',0x47494638396110001000C400001C1C1CE6E6E6CCCCCCB5B6B58D8D8D7B7B7B666666F7F7F73F3F3FD6D6D6ADADADFFFFFFEFEFEF5D5D5DC5C5C5DDDDDD999999333333757575BDBDBD85858599999900000000000000000000000000000000000000000000000000000000000021F9040514000B002C0000000010001000000583E0B20853699E8E2826ABE24C8FCACAE2434C94F41CC7BC2E87470142904C78BE058BC76C2613CCC4C4C188AA943CC6D111600AAED003C3711074198CAF4A901510B8E8805A2440A713E6803EB5B6530E0D7A0F090357750C011405080F130A8F867A898B09109601575C7A8A080203010C570B9A89040D8EA1A202AB020611009FA221003B,'0'),(27,'exe','application/octet-stream',NULL,'Windows Executable',0x47494638396110001000B30000000000FFFFFFFCFCFCF9F9F9F6F6F6F3F3F3F0F0F0ECECECE7E7E7E5E5E5DADADAFFFFFF00000000000000000000000021F9040100000B002C00000000100010000004533004A4AABD48CAA2BBE69FA70902B879646A0660EA0A4657BC6FFCD1E9606FEFE00F841D8BF4FB1108878EA1682C18929A2533F8CC4481C7E3A1925819B2C7422200F37CB3CE44D91C761E12420DB840278822003B,'1'),(28,'html','text/html',NULL,'HTML Document',0x47494638396110001000D50000000000FFFFFFA5B4BEA6B8C3B0C2CAAFBFC6ACBFC1AFC4C2AFC2BFB2C6BCD0DDD5B8CCBEBDD2C2C4D4C6BCD3BBBDD1B8C1D4BCC5DEB8C5DCB9C7E0B8D4E3CACBE6B8D3F0B5D0E5B9C8DAB6EFF2ECEBEEE8D7F5B4D1E8B6CDE1B5CCE0B4D4E2C4D2DFC3D7F4B4D6F3B3D4EBB8D0E5B6D6E5C3D1DDC2DAF5B6DCF5B8E7F9CDD6E5C2E9ECE5E6E9E2E1F7BFDAEEBAD9E7C3E8EBE3FCFCFCF9F9F9F6F6F6F3F3F3F0F0F0ECECECEAEAEAE7E7E7E1E1E1DDDDDDDADADAD0D0D0CECECEFFFFFF00000021F9040100003E002C000000001000100000069CC04000B72B1A8F38A190268CD16C365A6C5A53069832D6CBE57AB164B1AA52BABA2C1291D00816B6D26425C7E121986C5432F152C691101806081B1C337A57322428140A0D031624856E311F222D292905151F3337563531191D22272710181A35494A35323219201E1E26A837399EADB879393ABCB733BFBF383C3D3B8635C0334FB632365601C7BF4F383A6DCED0D2393986AB503637DF33CE0141003B,'1'),(29,'mpg','video/mpeg',NULL,'MPEG Movie',0x47494638396110001000D50000000000FFFFFFF7F7F8EEEFF1E2E5E9CED6DFC8DAECE9ECEFEEF0F2B2D5F4BEDBF5B9D5EEB9D3EAB3D9F8B5D8F5B9D8F1BBD9F3BEDAF1C2D8EACDD9E3D6E2ECE0E7EDBCDCF4C2DBEED3DFE8DFE8EFE2EAF0EBEFF2E5E9ECBEE2FCBCD8ECCCDDE9DAE6EEE8EBEDBFE2F8E0EBF2E7ECEFEFF1F2EAECEDF4F5F5F1F2F2FCFCFCF9F9F9F6F6F6F3F3F3F0F0F0ECECECE9E9E9E8E8E8E5E5E5E2E2E2DFDFDFDADADAD5D5D5D2D2D2C8C8C8FFFFFF00000000000000000000000000000000000000000021F90401000038002C00000000100010000006A3C04060662B1A8F33A1107512A0620395ABE592BD940154ADE8AAC96C331AAD856DDDC4E29ACB26769557A9B83C7E222B5B2CF90ADE44D9852D262927060E0E1716232F03652729120F191A110A28286E772D0309247B25221C2E98802F210D0307182C161531A30103282B161F2B1B131026263058A2021A1D1E0C0B0533338C4A2E307B082014040129C8C92D7B272797D25842A2D72C7E307F582CE4E47E2CDB0141003B,'1'),(30,'pps','application/vnd.ms-powerpoint','GoogleDocsViewer','Microsoft PowerPoint',0x47494638396110001000D50000000000FFFFFFF3D5D6F2D9DEE6D1D8E2E3E5DFE0E2DCDDDFE6E9EED3DEECF7F8F9CEE2F4DFF4FEDDF3FBE5E7E7F2F7D4EFF0D7FBFBF9F8F8F7F5F5F4F8F5E8F6F4ECFFE5C2EFECE8FDE8D1FFD2A6FED3A8FFDAB6FFE5CAFCEEE0F4EFEAF3EEE9F2EDE8EBEAE9FFCFA4FFD0A6FCCFA6E9C5A4FBE5D0F8E2CEF5E0CCFFECDAFAE8D8FEDDC1ECD5C1FBE3CEFAE3CFE8D5C5F0E8E2F1ECE8FCFCFCF9F9F9F7F7F7F3F3F3F0F0F0ECECECE8E8E8D3D3D3FFFFFF00000000000000000000000000000021F9040100003A002C0000000010001000000699C08070482C066AB6A47299BCDD9044994422AB159301993660A345AC5A2D567BC3E142685C2D3C96CD668844E21679CF64C9F04CB26034086E335E37368177100F0203011234131284521233011114153201344A910A0A0E05050706A1053939053317182A2D26AF28272E272F138F01301B1D2B2223BEBE24253512423016291C191ACBCB242C94423320201ED51FD320311146DCDDDE4641003B,'1'),(31,'ppt','application/vnd.ms-powerpoint','GoogleDocsViewer','Microsoft PowerPoint',0x47494638396110001000D50000000000FFFFFFF3D5D6F2D9DEE6D1D8E2E3E5DFE0E2DCDDDFE6E9EED3DEECF7F8F9CEE2F4DFF4FEDDF3FBE5E7E7F2F7D4EFF0D7FBFBF9F8F8F7F5F5F4F8F5E8F6F4ECFFE5C2EFECE8FDE8D1FFD2A6FED3A8FFDAB6FFE5CAFCEEE0F4EFEAF3EEE9F2EDE8EBEAE9FFCFA4FFD0A6FCCFA6E9C5A4FBE5D0F8E2CEF5E0CCFFECDAFAE8D8FEDDC1ECD5C1FBE3CEFAE3CFE8D5C5F0E8E2F1ECE8FCFCFCF9F9F9F7F7F7F3F3F3F0F0F0ECECECE8E8E8D3D3D3FFFFFF00000000000000000000000000000021F9040100003A002C0000000010001000000699C08070482C066AB6A47299BCDD9044994422AB159301993660A345AC5A2D567BC3E142685C2D3C96CD668844E21679CF64C9F04CB26034086E335E37368177100F0203011234131284521233011114153201344A910A0A0E05050706A1053939053317182A2D26AF28272E272F138F01301B1D2B2223BEBE24253512423016291C191ACBCB242C94423320201ED51FD320311146DCDDDE4641003B,'1'),(32,'pptx','application/vnd.openxmlformats-officedocument.presentationml.presentation','GoogleDocsViewer','Microsoft PowerPoint',0x47494638396110001000D50000000000FFFFFFF3D5D6F2D9DEE6D1D8E2E3E5DFE0E2DCDDDFE6E9EED3DEECF7F8F9CEE2F4DFF4FEDDF3FBE5E7E7F2F7D4EFF0D7FBFBF9F8F8F7F5F5F4F8F5E8F6F4ECFFE5C2EFECE8FDE8D1FFD2A6FED3A8FFDAB6FFE5CAFCEEE0F4EFEAF3EEE9F2EDE8EBEAE9FFCFA4FFD0A6FCCFA6E9C5A4FBE5D0F8E2CEF5E0CCFFECDAFAE8D8FEDDC1ECD5C1FBE3CEFAE3CFE8D5C5F0E8E2F1ECE8FCFCFCF9F9F9F7F7F7F3F3F3F0F0F0ECECECE8E8E8D3D3D3FFFFFF00000000000000000000000000000021F9040100003A002C0000000010001000000699C08070482C066AB6A47299BCDD9044994422AB159301993660A345AC5A2D567BC3E142685C2D3C96CD668844E21679CF64C9F04CB26034086E335E37368177100F0203011234131284521233011114153201344A910A0A0E05050706A1053939053317182A2D26AF28272E272F138F01301B1D2B2223BEBE24253512423016291C191ACBCB242C94423320201ED51FD320311146DCDDDE4641003B,'1'),(33,'png','image/png','Image','PNG Image',0x47494638396110001000E60000000000FFFFFFC7BFC0BBB6B7B2ADAFBFBDBFDFDDE4DADAECEBEBEFDFDFE2E6E6E8E3E5F2EAECF5F2F3F7C6CDE1D7DAE3CBD6F2BBC2D4C7CDDCB6BBC8CED2DCC8CACFBFCCE9CFDCFAD5E1FCD1DAEECEDCF7D9E5FEADB5C5E2E8F4DDDFE3DAE6FCEAEEF5A8B0BCE5E8ECC5D3E5E8E9EAECF0F2E2DDD6E9E6E2DED4C8E3DCD3E5DED5EEE7DFE9E6E4E5D1C8DBCBC5CBC8C7BFB9B9D0CECED6D5D5FCFCFCF9F9F9F7F7F7F3F3F3F0F0F0ECECECEAEAEAE7E7E7E5E5E5E2E2E2DDDDDDDADADAD3D3D3FFFFFF00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000021F90401000040002C00000000100010000007AD8001013B3F8586873B82823701338C3739343B398E8A018C3532383F2F3C370A958A3733993C3F0A373D38A18B33339235AEB28CA2AE3A0D3B3A34BB34B4AD36370B05323C393535389637922315042F31080938C9A2391D16132E3015070C0A898A38241718022D140F2111243A96D4201F03061A0E1C1E3C0AEF37262A251B172048F0C1C3C63B1C2752A058212243A36A8A7240CA4191058F191023DED8484D87274B8A6C881C79C320C840003B,'1'),(34,'doc','application/msword','GoogleDocsViewer','Microsoft Word',0x47494638396110001000D50000000000FFFFFFF5F5F7F7F7F8F4F4F5F1F1F2E1E9F9E2E5EBC3D6F7D5E2F9CEDAEFDCE7FBE2EBFBD7DFEDE2E9F6DDE3EEE8EEF9EBEFF6C1D6F8C3D7F9BCCFEFC6D9F9BCCEECCFDCF1D8E5FBD3DFF2D8E3F5E3EBF8E7ECF4EEF2F8ECF1F8E9EEF5F7FAFEF0F3F7EEF1F5E3EBF5E7EEF7EBEFF4E6ECF2EEF1F4F0F2F4F7F8F9F8F8F7FCFBFAF7F6F5FAF9F9F8F7F7F6F5F5FCFCFCF3F3F3F0F0F0ECECECE9E9E9E5E5E5E0E0E0DFDFDFFFFFFF00000000000000000000000000000000000000000021F90401000038002C00000000100010000006AAC040403593198FC79750E85A3A9732A70A964AB5562A55AB058B2E5581566703494148A9AED3051B383CA99223125A7999308143E07239422F2D77012E2B2C111F2C2C23282F2983858A041F272327042E832C302C28042C041C28312F334E9B0411272F2F1C273231A64B812F04222822250546344E042931C131BB353736BD4BBF2C7D2E2F323737354F1A18060912D8140F044F011A200B0C1513081607DD0123261917ED0A0DDD41003B,'1'),(35,'docx','application/vnd.openxmlformats-officedocument.wordprocessingml.document','GoogleDocsViewer','Microsoft Word',0x47494638396110001000D50000000000FFFFFFF5F5F7F7F7F8F4F4F5F1F1F2E1E9F9E2E5EBC3D6F7D5E2F9CEDAEFDCE7FBE2EBFBD7DFEDE2E9F6DDE3EEE8EEF9EBEFF6C1D6F8C3D7F9BCCFEFC6D9F9BCCEECCFDCF1D8E5FBD3DFF2D8E3F5E3EBF8E7ECF4EEF2F8ECF1F8E9EEF5F7FAFEF0F3F7EEF1F5E3EBF5E7EEF7EBEFF4E6ECF2EEF1F4F0F2F4F7F8F9F8F8F7FCFBFAF7F6F5FAF9F9F8F7F7F6F5F5FCFCFCF3F3F3F0F0F0ECECECE9E9E9E5E5E5E0E0E0DFDFDFFFFFFF00000000000000000000000000000000000000000021F90401000038002C00000000100010000006AAC040403593198FC79750E85A3A9732A70A964AB5562A55AB058B2E5581566703494148A9AED3051B383CA99223125A7999308143E07239422F2D77012E2B2C111F2C2C23282F2983858A041F272327042E832C302C28042C041C28312F334E9B0411272F2F1C273231A64B812F04222822250546344E042931C131BB353736BD4BBF2C7D2E2F323737354F1A18060912D8140F044F011A200B0C1513081607DD0123261917ED0A0DDD41003B,'1'),(36,'mov','video/mpeg',NULL,'MPEG Movie',0x47494638396110001000D50000000000FFFFFFF7F7F8EEEFF1E2E5E9CED6DFC8DAECE9ECEFEEF0F2B2D5F4BEDBF5B9D5EEB9D3EAB3D9F8B5D8F5B9D8F1BBD9F3BEDAF1C2D8EACDD9E3D6E2ECE0E7EDBCDCF4C2DBEED3DFE8DFE8EFE2EAF0EBEFF2E5E9ECBEE2FCBCD8ECCCDDE9DAE6EEE8EBEDBFE2F8E0EBF2E7ECEFEFF1F2EAECEDF4F5F5F1F2F2FCFCFCF9F9F9F6F6F6F3F3F3F0F0F0ECECECE9E9E9E8E8E8E5E5E5E2E2E2DFDFDFDADADAD5D5D5D2D2D2C8C8C8FFFFFF00000000000000000000000000000000000000000021F90401000038002C00000000100010000006A3C04060662B1A8F33A1107512A0620395ABE592BD940154ADE8AAC96C331AAD856DDDC4E29ACB26769557A9B83C7E222B5B2CF90ADE44D9852D262927060E0E1716232F03652729120F191A110A28286E772D0309247B25221C2E98802F210D0307182C161531A30103282B161F2B1B131026263058A2021A1D1E0C0B0533338C4A2E307B082014040129C8C92D7B272797D25842A2D72C7E307F582CE4E47E2CDB0141003B,'1'),(37,'xls','application/vnd.ms-excel','GoogleDocsViewer','Microsoft Excel',0x47494638396110001000D50000000000FFFFFFF6F3F6F9F7F9FBFBFFBFE9BFC8ECC8DAF1DAE8FAE8DEE8DEA9E8A7B3EAB2BEEEBDBEE8BDCDF1CCA7E7A4A6DFA4A6DCA4C6F0C4BEE5BCCCE6CBD6E9D5E0EFDFFCFCFCF9F9F9F5F5F5F3F3F3F0F0F0ECECECEAEAEAE6E6E6E5E5E5E2E2E2DFDFDFFFFFFF00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000021F90401000022002C000000001000100000067AC04020C3D9188FC78C50A85C3A851BE7E0A2D15C30D76B20BACC5C8C592C96CBFC6EAE98F4D8E9058BB3E4A1390C6763DCE1BBDD18B8F8FD031C761C1D17040462447B461D481A1D7655921B1E2020904B1A18039C03021B21211F4F06120B0B0FA910154D4E06080E0C0AB311094F42071605050D0D1314B741003B,'1'),(38,'xlsx','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','GoogleDocsViewer','Microsoft Excel',0x47494638396110001000D50000000000FFFFFFF6F3F6F9F7F9FBFBFFBFE9BFC8ECC8DAF1DAE8FAE8DEE8DEA9E8A7B3EAB2BEEEBDBEE8BDCDF1CCA7E7A4A6DFA4A6DCA4C6F0C4BEE5BCCCE6CBD6E9D5E0EFDFFCFCFCF9F9F9F5F5F5F3F3F3F0F0F0ECECECEAEAEAE6E6E6E5E5E5E2E2E2DFDFDFFFFFFF00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000021F90401000022002C000000001000100000067AC04020C3D9188FC78C50A85C3A851BE7E0A2D15C30D76B20BACC5C8C592C96CBFC6EAE98F4D8E9058BB3E4A1390C6763DCE1BBDD18B8F8FD031C761C1D17040462447B461D481A1D7655921B1E2020904B1A18039C03021B21211F4F06120B0B0FA910154D4E06080E0C0AB311094F42071605050D0D1314B741003B,'1'),(39,'mp3','audio/x-mp3',NULL,'MP3 Audio',0x47494638396110001000D50000000000FFFFFFE5E5E7F7F7F8F4F4F5E7E8F1EEEFF1E4EDFBDFE3E5E3ECEEE4F2ECE0E5E2E5EAE6D7E6D9DCEADEDCE3DDC2DDC4D6E4D7DCE8DDBFDFC1BED7BFCAE3CAC8DAC8D5E5D5D4E3D4D1DED1D5E0D5D9EBD8C6DDC4D8E2D7D3F3CDCFE5C9DCE3DAEBF3E5F8F5E3F0EEEBF7F4F0FCFCFCF9F9F9F6F6F6F3F3F3F0F0F0ECECECEAEAEAE7E7E7E5E5E5E2E2E2DFDFDFDADADAD6D6D6D1D1D1FFFFFF00000000000000000000000000000000000000000000000000000000000000000000000021F90401000033002C00000000100010000006A3C04080F52A1A8F2CA130C54C11502DA809A5520652B2970BA67DA162B1A43215DEC25CACD629B6B2A60225536A3552994A2583BB84E27C48221B08777A63250211131D201E15062655630306250C1A2A0F16049056060328030B192C2517272791427605070A17771827046D4A7628092110151514282862AA03C228120E0D162A2C2E562AA7CE042930D204CCCF4F2A6A2656012BA74F2829CADADB2AE5E66903DB41003B,'1'),(40,'swf','application/x-shockwave-flash',NULL,'Macromedia Flash',0x47494638396110001000D50000000000FFFFFFF4F4F6E5E5E7F1F2F5EFF0F3D9DCE2EEEFF1E9EAECE2E3E5ECEEF1E5E7EAD1D6DCDCE0E5D9DDE2D6DADFD5D9DECFD5DBD3D8DDDBDFE3D9DCDFEAECEEF7F8F9F4F5F6EAEBECA4B4C1B6C1CAC3CED7CBD3DAC2CED7DADFE3E3E8ECADBCC6A9BDCAC4D4DEA7BCC8F5F7F8E5E7E8FCFCFCF9F9F9F6F6F6F3F3F3F0F0F0ECECECEAEAEAE7E7E7E1E1E1DDDDDDDADADAD0D0D0CECECEFFFFFF00000000000000000000000000000000000000000000000000000000000000000000000021F90401000033002C00000000100010000006A0C040A0052B1A8F2DA1302534A556AB9469CA54324D09C762E1489C9CCA80B4C4E05408A8C7006C3D51161A10610E39A9C2A9D34460C96C3E040E28776D1E16011C210D040628077826120426172322050C17189007120A240D1D0C182A03612A27279D11110C08072C2EA7A9B476092FB8A716BB1628282D313230844207BEBE4FB2262B6101C6C82B2D2F268FCD0717174F2D2E2EC46107502B2CE328CD0141003B,'1'),(41,'txt','text/plain','GoogleDocsViewer','Plain Text File',0x47494638396110001000D50000000000FFFFFFFCFCFDD4DAE6DCE4F3DFE5EEE2E5E8F2F4F5EFF1F2CACECFBDBEBBBDBDB7F0F0EEE2E2E0E0E0DFC0BFB4DAD9D3E4E3DDC2C1BEBFBBB5D4D1CDD7D3CEF5F1EEEEDDD3C1B9B5C5BCB8F5F0EEF1EEEDC9BFBCC5B8B5D9C1BDD6BBB8FAFAFAF6F6F6F3F3F3F0F0F0ECECECEAEAEAE7E7E7E3E3E3DADADAD2D2D2D0D0D0FFFFFF00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000021F9040100002B002C0000000010001000000691C04040442A198F488130305A0A05CA8000045A8E408C900954727C3C115155381299CD8D4A87A3608C99A2725942C94C1EA4F728441AED1710180A096E56447D220506030404087A7C2226657D23256286667E2695257986972395A2245BA0A22246262627277A23264825282A29274E231B1B24A5AB287C4E4C16170771BC0E4DC1571A2121712526C14220717ED6D241003B,'1'),(42,'rtf','text/richtext','GoogleDocsViewer','Rich Text File',0x47494638396110001000D50000000000FFFFFFFCFCFDD4DAE6DCE4F3DFE5EEE2E5E8F2F4F5EFF1F2CACECFBDBEBBBDBDB7F0F0EEE2E2E0E0E0DFC0BFB4DAD9D3E4E3DDC2C1BEBFBBB5D4D1CDD7D3CEF5F1EEEEDDD3C1B9B5C5BCB8F5F0EEF1EEEDC9BFBCC5B8B5D9C1BDD6BBB8FAFAFAF6F6F6F3F3F3F0F0F0ECECECEAEAEAE7E7E7E3E3E3DADADAD2D2D2D0D0D0FFFFFF00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000021F9040100002B002C0000000010001000000691C04040442A198F488130305A0A05CA8000045A8E408C900954727C3C115155381299CD8D4A87A3608C99A2725942C94C1EA4F728441AED1710180A096E56447D220506030404087A7C2226657D23256286667E2695257986972395A2245BA0A22246262627277A23264825282A29274E231B1B24A5AB287C4E4C16170771BC0E4DC1571A2121712526C14220717ED6D241003B,'1'),(43,'zip','application/zip',NULL,'Zip File',0x47494638396110001000C40000000000FFFFFFFCFCFCF9F9F9F6F6F6F3F3F3F0F0F0ECECECEAEAEAE7E7E7E5E5E5E2E2E2DADADAD6D6D6D3D3D3D0D0D0CDCDCDC8C8C8C5C5C5BABABAB6B6B6ADADADFFFFFF00000000000000000000000000000000000000000000000000000021F90401000016002C000000001000100000058360101C49699E88281AEB822485CACA62D1240E5408C2BC0682C283D1802478BE008BC76C260D4C43023188AA94BCC11141601EAE50C1002138740783AFEA903D34B868825A7440A70D66823EB5B63B04127A05060A577503040E0F1305090B8F867A898B060C9604575C7A8A13070A040357019A890D128EA1A207AB071114159FA221003B,'1'),(44,'ppsx','application/vnd.openxmlformats-officedocument.presentationml.slideshow',NULL,'Microsoft PowerPoint',0x47494638396110001000D50000C35D0478AFE084A4CC9FA1A5838484F4F3EFBF8B5DDE8A8CE8EBEED4C9C0F3B076E4E2E0E5A971CCCCCCDEDEDED2D590FF7A04BFBFBFD5D5D5FCA151ADB2B5D5C0ADB5BDBDA2DDF5FF9933FFFFFFF67908BB7F92F7D1A9E6E0C9FE860EF1BF92DDCFC3F5B67DDB95A4C6C4C1DCE889F6F6F6CC8A52B9C3CFFFCC99DCCDBFF2B27AFFB655EDEBEAFF7C08E7E7E7A7AAAEFFB66AEBE3BFFF8C10EFEFEFB5B5B5FF8207C5BDBDE1D3C5F9BE7EECAE76F7F7EFD7CBC0A6E1FCC7C5C300000000000021F90405140019002C0000000010001000000699C08C70482C661C92A472996C3490C4128B55721493999236237115AC5A2D56DB884446E888233C2ECD662781A05178CF4AC9F08C15E05D366E335E0D1281770F240722192C2E0B2C84522C331905311D25192E4A910808161414032FA1140404143309381F0A21AF0C392A39060B8F1915181C13102DBEBE1A000E2C42152B2830351ECBCB1A26944233292937D520D3293B0546DCDDDE4641003B,'1'),(45,'ppsx','application/vnd.openxmlformats-officedocument.presentationml.slideshow',NULL,'Microsoft PowerPoint',0x47494638396110001000D50000000000FFFFFFF3D5D6F2D9DEE6D1D8E2E3E5DFE0E2DCDDDFE6E9EED3DEECF7F8F9CEE2F4DFF4FEDDF3FBE5E7E7F2F7D4EFF0D7FBFBF9F8F8F7F5F5F4F8F5E8F6F4ECFFE5C2EFECE8FDE8D1FFD2A6FED3A8FFDAB6FFE5CAFCEEE0F4EFEAF3EEE9F2EDE8EBEAE9FFCFA4FFD0A6FCCFA6E9C5A4FBE5D0F8E2CEF5E0CCFFECDAFAE8D8FEDDC1ECD5C1FBE3CEFAE3CFE8D5C5F0E8E2F1ECE8FCFCFCF9F9F9F7F7F7F3F3F3F0F0F0ECECECE8E8E8D3D3D3FFFFFF00000000000000000000000000000021F9040100003A002C0000000010001000000699C08070482C066AB6A47299BCDD9044994422AB159301993660A345AC5A2D567BC3E142685C2D3C96CD668844E21679CF64C9F04CB26034086E335E37368177100F0203011234131284521233011114153201344A910A0A0E05050706A1053939053317182A2D26AF28272E272F138F01301B1D2B2223BEBE24253512423016291C191ACBCB242C94423320201ED51FD320311146DCDDDE4641003B,'0'),(46,'mp4','video/mpg4',NULL,'MPG4 Movie',0x47494638396110001000D50000000000FFFFFFF7F7F8EEEFF1E2E5E9CED6DFC8DAECE9ECEFEEF0F2B2D5F4BEDBF5B9D5EEB9D3EAB3D9F8B5D8F5B9D8F1BBD9F3BEDAF1C2D8EACDD9E3D6E2ECE0E7EDBCDCF4C2DBEED3DFE8DFE8EFE2EAF0EBEFF2E5E9ECBEE2FCBCD8ECCCDDE9DAE6EEE8EBEDBFE2F8E0EBF2E7ECEFEFF1F2EAECEDF4F5F5F1F2F2FCFCFCF9F9F9F6F6F6F3F3F3F0F0F0ECECECE9E9E9E8E8E8E5E5E5E2E2E2DFDFDFDADADAD5D5D5D2D2D2C8C8C8FFFFFF00000000000000000000000000000000000000000021F90401000038002C00000000100010000006A3C04060662B1A8F33A1107512A0620395ABE592BD940154ADE8AAC96C331AAD856DDDC4E29ACB26769557A9B83C7E222B5B2CF90ADE44D9852D262927060E0E1716232F03652729120F191A110A28286E772D0309247B25221C2E98802F210D0307182C161531A30103282B161F2B1B131026263058A2021A1D1E0C0B0533338C4A2E307B082014040129C8C92D7B272797D25842A2D72C7E307F582CE4E47E2CDB0141003B,'1'),(47,'mp4','video/mpg4',NULL,'MPG4 Movie',0x47494638396110001000D50000666666C7D3DCAEB7C13E86C4E6E6E69999995792C5C1CCD4F7F7F74A99D899B9D0778EA77494B1D8DADC298BE0A7A7A7B5C2CB828282C3C3C34DADED85A7C08B8B8BFFFFFF3292E3AEC5D5D6D6D6BEBEBE3D93D8ADADADCCCCCCBEC6CDEEEEEE4295DEA7BFD24B9BE472A0C22C94ED579BCEE0E2E4469DDF8CADC9D1D4D8B5B5B5C5C9CD3E8BCF4593CBA9BDCDD2D7DBDEDEDEEAEAEB4A9CDEB5C5D64AADF7A9C6DCBDC9D36699CCD0D6DB00000000000000000000000000000000000000000021F90405140016002C00000000100010000006A3408BE511291A8F0FA1B0618A3554A94F27D3E148949646A5D8A970228F422183CD00C4E24A2722EE940988B83C6E222B33303901DE6CD885192B08263717172527351229652608061B211809220D0D6E7719290E367B2F13101D9880121E2429071430272E2AA316290D04272304010C202B2B1A58A23118342D032C0B0F0F8C4A1D1A7B380A28021608C8C9197B262697D25842A2D7307E1A7F5830E4E47E30DB1641003B,'0'),(48,'hmtl5','text/html5',NULL,'HTML Document',0x47494638396110001000E60000000000FFFFFFE5E6E5D7DBD3D5D9D1DEE1DBE3E4E2E6E6E5E6DDD9E2906FE55928E55D2DE56C41E57F5BE58664E58867E59B81E6A48DE6CAC0E5CBC1E8D0C7E6CEC5D8C4BDE56235E68868E59174E6A690E6B7A7E6BFB2E6C0B3D9C4BDE6D3CDE6DAD6E6DCD9FEFEFEF7F7F7F6F6F6F1F1F1EFEFEFEEEEEEEDEDEDECECECEBEBEBEAEAEAE9E9E9E8E8E8E7E7E7E6E6E6E4E4E4E3E3E3E1E1E1E0E0E0DFDFDFDEDEDEDBDBDBDADADAD9D9D9D8D8D8D7D7D7D6D6D6CCCCCCC4C4C4BDBDBDAFAFAFABABABA2A2A2A1A1A1A0A0A09F9F9F9D9D9D9B9B9B9999997D7D7D767676FFFFFF00000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000021F9040100004A002C00000000100010000007B98001013E4644424446454388473E82823523272C313836302925243B8F0133292FA10202A12E259C8222322E2F100C1F120B2113283A9D320F0A191D2F111A1C1914B68F3318BABC0F1020190E1E9D342DADAF0C1B08CC16CF2907A1080A080D09153D9D3A26A42F1B172F06043B8E8F3B282C02DB020503393D409D3B26282A2B56A8B8F12388417E264229F48124C911548276287C5183C73E133C3A059018AAA28F202320C2EBC8C307102022E1F158D9A3E50B8D010201003B,'0'),(49,'hmtl5','text/html5',NULL,'HTML Document',0x47494638396110001000D50000000000FFFFFFEEF0EDEBEDE9EAECE8F1F1F0F2EEECF2AC93F2AE96F2BFADF2C2B1F2C8B9F0C7B7F2CDC0F2DBD3F2DFD8F2E4DFF2E5E0F2B09AF2B5A0F2C3B3F2D1C6F2D2C7F2DFD9F3E7E3F2E6E2F2E9E6F2ECEAECE1DEEBE1DEF2EDECFEFEFEFBFBFBFAFAFAF8F8F8F7F7F7F6F6F6F5F5F5F4F4F4F3F3F3F2F2F2F1F1F1F0F0F0EFEFEFEEEEEEEDEDEDECECECEBEBEBEAEAEAE5E5E5E1E1E1DEDEDED7D7D7D5D5D5D0D0D0CFCFCFCECECECDCDCDCCCCCCBEBEBEBABABAFFFFFF00000000000021F9040100003D002C00000000100010000006A2C0406096BBD96E39DC0DA99B09852C10C99472B552255108F60CAC4AA8B018751271851FD509D5986820084F84F4EAAA2887C50555B13C1618754F2B787A28140D1B0B0A1C5D2B6B6D1A130E068B1D8E6061060706090C19325D2F23620E12280504304E4F30536302032F32355D302324252626252E3436C0B6A463333B3C3A674230632C31B523315D01CB61CD333620C9ADD531333535DAAD31E332E528D20141003B,'1');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `global_lu_buildings` (
  `building_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `organisation_id` int(11) unsigned NOT NULL,
  `building_code` varchar(16) NOT NULL DEFAULT '',
  `building_name` varchar(128) NOT NULL DEFAULT '',
  `building_address1` varchar(128) NOT NULL DEFAULT '',
  `building_address2` varchar(128) NOT NULL DEFAULT '',
  `building_city` varchar(64) NOT NULL DEFAULT '',
  `building_province` varchar(64) NOT NULL DEFAULT '',
  `building_country` varchar(64) NOT NULL DEFAULT '',
  `building_postcode` varchar(16) NOT NULL DEFAULT '',
  PRIMARY KEY (`building_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `global_lu_community_type_options` (
  `ctoption_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `option_shortname` varchar(32) NOT NULL DEFAULT '',
  `option_name` varchar(84) NOT NULL DEFAULT '',
  PRIMARY KEY (`ctoption_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `global_lu_community_type_options` VALUES (1,'course_website','Course Website Functionality'),(2,'sequential_navigation','Learning Module Sequential Navigation');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `global_lu_community_types` (
  `ctype_id` int(12) NOT NULL AUTO_INCREMENT,
  `community_type_name` varchar(84) DEFAULT NULL,
  `default_community_template` varchar(30) NOT NULL DEFAULT 'default',
  `default_community_theme` varchar(12) NOT NULL DEFAULT 'default',
  `default_community_keywords` varchar(255) NOT NULL DEFAULT '',
  `default_community_protected` int(1) NOT NULL DEFAULT '1',
  `default_community_registration` int(1) NOT NULL DEFAULT '1',
  `default_community_members` text NOT NULL,
  `default_mail_list_type` enum('announcements','discussion','inactive') NOT NULL DEFAULT 'inactive',
  `default_community_type_options` text NOT NULL,
  `community_type_active` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`ctype_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `global_lu_community_types` VALUES (1,'Other','default','default','',1,0,'','inactive','{}',1),(2,'Course Website','course','course','',1,0,'','inactive','{\"course_website\":\"1\"}',1),(3,'Online Learning Module','learningmodule','default','',1,0,'','inactive','{\"sequential_navigation\":\"1\"}',1);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `global_lu_countries` (
  `countries_id` int(6) NOT NULL AUTO_INCREMENT,
  `country` varchar(250) NOT NULL DEFAULT '',
  `abbreviation` varchar(3) NOT NULL,
  `iso2` varchar(2) NOT NULL,
  `isonum` int(6) NOT NULL,
  PRIMARY KEY (`countries_id`),
  KEY `abbr_idx` (`abbreviation`),
  KEY `iso2_idx` (`iso2`),
  KEY `isonum_idx` (`isonum`)
) ENGINE=MyISAM AUTO_INCREMENT=242 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `global_lu_countries` VALUES (1,'Afghanistan','AFG','AF',4),(2,'Aland Islands','ALA','AX',248),(3,'Albania','ALB','AL',8),(4,'Algeria','DZA','DZ',12),(5,'American Samoa','ASM','AS',16),(6,'Andorra','AND','AD',20),(7,'Angola','AGO','AO',24),(8,'Anguilla','AIA','AI',660),(9,'Antarctica','ATA','AQ',10),(10,'Antigua and Barbuda','ATG','AG',28),(11,'Argentina','ARG','AR',32),(12,'Armenia','ARM','AM',51),(13,'Aruba','ABW','AW',533),(14,'Australia','AUS','AU',36),(15,'Austria','AUT','AT',40),(16,'Azerbaijan','AZE','AZ',31),(17,'Bahamas','BHS','BS',44),(18,'Bahrain','BHR','BH',48),(19,'Bangladesh','BGD','BD',50),(20,'Barbados','BRB','BB',52),(21,'Belarus','BLR','BY',112),(22,'Belgium','BEL','BE',56),(23,'Belize','BLZ','BZ',84),(24,'Benin','BEN','BJ',204),(25,'Bermuda','BMU','BM',60),(26,'Bhutan','BTN','BT',64),(27,'Bolivia','BOL','BO',68),(28,'Bosnia and Herzegovina','BIH','BA',70),(29,'Botswana','BWA','BW',72),(30,'Bouvet Island','BVT','BV',74),(31,'Brazil','BRA','BR',76),(32,'British Indian Ocean territory','IOT','IO',86),(33,'Brunei Darussalam','BRN','BN',96),(34,'Bulgaria','BGR','BG',100),(35,'Burkina Faso','BFA','BF',854),(36,'Burundi','BDI','BI',108),(37,'Cambodia','KHM','KH',116),(38,'Cameroon','CMR','CM',120),(39,'Canada','CAN','CA',124),(40,'Cape Verde','CPV','CV',132),(41,'Cayman Islands','CYM','KY',136),(42,'Central African Republic','CAF','CF',140),(43,'Chad','TCD','TD',148),(44,'Chile','CHL','CL',152),(45,'China','CHN','CN',156),(46,'Christmas Island','CXR','CX',162),(47,'Cocos (Keeling) Islands','CCK','CC',166),(48,'Colombia','COL','CO',170),(49,'Comoros','COM','KM',174),(50,'Congo','COG','CG',178),(51,'Congo','COG','CG',178),(52,'Democratic Republic','COD','CD',180),(53,'Cook Islands','COK','CK',184),(54,'Costa Rica','CRI','CR',188),(55,'Cote D\'Ivoire (Ivory Coast)','CIV','CI',384),(56,'Croatia (Hrvatska)','HRV','HR',191),(57,'Cuba','CUB','CU',192),(58,'Cyprus','CYP','CY',196),(59,'Czech Republic','CZE','CZ',203),(60,'Denmark','DNK','DK',208),(61,'Djibouti','DJI','DJ',262),(62,'Dominica','DMA','DM',212),(63,'Dominican Republic','DOM','DO',214),(64,'Timor-Leste','TLS','TL',626),(65,'Ecuador','ECU','EC',218),(66,'Egypt','EGY','EG',818),(67,'El Salvador','SLV','SV',222),(68,'Equatorial Guinea','GNQ','GQ',226),(69,'Eritrea','ERI','ER',232),(70,'Estonia','EST','EE',233),(71,'Ethiopia','ETH','ET',231),(72,'Falkland Islands','FLK','FK',238),(73,'Faroe Islands','FRO','FO',234),(74,'Fiji','FJI','FJ',242),(75,'Finland','FIN','FI',246),(76,'France','FRA','FR',250),(77,'French Guiana','GUF','GF',254),(78,'French Polynesia','PYF','PF',258),(79,'French Southern Territories','ATF','TF',260),(80,'Gabon','GAB','GA',266),(81,'Gambia','GMB','GM',270),(82,'Georgia','GEO','GE',268),(83,'Germany','DEU','DE',276),(84,'Ghana','GHA','GH',288),(85,'Gibraltar','GIB','GI',292),(86,'Greece','GRC','GR',300),(87,'Greenland','GRL','GL',304),(88,'Grenada','GRD','GD',308),(89,'Guadeloupe','GLP','GP',312),(90,'Guam','GUM','GU',316),(91,'Guatemala','GTM','GT',320),(92,'Guinea','GIN','GN',324),(93,'Guinea-Bissau','GNB','GW',624),(94,'Guyana','GUY','GY',328),(95,'Haiti','HTI','HT',332),(96,'Heard and McDonald Islands','HMD','HM',334),(97,'Honduras','HND','HN',340),(98,'Hong Kong','HKG','HK',344),(99,'Hungary','HUN','HU',348),(100,'Iceland','ISL','IS',352),(101,'India','IND','IN',356),(102,'Indonesia','IDN','ID',360),(103,'Iran','IRN','IR',364),(104,'Iraq','IRQ','IQ',368),(105,'Ireland','IRL','IE',372),(106,'Israel','ISR','IL',376),(107,'Italy','ITA','IT',380),(108,'Jamaica','JAM','JM',388),(109,'Japan','JPN','JP',392),(110,'Jordan','JOR','JO',400),(111,'Kazakhstan','KAZ','KZ',398),(112,'Kenya','KEN','KE',404),(113,'Kiribati','KIR','KI',296),(114,'Korea (north)','PRK','KP',408),(115,'Korea (south)','KOR','KR',410),(116,'Kuwait','KWT','KW',414),(117,'Kyrgyzstan','KGZ','KG',417),(118,'Lao People\'s Democratic Republic','LAO','LA',418),(119,'Latvia','LVA','LV',428),(120,'Lebanon','LBN','LB',422),(121,'Lesotho','LSO','LS',426),(122,'Liberia','LBR','LR',430),(123,'Libyan Arab Jamahiriya','LBY','LY',434),(124,'Liechtenstein','LIE','LI',438),(125,'Lithuania','LTU','LT',440),(126,'Luxembourg','LUX','LU',442),(127,'Macao','MAC','MO',446),(128,'Macedonia','MKD','MK',807),(129,'Madagascar','MDG','MG',450),(130,'Malawi','MWI','MW',454),(131,'Malaysia','MYS','MY',458),(132,'Maldives','MDV','MV',462),(133,'Mali','MLI','ML',466),(134,'Malta','MLT','MT',470),(135,'Marshall Islands','MHL','MH',584),(136,'Martinique','MTQ','MQ',474),(137,'Mauritania','MRT','MR',478),(138,'Mauritius','MUS','MU',480),(139,'Mayotte','MYT','YT',175),(140,'Mexico','MEX','MX',484),(141,'Micronesia','FSM','FM',583),(142,'Moldova','MDA','MD',498),(143,'Monaco','MCO','MC',492),(144,'Mongolia','MNG','MN',496),(145,'Montserrat','MSR','MS',500),(146,'Morocco','MAR','MA',504),(147,'Mozambique','MOZ','MZ',508),(148,'Myanmar','MMR','MM',104),(149,'Namibia','NAM','NA',516),(150,'Nauru','NRU','NR',520),(151,'Nepal','NPL','NP',524),(152,'Netherlands','NLD','NL',528),(153,'Netherlands Antilles','CUW','CW',531),(154,'New Caledonia','NCL','NC',540),(155,'New Zealand','NZL','NZ',554),(156,'Nicaragua','NIC','NI',558),(157,'Niger','NER','NE',562),(158,'Nigeria','NGA','NG',566),(159,'Niue','NIU','NU',570),(160,'Norfolk Island','NFK','NF',574),(161,'Northern Mariana Islands','MNP','MP',580),(162,'Norway','NOR','NO',578),(163,'Oman','OMN','OM',512),(164,'Pakistan','PAK','PK',586),(165,'Palau','PLW','PW',585),(166,'Palestinian Territories','PSE','PS',275),(167,'Panama','PAN','PA',591),(168,'Papua New Guinea','PNG','PG',598),(169,'Paraguay','PRY','PY',600),(170,'Peru','PER','PE',604),(171,'Philippines','PHL','PH',608),(172,'Pitcairn','PCN','PN',612),(173,'Poland','POL','PL',616),(174,'Portugal','PRT','PT',620),(175,'Puerto Rico','PRI','PR',630),(176,'Qatar','QAT','QA',634),(177,'Reunion','REU','RE',638),(178,'Romania','ROU','RO',642),(179,'Russian Federation','RUS','RU',643),(180,'Rwanda','RWA','RW',646),(181,'Saint Helena','SHN','SH',654),(182,'Saint Kitts and Nevis','KNA','KN',659),(183,'Saint Lucia','LCA','LC',662),(184,'Saint Pierre and Miquelon','SPM','PM',666),(185,'Saint Vincent and the Grenadines','VCT','VC',670),(186,'Samoa','WSM','WS',882),(187,'San Marino','SMR','SM',674),(188,'Sao Tome and Principe','STP','ST',678),(189,'Saudi Arabia','SAU','SA',682),(190,'Senegal','SEN','SN',686),(191,'Serbia and Montenegro','SRB','RS',688),(192,'Seychelles','SYC','SC',690),(193,'Sierra Leone','SLE','SL',694),(194,'Singapore','SGP','SG',702),(195,'Slovakia','SVK','SK',703),(196,'Slovenia','SVN','SI',705),(197,'Solomon Islands','SLB','SB',90),(198,'Somalia','SOM','SO',706),(199,'South Africa','ZAF','ZA',710),(200,'South Georgia and the South Sandwich Islands','SGS','GS',239),(201,'Spain','ESP','ES',724),(202,'Sri Lanka','LKA','LK',144),(203,'Sudan','SDN','SD',729),(204,'Suriname','SUR','SR',740),(205,'Svalbard and Jan Mayen Islands','SJM','SJ',744),(206,'Swaziland','SWZ','SZ',748),(207,'Sweden','SWE','SE',752),(208,'Switzerland','CHE','CH',756),(209,'Syria','SYR','SY',760),(210,'Taiwan','TWN','TW',158),(211,'Tajikistan','TJK','TJ',762),(212,'Tanzania','TZA','TZ',834),(213,'Thailand','THA','TH',764),(214,'Togo','TGO','TG',768),(215,'Tokelau','TKL','TK',772),(216,'Tonga','TON','TO',776),(217,'Trinidad and Tobago','TTO','TT',780),(218,'Tunisia','TUN','TN',788),(219,'Turkey','TUR','TR',792),(220,'Turkmenistan','TKM','TM',795),(221,'Turks and Caicos Islands','TCA','TC',796),(222,'Tuvalu','TUV','TV',798),(223,'Uganda','UGA','UG',800),(224,'Ukraine','UKR','UA',804),(225,'United Arab Emirates','ARE','AE',784),(226,'United Kingdom','GBR','GB',826),(227,'United States of America','USA','US',840),(228,'Uruguay','URY','UY',858),(229,'Uzbekistan','UZB','UZ',860),(230,'Vanuatu','VUT','VU',548),(231,'Vatican City','VAT','VA',336),(232,'Venezuela','VEN','VE',862),(233,'Vietnam','VNM','VN',704),(234,'Virgin Islands (British)','VGB','VG',92),(235,'Virgin Islands (US)','VIR','VI',850),(236,'Wallis and Futuna Islands','WLF','WF',876),(237,'Western Sahara','ESH','EH',732),(238,'Yemen','YEM','YE',887),(239,'Congo, Democratic Republic of the','COD','CD',180),(240,'Zambia','ZMB','ZM',894),(241,'Zimbabwe','ZWE','ZW',716);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `global_lu_disciplines` (
  `discipline_id` int(11) NOT NULL AUTO_INCREMENT,
  `discipline` varchar(250) NOT NULL,
  PRIMARY KEY (`discipline_id`)
) ENGINE=MyISAM AUTO_INCREMENT=65 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `global_lu_disciplines` VALUES (1,'Adolescent Medicine'),(2,'Anatomical Pathology'),(3,'Anesthesiology'),(4,'Cardiac Surgery'),(5,'Cardiology'),(6,'Child & Adolescent Psychiatry'),(7,'Clinical Immunology and Allergy'),(8,'Clinical Pharmacology'),(9,'Colorectal Surgery'),(10,'Community Medicine'),(11,'Critical Care Medicine'),(12,'Dermatology'),(13,'Developmental Pediatrics'),(14,'Diagnostic Radiology'),(15,'Emergency Medicine'),(16,'Endocrinology and Metabolism'),(17,'Family Medicine'),(18,'Forensic Pathology'),(19,'Forensic Psychiatry'),(20,'Gastroenterology'),(21,'General Pathology'),(22,'General Surgery'),(23,'General Surgical Oncology'),(24,'Geriatric Medicine'),(25,'Geriatric Psychiatry'),(26,'Gynecologic Oncology'),(27,'Gynecologic Reproductive Endocrinology and Infertility'),(28,'Hematological Pathology '),(29,'Hematology'),(30,'Infectious Disease'),(31,'Internal Medicine'),(32,'Maternal-Fetal Medicine'),(33,'Medical Biochemistry'),(34,'Medical Genetics'),(35,'Medical Microbiology'),(36,'Medical Oncology'),(37,'Neonatal-Perinatal Medicine'),(38,'Nephrology'),(39,'Neurology'),(40,'Neuropathology'),(41,'Neuroradiology'),(42,'Neurosurgery'),(43,'Nuclear Medicine'),(44,'Obstetrics & Gynecology'),(45,'Occupational Medicine'),(46,'Ophthalmology'),(47,'Orthopedic Surgery'),(48,'Otolaryngology-Head and Neck Surgery'),(49,'Palliative Medicine'),(50,'Pediatric Emergency Medicine'),(51,'Pediatric General Surgery'),(52,'Pediatric Hematology/Oncology'),(53,'Pediatric Radiology'),(54,'Pediatrics'),(55,'Physical Medicine and Rehabilitation'),(56,'Plastic Surgery'),(57,'Psychiatry'),(58,'Radiation Oncology'),(59,'Respirology'),(60,'Rheumatology'),(61,'Thoracic Surgery'),(62,'Transfusion Medicine'),(63,'Urology'),(64,'Vascular Surgery');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `global_lu_focus_groups` (
  `group_id` int(11) NOT NULL AUTO_INCREMENT,
  `focus_group` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`group_id`),
  KEY `focus_group` (`focus_group`)
) ENGINE=MyISAM AUTO_INCREMENT=25 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `global_lu_focus_groups` VALUES (1,'Cancer'),(2,'Neurosciences'),(3,'Cardiovascular, Circulatory and Respiratory'),(4,'Gastrointestinal'),(5,'Musculoskeletal\n'),(6,'Health Services Research'),(15,'Other'),(7,'Protein Function and Discovery'),(8,'Reproductive Sciences'),(9,'Genetics'),(10,'Nursing'),(11,'Primary Care Studies'),(12,'Emergency'),(13,'Critical Care'),(14,'Nephrology'),(16,'Educational Research'),(17,'Microbiology and Immunology'),(18,'Urology'),(19,'Psychiatry'),(20,'Anesthesiology'),(22,'Obstetrics and Gynecology'),(23,'Rehabilitation Therapy'),(24,'Occupational Therapy');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `global_lu_hospital_location` (
  `hosp_id` int(11) NOT NULL DEFAULT '0',
  `hosp_desc` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`hosp_id`),
  KEY `hosp_desc` (`hosp_desc`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `global_lu_objective_sets` (
  `objective_set_id` int(12) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text,
  `shortname` varchar(128) NOT NULL,
  `start_date` bigint(64) DEFAULT NULL,
  `end_date` bigint(64) DEFAULT NULL,
  `standard` tinyint(1) DEFAULT '0',
  `created_date` bigint(64) NOT NULL,
  `created_by` int(12) NOT NULL,
  `updated_date` bigint(64) DEFAULT NULL,
  `updated_by` int(12) DEFAULT NULL,
  `deleted_date` bigint(64) DEFAULT NULL,
  PRIMARY KEY (`objective_set_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `global_lu_objective_sets` VALUES (1,'Entrusbable Professional Activities','Entrusbable Professional Activities','epa',NULL,NULL,0,1483495904,1,NULL,NULL,NULL),(2,'Key Competencies','Key Competencies','kc',NULL,NULL,1,1483495904,1,NULL,NULL,NULL),(3,'Enabling Competencies','Enabling Competencies','ec',NULL,NULL,1,1483495904,1,NULL,NULL,NULL),(4,'Milestones','Milestones','milestone',NULL,NULL,0,1483495904,1,NULL,NULL,NULL);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `global_lu_objectives` (
  `objective_id` int(12) NOT NULL AUTO_INCREMENT,
  `objective_code` varchar(24) DEFAULT NULL,
  `objective_name` varchar(240) NOT NULL DEFAULT '',
  `objective_description` text,
  `objective_secondary_description` text,
  `objective_parent` int(12) NOT NULL DEFAULT '0',
  `objective_set_id` int(12) NOT NULL,
  `objective_order` int(12) NOT NULL DEFAULT '0',
  `objective_loggable` tinyint(1) NOT NULL DEFAULT '0',
  `objective_active` int(12) NOT NULL DEFAULT '1',
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(12) NOT NULL,
  PRIMARY KEY (`objective_id`),
  KEY `objective_order` (`objective_order`),
  KEY `objective_code` (`objective_code`),
  KEY `idx_parent` (`objective_parent`,`objective_active`),
  FULLTEXT KEY `ft_objective_search` (`objective_code`,`objective_name`,`objective_description`)
) ENGINE=MyISAM AUTO_INCREMENT=2403 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `global_lu_objectives` VALUES (1,NULL,'Curriculum Objectives','',NULL,0,0,2,0,1,0,0),(2,NULL,'Medical Expert','',NULL,1,0,0,0,1,0,0),(3,NULL,'Professionalism','',NULL,1,0,0,0,1,0,0),(4,NULL,'Scholar','',NULL,1,0,0,0,1,0,0),(5,NULL,'Communicator','',NULL,1,0,0,0,1,0,0),(6,NULL,'Collaborator','',NULL,1,0,0,0,1,0,0),(7,NULL,'Advocate','',NULL,1,0,0,0,1,0,0),(8,NULL,'Manager','',NULL,1,0,0,0,1,0,0),(9,NULL,'Application of Basic Sciences','The competent medical graduate articulates and uses the basic sciences to inform disease prevention, health promotion and the assessment and management of patients presenting with clinical illness.',NULL,2,0,0,0,1,0,0),(10,NULL,'Clinical Assessment','Is able to perform a complete and appropriate clinical assessment of patients presenting with clinical illness',NULL,2,0,1,0,1,0,0),(11,NULL,'Clinical Presentations','Is able to appropriately assess and provide initial management for patients presenting with clinical illness, as defined by the Medical Council of Canada Clinical Presentations',NULL,2,0,2,0,1,0,0),(12,NULL,'Health Promotion','Apply knowledge of disease prevention and health promotion to the care of patients',NULL,2,0,3,0,1,0,0),(13,NULL,'Professional Behaviour','Demonstrates appropriate professional behaviours to serve patients, the profession, and society',NULL,3,0,0,0,1,0,0),(14,NULL,'Principles of Professionalism','Apply knowledge of legal and ethical principles to serve patients, the profession, and society',NULL,3,0,1,0,1,0,0),(15,NULL,'Critical Appraisal','Critically evaluate medical information and its sources (the literature)',NULL,4,0,0,0,1,0,0),(16,NULL,'Research','Contribute to the process of knowledge creation (research)',NULL,4,0,1,0,1,0,0),(17,NULL,'Life Long Learning','Engages in life long learning',NULL,4,0,2,0,1,0,0),(18,NULL,'Effective Communication','Effectively communicates with colleagues, other health professionals, patients, families and other caregivers',NULL,5,0,0,0,1,0,0),(19,NULL,'Effective Collaboration','Effectively collaborate with colleagues and other health professionals',NULL,6,0,0,0,1,0,0),(20,NULL,'Determinants of Health','Articulate and apply the determinants of health and disease, principles of health promotion and disease prevention',NULL,7,0,0,0,1,0,0),(21,NULL,'Profession and Community','Effectively advocate for their patients, the profession, and community',NULL,7,0,1,0,1,0,0),(22,NULL,'Practice Options','Describes a variety of practice options and settings within the practice of Medicine',NULL,8,0,0,0,1,0,0),(23,NULL,'Balancing Health and Profession','Balances personal health and professional responsibilities',NULL,8,0,1,0,1,0,0),(24,NULL,'ME1.1 Homeostasis & Dysregulation','Applies knowledge of molecular, biochemical, cellular, and systems-level mechanisms that maintain homeostasis, and of the dysregulation of these mechanisms, to the prevention, diagnosis, and management of disease.',NULL,9,0,0,0,1,0,0),(25,NULL,'ME1.2 Physics and Chemistry','Apply major principles of physics and chemistry to explain normal biology, the pathobiology of significant diseases, and the mechanism of action of major technologies used in the prevention, diagnosis, and treatment of disease.',NULL,9,0,1,0,1,0,0),(26,NULL,'ME1.3 Genetics','Use the principles of genetic transmission, molecular biology of the human genome, and population genetics to guide assessments and clinical decision making.',NULL,9,0,2,0,1,0,0),(27,NULL,'ME1.4 Defense Mechanisms','Apply the principles of the cellular and molecular basis of immune and nonimmune host defense mechanisms in health and disease to determine the etiology of disease, identify preventive measures, and predict response to therapies.',NULL,9,0,3,0,1,0,0),(28,NULL,'ME1.5 Pathological Processes','Apply the mechanisms of general and disease-specific pathological processes in health and disease to the prevention, diagnosis, management, and prognosis of critical human disorders.',NULL,9,0,4,0,1,0,0),(29,NULL,'ME1.6 Microorganisms','Apply principles of the biology of microorganisms in normal physiology and disease to explain the etiology of disease, identify preventive measures, and predict response to therapies.',NULL,9,0,5,0,1,0,0),(30,NULL,'ME1.7 Pharmacology','Apply the principles of pharmacology to evaluate options for safe, rational, and optimally beneficial drug therapy.',NULL,9,0,6,0,1,0,0),(32,NULL,'ME2.1 History and Physical','Conducts a comprehensive and appropriate history and physical examination ',NULL,10,0,0,0,1,0,0),(33,NULL,'ME2.2 Procedural Skills','Demonstrate proficient and appropriate use of selected procedural skills, diagnostic and therapeutic',NULL,10,0,1,0,1,0,0),(34,NULL,'ME3.x Clinical Presentations','',NULL,11,0,0,0,1,0,0),(35,NULL,'ME4.1 Health Promotion & Maintenance','',NULL,12,0,0,0,1,0,0),(36,NULL,'P1.1 Professional Behaviour','Practice appropriate professional behaviours, including honesty, integrity, commitment, dependability, compassion, respect, an understanding of the human condition, and altruism in the educational  and clinical settings',NULL,13,0,0,0,1,0,0),(37,NULL,'P1.2 Patient-Centered Care','Learn how to deliver the highest quality patient-centered care, with commitment to patients\' well being.  ',NULL,13,0,1,0,1,0,0),(38,NULL,'P1.3 Self-Awareness','Is self-aware, engages consultancy appropriately and maintains competence',NULL,13,0,2,0,1,0,0),(39,NULL,'P2.1 Ethics','Analyze ethical issues encountered in practice (such as informed consent, confidentiality, truth telling, vulnerable populations, etc.)',NULL,14,0,0,0,1,0,0),(40,NULL,'P2.2 Law and Regulation','Apply profession-led regulation to serve patients, the profession and society. ',NULL,14,0,1,0,1,0,0),(41,NULL,'S1.1 Information Retrieval','Are able to retrieve medical information efficiently and effectively',NULL,15,0,0,0,1,0,0),(42,NULL,'S1.2 Critical Evaluation','Critically evaluate the validity and applicability of medical procedures and therapeutic modalities to patient care',NULL,15,0,1,0,1,0,0),(43,NULL,'S2.1 Research Methodology','Adopt rigorous research methodology and scientific inquiry procedures',NULL,16,0,0,0,1,0,0),(44,NULL,'S2.2 Sharing Innovation','Prepares and disseminates new medical information',NULL,16,0,1,0,1,0,0),(45,NULL,'S3.1 Learning Strategies','Implements effective personal learning experiences including the capacity to engage in reflective learning',NULL,17,0,0,0,1,0,0),(46,NULL,'CM1.1 Therapeutic Relationships','Demonstrate skills and attitudes to foster rapport, trust and ethical therapeutic relationships with patients and families',NULL,18,0,0,0,1,0,0),(47,NULL,'CM1.2 Eliciting Perspectives','Elicit and synthesize relevant information and perspectives of patients and families, colleagues and other professionals',NULL,18,0,1,0,1,0,0),(48,NULL,'CM1.3 Conveying Information','Convey relevant information and explanations appropriately to patients and families, colleagues and other professionals, orally and in writing',NULL,18,0,2,0,1,0,0),(49,NULL,'CM1.4 Finding Common Ground','Develop a common understanding on issues, problems, and plans with patients and families, colleagues and other professionals to develop a shared plan of care',NULL,18,0,3,0,1,0,0),(50,NULL,'CL 1.1 Working In Teams','Participate effectively and appropriately as part of a multiprofessional healthcare team.',NULL,19,0,0,0,1,0,0),(51,NULL,'CL1.2 Overcoming Conflict','Work with others effectively in order to prevent, negotiate, and resolve conflict.',NULL,19,0,1,0,1,0,0),(52,NULL,'CL1.3 Including Patients and Families','Includes patients and families in prevention and management of illness',NULL,19,0,2,0,1,0,0),(53,NULL,'CL1.4 Teaching and Learning','Teaches and learns from others consistently  ',NULL,19,0,3,0,1,0,0),(54,NULL,'A1.1 Applying Determinants of Health','Apply knowledge of the determinants of health for populations to medical encounters and problems.',NULL,20,0,0,0,1,0,0),(55,NULL,'A2.1 Community Resources','Identify and communicate about community resources to promote health, prevent disease, and manage illness in their patients and the communities they will serve.',NULL,21,0,0,0,1,0,0),(56,NULL,'A2.2 Responsibility and Service','Integrate the principles of advocacy into their understanding of their professional responsibility to patients and the communities they will serve. ',NULL,21,0,1,0,1,0,0),(57,NULL,'M1.1 Career Settings','Is aware of the variety of practice options and settings within the practice of Medicine, and makes informed personal choices regarding career direction',NULL,22,0,0,0,1,0,0),(58,NULL,'M2.1 Work / Life Balance','Identifies and implement strategies that promote care of one\'s self and one\'s colleagues to maintain balance between personal and educational/ professional commitments',NULL,23,0,0,0,1,0,0),(59,NULL,'ME1.1a','Apply knowledge of biological systems and their interactions to explain how the human body functions in health and disease. ',NULL,24,0,0,0,1,0,0),(60,NULL,'ME1.1b','Use the principles of feedback control to explain how specific homeostatic and reproductive systems maintain the internal environment and identify (1) how perturbations in these systems may result in disease and (2) how homeostasis may be changed by disease.',NULL,24,0,1,0,1,0,0),(61,NULL,'ME1.1c','Apply knowledge of the atomic and molecular characteristics of biological constituents to predict normal and pathological molecular function.',NULL,24,0,2,0,1,0,0),(62,NULL,'ME1.1d','Explain how the regulation of major biochemical energy production pathways and the synthesis/degradation of macromolecules function to maintain health and identify major forms of dysregulation in disease.',NULL,24,0,3,0,1,0,0),(63,NULL,'ME1.1e','Explain the major mechanisms of intra- and intercellular communication and their role in health and disease states.',NULL,24,0,4,0,1,0,0),(64,NULL,'ME1.1f','Apply an understanding of the morphological and biochemical events that occur when somatic or germ cells divide, and the mechanisms that regulate cell division and cell death, to explain normal and abnormal growth and development.',NULL,24,0,5,0,1,0,0),(65,NULL,'ME1.1g','Identify and describe the common and unique microscopic and three dimensional macroscopic structures of macromolecules, cells, tissues, organs, systems, and compartments that lead to their unique and integrated function from fertilization through senescence to explain how perturbations contribute to disease. ',NULL,24,0,6,0,1,0,0),(66,NULL,'ME1.1h','Predict the consequences of structural variability and damage or loss of tissues and organs due to maldevelopment, trauma, disease, and aging.',NULL,24,0,7,0,1,0,0),(67,NULL,'ME1.1i','Apply principles of information processing at the molecular, cellular, and integrated nervous system level and understanding of sensation, perception, decision making, action, and cognition to explain behavior in health and disease.',NULL,24,0,8,0,1,0,0),(68,NULL,'ME1.2a','Apply the principles of physics and chemistry, such as mass flow, transport, electricity, biomechanics, and signal detection and processing, to the specialized functions of membranes, cells, tissues, organs, and the human organism, and recognize how perturbations contribute to disease.',NULL,25,0,0,0,1,0,0),(69,NULL,'ME1.2b','Apply the principles of physics and chemistry to explain the risks, limitations, and appropriate use of diagnostic and therapeutic technologies.',NULL,25,0,1,0,1,0,0),(70,NULL,'ME1.3a','Describe the functional elements in the human genome, their evolutionary origins, their interactions, and the consequences of genetic and epigenetic changes on adaptation and health.',NULL,26,0,0,0,1,0,0),(71,NULL,'ME1.3b','Explain how variation at the gene level alters the chemical and physical properties of biological systems, and how this, in turn, influences health.',NULL,26,0,1,0,1,0,0),(72,NULL,'ME1.3c','Describe the major forms and frequencies of genetic variation and their consequences on health in different human populations.',NULL,26,0,2,0,1,0,0),(73,NULL,'ME1.3d','Apply knowledge of the genetics and the various patterns of genetic transmission within families in order to obtain and interpret family history and ancestry data, calculate risk of disease, and order genetic tests to guide therapeutic decision-making.',NULL,26,0,3,0,1,0,0),(74,NULL,'ME1.3e','Use to guide clinical action plans, the interaction of genetic and environmental factors to produce phenotypes and provide the basis for individual variation in response to toxic, pharmacological, or other exposures.',NULL,26,0,4,0,1,0,0),(75,NULL,'ME1.4a','Apply knowledge of the generation of immunological diversity and specificity to the diagnosis and treatment of disease.',NULL,27,0,0,0,1,0,0),(76,NULL,'ME1.4b','Apply knowledge of the mechanisms for distinction between self and nonself (tolerance and immune surveillance) to the maintenance of health, autoimmunity, and transplant rejection.',NULL,27,0,1,0,1,0,0),(77,NULL,'ME1.4c','Apply knowledge of the molecular basis for immune cell development to diagnose and treat immune deficiencies.',NULL,27,0,2,0,1,0,0),(78,NULL,'ME1.4d','Apply knowledge of the mechanisms used to defend against intracellular or extracellular microbes to the development of immunological prevention or treatment.',NULL,27,0,3,0,1,0,0),(79,NULL,'ME1.5a','Apply knowledge of cellular responses to injury, and the underlying etiology, biochemical and molecular alterations, to assess therapeutic interventions.',NULL,28,0,0,0,1,0,0),(80,NULL,'ME1.5b','Apply knowledge of the vascular and leukocyte responses of inflammation and their cellular and soluble mediators to the causation, resolution, prevention, and targeted therapy of tissue injury.',NULL,28,0,1,0,1,0,0),(81,NULL,'ME1.5c','Apply knowledge of the interplay of platelets, vascular endothelium, leukocytes, and coagulation factors in maintaining fluidity of blood, formation of thrombi, and causation of atherosclerosis to the prevention and diagnosis of thrombosis and atherosclerosis in various vascular beds, and the selection of therapeutic responses.',NULL,28,0,2,0,1,0,0),(82,NULL,'ME1.5d','Apply knowledge of the molecular basis of neoplasia to an understanding of the biological behavior, morphologic appearance, classification, diagnosis, prognosis, and targeted therapy of specific neoplasms.',NULL,28,0,3,0,1,0,0),(83,NULL,'ME1.6a','Apply the principles of host-pathogen and pathogen-population interactions and knowledge of pathogen structure, genomics, lifecycle, transmission, natural history, and pathogenesis to the prevention, diagnosis, and treatment of infectious disease.',NULL,29,0,0,0,1,0,0),(84,NULL,'ME1.6b','Apply the principles of symbiosis (commensalisms, mutualism, and parasitism) to the maintenance of health and disease.',NULL,29,0,1,0,1,0,0),(85,NULL,'ME1.6c','Apply the principles of epidemiology to maintaining and restoring the health of communities and individuals.',NULL,29,0,2,0,1,0,0),(86,NULL,'ME1.7a','Apply knowledge of pathologic processes, pharmacokinetics, and pharmacodynamics to guide safe and effective treatments.',NULL,30,0,0,0,1,0,0),(87,NULL,'ME1.7b','Select optimal drug therapy based on an understanding of pertinent research, relevant medical literature, regulatory processes, and pharmacoeconomics.',NULL,30,0,1,0,1,0,0),(88,NULL,'ME1.7c','Apply knowledge of individual variability in the use and responsiveness to pharmacological agents to selecting and monitoring therapeutic regimens and identifying adverse responses.',NULL,30,0,2,0,1,0,0),(89,NULL,'ME1.8a','Apply basic mathematical tools and concepts, including functions, graphs and modeling, measurement and scale, and quantitative reasoning, to an understanding of the specialized functions of membranes, cells, tissues, organs, and the human organism, in both health and disease.',NULL,31,0,0,0,1,0,0),(90,NULL,'ME1.8b','Apply the principles and approaches of statistics, biostatistics, and epidemiology to the evaluation and interpretation of disease risk, etiology, and prognosis, and to the prevention, diagnosis, and management of disease.',NULL,31,0,1,0,1,0,0),(91,NULL,'ME1.8c','Apply the basic principles of information systems, their design and architecture, implementation, use, and limitations, to information retrieval, clinical problem solving, and public health and policy.',NULL,31,0,2,0,1,0,0),(92,NULL,'ME1.8d','Explain the importance, use, and limitations of biomedical and health informatics, including data quality, analysis, and visualization, and its application to diagnosis, therapeutics, and characterization of populations and subpopulations. ',NULL,31,0,3,0,1,0,0),(93,NULL,'ME1.8e','Apply elements of the scientific process, such as inference, critical analysis of research design, and appreciation of the difference between association and causation, to interpret the findings, applications, and limitations of observational and experimental research in clinical decision making.',NULL,31,0,4,0,1,0,0),(94,NULL,'ME2.1a','Effectively identify and explore issues to be addressed in a patient encounter, including the patient\'s context and preferences.',NULL,32,0,0,0,1,0,0),(95,NULL,'ME2.1b','For purposes of prevention and health promotion, diagnosis and/or management, elicit a history that is relevant, concise and accurate to context and preferences.',NULL,32,0,1,0,1,0,0),(96,NULL,'ME2.1c','For the purposes of prevention and health promotion, diagnosis and/or management, perform a focused physical examination that is relevant and accurate.',NULL,32,0,2,0,1,0,0),(97,NULL,'ME2.1d','Select basic, medically appropriate investigative methods in an ethical manner.',NULL,32,0,3,0,1,0,0),(98,NULL,'ME2.1e','Demonstrate effective clinical problem solving and judgment to address selected common patient presentations, including interpreting available data and integrating information to generate differential diagnoses and management plans.',NULL,32,0,4,0,1,0,0),(99,NULL,'ME2.2a','Demonstrate effective, appropriate and timely performance of selected diagnostic procedures.',NULL,33,0,0,0,1,0,0),(100,NULL,'ME2.2b','Demonstrate effective, appropriate and timely performance of selected therapeutic procedures.',NULL,33,0,1,0,1,0,0),(101,NULL,'ME3.xa','Identify and apply aspects of normal human structure and physiology relevant to the clinical presentation.',NULL,34,0,0,0,1,0,0),(102,NULL,'ME3.xb','Identify pathologic or maladaptive processes that are active.',NULL,34,0,1,0,1,0,0),(103,NULL,'ME3.xc','Develop a differential diagnosis for the clinical presentation.',NULL,34,0,2,0,1,0,0),(104,NULL,'ME3.xd','Use history taking and physical examination relevant to the clinical presentation.',NULL,34,0,3,0,1,0,0),(105,NULL,'ME3.xe','Use diagnostic tests or procedures appropriately to establish working diagnoses.',NULL,34,0,4,0,1,0,0),(106,NULL,'ME3.xf','Provide appropriate initial management for the clinical presentation.',NULL,34,0,5,0,1,0,0),(107,NULL,'ME3.xg','Provide evidence for diagnostic and therapeutic choices.',NULL,34,0,6,0,1,0,0),(108,NULL,'ME4.1a','Demonstrate awareness and respect for the Determinants of Health in identifying the needs of a patient.',NULL,35,0,0,0,1,0,0),(109,NULL,'ME4.1b','Discover opportunities for health promotion and disease prevention as well as resources for patient care.',NULL,35,0,1,0,1,0,0),(110,NULL,'ME4.1c','Formulate preventive measures into their management strategies.',NULL,35,0,2,0,1,0,0),(111,NULL,'ME4.1d','Communicate with the patient, the patient\'s family and concerned others with regard to risk factors and their modification where appropriate.',NULL,35,0,3,0,1,0,0),(112,NULL,'ME4.1e','Describe programs for the promotion of health including screening for, and the prevention of, illness.',NULL,35,0,4,0,1,0,0),(113,NULL,'P1.1a','Defines the concepts of honesty, integrity, commitment, dependability, compassion, respect and altruism as applied to medical practice and correctly identifies examples of appropriate and inappropriate application.',NULL,36,0,0,0,1,0,0),(114,NULL,'P1.1b','Applies these concepts in medical and professional encounters.',NULL,36,0,1,0,1,0,0),(115,NULL,'P1.2a','Defines the concept of \"standard of care\".',NULL,37,0,0,0,1,0,0),(116,NULL,'P1.2b','Applies diagnostic and therapeutic modalities in evidence based and patient centred contexts.',NULL,37,0,1,0,1,0,0),(117,NULL,'P1.3a','Recognizes and acknowledges limits of personal competence.',NULL,38,0,0,0,1,0,0),(118,NULL,'P1.3b','Is able to acquire specific knowledge appropriately to assist clinical management.',NULL,38,0,1,0,1,0,0),(119,NULL,'P1.3c','Engages colleagues and other health professionals appropriately.',NULL,38,0,2,0,1,0,0),(120,NULL,'P2.1a','Analyze ethical issues encountered in practice (such as informed consent, confidentiality, truth telling, vulnerable populations etc).',NULL,39,0,0,0,1,0,0),(121,NULL,'P2.1b','Analyze legal issues encountered in practice (such as conflict of interest, patient rights and privacy, etc).',NULL,39,0,1,0,1,0,0),(122,NULL,'P2.1c','Analyze the psycho-social, cultural and religious issues that could affect patient management.',NULL,39,0,2,0,1,0,0),(123,NULL,'P2.1d','Define and implement principles of appropriate relationships with patients.',NULL,39,0,3,0,1,0,0),(124,NULL,'P2.2a','Recognize the professional, legal and ethical codes and obligations required of current practice in a variety of settings, including hospitals, private practice and health care institutions, etc.',NULL,40,0,0,0,1,0,0),(125,NULL,'P2.2b','Recognize and respond appropriately to unprofessional behaviour in colleagues.',NULL,40,0,1,0,1,0,0),(126,NULL,'S1.1a','Use objective parameters to assess reliability of various sources of medical information.',NULL,41,0,0,0,1,0,0),(127,NULL,'S1.1b','Are able to efficiently search sources of medical information in order to address specific clinical questions.',NULL,41,0,1,0,1,0,0),(128,NULL,'S1.2a','Apply knowledge of research and statistical methodology to the review of medical information and make decisions for health care of patients and society through scientifically rigourous analysis of evidence.',NULL,42,0,0,0,1,0,0),(129,NULL,'S1.2b','Apply to the review of medical literature the principles of research ethics, including disclosure, conflicts of interest, research on human subjects and industry relations.',NULL,42,0,1,0,1,0,0),(130,NULL,'S1.2c','Identify the nature and requirements of organizations contributing to medical education.',NULL,42,0,2,0,1,0,0),(131,NULL,'S1.2d','Balance scientific evidence with consideration of patient preferences and overall quality of life in therapeutic decision making.',NULL,42,0,3,0,1,0,0),(132,NULL,'S2.1a','Formulates relevant research hypotheses.',NULL,43,0,0,0,1,0,0),(133,NULL,'S2.1b','Develops rigorous methodologies.',NULL,43,0,1,0,1,0,0),(134,NULL,'S2.1c','Develops appropriate collaborations in order to participate in research projects.',NULL,43,0,2,0,1,0,0),(135,NULL,'S2.1d','Practice research ethics, including disclosure, conflicts of interest, research on human subjects and industry relations.',NULL,43,0,3,0,1,0,0),(136,NULL,'S2.1e','Evaluates the outcomes of research by application of rigorous statistical analysis.',NULL,43,0,4,0,1,0,0),(137,NULL,'S2.2a','Report to students and faculty upon new knowledge gained from research and enquiry, using a variety of methods.',NULL,44,0,0,0,1,0,0),(138,NULL,'S3.1a','Develop lifelong learning strategies through integration of the principles of learning.',NULL,45,0,0,0,1,0,0),(139,NULL,'S3.1b','Self-assess learning critically, in congruence with others\' assessment, and address prioritized learning issues.',NULL,45,0,1,0,1,0,0),(140,NULL,'S3.1c','Ask effective learning questions and solve problems appropriately.',NULL,45,0,2,0,1,0,0),(141,NULL,'S3.1d','Consult multiple sources of information.',NULL,45,0,3,0,1,0,0),(142,NULL,'S3.1e','Employ a variety of learning methodologies.',NULL,45,0,4,0,1,0,0),(143,NULL,'S3.1f','Learn with and enhance the learning of others through communities of practice.',NULL,45,0,5,0,1,0,0),(144,NULL,'S3.1g','Employ information technology (informatics) in learning, including, in clerkship, access to patient record data and other technologies.',NULL,45,0,6,0,1,0,0),(145,NULL,'CM1.1a','Apply the skills that develop positive therapeutic relationships with patients and their families, characterized by understanding, trust, respect, honesty and empathy.',NULL,46,0,0,0,1,0,0),(146,NULL,'CM1.1b','Respect patient confidentiality, privacy and autonomy.',NULL,46,0,1,0,1,0,0),(147,NULL,'CM1.1c','Listen effectively and be aware of and responsive to nonverbal cues.',NULL,46,0,2,0,1,0,0),(148,NULL,'CM1.1d','Communicate effectively with individuals regardless of their social, cultural or ethnic backgrounds, or disabilities.',NULL,46,0,3,0,1,0,0),(149,NULL,'CM1.1e','Effectively facilitate a structured clinical encounter.',NULL,46,0,4,0,1,0,0),(150,NULL,'CM1.2a','Gather information about a disease, but also about a patient\'s beliefs, concerns, expectations and illness experience.',NULL,47,0,0,0,1,0,0),(151,NULL,'CM1.2b','Seek out and synthesize relevant information from other sources, such as a patient\'s family, caregivers and other professionals.',NULL,47,0,1,0,1,0,0),(152,NULL,'CM1.3a','Provide accurate information to a patient and family, colleagues and other professionals in a clear, non-judgmental, and understandable manner.',NULL,48,0,0,0,1,0,0),(153,NULL,'CM1.3b','Maintain clear, accurate and appropriate records of clinical encounters and plans.',NULL,48,0,1,0,1,0,0),(154,NULL,'CM1.3c','Effectively present verbal reports of clinical encounters and plans.',NULL,48,0,2,0,1,0,0),(155,NULL,'CM1.4a','Effectively identify and explore problems to be addressed from a patient encounter, including the patient\'s context, responses, concerns and preferences.',NULL,49,0,0,0,1,0,0),(156,NULL,'CM1.4b','Respect diversity and difference, including but not limited to the impact of gender, religion and cultural beliefs on decision making.',NULL,49,0,1,0,1,0,0),(157,NULL,'CM1.4c','Encourage discussion, questions and interaction in the encounter.',NULL,49,0,2,0,1,0,0),(158,NULL,'CM1.4d','Engage patients, families and relevant health professionals in shared decision making to develop a plan of care.',NULL,49,0,3,0,1,0,0),(159,NULL,'CM1.4e','Effectively address challenging communication issues such as obtaining informed consent, delivering bad news, and addressing anger, confusion and misunderstanding.',NULL,49,0,4,0,1,0,0),(160,NULL,'CL1.1a','Clearly describe and demonstrate their roles and responsibilities under law and other provisions, to other professionals within a variety of health care settings.',NULL,50,0,0,0,1,0,0),(161,NULL,'CL1.1b','Recognize and respect the diversity of roles and responsibilities of other health care professionals in a variety of settings, noting  how these roles interact with their own.',NULL,50,0,1,0,1,0,0),(162,NULL,'CL1.1c','Work with others to assess, plan, provide and integrate care for individual patients.',NULL,50,0,2,0,1,0,0),(163,NULL,'CL1.1d','Respect team ethics, including confidentiality, resource allocation and professionalism.',NULL,50,0,3,0,1,0,0),(164,NULL,'CL1.1e','Where appropriate, demonstrate leadership in a healthcare team.',NULL,50,0,4,0,1,0,0),(165,NULL,'CL1.2a','Demonstrate a respectful attitude towards other colleagues and members of an interprofessional team members in a variety of settings.',NULL,51,0,0,0,1,0,0),(166,NULL,'CL1.2b','Respect differences, and work to overcome misunderstandings and limitations in others, that may contribute to conflict.',NULL,51,0,1,0,1,0,0),(167,NULL,'CL1.2c','Recognize one\'s own differences, and work to overcome one\'s own misunderstandings and limitations that may contribute to interprofessional conflict.',NULL,51,0,2,0,1,0,0),(168,NULL,'CL1.2d','Reflect on successful interprofessional team function.',NULL,51,0,3,0,1,0,0),(169,NULL,'CL1.3a','Identify the roles of patients and their family in prevention and management of illness.',NULL,52,0,0,0,1,0,0),(170,NULL,'CL1.3b','Learn how to inform and involve the patient and family in decision-making and management plans.',NULL,52,0,1,0,1,0,0),(171,NULL,'CL1.4a','Improve teaching through advice from experts in medical education.',NULL,53,0,0,0,1,0,0),(172,NULL,'CL1.4b','Accept supervision and feedback.',NULL,53,0,1,0,1,0,0),(173,NULL,'CL1.4c','Seek learning from others.',NULL,53,0,2,0,1,0,0),(174,NULL,'A1.1a','Explain factors that influence health, disease, disability and access to care including non-biologic factors (cultural, psychological, sociologic, familial, economic, environmental, legal, political, spiritual needs and beliefs).',NULL,54,0,0,0,1,0,0),(175,NULL,'A1.1b','Describe barriers to access to care and resources.',NULL,54,0,1,0,1,0,0),(176,NULL,'A1.1c','Discuss health issues for special populations, including vulnerable or marginalized populations.',NULL,54,0,2,0,1,0,0),(177,NULL,'A1.1d','Identify principles of health policy and implications.',NULL,54,0,3,0,1,0,0),(178,NULL,'A1.1e','Describe health programs and interventions at the population level.',NULL,54,0,4,0,1,0,0),(179,NULL,'A2.1a','Identify the role of and method of access to services of community resources.',NULL,55,0,0,0,1,0,0),(180,NULL,'A2.1b','Describe appropriate methods of communication about community resources to and on behalf of patients.',NULL,55,0,1,0,1,0,0),(181,NULL,'A2.1c','Locate and analyze a variety of health communities and community health networks in the local Kingston area and beyond.',NULL,55,0,2,0,1,0,0),(182,NULL,'A2.2a','Describe the role and examples of physicians and medical associations in advocating collectively for health and patient safety.',NULL,56,0,0,0,1,0,0),(183,NULL,'A2.2b','Analyze the ethical and professional issues inherent in health advocacy, including possible conflict between roles of gatekeeper and manager.',NULL,56,0,1,0,1,0,0),(184,NULL,'M1.1a','Outline strategies for effective practice in a variety of health care settings, including their structure, finance and operation.',NULL,57,0,0,0,1,0,0),(185,NULL,'M1.1b','Outline the common law and statutory provisions which govern practice and collaboration within hospital and other settings.',NULL,57,0,1,0,1,0,0),(186,NULL,'M1.1c','Recognizes one\'s own personal preferences and strengths and uses this knowledge in career decisions.',NULL,57,0,2,0,1,0,0),(187,NULL,'M1.1d','Identify career paths within health care settings.',NULL,57,0,3,0,1,0,0),(188,NULL,'M2.1a','Identify and balance personal and educational priorities to foster future balance between personal health and a sustainable practice.',NULL,58,0,0,0,1,0,0),(189,NULL,'M2.1b','Practice personal and professional awareness, insight and acceptance of feedback and peer review;  participate in peer review.',NULL,58,0,1,0,1,0,0),(190,NULL,'M2.1c','Implement plans to overcome barriers to health personal and professional behavior.',NULL,58,0,2,0,1,0,0),(191,NULL,'M2.1d','Recognize and respond to other educational/professional colleagues in need of support.',NULL,58,0,3,0,1,0,0),(200,NULL,'Clinical Learning Objectives',NULL,NULL,0,0,1,0,1,0,0),(201,NULL,'Pain, lower limb',NULL,NULL,200,0,113,0,1,1257353646,1),(202,NULL,'Pain, upper limb',NULL,NULL,200,0,112,0,1,1257353646,1),(203,NULL,'Fracture/disl\'n',NULL,NULL,200,0,111,0,1,1257353646,1),(204,NULL,'Scrotal pain',NULL,NULL,200,0,101,0,1,1257353646,1),(205,NULL,'Blood in urine',NULL,NULL,200,0,100,0,1,1257353646,1),(206,NULL,'Urinary obstruction/hesitancy',NULL,NULL,200,0,99,0,1,1257353646,1),(207,NULL,'Nausea/vomiting',NULL,NULL,200,0,98,0,1,1257353646,1),(208,NULL,'Hernia',NULL,NULL,200,0,97,0,1,1257353646,1),(209,NULL,'Abdominal injuries',NULL,NULL,200,0,96,0,1,1257353646,1),(210,NULL,'Chest injuries',NULL,NULL,200,0,95,0,1,1257353646,1),(211,NULL,'Breast disorders',NULL,NULL,200,0,94,0,1,1257353646,1),(212,NULL,'Anorectal pain',NULL,NULL,200,0,93,0,1,1257353646,1),(213,NULL,'Blood, GI tract',NULL,NULL,200,0,92,0,1,1257353646,1),(214,NULL,'Abdominal distension',NULL,NULL,200,0,91,0,1,1257353646,1),(215,NULL,'Subs abuse/addic/wdraw',NULL,NULL,200,0,90,0,1,1257353646,1),(216,NULL,'Abdo pain - acute',NULL,NULL,200,0,89,0,1,1257353646,1),(217,NULL,'Psychosis/disord thoughts',NULL,NULL,200,0,88,0,1,1257353646,1),(218,NULL,'Personality disorders',NULL,NULL,200,0,87,0,1,1257353646,1),(219,NULL,'Panic/anxiety',NULL,NULL,200,0,86,0,1,1257353646,1),(221,NULL,'Mood disorders',NULL,NULL,200,0,84,0,1,1257353646,1),(222,NULL,'XR-Wrist/hand',NULL,NULL,200,0,83,0,1,1257353646,1),(223,NULL,'XR-Chest',NULL,NULL,200,0,82,0,1,1257353646,1),(224,NULL,'XR-Hip/pelvis',NULL,NULL,200,0,81,0,1,1257353646,1),(225,NULL,'XR-Ankle/foot',NULL,NULL,200,0,80,0,1,1257353646,1),(226,NULL,'Skin ulcers-tumors',NULL,NULL,200,0,79,0,1,1257353646,1),(228,NULL,'Skin wound',NULL,NULL,200,0,77,0,1,1257353646,1),(233,NULL,'Dyspnea, acute',NULL,NULL,200,0,72,0,1,1257353646,1),(234,NULL,'Infant/child nutrition',NULL,NULL,200,0,71,0,1,1257353646,1),(235,NULL,'Newborn assessment',NULL,NULL,200,0,70,0,1,1257353646,1),(236,NULL,'Rash,child',NULL,NULL,200,0,69,0,1,1257353646,1),(237,NULL,'Ped naus/vom/diarh',NULL,NULL,200,0,68,0,1,1257353646,1),(238,NULL,'Ped EM\'s-acutely ill',NULL,NULL,200,0,67,0,1,1257353646,1),(239,NULL,'Ped dysp/resp dstres',NULL,NULL,200,0,66,0,1,1257353646,1),(240,NULL,'Ped constipation',NULL,NULL,200,0,65,0,1,1257353646,1),(241,NULL,'Fever in a child',NULL,NULL,200,0,64,0,1,1257353646,1),(242,NULL,'Ear pain',NULL,NULL,200,0,63,0,1,1257353646,1),(257,NULL,'Prolapse',NULL,NULL,200,0,48,0,1,1257353646,1),(258,NULL,'Vaginal bleeding, abn',NULL,NULL,200,0,47,0,1,1257353646,1),(259,NULL,'Postpartum, normal',NULL,NULL,200,0,46,0,1,1257353646,1),(260,NULL,'Labour, normal',NULL,NULL,200,0,45,0,1,1257353646,1),(261,NULL,'Labour, abnormal',NULL,NULL,200,0,44,0,1,1257353646,1),(262,NULL,'Infertility',NULL,NULL,200,0,43,0,1,1257353646,1),(263,NULL,'Incontinence-urine',NULL,NULL,200,0,42,0,1,1257353646,1),(264,NULL,'Hypertension, preg',NULL,NULL,200,0,41,0,1,1257353646,1),(265,NULL,'Dysmenorrhea',NULL,NULL,200,0,40,0,1,1257353646,1),(266,NULL,'Contraception',NULL,NULL,200,0,39,0,1,1257353646,1),(267,NULL,'Antepartum care',NULL,NULL,200,0,38,0,1,1257353646,1),(268,NULL,'Weakness',NULL,NULL,200,0,37,0,1,1257353646,1),(269,NULL,'Sodium-abn',NULL,NULL,200,0,36,0,1,1257353646,1),(270,NULL,'Renal failure',NULL,NULL,200,0,35,0,1,1257353646,1),(271,NULL,'Potassium-abn',NULL,NULL,200,0,34,0,1,1257353646,1),(272,NULL,'Murmur',NULL,NULL,200,0,33,0,1,1257353646,1),(273,NULL,'Joint pain, poly',NULL,NULL,200,0,32,0,1,1257353646,1),(274,NULL,'Impaired LOC (coma)',NULL,NULL,200,0,31,0,1,1257353646,1),(275,NULL,'Hypotension',NULL,NULL,200,0,30,0,1,1257353646,1),(276,NULL,'Hypertension',NULL,NULL,200,0,29,0,1,1257353646,1),(277,NULL,'H+ concentratn, abn',NULL,NULL,200,0,28,0,1,1257353646,1),(278,NULL,'Fever',NULL,NULL,200,0,27,0,1,1257353646,1),(279,NULL,'Edema',NULL,NULL,200,0,26,0,1,1257353646,1),(280,NULL,'Dyspnea-chronic',NULL,NULL,200,0,25,0,1,1257353646,1),(281,NULL,'Diabetes mellitus',NULL,NULL,200,0,24,0,1,1257353646,1),(282,NULL,'Dementia',NULL,NULL,200,0,23,0,1,1257353646,1),(283,NULL,'Delerium/confusion',NULL,NULL,200,0,22,0,1,1257353646,1),(284,NULL,'Cough',NULL,NULL,200,0,21,0,1,1257353646,1),(286,NULL,'Anemia',NULL,NULL,200,0,19,0,1,1257353646,1),(287,NULL,'Chest pain',NULL,NULL,200,0,18,0,1,1257353646,1),(288,NULL,'Abdo pain-chronic',NULL,NULL,200,0,17,0,1,1257353646,1),(289,NULL,'Wk-rel\'td health iss',NULL,NULL,200,0,16,0,1,1257353646,1),(290,NULL,'Weight loss/gain',NULL,NULL,200,0,15,0,1,1257353646,1),(291,NULL,'URTI',NULL,NULL,200,0,14,0,1,1257353646,1),(292,NULL,'Sore throat',NULL,NULL,200,0,13,0,1,1257353646,1),(293,NULL,'Skin rash',NULL,NULL,200,0,12,0,1,1257353646,1),(294,NULL,'Pregnancy',NULL,NULL,200,0,11,0,1,1257353646,1),(295,NULL,'Periodic health exam',NULL,NULL,200,0,10,0,1,1257353646,1),(296,NULL,'Pain, spinal',NULL,NULL,200,0,9,0,1,1257353646,1),(299,NULL,'Headache',NULL,NULL,200,0,6,0,1,1257353646,1),(300,NULL,'Fatigue',NULL,NULL,200,0,5,0,1,1257353646,1),(303,NULL,'Dysuria/pyuria',NULL,NULL,200,0,2,0,1,1257353646,1),(304,NULL,'Fracture/dislocation',NULL,NULL,200,0,114,0,1,1261414735,1),(305,NULL,'Pain',NULL,NULL,200,0,115,0,1,1261414735,1),(306,NULL,'Preop Assess - anesthesiology',NULL,NULL,200,0,116,0,1,1261414735,1),(307,NULL,'Preop Assess - surgery',NULL,NULL,200,0,117,0,1,1261414735,1),(308,NULL,'Pain - spinal',NULL,NULL,200,0,118,0,1,1261414735,1),(309,NULL,'MCC Presentations',NULL,NULL,0,0,3,0,1,1265296358,1),(310,'1-E','Abdominal Distension','Abdominal distention is common and may indicate the presence of serious intra-abdominal or systemic disease.',NULL,309,0,1,0,1,1271174177,1),(311,'2-E','Abdominal Mass','If hernias are excluded, most other abdominal masses represent a significant underlying disease that requires complete investigation.',NULL,309,0,2,0,1,1271174177,1),(312,'2-1-E','Adrenal Mass','Adrenal masses are at times found incidentally after CT, MRI, or ultrasound examination done for unrelated reasons.  The incidence is about 3.5 % (almost 10 % of autopsies).',NULL,311,0,1,0,1,1271174178,1),(313,'2-2-E','Hepatomegaly','True hepatomegaly (enlargement of the liver with a span greater than 14 cm in adult males and greater than 12 cm in adult females) is an uncommon clinical presentation, but is important to recognize in light of potentially serious causal conditions.',NULL,311,0,2,0,1,1271174178,1),(314,'2-4-E','Hernia (abdominal Wall And Groin)','A hernia is defined as an abnormal protrusion of part of a viscus through its containing wall.  Hernias, in particular inguinal hernias, are very common, and thus, herniorrphaphy is a very common surgical intervention.',NULL,311,0,3,0,1,1271174178,1),(315,'2-3-E','Splenomegaly','Splenomegaly, an enlarged spleen detected on physical examination by palpitation or percussion at Castell\'s point, is relatively uncommon.  However, it is often associated with serious underlying pathology.',NULL,311,0,4,0,1,1271174178,1),(316,'3-1-E','Abdominal Pain (children)','Abdominal pain is a common complaint in children.  While the symptoms may result from serious abdominal pathology, in a large proportion of cases, an identifiable organic cause is not found.  The causes are often age dependent.',NULL,309,0,3,0,1,1271174178,1),(317,'3-2-E','Abdominal Pain, Acute ','Abdominal pain may result from intra-abdominal inflammation or disorders of the abdominal wall.  Pain may also be referred from sources outside the abdomen such as retroperitoneal processes as well as intra-thoracic processes.  Thorough clinical evaluation is the most important \"test\" in the diagnosis of abdominal pain.',NULL,309,0,4,0,1,1271174178,1),(318,'3-4-E','Abdominal Pain, Anorectal','While almost all causes of anal pain are treatable, some can be destructive locally if left untreated.',NULL,309,0,5,0,1,1271174178,1),(319,'3-3-E','Abdominal Pain, Chronic','Chronic and recurrent abdominal pain, including heartburn or dyspepsia is a common symptom (20 - 40 % of adults) with an extensive differential diagnosis and heterogeneous pathophysiology.  The history and physical examination frequently differentiate between functional and more serious underlying diseases.',NULL,309,0,6,0,1,1271174178,1),(320,'4-E','Allergic Reactions/food Allergies Intolerance/atopy','Allergic reactions are considered together despite the fact that they exhibit a variety of clinical responses and are considered separately under the appropriate presentation.  The rationale for considering them together is that in some patients with a single response (e.g., atopic dermatitis), other atopic disorders such as asthma or allergic rhinitis may occur at other times.  Moreover, 50% of patients with atopic dermatitis report a family history of respiratory atopy. ',NULL,309,0,7,0,1,1271174178,1),(321,'5-E','Attention Deficit/hyperactivity Disorder (adhd)/learning Dis','Family physicians at times are the initial caregivers to be confronted by developmental and behavioural problems of childhood and adolescence (5 - 10% of school-aged population).  Lengthy waiting lists for specialists together with the urgent plight of patients often force primary-care physicians to care for these children.',NULL,309,0,8,0,1,1271174178,1),(322,'6-E','Blood From Gastrointestinal Tract','Both upper and lower gastrointestinal bleeding are common and may be life-threatening.  Upper intestinal bleeding usually presents with hematemesis (blood or coffee-ground material) and/or melena (black, tarry stools).  Lower intestinal bleeding usually manifests itself as hematochezia (bright red blood or dark red blood or clots per rectum).  Unfortunately, this difference is not consistent. Melena may be seen in patients with colorectal or small bowel bleeding, and hematochezia may be seen with massive upper gastrointestinal bleeding.  Occult bleeding from the gastrointestinal tract may also be identified by positive stool for occult blood or the presence of iron deficiency anemia.',NULL,309,0,9,0,1,1271174178,1),(323,'6-2-E','Blood From Gastrointestinal Tract, Lower/hematochezia','Although lower gastrointestinal bleeding (blood originating distal to ligament of Treitz) or hematochezia is less common than upper (20% vs. 80%), it is associated with 10 -20% morbidity and mortality since it usually occurs in the elderly.  Early identification of colorectal cancer is important in preventing cancer-related morbidity and mortality (colorectal cancer is second only to lung cancer as a cause of cancer-related death). ',NULL,322,0,1,0,1,1271174178,1),(324,'6-1-E','Blood From Gastrointestinal Tract, Upper/hematemesis','Although at times self-limited, upper GI bleeding always warrants careful and urgent evaluation, investigation, and treatment.  The urgency of treatment and the nature of resuscitation depend on the amount of blood loss, the likely cause of the bleeding, and the underlying health of the patient.',NULL,322,0,2,0,1,1271174178,1),(325,'7-E','Blood In Sputum (hemoptysis/prevention Of Lung Cancer)','Expectoration of blood can range from blood streaking of sputum to massive hemoptysis (&gt;200 ml/d) that may be acutely life threatening.  Bleeding usually starts and stops unpredictably, but under certain circumstances may require immediate establishment of an airway and control of the bleeding.',NULL,309,0,10,0,1,1271174178,1),(326,'8-E','Blood In Urine (hematuria)','Urinalysis is a screening procedure for insurance and routine examinations.  Persistent hematuria implies the presence of conditions ranging from benign to malignant.',NULL,309,0,11,0,1,1271174178,1),(327,'9-1-E','Hypertension','Hypertension is a common condition that usually presents with a modest elevation in either systolic or diastolic blood pressure.  Under such circumstances, the diagnosis of hypertension is made only after three separate properly measured blood pressures.  Appropriate investigation and management of hypertension is expected to improve health outcomes.',NULL,309,0,12,0,1,1271174178,1),(328,'9-1-1-E','Hypertension In Childhood','The prevalence of hypertension in children is&lt;1 %, but often results from identifiable causes (usually renal or vascular).  Consequently, vigorous investigation is warranted.',NULL,327,0,1,0,1,1271174178,1),(329,'9-1-2-E','Hypertension In The Elderly','Elderly patients (&gt;65 years) have hypertension much more commonly than younger patients do, especially systolic hypertension.  The prevalence of hypertension among the elderly may reach 60 -80 %.',NULL,327,0,2,0,1,1271174178,1),(330,'9-1-3-E','Malignant Hypertension','Malignant hypertension and hypertensive encephalopathies are two life-threatening syndromes caused by marked elevation in blood pressure.',NULL,327,0,3,0,1,1271174178,1),(331,'9-1-4-E','Pregnancy Associated Hypertension','Ten to 20 % of pregnancies are associated with hypertension.  Chronic hypertension complicates&lt;5%, preeclampsia occurs in slightly&gt;6%, and gestational hypertension arises in 6% of pregnant women.  Preeclampsia is potentially serious, but can be managed by treatment of hypertension and \'cured\' by delivery of the fetus.',NULL,327,0,4,0,1,1271174178,1),(332,'9-2-E','Hypotension/shock','All physicians must deal with life-threatening emergencies.  Regardless of underlying cause, certain general measures are usually indicated (investigations and therapeutic interventions) that can be life saving.',NULL,309,0,13,0,1,1271174178,1),(333,'9-2-1-E','Anaphylaxis','Anaphylaxis causes about 50 fatalities per year, and occurs in 1/5000-hospital admissions in Canada.  Children most commonly are allergic to foods.',NULL,332,0,1,0,1,1271174178,1),(334,'10-1-E','Breast Lump/screening','Complaints of breast lumps are common, and breast cancer is the most common cancer in women.  Thus, all breast complaints need to be pursued to resolution.  Screening women 50 - 69 years with annual mammography improves survival. ',NULL,309,0,14,0,1,1271174178,1),(335,'10-2-E','Galactorrhea/discharge','Although noticeable breast secretions are normal in&gt;50 % of reproductive age women, spontaneous persistent galactorrhea may reflect underlying disease and requires investigation.',NULL,309,0,15,0,1,1271174178,1),(336,'10-3-E','Gynecomastia','Although a definite etiology for gynecomastia is found in&lt;50% of patients, a careful drug history is important so that a treatable cause is detected.  The underlying feature is an increased estrogen to androgen ratio.',NULL,309,0,16,0,1,1271174178,1),(337,'11-E','Burns','Burns are relatively common and range from minor cutaneous wounds to major life-threatening traumas.  An understanding of the patho-physiology and treatment of burns and the metabolic and wound healing response will enable physicians to effectively assess and treat these injuries.',NULL,309,0,17,0,1,1271174178,1),(338,'12-1-E','Hypercalcemia','Hypercalcemia may be associated with an excess of calcium in both extracellular fluid and bone (e.g., increased intestinal absorption), or with a localised or generalised deficit of calcium in bone (e.g., increased bone resorption).  This differentiation by physicians is important for both diagnostic and management reasons.',NULL,309,0,18,0,1,1271174178,1),(339,'12-4-E','Hyperphosphatemia','Acute severe hyperphosphatemia can be life threatening.',NULL,309,0,19,0,1,1271174178,1),(340,'12-2-E','Hypocalcemia','Tetany, seizures, and papilledema may occur in patients who develop hypocalcemia acutely.',NULL,309,0,20,0,1,1271174178,1),(341,'12-3-E','Hypophosphatemia/fanconi Syndrome','Of hospitalised patients, 10-15% develop hypophosphatemia, and a small proportion have sufficiently profound depletion to lead to complications (e.g., rhabdomyolysis).',NULL,309,0,21,0,1,1271174178,1),(342,'13-E','Cardiac Arrest','All physicians are expected to attempt resuscitation of an individual with cardiac arrest. In the community, cardiac arrest most commonly is caused by ventricular fibrillation. However, heart rhythm at clinical presentation in many cases is unknown.  As a consequence, operational criteria for cardiac arrest do not rely on heart rhythm but focus on the presumed sudden pulse-less condition and the absence of evidence of a non-cardiac condition as the cause of the arrest.',NULL,309,0,22,0,1,1271174178,1),(343,'14-E','Chest Discomfort/pain/angina Pectoris','Chest pain in the primary care setting, although potentially severe and disabling, is more commonly of benign etiology.  The correct diagnosis requires a cost-effective approach.  Although coronary heart disease primarily occurs in patients over the age of 40, younger men and women can be affected (it is estimated that advanced lesions are present in 20% of men and 8% of women aged 30 to 34).  Physicians must recognise the manifestations of coronary artery disease and assess coronary risk factors.  Modifications of risk factors should be recommended as necessary.',NULL,309,0,23,0,1,1271174178,1),(344,'15-1-E','Bleeding Tendency/bruising','A bleeding tendency (excessive, delayed, or spontaneous bleeding) may signify serious underlying disease.  In children or infants, suspicion of a bleeding disorder may be a family history of susceptibility to bleeding.  An organised approach to this problem is essential.  Urgent management may be required.',NULL,309,0,24,0,1,1271174178,1),(345,'15-2-E','Hypercoagulable State','Patients may present with venous thrombosis and on occasion with pulmonary embolism. A risk factor for thrombosis can now be identified in over 80% of such patients.',NULL,309,0,25,0,1,1271174178,1),(346,'16-1-E',' Adult Constipation','Constipation is common in Western society, but frequency depends on patient and physician\'s definition of the problem.  One definition is straining, incomplete evacuation, sense of blockade, manual maneuvers, and hard stools at least 25% of the time along with&lt;3 stools/week for at least 12 weeks (need not be consecutive).  The prevalence of chronic constipation rises with age. In patients&gt;65 years, almost 1/3 complain of constipation.',NULL,309,0,26,0,1,1271174178,1),(347,'16-2-E','Pediatric Constipation','Constipation is a common problem in children.  It is important to differentiate functional from organic causes in order to develop appropriate management plans.',NULL,309,0,27,0,1,1271174178,1),(348,'17-E','Contraception','Ideally, the prevention of an unwanted pregnancy should be directed at education of patients, male and female, preferably before first sexual contact.  Counselling patients about which method to use, how, and when is a must for anyone involved in health care.',NULL,309,0,28,0,1,1271174178,1),(349,'18-E','Cough','Chronic cough is the fifth most common symptom for which patients seek medical advice.  Assessment of chronic cough must be thorough.  Patients with benign causes for their cough (gastro-esophageal reflux, post-nasal drip, two of the commonest causes) can often be effectively and easily managed.  Patients with more serious causes for their cough (e.g., asthma, the other common cause of chronic cough) require full investigation and management is more complex.',NULL,309,0,29,0,1,1271174178,1),(350,'19-E','Cyanosis/hypoxemia/hypoxia','Cyanosis is the physical sign indicative of excessive concentration of reduced hemoglobin in the blood, but at times is difficult to detect (it must be sought carefully, under proper lighting conditions).  Hypoxemia (low partial pressure of oxygen in blood), when detected, may be reversible with oxygen therapy after which the underlying cause requires diagnosis and management.',NULL,309,0,30,0,1,1271174178,1),(351,'19-1-E','Cyanosis/hypoxemia/hypoxia In Children','Evaluation of the patient with cyanosis depends on the age of the child.  It is an ominous finding and differentiation between peripheral and central is essential in order to mount appropriate management.',NULL,350,0,1,0,1,1271174178,1),(352,'20-E','Deformity/limp/pain In Lower Extremity, Child','\'Limp\' is a bumpy, rough, or strenuous way of walking, usually caused by weakness, pain, or deformity.  Although usually caused by benign conditions, at times it may be life or limb threatening. ',NULL,309,0,31,0,1,1271174178,1),(353,'21-E','Development Disorder/developmental Delay','Providing that normal development and behavior is readily recognized, primary care physicians will at times be the first physicians in a position to assess development in an infant, and recognize abnormal delay and/or atypical development.  Developmental surveillance and direct developmental screening of children, especially those with predisposing risks, will then be an integral part of health care.',NULL,309,0,32,0,1,1271174178,1),(354,'22-1-E','Acute Diarrhea','Diarrheal diseases are extremely common worldwide, and even in North America morbidity and mortality is significant.  One of the challenges for a physician faced with a patient with acute diarrhea is to know when to investigate and initiate treatment and when to simply wait for a self-limiting condition to run its course.',NULL,309,0,33,0,1,1271174178,1),(355,'22-2-E','Chronic Diarrhea','Chronic diarrhea is a decrease in fecal consistency lasting for 4 or more weeks.  It affects about 5% of the population.',NULL,309,0,34,0,1,1271174178,1),(356,'22-3-E','Pediatric Diarrhea','Diarrhea is defined as frequent, watery stools and is a common problem in infants and children.  In most cases, it is mild and self-limited, but the potential for hypovolemia (reduced effective arterial/extracellular volume) and dehydration (water loss in excess of solute) leading to electrolyte abnormalities is great.  These complications in turn may lead to significant morbidity or even mortality.',NULL,309,0,35,0,1,1271174178,1),(357,'23-E','Diplopia','Diplopia is the major symptom associated with dysfunction of extra-ocular muscles or abnormalities of the motor nerves innervating these muscles.  Monocular diplopia is almost always indicative of relatively benign optical problems whereas binocular diplopia is due to ocular misalignment.  Once restrictive disease or myasthenia gravis is excluded, the major cause of binocular diplopia is a cranial nerve lesion.  Careful clinical assessment will enable diagnosis in most, and suggest appropriate investigation and management.',NULL,309,0,36,0,1,1271174178,1),(358,'24-E','Dizziness/vertigo','\"Dizziness\" is a common but imprecise complaint.  Physicians need to determine whether it refers to true vertigo, \'dizziness\', disequilibrium, or pre-syncope/ lightheadedness. ',NULL,309,0,37,0,1,1271174178,1),(359,'25-E','Dying Patient/bereavement','Physicians are frequently faced with patients dying from incurable or untreatable diseases. In such circumstances, the important role of the physician is to alleviate any suffering by the patient and to provide comfort and compassion to both patient and family. ',NULL,309,0,38,0,1,1271174178,1),(360,'26-E','Dysphagia/difficulty Swallowing','Dysphagia should be regarded as a danger signal that indicates the need to evaluate and define the cause of the swallowing difficulty and thereafter initiate or refer for treatment.',NULL,309,0,39,0,1,1271174178,1),(361,'27-E','Dyspnea','Dyspnea is common and distresses millions of patients with pulmonary disease and myocardial dysfunction.  Assessment of the manner dyspnea is described by patients suggests that their description may provide insight into the underlying pathophysiology of the disease.',NULL,309,0,40,0,1,1271174178,1),(362,'27-1-E','Acute Dyspnea (minutes To Hours)','Shortness of breath occurring over minutes to hours is caused by a relatively small number of conditions.  Attention to clinical information and consideration of these conditions can lead to an accurate diagnosis.  Diagnosis permits initiation of therapy that can limit associated morbidity and mortality.',NULL,361,0,1,0,1,1271174178,1),(363,'27-2-E','Chronic Dyspnea (weeks To Months)','Since patients with acute dyspnea require more immediate evaluation and treatment, it is important to differentiate them from those with chronic dyspnea.  However, chronic dyspnea etiology may be harder to elucidate.  Usually patients have cardio-pulmonary disease, but symptoms may be out of proportion to the demonstrable impairment.',NULL,361,0,2,0,1,1271174178,1),(364,'27-3-E','Pediatric Dyspnea/respiratory Distress','After fever, respiratory distress is one of the commonest pediatric emergency complaints.',NULL,361,0,3,0,1,1271174178,1),(365,'28-E','Ear Pain','The cause of ear pain is often otologic, but it may be referred.  In febrile young children, who most frequently are affected by ear infections, if unable to describe the pain, a good otologic exam is crucial. (see also <a href=\"objectives.pl?lang=english&amp;loc=obj&amp;id=40-E\" title=\"Presentation 40-E\">Hearing Loss/Deafness)',NULL,309,0,41,0,1,1271174178,1),(366,'29-1-E',' Generalized Edema','Patients frequently complain of swelling.  On closer scrutiny, such swelling often represents expansion of the interstitial fluid volume.  At times the swelling may be caused by relatively benign conditions, but at times serious underlying diseases may be present.',NULL,309,0,42,0,1,1271174178,1),(367,'29-2-E',' Unilateral/local Edema','Over 90 % of cases of acute pulmonary embolism are due to emboli emanating from the proximal veins of the lower extremities.',NULL,309,0,43,0,1,1271174178,1),(368,'30-E','Eye Redness','Red eye is a very common complaint.  Despite the rather lengthy list of causal conditions, three problems make up the vast majority of causes: conjunctivitis (most common), foreign body, and iritis.  Other types of injury are relatively less common, but important because excessive manipulation may cause further damage or even loss of vision.',NULL,309,0,44,0,1,1271174178,1),(369,'31-1-E','Failure To Thrive, Elderly ','Failure to thrive for an elderly person means the loss of energy, vigor and/or weight often accompanied by a decline in the ability to function and at times associated with depression.',NULL,309,0,45,0,1,1271174178,1),(370,'31-2-E','Failure To Thrive, Infant/child','Failure to thrive is a phrase that describes the occurrence of growth failure in either height or weight in childhood.  Since failure to thrive is attributed to children&lt;2 years whose weight is below the 5th percentile for age on more than one occasion, it is essential to differentiate normal from the abnormal growth patterns.',NULL,309,0,46,0,1,1271174178,1),(371,'32-E','Falls','Falls are common (&gt;1/3 of people over 65 years; 80% among those with?4 risk factors) and 1 in 10 are associated with serious injury such as hip fracture, subdural hematoma, or head injury.  Many are preventable.  Interventions that prevent falls and their sequelae delay or reduce the frequency of nursing home admissions.',NULL,309,0,47,0,1,1271174178,1),(372,'33-E','Fatigue ','In a primary care setting, 20-30% of patients will report significant fatigue (usually not associated with organic cause).  Fatigue&lt;1 month is \'recent\';&gt;6 months, it is \'chronic\'.',NULL,309,0,48,0,1,1271174178,1),(373,'34-E','Fractures/dislocations ','Fractures and dislocations are common problems at any age and are related to high-energy injuries (e.g., motor accidents, sport injuries) or, at the other end of the spectrum, simple injuries such as falls or non-accidental injuries.  They require initial management by primary care physicians with referral for difficult cases to specialists.',NULL,309,0,49,0,1,1271174178,1),(374,'35-E','Gait Disturbances/ataxia ','Abnormalities of gait can result from disorders affecting several levels of the nervous system and the type of abnormality observed clinically often indicates the site affected.',NULL,309,0,50,0,1,1271174178,1),(375,'36-E','Genetic Concerns','Genetics have increased our understanding of the origin of many diseases.  Parents with a family history of birth defects or a previously affected child need to know that they are at higher risk of having a baby with an anomaly.  Not infrequently, patients considering becoming parents seek medical advice because of concerns they might have.  Primary care physicians must provide counseling about risk factors such as maternal age, illness, drug use, exposure to infectious or environmental agents, etc. and if necessary referral if further evaluation is necessary.',NULL,309,0,51,0,1,1271174178,1),(376,'36-1-E','Ambiguous Genitalia','Genetic males with 46, XY genotype but having impaired androgen sensitivity of varying severity may present with features that range from phenotypic females to \'normal\' males with only minor defects in masculinization or infertility.  Primary care physicians may be called upon to determine the nature of the problem.',NULL,375,0,1,0,1,1271174178,1),(377,'36-2-E','Dysmorphic Features','Three out of 100 infants are born with a genetic disorder or congenital defect.  Many of these are associated with long-term disability, making early detection and identification vital.  Although early involvement of genetic specialists in the care of such children is prudent, primary care physicians are at times required to contribute immediate care, and subsequently assist with long term management of suctients.',NULL,375,0,2,0,1,1271174178,1),(378,'37-1-E','Hyperglycemia/diabetes Mellitus','Diabetes mellitus is a very common disorder associated with a relative or absolute impairment of insulin secretion together with varying degrees of peripheral resistance to the action of insulin.  The morbidity and mortality associated with diabetic complications may be reduced by preventive measures.  Intensive glycemic control will reduce neonatal complications and reduce congenital malformations in pregnancy diabetes.',NULL,309,0,52,0,1,1271174178,1),(379,'37-2-E','Hypoglycemia','Maintenance of the blood sugar within normal limits is essential for health.  In the short-term, hypoglycemia is much more dangerous than hyperglycemia.  Fortunately, it is an uncommon clinical problem outside of therapy for diabetes mellitus. ',NULL,309,0,53,0,1,1271174178,1),(380,'38-1-E','Alopecia ','Although in themselves hair changes may be innocuous, they can be psychologically unbearable.  Frequently they may provide significant diagnostic hints of underlying disease.',NULL,309,0,54,0,1,1271174178,1),(381,'38-2-E','Nail Complaints ','Nail disorders (toenails more than fingernails), especially ingrown, infected, and painful nails, are common conditions.  Local nail problems may be acute or chronic.  Relatively simple treatment can prevent or alleviate symptoms.  Although in themselves nail changes may be innocuous, they frequently provide significant diagnostic hints of underlying disease.',NULL,309,0,55,0,1,1271174178,1),(382,'39-E','Headache','The differentiation of patients with headaches due to serious or life-threatening conditions from those with benign primary headache disorders (e.g., tension headaches or migraine) is an important diagnostic challenge.',NULL,309,0,56,0,1,1271174178,1),(383,'40-E','Hearing Loss/deafness ','Many hearing loss causes are short-lived, treatable, and/or preventable.  In the elderly, more permanent sensorineural loss occurs.  In pediatrics, otitis media accounts for 25% of office visits.  Adults/older children have otitis less commonly, but may be affected by sequelae of otitis.',NULL,309,0,57,0,1,1271174178,1),(384,'41-E','Hemiplegia/hemisensory Loss +/- Aphasia','Hemiplegia/hemisensory loss results from an upper motor neuron lesion above the mid-cervical spinal cord.  The concomitant finding of aphasia is diagnostic of a dominant cerebral hemisphere lesion.  Acute hemiplegia generally heralds the onset of serious medical conditions, usually of vascular origin, that at times are effectively treated by advanced medical and surgical techniques.</p>\r\n<p>If the sudden onset of focal neurologic symptoms and/or signs lasts&lt;24 hours, presumably it was caused by a transient decrease in blood supply rendering the brain ischemic but with blood flow restoration timely enough to avoid infarction.  This definition of transient ischemic attacks (TIA) is now recognized to be inadequate.  ',NULL,309,0,58,0,1,1271174178,1),(385,'42-1-E','Anemia','The diagnosis in a patient with anemia can be complex.  An unfocused or unstructured investigation of anemia can be costly and inefficient.  Simple tests may provide important information.  Anemia may be the sole manifestation of serious medical disease.',NULL,309,0,59,0,1,1271174178,1),(386,'42-2-E','Polycythemia/elevated Hemoglobin','The reason for evaluating patients with elevated hemoglobin levels (male 185 g/L, female 165 g/L) is to ascertain the presence or absence of polycythemia vera first, and subsequently to differentiate between the various causes of secondary erythrocytosis.',NULL,309,0,60,0,1,1271174178,1),(387,'43-E','Hirsutism/virilization','Hirsutism, terminal body hair where unusual (face, chest, abdomen, back), is a common problem, particularly in dark-haired, darkly pigmented, white women.  However, if accompanied by virilization, then a full diagnostic evaluation is essential because it is androgen-dependent.  Hypertrichosis on the other hand is a rare condition usually caused by drugs or systemic illness.',NULL,309,0,61,0,1,1271174178,1),(388,'44-E','Hoarseness/dysphonia/speech And Language Abnormalities','Patients with impairment in comprehension and/or use of the form, content, or function of language are said to have a language disorder.  Those who have correct word choice and syntax but have speech disorders may have an articulation disorder.  Almost any change in voice quality may be described as hoarseness.  However, if it lasts more than 2 weeks, especially in patients who use alcohol or tobacco, it needs to be evaluated.',NULL,309,0,62,0,1,1271174178,1),(389,'45-E','Hydrogen Ion Concentration Abnormal, Serum','Major adverse consequences may occur with severe acidemia and alkalemia despite absence of specific symptoms.  The diagnosis depends on the clinical setting and laboratory studies.  It is crucial to distinguish acidemia due to metabolic causes from that due to respiratory causes; especially important is detecting the presence of both.  Management of the underlying causes and not simply of the change in [H+] is essential.',NULL,309,0,63,0,1,1271174178,1),(390,'46-E','Infertility','Infertility, meaning the inability to conceive after one year of intercourse without contraception, affects about 15% of couples.  Both partners must be investigated; male-associated factors account for approximately half of infertility problems.  Although current emphasis is on treatment technologies, it is important to consider first the cause of the infertility and tailor the treatment accordingly.',NULL,309,0,64,0,1,1271174178,1),(391,'47-1-E','Incontinence, Stool','Fecal incontinence varies from inadvertent soiling with liquid stool to the involuntary excretion of feces.  It is a demoralizing disability because it affects self-assurance and can lead to social isolation.  It is the second leading cause of nursing home placement.',NULL,309,0,65,0,1,1271174178,1),(392,'47-2-E','Incontinence, Urine','Because there is increasing incidence of involuntary micturition with age, incontinence has increased in frequency in our ageing population.  Unfortunately, incontinence remains under treated despite its effect on quality of life and impact on physical and psychological morbidity.  Primary care physicians should diagnose the cause of incontinence in the majority of cases.',NULL,309,0,66,0,1,1271174178,1),(393,'47-3-E','Incontinence, Urine, Pediatric (enuresis)','Enuresis is the involuntary passage of urine, and may be diurnal (daytime), nocturnal (nighttime), or both.  The majority of children have primary nocturnal enuresis (20% of five-year-olds).  Diurnal and secondary enuresis is much less common, but requires differentiating between underlying diseases and stress related conditions.',NULL,309,0,67,0,1,1271174178,1),(394,'48-E','Impotence/erectile Dysfunction','Impotence is an issue that has a major impact on relationships.  There is a need to explore the impact with both partners, although many consider it a male problem.  Impotence is present when an erection of sufficient rigidity for sexual intercourse cannot be acquired or sustained&gt;75% of the time.',NULL,309,0,68,0,1,1271174178,1),(395,'49-E','Jaundice ','Jaundice may represent hemolysis or hepatobiliary disease.  Although usually the evaluation of a patient is not urgent, in a few situations it is a medical emergency (e.g., massive hemolysis, ascending cholangitis, acute hepatic failure).',NULL,309,0,69,0,1,1271174178,1),(396,'49-1-E','Neonatal Jaundice ','Jaundice, usually mild unconjugated bilirubinemia, affects nearly all newborns.  Up to 65% of full-term neonates have jaundice at 72 - 96 hours of age.  Although some causes are ominous, the majority are transient and without consequences.',NULL,395,0,1,0,1,1271174178,1),(397,'50-1-E','Joint Pain, Mono-articular (acute, Chronic)','Any arthritis can initially present as one swollen painful joint.  Thus, the early exclusion of polyarticular joint disease may be challenging.  In addition, pain caused by a problem within the joint needs to be distinguished from pain arising from surrounding soft tissues.',NULL,309,0,70,0,1,1271174178,1),(398,'50-2-E','Joint Pain, Poly-articular (acute, Chronic)','Polyarticular joint pain is common in medical practice, and causes vary from some that are self-limiting to others which are potentially disabling and life threatening.',NULL,309,0,71,0,1,1271174178,1),(399,'50-3-E','Periarticular Pain/soft Tissue Rheumatic Disorders','Pain caused by a problem within the joint needs to be distinguished from pain arising from surrounding soft tissues.',NULL,309,0,72,0,1,1271174178,1),(400,'51-E','Lipids Abnormal, Serum ','Hypercholesterolemia is a common and important modifiable risk factor for ischemic heart disease (IHD) and cerebro-vascular disease.  The relationship of elevated triglycerides to IHD is less clear (may be a modest independent predictor) but very high levels predispose to pancreatitis.  HDL cholesterol is inversely related to IHD risk.',NULL,309,0,73,0,1,1271174178,1),(401,'52-E','Liver Function Tests Abnormal, Serum','Appropriate investigation can distinguish benign reversible liver disease requiring no treatment from potentially life-threatening conditions requiring immediate therapy.',NULL,309,0,74,0,1,1271174178,1),(402,'53-E','Lump/mass, Musculoskeletal ','Lumps or masses are a common cause for consultation with a physician.  The majority will be of a benign dermatologic origin. Musculoskeletal lumps or masses are not common, but they represent an important cause of morbidity and mortality, especially among young people.',NULL,309,0,75,0,1,1271174178,1),(403,'54-E','Lymphadenopathy','Countless potential causes may lead to lymphadenopathy.  Some of these are serious but treatable.  In a study of patients with lymphadenopathy, 84% were diagnosed with benign lymphadenopathy and the majority of these were due to a nonspecific (reactive) etiology.',NULL,309,0,76,0,1,1271174178,1),(404,'54-1-E','Mediastinal Mass/hilar Adenopathy','The mediastinum contains many vital structures (heart, aorta, pulmonary hila, esophagus) that are affected directly or indirectly by mediastinal masses.  Evaluation of such masses is aided by envisaging the nature of the mass from its location in the mediastinum.</p>\r\n<p>',NULL,403,0,1,0,1,1271174178,1),(405,'55-E','Magnesium Concentration Serum, Abnormal/hypomagnesemia ','Although hypomagnesemia occurs in only about 10% of hospitalized patients, the incidence rises to over 60% in severely ill patients.  It is frequently associated with hypokalemia and hypocalcemia.',NULL,309,0,77,0,1,1271174178,1),(406,'56-1-E','Amenorrhea/oligomenorrhea','The average age of onset of menarche in North America is 11 to 13 years and menopause is approximately 50 years.  Between these ages, absence of menstruation is a cause for investigation and appropriate management.',NULL,309,0,78,0,1,1271174178,1),(407,'56-2-E','Dysmenorrhea','Approximately 30 - 50% of post-pubescent women experience painful menstruation and 10% of women are incapacitated by pain 1 - 3 days per month.  It is the single greatest cause of lost working hours and school days among young women.',NULL,309,0,79,0,1,1271174178,1),(408,'56-3-E','Pre-menstrual Syndrome (pms)','Pre-menstrual syndrome is a combination of physical, emotional, or behavioral symptoms that occur prior to the menstrual cycle and are absent during the rest of the cycle.  The symproms, on occasion, are severe enough to intefere significantly with work and/or home activities.',NULL,309,0,80,0,1,1271174178,1),(409,'57-E','Menopause ','Women cease to have menstrual periods at about 50 years of age, although ovarian function declines earlier.  Changing population demographics means that the number of women who are menopausal will continue to grow, and many women will live 1/3 of their lives after ovarian function ceases.  Promotion of health maintenance in this group of women will enhance physical, emotional, and sexual quality of life.',NULL,309,0,81,0,1,1271174178,1),(410,'58-1-E','Coma','Patients with altered level of consciousness account for 5% of hospital admissions.  Coma however is defined as a state of pathologic unconsciousness (unarousable).',NULL,309,0,82,0,1,1271174178,1),(411,'58-2-E','Delirium/confusion ','An acute confusional state in patients with medical illness, especially among those who are older, is extremely common.  Between 10 - 15% of elderly patients admitted to hospital have delirium and up to a further 30% develop delirium while in hospital.  It represents a disturbance of consciousness with reduced ability to focus, sustain, or shift attention (DSM-IV).  This disturbance tends to develop over a short period of time (hours to days) and tends to fluctuate during the course of the day.  A clear understanding of the differential diagnosis enables rapid and appropriate management.',NULL,309,0,83,0,1,1271174178,1),(412,'58-3-E','Dementia','Dementia is a problem physicians encounter frequently, and causes that are potentially treatable require identification.  Alzheimer disease is the most common form of dementia in the elderly (about 70%), and primary care physicians will need to diagnose and manage the early cognitive manifestations.',NULL,309,0,84,0,1,1271174178,1),(413,'59-E','Mood Disorders ','Depression is one of the top five diagnoses made in the offices of primary care physicians.  Depressed mood occurs in some individuals as a normal reaction to grief, but in others it is considered abnormal because it interferes with the person\'s daily function (e.g., self-care, relationships, work, self-support).  Thus, it is necessary for primary care clinicians to detect depression, initiate treatment, and refer to specialists for assistance when required.',NULL,309,0,85,0,1,1271174178,1),(414,'60-E','Mouth Problems','Although many disease states can affect the mouth, the two most common ones are odontogenic infections (dental carries and periodontal infections) and oral carcinoma. Almost 15% of the population have significant periodontal disease despite its being preventable.  Such infections, apart from the discomfort inflicted, may result in serious complications.',NULL,309,0,86,0,1,1271174178,1),(415,'61-E','Movement Disorders,involuntary/tic Disorders','Movement disorders are regarded as either excessive (hyperkinetic) or reduced (bradykinetic) activity.  Diagnosis depends primarily on careful observation of the clinical features. ',NULL,309,0,87,0,1,1271174178,1),(416,'62-1-E','Diastolic Murmur','Although systolic murmurs are often \"innocent\" or physiological, diastolic murmurs are virtually always pathologic.',NULL,309,0,88,0,1,1271174178,1),(417,'62-2-E','Heart Sounds, Pathological','Pathological heart sounds are clues to underlying heart disease.',NULL,309,0,89,0,1,1271174178,1),(418,'62-3-E','Systolic Murmur','Ejection systolic murmurs are common, and frequently quite \'innocent\' (with absence of cardiac findings and normal splitting of the second sound).',NULL,309,0,90,0,1,1271174178,1),(419,'63-E','Neck Mass/goiter/thyroid Disease ','The vast majority of neck lumps are benign (usually reactive lymph nodes or occasionally of congenital origin).  The lumps that should be of most concern to primary care physicians are the rare malignant neck lumps.  Among patients with thyroid nodules, children, patients with a family history or history for head and neck radiation, and adults&lt;30 years or&gt;60 years are at higher risk for thyroid cancer.',NULL,309,0,91,0,1,1271174178,1),(420,'64-E','Newborn, Depressed','A call requesting assistance in the delivery of a newborn may be \"routine\" or because the neonate is depressed and requires resuscitation.  For any type of call, the physician needs to be prepared to manage potential problems.',NULL,309,0,92,0,1,1271174178,1),(421,'65-E','Non-reassuring Fetal Status (fetal Distress)','Non-reassuring fetal status occurs in 5 - 10% of pregnancies.  (Fetal distress, a term also used, is imprecise and has a low positive predictive value.  The newer term should be used.)  Early detection and pro-active management can reduce serious consequences and prepare parents for eventualities.',NULL,309,0,93,0,1,1271174178,1),(422,'66-E','Numbness/tingling/altered Sensation','Disordered sensation may be alarming and highly intrusive.  The physician requires a framework of knowledge in order to assess abnormal sensation, consider the likely site of origin, and recognise the implications.',NULL,309,0,94,0,1,1271174178,1),(423,'67-E','Pain','Because pain is considered a signal of disease, it is the most common symptom that brings a patient to a physician.  Acute pain is a vital protective mechanism.  In contrast, chronic pain (&gt;6 weeks or lasting beyond the ordinary duration of time that an injury needs to heal) serves no physiologic role and is itself a disease state.  Pain is an unpleasant somatic sensation, but it is also an emotion.  Although control of pain/discomfort is a crucial endpoint of medical care, the degree of analgesia provided is often inadequate, and may lead to complications (e.g., depression, suicide).  Physicians should recognise the development and progression of pain, and develop strategies for its control.',NULL,309,0,95,0,1,1271174178,1),(424,'67-1-2-1-E',' Generalized Pain Disorders','Fibromyalgia, a common cause of chronic musculoskeletal pain and fatigue, has no known etiology and is not associated with tissue inflammation.  It affects muscles, tendons, and ligaments.  Along with a group of similar conditions, fibromyalgia is controversial because obvious sign and laboratory/radiological abnormalities are lacking.</p>\r\n<p>Polymyalgia rheumatica, a rheumatic condition frequently linked to giant cell (temporal) arteritis, is a relatively common disorder (prevalence of about 700/100,000 persons over 50 years of age).  Synovitis is considered to be the cause of the discomfort.',NULL,423,0,1,0,1,1271174178,1),(425,'67-1-2-3-E','Local Pain, Hip/knee/ankle/foot','With the current interest in physical activity, the commonest cause of leg pain is muscular or ligamentous strain.  The knee, the most intricate joint in the body, has the greatest susceptibility to pain.',NULL,423,0,2,0,1,1271174178,1),(426,'67-1-2-2-E','Local Pain, Shoulder/elbow/wrist/hand','After backache, upper extremity pain is the most common type of musculoskeletal pain.',NULL,423,0,3,0,1,1271174178,1),(427,'67-1-2-4-E','Local Pain, Spinal Compression/osteoporosis','Spinal compression is one manifestation of osteoporosis, the prevalence of which increases with age.  As the proportion of our population in old age rises, osteoporosis becomes an important cause of painful fractures, deformity, loss of mobility and independence, and even death.  Although less common in men, the incidence of fractures increases exponentially with ageing, albeit 5 - 10 years later.  For unknown reasons, the mortality associated with fractures is higher in men than in women.',NULL,423,0,4,0,1,1271174178,1),(428,'67-1-2-6-E','Local Pain, Spine/low Back Pain','Low back pain is one of the most common physical complaints and a major cause of lost work time.  Most frequently it is associated with vocations that involve lifting, twisting, bending, and reaching.  In individuals suffering from chronic back pain, 5% will have an underlying serious disease.',NULL,423,0,5,0,1,1271174178,1),(429,'67-1-2-5-E','Local Pain, Spine/neck/thoracic','Approximately 10 % of the adult population have neck pain at any one time.  This prevalence is similar to low back pain, but few patients lose time from work and the development of neurologic deficits is&lt;1 %.',NULL,423,0,6,0,1,1271174178,1),(430,'67-2-2-E','Central/peripheral Neuropathic Pain','Neuropathic pain is caused by dysfunction of the nervous system without tissue damage.  The pain tends to be chronic and causes great discomfort.',NULL,423,0,7,0,1,1271174178,1),(431,'67-2-1-E','Sympathetic/complex Regional Pain Syndrome/reflex Sympatheti','Following an injury or vascular event (myocardial infarction, stroke), a disorder may develop that is characterized by regional pain and sensory changes (vasomotor instability, skin changes, and patchy bone demineralization).',NULL,423,0,8,0,1,1271174178,1),(432,'68-E','Palpitations (abnormal Ecg-arrhythmia)','Palpitations are a common symptom.  Although the cause is often benign, occasionally it may indicate the presence of a serious underlying problem.',NULL,309,0,96,0,1,1271174178,1),(433,'69-E','Panic And Anxiety ','Panic attacks/panic disorders are common problems in the primary care setting.  Although such patients may present with discrete episodes of intense fear, more commonly they complain of one or more physical symptoms.  A minority of such patients present to mental health settings, whereas 1/3 present to their family physician and another 1/3 to emergency departments.  Generalized anxiety disorder, characterized by excessive worry and anxiety that are difficult to control, tends to develop secondary to other psychiatric conditions.',NULL,309,0,97,0,1,1271174178,1),(434,'70-E','Pap Smear Screening','Carcinoma of the cervix is a preventable disease.  Any female patient who visits a physician\'s office should have current screening guidelines applied and if appropriate, a Pap smear should be recommended.',NULL,309,0,98,0,1,1271174178,1),(435,'71-E','Pediatric Emergencies  - Acutely Ill Infant/child','Although pediatric emergencies such as the ones listed below are discussed with the appropriate condition, the care of the patient in the pediatric age group demands special skills',NULL,309,0,99,0,1,1271174178,1),(436,'71-1-E','Crying/fussing Child','A young infant whose only symptom is crying/fussing challenges the primary care physician to distinguish between benign and organic causes.',NULL,435,0,1,0,1,1271174178,1),(437,'71-2-E','Hypotonia/floppy Infant/child','Infants/children with decreased resistance to passive movement differ from those with weakness and hyporeflexia.  They require detailed, careful neurologic evaluation. Management programs, often life-long, are multidisciplinary and involve patients, family, and community.',NULL,435,0,2,0,1,1271174178,1),(438,'72-E','Pelvic Mass','Pelvic masses are common and may be found in a woman of any age, although the possible etiologies differ among age groups.  There is a need to diagnose and investigate them since early detection may affect outcome.',NULL,309,0,100,0,1,1271174178,1),(439,'73-E','Pelvic Pain','Acute pelvic pain is potentially life threatening.  Chronic pelvic pain is one of the most common problems in gynecology.  Women average 2 - 3 visits each year to physicians\' offices with chronic pelvic pain.  At present, only about one third of these women are given a specific diagnosis.  The absence of a clear diagnosis can frustrate both patients and clinicians.  Once the diagnosis is established, specific and usually successful treatment may be instituted.',NULL,309,0,101,0,1,1271174178,1),(440,'74-E','Periodic Health Examination (phe) ','Periodically, patients visit physicians\' office not because they are unwell, but because they want a \'check-up\'.  Such visits are referred to as health maintenance or the PHE. The PHE is an opportunity to relate to an asymptomatic patient for the purpose of case finding and screening for undetected disease and risky behaviour.  It is also an opportunity for health promotion and disease prevention.  The decision to include or exclude a medical condition in the PHE should be based on the burden of suffering caused by the condition, the quality of the screening, and effectiveness of the intervention.',NULL,309,0,102,0,1,1271174178,1),(441,'74-2-E','Infant And Child Immunization ','Immunization has reduced or eradicated many infectious diseases and has improved overall world health.  Recommended immunization schedules are constantly updated as new vaccines become available.',NULL,440,0,1,0,1,1271174178,1),(442,'74-1-E','Newborn Assessment/nutrition ','Primary care physicians play a vital role in identifying children at risk for developmental and other disorders that are threatening to life or long-term health before they become symptomatic.  In most cases, parents require direction and reassurance regarding the health status of their newborn infant.  With respect to development, parental concerns regarding the child\'s language development, articulation, fine motor skills, and global development require careful assessment.',NULL,440,0,2,0,1,1271174178,1),(443,'74-3-E','Pre-operative Medical Evaluation','Evaluation of patients prior to surgery is an important element of comprehensive medical care.  The objectives of such an evaluation include the detection of unrecognized disease that may increase the risk of surgery and how to minimize such risk.',NULL,440,0,3,0,1,1271174178,1),(444,'74-4-E','Work-related Health Issues ','Physicians will encounter health hazards in their own work place, as well as in patients\' work place.  These hazards need to be recognised and addressed.  A patient\'s reported environmental exposures may prompt interventions important in preventing future illnesses/injuries.  Equally important, physicians can not only play an important role in preventing occupational illness but also in promoting environmental health.',NULL,440,0,4,0,1,1271174178,1),(445,'75-E','Personality Disorders ','Personality disorders are persistent and maladaptive patterns of behaviour exhibited over a wide variety of social, occupational, and relationship contexts and leading to distress and impairment.  They represent important risk factors for a variety of medical, interpersonal, and psychiatric difficulties.  For example, patients with personality difficulties may attempt suicide, or may be substance abusers.  As a group, they may alienate health care providers with angry outbursts, high-risk behaviours, signing out against medical advice, etc.',NULL,309,0,103,0,1,1271174178,1),(446,'76-E','Pleural Effusion/pleural Abnormalities',NULL,NULL,309,0,104,0,1,1271174178,1),(447,'77-E','Poisoning','Exposures to poisons or drug overdoses account for 5 - 10% of emergency department visits, and&gt;5 % of admissions to intensive care units.  More than 50 % of these patients are children less than 6 years of age.',NULL,309,0,105,0,1,1271174178,1),(448,'78-4-E','Administration Of Effective Health Programs At The Populatio','Knowing the organization of the health care and public health systems in Canada as well as how to determine the most cost-effective interventions are becoming key elements of clinical practice. Physicians also must work well in multidisciplinary teams within the current system in order to achieve the maximum health benefit for all patients and residents. ',NULL,309,0,106,0,1,1271174178,1),(449,'78-2-E','Assessing And Measuring Health Status At The Population Leve','Knowing the health status of the population allows for better planning and evaluation of health programs and tailoring interventions to meet patient/community needs. Physicians are also active participants in disease surveillance programs, encouraging them to address health needs in the population and not merely health demands.',NULL,309,0,107,0,1,1271174178,1),(450,'78-1-E','Concepts Of Health And Its Determinants','Concepts of health, illness, disease and the socially defined sick role are fundamental to understanding the health of a community and to applying that knowledge to the patients that a physician serves. With advances in care, the aspirations of patients for good health have expanded and this has placed new demands on physicians to address issues that are not strictly biomedical in nature. These concepts are also important if the physician is to understand health and illness behaviour. ',NULL,309,0,108,0,1,1271174178,1),(451,'78-6-E','Environment','Environmental issues are important in medical practice because exposures may be causally linked to a patient\'s clinical presentation and the health of the exposed population. A physician is expected to work with regulatory agencies to help implement the necessary interventions to prevent future illness.  Physician involvement is important in the promotion of global environmental health.',NULL,309,0,109,0,1,1271174178,1),(452,'78-7-E','Health Of Special Populations','Health equity is defined as each person in society having an equal opportunity for health. Each community is composed of diverse groups of individuals and sub-populations. Due to variations in factors such as physical location, culture, behaviours, age and gender structure, populations have different health risks and needs that must be addressed in order to achieve health equity.  Hence physicians need to be aware of the differing needs of population groups and must be able to adjust service provision to ensure culturally safe communications and care.',NULL,309,0,110,0,1,1271174178,1),(453,'78-3-E','Interventions At The Population Level','Many interventions at the individual level must be supported by actions at the community level. Physicians will be expected to advocate for community wide interventions and to address issues that occur to many patients across their practice. ',NULL,309,0,111,0,1,1271174178,1),(454,'78-5-E','Outbreak Management','Physicians are crucial participants in the control of outbreaks of disease. They must be able to diagnose cases, recognize outbreaks, report these to public health authorities and work with authorities to limit the spread of the outbreak. A common example includes physicians working in nursing homes and being asked to assist in the control of an outbreak of influenza or diarrhea.',NULL,309,0,112,0,1,1271174178,1),(455,'79-1-E','Hyperkalemia ','Hyperkalemia may have serious consequences (especially cardiac) and may also be indicative of the presence of serious associated medical conditions.',NULL,309,0,113,0,1,1271174178,1),(456,'79-2-E','Hypokalemia ','Hypokalemia, a common clinical problem, is most often discovered on routine analysis of serum electrolytes or ECG results.  Symptoms usually develop much later when depletion is quite severe.',NULL,309,0,114,0,1,1271174178,1),(457,'80-1-E','Antepartum Care ','The purpose of antepartum care is to help achieve as good a maternal and infant outcome as possible.  This means that psychosocial issues as well as biological issues need to be addressed.',NULL,309,0,115,0,1,1271174178,1),(458,'80-2-E','Intrapartum Care/postpartum Care ','Intrapartum and postpartum care means the care of the mother and fetus during labor and the six-week period following birth during which the reproductive tract returns to its normal nonpregnant state.  Of pregnant women, 85% will undergo spontaneous labor between 37 and 42 weeks of gestation.  Labor is the process by which products of conception are delivered from the uterus by progressive cervical effacement and dilatation in the presence of regular uterine contractions.',NULL,309,0,116,0,1,1271174178,1),(459,'80-3-E','Obstetrical Complications ','Virtually any maternal medical or surgical condition can complicate the course of a pregnancy and/or be affected by the pregnancy.  In addition, conditions arising in pregnancy can have adverse effects on the mother and/or the fetus.  For example, babies born prematurely account for&gt;50% of perinatal morbidity and mortality; an estimated 5% of women will describe bleeding of some extent during pregnancy, and in some patients the bleeding will endanger the mother.',NULL,309,0,117,0,1,1271174178,1),(460,'81-E','Pregnancy Loss','A miscarriage or abortion is a pregnancy that ends before the fetus can live outside the uterus.  The term also means the actual passage of the uterine contents.  It is very common in early pregnancy; up to 20% of pregnant women have a miscarriage before 20 weeks of pregnancy, 80% of these in the first 12 weeks.',NULL,309,0,118,0,1,1271174178,1),(461,'82-E','Prematurity','The impact of premature birth is best summarized by the fact that&lt;10% of babies born prematurely in North America account for&gt;50% of all perinatal morbidity and mortality.  Yet outcomes, although guarded, can be rewarding given optimal circumstances.',NULL,309,0,119,0,1,1271174178,1),(462,'83-E','Prolapse/pelvic Relaxation','Patients with pelvic relaxation present with a forward and downward drop of the pelvic organs (bladder, rectum).  In order to identify patients who would benefit from therapy, the physician should be familiar with the manifestations of pelvic relaxation (uterine prolapse, vaginal vault prolapse, cystocele, rectocele, and enterocele) and have an approach to management.',NULL,309,0,120,0,1,1271174178,1),(463,'84-E','Proteinuria ','Urinalysis is a screening procedure used frequently for insurance and routine examinations.  Proteinuria is usually identified by positive dipstick on routine urinalysis. Persistent proteinuria often implies abnormal glomerular function.',NULL,309,0,121,0,1,1271174178,1),(464,'85-E','Pruritus ','Itching is the most common symptom in dermatology.  In the absence of primary skin lesions, generalised pruritus can be indicative of an underlying systemic disorder.  Most patients with pruritus do not have a systemic disorder and the itching is due to a cutaneous disorder.',NULL,309,0,122,0,1,1271174178,1),(465,'86-E','Psychotic Patient/disordered Thought','Psychosis is a general term for a major mental disorder characterized by derangement of personality and loss of contact with reality, often with false beliefs (delusions), disturbances in sensory perception (hallucinations), or thought disorders (illusions). Schizophrenia is both the most common (1% of world population) and the classic psychotic disorder.  There are other psychotic syndromes that do not meet the diagnostic criteria for schizophrenia, some of them caused by general medical conditions or induced by a substance (alcohol, hallucinogens, steroids).  In the evaluation of any psychotic patient in a primary care setting all of these possibilities need to be considered.',NULL,309,0,123,0,1,1271174178,1),(466,'87-E','Pulse Abnormalities/diminished/absent/bruits','Arterial pulse characteristics should be assessed as an integral part of the physical examination.  Carotid, radial, femoral, posterior tibial, and dorsalis pedis pulses should be examined routinely on both sides, and differences, if any, in amplitude, contour, and upstroke should be ascertained.',NULL,309,0,124,0,1,1271174178,1),(467,'88-E','Pupil Abnormalities ','Pupillary disorders of changing degree are in general of little clinical importance.  If only one pupil is fixed to light, it is suspicious of the effect of mydriatics.  However, pupillary disorders with neurological symptoms may be of significance.',NULL,309,0,125,0,1,1271174178,1),(468,'89-1-E','Acute Renal Failure (anuria/oliguria/arf)','A sudden and rapid rise in serum creatinine is a common finding.  A competent physician is required to have an organised approach to this problem.',NULL,309,0,126,0,1,1271174178,1),(469,'89-2-E','Chronic Renal Failure ','Although specialists in nephrology will care for patients with chronic renal failure, family physicians will need to identify patients at risk for chronic renal disease, will participate in treatment to slow the progression of chronic renal disease, and will care for other common medical problems that afflict these patients.  Physicians must realise that patients with chronic renal failure have unique risks and that common therapies may be harmful because kidneys are frequently the main routes for excretion of many drugs.',NULL,309,0,127,0,1,1271174178,1),(470,'90-E','Scrotal Mass ','In children and adolescents, scrotal masses vary from incidental, requiring only reassurance, to acute pathologic events.  In adults, tumors of the testis are relatively uncommon (only 1 - 2 % of malignant tumors in men), but are considered of particular importance because they affect predominantly young men (25 - 34 years).  In addition, recent advances in management have resulted in dramatic improvement in survival rate.',NULL,309,0,128,0,1,1271174178,1),(471,'91-E','Scrotal Pain ','In most scrotal disorders, there is swelling of the testis or its adnexae.  However, some conditions are not only associated with pain, but pain may precede the development of an obvious mass in the scrotum.',NULL,309,0,129,0,1,1271174178,1),(472,'92-E','Seizures (epilepsy)','Seizures are an important differential diagnosis of syncope.  A seizure is a transient neurological dysfunction resulting from excessive/abnormal electrical discharges of cortical neurons.  They may represent epilepsy (a chronic condition characterized by recurrent seizures) but need to be differentiated from a variety of secondary causes.',NULL,309,0,130,0,1,1271174178,1),(473,'93-1-E','Sexual Maturation, Abnormal ','Sexual development is important to adolescent perception of self-image and wellbeing. Many factors may disrupt the normal progression to sexual maturation.',NULL,309,0,131,0,1,1271174178,1),(474,'94-E','Sexually Concerned Patient/gender Identity Disorder','The social appropriateness of sexuality is culturally determined.  The physician\'s own sexual attitude needs to be recognised and taken into account in order to deal with the patient\'s concern in a relevant manner.  The patient must be set at ease in order to make possible discussion of private and sensitive sexual issues.',NULL,309,0,132,0,1,1271174178,1),(475,'95-E','Skin Ulcers/skin Tumors (benign And Malignant)',NULL,NULL,309,0,133,0,1,1271174178,1),(476,'96-E','Skin Rash, Macules',NULL,NULL,309,0,134,0,1,1271174178,1),(477,'97-E','Skin Rash, Papules',NULL,NULL,309,0,135,0,1,1271174178,1),(478,'97-1-E','Childhood Communicable Diseases ','Communicable diseases are common in childhood and vary from mild inconveniences to life threatening disorders.  Physicians need to differentiate between these common conditions and initiate management.',NULL,477,0,1,0,1,1271174178,1),(479,'97-2-E','Urticaria/angioedema/anaphylaxis',NULL,NULL,477,0,2,0,1,1271174178,1),(480,'98-E','Sleep And Circadian Rhythm Disorders/sleep Apnea Syndrome/in','Insomnia is a symptom that affects 1/3 of the population at some time, and is a persistent problem in 10 % of the population.  Affected patients complain of difficulty in initiating and maintaining sleep, and this inability to obtain adequate quantity and quality of sleep results in impaired daytime functioning.',NULL,309,0,136,0,1,1271174178,1),(481,'99-1-E','Hypernatremia ','Although not extremely common, hypernatremia is likely to be encountered with increasing frequency in our ageing population.  It is also encountered at the other extreme of life, the very young, for the same reason: an inability to respond to thirst by drinking water.',NULL,309,0,137,0,1,1271174178,1),(482,'99-2-E','Hyponatremia ','Hyponatremia is detected in many asymptomatic patients because serum electrolytes are measured almost routinely.  In children with sodium depletion, the cause of the hyponatremia is usually iatrogenic.  The presence of hyponatremia may predict serious neurologic complications or be relatively benign.',NULL,309,0,138,0,1,1271174178,1),(483,'100-E','Sore Throat (rhinorrhea) ','Rhinorrhea and sore throat occurring together indicate a viral upper respiratory tract infection such as the \"common cold\".  Sore throat may be due to a variety of bacterial and viral pathogens (as well as other causes in more unusual circumstances).  Infection is transmitted from person to person and arises from direct contact with infected saliva or nasal secretions.  Rhinorrhea alone is not infective and may be seasonal (hay fever or allergic rhinitis) or chronic (vaso-motor rhinitis).',NULL,309,0,139,0,1,1271174178,1),(484,'100-1-E','Smell/taste Dysfunction ','In order to evaluate patients with smell or taste disorders, a multi-disciplinary approach is required.  This means that in addition to the roles specialists may have, the family physician must play an important role.',NULL,483,0,1,0,1,1271174178,1),(485,'101-E','Stature Abnormal (tall Stature/short Stature)','To define any growth point, children should be measured accurately and each point (height, weight, and head circumference) plotted.  One of the more common causes of abnormal growth is mis-measurement or aberrant plotting.',NULL,309,0,140,0,1,1271174178,1),(486,'102-E','Strabismus And/or Amblyopia ','Parental concern about children with a wandering eye, crossing eye, or poor vision in one eye makes it necessary for physicians to know how to manage such problems.',NULL,309,0,141,0,1,1271174178,1),(487,'103-E','Substance Abuse/drug Addiction/withdrawal','Alcohol and nicotine abuse is such a common condition that virtually every clinician is confronted with their complications.  Moreover, 10 - 15% of outpatient visits as well as 25 - 40% of hospital admissions are related to substance abuse and its sequelae.',NULL,309,0,142,0,1,1271174178,1),(488,'104-E','Sudden Infant Death Syndrome(sids)/acute Life Threatening Ev','SIDS and/or ALTE are a devastating event for parents, caregivers and health care workers alike.  It is imperative that the precursors, probable cause and parental concerns are extensively evaluated to prevent recurrence.',NULL,309,0,143,0,1,1271174178,1),(489,'105-E','Suicidal Behavior','Psychiatric emergencies are common and serious problems.  Suicidal behaviour is one of several psychiatric emergencies which physicians must know how to assess and manage.',NULL,309,0,144,0,1,1271174178,1),(490,'106-E','Syncope/pre-syncope/loss Of Consciousness  (fainting)','Syncopal episodes, an abrupt and transient loss of consciousness followed by a rapid and usually complete recovery, are common.  Physicians are required to distinguish syncope from seizures, and benign syncope from syncope caused by serious underlying illness.',NULL,309,0,145,0,1,1271174178,1),(491,'107-3-E','Fever In A Child/fever In A Child Less Than Three Weeks','Fever in children is the most common symptom for which parents seek medical advice.  While most causes are self-limited viral infections (febrile illness of short duration) it is important to identify serious underlying disease and/or those other infections amenable to treatment.',NULL,309,0,146,0,1,1271174178,1),(492,'107-4-E','Fever In The Immune Compromised Host/recurrent Fever','Patients with certain immuno-deficiencies are at high risk for infections.  The infective organism and site depend on the type and severity of immuno-suppression.  Some of these infections are life threatening.',NULL,309,0,147,0,1,1271174178,1),(493,'107-2-E','Fever Of Unknown Origin ','Unlike acute fever (&lt;2 weeks), which is usually either viral (low-grade, moderate fever) or bacterial (high grade, chills, rigors) in origin, fever of unknown origin is an illness of three weeks or more without an established diagnosis despite appropriate investigation.',NULL,309,0,148,0,1,1271174178,1),(494,'107-1-E','Hyperthermia ','Hyperthermia is an elevation in core body temperature due to failure of thermo-regulation (in contrast to fever, which is induced by cytokine activation).  It is a medical emergency and may be associated with severe complications and death.  The differential diagnosis is extensive (includes all causes of fever).',NULL,309,0,149,0,1,1271174178,1),(495,'107-5-E','Hypothermia ','Hypothermia is the inability to maintain core body temperature.  Although far less common than is elevation in temperature, hypothermia (central temperature ? 35Ãƒâ€šÃ‚Â°C) is of considerable importance because it can represent a medical emergency.  Severe hypothermia is defined as a core temperature of &lt;28Ãƒâ€šÃ‚Â°C.',NULL,309,0,150,0,1,1271174178,1),(496,'108-E','Tinnitus','Tinnitus is an awareness of sound near the head without an obvious external source.  It may involve one or both ears, be continuous or intermittent.  Although not usually related to serious medical problems, in some it may interfere with daily activities, affect quality of life, and in a very few be indicative of serious organic disease.',NULL,309,0,151,0,1,1271174178,1),(497,'109-E','Trauma/accidents','Management of patients with traumatic injuries presents a variety of challenges.  They require evaluation in the emergency department for triage and prevention of further deterioration prior to transfer or discharge.  Early recognition and management of complications along with aggressive treatment of underlying medical conditions are necessary to minimise morbidity and mortality in this patient population.',NULL,309,0,152,0,1,1271174178,1),(498,'109-1-E','Abdominal Injuries ','The major causes of blunt trauma are motor vehicles, auto-pedestrian injuries, and motorcycle/all terrain vehicle injuries.  In children, bicycle injuries, falls, and child abuse also contribute.  Assessment of a patient with an abdominal injury is difficult.  As a consequence, important injuries tend to be missed.  Rupture of a hollow viscus or bleeding from a solid organ may produce few clinical signs.',NULL,497,0,1,0,1,1271174178,1),(499,'109-2-E','Bites, Animal/insects ','Since so many households include pets, animal bite wounds are common.  Dog and cat bites account for about 1% of emergency visits, the majority in children.  Some can be serious and lead to limb damage, and at times permanent disability.</p>\r\n<p>Insect bites in Canada most commonly cause a local inflammatory reaction that subsides within a few hours and is mostly a nuisance.  In contrast, mosquitoes can transmit infectious disease to more than 700 million people in other geographic areas of the world (e.g., malaria, yellow fever, dengue, encephalitis and filariasis among others), as well as in Canada.  Tick-borne illness is also common.  On the other hand, systemic reactions to insect bites are extremely rare compared with insect stings.  The most common insects associated with systemic allergic reactions were blackflies, deerflies, and horseflies.',NULL,497,0,2,0,1,1271174178,1),(500,'109-3-E','Bone/joint Injury','Major fractures are at times associated with other injuries, and priorities must be set for each patient.  For example, hemodynamic stability takes precedence over fracture management, but an open fracture should be managed as soon as possible.  On the other hand, management of many soft tissue injuries is facilitated by initial stabilization of bone or joint injury. Unexplained fractures in children should alert physicians to the possibility of abuse.',NULL,497,0,3,0,1,1271174178,1),(501,'109-4-E','Chest Injuries ','Injury to the chest may be blunt (e.g., motor vehicle accident resulting in steering wheel blow to sternum, falls, explosions, crush injuries) or penetrating (knife/bullet).  In either instance, emergency management becomes extremely important to the eventual outcome.',NULL,497,0,4,0,1,1271174178,1),(502,'109-6-E','Drowning (near-drowning) ','Survival after suffocation by submersion in a liquid medium, including loss of consciousness, is defined as near drowning.  The incidence is uncertain, but likely it may occur several hundred times more frequently than drowning deaths (150,000/year worldwide).',NULL,497,0,5,0,1,1271174178,1),(503,'109-8-E','Facial Injuries ','Facial injuries are potentially life threatening because of possible damage to the airway and central nervous system.',NULL,497,0,6,0,1,1271174178,1),(504,'109-9-E','Hand/wrist Injuries ','Hand injuries are common problems presenting to emergency departments.  The ultimate function of the hand depends upon the quality of the initial care, the severity of the original injury and rehabilitation.',NULL,497,0,7,0,1,1271174178,1),(505,'109-10-E','Head Trauma/brain Death/transplant Donations','Most head trauma is mild and not associated with brain injury or long-term sequelae. Improved outcome after head trauma depends upon preventing deterioration and secondary brain injury.  Serious intracranial injuries may remain undetected due to failure to obtain an indicated head CT.',NULL,497,0,8,0,1,1271174178,1),(506,'109-11-E','Nerve Injury ','Peripheral nerve injuries often occur as part of more extensive injuries and tend to go unrecognized.  Evaluation of these injuries is based on an accurate knowledge of the anatomy and function of the nerve(s) involved.',NULL,497,0,9,0,1,1271174178,1),(507,'109-12-E','Skin Wounds/regional Anaesthesia','Skin and subcutaneous wounds tend to be superficial and can be repaired under local anesthesia.  Animal bite wounds are common and require special consideration.  Since so many households include pets, dog and cat bites account for about 1% of emergency visits, the majority in children.  Some can be serious and lead to limb damage, and at times permanent disability.',NULL,497,0,10,0,1,1271174178,1),(508,'109-13-E','Spinal Trauma','Most spinal cord injuries are a result of car accidents, falls, sports-related trauma, or assault with weapons.  The average age at the time of spinal injury is approximately 35 years, and men are four times more likely to be injured than are women.  The sequelae of such events are dire in terms of effect on patient, family, and community.  Initial immobilization and maintenance of ventilation are of critical importance.',NULL,497,0,11,0,1,1271174178,1),(509,'109-14-E','Urinary Tract Injuries ','Urinary tract injuries are usually closed rather than penetrating, and may affect the kidneys and/or the collecting system.',NULL,497,0,12,0,1,1271174178,1),(510,'109-15-E','Vascular Injury ','Vascular injuries are becoming more common.  Hemorrhage may be occult and require a high index of suspicion (e.g., fracture in an adjacent bone).',NULL,497,0,13,0,1,1271174178,1),(511,'110-1-E','Dysuria And/or Pyuria ','Patients with urinary tract infections, especially the very young and very old, may present in an atypical manner.  Appropriate diagnosis and management may prevent significant morbidity.  Dysuria may mean discomfort/pain on micturition or difficulty with micturition.  Pain usually implies infection whereas difficulty is usually related to distal mechanical obstruction (e.g., prostatic).',NULL,309,0,153,0,1,1271174178,1),(512,'110-2-E','Polyuria/polydipsia','Urinary frequency, a common complaint, can be confused with polyuria, a less common, but important complaint.  Diabetes mellitus is a common disorder with morbidity and mortality that can be reduced by preventive measures.  Intensive glycemic control during pregnancy will reduce neonatal complications.',NULL,309,0,154,0,1,1271174178,1),(513,'111-E','Urinary Obstruction/hesitancy/prostatic Cancer','Urinary tract obstruction is a relatively common problem.  The obstruction may be complete or incomplete, and unilateral or bilateral.  Thus, the consequences of the obstruction depend on its nature.',NULL,309,0,155,0,1,1271174178,1),(514,'112-E','Vaginal Bleeding, Excessive/irregular/abnormal','Vaginal bleeding is considered abnormal when it occurs at an unexpected time (before menarche or after menopause) or when it varies from the norm in amount or pattern (urinary tract and bowel should be excluded as a source).  Amount or pattern is considered outside normal when it is associated with iron deficiency anemia, it lasts&gt;7days, flow is&gt;80ml/clots, or interval is&lt;24 days.',NULL,309,0,156,0,1,1271174178,1),(515,'113-E','Vaginal Discharge/vulvar Itch/std ','Vaginal discharge, with or without pruritus, is a common problem seen in the physician\'s office.',NULL,309,0,157,0,1,1271174178,1),(516,'114-E','Violence, Family','There are a number of major psychiatric emergencies and social problems which physicians must be prepared to assess and manage.  Domestic violence is one of them, since it has both direct and indirect effects on the health of populations.  Intentional controlling or violent behavior (physical, sexual, or emotional abuse, economic control, or social isolation of the victim) by a person who is/was in an intimate relationship with the victim is domestic violence.  The victim lives in a state of constant fear, terrified about when the next episode of abuse will occur.  Despite this, abuse frequently remains hidden and undiagnosed because patients often conceal that they are in abusive relationships.  It is important for clinicians to seek the diagnosis in certain groups of patients.',NULL,309,0,158,0,1,1271174178,1),(517,'114-3-E','Adult Abuse/spouse Abuse ','The major problem in spouse abuse is wife abuse (some abuse of husbands has been reported).  It is the abuse of power in a relationship involving domination, coercion, intimidation, and the victimization of one person by another.  Ten percent of women in a relationship with a man have experienced abuse.  Of women presenting to a primary care clinic, almost 1/3 reported physical and verbal abuse.',NULL,516,0,1,0,1,1271174178,1),(518,'114-1-E','Child Abuse, Physical/emotional/sexual/neglect/self-induced ','Child abuse is intentional harm to a child by the caregiver.  It is part of the spectrum of family dysfunction and leads to significant morbidity and mortality (recently sexual attacks on children by groups of other children have increased).  Abuse causes physical and emotional trauma, and may present as neglect.  The possibility of abuse must be in the mind of all those involved in the care of children who have suffered traumatic injury or have psychological or social disturbances (e.g., aggressive behavior, stress disorder, depressive disorder, substance abuse, etc.).',NULL,516,0,2,0,1,1271174178,1),(519,'114-2-E','Elderly Abuse ','Abuse of the elderly may represent an act or omission that results in harm to the elderly person\'s health or welfare.  Although the incidence and prevalence in Canada has been difficult to quantitate, in one study 4 % of surveyed seniors report that they experienced abuse.  There are three categories of abuse: domestic, institutional, and self-neglect.',NULL,516,0,3,0,1,1271174178,1),(520,'115-1-E','Acute Visual Disturbance/loss','Loss of vision is a frightening symptom that demands prompt attention; most patients require an urgent ophthalmologic opinion.',NULL,309,0,159,0,1,1271174178,1),(521,'115-2-E','Chronic Visual Disturbance/loss ','Loss of vision is a frightening symptom that demands prompt attention on the part of the physician.',NULL,309,0,160,0,1,1271174178,1),(522,'116-E','Vomiting/nausea ','Nausea may occur alone or along with vomiting (powerful ejection of gastric contents), dyspepsia, and other GI complaints.  As a cause of absenteeism from school or workplace, it is second only to the common cold.  When prolonged or severe, vomiting may be associated with disturbances of volume, water and electrolyte metabolism that may require correction prior to other specific treatment.',NULL,309,0,161,0,1,1271174178,1),(523,'117-E','Weakness/paralysis/paresis/loss Of Motion','Many patients who complain of weakness are not objectively weak when muscle strength is formally tested.  A careful history and physical examination will permit the distinction between functional disease and true muscle weakness.',NULL,309,0,162,0,1,1271174178,1),(524,'118-3-E','Weight (low) At Birth/intrauterine Growth Restriction ','Intrauterine growth restriction (IUGR) is often a manifestation of congenital infections, poor maternal nutrition, or maternal illness.  In other instances, the infant may be large for the gestational age.  There may be long-term sequelae for both.  Low birth weight is the most important risk factor for infant mortality.  It is also a significant determinant of infant and childhood morbidity, particularly neuro-developmental problems and learning disabilities.',NULL,309,0,163,0,1,1271174178,1),(525,'118-1-E','Weight Gain/obesity ','Obesity is a chronic disease that is increasing in prevalence. The percentage of the population with a body mass index of&gt;30 kg/m2 is approximately 15%.',NULL,309,0,164,0,1,1271174178,1),(526,'118-2-E','Weight Loss/eating Disorders/anorexia ','Although voluntary weight loss may be of no concern in an obese patient, it could be a manifestation of psychiatric illness.  Involuntary clinically significant weight loss (&gt;5% baseline body weight or 5 kg) is nearly always a sign of serious medical or psychiatric illness and should be investigated.',NULL,309,0,165,0,1,1271174178,1),(527,'119-1-E','Lower Respiratory Tract Disorders ','Individuals with episodes of wheezing, breathlessness, chest tightness, and cough usually have limitation of airflow.  Frequently this limitation is reversible with treatment.  Without treatment it may be lethal.',NULL,309,0,166,0,1,1271174178,1),(528,'119-2-E','Upper Respiratory Tract Disorders ','Wheezing, a continuous musical sound&gt;1/4 seconds, is produced by vibration of the walls of airways narrowed almost to the point of closure.  It can originate from airways of any size, from large upper airways to intrathoracic small airways.  It can be either inspiratory or expiratory, unlike stridor (a noisy, crowing sound, usually inspiratory and resulting from disturbances in or adjacent to the larynx).',NULL,309,0,167,0,1,1271174178,1),(529,'120-E','White Blood Cells, Abnormalities Of','Because abnormalities of white blood cells (WBCs) occur commonly in both asymptomatic as well as acutely ill patients, every physician will need to evaluate patients for this common problem.  Physicians also need to select medications to be prescribed mindful of the morbidity and mortality associated with drug-induced neutropenia and agranulocytosis.',NULL,309,0,168,0,1,1271174178,1),(2328,'','AAMC Physician Competencies Reference Set','July 2013 *Source: Englander R, Cameron T, Ballard AJ, Dodge J, Bull J, and Aschenbrener CA. Toward a common taxonomy of competency domains for the health professions and competencies for physicians. Acad Med. 2013;88:1088-1094.',NULL,0,0,0,0,1,1391798786,1),(2329,'aamc-pcrs-comp-c0100','1 Patient Care','Provide patient-centered care that is compassionate, appropriate, and effective for the treatment of health problems and the',NULL,2328,0,0,0,1,1391798786,1),(2330,'aamc-pcrs-comp-c0200','2 Knowledge for Practice','Demonstrate knowledge of established and evolving biomedical, clinical, epidemiological and social-behavioral sciences, as well as the application of this knowledge to patient care',NULL,2328,0,1,0,1,1391798786,1),(2331,'aamc-pcrs-comp-c0300','3 Practice-Based Learning and Improvement','Demonstrate the ability to investigate and evaluate oneÃƒÂ¢??s care of patients, to appraise and assimilate scientific evidence, and to continuously improve patient care based on constant self-evaluation and life-long learning',NULL,2328,0,2,0,1,1391798786,1),(2332,'aamc-pcrs-comp-c0400','4 Interpersonal and Communication Skills','Demonstrate interpersonal and communication skills that result in the effective exchange of information and collaboration with patients, their families, and health professionals',NULL,2328,0,3,0,1,1391798786,1),(2333,'aamc-pcrs-comp-c0500','5 Professionalism','Demonstrate a commitment to carrying out professional responsibilities and an adherence to ethical principles',NULL,2328,0,4,0,1,1391798786,1),(2334,'aamc-pcrs-comp-c0600','6 Systems-Based Practice','Demonstrate an awareness of and responsiveness to the larger context and system of health care, as well as the ability to call effectively on other resources in the system to provide optimal health care',NULL,2328,0,5,0,1,1391798786,1),(2335,'aamc-pcrs-comp-c0700','7 Interprofessional Collaboration','Demonstrate the ability to engage in an interprofessional team in a manner that optimizes safe, effective patient- and population-centered care',NULL,2328,0,6,0,1,1391798786,1),(2336,'aamc-pcrs-comp-c0800','8 Personal and Professional Development','Demonstrate the qualities required to sustain lifelong personal and professional growth',NULL,2328,0,7,0,1,1391798786,1),(2337,'aamc-pcrs-comp-c0101','1.1','Perform all medical, diagnostic, and surgical procedures considered',NULL,2329,0,0,0,1,1391798786,1),(2338,'aamc-pcrs-comp-c0102','1.2','Gather essential and accurate information about patients and their conditions through history-taking, physical examination, and the use of laboratory data, imaging, and other tests',NULL,2329,0,1,0,1,1391798786,1),(2339,'aamc-pcrs-comp-c0103','1.3','Organize and prioritize responsibilities to provide care that is safe, effective, and efficient',NULL,2329,0,2,0,1,1391798786,1),(2340,'aamc-pcrs-comp-c0104','1.4','Interpret laboratory data, imaging studies, and other tests required for the area of practice',NULL,2329,0,3,0,1,1391798786,1),(2341,'aamc-pcrs-comp-c0105','1.5','Make informed decisions about diagnostic and therapeutic interventions based on patient information and preferences, up-to-date scientific evidence, and clinical judgment',NULL,2329,0,4,0,1,1391798786,1),(2342,'aamc-pcrs-comp-c0106','1.6','Develop and carry out patient management plans',NULL,2329,0,5,0,1,1391798786,1),(2343,'aamc-pcrs-comp-c0107','1.7','Counsel and educate patients and their families to empower them to participate in their care and enable shared decision making',NULL,2329,0,6,0,1,1391798786,1),(2344,'aamc-pcrs-comp-c0108','1.8','Provide appropriate referral of patients including ensuring continuity of care throughout transitions between providers or settings, and following up on patient progress and outcomes',NULL,2329,0,7,0,1,1391798786,1),(2345,'aamc-pcrs-comp-c0109','1.9','Provide health care services to patients, families, and communities aimed at preventing health problems or maintaining health',NULL,2329,0,8,0,1,1391798786,1),(2346,'aamc-pcrs-comp-c0110','1.10','Provide appropriate role modeling',NULL,2329,0,9,0,1,1391798786,1),(2347,'aamc-pcrs-comp-c0111','1.11','Perform supervisory responsibilities commensurate with one\'s roles, abilities, and qualifications',NULL,2329,0,10,0,1,1391798786,1),(2348,'aamc-pcrs-comp-c0199','1.99','Other patient care',NULL,2329,0,11,0,1,1391798786,1),(2349,'aamc-pcrs-comp-c0201','2.1','Demonstrate an investigatory and analytic approach to clinical situations',NULL,2330,0,0,0,1,1391798786,1),(2350,'aamc-pcrs-comp-c0202','2.2','Apply established and emerging bio-physical scientific principles fundamental to health care for patients and populations',NULL,2330,0,1,0,1,1391798786,1),(2351,'aamc-pcrs-comp-c0203','2.3','Apply established and emerging principles of clinical sciences to diagnostic and therapeutic decision-making, clinical problem-solving, and other aspects of evidence-based health care',NULL,2330,0,2,0,1,1391798786,1),(2352,'aamc-pcrs-comp-c0204','2.4','Apply principles of epidemiological sciences to the identification of health problems, risk factors, treatment strategies, resources, and disease prevention/health promotion efforts for patients and populations',NULL,2330,0,3,0,1,1391798786,1),(2353,'aamc-pcrs-comp-c0205','2.5','Apply principles of social-behavioral sciences to provision of patient care, including assessment of the impact of psychosocial and cultural influences on health, disease, care-seeking, care compliance, and barriers to and attitudes toward care',NULL,2330,0,4,0,1,1391798786,1),(2354,'aamc-pcrs-comp-c0206','2.6','Contribute to the creation, dissemination, application, and translation of new health care knowledge and practices',NULL,2330,0,5,0,1,1391798786,1),(2355,'aamc-pcrs-comp-c0299','2.99','Other knowledge for practice',NULL,2330,0,6,0,1,1391798786,1),(2356,'aamc-pcrs-comp-c0301','3.1','Identify strengths, deficiencies, and limits in one\'s knowledge and expertise',NULL,2331,0,0,0,1,1391798786,1),(2357,'aamc-pcrs-comp-c0302','3.2','Set learning and improvement goals',NULL,2331,0,1,0,1,1391798786,1),(2358,'aamc-pcrs-comp-c0303','3.3','Identify and perform learning activities that address one\'s gaps in knowledge, skills, and/or attitudes',NULL,2331,0,2,0,1,1391798786,1),(2359,'aamc-pcrs-comp-c0304','3.4','Systematically analyze practice using quality improvement methods, and implement changes with the goal of practice improvement',NULL,2331,0,3,0,1,1391798786,1),(2360,'aamc-pcrs-comp-c0305','3.5','Incorporate feedback into daily practice',NULL,2331,0,4,0,1,1391798786,1),(2361,'aamc-pcrs-comp-c0306','3.6','Locate, appraise, and assimilate evidence from scientific studies related to',NULL,2331,0,5,0,1,1391798786,1),(2362,'aamc-pcrs-comp-c0307','3.7','Use information technology to optimize learning',NULL,2331,0,6,0,1,1391798786,1),(2363,'aamc-pcrs-comp-c0308','3.8','Participate in the education of patients, families, students, trainees, peers and other health professionals',NULL,2331,0,7,0,1,1391798786,1),(2364,'aamc-pcrs-comp-c0309','3.9','Obtain and utilize information about individual patients, populations of patients, or communities from which patients are drawn to improve care',NULL,2331,0,8,0,1,1391798786,1),(2365,'aamc-pcrs-comp-c0310','3.10','Continually identify, analyze, and implement new knowledge, guidelines, standards, technologies, products, or services that have been demonstrated to improve outcomes',NULL,2331,0,9,0,1,1391798786,1),(2366,'aamc-pcrs-comp-c0399','3.99','Other practice-based learning and improvement',NULL,2331,0,10,0,1,1391798786,1),(2367,'aamc-pcrs-comp-c0401','4.1','Communicate effectively with patients, families, and the public, as appropriate, across a broad range of socioeconomic and cultural backgrounds',NULL,2332,0,0,0,1,1391798786,1),(2368,'aamc-pcrs-comp-c0402','4.2','Communicate effectively with colleagues within one\'s profession or specialty, other health professionals, and health related agencies (see also 7.3)',NULL,2332,0,1,0,1,1391798786,1),(2369,'aamc-pcrs-comp-c0403','4.3','Work effectively with others as a member or leader of a health care team or other professional group (see also 7.4)',NULL,2332,0,2,0,1,1391798786,1),(2370,'aamc-pcrs-comp-c0404','4.4','Act in a consultative role to other health professionals',NULL,2332,0,3,0,1,1391798786,1),(2371,'aamc-pcrs-comp-c0405','4.5','Maintain comprehensive, timely, and legible medical records',NULL,2332,0,4,0,1,1391798786,1),(2372,'aamc-pcrs-comp-c0406','4.6','Demonstrate sensitivity, honesty, and compassion in difficult conversations, including those about death, end of life, adverse events, bad news, disclosure of errors, and other sensitive topics',NULL,2332,0,5,0,1,1391798786,1),(2373,'aamc-pcrs-comp-c0407','4.7','Demonstrate insight and understanding about emotions and human responses to emotions that allow one to develop and manage interpersonal',NULL,2332,0,6,0,1,1391798786,1),(2374,'aamc-pcrs-comp-c0499','4.99','Other interpersonal and communication skills',NULL,2332,0,7,0,1,1391798786,1),(2375,'aamc-pcrs-comp-c0501','5.1','Demonstrate compassion, integrity, and respect for others',NULL,2333,0,0,0,1,1391798786,1),(2376,'aamc-pcrs-comp-c0502','5.2','Demonstrate responsiveness to patient needs that supersedes self-interest',NULL,2333,0,1,0,1,1391798786,1),(2377,'aamc-pcrs-comp-c0503','5.3','Demonstrate respect for patient privacy and autonomy',NULL,2333,0,2,0,1,1391798786,1),(2378,'aamc-pcrs-comp-c0504','5.4','Demonstrate accountability to patients, society, and the profession',NULL,2333,0,3,0,1,1391798786,1),(2379,'aamc-pcrs-comp-c0505','5.5','Demonstrate sensitivity and responsiveness to a diverse patient population, including but not limited to diversity in gender, age, culture, race, religion, disabilities, and sexual orientation',NULL,2333,0,4,0,1,1391798786,1),(2380,'aamc-pcrs-comp-c0506','5.6','Demonstrate a commitment to ethical principles pertaining to provision or withholding of care, confidentiality, informed consent, and business practices, including compliance with relevant laws, policies, and regulations',NULL,2333,0,5,0,1,1391798786,1),(2381,'aamc-pcrs-comp-c0599','5.99','Other professionalism',NULL,2333,0,6,0,1,1391798786,1),(2382,'aamc-pcrs-comp-c0601','6.1','Work effectively in various health care delivery settings and systems relevant to one\'s clinical specialty',NULL,2334,0,0,0,1,1391798786,1),(2383,'aamc-pcrs-comp-c0602','6.2','Coordinate patient care within the health care system relevant to one\'s clinical specialty',NULL,2334,0,1,0,1,1391798786,1),(2384,'aamc-pcrs-comp-c0603','6.3','Incorporate considerations of cost awareness and risk-benefit analysis in patient and/or population-based care',NULL,2334,0,2,0,1,1391798786,1),(2385,'aamc-pcrs-comp-c0604','6.4','Advocate for quality patient care and optimal patient care systems',NULL,2334,0,3,0,1,1391798786,1),(2386,'aamc-pcrs-comp-c0605','6.5','Participate in identifying system errors and implementing potential systems solutions',NULL,2334,0,4,0,1,1391798786,1),(2387,'aamc-pcrs-comp-c0606','6.6','Perform administrative and practice management responsibilities commensurate with oneÃƒÂ¢??s role, abilities, and qualifications',NULL,2334,0,5,0,1,1391798786,1),(2388,'aamc-pcrs-comp-c0699','6.99','Other systems-based practice',NULL,2334,0,6,0,1,1391798786,1),(2389,'aamc-pcrs-comp-c0701','7.1','Work with other health professionals to establish and maintain a climate of mutual respect, dignity, diversity, ethical integrity, and trust',NULL,2335,0,0,0,1,1391798786,1),(2390,'aamc-pcrs-comp-c0702','7.2','Use the knowledge of oneÃƒÂ¢??s own role and the roles of other health professionals to appropriately assess and address the health care needs of the patients and populations served',NULL,2335,0,1,0,1,1391798786,1),(2391,'aamc-pcrs-comp-c0703','7.3','Communicate with other health professionals in a responsive and responsible manner that supports the maintenance of health and the',NULL,2335,0,2,0,1,1391798786,1),(2392,'aamc-pcrs-comp-c0704','7.4','Participate in different team roles to establish, develop, and continuously enhance interprofessional teams to provide patient- and population-centered care that is safe, timely, efficient, effective, and equitable',NULL,2335,0,3,0,1,1391798786,1),(2393,'aamc-pcrs-comp-c0799','7.99','Other interprofessional collaboration',NULL,2335,0,4,0,1,1391798786,1),(2394,'aamc-pcrs-comp-c0801','8.1','Develop the ability to use self-awareness of knowledge, skills, and emotional limitations to engage in appropriate help-seeking behaviors',NULL,2336,0,0,0,1,1391798786,1),(2395,'aamc-pcrs-comp-c0802','8.2','Demonstrate healthy coping mechanisms to respond to stress',NULL,2336,0,1,0,1,1391798786,1),(2396,'aamc-pcrs-comp-c0803','8.3','Manage conflict between personal and professional responsibilities',NULL,2336,0,2,0,1,1391798786,1),(2397,'aamc-pcrs-comp-c0804','8.4','Practice flexibility and maturity in adjusting to change with the capacity to alter one\'s behavior',NULL,2336,0,3,0,1,1391798786,1),(2398,'aamc-pcrs-comp-c0805','8.5','Demonstrate trustworthiness that makes colleagues feel secure when one is responsible for the care of patients',NULL,2336,0,4,0,1,1391798786,1),(2399,'aamc-pcrs-comp-c0806','8.6','Provide leadership skills that enhance team functioning, the learning environment, and/or the health care delivery system',NULL,2336,0,5,0,1,1391798786,1),(2400,'aamc-pcrs-comp-c0807','8.7','Demonstrate self-confidence that puts patients, families, and members of the health care team at ease',NULL,2336,0,6,0,1,1391798786,1),(2401,'aamc-pcrs-comp-c0808','8.8','Recognize that ambiguity is part of clinical health care and respond by utilizing appropriate resources in dealing with uncertainty',NULL,2336,0,7,0,1,1391798786,1),(2402,'aamc-pcrs-comp-c0899','8.99','Other personal and professional development',NULL,2336,0,8,0,1,1391798786,1);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `global_lu_provinces` (
  `province_id` int(11) NOT NULL AUTO_INCREMENT,
  `country_id` int(11) NOT NULL,
  `province` varchar(200) NOT NULL,
  `abbreviation` varchar(200) NOT NULL,
  PRIMARY KEY (`province_id`)
) ENGINE=MyISAM AUTO_INCREMENT=64 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `global_lu_provinces` VALUES (1,39,'Alberta','AB'),(2,39,'British Columbia','BC'),(3,39,'Manitoba','MB'),(4,39,'New Brunswick','NB'),(5,39,'Newfoundland and Labrador','NL'),(6,39,'Northwest Territories','NT'),(7,39,'Nova Scotia','NS'),(8,39,'Nunavut','NU'),(9,39,'Ontario','ON'),(10,39,'Prince Edward Island','PE'),(11,39,'Quebec','QC'),(12,39,'Saskatchewan','SK'),(13,39,'Yukon Territory','YT'),(14,227,'Alabama','AL'),(15,227,'Alaska','AK'),(16,227,'Arizona','AZ'),(17,227,'Arkansas','AR'),(18,227,'California','CA'),(19,227,'Colorado','CO'),(20,227,'Connecticut','CT'),(21,227,'Delaware','DE'),(22,227,'Florida','FL'),(23,227,'Georgia','GA'),(24,227,'Hawaii','HI'),(25,227,'Idaho','ID'),(26,227,'Illinois','IL'),(27,227,'Indiana','IN'),(28,227,'Iowa','IA'),(29,227,'Kansas','KS'),(30,227,'Kentucky','KY'),(31,227,'Louisiana','LA'),(32,227,'Maine','ME'),(33,227,'Maryland','MD'),(34,227,'Massachusetts','MA'),(35,227,'Michigan','MI'),(36,227,'Minnesota','MN'),(37,227,'Mississippi','MS'),(38,227,'Missouri','MO'),(39,227,'Montana','MT'),(40,227,'Nebraska','NE'),(41,227,'Nevada','NV'),(42,227,'New Hampshire','NH'),(43,227,'New Jersey','NJ'),(44,227,'New Mexico','NM'),(45,227,'New York','NY'),(46,227,'North Carolina','NC'),(47,227,'North Dakota','ND'),(48,227,'Ohio','OH'),(49,227,'Oklahoma','OK'),(50,227,'Oregon','OR'),(51,227,'Pennsylvania','PA'),(52,227,'Rhode Island','RI'),(53,227,'South Carolina','SC'),(54,227,'South Dakota','SD'),(55,227,'Tennessee','TN'),(56,227,'Texas','TX'),(57,227,'Utah','UT'),(58,227,'Vermont','VT'),(59,227,'Virginia','VA'),(60,227,'Washington','WA'),(61,227,'West Virginia','WV'),(62,227,'Wisconsin','WI'),(63,227,'Wyoming','WY');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `global_lu_publication_type` (
  `type_id` int(11) NOT NULL DEFAULT '0',
  `type_description` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`type_id`),
  KEY `type_description` (`type_description`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `global_lu_publication_type` VALUES (1,'Peer-Reviewed Article'),(2,'Non-Peer-Reviewed Article'),(3,'Chapter'),(4,'Peer-Reviewed Abstract'),(5,'Non-Peer-Reviewed Abstract'),(6,'Complete Book'),(7,'Monograph'),(8,'Editorial'),(9,'Published Conference Proceeding'),(10,'Poster Presentations'),(11,'Technical Report');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `global_lu_roles` (
  `role_id` int(11) NOT NULL DEFAULT '0',
  `role_description` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`role_id`),
  KEY `role_description` (`role_description`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `global_lu_roles` VALUES (1,'Lead Author'),(2,'Contributing Author'),(3,'Editor'),(4,'Co-Editor'),(5,'Senior Author'),(6,'Co-Lead');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `global_lu_rooms` (
  `room_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `building_id` int(11) unsigned NOT NULL,
  `room_number` varchar(20) NOT NULL DEFAULT '',
  `room_name` varchar(100) DEFAULT NULL,
  `room_description` varchar(255) DEFAULT NULL,
  `room_max_occupancy` int(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `global_lu_schools` (
  `schools_id` int(11) NOT NULL AUTO_INCREMENT,
  `school_title` varchar(250) NOT NULL,
  PRIMARY KEY (`schools_id`)
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `global_lu_schools` VALUES (1,'University of Alberta'),(2,'University of British Columbia'),(3,'University of Calgary'),(4,'Dalhousie University'),(5,'Laval University'),(6,'University of Manitoba'),(7,'McGill University'),(8,'McMaster University'),(9,'Memorial University of Newfoundland'),(10,'Universite de Montreal'),(11,'Northern Ontario School of Medicine'),(12,'University of Ottawa'),(13,'Queen\'s University'),(14,'University of Saskatchewan'),(15,'Universite de Sherbrooke'),(16,'University of Toronto'),(17,'University of Western Ontario');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gradebook_assessment_form_elements` (
  `gafelement_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `assessment_id` int(10) unsigned DEFAULT NULL,
  `afelement_id` int(11) unsigned DEFAULT NULL,
  `weight` float DEFAULT NULL,
  PRIMARY KEY (`gafelement_id`),
  KEY `assessment_id` (`assessment_id`),
  KEY `afelement_id` (`afelement_id`),
  CONSTRAINT `gradebook_assessment_form_elements_ibfk_1` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`assessment_id`),
  CONSTRAINT `gradebook_assessment_form_elements_ibfk_2` FOREIGN KEY (`afelement_id`) REFERENCES `cbl_assessment_form_elements` (`afelement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gradebook_assessment_item_responses` (
  `gairesponse_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `assessment_id` int(11) unsigned DEFAULT NULL,
  `iresponse_id` int(11) unsigned DEFAULT NULL,
  `score` float DEFAULT NULL,
  PRIMARY KEY (`gairesponse_id`),
  KEY `assessment_id` (`assessment_id`),
  KEY `iresponse_id` (`iresponse_id`),
  CONSTRAINT `gradebook_assessment_item_responses_ibfk_1` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`assessment_id`),
  CONSTRAINT `gradebook_assessment_item_responses_ibfk_2` FOREIGN KEY (`iresponse_id`) REFERENCES `cbl_assessments_lu_item_responses` (`iresponse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_members` (
  `gmember_id` int(12) NOT NULL AUTO_INCREMENT,
  `group_id` int(12) NOT NULL DEFAULT '0',
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `start_date` bigint(64) NOT NULL DEFAULT '0',
  `finish_date` bigint(64) NOT NULL DEFAULT '0',
  `member_active` int(1) NOT NULL DEFAULT '1',
  `entrada_only` int(1) DEFAULT '0',
  `created_date` bigint(64) NOT NULL,
  `created_by` int(11) NOT NULL,
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`gmember_id`),
  KEY `group_id` (`group_id`,`proxy_id`,`updated_date`,`updated_by`),
  KEY `member_active` (`member_active`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_organisations` (
  `gorganisation_id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL,
  `organisation_id` int(11) NOT NULL,
  `created_date` bigint(64) NOT NULL,
  `created_by` int(11) NOT NULL,
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(11) NOT NULL,
  PRIMARY KEY (`gorganisation_id`),
  KEY `group_id` (`group_id`,`organisation_id`,`updated_date`,`updated_by`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `group_organisations` VALUES (1,1,1,0,0,1449685604,1),(2,2,1,0,0,1449685604,1),(3,3,1,0,0,1449685604,1),(4,4,1,0,0,1449685604,1),(5,5,1,0,0,1449685604,1);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups` (
  `group_id` int(12) NOT NULL AUTO_INCREMENT,
  `group_name` varchar(255) NOT NULL,
  `group_type` enum('course_list','cohort') NOT NULL DEFAULT 'course_list',
  `group_value` int(12) DEFAULT NULL,
  `start_date` bigint(64) DEFAULT NULL,
  `expire_date` bigint(64) DEFAULT NULL,
  `group_active` int(1) NOT NULL DEFAULT '1',
  `created_date` bigint(64) NOT NULL,
  `created_by` int(11) NOT NULL,
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(12) NOT NULL,
  PRIMARY KEY (`group_id`),
  FULLTEXT KEY `group_title` (`group_name`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `groups` VALUES (1,'Class of 2015','cohort',NULL,NULL,NULL,1,0,0,1449685604,1),(2,'Class of 2016','cohort',NULL,NULL,NULL,1,0,0,1449685604,1),(3,'Class of 2017','cohort',NULL,NULL,NULL,1,0,0,1449685604,1),(4,'Class of 2018','cohort',NULL,NULL,NULL,1,0,0,1449685604,1),(5,'Class of 2019','cohort',NULL,NULL,NULL,1,0,0,1449685604,1);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `learning_object_file_permissions` (
  `lo_file_permission_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `lo_file_id` int(11) NOT NULL,
  `proxy_id` int(11) DEFAULT NULL,
  `permission` enum('read','write','delete') NOT NULL DEFAULT 'read',
  `updated_date` int(11) NOT NULL,
  `updated_by` int(11) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`lo_file_permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `learning_object_file_tags` (
  `lo_file_tag_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `lo_file_id` int(11) NOT NULL,
  `tag` varchar(255) NOT NULL DEFAULT '',
  `updated_date` int(11) NOT NULL,
  `updated_by` int(11) NOT NULL,
  `active` tinyint(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`lo_file_tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `learning_object_files` (
  `lo_file_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL DEFAULT '',
  `filesize` int(11) NOT NULL,
  `mime_type` varchar(32) NOT NULL,
  `description` varchar(255) DEFAULT '',
  `proxy_id` int(11) NOT NULL,
  `public` tinyint(1) NOT NULL DEFAULT '0',
  `updated_date` int(11) NOT NULL,
  `updated_by` int(11) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`lo_file_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `linked_objectives` (
  `linked_objective_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `objective_id` int(12) NOT NULL,
  `target_objective_id` int(12) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`linked_objective_id`)
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
  `course_id` int(12) unsigned NOT NULL DEFAULT '0',
  `llocation_id` int(12) unsigned NOT NULL DEFAULT '0',
  `lsite_id` int(11) NOT NULL,
  `comments` text,
  `reflection` text NOT NULL,
  `entry_active` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`lentry_id`),
  KEY `proxy_id` (`proxy_id`,`entry_active`),
  KEY `proxy_id_2` (`proxy_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logbook_entry_objectives` (
  `leobjective_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `lentry_id` int(12) unsigned NOT NULL DEFAULT '0',
  `objective_id` int(12) unsigned NOT NULL DEFAULT '0',
  `participation_level` int(12) NOT NULL DEFAULT '3',
  `updated_by` int(11) NOT NULL,
  `updated_date` int(11) DEFAULT NULL,
  `objective_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`leobjective_id`),
  KEY `lentry_id` (`lentry_id`,`objective_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logbook_lu_ageranges` (
  `agerange_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `agerange` varchar(8) DEFAULT NULL,
  `agerange_active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`agerange_id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `logbook_lu_ageranges` VALUES (1,'< 1',1),(2,'1 - 4',1),(3,'5 - 12',1),(4,'13 - 19',1),(5,'20 - 64',1),(6,'65 - 74',1),(7,'75+',1);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logbook_lu_locations` (
  `llocation_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `location` varchar(64) DEFAULT NULL,
  `location_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`llocation_id`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `logbook_lu_locations` VALUES (1,'Clinic',1),(2,'Ward',1),(3,'Emergency',1),(4,'ICU',1),(5,'Private Office',1),(6,'OR',1),(7,'NICU',1),(8,'Nursing Home',1),(9,'Community Site',1),(10,'Computer Interactive Case',1),(11,'Other (provide details in additional comments field)',1);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `logbook_lu_sites` (
  `lsite_id` int(11) NOT NULL AUTO_INCREMENT,
  `site_name` varchar(64) NOT NULL,
  `site_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`lsite_id`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `logbook_lu_sites` VALUES (1,'Brockville General Hospital',1),(2,'Brockville Pyschiatric Hospital',1),(3,'Hotel Dieu Hospital (Kingston)',1),(4,'Kingston General Hospital',1),(5,'Lakeridge Health',1),(6,'Markam Stouffville Hospital',1),(7,'Perth Family Health Team',1),(8,'Perth/Smiths Falls District Hospital',1),(9,'Peterborough Regional Health Centre',1),(10,'Providence Care Centre',1),(11,'Quinte Health Care',1),(12,'Weenebayko General Hospital',1),(13,'Other (provide details in additional comments field)',1);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lrs_history` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(43) NOT NULL DEFAULT '',
  `run_last` bigint(64) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `map_assessments_meta` (
  `map_assessments_meta_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `fk_assessment_method_id` int(11) NOT NULL,
  `fk_assessments_meta_id` int(11) NOT NULL,
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(11) NOT NULL,
  PRIMARY KEY (`map_assessments_meta_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `map_event_resources` (
  `map_event_resources_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `fk_medbiq_resource_id` int(11) DEFAULT NULL,
  `fk_resource_id` int(11) NOT NULL,
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(11) NOT NULL,
  PRIMARY KEY (`map_event_resources_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `map_events_eventtypes` (
  `map_events_eventtypes_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `fk_instructional_method_id` int(11) NOT NULL,
  `fk_eventtype_id` int(11) NOT NULL,
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(11) NOT NULL,
  PRIMARY KEY (`map_events_eventtypes_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `medbiq_assessment_methods` (
  `assessment_method_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(10) DEFAULT NULL,
  `assessment_method` varchar(250) NOT NULL DEFAULT '',
  `assessment_method_description` text,
  `active` int(1) NOT NULL DEFAULT '1',
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(11) NOT NULL,
  PRIMARY KEY (`assessment_method_id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `medbiq_assessment_methods` VALUES (1,'AM001','Clinical Documentation Review','The review and assessment of clinical notes and logs kept by learners as part of practical training in the clinical setting (Bowen & Smith, 2010; Irby, 1995)',1,0,0),(2,'AM002','Clinical Performance Rating/Checklist','A non-narrative assessment tool (checklist, Likert-type scale, other instrument) used to note completion or\rachievement of learning tasks (MacRae, Vu, Graham, Word-Sims, Colliver, & Robbs, 1995; Turnbull, Gray, & MacFadyen, 1998) also see ?Direct Observations or Performance Audits,? Institute for International Medical Education, 2002)',1,0,0),(3,'AM003','Exam - Institutionally Developed, Clinical Performance','Practical performance-based examination developed internally to assess problem solving, clinical reasoning, decision making, and[/or] communication skills (LCME, 2011) (Includes observation of learner or small group by instructor)',1,0,0),(4,'AM004','Exam - Institutionally Developed, Written/Computer-based','Examination utilizing various written question-and-answer formats (multiple-choice, short answer, essay, etc.) which may assess learners\' factual knowledge retention; application of knowledge, concepts, and principles; problem-solving acumen; and clinical reasoning (Cooke, Irby, & O?Brien, 2010b; LCME, 2011)',1,0,0),(5,'AM005','Exam - Institutionally Developed, Oral','Verbal examination developed internally to assess problem solving, clinical reasoning, decision making, and[/or] communication skills (LCME, 2011)',1,0,0),(6,'AM006','Exam - Licensure, Clinical Performance','Practical, performance-based examination developed by a professional licensing body to assess clinical skills such as problem solving, clinical reasoning, decision making, and communication, for licensure to practice in a given jurisdiction (e.g., USMLE for the United States); typically paired with a written/computer-based component (MCC, 2011a & 2011c; NBOME, 2010b; USMLE, n.d.); may also be used by schools to assess learners? achievement of certain curricular objectives',1,0,0),(7,'AM007','Exam - Licensure, Written/Computer-based','Standardized written examination administered to assess learners\' factual knowledge retention; application of knowledge, concepts, and principles; problem-solving acumen; and clinical reasoning, for licensure to practice in a given jurisdiction (e.g., USMLE for the United States); typically paired with a clinical performance component (MCC, 2011a & 2011b; NBOME, 2010b; USMLE, n.d.); may also be used by schools or learners themselves to assess achievement of certain curricular objectives',1,0,0),(8,'AM008','Exam - Nationally Normed/Standardized, Subject','Standardized written examination administered to assess learners? achievement of nationally established educational expectations for various levels of training and/or specialized subject area(s) (e.g., NBME Subject or ?Shelf? Exam) (NBME, 2011; NBOME, 2010a)',1,0,0),(9,'AM009','Multisource Assessment','A formal assessment of performance by supervisors, peers, patients, and coworkers (Bowen & Smith, 2010; Institute for International Medical Education, 2002) (Also see Peer Assessment)',1,0,0),(10,'AM010','Narrative Assessment','An instructor\'s or observer\'s written subjective assessment of a learner\'s work or performance (Mennin, McConnell, & Anderson, 1997); May Include: Comments within larger assessment; Observation of learner or small group by instructor',1,0,0),(11,'AM011','Oral Patient Presentation','The presentation of clinical case (patient) findings, history and physical, differential diagnosis, treatment plan, etc., by a learner to an instructor or small group, and subsequent discussion with the instructor and/or small group for the purposes of learner demonstrating skills in clinical reasoning, problem-solving, etc.\r(Wiener, 1974)',1,0,0),(12,'AM012','Participation','Sharing or taking part in an activity (Education Resources Information Center, 1966b)',1,0,0),(13,'AM013','Peer Assessment','The concurrent or retrospective review by learners of the quality and efficiency of practices or services ordered or performed by fellow learners (based on MeSH Scope Note for \"Peer Review, Health Care,\" U.S. National Library of Medicine, 1992)',1,0,0),(14,'AM014','Portfolio-Based Assessment','Review of a learner\'s achievement of agreed-upon academic objectives or completion of a negotiated set of learning activities, based on a learner portfolio (Institute for International Medical Education, 2002) (\"a systematic collection of a student\'s work samples, records of observation, test results, etc., over a period of time\"? Education Resources Information Center, 1994)',1,0,0),(15,'AM015','Practical (Lab)','Learner engagement in hands-on or simulated exercises in which they collect or use data to test and/or verify hypotheses or to address questions about principles and/or phenomena (LCME, 2011)',1,0,0),(16,'AM016','Research or Project Assessment','Assessment of activities and outcomes (e.g., posters, presentations, reports, etc.) of a project in which the learner participated or conducted research (Dyrbye, Davidson, & Cook, 2008)',1,0,0),(17,'AM017','Self-Assessment','The process of evaluating one?s own deficiencies, achievements, behavior or professional performance and competencies (Institute for International Medical Education, 2002); Assessment completed by the learner to reflect and critically assess his/her own performance against a set of established criteria (Gordon, 1991) (NOTE: Does not refer to NBME Self-Assessment)',1,0,0),(18,'AM018','Stimulated Recall','The use of various stimuli (e.g., written records, audio tapes, video tapes) to re-activate the experience of a learner during a learning activity or clinical encounter in order to reflect on task performance, reasoning, decision-making, interpersonal skills, personal thoughts and feelings, etc. (Barrows, 2000)',1,0,0);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `medbiq_instructional_methods` (
  `instructional_method_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(10) DEFAULT NULL,
  `instructional_method` varchar(250) NOT NULL DEFAULT '',
  `instructional_method_description` text,
  `active` int(1) NOT NULL DEFAULT '1',
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(11) NOT NULL,
  PRIMARY KEY (`instructional_method_id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `medbiq_instructional_methods` VALUES (1,'IM001','Case-Based Instruction/Learning','The use of patient cases (actual or theoretical) to stimulate discussion, questioning, problem solving, and reasoning on issues pertaining to the basic sciences and clinical disciplines (Anderson, 2010)',1,0,0),(2,'IM002','Clinical Experience - Ambulatory','Practical experience(s) in patient care and health-related services carried out in an ambulatory/outpatient\rsetting (LCME, 2011) where actual symptoms are studied and treatment is given (Education Resources Information Center, 1968 & 1981)',1,0,0),(3,'IM003','Clinical Experience - Inpatient','Practical experience(s) in patient care and health-related services carried out in an inpatient setting (LCME, 2011) where actual symptoms are studied and treatment is given (Education Resources Information Center, 1968 & 1981)',1,0,0),(4,'IM004','Concept Mapping','Technique [that] allows learners to organize and represent knowledge in an explicit interconnected network. Linkages between concepts are explored to make apparent connections that are not usually seen. Concept mapping also encourages the asking of questions about relationships between concepts that may not have been presented in traditional courses, standard texts, and teaching materials. It shifts the focus of learning away from rote acquisition of information to visualizing the underlying concepts that provide the cognitive\rframework of what the learner already knows, to facilitate the acquisition of new knowledge (Weiss & Levinson, 2000, citing Novak & Gowin, 1984)',1,0,0),(5,'IM005','Conference',NULL,1,0,0),(6,'IM006','Demonstration','A description, performance, or explanation of a process, illustrated by examples, observable action, specimens, etc. (Dictionary.com, n.d.)',1,0,0),(7,'IM007','Discussion, Large Group (>13)','An exchange (oral or written) of opinions, observations, or ideas among a Large Group [more than 12\rparticipants], usually to analyze, clarify, or reach conclusions about issues, questions, or problems (Education Resources Information Center, 1980)',1,0,0),(8,'IM008','Discussion, Small Group (<12)','An exchange (oral or written) of opinions, observations, or ideas among a Small Group [12 or fewer participants], usually to analyze, clarify, or reach conclusions about issues, questions, or problems (Education Resources Information Center, 1980)',1,0,0),(9,'IM009','Games','Individual or group games that have cognitive, social, behavioral, and/or emotional, etc., dimensions which are related to educational objectives (Education Resources Information Center, 1966a)',1,0,0),(10,'IM010','Independent Learning','Instructor-/ or mentor-guided learning activities to be performed by the learner outside of formal educational settings (classroom, lab, clinic) (Bowen & Smith, 2010); Dedicated time on learner schedules to prepare for specific learning activities, e.g., case discussions, TBL, PBL, clinical activities, research project(s)',1,0,0),(11,'IM011','Journal Club','A forum in which participants discuss recent research papers from field literature in order to develop\rcritical reading skills (comprehension, analysis, and critique) (Cooke, Irby, & O\'Brien, 2010a; Mann & O\'Neill, 2010; Woods & Winkel, 1982)',1,0,0),(12,'IM012','Laboratory','Hands-on or simulated exercises in which learners collect or use data to test and/or verify hypotheses or to address questions about principles and/or phenomena (LCME, 2011)',1,0,0),(13,'IM013','Lecture','An instruction or verbal discourse by a speaker before a large group of learners (Institute for International Medical Education, 2002)',1,0,0),(14,'IM014','Mentorship','The provision of guidance, direction and support by senior professionals to learners or more junior professionals (U.S. National Library of Medicine, 1987)',1,0,0),(15,'IM015','Patient Presentation - Faculty','A presentation by faculty of patient findings, history and physical, differential diagnosis, treatment plan,\retc. (Wiener, 1974)',1,0,0),(16,'IM016','Patient Presentation - Learner','A presentation by a learner or learners to faculty, resident(s), and/or other learners of patient findings, history and physical, differential diagnosis, treatment plan, etc. (Wiener, 1974)',1,0,0),(17,'IM017','Peer Teaching','Learner-to-learner instruction for the mutual learning experience of both \"teacher\" and \"learner\"; may be \"peer-to-peer\" (same training level) or \"near-peer\" (higher-level learner teaching lower-level learner)\r(Soriano et al., 2010)',1,0,0),(18,'IM018','Preceptorship','Practical experience in medical and health-related services wherein the professionally-trained learner works\runder the supervision of an established professional in the particular field (U. S. National Library of Medicine, 1974)',1,0,0),(19,'IM019','Problem-Based Learning (PBL)','The use of carefully selected and designed patient cases that demand from the learner acquisition of critical\rknowledge, problem solving proficiency, self-directed learning strategies, and team participation skills as those needed in professional practice (Eshach & Bitterman, 2003; see also Major & Palmer, 2001; Cooke, Irby, & O\'Brien, 2010b;\rBarrows & Tamblyn, 1980)',1,0,0),(20,'IM020','Reflection','Examination by the learner of his/her personal experiences of a learning event, including the cognitive, emotional, and affective aspects; the use of these past experiences in combination with objective information\rto inform present clinical decision-making and problem-solving (Mann, Gordon, & MacLeod, 2009; Mann & O\'Neill, 2010)',1,0,0),(21,'IM021','Research','Short-term or sustained participation in research',1,0,0),(22,'IM022','Role Play/Dramatization','The adopting or performing the role or activities of another individual',1,0,0),(23,'IM023','Self-Directed Learning','Learners taking the initiative for their own learning: diagnosing needs, formulating goals, identifying resources, implementing appropriate activities, and evaluating outcomes (Garrison, 1997; Spencer & Jordan, 1999)',1,0,0),(24,'IM024','Service Learning Activity','A structured learning experience that combines community service with preparation and reflection (LCME, 2011)',1,0,0),(25,'IM025','Simulation','A method used to replace or amplify real patient encounters with scenarios designed to replicate real health care situations, using lifelike mannequins, physical models, standardized patients, or computers (Passiment,\rSacks, & Huang, 2011)',1,0,0),(26,'IM026','Team-Based Learning (TBL)','A form of collaborative learning that follows a specific sequence of individual work, group work and immediate feedback; engages learners in learning activities within a small group that works independently in classes with high learner-faculty ratios (Anderson, 2010; Team-Based Learning Collaborative, n.d.; Thompson, Schneider, Haidet, Perkowski, & Richards, 2007)',1,0,0),(27,'IM027','Team-Building','Workshops, sessions, and/or activities contributing to the development of teamwork skills, often as a foundation for group work in learning (PBL, TBL, etc.) and practice (interprofessional/-disciplinary, etc.)\r(Morrison, Goldfarb, & Lanken, 2010)',1,0,0),(28,'IM028','Tutorial','Instruction provided to a learner or small group of learners by direct interaction with an instructor (Education\rResources Information Center, 1966c)',1,0,0),(29,'IM029','Ward Rounds','An instructional session conducted in an actual clinical setting, using real patients or patient cases to demonstrate procedures or clinical skills, illustrate clinical reasoning and problem-solving, or stimulate discussion and analytical thinking among a group of learners (Bowen & Smith, 2010; Wiener, 1974)',1,0,0),(30,'IM030','Workshop','A brief intensive educational program for a relatively small group of people that focuses especially on techniques and skills related to a specific topic (U. S. National Library of Medicine, 2011)',1,0,0);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `medbiq_resources` (
  `resource_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `resource` varchar(250) NOT NULL DEFAULT '',
  `resource_description` text,
  `active` int(1) NOT NULL DEFAULT '1',
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(11) NOT NULL,
  PRIMARY KEY (`resource_id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `medbiq_resources` VALUES (1,'Audience Response System','An electronic communication system that allows groups of people to vote on a topic or answer a question. Each person has a remote control (\"clicker\") with which selections can be made; Typically, the results are\rinstantly made available to the participants via a graph displayed on the projector. (Group on Information Resources, 2011; Stoddard & Piquette, 2010)',1,0,0),(2,'Audio','Devices or applications used to acquire or transfer knowledge, attitudes, or skills through study, instruction, or experience using auditory delivery (see \"Electronic Learning,\" Education Resources Information Center, 2008b)',1,0,0),(3,'Cadaver','A human body preserved post-mortem and \"used...to study anatomy, identify disease sites, determine causes of death, and provide tissue to repair a defect in a living human being\" (MedicineNet.com, 2004)',1,0,0),(4,'Clinical Correlation','The application and elaboration of concepts introduced in lecture, reading assignments, independent study, and other learning activities to real patient or case scenarios in order to promote knowledge retrieval in similar clinical situations at a later time (Euliano, 2001)',1,0,0),(5,'Distance Learning - Asynchronous','Education facilitated through communications media (often electronic), with little or no classroom or other face-to-face contact between learners and teachers, and which \"does not occur in real time or involve simultaneous interaction on the part of participants. It is intermittent and generally characterized by a significant time delay or interval between sending and receiving or responding to messages\" (Education Resources Information Center, 1983; 2008a)',1,0,0),(6,'Distance Learning - Synchronous','Education facilitated through communications media (often electronic), with little or no classroom or other face-to-face contact between learners and teachers, \"in real time, characterized by concurrent exchanges between participants. Interaction is simultaneous without a meaningful time delay between sending a message and receiving or responding to it. Occurs in electronic (e.g., interactive videoconferencing) and non-electronic environments (e.g., telephone conversations)\" (Education Resources Information Center, 1983; 2008c)',1,0,0),(7,'Educational Technology','Mobile or desktop technology (hardware or software) used for instruction/learning through audiovisual (A/V), multimedia, web-based, or online modalities (Group on Information Resources, 2011); Sometimes includes dedicated space (see Virtual/Computerized Lab)',1,0,0),(8,'Electronic Health/Medical Record (EHR/EMR)','An individual patient\'s medical record in digital format...usually accessed on a computer, often over a network...[M]ay be made up of electronic medical records (EMRs) from many locations and/or sources. An Electronic Medical Record (EMR) may be an inpatient or outpatient medical record in digital format that may or may not be linked to or part of a larger EHR (Group on Information Resources, 2011)',1,0,0),(9,'Film/Video','Devices or applications used to acquire or transfer knowledge, attitudes, or skills through study, instruction, or experience using visual recordings (see \"Electronic Learning,\" Education Resources Information Center, 2008b)',1,0,0),(10,'Key Feature','An element specific to a clinical case or problem that demands the use of particular clinical skills in order to achieve the problem\'s successful resolution; Typically presented as written exam questions, as in the Canadian Qualifying Examination in Medicine (Page & Bordage, 1995; Page, Bordage, & Allen, 1995)',1,0,0),(11,'Mannequin','A life-size model of the human body that mimics various anatomical functions to teach skills and procedures in health education; may be low-fidelity (having limited or no electronic inputs) or high-fidelity\r(connected to a computer that allows the robot to respond dynamically to user input) (Group on Information Resources, 2011; Passiment, Sacks, & Huang, 2011)',1,0,0),(12,'Plastinated Specimens','Organic material preserved by replacing water and fat in tissue with silicone, resulting in \"anatomical specimens [that] are safer to use, more pleasant to use, and are much more durable and have a much longer shelf life\" (University of Michigan Plastination Lab, n.d.); See also: Wet Lab',1,0,0),(13,'Printed Materials (or Digital Equivalent)','Reference materials produced or selected by faculty to augment course teaching and learning',1,0,0),(14,'Real Patient','An actual clinical patient',1,0,0),(15,'Searchable Electronic Database','A collection of information organized in such a way that a computer program can quickly select desired pieces of data (Webopedia, n.d.)',1,0,0),(16,'Standardized/Simulated Patient (SP)','Individual trained to portray a patient with a specific condition in a realistic, standardized and repeatable way (where portrayal/presentation varies based only on learner performance) (ASPE, 2011)',1,0,0),(17,'Task Trainer','A physical model that simulates a subset of physiologic function to include normal and abnormal anatomy (Passiment, Sacks, & Huang, 2011); Such models which provide just the key elements of the task or skill being learned (CISL, 2011)',1,0,0),(18,'Virtual Patient','An interactive computer simulation of real-life clinical scenarios for the purpose of medical training, education, or assessment (Smothers, Azan, & Ellaway, 2010)',1,0,0),(19,'Virtual/Computerized Laboratory','A practical learning environment in which technology- and computer-based simulations allow learners to engage in computer-assisted instruction while being able to ask and answer questions and also engage in discussion of content (Cooke, Irby, & O\'Brien, 2010a); also, to learn through experience by performing medical tasks, especially high-risk ones, in a safe environment (Uniformed Services University, 2011)',1,0,0),(20,'Wet Laboratory','Facilities outfitted with specialized equipment* and bench space or adjustable, flexible desktop space for working with solutions or biological materials (\"C.1 Wet Laboratories,\" 2006; Stanford University School of Medicine, 2007;\rWBDG Staff, 2010) *Often includes sinks, chemical fume hoods, biosafety cabinets, and piped services such as deionized or RO water, lab cold and hot water, lab waste/vents, carbon dioxide, vacuum, compressed air, eyewash, safety showers, natural gas, telephone, LAN, and power (\"C.1 Wet Laboratories,\" 2006)',1,0,0),(21,'Animation','',1,0,0),(22,'Medical Images','',1,0,0),(23,'Mobile Application','',1,0,0),(24,'Scenario','',1,0,0),(25,'Ultrasound','',1,0,0),(26,'Virtual Reality','',1,0,0);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `meta_type_relations` (
  `meta_data_relation_id` int(11) NOT NULL AUTO_INCREMENT,
  `meta_type_id` int(10) unsigned DEFAULT NULL,
  `entity_type` varchar(63) NOT NULL,
  `entity_value` varchar(63) NOT NULL,
  PRIMARY KEY (`meta_data_relation_id`),
  UNIQUE KEY `meta_type_id` (`meta_type_id`,`entity_type`,`entity_value`)
) ENGINE=MyISAM AUTO_INCREMENT=19 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `meta_type_relations` VALUES (1,1,'organisation:group','1:student'),(2,7,'organisation:group','1:student'),(3,3,'organisation:group','1:student'),(4,4,'organisation:group','1:student'),(5,5,'organisation:group','1:student'),(6,8,'organisation:group','1:student'),(7,9,'organisation:group','1:student'),(8,10,'organisation:group','1:student'),(9,11,'organisation:group','1:student'),(10,12,'organisation:group','1:student'),(11,13,'organisation:group','1:student'),(12,14,'organisation:group','1:student'),(13,15,'organisation:group','1:student'),(14,16,'organisation:group','1:student'),(15,17,'organisation:group','1:student'),(16,18,'organisation:group','1:student'),(17,20,'organisation:group','1:student'),(18,21,'organisation:group','1:student');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `meta_types` (
  `meta_type_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `label` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `parent_type_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`meta_type_id`)
) ENGINE=MyISAM AUTO_INCREMENT=22 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `meta_types` VALUES (1,'N95 Mask Fit','Make, Model, and size definition of required N95 masks.',NULL),(2,'Police Record Check','Police Record Checks to verify background as clear of events which could prevent placement in hospitals or clinics.',NULL),(3,'Full','Full record check. Due to differences in how police departments handle reporting of background checks, vulnerable sector screening (VSS) is a separate type of record',2),(4,'Vulnerable Sector Screening','Required for placement in hospitals or clinics. May be included in full police record checks or may be a separate document.',2),(5,'Assertion','Yearly or bi-yearly assertion that prior police background checks remain valid.',2),(6,'Immunization/Health Check','',NULL),(7,'Hepatitis B','',6),(8,'Tuberculosis','',6),(9,'Measles','',6),(10,'Mumps','',6),(11,'Rubella','',6),(12,'Tetanus/Diptheria','',6),(13,'Polio','',6),(14,'Varicella','',6),(15,'Pertussis','',6),(16,'Influenza','Each student is required to obtain an annual influenza immunization. The Ontario government provides the influenza vaccine free to all citizens during the flu season. Students will be required to follow Public Health guidelines put forward for health care professionals. Thia immunization must be received by December 1st each academic year and documentation forwarded to the UGME office by the student',6),(17,'Hepatitis C','',6),(18,'HIV','',6),(19,'Cardiac Life Support','',NULL),(20,'Basic','',19),(21,'Advanced','',19);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `meta_values` (
  `meta_value_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `meta_type_id` int(10) unsigned NOT NULL,
  `proxy_id` int(10) unsigned NOT NULL,
  `data_value` varchar(255) NOT NULL,
  `value_notes` text NOT NULL,
  `effective_date` bigint(20) DEFAULT NULL,
  `expiry_date` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`meta_value_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `migration` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL,
  `success` int(4) NOT NULL DEFAULT '0',
  `fail` int(4) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `migrations` VALUES ('2015_01_28_143720_556',1,0,0,1450108251),('2015_10_05_115238_571',1,1,0,1450108251),('2015_10_07_140708_607',1,1,0,1450108251),('2015_11_09_114101_211',1,3,0,1450108251),('2015_11_19_141523_555',1,1,0,1450108251),('2015_12_14_100257_655',1,0,0,1450108251),('2015_02_06_141230_501',1,14,0,1464582935),('2015_02_10_162530_501',1,4,0,1464582935),('2015_04_23_213148_701',1,2,0,1464582935),('2015_11_02_143612_445',1,3,0,1464582935),('2015_12_23_000231_648',1,2,0,1464582935),('2016_01_14_163721_658',1,250,0,1464582935),('2016_01_29_151435_666',1,3,0,1464582936),('2016_03_10_124616_686',1,1,0,1464582936),('2016_03_23_225930_706',1,1,0,1464582936),('2016_03_28_202227_707',1,1,0,1464582936),('2016_03_28_211819_726',1,3,0,1464582936),('2016_03_29_113528_696',1,3,0,1464582936),('2016_04_05_105040_745',1,3,0,1464582936),('2016_04_05_145610_747',1,3,0,1464582936),('2016_05_29_235550_857',1,2,0,1464582936),('2015_01_28_154313_558',1,48,0,1483495901),('2015_02_13_100237_558',1,1,0,1483495901),('2015_02_20_103041_560',1,2,0,1483495901),('2015_02_20_130446_558',1,4,0,1483495901),('2015_02_24_083616_558',1,0,0,1483495902),('2015_02_24_124946_558',1,1,0,1483495902),('2015_02_24_142830_558',1,3,0,1483495902),('2015_02_24_160446_558',1,6,0,1483495902),('2015_02_25_160706_558',1,1,0,1483495902),('2015_02_25_161433_558',1,2,0,1483495902),('2015_02_25_194020_558',1,1,0,1483495902),('2015_02_25_200000_558',1,1,0,1483495902),('2015_02_26_094149_558',1,3,0,1483495902),('2015_02_26_143839_558',1,9,0,1483495902),('2015_02_26_173341_558',1,1,0,1483495902),('2015_02_26_191105_558',1,1,0,1483495902),('2015_03_03_131415_558',1,1,0,1483495902),('2015_03_03_141256_558',1,2,0,1483495902),('2015_04_10_145114_558',1,1,0,1483495902),('2015_05_15_130638_558',1,3,0,1483495902),('2015_05_20_125455_558',1,1,0,1483495902),('2015_05_22_093608_558',1,1,0,1483495902),('2015_05_22_105753_558',1,1,0,1483495902),('2015_05_27_141448_558',1,1,0,1483495902),('2015_06_25_162521_558',1,1,0,1483495902),('2015_06_26_103137_558',1,2,0,1483495902),('2015_06_30_132039_558',1,1,0,1483495902),('2015_07_07_112833_558',1,3,0,1483495902),('2015_07_15_134443_558',1,1,0,1483495902),('2015_07_27_085651_558',1,4,0,1483495902),('2015_07_27_110335_558',1,4,0,1483495902),('2015_07_27_123307_558',1,1,0,1483495902),('2015_08_07_134306_558',1,8,0,1483495902),('2015_08_12_120043_558',1,3,0,1483495902),('2015_08_12_145426_558',1,2,0,1483495902),('2015_08_25_100105_558',1,1,0,1483495902),('2015_08_27_131116_558',1,1,0,1483495902),('2015_09_01_134859_558',1,1,0,1483495902),('2015_09_03_094219_558',1,1,0,1483495902),('2015_09_08_105058_558',1,2,0,1483495902),('2015_09_10_090232_573',1,2,0,1483495902),('2015_09_11_104429_558',1,1,0,1483495902),('2015_09_16_142818_558',1,3,0,1483495902),('2015_09_29_101115_558',1,2,0,1483495902),('2015_10_02_144459_558',1,2,0,1483495902),('2015_10_08_114141_558',1,1,0,1483495902),('2015_10_15_105816_558',1,3,0,1483495902),('2015_11_18_091429_558',1,9,0,1483495903),('2015_11_18_133543_558',1,1,0,1483495903),('2015_11_30_105832_558',1,1,0,1483495903),('2015_12_03_100956_558',1,1,0,1483495903),('2015_12_10_104135_558',1,1,0,1483495903),('2016_01_14_104922_558',1,1,0,1483495903),('2016_01_20_121723_558',1,3,0,1483495903),('2016_01_21_143258_558',1,1,0,1483495903),('2016_01_22_091315_558',1,1,0,1483495903),('2016_01_28_111745_558',1,0,0,1483495903),('2016_02_03_145049_558',1,1,0,1483495903),('2016_03_01_093559_558',1,2,0,1483495903),('2016_03_07_104809_558',1,1,0,1483495903),('2016_04_05_094031_558',1,2,0,1483495903),('2016_04_07_164823_695',1,1,0,1483495903),('2016_04_08_093542_762',1,3,0,1483495903),('2016_04_08_163323_744',1,5,0,1483495903),('2016_04_22_113127_780',1,2,0,1483495903),('2016_04_28_111323_29',1,5,0,1483495903),('2016_05_02_152214_695',1,14,0,1483495903),('2016_05_05_145709_695',1,2,0,1483495903),('2016_05_06_094958_695',1,1,0,1483495903),('2016_05_06_100718_695',1,1,0,1483495903),('2016_05_06_133104_695',1,1,0,1483495903),('2016_05_09_095058_806',1,1,0,1483495903),('2016_05_09_145312_793',1,1,0,1483495903),('2016_05_10_085657_558',1,3,0,1483495903),('2016_05_13_145916_695',1,3,0,1483495903),('2016_05_20_084021_841',1,1,0,1483495903),('2016_05_24_102734_558',1,1,0,1483495903),('2016_05_24_105810_695',1,1,0,1483495903),('2016_05_26_132210_558',1,2,0,1483495903),('2016_05_30_095337_695',1,1,0,1483495903),('2016_05_31_133812_558',1,1,0,1483495903),('2016_06_06_114233_885',1,3,0,1483495903),('2016_06_09_150429_889',1,2,0,1483495903),('2016_06_09_152346_901',1,4,0,1483495904),('2016_06_13_083546_883',1,1,0,1483495904),('2016_06_13_114418_810',1,1,0,1483495904),('2016_06_14_100522_914',1,1,0,1483495904),('2016_06_14_102557_923',1,1,0,1483495904),('2016_06_16_115300_932',1,1,0,1483495904),('2016_06_17_131437_558',1,3,0,1483495904),('2016_06_21_162705_957',1,1,0,1483495904),('2016_06_27_090833_949',1,1,0,1483495904),('2016_06_30_100114_971',1,1,0,1483495904),('2016_07_04_141351_695',1,1,0,1483495904),('2016_07_04_163356_695',1,1,0,1483495904),('2016_07_04_164821_695',1,1,0,1483495904),('2016_07_06_125605_994',1,1,0,1483495904),('2016_07_11_085528_942',1,4,0,1483495904),('2016_07_11_141024_558',1,3,0,1483495904),('2016_07_25_092223_1040',1,1,0,1483495904),('2016_07_25_093116_1028',1,1,0,1483495904),('2016_08_12_132048_558',1,3,0,1483495904),('2016_08_16_171553_1098',1,1,0,1483495904),('2016_08_25_083636_1087',1,3,0,1483495904),('2016_08_25_093740_558',1,1,0,1483495904),('2016_08_25_111933_1081',1,1,0,1483495904),('2016_08_26_143049_1090',1,1,0,1483495904),('2016_09_01_091420_1110',1,1,0,1483495904),('2016_09_06_143101_1101',1,2,0,1483495904),('2016_09_13_121207_358',1,1,0,1483495904),('2016_09_13_122218_1118',1,1,0,1483495904),('2016_09_16_083715_1004',1,2,0,1483495904),('2016_10_05_085608_1184',1,2,0,1483495904),('2016_10_05_100952_558',1,5,0,1483495904),('2016_10_14_152629_558',1,1,0,1483495904),('2016_10_14_153051_558',1,1,0,1483495904),('2016_10_20_145751_1126',1,1,0,1483495904),('2016_11_03_123219_1031',1,2,0,1483495904),('2016_11_04_095730_1306',1,3,0,1483495904),('2016_11_11_085655_1354',1,1,0,1483495904),('2016_11_15_172222_532',1,2,0,1483495904),('2016_11_23_121842_1342',1,2,0,1483495905),('2016_11_28_102729_1426',1,1,0,1483495905),('2016_12_07_115513_558',1,1,0,1483495905),('2017_01_03_201034_1508',1,2,0,1483495905),('2016_01_30_124831_502',1,4,0,1492431737),('2016_06_10_182935_900',1,29,0,1492431737),('2016_06_10_201813_900',1,2,0,1492431737),('2016_06_10_203558_900',1,1,0,1492431737),('2016_07_12_182633_900',1,2,0,1492431737),('2016_07_13_162523_900',1,0,0,1492431737),('2016_07_18_161953_175',1,1,0,1492431737),('2016_07_18_163204_175',1,0,0,1492431737),('2016_07_18_185148_175',1,1,0,1492431737),('2016_07_21_102058_1443',1,1,0,1492431737),('2016_08_07_002215_1060',1,1,0,1492431737),('2016_08_07_002634_1060',1,1,0,1492431737),('2016_08_22_153916_1529',1,1,0,1492431737),('2016_08_25_101403_1541',1,0,0,1492431738),('2016_09_01_214358_1547',1,1,0,1492431738),('2016_09_07_192943_1548',1,1,0,1492431738),('2016_09_18_124637_1573',1,2,0,1492431738),('2016_09_19_155932_1330',1,2,0,1492431738),('2016_09_19_183432_1573',1,3,0,1492431738),('2016_10_14_102857_1583',1,1,0,1492431738),('2016_10_14_104207_1583',1,0,0,1492431738),('2016_10_15_111346_1605',1,3,0,1492431738),('2016_10_17_185451_1605',1,1,0,1492431738),('2016_10_19_100101_502',1,1,0,1492431738),('2016_10_28_150043_558',1,1,0,1492431738),('2016_11_16_215252_1633',1,1,0,1492431738),('2017_01_03_124412_1506',1,1,0,1492431738),('2017_01_04_095257_1292',1,9,0,1492431738),('2017_01_25_103134_1700',1,0,0,1492431738),('2017_01_31_081059_558',1,1,0,1492431738),('2017_02_03_133221_558',1,1,0,1492431738),('2017_02_09_102028_558',1,0,0,1492431738),('2017_02_09_145025_558',1,1,0,1492431738),('2017_02_10_135318_558',1,1,0,1492431738),('2017_02_14_021253_1618',1,1,0,1492431738),('2017_02_15_133254_1594',1,1,0,1492431738),('2017_02_17_095629_1622',1,1,0,1492431738),('2017_02_23_100009_558',1,8,0,1492431738),('2017_03_01_161920_900',1,3,0,1492431738),('2017_03_09_124107_1679',1,1,0,1492431738),('2017_03_13_132425_1692',1,1,0,1492431738),('2017_03_14_143617_900',1,1,0,1492431739),('2017_03_14_151620_1695',1,1,0,1492431739),('2017_03_27_212435_1155',1,2,0,1492431739),('2017_03_28_162020_1693',1,1,0,1492431739),('2017_03_28_162825_1693',1,1,0,1492431739),('2017_03_31_094258_900',1,3,0,1492431739),('2017_04_05_123631_1764',1,1,0,1492431739),('2017_04_07_112731_1807',1,1,0,1492431739),('2017_04_17_081350_1805',1,2,0,1492431739);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mtd_categories` (
  `id` int(11) NOT NULL,
  `category_code` varchar(3) NOT NULL,
  `category_description` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mtd_facilities` (
  `id` int(11) NOT NULL,
  `facility_code` int(3) NOT NULL,
  `facility_name` varchar(50) NOT NULL,
  `kingston` int(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mtd_locale_duration` (
  `id` int(11) NOT NULL,
  `location_id` int(3) NOT NULL,
  `percent_time` int(3) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mtd_moh_program_codes` (
  `id` int(11) NOT NULL,
  `program_code` varchar(3) NOT NULL,
  `program_description` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mtd_moh_service_codes` (
  `id` int(11) NOT NULL,
  `service_code` varchar(3) NOT NULL,
  `service_description` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mtd_pgme_moh_programs` (
  `id` int(11) NOT NULL,
  `pgme_program_name` varchar(100) NOT NULL,
  `moh_service_name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mtd_schedule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `service_id` int(3) NOT NULL,
  `resident_id` int(11) NOT NULL,
  `creator_id` int(12) NOT NULL,
  `type_code` varchar(1) NOT NULL,
  `updated_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(12) NOT NULL,
  `category_id` int(3) DEFAULT NULL,
  `home_program_id` int(3) DEFAULT NULL,
  `home_school_id` int(3) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mtd_schools` (
  `id` int(11) NOT NULL,
  `school_code` varchar(3) NOT NULL,
  `school_description` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mtd_type` (
  `id` int(12) NOT NULL AUTO_INCREMENT,
  `type_code` varchar(1) NOT NULL,
  `type_description` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `mtd_type` VALUES (1,'I','in-patient/emergency'),(2,'O','out-patient');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notice_audience` (
  `naudience_id` int(11) NOT NULL AUTO_INCREMENT,
  `notice_id` int(11) NOT NULL,
  `audience_type` varchar(20) NOT NULL,
  `audience_value` int(11) NOT NULL DEFAULT '0',
  `updated_by` int(11) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  PRIMARY KEY (`naudience_id`),
  KEY `audience_id` (`notice_id`,`audience_type`,`audience_value`,`updated_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notices` (
  `notice_id` int(12) NOT NULL AUTO_INCREMENT,
  `organisation_id` int(12) DEFAULT NULL,
  `notice_summary` text NOT NULL,
  `notice_details` text NOT NULL,
  `display_from` bigint(64) NOT NULL DEFAULT '0',
  `display_until` bigint(64) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  `created_by` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`notice_id`),
  KEY `display_from` (`display_from`),
  KEY `display_until` (`display_until`),
  KEY `organisation_id` (`organisation_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notification_users` (
  `nuser_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `proxy_id` int(11) NOT NULL,
  `notification_user_type` enum('proxy_id','external_assessor_id') NOT NULL DEFAULT 'proxy_id',
  `content_type` varchar(48) NOT NULL DEFAULT '',
  `record_id` int(11) NOT NULL,
  `record_proxy_id` int(11) DEFAULT NULL,
  `notify_active` tinyint(1) NOT NULL DEFAULT '0',
  `digest_mode` tinyint(1) NOT NULL DEFAULT '0',
  `next_notification_date` int(64) NOT NULL DEFAULT '0',
  PRIMARY KEY (`nuser_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `notification_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `nuser_id` int(11) NOT NULL,
  `notification_body` text NOT NULL,
  `proxy_id` int(11) NOT NULL,
  `from_email` varchar(255) DEFAULT NULL,
  `from_firstname` varchar(255) DEFAULT NULL,
  `from_lastname` varchar(255) DEFAULT NULL,
  `digest` tinyint(1) NOT NULL DEFAULT '0',
  `sent` tinyint(1) NOT NULL DEFAULT '0',
  `sent_date` bigint(64) DEFAULT '0',
  PRIMARY KEY (`notification_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `objective_audience` (
  `oaudience_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `objective_id` int(12) NOT NULL,
  `organisation_id` int(12) NOT NULL,
  `audience_type` enum('COURSE','EVENT') NOT NULL DEFAULT 'COURSE',
  `audience_value` varchar(12) NOT NULL DEFAULT '',
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(12) NOT NULL,
  PRIMARY KEY (`oaudience_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `objective_audience` VALUES (1,1,1,'COURSE','all',0,0),(2,200,1,'COURSE','all',0,0),(3,309,1,'COURSE','all',0,0);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `objective_organisation` (
  `objective_id` int(12) NOT NULL,
  `organisation_id` int(12) NOT NULL,
  PRIMARY KEY (`objective_id`,`organisation_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `objective_organisation` VALUES (1,1),(2,1),(3,1),(4,1),(5,1),(6,1),(7,1),(8,1),(9,1),(10,1),(11,1),(12,1),(13,1),(14,1),(15,1),(16,1),(17,1),(18,1),(19,1),(20,1),(21,1),(22,1),(23,1),(24,1),(25,1),(26,1),(27,1),(28,1),(29,1),(30,1),(32,1),(33,1),(34,1),(35,1),(36,1),(37,1),(38,1),(39,1),(40,1),(41,1),(42,1),(43,1),(44,1),(45,1),(46,1),(47,1),(48,1),(49,1),(50,1),(51,1),(52,1),(53,1),(54,1),(55,1),(56,1),(57,1),(58,1),(59,1),(60,1),(61,1),(62,1),(63,1),(64,1),(65,1),(66,1),(67,1),(68,1),(69,1),(70,1),(71,1),(72,1),(73,1),(74,1),(75,1),(76,1),(77,1),(78,1),(79,1),(80,1),(81,1),(82,1),(83,1),(84,1),(85,1),(86,1),(87,1),(88,1),(89,1),(90,1),(91,1),(92,1),(93,1),(94,1),(95,1),(96,1),(97,1),(98,1),(99,1),(100,1),(101,1),(102,1),(103,1),(104,1),(105,1),(106,1),(107,1),(108,1),(109,1),(110,1),(111,1),(112,1),(113,1),(114,1),(115,1),(116,1),(117,1),(118,1),(119,1),(120,1),(121,1),(122,1),(123,1),(124,1),(125,1),(126,1),(127,1),(128,1),(129,1),(130,1),(131,1),(132,1),(133,1),(134,1),(135,1),(136,1),(137,1),(138,1),(139,1),(140,1),(141,1),(142,1),(143,1),(144,1),(145,1),(146,1),(147,1),(148,1),(149,1),(150,1),(151,1),(152,1),(153,1),(154,1),(155,1),(156,1),(157,1),(158,1),(159,1),(160,1),(161,1),(162,1),(163,1),(164,1),(165,1),(166,1),(167,1),(168,1),(169,1),(170,1),(171,1),(172,1),(173,1),(174,1),(175,1),(176,1),(177,1),(178,1),(179,1),(180,1),(181,1),(182,1),(183,1),(184,1),(185,1),(186,1),(187,1),(188,1),(189,1),(190,1),(191,1),(200,1),(201,1),(202,1),(203,1),(204,1),(205,1),(206,1),(207,1),(208,1),(209,1),(210,1),(211,1),(212,1),(213,1),(214,1),(215,1),(216,1),(217,1),(218,1),(219,1),(221,1),(222,1),(223,1),(224,1),(225,1),(226,1),(228,1),(233,1),(234,1),(235,1),(236,1),(237,1),(238,1),(239,1),(240,1),(241,1),(242,1),(257,1),(258,1),(259,1),(260,1),(261,1),(262,1),(263,1),(264,1),(265,1),(266,1),(267,1),(268,1),(269,1),(270,1),(271,1),(272,1),(273,1),(274,1),(275,1),(276,1),(277,1),(278,1),(279,1),(280,1),(281,1),(282,1),(283,1),(284,1),(286,1),(287,1),(288,1),(289,1),(290,1),(291,1),(292,1),(293,1),(294,1),(295,1),(296,1),(299,1),(300,1),(303,1),(304,1),(305,1),(306,1),(307,1),(308,1),(309,1),(310,1),(311,1),(312,1),(313,1),(314,1),(315,1),(316,1),(317,1),(318,1),(319,1),(320,1),(321,1),(322,1),(323,1),(324,1),(325,1),(326,1),(327,1),(328,1),(329,1),(330,1),(331,1),(332,1),(333,1),(334,1),(335,1),(336,1),(337,1),(338,1),(339,1),(340,1),(341,1),(342,1),(343,1),(344,1),(345,1),(346,1),(347,1),(348,1),(349,1),(350,1),(351,1),(352,1),(353,1),(354,1),(355,1),(356,1),(357,1),(358,1),(359,1),(360,1),(361,1),(362,1),(363,1),(364,1),(365,1),(366,1),(367,1),(368,1),(369,1),(370,1),(371,1),(372,1),(373,1),(374,1),(375,1),(376,1),(377,1),(378,1),(379,1),(380,1),(381,1),(382,1),(383,1),(384,1),(385,1),(386,1),(387,1),(388,1),(389,1),(390,1),(391,1),(392,1),(393,1),(394,1),(395,1),(396,1),(397,1),(398,1),(399,1),(400,1),(401,1),(402,1),(403,1),(404,1),(405,1),(406,1),(407,1),(408,1),(409,1),(410,1),(411,1),(412,1),(413,1),(414,1),(415,1),(416,1),(417,1),(418,1),(419,1),(420,1),(421,1),(422,1),(423,1),(424,1),(425,1),(426,1),(427,1),(428,1),(429,1),(430,1),(431,1),(432,1),(433,1),(434,1),(435,1),(436,1),(437,1),(438,1),(439,1),(440,1),(441,1),(442,1),(443,1),(444,1),(445,1),(446,1),(447,1),(448,1),(449,1),(450,1),(451,1),(452,1),(453,1),(454,1),(455,1),(456,1),(457,1),(458,1),(459,1),(460,1),(461,1),(462,1),(463,1),(464,1),(465,1),(466,1),(467,1),(468,1),(469,1),(470,1),(471,1),(472,1),(473,1),(474,1),(475,1),(476,1),(477,1),(478,1),(479,1),(480,1),(481,1),(482,1),(483,1),(484,1),(485,1),(486,1),(487,1),(488,1),(489,1),(490,1),(491,1),(492,1),(493,1),(494,1),(495,1),(496,1),(497,1),(498,1),(499,1),(500,1),(501,1),(502,1),(503,1),(504,1),(505,1),(506,1),(507,1),(508,1),(509,1),(510,1),(511,1),(512,1),(513,1),(514,1),(515,1),(516,1),(517,1),(518,1),(519,1),(520,1),(521,1),(522,1),(523,1),(524,1),(525,1),(526,1),(527,1),(528,1),(529,1),(2328,1),(2329,1),(2330,1),(2331,1),(2332,1),(2333,1),(2334,1),(2335,1),(2336,1),(2337,1),(2338,1),(2339,1),(2340,1),(2341,1),(2342,1),(2343,1),(2344,1),(2345,1),(2346,1),(2347,1),(2348,1),(2349,1),(2350,1),(2351,1),(2352,1),(2353,1),(2354,1),(2355,1),(2356,1),(2357,1),(2358,1),(2359,1),(2360,1),(2361,1),(2362,1),(2363,1),(2364,1),(2365,1),(2366,1),(2367,1),(2368,1),(2369,1),(2370,1),(2371,1),(2372,1),(2373,1),(2374,1),(2375,1),(2376,1),(2377,1),(2378,1),(2379,1),(2380,1),(2381,1),(2382,1),(2383,1),(2384,1),(2385,1),(2386,1),(2387,1),(2388,1),(2389,1),(2390,1),(2391,1),(2392,1),(2393,1),(2394,1),(2395,1),(2396,1),(2397,1),(2398,1),(2399,1),(2400,1),(2401,1),(2402,1);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `observership_reflections` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `observership_id` int(11) NOT NULL,
  `physicians_role` text NOT NULL,
  `physician_reflection` text NOT NULL,
  `role_practice` text,
  `observership_challenge` text NOT NULL,
  `discipline_reflection` text NOT NULL,
  `challenge_predictions` text,
  `questions` text,
  `career` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `org_community_types` (
  `octype_id` int(12) NOT NULL AUTO_INCREMENT,
  `organisation_id` int(12) NOT NULL,
  `community_type_name` varchar(84) NOT NULL DEFAULT '',
  `default_community_template` varchar(30) NOT NULL DEFAULT 'default',
  `default_community_theme` varchar(12) NOT NULL DEFAULT 'default',
  `default_community_keywords` varchar(255) NOT NULL DEFAULT '',
  `default_community_protected` int(1) NOT NULL DEFAULT '1',
  `default_community_registration` int(1) NOT NULL DEFAULT '1',
  `default_community_members` text NOT NULL,
  `default_mail_list_type` enum('announcements','discussion','inactive') NOT NULL DEFAULT 'inactive',
  `community_type_options` text NOT NULL,
  `community_type_active` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`octype_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `org_community_types` VALUES (1,1,'Community','default','default','',1,0,'','inactive','{}',1),(2,1,'Course Website','course','course','',1,0,'','inactive','{\"course_website\":\"1\"}',1),(3,1,'Learning Module','learningmodule','default','',1,0,'','inactive','{\"sequential_navigation\":\"1\"}',1);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `organisation_lu_restricted_days` (
  `orday_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `organisation_id` int(12) NOT NULL,
  `date_type` enum('specific','computed','weekly','monthly') NOT NULL DEFAULT 'specific',
  `offset` tinyint(1) DEFAULT NULL,
  `day` tinyint(2) DEFAULT NULL,
  `month` tinyint(2) DEFAULT NULL,
  `year` int(4) DEFAULT NULL,
  `updated_date` int(12) NOT NULL,
  `updated_by` int(12) NOT NULL,
  `day_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`orday_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `permission_id` int(12) NOT NULL AUTO_INCREMENT,
  `assigned_by` int(12) NOT NULL DEFAULT '0',
  `assigned_to` int(12) NOT NULL DEFAULT '0',
  `valid_from` bigint(64) NOT NULL DEFAULT '0',
  `valid_until` bigint(64) NOT NULL DEFAULT '0',
  `teaching_reminders` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`permission_id`),
  KEY `assigned_by` (`assigned_by`),
  KEY `assigned_to` (`assigned_to`),
  KEY `valid_from` (`valid_from`),
  KEY `valid_until` (`valid_until`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pg_blocks` (
  `id` int(12) NOT NULL AUTO_INCREMENT,
  `block_name` varchar(8) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `year` varchar(9) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=27 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `pg_blocks` VALUES (1,'1','2010-07-01','2010-07-26','2010-2011'),(2,'2','2010-07-27','2010-08-23','2010-2011'),(3,'3','2010-08-24','2010-09-20','2010-2011'),(4,'4','2010-09-21','2010-10-18','2010-2011'),(5,'5','2010-10-19','2010-11-15','2010-2011'),(6,'6','2010-11-16','2010-12-13','2010-2011'),(7,'7','2010-12-14','2011-01-10','2010-2011'),(8,'8','2011-01-11','2011-02-07','2010-2011'),(9,'9','2011-02-08','2011-03-07','2010-2011'),(10,'10','2011-03-08','2011-04-04','2010-2011'),(11,'11','2011-04-05','2011-05-02','2010-2011'),(12,'12','2011-05-03','2011-05-30','2010-2011'),(13,'13','2011-05-31','2011-06-30','2010-2011'),(14,'1','2011-07-01','2011-08-01','2011-2012'),(15,'2','2011-08-02','2011-08-29','2011-2012'),(16,'3','2011-08-30','2011-09-26','2011-2012'),(17,'4','2011-09-27','2011-10-24','2011-2012'),(18,'5','2011-10-25','2011-11-21','2011-2012'),(19,'6','2011-11-22','2011-12-19','2011-2012'),(20,'7','2012-12-20','2012-01-16','2011-2012'),(21,'8','2012-01-17','2012-02-13','2011-2012'),(22,'9','2012-02-14','2012-03-12','2011-2012'),(23,'10','2012-03-13','2012-04-09','2011-2012'),(24,'11','2012-04-10','2012-05-07','2011-2012'),(25,'12','2012-05-08','2012-06-04','2011-2012'),(26,'13','2012-06-05','2012-06-30','2011-2012');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pg_eval_response_rates` (
  `id` int(12) NOT NULL AUTO_INCREMENT,
  `program_name` varchar(100) NOT NULL,
  `response_type` varchar(20) NOT NULL,
  `completed` int(10) NOT NULL,
  `distributed` int(10) NOT NULL,
  `percent_complete` int(3) NOT NULL,
  `gen_date` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pg_one45_community` (
  `id` int(12) NOT NULL AUTO_INCREMENT,
  `one45_name` varchar(50) NOT NULL,
  `community_name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `poll_answers` (
  `answer_id` int(12) NOT NULL AUTO_INCREMENT,
  `poll_id` int(12) NOT NULL DEFAULT '0',
  `answer_text` varchar(255) NOT NULL,
  `answer_order` int(2) NOT NULL DEFAULT '0',
  PRIMARY KEY (`answer_id`),
  KEY `poll_id` (`poll_id`),
  KEY `answer_order` (`answer_order`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `poll_questions` (
  `poll_id` int(12) NOT NULL AUTO_INCREMENT,
  `poll_target_type` enum('group','grad_year','cohort') NOT NULL,
  `poll_target` varchar(32) NOT NULL DEFAULT 'all',
  `poll_question` text NOT NULL,
  `poll_from` bigint(64) NOT NULL DEFAULT '0',
  `poll_until` bigint(64) NOT NULL DEFAULT '0',
  PRIMARY KEY (`poll_id`),
  KEY `poll_target` (`poll_target`),
  KEY `poll_from` (`poll_from`),
  KEY `poll_until` (`poll_until`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `poll_results` (
  `result_id` int(12) NOT NULL AUTO_INCREMENT,
  `poll_id` int(12) NOT NULL DEFAULT '0',
  `answer_id` int(12) NOT NULL DEFAULT '0',
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `ip` varchar(64) NOT NULL,
  `timestamp` bigint(64) NOT NULL DEFAULT '0',
  PRIMARY KEY (`result_id`),
  KEY `poll_id` (`poll_id`),
  KEY `answer_id` (`answer_id`),
  KEY `proxy_id` (`proxy_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `portfolio-advisors` (
  `padvisor_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `proxy_id` int(11) NOT NULL,
  `portfolio_id` int(11) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`padvisor_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `portfolio_artifact_permissions` (
  `permission_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pentry_id` int(10) unsigned NOT NULL,
  `allow_to` int(10) unsigned NOT NULL COMMENT 'Who allowed to access',
  `proxy_id` int(10) unsigned NOT NULL COMMENT 'Who has created this permission',
  `view` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `comment` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `edit` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`permission_id`),
  KEY `portfolio_user_permissions_pentry_id` (`pentry_id`),
  CONSTRAINT `portfolio_user_permissions_pentry_id` FOREIGN KEY (`pentry_id`) REFERENCES `portfolio_entries` (`pentry_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `portfolio_entries` (
  `pentry_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pfartifact_id` int(11) unsigned NOT NULL,
  `proxy_id` int(11) unsigned NOT NULL COMMENT 'proxy_id of users from entrada_auth.user_data.id',
  `submitted_date` bigint(64) unsigned NOT NULL DEFAULT '0',
  `reviewed_date` bigint(64) unsigned NOT NULL DEFAULT '0',
  `reviewed_by` int(10) unsigned NOT NULL,
  `flag` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `flagged_by` int(10) unsigned NOT NULL,
  `flagged_date` bigint(64) unsigned NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11) unsigned NOT NULL COMMENT 'proxy_id of users from entrada_auth.user_data.id',
  `_edata` text NOT NULL,
  `_class` varchar(200) NOT NULL,
  `order` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `type` enum('file','reflection','url') NOT NULL DEFAULT 'reflection',
  PRIMARY KEY (`pentry_id`),
  KEY `pfartifact_id` (`pfartifact_id`),
  KEY `order` (`order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Used to record portfolio entries made by learners.';
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `portfolio_entry_comments` (
  `pecomment_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pentry_id` int(11) unsigned NOT NULL,
  `proxy_id` int(11) unsigned NOT NULL COMMENT 'proxy_id of users from entrada_auth.user_data.id',
  `comment` text NOT NULL,
  `submitted_date` bigint(64) unsigned NOT NULL,
  `flag` tinyint(1) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11) unsigned NOT NULL COMMENT 'proxy_id of users from entrada_auth.user_data.id',
  PRIMARY KEY (`pecomment_id`),
  KEY `pentry_id` (`pentry_id`),
  CONSTRAINT `portfolio_entry_comments_ibfk_1` FOREIGN KEY (`pentry_id`) REFERENCES `portfolio_entries` (`pentry_id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Used to store comments on particular portfolio entries.';
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `portfolio_folder_artifact_reviewers` (
  `pfareviewer_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pfartifact_id` int(11) unsigned NOT NULL,
  `proxy_id` int(11) unsigned NOT NULL COMMENT 'proxy_id of users from entrada_auth.user_data.id',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11) unsigned NOT NULL COMMENT 'proxy_id of users from entrada_auth.user_data.id',
  PRIMARY KEY (`pfareviewer_id`),
  KEY `pfartifact_id` (`pfartifact_id`),
  CONSTRAINT `portfolio_folder_artifact_reviewers_ibfk_1` FOREIGN KEY (`pfartifact_id`) REFERENCES `portfolio_folder_artifacts` (`pfartifact_id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='List teachers responsible for reviewing an artifact.';
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `portfolio_folder_artifacts` (
  `pfartifact_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pfolder_id` int(11) unsigned NOT NULL,
  `artifact_id` int(11) unsigned NOT NULL,
  `proxy_id` int(10) unsigned NOT NULL DEFAULT '0',
  `title` varchar(128) NOT NULL,
  `description` text NOT NULL,
  `start_date` bigint(64) unsigned NOT NULL DEFAULT '0',
  `finish_date` bigint(64) unsigned NOT NULL DEFAULT '0',
  `allow_commenting` tinyint(1) NOT NULL DEFAULT '0',
  `order` int(3) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11) unsigned NOT NULL COMMENT 'proxy_id of users from entrada_auth.user_data.id',
  `_edata` text,
  `handler_object` varchar(80) NOT NULL,
  PRIMARY KEY (`pfartifact_id`),
  KEY `pfolder_id` (`pfolder_id`),
  KEY `artifact_id` (`artifact_id`),
  CONSTRAINT `portfolio_folder_artifacts_ibfk_1` FOREIGN KEY (`pfolder_id`) REFERENCES `portfolio_folders` (`pfolder_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `portfolio_folder_artifacts_ibfk_2` FOREIGN KEY (`artifact_id`) REFERENCES `portfolios_lu_artifacts` (`artifact_id`) ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='List of artifacts within a particular portfolio folder.';
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `portfolio_folders` (
  `pfolder_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `portfolio_id` int(11) unsigned NOT NULL,
  `title` varchar(32) NOT NULL,
  `description` text NOT NULL,
  `allow_learner_artifacts` tinyint(1) NOT NULL DEFAULT '0',
  `order` int(3) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11) unsigned NOT NULL COMMENT 'proxy_id of users from entrada_auth.user_data.id',
  PRIMARY KEY (`pfolder_id`),
  KEY `portfolio_id` (`portfolio_id`),
  CONSTRAINT `portfolio_folders_ibfk_1` FOREIGN KEY (`portfolio_id`) REFERENCES `portfolios` (`portfolio_id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='The list of folders within each portfolio.';
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `portfolios` (
  `portfolio_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int(4) unsigned NOT NULL,
  `portfolio_name` varchar(100) NOT NULL,
  `start_date` bigint(64) unsigned NOT NULL,
  `finish_date` bigint(64) unsigned NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11) unsigned NOT NULL COMMENT 'proxy_id of users from entrada_auth.user_data.id',
  `organisation_id` int(11) NOT NULL,
  `allow_student_export` tinyint(1) unsigned DEFAULT '1',
  PRIMARY KEY (`portfolio_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='The portfolio container for each class of learners.';
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `portfolios_lu_artifacts` (
  `artifact_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(128) NOT NULL,
  `description` text NOT NULL,
  `handler_object` varchar(80) NOT NULL COMMENT 'PHP class which handles displays form to user.',
  `allow_learner_addable` tinyint(1) NOT NULL DEFAULT '0',
  `order` int(3) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `updated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11) unsigned NOT NULL COMMENT 'proxy_id of users from entrada_auth.user_data.id',
  PRIMARY KEY (`artifact_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COMMENT='Lookup table that stores all available types of artifacts.';
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `portfolios_lu_artifacts` VALUES (1,'Personal Reflection','','Portfolio_Model_Artifact_Handler_PersonalReflection',1,1,1,'2017-04-17 08:22:19',0),(2,'Document Attachment','','Portfolio_Model_Artifact_Handler_DocumentAttachment',1,2,1,'2017-04-17 08:22:19',0);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `profile_custom_fields` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `department_id` int(11) NOT NULL,
  `organisation_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(32) NOT NULL DEFAULT '',
  `type` enum('TEXTAREA','TEXTINPUT','CHECKBOX','RICHTEXT','LINK') NOT NULL DEFAULT 'TEXTAREA',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `length` smallint(3) DEFAULT NULL,
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `order` smallint(3) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `profile_custom_responses` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `field_id` int(11) NOT NULL,
  `proxy_id` int(11) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quiz_contacts` (
  `qcontact_id` int(12) NOT NULL AUTO_INCREMENT,
  `quiz_id` int(12) NOT NULL DEFAULT '0',
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`qcontact_id`),
  KEY `quiz_id` (`quiz_id`,`proxy_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quiz_progress` (
  `qprogress_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `aquiz_id` int(12) unsigned NOT NULL,
  `content_type` enum('event','community_page') DEFAULT 'event',
  `content_id` int(12) unsigned NOT NULL,
  `quiz_id` int(12) unsigned NOT NULL,
  `proxy_id` int(12) unsigned NOT NULL,
  `progress_value` varchar(16) NOT NULL,
  `quiz_score` int(12) NOT NULL,
  `quiz_value` int(12) NOT NULL,
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(12) unsigned NOT NULL,
  PRIMARY KEY (`qprogress_id`),
  KEY `content_id` (`aquiz_id`,`content_id`,`proxy_id`),
  KEY `quiz_id` (`quiz_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quiz_progress_responses` (
  `qpresponse_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `qprogress_id` int(12) unsigned NOT NULL,
  `aquiz_id` int(12) unsigned NOT NULL,
  `content_type` enum('event','community_page') NOT NULL DEFAULT 'event',
  `content_id` int(12) unsigned NOT NULL,
  `quiz_id` int(12) unsigned NOT NULL,
  `proxy_id` int(12) unsigned NOT NULL,
  `qquestion_id` int(12) unsigned NOT NULL,
  `qqresponse_id` int(12) unsigned NOT NULL,
  `updated_date` bigint(64) NOT NULL,
  `updated_by` int(12) unsigned NOT NULL,
  PRIMARY KEY (`qpresponse_id`),
  KEY `qprogress_id` (`qprogress_id`,`aquiz_id`,`content_id`,`quiz_id`,`proxy_id`,`qquestion_id`,`qqresponse_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quiz_question_responses` (
  `qqresponse_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `qquestion_id` int(12) unsigned NOT NULL,
  `response_text` longtext NOT NULL,
  `response_order` int(3) unsigned NOT NULL,
  `response_correct` enum('0','1') NOT NULL DEFAULT '0',
  `response_is_html` enum('0','1') NOT NULL,
  `response_feedback` text NOT NULL,
  `response_active` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`qqresponse_id`),
  KEY `qquestion_id` (`qquestion_id`,`response_order`,`response_correct`),
  KEY `response_is_html` (`response_is_html`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quiz_questions` (
  `qquestion_id` int(12) NOT NULL AUTO_INCREMENT,
  `quiz_id` int(12) NOT NULL DEFAULT '0',
  `questiontype_id` int(12) NOT NULL DEFAULT '1',
  `question_text` longtext NOT NULL,
  `question_points` int(6) NOT NULL DEFAULT '0',
  `question_order` int(6) NOT NULL DEFAULT '0',
  `qquestion_group_id` int(12) unsigned DEFAULT NULL,
  `question_active` int(1) NOT NULL DEFAULT '1',
  `randomize_responses` int(1) NOT NULL,
  PRIMARY KEY (`qquestion_id`),
  KEY `quiz_id` (`quiz_id`,`questiontype_id`,`question_order`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quizzes` (
  `quiz_id` int(12) NOT NULL AUTO_INCREMENT,
  `quiz_title` varchar(64) NOT NULL,
  `quiz_description` text NOT NULL,
  `quiz_active` int(1) NOT NULL DEFAULT '1',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  `created_by` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`quiz_id`),
  KEY `quiz_active` (`quiz_active`),
  FULLTEXT KEY `quiz_title` (`quiz_title`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quizzes_lu_questiontypes` (
  `questiontype_id` int(12) NOT NULL AUTO_INCREMENT,
  `questiontype_title` varchar(64) NOT NULL,
  `questiontype_description` text NOT NULL,
  `questiontype_active` int(1) NOT NULL DEFAULT '1',
  `questiontype_order` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`questiontype_id`),
  KEY `questiontype_active` (`questiontype_active`,`questiontype_order`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `quizzes_lu_questiontypes` VALUES (1,'Multiple Choice Question','',1,0),(2,'Descriptive Text','',1,0),(3,'Page Break','',1,0);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quizzes_lu_quiztypes` (
  `quiztype_id` int(12) NOT NULL AUTO_INCREMENT,
  `quiztype_code` varchar(12) NOT NULL,
  `quiztype_title` varchar(64) NOT NULL,
  `quiztype_description` text NOT NULL,
  `quiztype_active` int(1) NOT NULL DEFAULT '1',
  `quiztype_order` int(3) NOT NULL DEFAULT '0',
  PRIMARY KEY (`quiztype_id`),
  KEY `quiztype_active` (`quiztype_active`,`quiztype_order`),
  KEY `quiztype_code` (`quiztype_code`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `quizzes_lu_quiztypes` VALUES (1,'delayed','Delayed Quiz Results','This option restricts the learners ability to review the results of a quiz attempt (i.e. score, correct / incorrect responses, and question feedback) until after the time release period has expired.',1,0),(2,'immediate','Immediate Quiz Results','This option will allow the learner to review the results of a quiz attempt (i.e. score, correct / incorrect responses, and question feedback) immediately after they complete the quiz.',1,1),(3,'hide','Hide Quiz Results','This option restricts the learners ability to review the results of a quiz attempt (i.e. score, correct / incorrect responses, and question feedback), and requires either manual release of the results to the students, or use of a Gradebook Assessment to release the resulting score.',1,2);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reports_aamc_ci` (
  `raci_id` int(12) NOT NULL AUTO_INCREMENT,
  `organisation_id` int(12) NOT NULL,
  `report_title` varchar(255) NOT NULL,
  `report_date` bigint(64) NOT NULL DEFAULT '0',
  `report_start` varchar(10) NOT NULL DEFAULT '',
  `report_finish` varchar(10) NOT NULL DEFAULT '',
  `collection_start` bigint(64) NOT NULL DEFAULT '0',
  `collection_finish` bigint(64) NOT NULL DEFAULT '0',
  `report_langauge` varchar(12) NOT NULL DEFAULT 'en-us',
  `report_description` text NOT NULL,
  `report_supporting_link` text NOT NULL,
  `program_level_objective_id` int(12) DEFAULT NULL,
  `report_params` text NOT NULL,
  `report_active` tinyint(1) NOT NULL DEFAULT '1',
  `report_status` enum('draft','published') NOT NULL DEFAULT 'draft',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`raci_id`),
  KEY `report_date` (`report_date`),
  KEY `report_active` (`organisation_id`,`report_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `resource_images` (
  `image_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `resource_type` enum('course','track','objective') NOT NULL DEFAULT 'course',
  `resource_id` int(12) unsigned NOT NULL DEFAULT '0',
  `image_mimetype` varchar(64) DEFAULT NULL,
  `image_filesize` int(32) NOT NULL DEFAULT '0',
  `image_active` int(1) NOT NULL DEFAULT '1',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  PRIMARY KEY (`image_id`),
  UNIQUE KEY `resource_id` (`resource_id`,`resource_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rp_now_config` (
  `rpnow_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `exam_url` varchar(128) DEFAULT NULL,
  `exam_sponsor` int(11) unsigned DEFAULT NULL,
  `rpnow_reviewed_exam` int(1) DEFAULT '0',
  `rpnow_reviewer_notes` text,
  `exam_post_id` int(11) unsigned NOT NULL,
  `updated_date` bigint(64) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `deleted_date` bigint(64) DEFAULT NULL,
  PRIMARY KEY (`rpnow_id`),
  KEY `rp_now_config_fk_1` (`exam_post_id`),
  CONSTRAINT `rp_now_config_fk_1` FOREIGN KEY (`exam_post_id`) REFERENCES `exam_posts` (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rp_now_users` (
  `rpnow_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `proxy_id` int(11) unsigned NOT NULL,
  `exam_code` varchar(20) NOT NULL,
  `ssi_record_locator` varchar(50) DEFAULT NULL,
  `rpnow_config_id` int(11) unsigned NOT NULL,
  `created_date` bigint(64) NOT NULL,
  `created_by` int(11) NOT NULL,
  `updated_date` bigint(64) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `deleted_date` bigint(64) DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`rpnow_id`),
  KEY `rp_now_user_fk_1` (`rpnow_config_id`),
  CONSTRAINT `rp_now_user_fk_1` FOREIGN KEY (`rpnow_config_id`) REFERENCES `rp_now_config` (`rpnow_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `secure_access_files` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `resource_type` enum('exam_post','attached_quiz') DEFAULT 'exam_post',
  `resource_id` int(11) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_type` varchar(255) DEFAULT NULL,
  `file_title` varchar(128) DEFAULT NULL,
  `file_size` varchar(32) DEFAULT NULL,
  `updated_date` bigint(64) DEFAULT NULL,
  `updated_by` int(12) DEFAULT NULL,
  `deleted_date` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `secure_access_keys` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `resource_type` enum('exam_post','attached_quiz') DEFAULT 'exam_post',
  `resource_id` int(11) DEFAULT NULL,
  `key` text,
  `version` varchar(64) DEFAULT NULL,
  `updated_date` bigint(64) DEFAULT NULL,
  `updated_by` int(12) DEFAULT NULL,
  `deleted_date` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `setting_id` int(12) NOT NULL AUTO_INCREMENT,
  `shortname` varchar(64) NOT NULL,
  `organisation_id` int(12) DEFAULT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`setting_id`)
) ENGINE=MyISAM AUTO_INCREMENT=25 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `settings` VALUES (1,'version_db',NULL,'19000'),(2,'version_entrada',NULL,'1.9.0'),(3,'export_weighted_grade',NULL,'1'),(4,'export_calculated_grade',NULL,'{\"enabled\":0}'),(5,'course_webpage_assessment_cohorts_count',NULL,'4'),(6,'valid_mimetypes',NULL,'{\"default\":[\"image\\/jpeg\",\"image\\/gif\",\"image\\/png\",\"text\\/csv\",\"text\\/richtext\",\"application\\/rtf\",\"application\\/pdf\",\"application\\/zip\",\"application\\/msword\",\"application\\/vnd.ms-office\",\"application\\/vnd.ms-powerpoint\",\"application\\/vnd.ms-write\",\"application\\/vnd.ms-excel\",\"application\\/vnd.ms-access\",\"application\\/vnd.ms-project\",\"application\\/vnd.openxmlformats-officedocument.wordprocessingml.document\",\"application\\/vnd.openxmlformats-officedocument.wordprocessingml.template\",\"application\\/vnd.openxmlformats-officedocument.spreadsheetml.sheet\",\"application\\/vnd.openxmlformats-officedocument.spreadsheetml.template\",\"application\\/vnd.openxmlformats-officedocument.presentationml.presentation\",\"application\\/vnd.openxmlformats-officedocument.presentationml.slideshow\",\"application\\/vnd.openxmlformats-officedocument.presentationml.template\",\"application\\/vnd.openxmlformats-officedocument.presentationml.slide\",\"application\\/onenote\",\"application\\/vnd.apple.keynote\",\"application\\/vnd.apple.numbers\",\"application\\/vnd.apple.pages\"],\"lor\":[\"image\\/jpeg\",\"image\\/gif\",\"image\\/png\",\"text\\/csv\",\"text\\/richtext\",\"application\\/rtf\",\"application\\/pdf\",\"application\\/zip\",\"application\\/msword\",\"application\\/vnd.ms-office\",\"application\\/vnd.ms-powerpoint\",\"application\\/vnd.ms-write\",\"application\\/vnd.ms-excel\",\"application\\/vnd.ms-access\",\"application\\/vnd.ms-project\",\"application\\/vnd.openxmlformats-officedocument.wordprocessingml.document\",\"application\\/vnd.openxmlformats-officedocument.wordprocessingml.template\",\"application\\/vnd.openxmlformats-officedocument.spreadsheetml.sheet\",\"application\\/vnd.openxmlformats-officedocument.spreadsheetml.template\",\"application\\/vnd.openxmlformats-officedocument.presentationml.presentation\",\"application\\/vnd.openxmlformats-officedocument.presentationml.slideshow\",\"application\\/vnd.openxmlformats-officedocument.presentationml.template\",\"application\\/vnd.openxmlformats-officedocument.presentationml.slide\",\"application\\/onenote\",\"application\\/vnd.apple.keynote\",\"application\\/vnd.apple.numbers\",\"application\\/vnd.apple.pages\"]}'),(7,'lrs_endpoint',NULL,''),(8,'lrs_version',NULL,''),(9,'lrs_username',NULL,''),(10,'lrs_password',NULL,''),(24,'bookmarks_display_sidebar',NULL,'1'),(12,'flagging_notifications',1,'1'),(13,'twitter_consumer_key',NULL,''),(14,'twitter_consumer_secret',NULL,''),(15,'twitter_language',NULL,'en'),(16,'twitter_sort_order',NULL,'recent'),(17,'twitter_update_interval',NULL,'5'),(18,'caliper_endpoint',NULL,''),(19,'caliper_sensor_id',NULL,''),(20,'caliper_api_key',NULL,''),(21,'caliper_debug',NULL,'0'),(22,'prizm_doc_settings',NULL,'{\"url\" : \"\\/\\/api.accusoft.com\\/v1\\/viewer\\/\",\"key\" : \"b2GVmI5r7iL2zAKFZDww4HqCCmac5NRnFzgfDzco_xEIdZz3rbwrsX4o4-7lOF7L\",\"viewertype\" : \"html5\",\"viewerheight\" : \"600\",\"viewerwidth\" : \"100%\",\"upperToolbarColor\" : \"000000\",\"lowerToolbarColor\" : \"88909e\",\"bottomToolbarColor\" : \"000000\",\"backgroundColor\" : \"e4eaee\",\"fontColor\" : \"ffffff\",\"buttonColor\" : \"white\",\"hidden\" : \"esign,redact\"}'),(23,'podcast_display_sidebar',NULL,'1');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `statistics` (
  `statistic_id` int(12) NOT NULL AUTO_INCREMENT,
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `timestamp` bigint(64) NOT NULL DEFAULT '0',
  `module` varchar(64) NOT NULL DEFAULT 'undefined',
  `action` varchar(64) NOT NULL DEFAULT 'undefined',
  `action_field` varchar(64) DEFAULT NULL,
  `action_value` varchar(64) DEFAULT NULL,
  `prune_after` bigint(64) NOT NULL DEFAULT '0',
  PRIMARY KEY (`statistic_id`),
  KEY `proxy_id` (`proxy_id`,`timestamp`,`module`,`action`,`action_field`,`action_value`),
  KEY `proxy_id_2` (`proxy_id`),
  KEY `timestamp` (`timestamp`),
  KEY `module` (`module`,`action`,`action_field`,`action_value`),
  KEY `action` (`action`),
  KEY `action_field` (`action_field`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `statistics_archive` (
  `statistic_id` int(12) NOT NULL AUTO_INCREMENT,
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `timestamp` bigint(64) NOT NULL DEFAULT '0',
  `module` varchar(64) NOT NULL DEFAULT 'undefined',
  `action` varchar(64) NOT NULL DEFAULT 'undefined',
  `action_field` varchar(64) DEFAULT NULL,
  `action_value` varchar(64) DEFAULT NULL,
  `prune_after` bigint(64) NOT NULL DEFAULT '0',
  PRIMARY KEY (`statistic_id`),
  KEY `proxy_id` (`proxy_id`,`timestamp`,`module`,`action`,`action_field`,`action_value`),
  KEY `proxy_id_2` (`proxy_id`),
  KEY `timestamp` (`timestamp`),
  KEY `module` (`module`,`action`,`action_field`,`action_value`),
  KEY `action` (`action`),
  KEY `action_field` (`action_field`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_awards_external` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL DEFAULT '',
  `year` year(4) NOT NULL,
  `awarding_body` varchar(4096) NOT NULL,
  `award_terms` mediumtext NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `comment` varchar(4096) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_awards_internal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `award_id` int(11) NOT NULL,
  `year` year(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_awards_internal_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `award_terms` mediumtext NOT NULL,
  `title` varchar(200) NOT NULL DEFAULT '',
  `disabled` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `title_unique` (`title`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_clineval_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source` varchar(4096) NOT NULL,
  `comment` mediumtext NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_community_health_and_epidemiology` (
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `organization` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `supervisor` varchar(255) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `comment` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_contributions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role` varchar(4096) NOT NULL,
  `org_event` varchar(256) NOT NULL DEFAULT '',
  `date` varchar(256) NOT NULL DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL,
  `start_month` int(11) DEFAULT NULL,
  `start_year` int(11) DEFAULT NULL,
  `end_month` int(11) DEFAULT NULL,
  `end_year` int(11) DEFAULT NULL,
  `comment` varchar(4096) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_critical_enquiries` (
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `organization` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `supervisor` varchar(255) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `comment` varchar(4096) DEFAULT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_disciplinary_actions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action_details` mediumtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_formal_remediations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `remediation_details` mediumtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_international_activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `title` varchar(256) NOT NULL,
  `location` varchar(256) NOT NULL,
  `site` varchar(256) NOT NULL,
  `start` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `end` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_leaves_of_absence` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `absence_details` mediumtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_mspr` (
  `user_id` int(11) DEFAULT NULL,
  `last_update` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `generated` bigint(64) DEFAULT NULL,
  `closed` bigint(64) DEFAULT NULL,
  `carms_number` int(10) unsigned DEFAULT NULL,
  KEY `idx_user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_mspr_class` (
  `year` int(11) NOT NULL DEFAULT '0',
  `closed` int(11) DEFAULT NULL,
  PRIMARY KEY (`year`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_observerships` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `title` varchar(256) NOT NULL,
  `location` varchar(256) NOT NULL DEFAULT '',
  `city` varchar(32) DEFAULT NULL,
  `prov` varchar(32) DEFAULT NULL,
  `country` varchar(32) DEFAULT NULL,
  `postal_code` varchar(12) DEFAULT NULL,
  `address_l1` varchar(64) DEFAULT NULL,
  `address_l2` varchar(64) DEFAULT NULL,
  `observership_details` text,
  `activity_type` varchar(32) DEFAULT NULL,
  `clinical_discipline` varchar(32) DEFAULT NULL,
  `organisation` varchar(32) DEFAULT NULL,
  `order` int(3) DEFAULT '0',
  `reflection_id` int(11) DEFAULT NULL,
  `site` varchar(256) NOT NULL DEFAULT '',
  `start` int(11) NOT NULL,
  `end` int(11) DEFAULT NULL,
  `preceptor_prefix` varchar(4) DEFAULT NULL,
  `preceptor_firstname` varchar(256) DEFAULT NULL,
  `preceptor_lastname` varchar(256) DEFAULT NULL,
  `preceptor_proxy_id` int(12) unsigned DEFAULT NULL,
  `preceptor_email` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected','confirmed','denied') DEFAULT NULL,
  `unique_id` varchar(64) DEFAULT NULL,
  `notice_sent` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_research` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `citation` varchar(4096) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `priority` tinyint(4) NOT NULL DEFAULT '0',
  `comment` varchar(4096) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_student_run_electives` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_name` varchar(255) NOT NULL,
  `university` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  `start_month` tinyint(2) unsigned DEFAULT NULL,
  `start_year` smallint(4) unsigned DEFAULT NULL,
  `end_month` tinyint(2) unsigned DEFAULT NULL,
  `end_year` smallint(4) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_studentships` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(4096) NOT NULL,
  `year` year(4) NOT NULL DEFAULT '0000',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `topic_organisation` (
  `topic_id` int(12) NOT NULL,
  `organisation_id` int(12) NOT NULL,
  PRIMARY KEY (`topic_id`,`organisation_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tweets` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `tweets_handle` char(16) DEFAULT NULL,
  `tweets_hashtag` char(100) DEFAULT NULL,
  `tweets` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `tweets_handle` (`tweets_handle`,`tweets_hashtag`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users_online` (
  `session_id` varchar(32) NOT NULL,
  `ip_address` varchar(32) NOT NULL,
  `proxy_id` int(12) NOT NULL DEFAULT '0',
  `username` varchar(32) NOT NULL,
  `firstname` varchar(35) NOT NULL,
  `lastname` varchar(35) NOT NULL,
  `timestamp` bigint(64) NOT NULL DEFAULT '0',
  PRIMARY KEY (`session_id`),
  KEY `ip_address` (`ip_address`),
  KEY `proxy_id` (`proxy_id`),
  KEY `timestamp` (`timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

