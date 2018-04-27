<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Tool for migration of assessment target types.
 * Sets to evaluation if it cannot be reliably determined.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Joshua Belanger <jb301@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */
@set_time_limit(0);
@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../../../www-root/core",
    dirname(__FILE__) . "/../../../www-root/core/includes",
    dirname(__FILE__) . "/../../../www-root/core/library",
    dirname(__FILE__) . "/../../../www-root/core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

/**
 * Usage blurb. When displayed, exits script.
 */
function show_this_usage() {
    echo "\nAssessment Target Migration Tool\n";
    echo "\nUsage: php assessment-target-type-migration-tool.php [--usage|--execute] [--f]";
    echo "\nAvailable Execution Modes:";
    echo "\n  --usage                         Brings up this help screen.";
    echo "\n  --execute                       Default execution, honours flags.";
    echo "\n  --execute_clean                 Truncate scales, trees, variables, blueprints and execute all migrations (force 'yes').";
    echo "\nAvailable Flags:";
    echo "\n  --f                             Force answer 'Yes' for all execution mode questions.";
    echo "\n  --clear_lock                    Clears the lock file to allow multiple migration executions.";
    echo "\n  --all                           Execute all migrations and data inserts.";
    echo "\n  --none                          Execute none of the migrations and data inserts (only obey truncate flags).";
    echo "\n\n";
    exit();
}

/**
 * Explode a string into separate SQL statements.
 *
 * @param string $string
 * @return array
 */
function parse_sql($string = "") {
    $sql_query = "";
    $sql = array();
    if ($string) {
        $lines = preg_split('/$\R?^/m', $string);
        foreach ($lines as $sql_line) {
            if ((trim($sql_line) != "") && (strpos($sql_line, "--") === false)) {
                $sql_query .= $sql_line;
                // Look for the end of the current statement - semi-colon with only whitespace after.
                if (preg_match('/;[\s]*$/', $sql_line)) {
                    $sql[] = $sql_query;
                    $sql_query = "";
                }
            }
        }
    }
    return $sql;
}

/**
 * Validate and sanitize the command line arguments.
 *
 * @param array $argv
 * @return array
 */
function validate_arguments($argv) {
    $sanitized = array();
    $flags = array();
    $params = array();
    if (count($argv) == 1) {
        show_this_usage();
    }

    foreach ($argv as $argument) {
        $exploded_flags = explode("--", $argument);
        if (count($exploded_flags) == 2 && $exploded_flags[0] == "") {
            $flags[] = $exploded_flags[1];
        } else {
            $exploded_param = explode("=", $argument);
            if (count($exploded_param) == 2) {
                $params[$exploded_param[0]] = $exploded_param[1];
            }
        }
    }
    $sanitized["flags"] = array_map(
        function($v) {
            return clean_input($v, array("trim"));
        },
        $flags
    );

    return $sanitized;
}

/**
 * Wait for user input in command line. Case insensitive.
 * Return bool true when input is accepted, return false when input is not valid.
 *
 * @param $wait_string
 * @param string $success_string
 * @param string $abort_string
 * @param array $wait_tokens
 * @return bool
 */
function wait_for_input($wait_string, $success_string = "", $abort_string = "", $wait_tokens = array("yes", "y")) {
    echo $wait_string;
    $status = true;
    $handle = fopen ("php://stdin","r");
    $line = fgets($handle);
    $filtered = strtolower(trim($line));
    if (!in_array($filtered, $wait_tokens)){
        echo $abort_string;
        $status = false;
    } else {
        echo $success_string;
    }
    fclose($handle);
    return $status;
}

/**
 * Build a settings array and query the user what settings to set.
 * Exits script when action is not set for execution.
 *
 * @param $arguments
 * @return array
 */
function configure_settings_array($arguments) {
    $action = "--usage";

    if (in_array("clear_lock", $arguments)) {
        if (!remove_lockfile()) {
            exit("\nFailed to clear lock. Exiting...");
        }
    }
    if (in_array("execute", $arguments["flags"])) {
        $action = "--execute";
    } else if (in_array("execute_clean", $arguments["flags"])) {
        $action = "--execute_clean";
    }
    $force_yes = false;
    if (in_array("f", $arguments["flags"])) {
        $force_yes = true;
    }
    if ($action != "--execute" && $action != "--execute_clean") {
        show_this_usage(); // exits
    }
    echo "\nThis will insert and adjust the required records to make your assessment targets use the correct type.\n\n";
    if (!$force_yes) {
        if (!wait_for_input("Type \"Yes\" to continue...")) {
            echo "Aborting...\n";
            exit();
        }
    }
    echo "\n";
    echo "Proceeding...";

    if ($action == "--execute_clean") {
        return array(
            "migrate_assessment_type" => true,
            "action" => $action,
            "force_yes" => true
        );
    }

    if (in_array("all", $arguments["flags"])) {
        $migrate_assessment_type = true;
    } else if (in_array("none", $arguments["flags"])) {
        $migrate_assessment_type = false;
    } else {
        $migrate_assessment_type = $force_yes ? true : wait_for_input("\n\nMigrate distribution assessment type data? (Type 'Yes' to confirm)\n", "Will migrate distribution assessment type data.", "Will NOT migrate distribution assessment type data.");

    }

    $settings = array();
    $settings["migrate_assessment_type"] = $migrate_assessment_type;
    $settings["action"] = $action;
    $settings["force_yes"] = $force_yes;

    return $settings;
}

/**
 * Main point of execution
 *
 * @param array $arguments
 */
function run($arguments) {

    $settings = configure_settings_array($arguments); // Exits when --execute is not set. Queries user for modes.

    if ($settings["migrate_assessment_type"]) {
        migrate_distribution_assessment_type();
    }
    echo "\n\nProcess complete.\n";
    echo "\n";
}

/**
 * Script Execution start:
 */

$sanitized_arguments = validate_arguments($argv);
if (empty($sanitized_arguments)) {
    show_this_usage();
}

/**
 * Execute main
 */
run($sanitized_arguments);

/**
 * Migrate the distribution assessment type field. Determines what the appropriate value for the assessment type should be in the distribution record.
 * Target records are also updated afterward.
 */
function migrate_distribution_assessment_type() {
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
}