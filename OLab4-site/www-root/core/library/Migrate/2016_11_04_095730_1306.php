<?php
class Migrate_2016_11_04_095730_1306 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        -- SQL Upgrade Queries Here;
        UPDATE `assessments` SET `collection_id` = NULL WHERE `collection_id` IS NOT NULL;
        TRUNCATE TABLE `assessment_collections`;
        ALTER TABLE `assessment_collections` ADD COLUMN `course_id` INT(12) unsigned AFTER `collection_id`;
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
        -- SQL Downgrade Queries Here;
        ALTER TABLE `assessment_collections` DROP COLUMN `course_id`;
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
        
        if ($migration->columnExists(DATABASE_NAME, "assessment_collections", "course_id")) {
            return 1;
        }

        return 0;
    }
}
