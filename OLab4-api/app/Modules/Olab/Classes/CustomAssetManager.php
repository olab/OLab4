<?php

namespace Entrada\Modules\Olab\Classes;

use Entrada\Modules\Olab\Classes\ArrayHelper;
use Illuminate\Support\Facades\Log;

class CustomAssetManager
{
    /**
     * @var array
     */
    private static $scripts = [];

    /**
     * @var array
     */
    private static $styles = [];

    /**
     * @var array
     */
    private static $raw_scripts = [];

    /***
     * @param string $handle
     * @param string $code
     */
    public static function addRawScript($handle, $code, $replace = false)
    {
        if ( $replace == false ) {
          Log::debug('Adding raw script [' . $handle . ']: ' . $code );
        }
        else {
          Log::debug('Setting raw script [' . $handle . ']: ' . $code );
          static::$raw_scripts[$handle] = [];          
        }

        static::$raw_scripts[$handle][] = $code;
    }

    /**
     * @param string $handle
     * @param string $path
     */
    public static function addScript($handle, $path, $replace = false)
    {
        $path = static::sanitizePath($path);
        if ( $replace == false ) {
          Log::debug('Adding script path [' . $handle . ']: ' . $path );
        }
        else {
          Log::debug('Setting script path [' . $handle . ']: ' . $path );
          static::$scripts[$handle] = [];          
        }

        static::$scripts[$handle][] = $path;
    }

    /**
     * @param string $handle
     * @param string $path
     */
    public static function addStyle($handle, $path, $replace = false)
    {
        $path = static::sanitizePath($path);
        if ( $replace == false ) {
          Log::debug('Adding style path [' . $handle . ']: ' . $path );
        }
        else {
          Log::debug('Setting style path [' . $handle . ']: ' . $path );
          static::$styles[$handle] = [];          
        }

        static::$styles[$handle][] = $path;
    }

    /**
     * @param array $controller
     * @return array
     */
    public static function loadAssets($templateData, $site_prefix = '')
    {
        if (empty($templateData)) {
            $templateData = [];
        }

        $temp_prefix = "";

        foreach (static::$scripts as $key => $value) {
            if ( strpos( $value[0], "http" ) === 0 ) {
              $temp_prefix = "";
            }
            else {
              $temp_prefix = $site_prefix;              
            }
            $templateData['scripts_stack'][] = array( 'id' => $key, 'src' => $temp_prefix . $value[0] );        	
        }

        foreach (static::$styles as $key => $value) {
            $templateData['styles_stack'][] = array( 'id' => $key, 'src' => $site_prefix . $value[0] );        	
        }

        foreach (static::$raw_scripts as $key => $value) {
            $templateData['raw_scripts_stack'][] = array( 'id' => $key, 'src' => $value[0] );        	
        }

        //foreach (static::getScripts() as $script) {
        //    $templateData['scripts_stack'][] = $script;
        //}

        //foreach (static::getStyles() as $script) {
        //    $templateData['styles_stack'][] = $script;
        //}

        //foreach (static::getRawScripts() as $script) {
        //    $templateData['raw_scripts_stack'][] = $script;
        //}

        return $templateData;
    }

    public static function GetScriptCount() {
      return sizeof( static::$scripts );
    }

    public static function GetStyleCount() {
      return sizeof( static::$styles );
    }

    public static function GetRawScriptCount() {
      return sizeof( static::$raw_scripts );
    }

    /**
     * @return array
     */
    public static function getScripts()
    {
        return ArrayHelper::flatten(static::$scripts);
    }

    /**
     * @return array
     */
    public static function getStyles()
    {
        return ArrayHelper::flatten(static::$styles);
    }

    /**
     * @return array
     */
    public static function getRawScripts()
    {
        return ArrayHelper::flatten(static::$raw_scripts);
    }

    /**
     * @param string $path
     * @return string
     */
    private static function sanitizePath($path)
    {
        if (strpos($path, 'http') === 0) {
            return $path;
        }

        return '/' . ltrim($path, '/');
    }
}
