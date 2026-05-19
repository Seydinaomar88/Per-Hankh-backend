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

        // IMPORTANT : récupérer le role depuis pivot correctement
        $membership = $workspace->users()
            ->where('user_id', $user->id)
            ->first();

        if (!$membership) {
            return response()->json(['message' => 'Not member'], 403);
        }

        $role = $membership->pivot->role;

        if (!in_array($role, ['owner', 'admin'])) {
            return response()->json(['message' => 'Admin only'], 403);
        }

        return $next($request);
    }
}