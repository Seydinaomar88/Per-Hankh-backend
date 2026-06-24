<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Requests\MoveTaskRequest;

use App\Http\Resources\TaskResource;

use App\Models\Workspace;
use App\Models\Board;
use App\Models\KanbanColumn;
use App\Models\Task;
use App\Models\User;
use App\Models\Notification;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Pusher\Pusher as PusherClient;
use App\Mail\TaskAssignedEmail;
use App\Mail\TaskCompletedEmail;
use App\Mail\TaskOverdueEmail;
use Carbon\Carbon;

class TaskController extends Controller
{
    /**
     * LIST TASKS
     */
    public function index(
        Request $request,
        Workspace $workspace,
        Board $board,
        KanbanColumn $column
    ): JsonResponse {

        $tasks = Task::with([
            'creator',
            'assignedUser',
            'files'
        ])

        ->where(
            'kanban_column_id',
            $column->getKey()
        )

        ->when(
            $request->search,
            function ($query, $search) {
                $query->where(
                    'title',
                    'LIKE',
                    "%{$search}%"
                );
            }
        )

        ->when(
            $request->assigned_to,
            function ($query, $assignedTo) {
                $query->where(
                    'assigned_to',
                    $assignedTo
                );
            }
        )

        ->when(
            $request->priority,
            function ($query, $priority) {
                $query->where(
                    'priority',
                    $priority
                );
            }
        )

        ->when(
            $request->status,
            function ($query, $status) {
                $query->where(
                    'status',
                    $status
                );
            }
        )

        ->orderBy('position', 'asc')

        ->paginate(10);

        return response()->json([
            'status' => true,
            'tasks' => TaskResource::collection($tasks),
            'pagination' => [
                'current_page' => $tasks->currentPage(),
                'last_page' => $tasks->lastPage(),
                'per_page' => $tasks->perPage(),
                'total' => $tasks->total(),
            ]
        ]);
    }

    /**
     * STORE TASK - AVEC DIFFUSION WEBSOCKET EN TEMPS RÉEL
     */
    public function store(
        StoreTaskRequest $request,
        Workspace $workspace,
        Board $board,
        KanbanColumn $column
    ): JsonResponse {

        $maxPosition = Task::where('kanban_column_id', $column->getKey())->max('position') ?? -1;

        $task = Task::create([
            'kanban_column_id' => $column->getKey(),
            'workspace_id' => $workspace->getKey(),
            'created_by' => Auth::id(),
            'assigned_to' => $request->assigned_to,
            'title' => $request->title,
            'description' => $request->description,
            'due_date' => $request->due_date,
            'priority' => $request->priority ?? 'medium',
            'status' => $request->status ?? 'not_started',
            'position' => $maxPosition + 1,
            'tags' => $request->tags,
        ]);

        if ($task->assigned_to) {
            $assignedUser = User::find($task->assigned_to);
            if ($assignedUser && $assignedUser->id !== Auth::id()) {
                try {
                    Mail::to($assignedUser->email)->send(new TaskAssignedEmail(
                        $assignedUser,
                        $task,
                        Auth::user()->name
                    ));
                    Log::info('Email d\'assignation envoyé à: ' . $assignedUser->email);
                    
                    $this->createNotification(
                        $task->assigned_to,
                        "Nouvelle tâche assignée : {$task->title} par " . Auth::user()->name,
                        'task_assigned',
                        $task
                    );
                    
                    $this->broadcastTaskEvent('task.assigned', $task);
                } catch (\Exception $e) {
                    Log::error('Erreur envoi email d\'assignation: ' . $e->getMessage());
                }
            }
        }

        $task->load(['creator', 'assignedUser', 'files']);

        //  DIFFUSER LA CRÉATION EN TEMPS RÉEL
        $this->broadcastTaskCreated($task, $column->id, $workspace->id);

        return response()->json([
            'status' => true,
            'message' => 'Task created successfully',
            'task' => new TaskResource($task)
        ], 201);
    }

