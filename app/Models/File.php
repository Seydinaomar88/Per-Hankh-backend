<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    protected $fillable = [

        'workspace_id',

        'task_id',

        'note_id',

        'uploaded_by',

        'original_name',

        'file_name',

        'mime_type',

        'size',

        'path',
    ];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function note()
    {
        return $this->belongsTo(Note::class);
    }

    public function uploader()
    {
        return $this->belongsTo(
            User::class,
            'uploaded_by'
        );
    }
}