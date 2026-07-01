<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['user_id', 'ai_enabled', 'daily_reminder', 'notification_time'])]

class Setting extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
