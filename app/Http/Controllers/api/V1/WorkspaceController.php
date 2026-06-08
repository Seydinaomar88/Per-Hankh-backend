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

    // Liste des workspaces de l'utilisateur connecté
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        $workspaces = $user->workspaces->map(function ($workspace) {
            return [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'description' => $workspace->description,
                'owner_id' => $workspace->owner_id,
                'role' => $workspace->pivot->role ?? null,
            ];
        });

        return response()->json([
            'status' => true,
            'workspaces' => $workspaces
        ]);
    }

    // Création workspace
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Non authentifié'
            ], 401);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        $workspace = $this->workspaceService->create($data, $user);

        return response()->json([
            'status' => true,
            'message' => 'Espace de travail créé avec succès',
            'workspace' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'description' => $workspace->description,
                'owner_id' => $workspace->owner_id,
                'role' => 'owner',
            ]
        ], 201);
    }

    // Afficher un workspace spécifique
    public function show(Workspace $workspace): JsonResponse
    {
        $user = request()->user();
        
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Non authentifié'
            ], 401);
        }
        
        $hasAccess = $workspace->users()
            ->where('user_id', $user->id)
            ->exists();
        
        if (!$hasAccess) {
            return response()->json([
                'status' => false,
                'message' => 'Accès refusé'
            ], 403);
        }
        
        $role = $workspace->users()
            ->where('user_id', $user->id)
            ->first()
            ?->pivot
            ?->role;
        
        return response()->json([
            'status' => true,
            'data' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'description' => $workspace->description,
                'owner_id' => $workspace->owner_id,
                'role' => $role,
                'created_at' => $workspace->created_at,
                'updated_at' => $workspace->updated_at,
            ]
        ]);
    }

    // Récupérer les membres d'un workspace
    public function members(Workspace $workspace): JsonResponse
    {
        $user = request()->user();
        
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Non authentifié'
            ], 401);
        }
        
        $hasAccess = $workspace->users()
            ->where('user_id', $user->id)
            ->exists();
        
        if (!$hasAccess) {
            return response()->json([
                'status' => false,
                'message' => 'Accès refusé'
            ], 403);
        }
        
        $members = $workspace->users()
            ->select('users.id', 'users.name', 'users.username', 'users.email', 'user_workspace.role')
            ->get()
            ->map(function($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'username' => $member->username,
                    'email' => $member->email,
                    'role' => $member->pivot->role ?? 'member',
                ];
            });
        
        return response()->json([
            'status' => true,
            'data' => $members
        ]);
    }

    // Ajouter un membre
    public function addMember(Request $request, Workspace $workspace): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'role' => 'required|in:member,viewer,admin'
        ]);

        $authUser = $request->user();

        $isAllowed = $workspace->users()
            ->where('user_id', $authUser->id)
            ->wherePivotIn('role', [WorkspaceRole::OWNER, WorkspaceRole::ADMIN])
            ->exists();

        if (!$isAllowed) {
            return response()->json([
                'status' => false,
                'message' => 'Permission refusée'
            ], 403);
        }

        $userToAdd = User::where('email', $request->email)->first();
        
        if (!$userToAdd) {
            return response()->json([
                'status' => false,
                'message' => 'Utilisateur non trouvé'
            ], 404);
        }
        
        // Vérifier si déjà membre
        $alreadyMember = $workspace->users()
            ->where('user_id', $userToAdd->id)
            ->exists();
            
        if ($alreadyMember) {
            return response()->json([
                'status' => false,
                'message' => 'Cet utilisateur est déjà membre'
            ], 422);
        }

        $workspace->users()->attach($userToAdd->id, ['role' => $request->role]);

        return response()->json([
            'status' => true,
            'message' => 'Membre ajouté avec succès'
        ]);
    }

    // Supprimer un membre
    public function removeMember(Workspace $workspace, User $user): JsonResponse
    {
        $authUser = request()->user();
        
        if (!$authUser) {
            return response()->json([
                'status' => false,
                'message' => 'Non authentifié'
            ], 401);
        }
        
        $isAllowed = $workspace->users()
            ->where('user_id', $authUser->id)
            ->wherePivotIn('role', ['owner', 'admin'])
            ->exists();
        
        if (!$isAllowed) {
            return response()->json([
                'status' => false,
                'message' => 'Permission refusée'
            ], 403);
        }
        
        if ($workspace->owner_id === $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'Impossible de retirer le propriétaire de l\'espace de travail'
            ], 422);
        }
        
        $workspace->users()->detach($user->id);
        
        return response()->json([
            'status' => true,
            'message' => 'Membre retiré avec succès'
        ]);
    }

    // Mettre à jour le rôle d'un membre
    public function updateMemberRole(Request $request, Workspace $workspace, User $user): JsonResponse
    {
        $request->validate([
            'role' => 'required|in:admin,member,viewer'
        ]);
        
        $authUser = request()->user();
        
        if (!$authUser) {
            return response()->json([
                'status' => false,
                'message' => 'Non authentifié'
            ], 401);
        }
        
        $isAllowed = $workspace->users()
            ->where('user_id', $authUser->id)
            ->wherePivotIn('role', ['owner', 'admin'])
            ->exists();
        
        if (!$isAllowed) {
            return response()->json([
                'status' => false,
                'message' => 'Permission refusée'
            ], 403);
        }
        
        if ($workspace->owner_id === $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'Impossible de modifier le rôle du propriétaire'
            ], 422);
        }
        
        $workspace->users()->updateExistingPivot($user->id, ['role' => $request->role]);
        
        return response()->json([
            'status' => true,
            'message' => 'Rôle modifié avec succès'
        ]);
    }
}