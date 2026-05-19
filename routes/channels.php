<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;  // ← Ajoutez cette ligne

// Version TEST - Autorise TOUS les accès sans vérification
Broadcast::channel('workspace.{workspaceId}', function ($user = null, $workspaceId) {
    // Log pour debug
    Log::info('Channel auth called', [
        'user' => $user,
        'workspace_id' => $workspaceId
    ]);
    
    // Accepte tout le monde pour le test
    return ['id' => 1, 'name' => 'Test User'];
});

// Pour les canaux private-workspace.*
Broadcast::channel('private-workspace.{workspaceId}', function ($user = null, $workspaceId) {
    Log::info('Private channel auth called', [
        'user' => $user,
        'workspace_id' => $workspaceId
    ]);
    
    // Accepte tout le monde pour le test
    return ['id' => 1, 'name' => 'Test User'];
});