    /**
     * DIFFUSER LA CRÉATION DE TÂCHE VIA WEBSOCKET
     */
    private function broadcastTaskCreated(Task $task, int $columnId, int $workspaceId): void
    {
        try {
            $pusher = new PusherClient(
                env('PUSHER_APP_KEY'),
                env('PUSHER_APP_SECRET'),
                env('PUSHER_APP_ID'),
                [
                    'cluster' => env('PUSHER_CLUSTER', 'eu'),
                    'useTLS' => true,
                    'encrypted' => true,
                ]
            );
            
            $user = Auth::user();
            
            $data = [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status,
                'priority' => $task->priority,
                'column_id' => $columnId,
                'position' => $task->position,
                'assigned_to' => $task->assigned_to,
                'due_date' => $task->due_date,
                'tags' => $task->tags,
                'workspace_id' => $workspaceId,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'timestamp' => now()->toIso8601String(),
                'task' => [
                    'id' => $task->id,
                    'title' => $task->title,
                    'column_id' => $columnId,
                    'status' => $task->status,
                    'description' => $task->description,
                    'priority' => $task->priority,
                    'position' => $task->position,
                    'assigned_to' => $task->assigned_to,
                    'due_date' => $task->due_date,
                    'tags' => $task->tags,
                    'created_at' => $task->created_at?->toISOString(),
                    'creator' => $task->creator ? [
                        'id' => $task->creator->id,
                        'name' => $task->creator->name,
                        'email' => $task->creator->email,
                    ] : null,
                    'assignedUser' => $task->assignedUser ? [
                        'id' => $task->assignedUser->id,
                        'name' => $task->assignedUser->name,
                        'email' => $task->assignedUser->email,
                    ] : null,
                ]
            ];
            
            // Diffuser sur le canal global des tâches
            $pusher->trigger('tasks', 'task.created', $data);
            
            // Diffuser sur le canal du workspace
            $pusher->trigger('workspace.' . $workspaceId, 'task.created', $data);
            
            Log::info('Pusher event sent: task.created', [
                'task_id' => $task->id,
                'workspace_id' => $workspaceId,
                'user_id' => $user->id
            ]);
        } catch (\Exception $e) {
            Log::error('Pusher error broadcastTaskCreated: ' . $e->getMessage());
        }
    }

    /**
     * SHOW TASK
     */
    public function show(
        Workspace $workspace,
        Board $board,
        KanbanColumn $column,
        Task $task
    ): JsonResponse {

        $task->load(['creator', 'assignedUser', 'files']);

        return response()->json([
            'status' => true,
            'task' => new TaskResource($task)
        ]);
    }

    /**
     * UPDATE TASK - AVEC GESTION DES PERMISSIONS ET NOTIFICATIONS EN TEMPS RÉEL
     */
    public function update(
        UpdateTaskRequest $request,
        Workspace $workspace,
        Board $board,
        KanbanColumn $column,
        Task $task
    ): JsonResponse {

        if ($task->workspace_id !== $workspace->getKey()) {
            return response()->json([
                'status' => false,
                'message' => 'Tache non authoriser'
            ], 403);
        }

        // VÉRIFICATION DES PERMISSIONS
        $user = Auth::user();
        $member = $workspace->users()->where('user_id', $user->id)->first();
        $role = $member ? $member->pivot->role : null;

        $canEdit = false;
        
        if (in_array($role, ['owner', 'admin'])) {
            $canEdit = true;
        } elseif ($task->created_by === $user->id) {
            $canEdit = true;
        } elseif ($task->assigned_to === $user->id) {
            $canEdit = true;
        }

        if (!$canEdit) {
            return response()->json([
                'status' => false,
                'message' => 'Vous n\'êtes pas autorisé à modifier cette tâche.'
            ], 403);
        }

        $oldStatus = $task->status;
        $oldAssignedTo = $task->assigned_to;

        $task->update($request->validated());

        if ($task->status === 'done' && $oldStatus !== 'done') {
            $this->handleTaskCompleted($task);
        }

        if ($task->assigned_to && $task->assigned_to !== $oldAssignedTo) {
            $this->handleTaskAssigned($task);
        }

        $task->load(['creator', 'assignedUser', 'files']);

        // DIFFUSER LA MISE À JOUR EN TEMPS RÉEL
        $this->broadcastTaskEvent('task.updated', $task);

        return response()->json([
            'status' => true,
            'message' => 'Tache modifier avec succes',
            'task' => new TaskResource($task)
        ]);
    }

