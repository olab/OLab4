<?php
class Migrate_2017_03_31_094258_900 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `rp_now_config` DROP COLUMN `ssi_record_locator`;
        ALTER TABLE `rp_now_config` DROP COLUMN `exam_code`;

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
        `deleted_by`int(11) DEFAULT NULL,
        PRIMARY KEY (`rpnow_id`),
        KEY `rp_now_user_fk_1` (`rpnow_config_id`),
        CONSTRAINT `rp_now_user_fk_1` FOREIGN KEY (`rpnow_config_id`) REFERENCES `rp_now_config` (`rpnow_id`)
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
        ALTER TABLE `rp_now_config` ADD COLUMN `ssi_record_locator` varchar(50) NOT NULL;
        ALTER TABLE `rp_now_config` ADD COLUMN `exam_code` varchar(20) NOT NULL;
        DROP TABLE `rp_now_users`;
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
        if (!$migration->columnExists(DATABASE_NAME, "rp_now_config", "ssi_record_locator") && !$migration->columnExists(DATABASE_NAME, "rp_now_config", "exam_code") && $migration->tableExists(DATABASE_NAME, "rp_now_users")) {
            return 1;
        }
        return 0;
    }
}
