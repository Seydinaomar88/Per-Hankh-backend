<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\WorkspaceService;
use Illuminate\Http\JsonResponse;
use App\Models\Workspace;
use App\Models\User;
use App\Enums\WorkspaceRole;

class WorkspaceController extends Controller
{
    protected WorkspaceService $workspaceService;

    public function __construct(WorkspaceService $workspaceService)
    {
        $this->workspaceService = $workspaceService;
    }

    // 📌 Liste des workspaces de l'utilisateur connecté
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $workspaces = $user->workspaces->map(function ($workspace) {
            return [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'description' => $workspace->description,
                'owner_id' => $workspace->owner_id,

                // ✅ safe check pivot (évite crash si null)
                'role' => $workspace->pivot->role ?? null,
            ];
        });

        return response()->json([
            'status' => true,
            'workspaces' => $workspaces
        ]);
    }

    // 📌 Création workspace
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        $workspace = $this->workspaceService->create(
            $data,
            $user
        );

        return response()->json([
            'status' => true,
            'message' => 'Workspace created successfully',
            'workspace' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'description' => $workspace->description,
                'owner_id' => $workspace->owner_id,

                // ✅ important pour frontend PER ANKH
                'role' => 'owner',
            ]
        ], 201);
    }
    public function addMember(Request $request, Workspace $workspace)
{
    $request->validate([
        'user_id' => 'required|exists:users,id',
        'role' => 'required|in:member,viewer,admin'
    ]);

    $authUser = $request->user();

    // 🔐 Vérifier si l'utilisateur a le droit
    $isAllowed = $workspace->users()
        ->where('user_id', $authUser->id)
        ->wherePivotIn('role', [WorkspaceRole::OWNER, WorkspaceRole::ADMIN])
        ->exists();

    if (!$isAllowed) {
        return response()->json([
            'status' => false,
            'message' => 'Permission denied'
        ], 403);
    }

    // 👤 Ajouter membre
    $workspace->users()->syncWithoutDetaching([
        $request->user_id => [
            'role' => $request->role
        ]
    ]);

    return response()->json([
        'status' => true,
        'message' => 'Member added successfully'
    ]);
}
}