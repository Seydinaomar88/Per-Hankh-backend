<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskNote extends Model
{
    protected $table = 'task_notes';
    
    protected $fillable = [
        'task_id',
        'created_by',
        'title',
        'content',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function comments()
    {
        return $this->hasMany(TaskNoteComment::class);
    }
}