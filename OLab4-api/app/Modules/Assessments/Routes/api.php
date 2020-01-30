<?php

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

Route::get('/assessments/tasks', 'AssessmentsController@tasks');

Route::get('/assessments/tasks/{task_id}', 'AssessmentsController@singleAssessment');

Route::put('/assessments/tasks/{task_id}/remove', 'AssessmentsController@deleteTask');

Route::get('/assessments/tasks/{task_id}/targets', 'AssessmentsController@targetsForTask');

Route::get('/assessments/tasks/{task_id}/targets/{target_id}', 'AssessmentsController@singleTarget');

Route::put('/assessments/tasks/{task_id}/targets/{target_id}/delete', 'AssessmentsController@removeTarget');

Route::post('/assessments/tasks/{task_id}/targets/{target_id}/save', 'AssessmentsController@saveTask');

Route::post('/assessments/trigger', 'AssessmentsController@triggerAssessment');

Route::get('/assessments/courses', 'AssessmentsController@getCourses');

Route::get("/assessments/users/{user_id}/courses", "AssessmentsController@getUserCourse");
Route::get("/assessments/users/courses", "AssessmentsController@getUserCourse");
Route::get("/assessments/courses/epas", "AssessmentsController@getCourseEPAs");
Route::get("/assessments/courses/{course_id}/epas", "AssessmentsController@getCourseEPAs");

Route::get("/assessments/assessment-tools", "AssessmentsController@getAssessmentTools");

Route::get("/assessments/assessment-methods", "AssessmentsController@getAssessmentMethods");

Route::get("/assessments/users/{user_id}/pin", "AssessmentsController@getUserPIN");
Route::get("/assessments/users/pin", "AssessmentsController@getUserPIN");

Route::post("/assessments/users/verify-pin", "AssessmentsController@verifyPin");
Route::post("/assessments/tasks/save-responses", "AssessmentsController@saveResponses");

Route::match(["post", "put"], "/assessments/tasks/{task_id}/targets/{target_id}/save", "AssessmentsController@saveWithPost");