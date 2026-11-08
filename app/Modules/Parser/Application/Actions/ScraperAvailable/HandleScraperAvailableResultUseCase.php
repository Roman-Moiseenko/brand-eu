<?php

declare(strict_types=1);

namespace App\Modules\Parser\Application\Actions\ScraperAvailable;

use App\Modules\Catalog\Application\Actions\Product\EditPage\SetProductSizesUseCase;
use App\Modules\Catalog\Application\DTOs\Product\EditPage\UpdateProductSizesData;
use App\Modules\Catalog\Domain\Entities\ProductSizeEntity;
use App\Modules\Catalog\Domain\Interfaces\ProductRepositoryInterface;
use App\Modules\Catalog\Domain\Interfaces\ProductSizeRepositoryInterface;
use App\Modules\Catalog\Domain\Interfaces\SizeGridRepositoryInterface;
use App\Modules\Parser\Application\DTOs\ScraperAvailable\ScraperAvailableResultData;
use App\Modules\Parser\Domain\Entities\ParserProductSizeEntity;
use App\Modules\Parser\Domain\Interfaces\ParserCategoryRepositoryInterface;
use App\Modules\Parser\Domain\Interfaces\ParserProductRepositoryInterface;
use App\Modules\Parser\Domain\Interfaces\ScraperAvailableRepositoryInterface;
use App\Modules\Parser\Infrastructure\Mappers\SizeMapper;
use App\Modules\Parser\Infrastructure\Services\Common\CalculateParserPriceService;

/**
 * Обрабатывает результат проверки доступности товара скрапером.
 */
readonly class HandleScraperAvailableResultUseCase
{
    public function __construct(
        private ScraperAvailableRepositoryInterface $scraperAvailableRepository,
        private ParserProductRepositoryInterface    $parserProductRepository,
        private ProductRepositoryInterface          $productRepository,
        private ProductSizeRepositoryInterface      $productSizeRepository,
        private SizeGridRepositoryInterface         $sizeGridRepository,
        private ParserCategoryRepositoryInterface   $parserCategoryRepository,
        private SetProductSizesUseCase              $setProductSizesUseCase,
        private SizeMapper                          $sizeMapper,
        private CalculateParserPriceService         $calculate,
    )
    {
    }

    public function execute(ScraperAvailableResultData $dto): void
    {
        $record = $this->scraperAvailableRepository->getById($dto->id);

        $parserProduct = $this->parserProductRepository->getByUrl($record->url);

        if ($parserProduct === null) {
            $this->scraperAvailableRepository->delete($dto->id);

            return;
        }

        $product = $this->productRepository->getById($parserProduct->productId);

        // Цены: применяем только если переданы оба значения и они отличаются
        $priceBaseChanged = false;

        if ($dto->priceBase !== null && $dto->priceSell !== null) {
            if ($parserProduct->priceBase != $dto->priceBase) {
                $parserProduct->priceBase = (float) $dto->priceBase;
                $priceBaseChanged = true;
            }

            if ($parserProduct->priceSell != $dto->priceSell) {
                $parserProduct->priceSell = (float) $dto->priceSell;
            }
        }

        if ($priceBaseChanged) {
            $this->calculate->execute($parserProduct->priceBase, $product->id);
        }

        // Синхронизация размеров
        $category = $parserProduct->categoryId !== null
            ? $this->parserCategoryRepository->getById($parserProduct->categoryId)
            : null;

        if ($category !== null && $category->sizeGridId !== null) {
            // Гарантируем наличие вариантов размеров товара (если они были удалены)
            $this->setProductSizesUseCase->execute(new UpdateProductSizesData($product->id, $category->sizeGridId));

            $grid = $this->sizeGridRepository->getById($category->sizeGridId);

            // Все варианты сетки по умолчанию неактивны
            $desiredActive = [];
            foreach ($grid->getCombinations() as $values) {
                $desiredActive[ProductSizeEntity::keyFromValues($values)] = false;
            }

            $dimensionCode = $grid->dimensions[0]->code ?? 'size';
            $productSizes = $this->productSizeRepository->getByProductId($product->id);

            // Индекс существующих размеров парсера по каноническому ключу
            $existingByKey = [];
            foreach ($parserProduct->sizes as $existingSize) {
                if ($existingSize->values !== []) {
                    $existingByKey[ProductSizeEntity::keyFromValues($existingSize->values)] = $existingSize;
                }
            }

            $sizesForParser = [];
            $processedKeys = [];

            foreach ($dto->sizes as $size) {
                if (!is_array($size)) {
                    continue;
                }

                $sizeOriginal = (string) ($size['size'] ?? '');
                if ($sizeOriginal === '') {
                    continue;
                }

                $sizeProduct = $this->sizeMapper->map($sizeOriginal);
                $available = (bool) ($size['available'] ?? true);

                // Находим размер товара
                $sizeEntity = null;
                foreach ($productSizes as $productSize) {
                    if (($productSize->values[$dimensionCode] ?? null) === $sizeProduct) {
                        $sizeEntity = $productSize;
                        break;
                    }
                }

                if ($sizeEntity === null) {
                    continue;
                }

                $key = $sizeEntity->key();
                $processedKeys[$key] = true;

                if ($available) {
                    $desiredActive[$key] = true;
                }

                if (isset($existingByKey[$key])) {
                    /** @var ParserProductSizeEntity $parserSize */
                    $parserSize = $existingByKey[$key];
                    $parserSize->available = $available;
                    $parserSize->sizeOriginal = $sizeOriginal;
                    $parserSize->size = $sizeProduct;
                    $parserSize->sku = (string) ($size['sku'] ?? '');
                    $parserSize->price = (float) ($size['price'] ?? 0);
                    $sizesForParser[] = $parserSize;
                } else {
                    // Появился новый размер — добавляем в парсер-товар
                    $sizesForParser[] = ParserProductSizeEntity::create(
                        productSizeId: $sizeEntity->id,
                        sizeOriginal: $sizeOriginal,
                        size: $sizeProduct,
                        sku: (string) ($size['sku'] ?? ''),
                        price: (float) ($size['price'] ?? 0),
                        available: $available,
                    );
                }
            }

            // Отключаем размеры, которых больше нет в $dto->sizes
            foreach ($parserProduct->sizes as $existingSize) {
                if ($existingSize->values === []) {
                    $sizesForParser[] = $existingSize;
                    continue;
                }

                $key = ProductSizeEntity::keyFromValues($existingSize->values);
                if (isset($processedKeys[$key])) {
                    continue;
                }

                $existingSize->available = false;
                $sizesForParser[] = $existingSize;
            }

            $parserProduct->sizes = $sizesForParser;

            // Применяем размерный ряд товара
            $this->productSizeRepository->rebuildForProduct($product->id, $grid, $desiredActive);
        }

        $this->parserProductRepository->save($parserProduct);

        $this->scraperAvailableRepository->delete($dto->id);
    }
}
