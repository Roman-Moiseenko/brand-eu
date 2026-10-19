<?php

declare(strict_types=1);

namespace App\Modules\Parser\Application\Actions\Product;

use App\Modules\Parser\Application\DTOs\Product\ParserProductUpdateData;
use App\Modules\Parser\Domain\Entities\ParserProductEntity;
use App\Modules\Parser\Domain\Interfaces\ParserProductRepositoryInterface;
use App\Modules\Parser\Domain\ValueObjects\Package;
use App\Modules\Shared\Domain\ValueObjects\Slug;

class UpdateParserProductUseCase
{
    public function __construct(
        private ParserProductRepositoryInterface $productRepository,
    )
    {
    }

    public function execute(ParserProductUpdateData $dto):? ParserProductEntity
    {
        // Получаем текущую сущность
        $product = $this->productRepository->getById($dto->id);

        // Обновляем только те поля, которые переданы в DTO (не null)
        if ($dto->name !== null) {
            $product->name = $dto->name;
        }
        if ($dto->code !== null) {
            $product->code = $dto->code;
        }

        if ($dto->url !== null) {
            $product->url = $dto->url;
        }
        if ($dto->priceSell !== null) {
            $product->priceSell = $dto->priceSell;
        }
        if ($dto->priceBase !== null) {
            $product->priceBase = $dto->priceBase;
        }

        if ($dto->availability !== null) {
            $product->availability = $dto->availability;
        }


        return $this->productRepository->save($product);
    }
}
