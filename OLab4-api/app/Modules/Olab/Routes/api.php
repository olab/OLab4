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

// CMW: original
/*
Route::get('/olab', function (Request $request) {
    // return $request->olab();
})->middleware('auth:api');
*/

Route::delete( 'olab/constants/{id}',             'OlabConstantAuthoringController@delete');
Route::delete( 'olab/counters/{id}',              'OlabCounterAuthoringController@delete');
Route::delete( 'olab/files/{id}',                 'OlabFileAuthoringController@delete');
Route::delete( 'olab/maps/{mapId}',               'OlabMapAuthoringController@delete');
Route::delete( 'olab/maps/{mapId}/nodes/{nodeId}','OlabNodeAuthoringController@delete');
Route::delete( 'olab/maps/{mapId}/nodes/{nodeId}/links/{linkId}', 'OlabLinkAuthoringController@delete');
Route::delete( 'olab/questionresponses/{id}',     'OlabQuestionResponseAuthoringController@delete');
Route::delete( 'olab/questions/{id}',             'OlabQuestionAuthoringController@delete');
Route::delete( 'olab/templates/{id}',             'OlabTemplateAuthoringController@delete');

Route::get( 'olab',                                'OlabMainController@index');
Route::get( 'olab/admin/roles',                    'OlabAdminController@roles');
Route::get( 'olab/admin/users',                    'OlabAdminController@users');
Route::get( 'olab/constants',                     'OlabConstantAuthoringController@getMany');
Route::get( 'olab/constants/{id}',                'OlabConstantAuthoringController@getSingle');
Route::get( 'olab/convert/{nodeId}',               'OlabConversionController@convert');
Route::get( 'olab/convert/index/{version}',        'OlabConversionController@index');
Route::get( 'olab/counters',                      'OlabCounterAuthoringController@getMany');
Route::get( 'olab/counters/{id}',                 'OlabCounterAuthoringController@getSingle');
Route::get( 'olab/courses',                       'OlabCoursesAuthoringController@getMany');
Route::get( 'olab/courses/{courseId}',            'OlabCoursesAuthoringController@getSingle');
Route::get( 'olab/file/{fileId}',                  'OlabMainController@download');
Route::get( 'olab/files',                         'OlabFileAuthoringController@getMany');
Route::get( 'olab/files/{id}',                    'OlabFileAuthoringController@get');
Route::get( 'olab/globals',                       'OlabGlobalAuthoringController@getMany');
Route::get( 'olab/globals/{globalId}',            'OlabGlobalAuthoringController@getSingle');
Route::get( 'olab/h5p/embed/{contentId}',         'OlabH5PController@embed');
Route::get( 'olab/info',                          'OlabAdminController@info');
Route::get( 'olab/info/{mapId}/{nodeId}',          'OlabNodeController@info');
Route::get( 'olab/lrs/endpoints/active',           'OlabLrsController@endpoints_active');
Route::get( 'olab/lrs/statements/new',             'OlabLrsController@statements_new');
Route::get( 'olab/map/canopen/{mapId}/{nodeId?}',  'OlabMapController@canOpen');
Route::get( 'olab/map/info/{mapId}',               'OlabMapController@info');
Route::get( 'olab/map/list',                       'OlabMapController@list');
Route::get( 'olab/mapnode/list',                   'OlabNodeController@list');
Route::get( 'olab/maps',                          'OlabMapAuthoringController@getMany');
Route::get( 'olab/maps/{mapId}',                  'OlabMapAuthoringController@getSingle');
Route::get( 'olab/maps/{mapId}/counteractions',   'OlabMapAuthoringController@getCounterActions');
Route::get( 'olab/maps/{mapId}/nodes',            'OlabNodeAuthoringController@getMany');
Route::get( 'olab/maps/{mapId}/nodes/{nodeId}',   'OlabNodeAuthoringController@get');
Route::get( 'olab/maps/{mapId}/nodes/{nodeId}/links/{linkId}', 'OlabLinkAuthoringController@getSingle');
Route::get( 'olab/maps/{mapId}/nodes/{nodeId}/scopedobjects', 'OlabScopedObjectAuthoringController@getNodeScopedObjects');
Route::get( 'olab/maps/{mapId}/scopedobjects',    'OlabScopedObjectAuthoringController@getMapScopedObjects');
Route::get( 'olab/media/{mapId}/{nodeId}/{mediaId}', 'OlabMainController@media');
Route::get( 'olab/play/{mapId}/{nodeId?}',         'OlabNodeController@play');
Route::get( 'olab/questionresponses/{id}',        'OlabQuestionResponseAuthoringController@getSingle');
Route::get( 'olab/questions',                     'OlabQuestionAuthoringController@getMany');
Route::get( 'olab/questions/{id}',                'OlabQuestionAuthoringController@getSingle');
Route::get( 'olab/questions/{id}/questionresponses','OlabQuestionResponseAuthoringController@getMany');
Route::get( 'olab/resume/{mapId}/{nodeId?}',       'OlabNodeController@resume');
Route::get( 'olab/servers',                       'OlabServerAuthoringController@getMany');
Route::get( 'olab/servers/{serverId}',            'OlabServerAuthoringController@getSingle');
Route::get( 'olab/template/links',                'OlabLinkAuthoringController@getTemplate');
Route::get( 'olab/template/nodes',                'OlabNodeAuthoringController@getTemplate');
Route::get( 'olab/templates',                     'OlabTemplateAuthoringController@getMany');
Route::get( 'olab/templates/{templateId}',        'OlabTemplateAuthoringController@get');
Route::get( 'olab/test',                          'OlabAdminController@test');

