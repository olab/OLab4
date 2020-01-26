<?php
class Migrate_2016_09_06_143101_1101 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE `cbl_assessment_distribution_methods` (
        `admethod_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `method_title` varchar(128) NOT NULL,
        `created_by` int(11) DEFAULT NULL,
        `created_date` bigint(64) unsigned DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `updated_date` bigint(64) unsigned DEFAULT NULL,
        `deleted_date` bigint(64) unsigned DEFAULT NULL,
        PRIMARY KEY (`admethod_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        INSERT INTO `cbl_assessment_distribution_methods` (`admethod_id`, `method_title`, `created_by`, `created_date`, `updated_by`, `updated_date`, `deleted_date`)
        VALUES (null, "Rotation Schedule", 1, UNIX_TIMESTAMP(NOW()), null, null, null),
        (null, "Delegation", 1, UNIX_TIMESTAMP(NOW()), null, null, null),
        (null, "Learning Event", 1, UNIX_TIMESTAMP(NOW()), null, null, null),
        (null, "Date Range", 1, UNIX_TIMESTAMP(NOW()), null, null, null);
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
        DROP TABLE `cbl_assessment_distribution_methods`;
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
        $migration = new Models_Migration();

        if ($migration->tableExists(DATABASE_NAME, "cbl_assessment_distribution_methods")) {
            return 1;
        }
        return 0;
    }
}
