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

namespace App\Modules\Olab\Classes\Autoload\WikiTags;

use Illuminate\Support\Facades\Log;
use \Exception;
use App\Modules\Olab\Classes\Autoload\MimeTypes\OlabMimeTypeBase;
use App\Modules\Olab\Classes\SiteFileHandler;
use App\Modules\Olab\Classes\OlabCodeTracer;

class OlabMrTag extends OlabTagBase
{
    const OLAB_HTML_TAG = "olab-download";

    /**
     * Summary of render
     * @param mixed $aNode
     * @param mixed $oScopeObjectManger
     * @param mixed $sWikiInnerTag
     */
    public static function Render( $sNodeMarkup, &$oScopeObjectManger, $sWikiInnerText )
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
            if ( $aFile["is_embedded"] == 0 ) {
                return $sNodeMarkup;
            }

            // test if image mime type
            if ( OlabMimeTypeBase::IsImageType( $aFile["mime"] ) ) {
                $sTag = "olab-image";
            }

            // test if audio mime type
            if ( OlabMimeTypeBase::IsAudioType( $aFile["mime"] ) ) {
                $sTag = "olab-audio";
            }

            // test if audio mime type
            if ( OlabMimeTypeBase::IsVideoType( $aFile["mime"] ) ) {
                $sTag = "olab-video";
            }

            // build base64 encoded content mime string
            $sEncodedContent = OlabMimeTypeBase::GetEncodedContentString( $aFile );

            $aFile["encoded_content"] = $sEncodedContent;

            $element = "<" . $sTag .
                    " class='" . $sTag . "'" .
                    " id='" . $sTag . $id . "'" .
                    " v-bind:file='file(" . $id . ")'" .
                    " src='" . $sEncodedContent . "'></" .
                    $sTag . ">";

            $sNodeMarkup = str_replace( "[[" . $sWikiInnerText . "]]" , $element, $sNodeMarkup );

            // remove the encoded string so we can log the text
            $element = "<" . $sTag .
                    " class='" . $sTag . "'" .
                    " id='" . $sTag . $id . "'" .
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