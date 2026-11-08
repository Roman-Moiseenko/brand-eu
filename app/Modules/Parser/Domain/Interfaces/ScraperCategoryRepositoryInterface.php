<?php

declare(strict_types=1);

namespace App\Modules\Parser\Domain\Interfaces;

use App\Modules\Parser\Domain\Entities\ScraperCategoryEntity;

interface ScraperCategoryRepositoryInterface
{
    public function save(ScraperCategoryEntity $category): ScraperCategoryEntity;

    public function getById(int $id): ScraperCategoryEntity;

    public function delete(int $id): void;

    public function existsByBrandId(int $brandId): bool;

    public function findFirstByBrandId(int $brandId): ?ScraperCategoryEntity;
}
