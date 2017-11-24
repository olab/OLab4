<?php
class Migrate_2015_06_25_162521_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_assessment_distribution_targets`
        ADD COLUMN `target_role` enum('learner','faculty','any') NOT NULL DEFAULT 'any' AFTER `target_scope`;
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
        ALTER TABLE `cbl_assessment_distribution_targets`
        DROP COLUMN `target_role`;
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

        $query = "SHOW COLUMNS FROM `cbl_assessment_distribution_targets` LIKE 'target_role'";
        $column = $db->GetRow($query);
        if ($column) {
            return 1;
        } else {
            return 0;
        }
    }
}
