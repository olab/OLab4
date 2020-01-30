<?php
class Migrate_2017_03_14_143617_900 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `rp_now_config`
        ADD COLUMN `exam_code` varchar(20) NOT NULL AFTER `exam_post_id`;
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
        ALTER TABLE `rp_now_config` DROP COLUMN `exam_code`;
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

        if ($migration->columnExists(DATABASE_NAME, "rp_now_config", "exam_code")) {
            return 1;
        }
        return 0;
    }
}
