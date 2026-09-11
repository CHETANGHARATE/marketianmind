<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AdminAnnouncementNotification;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    /**
     * Display announcements dashboard and creation form.
     */
    public function index(): View
    {
        $studentsCount = User::where('role', UserRole::STUDENT->value)->count();
        $recentStudents = User::where('role', UserRole::STUDENT->value)->latest()->take(20)->get(['id', 'name', 'email']);

        return view('admin.announcements.index', compact('studentsCount', 'recentStudents'));
    }

    /**
     * Broadcast an announcement notification.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:1000'],
            'action_url' => ['nullable', 'url', 'max:255'],
            'target' => ['required', 'in:all,specific'],
            'user_id' => ['nullable', 'required_if:target,specific', 'exists:users,id'],
        ]);

        $notification = new AdminAnnouncementNotification(
            $validated['title'],
            $validated['message'],
            $validated['action_url'] ?? null
        );

        $recipientCount = 0;

        if ($validated['target'] === 'all') {
            User::where('role', UserRole::STUDENT->value)->chunk(100, function ($students) use ($notification, &$recipientCount) {
                foreach ($students as $student) {
                    $student->notify(clone $notification);
                    $recipientCount++;
                }
            });

            AuditLogger::log(
                'announcement.broadcast',
                'Announcement',
                "Broadcast announcement '{$validated['title']}' to {$recipientCount} students."
            );

            $message = "Announcement successfully broadcast to {$recipientCount} students.";
        } else {
            $student = User::findOrFail($validated['user_id']);
            $student->notify($notification);

            AuditLogger::log(
                'announcement.targeted',
                $student,
                "Sent announcement '{$validated['title']}' to student #{$student->id} ({$student->email})."
            );

            $message = "Announcement successfully sent to {$student->name}.";
        }

        return redirect()->route('admin.announcements.index')->with('status', $message);
    }
}