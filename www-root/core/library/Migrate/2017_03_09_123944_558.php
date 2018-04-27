<?php
class Migrate_2017_03_09_123944_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {
        $this->record();
        ?>
        ALTER TABLE `cbl_distribution_assessment_targets` ADD COLUMN `deleted_reason_id` int(11) unsigned DEFAULT NULL AFTER `deleted_date`;
        ALTER TABLE `cbl_distribution_assessment_targets` ADD COLUMN `deleted_reason_notes` varchar(255) DEFAULT NULL AFTER `deleted_reason_id`;
        ALTER TABLE `cbl_distribution_assessment_targets` ADD COLUMN `deleted_by`int(11) DEFAULT NULL AFTER `deleted_reason_notes`;
        ALTER TABLE `cbl_distribution_assessment_targets` ADD COLUMN `visible` TINYINT(1) NOT NULL DEFAULT '1' AFTER `deleted_by`;
        <?php


        $this->stop();

        if ($this->run()) {
            $previously_deleted_tasks = Models_Assessments_DeletedTask::fetchAllRecords();
            if ($previously_deleted_tasks) {
                $deletions_converted = 0;
                $deletions_added = 0;

                foreach ($previously_deleted_tasks as $previously_deleted_task) {
                    // Assessment targets will only exists for deliveries that have taken place.
                    if ($previously_deleted_task->getDeliveryDate() <= time()) {

                        $assessments = Models_Assessments_Assessor::fetchAllByDistributionIDAssessorTypeAssessorValue($previously_deleted_task->getADistributionID(), $previously_deleted_task->getAssessorType(), $previously_deleted_task->getAssessorValue());
                        if ($assessments) {
                            foreach ($assessments as $assessment) {
                                if ($assessment->getDeliveryDate() == $previously_deleted_task->getDeliveryDate()) {

                                    $assessment_target = Models_Assessments_AssessmentTarget::fetchRowByDAssessmentIDTargetTypeTargetValue($assessment->getID(), $previously_deleted_task->getTargetType(), $previously_deleted_task->getTargetID());
                                    if ($assessment_target) {

                                        $new_deletion = $assessment_target->toArray();
                                        $new_deletion["deleted_reason_id"] = $previously_deleted_task->getDeletedReasonID();
                                        $new_deletion["deleted_reason_notes"] = $previously_deleted_task->getDeletedReasonNotes();
                                        $new_deletion["deleted_date"] = $previously_deleted_task->getCreatedDate();
                                        $new_deletion["deleted_by"] = $previously_deleted_task->getCreatedBy();
                                        $new_deletion["visible"] = $previously_deleted_task->getVisible();
                                        $new_deletion_data["updated_date"] = time();
                                        $new_deletion_data["updated_by"] = 1;


                                        if ($assessment_target->fromArray($new_deletion)->update()) {
                                            $deletions_converted++;
                                        } else {
                                            echo "\nCould not update corresponding assessment target record ({$assessment_target->getID()}) for previously deleted task with deleted_task_id of {$previously_deleted_task->getID()}. DB said: " . $db->ErrorMsg();
                                        }
                                    } else {
                                        //echo "\nCould not find a corresponding assessment target record for previously deleted task with deleted_task_id of {$previously_deleted_task->getID()}.";

                                        $new_deletion_data = array();
                                        $new_deletion_data["dassessment_id"] = $assessment->getID();
                                        $new_deletion_data["adistribution_id"] = $assessment->getADistributionID();
                                        $new_deletion_data["target_type"] = $previously_deleted_task->getTargetType();
                                        $new_deletion_data["target_value"] = $previously_deleted_task->getTargetID();
                                        $new_deletion_data["deleted_reason_id"] = $previously_deleted_task->getDeletedReasonID();
                                        $new_deletion_data["deleted_reason_notes"] = $previously_deleted_task->getDeletedReasonNotes();
                                        $new_deletion_data["deleted_date"] = $previously_deleted_task->getCreatedDate();
                                        $new_deletion_data["deleted_by"] = $previously_deleted_task->getCreatedBy();
                                        $new_deletion_data["visible"] = $previously_deleted_task->getVisible();
                                        $new_deletion_data["created_date"] = time();
                                        $new_deletion_data["created_by"] = 1;
                                        $new_deletion_data["updated_date"] = time();
                                        $new_deletion_data["updated_by"] = 1;

                                        $new_deletion = new Models_Assessments_AssessmentTarget($new_deletion_data);

                                        if ($new_deletion->insert()) {
                                            $deletions_added++;
                                        } else {
                                            echo "\nCould not insert corresponding assessment target record ({$assessment_target->getID()}) for previously deleted task with deleted_task_id of {$previously_deleted_task->getID()}. DB said: " . $db->ErrorMsg();
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                echo "\n\nConverted {$deletions_converted} deleted_tasks to deleted assessment_task records.\n";
                echo "\nAdded {$deletions_added} assessment_tasks to correspond to deleted_tasks records.\n";
            }
        } else {
            return false;
        }

        return true;
    }

    /**
     * Required: SQL / PHP that performs the downgrade migration.
     */
    public function down() {
        $this->record();
        ?>
        ALTER TABLE `cbl_distribution_assessment_targets` DROP COLUMN `deleted_reason_id`;
        ALTER TABLE `cbl_distribution_assessment_targets` DROP COLUMN `deleted_reason_notes`;
        ALTER TABLE `cbl_distribution_assessment_targets` DROP COLUMN `deleted_by`;
        ALTER TABLE `cbl_distribution_assessment_targets` DROP COLUMN `visible`;
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
        if ($migration->columnExists(DATABASE_NAME, "cbl_distribution_assessment_targets", "deleted_reason_id") &&
            $migration->columnExists(DATABASE_NAME, "cbl_distribution_assessment_targets", "deleted_reason_notes") &&
            $migration->columnExists(DATABASE_NAME, "cbl_distribution_assessment_targets", "deleted_by") &&
            $migration->columnExists(DATABASE_NAME, "cbl_distribution_assessment_targets", "visible")
        ) {
            return 1;
        }
        return 0;
    }
}
