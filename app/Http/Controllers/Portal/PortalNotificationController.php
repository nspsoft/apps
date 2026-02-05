<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PortalNotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if (!$user->supplier_id) {
            abort(403, 'User is not linked to a supplier.');
        }

        $filter = $request->get('filter', 'all');
        
        $query = $user->notifications();
        
        if ($filter === 'unread') {
            $query->whereNull('read_at');
        }

        $notifications = $query->latest()->paginate(20);

        return Inertia::render('Portal/Notifications/Index', [
            'notifications' => $notifications,
            'unread_count' => $user->unreadNotifications()->count(),
            'filter' => $filter,
        ]);
    }

    public function markAsRead($id)
    {
        $user = auth()->user();

        if (!$user->supplier_id) {
            abort(403);
        }

        $notification = $user->notifications()->findOrFail($id);
        $notification->markAsRead();

        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllAsRead()
    {
        $user = auth()->user();

        if (!$user->supplier_id) {
            abort(403);
        }

        $user->unreadNotifications->markAsRead();

        return back()->with('success', 'All notifications marked as read.');
    }
}
