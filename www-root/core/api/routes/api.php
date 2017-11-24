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

use \App\Models\Auth\User;

Route::get('/', function () {
    return 'Welcome to the Entrada API v2. Please see our documentation for assistance.';
});

/*
 * This is an example of how you can create basic routes with no controller.
 */

//$app->get('/hi', function () use ($app) {
//    return "Enter your name in the url, in the format /hi/{your name}";
//});

//$app->get('/hi/{name}', function ($name) use ($app) {
//    return "Hi, " . ucfirst($name) . "!";
//});
