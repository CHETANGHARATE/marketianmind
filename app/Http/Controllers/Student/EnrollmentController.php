<?php

namespace App\Http\Controllers\Student;

use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    /**
     * Enroll the authenticated student in a free course.
     */
    public function store(Request $request, Course $course): RedirectResponse
    {
        $user = $request->user();

        if (! $course->isPublished()) {
            abort(404, 'Course not found or unavailable.');
        }

        if (! $course->is_free) {
            return back()->with('error', 'This is a paid course. Online purchase functionality is coming soon.');
        }

        // Check if student is already actively enrolled
        $existingEnrollment = Enrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if ($existingEnrollment && $existingEnrollment->isActive()) {
            return redirect()
                ->route('student.courses.show', $course)
                ->with('status', 'You are already enrolled in this course.');
        }

        if ($existingEnrollment && $existingEnrollment->isCompleted()) {
            return redirect()
                ->route('student.courses.show', $course)
                ->with('status', 'You have already completed this course.');
        }

        if ($existingEnrollment) {
            $existingEnrollment->update([
                'status' => EnrollmentStatus::ACTIVE,
                'enrolled_at' => now(),
            ]);
        } else {
            Enrollment::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'status' => EnrollmentStatus::ACTIVE,
                'enrolled_at' => now(),
            ]);
        }

        $user->notify(new \App\Notifications\CourseEnrollmentNotification($course));
        app(\App\Services\TransactionalMailService::class)->sendCourseEnrollment($user, $course);

        return redirect()
            ->route('student.courses.show', $course)
            ->with('status', 'Congratulations! You have successfully enrolled in this course.');
    }
}