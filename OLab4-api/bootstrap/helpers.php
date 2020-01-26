<?php

/*
|--------------------------------------------------------------------------
| Custom helpers
|--------------------------------------------------------------------------
|
| These are helper functions meant to run before Laravel is loaded.
|
*/

if (!function_exists('dd')) {
	function dd() {
	    array_map(function($x) { var_dump($x); }, func_get_args());
	    die;
	}
}

if (!function_exists('init_entrada')) {
    function init_entrada() {
        if (!defined('ENTRADA_ABSOLUTE')) {

            $entrada_root = __DIR__ . "/../../../../..";
            if (!file_exists($entrada_root . '/includes/init.inc.php')) {
                /**
                 * The Entrada directories are not available as a relative path. Need to determine the absolute path
                 */
                if (PHP_SAPI === 'cli') {
                    $command_path = dirname((empty($_SERVER["PWD"]) ? "" : $_SERVER["PWD"]) . "/" . $_SERVER["PHP_SELF"]);
                    while(strlen($command_path) > 1) {
                        $base = basename($command_path);
                        if ($base == "core") {
                            break;
                        } else {
                            $command_path = dirname($command_path);
                        }
                    }
                    $entrada_root = $command_path;
                } else {
                    $entrada_root = $_SERVER["DOCUMENT_ROOT"] . "/core";
                }
            }

            @set_include_path(implode(PATH_SEPARATOR, array(
                $entrada_root . '/',
                $entrada_root . '/includes',
                $entrada_root . '/library',
                $entrada_root . '/library/vendor',
                get_include_path(),
            )));

            require_once 'includes/init.inc.php';
        }
    }
}
