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
use Entrada\Modules\Olab\Classes\OlabCodeTracer;
use Entrada\Modules\Olab\Classes\OlabConstants;
use Entrada\Modules\Olab\Models\UserState;
use Entrada\Modules\Olab\Classes\ScopedObjectManager;

class OlabMrTag extends OlabTagBase
{
  const OLAB_HTML_TAG = "olab-download";

  /**
    * Renderer for wikitag
    * @param int $show_wiki 
    * @param UserState $oState 
    * @param mixed $sNodeMarkup 
    * @param ScopedObjectManager $oScopeObjectManger 
    * @param mixed $sWikiInnerText 
    * @return mixed
    */
  public static function Render( int $show_wiki, UserState $oState, $sNodeMarkup, ScopedObjectManager &$oScopeObjectManger, $sWikiInnerText )
  {
    // spin up a function tracer.  Handles entry/exit/timing messages
    $tracer = new OlabCodeTracer(__CLASS__, __FUNCTION__ . "($sWikiInnerText)" );

    try
    {
      $aParts = parent::ExtractWikiParts( $sWikiInnerText );
      $id = trim($aParts[1], "\"");
      $sTag = self::OLAB_HTML_TAG;

      $aFile = $oScopeObjectManger->getFile( $id );

      if ( $aFile == null )
        throw new Exception( "Unable to find file resource. id = " . $id );

      // test if file is not embedded (downloadable via link), means the rendering will
      // happen browser side - so leave markup alone.
      if ( $aFile["isEmbedded"] == 0 ) {
        return $sNodeMarkup;
      }

      // test if image mime type
      if ( OlabMimeTypeBase::IsImageType( $aFile["mime"] ) ) {
        $sTag = "olab-image";
      }

      // test if audio mime type
      else if ( OlabMimeTypeBase::IsAudioType( $aFile["mime"] ) ) {
        $sTag = "olab-audio";
      }

      // test if audio mime type
      else if ( OlabMimeTypeBase::IsVideoType( $aFile["mime"] ) ) {
        $sTag = "olab-video";
      }

      // build base64 encoded content mime string
      $sEncodedContent = OlabMimeTypeBase::GetEncodedContentString( $aFile );

      $aFile["encodedContent"] = $sEncodedContent;

      $element = "<" . $sTag .
              " class='" . $sTag . "'" .
              " id='" . $sTag . $id . "'" .
              " v-bind:file='file(\"" . $id . "\")'" .
              " src='" . $sEncodedContent . "'></" .
              $sTag . ">";

      if ( $show_wiki == OlabConstants::RENDER_MARKUP ) {
        $sNodeMarkup = str_replace( "[[" . $sWikiInnerText . "]]" , $element, $sNodeMarkup );
      }
      else if ( $show_wiki == OlabConstants::RENDER_MARKUP_WITH_WIKITAG ) {
        $sNodeMarkup = str_replace( "[[" . $sWikiInnerText . "]]" , "[[" . $sWikiInnerText . "]]" . $element, $sNodeMarkup );
      }

      // remove the encoded string so we can log the text
      $element = "<" . $sTag .
              " class='" . $sTag . "'" .
              " id='" . $sTag . $id . "'" .
              " v-bind:file='file(\"" . $id . "\")'" .
              " src='<EncodedContent size:" . strlen( $sEncodedContent ) . ">'></" .
              $sTag . ">";

      Log::debug("node text '" . $element . "'" );

    }
    catch (Exception $exception)
    {
      $sNodeMarkup = parent::BuildWikiError( $exception, $sWikiInnerText, $sNodeMarkup );
    }

    return $sNodeMarkup;
  }

}