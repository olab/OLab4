<?php
class Migrate_2017_10_26_095137_2398 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        // This migration fixes any potential missing course_ids on assessment records that were created via learning events feedback form triggerings.
        ?>
        UPDATE `cbl_distribution_assessments` a
        JOIN `events` e ON e.`event_id` = a.`associated_record_id`
        AND a.`associated_record_type` = 'event_id'
        SET a.`course_id` = e.`course_id`
        WHERE a.`course_id` IS NULL
        AND a.`adistribution_id` IS NULL;

        UPDATE `cbl_distribution_assessments` a
        JOIN `cbl_assessment_distributions` b ON a.`adistribution_id` = b.`adistribution_id`
        SET a.`course_id` = b.`course_id`
        WHERE a.`course_id` IS NULL;
        <?php
        $this->stop();

        return $this->run();
    }

    /**
     * Required: SQL / PHP that performs the downgrade migration.
     */
    public function down() {
        $this->record();
        // There is no down migration since this data should always be present, regardless of the state of the state of the codebase.
        ?>
        -- SQL Downgrade Queries Here;
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
        return -1;
    }
}
