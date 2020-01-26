<?php
class Migrate_2017_06_22_090031_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE IF NOT EXISTS `cbl_cbme_upload_history` (
        `file_name` char(255) NOT NULL,
        `file_type` char(255) NOT NULL,
        `course_id` int(11) NOT NULL,
        `upload_type` char(255) NOT NULL,
        `created_date` bigint(64) unsigned NOT NULL,
        `created_by` int(11) unsigned NOT NULL,
        PRIMARY KEY (`created_by`, `created_date`)
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
        DROP TABLE `cbl_cbme_upload_history`;
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
        if ($migration->tableExists(DATABASE_NAME, "cbl_cbme_upload_history")) {
            return 1;
        }
        return 0;
    }
}
