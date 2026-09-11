<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Display the authenticated student's notifications.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $filter = $request->query('filter', 'all');

        $query = $user->notifications();

        if ($filter === 'unread') {
            $query->whereNull('read_at');
        }

        $notifications = $query->latest()->paginate(15)->withQueryString();
        $unreadCount = $user->unreadNotifications()->count();
        $totalCount = $user->notifications()->count();

        return view('student.notifications.index', compact(
            'notifications',
            'filter',
            'unreadCount',
            'totalCount'
        ));
    }

    /**
     * Mark a single notification as read and optionally redirect to action URL.
     */
    public function markAsRead(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);

        if (! $notification->read()) {
            $notification->markAsRead();
        }

        $targetUrl = $request->input('target_url', $notification->data['action_url'] ?? null);

        if ($targetUrl && is_string($targetUrl)) {
            return redirect()->to($targetUrl);
        }

        return back()->with('status', 'Notification marked as read.');
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('status', 'All notifications marked as read.');
    }

    /**
     * Delete an individual notification.
     */
    public function destroy(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->delete();

        return back()->with('status', 'Notification removed.');
    }

    /**
     * Clear all read notifications for the authenticated student.
     */
    public function clearRead(Request $request): RedirectResponse
    {
        $deleted = $request->user()->readNotifications()->delete();

        return back()->with('status', 'All read notifications have been cleared.');
    }
}