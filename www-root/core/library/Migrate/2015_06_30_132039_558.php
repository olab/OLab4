<?php
class Migrate_2015_06_30_132039_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        INSERT INTO `settings` (`shortname`, `organisation_id`, `value`)
        VALUES
        ('version_js', NULL, '1600');
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
        DELETE FROM `settings` WHERE `shortname` = 'version_js';
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
        $query = "SELECT * FROM `settings` WHERE `shortname` = 'version_js'";
        $found = $db->GetRow($query);
        if ($found) {
            return 1;
        } else {
            return 0;
        }
    }
}
