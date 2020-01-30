<?php
class Migrate_2017_03_01_161920_900 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `exam_posts`
        ADD COLUMN `secure_mode` varchar(32) DEFAULT NULL AFTER `resume_password`;

        UPDATE `exam_posts` SET `secure_mode` = 'seb' WHERE `secure` = 1;

        CREATE TABLE `rp_now_config` (
        `rpnow_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `exam_url` varchar(128) DEFAULT NULL,
        `exam_sponsor` int(11) unsigned DEFAULT NULL,
        `rpnow_reviewed_exam` int(1) DEFAULT '0',
        `rpnow_reviewer_notes` TEXT,
        `ssi_record_locator` varchar(50) NOT NULL,
        `exam_post_id` int(11) unsigned NOT NULL,
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`rpnow_id`),
        KEY `rp_now_config_fk_1` (`exam_post_id`),
        CONSTRAINT `rp_now_config_fk_1` FOREIGN KEY (`exam_post_id`) REFERENCES `exam_posts` (`post_id`)
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
        ALTER TABLE `exam_posts` DROP COLUMN `secure_mode`;
        DROP TABLE `rp_now_config`;
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

        if ($migration->tableExists(DATABASE_NAME, "rp_now_config") && $migration->columnExists(DATABASE_NAME, "rp_now_config", "secure_mode")) {
            return 1;
        }
        return 0;
    }
}
