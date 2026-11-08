<?php

declare(strict_types=1);

namespace App\Modules\Parser\Application\DTOs\ScraperProduct;

use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class ScraperProductResultData extends Data
{
    /**
     * @param array<string, mixed> $product
     */
    public function __construct(
        #[Required, Numeric]
        public readonly int $id,
        #[Required, StringType]
        public readonly string $brand,
        #[Required]
        public readonly array $product,
        #[StringType]
        public readonly string $lang = 'pl',
    )
    {
    }
}
