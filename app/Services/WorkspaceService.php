<?php

namespace App\Services;

use App\Models\User;
use App\Models\Workspace;
use App\Enums\WorkspaceRole;

class WorkspaceService
{
    public function create(array $data, User $user)
    {
        $workspace = Workspace::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'owner_id' => $user->id,
        ]);

        // 👑 Créateur = OWNER
        $workspace->users()->attach($user->id, [
            'role' => WorkspaceRole::OWNER
        ]);

        return $workspace;
    }
}