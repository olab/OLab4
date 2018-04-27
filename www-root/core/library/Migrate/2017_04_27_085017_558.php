<?php
class Migrate_2017_04_27_085017_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE `event_feedback_forms` (
        `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `event_id` int(11) NOT NULL DEFAULT '0',
        `form_id` int(11) NOT NULL,
        `required` int(1) NOT NULL DEFAULT '0',
        `timeframe` varchar(64) NOT NULL DEFAULT 'none',
        `accesses` int(11) NOT NULL DEFAULT '0',
        `release_date` int(11) NOT NULL,
        `release_until` int(11) NOT NULL,
        `created_by` int(11) NOT NULL,
        `created_date` int(11) NOT NULL,
        `updated_by` int(11) NOT NULL,
        `updated_date` int(11) NOT NULL,
        `deleted_date` int(11) DEFAULT NULL,
        PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        INSERT INTO `event_lu_resource_types` (`event_resource_type_id`, `resource_type`, `description`, `updated_date`, `updated_by`, `active`)
        VALUES (13, 'Feedback Form', 'Attach a feedback form to this learning event.', UNIX_TIMESTAMP(), 1, 1);
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
        DROP TABLE `event_feedback_forms`;
        DELETE FROM `event_lu_resource_types` WHERE `resource_type` = 'Feedback Form';
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
        $migration = new Models_Migration();

        $query = "SELECT * FROM `event_lu_resource_types` WHERE `resource_type` = 'Feedback Form'";
        $found = $db->GetRow($query);
        if ($migration->tableExists(DATABASE_NAME, "event_feedback_forms") && $found) {
            return 1;
        }
        return 0;
    }
}
