<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'company_name',
    'logo_path',
    'representative_title',
    'representative_name',
    'address',
    'business_description',
    'services',
    'email',
    'business_hours',
])]
class CompanyProfile extends Model
{
    protected $table = 'company_profile';

    public static function current(): self
    {
        return static::firstOrCreate([], [
            'company_name' => 'MKグループ株式会社',
            'representative_title' => '代表取締役',
            'representative_name' => '黒川 真至',
            'address' => '大阪府大阪市中央区本町4-8-1',
            'business_description' => "・インターネットサービスの企画・開発・運営\n・広告・マーケティング事業\n・システム開発事業",
            'services' => "・SharePoy\n・TSUNAGU",
            'email' => 'info@mkgrp.biz',
            'business_hours' => "平日 9:30～18:00\n（土日祝日・年末年始を除く）",
        ]);
    }
}
