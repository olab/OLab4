<?php
class Migrate_2016_09_07_192943_1548 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
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
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
        <?php
        $this->stop();

        if ((!@is_dir(EXAM_STORAGE_PATH))) {
            mkdir(EXAM_STORAGE_PATH);
        }

        return $this->run();
    }

    /**
     * Required: SQL / PHP that performs the downgrade migration.
     */
    public function down() {
        $this->record();
        ?>
        DROP TABLE `exam_attached_files`;
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

        // Check for new field
        if ($migration->tableExists(DATABASE_NAME, "exam_attached_files")) {
            return 1;
        } else {
            return 0;
        }
    }
}
