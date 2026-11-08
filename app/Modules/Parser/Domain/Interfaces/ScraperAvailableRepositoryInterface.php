<?php

declare(strict_types=1);

namespace App\Modules\Parser\Domain\Interfaces;

use App\Modules\Parser\Domain\Entities\ScraperAvailableEntity;

interface ScraperAvailableRepositoryInterface
{
    public function save(ScraperAvailableEntity $available): ScraperAvailableEntity;

    public function getById(int $id): ScraperAvailableEntity;

    public function delete(int $id): void;

    public function findFirstByBrandId(int $brandId): ?ScraperAvailableEntity;

    public function existsByBrandId(int $brandId): bool;
}
