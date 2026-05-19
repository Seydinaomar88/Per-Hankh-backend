<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Models\User;
use App\Models\File;
use App\Models\Workspace;
use App\Models\KanbanColumn;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [

        'kanban_column_id',
        'workspace_id',
        'created_by',
        'assigned_to',

        'title',
        'description',

        'due_date',
        'priority',

        'position',

        'tags',
    ];

    protected $casts = [

        'tags' => 'array',

        'due_date' => 'datetime',
    ];

    /**
     * COLUMN
     */
    public function column()
    {
        return $this->belongsTo(
            KanbanColumn::class,
            'kanban_column_id'
        );
    }

    /**
     * WORKSPACE
     */
    public function workspace()
    {
        return $this->belongsTo(
            Workspace::class
        );
    }

    /**
     * CREATOR
     */
    public function creator()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    /**
     * ASSIGNED USER
     */
    public function assignedUser()
    {
        return $this->belongsTo(
            User::class,
            'assigned_to'
        );
    }

    /**
     * FILES
     */
    public function files()
    {
        return $this->hasMany(
            File::class
        );
    }
}