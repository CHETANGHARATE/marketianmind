<?php

use App\Http\Controllers\Admin\AnnouncementController as AdminAnnouncementController;
use App\Http\Controllers\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Admin\CourseReviewController as AdminCourseReviewController;
use App\Http\Controllers\Admin\CertificateController as AdminCertificateController;
use App\Http\Controllers\Admin\CourseAnalyticsController;
use App\Http\Controllers\Admin\CourseCategoryController as AdminCourseCategoryController;
use App\Http\Controllers\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Admin\CourseModuleController as AdminCourseModuleController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EnrollmentController as AdminEnrollmentController;
use App\Http\Controllers\Admin\InstructorController as AdminInstructorController;
use App\Http\Controllers\Admin\LeadController as AdminLeadController;
use App\Http\Controllers\Admin\LessonController as AdminLessonController;
use App\Http\Controllers\Admin\LessonResourceController as AdminLessonResourceController;
use App\Http\Controllers\Admin\MailController as AdminMailController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ReferralController as AdminReferralController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\StudentController as AdminStudentController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Public\AboutController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\CourseController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\LeadCaptureController;
use App\Http\Controllers\Public\ReferralCaptureController;
use App\Http\Controllers\RazorpayWebhookController;
use App\Http\Controllers\Student\CertificateController as StudentCertificateController;
use App\Http\Controllers\Student\CheckoutController as StudentCheckoutController;
use App\Http\Controllers\Student\CourseReviewController as StudentCourseReviewController;
use App\Http\Controllers\Student\CourseController as StudentCourseController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\EnrollmentController as StudentEnrollmentController;
use App\Http\Controllers\Student\LearningController as StudentLearningController;
use App\Http\Controllers\Student\LessonController as StudentLessonController;
use App\Http\Controllers\Student\NotificationController as StudentNotificationController;
use App\Http\Controllers\Student\OrderController as StudentOrderController;
use App\Http\Controllers\Student\ProfileController as StudentProfileController;
use App\Http\Controllers\Student\ReferralController as StudentReferralController;
use App\Http\Controllers\Student\WishlistController as StudentWishlistController;
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
Route::post('/contact', [ContactController::class, 'submit'])->middleware('throttle:6,1')->name('contact.submit');
Route::post('/leads', [LeadCaptureController::class, 'store'])->middleware('throttle:6,1')->name('leads.store');
Route::get('/ref/{code}', [ReferralCaptureController::class, 'capture'])->name('referral.capture');

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
    Route::get('/courses/{course}/lessons/{lesson}/resources/{resource}/download', [StudentLessonController::class, 'downloadResource'])->name('courses.lessons.resources.download');

    // Student Paid Course Purchase & Checkout
    Route::post('/courses/{course}/purchase', [StudentCheckoutController::class, 'purchase'])->name('courses.purchase');
    Route::get('/courses/checkout/{order}', [StudentCheckoutController::class, 'showCheckout'])->name('courses.checkout');
    Route::post('/courses/checkout/{order}/apply-coupon', [StudentCheckoutController::class, 'applyCoupon'])->name('courses.checkout.apply-coupon');
    Route::post('/courses/checkout/{order}/remove-coupon', [StudentCheckoutController::class, 'removeCoupon'])->name('courses.checkout.remove-coupon');
    Route::post('/courses/checkout/{order}/complete-free', [StudentCheckoutController::class, 'completeFree'])->name('courses.checkout.complete-free');

    // Student Orders & Purchase History
    Route::get('/orders', [StudentOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [StudentOrderController::class, 'show'])->name('orders.show');

    // Student Course Certificates
    Route::get('/certificates/{certificate}', [StudentCertificateController::class, 'show'])->name('certificates.show');

    // Student Course Reviews & Ratings
    Route::post('/courses/{course}/reviews', [StudentCourseReviewController::class, 'store'])->name('courses.reviews.store');
    Route::delete('/courses/{course}/reviews/{review}', [StudentCourseReviewController::class, 'destroy'])->name('courses.reviews.destroy');

    // Student Wishlist / Saved Courses
    Route::get('/wishlist', [StudentWishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist/{course}', [StudentWishlistController::class, 'store'])->name('wishlist.store');
    Route::delete('/wishlist/{course}', [StudentWishlistController::class, 'destroy'])->name('wishlist.destroy');
    Route::post('/wishlist/{course}/toggle', [StudentWishlistController::class, 'toggle'])->name('wishlist.toggle');

    // Student In-App Notifications
    Route::get('/notifications', [StudentNotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{id}/read', [StudentNotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [StudentNotificationController::class, 'markAllRead'])->name('notifications.markAllRead');
    Route::delete('/notifications/clear-read', [StudentNotificationController::class, 'clearRead'])->name('notifications.clearRead');
    Route::delete('/notifications/{id}', [StudentNotificationController::class, 'destroy'])->name('notifications.destroy');

    // Student Referral Program
    Route::get('/referrals', [StudentReferralController::class, 'index'])->name('referrals.index');
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

    // Admin Instructors Management
    Route::resource('instructors', AdminInstructorController::class)->except(['show']);
    Route::patch('/instructors/{instructor}/toggle', [AdminInstructorController::class, 'toggleStatus'])->name('instructors.toggle');

    // Course Curriculum: Modules & Lessons
    Route::resource('courses.modules', AdminCourseModuleController::class)
        ->names('courses.modules')
        ->except(['show']);

    Route::resource('courses.modules.lessons', AdminLessonController::class)
        ->names('courses.modules.lessons')
        ->except(['show']);

    // Admin Lesson Resources & Downloads
    Route::get('/courses/{course}/modules/{module}/lessons/{lesson}/resources', [AdminLessonResourceController::class, 'index'])->name('courses.modules.lessons.resources.index');
    Route::post('/courses/{course}/modules/{module}/lessons/{lesson}/resources', [AdminLessonResourceController::class, 'store'])->name('courses.modules.lessons.resources.store');
    Route::delete('/courses/{course}/modules/{module}/lessons/{lesson}/resources/{resource}', [AdminLessonResourceController::class, 'destroy'])->name('courses.modules.lessons.resources.destroy');

    // Admin Order Management
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');

    // Admin Coupons & Promotions
    Route::resource('coupons', AdminCouponController::class)->except(['show']);
    Route::patch('/coupons/{coupon}/toggle', [AdminCouponController::class, 'toggleStatus'])->name('coupons.toggle');

    // Admin Course Review Moderation
    Route::get('/reviews', [AdminCourseReviewController::class, 'index'])->name('reviews.index');
    Route::patch('/reviews/{review}/toggle', [AdminCourseReviewController::class, 'toggleStatus'])->name('reviews.toggle');
    Route::delete('/reviews/{review}', [AdminCourseReviewController::class, 'destroy'])->name('reviews.destroy');

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

    // Admin Platform Announcements
    Route::get('/announcements', [AdminAnnouncementController::class, 'index'])->name('announcements.index');
    Route::post('/announcements', [AdminAnnouncementController::class, 'store'])->name('announcements.store');

    // Admin Mail & SMTP Configuration
    Route::get('/mail', [AdminMailController::class, 'index'])->name('mail.index');
    Route::post('/mail/test', [AdminMailController::class, 'sendTest'])->name('mail.test');

    // Admin Leads & Inquiries
    Route::resource('leads', AdminLeadController::class)->only(['index', 'show', 'update', 'destroy']);

    // Admin Referrals Management
    Route::get('/referrals', [AdminReferralController::class, 'index'])->name('referrals.index');

    // Admin Business Reports & Analytics
    Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/sales', [AdminReportController::class, 'sales'])->name('reports.sales');
    Route::get('/reports/courses', [AdminReportController::class, 'courses'])->name('reports.courses');
    Route::get('/reports/enrollments', [AdminReportController::class, 'enrollments'])->name('reports.enrollments');
    Route::get('/reports/coupons', [AdminReportController::class, 'coupons'])->name('reports.coupons');
    Route::get('/reports/export/sales', [AdminReportController::class, 'exportSales'])->name('reports.export.sales');
    Route::get('/reports/export/courses', [AdminReportController::class, 'exportCourses'])->name('reports.export.courses');
    Route::get('/reports/export/enrollments', [AdminReportController::class, 'exportEnrollments'])->name('reports.export.enrollments');
});


