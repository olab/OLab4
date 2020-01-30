<?php
class Migrate_2017_02_09_102028_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE `cbl_assessment_progress_approvals` (
        `adapproval_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `aprogress_id` int(11) unsigned NOT NULL,
        `adistribution_id` int(11) unsigned NOT NULL,
        `approver_id` int(11) NOT NULL,
        `release_status` tinyint(1) NOT NULL DEFAULT '0',
        `comments` text,
        `created_date` bigint(64) NOT NULL,
        `created_by` int(11) NOT NULL,
        PRIMARY KEY (`adapproval_id`),
        KEY `aprogress_id` (`aprogress_id`),
        KEY `adistribution_id` (`adistribution_id`),
        CONSTRAINT `cbl_assessment_progress_approvals_ibfk_1` FOREIGN KEY (`adistribution_id`) REFERENCES `cbl_assessment_distributions` (`adistribution_id`),
        CONSTRAINT `cbl_assessment_progress_approvals_ibfk_2` FOREIGN KEY (`aprogress_id`) REFERENCES `cbl_assessment_progress` (`aprogress_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;


        CREATE TABLE `cbl_assessment_distribution_approvers` (
        `adapprover_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `adistribution_id` int(11) unsigned NOT NULL,
        `proxy_id` int(11) NOT NULL,
        `created_date` bigint(64) NOT NULL,
        `created_by` int(11) NOT NULL,
        PRIMARY KEY (`adapprover_id`),
        KEY `adistribution_id` (`adistribution_id`),
        CONSTRAINT `cbl_assessment_distribution_approvers_ibfk_1` FOREIGN KEY (`adistribution_id`) REFERENCES `cbl_assessment_distributions` (`adistribution_id`)
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
        DROP TABLE `cbl_assessment_progress_approvals`;

        DROP TABLE `cbl_assessment_distribution_approvers`;
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

        if ($migration->tableExists(DATABASE_NAME, "cbl_assessment_progress_approvals") && $migration->tableExists(DATABASE_NAME, "cbl_assessment_distribution_approvers")) {
            return 1;
        }
        return 0;    }
}
