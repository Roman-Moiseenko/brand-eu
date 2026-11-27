<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Actions\Product;

use App\Modules\Catalog\Domain\Entities\ProductSizeEntity;
use App\Modules\Catalog\Domain\Entities\SizeGridEntity;
use App\Modules\Catalog\Domain\Entities\SizeGridValueEntity;
use App\Modules\Catalog\Domain\Interfaces\ProductSizeRepositoryInterface;
use App\Modules\Catalog\Domain\Interfaces\SizeGridRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObjects\SizeGridDimension;
use DomainException;

/**
 * Добавляет размер в товар. Если у сетки есть ось «Полнота» (width),
 * берётся первая полнота из списка.
 */
readonly class AddSizeToProductUseCase
{
    public function __construct(
        private SizeGridRepositoryInterface $sizeGridRepository,
        private ProductSizeRepositoryInterface $productSizeRepository,
    ) {
    }

    public function execute(int $sizeId, int $gridId, int $productId): ProductSizeEntity
    {
        $grid = $this->sizeGridRepository->getById($gridId);

        $sizeValue = $this->findValue($grid, $sizeId);

        $values = [SizeGridDimension::DIMENSION_SIZE => $sizeValue->value];

        if ($grid->hasDimension(SizeGridDimension::DIMENSION_WIDTH)) {
            $widthValues = $grid->getValuesByDimension(SizeGridDimension::DIMENSION_WIDTH);

            if ($widthValues !== []) {
                $values[SizeGridDimension::DIMENSION_WIDTH] = $widthValues[0]->value;
            }
        }

        return $this->productSizeRepository->save(new ProductSizeEntity(
            productId: $productId,
            values: $values,
            isActive: false,
        ));
    }

    private function findValue(SizeGridEntity $grid, int $valueId): SizeGridValueEntity
    {
        foreach ($grid->values as $value) {
            if ($value->id === $valueId) {
                return $value;
            }
        }

        throw new DomainException('Значение размерной сетки не найдено');
    }
}
