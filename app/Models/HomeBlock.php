<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['type', 'title', 'body', 'button_text', 'button_url', 'color', 'image_path', 'sort_order'])]
class HomeBlock extends Model
{
    public const TYPES = ['text', 'image', 'benefits', 'cta', 'referral_cta', 'collaboration_cta', 'sales_materials', 'announcements'];

    public const COLORS = ['gray', 'blue', 'orange', 'red'];
}
