<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $notifications = $user->notifications()
            ->when($request->has('unread'), function ($query) {
                $query->whereNull('read_at');
            })
            ->orderBy('created_at', 'desc')
            ->limit($request->input('limit', 20))
            ->get();

        return response()->json($notifications);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $notification = $user->notifications()->find($id);
        
        if (!$notification) {
            return response()->json(['error' => 'Notification not found'], 404);
        }
        
        $notification->markAsRead();
        
        return response()->json(['message' => 'Notification marked as read']);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->unreadNotifications->markAsRead();
        
        return response()->json(['message' => 'All notifications marked as read']);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();
        $count = $user->unreadNotifications()->count();
        
        return response()->json(['count' => $count]);
    }
}
