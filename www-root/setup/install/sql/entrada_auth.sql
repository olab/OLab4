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
CREATE TABLE `acl_permissions` (
  `permission_id` int(12) NOT NULL AUTO_INCREMENT,
  `resource_type` varchar(64) DEFAULT NULL,
  `resource_value` int(12) DEFAULT NULL,
  `entity_type` varchar(64) DEFAULT NULL,
  `entity_value` varchar(64) DEFAULT NULL,
  `app_id` int(12) DEFAULT NULL,
  `create` tinyint(1) DEFAULT NULL,
  `read` tinyint(1) DEFAULT NULL,
  `update` tinyint(1) DEFAULT NULL,
  `delete` tinyint(1) DEFAULT NULL,
  `assertion` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`permission_id`),
  KEY `entity_type` (`entity_type`,`entity_value`)
) ENGINE=MyISAM AUTO_INCREMENT=198 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `acl_permissions` VALUES (1,'community',NULL,NULL,NULL,1,1,1,NULL,NULL,'NotGuest'),(2,'course',NULL,'group','student',1,NULL,0,NULL,NULL,'CourseEnrollment'),(3,'course',NULL,NULL,NULL,1,NULL,1,NULL,NULL,'ResourceOrganisation&NotGuest'),(4,'dashboard',NULL,NULL,NULL,1,NULL,1,NULL,NULL,'NotGuest'),(5,'discussion',NULL,NULL,NULL,1,NULL,1,NULL,NULL,'NotGuest'),(6,'library',NULL,NULL,NULL,1,NULL,1,NULL,NULL,'NotGuest'),(7,'people',NULL,NULL,NULL,1,NULL,1,NULL,NULL,'NotGuest'),(8,'podcast',NULL,NULL,NULL,1,NULL,1,NULL,NULL,'NotGuest'),(9,'profile',NULL,NULL,NULL,1,NULL,1,NULL,NULL,'NotGuest'),(10,'search',NULL,NULL,NULL,1,NULL,1,NULL,NULL,'NotGuest'),(11,'event',NULL,NULL,NULL,1,NULL,1,NULL,NULL,'ResourceOrganisation&NotGuest'),(12,'resourceorganisation',1,NULL,NULL,1,NULL,1,NULL,NULL,'NotGuest'),(13,'coursecontent',NULL,'role','pcoordinator',1,NULL,NULL,1,NULL,'CourseOwner'),(14,'evaluation',NULL,'group:role','staff:admin',1,1,1,1,1,'ResourceOrganisation'),(15,'evaluationform',NULL,'group:role','staff:admin',1,1,1,1,1,'ResourceOrganisation'),(16,'evaluationformquestion',NULL,'group:role','staff:admin',1,1,1,1,1,NULL),(17,'event',NULL,'role','pcoordinator',1,1,NULL,NULL,NULL,'CourseOwner'),(18,'event',NULL,'role','pcoordinator',1,NULL,NULL,1,1,'EventOwner'),(19,'event',NULL,NULL,NULL,1,NULL,1,NULL,NULL,'EventEnrollment&NotGuest'),(20,'event',NULL,'group','student',1,NULL,0,NULL,NULL,'NotEventEnrollment'),(21,'eventcontent',NULL,'role','pcoordinator',1,NULL,NULL,1,NULL,'EventOwner'),(22,'coursecontent',NULL,'role','director',1,NULL,NULL,1,NULL,'CourseOwner'),(23,'coursecontent',NULL,'role','lecturer',1,NULL,NULL,1,NULL,'CourseOwner'),(24,'eventcontent',NULL,'role','lecturer',1,NULL,NULL,1,NULL,'EventOwner'),(25,'eventcontent',NULL,'role','director',1,NULL,NULL,1,NULL,'EventOwner'),(26,'eventcontent',NULL,'group:role','faculty:admin',1,NULL,NULL,1,NULL,'EventOwner'),(27,NULL,NULL,'group:role','medtech:admin',1,1,1,1,1,NULL),(28,'notice',NULL,'group:role','faculty:director',1,1,NULL,1,1,'ResourceOrganisation'),(29,'notice',NULL,'group:role','faculty:admin',1,1,NULL,1,1,'ResourceOrganisation'),(30,'notice',NULL,'group:role','staff:admin',1,1,NULL,1,1,'ResourceOrganisation'),(31,'notice',NULL,'group:role','staff:pcoordinator',1,1,NULL,1,1,'ResourceOrganisation'),(32,'resourceorganisation',1,'organisation:group:role','1:faculty:director',1,1,NULL,NULL,NULL,NULL),(33,'resourceorganisation',1,'organisation:group:role','1:faculty:admin',1,1,NULL,NULL,NULL,NULL),(34,'resourceorganisation',1,'organisation:group:role','1:staff:admin',1,1,NULL,NULL,NULL,NULL),(35,'resourceorganisation',1,'organisation:group:role','1:staff:pcoordinator',1,1,NULL,NULL,NULL,NULL),(36,'resourceorganisation',NULL,NULL,NULL,1,NULL,NULL,0,NULL,NULL),(136,'poll',NULL,'group:role','faculty:admin',1,1,NULL,1,1,NULL),(38,'poll',NULL,'role','pcoordinator',1,1,NULL,1,1,NULL),(39,'quiz',NULL,'group:role','faculty:director',1,NULL,NULL,1,1,'QuizOwner'),(40,'firstlogin',NULL,NULL,NULL,1,NULL,1,NULL,NULL,NULL),(41,'community',NULL,NULL,NULL,1,NULL,NULL,1,1,'CommunityOwner'),(42,'quiz',NULL,'group:role','faculty:admin',1,NULL,NULL,1,1,'QuizOwner'),(43,'quiz',NULL,'group:role','faculty:lecturer',1,NULL,NULL,1,1,'QuizOwner'),(44,'quiz',NULL,'group:role','resident:lecturer',1,NULL,NULL,1,1,'QuizOwner'),(45,'quiz',NULL,'group:role','staff:admin',1,NULL,NULL,1,1,'QuizOwner'),(46,'quiz',NULL,'group:role','staff:pcoordinator',1,NULL,NULL,1,1,'QuizOwner'),(47,NULL,NULL,'group:role','guest:communityinvite',1,0,0,0,0,NULL),(48,'clerkship',NULL,'group','student',1,NULL,1,NULL,NULL,'Clerkship'),(49,'clerkship',NULL,'group','staff',1,NULL,1,NULL,NULL,NULL),(50,'clerkship',NULL,'group','faculty',1,NULL,1,NULL,NULL,NULL),(51,NULL,NULL,'group:role','staff:admin',1,1,1,1,1,'ResourceOrganisation'),(52,'resourceorganisation',1,'organisation:group:role','1:staff:admin',1,1,1,1,1,NULL),(53,'clerkship',NULL,'group:role','staff:admin',1,1,1,1,1,NULL),(54,'clerkship',NULL,'group:role','faculty:clerkship',1,1,1,1,1,NULL),(55,'quiz',NULL,NULL,NULL,1,NULL,1,NULL,NULL,'NotGuest'),(56,'quiz',NULL,'group','faculty',1,1,NULL,NULL,NULL,NULL),(57,'quiz',NULL,'group','staff',1,1,NULL,NULL,NULL,NULL),(58,'quiz',NULL,'group:role','resident:lecturer',1,1,NULL,NULL,NULL,NULL),(59,'photo',NULL,NULL,NULL,1,NULL,1,NULL,NULL,'Photo'),(60,'photo',NULL,'group','faculty',1,NULL,1,NULL,NULL,NULL),(61,'photo',NULL,'group','staff',1,NULL,1,NULL,NULL,NULL),(62,'clerkshipschedules',NULL,'group','faculty',1,NULL,1,NULL,NULL,NULL),(63,'clerkshipschedules',NULL,'group','staff',1,NULL,1,NULL,NULL,NULL),(64,'reportindex',NULL,'organisation:group:role','1:staff:admin',1,NULL,1,NULL,NULL,NULL),(65,'report',NULL,'organisation:group:role','1:staff:admin',1,NULL,1,NULL,NULL,NULL),(66,'assistant_support',NULL,'group:role','faculty:director',1,1,1,1,1,NULL),(67,'assistant_support',NULL,'group:role','faculty:clerkship',1,1,1,1,1,NULL),(68,'assistant_support',NULL,'group:role','faculty:admin',1,1,1,1,1,NULL),(69,'assistant_support',NULL,'group:role','faculty:lecturer',1,1,1,1,1,NULL),(70,'assistant_support',NULL,'group:role','staff:admin',1,1,1,1,1,NULL),(71,'assistant_support',NULL,'group:role','staff:pcoordinator',1,1,1,1,1,NULL),(72,'lottery',NULL,'group','student',1,NULL,1,NULL,NULL,'ClerkshipLottery'),(73,'lottery',NULL,'group:role','staff:admin',1,NULL,1,NULL,NULL,NULL),(74,'lottery',NULL,'group:role','faculty:director',1,NULL,1,NULL,NULL,NULL),(75,'logbook',NULL,'group:role','staff:pcoordinator',1,NULL,1,1,NULL,NULL),(76,'annualreport',NULL,'group','faculty',1,1,1,1,1,NULL),(77,'gradebook',NULL,'role','pcoordinator',1,NULL,1,NULL,NULL,'GradebookOwner'),(78,'gradebook',NULL,'group:role','faculty:admin',1,NULL,1,NULL,NULL,'GradebookOwner'),(79,'gradebook',NULL,'group:role','faculty:director',1,NULL,1,NULL,NULL,'GradebookOwner'),(80,'dashboard',NULL,NULL,NULL,1,NULL,NULL,1,NULL,'NotGuest'),(81,'regionaled',NULL,'group','resident',1,NULL,1,NULL,NULL,'HasAccommodations'),(82,'regionaled',NULL,'group','student',1,NULL,1,NULL,NULL,'HasAccommodations'),(83,'regionaled_tab',NULL,'group','resident',1,NULL,1,NULL,NULL,'HasAccommodations'),(84,'awards',NULL,'group:role','staff:admin',1,1,1,1,1,NULL),(85,'mspr',NULL,'group:role','staff:admin',1,1,1,1,1,NULL),(86,'mspr',NULL,'group','student',1,NULL,1,1,NULL,NULL),(87,'user',NULL,'group:role','staff:admin',1,1,1,1,1,NULL),(88,'incident',NULL,'group:role','staff:admin',1,1,1,1,1,NULL),(89,'task',NULL,'group:role','staff:admin',1,1,1,1,1,'ResourceOrganisation'),(90,'task',NULL,'group:role','faculty:director',1,NULL,1,1,1,'TaskOwner'),(91,'task',NULL,NULL,NULL,1,NULL,1,NULL,NULL,'TaskRecipient'),(92,'task',NULL,'role','pcoordinator',1,NULL,1,1,1,'TaskOwner'),(93,'task',NULL,'group:role','faculty:director',1,1,NULL,NULL,NULL,'CourseOwner'),(94,'task',NULL,'role','pcoordinator',1,1,NULL,NULL,NULL,'CourseOwner'),(95,'taskverification',NULL,NULL,NULL,1,NULL,NULL,1,NULL,'TaskVerifier'),(96,'task',NULL,NULL,NULL,1,NULL,1,NULL,NULL,'TaskVerifier'),(97,'tasktab',NULL,NULL,NULL,1,NULL,1,NULL,NULL,'ShowTaskTab'),(98,'mydepartment',NULL,'group','faculty',1,1,1,1,1,'DepartmentHead'),(99,'myowndepartment',NULL,'user','1',1,1,1,1,1,NULL),(100,'annualreportadmin',NULL,'group:role','medtech:admin',1,1,1,1,1,NULL),(101,'gradebook',NULL,'group','student',1,NULL,1,NULL,NULL,NULL),(102,'metadata',NULL,'group:role','staff:admin',1,1,1,1,1,NULL),(103,'evaluation',NULL,'group','faculty',1,NULL,1,NULL,NULL,'IsEvaluated'),(104,'evaluation',NULL,'group','faculty',1,NULL,1,NULL,NULL,'EvaluationReviewer'),(105,'evaluationform',NULL,'group','faculty',1,1,1,1,NULL,'EvaluationFormAuthor&ResourceOrganisation'),(106,'evaluationquestion',NULL,'group','faculty',1,1,1,1,NULL,'ResourceOrganisation'),(107,'evaluationquestion',NULL,'group:role','staff:admin',1,1,1,1,1,'ResourceOrganisation'),(108,'encounter_tracking',NULL,'group','student',NULL,NULL,1,NULL,NULL,'LoggableFound'),(109,'encounter_tracking',NULL,'role','admin',NULL,NULL,0,NULL,NULL,NULL),(110,'coursecontent',NULL,'group:role','staff:admin',NULL,NULL,0,NULL,NULL,'NotCourseOwner'),(111,'coursecontent',NULL,'group','faculty',NULL,NULL,0,NULL,NULL,'NotCourseOwner'),(112,'gradebook',NULL,'group','faculty',NULL,NULL,1,1,NULL,'GradebookDropbox'),(113,'gradebook',NULL,'group:role','staff:admin',NULL,NULL,1,1,NULL,'GradebookDropbox'),(114,'assignment',NULL,'group','faculty',NULL,NULL,1,1,NULL,'AssignmentContact'),(115,'assessment',NULL,'group','faculty',NULL,NULL,NULL,1,NULL,'AssessmentContact'),(116,'assignment',NULL,'group:role','staff:admin',NULL,NULL,1,1,NULL,'AssignmentContact'),(117,'assessment',NULL,'group:role','staff:admin',NULL,NULL,NULL,1,NULL,'AssessmentContact'),(118,'eportfolio',NULL,'group:role','medtech:admin',1,1,1,1,1,'EportfolioOwner'),(119,'eportfolio',NULL,'group','student',1,NULL,1,NULL,NULL,'EportfolioOwner'),(120,'eportfolio',NULL,'group','resident',1,NULL,1,NULL,NULL,'EportfolioOwner'),(121,'eportfolio',NULL,'group','alumni',1,NULL,1,NULL,NULL,'EportfolioOwner'),(122,'eportfolio',NULL,'group','faculty',1,NULL,1,NULL,NULL,'EportfolioOwner'),(123,'eportfolio-review',NULL,'group:role','medtech:admin',1,1,1,1,NULL,'EportfolioArtifactReviewer'),(124,'eportfolio-artifact-entry',NULL,'group','student',1,1,1,1,1,'EportfolioArtifactEntryOwner'),(125,'eportfolio-review',NULL,'group','faculty',1,1,1,1,1,NULL),(126,'eportfolio-mentor-view',NULL,'group','faculty',1,1,1,1,1,NULL),(127,'eportfolio-artifact-entry',NULL,'group','student',1,1,1,NULL,NULL,'EportfolioArtifactSharePermitted'),(128,'eportfolio-manage',NULL,'group:role','medtech:admin',1,1,1,NULL,NULL,NULL),(129,'eportfolio-artifact-entry',NULL,'group','faculty',1,1,1,NULL,NULL,NULL),(130,'eportfolio-review-interface',NULL,'group','faculty',1,1,1,1,1,NULL),(131,'masquerade',NULL,'group:role','medtech:admin',1,1,1,1,1,NULL),(134,'observerships',NULL,'group:role','faculty:admin',1,1,1,1,1,NULL),(133,'observerships',NULL,'role','student',1,1,1,1,0,NULL),(135,'observerships',NULL,'group:role','staff:admin',1,1,1,1,1,NULL),(137,'poll',NULL,'group:role','staff:admin',1,1,NULL,1,1,NULL),(138,'assessmentcomponent',NULL,'role','admin',NULL,1,1,1,1,'AssessmentComponent'),(139,'assessmentcomponent',NULL,'group:role','staff:pcoordinator',NULL,1,1,1,1,'AssessmentComponent'),(140,'assessments',NULL,'group:role','staff:pcoordinator',NULL,1,1,1,1,NULL),(141,'assessments',NULL,'group:role','staff:admin',NULL,1,1,1,1,NULL),(142,'assessor',NULL,NULL,NULL,1,1,1,1,NULL,'Assessor'),(143,'assessmentprogress',NULL,NULL,NULL,1,1,1,1,1,'AssessmentProgress'),(144,'assessmentresult',NULL,NULL,NULL,1,NULL,1,NULL,NULL,'AssessmentResult'),(145,'academicadvisor',NULL,'group','faculty',1,NULL,1,NULL,NULL,'AcademicAdvisor'),(146,'academicadvisor',NULL,'group:role','staff:pcoordinator',1,NULL,1,NULL,NULL,'AcademicAdvisor'),(147,'gradebook',NULL,'group','student',1,1,1,1,1,'GradebookTA'),(148,'exam',NULL,'group:role','faculty:director',1,NULL,NULL,1,1,'ExamOwner'),(149,'exam',NULL,'group:role','faculty:admin',1,NULL,NULL,1,1,'ExamOwner'),(150,'exam',NULL,'group:role','staff:pcoordinator',1,NULL,NULL,1,1,'ExamOwner'),(151,'exam',NULL,NULL,NULL,1,NULL,1,NULL,NULL,'NotGuest'),(152,'exam',NULL,'group','staff',1,NULL,1,NULL,NULL,NULL),(153,'exam',NULL,'group','faculty',1,NULL,1,NULL,NULL,NULL),(154,'exam',NULL,'group:role','staff:admin',1,1,1,NULL,NULL,NULL),(155,'exam',NULL,'group:role','staff:pcoordinator',1,1,1,NULL,NULL,NULL),(156,'exam',NULL,'group:role','faculty:admin',1,1,1,NULL,NULL,NULL),(157,'exam',NULL,'group:role','faculty:director',1,1,1,NULL,NULL,NULL),(158,'exam',NULL,'group:role','staff:admin',1,NULL,NULL,1,1,'ExamOwner'),(159,'examdashboard',NULL,'group','staff',1,NULL,1,NULL,NULL,NULL),(160,'examdashboard',NULL,'group','faculty',1,NULL,1,NULL,NULL,NULL),(161,'examfolder',NULL,'group:role','staff:admin',1,NULL,NULL,1,1,'ExamFolderOwner'),(162,'examfolder',NULL,NULL,NULL,1,NULL,1,NULL,NULL,'NotGuest'),(163,'examfolder',NULL,'group:role','faculty:admin',1,NULL,NULL,1,1,'ExamFolderOwner'),(164,'examfolder',NULL,'group:role','staff:pcoordinator',1,NULL,NULL,1,1,'ExamFolderOwner'),(165,'examfolder',NULL,'group:role','faculty:director',1,NULL,NULL,1,1,'ExamFolderOwner'),(166,'examfolder',NULL,'group','staff',1,NULL,1,NULL,NULL,NULL),(167,'examfolder',NULL,'group','faculty',1,NULL,1,NULL,NULL,NULL),(168,'examgradefnb',NULL,'group:role','faculty:director',1,1,1,1,1,NULL),(169,'examgradefnb',NULL,'group:role','staff:pcoordinator',1,1,1,1,1,NULL),(170,'examgradefnb',NULL,'group:role','staff:admin',1,1,1,1,1,NULL),(171,'examquestion',NULL,'group:role','medtech:admin',1,1,1,1,1,NULL),(172,'examquestion',NULL,'group:role','staff:pcoordinator',1,NULL,NULL,1,1,'ExamQuestionOwner'),(173,'examquestion',NULL,'group:role','staff:admin',1,NULL,NULL,1,1,'ExamQuestionOwner'),(174,'examquestion',NULL,'group:role','staff:admin',1,1,1,NULL,NULL,NULL),(175,'examquestion',NULL,'group:role','staff:pcoordinator',1,1,1,NULL,NULL,NULL),(176,'examquestion',NULL,'group:role','faculty:admin',1,1,1,NULL,NULL,NULL),(177,'examquestion',NULL,'group:role','faculty:director',1,1,1,NULL,NULL,NULL),(178,'examquestion',NULL,'group:role','faculty:admin',1,NULL,NULL,1,1,'ExamQuestionOwner'),(179,'examquestion',NULL,'group','faculty',1,NULL,1,NULL,NULL,NULL),(180,'examquestion',NULL,'group:role','faculty:director',1,NULL,NULL,1,1,'ExamQuestionOwner'),(181,'examquestion',NULL,NULL,NULL,1,NULL,1,NULL,NULL,'NotGuest'),(182,'examquestion',NULL,'group','staff',1,NULL,1,NULL,NULL,NULL),(183,'examquestiongroup',NULL,'group:role','faculty:director',1,NULL,NULL,1,1,'ExamQuestionGroupOwner'),(184,'examquestiongroup',NULL,'group:role','faculty:admin',1,NULL,NULL,1,1,'ExamQuestionGroupOwner'),(185,'examquestiongroup',NULL,'group:role','staff:pcoordinator',1,NULL,NULL,1,1,'ExamQuestionGroupOwner'),(186,'examquestiongroup',NULL,'group:role','staff:admin',1,NULL,NULL,1,1,'ExamQuestionGroupOwner'),(187,'examquestiongroup',NULL,'group:role','faculty:director',1,1,1,NULL,NULL,NULL),(188,'examquestiongroup',NULL,'group:role','faculty:admin',1,1,1,NULL,NULL,NULL),(189,'examquestiongroup',NULL,'group:role','staff:pcoordinator',1,1,1,NULL,NULL,NULL),(190,'examquestiongroup',NULL,'group:role','staff:admin',1,1,1,NULL,NULL,NULL),(191,'examquestiongroupindex',NULL,NULL,NULL,1,NULL,0,NULL,NULL,NULL),(192,'examquestiongroupindex',NULL,'role','admin',1,NULL,1,NULL,NULL,NULL),(193,'secure',NULL,'group','faculty',1,NULL,1,NULL,NULL,NULL),(194,'assessments',NULL,'group','student',1,NULL,1,NULL,NULL,NULL),(195,'assessments',NULL,'group','resident',1,NULL,1,NULL,NULL,NULL),(196,'assessments',NULL,'group','staff',1,NULL,1,NULL,NULL,NULL),(197,'assessments',NULL,'group','faculty',1,NULL,1,NULL,NULL,NULL);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `department_heads` (
  `department_heads_id` int(11) NOT NULL AUTO_INCREMENT,
  `department_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`department_heads_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `departments` (
  `department_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `organisation_id` int(12) unsigned NOT NULL DEFAULT '1',
  `entity_id` int(12) unsigned NOT NULL DEFAULT '0',
  `parent_id` int(12) NOT NULL DEFAULT '0',
  `department_title` varchar(128) NOT NULL DEFAULT '',
  `department_address1` varchar(128) NOT NULL DEFAULT '',
  `department_address2` varchar(128) NOT NULL DEFAULT '',
  `department_city` varchar(64) NOT NULL DEFAULT 'Kingston',
  `department_province` varchar(64) NOT NULL DEFAULT 'ON',
  `province_id` int(12) NOT NULL DEFAULT '9',
  `department_country` varchar(64) NOT NULL DEFAULT 'CA',
  `country_id` int(12) NOT NULL DEFAULT '39',
  `department_postcode` varchar(16) NOT NULL DEFAULT '',
  `department_telephone` varchar(32) NOT NULL DEFAULT '',
  `department_fax` varchar(32) NOT NULL DEFAULT '',
  `department_email` varchar(128) NOT NULL DEFAULT '',
  `department_url` text NOT NULL,
  `department_desc` text,
  `department_active` int(1) NOT NULL DEFAULT '1',
  `updated_date` bigint(64) unsigned NOT NULL,
  `updated_by` int(12) unsigned NOT NULL,
  `department_code` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`department_id`),
  UNIQUE KEY `organisation_id` (`organisation_id`,`entity_id`,`department_title`),
  KEY `department_active` (`department_active`),
  KEY `parent_id` (`parent_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `departments` VALUES (1,1,5,0,'Medical IT','','','Kingston','ON',9,'CA',39,'','','','','',NULL,1,0,0,NULL);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `entity_type` (
  `entity_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `entity_title` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`entity_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `entity_type` VALUES (1,'Faculty'),(2,'School'),(3,'Department'),(4,'Division'),(5,'Unit');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `location_ipranges` (
  `iprange_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `location_id` int(12) unsigned NOT NULL DEFAULT '0',
  `block_start` varchar(32) NOT NULL DEFAULT '0',
  `block_end` varchar(32) NOT NULL DEFAULT '0',
  PRIMARY KEY (`iprange_id`),
  KEY `location_id` (`location_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `locations` (
  `location_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `organisation_id` int(12) unsigned NOT NULL DEFAULT '1',
  `department_id` int(12) unsigned NOT NULL DEFAULT '0',
  `location_title` varchar(128) NOT NULL DEFAULT '',
  `location_address1` varchar(128) NOT NULL DEFAULT '',
  `location_address2` varchar(128) NOT NULL DEFAULT '',
  `location_city` varchar(64) NOT NULL DEFAULT 'Kingston',
  `location_province` char(2) NOT NULL DEFAULT 'ON',
  `location_country` char(2) NOT NULL DEFAULT 'CA',
  `location_postcode` varchar(7) NOT NULL DEFAULT '',
  `location_telephone` varchar(32) NOT NULL DEFAULT '',
  `location_fax` varchar(32) NOT NULL DEFAULT '',
  `location_email` varchar(128) NOT NULL DEFAULT '',
  `location_url` text NOT NULL,
  `location_longitude` varchar(12) DEFAULT NULL,
  `location_latitude` varchar(12) DEFAULT NULL,
  `location_desc` text,
  PRIMARY KEY (`location_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `organisations` (
  `organisation_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `organisation_title` varchar(128) NOT NULL DEFAULT '',
  `organisation_address1` varchar(128) NOT NULL DEFAULT '',
  `organisation_address2` varchar(128) NOT NULL DEFAULT '',
  `organisation_city` varchar(64) NOT NULL DEFAULT 'Kingston',
  `organisation_province` varchar(64) NOT NULL DEFAULT 'ON',
  `organisation_country` varchar(64) NOT NULL DEFAULT 'CA',
  `organisation_postcode` varchar(16) NOT NULL DEFAULT '',
  `organisation_telephone` varchar(32) NOT NULL DEFAULT '',
  `organisation_fax` varchar(32) NOT NULL DEFAULT '',
  `organisation_email` varchar(128) NOT NULL DEFAULT '',
  `organisation_url` text NOT NULL,
  `organisation_twitter` varchar(16) DEFAULT NULL,
  `organisation_hashtags` text,
  `organisation_desc` text,
  `template` varchar(32) NOT NULL DEFAULT 'default',
  `aamc_institution_id` varchar(32) DEFAULT NULL,
  `aamc_institution_name` varchar(255) DEFAULT NULL,
  `aamc_program_id` varchar(32) DEFAULT NULL,
  `aamc_program_name` varchar(255) DEFAULT NULL,
  `organisation_active` tinyint(1) NOT NULL DEFAULT '1',
  `app_id` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`organisation_id`),
  KEY `organisation_active` (`organisation_active`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `organisations` VALUES (1,'Your University','University Avenue','','Kingston','ON','CA','K7L3N6','613-533-2000','','','http://www.yourschool.ca',NULL,NULL,NULL,'default',NULL,NULL,NULL,NULL,1,1);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset` (
  `id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `ip` varchar(24) NOT NULL DEFAULT '',
  `date` bigint(64) NOT NULL DEFAULT '0',
  `user_id` int(12) NOT NULL DEFAULT '0',
  `hash` varchar(64) NOT NULL DEFAULT '',
  `complete` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `hash` (`hash`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `registered_apps` (
  `id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `script_id` varchar(32) NOT NULL DEFAULT '0',
  `script_password` varchar(32) NOT NULL DEFAULT '',
  `server_ip` varchar(75) NOT NULL DEFAULT '',
  `server_url` text NOT NULL,
  `employee_rep` int(12) unsigned NOT NULL DEFAULT '0',
  `notes` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `script_id` (`script_id`),
  KEY `script_password` (`script_password`),
  KEY `server_ip` (`server_ip`),
  KEY `employee_rep` (`employee_rep`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `registered_apps` VALUES (1,'%AUTH_USERNAME%',MD5('%AUTH_PASSWORD%'),'%','%',1,'Entrada');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `sesskey` varchar(64) NOT NULL DEFAULT '',
  `expiry` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `expireref` varchar(250) DEFAULT '',
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `sessdata` longtext,
  PRIMARY KEY (`sesskey`),
  KEY `sess2_expiry` (`expiry`),
  KEY `sess2_expireref` (`expireref`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `statistics` (
  `statistic_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `proxy_id` int(12) unsigned NOT NULL DEFAULT '0',
  `app_id` int(12) unsigned NOT NULL DEFAULT '0',
  `role` varchar(32) NOT NULL DEFAULT '',
  `group` varchar(32) NOT NULL DEFAULT '',
  `timestamp` bigint(64) NOT NULL DEFAULT '0',
  `prune_after` bigint(64) NOT NULL DEFAULT '0',
  PRIMARY KEY (`statistic_id`),
  KEY `proxy_id` (`proxy_id`,`app_id`,`role`,`group`,`timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_group_organisation` (
  `groups_id` int(11) NOT NULL,
  `organisation_id` int(12) unsigned NOT NULL,
  PRIMARY KEY (`groups_id`,`organisation_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `system_group_organisation` VALUES (1,1),(2,1),(3,1),(4,1),(5,1),(6,1),(7,1);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_name` varchar(45) NOT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `system_groups` VALUES (1,'student',1),(2,'alumni',1),(3,'faculty',1),(4,'resident',1),(5,'staff',1),(6,'medtech',1),(7,'guest',0);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(45) NOT NULL,
  `groups_id` int(11) NOT NULL,
  PRIMARY KEY (`id`,`groups_id`)
) ENGINE=MyISAM AUTO_INCREMENT=31 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `system_roles` VALUES (1,(YEAR(CURRENT_DATE())+4),1),(2,(YEAR(CURRENT_DATE())+3),1),(3,(YEAR(CURRENT_DATE())+2),1),(4,(YEAR(CURRENT_DATE())+1),1),(5,(YEAR(CURRENT_DATE())),1),(6,(YEAR(CURRENT_DATE())-1),1),(7,(YEAR(CURRENT_DATE())-2),1),(8,(YEAR(CURRENT_DATE())-3),1),(9,(YEAR(CURRENT_DATE())-4),1),(10,(YEAR(CURRENT_DATE())+4),2),(11,(YEAR(CURRENT_DATE())+3),2),(12,(YEAR(CURRENT_DATE())+2),2),(13,(YEAR(CURRENT_DATE())+1),2),(14,(YEAR(CURRENT_DATE())),2),(15,(YEAR(CURRENT_DATE())-1),2),(16,(YEAR(CURRENT_DATE())-2),2),(17,(YEAR(CURRENT_DATE())-3),2),(18,(YEAR(CURRENT_DATE())-4),2),(19,'faculty',3),(20,'lecturer',3),(21,'director',3),(22,'admin',3),(23,'resident',4),(24,'lecturer',4),(25,'staff',5),(26,'pcoordinator',5),(27,'admin',5),(28,'staff',6),(29,'admin',6),(30,'communityinvite',7);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_access` (
  `id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(12) unsigned NOT NULL DEFAULT '0',
  `app_id` int(12) unsigned NOT NULL DEFAULT '0',
  `organisation_id` int(12) unsigned NOT NULL DEFAULT '0',
  `account_active` enum('true','false') NOT NULL DEFAULT 'true',
  `access_starts` bigint(64) NOT NULL DEFAULT '0',
  `access_expires` bigint(64) NOT NULL DEFAULT '0',
  `last_login` bigint(64) NOT NULL DEFAULT '0',
  `last_ip` varchar(75) NOT NULL DEFAULT '',
  `login_attempts` int(11) DEFAULT NULL,
  `locked_out_until` bigint(64) DEFAULT NULL,
  `role` varchar(35) NOT NULL DEFAULT '',
  `group` varchar(35) NOT NULL DEFAULT '',
  `extras` longtext NOT NULL,
  `private_hash` varchar(32) DEFAULT NULL,
  `notes` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `private_hash` (`private_hash`),
  KEY `user_id` (`user_id`),
  KEY `app_id` (`app_id`),
  KEY `account_active` (`account_active`),
  KEY `access_starts` (`access_starts`),
  KEY `access_expires` (`access_expires`),
  KEY `role` (`role`),
  KEY `group` (`group`),
  KEY `user_app_id` (`user_id`,`app_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `user_access` VALUES (1,1,1,1,'true',1216149930,0,0,'',NULL,NULL,'admin','medtech','YToxOntzOjE2OiJhbGxvd19wb2RjYXN0aW5nIjtzOjM6ImFsbCI7fQ==','fbbeb05c0bdeb9fe489765034ae76b58','');

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_data` (
  `id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `number` int(12) unsigned NOT NULL DEFAULT '0',
  `username` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL DEFAULT '',
  `salt` varchar(64) DEFAULT NULL,
  `organisation_id` int(12) NOT NULL DEFAULT '1',
  `department` varchar(255) DEFAULT NULL,
  `prefix` varchar(10) NOT NULL DEFAULT '',
  `firstname` varchar(35) NOT NULL DEFAULT '',
  `lastname` varchar(35) NOT NULL DEFAULT '',
  `date_of_birth` bigint(64) DEFAULT NULL,
  `email` varchar(255) NOT NULL DEFAULT '',
  `email_alt` varchar(255) NOT NULL DEFAULT '',
  `email_updated` bigint(64) DEFAULT NULL,
  `google_id` varchar(128) DEFAULT NULL,
  `telephone` varchar(25) NOT NULL DEFAULT '',
  `fax` varchar(25) NOT NULL DEFAULT '',
  `address` varchar(255) NOT NULL DEFAULT '',
  `city` varchar(35) NOT NULL DEFAULT '',
  `province` varchar(35) NOT NULL DEFAULT '',
  `postcode` varchar(12) NOT NULL DEFAULT '',
  `country` varchar(35) NOT NULL DEFAULT '',
  `country_id` int(12) DEFAULT NULL,
  `province_id` int(12) DEFAULT NULL,
  `notes` text NOT NULL,
  `office_hours` text,
  `privacy_level` int(1) DEFAULT '0',
  `copyright` bigint(64) NOT NULL DEFAULT '0',
  `notifications` int(1) NOT NULL DEFAULT '1',
  `entry_year` int(11) DEFAULT NULL,
  `grad_year` int(11) DEFAULT NULL,
  `gender` int(1) NOT NULL DEFAULT '0',
  `clinical` int(1) NOT NULL DEFAULT '0',
  `uuid` varchar(36) DEFAULT NULL,
  `created_date` bigint(64) NOT NULL,
  `created_by` int(11) NOT NULL,
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `number` (`number`),
  KEY `password` (`password`),
  KEY `firstname` (`firstname`),
  KEY `lastname` (`lastname`),
  KEY `privacy_level` (`privacy_level`),
  KEY `google_id` (`google_id`),
  KEY `clinical` (`clinical`),
  KEY `organisation_id` (`organisation_id`),
  KEY `gender` (`gender`),
  KEY `country_id` (`country_id`),
  KEY `province_id` (`province_id`),
  KEY `idx_uuid` (`uuid`),
  FULLTEXT KEY `firstname_2` (`firstname`,`lastname`,`email`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `user_data` VALUES (1,0,'%ADMIN_USERNAME%','%ADMIN_PASSWORD_HASH%','%ADMIN_PASSWORD_SALT%',1,NULL,'','%ADMIN_FIRSTNAME%','%ADMIN_LASTNAME%',NULL,'%ADMIN_EMAIL%','',NULL,NULL,'','','','','','','',NULL,NULL,'System Administrator',NULL,0,0,1,NULL,NULL,0,1,UUID(),UNIX_TIMESTAMP(),1,0,0);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_data_resident` (
  `proxy_id` int(12) NOT NULL,
  `cmpa_no` int(11) NOT NULL,
  `cpso_no` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `student_no` int(11) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `category_id` int(11) NOT NULL,
  `assess_prog_img` varchar(1) NOT NULL,
  `assess_prog_non_img` varchar(1) NOT NULL,
  PRIMARY KEY (`proxy_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_departments` (
  `udep_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(12) unsigned NOT NULL DEFAULT '0',
  `dep_id` int(12) unsigned NOT NULL DEFAULT '0',
  `dep_title` varchar(255) NOT NULL DEFAULT '',
  `entrada_only` int(1) DEFAULT '0',
  PRIMARY KEY (`udep_id`),
  KEY `user_id` (`user_id`),
  KEY `dep_id` (`dep_id`),
  KEY `dep_title` (`dep_title`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

INSERT INTO `user_departments` VALUES (1,1,1,'System Administrator',0);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_incidents` (
  `incident_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `proxy_id` int(12) unsigned NOT NULL DEFAULT '0',
  `incident_title` text NOT NULL,
  `incident_description` text,
  `incident_severity` tinyint(1) NOT NULL DEFAULT '1',
  `incident_status` tinyint(1) NOT NULL DEFAULT '1',
  `incident_author_id` int(12) NOT NULL DEFAULT '0',
  `incident_date` bigint(64) NOT NULL DEFAULT '0',
  `follow_up_date` bigint(64) DEFAULT NULL,
  PRIMARY KEY (`incident_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_mobile_data` (
  `id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `proxy_id` int(12) unsigned NOT NULL DEFAULT '0',
  `hash` varchar(64) DEFAULT NULL,
  `hash_expires` bigint(64) NOT NULL DEFAULT '0',
  `push_notifications` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int(12) unsigned NOT NULL DEFAULT '1',
  `created_date` bigint(64) NOT NULL DEFAULT '0',
  `updated_by` int(12) NOT NULL DEFAULT '1',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  `deleted_date` bigint(64) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_photos` (
  `photo_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `proxy_id` int(12) unsigned NOT NULL DEFAULT '0',
  `photo_mimetype` varchar(64) NOT NULL,
  `photo_filesize` int(32) NOT NULL DEFAULT '0',
  `photo_active` int(1) NOT NULL DEFAULT '1',
  `photo_type` int(1) NOT NULL DEFAULT '0',
  `updated_date` bigint(64) NOT NULL DEFAULT '0',
  PRIMARY KEY (`photo_id`),
  KEY `photo_active` (`photo_active`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_preferences` (
  `preference_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `app_id` int(12) unsigned NOT NULL DEFAULT '0',
  `proxy_id` int(12) unsigned NOT NULL DEFAULT '0',
  `module` varchar(32) NOT NULL DEFAULT '',
  `preferences` text NOT NULL,
  `updated` bigint(64) NOT NULL DEFAULT '0',
  PRIMARY KEY (`preference_id`),
  KEY `app_id` (`app_id`,`proxy_id`,`module`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_relations` (
  `relation_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `from` int(10) unsigned NOT NULL,
  `to` int(10) unsigned NOT NULL,
  `type` smallint(5) unsigned NOT NULL,
  PRIMARY KEY (`relation_id`),
  UNIQUE KEY `relation_unique` (`from`,`to`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

