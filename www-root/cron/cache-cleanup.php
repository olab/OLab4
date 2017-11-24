<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Cron job responsible for cleaning up the cache directory after ADODB.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <simpson@queensu.ca>
 * @copyright Copyright 2012 Queen's University. All Rights Reserved.
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

if (defined("CACHE_DIRECTORY") && CACHE_DIRECTORY && is_dir(CACHE_DIRECTORY) && is_writable(CACHE_DIRECTORY)) {
	$command = "find ".CACHE_DIRECTORY." -mtime +7 | grep '\.cache' | xargs rm -f";
	exec($command);
	
	application_log("notice", "Scrubbed the Entrada cache directory by running: [".$command."].");
} else {
	application_log("error", "Unable to cleanup the Entrada cache directory. Please check the CACHE_DIRECTORY constant in settings.inc.php.");
}