<?php
class Migrate_2015_09_08_105058_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_distribution_assessment_assessors` ADD COLUMN `published` tinyint(1) NOT NULL DEFAULT '0' AFTER `delegation_list_id`;
        ALTER TABLE `cbl_distribution_assessments` ADD COLUMN `published` tinyint(1) NOT NULL DEFAULT '0' AFTER `max_submittable`;
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
        ALTER TABLE `cbl_distribution_assessment_assessors` DROP COLUMN `published`;
        ALTER TABLE `cbl_distribution_assessments` DROP COLUMN `published`;
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
        $query = "SHOW COLUMNS FROM `cbl_distribution_assessment_assessors` LIKE 'published'";
        $column = $db->GetRow($query);
        if ($column) {
            $query = "SHOW COLUMNS FROM `cbl_distribution_assessments` LIKE 'published'";
            $second_column = $db->GetRow($query);
            if ($second_column) {
                return 1;
            }
        }
        return 0;
    }
}
