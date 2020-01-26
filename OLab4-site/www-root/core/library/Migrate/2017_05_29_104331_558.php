<?php
class Migrate_2017_05_29_104331_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        global $db;
        ?>
        ALTER TABLE `cbl_assessment_rating_scale_responses` ADD COLUMN `weight` int(11) DEFAULT NULL AFTER `flag_response`;
        <?php
        $query = "SELECT * FROM `cbl_assessment_rating_scale_responses` WHERE `text` = 'Not Observed' AND `deleted_date` IS NULL";
        $results = $db->GetAll($query);
        if ($results) {
            foreach ($results as $result) {
                $query = "UPDATE `cbl_assessment_rating_scale_responses` SET `weight` = 0 WHERE `rating_scale_response_id` = " .$db->qstr($result["rating_scale_response_id"]);
                $db->Execute($query);
            }
        }

        $this->stop();

        return $this->run();
    }

    /**
     * Required: SQL / PHP that performs the downgrade migration.
     */
    public function down() {
        $this->record();
        global $db;
        ?>
        ALTER TABLE `cbl_assessment_rating_scale_responses` DROP COLUMN `weight`;
        <?php
        $query = "SELECT * FROM `cbl_assessment_rating_scale_responses` WHERE `text` = 'Not Observed' AND `deleted_date` IS NULL";
        $results = $db->GetAll($query);
        if ($results) {
            foreach ($results as $result) {
                $query = "UPDATE `cbl_assessment_rating_scale_responses` SET `weight` = NULL WHERE `rating_scale_response_id` = " .$db->qstr($result["rating_scale_response_id"]);
                $db->Execute($query);
            }
        }

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
        if ($migrate->columnExists(DATABASE_NAME, "cbl_assessment_rating_scale_responses", "weight")) {
            return 1;
        }
        return -1;
    }
}
