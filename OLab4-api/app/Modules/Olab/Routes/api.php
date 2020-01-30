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

Route::get('olab/convert/index/{version}',        'OlabConversionController@index');
Route::get('olab/convert/{nodeId}',               'OlabConversionController@convert');
Route::post('olab/import/upload',                 'OlabConversionController@upload');

Route::get('olab',                                'OlabMainController@index');
Route::get('olab/file/{fileId}',                  'OlabMainController@download');
Route::get('olab/media/{mapId}/{nodeId}/{mediaId}', 'OlabMainController@media');

Route::get('olab/play/{mapId}/{nodeId?}',         'OlabNodeController@play');
Route::get('olab/resume/{mapId}/{nodeId?}',       'OlabNodeController@resume');
Route::get('olab/info/{mapId}/{nodeId}',          'OlabNodeController@info');

Route::get('olab/map/list',                       'OlabMapController@list');
Route::get('olab/map/info/{mapId}',               'OlabMapController@info');
Route::get('olab/map/canopen/{mapId}/{nodeId?}',  'OlabMapController@canOpen');

Route::get('olab/mapnode/list',                   'OlabNodeController@list');

Route::get('olab/admin/users',                    'OlabAdminController@users');
Route::get('olab/admin/roles',                    'OlabAdminController@roles');

Route::post('olab/question/radio/{nodeId}',       'OlabQuestionController@postRadioResponse');
Route::post('olab/question/multichoice/{nodeId}', 'OlabQuestionController@postMultichoiceResponse');
Route::post('olab/question/dropdown/{nodeId}',    'OlabQuestionController@postDropdownResponse');
Route::post('olab/question/slider/{nodeId}',      'OlabQuestionController@postSliderResponse');
Route::post('olab/suspend/{mapId}/{nodeId}',      'OlabNodeController@suspend');

Route::get('olab/lrs/endpoints/active',           'OlabLrsController@endpoints_active');
Route::get('olab/lrs/statements/new',             'OlabLrsController@statements_new');
Route::post('olab/lrs/statements/transmit',       'OlabLrsController@statements_transmit');

Route::post('olab/h5p/saveXAPIStatement',         'OlabH5PController@saveXAPIStatement');
Route::get( 'olab/h5p/embed/{contentId}',         'OlabH5PController@embed');
Route::post('olab/h5p/saveResult',                'OlabH5PController@saveResult');

// authoring API calls

// scoped object calls
Route::get( 'olab/maps/{mapId}/scopedobjects',    'OlabScopedObjectAuthoringController@getMapScopedObjects');
Route::get( 'olab/maps/{mapId}/nodes/{nodeId}/scopedobjects', 'OlabScopedObjectAuthoringController@getNodeScopedObjects');

Route::post( 'olab/files',                        'OlabFileAuthoringController@create');
Route::get( 'olab/files',                         'OlabFileAuthoringController@getMany');
Route::get( 'olab/files/{id}',                    'OlabFileAuthoringController@get');
Route::delete( 'olab/files/{id}',                 'OlabFileAuthoringController@delete');
Route::put( 'olab/files/{id}',                    'OlabFileAuthoringController@edit');

Route::post( 'olab/constants',                    'OlabConstantAuthoringController@create');
Route::get( 'olab/constants',                     'OlabConstantAuthoringController@getMany');
Route::get( 'olab/constants/{id}',                'OlabConstantAuthoringController@getSingle');
Route::delete( 'olab/constants/{id}',             'OlabConstantAuthoringController@delete');
Route::put( 'olab/constants/{id}',                'OlabConstantAuthoringController@edit');

Route::post( 'olab/counters',                     'OlabCounterAuthoringController@create');
Route::get( 'olab/counters',                      'OlabCounterAuthoringController@getMany');
Route::get( 'olab/counters/{id}',                 'OlabCounterAuthoringController@getSingle');
Route::delete( 'olab/counters/{id}',              'OlabCounterAuthoringController@delete');
Route::put( 'olab/counters/{id}',                 'OlabCounterAuthoringController@edit');
Route::post( 'olab/counters/value/{id}',            'OlabCounterController@editValue');

Route::post( 'olab/questions',                    'OlabQuestionAuthoringController@create');
Route::get( 'olab/questions',                     'OlabQuestionAuthoringController@getMany');
Route::get( 'olab/questions/{id}',                'OlabQuestionAuthoringController@getSingle');
Route::delete( 'olab/questions/{id}',             'OlabQuestionAuthoringController@delete');
Route::put( 'olab/questions/{id}',                'OlabQuestionAuthoringController@edit');

