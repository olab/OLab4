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
 * A class to manage external file stores
 *
 * @author Organisation: Cumming School of Medicine, University of Calgary
 * @author Developer: Corey Wirun (corey@cardinalcreek.ca)
 * @copyright Copyright 2017 University of Calgary. All Rights Reserved.
 */

namespace App\Modules\Olab\Classes;

use Illuminate\Support\Facades\Log;
use App\Modules\Olab\Models\SystemSettings;
use App\Modules\Olab\Models\MapNodes;
use App\Modules\Olab\Models\Servers;
use \Exception;

/**
 * Site files handler
 *
 * @version 1.0
 * @author wirunc
 */
class SiteFileHandler
{
    protected static $fileRoot;

    /**
     * Bootstrapping class for setting the file root
     */
    public static function initialize() {
        //self::$fileRoot = SystemSettings::where( 'key', SystemSettings::FILEROOT_KEY )->get()->first()->value;
        self::$fileRoot = ENTRADA_ABSOLUTE . "/core/storage/olab";
    }

    public static function FileRoot() {
        return self::$fileRoot;
    }

    /**
     * Get full file path for file object
     * @param mixed $systemFile (SystemFile object or SystemFile array)
     * @exception Exception throws if file not found
     * @return array
     */
    public static function GetFilePath( $systemFile ) {

        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        if ( is_a( $systemFile, 'SystemFile' ) ) {
            $aSystemFile = $systemFile->toArray();
        }
        else {
            $aSystemFile = $systemFile;
        }

        $path = self::FileRoot();

        $var = array();

        if ( $aSystemFile["imageable_type"] == 'Servers' ) {

            $var["Server"] = $aSystemFile["imageable_id"];
            $var["Map"] = null;
            $var["Node"] = null;

        }
        else if ( $aSystemFile["imageable_type"] == 'Maps' ) {

            $var["Server"] = Servers::LOCAL_SERVER_ID;
            $var["Map"] = $aSystemFile["imageable_id"];
            $var["Node"] = null;

        } else if ( $aSystemFile["imageable_type"] == 'Nodes' ) {

            $node = MapNodes::At( $aSystemFile["imageable_id"] );
            $var["Server"] = Servers::LOCAL_SERVER_ID;
            $var["Map"] = $node->map_id;
            $var["Node"] = $aSystemFile["imageable_id"];
        }

        $path .= "/" . $var["Server"];

        if ( $var["Map"] != null )
            $path .= "/" . $var["Map"];

        if ( $var["Node"] != null )
            $path .= "/" . $var["Node"];

        $path .= "/" . $aSystemFile["path"];

        if ( file_exists( $path ))
            return $path;

        throw new Exception( "File '..." . str_replace( self::FileRoot(), "", $path ) . "' not found" );
    }
}

// bootstraps the class so statics can be set
SiteFileHandler::initialize();