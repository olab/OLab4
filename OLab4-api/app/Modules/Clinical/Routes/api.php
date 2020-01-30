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


Route::get('clinical/curriculum_period', 'ClinicalController@curriculumPeriod');
Route::get('clinical/courses', 'ClinicalController@courses');
Route::get('clinical/my_learners', 'ClinicalController@myLearners');
Route::get('clinical/leave_types', 'LeaveTrackingController@leaveTypes');
Route::get('clinical/slot-types', 'ClinicalController@slotTypes');
Route::get('clinical/block-types', 'ClinicalController@blockTypes');
Route::get('clinical/users', 'ClinicalController@getUsersByGroup');
Route::apiResource('clinical/rotation-schedule-slot', 'RotationScheduleSlotController');
Route::apiResource('clinical/leave_tracking', 'LeaveTrackingController');
Route::apiResource('clinical/draft-rotation-schedule', 'DraftRotationScheduleController');
Route::apiResource('clinical/rotation-schedule', 'RotationScheduleController');
Route::get('clinical/rotation-schedule/shift-blocks/{schedule_id}', 'RotationScheduleController@shiftBlocks');
Route::get('clinical/rotation-schedule/mapping-url/{schedule_id}', 'RotationScheduleController@mappingUrl');
Route::get('clinical/rotation-schedule-path', 'ClinicalController@RotationSchedulePath');
Route::get('clinical/rotation-schedule-templates/{cperiod_id}', 'RotationScheduleController@templates');
Route::post('clinical/draft-rotation-schedule/copy', 'DraftRotationScheduleController@copyExistingRotation');
Route::post('clinical/draft-rotation-schedule/export', 'DraftRotationScheduleController@export');
Route::put('clinical/draft-rotation-schedule/change-status/{id}', 'DraftRotationScheduleController@changeStatus');
Route::post('clinical/rotation-schedule/import', 'RotationScheduleController@ImportRotationStructure');

/**
 * API for learner assignment to schedules/slots
 */
Route::apiResource('clinical/schedules/{schedule_id}/audience', 'ScheduleAudienceController');
Route::get('clinical/schedules/{schedule_id}/template', 'ScheduleAudienceController@template');
Route::get('clinical/schedules/{schedule_id}/learners', 'ScheduleAudienceController@learners');
Route::get('clinical/schedules/{schedule_id}/rotations', 'ScheduleRotationController@index');
Route::get('clinical/schedules/{schedule_id}/rotations/{rotation_id}/blocks', 'ScheduleRotationController@blocks');
Route::get('clinical/schedules/{schedule_id}/slots', 'ScheduleAudienceController@slots');