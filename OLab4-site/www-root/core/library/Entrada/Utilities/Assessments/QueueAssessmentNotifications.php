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
 * This utility will process the "extra" assessment notification options. An optional
 * assessment ID can be specified, otherwise it will process all relevant assessments.
 * Each type of notification can be turned off using the flags during instantiation.
 *
 * @author Organisation: Queen's University
 * @author Developer Joshua Belanger <jb301@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 */
class Entrada_Utilities_Assessments_QueueAssessmentNotifications extends Entrada_Assessments_Base {

    protected   $dassessment_id = null,
                $process_expiry_warnings = true;

    public function __construct($arr = null) {
        parent::__construct($arr);
    }

    public function run($verbosity = false) {

        $this->setVerbose($verbosity);

        if ($this->process_expiry_warnings) {
            $this->queueExpiryWarningNotifications();
        }

        $this->verboseOut("\n{$this->cliString("Notification queueing completed", "green")}.\n");
    }

    /**
     * Sends expiry warning notifications. This will use the provided assessment id if possible, otherwise it will
     * process all assessments that have passed their expiry notification date.
     */
    private function queueExpiryWarningNotifications() {
        global $ENTRADA_TEMPLATE;

        $this->verboseOut("Queuing expiry warning notifications\n\n");

        $assessment_model = new Models_Assessments_Assessor();
        $assessments = array();

        if ($this->dassessment_id) {
            $specified_assessment = $assessment_model::fetchRowByID($this->dassessment_id);
            if ($specified_assessment) {
                if ($specified_assessment->getExpiryNotificationDate() <= time()) {
                    $assessments[] = $specified_assessment;
                } else {
                    $this->verboseOut("{$this->cliString("The provided assessment has no matching expiry notification date", "red")}.\n");
                }
            } else {
                $this->verboseOut("{$this->cliString("The provided assessment could not be found", "red")}.\n");
            }
        } else {
            $assessments = $assessment_model->fetchAllByExpiryNotificationDate(time());
        }

        if ($assessments) {
            foreach ($assessments as $assessment) {

                // Instantiate our assessments API.
                $assessment_api = new Entrada_Assessments_Assessment(
                    array(
                        "dassessment_id" => $assessment->getID(),
                        "fetch_form_data" => false,
                        "limit_dataset" => array("targets", "progress")
                    )
                );

                // If we determined that the assessment is complete and should not be sent a warning,
                // clear the expiry notification date so that this assessment is not processed again.
                $assessment_api->fetchAssessmentData();
                $completed = $assessment_api->isOverallAssessmentCompleted();
                $already_expired = $assessment->getExpiryDate() <= time() ? true : false;
                $should_send = ($already_expired || $completed) ? false : true;

                if (!$should_send) {
                    $assessment->setExpiryNotificationDate(null);
                    $assessment->update();
                    continue;
                }

                $this->clearStorage();
                $this->verboseOut("Queuing expiry warning notification for {$assessment->getID()}. ");
                $queue_start_time = microtime(true);
                $ENTRADA_TEMPLATE->setActiveTemplate($assessment->getOrganisationID());

                $external = $assessment->getAssessorType() == "external" ? true : false;

                $status = $this->queueExpiryWarningNotification(
                    $assessment->getID(),
                    $assessment->getADistributionID(),
                    $assessment->getAssessorValue(),
                    "assessment_expiry_warning",
                    $assessment->getAssessorValue(),
                    $external
                );

                $queue_end_time = microtime(true);
                $queue_runtime = sprintf("%.3f", ($queue_end_time - $queue_start_time));
                $colour = ($queue_runtime > 1) ? "red" : "blue";

                if ($status) {
                    // Clear the expiry notification date so that this assessment is not processed again.
                    $assessment->setExpiryNotificationDate(null);
                    $assessment->update();
                    $this->verboseOut("Expiry warning notification for {$assessment->getID()} queued. Took {$this->cliString($queue_runtime, $colour)} seconds. \n");
                } else {
                    $this->verboseOut("{$this->cliString("Unable to queue expiry warning notification. Took {$this->cliString($queue_runtime, $colour)} seconds. \n", "red")}.\n");
                }
            }
        }

        $this->verboseOut("\n{$this->cliString("Expiry warning notification queueing completed", "green")}.\n");
    }

}