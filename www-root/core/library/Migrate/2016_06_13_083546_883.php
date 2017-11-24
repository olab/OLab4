<?php
class Migrate_2016_06_13_083546_883 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        INSERT INTO `settings` (`shortname`, `organisation_id`, `value`) VALUES ('prizm_doc_settings', NULL, '{"url" : "\\/\\/api.accusoft.com\\/v1\\/viewer\\/","key" : "b2GVmI5r7iL2zAKFZDww4HqCCmac5NRnFzgfDzco_xEIdZz3rbwrsX4o4-7lOF7L","viewertype" : "html5","viewerheight" : "600","viewerwidth" : "100%","upperToolbarColor" : "000000","lowerToolbarColor" : "88909e","bottomToolbarColor" : "000000","backgroundColor" : "e4eaee","fontColor" : "ffffff","buttonColor" : "white","hidden" : "esign,redact"}');
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
        DELETE FROM `settings` WHERE `shortname` = 'prizm_doc_settings';
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

        $query = "SELECT `shortname` FROM `settings` WHERE `shortname` = 'prizm_doc_settings'";
        $results = $db->getRow($query);
        if ($results) {
            return 1;
        }

        return 0;
    }
}
