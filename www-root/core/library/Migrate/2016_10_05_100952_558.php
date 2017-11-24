<?php
class Migrate_2016_10_05_100952_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `global_lu_objectives` ADD COLUMN `objective_set_id` int(12) NOT NULL AFTER `objective_parent`;
        ALTER TABLE `global_lu_objectives` ADD COLUMN `objective_secondary_description` text AFTER `objective_description`;

        CREATE TABLE IF NOT EXISTS `global_lu_objective_sets` (
        `objective_set_id` int(12) NOT NULL AUTO_INCREMENT,
        `title` varchar(255) NOT NULL,
        `description` text,
        `shortname` varchar(128) NOT NULL,
        `start_date` bigint(64) DEFAULT NULL,
        `end_date` bigint(64) DEFAULT NULL,
        `standard` tinyint(1) DEFAULT 0,
        `created_date` bigint(64) NOT NULL,
        `created_by` int(12) NOT NULL,
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(12) DEFAULT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`objective_set_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        INSERT INTO `global_lu_objective_sets` (`title`, `description`, `shortname`, `start_date`, `end_date`, `standard`, `created_date`, `created_by`, `updated_date`, `updated_by`, `deleted_date`)
        VALUES
        ('Entrusbable Professional Activities', 'Entrusbable Professional Activities', 'epa', NULL, NULL, 0,  UNIX_TIMESTAMP(NOW()), 1, NULL, NULL, NULL),
        ('Key Competencies', 'Key Competencies', 'kc', NULL, NULL, 1,  UNIX_TIMESTAMP(NOW()), 1, NULL, NULL, NULL),
        ('Enabling Competencies', 'Enabling Competencies', 'ec', NULL, NULL, 1,  UNIX_TIMESTAMP(NOW()), 1, NULL, NULL, NULL),
        ('Milestones', 'Milestones', 'milestone', NULL, NULL, 0,  UNIX_TIMESTAMP(NOW()), 1, NULL, NULL, NULL);

        CREATE TABLE IF NOT EXISTS `cbme_course_objectives` (
        `cbme_course_objective_id` int(12) NOT NULL AUTO_INCREMENT,
        `objective_id` int(12) NOT NULL,
        `course_id` int(12) NOT NULL,
        `created_date` bigint(64) NOT NULL,
        `created_by` int(12) NOT NULL,
        `updated_date` bigint(64) DEFAULT NULL,
        `updated_by` int(12) DEFAULT NULL,
        `deleted_date` bigint(64) DEFAULT NULL,
        PRIMARY KEY (`cbme_course_objective_id`)
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
        ALTER TABLE `global_lu_objectives` DROP COLUMN `objective_set_id`;
        ALTER TABLE `global_lu_objectives` DROP COLUMN `objective_secondary_description`;
        DROP TABLE IF EXISTS `global_lu_objective_sets`;
        DROP TABLE IF EXISTS `cbme_course_objectives`;
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
        if ($migration->columnExists(DATABASE_NAME, "global_lu_objectives", "objective_set_id") && $migration->columnExists(DATABASE_NAME, "global_lu_objectives", "objective_secondary_description") && $migration->tableExists(DATABASE_NAME, "global_lu_objective_sets")) {
            return 1;
        }
        return 0;
    }
}
