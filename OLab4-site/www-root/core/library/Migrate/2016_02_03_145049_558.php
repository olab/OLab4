<?php
class Migrate_2016_02_03_145049_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_distribution_assessments` ADD COLUMN `additional_assessment` tinyint(1) DEFAULT 0 AFTER `external_hash`;
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
        ALTER TABLE `cbl_distribution_assessments` DROP COLUMN `additional_assessment`;
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
        $query = "SELECT `column_type` FROM `information_schema`.`columns`
                  WHERE `table_name` = 'cbl_distribution_assessments' AND `column_name` = 'additional_assessment'";
        $column = $db->GetRow($query);
        if ($column) {
            return 1;
        } else {
            return 0;
        }
    }
}
