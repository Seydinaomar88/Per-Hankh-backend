<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Models\Workspace;
use App\Models\Note;
use App\Models\Task;
use App\Models\Comment;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Pusher\Pusher as PusherClient;

class CommentController extends Controller
{
    /**
     * LIST COMMENTS FOR NOTE
     */
    public function index(Workspace $workspace, Note $note): JsonResponse
    {
        $comments = Comment::where('note_id', $note->getKey())
            ->with('user')
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'comments' => $comments
        ]);
    }

    /**
     * LIST COMMENTS FOR TASK
     */
    public function indexTask(Workspace $workspace, $board, $column, Task $task): JsonResponse
    {
        $comments = Comment::where('task_id', $task->getKey())
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $comments
        ]);
    }

    /**
     * STORE COMMENT FOR NOTE
     */
    public function store(StoreCommentRequest $request, Workspace $workspace, Note $note): JsonResponse
    {
        $comment = Comment::create([
            'workspace_id' => $workspace->getKey(),
            'note_id' => $note->getKey(),
            'user_id' => Auth::id(),
            'content' => $request->content,
        ]);

        $this->handleMentions($comment->content, $workspace->getKey());

        return response()->json([
            'status' => true,
            'message' => 'Commentaire ajouté avec succès',
            'comment' => $comment->load('user')
        ]);
    }

    /**
     * STORE COMMENT FOR TASK
     */
    public function storeTask(StoreCommentRequest $request, Workspace $workspace, $board, $column, Task $task): JsonResponse
    {
        $comment = Comment::create([
            'workspace_id' => $workspace->getKey(),
            'task_id' => $task->getKey(),
            'user_id' => Auth::id(),
            'content' => $request->content,
        ]);

        $this->handleMentions($comment->content, $workspace->getKey());

        // Émettre un événement WebSocket pour le nouveau commentaire
        $this->broadcastNewComment($comment, $workspace->getKey());

        return response()->json([
            'status' => true,
            'message' => 'Commentaire ajouté avec succès',
            'data' => $comment->load('user')
        ]);
    }

    /**
     * SHOW COMMENT
     */
    public function show(Workspace $workspace, Note $note, Comment $comment): JsonResponse
    {
        return response()->json([
            'status' => true,
            'comment' => $comment
        ]);
    }

    /**
     * DELETE COMMENT FOR NOTE
     */
    public function destroy(Workspace $workspace, Note $note, Comment $comment): JsonResponse
    {
        $comment->delete();
        return response()->json([
            'status' => true,
            'message' => 'Commentaire supprimé avec succès'
        ]);
    }

    /**
     * DELETE COMMENT FOR TASK
     */
    public function destroyTask(Workspace $workspace, $board, $column, Task $task, Comment $comment): JsonResponse
    {
        $comment->delete();
        return response()->json([
            'status' => true,
            'message' => 'Commentaire supprimé avec succès'
        ]);
    }

    /**
     * GESTION DES MENTIONS
     */
    private function handleMentions(string $content, int $workspaceId): void
    {
        preg_match_all('/@([A-Za-z0-9_]+)/', $content, $matches);

        if (!empty($matches[1])) {
            foreach ($matches[1] as $username) {
                $mentionedUser = User::where('username', $username)->first();

                if ($mentionedUser) {
                    $notification = Notification::create([
                        'user_id' => $mentionedUser->getKey(),
                        'created_by' => Auth::id(),
                        'workspace_id' => $workspaceId,
                        'type' => 'mention',
                        'message' => Auth::user()->name . ' vous a mentionné dans un commentaire : "' . substr($content, 0, 100) . '"',
                    ]);
                    
                    // Diffuser la notification via WebSocket
                    $this->broadcastNotification($notification);
                }
            }
        }
    }

    /**
     * DIFFUSER UNE NOTIFICATION VIA WEBSOCKET
     */
    private function broadcastNotification(Notification $notification): void
    {
        try {
            $pusher = new PusherClient(
                env('PUSHER_APP_KEY'),
                env('PUSHER_APP_SECRET'),
                env('PUSHER_APP_ID'),
                ['cluster' => 'eu', 'useTLS' => true]
            );
            
            $pusher->trigger('public-channel', 'notification.new', [
                'id' => $notification->id,
                'message' => $notification->message,
                'created_at' => $notification->created_at->toIso8601String(),
                'is_read' => false
            ]);
            
            Log::info('Notification diffusée', ['id' => $notification->id]);
        } catch (\Exception $e) {
            Log::error('Erreur Pusher: ' . $e->getMessage());
        }
    }

    /**
     * DIFFUSER UN NOUVEAU COMMENTAIRE VIA WEBSOCKET
     */
    private function broadcastNewComment(Comment $comment, int $workspaceId): void
    {
        try {
            $pusher = new PusherClient(
                env('PUSHER_APP_KEY'),
                env('PUSHER_APP_SECRET'),
                env('PUSHER_APP_ID'),
                ['cluster' => 'eu', 'useTLS' => true]
            );
            
            $pusher->trigger('public-channel', 'new.comment', [
                'id' => $comment->id,
                'task_id' => $comment->task_id,
                'content' => $comment->content,
                'user_name' => $comment->user->name ?? Auth::user()->name,
                'user_id' => Auth::id(),
                'created_at' => $comment->created_at->toIso8601String()
            ]);
            
            Log::info('Commentaire diffusé', ['id' => $comment->id]);
        } catch (\Exception $e) {
            Log::error('Erreur Pusher: ' . $e->getMessage());
        }
    }
}