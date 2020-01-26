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

namespace Entrada\Modules\Olab\Classes;

use Auth;
use \Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Entrada\Modules\Olab\Models\MapNodeTypes;
use Entrada\Modules\Olab\Models\QuestionTypes;
use Entrada\Modules\Olab\Models\Constants;
use Entrada\Modules\Olab\Models\Counters;
use Entrada\Modules\Olab\Models\UserState;
use Entrada\Modules\Olab\Models\Maps;
use Entrada\Modules\Olab\Models\MapNodes;
use Entrada\Modules\Olab\Models\Servers;
use Entrada\Http\Controllers\Controller;
use Entrada\Modules\Olab\Classes\GlobalObjectManager;
use Entrada\Modules\Olab\Classes\Autoload\Events\EventHandler;

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
  public static function onNodeOpen( $oNode, &$oScopedObjects, &$oState ) {

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($oNode->id, oScopedObjects, oState)" );

    try
    {
      // update any onNodeOpen counters for the node
      $aoActions = $oNode->CounterActions()->WithAction("open")->get();
      foreach ($aoActions as $oAction) {

        Log::debug( "processing counter action id = " . $oAction->id );

        // get the counter out of the scoped objects so we can determine 
        // where we need to go to get the value (scoped objects, or user state)
        $aCounter = &$oScopedObjects->getCounter( $oAction->counter_id );
        if ( $aCounter != null ) {

          //if ( ( $aCounter['scopeLevel'] == Maps::IMAGEABLE_TYPE ) ||
          //     ( $aCounter['scopeLevel'] == MapNodes::IMAGEABLE_TYPE ) ) {

          if ( $aCounter['scopeLevel'] == MapNodes::IMAGEABLE_TYPE ) {
            $aCounter = $oState->getCounter( $oAction->counter_id );
          }

          self::executeExpression( $aCounter, $oAction->expression, $oScopedObjects, $oState );
        }
        else
          Log::error( "Count not find counter, id = " . $oAction->counter_id . " to update in scoped objects." );

      }

    }
    catch (Exception $exception)
    {
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception );
    }

  }

  /**
   * Applies an expression to a counter and saves the results to the scoped objects.
   * @param array $aCounter 
   * @param mixed $expression 
   * @param ScopedObjectManager $oScopedObjects 
   * @param UserState $oState 
   */
  public static function executeExpression( &$aCounter, $expression, &$oScopedObjects, &$oState ) {

    // spin up a function tracer.  Handles entry/exit/timing messages
    $counter_id = $aCounter['id'];
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($counter_id, '$expression', oScopedObjects, oState)" );

    try
    {
      // test for no expression, meaning nothign to do
      if ( strlen( $expression ) == 0 ) {
        return;
      }

      // save the original value so we can do change detection later 
      // (if a onChange event is to be fired)
      $aCounter["orgValue"] = $aCounter["value"];

      // test if expression is a number - may have an '+/-' operator
      if ( is_numeric( $expression ) ) {

        $value = ( float )$expression;

        // catch case if an uninitialized counter
        if ( $aCounter["value"] == "" ) {
          $aCounter["value"] = 0;
        }

        $aCounter["value"] += $value;
      }

      // test if expression is a simple assignment
      else if ( $expression[0] == "=" ) {
        $aCounter["value"] = substr( $expression, 1 );
      }

      // fire onCounterChanged event, if value changed
      if ( $aCounter["orgValue"] != $aCounter["value"] ) {

        Log::debug( "counter '" . $aCounter["name"] . ": change detected " . 
                    $aCounter["orgValue"] . " => " . $aCounter["value"] );

        GlobalObjectManager::Get( GlobalObjectManager::EVENT )
            ->FireEvent( EventHandler::ON_COUNTER_CHANGED, array( $oState, $aCounter ) );
      }

      // update the counter in the user state if a map
      // or map node counter, else update in the database
      // if ( ( $aCounter['scopeLevel'] == Maps::IMAGEABLE_TYPE ) ||
      //     ( $aCounter['scopeLevel'] == MapNodes::IMAGEABLE_TYPE ) ) {
      if ( $aCounter['scopeLevel'] == MapNodes::IMAGEABLE_TYPE ) {

        // ensure state gets updated with counter change
        $oState->updateCounter( (int)$aCounter['id'], $aCounter['value']);
        $oState->save();

        Log::debug( "updated map/node counter: '" . $aCounter["name"] . ": " . 
                    $aCounter["orgValue"] . " => ( " . $expression . " ) = " . 
                    $aCounter["value"]);
      }
      else {

        // update the counter in the DB
        $oCounter = Counters::ById($counter_id)->first();

        if ( $oCounter != null ) {

          $oCounter->value = $aCounter["value"];

          // ensure scoped objects gets updated with counter change
          $aLocalCounter = &$oScopedObjects->getCounter( $oCounter->id );
          $aLocalCounter['value'] = $oCounter->value;

          $oCounter->save();

          Log::debug( "updated shared counter: '" . $aCounter["name"] . ": " . 
                      $aCounter["orgValue"] . " => ( " . $expression . " ) = " . 
                      $aCounter["value"]);

        }
      }
    }
    catch (Exception $exception)
    {
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception );
    }

  }

}