Route::post( 'olab/questions/{id}/questionresponses',
                                                  'OlabQuestionResponseAuthoringController@create');
Route::get( 'olab/questions/{id}/questionresponses',
                                                  'OlabQuestionResponseAuthoringController@getMany');
Route::get( 'olab/questions/{id}/questionresponses/{id2}',
                                                  'OlabQuestionResponseAuthoringController@getSingle');
Route::delete( 'olab/questions/{id}/questionresponses/{id2}',
                                                  'OlabQuestionResponseAuthoringController@delete');
Route::put( 'olab/questions/{id}/questionresponses/{id2}',
                                                  'OlabQuestionResponseAuthoringController@edit');

// map API calls
Route::get( 'olab/maps',                          'OlabMapAuthoringController@getMany');
Route::get( 'olab/maps/{mapId}',                  'OlabMapAuthoringController@getSingle');
Route::post( 'olab/maps',                         'OlabMapAuthoringController@create');
Route::post( 'olab/maps/{mapId}',                 'OlabMapAuthoringController@insertFromTemplate');
Route::delete( 'olab/maps/{mapId}',               'OlabMapAuthoringController@delete');
Route::put('olab/maps/{mapId}',                   'OlabMapAuthoringController@edit');

Route::get( 'olab/maps/{mapId}/counteractions',   'OlabMapAuthoringController@getCounterActions');
Route::put( 'olab/maps/{mapId}/counteractions',   'OlabMapAuthoringController@editCounterActions');

// template API calls
Route::get( 'olab/templates',                     'OlabTemplateAuthoringController@getMany');
Route::get( 'olab/templates/{templateId}',        'OlabTemplateAuthoringController@get');
Route::post( 'olab/templates',                    'OlabTemplateAuthoringController@create');
Route::delete( 'olab/templates/{id}',             'OlabTemplateAuthoringController@delete');
Route::post('olab/templates/{templateId}/nodes',  'OlabTemplateAuthoringController@cloneInsertNodes');

// node API calls
Route::get( 'olab/maps/{mapId}/nodes',            'OlabNodeAuthoringController@getMany');
Route::get( 'olab/maps/{mapId}/nodes/{nodeId}',   'OlabNodeAuthoringController@get');
Route::post('olab/maps/{mapId}/nodes',            'OlabNodeAuthoringController@create');
Route::put( 'olab/maps/{mapId}/nodes/{nodeId}',   'OlabNodeAuthoringController@edit');
Route::put( 'olab/maps/{mapId}/nodes',            'OlabNodeAuthoringController@edits');
Route::delete( 'olab/maps/{mapId}/nodes/{nodeId}','OlabNodeAuthoringController@delete');

// template API calls
Route::get( 'olab/template/nodes',                'OlabNodeAuthoringController@getTemplate');
Route::get( 'olab/template/links',                'OlabLinkAuthoringController@getTemplate');

// link API calls
Route::get( 'olab/maps/{mapId}/nodes/{nodeId}/links/{linkId}', 
                                                  'OlabLinkAuthoringController@getSingle');
Route::post( 'olab/maps/{mapId}/nodes/{nodeId}/links', 
                                                  'OlabLinkAuthoringController@create');
Route::put('olab/maps/{mapId}/nodes/{nodeId}/links/{linkId}',
                                                  'OlabLinkAuthoringController@edit');
Route::delete('olab/maps/{mapId}/nodes/{nodeId}/links/{linkId}',
                                                  'OlabLinkAuthoringController@delete');

Route::get( 'olab/info',                          'OlabAdminController@info');
Route::get( 'olab/test',                          'OlabAdminController@test');

Route::get( 'olab/servers',                       'OlabServerAuthoringController@getMany');
Route::get( 'olab/servers/{serverId}',            'OlabServerAuthoringController@getSingle');

Route::get( 'olab/courses',                       'OlabCoursesAuthoringController@getMany');
Route::get( 'olab/courses/{courseId}',            'OlabCoursesAuthoringController@getSingle');

Route::get( 'olab/globals',                       'OlabGlobalAuthoringController@getMany');
Route::get( 'olab/globals/{globalId}',            'OlabGlobalAuthoringController@getSingle');