<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Board;
use App\Models\Workspace;

class BoardController extends Controller
{
    /**
     * Liste des boards d’un workspace
     */
    public function index(Workspace $workspace)
    {
        $boards = $workspace->boards()->latest()->get();

        return response()->json([
            'status' => true,
            'boards' => $boards
        ]);
    }

    /**
     * Créer un board
     */
    public function store(Request $request, Workspace $workspace)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $board = $workspace->boards()->create($validated);

        return response()->json([
            'status' => true,
            'message' => 'Board created successfully',
            'board' => $board
        ], 201);
    }

    /**
     * Afficher un board
     */
    public function show(Workspace $workspace, Board $board)
    {
        return response()->json([
            'status' => true,
            'board' => $board
        ]);
    }

    /**
     * Modifier board
     */
    public function update(Request $request, Workspace $workspace, Board $board)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]);

        $board->update($validated);

        return response()->json([
            'status' => true,
            'message' => 'Board updated successfully',
            'board' => $board
        ]);
    }

    /**
     * Supprimer board
     */
    public function destroy(Workspace $workspace, Board $board)
    {
        $board->delete();

        return response()->json([
            'status' => true,
            'message' => 'Board deleted successfully'
        ]);
    }
}