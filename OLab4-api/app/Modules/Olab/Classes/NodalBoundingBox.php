<?php

namespace Entrada\Modules\Olab\Classes;

use \Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTAuth;
use Entrada\Http\Controllers\Controller;
use Entrada\Modules\Olab\Classes\OlabCodeTracer;
use Entrada\Modules\Olab\Classes\OlabConstants;
use Entrada\Modules\Olab\Classes\UserStateHandler;
use Entrada\Modules\Olab\Classes\ScopedObjectManager;
use Entrada\Modules\Olab\Classes\Autoload\AccessControl\AccessControlBase;
use Entrada\Modules\Olab\Classes\Autoload\Events\EventHandler;
use Entrada\Modules\Olab\Classes\OlabExceptionHandler;
use Entrada\Modules\Olab\Classes\Autoload\WikiTags\OlabTagBase;
use Entrada\Modules\Olab\Classes\HostSystemApi;
use Entrada\Modules\Olab\Classes\CounterManager;
use Entrada\Modules\Olab\Classes\OLabUtilities;
use Entrada\Modules\Olab\Classes\SecurityContext;

use \H5PCore;
use Entrada\Modules\Olab\Classes\h5p\H5PPlugin;
use Entrada\Modules\Olab;
use Entrada\Modules\Olab\Models\UserState;
use Entrada\Modules\Olab\Models\Map;
use Entrada\Modules\Olab\Models\Node;
use Entrada\Modules\Olab\Models\NodeCounter;
use Entrada\Modules\Olab\Models\Servers;
use Entrada\Modules\Olab\Models\Maps;
use Entrada\Modules\Olab\Models\MapTemplates;
use Entrada\Modules\Olab\Models\MapNodeLinks;
use Entrada\Modules\Olab\Models\H5pResults;
use Entrada\Modules\Olab\Models\MapNodes;
use Entrada\Modules\Olab\Classes\GlobalObjectManager;
use Entrada\Modules\Olab\Classes\PostDataHandler;

/**
 * NodalBoundingBox short summary.
 *
 * NodalBoundingBox description.
 *
 * @version 1.0
 * @author wirunc
 */
class NodalBoundingBox
{
  private $oSource;
  private $originalBox;
  public $transformedBox;
  public $transformVector;

  private $minX = PHP_INT_MAX;
  private $maxX = PHP_INT_MIN;
  private $minY = PHP_INT_MAX;
  private $maxY = PHP_INT_MIN;

  public function __construct( $oSource ) {

    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($oSource->id)" );
    $this->calculate_bounding_box( $oSource );
    $this->calculate_to_origin();

    Log::debug( "NodalBoundingBox = " . $this->__toString() );

  }

  private function clear() {

    $this->minX = PHP_INT_MAX;
    $this->maxX = PHP_INT_MIN;
    $this->minY = PHP_INT_MAX;
    $this->maxY = PHP_INT_MIN;

    $this->originalBox = array(); 
    $this->originalBox['upperLeft'] = array();
    $this->originalBox['upperLeft']['x'] = null;
    $this->originalBox['upperLeft']['y'] = null;

    $this->originalBox['lowerRight'] = array();
    $this->originalBox['lowerRight']['x'] = null;
    $this->originalBox['lowerRight']['y'] = null;

    $this->transformedBox = array(); 
    $this->transformedBox['upperLeft'] = array();
    $this->transformedBox['upperLeft']['x'] = null;
    $this->transformedBox['upperLeft']['y'] = null;

    $this->transformedBox['lowerRight'] = array();
    $this->transformedBox['lowerRight']['x'] = null;
    $this->transformedBox['lowerRight']['y'] = null;
  }

  private function is_clear() {

    return $this->minX == PHP_INT_MAX;
  }

  public function __toString() {
    
    $oStr = "org box: (" . $this->originalBox['upperLeft']['x']  . ", " . $this->originalBox['upperLeft']['y']  . ") -> " .      
           "(" . $this->originalBox['lowerRight']['x'] . ", " . $this->originalBox['lowerRight']['y'] . ").  ";

    $oStr .= "origin box: (" . $this->transformedBox['upperLeft']['x']  . ", " . $this->transformedBox['upperLeft']['y']  . ") -> " .      
             "(" . $this->transformedBox['lowerRight']['x'] . ", " . $this->transformedBox['lowerRight']['y'] . ")";

    $oStr .= "trans vector: (" . $this->transformVector['x']  . ", " . $this->transformVector['y']  . ")";

    return $oStr;
  }

  private function calculate_to_origin() {
    
    $this->transformVector['x'] = 0 - $this->originalBox['upperLeft']['x'];
    $this->transformVector['y'] = 0 - $this->originalBox['upperLeft']['y'];
  }

  private function calculate_bounding_box( $oSource ) {
    
    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "()" );

    try {

      $this->clear();
      $oNodes = $oSource->MapNodes()->get();

      foreach ($oNodes as $oNode ) {

        // if bounding box not set, initial it with first node
        if ( $this->is_clear() ) {

          $this->minX = $this->maxX = $oNode->x;
          $this->minY = $this->maxY = $oNode->y;

        }
        else {

          $this->minX = min( $this->minX, $oNode->x );
          $this->maxX = max( $this->maxX, $oNode->x );

          $this->minY = min( $this->minY, $oNode->y );
          $this->maxY = max( $this->maxY, $oNode->y );

        }

      }

      // if box is one dimensional on either axis, then make 
      // it wider/taller so it becomes a box
      if ( $this->minX == $this->maxX ) {
        $this->minX--;
        $this->maxX++;
      }

      if ( $this->minY == $this->maxY ) {
        $this->minY--;
        $this->maxY++;
      }

      $this->originalBox['upperLeft']['x'] = $this->minX;
      $this->originalBox['upperLeft']['y'] = $this->maxY;

      $this->originalBox['lowerRight']['x'] = $this->maxX;
      $this->originalBox['lowerRight']['y'] = $this->minY;

      $this->transformedBox = $this->originalBox;

      $this->transformedBox['upperLeft']['x'] = $this->transformedBox['upperLeft']['x'] - $this->originalBox['upperLeft']['x'];
      $this->transformedBox['upperLeft']['y'] = $this->transformedBox['upperLeft']['y'] - $this->originalBox['upperLeft']['y'];

      $this->transformedBox['lowerRight']['x'] = $this->transformedBox['lowerRight']['x'] - $this->originalBox['upperLeft']['x'];
      $this->transformedBox['lowerRight']['y'] = $this->transformedBox['lowerRight']['y'] - $this->originalBox['upperLeft']['y'];

    }
    catch (Exception $exception) {
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, true );
    }

    return $this->originalBox;

  }

  public function transform( MapNodes &$oNode, $vector ) {

    $aTmp['x'] = $oNode->x;
    $aTmp['y'] = $oNode->y;

    $oNode->x += $vector['x'];
    $oNode->y += $vector['y'];

    Log::debug("transform node: " . $oNode->id . " (" . $aTmp['x'] . "," . $aTmp['y'] . ") to " .
                                 "(" . $oNode->x . "," . $oNode->y . ")");

  }

  public function transform_to_origin( MapNodes &$oNode ) {
    
    $aTmp['x'] = $oNode->x;
    $aTmp['y'] = $oNode->y;

    $oNode->x += $this->transformVector['x'];
    $oNode->y += $this->transformVector['y'];

    Log::debug("transform node: " . $oNode->id . " (" . $aTmp['x'] . "," . $aTmp['y'] . ") to " .
                                 "(" . $oNode->x . "," . $oNode->y . ")");

  }

}