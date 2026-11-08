<?php

declare(strict_types=1);

namespace App\Modules\Parser\Domain\Interfaces;

use App\Modules\Parser\Domain\Entities\ScraperProductEntity;

interface ScraperProductRepositoryInterface
{
    public function save(ScraperProductEntity $product): ScraperProductEntity;

    public function getById(int $id): ScraperProductEntity;

    public function delete(int $id): void;

    public function findFirstByBrandId(int $brandId): ?ScraperProductEntity;
}
