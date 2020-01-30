<?php

namespace Entrada\Modules\Olab\Http\Controllers;

use Auth;
use \Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use Entrada\Http\Controllers\Controller;
use Entrada\Modules\Olab\Classes\SecurityContext;
use Entrada\Modules\Olab\Classes\OlabCodeTracer;
use Entrada\Modules\Olab\Classes\SiteFileHandler;
use Entrada\Modules\Olab\Classes\OlabExceptionHandler;
use Entrada\Modules\Olab\Classes\HostSystemApi;
use Entrada\Modules\Olab\Classes\Autoload\AccessControl\AccessControlBase;

use Entrada\Modules\Olab\Models\Maps;
use Entrada\Modules\Olab\Models\UserState;
use Entrada\Modules\Olab\Models\Files;

class OlabMainController extends OlabController
{

    const OLAB_STATE_COUNTER_KEY = 'cnt';
    const OLAB_STATE_NODE_KEY = 'nodeId';
    const OLAB_STATE_MAP_KEY = 'mapId';
    const OLAB_STATE_KEY = 'olabstate';

    /**
     * Get list of maps
     * @return \Illuminate\Http\JsonResponse
     */
    public function index() {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        $aPayload = array();

        try {

            // get all active maps
            $records = Maps::active()->get(['id', 'name', 'abstract', 'renderer_version'] );
            $mapData = array();

            // test access control context based on object type to evaluate.
            // in this case, it's a collection of maps.
            $oAccessControl = AccessControlBase::classFactory( $records );

            // loop through maps. if map is listable, add it to list for return
            foreach ($records as $record) {

                // test if have list access to map
                if ( $oAccessControl->isListable( $record->id )) {
                    array_push( $mapData, array( 'id' => $record->id,
                                                 'name' => $record->name,
                                                 'description' => $record->abstract,
                                                 'version' => $record->renderer_version) );
                }
            }

            Log::debug("found " . sizeof( $mapData ) . " maps to add to index list" );


            $userState = UserState::byUser( HostSystemApi::getUserId() )
                                    ->get()->toArray();

            $mapData = $this->attachUserState( $mapData, $userState );

            $aPayload['data'] = $mapData;

            return response()->json($aPayload);

        }
        catch (Exception $exception) {
            OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
            return OlabExceptionHandler::restApiError( $exception );
        }

    }

    public function media( Request $request, $map_id, $node_id, $media_id ) {
        
        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "(" . $map_id . "," . $node_id . ")" );

        try
        {
            session_write_close();

            $path = '/var/www/vhosts/OLab/OLab4/www-root/core/storage/olab/1/1063/End-Sign-K-6498.gif';

            ob_end_clean();
            if (!is_file($path) || connection_status()!=0)
                return(FALSE);

            //to prevent long file from getting cut off from     //max_execution_time

            set_time_limit(0);

            $name=basename($path);

            //filenames in IE containing dots will screw up the
            //filename unless we add this

            if (strstr($_SERVER['HTTP_USER_AGENT'], "MSIE"))
                $name = preg_replace('/\./', '%2e', $name, substr_count($name, '.') - 1);

            //required, or it might try to send the serving     //document instead of the file

            header("Cache-Control: ");
            header("Pragma: ");
            header("Content-Type: image/gif");
            //header("Content-Type: application/octet-stream");
            header("Content-Length: " .(string)(filesize($path)) );
            header('Content-Disposition: attachment; filename="'.$name.'"');
            header("Content-Transfer-Encoding: binary\n");

            if($file = fopen($path, 'rb')){
                while( (!feof($file)) && (connection_status()==0) ){
                    print(fread($file, 1024*8));
                    flush();
                }
                fclose($file);
            }
            return((connection_status()==0) and !connection_aborted()); 
            
        }
        catch (Exception $exception) {
            OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
            return OlabExceptionHandler::restApiError( $exception );
        }
    }

    /**
     * Download file from server
     * @param Request $request
     * @param mixed $file_id
     * @throws Exception
     */
    public function download( $file_id ) {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($file_id)" );

        try {

            // get the system file record
            $oSystemFile = Files::At( $file_id );
            if ( $oSystemFile == null )
                throw new Exception("Cannot find file info for index: " + $file_id );

            // get physical file path (ensure file exists as well)
            $full_path = SiteFileHandler::getFilePath( $oSystemFile );

            if ($fd = fopen ($full_path, "r")) {

                $fsize = filesize($full_path);

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
        }
        catch (Exception $exception) {
            OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );
            return OlabExceptionHandler::restApiError( $exception );
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

        return $maps;
    }
}
