<?php

declare(strict_types=1);

namespace App\Modules\Parser\Application\DTOs\ScraperCategory;

use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class ScraperCategoryResultData extends Data
{
    /**
     * @param array<int, array{sku?: string, url?: string}> $products
     */
    public function __construct(
        #[Required, StringType]
        public readonly string $brand,
        #[Required, Numeric]
        public readonly int $id,
        #[Required, BooleanType]
        public readonly bool $isLastPage,
        public readonly array $products = [],
        #[Nullable, StringType]
        public readonly ?string $nextPageUrl = null,
        #[Nullable]
        public readonly ?string $error = null,
    )
    {
    }
}
