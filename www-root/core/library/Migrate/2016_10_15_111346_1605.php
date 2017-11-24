<?php
class Migrate_2016_10_15_111346_1605 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `exam_progress`
        ADD COLUMN `use_self_timer` int(1) DEFAULT '0' AFTER `menu_open`;
        ALTER TABLE `exam_progress`
        ADD COLUMN `self_timer_start` bigint(64) DEFAULT NULL AFTER `use_self_timer`;
        ALTER TABLE `exam_progress`
        ADD COLUMN `self_timer_length` bigint(64) DEFAULT NULL AFTER `self_timer_start`;

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
        ALTER TABLE `exam_progress`
        DROP COLUMN `self_timer_start`;

        ALTER TABLE `exam_progress`
        DROP COLUMN `self_timer_length`;

        ALTER TABLE `exam_progress`
        DROP COLUMN `use_self_timer`;
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
        if ($migration->columnExists(DATABASE_NAME, "exam_progress", "use_self_timer")) {
            return 1;
        }
        return 0;
    }
}
