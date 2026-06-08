<?php

namespace App\Enums;

class WorkspaceRole
{
    const OWNER = 'owner';   // Propriétaire
    const ADMIN = 'admin';   // Administrateur  
    const MEMBER = 'membre';  // Membre
    const VIEWER = 'viewer';  // Spectateur (lecture seule)
}
