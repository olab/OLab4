<?php
class Migrate_2017_05_23_105137_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_assessment_item_objectives` ADD `cbme_objective_tree_id` INT(11) UNSIGNED NULL DEFAULT NULL AFTER `objective_id`;
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
        ALTER TABLE `cbl_assessment_item_objectives` DROP `cbme_objective_tree_id`;
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
        $migrate = new Models_Migration();

        if ($migrate->columnExists(DATABASE_NAME, "cbl_assessment_item_objectives", "cbme_objective_tree_id")) {
            return 1;
        }

        return 0;
    }
}
