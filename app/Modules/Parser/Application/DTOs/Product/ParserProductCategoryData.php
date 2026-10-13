<?php

declare(strict_types=1);

namespace App\Modules\Parser\Application\DTOs\Product;

use App\Modules\Parser\Domain\Entities\ParserProductEntity;
use App\Modules\Parser\Domain\Entities\ParserProductSizeEntity;
use Spatie\LaravelData\Data;

/**
 * DTO для списка товаров категории (на странице Show).
 * Содержит все поля товара, кроме изображений, дат и $data.
 */
class ParserProductCategoryData extends Data
{
    public function __construct(
        public readonly int     $id,
        public readonly string  $code,
        public readonly string  $name,
        public readonly string  $url,
        public readonly ?int    $brandId,
        public readonly ?int    $productId,
        public readonly string  $makerId,
        public readonly float   $priceSell,
        public readonly float   $priceBase,
        public readonly bool    $availability,
        /** @var ParserProductSizeData[] */
        public readonly array   $sizes = [],
    )
    {
    }

    public static function fromEntity(ParserProductEntity $product): self
    {
        return new self(
            id: $product->id ?? 0,
            code: $product->code,
            name: $product->name,
            url: $product->url,
            brandId: $product->brandId,
            productId: $product->productId,
            makerId: $product->makerId,
            priceSell: $product->priceSell,
            priceBase: $product->priceBase,
            availability: $product->availability,
            sizes: array_map(
                fn(ParserProductSizeEntity $size) => ParserProductSizeData::fromEntity($size),
                $product->sizes
            ),
        );
    }
}
