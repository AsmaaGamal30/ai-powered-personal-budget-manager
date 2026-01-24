<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryUser extends Model
{
    protected $guarded = [];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function budgets()
    {
        return $this->hasMany(Budget::class);
    }

    public function stats()
    {
        return $this->hasMany(Stats::class, 'user_category_id');
    }
}
