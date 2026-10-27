<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Interfaces;

use App\Modules\Catalog\Domain\Entities\SizeGridEntity;

interface SizeGridRepositoryInterface
{
    /**
     * @return SizeGridEntity[]
     */
    public function getAll(): array;

    public function getById(int $id): SizeGridEntity;

    /**
     * @param int[] $ids
     * @return array<int, SizeGridEntity> id => SizeGridEntity
     */
    public function getByIds(array $ids): array;

    public function save(SizeGridEntity $sizeGrid): SizeGridEntity;

    public function delete(int $id): void;
}
