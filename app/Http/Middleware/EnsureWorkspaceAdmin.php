<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspaceAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $workspace = $request->route('workspace');

        if (!$workspace) {
            return response()->json(['message' => 'Workspace not found'], 400);
        }

        $membership = $workspace->users()
            ->where('user_id', $user->id)
            ->first();

        if (!$membership) {
            return response()->json(['message' => 'Not member'], 403);
        }

        $role = $membership->pivot->role;

        // BLOQUER LES VIEWERS AVEC STATUS 403
        if ($role === 'viewer') {
            return response()->json([
                'status' => false,
                'message' => 'Vous n\'êtes pas autorisé à déplacer cette carte. Seuls les propriétaires, 
                administrateurs et membres peuvent déplacer les tâches.'
            ], 403);
        }

        return $next($request);
    }
}