<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['kind', 'pasted_text', 'summary', 'saved_count', 'unmatched_count'])]
class BimoniSharePoyLinkLog extends Model
{
    // Eloquentの既定の命名規則だと"Share"+"Poy"を別単語とみなし
    // bimoni_share_poy_link_logsと推測してしまうため明示する
    // （SharePoyUser/SharePoyDepositRecordモデルも同じ理由で$tableを明示している）。
    protected $table = 'bimoni_sharepoy_link_logs';

    protected function casts(): array
    {
        return [
            'summary' => 'array',
        ];
    }
}
