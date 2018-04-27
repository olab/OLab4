<?php
class Migrate_2018_02_21_142251_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE `cbl_assessments_lu_item_response_objectives` (
            `irobjective_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `iresponse_id` int(11) unsigned NOT NULL,
            `objective_id` int(11) unsigned NOT NULL,
            `created_date` bigint(64) NOT NULL,
            `created_by` int(11) NOT NULL,
            `updated_date` bigint(64) DEFAULT NULL,
            `updated_by` int(11) DEFAULT NULL,
            `deleted_date` bigint(64) DEFAULT NULL,
            PRIMARY KEY (`irobjective_id`),
            KEY `iresponse_id` (`iresponse_id`),
            KEY `objective_id` (`objective_id`)
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
        DROP TABLE `cbl_assessments_lu_item_response_objectives`;
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

        if ($migration->tableExists(DATABASE_NAME, "cbl_assessments_lu_item_response_objectives")) {
            return 1;
        }

        return 0;
    }
}
