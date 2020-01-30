<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

use \Entrada\Models\Auth\User;

Route::get('/', function () {
    return 'Welcome to the Entrada API v2. Please see our documentation for assistance.';
});
