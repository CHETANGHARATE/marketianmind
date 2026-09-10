<?php

use App\Http\Controllers\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Admin\CertificateController as AdminCertificateController;
use App\Http\Controllers\Admin\CourseAnalyticsController;
use App\Http\Controllers\Admin\CourseCategoryController as AdminCourseCategoryController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\CourseModuleController as AdminCourseModuleController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EnrollmentController as AdminEnrollmentController;
use App\Http\Controllers\Admin\LessonController as AdminLessonController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\StudentController as AdminStudentController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Public\AboutController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\CourseController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\RazorpayWebhookController;
use App\Http\Controllers\Student\CertificateController as StudentCertificateController;
use App\Http\Controllers\Student\CheckoutController as StudentCheckoutController;
use App\Http\Controllers\Student\CourseController as StudentCourseController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\EnrollmentController as StudentEnrollmentController;
use App\Http\Controllers\Student\LearningController as StudentLearningController;
use App\Http\Controllers\Student\LessonController as StudentLessonController;
use App\Http\Controllers\Student\OrderController as StudentOrderController;
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
| Public Webhook Routes (Signature Verified & CSRF Exempt)
|--------------------------------------------------------------------------
*/
Route::post('/webhooks/razorpay', [RazorpayWebhookController::class, 'handle'])->name('webhooks.razorpay');

/*
|--------------------------------------------------------------------------
| Payment Verification & Status Routes (Authenticated)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::post('/payments/razorpay/verify', [PaymentController::class, 'verify'])->name('payments.razorpay.verify');
    Route::get('/payment/success/{order}', [PaymentController::class, 'success'])->name('payment.success');
    Route::get('/payment/failed/{order}', [PaymentController::class, 'failed'])->name('payment.failed');
});

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

    // Student Paid Course Purchase & Checkout
    Route::post('/courses/{course}/purchase', [StudentCheckoutController::class, 'purchase'])->name('courses.purchase');
    Route::get('/courses/checkout/{order}', [StudentCheckoutController::class, 'showCheckout'])->name('courses.checkout');

    // Student Orders & Purchase History
    Route::get('/orders', [StudentOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [StudentOrderController::class, 'show'])->name('orders.show');

    // Student Course Certificates
    Route::get('/certificates/{certificate}', [StudentCertificateController::class, 'show'])->name('certificates.show');
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

    // Admin Order Management
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');

    // Admin Student Management
    Route::get('/students', [AdminStudentController::class, 'index'])->name('students.index');
    Route::get('/students/{student}', [AdminStudentController::class, 'show'])->name('students.show');

    // Admin Enrollment Management
    Route::get('/enrollments', [AdminEnrollmentController::class, 'index'])->name('enrollments.index');
    Route::get('/enrollments/{enrollment}', [AdminEnrollmentController::class, 'show'])->name('enrollments.show');

    // Admin Certificate Management
    Route::get('/certificates', [AdminCertificateController::class, 'index'])->name('certificates.index');
    Route::get('/certificates/{certificate}', [AdminCertificateController::class, 'show'])->name('certificates.show');

    // Admin Course Analytics
    Route::get('/analytics/courses', [CourseAnalyticsController::class, 'index'])->name('analytics.courses');
    Route::get('/analytics/courses/{course}', [CourseAnalyticsController::class, 'show'])->name('analytics.courses.show');

    // Admin Activity & Audit Logs
    Route::get('/audit-logs', [AdminAuditLogController::class, 'index'])->name('audit_logs.index');
    Route::get('/audit-logs/{auditLog}', [AdminAuditLogController::class, 'show'])->name('audit_logs.show');
});


