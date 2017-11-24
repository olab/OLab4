<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Cron job responsible for sending pending notifications.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Josh Dillon <jdillon@queensu.ca>
 * @author Developer: James Ellis <james.ellis@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 *
*/

@set_time_limit(0);
@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    dirname(__FILE__) . "/../core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code.
 */
require_once("init.inc.php");

$console_out = false; // set true to enable console output from queue_assessments object
if (isset($argv) && is_array($argv)) {
    if (in_array("--console-out", $argv)) {
        $console_out = true;
    }
}

if ($console_out) {
    $start_time = microtime(true);
    echo "\n\n";
    echo "[Started running distribution queue cron job @ " . $start_time . "] \n";
}

$queue_assessments = new Entrada_Utilities_Assessments_QueueAssessment();

if (!isset($_SERVER["SERVER_NAME"])) {
    $_SERVER["SERVER_NAME"] = "localhost";
}
if (!isset($_SERVER["REQUEST_URI"])) {
    $_SERVER["REQUEST_URI"] = (isset($_SERVER["SCRIPT_NAME"]))? $_SERVER["SCRIPT_NAME"] : "undefined";
}

$queue_assessments->run($console_out);

if ($console_out) {
    $end_time = microtime(true);
    $total_runtime = $end_time - $start_time;
    echo "[Finished running distribution queue cron job @ " . $end_time . "] \n";
    echo "[Total runtime in seconds: " . $total_runtime . "]\n\n";
}