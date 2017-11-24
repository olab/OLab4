<?php
class Migrate_2015_12_10_104135_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_assessment_notifications` MODIFY `notification_type` enum('delegator_start','delegator_late','assessor_start','assessor_reminder','assessor_late','flagged_response','assessment_removal') NOT NULL DEFAULT 'assessor_start';
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
        ALTER TABLE `cbl_assessment_notifications` MODIFY `notification_type` enum('delegator_start','delegator_late','assessor_start','assessor_reminder','assessor_late','flagged_response') NOT NULL DEFAULT 'assessor_start';
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
        global $db;

        $query = "SELECT `column_type` FROM `information_schema`.`columns`
                  WHERE `table_name` = 'cbl_assessment_notifications' AND `column_name` = 'notification_type'";
        $column_info = $db->GetOne($query);
        if ($column_info && $column_info == "enum('delegator_start','delegator_late','assessor_start','assessor_reminder','assessor_late','flagged_response','assessment_removal')") {
            return 1;
        } else {
            return 0;
        }
    }
}
