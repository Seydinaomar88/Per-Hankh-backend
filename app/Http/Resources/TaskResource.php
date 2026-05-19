<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(
        Request $request
    ): array {

        return [

            'id' => $this->id,

            'title' => $this->title,

            'description' => $this->description,

            'priority' => $this->priority,

            'due_date' => $this->due_date,

            'position' => $this->position,

            'tags' => $this->tags,

            /**
             * DATES FORMATÉES
             */
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),

            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),

            /**
             * FILES COUNT
             */
            'files_count' => $this->files->count(),

            /**
             * RELATIONS
             */
            'creator' => [

                'id' =>
                    $this->creator?->id,

                'name' =>
                    $this->creator?->name,

                'username' =>
                    $this->creator?->username,
            ],

            'assigned_user' => [

                'id' =>
                    $this->assignedUser?->id,

                'name' =>
                    $this->assignedUser?->name,

                'username' =>
                    $this->assignedUser?->username,
            ],

            /**
             * FILES
             */
            'files' => $this->files->map(function ($file) {

                return [

                    'id' => $file->id,

                    'original_name' =>
                        $file->original_name,

                    'mime_type' =>
                        $file->mime_type,

                    'size' =>
                        $file->size,

                    'path' =>
                        $file->path,

                    /**
                     * URL COMPLETE
                     */
                    'url' => asset(
                        'storage/' . $file->path
                    ),

                    'created_at' =>
                        $file->created_at?->format(
                            'Y-m-d H:i:s'
                        ),
                ];
            }),
        ];
    }
}