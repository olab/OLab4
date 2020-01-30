<?php
class Migrate_2016_07_18_161953_175 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        INSERT INTO `event_lu_resource_types` (`resource_type`, `description`, `updated_date`, `updated_by`, `active`)
        VALUES ('Exam', 'Attach an exam to this learning event.', UNIX_TIMESTAMP(), 1, 1);
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
        DELETE
        FROM `event_lu_resource_types`
        WHERE `resource_type` = 'Exam';
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
        $query1 = "
            SELECT 1
            FROM `event_lu_resource_types`
            WHERE `resource_type` = 'Exam'";

        if ($db->GetOne($query1)) {
            return 1;
        }
        return 0;
    }
}
