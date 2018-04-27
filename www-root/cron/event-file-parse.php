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
 * Looks for event files which did not have their contents parsed for search
 * and indexes the content.
 *
 * @author Organisation: Queen's University
 * @author Unit: Health Sciences Education Technology Unit
 * @author Developer: Eric Howarth <eric.howarth@queensu.ca>
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

set_time_limit(0);
ini_set("memory_limit", "500M");

set_include_path(implode(PATH_SEPARATOR, array(
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

/**
 * Read command line options.
 */
$options = getopt("h", ["time_limit::", "help::"]);

if (isset($options["help"]) || isset($options["h"])) {
    echo "\nUsage: php event-file-parse.php options";
    echo "\nParse event files for searchable content";
    echo "\n\n Options:";
    echo "\n--time_limit    Time limit in seconds for the script to run. Default 300 seconds (5 minutes)";
    echo "\n--help          Print this help message";
    echo "\n\n";
    exit;
}

$max_time = 300;
if (isset($options["time_limit"])) {
    $max_time = (int) $options["time_limit"];

    if ($max_time <= 0) {
        echo "\nError: you must supply a time limit in seconds with the option --time_limit=seconds\n";
        exit;
    }
}

$start_time = time();

if (!defined("SEARCH_FILE_CONTENTS") || !SEARCH_FILE_CONTENTS) {
    echo "\nSearching file contents is disabled in settings.inc.php. Nothing to do\n";
} else {

    $event_file_ids = Models_Event_Resource_File::getIdsNotParsed();

    $processed_count = 0;
    if ($event_file_ids) {
        application_log("cron", "Begin Event File Parsing");
        foreach ($event_file_ids as $event_file_id) {
            $event_file = Models_Event_Resource_File::fetchRowByID($event_file_id);

            $storage_filename = FILE_STORAGE_PATH . DIRECTORY_SEPARATOR . $event_file->getID();

            if (@file_exists($storage_filename)) {
                $pathinfo = pathinfo($event_file->getFileName());
                $contents = Entrada_FileToText::decode($storage_filename, $pathinfo["extension"]);
                if ($contents) {
                    $event_file->setFileContents($contents);
                } else {
                    $event_file->setFileContents("NA");
                }
            } else {
                /**
                 * the file does not exist. Set the contents to "NA", so we don't process again
                 */
                application_log("cron", "Event File " . $storage_filename . " could not be found. Cannot index this file");
                $event_file->setFileContents("NA");
            }
            if ($event_file->update() === false) {
                application_log("cron", "ERROR: could not update the record for Event File " . $storage_filename . " check the error log for a database error message");
            }
            $processed_count++;

            if ((time() - $start_time) > $max_time) {
                break;
            }
        }
        application_log("cron", "End Event File Parsing. " . $processed_count . " files parsed");
    }
}