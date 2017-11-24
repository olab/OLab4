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

Route::get('olab',                                'OlabController@index');
Route::get('olab/file/{fileId}',                  'OlabController@download');

Route::get('olab/play/{mapId}/{nodeId?}',         'OlabNodeController@play');

Route::get('olab/map/{mapId}',                    'OlabMapController@index');
Route::get('olab/info/{mapId}',                   'OlabMapController@info');

Route::post('olab/question/radio/{nodeId}',       'OlabQuestionController@postRadioResponse');
Route::post('olab/question/multichoice/{nodeId}', 'OlabQuestionController@postMultichoiceResponse');
Route::post('olab/question/dropdown/{nodeId}',    'OlabQuestionController@postDropdownResponse');

