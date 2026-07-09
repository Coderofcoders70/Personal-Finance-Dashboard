<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use AuthorizesRequests;
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index(Request $request)
    {
        return NotificationResource::collection(
            $this->notificationService->all($request->user())
        );
    }

    public function markAsRead(Notification $notification)
    {
        $this->authorize('update', $notification);

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.',
            'notification' => new NotificationResource(
                $this->notificationService->markAsRead($notification)
            ),
        ]);
    }
    
    public function markAllAsRead(Request $request)
    {
        $this->notificationService->markAllAsRead(
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read.',
        ]);
    }

    public function destroy(Notification $notification)
    {
        $this->authorize('delete', $notification);

        $this->notificationService->delete($notification);

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted successfully.',
        ]);
    }
}
