<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your module. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::apiResource('/locations/sites', 'SitesController');
Route::apiResource('/locations/sites_organisations', 'SitesOrganisationsController');
Route::apiResource('/locations/buildings', 'BuildingsController');
Route::apiResource('/locations/rooms', 'RoomsController');
Route::apiResource('/locations/countries', 'CountriesController');
Route::apiResource('/locations/provinces', 'ProvincesController');
Route::get('/locations/provinces/country/{country}', 'ProvincesController@showByCountry');
Route::get('/locations/rooms/building/{building}', 'RoomsController@showByBuilding');
Route::get('/locations/buildings/site/{site}', 'BuildingsController@showBySite');
Route::get('/locations/sites/org/{organisation}', 'SitesController@showByOrganisation');
