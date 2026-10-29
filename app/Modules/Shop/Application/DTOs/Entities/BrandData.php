<?php

namespace App\Modules\Shop\Application\DTOs\Entities;
class BrandData
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly string $image,
        public readonly string $description,

    )
    {
    }

}

