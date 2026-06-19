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

    // 🔥 CONSTANTES DE STATUT
    const STATUS_NOT_STARTED = 'not_started';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_REVIEW = 'review';
    const STATUS_DONE = 'done';

    const STATUSES = [
        self::STATUS_NOT_STARTED => 'Non commencée',
        self::STATUS_IN_PROGRESS => 'En cours',
        self::STATUS_REVIEW => 'En révision',
        self::STATUS_DONE => 'Terminée',
    ];

    const STATUS_COLORS = [
        self::STATUS_NOT_STARTED => 'bg-gray-100 text-gray-700',
        self::STATUS_IN_PROGRESS => 'bg-blue-100 text-blue-700',
        self::STATUS_REVIEW => 'bg-yellow-100 text-yellow-700',
        self::STATUS_DONE => 'bg-green-100 text-green-700',
    ];

    protected $fillable = [
        'kanban_column_id',
        'workspace_id',
        'created_by',
        'assigned_to',
        'title',
        'description',
        'due_date',
        'priority',
        'status', // 🔥 AJOUTER status
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

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function notes()
    {
        return $this->hasMany(TaskNote::class);
    }
}