    /**
     * DELETE TASK - AVEC VÉRIFICATION DES PERMISSIONS
     */
    public function destroy(
        Workspace $workspace,
        Board $board,
        KanbanColumn $column,
        Task $task
    ): JsonResponse {

        if ($task->workspace_id !== $workspace->getKey()) {
            return response()->json([
                'status' => false,
                'message' => 'Tache non authoriser'
            ], 403);
        }

        // VÉRIFICATION DES PERMISSIONS
        $user = Auth::user();
        $member = $workspace->users()->where('user_id', $user->id)->first();
        $role = $member ? $member->pivot->role : null;

        $canDelete = false;
        
        if (in_array($role, ['owner', 'admin'])) {
            $canDelete = true;
        } elseif ($task->created_by === $user->id) {
            $canDelete = true;
        }

        if (!$canDelete) {
            return response()->json([
                'status' => false,
                'message' => 'Vous n\'êtes pas autorisé à supprimer cette tâche.'
            ], 403);
        }

        $task->delete();

        // DIFFUSER LA SUPPRESSION EN TEMPS RÉEL
        $this->broadcastTaskEvent('task.deleted', $task);

        return response()->json([
            'status' => true,
            'message' => 'Tache supprime avec succes'
        ]);
    }

    /**
     * MOVE TASK - OPTIMISÉ ET RAPIDE
     */
    public function move(
        MoveTaskRequest $request,
        Workspace $workspace,
        Board $board,
        KanbanColumn $column,
        Task $task
    ): JsonResponse {

        if ($task->workspace_id !== $workspace->getKey()) {
            return response()->json([
                'status' => false,
                'message' => 'Tâche non trouvée'
            ], 404);
        }

        $user = Auth::user();
        $member = $workspace->users()->where('user_id', $user->id)->first();
        $role = $member ? $member->pivot->role : null;

        $allowedRoles = ['owner', 'admin', 'member'];
        
        if (!in_array($role, $allowedRoles)) {
            return response()->json([
                'status' => false,
                'message' => 'Vous n\'êtes pas autorisé à déplacer cette carte.'
            ], 403);
        }

        $destinationColumn = KanbanColumn::where('id', $request->kanban_column_id)
            ->where('board_id', $board->id)
            ->first();

        if (!$destinationColumn) {
            return response()->json([
                'status' => false,
                'message' => 'Colonne de destination invalide'
            ], 404);
        }

        $oldColumnId = $task->kanban_column_id;
        
        Log::info('MOVE TASK', [
            'task_id' => $task->id,
            'old_column' => $oldColumnId,
            'new_column' => $request->kanban_column_id,
            'position' => $request->position,
            'user_id' => $user->id,
            'user_name' => $user->name
        ]);
        
        $task->update([
            'kanban_column_id' => $request->kanban_column_id,
            'position' => $request->position,
        ]);

        $this->reorderColumn($request->kanban_column_id);
        if ($oldColumnId !== $request->kanban_column_id) {
            $this->reorderColumn($oldColumnId);
        }

        $task->load(['creator', 'assignedUser', 'files']);

        $this->sendPusherEventWithUser($task, $oldColumnId, $request->kanban_column_id, $board->id, $workspace->id, $user);

        return response()->json([
            'status' => true,
            'message' => 'Tâche déplacée avec succès',
            'task' => new TaskResource($task)
        ]);
    }

    /**
     * REORGANISER LES POSITIONS
     */
    private function reorderColumn(int $columnId): void
    {
        try {
            $tasks = Task::where('kanban_column_id', $columnId)
                ->orderBy('position', 'asc')
                ->get();
            
            foreach ($tasks as $index => $task) {
                $task->position = $index;
                $task->save();
            }
            
            Log::info("Colonne $columnId réorganisée avec succès");
        } catch (\Exception $e) {
            Log::error('Erreur reorder colonne ' . $columnId . ': ' . $e->getMessage());
        }
    }

