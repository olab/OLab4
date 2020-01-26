<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Cron job responsible for publishing form blueprints.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Adrian Mellognio <adrian.mellognio@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
*/

ini_set('memory_limit', '2056M');
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

$console_out = false; // set true to enable console output from Form Blueprint object
if (isset($argv) && is_array($argv)) {
    if (in_array("--console-out", $argv)) {
        $console_out = true;
    }
}

if ($console_out) {
    $start_time = microtime(true);
    echo "\n\n";
    echo "[Started running form blueprint queue cron job @ " . $start_time . "] \n";
}

$publish_blueprints = new Entrada_Utilities_Assessments_PublishFormBlueprint();

if (!isset($_SERVER["SERVER_NAME"])) {
    $_SERVER["SERVER_NAME"] = "localhost";
}
if (!isset($_SERVER["REQUEST_URI"])) {
    $_SERVER["REQUEST_URI"] = (isset($_SERVER["SCRIPT_NAME"]))? $_SERVER["SCRIPT_NAME"] : "undefined";
}

$lock_status = Entrada_Utilities::obtainExclusiveAccess("blueprint_publish");
if ($lock_status) {
    $publish_blueprints->run($console_out);
    // We release after publish, but even if we fail to release exclusive access, it will be automatically released by shutting down the DB connection.
    Entrada_Utilities::releaseExclusiveAccess("blueprint_publish");
} else {
    if ($console_out) {
        echo "\nPublish Blueprints is already running, not executing.\n";
    }
    application_log("error", "Unable to get exclusive access to publish blueprints. Cron is already running\n");
}

if ($console_out) {
    $end_time = microtime(true);
    $total_runtime = $end_time - $start_time;
    echo "\n[Finished running form blueprint queue cron job @ " . $end_time . "] \n";
    echo "[Total runtime in seconds: " . $total_runtime . "]\n\n";
}