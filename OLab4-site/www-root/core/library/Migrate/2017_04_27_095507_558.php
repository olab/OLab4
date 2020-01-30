<?php
class Migrate_2017_04_27_095507_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE IF NOT EXISTS `cbl_linked_assessments` (
            `link_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
            `originating_id` int(11) unsigned NOT NULL,
            `linked_id` int(11) unsigned NOT NULL,
            `created_date` bigint(64) NOT NULL,
            `created_by` int(11) NOT NULL,
            `updated_date` bigint(64) DEFAULT NULL,
            `updated_by` int(11) DEFAULT NULL,
            `deleted_date` bigint(64) DEFAULT NULL,
            PRIMARY KEY (`link_id`),
            KEY `originating_id` (`originating_id`)
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
        DROP TABLE `cbl_linked_assessments`;
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

        if ($migration->tableExists(DATABASE_NAME, "cbl_linked_assessments")) {
            return 1;
        }

        return 0;
    }
}
