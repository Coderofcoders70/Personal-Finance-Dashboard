<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'event', 'title', 'message', 'type', 'is_read'])]

class Notification extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
