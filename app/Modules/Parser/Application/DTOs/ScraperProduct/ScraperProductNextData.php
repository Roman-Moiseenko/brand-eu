<?php

declare(strict_types=1);

namespace App\Modules\Parser\Application\DTOs\ScraperProduct;

use App\Modules\Parser\Domain\Entities\ScraperProductEntity;
use Spatie\LaravelData\Data;

class ScraperProductNextData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $url,
    )
    {
    }

    public static function fromEntity(ScraperProductEntity $entity): self
    {
        return new self(
            id: $entity->id,
            url: $entity->url,
        );
    }
}
