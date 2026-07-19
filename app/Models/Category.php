<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'sort_order'])]
class Category extends Model
{
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
