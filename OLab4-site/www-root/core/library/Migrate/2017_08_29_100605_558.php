<?php
class Migrate_2017_08_29_100605_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_assessment_distributions` ADD COLUMN `exclude_self_assessments` tinyint(1) DEFAULT 0 AFTER `target_option`;
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
        ALTER TABLE `cbl_assessment_distributions` DROP COLUMN `exclude_self_assessments`;
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
        $migrate = new Models_Migration();
        if ($migrate->columnExists(DATABASE_NAME, "cbl_assessment_distributions", "exclude_self_assessments")) {
            return 1;
        }
        return 0;
    }
}
