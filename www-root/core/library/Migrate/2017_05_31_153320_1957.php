<?php
class Migrate_2017_05_31_153320_1957 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `assignment_file_versions` MODIFY COLUMN `file_mimetype` varchar(128) NOT NULL;
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
        ALTER TABLE `assignment_file_versions` MODIFY COLUMN `file_mimetype` varchar(64) NOT NULL;
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
        $column = $migration->fieldMetadata(DATABASE_NAME, "assignment_file_versions", "file_mimetype");
        if ($column && $column["Type"] == "varchar(128)") {
            return 1;
        }
        return 0;
    }
}
