<?php
class Migrate_2017_04_21_090045_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        //$this->record();
        //$this->stop();
        global $db;
        echo "\n\nStart assessment type migration.\n";
        $counters = array();
        $unreliably_determined = array();
        $all_distributions = Models_Assessments_Distribution::fetchAllRecordsIgnoreDeletedDate();
        foreach($all_distributions as $distribution) {
            $assessment_type = null;
            if ($distribution->getAssessorOption() === "faculty") {
                $assessment_type = "assessment";
            }
            if ($distribution->getAssessorOption() === "learner") {
                $targets = Models_Assessments_Distribution_Target::fetchAllByDistributionID($distribution->getAdistributionID());
                foreach ($targets as $target) {
                    if ($target->getTargetRole() === "learner" || $target->getTargetRole() === "self") {
                        $assessment_type = "assessment";
                    } else {
                        $assessment_type = "evaluation";
                    }
                }
            }

            if ($distribution->getAssessorOption() === "individual_users") {
                $assessors = Models_Assessments_Distribution_Assessor::fetchAllByDistributionID($distribution->getAdistributionID());
                if ($assessors) {
                    foreach ($assessors as $assessor) {

                        if ($assessor->getAssessorType() === "proxy_id") {

                            $user_access = Models_User_Access::fetchAllByUserID($assessor->getAssessorValue());
                            // Without any user access to check, we cannot safely make any assumptions of task type.
                            if ($user_access) {

                                $assessor_is_faculty = false;
                                // We must check all of the access records to ensure they are just a learner/faculty. Some users have multiple roles.
                                foreach ($user_access as $access) {
                                    if ($access->getOrganisationID() == $distribution->getOrganisationID()) {
                                        if ($access->getGroup() == "faculty") {
                                            $assessor_is_faculty = true;
                                        }
                                    }
                                }

                                if (!$assessor_is_faculty) {
                                    $targets = Models_Assessments_AssessmentTarget::fetchAllByDistributionID($distribution->getAdistributionID());
                                    foreach ($targets as $target) {
                                        if ($target->getTargetType() === "proxy_id") {
                                            $target_access = Models_User_Access::fetchAllByUserID($target->getTargetValue());
                                            // Without any user access to check, we cannot safely make any assumptions of task type.
                                            if ($target_access) {

                                                $target_is_faculty = false;
                                                // We must check all of the access records to ensure they are just a learner/faculty. Some users have multiple roles.
                                                foreach ($target_access as $access) {
                                                    if ($access->getOrganisationID() == $distribution->getOrganisationID()) {
                                                        if ($access->getGroup() == "faculty") {
                                                            $target_is_faculty = true;
                                                        }
                                                    }
                                                }

                                                if ($target_is_faculty) {
                                                    $assessment_type = "evaluation";
                                                } else {
                                                    $assessment_type = "assessment";
                                                }
                                            }
                                        } elseif ($target->getTargetType() === "course_id") {
                                            $assessment_type = "evaluation";
                                        }
                                    }
                                } else {
                                    // User is some sort of staff/faculty, we can safely assume it is an assessment.
                                    $assessment_type = "assessment";
                                }
                            }
                        } elseif ($assessor->getAssessorType() === "external_hash") {
                            // External assessors are always assessing learners.
                            $assessment_type = "assessment";
                        }
                    }
                }
            }
            if (!$assessment_type) {
                echo "\nNo assessment type could not be reliably determined for {$distribution->getID()}, using default of: 'evaluation'";
                $assessment_type = "evaluation";
                $deleted = $distribution->getDeletedDate() ? "deleted" : "active";
                $targets = Models_Assessments_AssessmentTarget::fetchAllByDistributionID($distribution->getAdistributionID());
                $unreliably_determined[$deleted][] = $distribution->getID() . (!$targets ? " (NO TARGETS)" : "");
            }

            if (!array_key_exists($assessment_type, $counters)) {
                $counters[$assessment_type] = 1;
            }
            $counters[$assessment_type]++;

            if (!$db->Execute("UPDATE `cbl_assessment_distributions` SET `assessment_type` = ? WHERE `adistribution_id` = ?", array($assessment_type, $distribution->getAdistributionID()))) {
                echo "\nFailed to update record (distribution = {$distribution->getID()} / type = '$assessment_type').";
            } else {
                echo "\nUpdated distribution ID {$distribution->getID()} with type = '$assessment_type'.";
            }
        }
        // After migration, update the assessment targets.
        if (!$db->Execute("UPDATE `cbl_distribution_assessment_targets` AS a JOIN `cbl_assessment_distributions` AS b ON b.`adistribution_id` = a.`adistribution_id` SET a.`task_type` = b.`assessment_type`")) {
            echo "\nFailed to update assessment target records.";
        }
        echo "\n\nCompleted assessment type migration.\n";
        foreach ($counters as $type => $count) {
            echo "\n{$count} distributions set to type '{$type}'.";
        }
        if (!empty($unreliably_determined)) {
            echo "\n\nThe following distribution's types could not be reliably determined:";
            foreach ($unreliably_determined as $status => $deleted_or_active) {
                $status_count = @count($deleted_or_active);
                echo "\n\n{$status_count} {$status} distributions:\n";
                foreach ($deleted_or_active as $distribution_id) {
                    echo "{$distribution_id}, ";
                }
            }
        }
        return true;
    }

    /**
     * Required: SQL / PHP that performs the downgrade migration.
     */
    public function down() {
        $this->record();
        ?>
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
