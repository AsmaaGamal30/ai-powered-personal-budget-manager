<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stats extends Model
{
    protected $guarded = [];

    public function categoryUser()
    {
        return $this->belongsTo(CategoryUser::class, 'user_category_id');
    }

    public function user()
    {
        return $this->categoryUser->user();
    }


}
