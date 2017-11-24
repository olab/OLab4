<?php

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
*/

Route::post('/login', 'AuthController@postLogin');

Route::post('/logout', 'AuthController@postLogout');

Route::get('/user', 'AuthController@getUser');
