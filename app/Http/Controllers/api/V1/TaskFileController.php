<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use App\Models\Board;
use App\Models\KanbanColumn;
use App\Models\Task;
use App\Models\File;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TaskFileController extends Controller
{
    protected CloudinaryService $cloudinaryService;

    public function __construct(CloudinaryService $cloudinaryService)
    {
        $this->cloudinaryService = $cloudinaryService;
    }

    public function index(Workspace $workspace, Board $board, KanbanColumn $column, Task $task)
    {
        if ($task->workspace_id !== $workspace->id) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $files = $task->files()->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => true,
            'data' => $files
        ]);
    }

    public function store(Request $request, Workspace $workspace, Board $board, KanbanColumn $column, Task $task)
    {
        try {
            if ($task->workspace_id !== $workspace->id) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
            }

            $request->validate([
                'file' => 'required|file|max:20480'
            ]);

            $uploadedFile = $request->file('file');
            
            // Upload vers Cloudinary
            $result = $this->cloudinaryService->upload($uploadedFile, 'tasks/' . $task->id);

            // Créer l'enregistrement en base
            $file = $task->files()->create([
                'workspace_id' => $workspace->id,
                'task_id' => $task->id,
                'uploaded_by' => Auth::id(),
                'original_name' => $uploadedFile->getClientOriginalName(),
                'file_name' => basename($result['public_id']),
                'mime_type' => $uploadedFile->getMimeType(),
                'size' => $result['bytes'],
                'path' => $result['secure_url'],
                'cloudinary_url' => $result['secure_url'],
                'cloudinary_public_id' => $result['public_id']
            ]);

            return response()->json([
                'status' => true,
                'data' => $file,
                'message' => 'Fichier uploadé avec succès'
            ], 201);

        } catch (\Exception $e) {
            Log::error('File upload error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erreur lors de l\'upload: ' . $e->getMessage()
            ], 500);
        }
    }

    public function download(Workspace $workspace, Board $board, KanbanColumn $column, Task $task, File $file)
    {
        try {
            if ($task->workspace_id !== $workspace->id) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
            }

            if ($file->task_id !== $task->id) {
                return response()->json(['status' => false, 'message' => 'File not found'], 404);
            }

            return response()->json([
                'status' => true,
                'download_url' => $file->cloudinary_url,
                'file_name' => $file->original_name
            ]);

        } catch (\Exception $e) {
            Log::error('Download error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erreur lors du téléchargement'
            ], 500);
        }
    }

    public function destroy(Workspace $workspace, Board $board, KanbanColumn $column, Task $task, File $file)
    {
        try {
            if ($task->workspace_id !== $workspace->id) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
            }

            if ($file->task_id !== $task->id) {
                return response()->json(['status' => false, 'message' => 'File not found'], 404);
            }

            // Supprimer de Cloudinary
            if ($file->cloudinary_public_id) {
                $this->cloudinaryService->delete($file->cloudinary_public_id);
            }
            
            $file->delete();
            
            return response()->json([
                'status' => true,
                'message' => 'Fichier supprimé avec succès'
            ]);

        } catch (\Exception $e) {
            Log::error('Delete error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erreur lors de la suppression'
            ], 500);
        }
    }
}