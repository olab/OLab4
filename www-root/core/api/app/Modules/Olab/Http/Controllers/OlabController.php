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
 * A generic olab controller
 *
 * @author Organisation: Cumming School of Medicine, University of Calgary
 * @author Developer: Corey Wirun (corey@cardinalcreek.ca)
 * @copyright Copyright 2017 University of Calgary. All Rights Reserved.
 */

namespace App\Modules\Olab\Http\Controllers;

use Auth;
use \Exception;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\Olab\Models\MapNodeTypes;
use App\Modules\Olab\Models\Maps;
use App\Modules\Olab\Models\UserState;
use App\Modules\Olab\Models\Files;
use App\Modules\Olab\Models\SystemSettings;
use App\Modules\Olab\Models\MapNodes;
use App\Modules\Olab\Models\Servers;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\JWTAuth;
use Tymon\JWTAuth\Payload;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\PayloadFactory;
use Tymon\JWTAuth\Facades\JWTFactory;
use Tymon\JWTAuth\Token;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Modules\Olab\Classes\OlabExceptionHandler;
use App\Modules\Olab\Classes\SiteFileHandler;
use App\Modules\Olab\Classes\OlabCodeTracer;
use App\Modules\Olab\Classes\HostSystemApi;

class OlabController extends Controller
{
    const OLAB_STATE_COUNTER_KEY = 'cnt';
    const OLAB_STATE_NODE_KEY = 'nodeId';
    const OLAB_STATE_MAP_KEY = 'mapId';
    const OLAB_STATE_KEY = 'olabstate';

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

    public function __construct( JWTAuth $jwt ) {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        $this->jwt = $jwt;

        try {

            // register applicable middleware (do not put any token code before this)
            //$this->middleware('auth');
            //$this->middleware('jwt.refresh');

            // get the payload so we can get the claims
            //$payload = Auth::parseToken()->getPayload();
            //$this->claims = $payload->getClaims();
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

    private function attachUserState( $maps, $userMapStates ) {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        // for every map, create an empty array or buttons for the map.
        // start by adding a 'play' button to all maps
        foreach( $maps as &$map ) {
            $map['navigation'] = array( 'mapId' => $map['id'], 'nodeId' => 0 );
        }

        // if there are user map states, then add a 'resume' button
        // to the map data.
        if ( sizeof( $userMapStates ) > 0 ) {

            // look through all map states
            foreach ( $userMapStates as $state ) {

                // loop through all maps and add the resume button to that map
                foreach ($maps as &$map ) {

                    // test if current map_id is same as user map state
                    if ( $map['id'] === $state['map_id'] ) {

                        // set nodeId to 'resume' slot in list
                        //$map[3][1] = $state['map_node_id'];
                        $map['navigation']['nodeId'] = $state['map_node_id'];

                        // add user state array to map
                        $map['userState'] = $state;
                    }
                }
            }
        }

        Log::debug("OlabController attachUserState exit");

        return $maps;
    }

    /**
     * Get list of maps
     * @return \Illuminate\Http\JsonResponse
     */
    public function index() {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        $aPayload = array();

        try {

            $records = Maps::active()->get(['id', 'name', 'abstract'] );
            $mapData = array();
            foreach ($records as $record) {
                array_push( $mapData, array( 'id' => $record->id,
                                             'name' => $record->name,
                                             'description' => $record->abstract ) );
            }

            $userState = UserState::byUser( HostSystemApi::GetUserId() )
                ->get()->toArray();

            $mapData = $this->attachUserState( $mapData, $userState );

            $aPayload['data'] = $mapData;


        }
        catch (Exception $exception) {
            return OlabExceptionHandler::RestApiError( $exception );
        }

        return response()->json($aPayload);

    }

    /**
     * Download file from server
     * @param Request $request
     * @param mixed $nFileId
     * @throws Exception
     * @return \Illuminate\Http\JsonResponse
     */
    public function download( Request $request, $nFileId ) {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($nFileId)" );

        try {

            Log::debug("OlabController download entry");

            // get the system file record
            $oSystemFile = Files::At( $nFileId );
            if ( $oSystemFile == null )
                throw new Exception("Cannot find file info for index: " + $nFileId );

            // get physical file path (ensure file exists as well)
            $fullPath = SiteFileHandler::GetFilePath( $oSystemFile );

            if ($fd = fopen ($fullPath, "r")) {

                $fsize = filesize($fullPath);

                header("Content-type: " . $oSystemFile->mime);
                header("Content-Disposition: attachment; filename=" . $oSystemFile->path);
                header("Content-length: " . $fsize);
                header("Cache-control: private");

                while(!feof($fd)) {
                    $buffer = fread($fd, 2048);
                    echo $buffer;
                }
            }

            fclose ($fd);

            Log::debug("OlabController download exit");

        }
        catch (Exception $exception) {
            return OlabExceptionHandler::RestApiError( $exception );
        }
    }

}