<?php

namespace App\Modules\Olab\Classes\Autoload\MimeTypes;

use Illuminate\Support\Facades\Log;

/**
 * OlabImageTypeBase short summary.
 *
 * OlabImageTypeBase description.
 *
 * @version 1.0
 * @author wirunc
 */
class OlabVideoTypeBase extends OlabMimeTypeBase
{
    /**
     * Renderer for image mime types
     * @param mixed $oSystemFile
     */
    public static function render( $oSystemFile, $nMapId, $nNodeId ) {

        $content = parent::GetEncodedContentString( $oSystemFile->mime, $oSystemFile->path );
        $html = "<video controls><source type='" . $oSystemFile->mime ."' src='" .  $content . "'></video>";

        return $html;

    }
}