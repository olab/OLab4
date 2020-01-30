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
 * This class tests the functions in Models_Organisation.
 *
 * @author Organisation: UT Southwestern
 * @author Developer: Pei-Te Yu 
 * @copyright Copyright 2017 Queen's University. All Rights Reserved.
 *
 */

require_once(dirname(__FILE__) . "/../../BaseTestCase.php");
use Carbon\Carbon;

// Since everyone's Time Zone rules are different, and UT Southwestern is based in America/Chicago,
// We're doing all unit tests based on this timezone.

/* Business rules for Event Durations, per AAMC:
 * 
 * 1. It must be ISO 8601 Duration format.
 * 2. It must ONLY use number of WEEKDAYS.  AAMC will use this divided by 5 to determine number
 *    of weeks elasped for the sequence in question.
 */
class SequenceDurationTest extends BaseTestCase
{
    public function testWeekdaysDiff1Day()
    {
        $model = new Models_Reports_Aamc(1);
        $startDate = Carbon::createFromFormat('Y-m-d H:i:s', '2017-06-12 12:00:00', 'America/Chicago');
        $endDate = Carbon::createFromFormat('Y-m-d H:i:s', '2017-06-13 12:00:00', 'America/Chicago');
        
        $this->assertEquals(
                "P1D",
                $model->sequenceDuration($startDate, $endDate)
                );
    }

    public function testWeekdaysDiff4Days()
    {
        $model = new Models_Reports_Aamc(1);
        $startDate = Carbon::createFromFormat('Y-m-d H:i:s', '2017-06-12 12:00:00', 'America/Chicago');
        $endDate = Carbon::createFromFormat('Y-m-d H:i:s', '2017-06-16 12:00:00', 'America/Chicago');
    
        $this->assertEquals(
                "P4D",
                $model->sequenceDuration($startDate, $endDate)
                );
    }
    
    public function testWeekdaysDiffWithWeekends()
    {
        $model = new Models_Reports_Aamc(1);
        $startDate = Carbon::createFromFormat('Y-m-d H:i:s', '2017-06-12 12:00:00', 'America/Chicago');
        $endDate = Carbon::createFromFormat('Y-m-d H:i:s', '2017-06-17 12:00:00', 'America/Chicago');
    
        $this->assertEquals(
                "P5D",
                $model->sequenceDuration($startDate, $endDate)
                );
    }

}
if (!defined('PHPUnit_MAIN_METHOD')) {
    EventDurationTest::main();
}
