<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['hero_tagline', 'closing_message', 'brand_logo_path'])]
class HomePageContent extends Model
{
    protected $table = 'home_page_content';

    public static function current(): self
    {
        return static::firstOrCreate([], [
            'hero_tagline' => '人と事業をつなぎ、新たな価値を共創する',
            'closing_message' => "つなぐだけで、価値がひろがり、収益がつづく。\nそれが、TSUNAGUのパートナーです。",
        ]);
    }
}
