-- MySQL dump 10.13  Distrib 8.0.17, for Win64 (x86_64)
--
-- Host: olab4.localhost    Database: openlabyrinth
-- ------------------------------------------------------
-- Server version	5.5.60-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `FileObjectsView`
--

DROP TABLE IF EXISTS `FileObjectsView`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `FileObjectsView` (
  `id` tinyint(4) NOT NULL,
  `name` tinyint(4) NOT NULL,
  `description` tinyint(4) NOT NULL,
  `path` tinyint(4) NOT NULL,
  `imageable_id` tinyint(4) NOT NULL,
  `imageable_type` tinyint(4) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `FileOrphansView`
--

DROP TABLE IF EXISTS `FileOrphansView`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `FileOrphansView` (
  `id` tinyint(4) NOT NULL,
  `name` tinyint(4) NOT NULL,
  `imageable_id` tinyint(4) NOT NULL,
  `mapID` tinyint(4) NOT NULL,
  `mapName` tinyint(4) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `author_rights`
--

DROP TABLE IF EXISTS `author_rights`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `author_rights` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `map_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cron`
--

DROP TABLE IF EXISTS `cron`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cron` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rule_id` int(11) NOT NULL,
  `activate` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rule_id` (`rule_id`),
  CONSTRAINT `cron_ibfk_1` FOREIGN KEY (`rule_id`) REFERENCES `map_counter_common_rules` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dictionary`
--

DROP TABLE IF EXISTS `dictionary`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dictionary` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `word` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `word` (`word`)
) ENGINE=InnoDB AUTO_INCREMENT=96114 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `groups`
--

DROP TABLE IF EXISTS `groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `h5p_contents`
--

DROP TABLE IF EXISTS `h5p_contents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `h5p_contents` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `user_id` int(10) unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `library_id` int(10) unsigned NOT NULL,
  `parameters` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `filtered` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(127) COLLATE utf8mb4_unicode_ci NOT NULL,
  `embed_type` varchar(127) COLLATE utf8mb4_unicode_ci NOT NULL,
  `disable` int(10) unsigned NOT NULL DEFAULT '0',
  `content_type` varchar(127) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `author` varchar(127) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `keywords` text COLLATE utf8mb4_unicode_ci,
  `description` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `h5p_contents_libraries`
--

DROP TABLE IF EXISTS `h5p_contents_libraries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `h5p_contents_libraries` (
  `content_id` int(10) unsigned NOT NULL,
  `library_id` int(10) unsigned NOT NULL,
  `dependency_type` varchar(31) COLLATE utf8mb4_unicode_ci NOT NULL,
  `weight` smallint(5) unsigned NOT NULL DEFAULT '0',
  `drop_css` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`content_id`,`library_id`,`dependency_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `h5p_contents_tags`
--

DROP TABLE IF EXISTS `h5p_contents_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `h5p_contents_tags` (
  `content_id` int(10) unsigned NOT NULL,
  `tag_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`content_id`,`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `h5p_contents_user_data`
--

DROP TABLE IF EXISTS `h5p_contents_user_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `h5p_contents_user_data` (
  `content_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `sub_content_id` int(10) unsigned NOT NULL,
  `data_id` varchar(127) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `preload` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `invalidate` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`content_id`,`user_id`,`sub_content_id`,`data_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `h5p_counters`
--

DROP TABLE IF EXISTS `h5p_counters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `h5p_counters` (
  `type` varchar(63) COLLATE utf8mb4_unicode_ci NOT NULL,
  `library_name` varchar(127) COLLATE utf8mb4_unicode_ci NOT NULL,
  `library_version` varchar(31) COLLATE utf8mb4_unicode_ci NOT NULL,
  `num` int(10) unsigned NOT NULL,
  PRIMARY KEY (`type`,`library_name`,`library_version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `h5p_events`
--

DROP TABLE IF EXISTS `h5p_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `h5p_events` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  `type` varchar(63) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sub_type` varchar(63) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_id` int(10) unsigned NOT NULL,
  `content_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `library_name` varchar(127) COLLATE utf8mb4_unicode_ci NOT NULL,
  `library_version` varchar(31) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=85 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `h5p_libraries`
--

DROP TABLE IF EXISTS `h5p_libraries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `h5p_libraries` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `name` varchar(127) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `major_version` int(10) unsigned NOT NULL,
  `minor_version` int(10) unsigned NOT NULL,
  `patch_version` int(10) unsigned NOT NULL,
  `runnable` int(10) unsigned NOT NULL,
  `restricted` int(10) unsigned NOT NULL DEFAULT '0',
  `fullscreen` int(10) unsigned NOT NULL,
  `embed_types` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `preloaded_js` text COLLATE utf8mb4_unicode_ci,
  `preloaded_css` text COLLATE utf8mb4_unicode_ci,
  `drop_library_css` text COLLATE utf8mb4_unicode_ci,
  `semantics` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `tutorial_url` varchar(1023) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name_version` (`name`,`major_version`,`minor_version`,`patch_version`),
  KEY `runnable` (`runnable`)
) ENGINE=InnoDB AUTO_INCREMENT=85 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `h5p_libraries_cachedassets`
--

DROP TABLE IF EXISTS `h5p_libraries_cachedassets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `h5p_libraries_cachedassets` (
  `library_id` int(10) unsigned NOT NULL,
  `hash` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`library_id`,`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `h5p_libraries_languages`
--

DROP TABLE IF EXISTS `h5p_libraries_languages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `h5p_libraries_languages` (
  `library_id` int(10) unsigned NOT NULL,
  `language_code` varchar(31) COLLATE utf8mb4_unicode_ci NOT NULL,
  `translation` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`library_id`,`language_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `h5p_libraries_libraries`
--

DROP TABLE IF EXISTS `h5p_libraries_libraries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `h5p_libraries_libraries` (
  `library_id` int(10) unsigned NOT NULL,
  `required_library_id` int(10) unsigned NOT NULL,
  `dependency_type` varchar(31) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`library_id`,`required_library_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `h5p_results`
--

DROP TABLE IF EXISTS `h5p_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `h5p_results` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `content_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `score` int(10) unsigned NOT NULL,
  `max_score` int(10) unsigned NOT NULL,
  `opened` int(10) unsigned NOT NULL,
  `finished` int(10) unsigned NOT NULL,
  `time` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `content_user` (`content_id`,`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `h5p_tags`
--

DROP TABLE IF EXISTS `h5p_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `h5p_tags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(31) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `languages`
--

DROP TABLE IF EXISTS `languages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `languages` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL,
  `key` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lrs`
--

DROP TABLE IF EXISTS `lrs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lrs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `is_enabled` tinyint(1) NOT NULL,
  `name` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `api_version` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lrs_statement`
--

DROP TABLE IF EXISTS `lrs_statement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lrs_statement` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lrs_id` int(10) unsigned NOT NULL,
  `statement_id` int(10) unsigned NOT NULL,
  `status` tinyint(3) unsigned NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `lrs_id` (`lrs_id`),
  KEY `statement_id` (`statement_id`),
  CONSTRAINT `lrs_statement_ibfk_1` FOREIGN KEY (`lrs_id`) REFERENCES `lrs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `lrs_statement_ibfk_2` FOREIGN KEY (`statement_id`) REFERENCES `statements` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=51334 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lti_consumer`
--

DROP TABLE IF EXISTS `lti_consumer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lti_consumer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `consumer_key` varchar(255) NOT NULL,
  `name` varchar(45) NOT NULL,
  `secret` varchar(32) NOT NULL,
  `lti_version` varchar(12) DEFAULT NULL,
  `consumer_name` varchar(255) DEFAULT NULL,
  `consumer_version` varchar(255) DEFAULT NULL,
  `consumer_guid` varchar(255) DEFAULT NULL,
  `css_path` varchar(255) DEFAULT NULL,
  `protected` tinyint(1) NOT NULL,
  `enabled` tinyint(1) NOT NULL,
  `enable_from` datetime DEFAULT NULL,
  `enable_until` datetime DEFAULT NULL,
  `without_end_date` tinyint(1) DEFAULT NULL,
  `last_access` date DEFAULT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  `role` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `consumer_key` (`consumer_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lti_consumers`
--

DROP TABLE IF EXISTS `lti_consumers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lti_consumers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `consumer_key` varchar(255) NOT NULL,
  `name` varchar(45) NOT NULL,
  `secret` varchar(32) NOT NULL,
  `lti_version` varchar(12) DEFAULT NULL,
  `consumer_name` varchar(255) DEFAULT NULL,
  `consumer_version` varchar(255) DEFAULT NULL,
  `consumer_guid` varchar(255) DEFAULT NULL,
  `css_path` varchar(255) DEFAULT NULL,
  `protected` tinyint(1) NOT NULL,
  `enabled` tinyint(1) NOT NULL,
  `enable_from` datetime DEFAULT NULL,
  `enable_until` datetime DEFAULT NULL,
  `without_end_date` tinyint(1) DEFAULT NULL,
  `last_access` date DEFAULT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  `role` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `consumer_key` (`consumer_key`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lti_contexts`
--

DROP TABLE IF EXISTS `lti_contexts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lti_contexts` (
  `consumer_key` varchar(255) NOT NULL,
  `context_id` varchar(255) NOT NULL,
  `lti_context_id` varchar(255) DEFAULT NULL,
  `lti_resource_id` varchar(255) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `settings` text,
  `primary_consumer_key` varchar(255) DEFAULT NULL,
  `primary_context_id` varchar(255) DEFAULT NULL,
  `share_approved` tinyint(1) DEFAULT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`consumer_key`),
  CONSTRAINT `lti_contexts_ibfk_1` FOREIGN KEY (`consumer_key`) REFERENCES `lti_consumers` (`consumer_key`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lti_nonces`
--

DROP TABLE IF EXISTS `lti_nonces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lti_nonces` (
  `consumer_key` varchar(255) NOT NULL,
  `value` varchar(32) NOT NULL,
  `expires` datetime NOT NULL,
  PRIMARY KEY (`consumer_key`),
  CONSTRAINT `lti_nonces_ibfk_1` FOREIGN KEY (`consumer_key`) REFERENCES `lti_consumers` (`consumer_key`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lti_providers`
--

DROP TABLE IF EXISTS `lti_providers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lti_providers` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` enum('video service') DEFAULT NULL,
  `consumer_key` varchar(255) DEFAULT NULL,
  `secret` varchar(32) DEFAULT NULL,
  `launch_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lti_sharekeys`
--

DROP TABLE IF EXISTS `lti_sharekeys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lti_sharekeys` (
  `share_key_id` varchar(32) NOT NULL,
  `primary_consumer_key` varchar(255) NOT NULL,
  `primary_context_id` varchar(255) NOT NULL,
  `auto_approve` tinyint(1) NOT NULL,
  `expires` datetime NOT NULL,
  PRIMARY KEY (`share_key_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lti_users`
--

DROP TABLE IF EXISTS `lti_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lti_users` (
  `consumer_key` varchar(255) NOT NULL,
  `context_id` varchar(255) NOT NULL,
  `user_id` varchar(255) DEFAULT NULL,
  `lti_result_sourcedid` varchar(255) DEFAULT NULL,
  `created` datetime NOT NULL,
  `updated` datetime NOT NULL,
  PRIMARY KEY (`consumer_key`),
  CONSTRAINT `lti_users_ibfk_1` FOREIGN KEY (`consumer_key`) REFERENCES `lti_contexts` (`consumer_key`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `lti_users_ibfk_2` FOREIGN KEY (`consumer_key`) REFERENCES `lti_contexts` (`consumer_key`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_avatars`
--

DROP TABLE IF EXISTS `map_avatars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_avatars` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `map_id` int(10) unsigned NOT NULL,
  `skin_1` varchar(6) DEFAULT NULL,
  `skin_2` varchar(6) DEFAULT NULL,
  `cloth` varchar(6) DEFAULT NULL,
  `nose` varchar(20) DEFAULT NULL,
  `hair` varchar(20) DEFAULT NULL,
  `environment` varchar(20) DEFAULT NULL,
  `accessory_1` varchar(20) DEFAULT NULL,
  `bkd` varchar(6) DEFAULT NULL,
  `sex` varchar(20) DEFAULT NULL,
  `mouth` varchar(20) DEFAULT NULL,
  `outfit` varchar(20) DEFAULT NULL,
  `bubble` varchar(20) DEFAULT NULL,
  `bubble_text` varchar(100) DEFAULT NULL,
  `accessory_2` varchar(20) DEFAULT NULL,
  `accessory_3` varchar(20) DEFAULT NULL,
  `age` varchar(2) DEFAULT NULL,
  `eyes` varchar(20) DEFAULT NULL,
  `hair_color` varchar(6) DEFAULT NULL,
  `image` varchar(100) DEFAULT NULL,
  `is_private` int(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `map_id` (`map_id`),
  KEY `map_id_2` (`map_id`),
  CONSTRAINT `map_avatars_ibfk_1` FOREIGN KEY (`map_id`) REFERENCES `maps` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_chat_elements`
--

DROP TABLE IF EXISTS `map_chat_elements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_chat_elements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `chat_id` int(10) unsigned NOT NULL,
  `question` text NOT NULL,
  `response` text NOT NULL,
  `function` varchar(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `chat_id` (`chat_id`),
  CONSTRAINT `map_chat_elements_ibfk_1` FOREIGN KEY (`chat_id`) REFERENCES `map_chats` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_chats`
--

DROP TABLE IF EXISTS `map_chats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_chats` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `map_id` int(10) unsigned NOT NULL,
  `counter_id` int(10) unsigned DEFAULT NULL,
  `stem` text NOT NULL,
  `is_private` int(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `map_id` (`map_id`,`counter_id`),
  KEY `counter_id` (`counter_id`),
  CONSTRAINT `map_chats_ibfk_1` FOREIGN KEY (`map_id`) REFERENCES `maps` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_collectionMaps`
--

DROP TABLE IF EXISTS `map_collectionMaps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_collectionMaps` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `collection_id` int(10) unsigned NOT NULL,
  `map_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `collection_id` (`collection_id`),
  KEY `map_id` (`map_id`),
  CONSTRAINT `map_collectionmaps_ibfk_1` FOREIGN KEY (`collection_id`) REFERENCES `map_collections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `map_collectionmaps_ibfk_2` FOREIGN KEY (`map_id`) REFERENCES `maps` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_collections`
--

DROP TABLE IF EXISTS `map_collections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_collections` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_contributor_roles`
--

DROP TABLE IF EXISTS `map_contributor_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_contributor_roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` varchar(700) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_contributors`
--

DROP TABLE IF EXISTS `map_contributors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_contributors` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `map_id` int(10) unsigned NOT NULL,
  `role_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `organization` varchar(200) NOT NULL,
  `order` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `map_id` (`map_id`),
  CONSTRAINT `map_contributors_ibfk_1` FOREIGN KEY (`map_id`) REFERENCES `maps` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_counter_common_rules`
--

DROP TABLE IF EXISTS `map_counter_common_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_counter_common_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `map_id` int(10) unsigned NOT NULL,
  `rule` longtext NOT NULL,
  `lightning` int(11) NOT NULL,
  `isCorrect` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `map_id` (`map_id`),
  CONSTRAINT `map_counter_common_rules_ibfk_1` FOREIGN KEY (`map_id`) REFERENCES `maps` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_counter_rule_relations`
--

DROP TABLE IF EXISTS `map_counter_rule_relations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_counter_rule_relations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(70) NOT NULL,
  `value` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_counter_rules`
--

DROP TABLE IF EXISTS `map_counter_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_counter_rules` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `counter_id` int(10) unsigned NOT NULL,
  `relation_id` int(11) NOT NULL,
  `value` double NOT NULL DEFAULT '0',
  `function` varchar(50) DEFAULT NULL,
  `redirect_node_id` int(11) DEFAULT NULL,
  `message` varchar(500) DEFAULT NULL,
  `counter` int(11) DEFAULT NULL,
  `counter_value` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `counter_id` (`counter_id`),
  CONSTRAINT `map_counter_rules_ibfk_1` FOREIGN KEY (`counter_id`) REFERENCES `map_counters` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_counters`
--

DROP TABLE IF EXISTS `map_counters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_counters` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `map_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(200) DEFAULT NULL,
  `description` text,
  `start_value` double NOT NULL DEFAULT '0',
  `icon_id` int(11) DEFAULT NULL,
  `prefix` varchar(20) DEFAULT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `visible` tinyint(1) DEFAULT '0',
  `out_of` int(11) DEFAULT NULL,
  `status` int(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `map_id` (`map_id`),
  CONSTRAINT `map_counters_ibfk_1` FOREIGN KEY (`map_id`) REFERENCES `maps` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_dam_elements`
--

DROP TABLE IF EXISTS `map_dam_elements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_dam_elements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `dam_id` int(10) unsigned NOT NULL,
  `element_type` varchar(20) DEFAULT NULL,
  `order` int(11) DEFAULT '0',
  `display` varchar(20) NOT NULL,
  `element_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `dam_id` (`dam_id`),
  KEY `element_id` (`element_id`),
  CONSTRAINT `map_dam_elements_ibfk_1` FOREIGN KEY (`dam_id`) REFERENCES `map_dams` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_dams`
--

DROP TABLE IF EXISTS `map_dams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_dams` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `map_id` int(10) unsigned NOT NULL,
  `name` varchar(500) DEFAULT NULL,
  `is_private` int(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `map_id` (`map_id`),
  CONSTRAINT `map_dams_ibfk_1` FOREIGN KEY (`map_id`) REFERENCES `maps` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_elements`
--

DROP TABLE IF EXISTS `map_elements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_elements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `map_id` int(10) unsigned NOT NULL,
  `mime` varchar(500) DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `path` varchar(300) NOT NULL,
  `args` varchar(100) DEFAULT NULL,
  `width` int(11) DEFAULT NULL,
  `width_type` varchar(2) NOT NULL DEFAULT 'px',
  `height` int(11) DEFAULT NULL,
  `height_type` varchar(2) NOT NULL DEFAULT 'px',
  `h_align` varchar(20) DEFAULT NULL,
  `v_align` varchar(20) DEFAULT NULL,
  `is_shared` tinyint(4) NOT NULL DEFAULT '1',
  `is_private` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `map_id` (`map_id`),
  CONSTRAINT `map_elements_ibfk_1` FOREIGN KEY (`map_id`) REFERENCES `maps` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_elements_metadata`
--

DROP TABLE IF EXISTS `map_elements_metadata`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_elements_metadata` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `element_id` int(11) NOT NULL,
  `description` text,
  `originURL` varchar(300) DEFAULT NULL,
  `copyright` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=240 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_feedback_operators`
--

DROP TABLE IF EXISTS `map_feedback_operators`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_feedback_operators` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `value` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_feedback_rules`
--

DROP TABLE IF EXISTS `map_feedback_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_feedback_rules` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `map_id` int(10) unsigned NOT NULL,
  `rule_type_id` int(11) NOT NULL,
  `value` int(11) DEFAULT NULL,
  `operator_id` int(11) DEFAULT NULL,
  `message` text,
  `counter_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `map_id` (`map_id`),
  CONSTRAINT `map_feedback_rules_ibfk_1` FOREIGN KEY (`map_id`) REFERENCES `maps` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_feedback_types`
--

DROP TABLE IF EXISTS `map_feedback_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_feedback_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `description` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_groups`
--

DROP TABLE IF EXISTS `map_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_groups` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `map_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `map_id` (`map_id`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_keys`
--

DROP TABLE IF EXISTS `map_keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_keys` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `map_id` int(10) unsigned NOT NULL,
  `key` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `key` (`key`),
  KEY `map_id` (`map_id`),
  CONSTRAINT `map_keys_ibfk_1` FOREIGN KEY (`map_id`) REFERENCES `maps` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_node_counters`
--

DROP TABLE IF EXISTS `map_node_counters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_node_counters` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `node_id` int(10) unsigned NOT NULL,
  `counter_id` int(11) NOT NULL,
  `function` varchar(20) NOT NULL,
  `display` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `node_id` (`node_id`),
  CONSTRAINT `map_node_counters_ibfk_1` FOREIGN KEY (`node_id`) REFERENCES `map_nodes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_node_jumps`
--

DROP TABLE IF EXISTS `map_node_jumps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_node_jumps` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `map_id` int(10) unsigned NOT NULL,
  `node_id` int(10) unsigned NOT NULL,
  `image_id` int(11) DEFAULT NULL,
  `text` varchar(500) DEFAULT NULL,
  `order` int(11) DEFAULT '1',
  `probability` int(11) DEFAULT '0',
  `hidden` tinyint(1) DEFAULT '0',
  `link_style_id` int(11) DEFAULT NULL,
  `thickness` int(11) DEFAULT NULL,
  `line_type` int(11) DEFAULT NULL,
  `color` varchar(45) DEFAULT NULL,
  `follow_once` int(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `map_id` (`map_id`),
  KEY `node_id` (`node_id`),
  CONSTRAINT `map_jumps_ibfk_1` FOREIGN KEY (`map_id`) REFERENCES `maps` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `map_jumps_ibfk_4` FOREIGN KEY (`node_id`) REFERENCES `map_nodes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_node_link_stylies`
--

DROP TABLE IF EXISTS `map_node_link_stylies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_node_link_stylies` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(70) NOT NULL,
  `description` varchar(500) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_node_link_types`
--

DROP TABLE IF EXISTS `map_node_link_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_node_link_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` varchar(500) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_node_links`
--

DROP TABLE IF EXISTS `map_node_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_node_links` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `map_id` int(10) unsigned NOT NULL,
  `node_id_1` int(10) unsigned NOT NULL,
  `node_id_2` int(10) unsigned NOT NULL,
  `image_id` int(11) DEFAULT NULL,
  `text` varchar(500) DEFAULT NULL,
  `order` int(11) DEFAULT '1',
  `probability` int(11) DEFAULT '0',
  `hidden` tinyint(1) DEFAULT '0',
  `link_style_id` int(11) DEFAULT NULL,
  `thickness` int(11) DEFAULT NULL,
  `line_type` int(11) DEFAULT NULL,
  `color` varchar(45) DEFAULT NULL,
  `follow_once` int(4) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_At` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `map_id` (`map_id`),
  KEY `node_id_1` (`node_id_1`),
  KEY `node_id_2` (`node_id_2`),
  CONSTRAINT `map_node_links_ibfk_1` FOREIGN KEY (`map_id`) REFERENCES `maps` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `map_node_links_ibfk_4` FOREIGN KEY (`node_id_1`) REFERENCES `map_nodes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `map_node_links_ibfk_5` FOREIGN KEY (`node_id_2`) REFERENCES `map_nodes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=104529 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_node_notes`
--

DROP TABLE IF EXISTS `map_node_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_node_notes` (
  `id` int(10) unsigned NOT NULL,
  `map_node_id` int(10) unsigned NOT NULL,
  `text` text,
  PRIMARY KEY (`id`),
  KEY `fk_map_node_id_idx` (`map_node_id`),
  CONSTRAINT `fk_map_node` FOREIGN KEY (`map_node_id`) REFERENCES `map_nodes` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_node_priorities`
--

DROP TABLE IF EXISTS `map_node_priorities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_node_priorities` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(70) NOT NULL,
  `description` varchar(500) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_node_references`
--

DROP TABLE IF EXISTS `map_node_references`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_node_references` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `map_id` int(11) unsigned NOT NULL,
  `node_id` int(11) unsigned NOT NULL,
  `element_id` int(11) unsigned NOT NULL,
  `type` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_node_section_nodes`
--

DROP TABLE IF EXISTS `map_node_section_nodes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_node_section_nodes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `section_id` int(10) unsigned NOT NULL,
  `node_id` int(10) unsigned NOT NULL,
  `order` int(11) NOT NULL,
  `node_type` enum('regular','in','out','crucial') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `section_id` (`section_id`),
  KEY `section_id_2` (`section_id`),
  KEY `node_id` (`node_id`),
  CONSTRAINT `map_node_section_nodes_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `map_node_sections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `map_node_section_nodes_ibfk_2` FOREIGN KEY (`node_id`) REFERENCES `map_nodes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `map_node_section_nodes_ibfk_3` FOREIGN KEY (`node_id`) REFERENCES `map_nodes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16017 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_node_sections`
--

DROP TABLE IF EXISTS `map_node_sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_node_sections` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `map_id` int(10) unsigned NOT NULL,
  `orderBy` enum('random','x','y') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `map_id` (`map_id`),
  CONSTRAINT `map_node_sections_ibfk_1` FOREIGN KEY (`map_id`) REFERENCES `maps` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=451 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_node_types`
--

DROP TABLE IF EXISTS `map_node_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_node_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(70) NOT NULL,
  `description` varchar(500) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_nodes`
--

DROP TABLE IF EXISTS `map_nodes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_nodes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `map_id` int(10) unsigned NOT NULL,
  `title` varchar(200) DEFAULT NULL,
  `text` text,
  `type_id` int(11) DEFAULT NULL,
  `probability` tinyint(1) DEFAULT NULL,
  `conditional` varchar(500) DEFAULT NULL,
  `conditional_message` varchar(1000) DEFAULT NULL,
  `info` text,
  `is_private` int(4) NOT NULL DEFAULT '0',
  `link_style_id` int(10) unsigned DEFAULT NULL,
  `link_type_id` int(11) DEFAULT '1',
  `priority_id` int(11) DEFAULT NULL,
  `kfp` tinyint(1) DEFAULT NULL,
  `undo` tinyint(1) DEFAULT NULL,
  `end` tinyint(1) DEFAULT NULL,
  `x` double DEFAULT NULL,
  `y` double DEFAULT NULL,
  `rgb` varchar(8) DEFAULT NULL,
  `show_info` tinyint(4) NOT NULL DEFAULT '0',
  `annotation` text,
  `height` int(11) DEFAULT NULL,
  `width` int(11) DEFAULT NULL,
  `locked` int(11) DEFAULT NULL,
  `collapsed` int(11) DEFAULT NULL,
  `visit_once` int(4) DEFAULT NULL,
  `force_reload` int(11) DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_At` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `map_id` (`map_id`),
  KEY `link_style_id` (`link_style_id`),
  CONSTRAINT `map_nodes_ibfk_1` FOREIGN KEY (`map_id`) REFERENCES `maps` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `map_nodes_ibfk_2` FOREIGN KEY (`link_style_id`) REFERENCES `map_node_link_stylies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=30892 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_popup_assign_types`
--

DROP TABLE IF EXISTS `map_popup_assign_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_popup_assign_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_popup_position_types`
--

DROP TABLE IF EXISTS `map_popup_position_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_popup_position_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_popup_positions`
--

DROP TABLE IF EXISTS `map_popup_positions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_popup_positions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_popups`
--

DROP TABLE IF EXISTS `map_popups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_popups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `map_id` int(11) NOT NULL,
  `title` varchar(300) NOT NULL,
  `text` text NOT NULL,
  `position_type` int(11) NOT NULL,
  `position_id` int(11) NOT NULL,
  `time_before` int(11) NOT NULL DEFAULT '0',
  `time_length` int(11) NOT NULL DEFAULT '0',
  `is_enabled` tinyint(4) NOT NULL DEFAULT '0',
  `title_hide` int(11) NOT NULL,
  `annotation` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=103 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_popups_assign`
--

DROP TABLE IF EXISTS `map_popups_assign`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_popups_assign` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `map_popup_id` int(11) NOT NULL,
  `assign_type_id` int(11) NOT NULL,
  `assign_to_id` int(11) NOT NULL,
  `redirect_to_id` int(11) DEFAULT NULL,
  `redirect_type_id` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=119 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_popups_counters`
--

DROP TABLE IF EXISTS `map_popups_counters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_popups_counters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `popup_id` int(11) NOT NULL,
  `counter_id` int(10) unsigned NOT NULL,
  `function` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `popup_id` (`popup_id`),
  KEY `counter_id` (`counter_id`),
  KEY `counter_id_2` (`counter_id`),
  CONSTRAINT `map_popups_counters_ibfk_1` FOREIGN KEY (`popup_id`) REFERENCES `map_popups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=541 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_popups_styles`
--

DROP TABLE IF EXISTS `map_popups_styles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_popups_styles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `map_popup_id` int(11) NOT NULL,
  `is_default_background_color` tinyint(4) NOT NULL DEFAULT '1',
  `is_background_transparent` tinyint(4) NOT NULL DEFAULT '0',
  `background_color` varchar(10) DEFAULT NULL,
  `font_color` varchar(10) DEFAULT NULL,
  `border_color` varchar(10) DEFAULT NULL,
  `is_border_transparent` tinyint(4) NOT NULL DEFAULT '0',
  `background_transparent` varchar(4) NOT NULL,
  `border_transparent` varchar(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=91 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_question_responses`
--

DROP TABLE IF EXISTS `map_question_responses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_question_responses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `question_id` int(10) unsigned DEFAULT NULL,
  `response` varchar(250) DEFAULT NULL,
  `feedback` text,
  `is_correct` tinyint(1) DEFAULT NULL,
  `score` int(11) DEFAULT NULL,
  `from` varchar(200) DEFAULT NULL,
  `to` varchar(200) DEFAULT NULL,
  `order` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `question_id` (`question_id`),
  KEY `parent_id` (`parent_id`),
  CONSTRAINT `map_question_responses_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `map_questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `map_question_responses_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `map_question_responses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8499 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_question_types`
--

DROP TABLE IF EXISTS `map_question_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_question_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(70) DEFAULT NULL,
  `value` varchar(20) DEFAULT NULL,
  `template_name` varchar(200) NOT NULL,
  `template_args` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_question_validation`
--

DROP TABLE IF EXISTS `map_question_validation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_question_validation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question_id` int(10) unsigned NOT NULL,
  `validator` text NOT NULL,
  `second_parameter` text NOT NULL,
  `error_message` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `question_id` (`question_id`),
  CONSTRAINT `map_question_validation_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `map_questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_questions`
--

DROP TABLE IF EXISTS `map_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_questions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `map_id` int(10) unsigned DEFAULT NULL,
  `stem` varchar(500) DEFAULT NULL,
  `entry_type_id` int(11) NOT NULL,
  `width` int(11) NOT NULL DEFAULT '0',
  `height` int(11) NOT NULL DEFAULT '0',
  `feedback` varchar(1000) DEFAULT NULL,
  `prompt` text NOT NULL,
  `show_answer` tinyint(1) NOT NULL DEFAULT '1',
  `counter_id` int(11) DEFAULT NULL,
  `num_tries` int(11) NOT NULL DEFAULT '-1',
  `show_submit` tinyint(4) NOT NULL DEFAULT '0',
  `redirect_node_id` int(10) unsigned DEFAULT NULL,
  `submit_text` varchar(200) DEFAULT NULL,
  `type_display` int(11) NOT NULL DEFAULT '0',
  `settings` text,
  `is_private` int(4) NOT NULL DEFAULT '0',
  `order` int(10) DEFAULT NULL,
  `external_source_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `map_id` (`map_id`),
  KEY `parent_id` (`parent_id`),
  CONSTRAINT `map_questions_ibfk_1` FOREIGN KEY (`map_id`) REFERENCES `maps` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `map_questions_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `map_questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2917 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_sections`
--

DROP TABLE IF EXISTS `map_sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_sections` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` varchar(700) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_securities`
--

DROP TABLE IF EXISTS `map_securities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_securities` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` varchar(700) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_skins`
--

DROP TABLE IF EXISTS `map_skins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_skins` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `path` varchar(200) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `data` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_types`
--

DROP TABLE IF EXISTS `map_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` varchar(700) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_users`
--

DROP TABLE IF EXISTS `map_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `map_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `map_id` (`map_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `map_users_ibfk_1` FOREIGN KEY (`map_id`) REFERENCES `maps` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `map_users_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=736 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_vpd_elements`
--

DROP TABLE IF EXISTS `map_vpd_elements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_vpd_elements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `vpd_id` int(10) unsigned NOT NULL,
  `key` varchar(100) NOT NULL,
  `value` varchar(500) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `vpd_id` (`vpd_id`),
  CONSTRAINT `map_vpd_elements_ibfk_1` FOREIGN KEY (`vpd_id`) REFERENCES `map_vpds` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_vpd_types`
--

DROP TABLE IF EXISTS `map_vpd_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_vpd_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `label` varchar(500) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_vpds`
--

DROP TABLE IF EXISTS `map_vpds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_vpds` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `map_id` int(10) unsigned NOT NULL,
  `vpd_type_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `vpd_type_id` (`vpd_type_id`),
  KEY `vpd_type_id_2` (`vpd_type_id`),
  CONSTRAINT `map_vpds_ibfk_1` FOREIGN KEY (`vpd_type_id`) REFERENCES `map_vpd_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `maps`
--

DROP TABLE IF EXISTS `maps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `maps` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `author_id` int(10) unsigned NOT NULL,
  `abstract` varchar(2000) NOT NULL,
  `startScore` int(11) NOT NULL,
  `threshold` int(11) NOT NULL,
  `keywords` varchar(500) NOT NULL DEFAULT '''''',
  `type_id` int(10) unsigned NOT NULL,
  `units` varchar(10) NOT NULL,
  `security_id` int(10) unsigned NOT NULL,
  `guid` varchar(50) NOT NULL,
  `timing` tinyint(1) NOT NULL,
  `delta_time` int(11) NOT NULL,
  `reminder_msg` varchar(255) NOT NULL DEFAULT '',
  `reminder_time` int(11) NOT NULL DEFAULT '0',
  `show_bar` tinyint(1) NOT NULL,
  `show_score` tinyint(1) NOT NULL,
  `skin_id` int(10) unsigned NOT NULL,
  `enabled` tinyint(1) NOT NULL,
  `section_id` int(10) unsigned NOT NULL,
  `language_id` int(10) unsigned DEFAULT '1',
  `feedback` varchar(2000) NOT NULL,
  `dev_notes` varchar(1000) NOT NULL,
  `source` varchar(50) NOT NULL,
  `source_id` int(11) NOT NULL,
  `verification` text,
  `assign_forum_id` int(11) DEFAULT NULL,
  `author_rights` int(11) NOT NULL,
  `revisable_answers` tinyint(1) NOT NULL,
  `send_xapi_statements` tinyint(1) NOT NULL DEFAULT '0',
  `renderer_version` float DEFAULT NULL,
  `is_template` int(11) DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_At` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `author_id` (`author_id`),
  KEY `author_id_2` (`author_id`,`type_id`,`security_id`,`section_id`,`language_id`),
  KEY `security_id` (`security_id`),
  KEY `type_id` (`type_id`,`skin_id`,`section_id`,`language_id`),
  KEY `skin_id` (`skin_id`),
  KEY `section_id` (`section_id`),
  KEY `language_id` (`language_id`),
  CONSTRAINT `maps_ibfk_2` FOREIGN KEY (`security_id`) REFERENCES `map_securities` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE,
  CONSTRAINT `maps_ibfk_3` FOREIGN KEY (`type_id`) REFERENCES `map_types` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE,
  CONSTRAINT `maps_ibfk_6` FOREIGN KEY (`section_id`) REFERENCES `map_sections` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE,
  CONSTRAINT `maps_ibfk_7` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1603 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `oauth_providers`
--

DROP TABLE IF EXISTS `oauth_providers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_providers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(250) NOT NULL,
  `version` varchar(200) NOT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `appId` varchar(300) DEFAULT NULL,
  `secret` varchar(300) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `options`
--

DROP TABLE IF EXISTS `options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `options` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL DEFAULT '',
  `value` longtext NOT NULL,
  `autoload` varchar(20) NOT NULL DEFAULT 'yes',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `phinxlog`
--

DROP TABLE IF EXISTS `phinxlog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `phinxlog` (
  `version` bigint(20) NOT NULL,
  `migration_name` varchar(100) DEFAULT NULL,
  `start_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `end_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `breakpoint` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `q_cumulative`
--

DROP TABLE IF EXISTS `q_cumulative`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `q_cumulative` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question_id` int(10) unsigned NOT NULL,
  `map_id` int(10) unsigned NOT NULL,
  `reset` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `question_id` (`question_id`),
  KEY `map_id` (`map_id`),
  CONSTRAINT `q_cumulative_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `map_questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `q_cumulative_ibfk_2` FOREIGN KEY (`map_id`) REFERENCES `maps` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `scenario_maps`
--

DROP TABLE IF EXISTS `scenario_maps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `scenario_maps` (
  `id` int(10) unsigned NOT NULL,
  `map_id` int(10) unsigned NOT NULL,
  `scenario_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_scenerio_maps_idx` (`scenario_id`),
  KEY `fk_scenario_maps_maps_idx` (`map_id`),
  CONSTRAINT `fk_scenario_maps_maps` FOREIGN KEY (`map_id`) REFERENCES `maps` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_scenario_maps_scenarios` FOREIGN KEY (`scenario_id`) REFERENCES `scenarios` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `scenarios`
--

DROP TABLE IF EXISTS `scenarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `scenarios` (
  `id` int(10) unsigned NOT NULL,
  `name` varchar(45) NOT NULL,
  `description` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `scope_types`
--

DROP TABLE IF EXISTS `scope_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `scope_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `description` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `security_roles`
--

DROP TABLE IF EXISTS `security_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `security_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `imageable_id` int(11) NOT NULL,
  `imageable_type` varchar(45) NOT NULL,
  `acl` varchar(45) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `security_users`
--

DROP TABLE IF EXISTS `security_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `security_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `imageable_id` int(11) NOT NULL,
  `imageable_type` varchar(45) NOT NULL,
  `acl` varchar(45) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `servers`
--

DROP TABLE IF EXISTS `servers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `servers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `description` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sjt_response`
--

DROP TABLE IF EXISTS `sjt_response`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sjt_response` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `response_id` int(10) unsigned NOT NULL,
  `position` int(11) NOT NULL,
  `points` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `response_id` (`response_id`),
  CONSTRAINT `sjt_response_ibfk_1` FOREIGN KEY (`response_id`) REFERENCES `map_question_responses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `statements`
--

DROP TABLE IF EXISTS `statements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `statements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `session_id` int(10) unsigned DEFAULT NULL,
  `initiator` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `statement` text NOT NULL,
  `timestamp` decimal(18,6) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`),
  CONSTRAINT `statements_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `user_sessions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=52441 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `statistics_user_datesave`
--

DROP TABLE IF EXISTS `statistics_user_datesave`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `statistics_user_datesave` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date_save` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `statistics_user_responses`
--

DROP TABLE IF EXISTS `statistics_user_responses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `statistics_user_responses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `response` varchar(700) CHARACTER SET utf8 NOT NULL,
  `node_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `statistics_user_sessions`
--

DROP TABLE IF EXISTS `statistics_user_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `statistics_user_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `map_id` int(11) NOT NULL,
  `start_time` decimal(18,6) NOT NULL,
  `end_time` decimal(18,6) DEFAULT NULL,
  `user_ip` varchar(50) CHARACTER SET utf8 NOT NULL,
  `webinar_id` int(11) NOT NULL,
  `webinar_step` int(11) NOT NULL,
  `date_save_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `statistics_user_sessiontraces`
--

DROP TABLE IF EXISTS `statistics_user_sessiontraces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `statistics_user_sessiontraces` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `session_id` int(11) unsigned NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `map_id` int(11) unsigned NOT NULL,
  `node_id` int(11) unsigned NOT NULL,
  `is_redirected` tinyint(1) NOT NULL DEFAULT '0',
  `counters` varchar(700) CHARACTER SET utf8 DEFAULT NULL,
  `date_stamp` decimal(18,6) DEFAULT NULL,
  `confidence` smallint(6) DEFAULT NULL,
  `dams` varchar(700) CHARACTER SET utf8 DEFAULT NULL,
  `bookmark_made` decimal(18,6) DEFAULT NULL,
  `bookmark_used` decimal(18,6) DEFAULT NULL,
  `end_date_stamp` decimal(18,6) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `system_constants`
--

DROP TABLE IF EXISTS `system_constants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_constants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) DEFAULT NULL,
  `description` text,
  `imageable_id` int(11) NOT NULL,
  `imageable_type` varchar(45) NOT NULL,
  `value` blob,
  `is_system` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_At` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3069 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `system_counter_actions`
--

DROP TABLE IF EXISTS `system_counter_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_counter_actions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `counter_id` int(10) unsigned NOT NULL,
  `map_id` int(11) DEFAULT NULL,
  `operation_type` varchar(45) NOT NULL,
  `expression` varchar(256) NOT NULL,
  `visible` int(11) NOT NULL DEFAULT '0',
  `imageable_id` int(10) NOT NULL,
  `imageable_type` varchar(45) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_counter_action_counter_idx` (`counter_id`),
  CONSTRAINT `fk_counter_action_counter` FOREIGN KEY (`counter_id`) REFERENCES `system_counters` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=12451 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `system_counters`
--

DROP TABLE IF EXISTS `system_counters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_counters` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) DEFAULT NULL,
  `description` text,
  `start_value` blob,
  `value` blob,
  `icon_id` int(11) DEFAULT NULL,
  `prefix` varchar(20) DEFAULT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `visible` tinyint(1) DEFAULT '0',
  `out_of` int(11) DEFAULT NULL,
  `status` int(1) NOT NULL,
  `imageable_id` int(11) NOT NULL,
  `imageable_type` varchar(45) NOT NULL,
  `is_system` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_At` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1356 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `system_courses`
--

DROP TABLE IF EXISTS `system_courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_courses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `description` varchar(200) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_At` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `system_files`
--

DROP TABLE IF EXISTS `system_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_files` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `description` text,
  `mime` varchar(500) DEFAULT NULL,
  `path` varchar(300) NOT NULL,
  `type` int(11) DEFAULT NULL,
  `args` varchar(100) DEFAULT NULL,
  `width` int(11) DEFAULT NULL,
  `width_type` varchar(2) NOT NULL DEFAULT 'px',
  `height` int(11) DEFAULT NULL,
  `height_type` varchar(2) NOT NULL DEFAULT 'px',
  `h_align` varchar(20) DEFAULT NULL,
  `v_align` varchar(20) DEFAULT NULL,
  `origin_url` varchar(100) DEFAULT NULL,
  `copyright` varchar(45) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `is_shared` tinyint(4) NOT NULL DEFAULT '1',
  `is_private` tinyint(4) NOT NULL DEFAULT '0',
  `is_embedded` tinyint(4) DEFAULT NULL,
  `encoded_content` blob,
  `imageable_id` int(11) NOT NULL,
  `imageable_type` varchar(45) NOT NULL,
  `is_system` int(11) DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1294 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `system_globals`
--

DROP TABLE IF EXISTS `system_globals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_globals` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `description` varchar(200) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_At` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `system_question_responses`
--

DROP TABLE IF EXISTS `system_question_responses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_question_responses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) DEFAULT NULL,
  `description` text,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `question_id` int(10) unsigned DEFAULT NULL,
  `response` varchar(250) DEFAULT NULL,
  `feedback` text,
  `is_correct` tinyint(1) DEFAULT NULL,
  `score` int(11) DEFAULT NULL,
  `from` varchar(200) DEFAULT NULL,
  `to` varchar(200) DEFAULT NULL,
  `order` int(10) unsigned NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_At` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `question_id` (`question_id`),
  KEY `parent_id` (`parent_id`),
  CONSTRAINT `system_question_responses_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `system_questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `system_question_responses_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `system_question_responses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9874 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `system_question_types`
--

DROP TABLE IF EXISTS `system_question_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_question_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(70) DEFAULT NULL,
  `value` varchar(20) DEFAULT NULL,
  `template_name` varchar(200) NOT NULL,
  `template_args` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `system_question_validation`
--

DROP TABLE IF EXISTS `system_question_validation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_question_validation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question_id` int(10) unsigned NOT NULL,
  `validator` text NOT NULL,
  `second_parameter` text NOT NULL,
  `error_message` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `question_id` (`question_id`),
  CONSTRAINT `system_question_validation_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `system_questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `system_questions`
--

DROP TABLE IF EXISTS `system_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_questions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `description` text,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `stem` varchar(500) DEFAULT NULL,
  `entry_type_id` int(11) NOT NULL,
  `width` int(11) NOT NULL DEFAULT '0',
  `height` int(11) NOT NULL DEFAULT '0',
  `feedback` varchar(1000) DEFAULT NULL,
  `prompt` text NOT NULL,
  `show_answer` tinyint(1) NOT NULL DEFAULT '1',
  `counter_id` int(11) DEFAULT NULL,
  `num_tries` int(11) NOT NULL DEFAULT '-1',
  `show_submit` tinyint(4) NOT NULL DEFAULT '0',
  `redirect_node_id` int(10) unsigned DEFAULT NULL,
  `submit_text` varchar(200) DEFAULT NULL,
  `type_display` int(11) NOT NULL DEFAULT '0',
  `settings` text,
  `is_private` int(4) NOT NULL DEFAULT '0',
  `order` int(10) DEFAULT NULL,
  `external_source_id` varchar(255) DEFAULT NULL,
  `imageable_id` int(11) NOT NULL,
  `imageable_type` varchar(45) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_At` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  CONSTRAINT `system_questions_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `system_questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3537 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `system_scripts`
--

DROP TABLE IF EXISTS `system_scripts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_scripts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) DEFAULT NULL,
  `description` text,
  `source` blob,
  `is_raw` bit(1) DEFAULT b'0',
  `order` int(11) DEFAULT NULL,
  `postload_id` int(11) DEFAULT NULL,
  `imageable_type` varchar(45) NOT NULL,
  `imageable_id` int(11) NOT NULL,
  `system_scriptscol` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_At` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2712 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `system_servers`
--

DROP TABLE IF EXISTS `system_servers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_servers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `description` varchar(200) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_At` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(45) NOT NULL,
  `description` varchar(256) DEFAULT NULL,
  `value` varchar(256) NOT NULL,
  `system_settingscol` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `system_themes`
--

DROP TABLE IF EXISTS `system_themes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_themes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) DEFAULT NULL,
  `description` text,
  `map_id` int(10) DEFAULT NULL,
  `header_text` text,
  `footer_text` text,
  `left_text` text,
  `right_text` text,
  `imageable_type` varchar(45) NOT NULL,
  `imageable_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_At` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=160 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `today_tips`
--

DROP TABLE IF EXISTS `today_tips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `today_tips` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(300) NOT NULL,
  `text` text NOT NULL,
  `start_date` datetime NOT NULL,
  `weight` int(11) NOT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT '0',
  `is_archived` tinyint(4) NOT NULL DEFAULT '0',
  `end_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `twitter_credits`
--

DROP TABLE IF EXISTS `twitter_credits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `twitter_credits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `API_key` text NOT NULL,
  `API_secret` text NOT NULL,
  `Access_token` text NOT NULL,
  `Access_token_secret` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_bookmarks`
--

DROP TABLE IF EXISTS `user_bookmarks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_bookmarks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `session_id` int(10) unsigned NOT NULL,
  `node_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`),
  KEY `node_id` (`node_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_bookmarks_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `user_sessions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `user_bookmarks_ibfk_2` FOREIGN KEY (`node_id`) REFERENCES `map_nodes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `user_bookmarks_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `user_bookmarks_ibfk_4` FOREIGN KEY (`session_id`) REFERENCES `user_sessions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `user_bookmarks_ibfk_5` FOREIGN KEY (`node_id`) REFERENCES `map_nodes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `user_bookmarks_ibfk_6` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_groups`
--

DROP TABLE IF EXISTS `user_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_groups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `group_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `group_id` (`group_id`),
  KEY `user_id_2` (`user_id`),
  KEY `group_id_2` (`group_id`),
  CONSTRAINT `user_groups_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `user_groups_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_notes`
--

DROP TABLE IF EXISTS `user_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_notes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `session_id` int(10) unsigned DEFAULT NULL,
  `webinar_id` int(11) DEFAULT NULL,
  `text` text NOT NULL,
  `created_at` decimal(18,6) NOT NULL,
  `updated_at` decimal(18,6) NOT NULL,
  `deleted_at` decimal(18,6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_id` (`session_id`),
  KEY `user_id` (`user_id`),
  KEY `webinar_id` (`webinar_id`),
  CONSTRAINT `user_notes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `user_notes_ibfk_2` FOREIGN KEY (`session_id`) REFERENCES `user_sessions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `user_notes_ibfk_3` FOREIGN KEY (`webinar_id`) REFERENCES `webinars` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_responses`
--

DROP TABLE IF EXISTS `user_responses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_responses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `question_id` int(10) unsigned NOT NULL,
  `session_id` int(10) unsigned NOT NULL,
  `response` varchar(1000) DEFAULT NULL,
  `node_id` int(10) unsigned NOT NULL,
  `created_at` decimal(18,6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`),
  KEY `question_id` (`question_id`),
  CONSTRAINT `user_responses_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `map_questions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `user_responses_ibfk_2` FOREIGN KEY (`session_id`) REFERENCES `user_sessions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_sessions`
--

DROP TABLE IF EXISTS `user_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_sessions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `map_id` int(10) unsigned NOT NULL,
  `start_time` decimal(18,6) NOT NULL,
  `user_ip` varchar(50) NOT NULL,
  `webinar_id` int(11) DEFAULT NULL,
  `webinar_step` int(11) DEFAULT NULL,
  `notCumulative` tinyint(1) NOT NULL,
  `reset_at` decimal(18,6) DEFAULT NULL,
  `end_time` decimal(18,6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  KEY `user_id` (`user_id`),
  KEY `map_id` (`map_id`),
  CONSTRAINT `user_sessions_ibfk_2` FOREIGN KEY (`map_id`) REFERENCES `maps` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=152300 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_sessiontraces`
--

DROP TABLE IF EXISTS `user_sessiontraces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_sessiontraces` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `session_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `map_id` int(10) unsigned NOT NULL,
  `node_id` int(10) unsigned NOT NULL,
  `is_redirected` tinyint(1) NOT NULL DEFAULT '0',
  `counters` varchar(2000) DEFAULT NULL,
  `date_stamp` decimal(18,6) DEFAULT NULL,
  `confidence` smallint(6) DEFAULT NULL,
  `dams` varchar(700) DEFAULT NULL,
  `bookmark_made` decimal(18,6) DEFAULT NULL,
  `bookmark_used` decimal(18,6) DEFAULT NULL,
  `end_date_stamp` decimal(18,6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`),
  KEY `user_id` (`user_id`),
  KEY `map_id` (`map_id`),
  KEY `node_id` (`node_id`),
  KEY `session_id_2` (`session_id`),
  CONSTRAINT `user_sessiontraces_ibfk_3` FOREIGN KEY (`map_id`) REFERENCES `maps` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `user_sessiontraces_ibfk_5` FOREIGN KEY (`session_id`) REFERENCES `user_sessions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `user_sessiontraces_ibfk_6` FOREIGN KEY (`node_id`) REFERENCES `map_nodes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_state`
--

DROP TABLE IF EXISTS `user_state`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_state` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `map_id` int(10) unsigned NOT NULL,
  `map_node_id` int(10) unsigned NOT NULL,
  `state_data` blob NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `session_id` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `map_fk_idx` (`map_id`),
  KEY `user_fk_idx` (`user_id`),
  KEY `map_node_fk_idx` (`map_node_id`),
  CONSTRAINT `map_fk` FOREIGN KEY (`map_id`) REFERENCES `maps` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `map_node_fk` FOREIGN KEY (`map_node_id`) REFERENCES `map_nodes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=2015 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_types`
--

DROP TABLE IF EXISTS `user_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_types` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL,
  `description` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(800) NOT NULL,
  `email` varchar(250) NOT NULL,
  `nickname` varchar(120) NOT NULL,
  `language_id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `resetHashKey` varchar(255) DEFAULT NULL,
  `resetHashKeyTime` datetime DEFAULT NULL,
  `resetAttempt` int(11) DEFAULT NULL,
  `resetTimestamp` datetime DEFAULT NULL,
  `visualEditorAutosaveTime` int(11) DEFAULT '50000',
  `oauth_provider_id` int(11) DEFAULT NULL,
  `oauth_id` varchar(300) DEFAULT NULL,
  `history` varchar(255) DEFAULT NULL,
  `history_readonly` tinyint(1) DEFAULT NULL,
  `history_timestamp` int(11) DEFAULT NULL,
  `modeUI` enum('easy','advanced') NOT NULL,
  `is_lti` tinyint(1) DEFAULT '0',
  `settings` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`,`email`),
  KEY `fk_language_id` (`language_id`),
  KEY `fk_type_id` (`type_id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `vocablets`
--

DROP TABLE IF EXISTS `vocablets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vocablets` (
  `guid` varchar(50) NOT NULL,
  `state` varchar(10) NOT NULL,
  `version` varchar(5) NOT NULL,
  `name` varchar(64) NOT NULL,
  `path` varchar(128) NOT NULL,
  `id` int(10) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`),
  UNIQUE KEY `guid` (`guid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `webinar_groups`
--

DROP TABLE IF EXISTS `webinar_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `webinar_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `webinar_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `webinar_macros`
--

DROP TABLE IF EXISTS `webinar_macros`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `webinar_macros` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `text` varchar(255) DEFAULT NULL,
  `hot_keys` varchar(255) DEFAULT NULL,
  `webinar_id` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `webinar_id` (`webinar_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `webinar_maps`
--

DROP TABLE IF EXISTS `webinar_maps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `webinar_maps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `webinar_id` int(11) NOT NULL,
  `which` enum('labyrinth','section') NOT NULL,
  `reference_id` int(11) NOT NULL,
  `step` int(11) NOT NULL,
  `cumulative` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `step` (`step`),
  CONSTRAINT `webinar_maps_ibfk_1` FOREIGN KEY (`step`) REFERENCES `webinar_steps` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `webinar_maps_ibfk_2` FOREIGN KEY (`step`) REFERENCES `webinar_steps` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `webinar_node_poll`
--

DROP TABLE IF EXISTS `webinar_node_poll`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `webinar_node_poll` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `node_id` int(10) unsigned NOT NULL,
  `webinar_id` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `node_id` (`node_id`),
  KEY `webinar_id` (`webinar_id`),
  CONSTRAINT `webinar_node_poll_ibfk_1` FOREIGN KEY (`webinar_id`) REFERENCES `webinars` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `webinar_node_poll_ibfk_2` FOREIGN KEY (`node_id`) REFERENCES `map_nodes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `webinar_poll`
--

DROP TABLE IF EXISTS `webinar_poll`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `webinar_poll` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `on_node` int(10) unsigned NOT NULL,
  `to_node` int(10) unsigned NOT NULL,
  `time` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `on_node` (`on_node`),
  KEY `to_node` (`to_node`),
  CONSTRAINT `webinar_poll_ibfk_1` FOREIGN KEY (`on_node`) REFERENCES `map_nodes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `webinar_poll_ibfk_2` FOREIGN KEY (`to_node`) REFERENCES `map_nodes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `webinar_steps`
--

DROP TABLE IF EXISTS `webinar_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `webinar_steps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `webinar_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `webinar_users`
--

DROP TABLE IF EXISTS `webinar_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `webinar_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `webinar_id` int(11) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `include_4R` tinyint(1) NOT NULL DEFAULT '1',
  `expert` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `webinar_users_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `webinar_users_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `webinars`
--

DROP TABLE IF EXISTS `webinars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `webinars` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(250) NOT NULL,
  `current_step` int(11) DEFAULT NULL,
  `forum_id` int(11) NOT NULL,
  `isForum` tinyint(1) NOT NULL DEFAULT '1',
  `publish` varchar(100) DEFAULT NULL,
  `author_id` int(11) NOT NULL,
  `changeSteps` enum('manually','automatic') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2020-01-30 12:15:13
