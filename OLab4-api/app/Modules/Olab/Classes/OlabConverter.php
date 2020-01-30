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
 * A class to expose information from a hosting system
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
use Entrada\Modules\Olab\Models\MapQuestions;
use Entrada\Modules\Olab\Models\MapQuestionResponses;
use Entrada\Modules\Olab\Models\BaseModel;
use Entrada\Modules\Olab\Models\QuestionTypes;
use Entrada\Modules\Olab\Models\Questions;
use Entrada\Modules\Olab\Models\QuestionResponses;
use Entrada\Modules\Olab\Models\Constants;
use Entrada\Modules\Olab\Models\MapNodeQ;
use Entrada\Modules\Olab\Models\Counters;
use Entrada\Modules\Olab\Models\UserState;
use Entrada\Modules\Olab\Models\MapNodeLinks;
use Entrada\Modules\Olab\Models\MapTemplates;
use Entrada\Modules\Olab\Models\MapElements;
use Entrada\Modules\Olab\Models\MapAvatars;
use Entrada\Modules\Olab\Models\MapVpdElements;
use Entrada\Modules\Olab\Models\SystemSettings;
use Entrada\Modules\Olab\Models\Maps;
use Entrada\Modules\Olab\Models\Files;
use Entrada\Modules\Olab\Models\MapNodes;
use Entrada\Modules\Olab\Models\Servers;
use Entrada\Modules\Olab\Classes\Autoload\WikiTags\OlabTagBase;
use Entrada\Modules\Olab\Classes\OlabObjectNotFoundException;

use \Ds\Map;

/**
 * OlabConverter schema converter.
 *
 * @version 1.0
 * @author wirunc
*/
class OlabConverter
{
    private $conn = null;
    private $question_id_map;
    private $nodelink_id_map;
    private $media_resource_id_map;
    private $current_conversion_stack;
    private $avatar_relocate_map;
    private $avatar_relocate_id_map;
    private $file_relocate_map;
    private $import_directory;
    private $import_errors;

    public function __construct() {
        // spin up a function tracer.  Handles entry/exit/timing messages
        $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

        $this->question_id_map  = new Map([]);
        $this->nodelink_id_map  = new Map([]);
        $this->media_resource_id_map  = new Map([]);
        $this->avatar_relocate_id_map  = new Map([]);
        $this->file_relocate_map  = new Map([]);
        $this->avatar_relocate_map  = new Map([]);
        $this->current_conversion_stack = array();
        $this->import_errors = array();
    }

    public function GetConversionStack() {
        return $this->current_conversion_stack;    
    }

    public function HaveImportErrors() {
        return sizeof( $this->import_errors );    
    }

    public function GetImportErrors() {
        return $this->import_errors;    
    }

    private function pushConversionStack( $item ) {
        array_push( $this->current_conversion_stack, $item);
    }

    private function popConversionStack() {
        return array_pop( $this->current_conversion_stack );
    }

