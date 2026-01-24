<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    protected $guarded = [];

    public function categoryUser()
    {
        return $this->belongsTo(CategoryUser::class);
    }

    public function user()
    {
        return $this->categoryUser->user();
    }

    public function category()
    {
        return $this->categoryUser->category();
    }
}
