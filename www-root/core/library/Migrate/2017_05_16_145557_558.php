<?php
class Migrate_2017_05_16_145557_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_linked_assessments` CHANGE `created_by` `created_by` INT(11)  NULL  DEFAULT NULL;
        ALTER TABLE `cbl_distribution_assessments` CHANGE `created_by` `created_by` INT(11)  NULL  DEFAULT NULL;
        ALTER TABLE `cbl_distribution_assessment_targets` CHANGE `created_by` `created_by` INT(11)  NULL  DEFAULT NULL;
        ALTER TABLE `cbl_distribution_assessment_targets` CHANGE `updated_by` `updated_by` INT(11)  NULL  DEFAULT NULL;
        ALTER TABLE `cbl_distribution_assessment_targets` CHANGE `updated_date` `updated_date` INT(11)  NULL  DEFAULT NULL;
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
        ALTER TABLE `cbl_linked_assessments` CHANGE `created_by` `created_by` INT(11)  NOT NULL;
        ALTER TABLE `cbl_distribution_assessments` CHANGE `created_by` `created_by` INT(11)  NOT NULL;
        ALTER TABLE `cbl_distribution_assessment_targets` CHANGE `created_by` `created_by` INT(11)  NOT NULL;
        ALTER TABLE `cbl_distribution_assessment_targets` CHANGE `updated_by` `updated_by` INT(11) NOT NULL;
        ALTER TABLE `cbl_distribution_assessment_targets` CHANGE `updated_date` `updated_date` INT(11)  NULL  DEFAULT NULL;
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
        $meta1 = $migrate->fieldMetadata(DATABASE_NAME, "cbl_linked_assessments", "created_by");
        $meta2 = $migrate->fieldMetadata(DATABASE_NAME, "cbl_distribution_assessments", "created_by");
        $meta3 = $migrate->fieldMetadata(DATABASE_NAME, "cbl_distribution_assessment_targets", "created_by");
        $meta4 = $migrate->fieldMetadata(DATABASE_NAME, "cbl_distribution_assessment_targets", "updated_by");
        $meta5 = $migrate->fieldMetadata(DATABASE_NAME, "cbl_distribution_assessment_targets", "updated_date");
        if ($meta1["Null"] !== "YES") {
            return 0;
        }
        if ($meta2["Null"] !== "YES") {
            return 0;
        }
        if ($meta3["Null"] !== "YES") {
            return 0;
        }
        if ($meta4["Null"] !== "YES") {
            return 0;
        }
        if ($meta5["Null"] !== "YES") {
            return 0;
        }
        return 1;
    }
}
