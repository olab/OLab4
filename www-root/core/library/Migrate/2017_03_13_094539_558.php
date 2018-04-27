<?php
class Migrate_2017_03_13_094539_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_assessor_target_feedback` CHANGE `created_by` `created_by` INT(11)  NULL;
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
        ALTER TABLE `cbl_assessor_target_feedback` CHANGE `created_by` `created_by` INT(11) NOT NULL;
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
        $meta = $migration->fieldMetadata(DATABASE_NAME, "cbl_assessor_target_feedback", "created_by");
        if ($meta["Null"] == "NO") {
            return 0;
        }
        return 1;
    }
}
