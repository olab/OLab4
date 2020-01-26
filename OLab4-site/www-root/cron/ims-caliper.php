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
 * Mines the statistics table for interesting information to send to the IMS caliper.
 *
 * @author Organisation: Queen's University
 * @author Unit: Health Sciences Education Technology Unit
 * @author Developer: Frederic Turmel <ft11@queensu.ca>
 * @copyright Copyright 2016 Queen's University. All Rights Reserved.
 *
 */
set_time_limit(0);

set_include_path(implode(PATH_SEPARATOR, array(
    dirname(__FILE__) . "/../",
    dirname(__FILE__) . "/../core",
    dirname(__FILE__) . "/../core/includes",
    dirname(__FILE__) . "/../core/library",
    dirname(__FILE__) . "/../core/library/vendor",
    get_include_path(),
)));

/**
 * Include the Entrada init code and caliper libraries.
 */
require_once("init.inc.php");
require_once "Caliper/Sensor.php";
require_once "Caliper/Options.php";

$caliper_endpoint = Entrada_Settings::read("caliper_endpoint");
$caliper_sensor_id = Entrada_Settings::read("caliper_sensor_id");
$caliper_api_key = Entrada_Settings::read("caliper_api_key");
$caliper_debug = (Entrada_Settings::read("caliper_debug") == 1) ? true : false;

if ($caliper_endpoint) {
    $statistics = new Models_Statistic();

    /*
     * Learner Statistics
     */
    $raw_stats = $statistics->getLearnerStats();

    $sensor = new Sensor('id');
    $options = new Options();
    $options->setApiKey($caliper_api_key);
    $options->setDebug($caliper_debug);
    $options->setJsonEncodeOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    $options->setHost($caliper_endpoint);

    $sensor->registerClient('http', new Client('clientId', $options));

    if ($raw_stats) {
        foreach ($raw_stats as $stat) {
            print_r($stat);
            $caliper = new Models_IMS_Caliper($stat);

            if ($event = $caliper->getEvent()) {
                $sensor->send($sensor, $event);
                $sensor->describe($sensor, $caliper->getEntity());
            }

            unset($caliper);
        }
    }
}

