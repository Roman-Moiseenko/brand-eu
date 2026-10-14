<?php

namespace App\Modules\Parser\Infrastructure\Services;

use App\Modules\Parser\Application\Interfaces\ParserServiceInterface;
use App\Modules\Parser\Domain\Entities\ParserProductEntity;

class AdidasParserService implements ParserServiceInterface
{

    public function getListCategories(): array
    {
        return [' --- '];
    }

    public function addCategory($categoryData, ?int $parent_id = null): void
    {
        // TODO: Implement addCategory() method.
    }

    public function GetListProductsByCategory(string $categoryCode): array
    {
        // TODO: Implement GetListProductsByCategory() method.
    }

    public function GetParserProduct($productData): ?ParserProductEntity
    {
        // TODO: Implement GetParserProduct() method.
    }

    public function GetAvailability($productCate): void
    {
        // TODO: Implement GetAvailability() method.
    }
}
