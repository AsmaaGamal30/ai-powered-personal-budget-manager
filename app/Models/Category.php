<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
