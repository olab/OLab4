<?php

namespace App\Modules\Olab\Classes\Autoload\MimeTypes;

use App\Modules\Olab\Models\SystemSettings;
use App\Modules\Olab\Classes\SiteFileHandler;
use Illuminate\Support\Facades\Log;
use App\Modules\Olab\Classes\Autoload\OlabAutoloadBase;

/**
 * Abstract Base class for Olab mime type handlers
 *
 * @version 1.0
 * @author wirunc
 */
abstract class OlabMimeTypeBase extends OlabAutoloadBase
{
    protected static $fileRoot;

    const MIME_TYPE_CLASS_NS = "\\App\\Modules\\Olab\\Classes\\Autoload\\MimeTypes\\";
    const PATH_TO_MIME_TYPES_CLASSES = "MimeTypes/";

    /**
     * Autoload mime type class by class name
     * @param mixed $class
     */
    public static function AutoLoadClass( $class ) {

        $filename = parent::GetAutoLoadBasePath() . "/" . self::PATH_TO_MIME_TYPES_CLASSES . $class . '.php';
        Log::debug("looking to autoload class from file: " . $filename );

        if (file_exists( $filename )) {
            require_once $filename;
        }
    }


    // base classes need to derive this
    abstract public static function Render( $oSystemFile, $nMapId, $nNodeId );

    /**
     * Tests if mime type is an image type
     * @param mixed $sMimeType
     * @return boolean
     */
    public static function IsAudioType( $sMimeType ) {
        return ( strpos( $sMimeType, "audio/") !== false );
    }

    /**
     * Tests if mime type is an image type
     * @param mixed $sMimeType
     * @return boolean
     */
    public static function IsImageType( $sMimeType ) {
        return ( strpos( $sMimeType, "image/") !== false );
    }

    /**
     * Tests if mime type is a video type
     * @param mixed $sMimeType
     * @return boolean
     */
    public static function IsVideoType( $sMimeType ) {
        return ( strpos( $sMimeType, "video/") !== false );
    }
    
    /**
     * Get olab class name from Wiki command
     * @param mixed $sMimeType
     * @return string
     */
    public static function GetClassName( $sMimeType)
    {
        $sMimeType  = ucwords( strtolower( $sMimeType ) );
        $sMimeType = str_replace( "/", "", $sMimeType );
        $sMimeType = str_replace( "-", "", $sMimeType );

        return "Olab" . $sMimeType . "MimeType";
    }

    /**
     * Get base64 encoded file contents
     * @param mixed $fullFileName SystemFile array
     * @return null|string
     */
    public static function GetEncodedContentString( $aSystemFile ) {

        $mimeType = $aSystemFile["mime"];
        $fullFileName = SiteFileHandler::GetFilePath( $aSystemFile );
        return 'data:' . $mimeType . ';base64,' . base64_encode(file_get_contents($fullFileName));
    }
}

