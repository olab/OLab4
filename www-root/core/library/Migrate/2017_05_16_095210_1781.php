<?php
class Migrate_2017_05_16_095210_1781 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `exam_posts` ADD COLUMN `use_honor_code` int(1) DEFAULT '0' NOT NULL AFTER `secure_mode`;
        ALTER TABLE `exam_posts` ADD COLUMN `honor_code` text DEFAULT NULL AFTER `use_honor_code`;
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
        ALTER TABLE `exam_posts` DROP COLUMN `use_honor_code`;
        ALTER TABLE `exam_posts` DROP COLUMN `honor_code`;
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
        if ($migration->indexExists(DATABASE_NAME, "exam_posts", "use_honor_code")) {
            if ($migration->indexExists(DATABASE_NAME, "exam_posts", "honor_code")) {
                return 1;
            }
        }

        return 0;
    }
}
