<?php
class Migrate_2015_10_15_105816_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_assessment_distributions` ADD COLUMN `flagging_notifications` enum('disabled','reviewers','pcoordinators','directors','authors') NOT NULL DEFAULT 'disabled' AFTER `submittable_by_target`;
        ALTER TABLE `cbl_assessment_notifications` MODIFY COLUMN `notification_type` enum('delegator_start','delegator_late','assessor_start','assessor_reminder','assessor_late','flagged_response') NOT NULL DEFAULT 'assessor_start';
        INSERT INTO `settings` (`shortname`, `organisation_id`, `value`) VALUES ('flagging_notifications', 1, '1');
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
        ALTER TABLE `cbl_assessment_distributions` DROP COLUMN `flagging_notifications`;
        ALTER TABLE `cbl_assessment_notifications` MODIFY COLUMN `notification_type` enum('delegator_start','delegator_late','assessor_start','assessor_reminder','assessor_late') NOT NULL DEFAULT 'assessor_start';
        DELETE FROM `settings` WHERE `shortname` = 'flagging_notifications';
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
        $query = "SHOW COLUMNS FROM `cbl_assessment_distributions` LIKE 'flagging_notifications'";
        $column = $db->GetRow($query);
        if ($column) {
            $query = "SELECT * FROM `settings` WHERE `shortname` = 'flagging_notifications'";
            $settings_record = $db->GetRow($query);
            if ($settings_record) {
                return 1;
            }
        }
        return 0;
    }
}
