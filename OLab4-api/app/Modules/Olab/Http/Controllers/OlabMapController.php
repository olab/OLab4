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

use Entrada\Modules\Olab\Models\MapNodeTypes;
use Entrada\Modules\Olab\Models\QuestionTypes;
use Entrada\Modules\Olab\Models\Constants;
use Entrada\Modules\Olab\Models\CounterActions;
use Entrada\Modules\Olab\Models\UserState;
use Entrada\Modules\Olab\Models\Maps;
use Entrada\Modules\Olab\Models\MapNodes;
use Entrada\Modules\Olab\Models\Servers;
use Entrada\Modules\Olab\Classes\Autoload\AccessControl\AccessControlBase;

use Entrada\Modules\Olab\Classes\Autoload\WikiTags\OlabTagBase;

class OlabMapController extends OlabController
{
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

            // run common controller initialization
            $this->initialize();
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

    public function canOpen( $map_id, $node_id ) {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($map_id)" );

        $aData = array();
        $aData[ 'result' ] = false;
        $aData[ 'mapId' ] = $map_id;
        $aData[ 'nodeId' ] = $node_id;

        try
        {
            $oMap = Maps::active()->Abbreviated()->ById( $map_id )->first();
            if ( $oMap !== null ) {

                // test access control object based on object type
                $oAccessControl = AccessControlBase::classFactory( $oMap );
                $aData[ 'result' ] = $oAccessControl->isExecutable( $oMap->id );
                if ( $aData[ 'result'] === false ) {
                    $aData[ 'message' ] = "Access is denied";
                }
            }
            else {
                $aData[ 'message'] = "Unknown map '" . $map_id . "'";
            }

            return $aData;

        }
        catch (Exception $exception) {
            OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
            return OlabExceptionHandler::restApiError( $exception );
        }

    }

    /**
     * Get metadata info for map
     * @param Request $request
     * @param mixed $map_id
     */
    public function info( Request $request, $map_id ) {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($map_id)" );
        $aData = array();

        try {

            $oMap = Maps::active()->Abbreviated()
                ->with( ScopedObjectManager::RELATION_NAME_QUESTIONS )
                ->with( 'Counters' )
                ->with( 'MapNodes')
                ->with( 'MapNodeLinks')
                ->findOrFail($map_id);

            $aData['id'] = $oMap->id;
            $aData['title'] = $oMap->name;
            $aData['author'] = "Corey Wirun";
            $aData['nodeCount'] = $oMap->MapNodes()->count();
            $aData['linkCount'] = $oMap->MapNodeLinks()->count();
            $aData['counterCount'] = $oMap->Counters()->count();
            $aData['questionCount'] = $oMap->Questions()->count();

            $oUserState = $this->oUserState->Get( $this->user_id, $map_id );

            if ( $oUserState != null ) {

                // get the node from the list so we can display the node name
                foreach ($oMap->MapNodes()->get()->toArray() as $aNode)
                {
                    if ( $aNode['id'] == $oUserState->map_node_id ) {
                        $aData['resumeInfo'] = "Node '" . $aNode['title'] . "' at " . $oUserState->updated_at;
                        break;
                    }
                }

            }

            return $aData;

        }
        catch (Exception $exception) {
            OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
            return OlabExceptionHandler::restApiError( $exception );
        }

    }


}