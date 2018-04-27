<?php
class Migrate_2017_03_06_095940_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_distribution_assessments` ADD `organisation_id` INT(11)  UNSIGNED  NULL  AFTER `assessment_type_id`;
        UPDATE `cbl_distribution_assessments` a
        LEFT JOIN `cbl_assessment_distributions` d ON d.`adistribution_id` = a.`adistribution_id`
        SET a.`organisation_id` = d.`organisation_id`;
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
        ALTER TABLE `cbl_distribution_assessments` DROP `organisation_id`;
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
        if ($migrate->columnExists(DATABASE_NAME, "cbl_distribution_assessments", "organisation_id")) {
            return 1;
        }
        return 0;
    }
}
