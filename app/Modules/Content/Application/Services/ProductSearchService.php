<?php

declare(strict_types=1);

namespace App\Modules\Content\Application\Services;

use App\Modules\Catalog\Domain\Entities\ProductEntity;
use App\Modules\Catalog\Domain\Entities\ProductSizeEntity;
use App\Modules\Catalog\Domain\Interfaces\ProductRepositoryInterface;
use App\Modules\Catalog\Domain\Interfaces\ProductSizeRepositoryInterface;
use App\Modules\Catalog\Domain\Interfaces\SizeGridRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObjects\SizeGridDimension;
use App\Modules\Content\Application\DTOs\ProductWidget\ProductSearchResultData;
use App\Modules\Shared\Application\Actions\GetPhotoByEntityListUseCase;
use App\Modules\Shared\Application\DTOs\Photo\PhotoByEntityListData;
use App\Modules\Shared\Domain\Interfaces\PhotoRepositoryInterface;
use App\Modules\Shared\Domain\ValueObjects\PhotoType;

final readonly class ProductSearchService
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private PhotoRepositoryInterface $photoRepository,
        private GetPhotoByEntityListUseCase $photoByEntityListUseCase,
        private SizeGridRepositoryInterface $sizeGridRepository,
        private ProductSizeRepositoryInterface $productSizeRepository,
    ) {}

    private const string MODEL_TYPE = 'catalog.product';
    private const string PHOTO_TYPE = 'gallery';

    /**
     * Поиск товаров с подгрузкой изображений.
     *
     * @param string $query
     * @param int $limit
     * @return ProductSearchResultData[]
     */
    public function search(string $query, int $limit = 10): array
    {
        $products = $this->productRepository->search($query, $limit);

        if (empty($products))
            return [];


        $productIds = array_map(fn(ProductEntity $p) => $p->id, $products);
        $images = $this->loadImages($productIds);
        $sizes = $this->loadSizes($products);

        return array_map(
            fn(ProductEntity $product) => $this->toResultData(
                $product,
                $images[$product->id] ?? [],
                $sizes[$product->id] ?? null,
            ),
            $products,
        );
    }

    /**
     * Получить товар по ID с подгрузкой изображений.
     *
     * @param int $id
     * @return ProductSearchResultData|null
     */
    public function getById(int $id): ?ProductSearchResultData
    {
        try {
            $product = $this->productRepository->getById($id);
        } catch (\Throwable) {
            return null;
        }

        $images = $this->loadImages([$product->id]);
        $sizes = $this->loadSizes([$product]);
        return $this->toResultData($product, $images[$product->id] ?? [], $sizes[$product->id] ?? null);
    }

    /**
     * @param int[] $productIds
     * @return array<int, array{src: ?string, alt: ?string, next_src: ?string, next_alt: ?string}>
     */
    private function loadImages(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        $type = new PhotoType(self::PHOTO_TYPE);
        // findByEntities возвращает array<int, string> — imageableId => uploadUrl первого фото
        //$firstPhotos = $this->photoRepository->findByEntities($productIds, self::MODEL_TYPE, $type);
        $dto = new PhotoByEntityListData($productIds, self::MODEL_TYPE, $type->getValue());
        $firstPhotos = $this->photoByEntityListUseCase->execute($dto);
        $result = [];
        foreach ($productIds as $id) {
            $result[$id] = [
                'src' => $firstPhotos[$id] ?? null,
                'alt' => null, // alt не возвращается findByEntities
                'next_src' => null,
                'next_alt' => null,
            ];
        }

        return $result;
    }

    private function toResultData(ProductEntity $product, array $images, ?array $sizes = null): ProductSearchResultData
    {
        return new ProductSearchResultData(
            id: $product->id,
            name: $product->name,
            code: $product->code->getCode(),
            code_search: $product->code->getCodeSearch(),
            url: route('shop.product.view', $product->slug, true),
            short: $product->short,
            price: null, // цена будет добавлена позже
            quantity: 1,
            in_stock: true,
            image_src: $images['src'] ?? null,
            image_alt: $images['alt'] ?? null,
            image_next_src: $images['next_src'] ?? null,
            image_next_alt: $images['next_alt'] ?? null,
            sizes: $sizes,
        );
    }

    /**
     * Активные варианты размеров для товаров с настроенной размерной сеткой.
     *
     * @param ProductEntity[] $products
     * @return array<int, array<int, array{productSizeId: int, label: string}>> product_id => sizes[]
     */
    private function loadSizes(array $products): array
    {
        $withGrid = array_values(array_filter(
            $products,
            fn(ProductEntity $p) => $p->sizeGridId !== null,
        ));

        if ($withGrid === []) {
            return [];
        }

        $gridIds = array_values(array_unique(array_map(
            fn(ProductEntity $p) => $p->sizeGridId,
            $withGrid,
        )));

        $grids = $this->sizeGridRepository->getByIds($gridIds);

        $productIds = array_map(fn(ProductEntity $p) => $p->id, $withGrid);
        $sizesByProduct = $this->productSizeRepository->getActiveByProductIds($productIds);

        $result = [];

        foreach ($withGrid as $product) {
            $grid = $grids[$product->sizeGridId] ?? null;
            if ($grid === null) {
                continue;
            }

            $sizes = $sizesByProduct[$product->id] ?? [];
            if ($sizes === []) {
                continue;
            }

            $codes = array_map(
                fn(SizeGridDimension $dimension) => $dimension->code,
                $grid->dimensions,
            );

            $result[$product->id] = array_map(
                fn(ProductSizeEntity $size) => [
                    'productSizeId' => $size->id,
                    'label' => $this->buildLabel($size->values, $codes),
                ],
                $sizes,
            );
        }

        return $result;
    }

    /**
     * Подпись варианта размера: одна ось — её значение, две и более —
     * "первое значение (остальные через запятую)".
     *
     * @param array<string, string> $values
     * @param string[] $codes Коды осей в порядке объявления в сетке
     */
    private function buildLabel(array $values, array $codes): string
    {
        $parts = [];

        foreach ($codes as $code) {
            $value = $values[$code] ?? '';
            if ($value !== '') {
                $parts[] = $value;
            }
        }

        if (count($parts) === 1) {
            return $parts[0];
        }

        if (count($parts) >= 2) {
            return $parts[0] . ' (' . implode(', ', array_slice($parts, 1)) . ')';
        }

        return implode(' ', $parts);
    }
}
