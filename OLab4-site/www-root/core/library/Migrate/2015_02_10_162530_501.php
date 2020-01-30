<?php
class Migrate_2015_02_10_162530_501 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        global $db;
        
        $this->record();
        ?>
        ALTER TABLE `community_discussions` ADD COLUMN `forum_category` text NOT NULL AFTER `forum_description`;

        CREATE TABLE IF NOT EXISTS `community_discussions_files` (
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

        CREATE TABLE IF NOT EXISTS `community_discussion_file_versions` (
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

        CREATE TABLE IF NOT EXISTS `community_discussions_open` (
        `cdopen_id` int(12) NOT NULL AUTO_INCREMENT,
        `community_id` int(12) NOT NULL DEFAULT '0',
        `page_id` int(12) NOT NULL DEFAULT '0',
        `proxy_id` int(12) NOT NULL DEFAULT '0',
        `discussion_open` varchar(1000) NOT NULL,
        PRIMARY KEY (`cdopen_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
        <?php
        $this->stop();

        /**
         * Run the SQL provided above.
         */
        $sql_result = $this->run();

        $class_name = get_class($this);

        /**
         * Transform existing discussion forum data.
         */
        
        // Remove the previously migrated discussion boards so that they don't all have to be checked.
        $migrated_boards_query = "SELECT `resource_value` FROM `community_acl` WHERE `resource_type` = 'communitydiscussion'";
        $migrated_boards_results = $db->GetAll($migrated_boards_query);
        if ($migrated_boards_results) {
            $migrated_boards = array_map(function($item) {
                return (int) $item["resource_value"];
            }, $migrated_boards_results);
        } else {
            $migrated_boards = array(0);
        }

        // Migration of discussion boards
        $all_boards_query = "SELECT a.`cdiscussion_id` AS `id`, a.`allow_member_post` AS `create`, a.`allow_member_read` AS `read`
                                FROM `community_discussions` AS a
                                JOIN `community_courses` AS b
                                ON b.`community_id` = a.`community_id`
                                WHERE a.`cdiscussion_id` NOT IN (".implode(",", $migrated_boards).")
                                GROUP BY `id`
                                ORDER BY `id` ASC";
        $discussion_boards = $db->GetAll($all_boards_query);
        if ($discussion_boards) {
            $total = count($discussion_boards);
            print "\n";
            print $class_name . ": Task: " . $this->color("Migrating {$total} community discussion boards.", "pink");

            foreach ($discussion_boards as $board) {
                // Discussion boards may already have an entry in acl_permissions. If so, this has priority over allow_member_read etc.
                $board_acl_query = "SELECT a.*
                                        FROM `".AUTH_DATABASE."`.`acl_permissions` AS a
                                        WHERE `resource_type` = 'communitydiscussion'
                                        AND `resource_value` = ".$db->qstr($board["id"]);
                $board_acl_results = $db->GetRow($board_acl_query);
                if ($board_acl_results) {
                    $board["create"] = $board_acl_results["create"];
                    $board["read"] = $board_acl_results["read"];
                    $board["update"] = $board_acl_results["update"];
                    $board["delete"] = $board_acl_results["delete"];
                    $board["assertion"] = $board_acl_results["assertion"];
                } else {
                    $board["update"] = 0;
                    $board["delete"] = 0;
                    $board["assertion"] = "CourseCommunityEnrollment";
                }

                $insert_query = "INSERT INTO `community_acl` (`resource_type`, `resource_value`, `create`, `read`, `update`, `delete`, `assertion`)
                VALUES (
                    'communitydiscussion',
                    ".$db->qstr($board["id"]).",
                    ".$db->qstr($board["create"]).",
                    ".$db->qstr($board["read"]).",
                    ".$db->qstr($board["update"]).",
                    ".$db->qstr($board["delete"]).",
                    ".$db->qstr($board["assertion"])."
                )";
                $insert_result = $db->Execute($insert_query);
                if (!$insert_result) {
                    print "\n";
                    print $class_name . ": " . $this->color("Failed to migrate community discussion board #{$board["id"]}. " . $db->ErrorMsg(), "red");
                }
            }
        }

        // Get the migrated folders so that they don't all have to be checked
        $migrated_folders_query = "SELECT `resource_value` FROM `community_acl` WHERE `resource_type` = 'communityfolder'";
        $migrated_folders_results = $db->GetAll($migrated_folders_query);
        if ($migrated_folders_results) {
            $migrated_folders = array_map(function($item) {
                return (int) $item["resource_value"];
            }, $migrated_folders_results);
        } else {
            $migrated_folders = array(0);
        }

        // Migration of folders
        $all_folders_query = "SELECT a.`cshare_id` AS `id`, a.`allow_member_upload` AS `create`, a.`allow_member_read` AS `read`, a.`allow_member_comment` AS `update`
                                FROM `community_shares` AS a
                                JOIN `community_courses` AS b
                                ON  b.`community_id` = a.`community_id`
                                WHERE a.`cshare_id` NOT IN (".implode(",", $migrated_folders).")
                                GROUP BY `id`
                                ORDER BY `id` ASC";
        $shared_folders = $db->GetAll($all_folders_query);
        if ($shared_folders) {
            $total = count($shared_folders);
            print "\n";
            print $class_name . ": Task: " . $this->color("Migrating {$total} community shared folders.", "pink");

            foreach ($shared_folders as $folder) {
                $insert_query = "INSERT INTO `community_acl` (`resource_type`, `resource_value`, `create`, `read`, `update`, `delete`, `assertion`)
                VALUES (
                    'communityfolder',
                    ".$db->qstr($folder["id"]).",
                    ".$db->qstr($folder["create"]).",
                    ".$db->qstr($folder["read"]).",
                    ".$db->qstr($folder["update"]).",
                    0,
                    'CourseCommunityEnrollment'
                )";

                $insert_result = $db->Execute($insert_query);
                if (!$insert_result) {
                    print "\n";
                    print $class_name . ": " . $this->color("Failed to migrate community shared folders #{$folder["id"]}. " . $db->ErrorMsg(), "red");
                }
            }
        }

        // Get the migrated files so that they don't all have to be checked
        $migrated_files_query = "SELECT `resource_value` FROM `community_acl` WHERE `resource_type` = 'communityfile'";
        $migrated_files_results = $db->GetAll($migrated_files_query);
        if ($migrated_files_results) {
            $migrated_files = array_map(function($item) {
                return (int) $item["resource_value"];
            }, $migrated_files_results);
        } else {
            $migrated_files = array(0);
        }

        // Migration of files
        $all_files_query = "SELECT a.`csfile_id` AS `id`, a.`allow_member_read` AS `read`, a.`allow_member_revision` AS `update`
                            FROM `community_share_files` AS a
                            JOIN `community_courses` AS b
                            ON  b.`community_id` = a.`community_id`
                            WHERE a.`csfile_id` NOT IN (".implode(",", $migrated_files).")
                            GROUP BY `id`
                            ORDER BY `id` ASC";
        $shared_files = $db->GetAll($all_files_query);
        if ($shared_files) {
            $total = count($shared_files);
            print "\n";
            print $class_name . ": Task: " . $this->color("Migrating {$total} community shared files.", "pink");

            foreach ($shared_files as $file) {
                $insert_query = "INSERT INTO `community_acl` (`resource_type`, `resource_value`, `create`, `read`, `update`, `delete`, `assertion`)
                VALUES (
                    'communityfile',
                    ".$db->qstr($file["id"]).",
                    0,
                    ".$db->qstr($file["read"]).",
                    ".$db->qstr($file["update"]).",
                    0,
                    'CourseCommunityEnrollment'
                )";

                $insert_result = $db->Execute($insert_query);
                if (!$insert_result) {
                    print "\n";
                    print $class_name . ": " . $this->color("Failed to migrate community document share file #{$file["id"]}. " . $db->ErrorMsg(), "red");
                }
            }
        }

        // Get the migrated links so that they don't all have to be checked
        $migrated_links_query = "SELECT `resource_value` FROM `community_acl` WHERE `resource_type` = 'communitylink'";
        $migrated_links_results = $db->GetAll($migrated_links_query);
        if ($migrated_links_results) {
            $migrated_links = array_map(function($item) {
                return (int) $item["resource_value"];
            }, $migrated_links_results);
        } else {
            $migrated_links = array(0);
        }

        // Migration of links
        $all_links_query = "SELECT a.`cslink_id` AS `id`, a.`allow_member_read` AS `read`, a.`allow_member_revision` AS `update`
                            FROM `community_share_links` AS a
                            JOIN `community_courses` AS b
                            ON  b.`community_id` = a.`community_id`
                            WHERE a.`cslink_id` NOT IN (".implode(",", $migrated_links).")
                            GROUP BY `id`
                            ORDER BY `id` ASC";
        $shared_links = $db->GetAll($all_links_query);
        if ($shared_links) {
            $total = count($shared_links);
            print "\n";
            print $class_name . ": Task: " . $this->color("Migrating {$total} community shared links.", "pink");

            foreach ($shared_links as $link) {
                $insert_query = "INSERT INTO `community_acl` (`resource_type`, `resource_value`, `create`, `read`, `update`, `delete`, `assertion`)
                VALUES (
                    'communitylink',
                    ".$db->qstr($link["id"]).",
                    0,
                    ".$db->qstr($link["read"]).",
                    ".$db->qstr($link["update"]).",
                    0,
                    'CourseCommunityEnrollment'
                )";

                $insert_result = $db->Execute($insert_query);
                if (!$insert_result) {
                    print "\n";
                    print $class_name . ": " . $this->color("Failed to migrate community document share link #{$link["id"]}. " . $db->ErrorMsg(), "red");
                }
            }
        }
        
        return $sql_result;
    }

    /**
     * Required: SQL / PHP that performs the downgrade migration.
     */
    public function down() {
        $this->record();
        ?>
        DROP TABLE IF EXISTS `community_discussions_files`, `community_discussion_file_versions`, `community_discussions_open`;

        ALTER TABLE `community_discussions` DROP COLUMN `forum_category`;
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
        if ($migration->tableExists(DATABASE_NAME, "community_discussions_files")) {
            if ($migration->tableExists(DATABASE_NAME, "community_discussion_file_versions")) {
                if ($migration->tableExists(DATABASE_NAME, "community_discussions_open")) {
                    if ($migration->columnExists(DATABASE_NAME, "community_discussions", "forum_category")) {
                        return 1;
                    }
                }
            }
        }

        return 0;
    }
}