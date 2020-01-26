<?php
class Migrate_2017_09_26_122639_2338 extends Entrada_Cli_Migrate {
    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        INSERT INTO settings (shortname, value) VALUES ('community_share_show_file_versions', '1');
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
        DELETE FROM settings WHERE shortname='community_share_show_file_versions';
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
        $settings = new Entrada_Settings();

        if ($settings->read("community_share_show_file_versions") == '1') {
            return 1;
        }

        return 0;
    }
}
