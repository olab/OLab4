<?php
class Migrate_2016_06_09_150429_889 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        CREATE TABLE IF NOT EXISTS `assessment_graders` (
            `ag_id` int(12) unsigned NOT NULL AUTO_INCREMENT,
            `assessment_id` int(12) unsigned NOT NULL,
            `proxy_id` int(12) unsigned NOT NULL,
            `grader_proxy_id` int(12) unsigned NOT NULL,
        PRIMARY KEY (`ag_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;

        ALTER TABLE `assessments`
            ADD `self_assessment` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT  '0' AFTER  `narrative`;
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
        DROP TABLE `assessment_graders`;

        ALTER TABLE `assessments` DROP `self_assessment`;
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

        if ($migration->tableExists(DATABASE_NAME, "assessment_graders")) {
            if ($migration->columnExists(DATABASE_NAME, "assessments", "self_assessment")) {
                return 1;
            }
        }

        return 0;
    }
}
