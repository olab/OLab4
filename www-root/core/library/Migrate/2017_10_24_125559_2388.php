<?php
class Migrate_2017_10_24_125559_2388 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        INSERT INTO `settings` (`shortname`, `organisation_id`, `value`) VALUES ('cbme_enabled', NULL, 0);

        ALTER TABLE `global_lu_objectives` ADD `associated_objective` int(12) DEFAULT NULL AFTER `objective_set_id`;
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
        DELETE FROM `settings` WHERE `shortname` = 'cbme_enabled';

        ALTER TABLE `global_lu_objectives` DROP COLUMN `associated_objective`;
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
        $settings = new Entrada_Settings();
        $migrate = new Models_Migration();

        if (($settings->read("cbme_enabled") !== false) && $migrate->columnExists(DATABASE_NAME, "global_lu_objectives", "associated_objective")) {
            return 1;
        }

        return 0;
    }
}
