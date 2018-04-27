<?php
class Migrate_2017_09_22_151148_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_schedule_draft_authors` CHANGE `proxy_id` `author_value` int(11) DEFAULT NULL;
        ALTER TABLE `cbl_schedule_draft_authors` ADD COLUMN `author_type` ENUM('proxy_id', 'course_id') DEFAULT 'proxy_id' AFTER `author_value`;
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
        ALTER TABLE `cbl_schedule_draft_authors` DROP COLUMN `author_type`;
        ALTER TABLE `cbl_schedule_draft_authors` CHANGE `author_value` `proxy_id` int(11) DEFAULT NULL;
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
        if ($migration->columnExists(DATABASE_NAME, "cbl_schedule_draft_authors", "author_type")
            && $migration->columnExists(DATABASE_NAME, "cbl_schedule_draft_authors", "author_value")
        ) {
            return 1;
        } else {
            return 0;
        }
    }
}