    /**
     * ENVOYER L'ÉVÉNEMENT PUSHER AVEC LES INFOS UTILISATEUR
     */
    private function sendPusherEventWithUser(Task $task, int $oldColumnId, int $newColumnId, int $boardId, int $workspaceId, User $user): void
    {
        try {
            $pusher = new PusherClient(
                env('PUSHER_APP_KEY'),
                env('PUSHER_APP_SECRET'),
                env('PUSHER_APP_ID'),
                [
                    'cluster' => env('PUSHER_CLUSTER', 'eu'),
                    'useTLS' => true,
                    'encrypted' => true,
                ]
            );
            
            $data = [
                'id' => $task->id,
                'title' => $task->title,
                'source_column_id' => $oldColumnId,
                'destination_column_id' => $newColumnId,
                'position' => $task->position,
                'board_id' => $boardId,
                'workspace_id' => $workspaceId,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'timestamp' => now()->toIso8601String()
            ];
            
            $pusher->trigger('tasks', 'task.moved', $data);
            $pusher->trigger('workspace.' . $workspaceId, 'task.moved', $data);
            
            Log::info('Pusher event sent: task.moved', [
                'task_id' => $task->id,
                'user_id' => $user->id,
                'user_name' => $user->name
            ]);
        } catch (\Exception $e) {
            Log::error('Pusher error: ' . $e->getMessage());
        }
    }

    /**
     * GÉRER TÂCHE TERMINÉE
     */
    private function handleTaskCompleted(Task $task): void
    {
        try {
            $completedBy = Auth::user()->name;
            
            Log::info('Tâche terminée: ' . $task->title . ' par ' . $completedBy);
            
            if ($task->created_by) {
                $creator = User::find($task->created_by);
                if ($creator) {
                    Mail::to($creator->email)->send(new TaskCompletedEmail(
                        $creator,
                        $task,
                        $completedBy
                    ));
                    Log::info(' Email tâche terminée envoyé à: ' . $creator->email);
                }
            }
            
            if ($task->assigned_to && $task->assigned_to !== $task->created_by) {
                $assignedUser = User::find($task->assigned_to);
                if ($assignedUser) {
                    Mail::to($assignedUser->email)->send(new TaskCompletedEmail(
                        $assignedUser,
                        $task,
                        $completedBy
                    ));
                    Log::info('Email tâche terminée envoyé à: ' . $assignedUser->email);
                }
            }
            
            $this->createNotification(
                $task->created_by,
                "Tâche terminée : {$task->title} par {$completedBy}",
                'task_completed',
                $task
            );
            
            if ($task->assigned_to && $task->assigned_to !== $task->created_by) {
                $this->createNotification(
                    $task->assigned_to,
                    "Tâche terminée : {$task->title} par {$completedBy}",
                    'task_completed',
                    $task
                );
            }
            
            $this->broadcastTaskEvent('task.completed', $task);
            $this->broadcastTaskCompletedNotification($task, $completedBy);
            
            Log::info('Notifications tâche terminée envoyées pour: ' . $task->title);
            
        } catch (\Exception $e) {
            Log::error('Erreur notification tâche terminée: ' . $e->getMessage());
        }
    }

    /**
     * GÉRER TÂCHE ASSIGNÉE
     */
    private function handleTaskAssigned(Task $task): void
    {
        try {
            $assignedUser = User::find($task->assigned_to);
            $assigner = Auth::user();
            
            if ($assignedUser && $assignedUser->id !== $assigner->id) {
                Mail::to($assignedUser->email)->send(new TaskAssignedEmail(
                    $assignedUser,
                    $task,
                    $assigner->name
                ));
                Log::info('Email d\'assignation envoyé à: ' . $assignedUser->email);
                
                $this->createNotification(
                    $task->assigned_to,
                    "Nouvelle tâche assignée : {$task->title} par {$assigner->name}",
                    'task_assigned',
                    $task
                );
                
                $this->broadcastTaskEvent('task.assigned', $task);
            }
        } catch (\Exception $e) {
            Log::error('Erreur notification assignation: ' . $e->getMessage());
        }
    }

