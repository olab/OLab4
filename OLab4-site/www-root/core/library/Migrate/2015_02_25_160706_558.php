<?php
class Migrate_2015_02_25_160706_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE IF NOT EXISTS `cbl_distribution_assessments` (
        `dassessment_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `adistribution_id` int(11) unsigned DEFAULT NULL,
        `proxy_id` int(11) unsigned NOT NULL,
        `number_submitted` int(11) unsigned DEFAULT '0',
        `min_submittable` int(11) unsigned DEFAULT '0',
        `max_submittable` int(11) unsigned DEFAULT '0',
        `start_date` BIGINT(64) NOT NULL DEFAULT '0',
        `end_date` BIGINT(64) NOT NULL DEFAULT '0',
        `deleted_date` BIGINT(64) DEFAULT NULL,
        PRIMARY KEY (`dassessment_id`)
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
        DROP TABLE `cbl_distribution_assessor_list`
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
