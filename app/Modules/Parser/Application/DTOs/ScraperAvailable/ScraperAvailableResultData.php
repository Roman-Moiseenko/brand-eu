<?php

declare(strict_types=1);

namespace App\Modules\Parser\Application\DTOs\ScraperAvailable;

use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class ScraperAvailableResultData extends Data
{
    /**
     * @param array<int, array{size?: string, sku?: string, available?: bool, price?: float|int|null}> $sizes
     */
    public function __construct(
        #[Required, Numeric]
        public readonly int $id,
        #[Required, StringType]
        public readonly string $brand,
        #[Nullable, Numeric]
        public readonly ?float $priceBase = null,
        #[Nullable, Numeric]
        public readonly ?float $priceSell = null,
        public readonly array $sizes = [],
    )
    {
    }
}
