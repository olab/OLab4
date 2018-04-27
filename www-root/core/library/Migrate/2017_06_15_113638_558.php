<?php
class Migrate_2017_06_15_113638_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_assessment_distributions` ADD `target_option` ENUM('all', 'only_cbme', 'non_cbme') NOT NULL DEFAULT 'non_cbme' AFTER `assessor_option`;
        ALTER TABLE `user_learner_levels` ADD `cbme` tinyint(1) NOT NULL DEFAULT '0' AFTER `course_id`;
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
        ALTER TABLE `cbl_assessment_distributions` DROP COLUMN `target_option`;
        ALTER TABLE `user_learner_levels` DROP COLUMN `cbme`;
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
        $migrate = new Models_Migration();
        if ($migrate->columnExists(DATABASE_NAME, "cbl_assessment_distributions", "target_option") &&
            $migrate->columnExists(DATABASE_NAME, "user_learner_levels", "cbme")
        ) {
            return 1;
        }
        return 0;
    }
}
