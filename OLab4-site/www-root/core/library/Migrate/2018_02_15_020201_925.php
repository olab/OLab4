<?php
class Migrate_2018_02_15_020201_925 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE IF NOT EXISTS `learning_objects` (
        `learning_object_id` int(12) NOT NULL AUTO_INCREMENT,
        `title` varchar(255) NOT NULL DEFAULT '',
        `description` text,
        `primary_usage` varchar(255) DEFAULT NULL,
        `tool` varchar(255) DEFAULT NULL,
        `object_type` enum('link','tincan','scorm') NOT NULL DEFAULT 'link',
        `url` text NOT NULL,
        `filename` varchar(255) DEFAULT NULL,
        `filename_hashed` varchar(255) DEFAULT NULL,
        `screenshot_filename` varchar(255) NOT NULL DEFAULT '',
        `viewable_start` bigint(64) DEFAULT NULL,
        `viewable_end` bigint(64) DEFAULT NULL,
        `created_date` bigint(64) DEFAULT NULL,
        `created_by` int(11) DEFAULT NULL,
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`learning_object_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `learning_object_authors` (
        `id` int(12) NOT NULL AUTO_INCREMENT,
        `author_id` int(12) NOT NULL COMMENT 'This value is either entrada_auth.user_data.id if auth_type is Internal, entrada.lo_external_authors.eauthor_id if auth_type is External.',
        `author_type` enum('Internal','External') NOT NULL DEFAULT 'Internal',
        `learning_object_id` int(12) NOT NULL,
        PRIMARY KEY (`id`),
        KEY `learning_object_id` (`learning_object_id`),
        CONSTRAINT `fk_learning_object` FOREIGN KEY (`learning_object_id`) REFERENCES `learning_objects` (`learning_object_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `learning_object_external_authors` (
        `eauthor_id` int(12) NOT NULL AUTO_INCREMENT,
        `firstname` varchar(255) NOT NULL DEFAULT '',
        `lastname` varchar(255) NOT NULL DEFAULT '',
        `email` varchar(255) NOT NULL DEFAULT '',
        `created_date` int(11) NOT NULL,
        `created_by` int(11) NOT NULL,
        `updated_date` int(11) NOT NULL,
        `updated_by` int(11) NOT NULL,
        `deleted_date` int(11) DEFAULT NULL,
        PRIMARY KEY (`eauthor_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS `learning_objects_progress` (
        `learning_objects_progress_id` INT(12) NOT NULL AUTO_INCREMENT,
        `proxy_id` INT(11) NOT NULL DEFAULT '0',
        `learning_objects_activity_id` VARCHAR(255) NOT NULL,
        `learning_objects_state_id` VARCHAR(255) NOT NULL,
        `data` TEXT NOT NULL,
        `created_date` BIGINT(64) NOT NULL DEFAULT '0',
        PRIMARY KEY (`learning_objects_progress_id`),
        INDEX `proxy_id` (`proxy_id`),
        INDEX `learning_objects_activity_id` (`learning_objects_activity_id`),
        INDEX `learning_objects_state_id` (`learning_objects_state_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        INSERT INTO `<?php echo AUTH_DATABASE; ?>`.`acl_permissions` (`resource_type`, `resource_value`, `entity_type`, `entity_value`, `app_id`, `create`, `read`, `update`, `delete`)
        VALUES
        ('lor', NULL, 'group:role', 'staff:admin', NULL, 1, 1, 1, 1),
        ('lor', NULL, 'group:role', 'medtech:admin', NULL, 1, 1, 1, 1);

        INSERT INTO `settings` (`setting_id`, `shortname`, `organisation_id`, `value`)
        VALUES
        (NULL, 'filesystem_hash', NULL, 'sha256'),
        (NULL, 'filesystem_chunksplit', NULL, '8');
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
        SET FOREIGN_KEY_CHECKS = 0;
        DROP TABLE `learning_objects`;
        DROP TABLE `learning_object_authors`;
        DROP TABLE `learning_object_external_authors`;
        DROP TABLE `learning_objects_progress`;
        SET FOREIGN_KEY_CHECKS = 1;

        DELETE FROM `<?php echo AUTH_DATABASE; ?>`.`acl_permissions` WHERE `resource_type` = 'lor';

        DELETE FROM `settings` WHERE `shortname` = "filesystem_hash" OR `shortname` = "filesystem_chunksplit";
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

        if ($migration->tableExists(DATABASE_NAME, "learning_objects")) {
            if ($migration->tableExists(DATABASE_NAME, "learning_object_authors")) {
                if ($migration->tableExists(DATABASE_NAME, "learning_object_external_authors")) {
                    if ($migration->tableExists(DATABASE_NAME, "learning_objects_progress")) {
                        return 1;
                    }
                }
            }
        }

        return 0;
    }
}
