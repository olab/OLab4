<?php
class Migrate_2017_09_22_110736_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE IF NOT EXISTS `user_access_requests` (
        `user_access_request_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
        `receiving_proxy_id` int(12) unsigned NOT NULL,
        `requested_user_firstname` varchar(35) NOT NULL,
        `requested_user_lastname` varchar(35) NOT NULL,
        `requested_user_email` varchar(255) NOT NULL,
        `requested_user_number` int(12) DEFAULT NULL,
        `requested_group` varchar(35) NOT NULL,
        `requested_role` varchar(35) NOT NULL,
        `additional_comments` text,
        `created_date` bigint(64) unsigned NOT NULL,
        `created_by` int(12) unsigned NOT NULL,
        `updated_date` bigint(64) unsigned DEFAULT NULL,
        `updated_by` int(12) unsigned DEFAULT NULL,
        `deleted_date` bigint(64) unsigned DEFAULT NULL,
        PRIMARY KEY (`user_access_request_id`)
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
        DROP TABLE IF EXISTS `user_access_request`;
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

        if ($migration->tableExists(DATABASE_NAME, "user_access_requests")) {
            return 1;
        }

        return 0;
    }
}
