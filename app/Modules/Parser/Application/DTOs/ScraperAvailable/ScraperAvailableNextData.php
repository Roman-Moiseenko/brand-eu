<?php

declare(strict_types=1);

namespace App\Modules\Parser\Application\DTOs\ScraperAvailable;

use App\Modules\Parser\Domain\Entities\ScraperAvailableEntity;
use Spatie\LaravelData\Data;

class ScraperAvailableNextData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $url,
    )
    {
    }

    public static function fromEntity(ScraperAvailableEntity $entity): self
    {
        return new self(
            id: $entity->id,
            url: $entity->url,
        );
    }
}
