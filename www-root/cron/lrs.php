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
 * Mines the statistics table for interesting information to send to the LRS.
 *
 * @author Organisation: Queen's University
 * @author Unit: Health Sciences Education Technology Unit
 * @author Developer: Matt Simpson <simpson@queensu.ca>
 * @copyright Copyright 2015 Queen's University. All Rights Reserved.
 *
 */

set_time_limit(0);

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

$lrs_endpoint = Entrada_Settings::read("lrs_endpoint");

if ($lrs_endpoint) {
    $lrs_version = Entrada_Settings::read("lrs_version");
    $lrs_username = Entrada_Settings::read("lrs_username");
    $lrs_password = Entrada_Settings::read("lrs_password");

    $lrs = new TinCan\RemoteLRS($lrs_endpoint, $lrs_version, $lrs_username, $lrs_password);

    $history = new Models_Lrs_History();
    $run_last = $history->runLast("cron");

    $statistics = new Models_Statistic();

    /*
     * Learner Statistics
     */
    $raw_stats = $statistics->getLearnerStats($run_last);

    if ($raw_stats) {
        foreach ($raw_stats as $stat) {
            $date = new DateTime();
            $date->setTimestamp($stat["timestamp"]);

//          echo "[New] " . $stat["proxy_id"] . " at " . $date->format("c") . "\n";
//          flush();

            $response = $lrs->saveStatement(
                array(
                    "actor" => array(
                        "name" => $stat["firstname"] . " " . $stat["lastname"],
                        "mbox" => $stat["email"],
                    ),
                    "timestamp" => $date->format("c"),
                    "verb" => array(
                        "id" => ENTRADA_URL . "/xapi/verbs/" . $stat["action"],
                    ),
                    "object" => array(
                        "id" => ENTRADA_URL . "/" . $stat["module"] . "/" . $stat["action_field"] . "/" . $stat["action_value"],
                    )
                )
            );
        }
    }

    $history->fromArray(array("type" => "cron", "run_last" => time()));
    $history->insert();
}