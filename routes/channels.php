<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

// Canal privé pour les tâches
Broadcast::channel('task.{taskId}', function ($user, $taskId) {
    Log::info('Task channel auth', [
        'user_id' => $user->id ?? null,
        'task_id' => $taskId
    ]);
    
    try {
        $task = \App\Models\Task::findOrFail($taskId);
        $hasAccess = $user->workspaces()->where('workspace_id', $task->workspace_id)->exists();
        Log::info('Task access result', ['hasAccess' => $hasAccess]);
        return $hasAccess ? $user : false;
    } catch (\Exception $e) {
        Log::error('Task channel error: ' . $e->getMessage());
        return false;
    }
});

// Canal privé pour les workspaces
Broadcast::channel('workspace.{workspaceId}', function ($user, $workspaceId) {
    Log::info('Workspace channel auth', [
        'user_id' => $user->id ?? null,
        'workspace_id' => $workspaceId
    ]);
    
    try {
        $hasAccess = $user->workspaces()->where('workspace_id', $workspaceId)->exists();
        Log::info('Workspace access result', ['hasAccess' => $hasAccess]);
        return $hasAccess ? $user : false;
    } catch (\Exception $e) {
        Log::error('Workspace channel error: ' . $e->getMessage());
        return false;
    }
});


Broadcast::channel('private-user.{userId}', function ($user, $userId) {
    Log::info('Private user channel auth', [
        'user_id' => $user->id ?? null,
        'requested_user_id' => $userId
    ]);
    return (int) $user->id === (int) $userId;
});

// Canal public pour les événements généraux
Broadcast::channel('public-channel', function ($user) {
    return true;
});

// Canal pour les tâches
Broadcast::channel('tasks', function ($user) {
    return true;
});