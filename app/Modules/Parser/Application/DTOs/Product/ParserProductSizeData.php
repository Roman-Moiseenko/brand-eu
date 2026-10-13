<?php

declare(strict_types=1);

namespace App\Modules\Parser\Application\DTOs\Product;

use App\Modules\Parser\Domain\Entities\ParserProductSizeEntity;
use Spatie\LaravelData\Data;

/**
 * DTO для отображения размеров спарсенного товара (ParserProductSizes).
 */
class ParserProductSizeData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $size,
        public readonly string $sizeOriginal,
        public readonly string $sku,
        public readonly string $url,
        public readonly float $price,
        public readonly bool $available,
        /** @var array<int|string, mixed> */
        public readonly array $values = [],
    ) {}

    public static function fromEntity(ParserProductSizeEntity $size): self
    {
        return new self(
            id: $size->id ?? 0,
            size: $size->size,
            sizeOriginal: $size->sizeOriginal,
            sku: $size->sku,
            url: $size->url,
            price: $size->price,
            available: $size->available,
            values: $size->values,
        );
    }
}
