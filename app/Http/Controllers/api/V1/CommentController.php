<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

use App\Http\Requests\StoreCommentRequest;

use App\Models\Workspace;
use App\Models\Note;
use App\Models\Comment;
use App\Models\User;
use App\Models\Notification;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    /**
     * LIST COMMENTS
     */
    public function index(
        Workspace $workspace,
        Note $note
    ): JsonResponse {

        $comments = Comment::where(
            'note_id',
            $note->getKey()
        )
        ->with('user')
        ->latest()
        ->get();

        return response()->json([
            'status' => true,
            'comments' => $comments
        ]);
    }

    /**
     * STORE COMMENT
     */
    public function store(
        StoreCommentRequest $request,
        Workspace $workspace,
        Note $note
    ): JsonResponse {

        $comment = Comment::create([

            'workspace_id' => $workspace->getKey(),

            'note_id' => $note->getKey(),

            'user_id' => Auth::id(),

            'content' => $request->content,
        ]);

        /**
         * DETECT @mentions
         */
        preg_match_all(
            '/@([A-Za-z0-9_]+)/',
            $comment->content,
            $matches
        );

        /**
         * CREATE NOTIFICATIONS
         */
        if (!empty($matches[1])) {

            foreach ($matches[1] as $username) {

                $mentionedUser = User::where(
                    'username',
                    $username
                )->first();

                if ($mentionedUser) {

                    Notification::create([

                        'user_id' => $mentionedUser->getKey(),

                        'created_by' => Auth::id(),

                        'type' => 'mention',

                        'message' => Auth::user()->name .
                            ' mentioned you in a comment',
                    ]);
                }
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Comment created successfully',
            'comment' => $comment
        ]);
    }

    /**
     * SHOW COMMENT
     */
    public function show(
        Workspace $workspace,
        Note $note,
        Comment $comment
    ): JsonResponse {

        return response()->json([
            'status' => true,
            'comment' => $comment
        ]);
    }

    /**
     * DELETE COMMENT
     */
    public function destroy(
        Workspace $workspace,
        Note $note,
        Comment $comment
    ): JsonResponse {

        $comment->delete();

        return response()->json([
            'status' => true,
            'message' => 'Comment deleted successfully'
        ]);
    }
}
