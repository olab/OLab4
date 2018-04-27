<?php
class Migrate_2017_08_09_123058_1955 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        INSERT INTO `settings` (`shortname`, `organisation_id`, `value`)
        VALUES
        ('language_supported', NULL, '{"en":{"name" : "English"}}'),
        ('curriculum_tagsets_allow_attributes ', NULL, '1'),
        ('curriculum_tagsets_max_allow_levels ', NULL, '9'),
        ('curriculum_tags_default_status', NULL, 2);

        ALTER TABLE `global_lu_objective_sets` ADD COLUMN `languages` text DEFAULT '' AFTER `standard`;
        ALTER TABLE `global_lu_objective_sets` ADD COLUMN `requirements` text DEFAULT '' AFTER `languages`;
        ALTER TABLE `global_lu_objective_sets` ADD COLUMN `maximum_levels` INT(2) DEFAULT '1' AFTER `requirements`;
        ALTER TABLE `global_lu_objective_sets` ADD COLUMN `short_method` text DEFAULT '' AFTER `maximum_levels`;
        ALTER TABLE `global_lu_objective_sets` ADD COLUMN `long_method` text DEFAULT '' AFTER `short_method`;
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
        DELETE FROM `settings` WHERE `shortname` = 'language_supported';
        DELETE FROM `settings` WHERE `shortname` = 'curriculum_tagsets_allow_attributes';
        DELETE FROM `settings` WHERE `shortname` = 'curriculum_tagsets_max_allow_levels';
        DELETE FROM `settings` WHERE `shortname` = 'curriculum_tags_default_status';

        ALTER TABLE `global_lu_objective_sets` DROP COLUMN `languages`;
        ALTER TABLE `global_lu_objective_sets` DROP COLUMN `requirements`;
        ALTER TABLE `global_lu_objective_sets` DROP COLUMN `maximum_levels`;
        ALTER TABLE `global_lu_objective_sets` DROP COLUMN `short_method`;
        ALTER TABLE `global_lu_objective_sets` DROP COLUMN `long_method`;
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
        $settings = new Entrada_Settings();
        if ($settings->read("language_supported") !== false) {
            if ($settings->read("curriculum_tagsets_allow_attributes") !== false) {
                if ($settings->read("curriculum_tagsets_max_allow_levels") !== false) {
                    if ($settings->read("curriculum_tags_default_status") !== false) {
                        if ($migration->columnExists(DATABASE_NAME, "global_lu_objective_sets", "languages")) {
                            if ($migration->columnExists(DATABASE_NAME, "global_lu_objective_sets", "requirements")) {
                                if ($migration->columnExists(DATABASE_NAME, "global_lu_objective_sets", "maximum_levels")) {
                                    if ($migration->columnExists(DATABASE_NAME, "global_lu_objective_sets", "short_method")) {
                                        if ($migration->columnExists(DATABASE_NAME, "global_lu_objective_sets", "long_method")) {
                                            return 1;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return 0;
    }
}
