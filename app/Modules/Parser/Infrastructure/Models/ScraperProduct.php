<?php

declare(strict_types=1);

namespace App\Modules\Parser\Infrastructure\Models;

use App\Modules\Catalog\Infrastructure\Models\Brand;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $parser_category_id
 * @property int $brand_id
 * @property string $url
 *
 * @property ParserCategory $parserCategory
 * @property Brand $brand
 */
class ScraperProduct extends Model
{
    public $timestamps = false;

    protected $table = 'scraper_products';

    protected $fillable = [
        'parser_category_id',
        'brand_id',
        'url',
    ];

    protected $casts = [
        'parser_category_id' => 'integer',
        'brand_id' => 'integer',
    ];

    public function parserCategory(): BelongsTo
    {
        return $this->belongsTo(ParserCategory::class, 'parser_category_id', 'id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id', 'id');
    }
}
