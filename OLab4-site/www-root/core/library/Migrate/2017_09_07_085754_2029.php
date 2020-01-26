<?php

class Migrate_2017_09_07_085754_2029 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `drafts`ADD COLUMN `copy_resources_as_draft` tinyint(1) NOT NULL DEFAULT 0 AFTER `preserve_elements`;
        ALTER TABLE `event_links`ADD COLUMN `draft` tinyint(1) NOT NULL DEFAULT 0 AFTER `updated_by`;
        ALTER TABLE `event_files`ADD COLUMN `draft` tinyint(1) NOT NULL DEFAULT 0 AFTER `updated_by`;
        ALTER TABLE `attached_quizzes`ADD COLUMN `draft` tinyint(1)  NOT NULL DEFAULT 0 AFTER `updated_by`;
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
        ALTER TABLE `drafts` DROP COLUMN `copy_resources_as_draft`;
        ALTER TABLE `event_links` DROP COLUMN `draft`;
        ALTER TABLE `event_files` DROP COLUMN `draft`;
        ALTER TABLE `attached_quizzes` DROP COLUMN `draft`;
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
        if ($migration->columnExists(DATABASE_NAME, "drafts", "copy_resources_as_draft")
            && $migration->columnExists(DATABASE_NAME, "event_links", "draft")
            && $migration->columnExists(DATABASE_NAME, "event_files", "draft")
            && $migration->columnExists(DATABASE_NAME, "attached_quizzes", "draft")
        ) {
            return 1;
        } else {
            return 0;
        }
    }
}
