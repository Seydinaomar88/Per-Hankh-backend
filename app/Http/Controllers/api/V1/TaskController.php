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

use App\Events\TaskMoved;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

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
                    'Unauthorized task'

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
                'Task updated successfully',

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
                    'Unauthorized task'

            ], 403);
        }

        $task->delete();

        return response()->json([

            'status' => true,

            'message' =>
                'Task deleted successfully'
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
         * SECURITY
         */
        if (
            $task->workspace_id !==
            $workspace->getKey()
        ) {

            return response()->json([

                'status' => false,

                'message' =>
                    'Unauthorized task'

            ], 403);
        }

        /**
         * MOVE TASK
         */
        $task->update([

            'kanban_column_id' =>
                $request->kanban_column_id,

            'position' =>
                $request->position,
        ]);

        /**
         * RELOAD RELATIONS
         */
        $task->load([
            'creator',
            'assignedUser',
            'files'
        ]);

        /**
         * BROADCAST EVENT
         */
        broadcast(
            new TaskMoved($task)
        );

        return response()->json([

            'status' => true,

            'message' =>
                'Task moved successfully',

            'task' =>
                new TaskResource($task)
        ]);
    }
}