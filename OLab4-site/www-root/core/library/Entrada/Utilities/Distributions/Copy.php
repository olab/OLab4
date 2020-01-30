<?php

/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Entrada is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Entrada is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Entrada.  If not, see <http://www.gnu.org/licenses/>.
 *
 * This class contains methods to copy distributions. It needs to be
 * provided with authors to search for, a CPeriodID to copy from,
 * and a CPeriodID to copy to, as well as the drafts to search
 * for the updated schedules that use the new CPeriodIDs. The execute
 * parameter controls whether or not the new entities will be
 * pushed to the database or simply returned as a preview of the
 * changes that would take place.
 *
 * @author Organisation: Queen's University
 * @author Developer: Joshua Belanger <jb301@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 */
class Entrada_Utilities_Distributions_Copy {

    private $result_status = array();

    public function copyByAuthorTypeAuthorIDFromCPeriodToCPeriod($author_type, $author_value, $old_cperiod_id, $new_cperiod_id, $draft_id, $execute = false) {
        global $translate;


        $authors = Models_Assessments_Distribution_Author::fetchAllByAuthorTypeAuthorValue($author_type, $author_value);
        if ($authors) {
            foreach ($authors as $author) {
                /*
                 * TODO change this to run through in non-execution to check for errors before executing
                 */

                $distribution = Models_Assessments_Distribution::fetchRowByID($author->getAdistributionID());
                if ($distribution) {
                    if ($distribution->getCPeriodID() == $old_cperiod_id) {
                        $this->copyDistribution($distribution, $new_cperiod_id, $draft_id, $execute);
                    }
                }
            }
        } else {
            $this->result_status[0][0] = array("status" => "success", "data" => $translate->_("No distribution authors found based on the provided parameters."));
        }
    }

    public function copyByCourseIDFromCPeriodToCPeriod($course_id, $old_cperiod_id, $new_cperiod_id, $draft_id, $execute = false) {
        global $translate;

        $distributions = Models_Assessments_Distribution::fetchAllByCourseID($course_id);
        if ($distributions) {
            foreach ($distributions as $distribution) {
                /*
                 * TODO change this to run through in non-execution to check for errors before executing
                 */

                if ($distribution->getCPeriodID() == $old_cperiod_id) {
                    $this->copyDistribution($distribution, $new_cperiod_id, $draft_id, $execute);
                }
            }
        } else {
            $this->result_status[0][0] = array("status" => "success", "data" => $translate->_("No active distributions found with the provided course ID."));
        }
    }

