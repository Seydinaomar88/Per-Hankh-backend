<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

use App\Http\Requests\StoreKanbanColumnRequest;

use App\Models\Workspace;
use App\Models\Board;
use App\Models\KanbanColumn;

class KanbanColumnController extends Controller
{
    public function store(
        StoreKanbanColumnRequest $request,
        Workspace $workspace,
        Board $board
    ) {

        $column = KanbanColumn::create([
            'board_id' => $board->id,
            'name' => $request->name,
            'position' => $request->position ?? 0,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Column created successfully',
            'column' => $column
        ]);
    }
}