<?php
class Migrate_2016_03_10_124616_686 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `course_contacts` MODIFY `contact_type` VARCHAR(18) NOT NULL DEFAULT 'director';
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
        ALTER TABLE `course_contacts` MODIFY `contact_type` VARCHAR(12) NOT NULL DEFAULT 'director';
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
        $column = $migration->fieldMetadata(DATABASE_NAME, "course_contacts", "contact_type");
        if ($column && $column["Type"] == "varchar(18)") {
            return 1;
        }
        return 0;
    }
}
