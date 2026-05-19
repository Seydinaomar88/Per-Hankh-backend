<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

use App\Http\Requests\StoreFileRequest;

use App\Models\Workspace;
use App\Models\Task;
use App\Models\Note;
use App\Models\File;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FileController extends Controller
{
    /**
     * LIST FILES
     */
    public function index(
        Workspace $workspace
    ): JsonResponse {

        $files = File::where(
            'workspace_id',
            $workspace->getKey()
        )
        ->latest()
        ->get();

        return response()->json([
            'status' => true,
            'files' => $files
        ]);
    }

    /**
     * UPLOAD FILE
     */
    public function store(
        StoreFileRequest $request,
        Workspace $workspace
    ): JsonResponse {

        $uploadedFile = $request->file('file');

        /**
         * stockage physique
         */
        $path = $uploadedFile->store(
            'uploads',
            'public'
        );

        /**
         * save DB
         */
        $file = File::create([

            'workspace_id' => $workspace->getKey(),

            /**
             * optionnel
             */
            'task_id' => $request->task_id,

            /**
             * optionnel
             */
            'note_id' => $request->note_id,

            'uploaded_by' => Auth::id(),

            'original_name' => $uploadedFile->getClientOriginalName(),

            'file_name' => basename($path),

            'mime_type' => $uploadedFile->getMimeType(),

            'size' => $uploadedFile->getSize(),

            'path' => $path,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'File uploaded successfully',
            'file' => $file
        ]);
    }

    /**
     * SHOW FILE
     */
    public function show(
        Workspace $workspace,
        File $file
    ): JsonResponse {

        return response()->json([
            'status' => true,
            'file' => $file
        ]);
    }

    /**
     * DOWNLOAD FILE
     */
    public function download(
        Workspace $workspace,
        File $file
    ): BinaryFileResponse {

        return response()->download(
            storage_path(
                'app/public/' . $file->path
            ),
            $file->original_name
        );
    }

    /**
     * DELETE FILE
     */
    public function destroy(
        Workspace $workspace,
        File $file
    ): JsonResponse {

        /**
         * delete physical file
         */
        if (
            Storage::disk('public')
                ->exists($file->path)
        ) {

            Storage::disk('public')
                ->delete($file->path);
        }

        /**
         * delete DB
         */
        $file->delete();

        return response()->json([
            'status' => true,
            'message' => 'File deleted successfully'
        ]);
    }
}