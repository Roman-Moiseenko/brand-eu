<?php

namespace App\Modules\Parser\Application\DTOs\Product;

use Illuminate\Http\Request;
use Spatie\LaravelData\Data;

class ParserProductUpdateData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $name = null,
        public readonly ?string $code = null,
        public readonly ?string $slug = null,
        public readonly ?string $url = null,
        public readonly ?float $priceSell = null,
        public readonly ?float $priceBase = null,
        public readonly ?string $short = null,
        public readonly ?string $description = null,
        public readonly ?bool $availability = null,

    )
    {
    }
}
