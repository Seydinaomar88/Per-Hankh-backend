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

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Pusher\Pusher as PusherClient;

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

        /**
         * SEARCH
         */
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

        /**
         * FILTER ASSIGNED USER
         */
        ->when(
            $request->assigned_to,
            function ($query, $assignedTo) {

                $query->where(
                    'assigned_to',
                    $assignedTo
                );
            }
        )

        /**
         * FILTER PRIORITY
         */
        ->when(
            $request->priority,
            function ($query, $priority) {

                $query->where(
                    'priority',
                    $priority
                );
            }
        )

        ->orderBy('position')

        ->paginate(10);

        return response()->json([

            'status' => true,

            'tasks' => TaskResource::collection($tasks),

            'pagination' => [

                'current_page' =>
                    $tasks->currentPage(),

                'last_page' =>
                    $tasks->lastPage(),

                'per_page' =>
                    $tasks->perPage(),

                'total' =>
                    $tasks->total(),
            ]
        ]);
    }

    /**
     * STORE TASK
     */
    public function store(
        StoreTaskRequest $request,
        Workspace $workspace,
        Board $board,
        KanbanColumn $column
    ): JsonResponse {

        $task = Task::create([

            'kanban_column_id' =>
                $column->getKey(),

            'workspace_id' =>
                $workspace->getKey(),

            'created_by' =>
                Auth::id(),

            'assigned_to' =>
                $request->assigned_to,

            'title' =>
                $request->title,

            'description' =>
                $request->description,

            'due_date' =>
                $request->due_date,

            'priority' =>
                $request->priority ?? 'medium',

            'position' =>
                $request->position ?? 0,

            'tags' =>
                $request->tags,
        ]);

        /**
         * LOAD RELATIONS
         */
        $task->load([
            'creator',
            'assignedUser',
            'files'
        ]);

        return response()->json([

            'status' => true,

            'message' =>
                'Task created successfully',

            'task' =>
                new TaskResource($task)

        ], 201);
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

        $task->load([
            'creator',
            'assignedUser',
            'files'
        ]);

        return response()->json([

            'status' => true,

            'task' =>
                new TaskResource($task)
        ]);
    }

    /**
     * UPDATE TASK
     */
    public function update(
        UpdateTaskRequest $request,
        Workspace $workspace,
        Board $board,
        KanbanColumn $column,
        Task $task
    ): JsonResponse {

        /**
         * SECURITY
         */
        if (
            $task->workspace_id !==
            $workspace->getKey()
        ) {

            return response()->json([

                'status' => false,

                'message' =>
                    'Tache non authoriser'

            ], 403);
        }

        $task->update(
            $request->validated()
        );

        /**
         * RELOAD RELATIONS
         */
        $task->load([
            'creator',
            'assignedUser',
            'files'
        ]);

        return response()->json([

            'status' => true,

            'message' =>
                'Tache modifier avec succes',

            'task' =>
                new TaskResource($task)
        ]);
    }

    /**
     * DELETE TASK
     */
    public function destroy(
        Workspace $workspace,
        Board $board,
        KanbanColumn $column,
        Task $task
    ): JsonResponse {

        /**
         * SECURITY
         */
        if (
            $task->workspace_id !==
            $workspace->getKey()
        ) {

            return response()->json([

                'status' => false,

                'message' =>
                    'Tache non authoriser'

            ], 403);
        }

        $task->delete();

        return response()->json([

            'status' => true,

            'message' =>
                'Tache supprime avec succes'
        ]);
    }

    /**
     * MOVE TASK
     */
    public function move(
        MoveTaskRequest $request,
        Workspace $workspace,
        Board $board,
        KanbanColumn $column,
        Task $task
    ): JsonResponse {

        /**
         * SECURITY - Vérifier que la tâche appartient au workspace
         */
        if ($task->workspace_id !== $workspace->getKey()) {
            return response()->json([
                'status' => false,
                'message' => 'Tâche non trouvée'
            ], 404);
        }

        /**
         * VÉRIFICATION DES PERMISSIONS POUR DÉPLACER UNE TÂCHE
         */
        $user = Auth::user();
        $member = $workspace->users()->where('user_id', $user->id)->first();
        $role = $member ? $member->pivot->role : null;

        $allowedRoles = ['owner', 'admin', 'member'];
        
        if (!in_array($role, $allowedRoles)) {
            return response()->json([
                'status' => false,
                'message' => 'Vous n\'êtes pas autorisé à déplacer cette carte. Seuls les propriétaires, administrateurs et membres peuvent déplacer les tâches.'
            ], 403);
        }

        /**
         * Vérifier que la colonne de destination existe dans le board
         */
        $destinationColumn = KanbanColumn::where('id', $request->kanban_column_id)
            ->where('board_id', $board->id)
            ->first();

        if (!$destinationColumn) {
            return response()->json([
                'status' => false,
                'message' => 'Colonne de destination invalide'
            ], 404);
        }

        /**
         * MOVE TASK
         */
        $oldColumnId = $task->kanban_column_id;
        
        $task->update([
            'kanban_column_id' => $request->kanban_column_id,
            'position' => $request->position,
        ]);

        /**
         * REORGANISER LES POSITIONS DES TÂCHES
         */
        $this->reorderPositions($oldColumnId);
        $this->reorderPositions($request->kanban_column_id);

        /**
         * RELOAD RELATIONS
         */
        $task->load([
            'creator',
            'assignedUser',
            'files'
        ]);

        /**
         * ENVOYER L'ÉVÉNEMENT PUSHER POUR LA SYNCHRONISATION EN TEMPS RÉEL
         */
        try {
            $pusher = new PusherClient(
                env('PUSHER_APP_KEY'),
                env('PUSHER_APP_SECRET'),
                env('PUSHER_APP_ID'),
                ['cluster' => 'eu', 'useTLS' => true]
            );
            
            $pusher->trigger('public-channel', 'task.moved', [
                'task_id' => $task->id,
                'title' => $task->title,
                'source_column_id' => $oldColumnId,
                'destination_column_id' => $request->kanban_column_id,
                'workspace_id' => $workspace->id,
                'timestamp' => now()->toIso8601String()
            ]);
            
            Log::info('Pusher event sent: task.moved', ['task_id' => $task->id]);
        } catch (\Exception $e) {
            Log::error('Pusher error: ' . $e->getMessage());
        }

        return response()->json([
            'status' => true,
            'message' => 'Tâche déplacée avec succès',
            'task' => new TaskResource($task)
        ]);
    }

    /**
     * Réorganiser les positions des tâches dans une colonne
     */
    private function reorderPositions(int $columnId): void
    {
        $tasks = Task::where('kanban_column_id', $columnId)
            ->orderBy('position', 'asc')
            ->get();
        
        foreach ($tasks as $index => $task) {
            $task->update(['position' => $index]);
        }
    }
}