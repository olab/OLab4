<?php
class Migrate_2017_12_18_085231_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_assessment_method_group_meta`
        ADD COLUMN `assessment_cue` tinyint(1) unsigned NOT NULL DEFAULT '0' AFTER `skip_validation`;
        <?php
        $this->stop();
        $executed = false;
        if ($this->run()) {
            $executed = true;
            $assessment_method_model = new Models_Assessments_Method();
            $assessment_method_ids = $assessment_method_model->fetchMethodIDsByShortnames(array("complete_and_confirm_by_email", "double_blind_assessment"));

            foreach ($assessment_method_ids as $id) {
                $method_meta = Models_Assessments_Method_Meta::fetchRowByAssessmentMethodIDGroup($id["assessment_method_id"], "student");
                if ($method_meta) {
                    $method_meta->setAssessmentCue(1);
                    $method_meta->update();
                }
            }
        }

        return $executed;
    }

    /**
     * Required: SQL / PHP that performs the downgrade migration.
     */
    public function down() {
        $this->record();
        ?>
        ALTER TABLE `cbl_assessment_method_group_meta`
        DROP COLUMN `assessment_cue`;
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

        if ($migration->columnExists(DATABASE_NAME, "cbl_assessment_method_group_meta", "assessment_cue")) {
            return 1;
        }

        return 0;
    }
}
