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
 * A controller for map functionality
 *
 * @author Organisation: Cumming School of Medicine, University of Calgary
 * @author Developer: Corey Wirun (corey@cardinalcreek.ca)
 * @copyright Copyright 2017 University of Calgary. All Rights Reserved.
 */

namespace Entrada\Modules\Olab\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Entrada\Http\Controllers\Controller;
use \Exception;
use Tymon\JWTAuth\JWTAuth;

use Entrada\Modules\Olab\Classes\ScopedObjectManager;
use Entrada\Modules\Olab\Classes\UserStateHandler;
use Entrada\Modules\Olab\Classes\OlabExceptionHandler;
use Entrada\Modules\Olab\Classes\CounterManager;
use Entrada\Modules\Olab\Classes\OlabCodeTracer;
use Entrada\Modules\Olab\Classes\HostSystemApi;
use Entrada\Modules\Olab\Classes\PostDataHandler;
use Entrada\Modules\Olab\Classes\OLabUtilities;

use Entrada\Modules\Olab\Models\MapNodeTypes;
use Entrada\Modules\Olab\Models\QuestionTypes;
use Entrada\Modules\Olab\Models\Constants;
use Entrada\Modules\Olab\Models\Counters;
use Entrada\Modules\Olab\Models\CounterActions;
use Entrada\Modules\Olab\Models\UserState;
use Entrada\Modules\Olab\Models\Maps;
use Entrada\Modules\Olab\Models\MapNodes;
use Entrada\Modules\Olab\Models\Servers;
use Entrada\Modules\Olab\Classes\Autoload\AccessControl\AccessControlBase;

use Entrada\Modules\Olab\Classes\OlabAccessDeniedException;
use Entrada\Modules\Olab\Classes\OlabObjectNotFoundException;

use Entrada\Modules\Olab\Classes\Autoload\WikiTags\OlabTagBase;

class OlabCounterController extends OlabController
{
    public function editValue( Request $request, int $id ) {
        
        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($id)" );

        $payload = array();

        try {

            // run common controller initialization
            $this->initialize( $request );

            $oObj = $this->get_counter( $id );

            $oPostData = new PostDataHandler( $request );

            $value = null;
            $oPostData->get_text( $value, 'value');

            Log::debug( "edit counter value = '$oObj->name' = '$oObj->value' -> '$value' " );

            if ( is_numeric( $oObj->value )) {

                // test if expression against current counter value
                if ( strpos( $value, "+" ) === 0 ) {
                    $oObj->value = $oObj->value + intval( $value );
                }
                else if ( strpos( $value, "-" ) === 0 ) {
                    $oObj->value = $oObj->value - intval( $value );
                }
                else {
                    $oObj->value = $value;
                }
                
            }
            else {
                $oObj->value = $value;                
            }

            $this->write_object( $oObj );    

            $payload = OLabUtilities::make_api_return( null, $tracer, $oObj );

        }
        catch (Exception $exception) {
            OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
            $payload = OLabUtilities::make_api_return( $exception, $tracer );
        }

        return response()->json($payload);
    }

}