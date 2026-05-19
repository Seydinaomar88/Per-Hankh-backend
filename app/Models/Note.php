<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    protected $fillable = [

        'workspace_id',

        'created_by',

        'title',

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
     * CREATOR
     */
    public function creator()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function files()
    {
        return $this->hasMany(File::class);
    }
}
