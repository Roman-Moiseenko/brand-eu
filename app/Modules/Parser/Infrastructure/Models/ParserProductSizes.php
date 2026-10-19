<?php

namespace App\Modules\Parser\Infrastructure\Models;

use App\Modules\Catalog\Infrastructure\Models\ProductSize;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_id ParserProduct
 * @property string $size_original;
 * @property string $size;
 * @property int $product_size_id; // id из товарного размера ProductSizeEntity
 * @property bool $available;
 * @property string $url;
 * @property string $sku;
 * @property float $price
 *
 * @property ParserProduct $product
 * @property ProductSize $productSize
 */
class ParserProductSizes extends Model
{
    public $timestamps = false;

    protected $table = 'parser_product_sizes';

    protected $fillable = [
        'product_id',
        'size_original',
        'size',
        'product_size_id',
        'available',
        'url',
        'sku',
        'price',
    ];

    protected $casts = [
        'available' => 'boolean',
        'price' => 'float',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(ParserProduct::class, 'product_id', 'id');
    }

    public function productSize(): BelongsTo
    {
        return $this->belongsTo(ProductSize::class, 'product_size_id', 'id');
    }
}
