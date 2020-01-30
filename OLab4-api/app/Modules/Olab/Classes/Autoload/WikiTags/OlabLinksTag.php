<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Entrada is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Entrada is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Entrada.  If not, see <http://www.gnu.org/licenses/>.
 *
 * A wrapper for the
 *
 * @author Organisation: Cumming School of Medicine, University of Calgary
 * @author Developer: Corey Wirun (corey@cardinalcreek.ca)
 * @copyright Copyright 2017 University of Calgary. All Rights Reserved.
 */

namespace Entrada\Modules\Olab\Classes\Autoload\WikiTags;

use Illuminate\Support\Facades\Log;
use \Exception;
use Entrada\Modules\Olab\Classes\Autoload\MimeTypes\OlabMimeTypeBase;
use Entrada\Modules\Olab\Classes\SiteFileHandler;
use Entrada\Modules\Olab\Classes\UserStateHandler;
use Entrada\Modules\Olab\Classes\OlabExceptionHandler;
use Entrada\Modules\Olab\Classes\OlabCodeTracer;
use Entrada\Modules\Olab\Models\UserState;
use Entrada\Modules\Olab\Classes\ScopedObjectManager;

class OlabLinksTag extends OlabTagBase
{
  public static function AdjustProperties( &$aaPayload, UserState $oState, ScopedObjectManager &$oScopeObjectManger ) {

    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "(...)" );

    $aNewLinks = array();
    
    try
    {

      if ( $aaPayload['node']['linkTypeId'] == 2 ) {
        self::_handleRandomLinks( $aaPayload['node'] );
      }
      else if ( $aaPayload['node']['linkTypeId'] == 3 ) {
        self::_handleRandomPickOneLink( $aaPayload['node'] );
      }
      else {

        $num_node_links = sizeof( $aaPayload['node']['MapNodeLinks'] );

        // get and apply probability that link will by applied
        for ( $j = 0; $j < $num_node_links;  $j++ ) {
          
          $aLink = $aaPayload['node']['MapNodeLinks'][ $j ];
          
          if ( $aLink['probability'] != 0 ) {

            $chance_of_showing = $aLink['probability'];
            $chance = rand( 0, 100 );

            if ( $chance <= $chance_of_showing ) {
              continue;
            }

          }

          $aNewLinks[] = $aLink;          

        }

        $aaPayload['node']['MapNodeLinks'] = $aNewLinks;

      }


    }
    catch (Exception $exception)
    {
      OlabExceptionHandler::logException( $tracer->sBlockName, $exception, false );      
    }      

  }  

  private static function _handleRandomLinks( &$aNode ) {

  }

  private static function _handleRandomPickOneLink( &$aNode ) {
    
    // make a copy of the node link array
    $aaNodeLinks = $aNode['MapNodeLinks'];

    $nProbTotal = 0;
    $nCumulativeTotal = 0;

    // tally up total probabilities for links
    foreach ($aaNodeLinks as $aNodeLink) {      
      $nProbTotal += (int)$aNodeLink['probability'];
    }

    // adjust the probabilities as a cummulative percentage of total
    foreach ($aaNodeLinks as &$aNodeLink) {      

      if ( $nProbTotal == 0  ) {
        $nNewProbability = 100 / sizeof($aaNodeLinks);        
      }
      else {
        $nNewProbability = $aNodeLink['probability'] / $nProbTotal * 100;
      }

      if ( $nCumulativeTotal == 0 ) {
        $nCumulativeTotal = $nNewProbability;
      }
      else {
        $nCumulativeTotal += $nNewProbability;       
      }

      $aNodeLink['probability'] = $nCumulativeTotal;
    }

    // choose a link
    $nChoice = mt_rand(0, 100);
    $nIdChosen = 0;

    foreach ($aaNodeLinks as $aNodeLink) {      

      if ( $aNodeLink['probability'] < $nChoice ) {
        continue;
      }
      else {
        $nIdChosen = $aNodeLink['id'];
        break;
      }

    }

    // remove all but the id of choice from the original node link array
    foreach ($aNode['MapNodeLinks'] as $aNodeLink) {   
      
      if ( $aNodeLink['id'] == $nIdChosen ) {
        $aNode['MapNodeLinks'] = array();
        array_push( $aNode['MapNodeLinks'], $aNodeLink );
        break;
      }
    }

  }

}