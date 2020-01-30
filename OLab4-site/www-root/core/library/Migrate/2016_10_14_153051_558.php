<?php
class Migrate_2016_10_14_153051_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE `cbl_course_contacts` (
        `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `course_id` int(11) NOT NULL,
        `assessor_value` int(11) NOT NULL,
        `assessor_type` enum('internal','external') NOT NULL DEFAULT 'internal',
        `visible` tinyint(1) DEFAULT '1',
        `created_by` int(11) NOT NULL,
        `created_date` int(11) NOT NULL,
        `updated_by` int(11) NOT NULL,
        `updated_date` int(11) NOT NULL,
        `deleted_date` int(11) DEFAULT NULL,
        PRIMARY KEY (`id`)
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
        DROP TABLE `cbl_course_contacts`;
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

        if ($migration->tableExists(DATABASE_NAME, "cbl_course_contacts")) {
            return 1;
        }
        return 0;
    }
}
