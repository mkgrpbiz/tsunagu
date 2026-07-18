<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['tagline', 'hero_line1', 'hero_highlight', 'hero_suffix', 'steps_title', 'step1', 'step2', 'step3', 'benefits_title', 'benefits_body', 'cta_text', 'brand_badge_text'])]
class LandingPageContent extends Model
{
    protected $table = 'landing_page_content';

    public static function current(): self
    {
        return static::firstOrCreate([], [
            'tagline' => '人と事業をつなぎ、新たな価値を共創する',
            'hero_line1' => 'あなたのつながりが、',
            'hero_highlight' => '継続的な収益',
            'hero_suffix' => 'に変わる。',
            'steps_title' => 'ご参加の流れ',
            'step1' => '下のボタンから登録フォームへ',
            'step2' => 'プロフィールとパスワードを入力',
            'step3' => 'マイページから案件を選んで紹介開始',
            'benefits_title' => 'パートナーのメリット',
            'benefits_body' => '',
            'cta_text' => '無料で登録する',
            'brand_badge_text' => '審査制の共創型ビジネスプラットフォーム',
        ]);
    }
}
