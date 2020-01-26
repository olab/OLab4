<?php
class Migrate_2017_02_27_112704_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_distribution_assessments` ADD `feedback_required` TINYINT(1)  NOT NULL  DEFAULT '0'  AFTER `max_submittable`;
        UPDATE `cbl_distribution_assessments` a
        JOIN `cbl_assessment_distributions` b ON a.`adistribution_id` = b.`adistribution_id`
        SET a.`feedback_required` = b.`feedback_required`;
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
        ALTER TABLE `cbl_distribution_assessments` DROP `feedback_required`;
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
        if ($migration->columnExists(DATABASE_NAME, "cbl_distribution_assessments", "feedback_required")) {
            return 1;
        }
        return 0;
    }
}
