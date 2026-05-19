<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

use App\Http\Requests\StoreNoteRequest;

use App\Models\Workspace;
use App\Models\Note;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NoteController extends Controller
{
    /**
     * LIST NOTES
     */
    public function index(
        Workspace $workspace
    ): JsonResponse {

        $notes = Note::where(
            'workspace_id',
            $workspace->getKey()
        )
        ->latest()
        ->get();

        return response()->json([
            'status' => true,
            'notes' => $notes
        ]);
    }

    /**
     * STORE NOTE
     */
    public function store(
        StoreNoteRequest $request,
        Workspace $workspace
    ): JsonResponse {

        $note = Note::create([

            'workspace_id' => $workspace->getKey(),

            'created_by' => Auth::id(),

            'title' => $request->title,

            'content' => $request->content,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Note created successfully',
            'note' => $note
        ]);
    }

    /**
     * SHOW NOTE
     */
    public function show(
        Workspace $workspace,
        Note $note
    ): JsonResponse {

        return response()->json([
            'status' => true,
            'note' => $note
        ]);
    }

    /**
     * DELETE NOTE
     */
    public function destroy(
        Workspace $workspace,
        Note $note
    ): JsonResponse {

        $note->delete();

        return response()->json([
            'status' => true,
            'message' => 'Note deleted successfully'
        ]);
    }
}
