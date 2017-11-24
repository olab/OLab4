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

namespace App\Modules\Olab\Http\Controllers;

use Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use Tymon\JWTAuth\Payload;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\PayloadFactory;
use Tymon\JWTAuth\Facades\JWTFactory;
use Tymon\JWTAuth\Token;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Modules\Olab\Models\MapNodeTypes;
use App\Modules\Olab\Models\QuestionTypes;
use App\Modules\Olab\Models\Constants;
use App\Modules\Olab\Models\CounterActions;
use App\Modules\Olab\Models\UserState;
use App\Modules\Olab\Models\Maps;
use App\Modules\Olab\Models\MapNodes;
use App\Modules\Olab\Models\Servers;
use App\Http\Controllers\Controller;
use App\Modules\Olab\Classes\ScopedObjectManager;
use App\Modules\Olab\Classes\UserStateHandler;
use App\Modules\Olab\Classes\OlabExceptionHandler;
use App\Modules\Olab\Classes\CounterManager;
use App\Modules\Olab\Classes\OlabCodeTracer;
use App\Modules\Olab\Classes\HostSystemApi;
use App\Modules\Olab\Classes\Autoload\WikiTags\OlabTagBase;

class OlabMapController extends Controller
{
    //const OLAB_STATE_COUNTER_KEY = 'cnt';
    //const OLAB_STATE_NODE_KEY = 'nodeId';
    //const OLAB_STATE_MAP_KEY = 'mapId';
    //const OLAB_STATE_KEY = 'olabstate';

    /**
     * @var \Tymon\JWTAuth\JWTAuth
     */
    protected $jwt;

    /**
     * Current token claims data
     * @var mixed
     */
    protected $claims;

    /**
     * Current token OLab state data
     * @var mixed
     */
    protected $oLabStateData;

    /**
     * Map state data
     * @var mixed
     */
    protected $state_data;
    protected $session_id;
    protected $oSystemObjects;
    protected $oUserState;

    protected $nodeData;

    /**
     * Standard constructor
     * @param JWTAuth $jwt
     */
    public function __construct(JWTAuth $jwt) {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        $this->jwt = $jwt;

        try {
            // create user state handler class
            $this->oUserState = new UserStateHandler();

        }
        catch (TokenExpiredException $exception) {

            Log::error($exception->getMessage());
            throw $exception;
        }
        catch (TokenInvalidException $exception) {

            Log::error($exception->getMessage());
            throw $exception;
        }
        catch (Exception $exception) {

            Log::error($exception->getMessage());
            throw $exception;
        }

    }

    private function buildQuestionArray( $inputArray ) {
        return $inputArray;
    }

    /**
     * Get metadata info for map
     * @param Request $request
     * @param mixed $nMapId
     */
    public function info( Request $request, $nMapId ) {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($nMapId)" );
        $aData = array();

        try {
            global $ENTRADA_USER;

            $iUserId = $ENTRADA_USER->getID();

            $oMap = Maps::active()->Abbreviated()
                ->with( 'Questions' )
                ->with( 'Counters' )
                ->with( 'MapNodes')
                ->with( 'MapNodeLinks')
                ->findOrFail($nMapId);

            $aData['id'] = $oMap->id;
            $aData['title'] = $oMap->name;
            $aData['author'] = "Corey Wirun";
            $aData['nodeCount'] = $oMap->MapNodes()->count();
            $aData['linkCount'] = $oMap->MapNodeLinks()->count();
            $aData['counterCount'] = $oMap->Counters()->count();
            $aData['questionCount'] = $oMap->Questions()->count();

            $oUserState = $this->oUserState->Get( $iUserId, $nMapId );

            if ( $oUserState != null ) {

                // get the node from the list so we can display the node name
                foreach ($oMap->MapNodes()->get()->toArray() as $aNode)
                {
                    if ( $aNode['id'] == $oUserState->map_node_id ) {
                        $aData['resumeInfo'] = "Node '" . $aNode['title'] . "' at " . $oUserState->created_at;
                        break;
                    }
                }

            }

        }
        catch (Exception $exception) {
            return OlabExceptionHandler::RestApiError( $exception );
        }

        return $aData;

    }


}