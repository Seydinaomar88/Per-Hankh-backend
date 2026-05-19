<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'created_by',
        'type',
        'message',
        'is_read',
    ];

    /**
     * RECEIVER
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * AUTHOR
     */
    public function creator()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }
}
