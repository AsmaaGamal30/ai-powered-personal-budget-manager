<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $guarded = [];

    public function users()
    {
        return $this->belongsToMany(User::class, 'category_users');
    }

    public function budgets()
    {
        return $this->hasManyThrough(Budget::class, CategoryUser::class);
    }
}