<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class NotificationController
 *
 * @package App\Http\Controllers
 *
 * @author Muibi Azeez Abolade
 * @email muibi.azeezabolade@gmail.com
 * @since 2024-10-24
 * @version 1.0
 *
 * @tags Notification
 *
 * ${Description}
 */
class NotificationController extends Controller
{
    /**
     * Fetch all notifications for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $notifications = $user->notifications()->paginate($request->per_page ?? 15);

        return response()->success([
            'notifications' => NotificationResource::collection($notifications),
            'unread_count' => $user->unreadNotifications()->count(),
        ], 'Notifications retrieved successfully');
    }

    /**
     * Mark a specific notification as read.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->success(null, 'Notification marked as read');
    }

    /**
     * Mark all notifications as read for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->success(null, 'All notifications marked as read');
    }

    /**
     * Delete a specific notification.
     *
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->delete();

        return response()->success(null, 'Notification deleted');
    }

    /**
     * Get the count of unread notifications for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $request->user()->unreadNotifications()->count();

        return response()->success(['unread_count' => $count]);
    }
}
