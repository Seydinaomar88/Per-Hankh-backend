<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskNoteComment extends Model
{
    protected $table = 'task_note_comments';
    
    protected $fillable = [
        'task_note_id',
        'user_id',
        'content',
    ];

    public function taskNote()
    {
        return $this->belongsTo(TaskNote::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}