<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

class BroadcastController extends Controller
{
    public function auth(Request $request)
    {
        try {
            $user = $request->user();
            
            Log::info('Broadcasting auth request', [
                'user_id' => $user?->id,
                'channel' => $request->channel_name,
                'socket_id' => $request->socket_id,
                'user_exists' => $user ? true : false,
            ]);

            // Vérifier que l'utilisateur est authentifié
            if (!$user) {
                Log::error('Broadcasting auth: Utilisateur non authentifié');
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            // Vérifier le canal
            $channelName = $request->channel_name;
            $socketId = $request->socket_id;

            // Si c'est un canal privé, vérifier les permissions
            if (str_starts_with($channelName, 'private-user.')) {
                $targetUserId = (int) str_replace('private-user.', '', $channelName);
                if ($user->id !== $targetUserId) {
                    Log::error(' User not authorized for this channel', [
                        'user_id' => $user->id,
                        'target_user_id' => $targetUserId
                    ]);
                    return response()->json(['error' => 'Unauthorized'], 403);
                }
            }

            // Authentifier avec Pusher
            $pusher = new \Pusher\Pusher(
                env('PUSHER_APP_KEY'),
                env('PUSHER_APP_SECRET'),
                env('PUSHER_APP_ID'),
                [
                    'cluster' => env('PUSHER_CLUSTER', 'eu'),
                    'useTLS' => true,
                ]
            );

            // Générer l'auth pour le canal
            $auth = $pusher->socket_auth($channelName, $socketId);
            
            Log::info('Broadcasting auth success', [
                'auth' => $auth,
                'user_id' => $user->id,
            ]);

            return response($auth, 200)
                ->header('Content-Type', 'application/json');

        } catch (\Exception $e) {
            Log::error('Broadcasting auth error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['error' => $e->getMessage()], 403);
        }
    }
}