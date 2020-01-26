<?php
class Migrate_2015_02_13_100237_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        -- SQL Upgrade Queries Here;
        CREATE TABLE `cbl_external_assessors` (
        `eassessor_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `firstname` varchar(35) NOT NULL DEFAULT '',
        `lastname` varchar(35) NOT NULL DEFAULT '',
        `email` varchar(255) NOT NULL DEFAULT '',
        `created_date` int(11) NOT NULL,
        `created_by` int(11) NOT NULL,
        `deleted_date` int(11) DEFAULT NULL,
        `updated_date` int(11) NOT NULL,
        `updated_by` int(11) NOT NULL,
        PRIMARY KEY (`eassessor_id`)
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
        DROP TABLE `cbl_external_assessors`
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
