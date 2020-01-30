<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Script to add a reviewer to all distributions with flagged reviewers for a course.
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Joshua Belanger <jb301@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
@set_time_limit(0);
@set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../../www-root/core",
    dirname(__FILE__) . "/../../www-root/core/includes",
    dirname(__FILE__) . "/../../www-root/core/library",
    dirname(__FILE__) . "/../../www-root/core/library/vendor",
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
    echo "\nUsage: add-reviewer-to-distributions-by-course.php [options] [course id] [proxy id]";
    echo "\n   --usage       Brings up this help screen.";
    echo "\n   --execute     Ensures the specified proxy id is a reviewer for all distributions with custom reviewers.";
    echo "\n\n";
    exit();
}

/**
 * Main point of execution
 *
 * @param $argc
 * @param $argv
 */
function run($argc, &$argv) {
    $action = "--usage";
    $course_id = $proxy_id = 0;
    if ($argc > 1 && !empty($argv)) {
        $action = @$argv[1];
        $course_id = @$argv[2];
        $proxy_id = @$argv[3];
    }

    switch ($action) {
        case "--execute":
            if (!$course_id || !$proxy_id) {
                show_this_usage();
            }

            $distributions = Models_Assessments_Distribution::fetchAllByCourseID($course_id);
            if ($distributions) {
                foreach ($distributions as $distribution) {
                    echo "\n\nProcessing distribution {$distribution->getID()}.";
                    if ($distribution->getFlaggingNotifications() == "reviewers") {
                        $reviewers = Models_Assessments_Distribution_Reviewer::fetchAllByDistributionID($distribution->getID());
                        $found = false;
                        if ($reviewers) {
                            foreach ($reviewers as $reviewer) {
                                if ($reviewer->getProxyID() == $proxy_id) {
                                    $found = true;
                                }
                            }
                        }
                        if (!$found) {
                            global $db;
                            $new_reviewer = new Models_Assessments_Distribution_Reviewer(array(
                                "adistribution_id"  => $distribution->getID(),
                                "proxy_id"          => $proxy_id,
                                "created_date"      => time(),
                                "created_by"        => 1
                            ));
                            if ($new_reviewer->insert()) {
                                echo "\nSuccessfully added new reviewer with proxy_id of {$proxy_id}.";
                            } else {
                                echo "\nUnable to insert new reviewer, DB said: {$db->ErrorMsg()}.";
                            }
                        } else {
                            echo "\nReviewer with proxy_id {$proxy_id} already exists, skipping.";
                        }
                    } else {
                        echo "\nFlagging notifications set to {$distribution->getFlaggingNotifications()}, skipping.";
                    }
                }
            } else {
                echo "\n\nNo distributions found for the given course.";

            }

            echo "\n";
            exit;
            break;
        case "--help":
        case "--usage":
        default :
            show_this_usage();
            break;
    }

}

// Execute
run($argc, $argv);