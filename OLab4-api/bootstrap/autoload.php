<?php

define('LARAVEL_START', microtime(true));

/**
 * Make sure Entrada initialization is completed
 */

init_entrada();

/*
|--------------------------------------------------------------------------
| Register The Composer Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader
| for our application. We just need to utilize it! We'll require it
| into the script here so that we do not have to worry about the
| loading of any our classes "manually". Feels great to relax.
|
*/

require_once 'vendor/autoload.php';
