<?php
class Migrate_2017_05_09_155431_1099 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE `disclaimers` (
        `disclaimer_id` int(11) NOT NULL AUTO_INCREMENT,
        `disclaimer_title` varchar(255) NOT NULL DEFAULT '',
        `disclaimer_issue_date` bigint(64) DEFAULT NULL,
        `disclaimer_expire_date` bigint(64) DEFAULT NULL,
        `disclaimer_text` text NOT NULL,
        `organisation_id` int(11) NOT NULL,
        `upon_decline` enum('continue','log_out') NOT NULL DEFAULT 'continue',
        `email_admin` tinyint(1) NOT NULL DEFAULT 0,
        `created_date` bigint(64) NOT NULL,
        `created_by` int(11) NOT NULL,
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        `deleted_by` int(11) DEFAULT NULL,
        PRIMARY KEY (`disclaimer_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE `disclaimer_audience` (
        `disclaimer_audience_id` int(11) NOT NULL AUTO_INCREMENT,
        `disclaimer_id` int(11) NOT NULL,
        `disclaimer_audience_type` enum('proxy_id','grad_year','cohort','group_id','course_id','cgroup_id','role_id') NOT NULL DEFAULT 'cohort',
        `disclaimer_audience_value` varchar(16) NOT NULL,
        `updated_date` bigint(64) NOT NULL,
        `updated_by` int(11) NOT NULL,
        PRIMARY KEY (`disclaimer_audience_id`),
        KEY `disclaimer_id` (`disclaimer_id`),
        CONSTRAINT `disclaimer_audience_ibfk_1` FOREIGN KEY (`disclaimer_id`) REFERENCES `disclaimers` (`disclaimer_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE `disclaimer_audience_users` (
        `disclaimer_audience_users_id` int(11) NOT NULL AUTO_INCREMENT,
        `disclaimer_id` int(11) NOT NULL,
        `proxy_id` int(11) NOT NULL,
        `approved` tinyint(1) NOT NULL DEFAULT '0',
        `updated_date` bigint(64) NOT NULL,
        `updated_by` int(11) NOT NULL,
        PRIMARY KEY (`disclaimer_audience_users_id`),
        KEY `disclaimer_id` (`disclaimer_id`),
        KEY `proxy_id` (`proxy_id`),
        CONSTRAINT `disclaimer_audience_users_ibfk_1` FOREIGN KEY (`disclaimer_id`) REFERENCES `disclaimers` (`disclaimer_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        INSERT INTO `<?php echo AUTH_DATABASE; ?>`.`acl_permissions` (`permission_id`, `resource_type`, `resource_value`, `entity_type`, `entity_value`, `app_id`, `create`, `read`, `update`, `delete`, `assertion`)
        VALUES
        (NULL, 'disclaimers', NULL, NULL, NULL, 1, NULL, 1, NULL, NULL, 'NotGuest'),
        (NULL, 'disclaimers', NULL, 'group:role', 'staff:admin', 1, 1, NULL, 1, 1, NULL);

        INSERT INTO `<?php echo AUTH_DATABASE; ?>`.`acl_permissions` (`permission_id`, `resource_type`, `resource_value`, `entity_type`, `entity_value`, `app_id`, `create`, `read`, `update`, `delete`, `assertion`)
        VALUES
        (NULL, 'disclaimer_audience', NULL, NULL, NULL, 1, NULL, 1, NULL, NULL, 'NotGuest'),
        (NULL, 'disclaimer_audience', NULL, 'group:role', 'staff:admin', 1, 1, NULL, 1, 1, NULL);

        INSERT INTO `<?php echo AUTH_DATABASE; ?>`.`acl_permissions` (`permission_id`, `resource_type`, `resource_value`, `entity_type`, `entity_value`, `app_id`, `create`, `read`, `update`, `delete`, `assertion`)
        VALUES
        (NULL, 'disclaimer_audience_users', NULL, NULL, NULL, 1, NULL, 1, NULL, NULL, 'NotGuest'),
        (NULL, 'disclaimer_audience_users', NULL, 'group:role', 'staff:admin', 1, 1, NULL, 1, 1, NULL);
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
        DROP TABLE `disclaimers`;
        DROP TABLE `disclaimer_audience`;
        DROP TABLE `disclaimer_audience_users`;
        DELETE FROM `<?php echo AUTH_DATABASE; ?>`.`acl_permissions` WHERE `resource_type` = 'disclaimers';
        DELETE FROM `<?php echo AUTH_DATABASE; ?>`.`acl_permissions` WHERE `resource_type` = 'disclaimer_audience';
        DELETE FROM `<?php echo AUTH_DATABASE; ?>`.`acl_permissions` WHERE `resource_type` = 'disclaimer_audience_users';
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
        if ($migration->tableExists(DATABASE_NAME, "disclaimers") && $migration->tableExists(DATABASE_NAME, "disclaimer_audience")) {
            return 1;
        }
        return 0;
    }
}
