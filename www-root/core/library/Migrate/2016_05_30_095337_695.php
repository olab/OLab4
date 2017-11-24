<?php
class Migrate_2016_05_30_095337_695 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        UPDATE `filetypes` SET `classname` = "GoogleDocsViewer" WHERE `ext` IN ("pps","ppt","pptx","doc","docx","xls","xlsx","txt","rtf","pdf");
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
        UPDATE `filetypes` SET `classname` = "Document" WHERE `ext` IN ("pps","ppt","pptx","doc","docx","xls","xlsx","txt","rtf","pdf");
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

        $query = "SELECT `classname` FROM `filetypes` WHERE `classname` = 'GoogleDocsViewer'";
        $results = $db->getRow($query);
        if ($results) {
            return 1;
        }

        return 0;
    }
}
