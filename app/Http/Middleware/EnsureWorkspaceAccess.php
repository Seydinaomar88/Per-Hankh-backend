<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspaceAccess
{
   public function handle(Request $request, Closure $next): Response
{
    $user = $request->user();

    if (!$user) {
        return response()->json(['message' => 'Unauthenticated'], 401);
    }

    $workspace = $request->route('workspace');

    if (!$workspace) {
        return response()->json(['message' => 'Workspace missing'], 400);
    }

    $hasAccess = $workspace->users()
        ->where('user_id', $user->id)
        ->exists();

    if (!$hasAccess) {
        return response()->json(['message' => 'Access denied'], 403);
    }

    return $next($request);
}
}