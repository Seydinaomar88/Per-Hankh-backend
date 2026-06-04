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
     * LIST COMMENTS FOR TASK (NOUVEAU)
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
            'message' => 'Comment created successfully',
            'comment' => $comment->load('user')
        ]);
    }

    /**
     * STORE COMMENT FOR TASK (NOUVEAU)
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
            'message' => 'Comment deleted successfully'
        ]);
    }

    /**
     * DELETE COMMENT FOR TASK (NOUVEAU)
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
                    Notification::create([
                        'user_id' => $mentionedUser->getKey(),
                        'created_by' => Auth::id(),
                        'workspace_id' => $workspaceId,
                        'type' => 'mention',
                        'message' => Auth::user()->name . ' vous a mentionné dans un commentaire',
                    ]);
                }
            }
        }
    }
}