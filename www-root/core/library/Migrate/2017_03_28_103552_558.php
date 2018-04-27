<?php
class Migrate_2017_03_28_103552_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_assessment_ss_future_tasks` MODIFY `additional_assessment` tinyint(1) NOT NULL DEFAULT '0';
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
        ALTER TABLE `cbl_assessment_ss_future_tasks` MODIFY `additional_assessment` tinyint(1) DEFAULT '0' AFTER `end_date`;
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
        $meta = $migration->fieldMetadata(DATABASE_NAME, "cbl_assessment_ss_future_tasks", "additional_assessment");
        if ($meta["Null"] == "NO") {
            return 1;
        }
        return -1;
    }
}
