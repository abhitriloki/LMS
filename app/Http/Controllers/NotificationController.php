<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Display all notifications
     */
    public function index(Request $request)
    {
        $notifications = $this->notificationService->getAll(Auth::user());
        $unreadCount = $this->notificationService->getUnreadCount(Auth::user());

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    /**
     * Get unread notifications (for AJAX)
     */
    public function unread(Request $request)
    {
        $notifications = $this->notificationService->getUnread(Auth::user());
        $unreadCount = $this->notificationService->getUnreadCount(Auth::user());

        return response()->json([
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(Notification $notification)
    {
        $this->authorize('view', $notification);
        
        $this->notificationService->markAsRead($notification);

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Notification marked as read');
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        $this->notificationService->markAllAsRead(Auth::user());

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'All notifications marked as read');
    }

    /**
     * Delete a notification
     */
    public function destroy(Notification $notification)
    {
        $this->authorize('delete', $notification);
        
        $this->notificationService->delete($notification);

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Notification deleted');
    }
}
