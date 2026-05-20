<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;

use App\Http\Requests\StoreFileRequest;

use App\Http\Resources\FileResource;

use App\Models\Workspace;
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

        $files = File::with([
            'uploader',
            'task',
            'note'
        ])
        ->where(
            'workspace_id',
            $workspace->getKey()
        )
        ->latest()
        ->get();

        return response()->json([

            'status' => true,

            'files' =>
                FileResource::collection($files)
        ]);
    }

    /**
     * UPLOAD FILE
     */
    public function store(
        StoreFileRequest $request,
        Workspace $workspace
    ): JsonResponse {

        /**
         * SECURITY
         * vérifier task appartient workspace
         */
        if ($request->task_id) {

            $taskExists = $workspace
                ->tasks()
                ->where('id', $request->task_id)
                ->exists();

            if (!$taskExists) {

                return response()->json([

                    'status' => false,

                    'message' =>
                        'Task does not belong to workspace'

                ], 422);
            }
        }

        /**
         * SECURITY
         * vérifier note appartient workspace
         */
        if ($request->note_id) {

            $noteExists = $workspace
                ->notes()
                ->where('id', $request->note_id)
                ->exists();

            if (!$noteExists) {

                return response()->json([

                    'status' => false,

                    'message' =>
                        'Note does not belong to workspace'

                ], 422);
            }
        }

        $uploadedFile = $request->file('file');

        /**
         * STORAGE
         */
        $path = $uploadedFile->store(
            'uploads',
            'public'
        );

        /**
         * SAVE DB
         */
        $file = File::create([

            'workspace_id' =>
                $workspace->getKey(),

            'task_id' =>
                $request->task_id,

            'note_id' =>
                $request->note_id,

            'uploaded_by' =>
                Auth::id(),

            'original_name' =>
                $uploadedFile->getClientOriginalName(),

            'file_name' =>
                basename($path),

            'mime_type' =>
                $uploadedFile->getMimeType(),

            'size' =>
                $uploadedFile->getSize(),

            'path' =>
                $path,
        ]);

        /**
         * LOAD RELATIONS
         */
        $file->load([
            'uploader',
            'task',
            'note'
        ]);

        return response()->json([

            'status' => true,

            'message' =>
                'File uploaded successfully',

            'file' =>
                new FileResource($file)

        ], 201);
    }

    /**
     * SHOW FILE
     */
    public function show(
        Workspace $workspace,
        File $file
    ): JsonResponse {

        /**
         * SECURITY
         */
        if (
            $file->workspace_id !==
            $workspace->id
        ) {

            return response()->json([

                'status' => false,

                'message' =>
                    'Unauthorized file'

            ], 403);
        }

        $file->load([
            'uploader',
            'task',
            'note'
        ]);

        return response()->json([

            'status' => true,

            'file' =>
                new FileResource($file)
        ]);
    }

    /**
     * DOWNLOAD FILE
     */
    public function download(
        Workspace $workspace,
        File $file
    ): BinaryFileResponse {

        /**
         * SECURITY
         */
        if (
            $file->workspace_id !==
            $workspace->id
        ) {

            abort(403, 'Unauthorized file');
        }

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
         * SECURITY
         */
        if (
            $file->workspace_id !==
            $workspace->id
        ) {

            return response()->json([

                'status' => false,

                'message' =>
                    'Unauthorized file'

            ], 403);
        }

        /**
         * DELETE PHYSICAL FILE
         */
        if (
            Storage::disk('public')
                ->exists($file->path)
        ) {

            Storage::disk('public')
                ->delete($file->path);
        }

        /**
         * DELETE DB
         */
        $file->delete();

        return response()->json([

            'status' => true,

            'message' =>
                'File deleted successfully'
        ]);
    }
}