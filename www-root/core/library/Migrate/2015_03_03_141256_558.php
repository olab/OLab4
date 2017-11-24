<?php
class Migrate_2015_03_03_141256_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_assessment_progress` ADD COLUMN `one45_p_id` int(11) DEFAULT NULL AFTER `one45_formsAttached_id`;
        ALTER TABLE `cbl_assessment_progress` ADD COLUMN `one45_moment_id` int(11) DEFAULT NULL AFTER `one45_p_id`;
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
        ALTER TABLE `cbl_assessment_progress` DROP COLUMN `one45_p_id`;
        ALTER TABLE `cbl_assessment_progress` DROP COLUMN `one45_moment_id`;
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
        return -1;
    }
}