    /**
     * Converts a specified map to the latest renderer
     * @param mixed $map_id 
     * @throws Exception 
     * @return {array} Array of progress messages
     */
    public function convert( $map_id ) {

        try
        {
            $oNewMap = null;

            // spin up a function tracer.  Handles entry/exit/timing messages
            $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "(" . $map_id . ")" );        

            // get the full map record 
            $oMap = Maps::At( $map_id );
            if ( $oMap == null ) {
              throw new OlabObjectNotFoundException("Map", $map_id );
            }

            BaseModel::beginTransaction();

            $oNewMap = $this->convertMap( $oMap );

            // convert all the nodes in the source map
            $this->convertMapNodes( $oMap, $oNewMap->id );

            // convert map links to point to/from new map nodes
            $this->convertMapLinks( $oMap, $oNewMap );

            // if made it this far, then we can process the files relocation
            $this->relocateSourceFiles( $oNewMap, SystemSettings::ByKey( SystemSettings::FILEROOT_KEY )->first()->value );

            BaseModel::commit();

            return $oNewMap;

        }
        catch (Exception $exception) {

            BaseModel::rollBack();
            throw $exception;
        }

    }

    /**
     * Imports a specified map from an xml file
     * @param mixed $import_directory Import file directory
     * @throws Exception 
     * @return {array} Array of progress messages
     */
    public function import( $import_directory ) {

        $oMap = null;

        try
        {
            $oNewMap = null;

            // spin up a function tracer.  Handles entry/exit/timing messages
            $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "(" . $import_directory . ")" );        

            $this->import_directory = $import_directory;

            $importer_author_id = HostSystemApi::getUserId();

            // create new map 
            $oMap = Maps::import( $this->import_directory );
            $original_id = $oMap->id;

            // change the ownership to the importing user
            $oMap->author_id = $importer_author_id;

            BaseModel::beginTransaction();

            $this->importMap( $oMap );

            // convert all the nodes in the source map
            $this->importMapNodes( $oMap->id );

            // convert map links to point to/from new map nodes
            $this->importMapLinks( $original_id, $oMap );

            // process the files relocation
            $this->relocateSourceFiles( $oMap, $import_directory . DIRECTORY_SEPARATOR . "media", $this->file_relocate_map );
            $this->relocateSourceFiles( $oMap, $import_directory . DIRECTORY_SEPARATOR . "media", $this->avatar_relocate_map );

            if ( empty( $this->import_errors )) {
              BaseModel::commit();
            }
            else {
              BaseModel::rollBack();              
            }

        }
        catch (Exception $exception) {

            BaseModel::rollBack();
            Log::error( $exception->getMessage() );
            throw $exception;
        }

        return $oMap;

    }

    private function relocateSourceFiles( $oMap, $sourceDirectory, $fileMap ) {
      
      $targetDirectory = HostSystemApi::getFileRoot() . "/images/olab/files";

      // test if source directory even exists
      if ( !file_exists( $sourceDirectory ) ) {
        return;
      }

      if ( realpath( $sourceDirectory) != $sourceDirectory ) {
        throw new Exception("invalid file relocate source base directory '" . $sourceDirectory . "' found." );
      }

      if ( !file_exists( $targetDirectory ) || ( realpath( $targetDirectory) != $targetDirectory ) )
        throw new Exception("invalid file relocate destination base directory '" . $targetDirectory . "' found." );

      $keys = $fileMap->keys();
      foreach ($keys as $key )
      {
        $sourceFile = $sourceDirectory . DIRECTORY_SEPARATOR . $key;
        $destinationFile = $targetDirectory . $fileMap->get( $key );

        // double check the directories in case there's anything out-of-tree
        $dir = dirname( $sourceFile );
        if ( strpos( $dir, ".." ) !== false ) {
          $this->import_errors[] = "Invalid source path found '" . $dir . "' relocating file '" . basename( $sourceFile );
          continue;              
        }
        
        $dir = dirname( $destinationFile );
        if ( strpos( $dir, ".." ) !== false ) {
          $this->import_errors[] = "Invalid destination path found '" . $dir . "' relocating file '" . basename( $destinationFile );
          continue;
        }

        Log::debug("Relocating " . $sourceFile . " -> " . $destinationFile );

        $destination_directory = dirname( $destinationFile );

        // make the target directory
        if ( !file_exists( $destination_directory )) {

          try
          {
            mkdir( $destination_directory, 0775, true );                	
          }
          catch (Exception $exception)
          {
            $this->import_errors[] = "Error creating media directory '" . $destination_directory . "' " . $exception->getMessage();
            continue;
          }               
        }

        // test if source file exists
        if ( !file_exists( $sourceFile )) {

          $import_root = HostSystemApi::getImportRoot();
  	      $source_file = str_replace($import_root, '', $sourceFile);         
          $this->import_errors[] = "Not able to find source file '" . $source_file . " in import archive.";
          continue;
        }

        // copy source file to url-able public directory
        try
        {
          copy( $sourceFile, $destinationFile );
        }
        catch (Exception $exception)
        {
          $this->import_errors[] = "Error copying file '" . $destinationFile . "' " . $exception->getMessage();
          continue;
        }               

      }        
    }

    /**
     * Converts a map
     * @param mixed $oMap Original map
     * @return mixed Converted map
     */
    private function convertMap( $oMap ) {
        
        $this->pushConversionStack( "Map '" . $oMap->name . "'(" . $oMap->id . ")" );

        $oNewMap = $oMap->replicate();
        $oNewMap->renderer_version = 4;
        $oNewMap->name = "[OLAB4] " . $oNewMap->name;
        $oNewMap->save();

        Log::debug("Converted map " . $oMap->id . " -> " . $oNewMap->id );

        $this->convertMapQuestions( $oMap, $oNewMap->id );

        $this->convertMapElements( $oMap, $oNewMap->id );

        $this->createDefaultMapTemplate( $oNewMap );

        return $oNewMap;
    }

    /**
     * Converts a map
     * @param mixed $oNewMap New map
     * @return mixed Converted map
     */
    private function importMap( &$oNewMap ) {
      
      $this->pushConversionStack( "Map '" . $oNewMap->name . "'(" . $oNewMap->id . ")" );

      $original_id = $oNewMap->id;
      $oNewMap->id = null;
      $oNewMap->renderer_version = 4;
      $oNewMap->name = "[OLAB4] " . $oNewMap->name;
      $oNewMap->save();

      Log::debug("Imported map " . $original_id . " -> " . $oNewMap->id );

      $this->importMapQuestions( $oNewMap->id );
      $this->importMapElements( $oNewMap->id );
      $this->importMapAvatars( $oNewMap->id );
      $this->importMapCounters( $oNewMap->id );

      //$this->createDefaultMapTemplate( $oNewMap );

      return $oNewMap;
    }

    /**
     * Converts the modes from the original map
     * @param mixed $oMap Original map
     * @param mixed $nNewMapId New/converted map id
     * @return mixed array of new nodes
     */
    private function convertMapNodes( $oMap, $nNewMapId ) {
        
        $aObjects = $oMap->MapNodes()->get();
        $oNewObject = null;
        $aoNewMapNodes = array();

        // convert each map node in original map
        foreach ($aObjects as $oObject )
        {
            $this->pushConversionStack( "Node '" . $oObject->title . "'(" . $oObject->id . ")" );

            $oNewObject = $oObject->replicate();

            $oNewObject->map_id = $nNewMapId;
            $oNewObject->title = "[OLAB4] " . $oNewObject->title;

            // intermediate save so we can get the new node id 
            $oNewObject->save();               

            // remap all question ids to the new question object
            $oNewObject->text = $this->remapQuestionTags( $oNewObject );

            // create/remap legagy VPD (constants) to new constants
            $oNewObject->text = $this->remapVPDTags( $oNewObject );

            // create/remap legagy MR (media resources) to new MR's
            $oNewObject->text = $this->remapMRTags( $oNewObject );

            // do final save
            $oNewObject->save();               
            
            Log::debug("Converted map node " . $oObject->id . " -> " . $oNewObject->id );

            // cache converted node id since we need them when 
            // converting node links new nodes (new -> old)
            $this->nodelink_id_map->put( $oObject->id, $oNewObject->id );
            Log::debug("nodelink_id_map[ " . $oObject->id . " ] = " . $oNewObject->name  );

            array_push( $aoNewMapNodes, $oNewObject );

            $this->popConversionStack();

        }

        return $aoNewMapNodes;
    }

    /**
     * Imports map nodes
     * @param mixed $new_map_id Parent map id 
     * @return mixed
     */
    private function importMapNodes( $new_map_id ) {
        
        // import questions from file
        $aObjects = MapNodes::import( $this->import_directory );
        Log::debug("Found map nodes for conversion: " . sizeof( $aObjects ) );

        $aoNewMapNodes = array();

        // convert each map node in original map
        foreach ($aObjects as $oObject )
        {
            $this->pushConversionStack( "Node '" . $oObject->title . "'(" . $oObject->id . ")" );

            $original_id = $oObject->id;
            $oObject->map_id = $new_map_id;
            $oObject->id = null;
            $oObject->title = "[OLAB4] " . $oObject->title;

            // intermediate save so we can get the new node id 
            $oObject->save();               

            // remap all question ids to the new question object
            $oObject->text = $this->remapQuestionTags( $oObject );

            // create/remap legagy VPD (constants) to new constants
            $oObject->text = $this->remapVPDTags( $oObject );

            // create/remap legagy MR (media resources) to new MR's
            $oObject->text = $this->remapMRTags( $oObject );

            // create/remap legagy AV (avatars) to new AV's
            $oObject->text = $this->remapAVTags( $oObject );          

            // do final save
            $oObject->save();               
            
            Log::debug("Converted map node " . $original_id . " -> " . $oObject->id );

            // cache converted node id since we need them when 
            // converting node links new nodes (new -> old)
            $this->nodelink_id_map->put( $original_id, $oObject->id );
            Log::debug("nodelink_id_map[ " . $original_id . " ] = " . $oObject->id  );

            array_push( $aoNewMapNodes, $oObject );

            $this->popConversionStack();

        }

        return $aoNewMapNodes;

    }

    /**
     * Imports map avatars
     * @param mixed $new_map_id Parent map id 
     */
    private function importMapAvatars( $new_map_id ) {
        
        // import questions from file
        $aObjects = MapAvatars::import( $this->import_directory );
        Log::debug("Found map avatars for conversion: " . sizeof( $aObjects ) );

        $aoNewObjects = array();

        // convert each map node in original map
        foreach ($aObjects as $oObject )
        {
            $this->pushConversionStack( "Map avatar '" . $oObject->title . "'(" . $oObject->id . ")" );

            $original_id = $oObject->id;
            $oObject->id = null;
            $oObject->map_id = $new_map_id;
            $oObject->image = basename( $oObject->image );

            if ( strlen( $oObject->image ) == 0 )
                throw new Exception("Unable to convert avatar.  Rendered image file not prerendered.");

            $oObject->image = "/1/" . $new_map_id . "/" . $oObject->image;

            $this->avatar_relocate_map->put( basename( $oObject->image ), $oObject->image );
            Log::debug("avatar_relocate_map[ " . basename( $oObject->image ) . " ] = " . $oObject->image  );

            // intermediate save so we can get the new node id 
            $oObject->save();               

            $this->avatar_relocate_id_map->put( $original_id, $oObject->id );
            Log::debug("avatar_relocate_id_map[ " . $original_id . " ] = " . $oObject->id  );

            Log::debug("Converted map avatar " . $original_id . " -> " . $oObject->id );

            array_push( $aoNewObjects, $oObject );

            $this->popConversionStack();

        }

        return $aoNewObjects;
    }

    /**
     * Imports map counters
     * @param mixed $new_map_id Parent map id 
     */
    private function importMapCounters( $new_map_id ) {
        
        // import questions from file
        $aObjects = Counters::import( $this->import_directory );
        Log::debug("Found map counters for conversion: " . sizeof( $aObjects ) );

        $aoNewObjects = array();

        // convert each map node in original map
        foreach ($aObjects as $oObject )
        {
            $this->pushConversionStack( "Map counter '" . $oObject->name . "'(" . $oObject->id . ")" );

            $original_id = $oObject->id;
            $oObject->id = null;
            $oObject->imageable_id = $new_map_id;

            // intermediate save so we can get the new node id 
            $oObject->save();               

            Log::debug("Converted map counter " . $original_id . " -> " . $oObject->id );

            array_push( $aoNewObjects, $oObject );

            $this->popConversionStack();
        }

        return $aoNewObjects;
    }

    /**
     * Convert legacy map links to point to new nodes
     * @param mixed $aoMapNodes 
     */
    private function convertMapLinks( $oMap, $oNewMap ) {

        // get collection of original map links
        $aObjects = MapNodeLinks::ByMap( $oMap->id )->get();

        foreach ($aObjects as $oObject)
        {
            $this->pushConversionStack( "MapNodeLink " . $oObject->id );

            $oNewObject = MapNodeLinks::Create( $oNewMap->id, $oObject );

            $oNewObject->node_id_1 = $this->nodelink_id_map[ $oObject->node_id_1 ];
            $oNewObject->node_id_2 = $this->nodelink_id_map[ $oObject->node_id_2 ];
        	
            $oNewObject->save();

            Log::debug("Converted map node link " . $oObject->node_id_1 . " -> " . $oNewObject->node_id_1 );
            Log::debug("                        " . $oObject->node_id_2 . " -> " . $oNewObject->node_id_2 );

            $this->popConversionStack();
        }       

    }

    /**
     * Import legacy map links to point to new nodes
     * @param mixed $oMap Original map 
     * @param mixed $oNewMap Imported map 
     */
    private function importMapLinks( $original_map_id, $oNewMap ) {

        // get collection of original map links
        $aObjects = MapNodeLinks::import( $this->import_directory );
        Log::debug("Found map nodes links for conversion: " . sizeof( $aObjects ) );

        foreach ($aObjects as $oObject)
        {
            $this->pushConversionStack( "MapNodeLink " . $oObject->id );

            $oObject->map_id = $oNewMap->id;
            $original_id = $oObject->id;
            $oObject->id = null;

            $original_node_1 = $oObject->node_id_1;
            $original_node_2 = $oObject->node_id_2;

            $oObject->node_id_1 = $this->nodelink_id_map[ $oObject->node_id_1 ];
            $oObject->node_id_2 = $this->nodelink_id_map[ $oObject->node_id_2 ];
        	
            $oObject->save();

            Log::debug("Converted map node link " . $original_node_1 . " -> " . $oObject->node_id_1 );
            Log::debug("                        " . $original_node_2 . " -> " . $oObject->node_id_2 );

            $this->popConversionStack();
        }       

    }

    /**
     * Converts map elements
     * @param mixed $oMap Original map object
     * @param mixed $parent_id New parent object id
     * @return mixed
     */
    private function convertMapElements( $oMap, $parent_id ) {
        
        $aObjects = MapElements::ByMap( $oMap->id )->get();

        // convert each map-level question in original map
        foreach ($aObjects as $oObject) {  
            
            $this->convertMapElement( $oObject, $parent_id );

        }

        return $aObjects->count();
    }

    /**
     * Import map elements
     * @param mixed $oMap Original map object
     * @param mixed $parent_id New parent object id
     * @return mixed
     */
    private function importMapElements( $parent_id ) {
        
        $aObjects = MapElements::import( $this->import_directory );
        Log::debug("Found map elements for conversion: " . sizeof( $aObjects ) );

        // convert each map-level question in original map
        foreach ($aObjects as $oObject) {  
            
            $this->importMapElement( $oObject, $parent_id );

        }

        return sizeof( $aObjects );
    }

    /**
     * Convert single map element record
     * @param mixed $oObject Map element
     */
    private function convertMapElement( $oObject, $parent_id ) {
        
        $this->pushConversionStack( "Element '" . $oObject->name . "'(" . $oObject->id . ")" );

        // convert legacy object into new one, and save to database
        $oNewObject = Files::Create( $parent_id, $oObject );

        // add to source/destination file map to run if entire
        // map converts successfully
        $newfilePath = $this->generateNewFilePath( $oNewObject );
        $this->file_relocate_map->put( $oNewObject->path, $newfilePath );
        Log::debug("file_relocate_map[ " . $oNewObject->path . " ] = " . $newfilePath  );

        $oNewObject->path = $this->file_relocate_map[ $oNewObject->path ];
        $oNewObject->save();               

        // update question with name based on just-added id
        $oNewObject->name = "ELEMENT" . $oNewObject->id;
        $oNewObject->save();                        

        Log::debug("Converted map element " . $oObject->id . " -> " 
                                            . $oNewObject->id );

        // cache converted question name since we need them when 
        // converting question WIKI tags to new named question
        $this->media_resource_id_map->put( $oObject->id, $oNewObject->name );
        Log::debug("media_resource_id_map[ " . $oObject->id . " ] = " . $oNewObject->name  );

        $this->popConversionStack();
    }
    
    /**
     * Import single map element record
     * @param mixed $oObject Map element
     */
    private function importMapElement( $oObject, $parent_id ) {
        
        $this->pushConversionStack( "Element '" . $oObject->name . "'(" . $oObject->id . ")" );

        // convert legacy object into new one, and save to database
        $oNewObject = Files::Create( $parent_id, $oObject );

        // strip off any path
        $oNewObject->path = basename( $oNewObject->path );

        // add to source/destination file map to run if entire
        // map converts successfully
        $newfilePath = $this->generateNewFilePath( $oNewObject );
        $this->file_relocate_map->put( $oNewObject->path, $newfilePath );
        Log::debug("file_relocate_map[ " . $oNewObject->path . " ] = " . $newfilePath  );

        $oNewObject->path = $this->file_relocate_map[ $oNewObject->path ];
        $oNewObject->save();               

        // update question with name based on just-added id
        $oNewObject->name = "ELEMENT" . $oNewObject->id;
        $oNewObject->save();                        

        Log::debug("Converted map element " . $oObject->id . " -> " 
                                            . $oNewObject->id . " = " . $oNewObject->path );

        // cache converted question name since we need them when 
        // converting question WIKI tags to new named question
        $this->media_resource_id_map->put( $oObject->id, $oNewObject->name );
        Log::debug("media_resource_id_map[ " . $oObject->id . " ] = " . $oNewObject->name  );

        $this->popConversionStack();
    }

    private function generateNewFilePath( $oElement ) {

        $sourceFileName = basename( $oElement->path );
        $sourceFileName = "/1/" . $oElement->imageable_id . "/" . $sourceFileName;

        Log::debug("Created file name mapping " . $oElement->path . " -> " 
                                                . $sourceFileName );
        return $sourceFileName;
    }

    /**
     * Converts map questions
     * @param mixed $oMap Original map object
     * @param mixed $parent_id New parent object id
     * @return mixed
     */
    private function convertMapQuestions( $oMap, $parent_id ) {
        
        // get lecacy questions
        $aObjects = MapQuestions::ByMap( $oMap->id )->get();

        // convert each map-level question in original map
        foreach ($aObjects as $oObject) {  
            $this->convertMapQuestion( $oObject, $parent_id );
        }

        return $aObjects->count();
    }

    /**
     * Imports map questions
     * @param mixed $oMap Original map object
     * @param mixed $parent_id New parent object id
     * @return mixed
     */
    private function importMapQuestions( $parent_id ) {
        
        // import questions from file
        $aObjects = Questions::import( $this->import_directory, $parent_id );
        Log::debug("Found map questions for conversion: " . sizeof( $aObjects ) );

        // convert each map-level question in original map
        foreach ($aObjects as $oObject) {  
            $this->importMapQuestion( $oObject, $parent_id );
        }

        return $aObjects;
    }

    /**
     * Convert single question record
     * @param mixed $oObject 
     */
    private function convertMapQuestion( $oObject, $parent_id ) {
        
        $this->pushConversionStack( "Question " . $oObject->id );

        // convert legacy object into new one, and save to database
        $oNewObject = Questions::Create( $parent_id, $oObject );
        $oNewObject->save();               

        // update question with name based on just-added id
        $oNewObject->name = "QUESTION" . $oNewObject->id;
        $oNewObject->save();                        

        Log::debug("Converted map question " . $oObject->id . " -> " 
                                             . $oNewObject->id );

        // cache converted question name since we need them when 
        // converting question WIKI tags to new named question
        $this->question_id_map->put( $oObject->id, $oNewObject->name );
        Log::debug("question_id_map[ " . $oObject->id . " ] = " . $oNewObject->name  );

        $this->convertMapQuestionResponses( $oObject, $oNewObject->id );

        $this->popConversionStack();

    }

   /**
     * Import single question record
     * @param mixed $oObject 
     */
    private function importMapQuestion( &$oNewObject, $parent_id ) {
        
        $this->pushConversionStack( "Question " . $oNewObject->id );

        $original_id = $oNewObject->id;
        $oNewObject->id = null;
        $oNewObject->save();               

        // update question with name based on just-added id
        $oNewObject->name = "QUESTION" . $oNewObject->id;
        $oNewObject->save();                        

        Log::debug("Imported map question " . $original_id . " -> " . $oNewObject->id );

        // cache converted question name since we need them when 
        // converting question WIKI tags to new named question
        $this->question_id_map->put( $original_id, $oNewObject->name );
        Log::debug("question_id_map[ " . $original_id . " ] = " . $oNewObject->name  );

        $this->importMapQuestionResponses( $original_id, $oNewObject->id );

        $this->popConversionStack();

    }

    /**
     * @param mixed $oMapQuestion Original map question object
     * @param mixed $parent_id New parent object id
     */
    private function convertMapQuestionResponses( $oMapQuestion, $parent_id ) {
        
        $aObjects = MapQuestionResponses::ByMapQuestion( $oMapQuestion->id )->get();

        // convert each question response in original question
        foreach ($aObjects as $oObject) {  
            
            $this->pushConversionStack( "QuestionResp " . $oObject->id );

            // convert legacy object into new one, and save to database
            $oNewObject = QuestionResponses::Create( $parent_id, $oObject );
            $oNewObject->save();               

            Log::debug("Converted map question response " . $oObject->id . " -> " 
                                                          . $oNewObject->id . " parent id = " . $parent_id);

            $this->popConversionStack();

        }

        return $aObjects->count();
    }

    /**
     * @param mixed $original_id Original map question id
     * @param mixed $parent_id New parent object id
     */
    private function importMapQuestionResponses( $original_question_id, $new_question_id ) {
        
        // import questions from file
        $aObjects = QuestionResponses::import( $this->import_directory, $new_question_id );
        Log::debug("Found map questions responses for conversion: " . sizeof( $aObjects ) );

        // convert each map-level question in original map
        foreach ($aObjects as $oObject) {  

            // skip over response if not part of current question
            if ( $oObject->question_id != $original_question_id ) {
                continue;
            }

            $this->pushConversionStack( "QuestionResp " . $oObject->id );

            $original_id = $oObject->id;
            $oObject->id = null;
            $oObject->question_id = $new_question_id;
            $oObject->save();                        

            Log::debug("Imported question response " . $original_id . " -> " . $oObject->id );
            
            $this->popConversionStack();
        }

        return sizeof( $aObjects );
    }

    private function createDefaultMapTemplate( $oMap ) {
        
        $oNewMapTemplate = MapTemplates::Create( $oMap );
        $oNewMapTemplate->save();

        Log::debug("Created debault map template " . $oNewMapTemplate->id );
    }

   /**
     * Create and convert AV tags to new id's
     * @param mixed $oNode 
     */
    private function remapAVTags( $oNode ) {
        
        // extract all wiki tags
        $asWikiTags = OlabTagBase::ExtractWikiTags( $oNode->text, "AV" );
       
        // loop through all the tags, convert old ID to new name
        foreach ( $asWikiTags as $sWikiTag ) {

            $source_tag = "AV:" . $sWikiTag;

            $this->pushConversionStack( "WikiTag '" . $source_tag . "'" );

            $new_tag = "AV:" . $this->avatar_relocate_id_map[ (int)$sWikiTag ];
            $oNode->text = str_replace( $source_tag, $new_tag, $oNode->text );

            Log::debug("Converted WIKI tag " . $source_tag . " -> " . $new_tag );

            $this->popConversionStack();
        }

        return $oNode->text;
    }

   /**
     * Create and convert MR tags to new file objects
     * @param mixed $oNode 
     */
    private function remapMRTags( $oNode ) {
        
        // extract all wiki tags
        $asWikiTags = OlabTagBase::ExtractWikiTags( $oNode->text, Files::WIKI_TAG_MEDIA_RESOURCE );
       
        // loop through all the tags, convert old ID to new name
        foreach ( $asWikiTags as $sWikiTag ) {

            $source_tag = Files::WIKI_TAG_MEDIA_RESOURCE . ":" . $sWikiTag;

            $this->pushConversionStack( "WikiTag '" . $source_tag . "'" );

            // test if question was related to the source map, otherwise
            // it was orphaned or invalidly used in multiple maps and must be read in 
            // directly and then processed as normal
            if (( $this->media_resource_id_map->count() == 0 ) || ( !$this->media_resource_id_map->hasKey( (int)$sWikiTag ) ) ){

                $oQuestion = MapElements::At( (int)$sWikiTag )->first();
                $this->convertMapElement( $oQuestion, $oNode->map_id );
            }

            $new_tag = Files::WIKI_TAG_MEDIA_RESOURCE . ":" . $this->media_resource_id_map[ (int)$sWikiTag ];
            $oNode->text = str_replace( $source_tag, $new_tag, $oNode->text );

            Log::debug("Converted WIKI tag " . $source_tag . " -> " . $new_tag );

            $this->popConversionStack();
        }

        return $oNode->text;
    }

   /**
     * Create and convert VPD tags to new constant objects
     * @param mixed $oNewMapNode 
     */
    private function remapVPDTags( $oNode )
    {
        // extract all wiki tags
        $asWikiTags = OlabTagBase::ExtractWikiTags( $oNode->text, "VPD" );

        // loop through all the tags, convert old ID to new name
        foreach ( $asWikiTags as $sWikiTag ) {

            $source_tag = "VPD:" . $sWikiTag;

            $this->pushConversionStack( "WikiTag '" . $source_tag . "'" );

            // load legacy VPD object
            $oVPDElement = MapVpdElements::TextAt( ( int )$sWikiTag )->first();

            if ( !$oVPDElement ) {
                Log::debug("Unconvertable VPD tag '" . $source_tag . "'" );
            }
            else {

                // convert legacy object into new one, and save to database
                $oNewNodeConstant = Constants::Create( $oNode->id, $oVPDElement );
                // update question with name based on just-added id
                $oNewNodeConstant->name = "CONSTANT" . $oNode->id;
                $oNewNodeConstant->save();     

                $new_tag = "CONST:" . $oNewNodeConstant->id;
                $oNode->text = str_replace( $source_tag, $new_tag, $oNode->text );

                Log::debug("Converted WIKI tag " . $source_tag . " -> " . $new_tag );
            }

            $this->popConversionStack();

        }

        return $oNode->text;

    }

    /**
     * Parses question wiki tags and remaps them to the new
     * format
     * @param mixed $oNode 
     */
    private function remapQuestionTags( $oNode ) {
        
        // extract all wiki tags
        $asWikiTags = OlabTagBase::ExtractWikiTags( $oNode->text, Questions::WIKI_TAG_QUESTION );
       
        // loop through all the tags, convert old ID to new name
        foreach ( $asWikiTags as $sWikiTag ) {

            $source_tag = Questions::WIKI_TAG_QUESTION . ":" . $sWikiTag;

            $this->pushConversionStack( "WikiTag '" . $source_tag . "'" );

            // test if question was related to the source map, otherwise
            // it was orphaned or invalidly used in multiple maps and must be read in 
            // directly and then processed as normal
            if (( $this->question_id_map->count() == 0 ) || ( !$this->question_id_map->hasKey( (int)$sWikiTag ) ) ){

                $oQuestion = MapQuestions::At( (int)$sWikiTag )->first();
                $this->convertMapQuestion( $oQuestion, $oNode->map_id );
            }

            $new_tag = Questions::WIKI_TAG_QUESTION . ":" . $this->question_id_map[ (int)$sWikiTag ];
            $oNode->text = str_replace( $source_tag, $new_tag, $oNode->text );

            Log::debug("Converted WIKI tag " . $source_tag . " -> " . $new_tag );

            $this->popConversionStack();
        }

        return $oNode->text;

    }
}
