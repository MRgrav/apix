<?php

use App\Http\Controllers\Api\ClassController;
use App\Http\Controllers\StudyMaterialController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use \App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\WorkshopController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\InstructorController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\CourseCategoryController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\TrialController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


Route::post('signup', [AuthController::class, 'signUp']);
Route::post('signin', [AuthController::class, 'signIn']);
Route::post('verify', [AuthController::class, 'verifyPhoneOtp']);
Route::post('resend-otp', [AuthController::class, 'resendOtp']);
Route::post('reset', [AuthController::class, 'resetPassword']);
Route::get('purchase', [CourseController::class, 'getPurchasedCourses'])->middleware('auth:sanctum');



//Route::get('/home', [HomeController::class, 'index']);





Route::apiResource('workshops', WorkshopController::class);

Route::post('/users/{userId}/certificate/upload', [CertificateController::class, 'uploadCertificate']);

Route::get('/users/{userId}/certificate/download', [CertificateController::class, 'downloadCertificate']);

Route::get('/certificates/{userId}', [CertificateController::class, 'getCertificate']);



Route::apiResource('classes', ClassController::class);


Route::prefix('courses')->group(function() {
    Route::get('/', [CourseController::class, 'index']);
    Route::middleware('auth:sanctum')->get('/{id}', [CourseController::class, 'show']);
    Route::post('/', [CourseController::class, 'store']);
    Route::put('/{id}', [CourseController::class, 'update']);
    Route::delete('/{id}', [CourseController::class, 'destroy']);
    Route::post('/content/{id}', [CourseController::class,'updateCourseContent']);
    Route::middleware('auth:sanctum')->post('/{id}/trial', [CourseController::class, 'startTrial']);
    Route::middleware('auth:sanctum')->post('/buy', [CourseController::class, 'confirmPayment']);

});

Route::prefix('categories')->group(function() {
    Route::get('/', [CourseCategoryController::class, 'index']);
    Route::get('/{id}', [CourseCategoryController::class, 'show']);
    Route::post('/', [CourseCategoryController::class, 'store']);
    Route::put('/{id}', [CourseCategoryController::class, 'update']);
    Route::delete('/{id}', [CourseCategoryController::class, 'destroy']);

});

Route::prefix('sections')->group(function() {
    Route::get('/', [SectionController::class, 'index']);
    Route::get('/{id}', [SectionController::class, 'show']);
    Route::post('/', [SectionController::class, 'store']);
    Route::put('/{id}', [SectionController::class, 'update']);
    Route::delete('/{id}', [SectionController::class, 'destroy']);
});

Route::prefix('home')->group(function() {
    Route::get('/', [HomeController::class, 'index']);
    Route::get('/users/purchase', [HomeController::class, 'getPurchasedCoursesWithGroupVideos'])->middleware('auth:sanctum');


});
Route::apiResource('materials', StudyMaterialController::class);


Route::get('courses/{course_id}/videos', [VideoController::class, 'index']);
Route::post('videos', [VideoController::class, 'store']);
Route::get('videos/{id}', [VideoController::class, 'show']);
Route::put('videos/{id}', [VideoController::class, 'update']);
Route::delete('videos/{id}', [VideoController::class, 'destroy']);

// Play a video (increments the play count)
Route::post('videos/{id}/play', [VideoController::class, 'play']);



Route::post('/groups/{courseId}', [GroupController::class, 'addGroup'])->middleware('auth:sanctum');
Route::post('/groups/{groupId}/assign-user', [GroupController::class, 'assignUserToGroup']);




Route::post('/instructors/assign', [InstructorController::class, 'assignInstructor']);
Route::delete('/instructors/remove/{id}', [InstructorController::class, 'removeInstructor']);
Route::get('/courses/{courseId}/instructors', [InstructorController::class, 'getInstructorsByCourse']);




Route::prefix('trials')->group(function () {
    Route::post('/{courseId}/start', [TrialController::class, 'startTrial']);
    Route::get('/{courseId}/users', [TrialController::class, 'getTrialUsers']);
    Route::post('/{courseId}/user/{userId}/set-link', [TrialController::class, 'setTrialLinkAndDescription']);
    Route::get('/{courseId}/user/{userId}/get-link', [TrialController::class, 'getTrialLinkAndDescription']);
});
