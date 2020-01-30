<?php
class Migrate_2015_09_29_101115_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>

        ALTER TABLE `cbl_assessment_notifications` ADD COLUMN `dassessment_id` int(11) unsigned NOT NULL AFTER `adistribution_id`;

        UPDATE `cbl_assessment_notifications` SET `dassessment_id` =
        (
            SELECT `record_id` FROM `notification_users`
            WHERE `notification_users`.`nuser_id` = `cbl_assessment_notifications`.`nuser_id`
        );

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
        ALTER TABLE `cbl_assessment_notifications` DROP COLUMN `dassessment_id`;
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
        $query = "SHOW COLUMNS FROM `cbl_assessment_notifications` LIKE 'dassessment_id'";
        $column = $db->GetRow($query);
        if ($column) {
            return 1;
        }
        return 0;
    }
}
