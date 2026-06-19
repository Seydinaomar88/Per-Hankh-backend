<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNoteRequest;
use App\Models\Workspace;
use App\Models\Note;
use App\Models\Task;
use App\Models\TaskNoteComment;
use App\Models\TaskNote;
use App\Models\User;
use App\Models\Notification;
use App\Events\NotificationSent;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Events\TaskNoteCommentCreated;
use Illuminate\Support\Facades\Log;
use Pusher\Pusher as PusherClient;

class NoteController extends Controller
{
    /**
     * LISTE DES NOTES (Niveau workspace)
     */
    public function index(Workspace $workspace): JsonResponse
    {
        $notes = Note::where('workspace_id', $workspace->getKey())
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'notes' => $notes
        ]);
    }

    /**
     * CRÉER UNE NOTE (Niveau workspace)
     */
    public function store(StoreNoteRequest $request, Workspace $workspace): JsonResponse
    {
        $note = Note::create([
            'workspace_id' => $workspace->getKey(),
            'created_by' => Auth::id(),
            'title' => $request->title,
            'content' => $request->content,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Note créée avec succès',
            'note' => $note
        ]);
    }

    /**
     * AFFICHER UNE NOTE (Niveau workspace)
     */
    public function show(Workspace $workspace, Note $note): JsonResponse
    {
        return response()->json([
            'status' => true,
            'note' => $note
        ]);
    }

    /**
     * MODIFIER UNE NOTE (Niveau workspace)
     */
    public function update(Request $request, Workspace $workspace, Note $note): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'nullable|string'
        ]);

        $note->update($validated);

        return response()->json([
            'status' => true,
            'message' => 'Note modifiée avec succès',
            'note' => $note
        ]);
    }

    /**
     * SUPPRIMER UNE NOTE (Niveau workspace)
     */
    public function destroy(Workspace $workspace, Note $note): JsonResponse
    {
        $note->delete();

        return response()->json([
            'status' => true,
            'message' => 'Note supprimée avec succès'
        ]);
    }

    // ============ MÉTHODES POUR LES NOTES DES TÂCHES ============

    /**
     * LISTE DES NOTES D'UNE TÂCHE
     */
    public function indexTask(int $workspaceId, int $boardId, int $columnId, int $taskId): JsonResponse
    {
        $task = Task::findOrFail($taskId);
        
        $notes = $task->notes()->with('creator')->latest()->get();

        return response()->json([
            'status' => true,
            'data' => $notes
        ]);
    }

    /**
     * CRÉER UNE NOTE POUR UNE TÂCHE
     */
    public function storeTask(Request $request, int $workspaceId, int $boardId, int $columnId, int $taskId): JsonResponse
    {
        $task = Task::findOrFail($taskId);
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string'
        ]);

        $note = TaskNote::create([
            'task_id' => $task->id,
            'created_by' => Auth::id(),
            'title' => $validated['title'],
            'content' => $validated['content'] ?? null,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Note de tâche créée avec succès',
            'data' => $note
        ], 201);
    }

    /**
     * MODIFIER UNE NOTE DE TÂCHE
     */
    public function updateTask(Request $request, int $workspaceId, int $boardId, int $columnId, int $taskId, int $noteId): JsonResponse
    {
        $task = Task::findOrFail($taskId);
        $note = TaskNote::where('task_id', $task->id)->findOrFail($noteId);
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string'
        ]);

        $note->update($validated);

        return response()->json([
            'status' => true,
            'message' => 'Note de tâche modifiée avec succès',
            'data' => $note
        ]);
    }

    /**
     * SUPPRIMER UNE NOTE DE TÂCHE
     */
    public function destroyTask(int $workspaceId, int $boardId, int $columnId, int $taskId, int $noteId): JsonResponse
    {
        $task = Task::findOrFail($taskId);
        $note = TaskNote::where('task_id', $task->id)->findOrFail($noteId);
        
        $note->delete();

        return response()->json([
            'status' => true,
            'message' => 'Note de tâche supprimée avec succès'
        ]);
    }

    /**
     * LISTE DES COMMENTAIRES D'UNE NOTE DE TÂCHE
     */
    public function indexTaskNoteComments(int $workspaceId, int $boardId, int $columnId, int $taskId, int $noteId): JsonResponse
    {
        $note = TaskNote::findOrFail($noteId);
        $comments = $note->comments()->with('user')->latest()->get();

        return response()->json([
            'status' => true,
            'data' => $comments
        ]);
    }

    /**
     * CRÉER UN COMMENTAIRE POUR UNE NOTE DE TÂCHE
     */
    public function storeTaskNoteComment(Request $request, int $workspaceId, int $boardId, int $columnId, int $taskId, int $noteId): JsonResponse
    {
        $note = TaskNote::findOrFail($noteId);
        
        $validated = $request->validate([
            'content' => 'required|string',
            'mentions' => 'nullable|array'
        ]);

        $comment = TaskNoteComment::create([
            'task_note_id' => $note->id,
            'user_id' => Auth::id(),
            'content' => $validated['content'],
        ]);

        $comment->load('user');

        try {
            // Envoyer via Pusher
            $this->sendPusherEvent($comment, $taskId, $noteId, $workspaceId, $validated['mentions'] ?? []);
            
        } catch (\Exception $e) {
            Log::error('Erreur Pusher: ' . $e->getMessage());
        }

        return response()->json([
            'status' => true,
            'message' => 'Commentaire ajouté avec succès',
            'data' => $comment
        ], 201);
    }

    private function sendPusherEvent(TaskNoteComment $comment, int $taskId, int $noteId, int $workspaceId, array $mentions = []): void
    {
        try {
            $pusher = new PusherClient(
                env('PUSHER_APP_KEY'),
                env('PUSHER_APP_SECRET'),
                env('PUSHER_APP_ID'),
                ['cluster' => 'eu', 'useTLS' => true]
            );
            
            $data = [
                'id' => $comment->id,
                'task_note_id' => $noteId,
                'task_id' => $taskId,
                'workspace_id' => $workspaceId,
                'user_id' => $comment->user_id,
                'user_name' => $comment->user->name ?? 'Utilisateur',
                'content' => $comment->content,
                'mentions' => $mentions,
                'created_at' => $comment->created_at->toISOString()
            ];
            
            $pusher->trigger('task-note-' . $taskId . '-' . $noteId, 'task.note.comment.created', $data);
            
        } catch (\Exception $e) {
            Log::error('Pusher error: ' . $e->getMessage());
        }
    }

    /**
     * SUPPRIMER UN COMMENTAIRE DE NOTE DE TÂCHE
     */
    public function destroyTaskNoteComment(int $workspaceId, int $boardId, int $columnId, int $taskId, int $noteId, int $commentId): JsonResponse
    {
        $comment = TaskNoteComment::findOrFail($commentId);
        
        if ($comment->user_id !== Auth::id()) {
            return response()->json([
                'status' => false,
                'message' => 'Non autorisé'
            ], 403);
        }
        
        $comment->delete();

        return response()->json([
            'status' => true,
            'message' => 'Commentaire supprimé avec succès'
        ]);
    }
}