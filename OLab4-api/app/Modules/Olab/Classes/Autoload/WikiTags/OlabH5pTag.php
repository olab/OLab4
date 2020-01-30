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
use Entrada\Modules\Olab\Classes\h5p\H5PPlugin;
use Entrada\Modules\Olab\Models\UserState;
use Entrada\Modules\Olab\Classes\ScopedObjectManager;

class OlabH5pTag extends OlabTagBase
{

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
            $id = (int)trim($aParts[1], "\"");

            $element = H5PPlugin::renderShortCode($id);

            if ( $show_wiki == OlabConstants::RENDER_MARKUP ) {
              $sNodeMarkup = str_replace( "[[" . $sWikiInnerText . "]]" , $element, $sNodeMarkup );
            }
            else if ( $show_wiki == OlabConstants::RENDER_MARKUP_WITH_WIKITAG ) {
              $sNodeMarkup = str_replace( "[[" . $sWikiInnerText . "]]" , "[[" . $sWikiInnerText . "]]" . $element, $sNodeMarkup );
            }

            Log::debug(__CLASS__ . " replace: [[" . $sWikiInnerText . "]] = '" . $element . "'" );

        }
        catch (Exception $exception)
        {
            $sNodeMarkup = parent::BuildWikiError( $exception, $sWikiInnerText, $sNodeMarkup );
        }

        return $sNodeMarkup;
    }

}
