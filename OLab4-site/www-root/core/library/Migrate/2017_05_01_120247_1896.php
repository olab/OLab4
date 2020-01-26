<?php
class Migrate_2017_05_01_120247_1896 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `draft_events` ADD `keywords_hidden` INT(1) NULL DEFAULT '0' AFTER `event_objectives`;
        ALTER TABLE `draft_events` ADD `keywords_release_date` BIGINT(64) NULL DEFAULT '0' AFTER `keywords_hidden`;
        ALTER TABLE `draft_events` ADD `event_color` varchar(20) NULL DEFAULT NULL AFTER `release_until`;
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
        ALTER TABLE `draft_events` DROP `keywords_hidden`;
        ALTER TABLE `draft_events` DROP `keywords_release_date`;
        ALTER TABLE `draft_events` DROP `event_color`;
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
        if ($migration->columnExists(DATABASE_NAME, "draft_events", "keywords_hidden")) {
            if ($migration->columnExists(DATABASE_NAME, "draft_events", "keywords_release_date")) {
                if ($migration->columnExists(DATABASE_NAME, "draft_events", "event_color")) {
                    return 1;
                }
            }
        }

        return 0;
    }
}
