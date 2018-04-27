<?php
class Migrate_2017_11_03_100712_2430 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();

        $migrate = new Models_Migration();
        /**
         * We are looking for the incorrect primary key names 'adapprover_id' and 'adapproval_id'. Change to `apapproval_id'
         */
        if ($migrate->columnExists(DATABASE_NAME,"cbl_assessment_progress_approvals", "adapprover_id" )) {
            ?>
            ALTER TABLE `cbl_assessment_progress_approvals` CHANGE `adapprover_id` `apapproval_id` int(11) unsigned NOT NULL AUTO_INCREMENT;
            <?php
        }
        if ($migrate->columnExists(DATABASE_NAME,"cbl_assessment_progress_approvals", "adapproval_id" )) {
            ?>
            ALTER TABLE `cbl_assessment_progress_approvals` CHANGE `adapproval_id` `apapproval_id` int(11) unsigned NOT NULL AUTO_INCREMENT;
            <?php
        }
        $this->stop();

        return $this->run();
    }

    /**
     * Required: SQL / PHP that performs the downgrade migration.
     */
    public function down() {

        /**
         * Note that this returns the column name to the preferred incorrect version adapproval_id
         */
        $this->record();
        ?>
        ALTER TABLE `cbl_assessment_progress_approvals` CHANGE `apapproval_id` `adapproval_id` int(11) unsigned NOT NULL AUTO_INCREMENT;
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
        if ($migrate->columnExists(DATABASE_NAME, "cbl_assessment_progress_approvals", "apapproval_id")) {
            return 1;
        }
        return 0;
    }
}
