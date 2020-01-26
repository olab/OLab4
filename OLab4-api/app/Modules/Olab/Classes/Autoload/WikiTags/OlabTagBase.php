<?php

namespace Entrada\Modules\Olab\Classes\Autoload\WikiTags;

use Illuminate\Support\Facades\Log;
use Entrada\Modules\Olab\Classes\Autoload\OlabAutoloadBase;
use Entrada\Modules\Olab\Classes\UserStateHandler;
use Entrada\Modules\Olab\Classes\OlabCodeTracer;
use Entrada\Modules\Olab\Classes\ScopedObjectManager;
use Entrada\Modules\Olab\Models\UserState;

/**
 * Abstract Base class for Olab Wiki tags
 *
 *
 * @version 1.0
 * @author wirunc
 */
abstract class OlabTagBase extends OlabAutoloadBase
{
    const ALL_TAG_WIKI_PATTERN = "/\[\[(.*?)\]\]/";
    const SPECIFIC_TAG_WIKI_PATTERN = "/\[\[%TAG%:(.*?)\]\]/";

    // note: ensure this namespace is correct
    const CLASS_NS = "\\Entrada\\Modules\\Olab\\Classes\\Autoload\\WikiTags\\";

    const PATH_TO_CLASSES = "WikiTags/";

    // base classes need to derive this
    public static function Render( int $show_wiki, UserState $oState, $sNodeMarkup, ScopedObjectManager &$oScopeObjectManger, $sWikiInnerText ) {
      return $sNodeMarkup;
    }

    // default implementation of method that overrides source object properties
    public static function AdjustProperties( &$aPayload, UserState $oState,  ScopedObjectManager &$oScopeObjectManger ) {
      return null;
    }

    /**
     * Autoload wiki tag class by class name
     * @param mixed $class
     */
    private static function AutoLoadClass( $class ) {

        $filename = parent::GetAutoLoadBasePath() . "/" . self::PATH_TO_CLASSES . $class . '.php';
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
            $sClassName = self::CLASS_NS . $sClassName;

            // test if class exists (i.e. has been autoloaded)
            if ( class_exists( $sClassName, false )) {

                // instantiate class and pass back
                return new $sClassName();
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
     * @param string $tag optional WIKI tag to look for
     * @return mixed Array of Wiki tag inner text strings
     **/
    public static function ExtractWikiTags( $sNodeMarkup, $tag = null ) {

        $aMatches = null;

        // test if looking for specific WIKI tag
        if ( $tag == null ) {
            preg_match_all( self::ALL_TAG_WIKI_PATTERN, $sNodeMarkup, $aMatches );
        }
        else {
            $pattern = str_replace( "%TAG%", $tag, self::SPECIFIC_TAG_WIKI_PATTERN );
            preg_match_all( $pattern, $sNodeMarkup, $aMatches );                   
        }

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