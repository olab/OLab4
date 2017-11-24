<?php
/**
 * OpenLabyrinth [ http://www.openlabyrinth.ca ]
 *
 * OpenLabyrinth is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OpenLabyrinth is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OpenLabyrinth.  If not, see <http://www.gnu.org/licenses/>.
 *
 * A class to manage system counters.
 *
 * @author Organisation: Cumming School of Medicine, University of Calgary
 * @author Developer: Corey Wirun (corey@cardinalcreek.ca)
 * @copyright Copyright 2017 University of Calgary. All Rights Reserved.
 */

namespace App\Modules\Olab\Classes;

use Auth;
use \Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Modules\Olab\Models\MapNodeTypes;
use App\Modules\Olab\Models\QuestionTypes;
use App\Modules\Olab\Models\Constants;
use App\Modules\Olab\Models\Counters;
use App\Modules\Olab\Models\UserState;
use App\Modules\Olab\Models\Maps;
use App\Modules\Olab\Models\MapNodes;
use App\Modules\Olab\Models\Servers;
use App\Http\Controllers\Controller;

/**
* CounterManager counter manager.
*
* CounterManager description.
*
* @version 1.0
* @author wirunc
*/
class CounterManager
{
    public function __construct() {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );
    }

    /**
     * On node opened handler
     */
    public static function onNodeOpen( $oNode, &$oUserState ) {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        try
        {
            $oScopedObjects = new ScopedObjectManager();
            $oScopedObjects->loadFromUserState( $oUserState );

            // update any node open counters for the node
            $aoActions = $oNode->CounterActions()->WithAction("open")->get();
            foreach ($aoActions as $oAction) {
                self::executeExpression( $oAction->counter_id, $oAction->expression, $oScopedObjects );
            }

            // update the server-level node counter (+=1) and persist to database
            $oNodeCounter = Counters::AtByName( Counters::SYSTEM_NODE_COUNTER_NAME );
            if ( $oNodeCounter != null ) {

                if ( $oNodeCounter->value != null ) {
                    $oNodeCounter->value = $oNodeCounter->value + 1;
                }
                else {
                    $oNodeCounter->value = 1;
                }
                $oNodeCounter->save();

                // update the node counters in the user state
                self::executeExpression( $oNodeCounter->id, "+1", $oScopedObjects );

            }

            $oUserState->state_data = json_encode( $oScopedObjects->getCounter( null, false ) );

        }
        catch (Exception $exception)
        {
            OlabExceptionHandler::LogException( $tracer->sBlockName, $exception );
        }

    }

    public static function executeExpression( $counter_id, $expression, $oScopedObjects ) {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($counter_id, $expression, oScopedObjects)" );

        try
        {
            $aCounter = &$oScopedObjects->getCounter( $counter_id );

            $orgValue = $aCounter["value"];

            if ( $aCounter != null ) {

                // test if expression is a number
                if ( is_numeric( $expression ) ) {
                    $value = ( float )$expression;
                    $aCounter["value"] += $value;
                }

                // test if expression is a simple assignment
                else if ( $expression[0] == "=" ) {
                    $aCounter["value"] = substr( $expression, 1 );
                }

                Log::debug( $aCounter["name"] . ": " . $orgValue . " + ( " . $expression . " ) = " . $aCounter["value"]);

            }

        }
        catch (Exception $exception)
        {
            OlabExceptionHandler::LogException( $tracer->sBlockName, $exception );
        }

    }

}