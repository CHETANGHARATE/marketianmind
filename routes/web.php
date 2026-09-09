<?php

use App\Http\Controllers\Admin\CourseCategoryController as AdminCourseCategoryController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\CourseModuleController as AdminCourseModuleController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\LessonController as AdminLessonController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Public\AboutController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\CourseController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Student\CourseController as StudentCourseController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\EnrollmentController as StudentEnrollmentController;
use App\Http\Controllers\Student\LearningController as StudentLearningController;
use App\Http\Controllers\Student\LessonController as StudentLessonController;
use App\Http\Controllers\Student\ProfileController as StudentProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [AboutController::class, 'index'])->name('about');
Route::get('/courses', [CourseController::class, 'index'])->name('courses');
Route::get('/courses/digital-marketing-for-business-owners', [CourseController::class, 'show'])->name('course.details');
Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');
Route::get('/contact', [ContactController::class, 'index'])->name('contact');

/*
|--------------------------------------------------------------------------
| Guest Authentication Routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

/*
|--------------------------------------------------------------------------
| Authenticated Session Routes
|--------------------------------------------------------------------------
*/
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Student Portal Routes (Authenticated & Student Role Protected)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
    Route::get('/my-learning', [StudentLearningController::class, 'myLearning'])->name('my-learning');
    Route::get('/courses', [StudentCourseController::class, 'index'])->name('courses');
    Route::get('/courses/index', [StudentCourseController::class, 'index'])->name('courses.index');
    Route::get('/progress', [StudentLearningController::class, 'progress'])->name('progress');
    Route::get('/profile', [StudentProfileController::class, 'show'])->name('profile');
    Route::put('/profile', [StudentProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [StudentProfileController::class, 'updatePassword'])->name('profile.password.update');

    // Student Course Access, Enrollment & Learning Player
    Route::post('/courses/{course}/enroll', [StudentEnrollmentController::class, 'store'])->name('courses.enroll');
    Route::get('/courses/{course}', [StudentCourseController::class, 'show'])->name('courses.show');
    Route::get('/courses/{course}/lessons/{lesson}', [StudentLessonController::class, 'show'])->name('courses.lessons.show');
    Route::post('/courses/{course}/lessons/{lesson}/complete', [StudentLessonController::class, 'complete'])->name('courses.lessons.complete');
    Route::get('/courses/{course}/lessons/{lesson}/pdf', [StudentLessonController::class, 'downloadPdf'])->name('courses.lessons.pdf');
});

/*
|--------------------------------------------------------------------------
| Admin Portal Routes (Authenticated & Admin Role Protected)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::resource('courses', AdminCourseController::class);
    Route::resource('course-categories', AdminCourseCategoryController::class)
        ->parameters(['course-categories' => 'category'])
        ->names('categories')
        ->except(['create', 'show', 'edit']);

    // Course Curriculum: Modules & Lessons
    Route::resource('courses.modules', AdminCourseModuleController::class)
        ->names('courses.modules')
        ->except(['show']);

    Route::resource('courses.modules.lessons', AdminLessonController::class)
        ->names('courses.modules.lessons')
        ->except(['show']);
});


