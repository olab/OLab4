<?php
class Migrate_2016_06_08_165817_902 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE `curriculum_map_versions` (
            `version_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `title` varchar(255) NOT NULL DEFAULT '',
            `description` text,
            `status` enum('draft', 'published') NOT NULL DEFAULT 'draft',
            `created_date` bigint(64) NOT NULL,
            `created_by` int(11) NOT NULL,
            `updated_date` bigint(64) DEFAULT NULL,
            `updated_by` int(11) DEFAULT NULL,
            `deleted_date` bigint(64) DEFAULT NULL,
            PRIMARY KEY (`version_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE `curriculum_map_version_organisations` (
            `version_id` int(11) unsigned NOT NULL,
            `organisation_id` int(11) NOT NULL,
            PRIMARY KEY (`version_id`,`organisation_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE `curriculum_map_version_periods` (
            `version_id` int(11) unsigned NOT NULL,
            `cperiod_id` int(11) NOT NULL,
            PRIMARY KEY (`version_id`,`cperiod_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        ALTER TABLE `linked_objectives` ADD `version_id` INT(11) UNSIGNED NULL DEFAULT NULL AFTER `linked_objective_id`;
        ALTER TABLE `linked_objectives` ADD INDEX (`version_id`);
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
        DROP TABLE `curriculum_map_versions`;
        DROP TABLE `curriculum_map_version_organisations`;
        DROP TABLE `curriculum_map_version_periods`;
        ALTER TABLE `linked_objectives` DROP `version_id`;
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
