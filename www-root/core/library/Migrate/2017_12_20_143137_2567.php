<?php
class Migrate_2017_12_20_143137_2567 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `exam_question_answers` ADD COLUMN `locked` int(1) DEFAULT '0' AFTER `order`;
        ALTER TABLE `exam_question_answers` MODIFY COLUMN `deleted_date` BIGINT(64) DEFAULT NULL AFTER `updated_by`;
        ALTER TABLE `exams` ADD COLUMN `random_answers` int(1) DEFAULT '0' AFTER `random`;
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
        ALTER TABLE `exam_question_answers` DROP COLUMN `locked`;
        ALTER TABLE `exams` DROP COLUMN `random_answers`;
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
        if ($migration->columnExists(DATABASE_NAME, "exam_question_answers", "locked")) {
            if ($migration->columnExists(DATABASE_NAME, "exams", "random_answers")) {
                return 1;
            }
        }

        return 0;
    }
}
