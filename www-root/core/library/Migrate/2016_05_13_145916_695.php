<?php
class Migrate_2016_05_13_145916_695 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `filetypes` ADD COLUMN `classname` VARCHAR(128) NULL AFTER `mime`;
        UPDATE `filetypes` SET `classname` = "Image" where `ext` IN ("jpg","gif","png"  );
        UPDATE `filetypes` SET `classname` = "Document" where `ext` IN ("pps","ppt","pptx","doc","docx","xls","xlsx","txt","rtf","pdf");
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
        ALTER TABLE `filetypes` DROP COLUMN `classname`;
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
        if ($migration->columnExists(DATABASE_NAME, "filetypes", "classname")) {
            return 1;
        }

        return 0;
    }
}
