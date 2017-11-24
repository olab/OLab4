<?php
class Migrate_2015_08_12_145426_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        UPDATE `cbl_leave_tracking` AS t
        JOIN cbl_lu_leave_tracking_types AS lu ON
        t.`leave_type` = LOWER(lu.`type_value`)
        SET t.`type_id` = lu.`type_id`;

        ALTER TABLE `cbl_leave_tracking` DROP COLUMN `leave_type`;
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
        ALTER TABLE `cbl_leave_tracking` ADD COLUMN `leave_type` ENUM('vacation','conference','interview','sick','other','maternity','paternity','absence','stat days','research','education days','professional development','study days','academic half day') DEFAULT 'vacation' AFTER `proxy_id`;

        UPDATE `cbl_leave_tracking` AS t
        JOIN cbl_lu_leave_tracking_types AS lu ON
        t.`type_id` = lu.`type_id`
        SET t.`leave_type` = LOWER(lu.`type_value`);
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
        $query = "SHOW COLUMNS FROM `cbl_leave_tracking` LIKE 'leave_type'";
        $column = $db->GetRow($query);
        if (!$column) {
            return 1;
        } else {
            return 0;
        }
    }
}
