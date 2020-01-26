<?php
class Migrate_2017_06_12_112448_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_assessments_lu_rating_scale_types`
            ADD `ordering` INT(11) UNSIGNED NOT NULL AFTER `active`,
            ADD `dashboard_visibility` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' AFTER `ordering`;
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
        ALTER TABLE `cbl_assessments_lu_rating_scale_types`
            DROP `ordering`,
            DROP `dashboard_visibility`;
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

        if ($migration->columnExists(DATABASE_NAME, "cbl_assessments_lu_rating_scale_types", "ordering")) {
            if ($migration->columnExists(DATABASE_NAME, "cbl_assessments_lu_rating_scale_types", "dashboard_visibility")) {
                return 1;
            }
        }

        return 0;
    }
}
