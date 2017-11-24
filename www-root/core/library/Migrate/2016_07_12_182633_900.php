<?php
class Migrate_2016_07_12_182633_900 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
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
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

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
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
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
        DROP TABLE `secure_access_files`;
        DROP TABLE `secure_access_keys`;
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

        if ($migration->tableExists(DATABASE_NAME, "secure_access_files")) {
            if ($migration->tableExists(DATABASE_NAME, "secure_access_keys")) {

                return 1;
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }
}
