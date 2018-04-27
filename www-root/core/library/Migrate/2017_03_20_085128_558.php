<?php
class Migrate_2017_03_20_085128_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_assessments_lu_items` ADD `allow_default` TINYINT(1) NOT NULL DEFAULT '0' AFTER `comment_type`;
        ALTER TABLE `cbl_assessments_lu_items` ADD `default_response` INT(11) DEFAULT NULL AFTER `allow_default`;
        ALTER TABLE `cbl_assessments_lu_items` DROP `standard_item`;
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
        ALTER TABLE `cbl_assessments_lu_items` DROP `allow_default`;
        ALTER TABLE `cbl_assessments_lu_items` DROP `default_response`;
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

        if ($migration->columnExists(DATABASE_NAME, "cbl_assessments_lu_items", "allow_default") &&
            $migration->columnExists(DATABASE_NAME, "cbl_assessments_lu_items", "default_response") &&
            !$migration->columnExists(DATABASE_NAME, "cbl_assessments_lu_items", "standard_item")) {
            return 1;
        }

        return 0;
    }
}