    /**
     * CRÉER UNE NOTIFICATION EN BASE DE DONNÉES
     * 
     * @param int $userId
     * @param string $message
     * @param string $type
     * @param Task $task
     * @return void
     */
    private function createNotification(int $userId, string $message, string $type, Task $task): void
    {
        try {
            Notification::create([
                'user_id' => $userId,
                'workspace_id' => $task->workspace_id,
                'created_by' => Auth::id(),
                'type' => $type,
                'message' => $message,
                'data' => json_encode([
                    'task_id' => $task->id,
                    'task_title' => $task->title,
                    'due_date' => $task->due_date,
                    'status' => $task->status,
                ]),
                'is_read' => false,
            ]);
            
            Log::info('Notification créée pour utilisateur ' . $userId . ' (type: ' . $type . ')');
        } catch (\Exception $e) {
            Log::error('Erreur création notification: ' . $e->getMessage());
        }
    }

    /**
     * DIFFUSER UN ÉVÉNEMENT TÂCHE VIA WEBSOCKET
     */
    private function broadcastTaskEvent(string $event, Task $task): void
    {
        try {
            $pusher = new PusherClient(
                env('PUSHER_APP_KEY'),
                env('PUSHER_APP_SECRET'),
                env('PUSHER_APP_ID'),
                ['cluster' => 'eu', 'useTLS' => true]
            );
            
            $data = [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status,
                'priority' => $task->priority,
                'assigned_to' => $task->assigned_to,
                'due_date' => $task->due_date,
                'tags' => $task->tags,
                'workspace_id' => $task->workspace_id,
                'user_id' => Auth::id(),
                'user_name' => Auth::user()->name,
                'timestamp' => now()->toIso8601String()
            ];
            
            $pusher->trigger('tasks', $event, $data);
            $pusher->trigger('workspace.' . $task->workspace_id, $event, $data);
            
            Log::info("Événement {$event} diffusé pour la tâche {$task->id}");
        } catch (\Exception $e) {
            Log::error('Erreur broadcast: ' . $e->getMessage());
        }
    }

    /**
     * DIFFUSER UNE NOTIFICATION SPÉCIFIQUE DE TÂCHE TERMINÉE
     */
    private function broadcastTaskCompletedNotification(Task $task, string $completedBy): void
    {
        try {
            $pusher = new PusherClient(
                env('PUSHER_APP_KEY'),
                env('PUSHER_APP_SECRET'),
                env('PUSHER_APP_ID'),
                ['cluster' => 'eu', 'useTLS' => true]
            );
            
            $data = [
                'id' => 'task_completed_' . $task->id . '_' . time(),
                'message' => "Tâche terminée : {$task->title} par {$completedBy}",
                'type' => 'task_completed',
                'task_id' => $task->id,
                'task_title' => $task->title,
                'completed_by' => $completedBy,
                'created_at' => now()->toIso8601String(),
                'is_read' => false,
            ];
            
            $pusher->trigger('public-channel', 'notification.new', $data);
            
            if ($task->created_by) {
                $pusher->trigger('private-user.' . $task->created_by, 'notification.new', $data);
            }
            
            if ($task->assigned_to && $task->assigned_to !== $task->created_by) {
                $pusher->trigger('private-user.' . $task->assigned_to, 'notification.new', $data);
            }
            
            Log::info("Notification tâche terminée diffusée pour la tâche {$task->id}");
        } catch (\Exception $e) {
            Log::error('Erreur broadcast notification: ' . $e->getMessage());
        }
    }

