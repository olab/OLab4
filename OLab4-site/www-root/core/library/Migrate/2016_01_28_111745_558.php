<?php
class Migrate_2016_01_28_111745_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_assessment_progress` DROP FOREIGN KEY `cbl_assessment_progress_ibfk_2`;
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
        ALTER TABLE `cbl_assessment_progress` ADD CONSTRAINT `cbl_assessment_progress_ibfk_2` FOREIGN KEY (`adtarget_id`) REFERENCES `cbl_assessment_distribution_targets` (`adtarget_id`);
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

        $query = "SELECT *
                  FROM information_schema.REFERENTIAL_CONSTRAINTS
                  WHERE REFERENCED_TABLE_NAME = 'cbl_assessment_progress'
                  AND CONSTRAINT_NAME = 'cbl_assessment_progress_ibfk_2'";
        $constraint = $db->GetOne($query);
        if (!$constraint) {
            return 1;
        } else {
            return 0;
        }
    }
}
