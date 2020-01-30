<?php
class Migrate_2017_05_17_104914_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE IF NOT EXISTS `global_lu_likelihoods` (
        `likelihood_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `title` varchar(128) NOT NULL DEFAULT '',
        `description` varchar(128) NOT NULL DEFAULT '',
        `order` int(11) NOT NULL DEFAULT '0',
        `created_date` bigint(64) unsigned NOT NULL,
        `created_by` int(11) unsigned NOT NULL,
        `updated_date` bigint(64) unsigned NOT NULL,
        `updated_by` int(11) unsigned NOT NULL,
        `deleted_date` bigint(64) unsigned DEFAULT NULL,
        `deleted_by` int(11) unsigned DEFAULT NULL,
        PRIMARY KEY (`likelihood_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

        INSERT INTO `global_lu_likelihoods` (`likelihood_id`, `title`, `description`, `order`, `created_date`, `created_by`, `updated_date`, `updated_by`, `deleted_date`, `deleted_by`)
        VALUES
        (1, 'Unlikely', 'Not very likely to encounter', 0, 1495033048, 1, 1495033048, 1, NULL, NULL),
        (2, 'Likely', 'Likely to encounter', 1, 1495033048, 1, 1495033048, 1, NULL, NULL),
        (3, 'Very Likely', 'Very likely to encounter', 2, 1495033048, 1, 1495033048, 1, NULL, NULL);
        
        CREATE TABLE IF NOT EXISTS `cbl_schedule_course_objectives` (
        `sco_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `course_id` int(11) unsigned NOT NULL,
        `schedule_id` int(11) unsigned NOT NULL,
        `objective_id` int(11) unsigned DEFAULT NULL,
        `likelihood_id` int(11) unsigned DEFAULT NULL,
        `priority`  tinyint(1) NOT NULL DEFAULT 0,
        `created_date` bigint(64) unsigned NOT NULL,
        `created_by` int(11) unsigned NOT NULL,
        `updated_by` int(11) unsigned DEFAULT NULL,
        `updated_date` bigint(64) unsigned DEFAULT NULL,
        `deleted_date` bigint(64) unsigned DEFAULT NULL,
        `deleted_by` int(11) unsigned DEFAULT NULL,
        PRIMARY KEY (`sco_id`),
        KEY `schedule_id` (`schedule_id`),
        CONSTRAINT `cbl_schedule_course_objectives_schedule_id` FOREIGN KEY (`schedule_id`) REFERENCES `cbl_schedule` (`schedule_id`),
        CONSTRAINT `cbl_schedule_course_objectives_likelihood_id` FOREIGN KEY (`likelihood_id`) REFERENCES `global_lu_likelihoods` (`likelihood_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
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
        SET FOREIGN_KEY_CHECKS=0;

        DROP TABLE IF EXISTS `global_lu_likelihoods`, `cbl_schedule_course_objectives`;

        SET FOREIGN_KEY_CHECKS=1;
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
        if ($migration->tableExists(DATABASE_NAME, "global_lu_likelihoods") &&
            $migration->tableExists(DATABASE_NAME, "cbl_schedule_course_objectives")
        ) {
            return 1;
        }
        return 0;
    }
}
