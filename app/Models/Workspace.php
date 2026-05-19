<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Workspace extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'owner_id'
    ];

    /**
     * Membres du workspace
     */
    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Propriétaire
     */
    public function owner()
    {
        return $this->belongsTo(
            User::class,
            'owner_id'
        );
    }

    /**
     * Boards
     */
    public function boards()
    {
        return $this->hasMany(Board::class);
    }

    /**
     * Files
     */
    public function files()
    {
        return $this->hasMany(File::class);
    }
}