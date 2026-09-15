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

        $validityDays = $course->getAccessValidityDays();
        $now = now();
        $startsAt = $now;
        $expiresAt = $startsAt->copy()->addDays($validityDays);

        if ($existingEnrollment) {
            $existingEnrollment->update([
                'status' => EnrollmentStatus::ACTIVE,
                'starts_at' => $startsAt,
                'expires_at' => $expiresAt,
                'enrolled_at' => $now,
            ]);

            \App\Models\CourseAccessPeriod::create([
                'enrollment_id' => $existingEnrollment->id,
                'period_type' => 'renewal',
                'starts_at' => $startsAt,
                'expires_at' => $expiresAt,
            ]);
        } else {
            $enrollment = Enrollment::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'status' => EnrollmentStatus::ACTIVE,
                'starts_at' => $startsAt,
                'expires_at' => $expiresAt,
                'enrolled_at' => $now,
            ]);

            \App\Models\CourseAccessPeriod::create([
                'enrollment_id' => $enrollment->id,
                'period_type' => 'initial',
                'starts_at' => $startsAt,
                'expires_at' => $expiresAt,
            ]);
        }

        app(\App\Services\EngagementService::class)->handleEnrollment($user, $course, false);

        app(\App\Services\MarketingAutomationService::class)->dispatchTrigger(
            \App\Enums\AutomationTrigger::COURSE_ENROLLED,
            $user,
            ['course_id' => $course->id],
            'enrollment_' . $user->id . '_' . $course->id
        );

        return redirect()
            ->route('student.courses.show', $course)
            ->with('status', 'Congratulations! You have successfully enrolled in this course.');
    }
}