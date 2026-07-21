<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

// Other Models:
use App\Models\Transaction;
use App\Models\Category;
use App\Models\Notification;

#[Fillable(['name', 'email', 'password', 'avatar', 'currency', 'timezone', 'language', 'theme', 'ai_enabled', 'daily_reminder', 'notification_time'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',

            'ai_enabled' => 'boolean',
            'daily_reminder' => 'boolean',
            'notification_time' => 'datetime:H:i',
        ];
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
