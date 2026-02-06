<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $guarded = [];

    public function budgets()
    {
        return $this->hasMany(Budget::class);
    }

    public function stats()
    {
        return $this->hasMany(Stats::class);
    }
}