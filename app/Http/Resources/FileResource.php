<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(
        Request $request
    ): array {

        return [

            'id' => $this->id,

            'original_name' =>
                $this->original_name,

            'file_name' =>
                $this->file_name,

            'mime_type' =>
                $this->mime_type,

            'size' =>
                $this->size,

            'size_kb' =>
                round($this->size / 1024, 2),

            'path' =>
                $this->path,

            /**
             * URL DOWNLOAD
             */
            'download_url' => url(
                '/api/v1/workspaces/' .
                $this->workspace_id .
                '/files/' .
                $this->id .
                '/download'
            ),

            'created_at' =>
                $this->created_at?->toDateTimeString(),

            /**
             * RELATIONS
             */
            'uploader' => [

                'id' =>
                    $this->uploader?->id,

                'name' =>
                    $this->uploader?->name,

                'username' =>
                    $this->uploader?->username,
            ],

            'task' => $this->task ? [

                'id' =>
                    $this->task->id,

                'title' =>
                    $this->task->title,

            ] : null,

            'note' => $this->note ? [

                'id' =>
                    $this->note->id,

                'title' =>
                    $this->note->title,

            ] : null,
        ];
    }
}