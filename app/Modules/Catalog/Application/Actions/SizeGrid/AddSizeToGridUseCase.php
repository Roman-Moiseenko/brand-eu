<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Actions\SizeGrid;

use App\Modules\Catalog\Domain\Entities\SizeGridEntity;
use App\Modules\Catalog\Domain\Entities\SizeGridValueEntity;
use App\Modules\Catalog\Domain\Interfaces\SizeGridRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObjects\SizeGridDimension;
use DomainException;

/**
 * Добавляет новый размер в размерную сетку.
 */
readonly class AddSizeToGridUseCase
{
    public function __construct(
        private SizeGridRepositoryInterface $sizeGridRepository,
    ) {
    }

    public function execute(int $sizeGridId, string $size): SizeGridValueEntity
    {
        $grid = $this->sizeGridRepository->getById($sizeGridId);

        $grid->addValue(new SizeGridValueEntity(
            dimensionCode: SizeGridDimension::DIMENSION_SIZE,
            value: $size,
            sortOrder: $this->nextSortOrder($grid),
        ));

        $grid = $this->sizeGridRepository->save($grid);

        foreach ($grid->getValuesByDimension(SizeGridDimension::DIMENSION_SIZE) as $value) {
            if ($value->value === $size) return $value;
        }

        throw new DomainException('Не удалось добавить размер в сетку');
    }

    private function nextSortOrder(SizeGridEntity $grid): int
    {
        $max = 0;

        foreach ($grid->getValuesByDimension(SizeGridDimension::DIMENSION_SIZE) as $value) {
            $max = max($max, $value->sortOrder);
        }

        return $max + 1;
    }
}
