<?php
class Migrate_2016_09_13_122218_1118 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `reports_aamc_ci` ADD `program_level_objective_id` INT(12)  NULL  DEFAULT NULL  AFTER `report_supporting_link`;
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
        ALTER TABLE `reports_aamc_ci` DROP `program_level_objective_id`;
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
        if ($migration->columnExists(DATABASE_NAME, "reports_aamc_ci", "program_level_objective_id")) {
            return 1;
        } else {
            return 0;
        }
    }
}
