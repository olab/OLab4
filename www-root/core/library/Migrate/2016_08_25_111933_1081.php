<?php
class Migrate_2016_08_25_111933_1081 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE `assessment_statistics` (
        `assessment_statistic_id` int(12) NOT NULL AUTO_INCREMENT,
        `proxy_id` int(12) NOT NULL,
        `created_date` bigint(64) DEFAULT NULL,
        `module` varchar(64) DEFAULT NULL,
        `sub_module` varchar(64) DEFAULT NULL,
        `action` varchar(64) DEFAULT NULL,
        `assessment_id` varchar(64) NOT NULL,
        `distribution_id` varchar(64) NOT NULL,
        `target_id` varchar(64) NOT NULL,
        `progress_id` varchar(64) DEFAULT NULL,
        `prune_after` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`assessment_statistic_id`)
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
        DROP TABLE `assessment_statistics`;
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

        if ($migration->tableExists(DATABASE_NAME, "assessment_statistics")) {
            return 1;
        }
        return 0;
    }
}
