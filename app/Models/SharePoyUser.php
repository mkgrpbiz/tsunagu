<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'sharepoy_user_id',
    'referrer_sharepoy_user_id',
    'name',
    'name_kana',
])]
class SharePoyUser extends Model
{
    protected $table = 'sharepoy_users';

    public function depositRecords(): HasMany
    {
        return $this->hasMany(SharePoyDepositRecord::class);
    }
}
