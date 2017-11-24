<?php
class Migrate_2016_07_11_085528_942 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
	ALTER TABLE `cbl_assessment_lu_task_deleted_reasons` ADD COLUMN `order_id` int(11) UNSIGNED NOT NULL AFTER `reason_id`;
	UPDATE `cbl_assessment_lu_task_deleted_reasons` SET `order_id` = 3 WHERE `reason_id` = 1;
	INSERT INTO `cbl_assessment_lu_task_deleted_reasons` SET `reason_id` = 2, `order_id` = 1, `reason_details` = 'Did not work with the target', `notes_required` = 0, `created_date` = 1456515087, `created_by` = 1;
	INSERT INTO `cbl_assessment_lu_task_deleted_reasons` SET `reason_id` = 3, `order_id` = 2, `reason_details` = 'Completed all relevant tasks on relevant targets', `notes_required` = 0, `created_date` = 1456515087, `created_by` = 1;
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
	ALTER TABLE `cbl_assessment_lu_task_deleted_reasons` DROP COLUMN `order_id`;
	DELETE FROM `cbl_assessment_lu_task_deleted_reasons` WHERE `reason_id` = 2;
	DELETE FROM `cbl_assessment_lu_task_deleted_reasons` WHERE `reason_id` = 3;
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
        if ($migration->columnExists(DATABASE_NAME, "cbl_assessment_lu_task_deleted_reasons", "order_id")) {
			return 1;
        } else {
			return 0;
		}
    }
}
