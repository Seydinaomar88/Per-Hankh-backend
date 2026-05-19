<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

use App\Models\Notification;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * LIST USER NOTIFICATIONS
     */
    public function index(
        Request $request
    ): JsonResponse {

        $notifications = Notification::query()

            ->where(
                'user_id',
                Auth::id()
            )

            /**
             * FILTER TYPE
             */
            ->when(
                $request->type,
                function ($query, $type) {

                    $query->where(
                        'type',
                        $type
                    );
                }
            )

            /**
             * FILTER READ STATUS
             */
            ->when(
                !is_null($request->is_read),
                function ($query) use ($request) {

                    $query->where(
                        'is_read',
                        filter_var(
                            $request->is_read,
                            FILTER_VALIDATE_BOOLEAN
                        )
                    );
                }
            )

            ->latest()

            ->get();

        return response()->json([

            'status' => true,

            'notifications' => $notifications
        ]);
    }

    /**
     * UNREAD NOTIFICATIONS
     */
    public function unread(): JsonResponse
    {
        $notifications = Notification::where(
            'user_id',
            Auth::id()
        )

        ->where(
            'is_read',
            false
        )

        ->latest()

        ->get();

        return response()->json([

            'status' => true,

            'notifications' => $notifications
        ]);
    }

    /**
     * UNREAD COUNT
     */
    public function unreadCount(): JsonResponse
    {
        $count = Notification::where(
            'user_id',
            Auth::id()
        )

        ->where(
            'is_read',
            false
        )

        ->count();

        return response()->json([

            'status' => true,

            'count' => $count
        ]);
    }

    /**
     * MARK AS READ
     */
    public function markAsRead(
        Notification $notification
    ): JsonResponse {

        /**
         * SECURITY
         */
        if (
            $notification->user_id !== Auth::id()
        ) {

            return response()->json([

                'status' => false,

                'message' => 'Unauthorized'

            ], 403);
        }

        $notification->update([

            'is_read' => true
        ]);

        return response()->json([

            'status' => true,

            'message' =>
                'Notification marked as read',

            'notification' => $notification
        ]);
    }

    /**
     * MARK ALL AS READ
     */
    public function markAllAsRead(): JsonResponse
    {
        Notification::where(
            'user_id',
            Auth::id()
        )

        ->where(
            'is_read',
            false
        )

        ->update([

            'is_read' => true
        ]);

        return response()->json([

            'status' => true,

            'message' =>
                'All notifications marked as read'
        ]);
    }

    /**
     * DELETE NOTIFICATION
     */
    public function destroy(
        Notification $notification
    ): JsonResponse {

        /**
         * SECURITY
         */
        if (
            $notification->user_id !== Auth::id()
        ) {

            return response()->json([

                'status' => false,

                'message' => 'Unauthorized'

            ], 403);
        }

        $notification->delete();

        return response()->json([

            'status' => true,

            'message' =>
                'Notification deleted successfully'
        ]);
    }
}