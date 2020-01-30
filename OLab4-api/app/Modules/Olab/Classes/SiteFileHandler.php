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

namespace Entrada\Modules\Olab\Classes;

use Illuminate\Support\Facades\Log;
use Entrada\Modules\Olab\Models\SystemSettings;
use Entrada\Modules\Olab\Classes\OLabUtilities;
use Entrada\Modules\Olab\Models\MapNodes;
use Entrada\Modules\Olab\Models\Maps;
use Entrada\Modules\Olab\Models\Servers;
use Entrada\Modules\Olab\Models\Files;
use \Exception;

/**
 * Helper class to handle physical files associated with 
 * a Files scoped-object
 *
 * @version 1.0
 * @author wirunc
 */
class SiteFileHandler
{
  /**
   * create and store physical file assocaited with
   * a Files object
   * @param Files $oFile 
   * @return string Physical file name (including directory)
   */
  public static function createFile( Files $oFile ) {

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($oFile->path)" );

    $partial_path = self::getFilePath( $oFile );
    $base_path = self::getFileBaseDirectory( $oFile );
    $full_path = OLabUtilities::concat_path( $base_path, $partial_path );

    if ( !file_exists( $full_path )) {
      mkdir($full_path, 0744, true);
    }

    $file_path = OLabUtilities::concat_path( $full_path, $oFile->path );

    Log::debug( "creating new file = " . $file_path );

    $data = base64_decode($oFile->encoded_content);

    file_put_contents( $file_path, $data );
    chmod( $file_path, 744 );

    Log::debug("Created: " . $full_path );

    return OLabUtilities::concat_path( $partial_path, $oFile->path );

  }

  /**
   * Gets the physical file size for a file resource
   * @param Files $oFile 
   * @return int file size (in bytes)
   */
  public static function getFileSize( Files $oFile ) {
  
    $partial_path = self::getFilePath( $oFile );
    $base_path = self::getFileBaseDirectory( $oFile );
    $file_path = OLabUtilities::concat_path( $base_path, $oFile->path );

    $file_size = 0;

    if ( file_exists( $file_path )) {
      $file_size = filesize($file_path);
    }
    
    return $file_size;
  }

  /**
   * Get base directory (public or private stoage) for
   * a Files object
   * @param Files $oFile 
   * @return string
   */
  private static function getFileBaseDirectory( Files $oFile ) {
    
    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($oFile->path)" );

    $base_path = "";

    // get the base path depending on the files type
    switch ( $oFile->type )
    {
      case Files::FILES_TYPE_INLINE:
      case Files::FILES_TYPE_DELAYED_PUBLIC:
        $base_path = Files::GetPublicFileRoot();
        break;
      case Files::FILES_TYPE_DELAYED_PRIVATE:
        $base_path = Files::GetPrivateFileRoot();
        break;
      default:
        break;
    }

    return $base_path;
  }

  /**
   * Deletes a physical file from a File object,
   * if it exists.
   * @param Files $oFile 
   * @return bool true if file deleted
   */
  public static function deleteFile( Files $oFile ) {
    
    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($oFile->path)" );

    $base_path = self::getFileBaseDirectory( $oFile );
    $full_path = OLabUtilities::concat_path( $base_path, $oFile->path );

    if ( file_exists( $full_path ) ) {
      unlink( $full_path );
      Log::debug("Deleted: " . $full_path );
      return true;
    }
    
    return false;
  }

  /**
   * Get file path for file object relative to base 
   * storage location (public/private)
   * @param Files $oFile
   * @return string
   */
  public static function getFilePath( Files $oFile ) {

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

    $aSystemFile = $oFile->toArray();
    $base_path = "";
    
    $var = array();

    if ( $aSystemFile["scopeLevel"] == Servers::IMAGEABLE_TYPE ) {

      $var["Server"] = $aSystemFile["parentId"];
      $var["Map"] = null;
      $var["Node"] = null;

    }
    else if ( $aSystemFile["scopeLevel"] == Maps::IMAGEABLE_TYPE ) {

      $var["Server"] = Servers::DEFAULT_LOCAL_ID;
      $var["Map"] = $aSystemFile["parentId"];
      $var["Node"] = null;

    } else if ( $aSystemFile["scopeLevel"] == MapNodes::IMAGEABLE_TYPE ) {

      $node = MapNodes::At( $aSystemFile["parentId"] );
      $var["Server"] = Servers::DEFAULT_LOCAL_ID;
      $var["Map"] = $node->map_id;
      $var["Node"] = $aSystemFile["parentId"];
    }

    $path = OLabUtilities::concat_path( $base_path, $var["Server"] );

    if ( $var["Map"] != null )
      $path = OLabUtilities::concat_path( $path, $var["Map"] );

    if ( $var["Node"] != null )
      $path = OLabUtilities::concat_path( $path, $var["Node"] );

    return $path;

  }
}
