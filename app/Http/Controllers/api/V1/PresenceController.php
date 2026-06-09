<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Events\UserPresence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PresenceController extends Controller
{
    public function online(Request $request)
    {
        $user = Auth::user();
        $workspaceId = $request->workspace_id;
        
        if (!$workspaceId) {
            return response()->json([
                "status" => false,
                "message" => "workspace_id required"
            ], 400);
        }
        
        broadcast(new UserPresence([
            "id" => $user->id,
            "name" => $user->name,
            "username" => $user->username
        ], (int)$workspaceId, "online"))->toOthers();
        
        return response()->json([
            "status" => true,
            "message" => "Presence online sent"
        ]);
    }
    
    public function offline(Request $request)
    {
        $user = Auth::user();
        $workspaceId = $request->workspace_id;
        
        if (!$workspaceId) {
            return response()->json([
                "status" => false,
                "message" => "workspace_id required"
            ], 400);
        }
        
        broadcast(new UserPresence([
            "id" => $user->id,
            "name" => $user->name,
            "username" => $user->username
        ], (int)$workspaceId, "offline"))->toOthers();
        
        return response()->json([
            "status" => true,
            "message" => "Presence offline sent"
        ]);
    }
}
