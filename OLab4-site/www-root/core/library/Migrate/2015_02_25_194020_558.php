<?php
class Migrate_2015_02_25_194020_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        -- SQL Upgrade Queries Here;
        CREATE TABLE `cbl_leave_tracking` (
        `leave_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `proxy_id` int(11) NOT NULL,
        `leave_type` enum('vacation','conference','interview','sick','other','maternity','paternity','absence','stat days','research','education days','professional development','study days','academic half day') NOT NULL DEFAULT 'vacation',
        `start_date` int(11) NOT NULL,
        `end_date` int(11) NOT NULL,
        `created_date` int(11) NOT NULL,
        `created_by` int(11) NOT NULL,
        `updated_date` int(11) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `deleted_date` int(11) DEFAULT NULL,
        PRIMARY KEY (`leave_id`)
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
        -- SQL Downgrade Queries Here;
        DROP TABLE `cbl_leave_tracking`;
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
