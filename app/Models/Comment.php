<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $fillable = [
        'workspace_id',
        'note_id',
        'task_id',      // Ajout
        'user_id',
        'content',
    ];

    /**
     * WORKSPACE
     */
    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * NOTE
     */
    public function note()
    {
        return $this->belongsTo(Note::class);
    }

    /**
     * TASK (NOUVEAU)
     */
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * USER
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}