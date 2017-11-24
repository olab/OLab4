<?php
class Migrate_2015_07_07_112833_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_assessment_distributions`
        ADD `release_start_date` bigint(64) NOT NULL AFTER `end_date`;
        ALTER TABLE `cbl_assessment_distributions`
        ADD `release_end_date` bigint(64) NOT NULL AFTER `release_start_date`;

        CREATE TABLE `cbl_assessment_notifications` (
        `anotification_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `adistribution_id` int(11) unsigned NOT NULL,
        `notification_id` int(11) unsigned NOT NULL,
        `nuser_id` int(11) unsigned NOT NULL,
        `notification_type` enum('delegator_start','delegator_late','assessor_start','assessor_reminder','assessor_late') NOT NULL DEFAULT 'assessor_start',
        `schedule_id` int(11) unsigned DEFAULT NULL,
        `sent_date` bigint(64) NOT NULL,
        PRIMARY KEY (`anotification_id`),
        KEY `adistribution_id` (`adistribution_id`),
        KEY `schedule_id` (`schedule_id`),
        CONSTRAINT `cbl_assessment_notifications_ibfk_2` FOREIGN KEY (`schedule_id`) REFERENCES `cbl_schedule` (`schedule_id`),
        CONSTRAINT `cbl_assessment_notifications_ibfk_1` FOREIGN KEY (`adistribution_id`) REFERENCES `cbl_assessment_distributions` (`adistribution_id`)
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
        ALTER TABLE `cbl_assessment_distributions` DROP `release_start_date`;
        ALTER TABLE `cbl_assessment_distributions` DROP `release_end_date`;
        DROP TABLE IF EXISTS `cbl_assessment_notifications`;
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

        $query = "SHOW COLUMNS FROM `cbl_assessment_distributions` LIKE 'release_start_date'";
        $column = $db->GetRow($query);
        if ($column) {
            $query = "SHOW COLUMNS FROM `cbl_assessment_distributions` LIKE 'release_end_date'";
            $column = $db->GetRow($query);
            if ($column) {
                $query = "SHOW TABLES LIKE 'cbl_assessment_notifications'";
                $table = $db->GetRow($query);
                if ($table) {
                    return 1;
                } else {
                    return 0;
                }
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }
}
