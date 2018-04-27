<?php
class Migrate_2016_03_28_194377_640 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `event_files` ADD COLUMN `file_contents` longtext DEFAULT NULL AFTER `file_notes`;
        ALTER TABLE `event_files` ADD FULLTEXT `ft_contents_search` (`file_contents`);
        ALTER TABLE `event_files` ADD FULLTEXT `ft_name_search` (`file_name`);
        ALTER TABLE `event_files` ADD FULLTEXT `ft_notes_search` (`file_notes`);
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
        ALTER TABLE `event_files` DROP `file_contents`;
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
        if ($migration->columnExists(DATABASE_NAME, "event_files", "file_contents")) {
            return 1;
        }

        return 0;
    }
}
