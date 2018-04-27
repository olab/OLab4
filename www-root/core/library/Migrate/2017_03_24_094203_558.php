<?php
class Migrate_2017_03_24_094203_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE `cbl_assessments_lu_flags` (
            `flag_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `organisation_id` INT(11) UNSIGNED NOT NULL ,
            `ordering` INT(11) UNSIGNED NOT NULL ,
            `title` CHAR(255) NOT NULL ,
            `description` TEXT NULL DEFAULT NULL ,
            `color` CHAR(10) NOT NULL ,
            `visibility` ENUM('Default','Admin','Public') NOT NULL ,
            `created_date` INT(64) NOT NULL ,
            `created_by` INT(11) NOT NULL ,
            `updated_date` INT(64) NULL DEFAULT NULL ,
            `updated_by` INT(11) NULL DEFAULT NULL ,
            `deleted_date` INT(64) NULL DEFAULT NULL ,
        PRIMARY KEY (`flag_id`),
        INDEX (`organisation_id`)) ENGINE=InnoDB;

        ALTER TABLE `cbl_assessments_lu_item_responses` CHANGE `flag_response` `flag_response` INT(11) NOT NULL DEFAULT '0';
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
        DROP TABLE `cbl_assessments_lu_flags`;
        ALTER TABLE `cbl_assessments_lu_item_responses` CHANGE `flag_response` `flag_response` TINYINT(1) NOT NULL DEFAULT '0';
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

        if ($migration->tableExists(DATABASE_NAME, "cbl_assessments_lu_flags")) {
            $metadata = $migration->fieldMetadata(DATABASE_NAME, "cbl_assessments_lu_item_responses", "flag_response");
            if ($metadata["Type"] == "int(11)") {
                return 1;
            }
        }

        return 0;
    }
}
