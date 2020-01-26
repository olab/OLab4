<?php
class Migrate_2018_03_07_113443_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_assessment_item_objectives` ADD INDEX `objective_id` (`objective_id`);
        ALTER TABLE `cbl_assessment_progress_responses` ADD INDEX `iresponse_id` (`iresponse_id`);
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
        ALTER TABLE `cbl_assessment_item_objectives` DROP INDEX `objective_id`;
        ALTER TABLE `cbl_assessment_progress_responses` DROP INDEX `iresponse_id`;
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
        if (!$migrate->indexExists(DATABASE_NAME, 'cbl_assessment_item_objectives', 'objective_id')) {
            return 0;
        }
        if (!$migrate->indexExists(DATABASE_NAME, 'cbl_assessment_progress_responses', 'iresponse_id')) {
            return 0;
        }
        return 1;
    }
}
