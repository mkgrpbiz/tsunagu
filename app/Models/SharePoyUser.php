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
        return $this->hasMany(SharePoyDepositRecord::class, 'sharepoy_user_id');
    }

    /**
     * SharePoy+管理に該当ユーザーが見つからなかったContractを「確認済み・非マッチ」として
     * 記録しておくための受け皿ユーザー。次回集計で対象外にするために使う。
     */
    public static function unmatchedPlaceholder(): self
    {
        return static::firstOrCreate(
            ['sharepoy_user_id' => 'UNMATCHED'],
            ['name' => '非マッチ(確認済み)', 'name_kana' => '']
        );
    }
}