Route::post( 'olab/constants',                    'OlabConstantAuthoringController@create');
Route::post( 'olab/counters',                     'OlabCounterAuthoringController@create');
Route::post( 'olab/counters/value/{id}',            'OlabCounterController@editValue');
Route::post( 'olab/files',                        'OlabFileAuthoringController@create');
Route::post( 'olab/h5p/saveResult',                'OlabH5PController@saveResult');
Route::post( 'olab/h5p/saveXAPIStatement',         'OlabH5PController@saveXAPIStatement');
Route::post( 'olab/import/upload',                 'OlabConversionController@upload');
Route::post( 'olab/lrs/statements/transmit',       'OlabLrsController@statements_transmit');
Route::post( 'olab/maps',                         'OlabMapAuthoringController@create');
Route::post( 'olab/maps/{mapId}',                 'OlabMapAuthoringController@insertFromTemplate');
Route::post( 'olab/maps/{mapId}/nodes',            'OlabNodeAuthoringController@create');
Route::post( 'olab/maps/{mapId}/nodes/{nodeId}/links', 'OlabLinkAuthoringController@create');
Route::post( 'olab/questionresponses',             'OlabQuestionResponseAuthoringController@create');
Route::post( 'olab/question/dropdown/{nodeId}',    'OlabQuestionController@postDropdownResponse');
Route::post( 'olab/question/multichoice/{nodeId}', 'OlabQuestionController@postMultichoiceResponse');
Route::post( 'olab/question/radio/{nodeId}',       'OlabQuestionController@postRadioResponse');
Route::post( 'olab/question/slider/{nodeId}',      'OlabQuestionController@postSliderResponse');
Route::post( 'olab/questions',                    'OlabQuestionAuthoringController@create');
Route::post( 'olab/suspend/{mapId}/{nodeId}',      'OlabNodeController@suspend');
Route::post( 'olab/templates',                    'OlabTemplateAuthoringController@create');
Route::post( 'olab/templates/{templateId}/nodes',  'OlabTemplateAuthoringController@cloneInsertNodes');

Route::put( 'olab/constants/{id}',                'OlabConstantAuthoringController@edit');
Route::put( 'olab/counters/{id}',                 'OlabCounterAuthoringController@edit');
Route::put( 'olab/files/{id}',                    'OlabFileAuthoringController@edit');
Route::put( 'olab/maps/{mapId}',                   'OlabMapAuthoringController@edit');
Route::put( 'olab/maps/{mapId}/counteractions',   'OlabMapAuthoringController@editCounterActions');
Route::put( 'olab/maps/{mapId}/nodes',            'OlabNodeAuthoringController@edits');
Route::put( 'olab/maps/{mapId}/nodes/{nodeId}',   'OlabNodeAuthoringController@edit');
Route::put( 'olab/maps/{mapId}/nodes/{nodeId}/links/{linkId}', 'OlabLinkAuthoringController@edit');
Route::put( 'olab/questionresponses/{id}',        'OlabQuestionResponseAuthoringController@edit');
Route::put( 'olab/questions/{id}',                'OlabQuestionAuthoringController@edit');