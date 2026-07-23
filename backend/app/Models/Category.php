<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'name', 'type', 'icon', 'color'])]

class Category extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function isDefault(): bool
    {
        return $this->user_id === null;
    }

    public function isCustom(): bool
    {
        return $this->user_id !== null;
    }

    public function scopeDefault(Builder $query): Builder
    {
        return $query->whereNull('user_id');
    }

    public function scopeCustom(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

}
