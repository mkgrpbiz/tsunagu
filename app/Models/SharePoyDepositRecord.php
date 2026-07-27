<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sharepoy_user_id',
    'inquiry_id',
    'contract_id',
    'source',
    'deposit_date',
    'tsunagu_unit_price',
    'agency_unit_price',
    'count',
    'memo',
])]
class SharePoyDepositRecord extends Model
{
    private const SOURCE_LABELS = [
        'bimoni_sharepoy' => 'BIMONI(SharePoy)',
        'product_monitor' => '商品受け取りモニター',
        'mystery_shopper' => '覆面調査モニター',
    ];

    protected $table = 'sharepoy_deposit_records';

    protected function casts(): array
    {
        return [
            'deposit_date' => 'date',
        ];
    }

    public function sourceLabel(): string
    {
        return self::SOURCE_LABELS[$this->source] ?? $this->source;
    }

    public function sharePoyUser(): BelongsTo
    {
        return $this->belongsTo(SharePoyUser::class, 'sharepoy_user_id');
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
}
