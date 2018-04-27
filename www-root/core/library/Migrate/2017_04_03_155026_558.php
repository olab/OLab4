<?php
class Migrate_2017_04_03_155026_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_assessments_lu_form_blueprints`
            ADD `include_instructions` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `description`,
            ADD `instructions` TEXT NULL AFTER `include_instructions`;
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
        ALTER TABLE `cbl_assessments_lu_form_blueprints`
            DROP `include_instructions`,
            DROP `instructions`;
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

        if ($migration->columnExists(DATABASE_NAME, "cbl_assessments_lu_form_blueprints", "include_instructions")) {
            if ($migration->columnExists(DATABASE_NAME, "cbl_assessments_lu_form_blueprints", "instructions")) {
                return 1;
            }
        }

        return 0;
    }
}
