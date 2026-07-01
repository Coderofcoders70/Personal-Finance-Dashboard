<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['user_id', 'avatar', 'currency', 'timezone', 'language', 'theme'])]

class Profile extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