    private function copyDistribution($distribution, $new_cperiod_id, $draft_id, $execute) {
        global $translate;
        $new_schedule_associations = array();

        if ($distribution) {
            $distribution_id = $distribution->getID();

            // Only process distributions that are schedule based.
            $distribution_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($distribution_id);
            if ($distribution_schedule) {

                $old_schedule = Models_Schedule::fetchRowByID($distribution_schedule->getScheduleID());
                if ($old_schedule) {

                    $old_rotation = false;

                    // The copied_from field is only relevant to the parent rotation stream, not blocks.
                    if ($old_schedule->getScheduleType() == "rotation_stream") {
                        $old_rotation = $old_schedule;
                    } elseif ($old_schedule->getScheduleType() == "rotation_block") {
                        $old_rotation = Models_Schedule::fetchRowByID($old_schedule->getScheduleParentID());
                    }

                    if ($old_rotation) {
                        // Determine the equivalent schedule for the new Curriculum Period based on the copied_from id within the provided draft.
                        if ($draft_id) {
                            $draft = Models_Schedule_Draft::fetchRowByID($draft_id);
                            if ($draft) {
                                // Check copied_from of the newer rotations.
                                $new_rotations = Models_Schedule::fetchAllByDraftID($draft_id, "rotation_stream");
                                if ($new_rotations) {
                                    foreach ($new_rotations as $new_rotation) {
                                        if ($new_rotation->getCopiedFrom() == $old_rotation->getID()) {
                                            // Store a reference to copied schedules so we don't need to search every time.
                                            $new_schedule_associations[$old_schedule->getID()] = $new_rotation->getID();
                                        }
                                    }
                                } else {
                                    $this->result_status[$distribution_id][] = array("status" => "error", "data" => $translate->_("No rotations found in the provided draft."));
                                }
                            } else {
                                $this->result_status[$distribution_id][] = array("status" => "error", "data" => $translate->_("Unable to fetch the draft provided."));
                            }
                        } else {
                            $this->result_status[$distribution_id][] = array("status" => "error", "data" => $translate->_("No draft ID to copy to provided."));
                        }
                    } else {
                        $this->result_status[$distribution_id][] = array("status" => "error", "data" => $translate->_("Unable to fetch the previous/existing schedule associated with " . $distribution->getTitle() . "."));
                    }

                    // If the array key exists and has a value associated with it, we have successfully found the new schedule to associate the distribution with.
                    if (array_key_exists($old_schedule->getID(), $new_schedule_associations) && $new_schedule_associations[$old_schedule->getID()]) {

                        $new_schedule_id = false;
                        if ($old_schedule->getScheduleType() == "rotation_stream") {
                            // If the schedule was the overall rotation, we don't need to worry about finding the block type and position/order.
                            $new_schedule_id = $new_schedule_associations[$old_schedule->getID()];

                        } elseif ($old_schedule->getScheduleType() == "rotation_block") {

                            // The schedule was a block, so we must determine which block type and position to utilize.
                            $new_blocks = Models_Schedule::fetchAllByParentID($new_schedule_associations[$old_schedule->getID()]);
                            if ($new_blocks) {
                                foreach ($new_blocks as $new_block) {
                                    if ($new_block->getBlockTypeID() == $old_schedule->getBlockTypeID() && $new_block->getOrder() == $old_schedule->getOrder()) {
                                        $new_schedule_id = $new_block->getID();
                                    }
                                }
                            } else {
                                $this->result_status[$distribution_id][] = array("status" => "error", "data" => $translate->_("Unable to fetch the blocks associated with the new schedule for " . $distribution->getTitle() . "."));
                            }
                        }

                        // We finally have a reference to the ID of the new schedule to copy to. We are good to copy the distribution.
                        if ($new_schedule_id) {

                            // Ensure the new version does not already exist (same name and cperiod_id, and the same schedule within the rotation schedule).
                            $exists = false;
                            $existing_distributions = Models_Assessments_Distribution::fetchAllByTitleCPeriodID($distribution->getTitle(), $new_cperiod_id);
                            if ($existing_distributions) {
                                foreach ($existing_distributions as $existing_distribution) {
                                    $existing_distribution_schedule = Models_Assessments_Distribution_Schedule::fetchRowByDistributionID($existing_distribution->getID());
                                    if ($existing_distribution_schedule && $existing_distribution_schedule->getScheduleID() == $new_schedule_id) {
                                        $exists = true;
                                        $this->result_status[$distribution_id][] = array("status" => "success", "data" => $translate->_("New distribution for the provided Curriculum Period already exists."));
                                    }
                                }
                            }

                            if (!$exists) {
                                // Distribution.
                                $new_distribution_data = $distribution->toArray();
                                unset($new_distribution_data["adistribution_id"]);
                                $new_distribution_data["cperiod_id"] = $new_cperiod_id;
                                $new_distribution_data["updated_date"] = time();
                                $new_distribution_data["created_date"] = time();
                                if ($new_distribution_data["start_date"]) {
                                    $new_distribution_data["start_date"] = strtotime("+1 year", $new_distribution_data["start_date"]);
                                }
                                if ($new_distribution_data["end_date"]) {
                                    $new_distribution_data["end_date"] = strtotime("+1 year", $new_distribution_data["end_date"]);
                                }
                                if ($new_distribution_data["release_date"]) {
                                    $new_distribution_data["release_date"] = strtotime("+1 year", $new_distribution_data["release_date"]);
                                }
                                /**
                                 * Once the CRON job is updated to respect release start and release end, this will need to be revisited.
                                 */
                                $new_distribution_data["release_start_date"] = 0;
                                $new_distribution_data["release_end_date"] = 0;

                                $new_distribution = new Models_Assessments_Distribution($new_distribution_data);
                                if ($execute) {
                                    if ($new_distribution->insert()) {
                                        $this->result_status[$distribution_id][] = array("status" => "success", "data" => $translate->_("Successfully copied distribution record."));
                                    } else {
                                        $this->result_status[$distribution_id][] = array("status" => "error", "data" => $translate->_("An error occurred while attempting to copy distribution record."));
                                    }
                                } else {
                                    $this->result_status[$distribution_id][] = array("status" => "success", "data" => $translate->_("Able to copy distribution record."));
                                }

                                // Author.
                                $authors = Models_Assessments_Distribution_Author::fetchAllByDistributionID($distribution->getID());
                                if ($authors) {
                                    foreach ($authors as $author) {
                                        $new_author_data = $author->toArray();
                                        unset($new_author_data["adauthor_id"]);
                                        $new_author_data["adistribution_id"] = $new_distribution->getID();
                                        $new_author_data["created_date"] = time();

                                        $new_author = new Models_Assessments_Distribution_Author($new_author_data);
                                        if ($execute) {
                                            if ($new_author->insert()) {
                                                $this->result_status[$distribution_id][] = array("status" => "success", "data" => $translate->_("Successfully copied author."));
                                            } else {
                                                $this->result_status[$distribution_id][] = array("status" => "error", "data" => $translate->_("An error occurred while attempting to copy author."));
                                            }
                                        } else {
                                            $this->result_status[$distribution_id][] = array("status" => "success", "data" => $translate->_("Able to copy author."));
                                        }
                                    }
                                }

                                // Targets.
                                $targets = Models_Assessments_Distribution_Target::fetchAllByDistributionID($distribution->getID());
                                if ($targets) {
                                    foreach ($targets as $target) {
                                        $new_target_data = $target->toArray();
                                        unset($new_target_data["adtarget_id"]);
                                        $new_target_data["adistribution_id"] = $new_distribution->getID();
                                        // If the target was the schedule, it will need to be updated to reference the new schedule.
                                        if ($new_target_data["target_type"] == "schedule_id") {
                                            $new_target_data["target_id"] = $new_schedule_id;
                                        }

                                        $new_target = new Models_Assessments_Distribution_Target($new_target_data);
                                        if ($execute) {
                                            if ($new_target->insert()) {
                                                $this->result_status[$distribution_id][] = array("status" => "success", "data" => $translate->_("Successfully copied target."));
                                            } else {
                                                $this->result_status[$distribution_id][] = array("status" => "error", "data" => $translate->_("An error occurred while attempting to copy target."));
                                            }
                                        } else {
                                            $this->result_status[$distribution_id][] = array("status" => "success", "data" => $translate->_("Able to copy target."));
                                        }
                                    }
                                }

                                // Assessors.
                                $assessors = Models_Assessments_Distribution_Assessor::fetchAllByDistributionID($distribution->getID());
                                if ($assessors) {
                                    foreach ($assessors as $assessor) {
                                        $new_assessor_data = $assessor->toArray();
                                        unset($new_assessor_data["adassessor_id"]);
                                        $new_assessor_data["adistribution_id"] = $new_distribution->getID();
                                        // If the assessor was the schedule, it will need to be updated to reference the new schedule.
                                        if ($new_assessor_data["assessor_type"] == "schedule_id") {
                                            $new_assessor_data["assessor_value"] = $new_schedule_id;
                                        }

                                        $new_assessor = new Models_Assessments_Distribution_Assessor($new_assessor_data);
                                        if ($execute) {
                                            if ($new_assessor->insert()) {
                                                $this->result_status[$distribution_id][] = array("status" => "success", "data" => $translate->_("Successfully copied assessor."));
                                            } else {
                                                $this->result_status[$distribution_id][] = array("status" => "error", "data" => $translate->_("An error occurred while attempting to copy assessor."));
                                            }
                                        } else {
                                            $this->result_status[$distribution_id][] = array("status" => "success", "data" => $translate->_("Able to copy assessor."));
                                        }
                                    }
                                }

                                // Delegators.
                                $delegator = Models_Assessments_Distribution_Delegator::fetchRowByDistributionID($distribution->getID());
                                if ($delegator) {
                                    $new_delegator_data = $delegator->toArray();
                                    unset($new_delegator_data["addelegator_id"]);
                                    $new_delegator_data["adistribution_id"] = $new_distribution->getID();

                                    $new_delegator = new Models_Assessments_Distribution_Delegator($new_delegator_data);
                                    if ($execute) {
                                        if ($new_delegator->insert()) {
                                            $this->result_status[$distribution_id][] = array("status" => "success", "data" => $translate->_("Successfully copied delegator."));
                                        } else {
                                            $this->result_status[$distribution_id][] = array("status" => "error", "data" => $translate->_("An error occurred while attempting to copy delegator."));
                                        }
                                    } else {
                                        $this->result_status[$distribution_id][] = array("status" => "success", "data" => $translate->_("Able to copy delegator."));
                                    }
                                }

                                // Reviewers.
                                $reviewers = Models_Assessments_Distribution_Reviewer::fetchAllByDistributionID($distribution->getID());
                                if ($reviewers) {
                                    foreach ($reviewers as $reviewer) {
                                        $new_reviewer_data = $reviewer->toArray();
                                        unset($new_reviewer_data["adreviewer_id"]);
                                        $new_reviewer_data["adistribution_id"] = $new_distribution->getID();
                                        $new_reviewer_data["updated_date"] = time();
                                        $new_reviewer_data["created_date"] = time();

                                        $new_reviewer = new Models_Assessments_Distribution_Reviewer($new_reviewer_data);
                                        if ($execute) {
                                            if ($new_reviewer->insert()) {
                                                $this->result_status[$distribution_id][] = array("status" => "success", "data" => $translate->_("Successfully copied reviewer."));
                                            } else {
                                                $this->result_status[$distribution_id][] = array("status" => "error", "data" => $translate->_("An error occurred while attempting to copy reviewer."));
                                            }
                                        } else {
                                            $this->result_status[$distribution_id][] = array("status" => "success", "data" => $translate->_("Able to copy reviewer."));
                                        }
                                    }
                                }

                                // Distribution Schedule.
                                $new_distribution_schedule_data = $distribution_schedule->toArray();
                                unset($new_distribution_schedule_data["adschedule_id"]);
                                $new_distribution_schedule_data["adistribution_id"] = $new_distribution->getID();
                                $new_distribution_schedule_data["schedule_id"] = $new_schedule_id;
                                $new_distribution_schedule_data["addelegator_id"] = ($delegator && isset($new_delegator) && $new_delegator ? $new_delegator->getID() : NULL);

                                $new_distribution_schedule = new Models_Assessments_Distribution_Schedule($new_distribution_schedule_data);
                                if ($execute) {
                                    if ($new_distribution_schedule->insert()) {
                                        $this->result_status[$distribution_id][] = array("status" => "success", "data" => $translate->_("Successfully copied distribution schedule."));
                                    } else {
                                        $this->result_status[$distribution_id][] = array("status" => "error", "data" => $translate->_("An error occurred while attempting to copy distribution schedule."));
                                    }
                                } else {
                                    $this->result_status[$distribution_id][] = array("status" => "success", "data" => $translate->_("Able to copy distribution schedule."));
                                }
                            }
                        } else {
                            $this->result_status[$distribution_id][] = array("status" => "error", "data" => $translate->_("No suitable schedule was found in the draft for " . $distribution->getTitle() . " (" . $old_schedule->getTitle() . ")."));
                        }
                    } else {
                        $this->result_status[$distribution_id][] = array("status" => "error", "data" => $translate->_("No suitable schedule was found in the draft for " . $distribution->getTitle() . " (" . $old_schedule->getTitle() . ")."));
                    }
                } else {
                    $this->result_status[$distribution_id][] = array("status" => "error", "data" => $translate->_("The schedule associated with " . $distribution->getTitle() . " was not found."));
                }
            }
        }
    }

    public function getResultStatus() {
        return $this->result_status;
    }

}