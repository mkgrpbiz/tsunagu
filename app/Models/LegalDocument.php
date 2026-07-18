<?php

namespace App\Models;

use App\Enums\LegalDocumentStatus;
use App\Enums\LegalDocumentType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'type',
    'title',
    'body',
    'version',
    'status',
    'effective_date',
    'requires_reconsent',
    'change_notes',
    'published_at',
    'created_by_user_id',
])]
class LegalDocument extends Model
{
    protected function casts(): array
    {
        return [
            'type' => LegalDocumentType::class,
            'status' => LegalDocumentStatus::class,
            'effective_date' => 'date',
            'requires_reconsent' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function consents(): HasMany
    {
        return $this->hasMany(LegalDocumentConsent::class);
    }

    public static function currentPublished(LegalDocumentType $type): ?self
    {
        return static::where('type', $type)
            ->where('status', LegalDocumentStatus::Published)
            ->orderByDesc('effective_date')
            ->orderByDesc('created_at')
            ->first();
    }

    public static function latestVersion(LegalDocumentType $type): ?self
    {
        return static::where('type', $type)
            ->latest('created_at')
            ->first();
    }
}
