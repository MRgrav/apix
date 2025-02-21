<?php

use App\Http\Controllers\Api\ClassController;
use App\Http\Controllers\CoursePlanController;
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
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\MicroController;
use App\Http\Controllers\TeacherClassController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AdminController;
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
Route::post('signup/instuctor', [AuthController::class, 'signUpInstructor']);
Route::post('signin', [AuthController::class, 'signIn']);
Route::post('verify', [AuthController::class, 'verifyPhoneOtp']);
Route::post('resend-otp', [AuthController::class, 'resendOtp']);
Route::post('reset', [AuthController::class, 'resetPassword']);
Route::get('purchase', [CourseController::class, 'getPurchasedCourses'])->middleware('auth:sanctum');
Route::get('/users', [AuthController::class, 'getAllUsers']); // Get all users
Route::delete('/users/{id}', [AuthController::class, 'deleteUser']); // Delete a user by ID
Route::get('/my-profile', [AuthController::class, 'myProfile'])->middleware('auth:sanctum');



//Route::get('/home', [HomeController::class, 'index']);



Route::get('/statuses', [StatusController::class, 'index']); // Get all statuses
Route::post('/statuses', [StatusController::class, 'store']); // Create a new status




Route::apiResource('workshops', WorkshopController::class);

Route::post('/users/{userId}/certificate/upload', [CertificateController::class, 'uploadCertificate']);

Route::get('/users/{userId}/certificate/download', [CertificateController::class, 'downloadCertificate']);

Route::get('/certificates/{userId}', [CertificateController::class, 'getCertificate']);



Route::post('/students', [StudentController::class, 'store']);
Route::get('/students', [StudentController::class, 'getAllStudents']);


Route::apiResource('classes', ClassController::class);


Route::prefix('courses')->group(function() {
    Route::get('/', [CourseController::class, 'index']);
    Route::get('/details/{id}', [CourseController::class, 'getCourseDetailsById']);
    Route::middleware('auth:sanctum')->get('/{id}', [CourseController::class, 'show']);
    Route::post('/', [CourseController::class, 'store']);
    Route::put('/{id}', [CourseController::class, 'update']);
    Route::delete('/{id}', [CourseController::class, 'destroy']);
    Route::post('/enroll/{courseId}', [CourseController::class, 'createOrder'])->middleware('auth:sanctum');
    Route::post('/content/{id}', [CourseController::class,'updateCourseContent']);
    Route::middleware('auth:sanctum')->post('/{id}/trial', [CourseController::class, 'startTrial']);
    Route::middleware('auth:sanctum')->post('/buy', [CourseController::class, 'confirmPayment']);
    Route::get('/renewal/{courseId}', [CourseController::class, 'renewal'])->middleware('auth:sanctum');
    Route::middleware('auth:sanctum')->post('/renew', [CourseController::class, 'confirmRenewalPayment']);
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
    Route::get('/v2', [HomeController::class, 'getHomePage'])->middleware('auth:sanctum');

});
Route::apiResource('materials', StudyMaterialController::class);


// Route::get('courses/{course_id}/videos', [VideoController::class, 'index']);
Route::get('courses/{group_id}/videos', [VideoController::class, 'index']);
Route::post('videos', [VideoController::class, 'store']);
Route::get('videos/{id}', [VideoController::class, 'show']);
Route::put('videos/{id}', [VideoController::class, 'update']);
Route::delete('videos/{id}', [VideoController::class, 'destroy']);

// Play a video (increments the play count)
Route::post('videos/{id}/play', [VideoController::class, 'play']);


Route::prefix('groups')->group(function () {
    Route::post('/{courseId}', [GroupController::class, 'addGroup'])->middleware('auth:sanctum');
    Route::post('/{groupId}/assign-user', [GroupController::class, 'assignUserToGroup']);
    Route::get('/{groupId}', [GroupController::class, 'getGroup']);
    Route::get('/{groupId}/study', [GroupController::class, 'getGroupStudent'])->middleware('auth:sanctum'); // Get a group by ID
    Route::delete('/{groupId}', [GroupController::class, 'deleteGroup']); // Delete a group by ID
    Route::get('/', [GroupController::class, 'getAllGroups']); // Get all groups
    Route::post('/{groupId}/live-class', [GroupController::class, 'updateLiveClass']);
    Route::get('/{groupId}/live-class', [GroupController::class, 'getLiveClass']);
    Route::get('/content/{groupId}', [HomeController::class, 'getPurchasedCourseDetails']);
});

