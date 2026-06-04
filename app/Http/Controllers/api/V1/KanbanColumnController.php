<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKanbanColumnRequest;
use App\Models\Workspace;
use App\Models\Board;
use App\Models\KanbanColumn;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class KanbanColumnController extends Controller
{
    /**
     * LIST COLUMNS
     */
    public function index(Workspace $workspace, Board $board): JsonResponse
    {
        $columns = KanbanColumn::where("board_id", $board->id)
            ->orderBy("position")
            ->get();

        return response()->json([
            "status" => true,
            "data" => $columns
        ]);
    }

    /**
     * SHOW COLUMN
     */
    public function show(Workspace $workspace, Board $board, KanbanColumn $column): JsonResponse
    {
        return response()->json([
            "status" => true,
            "data" => $column
        ]);
    }

    /**
     * CREATE COLUMN
     */
    public function store(StoreKanbanColumnRequest $request, Workspace $workspace, Board $board): JsonResponse
    {
        $column = KanbanColumn::create([
            "board_id" => $board->id,
            "name" => $request->name,
            "position" => $request->position ?? 0,
        ]);

        return response()->json([
            "status" => true,
            "message" => "Column created successfully",
            "data" => $column
        ], 201);
    }

    /**
     * UPDATE COLUMN
     */
    public function update(Request $request, Workspace $workspace, Board $board, KanbanColumn $column): JsonResponse
    {
        $request->validate([
            "name" => "sometimes|string|max:255",
            "position" => "sometimes|integer"
        ]);

        $column->update($request->only(["name", "position"]));

        return response()->json([
            "status" => true,
            "message" => "Column updated successfully",
            "data" => $column
        ]);
    }

    /**
     * DELETE COLUMN
     */
    public function destroy(Workspace $workspace, Board $board, KanbanColumn $column): JsonResponse
    {
        $column->delete();

        return response()->json([
            "status" => true,
            "message" => "Column deleted successfully"
        ]);
    }
}
