<?php
class Migrate_2017_02_13_092722_558 extends Entrada_Cli_Migrate {

    /**
     * Required: SQL / PHP that performs the upgrade migration.
     */
    public function up() {

        $this->record();
        // Update assessment records to point to their respective schedules
        ?>
        UPDATE `cbl_distribution_assessments` a
        JOIN `cbl_assessment_distributions` d ON a.`adistribution_id` = d.`adistribution_id`
        JOIN `cbl_assessment_distribution_schedule` s ON a.`adistribution_id` = s.`adistribution_id`
        SET a.`associated_record_type` = 'schedule_id', a.`associated_record_id` = s.`schedule_id`;
        <?php
        $this->stop();
        $this->run();
        $class_name = get_class($this);
        $ignored_assessment_updates = array();

        echo "\n$class_name: Execute target creation migration for " . $this->color("non-delegation", "green") . " based assessments:";

        // Now run the logic to create the needed assessment target records
        $all_distro_ids = Models_Assessments_Distribution::fetchAllDistributionDateData();
        if (empty($all_distro_ids)) {
            echo "\n$class_name: No assessment records found.";
        } else {
            // Migrate distributions for non-delegations
            foreach ($all_distro_ids as $adistribution_id => $distro_meta) {
                if ($distro_meta["cperiod_id"] > 0 && $distro_meta["release_date"] >= $distro_meta["cperiod_end"]) {
                    continue;
                }
                if ($assessments = Models_Assessments_Assessor::fetchAllRecordsByDistributionID($adistribution_id)) {
                    foreach ($assessments as $assessment) {
                        $dassessment_id = $assessment->getID();
                        if ($assessment->getDeletedDate()) {
                            continue;
                        }
                        $assessment_targets = array();
                        $dt_user_id = null;
                        $dt_assessor_id = null;

                        // Fetch all of the existing target records. If we have some, we can skip the assessment targets check
                        $existing_target_records = Models_Assessments_AssessmentTarget::fetchAllByDassessmentID($dassessment_id);
                        if (!empty($existing_target_records)) {

                            echo "\n$class_name: Target records already exist for dassessment_id: $dassessment_id / adistribution_id: $adistribution_id (skipping)";

                        } else {

                            $distribution_targets = Models_Assessments_Distribution_Target::fetchAllByDistributionID($adistribution_id);
                            if ($distribution_targets) {
                                if (!empty($distribution_targets) && count($distribution_targets) == 1) {
                                    $single_distribution_target = array_shift($distribution_targets);
                                    if ($single_distribution_target->getTargetType() == "self") {
                                        $dt_assessor_id = $dt_user_id = $assessment->getAssessorValue();
                                    }
                                }
                            }
                            $assessment_targets = Models_Assessments_Distribution_Target::getAssessmentTargets($adistribution_id, $dassessment_id, $dt_assessor_id, $dt_user_id);
                        }

                        if (is_array($assessment_targets) && !empty($assessment_targets)) {

                            $found_associated_records = array();
                            $associated_record_id = null;
                            $associated_record_type = null;

                            $targets_added = 0;

                            foreach ($assessment_targets as $assessment_target) {
                                // Create an assessment target record
                                $new_record = new Models_Assessments_AssessmentTarget();
                                $new_record_data = $new_record->toArray();
                                $new_record_data["dassessment_id"] = $dassessment_id;
                                $new_record_data["adistribution_id"] = $adistribution_id;
                                $new_record_data["created_date"] = time();
                                $new_record_data["created_by"] = 1;
                                $new_record_data["updated_date"] = time();
                                $new_record_data["updated_by"] = 1;
                                $new_record_data["visible"] = 1;
                                $new_record_data["target_value"] = $assessment_target["target_record_id"];

                                $associated_record_type = $assessment_target["distribution_target_type"];
                                $associated_record_id = $assessment_target["distribution_target_id"];

                                switch ($assessment_target["distribution_target_type"]) {
                                    case "eventtype_id":
                                        if ($assessment_target["distribution_target_scope"] == "self" && $assessment_target["distribution_target_role"] == "any") {
                                            $new_record_data["target_type"] = "event_id";
                                        }
                                        break;
                                    case "schedule_id":
                                        if ($assessment_target["distribution_target_scope"] == "self" && $assessment_target["distribution_target_role"] == "any") {
                                            $new_record_data["target_type"] = "schedule_id";
                                        }
                                        break;
                                    case "course_id":
                                        if ($assessment_target["distribution_target_scope"] == "self" && $assessment_target["distribution_target_role"] == "any") {
                                            $new_record_data["target_type"] = "course_id";
                                        }
                                        break;
                                    default:
                                        $new_record_data["target_type"] = "proxy_id";
                                        $associated_record_id = null; // Proxy target type can have multiple specific proxies.
                                        break;
                                }

                                // Check and see if this target record already exists
                                // Insert this one if not
                                $found_associated_records[$associated_record_type] = $associated_record_type;

                                // Check and see if this target record already exists
                                // Insert this one if not
                                $found_associated_records[$associated_record_type] = $associated_record_type;

                                if ($dt_assessor_id) {
                                    $new_record_data["target_type"] = "proxy_id";
                                }
                                $existing = Models_Assessments_AssessmentTarget::fetchRowByDAssessmentIDTargetTypeTargetValue($dassessment_id, $new_record_data["target_type"], $new_record_data["target_value"]);
                                if (!$existing) {
                                    $new_record->fromArray($new_record_data);
                                    if (!$new_record->insert()) {
                                        echo "\n$class_name: Failed to insert new record for dassessment ID: $dassessment_id";
                                    } else {
                                        $targets_added++;
                                    }
                                } else {
                                    echo "\n$class_name: Found an existing target record for assessment id: {$dassessment_id}";
                                }
                            }
                            echo "\n$class_name: Added " . $this->color("$targets_added", "green") . " new target records for dassessment_id = $dassessment_id / adistribution_id = $adistribution_id";

                            if ($dt_assessor_id) {
                                // Was a self assessment, so override the previous settings
                                $associated_record_id = null;
                                $associated_record_type = "proxy_id";
                            }

                            // Update the assessment record with the associated record type/id if it isn't already set by an external process.
                            // We assume the existing data is fine (which it should be if the migration ran)
                            $assessment_update = $assessment->toArray();
                            if ($assessment_update["associated_record_type"] == null) {
                                if (count($found_associated_records) > 1) {
                                    echo "\n$class_name: Found multiple associated record types, ". $this->color("ignoring assessment update (dassessment_id: $dassessment_id)", "pink");
                                    $ignored_assessment_updates[] = $dassessment_id;
                                } else {
                                    $assessment_update["associated_record_type"] = $associated_record_type;
                                    $assessment_update["associated_record_id"] = $associated_record_id;
                                    $assessment->fromArray($assessment_update);
                                    echo "\n$class_name: Updating assessment record for ID ". $this->color("'$dassessment_id'", "green");
                                    if (!$assessment->update()) {
                                        echo "\n$class_name: " . $this->color("Failed to save assessment ID: '$dassessment_id'!", "red");
                                    }
                                }
                            } else {
                                echo "\n$class_name: Assessment (dassessment_id: '$dassessment_id' / adistribution_id: '$adistribution_id') already has appropriate associated values (skipping update)";
                            }
                        }
                    }
                } else {
                    echo "\n$class_name: No assessments for adistribution_id '$adistribution_id' (skipping)";
                }
            }
        }
        echo "\n$class_name: Assessment target update ". $this->color("completed for non-delegation", "green") . " based assessments.";

        echo "\n$class_name: Execute target creation migration for ". $this->color("delegation", "green"). " based assessments:";

        // Find the delegations. We only want to update their assessment task with the relevant IDs.
        $all_delegation_ids = Models_Assessments_Distribution::fetchAllDistributionDateData(null, false, true);
        if (empty($all_delegation_ids)) {
            echo "\n$class_name: No delegation distributions found.";
        } else {
            foreach ($all_delegation_ids as $adistribution_id => $delegation_meta) {
                if ($delegation_meta["cperiod_id"] > 0 && $delegation_meta["release_date"] >= $delegation_meta["cperiod_end"]) {
                    continue;
                }
                if ($assessments = Models_Assessments_Assessor::fetchAllRecordsByDistributionID($adistribution_id)) {
                    foreach ($assessments as $assessment) {
                        if ($assessment->getAssociatedRecordType()) {
                            continue;
                        }
                        $dassessment_id = $assessment->getID();
                        // Check the distribution target
                        $distribution_targets = Models_Assessments_Distribution_Target::fetchAllByDistributionID($adistribution_id);
                        if (empty($distribution_targets)) {
                            echo "\n$class_name: There are no distribution targets for this distribution (adistribution_id = $adistribution_id)";
                        } else {
                            if (count($distribution_targets) == 1) {
                                // Use the distribution target
                                $distribution_target = array_shift($distribution_targets);
                                $assessment_data = $assessment->toArray();
                                if (!$distribution_target->getTargetID() && $distribution_target->getTargetType() == "self") {
                                    $assessment_data["associated_record_type"] = "proxy_id";
                                    $assessment_data["associated_record_id"] = null;
                                } else {
                                    $assessment_data["associated_record_type"] = $distribution_target->getTargetType();
                                    $assessment_data["associated_record_id"] = $distribution_target->getTargetID();
                                }
                                $assessment->fromArray($assessment_data);
                                echo "\n$class_name: Update assessment (dassessment_id = " . $this->color("$dassessment_id", "green");
                                echo "/ adistribution_id = $adistribution_id) (Using distribution target type " . $this->color("{$distribution_target->getTargetType()}", "green");
                                echo "/ Target ID {$distribution_target->getTargetID()})";
                                if (!$assessment->update()) {
                                    echo "\n$class_name: ". $this->color("Failed to save assessment ID: '$dassessment_id'!", "red");
                                }
                            } else {
                                // Use proxy
                                $assessment_data = $assessment->toArray();
                                $assessment_data["associated_record_type"] = "proxy_id";
                                $assessment_data["associated_record_id"] = null;
                                $assessment->fromArray($assessment_data);
                                echo "\n$class_name: Update assessment (dassessment_id = " . $this->color("$dassessment_id", "green") . ") (forcing type = 'proxy_id')";
                                if (!$assessment->update()) {
                                    echo "\n$class_name: ". $this->color("Failed to save assessment ID: '$dassessment_id'!", "red");
                                }
                            }
                        }
                    }
                }
            }
        }

        echo "\n$class_name: Assessment target update completed for " . $this->color("delegation", "green") . " based assessments.";

        // Migrate "additional assessments"
        echo "\n$class_name: Assessment target update for ". $this->color("'additional'", "green") . " assessments:";
        $all_additional_assessments = Models_Assessments_Assessor::fetchAllAdditionalTasksForDistributions();
        if (is_array($all_additional_assessments)) {
            foreach ($all_additional_assessments as $additional_assessment) {
                if ($distributon = Models_Assessments_Distribution::fetchRowByID($additional_assessment["adistribution_id"])) {
                    $dt_user_id = $additional_assessment["assessor_value"];
                    $dt_assessor_id = $additional_assessment["assessor_value"];
                    $assessment_targets = Models_Assessments_Distribution_Target::getAssessmentTargets($additional_assessment["adistribution_id"], $additional_assessment["dassessment_id"], $dt_assessor_id, $dt_user_id, $additional_assessment["external_hash"] ? true : false);

                    foreach ($assessment_targets as $assessment_target) {
                        // Create an assessment target record
                        $new_record = new Models_Assessments_AssessmentTarget();
                        $new_record_data = $new_record->toArray();
                        $new_record_data["dassessment_id"] = $additional_assessment["dassessment_id"];
                        $new_record_data["adistribution_id"] = $additional_assessment["adistribution_id"];
                        $new_record_data["created_date"] = time();
                        $new_record_data["created_by"] = 1;
                        $new_record_data["updated_date"] = time();
                        $new_record_data["updated_by"] = 1;
                        $new_record_data["target_value"] = $assessment_target["target_record_id"];

                        $associated_record_type = $assessment_target["distribution_target_type"];
                        $associated_record_id = $assessment_target["distribution_target_id"];

                        switch ($assessment_target["distribution_target_type"]) {
                            case "eventtype_id":
                                if ($assessment_target["distribution_target_scope"] == "self" && $assessment_target["distribution_target_role"] == "any") {
                                    $new_record_data["target_type"] = "event_id";
                                }
                                break;
                            case "schedule_id":
                                if ($assessment_target["distribution_target_scope"] == "self" && $assessment_target["distribution_target_role"] == "any") {
                                    $new_record_data["target_type"] = "schedule_id";
                                }
                                break;
                            case "course_id":
                                if ($assessment_target["distribution_target_scope"] == "self" && $assessment_target["distribution_target_role"] == "any") {
                                    $new_record_data["target_type"] = "course_id";
                                }
                                break;
                            default:
                                $new_record_data["target_type"] = "proxy_id";
                                $associated_record_id = null; // Proxy target type can have multiple specific proxies.
                                break;
                        }

                        // Check and see if this target record already exists
                        // Insert this one if not
                        $found_associated_records[$associated_record_type] = $associated_record_type;

                        if ($dt_assessor_id) {
                            $new_record_data["target_type"] = "proxy_id";
                        }
                        $existing = Models_Assessments_AssessmentTarget::fetchRowByDAssessmentIDTargetTypeTargetValue($additional_assessment["dassessment_id"], $new_record_data["target_type"], $new_record_data["target_value"]);
                        if (!$existing) {
                            $new_record->fromArray($new_record_data);
                            if (!$new_record->insert()) {
                                echo "\n$class_name: ". $this->color("Failed to insert new record for adistribution_id {$additional_assessment["adistribution_id"]} dassessment {$additional_assessment["dassessment_id"]}", "red");
                            } else {
                                echo "\n$class_name: Inserted new target record: " . $this->color($new_record->getID(), "green");
                            }
                        } else {
                            echo "\n$class_name: Found an existing target record for adistribution_id {$additional_assessment["adistribution_id"]} assessment id: {$additional_assessment["dassessment_id"]}";
                        }
                        if ($dt_assessor_id) {
                            // Was a self assessment, so override the previous settings
                            $associated_record_id = null;
                            $associated_record_type = "proxy_id";
                        }

                        // Update the assessment record with the associated record type/id if it isn't already set by an external process.
                        // We assume the existing data is fine (which it should be if the migration ran)
                        $assessment_update_record = Models_Assessments_Assessor::fetchRowByID($additional_assessment["dassessment_id"]);
                        $assessment_update = array();
                        if ($assessment_update_record) {
                            $assessment_update = $assessment_update_record->toArray();
                        }
                        if (@$assessment_update["associated_record_type"] == null) {
                            if (count($found_associated_records) > 1) {
                                echo "\n$class_name: Found multiple types, ";
                                echo $this->color("ignoring assessment update (dassessment_id: {$additional_assessment["dassessment_id"]}).", "red");
                                echo " This assessment record will have to be updated manually.";
                                $ignored_assessment_updates[] = $additional_assessment["dassessment_id"];
                            } else {
                                $assessment_update["associated_record_type"] = $associated_record_type;
                                $assessment_update["associated_record_id"] = $associated_record_id;
                                $assessment_update_record->fromArray($assessment_update);
                                echo "\n$class_name: Update adistribution_id {$additional_assessment["adistribution_id"]} dassessment_id: " . $this->color("{$additional_assessment["dassessment_id"]}", "green");
                                if (!$assessment_update_record->update()) {
                                    echo "\n$class_name: " . $this->color("Failed to update adistribution_id {$additional_assessment["adistribution_id"]} assessment ID: '{$additional_assessment["dassessment_id"]}'! Continuing...", "pink");
                                }
                            }
                        } else {
                            echo "\n$class_name: adistribution_id {$additional_assessment["adistribution_id"]} dassessment_id: {$additional_assessment["dassessment_id"]} already has appropriate values.";
                        }
                    }

                } else {
                    echo "\n$class_name: " . $this->color("Failed to fetch distribution {$additional_assessment["adistribution_id"]}!", "pink");
                }
            }
        } else {
            echo "\n$class_name: No additional assessments found.";
        }
        echo "\n$class_name: Assessment target update completed for " . $this->color("'additional assessments'.", "green");

        if (!empty($ignored_assessment_updates)) {
            echo "\n$class_name: The following assessments (cbl_distribution_assessment records) were ignored due to ambiguous distribution configurations. Their associated_record_type fields will have to be manually updated:\n";
            foreach ($ignored_assessment_updates as $ignored_assessment_update) {
                echo "   dassessment_id: $ignored_assessment_update\n";
            }
        }
        return true;
    }

    /**
     * Required: SQL / PHP that performs the downgrade migration.
     */
    public function down() {
        $this->record();
        // Sorry, there's no going back from this one.
        // However, the added data to the associated_record_type/id fields will not impact previous versions of the code (they are ignored where applicable).
        // The additional target records created will also be ignored as the old versions of the code use inference instead of the actual target records.
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
