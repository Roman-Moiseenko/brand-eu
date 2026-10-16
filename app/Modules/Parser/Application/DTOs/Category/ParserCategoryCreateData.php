<?php

namespace App\Modules\Parser\Application\DTOs\Category;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class ParserCategoryCreateData extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public readonly string $name,
        #[Required, StringType, Max(255)]
        public readonly string $code,
        #[Required, Numeric]
        public readonly ?int   $brandId,
        #[Nullable, Numeric]
        public readonly ?int   $parentId,
        #[Nullable, StringType, Max(255)]
        public readonly string $url,
        #[Nullable, StringType, Max(255)]
        public readonly string $lang = 'pl',
    )
    {
    }
}
