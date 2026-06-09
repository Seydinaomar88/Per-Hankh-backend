<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Pusher\Pusher as PusherClient;

class NotificationController extends Controller
{
    /**
     * LISTE DES NOTIFICATIONS DE L'UTILISATEUR
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = Notification::query()
            ->where('user_id', Auth::id())
            ->when($request->type, function ($query, $type) {
                $query->where('type', $type);
            })
            ->when(!is_null($request->is_read), function ($query) use ($request) {
                $query->where('is_read', filter_var($request->is_read, FILTER_VALIDATE_BOOLEAN));
            })
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'notifications' => $notifications
        ]);
    }

    /**
     * NOTIFICATIONS NON LUS
     */
    public function unread(): JsonResponse
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'notifications' => $notifications
        ]);
    }

    /**
     * COMPTEUR DES NOTIFICATIONS NON LUS
     */
    public function unreadCount(): JsonResponse
    {
        $count = Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->count();

        return response()->json([
            'status' => true,
            'count' => $count
        ]);
    }

    /**
     * MARQUER UNE NOTIFICATION COMME LUE
     */
    public function markAsRead(Notification $notification): JsonResponse
    {
        if ($notification->user_id !== Auth::id()) {
            return response()->json([
                'status' => false,
                'message' => 'Non autorisé'
            ], 403);
        }

        $notification->update(['is_read' => true]);

        // Diffuser la mise à jour via WebSocket
        $this->broadcastNotificationRead($notification);

        return response()->json([
            'status' => true,
            'message' => 'Notification marquée comme lue',
            'notification' => $notification
        ]);
    }

    /**
     * MARQUER TOUTES LES NOTIFICATIONS COMME LUES
     */
    public function markAllAsRead(): JsonResponse
    {
        Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'status' => true,
            'message' => 'Toutes les notifications ont été marquées comme lues'
        ]);
    }

    /**
     * SUPPRIMER UNE NOTIFICATION
     */
    public function destroy(Notification $notification): JsonResponse
    {
        if ($notification->user_id !== Auth::id()) {
            return response()->json([
                'status' => false,
                'message' => 'Non autorisé'
            ], 403);
        }

        $notification->delete();

        return response()->json([
            'status' => true,
            'message' => 'Notification supprimée avec succès'
        ]);
    }

    /**
     * DIFFUSER LA MISE À JOUR D'UNE NOTIFICATION VIA WEBSOCKET
     */
    private function broadcastNotificationRead(Notification $notification): void
    {
        try {
            $pusher = new PusherClient(
                env('PUSHER_APP_KEY'),
                env('PUSHER_APP_SECRET'),
                env('PUSHER_APP_ID'),
                ['cluster' => 'eu', 'useTLS' => true]
            );
            
            $pusher->trigger('public-channel', 'notification.read', [
                'id' => $notification->id,
                'user_id' => $notification->user_id,
                'is_read' => true
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur Pusher: ' . $e->getMessage());
        }
    }
}