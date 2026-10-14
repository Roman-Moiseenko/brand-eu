<?php

declare(strict_types=1);

namespace App\Modules\Parser\Application\DTOs\Category;

use App\Modules\Parser\Domain\Entities\ParserCategoryEntity;
use Spatie\LaravelData\Data;

class ParserCategoryIndexData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly bool $active,
        public readonly string $code,
        public readonly ?int $categoryId = null,
        public readonly ?int $sizeGridId = null,
        /** @var ParserCategoryIndexData[] */
        public readonly array $children = [],
    ) {}

    public static function fromEntity(ParserCategoryEntity $category): self
    {
        return new self(
            id: $category->id,
            name: $category->name,
            active: $category->active,
            code: $category->code,
            categoryId: $category->categoryId,
            sizeGridId: $category->sizeGridId,
            children: array_map(
                fn(ParserCategoryEntity $child) => self::fromEntity($child),
                $category->children
            ),
        );
    }
}
