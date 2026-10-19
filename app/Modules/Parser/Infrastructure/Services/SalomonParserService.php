<?php

namespace App\Modules\Parser\Infrastructure\Services;

use App\Modules\Parser\Application\Interfaces\ParserServiceInterface;
use App\Modules\Parser\Domain\Entities\ParserProductEntity;

class SalomonParserService implements ParserServiceInterface
{

    public function getListCategories(): array
    {
        return [' --- '];
    }

    public function addCategory($categoryData, ?int $parent_id = null): bool
    {
        // TODO: Implement addCategory() method.
    }

    public function GetListProductsByCategory(string $categoryUrl): array
    {
        // TODO: Implement GetListProductsByCategory() method.
    }

    public function GetParserProduct($productData, $categoryId)
    {
        // TODO: Implement GetParserProduct() method.
    }

    public function GetAvailability($productCate): void
    {
        // TODO: Implement GetAvailability() method.
    }
}
