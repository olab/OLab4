<?php
class Migrate_2016_10_28_150043_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        global $db;
        $this->record();
        ?>
        ALTER TABLE `cbl_assessment_distributions` ADD COLUMN `assessment_type` ENUM('assessment', 'evaluation') NOT NULL AFTER `course_id`;
        <?php
        $this->stop();
        $result = false;
        if($this->run()) {
            $result = true;
            $all_distributions = Models_Assessments_Distribution::fetchAllRecordsIgnoreDeletedDate();
            foreach($all_distributions as $distribution) {
                $assessment_type = null;
                if($distribution->getAssessorOption() === "faculty") {
                    $assessment_type = "assessment";
                }
                if($distribution->getAssessorOption() === "learner") {
                    // We care about the target here
                    $targets = Models_Assessments_Distribution_Target::fetchAllByDistributionID($distribution->getAdistributionID());
                    foreach($targets as $target) {
                        if($target->getTargetRole() === "learner") {
                            $assessment_type = "assessment";
                        } else {
                            $assessment_type = "evaluation";
                        }
                    }
                }
                if($distribution->getAssessorOption() === "individual_users") {
                    $assessors = Models_Assessments_Distribution_Assessor::fetchAllByDistributionID($distribution->getAdistributionID());
                    foreach($assessors as $assessor) {
                        if($assessor->getAssessorType() === "proxy_id") {
                            $user_access = Models_User_Access::fetchRowByUserIDAppID($assessor->getAdassessorID());
                            if($user_access) {
                                if ($user_access->getGroup() === "student") {
                                    $targets = Models_Assessments_AssessmentTarget::fetchAllByDistributionID($distribution->getAdistributionID());
                                    foreach ($targets as $target) {
                                        if ($target->getTargetType() === "proxy_id") {
                                            $target_access = Models_User_Access::fetchRowByUserIDAppID($target->getID());
                                            if($target_access) {
                                                if ($target_access->getGroup() !== "student") {
                                                    $assessment_type = "evaluation";
                                                } else {
                                                    $assessment_type = "assessment";
                                                }
                                            }
                                        }
                                    }
                                } else {
                                    $assessment_type = "assessment";
                                }
                            } else {
                                $assessment_type = "assessment";
                            }
                        } else {
                            $assessment_type = "assessment";
                        }
                    }
                }
                if(!$db->Execute("UPDATE `cbl_assessment_distributions` SET `assessment_type` = ? WHERE `adistribution_id` = ?", array($assessment_type, $distribution->getAdistributionID()))) {

                }
            }
        }

        return $result;
    }

    /**
     * Required: SQL / PHP that performs the downgrade migration.
     */
    public function down() {
        $this->record();
        ?>
        ALTER TABLE `cbl_assessment_distributions` DROP `assessment_type`;
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
        if ($migration->columnExists(DATABASE_NAME, "cbl_assessment_distributions", "assessment_type")) {
            return 1;
        }
        return 0;
    }
}