    /**
     * Envoyer l'événement Pusher (legacy)
     */
    private function sendPusherEvent(Task $task, int $oldColumnId, int $newColumnId, int $boardId, int $workspaceId): void
    {
        try {
            $pusher = new PusherClient(
                env('PUSHER_APP_KEY'),
                env('PUSHER_APP_SECRET'),
                env('PUSHER_APP_ID'),
                ['cluster' => 'eu', 'useTLS' => true]
            );
            
            $data = [
                'id' => $task->id,
                'title' => $task->title,
                'source_column_id' => $oldColumnId,
                'destination_column_id' => $newColumnId,
                'position' => $task->position,
                'board_id' => $boardId,
                'workspace_id' => $workspaceId,
                'user_id' => Auth::id(),
                'user_name' => Auth::user()->name,
                'timestamp' => now()->toIso8601String()
            ];
            
            $pusher->trigger('tasks', 'task.moved', $data);
            
            Log::info('Pusher event sent: task.moved', ['task_id' => $task->id]);
        } catch (\Exception $e) {
            Log::error('Pusher error: ' . $e->getMessage());
        }
    }

    // 🔥 NOUVELLES MÉTHODES POUR LES TÂCHES EN RETARD

    /**
     * Vérifier et envoyer les notifications pour les tâches en retard
     * 
     * @return JsonResponse
     */
    public function checkOverdueTasks(): JsonResponse
    {
        try {
            $overdueTasks = Task::with(['assignedUser', 'creator', 'workspace'])
                ->where('due_date', '<', Carbon::now())
                ->where('status', '!=', 'done')
                ->whereNotNull('due_date')
                ->get();

            $count = 0;

            foreach ($overdueTasks as $task) {
                // Vérifier si une notification a déjà été envoyée aujourd'hui
                $existingNotification = Notification::where('type', 'task_overdue')
                    ->where('data', 'LIKE', '%"task_id":' . $task->id . '%')
                    ->whereDate('created_at', Carbon::today())
                    ->exists();

                if (!$existingNotification) {
                    $this->sendOverdueNotification($task);
                    $count++;
                    Log::info("✅ Notification de retard envoyée pour la tâche: {$task->title}");
                }
            }

            return response()->json([
                'status' => true,
                'message' => "{$count} notification(s) de retard envoyée(s)",
                'count' => $count
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur vérification tâches en retard: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erreur lors de l\'envoi des notifications'
            ], 500);
        }
    }

    /**
     * Envoyer les notifications pour une tâche en retard
     * 
     * @param Task $task
     * @return void
     */
    private function sendOverdueNotification(Task $task): void
    {
        try {
            // Notification pour le créateur
            if ($task->created_by) {
                $this->createNotification(
                    $task->created_by,
                    'Votre tâche "' . $task->title . '" est en retard !',
                    'task_overdue',
                    $task
                );
            }

            // Notification pour l'assigné
            if ($task->assigned_to && $task->assigned_to !== $task->created_by) {
                $this->createNotification(
                    $task->assigned_to,
                    'La tâche "' . $task->title . '" qui vous est assignée est en retard !',
                    'task_overdue',
                    $task
                );
            }

            // Email à l'assigné
            if ($task->assigned_to) {
                $assignedUser = User::find($task->assigned_to);
                if ($assignedUser && $assignedUser->email) {
                    try {
                        Mail::to($assignedUser->email)->send(new TaskOverdueEmail($assignedUser, $task));
                        Log::info("📧 Email retard envoyé à: {$assignedUser->email}");
                    } catch (\Exception $e) {
                        Log::error('❌ Erreur email assigné: ' . $e->getMessage());
                    }
                }
            }

            // Email au créateur si différent
            if ($task->created_by && $task->created_by !== $task->assigned_to) {
                $creator = User::find($task->created_by);
                if ($creator && $creator->email) {
                    try {
                        Mail::to($creator->email)->send(new TaskOverdueEmail($creator, $task));
                        Log::info("📧 Email retard envoyé au créateur: {$creator->email}");
                    } catch (\Exception $e) {
                        Log::error('❌ Erreur email créateur: ' . $e->getMessage());
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error('❌ Erreur envoi notification retard: ' . $e->getMessage());
        }
    }
}