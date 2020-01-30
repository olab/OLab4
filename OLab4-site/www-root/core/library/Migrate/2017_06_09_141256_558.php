<?php
class Migrate_2017_06_09_141256_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_assessments_lu_flags` CHANGE `organisation_id` `organisation_id` INT(11)  UNSIGNED  NULL  DEFAULT NULL;
        ALTER TABLE `cbl_assessments_lu_flags` ADD `flag_value` INT(11)  UNSIGNED  NOT NULL  AFTER `flag_id`;

        INSERT INTO `cbl_assessments_lu_flags` (`flag_id`, `flag_value`, `organisation_id`, `ordering`, `title`, `description`, `color`, `visibility`, `created_date`, `created_by`, `updated_date`, `updated_by`, `deleted_date`)
        VALUES
            (1, 1, NULL, 1, 'Flagged', 'The default flag value.', '#FF0000', 'Default', UNIX_TIMESTAMP(), 1, NULL, NULL, NULL);

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
        ALTER TABLE `cbl_assessments_lu_flags` CHANGE `organisation_id` `organisation_id` INT(11)  UNSIGNED  NOT NULL  DEFAULT 0;
        ALTER TABLE `cbl_assessments_lu_flags` DROP `flag_value`;
        DELETE FROM `cbl_assessments_lu_flags` WHERE `flag_id` = 1;
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

        $field_meta1 = $migrate->fieldMetadata(DATABASE_NAME, "cbl_assessments_lu_flags", "organisation_id");
        if ($field_meta1["Null"] == "NO") {
            return 0;
        }
        if (!$migrate->columnExists(DATABASE_NAME, "cbl_assessments_lu_flags", "flag_value")) {
            return 0;
        }
        return 1;
    }
}
