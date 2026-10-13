<?php

namespace App\Modules\Parser\Infrastructure\Models;

use App\Modules\Accounting\Entity\Currency;
use App\Modules\Catalog\Infrastructure\Models\Product;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $url
 * @property int $brand_id
 * @property int $product_id
 * @property string $code
 * @property float $price_base
 * @property float $price_sell
 *
 * @property string $maker_id id в магазине Бренда
 *
 * @property string $data - чистый json  с оригинальными данными
 *
 * @property bool $availability - Можно или нет возить под заказ
 *
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property ParserProductSizes[] $sizes
 * @property Product $product
 * @property ParserCategory[] $categories
 */
class ParserProduct extends Model
{
    protected $touches = ['categories'];

    public $timestamps = true;
    protected $table = 'parser_products';

    protected $casts = [
        'price_base' => 'float',
        'price_sell' => 'float',
        'availability' => 'boolean',
    ];

    protected $fillable = [
        'url',
        'product_id',
        'brand_id',
        'maker_id',
        'data',
        'name',
        'code',
        'price_base',
        'price_sell',
        'availability',
    ];

    public static function register(string $url, int $product_id): self
    {
        return self::create([
            'url' => $url,
            'product_id' => $product_id,
        ]);
    }

    public function category(): ?ParserCategory
    {
        return $this->categories()->first();
    }

    public function sizes(): HasMany
    {
        return $this->hasMany(ParserProductSizes::class, 'product_id', 'id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            ParserCategory::class,
            'parser_categories_products',
            'product_id', 'category_id'
        );
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function currency(): Currency
    {
        /** @var ParserCategory $category */
        $category = $this->categories()->first();
        return $category->brand->currency->first();
    }
}
