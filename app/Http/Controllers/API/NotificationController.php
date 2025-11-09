<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Traits\ApiResponse;
use Carbon\Carbon;

class NotificationController extends Controller
{
    use ApiResponse;

    /**
     * Get all notifications for logged-in user with pagination
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $notifications = $user->notifications()->latest()->paginate(20);

        $notifications->getCollection()->transform(function ($notification) {
            return [
                'data' => $notification->data,
                'read_at' => $notification->read_at,
                'time_ago' => Carbon::parse($notification->created_at)->diffForHumans(),
            ];
        });

        return $this->success($notifications, 'Notifications retrieved successfully', 200);
    }

    /**
     * Mark a single notification as read
     */
    public function markAsRead($id)
    {
        $user = Auth::user();

        $notification = $user->notifications()->where('id', $id)->first();

        if (!$notification) {
            return $this->error([], 'Notification not found', 404);
        }

        $notification->markAsRead();

        // Return only `data` and `read_at`
        return $this->success([
            'data' => $notification->data,
            'read_at' => $notification->read_at,
        ], 'Notification marked as read', 200);
    }


    /**
     * Clear all notifications for logged-in user
     */
    public function clearAll()
    {
        $user = Auth::user();

        $user->notifications()->delete();

        return $this->success([], 'All notifications cleared', 200);
    }
}
