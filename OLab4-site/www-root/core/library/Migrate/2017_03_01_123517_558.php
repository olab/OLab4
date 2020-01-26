<?php
class Migrate_2017_03_01_123517_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_assessment_progress_approvals` CHANGE `adistribution_id` `adistribution_id` INT(11)  UNSIGNED  NULL;
        ALTER TABLE `cbl_assessment_distribution_approvers` CHANGE `adistribution_id` `adistribution_id` INT(11)  UNSIGNED  NULL;
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
        ALTER TABLE `cbl_assessment_progress_approvals` CHANGE `adistribution_id` `adistribution_id` INT(11)  UNSIGNED NOT NULL;
        ALTER TABLE `cbl_assessment_distribution_approvers` CHANGE `adistribution_id` `adistribution_id` INT(11)  UNSIGNED NOT NULL;
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
        $field_meta1 = $migrate->fieldMetadata(DATABASE_NAME, "cbl_assessment_progress_approvals", "adistribution_id");
        $field_meta2 = $migrate->fieldMetadata(DATABASE_NAME, "cbl_assessment_distribution_approvers", "adistribution_id");
        if (empty($field_meta1) ||empty($field_meta2)) {
            return 0;
        }
        if ($field_meta1["Null"] == "YES" && $field_meta2["Null"] == "YES") {
            return 1;
        }
        return 0;
    }
}