// for student
Route::get('/my-groups', [GroupController::class, 'myGroups'])->middleware('auth:sanctum');

Route::get('/instructors/home', [InstructorController::class, 'home'])->middleware('auth:sanctum');
Route::post('/instructors/assign', [InstructorController::class, 'assignInstructor']);
Route::delete('/instructors/remove/{id}', [InstructorController::class, 'removeInstructor']);
Route::get('/courses/{courseId}/instructors', [InstructorController::class, 'getInstructorsByCourse']);
Route::get('/instructors', [InstructorController::class, 'getAllInstructors']);



//trails
Route::prefix('trials')->group(function () {
    Route::post('/{courseId}/start', [TrialController::class, 'startTrial']);
    Route::get('/{courseId}/users', [TrialController::class, 'getTrialUsers']);
    Route::post('/{courseId}/user/{userId}/set-link', [TrialController::class, 'setTrialLinkAndDescription']);
    Route::get('/{courseId}/user/{userId}/get-link', [TrialController::class, 'getTrialLinkAndDescription']);
//plans
    // Route::apiResource('course-plans', CoursePlanController::class);
    
    

});

// filtered plans only (is_nri: true/false)
// Route::get('/courses/available-plans', [CoursePlanController::class, 'getPlansByNriStatus']);
// accessible to everyone
Route::get('/courses/plans/{id}',[CoursePlanController::class, 'coursePlans']);

// class start by instructor : required = group_id
Route::post('/class/start', [TeacherClassController::class, 'store'])->middleware('auth:sanctum');


// micro apis
Route::get('/micro/courses', [MicroController::class, 'getCoursesName']);
Route::get('/micro/instructors', [MicroController::class, 'getInstructors']);
Route::get('/micro/students', [MicroController::class, 'getStudents']);
Route::get('/micro/users', [MicroController::class, 'getUsers']);
Route::get('/micro/enrollables/{courseId}', [MicroController::class, 'enrollableStudents']);
Route::get('/micro/groups/{courseId}', [MicroController::class, 'getGroupsByCourseId']);
Route::get('/micro/recorded-classes', [MicroController::class, 'getRecordedClasses'])->middleware('auth:sanctum');
Route::get('/micro/study-materials', [MicroController::class, 'getStudyMaterials'])->middleware('auth:sanctum');
Route::get('/micro/payment-history', [MicroController::class, 'getPaymentHistory'])->middleware('auth:sanctum');
Route::get('/micro/expiry/{courseId}', [MicroController::class, 'getCourseStatus'])->middleware('auth:sanctum');
Route::get('/micro/renewals', [MicroController::class, 'myRenewals'])->middleware('auth:sanctum');
Route::get('/micro/routines', [MicroController::class, 'myClassSchedules'])->middleware('auth:sanctum');
Route::prefix('attendance')->group(function(){
    Route::post('/', [AttendanceController::class, 'store'])->middleware('auth:sanctum');
});

Route::prefix('admin')->group(function() {
    Route::get('/groups/{groupId}', [AdminController::class, 'getGroupDetails']);
    Route::post('/payrolls', [AdminController::class, 'createPayroll'])->middleware('auth:sanctum');
    Route::get('/payrolls', [AdminController::class, 'getMyPayroll'])->middleware('auth:sanctum');
    Route::get('/groups', [GroupController::class, 'getInstructorsGroups'])->middleware('auth:sanctum');
    Route::put('/routine/{groupId}', [AdminController::class, 'createRoutine'])->middleware('auth:sanctum');
    Route::delete('/routine/{id}', [AdminController::class, 'deleteRoutine'])->middleware('auth:sanctum');;
    Route::get('/', [AdminController::class, 'getAdminDashboard'])->middleware('auth:sanctum');
});


