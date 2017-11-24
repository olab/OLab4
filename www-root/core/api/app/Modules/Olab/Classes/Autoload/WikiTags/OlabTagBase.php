<?php

namespace App\Modules\Olab\Classes\Autoload\WikiTags;

use Illuminate\Support\Facades\Log;
use App\Modules\Olab\Classes\Autoload\OlabAutoloadBase;
use App\Modules\Olab\Classes\OlabCodeTracer;

/**
 * Abstract Base class for Olab Wiki tags
 *
 *
 * @version 1.0
 * @author wirunc
 */
abstract class OlabTagBase extends OlabAutoloadBase
{
    const WIKI_PATTERN = "/\[\[(.*?)\]\]/";
    const WIKI_TAG_CLASS_NS = "\\App\\Modules\\Olab\\Classes\\Autoload\\WikiTags\\";
    const PATH_TO_WIKI_TAG_CLASSES = "WikiTags/";

    // base classes need to derive this
    abstract public static function Render( $sNodeMarkup, &$oScopeObjectManger, $sWikiInnerText );

    /**
     * Autoload wiki tag class by class name
     * @param mixed $class
     */
    private static function AutoLoadClass( $class ) {

        $filename = parent::GetAutoLoadBasePath() . "/" . self::PATH_TO_WIKI_TAG_CLASSES . $class . '.php';
        Log::debug("looking to autoload class from file: " . $filename );

        if (file_exists( $filename )) {
            require_once $filename;
        }
    }

    /**
     * Build wiki tag renderer error message
     * @param mixed $exception
     * @param mixed $sWikiInnerText
     * @param mixed $sNodeMarkup
     * @return mixed
     */
    protected static function BuildWikiError( $exception, $sWikiInnerText, $sNodeMarkup ) {
        return str_replace( "[[" . $sWikiInnerText . "]]" ,
                           "&gt;&gt;ERROR(" . $sWikiInnerText . "): " . $exception->getMessage() . "&lt;&lt;",
                           $sNodeMarkup );
   }

    /**
     * Creates an instance of a Wiki tag command renderer class
     * @param mixed $sWikiTagCommand Wiki tag command string
     * @return mixed Instance of tag renderer class
     */
    public static function ClassFactory( $sWikiTagCommand ) {

        try
        {
            $sClassName = OlabTagBase::GetClassName( $sWikiTagCommand );

            // autoload the file containing the render class
            self::AutoLoadClass( $sClassName );

            // add namespace to class name
            $sClassName = self::WIKI_TAG_CLASS_NS . $sClassName;

            // test if class exists (i.e. has been autoloaded)
            if ( class_exists( $sClassName, false )) {

                // instantiate class and pass back
                return new $sClassName();
            }
            else {
                Log::debug( "Class '" . $sClassName . " not rendered on server" );
                return null;
            }

        }
        catch (Exception $exception)
        {
            Log::error($exception->getMessage());
        }

        Log::error( "Could not instantiate class for '" . $sWikiTagCommand  . "'" );

        return null;
    }

    /**
     * Get olab Wiki tag class name from Wiki command string
     * @param mixed $sTag Full Wiki tag string
     * @return string
     */
    private static function GetClassName( $sTag )
    {
        $sTag = self::ExtractWikiCommand( $sTag );
        $sTag  = ucwords( strtolower( $sTag ) );
        return "Olab" . $sTag . "Tag";
    }

    /**
     * Extract all the wiki tags from the markup
     * @param mixed $sNodeMarkup text
     * @return mixed Array of Wiki tag inner text strings
     **/
    public static function ExtractWikiTags( $sNodeMarkup ) {

        preg_match_all( self::WIKI_PATTERN, $sNodeMarkup, $aMatches );
        if ( sizeof( $aMatches ) != 2 )
            return null;
        return $aMatches[1];
    }

    /**
     * Get Wiki tag command from Wiki tag
     * @param mixed $sTag
     * @return mixed Wiki tag
     */
    private static function ExtractWikiCommand( $sTag ) {
        $aParts = preg_split('/(:|,)/', $sTag );
        return $aParts[0];
    }

    /**
     * Split up Wiki tag into parts
     * @param mixed $sTag
     * @return mixed Wiki tag
     */
    protected static function ExtractWikiParts( $sTag ) {
        $aParts = preg_split('/(:|,)/', $sTag );
        return $aParts;
    }

}