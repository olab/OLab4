<?php
class Migrate_2015_07_15_134443_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE `cbl_assessment_distribution_reviewers` (
        `adreviewer_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `adistribution_id` int(11) unsigned NOT NULL,
        `proxy_id` int(11) NOT NULL,
        `created_date` bigint(64) NOT NULL,
        `created_by` int(11) NOT NULL,
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(11) DEFAULT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`adreviewer_id`),
        KEY `adistribution_id` (`adistribution_id`),
        CONSTRAINT `cbl_assessment_distribution_reviewers_ibfk_1` FOREIGN KEY (`adistribution_id`) REFERENCES `cbl_assessment_distributions` (`adistribution_id`)
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
        DROP TABLE IF EXISTS `cbl_assessment_distribution_reviewers`;
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

        $query = "SHOW TABLES LIKE 'cbl_assessment_distribution_reviewers'";
        $table = $db->GetRow($query);
        if ($table) {
            return 1;
        } else {
            return 0;
        }
    }
}
