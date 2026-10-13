<?php

namespace App\Modules\Parser\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $product_id ParserProduct
 * @property string $sizeOriginal;
 * @property string $size;
 * @property int $productSizeId; // id из товарного размера ProductSizeEntity
 * @property bool $available;
 * @property string $url;
 * @property string $sku;
 * @property float $price
 */
class ParserProductSizes extends Model
{

    public $timestamps = false;

}
