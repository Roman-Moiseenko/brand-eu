<?php

declare(strict_types=1);

namespace App\Modules\Parser\Application\DTOs\ScraperAvailable;

use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class ScraperAvailableNextRequestData extends Data
{
    public function __construct(
        #[Required, StringType]
        public readonly string $brand,
    )
    {
    }
}
