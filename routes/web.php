<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
// web.php

// use App\Http\Controllers\Panel\Instructor\CourseController;

// Route::prefix('courses')->group(function () {
//     Route::get('/', [CourseController::class, 'courses'])->name('instructor.courses');
//     Route::get('/add', [CourseController::class, 'addCourse'])->name('instructor.courses.add');
//     Route::post('/store', [CourseController::class, 'storeCourse'])->name('instructor.courses.store');
//     Route::get('/edit/{slug}', [CourseController::class, 'editCourse'])->name('instructor.courses.edit');
//     Route::post('/update/{slug}', [CourseController::class, 'updateCourse'])->name('instructor.courses.update');
//     Route::delete('/delete/{id}', [CourseController::class, 'deleteCourse'])->name('instructor.courses.delete');
// });
