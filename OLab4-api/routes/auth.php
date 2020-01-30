<?php

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
*/

Route::post('/login', 'AuthController@postLogin');

Route::post('/client', 'AuthController@postClient');

Route::post('/logout', 'AuthController@postLogout');

Route::get('/user', 'AuthController@getUser');
