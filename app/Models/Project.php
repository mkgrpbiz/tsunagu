<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'category_id',
    'sort_order',
    'name',
    'legacy_names',
    'description',
    'image_path',
    'status',
    'oshigoto_listed',
    'client_name',
    'referrer_agency_id',
    'tsunagu_unit_prices',
    'agency_unit_prices',
    'payment_timing',
    'recruitment_template',
    'line_auto_message',
])]
class Project extends Model
{
    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'oshigoto_listed' => 'boolean',
            'tsunagu_unit_prices' => 'array',
            'agency_unit_prices' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function referrerAgency(): BelongsTo
    {
        return $this->belongsTo(Agency::class, 'referrer_agency_id');
    }

    public function inviteLinks(): HasMany
    {
        return $this->hasMany(InviteLink::class);
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }

    public function singleAgencyUnitPrice(): ?int
    {
        return count($this->agency_unit_prices ?? []) === 1 ? $this->agency_unit_prices[0] : null;
    }

    public function singleTsunaguUnitPrice(): ?int
    {
        return count($this->tsunagu_unit_prices ?? []) === 1 ? $this->tsunagu_unit_prices[0] : null;
    }

    public function formattedTsunaguUnitPrices(): string
    {
        return self::formatUnitPrices($this->tsunagu_unit_prices);
    }

    public function formattedAgencyUnitPrices(): string
    {
        return self::formatUnitPrices($this->agency_unit_prices);
    }

    private static function formatUnitPrices(?array $prices): string
    {
        if (empty($prices)) {
            return '変動';
        }

        return collect($prices)->map(fn (int $price) => '¥'.number_format($price))->implode(' / ');
    }

    public function legacyNamesList(): array
    {
        return collect(explode("\n", (string) $this->legacy_names))
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }

    public static function findByAnyName(string $name): ?self
    {
        $name = trim($name);

        return static::where('name', $name)->first()
            ?? static::whereNotNull('legacy_names')
                ->get()
                ->first(fn (self $project) => in_array($name, $project->legacyNamesList(), true));
    }
}
