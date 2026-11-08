<?php

declare(strict_types=1);

namespace App\Modules\Parser\Application\DTOs\ScraperCategory;

use App\Modules\Parser\Domain\Entities\ScraperCategoryEntity;
use Spatie\LaravelData\Data;

class ScraperCategoryNextData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $url,
    )
    {
    }

    public static function fromEntity(ScraperCategoryEntity $entity): self
    {
        return new self(
            id: $entity->id,
            url: $entity->url,
        );
    }
}
