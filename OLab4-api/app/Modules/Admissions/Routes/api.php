<?php

use Illuminate\Support\Facades\Route;

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

// ===== APPLICANT SORTING =====
Route::post('/admissions/sort', 'SortController@sort');

Route::get('/admissions/', 'DataLoaderController@testModels');
Route::get('/admissions/load', 'DataLoaderController@load');
Route::get('/admissions/unzip', 'DataLoaderController@unzip');
Route::get('/admissions/parse', 'DataLoaderController@parse');
Route::get('/admissions/load-applicants', 'DataLoaderController@loadApplicants');
Route::get('/admissions/immigrate', 'DataLoaderController@immigrate');
Route::get('/admissions/decrypt', 'DataLoaderController@decrypt');
Route::get('/admissions/keys', 'DataLoaderController@loadKeys');

Route::get('/admissions/models', 'DataLoaderController@loadModels');

// ===== ADMISSIONS CYCLES =======
Route::resource("/admissions/cycles", "CycleController");

Route::resource("/admissions/flags", "FileFlagController");
Route::resource("/admissions/applicants/files/flags", "FileFlagController");
Route::resource("/admissions/applicants/{applicant_id}/files/{file_id}/flags", "FileFlagController");

// ===== APPLICANT FILES =======
// if this is declared after the applicant resource,
//      the 'show' method {/applicants/{id}) will override /applicants/file
Route::get("/admissions/applicants/files/{id}/download", "FileController@download");
Route::resource("/admissions/applicants/files", "FileController");

// =====  APPLICANTS  =====
Route::put("/admissions/applicants", "ApplicantController@massUpdate");
Route::get("/admissions/file-review/applicants", "ApplicantController@review");
Route::get("/admissions/applicants/{applicant_id}/files", "ApplicantController@files");

// Resource comes after custom routes!
Route::resource("/admissions/applicants", "ApplicantController", [
    'except' => ['create']
]);


// =====  READERS AND GROUPS  =====\
Route::post("/admissions/reader/{reader_type}", "ReaderController@storeWithType");
Route::post("/admissions/readers/upload", "ReaderController@createWithCSV");
Route::post("/admissions/readers/{type}", "ReaderController@createWithType");
Route::resource("/admissions/readers", "ReaderController");

Route::post("/admissions/reader-groups/mass-update", "ReaderGroupController@store");
Route::get("/admissions/reader-groups/assign", "ReaderGroupController@assignApplicants");
Route::resource("/admissions/reader-groups", "ReaderGroupController");

// Groups and Readers for specific applicants
Route::get("/admissions/applicants/{applicant_id}/groups", "ApplicantController@groups");
Route::get("/admissions/applicants/{applicant_id}/readers", "ApplicantController@readers");

Route::resource("/admissions/reader-types", "ReaderTypeController");


// ===== APPLICANT READER SCORES =======

// =====  POOLS  =====
// These should probably be post and put respectively, but let's not create problems where they don't need to be
Route::match(['post', 'put'], "/admissions/pools/{pool}/filters", "FilterController@storeWithPool");
Route::match(['post', 'put'], "/admissions/pools/{pool}/filters/{filter}", "FilterController@updateWithPool");
Route::resource("/admissions/pools", "PoolController");

// =====  POOL FILTERS  =====
Route::resource("/admissions/filters", "FilterController");


// =====  SETTINGS  =====

Route::put("/admissions/settings", "SettingsController@updateByShortname");
// Resource comes after custom routes!
Route::resource("/admissions/settings", "SettingsController");

// =====  LOGS =====

Route::get("/admissions/logs", "LogController@index");

Route::get("/admissions/test", "ApplicantController@test");

Route::get("/admissions/clear", function($e) {
    echo "cleared";
});

